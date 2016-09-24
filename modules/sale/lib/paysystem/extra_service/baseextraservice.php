<?php

namespace Bitrix\Sale\PaySystem\ExtraService;

use Bitrix\Main\NotImplementedException;
use Bitrix\Sale\Internals\Input;

abstract class BaseExtraService
{
	/**
	 * @var array
	 */
	protected $fields;
	/**
	 * @var array
	 */
	protected $additionalFields;

	/**
	 * @param array $initParams
	 * @param array $additionalParams
	 */
	public function __construct(array $initParams, array $additionalParams = array())
	{
		$this->fields = $initParams;
		$this->additionalFields = $additionalParams;
	}

	/**
	 * @param $name
	 * @return string
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEditControl($name)
	{
		$value = ($this->getValue() !== '') ? $this->getValue() : $this->getDefaultValue();

		return Input\Manager::getEditHtml($name, $this->fields['PARAMS'], $value);
	}

	/**
	 * @throws NotImplementedException
	 */
	public function getViewControl()
	{
		return Input\Manager::getViewHtml($this->fields['PARAMS'], $this->getValue());
	}

	/**
	 * @return string
	 */
	public abstract function getDefaultValue();

	/**
	 * @return string
	 */
	public abstract function getValue();

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->fields['NAME'];
	}
}