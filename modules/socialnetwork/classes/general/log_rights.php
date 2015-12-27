<?
class CSocNetLogRights
{
	public static function Add($LOG_ID, $GROUP_CODE, $bShare = false, $followSet = true)
	{
		global $DB;

		if (is_array($GROUP_CODE))
		{
			foreach($GROUP_CODE as $GROUP_CODE_TMP)
			{
				CSocNetLogRights::Add($LOG_ID, $GROUP_CODE_TMP, $bShare, $followSet);
			}
			return false;
		}
		else
		{
			$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetLogRightsAdd");
			while ($arEvent = $db_events->Fetch())
			{
				if (ExecuteModuleEventEx($arEvent, array($LOG_ID, $GROUP_CODE)) === false)
				{
					return false;
				}
			}

			$NEW_RIGHT_ID = $DB->Add(
				"b_sonet_log_right",
				array(
					"LOG_ID" => $LOG_ID,
					"GROUP_CODE" => $GROUP_CODE,
				),
				array(),
				"",
				true // ignore errors
			);

			if ($NEW_RIGHT_ID)
			{
				if (preg_match('/^U(\d+)$/', $GROUP_CODE, $matches))
				{
					if($followSet)
					{
						CSocNetLogFollow::Set($matches[1], "L".$LOG_ID, "Y", ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID));
					}
				}
				elseif (
					$bShare
					&& preg_match('/^SG(\d+)$/', $GROUP_CODE, $matches)
				)
				{
					// get all members who unfollow and set'em unfollow from the date
					$arUserIDToCheck = array();

					$rsGroupMembers = CSocNetUserToGroup::GetList(
						array(),
						array(
							"GROUP_ID" => $matches[1],
							"USER_ACTIVE" => "Y",
							"<=ROLE" => SONET_ROLES_USER
						),
						false,
						false,
						array("USER_ID")
					);

					while ($arGroupMembers = $rsGroupMembers->Fetch())
					{
						$arUserIDToCheck[] = $arGroupMembers["USER_ID"];
					}

					if (!empty($arUserIDToCheck))
					{
						$arUserIDFollowDefault = array(
							"Y" => array(),
							"N" => array()
						);
						$arUserIDAlreadySaved = array();
						$default_follow_type = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");

						$rsFollow = CSocNetLogFollow::GetList(
							array(
								"USER_ID" => $arUserIDToCheck,
								"CODE" => "**"
							),
							array("USER_ID", "TYPE")
						);
						while($arFollow = $rsFollow->Fetch())
						{
							$arUserIDFollowDefault[$arFollow["TYPE"]][] = $arFollow["USER_ID"];
						}

						$rsFollow = CSocNetLogFollow::GetList(
							array(
								"USER_ID" => $arUserIDToCheck,
								"CODE" => "L".$LOG_ID
							),
							array("USER_ID")
						);
						while($arFollow = $rsFollow->Fetch())
						{
							$arUserIDAlreadySaved[] = $arFollow["USER_ID"];
						}

						foreach($arUserIDToCheck as $iUserID)
						{
							// for them who not followed by default and not already saved follow/unfollow for the log entry
							if (
								!in_array($iUserID, $arUserIDAlreadySaved)
								&& (
									(
										$default_follow_type == "N"
										&& !in_array($iUserID, $arUserIDFollowDefault["Y"])
									)
									|| (
										$default_follow_type == "Y"
										&& in_array($iUserID, $arUserIDFollowDefault["N"])
									)
								)
							)
							{
								CSocNetLogFollow::Add(
									$iUserID,
									"L".$LOG_ID,
									"N",
									ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID)
								);
							}
						}
					}
				}
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("SONET_LOG_".intval($LOG_ID));
			}

