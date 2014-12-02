<?php
namespace Bitrix\Main\DB;

class MssqlResult extends Result
{
	/** @var \Bitrix\Main\Entity\ScalarField[]  */
	private $resultFields = null;

	/**
	 * @param resource $result Database-specific query result.
	 * @param Connection $dbConnection Connection object.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Helps to collect debug information.
	 */
	static public function __construct($result, Connection $dbConnection = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	/**
	 * Returns an array of fields according to columns in the result.
	 *
	 * @return \Bitrix\Main\Entity\ScalarField[]
	 */
	public function getFields()
	{
		if ($this->resultFields == null)
		{
			$this->resultFields = array();
			if (is_resource($this->resource))
			{
				$fields = sqlsrv_field_metadata($this->resource);
				if ($fields && $this->connection)
				{
					$helper = $this->connection->getSqlHelper();
					foreach ($fields as $value)
					{
						$name = ($value["Name"] <> ''? $value["Name"]: uniqid());
						$parameters = array(
							"size" => $value["Size"],
							"scale" => $value["Scale"],
						);
						$this->resultFields[$name] = $helper->getFieldByColumnType($name, $value["Type"], $parameters);
					}
				}
			}
		}

		return $this->resultFields;
	}

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return integer
	 */
	public function getSelectedRowsCount()
	{
		return sqlsrv_num_rows($this->resource);
	}

	/**
	 * Returns next result row or false.
	 *
	 * @return array|false
	 */
	protected function fetchRowInternal()
	{
		return sqlsrv_fetch_array($this->resource, SQLSRV_FETCH_ASSOC);
	}
}
