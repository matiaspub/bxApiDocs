<?php


namespace Bitrix\Sale\Compatible\Internals;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Compatible\Internals;
use Bitrix\Sale\Compatible;
use Bitrix\Sale;

abstract class EntityCompatibility
{
	/** @var Compatible\OrderQuery|null */
	protected $query = null;

	/** @var array */
	protected $filter = array();

	/** @var array */
	protected $select = array();

	/** @var array|null */
	protected $group = null;

	/** @var array */
	protected $sort = array();

	/** @var array|null */
	protected $nav = null;

	/** @var null */
	protected $queryAliasList = null;

	/** @var Sale\Internals\Fields|null  */
	protected $fields = null;

	/** @var array */
	protected $rawFields = array();

	const ENTITY_ORDER = 'ORDER';
	const ENTITY_PAYMENT = 'PAYMENT';

	const ENTITY_ORDER_TABLE = 'b_sale_order';
	const ENTITY_PAYMENT_TABLE = 'b_sale_order_payment';
	protected function __construct(array $fields = array())
	{
		throw new SystemException('not set construct');
	}

	/**
	 * @return array
	 */
	protected static function getAliasFields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	protected static function getSelectFields()
	{
		return array();
	}

	/**
	 * @param $sort
	 * @param $filter
	 * @param $group
	 * @param $nav
	 * @param $select
	 * @param $callback
	 * @return Compatible\CDBResult
	 */
	public static function getList($sort = array(), $filter = array(), $group = array(), $nav = array(), $select = array(), $callback = false)
	{
		if (empty($select))
		{
			$select = array('*');
		}

		$compatibility = new static();
		if (!empty($filter))
		{
			$compatibility->setFilter($filter);
		}

		if (!empty($select))
		{
			$compatibility->setSelect($select);
		}


		if (!empty($group))
		{
			$compatibility->setGroup($group);
		}

		if (!empty($sort))
		{
			$compatibility->setSort($sort);
		}

		if (!empty($nav))
		{
			$compatibility->setNav($nav);
		}

		if (!empty($callback))
		{
			$compatibility->setCallback($callback);
		}

		return $compatibility->execute();
	}

	/**
	 * @param array $fields
	 * @throws SystemException
	 */
	public static function add(array $fields)
	{
		throw new SystemException('not set add');
	}

	/**
	 * @param array $filter
	 */
	public function setFilter(array $filter = array())
	{

		$aliasFields = static::getAliasFields();
		foreach($filter as $fieldName => $fieldValue)
		{
			$filterMatch = $this->query->explodeFilterKey($fieldName);
			$fieldClearName = $filterMatch['alias'];

			if (!in_array($fieldClearName, $this->getQueryAliasList()))
			{
				if (isset($aliasFields[$fieldClearName]))
				{
					$this->addQueryAlias($fieldClearName, $aliasFields[$fieldClearName]);
				}
			}

			if ($propKey = $this->parseField($fieldClearName))
			{
				$this->addFilter($filterMatch['modifier'].$filterMatch['operator'].$propKey, $fieldValue);
			}
			else
			{
				if (!$this->checkWhiteListFields($fieldClearName))
					continue;

				if (!is_array($aliasFields[$fieldClearName]))
				{
					$this->addFilter($fieldName, $fieldValue);
				}
				else
				{
					$this->addFilterForAlias($aliasFields[$fieldClearName], $fieldName, $fieldValue);
					$this->addSelectForAlias($aliasFields[$fieldClearName]);
				}
			}
		}
	}

	/**
	 * @param array $select
	 */
	public function setSelect(array $select = array())
	{
		$aliasFields = static::getAliasFields();
		if (empty($select))
		{
			$select = static::getSelectFields();
		}

		foreach($select as $key => $fieldName)
		{
			if ($fieldName == "*")
			{
				unset($select[$key]);

				$select = array_merge($select, static::getSelectFields());

				break;
			}
		}

		foreach($select as $fieldName)
		{
			if (!in_array($fieldName, $this->getQueryAliasList()))
			{
				if (isset($aliasFields[$fieldName]))
				{
					$this->addQueryAlias($fieldName, $aliasFields[$fieldName]);
				}
			}

			if ($propKey = $this->parseField($fieldName))
			{
				$this->addSelect($propKey);
			}
			else
			{
				if (!is_array($aliasFields[$fieldName]))
				{
					$this->addSelect($fieldName);
				}
				else
				{
					$this->addSelectForAlias($aliasFields[$fieldName]);
				}
			}
		}
	}

