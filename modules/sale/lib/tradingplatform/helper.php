<?php

namespace Bitrix\Sale\TradingPlatform;

use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\SiteTable;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\StatusLangTable;

Loc::loadMessages(__FILE__);

/**
 * Class Helper
 * Collections of different useful static methods.
 * @package Bitrix\Sale\TradingPlatform
 */
class Helper
{
	/**
	 * Wraps  Product Provider Class::GetProductData from module catalog.
	 * @param int $productId Product Id.
	 * @param int $quantity Product quantity.
	 * @param string $siteId Site id.
	 * @return array.
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getProductById($productId, $quantity, $siteId)
	{
		$result = array();

		if(\CModule::IncludeModule('catalog'))
		{
			if ($productProvider = \CSaleBasket::GetProductProvider(array(
				"MODULE" => "catalog",
				"PRODUCT_PROVIDER_CLASS" => "CCatalogProductProvider"))
			)
			{
				global $USER;
				$bTmpUserCreated = false;
				if (!\CCatalog::IsUserExists())
				{
					$bTmpUserCreated = true;
					if (isset($USER))
					{
						$USER_TMP = $USER;
						unset($USER);
					}

					$USER = new \CUser();
				}

				$result = $productProvider::GetProductData(array(
					"PRODUCT_ID" => $productId,
					"RENEWAL"    => "N",
					"QUANTITY" => $quantity,
					"SITE_ID"    => $siteId
				));

				$result["MODULE"] = "catalog";
				$result["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";
				$dbIblockElement = \CIBlockElement::GetList(array(), array("ID" => $productId), false, false, array('XML_ID', 'IBLOCK_EXTERNAL_ID'));

				if($IblockElement = $dbIblockElement->Fetch())
				{
					if(strlen($IblockElement["XML_ID"]) > 0)
						$result["PRODUCT_XML_ID"] = $IblockElement["XML_ID"];

					if(strlen($IblockElement["IBLOCK_EXTERNAL_ID"]) > 0)
						$result["CATALOG_XML_ID"] = $IblockElement["IBLOCK_EXTERNAL_ID"];
				}

				if ($bTmpUserCreated)
				{
					unset($USER);
					if (isset($USER_TMP))
					{
						$USER = $USER_TMP;
						unset($USER_TMP);
					}
				}
			}
		}
		else
		{
			throw new \Bitrix\Main\SystemException("Can't include module \"Catalog\"!");
		}

		return $result;
	}

	/**
	 * @param string $siteId Site id.
	 * @return array List of person types for given site.
	 */
	public static function getPersonTypesList($siteId)
	{
		$dbResultList = \CSalePersonType::GetList(
			"NAME",
			"ASC",
			array(
				"LID" => $siteId,
				"ACTIVE" => "Y"
			)
		);

		$arPersonTypes = array();
		while ($arPT = $dbResultList->Fetch())
			$arPersonTypes[$arPT['ID']] = $arPT['NAME'];

		return $arPersonTypes;
	}

	/**
	 * @param string $siteId Site id.
	 * @return array List of diliveries for given site.
	 */
	public static function getDeliveryList($siteId)
	{
		$arDeliveryFilter = array(
			"LID" => $siteId,
			"ACTIVE" => "Y"
		);

		$dbDeliveryList = \CSaleDelivery::GetList(
			array("NAME" => "ASC"),
			$arDeliveryFilter,
			false,
			false,
			array("ID", "NAME")
		);

		$arDeliveryList=array();
		while ($arDelivery = $dbDeliveryList->Fetch())
			$arDeliveryList[$arDelivery["ID"]] = $arDelivery["NAME"];

		return $arDeliveryList;
	}

	/**
	 * @param int $personTypeId Person type Id.
	 * @return array Orders properties.
	 */
	public static function getOrderPropsList($personTypeId)
	{
		if(intval($personTypeId) <= 0)
			throw new ArgumentNullException('personTypeId');

		$res = OrderPropsTable::getList(array(
			'filter' => array('=PERSON_TYPE_ID' => $personTypeId),
			'order'  => array('SORT' => 'ASC', 'NAME' => 'ASC'),
			'select' => array('ID', 'CODE', 'NAME')
		));

		return $res->fetchAll();
	}

	/**
	 * @return array List of properties wich required for order creation.
	 */
	public static function getRequiredOrderProps()
	{
		return array(
			"FIO",
			"EMAIL",
			"PHONE",
			"ZIP",
			"CITY",
			"ADDRESS",
		);
	}


