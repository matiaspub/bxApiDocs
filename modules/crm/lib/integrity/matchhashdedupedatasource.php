<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

abstract class MatchHashDedupeDataSource extends DedupeDataSource
{
	public function __construct($typeID, DedupeParams $params)
	{
		parent::__construct($typeID, $params);
	}
	/**
	* @return DuplicateCriterion
	*/
	abstract protected function createCriterionFromMatches(array $matches);
	abstract protected function prepareResult(array &$map, DedupeDataSourceResult $result);
	/**
	* @return DedupeDataSourceResult
	*/
	public function getList($offset, $limit)
	{
		$result = new DedupeDataSourceResult();

		$typeID = $this->typeID;
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		//$userID = $this->getUserID();

		$query = new Main\Entity\Query(Entity\DuplicateEntityMatchHashTable::getEntity());

		$query->addSelect('MATCH_HASH');
		$query->addGroup('MATCH_HASH');
		$query->addOrder('MATCH_HASH', 'ASC');

		$query->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$query->addSelect('QTY');
		$query->addFilter('>QTY', 1);

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);

		$permissionSql = '';
		if($enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return $result;
			}
			if($permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		if(!is_int($offset))
		{
			$offset = (int)$offset;
		}
		if($offset > 0)
		{
			$query->setOffset($offset);
		}

		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}
		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();

		$processedItemCount = 0;
		$lightHashes = array();
		$heavyHashes = array();
		while($fields = $dbResult->fetch())
		{
			$processedItemCount++;

			$quantity = isset($fields['QTY']) ? (int)$fields['QTY'] : 0;
			$matchHash = isset($fields['MATCH_HASH']) ? $fields['MATCH_HASH'] : '';
			if($matchHash === '' || $quantity < 2)
			{
				continue;
			}

			if($quantity <= 100)
			{
				$lightHashes[] = $matchHash;
			}
			else
			{
				$heavyHashes[] = $matchHash;
			}
		}
		$result->setProcessedItemCount($processedItemCount);

		$map = array();
		if(!empty($heavyHashes))
		{
			foreach($heavyHashes as $matchHash)
			{
				$query = new Main\Entity\Query(Entity\DuplicateEntityMatchHashTable::getEntity());
				$query->addSelect('ENTITY_ID');
				$query->addSelect('IS_PRIMARY');

				$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
				$query->addFilter('=TYPE_ID', $typeID);
				$query->addFilter('=MATCH_HASH', $matchHash);

				if($enablePermissionCheck && $permissionSql !== '')
				{
					$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
				}

				$query->setOffset(0);
				$query->setLimit(100);

				$dbResult = $query->exec();
				while($fields = $dbResult->fetch())
				{
					$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
					if($entityID <= 0)
					{
						continue;
					}

					if(!isset($map[$matchHash]))
					{
						$map[$matchHash] = array();
					}

					$isPrimary = isset($fields['IS_PRIMARY']) && $fields['IS_PRIMARY'] === 'Y';
					if($isPrimary)
					{
						if(!isset($map[$matchHash]['PRIMARY']))
						{
							$map[$matchHash]['PRIMARY'] = array();
						}
						$map[$matchHash]['PRIMARY'][] = $entityID;
					}
					else
					{
						if(!isset($map[$matchHash]['SECONDARY']))
						{
							$map[$matchHash]['SECONDARY'] = array();
						}
						$map[$matchHash]['SECONDARY'][] = $entityID;
					}
				}
			}
		}
		if(!empty($lightHashes))
		{
			$query = new Main\Entity\Query(Entity\DuplicateEntityMatchHashTable::getEntity());
			$query->addSelect('ENTITY_ID');
			$query->addSelect('MATCH_HASH');
			$query->addSelect('IS_PRIMARY');

			$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
			$query->addFilter('=TYPE_ID', $typeID);
			$query->addFilter('@MATCH_HASH', $lightHashes);

			if($enablePermissionCheck && $permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}

			$dbResult = $query->exec();
			while($fields = $dbResult->fetch())
			{
				$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
				if($entityID <= 0)
				{
					continue;
				}

				$matchHash = isset($fields['MATCH_HASH']) ? $fields['MATCH_HASH'] : '';
				if($matchHash === '')
				{
					continue;
				}

				if(!isset($map[$matchHash]))
				{
					$map[$matchHash] = array();
				}

				$isPrimary = isset($fields['IS_PRIMARY']) && $fields['IS_PRIMARY'] === 'Y';
				if($isPrimary)
				{
					if(!isset($map[$matchHash]['PRIMARY']))
					{
						$map[$matchHash]['PRIMARY'] = array();
					}
					$map[$matchHash]['PRIMARY'][] = $entityID;
				}
				else
				{
					if(!isset($map[$matchHash]['SECONDARY']))
					{
						$map[$matchHash]['SECONDARY'] = array();
					}
					$map[$matchHash]['SECONDARY'][] = $entityID;
				}
			}
		}

		$this->prepareResult($map, $result);

		return $result;
	}
}