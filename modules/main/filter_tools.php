<?
IncludeModuleLangFile(__FILE__);

/*********************************************************************
							Фильтр
*********************************************************************/


/**
 * <p>Проверяет две даты на корректность и сравнивает их между собой. Как правило функция используется в фильтрах для проверки корректности введенного периода времени.</p>
 *
 *
 * @param string $date1  Первая дата интервала ("c").
 *
 * @param string $date2  Вторая дата интервала ("по").
 *
 * @param string &$is_date1_wrong  Данный параметр является ссылкой на исходную переменную. Если в
 * нем будет возвращено "Y", то дата заданная в параметре <i>date1</i>
 * некорректна для формата даты текущего сайта (языка).
 *
 * @param string &$is_date2_wrong  Данный параметр является ссылкой на исходную переменную. Если в
 * нем будет возвращено "Y", то дата заданная в параметре <i>date2</i>
 * некорректна для формата даты текущего сайта (языка).
 *
 * @param string &$is_date2_less_date1  Данный параметр является ссылкой на исходную переменную. Если в
 * нем будет возвращено "Y", то дата заданная в параметре <i>date1</i>
 * больше даты заданной в параметре <i>date2</i>, что неправильно если
 * задается период времени.
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * <b>CheckFilterDates</b>("10.01.2003", "15.02.2004", $date1_wrong, $date2_wrong, $date2_less);
 * if ($date1_wrong=="Y") echo "Неверный формат первой даты!";
 * if ($date2_wrong=="Y") echo "Неверный формат второй даты!";
 * if ($date2_less=="Y") echo "Первая дата больше второй!";
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/isdate.php">CDataBase::IsDate</a> </li>
 * <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdatabase/comparedates.php">CDataBase::CompareDates</a>
 * </li> </ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/checkfilterdates.php
 * @author Bitrix
 */
function CheckFilterDates($date1, $date2, &$date1_wrong, &$date2_wrong, &$date2_less_date1)
{
	global $DB;
	$date1 = trim($date1);
	$date2 = trim($date2);
	$date1_wrong = "N";
	$date2_wrong = "N";
	$date2_less_date1 = "N";
	if (strlen($date1)>0 && !CheckDateTime($date1)) $date1_wrong = "Y";
	if (strlen($date2)>0 && !CheckDateTime($date2)) $date2_wrong = "Y";
	if ($date1_wrong!="Y" && $date2_wrong!="Y" && strlen($date1)>0 && strlen($date2)>0 && $DB->CompareDates($date2,$date1)<0) $date2_less_date1="Y";
}


/**
 * <p>Инициализирует, либо запоминает переменные фильтра в сессии.</p> <p class="note"><b>Примечание</b>. Функция работает с переменными из глобальной области видимости, это необходимо учитывать при <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2818" >создании основных файлов компонентов</a>.</p>
 *
 *
 * @param array $vars  Массив имен переменных фильтра.
 *
 * @param array $stringid  Идентификатор фильтра. Строка идентифицирующая данный фильтр в
 * сессионном массиве: $_SESSION["SESS_ADMIN"][<i>id</i>]
 *
 * @param string $action = "set" Что необходимо сделать: запомнить значения или получить значения
 * фильтра. Если значение равно "set", то значения переменных имена
 * которых были переданы в параметре <i>vars</i> будут запомнены в
 * сессионном массиве $_SESSION["SESS_ADMIN"][<i>id</i>]. В противном случае эти
 * переменный будут инициализированы значениями хранящимися в
 * сессионном массиве $_SESSION["SESS_ADMIN"][<i>id</i>].<br>Параметр
 * необязательный. По умолчанию - "set".
 *
 * @param bool $session = true Использовать ли сессию. Если значение данного параметра равно
 * "true", то значения фильтра будут запоминаться в сессионном массиве
 * $_SESSION["SESS_ADMIN"][<i>id</i>].
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * $FilterArr = Array(
 *     "find_id",
 *     "find_id_exact_match",
 *     );
 * 
 * // если нажата кнопка "Установить фильтр" то
 * if (strlen($set_filter)&gt;0) 
 * {
 *     // запоминаем значения фильтра в сессии
 *     <b>InitFilterEx</b>($FilterArr,"ADV_BANNER_LIST","set"); 
 * }
 * else 
 * {
 *     // инициализируем значения фильтра из сессии
 *     <b>InitFilterEx</b>($FilterArr,"ADV_BANNER_LIST","get");
 * }
 * 
 * // если была нажата кнопка "Сбросить фильтр"
 * if (strlen($del_filter)&gt;0) DelFilterEx($FilterArr,"ADV_BANNER_LIST");
 * 
 * $arFilter = Array(
 *     "ID"                    =&gt; $find_id,
 *     "ID_EXACT_MATCH"        =&gt; $find_id_exact_match,
 *     );
 * $rsBanners = CAdvBanner::GetList($by, $order, $arFilter, $is_filtered);
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/delfilterex.php">DelFilterEx</a> </li></ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/initfilterex.php
 * @author Bitrix
 */
