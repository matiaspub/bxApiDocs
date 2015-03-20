<?php
/*
 * CRM Activity
 */
IncludeModuleLangFile(__FILE__);
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
class CAllCrmActivity
{
	const CACHE_NAME = 'CRM_ACTIVITY_CACHE';
	const TABLE_ALIAS = 'A';
	const UF_ENTITY_TYPE = 'CRM_ACTIVITY';
	//const UF_WEBDAV_FIELD_NAME = 'UF_CRM_ACTIVITY_WDAV';
	const COMMUNICATION_TABLE_ALIAS = 'AC';

	private static $FIELDS = null;
	private static $FIELD_INFOS = null;
	private static $COMM_FIELD_INFOS = null;
	private static $FIELDS_CACHE = array();
	private static $COMMUNICATION_FIELDS = null;

	private static $USER_PERMISSIONS = null;
	private static $STORAGE_TYPE_ID = StorageType::Undefined;
	protected static $errors = array();
	private static $URN_REGEX = '/\[\s*(?:CRM\s*\:)\s*(?P<urn>[0-9]+\s*[-]\s*[0-9A-Z]+)\s*\]/i';
	private static $URN_BODY_REGEX = '/\[\s*(?:msg\s*\:)\s*(?P<urn>[0-9]+\s*[-]\s*[0-9A-Z]+)\s*\]/i';
	private static $URN_BODY_HTML_ENTITY_REGEX = '/\&\#91\;\s*(?:msg\s*\:)\s*(?P<urn>[0-9]+\s*[-]\s*[0-9A-Z]+)\s*\&\#93\;/i';

	private static $TASK_OPERATIONS = array();