			return $NEW_RIGHT_ID;
		}
	}

	public static function Update($RIGHT_ID, $GROUP_CODE)
	{
		global $DB;
		$RIGHT_ID = intval($RIGHT_ID);

		if (is_array($GROUP_CODE))
		{
			foreach($GROUP_CODE as $GROUP_CODE_TMP)
			{
				CSocNetLogRights::Update($RIGHT_ID, $GROUP_CODE_TMP);
			}

			return false;
		}
		else
		{
			$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetLogRightsUpdate");
			while ($arEvent = $db_events->Fetch())
			{
				if (ExecuteModuleEventEx($arEvent, array($RIGHT_ID, &$GROUP_CODE)) === false)
				{
					return false;
				}
			}

			$strUpdate = $DB->PrepareUpdate("b_sonet_log_right", array(
				"GROUP_CODE" => $GROUP_CODE
			));
			$DB->Query("UPDATE b_sonet_log_right SET ".$strUpdate." WHERE ID = ".$RIGHT_ID);
			return $RIGHT_ID;
		}
	}

	public static function Delete($RIGHT_ID)
	{
		global $DB;
		$RIGHT_ID = intval($RIGHT_ID);
		$DB->Query("DELETE FROM b_sonet_log_right WHERE ID = ".$RIGHT_ID);
	}

	public static function DeleteByLogID($LOG_ID)
	{
		global $DB;

		$LOG_ID = intval($LOG_ID);
		$DB->Query("DELETE FROM b_sonet_log_right WHERE LOG_ID = ".$LOG_ID);

		$db_events = GetModuleEvents("socialnetwork", "OnSocNetLogRightsDelete");
		while ($arEvent = $db_events->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($LOG_ID));
		}
	}

	public static function GetList($aSort=array(), $aFilter=array())
	{
		global $DB;

		$arFilter = array();
		foreach($aFilter as $key=>$val)
		{
			$val = $DB->ForSql($val);
			if(strlen($val) <= 0)
			{
				continue;
			}

			switch(strtoupper($key))
			{
				case "ID":
					$arFilter[] = "R.ID=".intval($val);
					break;
				case "LOG_ID":
					$arFilter[] = "R.LOG_ID=".intval($val);
					break;
				case "GROUP_CODE":
					$arFilter[] = "R.GROUP_CODE='".$val."'";
					break;
			}
		}

		$arOrder = array();
		foreach($aSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> "ASC"?"DESC":"ASC");
			switch(strtoupper($key))
			{
				case "ID":
					$arOrder[] = "R.ID ".$ord;
					break;
				case "LOG_ID":
					$arOrder[] = "R.LOG_ID ".$ord;
					break;
				case "GROUP_CODE":
					$arOrder[] = "R.GROUP_CODE ".$ord;
					break;
			}
		}

		$sOrder = (count($arOrder) > 0 ? "\n ORDER BY ".implode(", ",$arOrder) : "");

		if(count($arFilter) == 0)
			$sFilter = "";
		else
			$sFilter = "\nWHERE ".implode("\nAND ", $arFilter);

		$strSql = "
			SELECT
				R.ID
				,R.LOG_ID
				,R.GROUP_CODE
			FROM
				b_sonet_log_right R
			".$sFilter.$sOrder;

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function SetForSonet($logID, $entity_type, $entity_id, $feature, $operation, $bNew = false)
	{
		$bFlag = true;
		if (!$bNew)
		{
			$rsRights = CSocNetLogRights::GetList(array(), array("LOG_ID" => $logID));
			if ($arRights = $rsRights->Fetch())
			{
				$bFlag = false;
			}
		}

		if ($bFlag)
		{
			$bExtranet = false;
			$perm = CSocNetFeaturesPerms::GetOperationPerm($entity_type, $entity_id, $feature, $operation);
			if ($perm)
			{
				if (CModule::IncludeModule("extranet") && $extranet_site_id = CExtranet::GetExtranetSiteID())
				{
					$arLogSites = array();
					$rsLogSite = CSocNetLog::GetSite($logID);
					while($arLogSite = $rsLogSite->Fetch())
					{
						$arLogSites[] = $arLogSite["LID"];
					}

					if (in_array($extranet_site_id, $arLogSites))
					{
						$bExtranet = true;
					}
				}

				if ($bExtranet)
				{
					if ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_OWNER)
						CSocNetLogRights::Add($logID, array("SA", "S".SONET_ENTITY_GROUP.$entity_id, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_OWNER));
					elseif ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_MODERATOR)
						CSocNetLogRights::Add($logID, array("SA", "S".SONET_ENTITY_GROUP.$entity_id, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_OWNER, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_MODERATOR));
					elseif ($entity_type == SONET_ENTITY_GROUP && in_array($perm, array(SONET_ROLES_USER, SONET_ROLES_AUTHORIZED, SONET_ROLES_ALL)))
						CSocNetLogRights::Add($logID, array("SA", "S".SONET_ENTITY_GROUP.$entity_id, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_OWNER, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_MODERATOR, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_USER));
					elseif ($entity_type == SONET_ENTITY_USER && $perm == SONET_RELATIONS_TYPE_NONE)
						CSocNetLogRights::Add($logID, array("SA", "U".$entity_id));
					elseif ($entity_type == SONET_ENTITY_USER && in_array($perm, array(SONET_RELATIONS_TYPE_FRIENDS, SONET_RELATIONS_TYPE_FRIENDS2, SONET_RELATIONS_TYPE_AUTHORIZED, SONET_RELATIONS_TYPE_ALL)))
					{
						$arCode = array("SA");
						$arLog = CSocNetLog::GetByID($logID);
						if ($arLog)
						{
							$dbUsersInGroup = CSocNetUserToGroup::GetList(
								array(),
								array(
									"USER_ID" => $arLog["USER_ID"],
									"<=ROLE" => SONET_ROLES_USER,
									"GROUP_SITE_ID" => $extranet_site_id,
									"GROUP_ACTIVE" => "Y"
								),
								false,
								false,
								array("ID", "GROUP_ID")
							);
							while ($arUsersInGroup = $dbUsersInGroup->Fetch())
							{
								if (!in_array("S".SONET_ENTITY_GROUP.$arUsersInGroup["GROUP_ID"]."_".SONET_ROLES_USER, $arCode))
								{
									$arCode = array_merge(
										$arCode,
										array(
											"S".SONET_ENTITY_GROUP.$arUsersInGroup["GROUP_ID"]."_".SONET_ROLES_OWNER,
											"S".SONET_ENTITY_GROUP.$arUsersInGroup["GROUP_ID"]."_".SONET_ROLES_MODERATOR,
											"S".SONET_ENTITY_GROUP.$arUsersInGroup["GROUP_ID"]."_".SONET_ROLES_USER
										)
									);
								}
							}

							CSocNetLogRights::Add($logID, $arCode);
						}
					}
				}
				else
				{
					if ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_OWNER)
						CSocNetLogRights::Add($logID, array("SA", "S".SONET_ENTITY_GROUP.$entity_id, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_OWNER));
					elseif ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_MODERATOR)
						CSocNetLogRights::Add($logID, array("SA", "S".SONET_ENTITY_GROUP.$entity_id, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_OWNER, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_MODERATOR));
					elseif ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_USER)
						CSocNetLogRights::Add($logID, array("SA", "S".SONET_ENTITY_GROUP.$entity_id, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_OWNER, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_MODERATOR, "S".SONET_ENTITY_GROUP.$entity_id."_".SONET_ROLES_USER));
					elseif ($entity_type == SONET_ENTITY_USER && in_array($perm, array(SONET_RELATIONS_TYPE_FRIENDS, SONET_RELATIONS_TYPE_FRIENDS2)))
					{
						$arCodes = array("SA", "U".$entity_id, "S".$entity_type.$entity_id."_".SONET_RELATIONS_TYPE_FRIENDS);
						CSocNetLogRights::Add($logID, $arCodes);
					}
					elseif ($entity_type == SONET_ENTITY_USER && $perm == SONET_RELATIONS_TYPE_NONE)
						CSocNetLogRights::Add($logID, array("SA", "U".$entity_id));
					elseif ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_AUTHORIZED)
						CSocNetLogRights::Add($logID, array("SA", "S".$entity_type.$entity_id, "S".$entity_type.$entity_id."_".SONET_ROLES_USER, "AU"));
					elseif ($entity_type == SONET_ENTITY_USER && $perm == SONET_RELATIONS_TYPE_AUTHORIZED)
						CSocNetLogRights::Add($logID, array("SA", "AU"));
					elseif ($entity_type == SONET_ENTITY_GROUP && $perm == SONET_ROLES_ALL)
						CSocNetLogRights::Add($logID, array("SA", "S".$entity_type.$entity_id, "S".$entity_type.$entity_id."_".SONET_ROLES_USER, "G2"));
					elseif ($entity_type == SONET_ENTITY_USER && $perm == SONET_RELATIONS_TYPE_ALL)
						CSocNetLogRights::Add($logID, array("SA", "G2"));
				}
			}
		}
	}

	public static function CheckForUser($logID, $userID, $siteID = SITE_ID)
	{
		$strSql = "SELECT SLR.ID FROM b_sonet_log_right SLR
			INNER JOIN b_user_access UA ON 0=1 ".
//			(CSocNetUser::IsUserModuleAdmin($userID, $siteID) ? " OR SLR.GROUP_CODE = 'SA'" : "").
			(intval($userID) > 0 ? " OR (SLR.GROUP_CODE = 'AU')" : "").
			" OR (SLR.GROUP_CODE = 'G2')".
			(intval($userID) > 0 ? " OR (UA.ACCESS_CODE = SLR.GROUP_CODE AND UA.USER_ID = ".intval($userID).")" : "")."
			WHERE SLR.LOG_ID = ".intval($logID);

		$result = $GLOBALS["DB"]->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if ($ar = $result->Fetch())
		{
			return true;
		}

		return false;
	}
	
	public static function CheckForUserAll($logID)
	{
		$strSql = "SELECT SLR.ID FROM b_sonet_log_right SLR
			WHERE 
			SLR.LOG_ID = ".intval($logID)." 
			AND (
				(SLR.GROUP_CODE = 'AU') 
				OR (SLR.GROUP_CODE = 'G2')
			)";

		$result = $GLOBALS["DB"]->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if($ar = $result->Fetch())
		{
			return true;
		}

		return false;
	}
	
	public static function CheckForUserOnly($logID, $userID)
	{
		if (
			intval($logID) <= 0
			|| intval($userID) <= 0
		)
			return false;

		$strSql = "SELECT SLR.ID FROM b_sonet_log_right SLR
			INNER JOIN b_user_access UA ON 0=1 OR (UA.ACCESS_CODE = SLR.GROUP_CODE AND UA.USER_ID = ".intval($userID).") 
			WHERE SLR.LOG_ID = ".intval($logID);

		$result = $GLOBALS["DB"]->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if($ar = $result->Fetch())
		{
			return true;
		}

		return false;
	}	

}
?>