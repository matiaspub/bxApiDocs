<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm;
class DuplicatePersonCriterion extends DuplicateCriterion
{
	private static $LANG_INCLUDED = false;
	protected $name = '';
	protected $secondName = '';
	protected $lastName = '';

	public function __construct($lastName, $name = '', $secondName = '')
	{
		$this->setLastName($lastName);
		$this->setName($name);
		$this->setSecondName($secondName);
	}

	public function getName()
	{
		return $this->name;
	}
	public function setName($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}
		$this->name = $name;
	}
	public function getSecondName()
	{
		return $this->secondName;
	}
	public function setSecondName($secondName)
	{
		if(!is_string($secondName))
		{
			throw new Main\ArgumentTypeException('secondName', 'string');
		}
		$this->secondName = $secondName;
	}
	public function getLastName()
	{
		return $this->lastName;
	}
	public function setLastName($lastName)
	{
		if(!is_string($lastName))
		{
			throw new Main\ArgumentTypeException('lastName', 'string');
		}
		$this->lastName = $lastName;
	}
	public static function prepareCode($name)
	{
		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		if($name === '')
		{
			return '';
		}

		return strtolower(trim($name));
	}
	public static function register($entityTypeID, $entityID, $lastName, $name, $secondName, $isRaw = true)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_string($lastName))
		{
			throw new Main\ArgumentTypeException('lastName', 'string');
		}

		if(!is_string($name))
		{
			throw new Main\ArgumentTypeException('name', 'string');
		}

		if(!is_string($secondName))
		{
			throw new Main\ArgumentTypeException('secondName', 'string');
		}

		if($isRaw)
		{
			$lastName = self::prepareCode($lastName);
			$name = self::prepareCode($name);
			$secondName = self::prepareCode($secondName);
		}

		self::unregister($entityTypeID, $entityID);

		if($lastName !== '')
		{
			DuplicatePersonMatchCodeTable::add(
				array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
					'NAME' => $name,
					'SECOND_NAME' => $secondName,
					'LAST_NAME' => $lastName
				)
			);

			$matches = array('LAST_NAME' => $lastName);
			if($name !== '')
			{
				$matches['NAME'] = $name;
			}
			if($secondName !== '')
			{
				$matches['SECOND_NAME'] = $secondName;
			}

			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::PERSON);

			DuplicateEntityMatchHash::register(
				$entityTypeID,
				$entityID,
				DuplicateIndexType::PERSON,
				self::prepareMatchHash($matches),
				true
			);

			if(isset($matches['SECOND_NAME']))
			{
				unset($matches['SECOND_NAME']);
				DuplicateEntityMatchHash::register(
					$entityTypeID,
					$entityID,
					DuplicateIndexType::PERSON,
					self::prepareMatchHash($matches),
					false
				);
			}

			if(isset($matches['NAME']))
			{
				unset($matches['NAME']);
				DuplicateEntityMatchHash::register(
					$entityTypeID,
					$entityID,
					DuplicateIndexType::PERSON,
					self::prepareMatchHash($matches),
					false
				);
			}
		}
	}
	public static function unregister($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		$dbResult = DuplicatePersonMatchCodeTable::getList(
			array(
				'select' =>array('ID'),
				'order' => array('ID' =>'ASC'),
				'filter' =>  array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				)
			)
		);
		while($fields = $dbResult->fetch())
		{
			DuplicatePersonMatchCodeTable::delete($fields['ID']);
		}

		DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::PERSON);
	}
	public static function getRegisteredEntityMatches($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		$dbResult = DuplicatePersonMatchCodeTable::getList(
			array(
				'select' =>array('ID', 'LAST_NAME', 'NAME', 'SECOND_NAME'),
				'order' => array('ID' =>'ASC'),
				'filter' =>  array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				)
			)
		);

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$matches = array(
				'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '',
				'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
				'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
			);
			$results[self::prepareMatchHash($matches)] = $matches;
		}
		return $results;
	}
	public static function prepareSortParams($entityTypeID, array &$entityIDs)
	{
		if(empty($entityIDs))
		{
			return array();
		}

		$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
		$query->addSelect('ENTITY_ID');
		$query->addSelect('LAST_NAME');
		$query->addSelect('SECOND_NAME');
		$query->addSelect('NAME');

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);

		$result = array();

		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$entityID = intval($fields['ENTITY_ID']);

			$name = isset($fields['NAME']) ? $fields['NAME'] : '';
			$secondName = isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : '';
			$lastName = isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '';

			if($name !== '' && $secondName !== '')
			{
				$slug = "{$lastName} {$name} {$secondName}";
			}
			elseif($name !== '')
			{
				$slug = "{$lastName} {$name}";
			}
			else
			{
				$slug = $lastName;
			}
			$result[$entityID] = array('FULL_NAME' => $slug);
		}
		return $result;
	}
	public static function checkIndex(array $params)
	{
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : \CCrmOwnerType::Undefined;
		if($entityTypeID !== \CCrmOwnerType::Undefined
			&& $entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			throw new Main\NotSupportedException("Entity type: '".\CCrmOwnerType::ResolveName($entityTypeID)."' is not supported in current context");
		}
		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;

		$listParams = array(
			'select' => array('USER_ID', 'TYPE_ID', 'ENTITY_TYPE_ID'),
			'order' => array('USER_ID'=>'ASC', 'TYPE_ID'=>'ASC', 'ENTITY_TYPE_ID'=>'ASC'),
			'filter' => array(
				'=USER_ID' => $userID,
				'=ENTITY_TYPE_ID' => $entityTypeID,
				'=TYPE_ID' => DuplicateIndexType::PERSON
			),
			'limit' => 1
		);

		$dbResult = Entity\DuplicateIndexTable::getList($listParams);
		return is_array($dbResult->fetch());
	}
	/**
	* @return Main\Entity\Query
	*/
	protected static function createQuery()
	{
		return (new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity()));
	}
	protected static function setQueryFilter(Main\Entity\Query $query, array $matches)
	{
		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';
		if($lastName === '')
		{
			throw new Main\ArgumentException("Parameter 'LAST_NAME' is required.", 'matches');
		}
		$query->addFilter('=LAST_NAME', $matches['LAST_NAME']);
		$query->addFilter('=NAME', isset($matches['NAME']) ? $matches['NAME'] : '');
		$query->addFilter('=SECOND_NAME', isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '');
	}
	public function find($entityTypeID = \CCrmOwnerType::Undefined, $limit = 50)
	{
		if($this->lastName === '')
		{
			//Invalid Operation?
			return null;
		}

		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($limit))
		{
			throw new Main\ArgumentTypeException('limit', 'integer');
		}

		$listParams = array(
			'select' =>array('ENTITY_TYPE_ID', 'ENTITY_ID'),
			'order' => array('ENTITY_TYPE_ID' => 'ASC', 'ENTITY_ID' => 'ASC'),
			'filter' => array('LAST_NAME' => $this->lastName)
		);

		if(\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$listParams['filter']['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		if($this->name !== '')
		{
			$listParams['filter']['NAME'] = $this->name;
		}

		if($this->secondName !== '')
		{
			$listParams['filter']['SECOND_NAME'] = $this->secondName;
		}

		if($limit > 0)
		{
			$listParams['limit'] = $limit;
		}

		$dbResult = DuplicatePersonMatchCodeTable::getList($listParams);
		$entities = array();
		while($fields = $dbResult->fetch())
		{
			$entityTypeID = isset($fields['ENTITY_TYPE_ID']) ? intval($fields['ENTITY_TYPE_ID']) : 0;
			$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;

			if(\CCrmOwnerType::IsDefined($entityTypeID) && $entityID > 0)
			{
				$entities[] = new DuplicateEntity($entityTypeID, $entityID);
			}
		}
		return !empty($entities) ? new Duplicate($this, $entities) : null;
	}
	public function equals(DuplicateCriterion $item)
	{
		if(!($item instanceof DuplicatePersonCriterion))
		{
			return false;
		}
		/** @var DuplicatePersonCriterion $item */
		return ($this->name === $item->getName()
			&& $this->secondName === $item->getSecondName()
			&& $this->lastName === $item->getLastName());
	}
	public function getTypeName()
	{
		return 'PERSON';
	}
	public static function createFromMatches(array $matches)
	{
		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';
		$name = isset($matches['NAME']) ? $matches['NAME'] : '';
		$secondName = isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '';

		return new DuplicatePersonCriterion($lastName, $name, $secondName);
	}
	public static function loadEntityMatches($entityTypeID, $entityID)
	{
		$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);

		$query->addSelect('LAST_NAME');
		$query->addSelect('NAME');
		$query->addSelect('SECOND_NAME');

		$query->setLimit(1);
		$dbResult = $query->exec();
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}
	public static function loadEntitiesMatches($entityTypeID, array $entityIDs)
	{
		$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);

		$query->addSelect('ENTITY_ID');
		$query->addSelect('LAST_NAME');
		$query->addSelect('NAME');
		$query->addSelect('SECOND_NAME');

		$dbResult = $query->exec();
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}
			$results[$entityID] = array(
				'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '',
				'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
				'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
			);
		}
		return $results;
	}
	public function getMatches()
	{
		$result = array();
		if($this->lastName !== '')
		{
			$result['LAST_NAME'] = $this->lastName;
		}

		if($this->name !== '')
		{
			$result['NAME'] = $this->name;
		}

		if($this->secondName !== '')
		{
			$result['SECOND_NAME'] = $this->secondName;
		}

		return $result;
	}
	public function getMatchHash()
	{
		$result = array();
		if($this->lastName !== '')
		{
			$result[] = $this->lastName;
		}
		if($this->name !== '')
		{
			$result[] = $this->name;
		}
		if($this->secondName !== '')
		{
			$result[] = $this->secondName;
		}
		return !empty($result) ? md5(implode(';', $result)) : '';
	}
	public static function prepareMatchHash(array $matches)
	{
		$result = array();

		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';
		if($lastName !== '')
		{
			$result[] = $lastName;
		}

		$name = isset($matches['NAME']) ? $matches['NAME'] : '';
		if($name !== '')
		{
			$result[] = $name;
		}

		$secondName = isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '';
		if($secondName !== '')
		{
			$result[] = $secondName;
		}
		return !empty($result) ? md5(implode(';', $result)) : '';
	}
	public function getIndexTypeID()
	{
		return DuplicateIndexType::PERSON;
	}
	public function getMatchDescription()
	{
		return \CUser::FormatName(
			Crm\Format\PersonNameFormatter::LastFirstSecondFormat,
			array(
				'LAST_NAME' => ucfirst($this->lastName),
				'NAME' => ucfirst($this->name),
				'SECOND_NAME' => ucfirst($this->secondName)
			),
			false,
			false
		);
	}
	public function getTextTotals($count, $limit = 0)
	{
		self::includeLangFile();

		if(!is_int($count))
		{
			$count = (int)$count;
		}

		if(!is_int($limit))
		{
			$limit = (int)$limit;
		}

		$exceeded = $limit > 0 && $count > $limit;
		if($exceeded)
		{
			$count = $limit;
		}

		return GetMessage(
			$exceeded ? 'CRM_DUP_CRITERION_PERS_ENTITY_TOTAL_EXCEEDED' : 'CRM_DUP_CRITERION_PERS_ENTITY_TOTAL',
			array(
				'#DESCR#'=> $this->getMatchDescription(),
				'#QTY#'=> Duplicate::entityCountToText($count)
			)
		);
	}
	private static function includeLangFile()
	{
		if(!self::$LANG_INCLUDED)
		{
			self::$LANG_INCLUDED = IncludeModuleLangFile(__FILE__);
		}
	}
	protected function onAfterDuplicateCreated(Duplicate $dup, $entityTypeID, $userID, $enablePermissionCheck, $enableRanking, array &$rankings)
	{
		$name = $this->name;
		$lastName = $this->lastName;
		$secondName = $this->secondName;
		$rootEntityID = $dup->getRootEntityID();

		if($secondName === '' && $name === '')
		{
			return;
		}

		$permissionSql = '';
		if($enablePermissionCheck)
		{
			$permissions = isset($params['PERMISSIONS']) ? $params['PERMISSIONS'] : null;
			if($permissions === null)
			{
				$permissions = \CCrmPerms::GetUserPermissions($userID);
			}

			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::ResolveName($entityTypeID),
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> $permissions)
			);

			if($permissionSql === false)
			{
				//Access denied;
				return;
			}
		}

		if($secondName !== '')
		{
			$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
			$query->addSelect('ENTITY_ID');
			$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);

			$query->addFilter('=LAST_NAME', $lastName);
			$query->addFilter('=NAME', $name);
			$query->addFilter('=SECOND_NAME', '');

			if($rootEntityID)
			{
				$query->addFilter('!ENTITY_ID', $rootEntityID);
				$query->addFilter(
					'!@ENTITY_ID',
					DuplicateIndexMismatch::prepareQueryField(
						self::createFromMatches(array('LAST_NAME' => $lastName, 'NAME' => $name)),
						$entityTypeID,
						$rootEntityID,
						$userID
					)
				);
			}

			if($enablePermissionCheck && $permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}

			$dbResult = $query->exec();
			while($fields = $dbResult->fetch())
			{
				$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
				if($entityID <= 0)
				{
					continue;
				}

				$entity =  new DuplicateEntity($entityTypeID, $entityID);
				$entity->setCriterion(self::createFromMatches(array('LAST_NAME' => $lastName, 'NAME' => $name)));
				if($enableRanking)
				{
					$rankings[] = $entity->getRanking();
				}
				$dup->addEntity($entity);
			}
		}
		if($name !== '')
		{
			$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
			$query->addSelect('ENTITY_ID');
			$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);

			$query->addFilter('=LAST_NAME', $lastName);
			$query->addFilter('=NAME', '');
			$query->addFilter('=SECOND_NAME', '');

			if($rootEntityID)
			{
				$query->addFilter('!ENTITY_ID', $rootEntityID);
				$query->addFilter(
					'!@ENTITY_ID',
					DuplicateIndexMismatch::prepareQueryField(
						self::createFromMatches(array('LAST_NAME' => $lastName)),
						$entityTypeID,
						$rootEntityID,
						$userID
					)
				);
			}

			if($enablePermissionCheck && $permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}

			$dbResult = $query->exec();
			while($fields = $dbResult->fetch())
			{
				$entityID = isset($fields['ENTITY_ID']) ? intval($fields['ENTITY_ID']) : 0;
				if($entityID <= 0)
				{
					continue;
				}

				$entity =  new DuplicateEntity($entityTypeID, $entityID);
				$entity->setCriterion(self::createFromMatches(array('LAST_NAME' => $lastName)));
				if($enableRanking)
				{
					$rankings[] = $entity->getRanking();
				}
				$dup->addEntity($entity);
			}
		}
	}
}