<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Sale\Delivery\Services;
use Bitrix\Sale\Services\PaySystem\Restrictions\Manager;

Loc::loadMessages(__FILE__);

/**
 * Class DeliveryPaySystemTable
 *
 * Fields:
 * <ul>
 * <li> DELIVERY_ID string(35) mandatory
 * <li> PAYSYSTEM_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class DeliveryPaySystemTable extends \Bitrix\Main\Entity\DataManager
{
	const LINK_DIRECTION_DELIVERY_PAYSYSTEM = "D";
	const LINK_DIRECTION_PAYSYSTEM_DELIVERY = "P";

	const ENTITY_TYPE_DELIVERY = "DELIVERY_ID";
	const ENTITY_TYPE_PAYSYSTEM = "PAYSYSTEM_ID";

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_sale_delivery2paysystem';
	}

	public static function getMap()
	{
		return array(
			'DELIVERY_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_PAYSYSTEM_ENTITY_DELIVERY_ID_FIELD'),
			),
			'DELIVERY' => array(
				'data_type' => '\Bitrix\Sale\Delivery\Services\Table',
				'reference' => array(
					'=this.DELIVERY_ID' => 'ref.ID'
				)
			),
			'LINK_DIRECTION' => array(
				'primary' => true,
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateLinkDirection'),
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_PAYSYSTEM_ENTITY_LINK_DIRECTION'),
			),
			'PAYSYSTEM_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('DELIVERY_PAYSYSTEM_ENTITY_PAYSYSTEM_ID_FIELD'),
			),
			'PAYSYSTEM' => array(
				'data_type' => '\Bitrix\Sale\Internals\PaySystemActionTable',
				'reference' => array(
					'=this.PAYSYSTEM_ID' => 'ref.ID'
				)
			)
		);
	}

	public static function validateLinkDirection()
	{
		return array(
			new \Bitrix\Main\Entity\Validator\Length(1, 1),
		);
	}

	/**
	 * @param int $entityId
	 * @param string $entityType self::ENTITY_TYPE_DELIVERY || self::ENTITY_TYPE_PAYSYSTEM
	 * @param int[] $linkedIds Empty means all
	 * @return \Bitrix\Main\Entity\Result
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function setLinks($entityId, $entityType, array $linkedIds = array(), $actualizeRestrictions = true)
	{
		if(intval($entityId) <= 0)
			throw new ArgumentNullException("entityId");

		if($entityType != self::ENTITY_TYPE_DELIVERY && $entityType != self::ENTITY_TYPE_PAYSYSTEM)
			throw new ArgumentOutOfRangeException("entityType");

		$result = new \Bitrix\Main\Entity\Result();
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();
		$entityId = $sqlHelper->forSql($entityId);

		if($entityType == self::ENTITY_TYPE_DELIVERY)
		{
			$linkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM ;
			$reverseLinkDirection = self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			$reverseEntityType = self::ENTITY_TYPE_PAYSYSTEM;
		}
		else
		{
			$linkDirection =  self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			$reverseLinkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM;
			$reverseEntityType = self::ENTITY_TYPE_DELIVERY;
		}

		//delete current entity links
		$con->queryExecute(
			"DELETE FROM ".self::getTableName().
			" WHERE ".$entityType."=".$entityId." AND LINK_DIRECTION='".$linkDirection."'"
		);

		//insert new links
		if(!empty($linkedIds))
			self::insertLinks($entityId, $linkDirection, $entityType, $linkedIds);

		$glParams = array(
			'filter' => array(
				'=LINK_DIRECTION' => $reverseLinkDirection,
			)
		);

		if(!empty($linkedIds))
			$glParams['filter'][$reverseEntityType] = $linkedIds;

		$res = self::getList($glParams);

		$linkedToEntity = array();
		$linkedToOther = array();

		while($rec = $res->fetch())
		{
			if($rec[$entityType] == $entityId)
			{
				if(!in_array($rec[$reverseEntityType], $linkedToEntity))
					$linkedToEntity[] = $rec[$reverseEntityType];
			}
			else
			{
				if(!in_array($rec[$reverseEntityType], $linkedToOther))
					$linkedToOther[] = $rec[$reverseEntityType];
			}
		}

		$reverseIdsToAdd = array_diff($linkedToOther, $linkedToEntity);

		//set reverse links to current entity
		if(!empty($reverseIdsToAdd))
			self::insertLinks($entityId, $reverseLinkDirection, $entityType, $reverseIdsToAdd);

		$reverseIdsToDelete = array_diff($linkedToEntity, $linkedIds);

		if(!empty($reverseIdsToDelete))
		{
			//delete reverse links we didn't choose
			foreach($reverseIdsToDelete as $id)
			{
				self::delete(array(
					$entityType => $entityId,
					$reverseEntityType => $id,
					"LINK_DIRECTION" => $reverseLinkDirection
				));
			}
		}

		if($actualizeRestrictions)
		{
			self::actualizeDeliveriesRestrictionByPS();
			self::actualizePaySystemRestrictionByDelivery();
		}

		return $result;
	}

	protected static function actualizeDeliveriesRestrictionByPS()
	{
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$restrictions = array();
		$dbR = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
			'filter' => array(
				'=CLASS_NAME' => '\Bitrix\Sale\Delivery\Restrictions\ByPaySystem'
			),
			'select' => array('SERVICE_ID')
		));

		while($restr = $dbR->fetch())
			$restrictions[] = $restr['SERVICE_ID'];

		$deliveryList = self::getEntityItemsFullList(self::ENTITY_TYPE_DELIVERY);
		$dLinkedToP = array();
		$deliveriesToPs = array();
		$linkedPS = array();

		$dbP2S = DeliveryPaySystemTable::getList();

		while($d2p = $dbP2S->fetch())
		{
			if($d2p["LINK_DIRECTION"] == self::LINK_DIRECTION_DELIVERY_PAYSYSTEM && !in_array($d2p["DELIVERY_ID"], $dLinkedToP))
				$dLinkedToP[] = $d2p["DELIVERY_ID"];

			if($d2p["LINK_DIRECTION"] == self::LINK_DIRECTION_PAYSYSTEM_DELIVERY )
			{
				if(!isset($deliveriesToPs[$d2p["DELIVERY_ID"]]))
					$deliveriesToPs[$d2p["DELIVERY_ID"]] = array();

				$linkedPS[] = $d2p["PAYSYSTEM_ID"];
				$deliveriesToPs[$d2p["DELIVERY_ID"]][] = $d2p["PAYSYSTEM_ID"];
			}
		}

		$notLinkedToPS = array_diff($deliveryList, $dLinkedToP);
		$existLinkedPs = !empty($linkedPS);
		$notNeedRestriction = array();
		$needRestriction = array();

		foreach($deliveryList as $id)
		{
			$need = true;

			//DS not linked to PS and (All PS having links linked to current DS
			if(in_array($id, $notLinkedToPS))
			{
				if(isset($deliveriesToPs[$id]))
					$diff = array_diff($linkedPS, $deliveriesToPs[$id]);
				else
					$diff = $linkedPS;

				if(!$existLinkedPs || empty($diff))
				{
					$notNeedRestriction[] = $id;
					$need = false;
				}
			}

			// DS linked to PS or exist linked PS but not linked to current DS
			if($need)
				$needRestriction[] = $id;
		}

		$notNeedRestriction = array_intersect($notNeedRestriction, $restrictions);

		if(!empty($notNeedRestriction))
		{
			$sql = "";

			foreach($notNeedRestriction as $deliveryId)
				$sql .= " ".($sql == "" ? "WHERE CLASS_NAME='".$sqlHelper->forSql('\Bitrix\Sale\Delivery\Restrictions\ByPaySystem')."' AND (" : "OR " )."SERVICE_ID=".$sqlHelper->forSql($deliveryId)." AND SERVICE_TYPE=".Restrictions\Manager::SERVICE_TYPE_SHIPMENT;

			$sql = "DELETE FROM ".\Bitrix\Sale\Internals\ServiceRestrictionTable::getTableName().$sql.")";
			$con->queryExecute($sql);
		}

		$needRestriction = array_diff($needRestriction, $restrictions);

		//let's... add missing
		if(!empty($needRestriction))
		{
			$sql = "";

			foreach($needRestriction as $deliveryId)
				$sql .= ($sql == "" ? " " : ", ")."(".$sqlHelper->forSql($deliveryId).", '".$sqlHelper->forSql('\Bitrix\Sale\Delivery\Restrictions\ByPaySystem')."', ".Restrictions\Manager::SERVICE_TYPE_SHIPMENT.")";

			$sql = "INSERT INTO ".\Bitrix\Sale\Internals\ServiceRestrictionTable::getTableName()."(SERVICE_ID, CLASS_NAME, SERVICE_TYPE) VALUES".$sql;
			$con->queryExecute($sql);
		}
	}

	protected static function actualizePaySystemRestrictionByDelivery()
	{
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$restrictions = array();
		$dbR = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList(array(
			'filter' => array(
				'=CLASS_NAME' => '\Bitrix\Sale\Services\PaySystem\Restrictions\Delivery'
			),
			'select' => array('SERVICE_ID')
		));

		while($restr = $dbR->fetch())
			$restrictions[] = $restr['SERVICE_ID'];

		$deliveryList = self::getEntityItemsFullList(self::ENTITY_TYPE_PAYSYSTEM);
		$dLinkedToP = array();
		$deliveriesToPs = array();
		$linkedPS = array();

		$dbP2S = DeliveryPaySystemTable::getList();

		while($d2p = $dbP2S->fetch())
		{
			if($d2p["LINK_DIRECTION"] == self::LINK_DIRECTION_PAYSYSTEM_DELIVERY && !in_array($d2p["PAYSYSTEM_ID"], $dLinkedToP))
				$dLinkedToP[] = $d2p["PAYSYSTEM_ID"];

			if($d2p["LINK_DIRECTION"] == self::LINK_DIRECTION_DELIVERY_PAYSYSTEM)
			{
				if(!isset($deliveriesToPs[$d2p["PAYSYSTEM_ID"]]))
					$deliveriesToPs[$d2p["PAYSYSTEM_ID"]] = array();

				$linkedPS[] = $d2p["DELIVERY_ID"];
				$deliveriesToPs[$d2p["PAYSYSTEM_ID"]][] = $d2p["DELIVERY_ID"];
			}
		}

		$notLinkedToPS = array_diff($deliveryList, $dLinkedToP);
		$existLinkedPs = !empty($linkedPS);
		$notNeedRestriction = array();
		$needRestriction = array();

		foreach($deliveryList as $id)
		{
			$need = true;

			//DS not linked to PS and (All PS having links linked to current DS
			if(in_array($id, $notLinkedToPS))
			{
				if(isset($deliveriesToPs[$id]))
					$diff = array_diff($linkedPS, $deliveriesToPs[$id]);
				else
					$diff = $linkedPS;

				if(!$existLinkedPs || empty($diff))
				{
					$notNeedRestriction[] = $id;
					$need = false;
				}
			}

			// DS linked to PS or exist linked PS but not linked to current DS
			if($need)
				$needRestriction[] = $id;
		}

		$notNeedRestriction = array_intersect($notNeedRestriction, $restrictions);

		if(!empty($notNeedRestriction))
		{
			$sql = "";

			foreach($notNeedRestriction as $deliveryId)
				$sql .= " ".($sql == "" ? "WHERE CLASS_NAME='".$sqlHelper->forSql('\Bitrix\Sale\Services\PaySystem\Restrictions\Delivery')."' AND (" : "OR " )."SERVICE_ID=".$sqlHelper->forSql($deliveryId)." AND SERVICE_TYPE=".Manager::SERVICE_TYPE_PAYMENT;

			$sql = "DELETE FROM ".\Bitrix\Sale\Internals\ServiceRestrictionTable::getTableName().$sql.")";
			$con->queryExecute($sql);
		}

		$needRestriction = array_diff($needRestriction, $restrictions);

		//let's... add missing
		if(!empty($needRestriction))
		{
			$sql = "";

			foreach($needRestriction as $deliveryId)
				$sql .= ($sql == "" ? " " : ", ")."(".$sqlHelper->forSql($deliveryId).", '".$sqlHelper->forSql('\Bitrix\Sale\Services\PaySystem\Restrictions\Delivery')."', ".Manager::SERVICE_TYPE_PAYMENT.")";

			$sql = "INSERT INTO ".\Bitrix\Sale\Internals\ServiceRestrictionTable::getTableName()."(SERVICE_ID, CLASS_NAME, SERVICE_TYPE) VALUES".$sql;
			$con->queryExecute($sql);
		}
	}

	protected static function insertLinks($entityId, $linkDirection, $entityType, $linkedIds)
	{
		$con = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();
		$entityId = $sqlHelper->forSql($entityId);
		$linkDirection = $sqlHelper->forSql($linkDirection);

		$sql = "INSERT INTO ".
			self::getTableName().
			"(DELIVERY_ID, PAYSYSTEM_ID, LINK_DIRECTION) ".
			"VALUES";

		$first = true;

		foreach($linkedIds as $id)
		{
			if(!$first)
				$sql .= ",";
			else
				$first = false;

			$id = $sqlHelper->forSql($id);

			if($entityType == self::ENTITY_TYPE_DELIVERY)
				$sql .= " (".$entityId.", ".$id;
			else
				$sql .= " (".$id.", ".$entityId;

			$sql .= ", '".$linkDirection."')";
		}

		$con->queryExecute($sql);
	}

	/**
	 * @param int $entityId
	 * @param string $entityType self::ENTITY_TYPE_DELIVERY || self::ENTITY_TYPE_PAYSYSTEM
	 * @return int[]
	 * @throws ArgumentNullException
	 * @throws ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getLinks($entityId, $entityType, array $preparedData = array())
	{
		$result = array();

		if(intval($entityId) <= 0)
			return array();

		if($entityType != self::ENTITY_TYPE_DELIVERY && $entityType != self::ENTITY_TYPE_PAYSYSTEM)
			throw new ArgumentOutOfRangeException("entityType");

		if($entityType == self::ENTITY_TYPE_DELIVERY)
		{
			$linkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM ;
			$reverseLinkDirection = self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			$reverseEntityType = self::ENTITY_TYPE_PAYSYSTEM;
		}
		else
		{
			$linkDirection =  self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			$reverseLinkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM;
			$reverseEntityType = self::ENTITY_TYPE_DELIVERY;
		}

		if(isset($preparedData[$entityId]["DIRECT"]))
		{
			$result = $preparedData[$entityId]["DIRECT"];
		}
		else
		{
			$glParams = array(
				'filter' => array(
					"=".$entityType => $entityId,
					"=LINK_DIRECTION" => $linkDirection
				),
				'select' => array($reverseEntityType)
			);

			$res = self::getList($glParams);

			while($rec = $res->fetch())
				$result[] = $rec[$reverseEntityType];
		}

		//if entity has links they must be actual
		if(!empty($result))
			return $result;

		if(isset($preparedData[$entityId]["REVERSE"]))
		{
			$result = $preparedData[$entityId]["REVERSE"];
		}
		else
		{
			$res = self::getList(array(
				'filter' => array(
					"=".$entityType => $entityId,
					"=LINK_DIRECTION" => $reverseLinkDirection
				),
				'select' => array($reverseEntityType)
			));

			while($rec = $res->fetch())
				$result[] = $rec[$reverseEntityType];
		}

		$result = array_merge($result, self::getUnlinkedEnityItems($reverseEntityType));
		return $result;
	}


	protected static function getUnlinkedEnityItems($entityType)
	{
		static $result = array();

		if(!isset($result[$entityType]))
		{
			$list = self::getEntityItemsFullList($entityType);

			if($entityType == self::ENTITY_TYPE_DELIVERY)
				$linkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM;
			else
				$linkDirection =  self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;

			$res = DeliveryPaySystemTable::getList(array(
				'group' => array($entityType),
				'select' => array($entityType),
				'filter' => array(
					"LINK_DIRECTION" => $linkDirection
				)
			));

			while($item = $res->fetch())
			{
				if(in_array($item[$entityType], $list))
				{
					unset($list[array_search($item[$entityType], $list)]);
				}
			}

			$result[$entityType] = $list;
		}

		return $result[$entityType];
	}

	protected static function getEntityItemsFullList($entityType)
	{
		static $result = array();

		if(isset($result[$entityType]))
			return $result[$entityType];

		$result[$entityType] = array();

		if($entityType == self::ENTITY_TYPE_DELIVERY)
		{
			foreach(\Bitrix\Sale\Delivery\Services\Manager::getActiveList() as $dsrv)
			{
				$obj = Services\Manager::createObject($dsrv);

				if ($obj && ($obj->canHasChildren() || $obj->canHasProfiles()))
					continue;

				$result[$entityType][] = $dsrv["ID"];
			}
		}
		else
		{
			$dbRes = PaySystemActionTable::getList(array(
				'filter' => array("ACTIVE" =>  "Y"),
				'select' => array("ID")
			));

			while($ps = $dbRes->fetch())
				$result[$entityType][] = $ps["ID"];
		}

		return $result[$entityType];
	}

	public static function prepareData(array $entityIds, $entityType)
	{
		static $preparedData = array();

		if(!isset($preparedData[$entityType]))
			$preparedData[$entityType] = array();

		if($entityType == self::ENTITY_TYPE_DELIVERY)
		{
			$linkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM;
			$reverseLinkDirection = self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			$reverseEntityType = self::ENTITY_TYPE_PAYSYSTEM;
		}
		else
		{
			$linkDirection =  self::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			$reverseLinkDirection = self::LINK_DIRECTION_DELIVERY_PAYSYSTEM;
			$reverseEntityType = self::ENTITY_TYPE_DELIVERY;
		}

		if(empty($entityIds))
			$entityIds = self::getEntityItemsFullList($entityType);

		$arrdif = array_diff($entityIds, array_keys($preparedData[$entityType]));

		if(is_array($arrdif) && empty($arrdif))
			return array_intersect_key($preparedData[$entityType], $entityIds);

		$glParams = array(
			'filter' => array(
				"=".$entityType => $arrdif
			)
		);

		$res = DeliveryPaySystemTable::getList($glParams);

		foreach($arrdif as $id)
		{
			$preparedData[$entityType][$id] = array(
				"DIRECT" => array(),
				"REVERSE" => array()
			);
		}

		while($rec = $res->fetch())
		{
			if($rec["LINK_DIRECTION"] == $linkDirection)
				$preparedData[$entityType][$rec[$entityType]]["DIRECT"][] = $rec[$reverseEntityType];
			elseif($rec["LINK_DIRECTION"] == $reverseLinkDirection)
				$preparedData[$entityType][$rec[$entityType]]["REVERSE"][] = $rec[$reverseEntityType];
		}

		return $preparedData[$entityType];
	}
}