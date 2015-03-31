<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class OrganizationDedupeDataSource extends MatchHashDedupeDataSource
{
	public function __construct(DedupeParams $params)
	{
		parent::__construct(DuplicateIndexType::ORGANIZATION, $params);
	}
	/**
	* @return Array
	*/
	protected function getEntityMatchesByHash($entityTypeID, $entityID, $matchHash)
	{
		$matches = DuplicateOrganizationCriterion::loadEntityMatches($entityTypeID, $entityID);
		return DuplicateOrganizationCriterion::prepareMatchHash($matches) === $matchHash
			? $matches : null;
	}
	/**
	* @return DuplicateCriterion
	*/
	protected function createCriterionFromMatches(array $matches)
	{
		return DuplicateOrganizationCriterion::createFromMatches($matches);
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

		$query = new Main\Entity\Query(DuplicateOrganizationMatchCodeTable::getEntity());
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
		$title = isset($matches['TITLE']) ? $matches['TITLE'] : '';
		if($title === '')
		{
			throw new Main\ArgumentException("Parameter 'TITLE' is required.", 'matches');
		}
		$query->addFilter('=TITLE', $title);

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