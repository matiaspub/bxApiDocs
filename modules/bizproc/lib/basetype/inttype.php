<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\FieldType;

Loc::loadMessages(__FILE__);

/**
 * Class Int
 * @package Bitrix\Bizproc\BaseType
 */
class IntType extends Double
{

	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::INT;
	}

	/**
	 * Normalize single value.
	 *
	 * @param FieldType $fieldType Document field type.
	 * @param mixed $value Field value.
	 * @return mixed Normalized value
	 */
	
	/**
	* <p>Статический метод нормализует одиночное значение.</p>
	*
	*
	* @param mixed $Bitrix  Тип поля документа.
	*
	* @param Bitri $Bizproc  Значение поля.
	*
	* @param FieldType $fieldType  
	*
	* @param mixed $value  
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/inttype/tosinglevalue.php
	* @author Bitrix
	*/
	public static function toSingleValue(FieldType $fieldType, $value)
	{
		if (is_array($value))
		{
			reset($value);
			$value = current($value);
		}
		return $value;
	}

	/**
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return null|int
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = Base::extractValue($fieldType, $field, $request);

		if ($value !== null && is_string($value) && strlen($value) > 0)
		{
			if (\CBPActivity::isExpression($value))
				return $value;

			$value = str_replace(' ', '', $value);
			if (preg_match('#^[0-9\-]+$#', $value))
			{
				$value = (int) $value;
			}
			else
			{
				$value = null;
				static::addError(array(
					'code' => 'ErrorValue',
					'message' => Loc::getMessage('BPDT_INT_INVALID'),
					'parameter' => static::generateControlName($field),
				));
			}
		}
		else
		{
			$value = null;
		}

		return $value;
	}
}