<?php
namespace Bitrix\Main\DB;

class MysqliConnection extends MysqlCommonConnection
{
	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MysqliSqlHelper($this);
	}


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		$dbHost = $this->dbHost;
		$dbPort = 0;
		if (($pos = strpos($dbHost, ":")) !== false)
		{
			$dbPort = intval(substr($dbHost, $pos + 1));
			$dbHost = substr($dbHost, 0, $pos);
		}
		if (($this->dbOptions & self::PERSISTENT) != 0)
			$dbHost = "p:".$dbHost;

		/** @var $connection \mysqli */
		$connection = \mysqli_init();
		if (!$connection)
			throw new ConnectionException('Mysql init failed');

		if (!empty($this->dbInitCommand))
		{
			if (!$connection->options(MYSQLI_INIT_COMMAND, $this->dbInitCommand))
				throw new ConnectionException('Setting mysql init command failed');
		}

		if ($dbPort > 0)
			$r = $connection->real_connect($dbHost, $this->dbLogin, $this->dbPassword, $this->dbName, $dbPort);
		else
			$r = $connection->real_connect($dbHost, $this->dbLogin, $this->dbPassword, $this->dbName);

		if (!$r)
			throw new ConnectionException(
				'Mysql connect error',
				sprintf('(%s) %s', $connection->connect_errno, $connection->connect_error)
			);

		$this->resource = $connection;
		$this->isConnected = true;

		// nosql memcached driver
		if (isset($this->configuration['memcache']))
		{
			$memcached = \Bitrix\Main\Application::getInstance()->getConnectionPool()->getConnection($this->configuration['memcache']);
			mysqlnd_memcache_set($this->resource, $memcached->getResource());
		}


		//global $DB, $USER, $APPLICATION;
		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php"))
			include($fn);
	}

	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		$con = $this->resource;

		/** @var $con \mysqli */
		$con->close();
	}


	/*********************************************************
	 * Query
	 *********************************************************/

	/**
	 * @param $sql
	 * @param array|null $arBinds
	 * @param $offset
	 * @param $limit
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery|null $trackerQuery
	 * @return \mysqli_result
	 * @throws SqlException|\Bitrix\Main\ArgumentException
	 */
	protected function queryInternal($sql, array $arBinds = null, $offset = 0, $limit = 0, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		$this->connectInternal();

		if($limit > 0)
		{
			$sql = $this->getSqlHelper()->getTopSql($sql, $limit, $offset);
		}

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		/** @var $con \mysqli */
		$con = $this->resource;
		$result = $con->query($sql, MYSQLI_STORE_RESULT);

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlQueryException('Mysql query error', $this->getErrorMessage(), $sql);

		return $result;
	}

	public function getIdentity($name = "")
	{
		$con = $this->getResource();

		/** @var $con \mysqli */
		return $con->insert_id;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return Result
	 */
	protected function createDbResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MysqliResult($result, $this, $trackerQuery);
	}

	public function getAffectedRowsCount()
	{
		/** @var $con \mysqli */
		$con = $this->getResource();

		return $con->affected_rows;
	}

	/*********************************************************
	 * DDL
	 *********************************************************/

	public function getTableFields($tableName)
	{
		if (!array_key_exists($tableName, $this->tableColumnsCache))
		{
			$this->tableColumnsCache[$tableName] = array();

			$tableName = preg_replace("/[^a-z0-9_]+/i", "", $tableName);
			$tableName = trim($tableName);

			$dbResult = $this->query("SHOW COLUMNS FROM `".$this->getSqlHelper()->forSql($tableName)."`");
			while ($ar = $dbResult->fetch())
			{
				$ar["NAME"] = $ar["Field"];
				$ar["TYPE"] = $ar["Type"];
				$this->tableColumnsCache[$tableName][$ar["NAME"]] = $ar;
			}
		}
		return $this->tableColumnsCache[$tableName];
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	static public function getType()
	{
		return "mysql";
	}

	public function getVersion()
	{
		if ($this->version == null)
		{
			$con = $this->getResource();

			/** @var $con \mysqli */
			$version = trim($con->server_info);
			preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $ar);
			$this->version = $ar[0];
		}

		return array($this->version, null);
	}

	protected function getErrorMessage()
	{
		$con = $this->resource;

		/** @var $con \mysqli */
		return sprintf("(%s) %s", $con->errno, $con->error);
	}
}
