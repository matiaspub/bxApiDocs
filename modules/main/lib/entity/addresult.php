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
	protected $id;

	static public function __construct()
	{
		parent::__construct();
	}

	static public function setId($id)
	{
		$this->id = $id;
	}

	static public function getId()
	{
		return $this->id;
	}
}
