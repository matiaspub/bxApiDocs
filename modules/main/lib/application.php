<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use \Bitrix\Main\IO;

/**
 * Base class for any application.
 */
abstract class Application
{
	/**
	 * @var Application
	 */
	protected static $instance = null;

	/**
	 * Execution context.
	 *
	 * @var Context
	 */
	protected $context;

	/**
	 * Pool of database connections.
	 *
	 * @var \Bitrix\Main\DB\DbConnectionPool
	 */
	protected $dbConnectionPool;

	/**
	 * Managed cache instance.
	 *
	 * @var \Bitrix\Main\Data\ManagedCache
	 */
	protected $managedCache;

	/**
	 * LRU cache instance.
	 *
	 * @var \Bitrix\Main\Data\LruCache
	 */
	protected $lruCache;

	/**
	 * @var \Bitrix\Main\System\IApplicationStrategy
	 */
	protected $initializationStrategy = null;

	private $transferUri = null;

	private $isKernelInitialized = false;
	private $isShellInitialized = false;

	/**
	 * @var \Bitrix\Main\Diag\ExceptionHandler
	 */
	protected $exceptionHandler = null;

	/**
	 * @var Dispatcher
	 */
	private $dispatcher = null;

	/**
	 * Creates new application instance.
	 */
	protected function __construct()
	{
		$this->isKernelInitialized = false;
		$this->isShellInitialized = false;
		$this->transferUri = null;
	}

	/**
	 * Returns current instance of the server.
	 * Server should be previously started by start()
	 *
	 * @return Application
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance))
			self::$instance = new static();

		return static::$instance;
	}

	public function turnOnCompatibleMode()
	{
		$this->initializationStrategy = new \Bitrix\Main\System\CompatibleStrategy();
	}

	protected function canSetInputParameters()
	{
		return !$this->isKernelInitialized;
	}

	protected function setTransferUri($url)
	{
		if ($this->isKernelInitialized)
			throw new SystemException("Url rewriting is allowed only during kernel initialization");

		$this->transferUri = $url;
	}

	private function transferUri($url)
	{
		$url = IO\Path::normalize($url);

		$urlTmp = trim($url, " \t\n\r\0\x0B\\/");
		if (empty($urlTmp))
			throw new ArgumentNullException("url");

		$ext = IO\Path::getExtension($url);
		if (strtolower($ext) != "php")
			throw new SystemException("Only php files are allowable for url rewriting");

		$arUrl = explode("/", $url);
		$rootDirName = "";
		while (!empty($arUrl) && ($rootDirName = array_shift($arUrl)) === "");
		$rootDirName = strtolower(str_replace(".", "", $rootDirName));
		if (in_array($rootDirName, array("bitrix", "local", "upload")))
			throw new SystemException(sprintf("Can not use path '%s' for url rewriting", $url));

		if (!IO\Path::validate($url))
			throw new SystemException(sprintf("Path '%s' is not valid", $url));

		$absUrl = IO\Path::convertRelativeToAbsolute($url);
		if (!IO\File::isFileExists($absUrl))
			throw new SystemException(sprintf("Path '%s' is not found", $url));

		$absUrlPhysical = IO\Path::convertLogicalToPhysical($absUrl);

		global $APPLICATION, $USER, $DB;

		include_once($absUrlPhysical);

		die();
	}

	/**
	 * Initializes application. Should be called before start() method.
	 */
	final public function initialize()
	{
		$initStrategy = $this->initializationStrategy;

		if (!$this->isKernelInitialized)
		{
			if ($initStrategy != null)
				$initStrategy->preInitialize();

			$this->initializeKernel($initStrategy);

			$this->isKernelInitialized = true;
		}

		if (!empty($this->transferUri))
		{
			$transferUri = $this->transferUri;
			$this->transferUri = null;

			$this->transferUri($transferUri);
			die();
		}

		if (!$this->isShellInitialized)
		{
			$this->initializeShell($initStrategy);

			if ($initStrategy != null)
				$initStrategy->postInitialize();

			$this->isShellInitialized = true;
		}
	}

