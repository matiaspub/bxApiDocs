<?php
namespace Bitrix\Main;

final class Loader
{
	const SAFE_MODE = false;

	const BITRIX_HOLDER = "bitrix";
	const LOCAL_HOLDER = "local";

	private static $safeModeModules = array("main", "fileman");

	private static $arLoadedModules = array("main" => true);
	private static $arLoadedModulesHolders = array("main" => self::BITRIX_HOLDER);

	private static $arAutoLoadClasses = array();

	public static function includeModule($moduleName)
	{
		if (!is_string($moduleName) || $moduleName == "")
			throw new LoaderException("Empty module name");
		if (preg_match("#[^a-zA-Z0-9._]#", $moduleName))
			throw new LoaderException(sprintf("Module name '%s' is not correct", $moduleName));

		$moduleName = strtolower($moduleName);

		if (self::SAFE_MODE)
		{
			if (!in_array($moduleName, self::$safeModeModules))
				return null;
		}

		if (array_key_exists($moduleName, self::$arLoadedModules))
			return self::$arLoadedModules[$moduleName];

		$arInstalledModules = ModuleManager::getInstalledModules();
		if (!array_key_exists($moduleName, $arInstalledModules))
			return self::$arLoadedModules[$moduleName] = null;

		static $documentRoot = null;
		if ($documentRoot === null)
		{
			$documentRoot = Application::getDocumentRoot();
			while (substr($documentRoot, -1) == "/")
				$documentRoot = substr($documentRoot, 0, -1);
		}

		$moduleHolder = self::LOCAL_HOLDER;
		$pathToModule = $documentRoot."/".$moduleHolder."/modules/".$moduleName;
		$pathToInclude = $pathToModule."/include_module.php";
		if (!file_exists($pathToInclude))
		{
			$pathToInclude = $pathToModule."/include.php";
			if (!file_exists($pathToInclude))
			{
				$moduleHolder = self::BITRIX_HOLDER;
				$pathToModule = $documentRoot."/".$moduleHolder."/modules/".$moduleName;
				$pathToInclude = $pathToModule."/include_module.php";
				if (!file_exists($pathToInclude))
				{
					$pathToInclude = $pathToModule."/include.php";
					if (!file_exists($pathToInclude))
						return self::$arLoadedModules[$moduleName] = null;
				}
			}
		}

		$res = self::includeModuleInternal($pathToInclude);
		if ($res === false)
			return self::$arLoadedModules[$moduleName] = null;

		self::$arLoadedModulesHolders[$moduleName] = $moduleHolder;

		if (strpos($moduleName, ".") !== false)
		{
			$moduleNameTmp = str_replace(".", "_", $moduleName);
			$className = "\\".$moduleNameTmp."\\".$moduleNameTmp;
		}
		else
		{
			$className = "\\Bitrix\\".$moduleName;
		}

		if (class_exists($className))
			return self::$arLoadedModules[$moduleName] = new $className();

		return self::$arLoadedModules[$moduleName] = true;
	}

	private static function includeModuleInternal($path)
	{
		global $DB, $MESS;
		return include_once($path);
	}

	public static function registerAutoLoadClasses($moduleName, array $arClasses)
	{
		if (!is_array($arClasses))
			throw new LoaderException("Classes are not specified");

		if (empty($arClasses))
			return;

		if (!is_string($moduleName) || $moduleName == "" || preg_match("#[^a-zA-Z0-9._]#", $moduleName))
			throw new LoaderException(sprintf("Module name '%s' is not correct", $moduleName));

		foreach ($arClasses as $key => $value)
		{
			self::$arAutoLoadClasses[strtolower($key)] = array(
				"module" => $moduleName,
				"file" => $value
			);
		}
	}

