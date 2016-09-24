<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class CommunicationDedupeDataSource extends MatchHashDedupeDataSource
{
	public function __construct($typeID, DedupeParams $params)
	{
		if($typeID !== DuplicateIndexType::COMMUNICATION_PHONE
			&& $typeID !== DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			throw new Main\NotSupportedException("Type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}

		parent::__construct($typeID, $params);
	}
	protected function getCommunicationType()
	{
		return $this->typeID === DuplicateIndexType::COMMUNICATION_EMAIL ? 'EMAIL' : 'PHONE';
	}
	/**
	* @return Array
	*/
	protected function getEntityMatchesByHash($entityTypeID, $entityID, $matchHash)
	{
		$allMatches = DuplicateCommunicationCriterion::loadEntityMatches($entityTypeID, $entityID, $this->getCommunicationType());
		foreach($allMatches as $matches)
		{
			if(DuplicateCommunicationCriterion::prepareMatchHash($matches) === $matchHash)
			{
				return $matches;
			}
		}
		return null;
	}
	/**
	* @return DuplicateCriterion
	*/
	protected function createCriterionFromMatches(array $matches)
	{
		return DuplicateCommunicationCriterion::createFromMatches($matches);
	}
	protected function findEntityMatches($entityTypeID, $entityID, $matchHash)
	{
		return DuplicateCommunicationCriterion::loadEntityMatches($entityTypeID, $entityID, $this->getCommunicationType());
	}
	protected function prepareResult(array &$map, DedupeDataSourceResult $result)
	{
		$entityTypeID = $this->getEntityTypeID();
		foreach($map as $matchHash => &$entry)
		{
			$primaryQty = isset($entry['PRIMARY']) ? count($entry['PRIMARY']) : 0;
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
		}
		unset($entry);
	}
	public function calculateEntityCount(DuplicateCriterion $criterion, array $options = null)
	{
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$userID = $this->getUserID();

		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
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
		$type = isset($matches['TYPE']) ? $matches['TYPE'] : '';
		if($type === '')
		{
			throw new Main\ArgumentException("Parameter 'TYPE' is required.", 'matches');
		}

		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		if($type === '')
		{
			throw new Main\ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		$query->addFilter('=TYPE', $type);
		$query->addFilter('=VALUE', $value);

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