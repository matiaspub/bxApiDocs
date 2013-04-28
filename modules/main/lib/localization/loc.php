<?php
namespace Bitrix\Main\Localization;

use \Bitrix\Main;
use \Bitrix\Main\IO\Path;
use \Bitrix\Main\Context;

final class Loc
{
	private static $messages = array();
	private static $customMessages = null;
	private static $includedFiles = array();
	private static $lazyLoadFiles = array();

	/**
	 * Returns translation by message code.
	 * Loc::loadMessages(__FILE__) should be called first once per php file
	 *
	 * @param string $code
	 * @param array $replace e.g. array("#NUM#"=>5)
	 * @param string $language
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @return string
	 */
	public static function getMessage($code, $replace = null, $language = null)
	{
		if($code == '')
			throw new Main\ArgumentNullException("code");

		if($language === null)
			$language = \Bitrix\Main\Context::getCurrent()->getLanguage();

		if(!isset(self::$messages[$language][$code]))
			self::loadLazy($code, $language);

		$s = self::$messages[$language][$code];

		if($replace !== null && is_array($replace))
			foreach($replace as $search => $repl)
				$s = str_replace($search, $repl, $s);

		return $s;
	}

	/**
	 * Loads language messages for specified file in a lazy way
	 *
	 * @param string $file
	 */
	public static function loadMessages($file)
	{
		self::$lazyLoadFiles[$file] = $file;
	}

	/**
	 * Loads language messages for specified file
	 *
	 * @param string $file
	 * @param string $language
	 * @return array
	 */
	public static function loadLanguageFile($file, $language = null)
	{
		if($language === null)
			$language = \Bitrix\Main\Context::getCurrent()->getLanguage();

		if(!isset(self::$messages[$language]))
			self::$messages[$language] = array();

		//first time call only for lang
		if(self::$customMessages === null)
			self::$customMessages = self::loadCustomMessages($language);

		$file = Path::normalize($file);

		static $dirCache = array();

		//let's find language folder
		$langDir = $fileName = "";
		$filePath = $file;
		while(($slashPos = strrpos($filePath, "/")) !== false)
		{
			$filePath = substr($filePath, 0, $slashPos);
			if(!isset($dirCache[$filePath]))
				$dirCache[$filePath] = $isDir = is_dir($filePath."/lang");
			else
				$isDir = $dirCache[$filePath];
			if($isDir)
			{
				$langDir = $filePath."/lang";
				$fileName = substr($file, $slashPos);
				break;
			}
		}

		$mess = array();
		if($langDir <> "")
		{
			//load messages for default lang first
			$defaultLang = self::getDefaultLang($language);
			if($defaultLang <> $language)
			{
				$langFile = $langDir."/".$defaultLang.$fileName;
				if(file_exists($langFile))
					$mess = self::includeFile($langFile);
			}

			//then load messages for specified lang
			$langFile = $langDir."/".$language.$fileName;
			if(file_exists($langFile))
				$mess = array_merge($mess, self::includeFile($langFile));

			foreach($mess as $key => $val)
				self::$messages[$language][$key] = $val;
		}

		return $mess;
	}

	private static function loadLazy($code, $language)
	{
		if (PHP_VERSION_ID < 50306)
			$trace = debug_backtrace(false);
		elseif (PHP_VERSION_ID < 50400)
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		else
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
		//$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

		$file = null;
		for($i = 3; $i >= 1; $i--)
		{
			if(stripos($trace[$i]["function"], "GetMessage") === 0)
			{
				$file = $trace[$i]["file"];
				break;
			}
		}

		if($file !== null && isset(self::$lazyLoadFiles[$file]))
		{
			self::loadLanguageFile($file, $language);
			unset(self::$lazyLoadFiles[$file]);
		}
		else
		{
			foreach(self::$lazyLoadFiles as $file)
			{
				self::loadLanguageFile($file, $language);
				unset(self::$lazyLoadFiles[$file]);
				if(isset(self::$messages[$language][$code]))
					break;
			}
		}
	}

	/**
	 * Read messages from user defined lang file
	 */
	private static function loadCustomMessages($lang)
	{
		$customMess = array();
		$documentRoot = \Bitrix\Main\Application::getDocumentRoot();
		if(($fname = Main\Loader::getLocal("php_interface/user_lang/".$lang."/lang.php", $documentRoot)) !== false)
		{
			$mess = self::includeFile($fname);

			// typical call is Loc::loadMessages(__FILE__)
			// __FILE__ can differ from path used in the user file
			foreach($mess as $key => $val)
				$customMess[str_replace("\\", "/", realpath($documentRoot.$key))] = $val;
		}
		return $customMess;
	}

	/**
	 * Read messages from lang file
	 */
	private static function includeFile($path)
	{
		self::$includedFiles[$path] = $path;

		//the name $MESS is predefined in language files
		$MESS = array();
		include($path);

		//redefine messages from user lang file
		if(!empty(self::$customMessages))
		{
			$path = str_replace("\\", "/", realpath($path));
			if(is_array(self::$customMessages[$path]))
				foreach(self::$customMessages[$path] as $key => $val)
					$MESS[$key] = $val;
		}

		return $MESS;
	}

	/**
	 * Returns default language for specified language. Defualt language is used when translation is not found.
	 *
	 * @param string $lang
	 * @return string
	 */
	public static function getDefaultLang($lang)
	{
		static $subst = array('ua'=>'ru', 'kz'=>'ru', 'ru'=>'ru', 'de'=>'de');
		if(isset($subst[$lang]))
			return $subst[$lang];
		return 'en';
	}

	public static function date($date, Context\Culture $culture = null)
	{
		if ($culture == null)
			$culture = \Bitrix\Main\Context::getCurrent()->getCulture();

		return $date;
	}

	public static function datetime($datetime, Context\Culture $culture = null)
	{
		if ($culture == null)
			$culture = \Bitrix\Main\Context::getCurrent()->getCulture();

		return $datetime;
	}

	public static function money($money, Context\Culture $culture = null)
	{
		if ($culture == null)
			$culture = \Bitrix\Main\Context::getCurrent()->getCulture();

		return $money;
	}
}
