<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Delivery;

use Bitrix\Sale;

final class DeliveryLocationTable extends Sale\Location\Connector
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_delivery2location';
	}

	static public function getLinkField()
	{
		return 'DELIVERY_ID';
	}

	public static function getLocationLinkField()
	{
		return 'LOCATION_CODE';
	}

	static public function getTargetEntityName()
	{
		return 'Bitrix\Sale\Delivery\Delivery';
	}

	public static function getMap()
	{
		return array(
			
			'DELIVERY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'primary' => true
			),
			'LOCATION_CODE' => array(
				'data_type' => 'string',
				'required' => true,
				'primary' => true
			),
			'LOCATION_TYPE' => array(
				'data_type' => 'string',
				'default' => self::DB_LOCATION_FLAG,
				'required' => true,
				'primary' => true
			),

			// virtual
			'LOCATION' => array(
				'data_type' => '\Bitrix\Sale\Location\Location',
				'reference' => array(
					'=this.LOCATION_CODE' => 'ref.CODE',
					'=this.LOCATION_TYPE' => array('?', self::DB_LOCATION_FLAG)
				)
			),
			'GROUP' => array(
				'data_type' => '\Bitrix\Sale\Location\Group',
				'reference' => array(
					'=this.LOCATION_CODE' => 'ref.CODE',
					'=this.LOCATION_TYPE' => array('?', self::DB_GROUP_FLAG)
				)
			),

			'DELIVERY' => array(
				'data_type' => static::getTargetEntityName(),
				'reference' => array(
					'=this.DELIVERY_ID' => 'ref.ID'
				)
			),
		);
	}
}
