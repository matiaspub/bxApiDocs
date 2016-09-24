<?php
namespace Bitrix\Main\DB;

class MysqliResult extends Result
{
	/** @var \mysqli_result */
	protected $resource;

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqliresult/getselectedrowscount.php
	* @author Bitrix
	*/
	public function getSelectedRowsCount()
	{
		return $this->resource->num_rows;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqliresult/getfields.php
	* @author Bitrix
	*/
	public function getFields()
	{
		if ($this->resultFields == null)
		{
			$this->resultFields = array();
			if (is_object($this->resource))
			{
				$fields = $this->resource->fetch_fields();
				if ($fields && $this->connection)
				{
					$helper = $this->connection->getSqlHelper();
					foreach ($fields as $field)
					{
						$this->resultFields[$field->name] = $helper->getFieldByColumnType($field->name, $field->type);
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
		return $this->resource->fetch_assoc();
	}
}
