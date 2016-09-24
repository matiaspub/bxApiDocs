<?php

namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CategoryVariationTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CATEGORY_ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> REQUIRED string(1) mandatory
 * <li> MIN_VALUES int mandatory
 * <li> MAX_VALUES int mandatory
 * <li> SELECTION_MODE string(255) mandatory
 * <li> ALLOWED_AS_VARIATION string(1) optional
 * <li> DEPENDENCY_NAME string(255) optional
 * <li> DEPENDENCY_VALUE string(255) optional
 * <li> HELP_URL string(255) optional
 * <li> VALUE text optional
 * </ul>
 *
 * @package Bitrix\Sale\TradingPlatform\Ebay
 **/

class CategoryVariationTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_ebay_cat_var';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_ID_FIELD'),
			),
			'CATEGORY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_CATEGORY_ID_FIELD'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_NAME_FIELD'),
			),
			'REQUIRED' => array(
				'data_type' => 'string',
				'default' => 'N',
				'validation' => array(__CLASS__, 'validateRequired'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_REQUIRED_FIELD'),
			),
			'MIN_VALUES' => array(
				'data_type' => 'integer',
				'default' => 0,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_MIN_VALUES_FIELD'),
			),
			'MAX_VALUES' => array(
				'data_type' => 'integer',
				'default' => 1,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_MAX_VALUES_FIELD'),
			),
			'SELECTION_MODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateSelectionMode'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_SELECTION_MODE_FIELD'),
			),
			'ALLOWED_AS_VARIATION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateAllowedAsVariation'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_ALLOWED_AS_VARIATION_FIELD'),
			),
			'DEPENDENCY_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDependencyName'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_DEPENDENCY_NAME_FIELD'),
			),
			'DEPENDENCY_VALUE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDependencyValue'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_DEPENDENCY_VALUE_FIELD'),
			),
			'HELP_URL' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateHelpUrl'),
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_HELP_URL_FIELD'),
			),
			'VALUE' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_EBAY_VARIATION_METADATA_ENTITY_VALUES_FIELD'),
			),
		);
	}
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateValue()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateRequired()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	public static function validateSelectionMode()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateAllowedAsVariation()
	{
		return array(
			new Entity\Validator\Length(null, 1),
		);
	}
	public static function validateDependencyName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateDependencyValue()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateHelpUrl()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}