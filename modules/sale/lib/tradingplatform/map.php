<?php
namespace Bitrix\Sale\TradingPlatform;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class MapTable
 * Maps external and internal things.
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENTITY_ID int mandatory
 * <li> VALUE_EXTERNAL string(255) mandatory
 * <li> VALUE_INTERNAL string(255) mandatory
 * </ul>
 *
 * @package Bitrix\Sale\TradingPlatform
 **/

class MapTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_tp_map';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_ID_FIELD'),
			),
			'ENTITY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_ENTITY_ID_FIELD'),
			),
			'VALUE_EXTERNAL' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateValueExternal'),
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_VALUE_EXTERNAL_FIELD'),
			),
			'VALUE_INTERNAL' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateValueInternal'),
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_VALUE_INTERNAL_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('TRADING_PLATFORM_MAP_ENTITY_PARAMS_FIELD'),
			)
		);
	}

	public static function validateValueExternal()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateValueInternal()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Deletes all records with mapEntityId.
	 * @param string $mapEntityId Map entity id.
	 */
	public static function deleteByMapEntityId($mapEntityId)
	{
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();
		$tableName = self::getTableName();

		$strSql =
			"DELETE FROM ".$tableName." ".
			"WHERE ENTITY_ID=".$sqlHelper->forSql($mapEntityId);

		$con->queryExecute($strSql);
	}
}