	/**
	 * Initializes application kernel.
	 * Шаблонный метод определяет основу алгоритма и позволяет наследникам переопределять
	 * некоторые шаги алгоритма, не изменяя его структуру в целом.
	 *
	 * @param System\IApplicationStrategy $initStrategy
	 */
	final protected function initializeKernel(\Bitrix\Main\System\IApplicationStrategy $initStrategy = null)
	{
		/*** Базовая инициализация, без которой нельзя ***/

		$this->initializeExceptionHandler();

		//<start.php>
		// подключение конфига
		// константы
		// соединение
		$this->createDatabaseConnection();

		if ($initStrategy != null)
			$initStrategy->createDatabaseConnection();

		// в проактивной защите нужно делать поддержку контекста
		$this->initializeContext();
		if ($initStrategy != null)
			$initStrategy->initializeContext();   /// это не нужно

		//</start.php>

		//<include.php>
		//error_reporting(\COption::getOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) & ~E_STRICT);

		// это видимо на страницу, но нужно до апдейтера
		//if(!defined("BX_COMP_MANAGED_CACHE") && \COption::getOptionString("main", "component_managed_cache_on", "Y") <> "N")
		//	// define("BX_COMP_MANAGED_CACHE", true);

		// определяется сайт и язык
		// видимо, определение языка нужно куда-то отдельно
		// и там еще $APPLICATION->reinitPath

		$this->initializeCulture();   // добавить после этого инициализацию переменных

		$this->initializeBasicKernel();
		if ($initStrategy != null)
			$initStrategy->initializeBasicKernel();

		/*** Лицензии и обновления ***/

		$this->updateMainDb(); // нужны переделки контроллера и тогда делать редирект
		$this->updateModulesDB();

		$this->initializeDispatcher();
		if ($initStrategy != null)
			$initStrategy->initializeDispatcher();

		/*** Переопределение пути ***/

		$this->rewriteUrlIfNeeded();

		/*** Расширенная инициализация ***/

		// выполнение кастомных скриптов инициализации и определение констант после init.php
		$this->runInitScripts();
		if ($initStrategy != null)
			$initStrategy->runInitScripts();

		$this->initializeExtendedKernel();
		if ($initStrategy != null)
			$initStrategy->initializeExtendedKernel();
	}

	final public function getDispatcher()
	{
		if (is_null($this->dispatcher))
			throw new NotSupportedException();
		if (!($this->dispatcher instanceof Dispatcher))
			throw new NotSupportedException();

		return clone $this->dispatcher;
	}

	abstract protected function rewriteUrlIfNeeded();

	/**
	 * Initializes context of the current request.
	 * Should be implemented in subclass.
	 */
	abstract protected function initializeContext();

	/**
	 * Initializes default culture of the current request.
	 * Should be implemented in subclass.
	 */
	abstract protected function initializeCulture();

	/**
	 * Initializes basic part of kernel. It is called before update system call.
	 * Should be implemented in subclass.
	 */
	abstract protected function initializeBasicKernel();

	/**
	 * Initializes extended part of kernel. It is called after update system call.
	 * Should be implemented in subclass.
	 */
	abstract protected function initializeExtendedKernel();

	/**
	 * Initializes application shell. Called after initializeKernel.
	 * Should be implemented in subclass.
	 *
	 * @param System\IApplicationStrategy $initStrategy
	 */
	abstract protected function initializeShell(\Bitrix\Main\System\IApplicationStrategy $initStrategy = null);

	/**
	 * Starts request execution. Should be called after initialize.
	 * Should be implemented in subclass.
	 */
	abstract public function start();