function InitFilterEx($arName, $varName, $action="set", $session=true, $FilterLogic="FILTER_logic")
{

	if ($session && is_array($_SESSION["SESS_ADMIN"][$varName]))
		$FILTER = $_SESSION["SESS_ADMIN"][$varName];
	else
		$FILTER = Array();

	global $$FilterLogic;
	if ($action=="set")
		$FILTER[$FilterLogic] = $$FilterLogic;
	else
		$$FilterLogic = $FILTER[$FilterLogic];

	for($i=0, $n=count($arName); $i < $n; $i++)
	{
		$name = $arName[$i];
		$period = $arName[$i]."_FILTER_PERIOD";
		$direction = $arName[$i]."_FILTER_DIRECTION";
		$bdays = $arName[$i]."_DAYS_TO_BACK";

		global $$name, $$direction, $$period, $$bdays;

		if ($action=="set")
		{
			$FILTER[$name] = $$name;
			if(isset($$period) || isset($FILTER[$period]))
				$FILTER[$period] = $$period;

			if(isset($$direction) || isset($FILTER[$direction]))
				$FILTER[$direction] = $$direction;

			if(isset($$bdays) || isset($FILTER[$bdays]))
			{
				$FILTER[$bdays] = $$bdays;
				if (strlen($$bdays)>0 && $$bdays!="NOT_REF")
					$$name = GetTime(time()-86400*intval($FILTER[$bdays]));
			}

		}
		else
		{
			$$name = isset($FILTER[$name])? $FILTER[$name]: null;
			if(isset($$period) || isset($FILTER[$period]))
				$$period = $FILTER[$period];

			if(isset($$direction) || isset($FILTER[$direction]))
				$$direction = $FILTER[$direction];

			if (isset($FILTER[$bdays]) && strlen($FILTER[$bdays])>0 && $FILTER[$bdays]!="NOT_REF")
			{
				$$bdays = $FILTER[$bdays];
				$$name = GetTime(time()-86400*intval($FILTER[$bdays]));
			}
		}
	}

	if($session)
	{
		if(!is_array($_SESSION["SESS_ADMIN"]))
			$_SESSION["SESS_ADMIN"] = array();
		$_SESSION["SESS_ADMIN"][$varName] = $FILTER;
	}
}


/**
 * <p>Очищает переменные, содержащие значения фильтра, и очищает соответствующие сессионные переменные.</p> <p class="note"><b>Примечание</b>. Функция работает с переменными из глобальной области видимости, это необходимо учитывать при создании <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2818" >основных файлов компонентов</a>.</p>
 *
 *
 * @param array $vars  Массив имен переменных фильтра.
 *
 * @param array $stringid  Идентификатор фильтра. Строка идентифицирующая данный фильтр в
 * сессионном массиве: $_SESSION["SESS_ADMIN"][<i>id</i>]
 *
 * @param bool $session = true Использовать ли сессию. Если значение данного параметра равно
 * "true", то значения фильтра будут также очищены из сессионного
 * массива $_SESSION["SESS_ADMIN"][<i>id</i>].
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * $FilterArr = Array(
 *     "find_id",
 *     "find_id_exact_match",
 *     );
 * 
 * // если нажата кнопка "Установить фильтр" то
 * if (strlen($set_filter)&gt;0) 
 * {
 *     // запоминаем значения фильтра в сессии
 *     InitFilterEx($FilterArr,"ADV_BANNER_LIST","set"); 
 * }
 * else 
 * {
 *     // инициализируем значения фильтра из сессии
 *     InitFilterEx($FilterArr,"ADV_BANNER_LIST","get");
 * }
 * 
 * // если была нажата кнопка "Сбросить фильтр"
 * if (strlen($del_filter)&gt;0) <b>DelFilterEx</b>($FilterArr,"ADV_BANNER_LIST");
 * 
 * $arFilter = Array(
 *     "ID"                    =&gt; $find_id,
 *     "ID_EXACT_MATCH"        =&gt; $find_id_exact_match,
 *     );
 * $rsBanners = CAdvBanner::GetList($by, $order, $arFilter, $is_filtered);
 * ?&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/initfilterex.php">InitFilterEx</a> </li></ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/delfilterex.php
 * @author Bitrix
 */
function DelFilterEx($arName, $varName, $session=true, $FilterLogic="FILTER_logic")
{
	global $$FilterLogic;

	if ($session)
		unset($_SESSION["SESS_ADMIN"][$varName]);

	foreach ($arName as $name)
	{
		$period = $name."_FILTER_PERIOD";
		$direction = $name."_FILTER_DIRECTION";
		$bdays = $name."_DAYS_TO_BACK";

		global $$name, $$period, $$direction, $$bdays;

		$$name = "";
		$$period ="";
		$$direction = "";
		$$bdays = "";
	}

	$$FilterLogic = "and";
}

