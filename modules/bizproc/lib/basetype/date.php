<?php
namespace Bitrix\Bizproc\BaseType;

use Bitrix\Main\Loader;
use Bitrix\Main\Type;
use Bitrix\Main\Localization\Loc;
use Bitrix\Bizproc\FieldType;

Loc::loadMessages(__FILE__);

/**
 * Class Date
 * @package Bitrix\Bizproc\BaseType
 */
class Date extends Base
{
	/**
	 * @return string
	 */
	public static function getType()
	{
		return FieldType::DATE;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/date/tosinglevalue.php
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
			case FieldType::DOUBLE:
			case FieldType::INT:
				$value = $value? (int)strtotime($value) : 0;
				break;
			case FieldType::DATE:
			case FieldType::DATETIME:
			case FieldType::STRING:
			case FieldType::TEXT:
				$value = (string) $value;
				if ($value)
				{
					if ($type == FieldType::DATE)
						$format = \FORMAT_DATE;
					elseif ($type == FieldType::DATETIME)
						$format = \FORMAT_DATETIME;
					else
						$format = static::getType() == FieldType::DATE ? \FORMAT_DATE : \FORMAT_DATETIME;

					if (\CheckDateTime($value, $format))
					{
						$value = date(Type\Date::convertFormatToPhp($format), \MakeTimeStamp($value, $format));
					}
					else
					{
						$value = date(Type\Date::convertFormatToPhp($format), strtotime($value));
					}
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/bizproc/basetype/date/getconversionmap.php
	* @author Bitrix
	*/
	public static function getConversionMap()
	{
		return array(
			array(
				FieldType::DOUBLE,
				FieldType::INT,
				FieldType::DATE,
				FieldType::DATETIME,
				FieldType::STRING,
				FieldType::TEXT
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
		$name = static::generateControlName($field);
		$renderResult = '';

		if ($renderMode & FieldType::RENDER_MODE_MOBILE)
		{
			$renderResult = '<div><input type="hidden" value="'
				.htmlspecialcharsbx($value).'" data-type="'
				.htmlspecialcharsbx(static::getType()).'" name="'.htmlspecialcharsbx($name).'"/>'
				.'<a href="#" onclick="return BX.BizProcMobile.showDatePicker(this, event);">'
				.($value? htmlspecialcharsbx($value) : Loc::getMessage('BPDT_DATE_MOBILE_SELECT')).'</a></div>';
		}
		elseif ($renderMode & FieldType::RENDER_MODE_ADMIN)
		{
			require_once(Loader::getLocal('/modules/main/interface/init_admin.php'));
			$renderResult = \CAdminCalendar::calendarDate($name, $value, 19, static::getType() == FieldType::DATETIME);
		}
		else
		{
			ob_start();
			global $APPLICATION;

			$APPLICATION->includeComponent(
				'bitrix:main.calendar',
				'',
				array(
					'SHOW_INPUT' => 'Y',
					'FORM_NAME' => $field['Form'],
					'INPUT_NAME' => $name,
					'INPUT_VALUE' => $value,
					'SHOW_TIME' => static::getType() == FieldType::DATETIME ? 'Y' : 'N'
				),
				false,
				array('HIDE_ICONS' => 'Y')
			);

			$renderResult = ob_get_contents();
			ob_end_clean();
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
	 * @param FieldType $fieldType
	 * @param array $field
	 * @param array $request
	 * @return null|string
	 */
	protected static function extractValue(FieldType $fieldType, array $field, array $request)
	{
		$value = parent::extractValue($fieldType, $field, $request);

		if ($value !== null && is_string($value) && strlen($value) > 0)
		{
			$format = static::getType() == FieldType::DATETIME ? \FORMAT_DATETIME : \FORMAT_DATE;
			if(!\CheckDateTime($value, $format))
			{
				$value = null;
				static::addError(array(
					'code' => 'ErrorValue',
					'message' => Loc::getMessage('BPDT_DATE_INVALID'),
					'parameter' => static::generateControlName($field),
				));
			}
			else
				$value = \ConvertDateTime($value, $format);
		}
		else
		{
			$value = null;
		}

		return $value;
	}
}