	private static $IGNORE_CALENDAR_EVENTS = false;
	private static $CURRENT_DAY_TIME_STAMP = null;
	private static $NEXT_DAY_TIME_STAMP = null;
	private static $CLIENT_INFOS = null;
	// CRUD -->
	public static function Add(&$arFields, $checkPerms = true, $regEvent = true, $options = array())
	{
		global $DB;
		if(!is_array($options))
		{
			$options = array();
		}

		// Setup ownership data if need
		if((empty($arFields['OWNER_ID']) || empty($arFields['OWNER_TYPE_ID']))
			&& isset($arFields['BINDINGS'])
			&& is_array($arFields['BINDINGS'])
			&& !empty($arFields['BINDINGS']))
		{
			$arFields['OWNER_ID'] = $arFields['BINDINGS'][0]['OWNER_ID'];
			$arFields['OWNER_TYPE_ID'] = $arFields['BINDINGS'][0]['OWNER_TYPE_ID'];
		}

		if(!isset($arFields['STORAGE_TYPE_ID']))
		{
			$arFields['STORAGE_TYPE_ID'] = StorageType::getDefaultTypeID();
		}

		if (!self::CheckFields('ADD', $arFields, 0, null))
		{
			return false;
		}

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) ? $arFields['STORAGE_ELEMENT_IDS'] : array();
		$storageElementsSerialized = false;
		if(is_array($storageElementIDs))
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
			$storageElementsSerialized = true;
		}

		if(isset($arFields['SETTINGS']) && is_array($arFields['SETTINGS']))
		{
			$arFields['SETTINGS'] = serialize($arFields['SETTINGS']);
		}

		self::NormalizeDateTimeFields($arFields);
		$ID = $DB->Add(CCrmActivity::TABLE_NAME, $arFields, array('DESCRIPTION', 'STORAGE_ELEMENT_IDS', 'SETTINGS'));
		if(is_string($ID) && $ID !== '')
		{
			//MS SQL RETURNS STRING INSTEAD INT
			$ID = intval($ID);
		}

		if($ID === false)
		{
			self::RegisterError(array('text' => "DB connection was lost."));
			return false;
		}


		$arFields['ID'] = $ID;
		$arFields['SETTINGS'] = isset($arFields['SETTINGS']) ? unserialize($arFields['SETTINGS']) : array();

		CCrmActivity::DoSaveElementIDs($ID, $arFields['STORAGE_TYPE_ID'], $storageElementIDs);

		$arBindings = isset($arFields['BINDINGS']) && is_array($arFields['BINDINGS']) ? $arFields['BINDINGS'] : array();

		$isOwnerInBindings = false;
		$ownerID = intval($arFields['OWNER_ID']);
		$ownerTypeID = intval($arFields['OWNER_TYPE_ID']);
		foreach($arBindings as &$arBinding)
		{
			$curOwnerTypeID = isset($arBinding['OWNER_TYPE_ID']) ? intval($arBinding['OWNER_TYPE_ID']) : 0;
			$curOwnerID = isset($arBinding['OWNER_ID']) ? intval($arBinding['OWNER_ID']) : 0;

			if($curOwnerTypeID === $ownerTypeID && $curOwnerID === $ownerID)
			{
				$isOwnerInBindings = true;
				break;
			}
		}
		unset($arBinding);

		if(!$isOwnerInBindings)
		{
			$arBindings[] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		self::SaveBindings($ID, $arBindings, false, false);

		if($regEvent)
		{
			foreach($arBindings as &$arBinding)
			{
				self::RegisterAddEvent($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $arFields, false);
			}
			unset($arBinding);
		}

		// Synchronize user activity -->
		$responsibleID = isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
		if($responsibleID > 0)
		{
			foreach($arBindings as &$arBinding)
			{
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
			}
			unset($arBinding);
		}
		// <-- Synchronize user activity

		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
		$associatedEntityID = isset($arFields['ASSOCIATED_ENTITY_ID']) ? intval($arFields['ASSOCIATED_ENTITY_ID']) : 0;
		$skipAssocEntity = isset($options['SKIP_ASSOCIATED_ENTITY'])
			? (bool)$options['SKIP_ASSOCIATED_ENTITY'] : false;

		if(!$skipAssocEntity && $typeID > 0 && $associatedEntityID <= 0)
		{
			switch($typeID)
			{
				case CCrmActivityType::Call:
				case CCrmActivityType::Meeting:
				{
					$completed = isset($arFields['COMPLETED']) ? $arFields['COMPLETED'] === 'Y' : false;
					// Check for settings
					$displayCompleted = $typeID === CCrmActivityType::Call
						? CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedCalls, true)
						: CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedMeetings, true);

					if(!$completed || $displayCompleted)
					{
						$eventID = self::SaveCalendarEvent($arFields);
						if(is_int($eventID) && $eventID > 0)
						{
							$DB->Query(
								'UPDATE '.CCrmActivity::TABLE_NAME.' SET '.$DB->PrepareUpdate(CCrmActivity::TABLE_NAME, array('ASSOCIATED_ENTITY_ID' => $eventID)).' WHERE ID = '.$ID,
								false,
								'File: '.__FILE__.'<br>Line: '.__LINE__
							);
						}
					}
					break;
				}
				case CCrmActivityType::Task:
				case CCrmActivityType::Email:
					//do nothing
					break;
			}
		}

		if($storageElementsSerialized)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = $storageElementIDs;
		}

		if(is_int($ID) && $ID > 0)
		{
			if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				self::RegisterLiveFeedEvent($arFields);
				if($responsibleID > 0)
				{
					CCrmSonetSubscription::RegisterSubscription(
						CCrmOwnerType::Activity,
						$ID,
						CCrmSonetSubscriptionType::Responsibility,
						$responsibleID
					);
				}
			}

			$rsEvents = GetModuleEvents('crm', 'OnActivityAdd');
			while ($arEvent = $rsEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
			}
		}

		return $ID;
	}

	public static function Update($ID, $arFields, $checkPerms = true, $regEvent = true, $options = array())
	{
		global $DB;
		if(!is_array($options))
		{
			$options = array();
		}

		$arPrevEntity = self::GetByID($ID, $checkPerms);
		if(!$arPrevEntity)
		{
			return false; // is not exists
		}

		if(!self::CheckFields('UPDATE', $arFields, $ID, array('PREVIOUS_FIELDS' => $arPrevEntity)))
		{
			return false;
		}

		$arPrevBindings = self::GetBindings($ID);
		$arRecordBindings = array();

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) ? $arFields['STORAGE_ELEMENT_IDS'] : null;
		$storageElementsSerialized = false;
		if(is_array($storageElementIDs))
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
			$storageElementsSerialized = true;
		}
		elseif($storageElementIDs !== null)
		{
			//Skip Storage Elements Processing - Treat As Not Changed
			$storageElementIDs = null;
		}

		if(isset($arFields['STORAGE_ELEMENT_IDS']))
		{
			$arRecordBindings['STORAGE_ELEMENT_IDS'] = $arFields['STORAGE_ELEMENT_IDS'];
		}

		if(isset($arFields['SETTINGS']))
		{
			if(is_array($arFields['SETTINGS']))
			{
				$arFields['SETTINGS'] = serialize($arFields['SETTINGS']);
			}
			$arRecordBindings['SETTINGS'] = $arFields['SETTINGS'];
		}

		$arBindings = (isset($arFields['BINDINGS']) && is_array($arFields['BINDINGS'])) ? $arFields['BINDINGS'] : null;
		if(is_array($arBindings))
		{
			$bindingQty = count($arBindings);
			if($bindingQty === 1)
			{
				// Change activity ownership if only one binding defined
				$arBinding = $arBindings[0];
				$arFields['OWNER_ID'] = $arBinding['OWNER_ID'];
				$arFields['OWNER_TYPE_ID'] = $arBinding['OWNER_TYPE_ID'];
			}
			elseif($bindingQty === 0)
			{
				// Clear activity ownership if only no bindings are defined
				$arFields['OWNER_ID'] = 0;
				$arFields['OWNER_TYPE_ID'] = CCrmOwnerType::Undefined;
			}
		}

		self::NormalizeDateTimeFields($arFields);

		if(isset($arFields['ID']))
		{
			unset($arFields['ID']);
		}

		$sql = 'UPDATE '.CCrmActivity::TABLE_NAME.' SET '.$DB->PrepareUpdate(CCrmActivity::TABLE_NAME, $arFields).' WHERE ID = '.$ID;
		if(!empty($arRecordBindings))
		{
			$DB->QueryBind($sql, $arRecordBindings, false);
		}
		else
		{
			$DB->Query($sql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		}
		$arFields['SETTINGS'] = isset($arFields['SETTINGS']) ? unserialize($arFields['SETTINGS']) : array();

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		$storageTypeID = isset($arFields['STORAGE_TYPE_ID']) ? intval($arFields['STORAGE_TYPE_ID']) : StorageType::Undefined;
		if($storageTypeID === StorageType::Undefined)
		{
			$storageTypeID = isset($arPrevEntity['STORAGE_TYPE_ID']) ? intval($arPrevEntity['STORAGE_TYPE_ID']) : self::GetDefaultStorageTypeID();
		}

		if(is_array($storageElementIDs))
		{
			CCrmActivity::DoSaveElementIDs($ID, $storageTypeID, $storageElementIDs);
		}

		$arCurEntity = self::GetByID($ID, false);

		if(is_array($arBindings))
		{
			self::SaveBindings($ID, $arBindings, false, false);
			$bindibgsChanged = true;
		}
		else
		{
			$arBindings = self::GetBindings($ID);
			$bindibgsChanged = false;
		}

		// Synchronize user activity -->
		$arSyncKeys = array();
		$responsibleID = isset($arFields['RESPONSIBLE_ID'])
			? intval($arFields['RESPONSIBLE_ID'])
			: (isset($arPrevEntity['RESPONSIBLE_ID']) ? intval($arPrevEntity['RESPONSIBLE_ID']) : 0);

		foreach($arBindings as &$arBinding)
		{
			if($responsibleID > 0)
			{
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
				$arSyncKeys[] = "{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}_{$responsibleID}";
			}
			self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
			$arSyncKeys[] = "{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}";
		}
		unset($arBinding);

		$prevResponsibleID = isset($arPrevEntity['RESPONSIBLE_ID']) ? intval($arPrevEntity['RESPONSIBLE_ID']) : 0;
		if(!empty($arPrevBindings))
		{
			foreach($arPrevBindings as &$arBinding)
			{
				if($prevResponsibleID > 0 && !in_array("{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}_{$prevResponsibleID}", $arSyncKeys, true))
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $prevResponsibleID);
				}
				if(!in_array("{$arBinding['OWNER_TYPE_ID']}_{$arBinding['OWNER_ID']}", $arSyncKeys, true))
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
				}
			}
			unset($arBinding);
		}
		// <-- Synchronize user activity

		if($regEvent)
		{
			foreach($arBindings as &$arBinding)
			{
				self::RegisterUpdateEvent($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $arCurEntity, $arPrevEntity, false);
			}
			unset($arBinding);
		}

		$typeID = isset($arCurEntity['TYPE_ID']) ? intval($arCurEntity['TYPE_ID']) : CCrmActivityType::Undefined;
		$skipAssocEntity = isset($options['SKIP_ASSOCIATED_ENTITY'])
			? (bool)$options['SKIP_ASSOCIATED_ENTITY'] : false;

		if(!$skipAssocEntity && $typeID > 0)
		{
			switch($typeID)
			{
				case CCrmActivityType::Call:
				case CCrmActivityType::Meeting:
					{
						$completed = isset($arCurEntity['COMPLETED']) ? $arCurEntity['COMPLETED'] === 'Y' : false;
						// Check for settings
						$displayCompleted = $typeID === CCrmActivityType::Call
							? CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedCalls, true)
							: CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedMeetings, true);

						if(!$completed || $displayCompleted)
						{
							$arCurEntity['BINDINGS'] = $arBindings;
							$eventID = self::SaveCalendarEvent($arCurEntity);
							if(is_int($eventID) && $eventID > 0)
							{
								$DB->Query(
									'UPDATE '.CCrmActivity::TABLE_NAME.' SET '.$DB->PrepareUpdate(CCrmActivity::TABLE_NAME, array('ASSOCIATED_ENTITY_ID' => $eventID)).' WHERE ID = '.$ID,
									false,
									'File: '.__FILE__.'<br>Line: '.__LINE__
								);
							}
						}
						else
						{
							if(self::DeleteCalendarEvent($arCurEntity))
							{
								$DB->Query(
									'UPDATE '.CCrmActivity::TABLE_NAME.' SET '.$DB->PrepareUpdate(CCrmActivity::TABLE_NAME, array('ASSOCIATED_ENTITY_ID' => 0)).' WHERE ID = '.$ID,
									false,
									'File: '.__FILE__.'<br>Line: '.__LINE__
								);
							}
						}
					}
					break;
				case CCrmActivityType::Task:
					{
						self::SaveTask($arCurEntity);
						break;
					}
				case CCrmActivityType::Email:
					//do nothing
					break;
			}
		}

		$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;
		$isSonetEventRegistred = false;

		if($registerSonetEvent)
		{
			$isSonetEventSynchronized = self::SynchronizeLiveFeedEvent(
				$ID,
				array(
					'PROCESS_BINDINGS' => $bindibgsChanged,
					'BINDINGS' => $bindibgsChanged ? $arBindings : null,
					'REFRESH_DATE' => isset($arFields['COMPLETED']) && $arFields['COMPLETED'] !== $arPrevEntity['COMPLETED'],
					'START_RESPONSIBLE_ID' => $arPrevEntity['RESPONSIBLE_ID'],
					'FINAL_RESPONSIBLE_ID' => $responsibleID,
					'EDITOR_ID' => (intval($arFields["EDITOR_ID"]) > 0 ? $arFields["EDITOR_ID"] : CCrmSecurityHelper::GetCurrentUserID()),
					'TYPE_ID' => $typeID,
					'SUBJECT' => (isset($arFields['SUBJECT']) ? $arFields['SUBJECT'] : $arPrevEntity['SUBJECT'])
				)
			);

			if(!$isSonetEventSynchronized)
			{
				$itemFields = self::GetByID($ID);
				if(is_array($itemFields))
				{
					$itemFields['BINDINGS'] = $arBindings;
					$sonetEventID = self::RegisterLiveFeedEvent($itemFields);
					$isSonetEventRegistred = is_int($sonetEventID) && $sonetEventID > 0;

					if($responsibleID > 0)
					{
						CCrmSonetSubscription::RegisterSubscription(
							CCrmOwnerType::Activity,
							$ID,
							CCrmSonetSubscriptionType::Responsibility,
							$responsibleID
						);
					}
				}
			}
		}

		if(!$isSonetEventRegistred && $responsibleID !== $prevResponsibleID)
		{
			CCrmSonetSubscription::ReplaceSubscriptionByEntity(
				CCrmOwnerType::Activity,
				$ID,
				CCrmSonetSubscriptionType::Responsibility,
				$responsibleID,
				$prevResponsibleID,
				$registerSonetEvent
			);
		}

		if($storageElementsSerialized)
		{
			$arFields['STORAGE_ELEMENT_IDS'] = $storageElementIDs;
		}

		$rsEvents = GetModuleEvents('crm', 'OnActivityUpdate');
		while ($arEvent = $rsEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID, &$arFields));
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("CRM_ACTIVITY_".$ID);
		}

		return true;
	}
	public static function Delete($ID, $checkPerms = true, $regEvent = true, $options = array())
	{
		$ID = intval($ID);
		if(!is_array($options))
		{
			$options = array();
		}

		$events = GetModuleEvents('crm', 'OnBeforeActivityDelete');
		while ($event = $events->Fetch())
		{
			if (ExecuteModuleEventEx($event, array($ID)) === false)
			{
				return false;
			}
		}

		$ary = (isset($options['ACTUAL_ITEM']) && is_array($options['ACTUAL_ITEM']))
			? $options['ACTUAL_ITEM']
			: self::GetByID($ID, $checkPerms);

		if(!is_array($ary))
		{
			return false; //is not found
		}

		$arBindings = isset($options['ACTUAL_BINDINGS']) && is_array($options['ACTUAL_BINDINGS'])
			? $options['ACTUAL_BINDINGS']
			: self::GetBindings($ID);

		if(!self::InnerDelete($ID, $options))
		{
			return false;
		}

		self::UnregisterLiveFeedEvent($ID);
		CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Activity, $ID);

		// Synchronize user activity -->
		$skipUserActivitySync = isset($options['SKIP_USER_ACTIVITY_SYNC']) ? $options['SKIP_USER_ACTIVITY_SYNC'] : false;
		if(!$skipUserActivitySync)
		{
			$responsibleID = isset($ary['RESPONSIBLE_ID']) ? intval($ary['RESPONSIBLE_ID']) : 0;
			if($responsibleID > 0 && is_array($arBindings))
			{
				foreach($arBindings as &$arBinding)
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
				}
				unset($arBinding);
			}
		}
		// <-- Synchronize user activity

		if($regEvent && is_array($arBindings))
		{
			foreach($arBindings as &$arBinding)
			{
				self::RegisterRemoveEvent($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $ary, $checkPerms);
			}
			unset($arBinding);
		}

		$skipAssocEntity = isset($options['SKIP_ASSOCIATED_ENTITY']) ? (bool)$options['SKIP_ASSOCIATED_ENTITY'] : false;
		if(!$skipAssocEntity && isset($ary['TYPE_ID']) && isset($ary['ASSOCIATED_ENTITY_ID']))
		{
			switch(intval($ary['TYPE_ID']))
			{
				case CCrmActivityType::Call:
				case CCrmActivityType::Meeting:
					{
						self::DeleteCalendarEvent($ary);
					}
					break;
				case CCrmActivityType::Task:
					{
						self::DeleteTask($ary);
					}
					break;
				case CCrmActivityType::Email:
					//do nothing
					break;
			}
		}

		$rsEvents = GetModuleEvents('crm', 'OnActivityDelete');
		while ($arEvent = $rsEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array($ID));
		}

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->ClearByTag("CRM_ACTIVITY_".$ID);
		}

		return true;
	}
	// <-- CRUD
	//Service -->
	protected static function InnerDelete($ID, $options = array())
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		CCrmEntityHelper::RemoveCached(self::CACHE_NAME, $ID);

		$result = true;
		if(!(isset($options['SKIP_BINDINGS']) && $options['SKIP_BINDINGS']))
		{
			$result = self::DeleteBindings($ID);
		}

		if($result && !(isset($options['SKIP_COMMUNICATIONS']) && $options['SKIP_COMMUNICATIONS']))
		{
			$result = self::DeleteCommunications($ID);
		}

		if($result && !(isset($options['SKIP_FILES']) && $options['SKIP_FILES']))
		{
			$result = self::DeleteStorageElements($ID);

			if($result)
			{
				$result = $DB->Query(
					'DELETE FROM '.CCrmActivity::ELEMENT_TABLE_NAME.' WHERE ACTIVITY_ID = '.$ID,
					false,
					'File: '.__FILE__.'<br/>Line: '.__LINE__
				);
			}
		}

		if($result)
		{
			$result = $DB->Query('DELETE FROM '.CCrmActivity::TABLE_NAME.' WHERE ID = '.$ID, true) !== false;
		}

		return $result;
	}
	protected static function NormalizeStorageElementIDs(&$arElementIDs)
	{
		$result = array();
		foreach($arElementIDs as $elementID)
		{
			$result[] = intval($elementID);
		}

		return array_unique($result, SORT_NUMERIC);
	}
	protected static function NormalizeDateTimeFields(&$arFields)
	{
		//With format 'MM/DD/YYYY H:MI:SS TT' call MakeTimeStamp("01/01/1970 01:00 PM") will not work.;
		if(isset($arFields['START_TIME']))
		{
			$arFields['START_TIME'] = CCrmDateTimeHelper::NormalizeDateTime($arFields['START_TIME']);
		}

		if(isset($arFields['END_TIME']))
		{
			$arFields['END_TIME'] = CCrmDateTimeHelper::NormalizeDateTime($arFields['END_TIME']);
		}

		if(isset($arFields['DEADLINE']))
		{
			$arFields['DEADLINE'] = CCrmDateTimeHelper::NormalizeDateTime($arFields['DEADLINE']);
		}
	}
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'OWNER_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'OWNER_TYPE_ID' => array(
					'TYPE' => 'crm_enum_ownertype',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'TYPE_ID' => array(
					'TYPE' => 'crm_enum_activitytype',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'SUBJECT' => array('TYPE' => 'string'),
				'START_TIME' => array('TYPE' => 'datetime'),
				'END_TIME' => array('TYPE' => 'datetime'),
				'DEADLINE' => array('TYPE' => 'datetime'),
				'COMPLETED' => array('TYPE' => 'char'),
				'RESPONSIBLE_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'PRIORITY' => array('TYPE' => 'crm_enum_activitypriority'),
				'NOTIFY_TYPE' => array('TYPE' => 'crm_enum_activitynotifytype'),
				'NOTIFY_VALUE' => array('TYPE' => 'integer'),
				'DESCRIPTION' => array('TYPE' => 'string'),
				'DESCRIPTION_TYPE' => array('TYPE' => 'crm_enum_contenttype'),
				'DIRECTION' => array('TYPE' => 'crm_enum_activitydirection'),
				'LOCATION' => array('TYPE' => 'string'),
				'CREATED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'AUTHOR_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'LAST_UPDATED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'EDITOR_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SETTINGS' => array('TYPE' => 'object'),
				'ORIGIN_ID' => array('TYPE' => 'string')
			);
		}
		return self::$FIELD_INFOS;
	}
	public static function GetCommunicationFieldsInfo()
	{
		if(!self::$COMM_FIELD_INFOS)
		{
			self::$COMM_FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTIVITY_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ENTITY_ID' => array('TYPE' => 'integer'),
				'ENTITY_TYPE_ID' => array('TYPE' => 'integer'),
				'TYPE' => array('TYPE' => 'string'),
				'VALUE' => array('TYPE' => 'string'),
				'OWNER_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'OWNER_TYPE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'ENTITY_SETTINGS' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				)
			);
		}
		return self::$COMM_FIELD_INFOS;
	}
	protected  static function GetFields()
	{
		if(!isset(self::$FIELDS))
		{
			$responsibleJoin = 'LEFT JOIN b_user U ON A.RESPONSIBLE_ID = U.ID';
			//$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
			//$bindingJoin = "INNER JOIN {$bindingTableName} B ON A.ID = B.ACTIVITY_ID";

			self::$FIELDS = array(
				'ID' => array('FIELD' => 'A.ID', 'TYPE' => 'int'),
				'OWNER_ID' => array('FIELD' => 'A.OWNER_ID', 'TYPE' => 'int'),
				'OWNER_TYPE_ID' => array('FIELD' => 'A.OWNER_TYPE_ID', 'TYPE' => 'int'),
				//'OWNER_ID' => array('FIELD' => 'B.OWNER_ID', 'TYPE' => 'int', 'FROM' => $bindingJoin, 'DEFAULT' => 'N'),
				//'OWNER_TYPE_ID' => array('FIELD' => 'B.OWNER_TYPE_ID', 'TYPE' => 'int', 'FROM' => $bindingJoin, 'DEFAULT' => 'N'),
				'TYPE_ID' => array('FIELD' => 'A.TYPE_ID', 'TYPE' => 'int'),
				'PARENT_ID' => array('FIELD' => 'A.PARENT_ID', 'TYPE' => 'int'),
				'ASSOCIATED_ENTITY_ID' => array('FIELD' => 'A.ASSOCIATED_ENTITY_ID', 'TYPE' => 'int'),
				'URN' => array('FIELD' => 'A.URN', 'TYPE' => 'string'),
				'SUBJECT' => array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string'),
				'CREATED' => array('FIELD' => 'A.CREATED', 'TYPE' => 'datetime'),
				'LAST_UPDATED' => array('FIELD' => 'A.LAST_UPDATED', 'TYPE' => 'datetime'),
				'START_TIME' => array('FIELD' => 'A.START_TIME', 'TYPE' => 'datetime'),
				'END_TIME' => array('FIELD' => 'A.END_TIME', 'TYPE' => 'datetime'),
				'DEADLINE' => array('FIELD' => 'A.DEADLINE', 'TYPE' => 'datetime'),
				'COMPLETED' => array('FIELD' => 'A.COMPLETED', 'TYPE' => 'char'),
				'RESPONSIBLE_ID' => array('FIELD' => 'A.RESPONSIBLE_ID', 'TYPE' => 'int'),
				'RESPONSIBLE_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'RESPONSIBLE_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $responsibleJoin),
				'PRIORITY' => array('FIELD' => 'A.PRIORITY', 'TYPE' => 'int'),
				'NOTIFY_TYPE' => array('FIELD' => 'A.NOTIFY_TYPE', 'TYPE' => 'int'),
				'NOTIFY_VALUE' => array('FIELD' => 'A.NOTIFY_VALUE', 'TYPE' => 'int'),
				'DESCRIPTION' => array('FIELD' => 'A.DESCRIPTION', 'TYPE' => 'string'),
				'DESCRIPTION_TYPE' => array('FIELD' => 'A.DESCRIPTION_TYPE', 'TYPE' => 'int'),
				'DIRECTION' => array('FIELD' => 'A.DIRECTION', 'TYPE' => 'int'),
				'LOCATION' => array('FIELD' => 'A.LOCATION', 'TYPE' => 'string'),
				'STORAGE_TYPE_ID' => array('FIELD' => 'A.STORAGE_TYPE_ID', 'TYPE' => 'int'),
				'STORAGE_ELEMENT_IDS' => array('FIELD' => 'A.STORAGE_ELEMENT_IDS', 'TYPE' => 'string'),
				'SETTINGS' => array('FIELD' => 'A.SETTINGS', 'TYPE' => 'string'),
				'ORIGIN_ID' => array('FIELD' => 'A.ORIGIN_ID', 'TYPE' => 'string'),
				'AUTHOR_ID' => array('FIELD' => 'A.AUTHOR_ID', 'TYPE' => 'int'),
				'EDITOR_ID' => array('FIELD' => 'A.EDITOR_ID', 'TYPE' => 'int')
			);
		}

		$arFields = self::$FIELDS;
		CCrmActivity::CreateLogicalField('TYPE_NAME', $arFields);
		return $arFields;
	}
	public static function HandleStorageElementDeletion($storageTypeID, $elementID)
	{
		global $DB;

		$storageTypeID = (int)$storageTypeID;
		$elementID = (int)$elementID;

		$dbResult = $DB->Query(
			'SELECT ACTIVITY_ID FROM '.CCrmActivity::ELEMENT_TABLE_NAME.' WHERE STORAGE_TYPE_ID = '.$storageTypeID.' AND ELEMENT_ID = '.$elementID,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		while($arResult = $dbResult->Fetch())
		{
			$entityID = isset($arResult['ACTIVITY_ID']) ? (int)$arResult['ACTIVITY_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			$dbEntity = self::GetList(
				array(),
				array('ID' => $entityID),
				false,
				false,
				array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS')
			);

			$arEntity = $dbEntity->Fetch();
			if(!is_array($arEntity))
			{
				continue;
			}

			$arEntity['STORAGE_TYPE_ID'] = isset($arEntity['STORAGE_TYPE_ID'])
				? (int)$arEntity['STORAGE_TYPE_ID'] : $storageTypeID;
			self::PrepareStorageElementIDs($arEntity);
			if(!empty($arEntity['STORAGE_ELEMENT_IDS']))
			{
				$arEntity['STORAGE_ELEMENT_IDS'] = array_diff($arEntity['STORAGE_ELEMENT_IDS'], array($elementID));
			}

			self::Update($entityID, $arEntity, false, true);
		}
	}
	//Check fields before ADD and UPDATE.
	private static function CheckFields($action, &$fields, $ID, $params = null)
	{
		global $DB;
		self::ClearErrors();

		if(!(is_array($fields) && count($fields) > 0))
		{
			self::RegisterError(array('text' => 'Fields is not specified.'));
			return false;
		}

		if($action == 'ADD')
		{
			// Validation
			if (!isset($fields['OWNER_ID']))
			{
				self::RegisterError(array('text' => 'OWNER_ID is not assigned.'));
			}

			if (!isset($fields['OWNER_TYPE_ID']))
			{
				self::RegisterError(array('text' => 'OWNER_TYPE_ID is not assigned.'));
			}

			if (!isset($fields['TYPE_ID']))
			{
				self::RegisterError(array('text' => 'TYPE_ID is not assigned.'));
			}
			elseif(!CCrmActivityType::IsDefined($fields['TYPE_ID']))
			{
				self::RegisterError(array('text' => 'TYPE_ID is not supported.'));
			}

			if (!isset($fields['SUBJECT']))
			{
				self::RegisterError(array('text' => 'SUBJECT is not assigned.'));
			}

//			if (!isset($fields['START_TIME'])) //is allowed for tasks
//			{
//				self::RegisterError(array('text' => 'START_TIME is not assigned.'));
//			}

			if (!isset($fields['RESPONSIBLE_ID']))
			{
				self::RegisterError(array('text' => 'RESPONSIBLE_ID is not assigned.'));
			}

			if (!isset($fields['NOTIFY_TYPE']))
			{
				$fields['NOTIFY_TYPE'] = CCrmActivityNotifyType::None;
			}

			if ($fields['NOTIFY_TYPE'] == CCrmActivityNotifyType::None)
			{
				$fields['NOTIFY_VALUE'] = 0;
			}
			elseif (!isset($fields['NOTIFY_VALUE']))
			{
				self::RegisterError(array('text' => 'NOTIFY_VALUE is not assigned.'));
			}

			if(isset($fields['COMPLETED']))
			{
				$completed = strtoupper(strval($fields['COMPLETED']));
				if(!($completed == 'Y' || $completed == 'N'))
				{
					$completed = intval($fields['COMPLETED']) > 0 ? 'Y' : 'N';
				}
				$fields['COMPLETED'] = $completed;
			}
			else
			{
				$fields['COMPLETED'] = 'N';
			}

			if (isset($fields['CREATED']))
			{
				unset($fields['CREATED']);
			}
			if (isset($fields['LAST_UPDATED']))
			{
				unset($fields['LAST_UPDATED']);
			}
			$fields['~CREATED'] = $fields['~LAST_UPDATED'] = $DB->CurrentTimeFunction();

			if(!isset($fields['AUTHOR_ID']))
			{
				$currentUserId = CCrmPerms::GetCurrentUserID();
				$fields['AUTHOR_ID'] = $currentUserId > 0 ? $currentUserId : $fields['RESPONSIBLE_ID'];
			}

			$fields['EDITOR_ID'] = $fields['AUTHOR_ID'];

			if (!isset($fields['END_TIME']) && isset($fields['START_TIME']))
			{
				$fields['END_TIME'] = $fields['START_TIME'];
			}
			elseif (!isset($fields['START_TIME']) && isset($fields['END_TIME']))
			{
				$fields['START_TIME'] = $fields['END_TIME'];
			}

			//DEADLINE -->
			if (isset($fields['DEADLINE']))
			{
				unset($fields['DEADLINE']);
			}
			$typeID = intval($fields['TYPE_ID']);
			if ($typeID === CCrmActivityType::Task && isset($fields['END_TIME']))
			{
				$fields['DEADLINE'] = $fields['END_TIME'];
			}
			elseif ($typeID !== CCrmActivityType::Task && isset($fields['START_TIME']))
			{
				$fields['DEADLINE'] = $fields['START_TIME'];
			}

			if(!isset($fields['DEADLINE']))
			{
				$fields['~DEADLINE'] = CCrmDateTimeHelper::GetMaxDatabaseDate();
			}
			//<-- DEADLINE

			if (!isset($fields['ASSOCIATED_ENTITY_ID']))
			{
				$fields['ASSOCIATED_ENTITY_ID'] = 0;
			}

			if (!isset($fields['PRIORITY']))
			{
				$fields['PRIORITY'] = CCrmActivityPriority::Low;
			}

			if (!isset($fields['DIRECTION']))
			{
				$fields['DIRECTION'] = CCrmActivityDirection::Undefined;
			}

			if (!isset($fields['DESCRIPTION_TYPE']))
			{
				$fields['DESCRIPTION_TYPE'] = CCrmContentType::PlainText;
			}

			if(!isset($arFields['STORAGE_TYPE_ID']))
			{
				$arFields['STORAGE_TYPE_ID'] = self::GetDefaultStorageTypeID();
			}

			if(!isset($arFields['PARENT_ID']))
			{
				$arFields['PARENT_ID'] = 0;
			}
		}
		else//if($action == 'UPDATE')
		{
			$prevFields = is_array($params) && isset($params['PREVIOUS_FIELDS']) && is_array($params['PREVIOUS_FIELDS'])
				? $params['PREVIOUS_FIELDS'] : null;

			if(!is_array($prevFields) && !self::Exists($ID, false))
			{
				self::RegisterError(array('text' => "Could not find CrmActivity(ID = $ID)"));
			}

			if(isset($fields['COMPLETED']))
			{
				$completed = strtoupper(strval($fields['COMPLETED']));
				if(!($completed == 'Y' || $completed == 'N'))
				{
					$completed = intval($fields['COMPLETED']) > 0 ? 'Y' : 'N';
				}
				$fields['COMPLETED'] = $completed;
			}

			// Default settings
			if (isset($fields['CREATED']))
			{
				unset($fields['CREATED']);
			}
			if (isset($fields['LAST_UPDATED']))
			{
				unset($fields['LAST_UPDATED']);
			}
			$fields['~LAST_UPDATED'] = $DB->CurrentTimeFunction();

			if(!isset($fields['EDITOR_ID']))
			{
				$userID = isset($fields['AUTHOR_ID']) ? $fields['AUTHOR_ID'] : 0;
				if($userID <= 0)
				{
					$userID = CCrmPerms::GetCurrentUserID();
				}
				$fields['EDITOR_ID'] = $userID > 0 ? $userID : $fields['RESPONSIBLE_ID'];
			}
			unset($fields['AUTHOR_ID']);

			// TYPE_ID -->
			if(isset($fields['TYPE_ID']))
			{
				unset($fields['TYPE_ID']);
			}
			// <-- TYPE_ID

			//DEADLINE -->
			if (isset($fields['DEADLINE']))
			{
				unset($fields['DEADLINE']);
			}

			$typeID = isset($prevFields['TYPE_ID']) ? intval($prevFields['TYPE_ID']) : CCrmActivityType::Undefined;
			if ($typeID === CCrmActivityType::Task && isset($fields['END_TIME']))
			{
				$fields['DEADLINE'] = $fields['END_TIME'];
			}
			elseif ($typeID !== CCrmActivityType::Task && isset($fields['START_TIME']))
			{
				$fields['DEADLINE'] = $fields['START_TIME'];
			}
			//<-- DEADLINE
		}

		return self::GetErrorCount() == 0;
	}
	public static function DeleteBindings($activityID)
	{
		$activityID = intval($activityID);
		if($activityID <= 0)
		{
			return false;
		}

		global $DB;

		$DB->Query(
			'DELETE FROM '.CCrmActivity::BINDING_TABLE_NAME.' WHERE ACTIVITY_ID = '.$activityID,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		return true;
	}
	public static function DeleteCommunications($activityID)
	{
		$activityID = intval($activityID);
		if($activityID <= 0)
		{
			return false;
		}

		global $DB;
		$commTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;

		$DB->Query(
			"DELETE FROM {$commTableName} WHERE ACTIVITY_ID = {$activityID}",
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		return true;
	}
	protected static function DeleteStorageElements($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		$dbRes = self::GetList(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS')
		);

		$arRes = $dbRes->Fetch();
		if(!is_array($arRes))
		{
			self::RegisterError(array('text' => "Could not find activity with ID '{$ID}'."));
			return false;
		}

		$storageTypeID = isset($arRes['STORAGE_TYPE_ID'])
			? intval($arRes['STORAGE_TYPE_ID']) : StorageType::Undefined;

		if($storageTypeID === StorageType::File)
		{
			self::PrepareStorageElementIDs($arRes);
			$arFileIDs = isset($arRes['STORAGE_ELEMENT_IDS']) ? $arRes['STORAGE_ELEMENT_IDS'] : array();
			foreach($arFileIDs as $fileID)
			{
				CFile::Delete($fileID);
			}
		}

		return true;
	}
	protected static function RegisterError($arMsg)
	{
		if(is_array($arMsg) && isset($arMsg['text']))
		{
			self::$errors[] = $arMsg['text'];
			$GLOBALS['APPLICATION']->ThrowException(new CAdminException(array($arMsg)));
		}
	}
	private static function ClearErrors()
	{
		self::$errors = array();
	}
	// <-- Service
	// Contract -->
	public static function GetByID($ID, $checkPerms = true)
	{
		$ID = intval($ID);

		if($ID <= 0)
		{
			return null;
		}

		$res = CCrmEntityHelper::GetCached(self::CACHE_NAME, $ID);
		if (is_array($res))
		{
			return $res;
		}

		$filter = array('ID' => $ID);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = self::GetList(array(), $filter);

		if(is_array($res = $dbRes->Fetch()))
		{
			CCrmEntityHelper::SetCached(self::CACHE_NAME, $ID, $res);
		}

		return $res;
	}
	public static function GetByOriginID($originID, $checkPerms = true)
	{
		$originID = strval($originID);
		if($originID === '')
		{
			return false;
		}

		$filter = array('ORIGIN_ID' => $originID);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}
		$dbRes = self::GetList(array(), $filter);
		return is_object($dbRes) ? $dbRes->Fetch() : false;
	}
	public static function GetIDByOrigin($originID)
	{
		$originID = strval($originID);
		if($originID === '')
		{
			return 0;
		}

		$dbRes = self::GetList(array(), array('ORIGIN_ID' => $originID, 'CHECK_PERMISSIONS'=> 'N'), false, false, array('ID'));
		$res = is_object($dbRes) ? $dbRes->Fetch() : null;
		return is_array($res) ? intval($res['ID']) : 0;
	}
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			CCrmActivity::DB_TYPE,
			CCrmActivity::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(),
			'',
			'',
			array('CAllCrmActivity', 'BuildPermSql'),
			array('CAllCrmActivity', '__AfterPrepareSql')
		);

		if(!is_array($arSelectFields))
		{
			$arSelectFields = array();
		}

		$result = $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
		return (is_object($result) && is_subclass_of($result, 'CAllDBResult'))
			? new CCrmActivityDbResult($result, $arSelectFields)
			: $result;
	}
	public static function GetCount($arFilter)
	{
		$result = self::GetList(array(), $arFilter, array(), false, array());
		return is_int($result) ? $result : 0;
	}
	static public function BuildPermSql($aliasPrefix = 'A', $permType = 'READ', $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$userPermissions = isset($arOptions['PERMS']) ? $arOptions['PERMS'] : null;
		$userID = ($userPermissions !== null && is_object($userPermissions)) ? $userPermissions->GetUserID() : 0;
		if (CCrmPerms::IsAdmin($userID))
		{
			return '';
		}

		if(!CCrmPerms::IsAccessEnabled($userPermissions))
		{
			// User does not have permissions at all.
			return false;
		}

		$entitiesSql = array();
		$permOptions = array_merge(array('IDENTITY_COLUMN' => 'OWNER_ID'), $arOptions);
		$entitiesSql[strval(CCrmOwnerType::Lead)] = CCrmLead::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[strval(CCrmOwnerType::Deal)] = CCrmDeal::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[strval(CCrmOwnerType::Contact)] = CCrmContact::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[strval(CCrmOwnerType::Company)] = CCrmCompany::BuildPermSql($aliasPrefix, $permType, $permOptions);
		$entitiesSql[strval(CCrmOwnerType::Invoice)] = CCrmInvoice::BuildPermSql($aliasPrefix, $permType, $permOptions);

		foreach($entitiesSql as $entityTypeID => $entitySql)
		{
			if(!is_string($entitySql))
			{
				//If $entityPermSql is not string - acces denied. Clear permission SQL and related records will be ignored.
				unset($entitiesSql[$entityTypeID]);
				continue;
			}

			if($entitySql !== '')
			{
				$entitiesSql[$entityTypeID] = '('.$aliasPrefix.'.OWNER_TYPE_ID = '.$entityTypeID.' AND ('.$entitySql.') )';
			}
			else
			{
				// No permissions check - fetch all related records
				$entitiesSql[$entityTypeID] = '('.$aliasPrefix.'.OWNER_TYPE_ID = '.$entityTypeID.')';
			}
		}

		//If $entitiesSql is empty - user does not have permissions at all.
		if(empty($entitiesSql))
		{
			return false;
		}

		$userID = CCrmSecurityHelper::GetCurrentUserID();
		if($userID > 0)
		{
			//Allow responsible user to view activity without permissions check.
			return $aliasPrefix.'.RESPONSIBLE_ID = '.$userID.' OR '.implode(' OR ', $entitiesSql);
		}
		else
		{
			return implode(' OR ', $entitiesSql);
		}
	}
	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		$sql = isset($arFilter['BINDINGS'])
			? CCrmActivity::PrepareBindingsFilterSql(
				$arFilter['BINDINGS'],
				$sender->GetTableAlias())
			: '';

		return $sql !== '' ? array('FROM' => $sql) : false;
	}
	protected static function PrepareAssociationsSave(&$arNew, &$arOld, &$arAdd, &$arDelete)
	{
		foreach($arNew as $arNewItem)
		{
			$ID = isset($arNewItem['ID']) ? intval($arNewItem['ID']) : 0;
			if($ID <= 0)
			{
				$arAdd[] = $arNewItem;
				continue;
			}
		}

		foreach($arOld as $arOldItem)
		{
			$oldID = intval($arOldItem['ID']);
			$found = false;
			foreach($arNew as $arNewItem)
			{
				if((isset($arNewItem['ID']) ? intval($arNewItem['ID']) : 0) === $oldID)
				{
					$found = true;
					break;
				}
			}

			if(!$found)
			{
				$arDelete[] = $arOldItem;
			}
		}

	}
	public static function SaveBindings($ID, $arBindings, $registerEvents = true, $checkPerms = true)
	{
		foreach($arBindings as &$arBinding)
		{
			$arBinding['ACTIVITY_ID'] = $ID;
		}
		unset($arBinding);

		CCrmActivity::DoSaveBindings($ID, $arBindings);
	}
	public static function GetBindings($ID)
	{
		global $DB;

		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		$dbRes = $DB->Query(
			'SELECT ID, OWNER_ID, OWNER_TYPE_ID FROM '.CCrmActivity::BINDING_TABLE_NAME.' WHERE ACTIVITY_ID = '.$DB->ForSql($ID),
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = $arRes;
		}
		return $result;
	}
	public static function GetBoundIDs($ownerTypeID, $ownerID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;

		$dbRes = $DB->Query(
			"SELECT ACTIVITY_ID FROM {$bindingTableName} WHERE OWNER_ID = {$ownerID} AND OWNER_TYPE_ID = {$ownerTypeID}",
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = intval($arRes['ACTIVITY_ID']);
		}
		return $result;
	}

	public static function Rebind($ownerTypeID, $oldOwnerID, $newOwnerID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$oldOwnerID = intval($oldOwnerID);
		$newOwnerID = intval($newOwnerID);

		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		$communicationTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;

		$sql= "SELECT ID FROM ".CCrmActivity::BINDING_TABLE_NAME." WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}";
		CSqlUtil::PrepareSelectTop($sql, 1, CCrmActivity::DB_TYPE);
		$dbResult = $DB->Query($sql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if(!(is_object($dbResult) && is_array($dbResult->Fetch())))
		{
			return;
		}

		$responsibleIDs = array();
		$sql =  "SELECT DISTINCT A.RESPONSIBLE_ID FROM {$bindingTableName} B INNER JOIN {$tableName} A ON A.ID = B.ACTIVITY_ID AND B.OWNER_TYPE_ID = {$ownerTypeID} AND B.OWNER_ID = {$oldOwnerID}";
		$dbResult = $DB->Query($sql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$responsibleIDs[] = (int)$fields['RESPONSIBLE_ID'];
			}
		}

		$comm = array('ENTITY_ID'=> $newOwnerID, 'ENTITY_TYPE_ID' => $ownerTypeID);
		self::PrepareCommunicationSettings($comm);
		$entityCommSettings = isset($comm['ENTITY_SETTINGS']) ? $DB->ForSql(serialize($comm['ENTITY_SETTINGS'])) : '';

		$DB->Query(
			"UPDATE {$communicationTableName} SET ENTITY_ID = {$newOwnerID}, ENTITY_SETTINGS = '{$entityCommSettings}' WHERE ENTITY_TYPE_ID = {$ownerTypeID} AND ENTITY_ID = {$oldOwnerID}",
			false,
			'File: '.__FILE__.'<br>Line: '.__LINE__
		);

		$DB->Query(
			"UPDATE {$communicationTableName} SET OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}",
			false,
			'File: '.__FILE__.'<br>Line: '.__LINE__
		);

		$DB->Query(
			"UPDATE {$bindingTableName} SET OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}",
			false,
			'File: '.__FILE__.'<br>Line: '.__LINE__
		);

		$DB->Query(
			"UPDATE {$tableName} SET OWNER_ID = {$newOwnerID} WHERE OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$oldOwnerID}",
			false,
			'File: '.__FILE__.'<br>Line: '.__LINE__
		);

		if(!empty($responsibleIDs))
		{
			foreach($responsibleIDs as $responsibleID)
			{
				self::SynchronizeUserActivity($ownerTypeID, $oldOwnerID, $responsibleID);
				self::SynchronizeUserActivity($ownerTypeID, $newOwnerID, $responsibleID);
			}
		}
		self::SynchronizeUserActivity($ownerTypeID, $oldOwnerID, 0);
		self::SynchronizeUserActivity($ownerTypeID, $newOwnerID, 0);
	}

	private static function PrepareCommunicationSettings(&$arComm, $arFields = null)
	{
		$commEntityID = isset($arComm['ENTITY_ID']) ? intval($arComm['ENTITY_ID']) : 0;
		$commEntityTypeID = isset($arComm['ENTITY_TYPE_ID']) ? intval($arComm['ENTITY_TYPE_ID']) : 0;

		if($commEntityID > 0 && $commEntityTypeID > 0)
		{
			if($commEntityTypeID === CCrmOwnerType::Lead)
			{
				$arLead = is_array($arFields) ? $arFields : CCrmLead::GetByID($commEntityID, false);
				if(!is_array($arLead))
				{
					$arComm['ENTITY_SETTINGS'] = array();
					return false;
				}

				$arComm['ENTITY_SETTINGS'] =
					array(
						'NAME' => isset($arLead['NAME']) ? $arLead['NAME'] : '',
						'SECOND_NAME' => isset($arLead['SECOND_NAME']) ? $arLead['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arLead['LAST_NAME']) ? $arLead['LAST_NAME'] : '',
						'LEAD_TITLE' => isset($arLead['TITLE']) ? $arLead['TITLE'] : ''
					);
				return true;
			}
			elseif($commEntityTypeID === CCrmOwnerType::Contact)
			{
				$arContact = is_array($arFields) ? $arFields : CCrmContact::GetByID($commEntityID, false);
				if(!is_array($arContact))
				{
					$arComm['ENTITY_SETTINGS'] = array();
					return false;
				}

				$arComm['ENTITY_SETTINGS'] = array(
					'NAME' => isset($arContact['NAME']) ? $arContact['NAME'] : '',
					'SECOND_NAME' => isset($arContact['SECOND_NAME']) ? $arContact['SECOND_NAME'] : '',
					'LAST_NAME' => isset($arContact['LAST_NAME']) ? $arContact['LAST_NAME'] : ''
				);

				$arCompany = isset($arContact['COMPANY_ID']) ? CCrmCompany::GetByID($arContact['COMPANY_ID'], false) : null;
				if($arCompany && isset($arCompany['TITLE']))
				{
					$arComm['ENTITY_SETTINGS']['COMPANY_TITLE'] = $arCompany['TITLE'];
				}
				return true;
			}
			elseif($commEntityTypeID === CCrmOwnerType::Company)
			{
				$arCompany = is_array($arFields) ? $arFields : CCrmCompany::GetByID($commEntityID, false);
				if(!is_array($arCompany))
				{
					$arComm['ENTITY_SETTINGS'] = array();
					return false;
				}
				$arComm['ENTITY_SETTINGS'] = array('COMPANY_TITLE' => isset($arCompany['TITLE']) ? $arCompany['TITLE'] : '');
				return true;
			}
		}

		$arComm['ENTITY_SETTINGS'] = array();
		return false;
	}
	public static function SaveCommunications($ID, $arComms, $arFields = array(), $registerEvents = true, $checkPerms = true)
	{
		if(empty($arFields))
		{
			$arFields = self::GetByID($ID, false);
		}

		$ownerID = isset($arFields['OWNER_ID']) ? $arFields['OWNER_ID'] : 0;
		$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? $arFields['OWNER_TYPE_ID'] : 0;
		foreach($arComms as &$arComm)
		{
			self::PrepareCommunicationSettings($arComm);
			$arComm['ENTITY_SETTINGS'] = serialize($arComm['ENTITY_SETTINGS']);
			$arComm['ACTIVITY_ID'] = $ID;
			$arComm['OWNER_ID'] = $ownerID;
			$arComm['OWNER_TYPE_ID'] = $ownerTypeID;
		}
		unset($arComm);

		CCrmActivity::DoSaveCommunications($ID, $arComms, $arFields, $registerEvents, $checkPerms);
	}

	public static function GetCommunications($activityID, $top = 0)
	{
		$activityID = intval($activityID);
		if($activityID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		global $DB;
		$commTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;
		$sql = "SELECT ID, TYPE, VALUE, ENTITY_ID, ENTITY_TYPE_ID, ENTITY_SETTINGS FROM {$commTableName} WHERE ACTIVITY_ID = {$activityID} ORDER BY ID ASC";
		$top = intval($top);
		if($top > 0)
		{
			CSqlUtil::PrepareSelectTop($sql, $top, CCrmActivity::DB_TYPE);
		}

		$dbRes = $DB->Query($sql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$arRes['ENTITY_SETTINGS'] = isset($arRes['ENTITY_SETTINGS']) && $arRes['ENTITY_SETTINGS'] !== '' ? unserialize($arRes['ENTITY_SETTINGS']) : array();
			$result[] = $arRes;
		}
		return $result;
	}
	public static function PrepareClientInfos($IDs, $arOptions = null)
	{
		$nameTemplate = is_array($arOptions) && isset($arOptions['NAME_TEMPLATE'])
			&& is_string($arOptions['NAME_TEMPLATE']) && $arOptions['NAME_TEMPLATE'] !== ''
			? $arOptions['NAME_TEMPLATE'] : \Bitrix\Crm\Format\PersonNameFormatter::getFormat();

		$result = array();
		if(!is_array(self::$CLIENT_INFOS) || empty(self::$CLIENT_INFOS))
		{
			$selectIDs = $IDs;
		}
		else
		{
			$selectIDs = array();
			foreach($IDs as $ID)
			{
				if(!isset(self::$CLIENT_INFOS[$ID]))
				{
					$selectIDs[] = $ID;
				}
				else
				{
					$info = self::$CLIENT_INFOS[$ID];
					if(isset($info['NAME_DATA']) && $nameTemplate !== $info['NAME_DATA']['NAME_TEMPLATE'])
					{
						$nameData = $info['NAME_DATA'];
						$info['TITLE'] = CUser::FormatName(
							$nameTemplate,
							array(
								'LOGIN' => '',
								'NAME' => isset($nameData['NAME']) ? $nameData['NAME'] : '',
								'LAST_NAME' => isset($nameData['LAST_NAME']) ? $nameData['LAST_NAME'] : '',
								'SECOND_NAME' => isset($nameData['SECOND_NAME']) ? $nameData['SECOND_NAME'] : ''
							),
							false, false
						);
					}
					$result[$ID] = $info;
				}
			}
		}

		if(!empty($selectIDs))
		{
			global $DB;
			$condition = implode(',', $selectIDs);
			$dbResult = $DB->Query("SELECT A.ID ACTIVITY_ID, A.OWNER_TYPE_ID, A.OWNER_ID, C3.ENTITY_ID, C3.ENTITY_TYPE_ID, C3.ENTITY_SETTINGS
				FROM b_crm_act A LEFT OUTER JOIN(
					SELECT C2.ID, C2.ACTIVITY_ID, C2.ENTITY_ID, C2.ENTITY_TYPE_ID, C2.ENTITY_SETTINGS
						FROM (SELECT ACTIVITY_ID, MIN(ID) ID FROM b_crm_act_comm WHERE ACTIVITY_ID IN({$condition}) GROUP BY ACTIVITY_ID) C1
							INNER JOIN b_crm_act_comm C2 ON C1.ID = C2.ID) C3 ON C3.ACTIVITY_ID = A.ID
				WHERE A.ID IN({$condition})");

			if(is_object($dbResult))
			{
				if(self::$CLIENT_INFOS === null)
				{
					self::$CLIENT_INFOS = array();
				}

				while($comm = $dbResult->Fetch())
				{
					$ID = intval($comm['ACTIVITY_ID']);
					$entityID = isset($comm['ENTITY_ID']) ? intval($comm['ENTITY_ID']) : 0;
					$entityTypeID = isset($comm['ENTITY_TYPE_ID']) ? intval($comm['ENTITY_TYPE_ID']) : 0;

					if($entityID <= 0 || $entityTypeID <= 0)
					{
						$entityID = isset($comm['OWNER_ID']) ? intval($comm['OWNER_ID']) : 0;
						$entityTypeID = isset($comm['OWNER_TYPE_ID']) ? intval($comm['OWNER_TYPE_ID']) : 0;
					}

					if($entityID <= 0 || $entityTypeID <= 0 || $entityTypeID === CCrmOwnerType::Deal)
					{
						continue;
					}

					$info = array(
						'ENTITY_ID' => $entityID,
						'ENTITY_TYPE_ID' => $entityTypeID,
						'TITLE' => '',
						'SHOW_URL' => CCrmOwnerType::GetShowUrl($entityTypeID, $entityID, false)
					);

					$settings = isset($comm['ENTITY_SETTINGS']) ? unserialize($comm['ENTITY_SETTINGS']) : array();
					if(empty($settings))
					{
						$customComm = array('ENTITY_ID' => $entityID, 'ENTITY_TYPE_ID' => $entityTypeID);
						self::PrepareCommunicationSettings($customComm);
						if(isset($customComm['ENTITY_SETTINGS']))
						{
							$settings = $customComm['ENTITY_SETTINGS'];
						}
					}

					if($entityTypeID === CCrmOwnerType::Lead)
					{
						$info['TITLE'] = isset($settings['LEAD_TITLE']) ? $settings['LEAD_TITLE'] : '';
					}
					elseif($entityTypeID === CCrmOwnerType::Company)
					{
						$info['TITLE'] = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
					}
					elseif($entityTypeID === CCrmOwnerType::Contact)
					{
						$info['TITLE'] = CUser::FormatName(
							$nameTemplate,
							array(
								'LOGIN' => '',
								'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
								'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
								'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : ''
							),
							false, false
						);

						$info['NAME_DATA'] = array(
							'NAME_TEMPLATE' => $nameTemplate,
							'NAME' => isset($settings['NAME']) ? $settings['NAME'] : '',
							'LAST_NAME' => isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '',
							'SECOND_NAME' => isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : ''
						);
					}

					$result[$ID] = self::$CLIENT_INFOS[$ID] = $info;
				}
			}
		}
		return $result;
	}

	protected static function GetCommunicationFields()
	{
		if(!isset(self::$COMMUNICATION_FIELDS))
		{
			self::$COMMUNICATION_FIELDS = array(
				'ID' => array('FIELD' => 'AC.ID', 'TYPE' => 'int'),
				'ACTIVITY_ID' => array('FIELD' => 'AC.ACTIVITY_ID', 'TYPE' => 'int'),
				'OWNER_ID' => array('FIELD' => 'AC.OWNER_ID', 'TYPE' => 'int'),
				'OWNER_TYPE_ID' => array('FIELD' => 'AC.OWNER_TYPE_ID', 'TYPE' => 'int'),
				'TYPE' => array('FIELD' => 'AC.TYPE', 'TYPE' => 'string'),
				'VALUE' => array('FIELD' => 'AC.VALUE', 'TYPE' => 'string'),
				'ENTITY_ID' => array('FIELD' => 'AC.ENTITY_ID', 'TYPE' => 'int'),
				'ENTITY_TYPE_ID' => array('FIELD' => 'AC.ENTITY_TYPE_ID', 'TYPE' => 'int'),
				'ENTITY_SETTINGS' => array('FIELD' => 'AC.ENTITY_SETTINGS', 'TYPE' => 'string'),
			);
		}

		return self::$COMMUNICATION_FIELDS;
	}

	public static function GetCommunicationList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			CCrmActivity::DB_TYPE,
			CCrmActivity::COMMUNICATION_TABLE_NAME,
			self::COMMUNICATION_TABLE_ALIAS,
			self::GetCommunicationFields(),
			'',
			'',
			array(),
			array()
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function PrepareCommunications($entityType, $entityID, $communicationType)
	{
		$entityType =  strtoupper(strval($entityType));
		$entityID = intval($entityID);
		$communicationType = strtoupper($communicationType);
		if($communicationType === '')
		{
			$communicationType = 'PHONE';
		}

		$dbResFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $entityType, 'ELEMENT_ID' => $entityID, 'TYPE_ID' =>  $communicationType)
		);

		$result = array();
		while($arField = $dbResFields->Fetch())
		{
			if(empty($arField['VALUE']))
			{
				continue;
			}

			$result[] = array(
				'ENTITY_ID' => $entityID,
				'ENTITY_TYPE' => $entityType,
				'TYPE' => $communicationType,
				'VALUE' => $arField['VALUE'],
				'VALUE_TYPE' => $arField['VALUE_TYPE']
			);
		}

		return $result;
	}
	public static function GetCommunicationTitle(&$arComm)
	{
		self::PrepareCommunicationInfo($arComm);
		return isset($arComm['TITLE']) ? $arComm['TITLE'] : '';
	}
	public static function PrepareCommunicationInfo(&$arComm, $arFields = null)
	{
		if(!isset($arComm['ENTITY_SETTINGS']))
		{
			if(!self::PrepareCommunicationSettings($arComm, $arFields))
			{
				$arComm['TITLE'] = '';
				$arComm['DESCRIPTION'] = '';
				return false;
			}
		}

		$title = '';
		$description = '';

		$fullNameFormat = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		$entityTypeID = isset($arComm['ENTITY_TYPE_ID']) ? intval($arComm['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if($entityTypeID === CCrmOwnerType::Lead)
		{
			$name = '';
			$secondName = '';
			$lastName = '';
			$leadTitle = '';

			if(is_array(($arComm['ENTITY_SETTINGS'])))
			{
				$settings = $arComm['ENTITY_SETTINGS'];

				$name = isset($settings['NAME']) ? $settings['NAME'] : '';
				$secondName = isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : '';
				$lastName = isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '';
				$leadTitle = isset($settings['LEAD_TITLE']) ? $settings['LEAD_TITLE'] : '';
			}
			else
			{
				$arEntity = CCrmLead::GetByID($arComm['ENTITY_ID']);
				if($arEntity)
				{
					$name = isset($arEntity['NAME']) ? $arEntity['NAME'] : '';
					$secondName = isset($arEntity['SECOND_NAME']) ? $arEntity['SECOND_NAME'] : '';
					$lastName = isset($arEntity['LAST_NAME']) ? $arEntity['LAST_NAME'] : '';
					$leadTitle = isset($arEntity['TITLE']) ? $arEntity['TITLE'] : '';
				}
			}

			if($name === '' && $secondName === '' && $lastName === '')
			{
				$title = $leadTitle;
				//$description = '';
			}
			else
			{
				$title = CUser::FormatName($fullNameFormat,
					array(
						'LOGIN' => '',
						'NAME' => $name,
						'SECOND_NAME' => $secondName,
						'LAST_NAME' => $lastName
					),
					false,
					false
				);
				$description = $leadTitle;
			}
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			// Empty TYPE is person to person communiation, empty ENTITY_ID is unbound communication - no method to build title
			if(!($arComm['TYPE'] === '' && intval($arComm['ENTITY_ID']) === 0))
			{
				$name = '';
				$secondName = '';
				$lastName = '';
				$companyTitle = '';

				if(is_array(($arComm['ENTITY_SETTINGS'])))
				{
					$settings = $arComm['ENTITY_SETTINGS'];

					$name = isset($settings['NAME']) ? $settings['NAME'] : '';
					$secondName = isset($settings['SECOND_NAME']) ? $settings['SECOND_NAME'] : '';
					$lastName = isset($settings['LAST_NAME']) ? $settings['LAST_NAME'] : '';
					$companyTitle = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
				}
				else
				{
					$arEntity = CCrmContact::GetByID($arComm['ENTITY_ID']);
					if($arEntity)
					{
						$name = isset($arEntity['NAME']) ? $arEntity['NAME'] : '';
						$secondName = isset($arEntity['SECOND_NAME']) ? $arEntity['SECOND_NAME'] : '';
						$lastName = isset($arEntity['LAST_NAME']) ? $arEntity['LAST_NAME'] : '';
						$companyTitle = isset($arEntity['COMPANY_TITLE']) ? $arEntity['COMPANY_TITLE'] : '';
					}
				}

				$title = CUser::FormatName($fullNameFormat,
					array(
						'LOGIN' => '',
						'NAME' => $name,
						'SECOND_NAME' => $secondName,
						'LAST_NAME' => $lastName
					),
					false,
					false
				);

				$description = $companyTitle;
			}
		}
		elseif($entityTypeID === CCrmOwnerType::Company)
		{
			if(is_array(($arComm['ENTITY_SETTINGS'])))
			{
				$settings = $arComm['ENTITY_SETTINGS'];
				$title = isset($settings['COMPANY_TITLE']) ? $settings['COMPANY_TITLE'] : '';
			}
			else
			{
				$arEntity = CCrmCompany::GetByID($arComm['ENTITY_ID']);
				if($arEntity)
				{
					$title = isset($arEntity['TITLE']) ? $arEntity['TITLE'] : '';
				}
			}
		}

		$arComm['TITLE'] = $title;
		$arComm['DESCRIPTION'] = $description;
		return true;
	}
	public static function PrepareStorageElementInfo(&$arFields)
	{
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID']) ? (int)$arFields['STORAGE_TYPE_ID'] : StorageType::Undefined;
		if(!StorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = self::GetDefaultStorageTypeID();
		}

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS'])
			? $arFields['STORAGE_ELEMENT_IDS'] : array();

		if($storageTypeID === StorageType::File)
		{
			$arFields['FILES'] = array();
			foreach($storageElementIDs as $fileID)
			{
				$arData = CFile::GetFileArray($fileID);
				if(is_array($arData))
				{
					$arFields['FILES'][] = array(
						'fileID' => $arData['ID'],
						'fileName' => $arData['FILE_NAME'],
						'fileURL' =>  CCrmUrlUtil::UrnEncode($arData['SRC']),
						'fileSize' => $arData['FILE_SIZE']
					);
				}
			}
		}
		elseif($storageTypeID === StorageType::WebDav || $storageTypeID === StorageType::Disk)
		{
			$infos = array();
			foreach($storageElementIDs as $elementID)
			{
				$infos[] = StorageManager::getFileInfo($elementID, $storageTypeID);
			}
			$arFields[$storageTypeID === StorageType::Disk ? 'DISK_FILES' : 'WEBDAV_ELEMENTS'] = &$infos;
			unset($infos);
		}
	}

	public static function SaveRecentlyUsedCommunication($arComm, $userID = 0)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$communicationType = isset($arComm['TYPE']) ? $arComm['TYPE'] : '';
		$entityTypeID = isset($arComm['ENTITY_TYPE_ID']) ? intval($arComm['ENTITY_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			$entityTypeName = isset($arComm['ENTITY_TYPE']) ? $arComm['ENTITY_TYPE'] : '';
			if($entityTypeName !== '')
			{
				$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
			}
		}

		$entityID = isset($arComm['ENTITY_ID']) ? intval($arComm['ENTITY_ID']) : 0;
		$value = isset($arComm['VALUE']) ? $arComm['VALUE'] : '';

		if(!CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return false;
		}

		$optionName = $communicationType !== '' ? 'lru_'.strtolower($communicationType) : 'lru_person';

		$ary = CUserOptions::GetOption('crm_activity', $optionName, array(), $userID);
		$qty = count($ary);
		if($qty > 0)
		{
			for($i = 0; $i < $qty; $i++)
			{
				$item = $ary[$i];
				if($item['VALUE'] === $value
					&& $item['ENTITY_ID'] === $entityID
					&& $item['ENTITY_TYPE_ID'] === $entityTypeID)
				{
					// already exists
					return true;
				}
			}

			if($qty >= 20)
			{
				array_shift($ary);
			}
		}

		$entitySettings = isset($arComm['ENTITY_SETTINGS'])
			? $arComm['ENTITY_SETTINGS'] : null;
		if(!is_array($entitySettings))
		{
			self::PrepareCommunicationSettings($arComm);
			$entitySettings = $arComm['ENTITY_SETTINGS'];
		}

		$ary[] = array(
			'TYPE' => $communicationType,
			'VALUE' => $value,
			'ENTITY_ID' => $entityID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_SETTINGS' => $entitySettings
		);

		CUserOptions::SetOption('crm_activity', $optionName, $ary);
		return true;
	}
	public static function GetRecentlyUsedCommunications($communicationType, $userID = 0)
	{
		$communicationType = strval($communicationType);
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		$optionName = $communicationType !== '' ? 'lru_'.strtolower($communicationType) : 'lru_person';
		return CUserOptions::GetOption('crm_activity', $optionName, array(), $userID);
	}
	public static function PrepareStorageElementIDs(&$arFields)
	{
		if(isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
		{
			return;
		}

		if(isset($arFields['~STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['~STORAGE_ELEMENT_IDS'];
		}
		elseif(isset($arFields['STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['STORAGE_ELEMENT_IDS'];
		}
		else
		{
			$field = '';
		}

		if(is_array($field))
		{
			$result = $field;
		}
		elseif(is_numeric($field))
		{
			$ID = (int)$field;
			if($ID <= 0)
			{
				$ID = isset($arFields['ID']) ? (int)$arFields['ID'] : (isset($arFields['~ID']) ? (int)$arFields['~ID'] : 0);
			}

			if($ID <= 0)
			{
				$result = array();
			}
			else
			{
				$result = self::LoadElementIDs($ID);
				$arUpdateFields = array('STORAGE_ELEMENT_IDS' => serialize($result));
				$table = CCrmActivity::TABLE_NAME;
				global $DB;
				$DB->QueryBind(
					'UPDATE '.$table.' SET '.$DB->PrepareUpdate($table, $arUpdateFields).' WHERE ID = '.$ID,
					$arUpdateFields,
					false
				);
			}
		}
		elseif(is_string($field) && $field !== '')
		{
			$result = unserialize($field);
		}
		else
		{
			$result = array();
		}

		$arFields['~STORAGE_ELEMENT_IDS'] = $arFields['STORAGE_ELEMENT_IDS'] = &$result;
		unset($result);
	}
	public static function Exists($ID, $checkPerms = true)
	{
		$filter = array('ID'=> $ID);
		if(!$checkPerms)
		{
			$filter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = CCrmActivity::GetList(array(), $filter, false, false, array('ID'));
		return is_array($dbRes->Fetch());
	}
	public static function Complete($ID, $completed = true, $options = array())
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		if(is_string($completed))
		{
			$completed = strtoupper($completed)  === 'Y' ? 'Y' : 'N';
		}
		else
		{
			$completed = ((bool)$completed) ? 'Y' : 'N';
		}

		return self::Update($ID, array('COMPLETED' => $completed), true, true, $options);
	}
	public static function SetPriority($ID, $priority, $options = array())
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			self::RegisterError(array('text' => 'Invalid arguments are supplied.'));
			return false;
		}

		$priority = intval($priority);
		return self::Update($ID, array('PRIORITY' => $priority), true, true, $options);
	}
	public static function GetLastErrorMessage()
	{
		return ($c = count(self::$errors)) > 0 ? self::$errors[$c - 1] : '';
	}
	public static function GetErrorMessages()
	{
		return self::$errors;
	}
	public static function GetErrorCount()
	{
		return count(self::$errors);
	}
	public static function TryResolveUserFieldOwners(&$arUsefFieldData, &$arOwnerData, $arField = null)
	{
		$parsed = 0;

		$defaultTypeName = '';
		if(is_array($arField)
			&& isset($arField['USER_TYPE_ID']) && $arField['USER_TYPE_ID'] === 'crm'
			&& isset($arField['SETTINGS']) && is_array($arField['SETTINGS']))
		{
			foreach($arField['SETTINGS'] as $k => $v)
			{
				if($v !== 'Y')
				{
					continue;
				}

				if($defaultTypeName === '')
				{
					$defaultTypeName = $k;
					continue;
				}

				// There is more than one type enabled
				$defaultTypeName = '';
				break;
			}
		}

		foreach($arUsefFieldData as $value)
		{
			$value = strval($value);
			if($value === '')
			{
				continue;
			}

			$ownerTypeName = '';
			$ownerID = 0;
			if(preg_match('/^([A-Z]+)_([0-9]+)$/', strtoupper(trim($value)), $match) === 1)
			{
				$ownerTypeName = CCrmOwnerTypeAbbr::ResolveName($match[1]);
				$ownerID = intval($match[2]);
			}
			elseif($defaultTypeName !== '')
			{
				$ownerTypeName = $defaultTypeName;
				$ownerID = intval($value);
			}

			if($ownerTypeName === '' || $ownerID <= 0)
			{
				continue;
			}

			$arOwnerData[] = array(
				'OWNER_TYPE_NAME' => $ownerTypeName,
				'OWNER_ID' => $ownerID
			);

			$parsed++;
		}
		return $parsed > 0;
	}
	public static function CreateFromCalendarEvent(
		$eventID,
		&$arEventFields,
		$checkPerms = true,
		$regEvent = true)
	{
		$eventID = intval($eventID);
		if($eventID <= 0 && isset($arEventFields['ID']))
		{
			$eventID = intval($arEventFields['ID']);
		}

		if($eventID <= 0)
		{
			return false;
		}

		$entityCount = self::GetList(
			array(),
			array(
				'=ASSOCIATED_ENTITY_ID' => $eventID,
				'@TYPE_ID' =>
					array(
						CCrmActivityType::Call,
						CCrmActivityType::Meeting
					)
			),
			array(),
			false,
			false
		);

		if($entityCount > 0)
		{
			return false;
		}

		$arFields = array();
		self::SetFromCalendarEvent($eventID, $arEventFields, $arFields);
		if(isset($arFields['BINDINGS']) && count($arFields['BINDINGS']) > 0)
		{
			return self::Add($arFields, $checkPerms, $regEvent);
		}
	}
	// Event handlers -->
	public static function CreateFromTask(
		$taskID,
		&$arTaskFields,
		$checkPerms = true,
		$regEvent = true)
	{
		$entityCount = self::GetList(
			array(),
			array(
				'=TYPE_ID' =>  CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $taskID,
				'CHECK_PERMISSIONS' => 'N'
			),
			array(),
			false,
			false
		);

		if(is_int($entityCount) && $entityCount > 0)
		{
			return false;
		}

		$arFields = array();
		self::SetFromTask($taskID, $arTaskFields, $arFields);
		if(isset($arFields['BINDINGS']) && count($arFields['BINDINGS']) > 0)
		{
			return self::Add($arFields, $checkPerms, $regEvent, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
		}
	}
	public static function CreateFromDealEvent(&$arDeal)
	{
		$dealID = intval($arDeal['ID']);
		$originID = "DEAL_EVENT_{$dealID}";
		if(self::GetByOriginID($originID) !== false)
		{
			return false; //Already exists
		}

		$now = time() + CTimeZone::GetOffset();
		$typeID = $arDeal['EVENT_ID'] === 'PHONE' ? CCrmActivityType::Call : CCrmActivityType::Activity;
		$subject = GetMessage($typeID === CCrmActivityType::Call ? 'CRM_ACTIVITY_FROM_DEAL_EVENT_CALL' : 'CRM_ACTIVITY_FROM_DEAL_EVENT_INFO');

		$date = $now;
		if(isset($arDeal['EVENT_DATE']))
		{
			$date = MakeTimeStamp($arDeal['EVENT_DATE']);
		}
		elseif(isset($arDeal['DATE_MODIFY']))
		{
			$date = MakeTimeStamp($arDeal['DATE_MODIFY']);
		}
		elseif(isset($arDeal['DATE_CREATE']))
		{
			$date = MakeTimeStamp($arDeal['DATE_CREATE']);
		}

		$dateFmt = ConvertTimeStamp($date, 'FULL', SITE_ID);

		$responsibleID = 0;
		if(isset($arDeal['ASSIGNED_BY_ID']))
		{
			$responsibleID = intval($arDeal['ASSIGNED_BY_ID']);
		}
		elseif(isset($arDeal['MODIFY_BY_ID']))
		{
			$responsibleID = intval($arDeal['MODIFY_BY_ID']);
		}
		elseif(isset($arDeal['CREATED_BY_ID']))
		{
			$responsibleID = intval($arDeal['CREATED_BY_ID']);
		}

		$arFields = array(
			'TYPE_ID' => $typeID,
			'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
			'OWNER_ID' => $dealID,
			'SUBJECT' => $subject,
			'START_TIME' => $dateFmt,
			'END_TIME' => $dateFmt,
			'COMPLETED' => ($date <= $now) ? 'Y' : 'N',
			'RESPONSIBLE_ID' => $responsibleID,
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => isset($arDeal['EVENT_DESCRIPTION']) ? $arDeal['EVENT_DESCRIPTION'] : '',
			'LOCATION' => '',
			'DIRECTION' => $typeID === CCrmActivityType::Call ? CCrmActivityDirection::Outgoing : CCrmActivityDirection::Undefined,
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'BINDINGS' => array(
				array(
					'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
					'OWNER_ID' => $arDeal['ID']
				)
			),
			'ORIGIN_ID' => $originID,
			'SETTINGS' => array()
		);

		return self::Add($arFields, false, false);
	}
	// <-- Contract
	private static function SetFromTask($taskID, &$arTaskFields, &$arFields)
	{
		$isNew = !(isset($arFields['ID']) && intval($arFields['ID']) > 0);
		if($isNew)
		{
			$arFields['TYPE_ID'] =  CCrmActivityType::Task;
			$arFields['ASSOCIATED_ENTITY_ID'] = $taskID;
			$arFields['NOTIFY_TYPE'] = CCrmActivityNotifyType::None;
		}

		if($isNew || isset($arTaskFields['TITLE']))
		{
			$arFields['SUBJECT'] = isset($arTaskFields['TITLE']) ? $arTaskFields['TITLE'] : '';
		}

		if($isNew || isset($arTaskFields['RESPONSIBLE_ID']))
		{
			$arFields['RESPONSIBLE_ID'] = isset($arTaskFields['RESPONSIBLE_ID']) ? intval($arTaskFields['RESPONSIBLE_ID']) : 0;
		}

		if($isNew || isset($arTaskFields['PRIORITY']))
		{
			// Try to convert 'task priority' to 'crm activity priority'
			$priorityText = isset($arTaskFields['PRIORITY']) ? strval($arTaskFields['PRIORITY']) : '0';
			$priority = CCrmActivityPriority::Low;
			if($priorityText === '1')
			{
				$priority = CCrmActivityPriority::Medium;
			}
			elseif($priorityText === '2')
			{
				$priority = CCrmActivityPriority::High;
			}

			$arFields['PRIORITY'] = $priority;
		}

		if($isNew || isset($arTaskFields['STATUS']))
		{
			// Try to find status
			$completed = 'N';
			if(isset($arTaskFields['STATUS']))
			{
				$status = intval($arTaskFields['STATUS']);
				// COMPLETED: 5, DECLINED: 7
				if($status === 5 || $status === 7)
				{
					$completed = 'Y';
				}
			}
			$arFields['COMPLETED'] = $completed;
		}

		$start = null;
		$end = null;

		if(isset($arTaskFields['DATE_START']) || isset($arTaskFields['START_DATE_PLAN']))
		{
			// Try to find start date
			if(isset($arTaskFields['DATE_START']) && $arTaskFields['DATE_START'] !== false)
			{
				$start = $arTaskFields['DATE_START'];
			}
			elseif(isset($arTaskFields['START_DATE_PLAN']) && $arTaskFields['START_DATE_PLAN'] !== false)
			{
				$start = $arTaskFields['START_DATE_PLAN'];
			}

			if($start)
			{
				$arFields['START_TIME'] = $start;
			}
		}

		if(isset($arTaskFields['DEADLINE']) || isset($arTaskFields['CLOSED_DATE']) || isset($arTaskFields['END_DATE_PLAN']))
		{
			$isCompleted = isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y';

			// Try to find end date
			if(!$isCompleted && isset($arTaskFields['DEADLINE']) && $arTaskFields['DEADLINE'] !== false)
			{
				$end = $arTaskFields['DEADLINE'];
			}
			elseif($isCompleted && isset($arTaskFields['CLOSED_DATE']) && $arTaskFields['CLOSED_DATE'] !== false)
			{
				$end = $arTaskFields['CLOSED_DATE'];
			}

			if(!$end)
			{
				if(isset($arTaskFields['END_DATE_PLAN']) && $arTaskFields['END_DATE_PLAN'] !== false)
				{
					$end = $arTaskFields['END_DATE_PLAN'];
				}
				elseif($arFields['START_TIME'])
				{
					$end = $arFields['START_TIME'];
				}
			}

			if($end)
			{
				$arFields['END_TIME'] = $end;
				if(!$start)
				{
					$arFields['START_TIME'] = $end;
				}
			}
		}

		if($isNew || isset($arTaskFields['DESCRIPTION']))
		{
			$description = isset($arTaskFields['DESCRIPTION']) ? $arTaskFields['DESCRIPTION'] : '';
			$descriptionType =
				isset($arTaskFields['DESCRIPTION_IN_BBCODE']) && $arTaskFields['DESCRIPTION_IN_BBCODE'] === 'Y'
				? CCrmContentType::BBCode
				: CCrmContentType::Html;

			if($description !== '' && $descriptionType === CCrmContentType::Html)
			{
				$sanitizer = new CBXSanitizer();
				$sanitizer->ApplyDoubleEncode(false);
				$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
				$description = $sanitizer->SanitizeHtml($description);
			}

			if($description === '')
			{
				//Ignore content type if description is empty
				$descriptionType = CCrmContentType::PlainText;
			}

			$arFields['DESCRIPTION'] = $description;
			$arFields['DESCRIPTION_TYPE'] = $descriptionType;
		}

		$arTaskOwners =  isset($arTaskFields['UF_CRM_TASK']) ? $arTaskFields['UF_CRM_TASK'] : array();
		$arOwnerData = array();

		if(!is_array($arTaskOwners))
		{
			$arTaskOwners  = array($arTaskOwners);
		}

		$arFields['BINDINGS'] = array();

		if(self::TryResolveUserFieldOwners($arTaskOwners, $arOwnerData, CCrmUserType::GetTaskBindingField()))
		{
			foreach($arOwnerData as $arOwnerInfo)
			{
				$arFields['BINDINGS'][] = array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($arOwnerInfo['OWNER_TYPE_NAME']),
					'OWNER_ID' => $arOwnerInfo['OWNER_ID']
				);
			}
		}
	}
	private static function SetFromCalendarEvent($eventID, &$arEventFields, &$arFields)
	{
		$isNew = !(isset($arFields['ID']) && intval($arFields['ID']) > 0);

		$arFields['ASSOCIATED_ENTITY_ID'] = $eventID;

		$arEventOwners = array();
		if(isset($arEventFields['UF_CRM_CAL_EVENT']))
		{
			$arEventOwners = $arEventFields['UF_CRM_CAL_EVENT'];
		}
		else
		{
			//Try to load if not found CRM bindings
			$arReloadedEventFields = CCalendarEvent::GetById($eventID, false);
			if(isset($arReloadedEventFields['UF_CRM_CAL_EVENT']))
			{
				$arEventOwners = $arReloadedEventFields['UF_CRM_CAL_EVENT'];
			}
		}

		if(!is_array($arEventOwners))
		{
			$arEventOwners = array($arEventOwners);
		}

		$arOwnerData = array();
		self::TryResolveUserFieldOwners($arEventOwners, $arOwnerData, CCrmUserType::GetCalendarEventBindingField());
		if(!empty($arOwnerData))
		{
			$arFields['OWNER_TYPE_ID'] = CCrmOwnerType::ResolveID($arOwnerData[0]['OWNER_TYPE_NAME']);
			$arFields['OWNER_ID'] = $arOwnerData[0]['OWNER_ID'];

			foreach($arOwnerData as &$arOwnerInfo)
			{
				$arFields['BINDINGS'][] = array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($arOwnerInfo['OWNER_TYPE_NAME']),
					'OWNER_ID' => $arOwnerInfo['OWNER_ID']
				);
			}
			unset($arOwnerInfo);
		}
		else
		{
			$arFields['OWNER_TYPE_ID'] = 0;
			$arFields['OWNER_ID'] = 0;
			$arFields['BINDINGS'] = array();
		}

		if($isNew)
		{
			//Meeting by default
			$arFields['TYPE_ID'] = CCrmActivityType::Meeting;
			//Not completed for new activities. Do not change existed activities.
			$arFields['COMPLETED'] = 'N';
		}

		if($isNew || isset($arEventFields['NAME']))
		{
			$arFields['SUBJECT'] = isset($arEventFields['NAME']) ? $arEventFields['NAME'] : '';
		}

		$isPeriodicEvent = isset($arEventFields['RRULE']) && $arEventFields['RRULE'] !== '';
		//If 'DT_FROM' is assigned set 'START_TIME' and 'END_TIME' from 'DT_FROM'. Activity deadline will be at calevent DT_FROM
		//Ignore 'DT_TO' if periodic event
		if(isset($arEventFields['DT_FROM']) && isset($arEventFields['DT_TO']))
		{
			$arFields['START_TIME'] = $arEventFields['DT_FROM'];
			$arFields['END_TIME'] = !$isPeriodicEvent ? $arEventFields['DT_TO'] : $arEventFields['DT_FROM'];
		}
		elseif(isset($arEventFields['DT_FROM']))
		{
			$arFields['START_TIME'] = $arFields['END_TIME'] = $arEventFields['DT_FROM'];
		}
		elseif(isset($arEventFields['DT_TO']) && !$isPeriodicEvent)
		{
			$arFields['START_TIME'] = $arFields['END_TIME'] = $arEventFields['DT_TO'];
		}

		if($isNew || isset($arEventFields['CREATED_BY']))
		{
			$arFields['RESPONSIBLE_ID'] = isset($arEventFields['CREATED_BY']) ? intval($arEventFields['CREATED_BY']) : 0;
		}

		if($isNew || isset($arEventFields['IMPORTANCE']))
		{
			$arFields['PRIORITY'] = CCrmActivityPriority::FromCalendarEventImportance(isset($arEventFields['IMPORTANCE']) ? $arEventFields['IMPORTANCE'] : '');
		}

		if($isNew || isset($arEventFields['DESCRIPTION']))
		{
			$arFields['DESCRIPTION'] = isset($arEventFields['DESCRIPTION']) ? $arEventFields['DESCRIPTION'] : '';
		}

		if($isNew || isset($arEventFields['LOCATION']))
		{
			$arFields['LOCATION'] = isset($arEventFields['LOCATION']) ? $arEventFields['LOCATION'] : '';
		}

		if($isNew || isset($arEventFields['REMIND']))
		{
			$remindData = isset($arEventFields['REMIND']) ? $arEventFields['REMIND'] : array();
			if(is_string($remindData))
			{
				if($remindData !== '')
				{
					$remindData = unserialize($remindData);
				}

				if(!is_array($remindData))
				{
					$remindData = array();
				}
			}

			if(empty($remindData))
			{
				$arFields['NOTIFY_TYPE'] = CCrmActivityNotifyType::None;
			}
			else
			{
				$remindInfo = $remindData[0];
				$remindType = CCrmActivityNotifyType::FromCalendarEventRemind(isset($remindInfo['type']) ? $remindInfo['type'] : '');
				$remindValue = isset($remindInfo['count']) ? intval($remindInfo['count']) : 0;
				if($remindType !== CCrmActivityNotifyType::None && $remindValue > 0)
				{
					$arFields['NOTIFY_TYPE'] = $remindType;
					$arFields['NOTIFY_VALUE'] = $remindValue;
				}
			}
		}
	}
	// Event handlers -->
	public static function OnTaskAdd($taskID, &$arTaskFields)
	{
		self::CreateFromTask($taskID, $arTaskFields, false, true);
	}
	public static function OnBeforeTaskAdd(&$arTaskFields)
	{
		//Search for undefined or default title
		$title = isset($arTaskFields['TITLE']) ? trim($arTaskFields['TITLE']) : '';
		if($title !== '' && preg_match('/^\s*CRM\s*:\s*$/i', $title) !== 1)
		{
			return;
		}

		$arTaskOwners =  isset($arTaskFields['UF_CRM_TASK']) ? $arTaskFields['UF_CRM_TASK'] : array();
		if(!is_array($arTaskOwners))
		{
			$arTaskOwners  = array($arTaskOwners);
		}

		$arOwnerData = array();
		if(self::TryResolveUserFieldOwners($arTaskOwners, $arOwnerData, CCrmUserType::GetTaskBindingField()))
		{
			$arOwnerInfo = $arOwnerData[0];
			$arTaskFields['TITLE'] = 'CRM: '.CCrmOwnerType::GetCaption(
				CCrmOwnerType::ResolveID($arOwnerInfo['OWNER_TYPE_NAME']),
				$arOwnerInfo['OWNER_ID']
			);
		}
	}
	public static function OnTaskUpdate($taskID, &$arTaskFields)
	{
		$taskID = intval($taskID);
		if(isset(self::$TASK_OPERATIONS[$taskID]) && self::$TASK_OPERATIONS[$taskID] === 'U')
		{
			return;
		}

		if (!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return;
		}

		$dbTask = CTasks::GetByID($taskID, false);
		$arAllTaskFields = $dbTask->Fetch();
		if(!$arAllTaskFields)
		{
			return;
		}

		$dbEntities = self::GetList(
			array(),
			array(
				'=TYPE_ID' =>  CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $taskID,
				'CHECK_PERMISSIONS' => 'N'
			)
		);

		// Does not works on MSSQL
		//if($dbEntities->SelectedRowsCount() > 0)

		$isFound = false;
		while($arEntity = $dbEntities->Fetch())
		{
			if(!$isFound)
			{
				$isFound = true;
			}
			self::SetFromTask($taskID, $arAllTaskFields, $arEntity);
			// Update activity if bindings are found overwise delete unbound activity
			if(isset($arEntity['BINDINGS']) && count($arEntity['BINDINGS']) > 0)
			{
				self::Update($arEntity['ID'], $arEntity, false, true, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
			}
			else
			{
				self::Delete($arEntity['ID'], false, true, array('SKIP_ASSOCIATED_ENTITY' => true));
			}
		}

		if(!$isFound)
		{
			$arFields = array();
			self::SetFromTask($taskID, $arAllTaskFields, $arFields);
			if(isset($arFields['BINDINGS']) && count($arFields['BINDINGS']) > 0)
			{
				self::Add($arFields, false, true, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
			}
		}
	}
	public static function OnTaskDelete($taskID)
	{
		$taskID = intval($taskID);
		if(isset(self::$TASK_OPERATIONS[$taskID]) && self::$TASK_OPERATIONS[$taskID] === 'D')
		{
			return;
		}

		$dbEntities = self::GetList(
			array(),
			array(
				'=TYPE_ID' =>  CCrmActivityType::Task,
				'=ASSOCIATED_ENTITY_ID' => $taskID,
				'CHECK_PERMISSIONS' => 'N'
			),
			false,
			false,
			array('ID')
		);

		while($arEntity = $dbEntities->Fetch())
		{
			self::Delete($arEntity['ID'], false, true, array('SKIP_ASSOCIATED_ENTITY' => true));
		}
	}
	public static function OnCalendarEventEdit($arFields, $bNew, $userId)
	{
		if(self::$IGNORE_CALENDAR_EVENTS)
		{
			return;
		}

		$eventID = isset($arFields['ID']) ? (int)$arFields['ID'] : 0;
		if($eventID <= 0)
		{
			return;
		}

		$arEventFields = CCalendarEvent::GetById($eventID, false);

		$isFound = false;
		if(!$bNew)
		{
			$dbEntities = self::GetList(
				array(),
				array(
					'=ASSOCIATED_ENTITY_ID' => $eventID,
					'@TYPE_ID' =>
					array(
						CCrmActivityType::Call,
						CCrmActivityType::Meeting
					)
				)
			);

			$arEntity = $dbEntities->Fetch();
			if(is_array($arEntity))
			{
				if(!$isFound)
				{
					$isFound = true;
				}
				self::SetFromCalendarEvent($eventID, $arEventFields, $arEntity);
				// Update activity if bindings are found overwise delete unbound activity
				if(isset($arEntity['BINDINGS']) && count($arEntity['BINDINGS']) > 0)
				{
					self::Update($arEntity['ID'], $arEntity, false, true, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
				}
				else
				{
					self::Delete($arEntity['ID'], false, true, array('SKIP_ASSOCIATED_ENTITY' => true));
				}
			}
		}

		if(!$isFound)
		{
			$arFields = array();
			self::SetFromCalendarEvent($eventID, $arEventFields, $arFields);
			if(isset($arFields['BINDINGS']) && count($arFields['BINDINGS']) > 0)
			{
				self::Add($arFields, false, true, array('SKIP_ASSOCIATED_ENTITY' => true, 'REGISTER_SONET_EVENT' => true));
			}
		}

	}
	public static function OnCalendarEventDelete($eventID, $arEventFields)
	{
		if(self::$IGNORE_CALENDAR_EVENTS)
		{
			return;
		}

		$dbEntities = self::GetList(
			array(),
			array(
				'=ASSOCIATED_ENTITY_ID' => $eventID,
				'@TYPE_ID' =>
				array(
					CCrmActivityType::Call,
					CCrmActivityType::Meeting
				)
			)
		);

		while($arEntity = $dbEntities->Fetch())
		{
			self::Delete($arEntity['ID'], false, false);
		}
	}
	// <-- Event handlers
	public static function DeleteByOwner($ownerTypeID, $ownerID)
	{
		$ownerID = intval($ownerID);
		$ownerTypeID = intval($ownerTypeID);
		if($ownerID <= 0 || $ownerTypeID <= 0)
		{
			return;
		}

		$processedIDs = self::DeleteBindingsByOwner($ownerTypeID, $ownerID);
		$deletedItemIDs = self::DeleteUnbound(
			array(array('OWNER_TYPE_ID' => $ownerTypeID, 'OWNER_ID' => $ownerID))
		);

		$presentItemIDs = array_diff($processedIDs, $deletedItemIDs);
		$responsibleIDs = array();
		if(!empty($presentItemIDs))
		{
			$dbRes = self::GetList(
				array(), array('@ID' => $presentItemIDs, 'CHECK_PERMISSIONS' => 'N'), false, false, array('ID', 'RESPONSIBLE_ID')
			);

			if(is_object($dbRes))
			{
				while($item = $dbRes->Fetch())
				{
					$responsibleID = isset($item['RESPONSIBLE_ID']) ? intval($item['RESPONSIBLE_ID']) : 0;
					if($responsibleID > 0 && !in_array($responsibleID, $responsibleIDs, true))
					{
						$responsibleIDs[] = $responsibleID;
					}
				}
			}
		}

		// Synchronize user activity -->
		if(!empty($responsibleIDs))
		{
			foreach($responsibleIDs as $responsibleID)
			{
				self::SynchronizeUserActivity($ownerTypeID, $ownerID, $responsibleID);
			}
		}
		self::SynchronizeUserActivity($ownerTypeID, $ownerID, 0);
	}
	public static function DeleteBindingsByOwner($ownerTypeID, $ownerID)
	{
		$ownerID = intval($ownerID);
		$ownerTypeID = intval($ownerTypeID);
		if($ownerID <= 0 || $ownerTypeID <= 0)
		{
			return array();
		}

		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		global $DB;

		$dbRes = $DB->Query(
			"SELECT ACTIVITY_ID FROM {$bindingTableName} WHERE OWNER_ID = {$ownerID} AND OWNER_TYPE_ID = {$ownerTypeID}",
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		$processedIDs = array();
		if(is_object($dbRes))
		{
			while($arRes = $dbRes->Fetch())
			{
				$processedIDs[] = intval($arRes['ACTIVITY_ID']);
			}
		}

		if(!empty($processedIDs))
		{
			$DB->Query(
				"DELETE FROM {$bindingTableName} WHERE OWNER_ID = {$ownerID} AND OWNER_TYPE_ID = {$ownerTypeID}",
				false,
				'File: '.__FILE__.'<br/>Line: '.__LINE__
			);
		}

		return $processedIDs;
	}
	public static function DeleteUnbound($arBindings = null)
	{
		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		global $DB;
		$dbRes = $DB->Query(
			"SELECT ID FROM {$tableName} WHERE ID NOT IN (SELECT ACTIVITY_ID FROM {$bindingTableName})",
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		$delOptions = array(
			'SKIP_BINDINGS' => true,
			'SKIP_USER_ACTIVITY_SYNC' => true
		);

		if(is_array($arBindings) && !empty($arBindings))
		{
			$delOptions['ACTUAL_BINDINGS'] = $arBindings;
		}

		$processedIDs = array();
		$responsibleIDs = array();
		while($arRes = $dbRes->Fetch())
		{
			$itemID = intval($arRes['ID']);

			$item = self::GetByID($itemID, false);
			if(!is_array($item))
			{
				continue;
			}

			$processedIDs[] = $itemID;
			$responsibleID = isset($item['RESPONSIBLE_ID']) ? intval($item['RESPONSIBLE_ID']) : 0;
			if($responsibleID > 0 && !in_array($responsibleID, $responsibleIDs, true))
			{
				$responsibleIDs[] = $responsibleID;
			}

			$delOptions['ACTUAL_ITEM'] = $item;
			self::Delete($itemID, false, false, $delOptions);
		}

		// Synchronize user activity -->
		if(is_array($arBindings) && !empty($arBindings))
		{
			foreach($arBindings as &$arBinding)
			{
				foreach($responsibleIDs as $responsibleID)
				{
					self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], $responsibleID);
				}
				self::SynchronizeUserActivity($arBinding['OWNER_TYPE_ID'], $arBinding['OWNER_ID'], 0);
			}
			unset($arBinding);
		}
		// <-- Synchronize user activity

		return $processedIDs;
	}
	private static function ResolveEventTypeName($entityTypeID)
	{
		$entityTypeID = intval($entityTypeID);

		if($entityTypeID === CCrmActivityType::Call)
		{
			return 'CALL';
		}
		elseif($entityTypeID === CCrmActivityType::Meeting)
		{
			return 'MEETING';
		}
		elseif($entityTypeID === CCrmActivityType::Task)
		{
			return 'TASK';
		}
		elseif($entityTypeID === CCrmActivityType::Email)
		{
			return 'EMAIL';
		}

		return 'EVENT';
	}
	private static function ResolveStorageElementName($storageTypeID, $elementID)
	{
		return StorageManager::getFileName($elementID, $storageTypeID);
	}
	private static function PrepareFileEvent($storageTypeID, $elementID, $action, &$arRow, &$arEvents)
	{
		$storageTypeID = intval($storageTypeID);
		$elementID = intval($elementID);
		$action = strtoupper(strval($action));

		$typeID = isset($arRow['TYPE_ID']) ? intval($arRow['TYPE_ID']) : CCrmActivityType::Undefined;
		$typeName = self::ResolveEventTypeName($typeID);

		$name = isset($arRow['SUBJECT']) ? strval($arRow['SUBJECT']) : '';
		if($name === '')
		{
			$name = "[{$arRow['ID']}]";
		}

		$arEventFiles = array();
		if($action === 'ADD' && $storageTypeID !== StorageType::Undefined)
		{
			$arEventFiles = self::MakeRawFiles($storageTypeID, array($elementID));
		}

		$arEvents[] = array(
			'EVENT_NAME' => GetMessage(
				"CRM_ACTIVITY_{$typeName}_FILE_{$action}",
				array('#NAME#'=> $name)
			),
			'EVENT_TEXT_1' => $action !== 'ADD' ? self::ResolveStorageElementName($storageTypeID, $elementID) : '',
			'EVENT_TEXT_2' => '',
			'FILES' => $arEventFiles
		);
	}
	private static function PrepareUpdateEvent($fieldName, $arNewRow, $arOldRow, &$arEvents)
	{
		$fieldName = strtoupper(strval($fieldName));

		if($fieldName === '')
		{
			return false;
		}

		$typeID = isset($arNewRow['TYPE_ID']) ? intval($arNewRow['TYPE_ID']) : CCrmActivityType::Undefined;

		$changed = false;
		$oldText = $newText = '';

		if($fieldName === 'NOTIFY')
		{
			$oldType = isset($arOldRow['NOTIFY_TYPE']) ? intval($arOldRow['NOTIFY_TYPE']) : CCrmActivityNotifyType::None;
			$newType = isset($arNewRow['NOTIFY_TYPE']) ? intval($arNewRow['NOTIFY_TYPE']) : CCrmActivityNotifyType::None;

			$oldVal = isset($arOldRow['NOTIFY_VALUE']) ? intval($arOldRow['NOTIFY_VALUE']) : 0;
			$newVal = isset($arNewRow['NOTIFY_VALUE']) ? intval($arNewRow['NOTIFY_VALUE']) : 0;

			if($oldType !== $newType || $oldVal !== $newVal)
			{
				$changed = true;

				$oldText =
					$oldType === CCrmActivityNotifyType::None
						? CCrmActivityNotifyType::ResolveDescription(CCrmActivityNotifyType::None)
						: (strval($oldVal).' '.CCrmActivityNotifyType::ResolveDescription($oldType));

				$newText =
					$newType === CCrmActivityNotifyType::None
						? CCrmActivityNotifyType::ResolveDescription(CCrmActivityNotifyType::None)
						: (strval($newVal).' '.CCrmActivityNotifyType::ResolveDescription($newType));
			}
		}
		else
		{
			$old = isset($arOldRow[$fieldName]) ? strval($arOldRow[$fieldName]) : '';
			$new = isset($arNewRow[$fieldName]) ? strval($arNewRow[$fieldName]) : '';

			if(strcmp($old, $new) !== 0)
			{
				$changed = true;

				$oldText = $old;
				$newText = $new;

				if($fieldName === 'COMPLETED')
				{
					$oldText = CCrmActivityStatus::ResolveDescription(
						$old === 'Y' ? CCrmActivityStatus::Completed : CCrmActivityStatus::Waiting,
						isset($arOldRow['TYPE_ID']) ? intval($arOldRow['TYPE_ID']) : CCrmActivityType::Undefined
					);

					$newText = CCrmActivityStatus::ResolveDescription(
						$new === 'Y' ? CCrmActivityStatus::Completed : CCrmActivityStatus::Waiting,
						isset($arNewRow['TYPE_ID']) ? intval($arNewRow['TYPE_ID']) : CCrmActivityType::Undefined
					);
				}
				elseif($fieldName === 'PRIORITY')
				{
					$oldText = CCrmActivityPriority::ResolveDescription($old);
					$newText = CCrmActivityPriority::ResolveDescription($new);
				}
				elseif($fieldName === 'DIRECTION')
				{
					$oldText = CCrmActivityDirection::ResolveDescription($old, $typeID);
					$newText = CCrmActivityDirection::ResolveDescription($new, $typeID);
				}
				elseif($fieldName === 'RESPONSIBLE_ID')
				{
					$oldID = intval($old);
					$arOldUser = array();

					$newID = intval($new);
					$arNewUser = array();

					$dbUser = CUser::GetList(
						($by='id'),
						($order='asc'),
						array('ID'=> "{$oldID}|{$newID}"),
						array(
							'FIELDS'=> array(
								'ID',
								'LOGIN',
								'EMAIL',
								'NAME',
								'LAST_NAME',
								'SECOND_NAME'
							)
						)
					);

					while (is_array($arUser = $dbUser->Fetch()))
					{
						$userID = intval($arUser['ID']);
						if($userID === $oldID)
						{
							$arOldUser = $arUser;
						}
						elseif($userID === $newID)
						{
							$arNewUser = $arUser;
						}
					}

					$template = CSite::GetNameFormat(false);
					$oldText = CUser::FormatName($template, $arOldUser);
					$newText = CUser::FormatName($template, $arNewUser);
				}
			}
		}

		if($changed)
		{
			$typeName = self::ResolveEventTypeName($typeID);
			$name = isset($arNewRow['SUBJECT']) ? strval($arNewRow['SUBJECT']) : '';
			if($name === '')
			{
				$name = "[{$arNewRow['ID']}]";
			}

			$arEvents[] = array(
				'EVENT_NAME' => GetMessage(
					"CRM_ACTIVITY_CHANGE_{$typeName}_{$fieldName}",
					array('#NAME#'=> $name)
				),
				'EVENT_TEXT_1' => $oldText !== '' ? $oldText : GetMessage('CRM_ACTIVITY_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => $newText !== '' ? $newText : GetMessage('CRM_ACTIVITY_FIELD_COMPARE_EMPTY'),
				'USER_ID' => isset($arNewRow['EDITOR_ID']) ? $arNewRow['EDITOR_ID'] : 0
			);
		}

		return $changed;
	}
	private static function RegisterAddEvent($ownerTypeID, $ownerID, $arRow, $checkPerms)
	{
		$typeID = isset($arRow['TYPE_ID']) ? (int)$arRow['TYPE_ID'] : CCrmActivityType::Undefined;

		$typeName = 'EVENT';
		if($typeID === CCrmActivityType::Call)
		{
			$typeName = 'CALL';
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$typeName = 'MEETING';
		}
		elseif($typeID === CCrmActivityType::Task)
		{
			$typeName = 'TASK';
		}
		elseif($typeID === CCrmActivityType::Email)
		{
			$typeName = 'EMAIL';
		}

		$arEventFiles = array();
		$storageTypeID = isset($arRow['STORAGE_TYPE_ID']) ? (int)$arRow['STORAGE_TYPE_ID'] : StorageType::Undefined;
		if($storageTypeID !== StorageType::Undefined)
		{
			self::PrepareStorageElementIDs($arRow);
			if(!empty($arRow['STORAGE_ELEMENT_IDS']))
			{
				$arEventFiles = self::MakeRawFiles($storageTypeID, $arRow['STORAGE_ELEMENT_IDS']);
			}
		}

		$eventText  = '';
		$eventText .= '<b>'.GetMessage('CRM_ACTIVITY_SUBJECT').'</b>: '.(isset($arRow['SUBJECT']) ? strval($arRow['SUBJECT']) : '').PHP_EOL;
		if(!empty($arRow['LOCATION']))
		{
			$eventText .= '<b>'.GetMessage('CRM_ACTIVITY_LOCATION').'</b>: '.$arRow['LOCATION'].PHP_EOL;
		}
		if(!empty($arRow['DESCRIPTION']))
		{
			$eventText .= $arRow['DESCRIPTION'];
		}

		$arEvents = array(
			array(
				'EVENT_NAME' => GetMessage("CRM_ACTIVITY_{$typeName}_ADD"),
				'EVENT_TEXT_1' => $eventText,
				'EVENT_TEXT_2' => '',
				'USER_ID' => isset($arRow['EDITOR_ID']) ? $arRow['EDITOR_ID'] : 0,
				'FILES' => $arEventFiles
			)
		);

		return self::RegisterEvents($ownerTypeID, $ownerID, $arEvents, $checkPerms);
	}
	private static function RegisterUpdateEvent($ownerTypeID, $ownerID, $arNewRow, $arOldRow, $checkPerms)
	{
		$arEvents = array();

		self::PrepareUpdateEvent('SUBJECT', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('START_TIME', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('END_TIME', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('COMPLETED', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('PRIORITY', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('NOTIFY', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('DESCRIPTION', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('LOCATION', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('DIRECTION', $arNewRow, $arOldRow, $arEvents);
		self::PrepareUpdateEvent('RESPONSIBLE_ID', $arNewRow, $arOldRow, $arEvents);

		// Processing of the files
		$newStorageTypeID = isset($arNewRow['STORAGE_TYPE_ID']) ? intval($arNewRow['STORAGE_TYPE_ID']) : StorageType::Undefined;
		$oldStorageTypeID = isset($arOldRow['STORAGE_TYPE_ID']) ? intval($arOldRow['STORAGE_TYPE_ID']) : StorageType::Undefined;

		self::PrepareStorageElementIDs($arNewRow);
		$newElementIDs = $arNewRow['STORAGE_ELEMENT_IDS'];

		self::PrepareStorageElementIDs($arOldRow);
		$oldElementIDs = $arOldRow['STORAGE_ELEMENT_IDS'];

		if($newStorageTypeID === $oldStorageTypeID)
		{
			$arRemovedElementIDs = array_values(array_diff($oldElementIDs, $newElementIDs));
			if(!empty($arRemovedElementIDs))
			{
				foreach($arRemovedElementIDs as $elementID)
				{
					self::PrepareFileEvent($oldStorageTypeID, $elementID, 'REMOVE', $arNewRow, $arEvents);
				}
			}

			$arAddedElementIDs = array_values(array_diff($newElementIDs, $oldElementIDs));
			if(!empty($arAddedElementIDs))
			{
				foreach($arAddedElementIDs as $elementID)
				{
					self::PrepareFileEvent($newStorageTypeID, $elementID, 'ADD', $arNewRow, $arEvents);
				}
			}
		}
		else
		{
			foreach($oldElementIDs as $elementID)
			{
				self::PrepareFileEvent($oldStorageTypeID, $elementID, 'REMOVE', $arNewRow, $arEvents);
			}

			foreach($newElementIDs as $elementID)
			{
				self::PrepareFileEvent($newStorageTypeID, $elementID, 'ADD', $arNewRow, $arEvents);
			}
		}

		return count($arEvents) > 0 ? self::RegisterEvents($ownerTypeID, $ownerID, $arEvents, $checkPerms) : false;
	}
	private static function RegisterRemoveEvent($ownerTypeID, $ownerID, $arRow, $checkPerms)
	{
		$typeID = isset($arRow['TYPE_ID']) ? (int)$arRow['TYPE_ID'] : CCrmActivityType::Undefined;

		$typeName = 'EVENT';
		if($typeID === CCrmActivityType::Call)
		{
			$typeName = 'CALL';
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$typeName = 'MEETING';
		}
		elseif($typeID === CCrmActivityType::Task)
		{
			$typeName = 'TASK';
		}
		elseif($typeID === CCrmActivityType::Email)
		{
			$typeName = 'EMAIL';
		}

		$name = isset($arRow['SUBJECT']) ? strval($arRow['SUBJECT']) : '';
		if($name === '')
		{
			$name = "[{$arRow['ID']}]";
		}

		return self::RegisterEvents(
			$ownerTypeID,
			$ownerID,
			array(
				array(
					'EVENT_NAME' => GetMessage("CRM_ACTIVITY_{$typeName}_REMOVE"),
					'EVENT_TEXT_1' => $name,
					'EVENT_TEXT_2' => '',
					'USER_ID' => isset($arRow['EDITOR_ID']) ? $arRow['EDITOR_ID'] : 0
				)
			),
			$checkPerms
		);
	}
	private static function GetEventName($arFields)
	{
		if(!is_array($arFields))
		{
			return '';
		}

		$name = isset($arFields['SUBJECT']) ? strval($arFields['SUBJECT']) : '';
		if($name === '' && isset($arFields['ID']))
		{
			$name = "[{$arFields['ID']}]";
		}

		return $name;
	}
	private static function GetEventMessageSuffix($arFields)
	{
		$suffix = '';

		$typeID = self::GetActivityType($arFields);
		if($typeID === CCrmActivityType::Call)
		{
			$suffix = 'CALL';
		}
		elseif($typeID === CCrmActivityType::Meeting)
		{
			$suffix = 'MEETING';
		}
		elseif($typeID === CCrmActivityType::Email)
		{
			$suffix = 'EMAIL';
		}
		elseif($typeID === CCrmActivityType::Task)
		{
			$suffix = 'TASK';
		}

		return $suffix;
	}
	protected static function RegisterFileEvent($ID, $arFields, $fileInfo, $eventType, $checkPerms = true)
	{
		if(!is_array($fileInfo))
		{
			return false;
		}

		// 'TYPE_ID' and SUBJECT are required for event registration
		if(!is_array($arFields) || count($arFields) === 0 || !isset($arFields['TYPE_ID']) || !isset($arFields['SUBJECT']))
		{
			$dbRes = self::GetList(
				array(),
				array('=ID' => $ID),
				false,
				false,
				array('TYPE_ID', 'SUBJECT')
			);

			$arFields = $dbRes->Fetch();
			if(!$arFields)
			{
				self::RegisterError(array('text' => 'Activity not found.'));
				return false;
			}
		}

		$eventType = strtoupper(strval($eventType));
		if($eventType === '')
		{
			$eventType = 'ADD';
		}

		$suffix = self::GetEventMessageSuffix($arFields);
		if($suffix !== '')
		{
			$suffix = "_{$suffix}";
		}

		$eventName = GetMessage(
			"CRM_ACTIVITY_FILE_{$eventType}{$suffix}",
			array('#NAME#' => self::GetEventName($arFields))
		);

		$arBindings = isset($arFields['BINDINGS']) ? $arFields['BINDINGS'] : self::GetBindings($ID);
		foreach($arBindings as &$arBinding)
		{
			self::RegisterEvents(
				$arBinding['OWNER_TYPE_ID'],
				$arBinding['OWNER_ID'],
				array(
					array(
						'EVENT_NAME' => $eventName,
						'EVENT_TEXT_1' => $fileInfo['FILE_NAME'],
						'EVENT_TEXT_2' => '',
						'USER_ID' => isset($arFields['EDITOR_ID']) ? $arFields['EDITOR_ID'] : 0
					)
				),
				$checkPerms
			);

		}
		unset($arBinding);

		return true;
	}
	protected static function RegisterCommunicationEvent($ID, $arFields, $arComm, $eventType, $checkPerms = true)
	{
		if(!is_array($arComm))
		{
			return false;
		}

		// 'TYPE_ID' and SUBJECT are required for event registration
		if(!is_array($arFields) || count($arFields) === 0 || !isset($arFields['TYPE_ID']) || !isset($arFields['SUBJECT']))
		{
			$dbRes = self::GetList(array(), array('=ID' => $ID), false, false, array('TYPE_ID', 'SUBJECT'));
			$arFields = $dbRes->Fetch();
			if(!$arFields)
			{
				self::RegisterError(array('text' => 'Activity not found.'));
				return false;
			}
		}

		$eventType = strtoupper(strval($eventType));
		if($eventType === '')
		{
			$eventType = 'ADD';
		}

		$suffix = self::GetEventMessageSuffix($arFields);
		if($suffix !== '')
		{
			$suffix = "_{$suffix}";
		}

		$eventName = GetMessage(
			"CRM_ACTIVITY_COMM_{$arComm['TYPE']}_{$eventType}{$suffix}",
			array('#NAME#' => self::GetEventName($arFields))
		);

		$arBindings = isset($arFields['BINDINGS']) ? $arFields['BINDINGS'] : self::GetBindings($ID);
		foreach($arBindings as &$arBinding)
		{
			self::RegisterEvents(
				$arBinding['OWNER_TYPE_ID'],
				$arBinding['OWNER_ID'],
				array(
					array(
						'EVENT_NAME' => $eventName,
						'EVENT_TEXT_1' => $arComm['VALUE'],
						'EVENT_TEXT_2' => '',
						'USER_ID' => isset($arFields['EDITOR_ID']) ? $arFields['EDITOR_ID'] : 0
					)
				),
				$checkPerms
			);
		}
		unset($arBinding);

		return true;
	}
	public static function GetActivityType(&$arFields)
	{
		return is_array($arFields) && isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
	}
	protected static function RegisterEvents($ownerTypeID, $ownerID, $arEvents, $checkPerms)
	{
		$CCrmEvent = new CCrmEvent();
		foreach($arEvents as $arEvent)
		{
			$arEvent['EVENT_TYPE'] = 1;
			$arEvent['ENTITY_TYPE'] = CCrmOwnerType::ResolveName($ownerTypeID);
			$arEvent['ENTITY_ID'] = $ownerID;
			$arEvent['ENTITY_FIELD'] = 'ACTIVITIES';

			if(!isset($arEvent['USER_ID']) || $arEvent['USER_ID'] <= 0)
			{
				$arEvent['USER_ID']  = CCrmSecurityHelper::GetCurrentUserID();
			}

			$CCrmEvent->Add($arEvent, $checkPerms);
		}

		return true;
	}
	protected static function GetUserPermissions()
	{
		if(self::$USER_PERMISSIONS === null)
		{
			self::$USER_PERMISSIONS = CCrmPerms::GetCurrentUserPermissions();
		}

		return self::$USER_PERMISSIONS;
	}
	public static function CheckCreatePermission($ownerTypeID, $userPermissions = null)
	{
		$ownerTypeID = intval($ownerTypeID);

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		return !$userPermissions->HavePerm(CCrmOwnerType::ResolveName($ownerTypeID), BX_CRM_PERM_NONE, 'ADD');
	}
	public static function CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions = null)
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		if($ownerTypeID <= 0)
		{
			return true;
		}

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);

		if($ownerID <= 0)
		{
			return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'WRITE');
		}

		$attrs = $userPermissions->GetEntityAttr($ownerTypeName, $ownerID);
		return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'WRITE')
			&& $userPermissions->CheckEnityAccess($ownerTypeName, 'WRITE', isset($attrs[$ownerID]) ? $attrs[$ownerID] : array());
	}
	public static function CheckItemUpdatePermission(array $fields, $userPermissions = null)
	{
		$ID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($ID <=  0)
		{
			return false;
		}

		$bindings = self::GetBindings($ID);
		if(is_array($bindings) && !empty($bindings))
		{
			foreach($bindings as &$binding)
			{
				if(!self::CheckUpdatePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $userPermissions))
				{
					return false;
				}
			}
			unset($binding);
			return true;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : CCrmOwnerType::Undefined;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		return $ownerID > 0
			&& CCrmOwnerType::IsDefined($ownerTypeID)
			&& self::CheckUpdatePermission($ownerTypeID, $ownerID, $userPermissions);
	}
	public static function CheckCompletePermission($ownerTypeID, $ownerID, $userPermissions = null, $params = null)
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		if($ownerTypeID <= 0)
		{
			return true;
		}

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		if(is_array($params))
		{
			$ID = isset($params['ID']) ? $params['ID'] : 0;
			$fields = isset($params['FIELDS']) ? $params['FIELDS'] : null;
			if(!is_array($fields) && $ID > 0)
			{
				$fields = self::GetByID($ID, false);
			}

			if(is_array($fields))
			{
				$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : CCrmActivityType::Undefined;
				$associatedEntityID = isset($fields['ASSOCIATED_ENTITY_ID']) ? (int)$fields['ASSOCIATED_ENTITY_ID'] : 0;
			}
			else
			{
				$typeID = CCrmActivityType::Undefined;
				$associatedEntityID = 0;
			}

			if($typeID === CCrmActivityType::Task && $associatedEntityID > 0)
			{
				return Bitrix\Crm\Integration\TaskManager::checkCompletePermission(
					$associatedEntityID,
					$userPermissions->GetUserID()
				);
			}
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);
		if($ownerID <= 0)
		{
			return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'WRITE');
		}

		$attrs = $userPermissions->GetEntityAttr($ownerTypeName, $ownerID);
		return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'WRITE')
			&& $userPermissions->CheckEnityAccess($ownerTypeName, 'WRITE', isset($attrs[$ownerID]) ? $attrs[$ownerID] : array());
	}
	public static function CheckDeletePermission($ownerTypeID, $ownerID, $userPermissions = null)
	{
		$ownerTypeID = (int)$ownerTypeID;
		$ownerID = (int)$ownerID;

		if($ownerTypeID <= 0 || $ownerID <= 0)
		{
			return true;
		}

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);

		$attrs = $userPermissions->GetEntityAttr($ownerTypeName, $ownerID);
		return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'DELETE')
			&& $userPermissions->CheckEnityAccess($ownerTypeName, 'DELETE', isset($attrs[$ownerID]) ? $attrs[$ownerID] : array());
	}
	public static function CheckItemDeletePermission(array $fields, $userPermissions = null)
	{
		$ID = isset($fields['ID']) ? (int)$fields['ID'] : 0;
		if($ID <=  0)
		{
			return false;
		}

		$bindings = self::GetBindings($ID);
		if(is_array($bindings) && !empty($bindings))
		{
			foreach($bindings as &$binding)
			{
				if(!self::CheckDeletePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'], $userPermissions))
				{
					return false;
				}
			}
			unset($binding);
			return true;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? (int)$fields['OWNER_TYPE_ID'] : CCrmOwnerType::Undefined;
		$ownerID = isset($fields['OWNER_ID']) ? (int)$fields['OWNER_ID'] : 0;

		return $ownerID > 0
			&& CCrmOwnerType::IsDefined($ownerTypeID)
			&& self::CheckDeletePermission($ownerTypeID, $ownerID, $userPermissions);
	}
	public static function CheckReadPermission($ownerTypeID, $ownerID, $userPermissions = null)
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);

		if($ownerTypeID <= 0)
		{
			return true;
		}

		if(!$userPermissions)
		{
			$userPermissions = self::GetUserPermissions();
		}

		$ownerTypeName = CCrmOwnerType::ResolveName($ownerTypeID);

		if($ownerID <= 0)
		{
			return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'READ');
		}

		$attrs = $userPermissions->GetEntityAttr($ownerTypeName, $ownerID);
		return !$userPermissions->HavePerm($ownerTypeName, BX_CRM_PERM_NONE, 'READ')
			&& $userPermissions->CheckEnityAccess($ownerTypeName, 'READ', isset($attrs[$ownerID]) ? $attrs[$ownerID] : array());
	}
	protected static function ReadContactCommunication(&$arRes, $communicationType)
	{
		$item = array(
			'ENTITY_ID' => $arRes['ELEMENT_ID'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
			'TYPE' => $communicationType,
			'VALUE' => $arRes['VALUE'],
			'ENTITY_SETTINGS' => array(
				'NAME' => $arRes['NAME'],
				'SECOND_NAME' => $arRes['SECOND_NAME'],
				'LAST_NAME' => $arRes['LAST_NAME'],
				'COMPANY_TITLE' => $arRes['COMPANY_TITLE']
			)
		);

		self::PrepareCommunicationInfo($item);
		return $item;
	}
	protected static function ReadCompanyCommunication(&$arRes, $communicationType)
	{
		$item = array(
			'ENTITY_ID' => $arRes['ELEMENT_ID'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
			'TYPE' => $communicationType,
			'VALUE' => $arRes['VALUE'],
			'ENTITY_SETTINGS' => array(
				'COMPANY_TITLE' => $arRes['COMPANY_TITLE']
			)
		);

		self::PrepareCommunicationInfo($item);
		return $item;
	}
	protected static function ReadLeadCommunication(&$arRes, $communicationType)
	{
		$item = array(
			'ENTITY_ID' => $arRes['ELEMENT_ID'],
			'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
			'TYPE' => $communicationType,
			'VALUE' => $arRes['VALUE'],
			'ENTITY_SETTINGS' => array(
				'LEAD_TITLE' => $arRes['LEAD_TITLE'],
				'NAME' => $arRes['NAME'],
				'SECOND_NAME' => $arRes['SECOND_NAME'],
				'LAST_NAME' => $arRes['LAST_NAME'],
			)
		);

		self::PrepareCommunicationInfo($item);
		return $item;
	}
	protected static function CreateLogicalField($fieldName, &$arFields)
	{
		global $DB;

		$fieldName = strval($fieldName);

		if($fieldName === 'TYPE_NAME')
		{
			if(isset(self::$FIELDS_CACHE[LANGUAGE_ID]) && isset(self::$FIELDS_CACHE[LANGUAGE_ID]['TYPE_NAME']))
			{
				$arFields['TYPE_NAME'] = self::$FIELDS_CACHE[LANGUAGE_ID]['TYPE_NAME'];
				return;
			}

			$arTypeDescr = CCrmActivityType::GetAllDescriptions();
			if(count($arTypeDescr) == 0)
			{
				return;
			}

			$sql = 'CASE '.self::TABLE_ALIAS.'.TYPE_ID';
			foreach($arTypeDescr as $typeID=>&$typeDescr)
			{
				$sql .= " WHEN {$typeID} THEN '{$DB->ForSql($typeDescr)}'";
			}
			unset($typeDescr);
			$sql .= ' END';

			if(!isset(self::$FIELDS_CACHE[LANGUAGE_ID]))
			{
				self::$FIELDS_CACHE[LANGUAGE_ID] = array();
			}
			$arFields['TYPE_NAME'] = self::$FIELDS_CACHE[LANGUAGE_ID]['TYPE_NAME'] = array('FIELD' => $sql, 'TYPE' => 'string');
		}
	}
	public static function GetCommunicationsByOwner($entityType, $entityID, $communicationType)
	{
		global $DB;
		$entityType =  strtoupper(strval($entityType));
		$entityTypeID =  CCrmOwnerType::ResolveID($entityType);
		$entityID = intval($entityID);
		$communicationType = strval($communicationType);

		$commTableName = CCrmActivity::COMMUNICATION_TABLE_NAME;
		$sql = "SELECT ID, ENTITY_ID, ENTITY_TYPE_ID, VALUE FROM {$commTableName} WHERE OWNER_ID = {$entityID} AND OWNER_TYPE_ID = {$entityTypeID} AND TYPE = '{$DB->ForSql($communicationType)}' ORDER BY ID DESC";

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = array(
				'ENTITY_ID' => $arRes['ENTITY_ID'],
				'ENTITY_TYPE_ID' => $arRes['ENTITY_TYPE_ID'],
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName($arRes['ENTITY_TYPE_ID']),
				'TYPE' => $communicationType,
				'VALUE' => $arRes['VALUE']
			);
		}
		return $result;
	}
	public static function FindContactCommunications($needle, $communicationType, $top = 50)
	{
		$needle = strval($needle);
		$communicationType = strval($communicationType);
		$top = intval($top);

		if($needle === '')
		{
			return array();
		}

		global $DB;
		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$contactTableName = CCrmContact::TABLE_NAME;
		$companyTableName = CCrmCompany::TABLE_NAME;
		$result = array();

		if($communicationType === '')
		{
			//Search by FULL_NAME
			$sql  = "SELECT C.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, CO.TITLE COMPANY_TITLE FROM {$contactTableName} C LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID WHERE C.FULL_NAME LIKE '{$DB->ForSqlLike('%'.$needle.'%')}'";
			if($top > 0)
			{
				$sql = $DB->TopSql($sql, $top);
			}

			$dbRes = $DB->Query(
				$sql,
				false,
				'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
			);

			while($arRes = $dbRes->Fetch())
			{
				$result[] = CAllCrmActivity::ReadContactCommunication($arRes, $communicationType);
			}

			return $result;
		}

		//Search by FULL_NAME
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND C.FULL_NAME LIKE '{$DB->ForSqlLike('%'.$needle.'%')}' LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadContactCommunication($arRes, $communicationType);
		}

		//Search by VALUE
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND FM.VALUE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}' LEFT OUTER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadContactCommunication($arRes, $communicationType);
		}

		return $result;
	}
	public static function FindCompanyCommunications($needle, $communicationType, $top = 50)
	{
		$needle = strval($needle);
		$communicationType = strval($communicationType);
		$top = intval($top);

		if($needle === '')
		{
			return array();
		}

		global $DB;
		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$companyTableName = CCrmCompany::TABLE_NAME;
		$result = array();

		if($communicationType === '')
		{
			//Search by FULL_NAME
			$sql  = "SELECT CO.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, CO.TITLE AS COMPANY_TITLE FROM {$companyTableName} CO WHERE CO.TITLE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}'";
			if($top > 0)
			{
				$sql = $DB->TopSql($sql, $top);
			}

			$dbRes = $DB->Query(
				$sql,
				false,
				'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
			);

			while($arRes = $dbRes->Fetch())
			{
				$result[] = CAllCrmActivity::ReadCompanyCommunication($arRes, $communicationType);
			}

			return $result;
		}

		//Search by FULL_NAME
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, CO.TITLE AS COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$companyTableName} CO ON FM.ELEMENT_ID = CO.ID AND FM.ENTITY_ID = 'COMPANY' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND CO.TITLE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}'";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadCompanyCommunication($arRes, $communicationType);
		}

		//Search by VALUE
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, CO.TITLE AS COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$companyTableName} CO ON FM.ELEMENT_ID = CO.ID AND FM.ENTITY_ID = 'COMPANY' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND FM.VALUE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}'";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadCompanyCommunication($arRes, $communicationType);
		}

		return $result;
	}
	public static function FindLeadCommunications($needle, $communicationType, $top = 50)
	{
		$needle = strval($needle);
		$communicationType = strval($communicationType);

		if($needle === '')
		{
			return array();
		}

		global $DB;
		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$leadTableName = CCrmLead::TABLE_NAME;
		$result = array();

		if($communicationType === '')
		{
			//Search by TITLE and FULL_NAME
			$sql  = "SELECT L.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.TITLE AS LEAD_TITLE FROM {$leadTableName} L WHERE L.TITLE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}' OR L.FULL_NAME LIKE '{$DB->ForSqlLike('%'.$needle.'%')}'";
			if($top > 0)
			{
				$sql = $DB->TopSql($sql, $top);
			}

			$dbRes = $DB->Query(
				$sql,
				false,
				'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
			);

			while($arRes = $dbRes->Fetch())
			{
				$result[] = CAllCrmActivity::ReadLeadCommunication($arRes, $communicationType);
			}

			return $result;
		}

		//Search by TITLE and FULL_NAME
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.TITLE AS LEAD_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$leadTableName} L ON FM.ELEMENT_ID = L.ID AND FM.ENTITY_ID = 'LEAD' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND (L.TITLE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}' OR L.FULL_NAME LIKE '{$DB->ForSqlLike('%'.$needle.'%')}')";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadLeadCommunication($arRes, $communicationType);
		}

		//Search by VALUE
		$sql  = "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, L.NAME, L.SECOND_NAME, L.LAST_NAME, L.TITLE AS LEAD_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$leadTableName} L ON FM.ELEMENT_ID = L.ID AND FM.ENTITY_ID = 'LEAD' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' AND FM.VALUE LIKE '{$DB->ForSqlLike('%'.$needle.'%')}'";
		if($top > 0)
		{
			$sql = $DB->TopSql($sql, $top);
		}

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		while($arRes = $dbRes->Fetch())
		{
			$result[] = CAllCrmActivity::ReadLeadCommunication($arRes, $communicationType);
		}

		return $result;
	}
	public static function GetCompanyCommunications($companyID, $communicationType)
	{
		global $DB;
		$companyID = intval($companyID);

		$fieldMultiTableName = CCrmActivity::FIELD_MULTI_TABLE_NAME;
		$contactTableName = CCrmContact::TABLE_NAME;
		$companyTableName = CCrmCompany::TABLE_NAME;

		$sql  = $communicationType !== ''
			? "SELECT FM.ELEMENT_ID, FM.VALUE_TYPE, FM.VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, CO.TITLE COMPANY_TITLE FROM {$fieldMultiTableName} FM INNER JOIN {$contactTableName} C ON FM.ELEMENT_ID = C.ID AND FM.ENTITY_ID = 'CONTACT' AND FM.TYPE_ID = '{$DB->ForSql($communicationType)}' INNER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID AND C.COMPANY_ID = {$companyID}"
			: "SELECT C.ID AS ELEMENT_ID, '' AS VALUE_TYPE, '' AS VALUE, C.NAME, C.SECOND_NAME, C.LAST_NAME, CO.TITLE COMPANY_TITLE FROM {$contactTableName} C INNER JOIN {$companyTableName} CO ON C.COMPANY_ID = CO.ID AND C.COMPANY_ID = {$companyID}";

		$dbRes = $DB->Query(
			$sql,
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		$result = array();
		while($arRes = $dbRes->Fetch())
		{
			$result[] = array(
				'ENTITY_ID' => $arRes['ELEMENT_ID'],
				'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
				'ENTITY_TYPE' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'TYPE' => $communicationType,
				'VALUE' => $arRes['VALUE'],
				'VALUE_TYPE' => $arRes['VALUE_TYPE'],
				'ENTITY_SETTINGS' => array(
					'NAME' => $arRes['NAME'],
					'SECOND_NAME' => $arRes['SECOND_NAME'],
					'LAST_NAME' => $arRes['LAST_NAME'],
					'COMPANY_TITLE' => $arRes['COMPANY_TITLE']
				)
			);
		}
		return $result;
	}
	public static function GetStorageTypeID($ID)
	{
		$ID = intval($ID);
		$dbRes = CCrmActivity::GetList(array(), array('ID'=> $ID), false, false, array('STORAGE_TYPE_ID'));
		$arRes = $dbRes->Fetch();
		return is_array($arRes) && isset($arRes['STORAGE_TYPE_ID']) ? intval($arRes['STORAGE_TYPE_ID']) : StorageType::Undefined;
	}
	public static function GetDefaultStorageTypeID()
	{
		if(self::$STORAGE_TYPE_ID === StorageType::Undefined)
		{
			self::$STORAGE_TYPE_ID = intval(CUserOptions::GetOption('crm', 'activity_storage_type_id', StorageType::Undefined));
			if(self::$STORAGE_TYPE_ID === StorageType::Undefined
				|| !StorageType::isDefined(self::$STORAGE_TYPE_ID))
			{
				self::$STORAGE_TYPE_ID = StorageType::getDefaultTypeID();
			}
		}
		return self::$STORAGE_TYPE_ID;
	}
	public static function SetDefaultStorageTypeID($storageTypeID)
	{
		$storageTypeID = (int)$storageTypeID;

		if(!StorageType::isDefined($storageTypeID))
		{
			$storageTypeID = StorageType::getDefaultTypeID();
		}

		if($storageTypeID === self::$STORAGE_TYPE_ID)
		{
			return;
		}

		self::$STORAGE_TYPE_ID = $storageTypeID;
		CUserOptions::SetOption('crm', 'activity_storage_type_id', $storageTypeID);
	}
	public static function PrepareUrn(&$arFields)
	{
		if(!is_array($arFields))
		{
			return '';
		}

		$ID = isset($arFields['ID']) ? intval($arFields['ID']) : 0;
		if($ID <= 0)
		{
			return '';
		}

		// URN: [ID]-[CHECK_WORD]-[OWNER_ID]-[OWNER_TYPE_ID]
		$urn = "{$ID}-".randString(6, 'ABCDEFGHIJKLNMOPQRSTUVWXYZ0123456789');

		//$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
		//$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : 0;
		//if($ownerID > 0 && $ownerTypeID > 0)
		//{
		//	$urn .= "-{$ownerID}-{$ownerTypeID}";
		//}

		return $urn;
	}
	public static function InjectUrnInMessage(&$messageData, $urn, $codeAllocation = false)
	{
		if(!is_array($messageData) || empty($messageData))
		{
			return false;
		}

		if($codeAllocation === false)
		{
			$codeAllocation = CCrmEMailCodeAllocation::GetCurrent();
		}

		if($codeAllocation === CCrmEMailCodeAllocation::Subject)
		{
			$messageData['SUBJECT'] = CCrmActivity::InjectUrnInSubject(
				$urn,
				isset($messageData['SUBJECT']) ? $messageData['SUBJECT'] : ''
			);

			return true;
		}
		elseif($codeAllocation === CCrmEMailCodeAllocation::Body)
		{
			$messageData['BODY'] = CCrmActivity::InjectUrnInBody(
				$urn,
				isset($messageData['BODY']) ? $messageData['BODY'] : '',
				isset($messageData['BODY_TYPE']) ? $messageData['BODY_TYPE'] : 'html'
			);
			return true;
		}

		return false;
	}
	public static function InjectUrnInSubject($urn, $str)
	{
		$urn = strval($urn);
		$str = strval($str);

		if($urn === '')
		{
			return $str;
		}

		if($str !== '')
		{
			$str = rtrim(preg_replace(self::$URN_REGEX, '', $str));
		}

		if($str === '')
		{
			return "[CRM:{$urn}]";
		}

		return "{$str} [CRM:{$urn}]";
	}
	public static function InjectUrnInBody($urn, $str, $type = 'html')
	{
		$urn = strval($urn);
		$str = strval($str);
		$type = strtolower(strval($type));
		if($type === '')
		{
			$type = 'html';
		}

		if($urn === '')
		{
			return $str;
		}

		if($str !== '')
		{
			if($type === 'html')
			{
				//URN already encoded
				$str = rtrim(preg_replace(self::$URN_BODY_HTML_ENTITY_REGEX.BX_UTF_PCRE_MODIFIER, '', $str));
			}
			else
			{
				$str = rtrim(preg_replace(self::$URN_BODY_REGEX.BX_UTF_PCRE_MODIFIER, '', $str));
			}
		}

		if($str !== '')
		{
			$str .= $type === 'html' ? '<br/>' : CCrmEMail::GetEOL();
		}

		$str .='[msg:'.strtolower($urn).']';

		return $str;
	}
	public static function ExtractUrnFromMessage(&$messageData, $codeAllocation = false)
	{
		if(!is_array($messageData) || empty($messageData))
		{
			return '';
		}

		if($codeAllocation === false)
		{
			$codeAllocation = CCrmEMailCodeAllocation::GetCurrent();
		}

		$subject = isset($messageData['SUBJECT']) ? $messageData['SUBJECT'] : '';
		$body = isset($messageData['BODY']) ? $messageData['BODY'] : '';

		$result = '';
		if($codeAllocation === CCrmEMailCodeAllocation::Subject)
		{
			$result = CCrmActivity::ExtractUrnFromSubject($subject);
			if($result === '')
			{
				$result = CCrmActivity::ExtractUrnFromBody($body);
			}
		}
		elseif($codeAllocation === CCrmEMailCodeAllocation::Body)
		{
			$result = CCrmActivity::ExtractUrnFromBody($body);
			if($result === '')
			{
				$result = CCrmActivity::ExtractUrnFromSubject($subject);
			}
		}
		return $result;
	}
	public static function ExtractUrnFromSubject($str)
	{
		$str = strval($str);

		if($str === '')
		{
			return '';
		}

		$matches = array();
		if(preg_match(self::$URN_REGEX, $str, $matches) !== 1)
		{
			return '';
		}
		return isset($matches['urn']) ? $matches['urn'] : '';
	}
	public static function ExtractUrnFromBody($str)
	{
		$str = strval($str);

		if($str === '')
		{
			return '';
		}

		$matches = array();
		if(preg_match(self::$URN_BODY_REGEX.BX_UTF_PCRE_MODIFIER, $str, $matches) !== 1)
		{
			return '';
		}
		return isset($matches['urn']) ? $matches['urn'] : '';
	}
	public static function ClearUrn($str)
	{
		$str = strval($str);

		if($str === '')
		{
			return $str;
		}

		return rtrim(preg_replace(self::$URN_REGEX, '', $str));
	}
	public static function ParseUrn($urn)
	{
		$urn = strval($urn);

		$result = array(
			'URN' => $urn,
			'ID' => 0,
			'CHECK_WORD' => ''
		);

		if($urn !== '')
		{
			$ary =  explode('-', $urn);
			if(count($ary) > 1)
			{
				$result['ID'] = intval($ary[0]);
				$result['CHECK_WORD'] = $ary[1];
			}
		}

		return $result;
	}
	public static function GetNearest($ownerTypeID, $ownerID, $userID)
	{
		global $DB;

		$tableName = CCrmActivity::TABLE_NAME;
		$bindingTableName = CCrmActivity::BINDING_TABLE_NAME;
		$deadline = $DB->DateToCharFunction('a.DEADLINE', 'FULL');

		$userID = intval($userID);
		if($userID > 0)
		{
			$sql = "SELECT a.ID, {$deadline} AS DEADLINE_FORMATTED, a.DEADLINE FROM {$tableName} a INNER JOIN {$bindingTableName} b ON a.ID = b.ACTIVITY_ID AND a.COMPLETED = 'N' AND a.RESPONSIBLE_ID = {$userID} AND a.DEADLINE IS NOT NULL AND b.OWNER_TYPE_ID = {$ownerTypeID} AND b.OWNER_ID = {$ownerID} ORDER BY a.DEADLINE ASC";
		}
		else
		{
			$sql = "SELECT a.ID, {$deadline} AS DEADLINE_FORMATTED, a.DEADLINE FROM {$tableName} a INNER JOIN {$bindingTableName} b ON a.ID = b.ACTIVITY_ID AND a.COMPLETED = 'N' AND a.DEADLINE IS NOT NULL AND b.OWNER_TYPE_ID = {$ownerTypeID} AND b.OWNER_ID = {$ownerID} ORDER BY a.DEADLINE ASC";
		}

		$dbResult = $DB->Query(
			$DB->TopSql($sql, 1),
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		$arResult = $dbResult ? $dbResult->Fetch() : null;
		if($arResult)
		{
			$arResult['DEADLINE'] = $arResult['DEADLINE_FORMATTED'];
			unset($arResult['DEADLINE_FORMATTED']);
		}

		return $arResult;
	}
	public static function SynchronizeUserActivity($ownerTypeID, $ownerID, $userID)
	{
		$ownerTypeID = intval($ownerTypeID);
		$ownerID = intval($ownerID);
		$userID = intval($userID);

		if($userID < 0)
		{
			$userID = 0;
		}

		if($ownerTypeID <= CCrmOwnerType::Undefined || $ownerID <= 0)
		{
			return;
		}

		$arResult = CCrmActivity::GetNearest($ownerTypeID, $ownerID, $userID);
		if(is_array($arResult))
		{
			$activityID = isset($arResult['ID']) ? intval($arResult['ID']) : 0;
			$deadline = isset($arResult['DEADLINE']) ? $arResult['DEADLINE'] : '';
		}
		else
		{
			$activityID = 0;
			$deadline = '';
		}

		if($activityID > 0 && $deadline !== '')
		{
			CCrmActivity::DoSaveNearestUserActivity(
				array(
					'USER_ID' => $userID,
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE_ID' => $ownerTypeID,
					'ACTIVITY_ID' => $activityID,
					'ACTIVITY_TIME' => $deadline,
					'SORT' => ($userID > 0 ? '1' : '0').date('YmdHis', MakeTimeStamp($deadline))
				)
			);
		}
		else
		{
			global $DB;
			$tableName = CCrmActivity::USER_ACTIVITY_TABLE_NAME;
			$DB->Query(
				"DELETE FROM {$tableName} WHERE USER_ID = {$userID} AND OWNER_TYPE_ID = {$ownerTypeID} AND OWNER_ID = {$ownerID}",
				false,
				'File: '.__FILE__.'<br/>Line: '.__LINE__
			);
		}

		$counter = new CCrmUserCounter($userID, CCrmUserCounter::CurrentActivies);
		$counter->Synchronize();
	}
	public static function MakeRawFiles($storageTypeID, array $arFileIDs)
	{
		return \Bitrix\Crm\Integration\StorageManager::makeFileArray($arFileIDs, $storageTypeID);
	}
	protected static function SaveCalendarEvent(&$arFields)
	{
		$responsibleID =  isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;

		if(!($responsibleID > 0
			&& ($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)))
		{
			return false;
		}

		if (!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return false;
		}

		$arCalEventFields = array(
			'CAL_TYPE' => 'user',
			'OWNER_ID' => $responsibleID,
			'NAME' => isset($arFields['SUBJECT']) ? $arFields['SUBJECT'] : '',
			'DT_FROM' => isset($arFields['START_TIME']) ? $arFields['START_TIME'] : '',
			'DT_TO' => isset($arFields['END_TIME']) ? $arFields['END_TIME'] : '',
			'IMPORTANCE' => CCrmActivityPriority::ToCalendarEventImportance(
				isset($arFields['PRIORITY'])
					? intval($arFields['PRIORITY'])
					: CCrmActivityPriority::Low
			),
			'DESCRIPTION' => isset($arFields['DESCRIPTION']) ? $arFields['DESCRIPTION'] : ''
		);


		$associatedEntityID = isset($arFields['ASSOCIATED_ENTITY_ID']) ? intval($arFields['ASSOCIATED_ENTITY_ID']) : 0;
		if($associatedEntityID > 0)
		{
			$arCalEventFields['ID'] = $associatedEntityID;
			$arPresentEventFields = CCalendarEvent::GetById($associatedEntityID, false);
			if(is_array($arPresentEventFields))
			{
				if(isset($arPresentEventFields['RRULE']) && $arPresentEventFields['RRULE'] != '')
				{
					$arCalEventFields['RRULE'] = CCalendarEvent::ParseRRULE($arPresentEventFields['RRULE']);
				}

				if(isset($arPresentEventFields['DT_LENGTH']))
				{
					$arCalEventFields['DT_LENGTH'] = $arPresentEventFields['DT_LENGTH'];
				}
			}
		}
		if(isset($arFields['NOTIFY_TYPE']) && $arFields['NOTIFY_TYPE'] != CCrmActivityNotifyType::None)
		{
			$arCalEventFields['REMIND'] = array(
				array(
					'type' => CCrmActivityNotifyType::ToCalendarEventRemind($arFields['NOTIFY_TYPE']),
					'count' => isset($arFields['NOTIFY_VALUE']) ? intval($arFields['NOTIFY_VALUE']) : 15
				)
			);
		}

		self::$IGNORE_CALENDAR_EVENTS = true;
		// We must initialize CCalendar!
		$calendar = new CCalendar();
		$calendar->Init(
			array(
				'type'=>'user',
				'userId' => $responsibleID,
				'ownerId' => $responsibleID
			)
		);

		$result = $calendar->SaveEvent(
			array(
				'arFields' => $arCalEventFields,
				'userId' => $responsibleID,
				'autoDetectSection' => true,
				'autoCreateSection' => true
			)
		);

		$eventID = intval($result);
		$ownerID = intval($arFields['OWNER_ID']);
		$ownerTypeID = intval($arFields['OWNER_TYPE_ID']);

		$arBindings = isset($arFields['BINDINGS']) ? $arFields['BINDINGS'] : array();
		if(empty($arBindings) && $ownerID > 0 && $ownerTypeID > 0)
		{
			$arBindings[] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		if($eventID > 0 && !empty($arBindings))
		{
			$arUserFields = array();
			foreach($arBindings as &$arBinding)
			{
				$arUserFields[] = CUserTypeCrm::GetShortEntityType(CCrmOwnerType::ResolveName($arBinding['OWNER_TYPE_ID'])).'_'.$arBinding['OWNER_ID'];
			}
			unset($arBinding);

			CCalendarEvent::UpdateUserFields(
				$eventID,
				array('UF_CRM_CAL_EVENT' => $arUserFields)
			);
		}
		self::$IGNORE_CALENDAR_EVENTS = false;
		return $result;
	}
	protected static function DeleteCalendarEvent(&$arFields)
	{
		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
		if(!($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting))
		{
			return false;
		}

		$assocEntityID =  isset($arFields['ASSOCIATED_ENTITY_ID']) ? intval($arFields['ASSOCIATED_ENTITY_ID']) : 0;
		if ($assocEntityID <= 0)
		{
			return false;
		}

		if (!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return false;
		}

		self::$IGNORE_CALENDAR_EVENTS = true;
		CCalendarEvent::Delete(
			array(
				'id' => $assocEntityID,
				'bMarkDeleted' => false
			)
		);
		self::$IGNORE_CALENDAR_EVENTS = false;
		return true;
	}
	protected static function SaveTask(&$arFields)
	{
		$responsibleID =  isset($arFields['RESPONSIBLE_ID']) ? intval($arFields['RESPONSIBLE_ID']) : 0;
		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;

		if(!($responsibleID > 0 && $typeID === CCrmActivityType::Task))
		{
			return false;
		}

		if (!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return false;
		}

		$associatedEntityID = isset($arFields['ASSOCIATED_ENTITY_ID']) ? intval($arFields['ASSOCIATED_ENTITY_ID']) : 0;
		if($associatedEntityID <= 0)
		{
			return false;
		}

		$arTaskFields = array();
		if(isset($arFields['SUBJECT']))
		{
			$arTaskFields['TITLE'] = $arFields['SUBJECT'];
		}
		if(isset($arFields['END_TIME'] ))
		{
			$arTaskFields['DEADLINE'] = $arFields['END_TIME'];
		}
		if(isset($arFields['COMPLETED']) && $arFields['COMPLETED'] !== 'Y')
		{
			$arTaskFields['STATUS'] = CTasks::STATE_PENDING;
		}

		$result = true;
		if(!empty($arTaskFields))
		{
			$task = new CTasks();

			self::$TASK_OPERATIONS[$associatedEntityID] = 'U';
			$result = $task->Update($associatedEntityID, $arTaskFields);
			unset(self::$TASK_OPERATIONS[$associatedEntityID]);
		}

		if(isset($arFields['COMPLETED']) && $arFields['COMPLETED'] === 'Y')
		{
			self::$TASK_OPERATIONS[$associatedEntityID] = 'U';
			try
			{
				$currentUser = CCrmSecurityHelper::GetCurrentUserID();
				$taskItem = CTaskItem::getInstance($associatedEntityID, $currentUser > 0 ? $currentUser : 1);
				$taskItem->complete();
				$result = true;
			}
			catch (TasksException $e)
			{
				$result = false;
			}
			unset(self::$TASK_OPERATIONS[$associatedEntityID]);
		}

		return $result;
	}
	protected static function DeleteTask(&$arFields)
	{
		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
		if($typeID !== CCrmActivityType::Task)
		{
			return false;
		}

		$associatedEntityID =  isset($arFields['ASSOCIATED_ENTITY_ID']) ? intval($arFields['ASSOCIATED_ENTITY_ID']) : 0;
		if ($associatedEntityID <= 0)
		{
			return false;
		}

		if (!(IsModuleInstalled('tasks') && CModule::IncludeModule('tasks')))
		{
			return false;
		}

		self::$TASK_OPERATIONS[$associatedEntityID] = 'D';
		CTasks::Delete($associatedEntityID);
		unset(self::$TASK_OPERATIONS[$associatedEntityID]);
	}
	public static function RefreshCalendarBindings()
	{
		if (!(IsModuleInstalled('calendar') && CModule::IncludeModule('calendar')))
		{
			return false;
		}

		global $DB;
		$dbResult = $DB->Query(
			'SELECT OWNER_ID, OWNER_TYPE_ID, ASSOCIATED_ENTITY_ID FROM '.CCrmActivity::TABLE_NAME.' WHERE OWNER_ID > 0 AND OWNER_TYPE_ID > 0 AND ASSOCIATED_ENTITY_ID > 0 AND TYPE_ID IN ('.CCrmActivityType::Call.', '.CCrmActivityType::Meeting.')',
			false,
			'File: '.__FILE__.'<br>Line: '.__LINE__
		);

		if(!$dbResult)
		{
			return false;
		}

		while($arResult = $dbResult->Fetch())
		{
			$ownerID = intval($arResult['OWNER_ID']);
			$ownerTypeID = intval($arResult['OWNER_TYPE_ID']);
			$assocEntityID = intval($arResult['ASSOCIATED_ENTITY_ID']);

			if($ownerID > 0 && $ownerTypeID > 0 && $assocEntityID > 0)
			{
				CCalendarEvent::UpdateUserFields(
					$assocEntityID,
					array(
						'UF_CRM_CAL_EVENT' => array(
							CUserTypeCrm::GetShortEntityType(CCrmOwnerType::ResolveName($ownerTypeID)).'_'.$ownerID
						)
					)
				);
			}
		}

		return true;
	}
	public static function Notify(&$arFields, $schemeTypeID, $tag = '')
	{
		if(!is_array($arFields))
		{
			return false;
		}

		$responsibleID = $arFields['RESPONSIBLE_ID'] ? intval($arFields['RESPONSIBLE_ID']) : 0;
		if($responsibleID <= 0)
		{
			return false;
		}

		if($schemeTypeID === CCrmNotifierSchemeType::IncomingEmail)
		{
			$showUrl = CCrmOwnerType::GetShowUrl(
				$arFields['OWNER_TYPE_ID'] ? intval($arFields['OWNER_TYPE_ID']) : 0,
				$arFields['OWNER_ID'] ? intval($arFields['OWNER_ID']) : 0
			);

			if($showUrl === '')
			{
				return false;
			}

			$subject = isset($arFields['SUBJECT']) ? $arFields['SUBJECT'] : '';
			$addresserHtml = '';
			$communications = isset($arFields['COMMUNICATIONS']) ? $arFields['COMMUNICATIONS'] : array();
			if(!empty($communications))
			{
				$comm = $communications[0];

				$caption = '';
				if(isset($comm['ENTITY_TYPE_ID']) && isset($comm['ENTITY_ID']))
				{
					$caption = CCrmOwnerType::GetCaption($comm['ENTITY_TYPE_ID'], $comm['ENTITY_ID']);
				}
				if($caption === '')
				{
					$caption = $comm['VALUE'];
				}

				$addresserShowUrl = CCrmOwnerType::GetShowUrl(
					$comm['ENTITY_TYPE_ID'],
					$comm['ENTITY_ID']
				);

				$addresserHtml = $addresserShowUrl !== ''
					? '<a target="_blank" href="'.htmlspecialcharsbx($addresserShowUrl).'">'.htmlspecialcharsbx($caption).'</a>'
					: htmlspecialcharsbx($caption);
			}

			if($addresserHtml === '')
			{
				$messageTemplate = GetMessage('CRM_ACTIVITY_NOTIFY_MESSAGE_INCOMING_EMAIL');
				return CCrmNotifier::Notify(
					$responsibleID,
					str_replace(
						'#VIEW_URL#',
						htmlspecialcharsbx($showUrl),
						$messageTemplate
					),
					str_replace(
						'#VIEW_URL#',
						htmlspecialcharsbx(CCrmUrlUtil::ToAbsoluteUrl($showUrl)),
						$messageTemplate
					),
					$schemeTypeID,
					$tag
				);
			}

			$messageTemplate = GetMessage('CRM_ACTIVITY_NOTIFY_MESSAGE_INCOMING_EMAIL_EXT');
			return CCrmNotifier::Notify(
				$responsibleID,
				str_replace(
					array(
						'#VIEW_URL#',
						'#SUBJECT#',
						'#ADDRESSER#'
					),
					array(
						htmlspecialcharsbx($showUrl),
						htmlspecialcharsbx($subject),
						$addresserHtml
					),
					$messageTemplate
				),
				str_replace(
					array(
						'#VIEW_URL#',
						'#SUBJECT#',
						'#ADDRESSER#'
					),
					array(
						htmlspecialcharsbx(CCrmUrlUtil::ToAbsoluteUrl($showUrl)),
						htmlspecialcharsbx($subject),
						$addresserHtml
					),
					$messageTemplate
				),
				$schemeTypeID,
				$tag
			);
		}

		return false;
	}
	public static function PrepareJoin($userID, $ownerTypeID, $ownerAlias, $alias = '', $userAlias = '', $respAlias = '')
	{
		$userID = intval($userID);
		$ownerTypeID = intval($ownerTypeID);
		$ownerAlias = strval($ownerAlias);
		if($ownerAlias === '')
		{
			$ownerAlias = 'L';
		}

		$alias = strval($alias);
		if($alias === '')
		{
			$alias = 'A';
		}

		$userAlias = strval($userAlias);
		if($userAlias === '')
		{
			$userAlias = 'UA';
		}

		$respAlias = strval($respAlias);

		// Zero user is intended for nearest activity in general.
		$userTableName = CCrmActivity::USER_ACTIVITY_TABLE_NAME;
		$activityTableName = CCrmActivity::TABLE_NAME;
		if($respAlias !== '')
		{
			return "LEFT JOIN {$userTableName} {$userAlias} ON {$userAlias}.USER_ID = {$userID} AND {$userAlias}.OWNER_ID = {$ownerAlias}.ID AND {$userAlias}.OWNER_TYPE_ID = {$ownerTypeID} LEFT JOIN {$activityTableName} {$alias} ON {$alias}.ID = {$userAlias}.ACTIVITY_ID LEFT JOIN b_user {$respAlias} ON {$alias}.RESPONSIBLE_ID = {$respAlias}.ID";
		}
		else
		{
			return "LEFT JOIN {$userTableName} {$userAlias} ON {$userAlias}.USER_ID = {$userID} AND {$userAlias}.OWNER_ID = {$ownerAlias}.ID AND {$userAlias}.OWNER_TYPE_ID = {$ownerTypeID} LEFT JOIN {$activityTableName} {$alias} ON {$alias}.ID = {$userAlias}.ACTIVITY_ID";
		}
	}
	public static function IsCurrentDay($time)
	{
		if(self::$CURRENT_DAY_TIME_STAMP === null || self::$NEXT_DAY_TIME_STAMP === null)
		{
			$t = time() + CTimeZone::GetOffset();
			self::$CURRENT_DAY_TIME_STAMP = mktime(0, 0, 0, date('n', $t), date('j', $t), date('Y', $t));
			$t += 86400;
			self::$NEXT_DAY_TIME_STAMP = mktime(0, 0, 0, date('n', $t), date('j', $t), date('Y', $t));
		}

		return $time >= self::$CURRENT_DAY_TIME_STAMP && $time < self::$NEXT_DAY_TIME_STAMP;
	}
	public static function GetCurrentQuantity($userID, $ownerTypeID)
	{
		$userID = intval($userID);
		$ownerTypeID = intval($ownerTypeID);
		if($userID <= 0 || $ownerTypeID <= 0)
		{
			return 0;
		}

		$currentDay = time() + CTimeZone::GetOffset();
		$currentDayEnd = ConvertTimeStamp(mktime(23, 59, 59, date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay)), 'FULL', SITE_ID);

		global $DB;
		$currentDayEnd = $DB->CharToDateFunction($DB->ForSql($currentDayEnd), 'FULL');
		$activityTable = CCrmActivity::USER_ACTIVITY_TABLE_NAME;
		$sql = "SELECT COUNT(DISTINCT a.OWNER_ID) AS CNT FROM {$activityTable} a WHERE a.USER_ID = {$userID} AND a.OWNER_TYPE_ID = {$ownerTypeID} AND a.ACTIVITY_TIME <= {$currentDayEnd}";

		$dbResult = $DB->Query(
			$sql,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);
		$result = $dbResult->Fetch();
		return is_array($result) ? intval($result['CNT']) : 0;
	}
	public static function GetDefaultCommunicationValue($ownerTypeID, $ownerID, $commType)
	{
		$dbMultiFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => CCrmOwnerType::ResolveName($ownerTypeID), 'ELEMENT_ID' => $ownerID, 'TYPE_ID' =>  $commType)
		);

		$multiField = $dbMultiFields->Fetch();
		return is_array($multiField) ? $multiField['VALUE'] : '';
	}
	private static function RegisterLiveFeedEvent(&$arFields)
	{
		$ID = isset($arFields['ID']) ? intval($arFields['ID']) : 0;
		if($ID <= 0)
		{
			$arFields['ERROR'] = 'Could not find activity ID.';
			return false;
		}

		$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$arFields['ERROR'] = 'Could not find owner type ID.';
			return false;
		}

		$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
		if($ownerID <= 0)
		{
			$arFields['ERROR'] = 'Could not find owner ID.';
			return false;
		}

		$authorID = isset($arFields['AUTHOR_ID']) ? intval($arFields['AUTHOR_ID']) : 0;
		$editorID = isset($arFields['EDITOR_ID']) ? intval($arFields['EDITOR_ID']) : 0;
		$userID = $authorID > 0 ? $authorID : $editorID;
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUserID();
		}

		// Params are not assigned - we will use current activity only.
		$liveFeedFields = array(
			'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
			'ENTITY_ID' => $ID,
			'USER_ID' => $userID,
			'MESSAGE' => '',
			'TITLE' => ''
			//'PARAMS' => array()
		);

		$bindings = isset($arFields['BINDINGS']) && is_array($arFields['BINDINGS']) ? $arFields['BINDINGS'] : array();
		if(!empty($bindings))
		{
			$liveFeedFields['PARENTS'] = $bindings;
			$liveFeedFields['PARENT_OPTIONS'] = array(
				'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
				'ENTITY_ID_KEY' => 'OWNER_ID'
			);

			$ownerInfoOptions = array(
				'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
				'ENTITY_ID_KEY' => 'OWNER_ID',
				'ADDITIONAL_DATA' => array('LEVEL' => 2)
			);

			$additionalParents = array();
			foreach($bindings as &$binding)
			{
				$ownerTypeID = isset($binding['OWNER_TYPE_ID']) ? intval($binding['OWNER_TYPE_ID']) : CCrmOwnerType::Undefined;
				$ownerID = isset($binding['OWNER_ID']) ? intval($binding['OWNER_ID']) : 0;

				if($ownerTypeID === CCrmOwnerType::Contact && $ownerID > 0)
				{
					$owners = array();
					if(CCrmOwnerType::TryGetOwnerInfos(CCrmOwnerType::Contact, $ownerID, $owners, $ownerInfoOptions))
					{
						$additionalParents = array_merge($additionalParents, $owners);
					}
				}
			}
			unset($binding);
			if(!empty($additionalParents))
			{
				$liveFeedFields['PARENTS'] = array_merge($bindings, $additionalParents);
			}
		}

		self::PrepareStorageElementIDs($arFields);
		$arStorageElementID = $arFields["STORAGE_ELEMENT_IDS"];
		if (!empty($arStorageElementID))
		{
			if ($arFields["STORAGE_TYPE_ID"] == StorageType::WebDav)
			{
				$liveFeedFields["UF_SONET_LOG_DOC"] = $arStorageElementID;
			}
			else if ($arFields["STORAGE_TYPE_ID"] == StorageType::Disk)
			{
				$liveFeedFields["UF_SONET_LOG_DOC"] = array();
				//We have to add prefix Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX to file ID
				foreach($arStorageElementID as $elementID)
				{
					$liveFeedFields["UF_SONET_LOG_DOC"][] = "n{$elementID}";
				}
			}
			else
			{
				$liveFeedFields["UF_SONET_LOG_FILE"] = $arStorageElementID;
			}
		}

		if (
			$arFields['TYPE_ID'] == CCrmActivityType::Task
			&& isset($arFields["ASSOCIATED_ENTITY_ID"])
			&& intval($arFields["ASSOCIATED_ENTITY_ID"]) > 0
			&& CModule::IncludeModule("tasks")
		)
		{
			$dbTask = CTasks::GetByID($arFields["ASSOCIATED_ENTITY_ID"], false);
			if ($arTask = $dbTask->Fetch())
			{
				$ufDocID = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFieldValue("TASKS_TASK", "UF_TASK_WEBDAV_FILES", $arTask["ID"], LANGUAGE_ID);
				if ($ufDocID)
				{
					$liveFeedFields["UF_SONET_LOG_DOC"] = $ufDocID;
				}
			}
		}

		$eventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add);
		if(!(is_int($eventID) && $eventID > 0) && isset($liveFeedFields['ERROR']))
		{
			$arFields['ERROR'] = $liveFeedFields['ERROR'];
		}
		else
		{
			if ($arTask)
			{
				$arSocnetRights = array();

				$arTaskParticipant = CTaskNotifications::GetRecipientsIDs(
					$arTask,
					false		// don't exclude current user
				);

				$arSocnetRights = CTaskNotifications::__UserIDs2Rights($arTaskParticipant);

				if (
					isset($arTask['GROUP_ID'])
					&& intval($arTask['GROUP_ID']) > 0
				)
				{
					$perm = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_GROUP, $arTask['GROUP_ID'], "tasks", "view");

					$arSocnetRights = array_merge(
						$arSocnetRights,
						array(
							'SG'.$arTask['GROUP_ID'], 
							'SG'.$arTask['GROUP_ID'].'_'.$perm
						)
					);
				}

				CSocNetLogRights::DeleteByLogID($eventID);
				CSocNetLogRights::Add($eventID, $arSocnetRights);
			}

			if (
				intval($arFields["RESPONSIBLE_ID"]) > 0
				&& $arFields["RESPONSIBLE_ID"] != $userID
				&& CModule::IncludeModule("im")
			)
			{
				switch ($arFields['TYPE_ID'])
				{
					case CCrmActivityType::Call:
						$type = 'CALL';
						break;
					case CCrmActivityType::Meeting:
						$type = 'MEETING';
						break;
					default:
						$type = false;
				}

				if ($type)
				{
					$url = "/crm/stream/?log_id=#log_id#";
					$url = str_replace(array("#log_id#"), array($eventID), $url);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $arFields["RESPONSIBLE_ID"],
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $eventID,
						"NOTIFY_EVENT" => "activity_add",
						"NOTIFY_TAG" => "CRM|ACTIVITY|".$ID,
						"NOTIFY_MESSAGE" => GetMessage("CRM_ACTIVITY_".$type."_RESPONSIBLE_IM_NOTIFY", Array("#title#" => '<a href="'.$url.'">'.htmlspecialcharsbx($arFields['SUBJECT']).'</a>')),
						"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_ACTIVITY_".$type."_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['SUBJECT'])))." (".$serverName.$url.")"
					);
					CIMNotify::Add($arMessageFields);
				}
			}
		}

		return $eventID;
	}
	private static function SynchronizeLiveFeedEvent($activityID, $params)
	{
		if(!is_array($params))
		{
			$params = array();
		}

		$processBindings = isset($params['PROCESS_BINDINGS']) ? (bool)$params['PROCESS_BINDINGS'] : false;
		$bindings = isset($params['BINDINGS']) && is_array($params['BINDINGS']) ? $params['BINDINGS'] : array();
		$hasBindings = !empty($bindings);
		if($processBindings)
		{
			CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Activity, $activityID, array('QUICK' => $hasBindings));
		}

		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ENTITY_ID' => $activityID
			),
			array('ID', 'EVENT_ID')
		);

		if(empty($slEntities))
		{
			return false;
		}

		global $DB;
		foreach($slEntities as &$slEntity)
		{
			$slID = intval($slEntity['ID']);
			$slEventType = $slEntity['EVENT_ID'];

			if(isset($params['REFRESH_DATE']) ? (bool)$params['REFRESH_DATE'] : false)
			{
				//Update LOG_UPDATE for force event to rise in global feed
				//Update LOG_DATE for force event to rise in entity feed
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'=LOG_UPDATE' => $DB->CurrentTimeFunction(),
						'=LOG_DATE' => $DB->CurrentTimeFunction()
					)
				);
			}
			else
			{
				//HACK: FAKE UPDATE FOR INVALIDATE CACHE
				CCrmLiveFeed::UpdateLogEvent(
					$slID,
					array(
						'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
						'ENTITY_ID' => $activityID,
					)
				);
			}
