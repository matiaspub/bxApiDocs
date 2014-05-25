<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Type;

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
			case MYSQLI_TYPE_DATETIME:
			case MYSQLI_TYPE_TIMESTAMP:
				if($value !== null)
				{
					$value = new Type\DateTime($value, "Y-m-d H:i:s");
				}
				break;
			case MYSQLI_TYPE_DATE:
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
