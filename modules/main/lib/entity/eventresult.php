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
	/** @var array */
	protected $modified = array();

	/** @var string[] */
	protected $unset = array();

	/** @var EntityError[] */
	protected $errors = array();

	static public function __construct()
	{
		parent::__construct(parent::SUCCESS, $parameters = null, $moduleId = null, $handler = null);
	}

	/**
	 * Sets the errors array and changes the event type to ERROR
	 * @param EntityError[] $errors
	 */
	public function setErrors(array $errors)
	{
		$this->errors = $errors;
		$this->type = parent::ERROR;
	}

	/**
	 * @param EntityError $error
	 */
	public function addError(EntityError $error)
	{
		$this->errors[] = $error;
		$this->type = parent::ERROR;
	}

	/**
	 * @return EntityError[]|FieldError[]
	 */
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

	/**
	 * @param string $fieldName
	 */
	public function unsetField($fieldName)
	{
		$this->unset[] = $fieldName;
	}

	public function getUnset()
	{
		return $this->unset;
	}
}
