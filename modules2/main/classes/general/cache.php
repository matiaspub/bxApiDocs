<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

interface ICacheBackend
{
	function IsAvailable();
	public static function clean($basedir, $initdir = false, $filename = false);
	public static function read(&$arAllVars, $basedir, $initdir, $filename, $TTL);
	public static function write($arAllVars, $basedir, $initdir, $filename, $TTL);
	public static function IsCacheExpired($path);
}

class CPHPCache
{
	/** @var ICacheBackend */
	var $_cache;
	var $content;
	var $vars;
	var $TTL;
	var $uniq_str;
	var $basedir;
	var $initdir;
	var $filename;
	var $bStarted = false;
	var $bInit = "NO";

	function __construct()
	{
		$this->CPHPCache();
	}

	public static function CPHPCache()
	{
		$this->_cache = $this->_new_cache_object();
	}

	function _new_cache_object()
	{
		static $cache_type = false;
		if($cache_type === false)
		{
			$isOK = false;
			if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php"))
			{
				include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php");
				if(defined("BX_MEMCACHE_CLUSTER") && extension_loaded('memcache'))
				{
					include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/classes/general/memcache_cache.php");
					$obCache = new CPHPCacheMemcacheCluster;
					if($obCache->IsAvailable())
					{
						$cache_type = "CPHPCacheMemcacheCluster";
						$isOK = true;
					}
				}
			}
			//There is no cluster configuration
			if($cache_type === false)
			{
				if(defined("BX_CACHE_TYPE"))
				{
					switch(BX_CACHE_TYPE)
					{
						case "memcache":
						case "CPHPCacheMemcache":
							if(extension_loaded('memcache') && defined("BX_MEMCACHE_HOST"))
							{
								include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_memcache.php");
								$cache_type = "CPHPCacheMemcache";
							}
							break;
						case "eaccelerator":
						case "CPHPCacheEAccelerator":
							if(extension_loaded('eaccelerator'))
							{
								include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_eaccelerator.php");
								$cache_type = "CPHPCacheEAccelerator";
							}
							break;
						case "apc":
						case "CPHPCacheAPC":
							if(extension_loaded('apc'))
							{
								include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_apc.php");
								$cache_type = "CPHPCacheAPC";
							}
							break;
						case "xcache":
						case "CPHPCacheXCache":
							if(extension_loaded('xcache'))
							{
								include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_xcache.php");
								$cache_type = "CPHPCacheXCache";
							}
							break;
						default:
							if(defined("BX_CACHE_CLASS_FILE") && file_exists(BX_CACHE_CLASS_FILE))
							{
								include_once(BX_CACHE_CLASS_FILE);
								$cache_type = BX_CACHE_TYPE;
							}
							break;
					}
				}
				else
				{
					include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_files.php");
					$cache_type = "CPHPCacheFiles";
				}
			}

			//Probe the cache backend class
			if(!$isOK && class_exists($cache_type))
			{
				$obCache = new $cache_type;
				if ($obCache instanceof ICacheBackend)
					$isOK = $obCache->IsAvailable();
			}

			//Bulletproof files cache
			if(!$isOK)
			{
				include_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/cache_files.php");
				$cache_type = "CPHPCacheFiles";
			}
		}

		$cache = new $cache_type;
		return $cache;
	}

	public static function GetPath($uniq_str)
	{
		$un = md5($uniq_str);
		return substr($un, 0, 2)."/".$un.".php";
	}

	public static function Clean($uniq_str, $initdir = false, $basedir = "cache")
	{
		if(is_object($this) && is_object($this->_cache))
		{
			$basedir = BX_PERSONAL_ROOT."/".$basedir."/";
			$filename = CPHPCache::GetPath($uniq_str);
			return $this->_cache->clean($basedir, $initdir, "/".$filename);
		}
		else
		{
			$obCache = new CPHPCache();
			return $obCache->Clean($uniq_str, $initdir, $basedir);
		}
	}

	public static function CleanDir($initdir = false, $basedir = "cache")
	{
		$basedir = BX_PERSONAL_ROOT."/".$basedir."/";
		return $this->_cache->clean($basedir, $initdir);
	}

	
	/**
	 * <p>Инициализирует ряд свойств объекта класса CPHPCache. Если файл кеша отсутствует или истек период его жизни, то функция вернет "false", в противном случае функция вернет "true".</p>
	 *
	 *
	 *
	 *
	 * @param int $cache_life_time  Время жизни кеша в секундах.
	 *
	 *
	 *
	 * @param string $cache_id  Уникальный идентификатор кеша. В этот идентификатор должны
	 * входить все параметры которые могут повлиять на результат
	 * исполнения кешируемого кода.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Папка, в которой хранится кеш компонента, относительно
	 * <i>/bitrix/cache/</i>. Если значение - "/", то кеш будет действительным для
	 * всех каталогов сайта. <br>Необязательный. По умолчанию - текущий
	 * каталог.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPHPCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$SECTION_ID.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // если кеш есть и он ещё не истек то
	 * if($obCache-&gt;InitCache($life_time, $cache_id, "/")) :
	 *     // получаем закешированные переменные
	 *     $vars = <b>$obCache-&gt;GetVars</b>();
	 *     $SECTION_TITLE = $vars["SECTION_TITLE"];
	 * else :
	 *     // иначе обращаемся к базе
	 *     $arSection = GetIBlockSection($SECTION_ID);
	 *     $SECTION_TITLE = $arSection["NAME"];
	 * endif;
	 * 
	 * // добавляем пункт меню в навигационную цепочку
	 * $APPLICATION-&gt;AddChainItem($SECTION_TITLE, $SECTION_URL."SECTION_ID=".$SECTION_ID);
	 * 
	 * // начинаем буферизирование вывода
	 * if($obCache-&gt;StartDataCache()):
	 * 
	 *     // выбираем из базы параметры элемента инфо-блока
	 *     if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *         echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *     endif;
	 * 
	 *     // записываем предварительно буферизированный вывод в файл кеша
	 *     // вместе с дополнительной переменной
	 *     $obCache-&gt;EndDataCache(array(
	 *         "SECTION_TITLE"    =&gt; $SECTION_TITLE
	 *         )); 
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3485"
	 * >Кеширование</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/output.php">CPHPCache::Output</a></li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php
	 * @author Bitrix
	 */
	public static function InitCache($TTL, $uniq_str, $initdir=false, $basedir = "cache")
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;
		if($initdir === false)
			$initdir = $APPLICATION->GetCurDir();