	/**
	 * \Bitrix\Main\IO\File -> /main/lib/io/file.php
	 * \Bitrix\IBlock\Type -> /iblock/lib/type.php
	 * \Bitrix\IBlock\Section\Type -> /iblock/lib/section/type.php
	 * \QSoft\Catalog\Tools\File -> /qsoft.catalog/lib/tools/file.php
	 *
	 * @param $className
	 */
	public static function autoLoad($className)
	{
		if (!is_string($className))
			return;

		$className = trim($className, '\\/');
		if (empty($className))
			return;

		if (preg_match("#[^\\\\/a-zA-Z0-9_]#", $className))
			return;

		$file = strtolower($className);

		if(substr($file, -5) == "table")
			$file = substr($file, 0, -5);

		static $documentRoot = null;
		if ($documentRoot === null)
		{
			$documentRoot = $_SERVER["DOCUMENT_ROOT"];
			while (substr($documentRoot, -1) == "/")
				$documentRoot = substr($documentRoot, 0, -1);
		}

		if (array_key_exists($file, self::$arAutoLoadClasses))
		{
			$pathInfo = self::$arAutoLoadClasses[$file];
			if ($pathInfo["module"] != "")
			{
				$m = $pathInfo["module"];
				if (file_exists($documentRoot."/".self::$arLoadedModulesHolders[$m]."/modules/".$m."/" .$pathInfo["file"]))
					require_once($documentRoot."/".self::$arLoadedModulesHolders[$m]."/modules/".$m."/" .$pathInfo["file"]);
			}
			else
			{
				if (($includePath = self::getLocal($pathInfo["file"], $documentRoot)) !== false)
					require_once($includePath);
			}
			return;
		}

		$file = str_replace('\\', '/', $file);
		$arFile = explode("/", $file);
		$module = "";

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

		if (!array_key_exists($module, self::$arLoadedModules) || self::$arLoadedModules[$module] == null)
			return;

		$filePath = $documentRoot."/".self::$arLoadedModulesHolders[$module]."/modules/".$module."/lib/".implode("/", $arFile).".php";
		if (file_exists($filePath))
			require_once($filePath);
	}

	/*private static function AutoLoadRecursive($basePath, $path, $filePath, &$fl)
	{
		if (($handle = opendir($basePath.$path)) && $fl)
		{
			while ((($dir = readdir($handle)) !== false) && $fl)
			{
				if ($dir == "." || $dir == ".." || !is_dir($basePath.$path."/".$dir))
					continue;

				$path2 = $path.'/'.$dir;
				if (file_exists($basePath.$path2.$filePath))
				{
					$fl = false;
					require_once($basePath.$path2.$filePath);
					break;
				}
				self::autoLoadRecursive($basePath, $path2, $filePath, $fl);
			}
			closedir($handle);
		}
	}*/

	/**
	 * Checks if file exists in /local or /bitrix directories
	 *
	 * @param string $path File path relative to /local/ or /bitrix/
	 * @param string $root Server document root, default \Bitrix\Main\Application::getDocumentRoot()
	 * @return string|bool Returns combined path or false if the file does not exist in both dirs
	 */
	public static function getLocal($path, $root = null)
	{
		if ($root === null)
			$root = Application::getDocumentRoot();

		if (file_exists($root."/local/".$path))
			return $root."/local/".$path;
		elseif (file_exists($root."/bitrix/".$path))
			return $root."/bitrix/".$path;
		else
			return false;
	}

	public static function getPersonal($path)
	{
		/*$context = Application::getInstance()->getContext();
		if ($context == null)
			throw new SystemException("Context is not initialized");

		$server = $context->getServer();
		if ($server == null)
			throw new SystemException("Server is not initialized");

		$root = $server->getDocumentRoot();
		$personal = $server->get("BX_PERSONAL_ROOT");
		*/
		$root = Application::getDocumentRoot();
		$personal = isset($_SERVER["BX_PERSONAL_ROOT"]) ? $_SERVER["BX_PERSONAL_ROOT"] : "";

		if (!empty($personal) && file_exists($root.$personal."/".$path))
			return $root.$personal."/".$path;

		return self::getLocal($path, $root);
	}
}

class LoaderException
	extends \Exception
{
	static public function __construct($message = "", $code = 0, \Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}
}

\spl_autoload_register('\Bitrix\Main\Loader::autoLoad');