function InitFilter($arName)
{
	$md5Path = md5(GetPagePath());
	$FILTER = $_SESSION["SESS_ADMIN"][$md5Path];

	foreach ($arName as $name)
	{
		global $$name;

		if(isset($$name))
			$FILTER[$name] = $$name;
		else
			$$name = $FILTER[$name];
	}

	$_SESSION["SESS_ADMIN"][$md5Path] = $FILTER;
}

function DelFilter($arName)
{
	$md5Path = md5(GetPagePath());
	unset($_SESSION["SESS_ADMIN"][$md5Path]);

	foreach ($arName as $name)
	{
		global $$name;
		$$name = "";
	}
}


/**
 * <p>Возвращает набор HTML тэгов типа hidden из набора переменных, имена которых передаются в массиве, либо указан префикс имен этих переменных.</p>
 *
 *
 * @param mixed $var  Имена переменных. В данном параметре можно передать либо массив,
 * либо префикс имен этих переменных.
 *
 * @param array $button = array("filter"=>"Y" В данном параметре можно передать массив, представляющий из себя
 * имя и значение произвольных переменных, которые будут добавлены
 * в результат. Структура данного массива: <pre bgcolor="#323232" style="padding:5px;">array("ИМЯ_ПЕРЕМЕННОЙ" =&gt;
 * "ЗНАЧЕНИЕ_ПЕРЕМЕННОЙ")</pre> 	Как правило данный параметр используют
 * для передачи имени и значения кнопки "Установить фильтр".<br>
 * 	Параметр необязательный. По умолчанию - array("filter" =&gt; "Y", "set_filter" =&gt;
 * "Y").
 *
 * @param mixed $set_filter  
 *
 * @param set_filte $set_filteY  
 *
 * @return string 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;form method="GET"&gt;
 *     &lt;?
 *     // добавим набор тэгов hidden имена которых будут начинаться с префикса "filter_"
 *     // и значениями равными значениям соответствующих переменных
 *     echo <b>GetFilterHiddens</b>("filter_");
 *     ?&gt;
 *     &lt;input type="hidden" name="lang" value="&lt;?echo LANGUAGE_ID?&gt;"&gt;
 *     &lt;input type="submit" name="Add" value="Добавить новую запись&amp;gt;"&gt;
 * &lt;/form&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/getfilterparams.php">GetFilterParams</a>
 * </li></ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/getfilterhiddens.php
 * @author Bitrix
 */
function GetFilterHiddens($var = "filter_", $button = array("filter" => "Y", "set_filter" => "Y"))
{
	// если поступил не массив имен переменных то
	if (!is_array($var))
	{
		// получим имена переменных фильтра по префиксу
		$arKeys = @array_merge(array_keys($_GET), array_keys($_POST));
		if (is_array($arKeys) && count($arKeys)>0)
		{
			$len = strlen($var);
			foreach (array_unique($arKeys) as $key)
				if (substr($key, 0, $len) == $var)
					$arrVars[] = $key;
		}
	}
	else $arrVars = $var;

	// если получили массив переменных фильтра то
	if (is_array($arrVars) && count($arrVars)>0)
	{
		// соберем строку из URL параметров
		foreach ($arrVars as $var_name)
		{
			global $$var_name;
			$value = $$var_name;
			if (is_array($value))
			{
				if (count($value)>0)
				{
					reset($value);
					foreach($value as $v)
					{
						$res .= '<input type="hidden" name="'.htmlspecialcharsbx($var_name).'[]" value="'.htmlspecialcharsbx($v).'">';
					}
				}
			}
			elseif (strlen($value)>0 && $value!="NOT_REF")
			{
				$res .= '<input type="hidden" name="'.htmlspecialcharsbx($var_name).'" value="'.htmlspecialcharsbx($value).'">';
			}
		}
	}

	if(is_array($button))
	{
		reset($button); // php bug
		while(list($key, $value) = each($button))
			$res.='<input type="hidden" name="'.htmlspecialcharsbx($key).'" value="'.htmlspecialcharsbx($value).'">';
	}
	else
		$res .= $button;

	return $res;
}


/**
 * <p>Возвращает строку состоящюю из параметров для URL'а из входящего массива переменных, либо переменных имена которых начинаются с указанного префикса.</p>
 *
 *
 * @param mixed $var  Имена переменных. В данном параметре можно передать либо массив,
 * либо префикс имен этих переменных.
 *
 * @param bool $DoHtmlEncode = true Если значение равно "true", то результат будет приведен в
 * HTML-безопасный вид.
 *
 * @param array $button = array("filter"=>"Y" В данном параметре можно передать массив, представляющий из себя
 * имя и значение произвольных переменных, которые будут добавлены
 * в результат. Структура данного массива: <pre bgcolor="#323232" style="padding:5px;">array("ИМЯ_ПЕРЕМЕННОЙ" =&gt;
 * "ЗНАЧЕНИЕ_ПЕРЕМЕННОЙ")</pre> 	Как правило данный параметр используют
 * для передачи имени и значения кнопки "Установить фильтр".<br>
 * 	Параметр необязательный. По умолчанию - array("filter" =&gt; "Y", "set_filter" =&gt;
 * "Y").
 *
 * @param mixed $set_filter  
 *
 * @param set_filte $set_filteY  
 *
 * @return string 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // Данный код строит ссылку, при переходе по которой пользователь 
 * // попадет на страницу adv.php с теми же переменными, 
 * // имена которых имеют префикс - "filter_", 
 * // с какими он попал на текущую страницу
 * ?&gt;
 * &lt;a href="adv.php?id=&lt;?echo $f_ID?&gt;&amp;&lt;?echo <b>GetFilterParams</b>("filter_");?&gt;"&gt;Изменить&lt;/a&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/getfilterhiddens.php">GetFilterHiddens</a>
 * </li></ul><a name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/getfilterparams.php
 * @author Bitrix
 */
