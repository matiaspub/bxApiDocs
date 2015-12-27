<?
IncludeModuleLangFile(__FILE__);

class CSocNetLogFollow
{
	public static function Set($user_id, $code = "**", $type = "Y", $follow_date = false, $site_id = SITE_ID, $bByWF = false)
	{
		static $LOG_CACHE;

		if (strlen($code) <= 0)
		{
			$code = "**";
		}

		if ($type != "Y")
		{
			$type = "N";
		}

		if (intval($user_id) <= 0)
		{
			$user_id = $GLOBALS["USER"]->GetID();
		}

		$arFollows = array();

		$rsFollow = CSocNetLogFollow::GetList(
			array(
				"USER_ID" => $user_id, 
				"CODE" => array_unique(array("**", $code))
			)
		);
		while($arFollow = $rsFollow->Fetch())
		{
			$arFollows[$arFollow["CODE"]] = array(
				"TYPE" => $arFollow["TYPE"],
				"FOLLOW_DATE" => $arFollow["FOLLOW_DATE"]
			);
		}

		$default_type = (
			array_key_exists("**", $arFollows)
				? $arFollows["**"]["TYPE"]
				: COption::GetOptionString("socialnetwork", "follow_default_type", "Y")
		);

		if (preg_match('/^L(\d+)$/', $code, $matches))
		{
			$log_id = intval($matches[1]);
			if ($log_id > 0)
			{
				if (isset($LOG_CACHE[$log_id]))
				{
					$arLog = $LOG_CACHE[$log_id];
				}
				else
				{
					$rsLog = CSocNetLog::GetList(
						array("ID" => "DESC"),
						array("ID" => $log_id),
						false,
						false,
						array("ID", "LOG_UPDATE", "LOG_DATE"),
						array(
							"CHECK_RIGHTS" => "N",
							"USE_SUBSCRIBE" => "N",
							"USE_FOLLOW" => "N"
						)
					);

					if ($arLog = $rsLog->Fetch())
					{
						$LOG_CACHE[$log_id] = $arLog;
					}
				}

				if ($arLog)
				{
					$log_date = (strlen($arLog["LOG_DATE"]) > 0 ? $arLog["LOG_DATE"] : false);
					$log_update = (strlen($arLog["LOG_UPDATE"]) > 0 ? $arLog["LOG_UPDATE"] : false);

					if (array_key_exists($code, $arFollows)) // already in the follows table
					{
						$res = CSocNetLogFollow::Update(
							$user_id, 
							$code, 
							$type, 
							(
								strlen($arFollows[$code]["FOLLOW_DATE"]) > 0 
									? $arFollows[$code]["FOLLOW_DATE"] // existing value
									: (
										$type == "N" 
											? $log_update 
											: ($code == "**" ? $log_date : false)
									)
							),
							$bByWF
						);
					}
					elseif ($type != $default_type) // new record in the follow table only if not equal to default type
					{
						$res = CSocNetLogFollow::Add(
							$user_id, 
							$code, 
							$type, 
							(
								$follow_date
									? $follow_date
									: (
										$type == "N" 
											? $log_update 
											: $log_date
									)
							),
							$bByWF
						);
					}
				}
			}
		}
		else // **, change of default type
		{
			$res = (
				array_key_exists($code, $arFollows)
					? CSocNetLogFollow::Update($user_id, $code, $type, false)
					: CSocNetLogFollow::Add($user_id, $code, $type, false)
			);
		}

		return $res;
	}
	
