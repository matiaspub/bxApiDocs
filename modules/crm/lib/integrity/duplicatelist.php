<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DuplicateList
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $userID = 0;
	protected $enablePermissionCheck = false;
	protected $enableRanking = false;
	protected $sortTypeID = DuplicateIndexType::UNDEFINED;
	protected $sortOrder = SORT_ASC;

	public function __construct($typeID, $entityTypeID, $userID, $enablePermissionCheck = false)
	{
		$this->setTypeID($typeID);
		$this->setEntityTypeID($entityTypeID);
		$this->setUserID($userID);
		$this->enabledPermissionCheck($enablePermissionCheck);
	}
	public function getTypeID()
	{
		return $this->typeID;
	}
	public function setTypeID($typeID)
	{
		if(!is_numeric($typeID))
		{
			throw new Main\ArgumentTypeException('typeID', 'integer');
		}

		if(!is_int($typeID))
		{
			$typeID = intval($typeID);
		}

		$this->typeID = $typeID;
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function setEntityTypeID($entityTypeID)
	{
		if(!is_numeric($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = intval($entityTypeID);
		}

		if($entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			throw new Main\NotSupportedException("Criterion type(s): '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context.");
		}

		$this->entityTypeID = $entityTypeID;
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
	}
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	public function enabledPermissionCheck($enable)
	{
		$this->enablePermissionCheck = is_bool($enable) ? $enable : (bool)$enable;
	}
	public function isRankingEnabled()
	{
		return $this->enableRanking;
	}
	public function enabledRanking($enable)
	{
		$this->enableRanking = is_bool($enable) ? $enable : (bool)$enable;
	}
	public function getSortTypeID()
	{
		return $this->sortTypeID;
	}
	public function setSortTypeID($typeID)
	{
		if(!is_numeric($typeID))
		{
			throw new Main\ArgumentTypeException('typeID', 'integer');
		}

		if(!is_int($typeID))
		{
			$typeID = intval($typeID);
		}

		if(!DuplicateIndexType::isSigle($typeID))
		{
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context. Please use single type for sorting.");
		}
		$this->sortTypeID = $typeID;
	}
	public function getSortOrder()
	{
		return $this->sortOrder;
	}
	public function setSortOrder($sortOrder)
	{
		if(!is_numeric($sortOrder))
		{
			throw new Main\ArgumentTypeException('sortOrder', 'integer');
		}

		if(!is_int($sortOrder))
		{
			$sortOrder = intval($sortOrder);
		}

		if($sortOrder !== SORT_DESC && $sortOrder !== SORT_ASC)
		{
			throw new Main\ArgumentOutOfRangeException('sortOrder', SORT_DESC, SORT_ASC);
		}

		$this->sortOrder = $sortOrder;
	}
	public function isSortingEnabled()
	{
		return $this->sortTypeID !== DuplicateIndexType::UNDEFINED;
	}
	/**
	 * @return Main\Entity\Query
	 */
	private function createQuery($offset = 0, $limit = 0)
	{
		if(!is_int($offset))
		{
			$offset = intval($offset);
		}

		if(!is_int($limit))
		{
			$limit = intval($limit);
		}

		$typeIDs = $this->getTypeIDs();
		if(empty($typeIDs))
		{
			throw new Main\NotSupportedException("Criterion types are required.");
		}

		$query = new Main\Entity\Query(Entity\DuplicateIndexTable::getEntity());
		$query->addSelect('ROOT_ENTITY_ID');
		$query->addSelect('ROOT_ENTITY_NAME');
		$query->addSelect('ROOT_ENTITY_TITLE');
		$query->addSelect('QUANTITY');
		$query->addSelect('TYPE_ID');
		$query->addSelect('MATCHES');
		$query->addSelect('IS_JUNK');

		$permissionSql = '';
		if($this->enablePermissionCheck)
		{
			$permissions = \CCrmPerms::GetUserPermissions($this->userID);
			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($this->entityTypeID),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> $permissions)
			);

			if($permissionSql === false)
			{
				//Access denied;
				return array();
			}
		}

		$query->addFilter('=USER_ID', $this->userID);
		$query->addFilter('=ENTITY_TYPE_ID', $this->entityTypeID);
		$query->addFilter('@TYPE_ID', $typeIDs);

		if($this->enablePermissionCheck && $permissionSql !== '')
		{
			$query->addFilter('@ROOT_ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
		}

		if($offset > 0)
		{
			$query->setOffset($offset);
		}

		if($limit >  0)
		{
			$query->setLimit($limit);
		}

		$enableSorting = $this->sortTypeID !== DuplicateIndexType::UNDEFINED;
		if($enableSorting)
		{
			$order = $this->sortOrder === SORT_DESC ? 'DESC' : 'ASC';
			if($this->sortTypeID === DuplicateIndexType::COMMUNICATION_EMAIL)
			{
				$query->addOrder('ROOT_ENTITY_EMAIL_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_EMAIL', $order);
			}
			elseif($this->sortTypeID === DuplicateIndexType::COMMUNICATION_PHONE)
			{
				$query->addOrder('ROOT_ENTITY_PHONE_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_PHONE', $order);
			}
			elseif($this->sortTypeID === DuplicateIndexType::PERSON)
			{
				$query->addOrder('ROOT_ENTITY_NAME_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_NAME', $order);
			}
			elseif($this->sortTypeID === DuplicateIndexType::ORGANIZATION)
			{
				$query->addOrder('ROOT_ENTITY_TITLE_FLAG', $order);
				$query->addOrder('ROOT_ENTITY_TITLE', $order);
			}
		}
		return $query;
	}
	/**
	 * @return Duplicate[]
	 */
	public function getRootItems($offset = 0, $limit = 0)
	{
		$query = $this->createQuery($offset, $limit);
		$dbResult = $query->exec();

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = $this->createDuplicate($fields);
		}
		return $results;
	}
	/**
	 * @return Boolean
	 */
	public function isJunk($entityID)
	{
		$query = $this->createQuery(0, 0);
		$query->addFilter('=ROOT_ENTITY_ID', $entityID);
		$dbResult = $query->exec();

		$fields = $dbResult->fetch();
		return is_array($fields) && isset($fields['IS_JUNK']) && strtoupper($fields['IS_JUNK']) === 'Y';
	}
	private function createDuplicate(array &$fields)
	{
		$rootEntityID = isset($fields['ROOT_ENTITY_ID']) ? (int)$fields['ROOT_ENTITY_ID'] : 0;
		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : 0;
		$matches = isset($fields['MATCHES']) ? $fields['MATCHES'] : '';
		$matches = $matches !== '' ? unserialize($matches) : array();
		$quantity = isset($fields['QUANTITY']) ? (int)$fields['QUANTITY'] : 0;

		$result = new Duplicate(DuplicateManager::createCriterion($typeID, $matches), array());
		$result->setRootEntityID($rootEntityID);

		$isJunk = isset($fields['IS_JUNK']) && strtoupper($fields['IS_JUNK']) === 'Y';
		if($isJunk)
		{
			$result->markAsJunk(true);
			//Try to supply more information for junked item (if root entity is already deleted)
			$rootPersName = isset($fields['ROOT_ENTITY_NAME']) ? $fields['ROOT_ENTITY_NAME'] : '';
			if($rootPersName !== '')
			{
				$names = explode(' ', $rootPersName);
				$qty = count($names);
				for($i = 0; $i < $qty; $i++)
				{
					$names[$i] = ucfirst($names[$i]);
				}
				$result->setRootPersonName(implode(' ', $names));
			}

			$rootOrgTitle = isset($fields['ROOT_ENTITY_TITLE']) ? $fields['ROOT_ENTITY_TITLE'] : '';
			if($rootOrgTitle !== '')
			{
				$result->setRootOrganizationTitle(ucfirst($rootOrgTitle));
			}
		}

		$result->setTotalEntityCount($quantity);
		return $result;
	}

	private function getTypeIDs()
	{
		$result = array();
		if(($this->typeID & DuplicateIndexType::PERSON) !== 0)
		{
			$result[] = DuplicateIndexType::PERSON;
		}
		if(($this->typeID & DuplicateIndexType::ORGANIZATION) !== 0)
		{
			$result[] = DuplicateIndexType::ORGANIZATION;
		}
		if(($this->typeID & DuplicateIndexType::COMMUNICATION_PHONE) !== 0)
		{
			$result[] = DuplicateIndexType::COMMUNICATION_PHONE;
		}
		if(($this->typeID & DuplicateIndexType::COMMUNICATION_EMAIL) !== 0)
		{
			$result[] = DuplicateIndexType::COMMUNICATION_EMAIL;
		}
		return $result;
	}
}