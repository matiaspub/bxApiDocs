<?php
namespace Bitrix\Main\DB;

class MysqlConnection
	extends MysqlCommonConnection
{
	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MysqlSqlHelper($this);
	}


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	public function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		mysql_close($this->resource);
	}

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		if (($this->dbOptions & self::PERSISTENT) != 0)
			$connection = mysql_pconnect($this->dbHost, $this->dbLogin, $this->dbPassword);
		else
			$connection = mysql_connect($this->dbHost, $this->dbLogin, $this->dbPassword, true);

		if (!$connection)
			throw new ConnectionException('Mysql connect error', mysql_error());

		if (!mysql_select_db($this->dbName, $connection))
			throw new ConnectionException('Mysql select db error', mysql_error($connection));

		$this->resource = $connection;
		$this->isConnected = true;

		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php"))
			include($fn);
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
	 * @return resource
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

		$result = mysql_query($sql, $this->resource);

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlQueryException('Mysql query error', mysql_error($this->resource), $sql);

		return $result;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return Result
	 */
	protected function createDbResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MysqlResult($result, $this, $trackerQuery);
	}

	public function getIdentity($name = "")
	{
		$this->connectInternal();
		return mysql_insert_id($this->resource);
	}

	public function getAffectedRowsCount()
	{
		return mysql_affected_rows($this->getResource());
	}

	/*********************************************************
	 * DDL
	 *********************************************************/

	public function getTableFields($tableName)
	{
		if (!array_key_exists($tableName, $this->tableColumnsCache))
		{
			$this->tableColumnsCache[$tableName] = array();
			$this->connectInternal();

			// most proper use:
			// $rs = $this->queryInternal("SHOW COLUMNS FROM ".$this->getSqlHelper()->quote($tableName));

			// deprecated use:
			//$rs = mysql_list_fields($this->dbName, $tableName, $this->resource);

			// adopted use:
			$rs = $this->queryInternal("SELECT * FROM ".$this->getSqlHelper()->quote($tableName)." LIMIT 1");
			if ($rs > 0)
			{
				$intNumFields = mysql_num_fields($rs);
				while (--$intNumFields >= 0)
				{
					$name = mysql_field_name($rs, $intNumFields);
					$type = mysql_field_type($rs, $intNumFields);
					$this->tableColumnsCache[$tableName][$name] = array(
						"NAME" => $name,
						"TYPE" => $type,
					);
				}
			}
		}
		return $this->tableColumnsCache[$tableName];
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar("SELECT VERSION()");
			if ($version != null)
			{
				$version = trim($version);
				preg_match("#[0-9]+\\.[0-9]+\\.[0-9]+#", $version, $ar);
				$this->version = $ar[0];
			}
		}

		return array($this->version, null);
	}

	static public function getType()
	{
		return "mysql";
	}

	protected function getErrorMessage()
	{
		return sprintf("[%s] %s", mysql_errno($this->resource), mysql_error($this->resource));
	}
}
