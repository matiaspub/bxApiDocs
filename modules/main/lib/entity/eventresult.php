<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity;

class EventResult extends \Bitrix\Main\EventResult
{
	protected $modified;
	protected $unset;
	protected $errors;

	public function __construct()
	{
		parent::__construct(parent::SUCCESS, $parameters = null, $moduleId = null, $handler = null);
		$this->modified = array();
		$this->unset = array();
		$this->errors = array();
	}

	/**
	 * Sets the errors array and changes the event type to ERROR
	 * @param array $errors
	 */
	public function setErrors(array $errors)
	{
		$this->errors = $errors;
		$this->type = parent::ERROR;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Sets the array of fields to modify data in the Bitrix\Main\Entity\Event
	 * @param array $fields
	 */
	public function modifyFields(array $fields)
	{
		$this->modified = $fields;
	}

	public function getModified()
	{
		return $this->modified;
	}

	/**
	 * Sets the array of fields names to unset data in the Bitrix\Main\Entity\Event
	 * @param array $fields
	 */
	public function unsetFields(array $fields)
	{
		$this->unset = $fields;
	}

	public function getUnset()
	{
		return $this->unset;
	}
}
