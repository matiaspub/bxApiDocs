<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2012 Bitrix
 */

namespace Bitrix\Main\Data;

use Bitrix\Main;
use Bitrix\Main\Config;

/**
 * Connection pool is a connections holder
 */
class ConnectionPool
{
	/**
	 * @var Connection[]
	 */
	private $connections = array();

	private $connectionParameters = array();

	const DEFAULT_CONNECTION_NAME = "default";

	/**
	 * Creates connection pool object
	 */
	static public function __construct()
	{
	}

	private function createConnection($name, $parameters)
	{
		$className = $parameters['className'];

		if (!class_exists($className))
		{
			throw new Config\ConfigurationException(sprintf(
				"Class '%s' for '%s' connection was not found", $className, $name
			));
		}

		return new $className($parameters);
	}

	/**
	 * Returns database connection by its name. Creates new connection if necessary.
	 *
	 * @param string $name Connection name
	 * @return Connection
	 * @throws \Bitrix\Main\Config\ConfigurationException If connection with specified name does not exist
	 */
	public function getConnection($name = "")
	{
		if ($name === "")
			$name = self::DEFAULT_CONNECTION_NAME;

		if (!isset($this->connections[$name]))
		{
			$connParameters = $this->getConnectionParameters($name);
			if (empty($connParameters) || !is_array($connParameters))
				throw new Config\ConfigurationException(sprintf("Database connection '%s' is not found", $name));

			$this->connections[$name] = $this->createConnection($name, $connParameters);
		}

		return $this->connections[$name];
	}

	/**
	 * Search connection parameters (type, host, db, login and password) by connection name
	 *
	 * @param string $name Connection name
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	private function getConnectionParameters($name)
	{
		if (!is_string($name))
			throw new Main\ArgumentTypeException("name", "string");

		if ($name === "")
			throw new Main\ArgumentNullException("name");

		if (isset($this->connectionParameters[$name]) && !empty($this->connectionParameters[$name]))
			return $this->connectionParameters[$name];

		$params = Config\Configuration::getValue('connections');
		if (isset($params[$name]) && !empty($params[$name]))
			return $params[$name];

		if ($name === static::DEFAULT_CONNECTION_NAME)
		{
			$params = $this->getDbConnConnectionParameters();
			if (!empty($params))
				return $params;
		}

		return null;
	}

	public function setConnectionParameters($name, $parameters)
	{
		$this->connectionParameters[$name] = $parameters;
	}

	static public function getDefaultConnectionType()
	{
		$params = Config\Configuration::getValue('connections');
		if (isset($params[static::DEFAULT_CONNECTION_NAME]) && !empty($params[static::DEFAULT_CONNECTION_NAME]))
		{
			$cn = $params[static::DEFAULT_CONNECTION_NAME]['className'];
			if ($cn === "\\Bitrix\\Main\\DB\\MysqlConnection" || $cn === "\\Bitrix\\Main\\DB\\MysqliConnection")
				return 'MYSQL';
			elseif ($cn === "\\Bitrix\\Main\\DB\\OracleConnection")
				return 'ORACLE';
			else
				return 'MSSQL';
		}

		return strtoupper($GLOBALS["DBType"]);
	}

	private function getDbConnConnectionParameters()
	{
		/* Old kernel code for compatibility */

		global $DBType, $DBDebug, $DBDebugToFile, $DBHost, $DBName, $DBLogin, $DBPassword, $DBSQLServerType;

		require_once(
			Main\Application::getDocumentRoot().
			Main\Application::getPersonalRoot().
			"/php_interface/dbconn.php"
		);

		$DBType = strtolower($DBType);
		if ($DBType == 'mysql')
			$className = "\\Bitrix\\Main\\DB\\MysqlConnection";
		elseif ($DBType == 'mssql')
			$className = "\\Bitrix\\Main\\DB\\MssqlConnection";
		else
			$className = "\\Bitrix\\Main\\DB\\OracleConnection";

		return array(
			'className' => $className,
			'host' => $DBHost,
			'database' => $DBName,
			'login' => $DBLogin,
			'password' => $DBPassword,
			'options' =>  ((!defined("DBPersistent") || DBPersistent) ? Main\DB\Connection::PERSISTENT : 0) | ((defined("DELAY_DB_CONNECT") && DELAY_DB_CONNECT === true) ? Main\DB\Connection::DEFERRED : 0)
		);
	}
}