	/**
	 * @param $key
	 *
	 * @return null|string
	 */
	static public function parseField($key)
	{
		return null;
	}

	/**
	 * @param array $group
	 */
	public function setGroup(array $group = array())
	{
		foreach($group as $fieldName => $fieldValue)
		{
			if ($propKey = $this->parseField($fieldName))
			{
				$this->group[$propKey] = $fieldValue;
			}
			else
			{
				$this->group[$fieldName] = $fieldValue;
			}
		}
	}

	/**
	 * @param array $sort
	 */
	public function setSort(array $sort = array())
	{
		foreach($sort as $fieldName => $fieldValue)
		{
			$fieldName = strtoupper($fieldName);
			if ($propKey = $this->parseField($fieldName))
			{
				$this->sort[$propKey] = $fieldValue;
			}
			else
			{
				$this->sort[$fieldName] = $fieldValue;
			}
		}
	}

	/**
	 * @param array $nav
	 */
	public function setNav(array $nav = array())
	{
		$this->nav = $nav;
	}

	/**
	 * @param array $callback
	 */
	public function setCallback(array $callback)
	{

		if (($sql = call_user_func_array($callback, array())) && strval(trim($sql)) != '')
		{
			$this->query->registerRuntimeField('',
										 new Entity\ExpressionField(
											 '__CALLBACK',
											 '(CASE WHEN ('.$sql.') THEN 1 ELSE 0 END)'
										 )
			);
			$this->query->addFilter('=__CALLBACK', 1);
		}
	}

	/**
	 * @param $name
	 * @param $value
	 * @return bool
	 */
	protected function addFilter($name, $value)
	{
		if (isset($this->filter[$name]))
			return false;

		$this->filter[$name] = $value;

		return true;
	}

