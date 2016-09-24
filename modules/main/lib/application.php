<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Data;
use Bitrix\Main\Diag;
use Bitrix\Main\IO;

/**
 * Base class for any application.
 */
abstract class Application
{
	/**
	 * @var Application
	 */
	protected static $instance = null;

	protected $isBasicKernelInitialized = false;
	protected $isExtendedKernelInitialized = false;

	/**
	 * Execution context.
	 *
	 * @var Context
	 */
	protected $context;

	/**
	 * Pool of database connections.
	 *
	 * @var Data\ConnectionPool
	 */
	protected $connectionPool;

	/**
	 * Managed cache instance.
	 *
	 * @var \Bitrix\Main\Data\ManagedCache
	 */
	protected $managedCache;

	/**
	 * Tagged cache instance.
	 *
	 * @var \Bitrix\Main\Data\TaggedCache
	 */
	protected $taggedCache;

	/**
	 * LRU cache instance.
	 *
	 * @var \Bitrix\Main\Data\LruCache
	 */
	protected $lruCache;

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

	}

	/**
	 * Returns current instance of the Application.
	 *
	 * @return Application
	 * @throws SystemException
	 */
	
	/**
	* <p>Статический метод возвращает текущий экземпляр приложения.</p> <p>Без параметров</p>
	*
	*
	* @return \Bitrix\Main\Application 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Объект приложения можно получить так:$application = Application::getInstance();
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getinstance.php
	* @author Bitrix
	*/
	public static function getInstance()
	{
		if (!isset(static::$instance))
			static::$instance = new static();

		return static::$instance;
	}

	/**
	 * Does minimally possible kernel initialization
	 *
	 * @throws SystemException
	 */
	
	/**
	* <p>Нестатичный метод производит первичную инициализацию ядра.</p> <p>Без параметров</p>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/initializebasickernel.php
	* @author Bitrix
	*/
	public function initializeBasicKernel()
	{
		if ($this->isBasicKernelInitialized)
			return;
		$this->isBasicKernelInitialized = true;

		$this->initializeExceptionHandler();
		$this->initializeCache();
		$this->createDatabaseConnection();
	}

	/**
	 * Does full kernel initialization. Should be called somewhere after initializeBasicKernel()
	 *
	 * @param array $params Parameters of the current request (depends on application type)
	 * @throws SystemException
	 */
	
	/**
	* <p>Нестатический метод производит полную инициализацию ядра. Метод следует вызывать после метода <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/initializebasickernel.php">initializeBasicKernel</a>.</p>
	*
	*
	* @param array $params  Параметры текущего запроса (в зависимости от типа приложения).
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/initializeextendedkernel.php
	* @author Bitrix
	*/
	public function initializeExtendedKernel(array $params)
	{
		if ($this->isExtendedKernelInitialized)
			return;
		$this->isExtendedKernelInitialized = true;

		$this->initializeContext($params);

		//$this->initializeDispatcher();
	}

	final public function getDispatcher()
	{
		if (is_null($this->dispatcher))
			throw new NotSupportedException();
		if (!($this->dispatcher instanceof Dispatcher))
			throw new NotSupportedException();

		return clone $this->dispatcher;
	}

	/**
	 * Initializes context of the current request.
	 * Should be implemented in subclass.
	 */
	abstract protected function initializeContext(array $params);

	/**
	 * Starts request execution. Should be called after initialize.
	 * Should be implemented in subclass.
	 */
	
	/**
	* <p>Нестатический метод запускает выполнение запроса. Вызывается после методов инициализации.</p> <p>Следует реализовывать как подкласс.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/start.php
	* @author Bitrix
	*/
	abstract public function start();

	/**
	 * Exception handler can be initialized through the Config\Configuration (.settings.php file).
	 *
	 * 'exception_handling' => array(
	 *		'value' => array(
	 *			'debug' => true,        // output exception on screen
	 *			'handled_errors_types' => E_ALL & ~E_STRICT & ~E_NOTICE,    // catchable error types, printed to log
	 *			'exception_errors_types' => E_ALL & ~E_NOTICE & ~E_STRICT,  // error types from catchable which throws exceptions
	 *			'ignore_silence' => false,      // ignore @
	 *			'assertion_throws_exception' => true,       // assertion throws exception
	 *			'assertion_error_type' => 256,
	 *			'log' => array(
	 *              'class_name' => 'MyLog',        // custom log class, must extends ExceptionHandlerLog; can be omited, in this case default Diag\FileExceptionHandlerLog will be used
	 *              'extension' => 'MyLogExt',      // php extension, is used only with 'class_name'
	 *              'required_file' => 'modules/mylog.module/mylog.php'     // included file, is used only with 'class_name'
	 *				'settings' => array(        // any settings for 'class_name'
	 *					'file' => 'bitrix/modules/error.log',
	 *					'log_size' => 1000000,
	 *				),
	 *			),
	 *		),
	 *		'readonly' => false,
	 *	),
	 *
	 */
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

		$exceptionHandler->initialize(
			array($this, "createExceptionHandlerOutput"),
			array($this, "createExceptionHandlerLog")
		);

		$this->exceptionHandler = $exceptionHandler;
	}

	static public function createExceptionHandlerLog()
	{
		$exceptionHandling = Config\Configuration::getValue("exception_handling");
		if ($exceptionHandling === null || !is_array($exceptionHandling) || !isset($exceptionHandling["log"]) || !is_array($exceptionHandling["log"]))
			return null;

		$options = $exceptionHandling["log"];

		$log = null;

		if (isset($options["class_name"]) && !empty($options["class_name"]))
		{
			if (isset($options["extension"]) && !empty($options["extension"]) && !extension_loaded($options["extension"]))
				return null;

			if (isset($options["required_file"]) && !empty($options["required_file"]) && ($requiredFile = Loader::getLocal($options["required_file"])) !== false)
				require_once($requiredFile);

			$className = $options["class_name"];
			if (!class_exists($className))
				return null;

			$log = new $className();
		}
		elseif (isset($options["settings"]) && is_array($options["settings"]))
		{
			$log = new Diag\FileExceptionHandlerLog();
		}
		else
		{
			return null;
		}

		$log->initialize(
			isset($options["settings"]) && is_array($options["settings"]) ? $options["settings"] : array()
		);

		return $log;
	}

	static public function createExceptionHandlerOutput()
	{
		return new Diag\ExceptionHandlerOutput();
	}

	/**
	 * Creates database connection pool.
	 */
	protected function createDatabaseConnection()
	{
		$this->connectionPool = new Data\ConnectionPool();
	}

	protected function initializeCache()
	{
		//TODO: Should be transfered to where GET parameter is defined in future
		//magic parameters: show cache usage statistics
		$show_cache_stat = "";
		if (isset($_GET["show_cache_stat"]))
		{
			$show_cache_stat = (strtoupper($_GET["show_cache_stat"]) == "Y" ? "Y" : "");
			@setcookie("show_cache_stat", $show_cache_stat, false, "/");
		}
		elseif (isset($_COOKIE["show_cache_stat"]))
		{
			$show_cache_stat = $_COOKIE["show_cache_stat"];
		}
		Data\Cache::setShowCacheStat($show_cache_stat === "Y");

		if (isset($_GET["clear_cache_session"]))
			Data\Cache::setClearCacheSession($_GET["clear_cache_session"] === 'Y');
		if (isset($_GET["clear_cache"]))
			Data\Cache::setClearCache($_GET["clear_cache"] === 'Y');
	}

	/*
	final private function initializeDispatcher()
	{
		$dispatcher = new Dispatcher();
		$dispatcher->initialize();
		$this->dispatcher = $dispatcher;
	}
	*/

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
	 * @return Data\ConnectionPool
	 */
	
	/**
	* <p>Нестатический метод возвращает объект пула соединений базы данных.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Data\ConnectionPool 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getconnectionpool.php
	* @author Bitrix
	*/
	public function getConnectionPool()
	{
		return $this->connectionPool;
	}

	/**
	 * Returns context of the current request.
	 *
	 * @return Context
	 */
	
	/**
	* <p>Нестатический метод возвращает содержание текущего соединения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Context 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getcontext.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод изменяет содержание текущего запроса.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Context $context  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/setcontext.php
	* @author Bitrix
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
	 * @return DB\Connection
	 */
	
	/**
	* <p>Статический метод возвращает соединение с базой данных указанного имени. Если параметр <b>name</b> - пустой, то возвращается соединение по умолчанию.</p>
	*
	*
	* @param string $name = "" Название соединения. Если пустое - то соединение по умолчанию.
	*
	* @return \Bitrix\Main\DB\Connection 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Как из класса приложения получить соединение с БД$connection = Application::getConnection();use Bitrix\Main\Application;
	* use Bitrix\Main\Diag\Debug;
	* 
	* $record = Application::getConnection()
	* -&gt;query("select 1+1;")
	* -&gt;fetch();
	* Debug::writeToFile($record);
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getconnection.php
	* @author Bitrix
	*/
	public static function getConnection($name = "")
	{
		$pool = Application::getInstance()->getConnectionPool();
		return $pool->getConnection($name);
	}

	/**
	 * Returns new instance of the Cache object.
	 *
	 * @return Data\Cache
	 */
	
	/**
	* <p>Возвращает новый экземпляр объекта кеша. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Data\Cache 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getcache.php
	* @author Bitrix
	*/
	static public function getCache()
	{
		return Data\Cache::createInstance();
	}

	/**
	 * Returns manager of the managed cache.
	 *
	 * @return Data\ManagedCache
	 */
	
	/**
	* <p>Нестатический метод  возвращает объект управляемого кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Data\ManagedCache 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getmanagedcache.php
	* @author Bitrix
	*/
	public function getManagedCache()
	{
		if ($this->managedCache == null)
		{
			$this->managedCache = new Data\ManagedCache();
		}

		return $this->managedCache;
	}

	/**
	 * Returns manager of the managed cache.
	 *
	 * @return Data\TaggedCache
	 */
	
	/**
	* <p>Нестатический метод возвращает объект тегированного кеша.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Data\TaggedCache 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/gettaggedcache.php
	* @author Bitrix
	*/
	public function getTaggedCache()
	{
		if ($this->taggedCache == null)
		{
			$this->taggedCache = new Data\TaggedCache();
		}

		return $this->taggedCache;
	}

	/**
	 * Returns true id server is in utf-8 mode. False - otherwise.
	 *
	 * @return bool
	 */
	
	/**
	* <p>Статический метод вернёт <i>true</i> если сервер работает в utf-8. И вернёт <i>false</i> в противном случае.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/isutfmode.php
	* @author Bitrix
	*/
	public static function isUtfMode()
	{
		static $isUtfMode = null;
		if ($isUtfMode === null)
		{
			$isUtfMode = Config\Configuration::getValue("utf_mode");
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
	
	/**
	* <p>Статический метод возвращает <i>document root</i> сервера.</p> <p class="note">Обратите внимание: вместо <b>$_SERVER["DOCUMENT_ROOT"]</b> сейчас можно использовать <i>Bitrix\Main\Application::getDocumentRoot()</i>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Как из класса приложения получить <i>document_root</i>:$docRoot = Application::getDocumentRoot()
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getdocumentroot.php
	* @author Bitrix
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

		return Loader::getDocumentRoot();
	}

	/**
	 * Returns personal root directory (relative to document root)
	 *
	 * @return null|string
	 */
	
	/**
	* <p>Статический метод возвращает путь к персональной директории (относительно <i>document root</i>).</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/getpersonalroot.php
	* @author Bitrix
	*/
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

		return isset($_SERVER["BX_PERSONAL_ROOT"]) ? $_SERVER["BX_PERSONAL_ROOT"] : "/bitrix";
	}

	/**
	 * Resets accelerator if any.
	 */
	
	/**
	* <p>Статический метод производит перезапуск акселлератора.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/application/resetaccelerator.php
	* @author Bitrix
	*/
	public static function resetAccelerator()
	{
		if (defined("BX_NO_ACCELERATOR_RESET"))
			return;

		$fl = Config\Configuration::getValue("no_accelerator_reset");
		if ($fl)
			return;

		if (function_exists("accelerator_reset"))
			accelerator_reset();
		elseif (function_exists("wincache_refresh_if_changed"))
			wincache_refresh_if_changed();
	}
}
