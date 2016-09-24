<?php
namespace Bitrix\Main\Localization;

use Bitrix\Main;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Context;

final class Loc
{
	private static $currentLang = null;
	private static $messages = array();
	private static $customMessages = array();
	private static $userMessages = null;
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
	
	/**
	* <p>Статический метод возвращает по коду соответствующее сообщение на текущем языке. Массивы соответствий кодов и сообщений задаются в языковых файлах. Перед использованием этой функции необходимо подключить соответствующий языковой файл ( <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/localization/loc/loadmessages.php">Loc::loadMessages(__FILE__)</a>).</p> <p>Аналог в старом ядре: <a href="https://forums.bitrix24.ru/forum17/topic11067/" >GetMessage</a></p>
	*
	*
	* @param string $code  Код сообщения. Код должен быть уникальным в рамках всего
	* продукта.
	*
	* @param array $replace = null Массив пар "шаблон" =&gt; "замена". Позволяет организовать замену по
	* шаблону. Например, array("#NUM#"=&gt;5).
	*
	* @param string $language = null ID языка.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/localization/loc/getmessage.php
	* @author Bitrix
	*/
	public static function getMessage($code, $replace = null, $language = null)
	{
		if($language === null)
		{
			//function call optimization
			if(static::$currentLang === null)
				self::getCurrentLang();

			$language = static::$currentLang;
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
	
	/**
	* <p>Статический vетод загружает языковые сообщения для указанного файла, ищет первую папку <code>/lang</code> в пути наверх относительно переданного файла.</p> <p>Аналог методов <a href="http://dev.1c-bitrix.ru/api_help/main/functions/localization/includemodulelangfile.php" >IncludeModuleLangFile</a> и <a href="http://dev.1c-bitrix.ru/api_help/main/functions/localization/includetemplatelangfile.php" >IncludeTemplateLangFile</a> старого ядра.</p>
	*
	*
	* @param string $file  Файл.
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/localization/loc/loadmessages.php
	* @author Bitrix
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
		if(self::$currentLang === null)
		{
			$context = Context::getCurrent();
			if($context !== null)
			{
				self::$currentLang = $context->getLanguage();
			}
			else
			{
				self::$currentLang = 'en';
			}
		}
		return self::$currentLang;
	}

	public static function setCurrentLang($language)
	{
		static::$currentLang = $language;
	}

	private static function includeLangFiles($file, $language)
	{
		static $langDirCache = array();

		$path = Path::getDirectory($file);

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
				$langPath = $filePath."/lang";
				if(is_dir($langPath))
				{
					$langDir = $langPath;
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
		}

		return $mess;
	}

	/**
	 * Loads language messages for specified file
	 *
	 * @param string $file
	 * @param string $language
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает языковые сообщения для указанного файла.</p>
	*
	*
	* @param string $file  Файл
	*
	* @param string $language = null ID языка
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/localization/loc/loadlanguagefile.php
	* @author Bitrix
	*/
	public static function loadLanguageFile($file, $language = null, $normalize = true)
	{
		if($language === null)
		{
			$language = self::getCurrentLang();
		}

		if($normalize)
		{
			$file = Path::normalize($file);
		}

		if(!isset(self::$messages[$language]))
		{
			self::$messages[$language] = array();
		}

		//first time call only for lang
		if(self::$userMessages === null)
		{
			self::$userMessages = self::loadUserMessages($language);
		}

		//let's find language folder and include lang files
		$mess = self::includeLangFiles($file, $language);

		foreach($mess as $key => $val)
		{
			if(isset(self::$customMessages[$language][$key]))
			{
				self::$messages[$language][$key] = $mess[$key] = self::$customMessages[$language][$key];
			}
			else
			{
				self::$messages[$language][$key] = $val;
			}
		}

		return $mess;
	}

	/**
	 * Loads custom messages from the file to overwrite messages by their IDs.
	 *
	 * @param $file
	 * @param null $language
	 */
	
	/**
	* <p>Статический метод загружает изменённые фразы из файла для перезаписи сообщений по их ID.</p>
	*
	*
	* @param mixed $file  Файл с фразами
	*
	* @param null $language = null ID языка
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/localization/loc/loadcustommessages.php
	* @author Bitrix
	*/
	public static function loadCustomMessages($file, $language = null)
	{
		if($language === null)
		{
			$language = self::getCurrentLang();
		}

		if(!isset(self::$customMessages[$language]))
		{
			self::$customMessages[$language] = array();
		}

		//let's find language folder and include lang files
		$mess = self::includeLangFiles(Path::normalize($file), $language);

		foreach($mess as $key => $val)
		{
			self::$customMessages[$language][$key] = $val;
		}
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
			self::loadLanguageFile($currentFile, $language, false);
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
					self::loadLanguageFile($file, $language, false);
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

		if(!isset(self::$messages[$language][$code]) && defined("BX_MESS_LOG"))
		{
			file_put_contents(BX_MESS_LOG, $code.": not found for ".$currentFile."\n", FILE_APPEND);
		}
	}

	/**
	 * Reads messages from user defined lang file
	 *
	 * @param string $lang
	 * @return array
	 */
	private static function loadUserMessages($lang)
	{
		$userMess = array();
		$documentRoot = Main\Application::getDocumentRoot();
		if(($fname = Main\Loader::getLocal("php_interface/user_lang/".$lang."/lang.php", $documentRoot)) !== false)
		{
			$mess = self::includeFile($fname);

			// typical call is Loc::loadMessages(__FILE__)
			// __FILE__ can differ from path used in the user file
			foreach($mess as $key => $val)
				$userMess[str_replace("\\", "/", realpath($documentRoot.$key))] = $val;
		}
		return $userMess;
	}

	/**
	 * Reads messages from lang file.
	 *
	 * @param string $path
	 * @return array
	 */
	private static function includeFile($path)
	{
		self::$includedFiles[$path] = $path;

		//the name $MESS is predefined in language files
		$MESS = array();
		include($path);

		//redefine messages from user lang file
		if(!empty(self::$userMessages))
		{
			$path = str_replace("\\", "/", realpath($path));
			if(is_array(self::$userMessages[$path]))
				foreach(self::$userMessages[$path] as $key => $val)
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
	
	/**
	* <p>Статический метод возвращает язык-замену по умолчанию для других языков. Сообщение на языке по умолчанию используется если сообщение на выбранном языке не найдено.</p>
	*
	*
	* @param string $lang  Сообщение на выбранном языке.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/localization/loc/getdefaultlang.php
	* @author Bitrix
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
