<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

class AddResult extends Result
{
	/** @var  int */
	protected $id;

	static public function __construct()
	{
		parent::__construct();
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Returns id of added record
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает ID добавленной записи.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/addresult/getid.php
	* @author Bitrix
	*/
	public function getId()
	{
		return $this->id;
	}
}
