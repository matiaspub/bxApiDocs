<?php
namespace Bitrix\Main;

/**
 * Class Loader loads required files, classes and modules. It is the only class which is included directly.
 * @package Bitrix\Main
 */
final class Loader
{
	/**
	 * Can be used to prevent loading all modules except main and fileman
	 */
	const SAFE_MODE = false;

	const BITRIX_HOLDER = "bitrix";
	const LOCAL_HOLDER = "local";

	private static $safeModeModules = array("main", "fileman");

	private static $arLoadedModules = array("main" => true);
	private static $arSemiloadedModules = array();
	private static $arLoadedModulesHolders = array("main" => self::BITRIX_HOLDER);
	private static $arSharewareModules = array();

	/**
	 * Returned by includeSharewareModule() if module is not found
	 */
	const MODULE_NOT_FOUND = 0;
	/**
	 * Returned by includeSharewareModule() if module is installed
	 */
	const MODULE_INSTALLED = 1;
	/**
	 * Returned by includeSharewareModule() if module works in demo mode
	 */
	const MODULE_DEMO = 2;
	/**
	 * Returned by includeSharewareModule() if the trial period is expired
	 */
	const MODULE_DEMO_EXPIRED = 3;

	private static $arAutoLoadClasses = array();

	private static $isAutoLoadOn = true;

	const ALPHA_LOWER = "qwertyuioplkjhgfdsazxcvbnm";
	const ALPHA_UPPER = "QWERTYUIOPLKJHGFDSAZXCVBNM";

	/**
	 * Includes module by its name
	 *
	 * @param string $moduleName Name of the included module
	 * @return bool Returns true if module was included successfully, otherwise returns false
	 * @throws LoaderException
	 */
	
	/**
	* <p>Статический метод подключает модуль по его имени. Возвращает <i>true</i> если подключение успешно и <i>false</i> в обратном случае.</p> <p>Аналог метода <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cmodule/includemodule.php" >CModule::IncludeModule</a> в старом ядре.</p>
	*
	*
	* @param string $moduleName  Имя подключаемого модуля.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/includemodule.php
	* @author Bitrix
	*/
	public static function includeModule($moduleName)
	{
		if (!is_string($moduleName) || $moduleName == "")
			throw new LoaderException("Empty module name");
		if (preg_match("#[^a-zA-Z0-9._]#", $moduleName))
			throw new LoaderException(sprintf("Module name '%s' is not correct", $moduleName));

		$moduleName = strtr($moduleName, static::ALPHA_UPPER, static::ALPHA_LOWER);

		if (self::SAFE_MODE)
		{
			if (!in_array($moduleName, self::$safeModeModules))
				return false;
		}

		if (isset(self::$arLoadedModules[$moduleName]))
			return self::$arLoadedModules[$moduleName];

		if (isset(self::$arSemiloadedModules[$moduleName]))
			trigger_error("Module '".$moduleName."' is in loading progress", E_USER_WARNING);

		$arInstalledModules = ModuleManager::getInstalledModules();
		if (!isset($arInstalledModules[$moduleName]))
			return self::$arLoadedModules[$moduleName] = false;

		$documentRoot = static::getDocumentRoot();

		$moduleHolder = self::LOCAL_HOLDER;
		$pathToInclude = $documentRoot."/".$moduleHolder."/modules/".$moduleName."/include.php";
		if (!file_exists($pathToInclude))
		{
			$moduleHolder = self::BITRIX_HOLDER;
			$pathToInclude = $documentRoot."/".$moduleHolder."/modules/".$moduleName."/include.php";
			if (!file_exists($pathToInclude))
				return self::$arLoadedModules[$moduleName] = false;
		}

		self::$arLoadedModulesHolders[$moduleName] = $moduleHolder;
		self::$arSemiloadedModules[$moduleName] = true;

		$res = self::includeModuleInternal($pathToInclude);

		unset(self::$arSemiloadedModules[$moduleName]);
		if ($res === false)
			return self::$arLoadedModules[$moduleName] = false;

		return self::$arLoadedModules[$moduleName] = true;
	}

