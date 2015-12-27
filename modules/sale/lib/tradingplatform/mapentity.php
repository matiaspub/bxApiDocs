<?php
namespace Bitrix\Sale\TradingPlatform;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class MapEntityTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TRADING_PLATFORM_ID int mandatory
 * <li> CODE string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Sale\TradingPlatform
 **/

class MapEntityTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_map_entity';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_ENTITY_ID_FIELD'),
			),
			'TRADING_PLATFORM_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_ENTITY_TRADING_PLATFORM_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_ENTITY_CODE_FIELD'),
			),
		);
	}

	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
}