/*
					'START_RESPONSIBLE_ID' => $arPrevEntity['RESPONSIBLE_ID'],
					'FINAL_RESPONSIBLE_ID' => $responsibleID
*/
			$userID = (intval($params['EDITOR_ID']) > 0 ? $params['EDITOR_ID'] : CCrmSecurityHelper::GetCurrentUserID());
			if (
				intval($params['START_RESPONSIBLE_ID']) != intval($params['FINAL_RESPONSIBLE_ID'])
				&& CModule::IncludeModule("im")
			)
			{
				switch ($params['TYPE_ID'])
				{
					case CCrmActivityType::Call:
						$type = 'CALL';
						break;
					case CCrmActivityType::Meeting:
						$type = 'MEETING';
						break;
					default:
						$type = false;
				}

				if ($type)
				{
					$url = "/crm/stream/?log_id=#log_id#";
					$url = str_replace(array("#log_id#"), array($slID), $url);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"FROM_USER_ID" => $userID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $slID,
						"NOTIFY_EVENT" => "activity_add",
						"NOTIFY_TAG" => "CRM|ACTIVITY|".$activityID
					);

					if (intval($params['START_RESPONSIBLE_ID']) != $userID)
					{
						$arMessageFields["TO_USER_ID"] = $params['START_RESPONSIBLE_ID'];
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("CRM_ACTIVITY_".$type."_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => '<a href="'.$url.'">'.htmlspecialcharsbx($params['SUBJECT']).'</a>'));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("CRM_ACTIVITY_".$type."_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($params['SUBJECT'])))." (".$serverName.$url.")";

						CIMNotify::Add($arMessageFields);
					}

					if (intval($params['FINAL_RESPONSIBLE_ID']) != $userID)
					{
						$arMessageFields["TO_USER_ID"] = $params['FINAL_RESPONSIBLE_ID'];
						$arMessageFields["NOTIFY_MESSAGE"] = GetMessage("CRM_ACTIVITY_".$type."_RESPONSIBLE_IM_NOTIFY", Array("#title#" => '<a href="'.$url.'">'.htmlspecialcharsbx($params['SUBJECT']).'</a>'));
						$arMessageFields["NOTIFY_MESSAGE_OUT"] = GetMessage("CRM_ACTIVITY_".$type."_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($params['SUBJECT'])))." (".$serverName.$url.")";

						CIMNotify::Add($arMessageFields);
					}
				}
			}

			if($processBindings && $hasBindings)
			{
				CCrmSonetRelation::RegisterRelationBundle(
					$slID,
					$slEventType,
					CCrmOwnerType::Activity,
					$activityID,
					$bindings,
					array(
						'ENTITY_TYPE_ID_KEY' => 'OWNER_TYPE_ID',
						'ENTITY_ID_KEY' => 'OWNER_ID',
						'TYPE_ID' => CCrmSonetRelationType::Ownership
					)
				);
			}
		}
		unset($slEntity);
		return true;
	}
	private static function UnregisterLiveFeedEvent($activityID)
	{
		$slEntities = CCrmLiveFeed::GetLogEvents(
			array(),
			array(
				'ENTITY_TYPE_ID' => CCrmOwnerType::Activity,
				'ENTITY_ID' => $activityID
			),
			array('ID')
		);

		$options = array('UNREGISTER_RELATION' => false);
		foreach($slEntities as &$slEntity)
		{
			CCrmLiveFeed::DeleteLogEvent($slEntity['ID'], $options);
		}
		unset($slEntity);
		CCrmSonetRelation::UnRegisterRelationsByEntity(CCrmOwnerType::Activity, $activityID);
	}
	public static function OnBeforeIntantMessangerChatAdd(\Bitrix\Main\Entity\Event $event)
	{
		$result = new \Bitrix\Main\Entity\EventResult();

		$fields = $event->getParameter('fields');
		$entityType = isset($fields['ENTITY_TYPE']) ? $fields['ENTITY_TYPE'] : '';
		$m = null;
		if(preg_match('/^CRM_([A-Z]+)$/i', $entityType, $m) === 1)
		{
			$entityTypeName = isset($m[1]) ? $m[1] : '';
			$ownerTypeID = CCrmOwnerType::ResolveID($entityTypeName);
			$ownerID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
			$ownerInfo = null;
			if(CCrmOwnerType::IsDefined($ownerTypeID)
				&& $ownerID > 0
				&& CCrmOwnerType::TryGetInfo($ownerTypeID, $ownerID, $ownerInfo, false))
			{
				$changedFields['TITLE'] = $ownerInfo['CAPTION'];
				$changedFields['AVATAR'] = $ownerInfo['IMAGE_ID'];
				$result->modifyFields($changedFields);
			}
		}
		return $result;
	}
	protected static function GetMaxDbDate()
	{
		return '';
	}
	public static function AddEmailSignature(&$message, $contentType = 0)
	{
		return Bitrix\Crm\Integration\Bitrix24Email::addSignature($message, $contentType);
	}
	public static function LoadElementIDs($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return array();
		}

		global $DB;
		$result = array();
		$table = CCrmActivity::ELEMENT_TABLE_NAME;
		$dbResult = $DB->Query("SELECT ELEMENT_ID FROM {$table} WHERE ACTIVITY_ID = {$ID}", false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		while($arResult = $dbResult->Fetch())
		{
			$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
			if($elementID > 0)
			{
				$result[] = $elementID;
			}
		}
		return $result;
	}
	public static function GetEntityList($entityTypeID, $userID, $sortOrder, array $filter, $navParams = false)
	{
		$entityTypeID = (int)$entityTypeID;
		$userID = (int)$userID;

		$userIDs = array(0);
		if($userID > 0)
		{
			$userIDs[] = $userID;
		}

		$lb = null;
		if($entityTypeID === CCrmOwnerType::Lead)
		{
			$lb = CCrmLead::CreateListBuilder();
		}
		else if($entityTypeID === CCrmOwnerType::Deal)
		{
			$lb = CCrmDeal::CreateListBuilder();
		}
		else if($entityTypeID === CCrmOwnerType::Contact)
		{
			$lb = CCrmContact::CreateListBuilder();
		}
		else if($entityTypeID === CCrmOwnerType::Company)
		{
			$lb = CCrmCompany::CreateListBuilder();
		}

		if(!$lb)
		{
			return null;
		}

		$fields = $lb->GetFields();
		$entityAlias = $lb->GetTableAlias();
		$join = 'LEFT JOIN '.CCrmActivity::USER_ACTIVITY_TABLE_NAME.' UA ON UA.USER_ID IN ('.implode(',', $userIDs).') AND UA.OWNER_ID = '.$entityAlias.'.ID AND UA.OWNER_TYPE_ID = '.$entityTypeID;
		$fields['ACTIVITY_USER_ID'] = array('FIELD' => 'MAX(UA.USER_ID)', 'TYPE' => 'int', 'FROM'=> $join);
		$fields['ACTIVITY_SORT'] = array('FIELD' => 'MAX(UA.SORT)', 'TYPE' => 'string', 'FROM'=> $join);
		$lb->SetFields($fields);

		$sortOrder = strtoupper($sortOrder);
		if($sortOrder !== 'DESC' && $sortOrder !== 'ASC')
		{
			$sortOrder = 'ASC';
		}

		$options = array(
			'PERMISSION_SQL_TYPE' => 'FROM',
			'PERMISSION_SQL_UNION' => 'DISTINCT'
		);

		return $lb->Prepare(
			array('ACTIVITY_USER_ID' => 'DESC', 'ACTIVITY_SORT' => $sortOrder, 'ID' => $sortOrder),
			$filter,
			array('ID'),
			$navParams,
			array('ID'),
			$options
		);
	}
}

