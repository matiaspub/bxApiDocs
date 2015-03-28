<?
IncludeModuleLangFile(__FILE__);

class CCrmLiveFeedEntity
{
	const Lead = SONET_CRM_LEAD_ENTITY;
	const Contact = SONET_CRM_CONTACT_ENTITY;
	const Company = SONET_CRM_COMPANY_ENTITY;
	const Deal = SONET_CRM_DEAL_ENTITY;
	const Activity = SONET_CRM_ACTIVITY_ENTITY;
	const Invoice = SONET_CRM_INVOICE_ENTITY;
	const Undefined = '';

	private static $ALL_FOR_SQL = null;

	public static function IsDefined($entityType)
	{
		return $entityType === self::Lead
			|| $entityType === self::Contact
			|| $entityType === self::Company
			|| $entityType === self::Deal
			|| $entityType === self::Activity
			|| $entityType === self::Invoice;
	}
	public static function GetAll()
	{
		return array(self::Lead, self::Contact, self::Company, self::Deal, self::Activity, self::Invoice);
	}

	/*
	 * Get types that do not have dependencies
	 * */
	public static function GetLeafs()
	{
		return array(self::Activity, self::Invoice);
	}

	public static function GetAllForSql()
	{
		if(self::$ALL_FOR_SQL === null)
		{
			global $DB;
			self::$ALL_FOR_SQL = array(
				"'".($DB->ForSql(self::Lead))."'",
				"'".($DB->ForSql(self::Contact))."'",
				"'".($DB->ForSql(self::Company))."'",
				"'".($DB->ForSql(self::Deal))."'",
				"'".($DB->ForSql(self::Activity))."'",
				"'".($DB->ForSql(self::Invoice))."'"
			);
		}
		return self::$ALL_FOR_SQL;
	}

	public static function GetForSql($types)
	{
		if(!is_array($types) || empty($types))
		{
			return self::GetAllForSql();
		}

		global $DB;
		$result = array();
		foreach($types as $type)
		{
			if(self::IsDefined($type))
			{
				$result[] = "'".($DB->ForSql($type))."'";
			}
		}
		return $result;
	}

	public static function GetForSqlString($types)
	{
		return implode(',', self::GetForSql($types));
	}

	public static function ResolveEntityTypeID($entityType)
	{
		switch($entityType)
		{
			case self::Lead:
			{
				return CCrmOwnerType::Lead;
			}
			case self::Contact:
			{
				return CCrmOwnerType::Contact;
			}
			case self::Company:
			{
				return CCrmOwnerType::Company;
			}
			case self::Deal:
			{
				return CCrmOwnerType::Deal;
			}
			case self::Activity:
			{
				return CCrmOwnerType::Activity;
			}
			case self::Invoice:
			{
				return CCrmOwnerType::Invoice;
			}
			default:
			{
				return CCrmOwnerType::Undefined;
			}
		}
	}
	public static function GetByEntityTypeID($entityTypeID)
	{
		switch($entityTypeID)
		{
			case CCrmOwnerType::Lead:
			{
				return self::Lead;
			}
			case CCrmOwnerType::Contact:
			{
				return self::Contact;
			}
			case CCrmOwnerType::Company:
			{
				return self::Company;
			}
			case CCrmOwnerType::Deal:
			{
				return self::Deal;
			}
			case CCrmOwnerType::Activity:
			{
				return self::Activity;
			}
			case CCrmOwnerType::Invoice:
			{
				return self::Invoice;
			}
			default:
			{
				return self::Undefined;
			}
		}
	}
}

class CCrmLiveFeedEvent
{
	// Entity prefixes -->
	const LeadPrefix = 'crm_lead_';
	const ContactPrefix = 'crm_contact_';
	const CompanyPrefix = 'crm_company_';
	const DealPrefix = 'crm_deal_';
	const ActivityPrefix = 'crm_activity_';
	const InvoicePrefix = 'crm_invoice_';
	//<-- Entity prefixes

	// Event -->
	const Add = 'add';
	const Progress = 'progress';
	const Denomination = 'denomination';
	const Responsible = 'responsible';
	const Client = 'client';
	const Owner = 'owner';
	const Message = 'message';
	const Custom = 'custom';
	//<-- Event

