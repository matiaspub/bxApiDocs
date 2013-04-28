<?php
namespace Bitrix\Main\DB;

class OracleDbConnection
	extends DbConnection
{
	private $transaction = OCI_COMMIT_ON_SUCCESS;

	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new OracleSqlHelper($this);
	}


	/***********************************************************
	 * Connection and disconnection
	 ***********************************************************/

	protected function connectInternal()
	{
		if ($this->isConnected)
			return;

		if (($this->dbOptions & self::PERSISTENT) != 0)
			$connection = oci_pconnect($this->dbLogin, $this->dbPassword, $this->dbName);
		else
			$connection = oci_connect($this->dbLogin, $this->dbPassword, $this->dbName);

		if (!$connection)
			throw new ConnectionException('Oracle connect error', $this->getErrorMessage());

		$this->isConnected = true;
		$this->resource = $connection;

		global $DB, $USER, $APPLICATION;
		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect.php"))
			include($fn);
	}

	protected function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		$this->isConnected = false;
		oci_close($this->resource);
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

		$bindsKeys = array();
		if (!empty($arBinds))
		{
			$binds1 = $binds2 = "";
			foreach ($arBinds as $key => $value)
			{
				if (strlen($value) > 0)
				{
					if ($binds1 != "")
					{
						$binds1 .= ",";
						$binds2 .= ",";
					}

					$bindsKeys[] = $key;
					$binds1 .= $key;
					$binds2 .= ":".$key;
				}
			}

			if ($binds1 != "")
				$sql .= " RETURNING ".$binds1." INTO ".$binds2;
		}

		if ($limit > 0)
		{
			if (preg_match("#\\sROWNUM\\W#i", $sql))
				throw new \Bitrix\Main\ArgumentException("Duplicate limit settings");

			if ($offset <= 0)
			{
				$sql =
					"SELECT * ".
					"FROM (".$sql.") ".
					"WHERE ROWNUM <= ".$limit."";
			}
			else
			{
				$sql =
					"SELECT * ".
					"FROM (".
					"   SELECT rownum_query_alias.*, ROWNUM rownum_alias ".
					"   FROM (".$sql.") rownum_query_alias ".
					"   WHERE ROWNUM <= ".($offset + $limit - 1)." ".
					") ".
					"WHERE rownum_alias >= ".$offset."";
			}
		}

		if ($trackerQuery != null)
			$trackerQuery->startQuery($sql, $arBinds);

		$result = oci_parse($this->resource, $sql);

		if (!$result)
		{
			if ($trackerQuery != null)
				$trackerQuery->finishQuery();

			throw new SqlException("", $this->getErrorMessage());
		}

		$executionMode = $this->transaction;
		$clob = array();
		if (!empty($arBinds))
		{
			$executionMode = OCI_DEFAULT;
			foreach ($bindsKeys as $key)
			{
				$clob[$key] = oci_new_descriptor($this->resource, OCI_D_LOB);
				oci_bind_by_name($result, ":".$key, $clob[$key], -1, OCI_B_CLOB);
			}
		}

		if (!oci_execute($result, $executionMode))
		{
			if ($trackerQuery != null)
				$trackerQuery->finishQuery();

			throw new SqlException("", $this->getErrorMessage());
		}

		if (!empty($arBinds))
		{
			if (oci_num_rows($result) > 0)
				foreach ($bindsKeys as $key)
					$clob[$key]->save($arBinds[$key]);

			if ($this->transaction == OCI_COMMIT_ON_SUCCESS)
				oci_commit($this->resource);

			foreach ($bindsKeys as $key)
				$clob[$key]->free();
		}

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		return $result;
	}

	/**
	 * Adds row to table and returns ID of added row
	 *
	 * @param string $tableName
	 * @param array $data
	 * @param string $identity
	 * @return integer
	 */
	static public function add($tableName, array $data, $identity = "ID")
	{
		if($identity !== null && !isset($data[$identity]))
			$data[$identity] = $this->getIdentity("sq_".$tableName);

		$insert = $this->getSqlHelper()->prepareInsert($tableName, $data);

		$binds = array();
		foreach($insert[2] as $name)
			if(isset($data[$name]))
				$binds[$name] = $data[$name];

		$sql =
			"INSERT INTO ".$tableName."(".$insert[0].") ".
			"VALUES (".$insert[1].")";

		$this->queryExecute($sql, $binds);

		return $data[$identity];
	}

	static public function getIdentity($name = "")
	{
		$name = preg_replace("/[^A-Za-z0-9_]+/i", "", $name);
		$name = trim($name);

		if($name == '')
			throw new \Bitrix\Main\ArgumentNullException("name");

		$sql = "SELECT ".$name.".NEXTVAL FROM DUAL";
		return $this->queryScalar($sql);
	}

	/**
	 * @param $result
	 * @param \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery
	 * @return OracleDbResult
	 */
	protected function createDbResult($result, \Bitrix\Main\Diag\SqlTrackerQuery $trackerQuery = null)
	{
		return new OracleDbResult($this, $result, $trackerQuery);
	}

	static public function getAffectedRowsCount()
	{
		return oci_num_rows($this->lastQueryResult);
	}

	/*********************************************************
	 * DDL
	 *********************************************************/

	static public function isTableExists($tableName)
	{
		if (empty($tableName))
			return false;

		$result = $this->queryScalar("
			SELECT COUNT(TABLE_NAME)
			FROM USER_TABLES
			WHERE TABLE_NAME LIKE UPPER('".$this->getSqlHelper()->forSql($tableName)."')
		");
		return ($result > 0);
	}

	static public function isIndexExists($tableName, array $arColumns)
	{
		return $this->getIndexName($tableName, $arColumns) !== null;
	}

	static public function getIndexName($tableName, array $arColumns, $strict = false)
	{
		if (!is_array($arColumns) || empty($arColumns))
			return null;

		$isFunc = false;
		$arIndexes = array();

		$result = $this->query("SELECT * FROM USER_IND_COLUMNS WHERE TABLE_NAME = upper('".$this->getSqlHelper()->forSql($tableName)."')");
		while ($ar = $result->fetch())
		{
			$arIndexes[$ar["INDEX_NAME"]][$ar["COLUMN_POSITION"] - 1] = $ar["COLUMN_NAME"];
			if (strncmp($ar["COLUMN_NAME"], "SYS_NC", 6) === 0)
				$isFunc = true;
		}

		if ($isFunc)
		{
			$result = $this->query("SELECT * FROM USER_IND_EXPRESSIONS WHERE TABLE_NAME = upper('".$this->getSqlHelper()->forSql($tableName)."')");
			while ($ar = $result->fetch())
				$arIndexes[$ar["INDEX_NAME"]][$ar["COLUMN_POSITION"] - 1] = $ar["COLUMN_EXPRESSION"];
		}

		$columns = implode(",", $arColumns);
		foreach ($arIndexes as $key => $arKeyColumn)
		{
			ksort($arKeyColumn);
			$keyColumn = implode(",", $arKeyColumn);
			if ($strict)
			{
				if ($keyColumn === $columns)
					return $key;
			}
			else
			{
				if (substr($keyColumn, 0, strlen($columns)) === $columns)
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
			$sql = "SELECT *
				FROM USER_TAB_COLUMNS
				WHERE UPPER(TABLE_NAME) = UPPER('".$this->getSqlHelper()->forSql($tableName)."')";
			$result = $this->query($sql);
			while ($ar = $result->fetch())
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
		$this->transaction = OCI_DEFAULT;
	}

	static public function commitTransaction()
	{
		$this->connectInternal();
		OCICommit($this->resource);
		$this->transaction = OCI_COMMIT_ON_SUCCESS;
	}

	static public function rollbackTransaction()
	{
		$this->connectInternal();
		OCIRollback($this->resource);
		$this->transaction = OCI_COMMIT_ON_SUCCESS;
	}


	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	protected function getErrorMessage()
	{
		if ($this->isConnected)
			$error = oci_error($this->resource);
		else
			$error = oci_error();

		if (!$error)
			return "";

		$result = sprintf("[%s] %s", $error["code"], $error["message"]);
		if (!empty($error["sqltext"]))
			$result .= sprintf(" (%s)", $error["sqltext"]);

		return $result;
	}

	static public function getType()
	{
		return "oracle";
	}

	static public function getVersion()
	{
		if ($this->version == null)
		{
			$version = $this->queryScalar('SELECT BANNER FROM v$version');
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

}
