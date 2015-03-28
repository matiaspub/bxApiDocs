<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class PersonDedupeDataSource extends MatchHashDedupeDataSource
{
	public function __construct(DedupeParams $params)
	{
		parent::__construct(DuplicateIndexType::PERSON, $params);
	}
	/**
	* @return Array
	*/
	protected function loadEntityMatches($entityTypeID, $entityID)
	{
		return DuplicatePersonCriterion::loadEntityMatches($entityTypeID, $entityID);
	}
	/**
	* @return Array
	*/
	protected function loadEntitesMatches($entityTypeID, array $entityIDs)
	{
		return DuplicatePersonCriterion::loadEntitiesMatches($entityTypeID, $entityIDs);
	}
	/**
	* @return Array
	*/
	protected function getEntityMatchesByHash($entityTypeID, $entityID, $matchHash)
	{
		$matches = DuplicatePersonCriterion::loadEntityMatches($entityTypeID, $entityID);
		return DuplicatePersonCriterion::prepareMatchHash($matches) === $matchHash
			? $matches : null;
	}
	/**
	* @return DuplicateCriterion
	*/
	protected function createCriterionFromMatches(array $matches)
	{
		return DuplicatePersonCriterion::createFromMatches($matches);
	}
	protected function prepareResult(array &$map, DedupeDataSourceResult $result)
	{
		$entityTypeID = $this->getEntityTypeID();
		foreach($map as $matchHash => &$entry)
		{
			$primaryQty = isset($entry['PRIMARY']) ? count($entry['PRIMARY']) : 0;
			$secondaryQty = isset($entry['SECONDARY']) ? count($entry['SECONDARY']) : 0;

			if($primaryQty > 1)
			{
				$matches = $this->getEntityMatchesByHash($entityTypeID, $entry['PRIMARY'][0], $matchHash);
				if(is_array($matches))
				{
					$criterion = $this->createCriterionFromMatches($matches);
					$dup = new Duplicate($criterion, array());
					foreach($entry['PRIMARY'] as $entityID)
					{
						$dup->addEntity(new DuplicateEntity($entityTypeID, $entityID));
					}
					$result->addItem($matchHash, $dup);
				}
			}

			if($primaryQty > 0 && $secondaryQty > 0)
			{
				$matches = $this->loadEntitesMatches($entityTypeID, $entry['SECONDARY']);
				foreach($matches as $entityID => $entityMatches)
				{
					$criterion = $this->createCriterionFromMatches($entityMatches);
					$entityMatchHash = $criterion->getMatchHash();
					if($entityMatchHash === '')
					{
						continue;
					}

					$dup = $result->getItem($entityMatchHash);
					if(!$dup)
					{
						$dup = new Duplicate($criterion, array(new DuplicateEntity($entityTypeID, $entityID)));
						$dup->setOption('enableOverwrite', false);
						$dup->setRootEntityID($entityID);
					}

					$result->addItem($entityMatchHash, $dup);
					foreach($entry['PRIMARY'] as $primaryEntityID)
					{
						$matches = $this->getEntityMatchesByHash($entityTypeID, $primaryEntityID, $matchHash);
						if(is_array($matches))
						{
							$entity = new DuplicateEntity($entityTypeID, $primaryEntityID);
							$entity->setCriterion($this->createCriterionFromMatches($matches));
							$dup->addEntity($entity);
						}
					}
				}
			}
		}
		unset($entry);
	}
	public function calculateEntityCount(DuplicateCriterion $criterion, array $options = null)
	{
		$count = $this->innerCalculateEntityCount($criterion, $options);

		$matches = $criterion->getMatches();
		$name = isset($matches['NAME']) ? $matches['NAME'] : '';
		$secondName = isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '';
		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';

		if($secondName !== '' && $name !== '')
		{
			$count += $this->innerCalculateEntityCount(
				DuplicatePersonCriterion::createFromMatches(array('LAST_NAME' => $lastName, 'NAME' => $name)),
				$options
			);
		}
		if($name !== '')
		{
			$count += $this->innerCalculateEntityCount(
				DuplicatePersonCriterion::createFromMatches(array('LAST_NAME' => $lastName)),
				$options
			);
		}

		return $count;
	}
	protected function innerCalculateEntityCount(DuplicateCriterion $criterion, array $options = null)
	{
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$userID = $this->getUserID();

		$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
		$query->addSelect('QTY');
		$query->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);

		if($enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return 0;
			}
			if(is_string($permissionSql) && $permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		$matches = $criterion->getMatches();
		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';
		if($lastName === '')
		{
			throw new Main\ArgumentException("Parameter 'LAST_NAME' is required.", 'matches');
		}
		$query->addFilter('=LAST_NAME', $lastName);
		$query->addFilter('=NAME', isset($matches['NAME']) ? $matches['NAME'] : '');
		$query->addFilter('=SECOND_NAME', isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '');

		$rootEntityID = 0;
		if(is_array($options) && isset($options['ROOT_ENTITY_ID']))
		{
			$rootEntityID =  (int)$options['ROOT_ENTITY_ID'];
		}
		if($rootEntityID > 0)
		{
			$query->addFilter('!ENTITY_ID', $rootEntityID);
			$query->addFilter(
				'!@ENTITY_ID',
				DuplicateIndexMismatch::prepareQueryField($criterion, $entityTypeID, $rootEntityID, $userID)
			);
		}

		$limit = 0;
		if(is_array($options) && isset($options['LIMIT']))
		{
			$limit =  (int)$options['LIMIT'];
		}
		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();
		$fields = $dbResult->fetch();
		return is_array($fields) && isset($fields['QTY']) ? intval($fields['QTY']) : 0;
	}
}