class CCrmActivityType
{
	const Undefined = 0;
	const Meeting = 1;
	const Call = 2;
	const Task = 3;
	const Email = 4;
	const Activity = 5; // General type for import of calendar events and etc.

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Activity;
	}

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Undefined => '',
				self::Meeting => GetMessage('CRM_ACTIVITY_TYPE_MEETING'),
				self::Call => GetMessage('CRM_ACTIVITY_TYPE_CALL'),
				self::Task => GetMessage('CRM_ACTIVITY_TYPE_TASK'),
				self::Email => GetMessage('CRM_ACTIVITY_TYPE_EMAIL'),
				self::Activity => GetMessage('CRM_ACTIVITY_TYPE_ACTIVITY')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function ResolveDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : $all[self::Undefined];
	}

	public static function PrepareListItems()
	{
		return CCrmEnumeration::PrepareListItems(self::GetAllDescriptions(), array(self::Undefined));
	}

	public static function PrepareFilterItems()
	{
		return CCrmEnumeration::PrepareFilterItems(self::GetAllDescriptions(), array(self::Undefined));
	}
}

class CCrmActivityStatus
{
	const Undefined = 0;
	const Waiting = 1;
	const Completed = 2;

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Undefined => '',
				self::Waiting => GetMessage('CRM_ACTIVITY_STATUS_WAITING'),
				self::Completed => GetMessage('CRM_ACTIVITY_STATUS_COMPLETED')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function ResolveDescription($statusID, $typeID)
	{
		$statusID = intval($statusID);
		//$typeID = intval($typeID); //RESERVED

		$all = self::GetAllDescriptions();
		return isset($all[$statusID]) ? $all[$statusID] : $all[self::Undefined];
	}

