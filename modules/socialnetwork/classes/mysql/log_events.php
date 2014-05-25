<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/socialnetwork/classes/general/log_events.php");

class CSocNetLogEvents extends CAllSocNetLogEvents
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
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

		if (!CSocNetLogEvents::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sonet_log_events", $arFields);

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
				"INSERT INTO b_sonet_log_events(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());
		}

		return $ID;
	}

	
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
			return false;
		}

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetLogEvents::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_log_events", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_log_events SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
			$ID = False;

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	public static function GetList($arOrder = Array("ID" => "DESC"), $arFilter = Array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arParams = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "USER_ID", "ENTITY_TYPE", "ENTITY_ID", "ENTITY_CB", "ENTITY_MY", "EVENT_ID", "SITE_ID", "MAIL_EVENT", "TRANSPORT", "VISIBLE");

		static $arFields1 = array(
			"ID" => Array("FIELD" => "LE.ID", "TYPE" => "int"),
			"USER_ID" => Array("FIELD" => "LE.USER_ID", "TYPE" => "int"),
			"ENTITY_TYPE" => Array("FIELD" => "LE.ENTITY_TYPE", "TYPE" => "string"),
			"ENTITY_ID" => Array("FIELD" => "LE.ENTITY_ID", "TYPE" => "int"),
			"ENTITY_CB" => Array("FIELD" => "LE.ENTITY_CB", "TYPE" => "string"),
			"ENTITY_MY" => Array("FIELD" => "LE.ENTITY_MY", "TYPE" => "string"),
			"EVENT_ID" => Array("FIELD" => "LE.EVENT_ID", "TYPE" => "string"),
			"SITE_ID" => Array("FIELD" => "LE.SITE_ID", "TYPE" => "string"),
			"MAIL_EVENT" => Array("FIELD" => "LE.MAIL_EVENT", "TYPE" => "string"),
			"TRANSPORT" => Array("FIELD" => "LE.TRANSPORT", "TYPE" => "string"),
			"VISIBLE" => Array("FIELD" => "LE.VISIBLE", "TYPE" => "string"),
			"USER_NAME" => Array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (LE.USER_ID = U.ID)"),
			"USER_LAST_NAME" => Array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (LE.USER_ID = U.ID)"),
			"USER_LOGIN" => Array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (LE.USER_ID = U.ID)"),
			"USER_LID" => Array("FIELD" => "U.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (LE.USER_ID = U.ID)"),
			"USER_EMAIL" => Array("FIELD" => "U.EMAIL", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (LE.USER_ID = U.ID)"),
			"USER_ACTIVE" => Array("FIELD" => "U.ACTIVE", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (LE.USER_ID = U.ID)"),
		);

		if (array_key_exists("GROUP_SITE_ID", $arFilter) || array_key_exists("COMMON_GROUP_SITE_ID", $arFilter))
		{
			$arFields["GROUP_SITE_ID"] = Array("FIELD" => "SGS.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G on G.ID = LE.ENTITY_ID LEFT JOIN b_sonet_group_site SGS on SGS.GROUP_ID = G.ID");
			$arFields["COMMON_GROUP_SITE_ID"] = Array("FIELD" => "SGS.SITE_ID", "TYPE" => "string_or_null", "FROM" => "LEFT JOIN b_sonet_group G ON G.ID = LE.ENTITY_ID LEFT JOIN b_sonet_group_site SGS on SGS.GROUP_ID = G.ID");

			$strDistinct = " DISTINCT ";
			foreach ($arSelectFields as $i => $strFieldTmp)
				if (in_array($strFieldTmp, array("GROUP_SITE_ID", "COMMON_GROUP_SITE_ID")))
					unset($arSelectFields[$i]);

			foreach ($arOrder as $by => $order)
				if (!in_array($by, $arSelectFields))
					$arSelectFields[] = $by;
		}
		else
		{
			$arFields["GROUP_SITE_ID"] = Array("FIELD" => "G.SITE_ID", "TYPE" => "string", "FROM" => "INNER JOIN b_sonet_group G on G.ID = LE.ENTITY_ID");
			$arFields["COMMON_GROUP_SITE_ID"] = Array("FIELD" => "G.SITE_ID", "TYPE" => "string_or_null", "FROM" => "LEFT JOIN b_sonet_group G ON G.ID = LE.ENTITY_ID");
			$strDistinct = " ";
		}

		$arFields = array_merge($arFields1, $arFields);

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		if (
			!empty($arParams) 
			&& array_key_exists("ENTITY_TYPE", $arParams)
			&& array_key_exists("ENTITY_ID", $arParams)
			&& array_key_exists("EVENT_ID", $arParams)
			&& (
				array_key_exists("TRANSPORT", $arParams)
				|| array_key_exists("VISIBLE", $arParams)				
			)
		)
			$arSqls["SUBSCRIBE"] = CSocNetLogEvents::GetSQLForEvent(
				$arParams["ENTITY_TYPE"],
				$arParams["ENTITY_ID"],
				$arParams["EVENT_ID"],
				$arParams["USER_ID"],
				$arParams["TRANSPORT"],
				$arParams["VISIBLE"],
				$arParams["OF_ENTITIES"]
			);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", $strDistinct, $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sonet_log_events LE ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
			{
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
				if (strlen($arSqls["SUBSCRIBE"]) > 0)
					$strSql .= $arSqls["SUBSCRIBE"]." ";				
			}
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
			"FROM b_sonet_log_events LE ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
		{
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["SUBSCRIBE"]) > 0)
				$strSql .= $arSqls["SUBSCRIBE"]." ";							
		}
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_sonet_log_events LE ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
			{
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
				if (strlen($arSqls["SUBSCRIBE"]) > 0)
					$strSql .= $arSqls["SUBSCRIBE"]." ";								
			}
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
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