	/**
	 * @param string $selectName Name of input element.
	 * @param string $psIdValue Selected value.
	 * @param int $personTypeId Person type Id.
	 * @return string html element <select>...</select>
	 */
	public static function makeSelectorFromPaySystems($selectName, $psIdValue, $personTypeId)
	{
		static $arPaySystems = array();

		if(!isset($arPaySystems[$personTypeId]))
		{
			$arPaySystems[$personTypeId] = array();
			$dbResultList = \CSalePaySystem::GetList(
				array("NAME" => "ASC"),
				array(
					"ACTIVE" => "Y",
					"PSA_PERSON_TYPE_ID" => $personTypeId,
				),
				false,
				false,
				array("ID", "NAME")
			);

			while($arPS = $dbResultList->Fetch())
				$arPaySystems[$personTypeId][$arPS['ID']] = $arPS['NAME'];
		}

		$result = '<select name="'.$selectName.'">'.
			'<option value="">'.Loc::getMessage("SALE_EBAY_HLP_NOT_USE").'</option>';

		foreach ($arPaySystems[$personTypeId] as $psId => $psName)
		{
			$result.= '<option value="'.
				$psId.'"'.
				($psIdValue == $psId ? ' selected ': '').'>'.
				htmlspecialcharsbx($psName).
				'</option>';
		}

		$result .= '</select>';

		return $result;
	}

	/**
	 * @param string $name Name of select.
	 * @param array $data to use in select,
	 * @param string $selected Selected value.
	 * @param bool $bShowNotUse Show or not empty value.
	 * @return string Html select element <select>....</select>
	 */
	public static function getSelectHtml($name, array $data, $selected = "", $bShowNotUse = true)
	{
		if(!is_array($data) || empty($data))
			return "";

		$result = '<select name="'.htmlspecialcharsbx($name).'">';

		if($bShowNotUse)
			$result .= '<option value="">'.GetMessage("SALE_YM_NOT_USE").'</option>';

		foreach($data as $value => $title)
			$result .= '<option value="'.$value.'"'.($selected == $value ? " selected" : "").'>'.$title.'</option>';

		$result .= '</select>';

		return $result;
	}