	public static function PrepareListItems($typeID)
	{
		//$typeID = intval($typeID); //RESERVED
		return CCrmEnumeration::PrepareListItems(self::GetAllDescriptions(), array(self::Undefined));
	}
}

class CCrmActivityDirection
{
	const Undefined = 0;
	const Incoming = 1;
	const Outgoing = 2;

	private static $ALL_DESCRIPTIONS = array();

	public static function GetAllDescriptions($typeID = CCrmActivityType::Undefined)
	{
		if(!isset(self::$ALL_DESCRIPTIONS[$typeID]))
		{
			$typeID = intval($typeID);

			$incomingID = 'CRM_ACTIVITY_DIRECTION_INCOMING';
			$outgoingID = 'CRM_ACTIVITY_DIRECTION_OUTGOING';

			if($typeID === CCrmActivityType::Email)
			{
				$incomingID = 'CRM_ACTIVITY_EMAIL_DIRECTION_INCOMING';
				$outgoingID = 'CRM_ACTIVITY_EMAIL_DIRECTION_OUTGOING';
			}
			elseif($typeID === CCrmActivityType::Call)
			{
				$incomingID = 'CRM_ACTIVITY_CALL_DIRECTION_INCOMING';
				$outgoingID = 'CRM_ACTIVITY_CALL_DIRECTION_OUTGOING';
			}

			self::$ALL_DESCRIPTIONS[$typeID] = array(
				self::Undefined => '',
				self::Incoming => GetMessage($incomingID),
				self::Outgoing => GetMessage($outgoingID)
			);
		}

		return self::$ALL_DESCRIPTIONS[$typeID];
	}

