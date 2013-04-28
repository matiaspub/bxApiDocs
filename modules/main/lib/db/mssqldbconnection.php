<?php
namespace Bitrix\Main\DB;

class MssqlDbConnection
	extends DbConnection
{
	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MssqlSqlHelper($this);
	}

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		$connectionInfo = array(
			"UID" => $this->dbLogin,
			"PWD" => $this->dbPassword,
			"Database" => $this->dbName,
			"ReturnDatesAsStrings" => true,
			/*"CharacterSet" => "utf-8",*/
		);

		if (($this->dbOptions & self::PERSISTENT) != 0)
			$connectionInfo["ConnectionPooling"] = true;
		else
			$connectionInfo["ConnectionPooling"] = false;

		$connection = sqlsrv_connect($this->dbHost, $connectionInfo);

		if (!$connection)
			throw new ConnectionException('MS Sql connect error', $this->getErrorMessage());

		$this->resource = $connection;
		$this->isConnected = true;

		global $DB, $USER, $APPLICATION;
		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect.php"))
			include($fn);
	}

	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		sqlsrv_close($this->resource);
	}

	/**
	 * @param $sql
	 * @param array|null $arBinds
	 * @param $offset
	 * @param $limit
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery|null $trackerQuery
	 * @return mixed
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
			if (preg_match("#\\sROW_NUMBER\\s*\\(\\s*\\)#i", $sql)
				|| preg_match("#SELECT\\s+TOP\\s+\\d#i", $sql))
				throw new \Bitrix\Main\ArgumentException("Duplicate limit settings");

			if ($offset <= 0)
			{
				$sql = preg_replace("/^\\s*SELECT/i", "SELECT TOP ".$limit, $sql);
			}
			else
			{
				if (preg_match("#(\\s+order\\s+by(\\s+[a-z0-9_.]+(\\s+(asc|desc))?\\s*,)*(\\s+[a-z0-9_.]+(\\s+(asc|desc))?)\\s*)$#i", $sql, $matches))
				{
					$orderBy = $matches[1];
					$sqlTmp = substr($sql, 0, -strlen($orderBy));
				}
				else
				{
					$orderBy = "ORDER BY (SELECT 1)";
					$sqlTmp = $sql;
				}

				$sqlTmp = preg_replace(
					"/^\\s*SELECT/i",
					"SELECT ROW_NUMBER() OVER (".$orderBy.") AS ROW_NUMBER_ALIAS,",
					$sqlTmp
				);

				$sql =
					"WITH ROW_NUMBER_QUERY_ALIAS AS (".$sqlTmp.") ".
					"SELECT * ".
					"FROM ROW_NUMBER_QUERY_ALIAS ".
					"WHERE ROW_NUMBER_ALIAS BETWEEN ".$offset." AND ".($offset + $limit - 1)."";
			}
		}

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		$result = sqlsrv_query($this->resource, $sql, array(), array("Scrollable" => 'forward'));

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlException('MS Sql query error', $this->getErrorMessage());

		return $result;
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return DbResult
	 */
	protected function createDbResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new MssqlDbResult($this, $result, $trackerQuery);
	}

	static public function getIdentity($name = "")
	{
		return $this->queryScalar("SELECT @@IDENTITY as ID");
	}

	static public function getAffectedRowsCount()
	{
		return sqlsrv_rows_affected($this->lastQueryResult);
	}

	/*********************************************************
	 * DDL
	 *********************************************************/
	static public function isTableExists($tableName)
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

	static public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	static public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || count($arColumns) <= 0)
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

		$arIndexes = array();
		while ($ar = $rs->fetch())
			$arIndexes[$ar["index_name"]][$ar["key_ordinal"] - 1] = $ar["column_name"];

		$strColumns = implode(",", $arColumns);
		foreach ($arIndexes as $key => $keyColumn)
		{
			ksort($keyColumn);
			$strKeyColumns = implode(",", $keyColumn);
			if ($strict)
			{
				if ($strKeyColumns === $strColumns)
					return $key;
			}
			else
			{
				if (substr($strKeyColumns, 0, strlen($strColumns)) === $strColumns)
					return $key;
			}
		}

		return null;
	}

	static public function getTableFields($tableName)
	{
		if (!array_key_exists($tableName, $this->tableColumnsCache))
		{
			$this->tableColumnsCache[$tableName] = array();
			$strSql =
				"SELECT * ".
				"FROM INFORMATION_SCHEMA.COLUMNS ".
				"WHERE TABLE_NAME = '".$this->getSqlHelper()->forSql($tableName)."'";
			$rs = $this->query($strSql);
			while ($ar = $rs->fetch())
			{
				$ar["NAME"] = $ar["COLUMN_NAME"];
				$ar["TYPE"] = $ar["DATA_TYPE"];
				$this->tableColumnsCache[$tableName][$ar["COLUMN_NAME"]] = $ar;
			}
		}
		return $this->tableColumnsCache[$tableName];
	}

	/*********************************************************
	 * Transaction
	 *********************************************************/
	static public function startTransaction()
	{
		$this->connectInternal();
		sqlsrv_begin_transaction($this->resource);
	}

	static public function commitTransaction()
	{
		$this->connectInternal();
		sqlsrv_commit($this->resource);
	}

	static public function rollbackTransaction()
	{
		$this->connectInternal();
		sqlsrv_rollback($this->resource);
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/
	static public function getType()
	{
		return "mssql";
	}

	static public function getVersion()
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

	protected function getErrorMessage()
	{
		$errors = "";

		$arErrors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
		foreach ($arErrors as $error)
			$errors .= "SQLSTATE: ".$error['SQLSTATE'].";"." code: ".$error['code']."; message: ".$error[ 'message']."\n";

		return $errors;
	}
}
