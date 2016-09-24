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
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/__construct.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает соединение с БД по его имени. Создаёт новое соединение, если необходимо.</p>
	*
	*
	* @param string $name = "" Имя соединения.
	*
	* @return \Bitrix\Main\Data\Connection|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/getconnection.php
	* @author Bitrix
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
		{
			throw new Main\ArgumentTypeException("name", "string");
		}

		if ($name === "")
		{
			throw new Main\ArgumentNullException("name");
		}

		$params = null;
		if (isset($this->connectionParameters[$name]) && !empty($this->connectionParameters[$name]))
		{
			$params = $this->connectionParameters[$name];
		}
		else
		{
			$configParams = Config\Configuration::getValue('connections');
			if (isset($configParams[$name]) && !empty($configParams[$name]))
			{
				$params = $configParams[$name];
			}
			elseif ($name === static::DEFAULT_CONNECTION_NAME)
			{
				$dbconnParams = $this->getDbConnConnectionParameters();
				if (!empty($dbconnParams))
				{
					$params = $dbconnParams;
				}
			}
		}

		if ($params !== null && $name === static::DEFAULT_CONNECTION_NAME && !isset($params["include_after_connected"]))
		{
			$params["include_after_connected"] = \Bitrix\Main\Loader::getPersonal("php_interface/after_connect_d7.php");
		}

		return $params;
	}

	/**
	 * Sets named connection paramters.
	 *
	 * @param string $name Name of the connection.
	 * @param array $parameters Parameters values.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает параметры названного соединения.</p>
	*
	*
	* @param string $name  Имя соединения.
	*
	* @param array $parameters  Значения параметров.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/setconnectionparameters.php
	* @author Bitrix
	*/
	public function setConnectionParameters($name, $parameters)
	{
		$this->connectionParameters[$name] = $parameters;

		if(isset($this->connections[$name]))
		{
			unset($this->connections[$name]);
		}
	}

	/**
	 * Returns connected database type.
	 * - MYSQL
	 * - ORACLE
	 * - MSSQL
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает тип соединяемой БД</p> <ul> <li>MYSQL</li> <li>ORACLE</li>  <li>MSSQL</li>   </ul> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/getdefaultconnectiontype.php
	* @author Bitrix
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

		global $DBType, $DBDebug, $DBDebugToFile, $DBHost, $DBName, $DBLogin, $DBPassword;

		require_once(
			Main\Application::getDocumentRoot().
			Main\Application::getPersonalRoot().
			"/php_interface/dbconn.php"
		);

		$className = null;
		$type = strtolower($DBType);
		if($type == 'mysql')
		{
			$className = "\\Bitrix\\Main\\DB\\MysqlConnection";
		}
		elseif($type == 'mssql')
		{
			$className = "\\Bitrix\\Main\\DB\\MssqlConnection";
		}
		elseif($type == 'oracle')
		{
			$className = "\\Bitrix\\Main\\DB\\OracleConnection";
		}

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
	
	/**
	* <p>Нестатический метод возвращает соединение <i>slave</i>. Или <i>null</i> если запрос должен быть отправлен только к мастеру.</p>
	*
	*
	* @param string $sql  Строка SQL. Только SELECT будет отправлен на <i>slave</i>.
	*
	* @return \Bitrix\Main\DB\Connection|null 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/getslaveconnection.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод задаёт работу в только мастер-режиме. В этом режиме все запросы будут идти только к мастеру.</p>
	*
	*
	* @param boolean $mode  <i>True</i> запускает мастер-режим, <i>false</i> - завершает.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/usemasteronly.php
	* @author Bitrix
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
	 * @param bool $mode Ignore subsequent DML or not.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод. При игнорировании DML режима команда модификации данных не остановит запросы к <i>slave</i>.</p>
	*
	*
	* @param boolean $mode  Игнорировать или нет следующий DML.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/ignoredml.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод создаёт новое соединение основанное на уже используемом.</p>
	*
	*
	* @param string $name  Источник копирования.
	*
	* @param string $newName  Цель копирования.
	*
	* @param array $parameters = array() Параметры, полученные от метода <code>ConnectionPool::createConnection</code>.
	*
	* @return \Bitrix\Main\DB\Connection 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/cloneconnection.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает состояние запросов балансировки (если возможно использование <i>slave</i>).</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/isslavepossible.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает состояние запросов балансировки (если будет использован только мастер).</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connectionpool/ismasteronly.php
	* @author Bitrix
	*/
	public function isMasterOnly()
	{
		return ($this->masterOnly > 0);
	}
}