		$this->basedir = BX_PERSONAL_ROOT."/".$basedir."/";
		$this->initdir = $initdir;
		$this->filename = "/".CPHPCache::GetPath($uniq_str);
		$this->TTL = $TTL;
		$this->uniq_str = $uniq_str;

		$this->vars = false;

		if($TTL<=0)
			return false;

		if(isset($_GET["clear_cache_session"]) || isset($_GET["clear_cache"]))
		{
			if(is_object($USER) && $USER->CanDoOperation('cache_control'))
			{
				if(isset($_GET["clear_cache_session"]))
				{
					if(strtoupper($_GET["clear_cache_session"])=="Y")
						$_SESSION["SESS_CLEAR_CACHE"] = "Y";
					elseif(strlen($_GET["clear_cache_session"]) > 0)
						unset($_SESSION["SESS_CLEAR_CACHE"]);
				}

				if(isset($_GET["clear_cache"]) && strtoupper($_GET["clear_cache"])=="Y")
					return false;
			}
		}

		if(isset($_SESSION["SESS_CLEAR_CACHE"]) && $_SESSION["SESS_CLEAR_CACHE"] == "Y")
			return false;

		$arAllVars = array("CONTENT" => "", "VARS" => "");
		if(!$this->_cache->read($arAllVars, $this->basedir, $this->initdir, $this->filename, $this->TTL))
			return false;

