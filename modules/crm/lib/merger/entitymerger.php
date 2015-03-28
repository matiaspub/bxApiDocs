<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Recovery;
use Bitrix\Crm\Integrity;

abstract class EntityMerger
{
	const ROLE_UNDEFINED = 0;
	const ROLE_SEED = 1;
	const ROLE_TARG = 2;

	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $userID = 0;
	protected $userIsAdmin = false;
	protected $userPermissions = null;
	protected $userName = null;

	protected $enablePermissionCheck = false;

	public function __construct($entityTypeID, $userID, $enablePermissionCheck = false)
	{
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined', 'entityTypeID');
		}

		$this->entityTypeID = $entityTypeID;
		$this->setUserID($userID);
		$this->enabledPermissionCheck($enablePermissionCheck);
	}

	/** Create new entity merger by specified entity type ID
	 * @static
	 * @param int $entityTypeID
	 * @param int $currentUserID
	 * @param bool $enablePermissionCheck
	 * @return EntityMerger
	 */
	public static function create($entityTypeID, $currentUserID, $enablePermissionCheck = false)
	{
		return EntityMergerFactory::create($entityTypeID, $currentUserID, $enablePermissionCheck);
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function getEntityTypeName()
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeID);
	}
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	public function enabledPermissionCheck($enable)
	{
		$this->enablePermissionCheck = is_bool($enable) ? $enable : (bool)$enable;
	}

	public function isAdminUser()
	{
		return $this->userIsAdmin;
	}
	public function getUserID()
	{
		return $this->userID;
	}
	public function setUserID($userID)
	{
		if(!is_integer($userID))
		{
			$userID = intval($userID);
		}
		$userID = max($userID, 0);

		if($this->userID === $userID)
		{
			return;
		}

		$this->userID = $userID;
		$this->userPermissions = null;
		$this->userName = null;
		$this->userIsAdmin =  \CCrmPerms::IsAdmin($userID);
	}
	public function getUserName()
	{
		if($this->userName !== null)
		{
			return $this->userName;
		}

		if($this->userID <= 0)
		{
			return ($this->userName = '');
		}

		$dbResult = \CUser::GetList(
			($by='id'),
			($order='asc'),
			array('ID'=> $this->userID),
			array('FIELDS'=> array('ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME', 'SECOND_NAME')
			)
		);

		$fields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($fields))
		{
			return ($this->userName = '');
		}
		return ($this->userName = \CUser::FormatName(Crm\Format\PersonNameFormatter::getFormat(), $fields, false, false));
	}
	public static function isRoleDefined($roleID)
	{
		if(!is_int($roleID))
		{
			$roleID = (int)$roleID;
		}
		return $roleID === self::ROLE_SEED || $roleID === self::ROLE_TARG;
	}
	public function isMergable($entityID, $roleID)
	{
		if(!$this->enablePermissionCheck)
		{
			return true;
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'entityID');
		}

		if(!is_int($roleID))
		{
			$roleID = (int)$roleID;
		}
		if(!self::isRoleDefined($roleID))
		{
			throw new Main\ArgumentException('Merge role is not defined', 'roleID');
		}

		$entityTypeID = $this->entityTypeID;
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$permissions = $this->getUserPermissions();

		if($roleID === self::ROLE_SEED)
		{
			return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $permissions)
				&& \CCrmAuthorizationHelper::CheckDeletePermission($entityTypeName, $entityID, $permissions);
		}
		else
		{
			return \CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $permissions)
				&& \CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $permissions);
		}
	}
	/** Get possible merge collisions
	 * @param int $seedID
	 * @param int $targID
	 * @return array[EntityMergeCollision]
	 */
	public function getMergeCollisions($seedID, $targID)
	{
		if(!is_int($seedID))
		{
			$seedID = (int)$seedID;
		}

		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		$results = array();

		$seedResponsibleID = $this->getEntityResponsibleID($seedID, self::ROLE_SEED);
		$targResponsibleID = $this->getEntityResponsibleID($targID, self::ROLE_TARG);

		if($seedResponsibleID > 0 && $seedResponsibleID !== $targResponsibleID)
		{
			$responsiblePermissions = \CCrmPerms::GetUserPermissions($seedResponsibleID);
			if(!$this->checkEntityReadPermission($targID, $responsiblePermissions))
			{
				$results[EntityMergeCollision::READ_PERMISSION_LACK] = new EntityMergeCollision($this->entityTypeID, $seedID, $targID, EntityMergeCollision::READ_PERMISSION_LACK);
			}
			if($this->checkEntityUpdatePermission($seedID, $responsiblePermissions)
				&& !$this->checkEntityUpdatePermission($targID, $responsiblePermissions))
			{
				$results[EntityMergeCollision::UPDATE_PERMISSION_LACK] = new EntityMergeCollision($this->entityTypeID, $seedID, $targID, EntityMergeCollision::UPDATE_PERMISSION_LACK);
			}
		}

		$this->resolveMergeCollisions($seedID, $targID, $results);
		return $results;
	}
	protected function resolveMergeCollisions($seedID, $targID, array &$results)
	{
	}
	public function merge($seedID, $targID, Integrity\DuplicateCriterion $targCriterion)
	{
		if(!is_int($seedID))
		{
			$seedID = (int)$seedID;
		}

		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}

		$entityTypeID = $this->entityTypeID;
		if($this->enablePermissionCheck && !$this->userIsAdmin)
		{
			$userPermissions = $this->getUserPermissions();
			if(!$this->checkEntityReadPermission($seedID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $seedID, self::ROLE_SEED, EntityMergerException::READ_DENIED);
			}
			if(!$this->checkEntityDeletePermission($seedID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $seedID, self::ROLE_SEED, EntityMergerException::DELETE_DENIED);
			}
			if(!$this->checkEntityReadPermission($targID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $targID, self::ROLE_TARG, EntityMergerException::READ_DENIED);
			}
			if(!$this->checkEntityUpdatePermission($targID, $userPermissions))
			{
				throw new EntityMergerException($entityTypeID, $targID, self::ROLE_TARG, EntityMergerException::UPDATE_DENIED);
			}
		}

		$collisions = self::getMergeCollisions($seedID, $targID);

		$seed = $this->getEntityFields($seedID, self::ROLE_SEED);
		$targ = $this->getEntityFields($targID, self::ROLE_TARG);

		$entityFieldInfos = $this->getEntityFieldsInfo();
		$userFieldInfos = $this->getEntityUserFieldsInfo();

		EntityMerger::mergeEntityFields($seed, $targ, $entityFieldInfos);
		EntityMerger::mergeUserFields($seed, $targ, $userFieldInfos);

		$seedMultiFields = $this->getEntityMultiFields($seedID, self::ROLE_SEED);
		$targMultiFields = $this->getEntityMultiFields($targID, self::ROLE_TARG);

		EntityMerger::mergeMultiFields($seedMultiFields, $targMultiFields);

		if(!empty($targMultiFields))
		{
			$targ['FM'] = $targMultiFields;
		}

		//$recoveryData = self::prepareRecoveryData($seed, $entityFieldInfos, $userFieldInfos);
		//$recoveryData->setEntityTypeID($entityTypeID);
		//$recoveryData->setEntityID($seedID);
		//$this->setupRecoveryData($recoveryData, $seed);

		//if(!empty($seedMultiFields))
		//{
		//	$recoveryData->setDataItem('MULTI_FIELDS', $seedMultiFields);
		//}

		//$activityIDs = \CCrmActivity::GetBoundIDs($entityTypeID, $seedID);
		//if(!empty($activityIDs))
		//{
		//	$recoveryData->setDataItem('ACTIVITY_IDS', $activityIDs);
		//}

		//$eventIDs = array();
		//$result = \CCrmEvent::GetListEx(
		//	array('EVENT_REL_ID' => 'ASC'),
		//	array(
		//		'ENTITY_TYPE' => $entityTypeName,
		//		'ENTITY_ID' => $seedID,
		//		'EVENT_TYPE' => 0,
		//		'CHECK_PERMISSIONS' => 'N'
		//	),
		//	false,
		//	false,
		//	array('EVENT_REL_ID')
		//);

		//if(is_object($result))
		//{
		//	while($eventFields = $result->Fetch())
		//	{
		//		$eventIDs[] = (int)$eventFields['EVENT_REL_ID'];
		//	}
		//}
		//if(!empty($eventIDs))
		//{
		//	$recoveryData->setDataItem('EVENT_IDS', $eventIDs);
		//}

		//$recoveryData->setUserID($this->userID);
		//$recoveryData->save();

		$this->updateEntity($targID, $targ, self::ROLE_TARG);
		$this->rebind($seedID, $targID);

		$matches = $this->getRegisteredEntityMatches($entityTypeID, $seedID);

		$targIndexTypeID = $targCriterion->getIndexTypeID();
		if(!isset($matches[$targIndexTypeID]))
		{
			$matches[$targIndexTypeID] = array();
		}

		$targetMatchHash = $targCriterion->getMatchHash();
		if(!isset($matches[$targIndexTypeID][$targetMatchHash]))
		{
			$matches[$targIndexTypeID][$targetMatchHash] = $targCriterion->getMatches();
		}

		$this->deleteEntity($seedID, self::ROLE_SEED, array('ENABLE_DUP_INDEX_INVALIDATION' => false));
		if(!empty($matches))
		{
			$this->processEntityDeletion($entityTypeID, $seedID, $matches);
		}
		Integrity\DuplicateIndexBuilder::markAsJunk($entityTypeID, $seedID);

		if(!empty($collisions))
		{
			$messageFields = $this->prepareCollisionMessageFields($collisions, $seed, $targ);
			if(is_array($messageFields) && !empty($messageFields) && Main\Loader::includeModule('im'))
			{
				$messageFields['FROM_USER_ID'] = $this->userID;
				$messageFields['MESSAGE_TYPE'] = IM_MESSAGE_SYSTEM;
				$messageFields['NOTIFY_TYPE'] = IM_NOTIFY_FROM;
				$messageFields['NOTIFY_MODULE'] = 'crm';
				$messageFields['NOTIFY_EVENT'] = 'merge';
				$messageFields['NOTIFY_TAG'] = 'CRM|MERGE|COLLISION';

				\CIMNotify::Add($messageFields);
			}
		}
	}
	public function registerCriterionMismatch(Integrity\DuplicateCriterion $criterion, $leftEntityID, $rightEntityID)
	{
		$entityTypeID = $this->entityTypeID;
		$userID = $this->userID;
		$typeID = $criterion->getIndexTypeID();
		$matchHash = $criterion->getMatchHash();
		if($matchHash === '')
		{
			throw new Main\ArgumentException('Match hash is empty', 'criterion');
		}

		Integrity\DuplicateIndexMismatch::register($entityTypeID, $leftEntityID, $rightEntityID, $typeID, $matchHash, $userID);
	}
	protected static function mergeEntityFields(array &$seed, array &$targ, array &$fieldInfos)
	{
		if(empty($seed))
		{
			return;
		}

		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			// Skip PK
			if($fieldID === 'ID')
			{
				continue;
			}

			// Skip READONLY fields
			if(isset($fieldInfo['ATTRIBUTES'])
				&& in_array(\CCrmFieldInfoAttr::ReadOnly, $fieldInfo['ATTRIBUTES'], true))
			{
				continue;
			}

			$targFlg = isset($targ[$fieldID]);
			$seedFlg = isset($seed[$fieldID]);

			$type = isset($fieldInfo['TYPE']) ? $fieldInfo['TYPE'] : 'string';
			if($type === 'string'
				|| $type === 'char'
				|| $type === 'datetime'
				|| $type === 'crm_status'
				|| $type === 'crm_currency')
			{
				$targFlg = $targFlg && $targ[$fieldID] !== '';
				$seedFlg = $seedFlg && $seed[$fieldID] !== '';
			}
			elseif($type === 'double')
			{
				$targFlg = $targFlg && doubleval($targ[$fieldID]) !== 0.0;
				$seedFlg = $seedFlg && doubleval($seed[$fieldID]) !== 0.0;
			}
			elseif($type === 'integer' || $type === 'user')
			{
				$targFlg = $targFlg && intval($targ[$fieldID]) !== 0;
				$seedFlg = $seedFlg && intval($seed[$fieldID]) !== 0;
			}

			// Skip if target entity field is defined
			// Skip if seed entity field is not defined
			if(!$targFlg && $seedFlg)
			{
				$targ[$fieldID] = $seed[$fieldID];
			}
		}
		unset($fieldInfo);
	}
	protected static function mergeUserFields(array &$seed, array &$targ, array &$fieldInfos)
	{
		if(empty($seed))
		{
			return;
		}

		foreach($fieldInfos as $fieldID => &$fieldInfo)
		{
			$isMultiple = $fieldInfo['MULTIPLE'] === 'Y';
			if(!$isMultiple && !isset($targ[$fieldID]) && isset($seed[$fieldID]))
			{
				$targ[$fieldID] = $seed[$fieldID];
			}
			elseif($isMultiple && isset($seed[$fieldID]) && is_array($seed[$fieldID]))
			{
				if(isset($targ[$fieldID]) && is_array($targ[$fieldID]))
				{
					$targ[$fieldID] = array_merge(
						$targ[$fieldID],
						array_diff($seed[$fieldID], $targ[$fieldID])
					);
				}
				else
				{
					$targ[$fieldID] = $seed[$fieldID];
				}
			}
		}
		unset($fieldInfo);
	}
	protected static function mergeMultiFields(array &$seed, array &$targ)
	{
		if(empty($seed))
		{
			return;
		}

		$targMap = array();
		foreach($targ as $typeID => &$fields)
		{
			$typeMap = array();
			foreach($fields as &$field)
			{
				$value = isset($field['VALUE']) ? trim($field['VALUE']) : '';
				if($value === '')
				{
					continue;
				}

				$key = $typeID === \CCrmFieldMulti::PHONE
					? Crm\Integrity\DuplicateCommunicationCriterion::normalizePhone($value)
					: strtolower($value);

				if($key !== '' && !isset($typeMap[$key]))
				{
					$typeMap[$key] = true;
				}
			}
			unset($field);

			if(!empty($typeMap))
			{
				$targMap[$typeID] = &$typeMap;
			}
			unset($typeMap);
		}
		unset($fields);

		foreach($seed as $typeID => &$fields)
		{
			$fieldNum = 1;
			foreach($fields as $field)
			{
				$value = isset($field['VALUE']) ? trim($field['VALUE']) : '';
				if($value === '')
				{
					continue;
				}

				$key = $typeID === \CCrmFieldMulti::PHONE
					? Crm\Integrity\DuplicateCommunicationCriterion::normalizePhone($value)
					: strtolower($value);

				if($key !== '' && (!isset($targMap[$typeID]) || !isset($targMap[$typeID][$key])))
				{
					if(!isset($targ[$typeID]))
					{
						$targ[$typeID] = array();
					}

					while(isset($targ[$typeID]["n{$fieldNum}"]))
					{
						$fieldNum++;
					}

					$targ[$typeID]["n{$fieldNum}"] = $field;
				}
			}
		}
		unset($fields);
	}
	protected static function prepareRecoveryData(array &$fields, array &$entityFieldInfos, array &$userFieldInfos)
	{
		$item = new Recovery\EntityRecoveryData();
		$itemFields = array();
		foreach($entityFieldInfos as $fieldID => &$fieldInfo)
		{
			if(isset($fields[$fieldID]))
			{
				$itemFields[$fieldID] = $fields[$fieldID];
			}
		}
		unset($fieldInfo);

		foreach($userFieldInfos as $fieldID => &$userFieldInfo)
		{
			if(isset($fields[$fieldID]))
			{
				$itemFields[$fieldID] = $fields[$fieldID];
			}
		}
		unset($userFieldInfo);

		$item->setDataItem('FIELDS', $itemFields);
		return $item;
	}

	protected function getUserPermissions()
	{
		if($this->userPermissions === null)
		{
			$this->userPermissions = \CCrmPerms::GetUserPermissions($this->userID);
		}
		return $this->userPermissions;
	}
	protected function getRegisteredEntityMatches($entityTypeID, $entityID)
	{
		$results = array();
		$types = Integrity\DuplicateIndexBuilder::getExistedTypes($entityTypeID, $this->userID);
		foreach($types as $typeID)
		{
			if($typeID === Integrity\DuplicateIndexType::PERSON)
			{
				$results[$typeID] = Integrity\DuplicatePersonCriterion::getRegisteredEntityMatches($entityTypeID, $entityID);
			}
			elseif($typeID === Integrity\DuplicateIndexType::ORGANIZATION)
			{
				$results[$typeID] = Integrity\DuplicateOrganizationCriterion::getRegisteredEntityMatches($entityTypeID, $entityID);
			}
			elseif($typeID === Integrity\DuplicateIndexType::COMMUNICATION_EMAIL
				|| $typeID === Integrity\DuplicateIndexType::COMMUNICATION_PHONE)
			{
				$results[$typeID] = Integrity\DuplicateCommunicationCriterion::getRegisteredEntityMatches(
					$entityTypeID,
					$entityID,
					Integrity\DuplicateCommunicationCriterion::resolveTypeByIndexTypeID($typeID)
				);
			}
		}
		return $results;
	}
	protected function processEntityDeletion($entityTypeID, $entityID, array &$matchByType)
	{
		foreach($matchByType as $typeID => &$typeMatches)
		{
			foreach($typeMatches as &$matches)
			{
				$builder = Integrity\DuplicateManager::createIndexBuilder(
					$typeID,
					$entityTypeID,
					$this->userID,
					$this->enablePermissionCheck
				);

				$builder->processEntityDeletion(
					Integrity\DuplicateManager::createCriterion($typeID, $matches),
					$entityID
				);
			}
			unset($matches);
		}
		unset($typeMatches);
	}
	abstract protected function getEntityFieldsInfo();
	abstract protected function getEntityUserFieldsInfo();
	abstract protected function getEntityResponsibleID($entityID, $roleID);
	abstract protected function getEntityFields($entityID, $roleID);
	protected function getEntityMultiFields($entityID, $roleID)
	{
		$results = array();
		$dbResult = \CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => $this->getEntityTypeName(),
				'ELEMENT_ID' => $entityID
			)
		);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$results[$fields['TYPE_ID']][$fields['ID']] = array(
					'VALUE' => $fields['VALUE'],
					'VALUE_TYPE' => $fields['VALUE_TYPE']
				);
			}
		}
		return $results;
	}
	abstract protected function checkEntityReadPermission($entityID, $userPermissions);
	abstract protected function checkEntityUpdatePermission($entityID, $userPermissions);
	abstract protected function checkEntityDeletePermission($entityID, $userPermissions);
	abstract protected function setupRecoveryData(Recovery\EntityRecoveryData $recoveryData, array &$fields);
	abstract protected function rebind($seedID, $targID);
	abstract protected function updateEntity($entityID, array &$fields, $roleID);
	abstract protected function deleteEntity($entityID, $roleID, array $options = array());
	abstract protected function prepareCollisionMessageFields(array &$collisions, array &$seed, array &$targ);
}