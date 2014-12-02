<?
IncludeModuleLangFile(__FILE__);

class CAllSocNetEventUserView
{

	public static function SetUser($entityID, $feature = false, $permX = false, $bSetFeatures = false)
	{
		global $APPLICATION, $DB;

		$CacheRelatedUsers = array();

		$entityID = IntVal($entityID);
		if ($entityID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		$event_id = array();
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach ($arSocNetLogEvents as $event_tmp_id => $arLogEventTmp)
		{
			if (
				!array_key_exists("ENTITIES", $arLogEventTmp)
				|| !array_key_exists(SONET_ENTITY_USER, $arLogEventTmp["ENTITIES"])
			)
				continue;

			if (
				array_key_exists("NO_SET", $arLogEventTmp)
				&& $arLogEventTmp["NO_SET"]
			)
				continue;

			if (
				array_key_exists("OPERATION", $arLogEventTmp["ENTITIES"][SONET_ENTITY_USER])
				&& strlen($arLogEventTmp["ENTITIES"][SONET_ENTITY_USER]["OPERATION"]) <= 0
			)
				continue;

			$event_id[$arLogEventTmp["ENTITIES"][SONET_ENTITY_USER]["OPERATION"]] = $event_tmp_id;
			
			if (
				array_key_exists("COMMENT_EVENT", $arLogEventTmp)
				&& is_array($arLogEventTmp["COMMENT_EVENT"])
				&& array_key_exists("OPERATION", $arLogEventTmp["COMMENT_EVENT"])				
				&& array_key_exists("EVENT_ID", $arLogEventTmp["COMMENT_EVENT"])
				&& strlen($arLogEventTmp["COMMENT_EVENT"]["OPERATION"]) > 0
				&& strlen($arLogEventTmp["COMMENT_EVENT"]["EVENT_ID"]) > 0
				&& $arLogEventTmp["ENTITIES"][SONET_ENTITY_USER]["OPERATION"] != $arLogEventTmp["COMMENT_EVENT"]["OPERATION"]
			)
				$event_id[$arLogEventTmp["COMMENT_EVENT"]["OPERATION"]] = $arLogEventTmp["COMMENT_EVENT"]["EVENT_ID"];
		}

		if ($feature && !array_key_exists($feature, $event_id))
			return true;

		if ($feature && !is_array($feature))
			$event_id = array($feature => $event_id[$feature]);

		foreach ($event_id as $op => $event)
		{
			if (!CSocNetEventUserView::Delete(SONET_ENTITY_USER, $entityID, $event))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_ERROR_DELETE"), "ERROR_DELETE");
				return false;
			}

			$ar_event_tmp = array($event);
			
			$arCommentEvent = CSocNetLogTools::FindLogCommentEventByLogEventID($event);
			if ($arCommentEvent)
			{
				$event_comment = $arCommentEvent["EVENT_ID"];
				if (!in_array($event_comment, $event_id))
					$ar_event_tmp[] = $event_comment;
			}

			foreach($ar_event_tmp as $event_tmp)
			{
				if ($feature && $permX)
					$perm = $permX;
				else
					$perm = CSocNetUserPerms::GetOperationPerms($entityID, $op);

				if (
					array_key_exists(SONET_ENTITY_USER, $CacheRelatedUsers)
					&& array_key_exists($entityID, $CacheRelatedUsers[SONET_ENTITY_USER])
					&& array_key_exists($perm, $CacheRelatedUsers[SONET_ENTITY_USER][$entityID])
				)
					$arRelatedUsers = $CacheRelatedUsers[SONET_ENTITY_USER][$entityID][$perm];
				else
				{
					$arRelatedUsers = array();

					switch($perm)
					{
						case SONET_RELATIONS_TYPE_FRIENDS:
							$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $entityID);
							$dbFriends = CSocNetUserRelations::GetRelatedUsers($entityID, SONET_RELATIONS_FRIEND);
							while ($arFriends = $dbFriends->Fetch())
							{
								$friendID = (($entityID == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"]);
								$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $friendID);
							}
							break;			
						case SONET_RELATIONS_TYPE_FRIENDS2:
							$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $entityID);
							$dbFriends = CSocNetUserRelations::GetRelatedUsers($entityID, SONET_RELATIONS_FRIEND);
							while ($arFriends = $dbFriends->Fetch())
							{
								$friendID = (($entityID == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"]);
								$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $friendID);

								$dbFriends2 = CSocNetUserRelations::GetRelatedUsers($friendID, SONET_RELATIONS_FRIEND);
								while ($arFriends2 = $dbFriends2->Fetch())
								{
									$friendID2 = (($friendID == $arFriends2["FIRST_USER_ID"]) ? $arFriends2["SECOND_USER_ID"] : $arFriends2["FIRST_USER_ID"]);
									if ($friendID2 != $entityID)
										$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $friendID2, "user_im_id" => $friendID);
								}
							}
							break;
						case SONET_RELATIONS_TYPE_NONE:
							$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $entityID);
							break;
						case SONET_RELATIONS_TYPE_AUTHORIZED:
							$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => 0);
							break;
						case SONET_RELATIONS_TYPE_ALL:
							$arRelatedUsers = false;
							break;
					}
				}
				if (!empty($arRelatedUsers))
					$arRelatedUsers = array_unique($arRelatedUsers);

