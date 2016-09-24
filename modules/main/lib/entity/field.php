<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

/**
 * Base entity field class
 * @package bitrix
 * @subpackage main
 */
abstract class Field
{
	/** @var string */
	protected $name;

	/** @var string */
	protected $dataType;

	/** @var array */
	protected $initialParameters;

	/** @var string */
	protected $title;

	/** @var null|callback */
	protected $validation = null;

	/** @var null|callback[]|Validator\Base[] */
	protected $validators = null;

	/** @var array|callback[]|Validator\Base[] */
	protected $additionalValidators = array();

	/** @var null|callback */
	protected $fetchDataModification = null;

	/** @var null|callback[] */
	protected $fetchDataModifiers;

	/** @var null|callback[] */
	protected $additionalFetchDataModifiers = array();

	/** @var null|callback */
	protected $saveDataModification = null;

	/** @var null|callback[] */
	protected $saveDataModifiers;

	/** @var null|callback[] */
	protected $additionalSaveDataModifiers = array();

	/** @var bool */
	protected $isSerialized = false;

	/** @var Field */
	protected $parentField;

	/** @var Base */
	protected $entity;

	/**
	 * @deprecated
	 * @var array
	 */
	protected static $oldDataTypes = array(
		'float' => 'Bitrix\Main\Entity\FloatField',
		'string' => 'Bitrix\Main\Entity\StringField',
		'text' => 'Bitrix\Main\Entity\TextField',
		'datetime' => 'Bitrix\Main\Entity\DatetimeField',
		'date' => 'Bitrix\Main\Entity\DateField',
		'integer' => 'Bitrix\Main\Entity\IntegerField',
		'enum' => 'Bitrix\Main\Entity\EnumField',
		'boolean' => 'Bitrix\Main\Entity\BooleanField'
	);

	/**
	 * @param string      $name
	 * @param array       $parameters
	 * @throws \Exception
	 */
	public function __construct($name, $parameters = array())
	{
		if (!strlen($name))
		{
			throw new SystemException('Field name required');
		}

		$this->name = $name;
		$this->dataType = null;
		$this->initialParameters = $parameters;

		if (isset($parameters['title']))
		{
			$this->title = $parameters['title'];
		}

		// validation
		if (isset($parameters['validation']))
		{
			$this->validation = $parameters['validation'];
		}

		// fetch data modifiers
		if (isset($parameters['fetch_data_modification']))
		{
			$this->fetchDataModification = $parameters['fetch_data_modification'];
		}

		// save data modifiers
		if (isset($parameters['save_data_modification']))
		{
			$this->saveDataModification = $parameters['save_data_modification'];
		}

		if (!empty($parameters['serialized']))
		{
			$this->setSerialized();
		}
	}

	public function setEntity(Base $entity)
	{
		if ($this->entity !== null)
		{
			throw new SystemException(sprintf('Field "%s" already has entity', $this->name));
		}

		$this->entity = $entity;
	}

	public function resetEntity()
	{
		$this->entity = null;
	}

	/**
	 * @param        $value
	 * @param        $primary
	 * @param        $row
	 * @param Result $result
	 *
	 * @return Result
	 * @throws SystemException
	 */
	public function validateValue($value, $primary, $row, Result $result)
	{
		if ($value instanceof SqlExpression)
		{
			return $result;
		}

		$validators = $this->getValidators();

		foreach ($validators as $validator)
		{
			if ($validator instanceof IValidator)
			{
				$vResult = $validator->validate($value, $primary, $row, $this);
			}
			else
			{
				$vResult = call_user_func_array($validator, array($value, $primary, $row, $this));
			}

			if ($vResult !== true)
			{
				if ($vResult instanceof EntityError)
				{
					$result->addError($vResult);
				}
				else
				{
					$result->addError(new FieldError($this, $vResult, FieldError::INVALID_VALUE));
				}
			}
		}

		return $result;
	}

	public function modifyValueBeforeSave($value, $data)
	{
		$modifiers = $this->getSaveDataModifiers();

		foreach ($modifiers as $modifier)
		{
			$value = call_user_func_array($modifier, array($value, $data));
		}

		return $value;
	}

	/**
	 * @return callback[]|Validator\Base[]
	 * @throws \Exception
	 */
	public function getValidators()
	{
		if ($this->validators === null)
		{
			$this->validators = array();

			if ($this->validation !== null)
			{
				$validators = call_user_func($this->validation);

				if (!is_array($validators))
				{
					throw new SystemException(sprintf(
						'Validation for %s field of %s entity should return array of validators',
						$this->name, $this->entity->getDataClass()
					));
				}

				foreach ($validators as $validator)
				{
					$this->appendValidator($validator);
				}
			}

			foreach ($this->additionalValidators as $validator)
			{
				$this->appendValidator($validator);
			}
		}

		return $this->validators;
	}

	/**
	 * @param Validator\Base|callable $validator
	 */
	public function addValidator($validator)
	{
		// append only when not null. and when is null - delay it
		if ($this->validators === null)
		{
			$this->additionalValidators[] = $validator;
		}
		else
		{
			$this->appendValidator($validator);
		}
	}

	/**
	 * @param Validator\Base|callable $validator
	 * @throws \Exception
	 */
	protected function appendValidator($validator)
	{
		if (!($validator instanceof Validator\Base) && !is_callable($validator))
		{
			throw new SystemException(sprintf(
				'Validators of "%s" field of "%s" entity should be a Validator\Base or callback',
				$this->name, $this->entity->getDataClass()
			));
		}

		$this->validators[] = $validator;
	}

