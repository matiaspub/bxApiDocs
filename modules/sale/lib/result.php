<?php
namespace Bitrix\Sale;

use Bitrix\Main\Entity;

class Result extends Entity\Result
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

	static public function __destruct()
	{
		//just quietly die in contrast Entity\Result either checked errors or not.
	}

	public function addData(array $data)
	{
		$this->data = array_merge($this->data, $data);
	}
}

class ResultError
	extends Entity\EntityError
{

}