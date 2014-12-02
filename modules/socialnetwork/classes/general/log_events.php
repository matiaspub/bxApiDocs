<?
IncludeModuleLangFile(__FILE__);

class CAllSocNetLogEvents
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $arSocNetAllowedEntityTypes;

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "ENTITY_TYPE") || $ACTION=="ADD") && StrLen($arFields["ENTITY_TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_EMPTY_ENTITY_TYPE"), "EMPTY_ENTITY_TYPE");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_TYPE"))
		{
			if (!in_array($arFields["ENTITY_TYPE"], CSocNetAllowed::GetAllowedEntityTypes()))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_ERROR_NO_ENTITY_TYPE"), "ERROR_NO_ENTITY_TYPE");
				return false;
			}
		}

		if (is_set($arFields, "ENTITY_ID"))
		{
			$type = "";
			if (is_set($arFields, "ENTITY_TYPE"))
			{
				$type = $arFields["ENTITY_TYPE"];
			}
			elseif ($ACTION != "ADD")
			{
				$arRe = CAllSocNetLog::GetByID($ID);
				if ($arRe)
					$type = $arRe["ENTITY_TYPE"];
			}
			if (StrLen($type) <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_ERROR_CALC_ENTITY_TYPE"), "ERROR_CALC_ENTITY_TYPE");
				return false;
			}

			if ($type == SONET_SUBSCRIBE_ENTITY_GROUP && intval($arFields["ENTITY_ID"]) > 0)
			{
				$arResult = CSocNetGroup::GetByID($arFields["ENTITY_ID"]);
				if ($arResult == false)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
			elseif (
					$type == SONET_SUBSCRIBE_ENTITY_USER
					&& intval($arFields["ENTITY_ID"]) > 0
			)
			{
				$dbResult = CUser::GetByID($arFields["ENTITY_ID"]);
				if (!$dbResult->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
		}

		if ((is_set($arFields, "EVENT_ID") || $ACTION=="ADD") && StrLen($arFields["EVENT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_EMPTY_EVENT_ID"), "EMPTY_EVENT_ID");
			return false;
		}
		elseif (is_set($arFields, "EVENT_ID"))
		{
			$arFields["EVENT_ID"] = strtolower($arFields["EVENT_ID"]);
			if (
				!array_key_exists($arFields["EVENT_ID"], $arSocNetFeaturesSettings) 
				&& $arFields["EVENT_ID"] != "all"
				&& !array_key_exists($arFields["EVENT_ID"], $arSocNetLogEvents)
			)
			{
				$bFound = false;
				foreach($arSocNetFeaturesSettings as $feature_id => $arFeature)
				{
					if (
						array_key_exists("subscribe_events", $arFeature)
						&& array_key_exists($arFields["EVENT_ID"], $arFeature["subscribe_events"])
					)
					{
						$bFound = true;
						break;
					}
				}

				if (!$bFound && CSocNetLogTools::FindLogCommentEventByID($arFields["EVENT_ID"]))
					$bFound = true;

				if (!$bFound)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE");
					return false;
				}
			}
		}

		if (is_set($arFields, "SITE_ID") && $arFields["SITE_ID"] != false)
		{
			$dbResult = CSite::GetByID($arFields["SITE_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["SITE_ID"], GetMessage("SONET_LE_ERROR_NO_SITE")), "ERROR_NO_SITE");
				return false;
			}
		}

		if ((is_set($arFields, "MAIL_EVENT") || $ACTION=="ADD") && $arFields["MAIL_EVENT"] != "Y" && $arFields["MAIL_EVENT"] != "N")
			$arFields["MAIL_EVENT"] = "N";

		if (is_set($arFields, "MAIL_EVENT") && $arFields["MAIL_EVENT"] == "Y")
			$arFields["TRANSPORT"] = "M";

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
			return false;
		}

		$bSuccess = True;

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_log_events WHERE ID = ".$ID."", true);

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		global $DB;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		$DB->Query("DELETE FROM b_sonet_log_events WHERE USER_ID = ".$userID."", true);
		$DB->Query("DELETE FROM b_sonet_log_events WHERE ENTITY_TYPE = '".SONET_SUBSCRIBE_ENTITY_USER."' AND ENTITY_ID = ".$userID."", true);

		return true;
	}

	public static function DeleteByUserAndEntity($userID, $entityType, $entityID)
	{
		global $DB;

		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		$entityType = Trim($entityType);

		if (!in_array($entityType, CSocNetAllowed::GetAllowedEntityTypes()))
		{
			return false;
		}

		$entityID = IntVal($entityID);
		if ($entityID <= 0)
			return false;

		$bSuccess = $DB->Query(
			"DELETE FROM b_sonet_log_events ".
			"WHERE USER_ID = ".$userID." ".
			"	AND ENTITY_TYPE = '".$DB->ForSql($entityType, 1)."' ".
			"	AND ENTITY_ID = ".$entityID."",
			true
		);

		return $bSuccess;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_LE_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
			return false;
		}

		$dbResult = CSocNetLogEvents::GetList(Array(), Array("ID" => $ID));
		if ($arResult = $dbResult->GetNext())
		{
			return $arResult;
		}

		return False;
	}

	/***************************************/
	/**********      UTIL        ***********/
	/***************************************/
	public static function AutoSubscribe($userID, $entityType, $entityID)
	{
		$dbRes = CSocNetLogEvents::GetList(
			array(),
			array("USER_ID" => $userID, "ENTITY_TYPE" => $entityType, "ENTITY_ID" => $entityID)
		);
		if ($dbRes->Fetch())
			return;

		$SiteID = false;
		if ($entityType == SONET_SUBSCRIBE_ENTITY_GROUP)
			if ($arGroupTmp = CSocNetGroup::GetByID($entityID))
				$SiteID = $arGroupTmp["SITE_ID"];
			
		$arLogEvent = array(
			"USER_ID" => $userID,
			"ENTITY_TYPE" => $entityType,
			"ENTITY_ID" => $entityID,
			"EVENT_ID" => 'system',
			"SITE_ID" => $SiteID,
		);
		CSocNetLogEvents::Add($arLogEvent);

		if ($entityType == SONET_SUBSCRIBE_ENTITY_USER)
		{
			$arLogEvent = array(
				"USER_ID" => $userID,
				"ENTITY_TYPE" => $entityType,
				"ENTITY_ID" => $entityID,
				"EVENT_ID" => 'system_friends',
				"SITE_ID" => $SiteID,
				"MAIL_EVENT" => "Y",
			);
			CSocNetLogEvents::Add($arLogEvent);
		}

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		foreach ($arSocNetFeaturesSettings as $key => $value)
		{
			$arLogEvent = array(
				"USER_ID" => $userID,
				"ENTITY_TYPE" => $entityType,
				"ENTITY_ID" => $entityID,
				"EVENT_ID" => $key,
				"SITE_ID" => $SiteID,
				"MAIL_EVENT" => "Y",
			);
			CSocNetLogEvents::Add($arLogEvent);
		}
	}

	public static function GetSQL($user_id, $arMyEntities, $transport, $visible, $table_alias = "L")
	{
		global $DB;

		if (intval($user_id) <= 0)
			return false;

		if ((!defined("DisableSonetLogVisibleSubscr") || DisableSonetLogVisibleSubscr !== true) && $visible && strlen($visible) > 0)
		{
			$key_res = CSocNetGroup::GetFilterOperation($visible);
			$strField = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$visibleFilter = "AND (".($strNegative == "Y" ? " SLE.VISIBLE IS NULL OR NOT " : "")."(SLE.VISIBLE ".$strOperation." '".$DB->ForSql($strField)."'))";
			
			$transportFilter = "";				
		}
		else
		{
			$visibleFilter = "";			

			if ($transport && strlen($transport) > 0)
			{
				$key_res = CSocNetGroup::GetFilterOperation($transport);
				$strField = $key_res["FIELD"];
				$strNegative = $key_res["NEGATIVE"];
				$strOperation = $key_res["OPERATION"];
				$transportFilter = "AND (".($strNegative == "Y" ? " SLE.TRANSPORT IS NULL OR NOT " : "")."(SLE.TRANSPORT ".$strOperation." '".$DB->ForSql($strField)."'))";
			}
			else
				$transportFilter = "";
		}

		$strMyEntities = array();
		foreach($arMyEntities as $entity_type_tmp => $arMyEntity)
		{
			if (is_array($arMyEntity) && count($arMyEntity) > 0)
			{
				$strMyEntities[$entity_type_tmp] = $table_alias.".ENTITY_ID IN (".implode(",", $arMyEntity).")";
				$strNotMyEntities[$entity_type_tmp] = "(".$table_alias.".ENTITY_ID NOT IN (".implode(",", $arMyEntity).") AND ".$table_alias.".ENTITY_TYPE = '".$entity_type_tmp."')";
			}
		}

		$arCBFilterEntityType = array();
		$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

		foreach($arSocNetAllowedSubscribeEntityTypesDesc as $entity_type_tmp => $arEntityTypeTmp)
		{
			if (
				array_key_exists("USE_CB_FILTER", $arEntityTypeTmp)
				&& $arEntityTypeTmp["USE_CB_FILTER"] == "Y"
			)
			{
				$arCBFilterEntityType[] = "'".$entity_type_tmp."'";
			}
		}

		if (is_array($arCBFilterEntityType) && count($arCBFilterEntityType) > 0)
		{
			$strCBFilterEntityType = $table_alias.".ENTITY_TYPE IN (".implode(",", $arCBFilterEntityType).") AND ";
			$strNotCBFilterEntityType = $table_alias.".ENTITY_TYPE NOT IN (".implode(",", $arCBFilterEntityType).") OR ";
		}
		else
		{
			$strCBFilterEntityType = "";
			$strNotCBFilterEntityType = "";
		}

		$strSQL = "
		EXISTS(
			SELECT ID 
			FROM b_sonet_log_events SLE 
			WHERE 
				SLE.USER_ID = ".$user_id." 
				AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE
				AND SLE.ENTITY_CB = 'N'
				AND SLE.ENTITY_ID = ".$table_alias.".ENTITY_ID
				AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
				".$transportFilter."
				".$visibleFilter."
		)
		OR 
		(
			".$strCBFilterEntityType."
			EXISTS(
				SELECT ID 
				FROM b_sonet_log_events SLE 
				WHERE 
					SLE.USER_ID = ".$user_id." 
					AND SLE.ENTITY_CB = 'Y'
					AND SLE.ENTITY_ID = ".$table_alias.".USER_ID
					AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
					".$transportFilter."
					".$visibleFilter."
			)		
		)
		OR 
		(
			(
				NOT EXISTS(
					SELECT ID 
					FROM b_sonet_log_events SLE 
					WHERE 
						SLE.USER_ID = ".$user_id." 
						AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE
						AND SLE.ENTITY_CB = 'N'
						AND SLE.ENTITY_ID = ".$table_alias.".ENTITY_ID
						AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
				)
				OR
				EXISTS(
					SELECT ID 
					FROM b_sonet_log_events SLE 
					WHERE 
						SLE.USER_ID = ".$user_id." 
						AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE
						AND SLE.ENTITY_CB = 'N'
						AND SLE.ENTITY_ID = ".$table_alias.".ENTITY_ID
						AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
						AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
				)
			)
			AND 
			(
				".$strNotCBFilterEntityType."			
				NOT EXISTS(
					SELECT ID 
					FROM b_sonet_log_events SLE 
					WHERE 
						SLE.USER_ID = ".$user_id." 
						AND SLE.ENTITY_CB = 'Y'
						AND SLE.ENTITY_ID = ".$table_alias.".USER_ID
						AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
				)
				OR 
				EXISTS(
					SELECT ID 
					FROM b_sonet_log_events SLE 
					WHERE 
						SLE.USER_ID = ".$user_id." 
						AND SLE.ENTITY_CB = 'Y'
						AND SLE.ENTITY_ID = ".$table_alias.".USER_ID
						AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
						AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
				)
				
			)
			AND
			(
				EXISTS(
					SELECT ID 
					FROM b_sonet_log_events SLE 
					WHERE 
						SLE.USER_ID = ".$user_id." 
						AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE
						AND SLE.ENTITY_CB = 'N'
						AND SLE.ENTITY_ID = ".$table_alias.".ENTITY_ID
						AND SLE.EVENT_ID = 'all'
						".$transportFilter."
						".$visibleFilter."
				)
				OR
				(
					".$strCBFilterEntityType."				
					EXISTS(
						SELECT ID 
						FROM b_sonet_log_events SLE 
						WHERE 
							SLE.USER_ID = ".$user_id." 
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$table_alias.".USER_ID
							AND SLE.EVENT_ID = 'all'
							".$transportFilter."
							".$visibleFilter."
					)
				)
				OR
				(
					(
						NOT EXISTS(
							SELECT ID 
							FROM b_sonet_log_events SLE 
							WHERE 
								SLE.USER_ID = ".$user_id." 
								AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE
								AND SLE.ENTITY_CB = 'N'
								AND SLE.ENTITY_ID = ".$table_alias.".ENTITY_ID
								AND SLE.EVENT_ID = 'all'
						)
						OR 
						EXISTS(
							SELECT ID 
							FROM b_sonet_log_events SLE 
							WHERE 
								SLE.USER_ID = ".$user_id." 
								AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE
								AND SLE.ENTITY_CB = 'N'
								AND SLE.ENTITY_ID = ".$table_alias.".ENTITY_ID
								AND SLE.EVENT_ID = 'all'
								AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
						)
					)
					AND 
					(
						".$strNotCBFilterEntityType."					
						NOT EXISTS(
							SELECT ID 
							FROM b_sonet_log_events SLE 
							WHERE 
								SLE.USER_ID = ".$user_id." 
								AND SLE.ENTITY_CB = 'Y'
								AND SLE.ENTITY_ID = ".$table_alias.".USER_ID
								AND SLE.EVENT_ID = 'all'
						)
						OR
						EXISTS(
							SELECT ID 
							FROM b_sonet_log_events SLE 
							WHERE 
								SLE.USER_ID = ".$user_id." 
								AND SLE.ENTITY_CB = 'Y'
								AND SLE.ENTITY_ID = ".$table_alias.".USER_ID
								AND SLE.EVENT_ID = 'all'
								AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
						)
					)					
					AND
					(
					";

		if (count($strMyEntities) > 0)
		{
			foreach ($strMyEntities as $entity_type_tmp	=> $strMyEntity)
			{
				$strSQL .= (strlen($strMyEntity) > 0 ?	"
						(
							".$strMyEntity."
							AND
							(
								EXISTS(
									SELECT ID 
									FROM b_sonet_log_events SLE 
									WHERE 
										SLE.USER_ID = ".$user_id." 
										AND SLE.ENTITY_TYPE = '".$entity_type_tmp."'
										AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
										AND SLE.ENTITY_ID = 0 
										AND SLE.ENTITY_MY = 'Y'
										AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
										".$transportFilter."
										".$visibleFilter."
								)
								OR
								(
									(
										EXISTS(
											SELECT ID 
											FROM b_sonet_log_events SLE 
											WHERE 
												SLE.USER_ID = ".$user_id." 
												AND SLE.ENTITY_TYPE = '".$entity_type_tmp."'
												AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
												AND SLE.ENTITY_ID = 0 
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
										)
										OR
										NOT EXISTS(
											SELECT ID 
											FROM b_sonet_log_events SLE 
											WHERE 
												SLE.USER_ID = ".$user_id." 
												AND SLE.ENTITY_TYPE = '".$entity_type_tmp."'
												AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
												AND SLE.ENTITY_ID = 0 
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
										)
									)
									AND
									(
										EXISTS(
											SELECT ID 
											FROM b_sonet_log_events SLE 
											WHERE 
												SLE.USER_ID = ".$user_id." 
												AND SLE.ENTITY_TYPE = '".$entity_type_tmp."'
												AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
												AND SLE.ENTITY_ID = 0 
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = 'all'
												".$transportFilter."
												".$visibleFilter."
										)
									)
								)
							)
						)
						OR
				" : "");
			}
		}

		$strSQL .=	"
						(
							EXISTS(
								SELECT ID 
								FROM b_sonet_log_events SLE 
								WHERE 
									SLE.USER_ID = ".$user_id."
									AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
									AND SLE.ENTITY_ID = 0 
									AND SLE.ENTITY_MY = 'N'
									AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
									".$transportFilter."
									".$visibleFilter."
							)
							OR
							(
								(
									EXISTS(
										SELECT ID 
										FROM b_sonet_log_events SLE 
										WHERE 
											SLE.USER_ID = ".$user_id."
											AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
											AND SLE.ENTITY_ID = 0 
											AND SLE.ENTITY_MY = 'N'
											AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
											AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
										)
									OR
									NOT EXISTS(
										SELECT ID 
										FROM b_sonet_log_events SLE 
										WHERE 
											SLE.USER_ID = ".$user_id."
											AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
											AND SLE.ENTITY_ID = 0 
											AND SLE.ENTITY_MY = 'N'
											AND SLE.EVENT_ID = ".$table_alias.".EVENT_ID
									)
								)
								AND 
								(
									EXISTS(
										SELECT ID 
										FROM b_sonet_log_events SLE 
										WHERE 
											SLE.USER_ID = ".$user_id."
											AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
											AND SLE.ENTITY_ID = 0 
											AND SLE.ENTITY_MY = 'N'
											AND SLE.EVENT_ID = 'all'
									".$transportFilter."
									".$visibleFilter."
									)
									OR
									EXISTS(
										SELECT ID 
										FROM b_sonet_log_events SLE 
										WHERE 
											SLE.USER_ID = ".$user_id."
											AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
											AND SLE.ENTITY_ID = 0 
											AND SLE.ENTITY_MY = 'N'
											AND SLE.EVENT_ID = 'all'
											AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
									)
									OR
									NOT EXISTS(
										SELECT ID 
										FROM b_sonet_log_events SLE 
										WHERE 
											SLE.USER_ID = ".$user_id."
											AND SLE.ENTITY_TYPE = ".$table_alias.".ENTITY_TYPE 
											AND SLE.ENTITY_ID = 0 
											AND SLE.ENTITY_MY = 'N'
											AND SLE.EVENT_ID = 'all'
									)
								)
							)
						)
					)	
				)
			)
		)";

		return $strSQL;

	}

	public static function GetSQLForEvent($entity_type, $entity_id, $event_id, $user_id, $transport = false, $visible = true, $arOfEntities = array())
	{
		if (!in_array($entity_type, CSocNetAllowed::GetAllowedEntityTypes()))
		{
			return false;
		}

		if (intval($entity_id) <= 0)
			return false;

		$strSQL = "";
		
		if (
			is_array($arOfEntities)
			&& count($arOfEntities) > 0
		)
			$strOfEntities = "AND LE.USER_ID IN (".implode(",", $arOfEntities).")";
		else
			$strOfEntities = "";
			
		if (is_array($transport) && count($transport) > 0)
			$strTransport = "AND LE.TRANSPORT IN ('".implode("', '", $transport)."')";
		elseif(!is_array($transport) && strlen($transport) > 0)
			$strTransport = "AND LE.TRANSPORT = '".$transport."'";		
		else
			$strTransport = "";

		$strSQL .= "AND (
			(	
				LE.ENTITY_TYPE = '".$entity_type."'
				AND LE.ENTITY_ID = ".$entity_id."
				AND LE.ENTITY_CB = 'N'
				AND LE.EVENT_ID = '".$event_id."'
				".$strTransport."
			)";

		$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

		if (
			array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
			&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] == "Y"
			&& intval($user_id) > 0
		)
		{
			$strSQL .= "
				OR 
				(
					LE.ENTITY_TYPE = '".SONET_SUBSCRIBE_ENTITY_USER."'
					AND LE.ENTITY_ID = ".$user_id."
					AND LE.ENTITY_CB = 'Y'
					AND 
					(
						LE.EVENT_ID = '".$event_id."'
						OR LE.EVENT_ID = 'all'								
					)
					".$strTransport."
				)";
		}

		$strSQL .= "
			OR
			(
				LE.ENTITY_TYPE = '".$entity_type."'
				AND LE.ENTITY_ID = ".$entity_id."
				AND LE.ENTITY_CB = 'N'
				AND LE.EVENT_ID = 'all'
				".$strTransport."
			)
			OR 
			(
				LE.ENTITY_TYPE = '".$entity_type."'
				AND LE.ENTITY_ID = 0
				AND LE.ENTITY_MY = 'Y'
				AND 
				(
					LE.EVENT_ID = '".$event_id."'
					OR LE.EVENT_ID = 'all'
				)
				".$strOfEntities."
				".$strTransport."
			)
			OR
			(
				LE.ENTITY_TYPE = '".$entity_type."'
				AND LE.ENTITY_ID = 0
				AND LE.ENTITY_MY = 'N'
				AND 
				(
					LE.EVENT_ID = '".$event_id."'
					OR LE.EVENT_ID = 'all'
				)
				".$strTransport."
			)
		)";

		return $strSQL;
	}
}
?>