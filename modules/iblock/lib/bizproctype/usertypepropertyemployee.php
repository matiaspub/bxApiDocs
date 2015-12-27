<?php

namespace Bitrix\Iblock\BizprocType;

use Bitrix\Bizproc\BaseType;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Config;

class UserTypePropertyEmployee extends UserTypeProperty
{
	private static $isCompatible;

	/**
	 * @return string
	 */
	public static function getType()
	{
		if (!static::isCompatibleMode())
			return FieldType::USER;
		return FieldType::STRING;
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param mixed $value Field value.
	 * @param string $fromTypeClass Type class manager name.
	 * @return null
	 */
	public static function convertFrom(FieldType $fieldType, $value, $fromTypeClass)
	{
		if ($value === null)
			return null;

		/** @var BaseType\Base $fromTypeClass */
		$type = $fromTypeClass::getType();
		switch ($type)
		{
			case FieldType::DOUBLE:
			case FieldType::INT:
				$value = 'user_'.(int)$value;
				break;
			case FieldType::INTERNALSELECT:
			case FieldType::SELECT:
			case FieldType::STRING:
			case FieldType::TEXT:
			case FieldType::USER:
				if (strpos($value, 'user_') === false)
				{
					$value = null;
				}
				elseif (static::isCompatibleMode())
				{
					$value = \CBPHelper::stripUserPrefix($value);
				}
				break;
			default:
				$value = null;
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$value =  static::fixUserPrefix($value);
		$renderResult = parent::renderControlSingle($fieldType, $field, $value, false, $renderMode);

		if ($allowSelection)
		{
			$selectorValue = \CBPActivity::isExpression($value) ? $value : null;
			$renderResult .= static::renderControlSelector($field, $selectorValue, true, 'employee');
		}

		return $renderResult;
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param array $field Form field information.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlMultiple(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$value =  static::fixUserPrefix($value);
		$renderResult = parent::renderControlMultiple($fieldType, $field, $value, false, $renderMode);

		if ($allowSelection)
		{
			$selectorValue = null;
			if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
				$value = array($value);

			foreach ($value as $v)
			{
				if (\CBPActivity::isExpression($v))
					$selectorValue = $v;
			}
			$renderResult .= static::renderControlSelector($field, $selectorValue, true, 'employee');
		}

		return $renderResult;
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return null|mixed
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = parent::extractValue($fieldType, $field, $request);

		if ($value !== null && !static::isCompatibleMode())
		{
			$value = "user_".$value;
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		$value = static::fixUserPrefix($value);
		return parent::formatValuePrintable($fieldType, $value);
	}

	private static function fixUserPrefix($value)
	{
		if (!static::isCompatibleMode())
		{
			if (is_array($value) && isset($value['VALUE']))
				$value['VALUE'] = \CBPHelper::stripUserPrefix($value['VALUE']);
			else
				$value = \CBPHelper::stripUserPrefix($value);
		}

		return $value;
	}

	private static function isCompatibleMode()
	{
		if (static::$isCompatible === null)
		{
			static::$isCompatible = Config\Option::get('bizproc', 'employee_compatible_mode', 'N') == 'Y';
		}
		return static::$isCompatible;
	}
}