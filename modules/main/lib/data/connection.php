<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2012 Bitrix
 */

namespace Bitrix\Main\Data;

abstract class Connection
{
	protected $resource;

	protected $isConnected = false;

	protected $configuration;


	public function __construct($configuration)
	{
		$this->configuration = $configuration;
	}

	public function connect()
	{
		if ($this->isConnected)
		{
			return;
		}

		$this->connectInternal();
	}

	public function disconnect()
	{
		if (!$this->isConnected)
		{
			return;
		}

		$this->disconnectInternal();
	}

	public function getResource()
	{
		$this->connectInternal();
		return $this->resource;
	}

	abstract protected function connectInternal();
	abstract protected function disconnectInternal();

	public function getConfiguration()
	{
		return $this->configuration;
	}
}