		$GLOBALS["CACHE_STAT_BYTES"] += $this->_cache->read;
		$this->content = $arAllVars["CONTENT"];
		$this->vars = $arAllVars["VARS"];
		return true;
	}

	
	/**
	 * <p>Выводит HTML содержимое кеша.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPHPCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$SECTION_ID.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // если кеш есть и он ещё не истек то
	 * if($obCache-&gt;InitCache($life_time, $cache_id, "/") :
	 * 
	 *     // получаем закешированные переменные
	 *     $vars = $obCache-&gt;GetVars();
	 *     $SECTION_TITLE = $vars["SECTION_TITLE"];
	 * 
	 *     // добавляем пункт меню в навигационную цепочку
	 *     $APPLICATION-&gt;AddChainItem($SECTION_TITLE, $SECTION_URL."SECTION_ID=".$SECTION_ID);
	 * 
	 *     // выводим на экран содержимое кеша
	 *     <b>$obCache-&gt;Output</b>();
	 * 
	 * else :
	 * 
	 *     // иначе обращаемся к базе
	 *     $arSection = GetIBlockSection($SECTION_ID);
	 *     $SECTION_TITLE = $arSection["NAME"];
	 * 
	 *     // добавляем пункт меню в навигационную цепочку
	 *     $APPLICATION-&gt;AddChainItem($SECTION_TITLE, $SECTION_URL."SECTION_ID=".$SECTION_ID);
	 * 
	 *     // начинаем буферизирование вывода
	 *     if($obCache-&gt;StartDataCache()):
	 * 
	 *         // выбираем из базы параметры элемента инфо-блока
	 *         if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *             echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *         endif;
	 * 
	 *         // записываем предварительно буферизированный вывод в файл кеша
	 *         // вместе с дополнительной переменной
	 *         $obCache-&gt;EndDataCache(array(
	 *             "SECTION_TITLE"        =&gt; $SECTION_TITLE
	 *             )); 
	 *     endif;
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89607]Кеширование[/link]</li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/output.php
	 * @author Bitrix
	 */
	public static function Output()
	{
		echo $this->content;
	}

	
	/**
	 * <p>Возвращает PHP переменные сохраненные в кеше.</p>
	 *
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPHPCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$SECTION_ID.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // если кэш есть и он ещё не истек то
	 * if($obCache-&gt;InitCache($life_time, $cache_id, "/")) :
	 *     // получаем закешированные переменные
	 *     $vars = <b>$obCache-&gt;GetVars</b>();
	 *     $SECTION_TITLE = $vars["SECTION_TITLE"];
	 * else :
	 *     // иначе обращаемся к базе
	 *     $arSection = GetIBlockSection($SECTION_ID);
	 *     $SECTION_TITLE = $arSection["NAME"];
	 * endif;
	 * 
	 * // добавляем пункт меню в навигационную цепочку
	 * $APPLICATION-&gt;AddChainItem($SECTION_TITLE, $SECTION_URL."SECTION_ID=".$SECTION_ID);
	 * 
	 * // начинаем буферизирование вывода
	 * if($obCache-&gt;StartDataCache()):
	 * 
	 *     // выбираем из базы параметры элемента инфо-блока
	 *     if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *         echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *     endif;
	 * 
	 *     // записываем предварительно буферизированный вывод в файл кэша
	 *     // вместе с дополнительной переменной
	 *     $obCache-&gt;EndDataCache(array(
	 *         "SECTION_TITLE"    =&gt; $SECTION_TITLE
	 *         )); 
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89607]Кеширование[/link]</li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/getvars.php
	 * @author Bitrix
	 */
	public static function GetVars()
	{
		return $this->vars;
	}

	
	/**
	 * <p>Начинает буферизацию выводимого HTML, либо выводит содержимое кеша если он ещё не истек. Если файл кеша истек, то функция возвращает "true", в противном случае - "false".</p>
	 *
	 *
	 *
	 *
	 * @param int $cache_life_time = false Время жизни кеша в секундах.<br> Необязательный. По умолчанию -
	 * время жизни кеша предварительно заданное в функции <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/initcache.php">InitCache</a>.
	 *
	 *
	 *
	 * @param string $cache_id = false Уникальный идентификатор кеша. В этот идентификатор должны
	 * входить все параметры которые могут повлиять на результат
	 * исполнения кешируемого кода.<br> Необязательный. По умолчанию -
	 * уникальный идентификатор кеша предварительно заданный в функции
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a>.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Папка, в которой хранится кеш компонента, относительно
	 * <i>/bitrix/cache/</i>. Если значение - "/", то кеш будет действительным для
	 * всех каталогов сайта.<br> Необязательный. По умолчанию - имя
	 * каталога предварительно заданное в функции <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a>.
	 *
	 *
	 *
	 * @param array $vars = array() Массив переменных, которые необходимо закешировать, вида: <pre>array(
	 * "ИМЯ ПЕРЕМЕННОЙ 1" =&gt; "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 1", "ИМЯ ПЕРЕМЕННОЙ 2" =&gt;
	 * "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 2", ...)</pre> Непосредственно запись переменных
	 * в файл кеша осуществляется функцией <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/enddatacache.php">CPHPCache::EndDataCache</a>.<br>
	 * Необязательный. По умолчанию - пустой массив.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPHPCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$SECTION_ID.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // если кеш есть и он ещё не истек, то
	 * if($obCache-&gt;InitCache($life_time, $cache_id, "/") :
	 *     // получаем закешированные переменные
	 *     $vars = $obCache-&gt;GetVars();
	 *     $SECTION_TITLE = $vars["SECTION_TITLE"];
	 * else :
	 *     // иначе обращаемся к базе
	 *     $arSection = GetIBlockSection($SECTION_ID);
	 *     $SECTION_TITLE = $arSection["NAME"];
	 * endif;
	 * 
	 * // добавляем пункт меню в навигационную цепочку
	 * $APPLICATION-&gt;AddChainItem($SECTION_TITLE, $SECTION_URL."SECTION_ID=".$SECTION_ID);
	 * 
	 * // начинаем буферизирование вывода
	 * if(<b>$obCache-&gt;StartDataCache</b>()):
	 * 
	 *     // выбираем из базы параметры элемента инфо-блока
	 *     if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *         echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *     endif;
	 * 
	 *     // записываем предварительно буферизированный вывод в файл кеша
	 *     // вместе с дополнительной переменной
	 *     $obCache-&gt;EndDataCache(array(
	 *         "SECTION_TITLE"    =&gt; $SECTION_TITLE
	 *         )); 
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89607]Кеширование[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/enddatacache.php">CPHPCache::EndDataCache</a> </li> <li>
	 * <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php
	 * @author Bitrix
	 */
	public static function StartDataCache($TTL=false, $uniq_str=false, $initdir=false, $vars=Array(), $basedir = "cache")
	{
		$narg = func_num_args();
		if($narg<=0)
			$TTL = $this->TTL;
		if($narg<=1)
			$uniq_str = $this->uniq_str;
		if($narg<=2)
			$initdir = $this->initdir;
		if($narg<=3)
			$vars = $this->vars;

		if($this->InitCache($TTL, $uniq_str, $initdir, $basedir))
		{
			$this->Output();
			return false;
		}

		if($TTL<=0)
			return true;

		ob_start();
		$this->vars = $vars;
		$this->bStarted = true;

		return true;
	}

	public static function AbortDataCache()
	{
		if(!$this->bStarted)
			return;
		$this->bStarted = false;

		ob_end_flush();
	}

	/**
	 * Saves the result of calculation to the cache.
	 *
	 * @param mixed $vars
	 * @return void
	 */
	
	/**
	 * <p>Выводит <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/startdatacache.php">буферизированный HTML</a> и сохраняет его на диске вместе с заданным массивом переменных в файл кеша.</p>
	 *
	 *
	 *
	 *
	 * @param mixed $vars = false Массив переменных, значения которых необходимо записать в файл
	 * кэша, вида: <pre>array( "ИМЯ ПЕРЕМЕННОЙ 1" =&gt; "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 1", "ИМЯ
	 * ПЕРЕМЕННОЙ 2" =&gt; "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 2", ...)</pre>Необязательный. По
	 * умолчанию - массив переменных предварительно заданный в функции
	 * <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a>.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPHPCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$SECTION_ID.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // если кэш есть и он ещё не истек то
	 * if($obCache-&gt;InitCache($life_time, $cache_id, "/") :
	 *     // получаем закешированные переменные
	 *     $vars = $obCache-&gt;GetVars();
	 *     $SECTION_TITLE = $vars["SECTION_TITLE"];
	 * else :
	 *     // иначе обращаемся к базе
	 *     $arSection = GetIBlockSection($SECTION_ID);
	 *     $SECTION_TITLE = $arSection["NAME"];
	 * endif;
	 * 
	 * // добавляем пункт меню в навигационную цепочку
	 * $APPLICATION-&gt;AddChainItem($SECTION_TITLE, $SECTION_URL."SECTION_ID=".$SECTION_ID);
	 * 
	 * // начинаем буферизирование вывода
	 * if($obCache-&gt;StartDataCache()):
	 * 
	 *     // выбираем из базы параметры элемента инфо-блока
	 *     if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *         echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *     endif;
	 * 
	 *     // записываем предварительно буферизированный вывод в файл кеша
	 *     // вместе с дополнительной переменной
	 *     <b>$obCache-&gt;EndDataCache</b>(array(
	 *         "SECTION_TITLE"    =&gt; $SECTION_TITLE
	 *         )); 
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89607]Кеширование[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/enddatacache.php
	 * @author Bitrix
	 */
	public static function EndDataCache($vars=false)
	{
		if(!$this->bStarted)
			return;
		$this->bStarted = false;

		$arAllVars = array(
			"CONTENT" => ob_get_contents(),
			"VARS" => ($vars!==false? $vars: $this->vars),
		);

		$this->_cache->write($arAllVars, $this->basedir, $this->initdir, $this->filename, $this->TTL);
		$GLOBALS["CACHE_STAT_BYTES"] += $this->_cache->written;

		if(strlen(ob_get_contents()) > 0)
			ob_end_flush();
		else
			ob_end_clean();
	}

	
	/**
	 * <p>Проверяет не истек ли период жизни кеша. Функция как правило используется для удаления файлов кеша, период жизни которых истек.</p> <p class="note">Файл кеша создаваемый функциями класса CPHPCache имеет расширение ".php"</p>
	 *
	 *
	 *
	 *
	 * @param string $full_path  Полный путь к файлу кеша.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/cache/";
	 * if($handle = @opendir($path))
	 * {
	 *     while(($file=readdir($handle))!==false)
	 *     {
	 *         if($file == "." || $file == "..") continue;
	 *         if(!is_dir($path."/".$file))
	 *         {
	 *             if(substr($file, -5)==".html")
	 *                 $expired = CPageCache::IsCacheExpired($path."/".$file);
	 *             elseif(substr($file, -4)==".php")
	 *                 $expired = <b>CPHPCache::IsCacheExpired</b>($path."/".$file);
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89607]Кеширование[/link]</li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/other/bxclearcache.php">BXClearCache</a></li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/iscacheexpired.php
	 * @author Bitrix
	 */
	public static function IsCacheExpired($path)
	{
		if(is_object($this) && is_object($this->_cache))
		{
			return $this->_cache->IsCacheExpired($path);
		}
		else
		{
			$obCache = new CPHPCache();
			return $obCache->IsCacheExpired($path);
		}
	}
}

