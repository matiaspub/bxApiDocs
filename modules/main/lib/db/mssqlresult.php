<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Diag;
use Bitrix\Main\Type;

class MssqlResult extends Result
{
	private $resultFields = array();

	static public function __construct($result, Connection $dbConnection = null, Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	public function getResultFields()
	{
		if (empty($this->resultFields))
		{
			$fields = sqlsrv_field_metadata($this->resource);
			if ($fields)
			{
				foreach ($fields as $key => $value)
				{
					$this->resultFields[$key] = array(
						"name" => $value["Name"],
						"type" => $value["Type"],
					);
				}
			}
		}

		return $this->resultFields;
	}

	public function getSelectedRowsCount()
	{
		return sqlsrv_num_rows($this->resource);
	}

	public function getFieldsCount()
	{
		return count($this->getResultFields());
	}

	public function getFieldName($column)
	{
		$fields = $this->getResultFields();
		return $fields[$column]["name"];
	}

	protected function fetchRowInternal()
	{
		return sqlsrv_fetch_array($this->resource, SQLSRV_FETCH_NUMERIC);
	}

	protected function convertDataFromDb($value, $type)
	{
		switch ($type)
		{
			case 93:
				if($value !== null)
				{
					$value = new Type\DateTime(substr($value, 0, 19), "Y-m-d H:i:s");
				}
				break;
			case 12:
				if((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
				{
					$value = new Type\DateTime($value, "Y-m-d H:i:s");
				}
				break;
			case 91:
				if($value !== null)
				{
					$value = new Type\Date($value, "Y-m-d");
				}
				break;
			default:
				break;
		}

		return $value;
	}
}
