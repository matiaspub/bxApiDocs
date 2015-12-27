<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/extra.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/index.php
 * @author Bitrix
 */
class CExtra extends CAllExtra
{
	
	/**
	* <p>Добавляет новую запись в таблицу наценок. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров записи с ключами: <ul> <li>NAME -
	* название наценки;</li> <li>PERCENTAGE - процент наценки (может быть как
	* положительным, так и отрицательным)</li> </ul>
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного сохранения и <i>false</i> - в
	* противном случае </p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/cextra__add.937250e4.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CExtra::CheckFields('ADD', $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_catalog_extra", $arFields);

		$strSql = "INSERT INTO b_catalog_extra(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());
		CExtra::ClearCache();

		return $ID;
	}

	
	/**
	* <p>Метод возвращает список наценок в соответствии с фильтром и условиями сортировки. Метод динамичный.</p> <p></p> <div class="note"> <b>Примечание:</b> в таком виде метод работает с версии <b>11.0.0</b>. До этой версии использовалась устаревшая форма вызова метода (см. <a href="#old">ниже</a>).</div>
	*
	*
	* @param array $arOrder = array() Массив вида array(by1=&gt;order1[, by2=&gt;order2 [, ..]]), где by - поле для сортировки,
	* может принимать значения: <ul> <li> <b>ID</b> - код (ID) наценки</li> <li> <b>NAME</b> -
	* название наценки</li> <li> <b>PERCENTAGE</b> - величина наценки</li> </ul> поле order
	* - направление сортировки, может принимать значения: <ul> <li> <b>asc</b> -
	* по возрастанию</li> <li> <b>desc</b> - по убыванию</li> </ul> Необязательный. По
	* умолчанию данные не сортируются.
	*
	* @param array $arFilter = array() Массив параметров, по которым строится фильтр выборки. Имеет вид:
	* <pre class="syntax">array( "[модификатор1][оператор1]название_поля1" =&gt;
	* "значение1", "[модификатор2][оператор2]название_поля2" =&gt; "значение2",
	* . . . )</pre> Удовлетворяющие фильтру записи возвращаются в
	* результате, а записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются. <br> Допустимыми являются следующие модификаторы:
	* <ul> <li> <b>!</b> - отрицание;</li> <li> <b>+</b> - значения null, 0 и пустая строка
	* так же удовлетворяют условиям фильтра.</li> </ul> Допустимыми
	* являются следующие операторы: <ul> <li> <b>&gt;=</b> - значение поля больше
	* или равно передаваемой в фильтр величины;</li> <li> <b>&gt;</b> - значение
	* поля строго больше передаваемой в фильтр величины;</li> <li> <b>&lt;=</b> -
	* значение поля меньше или равно передаваемой в фильтр величины;</li>
	* <li> <b>&lt;</b> - значение поля строго меньше передаваемой в фильтр
	* величины;</li> <li> <b>@</b> - оператор может использоваться для
	* целочисленных и вещественных данных при передаче набора
	* значений (массива). В этом случае при генерации sql-запроса будет
	* использован sql-оператор <b>IN</b>, дающий компактную форму записи;</li>
	* <li> <b>~</b> - значение поля проверяется на соответствие
	* передаваемому в фильтр шаблону;</li> <li> <b>%</b> - значение поля
	* проверяется на соответствие передаваемой в фильтр строке в
	* соответствии с языком запросов.</li> </ul> "название поля" может
	* принимать значения: <ul> <li> <b>ID</b> - код (ID) наценки (число)</li> <li> <b>NAME</b>
	* - название наценки (строка)</li> <li> <b>PERCENTAGE</b> - величина наценки
	* (число)</li> </ul> Значения фильтра - одиночное значение или массив
	* значений. <br> Необязательное. По умолчанию наценки не фильтруются.
	*
	* @param mixed $arGroupBy = false Массив полей для группировки наценок. Имеет вид: <pre
	* class="syntax">array("название_поля1", "название_поля2", . . .)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле каталога. <br><br> Если
	* массив пустой, то метод вернет число записей, удовлетворяющих
	* фильтру. <br> Значение по умолчанию - <i>false</i> - означает, что
	* результат группироваться не будет.
	*
	* @param mixed $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li>любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Необязательный. По умолчанию false - наценки не
	* ограничиваются.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные поля.
	* <br> Необязательный. По умолчанию выводятся все поля.
	*
	* @return CDBResult <p>Объект класса <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">Класс
	* CDBResult</a>, содержащий ассоциативные массивы с ключами:</p> <table
	* class="tnormal" width="100%"><tbody> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код наценки.</td> </tr> <tr> <td>NAME</td> <td>Название наценки.</td> </tr> <tr>
	* <td>PERCENTAGE</td> <td>Величина наценки.</td> </tr> </tbody></table>
	* <h4>Примечания</h4></bod<a name="old"></a><p>Сохранен старый способ вызова:</p> <pre
	* class="syntax"><b>CDBResult CExtra::GetList(</b> string by, string order <b>);</b></pre><p>где by - поле
	* сортировки, а order - направление.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cextra/getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		// for old execution style
		if (!is_array($arOrder) && !is_array($arFilter))
		{
			$arOrder = strval($arOrder);
			$arFilter = strval($arFilter);
			if ('' != $arOrder && '' != $arFilter)
				$arOrder = array($arOrder => $arFilter);
			else
				$arOrder = array();
			$arFilter = array();
			$arGroupBy = false;
		}

		if (empty($arSelectFields))
			$arSelectFields = array("ID", "NAME", "PERCENTAGE");

		$arFields = array(
			"ID" => array("FIELD" => "E.ID", "TYPE" => "int"),
			"NAME" => array("FIELD" => "E.NAME", "TYPE" => "string"),
			"PERCENTAGE" => array("FIELD" => "E.PERCENTAGE", "TYPE" => "double"),
		);

		$arSqls = CCatalog::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (empty($arGroupBy) && is_array($arGroupBy))
		{
			$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_extra E ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql = "SELECT ".$arSqls["SELECT"]." FROM b_catalog_extra E ".$arSqls["FROM"];
		if (!empty($arSqls["WHERE"]))
			$strSql .= " WHERE ".$arSqls["WHERE"];
		if (!empty($arSqls["GROUPBY"]))
			$strSql .= " GROUP BY ".$arSqls["GROUPBY"];
		if (!empty($arSqls["ORDERBY"]))
			$strSql .= " ORDER BY ".$arSqls["ORDERBY"];

		$intTopCount = 0;
		$boolNavStartParams = (!empty($arNavStartParams) && is_array($arNavStartParams));
		if ($boolNavStartParams && array_key_exists('nTopCount', $arNavStartParams))
		{
			$intTopCount = intval($arNavStartParams["nTopCount"]);
		}
		if ($boolNavStartParams && 0 >= $intTopCount)
		{
			$strSql_tmp = "SELECT COUNT('x') as CNT FROM b_catalog_extra E ".$arSqls["FROM"];
			if (!empty($arSqls["WHERE"]))
				$strSql_tmp .= " WHERE ".$arSqls["WHERE"];
			if (!empty($arSqls["GROUPBY"]))
				$strSql_tmp .= " GROUP BY ".$arSqls["GROUPBY"];

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (empty($arSqls["GROUPBY"]))
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if ($boolNavStartParams && 0 < $intTopCount)
			{
				$strSql .= " LIMIT ".$intTopCount;
			}
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $dbRes;
	}
}
?>