	public static function ResolveDescription($directionID, $typeID)
	{
		$directionID = intval($directionID);
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions($typeID);

		return isset($all[$directionID]) ? $all[$directionID] : $all[self::Undefined];
	}

	public static function PrepareListItems($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions($typeID);

		return array(
			array('text' => $all[self::Incoming], 'value' => strval(self::Incoming)),
			array('text' => $all[self::Outgoing], 'value' => strval(self::Outgoing)),
		);
	}
}

class CCrmActivityPriority
{
	const None = 0;
	const Low = 1;
	const Medium = 2;
	const High = 3;

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::None => '',
				self::Low => GetMessage('CRM_PRIORITY_LOW'),
				self::Medium => GetMessage('CRM_PRIORITY_MEDIUM'),
				self::High => GetMessage('CRM_PRIORITY_HIGH')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function PrepareListItems()
	{
		return CCrmEnumeration::PrepareListItems(self::GetAllDescriptions(), array(self::None));
	}

	public static function PrepareFilterItems()
	{
		return CCrmEnumeration::PrepareFilterItems(self::GetAllDescriptions(), array(self::None));
	}

	public static function ResolveDescription($priorityID)
	{
		$priorityID = intval($priorityID);
		$all = self::GetAllDescriptions();
		return  isset($all[$priorityID]) ? $all[$priorityID] : $all[self::None];
	}

