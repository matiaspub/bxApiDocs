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
	
	/**
	* <p>Нестатический метод возвращает массив полей, связанный с колонками в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlresult/getfields.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает число строк в результате запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlresult/getselectedrowscount.php
	* @author Bitrix
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
