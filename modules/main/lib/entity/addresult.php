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
	public function getId()
	{
		return $this->id;
	}
}
