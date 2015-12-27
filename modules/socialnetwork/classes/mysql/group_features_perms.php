<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/group_features_perms.php");


/**
 * <b>CSocNetFeaturesPerms</b> - класс для управления правами на доступ к дополнительному функционалу групп и пользователей. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/index.php
 * @author Bitrix
 */
class CSocNetFeaturesPerms extends CAllSocNetFeaturesPerms
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	
	/**
	* <p>Создает новое право.</p> <p><b>Примечание</b>: для установки параметров права может так же использоваться метод <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/SetPerm.php">CSocNetFeaturesPerms::SetPerm</a>.</p>
	*
	*
	* @param array $arFields  Массив значений параметров. Допустимые ключи:<br> FEATURE_ID - код
	* дополнительного функционала,<br> OPERATION_ID - код операции,<br> ROLE - роль.
	*
	* @return int <p>Код вставленной записи.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/SetPerm.php">CSocNetFeaturesPerms::SetPerm</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/Update.php">CSocNetFeaturesPerms::Update</a></li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/Add.php
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

		if (!CSocNetFeaturesPerms::CheckFields("ADD", $arFields))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetFeaturesPermsAdd");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($arFields))===false)
				return false;

		$arInsert = $DB->PrepareInsert("b_sonet_features2perms", $arFields);

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
				"INSERT INTO b_sonet_features2perms(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			$events = GetModuleEvents("socialnetwork", "OnSocNetFeaturesPermsAdd");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			if (
				intval($arFields["FEATURE_ID"]) > 0
				&& defined("BX_COMP_MANAGED_CACHE")
			)
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_feature_".$arFields["FEATURE_ID"]);
		}

		return $ID;
	}

	
	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Возвращает список прав.</p>
	*
	*
	* @param array $arOrder = array("ID" Порядок сортировки возвращаемого списка, заданный в виде
	* массива. Ключами в массиве являются поля для сортировки, а
	* значениями - ASC/DESC - порядок сортировки. Допустимые ключи: <b>ID</b>,
	* <b>FEATURE_ID</b>, <b>OPERATION_ID, ROLE</b>, <b>FEATURE_ENTITY_TYPE</b>, <b>FEATURE_ENTITY_ID</b>,
	* <b>FEATURE_FEATURE</b>, <b>FEATURE_FEATURE_NAME</b>,<b> FEATURE_ACTIVE</b>.
	*
	* @param DES $C  Массив, задающий фильтр на возвращаемый список. Ключами в массиве
	* являются названия полей, а значениями - их значения. Допустимые
	* поля:<b>ID</b>, <b>FEATURE_ID</b>, <b>OPERATION_ID, ROLE</b>, <b>FEATURE_ENTITY_TYPE</b>, <b>FEATURE_ENTITY_ID</b>,
	* <b>FEATURE_FEATURE</b>, <b>FEATURE_FEATURE_NAME</b>,<b> FEATURE_ACTIVE</b>.
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
	* поля <b>ID</b>, <b>FEATURE_ID</b>, <b>OPERATION_ID</b>, <b>ROLE</b>. В массиве допустимы любые
	* поля из списка полей.
	*
	* @param array $arSelectFields = array() 
	*
	* @return CDBResult <p>Метод возвращает объект типа CDBResult, содержащий записи,
	* удовлетворяющие условию выборки.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://dev.1c-bitrix.ru/api_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/GetByID.php">CSocNetFeaturesPerms::GetById</a>
	* </li> </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeaturesperms/GetList.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "FEATURE_ID", "OPERATION_ID", "ROLE");

		static $arFields = array(
			"ID" => Array("FIELD" => "GFP.ID", "TYPE" => "int"),
			"FEATURE_ID" => Array("FIELD" => "GFP.FEATURE_ID", "TYPE" => "int"),
			"OPERATION_ID" => Array("FIELD" => "GFP.OPERATION_ID", "TYPE" => "string"),
			"ROLE" => Array("FIELD" => "GFP.ROLE", "TYPE" => "string"),
			"FEATURE_ENTITY_TYPE" => Array("FIELD" => "GF.ENTITY_TYPE", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_features GF ON (GFP.FEATURE_ID = GF.ID)"),
			"FEATURE_ENTITY_ID" => Array("FIELD" => "GF.ENTITY_ID", "TYPE" => "int", "FROM" => "INNER JOIN b_sonet_features GF ON (GFP.FEATURE_ID = GF.ID)"),
			"FEATURE_FEATURE" => Array("FIELD" => "GF.FEATURE", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_features GF ON (GFP.FEATURE_ID = GF.ID)"),
			"FEATURE_FEATURE_NAME" => Array("FIELD" => "GF.FEATURE_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_features GF ON (GFP.FEATURE_ID = GF.ID)"),
			"FEATURE_ACTIVE" => Array("FIELD" => "GF.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_features GF ON (GFP.FEATURE_ID = GF.ID)"),
		);

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_features2perms GFP ".
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
			"FROM b_sonet_features2perms GFP ".
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
				"FROM b_sonet_features2perms GFP ".
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
	
public static 	function GetAvaibleEntity($entityType, $feature, $role, $operation, $active, $visible, $siteID)
	{
		global $DB;
		
		if(Strlen($entityType) <= 0 || Strlen($role) <= 0 || Strlen($operation) <= 0)
			return false;
		if(Strlen($entityType) <= 0)
			$entityType = "G";
		if(Strlen($active) <= 0)
			$active = "Y";
		if(Strlen($visible) <= 0)
			$visible = "Y";
		if(Strlen($siteID) <= 0)
			$siteID = SITE_ID;		
			
		$strSql = "select b.ID as ID,
					b.ENTITY_TYPE as ENTITY_TYPE,
					b.ENTITY_ID as ENTITY_ID,
					b.FEATURE as FEATURE,
					b.ACTIVE as FEATURE_ACTIVE,
					p.OPERATION_ID as OPERATION_ID,
					p.ROLE as ROLE ";
		if($entityType == "G")
			$strSql .= ", g.SITE_ID as GROUP_SITE_ID,
					g.NAME as GROUP_NAME,
					g.VISIBLE as GROUP_VISIBLE,
					g.OWNER_ID as GROUP_OWNER_ID ";
		$strSql .= " from b_sonet_features b ".
					"LEFT JOIN b_sonet_features2perms p ON (b.ID = p.FEATURE_ID AND ". 
					"p.ROLE = '".$DB->ForSQL($role)."' AND p.OPERATION_ID = '".$DB->ForSQL($operation)."') ";
		if($entityType == "G")
			$strSql .= "INNER JOIN b_sonet_group g ON (g.ID = b.ENTITY_ID) ";
		$strSql .= "WHERE ".
					"b.FEATURE='".$DB->ForSQL($feature)."' AND ".
					"b.ACTIVE = '".$DB->ForSQL($active)."' AND ".
					"b.ENTITY_TYPE = '".$DB->ForSQL($entityType)."' ";

		if($entityType == "G")
			$strSql .= " AND g.ACTIVE = 'Y' AND ".
						"g.VISIBLE= 'Y' AND ". 
						"g.SITE_ID= '".$DB->ForSQL($siteID)."'";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $dbRes;
	}
}
?>