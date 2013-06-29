<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for boolean data type
 * @package bitrix
 * @subpackage main
 */
class BooleanField extends ScalarField
{
	/**
	 * Value (true, false) equivalent map
	 * @var array
	 */
	protected $values;

	public function __construct($name, $dataType, Base $entity, $parameters = array())
	{
		parent::__construct($name, $dataType, $entity, $parameters);

		if (empty($parameters['values']))
		{
			$this->values = array(false, true);
		}
		else
		{
			$this->values = $parameters['values'];
		}
	}


	/**
	 * Convert true/false values to actual field values
	 * @param boolean|integer|string $value
	 * @return mixed
	 */
	public function normalizeValue($value)
	{
		if (
			(is_string($value) && ($value == '1' || $value == '0'))
			||
			(is_bool($value))
		)
		{
			$value = (int) $value;
		}
		elseif (is_string($value) && $value == 'true')
		{
			$value = 1;
		}
		elseif (is_string($value) && $value == 'false')
		{
			$value = 0;
		}

		if (is_integer($value) && ($value == 1 || $value == 0))
		{
			$value = $this->values[$value];
		}

		return $value;
	}

	public function getValues()
	{
		return $this->values;
	}
}