				$CacheRelatedUsers[SONET_ENTITY_USER][$entityID][$perm] = $arRelatedUsers;
					
				if($arRelatedUsers && is_array($arRelatedUsers))
				{
					foreach($arRelatedUsers as $arRelatedUserID)
					{
						$arFields = array(
							"ENTITY_TYPE" => SONET_ENTITY_USER,
							"ENTITY_ID" => $arRelatedUserID["entity_id"],
							"EVENT_ID" => $event_tmp,
							"USER_ID" => $arRelatedUserID["user_id"],
							"USER_ANONYMOUS" => "N"
						);
							
						if (array_key_exists("user_im_id", $arRelatedUserID))
							$arFields["USER_IM_ID"] = $arRelatedUserID["user_im_id"];
						
						if (!CSocNetEventUserView::Add($arFields))
						{
							$errorMessage = "";
							if ($e = $APPLICATION->GetException())
								$errorMessage = $e->GetString();
							if (StrLen($errorMessage) <= 0)
								$errorMessage = GetMessage("SONET_EUV_ERROR_SET");

							$APPLICATION->ThrowException($errorMessage, "ERROR_SET");
							return false;
						}
					}
				}
				elseif($arRelatedUsers === false)
				{
					$arFields = array(
						"ENTITY_TYPE" => SONET_ENTITY_USER,
						"ENTITY_ID" => $entityID,
						"EVENT_ID" => $event_tmp,
						"USER_ID" => 0,
						"USER_ANONYMOUS" => "Y"
					);
					if (!CSocNetEventUserView::Add($arFields))
					{
						$errorMessage = "";
						if ($e = $APPLICATION->GetException())
							$errorMessage = $e->GetString();
						if (StrLen($errorMessage) <= 0)
							$errorMessage = GetMessage("SONET_EUV_ERROR_SET");

						$APPLICATION->ThrowException($errorMessage, "ERROR_SET");
						return false;
					}
				}
			}
		}

		if ($bSetFeatures)
		{
			$arActiveFeatures = array_keys(CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_USER, $entityID));
			foreach ($arActiveFeatures as $feature)
			{
				CSocNetEventUserView::SetFeature(SONET_ENTITY_USER, $entityID, $feature);
			}
		}

		return true;
	}
	
	public static function SetGroup($entityID, $bSetFeatures = false)
	{
		global $APPLICATION, $DB;

		$entityID = IntVal($entityID);
		if ($entityID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}
		
		$arGroup = CSocNetGroup::GetByID($entityID);
		if (!$arGroup)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_NO_ENTITY"), "SONET_EUV_NO_ENTITY");
			return false;
		}

		$arLogEvent = array();
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		foreach ($arSocNetLogEvents as $event_tmp_id => $arLogEventTmp)
		{
			if (
				!array_key_exists("ENTITIES", $arLogEventTmp)
				|| !array_key_exists(SONET_SUBSCRIBE_ENTITY_GROUP, $arLogEventTmp["ENTITIES"])
			)
				continue;

			if (
				array_key_exists("NO_SET", $arLogEventTmp)
				&& $arLogEventTmp["NO_SET"]
			)
				continue;

			$arLogEvent[] = $event_tmp_id;

			if (
				array_key_exists("COMMENT_EVENT", $arLogEventTmp)
				&& is_array($arLogEventTmp["COMMENT_EVENT"])
				&& array_key_exists("EVENT_ID", $arLogEventTmp["COMMENT_EVENT"])
				&& strlen($arLogEventTmp["COMMENT_EVENT"]["EVENT_ID"]) > 0
			)
				$arLogEvent[] = $arLogEventTmp["COMMENT_EVENT"]["EVENT_ID"];
		}
		$arLogEvent = array_unique($arLogEvent);

		foreach ($arLogEvent as $event_tmp_id)
		{
			if (!CSocNetEventUserView::Delete(SONET_ENTITY_GROUP, $entityID, $event_tmp_id))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_ERROR_DELETE"), "ERROR_DELETE");
				return false;
			}
		}

		$dbResult = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" => $entityID,
				"<=ROLE" => SONET_ROLES_USER,
				"USER_ACTIVE" => "Y"
			),
			false,
			false,
			array("USER_ID")
		);
		while ($arResult = $dbResult->Fetch())
		{
			foreach ($arLogEvent as $event_tmp_id)
			{
				$arFields = array(
					"ENTITY_TYPE" => SONET_ENTITY_GROUP,
					"ENTITY_ID" => $entityID,
					"EVENT_ID" => $event_tmp_id,
					"USER_ID" => $arResult["USER_ID"],
					"USER_ANONYMOUS" => "N"
				);
				if (!CSocNetEventUserView::Add($arFields))
				{
					$errorMessage = "";
					if ($e = $APPLICATION->GetException())
						$errorMessage = $e->GetString();
					if (StrLen($errorMessage) <= 0)
						$errorMessage = GetMessage("SONET_EUV_ERROR_SET");

					$APPLICATION->ThrowException($errorMessage, "ERROR_SET");
						return false;
				}
			}
		}
		
		if ($bSetFeatures)
		{
			$arActiveFeatures = array_keys(CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_GROUP, $entityID));
			foreach ($arActiveFeatures as $feature)
			{
				CSocNetEventUserView::SetFeature(SONET_ENTITY_GROUP, $entityID, $feature);
			}
		}
		
		return true;
	}

	public static function SetFeature($entityType, $entityID, $feature, $op = false, $permX = false, $bCheckEmpty = false)
	{
		global $APPLICATION, $DB, $arSocNetAllowedEntityTypes;

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

		$CacheRelatedUsers = array();
		
		$entityType = trim($entityType);
		if (!in_array($entityType, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_INCORRECT_ENTITY_TYPE"), "ERROR_INCORRECT_ENTITY_TYPE");
			return false;
		}
		
		$entityID = IntVal($entityID);
		if ($entityID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		if (!$bCheckEmpty || !CSocNetEventUserView::IsEntityEmpty($entityType, $entityID))
		{		
			$event_id = array();
			if (!$op || !$permX)
			{
				if (
					array_key_exists($feature, $arSocNetFeaturesSettings) 
					&& array_key_exists("subscribe_events", $arSocNetFeaturesSettings[$feature])
				)
				{
					foreach ($arSocNetFeaturesSettings[$feature]["subscribe_events"] as $event_id_tmp => $arEventIDTmp)
					{
						if (
							array_key_exists("NO_SET", $arEventIDTmp)
							&& $arEventIDTmp["NO_SET"]
						)
							continue;

						if (
							!array_key_exists("ENTITIES", $arEventIDTmp)
							|| !array_key_exists($entityType, $arEventIDTmp["ENTITIES"])
						)
							continue;

						$event_id[$arEventIDTmp["OPERATION"]][] = $event_id_tmp;

						if (
							array_key_exists("COMMENT_EVENT", $arEventIDTmp)
							&& is_array($arEventIDTmp["COMMENT_EVENT"])
							&& array_key_exists("OPERATION", $arEventIDTmp["COMMENT_EVENT"])
							&& strlen($arEventIDTmp["COMMENT_EVENT"]["OPERATION"]) > 0
						)
							$event_id[$arEventIDTmp["OPERATION"]][] = $arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"];
					}
					if (is_array($event_id[$arEventIDTmp["OPERATION"]]))
						$event_id[$arEventIDTmp["OPERATION"]] = array_unique($event_id[$arEventIDTmp["OPERATION"]]);
				}
			}
			else
			{
				$arOpTmp = array();
				if (
					array_key_exists($feature, $arSocNetFeaturesSettings) 
					&& array_key_exists("subscribe_events", $arSocNetFeaturesSettings[$feature])
				)
				{
					foreach ($arSocNetFeaturesSettings[$feature]["subscribe_events"] as $event_id_tmp => $arEventIDTmp)
					{
						if (
							array_key_exists("NO_SET", $arEventIDTmp)
							&& $arEventIDTmp["NO_SET"]
						)
							continue;

						if (
							!array_key_exists("ENTITIES", $arEventIDTmp)
							|| !array_key_exists($entityType, $arEventIDTmp["ENTITIES"])
						)
							continue;

						if (
							!array_key_exists("OPERATION", $arEventIDTmp)
							|| strlen($arEventIDTmp["OPERATION"]) <= 0
						)
							continue;
							
						$arOpTmp[] = $arEventIDTmp["OPERATION"];
						
						if (
							array_key_exists("COMMENT_EVENT", $arEventIDTmp)
							&& is_array($arEventIDTmp["COMMENT_EVENT"])
							&& array_key_exists("OPERATION", $arEventIDTmp["COMMENT_EVENT"])
							&& strlen($arEventIDTmp["COMMENT_EVENT"]["OPERATION"]) > 0
						)
							$arOpTmp[] = $arEventIDTmp["COMMENT_EVENT"]["OPERATION"];
					}
				}
				if (is_array($arOpTmp))
					$arOpTmp = array_unique($arOpTmp);
			
				if (in_array($op, $arOpTmp))
				{
					foreach ($arSocNetFeaturesSettings[$feature]["subscribe_events"] as $event_id_tmp => $arEventIDTmp)
					{
						if ($arEventIDTmp["OPERATION"] == $op)
							$event_id[$op][] = $event_id_tmp;

						if (
							array_key_exists("COMMENT_EVENT", $arEventIDTmp)
							&& is_array($arEventIDTmp["COMMENT_EVENT"])
							&& array_key_exists("OPERATION", $arEventIDTmp["COMMENT_EVENT"])
							&& $arEventIDTmp["COMMENT_EVENT"]["OPERATION"] == $op
						)
							$event_id[$op][] = $arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"];
					}
					if (is_array($event_id[$op]))
						$event_id[$op] = array_unique($event_id[$op]);
				}
				else
					return true;
			}

			if
			(
				intval($entityID) > 0
				&& array_key_exists($entityType, $arSocNetAllowedSubscribeEntityTypesDesc)
				&& array_key_exists("CLASS_DESC_GET", $arSocNetAllowedSubscribeEntityTypesDesc[$entityType])
				&& array_key_exists("METHOD_DESC_GET", $arSocNetAllowedSubscribeEntityTypesDesc[$entityType])
			)
				$arEntityTmp = call_user_func(
					array(
						$arSocNetAllowedSubscribeEntityTypesDesc[$entityType]["CLASS_DESC_GET"],
						$arSocNetAllowedSubscribeEntityTypesDesc[$entityType]["METHOD_DESC_GET"]
					),
					$entityID
				);

			foreach ($event_id as $op => $arEvent)
			{
				$arRelatedUsers = array();

				if (is_array($arEvent))
				{
					foreach($arEvent as $event)
					{
						if (!CSocNetEventUserView::Delete($entityType, $entityID, $feature, $event))
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_ERROR_DELETE"), "ERROR_DELETE");
							return false;
						}
				
						if (!$feature || !$permX)
							$perm = CSocNetFeaturesPerms::GetOperationPerm($entityType, $entityID, $feature, $op);
						else
							$perm = $permX;

						if (
							$entityType == SONET_SUBSCRIBE_ENTITY_GROUP
							&& $arEntityTmp
							&& $arEntityTmp["VISIBLE"] == "N"
							&& $perm > SONET_ROLES_USER
						)
							$perm = SONET_ROLES_USER;
						elseif ($entityType == SONET_SUBSCRIBE_ENTITY_USER)
						{
							$perm_profile = CSocNetUserPerms::GetOperationPerms($entityID, "viewprofile");
							if ($perm < $perm_profile)
								$perm = $perm_profile;
						}
							
						if (
							array_key_exists($entityType, $CacheRelatedUsers)
							&& array_key_exists($entityID, $CacheRelatedUsers[$entityType])
							&& array_key_exists($perm, $CacheRelatedUsers[$entityType][$entityID])
						)
							$arRelatedUsers = $CacheRelatedUsers[$entityType][$entityID][$perm];
						else
						{
							if ($entityType == SONET_SUBSCRIBE_ENTITY_USER)
							{
								switch($perm)
								{
									case SONET_RELATIONS_TYPE_FRIENDS:
										$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $entityID);
										$dbFriends = CSocNetUserRelations::GetRelatedUsers($entityID, SONET_RELATIONS_FRIEND);
										while ($arFriends = $dbFriends->Fetch())
										{
											$friendID = (($entityID == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"]);
											$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $friendID);
										}
										break;			
									case SONET_RELATIONS_TYPE_FRIENDS2:
										$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $entityID);
										$dbFriends = CSocNetUserRelations::GetRelatedUsers($entityID, SONET_RELATIONS_FRIEND);
										while ($arFriends = $dbFriends->Fetch())
										{
											$friendID = (($entityID == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"]);
											$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $friendID);
											
											$dbFriends2 = CSocNetUserRelations::GetRelatedUsers($friendID, SONET_RELATIONS_FRIEND);
											while ($arFriends2 = $dbFriends2->Fetch())
											{
												$friendID2 = (($friendID == $arFriends2["FIRST_USER_ID"]) ? $arFriends2["SECOND_USER_ID"] : $arFriends2["FIRST_USER_ID"]);
												if ($friendID2 != $entityID)
													$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $friendID2, "user_im_id" => $friendID);
											}
										}
										break;
									case SONET_RELATIONS_TYPE_NONE:
										$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => $entityID);
										break;
									case SONET_RELATIONS_TYPE_AUTHORIZED:
										$arRelatedUsers[] = array("entity_id" => $entityID, "user_id" => 0);
										break;
									case SONET_RELATIONS_TYPE_ALL:
										$arRelatedUsers = false;
										break;
								}
								if (!empty($arRelatedUsers))
									$arRelatedUsers = array_unique($arRelatedUsers);
									
								$CacheRelatedUsers[SONET_ENTITY_USER][$entityID][$perm] = $arRelatedUsers;
							}
							elseif ($entityType == SONET_SUBSCRIBE_ENTITY_GROUP)
							{
								switch($perm)
								{
									case SONET_ROLES_USER:
										$dbResult = CSocNetUserToGroup::GetList(
																	array(),
																	array(
																		"GROUP_ID" => $entityID,
																		"<=ROLE" => SONET_ROLES_USER,
																		"USER_ACTIVE" => "Y"
																	),
																	false,
																	false,
																	array("USER_ID")
																);
										while ($arResult = $dbResult->Fetch())
											$arRelatedUsers[] = $arResult["USER_ID"];
										break;	
									case SONET_ROLES_MODERATOR:
										$dbResult = CSocNetUserToGroup::GetList(
																	array(),
																	array(
																		"GROUP_ID" => $entityID,
																		"<=ROLE" => SONET_ROLES_MODERATOR,
																		"USER_ACTIVE" => "Y"
																	),
																	false,
																	false,
																	array("USER_ID")
																);
										while ($arResult = $dbResult->Fetch())
											$arRelatedUsers[] = $arResult["USER_ID"];
										break;	
									case SONET_ROLES_OWNER:
										$dbResult = CSocNetUserToGroup::GetList(
																	array(),
																	array(
																		"GROUP_ID" => $entityID,
																		"<=ROLE" => SONET_ROLES_OWNER,
																		"USER_ACTIVE" => "Y"
																	),
																	false,
																	false,
																	array("USER_ID")
																);
										while ($arResult = $dbResult->Fetch())
											$arRelatedUsers[] = $arResult["USER_ID"];
										break;	
									case SONET_ROLES_AUTHORIZED:
										$arRelatedUsers[] = 0;
										break;
									case SONET_ROLES_ALL:
										$arRelatedUsers = false;
										break;
								}

								if ($arRelatedUsers && is_array($arRelatedUsers) && in_array(0, $arRelatedUsers))
									$arRelatedUsers = array(0);
								elseif ($arRelatedUsers && is_array($arRelatedUsers))
									$arRelatedUsers = array_unique($arRelatedUsers);
									
								$CacheRelatedUsers[SONET_ENTITY_GROUP][$entityID][$perm] = $arRelatedUsers;
							}
						}
						
						if($arRelatedUsers && is_array($arRelatedUsers))
						{
							foreach($arRelatedUsers as $relatedUserID)
							{
								if (is_array($relatedUserID))
								{
									$arFields = array(
										"ENTITY_TYPE" => $entityType,
										"ENTITY_ID" => $relatedUserID["entity_id"],
										"EVENT_ID" => $event,
										"USER_ID" => $relatedUserID["user_id"],
										"USER_ANONYMOUS" => "N"
									);
									
									if (array_key_exists("user_im_id", $relatedUserID))
										$arFields["USER_IM_ID"] = $relatedUserID["user_im_id"];
									
									if (!CSocNetEventUserView::Add($arFields))
									{
										$errorMessage = "";
										if ($e = $APPLICATION->GetException())
											$errorMessage = $e->GetString();
										if (StrLen($errorMessage) <= 0)
											$errorMessage = GetMessage("SONET_EUV_ERROR_SET");

										$APPLICATION->ThrowException($errorMessage, "ERROR_SET");
										return false;
									}						
								}
								else
								{
									$arFields = array(
										"ENTITY_TYPE" => $entityType,
										"ENTITY_ID" => $entityID,
										"EVENT_ID" => $event,
										"USER_ID" => $relatedUserID,
										"USER_ANONYMOUS" => "N"
									);
									if (!CSocNetEventUserView::Add($arFields))
									{
										$errorMessage = "";
										if ($e = $APPLICATION->GetException())
											$errorMessage = $e->GetString();
										if (StrLen($errorMessage) <= 0)
											$errorMessage = GetMessage("SONET_EUV_ERROR_SET");

										$APPLICATION->ThrowException($errorMessage, "ERROR_SET");
										return false;
									}
								}
							}
						}
						else
						{
							$arFields = array(
								"ENTITY_TYPE" => $entityType,
								"ENTITY_ID" => $entityID,
								"EVENT_ID" => $event,
								"USER_ID" => 0,
								"USER_ANONYMOUS" => "Y"
							);
							if (!CSocNetEventUserView::Add($arFields))
							{
								$errorMessage = "";
								if ($e = $APPLICATION->GetException())
									$errorMessage = $e->GetString();
								if (StrLen($errorMessage) <= 0)
									$errorMessage = GetMessage("SONET_EUV_ERROR_SET");

								$APPLICATION->ThrowException($errorMessage, "ERROR_SET");
									return false;
							}
						}
					}
				}
			}
		}
		elseif($entityType == SONET_ENTITY_GROUP)
			CSocNetEventUserView::SetGroup($entityID, true);
		elseif($entityType == SONET_ENTITY_USER)
			CSocNetEventUserView::SetUser($entityID, false, false, true);

		return true;
	}

	public static function Entity2UserAdd($entityType, $entityID, $userID, $role)
	{
		global $APPLICATION, $DB, $arSocNetAllowedEntityTypes;

		$CacheRelatedUsers = array();

		$entityType = trim($entityType);
		if (!in_array($entityType, $arSocNetAllowedEntityTypes))
		{
			$APPLICATION->ThrowException(GetMessage("SONET_EUV_INCORRECT_ENTITY_TYPE"), "ERROR_INCORRECT_ENTITY_TYPE");
			return false;
		}
		
		$entityID = IntVal($entityID);
		if ($entityID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}
		
		$userID = IntVal($userID);
		if ($userID <= 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_EUV_EMPTY_USER_ID"), "ERROR_EMPTY_USER_ID");
			return false;
		}

		if (is_array($role))
		{
			if (count($role) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage("SONET_EUV_EMPTY_ROLE"), "ERROR_EMPTY_ROLE");
				return false;
			}
		}
		else
		{
			$role = trim($role);
			if (strlen($role) <= 0)
			{
				$APPLICATION->ThrowException(GetMessage("SONET_EUV_EMPTY_ROLE"), "ERROR_EMPTY_ROLE");
				return false;
			}
			$role = array($role);
		}

		if (!CSocNetEventUserView::IsEntityEmpty($entityType, $entityID))
		{
			$arEvents = array();
			$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

			foreach ($arSocNetLogEvents as $event_tmp_id => $arLogEventTmp)
			{
				if (
					!array_key_exists("ENTITIES", $arLogEventTmp)
					|| !array_key_exists($entityType, $arLogEventTmp["ENTITIES"])
				)
					continue;

				if (
					array_key_exists("NO_SET", $arLogEventTmp)
					&& $arLogEventTmp["NO_SET"]
				)
					continue;

				$arEvents[] = $event_tmp_id;			

				if (
					array_key_exists("COMMENT_EVENT", $arLogEventTmp)
					&& is_array($arLogEventTmp["COMMENT_EVENT"])
					&& array_key_exists("EVENT_ID", $arLogEventTmp["COMMENT_EVENT"])
					&& strlen($arLogEventTmp["COMMENT_EVENT"]["EVENT_ID"]) > 0
				)
					$arEvents[] = $arLogEventTmp["COMMENT_EVENT"]["EVENT_ID"];
			}

			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedEntityTypes();
			foreach ($arSocNetFeaturesSettings as $feature => $arFeature)
			{
				if (!array_key_exists("subscribe_events", $arFeature))
				{
					continue;
				}

				foreach ($arFeature["subscribe_events"] as $event_id_tmp => $arEventIDTmp)
				{
					if (
						array_key_exists("NO_SET", $arEventIDTmp)
						&& $arEventIDTmp["NO_SET"]
					)
						continue;

					if (
						!array_key_exists("OPERATION", $arEventIDTmp)
						|| strlen($arEventIDTmp["OPERATION"]) <= 0
					)
						continue;

					$featureOperationPerms = CSocNetFeaturesPerms::GetOperationPerm($entityType, $entityID, $feature, $arEventIDTmp["OPERATION"]);
					if (in_array($featureOperationPerms, $role))
						$arEvents[] = $event_id_tmp;

					if (
						array_key_exists("COMMENT_EVENT", $arEventIDTmp)
						&& is_array($arEventIDTmp["COMMENT_EVENT"])
						&& array_key_exists("EVENT_ID", $arEventIDTmp["COMMENT_EVENT"])
						&& array_key_exists("OPERATION", $arEventIDTmp["COMMENT_EVENT"])
						&& strlen($arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"]) > 0
						&& strlen($arEventIDTmp["COMMENT_EVENT"]["OPERATION"]) > 0
						&& ($arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"] != $event_id_tmp)
					)
					{
						$featureOperationPerms = CSocNetFeaturesPerms::GetOperationPerm($entityType, $entityID, $feature, $arEventIDTmp["COMMENT_EVENT"]["OPERATION"]);
						if (in_array($featureOperationPerms, $role))
							$arEvents[] = $arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"];
					}
				}
			}
			$arEvents = array_unique($arEvents);

			foreach($arEvents as $event)
			{
				$arFieldsEUV = array(
					"ENTITY_TYPE" => SONET_ENTITY_GROUP,
					"ENTITY_ID" => $entityID,
					"EVENT_ID" => $event,
					"USER_ID" => $userID,
					"USER_ANONYMOUS" => "N"
				);
				CSocNetEventUserView::Add($arFieldsEUV);
			}
		}
		elseif($entityType == SONET_ENTITY_GROUP)
			CSocNetEventUserView::SetGroup($entityID, true);
		elseif($entityType == SONET_ENTITY_USER)
			CSocNetEventUserView::SetUser($entityID, false, false, true);
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;

		$arSocNetAllowedSubscribeEntityTypes = CSocNetAllowed::GetAllowedEntityTypes();

		if (!array_key_exists("ENTITY_TYPE", $arFields))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_TYPE"), "ERROR_EMPTY_ENTITY_TYPE");
			return false;
		}

		if (!in_array($arFields["ENTITY_TYPE"], CSocNetAllowed::GetAllowedEntityTypes()))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_INCORRECT_ENTITY_TYPE"), "ERROR_INCORRECT_ENTITY_TYPE");
			return false;
		}

		if (!array_key_exists("ENTITY_ID", $arFields))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		// check primary key
		if ($ACTION == "ADD")
		{
			$dbResult = CSocNetEventUserView::GetList(
					Array("ENTITY_ID" => "DESC"), 
					Array(
						"ENTITY_TYPE" 	=> $arFields["ENTITY_TYPE"],
						"ENTITY_ID" 	=> intval($arFields["ENTITY_ID"]),
						"EVENT_ID" 		=> (array_key_exists("EVENT_ID", $arFields) ? $arFields["EVENT_ID"] : ""),
						"USER_ID" 		=> (array_key_exists("USER_ID", $arFields) ? intval($arFields["USER_ID"]) : 0),
						"USER_IM_ID" 	=> (array_key_exists("USER_IM_ID", $arFields) ? intval($arFields["USER_IM_ID"]) : 0)
					)
				);

			if ($arRes = $dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_RECORD_EXISTS"), "ERROR_RECORD_EXISTS");
				return false;
			}
		}

		return True;
	}

	public static function Delete($entityType, $entityID, $feature = false, $event = false)	
	{
		global $DB;

		$arSocNetAllowedSubscribeEntityTypes = CSocNetAllowed::GetAllowedEntityTypes();
		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		$arSocNetLogEvents = CSocNetAllowed::GetAllowedLogEvents();

		$entityType = trim($entityType);

		if (!in_array($entityType, CSocNetAllowed::GetAllowedEntityTypes()))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_INCORRECT_ENTITY_TYPE"), "ERROR_INCORRECT_ENTITY_TYPE");
			return false;
		}

		$entityID = IntVal($entityID);
		if ($entityID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		$strWhere = " WHERE ENTITY_TYPE = '".$entityType."' AND ENTITY_ID = ".$entityID;
		if ($feature)
		{
			if ($event)
				$strWhere .= " AND EVENT_ID = '".$event."'";
			else
			{
				$event_id = array();

				if (
					(
						array_key_exists($feature, $arSocNetLogEvents)
						&& array_key_exists("ENTITIES", $arSocNetLogEvents[$feature])
						&& array_key_exists($entityType, $arSocNetLogEvents[$feature]["ENTITIES"])
					)
					||
					(
						array_key_exists($feature, $arSocNetFeaturesSettings)
						&& array_key_exists("subscribe_events", $arSocNetFeaturesSettings[$feature])
						&& count($arSocNetFeaturesSettings[$feature]["subscribe_events"]) > 0
					)
				)
				{
					if (array_key_exists($feature, $arSocNetLogEvents))
					{
						$event_id[] = $feature;

						if (
							array_key_exists("COMMENT_EVENT", $arSocNetLogEvents[$feature])
							&& is_array($arSocNetLogEvents[$feature]["COMMENT_EVENT"])
							&& array_key_exists("EVENT_ID", $arSocNetLogEvents[$feature]["COMMENT_EVENT"])
							&& strlen($arSocNetLogEvents[$feature]["COMMENT_EVENT"]["EVENT_ID"]) > 0
						)
							$event_id[] = $arSocNetLogEvents[$feature]["COMMENT_EVENT"]["EVENT_ID"];
					}	

					if (
						array_key_exists($feature, $arSocNetFeaturesSettings)
						&& array_key_exists("subscribe_events", $arSocNetFeaturesSettings[$feature])
						&& count($arSocNetFeaturesSettings[$feature]["subscribe_events"]) > 0
					)
					{
						foreach ($arSocNetFeaturesSettings[$feature]["subscribe_events"] as $event_id_tmp => $arEventIDTmp)
						{
							if (
								array_key_exists("NO_SET", $arEventIDTmp)
								&& $arEventIDTmp["NO_SET"]
							)
							{
								continue;
							}

							$event_id[] = $event_id_tmp;

							if (
								array_key_exists("COMMENT_EVENT", $arEventIDTmp)
								&& is_array($arEventIDTmp["COMMENT_EVENT"])
								&& array_key_exists("EVENT_ID", $arEventIDTmp["COMMENT_EVENT"])
								&& strlen($arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"]) > 0
							)
								$event_id[] = $arEventIDTmp["COMMENT_EVENT"]["EVENT_ID"];
						}
					}
					$event_id = array_unique($event_id);
					
					$strWhere .= " AND (";
					$i = 0;
					foreach ($event_id as $ev)
					{
						if ($i > 0)
							$strWhere .= " OR ";
						$strWhere .= "EVENT_ID = '".$ev."'";					
						$i++;
					}
					$strWhere .= ")";
				}
			}
		}
		$bSuccess = $DB->Query("DELETE FROM b_sonet_event_user_view".$strWhere, true);

		return $bSuccess;
	}

	public static function IsEntityEmpty($entityType, $entityID)
	{
		global $arSocNetAllowedEntityTypes;

		$entityType = trim($entityType);
		if (!in_array($entityType, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_INCORRECT_ENTITY_TYPE"), "ERROR_INCORRECT_ENTITY_TYPE");
			return false;
		}
		
		$entityID = IntVal($entityID);
		if ($entityID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_EUV_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}
	
		$iCnt = CSocNetEventUserView::GetList(array(), array("ENTITY_TYPE" => $entityType, "ENTITY_ID" => $entityID), array());
		if (intval($iCnt) > 0)
			return false;
		else
			return true;	
	}
	
	public static function CheckPermissions($table, $user_id)
	{
		if ($user_id === false)
			$strUser = " AND EUV.USER_ANONYMOUS = 'Y' AND EUV.USER_ID = 0";
		else
			$strUser = " AND EUV.USER_ID IN (".intval($user_id).", 0)";

		return "INNER JOIN b_sonet_event_user_view EUV ".(strtolower($GLOBALS["DB"]->type) == "mysql" ? "USE INDEX (IX_SONET_EVENT_USER_VIEW_2)" : "")." ON
						EUV.ENTITY_TYPE = ".$table.".ENTITY_TYPE 
						AND ( 
							EUV.ENTITY_ID = ".$table.".ENTITY_ID
							OR EUV.ENTITY_ID = 0
						)
						AND EUV.EVENT_ID = ".$table.".EVENT_ID ".$strUser;
	}
	
	public static function CheckPermissionsByEvent($entity_type, $entity_id, $event_id, $user_id)
	{
		global $DB;

		$user_id = IntVal($user_id);
		if ($user_id <= 0)
			$user_id = $GLOBALS["USER"]->GetID();
		if ($user_id <= 0)
			return false;
			
		$entity_id = IntVal($entity_id);
		if ($entity_id <= 0)
			return false;

		$entity_type = trim($entity_type);
		if (strlen($entity_type) <= 0)
			return false;

		$event_id = trim($event_id);
		if (strlen($event_id) <= 0)
			return false;

		$strSQL = "SELECT USER_ID FROM b_sonet_event_user_view WHERE
							ENTITY_TYPE = '".$DB->ForSQL($entity_type)."'
							AND ENTITY_ID IN (0, ".$entity_id.")
							AND EVENT_ID = '".$DB->ForSQL($event_id)."'
							AND USER_ID IN (0, ".$user_id.")";

		$dbRes = $GLOBALS["DB"]->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
			return true;
		else
			return false;

	}	
}
?>