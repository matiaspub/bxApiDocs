<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Type;

class OracleResult extends Result
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
			case 'DATE':
				if($value !== null)
				{
					if(strlen($value) == 19)
					{
						//preferable format: NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'
						$value = new Type\DateTime($value, "Y-m-d H:i:s");
					}
					else
					{
						//default Oracle date format: 03-MAR-14
						$value = new Type\DateTime($value." 00:00:00", "d-M-y H:i:s");
					}
				}
				break;
			case 'VARCHAR2':
				if ((strlen($value) == 19) && preg_match("#^\\d{4}-\\d{2}-\\d{2} \\d{2}:\\d{2}:\\d{2}$#", $value))
				{
					$value = new Type\DateTime($value, "Y-m-d H:i:s");
				}
				break;
			case 'CLOB':
				if (is_object($value))
				{
					/** @var \OCI_Lob $value */
					$value = $value->load();
				}
				break;
			default:
				break;
		}

		return $value;
	}

	public function getSelectedRowsCount()
	{
		return oci_num_rows($this->resource);
	}

	public function getFieldsCount()
	{
		return oci_num_fields($this->resource);
	}

	public function getFieldName($column)
	{
		return oci_field_name($this->resource, $column + 1);
	}

	public function getResultFields()
	{
		if (empty($this->resultFields))
		{
			$numFields = oci_num_fields($this->resource);
			for ($i = 0; $i < $numFields; $i++)
			{
				$this->resultFields[$i] = array(
					"name" => oci_field_name($this->resource, $i + 1),
					"type" => oci_field_type($this->resource, $i + 1),
				);
			}
		}

		return $this->resultFields;
	}

	protected function fetchRowInternal()
	{
		return oci_fetch_array($this->resource, OCI_NUM + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
	}
}
