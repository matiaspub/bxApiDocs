<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm;
class DuplicateCommunicationCriterion extends DuplicateCriterion
{
	private static $LANG_INCLUDED = false;
	protected $entityTypeID = 0;
	protected $communicationType = '';
	protected $value = '';

	public function __construct($communicationType, $value)
	{
		$this->setCommunicationType($communicationType);
		$this->setValue($value);
	}
	public function getCommunicationType()
	{
		return $this->communicationType;
	}
	public function setCommunicationType($communicationType)
	{
		if(!is_string($communicationType))
		{
			throw new Main\ArgumentTypeException('communicationType', 'string');
		}
		$this->communicationType = $communicationType;
	}
	public function getValue()
	{
		return $this->value;
	}
	public function setValue($value)
	{
		if(!is_string($value))
		{
			throw new Main\ArgumentTypeException('value', 'string');
		}
		$this->value = $value;
	}
	/*public function prepareFilter(Crm\Mapper $mapper, DuplicateSearchParams $params)
	{
		$filter = array();
		if($this->value !== '')
		{
			$filter['=VALUE'] = $this->value;
			$filter['=TYPE_ID'] = $this->type;
			if($this->entityTypeID !== \CCrmOwnerType::Undefined)
			{
				$filter['=ENTITY_ID'] = \CCrmOwnerType::ResolveName($this->entityTypeID);
			}
		}
		return $filter;
	}*/
	public static function extractMultifieldsValues(array $multifiels, $type)
	{
		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		$result = array();
		if(isset($multifiels[$type]) && is_array($multifiels[$type]))
		{
			foreach($multifiels[$type] as &$data)
			{
				if(isset($data['VALUE']) && $data['VALUE'] !== '')
				{
					$result[] = $data['VALUE'];
				}
			}
			unset($data);
		}
		return $result;
	}
	public static function normalizePhone($value)
	{
		if(!is_string($value) || $value === '')
		{
			return '';
		}

		$result = \NormalizePhone($value, 1);
		if(is_string($result) && $result !== '')
		{
			return $result;
		}

		// Is not valid phone - just clear value
		$result = preg_replace("/[^0-9\#\*]/i", "", $value);
		return is_string($result) ? $result : '';
	}
	public static function prepareCodes($communicationType, array $values)
	{
		if(!is_string($communicationType))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		$result = array();
		if($communicationType === 'PHONE')
		{
			foreach($values as $value)
			{
				$value = self::normalizePhone($value);
				if(is_string($value) && $value !== '')
				{
					$result[] = $value;
				}
			}
		}
		else
		{
			foreach($values as $value)
			{
				if(is_string($value) && $value !== '')
				{
					$result[] = strtolower($value);
				}
			}
		}
		return $result;
	}
	public static function prepareCode($communicationType, $value)
	{
		$result = self::prepareCodes($communicationType, array($value));
		return !empty($result) ? $result[0] : $value;
	}
	public static function register($entityTypeID, $entityID, $type, array $values, $isRaw = true)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		if($isRaw)
		{
			$values = self::prepareCodes($type, $values);
		}

		DuplicateCommunicationMatchCodeTable::replaceValues($entityTypeID, $entityID, $type, $values);

		$typeID = DuplicateIndexType::UNDEFINED;
		if($type === 'PHONE')
		{
			$typeID = DuplicateIndexType::COMMUNICATION_PHONE;
		}
		elseif($type === 'EMAIL')
		{
			$typeID = DuplicateIndexType::COMMUNICATION_EMAIL;
		}

