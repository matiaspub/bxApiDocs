<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

use Bitrix\Main\DB\Connection;

class UpdateResult extends Result
{
	/** @var int */
	protected $affectedRowsCount;

	static public function __construct()
	{
		parent::__construct();
	}

	public function setAffectedRowsCount(Connection $connection)
	{
		$this->affectedRowsCount = $connection->getAffectedRowsCount();
	}

	/**
	 * @return int
	 */
	public function getAffectedRowsCount()
	{
		return $this->affectedRowsCount;
	}
}
