<?php
namespace Bitrix\Main\DB;

class MysqlDbConnection
	extends DbConnection
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

	static public function disconnectInternal()
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

		$offset = intval($offset);
		$limit = intval($limit);

		if ($offset > 0 && $limit <= 0)
			throw new \Bitrix\Main\ArgumentException("Limit should be set if offset is set");

		if ($limit > 0)
		{
			if (preg_match("#\\slimit\\s+\\d#i", $sql))
				throw new \Bitrix\Main\ArgumentException("Duplicate limit settings");

			$sql .= "\nLIMIT ".intval($offset).", ".intval($limit)."\n";
		}

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		$result = mysql_query($sql, $this->resource);

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlException('Mysql query error', mysql_error($this->resource));

		return $result;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return DbResult
	 */
	protected function createDbResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MysqlDbResult($this, $result, $trackerQuery);
	}

	static public function getIdentity($name = "")
	{
		$this->connectInternal();
		return mysql_insert_id($this->resource);
	}

	static public function getAffectedRowsCount()
	{
		return mysql_affected_rows($this->getResource());
	}

	/*********************************************************
	 * DDL
	 *********************************************************/

	static public function isTableExists($tableName)
	{
		$tableName = preg_replace("/[^A-Za-z0-9%_]+/i", "", $tableName);
		$tableName = trim($tableName);

		if (strlen($tableName) <= 0)
			return false;

		$dbResult = $this->query("SHOW TABLES LIKE '".$this->getSqlHelper()->forSql($tableName)."'");
		if ($arResult = $dbResult->fetch())
			return true;
		else
			return false;
	}

	static public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	static public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || count($arColumns) <= 0)
			return null;

		$rs = $this->query("SHOW INDEX FROM `".$this->getSqlHelper()->forSql($tableName)."`");
		if (!$rs)
			return null;

		$arIndexes = array();
		while ($ar = $rs->fetch())
			$arIndexes[$ar["Key_name"]][$ar["Seq_in_index"] - 1] = $ar["Column_name"];

		$strColumns = implode(",", $arColumns);
		foreach ($arIndexes as $Key_name => $arKeyColumns)
		{
			ksort($arKeyColumns);
			$strKeyColumns = implode(",", $arKeyColumns);
			if ($strict)
			{
				if ($strKeyColumns === $strColumns)
					return $Key_name;
			}
			else
			{
				if (substr($strKeyColumns, 0, strlen($strColumns)) === $strColumns)
					return $Key_name;
			}
		}

		return null;
	}

	static public function getTableFields($tableName)
	{
		if (!array_key_exists($tableName, $this->tableColumnsCache))
		{
			$this->tableColumnsCache[$tableName] = array();
			$this->connectInternal();
			$rs = mysql_list_fields($this->dbName, $tableName, $this->resource);
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
	 * Transaction
	 *********************************************************/

	static public function startTransaction()
	{
		$this->query("START TRANSACTION");
	}

	static public function commitTransaction()
	{
		$this->query("COMMIT");
	}

	static public function rollbackTransaction()
	{
		$this->query("ROLLBACK");
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	static public function getVersion()
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
