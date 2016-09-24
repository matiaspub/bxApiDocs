<?php
/*
 * CCrmSonetRelation
 */
class CCrmSonetRelation extends CAllCrmSonetRelation
{
	const TABLE_NAME = 'b_crm_sl_rel';
	const DB_TYPE = 'MYSQL';

	public function Register($logEntityID, $logEventID, $entityTypeID, $entityID, $parentEntityTypeID, $parentEntityID, $typeID = CCrmSonetRelationType::Ownership, $level = 1)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID) || !CCrmOwnerType::IsDefined($parentEntityTypeID))
		{
			return;
		}

		$logEntityID = intval($logEntityID);
		$entityID = intval($entityID);
		$parentEntityID = intval($parentEntityID);
		if($logEntityID <= 0 || $entityID <= 0 || $parentEntityID <= 0)
		{
			return;
		}

		if(!CCrmSonetRelationType::IsDefined($typeID))
		{
			$typeID = CCrmSonetRelationType::Ownership;
		}

		$level = intval($level);
		if($level <= 0)
		{
			$level = 1;
		}

		global $DB;
		$tableName = self::TABLE_NAME;

		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$slParentEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($parentEntityTypeID));
		$logEventID = $DB->ForSql($logEventID);

		CTimeZone::Disable();
		$logLastUpdateTime = CCrmLiveFeed::GetLogEventLastUpdateTime($logEntityID, true);
		if($logLastUpdateTime !== '')
		{
			$logLastUpdateTime = $DB->CharToDateFunction($logLastUpdateTime, 'FULL');
		}
		CTimeZone::Enable();

		$insertSql = "INSERT INTO {$tableName}(SL_ID, SL_EVENT_ID, SL_ENTITY_TYPE, ENTITY_ID, SL_PARENT_ENTITY_TYPE, PARENT_ENTITY_ID, SL_LAST_UPDATED, TYPE_ID, LVL)
			VALUES({$logEntityID}, '{$logEventID}', '{$slEntityType}', {$entityID}, '{$slParentEntityType}', {$parentEntityID}, {$logLastUpdateTime}, {$typeID}, {$level})";
		$DB->Query($insertSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
	public function RegisterBundle($logEntityID, $logEventID, $entityTypeID, $entityID, &$parents, $options = array())
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return;
		}

		$logEntityID = intval($logEntityID);
		$entityID = intval($entityID);
		if($logEntityID <= 0 || $entityID <= 0)
		{
			return;
		}

		if(!is_array($parents))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeIDKey = isset($options['ENTITY_TYPE_ID_KEY']) ? $options['ENTITY_TYPE_ID_KEY'] : '';
		if($entityTypeIDKey === '')
		{
			$entityTypeIDKey = 'ENTITY_TYPE_ID';
		}

		$entityIDKey = isset($options['ENTITY_ID_KEY']) ? $options['ENTITY_ID_KEY'] : '';
		if($entityIDKey === '')
		{
			$entityIDKey = 'ENTITY_ID';
		}

		$defaultTypeID = isset($options['TYPE_ID']) ? intval($options['TYPE_ID']) : CCrmOwnerType::Undefined;
		if(!CCrmSonetRelationType::IsDefined($defaultTypeID))
		{
			$defaultTypeID = CCrmSonetRelationType::Ownership;
		}

		$items = array();
		$slEntityType = CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID);
		$logLastUpdateTime = CCrmLiveFeed::GetLogEventLastUpdateTime($logEntityID, false);
		foreach($parents as &$parent)
		{
			$parentEntityTypeID = isset($parent[$entityTypeIDKey]) ? intval($parent[$entityTypeIDKey]) : CCrmOwnerType::Undefined;
			$parentEntityID = isset($parent[$entityIDKey]) ? intval($parent[$entityIDKey]) : 0;

			if(!CCrmOwnerType::IsDefined($parentEntityTypeID) || $parentEntityID <= 0)
			{
				continue;
			}

			$key = "{$parentEntityTypeID}_{$parentEntityID}";
			if(isset($items[$key]))
			{
				continue;
			}

			$typeID = isset($parent['TYPE_ID']) ? intval($parent['TYPE_ID']) : CCrmSonetRelationType::Undefined;
			if(!CCrmSonetRelationType::IsDefined($typeID))
			{
				$typeID = $defaultTypeID;
			}

			$level = isset($parent['LEVEL']) ? max(intval($parent['LEVEL']), 1) : 1;
			$items[$key] = array(
				'SL_ID' => $logEntityID,
				'SL_EVENT_ID' => $logEventID,
				'SL_ENTITY_TYPE' => $slEntityType,
				'ENTITY_ID' => $entityID,
				'SL_PARENT_ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($parentEntityTypeID),
				'PARENT_ENTITY_ID' => $parentEntityID,
				'SL_LAST_UPDATED' => $logLastUpdateTime,
				'TYPE_ID' => $typeID,
				'LVL' => $level
			);
		}
		unset($parent);

		global $DB;
		CTimeZone::Disable();
		$bulkColumns = '';
		$bulkValues = array();
		foreach($items as &$item)
		{
			$data = $DB->PrepareInsert(self::TABLE_NAME, $item);
			if(strlen($bulkColumns) == 0)
			{
				$bulkColumns = $data[0];
			}

			$bulkValues[] = $data[1];
		}
		unset($item);
		CTimeZone::Enable();

		if(count($bulkValues) == 0)
		{
			return;
		}

		$query = '';
		foreach($bulkValues as &$value)
		{
			if($query !== '')
			{
				$query .= ',';
			}

			$query .= "($value)";
		}

		if(strlen($query) == 0)
		{
			return;
		}

		$DB->Query(
			'INSERT INTO '.self::TABLE_NAME.'('.$bulkColumns.') VALUES'.$query,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);
	}
	public function Replace($entityTypeID, $entityID, $currentParent, $previousParent, $options = array())
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$entityID = intval($entityID);
		if($entityID <= 0)
		{
			return false;
		}

		if(!is_array($currentParent) || !is_array($previousParent))
		{
			return false;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeIDKey = isset($options['ENTITY_TYPE_ID_KEY']) ? $options['ENTITY_TYPE_ID_KEY'] : '';
		if($entityTypeIDKey === '')
		{
			$entityTypeIDKey = 'ENTITY_TYPE_ID';
		}

		$entityIDKey = isset($options['ENTITY_ID_KEY']) ? $options['ENTITY_ID_KEY'] : '';
		if($entityIDKey === '')
		{
			$entityIDKey = 'ENTITY_ID';
		}

		$currentParentEntityTypeID = isset($currentParent[$entityTypeIDKey]) ? intval($currentParent[$entityTypeIDKey]) : CCrmOwnerType::Undefined;
		$currentParentEntityID = isset($currentParent[$entityIDKey]) ? intval($currentParent[$entityIDKey]) : 0;

		$previousParentEntityTypeID = isset($previousParent[$entityTypeIDKey]) ? intval($previousParent[$entityTypeIDKey]) : CCrmOwnerType::Undefined;
		$previousParentEntityID = isset($previousParent[$entityIDKey]) ? intval($previousParent[$entityIDKey]) : 0;

		if(!CCrmOwnerType::IsDefined($currentParentEntityTypeID) || !CCrmOwnerType::IsDefined($previousParentEntityTypeID))
		{
			return false;
		}

		global $DB;
		$tableName = self::TABLE_NAME;

		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$currentSlParentEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($currentParentEntityTypeID));
		$previousSlParentEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($previousParentEntityTypeID));

		$updateSql = "UPDATE {$tableName} SET SL_PARENT_ENTITY_TYPE = '{$currentSlParentEntityType}', PARENT_ENTITY_ID = {$currentParentEntityID}
			WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND SL_PARENT_ENTITY_TYPE = '{$previousSlParentEntityType}'AND PARENT_ENTITY_ID = {$previousParentEntityID}";
		$dbResult = $DB->Query($updateSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegisterByLogEntityID($logEntityID, $typeID = CCrmSonetRelationType::Undefined)
	{
		$logEntityID = intval($logEntityID);
		if($logEntityID <= 0)
		{
			return;
		}

		global $DB;
		$tableName = self::TABLE_NAME;

		$deleteSql = CCrmSonetRelationType::IsDefined($typeID)
			? "DELETE FROM {$tableName} WHERE SL_ID = {$logEntityID} AND TYPE_ID = {$typeID}"
			: "DELETE FROM {$tableName} WHERE SL_ID = {$logEntityID}";
		$DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
	public function UnRegisterByEntity($entityTypeID, $entityID, $options = array())
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return;
		}

		$entityID = intval($entityID);
		if($entityID <= 0)
		{
			return;
		}

		global $DB;
		$tableName = self::TABLE_NAME;

		$modifiers = '';
		if(is_array($options) && isset($options['QUICK']) && $options['QUICK'] === true)
		{
			$modifiers = ' QUICK';
		}

		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));

		$deleteSql = "DELETE{$modifiers} FROM {$tableName} WHERE (SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID}) OR (SL_PARENT_ENTITY_TYPE = '{$slEntityType}' AND PARENT_ENTITY_ID = {$entityID})";
		$DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
	public function SynchronizeLastUpdateTime($logEntityID)
	{
		$logEntityID = intval($logEntityID);
		if($logEntityID <= 0)
		{
			return;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$sql = "UPDATE {$tableName} R INNER JOIN b_sonet_log L ON R.SL_ID = L.ID SET R.SL_LAST_UPDATED = L.LOG_UPDATE WHERE R.SL_ID = {$logEntityID}";
		$DB->Query($sql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
	}
	public function Rebind($entityTypeID, $srcEntityID, $dstEntityID)
	{
		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));

		$rowCount = 0;

		$updateSql = "UPDATE {$tableName} SET ENTITY_ID = {$dstEntityID}
			WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$srcEntityID}";
		$dbResult = $DB->Query($updateSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

		if(is_object($dbResult))
		{
			$rowCount += $dbResult->AffectedRowsCount();
		}

		$updateSql = "UPDATE {$tableName} SET PARENT_ENTITY_ID = {$dstEntityID}
			WHERE SL_PARENT_ENTITY_TYPE = '{$slEntityType}' AND PARENT_ENTITY_ID = {$srcEntityID}";
		$dbResult = $DB->Query($updateSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

		if(is_object($dbResult))
		{
			$rowCount += $dbResult->AffectedRowsCount();
		}

		return $rowCount > 0;
	}
}
