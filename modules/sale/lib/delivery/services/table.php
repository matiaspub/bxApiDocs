<?php

namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

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

	public static function getIdByCode($code)
	{
		$result = self::getIdCodeCached($code, "code");

		if($result !== false)
			return $result;

		$res = self::getList(array(
			'filter' => array(
				'=CODE' => $code
			),
		    'select' => array("ID")
		));

		if($handler = $res->fetch())
		{
			self::setIdCodeCached($handler["ID"], $code);
			return $handler["ID"];
		}

		return false;
	}

	public static function getCodeById($id)
	{
		$result = self::getIdCodeCached($id, "id");

		if($result !== false)
			return $result;

		$res = self::getList(array(
			'filter' => array(
				'=ID' => $id
			),
			'select' => array("CODE")
		));

		if($handler = $res->fetch())
		{
			self::setIdCodeCached($id, $handler["CODE"]);
			return $handler["CODE"];
		}

		return false;
	}

	protected static function getIdCodeCached($value, $type)
	{
		$result = false;
		$ttl = 315360000;
		$cacheId = "SALE_DELIVERY_ID_CODE_MAP_".($type == "id" ? "I" : "C")."_".$value;
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if($cacheManager->read($ttl, $cacheId))
			$result = $cacheManager->get($cacheId);

		return $result;
	}

	protected static function setIdCodeCached($id, $code)
	{
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$cacheManager->set("SALE_DELIVERY_ID_CODE_MAP_I_".$id, $code);
		$cacheManager->set("SALE_DELIVERY_ID_CODE_MAP_C_".$code, $id);
	}

	protected static function cleanIdCodeCached($id)
	{
		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$code = self::getIdCodeCached($id, "id");
		$cacheManager->clean("SALE_DELIVERY_ID_CODE_MAP_I_".$id);

		if(strlen($code) > 0)
			$cacheManager->clean("SALE_DELIVERY_ID_CODE_MAP_C_".$code);
	}

	protected static function isDeliveryInOrders($deliveryId)
	{
		$dbOrders = \CSaleOrder::GetList(
			array(),
			array("DELIVERY_ID" => $deliveryId),
			false,
			false,
			array("ID")
		);

		if($dbOrders->Fetch())
			$result = true;
		else
			$result = false;

		return $result;
	}

	public static function onBeforeDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$primary = $event->getParameter("primary");

		if(self::isDeliveryInOrders($primary["ID"]))
		{
			$result->addError(new Entity\FieldError(
				$event->getEntity()->getField('ID'),
				Loc::getMessage('DELIVERY_SERVICE_ENTITY_ERROR_DELETE_IN_ORDERS_EXIST')
			));
		}
		else
		{
			$dbRes = self::getList(array(
				'filter' => array(
					"PARENT_ID" => $primary["ID"]
				),
				'select' => array("ID")
			));

			while($child = $dbRes->fetch())
			{
				if(self::isDeliveryInOrders($child["ID"]))
				{
					$result->addError(new Entity\FieldError(
						$event->getEntity()->getField('ID'),
						Loc::getMessage('DELIVERY_SERVICE_ENTITY_ERROR_DELETE_IN_ORDERS_EXIST_CHLD')
					));

					break;
				}
			}
		}

		return $result;
	}

	public static function onAfterDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$primary = $event->getParameter("primary");

		$tablesToDelete= array(
			'b_sale_delivery2location',
			'b_sale_delivery2paysystem',
			'b_sale_delivery_rstr',
			'b_sale_delivery_es'
		);

		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		foreach($tablesToDelete as $table)
			$con->queryExecute("DELETE FROM ".$table." WHERE DELIVERY_ID=".$sqlHelper->forSql($primary["ID"]));

		$dbRes = self::getList(array(
			'filter' => array(
				"PARENT_ID" => $primary["ID"]
			),
			'select' => array("ID")
		));

		while($child = $dbRes->fetch())
			self::delete($child["ID"]);

		self::cleanIdCodeCached($primary["ID"]);
		return $result;
	}

	public static function onAfterAdd(Entity\Event $event)
	{
		$primary = $event->getParameter('primary');
		$fields = $event->getParameter('fields');

		if(!empty($fields['CODE']))
			self::setIdCodeCached($primary['ID'], $fields['CODE']);
	}

	public static function onAfterUpdate(Entity\Event $event)
	{
		$primary = $event->getParameter('primary');
		$fields = $event->getParameter('fields');

		if(!empty($fields['CODE']))
			self::cleanIdCodeCached($primary['ID']);
	}

	public static function setChildrenFieldsValues($id, array $data)
	{
		if(empty($data))
			return 0;

		$counter = 0;

		$res = self::getList(array(
			'filter' => array('PARENT_ID' => $id),
			'select' => array('ID')
		));

		while($child = $res->fetch())
		{
			$ures = self::update($child['ID'], $data);

			if($ures->isSuccess())
				$counter++;

			$counter += self::setChildrenFieldsValues($child['ID'], $data);
		}

		return $counter;
	}

	public static function getTopGroups()
	{
		$result = array();

		$res = self::getList(array(
			'order' => array('NAME' => 'ASC'),
			'select' => array('ID', 'NAME'),
			'filter' => array(
				'=PARENT_ID' => 0,
				'=CLASS_NAME' => '\Bitrix\Sale\Delivery\Services\Group',
				'=ACTIVE' => 'Y'
			)
		));

		while($group = $res->fetch())
			$result[$group["ID"]] = $group["NAME"];

		return $result;
	}
}
