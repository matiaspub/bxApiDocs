<?php
namespace Bitrix\Main\DB;

class MysqlResult extends Result
{
	private $resultFields = array();

	static public function __construct($result, Connection $dbConnection, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	protected function convertDataFromDb($value, $fieldType)
	{
		switch ($fieldType)
		{
			case 'timestamp':
			case 'datetime':
				return $value === null ? null : new \Bitrix\Main\Type\DateTime($value, "Y-m-d H:i:s");
				break;
			case 'date':
				return $value === null ? null : new \Bitrix\Main\Type\DateTime($value, "Y-m-d");
				break;
			default:
				break;
		}

		return $value;
	}

	public function getSelectedRowsCount()
	{
		return mysql_num_rows($this->resource);
	}

	public function getFieldsCount()
	{
		return mysql_num_fields($this->resource);
	}

	public function getFieldName($column)
	{
		return mysql_field_name($this->resource, $column);
	}

	protected function getErrorMessage()
	{
		return sprintf("[%s] %s", mysql_errno($this->connection->getResource()), mysql_error($this->connection->getResource()));
	}

	public function getResultFields()
	{
		if (empty($this->resultFields))
		{
			$numFields = mysql_num_fields($this->resource);
			for ($i = 0; $i < $numFields; $i++)
			{
				$this->resultFields[$i] = array(
					"name" => mysql_field_name($this->resource, $i),
					"type" => mysql_field_type($this->resource, $i),
				);
			}
		}
		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return mysql_fetch_row($this->resource);
	}
}
