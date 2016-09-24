<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm;
class DuplicateOrganizationCriterion extends DuplicateCriterion
{
	private static $LANG_INCLUDED = false;
	protected $title = '';
	protected $useStrictComparison = false;
	protected static $TYPE_RX = null;

	public function __construct($title)
	{
		$this->setTitle($title);
	}
	public static function getTypeRegexPattern()
	{
		if(self::$TYPE_RX !== null)
		{
			return self::$TYPE_RX;
		}

		self::$TYPE_RX = array();
		$dbBizType = Crm\BusinessTypeTable::getList(
			array(
				'select' =>array('NAME'),
				'order' => array('CODE' =>'DESC')
			)
		);

		$isUtf = defined('BX_UTF');
		$lBorder = $isUtf ? "(?<!\pL)" : "\b";
		$rBorder = $isUtf ? "(?!\pL)" : "\b";

		while($bizType = $dbBizType->fetch())
		{
			$name = isset($bizType['NAME']) ? $bizType['NAME'] : '';
			$parts = explode('.', $name);
			$qty = count($parts);

			if($qty === 1)
			{
				$pattern = "{$lBorder}{$name}{$rBorder}";
			}
			else
			{
				$ary = array();
				for($i = 0; $i < $qty; $i++)
				{
					$part = $parts[$i];
					if($part !== '')
					{
						$part = preg_replace('/\s+/', '\s+', $part);
					}

					if($i === 0)
					{
						$ary[] = "{$lBorder}{$part}";
					}
					elseif($i < ($qty - 1))
					{
						$ary[] = "(\s*\.{0,1}\s*){$part}";
					}
					else
					{
						if($part !== '')
						{
							$ary[] = "(\s*\.{0,1}\s*){$part}{$rBorder}";
						}
						else
						{
							$ary[] = "(\.|{$rBorder})";
						}
					}
				}
				$pattern = implode('', $ary);
			}

			if($pattern !== '')
			{
				self::$TYPE_RX[] = "/{$pattern}/i".BX_UTF_PCRE_MODIFIER;
			}
		}

		if($isUtf)
		{
			//\u00AB « left-pointing double angle quotation mark
			//\u00BB » right-pointing double angle quotation mark
			//\u201E „ double low-9 quotation mark
			//\u201F ? double high-reversed-9 quotation mark
			//\u2018 ‘ left single quotation mark
			//\u2019 ’ right single quotation mark
			//\u201C “ left double quotation mark
			//\u201D ” right double quotation mark
			self::$TYPE_RX[] = '/[\x{00AB}\x{00BB}\x{2018}\x{2019}\x{201C}\x{201D}\x{201E}\x{201F}]/u';
		}
		else
		{
			//AB « left-pointing double angle quotation mark
			//BB » right-pointing double angle quotation mark
			//84 „ double low-9 quotation mark
			//91 ‘ left single quotation mark
			//92 ’ right single quotation mark
			//93 “ left double quotation mark
			//94 ” right double quotation mark
			self::$TYPE_RX[] = '/[\xAB\xBB\x84\x91\x92\x93\x94]/';
		}

		self::$TYPE_RX[] = '/[\"\'\-\,\.\;\:\s]/i'.BX_UTF_PCRE_MODIFIER;
		return self::$TYPE_RX;
	}
	public function getTitle()
	{
		return $this->title;
	}
	public function setTitle($title, $isRaw = true)
	{
		if(!is_string($title))
		{
			throw new Main\ArgumentTypeException('title', 'string');
		}
		$this->title = $isRaw ? self::prepareCode($title) : $title;
	}
	public function isStrictComparison()
	{
		return $this->useStrictComparison;
	}
	public function setStrictComparison($useStrictComparison)
	{
		if(!is_bool($useStrictComparison))
		{
			throw new Main\ArgumentTypeException('useStrictComparison', 'boolean');
		}

		$this->useStrictComparison = $useStrictComparison;
	}
	/*public function prepareFilter(Crm\Mapper $mapper, DuplicateSearchParams $params)
	{
		$filter = array();
		if($this->title !== '')
		{
			//$filter[] = array('PREFIX' => '%', 'FIELD_NAME' => 'TITLE', 'VALUE' => $this->title);
			$filter['%'.$mapper->getMapping('TITLE')] = $this->title;
		}
		return $filter;
	}*/
	public static function prepareCode($title)
	{
		if(!is_string($title))
		{
			throw new Main\ArgumentTypeException('title', 'string');
		}

		if($title === '')
		{
			return '';
		}

		if(strpos($title, '&') >= 0)
		{
			$title = preg_replace('/\&/', 'and', $title);
		}

		return strtolower(trim(preg_replace(self::getTypeRegexPattern(), '', $title)));
	}
	public static function register($entityTypeID, $entityID, $title, $isRaw = true)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!is_int($entityID))
		{
			throw new Main\ArgumentTypeException('entityID', 'integer');
		}

		if(!is_string($title))
		{
			throw new Main\ArgumentTypeException('title', 'string');
		}

		self::unregister($entityTypeID, $entityID);

		if($isRaw)
		{
			$title = self::prepareCode($title);
		}

		if($title === '')
		{
			return;
		}

		DuplicateOrganizationMatchCodeTable::add(
			array(
				'ENTITY_TYPE_ID' => $entityTypeID,
				'ENTITY_ID' => $entityID,
				'TITLE' => $title
			)
		);

		$matches = array('TITLE' => $title);
		DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::ORGANIZATION);
		DuplicateEntityMatchHash::register(
			$entityTypeID,
			$entityID,
			DuplicateIndexType::ORGANIZATION,
			self::prepareMatchHash($matches),
			true
		);
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

		$dbResult = DuplicateOrganizationMatchCodeTable::getList(
			array(
				'select' =>array('ID'),
				'order' => array('ID' =>'ASC'),
				'filter' => array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				)
			)
		);
		while($fields = $dbResult->fetch())
		{
			DuplicateOrganizationMatchCodeTable::delete($fields['ID']);
		}

		DuplicateEntityMatchHash::unregisterEntity($entityTypeID, $entityID, DuplicateIndexType::ORGANIZATION);
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

		$dbResult = DuplicateOrganizationMatchCodeTable::getList(
			array(
				'select' =>array('ID', 'TITLE'),
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
			$matches = array('TITLE' => isset($fields['TITLE']) ? $fields['TITLE'] : '');
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

		$query = new Main\Entity\Query(DuplicateOrganizationMatchCodeTable::getEntity());
		$query->addSelect('ENTITY_ID');
		$query->addSelect('TITLE');

		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);

		$result = array();

		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$result[$fields['ENTITY_ID']] = array('TITLE' => isset($fields['TITLE']) ? $fields['TITLE'] : '');
		}
		return $result;
	}
	public static function checkIndex(array $params)
	{
		$entityTypeID = isset($params['ENTITY_TYPE_ID']) ? intval($params['ENTITY_TYPE_ID']) : \CCrmOwnerType::Undefined;
		if($entityTypeID !== \CCrmOwnerType::Undefined
			&& $entityTypeID !== \CCrmOwnerType::Lead
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
				'=TYPE_ID' => DuplicateIndexType::ORGANIZATION
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
		return (new Main\Entity\Query(DuplicateOrganizationMatchCodeTable::getEntity()));
	}
	protected static function setQueryFilter(Main\Entity\Query $query, array $matches)
	{
		$title = isset($matches['TITLE']) ? $matches['TITLE'] : '';
		if($title === '')
		{
			throw new Main\ArgumentException("Parameter 'TITLE' is required.", 'matches');
		}
		$query->addFilter('=TITLE', $title);
	}
	public function find($entityTypeID = \CCrmOwnerType::Undefined, $limit = 50)
	{
		if($this->title === '')
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

		$filter = array();
		if($this->useStrictComparison)
		{
			$filter['TITLE'] = $this->title;
		}
		else
		{
			$filter['%TITLE'] = new \CSQLWhereExpression('?s', strtoupper($this->title).'%');
		}

		if(\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$filter['ENTITY_TYPE_ID'] = $entityTypeID;
		}

		$dbResult = DuplicateOrganizationMatchCodeTable::getList(
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
		if(!($item instanceof DuplicateOrganizationCriterion))
		{
			return false;
		}
		/** @var DuplicateOrganizationCriterion $item */
		return ($this->title === $item->getTitle());
	}
	public function getTypeName()
	{
		return 'ORGANIZATION';
	}
	public function getMatches()
	{
		return array(
			'TITLE' => $this->title
		);
	}
	public static function createFromMatches(array $matches)
	{
		$title = isset($matches['TITLE']) ? $matches['TITLE'] : '';
		return new DuplicateOrganizationCriterion($title);
	}
	public static function loadEntityMatches($entityTypeID, $entityID)
	{
		$query = new Main\Entity\Query(DuplicateOrganizationMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('=ENTITY_ID', $entityID);

		$query->addSelect('TITLE');

		$query->setLimit(1);
		$dbResult = $query->exec();
		$fields = $dbResult->fetch();
		return is_array($fields) ? $fields : null;
	}
	public static function loadEntitiesMatches($entityTypeID, array $entityIDs)
	{
		$query = new Main\Entity\Query(DuplicateOrganizationMatchCodeTable::getEntity());
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);
		$query->addFilter('@ENTITY_ID', $entityIDs);

		$query->addSelect('ENTITY_ID');
		$query->addSelect('TITLE');

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
				'TITLE' => isset($fields['TITLE']) ? $fields['TITLE'] : '',
			);
		}
		return $results;
	}
	public function getMatchHash()
	{
		return $this->title !== '' ? md5($this->title) : '';
	}
	public static function prepareMatchHash(array $matches)
	{
		$title = isset($matches['TITLE']) ? $matches['TITLE'] : '';
		return $title !== '' ? md5($title) : '';
	}
	public function getIndexTypeID()
	{
		return DuplicateIndexType::ORGANIZATION;
	}
	public function getMatchDescription()
	{
		return ucfirst($this->title);
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
			$exceeded ? 'CRM_DUP_CRITERION_ORG_ENTITY_TOTAL_EXCEEDED' : 'CRM_DUP_CRITERION_ORG_ENTITY_TOTAL',
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
}