function GetFilterParams($var="filter_", $bDoHtmlEncode=true, $button = array("filter" => "Y", "set_filter" => "Y"))
{
	$arrVars = array(); // массив имен переменных фильтра
	$res=""; // результирующая строка

	// если поступил не массив имен переменных то
	if(!is_array($var))
	{
		// получим имена переменных фильтра по префиксу
		$arKeys = @array_merge(array_keys($_GET), array_keys($_POST));
		if(is_array($arKeys) && count($arKeys)>0)
		{
			$len = strlen($var);
			foreach (array_unique($arKeys) as $key)
				if (substr($key, 0, $len) == $var)
					$arrVars[] = $key;
		}
	}
	else
		$arrVars = $var;

	// если получили массив переменных фильтра то
	if(is_array($arrVars) && count($arrVars)>0)
	{
		// соберем строку из URL параметров
		foreach($arrVars as $var_name)
		{
			global $$var_name;
			$value = $$var_name;
			if(is_array($value))
			{
				if(count($value)>0)
				{
					reset($value);
					foreach($value as $v)
						$res .= "&".urlencode($var_name)."[]=".urlencode($v);
				}
			}
			elseif(strlen($value)>0 && $value!="NOT_REF")
			{
				$res .= "&".urlencode($var_name)."=".urlencode($value);
			}
		}
	}

	if(is_array($button))
	{
		reset($button); // php bug
		while(list($key, $value) = each($button))
			$res .= "&".$key."=".urlencode($value);
	}
	else
		$res .= $button;


	$tmp_phpbug = ($bDoHtmlEncode) ? htmlspecialcharsbx($res) : $res;

	return $tmp_phpbug;
	//return ($bDoHtmlEncode) ? htmlspecialcharsbx($res) : $res;
}

// устаревшая функция, оставлена для совместимости
function GetFilterStr($arr, $button="set_filter")
{
	foreach ($arr as $var)
	{
		global $$var;
		$value = $$var;
		if (is_array($value))
		{
			if (count($value)>0)
			{
				foreach($value as $v)
				{
					$str .= "&".urlencode($var)."[]=".urlencode($v);
				}
			}
		}
		elseif (strlen($value)>0 && $value!="NOT_REF")
		{
			$str .= "&".urlencode($var)."=".urlencode($value);
		}
	}
	return $str."&".$button."=Y";
}

function ShowExactMatchCheckbox($name, $title=false)
{
	$var = $name."_exact_match";
	global $$var;
	if ($title===false) $title=GetMessage("MAIN_EXACT_MATCH");
	return '<input type="hidden" name="'.$name.'_exact_match" value="N">'.InputType("checkbox", $name."_exact_match", "Y", $$var, false, "", "title='".$title."'");
}

function GetUrlFromArray($arr)
{
	if(!is_array($arr))
		return "";
	$str = "";
	while (list($key,$value) = each($arr))
	{
		if (is_array($value) && count($value)>0)
		{
			foreach ($value as $a) $str .= "&".$key.urlencode("[]")."=".urlencode($a);
		}
		elseif(strlen($value)>0 && $value!="NOT_REF") $str .= "&".$key."=".urlencode($value);
	}
	return $str;
}

function ShowAddFavorite($filterName=false, $btnName="set_filter", $module="statistic", $alt=false)
{
	global $QUERY_STRING, $SCRIPT_NAME, $sFilterID;
	if ($alt===false)
		$alt=GetMessage("MAIN_ADD_TO_FAVORITES");
	if ($filterName===false)
		$filterName = $sFilterID;
	$url = urlencode($SCRIPT_NAME."?".$QUERY_STRING. GetUrlFromArray($_SESSION["SESS_ADMIN"][$filterName])."&".$btnName."=Y");
	$str = "<a target='_blank' href='".BX_ROOT."/admin/favorite_edit.php?lang=".LANG."&module=$module&url=$url'><img alt='".$alt."' src='".BX_ROOT."/images/main/add_favorite.gif' width='16' height='16' border=0></a>";
	echo $str;
}

function IsFiltered($strSqlSearch)
{
	return (strlen($strSqlSearch)>0 && $strSqlSearch!="(1=1)" && $strSqlSearch!="(1=2)");
}