	public static function Add($user_id, $code, $type, $follow_date = false, $bByWF = false)
	{
		global $DB;

		if (
			intval($user_id) <= 0 
			|| strlen($code) <= 0
		)
		{
			return false;
		}

		if ($type != "Y")
		{
			$type = "N";
		}

		$ref_id = (preg_match('/(\d+)/', $code, $matches) ? intval($matches[1]) : 0);

		$strSQL = "INSERT INTO b_sonet_log_follow 
			(USER_ID, CODE, REF_ID, TYPE, FOLLOW_DATE, BY_WF)
			VALUES(".$user_id.", '".$code."', ".$ref_id.", '".$type."', ".($follow_date ? $DB->CharToDateFunction($follow_date) : $DB->CurrentTimeFunction()).", ".($bByWF ? "'Y'" : "null").")";

		if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
		{
			if (
				defined("BX_COMP_MANAGED_CACHE") 
				&& intval($user_id) > 0 
				&& $code === "**"
			)
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("SONET_LOG_FOLLOW_".$user_id);
			}

			return true;
		}
		else
			return false;
	}

	public static function Update($user_id, $code, $type, $follow_date = false, $bByWF = false)
	{
		global $DB;

		if (intval($user_id) <= 0 || strlen($code) <= 0)
			return false;

		if ($type != "Y")
			$type = "N";

		$strSQL = "UPDATE b_sonet_log_follow SET TYPE = '".$type."', FOLLOW_DATE = ".($follow_date ? $DB->CharToDateFunction($follow_date) : $DB->CurrentTimeFunction()).", BY_WF = ".($bByWF ? "'Y'" : "null")." WHERE USER_ID = ".$user_id." AND CODE = '".$code."'";
		if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
		{
			if (
				defined("BX_COMP_MANAGED_CACHE") 
				&& intval($user_id) > 0 
				&& $code === "**"
			)
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("SONET_LOG_FOLLOW_".$user_id);

			return true;
		}
		else
			return false;
	}

	public static function Delete($user_id, $code, $type = false)
	{
		global $DB;

		if (intval($user_id) <= 0 || strlen($code) <= 0)
			return false;

		$strSQL = "DELETE FROM b_sonet_log_follow WHERE USER_ID = ".$user_id." AND CODE = '".$code."'";

		if ($type)
			$strSQL .= " AND TYPE = '".$type."'";

		if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
		{
			if (
				defined("BX_COMP_MANAGED_CACHE") 
				&& intval($user_id) > 0 
				&& $code === "**"
			)
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("SONET_LOG_FOLLOW_".$user_id);

			return true;
		}
		else
			return false;
	}
	
	public static function DeleteByLogID($log_id, $type = false, $bUseSmartLogic = false)
	{
		global $DB;

		if (intval($log_id) <= 0)
		{
			return false;
		}

		if (
			$type == "Y"
			&& $bUseSmartLogic
		)
		{
			$default_follow = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");

			if ($default_follow == "N")
			{
				$arUserID = array();
				$strSQL = "SELECT 
							USER_ID FROM b_sonet_log_follow 
						WHERE 
							CODE = '**' 
							AND TYPE='Y' 
						";
				$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while ($arRes = $dbRes->Fetch())
				{
					$arUserID[] = $arRes["USER_ID"];
				}

				if (count($arUserID) > 0)
				{
					$strSQL = "DELETE FROM b_sonet_log_follow 
						WHERE 
							TYPE = 'Y' 
							AND CODE = 'L".$log_id."' 
							AND USER_ID IN (".implode(", ", $arUserID).")
					";
					$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				}

				$strSQL = "UPDATE b_sonet_log_follow 
					SET b_sonet_log_follow.FOLLOW_DATE = NULL 
					WHERE 
						TYPE = 'Y' 
						AND CODE = 'L".$log_id."' 
				";
				if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				$arUserID = array();
				$strSQL = "SELECT 
							USER_ID FROM b_sonet_log_follow 
						WHERE 
							CODE = '**' 
							AND TYPE='N' 
						";
				$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while ($arRes = $dbRes->Fetch())
					$arUserID[] = $arRes["USER_ID"];
				
				if (count($arUserID) > 0)
				{
					$strSQL = "UPDATE b_sonet_log_follow 
						SET b_sonet_log_follow.FOLLOW_DATE = NULL 
						WHERE 
							TYPE = 'Y' 
							AND CODE = 'L".$log_id."' 
							AND USER_ID IN (".implode(", ", $arUserID).")
					";
					$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				}

				$strSQL = "DELETE FROM b_sonet_log_follow 
					WHERE 
						TYPE = 'Y' 
						AND CODE = 'L".$log_id."'";

				if (count($arUserID) > 0)
					$strSQL .= " AND USER_ID NOT IN (".implode(", ", $arUserID).")";

				if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
					return true;
				else
					return false;
			}
		}
		else
		{
			$strSQL = "DELETE FROM b_sonet_log_follow WHERE CODE = 'L".$log_id."'";

			if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
				return true;
			else
				return false;
		}
	}

	public static function GetExactValueByRating($user_id, $rating_type_id, $rating_entity_id)
	{
		global $DB;

		if (
			intval($user_id) <= 0
			|| strlen($rating_type_id) <= 0
			|| intval($rating_entity_id) <= 0
		)
			return false;

		$arPostTypeID = array(
			"BLOG_POST", 
			"FORUM_TOPIC", 
			"IBLOCK_ELEMENT", 
			"BITRIX24_NEW_USER", 
			"INTRANET_NEW_USER", 
			"LOG_ENTRY"
		);

		$arCommentTypeID = array(
			"BLOG_COMMENT", 
			"FORUM_POST", 
			"BITRIX24_NEW_USER_COMMENT", 
			"INTRANET_NEW_USER_COMMENT",
			"LOG_COMMENT"
		);

		if (
			in_array($rating_type_id, $arCommentTypeID)
			|| 
			(
				!in_array($rating_type_id, $arCommentTypeID)
				&& !in_array($rating_type_id, $arPostTypeID)
			)
		)
		{
			$strSQL = "SELECT TYPE FROM b_sonet_log_follow LFW 
				INNER JOIN b_sonet_log_comment LC ON 
					LC.RATING_TYPE_ID = '".$rating_type_id."' 
					AND LC.RATING_ENTITY_ID = ".intval($rating_entity_id)." 
					AND LFW.REF_ID = LC.LOG_ID 
					AND LFW.CODE = ".$DB->Concat("'L'", ($DB->type == "MSSQL" ? "CAST(LC.LOG_ID as varchar(17))" : "LC.LOG_ID"))." 
				WHERE  
					LFW.USER_ID = ".intval($user_id);

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$strRes = $arRes["TYPE"];
		}

		if (
			in_array($rating_type_id, $arPostTypeID)
			|| 
			(
				!in_array($rating_type_id, $arCommentTypeID)
				&& !in_array($rating_type_id, $arPostTypeID)
				&& !$strRes
			)
		)
		{
			$strSQL = "SELECT TYPE FROM b_sonet_log_follow LFW 
				INNER JOIN b_sonet_log L ON 
					L.RATING_TYPE_ID = '".$rating_type_id."' 
					AND L.RATING_ENTITY_ID = ".intval($rating_entity_id)." 
					AND LFW.REF_ID = L.ID 
					AND LFW.CODE = ".$DB->Concat("'L'", ($DB->type == "MSSQL" ? "CAST(L.ID as varchar(17))" : "L.ID"))." 
				WHERE  
					LFW.USER_ID = ".intval($user_id);

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$strRes = $arRes["TYPE"];
		}

		return $strRes;
	}

	public static function GetList($arFilter = Array(), $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
		{
			$arSelectFields = array("USER_ID", "CODE", "TYPE", "FOLLOW_DATE", "BY_WF");
		}

		// FIELDS -->
		$arFields = array(
			"USER_ID" => Array("FIELD" => "SLF.USER_ID", "TYPE" => "int"),
			"CODE" => Array("FIELD" => "SLF.CODE", "TYPE" => "string"),
			"REF_ID" => Array("FIELD" => "SLF.REF_ID", "TYPE" => "int"),
			"TYPE" => array("FIELD" => "SLF.TYPE", "TYPE" => "char"),
			"FOLLOW_DATE" => Array("FIELD" => "SLF.FOLLOW_DATE", "TYPE" => "datetime"),
			"BY_WF" => array("FIELD" => "SLF.BY_WF", "TYPE" => "char"),
		);
		// <-- FIELDS

		$arSqls = CSocNetGroup::PrepareSql($arFields, array(), $arFilter, false, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_log_follow SLF ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	public static function GetDefaultValue($user_id)
	{
		if (intval($user_id) <= 0)
		{
			return false;
		}

		global $CACHE_MANAGER;

		$ttl = (defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600);

		$cache_id = 'sonet_follow_default_'.$user_id;
		$obCache = new CPHPCache;
		$cache_dir = '/sonet/log_follow/'.$user_id.'/';

		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$tmpVal = $obCache->GetVars();
			$default_follow = $tmpVal["VALUE"];
			unset($tmpVal);
		}
		else
		{
			$default_follow = false;
			
			if (is_object($obCache))
				$obCache->StartDataCache($ttl, $cache_id, $cache_dir);

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_dir);
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("SONET_LOG_FOLLOW_".$user_id);
			}

			$rsFollow = CSocNetLogFollow::GetList(
				array(
					"USER_ID" => $user_id,
					"CODE" => "**"
				),
				array("TYPE")
			);
			if ($arFollow = $rsFollow->Fetch())
			{
				$default_follow = $arFollow["TYPE"];
			}

			if (is_object($obCache))
			{
				$arCacheData = Array(
					"VALUE" => $default_follow
				);
				$obCache->EndDataCache($arCacheData);
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->EndTagCache();
				}
			}
		}
		unset($obCache);

		if (!$default_follow)
		{
			$default_follow = COption::GetOptionString("socialnetwork", "follow_default_type", "Y");
		}

		return $default_follow;
	}

	public static function OnBlogPostMentionNotifyIm($ID, $arMessageFields)
	{
		if (
			is_array($arMessageFields)
			&& intval($arMessageFields["TO_USER_ID"]) > 0
			&& intval($arMessageFields["LOG_ID"]) > 0
		)
		{
			$res = CSocNetLogFollow::Set(
				intval($arMessageFields["TO_USER_ID"]), 
				"L".intval($arMessageFields["LOG_ID"]), 
				"Y", 
				ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL", SITE_ID)
			);
		}

		return $res;
	}

	public static function checkAutoUnfollow($traffic_cnt, $traffic_avg, $userId = false)
	{
		if (
			intval($traffic_cnt) > 10
			&& intval($traffic_avg) < 60*60*4 // 4 hours
		)
		{
			$userId = (
				!$userId
				|| intval($userId) <= 0
					? $GLOBALS["USER"]->GetID()
					: intval($userId)
			);

			$default_follow = CSocNetLogFollow::GetDefaultValue($userId);
			if ($default_follow == 'Y')
			{
				$isAlreadyChecked = CUserOptions::GetOption("socialnetwork", "~log_autofollow_checked", "N", false, $userId);
				if ($isAlreadyChecked != 'Y')
				{
					if (CModule::IncludeModule('im'))
					{
						$arMessageFields2Send = array(
							"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
							"NOTIFY_TYPE" => IM_NOTIFY_CONFIRM,
							"NOTIFY_MODULE" => "socialnetwork",
							"NOTIFY_EVENT" => "sonet_auto_unfollow_btn",
							"NOTIFY_TAG" => "SONET|UNFOLLOW|".$userId,
							"TO_USER_ID" => $userId,
							"NOTIFY_MESSAGE" => GetMessage("SONET_LF_UNFOLLOW_IM_MESSAGE"),
							"NOTIFY_MESSAGE_OUT" => IM_MAIL_SKIP,
							"NOTIFY_BUTTONS" => Array(
								Array("TITLE" => GetMessage("SONET_LF_UNFOLLOW_IM_BUTTON_Y"), "VALUE" => "Y", "TYPE" => "accept"),
								Array("TITLE" => GetMessage("SONET_LF_UNFOLLOW_IM_BUTTON_N"), "VALUE" => "N", "TYPE" => "cancel"),
							)
						);

						CIMNotify::Add($arMessageFields2Send);
					}

					CUserOptions::SetOption("socialnetwork", "~log_autofollow_checked", "Y", false, $userId);
				}
			}
		}
	}

	public static function OnBeforeConfirmNotify($module, $tag, $value, $arParams)
	{
		if ($module == "socialnetwork")
		{
			$arTag = explode("|", $tag);
			if (
				count($arTag) == 3
				&& $arTag[1] == 'UNFOLLOW'
			)
			{
				if ($value == 'Y')
				{
					CSocNetLogFollow::Set($GLOBALS["USER"]->GetID(), "**", "N");
				}
				return true;
			}
		}
	}

}
?>