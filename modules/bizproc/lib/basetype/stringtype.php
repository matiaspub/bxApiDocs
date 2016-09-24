<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main;
use Bitrix\Bizproc\FieldType;

/**
 * Class String
 * @package Bitrix\Bizproc\BaseType
 */
class StringType extends Base
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::STRING;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/stringtype/tosinglevalue.php
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
			case FieldType::BOOL:
				$value = strtolower((string)$value);
				$value = in_array($value, array('y', 'yes', 'true', '1')) ? 'Y' : 'N';
				break;
			case FieldType::DATE:
			case FieldType::DATETIME:
				$value = (string) $value;
				if ($value)
				{
					$format = ($type == FieldType::DATE) ? \FORMAT_DATE : \FORMAT_DATETIME;
					if (\CheckDateTime($value, $format))
					{
						$value = date(Main\Type\Date::convertFormatToPhp($format), \MakeTimeStamp($value, $format));
					}
					else
					{
						$value = date(Main\Type\Date::convertFormatToPhp($format), strtotime($value));
					}
				}
				break;
			case FieldType::DOUBLE:
				$value = str_replace(' ', '', str_replace(',', '.', $value));
				$value = (float)$value;
				break;
			case FieldType::INT:
				$value = str_replace(' ', '', $value);
				$value = (int)$value;
				break;
			case FieldType::STRING:
			case FieldType::TEXT:
				$value = (string) $value;
				break;
			case FieldType::USER:
				$value = trim($value);
				if (strpos($value, 'user_') === false
					&& strpos($value, 'group_') === false
					&& !preg_match('#^[0-9]+$#', $value)
				)
				{
					$value = null;
				}
				break;
			default:
				$value = null;
		}

		return $value;
	}

	/**
	 * Return conversion map for current type.
	 * @return array Map.
	 */
	
	/**
	* <p>Статический метод возвращает таблицу преобразования для текущего типа.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/stringtype/getconversionmap.php
	* @author Bitrix
	*/
	public static function getConversionMap()
	{
		return array(
			array(
				FieldType::BOOL,
				FieldType::DATE,
				FieldType::DATETIME,
				FieldType::DOUBLE,
				FieldType::INT,
				FieldType::STRING,
				FieldType::TEXT,
				FieldType::USER
			)
		);
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
		$renderResult = parent::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
		if ($allowSelection)
		{
			$renderResult .= static::renderControlSelector($field, null, false, '', $fieldType);
		}
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
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
		$value = static::toSingleValue($fieldType, $value);
		return static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
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
		if (!is_array($value) || is_array($value) && \CBPHelper::isAssociativeArray($value))
			$value = array($value);

		if (empty($value))
			$value[] = null;

		$controls = array();

		foreach ($value as $k => $v)
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

		return $renderResult;
	}

}