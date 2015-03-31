<?php
/*
 * CCrmSonetRelation
 */
class CCrmSonetSubscription extends CAllCrmSonetSubscription
{
	const TABLE_NAME = 'b_crm_sl_subscr';
	const DB_TYPE = 'MYSQL';

	public function Register($entityTypeID, $entityID, $typeID, $userID)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!CCrmSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = CCrmSonetSubscriptionType::Observation;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));

		if($typeID === CCrmSonetSubscriptionType::Responsibility)
		{
			// Multiple responsibility is not allowed
			$deleteSql = "DELETE FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}";
			$DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		}

		$insertSql = "INSERT INTO {$tableName}(USER_ID, SL_ENTITY_TYPE, ENTITY_ID, TYPE_ID)
			VALUES({$userID}, '{$slEntityType}', {$entityID}, {$typeID})
			ON DUPLICATE KEY UPDATE USER_ID = {$userID}, SL_ENTITY_TYPE = '{$slEntityType}', ENTITY_ID = {$entityID}, TYPE_ID = {$typeID}";
		$dbResult = $DB->Query($insertSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UpdateByEntity($entityTypeID, $entityID, $typeID, $userID)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!CCrmSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = CCrmSonetSubscriptionType::Observation;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$updateSql = "UPDATE {$tableName} SET USER_ID = {$userID} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID} LIMIT 1";
		$dbResult = $DB->Query($updateSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegister($entityTypeID, $entityID, $typeID, $userID, $options = array())
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$typeID = intval($typeID);
		if(!CCrmSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = CCrmSonetSubscriptionType::Observation;
		}

		$modifiers = '';
		if(is_array($options) && isset($options['QUICK']) && $options['QUICK'] === true)
		{
			$modifiers = ' QUICK';
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$deleteSql = "DELETE{$modifiers} FROM {$tableName} WHERE USER_ID = $userID AND SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}";
		$dbResult = $DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegisterByEntity($entityTypeID, $entityID)
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

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$deleteSql = "DELETE FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID}";
		$dbResult = $DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function UnRegisterByType($entityTypeID, $entityID, $typeID)
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

		$typeID = intval($typeID);
		if(!CCrmSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = CCrmSonetSubscriptionType::Observation;
		}

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));
		$deleteSql = "DELETE FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND ENTITY_ID = {$entityID} AND TYPE_ID = {$typeID}";
		$dbResult = $DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult) && $dbResult->AffectedRowsCount() > 0;
	}
	public function ImportResponsibility($entityTypeID, $userID, $top)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = max(intval($userID), 0);
		$top = max(intval($top), 0);
		$typeID = CCrmSonetSubscriptionType::Observation;

		global $DB;
		$tableName = self::TABLE_NAME;
		$slEntityType = $DB->ForSql(CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID));

		$selectSql = '';
		if($entityTypeID === CCrmOwnerType::Lead
			|| $entityTypeID === CCrmOwnerType::Contact
			|| $entityTypeID === CCrmOwnerType::Company
			|| $entityTypeID === CCrmOwnerType::Deal
			|| $entityTypeID === CCrmOwnerType::Activity)
		{
			if($entityTypeID === CCrmOwnerType::Lead)
			{
				$selectTableName = CCrmLead::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			elseif($entityTypeID === CCrmOwnerType::Contact)
			{
				$selectTableName = CCrmContact::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			elseif($entityTypeID === CCrmOwnerType::Company)
			{
				$selectTableName = CCrmCompany::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			elseif($entityTypeID === CCrmOwnerType::Deal)
			{
				$selectTableName = CCrmDeal::TABLE_NAME;
				$userFieldName = 'ASSIGNED_BY_ID';
			}
			else //($entityTypeID === CCrmOwnerType::Activity
			{
				$selectTableName = CCrmActivity::TABLE_NAME;
				$userFieldName = 'RESPONSIBLE_ID';
			}

			$userFieldCondition = $userID > 0 ? " = {$userID}" : ' > 0';
			$selectSql = "SELECT {$userFieldName}, '{$slEntityType}', ID, $typeID FROM {$selectTableName} WHERE {$userFieldName}{$userFieldCondition} ORDER BY ID DESC";
		}

		if($selectSql === '')
		{
			return false;
		}

		if($top > 0)
		{
			CSqlUtil::PrepareSelectTop($selectSql, $top, self::DB_TYPE);
		}

		$deleteSql = "DELETE QUICK FROM {$tableName} WHERE SL_ENTITY_TYPE = '{$slEntityType}' AND TYPE_ID = $typeID";
		if($userID > 0)
		{
			$deleteSql .= " AND USER_ID = {$userID}";
		}
		$DB->Query($deleteSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);

		$insertSql = "INSERT INTO {$tableName}(USER_ID, SL_ENTITY_TYPE, ENTITY_ID, TYPE_ID) ".$selectSql;
		$dbResult = $DB->Query($insertSql, false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		return is_object($dbResult);
	}
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			self::DB_TYPE,
			self::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(),
			'',
			'',
			null
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}
}