function ResetFilterLogic($FilterLogic="FILTER_logic")
{
	$var = $FilterLogic."_reset";
	global $$var;
	$$var = "Y";
}

function ShowFilterLogicHelp()
{
	global $LogicHelp;
	$str = "";
	if(LANGUAGE_ID == "ru")
		$help_link = "http://dev.1c-bitrix.ru/user_help/help/filter.php";
	else
		$help_link = "http://www.bitrixsoft.com/help/index.html?page=".urlencode("source/main/help/en/filter.php.html");
	if ($LogicHelp != "Y")
	{
		$str = "<script type=\"text/javascript\">
		function LogicHelp() { window.open('".$help_link."', '','scrollbars=yes,resizable=yes,width=780,height=500,top='+Math.floor((screen.height - 500)/2-14)+',left='+Math.floor((screen.width - 780)/2-5)); }
		</script>";
	}
	$str .= "<a title='".GetMessage("FILTER_LOGIC_HELP")."' class='adm-input-help-icon' href='javascript:LogicHelp()'></a>";
	$LogicHelp = "Y";
	return $str;
}

function ShowLogicRadioBtn($FilterLogic="FILTER_logic")
{
	global $$FilterLogic;
	$s_and = "checked";
	if ($$FilterLogic=="or")
	{
		$s_or = "checked";
		$s_and = "";
	}
	$str = "<tr><td>".GetMessage("FILTER_LOGIC")."</td><td><input type='radio' name='$FilterLogic' value='and' ".$s_and.">".GetMessage("AND")."&nbsp;<input type='radio' name='$FilterLogic' value='or' ".$s_or.">".GetMessage("OR")."</td></tr>";
	return $str;
}

function GetFilterQuery($field, $val, $procent="Y", $ex_sep=array(), $clob="N", $div_fields="Y", $clob_upper="N")
{
	global $strError;
	$f = new CFilterQuery("and", "yes", $procent, $ex_sep, $clob, $div_fields, $clob_upper);
	$query = $f->GetQueryString($field, $val);
	$error = $f->error;
	if (strlen(trim($error))>0)
	{
		$strError .= $error."<br>";
		$query = "0";
	}
	return $query;
}

function GetFilterSqlSearch($arSqlSearch=array(), $FilterLogic="FILTER_logic")
{
	$var = $FilterLogic."_reset";
	global $strError, $$FilterLogic, $$var;
	$ResetFilterLogic = $$var;
	$$FilterLogic = ($$FilterLogic=="or") ? "or" : "and";
	if($ResetFilterLogic=="Y" && $$FilterLogic=="or")
	{
		$$FilterLogic = "and";
		$strError .= GetMessage("FILTER_ERROR_LOGIC")."<br>";
	}
	if($$FilterLogic=="or")
		$strSqlSearch = "1=2";
	else
		$strSqlSearch = "1=1";
	if (is_array($arSqlSearch) && count($arSqlSearch)>0)
	{
		foreach ($arSqlSearch as $condition)
		{
			if (strlen($condition)>0 && $condition!="0")
			{
				$strSqlSearch .= "
					".strtoupper($$FilterLogic)."
					(
						".$condition."
					)
					";
			}
		}
	}
	return "($strSqlSearch)";
}