	public function getFetchDataModifiers()
	{
		if ($this->fetchDataModifiers === null)
		{
			$this->fetchDataModifiers = array();

			if ($this->fetchDataModification !== null)
			{
				$modifiers = call_user_func($this->fetchDataModification);

				if (!is_array($modifiers))
				{
					throw new SystemException(sprintf(
						'Fetch Data Modification for %s field of %s entity should return array of modifiers (callbacks)',
						$this->name, $this->entity->getDataClass()
					));
				}

				foreach ($modifiers as $modifier)
				{
					$this->appendFetchDataModifier($modifier);
				}
			}

			foreach ($this->additionalFetchDataModifiers as $modifier)
			{
				$this->appendFetchDataModifier($modifier);
			}
		}

		return $this->fetchDataModifiers;
	}

	public function addFetchDataModifier($modifier)
	{
		// append only when not null. and when is null - delay it
		if ($this->fetchDataModifiers === null)
		{
			$this->additionalFetchDataModifiers[] = $modifier;
		}
		else
		{
			$this->appendFetchDataModifier($modifier);
		}
	}

	protected function appendFetchDataModifier($modifier)
	{
		if (!is_callable($modifier))
		{
			throw new SystemException(sprintf(
				'Modifier of "%s" field of "%s" entity should be a callback',
				$this->name, $this->entity->getDataClass()
			));
		}

		$this->fetchDataModifiers[] = $modifier;
	}

	public function getSaveDataModifiers()
	{
		if ($this->saveDataModifiers === null)
		{
			$this->saveDataModifiers = array();

			if ($this->saveDataModification !== null)
			{
				$modifiers = call_user_func($this->saveDataModification);

				if (!is_array($modifiers))
				{
					throw new SystemException(sprintf(
						'Save Data Modification for %s field of %s entity should return array of modifiers (callbacks)',
						$this->name, $this->entity->getDataClass()
					));
				}

				foreach ($modifiers as $modifier)
				{
					$this->appendSaveDataModifier($modifier);
				}
			}

			foreach ($this->additionalSaveDataModifiers as $modifier)
			{
				$this->appendSaveDataModifier($modifier);
			}
		}

		return $this->saveDataModifiers;
	}

	public function addSaveDataModifier($modifier)
	{
		// append only when not null. and when is null - delay it
		if ($this->saveDataModifiers === null)
		{
			$this->additionalSaveDataModifiers[] = $modifier;
		}
		else
		{
			$this->appendSaveDataModifier($modifier);
		}
	}

	protected function appendSaveDataModifier($modifier)
	{
		if (!is_callable($modifier))
		{
			throw new SystemException(sprintf(
				'Save modifier of "%s" field of "%s" entity should be a callback',
				$this->name, $this->entity->getDataClass()
			));
		}

		$this->saveDataModifiers[] = $modifier;
	}

	/**
	 * @return boolean
	 */
	public function isSerialized()
	{
		return !empty($this->isSerialized);
	}

	public function setSerialized()
	{
		if (!$this->isSerialized)
		{
			$this->isSerialized = true;

			// add save- and fetch modifiers
			$this->addSaveDataModifier(array($this, 'serialize'));
			$this->addFetchDataModifier(array($this, 'unserialize'));
		}
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getTitle()
	{
		if($this->title !== null)
		{
			return $this->title;
		}

		if(($title = Loc::getMessage($this->getLangCode())) <> '')
		{
			return $this->title = $title;
		}

		return $this->title = $this->name;
	}

	/**
	 * @param \Bitrix\Main\Entity\Field $parentField
	 */
	public function setParentField(Field $parentField)
	{
		$this->parentField = $parentField;
	}

	/**
	 * @return \Bitrix\Main\Entity\Field
	 */
	public function getParentField()
	{
		return $this->parentField;
	}

	/**
	 * @deprecated
	 * @return null|string
	 */
	public function getDataType()
	{
		if (empty($this->dataType))
		{
			return static::getOldDataTypeByField($this);
		}

		return $this->dataType;
	}

	/**
	 * @deprecated
	 * @param $class
	 *
	 * @return bool
	 */
	public static function getOldDataTypeByClass($class)
	{
		$map = array_flip(static::$oldDataTypes);

		return isset($map[$class]) ? $map[$class] : 'string';
	}

	/**
	 * @deprecated
	 * @param Field $field
	 *
	 * @return bool
	 */
	public static function getOldDataTypeByField(Field $field)
	{
		return static::getOldDataTypeByClass(get_class($field));
	}

	/**
	 * @deprecated
	 * @param $dateType
	 *
	 * @return bool
	 */
	public static function getClassByOldDataType($dateType)
	{
		return isset(static::$oldDataTypes[$dateType]) ? '\\'.static::$oldDataTypes[$dateType] : false;
	}

	public function getEntity()
	{
		return $this->entity;
	}

	public function getLangCode()
	{
		$entity = $this->getEntity();
		if($entity !== null)
		{
			return $entity->getLangCode().'_'.$this->getName().'_FIELD';
		}
		return null;
	}

	static public function serialize($value)
	{
		return serialize($value);
	}

	static public function unserialize($value)
	{
		return unserialize($value);
	}
}
