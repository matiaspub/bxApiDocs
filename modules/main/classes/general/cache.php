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

class CCacheDebug
{
	static $arCacheDebug = array();
	static $ShowCacheStat = false;
	static $skipUntil = array(
		"ccachedebug->add" => true,
		"cphpcache->initcache" => true,
		"cphpcache->startdatacache" => true,
		"cphpcache->enddatacache" => true,
		"cphpcache->clean" => true,
		"cphpcache->cleandir" => true,
		"cpagecache->initcache" => true,
		"cpagecache->startdatacache" => true,
		"cpagecache->enddatacache" => true,
		"cpagecache->clean" => true,
		"cpagecache->cleandir" => true,
		"ccachemanager->read" => true,
		"ccachemanager->setimmediate" => true,
		"ccachemanager->clean" => true,
		"ccachemanager->cleandir" => true,
		"ccachemanager->cleanall" => true,
		"cbitrixcomponent->startresultcache" => true,
	);
	public static function add($size, $path, $basedir, $initdir, $filename, $operation)
	{
		$prev = array();
		$found = -1;
		foreach(Bitrix\Main\Diag\Helper::getBackTrace(8) as $i => $tr)
		{
			$func = $tr["class"].$tr["type"].$tr["function"];

			if ($found < 0 && !isset(self::$skipUntil[strtolower($func)]))
			{
				$found = count(self::$arCacheDebug);
				self::$arCacheDebug[$found] = array(
					"TRACE" => array(),
					"path" => $path,
					"basedir" => $basedir,
					"initdir" => $initdir,
					"filename" => $filename,
					"cache_size" => $size,
					"callee_func" => $prev["class"].$prev["type"].$prev["function"],
					"operation" => $operation,
				);
				self::$arCacheDebug[$found]["TRACE"][] = array(
					"func" => $prev["class"].$prev["type"].$prev["function"],
					"args" => array(),
					"file" => $prev["file"],
					"line" => $prev["line"],
				);
			}

			if ($found > -1)
			{
				if (count(self::$arCacheDebug[$found]["TRACE"]) < 8)
				{
					$args = array();
					if (is_array($tr["args"]))
					{
						foreach ($tr["args"] as $k1 => $v1)
						{
							if (is_array($v1))
							{
								foreach ($v1 as $k2 => $v2)
								{
									if (is_scalar($v2))
										$args[$k1][$k2] = $v2;
									elseif (is_object($v2))
										$args[$k1][$k2] = get_class($v2);
									else
										$args[$k1][$k2] = gettype($v2);
								}
							}
							else
							{
								if (is_scalar($v1))
									$args[$k1] = $v1;
								elseif (is_object($v1))
									$args[$k1] = get_class($v1);
								else
									$args[$k1] = gettype($v1);
							}
						}
					}

					self::$arCacheDebug[$found]["TRACE"][] = array(
						"func" => $func,
						"args" => $args,
						"file" => $tr["file"],
						"line" => $tr["line"],
					);
				}
				else
				{
					break;
				}
			}
			$prev = $tr;
		}
	}
}

class CPHPCache
{
	/**
	 * @var Bitrix\Main\Data\Cache
	 */
	private $cache;

	public function __construct()
	{
		$this->cache = \Bitrix\Main\Data\Cache::createInstance();
	}

