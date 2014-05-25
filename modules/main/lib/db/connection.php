<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config;
use Bitrix\Main\Data;
use Bitrix\Main\Diag;

abstract class Connection
	extends Data\Connection
{
	/**@var SqlHelper */
	protected $sqlHelper;

	/** @var Diag\SqlTracker */
	protected $sqlTracker;
	protected $trackSql = false;

	protected $version;
	protected $versionExpress;

	protected $dbHost;
	protected $dbName;
	protected $dbLogin;
	protected $dbPassword;
	protected $dbInitCommand = 0;
	protected $dbOptions = 0;

	protected $tableColumnsCache = array();
	protected $lastQueryResult;


	const PERSISTENT = 1;
	const DEFERRED = 2;

	public function __construct($configuration)
	{
		parent::__construct($configuration);

		//if (!is_string($configuration['database']) || $configuration['database'] == "")
		//	throw new Config\ConfigurationException("Empty database name");
		//if (!is_string($configuration['login']) || $configuration['login'] == "")
		//	throw new Config\ConfigurationException("Empty database user login");

		$this->dbHost = $configuration['host'];
		$this->dbName = $configuration['database'];
		$this->dbLogin = $configuration['login'];
		$this->dbPassword = $configuration['password'];
		$this->dbInitCommand = isset($configuration['initCommand']) ? $configuration['initCommand'] : "";

		$this->dbOptions = intval($configuration['options']);
		if ($this->dbOptions < 0)
			$this->dbOptions = self::PERSISTENT | self::DEFERRED;
	}

	public function getDbHost()
	{
		return $this->dbHost;
	}

	public function getDbLogin()
	{
		return $this->dbLogin;
	}

	public function getDbName()
	{
		return $this->dbName;
	}

	public function setConnectionResourceNoDemand(&$dbCon)
	{
		$this->resource = &$dbCon;
		$this->isConnected = true;
	}

	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	public function getSqlHelper()
	{
		if ($this->sqlHelper == null)
			$this->sqlHelper = $this->createSqlHelper();

		return $this->sqlHelper;
	}

	/**
	 * @return SqlHelper
	 */
	abstract protected function createSqlHelper();


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	public function connect()
	{
		if (($this->dbOptions & self::DEFERRED) != 0)
			return;

		parent::connect();
	}

	public function disconnect()
	{
		if (($this->dbOptions & self::PERSISTENT) != 0)
			return;

		parent::disconnect();
	}


	/*********************************************************
	 * Query
	 *********************************************************/

	abstract protected function queryInternal($sql, array $arBinds = null, $offset = 0, $limit = 0, Diag\SqlTrackerQuery $trackerQuery = null);

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return Result
	 */
	abstract protected function createDbResult($result, Diag\SqlTrackerQuery $trackerQuery = null);

	/**
	 * Executes a query to database
	 * query($sql)
	 * query($sql, $limit)
	 * query($sql, $offset, $limit)
	 * query($sql, $arBinds)
	 * query($sql, $arBinds, $limit)
	 * query($sql, $arBinds, $offset, $limit)
	 *
	 * @param string $sql Sql query
	 * @param array $arBinds Array of binds
	 * @param int $offset Offset
	 * @param int $limit Limit
	 * @return Result
	 */
	public function query($sql)
	{
		list($sql, $arBinds, $offset, $limit) = self::parseQueryFunctionArgs(func_get_args());

		$trackerQuery = null;
		if ($this->trackSql)
			$trackerQuery = $this->sqlTracker->getNewTrackerQuery();

		$result = $this->queryInternal($sql, $arBinds, $offset, $limit, $trackerQuery);

		return $this->createDbResult($result, $trackerQuery);
	}

	/**
	 * Executes a query, fetches a row and returns single field value
	 *
	 * @param string $sql
	 * @param array $arBinds
	 * @return string|null
	 */
	public function queryScalar($sql, array $arBinds = null)
	{
		$trackerQuery = null;
		if ($this->trackSql)
			$trackerQuery = $this->sqlTracker->getNewTrackerQuery();

		$result = $this->queryInternal($sql, $arBinds, 0, 1, $trackerQuery);
		$dbResult = $this->createDbResult($result, $trackerQuery);

		$return = null;
		if ($ar = $dbResult->fetch())
			$return = array_shift($ar);

		return $return;
	}

	/**
	 * Executes a query without returning result, i.e. INSERT, UPDATE, DELETE
	 *
	 * @param string $sql
	 * @param array $arBinds
	 */
	public function queryExecute($sql, array $arBinds = null)
	{
		$trackerQuery = null;
		if ($this->trackSql)
			$trackerQuery = $this->sqlTracker->getNewTrackerQuery();

		$this->queryInternal($sql, $arBinds, 0, 0, $trackerQuery);
	}

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

		$arBinds = array();
		$offset = 0;
		$limit = 0;

		if ($numArgs == 1)
		{
			$sql = $args[0];
		}
		elseif ($numArgs == 2)
		{
			if (is_array($args[1]))
				list($sql, $arBinds) = $args;
			else
				list($sql, $limit) = $args;
		}
		elseif ($numArgs == 3)
		{
			if (is_array($args[1]))
				list($sql, $arBinds, $limit) = $args;
			else
				list($sql, $offset, $limit) = $args;
		}
		else
		{
			list($sql, $arBinds, $offset, $limit) = $args;
		}

		return array($sql, $arBinds, $offset, $limit);
	}

	/**
	 * Adds row to table and returns ID of added row
	 *
	 * @param string $tableName
	 * @param array $data
	 * @param string $identity For Oracle only
	 * @return integer
	 */
	public function add($tableName, array $data, $identity = "ID")
	{
		$insert = $this->getSqlHelper()->prepareInsert($tableName, $data);

		$sql =
			"INSERT INTO ".$tableName."(".$insert[0].") ".
			"VALUES (".$insert[1].")";

		$this->queryExecute($sql);

		return $this->getIdentity();
	}

	abstract public function getIdentity($name = "");

	public function executeSqlBatch($sqlBatch, $stopOnError = false)
	{
		$delimiter = $this->getSqlHelper()->getQueryDelimiter();

		$sqlBatch = trim($sqlBatch);

		$arSqlBatch = array();
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
					//find a qoute not preceeded by \
					if (preg_match("%^(.*?)(?<!\\\\)".$match[2]."%s", $sqlBatch, $string_match))
					{
						$sqlBatch = substr($sqlBatch, strlen($string_match[0]));
						$sql .= $string_match[0];
					}
					else
					{
						//String falled beyong end of file
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
							$arSqlBatch[] = str_replace("\r\n", "\n", $sql);
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
			$arSqlBatch[] = str_replace("\r\n", "\n", $sql);

		$result = array();
		foreach ($arSqlBatch as $sql)
		{
			try
			{
				$this->queryExecute($sql);
			}
			catch (SqlException $ex)
			{
				$result[] = $ex->getMessage();
				if ($stopOnError)
					return $result[0];
			}
		}

		return $result;
	}

	/**
	 * Returns affected rows count from last executed query
	 *
	 * @return int
	 */
	abstract public function getAffectedRowsCount();

	/*********************************************************
	 * DDL
	 *********************************************************/

	abstract public function isTableExists($tableName);
	abstract public function isIndexExists($tableName, array $arColumns);
	abstract public function getIndexName($tableName, array $arColumns, $strict = false);
	abstract public function getTableFields($tableName);

	public function getTableField($tableName, $columnName)
	{
		$tableFields = $this->getTableFields($tableName);

		return isset($tableFields[$columnName]) ? $tableFields[$columnName] : null;
	}

	abstract public function renameTable($currentName, $newName);

	public function dropColumn($tableName, $columnName)
	{
		$this->query('ALTER TABLE '.$this->getSqlHelper()->quote($tableName).' DROP COLUMN '.$this->getSqlHelper()->quote($columnName));
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/

	abstract public function startTransaction();
	abstract public function commitTransaction();
	abstract public function rollbackTransaction();


	/*********************************************************
	 * Tracker
	 *********************************************************/

	public function startTracker($reset = false)
	{
		if ($this->sqlTracker == null)
			$this->sqlTracker = new Diag\SqlTracker();
		if ($reset)
			$this->sqlTracker->reset();

		$this->trackSql = true;
	}

	public function stopTracker()
	{
		$this->trackSql = false;
	}

	public function getTracker()
	{
		return $this->sqlTracker;
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	abstract public function getType();
	abstract public function getVersion();
	abstract protected function getErrorMessage();

	public function clearCaches()
	{
		$this->tableColumnsCache = array();
	}
}