	public static function ToCalendarEventImportance($priorityID)
	{
		$priorityID = intval($priorityID);
		if($priorityID === CCrmActivityPriority::Low)
		{
			return 'low';
		}
		elseif($priorityID === CCrmActivityPriority::High)
		{
			return 'high';
		}

		return 'normal';
	}

	public static function FromCalendarEventImportance($importance)
	{
		$importance = strtolower(trim(strval($importance)));
		if($importance === '')
		{
			return CCrmActivityPriority::Medium;
		}

		if($importance === 'low')
		{
			return CCrmActivityPriority::Low;
		}
		elseif($importance === 'high')
		{
			return CCrmActivityPriority::High;
		}

		return CCrmActivityPriority::Medium;
	}
}

class CCrmActivityNotifyType
{
	const None = 0;
	const Min = 1;
	const Hour = 2;
	const Day = 3;

	private static $ALL_DESCRIPTIONS = null;

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::None => '',
				self::Min => GetMessage('CRM_NOTIFY_TYPE_MIN'),
				self::Hour => GetMessage('CRM_NOTIFY_TYPE_HOUR'),
				self::Day => GetMessage('CRM_NOTIFY_TYPE_DAY')
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}

	public static function PrepareListItems()
	{
		return CCrmEnumeration::PrepareListItems(self::GetAllDescriptions(), array(self::None));
	}

	public static function ResolveDescription($notifyTypeID)
	{
		$notifyTypeID = intval($notifyTypeID);
		$all = self::GetAllDescriptions();
		return  isset($all[$notifyTypeID]) ? $all[$notifyTypeID] : $all[self::None];
	}

	public static function ToCalendarEventRemind($notifyType)
	{
		$notifyType = intval($notifyType);

		$result = 'min';
		if($notifyType == self::Hour)
		{
			$result = 'hour';
		}
		elseif($notifyType == self::Day)
		{
			$result = 'day';
		}

		return $result;
	}

	public static function FromCalendarEventRemind($type)
	{
		$type = strtolower(strval($type));

		if($type === 'min')
		{
			return CCrmActivityNotifyType::Min;
		}
		elseif($type === 'hour')
		{
			return CCrmActivityNotifyType::Hour;
		}
		elseif($type === 'day')
		{
			return CCrmActivityNotifyType::Day;
		}

		return CCrmActivityNotifyType::None;
	}
}

