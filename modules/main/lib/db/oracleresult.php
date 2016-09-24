<?php
namespace Bitrix\Main\DB;

class OracleResult extends Result
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
	 * Returns the number of rows in the result.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Нестатический метод возвращает число строк в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oracleresult/getselectedrowscount.php
	* @author Bitrix
	*/
	public function getSelectedRowsCount()
	{
		return oci_num_rows($this->resource);
	}

	/**
	 * Returns an array of fields according to columns in the result.
	 *
	 * @return \Bitrix\Main\Entity\ScalarField[]
	 */
	
	/**
	* <p>Нестатический метод возвращает массив полей, связанный с колонками в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/oracleresult/getfields.php
	* @author Bitrix
	*/
	public function getFields()
	{
		if ($this->resultFields == null)
		{
			$this->resultFields = array();
			if (is_resource($this->resource))
			{
				$numFields = oci_num_fields($this->resource);
				if ($numFields > 0 && $this->connection)
				{
					$helper = $this->connection->getSqlHelper();
					for ($i = 1; $i <= $numFields; $i++)
					{
						$name = oci_field_name($this->resource, $i);
						$type = oci_field_type($this->resource, $i);
						$parameters = array(
							"precision" => oci_field_precision($this->resource, $i),
							"scale" => oci_field_scale($this->resource, $i),
							"size" => oci_field_size($this->resource, $i),
						);

						$this->resultFields[$name] = $helper->getFieldByColumnType($name, $type, $parameters);
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
		return oci_fetch_array($this->resource, OCI_ASSOC + OCI_RETURN_NULLS + OCI_RETURN_LOBS);
	}
}
