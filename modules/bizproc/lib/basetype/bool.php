<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\FieldType;

Loc::loadMessages(__FILE__);

/**
 * Class Bool
 * @package Bitrix\Bizproc\BaseType
 */
class Bool extends Base
{

	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::BOOL;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		return strtoupper($value) != 'N' && !empty($value)
			? Loc::getMessage('BPDT_BOOL_YES')
			: Loc::getMessage('BPDT_BOOL_NO');
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return null|mixed
	 */
	public static function convertTo(FieldType $fieldType, $value, $toTypeClass)
	{
		/** @var Base $toTypeClass */
		$type = $toTypeClass::getType();
		switch ($type)
		{
			case FieldType::DOUBLE:
			case FieldType::INT:
				$value = (int) ($value == 'Y');
				break;
			case FieldType::STRING:
			case FieldType::TEXT:
				$value = $value == 'Y' ? 'Y' : 'N';
				break;
			default:
				$value = null;
		}

		return $value;
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param mixed $value
	 * @param bool $allowSelection
	 * @param int $renderMode
	 * @return string
	 */
	protected static function renderControl(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$renderResult = '<select id="'.htmlspecialcharsbx(static::generateControlId($field))
				.'" name="'.htmlspecialcharsbx(static::generateControlName($field)).'">';

		if (!$fieldType->isRequired())
			$renderResult .= '<option value="">['.Loc::getMessage("BPDT_BOOL_NOT_SET").']</option>';

		$renderResult .= '<option value="Y"'.($value == "Y" ? ' selected' : '').'>'.Loc::getMessage("BPDT_BOOL_YES").'</option>
				<option value="N"'.($value == "N" ? ' selected' : '').'>'.Loc::getMessage("BPDT_BOOL_NO").'</option>
			</select>';

		return $renderResult;
	}

	/**
	 * @param int $renderMode Control render mode.
	 * @return bool
	 */
	public static function canRenderControl($renderMode)
	{
		return true;
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return null|string
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = parent::extractValue($fieldType, $field, $request);

		if ($value !== null && $value !== 'Y' && $value !== 'N')
		{
			if (is_bool($value))
			{
				$value = $value ? 'Y' : 'N';
			}
			elseif (is_string($value) && strlen($value) > 0)
			{
				$value = strtolower($value);
				if (in_array($value, array('y', 'yes', 'true', '1')))
				{
					$value = 'Y';
				}
				elseif (in_array($value, array('n', 'no', 'false', '0')))
				{
					$value = 'N';
				}
				else
				{
					$value = null;
					static::addError(array(
						'code' => 'ErrorValue',
						'message' => Loc::getMessage('BPDT_BOOL_INVALID'),
						'parameter' => static::generateControlName($field),
					));
				}
			}
			else
			{
				$value = null;
			}
		}

		return $value;
	}
}