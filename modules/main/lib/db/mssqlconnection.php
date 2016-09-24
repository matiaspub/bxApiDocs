<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag;
use Bitrix\Main\Entity;

/**
 * Class MssqlConnection
 *
 * Class for MS SQL database connections.
 * @package Bitrix\Main\DB
 */
class MssqlConnection extends Connection
{
	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return \Bitrix\Main\Db\SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MssqlSqlHelper($this);
	}

	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	/**
	 * Establishes a connection to the database.
	 * Includes php_interface/after_connect_d7.php on success.
	 * Throws exception on failure.
	 *
	 * @return void
	 * @throws \Bitrix\Main\DB\ConnectionException
	 */
	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		$connectionInfo = array(
			"UID" => $this->login,
			"PWD" => $this->password,
			"Database" => $this->database,
			"ReturnDatesAsStrings" => true,
			/*"CharacterSet" => "utf-8",*/
		);

		if (($this->options & self::PERSISTENT) != 0)
			$connectionInfo["ConnectionPooling"] = true;
		else
			$connectionInfo["ConnectionPooling"] = false;

		$connection = sqlsrv_connect($this->host, $connectionInfo);

		if (!$connection)
			throw new ConnectionException('MS Sql connect error', $this->getErrorMessage());

		$this->resource = $connection;
		$this->isConnected = true;

		// hide cautions
		sqlsrv_configure("WarningsReturnAsErrors", 0);

		$this->afterConnected();
	}

	/**
	 * Disconnects from the database.
	 * Does nothing if there was no connection established.
	 *
	 * @return void
	 */
	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		sqlsrv_close($this->resource);
	}

	/*********************************************************
	 * Query
	 *********************************************************/

	/**
	 * Executes a query against connected database.
	 * Rises SqlQueryException on any database error.
	 * <p>
	 * When object $trackerQuery passed then calls its startQuery and finishQuery
	 * methods before and after query execution.
	 *
	 * @param string                            $sql Sql query.
	 * @param array                             $binds Array of binds.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Debug collector object.
	 *
	 * @return resource
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	protected function queryInternal($sql, array $binds = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $binds);

		$result = sqlsrv_query($this->resource, $sql, array(), array("Scrollable" => 'forward'));

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlQueryException('MS Sql query error', $this->getErrorMessage(), $sql);

		return $result;
	}

	/**
	 * Returns database depended result of the query.
	 *
	 * @param resource $result Result of internal query function.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Debug collector object.
	 *
	 * @return Result
	 */
	protected function createResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MssqlResult($result, $this, $trackerQuery);
	}

	/**
	 * @return integer
	 */
	public function getInsertedId()
	{
		return $this->queryScalar("SELECT @@IDENTITY as ID");
	}

	/**
	 * Returns affected rows count from last executed query.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Нестатический метод возвращает количество поражённых строк из последнего невыполненного запроса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/getaffectedrowscount.php
	* @author Bitrix
	*/
	public function getAffectedRowsCount()
	{
		return sqlsrv_rows_affected($this->lastQueryResult);
	}

	/**
	 * Checks if a table exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Нестатический метод проверяет существование таблицы.</p>
	*
	*
	* @param string $tableName  Имя таблицы.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/istableexists.php
	* @author Bitrix
	*/
	public function isTableExists($tableName)
	{
		$tableName = preg_replace("/[^A-Za-z0-9%_]+/i", "", $tableName);
		$tableName = Trim($tableName);

		if (strlen($tableName) <= 0)
			return false;

		$result = $this->queryScalar(
			"SELECT COUNT(TABLE_NAME) ".
			"FROM INFORMATION_SCHEMA.TABLES ".
			"WHERE TABLE_NAME LIKE '".$this->getSqlHelper()->forSql($tableName)."'"
		);
		return ($result > 0);
	}

	/**
	 * Checks if an index exists.
	 * Actual columns in the index may differ from requested.
	 * $columns may present an "prefix" of actual index columns.
	 *
	 * @param string $tableName A table name.
	 * @param array  $columns An array of columns in the index.
	 *
	 * @return boolean
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод проверяет существование индекса.</p> <p>Актуально содержание колонок в индексе может отличаться от запрошенных. В <code>$columns</code> можно использовать префикс столбцов актуального индекса.</p>
	*
	*
	* @param string $tableName  Имя таблицы.
	*
	* @param array $columns  Массив столбцов в индексе.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/isindexexists.php
	* @author Bitrix
	*/
	public function isIndexExists($tableName, array $columns)
	{
		return $this->getIndexName($tableName, $columns) !== null;
	}

	/**
	 * Returns the name of an index.
	 *
	 * @param string $tableName A table name.
	 * @param array $columns An array of columns in the index.
	 * @param bool $strict The flag indicating that the columns in the index must exactly match the columns in the $arColumns parameter.
	 *
	 * @return string|null Name of the index or null if the index doesn't exist.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод возвращает имя индекса.</p>
	*
	*
	* @param string $tableName  Название таблицы.
	*
	* @param array $columns  Массив колонок индекса.
	*
	* @param boolean $strict = false Флаг, устанавливающий, что колонки в индексе должны точно
	* соответствовать колонкам в параметре $Columns.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/getindexname.php
	* @author Bitrix
	*/
	public function getIndexName($tableName, array $columns, $strict = false)
	{
		if (!is_array($columns) || count($columns) <= 0)
			return null;

		//2005
		//$rs = $this->query("SELECT index_id, COL_NAME(object_id, column_id) AS column_name, key_ordinal FROM SYS.INDEX_COLUMNS WHERE object_id=OBJECT_ID('".$this->forSql($tableName)."')", true);

		//2000
		$rs = $this->query(
			"SELECT s.indid as index_id, s.keyno as key_ordinal, c.name column_name, si.name index_name ".
			"FROM sysindexkeys s ".
			"   INNER JOIN syscolumns c ON s.id = c.id AND s.colid = c.colid ".
			"   INNER JOIN sysobjects o ON s.id = o.Id AND o.xtype = 'U' ".
			"   LEFT JOIN sysindexes si ON si.indid = s.indid AND si.id = s.id ".
			"WHERE o.name = UPPER('".$this->getSqlHelper()->forSql($tableName)."')");

		$indexes = array();
		while ($ar = $rs->fetch())
		{
			$indexes[$ar["index_name"]][$ar["key_ordinal"] - 1] = $ar["column_name"];
		}

		$columnsList = implode(",", $columns);
		foreach ($indexes as $indexName => $indexColumns)
		{
			ksort($indexColumns);
			$indexColumnList = implode(",", $indexColumns);
			if ($strict)
			{
				if ($indexColumnList === $columnsList)
					return $indexName;
			}
			else
			{
				if (substr($indexColumnList, 0, strlen($columnsList)) === $columnsList)
					return $indexName;
			}
		}

		return null;
	}

	/**
	 * Returns fields objects according to the columns of a table.
	 * Table must exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return Entity\ScalarField[] An array of objects with columns information.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод возвращает объекты полей соответствующие колонкам таблицы. Таблица должна существовать.</p>
	*
	*
	* @param string $tableName  Имя таблицы
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/gettablefields.php
	* @author Bitrix
	*/
	public function getTableFields($tableName)
	{
		if (!isset($this->tableColumnsCache[$tableName]))
		{
			$this->connectInternal();

			$query = $this->queryInternal("SELECT TOP 0 * FROM ".$this->getSqlHelper()->quote($tableName));

			$result = $this->createResult($query);

			$this->tableColumnsCache[$tableName] = $result->getFields();
		}
		return $this->tableColumnsCache[$tableName];
	}

	/**
	 * @param string $tableName Name of the new table.
	 * @param \Bitrix\Main\Entity\ScalarField[] $fields Array with columns descriptions.
	 * @param string[] $primary Array with primary key column names.
	 * @param string[] $autoincrement Which columns will be auto incremented ones.
	 *
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function createTable($tableName, $fields, $primary = array(), $autoincrement = array())
	{
		$sql = 'CREATE TABLE '.$this->getSqlHelper()->quote($tableName).' (';
		$sqlFields = array();

		foreach ($fields as $columnName => $field)
		{
			if (!($field instanceof Entity\ScalarField))
			{
				throw new ArgumentException(sprintf(
					'Field `%s` should be an Entity\ScalarField instance', $columnName
				));
			}

			$realColumnName = $field->getColumnName();

			$sqlFields[] = $this->getSqlHelper()->quote($realColumnName)
				. ' ' . $this->getSqlHelper()->getColumnTypeByField($field)
				. ' NOT NULL'
				. (in_array($columnName, $autoincrement, true) ? ' IDENTITY (1, 1)' : '')
			;
		}

		$sql .= join(', ', $sqlFields);

		if (!empty($primary))
		{
			foreach ($primary as &$primaryColumn)
			{
				$realColumnName = $fields[$primaryColumn]->getColumnName();
				$primaryColumn = $this->getSqlHelper()->quote($realColumnName);
			}

			$sql .= ', PRIMARY KEY('.join(', ', $primary).')';
		}

		$sql .= ')';

		$this->query($sql);
	}

	/**
	 * Renames the table. Renamed table must exists and new name must not be occupied by any database object.
	 *
	 * @param string $currentName Old name of the table.
	 * @param string $newName New name of the table.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод переименовывает таблицу. Таблица обязательно должна существовать и новое имя не должно встречаться в БД.</p>
	*
	*
	* @param string $currentName  Старое имя таблицы
	*
	* @param string $newName  Новое имя таблицы
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/renametable.php
	* @author Bitrix
	*/
	public function renameTable($currentName, $newName)
	{
		$this->query('EXEC sp_rename '.$this->getSqlHelper()->quote($currentName).', '.$this->getSqlHelper()->quote($newName));
	}

	/**
	 * Drops the table.
	 *
	 * @param string $tableName Name of the table to be dropped.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод удаляет таблицу.</p>
	*
	*
	* @param string $tableName  Имя удаляемой таблицы
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/droptable.php
	* @author Bitrix
	*/
	public function dropTable($tableName)
	{
		$this->query('DROP TABLE '.$this->getSqlHelper()->quote($tableName));
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/

	/**
	 * Starts new database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод производит запуск новой транзакции базы данных.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/starttransaction.php
	* @author Bitrix
	*/
	public function startTransaction()
	{
		$this->connectInternal();
		sqlsrv_begin_transaction($this->resource);
	}

	/**
	 * Commits started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический  метод останавливает начатую транзакцию Базы данных.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/committransaction.php
	* @author Bitrix
	*/
	public function commitTransaction()
	{
		$this->connectInternal();
		sqlsrv_commit($this->resource);
	}

	/**
	 * Rollbacks started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод откатывает начатую транзакцию.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/rollbacktransaction.php
	* @author Bitrix
	*/
	public function rollbackTransaction()
	{
		$this->connectInternal();
		sqlsrv_rollback($this->resource);
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	/**
	 * Returns database type.
	 * <ul>
	 * <li> mssql
	 * </ul>
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\Connection::getType
	 */
	
	/**
	* <p>Нестатический метод возвращает тип БД.</p> <ul><li> mssql </li></ul> <p>Без параметров</p>
	*
	*
	* @return string 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/connection/gettype.php">\Bitrix\Main\DB\Connection::getType</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/gettype.php
	* @author Bitrix
	*/
	static public function getType()
	{
		return "mssql";
	}

	/**
	 * Returns connected database version.
	 * Version presented in array of two elements.
	 * - First (with index 0) is database version.
	 * - Second (with index 1) is true when light/express version of database is used.
	 *
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Нестатический метод возвращает версию подключённой БД.</p> <p>Версия представляется в виде массива из двух элементов:</p> - Первый (с индексом 0) - версия БД.<br> - Второй (с индексом 1) выводится, если используется light или express версия БД.<br><p>Без параметров</p>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mssqlconnection/getversion.php
	* @author Bitrix
	*/
	public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar("SELECT @@VERSION");
			if ($version != null)
			{
				$version = trim($version);
				$this->versionExpress = (strpos($version, "Express Edition") > 0);
				preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $arr);
				$this->version = $arr[0];
			}
		}

		return array($this->version, $this->versionExpress);
	}

	/**
	 * Returns error message of last failed database operation.
	 *
	 * @return string
	 */
	protected function getErrorMessage()
	{
		$errors = "";
		foreach (sqlsrv_errors(SQLSRV_ERR_ERRORS) as $error)
		{
			$errors .= "SQLSTATE: ".$error['SQLSTATE'].";"." code: ".$error['code']."; message: ".$error[ 'message']."\n";
		}
		return $errors;
	}
}
