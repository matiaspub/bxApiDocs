<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\FieldType;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 * @package Bitrix\Bizproc\BaseType
 */
class Base
{

	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::STRING;
	}

	/** @var array $formats	 */
	protected static $formats = array(
		'printable' => 	array(
			'callable' =>'formatValuePrintable',
			'separator' => ', ',
		)
	);

	/**
	 * @param string $format
	 * @return callable|null
	 */
	protected static function getFormatCallable($format)
	{
		$format = strtolower($format);
		if (isset(static::$formats[$format]['callable']))
		{
			$callable = static::$formats[$format]['callable'];
			if (is_string($callable))
			{
				$callable = array(get_called_class(), $callable);
			}

			return $callable;
		}
		return null;
	}

	/**
	 * @param string $format
	 * @return string
	 */
	protected static function getFormatSeparator($format)
	{
		$format = strtolower($format);
		$separator = ', '; //default - coma
		if (isset(static::$formats[$format]['separator']))
		{
			$separator = static::$formats[$format]['separator'];
		}
		return $separator;
	}

	/**
	 * @param string $name Format name.
	 * @param array $options Format options.
	 * @throws Main\ArgumentException
	 * @return void
	 */
	public static function addFormat($name, array $options)
	{
		$name = strtolower($name);
		if (empty($options['callable']))
			throw new Main\ArgumentException('Callable property in format options is not set.');

		static::$formats[$name] = $options;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $format Format name.
	 * @return string
	 */
	public static function formatValueMultiple(FieldType $fieldType, $value, $format = 'printable')
	{
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $k => $v)
		{
			$value[$k] = static::formatValueSingle($fieldType, $v, $format);
		}

		return implode(static::getFormatSeparator($format), $value);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $format Format name.
	 * @return mixed|null
	 */
	public static function formatValueSingle(FieldType $fieldType, $value, $format = 'printable')
	{
		$callable = static::getFormatCallable($format);

		if (is_callable($callable))
		{
			return call_user_func($callable, $fieldType, $value);
		}
		//return original value if format not found
		return $value;
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		return static::convertValueSingle($fieldType, $value, '\Bitrix\Bizproc\BaseType\String');
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return array
	 */
	public static function convertValueMultiple(FieldType $fieldType, $value, $toTypeClass)
	{
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $k => $v)
		{
			$value[$k] = static::convertValueSingle($fieldType, $v, $toTypeClass);
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return bool|int|float|string
	 */
	public static function convertValueSingle(FieldType $fieldType, $value, $toTypeClass)
	{
		/** @var Base $toTypeClass */

		if (ltrim(get_called_class(), '\\') === ltrim($toTypeClass, '\\'))
			return $value;

		$result = static::convertTo($fieldType, $value, $toTypeClass);
		if ($result === null)
			$result = $toTypeClass::convertFrom($fieldType, $value, get_called_class());

		if ($result !== null)
			$fieldType->setTypeClass($toTypeClass);

		return $result !== null? $result : $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return null
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		return null;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $fromTypeClass Type class name.
	 * @return null
	 */
	public static function convertFrom(FieldType $fieldType, $value, $fromTypeClass)
	{
		return null;
	}

	/**
	 * @var array
	 */
	protected static $errors = array();

	/**
	 * @param mixed $error Error description.
	 */
	public static function addError($error)
	{
		static::$errors[] = $error;
	}

	/**
	 * @param array $errors Errors description.
	 * @return void
	 */
	public static function addErrors(array $errors)
	{
		static::$errors = array_merge(static::$errors, $errors);
	}

	/**
	 * @return array
	 */
	public static function getErrors()
	{
		return static::$errors;
	}

	/**
	 * Clean errors
	 */
	protected static function cleanErrors()
	{
		static::$errors = array();
	}

	/**
	 * @param array $field
	 * @return string
	 */
	protected static function generateControlId(array $field)
	{
		$id = 'id_'.$field['Field'];
		$index = isset($field['Index']) ? $field['Index'] : null;
		if ($index !== null)
		{
			$id .= '__n'.$index.'_';
		}
		return $id;
	}

	/**
	 * @param array $field
	 * @return string
	 */
	protected static function generateControlName(array $field)
	{
		$name = $field['Field'];
		$index = isset($field['Index']) ? $field['Index'] : null;
		if ($index !== null)
		{
			$name .= '[n'.$index.']';
		}
		return $name;
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
			.'" onclick="BX.Bizproc.cloneTypeControl(\'BizprocCloneable_'
			.htmlspecialcharsbx($wrapperId).'\')"/><br />';

		return $renderResult;
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
		// example: control rendering
		return '<input type="text" size="40" id="'.htmlspecialcharsbx($controlId).'" name="'
			.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx((string) $value).'">';
	}

	/**
	 * @param int $renderMode Control render mode.
	 * @return bool
	 */
	public static function canRenderControl($renderMode)
	{
		if ($renderMode & FieldType::RENDER_MODE_MOBILE)
			return false;

		return true;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
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
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
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
	 * @param array $field
	 * @param null|string $value
	 * @param bool $showInput
	 * @param string $selectorMode
	 * @return string
	 */
	protected static function renderControlSelector(array $field, $value = null, $showInput = false, $selectorMode = '')
	{
		$html = '';
		$controlId = static::generateControlId($field);
		if ($showInput)
		{
			$controlId = $controlId.'_text';
			$name = static::generateControlName($field).'_text';
			$html = '<input type="text" id="'.htmlspecialcharsbx($controlId).'" name="'
					.htmlspecialcharsbx($name).'" value="'.htmlspecialcharsbx((string)$value).'">';
		}
		$html .= '<input type="button" value="..." onclick="BPAShowSelector(\''
			.htmlspecialcharsbx($controlId).'\', \''.htmlspecialcharsbx(static::getType()).'\''
			.($selectorMode ? ', \''.htmlspecialcharsbx($selectorMode).'\'' : '').');">';

		return $html;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param string $callbackFunctionName Client callback function name.
	 * @param mixed $value Field value.
	 * @return string
	 */
	public static function renderControlOptions(FieldType $fieldType, $callbackFunctionName, $value)
	{
		return '';
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return null|mixed
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$name = $field['Field'];
		$value = isset($request[$name]) ? $request[$name] : null;
		$fieldIndex = isset($field['Index']) ? $field['Index'] : null;
		if (is_array($value) && !\CBPHelper::isAssociativeArray($value))
		{
			if ($fieldIndex !== null)
				$value = isset($value[$fieldIndex]) ? $value[$fieldIndex] : null;
			else
			{
				reset($value);
				$value = current($value);
			}
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return mixed|null
	 */
	public static function extractValueSingle(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();
		$result = static::extractValue($fieldType, $field, $request);
		if ($result === null || $result === '')
		{
			$nameText = $field['Field'].'_text';
			$text = isset($request[$nameText]) ? $request[$nameText] : null;
			if (\CBPActivity::isExpression($text))
				$result = $text;
		}
		return $result;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return array
	 */
	public static function extractValueMultiple(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();

		$name = $field['Field'];
		$value = isset($request[$name]) ? $request[$name] : array();

		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $k => $v)
		{
			$field['Index'] = $k;
			$result = static::extractValue($fieldType, $field, $request);
			if ($result === null || $result === '')
			{
				unset($value[$k]);
			}
			else
				$value[$k] = $result;
		}

		//apppend selector value
		$nameText = $field['Field'].'_text';
		$text = isset($request[$nameText]) ? $request[$nameText] : null;
		if (\CBPActivity::isExpression($text))
			$value[] = $text;

		return array_values($value);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return void
	 */
	public static function clearValueSingle(FieldType $fieldType, $value)
	{
		//Method fires when workflow was complete
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return void
	 */
	public static function clearValueMultiple(FieldType $fieldType, $value)
	{
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		foreach ($value as $v)
		{
			static::clearValueSingle($fieldType, $v);
		}
	}
}