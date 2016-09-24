<?php

namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services;

Loc::loadMessages(__FILE__);

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CODE string(50) optional
 * <li> PARENT_ID int optional
 * <li> NAME string(255) mandatory
 * <li> ACTIVE string(1) mandatory
 * <li> DESCRIPTION string(255) optional
 * <li> SORT int mandatory
 * <li> LOGOTIP int optional
 * <li> CONFIG string mandatory
 * <li> CURRENCY string(3) mandatory
 * <li> STORE string(255) optional
 * <li> CLASS_NAME string(255) optional
 * </ul>
 *
 * @package Bitrix\Sale\Delivery *
 **/

class Table extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_delivery_srv';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_ID_FIELD'),
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_CODE_FIELD'),
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_PARENT_ID_FIELD'),
			),
			'PARENT' => array(
				'data_type' => '\Bitrix\Sale\Delivery\Services\Table',
				'reference' => array(
					'=this.PARENT_ID' => 'ref.ID'
				)
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_NAME_FIELD'),
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_ACTIVE_FIELD'),
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_DESCRIPTION_FIELD'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'default' => 100,
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_SORT_FIELD'),
			),
			'LOGOTIP' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_LOGOTIP_FIELD'),
			),
			'CONFIG' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_CONFIG_FIELD'),
			),
			'CLASS_NAME' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateClassName'),
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_CLASS_NAME_FIELD'),
			),
			'CURRENCY' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCurrency'),
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_CURRENCY_FIELD'),
			),
			'TRACKING_PARAMS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_TRACKING_PARAMS_FIELD'),
			),
			'ALLOW_EDIT_SHIPMENT' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'default' => 'Y',
				'title' => Loc::getMessage('DELIVERY_SERVICE_ENTITY_ALLOW_EDIT_SHIPMENT_FIELD')
			)
		);
	}

	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}
	public static function validateName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}
	public static function validateCurrency()
	{
		return array(
			new Entity\Validator\Length(null, 3),
		);
	}
	public static function validateClassName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/* Deprecated methods moved to manager. Will be removed in future versions. */

	/**
	 * @deprecated use Services\Manager::getIdByCode()
	 */
	public static function getIdByCode($code)
	{
		return Services\Manager::getIdByCode($code);
	}

	/**
	 * @deprecated use Services\Manager::getCodeById()
	 */
	public static function getCodeById($id)
	{
		return Services\Manager::getCodeById($id);
	}

	/**
	 * @param mixed $primary
	 * @return Entity\DeleteResult
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		if ($primary == EmptyDeliveryService::getEmptyDeliveryServiceId())
		{
			$cacheManager = Application::getInstance()->getManagedCache();
			$cacheManager->clean(EmptyDeliveryService::CACHE_ID);
		}

		return parent::delete($primary);
	}
}
