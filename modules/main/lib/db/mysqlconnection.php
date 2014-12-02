<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Diag;

class MysqlConnection extends MysqlCommonConnection
{
	/**********************************************************
	 * SqlHelper
	 **********************************************************/

	/**
	 * @return \Bitrix\Main\Db\SqlHelper
	 */
	protected function createSqlHelper()
	{
		return new MysqlSqlHelper($this);
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

		if (($this->options & self::PERSISTENT) != 0)
			$connection = mysql_pconnect($this->host, $this->login, $this->password);
		else
			$connection = mysql_connect($this->host, $this->login, $this->password, true);

		if (!$connection)
			throw new ConnectionException('Mysql connect error ['.$this->host.', '.gethostbyname($this->host).']', mysql_error());

		if (!mysql_select_db($this->database, $connection))
			throw new ConnectionException('Mysql select db error ['.$this->database.']', mysql_error($connection));

		$this->resource = $connection;
		$this->isConnected = true;

		if ($fn = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php"))
			include($fn);
	}

	/**
	 * Disconnects from the database.
	 * Does nothing if there was no connection established.
	 *
	 * @return void
	 */
	public function disconnectInternal()
	{
		if (!$this->isConnected)
			return;

		mysql_close($this->resource);

		$this->isConnected = false;
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

		$result = mysql_query($sql, $this->resource);

		if ($trackerQuery != null)
			$trackerQuery->finishQuery();

		$this->lastQueryResult = $result;

		if (!$result)
			throw new SqlQueryException('Mysql query error', mysql_error($this->resource), $sql);

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
		return new MysqlResult($result, $this, $trackerQuery);
	}

	/**
	 * @return integer
	 */
	public function getInsertedId()
	{
		$this->connectInternal();
		return mysql_insert_id($this->resource);
	}

	/**
	 * Returns affected rows count from last executed query.
	 *
	 * @return integer
	 */
	public function getAffectedRowsCount()
	{
		return mysql_affected_rows($this->getResource());
	}

	/*********************************************************
	 * Type, version, cache, etc.
	 *********************************************************/

	/**
	 * Returns database type.
	 * <ul>
	 * <li> mysql
	 * </ul>
	 *
	 * @return string
	 * @see \Bitrix\Main\DB\Connection::getType
	 */
	static public function getType()
	{
		return "mysql";
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

	/**
	 * Returns error message of last failed database operation.
	 *
	 * @return string
	 */
	protected function getErrorMessage()
	{
		return sprintf("[%s] %s", mysql_errno($this->resource), mysql_error($this->resource));
	}
}
