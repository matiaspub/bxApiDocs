<?php
namespace Bitrix\Main\DB;

use Bitrix\Main;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config;
use Bitrix\Main\Data;
use Bitrix\Main\Diag;
use Bitrix\Main\Entity;

/**
 * Class Connection
 *
 * Base abstract class for database connections.
 * @package Bitrix\Main\DB
 */
abstract class Connection extends Data\Connection
{
	/**@var SqlHelper */
	protected $sqlHelper;

	/** @var Diag\SqlTracker */
	protected $sqlTracker;
	protected $trackSql = false;

	protected $version;
	protected $versionExpress;

	protected $host;
	protected $database;
	protected $login;
	protected $password;
	protected $initCommand = 0;
	protected $options = 0;
	protected $nodeId = 0;

	protected $tableColumnsCache = array();
	protected $lastQueryResult;

	/**
	 * @var bool Flag for static::query - if need to execute query or just to collect it
	 * @see $disabledQueryExecutingDump
	 */
	protected $queryExecutingEnabled = true;

	/** @var null|string[] Queries that were collected while Query Executing was Disabled */
	protected $disabledQueryExecutingDump;

	const PERSISTENT = 1;
	const DEFERRED = 2;

	/**
	 * $configuration may contain following keys:
	 * <ul>
	 * <li>host
	 * <li>database
	 * <li>login
	 * <li>password
	 * <li>initCommand
	 * <li>options
	 * </ul>
	 *
	 * @param array $configuration Array of Name => Value pairs.
	 */
	public function __construct(array $configuration)
	{
		parent::__construct($configuration);

		$this->host = $configuration['host'];
		$this->database = $configuration['database'];
		$this->login = $configuration['login'];
		$this->password = $configuration['password'];
		$this->initCommand = isset($configuration['initCommand']) ? $configuration['initCommand'] : "";
		$this->options = intval($configuration['options']);
	}

	/**
	 * @return string
	 * @deprecated Use getHost()
	 */
	public function getDbHost()
	{
		return $this->getHost();
	}

	/**
	 * @return string
	 * @deprecated Use getLogin()
	 */
	public function getDbLogin()
	{
		return $this->getLogin();
	}

	/**
	 * @return string
	 * @deprecated Use getDatabase()
	 */
	public function getDbName()
	{
		return $this->getDatabase();
	}

	/**
	 * Returns database host.
	 *
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * Returns database login.
	 *
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * Returns database name.
	 *
	 * @return string
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * Sets the connection resource directly.
	 *
	 * @param resource &$connection Database depended connection resource.
	 *
	 * @return void
	 */
	public function setConnectionResourceNoDemand(&$connection)
	{
		$this->resource = &$connection;
		$this->isConnected = true;
	}

	/**
	 * Temporary disables query executing. All queries being collected in disabledQueryExecutingDump
	 *
	 * @api
	 * @see enableQueryExecuting
	 * @see getDisabledQueryExecutingDump
	 *
	 * @return void
	 */
	public function disableQueryExecuting()
	{
		$this->queryExecutingEnabled = false;
	}

	/**
	 * Enables query executing after it has been temporary disabled
	 *
	 * @api
	 * @see disableQueryExecuting
	 *
	 * @return void
	 */
	public function enableQueryExecuting()
	{
		$this->queryExecutingEnabled = true;
	}

	/**
	 * @api
	 * @see disableQueryExecuting
	 *
	 * @return bool
	 */
	public function isQueryExecutingEnabled()
	{
		return $this->queryExecutingEnabled;
	}

	/**
	 * Returns queries that were collected while Query Executing was disabled and clears the dump.
	 *
	 * @api
	 * @see disableQueryExecuting
	 *
	 * @return null|\string[]
	 */
	public function getDisabledQueryExecutingDump()
	{
		$dump = $this->disabledQueryExecutingDump;
		$this->disabledQueryExecutingDump = null;

		return $dump;
	}

	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return \Bitrix\Main\Db\SqlHelper
	 */
	abstract protected function createSqlHelper();

	/**
	 * Returns database depended SqlHelper object.
	 * Creates new one on the first call per Connection object instance.
	 *
	 * @return \Bitrix\Main\Db\SqlHelper
	 */
	public function getSqlHelper()
	{
		if ($this->sqlHelper == null)
			$this->sqlHelper = $this->createSqlHelper();

		return $this->sqlHelper;
	}

	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	/**
	 * Connects to the database.
	 *
	 * @return void
	 */
	public function connect()
	{
		$this->isConnected = false;

		if (($this->options & self::DEFERRED) != 0)
			return;

		parent::connect();
	}

