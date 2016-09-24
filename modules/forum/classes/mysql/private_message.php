<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/private_message.php");

class CForumPrivateMessage extends CAllForumPrivateMessage
{
	public static function GetListEx($arOrder = Array("ID"=>"ASC"), $arFilter = Array(), $bCount = false, $iNum = 0, $arAddParams = array())
	{
		global $DB;
		$arSqlSearch = array();
		$arSqlOrder = array();
		$strSqlSearch = "";
		$strSqlOrder = "";
		$strSqlFrom = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		if (is_array($bCount) && empty($arAddParams)){
			$arAddParams = $bCount;
			$bCount = false;
			$iNum = 0;
		}
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));
		if (is_set($arAddParams, "nameTemplate"))
			$arAddParams["sNameTemplate"] = $arAddParams["nameTemplate"];
		$arAddParams["bCount"] = (!!$bCount || !!$arAddParams["bCount"]);
		$arAddParams["nTopCount"] = ($iNum > 0 ? $iNum : ($arAddParams["nTopCount"] > 0 ? $arAddParams["nTopCount"] : 0));

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "ID":
				case "FOLDER_ID":
				case "AUTHOR_ID":
				case "RECIPIENT_ID":
				case "USER_ID":
					if (IntVal($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(PM.".$key." IS NULL OR PM.".$key."<=0)";
					elseif (strToUpper($strOperation) == "IN")
						$arSqlSearch[] = ($strNegative=="Y"?" PM.".$key." IS NULL OR NOT ":"")."(PM.".$key." ".$strOperation." (".IntVal($val).") )";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" PM.".$key." IS NULL OR NOT ":"")."(PM.".$key." ".$strOperation." ".IntVal($val)." )";
					break;
				case "OWNER_ID":
					if (COption::GetOptionString("forum", "UsePMVersion", "2") == 2)
					{
						$user_id = 0;
						if (is_array($val) && intVal($val["USER_ID"]) > 0)
							$user_id = intVal($val["USER_ID"]);
						else
							$user_id = intVal($val);
						$arSqlSearch[] = 
							"(PM.USER_ID=".$user_id." AND ((PM.FOLDER_ID=2) OR (PM.FOLDER_ID=3)))";
					}
					else 
					{
						$arSqlSearch[] = 
							"((PM.AUTHOR_ID=".intVal($val).") AND (PM.IS_READ='N')) OR (PM.USER_ID=".intVal($val)." AND (PM.FOLDER_ID=2))";
					}
					break;
				case "POST_SUBJ":
				case "POST_MESSAGE":
				case "USE_SMILES":
				case "IS_READ":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(PM.".$key." IS NULL OR LENGTH(PM.".$key.")<=0)";
					elseif (strToUpper($strOperation) == "IN")
						$arSqlSearch[] = ($strNegative=="Y"?" PM.".$key." IS NULL OR NOT ":"")."(PM.".$key." ".$strOperation." ('".$DB->ForSql($val)."') )";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" PM.".$key." IS NULL OR NOT ":"")."(PM.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
				break;
				case "POST_DATE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(PM.".$key." IS NULL OR LENGTH(PM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" PM.".$key." IS NULL OR NOT ":"")."(PM.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL")." )";
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";

		$iCnt = 0;
		if ($arAddParams["bCount"] || is_set($arAddParams, "bDescPageNumbering"))
		{
			$strSql = "SELECT COUNT(PM.ID) AS CNT FROM b_forum_private_message PM WHERE (1=1) ".$strSqlSearch;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = ($db_res && ($res = $db_res->Fetch()) ? intval($res["CNT"]) : 0);
			if ($arAddParams["bCount"])
				return $iCnt;
		}

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			
			if ($by == "AUTHOR_NAME") $arSqlOrder[] = " AUTHOR_NAME ".$order." ";
			elseif ($by == "RECIPIENT_NAME") $arSqlOrder[] = " RECIPIENT_NAME ".$order." ";
			elseif ($by == "AUTHOR_ID") $arSqlOrder[] = " PM.AUTHOR_ID ".$order." ";
			elseif ($by == "RECIPIENT_ID") $arSqlOrder[] = " PM.RECIPIENT_ID ".$order." ";
			elseif ($by == "POST_DATE") $arSqlOrder[] = " PM.POST_DATE ".$order." ";
			elseif ($by == "POST_SUBJ") $arSqlOrder[] = " PM.POST_SUBJ ".$order." ";
			elseif ($by == "POST_MESSAGE") $arSqlOrder[] = " PM.POST_MESSAGE ".$order." ";
			elseif ($by == "IS_READ") $arSqlOrder[] = " PM.IS_READ ".$order." ";
			elseif ($by == "USE_SMILES") $arSqlOrder[] = " PM.USE_SMILES ".$order." ";
			else
			{
				$arSqlOrder[] = " PM.POST_DATE ".$order." ";
				$by = "POST_DATE";
			}
		}
		DelDuplicateSort($arSqlOrder); 
		if(!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql = 
			"SELECT 
				PM.ID, PM.POST_SUBJ, PM.POST_MESSAGE, PM.FOLDER_ID, PM.IS_READ, PM.USE_SMILES, PM.REQUEST_IS_READ, 
				".$DB->DateToCharFunction("PM.POST_DATE", "FULL")." as POST_DATE, 
				PM.USER_ID,
				
				PM.AUTHOR_ID, U.EMAIL AS AUTHOR_EMAIL, U.LOGIN AS AUTHOR_LOGIN, 
				CASE
					WHEN ((FU.SHOW_NAME='Y') AND (LENGTH(TRIM(CONCAT_WS('',".CForumUser::GetNameFieldsForQuery($arAddParams["sNameTemplate"]).")))>0))
						THEN TRIM(REPLACE(CONCAT_WS(' ',".CForumUser::GetNameFieldsForQuery($arAddParams["sNameTemplate"])."), '  ', ' '))
						ELSE U.LOGIN
					END AS AUTHOR_NAME, 
				PM.RECIPIENT_ID, UU.EMAIL AS RECIPIENT_EMAIL, UU.LOGIN AS RECIPIENT_LOGIN,
				CASE
					WHEN ((FUU.SHOW_NAME='Y') AND (LENGTH(TRIM(CONCAT_WS('',".CForumUser::GetNameFieldsForQuery($arAddParams["sNameTemplate"],"UU.").")))>0))
					THEN TRIM(REPLACE(CONCAT_WS(' ',".CForumUser::GetNameFieldsForQuery($arAddParams["sNameTemplate"],"UU.")."), '  ', ' '))
					ELSE UU.LOGIN
				END AS RECIPIENT_NAME
			FROM b_forum_private_message PM
				LEFT JOIN b_forum_user FU ON (PM.AUTHOR_ID = FU.USER_ID)
				LEFT JOIN b_forum_user FUU ON (PM.RECIPIENT_ID = FUU.USER_ID)
				LEFT JOIN b_user U ON (PM.AUTHOR_ID = U.ID)
				LEFT JOIN b_user UU ON (PM.RECIPIENT_ID = UU.ID)
			WHERE 1=1 ".$strSqlSearch."
			".$strSqlOrder;

		if (is_set($arAddParams, "bDescPageNumbering")) {
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
		} else {
			if ($arAddParams["nTopCount"] > 0)
				$strSql .= " LIMIT 0,".$arAddParams["nTopCount"];
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $db_res;
	}
}

class CForumPMFolder extends CAllForumPMFolder
{
	// 
}
