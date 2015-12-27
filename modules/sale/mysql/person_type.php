<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/person_type.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/index.php
 * @author Bitrix
 */
class CSalePersonType extends CAllSalePersonType
{
	
	/**
	* <p>Метод возвращает результат выборки записей из типов плательщика в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле типов плательщика, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и
	* т.д.). <br><br> Значение по умолчанию - пустой массив array() - означает,
	* что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи типов
	* плательщика. Массив имеет вид: <pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - значение поля находится в передаваемом в
	* фильтр разделенном запятой списке значений;</li> <li> <b>~</b> - значение
	* поля проверяется на соответствие передаваемому в фильтр
	* шаблону;</li> <li> <b>%</b> - значение поля проверяется на соответствие
	* передаваемой в фильтр строке в соответствии с языком запросов.</li>
	* </ul> В качестве "название_поляX" может стоять любое поле типов
	* плательщика.<br> Значение по умолчанию - пустой массив array() -
	* означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи типов плательщика.
	* Массив имеет вид: <pre class="syntax">array("название_поля1",
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле типов плательщика. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Этот фильтр означает "выбрать все записи, в которых
	* значение в поле LID (сайт системы) не равно en".<br><br> Значение по
	* умолчанию - <i>false</i> - означает, что результат группироваться не
	* будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные
	* поля.<br><br> Значение по умолчанию - пустой массив array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров типов плательщиков с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	* </tr> <tr> <td>ID</td> <td>Код типа плательщика.</td> </tr> <tr> <td>LID</td> <td>Код
	* сайта.</td> </tr> <tr> <td>LIDS</td> <td>Фильтрация/выборка всех сайтов, к
	* которым привязан тип плательщика.</td> </tr> <tr> <td>NAME</td> <td>Название
	* типа плательщика.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки.</td> </tr> <tr>
	* <td>ACTIVE</td> <td>Флаг активности пользователя [Y|N].</td> </tr> </table> <p>Если в
	* качестве параметра arGroupBy передается пустой массив, то метод
	* вернет число записей, удовлетворяющих фильтру.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем переключатели для выбора типа плательщика для текущего сайта
	* $db_ptype = CSalePersonType::GetList(Array("SORT" =&gt; "ASC"), Array("LID"=&gt;SITE_ID));
	* $bFirst = True;
	* while ($ptype = $db_ptype-&gt;Fetch())
	* {
	*    ?&gt;&lt;input type="radio" name="PERSON_TYPE" value="&lt;?echo $ptype["ID"] ?&gt;"&lt;?if ($bFirst) echo " checked";?&gt;&gt;&lt;?echo $ptype["NAME"] ?&gt;&lt;br&gt;&lt;?
	*    $bFirst = <i>false</i>;
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__getlist.2dca23fd.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if (strlen($arOrder) > 0 && strlen($arFilter) > 0)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			if (is_array($arGroupBy))
				$arFilter = $arGroupBy;
			else
				$arFilter = array();
			$arGroupBy = false;
		}
		if(empty($arSelectFields))
			$arSelectFields = Array("ID", "LID", "NAME", "SORT", "ACTIVE");
			
		if(is_set($arFilter, "LID") && !empty($arFilter["LID"]))
		{
			$arFilter["LIDS"] = $arFilter["LID"];
			unset($arFilter["LID"]);
		}

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "PT.ID", "TYPE" => "int"),
				"LID" => array("FIELD" => "PT.LID", "TYPE" => "string"),
				"LIDS" => array("FIELD" => "PTS.SITE_ID", "TYPE" => "string", "FROM" => "LEFT JOIN b_sale_person_type_site PTS ON (PT.ID = PTS.PERSON_TYPE_ID)"),
				"NAME" => array("FIELD" => "PT.NAME", "TYPE" => "string"),
				"SORT" => array("FIELD" => "PT.SORT", "TYPE" => "int"),
				"ACTIVE" => array("FIELD" => "PT.ACTIVE", "TYPE" => "char"),
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_person_type PT ".
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
			"FROM b_sale_person_type PT ".
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
				"FROM b_sale_person_type PT ".
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
		
		
		$arPT = array();
		$arResTmp = array();
		while ($arRes = $dbRes->Fetch())
		{
			if(IntVal($arRes["ID"]) > 0)
			{
				if(!in_array($arRes["ID"], $arPT))
					$arPT[] = $arRes["ID"];
				$arResTmp[] = $arRes;
			}
		}
		
		if(!empty($arPT) && is_array($arPT))
		{
			$strSql = "SELECT * from b_sale_person_type_site WHERE PERSON_TYPE_ID IN (".implode(",", $arPT).")";
			$dbRes1 = $DB->Query($strSql, false,	"File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes1 = $dbRes1->Fetch())
			{
				$arRes2[$arRes1["PERSON_TYPE_ID"]][] = $arRes1["SITE_ID"];
			}
		}

		foreach($arResTmp as $k => $v)
			$arResTmp[$k]["LIDS"] = $arRes2[$v["ID"]];
			
		$dbRes = new CDBResult();
		$dbRes->InitFromArray($arResTmp);

		return $dbRes;
	}

	
	/**
	* <p>Метод добавляет новый тип плательщика с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового типа плательщиков,
	* ключами в котором являются названия параметров, а значениями -
	* соответствующие значения.<br> Допустимые ключи:<ul> <li> <b>LID</b> - код
	* сайта, к которому привязан тип плательщика. (Может быть массивом
	* сайтов);</li> <li> <b>NAME</b> - название типа плательщика;</li> <li> <b>SORT</b> -
	* индекс сортировки.</li> <li> <b>ACTIVE</b> - флаг активности пользователя
	* [Y|N] .</li> </ul>
	*
	* @return Int <p>Возвращается код добавленного типа плательщика или <i>false</i> - в
	* случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepersontype/csalepersontype__add.a7f60787.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSalePersonType::CheckFields("ADD", $arFields))
			return false;

		$db_events = GetModuleEvents("sale", "OnBeforePersonTypeAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, Array(&$arFields))===false)
				return false;

		$arLID = Array();
		if(is_set($arFields, "LID"))
		{
			if(is_array($arFields["LID"]))
				$arLID = $arFields["LID"];
			else
				$arLID[] = $arFields["LID"];

			$str_LID = "''";
			$arFields["LID"] = false;
			foreach($arLID as $k => $v)
			{
				if(strlen($v) > 0)
				{
					$str_LID .= ", '".$DB->ForSql($v)."'";
					if(empty($arFields["LID"]))
						$arFields["LID"] = $v;
				}
				else
					unset($arLID[$k]);
			}
		}

		$arInsert = $DB->PrepareInsert("b_sale_person_type", $arFields);

		$strSql =
			"INSERT INTO b_sale_person_type(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());
		
		if(count($arLID)>0)
		{
			$strSql = "DELETE FROM b_sale_person_type_site WHERE PERSON_TYPE_ID=".$ID;
			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

			$strSql =
				"INSERT INTO b_sale_person_type_site(PERSON_TYPE_ID, SITE_ID) ".
				"SELECT ".$ID.", LID ".
				"FROM b_lang ".
				"WHERE LID IN (".$str_LID.") ";

			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		unset($GLOBALS["SALE_PERSON_TYPE_LIST_CACHE"]);

		$events = GetModuleEvents("sale", "OnPersonTypeAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, Array($ID, $arFields));

		return $ID;
	}
}
?>