	/**
	 * Disconnects from the database.
	 *
	 * @return void
	 */
	public function disconnect()
	{
		if (($this->options & self::PERSISTENT) != 0)
			return;

		parent::disconnect();
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
	abstract protected function queryInternal($sql, array $binds = null, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null);

	/**
	 * Returns database depended result of the query.
	 *
	 * @param resource $result Result of internal query function.
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery Debug collector object.
	 *
	 * @return Result
	 */
	abstract protected function createResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null);

	/**
	 * Executes a query to the database.
	 *
	 * - query($sql)
	 * - query($sql, $limit)
	 * - query($sql, $offset, $limit)
	 * - query($sql, $binds)
	 * - query($sql, $binds, $limit)
	 * - query($sql, $binds, $offset, $limit)
	 *
	 * @param string $sql Sql query.
	 * @param array $binds,... Array of binds.
	 * @param int $offset,... Offset of first row returned.
	 * @param int $limit,... Limit rows count.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function query($sql)
	{
		list($sql, $binds, $offset, $limit) = self::parseQueryFunctionArgs(func_get_args());

		if($limit > 0)
		{
			$sql = $this->getSqlHelper()->getTopSql($sql, $limit, $offset);
		}

		$trackerQuery = null;

		if ($this->queryExecutingEnabled)
		{
			$connection = Main\Application::getInstance()->getConnectionPool()->getSlaveConnection($sql);
			if($connection === null)
			{
				$connection = $this;
			}

			if ($this->trackSql)
			{
				$trackerQuery = $this->sqlTracker->getNewTrackerQuery();
				$trackerQuery->setNode($connection->getNodeId());
			}

			$result = $connection->queryInternal($sql, $binds, $trackerQuery);
		}
		else
		{
			if ($this->disabledQueryExecutingDump === null)
			{
				$this->disabledQueryExecutingDump = array();
			}

			$this->disabledQueryExecutingDump[] = $sql;
			$result = true;
		}

		return $this->createResult($result, $trackerQuery);
	}

	/**
	 * Executes a query, fetches a row and returns single field value
	 * from the first column of the result.
	 *
	 * @param string $sql Sql text.
	 * @param array $binds Binding array.
	 *
	 * @return string|null
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function queryScalar($sql, array $binds = null)
	{
		$result = $this->query($sql, $binds, 0, 1);

		if ($row = $result->fetch())
		{
			return array_shift($row);
		}

		return null;
	}

	/**
	 * Executes a query without returning result, i.e. INSERT, UPDATE, DELETE
	 *
	 * @param string $sql Sql text.
	 * @param array[string]mixed $binds Binding array.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function queryExecute($sql, array $binds = null)
	{
		$this->query($sql, $binds);
	}

	/**
	 * Helper function for parameters handling.
	 *
	 * @param mixed $args Variable list of parameters.
	 *
	 * @return array
	 * @throws ArgumentNullException
	 */
	protected static function parseQueryFunctionArgs($args)
	{
		/*
		 * query($sql)
		 * query($sql, $limit)
		 * query($sql, $offset, $limit)
		 * query($sql, $arBinds)
		 * query($sql, $arBinds, $limit)
		 * query($sql, $arBinds, $offset, $limit)
		 */
		$numArgs = count($args);
		if ($numArgs < 1)
			throw new ArgumentNullException("sql");

		$binds = array();
		$offset = 0;
		$limit = 0;

		if ($numArgs == 1)
		{
			$sql = $args[0];
		}
		elseif ($numArgs == 2)
		{
			if (is_array($args[1]))
				list($sql, $binds) = $args;
			else
				list($sql, $limit) = $args;
		}
		elseif ($numArgs == 3)
		{
			if (is_array($args[1]))
				list($sql, $binds, $limit) = $args;
			else
				list($sql, $offset, $limit) = $args;
		}
		else
		{
			list($sql, $binds, $offset, $limit) = $args;
		}

		return array($sql, $binds, $offset, $limit);
	}

	/**
	 * Adds row to table and returns ID of the added row.
	 * <p>
	 * $identity parameter must be null when table does not have autoincrement column.
	 *
	 * @param string $tableName Name of the table for insertion of new row..
	 * @param array $data Array of columnName => Value pairs.
	 * @param string $identity For Oracle only.
	 *
	 * @return integer
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function add($tableName, array $data, $identity = "ID")
	{
		$insert = $this->getSqlHelper()->prepareInsert($tableName, $data);

		$sql =
			"INSERT INTO ".$tableName."(".$insert[0].") ".
			"VALUES (".$insert[1].")";

		$this->queryExecute($sql);

		return $this->getInsertedId();
	}

	/**
	 * @return integer
	 */
	abstract public function getInsertedId();