$bFilterScriptShown = false;
$sFilterID = "";
function BeginFilter($sID, $bFilterSet, $bShowStatus=true)
{
	global $bFilterScriptShown, $sFilterID;
	$sFilterID = $sID;
	$s = "";
	if(!$bFilterScriptShown)
	{
		$s .= '
<script type="text/javascript">
<!--
function showfilter(id)
{
	var div = document.getElementById("flt_div_"+id);
	var tbl = document.getElementById("flt_table_"+id);
	var head = document.getElementById("flt_head_"+id);
	var flts = "", curval = "", oldval="";
	var aCookie = document.cookie.split("; ");
	//document.cookie = "flts=X; expires=Thu, 31 Dec 1999 23:59:59 GMT; path='.BX_ROOT.'/admin/;";return;
	for (var i=0; i < aCookie.length; i++)
	{
		var aCrumb = aCookie[i].split("=");
		if ("flts" == aCrumb[0])
		{
			if(aCrumb.length>1 && aCrumb[1].length>0)
			{
				var val = aCrumb[1];
				var arFVals = val.split("&");
				for (var j=0; j < arFVals.length; j++)
				{
					val = arFVals[j];
					if(val.length>0)
					{
						val = unescape(val);
						val = val.split("=");
						if(val.length>1 && val[1].length>0)
						{
							if(val[0] == id)
								curval = val[1];
							else
								flts = flts + escape(val[0] + "=" + val[1]) + "&";
						}
					}
				}
			}
		}

		if ("flt_"+id == aCrumb[0])
			oldval = aCrumb[1];
	}

	if(div.style.display!="none")
	{
		if(tbl.offsetWidth > 0)
			head.style.width = tbl.offsetWidth;
		if(oldval!="")
			document.cookie = "flt_"+id+"=X; expires=Fri, 31 Dec 1999 23:59:59 GMT; path='.BX_ROOT.'/admin/;";
		document.cookie = "flts="+flts+escape(id+"=N"+(tbl.offsetWidth))+"; expires=Thu, 31 Dec 2020 23:59:59 GMT; path='.BX_ROOT.'/admin/;";
		hidefilter(id);
	}
	else
	{
		if(oldval!="")
			document.cookie = "flt_"+id+"=X; expires=Fri, 31 Dec 1999 23:59:59 GMT; path='.BX_ROOT.'/admin/;";
		document.cookie = "flts="+flts+escape(id+"=Y)")+"; expires=Thu, 31 Dec 2020 23:59:59 GMT; path='.BX_ROOT.'/admin/;";
		document.getElementById("flt_link_show_"+id).style.display = "none";
		document.getElementById("flt_link_hide_"+id).style.display = "inline";
		document.getElementById("flt_image_"+id).src = "'.BX_ROOT.'/images/admin/line_up.gif";
		document.getElementById("flt_image_"+id).alt = "'.GetMessage("admin_filter_hide").'";
		div.style.display = "block";
		if(tbl.clientWidth > 0)
			head.style.width = tbl.clientWidth+2;
	}
}
function hidefilter(id)
{
	document.getElementById("flt_link_show_"+id).style.display = "inline";
	document.getElementById("flt_link_hide_"+id).style.display = "none";
	document.getElementById("flt_image_"+id).src = "'.BX_ROOT.'/images/admin/line_down.gif";
	document.getElementById("flt_image_"+id).alt = "'.GetMessage("admin_filter_show").'";
	document.getElementById("flt_div_"+id).style.display = "none";
}
tmpImage = new Image();
tmpImage.src = "'.BX_ROOT.'/images/admin/line_down.gif";
tmpImage.src = "'.BX_ROOT.'/images/admin/line_up.gif";
//-->
</script>
';
		$bFilterScriptShown = true;
	}

	parse_str($_COOKIE["flts"], $arFlts);
	if(is_set($arFlts, $sID))
	{
		$fltval = $arFlts[$sID];
		if(is_set($_COOKIE, "flt_".$sID))
			unset($_COOKIE["flt_".$sID]);
	}
	else
		$fltval = $_COOKIE["flt_".$sID];

	$s .= '
<table border="0" cellspacing="0" cellpadding="0" width="'.($fltval[0]=="N"? intval(substr($fltval, 1)):'').'"><tr><td>
<table border="0" cellspacing="0" cellpadding="0" width="100%" id="flt_head_'.$sID.'">
<tr>
	<td class="tablefilterhead">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
';
	if($bShowStatus)
	{
		$s .= '
	<td width="0%"><img src="'.BX_ROOT.'/images/admin/'.($bFilterSet? "green.gif":"grey.gif").'" alt="" width="16" height="16" border="0" hspace="1" vspace="1"></td>
	<td width="0%"><font class="tableheadtext">&nbsp;</font></td>
	<td width="100%" nowrap><font class="tableheadtext">'.($bFilterSet? GetMessage("admin_filter_filter_set"):GetMessage("admin_filter_filter_not_set")).'</font></td>
	<td width="0%"><font class="tableheadtext">&nbsp;&nbsp;&nbsp;</font></td>
';
	}
	else
	{
		$s .= '
	<td width="100%"><img src="/bitrix/images/1.gif" width="1" height="18" alt=""></td>
';
	}
	$s .= '
	<td width="0%"><font class="tableheadtext"><div id="flt_link_hide_'.$sID.'" style="display:inline;"><a href="javascript:showfilter(\''.$sID.'\');" title="'.GetMessage("admin_filter_hide").'">'.GetMessage("admin_filter_hide2").'</a></div><div id="flt_link_show_'.$sID.'" style="display:none;"><a href="javascript:showfilter(\''.$sID.'\');" title="'.GetMessage("admin_filter_show").'">'.GetMessage("admin_filter_show2").'</a></div></font></td>
	<td width="0%"><font class="tableheadtext">&nbsp;</font><a href="javascript:showfilter(\''.$sID.'\');"><img id="flt_image_'.$sID.'" src="'.BX_ROOT.'/images/admin/line_up.gif" alt="'.GetMessage("admin_filter_hide").'" width="30" height="11" border="0" hspace="3"></a></td>
</tr>
</table>
	</td>
</tr>
</table>
<div id="flt_div_'.$sID.'">
<table border="0" cellspacing="1" cellpadding="0" class="filter" id="flt_table_'.$sID.'" width="100%">
';
	return $s;
}

function EndFilter($sID="")
{
	global $sFilterID;
	if($sID == "")
		$sID = $sFilterID;
	$s = '
</table>
</div>
</td></tr></table>

';
	parse_str($_COOKIE["flts"], $arFlts);
	if(is_set($arFlts, $sID))
		$fltval = $arFlts[$sID];
	else
		$fltval = $_COOKIE["flt_".$sID];

	if($fltval[0]<>"Y")
		$s .= '<script type="text/javascript">hidefilter(\''.CUtil::JSEscape($sID).'\');</script>'."\n";
	return $s;
}