class CPageCache
{
	var $_cache;
	var $filename;
	var $content;
	var $TTL;
	var $bStarted = false;
	var $uniq_str = false;
	var $basedir;
	var $initdir = false;

	function __construct()
	{
		$this->_cache = CPHPCache::_new_cache_object();
	}

	public static function GetPath($uniq_str)
	{
		$un = md5($uniq_str);
		return substr($un, 0, 2)."/".$un.".html";
	}

	public static function Clean($uniq_str, $initdir = false, $basedir = "cache")
	{
		if(is_object($this) && is_object($this->_cache))
		{
			$basedir = BX_PERSONAL_ROOT."/".$basedir."/";
			$filename = CPageCache::GetPath($uniq_str);
			return $this->_cache->clean($basedir, $initdir, "/".$filename);
		}
		else
		{
			$obCache = new CPageCache();
			return $obCache->Clean($uniq_str, $initdir, $basedir);
		}
	}

	public static function CleanDir($initdir = false, $basedir = "cache")
	{
		$basedir = BX_PERSONAL_ROOT."/".$basedir."/";
		return $this->_cache->clean($basedir, $initdir);
	}

	
	/**
	 * <p>Инициализирует ряд свойств объекта класса CPageCache. Если файл кеша отсутствует или истек период его жизни, то функция вернет "false", в противном случае функция вернет "true".</p>
	 *
	 *
	 *
	 *
	 * @param int $cache_life_time  Время жизни кеша в секундах.
	 *
	 *
	 *
	 * @param string $cache_id  Уникальный идентификатор кеша. В этот идентификатор должны
	 * входить все параметры которые могут повлиять на результат
	 * исполнения кешируемого кода.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Папка, в которой хранится кеш компонента, относительно
	 * <i>/bitrix/cache/</i>. Если значение - "/", то кеш будет действительным для
	 * всех каталогов сайта. <br>Необязательный. По умолчанию - текущий
	 * каталог.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3485"
	 * >Кеширование</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/output.php">CPageCache::Output</a></li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/initcache.php
	 * @author Bitrix
	 */
	public static function InitCache($TTL, $uniq_str, $initdir = false, $basedir = "cache")
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $USER;
		if($initdir === false)
			$initdir = $APPLICATION->GetCurDir();

		$this->basedir = BX_PERSONAL_ROOT."/".$basedir."/";
		$this->initdir = $initdir;
		$this->filename = "/".CPageCache::GetPath($uniq_str);
		$this->TTL = $TTL;
		$this->uniq_str = $uniq_str;

		if($TTL<=0)
			return false;

		if(is_object($USER) && $USER->CanDoOperation('cache_control'))
		{
			if(isset($_GET["clear_cache_session"]))
			{
				if(strtoupper($_GET["clear_cache_session"])=="Y")
					$_SESSION["SESS_CLEAR_CACHE"] = "Y";
				elseif(strlen($_GET["clear_cache_session"]) > 0)
					unset($_SESSION["SESS_CLEAR_CACHE"]);
			}

			if(isset($_GET["clear_cache"]) && strtoupper($_GET["clear_cache"])=="Y")
				return false;
		}

		if(isset($_SESSION["SESS_CLEAR_CACHE"]) && $_SESSION["SESS_CLEAR_CACHE"] == "Y")
			return false;

		if(!$this->_cache->read($this->content, $this->basedir, $this->initdir, $this->filename, $this->TTL))
			return false;