	/**
	 * Parses the string containing multiple queries and executes the queries one by one.
	 * Queries delimiter depends on database type.
	 * @see \Bitrix\Main\Db\SqlHelper->getQueryDelimiter
	 *
	 * @param string $sqlBatch String with queries, separated by database-specific delimiters.
	 * @param bool $stopOnError Whether return after the first error.
	 *
	 * @return array Array of errors or empty array on success.
	 */
	public function executeSqlBatch($sqlBatch, $stopOnError = false)
	{
		$delimiter = $this->getSqlHelper()->getQueryDelimiter();

		$sqlBatch = trim($sqlBatch);

		$statements = array();
		$sql = "";

		do
		{
			if (preg_match("%^(.*?)(['\"`#]|--|".$delimiter.")%is", $sqlBatch, $match))
			{
				//Found string start
				if ($match[2] == "\"" || $match[2] == "'" || $match[2] == "`")
				{
					$sqlBatch = substr($sqlBatch, strlen($match[0]));
					$sql .= $match[0];
					//find a quote not preceded by \
					if (preg_match("%^(.*?)(?<!\\\\)".$match[2]."%s", $sqlBatch, $stringMatch))
					{
						$sqlBatch = substr($sqlBatch, strlen($stringMatch[0]));
						$sql .= $stringMatch[0];
					}
					else
					{
						//String foll beyond end of file
						$sql .= $sqlBatch;
						$sqlBatch = "";
					}
				}
				//Comment found
				elseif ($match[2] == "#" || $match[2] == "--")
				{
					//Take that was before comment as part of sql
					$sqlBatch = substr($sqlBatch, strlen($match[1]));
					$sql .= $match[1];
					//And cut the rest
					$p = strpos($sqlBatch, "\n");
					if ($p === false)
					{
						$p1 = strpos($sqlBatch, "\r");
						if ($p1 === false)
							$sqlBatch = "";
						elseif ($p < $p1)
							$sqlBatch = substr($sqlBatch, $p);
						else
							$sqlBatch = substr($sqlBatch, $p1);
					}
					else
						$sqlBatch = substr($sqlBatch, $p);
				}
				//Delimiter!
				else
				{
					//Take that was before delimiter as part of sql
					$sqlBatch = substr($sqlBatch, strlen($match[0]));
					$sql .= $match[1];
					//Delimiter must be followed by whitespace
					if (preg_match("%^[\n\r\t ]%", $sqlBatch))
					{
						$sql = trim($sql);
						if (!empty($sql))
						{
							$statements[] = str_replace("\r\n", "\n", $sql);
							$sql = "";
						}
					}
					//It was not delimiter!
					elseif (!empty($sqlBatch))
					{
						$sql .= $match[2];
					}
				}
			}
			else //End of file is our delimiter
			{
				$sql .= $sqlBatch;
				$sqlBatch = "";
			}
		}
		while (!empty($sqlBatch));

		$sql = trim($sql);
		if (!empty($sql))
			$statements[] = str_replace("\r\n", "\n", $sql);

		$result = array();
		foreach ($statements as $sql)
		{
			try
			{
				$this->queryExecute($sql);
			}
			catch (SqlException $ex)
			{
				$result[] = $ex->getMessage();
				if ($stopOnError)
					return $result;
			}
		}

		return $result;
	}

	/**
	 * Returns affected rows count from last executed query.
	 *
	 * @return integer
	 */
	abstract public function getAffectedRowsCount();

	/*********************************************************
	 * DDL
	 *********************************************************/

	/**
	 * Checks if a table exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return boolean
	 */
	abstract public function isTableExists($tableName);

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
	abstract public function isIndexExists($tableName, array $columns);

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
	abstract public function getIndexName($tableName, array $columns, $strict = false);