		if($typeID !== DuplicateIndexType::UNDEFINED)
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, $typeID);
			foreach($values as $value)
			{
				$matches = array('TYPE' => $type, 'VALUE' => $value);
				DuplicateEntityMatchHash::register(
					$entityTypeID,
					$entityID,
					$typeID,
					self::prepareMatchHash($matches),
					true
				);
			}
		}
	}
	public static function unregister($entityTypeID, $entityID, $type = '')
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		$filter = array(
			'ENTITY_TYPE_ID' => $entityTypeID,
			'ENTITY_ID' => $entityID
		);

		if($type !== '')
		{
			$filter['TYPE'] = $type;
		}

		$dbResult = DuplicateCommunicationMatchCodeTable::getList(
			array(
				'select' =>array('ID'),
				'order' => array('ID' =>'ASC'),
				'filter' => $filter
			)
		);
		while($fields = $dbResult->fetch())
		{
			DuplicateCommunicationMatchCodeTable::delete($fields['ID']);
		}

		if($type === 'PHONE')
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::COMMUNICATION_PHONE);
		}
		elseif($type === 'EMAIL')
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::COMMUNICATION_EMAIL);
		}
		elseif($type === '')
		{
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::COMMUNICATION_PHONE);
			DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::COMMUNICATION_EMAIL);
		}
	}
	public static function getRegisteredEntityMatches($entityTypeID, $entityID, $type = '')
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		$params = array(
			'select' =>array('ID', 'TYPE', 'VALUE'),
			'order' => array('ID' =>'ASC'),
			'filter' =>  array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID
			)
		);

		if($type !== '')
		{
			$params['filter']['TYPE'] = $type;
		}

		$dbResult = DuplicateCommunicationMatchCodeTable::getList($params);
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$matches = array(
				'TYPE' => isset($fields['TYPE']) ? $fields['TYPE'] : '',
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : ''
			);
			$results[self::prepareMatchHash($matches)] = $matches;
		}
		return $results;
	}
	public static function prepareSortParams($entityTypeID, array &$entityIDs, $type = '')
	{
		if(empty($entityIDs))
		{
			return array();
		}

		if(!is_string($type))
		{
			$type = '';
		}

		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());

		$query->addSelect('ENTITY_ID');
		$query->addSelect('TYPE');
		$query->addSelect('VALUE');

		$subQuery = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$subQuery->registerRuntimeField('', new Main\Entity\ExpressionField('MIN_ID', 'MIN(ID)'));
		$subQuery->addSelect('MIN_ID');

		$subQuery->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$subQuery->addFilter('@ENTITY_ID', $entityIDs);

		if($type !== '')
		{
			$subQuery->addFilter('=TYPE', $type);
		}

		$subQuery->addGroup('ENTITY_ID');
		$subQuery->addGroup('TYPE');

		$query->registerRuntimeField('',
			new Main\Entity\ReferenceField('M',
				Main\Entity\Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.MIN_ID'),
				array('join_type' => 'INNER')
			)
		);

		$result = array();

		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$entityID = intval($fields['ENTITY_ID']);
			if(!isset($result[$entityID]))
			{
				$result[$entityID] = array();
			}

			$type = isset($fields['TYPE']) ? $fields['TYPE'] : '';
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			$result[$entityID][$type] = $value;
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

		$typeID = isset($params['TYPE_ID']) ? intval($params['TYPE_ID']) : DuplicateIndexType::UNDEFINED;
		if($typeID !== DuplicateIndexType::COMMUNICATION_PHONE
			&& $typeID !== DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}

		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;

		$listParams = array(
			'select' => array('USER_ID', 'TYPE_ID', 'ENTITY_TYPE_ID'),
			'order' => array('USER_ID'=>'ASC', 'TYPE_ID'=>'ASC', 'ENTITY_TYPE_ID'=>'ASC'),
			'filter' => array(
				'=USER_ID' => $userID,
				'=ENTITY_TYPE_ID' => $entityTypeID,
				'=TYPE_ID' => $typeID
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
		return (new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity()));
	}
	protected static function setQueryFilter(Main\Entity\Query $query, array $matches)
	{
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
	}
	public static function getRegisteredTypes($entityTypeID, $entityID)
	{
		$dbResult = DuplicateCommunicationMatchCodeTable::getList(
			array(
				'select' => array('TYPE'),
				'filter' => array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				),
				'group' => array('TYPE'),
				'limit' => 2
			)
		);

		$result = array();
		if(is_object($dbResult))
		{
			while($fields = $dbResult->fetch())
			{
				if(isset($fields['TYPE']))
				{
					$result[] = $fields['TYPE'];
				}
			}
		}
		return $result;
	}
	/*
	 *  @return Duplicate;
	 */
	public function find($entityTypeID = \CCrmOwnerType::Undefined, $limit = 50)
	{
		if($this->communicationType === '')
		{
			//Invalid Operation?
			return null;
		}

		if($this->value === '')
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

		if($limit <= 0)
		{
			$limit = 50;
		}

		$filter = array(
			'TYPE' => $this->communicationType,
			'VALUE' => self::prepareCode($this->communicationType, $this->value)
		);

		if(\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$filter['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		$dbResult = DuplicateCommunicationMatchCodeTable::getList(
			array(
				'select' =>array('ENTITY_TYPE_ID', 'ENTITY_ID'),
				'order' => array('ENTITY_TYPE_ID' => 'ASC', 'ENTITY_ID' => 'ASC'),
				'filter' => $filter,
				'limit' => $limit
			)
		);
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
		if(!($item instanceof DuplicateCommunicationCriterion))
		{
			return false;
		}
		/** @var DuplicateCommunicationCriterion $item */
		if($this->communicationType !== $item->getCommunicationType())
		{
			return false;
		}

		if($this->communicationType === 'PHONE')
		{
			return self::normalizePhone($this->value) === self::normalizePhone($item->getValue());
		}

		return $this->value === $item->getValue();
	}
	public function getIndexTypeID()
	{
		if($this->communicationType === 'PHONE')
		{
			return DuplicateIndexType::COMMUNICATION_PHONE;
		}
		elseif($this->communicationType === 'EMAIL')
		{
			return DuplicateIndexType::COMMUNICATION_EMAIL;
		}
		else
		{
			return DuplicateIndexType::UNDEFINED;
		}
	}
	public function getTypeName()
	{
		return 'COMMUNICATION';
	}
	public function getMatches()
	{
		return array(
			'TYPE' => $this->communicationType,
			'VALUE' => $this->value
		);
	}
	public static function createFromMatches(array $matches)
	{
		$type = isset($matches['TYPE']) ? $matches['TYPE'] : '';
		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		return new DuplicateCommunicationCriterion($type, $value);
	}
	public static function loadEntityMatches($entityTypeID, $entityID, $communicationType)
	{
		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);
		$query->addFilter('=TYPE', $communicationType);

		$query->addSelect('VALUE');
		$query->addSelect('TYPE');

		$dbResult = $query->exec();
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$results[] = array(
				'TYPE' => $communicationType,
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : '',
			);
		}
		return $results;
	}
	public static function loadEntitiesMatches($entityTypeID, array $entityIDs, $communicationType)
	{
		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);
		$query->addFilter('=TYPE', $communicationType);

		$query->addSelect('ENTITY_ID');
		$query->addSelect('VALUE');

		$dbResult = $query->exec();
		$results = array();
		while($fields = $dbResult->fetch())
		{
			$entityID = isset($fields['ENTITY_ID']) ? (int)$fields['ENTITY_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			if(!isset($results[$entityID]))
			{
				$results[$entityID] = array();
			}

			$results[$entityID][] = array(
				'TYPE' => $communicationType,
				'VALUE' => isset($fields['VALUE']) ? $fields['VALUE'] : '',
			);
		}
		return $results;
	}
	public function getMatchHash()
	{
		return $this->value !== '' ? md5("{$this->communicationType};{$this->value}") : '';
	}
	public static function prepareMatchHash(array $matches)
	{
		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		$type = isset($matches['TYPE']) ? $matches['TYPE'] : '';
		return $value !== '' ? md5("{$type};{$value}") : '';
	}
	public function getMatchDescription()
	{
		return $this->value;
	}
	public static function getRegisteredCodes($entityTypeID, $entityID, $enablePermissionCheck = false, $userID = 0, $limit = 50)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_int($userID))
		{
			throw new Main\ArgumentTypeException('userID', 'integer');
		}

		if(!is_bool($enablePermissionCheck))
		{
			throw new Main\ArgumentTypeException('enablePermissionCheck', 'boolean');
		}

		if(!is_int($limit))
		{
			throw new Main\ArgumentTypeException('limit', 'integer');
		}

		$query = new Main\Entity\Query(DuplicateCommunicationMatchCodeTable::getEntity());
		$query->addSelect('TYPE');
		$query->addSelect('VALUE');

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);

		if($enablePermissionCheck && $userID > 0)
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
				return array();
			}
			elseif($permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();

		$results = array();
		while($fields = $dbResult->fetch())
		{
			$type = isset($fields['TYPE']) ? $fields['TYPE'] : '';
			$value = isset($fields['VALUE']) ? $fields['VALUE'] : '';
			if(!isset($results[$type]))
			{
				$results[$type] = array();
			}
			$results[$type][] = $value;
		}
		return $results;
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

		/*
		 * CRM_DUP_CRITERION_COMM_PHONE_ENTITY_TOTAL
		 * CRM_DUP_CRITERION_COMM_PHONE_ENTITY_TOTAL_EXCEEDED
		 * CRM_DUP_CRITERION_COMM_EMAIL_ENTITY_TOTAL
		 * CRM_DUP_CRITERION_COMM_EMAIL_ENTITY_TOTAL_EXCEEDED
		 */
		return GetMessage(
			($exceeded
				? "CRM_DUP_CRITERION_COMM_{$this->communicationType}_ENTITY_TOTAL_EXCEEDED"
				: "CRM_DUP_CRITERION_COMM_{$this->communicationType}_ENTITY_TOTAL"),
			array(
				'#DESCR#'=> $this->getMatchDescription(),
				'#QTY#'=> Duplicate::entityCountToText($count)
			)
		);
	}
	public static function resolveTypeByIndexTypeID($indexTypeID)
	{
		if($indexTypeID === DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			return 'EMAIL';
		}
		elseif($indexTypeID === DuplicateIndexType::COMMUNICATION_PHONE)
		{
			return 'PHONE';
		}
		return '';
	}
	private static function includeLangFile()
	{
		if(!self::$LANG_INCLUDED)
		{
			self::$LANG_INCLUDED = IncludeModuleLangFile(__FILE__);
		}
	}
}