		$GLOBALS["CACHE_STAT_BYTES"] += $this->_cache->read;
		return true;
	}

	
	/**
	 * <p>Выводит содержимое кеша. HTML-содержимое кэша доступно, только если файл кеша существует и предварительно был вызван метод <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/initcache.php">CPageCache::InitCache</a> или <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/startdatacache.php">CPageCache::StartDataCache</a>.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3485"
	 * >Кеширование</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/initcache.php">CPageCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/startdatacache.php">CPageCache::StartDataCache</a></li>
	 * </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/output.php
	 * @author Bitrix
	 */
	public static function Output()
	{
		echo $this->content;
	}

	
	/**
	 * <p>Начинает буферизацию выводимого HTML, либо выводит содержимое кеша если он ещё не истек. Если файл кеша истек, то функция возвращает "true", в противном случае - "false".</p>
	 *
	 *
	 *
	 *
	 * @param int $cache_life_time  Время жизни кеша в секундах.
	 *
	 *
	 *
	 * @param string $cache_id  Уникальный идентификатор кеша. В этот идентификатор должны
	 * входить все параметры которые могут повлиять на результат
	 * исполнения кэшируемого кода.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Папка, в которой хранится кеш компонента, относительно
	 * <i>/bitrix/cache/</i>. Если значение - "/", то кеш будет действительным для
	 * всех каталогов сайта. <br>Необязательный. По умолчанию - текущий
	 * каталог.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPageCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$IBLOCK_TYPE.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // инициализируем буферизирование вывода
	 * if(<b>$obCache-&gt;StartDataCache</b>($life_time, $cache_id, "/")):
	 *     // выбираем из базы параметры элемента инфо-блока
	 *     if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *         echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *     endif;
	 *     // записываем предварительно буферизированный вывод в файл кеша
	 *     $obCache-&gt;EndDataCache(); 
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li>[link=89607]Кеширование[/link] </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/enddatacache.php">CPageCache::EndDataCache</a> </li>
	 * <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/startdatacache.php
	 * @author Bitrix
	 */
	public static function StartDataCache($TTL, $uniq_str=false, $initdir=false, $basedir = "cache")
	{
		if($this->InitCache($TTL, $uniq_str, $initdir, $basedir))
		{
			$this->Output();
			return false;
		}

		if($TTL<=0)
			return true;

		ob_start();
		$this->bStarted = true;
		return true;
	}

	public static function AbortDataCache()
	{
		if(!$this->bStarted)
			return;
		$this->bStarted = false;

		ob_end_flush();
	}

	
	/**
	 * <p>Выводит <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/startdatacache.php">буферизированный HTML</a> и сохраняет его на диске в файл кеша.</p>
	 *
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // создаем объект
	 * $obCache = new CPageCache; 
	 * 
	 * // время кеширования - 30 минут
	 * $life_time = 30*60; 
	 * 
	 * // формируем идентификатор кеша в зависимости от всех параметров 
	 * // которые могут повлиять на результирующий HTML
	 * $cache_id = $ELEMENT_ID.$IBLOCK_TYPE.$USER-&gt;GetUserGroupString(); 
	 * 
	 * // инициализируем буферизирование вывода
	 * if($obCache-&gt;StartDataCache($life_time, $cache_id, "/")):
	 *     // выбираем из базы параметры элемента инфо-блока
	 *     if($arIBlockElement = GetIBlockElement($ELEMENT_ID, $IBLOCK_TYPE)):
	 *         echo "&lt;pre&gt;"; print_r($arIBlockElement); echo "&lt;/pre&gt;";
	 *     endif;
	 *     // записываем буферизированный результат на диск в файл кеша
	 *     <b>$obCache-&gt;EndDataCache</b>(); 
	 * endif;
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3485"
	 * >Кеширование</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/startdatacache.php">CPageCache::StartDataCache</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/enddatacache.php
	 * @author Bitrix
	 */
	public static function EndDataCache()
	{
		if(!$this->bStarted)
			return;
		$this->bStarted = false;

		$arAllVars = ob_get_contents();

		$this->_cache->write($arAllVars, $this->basedir, $this->initdir, $this->filename, $this->TTL);
		$GLOBALS["CACHE_STAT_BYTES"] += $this->_cache->written;

		if(strlen($arAllVars)>0)
			ob_end_flush();
		else
			ob_end_clean();
	}

	
	/**
	 * <p>Проверяет не истек ли период жизни кеша. Функция как правило используется для удаления файлов кеша, период жизни которых истек.</p> <p class="note">Файл кеша создаваемый функциями класса CPageCache имеет расширение ".html"</p>
	 *
	 *
	 *
	 *
	 * @param string $full_path  Полный путь к файлу кеша.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/cache/";
	 * if($handle = @opendir($path))
	 * {
	 *     while(($file=readdir($handle))!==false)
	 *     {
	 *         if($file == "." || $file == "..") continue;
	 *         if(!is_dir($path."/".$file))
	 *         {
	 *             if(substr($file, -5)==".html")
	 *                 $expired = <b>CPageCache::IsCacheExpired</b>($path."/".$file);
	 *             elseif(substr($file, -4)==".php")
	 *                 $expired = CPHPCache::IsCacheExpired($path."/".$file);
	 *         }
	 *     }
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3485"
	 * >Кеширование</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/reference/cpagecache/initcache.php">CPageCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/main/functions/other/bxclearcache.php">BXClearCache</a></li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/iscacheexpired.php
	 * @author Bitrix
	 */
	public static function IsCacheExpired($path)
	{
		if(is_object($this) && is_object($this->_cache))
		{
			return $this->_cache->IsCacheExpired($path);
		}
		else
		{
			$obCache = new CPHPCache();
			return $obCache->IsCacheExpired($path);
		}
	}
}


/**
 * <p>Удаляет все (либо только устаревшие) файлы кеша по указанному пути. Возвращает "true" в случае успешного завершения. В противном случае возвращает "false".</p>
 *
 *
 *
 *
 * @param bool $delete_all = false Если значение равно "true", то будут удалены все файлы кеша, если
 * значение равно "false", то будут удалены только устаревшие файлы
 * кеша.
 *
 *
 *
 * @param string $dir = "" Каталог, начиная с которого производить обработку. Применяется
 * для частичной обработки кеша. Задается относительно корневой
 * папки кеша - <b>/bitrix/cache/</b>.
 *
 *
 *
 * @return bool 
 *
 *
 * <h4>Example</h4> 
 * <pre>
 * &lt;?
 * // удалим весь кэш для каталога /forum/
 * <b>BXClearCache</b>(true, "/forum/");
 * ?&gt;
 * </pre>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/other/bxclearcache.php
 * @author Bitrix
 */
