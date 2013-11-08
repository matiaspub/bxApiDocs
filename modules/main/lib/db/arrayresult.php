<?php
namespace Bitrix\Main\DB;

class ArrayResult extends Result
{
	static public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	protected function convertDataFromDb($value, $fieldType)
	{
		throw new \Bitrix\Main\NotImplementedException("convertDataFromDb is not implemented for arrays");
	}

	public function getSelectedRowsCount()
	{
		return count($this->resource);
	}

	public function getFieldsCount()
	{
		foreach($this->resource as $row)
		{
			return count(array_keys($row));
		}
		return 0;
	}

	public function getFieldName($column)
	{
		foreach($this->resource as $row)
		{
			$keys = array_keys($row);
			return $keys[$column];
		}
		return null;
	}

	static public function getResultFields()
	{
		return null;
	}

	protected function fetchRowInternal()
	{
		$val = current($this->resource);
		next($this->resource);
		return $val;
	}
}
