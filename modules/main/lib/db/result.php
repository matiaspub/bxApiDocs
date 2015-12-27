<?php
namespace Bitrix\Main\DB;

/**
 * Class Result is the abstract base class for representing
 * database query result.
 * <p>
 * It has ability to transform raw data populated from
 * the database into useful associative arrays with
 * some fields unserialized and some presented as Datetime
 * objects or other changes.
 * <p>
 * It also supports query debugging by providing {@link \Bitrix\Main\Diag\SqlTracker}
 * with timing information.
 *
 * @package Bitrix\Main\DB
 */
abstract class Result
{
	/** @var \Bitrix\Main\DB\Connection */
	protected $connection;
	/** @var resource */
	protected $resource;
	/** @var \Bitrix\Main\Diag\SqlTrackerQuery */
	protected $trackerQuery = null;

	/** @var callable[] */
	protected $converters = array();
	/** @var string[] */
	protected $serializedFields = array();
	/** @var string[] */
	protected $replacedAliases = array();
	/** @var callable[] */
	protected $fetchDataModifiers = array();

	/** @var int */
	protected $count;

	/**
	 * @param resource $result Database-specific query result.
	 * @param Connection $dbConnection Connection object.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Helps to collect debug information.
	 */
	public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->resource = $result;
		$this->connection = $dbConnection;
		$this->trackerQuery = $trackerQuery;
		$resultFields = $this->getFields();
		if ($resultFields && $this->connection)
		{
			$helper = $this->connection->getSqlHelper();
			foreach ($resultFields as $key => $type)
			{
				$converter = $helper->getConverter($resultFields[$key]);
				if (is_callable($converter))
				{
					$this->converters[$key] = $converter;
				}
			}
		}
	}

	/**
	 * Returns database-specific resource of this result.
	 *
	 * @return null|resource
	 */
	public function getResource()
	{
		return $this->resource;
	}

	/**
	 * Sets list of aliased columns.
	 * This allows to overcome database limits on length of the column names.
	 *
	 * @param array[string]string $replacedAliases Aliases map from tech to human.
	 *
	 * @return void
	 * @see \Bitrix\Main\Db\Result::addReplacedAliases
	 */
	public function setReplacedAliases(array $replacedAliases)
	{
		$this->replacedAliases = $replacedAliases;
	}

	/**
	 * Extends list of aliased columns.
	 *
	 * @param array[string]string $replacedAliases Aliases map from tech to human.
	 *
	 * @return void
	 * @see \Bitrix\Main\Db\Result::setReplacedAliases
	 */
	public function addReplacedAliases(array $replacedAliases)
	{
		$this->replacedAliases = array_merge($this->replacedAliases, $replacedAliases);
	}

	/**
	 * Sets internal list of fields which will be unserialized on fetch.
	 *
	 * @param array $serializedFields List of fields.
	 *
	 * @return void
	 */
	public function setSerializedFields(array $serializedFields)
	{
		$this->serializedFields = $serializedFields;
	}

	/**
	 * Modifier should accept once fetched array as an argument, then modify by link or return new array:
	 * - function (&$data) { $data['AGE'] -= 7; }
	 * - function ($data) { $data['AGE'] -= 7; return $data; }
	 *
	 * @param callable $fetchDataModifier Valid callback.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function addFetchDataModifier($fetchDataModifier)
	{
		if (!is_callable($fetchDataModifier))
		{
			throw new \Bitrix\Main\ArgumentException('Data Modifier should be a callback');
		}

		$this->fetchDataModifiers[] = $fetchDataModifier;
	}

	/**
	 * Fetches one row of the query result and returns it in the associative array of raw DB data or false on empty data.
	 *
	 * @return array|false
	 */
	public function fetchRaw()
	{
		if ($this->trackerQuery != null)
		{
			$this->trackerQuery->restartQuery();
		}

		$data = $this->fetchRowInternal();

		if ($this->trackerQuery != null)
		{
			$this->trackerQuery->refinishQuery();
		}

		if (!$data)
		{
			return false;
		}

		return $data;
	}

	/**
	 * Fetches one row of the query result and returns it in the associative array of converted data or false on empty data.
	 *
	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching.
	 *
	 * @return array|false
	 */
	public function fetch(\Bitrix\Main\Text\Converter $converter = null)
	{
		$data = $this->fetchRaw();

		if (!$data)
		{
			return false;
		}

		if ($this->converters)
		{
			foreach ($this->converters as $field => $convertDataModifier)
			{
				$data[$field] = call_user_func_array($convertDataModifier, array($data[$field]));
			}
		}

		if ($this->serializedFields)
		{
			foreach ($this->serializedFields as $field)
			{
				if (isset($data[$field]))
					$data[$field] = unserialize($data[$field]);
			}
		}

		if ($this->replacedAliases)
		{
			foreach ($this->replacedAliases as $tech => $human)
			{
				$data[$human] = $data[$tech];
				unset($data[$tech]);
			}
		}

		if ($this->fetchDataModifiers)
		{
			foreach ($this->fetchDataModifiers as $fetchDataModifier)
			{
				$result = call_user_func_array($fetchDataModifier, array(&$data));

				if (is_array($result))
				{
					$data = $result;
				}
			}
		}

		if ($converter != null)
		{
			foreach ($data as $key => $val)
			{
				$data[$key] = $converter->encode(
					$val,
					(isset($data[$key."_TYPE"])? $data[$key."_TYPE"] : \Bitrix\Main\Text\Converter::TEXT)
				);
			}
		}

		return $data;
	}

	/**
	 * Fetches all the rows of the query result and returns it in the array of associative arrays.
	 * Returns an empty array if query has no data.
	 *
	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching.
	 *
	 * @return array
	 */
	public function fetchAll(\Bitrix\Main\Text\Converter $converter = null)
	{
		$res = array();
		while ($ar = $this->fetch($converter))
		{
			$res[] = $ar;
		}
		return $res;
	}

	/**
	 * Returns an array of fields according to columns in the result.
	 *
	 * @return @return \Bitrix\Main\Entity\ScalarField[]
	 */
	abstract public function getFields();

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return int
	 */
	abstract public function getSelectedRowsCount();

	/**
	 * Returns next result row or false.
	 *
	 * @return array|false
	 */
	abstract protected function fetchRowInternal();

	/**
	 * Returns current query tracker.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery|null
	 */
	public function getTrackerQuery()
	{
		return $this->trackerQuery;
	}

	/**
	 * Sets record count.
	 * @param int $n
	 */
	public function setCount($n)
	{
		$this->count = (int)$n;
	}

	/**
	 * Returns record count. It's required to set record count explicitly before.
	 * @return int
	 * @throws \Bitrix\Main\ObjectPropertyException
	 */
	public function getCount()
	{
		if($this->count !== null)
		{
			return $this->count;
		}
		throw new \Bitrix\Main\ObjectPropertyException("count");
	}
}