	static public function Clean($uniq_str, $initdir = false, $basedir = "cache")
	{
		if(is_object($this) && ($this instanceof CPHPCache))
		{
			return $this->cache->clean($uniq_str, $initdir, $basedir);
		}
		else
		{
			$obCache = new CPHPCache();
			return $obCache->Clean($uniq_str, $initdir, $basedir);
		}
	}

	
	/**
	 * <p>Функция очищает кеш по параметру <b>basedir</b>. Она подходит для сброса memcached-данных.</p>
	 *
	 *
	 *
	 *
	 * @param $initdi $r = false По умолчанию <i>false</i>
	 *
	 *
	 *
	 * @param $basedi $r = "cache" Директория с кешем.
	 *
	 *
	 *
	 * @return mixed 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * $obCache = new CPHPCache(); ... $obCache-&gt;CleanDir();
	 * </pre>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/cleandir.php
	 * @author Bitrix
	 */
	public function CleanDir($initdir = false, $basedir = "cache")
	{
		return $this->cache->cleanDir($initdir, $basedir);
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/output.php">CPHPCache::Output</a></li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php
	 * @author Bitrix
	 */
	public function InitCache($TTL, $uniq_str, $initdir=false, $basedir = "cache")
	{
		return $this->cache->initCache($TTL, $uniq_str, $initdir, $basedir);
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/output.php
	 * @author Bitrix
	 */
	public function Output()
	{
		return $this->cache->output();
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/getvars.php
	 * @author Bitrix
	 */
	public function GetVars()
	{
		return $this->cache->getVars();
	}

	
	/**
	 * <p>Начинает буферизацию выводимого HTML, либо выводит содержимое кеша если он ещё не истек. Если файл кеша истек, то функция возвращает "true", в противном случае - "false".</p>
	 *
	 *
	 *
	 *
	 * @param int $cache_life_time = false Время жизни кеша в секундах.<br> Необязательный. По умолчанию -
	 * время жизни кеша предварительно заданное в функции <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php">InitCache</a>.
	 *
	 *
	 *
	 * @param string $cache_id = false Уникальный идентификатор кеша. В этот идентификатор должны
	 * входить все параметры которые могут повлиять на результат
	 * исполнения кешируемого кода.<br> Необязательный. По умолчанию -
	 * уникальный идентификатор кеша предварительно заданный в функции
	 * <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a>.
	 *
	 *
	 *
	 * @param mixed $init_dir = false Папка, в которой хранится кеш компонента, относительно
	 * <i>/bitrix/cache/</i>. Если значение - "/", то кеш будет действительным для
	 * всех каталогов сайта.<br> Необязательный. По умолчанию - имя
	 * каталога предварительно заданное в функции <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a>.
	 *
	 *
	 *
	 * @param array $vars = array() Массив переменных, которые необходимо закешировать, вида: <pre>array(
	 * "ИМЯ ПЕРЕМЕННОЙ 1" =&gt; "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 1", "ИМЯ ПЕРЕМЕННОЙ 2" =&gt;
	 * "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 2", ...)</pre> Непосредственно запись переменных
	 * в файл кеша осуществляется функцией <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/enddatacache.php">CPHPCache::EndDataCache</a>.<br>
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/enddatacache.php">CPHPCache::EndDataCache</a> </li> <li>
	 * <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php
	 * @author Bitrix
	 */
	public function StartDataCache($TTL=false, $uniq_str=false, $initdir=false, $vars=Array(), $basedir = "cache")
	{
		$narg = func_num_args();
		if($narg<=0)
			return $this->cache->startDataCache();
		if($narg<=1)
			return $this->cache->startDataCache($TTL);
		if($narg<=2)
			return $this->cache->startDataCache($TTL, $uniq_str);
		if($narg<=3)
			return $this->cache->startDataCache($TTL, $uniq_str, $initdir);

		return $this->cache->startDataCache($TTL, $uniq_str, $initdir, $vars, $basedir);
	}

	public function AbortDataCache()
	{
		$this->cache->abortDataCache();
	}

	/**
	 * Saves the result of calculation to the cache.
	 *
	 * @param mixed $vars
	 * @return void
	 */
	
	/**
	 * <p>Выводит <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php">буферизированный HTML</a> и сохраняет его на диске вместе с заданным массивом переменных в файл кеша.</p>
	 *
	 *
	 *
	 *
	 * @param mixed $vars = false Массив переменных, значения которых необходимо записать в файл
	 * кэша, вида: <pre>array( "ИМЯ ПЕРЕМЕННОЙ 1" =&gt; "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 1", "ИМЯ
	 * ПЕРЕМЕННОЙ 2" =&gt; "ЗНАЧЕНИЕ ПЕРЕМЕННОЙ 2", ...)</pre>Необязательный. По
	 * умолчанию - массив переменных предварительно заданный в функции
	 * <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a>.
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/startdatacache.php">CPHPCache::StartDataCache</a> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/enddatacache.php
	 * @author Bitrix
	 */
	public function EndDataCache($vars=false)
	{
		$this->cache->endDataCache($vars);
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/initcache.php">CPHPCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxclearcache.php">BXClearCache</a></li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cphpcache/iscacheexpired.php
	 * @author Bitrix
	 */
	public static function IsCacheExpired($path)
	{
		if(is_object($this) && ($this instanceof CPHPCache))
		{
			return $this->cache->isCacheExpired($path);
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

	public function __construct()
	{
		$this->_cache = \Bitrix\Main\Data\Cache::createCacheEngine();
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
			if (\Bitrix\Main\Data\Cache::getShowCacheStat())
				\Bitrix\Main\Diag\CacheTracker::add(0, "", $basedir, $initdir, "/".$filename, "C");
			return $this->_cache->clean($basedir, $initdir, "/".$filename);
		}
		else
		{
			$obCache = new CPageCache();
			return $obCache->Clean($uniq_str, $initdir, $basedir);
		}
	}

	public function CleanDir($initdir = false, $basedir = "cache")
	{
		$basedir = BX_PERSONAL_ROOT."/".$basedir."/";
		if (\Bitrix\Main\Data\Cache::getShowCacheStat())
			\Bitrix\Main\Diag\CacheTracker::add(0, "", $basedir, $initdir, "", "C");
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/output.php">CPageCache::Output</a></li> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/initcache.php
	 * @author Bitrix
	 */
	public function InitCache($TTL, $uniq_str, $initdir = false, $basedir = "cache")
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

//		$GLOBALS["CACHE_STAT_BYTES"] += $this->_cache->read;
		if (\Bitrix\Main\Data\Cache::getShowCacheStat())
		{
			$read = 0;
			$path = '';
			if ($this->_cache instanceof \Bitrix\Main\Data\ICacheEngineStat)
			{
				$read = $this->_cache->getReadBytes();
				$path = $this->_cache->getCachePath();
			}
			elseif ($this->_cache instanceof \ICacheBackend)
			{
				$read = $this->_cache->read;
				$path = $this->_cache->path;
			}

			\Bitrix\Main\Diag\CacheTracker::addCacheStatBytes($read);
			\Bitrix\Main\Diag\CacheTracker::add($read, $path, $this->basedir, $this->initdir, $this->filename, "R");
		}
		return true;
	}

	
	/**
	 * <p>Выводит содержимое кеша. HTML-содержимое кэша доступно, только если файл кеша существует и предварительно был вызван метод <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/initcache.php">CPageCache::InitCache</a> или <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/startdatacache.php">CPageCache::StartDataCache</a>.</p>
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/initcache.php">CPageCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/startdatacache.php">CPageCache::StartDataCache</a></li>
	 * </ul>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/output.php
	 * @author Bitrix
	 */
	public function Output()
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/enddatacache.php">CPageCache::EndDataCache</a> </li>
	 * <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/navstringforcache.php">CDBResult::NavStringForCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/startdatacache.php
	 * @author Bitrix
	 */
	public function StartDataCache($TTL, $uniq_str=false, $initdir=false, $basedir = "cache")
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

	public function AbortDataCache()
	{
		if(!$this->bStarted)
			return;
		$this->bStarted = false;

		ob_end_flush();
	}

	
	/**
	 * <p>Выводит <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/startdatacache.php">буферизированный HTML</a> и сохраняет его на диске в файл кеша.</p>
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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/startdatacache.php">CPageCache::StartDataCache</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/enddatacache.php
	 * @author Bitrix
	 */
	public function EndDataCache()
	{
		if(!$this->bStarted)
			return;
		$this->bStarted = false;

		$arAllVars = ob_get_contents();

		$this->_cache->write($arAllVars, $this->basedir, $this->initdir, $this->filename, $this->TTL);

		if (\Bitrix\Main\Data\Cache::getShowCacheStat())
		{
			$written = 0;
			$path = '';
			if ($this->_cache instanceof \Bitrix\Main\Data\ICacheEngineStat)
			{
				$written = $this->_cache->getWrittenBytes();
				$path = $this->_cache->getCachePath();
			}
			elseif ($this->_cache instanceof \ICacheBackend)
			{
				$written = $this->_cache->written;
				$path = $this->_cache->path;
			}
			\Bitrix\Main\Diag\CacheTracker::addCacheStatBytes($written);
			\Bitrix\Main\Diag\CacheTracker::add($written, $path, $this->basedir, $this->initdir, $this->filename, "W");
		}

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
	 * href="http://dev.1c-bitrix.ru/api_help/main/reference/cpagecache/initcache.php">CPageCache::InitCache</a></li> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/main/functions/other/bxclearcache.php">BXClearCache</a></li> </ul><a
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
	/**
	 * @var Bitrix\Main\Data\ManagedCache
	 */
	private $manager;

	public function __construct()
	{
		$this->manager = \Bitrix\Main\Application::getInstance()->getManagedCache();
	}

	// Tries to read cached variable value from the file
	// Returns true on success
	// overwise returns false
	public function Read($ttl, $uniqid, $table_id=false)
	{
		return $this->manager->read($ttl, $uniqid, $table_id);
	}

	// This method is used to read the variable value
	// from the cache after successfull Read
	public function Get($uniqid)
	{
		return $this->manager->get($uniqid);
	}

	// Sets new value to the variable
	public function Set($uniqid, $val)
	{
		$this->manager->set($uniqid, $val);
	}

	public function SetImmediate($uniqid, $val)
	{
		$this->manager->setImmediate($uniqid, $val);
	}

	// Marks cache entry as invalid
	public function Clean($uniqid, $table_id=false)
	{
		$this->manager->clean($uniqid, $table_id);
	}

	// Marks cache entries associated with the table as invalid
	public function CleanDir($table_id)
	{
		$this->manager->cleanDir($table_id);
	}

	// Clears all managed_cache
	public function CleanAll()
	{
		$this->manager->cleanAll();
	}

	// Use it to flush cache to the files.
	// Caution: only at the end of all operations!
	static public function _Finalize()
	{
		\Bitrix\Main\Data\ManagedCache::finalize();
	}

	/*Components managed(tagged) cache*/

	public function GetCompCachePath($relativePath)
	{
		return $this->manager->getCompCachePath($relativePath);
	}

	public function StartTagCache($relativePath)
	{
		$this->manager->startTagCache($relativePath);
	}

	public function EndTagCache()
	{
		$this->manager->endTagCache();
	}

	public function AbortTagCache()
	{
		$this->manager->abortTagCache();
	}

	public function RegisterTag($tag)
	{
		$this->manager->registerTag($tag);
	}

	public function ClearByTag($tag)
	{
		$this->manager->clearByTag($tag);
	}
}

global $CACHE_MANAGER;
$CACHE_MANAGER = new CCacheManager;

$GLOBALS["CACHE_STAT_BYTES"] = 0;
//magic parameters: show cache usage statistics
$show_cache_stat = "";
if(array_key_exists("show_cache_stat", $_GET))
{
	$show_cache_stat = (strtoupper($_GET["show_cache_stat"]) == "Y"? "Y":"");
	setcookie("show_cache_stat", $show_cache_stat, false, "/");
}
elseif(array_key_exists("show_cache_stat", $_COOKIE))
{
	$show_cache_stat = $_COOKIE["show_cache_stat"];
}
CCacheDebug::$ShowCacheStat = ($show_cache_stat === "Y");

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

	public function __construct($entity, $length = 0, $ttl = 0)
	{
		$this->entity = $entity;

		if($length > 0)
			$this->len = intval($length);

		if($ttl > 0)
			$this->ttl = intval($ttl);
	}

	public function SetLength($length)
	{
		if($length > 0)
			$this->len = intval($length);

		while(count($this->values) > $this->len)
		{
			$this->cleanSet = false;
			array_shift($this->values);
		}
	}

	public function SetTTL($ttl)
	{
		if($ttl > 0)
			$this->ttl = intval($ttl);
	}

	public function Load()
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

	public function DeleteEntry($id)
	{
		if(array_key_exists($id, $this->values))
		{
			unset($this->values[$id]);
			$this->cleanSet = false;
		}
	}

	public function Clean()
	{
		global $DB;

		$objCache = new CPHPCache;
		$objCache->Clean($this->entity, $DB->type."/".$this->entity, "stack_cache");

		$this->values = array();
		$this->cleanGet = true;
		$this->cleanSet = true;
	}

	public function Get($id)
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

	public function Set($id, $value)
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

	public function Save()
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

	public function SetLength($entity, $length)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if(isset($this->cache[$entity]) && is_object($this->cache[$entity]))
			$this->cache[$entity]->SetLength($length);
		else
			$this->cacheLen[$entity] = $length;
	}

	public function SetTTL($entity, $ttl)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if(isset($this->cache[$entity]) && is_object($this->cache[$entity]))
			$this->cache[$entity]->SetTTL($ttl);
		else
			$this->cacheTTL[$entity] = $ttl;
	}

	public function Init($entity, $length = 0, $ttl = 0)
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

	public function Load($entity)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if (!array_key_exists($entity, $this->cache))
			$this->Init($entity);

		$this->cache[$entity]->Load();
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public function Clear($entity, $id = False)
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
	public function CleanAll()
	{
		$this->cache = array();

		$objCache = new CPHPCache;
		$objCache->CleanDir(false, "stack_cache");
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public function Exist($entity, $id)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return False;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		return array_key_exists($id, $this->cache[$entity]->values);
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public function Get($entity, $id)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return False;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		return $this->cache[$entity]->Get($id);
	}

	//NO ONE SHOULD NEVER EVER USE INTEGER $id HERE
	public function Set($entity, $id, $value)
	{
		if (defined("BITRIX_SKIP_STACK_CACHE") && BITRIX_SKIP_STACK_CACHE)
			return;

		if (!array_key_exists($entity, $this->cache))
			$this->Load($entity);

		$this->cache[$entity]->Set($id, $value);
	}

	public function Save($entity)
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