	protected function updateMainDb()
	{
		if (file_exists(($fname = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/update_db_updater.php")))
		{
			global $US_HOST_PROCESS_MAIN;
			$US_HOST_PROCESS_MAIN = True;
			include($fname);
		}
	}

	protected function updateModulesDb()
	{
		if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/update_db_updater.php")))
		{
			$GLOBALS["US_HOST_PROCESS_MAIN"] = False;
			include($_fname);
		}

		//include("module_updater.php");
	}

	protected function initializeExceptionHandler()
	{
		$exceptionHandler = new Diag\ExceptionHandler();

		$exceptionHandling = Config\Configuration::getValue("exception_handling");
		if ($exceptionHandling == null)
			$exceptionHandling = array();

		if (!isset($exceptionHandling["debug"]) || !is_bool($exceptionHandling["debug"]))
			$exceptionHandling["debug"] = false;
		$exceptionHandler->setDebugMode($exceptionHandling["debug"]);

		if (isset($exceptionHandling["handled_errors_types"]) && is_int($exceptionHandling["handled_errors_types"]))
			$exceptionHandler->setHandledErrorsTypes($exceptionHandling["handled_errors_types"]);

		if (isset($exceptionHandling["exception_errors_types"]) && is_int($exceptionHandling["exception_errors_types"]))
			$exceptionHandler->setExceptionErrorsTypes($exceptionHandling["exception_errors_types"]);

		if (isset($exceptionHandling["ignore_silence"]) && is_bool($exceptionHandling["ignore_silence"]))
			$exceptionHandler->setIgnoreSilence($exceptionHandling["ignore_silence"]);

		if (isset($exceptionHandling["assertion_throws_exception"]) && is_bool($exceptionHandling["assertion_throws_exception"]))
			$exceptionHandler->setAssertionThrowsException($exceptionHandling["assertion_throws_exception"]);

		if (isset($exceptionHandling["assertion_error_type"]) && is_int($exceptionHandling["assertion_error_type"]))
			$exceptionHandler->setAssertionErrorType($exceptionHandling["assertion_error_type"]);

		$exceptionHandlerOutput = $this->createExceptionHandlerOutput();
		$exceptionHandlerLog = $this->createExceptionHandlerLog(
			(isset($exceptionHandling["log"]) && is_array($exceptionHandling["log"])) ? $exceptionHandling["log"] : array()
		);

		$exceptionHandler->initialize($exceptionHandlerOutput, $exceptionHandlerLog);

		$this->exceptionHandler = $exceptionHandler;
	}

	protected function createExceptionHandlerLog(array $options)
	{
		$log = null;

		if (isset($options["class_name"]) && !empty($options["class_name"]))
		{
			if (isset($options["extension"]) && !empty($options["extension"]) && !extension_loaded($options["extension"]))
				throw new Config\ConfigurationException(sprintf("Extension '%s' is not loaded for exception handler log", $options["extension"]));

			if (isset($options["required_file"]) && !empty($options["required_file"]) && ($requiredFile = Loader::getLocal($options["required_file"])) !== false)
				require_once($requiredFile);

			$className = $options["class_name"];
			if (!class_exists($className))
				throw new Config\ConfigurationException(sprintf("Class '%s' does not exist for exception handler log", $className));

			$log = new $className();
		}
		else
		{
			$log = new Diag\FileExceptionHandlerLog();
		}

		$log->initialize(
			isset($options["settings"]) && is_array($options["settings"]) ? $options["settings"] : array()
		);

		return $log;
	}

	protected function createExceptionHandlerOutput()
	{
		return new Diag\ExceptionHandlerOutput();
	}

	/**
	 * Creates database connection pool.
	 */
	protected function createDatabaseConnection()
	{
		$this->dbConnectionPool = new \Bitrix\Main\Data\ConnectionPool();
	}

	final private function initializeDispatcher()
	{
		$dispatcher = new Dispatcher();
		$dispatcher->initialize();
		$this->dispatcher = $dispatcher;
	}

	protected function runInitScripts()
	{
		if (!($this->dispatcher instanceof Dispatcher))
			throw new \Exception();

		if (($includePath = Loader::getLocal("init_d7.php")) !== false)
			require_once($includePath);

		if (($includePath = Loader::getPersonal("php_interface/init_d7.php")) !== false)
			require_once($includePath);

		// константы после init.php
		// define("BX_CRONTAB_SUPPORT", defined("BX_CRONTAB"));

		if(!defined("BX_FILE_PERMISSIONS"))
			// define("BX_FILE_PERMISSIONS", 0644);
		if(!defined("BX_DIR_PERMISSIONS"))
			// define("BX_DIR_PERMISSIONS", 0755);
	}

	/**
	 * @return \Bitrix\Main\Diag\ExceptionHandler
	 */
	public function getExceptionHandler()
	{
		return $this->exceptionHandler;
	}

	/**
	 * Returns database connections pool object.
	 *
	 * @return DB\DbConnectionPool
	 */
	public function getDbConnectionPool()
	{
		return $this->dbConnectionPool;
	}

	/**
	 * Returns context of the current request.
	 *
	 * @return Context
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * Modifies context of the current request.
	 *
	 * @param Context $context
	 */
	public function setContext(Context $context)
	{
		$this->context = $context;
	}

	/**
	 * Static method returns database connection for the specified name.
	 * If name is empty - default connection is returned.
	 *
	 * @static
	 * @param string $name Name of database connection. If empty - default connection.
	 * @return DB\DbConnection
	 */
	public static function getDbConnection($name = "")
	{
		$pool = Application::getInstance()->getDbConnectionPool();
		return $pool->getConnection($name);
	}

	/**
	 * Returns new instance of the Cache object.
	 *
	 * @return Data\Cache
	 */
	static public function getCache()
	{
		return \Bitrix\Main\Data\Cache::createInstance();
	}

	/**
	 * Returns manager of the managed cache.
	 *
	 * @return Data\ManagedCache
	 */
	public function getManagedCache()
	{
		if ($this->managedCache == null)
			$this->managedCache = new \Bitrix\Main\Data\ManagedCache();
		return $this->managedCache;
	}

	/**
	 * Returns true id server is in utf-8 mode. False - otherwise.
	 *
	 * @return bool
	 */
	public static function isUtfMode()
	{
		static $isUtfMode = null;
		if ($isUtfMode === null)
		{
			$isUtfMode = \Bitrix\Main\Config\Configuration::getValue("utf_mode");
			if ($isUtfMode === null)
				$isUtfMode = false;
		}
		return $isUtfMode;
	}

	/**
	 * Returns server document root.
	 *
	 * @return null|string
	 */
	public static function getDocumentRoot()
	{
		static $documentRoot = null;
		if ($documentRoot != null)
			return $documentRoot;

		$context = Application::getInstance()->getContext();
		if ($context != null)
		{
			$server = $context->getServer();
			if ($server != null)
				return $documentRoot = $server->getDocumentRoot();
		}

		return rtrim($_SERVER["DOCUMENT_ROOT"], "\\/");
	}

	public static function getPersonalRoot()
	{
		static $personalRoot = null;
		if ($personalRoot != null)
			return $personalRoot;

		$context = Application::getInstance()->getContext();
		if ($context != null)
		{
			$server = $context->getServer();
			if ($server != null)
				return $personalRoot = $server->getPersonalRoot();
		}

		if (!empty($_SERVER["BX_PERSONAL_ROOT"]))
			return $_SERVER["BX_PERSONAL_ROOT"];

		return "/bitrix";
	}

	/**
	 * Resets accelerator if any.
	 */
	public static function resetAccelerator()
	{
		$fl = \Bitrix\Main\Config\Configuration::getValue("NoAcceleratorReset");
		if ($fl)
			return;

		if (function_exists("accelerator_reset"))
			accelerator_reset();
		elseif (function_exists("wincache_refresh_if_changed"))
			wincache_refresh_if_changed();
	}
}

/**

prolog.php
	include loader
	create application
	set input parameters
	application initialize
		initialize kernel
			initialize exception handler
			create database connection
			initialize context
			initialize culture (default)
			initialize basic kernel
			run updaters
			initialize dispatcher
			rewrite url (if needed)
			run custom init scripts
			initialize extended kernel
				agents
				session
				OnPageStart
		initialize shell
			authenticate user
			OnBeforeProlog
			authorize user
	create page
	application start
		exception handler output -> page
		register shutdown function - (finish)
		page start request
			initialize site
				update context culture
			initialize site template


	CMain::PrologActions();
	require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");


(finish)
	page render
	response flush

 */