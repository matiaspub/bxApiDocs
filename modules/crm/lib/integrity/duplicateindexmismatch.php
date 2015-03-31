<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Main;

class DuplicateIndexMismatch
{
	public static function register($entityTypeID, $leftEntityID, $rightEntityID, $typeID, $matchHash, $userID)
	{
		Entity\DuplicateIndexMismatchTable::upsert(
			array(
				'USER_ID'=> $userID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash,
				'L_ENTITY_ID' => $leftEntityID,
				'R_ENTITY_ID' => $rightEntityID
			)
		);

	}
	public static function unregister($entityTypeID, $leftEntityID, $rightEntityID, $typeID, $matchHash, $userID)
	{
		Entity\DuplicateIndexMismatchTable::delete(
			array(
				'USER_ID'=> $userID,
				'ENTITY_TYPE_ID' => $entityTypeID,
				'TYPE_ID' => $typeID,
				'MATCH_HASH' => $matchHash,
				'L_ENTITY_ID' => $leftEntityID,
				'R_ENTITY_ID' => $rightEntityID
			)
		);
	}
	public static function unregisterEntity($entityTypeID, $entityID)
	{
		Entity\DuplicateIndexMismatchTable::deleteByEntity($entityTypeID, $entityID);
	}
	public static function prepareQueryField(DuplicateCriterion $criterion, $entityTypeID, $entityID, $userID)
	{
		$typeID = $criterion->getIndexTypeID();
		$matchHash = $criterion->getMatchHash();

		$sql = array();

		$query = new Main\Entity\Query(Entity\DuplicateIndexMismatchTable::getEntity());
		$query->addSelect('R_ENTITY_ID', 'ENTITY_ID');
		$query->addFilter('=USER_ID', $userID);
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);
		$query->addFilter('=MATCH_HASH', $matchHash);
		$query->addFilter('=L_ENTITY_ID', $entityID);

		$sql[] = $query->getQuery();

		$query = new Main\Entity\Query(Entity\DuplicateIndexMismatchTable::getEntity());
		$query->addSelect('L_ENTITY_ID', 'ENTITY_ID');
		$query->addFilter('=USER_ID', $userID);
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);
		$query->addFilter('=MATCH_HASH', $matchHash);
		$query->addFilter('=R_ENTITY_ID', $entityID);

		$sql[] = $query->getQuery();
		return new Main\DB\SqlExpression(implode(' UNION ALL ', $sql));
	}
	public static function getMismatches($entityTypeID, $entityID, $typeID, $matchHash, $userID, $limit = 0)
	{
		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		$results = array();

		$query = new Main\Entity\Query(Entity\DuplicateIndexMismatchTable::getEntity());
		$query->addSelect('R_ENTITY_ID', 'ENTITY_ID');
		$query->addFilter('=USER_ID', $userID);
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);
		$query->addFilter('=MATCH_HASH', $matchHash);
		$query->addFilter('=L_ENTITY_ID', $entityID);

		if($limit > 0)
		{
			$query->addOrder('R_ENTITY_ID', 'ASC');
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$results[] = (int)$fields['ENTITY_ID'];
		}

		$query = new Main\Entity\Query(Entity\DuplicateIndexMismatchTable::getEntity());
		$query->addSelect('L_ENTITY_ID', 'ENTITY_ID');
		$query->addFilter('=USER_ID', $userID);
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=TYPE_ID', $typeID);
		$query->addFilter('=MATCH_HASH', $matchHash);
		$query->addFilter('=R_ENTITY_ID', $entityID);

		if($limit > 0)
		{
			$query->addOrder('L_ENTITY_ID', 'ASC');
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$results[] = (int)$fields['ENTITY_ID'];
		}

		return $results;
	}
}