<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Bizproc\FieldType;

/**
 * Class Text
 * @package Bitrix\Bizproc\BaseType
 */
class Text extends StringType
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::TEXT;
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
		$name = static::generateControlName($field);
		$controlId = static::generateControlId($field);
		$renderResult =  '<textarea rows="5" cols="40" id="'.htmlspecialcharsbx($controlId).'" name="'
			.htmlspecialcharsbx($name).'">'.htmlspecialcharsbx((string) $value).'</textarea>';

		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, null, false, '', $fieldType);
		}

		return $renderResult;
	}
}