	private static function includeModuleInternal($path)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $DB, $MESS;
		return include_once($path);
	}

	/**
	 * Includes shareware module by its name.
	 * Module must initialize constant <module name>_DEMO = Y in include.php to define demo mode.
	 * include.php must return false to define trial period expiration.
	 * Constants is used because it is easy to obfuscate them.
	 *
	 * @param string $moduleName Name of the included module
	 * @return int One of the following constant: Loader::MODULE_NOT_FOUND, Loader::MODULE_INSTALLED, Loader::MODULE_DEMO, Loader::MODULE_DEMO_EXPIRED
	 */
	
	/**
	* <p>Статический метод подключает партнёрский модуль по его имени.</p> <p>Модуль должен инициировать константу <code>&lt;module name&gt;_DEMO = Y</code> в файле <b>include.php</b> для определения деморежима. Файл <b>include.php</b> должен возвращать <i>false</i> для опредения истёкшего триал-периода.</p> <p>Возвращает одну из следующих констант:<br><code>Loader::MODULE_NOT_FOUND</code>, <br><code>Loader::MODULE_INSTALLED</code>, <br><code>Loader::MODULE_DEMO</code>, <br><code>Loader::MODULE_DEMO_EXPIRED</code>.</p> <p>Константы используются в силу того, что их легче подвергать обфускации.</p> <p>Аналог метода <b>CModule::IncludeModuleEx</b> в старом ядре.</p>
	*
	*
	* @param string $moduleName  Имя подключаемого модуля.
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/includesharewaremodule.php
	* @author Bitrix
	*/
	public static function includeSharewareModule($moduleName)
	{
		if (isset(self::$arSharewareModules[$moduleName]))
			return self::$arSharewareModules[$moduleName];

		$moduleNameTmp = str_replace(".", "_", $moduleName);

		if (self::includeModule($moduleName))
		{
			if (defined($moduleNameTmp."_DEMO") && constant($moduleNameTmp."_DEMO") == "Y")
				self::$arSharewareModules[$moduleName] = self::MODULE_DEMO;
			else
				self::$arSharewareModules[$moduleName] = self::MODULE_INSTALLED;

			return self::$arSharewareModules[$moduleName];
		}

		if (defined($moduleNameTmp."_DEMO") && constant($moduleNameTmp."_DEMO") == "Y")
			return self::$arSharewareModules[$moduleName] = self::MODULE_DEMO_EXPIRED;

		return self::$arSharewareModules[$moduleName] = self::MODULE_NOT_FOUND;
	}

	public static function clearModuleCache($moduleName)
	{
		if (!is_string($moduleName) || $moduleName == "")
			throw new LoaderException("Empty module name");

		if($moduleName !== "main")
		{
			unset(static::$arLoadedModules[$moduleName]);
			unset(static::$arLoadedModulesHolders[$moduleName]);
		}

		if (isset(static::$arSharewareModules[$moduleName]))
			unset(static::$arSharewareModules[$moduleName]);
	}

	/**
	 * Returns document root
	 *
	 * @return string Document root
	 */
	
	/**
	* <p>Статический метод возвращает <i>document root</i>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/getdocumentroot.php
	* @author Bitrix
	*/
	public static function getDocumentRoot()
	{
		static $documentRoot = null;
		if ($documentRoot === null)
			$documentRoot = rtrim($_SERVER["DOCUMENT_ROOT"], "/\\");
		return $documentRoot;
	}

	public static function switchAutoLoad($value = true)
	{
		static::$isAutoLoadOn = $value;
	}

	/**
	 * Registers classes for auto loading.
	 * All the frequently used classes should be registered for auto loading (performance).
	 * It is not necessary to register rarely used classes. They can be found and loaded dynamically.
	 *
	 * @param string $moduleName Name of the module. Can be null if classes are not part of any module
	 * @param array $arClasses Array of classes with class names as keys and paths as values.
	 * @throws LoaderException
	 */
	
	/**
	* <p>Статический метод регистрирует классы для автозагрузки.</p> <p>Все часто используемые классы должны быть зарегистрированы для автозагрузки. Для редкоиспользуемых классов можно регистрацией пренебречь, они должны быть найдены и подключены динамически.</p>
	*
	*
	* @param string $moduleName  Имя модуля. Может быть <i>null</i> если классы не являются часть
	* какого-либо модуля.
	*
	* @param array $arClasses  Массив классов с именами классов как ключами и пути как значения
	* ключей.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/registerautoloadclasses.php
	* @author Bitrix
	*/
	public static function registerAutoLoadClasses($moduleName, array $arClasses)
	{
		if (empty($arClasses))
			return;

		if (($moduleName !== null) && empty($moduleName))
		{
			throw new LoaderException(sprintf("Module name '%s' is not correct", $moduleName));
		}

		if (!static::$isAutoLoadOn)
		{
			if (!is_null($moduleName) && !isset(static::$arLoadedModulesHolders[$moduleName]))
				throw new LoaderException(sprintf("Holder of module '%s' is not found", $moduleName));

			$documentRoot = static::getDocumentRoot();

			if (!is_null($moduleName))
			{
				foreach ($arClasses as $value)
				{
					if (file_exists($documentRoot."/".self::$arLoadedModulesHolders[$moduleName]."/modules/".$moduleName."/".$value))
						require_once($documentRoot."/".self::$arLoadedModulesHolders[$moduleName]."/modules/".$moduleName."/".$value);
				}
			}
			else
			{
				foreach ($arClasses as $value)
				{
					if (($includePath = self::getLocal($value, $documentRoot)) !== false)
						require_once($includePath);
				}
			}
		}
		else
		{
			foreach ($arClasses as $key => $value)
			{
				$class = ltrim($key, "\\");
				self::$arAutoLoadClasses[strtr($class, static::ALPHA_UPPER, static::ALPHA_LOWER)] = array(
					"module" => $moduleName,
					"file" => $value
				);
			}
		}
	}

	public static function isAutoLoadClassRegistered($className)
	{
		$className = trim(ltrim($className, "\\"));
		if ($className == '')
			return false;

		$className = strtr($className, static::ALPHA_UPPER, static::ALPHA_LOWER);

		return isset(self::$arAutoLoadClasses[$className]);
	}

	/**
	 * \Bitrix\Main\IO\File -> /main/lib/io/file.php
	 * \Bitrix\IBlock\Type -> /iblock/lib/type.php
	 * \Bitrix\IBlock\Section\Type -> /iblock/lib/section/type.php
	 * \QSoft\Catalog\Tools\File -> /qsoft.catalog/lib/tools/file.php
	 *
	 * @param $className
	 */
	
	/**
	* <p>Статический метод производит загрузку зарегистрированных для автозагрузки методов из следующих файлов:</p> <p><code>\Bitrix\Main\IO\File</code> - файл <b>/main/lib/io/file.php</b>,<br><code>\Bitrix\IBlock\Type</code> - файл <b>/iblock/lib/type.php</b>,<br><code>\Bitrix\IBlock\Section\Type</code> - файл <b>/iblock/lib/section/type.php</b>,<br><code>\QSoft\Catalog\Tools\File</code> - файл <b>/qsoft.catalog/lib/tools/file.php</b>.</p>
	*
	*
	* @param mixed $className  Имя класса для загрузки.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/autoload.php
	* @author Bitrix
	*/
	public static function autoLoad($className)
	{
		$file = ltrim($className, "\\");    // fix web env
		$file = strtr($file, static::ALPHA_UPPER, static::ALPHA_LOWER);

		static $documentRoot = null;
		if ($documentRoot === null)
			$documentRoot = static::getDocumentRoot();

		if (isset(self::$arAutoLoadClasses[$file]))
		{
			$pathInfo = self::$arAutoLoadClasses[$file];
			if ($pathInfo["module"] != "")
			{
				$m = $pathInfo["module"];
				$h = isset(self::$arLoadedModulesHolders[$m]) ? self::$arLoadedModulesHolders[$m] : 'bitrix';
				include_once($documentRoot."/".$h."/modules/".$m."/" .$pathInfo["file"]);
			}
			else
			{
				require_once($documentRoot.$pathInfo["file"]);
			}
			return;
		}

		if (preg_match("#[^\\\\/a-zA-Z0-9_]#", $file))
			return;

		if (substr($file, -5) == "table")
			$file = substr($file, 0, -5);

		$file = str_replace('\\', '/', $file);
		$arFile = explode("/", $file);

		if ($arFile[0] === "bitrix")
		{
			array_shift($arFile);

			if (empty($arFile))
				return;

			$module = array_shift($arFile);
			if ($module == null || empty($arFile))
				return;
		}
		else
		{
			$module1 = array_shift($arFile);
			$module2 = array_shift($arFile);
			if ($module1 == null || $module2 == null || empty($arFile))
				return;

			$module = $module1.".".$module2;
		}

		if (!isset(self::$arLoadedModulesHolders[$module]))
			return;

		$filePath = $documentRoot."/".self::$arLoadedModulesHolders[$module]."/modules/".$module."/lib/".implode("/", $arFile).".php";

		if (file_exists($filePath))
			require_once($filePath);
	}

	/**
	 * Checks if file exists in /local or /bitrix directories
	 *
	 * @param string $path File path relative to /local/ or /bitrix/
	 * @param string $root Server document root, default static::getDocumentRoot()
	 * @return string|bool Returns combined path or false if the file does not exist in both dirs
	 */
	
	/**
	* <p>Статический метод проверяет существование файла в <code>/local</code> или <code>/bitrix</code> директориях.</p>
	*
	*
	* @param string $path  Путь к файлу относительно <code>/local/</code> или <code>/bitrix/</code>
	*
	* @param string $root = null Document root сервера, по умолчанию: static::getDocumentRoot()
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/getlocal.php
	* @author Bitrix
	*/
	public static function getLocal($path, $root = null)
	{
		if ($root === null)
			$root = static::getDocumentRoot();

		if (file_exists($root."/local/".$path))
			return $root."/local/".$path;
		elseif (file_exists($root."/bitrix/".$path))
			return $root."/bitrix/".$path;
		else
			return false;
	}

	/**
	 * Checks if file exists in personal directory.
	 * If $_SERVER["BX_PERSONAL_ROOT"] is not set than personal directory is equal to /bitrix/
	 *
	 * @param string $path File path relative to personal directory
	 * @return string|bool Returns combined path or false if the file does not exist
	 */
	
	/**
	* <p>Статический метод проверяет существование файла в персональной директории.</p> <p>Если <code>$_SERVER["BX_PERSONAL_ROOT"]</code> не установлена как персональная директория, то переменная равна <code>/bitrix/</code>.</p>
	*
	*
	* @param string $path  Путь к файлу относительно персональной директории.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/loader/getpersonal.php
	* @author Bitrix
	*/
	public static function getPersonal($path)
	{
		$root = static::getDocumentRoot();
		$personal = isset($_SERVER["BX_PERSONAL_ROOT"]) ? $_SERVER["BX_PERSONAL_ROOT"] : "";

		if (!empty($personal) && file_exists($root.$personal."/".$path))
			return $root.$personal."/".$path;

		return self::getLocal($path, $root);
	}
}

class LoaderException extends \Exception
{
	static public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

if (!function_exists("__autoload"))
{
	if (function_exists('spl_autoload_register'))
	{
		\spl_autoload_register('\Bitrix\Main\Loader::autoLoad');
	}
	else
	{
		function __autoload($className)
		{
			Loader::autoLoad($className);
		}
	}

	Loader::switchAutoLoad(true);
}
else
{
	Loader::switchAutoLoad(false);
}
