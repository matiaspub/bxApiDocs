<?php
namespace Bitrix\Main\DB;

class OracleDbResult
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
			case 'DATE':
				return new \Bitrix\Main\Type\DateTime($value, "d-M-y");
				break;
			case 'CLOB':
				if (is_object($value))
					return $value->load();
				break;
			case 'VARCHAR2':
				if ((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
					return new \Bitrix\Main\Type\DateTime($value, "Y-m-d H:i:s");
				break;
			default:
				break;
		}

		return $value;
	}

	static public function getSelectedRowsCount()
	{
		return oci_num_rows($this->resultResource);
	}

	static public function getFieldsCount()
	{
		return oci_num_fields($this->resultResource);
	}

	static public function getFieldName($column)
	{
		return oci_field_name($this->resultResource, $column + 1);
	}

	static public function getResultFields()
	{
		if (empty($this->resultFields))
		{
			$numFields = oci_num_fields($this->resultResource);
			for ($i = 0; $i < $numFields; $i++)
				$this->resultFields[$i] = array(
					"name" => oci_field_name($this->resultResource, $i + 1),
					"type" => oci_field_type($this->resultResource, $i + 1),
				);
		}

		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return oci_fetch_array($this->resultResource, OCI_NUM + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
	}
}