public static 	function GetUserLogEvents($userID, $arFilter = array())
	{
		global $DB;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		$strWhere = "";
		if (is_array($arFilter) && count($arFilter) > 0)
		{
			foreach ($arFilter as $key => $value)
			{
				switch ($key)
				{
					case "ENTITY_TYPE":
						$strWhere .= " AND L.ENTITY_TYPE = '".$DB->ForSql($value, 1)."' ";
						break;
					case "ENTITY_ID":
						$strWhere .= " AND L.ENTITY_ID = ".IntVal($value)." ";
						break;
					case "EVENT_ID":
						if (!is_array($value))
							$strWhere .= " AND L.EVENT_ID = '".$DB->ForSql($value, 50)."' ";
						else
						{
							if (!function_exists('__tmp_str_apos'))
							{
public static 								function __tmp_str_apos(&$tmpval, $tmpind)
								{
									if (strlen($tmpval) > 0)
										$tmpval = "'".$GLOBALS["DB"]->ForSql($tmpval, 50)."'";
								}
							}
							array_walk($value, '__tmp_str_apos');
							$strWhere .= " AND L.EVENT_ID IN (".implode(", ", $value).") ";
						}
						break;
					case "LOG_DATE_DAYS":
						$strWhere .= " AND L.LOG_DATE >= DATE_SUB(NOW(), INTERVAL ".IntVal($value)." DAY) ";
						break;
					case "SITE_ID":
						if (!is_array($value)):
							$strWhere .= " AND L.SITE_ID = '".$DB->ForSql($value, 2)."' ";
						else:
							$counter = 0;
							$strWhere .= " AND (";
							foreach($value as $site_id):
								if ($site_id === false)
									$strWhere .= ($counter > 0 ? " OR" : "")." L.SITE_ID IS NULL ";
								else
									$strWhere .= ($counter > 0 ? " OR" : "")." L.SITE_ID = '".$DB->ForSql($site_id, 2)."' ";
								$counter++;
							endforeach;
							$strWhere .= ") ";
						endif;
						break;
				}
			}
		}

		$strSql = 
			"SELECT L.ID, L.ENTITY_TYPE, L.ENTITY_ID, L.EVENT_ID, L.LOG_DATE, L.SITE_ID as SITE_ID, ".
			"	".$DB->DateToCharFunction("L.LOG_DATE", "FULL")." as LOG_DATE_FORMAT, ".
			"	L.TITLE_TEMPLATE, L.TITLE, L.MESSAGE, L.URL, L.MODULE_ID, L.CALLBACK_FUNC, ".
			"	G.NAME as GROUP_NAME, G.OWNER_ID as GROUP_OWNER_ID, G.INITIATE_PERMS as GROUP_INITIATE_PERMS, ".
			"	G.VISIBLE as GROUP_VISIBLE, G.OPENED as GROUP_OPENED, ".
			"	U.NAME as USER_NAME, U.LAST_NAME as USER_LAST_NAME, U.SECOND_NAME as USER_SECOND_NAME, U.LOGIN as USER_LOGIN ".
			"FROM b_sonet_log L ";

		if (!Array_Key_Exists("ALL", $arFilter) || StrToUpper($arFilter["ALL"]) != "Y")
		{
			$strSql .= 
				"	INNER JOIN b_sonet_log_events LE ".
				"		ON (L.ENTITY_TYPE = LE.ENTITY_TYPE AND L.ENTITY_ID = LE.ENTITY_ID AND (L.EVENT_ID = LE.EVENT_ID OR ((L.EVENT_ID = 'blog_post' OR L.EVENT_ID = 'blog_comment' OR L.EVENT_ID = 'blog_post_micro') AND LE.EVENT_ID = 'blog'))) ";
		}
		$strSql .= 
			"	LEFT JOIN b_sonet_group G ".
			"		ON (L.ENTITY_TYPE = 'G' AND L.ENTITY_ID = G.ID) ".
			"	LEFT JOIN b_user U ".
			"		ON (L.ENTITY_TYPE = 'U' AND L.ENTITY_ID = U.ID) ".
			"WHERE 1 = 1 ";
		if (!Array_Key_Exists("ALL", $arFilter) || StrToUpper($arFilter["ALL"]) != "Y")
			$strSql .= "	AND LE.USER_ID = ".$userID." ";

		$strSql .= 
			$strWhere.
			"ORDER BY L.LOG_DATE DESC";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}
}
?>