function BeginNote($sParams="")
{
	return '
<div class="adm-info-message-wrap" '.$sParams.'>
	<div class="adm-info-message">
';
}
function EndNote()
{
	return '
	</div>
</div>
';
}
function ShowSubMenu($aMenu)
{
	$s = '
<table cellspacing=0 cellpadding=0 border=0 >
<tr>
<td width="6"><img height=6 alt="" src="/bitrix/images/admin/mn_ltc.gif" width=6></td>
<td background=/bitrix/images/admin/mn_tline.gif><img height=1 alt="" src="/bitrix/images/1.gif" width=1></td>
<td width="6"><img height=6 alt="" src="/bitrix/images/admin/mn_rtc.gif" width=6></td></tr>
<tr>
<td width="6" background=/bitrix/images/admin/mn_lline.gif><img height=1 alt="" src="/bitrix/images/1.gif" width=1></td>
<td valign=top class="submenutable">

<table border="0" cellspacing="0" cellpadding="0">
<tr valign="top">
	<td><font class="submenutext">
';
foreach($aMenu as $menu)
{
	if($menu["SEPARATOR"]<>"")
	{
		$s .= '
</font></td>
<td width=13 background="/bitrix/images/admin/mn_delim.gif"><img height=1 alt="" src="/bitrix/images/1.gif" width=13></td>
<td><font class="submenutext">
';
		continue;
	}
	$s .= '
<table border="0" cellspacing="0" cellpadding="0">
<tr valign="top">
	<td width="7"><img src="/bitrix/images/admin/arr_right'.($menu["WARNING"]<>""? "_red":"").'.gif" alt="" width="7" height="7" border="0" vspace="4"></td>
	<td class="submenutext">&nbsp;</td>
	<td><font class="submenutext"><a class="submenutext" title="'.$menu["TITLE"].'" href="'.$menu["LINK"].'" '.$menu["LINK_PARAM"].'>'.$menu["TEXT"].'</a>'.$menu["TEXT_PARAM"].'</font></td>
</tr>
</table>
';
}
$s .= '
</font></td>
</tr>
</table>
<td width="6" background=/bitrix/images/admin/mn_rline.gif><img height=1 alt="" src="/bitrix/images/1.gif" width=1></td></tr>
<tr>
<td width="6"><img height=6 alt="" src="/bitrix/images/admin/mn_lbc.gif" width=6></td>
<td background=/bitrix/images/admin/mn_bline.gif><img height=1 alt="" src="/bitrix/images/1.gif" width=1></td>
<td width="6"><img height=6 alt="" src="/bitrix/images/admin/mn_rbc.gif" width=6></td></tr>
</table>
';
return $s;
}

/*********************************************************************
							Сортировка
*********************************************************************/


/**
 * <p>Инициализирует параметры сортировки хранимые в сессии, если сортировка не была явно задана. Если сортировка задана явно, то функция запоминает параметры сортировки в сессии.</p> <p class="note"><b>Примечание</b>. Функция работает с переменными из глобальной области видимости, это необходимо учитывать при создании <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=2818" >основных файлов компонентов</a>.</p>
 *
 *
 * @param mixed $Path = false Для какой страницы инициализировать сортировку. Значение "false"
 * означает, что необходимо инициализировать сортировку для
 * текущей страницы.<br>Необязательный параметр, по умолчанию равен
 * "false".
 *
 * @param string $by_var = "by" Имя переменной в которой передается идентфикатор поля для
 * сортировки.<br> Необязательный, по умолчанию равен "by".
 *
 * @param string $order_var = "order" Имя переменной, которая содержит направление сортировки: "asc" (по
 * возрастания) или desc (по убыванию). Необязательный параметр, по
 * умолчанию равен "order".
 *
 * @return mixed 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;?
 * // если переменные $by и $order явно заданы, то их значения запоминаются в сессии
 * // иначе они инициализируется значениями хранимыми в сессии
 * <b>InitSorting</b>();
 * $rsUsers = CUser::GetList($by, $order);
 * ?&gt;
 * &lt;table&gt;
 *     &lt;tr&gt; 
 *         &lt;td&gt;ID&lt;br&gt;&lt;?=SortingEx("s_id")?&gt;&lt;/td&gt;
 *         &lt;td&gt;Логин&lt;br&gt;&lt;?=SortingEx("s_name")?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 *     ...
 * &lt;/table&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/sortingex.php">SortingEx</a> </li></ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/initsorting.php
 * @author Bitrix
 */