function BXClearCache($full=false, $initdir="")
{
	if($full !== true && $full !== false && $initdir === "" && is_string($full))
	{
		$initdir = $full;
		$full = true;
	}

	$res = true;

	if($full === true)
	{
		$obCache = new CPHPCache;
		$obCache->CleanDir($initdir, "cache");
	}

	$path = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/cache".$initdir;
	if(is_dir($path) && ($handle = opendir($path)))
	{
		while(($file = readdir($handle)) !== false)
		{
			if($file == "." || $file == "..") continue;

			if(is_dir($path."/".$file))
			{
				if(!BXClearCache($full, $initdir."/".$file))
				{
					$res = false;
				}
				else
				{
					@chmod($path."/".$file, BX_DIR_PERMISSIONS);
					//We suppress error handle here because there may be valid cache files in this dir
					@rmdir($path."/".$file);
				}
			}
			elseif($full)
			{
				@chmod($path."/".$file, BX_FILE_PERMISSIONS);
				if(!unlink($path."/".$file))
					$res = false;
			}
			elseif(substr($file, -5)==".html")
			{
				if(CPageCache::IsCacheExpired($path."/".$file))
				{
					@chmod($path."/".$file, BX_FILE_PERMISSIONS);
					if(!unlink($path."/".$file))
						$res = false;
				}
			}
			elseif(substr($file, -4)==".php")
			{
				if(CPHPCache::IsCacheExpired($path."/".$file))
				{
					@chmod($path."/".$file, BX_FILE_PERMISSIONS);
					if(!unlink($path."/".$file))
						$res = false;
				}
			}
			else
			{
				//We should skip unknown file
				//it will be deleted with full cache cleanup
			}
		}
		closedir($handle);
	}

	return $res;
}
// The main purpose of the class is:
// one read - many uses - optional one write
// of the set of variables
class CCacheManager
{
	/** @var CPHPCache[] */
	var $CACHE = array();
	var $CACHE_PATH = array();
	var $VARS = array();
	var $TTL = array();
	// Tries to read cached variable value from the file
	// Returns true on success
	// overwise returns false
	public static function Read($ttl, $uniqid, $table_id=false)
	{
		global $DB;
		if(isset($this->CACHE[$uniqid]))
		{
			return true;
		}
		else
		{
			$this->CACHE[$uniqid] = new CPHPCache;
			$this->CACHE_PATH[$uniqid] = $DB->type.($table_id===false?"":"/".$table_id);
			$this->TTL[$uniqid] = $ttl;
			return $this->CACHE[$uniqid]->InitCache($ttl, $uniqid, $this->CACHE_PATH[$uniqid], "managed_cache");
		}
	}
	// This method is used to read the variable value
	// from the cache after successfull Read
	public static function Get($uniqid)
	{
		if(array_key_exists($uniqid, $this->VARS))
			return $this->VARS[$uniqid];
		elseif(isset($this->CACHE[$uniqid]))
			return $this->CACHE[$uniqid]->GetVars();
		else
			return false;
	}
	// Sets new value to the variable
	public static function Set($uniqid, $val)
	{
		if(isset($this->CACHE[$uniqid]))
			$this->VARS[$uniqid]=$val;
	}

	public static function SetImmediate($uniqid, $val)
	{
		if(isset($this->CACHE[$uniqid]))
		{
			$obCache = new CPHPCache;
			$obCache->StartDataCache($this->TTL[$uniqid], $uniqid, $this->CACHE_PATH[$uniqid], $val, "managed_cache");
			$obCache->EndDataCache();

			unset($this->CACHE[$uniqid]);
			unset($this->CACHE_PATH[$uniqid]);
			unset($this->VARS[$uniqid]);
		}
	}
	// Marks cache entry as invalid
	public static function Clean($uniqid, $table_id=false)
	{
		global $DB;
		$obCache = new CPHPCache;
		$obCache->Clean($uniqid, $DB->type.($table_id===false?"":"/".$table_id), "managed_cache");
		if(isset($this->CACHE[$uniqid]))
		{
			unset($this->CACHE[$uniqid]);
			unset($this->CACHE_PATH[$uniqid]);
			unset($this->VARS[$uniqid]);
		}
	}
	// Marks cache entries associated with the table as invalid
	public static function CleanDir($table_id)
	{
		global $DB;
		$strPath = $DB->type."/".$table_id;
		foreach($this->CACHE_PATH as $uniqid=>$Path)
		{
			if($Path==$strPath)
			{
				unset($this->CACHE[$uniqid]);
				unset($this->CACHE_PATH[$uniqid]);
				unset($this->VARS[$uniqid]);
			}
		}
		$obCache = new CPHPCache;
		$obCache->CleanDir($DB->type."/".$table_id, "managed_cache");
	}
	// Clears all managed_cache
	public static function CleanAll()
	{
		$this->CACHE = array();
		$this->CACHE_PATH = array();
		$this->VARS = array();
		$this->TTL = array();
		$obCache = new CPHPCache;
		$obCache->CleanDir(false, "managed_cache");

		if(defined("BX_COMP_MANAGED_CACHE"))
			$this->ClearByTag(true);
	}
	// Use it to flush cache to the files.
	// Causion: only at the end of all operations!
	function _Finalize()
	{
		global $CACHE_MANAGER;
		$obCache = new CPHPCache;
		foreach($CACHE_MANAGER->CACHE as $uniqid=>$val)
		{
			if(array_key_exists($uniqid, $CACHE_MANAGER->VARS))
			{
				$obCache->StartDataCache($CACHE_MANAGER->TTL[$uniqid], $uniqid, $CACHE_MANAGER->CACHE_PATH[$uniqid],  $CACHE_MANAGER->VARS[$uniqid], "managed_cache");
				$obCache->EndDataCache();
			}
		}
	}

