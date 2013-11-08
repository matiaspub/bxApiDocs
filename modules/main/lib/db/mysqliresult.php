<?php
namespace Bitrix\Main\DB;

class MysqliResult extends Result
{
	private $resultFields = array();

	static public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	protected function convertDataFromDb($value, $fieldType)
	{
		switch ($fieldType)
		{
			case 12:
			case 7:
				return $value === null ? null : new \Bitrix\Main\Type\DateTime($value, "Y-m-d H:i:s");
				break;
			case 10:
				return $value === null ? null : new \Bitrix\Main\Type\DateTime($value, "Y-m-d");
				break;
			default:
				break;
		}

		return $value;
	}

	public function getSelectedRowsCount()
	{
		/** @var $r \mysqli_result */
		$r = $this->resource;

		return $r->num_rows;
	}

	public function getFieldsCount()
	{
		$con = $this->connection->getResource();
		/** @var $con \mysqli */

		return $con->field_count;
	}

	public function getFieldName($column)
	{
		/** @var $r \mysqli_result */
		$r = $this->resource;

		return $r->fetch_field_direct($column);
	}

	public function getResultFields()
	{
		if (empty($this->resultFields))
		{
			/** @var $r \mysqli_result */
			$r = $this->resource;
			$resultFields = $r->fetch_fields();
			$this->resultFields = array();
			foreach ($resultFields as $key => $value)
			{
				$this->resultFields[$key] = array(
					"name" => $resultFields[$key]->name,
					"type" => $resultFields[$key]->type,
				);
			}
		}

		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		/** @var $r \mysqli_result */
		$r = $this->resource;
		return $r->fetch_row();
	}
}
