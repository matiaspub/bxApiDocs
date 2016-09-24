<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for enum data type
 * @package bitrix
 * @subpackage main
 */
class FloatField extends ScalarField
{
	/** @var int|null */
	protected $scale;

	public function __construct($name, $parameters = array())
	{
		parent::__construct($name, $parameters);

		if(isset($parameters['scale']))
		{
			$this->scale = intval($parameters['scale']);
		}
	}

	/**
	 * @return int|null
	 */
	public function getScale()
	{
		return $this->scale;
	}
}