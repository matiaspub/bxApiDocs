<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Bizproc\FieldType;

/**
 * Class User
 * @package Bitrix\Bizproc\BaseType
 */
class User extends Base
{

	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::USER;
	}

	/**
	 * Get formats list.
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает список форматов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/user/getformats.php
	* @author Bitrix
	*/
	public static function getFormats()
	{
		$formats = parent::getFormats();
		$formats['friendly'] = array(
			'callable' =>'formatValueFriendly',
			'separator' => ', ',
		);
		return $formats;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/user/tosinglevalue.php
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
	 * @param $value
	 * @return string
	 */
	protected static function formatValuePrintable(FieldType $fieldType, $value)
	{
		if (!is_array($value))
			$value = array($value);

		return \CBPHelper::usersArrayToString($value, null, $fieldType->getDocumentType());
	}

	/**
	 * @param FieldType $fieldType
	 * @param $value
	 * @return string
	 */
	protected static function formatValueFriendly(FieldType $fieldType, $value)
	{
		if (!is_array($value))
			$value = array($value);

		return \CBPHelper::usersArrayToString($value, null, $fieldType->getDocumentType(), false);
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
				$value = (string)$value;
				if (strpos($value, 'user_') === 0)
					$value = substr($value, strlen('user_'));
				$value = (int)$value;
				break;
			case FieldType::STRING:
			case FieldType::TEXT:
			case FieldType::USER:
				$value = (string)$value;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/user/getconversionmap.php
	* @author Bitrix
	*/
	public static function getConversionMap()
	{
		return array(
			array(
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
		if ($value !== null && !is_array($value))
			$value = array($value);

		$value = \CBPHelper::usersArrayToString($value, null, $fieldType->getDocumentType());
		$renderResult = parent::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
		$renderResult .= static::renderControlSelector($field, null, false, '', $fieldType);
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
	public static function renderControlSingle(FieldType $fieldType, array $field, $value, $allowSelection, $renderMode)
	{
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
		return static::renderControl($fieldType, $field, $value, $allowSelection, $renderMode);
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return array|null
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = parent::extractValue($fieldType, $field, $request);
		$result = null;

		if (is_string($value) && strlen($value) > 0)
		{
			$errors = array();
			$result = \CBPHelper::usersStringToArray($value, $fieldType->getDocumentType(), $errors);
			if (sizeof($errors) > 0)
			{
				static::addErrors($errors);
			}
		}

		return $result;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return null|string
	 */
	public static function extractValueSingle(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();
		$result = static::extractValue($fieldType, $field, $request);
		return is_array($result)? $result[0] : $result;
	}

	/**
	 * @param FieldType $fieldType Document field type.
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @return array|null
	 */
	public static function extractValueMultiple(FieldType $fieldType, array $field, array $request)
	{
		static::cleanErrors();
		return static::extractValue($fieldType, $field, $request);
	}
}