	/**
	 * @param array $aliasList
	 * @param $name
	 * @param $value
	 * @return bool
	 */
	protected function addFilterForAlias(array $aliasList, $name, $value)
	{
		$match = $this->query->explodeFilterKey($name);
		$rule = ($match['modifier']? $match['modifier']:"").($match['operator']? $match['operator']:"");

		$logic = array();
		foreach ($aliasList as $fieldName => $fieldValue)
		{
			$filterName = $rule.$fieldName;
			$logic[] = array($filterName => $value);
		}

		if (!empty($logic))
		{
			$logic['LOGIC'] = 'OR';
			$this->query->addFilter(null, $logic);
		}

		return true;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	protected function addSelect($name)
	{
		if (in_array($name, $this->select))
			return false;

		$this->select[] = $name;

		return true;
	}

	/**
	 * @param array $aliasList
	 * @return bool
	 */
	protected function addSelectForAlias(array $aliasList)
	{
		foreach ($aliasList as $fieldName => $fieldValue)
		{
			$this->addSelect($fieldName);
		}

		return true;
	}

	/**
	 * @param $name
	 * @param null $value
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function addQueryAlias($name, $value = null)
	{
		$list = array();

		if ($name == $value)
		{
			$value = null;
		}

		if (!empty($value) && is_array($value))
		{
			$list = $value;
		}
		else
		{
			$list[$name] = $value;
		}

		foreach ($list as $fieldName => $fieldValue)
		{
			if (in_array($fieldName, $this->queryAliasList))
			{
				return false;
			}

			$this->queryAliasList[] = $fieldName;
			$this->query->addAlias($fieldName, $fieldValue);

		}

		return true;
	}

	/**
	 * @return array|null
	 */
	protected function getQueryAliasList()
	{
		if ($this->queryAliasList === null)
		{
			$this->queryAliasList = array_keys($this->query->getAliases());
		}

		return $this->queryAliasList;
	}

	/**
	 * @return Compatible\CDBResult
	 */
	public function execute()
	{
		$this->query->prepare($this->sort, $this->filter, $this->group, $this->select);

		$result = new Compatible\CDBResult();
		return $this->query->compatibleExec($result, $this->nav);
	}


	/**
	 * @param $name
	 * @return null|string
	 */
	public function getField($name)
	{
		return $this->fields->get($name);
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function setField($name, $value)
	{
		$this->fields->set($name, $value);
	}

	/**
	 * @return array
	 */
	public function getFieldValues()
	{
		return $this->fields->getValues();
	}

	/**
	 * @param array $values
	 */
	public function setFields(array $values)
	{
		$this->fields->resetValues($values);
	}


	/**
	 * Remove unnecessary fields
	 *
	 * @param array $fields             An array of fields.
	 * @param array $availableFields    An array of allowed fields.
	 * @return array
	 */
	protected static function clearFields(array $fields, array $availableFields = array())
	{
		if (empty($availableFields))
			$availableFields = static::getAvailableFields();

		foreach ($fields as $fieldName => $fieldValue)
		{
			if (!in_array($fieldName, $availableFields))
			{
				unset($fields[$fieldName]);
			}
		}

		return $fields;
	}


	/**
	 * @internal
	 * Convert an array of dates from a string to an object
	 *
	 * @param array $fields           The array of dates
	 * @param array $dateFields An array describing the fields and types of dates
	 * @return array
	 */
	public static function convertDateFields(array $fields, array $dateFields = array())
	{
		$resultList = array();
		foreach ($fields as $k => $value)
		{
			$resultList[$k] = static::convertDateField($k, $value, $dateFields);
		}
		return $resultList;
	}

	/**
	 * Convert date from string to object
	 *
	 * @param string $name     Field name
	 * @param string $value    Field value
	 * @param array $dateFields
	 *
	 * @return Date|DateTime
	 */
	protected static function convertDateField($name, $value, array $dateFields = array())
	{
		$key = $name;
		if (substr($key,0,1) == '=')
		{
			$key = substr($key, 1);
		}

		if (!array_key_exists($key, $dateFields))
		{
			return $value;
		}

		if (!($value instanceof DateTime)
			&& !($value instanceof Date))
		{
			if (strval($value) == '')
				return false;

			$setValue = null;

			if (ToUpper($value) != "NOW()")
			{
				$setValue = $value;
			}

			if (ToUpper($dateFields[$key]) == "DATE")
			{
				$value = new Date($setValue);
			}
			elseif (ToUpper($dateFields[$key]) == "DATETIME")
			{
				$value = new DateTime($setValue);
			}
		}

		return $value;
	}

	/**
	 * Convert an array of dates from the object to a string
	 *
	 * @param array $fields   The array of dates
	 * @return array
	 */
	public static function convertDateFieldsToOldFormat(array $fields)
	{
		$resultList = array();
		foreach ($fields as $k => $value)
		{
			$valueString = static::convertDateFieldToOldFormat($value);
			$resultList[$k] = $valueString;

			if (($value instanceof DateTime)
				|| ($value instanceof Date))
			{
				$resultList['~'.$k] = $value;
			}

		}

		return $resultList;
	}

	/**
	 * Convert date object to a string
	 *
	 * @param string $value    Field value
	 * @return string
	 */
	protected static function convertDateFieldToOldFormat($value)
	{
		$setValue = $value;

		if (($value instanceof DateTime)
			|| ($value instanceof Date))
		{
			$setValue = $value->toString();
		}

		return $setValue;
	}

	/**
	 * @internal
	 * Convert date object to a string to format
	 *
	 * @param string $value    Field value
	 * @param string $format
	 * @return string
	 */
	public static function convertDateFieldToFormat($value, $format)
	{
		$setValue = $value;

		if (($value instanceof DateTime)
			|| ($value instanceof Date))
		{
			$phpFormat = $value->convertFormatToPhp($format);
			$setValue = $value->format($phpFormat);
			$setValue = str_replace(" 00:00:00", "", $setValue);
		}

		return $setValue;
	}


	/**
	 * @param array $fields
	 * @param array $replace
	 *
	 * @return array
	 */
	protected static function replaceFields(array $fields, array $replace = array())
	{
		if (empty($replace))
			return $fields;

		$replacedFields = $fields;

		foreach ($fields as $name => $value)
		{
			if (array_key_exists($name, $replace))
			{
				$replacedFields[$replace[$name]] = $replacedFields[$name];
				unset($replacedFields[$name]);
			}
		}

		return $replacedFields;
	}


	/**
	 * @return array
	 */
	protected static function getAvailableFields()
	{
		return array();
	}

	/**
	 * @param string $entityName
	 *
	 * @return bool
	 */
	protected static function checkEntityName($entityName)
	{
		return ($entityName == static::ENTITY_ORDER
			|| $entityName == static::ENTITY_PAYMENT
		);
	}

	/**
	 * @param $entityName
	 * @param array $fields
	 * @param array $availableFields
	 *
	 * @return array
	 * @throws ArgumentOutOfRangeException
	 */
	protected function parseRawFields($entityName, array $fields, array $availableFields = array())
	{
		if (!static::checkEntityName($entityName))
		{
			throw new ArgumentOutOfRangeException('entityName');
		}

		if (empty($availableFields))
			$availableFields = static::getAvailableFields();

		foreach ($fields as $name => $value)
		{
			$firstLetter = substr($name, 0, 1);
			if ($firstLetter == "~" || $firstLetter == "=")
			{
				$fieldName = ltrim($name, '=');
				$fieldName = ltrim($fieldName, '~');

				if (!in_array($fieldName, $availableFields))
					continue;

				$this->rawFields[$entityName][$firstLetter.$fieldName] = $value;
				unset($fields[$name]);
			}
		}

		return $fields;
	}


	/**
	 * @param $entity
	 * @param $entityName
	 *
	 * @return Sale\Result
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws ObjectNotFoundException
	 */
	public function saveRawFields($entity, $entityName)
	{
		global $DB;

		if (!static::checkEntityName($entityName))
		{
			throw new ArgumentOutOfRangeException('entityName');
		}

		if (empty($this->rawFields[$entityName]) || !is_array($this->rawFields[$entityName]))
			return new Sale\Result();

		$tableName = null;
		if ($entityName == static::ENTITY_ORDER)
		{
			/** @var Sale\OrderBase $entity */
			if (!$entity instanceof Sale\OrderBase)
			{
				throw new ObjectNotFoundException('Entity "Order" not found');
			}

			$tableName = static::ENTITY_ORDER_TABLE;
		}
		elseif ($entityName == static::ENTITY_PAYMENT)
		{
			/** @var Sale\Payment $entity */
			if (!$entity instanceof Sale\Payment)
			{
				throw new ObjectNotFoundException('Entity "Payment" not found');
			}
			$tableName = static::ENTITY_PAYMENT_TABLE;
		}

		if ($entity->getId() <= 0)
		{
			throw new ArgumentNullException('id');
		}

		if (strval(trim($tableName)) == '')
		{
			throw new ArgumentNullException('tableName');
		}

		$result = new Sale\Result();

		$queryValue = $DB->PrepareUpdate($tableName, $this->rawFields[$entityName]);

		foreach ($this->rawFields[$entityName] as $key => $value)
		{
			if (substr($key, 0, 1) != "=")
				continue;

			if (strval($queryValue) != '')
				$queryValue .= ", ";

			$queryValue .= substr($key, 1)."=".$value." ";
		}

		$sql =
			"UPDATE ".$tableName." SET ".
			"	".$queryValue." WHERE ID = ".$entity->getId()." ";

		if (!($DB->Query($sql, true, "File: ".__FILE__."<br>Line: ".__LINE__)))
		{
			$result->addError(new Sale\ResultError(Loc::getMessage('SALE_COMPATIBLE_'.$entityName.'_RAW_FIELD_UPDATE_ERROR'), 'SALE_'.$entityName.'_COMPATIBLE_RAW_FIELD_UPDATE_ERROR'));
			return $result;
		}

		return $result;
	}

	/**
	 * @param $entityName
	 * @param array $fields
	 * @param string $separator
	 *
	 * @return array
	 * @throws ArgumentOutOfRangeException
	 */
	public static function backRawField($entityName, array $fields, $separator = '=')
	{
		if (!static::checkEntityName($entityName))
		{
			throw new ArgumentOutOfRangeException('entityName');
		}

		if (empty($fields))
			return array();

		foreach($fields as $name => $value)
		{
			$fields[$separator.$name] = $value;
			unset($fields[$name]);
		}

		return $fields;
	}


	protected function getWhiteListFields()
	{
		return array_merge($this->getAvailableFields(), $this->getSelectFields());
	}

	/**
	 * @param $fieldName
	 *
	 * @return bool
	 */
	protected function checkWhiteListFields($fieldName)
	{
		$fields = $this->getWhiteListFields();
		if (!empty($fields))
		{
			if (in_array($fieldName, $fields))
				return true;

			if (strpos($fieldName, 'UF_') === 0)
				return true;
		}

		return false;
	}


}