<?php
namespace Bitrix\Main\DB;

class MysqlResult extends Result
{
	/** @var \Bitrix\Main\Entity\ScalarField[]  */
	protected $resultFields = null;

	/**
	 * @param resource $result Database-specific query result.
	 * @param Connection $dbConnection Connection object.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Helps to collect debug information.
	 */
	static public function __construct($result, Connection $dbConnection, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		parent::__construct($result, $dbConnection, $trackerQuery);
	}

	/**
	 * Returns the number of rows in the result.
	 *
	 * @return integer
	 */
	public function getSelectedRowsCount()
	{
		return mysql_num_rows($this->resource);
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
				$numFields = mysql_num_fields($this->resource);
				if ($numFields > 0 && $this->connection)
				{
					$helper = $this->connection->getSqlHelper();
					for ($i = 0; $i < $numFields; $i++)
					{
						$name = mysql_field_name($this->resource, $i);
						$type = mysql_field_type($this->resource, $i);

						$this->resultFields[$name] = $helper->getFieldByColumnType($name, $type);
					}
				}
			}
		}
		return $this->resultFields;
	}

	/**
	 * Returns next result row or false.
	 *
	 * @return array|false
	 */
	protected function fetchRowInternal()
	{
		return mysql_fetch_assoc($this->resource);
	}
}
