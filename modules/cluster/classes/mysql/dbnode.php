<?
IncludeModuleLangFile(__FILE__);

class CClusterDBNode extends CAllClusterDBNode
{
	public static function CheckFields(&$arFields, $ID)
	{
		global $DB, $APPLICATION;
		$aMsg = array();

		if(array_key_exists("NAME", $arFields))
			$arFields["NAME"] = trim($arFields["NAME"]);

		if(array_key_exists("ACTIVE", $arFields))
			$arFields["ACTIVE"] = $arFields["ACTIVE"] === "Y"? "Y": "N";

		if(array_key_exists("SELECTABLE", $arFields))
			$arFields["SELECTABLE"] = $arFields["SELECTABLE"] == "N"? "N": "Y";

		if(array_key_exists("WEIGHT", $arFields))
		{
			$weight = intval($arFields["WEIGHT"]);
			if($weight < 0)
				$weight = 0;
			elseif($weight > 100)
				$weight = 100;
			$arFields["WEIGHT"] = $weight;
		}

		if($arFields["ACTIVE"] == "Y" && $arFields["ROLE_ID"] != "SLAVE")
		{
			$obCheck = new CClusterDBNodeCheck;
			$nodeDB = $obCheck->SlaveNodeConnection(
				$arFields["DB_HOST"],
				$arFields["DB_NAME"],
				$arFields["DB_LOGIN"],
				$arFields["DB_PASSWORD"],
				$arFields["ROLE_ID"] == "MASTER"? $arFields["MASTER_HOST"]: false,
				$arFields["ROLE_ID"] == "MASTER"? $arFields["MASTER_PORT"]: false
			);
			if(is_object($nodeDB))
			{
				$arFields["SERVER_ID"] = intval($obCheck->GetServerVariable($nodeDB, "server_id"));
			}
			else
			{
				if(!array_key_exists("STATUS", $arFields))
					$arFields["STATUS"] = "OFFLINE";
				$aMsg[] = array("id" => "", "text" => $nodeDB);
			}
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function GetUpTime($node_id)
	{
		if($node_id > 1)
		{
			ob_start();
			$DB = CDatabase::GetDBNodeConnection($node_id, true, false);
			ob_end_clean();
		}
		else
		{
			$DB = $GLOBALS["DB"];
		}

		if(is_object($DB))
		{
			$rs = $DB->Query("show status like 'Uptime'", false, '', array('fixed_connection'=>true));
			if($ar = $rs->Fetch())
				return $ar["Value"];
		}

		return false;
	}
}
?>