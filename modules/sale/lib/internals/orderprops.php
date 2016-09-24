<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Internals;

use	Bitrix\Main\Entity\DataManager,
	Bitrix\Main\Entity\Validator,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderPropsTable extends DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_order_props';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'primary' => true,
				'autocomplete' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'PERSON_TYPE_ID' => array(
				'required' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'NAME' => array(
				'required' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getNameValidators'),
				'title' => Loc::getMessage('ORDER_PROPS_ENTITY_NAME_FIELD'),
			),
			'TYPE' => array(
				'required' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getTypeValidators'),
			),
			'REQUIRED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'save_data_modification'  => array(__CLASS__, 'getRequiredSaveModifiers'),
			),
			'DEFAULT_VALUE' => array(
				'data_type' => 'string',
				'validation'              => array(__CLASS__, 'getValueValidators'),
				'save_data_modification'  => array(__CLASS__, 'getValueSaveModifiers'),
				'fetch_data_modification' => array(__CLASS__, 'getValueFetchModifiers'),
				'title' => Loc::getMessage('ORDER_PROPS_ENTITY_DEFAULT_VALUE_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
				'title' => Loc::getMessage('ORDER_PROPS_ENTITY_SORT_FIELD'),
			),
			'USER_PROPS' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_LOCATION' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'PROPS_GROUP_ID' => array(
				'required' => true,
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getDescriptionValidators'),
				'title' => Loc::getMessage('ORDER_PROPS_ENTITY_DESCRIPTION_FIELD'),
			),
			'IS_EMAIL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_PROFILE_NAME' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_PAYER' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_LOCATION4TAX' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_FILTERED' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'save_data_modification' => array(__CLASS__, 'getFilteredSaveModifiers'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'getCodeValidators'),
				'title' => Loc::getMessage('ORDER_PROPS_ENTITY_CODE_FIELD'),
			),
			'IS_ZIP' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_PHONE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'IS_ADDRESS' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'UTIL' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'INPUT_FIELD_LOCATION' => array(
				'data_type' => 'integer',
				'format' => '/^[0-9]{1,11}$/',
			),
			'MULTIPLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
			),
			'SETTINGS' => array(
				'data_type' => 'string',
				'validation'              => array(__CLASS__, 'getSettingsValidators'),
				'save_data_modification'  => array(__CLASS__, 'getSettingsSaveModifiers'),
				'fetch_data_modification' => array(__CLASS__, 'getSettingsFetchModifiers'),
			),

			'GROUP' => array(
				'data_type' => 'Bitrix\Sale\Internals\OrderPropsGroupTable',
				'reference' => array('=this.PROPS_GROUP_ID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
			'PERSON_TYPE' => array(
				'data_type' => 'Bitrix\Sale\Internals\PersonTypeTable',
				'reference' => array('=this.PERSON_TYPE_ID' => 'ref.ID'),
				'join_type' => 'LEFT',
			),
		);
	}

	// value

	public static function getValueValidators()
	{
		return array(array(__CLASS__, 'validateValue'));
	}
	public static function validateValue($value, $primary, array $row, $field)
	{
		$maxlength = 500;
		$length = strlen(self::modifyValueForSave($value, $row));
		return $length > $maxlength
			? Loc::getMessage('SALE_ORDER_PROPS_DEFAULT_ERROR', array('#LENGTH#' => $length, '#MAXLENGTH#' => $maxlength))
			: true;
	}

	public static function getValueSaveModifiers()
	{
		return array(array(__CLASS__, 'modifyValueForSave'));
	}
	public static function modifyValueForSave($value)
	{
		return is_array($value) ? serialize($value) : $value;
	}

	public static function getValueFetchModifiers()
	{
		return array(array(__CLASS__, 'modifyValueForFetch'));
	}
	public static function modifyValueForFetch($value, $query, $property, $alias)
	{
		if (strlen($value))
		{
			if (CheckSerializedData($value)
				&& ($v = @unserialize($value)) !== false)
				//&& is_array($v)) TODO uncomment after a while)
			{
				$value = $v;
			}
			elseif ($property['MULTIPLE'] == 'Y') // compatibility
			{
				switch ($property['TYPE'])
				{
					case 'ENUM': $value = explode(',', $value); break;
					case 'FILE': $value = explode(', ', $value); break;
				}
			}
		}

		return $value;
	}

	// filtered

	public static function getFilteredSaveModifiers()
	{
		return array(array(__CLASS__, 'modifyFilteredForSave'));
	}
	public static function modifyFilteredForSave($value, array $data)
	{
		return $data['MULTIPLE'] == 'Y' ? 'N' : $value;
	}

	// settings

	public static function getSettingsValidators()
	{
		return array(array(__CLASS__, 'validateSettings'));
	}
	public static function validateSettings($value)
	{
		$maxlength = 500;
		$length = strlen(self::modifySettingsForSave($value));
		return $length > $maxlength
			? Loc::getMessage('SALE_ORDER_PROPS_SETTINGS_ERROR', array('#LENGTH#' => $length, '#MAXLENGTH#' => $maxlength))
			: true;
	}

	public static function getSettingsSaveModifiers()
	{
		return array(array(__CLASS__, 'modifySettingsForSave'));
	}
	public static function modifySettingsForSave($value)
	{
		return serialize($value);
	}

	public static function getSettingsFetchModifiers()
	{
		return array(array(__CLASS__, 'modifySettingsForFetch'));
	}
	public static function modifySettingsForFetch($value)
	{
		$v = @unserialize($value);
		return is_array($v) ? $v : array();
	}

	// required

	public static function getRequiredSaveModifiers()
	{
		return array(array(__CLASS__, 'modifyRequiredForSave'));
	}
	public static function modifyRequiredForSave ($value, array $property)
	{
		return ($value == 'Y'
			|| $property['IS_PROFILE_NAME'] == 'Y'
			|| $property['IS_LOCATION'    ] == 'Y'
			|| $property['IS_LOCATION4TAX'] == 'Y'
			|| $property['IS_PAYER'       ] == 'Y'
			|| $property['IS_ZIP'         ] == 'Y') ? 'Y' : 'N';
	}

	// validators

	public static function getNameValidators()
	{
		return array(new Validator\Length(1, 255));
	}

	public static function getTypeValidators()
	{
		return array(new Validator\Length(1, 20));
	}

	public static function getDescriptionValidators()
	{
		return array(new Validator\Length(null, 255));
	}

	public static function getCodeValidators()
	{
		return array(new Validator\Length(null, 50));
	}
}
