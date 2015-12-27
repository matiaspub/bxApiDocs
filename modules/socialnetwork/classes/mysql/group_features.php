<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/group_features.php");


/**
 * <b>CSocNetFeatures</b> - класс для работы с дополнительным функционалом групп и пользователей социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/index.php
 * @author Bitrix
 */
class CSocNetFeatures extends CAllSocNetFeatures
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	
	/**
	* <p>Сохраняет в базу запись о новом дополнительном функционале пользователя или группы.</p>
	*
	*
	* @param array $arFields  Параметры дополнительного функционала.
	*
	* @return int <p>Код записи, если сохранение прошло успешно. False - в противном
	* случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/csocnetfeatures.add.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetFeatures::CheckFields("ADD", $arFields))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetFeaturesAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sonet_features", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($arInsert[0]) > 0)
				$arInsert[0] .= ", ";
			$arInsert[0] .= $key;
			if (strlen($arInsert[1]) > 0)
				$arInsert[1] .= ", ";
			$arInsert[1] .= $value;
		}

		$ID = false;
		if (strlen($arInsert[0]) > 0)
		{
			$strSql =
				"INSERT INTO b_sonet_features(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			if (array_key_exists("ENTITY_TYPE", $arFields) && array_key_exists("ENTITY_ID", $arFields))
				unset($GLOBALS["SONET_FEATURES_CACHE"][$arFields["ENTITY_TYPE"]][$arFields["ENTITY_ID"]]);

			$events = GetModuleEvents("socialnetwork", "OnSocNetFeaturesAdd");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			if (
				defined("BX_COMP_MANAGED_CACHE")
				&& array_key_exists("ENTITY_TYPE", $arFields) 
				&& array_key_exists("ENTITY_ID", $arFields)
			)
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_features_".$arFields["ENTITY_TYPE"]."_".$arFields["ENTITY_ID"]);
		}

		return $ID;
	}

	
	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Выбирает список дополнительного функционала в соответствии с фильтром.</p>
	*
	*
	* @param array $arOrder = array("ID" Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки. Допустимые ключи:<b>ID</b>,
	* <b>ENTITY_TYPE</b>, <b>ENTITY_ID</b>, <b>FEATURE</b>, <b>FEATURE_NAME</b>, <b>ACTIVE</b>, <b>DATE_CREATE</b>,
	* <b>DATE_UPDATE</b>.
	*
	* @param DES $C  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения. Допустимае
	* поля: <b>ID</b>, <b>ENTITY_TYPE</b>, <b>ENTITY_ID</b>, <b>FEATURE</b>, <b>FEATURE_NAME</b>, <b>ACTIVE</b>,
	* <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>.
	*
	* @param array $arFilter = array() Массив, задающий группировку результирующего списка. Если
	* параметр содержит массив названий полей, то по этим полям будет
	* произведена группировка. Если параметр содержит пустой массив,
	* то метод вернет количество записей, удовлетворяющих фильтру. По
	* умолчанию параметр равен false - не группировать.
	*
	* @param array $arGroupBy = false Массив, задающий условия выбора для организации постраничной
	* навигации.
	*
	* @param array $arNavStartParams = false Массив, задающий выбираемые поля. Содержит список полей, которые
	* должны быть возвращены методом. Если массив пустой, то выбираются
	* поля <b>ID</b>, <b>ENTITY_TYPE</b>, <b>ENTITY_ID</b>, <b>FEATURE</b>, <b>FEATURE_NAME</b>, <b>ACTIVE</b>,
	* <b>DATE_CREATE</b>, <b>DATE_UPDATE</b>. В массиве допустимы любые поля из списка
	* полей.
	*
	* @param array $arSelectFields = array() 
	*
	* @return CDBResult <p>Метод возвращает объект типа CDBResult, содержащий записи,
	* удовлетворяющие условию выборки.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем в массив весь дополнительный функционал группы с идентификатором $ID
	* $arFeaturesTmp = array(); 
	* $dbResultTmp = CSocNetFeatures::GetList( 
	*    array(), 
	*    array("ENTITY_ID" =&gt; $ID, "ENTITY_TYPE" =&gt; SONET_ENTITY_GROUP) 
	* ); 
	* while ($arResultTmp = $dbResultTmp-&gt;GetNext()) 
	*    $arFeaturesTmp[$arResultTmp["FEATURE"]] = $arResultTmp; 
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> </ul><a
	* name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/GetList.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "ENTITY_TYPE", "ENTITY_ID", "FEATURE", "FEATURE_NAME", "ACTIVE", "DATE_CREATE", "DATE_UPDATE");

		static $arFields = array(
			"ID" => Array("FIELD" => "GF.ID", "TYPE" => "int"),
			"ENTITY_TYPE" => Array("FIELD" => "GF.ENTITY_TYPE", "TYPE" => "string"),
			"ENTITY_ID" => Array("FIELD" => "GF.ENTITY_ID", "TYPE" => "int"),
			"FEATURE" => Array("FIELD" => "GF.FEATURE", "TYPE" => "string"),
			"FEATURE_NAME" => Array("FIELD" => "GF.FEATURE_NAME", "TYPE" => "string"),
			"ACTIVE" => Array("FIELD" => "GF.ACTIVE", "TYPE" => "string"),
			"DATE_CREATE" => Array("FIELD" => "GF.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_UPDATE" => Array("FIELD" => "GF.DATE_UPDATE", "TYPE" => "datetime"),
			"GROUP_NAME" => Array("FIELD" => "G.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G ON (GF.GROUP_ID = G.ID)"),
		);

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_features GF ".
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
			"FROM b_sonet_features GF ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_features GF ".
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
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.2!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}
}
?>