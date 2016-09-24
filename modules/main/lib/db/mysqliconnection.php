<?php
namespace Bitrix\Main\DB;

use Bitrix\Main\Diag;

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

		$host = $this->host;
		$port = 0;
		if (($pos = strpos($host, ":")) !== false)
		{
			$port = intval(substr($host, $pos + 1));
			$host = substr($host, 0, $pos);
		}
		if (($this->options & self::PERSISTENT) != 0)
			$host = "p:".$host;

		/** @var $connection \mysqli */
		$connection = \mysqli_init();
		if (!$connection)
			throw new ConnectionException('Mysql init failed');

		if (!empty($this->initCommand))
		{
			if (!$connection->options(MYSQLI_INIT_COMMAND, $this->initCommand))
				throw new ConnectionException('Setting mysql init command failed');
		}

		if ($port > 0)
			$r = $connection->real_connect($host, $this->login, $this->password, $this->database, $port);
		else
			$r = $connection->real_connect($host, $this->login, $this->password, $this->database);

		if (!$r)
		{
			throw new ConnectionException(
				'Mysql connect error ['.$this->host.']',
				sprintf('(%s) %s', $connection->connect_errno, $connection->connect_error)
			);
		}

		$this->resource = $connection;
		$this->isConnected = true;

		// nosql memcached driver
		if (isset($this->configuration['memcache']))
		{
			$memcached = \Bitrix\Main\Application::getInstance()->getConnectionPool()->getConnection($this->configuration['memcache']);
			mysqlnd_memcache_set($this->resource, $memcached->getResource());
		}

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
		$con = $this->resource;

		/** @var $con \mysqli */
		$con->close();
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
		return new MysqliResult($result, $this, $trackerQuery);
	}

	/**
	 * @return integer
	 */
	public function getInsertedId()
	{
		$con = $this->getResource();

		/** @var $con \mysqli */
		return $con->insert_id;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqliconnection/getaffectedrowscount.php
	* @author Bitrix
	*/
	public function getAffectedRowsCount()
	{
		/** @var $con \mysqli */
		$con = $this->getResource();

		return $con->affected_rows;
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
	
	/**
	* <p>Нестатический метод возвращает тип БД:</p> <p></p> <ul><li> mysql </li></ul> <p>Без параметров</p>
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqliconnection/gettype.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает версию подключённой БД.</p> <p>Версия представляется в виде массива из двух элементов:</p> - Первый (с индексом 0) - версия БД.<br> - Второй (с индексом 1) выводится, если используется light или express версия БД.<br><p>Без параметров</p>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqliconnection/getversion.php
	* @author Bitrix
	*/
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

	/**
	 * Returns error message of last failed database operation.
	 *
	 * @return string
	 */
	protected function getErrorMessage()
	{
		$con = $this->resource;

		/** @var $con \mysqli */
		return sprintf("(%s) %s", $con->errno, $con->error);
	}

	/**
	 * Selects the default database for database queries.
	 *
	 * @param string $database Database name.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод устанавливает БД по умолчанию для запросов.</p>
	*
	*
	* @param string $database  Имя БД.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/mysqliconnection/selectdatabase.php
	* @author Bitrix
	*/
	public function selectDatabase($database)
	{
		/** @var $con \mysqli */
		$con = $this->resource;
		return $con->select_db($database);
	}
}