	/**
	 * @param array $params
	 * @return array Additional order properties.
	 */
	public static  function makeAdditionalOrderProps(array $params)
	{
		$psId = intval($params["PAY_SYSTEM"]);

		$result = array();

		$arPropFilter = array(
			"PERSON_TYPE_ID" => $params["PERSON_TYPE"],
			"ACTIVE" => "Y"
		);

		if ($psId != 0)
		{
			$arPropFilter["RELATED"]["PAYSYSTEM_ID"] = $psId;
			$arPropFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		if (strlen($params["DELIVERY"]) > 0)
		{
			$arPropFilter["RELATED"]["DELIVERY_ID"] = $params["DELIVERY"];
			$arPropFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		$dbOrderProps = \CSaleOrderProps::GetList(
			array(),
			$arPropFilter,
			false,
			false,
			array("ID", "CODE")
		);		

		while ($arOrderProps = $dbOrderProps->Fetch())
		{
			foreach(self::getOrderProps() as $prop)
			{
				if($arOrderProps["CODE"] == $params["ORDER_PROPS_MAP"][$prop] && isset($params[$prop]))
					$result[$arOrderProps["ID"]] = $params[$prop];
			}
		}

		return $result;
	}

	/**
	 * @return array Order's statuses & flags.
	 */
	public static function getBitrixStatuses($siteId)
	{
		$result = array(
			"CANCELED" => Loc::getMessage("SALE_EBAY_HLP_FLAG_CANCELED"),
			"ALLOW_DELIVERY" => Loc::getMessage("SALE_EBAY_HLP_FLAG_DELIVERY"),
			"PAYED" => Loc::getMessage("SALE_EBAY_HLP_FLAG_PAYED"),
			"DEDUCTED" => Loc::getMessage("SALE_EBAY_HLP_FLAG_DEDUCTED"),
		);

		if(strlen($siteId) <= 0)
			throw new ArgumentNullException("siteId");

		$dbRes = SiteTable::getList(array(
			'filter' => array(
				'LID' => $siteId
			),
			'select' => array("LANGUAGE_ID")
		));

		if($site = $dbRes->fetch())
			$langId = $site["LANGUAGE_ID"];
		else
			throw new SystemException("Site with id: \"".$siteId."\" not found!");

		$dbRes = StatusLangTable::getList(array(
			'filter' => array(
				'LID' => $langId,
				'STATUS.TYPE' => 'O'
			),
			'order' => array(
				"STATUS.SORT" => "ASC",
				"NAME" => "ASC"
			),
			'select' => array(
				"ID" => "STATUS.ID", "NAME"
			)
		));

		while ($row = $dbRes->fetch())
			$result[$row['ID']] = Loc::getMessage("SALE_EBAY_HLP_STATUS")." ".$row['NAME']. ' ['.$row['ID'].']';

		return $result;
	}

	/**
	 * @param string $selectName Name of select.
	 * @param int $iblockId Iblock Id.
	 * @param int $bitrixCategoryId Iblock section Id.
	 * @param string $value Selected value.
	 * @return string Html element <select>....
	 */
	public static function getBitrixCategoryPropsHtml($selectName, $iblockId, $bitrixCategoryId = 0, $value = '')
	{
		$catProps = self::getPropertiesList($iblockId, $bitrixCategoryId);

		$resultHtml =
			'	<select name="'.$selectName.'">
			<option value=""></option>
			<option value="">------------------</option>
			<option value="">'.Loc::getMessage("SALE_EBAY_HLP_CATEGORY_PROPS").'</option>
			<option value="">------------------</option>';

		foreach ($catProps as $propId => $prop)
			$resultHtml .= '<option value="'.$propId.'"'.($value == $propId ? ' selected' : '').'>'.$prop["NAME"].'</option>';

		$arOffers = \CCatalogSKU::GetInfoByProductIBlock($iblockId);
		if($arOffers)
		{
			$catProps2 = self::getPropertiesList($arOffers["IBLOCK_ID"], $bitrixCategoryId);

			$resultHtml .= '
				<option value="">------------------</option>
				<option value="">'.Loc::getMessage("SALE_EBAY_HLP_OFFERS_PROPS").'</option>
				<option value="">------------------</option>
			';

			foreach ($catProps2 as  $propId => $prop)
				$resultHtml .= '<option value="'.$propId.'"'.($value == $propId ? ' selected' : '').'>'.$prop["NAME"].'</option>';
		}

		$resultHtml .='	</select>';

		return $resultHtml;
	}

	/**
	 * @param int $iblockId Iblock Id.
	 * @param int $sectionId Section Id.
	 * @return array Iblock properties list.
	 */
	public static function getPropertiesList($iblockId, $sectionId = 0)
	{
		$result = \CIBlockSectionPropertyLink::GetArray($iblockId, $sectionId);

		$rsProps =  \CIBlockProperty::GetList(array(
			"SORT"=>"ASC",
			'ID' => 'ASC',
		), array(
			"IBLOCK_ID" => $iblockId,
			"CHECK_PERMISSIONS" => "N",
			"ACTIVE"=>"Y",
		));

		while ($arProp = $rsProps->Fetch())
			if(isset($result[$arProp["ID"]]))
				$result[$arProp["ID"]]["NAME"] = $arProp["NAME"];

		return $result;
	}


	static public function notifyNewOrder($newOrderId, $siteId, $buyerEmail = "", $buyerFio = "")
	{
		if(strlen($newOrderId) <= 0)
			throw new ArgumentNullException("newOrderId");

		if(strlen($siteId) <= 0)
			throw new ArgumentNullException("siteId");

		global $DB;

		$strOrderList = "";
		$baseLangCurrency = \CSaleLang::GetLangCurrency($siteId);
		$orderNew = \CSaleOrder::GetByID($newOrderId);
		$orderNew["BASKET_ITEMS"] = array();

		$dbBasketTmp = \CSaleBasket::GetList(
			array("SET_PARENT_ID" => "DESC", "TYPE" => "DESC", "NAME" => "ASC"),
			array("ORDER_ID" => $newOrderId),
			false,
			false,
			array(
				"ID","PRICE", "QUANTITY", "NAME"
			)
		);

		while ($arBasketTmp = $dbBasketTmp->GetNext())
			$orderNew["BASKET_ITEMS"][] = $arBasketTmp;

		$orderNew["BASKET_ITEMS"] = getMeasures($orderNew["BASKET_ITEMS"]);

		foreach ($orderNew["BASKET_ITEMS"] as $val)
		{
			if (\CSaleBasketHelper::isSetItem($val))
				continue;

			$measure = (isset($val["MEASURE_TEXT"])) ? $val["MEASURE_TEXT"] : GetMessage("SALE_YMH_SHT");
			$strOrderList .= $val["NAME"]." - ".$val["QUANTITY"]." ".$measure.": ".SaleFormatCurrency($val["PRICE"], $baseLangCurrency);
			$strOrderList .= "\n";
		}

		//send mail
		$arFields = array(
			"ORDER_ID" => $orderNew["ACCOUNT_NUMBER"],
			"ORDER_DATE" => Date($DB->DateFormatToPHP(\CLang::GetDateFormat("SHORT", $siteId))),
			"ORDER_USER" => $buyerFio,
			"PRICE" => SaleFormatCurrency($orderNew["PRICE"], $baseLangCurrency),
			"BCC" => \COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
			"EMAIL_TO" => array("PAYER_NAME" => $buyerFio , "USER_EMAIL" => $buyerEmail),
			"ORDER_LIST" => $strOrderList,
			"SALE_EMAIL" => \COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
			"DELIVERY_PRICE" => $orderNew["DELIVERY_PRICE"],
		);

		$eventName = "SALE_NEW_ORDER";

		$bSend = true;

		foreach(GetModuleEvents("sale", "OnOrderNewSendEmail", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($newOrderId, &$eventName, &$arFields))===false)
				$bSend = false;

		$emailSendRes = false;

		if($bSend)
		{
			$event = new \CEvent;
			$emailSendRes = $event->Send($eventName, $siteId, $arFields, "N");
		}

		$pushSendResult = \CSaleMobileOrderPush::send("ORDER_CREATED", array("ORDER" => $orderNew));

		return $emailSendRes && $pushSendResult;
	}

	public static function onAfterUpdateShipment(\Bitrix\Main\Event $event)
	{
		$result = new EventResult();
		$data = $event->getParameter('fields');

		if(!isset($data["TRACKING_NUMBER"]) && !isset($data["DELIVERY_NAME"]))
			return $result;

		$primary = $event->getParameter('id');

		$dbRes = OrderTable::getList(array(
			'select' => array(
				'*',
				'SITE_ID' => 'ORDER.LID',
				'TRADING_PLATFORM_CODE' => 'TRADING_PLATFORM.CODE',
				'TRADING_PLATFORM_CLASS' => 'TRADING_PLATFORM.CLASS',
				'DELIVERY_NAME' => 'SHIPMENT.DELIVERY.NAME',
				'DELIVERY_ID' => 'SHIPMENT.DELIVERY_ID',
				'TRACKING_NUMBER' => 'SHIPMENT.TRACKING_NUMBER'
			),
			'filter' => array(
				'=SHIPMENT.ID' => $primary['ID']
			),
			'runtime' => array(
				'SHIPMENT' => array(
					'data_type' => 'Bitrix\Sale\Internals\ShipmentTable',
					'reference' => array(
						'=this.ORDER_ID' => 'ref.ORDER_ID'
					)
				)
			)
		));

		if($platformOrder = $dbRes->fetch())
		{
			if(class_exists($platformOrder['TRADING_PLATFORM_CLASS']) && is_subclass_of($platformOrder['TRADING_PLATFORM_CLASS'], '\Bitrix\Sale\TradingPlatform\Platform'))
			{
				if($platform = call_user_func($platformOrder['TRADING_PLATFORM_CLASS'].'::getInstance'))
				{
					$result = $platform->onAfterUpdateShipment(
						$event,
						array_merge(
							$platformOrder,
							array(
								'TRACKING_NUMBER' => isset($data["TRACKING_NUMBER"]) ? $data["TRACKING_NUMBER"] : $platformOrder["TRACKING_NUMBER"],
								'DELIVERY_NAME' => isset($data["DELIVERY_NAME"]) ? $data["DELIVERY_NAME"] : $platformOrder["DELIVERY_NAME"],
								'DELIVERY_ID' => isset($data["DELIVERY_ID"]) ? $data["DELIVERY_ID"] : $platformOrder["DELIVERY_ID"]
							)
						)
					);
				}
			}
		}

		return $result;
	}

	public static function getIblocksIds()
	{
		if(!\Bitrix\Main\Loader::includeModule('catalog'))
			throw new SystemException('Module catalog is not installed');

		$arIBlockIDs = array();
		$rsCatalogs = \CCatalog::GetList(
			array(),
			array('!PRODUCT_IBLOCK_ID' => 0),
			false,
			false,
			array('PRODUCT_IBLOCK_ID')
		);
		while ($arCatalog = $rsCatalogs->Fetch())
		{
			$arCatalog['PRODUCT_IBLOCK_ID'] = intval($arCatalog['PRODUCT_IBLOCK_ID']);
			if (0 < $arCatalog['PRODUCT_IBLOCK_ID'])
				$arIBlockIDs[$arCatalog['PRODUCT_IBLOCK_ID']] = true;
		}
		$rsCatalogs = \CCatalog::GetList(
			array(),
			array('PRODUCT_IBLOCK_ID' => 0),
			false,
			false,
			array('IBLOCK_ID')
		);
		while ($arCatalog = $rsCatalogs->Fetch())
		{
			$arCatalog['IBLOCK_ID'] = intval($arCatalog['IBLOCK_ID']);
			if (0 < $arCatalog['IBLOCK_ID'])
				$arIBlockIDs[$arCatalog['IBLOCK_ID']] = true;
		}
		if (empty($arIBlockIDs))
			$arIBlockIDs[-1] = true;

		return $arIBlockIDs;
	}

	public static function getDefaultFeedIntervals()
	{
		return array(
			"PRODUCT" => 30,
			"INVENTORY" => 30,
			"IMAGE" => 30,
			"ORDER" => 30,
			"ORDER_ACK" => 15,
			"SHIPMENT" => 30
		);
	}
}
