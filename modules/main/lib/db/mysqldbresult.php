<?php
namespace Bitrix\Main\DB;

class MysqlDbResult
	extends DbResult
{
	private $resultFields = array();

	static public function __construct(DbConnection $dbConnection, $result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($dbConnection, $result, $trackerQuery);
	}

	protected function convertDataFromDb($value, $fieldType)
	{
		switch ($fieldType)
		{
			case 'timestamp':
			case 'datetime':
				return new \Bitrix\Main\Type\DateTime($value, "Y-m-d H:i:s");
				break;
			case 'date':
				return new \Bitrix\Main\Type\DateTime($value, "Y-m-d");
				break;
			default:
				break;
		}

		return $value;
	}

	static public function getSelectedRowsCount()
	{
		return mysql_num_rows($this->resultResource);
	}

	static public function getFieldsCount()
	{
		return mysql_num_fields($this->resultResource);
	}

	static public function getFieldName($column)
	{
		return mysql_field_name($this->resultResource, $column);
	}

	protected function getErrorMessage()
	{
		return sprintf("[%s] %s", mysql_errno($this->connection->getResource()), mysql_error($this->connection->getResource()));
	}

	static public function getResultFields()
	{
		if (empty($this->resultFields))
		{
			$numFields = mysql_num_fields($this->resultResource);
			for ($i = 0; $i < $numFields; $i++)
				$this->resultFields[$i] = array(
					"name" => mysql_field_name($this->resultResource, $i),
					"type" => mysql_field_type($this->resultResource, $i),
				);
		}
		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return mysql_fetch_row($this->resultResource);
	}
}
