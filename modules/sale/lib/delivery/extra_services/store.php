<?php

namespace Bitrix\Sale\Delivery\ExtraServices;

use Bitrix\Sale\Internals\Input;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Result;

Loc::loadMessages(__FILE__);

class Store extends Base
{
	protected static function getStoresList($nameOnly = true, $siteId = "")
	{
		if(!\Bitrix\Main\Loader::includeModule('catalog'))
			return array();

		$filter = array("ACTIVE" => "Y", "ISSUING_CENTER" => "Y");

		if(strlen($siteId) > 0)
			$filter["+SITE_ID"] = $siteId;

		$result = array();
		$dbList = \CCatalogStore::GetList(
			array("SORT" => "ASC", "TITLE" => "ASC"),
			$filter,
			false,
			false,
			array("ID", "SITE_ID", "TITLE", "ADDRESS", "DESCRIPTION", "IMAGE_ID", "PHONE", "SCHEDULE", "LOCATION_ID", "GPS_N", "GPS_S")
		);

		while ($store = $dbList->Fetch())
		{
			if($nameOnly)
				$result[$store["ID"]] = $store["TITLE"].(strlen($store["SITE_ID"]) > 0 ? " [".$store["SITE_ID"]."]" : "");
			else
				$result[$store["ID"]] = $store;
		}

		return $result;
	}

	static public function getClassTitle()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_STORE_TITLE");
	}

	static public function getCost()
	{
		return false;
	}

	public static function getAdminParamsName()
	{
		return Loc::getMessage("DELIVERY_EXTRA_SERVICE_STORE_TITLE");
	}

	public static function getAdminParamsControl($name, array $params, $currency = "")
	{
		return 	Input\Manager::getEditHtml(
			$name."[PARAMS][STORES]",
			array(
				"TYPE" => "ENUM",
				"MULTIPLE" => "Y",
				"OPTIONS" => self::getStoresList()
			),
			$params["PARAMS"]["STORES"]
		);
	}

	static public function getAdminDefaultControl($name, $value = false)
	{
		return Input\Manager::getEditHtml(
			$name,
			array(
				"TYPE" => "ENUM",
				"OPTIONS" => self::getStoresList()
			),
			$value
		);
	}

	public function getEditControl($prefix = "", $value = false)
	{
		global $APPLICATION;

		if(!$value)
			$value = $this->value;

		$result = '<div class="view_map">';
		$siteId = strlen(SITE_ID) > 0 ? SITE_ID : "";

		ob_start();
		$APPLICATION->IncludeComponent(
		"bitrix:sale.store.choose",
		".default",
		Array(
			"INPUT_NAME" => $prefix,
			"DELIVERY_ID" => $this->deliveryId,
			"SELECTED_STORE" => $value,
			"STORES_LIST" => self::getStoresList(false, $siteId)
		));

		$result .= ob_get_contents();
		ob_end_clean();

		$result .= '</div>';
		return $result;
	}

	public function getViewControl()
	{
		return Input\Manager::getViewHtml(
			array(
				"TYPE" => "ENUM",
				"OPTIONS" => self::getStoresList(true, strlen(SITE_ID) > 0 ? SITE_ID : "")
			),
			$this->value
		);
	}

	public static function isInner()
	{
		return true;
	}

	public static function getStoresIdsFromParams(array $params)
	{
		return $params["STORES"];
	}
}