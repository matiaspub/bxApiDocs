<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Crm\DealTable;
use Bitrix\Main;

class DuplicateEntityRanking
{
	/** @var DuplicateEntity $entity */
	protected $entity = null;
	protected $referenceCount = 0;
	protected $lastChanged = 0;
	protected $completeness = 0;
	protected $editable = false;
	protected $deleteable = false;

	public function __construct(DuplicateEntity $entity, $referenceCount = 0, $lastChanged = 0, $completeness = 0, $editable = false, $deleteable = false)
	{
		$this->entity = $entity;

		$this->setReferenceCount($referenceCount);
		$this->setLastChanged($lastChanged);
		$this->setCompleteness($completeness);
		$this->setEditable($editable);
		$this->setDeleteable($deleteable);
	}
	public function getEntity()
	{
		return $this->entity;
	}
	public function getReferenceCount()
	{
		return $this->referenceCount;
	}
	public function setReferenceCount($referenceCount)
	{
		if(!is_integer($referenceCount))
		{
			throw new Main\ArgumentTypeException('type', 'integer');
		}
		$this->referenceCount = $referenceCount;
	}
	public function getLastChanged()
	{
		return $this->lastChanged;
	}
	public function setLastChanged($lastChanged)
	{
		$this->lastChanged = $lastChanged;
	}
	public function getCompleteness()
	{
		return $this->completeness;
	}
	public function setCompleteness($completeness)
	{
		if(!is_integer($completeness))
		{
			throw new Main\ArgumentTypeException('type', 'integer');
		}
		$this->completeness = $completeness;
	}
	public function isEditable()
	{
		return $this->editable;
	}
	public function setEditable($editable)
	{
		if(!is_bool($editable))
		{
			throw new Main\ArgumentTypeException('type', 'boolean');
		}
		$this->editable = $editable;
	}
	public function isDeleteable()
	{
		return $this->deleteable;
	}
	public function setDeleteable($deleteable)
	{
		if(!is_bool($deleteable))
		{
			throw new Main\ArgumentTypeException('type', 'boolean');
		}
		$this->deleteable = $deleteable;
	}
	public static function compare(DuplicateEntityRanking $a, DuplicateEntityRanking $b)
	{
		if($a->editable !== $b->editable)
		{
			return $a->editable ? 1 : -1;
		}

		$result = $a->referenceCount - $b->referenceCount;
		if($result === 0)
		{
			$result = $a->lastChanged - $b->lastChanged;
		}
		if($result === 0)
		{
			$result = $a->completeness - $b->completeness;
		}
		return $result;
	}
	public static function compareReversed(DuplicateEntityRanking &$a, DuplicateEntityRanking &$b)
	{
		return (-1 * self::compare($a, $b));
	}
	public static function registerEntityStatistics($entityTypeID, $entityID, array $entityFields)
	{
		$lastChanged = 0;
		$completeness = 0;
		if($entityTypeID === \CCrmOwnerType::Contact)
		{
			if(isset($entityFields['DATE_MODIFY']) && $entityFields['DATE_MODIFY'] !== '')
			{
				$lastChanged = MakeTimeStamp($entityFields['DATE_MODIFY']);
			}

			if(isset($entityFields['NAME']) && $entityFields['NAME'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['SECOND_NAME']) && $entityFields['SECOND_NAME'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['LAST_NAME']) && $entityFields['LAST_NAME'] !== '')
			{
				$completeness++;
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Company)
		{
			if(isset($entityFields['DATE_MODIFY']) && $entityFields['DATE_MODIFY'] !== '')
			{
				$lastChanged = MakeTimeStamp($entityFields['DATE_MODIFY']);
			}

			if(isset($entityFields['TITLE']) && $entityFields['TITLE'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['ADDRESS']) && $entityFields['ADDRESS'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['ADDRESS_LEGAL']) && $entityFields['ADDRESS_LEGAL'] !== '')
			{
				$completeness++;
			}
		}
		elseif($entityTypeID === \CCrmOwnerType::Lead)
		{
			if(isset($entityFields['DATE_MODIFY']) && $entityFields['DATE_MODIFY'] !== '')
			{
				$lastChanged = MakeTimeStamp($entityFields['DATE_MODIFY']);
			}

			if(isset($entityFields['TITLE']) && $entityFields['TITLE'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['COMPANY_TITLE']) && $entityFields['COMPANY_TITLE'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['NAME']) && $entityFields['NAME'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['SECOND_NAME']) && $entityFields['SECOND_NAME'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['LAST_NAME']) && $entityFields['LAST_NAME'] !== '')
			{
				$completeness++;
			}
			if(isset($entityFields['ADDRESS']) && $entityFields['ADDRESS'] !== '')
			{
				$completeness++;
			}
		}
		else
		{
			throw new Main\NotSupportedException('Entity type: '.\CCrmOwnerType::ResolveName($entityTypeID).' is not supported in current context');
		}

		if($lastChanged <= 0)
		{
			$lastChanged = time() + \CTimeZone::GetOffset();
		}

		$completeness += count(DuplicateCommunicationCriterion::getRegisteredTypes($entityTypeID, $entityID));

		Entity\DuplicateEntityStatisticsTable::upsert(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'RANKING_DATA' => serialize(
					array(
						'LAST_CHANGED' => $lastChanged,
						'COMPLETENESS' => $completeness
					)
				)
			)
		);
	}
	public static function unRegisterEntityStatistics($entityTypeID, $entityID)
	{
		Entity\DuplicateEntityStatisticsTable::delete(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
			)
		);
	}
	public static function initializeBulk(array &$rankings, array $options = null)
	{
		if(empty($rankings))
		{
			return;
		}

		$map = array();
		$companyIDs = array();
		$contactIDs = array();
		$leadIDs = array();
		/** @var DuplicateEntityRanking $ranking */
		foreach($rankings as $ranking)
		{
			$entityTypeID = $ranking->entity->getEntityTypeID();
			$entityID = $ranking->entity->getEntityID();

			$map["{$entityTypeID}_{$entityID}"] = $ranking;

			if($entityTypeID === \CCrmOwnerType::Company && !isset($companyIDs[$entityID]))
			{
				$companyIDs[$entityID] = $entityID;
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact && !isset($contactIDs[$entityID]))
			{
				$contactIDs[$entityID] = $entityID;
			}
			elseif($entityTypeID === \CCrmOwnerType::Lead && !isset($leadIDs[$entityID]))
			{
				$leadIDs[$entityID] = $entityID;
			}
		}

		if(!empty($companyIDs))
		{
			self::loadBulk(\CCrmOwnerType::Company, $companyIDs, $map, $options);
		}
		if(!empty($contactIDs))
		{
			self::loadBulk(\CCrmOwnerType::Contact, $contactIDs, $map, $options);
		}
		if(!empty($leadIDs))
		{
			self::loadBulk(\CCrmOwnerType::Lead, $leadIDs, $map, $options);
		}
	}

	private static function loadBulk($entityTypeID, array &$entityIDs, array &$itemMap, array $options = null)
	{
		/** @var DuplicateEntityRanking[] $itemMap */
		if($entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company
			&& $entityTypeID !== \CCrmOwnerType::Lead)
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$checkPermissions = isset($options['CHECK_PERMISSIONS']) ? (bool)$options['CHECK_PERMISSIONS'] : false;
		$userID = isset($options['USER_ID']) ? (int)$options['USER_ID'] : 0;
		$permissions = $checkPermissions ? \CCrmPerms::GetUserPermissions($userID) : null;

		$limit = isset($options['LIMIT']) ? (int)$options['LIMIT'] : 3000;
		if($limit <= 0)
		{
			$limit = 3000;
		}

		$length = count($entityIDs);
		if($length === 0)
		{
			return;
		}

		while($length > 0)
		{
			if($length <= $limit)
			{
				$ids = $entityIDs;
				unset($entityIDs);
				$entityIDs = array();
			}
			else
			{
				$ids = array_splice($entityIDs, 0, $limit);
			}
			$length = count($entityIDs);

			if(empty($ids))
			{
				continue;
			}

			if($entityTypeID === \CCrmOwnerType::Lead)
			{
				$dbResult = Entity\DuplicateEntityStatisticsTable::getList(
					array(
						'select' => array('ENTITY_ID', 'RANKING_DATA'),
						'filter' => array(
							'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
							'ENTITY_ID' => $ids
						)
					)
				);
				while($fields = $dbResult->fetch())
				{
					$entityID = intval($fields['ENTITY_ID']);

					$key = "{$entityTypeID}_{$entityID}";
					if(!isset($itemMap[$key]))
					{
						continue;
					}

					if(isset($fields['RANKING_DATA']) && $fields['RANKING_DATA'] !== '')
					{
						$data = unserialize($fields['RANKING_DATA']);

						/** @var DuplicateEntityRanking $ranking */
						$ranking = $itemMap[$key];

						$ranking->lastChanged = isset($data['LAST_CHANGED']) ? $data['LAST_CHANGED'] : 0;
						$ranking->completeness = isset($data['COMPLETENESS']) ? $data['COMPLETENESS'] : 0;

						if($checkPermissions)
						{
							$ranking->editable = \CCrmLead::CheckUpdatePermission($entityID, $permissions);
							$ranking->deleteable = \CCrmLead::CheckDeletePermission($entityID, $permissions);
						}
					}
				}
			}
			else
			{
				$query = new Main\Entity\Query(Entity\DuplicateEntityStatisticsTable::getEntity());
				$query->addSelect('ENTITY_ID');
				$query->addSelect('RANKING_DATA');
				$query->addFilter('ENTITY_ID', $ids);
				$query->addFilter('ENTITY_TYPE_ID', $entityTypeID);

				if($entityTypeID === \CCrmOwnerType::Contact)
				{
					$subQuery = new Main\Entity\Query(DealTable::getEntity());
					$subQuery->addSelect('CONTACT_ID');
					$subQuery->addFilter('CONTACT_ID', $ids);
					$subQuery->addSelect('QTY');
					$subQuery->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));

					$referenceField = new Main\Entity\ReferenceField('D',
						Main\Entity\Base::getInstanceByQuery($subQuery),
						array('=this.ENTITY_ID' => 'ref.CONTACT_ID'),
						array('join_type' => 'LEFT')
					);
				}
				else//($entityTypeID === \CCrmOwnerType::Company)
				{
					$subQuery = new Main\Entity\Query(DealTable::getEntity());
					$subQuery->addSelect('COMPANY_ID');
					$subQuery->addFilter('COMPANY_ID', $ids);
					$subQuery->addSelect('QTY');
					$subQuery->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));

					$referenceField = new Main\Entity\ReferenceField('D',
						Main\Entity\Base::getInstanceByQuery($subQuery),
						array('=this.ENTITY_ID' => 'ref.COMPANY_ID'),
						array('join_type' => 'LEFT')
					);
				}
				$query->registerRuntimeField('', $referenceField);
				$query->addSelect('D.QTY', 'QTY');

				$dbResult = $query->exec();
				while($fields = $dbResult->fetch())
				{
					$entityID = intval($fields['ENTITY_ID']);

					$key = "{$entityTypeID}_{$entityID}";
					if(!isset($itemMap[$key]))
					{
						continue;
					}

					$itemMap[$key]->referenceCount = isset($fields['QTY']) ? intval($fields['QTY']) : 0;
					if(isset($fields['RANKING_DATA']) && $fields['RANKING_DATA'] !== '')
					{
						$data = unserialize($fields['RANKING_DATA']);

						/** @var DuplicateEntityRanking $ranking */
						$ranking = $itemMap[$key];

						$ranking->lastChanged = isset($data['LAST_CHANGED']) ? $data['LAST_CHANGED'] : 0;
						$ranking->completeness = isset($data['COMPLETENESS']) ? $data['COMPLETENESS'] : 0;

						if($checkPermissions)
						{
							if($entityTypeID === \CCrmOwnerType::Contact)
							{
								$ranking->editable = \CCrmContact::CheckUpdatePermission($entityID, $permissions);
								$ranking->deleteable = \CCrmContact::CheckDeletePermission($entityID, $permissions);
							}
							else
							{
								$ranking->editable = \CCrmCompany::CheckUpdatePermission($entityID, $permissions);
								$ranking->deleteable = \CCrmCompany::CheckDeletePermission($entityID, $permissions);
							}
						}
					}
				}
			}
		}
	}
}