	/*Components managed(tagged) cache*/

	var $comp_cache_stack = array();
	var $SALT = false;
	var $DBCacheTags = false;
	var $bWasTagged = false;

	public static function InitDBCache()
	{
		if(!$this->DBCacheTags)
		{
			global $DB;

			$this->DBCacheTags = array();
			$rs = $DB->Query("
				SELECT *
				FROM b_cache_tag
				WHERE SITE_ID = '".$DB->ForSQL(SITE_ID, 2)."'
				AND CACHE_SALT = '".$DB->ForSQL($this->SALT, 4)."'
			");
			while($ar = $rs->Fetch())
			{
				$path = $ar["RELATIVE_PATH"];
				$this->DBCacheTags[$path][$ar["TAG"]] = true;
			}
		}

	}

	public static function InitCompSalt()
	{
		if($this->SALT === false)
		{
			if($_SERVER["SCRIPT_NAME"] == "/bitrix/urlrewrite.php" && isset($_SERVER["REAL_FILE_PATH"]))
				$SCRIPT_NAME = $_SERVER["REAL_FILE_PATH"];
			elseif($_SERVER["SCRIPT_NAME"] == "/404.php" && isset($_SERVER["REAL_FILE_PATH"]))
				$SCRIPT_NAME = $_SERVER["REAL_FILE_PATH"];
			else
				$SCRIPT_NAME = $_SERVER["SCRIPT_NAME"];

			$this->SALT = "/".substr(md5($SCRIPT_NAME), 0, 3);
		}
	}

	public static function GetCompCachePath($relativePath)
	{
		global $BX_STATE;
		$this->InitCompSalt();

		if($BX_STATE === "WA")
			$salt = $this->SALT;
		else
			$salt = "/".substr(md5($BX_STATE), 0, 3);

		$path = "/".SITE_ID.$relativePath.$salt;
		return $path;
	}

	public static function StartTagCache($relativePath)
	{
		array_unshift($this->comp_cache_stack, array($relativePath, array()));
	}

	public static function EndTagCache()
	{
		global $DB;
		$this->InitCompSalt();

		if($this->bWasTagged)
		{
			$this->InitDBCache();
			$sqlSITE_ID = $DB->ForSQL(SITE_ID, 2);
			$sqlCACHE_SALT = $this->SALT;

			$strSqlPrefix = "
				INSERT INTO b_cache_tag (SITE_ID, CACHE_SALT, RELATIVE_PATH, TAG)
				VALUES
			";
			$maxValuesLen = $DB->type=="MYSQL"? 2048: 0;
			$strSqlValues = "";

			foreach($this->comp_cache_stack as $arCompCache)
			{
				$path = $arCompCache[0];
				if(strlen($path))
				{
					$sqlRELATIVE_PATH = $DB->ForSQL($path, 255);

					$sql = ",\n('".$sqlSITE_ID."', '".$sqlCACHE_SALT."', '".$sqlRELATIVE_PATH."',";

					if(!isset($this->DBCacheTags[$path]))
						$this->DBCacheTags[$path] = array();

					foreach($arCompCache[1] as $tag => $t)
					{
						if(!isset($this->DBCacheTags[$path][$tag]))
						{
							$strSqlValues .= $sql." '".$DB->ForSQL($tag, 50)."')";
							if(strlen($strSqlValues) > $maxValuesLen)
							{
								$DB->Query($strSqlPrefix.substr($strSqlValues, 2));
								$strSqlValues = "";
							}
							$this->DBCacheTags[$path][$tag] = true;
						}
					}
				}
			}
			if($strSqlValues <> '')
			{
				$DB->Query($strSqlPrefix.substr($strSqlValues, 2));
			}
		}

		array_shift($this->comp_cache_stack);
	}

	public static function AbortTagCache()
	{
		array_shift($this->comp_cache_stack);
	}

	public static function RegisterTag($tag)
	{
		if(count($this->comp_cache_stack))
		{
			$this->comp_cache_stack[0][1][$tag] = true;
			$this->bWasTagged = true;
		}
	}

	public static function ClearByTag($tag)
	{
		global $DB;

		if($tag === true)
			$sqlWhere = " WHERE TAG <> '*'";
		else
			$sqlWhere = "  WHERE TAG = '".$DB->ForSQL($tag)."'";

		$arDirs = array();
		$rs = $DB->Query("SELECT * FROM b_cache_tag".$sqlWhere);
		while($ar = $rs->Fetch())
			$arDirs[$ar["RELATIVE_PATH"]] = $ar;
		$DB->Query("DELETE FROM b_cache_tag".$sqlWhere);

		$obCache = new CPHPCache;
		foreach($arDirs as $path => $ar)
		{
			$DB->Query("
				DELETE FROM b_cache_tag
				WHERE SITE_ID = '".$DB->ForSQL($ar["SITE_ID"])."'
				AND CACHE_SALT = '".$DB->ForSQL($ar["CACHE_SALT"])."'
				AND RELATIVE_PATH = '".$DB->ForSQL($ar["RELATIVE_PATH"])."'
			");

			if(preg_match("/^managed:(.+)$/", $path, $match))
				$this->CleanDir($match[1]);
			else
				$obCache->CleanDir($path);

			unset($this->DBCacheTags[$path]);
		}
	}
}

global $CACHE_MANAGER;
$CACHE_MANAGER = new CCacheManager;

$GLOBALS["CACHE_STAT_BYTES"] = 0;

/*****************************************************************************************************/
/************************  CStackCacheManager  *******************************************************/
/*****************************************************************************************************/
class CStackCacheEntry
{
	var $entity = "";
	var $id = "";
	var $values = array();
	var $len = 10;
	var $ttl = 3600;
	var $cleanGet = true;
	var $cleanSet = true;

	function __construct($entity, $length = 0, $ttl = 0)
	{
		$this->entity = $entity;

		if($length > 0)
			$this->len = intval($length);

		if($ttl > 0)
			$this->ttl = intval($ttl);
	}

	public static function SetLength($length)
	{
		if($length > 0)
			$this->len = intval($length);

		while(count($this->values) > $this->len)
		{
			$this->cleanSet = false;
			array_shift($this->values);
		}
	}

	public static function SetTTL($ttl)
	{
		if($ttl > 0)
			$this->ttl = intval($ttl);
	}

	public static function Load()
	{
		global $DB;
		$objCache = new CPHPCache;
		if($objCache->InitCache($this->ttl, $this->entity, $DB->type."/".$this->entity, "stack_cache"))
		{
			$this->values = $objCache->GetVars();
			$this->cleanGet = true;
			$this->cleanSet = true;
		}
	}

	public static function DeleteEntry($id)
	{
		if(array_key_exists($id, $this->values))
		{
			unset($this->values[$id]);
			$this->cleanSet = false;
		}
	}

	public static function Clean()
	{
		global $DB;

		$objCache = new CPHPCache;
		$objCache->Clean($this->entity, $DB->type."/".$this->entity, "stack_cache");

		$this->values = array();
		$this->cleanGet = true;
		$this->cleanSet = true;
	}

	public static function Get($id)
	{
		if(array_key_exists($id, $this->values))
		{
			$result = $this->values[$id];
			//Move accessed value to the top of list only when it is not at the top
			end($this->values);
			if(key($this->values) !== $id)
			{
				$this->cleanGet = false;
				unset($this->values[$id]);
				$this->values = $this->values + array($id => $result);
			}

			return $result;
		}
		else
		{
			return false;
		}
	}

	public static function Set($id, $value)
	{
		if(array_key_exists($id, $this->values))
		{
			unset($this->values[$id]);
			$this->values = $this->values + array($id => $value);
		}
		else
		{
			$this->values = $this->values + array($id => $value);
			while(count($this->values) > $this->len)
				array_shift($this->values);
		}

		$this->cleanSet = false;
	}

	public static function Save()
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		global $DB;

		if(
			!$this->cleanSet
			|| (
				!$this->cleanGet
				&& (count($this->values) >= $this->len)
			)
		)
		{
			$objCache = new CPHPCache;
			$objCache->Clean($this->entity, $DB->type."/".$this->entity, "stack_cache");

			if($objCache->StartDataCache($this->ttl, $this->entity, $DB->type."/".$this->entity,  $this->values, "stack_cache"))
				$objCache->EndDataCache();

			$this->cleanGet = true;
			$this->cleanSet = true;
		}
	}
}

class CStackCacheManager
{
	/** @var CStackCacheEntry[] */
	var $cache = array();
	var $cacheLen = array();
	var $cacheTTL = array();
	var $eventHandlerAdded = false;

