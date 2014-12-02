<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2014 Bitrix
 */

namespace Bitrix\Main\Data;

/**
 * Class Connection
 *
 * Abstarct base class for data connections.
 */
abstract class Connection
{
	/** @var resource */
	protected $resource;
	protected $isConnected = false;
	protected $configuration;

	public function __construct(array $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * Connects to data source.
	 */
	public function connect()
	{
		$this->isConnected = false;

		$this->connectInternal();
	}

	/**
	 * Disconects from data source.
	 */
	public function disconnect()
	{
		$this->disconnectInternal();
	}

	/**
	 * Returns the resource of the connection.
	 *
	 * @return resource
	 */
	public function getResource()
	{
		$this->connectInternal();
		return $this->resource;
	}

	/**
	 * Returns the state of the connection.
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return $this->isConnected;
	}

	abstract protected function connectInternal();
	abstract protected function disconnectInternal();

	/**
	 * Returns the array with the connection parameters.
	 *
	 * @return array
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}
}
