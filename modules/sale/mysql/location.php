<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/location.php");

use Bitrix\Sale\Location;


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/index.php
 * @author Bitrix
 * @deprecated
 */
class CSaleLocation extends CAllSaleLocation
{
	
	/**
	* <p>Метод возвращает набор местоположений, удовлетворяющих фильтру arFilter. Возвращаются так же параметры стран и городов местоположений. Набор отсортирован в соответствии с массивом arOrder. Языкозависимые параметры выбираются для языка strLang. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array("SORT"=>"ASC" Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле местоположения, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и т.д.).
	*
	* @param COUNTRY_NAME_LAN $G  Массив, в соответствии с которым фильтруются записи
	* местоположений. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li><b> -
	* значение поля меньше или равно передаваемой в фильтр
	* величины;</b></li> <li><b> - значение поля строго меньше передаваемой в
	* фильтр величины;</b></li> <li> <b>~</b> - значение поля проверяется на
	* соответствие передаваемому в фильтр шаблону;</li> <li> <b>%</b> -
	* значение поля проверяется на соответствие передаваемой в фильтр
	* строке в соответствии с языком запросов.</li> </ul> В качестве
	* "название_поляX" может стоять любое поле местоположения.<br><br>
	* Пример фильтра: <pre class="syntax">array("COUNTRY_ID" =&gt; 15)</pre> Этот фильтр
	* означает "выбрать все записи, в которых значение в поле COUNTRY_ID (код
	* страны) равно 15".<br><br> Значение по умолчанию - пустой массив array() -
	* означает, что результат отфильтрован не будет.
	*
	* @param AS $C  Массив полей, по которым группируются записи местоположений.
	* Массив имеет вид: <pre class="syntax">array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле местоположений. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру.<br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
	*
	* @param CITY_NAME_LAN $G  Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param AS $C  Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @param  $array  
	*
	* @param arFilte $r = array() Код местоположения. </h
	*
	* @param array $arGroupBy = false Код страны.
	*
	* @param array $arNavStartParams = false Код региона.
	*
	* @param array $arSelectFields = array() Код города.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код местоположения.</td>
	* </tr> <tr> <td>COUNTRY_ID</td> <td>Код страны.</td> </tr> <tr> <td>REGION_ID</td> <td>Код
	* региона.</td> </tr> <tr> <td>CITY_ID</td> <td>Код города.</td> </tr> <tr> <td>SORT</td> <td>Индекс
	* сортировки.</td> </tr> <tr> <td>COUNTRY_NAME_ORIG</td> <td>Языконезависимое название
	* страны.</td> </tr> <tr> <td>COUNTRY_SHORT_NAME</td> <td>Языконезависимое короткое
	* название страны.</td> </tr> <tr> <td>REGION_NAME_ORIG</td> <td>Языконезависимое
	* название региона.</td> </tr> <tr> <td>CITY_NAME_ORIG</td> <td>Языконезависимое
	* название города.</td> </tr> <tr> <td>REGION_SHORT_NAME</td> <td>Языконезависимое
	* короткое название региона.</td> </tr> <tr> <td>CITY_SHORT_NAME</td>
	* <td>Языконезависимое короткое название города.</td> </tr> <tr>
	* <td>COUNTRY_LID</td> <td>Код языка для наименования страны.</td> </tr> <tr>
	* <td>COUNTRY_NAME</td> <td>Языкозависимое название страны, если оно
	* установлено. Иначе - языконезависимое название страны.</td> </tr> <tr>
	* <td>REGION_LID</td> <td>Код языка для наименования региона.</td> </tr> <tr>
	* <td>CITY_LID</td> <td>Код языка для наименования города.</td> </tr> <tr>
	* <td>REGION_NAME</td> <td>Языкозависимое название региона, если оно
	* установлено. Иначе - языконезависимое название региона.</td> </tr> <tr>
	* <td>CITY_NAME</td> <td>Языкозависимое название города, если оно
	* установлено. Иначе - языконезависимое название города.</td> </tr> <tr>
	* <td>LOC_DEFAULT</td> <td>Флаг "Выводить по умолчанию".</td> </tr> </table> <a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?// Выведем выпадающий список местоположений?&gt;
	* 
	* &lt;select name="LOCATION"&gt;
	*    &lt;?
	*    $db_vars = CSaleLocation::GetList(
	*         array(
	*                 "SORT" =&gt; "ASC",
	*                 "COUNTRY_NAME_LANG" =&gt; "ASC",
	*                 "CITY_NAME_LANG" =&gt; "ASC"
	*             ),
	*         array("LID" =&gt; LANGUAGE_ID),
	*         false,
	*         false,
	*         array()
	*     );
	*    while ($vars = $db_vars-&gt;Fetch()):
	*       ?&gt;
	*       &lt;option value="&lt;?= $vars["ID"]?&gt;"&gt;&lt;?= htmlspecialchars($vars["COUNTRY_NAME"]." - ".$vars["CITY_NAME"])?&gt;&lt;/option&gt;
	*       &lt;?
	*    endwhile;
	*    ?&gt;
	* &lt;/select&gt;
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getlist.a60c2ce1.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array("SORT"=>"ASC", "COUNTRY_NAME_LANG"=>"ASC", "CITY_NAME_LANG"=>"ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (is_string($arGroupBy) && strlen($arGroupBy) == 2)
		{
			$arFilter["LID"] = $arGroupBy;
			$arGroupBy = false;

			$arSelectFields = array("ID", "COUNTRY_ID", "REGION_ID", "CITY_ID", "SORT", "COUNTRY_NAME_ORIG", "COUNTRY_SHORT_NAME", "COUNTRY_NAME_LANG", "CITY_NAME_ORIG", "CITY_SHORT_NAME", "CITY_NAME_LANG", "REGION_NAME_ORIG", "REGION_SHORT_NAME", "REGION_NAME_LANG", "COUNTRY_NAME", "CITY_NAME", "REGION_NAME", "LOC_DEFAULT");
		}

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "COUNTRY_ID", "REGION_ID", "CITY_ID", "SORT", "COUNTRY_NAME_ORIG", "COUNTRY_SHORT_NAME", "REGION_NAME_ORIG", "CITY_NAME_ORIG", "REGION_SHORT_NAME", "CITY_SHORT_NAME", "COUNTRY_LID", "COUNTRY_NAME", "REGION_LID", "CITY_LID", "REGION_NAME", "CITY_NAME", "LOC_DEFAULT");

		if(!is_array($arOrder))
			$arOrder = array();

		foreach ($arOrder as $key => $dir)
		{
			if (!in_array($key, $arSelectFields))
				$arSelectFields[] = $key;
		}

		$arFilter = self::getFilterForGetList($arFilter);
		$arFields = self::getFieldMapForGetList($arFilter);

		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_location L ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sale_location L ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sale_location L ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// FOR MYSQL!!! ANOTHER CODE FOR ORACLE
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

	
	/**
	* <p>Метод возвращает параметры местоположения с кодом ID, включая параметры страны и города. Параметры, зависящие от языка, возвращаются для языка strLang. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код местоположения. </h
	*
	* @param string $strLang = LANGUAGE_ID Язык параметров, зависящих от языка. По умолчанию равен текущему
	* языку.
	*
	* @return array <p>Возвращает ассоциативный массив с ключами:</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> <th>С версии</th> </tr> <tr>
	* <td>ID</td> <td>Код местоположения.</td> <td></td> </tr> <tr> <td>COUNTRY_ID</td> <td>Код
	* страны.</td> <td></td> </tr> <tr> <td>CITY_ID</td> <td>Код города.</td> <td></td> </tr> <tr>
	* <td>SORT</td> <td>Индекс сортировки.</td> <td></td> </tr> <tr> <td>COUNTRY_NAME_ORIG</td>
	* <td>Языконезависимое название страны.</td> <td></td> </tr> <tr>
	* <td>COUNTRY_SHORT_NAME</td> <td>Языконезависимое сокращенное название
	* страны.</td> <td></td> </tr> <tr> <td>COUNTRY_NAME_LANG</td> <td>Языкозависимое название
	* страны.</td> <td></td> </tr> <tr> <td>CITY_NAME_ORIG</td> <td>Языконезависимое название
	* города.</td> <td></td> </tr> <tr> <td>CITY_SHORT_NAME</td> <td>Языконезависимое
	* сокращенное название города.</td> <td></td> </tr> <tr> <td>CITY_NAME_LANG</td>
	* <td>Языкозависимое название города.</td> <td></td> </tr> <tr> <td>REGION_ID</td> <td>Код
	* региона.</td> <td></td> </tr> <tr> <td>REGION_NAME_ORIG</td> <td>Языконезависимое
	* название региона.</td> <td>12.5 </td> </tr> <tr> <td>REGION_SHORT_NAME</td>
	* <td>Языконезависимое сокращенное название региона.</td> <td>12.5 </td> </tr>
	* <tr> <td>REGION_NAME_LANG</td> <td>Языкозависимое название региона.</td> <td>12.5 </td>
	* </tr> <tr> <td>COUNTRY_NAME</td> <td>Языкозависимое название страны, если оно
	* есть. Иначе - языконезависимое название страны.</td> <td></td> </tr> <tr>
	* <td>CITY_NAME</td> <td>Языкозависимое название города, если оно есть. Иначе
	* - языконезависимое название города.</td> <td></td> </tr> <tr> <td>REGION_NAME</td>
	* <td>Языкозависимое название региона, если оно есть. Иначе -
	* языконезависимое название региона.</td> <td>12.5 </td> </tr> </table> <p>  </p<a
	* name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arLocs = CSaleLocation::GetByID(22, LANGUAGE_ID);
	* echo $arLocs["COUNTRY_NAME"]." - ".$arLocs["CITY_NAME"];<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getbyid.bbc61011.php
	* @author Bitrix
	*/
	public static function GetByID($ID, $strLang = LANGUAGE_ID)
	{
		if(self::isLocationProMigrated())
			return parent::GetByID($ID, $strLang);

		global $DB;

		$ID = IntVal($ID);
		/*$strSql =
			"SELECT L.ID, L.COUNTRY_ID, L.CITY_ID, L.SORT, ".
			"	LC.NAME as COUNTRY_NAME_ORIG, LC.SHORT_NAME as COUNTRY_SHORT_NAME, LCL.NAME as COUNTRY_NAME_LANG, ".
			"	LG.NAME as CITY_NAME_ORIG, LG.SHORT_NAME as CITY_SHORT_NAME, LGL.NAME as CITY_NAME_LANG, ".
			"	IF(LCL.ID IS NULL, LC.NAME, LCL.NAME) as COUNTRY_NAME, ".
			"	IF(LGL.ID IS NULL, LG.NAME, LGL.NAME) as CITY_NAME ".
			"FROM b_sale_location L ".
			"	LEFT JOIN b_sale_location_country LC ON (L.COUNTRY_ID = LC.ID) ".
			"	LEFT JOIN b_sale_location_city LG ON (L.CITY_ID = LG.ID) ".
			"	LEFT JOIN b_sale_location_country_lang LCL ON (LC.ID = LCL.COUNTRY_ID AND LCL.LID = '".$DB->ForSql($strLang, 2)."') ".
			"	LEFT JOIN b_sale_location_city_lang LGL ON (LG.ID = LGL.CITY_ID AND LGL.LID = '".$DB->ForSql($strLang, 2)."') ".
			"WHERE L.ID = ".$ID." ";*/

		$strSql = "
		SELECT L.ID, L.COUNTRY_ID, L.CITY_ID, L.SORT, LC.NAME as COUNTRY_NAME_ORIG, LC.SHORT_NAME as COUNTRY_SHORT_NAME, LCL.NAME as COUNTRY_NAME_LANG,
		LG.NAME as CITY_NAME_ORIG, LG.SHORT_NAME as CITY_SHORT_NAME, LGL.NAME as CITY_NAME_LANG,
		L.REGION_ID, LR.NAME as REGION_NAME_ORIG, LR.SHORT_NAME as REGION_SHORT_NAME, LRL.NAME as REGION_NAME_LANG,
		IF(LCL.ID IS NULL, LC.NAME, LCL.NAME) as COUNTRY_NAME,
		IF(LGL.ID IS NULL, LG.NAME, LGL.NAME) as CITY_NAME,
		IF(LRL.ID IS NULL, LR.NAME, LRL.NAME) as REGION_NAME
		FROM b_sale_location L
			LEFT JOIN b_sale_location_country LC ON (L.COUNTRY_ID = LC.ID)
			LEFT JOIN b_sale_location_city LG ON (L.CITY_ID = LG.ID)
			LEFT JOIN b_sale_location_country_lang LCL ON (LC.ID = LCL.COUNTRY_ID AND LCL.LID = '".$DB->ForSql($strLang, 2)."')
			LEFT JOIN b_sale_location_city_lang LGL ON (LG.ID = LGL.CITY_ID AND LGL.LID = '".$DB->ForSql($strLang, 2)."')
			LEFT JOIN b_sale_location_region LR ON (L.REGION_ID = LR.ID)
			LEFT JOIN b_sale_location_region_lang LRL ON (LR.ID = LRL.REGION_ID AND LRL.LID = '".$DB->ForSql($strLang, 2)."')
		WHERE L.ID = ".$ID." ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	* <p>Метод возвращает набор стран по фильтру arFilter, отсортированный по массиву arOrder. Языкозависимые параметры берутся для языка strLang. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = Array("NAME_LANG"=>"ASC") Ассоциативный массив для сортировки записей. Сортировка
	* осуществляется последовательно по каждой паре ключ-значение.
	* Ключами являются названия параметров, а значениями - направления
	* сортировки. <br><br> Допустимые ключи: <ul> <li> <b>NAME_LANG</b> -
	* языкозависимое название страны;</li> <li> <b>ID</b> - код страны;</li> <li>
	* <b>NAME</b> - языконезависимое название страны; </li> <li> <b>SHORT_NAME</b> -
	* языконезависимое сокращенное название страны.</li> </ul> Допустимые
	* значения: <ul> <li> <b>ASC</b> - по возрастанию;</li> <li> <b>DESC</b> - по
	* убыванию.</li> </ul>
	*
	* @param array $arFilter = Array() Ассоциативный массив для фильтрации записей - выбираются только
	* те записи, которые удовлетворяют фильтру. Ключами являются
	* названия параметров, а значениями - условия на значения.<br><br>
	* Допустимые ключи: <ul> <li> <b>ID</b> - код страны;</li> <li> <b>NAME</b> -
	* языконезависимое название страны.</li> </ul>
	*
	* @param string $strLang = LANGUAGE_ID Язык, на котором выбираются языкозависимые параметры. По
	* умолчанию равен текущему языку.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код страны.</td> </tr> <tr>
	* <td>NAME_ORIG</td> <td>Языконезависимое название страны.</td> </tr> <tr>
	* <td>SHORT_NAME</td> <td>Языконезависимое короткое название страны.</td> </tr> <tr>
	* <td>NAME</td> <td>Языкозависимое название страны.</td> </tr> <tr> <td>NAME_LANG</td>
	* <td>Языкозависимое название страны, если оно есть. Иначе
	* языконезависимое название страны.</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__getcountrylist.c37e68f6.php
	* @author Bitrix
	*/
	public static function GetCountryList($arOrder = Array("NAME_LANG"=>"ASC"), $arFilter=Array(), $strLang = LANGUAGE_ID)
	{
		if(self::isLocationProMigrated())
			return self::GetLocationTypeList('COUNTRY', $arOrder, $arFilter, $strLang);

		global $DB;
		$arSqlSearch = Array();

		if(!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilterKey = count($filter_keys);
		for($i=0; $i < $countFilterKey; $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(ToUpper($key))
			{
			case "ID":
				$arSqlSearch[] = "C.ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
				break;
			case "NAME":
				$arSqlSearch[] = "C.NAME ".($bInvert?"<>":"=")." '".$val."' ";
				break;
			}
		}

		$strSqlSearch = "";
		$countSqlSearch = count($arSqlSearch);
		for($i=0; $i < $countSqlSearch; $i++)
		{
			$strSqlSearch .= " AND ";
			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql =
			"SELECT DISTINCT C.ID, C.NAME as NAME_ORIG, C.SHORT_NAME, CL.NAME as NAME, ".
			"	IF(CL.ID IS NULL, C.NAME, CL.NAME) as NAME_LANG ".
			"FROM b_sale_location_country C ".
			"	LEFT JOIN b_sale_location_country_lang CL ON (C.ID = CL.COUNTRY_ID AND CL.LID = '".$DB->ForSql($strLang, 2)."') ".
			(
				strlen($arOrder["SORT"]) > 0
				?
				"	LEFT JOIN b_sale_location SL ON (SL.COUNTRY_ID = C.ID AND (SL.CITY_ID = 0 OR ISNULL(SL.CITY_ID))) "
				:
				""
			).
			"WHERE 1 = 1 ".
			"	".$strSqlSearch." ";

		$arSqlOrder = Array();
		foreach ($arOrder as $by=>$order)
		{
			$by = ToUpper($by);
			$order = ToUpper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "SORT") $arSqlOrder[] = " SL.SORT ".$order;
			elseif ($by == "ID") $arSqlOrder[] = " C.ID ".$order." ";
			elseif ($by == "NAME") $arSqlOrder[] = " C.NAME ".$order." ";
			elseif ($by == "SHORT_NAME") $arSqlOrder[] = " C.SHORT_NAME ".$order." ";
			else
			{
				$arSqlOrder[] = " CL.NAME ".$order." ";
				$by = "NAME_LANG";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i < $countSqlOrder; $i++)
		{
			if ($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ", ";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	/**
	* The function select all region
	*
	* @param array $arOrder sorting an array of results
	* @param array $arFilter filtered an array of results
	* @param string $strLang language regions of the sample
	* @return true false
	*/
	public static function GetRegionList($arOrder = Array("NAME_LANG"=>"ASC"), $arFilter=Array(), $strLang = LANGUAGE_ID)
	{
		if(self::isLocationProMigrated())
			return self::GetLocationTypeList('REGION', $arOrder, $arFilter, $strLang);

		global $DB;
		$arSqlSearch = Array();

		if(!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilterKey = count($filter_keys);
		for($i=0; $i < $countFilterKey; $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(ToUpper($key))
			{
				case "ID":
					$arSqlSearch[] = "C.ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					break;
				case "NAME":
					$arSqlSearch[] = "C.NAME ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "COUNTRY_ID":
					$arSqlSearch[] = "SL.COUNTRY_ID ".($bInvert?"<>":"=")." '".$val."' ";
					break;
			}
		}

		$strSqlSearch = "";
		$countSqlSearch = count($arSqlSearch);
		for($i=0; $i < $countSqlSearch; $i++)
		{
			$strSqlSearch .= " AND ";
			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql =
			"SELECT C.ID, C.NAME as NAME_ORIG, C.SHORT_NAME, CL.NAME as NAME, ".
			"	IF(CL.ID IS NULL, C.NAME, CL.NAME) as NAME_LANG ".
			"FROM b_sale_location_region C ".
			"	LEFT JOIN b_sale_location_region_lang CL ON (C.ID = CL.REGION_ID AND CL.LID = '".$DB->ForSql($strLang, 2)."') ".
			"	LEFT JOIN b_sale_location SL ON (SL.REGION_ID = C.ID AND (SL.CITY_ID = 0 OR ISNULL(SL.CITY_ID))) ".
			"WHERE 1 = 1 ".
			"	".$strSqlSearch." ";

		$arSqlOrder = Array();
		foreach ($arOrder as $by=>$order)
		{
			$by = ToUpper($by);
			$order = ToUpper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "SORT") $arSqlOrder[] = " SL.SORT ".$order;
			elseif ($by == "ID") $arSqlOrder[] = " C.ID ".$order." ";
			elseif ($by == "NAME") $arSqlOrder[] = " C.NAME ".$order." ";
			elseif ($by == "SHORT_NAME") $arSqlOrder[] = " C.SHORT_NAME ".$order." ";
			else
			{
				$arSqlOrder[] = " CL.NAME ".$order." ";
				$by = "NAME_LANG";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i < $countSqlOrder; $i++)
		{
			if ($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ", ";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	/**
	 * The function select all cities
	 *
	 * @param array $arOrder sorting an array of results
	 * @param array $arFilter filtered an array of results
	 * @param string $strLang language regions of the sample
	 * @return true false
	 */
	public static function GetCityList($arOrder = Array("NAME_LANG"=>"ASC"), $arFilter=Array(), $strLang = LANGUAGE_ID)
	{
		if(self::isLocationProMigrated())
			return self::GetLocationTypeList('CITY', $arOrder, $arFilter, $strLang);

		global $DB;
		$arSqlSearch = Array();

		if(!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilterKey = count($filter_keys);
		for($i=0; $i < $countFilterKey; $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(ToUpper($key))
			{
				case "ID":
					$arSqlSearch[] = "C.ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
					break;
				case "NAME":
					$arSqlSearch[] = "C.NAME ".($bInvert?"<>":"=")." '".$val."' ";
					break;
				case "REGION_ID":
					$arSqlSearch[] = "SL.REGION_ID ".($bInvert?"<>":"=")." '".$val."' ";
					break;
			}
		}

		$strSqlSearch = "";
		$countSqlSearch = count($arSqlSearch);
		for($i=0; $i < $countSqlSearch; $i++)
		{
			$strSqlSearch .= " AND ";
			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql =
			"SELECT C.ID, C.NAME as NAME_ORIG, C.SHORT_NAME, CL.NAME as NAME, ".
			"	IF(CL.ID IS NULL, C.NAME, CL.NAME) as NAME_LANG ".
			"FROM b_sale_location_city C ".
			"	LEFT JOIN b_sale_location_city_lang CL ON (C.ID = CL.CITY_ID AND CL.LID = '".$DB->ForSql($strLang, 2)."') ".
			"	LEFT JOIN b_sale_location SL ON (SL.CITY_ID = C.ID) ".
			"WHERE 1 = 1 ".
			"	".$strSqlSearch." ";

		$arSqlOrder = Array();
		foreach ($arOrder as $by=>$order)
		{
			$by = ToUpper($by);
			$order = ToUpper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "SORT") $arSqlOrder[] = " SL.SORT ".$order;
			elseif ($by == "ID") $arSqlOrder[] = " C.ID ".$order." ";
			elseif ($by == "NAME") $arSqlOrder[] = " C.NAME ".$order." ";
			elseif ($by == "SHORT_NAME") $arSqlOrder[] = " C.SHORT_NAME ".$order." ";
			else
			{
				$arSqlOrder[] = " CL.NAME ".$order." ";
				$by = "NAME_LANG";
			}
		}

		$strSqlOrder = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i < $countSqlOrder; $i++)
		{
			if ($i==0)
				$strSqlOrder = " ORDER BY ";
			else
				$strSqlOrder .= ", ";

			$strSqlOrder .= $arSqlOrder[$i];
		}

		$strSql .= $strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	// have to use old table as a temporal place to store countries, kz add of a country doesn`t mean add of a location
	
	/**
	* <p>Метод добавляет новую страну с параметрами из массива <i> arFields</i>. Метод динамичный.</p> <p class="note"><b>Внимание!</b> Начиная с версии 14.10.0 метод не обновляется и обратная совместимость не поддерживается. Рекомендуется использовать методы нового ядра D7. Примеры работы с новым ядром можно увидеть <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3570" >здесь</a>.</p>
	*
	*
	* @param array $arFields  Массив с параметрами страны должен содержать ключи: <ul> <li> <b>NAME</b> -
	* название страны (не зависящее от языка);</li> <li> <b>SHORT_NAME</b> -
	* сокращенное название страны - аббревиатура (не зависящее от
	* языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является код языка, а
	* значением ассоциативный массив вида <pre class="syntax"> array("LID" =&gt; "код
	* языка", "NAME" =&gt; "название страны на этом языке", "SHORT_NAME" =&gt;
	* "сокращенное название страны (аббревиатура) на этом языке")</pre> Эта
	* пара ключ-значение должна присутствовать для каждого языка
	* системы.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного местоположения или <i>false</i> у
	* случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* Параметры вызова
	* </h
	* <tr>
	* <th width="15%">Параметр</th>
	* <th>Описание</th>
	* </tr>
	* <tr>
	* <td>arFields</td>
	* <td>Массив с параметрами страны должен содержать ключи:
	* <ul>
	* <li>
	* <b>NAME</b> - название страны (не зависящее от языка);</li>
	* 	<li>
	* <b>SHORT_NAME</b> - сокращенное название страны - аббревиатура (не зависящее от языка);</li>
	* 	<li>
	* <b>&lt;код языка&gt;</b> - ключем является код языка, а значением ассоциативный массив вида
	* <pre class="syntax">
	* array("LID" =&gt; "код языка",
	*       "NAME" =&gt; "название страны на этом языке",
	*       "SHORT_NAME" =&gt; "сокращенное название страны
	*                        (аббревиатура) на этом языке")</pre>
	* 	Эта пара ключ-значение должна присутствовать для каждого языка системы.</li>
	* </ul>
	* </td>
	* </tr>
	* 
	* 
	* 
	* &lt;?
	* $arCountry = array(
	*    "NAME" =&gt; "Russian Federation",
	*    "SHORT_NAME" =&gt; "Russia",
	*    "ru" =&gt; array(
	*       "LID" =&gt; "ru",
	*       "NAME" =&gt; "Российская федерация",
	*       "SHORT_NAME" =&gt; "Россия"
	*       ),
	*    "en" =&gt; array(
	*       "LID" =&gt; "en",
	*       "NAME" =&gt; "Russian Federation",
	*       "SHORT_NAME" =&gt; "Russia"
	*       )
	* );
	* 
	* $ID = CSaleLocation::AddCountry($arCountry);
	* if (IntVal($ID)&lt;=0)
	*    echo "Ошибка добавления страны";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addcountry.cbe82f7a.php
	* @author Bitrix
	*/
	public static function AddCountry($arFields)
	{
		global $DB;

		if (!CSaleLocation::CountryCheckFields("ADD", $arFields))
			return false;

		if(self::isLocationProMigrated())
		{
			return self::AddLocationUnattached('COUNTRY', $arFields);
		}

		foreach (GetModuleEvents('sale', 'OnBeforeCountryAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;
		}

		$arInsert = $DB->PrepareInsert("b_sale_location_country", $arFields);
		$strSql =
			"INSERT INTO b_sale_location_country(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		$b = "sort";
		$o = "asc";
		$db_lang = CLangAdmin::GetList($b, $o, array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ($arFields[$arLang['LID']])
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_country_lang", $arFields[$arLang["LID"]]);
				$strSql =
					"INSERT INTO b_sale_location_country_lang(COUNTRY_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		foreach (GetModuleEvents('sale', 'OnCountryAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	// have to use old table as a temporal place to store cities, kz we don`t know yet which country\region a newly-created city belongs to
	
	/**
	* <p>Метод добавляет новый город с параметрами из массива <i> arFields</i>. Метод динамичный.</p> <p class="note"><b>Внимание!</b> Начиная с версии 14.10.0 метод не обновляется и обратная совместимость не поддерживается. Рекомендуется использовать методы нового ядра D7. Примеры работы с новым ядром можно увидеть <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3570" >здесь</a>.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив с параметрами города должен содержать
	* ключи: <ul> <li> <b>NAME</b> - название города (не зависящее от языка);</li> <li>
	* <b>SHORT_NAME</b> - сокращенное название города - аббревиатура (не
	* зависящее от языка);</li> <li> <b>&lt;код языка&gt;</b> - ключем является код
	* языка, а значением ассоциативный массив вида <pre class="syntax"> array("LID"
	* =&gt; "код языка", "NAME" =&gt; "название города на этом языке", "SHORT_NAME" =&gt;
	* "сокращенное название города (аббревиатура) на этом языке")</pre> Эта
	* пара ключ-значение должна присутствовать для каждого языка
	* системы.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного города или <i>false</i> в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* Параметры вызова
	* </h
	* <tr>
	* <th width="15%">Параметр</th>
	* <th>Описание</th>
	* </tr>
	* <tr>
	* <td>arFields</td>
	* 	<td>Ассоциативный массив с параметрами города должен содержать ключи:
	* <ul>
	* <li>
	* <b>NAME</b> - название города (не зависящее от языка);</li>
	* 	<li>
	* <b>SHORT_NAME</b> - сокращенное название города - аббревиатура (не зависящее от языка);</li>
	* 	<li>
	* <b>&lt;код языка&gt;</b> - ключем является код языка, а значением ассоциативный массив вида
	* <pre class="syntax">
	* array("LID" =&gt; "код языка",
	*       "NAME" =&gt; "название города на этом языке",
	*       "SHORT_NAME" =&gt; "сокращенное название города
	*                        (аббревиатура) на этом языке")</pre>
	* Эта пара ключ-значение должна присутствовать для каждого языка системы.</li>
	* </ul>
	* </td>
	* </tr>
	* 
	* 
	* 
	* &lt;?
	* $arCity = array(
	*    "NAME" =&gt; "Kaliningrad",
	*    "SHORT_NAME" =&gt; "Kaliningrad",
	*    "ru" =&gt; array(
	*       "LID" =&gt; "ru",
	*       "NAME" =&gt; "Калининград",
	*       "SHORT_NAME" =&gt; "Калининград"
	*       ),
	*    "en" =&gt; array(
	*       "LID" =&gt; "en",
	*       "NAME" =&gt; "Kaliningrad",
	*       "SHORT_NAME" =&gt; "Kaliningrad"
	*       )
	* );
	* 
	* $ID = CSaleLocation::AddCity($arCity);
	* if (IntVal($ID)&lt;=0)
	*    echo "Ошибка добавления города";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addcity.d2d048d2.php
	* @author Bitrix
	*/
	public static function AddCity($arFields)
	{
		global $DB;

		if (!CSaleLocation::CityCheckFields("ADD", $arFields))
			return false;

		if(self::isLocationProMigrated())
		{
			return self::AddLocationUnattached('CITY', $arFields);
		}

		foreach (GetModuleEvents('sale', 'OnBeforeCityAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;
		}

		$arInsert = $DB->PrepareInsert("b_sale_location_city", $arFields);
		$strSql =
			"INSERT INTO b_sale_location_city(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		$b = "sort";
		$o = "asc";
		$db_lang = CLangAdmin::GetList($b, $o, array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ($arFields[$arLang["LID"]])
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_city_lang", $arFields[$arLang["LID"]]);
				$strSql =
					"INSERT INTO b_sale_location_city_lang(CITY_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		foreach (GetModuleEvents('sale', 'OnCityAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	// have to use old table as a temporal place to store region, kz we don`t know yet which country a newly-created region belongs to
	public static function AddRegion($arFields)
	{
		global $DB;

		if (!CSaleLocation::RegionCheckFields("ADD", $arFields))
			return false;

		if(self::isLocationProMigrated())
		{
			return self::AddLocationUnattached('REGION', $arFields);
		}

		foreach (GetModuleEvents('sale', 'OnBeforeRegionAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;
		}

		$arInsert = $DB->PrepareInsert("b_sale_location_region", $arFields);
		$strSql =
			"INSERT INTO b_sale_location_region(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		$b = "sort";
		$o = "asc";
		$db_lang = CLangAdmin::GetList($b, $o, array("ACTIVE" => "Y"));
		while ($arLang = $db_lang->Fetch())
		{
			if ($arFields[$arLang["LID"]])
			{
				$arInsert = $DB->PrepareInsert("b_sale_location_region_lang", $arFields[$arLang["LID"]]);
				$strSql =
					"INSERT INTO b_sale_location_region_lang(REGION_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		foreach (GetModuleEvents('sale', 'OnRegionAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	
	/**
	* <p>Метод добавляет новое местоположение на основании параметров массива <i> arFields</i>. Метод динамичный.</p> <p class="note"><b>Внимание!</b> Начиная с версии 14.10.0 метод не обновляется и обратная совместимость не поддерживается. Рекомендуется использовать методы нового ядра D7. Примеры работы с новым ядром можно увидеть <a href="https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&amp;LESSON_ID=3570" >здесь</a>.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров местоположения с ключами: <ul> <li>
	* <b>SORT</b> - индекс сортировки; </li> <li> <b>COUNTRY_ID</b> - код страны;</li> <li>
	* <b>REGION_ID</b> - код региона;</li> <li> <b>CITY_ID</b> - код города.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного местоположения или <i>false</i> у
	* случае ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* Параметры вызова
	* </h
	* <tr>
	* <th width="15%">Параметр</th>
	* <th>Описание</th>
	* </tr>
	* <tr>
	* <td>arFields</td>
	* <td>Ассоциативный массив параметров местоположения с ключами:
	* <ul>
	* <li>
	* <b>SORT</b> - индекс сортировки; </li>
	* 	<li>
	* <b>COUNTRY_ID</b> - код страны;</li>
	* 	<li>
	* <b>REGION_ID</b> - код региона;</li>
	* 	<li>
	* <b>CITY_ID</b> - код города.</li>
	* </ul>
	* </td>
	* </tr>
	* 
	* 
	* 
	* &lt;?
	* // Добавим местоположение из страны с кодом 2 и города с кодом 10
	* $ID = CSaleLocation::AddLocation(
	*       array(
	*          "COUNTRY_ID" =&gt; 2,
	*          "CITY_ID" =&gt; 10
	*          )
	*       );
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalelocation/csalelocation__addlocation.21fe0465.php
	* @author Bitrix
	*/
	public static function AddLocation($arFields)
	{
		global $DB;

		if (!CSaleLocation::LocationCheckFields("ADD", $arFields))
			return false;

		if(self::isLocationProMigrated())
		{
			return self::RebindLocationTriplet($arFields);
		}

		// make IX_B_SALE_LOC_CODE feel happy
		$arFields['CODE'] = 'randstr'.rand(999, 99999);

		foreach (GetModuleEvents('sale', 'OnBeforeLocationAdd', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;
		}

		$arInsert = $DB->PrepareInsert("b_sale_location", $arFields);
		$strSql =
			"INSERT INTO b_sale_location(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		// make IX_B_SALE_LOC_CODE feel happy
		Location\LocationTable::update($ID, array('CODE' => $ID));

		foreach (GetModuleEvents('sale', 'OnLocationAdd', true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}
}