<?php
namespace Bitrix\Main\Data;

class Cache
{
	/**
	 * @var ICacheEngine
	 */
	private $cacheEngine;

	private $clearCache = false;

	private $content;
	private $vars;
	private $TTL;
	private $uniqueString;
	private $baseDir;
	private $initDir;
	private $filename;
	private $isStarted = false;

	/**
	 * @return Cache
	 */
	public static function createInstance()
	{
		$cacheEngine = null;

		// Events can't be used here because events use cache

		$cacheType = "files";
		$v = \Bitrix\Main\Config\Configuration::getValue("cache");
		if ($v != null && isset($v["type"]) && !empty($v["type"]))
			$cacheType = $v["type"];

		if (is_array($cacheType))
		{
			if (isset($cacheType["class_name"]))
			{
				if (!isset($cacheType["extension"]) || extension_loaded($cacheType["extension"]))
				{
					if (isset($cacheType["required_file"]) && ($requiredFile = \Bitrix\Main\Loader::getLocal($cacheType["required_file"])) !== false)
						require_once($requiredFile);

					$className = $cacheType["class_name"];
					if (class_exists($className))
						$cacheEngine = new $className();
				}
			}
		}
		else
		{
			switch ($cacheType)
			{
				case "memcache":
					if (extension_loaded('memcache'))
						$cacheEngine = new CacheEngineMemcache();
					break;
				case "eaccelerator":
					if (extension_loaded('eaccelerator'))
						$cacheEngine = new CacheEngineEAccelerator();
					break;
				case "apc":
					if (extension_loaded('apc'))
						$cacheEngine = new CacheEngineApc();
					break;
				case "files":
					$cacheEngine = new CacheEngineFiles();
					break;
				default:
					break;
			}
		}

		if ($cacheEngine == null)
			throw new \Bitrix\Main\Config\ConfigurationException("Cache engine is not found");

		if (!$cacheEngine->isAvailable())
			throw new \Bitrix\Main\SystemException("Cache engine is not available");

		/*
		$v = \Bitrix\Main\Config\Configuration::getValue("cache");
		if ($v != null && isset($v["type"]))
		{
			$cacheType = $v["type"];
			if (is_array($cacheType))
			{
				if (isset($cacheType["class_name"]))
				{
					if (!isset($cacheType["extension"]) || extension_loaded($cacheType["extension"]))
					{
						if (isset($cacheType["required_file"]) && ($requiredFile = \Bitrix\Main\Loader::getLocal($cacheType["required_file"])))
							require_once($requiredFile);

						$className = $cacheType["class_name"];
						if (class_exists($className))
							$cacheEngine = new $className();
					}
				}
			}
			else
			{
				switch ($cacheType)
				{
					case "memcache":
						if (extension_loaded('memcache'))
							$cacheEngine = new CacheEngineMemcache();
						break;
					case "eaccelerator":
						if (extension_loaded('eaccelerator'))
							$cacheEngine = new CacheEngineEAccelerator();
						break;
					case "apc":
						if (extension_loaded('apc'))
							$cacheEngine = new CacheEngineApc();
						break;
					default:
						break;
				}
			}

			if (($cacheEngine != null) && !$cacheEngine->isAvailable())
				$cacheEngine = null;
		}

		if ($cacheEngine == null)
			$cacheEngine = new CacheEngineFiles();
		*/

		return new self($cacheEngine);
	}

	public function __construct(ICacheEngine $cacheEngine)
	{
		$this->cacheEngine = $cacheEngine;
	}

	public function setClearCache($value = false)
	{
		$this->clearCache = $value;
	}

	public function setClearCacheSession($value = false)
	{
		$_SESSION["SESS_CLEAR_CACHE"] = $value;
		$this->setClearCache($value);
	}

	private function getClearCache()
	{
		if (!$this->clearCache && isset($_SESSION["SESS_CLEAR_CACHE"]) && $_SESSION["SESS_CLEAR_CACHE"])
			$this->clearCache = true;

		return $this->clearCache;
	}

	private function getPath($uniqueString)
	{
		$un = md5($uniqueString);
		return substr($un, 0, 2)."/".$un.".php";
	}

	public function clean($uniqueString, $initDir = false, $baseDir = "cache")
	{
		$personalRoot = \Bitrix\Main\Application::getPersonalRoot();
		$baseDir = \Bitrix\Main\IO\Path::combine($personalRoot, $baseDir);
		$filename = $this->getPath($uniqueString);
		return $this->cacheEngine->clean($baseDir, $initDir, $filename);
	}

	public function cleanDir($initDir = false, $baseDir = "cache")
	{
		$personalRoot = \Bitrix\Main\Application::getPersonalRoot();
		$baseDir = \Bitrix\Main\IO\Path::combine($personalRoot, $baseDir);
		return $this->cacheEngine->clean($baseDir, $initDir);
	}

	public function initCache($TTL, $uniqueString, $initDir = false, $baseDir = "cache")
	{
		if ($initDir === false)
		{
			$request = \Bitrix\Main\Context::getCurrent()->getRequest();
			$initDir = $request->getRequestedPageDirectory();
		}

		$personalRoot = \Bitrix\Main\Application::getPersonalRoot();
		$this->baseDir = \Bitrix\Main\IO\Path::combine($personalRoot, $baseDir);
		$this->initDir = $initDir;
		$this->filename = $this->getPath($uniqueString);
		$this->TTL = $TTL;
		$this->uniqueString = $uniqueString;
		$this->vars = false;

		if ($TTL <= 0)
			return false;

		if ($this->getClearCache())
			return false;

		$arAllVars = array("CONTENT" => "", "VARS" => "");
		if (!$this->cacheEngine->read($arAllVars, $this->baseDir, $this->initDir, $this->filename, $this->TTL))
			return false;

		$this->content = $arAllVars["CONTENT"];
		$this->vars = $arAllVars["VARS"];

		return true;
	}

	public function output()
	{
		echo $this->content;
	}

	public function getVars()
	{
		return $this->vars;
	}

	public function startDataCache($TTL = false, $uniqueString = false, $initDir = false, $vars = array(), $baseDir = "cache")
	{
		$narg = func_num_args();
		if($narg<=0)
			$TTL = $this->TTL;
		if($narg<=1)
			$uniqueString = $this->uniqueString;
		if($narg<=2)
			$initDir = $this->initDir;
		if($narg<=3)
			$vars = $this->vars;

		if ($this->initCache($TTL, $uniqueString, $initDir, $baseDir))
		{
			$this->output();
			return false;
		}

		if ($TTL <= 0)
			return true;

		ob_start();
		$this->vars = $vars;
		$this->isStarted = true;

		return true;
	}

	public function abortDataCache()
	{
		if (!$this->isStarted)
			return;

		$this->isStarted = false;
		ob_end_flush();
	}

	public function endDataCache($vars=false)
	{
		if (!$this->isStarted)
			return;

		$this->isStarted = false;

		$arAllVars = array(
			"CONTENT" => ob_get_contents(),
			"VARS" => ($vars!==false ? $vars : $this->vars),
		);

		$this->cacheEngine->write($arAllVars, $this->baseDir, $this->initDir, $this->filename, $this->TTL);

		if(strlen(ob_get_contents()) > 0)
			ob_end_flush();
		else
			ob_end_clean();
	}

	public function isCacheExpired($path)
	{
		return $this->cacheEngine->isCacheExpired($path);
	}
}
