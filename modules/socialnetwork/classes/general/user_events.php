<?
IncludeModuleLangFile(__FILE__);

class CAllSocNetUserEvents
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $arSocNetUserEvents;

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && IntVal($arFields["USER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UE_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UE_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if ((is_set($arFields, "EVENT_ID") || $ACTION=="ADD") && strlen($arFields["EVENT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UE_EMPTY_EVENT_ID"), "EMPTY_EVENT_ID");
			return false;
		}
		elseif (is_set($arFields, "EVENT_ID") && !in_array($arFields["EVENT_ID"], $arSocNetUserEvents))
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["EVENT_ID"], GetMessage("SONET_UE_ERROR_NO_EVENT_ID")), "ERROR_NO_EVENT_ID");
			return false;
		}

		if ((is_set($arFields, "SITE_ID") || $ACTION=="ADD") && strlen($arFields["SITE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UE_EMPTY_SITE_ID"), "EMPTY_SITE_ID");
			return false;
		}
		elseif (is_set($arFields, "SITE_ID"))
		{
			$dbResult = CSite::GetByID($arFields["SITE_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["SITE_ID"], GetMessage("SONET_UE_ERROR_NO_SITE")), "ERROR_NO_SITE");
				return false;
			}
		}

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"] != "Y" && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_user_events WHERE ID = ".$ID."", true);

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($userID))
			return false;

		$userID = IntVal($userID);
		$bSuccess = True;

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_user_events WHERE USER_ID = ".$userID."", true);

		return $bSuccess;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetUserEvents::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_user_events", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_user_events SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$ID = False;
		}

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	public static function GetByID($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$dbResult = CSocNetUserEvents::GetList(Array(), Array("ID" => $ID));
		if ($arResult = $dbResult->GetNext())
		{
			return $arResult;
		}

		return False;
	}
	
	/***************************************/
	/**********  COMMON METHODS  ***********/
	/***************************************/
	public static function GetEventSite($userID, $event, $defSiteID)
	{
		global $arSocNetUserEvents;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;
		$event = StrToUpper(Trim($event));
		if (!in_array($event, $arSocNetUserEvents))
			return false;

		$arUserEvents = array();
		if (isset($GLOBALS["SONET_USER_EVENTS_".$userID]) && is_array($GLOBALS["SONET_USER_EVENTS_".$userID]) && !in_array("SONET_USER_EVENTS_".$userID, $_REQUEST))
		{
			$arUserEvents = $GLOBALS["SONET_USER_EVENTS_".$userID];
		}
		else
		{
			$dbResult = CSocNetUserEvents::GetList(Array(), Array("USER_ID" => $userID));
			while ($arResult = $dbResult->Fetch())
				$arUserEvents[$arResult["EVENT_ID"]] = (($arResult["ACTIVE"] == "Y") ? $arResult["SITE_ID"] : false);
			$GLOBALS["SONET_USER_EVENTS_".$userID] = $arUserEvents;
		}

		if (!array_key_exists($event, $arUserEvents))
			return $defSiteID;

		return $arUserEvents[$event];
	}
}
?>
