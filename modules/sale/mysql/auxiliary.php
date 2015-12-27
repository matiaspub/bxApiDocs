<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/auxiliary.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/index.php
 * @author Bitrix
 */
class CSaleAuxiliary extends CAllSaleAuxiliary
{
	//********** SELECT **************//
	
	/**
	* <p>Метод выбирает параметры информации о временном доступе с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код записи.
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров информации о
	* временном доступе с ключами:</p> <ul> <li> <b>ID</b> - код записи;</li> <li>
	* <b>USER_ID</b> - код пользователя;</li> <li> <b>ITEM</b> - ресурс, доступ к которому
	* разрешен;</li> <li> <b>ITEM_MD5</b> - идентификатор ресурса (строка,
	* однозначно идентифицирующая ресурс);</li> <li> <b>TIMESTAMP_X</b> - дата
	* изменения;</li> <li> <b>DATE_INSERT</b> - дата вставки записи.</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/csaleauxiliary.getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		$strSql = 
			"SELECT A.ID, A.USER_ID, A.ITEM, A.ITEM_MD5, ".
			"	".$DB->DateToCharFunction("A.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("A.DATE_INSERT", "FULL")." as DATE_INSERT ".
			"FROM b_sale_auxiliary A ".
			"WHERE A.ID = ".$ID." ";

		$dbAuxiliary = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arAuxiliary = $dbAuxiliary->Fetch())
			return $arAuxiliary;

		return false;
	}

	
	/**
	* <p>Метод выбирает параметры информации о временном доступе к ресурсу с идентификатором itemMD5 для пользователя с кодом userID. Метод динамичный.</p>
	*
	*
	* @param int $userID  Код пользователя. </h
	*
	* @param string $itemMD5  Идентификатор ресурса (строка, однозначно идентифицирующая
	* ресурс).
	*
	* @return array <p>Метод возвращает ассоциативный массив параметров информации о
	* временном доступе с ключами:</p> <ul> <li> <b>ID</b> - код записи</li> <li>
	* <b>USER_ID</b> - код пользователя</li> <li> <b>ITEM</b> - ресурс, доступ к которому
	* разрешен</li> <li> <b>ITEM_MD5</b> - идентификатор ресурса (строка,
	* однозначно идентифицирующая ресурс)</li> <li> <b>TIMESTAMP_X</b> - дата
	* изменения</li> <li> <b>DATE_INSERT</b> - дата вставки записи</li> </ul> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/csaleauxiliary.getbyparams.php
	* @author Bitrix
	*/
	public static function GetByParams($userID, $itemMD5)
	{
		global $DB;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		$itemMD5 = Trim($itemMD5);
		if (strlen($itemMD5) <= 0)
			return false;

		$itemMD5 = md5($itemMD5);

		$strSql = 
			"SELECT A.ID, A.USER_ID, A.ITEM, A.ITEM_MD5, ".
			"	".$DB->DateToCharFunction("A.TIMESTAMP_X", "FULL")." as TIMESTAMP_X, ".
			"	".$DB->DateToCharFunction("A.DATE_INSERT", "FULL")." as DATE_INSERT ".
			"FROM b_sale_auxiliary A ".
			"WHERE A.USER_ID = ".$userID." ".
			"	AND A.ITEM_MD5 = '".$DB->ForSql($itemMD5)."' ";

		$dbAuxiliary = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arAuxiliary = $dbAuxiliary->Fetch())
			return $arAuxiliary;

