<?php
namespace Bitrix\Main\DB;

abstract class Result
{
	/** @var Connection */
	protected $connection;
	protected $resource;
	/**@var \Bitrix\Main\Diag\SqlTrackerQuery */
	protected $trackerQuery;

	protected $arReplacedAliases = array();
	protected $arSerializedFields = array();

	/** @var \Closure */
	protected $fetchDataModifier = null;

	/**
	 * @param resource $result Database-specific query result
	 * @param Connection $dbConnection Connection object
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 */
	public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->resource = $result;
		$this->connection = $dbConnection;
		$this->trackerQuery = $trackerQuery;
	}

	public function getResource()
	{
		return $this->resource;
	}

	public function setReplacedAliases(array $arReplacedAliases)
	{
		$this->arReplacedAliases = $arReplacedAliases;
	}

	public function setSerializedFields(array $arSerializedFields)
	{
		$this->arSerializedFields = $arSerializedFields;
	}

	public function setFetchDataModifier($fetchDataModifier)
	{
		$this->fetchDataModifier = $fetchDataModifier;
	}

	/**
	 * Fetches one row of the query result and returns it in the associative array or false on empty data
	 *
	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching
	 * @return array|bool
	 */
	public function fetch(\Bitrix\Main\Text\Converter $converter = null)
	{
		if ($this->trackerQuery != null)
			$this->trackerQuery->restartQuery();

		$dataTmp = $this->fetchRowInternal();

		if ($this->trackerQuery != null)
			$this->trackerQuery->refinishQuery();

		if (!$dataTmp)
			return false;

		$resultFields = $this->getResultFields();

		if($resultFields !== null)
		{
			$data = array();
			foreach ($dataTmp as $key => $value)
				$data[$resultFields[$key]["name"]] = $this->convertDataFromDb($value, $resultFields[$key]["type"]);
		}
		else
		{
			$data = $dataTmp;
		}

		if (!empty($this->arSerializedFields))
		{
			foreach ($this->arSerializedFields as $field)
			{
				if (isset($data[$field]))
					$data[$field] = unserialize($data[$field]);
			}
		}

		if (!empty($this->arReplacedAliases))
		{
			foreach ($this->arReplacedAliases as $tech => $human)
			{
				$data[$human] = $data[$tech];
				unset($data[$tech]);
			}
		}

		if ($this->fetchDataModifier != null)
		{
			$c = $this->fetchDataModifier;
			$data = $c($data);
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
	 * Fetches all the rows of the query result and returns it in the array of associative arrays

	 * @param \Bitrix\Main\Text\Converter $converter Optional converter to encode data on fetching
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

	abstract public function getResultFields();
	abstract public function getSelectedRowsCount();
	abstract public function getFieldsCount();
	abstract public function getFieldName($column);

	abstract protected function fetchRowInternal();
	abstract protected function convertDataFromDb($value, $type);

	/**
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	public function getTrackerQuery()
	{
		return $this->trackerQuery;
	}
}
