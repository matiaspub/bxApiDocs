<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2012 Bitrix
 */

namespace Bitrix\Main\Data;

/**
 * Connection pool is a connections holder
 */
class ConnectionPool
{
	/**
	 * @var Connection[]
	 */
	private $connections = array();

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
			throw new \Bitrix\Main\Config\ConfigurationException(sprintf(
				"Class '%s' for '%s' connection was not found", $className, $name
			));
		}

		return new $className($parameters);
	}

	/**
	 * Returns database connection by int name. Creates new connection if necessary.
	 *
	 * @param string $name Connection name
	 * @return Connection
	 * @throws \Bitrix\Main\Config\ConfigurationException If connection with specified name does not exist
	 */
	static public function getConnection($name = "")
	{
		if ($name === "")
		{
			$name = self::DEFAULT_CONNECTION_NAME;
		}

		if (!isset($this->connections[$name]))
		{
			$connParameters = $this->searchConnectionParametersByName($name);

			if (!empty($connParameters) && is_array($connParameters))
			{
				$connection = $this->createConnection($name, $connParameters);

				$this->connections[$name] = $connection;
			}
			else
			{
				throw new \Bitrix\Main\Config\ConfigurationException(sprintf(
					"Database connection '%s' is not found", $name
				));
			}
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
	private function searchConnectionParametersByName($name)
	{
		if (!is_string($name))
		{
			throw new \Bitrix\Main\ArgumentTypeException("name", "string");
		}

		if ($name === "")
		{
			throw new \Bitrix\Main\ArgumentNullException("name");
		}

		$connectionsConfiguration = \Bitrix\Main\Config\Configuration::getValue('connections');

		if (isset($connectionsConfiguration[$name]))
		{
			return $connectionsConfiguration[$name];
		}

		/* TODO: exception, if config not found */
		return null;
	}
}