/**
 * @deprecated Please use \Bitrix\Crm\Integration\StorageType
 */
class CCrmActivityStorageType
{
	const Undefined = 0;
	const File = 1;
	const WebDav = 2;
	const Disk = 3;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Disk;
	}
}

class CCrmContentType
{
	const Undefined = 0;
	const PlainText = 1;
	const BBCode = 2;
	const Html = 3;

	const PlainTextName = 'PLAIN_TEXT';
	const BBCodeName = 'BBCODE';
	const HtmlName = 'HTML';

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID >= self::PlainText && $typeID <= self::Html;
	}

	public static function ResolveTypeID($typeName)
	{
		$typeName = strval($typeName);
		switch($typeName)
		{
			case self::PlainTextName:
				return self::PlainText;
			case self::BBCodeName:
				return self::BBCode;
			case self::HtmlName:
				return self::Html;
		}
		return self::Undefined;
	}

	private static $ALL_DESCRIPTIONS = null;
	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS)
		{
			self::$ALL_DESCRIPTIONS = array(
				self::Undefined => '',
				self::PlainText => 'Plain text',
				self::BBCode => 'bbCode',
				self::Html => 'HTML',
			);
		}

		return self::$ALL_DESCRIPTIONS;
	}
}

class CCrmActivityCalendarSettings
{
	const Undefined = 0;
	const DisplayCompletedCalls = 1;
	const DisplayCompletedMeetings = 2;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::DisplayCompletedMeetings;
	}

	private static function GetBooleanValue($settingName, $default = false)
	{
		return strtoupper(COption::GetOptionString('crm', $settingName, $default ? 'Y' : 'N')) !== 'N';
	}

	private static function SetBooleanValue($settingName, $value)
	{
		return COption::SetOptionString('crm', $settingName, $value ? 'Y' : 'N');
	}

	public static function GetValue($setting, $default)
	{
		$setting = intval($setting);
		if(!self::IsDefined($setting))
		{
			return $default;
		}

		if($setting === self::DisplayCompletedCalls)
		{
			return self::GetBooleanValue('act_cal_show_compl_call', $default);
		}
		elseif($setting === self::DisplayCompletedMeetings)
		{
			return self::GetBooleanValue('act_cal_show_compl_meeting', $default);
		}

		return $default;
	}

	public static function SetValue($setting, $value)
	{
		$setting = intval($setting);
		if(!self::IsDefined($setting))
		{
			return;
		}

		if($setting === self::DisplayCompletedCalls)
		{
			self::SetBooleanValue('act_cal_show_compl_call', $value);
		}
		elseif($setting === self::DisplayCompletedMeetings)
		{
			self::SetBooleanValue('act_cal_show_compl_meeting', $value);
		}
	}
}

class CCrmActivityEmailSender
{
	const ERR_CANT_LOAD_SUBSCRIBE = -1;
	const ERR_INVALID_DATA = -2;
	const ERR_INVALID_EMAIL = -3;
	const ERR_CANT_FIND_EMAIL_FROM = -4;
	const ERR_CANT_FIND_EMAIL_TO = -5;
	const ERR_CANT_ADD_POSTING = -6;
	const ERR_CANT_UPDATE_ACTIVITY = -7;

	public static function TrySendEmail($ID, &$arFields, &$arErrors)
	{
		global $APPLICATION;

		if (!CModule::IncludeModule('subscribe'))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_LOAD_SUBSCRIBE);
			return false;
		}

		$ID = intval($ID);
		if($ID <= 0 && isset($arFields['ID']))
		{
			$ID = intval($arFields['ID']);
		}

		if($ID <= 0 || !is_array($arFields))
		{
			$arErrors[] = array('CODE' => self::ERR_INVALID_DATA);
			return false;
		}

		$typeID = isset($arFields['TYPE_ID']) ? intval($arFields['TYPE_ID']) : CCrmActivityType::Undefined;
		if($typeID !== CCrmActivityType::Email)
		{
			$arErrors[] = array('CODE' => self::ERR_INVALID_DATA);
			return false;
		}

		$urn = CCrmActivity::PrepareUrn($arFields);
		if(!($urn !== ''
			&& CCrmActivity::Update($ID, array('URN'=> $urn), false, false)))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_UPDATE_ACTIVITY);
			return false;
		}

		$settings = isset($arFields['SETTINGS']) && is_array($arFields['SETTINGS']) ? $arFields['SETTINGS'] : array();

		// Creating Email -->
		$crmEmail = CCrmMailHelper::ExtractEmail(COption::GetOptionString('crm', 'mail', ''));
		$from = isset($settings['MESSAGE_FROM']) ? trim(strval($settings['MESSAGE_FROM'])) : '';
		if($from === '')
		{
			if($crmEmail !== '')
			{
				$from = $crmEmail;
			}
			else
			{
				$arErrors[] = array('CODE' => self::ERR_CANT_FIND_EMAIL_FROM);
			}
		}
		elseif(!check_email($from))
		{
			$arErrors[] = array(
				'CODE' => self::ERR_INVALID_EMAIL,
				'DATA' => array('EMAIL' => $from)
			);
		}

		//Save user email in settings -->
		if($from !== CUserOptions::GetOption('crm', 'activity_email_addresser', ''))
		{
			CUserOptions::SetOption('crm', 'activity_email_addresser', $from);
		}
		//<-- Save user email in settings


		$to = array();
		$commData = isset($arFields['COMMUNICATIONS']) ? $arFields['COMMUNICATIONS'] : array();
		foreach($commData as &$commDatum)
		{
			$commType = isset($commDatum['TYPE']) ? strtoupper(strval($commDatum['TYPE'])) : '';
			$commValue = isset($commDatum['VALUE']) ? strval($commDatum['VALUE']) : '';

			if($commType !== 'EMAIL' || $commValue === '')
			{
				continue;
			}

			if(!check_email($commValue))
			{
				$arErrors[] = array(
					'CODE' => self::ERR_INVALID_EMAIL,
					'DATA' => array('EMAIL' => $commValue)
				);
				continue;
			}

			$to[] = strtolower(trim($commValue));
		}
		unset($commDatum);

		if(count($to) == 0)
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_FIND_EMAIL_TO);
		}

		if(!empty($arErrors))
		{
			return false;
		}

		// Try to resolve posting charset -->
		$postingCharset = '';
		$siteCharset = defined('LANG_CHARSET') ? LANG_CHARSET : (defined('SITE_CHARSET') ? SITE_CHARSET : 'windows-1251');
		$arSupportedCharset = explode(',', COption::GetOptionString('subscribe', 'posting_charset'));
		if(count($arSupportedCharset) === 0)
		{
			$postingCharset = $siteCharset;
		}
		else
		{
			foreach($arSupportedCharset as $curCharset)
			{
				if(strcasecmp($curCharset, $siteCharset) === 0)
				{
					$postingCharset = $curCharset;
					break;
				}
			}

			if($postingCharset === '')
			{
				$postingCharset = $arSupportedCharset[0];
			}
		}
		//<-- Try to resolve posting charset
		$subject = isset($arFields['SUBJECT']) ? $arFields['SUBJECT'] : '';
		$description = isset($arFields['DESCRIPTION']) ? $arFields['DESCRIPTION'] : '';
		$descriptionType = isset($arFields['DESCRIPTION_TYPE']) ? intval($arFields['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;

		$descriptionHtml = '';
		if($descriptionType === CCrmContentType::Html)
		{
			$descriptionHtml = $description;
		}
		elseif($descriptionType === CCrmContentType::BBCode)
		{
			$parser = new CTextParser();
			$descriptionHtml = $parser->convertText($description);
		}
		elseif($descriptionType === CCrmContentType::PlainText)
		{
			$descriptionHtml = htmlspecialcharsbx($description);
		}

		$postingData = array(
			'STATUS' => 'D',
			'FROM_FIELD' => $from,
			'TO_FIELD' => $from,
			'BCC_FIELD' => implode(',', $to),
			'SUBJECT' => $subject,
			'BODY_TYPE' => 'html',
			'BODY' => $descriptionHtml,
			'DIRECT_SEND' => 'Y',
			'SUBSCR_FORMAT' => 'html',
			'CHARSET' => $postingCharset
		);

		CCrmActivity::InjectUrnInMessage(
			$postingData,
			$urn,
			CCrmEMailCodeAllocation::GetCurrent()
		);

		$posting = new CPosting();
		$postingID = $posting->Add($postingData);
		if(!(is_int($postingID) && $postingID > 0))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_ADD_POSTING);
			return false;
		}

		$arUpdateFields = array(
			'COMPLETED' => 'Y',
			'ASSOCIATED_ENTITY_ID'=> $postingID,
			'SETTINGS' => $settings
		);

		$fromEmail = strtolower(trim(CCrmMailHelper::ExtractEmail($from)));
		if($crmEmail !== '' && $fromEmail !== $crmEmail)
		{
			$arUpdateFields['SETTINGS']['MESSAGE_HEADERS'] =
				array('Reply-To' => "<{$fromEmail}>, <$crmEmail>");
		}

		$arUpdateFields['SETTINGS']['IS_MESSAGE_SENT'] = true;

		if(!CCrmActivity::Update($ID, $arUpdateFields, false, false))
		{
			$arErrors[] = array('CODE' => self::ERR_CANT_UPDATE_ACTIVITY);
			return false;
		}
		// <-- Creating Email

		// Attaching files -->
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : StorageType::Undefined;
		$storageElementsID = isset($arFields['STORAGE_ELEMENT_IDS'])
			&& is_array($arFields['STORAGE_ELEMENT_IDS'])
			? $arFields['STORAGE_ELEMENT_IDS'] : array();


		$arRawFiles = StorageManager::makeFileArray($storageElementsID, $storageTypeID);
		foreach($arRawFiles as &$arRawFile)
		{
			$posting->SaveFile($postingID, $arRawFile);
		}
		unset($arRawFile);
		// <-- Attaching files

		// Sending Email -->
		$posting->ChangeStatus($postingID, 'P');
		if(($e = $APPLICATION->GetException()) == false)
		{
			$rsAgents = CAgent::GetList(
				array('ID'=>'DESC'),
				array(
					'MODULE_ID' => 'subscribe',
					'NAME' => 'CPosting::AutoSend('.$postingID.',%',
				)
			);

			if(!$rsAgents->Fetch())
			{
				CAgent::AddAgent('CPosting::AutoSend('.$postingID.',true);', 'subscribe', 'N', 0);
			}
		}

		// Try add event to entity
		$CCrmEvent = new CCrmEvent();

		$ownerID = isset($arFields['OWNER_ID']) ? intval($arFields['OWNER_ID']) : 0;
		$ownerTypeID = isset($arFields['OWNER_TYPE_ID']) ? intval($arFields['OWNER_TYPE_ID']) : 0;

		if($ownerID > 0 && $ownerTypeID > 0)
		{
			$eventText  = '';
			$eventText .= GetMessage('CRM_ACTIVITY_EMAIL_SUBJECT').': '.$subject."\n\r";
			$eventText .= GetMessage('CRM_ACTIVITY_EMAIL_FROM').': '.$from."\n\r";
			$eventText .= GetMessage('CRM_ACTIVITY_EMAIL_TO').': '.implode(',', $to)."\n\r\n\r";
			$eventText .= $description;
			// Register event only for owner
			$CCrmEvent->Add(
				array(
					'ENTITY' => array(
						$ownerID => array(
							'ENTITY_TYPE' => CCrmOwnerType::ResolveName($ownerTypeID),
							'ENTITY_ID' => $ownerID
						)
					),
					'EVENT_ID' => 'MESSAGE',
					'EVENT_TEXT_1' => $eventText,
					'FILES' => $arRawFiles
				)
			);
		}
		// <-- Sending Email
		return true;
	}
}

class CCrmActivityDbResult extends CDBResult
{
	private $selectFields = null;
	private $selectCommunications = false;
	function CCrmActivityDbResult($res, $selectFields = array())
	{
		parent::CDBResult($res);

		if(!is_array($selectFields))
		{
			$selectFields = array();
		}
		$this->selectFields = $selectFields;
		$this->selectCommunications = in_array('COMMUNICATIONS', $selectFields, true);
	}

	function Fetch()
	{
		if ($result = parent::Fetch())
		{
			if(array_key_exists('SETTINGS', $result))
			{
				$result['SETTINGS'] = is_string($result['SETTINGS']) ? unserialize($result['SETTINGS']) : array();
			}

			if($this->selectCommunications)
			{
				$result['COMMUNICATIONS'] = CCrmActivity::GetCommunications($result['ID']);
			}
		}
		return $result;
	}
}