function InitSorting($Path=false, $sByVar="by", $sOrderVar="order")
{
	global $APPLICATION, $$sByVar, $$sOrderVar;

	if($Path===false)
		$Path = $APPLICATION->GetCurPage();

	$md5Path = md5($Path);

	if (strlen($$sByVar)>0)
		$_SESSION["SESS_SORT_BY"][$md5Path] = $$sByVar;
	else
		$$sByVar = $_SESSION["SESS_SORT_BY"][$md5Path];

	if(strlen($$sOrderVar)>0)
		$_SESSION["SESS_SORT_ORDER"][$md5Path] = $$sOrderVar;
	else
		$$sOrderVar = $_SESSION["SESS_SORT_ORDER"][$md5Path];

	strtolower($$sByVar);
	strtolower($$sOrderVar);
}


/**
 * <p>Возвращает HTML, представляющий из себя "стрелки" сортировки. Функция как правило используется в шапке таблиц.</p>
 *
 *
 * @param mixed $stringby  Значение которое будет передано в переменную <i>by_var</i>. Как правило
 * в данном параметре указывается идентификатор поля для
 * сортировки.
 *
 * @param mixed $Path = false URL страницы вместе со всеми параметрами для которой будет
 * сформирована ссылка на "стрелке".<br>Необязательный параметр,
 * равный по умолчанию - "false" (текущая страница).
 *
 * @param string $by_var = "by" Имя переменной в которую будет передано значение параметра
 * <i>by</i>.<br> Необязательный, по умолчанию равен "by".
 *
 * @param string $order_var = "order" Имя переменной, которая содержит направление сортировки: "asc" (по
 * возрастания) или desc (по убыванию). Необязательный параметр, по
 * умолчанию равен "order".
 *
 * @param string $anchor = "nav_start" Якорь, до которого страница будет прокручена в браузере после
 * установки сортировки. Необязательный параметр, по умолчанию
 * равен "nav_start".
 *
 * @return string 
 *
 * <h4>Example</h4> 
 * <pre bgcolor="#323232" style="padding:5px;">
 * &lt;table&gt;
 *     &lt;tr&gt; 
 *         &lt;td&gt;ID&lt;br&gt;&lt;?=<b>SortingEx</b>("s_id")?&gt;&lt;/td&gt;
 *         &lt;td&gt;Заголовок&lt;br&gt;&lt;?=<b>SortingEx</b>("s_name")?&gt;&lt;/td&gt;
 *     &lt;/tr&gt;
 *     &lt;tr&gt; 
 *         &lt;td&gt;1&lt;/td&gt;
 *         &lt;td&gt;Заголовок элемента&lt;/td&gt;
 *     &lt;/tr&gt;
 * &lt;/table&gt;
 * </pre>
 *
 *
 * <h4>See Also</h4> 
 * <ul><li> <a href="http://dev.1c-bitrix.ru/api_help/main/functions/filter/initsorting.php">InitSorting</a> </li></ul><a
 * name="examples"></a>
 *
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/functions/filter/sortingex.php
 * @author Bitrix
 */
function SortingEx($By, $Path = false, $sByVar="by", $sOrderVar="order", $Anchor="nav_start")
{
	global $APPLICATION;

	$sImgDown = "<img src=\"".BX_ROOT."/images/icons/up.gif\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GetMessage("ASC_ORDER")."\">";
	$sImgUp = "<img src=\"".BX_ROOT."/images/icons/down.gif\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GetMessage("DESC_ORDER")."\">";

	global $$sByVar, $$sOrderVar;
	$by=$$sByVar;
	$order=$$sOrderVar;

	if(strtoupper($By)==strtoupper($by))
	{
		if(strtoupper($order)=="DESC")
			$sImgUp = "<img src=\"".BX_ROOT."/images/icons/down-$$$.gif\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GetMessage("DESC_ORDER")."\">";
		else
			$sImgDown = "<img src=\"".BX_ROOT."/images/icons/up-$$$.gif\" width=\"15\" height=\"15\" border=\"0\" alt=\"".GetMessage("ASC_ORDER")."\">";
	}

	//Если путь не задан, то будем брать текущий со всеми переменными
	if($Path===false)
		$Path = $APPLICATION->GetCurUri();

	//Если нет переменных, то надо добавлять параметры через ?
	$found = strpos($Path, "?");
	if ($found === false) $strAdd2URL = "?";
	else $strAdd2URL = "&";

	$Path = preg_replace("/([?&])".$sByVar."=[^&]*[&]*/i", "\\1", $Path);
	$Path = preg_replace("/([?&])".$sOrderVar."=[^&]*[&]*/i", "\\1", $Path);

	$strTest = substr($Path,strlen($Path)-1);
	if($strTest=="&" OR $strTest == "?")
		$strAdd2URL="";

	return "<nobr><a href=\"".htmlspecialcharsbx($Path.$strAdd2URL.$sByVar."=".$By."&".$sOrderVar."=asc#".$Anchor)."\">".$sImgDown."</a>".
			"<a href=\"".htmlspecialcharsbx($Path.$strAdd2URL.$sByVar."=".$By."&".$sOrderVar."=desc#".$Anchor)."\">".$sImgUp."</a></nobr>";
}?>