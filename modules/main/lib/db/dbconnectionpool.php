<?php
namespace Bitrix\Main\DB;

/**
 * Connection pool is a cache of database connections
 */
class DbConnectionPool
{
	/**
	 * @var DbConnection[]
	 */
	private $arConnections = array();

	const DEFAULT_CONNECTION = "default";
	const DEFAULT_CONNECTION_CONFIGURATION = "default_db_connection";

	/**
	 * Creates connection pool object
	 */
	static public function __construct()
	{
	}

	private function createConnection($connectionType, $hostName, $dbName, $login, $password, $initCommand = '', $options = -1)
	{
		$className = $this->getDbConnectionClassNameByType($connectionType);
		return new $className($hostName, $dbName, $login, $password, $initCommand, $options);
	}

	private function getDbConnectionClassNameByType($connectionType)
	{
		switch ($connectionType)
		{
			case 'mysql':
				$className = "\\Bitrix\\Main\\DB\\MysqliDbConnection";
				if (!\extension_loaded("mysqli"))
					$className = "\\Bitrix\\Main\\DB\\MysqlDbConnection";
				break;
			case 'oracle':
				$className = "\\Bitrix\\Main\\DB\\OracleDbConnection";
				break;
			case 'mssql':
				$className = "\\Bitrix\\Main\\DB\\MssqlDbConnection";
				break;
			case 'mssql_native':
				$className = "\\Bitrix\\Main\\DB\\MssqlNativeDbConnection";
				break;
			default:
				throw new \Bitrix\Main\Config\ConfigurationException("Database type mismatch");
		}

		return $className;
	}

	/**
	 * Returns database connection by int name. Creates new connection if necessary.
	 *
	 * @param string $name Connection name
	 * @return DbConnection
	 * @throws \Bitrix\Main\Config\ConfigurationException If connection with specified name does not exist
	 */
	public function getConnection($name = "")
	{
		if ($name === "")
			$name = self::DEFAULT_CONNECTION;

		if (!isset($this->arConnections[$name]))
		{
			$conParams = $this->searchConnectionParametersByName($name);
			if ($conParams && is_array($conParams))
			{
				$connection = $this->createConnection(
					$conParams["type"],
					$conParams["host"],
					$conParams["db_name"],
					$conParams["login"],
					$conParams["password"],
					isset($conParams["init_command"]) ? $conParams["init_command"] : "",
					isset($conParams["options"]) ? $conParams["options"] : -1
				);
				$this->arConnections[$name] = $connection;
			}
			else
			{
				throw new \Bitrix\Main\Config\ConfigurationException(sprintf("Database connection '%s' is not found", $name));
			}
		}

		return $this->arConnections[$name];
	}

	/**
	 * Search connection parameters (type, host, db, login and password) by connection name
	 *
	 * @param string $name Connection name
	 * @return array('type' => string, 'host' => string, 'db_name' => string, 'login' => string, 'password' => string, "init_command" => string, "options" => string)|null
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	private function searchConnectionParametersByName($name)
	{
		if (!is_string($name))
			throw new \Bitrix\Main\ArgumentTypeException("name", "string");
		if ($name === "")
			throw new \Bitrix\Main\ArgumentNullException("name");

		if ($name === self::DEFAULT_CONNECTION)
		{
			$v = \Bitrix\Main\Config\Configuration::getValue(self::DEFAULT_CONNECTION_CONFIGURATION);
			if ($v != null)
				return $v;

			$DBType = "";
			$DBHost = "";
			$DBName = "";
			$DBLogin = "";
			$DBPassword = "";
			include(\Bitrix\Main\IO\Path::convertRelativeToAbsolute("/bitrix/php_interface/dbconn.php"));
			return array("type" => $DBType, "host" => $DBHost, "db_name" => $DBName, "login" => $DBLogin, "password" => $DBPassword);
		}

		/* TODO: реализовать */
		return null;
	}

	/*public static function getDbNodeConnection($nodeId, $checkStatus = true)
		{
			global $DB;

			if(!array_key_exists($nodeId, self::$arNodes))
			{
				if(CModule::includeModule('cluster'))
					self::$arNodes[$nodeId] = CClusterDBNode::getByID($nodeId);
				else
					self::$arNodes[$nodeId] = false;
			}
			$node = &self::$arNodes[$nodeId];

			if(
				is_array($node)
				&& (
					!$checkStatus
					|| (
						$node["ACTIVE"] == "Y"
						&& ($node["STATUS"] == "ONLINE" || $node["STATUS"] == "READY")
					)
				)
				&& !isset($node["ONHIT_ERROR"])
			)
			{
				if(!array_key_exists("DB", $node))
				{
					$node_DB = new CDatabase;
					$node_DB->type = $DB->type;
					$node_DB->debug = $DB->debug;
					$node_DB->DebugToFile = $DB->DebugToFile;
					$node_DB->bNodeConnection = true;
					if($node_DB->connect($node["DB_HOST"], $node["DB_NAME"], $node["DB_LOGIN"], $node["DB_PASSWORD"]))
					{
						if(defined("DELAY_DB_CONNECT") && DELAY_DB_CONNECT===true)
						{
							if($node_DB->doConnect())
								$node["DB"] = $node_DB;
						}
						else
						{
							$node["DB"] = $node_DB;
						}
					}
				}

				if(array_key_exists("DB", $node))
					return $node["DB"];
			}

			if($bIgnoreErrors)
			{
				return false;
			}
			else
			{
				if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn_error.php"))
					include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn_error.php");
				else
					include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/dbconn_error.php");
				die();
			}
		}

public static 		function GetModuleConnection($module_id, $bModuleInclude = false)
		{
			$node_id = COption::getOptionString($module_id, "dbnode_id", "N");
			if(is_numeric($node_id))
			{
				if($bModuleInclude)
				{
					$status = COption::getOptionString($module_id, "dbnode_status", "ok");
					if($status === "move")
						return false;
				}

				$moduleDB = CDatabase::getDBNodeConnection($node_id, $bModuleInclude);

				if(is_object($moduleDB))
				{
					$moduleDB->bModuleConnection = true;
					return $moduleDB;
				}

				//There was an connection error
				if($bModuleInclude && CModule::includeModule('cluster'))
					CClusterDBNode::setOffline($node_id);

				//TODO: unclear what to return when node went offline
				//in the middle of the hit.
				return false;
			}
			else
			{
				return $GLOBALS["DB"];
			}
		}
	*/
}