		return false;
	}

	
	/**
	* <p>Метод возвращает результат выборки записей информации о временном доступе в соответствии со своими параметрами. Метод динамичный.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> В качестве "название_поля<i>N</i>"
	* может стоять любое поле информации о временном доступе, а в
	* качестве "направление_сортировки<span lang="en-us"><span class="style1">N</span></span>"
	* могут быть значения "<i>ASC</i>" (по возрастанию) и "<i>DESC</i>" (по
	* убыванию).<br><br> Если массив сортировки имеет несколько элементов,
	* то результирующий набор сортируется последовательно по каждому
	* элементу (т.е. сначала сортируется по первому элементу, потом
	* результат сортируется по второму и т.д.). <br><br> Значение по
	* умолчанию - пустой массив array() - означает, что результат
	* отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи информации о
	* временном доступе. Массив имеет вид: <pre class="syntax">array(
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
	* </ul> В качестве "название_поля<span lang="en-us">N</span>" может стоять любое
	* поле информации о временном доступе.<br><br> Пример фильтра: <pre
	* class="syntax">array("USER_ID" =&gt; 150)</pre> Этот фильтр означает "выбрать все
	* записи, в которых значение в поле USER_ID (код пользователя) равно
	* 150".<br><br> Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи информации о
	* временном доступе. Массив имеет вид: <pre
	* class="syntax">array("название_поля1", "группирующая_функция2" =&gt;
	* "название_поля2", . . .)</pre> В качестве "название_поля<i>N</i>" может
	* стоять любое поле информации о временном доступе. В качестве
	* группирующей функции могут стоять: <ul> <li> <b> COUNT</b> - подсчет
	* количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li> <li>
	* <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру.<br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
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
	* ассоциативных массивов параметров информации о временном
	* доступе с ключами:</p> <ul> <li> <b>ID</b> - код записи; </li> <li> <b>USER_ID</b> - код
	* пользователя; </li> <li> <b>ITEM</b> - ресурс, доступ к которому разрешен;
	* </li> <li> <b>ITEM_MD5</b> - идентификатор ресурса (строка, однозначно
	* идентифицирующая ресурс); </li> <li> <b>TIMESTAMP_X</b> - дата изменения; </li> <li>
	* <b>DATE_INSERT</b> - дата вставки записи.</li> </ul> <p>Если в качестве параметра
	* arGroupBy передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/csaleauxiliary.getlist.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_ID", "TIMESTAMP_X", "ITEM", "ITEM_MD5", "DATE_INSERT");

		// FIELDS -->
		$arFields = array(
				"ID" => array("FIELD" => "A.ID", "TYPE" => "int"),
				"USER_ID" => array("FIELD" => "A.USER_ID", "TYPE" => "int"),
				"TIMESTAMP_X" => array("FIELD" => "A.TIMESTAMP_X", "TYPE" => "datetime"),
				"ITEM" => array("FIELD" => "A.ITEM", "TYPE" => "string"),
				"ITEM_MD5" => array("FIELD" => "A.ITEM_MD5", "TYPE" => "string", "WHERE" => array("CSaleAuxiliary", "PrepareItemMD54Where")),
				"DATE_INSERT" => array("FIELD" => "A.DATE_INSERT", "TYPE" => "datetime")
			);
		// <-- FIELDS

		$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_auxiliary A ".
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
			"FROM b_sale_auxiliary A ".
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
				"FROM b_sale_auxiliary A ".
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

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br><br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}


	
	/**
	* <p>Метод удаляет всю информацию о временном доступе, которая старше указанного времени. Метод динамичный.</p>
	*
	*
	* @param int $periodLength  Длина периода времени, в течение которого пользователь имеет
	* доступ к ресурсу.
	*
	* @param sring $periodType  Тип длины периода времени, в течение которого пользователь имеет
	* доступ к ресурсу. Допустимые значения: <ul> <li>I - минута;</li> <li>H -
	* час;</li> <li>D - сутки;</li> <li>W - неделя;</li> <li>M - месяц;</li> <li>Q - квартал;</li>
	* <li>S - полугодие;</li> <li>Y - год.</li> </ul>
	*
	* @return bool <p><i>true</i> в случае успешного удаления и <i>false</i> в противном
	* случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Удалим все записи старше 2 дней
	* CSaleAuxiliary::DeleteByTime(2, "D");
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/csaleauxiliary.deletebytime.php
	* @author Bitrix
	*/
	public static function DeleteByTime($periodLength, $periodType)
	{
		global $DB;

		$periodLength = IntVal($periodLength);
		if ($periodLength <= 0)
			return False;

		$periodType = Trim($periodType);
		$periodType = ToUpper($periodType);
		if (strlen($periodType) <= 0)
			return False;

		$deleteVal = 0;
		if ($periodType == "I")
			$deleteVal = mktime(date("H"), date("i") - $periodLength, date("s"), date("m"), date("d"), date("Y"));
		elseif ($periodType == "H")
			$deleteVal = mktime(date("H") - $periodLength, date("i"), date("s"), date("m"), date("d"), date("Y"));
		elseif ($periodType == "D")
			$deleteVal = mktime(date("H"), date("i"), date("s"), date("m"), date("d") - $periodLength, date("Y"));
		elseif ($periodType == "W")
			$deleteVal = mktime(date("H"), date("i"), date("s"), date("m"), date("d") - 7 * $periodLength, date("Y"));
		elseif ($periodType == "M")
			$deleteVal = mktime(date("H"), date("i"), date("s"), date("m") - $periodLength, date("d"), date("Y"));
		elseif ($periodType == "Q")
			$deleteVal = mktime(date("H"), date("i"), date("s"), date("m") - 3 * $periodLength, date("d"), date("Y"));
		elseif ($periodType == "S")
			$deleteVal = mktime(date("H"), date("i"), date("s"), date("m") - 6 * $periodLength, date("d"), date("Y"));
		elseif ($periodType == "Y")
			$deleteVal = mktime(date("H"), date("i"), date("s"), date("m"), date("d"), date("Y") - $periodLength);

		if ($deleteVal <= 0)
			return False;

		return $DB->Query("DELETE FROM b_sale_auxiliary WHERE DATE_INSERT < '".Date("Y-m-d H:i:s", $deleteVal)."' ", true);
	}

	
	/**
	* <p>Метод добавляет новую запись с информацией о временном доступе к ресурсу в соответствии с данными из массива <i>arFields</i>. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой информации о временном
	* доступе к ресурсу, ключами в котором являются названия
	* параметров, а значениями - соответствующие значения. Допустимые
	* ключи: <ul> <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>ITEM</b> - ресурс,
	* доступ к которому разрешен;</li> <li> <b>ITEM_MD5</b> - идентификатор ресурса
	* (строка, однозначно идентифицирующая ресурс);</li> <li> <b>DATE_INSERT</b> -
	* дата вставки записи.</li> </ul>
	*
	* @return int <p>Метод возвращает код вставленной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/csaleauxiliary.add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleAuxiliary::CheckFields("ADD", $arFields, 0))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_auxiliary", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0])>0) $arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1])>0) $arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$strSql =
			"INSERT INTO b_sale_auxiliary(".$arInsert[0].") ".
			"VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры записи с кодом ID информации о временном доступе к ресурсу в соответствии с данными из массива arFields. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код записи.
	*
	* @param array $arFields  Ассоциативный массив параметров новой информации о временном
	* доступе к ресурсу, ключами в котором являются названия
	* параметров, а значениями - соответствующие значения. Допустимые
	* ключи: <ul> <li> <b>USER_ID</b> - код пользователя;</li> <li> <b>ITEM</b> - ресурс,
	* доступ к которому разрешен;</li> <li> <b>ITEM_MD5</b> - идентификатор ресурса
	* (строка, однозначно идентифицирующая ресурс);</li> <li> <b>DATE_INSERT</b> -
	* дата вставки записи.</li> </ul>
	*
	* @return int <p>Метод возвращает код измененной записи или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaleauxiliary/csaleauxiliary.update.php
	* @author Bitrix
	*/
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSaleAuxiliary::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sale_auxiliary", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		$strSql = "UPDATE b_sale_auxiliary SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}
}
?>