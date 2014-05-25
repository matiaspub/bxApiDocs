<?php
namespace Bitrix\Main\Localization;

use Bitrix\Main;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Context;

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
	 * @return string
	 */
	public static function getMessage($code, $replace = null, $language = null)
	{
		static $currentLang = null;

		if($language === null)
		{
			if($currentLang === null)
			{
				$language = $currentLang = self::getCurrentLang();
			}
			else
			{
				$language = $currentLang;
			}
		}

		if(!isset(self::$messages[$language][$code]))
		{
			self::loadLazy($code, $language);
		}

		$s = self::$messages[$language][$code];

		if($replace !== null && is_array($replace))
		{
			foreach($replace as $search => $repl)
			{
				$s = str_replace($search, $repl, $s);
			}
		}

		return $s;
	}

	/**
	 * Loads language messages for specified file in a lazy way
	 *
	 * @param string $file
	 */
	public static function loadMessages($file)
	{
		if(($realPath = realpath($file)) !== false)
		{
			$file = $realPath;
		}
		$file = Path::normalize($file);

		self::$lazyLoadFiles[$file] = $file;
	}

	private static function getCurrentLang()
	{
		$context = Context::getCurrent();
		if($context !== null)
			return $context->getLanguage();
		return 'en';
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
			$language = self::getCurrentLang();

		if(!isset(self::$messages[$language]))
			self::$messages[$language] = array();

		//first time call only for lang
		if(self::$customMessages === null)
			self::$customMessages = self::loadCustomMessages($language);

		$path = Path::getDirectory($file);

		static $langDirCache = array();

		if(isset($langDirCache[$path]))
		{
			$langDir = $langDirCache[$path];
			$fileName = substr($file, (strlen($langDir)-5));
		}
		else
		{
			//let's find language folder
			$langDir = $fileName = "";
			$filePath = $file;
			while(($slashPos = strrpos($filePath, "/")) !== false)
			{
				$filePath = substr($filePath, 0, $slashPos);
				if(is_dir($filePath."/lang"))
				{
					$langDir = $filePath."/lang";
					$fileName = substr($file, $slashPos);
					$langDirCache[$path] = $langDir;
					break;
				}
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
				{
					$mess = self::includeFile($langFile);
				}
			}

			//then load messages for specified lang
			$langFile = $langDir."/".$language.$fileName;
			if(file_exists($langFile))
			{
				$mess = array_merge($mess, self::includeFile($langFile));
			}

			foreach($mess as $key => $val)
			{
				self::$messages[$language][$key] = $val;
			}
		}

		return $mess;
	}

	private static function loadLazy($code, $language)
	{
		if($code == '')
		{
			return;
		}

		$trace = Main\Diag\Helper::getBackTrace(4, DEBUG_BACKTRACE_IGNORE_ARGS);

		$currentFile = null;
		for($i = 3; $i >= 1; $i--)
		{
			if(stripos($trace[$i]["function"], "GetMessage") === 0)
			{
				$currentFile = Path::normalize($trace[$i]["file"]);
				break;
			}
		}

		if($currentFile !== null && isset(self::$lazyLoadFiles[$currentFile]))
		{
			//in most cases we know the file containing the "code" - load it directly
			self::loadLanguageFile($currentFile, $language);
			unset(self::$lazyLoadFiles[$currentFile]);
		}

		if(!isset(self::$messages[$language][$code]))
		{
			//we still don't know which file contains the "code" - go through the files in the reverse order
			$unset = array();
			if(($file = end(self::$lazyLoadFiles)) !== false)
			{
				do
				{
					self::loadLanguageFile($file, $language);
					$unset[] = $file;
					if(isset(self::$messages[$language][$code]))
					{
						if(defined("BX_MESS_LOG") && $currentFile !== null)
						{
							file_put_contents(BX_MESS_LOG, 'CTranslateUtils::CopyMessage("'.$code.'", "'.$file.'", "'.$currentFile.'");'."\n", FILE_APPEND);
						}
						break;
					}
				}
				while(($file = prev(self::$lazyLoadFiles)) !== false);
			}
			foreach($unset as $file)
			{
				unset(self::$lazyLoadFiles[$file]);
			}
		}
	}

	/**
	 * Read messages from user defined lang file
	 */
	private static function loadCustomMessages($lang)
	{
		$customMess = array();
		$documentRoot = Main\Application::getDocumentRoot();
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
		static $subst = array('ua'=>'ru', 'kz'=>'ru', 'ru'=>'ru');
		if(isset($subst[$lang]))
			return $subst[$lang];
		return 'en';
	}

	public static function getIncludedFiles()
	{
		return self::$includedFiles;
	}
}
