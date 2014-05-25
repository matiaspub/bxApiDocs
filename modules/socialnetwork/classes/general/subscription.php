<?
class CAllSocNetSubscription
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB;

		if (
			$ACTION != "ADD" 
			&& IntVal($ID) <= 0
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if (
			(is_set($arFields, "USER_ID") || $ACTION == "ADD") 
			&& IntVal($arFields["USER_ID"]) <= 0
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_SS_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_SS_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if (
			(is_set($arFields, "CODE") || $ACTION == "ADD") 
			&& strlen(trim($arFields["CODE"])) <= 0
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_SS_EMPTY_CODE"), "EMPTY_CODE");
			return false;
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$bSuccess = $DB->Query("DELETE FROM b_sonet_subscription WHERE ID = ".$ID."", true);

		return $bSuccess;
	}

	public static function DeleteEx($userID = false, $code = false)
	{
		global $DB;

		$userID = IntVal($userID);
		$code = trim($code);
		
		if (
			$userID <= 0
			&& strlen($code) <= 0
		)
			return false;

		$DB->Query("DELETE FROM b_sonet_subscription WHERE 1=1 ".
			(intval($userID) > 0 ? "AND USER_ID = ".$userID." " : "").
			(strlen($code) > 0 ? "AND CODE = '".$code."' " : "")
		, true);

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			if ($code)
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_subscription_".$code);
			else
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_subscription");
		}

		return true;
	}
	
	public static function Set($userID, $code, $value = false)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($userID))
			return false;

		$userID = IntVal($userID);
		$code = trim($code);
		
		if (
			$userID <= 0
			|| strlen($code) <= 0
		)
			return false;

		$value = ($value == "Y" ? "Y" : "N");

		$rsSubscription = CSocNetSubscription::GetList(
			array(),
			array(
				"USER_ID" => $userID, 
				"CODE" => $code
			)
		);

		if ($arSubscription = $rsSubscription->Fetch())
		{
			if ($value != "Y")
				CSocNetSubscription::Delete($arSubscription["ID"]);
		}
		else
		{
			if ($value == "Y")
				CSocNetSubscription::Add(array(
					"USER_ID" => $userID,
					"CODE" => $code
				));
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_subscription_".$code);

		return true;
	}	

	public static function NotifyGroup($arFields)
	{
		if (!CModule::IncludeModule("im"))
			return;

		if (!is_array($arFields["GROUP_ID"]))
			$arFields["GROUP_ID"] = array($arFields["GROUP_ID"]);

		if (empty($arFields["GROUP_ID"]))
			return;

		if (empty($arFields["EXCLUDE_USERS"]))
			$arFields["EXCLUDE_USERS"] = array();

		if (intval($arFields["LOG_ID"]) > 0)
		{
			$rsUnFollower = CSocNetLogFollow::GetList(
				array(
					"CODE" => "L".intval($arFields["LOG_ID"]),
					"TYPE" => "N"
				),
				array("USER_ID")
			);

			while ($arUnFollower = $rsUnFollower->Fetch())
				$arFields["EXCLUDE_USERS"][] = $arUnFollower["USER_ID"];

			$arFields["EXCLUDE_USERS"] = array_unique($arFields["EXCLUDE_USERS"]);
		}

		$arMessageFields = array(
			"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
			"NOTIFY_TYPE" => IM_NOTIFY_FROM,
			"NOTIFY_MODULE" => "socialnetwork",
			"NOTIFY_EVENT" => "sonet_group_event",
			"NOTIFY_TAG" => "SONET|EVENT|".(intval($arFields["LOG_ID"]) > 0 ? $arFields["LOG_ID"] : rand())
		);

		if (intval($arFields["FROM_USER_ID"]) > 0)
			$arMessageFields["FROM_USER_ID"] = $arFields["FROM_USER_ID"];

		$arUserToSend = array();
		$arUserIDToSend = array();
		$arGroupID = array();
		$arCodes = array();

		foreach ($arFields["GROUP_ID"] as $group_id)
			$arCodes[] = "SG".$group_id;

		$rsSubscriber = CSocNetSubscription::GetList(
			array(),
			array(
				"CODE" => $arCodes
			),
			false,
			false,
			array("USER_ID", "CODE")
		);

		while($arSubscriber = $rsSubscriber->Fetch())
		{
			if (
				!in_array($arSubscriber["USER_ID"], $arFields["EXCLUDE_USERS"])
				&& !in_array($arSubscriber["USER_ID"], $arUserIDToSend)
			)
			{
				if (preg_match('/^SG(\d+)$/', $arSubscriber["CODE"], $matches))
				{
					$arUserToSend[] = array(
						"USER_ID" => $arSubscriber["USER_ID"],
						"GROUP_ID" => $matches[1]
					);
					$arUserIDToSend[] = $arSubscriber["USER_ID"];
					$arGroupID[] = $matches[1];
				}
			}
		}

		$rsGroup = CSocNetGroup::GetList(
			array(),
			array("ID" => $arGroupID),
			false,
			false,
			array("ID", "NAME", "OWNER_ID")
		);

		while($arGroup = $rsGroup->GetNext())
			$arGroups[$arGroup["ID"]] = $arGroup;

		$workgroupsPage = COption::GetOptionString("socialnetwork", "workgroups_page", "/workgroups/", SITE_ID);
		$groupUrlTemplate = COption::GetOptionString("socialnetwork", "group_path_template", "/workgroups/group/#group_id#/", SITE_ID);
		$groupUrlTemplate = "#GROUPS_PATH#".substr($groupUrlTemplate, strlen($workgroupsPage), strlen($groupUrlTemplate)-strlen($workgroupsPage));

		foreach($arUserToSend as $arUser)
		{
			$arMessageFields["TO_USER_ID"] = $arUser["USER_ID"];
			$arTmp = CSocNetLogTools::ProcessPath(
				array(
					"URL" => $arFields["URL"],
					"GROUP_URL" => str_replace(array("#group_id#", "#GROUP_ID#"), $arUser["GROUP_ID"], $groupUrlTemplate)
				),
				$arUser["USER_ID"]
			);
			$url = $arTmp["URLS"]["URL"];

			if (
				strpos($url, "http://") === 0
				|| strpos($url, "https://") === 0
			)
				$serverName = "";
			else
				$serverName = $arTmp["SERVER_NAME"];

			$groupUrl = $serverName.$arTmp["URLS"]["GROUP_URL"];

			$group_name = (array_key_exists($arUser["GROUP_ID"], $arGroups) ? $arGroups[$arUser["GROUP_ID"]]["NAME"] : "");
			$arMessageFields["NOTIFY_MESSAGE"] = str_replace(
				array("#URL#", "#url#", "#group_name#", "#GROUP_ID#", "#group_id#"), 
				array($url, $url, "<a href=\"".$groupUrl."\" class=\"bx-notifier-item-action\">".$group_name."</a>", $arUser["GROUP_ID"], $arUser["GROUP_ID"]),
				$arFields["MESSAGE"]
			);
			$arMessageFields["NOTIFY_MESSAGE_OUT"] = str_replace(
				array("#URL#", "#url#", "#group_name#"), 
				array($serverName.$url, $serverName.$url, $group_name),
				$arFields["MESSAGE_OUT"]
			);

			$arMessageFields2Send = $arMessageFields;
			if (
				!is_set($arMessageFields2Send["FROM_USER_ID"]) 
				|| intval($arMessageFields2Send["FROM_USER_ID"]) <= 0
			)
			{
				$arMessageFields2Send["NOTIFY_TYPE"] = IM_NOTIFY_SYSTEM;
				$arMessageFields2Send["FROM_USER_ID"] = 0;
			}

			CIMNotify::Add($arMessageFields2Send);
		}
	}

	public static function IsUserSubscribed($userID, $code)
	{
		$userID = intval($userID);
		if ($userID <= 0)
			return false;
			
		$code = trim($code);
		if (strlen($code) <= 0)
			return false;

		$cache = new CPHPCache;
		$cache_time = 31536000;
		$cache_id = "entity_".$code;
		$cache_path = "/sonet/subscription/";
		
		if ($cache->InitCache($cache_time, $cache_id, $cache_path))
		{
			$arCacheVars = $cache->GetVars();
			$arSubscriberID = $arCacheVars["arSubscriberID"];
		}
		else
		{
			$cache->StartDataCache($cache_time, $cache_id, $cache_path);
			$arSubscriberID = array();

			$rsSubscription = CSocNetSubscription::GetList(
				array(),
				array("CODE" => $code)
			);

			while ($arSubscription = $rsSubscription->Fetch())
				$arSubscriberID[] = $arSubscription["USER_ID"];

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_subscription_".$code);
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");
			}
			
			$arCacheData = Array(
				"arSubscriberID" => $arSubscriberID
			);
			$cache->EndDataCache($arCacheData);

			if(defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->EndTagCache();
		}

		return (in_array($userID, $arSubscriberID));
	}
}
?>