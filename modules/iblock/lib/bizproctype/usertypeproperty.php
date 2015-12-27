<?php

namespace Bitrix\Iblock\BizprocType;

use Bitrix\Bizproc\BaseType;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Type;

class UserTypeProperty extends BaseType\Base
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::STRING;
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		$userType = static::getUserType($fieldType);
		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];

		if (!empty($userType['GetPublicViewHTML']))
		{
			$result = call_user_func_array(
				$userType['GetPublicViewHTML'],
				array(
					array('LINK_IBLOCK_ID' => $fieldType->getOptions()),
					array('VALUE' => $value),
					''
				)
			);

			return HTMLToTxt($result);
		}
		return parent::formatValuePrintable($fieldType, $value);
	}

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class manager name.
	 * @return null|mixed
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		if (is_subclass_of($toTypeClass, '\Bitrix\Iblock\BizprocType\UserTypeProperty'))
		{
			return $value;
		}

		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];

		$value = (string) $value;
		return BaseType\String::convertTo($fieldType, $value, $toTypeClass);
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
		$selectorValue = null;
		if (\CBPActivity::isExpression($value))
		{
			$selectorValue = $value;
			$value = null;
		}

		$userType = static::getUserType($fieldType);

		if (!empty($userType['GetPublicEditHTML']))
		{
			if (is_array($value) && isset($value['VALUE']))
				$value = $value['VALUE'];

			$renderResult = call_user_func_array(
				$userType['GetPublicEditHTML'],
				array(
					array('LINK_IBLOCK_ID' => $fieldType->getOptions()),
					array('VALUE' => $value),
					array(
						'FORM_NAME' => $field['Form'],
						'VALUE' => static::generateControlName($field)
					),
					true
				)
			);
		}
		else
			$renderResult = static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, $selectorValue, true);
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
		$selectorValue = null;
		$typeValue = array();
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $v)
		{
			if (\CBPActivity::isExpression($v))
				$selectorValue = $v;
			else
				$typeValue[] = $v;
		}

		$userType = static::getUserType($fieldType);

		if (!empty($userType['GetPublicEditHTMLMulty']))
		{
			foreach ($typeValue as $k => &$fld)
			{
				if (!is_array($fld) || !isset($fld['VALUE']))
					$fld = array('VALUE' => $fld);
				if ($fld['VALUE'] === null)
					unset($typeValue[$k]);
			}
			$typeValue = array_values($typeValue);

			$renderResult = call_user_func_array(
				$userType['GetPublicEditHTMLMulty'],
				array(
					array('LINK_IBLOCK_ID' => $fieldType->getOptions()),
					$typeValue,
					array(
						'FORM_NAME' => $field['Form'],
						'VALUE' => static::generateControlName($field)
					),
					true
				)
			);
		}
		else
		{
			$controls = array();
			// need to show at least one control
			if (empty($typeValue))
				$typeValue[] = null;

			foreach ($typeValue as $k => $v)
			{
				$singleField = $field;
				$singleField['Index'] = $k;
				$controls[] = static::renderControlSingle(
					$fieldType,
					$singleField,
					$v,
					$allowSelection,
					$renderMode
				);
			}

			$renderResult = static::wrapCloneableControls($controls, static::generateControlName($field));
		}

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, $selectorValue, true);
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
		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];

		$userType = static::getUserType($fieldType);

		if (array_key_exists('GetLength', $userType))
		{
			if (call_user_func_array(
					$userType['GetLength'],
					array(
						array('LINK_IBLOCK_ID' => $fieldType->getOptions()),
						array('VALUE' => $value)
					)
				) <= 0)
			{
				$value = null;
			}
		}

		if ($value != null && array_key_exists('CheckFields', $userType))
		{
			$errors = call_user_func_array(
				$userType['CheckFields'],
				array(
					array('LINK_IBLOCK_ID' => $fieldType->getOptions()),
					array('VALUE' => $value)
				)
			);
			if (sizeof($errors) > 0)
			{
				$value = null;
				foreach ($errors as $e)
					static::addError(array(
						'code' => 'ErrorValue',
						'message' => $e,
						'parameter' => static::generateControlName($field),
					));
			}
		}
		elseif ($value === '' && !array_key_exists('GetLength', $userType))
			$value = null;

		return $value;
	}

	protected static function getUserType(FieldType $fieldType)
	{
		return \CIBlockProperty::getUserType(substr($fieldType->getType(), 2));
	}

}