	const CommentSuffix = '_comment';
	public static function GetEventID($entityTypeID, $eventType)
	{
		switch($entityTypeID)
		{
			//Event IDs like crm_lead_add, crm_lead_add_comment
			case CCrmLiveFeedEntity::Lead:
			{
				return self::LeadPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Contact:
			{
				return self::ContactPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Company:
			{
				return self::CompanyPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Deal:
			{
				return self::DealPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Activity:
			{
				return self::ActivityPrefix.$eventType;
			}
			case CCrmLiveFeedEntity::Invoice:
			{
				return self::InvoicePrefix.$eventType;
			}
		}

		return '';
	}
	public static function PrepareEntityEventInfos($entityTypeID)
	{
		$result = array();

		$prefix = '';
		$events = null;
		switch($entityTypeID)
		{
			case CCrmLiveFeedEntity::Lead:
			{
				$prefix = self::LeadPrefix;
				$events = array(
					self::Add,
					self::Progress,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Deal:
			{
				$prefix = self::DealPrefix;
				$events = array(
					self::Add,
					self::Client,
					self::Progress,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Company:
			{
				$prefix = self::CompanyPrefix;
				$events = array(
					self::Add,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Contact:
			{
				$prefix = self::ContactPrefix;
				$events = array(
					self::Add,
					self::Owner,
					self::Responsible,
					self::Denomination,
					self::Message
				);
			}
			break;
			case CCrmLiveFeedEntity::Activity:
			{
				$prefix = self::ActivityPrefix;
				$events = array(self::Add);
			}
			break;
			case CCrmLiveFeedEntity::Invoice:
			{
				$prefix = self::InvoicePrefix;
				$events = array(self::Add);
			}
			break;
		}

		if(is_array($events))
		{
			foreach($events as &$event)
			{
				$eventID = "{$prefix}{$event}";
				$result[] = array(
					'EVENT_ID' => $eventID,
					'COMMENT_EVENT_ID' => $eventID.self::CommentSuffix,
					'COMMENT_ADD_CALLBACK' => (
						($prefix == self::ActivityPrefix && $event == self::Add) 
							? array("CCrmLiveFeed", "AddCrmActivityComment")
							: false
					),
					'COMMENT_UPDATE_CALLBACK' => (
						($prefix == self::ActivityPrefix && $event == self::Add) 
							? array("CCrmLiveFeed", "UpdateCrmActivityComment")
							: "NO_SOURCE"
					),
					'COMMENT_DELETE_CALLBACK' => (
						($prefix == self::ActivityPrefix && $event == self::Add) 
							? array("CCrmLiveFeed", "DeleteCrmActivityComment")
							: "NO_SOURCE"
					)
				);
			}
			unset($event);
		}

		return $result;
	}
}

class CCrmLiveFeed
{
	const UntitledMessageStub = '__EMPTY__';

	public static function OnFillSocNetAllowedSubscribeEntityTypes(&$entityTypes)
	{
		$typeNames =  CCrmLiveFeedEntity::GetAll();
		foreach($typeNames as $typeName)
		{
			$entityTypes[] = $typeName;
		}

		global $arSocNetAllowedSubscribeEntityTypesDesc;
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Lead] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Contact] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Company] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Deal] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
		$arSocNetAllowedSubscribeEntityTypesDesc[CCrmLiveFeedEntity::Activity] = array(
			'USE_CB_FILTER' => 'Y',
			'HAS_CB' => 'N'
		);
	}
	public static function OnFillSocNetLogEvents(&$events)
	{
		$lf_entities = CCrmLiveFeedEntity::GetAll();

		foreach($lf_entities as $lf_entity)
		{
			$infos = CCrmLiveFeedEvent::PrepareEntityEventInfos($lf_entity);
			if(!empty($infos))
			{
				foreach($infos as &$info)
				{
					$eventID = $info['EVENT_ID'];
					$commentEventID = $info['COMMENT_EVENT_ID'];

					$events[$eventID] = array(
						'ENTITIES' => array(
							$lf_entity => array(),
						),
						'CLASS_FORMAT' => 'CCrmLiveFeed',
						'METHOD_FORMAT' => 'FormatEvent',
						'HAS_CB' => 'N',
						'COMMENT_EVENT' => array(
							'EVENT_ID' => $commentEventID,
							'CLASS_FORMAT' => 'CCrmLiveFeed',
							'METHOD_FORMAT' => 'FormatComment'
						)
					);

					if (!empty($info['COMMENT_ADD_CALLBACK']))
					{
						$events[$eventID]['COMMENT_EVENT']['ADD_CALLBACK'] = $info['COMMENT_ADD_CALLBACK'];
					}

					if (!empty($info['COMMENT_UPDATE_CALLBACK']))
					{
						$events[$eventID]['COMMENT_EVENT']['UPDATE_CALLBACK'] = $info['COMMENT_UPDATE_CALLBACK'];
					}

					if (!empty($info['COMMENT_DELETE_CALLBACK']))
					{
						$events[$eventID]['COMMENT_EVENT']['DELETE_CALLBACK'] = $info['COMMENT_DELETE_CALLBACK'];
					}
				}
				unset($info);
			}
		}
	}
	public static function FormatEvent($arFields, $arParams, $bMail = false)
	{
		ob_start();
		if ($arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Activity)
		{
			if ($arActivity = CCrmActivity::GetByID($arFields["ENTITY_ID"], false))
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("CRM_ACTIVITY_".$arFields["ENTITY_ID"]);
				}

				$arActivity["COMMUNICATIONS"] = CCrmActivity::GetCommunications($arActivity["ID"]);
				$arComponentReturn = $GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.livefeed.activity', '', array(
					'FIELDS' => $arFields,
					'ACTIVITY' => $arActivity,
					'PARAMS' => $arParams
				), null, array('HIDE_ICONS' => 'Y'));
			}
		}
		elseif ($arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Invoice)
		{
			if ($arInvoice = CCrmInvoice::GetByID($arFields["ENTITY_ID"]))
			{
				if (!array_key_exists("URL", $arInvoice))
				{
					$arInvoice["URL"] = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Invoice, $arFields["ENTITY_ID"]);
				}

				$arComponentReturn = $GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.livefeed.invoice', '', array(
					'FIELDS' => $arFields,
					'INVOICE' => $arInvoice,
					'PARAMS' => $arParams
				), null, array('HIDE_ICONS' => 'Y'));
			}
		}
		else
		{
			$arComponentReturn = $GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.livefeed', '', array(
				'FIELDS' => $arFields,
				'PARAMS' => $arParams
			), null, array('HIDE_ICONS' => 'Y'));
		}

		$html_message = ob_get_contents();

		ob_end_clean();

		$arRights = array();
		$arEventFields = array(
			"LOG_ID" => $arFields["ID"],
			"EVENT_ID" => $arFields["EVENT_ID"]
		);
			
		if ($arParams["MOBILE"] == "Y")
		{
			self::OnBeforeSocNetLogEntryGetRights($arEventFields, $arRights);
			$arDestination = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"], "USE_ALL_DESTINATION" => true)), $iMoreCount);

			if (
				$arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Activity
				&& $arActivity
				&& $arActivity["TYPE_ID"] == CCrmActivityType::Task
			)
			{
				$title_24 = '';
			}
			else
			{
				$title_24 = GetMessage('CRM_LF_MESSAGE_TITLE_24');
			}

			$arResult = array(
				'EVENT' => $arFields,
				'EVENT_FORMATTED' => array(
					'TITLE_24' => $title_24,
					"MESSAGE" => htmlspecialcharsbx($html_message),
					"IS_IMPORTANT" => false,
					"DESTINATION" => $arDestination
				),
				"AVATAR_SRC" => CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY')
			);
		}
		else
		{
			if ($arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity)
			{
				$arEventFields["ACTIVITY"] = $arActivity;
			}
			elseif ($arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Invoice)
			{
				$arEventFields["ACTIVITY"] = $arInvoice;
			}

			self::OnBeforeSocNetLogEntryGetRights($arEventFields, $arRights);

			if (
				$arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity 
				&& $arActivity
			)
			{
				if ($arActivity["TYPE_ID"] == CCrmActivityType::Call)
				{
					if($arActivity["DIRECTION"] == CCrmActivityDirection::Incoming)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_CALL_INCOMING_TITLE");
					}
					elseif($arActivity["DIRECTION"] == CCrmActivityDirection::Outgoing)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_CALL_OUTGOING_TITLE");
					}
					$title24_2 = str_replace(
						"#COMPLETED#",
						"<i>".GetMessage($arActivity["COMPLETED"] == "Y" ? "CRM_LF_ACTIVITY_CALL_COMPLETED" : "")."</i>",
						$title24_2
					);
				}
				elseif ($arActivity["TYPE_ID"] == CCrmActivityType::Email)
				{
					if($arActivity["DIRECTION"] == CCrmActivityDirection::Incoming)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_EMAIL_INCOMING_TITLE");
					}
					elseif($arActivity["DIRECTION"] == CCrmActivityDirection::Outgoing)
					{
						$title24_2 = GetMessage("CRM_LF_ACTIVITY_EMAIL_OUTGOING_TITLE");
					}
				}
				elseif ($arActivity["TYPE_ID"] == CCrmActivityType::Meeting)
				{
					$title24_2 = GetMessage("CRM_LF_ACTIVITY_MEETING_TITLE");

					$title24_2 = str_replace(
						"#COMPLETED#",
						"<i>".GetMessage($arActivity["COMPLETED"] == "Y" ? "CRM_LF_ACTIVITY_MEETING_COMPLETED" : "CRM_LF_ACTIVITY_MEETING_NOT_COMPLETED")."</i>",
						$title24_2
					);
				}
				$title24_2_style = "crm-feed-activity-status";
			}

			$arDestination = CSocNetLogTools::FormatDestinationFromRights($arRights, array_merge($arParams, array("CREATED_BY" => $arFields["USER_ID"], "USE_ALL_DESTINATION" => true)), $iMoreCount);

			$arResult = array(
				'EVENT' => $arFields,
				'EVENT_FORMATTED' => array(
					'URL' => "",
					"MESSAGE" => htmlspecialcharsbx($html_message),
					"IS_IMPORTANT" => false,
					"DESTINATION" => $arDestination
				)
			);

			if (
				$arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity
				&& $arActivity["TYPE_ID"] == CCrmActivityType::Email
				&& $arActivity["DIRECTION"] == CCrmActivityDirection::Incoming
			)
			{
				switch ($arActivity['OWNER_TYPE_ID'])
				{
					case CCrmOwnerType::Company:
						$rsCrmCompany = CCrmCompany::GetListEx(array(), array('ID' => $arActivity['OWNER_ID'], 'CHECK_PERMISSIONS' => 'N'), false, false, array('LOGO'));
						if ($arCrmCompany = $rsCrmCompany->Fetch())
						{
							$fileID = $arCrmCompany['LOGO'];
						}
						break;
					case CCrmOwnerType::Contact:
						$rsCrmContact = CCrmContact::GetListEx(array(), array('ID' => $arActivity['OWNER_ID'], 'CHECK_PERMISSIONS' => 'N'), false, false, array('PHOTO'));
						if ($arCrmContact = $rsCrmContact->Fetch())
						{
							$fileID = $arCrmContact['PHOTO'];
						}
						break;
					default:
						$fileID = false;
				}

				$arResult["AVATAR_SRC"] = CSocNetLog::FormatEvent_CreateAvatar(
					array(
						'PERSONAL_PHOTO' => $fileID
					), 
					$arParams, 
					''
				);
			}
			else
			{
				$arResult["AVATAR_SRC"] = CSocNetLog::FormatEvent_CreateAvatar($arFields, $arParams, 'CREATED_BY');
			}

			if (isset($title24_2))
			{
				$arResult["EVENT_FORMATTED"]["TITLE_24_2"] = $title24_2;
				if (isset($title24_2_style))
				{
					$arResult["EVENT_FORMATTED"]["TITLE_24_2_STYLE"] = $title24_2_style;
				}
			}

			$arResult["CACHED_CSS_PATH"] = array(
				"/bitrix/themes/.default/crm-entity-show.css"
			);

			$arResult["CACHED_JS_PATH"] = array(
				"/bitrix/js/crm/progress_control.js",
				"/bitrix/js/crm/activity.js",
				"/bitrix/js/crm/common.js"
			);
			
			if (IsModuleInstalled("tasks"))
			{
				$arResult["CACHED_CSS_PATH"][] = "/bitrix/js/tasks/css/tasks.css";
			}
			
			if (is_array($arComponentReturn) && !empty($arComponentReturn["CACHED_CSS_PATH"]))
			{
				$arResult["CACHED_CSS_PATH"][] = $arComponentReturn["CACHED_CSS_PATH"];			
			}

			if (is_array($arComponentReturn) && !empty($arComponentReturn["CACHED_JS_PATH"]))
			{
				$arResult["CACHED_JS_PATH"][] = $arComponentReturn["CACHED_JS_PATH"];			
			}

			if (intval($iDestinationsMore) > 0)
			{
				$arResult["EVENT_FORMATTED"]["DESTINATION_MORE"] = $iDestinationsMore;
			}
		}

		if (
			$arFields["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity
			&& $arActivity["TYPE_ID"] == CCrmActivityType::Email
			&& $arActivity["DIRECTION"] == CCrmActivityDirection::Incoming
		)
		{
			$arResult['CREATED_BY']['FORMATTED'] = CCrmOwnerType::GetCaption($arActivity['OWNER_TYPE_ID'], $arActivity['OWNER_ID'], false);
		}
		else
		{
			$arFieldsTooltip = array(
				'ID' => $arFields['USER_ID'],
				'NAME' => $arFields['~CREATED_BY_NAME'],
				'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
				'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME'],
				'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
			);
			$arResult['CREATED_BY']['TOOLTIP_FIELDS'] = CSocNetLog::FormatEvent_FillTooltip($arFieldsTooltip, $arParams);
		}

		if (
			$arFields['ENTITY_TYPE'] == CCrmLiveFeedEntity::Activity
			&& $arActivity
			&& $arActivity["TYPE_ID"] == CCrmActivityType::Task
		)
		{
			$arResult["COMMENTS_PARAMS"] = array(
				"ENTITY_TYPE" => "TK",
				"ENTITY_XML_ID" => "TASK_".$arActivity["ASSOCIATED_ENTITY_ID"],
				"NOTIFY_TAGS" => "FORUM|COMMENT"
			);
		}

		return $arResult;
	}
	public static function FormatComment($arFields, $arParams, $bMail = false, $arLog = array())
	{
		$arResult = array(
			"EVENT_FORMATTED" => array(),
		);

		if (!CModule::IncludeModule("socialnetwork"))
		{
			return $arResult;
		}

		$arResult["EVENT_FORMATTED"] = array(
			"TITLE" => "",
			"MESSAGE" => $arFields["MESSAGE"]
		);

		static $parserLog = false;
		if (CModule::IncludeModule("forum"))
		{
			$arAllow = array(
				"HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "LOG_IMG" => "N",
				"QUOTE" => "Y", "LOG_QUOTE" => "N",
				"CODE" => "Y", "LOG_CODE" => "N",
				"FONT" => "Y", "LOG_FONT" => "N",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "Y",
				"MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N",
				"USERFIELDS" => $arFields["UF"],
				"USER" => "Y",
				"ALIGN" => "Y"
			);

			if (!$parserLog)
			{
				$parserLog = new forumTextParser(LANGUAGE_ID);
			}

			$parserLog->arUserfields = $arFields["UF"];
			$parserLog->pathToUser = $arParams["PATH_TO_USER"];
			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), $arAllow));
		}
		else
		{
			$arAllow = array(
				"HTML" => "Y", "ANCHOR" => "Y", "BIU" => "Y",
				"IMG" => "Y", "LOG_IMG" => "N",
				"QUOTE" => "Y", "LOG_QUOTE" => "N",
				"CODE" => "Y", "LOG_CODE" => "N",
				"FONT" => "Y", "LOG_FONT" => "N",
				"LIST" => "Y",
				"SMILES" => "Y",
				"NL2BR" => "Y",
				"MULTIPLE_BR" => "N",
				"VIDEO" => "Y", "LOG_VIDEO" => "N",
				"USERFIELDS" => $arFields["UF"]
			);

			if (!$parserLog)
			{
				$parserLog = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			}

			$arResult["EVENT_FORMATTED"]["MESSAGE"] = htmlspecialcharsbx($parserLog->convert(htmlspecialcharsback($arResult["EVENT_FORMATTED"]["MESSAGE"]), array(), $arAllow));
		}
		return $arResult;
	}
	static private function BuildRelationSelectSql(&$params)
	{
		$slRelTableName =  CCrmSonetRelation::TABLE_NAME;
		$parent = isset($params['PARENT']) ? $params['PARENT'] : array();
		$level = 1;
		$parents = array($parent);

		$nextParent = isset($parent['PARENT']) ? $parent['PARENT'] : null;
		while(is_array($nextParent))
		{
			$level++;
			$parents[] = $nextParent;
			$nextParent = isset($nextParent['PARENT']) ? $nextParent['PARENT'] : null;
		}

		$curParent = $parents[$level - 1];
		$parentEntityType = isset($curParent['ENTITY_TYPE']) ? $curParent['ENTITY_TYPE'] : '';
		$parentEntityID = isset($curParent['ENTITY_ID']) ? intval($curParent['ENTITY_ID']) : 0;

		$alias = 'R';
		$sqlFrom = "{$slRelTableName} R";
		$sqlWhere = "R.SL_PARENT_ENTITY_TYPE = '{$parentEntityType}' AND R.PARENT_ENTITY_ID = {$parentEntityID} AND R.LVL = {$level}";

		$subFilters = isset($params['SUB_FILTERS']) ? $params['SUB_FILTERS'] : null;
		if(!is_array($subFilters) || empty($subFilters))
		{
			return false;
		}

		$subFilterResults = array();
		foreach($subFilters as &$subFilter)
		{
			$entityType = isset($subFilter['ENTITY_TYPE']) ? $subFilter['ENTITY_TYPE'] : '';
			if($entityType === '')
			{
				continue;
			}

			$eventID = isset($subFilter['EVENT_ID']) ? $subFilter['EVENT_ID'] : '';
			$eventIDs = '';
			if(is_string($eventID) && $eventID !== '')
			{
				$eventIDs = "'{$eventID}'";
			}
			elseif(is_array($eventID))
			{
				foreach($eventID as $v)
				{
					if($v === '')
					{
						continue;
					}

					if($eventIDs !== '')
					{
						$eventIDs .= ', ';
					}

					$eventIDs .= "'{$v}'";
				}
			}

			$subFilterResults[] = $eventIDs !== ''
				? "({$alias}.SL_ENTITY_TYPE = '{$entityType}' AND {$alias}.SL_EVENT_ID IN ({$eventIDs}))"
				: "{$alias}.SL_ENTITY_TYPE = '{$entityType}'";
		}
		unset($subFilter);

		if(empty($subFilterResults))
		{
			return false;
		}

		if(count($subFilterResults) > 1)
		{
			$logic = isset($params['LOGIC']) ? $params['LOGIC'] : 'AND';
			$subFilterSql = '('.implode(" {$logic} ", $subFilterResults).')';
		}
		else
		{
			$subFilterSql = $subFilterResults[0] ;
		}

		return $sqlWhere !== ''
			? "SELECT {$alias}.SL_ID AS ID FROM {$sqlFrom} WHERE {$sqlWhere} AND {$subFilterSql}"
			: "SELECT {$alias}.SL_ID AS ID FROM {$sqlFrom} AND {$subFilterSql}";
	}
	static public function BuildRelationFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_RELATION')
		{
			return false;
		}
		$selctSql = self::BuildRelationSelectSql($vals);
		return "{$field} IN ({$selctSql})";
	}
	static private function BuildSubscriptionSelectSql(&$params, $options = array())
	{
		global $DB, $DBType;
		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$startTime = isset($options['START_TIME']) ? $options['START_TIME'] : '';
		if($startTime !== '')
		{
			$startTime = $DB->CharToDateFunction($DB->ForSql($startTime), 'FULL');
		}
		$top = isset($options['TOP']) ? intval($options['TOP']) : 0;

		$allEntities = isset($params['ENTITY_TYPES']) ? $params['ENTITY_TYPES'] : null;
		if(!is_array($allEntities) || empty($allEntities))
		{
			$allEntities = CCrmLiveFeedEntity::GetAll();
		}
		$allLeafTypes = CCrmLiveFeedEntity::GetLeafs();

		$subscrTableName = CCrmSonetSubscription::TABLE_NAME;
		$relTableName = CCrmSonetRelation::TABLE_NAME;

		$rootTypes = array_diff($allEntities, $allLeafTypes);
		$rootTypeSql = !empty($rootTypes) ? CCrmLiveFeedEntity::GetForSqlString($rootTypes) : '';
		if($rootTypeSql === '')
		{
			$rootSql = '';
		}
		else
		{
			$rootSql =
				"SELECT L1.ID FROM b_sonet_log L1
					INNER JOIN {$subscrTableName} S1
						ON S1.USER_ID = {$userID}
						AND L1.ENTITY_TYPE = S1.SL_ENTITY_TYPE
						AND L1.ENTITY_ID = S1.ENTITY_ID
						AND L1.ENTITY_TYPE IN ({$rootTypeSql})";

			if($startTime !== '')
			{
				$rootSql .= " AND L1.LOG_UPDATE >= {$startTime}";
			}

			if($top > 0)
			{
				$rootSql .= ' ORDER BY L1.LOG_UPDATE DESC';
				CSqlUtil::PrepareSelectTop($rootSql, $top, $DBType);
			}
		}

		$leafTypes = array_intersect($allEntities, $allLeafTypes);
		$leafTypeSql = !empty($leafTypes) ? CCrmLiveFeedEntity::GetForSqlString($leafTypes) : '';

		if($leafTypeSql === '')
		{
			$leafSql = '';
		}
		else
		{
			$leafSql =
				"SELECT R1.SL_ID AS ID FROM {$relTableName} R1
					INNER JOIN {$subscrTableName} S1
						ON S1.USER_ID = {$userID}
						AND R1.SL_ENTITY_TYPE IN($leafTypeSql)
						AND R1.LVL = 1
						AND R1.SL_PARENT_ENTITY_TYPE = S1.SL_ENTITY_TYPE
						AND R1.PARENT_ENTITY_ID = S1.ENTITY_ID";

			if($startTime !== '')
			{
				$leafSql .= " AND R1.SL_LAST_UPDATED >= {$startTime}";
			}

			if($top > 0)
			{
				$leafSql .= ' ORDER BY R1.SL_LAST_UPDATED DESC';
				CSqlUtil::PrepareSelectTop($leafSql, $top, $DBType);
			}
		}

		return array(
			'ROOT_SQL' => $rootSql,
			'LEAF_SQL' => $leafSql
		);
	}
	static public function BuildUserSubscriptionFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_USER_SUBSCR')
		{
			return false;
		}

		$sqlData = self::BuildSubscriptionSelectSql($vals);
		$rootSql = isset($sqlData['ROOT_SQL']) ? $sqlData['ROOT_SQL'] : '';
		$leafSql = isset($sqlData['LEAF_SQL']) ? $sqlData['LEAF_SQL'] : '';

		if($rootSql !== '')
		{
			$rootSql = "{$field} IN ($rootSql)";
		}

		if($leafSql !== '')
		{
			$leafSql = "{$field} IN ($leafSql)";
		}

		if($rootSql !== '' && $leafSql !== '')
		{
			return "{$rootSql} OR {$leafSql}";
		}
		elseif($rootSql !== '')
		{
			return $rootSql;
		}
		elseif($leafSql !== '')
		{
			return $leafSql;
		}

		return false;
	}
	static private function BuilUserAuthorshipSelectSql(&$params, $options = array())
	{
		global $DB, $DBType;
		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$allEntitySql = CCrmLiveFeedEntity::GetForSqlString(isset($params['ENTITY_TYPES']) ? $params['ENTITY_TYPES'] : null);

		if(!is_array($options))
		{
			$options = array();
		}

		$startTime = isset($options['START_TIME']) ? $options['START_TIME'] : '';
		if($startTime !== '')
		{
			$startTime = $DB->CharToDateFunction($DB->ForSql($startTime), 'FULL');
		}
		$top = isset($options['TOP']) ? intval($options['TOP']) : 0;

		$sql = "SELECT L1.ID FROM b_sonet_log L1 WHERE L1.USER_ID = {$userID} AND L1.ENTITY_TYPE IN ({$allEntitySql})";
		if($startTime !== '')
		{
			$sql .= " AND L1.LOG_UPDATE >= {$startTime}";
		}

		if($top > 0)
		{
			$sql .= ' ORDER BY L1.LOG_UPDATE DESC';
			CSqlUtil::PrepareSelectTop($sql, $top, $DBType);
		}
		return $sql;
	}
	static public function BuildUserAuthorshipFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_USER_AUTHOR')
		{
			return false;
		}

		$sql = self::BuilUserAuthorshipSelectSql($vals);
		return "{$field} IN ($sql)";
	}
	static private function BuilUserAddresseeSelectSql(&$params, $options = array())
	{
		global $DB, $DBType;
		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$startTime = isset($options['START_TIME']) ? $options['START_TIME'] : '';
		if($startTime !== '')
		{
			$startTime = $DB->CharToDateFunction($DB->ForSql($startTime), 'FULL');
		}
		$top = isset($options['TOP']) ? intval($options['TOP']) : 0;

		$allEntitySql = CCrmLiveFeedEntity::GetForSqlString(isset($params['ENTITY_TYPES']) ? $params['ENTITY_TYPES'] : null);
		$sql = "SELECT L1.ID FROM b_sonet_log L1
				INNER JOIN b_sonet_log_right LR1
					ON L1.ID = LR1.LOG_ID AND LR1.GROUP_CODE = 'U{$userID}'
					AND L1.ENTITY_TYPE IN ({$allEntitySql})";

		if($startTime !== '')
		{
			$sql .= " AND L1.LOG_UPDATE >= {$startTime}";
		}

		if($top > 0)
		{
			$sql .= ' ORDER BY L1.LOG_UPDATE DESC';
			CSqlUtil::PrepareSelectTop($sql, $top, $DBType);
		}
		return $sql;
	}
	static public function BuildUserAddresseeFilterSql($vals, $key, $operation, $isNegative, $field, $fields, $filter)
	{
		if($key !== 'CRM_USER_ADDRESSEE')
		{
			return false;
		}

		$sql = self::BuilUserAddresseeSelectSql($vals);
		return "{$field} IN ({$sql})";
	}
	static public function OnFillSocNetLogFields(&$fields)
	{
		if(!isset($fields['CRM_RELATION']))
		{
			$fields['CRM_RELATION'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildRelationFilterSql'));
		}

		if(!isset($fields['CRM_USER_SUBSCR']))
		{
			$fields['CRM_USER_SUBSCR'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildUserSubscriptionFilterSql'));
		}

		if(!isset($fields['CRM_USER_AUTHOR']))
		{
			$fields['CRM_USER_AUTHOR'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildUserAuthorshipFilterSql'));
		}

		if(!isset($fields['CRM_USER_ADDRESSEE']))
		{
			$fields['CRM_USER_ADDRESSEE'] = array('FIELD' => 'L.ID', 'WHERE' => array('CCrmLiveFeed', 'BuildUserAddresseeFilterSql'));
		}
	}
	static public function OnBuildSocNetLogPerms(&$perms, $params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$aliasPrefix = isset($params['ALIAS_PREFIX']) ? $params['ALIAS_PREFIX'] : 'L';
		$permType = isset($params['PERM_TYPE']) ? $params['PERM_TYPE'] : 'READ';
		$options = isset($params['OPTIONS']) ? $params['OPTIONS'] : null;
		if(!is_array($options))
		{
			$options = array();
		}

		//The parameter 'IDENTITY_COLUMN' is required for CCrmPerms::BuildSql
		if(!(isset($options['IDENTITY_COLUMN'])
			&& is_string($options['IDENTITY_COLUMN'])
			&& $options['IDENTITY_COLUMN'] !== ''))
		{
			$options['IDENTITY_COLUMN'] = 'ENTITY_ID';
		}

		$filterParams = isset($params['FILTER_PARAMS']) ? $params['FILTER_PARAMS'] : null;
		if(!is_array($filterParams))
		{
			$filterParams = array();
		}

		//$entityType = isset($filterParams['ENTITY_TYPE']) ? $filterParams['ENTITY_TYPE'] : '';
		//$entityID = isset($filterParams['ENTITY_ID']) ? intval($filterParams['ENTITY_ID']) : 0;

		$affectedEntityTypes = isset($filterParams['AFFECTED_TYPES']) && is_array($filterParams['AFFECTED_TYPES'])
			? $filterParams['AFFECTED_TYPES'] : array();

		$result = array();
		if(empty($affectedEntityTypes))
		{
			//By default preparing SQL for all CRM types
			$activityPerms = array();

			$result[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$result[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$result[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$result[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSql(CCrmOwnerType::DealName, $aliasPrefix, $permType, $options);
			$activityPerms[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSql(CCrmOwnerType::DealName, 'R', $permType, array('IDENTITY_COLUMN' => 'PARENT_ENTITY_ID'));

			$result[CCrmLiveFeedEntity::Invoice] = CCrmPerms::BuildSql(CCrmOwnerType::InvoiceName, $aliasPrefix, $permType, $options);

			$isRestricted = false;
			$activityFeedEnityType = CCrmLiveFeedEntity::Activity;
			$relationTableName = CCrmSonetRelation::TABLE_NAME;
			foreach($activityPerms as $type => $sql)
			{
				if($sql === '')
				{
					$activityPerms[$type] = "SELECT R.ENTITY_ID FROM {$relationTableName} R WHERE R.SL_ENTITY_TYPE = '{$activityFeedEnityType}' AND R.SL_PARENT_ENTITY_TYPE = '{$type}'";
					continue;
				}

				if(!$isRestricted)
				{
					$isRestricted = true;
				}

				if($sql === false)
				{
					unset ($activityPerms[$type]);
					continue;
				}
				$activityPerms[$type] = "SELECT R.ENTITY_ID FROM {$relationTableName} R WHERE R.SL_ENTITY_TYPE = '{$activityFeedEnityType}' AND R.SL_PARENT_ENTITY_TYPE = '{$type}' AND {$sql}";
			}

			if(!$isRestricted)
			{
				$result[CCrmLiveFeedEntity::Activity] = '';
			}
			elseif(!empty($activityPerms))
			{
				$result[CCrmLiveFeedEntity::Activity] = $aliasPrefix.'.'.$options['IDENTITY_COLUMN'].' IN ('.(implode(' UNION ALL ', $activityPerms)).')';
			}
		}
		else
		{
			if(in_array(CCrmLiveFeedEntity::Activity, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Activity] = '';
			}

			if(in_array(CCrmLiveFeedEntity::Lead, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Lead] = CCrmPerms::BuildSql(CCrmOwnerType::LeadName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Contact, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Contact] = CCrmPerms::BuildSql(CCrmOwnerType::ContactName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Company, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Company] = CCrmPerms::BuildSql(CCrmOwnerType::CompanyName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Deal, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Deal] = CCrmPerms::BuildSql(CCrmOwnerType::DealName, $aliasPrefix, $permType, $options);
			}

			if(in_array(CCrmLiveFeedEntity::Invoice, $affectedEntityTypes, true))
			{
				$result[CCrmLiveFeedEntity::Invoice] = CCrmPerms::BuildSql(CCrmOwnerType::InvoiceName, $aliasPrefix, $permType, $options);
			}
		}

		$resultSql = '';
		$isRestricted = false;

		if(!empty($result))
		{
			$entityTypeCol = 'ENTITY_TYPE';
			if(isset($options['ENTITY_TYPE_COLUMN'])
				&& is_string($options['ENTITY_TYPE_COLUMN'])
				&& $options['ENTITY_TYPE_COLUMN'] !== '')
			{
				$entityTypeCol = $options['ENTITY_TYPE_COLUMN'];
			}

			foreach($result as $type => &$sql)
			{
				if($sql === false)
				{
					//Access denied
					//$resultSql .= "({$aliasPrefix}.{$entityTypeCol} = '{$type}' AND 1<>1)";
					if(!$isRestricted)
					{
						$isRestricted = true;
					}
				}
				elseif(is_string($sql) && $sql !== '')
				{
					if($resultSql !== '')
					{
						$resultSql .= ' OR ';
					}
					$resultSql .= "({$aliasPrefix}.{$entityTypeCol} = '{$type}' AND {$sql})";
					if(!$isRestricted)
					{
						$isRestricted = true;
					}
				}
				else
				{
					if($resultSql !== '')
					{
						$resultSql .= ' OR ';
					}
					//All entities are allowed
					$resultSql .= "{$aliasPrefix}.{$entityTypeCol} = '{$type}'";
				}
			}
			unset($sql);
		}

		if($isRestricted)
		{
			if($resultSql !== '')
			{
				$perms[] = "({$resultSql})";
			}
			else
			{
				//Access denied
				$perms[] = false;
			}
		}
	}
	
	static public function OnBuildSocNetLogSql(&$arFields, &$arOrder, &$arFilter, &$arGroupBy, &$arSelectFields, &$arSqls)
	{
		if(!isset($arFilter['__CRM_JOINS']))
		{
			return;
		}

		$joins = $arFilter['__CRM_JOINS'];
		foreach($joins as &$join)
		{
			$sql = isset($join['SQL']) ? $join['SQL'] : '';
			if($sql !== '')
			{
				$arSqls['FROM'] .= ' '.$sql;
			}
		}
		unset($join);
	}

	static public function OnBuildSocNetLogFilter(&$filter, &$params, &$componentParams)
	{
		if(isset($filter['<=LOG_DATE']) && $filter['<=LOG_DATE'] === 'NOW')
		{
			//HACK: Clear filter by current time - is absolutely useless in CRM context and prevent db-engine from caching of query.
			unset($filter['<=LOG_DATE']);
		}

		if(isset($filter['SITE_ID']) && \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
		{
			//HACK: Clear filter by SITE_ID in bitrix24 context.
			unset($filter['SITE_ID']);
		}

		if(!is_array($params))
		{
			$params = array();
		}

		if(!(isset($params['AFFECTED_TYPES']) && is_array($params['AFFECTED_TYPES'])))
		{
			$params['AFFECTED_TYPES'] = array();
		}

		$entityType = isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;
		$options = isset($params['OPTIONS']) ? $params['OPTIONS'] : null;
		if(!is_array($options))
		{
			$options = array();
		}

		$customData = isset($options['CUSTOM_DATA']) ? $options['CUSTOM_DATA'] : null;
		if(!is_array($customData))
		{
			$customData = array();
		}

		$presetTopID = isset($customData['CRM_PRESET_TOP_ID']) ? $customData['CRM_PRESET_TOP_ID'] : '';
		$presetID = isset($customData['CRM_PRESET_ID']) ? $customData['CRM_PRESET_ID'] : '';

		$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($entityType);
		if($entityTypeID === CCrmOwnerType::Undefined)
		{
			$isActivityPresetEnabled = $presetID === 'activities';
			$isMessagePresetEnabled = $presetID === 'messages';
			$affectedEntityTypes = array();

			if($isActivityPresetEnabled)
			{
				$filter['ENTITY_TYPE'] = CCrmLiveFeedEntity::Activity;
				$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity);
				$affectedEntityTypes[] = CCrmLiveFeedEntity::Activity;
			}
			else
			{
				if($isMessagePresetEnabled)
				{
					$filter['@EVENT_ID'] = array(
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Lead, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Company, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Message),
						CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Invoice, CCrmLiveFeedEvent::Message)
					);
				}
				else
				{
					//Prepare general crm entities log
					$filter['@ENTITY_TYPE'] = array(
						CCrmLiveFeedEntity::Lead,
						CCrmLiveFeedEntity::Contact,
						CCrmLiveFeedEntity::Company,
						CCrmLiveFeedEntity::Deal,
						CCrmLiveFeedEntity::Activity,
						CCrmLiveFeedEntity::Invoice
					);
				}
			}

			if(
				$presetTopID !== 'all'
				&& (!isset($filter["ID"]) || intval($filter["ID"]) <= 0)
			)
			{
				$joinData = array();

				$filterValue = array(
					'USER_ID' => CCrmSecurityHelper::GetCurrentUserID(),
					'ENTITY_TYPES' => $affectedEntityTypes // optimization
				);

				$sqlOptions = array('TOP' => 20000);
				$startTime = isset($filter['>=LOG_UPDATE']) ? $filter['>=LOG_UPDATE'] : '';
				if($startTime !== '')
				{
					$sqlOptions['START_TIME'] = $startTime;
				}

				$subscrSqlData = self::BuildSubscriptionSelectSql($filterValue, $sqlOptions);
				$subscrRootSql = isset($subscrSqlData['ROOT_SQL']) ? $subscrSqlData['ROOT_SQL'] : '';
				$subscrLeafSql = isset($subscrSqlData['LEAF_SQL']) ? $subscrSqlData['LEAF_SQL'] : '';

				if($subscrRootSql !== '')
				{
					$joinData[] = "({$subscrRootSql})";
				}
				if($subscrLeafSql !== '')
				{
					$joinData[] = "({$subscrLeafSql})";
				}

				$userAuthorshipSql = self::BuilUserAuthorshipSelectSql($filterValue, $sqlOptions);
				if($userAuthorshipSql !== '')
				{
					$joinData[] = "({$userAuthorshipSql})";
				}

				$userAddresseeSql = self::BuilUserAddresseeSelectSql($filterValue, $sqlOptions);
				if($userAddresseeSql !== '')
				{
					$joinData[] = "({$userAddresseeSql})";
				}

				if(!empty($joinData))
				{
					if(isset($filter['__CRM_JOINS']))
					{
						$filter['__CRM_JOINS'] = array();
					}

					$joinSql = implode(' UNION ', $joinData);
					$filter['__CRM_JOINS'][] = array(
						'TYPE' => 'INNER',
						'SQL' =>"INNER JOIN ({$joinSql}) T ON T.ID = L.ID"
					);
					AddEventHandler('socialnetwork',  'OnBuildSocNetLogSql', array(__class__, 'OnBuildSocNetLogSql'));
					if(isset($filter['>=LOG_UPDATE']))
					{
						unset($filter['>=LOG_UPDATE']);
					}
				}

				/*$filter['__INNER_FILTER_CRM'] = array(
					'__INNER_FILTER_CRM_SUBSCRIPTION' =>
						array(
							'LOGIC' => 'OR',
							'CRM_USER_SUBSCR' => array($filterValue),
							'CRM_USER_AUTHOR' => array($filterValue),
							'CRM_USER_ADDRESSEE' => array($filterValue)
						)
				);*/
			}
			else
			{
				$componentParams["SHOW_UNREAD"] = "N";
			}

			return;
		}

		if($entityID <= 0)
		{
			//Invalid arguments - entityType is specified, but entityID is not.
			return;
		}

		$isExtendedMode = $presetTopID === 'extended';
		$isActivityPresetEnabled = $presetID === 'activities';
		$isMessagePresetEnabled = $presetID === 'messages';
		$isPresetDisabled = !$isActivityPresetEnabled && !$isMessagePresetEnabled;

		$mainFilter = array();
		$level1Filter = array(
			'PARENT' => array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID),
				'ENTITY_ID' => $entityID
			),
			'LOGIC' => 'OR',
			'SUB_FILTERS' => array()
		);
		$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$level2Filter = null;

		//ACTIVITIES & MESSAGES -->
		if($isPresetDisabled || $isActivityPresetEnabled)
		{
			$level1Filter['SUB_FILTERS'][] = array('ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity));
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity);
		}

		if(!$isActivityPresetEnabled)
		{
			$mainFilter['LOGIC'] = 'OR';
			$mainFilter['__INNER_FILTER_CRM_ENTITY'] = array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => $entityID
			);

			if($isMessagePresetEnabled)
			{
				$mainFilter['__INNER_FILTER_CRM_ENTITY']['EVENT_ID'] = array(CCrmLiveFeedEvent::GetEventID($entityType, CCrmLiveFeedEvent::Message));
			}

			//MESSAGES -->
			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Lead, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead);

			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead);

			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Company),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Company, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Lead);

			$level1Filter['SUB_FILTERS'][] = array(
				'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal),
				'EVENT_ID' => CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Message)
			);
			$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal);
			//<-- MESSAGES
		}
		//<-- ACTIVITIES & MESSAGES

		switch($entityTypeID)
		{
			case CCrmOwnerType::Contact:
			{
				//DEALS -->
				$dealEvents = array();
				if($isPresetDisabled)
				{
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Add);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Progress);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Client);
				}

				if(!empty($dealEvents))
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal),
						'EVENT_ID' => $dealEvents
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal);
				}
				//<-- DEALS

				//INVOICES -->
				if($isPresetDisabled)
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice)
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice);
				}
				//<-- INVOICES
			}
			break;
			case CCrmOwnerType::Company:
			{
				//CONTACTS -->
				$contactEvents = array();
				if($isPresetDisabled)
				{
					$contactEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Add);
				}

				if($isExtendedMode && $isPresetDisabled)
				{
					$contactEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Contact, CCrmLiveFeedEvent::Owner);
				}

				if(!empty($contactEvents))
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact),
						'EVENT_ID' => $contactEvents
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact);
				}
				//<-- CONTACTS

				//DEALS -->
				$dealEvents = array();
				if($isPresetDisabled)
				{
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Add);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Progress);
					$dealEvents[] = CCrmLiveFeedEvent::GetEventID(CCrmLiveFeedEntity::Deal, CCrmLiveFeedEvent::Client);
				}

				if(!empty($dealEvents))
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal),
						'EVENT_ID' => $dealEvents
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Deal);
				}
				//<-- DEALS

				//INVOICES -->
				if($isPresetDisabled)
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice)
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice);
				}
				//<-- INVOICES

				//CONTACT ACTIVITIES -->
				if($isExtendedMode && ($isPresetDisabled || $isActivityPresetEnabled))
				{
					$level2Filter = array(
						'PARENT' => array(
							'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Contact),
							'PARENT' => array(
								'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Company),
								'ENTITY_ID' => $entityID
							)
						),
						'SUB_FILTERS' => array(
							array(
								'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Activity)
							)
						)
					);
				}
				//<-- CONTACT ACTIVITIES
			}
			break;
			case CCrmOwnerType::Deal:
				//INVOICES -->
				if($isPresetDisabled)
				{
					$level1Filter['SUB_FILTERS'][] = array(
						'ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice)
					);
					$params['AFFECTED_TYPES'][] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::Invoice);
				}
				//<-- INVOICES
			break;
		}

		$relationFilters = array();
		if(!empty($level1Filter['SUB_FILTERS']))
		{
			$relationFilters[] = $level1Filter;
		}

		if(is_array($level2Filter))
		{
			$relationFilters[] = $level2Filter;
		}

		/*if(!empty($relationFilters))
		{
			$mainFilter['__INNER_FILTER_CRM_RELATION'] = array('CRM_RELATION' => $relationFilters);
		}
		$filter['__INNER_FILTER_CRM'] = $mainFilter;*/

		$joinData = array();
		if(!empty($mainFilter))
		{
			if(isset($mainFilter['__INNER_FILTER_CRM_ENTITY']))
			{
				$contextFilter = $mainFilter['__INNER_FILTER_CRM_ENTITY'];
				$entityTypeName =  $contextFilter['ENTITY_TYPE'];
				$entityID =  $contextFilter['ENTITY_ID'];
				$eventIDs =  isset($contextFilter['EVENT_ID']) ? $contextFilter['EVENT_ID'] : null;

				if($eventIDs !== null && !empty($eventIDs))
				{
					foreach($eventIDs as $k => $v)
					{
						$eventIDs[$k] = "'{$v}'";
					}

					$eventIDSql = implode(',', $eventIDs);
					$joinData[] = "(SELECT L1.ID FROM b_sonet_log L1 WHERE L1.ENTITY_TYPE = '{$entityTypeName}' AND L1.ENTITY_ID = {$entityID} AND L1.EVENT_ID IN({$eventIDSql}))";
				}
				else
				{
					$joinData[] = "(SELECT L1.ID FROM b_sonet_log L1 WHERE L1.ENTITY_TYPE = '{$entityTypeName}' AND L1.ENTITY_ID = {$entityID})";
				}
			}
		}

		if(!empty($relationFilters))
		{
			foreach($relationFilters as &$relationFilter)
			{
				$relationSql = self::BuildRelationSelectSql($relationFilter);
				if(is_string($relationSql) && $relationSql !== '')
				{
					$joinData[] = "({$relationSql})";
				}
			}
			unset($relationFilter);
		}

		if(!empty($joinData))
		{
			if(isset($filter['__CRM_JOINS']))
			{
				$filter['__CRM_JOINS'] = array();
			}

			$joinSql = implode(' UNION ', $joinData);
			$filter['__CRM_JOINS'][] = array(
				'TYPE' => 'INNER',
				'SQL' =>"INNER JOIN ({$joinSql}) T ON T.ID = L.ID"
			);
			AddEventHandler('socialnetwork',  'OnBuildSocNetLogSql', array(__class__, 'OnBuildSocNetLogSql'));
		}
	}
	static public function OnBuildSocNetLogOrder(&$arOrder, $arParams)
	{
		if (
			isset($arParams["CRM_ENTITY_TYPE"]) && strlen($arParams["CRM_ENTITY_TYPE"]) > 0
			&& isset($arParams["CRM_ENTITY_ID"]) && intval($arParams["CRM_ENTITY_ID"]) > 0
		)
		{
			$arOrder = array("LOG_DATE"	=> "DESC");
		}
	}
	static public function OnSocNetLogFormatDestination(&$arDestination, $right_tmp, $arRights, $arParams, $bCheckPermissions)
	{
		if (preg_match('/^('.CCrmLiveFeedEntity::Contact.'|'.CCrmLiveFeedEntity::Lead.'|'.CCrmLiveFeedEntity::Company.'|'.CCrmLiveFeedEntity::Deal.')(\d+)$/', $right_tmp, $matches))
		{
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->RegisterTag("crm_entity_name_".CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1])."_".$matches[2]);
			}

			$arDestination[] = array(
				"TYPE" => $matches[1],
				"ID" => $matches[2],
				"CRM_PREFIX" => GetMessage('CRM_LF_'.$matches[1].'_DESTINATION_PREFIX'),
				"URL" => (
					$arParams["MOBILE"] != 'Y' 
						? CCrmOwnerType::GetShowUrl(CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1]), $matches[2]) 
						: (
							isset($arParams["PATH_TO_".$matches[1]]) 
								? str_replace(
									array("#company_id#", "#contact_id#", "#lead_id#", "#deal_id#"), 
									$matches[2], 
									$arParams["PATH_TO_".$matches[1]]
								) 
								: '')
				),
				"TITLE" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1]), $matches[2], false))
			);
		}
	}
	static public function OnBeforeSocNetLogEntryGetRights($arEntryParams, &$arRights)
	{
		if (
			(
				!isset($arEntryParams["ENTITY_TYPE"])
				|| !isset($arEntryParams["ENTITY_ID"])
			)
			&& isset($arEntryParams["LOG_ID"])
			&& intval($arEntryParams["LOG_ID"]) > 0
		)
		{
			if ($arLog = CSocNetLog::GetByID($arEntryParams["LOG_ID"]))
			{
				$arEntryParams["ENTITY_TYPE"] = $arLog["ENTITY_TYPE"];
				$arEntryParams["ENTITY_ID"] = $arLog["ENTITY_ID"];
				$arEntryParams["EVENT_ID"] = $arLog["EVENT_ID"];
			}
		}

		if (
			!isset($arEntryParams["ENTITY_TYPE"])
			|| !in_array($arEntryParams["ENTITY_TYPE"], CCrmLiveFeedEntity::GetAll())
			|| !isset($arEntryParams["ENTITY_ID"])
		)
		{
			return true;
		}

		if ($arEntryParams["ENTITY_TYPE"] == CCrmLiveFeedEntity::Activity)
		{
			if (!isset($arEntryParams["ACTIVITY"]))
			{
				$arActivity = CCrmActivity::GetByID($arEntryParams["ENTITY_ID"]);

				if (!$arActivity)
				{
					return true;
				}

				$arEntryParams["ACTIVITY"] = $arActivity;
				$arEntryParams["ACTIVITY"]["COMMUNICATIONS"] = CCrmActivity::GetCommunications($arActivity["ID"]);
			}
			$arRights[] = CCrmLiveFeedEntity::GetByEntityTypeID($arEntryParams["ACTIVITY"]["OWNER_TYPE_ID"]).$arEntryParams["ACTIVITY"]["OWNER_ID"];
			$ownerEntityCode = $arEntryParams["ACTIVITY"]["OWNER_TYPE_ID"]."_".$arEntryParams["ACTIVITY"]["OWNER_ID"];

			if (!empty($arEntryParams["ACTIVITY"]["COMMUNICATIONS"]))
			{
				foreach ($arEntryParams["ACTIVITY"]["COMMUNICATIONS"] as $arActivityCommunication)
				{
					if ($arActivityCommunication["ENTITY_TYPE_ID"]."_".$arActivityCommunication["ENTITY_ID"] == $ownerEntityCode)
					{
						$arRights[] = CCrmLiveFeedEntity::GetByEntityTypeID($arActivityCommunication["ENTITY_TYPE_ID"]).$arActivityCommunication["ENTITY_ID"];
					}
				}
			}

			if (
				$arEntryParams["ACTIVITY"]["TYPE_ID"] == CCrmActivityType::Task
				&& intval($arEntryParams["ACTIVITY"]["ASSOCIATED_ENTITY_ID"]) > 0
				&& CModule::IncludeModule("tasks")
			)
			{
				$dbTask = CTasks::GetByID($arEntryParams["ACTIVITY"]["ASSOCIATED_ENTITY_ID"], false);
				if ($arTaskFields = $dbTask->Fetch())
				{
					$arTaskOwners =  isset($arTaskFields['UF_CRM_TASK']) ? $arTaskFields['UF_CRM_TASK'] : array();
					$arOwnerData = array();

					if(!is_array($arTaskOwners))
					{
						$arTaskOwners  = array($arTaskOwners);
					}

					$arFields['BINDINGS'] = array();

					if(CCrmActivity::TryResolveUserFieldOwners($arTaskOwners, $arOwnerData, CCrmUserType::GetTaskBindingField()))
					{
						foreach($arOwnerData as $arOwnerInfo)
						{
							$arRights[] = CCrmLiveFeedEntity::GetByEntityTypeID(CCrmOwnerType::ResolveID($arOwnerInfo['OWNER_TYPE_NAME'])).$arOwnerInfo['OWNER_ID'];
						}
					}
				}
			}
		}
		elseif ($arEntryParams["ENTITY_TYPE"] == CCrmLiveFeedEntity::Invoice)
		{
			if (!isset($arEntryParams["INVOICE"]))
			{
				$arInvoice = CCrmInvoice::GetByID($arEntryParams["ENTITY_ID"]);
				if (!$arInvoice)
				{
					return true;
				}

				$arEntryParams["INVOICE"] = $arInvoice;
			}

			if (intval($arEntryParams["INVOICE"]["UF_CONTACT_ID"]) > 0)
			{
				$arRights[] = CCrmLiveFeedEntity::Contact.$arEntryParams["INVOICE"]["UF_CONTACT_ID"];
			}

			if (intval($arEntryParams["INVOICE"]["UF_COMPANY_ID"]) > 0)
			{
				$arRights[] = CCrmLiveFeedEntity::Company.$arEntryParams["INVOICE"]["UF_COMPANY_ID"];
			}

			if (intval($arEntryParams["INVOICE"]["UF_DEAL_ID"]) > 0)
			{
				$arRights[] = CCrmLiveFeedEntity::Deal.$arEntryParams["INVOICE"]["UF_DEAL_ID"];
			}			
		}
		else
		{
			$arRights[] = $arEntryParams["ENTITY_TYPE"].$arEntryParams["ENTITY_ID"];
			if (in_array($arEntryParams["EVENT_ID"], array("crm_lead_message", "crm_deal_message", "crm_contact_message", "crm_company_message")))
			{
				$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arEntryParams["LOG_ID"]));
				while ($arRight = $dbRight->Fetch())
				{
					$arRights[] = $arRight["GROUP_CODE"];
				}
			}
		}

		return false;
	}
	static public function TryParseGroupCode($groupCode, &$data)
	{
		$m;
		if(preg_match('/^([A-Z]+)([0-9]+)$/i', $groupCode, $m) !== 1)
		{
			return false;
		}

		$data['ENTITY_TYPE'] = isset($m[1]) ? $m[1] : '';
		$data['ENTITY_ID'] = isset($m[2]) ? intval($m[2]) : 0;
		return true;
	}
	static public function OnBeforeSocNetLogRightsAdd($logID, $groupCode)
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return;
		}

		$logID = intval($logID);
		$groupCode = strval($groupCode);
		if($logID <= 0 || $groupCode === '')
		{
			return;
		}

		$dbResult = CSocNetLog::GetList(
			array(),
			array('ID' => $logID),
			false,
			false,
			array('ID', 'ENTITY_TYPE', 'ENTITY_ID', 'EVENT_ID')
		);

		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return;
		}

		$logEntityType = isset($fields['ENTITY_TYPE']) ? $fields['ENTITY_TYPE'] : '';
		$logEntityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		$logEventID = isset($fields['EVENT_ID']) ? $fields['EVENT_ID'] : '';

		if(!CCrmLiveFeedEntity::IsDefined($logEntityType))
		{
			return;
		}

		$relations = array();

		$groupCodeData = array();
		if(!self::TryParseGroupCode($groupCode, $groupCodeData))
		{
			return;
		}

		$entityType = $groupCodeData['ENTITY_TYPE'];
		$entityID = $groupCodeData['ENTITY_ID'];
		if(!CCrmLiveFeedEntity::IsDefined($entityType)
			|| $entityID <= 0
			|| ($entityType === $logEntityType
			&& $entityID === $logEntityID))
		{
			return;
		}

		$relations[] = array(
			'ENTITY_TYPE_ID' => CCrmLiveFeedEntity::ResolveEntityTypeID($entityType),
			'ENTITY_ID' => $entityID
		);

		CCrmSonetRelation::RegisterRelation(
			$logID,
			$logEventID,
			CCrmLiveFeedEntity::ResolveEntityTypeID($entityType),
			$logEntityID,
			CCrmLiveFeedEntity::ResolveEntityTypeID($entityType),
			$entityID,
			CCrmSonetRelationType::Correspondence
		);
	}
	static public function OnBeforeSocNetLogCommentCounterIncrement($arLogFields)
	{
		if (
			is_array($arLogFields)
			&& array_key_exists("ID", $arLogFields)
			&& array_key_exists("EVENT_ID", $arLogFields)
			&&
			(
				strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::LeadPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ContactPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::CompanyPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::DealPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ActivityPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::InvoicePrefix, 0) === 0
			)
		)
		{
			CCrmLiveFeed::CounterIncrement($arLogFields);
			return false;
		}
		else
		{
			return true;
		}
	}
	static public function OnAfterSocNetLogEntryCommentAdd($arLogFields, $arParams = array())
	{
		if (
			is_array($arLogFields)
			&& array_key_exists("ID", $arLogFields)
			&& array_key_exists("EVENT_ID", $arLogFields)
			&& array_key_exists("USER_ID", $arLogFields)
			&& CCrmSecurityHelper::GetCurrentUserID()
			&& CCrmSecurityHelper::GetCurrentUserID() != $arLogFields["USER_ID"]
			&&
			(
				strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::LeadPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ContactPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::CompanyPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::DealPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::ActivityPrefix, 0) === 0
				|| strpos($arLogFields["EVENT_ID"], CCrmLiveFeedEvent::InvoicePrefix, 0) === 0
			)
			&& CModule::IncludeModule("im")
		)
		{
			$genderSuffix = "";
			$dbUser = CUser::GetByID(CCrmSecurityHelper::GetCurrentUserID());
			if($arUser = $dbUser->Fetch())
			{
				$genderSuffix = $arUser["PERSONAL_GENDER"];
			}

			$title = self::GetNotifyEntryTitle($arLogFields, "COMMENT");
			if (strlen($title) > 0)
			{
				if (
					!isset($arParams["PATH_TO_LOG_ENTRY"])
					|| strlen($arParams["PATH_TO_LOG_ENTRY"]) <= 0
				)
				{
					$arParams["PATH_TO_LOG_ENTRY"] = '/crm/stream/?log_id=#log_id#';
				}

				$url = str_replace(array("#log_id#"), array($arLogFields["ID"]), $arParams["PATH_TO_LOG_ENTRY"]);
				$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

				$arMessageFields = array(
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"TO_USER_ID" => $arLogFields["USER_ID"],
					"FROM_USER_ID" => CCrmSecurityHelper::GetCurrentUserID(),
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "crm",
					"LOG_ID" => $arLogFields["ID"],
					"NOTIFY_EVENT" => "comment",
					"NOTIFY_TAG" => "CRM|LOG_COMMENT|".$arLogFields["ID"],
					"NOTIFY_MESSAGE" => GetMessage("CRM_LF_COMMENT_IM_NOTIFY_".$genderSuffix, Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
					"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LF_COMMENT_IM_NOTIFY_".$genderSuffix, Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
				);
				CIMNotify::Add($arMessageFields);
			}
		}
	}
	static public function GetNotifyEntryTitle($arLogFields, $type = "COMMENT")
	{
		switch ($arLogFields["EVENT_ID"])
		{
			case "crm_lead_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_LEAD_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLogFields["ENTITY_ID"], false)));
				break;
			case "crm_lead_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_LEAD_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50)),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLogFields["ENTITY_ID"], false)
				));
				break;
			case "crm_lead_progress":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_LEAD_PROGRESS", array(
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLogFields["ENTITY_ID"], false)
				));
				break;
			case "crm_company_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_COMPANY_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLogFields["ENTITY_ID"], false)));
				break;
			case "crm_company_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_COMPANY_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50)),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLogFields["ENTITY_ID"], false)
				));
				break;
			case "crm_contact_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_CONTACT_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLogFields["ENTITY_ID"], false)));
				break;
			case "crm_contact_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_CONTACT_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50)),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLogFields["ENTITY_ID"], false)
				));
				break;
			case "crm_deal_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_DEAL_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLogFields["ENTITY_ID"], false)));
				break;
			case "crm_deal_message":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_DEAL_MESSAGE", array(
					"#message_title#" => CCrmLiveFeedComponent::ParseText($arLogFields["MESSAGE"], array(), array("MAX_LENGTH" => 50)),
					"#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLogFields["ENTITY_ID"], false)
				));
				break;
			case "crm_deal_progress":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_DEAL_PROGRESS", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLogFields["ENTITY_ID"], false)));
				break;
			case "crm_invoice_add":
				return GetMessage("CRM_LF_IM_".$type."_TITLE_INVOICE_ADD", array("#title#" => CCrmOwnerType::GetCaption(CCrmOwnerType::Invoice, $arLogFields["ENTITY_ID"], false)));
				break;
			case "crm_activity_add":
				if ($arActivity = CCrmActivity::GetByID($arLogFields["ENTITY_ID"]))
				{
					switch ($arActivity["TYPE_ID"])
					{
						case CCrmActivityType::Meeting:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_MEETING_ADD", array("#title#" => $arActivity["SUBJECT"]));
							break;
						case CCrmActivityType::Call:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_CALL_ADD", array("#title#" => $arActivity["SUBJECT"]));
							break;
						case CCrmActivityType::Email:
							return GetMessage("CRM_LF_IM_".$type."_TITLE_ACTIVITY_EMAIL_ADD", array("#title#" => $arActivity["SUBJECT"]));
							break;
					}
				}
				break;
		}
		return "";
	}
	static public function OnAddRatingVote($rating_vote_id, $arRatingFields)
	{
		if (
			CModule::IncludeModule("socialnetwork") 
			&& CModule::IncludeModule("im")
		)
		{
			$arData = CSocNetLogTools::GetDataFromRatingEntity($arRatingFields["ENTITY_TYPE_ID"], $arRatingFields["ENTITY_ID"], false);
			if (
				is_array($arData)
				&& isset($arData["LOG_ID"])
				&& intval($arData["LOG_ID"]) > 0
			)
			{
				if (
					$arRatingFields["ENTITY_TYPE_ID"] != "LOG_COMMENT"
					&& ($arLog = CSocNetLog::GetByID($arData["LOG_ID"]))
					&& intval($arLog['USER_ID']) != intval($arRatingFields['USER_ID'])
					&& isset($arLog["ENTITY_TYPE"])
					&& in_array($arLog["ENTITY_TYPE"], CCrmLiveFeedEntity::GetAll())
				)
				{
					$title = self::GetNotifyEntryTitle($arLog, "LIKE");
					if (strlen($title) > 0)
					{
						if (
							!isset($arRatingFields["PATH_TO_LOG_ENTRY"])
							|| strlen($arRatingFields["PATH_TO_LOG_ENTRY"]) <= 0
						)
						{
							$arRatingFields["PATH_TO_LOG_ENTRY"] = '/crm/stream/?log_id=#log_id#';
						}

						$url = str_replace(array("#log_id#"), array($arLog["ID"]), $arRatingFields["PATH_TO_LOG_ENTRY"]);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						$arMessageFields = array(
							"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
							"TO_USER_ID" => intval($arLog['USER_ID']),
							"FROM_USER_ID" => intval($arRatingFields['USER_ID']),
							"NOTIFY_TYPE" => IM_NOTIFY_FROM,
							"NOTIFY_MODULE" => "main",
							"NOTIFY_EVENT" => "rating_vote",
							"NOTIFY_TAG" => "RATING|".($arRatingFields['VALUE'] >= 0?"":"DL|").$arRatingFields['ENTITY_TYPE_ID']."|".$arRatingFields['ENTITY_ID'],
							"NOTIFY_MESSAGE" => GetMessage("CRM_LF_LIKE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($title)."</a>")),
							"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LF_LIKE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($title)))." (".$serverName.$url.")"
						);
						CIMNotify::Add($arMessageFields);
					}
				}
			}
		}
	}
	static public function OnSocNetLogRightsDelete($logID)
	{
		CCrmSonetRelation::UnRegisterRelationsByLogEntityID($logID, CCrmSonetRelationType::Correspondence);
	}
	static public function OnBeforeSocNetLogDelete($logID)
	{
		CCrmSonetRelation::UnRegisterRelationsByLogEntityID($logID);
	}
	static public function CreateLogMessage(&$fields, $options = array())
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		global $APPLICATION, $DB;
		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$fields['ERROR'] = GetMessage('CRM_LF_MSG_ENTITY_TYPE_NOT_FOUND');
			return false;
		}

		$entityType = CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		if($entityID < 0)
		{
			$fields['ERROR'] = GetMessage('CRM_LF_MSG_ENTITY_TYPE_NOT_FOUND');
			return false;
		}

		$message = isset($fields['MESSAGE']) && is_string($fields['MESSAGE']) ? $fields['MESSAGE'] : '';
		if($message === '')
		{
			$fields['ERROR'] = GetMessage('CRM_LF_MSG_EMPTY');
			return false;
		}

		$title = isset($fields['TITLE']) && is_string($fields['TITLE']) ? $fields['TITLE'] : '';
		if($title === '')
		{
			$title = self::UntitledMessageStub;
		}

		$userID = isset($fields['USER_ID']) ? intval($fields['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$bbCodeParser = new CTextParser();
		$bbCodeParser->allow["HTML"] = "Y";
		$eventText = $bbCodeParser->convert4mail($message);

		$CCrmEvent = new CCrmEvent();
		$eventID = $CCrmEvent->Add(
			array(
				'ENTITY_TYPE'=> $entityType,
				'ENTITY_ID' => $entityID,
				'EVENT_ID' => 'INFO',
				'EVENT_TYPE' => 0, //USER
				'EVENT_TEXT_1' => $eventText,
				'DATE_CREATE' => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID),
				'FILES' => array()
			)
		);

		if(is_string($eventID))
		{
			//MS SQL RETURNS STRING INSTEAD INT
			$eventID = intval($eventID);
		}

		if(!(is_int($eventID) && $eventID > 0))
		{
			$fields['ERROR'] = 'Could not create event';
			return false;
		}

		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$eventID = CCrmLiveFeedEvent::GetEventID($liveFeedEntityType, CCrmLiveFeedEvent::Message);
		$eventFields = array(
			'EVENT_ID' => $eventID,
			'=LOG_DATE' => $DB->CurrentTimeFunction(),
			'TITLE' => $title,
			'MESSAGE' => $message,
			'TEXT_MESSAGE' => '',
			'MODULE_ID' => 'crm',
			'CALLBACK_FUNC' => false,
			'ENABLE_COMMENTS' => 'Y',
			'PARAMS' => '',
			'USER_ID' => $userID,
			'ENTITY_TYPE' => $liveFeedEntityType,
			'ENTITY_ID' => $entityID,
			'SOURCE_ID' => $eventID,
			'URL' => CCrmUrlUtil::AddUrlParams(
				CCrmOwnerType::GetShowUrl($entityTypeID, $entityID),
				array()
			),
		);

		if(isset($fields['WEB_DAV_FILES']) && is_array($fields['WEB_DAV_FILES']))
		{
			$eventFields = array_merge($eventFields, $fields['WEB_DAV_FILES']);
		}

		$sendMessage = isset($options['SEND_MESSAGE']) && is_bool($options['SEND_MESSAGE']) ? $options['SEND_MESSAGE'] : false;

		$logEventID = CSocNetLog::Add($eventFields, $sendMessage);
		if(is_int($logEventID) && $logEventID > 0)
		{
			$arSocnetRights = $fields["RIGHTS"];
			if (!empty($arSocnetRights))
			{
				$socnetPermsAdd = array();

				foreach($arSocnetRights as $perm_tmp)
				{
					if (preg_match('/^SG(\d+)$/', $perm_tmp, $matches))
					{
						if (!in_array("SG".$matches[1]."_".SONET_ROLES_USER, $arSocnetRights))
						{
							$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_USER;
						}

						if (!in_array("SG".$matches[1]."_".SONET_ROLES_MODERATOR, $arSocnetRights))
						{
							$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_MODERATOR;
						}

						if (!in_array("SG".$matches[1]."_".SONET_ROLES_OWNER, $arSocnetRights))
						{
							$socnetPermsAdd[] = "SG".$matches[1]."_".SONET_ROLES_OWNER;
						}
					}
				}
				if (count($socnetPermsAdd) > 0)
				{
					$arSocnetRights = array_merge($arSocnetRights, $socnetPermsAdd);
				}

				CSocNetLogRights::DeleteByLogID($logEventID);
				CSocNetLogRights::Add($logEventID, $arSocnetRights);

				if (
					array_key_exists("UF_SONET_LOG_DOC", $eventFields)
					&& is_array($eventFields["UF_SONET_LOG_DOC"])
					&& count($eventFields["UF_SONET_LOG_DOC"]) > 0
				)
				{
					if(!in_array("U".$userID, $arSocnetRights))
					{
						$arSocnetRights[] = "U".$userID;
					}
					CSocNetLogTools::SetUFRights($eventFields["UF_SONET_LOG_DOC"], $arSocnetRights);
				}
			}

			$arUpdateFields = array(
				"RATING_TYPE_ID" => "LOG_ENTRY",
				"RATING_ENTITY_ID" => $logEventID
			);
			CSocNetLog::Update($logEventID, $arUpdateFields);
			self::RegisterOwnershipRelations($logEventID, $eventID, $fields);

			$eventFields["LOG_ID"] = $logEventID;
			CCrmLiveFeed::CounterIncrement($eventFields);

			return $logEventID;
		}

		$ex = $APPLICATION->GetException();
		$fields['ERROR'] = $ex->GetString();
		return false;
	}

	static public function CreateLogEvent(&$fields, $eventType, $options = array())
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		global $APPLICATION, $DB;
		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$fields['ERROR'] = 'Entity type is not found';
			return false;
		}

		//$entityType = CCrmOwnerType::ResolveName($entityTypeID);
		$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		if($entityID < 0)
		{
			$fields['ERROR'] = 'Entity ID is not found';
			return false;
		}

		$message = isset($fields['MESSAGE']) && is_string($fields['MESSAGE']) ? $fields['MESSAGE'] : '';
		$title = isset($fields['TITLE']) && is_string($fields['TITLE']) ? $fields['TITLE'] : '';
		if($title === '')
		{
			$title = self::UntitledMessageStub;
		}

		$userID = isset($fields['USER_ID']) ? intval($fields['USER_ID']) : 0;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$sourceID = isset($fields['SOURCE_ID']) ? intval($fields['SOURCE_ID']) : 0;
		/*if(!(is_int($sourceID) && $sourceID > 0))
		{
			$fields['ERROR'] = 'Could not find event';
			return false;
		}*/
		$url = isset($fields['URL']) ? $fields['URL'] : '';
		$params = isset($fields['PARAMS']) ? $fields['PARAMS'] : null;
		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$eventID = CCrmLiveFeedEvent::GetEventID($liveFeedEntityType, $eventType);

		$eventFields = array(
			'EVENT_ID' => $eventID,
			'=LOG_DATE' => $DB->CurrentTimeFunction(),
			'TITLE' => $title,
			'MESSAGE' => $message,
			'TEXT_MESSAGE' => '',
			'MODULE_ID' => 'crm',
			'CALLBACK_FUNC' => false,
			'ENABLE_COMMENTS' => 'Y',
			'PARAMS' => is_array($params) && !empty($params) ? serialize($params) : '',
			'USER_ID' => $userID,
			'ENTITY_TYPE' => $liveFeedEntityType,
			'ENTITY_ID' => $entityID,
			'SOURCE_ID' => $sourceID,
			'URL' => $url,
			'UF_SONET_LOG_DOC' => (!empty($fields["UF_SONET_LOG_DOC"]) ? $fields["UF_SONET_LOG_DOC"] : false),
			'UF_SONET_LOG_FILE' => (!empty($fields["UF_SONET_LOG_DOC"]) ? $fields["UF_SONET_LOG_FILE"] : false)
		);
		$sendMessage = isset($options['SEND_MESSAGE']) && is_bool($options['SEND_MESSAGE']) ? $options['SEND_MESSAGE'] : false;

		$logEventID = CSocNetLog::Add($eventFields, $sendMessage);
		if(is_int($logEventID) && $logEventID > 0)
		{
			$arUpdateFields = array(
				'RATING_TYPE_ID' => 'LOG_ENTRY',
				'RATING_ENTITY_ID' => $logEventID
			);

			CSocNetLog::Update($logEventID, $arUpdateFields);
			self::RegisterOwnershipRelations($logEventID, $eventID, $fields);

			$eventFields["LOG_ID"] = $logEventID;
			CCrmLiveFeed::CounterIncrement($eventFields);

			return $logEventID;
		}

		$ex = $APPLICATION->GetException();
		$fields['ERROR'] = $ex->GetString();
		return false;
	}
	static public function GetLogEvents($sort, $filter, $select)
	{
		if(!(is_array($filter) && !empty($filter)))
		{
			return array();
		}

		if (!CModule::IncludeModule('socialnetwork'))
		{
			return array();
		}

		if(isset($filter['ENTITY_TYPE_ID']))
		{
			$filter['ENTITY_TYPE'] = CCrmLiveFeedEntity::GetByEntityTypeID($filter['ENTITY_TYPE_ID']);
			unset($filter['ENTITY_TYPE_ID']);
		}

		$dbResult = CSocNetLog::GetList(
			is_array($sort) ? $sort : array(),
			$filter,
			false,
			false,
			is_array($select) ? $select : array()
		);

		$result = array();
		if($dbResult)
		{
			while($ary = $dbResult->Fetch())
			{
				$result[] = &$ary;
				unset($ary);
			}
		}
		return $result;
	}
	static public function UpdateLogEvent($ID, $fields)
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		if(isset($fields['ENTITY_TYPE_ID']))
		{
			$fields['ENTITY_TYPE'] = CCrmLiveFeedEntity::GetByEntityTypeID($fields['ENTITY_TYPE_ID']);
			unset($fields['ENTITY_TYPE_ID']);
		}

		$refreshDate = isset($fields['LOG_UPDATE']) || isset($fields['=LOG_UPDATE']);
		$result = CSocNetLog::Update($ID, $fields);
		if($result !== false)
		{
			CCrmSonetRelation::SynchronizeRelationLastUpdateTime($ID);
		}
		return $result;
	}
	static public function DeleteLogEvent($ID, $options = array())
	{
		if (!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		$ID = intval($ID);
		if($ID <= 0)
		{
			return false;
		}

		CSocNetLog::Delete($ID);

		if(!is_array($options))
		{
			$options = array();
		}
		$unregisterRelation = !(isset($options['UNREGISTER_RELATION']) && $options['UNREGISTER_RELATION'] === false);
		if($unregisterRelation)
		{
			CCrmSonetRelation::UnRegisterRelationsByLogEntityID($ID);
		}
	}
	static public function DeleteLogEvents($params, $options = array())
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		if($liveFeedEntityType === CCrmLiveFeedEntity::Undefined)
		{
			return false;
		}

		$entityID = isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;
		if($entityID <= 0)
		{
			return false;
		}

		$dbRes = CSocNetLog::GetList(
			array('ID' => 'DESC'),
			array(
				'ENTITY_TYPE' => $liveFeedEntityType,
				'ENTITY_ID' => $entityID
			),
			false,
			false,
			array('ID')
		);
		while($arRes = $dbRes->Fetch())
		{
			CSocNetLog::Delete($arRes['ID']);
		}

		if(!is_array($options))
		{
			$options = array();
		}
		$unregisterRelation = !(isset($options['UNREGISTER_RELATION']) && $options['UNREGISTER_RELATION'] === false);
		if($unregisterRelation)
		{
			CCrmSonetRelation::UnRegisterRelationsByEntity($entityTypeID, $entityID);
		}
	}
	static public function Rebind($entityTypeID, $srcEntityID, $dstEntityID)
	{
		if(!CModule::IncludeModule('socialnetwork'))
		{
			return false;
		}

		$srcEntityID = (int)$srcEntityID;
		$dstEntityID = (int)$dstEntityID;
		$liveFeedEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$eventID = CCrmLiveFeedEvent::GetEventID($liveFeedEntityType, CCrmLiveFeedEvent::Message);

		$dbRes = CSocNetLog::GetList(
			array('ID' => 'DESC'),
			array(
				'EVENT_ID' => $eventID,
				'ENTITY_TYPE' => $liveFeedEntityType,
				'ENTITY_ID' => $srcEntityID
			),
			false,
			false,
			array('ID')
		);

		$IDs = array();
		while($arRes = $dbRes->Fetch())
		{
			$IDs[] = (int)$arRes['ID'];
		}

		foreach($IDs as $ID)
		{
			CSocNetLog::Update($ID, array('ENTITY_ID' => $dstEntityID));
		}
	}
	static private function RegisterOwnershipRelations($logEntityID, $logEventID, &$fields)
	{
		$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return;
		}

		$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
		if($entityID < 0)
		{
			return;
		}

		$parents = isset($fields['PARENTS']) && is_array($fields['PARENTS']) ? $fields['PARENTS'] : array();
		if(!empty($fields['PARENTS']))
		{
			$parentOptions = isset($fields['PARENT_OPTIONS']) && is_array($fields['PARENT_OPTIONS'])
				? $fields['PARENT_OPTIONS'] : array();

			$parentOptions['TYPE_ID'] = CCrmSonetRelationType::Ownership;
			CCrmSonetRelation::RegisterRelationBundle($logEntityID, $logEventID, $entityTypeID, $entityID, $parents, $parentOptions);
		}
		else
		{
			$parentEntityTypeID = isset($fields['PARENT_ENTITY_TYPE_ID']) ? intval($fields['PARENT_ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
			$parentEntityID = isset($fields['PARENT_ENTITY_ID']) ? intval($fields['PARENT_ENTITY_ID']) : 0;
			if(CCrmOwnerType::IsDefined($parentEntityTypeID) && $parentEntityID > 0)
			{
				CCrmSonetRelation::RegisterRelation($logEntityID, $logEventID, $entityTypeID, $entityID, $parentEntityTypeID, $parentEntityID, CCrmSonetRelationType::Ownership, 1);
			}
		}
	}
	static public function CounterIncrement($arLogFields)
	{
		CUserCounter::IncrementWithSelect(
			CCrmLiveFeed::GetSubSelect($arLogFields)
		);
	}
	static private function GetSubSelect($arLogFields, $bDecrement = false)
	{
		global $DB;


		$author_id = CCrmSecurityHelper::GetCurrentUserID();
		if($author_id <= 0 && isset($arLogFields["USER_ID"]))
		{
			$author_id = intval($arLogFields["USER_ID"]);
		}

		if($author_id <= 0)
		{
			return "";
		}

		$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($arLogFields["ENTITY_TYPE"]);
		$entityID = $arLogFields["ENTITY_ID"];

		$arEntities = array();

		if ($entityTypeID == CCrmOwnerType::Activity)
		{
			if ($arActivity = CCrmActivity::GetByID($entityID))
			{
				$entityTypeID = $arActivity["OWNER_TYPE_ID"];
				$entityID = $arActivity["OWNER_ID"];			
				$entityName = CCrmOwnerType::ResolveName($entityTypeID);
				$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
				$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

				if (
					intval($entityID) > 0
					&& $entityName
					&& intval($responsible_id) > 0
				)
				{
					if (!array_key_exists($entityName, $arEntities))
					{
						$arEntities[$entityName] = array();
					}

					$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
						"ENTITY_TYPE_ID" => $entityTypeID,
						"ENTITY_ID" => $entityID,
						"ENTITY_NAME" => $entityName,
						"IS_OPENED" => $bOpened,
						"RESPONSIBLE_ID" => $responsible_id
					);
				}

				$arCommunications = CCrmActivity::GetCommunications($arActivity["ID"]);
				foreach ($arCommunications as $arActivityCommunication)
				{
					$entityTypeID = $arActivityCommunication["ENTITY_TYPE_ID"];
					$entityID = $arActivityCommunication["ENTITY_ID"];			
					$entityName = CCrmOwnerType::ResolveName($entityTypeID);
					$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
					$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

					if (
						intval($entityID) > 0
						&& $entityName
						&& intval($responsible_id) > 0
					)
					{
						if (!array_key_exists($entityName, $arEntities))
						{
							$arEntities[$entityName] = array();
						}

						$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
							"ENTITY_TYPE_ID" => $entityTypeID,
							"ENTITY_ID" => $entityID,
							"ENTITY_NAME" => $entityName,
							"IS_OPENED" => $bOpened,
							"RESPONSIBLE_ID" => $responsible_id
						);
					}
				}				
			}
		}
		elseif ($entityTypeID == CCrmOwnerType::Invoice)
		{
			if ($arInvoice = CCrmInvoice::GetByID($entityID))
			{
				$arBindings = array(
					CCrmOwnerType::Contact => $arInvoice["UF_CONTACT_ID"],
					CCrmOwnerType::Company => $arInvoice["UF_COMPANY_ID"],
					CCrmOwnerType::Deal => $arInvoice["UF_DEAL_ID"]
				);

				foreach($arBindings as $entityTypeID => $entityID)
				{
					if (intval($entityID) > 0)
					{
						$entityName = CCrmOwnerType::ResolveName($entityTypeID);
						$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);
						$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

						if (
							$entityName
							&& intval($responsible_id) > 0
						)
						{
							if (!array_key_exists($entityName, $arEntities))
							{
								$arEntities[$entityName] = array();
							}

							$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
								"ENTITY_TYPE_ID" => $entityTypeID,
								"ENTITY_ID" => $entityID,
								"ENTITY_NAME" => $entityName,
								"IS_OPENED" => $bOpened,
								"RESPONSIBLE_ID" => $responsible_id
							);
						}
					}
				}
			}
		}
		else
		{
			$entityName = CCrmOwnerType::ResolveName($entityTypeID);
			$bOpened = CCrmOwnerType::isOpened($entityTypeID, $entityID, false);			
			$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

			if (
				intval($entityID) > 0
				&& $entityName
				&& intval($responsible_id) > 0
			)
			{
				if (!array_key_exists($entityName, $arEntities))
				{
					$arEntities[$entityName] = array();
				}

				$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
					"ENTITY_TYPE_ID" => $entityTypeID,
					"ENTITY_ID" => $entityID,
					"ENTITY_NAME" => $entityName,
					"IS_OPENED" => $bOpened,
					"RESPONSIBLE_ID" => $responsible_id
				);
			}
		}

		if (
			intval($arLogFields["LOG_ID"]) > 0
			&& in_array($arLogFields["EVENT_ID"], array("crm_lead_message", "crm_deal_message", "crm_contact_message", "crm_company_message"))
		)
		{
			$dbRight = CSocNetLogRights::GetList(array(), array("LOG_ID" => $arLogFields["LOG_ID"]));
			while ($arRight = $dbRight->Fetch())
			{
				if (preg_match('/^('.CCrmLiveFeedEntity::Contact.'|'.CCrmLiveFeedEntity::Lead.'|'.CCrmLiveFeedEntity::Company.'|'.CCrmLiveFeedEntity::Deal.')(\d+)$/', $arRight["GROUP_CODE"], $matches))
				{
					$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($matches[1]);
					$entityID = $matches[2];
					$entityName = CCrmOwnerType::ResolveName($entityTypeID);
					$responsible_id = CCrmOwnerType::GetResponsibleID($entityTypeID, $entityID, false);

					if (!array_key_exists($entityName, $arEntities))
					{
						$arEntities[$entityName] = array();
					}

					if (
						intval($entityID) > 0
						&& $entityName
						&& intval($responsible_id) > 0
						&& !array_key_exists($entityTypeID."_".$entityID, $arEntities[$entityName])
					)
					{
						$arEntities[$entityName][$entityTypeID."_".$entityID] = array(
							"ENTITY_TYPE_ID" => $entityTypeID,
							"ENTITY_ID" => $entityID,
							"ENTITY_NAME" => $entityName,
							"IS_OPENED" => CCrmOwnerType::isOpened($entityTypeID, $entityID, false),
							"RESPONSIBLE_ID" => $responsible_id
						);
					}
				}
			}
		}

		$arUserID = array();

		foreach ($arEntities as $entityName => $arTmp)
		{
			$sSql = "SELECT RL.RELATION, RP.ATTR 
				FROM b_crm_role_relation RL 
				INNER JOIN b_crm_role_perms RP ON RL.ROLE_ID = RP.ROLE_ID AND RP.ENTITY = '".$entityName."' AND RP.PERM_TYPE = 'READ'
			";

			$res = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while($row = $res->Fetch())
			{
				$user_id = false;

				switch ($row["ATTR"])
				{
					case BX_CRM_PERM_SELF:

						foreach ($arTmp as $arEntity)
						{
							$strSQL = "SELECT UA.USER_ID 
							FROM b_user_access UA 
							WHERE
								UA.USER_ID = ".intval($arEntity["RESPONSIBLE_ID"])."
								AND UA.ACCESS_CODE = '".$DB->ForSQL($row["RELATION"])."'";

							$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
							if (
								($arUser = $rsUser->Fetch())
								&& !in_array($arUser["USER_ID"], $arUserID)
								&& $arUser["USER_ID"] != $author_id
							)
							{
								$arUserID[] = $arUser["USER_ID"];
							}
						}

						break;
					case BX_CRM_PERM_ALL:
					case BX_CRM_PERM_CONFIG:

						$strSQL = "SELECT UA.USER_ID 
						FROM b_user_access UA 
						WHERE
							UA.ACCESS_CODE = '".$DB->ForSQL($row["RELATION"])."'";

						$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
						while ($arUser = $rsUser->Fetch())
						{
							if (
								!in_array($arUser["USER_ID"], $arUserID)
								&& $arUser["USER_ID"] != $author_id
							)
							{
								$arUserID[] = $arUser["USER_ID"];
							}
						}

						break;
					case BX_CRM_PERM_OPEN:

						foreach ($arTmp as $arEntity)
						{
							if ($arEntity["IS_OPENED"])
							{
								$strSQL = "SELECT UA.USER_ID 
								FROM b_user_access UA 
								WHERE
									UA.ACCESS_CODE = '".$DB->ForSQL($row["RELATION"])."'";

								$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
								while ($arUser = $rsUser->Fetch())
								{
									if (
										!in_array($arUser["USER_ID"], $arUserID)
										&& $arUser["USER_ID"] != $author_id
									)
									{
										$arUserID[] = $arUser["USER_ID"];
									}
								}
							}
						}

						break;
					case BX_CRM_PERM_DEPARTMENT:

						foreach ($arTmp as $arEntity)
						{
							$strSQL = "SELECT UA.USER_ID 
							FROM b_user_access UA 
							INNER JOIN b_user_access UA1 ON 
								UA1.USER_ID = ".intval($arEntity["RESPONSIBLE_ID"])."
								AND UA1.ACCESS_CODE LIKE 'D%'
								AND UA1.ACCESS_CODE NOT LIKE 'DR%'
								AND UA1.ACCESS_CODE = UA.ACCESS_CODE
							INNER JOIN b_user_access UA2 ON 
								UA2.USER_ID = UA.USER_ID
								AND UA2.ACCESS_CODE = '".$DB->ForSQL($row["RELATION"])."'";

							$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
							while ($arUser = $rsUser->Fetch())
							{
								if (
									!in_array($arUser["USER_ID"], $arUserID)
									&& $arUser["USER_ID"] != $author_id
								)
								{
									$arUserID[] = $arUser["USER_ID"];
								}
							}
						}

						break;
					case BX_CRM_PERM_SUBDEPARTMENT:

						foreach ($arTmp as $arEntity)
						{
							$strSQL = "SELECT UA.USER_ID 
							FROM b_user_access UA 
							INNER JOIN b_user_access UA1 ON 
								UA1.USER_ID = ".intval($arEntity["RESPONSIBLE_ID"])."
								AND UA1.ACCESS_CODE LIKE 'DR%'
								AND UA1.ACCESS_CODE = UA.ACCESS_CODE
							INNER JOIN b_user_access UA2 ON 
								UA2.USER_ID = UA.USER_ID
								AND UA2.ACCESS_CODE = '".$DB->ForSQL($row["RELATION"])."'";

							$rsUser = $DB->Query($strSQL, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
							while ($arUser = $rsUser->Fetch())
							{
								if (
									!in_array($arUser["USER_ID"], $arUserID)
									&& $arUser["USER_ID"] != $author_id
								)
								{
									$arUserID[] = $arUser["USER_ID"];
								}
							}
						}

						break;
				}
			}
		}

		$strSubscription = "";

		$cnt = 0;
		foreach ($arEntities as $entityName => $arTmp)
		{
			foreach ($arTmp as $arEntity)
			{
				if ($cnt > 0)
				{
					$strSubscription .= " OR ";
				}

				$strSubscription .= "
					EXISTS (
							SELECT S.USER_ID 
							FROM ".CCrmSonetSubscription::TABLE_NAME." S 
							WHERE 
								S.SL_ENTITY_TYPE = '".CCrmLiveFeedEntity::GetByEntityTypeID($arEntity["ENTITY_TYPE_ID"])."'
								AND S.ENTITY_ID = ".intval($arEntity["ENTITY_ID"])."
								AND U.ID = S.USER_ID
						) ";
				$cnt++;
			}
		}
	
		$strReturn = "SELECT 
			U.ID as ID
			,".($bDecrement ? "-1" : "1")." as CNT
			,'**' as SITE_ID
			,'CRM_**' as CODE,
			0 as SENT
		FROM b_user U 
		WHERE
			(
				U.ID IN (SELECT USER_ID FROM b_user_access WHERE ACCESS_CODE = 'G1' AND USER_ID <> ".$author_id.")
				".(!empty($arUserID) ? " OR U.ID IN (".implode(",", $arUserID).") " : "")."
			)".
			(
				(
					strlen($strSubscription) > 0 
					|| intval($arLogFields["LOG_ID"]) > 0
				)
					? "
					AND
					(
						".$strSubscription.
						(intval($arLogFields["LOG_ID"]) > 0
							?
								(strlen($strSubscription) > 0 ? " OR " : "")." 
								EXISTS (
									SELECT GROUP_CODE 
									FROM b_sonet_log_right LR
									WHERE 
										LR.LOG_ID = ".intval($arLogFields["LOG_ID"])." 
										AND LR.GROUP_CODE = ".$DB->Concat("'U'", ($DB->type == "MSSQL" ? "CAST(U.ID as varchar(17))" : "U.ID"))."
								) "
							: ""
						)."
					)
					"
					: ""
			);

		return $strReturn;
	}
	static public function CheckCreatePermission($entityType, $entityID, $userPermissions = null)
	{
		$canonicalEntityType = CCrmOwnerType::ResolveName(CCrmLiveFeedEntity::ResolveEntityTypeID($entityType));
		return CCrmAuthorizationHelper::CheckUpdatePermission($canonicalEntityType, $entityID, $userPermissions);
	}

	public static function OnSendMentionGetEntityFields($arCommentFields)
	{
		if (!in_array($arCommentFields["ENTITY_TYPE"], CCrmLiveFeedEntity::GetAll()))
		{
			return false;
		}
		
		if (!CModule::IncludeModule("socialnetwork"))
		{
			return true;
		}
		$dbLog = CSocNetLog::GetList(
			array(),
			array(
				"ID" => $arCommentFields["LOG_ID"],
			),
			false,
			false,
			array("ID", "ENTITY_ID", "EVENT_ID")
		);

		if ($arLog = $dbLog->Fetch())
		{
			$genderSuffix = "";
			$dbUser = CUser::GetByID($arCommentFields["USER_ID"]);
			if($arUser = $dbUser->Fetch())
			{
				$genderSuffix = $arUser["PERSONAL_GENDER"];
			}

			switch ($arLog["EVENT_ID"])
			{
				case "crm_company_add":
					$entityName = GetMessage("CRM_LF_COMPANY_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_COMPANY_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_contact_add":
					$entityName = GetMessage("CRM_LF_CONTACT_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_CONTACT_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_lead_add":
					$entityName = GetMessage("CRM_LF_LEAD_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_add":
					$entityName = GetMessage("CRM_LF_DEAL_ADD_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_company_responsible":
					$entityName = GetMessage("CRM_LF_COMPANY_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_COMPANY_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_contact_responsible":
					$entityName = GetMessage("CRM_LF_CONTACT_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_CONTACT_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_lead_responsible":
					$entityName = GetMessage("CRM_LF_LEAD_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_responsible":
					$entityName = GetMessage("CRM_LF_DEAL_RESPONSIBLE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_RESPONSIBLE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_company_message":
					$entityName = GetMessage("CRM_LF_COMPANY_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_COMPANY_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_contact_message":
					$entityName = GetMessage("CRM_LF_CONTACT_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_CONTACT_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_lead_message":
					$entityName = GetMessage("CRM_LF_LEAD_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Lead, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_LEAD_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_deal_message":
					$entityName = GetMessage("CRM_LF_DEAL_MESSAGE_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $arLog["ENTITY_ID"], false))));
					$notifyTag = "CRM_DEAL_MESSAGE|COMMENT_MENTION|".$arCommentFields["ID"];
					break;
				case "crm_activity_add":
					if ($arActivity = CCrmActivity::GetByID($arLog["ENTITY_ID"]))
					{
						switch ($arActivity["OWNER_TYPE_ID"])
						{
							case CCrmOwnerType::Company:
								$ownerType = "COMPANY";
								break;
							case CCrmOwnerType::Contact:
								$ownerType = "CONTACT";
								break;
							case CCrmOwnerType::Lead:
								$ownerType = "LEAD";
								break;
							case CCrmOwnerType::Deal:
								$ownerType = "DEAL";
								break;

						}
						
						switch ($arActivity["TYPE_ID"])
						{
							case CCrmActivityType::Meeting:
								$activityType = "MEETING";
								break;
							case CCrmActivityType::Call:
								$activityType = "CALL";
								break;
							case CCrmActivityType::Email:
								$activityType = "EMAIL";
								break;
						}

						if (
							$ownerType 
							&& $activityType
						)
						{
							$entityName = GetMessage("CRM_LF_ACTIVITY_".$activityType."_".$ownerType."_COMMENT_MENTION_TITLE", Array("#title#" => htmlspecialcharsbx(CCrmOwnerType::GetCaption($arActivity["OWNER_TYPE_ID"], $arActivity["OWNER_ID"], false))));
							$notifyTag = "CRM_ACTIVITY_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
						}
					}
					break;
				case "crm_invoice_add":
					if ($arInvoice = CCrmInvoice::GetByID($arLog["ENTITY_ID"]))
					{
						$entityName = GetMessage("CRM_LF_INVOICE_ADD_COMMENT_MENTION_TITLE", array("#id#" => $arInvoice["ID"]));
						$notifyTag = "CRM_INVOICE_ADD|COMMENT_MENTION|".$arCommentFields["ID"];
					}
					break;
			}

			if ($entityName)
			{
				$notifyMessage = GetMessage("CRM_LF_COMMENT_MENTION".(strlen($genderSuffix) > 0 ? "_".$genderSuffix : ""), Array("#title#" => "<a href=\"#url#\" class=\"bx-notifier-item-action\">".$entityName."</a>"));
				$notifyMessageOut = GetMessage("CRM_LF_COMMENT_MENTION".(strlen($genderSuffix) > 0 ? "_".$genderSuffix : ""), Array("#title#" => $entityName))." ("."#server_name##url#)";

				$strPathToLogCrmEntry = str_replace("#log_id#", $arLog["ID"], "/crm/stream/?log_id=#log_id#");
				$strPathToLogCrmEntryComment = $strPathToLogCrmEntry.(strpos($strPathToLogCrmEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"]."#com".$arCommentFields["ID"];

				if (in_array($arLog["EVENT_ID"], array("crm_company_message", "crm_contact_message", "crm_deal_message", "crm_lead_message")))
				{
					$strPathToLogEntry = str_replace("#log_id#", $arLog["ID"], COption::GetOptionString("socialnetwork", "log_entry_page", "/company/personal/log/#log_id#/", SITE_ID));
					$strPathToLogEntryComment = $strPathToLogEntry.(strpos($strPathToLogEntry, "?") !== false ? "&" : "?")."commentID=".$arCommentFields["ID"]."#com".$arCommentFields["ID"];
				}

				$arReturn = array(
					"IS_CRM" => "Y",
					"URL" => $strPathToLogEntryComment,
					"CRM_URL" => $strPathToLogCrmEntryComment,
					"NOTIFY_MODULE" => "crm",
					"NOTIFY_TAG" => $notifyTag,
					"NOTIFY_MESSAGE" => $notifyMessage,
					"NOTIFY_MESSAGE_OUT" => $notifyMessageOut
				);

				return $arReturn;
			}
			else
			{
				return false;
			}

		}
		else
		{
			return false;
		}
	}

	public static function GetShowUrl($logEventID)
	{
		return CComponentEngine::MakePathFromTemplate(
			'#SITE_DIR#crm/stream/?log_id=#log_id#',
			array('log_id' => $logEventID)
		);
	}

	public static function onAfterCommentAdd($entityType, $entityId, $arData)
	{
		global $USER;

		// 'TK' is our entity type
		if (
			$entityType !== 'TK'
			|| intval($entityId) <= 0
			|| !CModule::IncludeModule('tasks')
			|| !CModule::IncludeModule('socialnetwork')
		)
		{
			return;
		}

		$loggedInUserId = 1;
		if (is_object($USER) && method_exists($USER, 'getId'))
			$loggedInUserId = (int) $USER->getId();

		$taskId = (int) $entityId;
		$oTask = CTaskItem::getInstance($taskId, 1);
		$arTask = $oTask->getData();

		$topicId    = $arData['TOPIC_ID'];
		$messageId  = $arData['MESSAGE_ID'];
		$strMessage = $arData['PARAMS']['POST_MESSAGE'];

		$parser = new CTextParser();
		$message_notify = $parser->convert4mail($strMessage);

		$messageAuthorId = null;
		$messageEditDate = null;
		$messagePostDate = null;

		if (
			array_key_exists('AUTHOR_ID', $arData['PARAMS'])
			&& array_key_exists('EDIT_DATE', $arData['PARAMS'])
			&& array_key_exists('POST_DATE', $arData['PARAMS'])
		)
		{
			$messageAuthorId = $arData['PARAMS']['AUTHOR_ID'];
			$messageEditDate = $arData['PARAMS']['EDIT_DATE'];
			$messageEditDate = $arData['PARAMS']['POST_DATE'];
		}
		else
		{
			$arMessage = CForumMessage::GetByID($messageId);

			$messageAuthorId = $arMessage['AUTHOR_ID'];
			$messageEditDate = $arMessage['EDIT_DATE'];
			$messageEditDate = $arMessage['POST_DATE'];
		}

		$occurAsUserId = CTasksTools::getOccurAsUserId();
		if ( ! $occurAsUserId )
			$occurAsUserId = ($messageAuthorId ? $messageAuthorId : 1);

		$oTask = CTaskItem::getInstance($taskId, 1);
		$arTask = $oTask->getData();

		if (
			!isset($arTask)
			|| !isset($arTask['UF_CRM_TASK'])
			|| (
				is_array($arTask['UF_CRM_TASK'])
				&& (
					!isset($arTask['UF_CRM_TASK'][0])
					|| strlen($arTask['UF_CRM_TASK'][0]) <= 0
				)
			)
			|| (
				!is_array($arTask['UF_CRM_TASK'])
				&& (
					strlen($arTask['UF_CRM_TASK']) <= 0
				)
			)
		)
		{
			return;
		}

		$dbCrmActivity = CCrmActivity::GetList(
			array(), 
			array(
				'TYPE_ID' => CCrmActivityType::Task,
				'ASSOCIATED_ENTITY_ID' => $taskId,
				'CHECK_PERMISSIONS' => 'N'
			), 
			false, 
			false, 
			array('ID')
		);
		$arCrmActivity = $dbCrmActivity->Fetch();
		if (!$arCrmActivity)
		{
			return;
		}

		$crmActivityId = $arCrmActivity['ID'];

		// sonet log
		$dbLog = CSocNetLog::GetList(
			array(),
			array(
				"EVENT_ID" => "crm_activity_add",
				"ENTITY_ID" => $crmActivityId
			),
			false,
			false,
			array("ID", "ENTITY_TYPE", "ENTITY_ID")
		);
		if ($arLog = $dbLog->Fetch())
		{
			$log_id = $arLog["ID"];
			$entity_type = $arLog["ENTITY_TYPE"];
			$entity_id = $arLog["ENTITY_ID"];

			$strURL = $GLOBALS['APPLICATION']->GetCurPageParam("", array("IFRAME", "MID", "SEF_APPLICATION_CUR_PAGE_URL", BX_AJAX_PARAM_ID, "result"));
			$strURL = ForumAddPageParams(
				$strURL,
				array(
					"MID" => $messageId, 
					"result" => "reply"
				), 
				false, 
				false
			);
			$sText = (COption::GetOptionString("forum", "FILTER", "Y") == "Y" ? $arMessage["POST_MESSAGE_FILTER"] : $arMessage["POST_MESSAGE"]);

			$arFieldsForSocnet = array(
				"ENTITY_TYPE" => $entity_type,
				"ENTITY_ID" => $entity_id,
				"EVENT_ID" => "crm_activity_add_comment",
				"MESSAGE" => $sText,
				"TEXT_MESSAGE" => $parser->convert4mail($sText),
				"URL" => str_replace("?IFRAME=Y", "", str_replace("&IFRAME=Y", "", str_replace("IFRAME=Y&", "", $strURL))),
				"MODULE_ID" => "crm",
				"SOURCE_ID" => $messageId,
				"LOG_ID" => $log_id,
				"RATING_TYPE_ID" => "FORUM_POST",
				"RATING_ENTITY_ID" => $messageId
			);

			$arFieldsForSocnet["USER_ID"] = $occurAsUserId;
			$arFieldsForSocnet["=LOG_DATE"] = $GLOBALS['DB']->CurrentTimeFunction();

			$ufFileID = array();
			$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageId));
			while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
				$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

			if (count($ufFileID) > 0)
				$arFieldsForSocnet["UF_SONET_COM_FILE"] = $ufFileID;

			$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageId, LANGUAGE_ID);
			if ($ufDocID)
				$arFieldsForSocnet["UF_SONET_COM_DOC"] = $ufDocID;							

			$comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false);
			CSocNetLog::CounterIncrement($comment_id, false, false, "LC");
		}
	}

	public static function AddCrmActivityComment($arFields)
	{
		if (!CModule::IncludeModule("forum"))
		{
			return false;
		}

		$ufFileID = array();
		$ufDocID = array();
		$messageID = false;

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array('ID', 'ENTITY_ID', 'SOURCE_ID', 'SITE_ID', 'TITLE', 'PARAMS')
		);

		if ($arLog = $dbResult->Fetch())
		{
			$dbCrmActivity = CCrmActivity::GetList(
				array(),
				array(
					'ID' => $arLog['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N'
				)
			);

			if ($arCrmActivity = $dbCrmActivity->Fetch())
			{
				if (
					$arCrmActivity['TYPE_ID'] == CCrmActivityType::Task
					&& CModule::IncludeModule('tasks')
				)
				{
					$userID = $GLOBALS["USER"]->GetID();

					$dbTask = CTasks::GetByID($arCrmActivity["ASSOCIATED_ENTITY_ID"], false);
					if ($arTaskFields = $dbTask->Fetch())
					{
						if(!$userName = trim($GLOBALS["USER"]->GetFormattedName(false)))
						{
							$userName = $GLOBALS["USER"]->GetLogin();
						}

						$FORUM_ID = CTasksTools::GetForumIdForIntranet();

						if (!$arTaskFields["FORUM_TOPIC_ID"])
						{
							$arTopicFields = Array(
								"TITLE" => $arTaskFields["TITLE"],
								"USER_START_ID" => $arFields["USER_ID"],
								"STATE" => "Y",
								"FORUM_ID" => $FORUM_ID,
								"USER_START_NAME" => $userName,
								"START_DATE" => ConvertTimeStamp(time(), "FULL"),
								"POSTS" => 0,
								"VIEWS" => 0,
								"APPROVED" => "Y",
								"LAST_POSTER_NAME" => $userName,
								"LAST_POST_DATE" => ConvertTimeStamp(time(),"FULL"),
								"LAST_MESSAGE_ID" => 0,
								"XML_ID" => 'TASK_'.$arTaskFields['ID']
							);
							$TOPIC_ID = CForumTopic::Add($arTopicFields);
							if($TOPIC_ID)
							{
								$arFieldsFirstMessage = Array(
									"POST_MESSAGE" => $arTopicFields["XML_ID"],
									"AUTHOR_ID" => $arTopicFields["USER_START_ID"],
									"AUTHOR_NAME" => $arTopicFields["USER_START_NAME"],
									"FORUM_ID" => $arTopicFields["FORUM_ID"],
									"TOPIC_ID" => $TOPIC_ID,
									"APPROVED" => "Y",
									"NEW_TOPIC" => "Y",
									"PARAM1" => 'TK',
									"PARAM2" => $arTaskFields['ID'],
									"PERMISSION_EXTERNAL" => 'E',
									"PERMISSION" => 'E',
								);
								CForumMessage::Add($arFieldsFirstMessage, false, array("SKIP_INDEXING" => "Y", "SKIP_STATISTIC" => "N"));

								$oTask = new CTasks();
								$oTask->Update($arTaskFields["ID"], Array("FORUM_TOPIC_ID" => $TOPIC_ID));
							}
						}
						else
						{
							$TOPIC_ID = $arTaskFields["FORUM_TOPIC_ID"];
						}

						if ($TOPIC_ID)
						{
							$arFieldsP = array(
								"AUTHOR_ID" => $arFields["USER_ID"],
								"AUTHOR_NAME" => $userName,
								"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
								"FORUM_ID" => $FORUM_ID,
								"TOPIC_ID" => $TOPIC_ID,
								"APPROVED" => "Y"
							);

							$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
							if (is_array($arTmp))
							{
								if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
								{
									$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
								}
								elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
								{
									$arFieldsP["FILES"] = array();
									foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
										$arFieldsP["FILES"][] = array("FILE_ID" => $file_id);
								}
							}

							$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("FORUM_MESSAGE", $arFieldsP);

							$messageID = CForumMessage::Add($arFieldsP);

							// get UF DOC value and FILE_ID there
							if ($messageID > 0)
							{
								$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
								while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
									$ufFileID[] = $arAddedMessageFiles["FILE_ID"];

								$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
							}
						}
					}
				}
				else
				{
					return array(
						"NO_SOURCE" => "Y"
					);
				}
			}
			else
			{
				$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
			}
		}
		else
		{
			$sError = GetMessage("SONET_ADD_COMMENT_SOURCE_ERROR");
		}

		return array(
			"SOURCE_ID" => $messageID,
			"RATING_TYPE_ID" => "FORUM_POST",
			"RATING_ENTITY_ID" => $messageID,
			"ERROR" => $sError,
			"NOTES" => $sNote,
			"UF" => array(
				"FILE" => $ufFileID,
				"DOC" => $ufDocID
			)
		);
	}

	public static function UpdateCrmActivityComment($arFields)
	{
		if (
			!isset($arFields["SOURCE_ID"])
			|| intval($arFields["SOURCE_ID"]) <= 0
		)
		{
			return false;
		}

		$ufFileID = array();
		$ufDocID = array();

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array('ID', 'ENTITY_ID')
		);

		if ($arLog = $dbResult->Fetch())
		{
			$dbCrmActivity = CCrmActivity::GetList(
				array(),
				array(
					'ID' => $arLog['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N'
				)
			);

			if (
				($arCrmActivity = $dbCrmActivity->Fetch())
				&& ($arCrmActivity['TYPE_ID'] == CCrmActivityType::Task)
				&& CModule::IncludeModule("forum")
			)
			{
				$messageId = intval($arFields["SOURCE_ID"]);

				if ($arForumMessage = CForumMessage::GetByID($messageId))
				{
					$arFieldsMessage = array(
						"POST_MESSAGE" => $arFields["TEXT_MESSAGE"],
						"USE_SMILES" => "Y",
						"APPROVED" => "Y",
						"SONET_PERMS" => array("bCanFull" => true)
					);

					$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("SONET_COMMENT", $arTmp);
					if (is_array($arTmp))
					{
						if (array_key_exists("UF_SONET_COM_DOC", $arTmp))
						{
							$GLOBALS["UF_FORUM_MESSAGE_DOC"] = $arTmp["UF_SONET_COM_DOC"];
						}
						elseif (array_key_exists("UF_SONET_COM_FILE", $arTmp))
						{
							$arFieldsMessage["FILES"] = array();
							foreach($arTmp["UF_SONET_COM_FILE"] as $file_id)
							{
								$arFieldsMessage["FILES"][$file_id] = array("FILE_ID" => $file_id);
							}
							if (!empty($arFieldsMessage["FILES"]))
							{
								$arFileParams = array("FORUM_ID" => $arForumMessage["FORUM_ID"], "TOPIC_ID" => $arForumMessage["TOPIC_ID"]);
								if(CForumFiles::CheckFields($arFieldsMessage["FILES"], $arFileParams, "NOT_CHECK_DB"))
								{
									CForumFiles::Add(array_keys($arFieldsMessage["FILES"]), $arFileParams);
								}
							}
						}
					}

					$messageID = ForumAddMessage("EDIT", $arForumMessage["FORUM_ID"], $arForumMessage["TOPIC_ID"], $messageId, $arFieldsMessage, $sError, $sNote);
					unset($GLOBALS["UF_FORUM_MESSAGE_DOC"]);

					// get UF DOC value and FILE_ID there
					if ($messageID > 0)
					{
						$dbAddedMessageFiles = CForumFiles::GetList(array("ID" => "ASC"), array("MESSAGE_ID" => $messageID));
						while ($arAddedMessageFiles = $dbAddedMessageFiles->Fetch())
						{
							$ufFileID[] = $arAddedMessageFiles["FILE_ID"];
						}

						$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("FORUM_MESSAGE", "UF_FORUM_MESSAGE_DOC", $messageID, LANGUAGE_ID);
					}
				}
				else
				{
					$sError = GetMessage("CRM_SL_UPDATE_COMMENT_SOURCE_ERROR");
				}

				return array(
					"ERROR" => $sError,
					"NOTES" => $sNote,
					"UF" => array(
						"FILE" => $ufFileID,
						"DOC" => $ufDocID
					)
				);
			}
		}

		return array(
			"NO_SOURCE" => "Y"
		);
	}

	public static function DeleteCrmActivityComment($arFields)
	{
		if (
			!isset($arFields["SOURCE_ID"])
			|| intval($arFields["SOURCE_ID"]) <= 0
		)
		{
			return array(
				"NO_SOURCE" => "Y"
			);
		}

		$dbResult = CSocNetLog::GetList(
			array(),
			array("ID" => $arFields["LOG_ID"]),
			false,
			false,
			array('ID', 'ENTITY_ID')
		);

		if ($arLog = $dbResult->Fetch())
		{
			$dbCrmActivity = CCrmActivity::GetList(
				array(),
				array(
					'ID' => $arLog['ENTITY_ID'],
					'CHECK_PERMISSIONS' => 'N'
				)
			);

			if ($arCrmActivity = $dbCrmActivity->Fetch())
			{
				if ($arCrmActivity['TYPE_ID'] == CCrmActivityType::Task)
				{
					if (CModule::IncludeModule("forum"))
					{
						$res = ForumActions("DEL", array("MID" => intval($arFields["SOURCE_ID"])), $strErrorMessage, $strOKMessage);

						return array(
							"ERROR" => $strErrorMessage,
							"NOTES" => $strOKMessage
						);
					}
					else
					{
						return array(
							"ERROR" => GetMessage("CRM_SL_DELETE_COMMENT_SOURCE_ERROR_FORUM_NOT_INSTALLED"),
							"NOTES" => false
						);
					}
				}
				else
				{
					return array(
						"NO_SOURCE" => "Y"
					);
				}
			}
			else
			{
				return array(
					"NO_SOURCE" => "Y"
				);
			}
		}
		else
		{
			return array(
				"NO_SOURCE" => "Y"
			);
		}
	}	

	public static function GetLogEventLastUpdateTime($ID, $useTimeZome = true)
	{
		if(!$useTimeZome)
		{
			CTimeZone::Disable();
		}
		$dbResult = CSocNetLog::GetList(
			array(),
			array('ID' => $ID),
			false,
			false,
			array('ID', 'LOG_UPDATE')
		);

		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		$result = isset($arFields['LOG_UPDATE']) ? $arFields['LOG_UPDATE'] : '';

		if(!$useTimeZome)
		{
			CTimeZone::Enable();
		}

		return $result;
	}
}

class CCrmLiveFeedFilter
{
	private $gridFormID = null;
	private $entityTypeID = CCrmOwnerType::Undefined;
	private $arCompanyItemsTop = null;
	private $arItems = null;
	
	public function __construct($params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$this->gridFormID = isset($params["GridFormID"]) ? $params["GridFormID"] : "";
		$this->entityTypeID = isset($params["EntityTypeID"]) ? intval($params["EntityTypeID"]) : CCrmOwnerType::Undefined;

		$this->arCompanyItemsTop = array(
			"clearall" => array(
				"ID" => "clearall",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_COMPANY_PRESET_TOP")
			),
			"extended" => array(
				"ID" => "extended",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_COMPANY_PRESET_TOP_EXTENDED")
			)
		);
		
		$this->arCommonItemsTop = array(
			"clearall" => array(
				"ID" => "clearall",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_COMMON_PRESET_MY")
			),
			"all" => array(
				"ID" => "all",
				"SORT" => 200,
				"NAME" => GetMessage("CRM_LF_COMMON_PRESET_ALL")
			)
		);

		$this->arItems = array(
			"messages" => array(
				"ID" => "messages",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_PRESET_MESSAGES"),
				"FILTER" => array(
					"EVENT_ID" => array()
				)
			),
			"activities" => array(
				"ID" => "activities",
				"SORT" => 100,
				"NAME" => GetMessage("CRM_LF_PRESET_ACTIVITIES"),
				"FILTER" => array(
					"EVENT_ID" => array()
				)
			)
		);	
	}

	public function OnBeforeSonetLogFilterFill(&$arPageParamsToClear, &$arItemsTop, &$arItems, &$strAllItemTitle)
	{
		$arPageParamsToClear[] = $this->gridFormID."_active_tab";

		if ($this->entityTypeID == CCrmOwnerType::Company)
		{
			$arItemsTop = array_merge($arItemsTop, $this->arCompanyItemsTop);
		}
		elseif (empty($this->entityTypeID))
		{
			$arItemsTop = array_merge($arItemsTop, $this->arCommonItemsTop);
		}

		$arItems = array_merge($arItems, $this->arItems);

		$strAllItemTitle = GetMessage("CRM_LF_PRESET_ALL");

		return true;
	}

	public function OnSonetLogFilterProcess($presetFilterTopID, $presetFilterID, $arResultPresetFiltersTop, $arResultPresetFilters)
	{
		$result = array(
			"PARAMS" => array(
				"CUSTOM_DATA" => array(
					"CRM_PRESET_TOP_ID" => is_string($presetFilterTopID) ? $presetFilterTopID : '',
					"CRM_PRESET_ID" => is_string($presetFilterID) ? $presetFilterID : ''
				)
			)
		);

		return $result;
	}

	/*public static function Activate($params)
	{
		$self = new CCrmLiveFeedFilter($params);
		AddEventHandler("socialnetwork", "OnSonetLogFilterProcess", array($self, "OnSonetLogFilterProcess"));
	}*/
}

class CCrmLiveFeedComponent
{
	private $eventMeta = null;
	private $entityTypeID = null;
	private $fields = null;
	private $eventParams = null;
	private $activity = null;
	private $invoice = null;
	private $arSipServiceUrl = null;

	public function __construct($params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$this->fields = isset($params["FIELDS"]) && !empty($params["FIELDS"]) ? $params["FIELDS"] : false;
		$this->eventParams = isset($params["EVENT_PARAMS"]) ? $params["EVENT_PARAMS"] : array();
		$this->params = isset($params["PARAMS"]) ? $params["PARAMS"] : array();

		$this->arSipServiceUrl = array(
			CCrmOwnerType::Lead => SITE_DIR.'bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get(),
			CCrmOwnerType::Company => SITE_DIR.'bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
			CCrmOwnerType::Contact => SITE_DIR.'bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get()
		);

		if (!$this->fields)
		{
			throw new Exception("Empty fields");
		}

		$this->entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($this->fields["ENTITY_TYPE"]);

		if ($this->entityTypeID == CCrmOwnerType::Activity)
		{
			$this->activity = isset($params["ACTIVITY"]) ? $params["ACTIVITY"] : array();
			$this->eventMeta = array(
				CCrmActivityType::Meeting => array(
					"SUBJECT" => array(
						"CODE" => "COMBI_ACTIVITY_SUBJECT/ACTIVITY_ONCLICK", 
						"FORMAT" => "COMBI_TITLE"
					),
					"LOCATION" => array(
						"CODE" => "ACTIVITY_LOCATION",
						"FORMAT" => "TEXT"
					),
					"DATE" => array(
						"CODE" => "ACTIVITY_START_END_TIME",
						"FORMAT" => "DATETIME"
					),
					"CLIENT_ID" => array(
						"CODE" => "ACTIVITY_COMMUNICATIONS", 
						"FORMAT" => "COMMUNICATIONS"
					),
					"RESPONSIBLE" => array(
						"CODE" => "ACTIVITY_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				CCrmActivityType::Call => array(
					"SUBJECT" => array(
						"CODE" => "COMBI_ACTIVITY_SUBJECT/ACTIVITY_ONCLICK", 
						"FORMAT" => "COMBI_TITLE"
					),
					"DATE" => array(
						"CODE" => "ACTIVITY_START_END_TIME",
						"FORMAT" => "DATETIME"
					),
					"CLIENT_ID" => array(
						"CODE" => "ACTIVITY_COMMUNICATIONS", 
						"FORMAT" => "COMMUNICATIONS"
					),
					"RESPONSIBLE" => array(
						"CODE" => "ACTIVITY_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				CCrmActivityType::Email => array(
					"SUBJECT" => array(
						"CODE" => "COMBI_ACTIVITY_SUBJECT/ACTIVITY_ONCLICK", 
						"FORMAT" => "COMBI_TITLE"
					),
					"DATE" => array(
						"CODE" => "ACTIVITY_START_END_TIME",
						"FORMAT" => "DATETIME"
					),
					"CLIENT_ID" => array(
						"CODE" => "ACTIVITY_COMMUNICATIONS", 
						"FORMAT" => "COMMUNICATIONS"
					),
					"RESPONSIBLE" => array(
						"CODE" => "ACTIVITY_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				)
			);
		}
		elseif ($this->entityTypeID == CCrmOwnerType::Invoice)
		{
			$this->invoice = isset($params["INVOICE"]) ? $params["INVOICE"] : array();
			$this->eventMeta = array(
				"crm_invoice_add" => array(
					"INVOICE_ADD_TITLE" => array(
						"CODE" => "COMBI_INVOICE_ID/INVOICE_ORDER_TOPIC/INVOICE_URL", 
						"FORMAT" => "COMBI_TITLE_ID"
					),
					"PRICE" => array(
						"CODE" => array(
							"VALUE" => "INVOICE_PRICE",
							"CURRENCY" => "INVOICE_CURRENCY"
						),
						"FORMAT" => "SUM"
					),
					"STATUS" => array(
						"CODE" => "INVOICE_STATUS_ID",
						"FORMAT" => "INVOICE_PROGRESS",
					),
					"CLIENT_ID" => array(
						"CODE" => "COMBI_INVOICE_UF_CONTACT_ID/INVOICE_UF_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"DEAL" => array(
						"CODE" => "INVOICE_UF_DEAL_ID",
						"FORMAT" => "DEAL_ID",
					),					
					"RESPONSIBLE" => array(
						"CODE" => "INVOICE_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				)
			);
		}
		else
		{
			$this->eventMeta = array(
				"crm_lead_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "EVENT_PARAMS_TITLE", 
						"FORMAT" => "TEXT_ADD"
					),
					"STATUS" => array(
						"CODE" => "EVENT_PARAMS_STATUS_ID",
						"FORMAT" => "LEAD_PROGRESS",
					),
					"CLIENT_NAME" => array(
						"CODE" => "COMBI_EVENT_PARAMS_NAME/EVENT_PARAMS_LAST_NAME/EVENT_PARAMS_SECOND_NAME/EVENT_PARAMS_COMPANY_TITLE",
						"FORMAT" => "COMBI_CLIENT_NAME",
					),
					"OPPORTUNITY" => array(
						"CODE" => array(
							"VALUE" => "EVENT_PARAMS_OPPORTUNITY",
							"CURRENCY" => "EVENT_PARAMS_CURRENCY_ID"
						),
						"FORMAT" => "SUM"
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_lead_progress" => array(
					"FINAL_STATUS_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_STATUS_ID",
						"FORMAT" => "LEAD_PROGRESS",
					),
					"START_STATUS_ID" => array(
						"CODE" => "EVENT_PARAMS_START_STATUS_ID", 
						"FORMAT" => "LEAD_PROGRESS"
					)
				),
				"crm_lead_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID", 
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_lead_denomination" => array(
					"FINAL_TITLE" => array(
						"CODE" => "EVENT_PARAMS_FINAL_TITLE",
						"FORMAT" => "TEXT",
					),
					"START_TITLE" => array(
						"CODE" => "EVENT_PARAMS_START_TITLE", 
						"FORMAT" => "TEXT"
					)
				),
				"crm_lead_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_contact_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "COMBI_EVENT_PARAMS_NAME/EVENT_PARAMS_LAST_NAME/EVENT_PARAMS_SECOND_NAME/EVENT_PARAMS_PHOTO_ID/EVENT_PARAMS_COMPANY_ID/ENTITY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"PHONES" => array(
						"CODE" => "EVENT_PARAMS_PHONES",
						"FORMAT" => "PHONE",
					),
					"EMAILS" => array(
						"CODE" => "EVENT_PARAMS_EMAILS",
						"FORMAT" => "EMAIL",
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_contact_owner" => array(
					"FINAL_OWNER_COMPANY_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_OWNER_COMPANY_ID", 
						"FORMAT" => "COMPANY_ID"
					),
					"START_OWNER_COMPANY_ID" => array(
						"CODE" => "EVENT_PARAMS_START_OWNER_COMPANY_ID",
						"FORMAT" => "COMPANY_ID",
					),
				),
				"crm_contact_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID", 
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_contact_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_company_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "COMBI_EVENT_PARAMS_TITLE/EVENT_PARAMS_LOGO_ID/ENTITY_ID",
						"FORMAT" => "COMBI_COMPANY",
					),
					"COMPANY_TYPE" => array(
						"CODE" => "EVENT_PARAMS_TYPE",
						"FORMAT" => "COMPANY_TYPE",
					),
					"REVENUE" => array(
						"CODE" => array(
							"VALUE" => "EVENT_PARAMS_REVENUE",
							"CURRENCY" => "EVENT_PARAMS_CURRENCY_ID"
						),
						"FORMAT" => "SUM"
					),
					"PHONES" => array(
						"CODE" => "EVENT_PARAMS_PHONES",
						"FORMAT" => "PHONE",
					),
					"EMAILS" => array(
						"CODE" => "EVENT_PARAMS_EMAILS",
						"FORMAT" => "EMAIL",
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_company_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID", 
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_company_denomination" => array(
					"FINAL_TITLE" => array(
						"CODE" => "EVENT_PARAMS_FINAL_TITLE",
						"FORMAT" => "TEXT",
					),
					"START_TITLE" => array(
						"CODE" => "EVENT_PARAMS_START_TITLE", 
						"FORMAT" => "TEXT"
					)
				),
				"crm_company_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_deal_add" => array(
					"ADD_TITLE" => array(
						"CODE" => "EVENT_PARAMS_TITLE",
						"FORMAT" => "TEXT_ADD"
					),
					"STATUS" => array(
						"CODE" => "EVENT_PARAMS_STAGE_ID",
						"FORMAT" => "DEAL_PROGRESS",
					),
					"OPPORTUNITY" => array(
						"CODE" => array(
							"VALUE" => "EVENT_PARAMS_OPPORTUNITY",
							"CURRENCY" => "EVENT_PARAMS_CURRENCY_ID"
						),
						"FORMAT" => "SUM"
					),
					"CLIENT_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_CONTACT_ID/EVENT_PARAMS_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"RESPONSIBLE" => array(
						"CODE" => "EVENT_PARAMS_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_deal_progress" => array(
					"FINAL_STATUS_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_STATUS_ID",
						"FORMAT" => "DEAL_PROGRESS",
					),
					"START_STATUS_ID" => array(
						"CODE" => "EVENT_PARAMS_START_STATUS_ID", 
						"FORMAT" => "DEAL_PROGRESS"
					)
				),
				"crm_deal_responsible" => array(
					"FINAL_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_FINAL_RESPONSIBLE_ID",
						"FORMAT" => "PERSON_ID",
					),
					"START_RESPONSIBLE_ID" => array(
						"CODE" => "EVENT_PARAMS_START_RESPONSIBLE_ID", 
						"FORMAT" => "PERSON_ID"
					)
				),
				"crm_deal_denomination" => array(
					"FINAL_TITLE" => array(
						"CODE" => "EVENT_PARAMS_FINAL_TITLE",
						"FORMAT" => "TEXT",
					),
					"START_TITLE" => array(
						"CODE" => "EVENT_PARAMS_START_TITLE", 
						"FORMAT" => "TEXT"
					)
				),
				"crm_deal_message" => array(
					"MESSAGE_TITLE" => array(
						"CODE" => "TITLE",
						"FORMAT" => "TEXT_FORMATTED_BOLD",
					),
					"MESSAGE" => array(
						"CODE" => "MESSAGE",
						"FORMAT" => "TEXT_FORMATTED",
					),
				),
				"crm_deal_client" => array(
					"FINAL_CLIENT_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_FINAL_CLIENT_CONTACT_ID/EVENT_PARAMS_FINAL_CLIENT_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
					"START_CLIENT_ID" => array(
						"CODE" => "COMBI_EVENT_PARAMS_START_CLIENT_CONTACT_ID/EVENT_PARAMS_START_CLIENT_COMPANY_ID",
						"FORMAT" => "COMBI_CLIENT",
					),
				)
			);
		}

		if (!array_key_exists($this->fields["EVENT_ID"], $this->eventMeta))
		{
			return false;
		}
		
	}

	public function showField($arField, $arUF = array())
	{
		$strResult = "";

		switch($arField["FORMAT"])
		{
			case "LEAD_PROGRESS":
				if (!empty($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding crm-feed-info-bar-cont">';
					$strResult .= CCrmViewHelper::RenderLeadStatusControl(array(
						'ENTITY_TYPE_NAME' => CCrmOwnerType::Lead,
						'REGISTER_SETTINGS' => true,
						'PREFIX' => "",
						'ENTITY_ID' => CCrmLiveFeedEntity::Lead,
						'CURRENT_ID' => $arField["VALUE"],
						'READ_ONLY' => true
					));
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "DEAL_PROGRESS":
				if (!empty($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding crm-feed-info-bar-cont">';
					$strResult .= CCrmViewHelper::RenderDealStageControl(array(
							'ENTITY_TYPE_NAME' => CCrmOwnerType::Deal,
							'REGISTER_SETTINGS' => true,
							'PREFIX' => "",
							'ENTITY_ID' => CCrmLiveFeedEntity::Deal,
							'CURRENT_ID' => $arField["VALUE"],
							'READ_ONLY' => true
						));
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "INVOICE_PROGRESS":
				if (!empty($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding crm-feed-info-bar-cont">';
					$strResult .= CCrmViewHelper::RenderInvoiceStatusControl(array(
						'ENTITY_TYPE_NAME' => CCrmOwnerType::Invoice,
						'REGISTER_SETTINGS' => true,
						'PREFIX' => "",
						'ENTITY_ID' => CCrmLiveFeedEntity::Invoice,
						'CURRENT_ID' => $arField["VALUE"],
						'READ_ONLY' => true
					));
					$strResult .= "</span>";
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;				
			case "LEAD_STATUS":
				$infos = CCrmStatus::GetStatus('STATUS');
				if (
					!empty($arField["VALUE"])
					&& array_key_exists($arField["VALUE"], $infos)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= $infos[$arField["VALUE"]]["NAME"];
					$strResult .= "</span>";
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "PERSON_NAME":
				if (is_array($arField["VALUE"]))
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CUser::FormatName(CSite::GetNameFormat(), $arField["VALUE"]);
					$strResult .= "</span>";
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "PERSON_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$dbUser = CUser::GetByID(intval($arField["VALUE"]));
					if ($arUser = $dbUser->GetNext())
					{
						$strResult .= "#row_begin#";
						$strResult .= "#cell_begin_left#";
						$strResult .=  $arField["TITLE"].":";
						$strResult .= "#cell_end#";
						$strResult .= "#cell_begin_right#";

						if ($arUser["PERSONAL_PHOTO"] > 0)
						{
							$arFileTmp = CFile::ResizeImageGet(
								$arUser["PERSONAL_PHOTO"],
								array('width' => 39, 'height' => 39),
								BX_RESIZE_IMAGE_EXACT,
								false
							);
						}

						$strUser = "";

						$strUser .= '<div class="feed-com-avatar crm-feed-company-avatar">';
						if(is_array($arFileTmp) && isset($arFileTmp['src']))
						{
							if (strlen($this->params["PATH_TO_USER"]) > 0)
							{
								$strUser .= '<a target="_blank" href="'.str_replace(array("#user_id#", "#USER_ID#"), intval($arField["VALUE"]), $this->params["PATH_TO_USER"]).'"><img src="'.$arFileTmp['src'].'" alt=""/></a>';
							}
							else
							{
								$strUser .= '<img src="'.$arFileTmp['src'].'" alt=""/>';
							}
						}
						$strUser .= '</div>';

						if (strlen($this->params["PATH_TO_USER"]) > 0)
						{
							$strUser .= '<a class="crm-detail-info-resp-name" target="_blank" href="'.str_replace(array("#user_id#", "#USER_ID#"), intval($arField["VALUE"]), $this->params["PATH_TO_USER"]).'">'.CUser::FormatName(CSite::GetNameFormat(), $arUser, true, false).'</a>';
						}
						else
						{
							$strUser .= '<span class="crm-detail-info-resp-name">'.CUser::FormatName(CSite::GetNameFormat(), $arUser, true, false).'</span>';
						}						

						if (strlen($arUser["WORK_POSITION"]) > 0)
						{
							$strUser .= '<span class="crm-detail-info-resp-descr">'.$arUser["WORK_POSITION"].'</span>';
						}
						
						$strResult .= '<span class="crm-detail-info-resp">'.$strUser.'</span>';

						$strResult .= "#cell_end#";
						$strResult .= "#row_end#";
					}
				}
				break;
			case "COMPANY_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
							'ENTITY_ID' => $arField["VALUE"],
							'PREFIX' => "",
							'CLASS_NAME' => '',
							'CHECK_PERMISSIONS' => 'N'
						)
					);
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "COMPANY_TYPE":
				$infos = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
				if (
					!empty($arField["VALUE"])
					&& array_key_exists($arField["VALUE"], $infos)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= $infos[$arField["VALUE"]];
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "CONTACT_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					$strResult .= '<div class="crm-feed-client-block">';
					$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';
					$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arField["VALUE"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('PHOTO'));
					if (
						($arRes = $dbRes->Fetch()) 
						&& (intval($arRes["PHOTO"]) > 0)
					)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arRes["PHOTO"],
							array('width' => 39, 'height' => 39),
							BX_RESIZE_IMAGE_EXACT,
							false
						);

						if(
							is_array($arFileTmp) 
							&& isset($arFileTmp["src"])
						)
						{
							$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
						}
					}
					$strResult .= '</span>';						

					$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
							'ENTITY_ID' => $arField["VALUE"],
							'PREFIX' => "",
							'CLASS_NAME' => '',
							'CHECK_PERMISSIONS' => 'N'
						)
					);

					$strResult .= '</div>';

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "COMBI_CLIENT":
				if (
					is_array($arField["VALUE"])
					&& (
						(array_key_exists("CONTACT_ID", $arField["VALUE"]) && intval($arField["VALUE"]["CONTACT_ID"]) > 0)
						|| (array_key_exists("CONTACT_NAME", $arField["VALUE"]) && strlen($arField["VALUE"]["CONTACT_NAME"]) > 0)
						|| (array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"]) && strlen($arField["VALUE"]["CONTACT_LAST_NAME"]) > 0)
						|| (array_key_exists("COMPANY_ID", $arField["VALUE"]) && intval($arField["VALUE"]["COMPANY_ID"]) > 0)
					)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					if (
						(
							array_key_exists("CONTACT_ID", $arField["VALUE"]) 
							&& intval($arField["VALUE"]["CONTACT_ID"]) > 0
						)
						|| (
							array_key_exists("CONTACT_NAME", $arField["VALUE"]) 
							&& strlen($arField["VALUE"]["CONTACT_NAME"]) > 0
						)
						|| (
							array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"]) 
							&& strlen($arField["VALUE"]["CONTACT_LAST_NAME"]) > 0
						)
					)
					{
						if (
							array_key_exists("CONTACT_ID", $arField["VALUE"]) 
							&& intval($arField["VALUE"]["CONTACT_ID"]) > 0
						)
						{
							$strResult .= '<div class="crm-feed-client-block">';
							$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';
							$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arField["VALUE"]["CONTACT_ID"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('PHOTO', 'COMPANY_ID'));
							if ($arRes = $dbRes->Fetch())
							{
								$contactCompanyID = $arRes['COMPANY_ID'];
								if (intval($arRes["PHOTO"]) > 0)
								{
									$arFileTmp = CFile::ResizeImageGet(
										$arRes["PHOTO"],
										array('width' => 39, 'height' => 39),
										BX_RESIZE_IMAGE_EXACT,
										false
									);

									if(
										is_array($arFileTmp) 
										&& isset($arFileTmp["src"])
									)
									{
										$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
									}
								}
							}
							$strResult .= '</span>';						

							$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
								array(
									'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
									'ENTITY_ID' => $arField["VALUE"]["CONTACT_ID"],
									'PREFIX' => '',
									'CLASS_NAME' => '',
									'CHECK_PERMISSIONS' => 'N'
								)
							);
						}
						else
						{
							$strResult .= '<div class="crm-feed-client-block">';
							$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';

							if (intval($arField['VALUE']['PHOTO_ID']) > 0)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$arField['VALUE']['PHOTO_ID'],
									array('width' => 39, 'height' => 39),
									BX_RESIZE_IMAGE_EXACT,
									false
								);

								if(
									is_array($arFileTmp) 
									&& isset($arFileTmp["src"])
								)
								{
									$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
								}
							}

							$strResult .= '</span>';						

							if (
								array_key_exists("ENTITY_ID", $arField["VALUE"]) 
								&& intval($arField["VALUE"]["ENTITY_ID"]) > 0
							)
							{
								$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Contact, $arField["VALUE"]["ENTITY_ID"], true);
							}

							$clientName = CUser::FormatName(
								\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
								array(
									'LOGIN' => '',
									'NAME' => isset($arField['VALUE']['CONTACT_NAME']) ? $arField['VALUE']['CONTACT_NAME'] : '',
									'LAST_NAME' => isset($arField['VALUE']['CONTACT_LAST_NAME']) ? $arField['VALUE']['CONTACT_LAST_NAME'] : '',
									'SECOND_NAME' => isset($arField['VALUE']['CONTACT_SECOND_NAME']) ? $arField['VALUE']['CONTACT_SECOND_NAME'] : ''
								),
								false, false
							);

							$strResult .= (strlen($url) > 0 ? '<a href="'.$url.'" class="crm-feed-client-name">'.$clientName.'</a>' : $clientName);
						}

						$strResult .= '<span class="crm-feed-client-company">';
						$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
							array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => (
									array_key_exists("COMPANY_ID", $arField["VALUE"]) 
									&& intval($arField["VALUE"]["COMPANY_ID"]) > 0 
										? $arField["VALUE"]["COMPANY_ID"] 
										: intval($contactCompanyID)
								),
								'PREFIX' => '',
								'CLASS_NAME' => '',
								'CHECK_PERMISSIONS' => 'N'
							)
						);
						$strResult .= '</span>';

						$strResult .= '</div>';
					}
					else
					{
						$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
							array(
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $arField["VALUE"]["COMPANY_ID"],
								'PREFIX' => "",
								'CLASS_NAME' => '',
								'CHECK_PERMISSIONS' => 'N'
							)
						);
					}
					
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMBI_COMPANY":
				if (
					is_array($arField["VALUE"])
					&& (array_key_exists("TITLE", $arField["VALUE"]) && strlen($arField["VALUE"]["TITLE"]) > 0)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Company, $arField["VALUE"]["ENTITY_ID"]);
					if (intval($arField['VALUE']['LOGO_ID']) > 0)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arField['VALUE']['LOGO_ID'],
							array('width' => 39, 'height' => 39),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
					}

					if(is_array($arFileTmp) && isset($arFileTmp['src']))
					{
						$strResult .= '<a class="crm-feed-user-block" href="'.$url.'">';
						$strResult .= '<span class="feed-com-avatar crm-feed-company-avatar">';
						$strResult .= '<img width="39" height="39" alt="" src="'.$arFileTmp['src'].'">';
						$strResult .= '</span>';
						$strResult .= '<span class="crm-feed-user-name">'.$arField["VALUE"]["TITLE"].'</span>';
						$strResult .= '</a>';
					}
					else
					{
						$strResult .= '<a class="crm-feed-info-link" href="'.$url.'">'.$arField["VALUE"]["TITLE"].'</a>';
					}

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMBI_CLIENT_NAME":
				if (
					is_array($arField["VALUE"])
					&& (
						(array_key_exists("CONTACT_NAME", $arField["VALUE"]) && strlen($arField["VALUE"]["CONTACT_NAME"]) > 0)
						|| (array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"]) && strlen($arField["VALUE"]["CONTACT_LAST_NAME"]) > 0)
						|| (array_key_exists("COMPANY_TITLE", $arField["VALUE"]) && strlen($arField["VALUE"]["COMPANY_TITLE"]) > 0)
					)
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";

					if (
						(
							array_key_exists("CONTACT_NAME", $arField["VALUE"]) 
							&& strlen($arField["VALUE"]["CONTACT_NAME"]) > 0
						)
						|| (
							array_key_exists("CONTACT_LAST_NAME", $arField["VALUE"]) 
							&& strlen($arField["VALUE"]["CONTACT_LAST_NAME"]) > 0
						)
					)
					{
						$strResult .= '<div class="crm-feed-client-block">';
						$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar"></span>';

						$arUser = array(
							"NAME" => $arField["VALUE"]["CONTACT_NAME"],
							"LAST_NAME" => $arField["VALUE"]["CONTACT_LAST_NAME"],
							"SECOND_NAME" => $arField["VALUE"]["CONTACT_SECOND_NAME"],
						);
						$strResult .= CUser::FormatName(\Bitrix\Crm\Format\PersonNameFormatter::getFormat(), $arUser);

						$strResult .= '<span class="crm-feed-client-company">'.(strlen($arField["VALUE"]["COMPANY_TITLE"]) > 0 ? $arField["VALUE"]["COMPANY_TITLE"] : "").'</span>';
						$strResult .= '</div>';
					}
					else
					{
						$strResult .= $arField["VALUE"]["COMPANY_TITLE"];
					}
					
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "DEAL_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CCrmViewHelper::PrepareEntityBaloonHtml(
						array(
							'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
							'ENTITY_ID' => $arField["VALUE"],
							'PREFIX' => "",
							'CLASS_NAME' => '',
							'CHECK_PERMISSIONS' => 'N'
						)
					);
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMMUNICATIONS":
				if (
					is_array($arField["VALUE"]) 
					&& count($arField["VALUE"]) > 0
				)
				{
					$arCommunication = $arField["VALUE"][0];

					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<div class="crm-feed-client-block">';

					if (in_array($arCommunication["ENTITY_TYPE_ID"], array(CCrmOwnerType::Company, CCrmOwnerType::Contact, CCrmOwnerType::Lead)))
					{
						$strResult .= '<span class="feed-com-avatar crm-feed-user-avatar">';
						if ($arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Contact)
						{
							$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $arCommunication["ENTITY_ID"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('PHOTO'));
							if (
								($arRes = $dbRes->Fetch()) 
								&& (intval($arRes["PHOTO"]) > 0)
							)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$arRes["PHOTO"],
									array('width' => 39, 'height' => 39),
									BX_RESIZE_IMAGE_EXACT,
									false
								);

								if(
									is_array($arFileTmp) 
									&& isset($arFileTmp["src"])
								)
								{
									$strResult .= '<img width="39" height="39" src="'.$arFileTmp['src'].'" alt="">';
								}
							}
						}
						elseif ($arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Company)
						{
							$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $arCommunication["ENTITY_ID"], 'CHECK_PERMISSIONS' => 'N'), false, false, array('LOGO'));
							if (
								($arRes = $dbRes->Fetch()) 
								&& (intval($arRes["LOGO"]) > 0)
							)
							{
								$arFileTmp = CFile::ResizeImageGet(
									$arRes["LOGO"],
									array('width' => 30, 'height' => 30),
									BX_RESIZE_IMAGE_EXACT,
									false
								);

								if(
									is_array($arFileTmp) 
									&& isset($arFileTmp["src"])
								)
								{
									$strResult .= '<img width="30" height="30" src="'.$arFileTmp['src'].'" alt="">';
								}
							}
						}
						$strResult .= '</span>';						
					}

					$arBaloonFields = array(
						'ENTITY_TYPE_ID' => $arCommunication["ENTITY_TYPE_ID"],
						'ENTITY_ID' => $arCommunication["ENTITY_ID"],
						'PREFIX' => "",
						'CLASS_NAME' => 'crm-feed-client-name',
						'CHECK_PERMISSIONS' => 'N'
					);

					if (
						$arCommunication["ENTITY_TYPE_ID"] == CCrmOwnerType::Lead
						&& is_array($arCommunication["ENTITY_SETTINGS"])
					)
					{
						$arBaloonFields["TITLE"] = (isset($arCommunication["ENTITY_SETTINGS"]["LEAD_TITLE"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["LEAD_TITLE"]) : "");
						$arBaloonFields["NAME"] = (isset($arCommunication["ENTITY_SETTINGS"]["NAME"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["NAME"]) : "");
						$arBaloonFields["LAST_NAME"] = (isset($arCommunication["ENTITY_SETTINGS"]["LAST_NAME"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["LAST_NAME"]) : "");
						$arBaloonFields["SECOND_NAME"] = (isset($arCommunication["ENTITY_SETTINGS"]["SECOND_NAME"]) ? htmlspecialcharsback($arCommunication["ENTITY_SETTINGS"]["SECOND_NAME"]) : "");
					}
					$strResult .= '<div>'.CCrmViewHelper::PrepareEntityBaloonHtml($arBaloonFields).'</div>';

					switch ($arCommunication["TYPE"])
					{
						case 'EMAIL':
							$strResult .= '<div><a href="mailto:'.$arCommunication["VALUE"].'" class="crm-feed-client-phone">'.$arCommunication["VALUE"].'</div>';
							break;
						case 'PHONE':
							if (CCrmSipHelper::isEnabled())
							{
								ob_start();
								?>
								<script type="text/javascript">
								if (typeof (window.bSipManagerUrlDefined_<?=$arCommunication["ENTITY_TYPE_ID"]?>) === 'undefined')
								{
									window.bSipManagerUrlDefined_<?=$arCommunication["ENTITY_TYPE_ID"]?> = true;
									BX.ready(
										function()
										{
											var mgr = BX.CrmSipManager.getCurrent();
											mgr.setServiceUrl(
												"CRM_<?=CUtil::JSEscape(CCrmOwnerType::ResolveName($arCommunication["ENTITY_TYPE_ID"]))?>",
												"<?=CUtil::JSEscape($this->arSipServiceUrl[$arCommunication["ENTITY_TYPE_ID"]])?>"
											);

											if(typeof(BX.CrmSipManager.messages) === 'undefined')
											{
												BX.CrmSipManager.messages =
												{
													"unknownRecipient": "<?= GetMessageJS('CRM_LF_SIP_MGR_UNKNOWN_RECIPIENT')?>",
													"enableCallRecording": "<?= GetMessageJS('CRM_LF_SIP_MGR_ENABLE_CALL_RECORDING')?>",
													"makeCall": "<?= GetMessageJS('CCRM_LF_SIP_MGR_MAKE_CALL')?>"
												};
											}
										}
									);
								}
								</script>
								<?
								$strResult .= ob_get_contents();
								ob_end_clean();
							}

							$strResult .= '<div><span class="crm-feed-num-block">'.CCrmViewHelper::PrepareMultiFieldHtml(
								'PHONE',
								array(
									'VALUE' => $arCommunication["VALUE"], 
									'VALUE_TYPE_ID' => 'WORK'
								),
								array(
									'ENABLE_SIP' => true,
									'SIP_PARAMS' => array(
										'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::ResolveName($arCommunication["ENTITY_TYPE_ID"]),
										'ENTITY_ID' => $arCommunication["ENTITY_ID"]
									)
								)
							).'</span></div>';
							break;
					}

					$strResult .= '<span class="crm-feed-client-company">'.(is_array($arCommunication["ENTITY_SETTINGS"]) && isset($arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"]) ? $arCommunication["ENTITY_SETTINGS"]["COMPANY_TITLE"] : "").'</span>';
					$strResult .= '</div>';
					
					$moreCnt = count($arField["VALUE"]) - 1;
					if ($moreCnt > 0)
					{
						$strResult .= "#clients_more_link#";
					}

					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "AVATAR_ID":
				if (intval($arField["VALUE"]) > 0)
				{
					$arFileTmp = CFile::ResizeImageGet(
						$arField["VALUE"],
						array('width' => $this->params["AVATAR_SIZE"], 'height' => $this->params["AVATAR_SIZE"]),
						BX_RESIZE_IMAGE_EXACT,
						false
					);

					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= '<img src="'.$arFileTmp["src"].'" border="0" alt="'.$this->params["AVATAR_SIZE"].'" width="" height="'.$this->params["AVATAR_SIZE"].'">';
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "SUM":
				if (intval($arField["VALUE"]["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= '<span class="crm-feed-info-sum">'.CCrmCurrency::MoneyToString($arField["VALUE"]["VALUE"], $arField["VALUE"]["CURRENCY"]).'</span>';
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "PHONE":
			case "EMAIL":
				if (!empty($arField["VALUE"]))
				{
					$infos = CCrmFieldMulti::GetEntityTypes();

					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= CCrmViewHelper::PrepareFirstMultiFieldHtml(
						$arField["FORMAT"],
						$arField["VALUE"],
						$infos[$arField["FORMAT"]]
					);

					if(
						count($arField["VALUE"]) > 1
						|| (!empty($arField["VALUE"]["WORK"]) && count($arField["VALUE"]["WORK"]) > 1)
						|| (!empty($arField["VALUE"]["MOBILE"]) && count($arField["VALUE"]["MOBILE"]) > 1)
						|| (!empty($arField["VALUE"]["FAX"]) && count($arField["VALUE"]["FAX"]) > 1)
						|| (!empty($arField["VALUE"]["PAGER"]) && count($arField["VALUE"]["PAGER"]) > 1)
						|| (!empty($arField["VALUE"]["OTHER"]) && count($arField["VALUE"]["OTHER"]) > 1)
					)
					{
						$anchorID = strtolower($arField["FORMAT"]);
						$strResult .= '<span style="margin-left: 10px;" class="crm-item-tel-list" id="'.htmlspecialcharsbx($anchorID).'"'.' onclick="'.CCrmViewHelper::PrepareMultiFieldValuesPopup($anchorID, $anchorID, $arField["FORMAT"], $arField["VALUE"], $infos[$arField["FORMAT"]]).'"></span>';
					}
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "TEXT_FORMATTED":
			case "TEXT_FORMATTED_BOLD":
				if ($arField["VALUE"] != CCrmLiveFeed::UntitledMessageStub)
				{
					$text_formatted = $this->ParseText(htmlspecialcharsback($arField["VALUE"]), $arUF, $arParams["PARAMS"]);
					if (strlen($text_formatted) > 0)
					{
						$strResult .= "#row_begin#";
						$strResult .= "#cell_begin_colspan2#";
						if ($arField["FORMAT"] == "TEXT_FORMATTED_BOLD")
						{
							$strResult .=  "<b>".$text_formatted."</b>";
						}
						else
						{
							$strResult .=  $text_formatted;
						}
						$strResult .= "#cell_end#";
						$strResult .= "#row_end#";
					}
				}

				break;
			case "COMBI_TITLE":
				if (
					is_array($arField["VALUE"])
					&& array_key_exists("TITLE", $arField["VALUE"]) && strlen($arField["VALUE"]["TITLE"]) > 0
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';

					if (array_key_exists("URL", $arField["VALUE"]) && strlen($arField["VALUE"]["URL"]) > 0)
					{
						$strResult .= '<a href="'.$arField["VALUE"]["URL"].'">'.$arField["VALUE"]["TITLE"].'</a>';
					}
					elseif (array_key_exists("ONCLICK", $arField["VALUE"]) && strlen($arField["VALUE"]["ONCLICK"]) > 0)
					{
						$strResult .= '<a href="javascript:void(0)" onclick="'.$arField["VALUE"]["ONCLICK"].'">'.$arField["VALUE"]["TITLE"].'</a>';
					}
					else
					{
						$strResult .= $arField["VALUE"]["TITLE"];
					}

					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "COMBI_TITLE_ID":
				if (
					is_array($arField["VALUE"])
					&& array_key_exists("TITLE", $arField["VALUE"]) && strlen($arField["VALUE"]["TITLE"]) > 0
					&& array_key_exists("ID", $arField["VALUE"]) && strlen($arField["VALUE"]["ID"]) > 0
				)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';

					if (array_key_exists("URL", $arField["VALUE"]) && strlen($arField["VALUE"]["URL"]) > 0)
					{
						$strResult .= '<a href="'.$arField["VALUE"]["URL"].'">'.GetMessage("C_CRM_LF_COMBI_TITLE_ID_VALUE", array("#ID#" => $arField["VALUE"]["ID"], "#TITLE#" => $arField["VALUE"]["TITLE"])).'</a>';
					}
					else
					{
						$strResult .= GetMessage("C_CRM_LF_COMBI_TITLE_ID_VALUE", array("#ID#" => $arField["VALUE"]["ID"], "#TITLE#" => $arField["VALUE"]["TITLE"]));
					}

					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}

				break;
			case "TEXT_ADD":
				if (strlen($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= '<span class="crm-feed-info-name">'.$arField["VALUE"].'</span>';
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
				break;
			case "TEXT":
			default:
				if (strlen($arField["VALUE"]) > 0)
				{
					$strResult .= "#row_begin#";
					$strResult .= "#cell_begin_left#";
					$strResult .=  $arField["TITLE"].":";
					$strResult .= "#cell_end#";
					$strResult .= "#cell_begin_right#";
					$strResult .= '<span class="crm-feed-info-text-padding">';
					$strResult .= $arField["VALUE"];
					$strResult .= '</span>';
					$strResult .= "#cell_end#";
					$strResult .= "#row_end#";
				}
		}

		return $strResult;
	}
	
	public function formatFields()
	{
		$arReturn = array();

		if ($this->entityTypeID == CCrmOwnerType::Activity)
		{
			foreach($this->eventMeta[$this->activity["TYPE_ID"]] as $key => $arValue)
			{
				$arReturn[$key] = $this->formatField($key, $arValue);
			}
		}
		else
		{
			foreach($this->eventMeta[$this->fields["EVENT_ID"]] as $key => $arValue)
			{
				$arReturn[$key] = $this->formatField($key, $arValue);
			}
		}

		return $arReturn;
	}

	private function formatField($key, $arValue)
	{
		switch($key)
		{
			case "ADD_TITLE":
				$title = GetMessage("C_CRM_LF_".$this->fields["ENTITY_TYPE"]."_ADD_TITLE");
				break;
			default:
				$title = GetMessage("C_CRM_LF_".$key."_TITLE");
		}

		$value = $this->getValue($arValue["CODE"]);

		return array(
			"TITLE" => $title,
			"FORMAT" => $arValue["FORMAT"],
			"VALUE" => $value
		);
	}

	private function getValue($value_code)
	{
		if (!is_array($value_code))
		{
			if (strpos($value_code, "COMBI_") === 0)
			{
				$arFieldName = explode("/", substr($value_code, 6));
				if (is_array($arFieldName))
				{
					$arReturn = array();

					foreach($arFieldName as $fieldName)
					{
						if (strpos($fieldName, "EVENT_PARAMS_") === 0)
						{
							$key = substr($fieldName, 13);
						}
						elseif (strpos($fieldName, "ACTIVITY_") === 0)
						{
							$key = substr($fieldName, 9);
						}
						elseif (strpos($fieldName, "INVOICE_") === 0)
						{
							$key = substr($fieldName, 8);
						}
						else
						{
							$key = $fieldName;
						}

						if (strpos($key, "CONTACT_ID") !== false)
						{
							$key = "CONTACT_ID";
						}
						elseif (strpos($key, "LAST_NAME") !== false)
						{
							$key = "CONTACT_LAST_NAME";
						}
						elseif (strpos($key, "SECOND_NAME") !== false)
						{
							$key = "CONTACT_SECOND_NAME";
						}
						elseif (strpos($key, "NAME") !== false)
						{
							$key = "CONTACT_NAME";
						}
						elseif (strpos($key, "COMPANY_TITLE") !== false)
						{
							$key = "COMPANY_TITLE";
						}
						elseif (strpos($key, "COMPANY_ID") !== false)
						{
							$key = "COMPANY_ID";
						}
						elseif (
							strpos($key, "TITLE") !== false
							|| strpos($key, "ORDER_TOPIC") !== false
							|| strpos($key, "SUBJECT") !== false
						)
						{
							$key = "TITLE";
						}
						elseif (strpos($key, "ENTITY_ID") !== false)
						{
							$key = "ENTITY_ID";
						}
						elseif (strpos($key, "PHOTO_ID") !== false)
						{
							$key = "PHOTO_ID";
						}
						elseif (strpos($key, "LOGO_ID") !== false)
						{
							$key = "LOGO_ID";
						}
						elseif (strpos($key, "ID") !== false)
						{
							$key = "ID";
						}
						
						$arReturn[$key] = $this->getValue($fieldName);
					}

					return $arReturn;
				}
				
			}
			elseif (strpos($value_code, "EVENT_PARAMS_") === 0)
			{
				if (is_array($this->eventParams[substr($value_code, 13)]))
				{
					array_walk($this->eventParams[substr($value_code, 13)], array($this, '__htmlspecialcharsbx'));
					return $this->eventParams[substr($value_code, 13)];
				}
				else
				{
					return htmlspecialcharsbx($this->eventParams[substr($value_code, 13)]);
				}
			}
			elseif (strpos($value_code, "ACTIVITY_ONCLICK") === 0)
			{
				return "BX.CrmActivityEditor.viewActivity('livefeed', ".$this->activity["ID"].", { 'enableInstantEdit':true, 'enableEditButton':true });";
			}
			elseif (strpos($value_code, "ACTIVITY_") === 0)
			{
				if (is_array($this->activity[substr($value_code, 9)]))
				{
					array_walk($this->activity[substr($value_code, 9)], array($this, '__htmlspecialcharsbx'));
					return $this->activity[substr($value_code, 9)];
				}
				else
				{
					return htmlspecialcharsbx($this->activity[substr($value_code, 9)]);
				}
			}
			elseif (strpos($value_code, "INVOICE_") === 0)
			{
				if (is_array($this->activity[substr($value_code, 9)]))
				{
					array_walk($this->invoice[substr($value_code, 8)], array($this, '__htmlspecialcharsbx'));
					return $this->invoice[substr($value_code, 8)];
				}
				else
				{
					return htmlspecialcharsbx($this->invoice[substr($value_code, 8)]);
				}
			}
			else
			{
				if (is_array($this->activity[substr($value_code, 9)]))
				{
					array_walk($this->fields[$value_code], array($this, '__htmlspecialcharsbx'));
					return $this->fields[$value_code];
				}
				else
				{
					return htmlspecialcharsbx($this->fields[$value_code]);
				}
			}
		}
		else
		{
			$arReturn = array();
			foreach($value_code as $key_tmp => $value_tmp)
			{
				$arReturn[$key_tmp] = $this->getValue($value_tmp);
			}
			return $arReturn;
		}
	}

	function ParseText($text, $arUF, $arParams)
	{
		static $parser = false;
		if (CModule::IncludeModule("forum"))
		{
			if (!$parser)
				$parser = new forumTextParser(LANGUAGE_ID);

			$parser->pathToUser = $arParams["PATH_TO_USER"];
			$parser->arUserfields = $arUF;
			$textFormatted = $parser->convert(
				$text,
				array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arUF
				),
				"html"
			);
		}
		else
		{
			$parser = new logTextParser(false, $arParams["PATH_TO_SMILE"]);
			$textFormatted = $parser->convert(
				$text,
				array(),
				array(
					"HTML" => "N",
					"ALIGN" => "Y",
					"ANCHOR" => "Y", "BIU" => "Y",
					"IMG" => "Y", "QUOTE" => "Y",
					"CODE" => "Y", "FONT" => "Y",
					"LIST" => "Y", "SMILES" => "Y",
					"NL2BR" => "Y", "MULTIPLE_BR" => "N",
					"VIDEO" => "Y", "LOG_VIDEO" => "N",
					"SHORT_ANCHOR" => "Y",
					"USERFIELDS" => $arUF
				)
			);
		}

		if (
			isset($arParams["MAX_LENGTH"]) 
			&& intval($arParams["MAX_LENGTH"]) > 0
		)
		{
			$textFormatted = $parser->html_cut($textFormatted, $arParams["MAX_LENGTH"]);
		}
		return $textFormatted;
	}

	private function __htmlspecialcharsbx(&$val, $key)
	{
		if (is_array($val))
		{
			array_walk($val, array($this, '__htmlspecialcharsbx'));
		}
		else
		{
			$val = htmlspecialcharsbx($val);
		}
	}

	public static function ProcessLogEventEditPOST($arPOST, $entityTypeID, &$entityID, &$arResult)
	{
		$arEntityData = array();
		$errors = array();

		$enableTitle = isset($arPOST['ENABLE_POST_TITLE']) && strtoupper($arPOST['ENABLE_POST_TITLE']) === 'Y';
		$title = $enableTitle && isset($arPOST['POST_TITLE']) ? $arPOST['POST_TITLE'] : '';
		$message = isset($arPOST['MESSAGE']) ? htmlspecialcharsback($arPOST['MESSAGE']) : '';

		$arResult['EVENT']['MESSAGE'] = $message;
		$arResult['EVENT']['TITLE'] = $title;
		$arResult['ENABLE_TITLE'] = $enableTitle;

		$attachedFiles = array();
		$webDavFileFieldName = $arResult['WEB_DAV_FILE_FIELD_NAME'];
		if($webDavFileFieldName !== '' && isset($GLOBALS[$webDavFileFieldName]) && is_array($GLOBALS[$webDavFileFieldName]))
		{
			foreach($GLOBALS[$webDavFileFieldName] as $fileID)
			{
				if($fileID === '')
				{
					continue;
				}

				//fileID:  "888|165|16"
				$attachedFiles[] = $fileID;
			}

			if(!empty($attachedFiles) && is_array($arResult['WEB_DAV_FILE_FIELD']))
			{
				$arResult['WEB_DAV_FILE_FIELD']['VALUE'] = $attachedFiles;
			}
		}

		$allowToAll = (COption::GetOptionString('socialnetwork', 'allow_livefeed_toall', 'Y') === 'Y');
		if($allowToAll)
		{
			$arToAllRights = unserialize(COption::GetOptionString("socialnetwork", "livefeed_toall_rights", 'a:1:{i:0;s:2:"AU";}'));
			if(!$arToAllRights)
			{
				$arToAllRights = array('AU');
			}

			$arUserGroupCode = array_merge(array('AU'), CAccess::GetUserCodesArray($arResult['USER_ID']));
			if(count(array_intersect($arToAllRights, $arUserGroupCode)) <= 0)
			{
				$allowToAll = false;
			}
		}

		$arSocnetRights = array();

		if(!empty($arPOST['SPERM']))
		{
			foreach($arPOST['SPERM'] as $v => $k)
			{
				if(strlen($v) > 0 && is_array($k) && !empty($k))
				{
					foreach($k as $vv)
					{
						if(strlen($vv) > 0)
						{
							$arSocnetRights[] = $vv;
						}
					}
				}
			}
		}
		
		if (in_array('UA', $arSocnetRights) && !$allowToAll)
		{
			foreach ($arSocnetRights as $key => $value)
			{
				if ($value == 'UA')
				{
					unset($arSocnetRights[$key]);
					break;
				}
			}
		}

		foreach ($arSocnetRights as $key => $value)
		{
			if ($value == 'UA')
			{
				$arSocnetRights[] = 'AU';
				unset($arSocnetRights[$key]);
				break;
			}
		}
		$arSocnetRights = array_unique($arSocnetRights);

		$allFeedEtityTypes = CCrmLiveFeedEntity::GetAll();
		foreach ($arSocnetRights as $key => $value)
		{
			$groupCodeData = array();
			if (
				CCrmLiveFeed::TryParseGroupCode($value, $groupCodeData)
				&& in_array($groupCodeData['ENTITY_TYPE'], $allFeedEtityTypes, true)
			)
			{
				$entityType = $groupCodeData['ENTITY_TYPE'];
				$entityID = $groupCodeData['ENTITY_ID'];

				if(!CCrmLiveFeed::CheckCreatePermission($entityType, $entityID, $userPerms))
				{
					$canonicalEntityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($entityType);
					$errors[] = GetMessage(
						'CRM_SL_EVENT_EDIT_PERMISSION_DENIED',
						array('#TITLE#' => CCrmOwnerType::GetCaption($canonicalEntityTypeID, $entityID, false))
					);
				}
				else
				{
					$arEntityData[] = array('ENTITY_TYPE' => $entityType, 'ENTITY_ID' => $entityID);
				}
			}
		}

		if(!(CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0) && !empty($arEntityData))
		{
			$entityData = $arEntityData[0];
			$entityTypeID = CCrmLiveFeedEntity::ResolveEntityTypeID($entityData['ENTITY_TYPE']);
			$entityID = $entityData['ENTITY_ID'];
		}

		if(!empty($arEntityData))
		{
			$arResult['ENTITY_DATA'] = $arEntityData;
		}

		if(!(CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0))
		{
			$errors[] = GetMessage('CRM_SL_EVENT_EDIT_ENTITY_NOT_DEFINED');
		}

		if($message === '')
		{
			$errors[] = GetMessage('CRM_SL_EVENT_EDIT_EMPTY_MESSAGE');
		}

		if(empty($errors))
		{
			$fields = array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'USER_ID' => $arResult['USER_ID'],
				'TITLE' => $title,
				'MESSAGE' => $message,
				'RIGHTS' => $arSocnetRights
			);

			$parents = array();
			if(CCrmOwnerType::TryGetOwnerInfos($entityTypeID, $entityID, $parents))
			{
				$fields['PARENTS'] = &$parents;
			}
			unset($parents);

			if(!empty($attachedFiles))
			{
				$fields['WEB_DAV_FILES'] = array($webDavFileFieldName => $attachedFiles);
			}

			$messageID = CCrmLiveFeed::CreateLogMessage($fields);
			if(!(is_int($messageID) && $messageID > 0))
			{
				$errors[] = isset($fields['ERROR']) ? $fields['ERROR'] : 'UNKNOWN ERROR';
			}
			else
			{
				preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $message, $arMention);
				if (
					!empty($arMention)
					&& !empty($arMention[1])
					&& CModule::IncludeModule("im")
				)
				{
					$arMention = $arMention[1];
					$arMention = array_unique($arMention);

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => "",
						"FROM_USER_ID" => $arResult['USER_ID'],
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"NOTIFY_EVENT" => "mention"
					);

					$genderSuffix = "";
					$dbUser = CUser::GetByID($arResult['USER_ID']);

					if($arUser = $dbUser->Fetch())
					{
						switch ($arUser["PERSONAL_GENDER"])
						{
							case "M":
								$genderSuffix = "_M";
								break;
							case "F":
								$genderSuffix = "_F";
								break;
							default:
								$genderSuffix = "";
						}
					}

					$strIMMessageTitle = str_replace(Array("\r\n", "\n"), " ", (strlen($title) > 0 ? $title : $message));

					if (CModule::IncludeModule("blog"))
					{
						$strIMMessageTitle = trim(blogTextParser::killAllTags($strIMMessageTitle));
					}
					$strIMMessageTitle = TruncateText($strIMMessageTitle, 100);
					$strIMMessageTitleOut = TruncateText($strIMMessageTitle, 255);

					$strLogEntryURL = COption::GetOptionString("socialnetwork", "log_entry_page", SITE_DIR."company/personal/log/#log_id#/", SITE_ID);
					$strLogEntryURL = CComponentEngine::MakePathFromTemplate(
						$strLogEntryURL,
						array(
							"log_id" => $messageID
						)
					);

					$strLogEntryCrmURL = CComponentEngine::MakePathFromTemplate(
						SITE_DIR."crm/stream/?log_id=#log_id#",
						array(
							"log_id" => $messageID
						)
					);

					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					foreach($arMention as $val)
					{
						$val = IntVal($val);
						if (
							$val > 0 
							&& ($val != $arResult['USER_ID'])
						)
						{
							$bHasAccess = false;

							if (in_array('U'.$val, $arSocnetRights))
							{
								$url = $strLogEntryURL;
								$bHasAccess = true;
							}

							if (!$bHasAccess)
							{
								$arAccessCodes = array();
								$dbAccess = CAccess::GetUserCodes($val);
								while($arAccess = $dbAccess->Fetch())
								{
									$arAccessCodes[] = $arAccess["ACCESS_CODE"];
								}

								$arTmp = array_intersect($arAccess, $arSocnetRights);
								if (!empty($arTmp))
								{
									$url = $strLogEntryURL;
									$bHasAccess = true;
								}
							}

							if (!$bHasAccess)
							{
								$userPermissions = CCrmPerms::GetUserPermissions($val);
								foreach($arEntityData as $arEntity)
								{
									if (CCrmAuthorizationHelper::CheckReadPermission(CCrmOwnerType::ResolveName(CCrmLiveFeedEntity::ResolveEntityTypeID($arEntity['ENTITY_TYPE'])), $arEntity['ENTITY_ID'], $userPermissions))
									{
										$url = $strLogEntryCrmURL;
										$bHasAccess = true;
										break;
									}
								}
							}

							if ($bHasAccess)
							{
								$arMessageFields["TO_USER_ID"] = $val;
								$arMessageFields["NOTIFY_TAG"] = "CRM|MESSAGE_MENTION|".$messageID;
								$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("CRM_SL_EVENT_IM_MENTION_POST".$genderSuffix, Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($strIMMessageTitle)."</a>"));
								$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("CRM_SL_EVENT_IM_MENTION_POST".$genderSuffix, Array("#title#" => htmlspecialcharsbx($strIMMessageTitleOut)))." (".$serverName.$url.")";

								CIMNotify::Add($arMessageFields);
							}
						}
					}
				}

				return $messageID;
			}
		}

		return $errors;
	}

	public static function needToProcessRequest($method, $request)
	{
		return ($method == 'POST' && isset($request['save']) && $request['save'] === 'Y')
			|| ($method == 'GET' && isset($request['SONET_FILTER_MODE']) && $request['SONET_FILTER_MODE'] !== '')
			|| ($method == 'GET' && isset($request['log_filter_submit']) && $request['log_filter_submit'] === 'Y')
			|| ($method == 'GET' && isset($request['preset_filter_id']) && $request['preset_filter_id'] !== '')
			|| ($method == 'GET' && isset($request['preset_filter_top_id']) && $request['preset_filter_top_id'] !== '');
	}
}
