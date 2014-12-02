<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2012 Bitrix
 */

namespace Bitrix\Main\Data;

/**
 * Class description
 * @package    bitrix
 * @subpackage main
 */
class MemcacheConnection extends NosqlConnection
{
	protected $host = 'localhost';

	protected $port = '11211';

	public function __construct(array $configuration)
	{
		parent::__construct($configuration);

		// host validation
		if (array_key_exists('host', $configuration))
		{
			if (!is_string($configuration['host']) || $configuration['host'] == "")
			{
				throw new \Bitrix\Main\Config\ConfigurationException("Invalid host parameter");
			}

			$this->host = $configuration['host'];
		}

		// port validation
		if (array_key_exists('port', $configuration))
		{
			if (!is_string($configuration['port']) || $configuration['port'] == "")
			{
				throw new \Bitrix\Main\Config\ConfigurationException("Invalid port parameter");
			}

			$this->port = $configuration['port'];
		}
	}

	protected function connectInternal()
	{
		$this->resource = new \Memcache;
		$this->isConnected = $this->resource->connect($this->host, $this->port);
	}

	protected function disconnectInternal()
	{
	}

	public function get($key)
	{
		$this->connectInternal();

		return $this->resource->get($key);
	}

	public function set($key, $value)
	{
		$this->connectInternal();

		return $this->resource->set($key, $value);
	}
}
