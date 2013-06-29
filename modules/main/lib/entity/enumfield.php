<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for enum data type
 * @package bitrix
 * @subpackage main
 */
class EnumField extends ScalarField
{
	protected $values;

	public function __construct($name, $dataType, Base $entity, $parameters = array())
	{
		parent::__construct($name, $dataType, $entity, $parameters);

		if (empty($parameters['values']))
		{
			throw new \Exception(sprintf(
				'Required parameter "values" for %s field in %s entity not found',
				$this->name, $this->entity->getNamespace().$this->entity->getName()
			));
		}

		if (!is_array($parameters['values']))
		{
			throw new \Exception(sprintf(
				'Parameter "values" for %s field in %s entity should be an array',
				$this->name, $this->entity->getNamespace().$this->entity->getName()
			));
		}

		$this->values = $parameters['values'];
	}

	public function getValidators()
	{
		$validators = parent::getValidators();

		if ($this->validation === null)
		{
			$validators[] = new Validator\Enum;
		}

		return $validators;
	}

	public function getValues()
	{
		return $this->values;
	}
}