	/**
	 * Returns fields objects according to the columns of a table.
	 * Table must exists.
	 *
	 * @param string $tableName The table name.
	 *
	 * @return Entity\ScalarField[] An array of objects with columns information.
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	abstract public function getTableFields($tableName);

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
	abstract public function createTable($tableName, $fields, $primary = array(), $autoincrement = array());

	/**
	 * Creates primary index on column(s)
	 * @api
	 *
	 * @param string          $tableName Name of the table.
	 * @param string|string[] $columnNames Name of the column or array of column names to be included into the index.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function createPrimaryIndex($tableName, $columnNames)
	{
		if (!is_array($columnNames))
		{
			$columnNames = array($columnNames);
		}

		foreach ($columnNames as &$columnName)
		{
			$columnName = $this->getSqlHelper()->quote($columnName);
		}

		$sql = 'ALTER TABLE '.$this->getSqlHelper()->quote($tableName).' ADD PRIMARY KEY('.join(', ', $columnNames).')';

		return $this->query($sql);
	}

	/**
	 * Creates index on column(s)
	 * @api
	 *
	 * @param string          $tableName Name of the table.
	 * @param string          $indexName Name of the new index.
	 * @param string|string[] $columnNames Name of the column or array of column names to be included into the index.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function createIndex($tableName, $indexName, $columnNames)
	{
		if (!is_array($columnNames))
		{
			$columnNames = array($columnNames);
		}

		$sqlHelper = $this->getSqlHelper();

		foreach ($columnNames as &$columnName)
		{
			$columnName = $sqlHelper->quote($columnName);
		}
		unset($columnName);

		$sql = 'CREATE INDEX '.$sqlHelper->quote($indexName).' ON '.$sqlHelper->quote($tableName).' ('.join(', ', $columnNames).')';

		return $this->query($sql);
	}

	/**
	 * Returns an object for the single column according to the column type.
	 *
	 * @param string $tableName Name of the table.
	 * @param string $columnName Name of the column.
	 *
	 * @return Entity\ScalarField | null
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function getTableField($tableName, $columnName)
	{
		$tableFields = $this->getTableFields($tableName);

		return (isset($tableFields[$columnName])? $tableFields[$columnName] : null);
	}

	/**
	 * Truncates all table data
	 *
	 * @param string $tableName Name of the table.
	 * @return Result
	 */
	public function truncateTable($tableName)
	{
		return $this->query('TRUNCATE TABLE '.$this->getSqlHelper()->quote($tableName));
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
	abstract public function renameTable($currentName, $newName);

	/**
	 * Drops a column. This column must exists and must be not the part of primary constraint.
	 * and must be not the last one in the table.
	 *
	 * @param string $tableName Name of the table to which column will be dropped.
	 * @param string $columnName Name of the column to be dropped.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	public function dropColumn($tableName, $columnName)
	{
		$this->query('ALTER TABLE '.$this->getSqlHelper()->quote($tableName).' DROP COLUMN '.$this->getSqlHelper()->quote($columnName));
	}

	/**
	 * Drops the table.
	 *
	 * @param string $tableName Name of the table to be dropped.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	abstract public function dropTable($tableName);

	/*********************************************************
	 * Transaction
	 *********************************************************/

	/**
	 * Starts new database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	abstract public function startTransaction();

	/**
	 * Commits started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	abstract public function commitTransaction();

	/**
	 * Rollbacks started database transaction.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	abstract public function rollbackTransaction();


	/*********************************************************
	 * Tracker
	 *********************************************************/

	/**
	 * Starts collecting information about all queries executed.
	 *
	 * @param boolean $reset Clears all previously collected information when set to true.
	 *
	 * @return \Bitrix\Main\Diag\SqlTracker
	 */
	public function startTracker($reset = false)
	{
		if ($this->sqlTracker == null)
			$this->sqlTracker = new Diag\SqlTracker();
		if ($reset)
			$this->sqlTracker->reset();

		$this->trackSql = true;
		return $this->sqlTracker;
	}

	/**
	 * Stops collecting information about all queries executed.
	 *
	 * @return void
	 */
	public function stopTracker()
	{
		$this->trackSql = false;
	}

	/**
	 * Returns an object with information about queries executed.
	 * or null if no tracking was started.
	 *
	 * @return null|\Bitrix\Main\Diag\SqlTracker
	 */
	public function getTracker()
	{
		return $this->sqlTracker;
	}

	/**
	 * Sets new sql tracker.
	 *
	 * @param null|Diag\SqlTracker $sqlTracker New tracker.
	 *
	 * @return void
	 */
	public function setTracker(\Bitrix\Main\Diag\SqlTracker $sqlTracker = null)
	{
		$this->sqlTracker = $sqlTracker;
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	/**
	 * Returns database type.
	 * <ul>
	 * <li> mysql
	 * <li> oracle
	 * <li> mssql
	 * </ul>
	 *
	 * @return string
	 */
	abstract public function getType();

	/**
	 * Returns connected database version.
	 * Version presented in array of two elements.
	 * - First (with index 0) is database version.
	 * - Second (with index 1) is true when light/express version of database is used.
	 *
	 * @return array
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	abstract public function getVersion();

	/**
	 * Returns error message of last failed database operation.
	 *
	 * @return string
	 */
	abstract protected function getErrorMessage();

	/**
	 * Clears all internal caches which may be used by some dictionary functions.
	 *
	 * @return void
	 */
	public function clearCaches()
	{
		$this->tableColumnsCache = array();
	}

	/**
	 * Sets connection node identifier.
	 *
	 * @param string $nodeId Node identifier.
	 * @return void
	 */
	public function setNodeId($nodeId)
	{
		$this->nodeId = $nodeId;
	}

	/**
	 * Returns connection node identifier.
	 *
	 * @return string|null
	 */
	public function getNodeId()
	{
		return $this->nodeId;
	}
}
