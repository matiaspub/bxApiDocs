<?php

namespace Bitrix\Iblock\BizprocType;

use Bitrix\Bizproc\BaseType;
use Bitrix\Bizproc\FieldType;
use Bitrix\Main\Localization\Loc;

class UserTypePropertyHtml extends UserTypeProperty
{

	/**
	 * @param FieldType $fieldType Document field object.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class manager name.
	 * @return null|mixed
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];
		if (is_array($value) && isset($value['TEXT']))
			$value = $value['TEXT'];

		return parent::convertTo($fieldType, $value, $toTypeClass);
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];
		if (is_array($value) && isset($value['TEXT']))
			$value = $value['TEXT'];

		return HTMLToTxt(htmlspecialcharsback((string)$value));
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
			case FieldType::BOOL:
			case FieldType::DATE:
			case FieldType::DATETIME:
			case FieldType::DOUBLE:
			case FieldType::INT:
			case FieldType::INTERNALSELECT:
			case FieldType::SELECT:
			case FieldType::STRING:
			case FieldType::TEXT:
			case FieldType::USER:
				$value = array('TYPE' => 'text', 'TEXT' => (string) $value);
				break;
			default:
				$value = null;
		}

		return $value;
	}

	/**
	 * Low-level control rendering method
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param mixed $value
	 * @param bool $allowSelection
	 * @param int $renderMode
	 * @return string - HTML rendering
	 */
	protected static function renderControl(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$name = static::generateControlName($field);
		$controlId = static::generateControlId($field);

		if (is_array($value) && isset($value['VALUE']))
			$value = $value['VALUE'];
		if (is_array($value) && isset($value['TEXT']))
			$value = $value['TEXT'];

		return \CBPViewHelper::getHtmlEditor($controlId, $name, $value);
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
		// need to show at least one control
		if (empty($typeValue))
			$typeValue[] = null;

		$controls = array();

		foreach ($typeValue as $k => $v)
		{
			$singleField = $field;
			$singleField['Index'] = $k;
			$controls[] = static::renderControl(
				$fieldType,
				$singleField,
				$v,
				$allowSelection,
				$renderMode
			);
		}

		$renderResult = static::wrapCloneableControls($controls, static::generateControlName($field));

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, $selectorValue, true);
		}

		return $renderResult;
	}

	/**
	 * @param array $controls
	 * @param string $wrapperId
	 * @return string
	 */
	protected static function wrapCloneableControls(array $controls, $wrapperId)
	{
		$wrapperId = (string) $wrapperId;
		$renderResult = '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="BizprocCloneable_'
			.htmlspecialcharsbx($wrapperId).'">';

		foreach ($controls as $control)
		{
			$renderResult .= '<tr><td>'.$control.'</td></tr>';
		}
		$renderResult .= '</table>';
		$renderResult .= '<input type="button" value="'.Loc::getMessage('BPDT_BASE_ADD')
			.'" onclick="BX.Bizproc.cloneTypeControlHtml(\'BizprocCloneable_'
			.htmlspecialcharsbx($wrapperId).'\', \''.htmlspecialcharsbx($wrapperId).'\')"/><br />';

		return $renderResult;
	}

}