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

	/** @var array */
	protected $primary;

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

	public function setPrimary($primary)
	{
		$this->primary = $primary;
	}

	/**
	 * @return array
	 */
	public function getPrimary()
	{
		return $this->primary;
	}

	/**
	 * Returns id of updated record
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает ID обновлённой записи.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/updateresult/getid.php
	* @author Bitrix
	*/
	public function getId()
	{
		if (count($this->primary) == 1)
		{
			return end($this->primary);
		}

		return $this->primary;
	}
}
