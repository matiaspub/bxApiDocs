<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DuplicateIndexBuilder
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected $params = null;
	protected $dataSource = null;

	public function __construct($typeID, DedupeParams $params)
	{
		$this->typeID = $typeID;
		$this->params = $params;
	}
	public function getTypeID()
	{
		return $this->typeID;
	}
	/**
	 * @return DedupeParams
	 */
	public function getParams()
	{
		return $this->params;
	}
	public function getEntityTypeID()
	{
		return $this->params->getEntityTypeID();
	}
	public function getUserID()
	{
		return $this->params->getUserID();
	}
	public function isPermissionCheckEnabled()
	{
		return $this->params->isPermissionCheckEnabled();
	}
	public function getDataSource()
	{
		if($this->dataSource === null)
		{
			$this->dataSource = DedupeDataSource::create($this->typeID, $this->params);
		}
		return $this->dataSource;
	}
	public function isExists()
	{
		$params = array(
			'TYPE_ID' => $this->typeID,
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'USER_ID' => $this->getUserID()
		);

		if($this->typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::checkIndex($params);
		}
		elseif($this->typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::checkIndex($params);
		}

		elseif($this->typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $this->typeID === DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			return DuplicateCommunicationCriterion::checkIndex($params);
		}
		else
		{
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($this->typeID)."' is not supported in current context");
		}
	}
	public function remove()
	{
		Entity\DuplicateIndexTable::deleteByFilter(
			array(
				'TYPE_ID' => $this->typeID,
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'USER_ID' => $this->getUserID()
			)
		);
	}
	public function build(array &$progressData)
	{
		return $this->internalBuild($progressData);
	}
	public function processMismatchRegistration(DuplicateCriterion $criterion, $entityID = 0)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			$entityID = $this->getRootEntityID($criterion->getMatchHash());
		}

		if($entityID <= 0)
		{
			return;
		}

		$quantity = $criterion->getActualCount($this->getEntityTypeID(), $entityID, $this->getUserID(), $this->isPermissionCheckEnabled(), 100);
		if($quantity === 0)
		{
			Entity\DuplicateIndexTable::delete($this->getPrimaryKey($criterion->getMatchHash()));
		}
	}
	public function processEntityDeletion(DuplicateCriterion $criterion, $entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return;
		}

		$matchHash = $criterion->getMatchHash();
		$rootEntityID = $this->getRootEntityID($matchHash);

		if($rootEntityID <= 0)
		{
			return;
		}

		$entityTypeID = $this->getEntityTypeID();
		$userID = $this->getUserID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$quantity = $criterion->getActualCount($entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, 100);
		if($quantity === 0)
		{
			Entity\DuplicateIndexTable::delete($this->getPrimaryKey($matchHash));
			return;
		}

		if($entityID !== $rootEntityID)
		{
			return;
		}

		$dataSource = $this->getDataSource();
		$result = $dataSource->getList(0, 100);

		$item = $result->getItem($matchHash);
		if(!$item)
		{
			return;
		}

		$rankings = $item->getAllRankings();
		DuplicateEntityRanking::initializeBulk($rankings,
			array('CHECK_PERMISSIONS' => $enablePermissionCheck, 'USER_ID' => $userID)
		);
		$rootEntityInfo = array();
		if(!$this->tryResolveRootEntity($item, $matchHash, $rootEntityInfo))
		{
			Entity\DuplicateIndexTable::delete($this->getPrimaryKey($matchHash));
			return;
		}

		$rootEntityID = $rootEntityInfo['ENTITY_ID'];
		$item->setRootEntityID($rootEntityID);
		$sortParams = $this->prepareSortParams(array($rootEntityID));
		$data = $this->prepareTableData($matchHash, $item, $sortParams, true);
		Entity\DuplicateIndexTable::upsert($data);
	}

	public static function getExistedTypes($entityTypeID, $userID)
	{
		$dbResult = Entity\DuplicateIndexTable::getList(
			array(
				'select' => array('TYPE_ID'),
				'order' => array('TYPE_ID' => 'ASC'),
				'group' => array('TYPE_ID'),
				'filter' => array(
					'=USER_ID' => $userID,
					'=ENTITY_TYPE_ID' => $entityTypeID
				)
			)
		);

		$result = array();
		while($fields = $dbResult->fetch())
		{
			$result[] = intval($fields['TYPE_ID']);
		}
		return $result;
	}
	public static function markAsJunk($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException("Is not defined or invalid", 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException("Must be greater than zero", 'entityID');
		}

		Entity\DuplicateIndexTable::markAsJunk($entityTypeID, $entityID);
	}
	protected function internalBuild(array &$progressData)
	{
		$offset = isset($progressData['OFFSET']) ? max((int)$progressData['OFFSET'], 0) : 0;
		$limit = isset($progressData['LIMIT']) ? max((int)$progressData['LIMIT'], 0) : 0;

		$dataSource = $this->getDataSource();
		$result = $dataSource->getList($offset, $limit);

		$rankings = $result->getAllRankings();
		DuplicateEntityRanking::initializeBulk($rankings,
			array('CHECK_PERMISSIONS' => $this->isPermissionCheckEnabled(), 'USER_ID' => $this->getUserID())
		);

		$rootEntityIDs = array();
		$items = $result->getItems();
		foreach($items as $matchHash => $item)
		{
			$rootEntityInfo = array();
			if($this->tryResolveRootEntity($item, $matchHash, $rootEntityInfo))
			{
				$entityID = $rootEntityInfo['ENTITY_ID'];
				$rootEntityIDs[] = $entityID;
				$item->setRootEntityID($entityID);
			}
			else
			{
				$result->removeItem($matchHash);
			}
		}

		$sortParams = $this->prepareSortParams($rootEntityIDs);
		$effectiveItemCount = 0;

		$items = $result->getItems();
		foreach($items as $matchHash => $item)
		{
			$enableOverwrite = $item->getOption('enableOverwrite', true);
			if(!$enableOverwrite
				&& Entity\DuplicateIndexTable::exists($this->getPrimaryKey($matchHash)))
			{
				continue;
			}

			$data = $this->prepareTableData($matchHash, $item, $sortParams, true);
			Entity\DuplicateIndexTable::upsert($data);
			$effectiveItemCount++;
		}

		$processedItemCount = $result->getProcessedItemCount();
		$progressData['EFFECTIVE_ITEM_COUNT'] = $effectiveItemCount;
		$progressData['PROCESSED_ITEM_COUNT'] = $processedItemCount;
		$progressData['OFFSET'] = $offset + $processedItemCount;

		return $this->isInProgress($progressData);
	}
	public function isInProgress(array &$progressData)
	{
		return isset($progressData['PROCESSED_ITEM_COUNT']) && $progressData['PROCESSED_ITEM_COUNT'] > 0;
	}
	protected function getRootEntityID($matchHash)
	{
		$query = new Main\Entity\Query(Entity\DuplicateIndexTable::getEntity());
		$query->addSelect('ROOT_ENTITY_ID');

		$query->addFilter('=USER_ID', $this->getUserID());
		$query->addFilter('=ENTITY_TYPE_ID', $this->getEntityTypeID());
		$query->addFilter('=TYPE_ID', $this->typeID);
		$query->addFilter('=MATCH_HASH', $matchHash);

		$fields = $query->exec()->fetch();
		return is_array($fields) ? (int)$fields['ROOT_ENTITY_ID'] : 0;
	}
	protected function getPrimaryKey($matchHash)
	{
		return array(
			'USER_ID' => $this->getUserID(),
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'TYPE_ID' => $this->typeID,
			'MATCH_HASH' => $matchHash
		);
	}
	protected function checkRootEntityMismatches($rootEntityID, $matchHash, array $entities)
	{
		$map = array();
		/** @var DuplicateEntity $entity */
		foreach($entities as $entity)
		{
			$entityID = $entity->getEntityID();
			if($entityID === $rootEntityID)
			{
				continue;
			}

			$entityCriterion = $entity->getCriterion();
			$entityMatchHash = $entityCriterion ? $entityCriterion->getMatchHash() : $matchHash;
			if(!isset($map[$entityMatchHash]))
			{
				$map[$entityMatchHash] = array();
			}
			$map[$entityMatchHash][] = $entityID;
		}

		foreach($map as $entityMatchHash => $entityIDs)
		{
			$mismatches = array_intersect(
				$entityIDs,
				DuplicateIndexMismatch::getMismatches(
					$this->getEntityTypeID(),
					$rootEntityID,
					$this->typeID,
					$entityMatchHash,
					$this->getUserID(),
					100
				)
			);

			if(count($entityIDs) > count($mismatches))
			{
				return true;
			}
		}
		return false;
	}
	protected function tryResolveRootEntity(Duplicate $item, $matchHash, array &$entityInfo)
	{
		$entityTypeID = $this->getEntityTypeID();
		$entities = $item->getEntitiesByType($entityTypeID);

		/** @var DuplicateEntity[] $entities */
		$qty = count($entities);
		if($qty == 0)
		{
			return false;
		}
		elseif($qty === 1)
		{
			$entity = $entities[0];
			$entityID = $entity->getEntityID();

			$entityInfo['ENTITY_ID'] = $entityID;
			return true;
		}

		$entityID = $item->getRootEntityID();
		$entity = $entityID > 0 ? $item->findEntity($entityTypeID, $entityID) : null;
		if($entity)
		{
			if($this->checkRootEntityMismatches($entityID, $matchHash, $entities))
			{
				$entityInfo['ENTITY_ID'] = $entityID;
				return true;
			}
		}

		usort($entities, array('Bitrix\Crm\Integrity\DuplicateEntity', 'compareByRanking'));
		for($i = ($qty - 1); $i >= 0; $i--)
		{
			$entity = $entities[$i];
			if($entity->getCriterion() !== null)
			{
				continue;
			}

			$entityID = $entity->getEntityID();

			if($this->checkRootEntityMismatches($entityID, $matchHash, $entities))
			{
				$entityInfo['ENTITY_ID'] = $entityID;
				return true;
			}
		}
		return false;
	}
	protected function prepareSortParams(array $entityIDs)
	{
		$resut = array(
			'PERS' => array(),
			'ORG' => array(),
			'COMM' => array()
		);
		if(!empty($entityIDs))
		{
			$entityTypeID = $this->getEntityTypeID();
			if($entityTypeID === \CCrmOwnerType::Lead)
			{
				$resut['PERS'] = DuplicatePersonCriterion::prepareSortParams($entityTypeID, $entityIDs);
				$resut['ORG'] = DuplicateOrganizationCriterion::prepareSortParams($entityTypeID, $entityIDs);
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact)
			{
				$resut['PERS'] = DuplicatePersonCriterion::prepareSortParams($entityTypeID, $entityIDs);
			}
			elseif($entityTypeID === \CCrmOwnerType::Company)
			{
				$resut['ORG'] = DuplicateOrganizationCriterion::prepareSortParams($entityTypeID, $entityIDs);
			}
			$resut['COMM'] = DuplicateCommunicationCriterion::prepareSortParams($entityTypeID, $entityIDs);
		}
		return $resut;
	}
	protected function prepareTableData($matchHash, Duplicate $item, array &$sortParams, $enablePrimaryKey = true)
	{
		$data = array(
			'ROOT_ENTITY_ID' => 0,
			'ROOT_ENTITY_NAME' => '',
			'ROOT_ENTITY_TITLE' => '',
			'ROOT_ENTITY_PHONE' => '',
			'ROOT_ENTITY_EMAIL' => '',
			'QUANTITY' => 0
		);

		if($enablePrimaryKey)
		{
			$data['USER_ID'] = $this->getUserID();
			$data['ENTITY_TYPE_ID'] = $this->getEntityTypeID();
			$data['TYPE_ID'] = $this->typeID;
			$data['MATCH_HASH'] = $matchHash;

			$criterion = $item->getCriterion();
			$data['MATCHES'] = serialize($criterion->getMatches());
		}

		$entityID = $item->getRootEntityID();
		if($entityID > 0)
		{
			$data['ROOT_ENTITY_ID'] = $entityID;

			$pers = isset($sortParams['PERS']) ? $sortParams['PERS'] : null;
			if(is_array($pers) && isset($pers[$entityID]) && isset($pers[$entityID]['FULL_NAME']))
			{
				$data['ROOT_ENTITY_NAME'] = $pers[$entityID]['FULL_NAME'];
			}
			$org = isset($sortParams['ORG']) ? $sortParams['ORG'] : null;
			if(is_array($org) && isset($org[$entityID]) && isset($org[$entityID]['TITLE']))
			{
				$data['ROOT_ENTITY_TITLE'] = $org[$entityID]['TITLE'];
			}

			$comm = isset($sortParams['COMM']) ? $sortParams['COMM'] : null;
			if(is_array($comm) && isset($comm[$entityID]))
			{
				if(isset($comm[$entityID]['PHONE']))
				{
					$data['ROOT_ENTITY_PHONE'] = $comm[$entityID]['PHONE'];
				}
				if(isset($comm[$entityID]['EMAIL']))
				{
					$data['ROOT_ENTITY_EMAIL'] = $comm[$entityID]['EMAIL'];
				}
			}
		}

		return $data;
	}
}