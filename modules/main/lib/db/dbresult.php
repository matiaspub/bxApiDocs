<?php
namespace Bitrix\Main\DB;

abstract class DbResult
{
	/**
	 * @var DbConnection
	 */
	protected $connection;
	protected $resultResource;
	/**
	 * @var \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	protected $trackerQuery;

	protected $arReplacedAliases = array();
	protected $arSerializedFields = array();

	/**
	 * @var \Closure
	 */
	protected $fetchDataModifier = null;

	public function __construct(DbConnection $dbConnection, $result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connection = $dbConnection;
		$this->resultResource = $result;
		$this->trackerQuery = $trackerQuery;
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

	public function fetch(\Bitrix\Main\Text\Converter $converter = null)
	{
		if ($this->trackerQuery != null)
			$this->trackerQuery->restartQuery();

		$resultFields = $this->getResultFields();

		$dataTmp = $this->fetchRowInternal();

		if ($this->trackerQuery != null)
			$this->trackerQuery->refinishQuery();

		if (!$dataTmp)
			return false;

		$data = array();
		foreach ($dataTmp as $key => $value)
			$data[$resultFields[$key]["name"]] = $this->convertDataFromDb($value, $resultFields[$key]["type"]);

		if (!empty($this->arSerializedFields))
		{
			foreach ($this->arSerializedFields as $field)
			{
				if (array_key_exists($field, $data))
					$data[$field] = unserialize($data[$field]);
			}
		}

		if (!empty($this->arReplacedAliases))
		{
			foreach ($this->arReplacedAliases as $tech => $human)
			{
				if (array_key_exists($tech, $data))
				{
					$data[$human] = $data[$tech];
					unset($data[$tech]);
				}
			}
		}

		if ($this->fetchDataModifier != null)
		{
			$c = $this->fetchDataModifier;
			$data = $c($data);
		}

		if ($converter != null)
		{
			$arKeys = array_keys($data);
			foreach ($arKeys as $key)
				$data[$key] = $converter->encode(
					$data[$key],
					array_key_exists($key."_TYPE", $data) ? $data[$key."_TYPE"] : \Bitrix\Main\Text\Converter::TEXT
				);
		}

		return $data;
	}

	public function fetchAll(\Bitrix\Main\Text\Converter $converter = null)
	{
		$res = array();
		while ($ar = $this->fetch($converter))
			$res[] = $ar;
		return $ar;
	}

	abstract public function getResultFields();
	abstract public function getSelectedRowsCount();
	abstract public function getFieldsCount();
	abstract public function getFieldName($column);

	abstract protected function fetchRowInternal();
	abstract protected function convertDataFromDb($value, $type);
}
