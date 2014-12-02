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
	protected $connections = array();

	protected $connectionParameters = array();

	protected $slavePossible = true;
	protected $ignoreDml = 0;
	protected $masterOnly = 0;
	protected $slaveConnection = null;

	const DEFAULT_CONNECTION_NAME = "default";

	/**
	 * Creates connection pool object
	 */
	static public function __construct()
	{
	}

	/**
	 * @param string $name
	 * @param array $parameters
	 * @return Connection
	 * @throws \Bitrix\Main\Config\ConfigurationException
	 */
	protected function createConnection($name, $parameters)
	{
		$className = $parameters['className'];

		if (!class_exists($className))
		{
			throw new Config\ConfigurationException(sprintf(
				"Class '%s' for '%s' connection was not found", $className, $name
			));
		}

		$connection = new $className($parameters);
		$this->connections[$name] = $connection;
		return $connection;
	}

	/**
	 * Returns database connection by its name. Creates new connection if necessary.
	 *
	 * @param string $name Connection name.
	 * @return Connection|null
	 */
	public function getConnection($name = "")
	{
		if ($name === "")
		{
			$name = self::DEFAULT_CONNECTION_NAME;
		}

		if (!isset($this->connections[$name]))
		{
			$connParameters = $this->getConnectionParameters($name);
			if (!empty($connParameters) && is_array($connParameters))
			{
				$this->createConnection($name, $connParameters);
			}
		}

		if (isset($this->connections[$name]))
		{
			return $this->connections[$name];
		}

		return null;
	}

	/**
	 * Searches connection parameters (type, host, db, login and password) by connection name
	 *
	 * @param string $name Connection name
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function getConnectionParameters($name)
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

	/**
	 * Sets named connection paramters.
	 *
	 * @param string $name Name of the connection.
	 * @param array $parameters Parameters values.
	 * @return void
	 */
	public function setConnectionParameters($name, $parameters)
	{
		$this->connectionParameters[$name] = $parameters;
	}

	/**
	 * Returns connected database type.
	 * - MYSQL
	 * - ORACLE
	 * - MSSQL
	 *
	 * @return string
	 */
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

	protected function getDbConnConnectionParameters()
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

	/**
	 * Returns a slave connection or null if the query should go to the master.
	 *
	 * @param string $sql A SQL string. Only SELECT will go to a slave.
	 * @return Main\DB\Connection|null
	 */
	public function getSlaveConnection($sql)
	{
		if($this->masterOnly > 0)
		{
			//We requested to process all queries
			//by master connection
		}
		elseif($this->slavePossible)
		{
			$isSelect = preg_match('/^\s*(select|show)/i', $sql) && !preg_match('/get_lock/i', $sql);
			if(!$isSelect && $this->ignoreDml <= 0)
			{
				$this->slavePossible = false;
			}

			if($isSelect)
			{
				if($this->slaveConnection === null)
				{
					$this->useMasterOnly(true);
					$this->slaveConnection = $this->createSlaveConnection();
					$this->useMasterOnly(false);
				}
				if(is_object($this->slaveConnection))
				{
					return $this->slaveConnection;
				}
			}
		}
		return null;
	}

	/**
	 * In the master-only mode all queries will go to the master.
	 *
	 * @param bool $mode True starts the mode and false ends.
	 * @return void
	 */
	public function useMasterOnly($mode)
	{
		if($mode)
		{
			$this->masterOnly++;
		}
		else
		{
			$this->masterOnly--;
		}
	}

	/**
	 * In the ignore DML mode a data modification command will not stop next queries going to a slave.
	 *
	 * @param bool $mode Whenever to ignore subsequent DML or not.
	 * @return void
	 */
	public function ignoreDml($mode)
	{
		if($mode)
		{
			$this->ignoreDml++;
		}
		else
		{
			$this->ignoreDml--;
		}
	}

	/**
	 * Creates a new slave connection.
	 *
	 * @return bool|null|Connection
	 * @throws \Bitrix\Main\Config\ConfigurationException
	 */
	protected function createSlaveConnection()
	{
		if(!class_exists('csqlwhere'))
		{
			return null;
		}

		if(!Main\Loader::includeModule('cluster'))
		{
			return false;
		}

		$found = \CClusterSlave::GetRandomNode();

		if($found !== false)
		{
			$node = \CClusterDBNode::GetByID($found["ID"]);

			if(is_array($node) && $node["ACTIVE"] == "Y" && ($node["STATUS"] == "ONLINE" || $node["STATUS"] == "READY"))
			{
				$parameters = array(
					'host' => $node["DB_HOST"],
					'database' => $node["DB_NAME"],
					'login' => $node["DB_LOGIN"],
					'password' => $node["DB_PASSWORD"],
				);
				$connection = $this->cloneConnection(self::DEFAULT_CONNECTION_NAME, "node".$node["ID"], $parameters);
				$connection->setNodeId($node["ID"]);
				return $connection;
			}
		}
		return false;
	}

	/**
	 * Creates a new connection based on the supplied one.
	 *
	 * @param string $name Copy source.
	 * @param string $newName Copy target.
	 * @param array $parameters Parameters to be passed to createConnection method.
	 * @throws \Bitrix\Main\Config\ConfigurationException
	 * @return Main\DB\Connection
	 */
	public function cloneConnection($name, $newName, array $parameters=array())
	{
		$defParameters = $this->getConnectionParameters($name);
		if (empty($defParameters) || !is_array($defParameters))
		{
			throw new Config\ConfigurationException(sprintf("Database connection '%s' is not found", $name));
		}
		$parameters = array_merge($defParameters, $parameters);

		$connection = $this->createConnection($newName, $parameters);

		return $connection;
	}

	/**
	 * Returns the state of queries balancing (is a slave still can be used).
	 *
	 * @return bool
	 */
	public function isSlavePossible()
	{
		return $this->slavePossible;
	}

	/**
	 * Returns the state of queries balancing (is the master only can be used).
	 *
	 * @return bool
	 */
	public function isMasterOnly()
	{
		return ($this->masterOnly > 0);
	}
}