	public static function SetLength($entity, $length)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if(isset($this->cache[$entity]) && is_object($this->cache[$entity]))
			$this->cache[$entity]->SetLength($length);
		else
			$this->cacheLen[$entity] = $length;
	}

	public static function SetTTL($entity, $ttl)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if(isset($this->cache[$entity]) && is_object($this->cache[$entity]))
			$this->cache[$entity]->SetTTL($ttl);
		else
			$this->cacheTTL[$entity] = $ttl;
	}

	public static function Init($entity, $length = 0, $ttl = 0)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if (!$this->eventHandlerAdded)
		{
			AddEventHandler("main", "OnEpilog", array("CStackCacheManager", "SaveAll"));
			$this->eventHandlerAdded = True;
		}

		if($length <= 0 && isset($this->cacheLen[$entity]))
			$length = $this->cacheLen[$entity];

		if($ttl <= 0 && isset($this->cacheTTL[$entity]))
			$ttl = $this->cacheTTL[$entity];

		if (!array_key_exists($entity, $this->cache))
			$this->cache[$entity] = new CStackCacheEntry($entity, $length, $ttl);
	}

	public static function Load($entity)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if (!array_key_exists($entity, $this->cache))
			$this->Init($entity);

		$this->cache[$entity]->Load();
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public static function Clear($entity, $id = False)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		if ($id !== False)
			$this->cache[$entity]->DeleteEntry($id);
		else
			$this->cache[$entity]->Clean();
	}

	// Clears all managed_cache
	public static function CleanAll()
	{
		$this->cache = array();

		$objCache = new CPHPCache;
		$objCache->CleanDir(false, "stack_cache");
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public static function Exist($entity, $id)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return False;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		return array_key_exists($id, $this->cache[$entity]->values);
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public static function Get($entity, $id)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return False;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		return $this->cache[$entity]->Get($id);
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public static function Set($entity, $id, $value)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		$this->cache[$entity]->Set($id, $value);
	}

	public static function Save($entity)
	{
		if(defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if(array_key_exists($entity, $this->cache))
			$this->cache[$entity]->Save();
	}

	public static function SaveAll()
	{
		if(defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		/** @global CStackCacheManager $stackCacheManager */
		global $stackCacheManager;

		foreach($stackCacheManager->cache as $value)
		{
			$value->Save();
		}
	}

	public static function MakeIDFromArray($arVals)
	{
		$id = "id";

		sort($arVals);

		for ($i = 0, $c = count($arVals); $i < $c; $i++)
			$id .= "_".$arVals[$i];

		return $id;
	}
}

$GLOBALS["stackCacheManager"] = new CStackCacheManager();
