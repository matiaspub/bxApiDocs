<?

use \Bitrix\Sale\DiscountCouponsManager;
use \Bitrix\Sale\Compatible\DiscountCompatibility;
use \Bitrix\Sale\TradingPlatform\YandexMarket;

IncludeModuleLangFile(__FILE__);

/**
 * Yandex market purchase processing
 */

class CSaleYMHandler
{
	const JSON = 0;
	const XML = 1;

	const ERROR_STATUS_400 = "400 Bad Request";
	const ERROR_STATUS_401 = "401 Unauthorized";
	const ERROR_STATUS_403 = "403 Forbidden";
	const ERROR_STATUS_404 = "404 Not Found";
	const ERROR_STATUS_405 = "405 Method Not Allowed";
	const ERROR_STATUS_415 = "415 Unsupported Media Type";
	const ERROR_STATUS_420 = "420 Enhance Your Calm";
	const ERROR_STATUS_500 = "500 Internal Server Error";
	const ERROR_STATUS_503 = "503 Service Unavailable";

	const DATE_FORMAT = "d-m-Y";
	const XML_ID_PREFIX = "ymarket_";

	const TRADING_PLATFORM_CODE = "ymarket";

	protected $communicationFormat = self::JSON;
	protected $siteId = "";
	protected $authType = "HEADER"; // or URL

	const LOG_LEVEL_DISABLE = 0;
	const LOG_LEVEL_ERROR = 10;
	const LOG_LEVEL_INFO = 20;
	const LOG_LEVEL_DEBUG = 30;

	protected $logLevel = self::LOG_LEVEL_ERROR;

	protected $oAuthToken = null;
	protected $oAuthClientId = null;
	protected $oAuthLogin = null;

	protected $mapDelivery = array();
	protected $outlets = array();
	protected $mapPaySystems = array();

	protected $personTypeId = null;
	protected $campaignId = null;
	protected $yandexApiUrl = null;
	protected $yandexToken = null;
	protected $orderProps = array(
		"FIO" => "FIO",
		"EMAIL" => "EMAIL",
		"PHONE" => "PHONE",
		"ZIP" => "ZIP",
		"CITY" => "CITY",
		"LOCATION" => "LOCATION",
		"ADDRESS" => "ADDRESS"
	);

	protected $locationMapper = null;
	protected $active = true;

	protected static $isYandexRequest = false;

	public function __construct($arParams = array())
	{
		$this->siteId = $this->getSiteId($arParams);

		$settings = $this->getSettingsBySiteId($this->siteId);

		if(isset($settings["OAUTH_TOKEN"]))
			$this->oAuthToken = $settings["OAUTH_TOKEN"];

		if(isset($settings["OAUTH_CLIENT_ID"]))
			$this->oAuthClientId = $settings["OAUTH_CLIENT_ID"];

		if(isset($settings["OAUTH_LOGIN"]))
			$this->oAuthLogin = $settings["OAUTH_LOGIN"];

		if(isset($settings["DELIVERIES"]))
			$this->mapDelivery = $settings["DELIVERIES"];

		if(isset($settings["OUTLETS_IDS"]))
			$this->outlets = $settings["OUTLETS_IDS"];

		if(isset($settings["PAY_SYSTEMS"]))
			$this->mapPaySystems = $settings["PAY_SYSTEMS"];

		if(isset($settings["PERSON_TYPE"]))
			$this->personTypeId = $settings["PERSON_TYPE"];

		if(isset($settings["CAMPAIGN_ID"]))
			$this->campaignId = $settings["CAMPAIGN_ID"];

		if(isset($settings["YANDEX_URL"]))
			$this->yandexApiUrl = $settings["YANDEX_URL"];

		if(isset($settings["YANDEX_TOKEN"]))
			$this->yandexToken = $settings["YANDEX_TOKEN"];

		if(isset($settings["AUTH_TYPE"]))
			$this->authType = $settings["AUTH_TYPE"];

		if(isset($settings["DATA_FORMAT"]))
			$this->communicationFormat = $settings["DATA_FORMAT"];

		if(isset($settings["LOG_LEVEL"]))
			$this->logLevel = $settings["LOG_LEVEL"];

		if(isset($settings["ORDER_PROPS"]) && is_array($settings["ORDER_PROPS"]))
			$this->orderProps = $settings["ORDER_PROPS"];

		$this->active = static::isActive();
		$this->locationMapper = new CSaleYMLocation;
	}

	public static function isActive()
	{
		return YandexMarket::getInstance()->isActive();
	}

	/**
	 * @param bool $activity Set or unset activity
	 * @return \Bitrix\Main\Entity\UpdateResult|bool
	 */
	public static function setActivity($activity)
	{
		if($activity)
			static::eventsStart();
		else
			static::eventsStop();

		$settings = static::getSettings();

		if($activity && empty($settings) && static::install())
		{
				$settings = static::getSettings(false);
		}

		if(!empty($settings))
		{
			if($activity)
				$result = YandexMarket::getInstance()->setActive();
			else
				$result = YandexMarket::getInstance()->unsetActive();
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function checkSiteId($siteId)
	{
		$result = false;
		$rsSites = CSite::GetList($b = "", $o = "", Array(
			"LID" => $siteId,
			"ACTIVE"=>"Y"
		));

		if($arRes = $rsSites->Fetch())
			$result = true;

		return $result;
	}

	protected function getSiteId($arParams)
	{
		$result = "";

		if(
			isset($arParams["SITE_ID"])
			&& strlen($arParams["SITE_ID"]) > 0
			&& $this->checkSiteId($arParams["SITE_ID"])
		)
		{
			$result = $arParams["SITE_ID"];
		}
		elseif(defined("SITE_ID"))
		{
			$result = SITE_ID;
		}
		else
		{
			$rsSites = CSite::GetList($b = "", $o = "", Array(
				"ACTIVE"=> "Y",
				"DEF" => "Y"
			));

			if($arRes = $rsSites->Fetch())
				$result = $arRes["LID"];
		}

		return $result;
	}

	/**
	 * Returns Yandex-Market settings
	 * @param bool $cached Return cached or ont value
	 * @return array|bool
	 */
	public static function getSettings($cached = true)
	{
		static $settings = null;

		if($settings === null || !$cached)
		{
			$settingsRes = Bitrix\Sale\TradingPlatformTable::getList(array(
				'filter'=>array('=CODE' => static::TRADING_PLATFORM_CODE)
			));

			$settings = $settingsRes->fetch();

			if(!$settings)
				$settings = array();
		}

		return $settings;
	}

	/**
	 * Returns yandex-market settings for concrete site
	 * @param $siteId string Site idenifier
	 * @param bool $cached Return cached or ont value
	 * @return array
	 */
	public static function getSettingsBySiteId($siteId, $cached = true)
	{
		$settings = static::getSettings($cached);
		return isset($settings["SETTINGS"][$siteId]) ? $settings["SETTINGS"][$siteId] : array();
	}

	/**
	 * Saves settings
	 * @param $arSettings array Settings array to save
	 * @return bool
	 */
	public static function saveSettings($arSettings)
	{
		if(!is_array($arSettings))
			return false;

		foreach ($arSettings as $siteId => $siteSett)
		{
			if(isset($siteSett["OUTLETS_IDS"]) && is_array($siteSett["OUTLETS_IDS"]))
			{
				$newOutletsIds = array();

				foreach ($siteSett["OUTLETS_IDS"] as $outletId)
					if(strlen($outletId) > 0)
						$newOutletsIds[] = $outletId;

				$arSettings[$siteId]["OUTLETS_IDS"] = $newOutletsIds;
			}
		}

		$settings = static::getSettings(false);

		if(!empty($settings))
		{
			if(is_array($settings))
			$result = Bitrix\Sale\TradingPlatformTable::update(
				YandexMarket::getInstance()->getId(),
				array("SETTINGS" => $arSettings)
			);
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	protected function getProductById($productId, $quantity)
	{
		$arResult = array();

		if(CModule::IncludeModule('catalog'))
		{

			if ($productProvider = CSaleBasket::GetProductProvider(array(
					"MODULE" => "catalog",
					"PRODUCT_PROVIDER_CLASS" => "CCatalogProductProvider"))
			)
			{
				$arResult = $productProvider::GetProductData(array(
					"PRODUCT_ID" => $productId,
					"RENEWAL"    => "N",
					"QUANTITY" => $quantity,
					"SITE_ID"    => $this->siteId
				));
			}
		}
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_500, GetMessage("SALE_YMH_ERROR_CATALOG_NOT_INSTALLED"));
		}

		return $arResult;
	}

	protected function getItemCartInfo($arItem, $currency)
	{
		$arResult = array();
		$arProduct = $this->getProductById($arItem["offerId"], $arItem["count"]);

		if($arProduct["CURRENCY"] != $currency && \Bitrix\Main\Loader::includeModule('currency'))
		{
				$price = \CCurrencyRates::convertCurrency(
					$arProduct["PRICE"],
					$arProduct["CURRENCY"],
					$currency
				);
		}
		else
		{
			$price = $arProduct["PRICE"];
		}

		if(isset($arProduct["error"]))
		{
			$arResult = $arProduct;
		}
		elseif(!empty($arProduct))
		{
			$arResult = array(
				"feedId" => $arItem["feedId"],
				"offerId" => $arItem["offerId"],
				"price" => round(floatval($price), 2),
				"count" => $arProduct["QUANTITY"],
				"weight" => $arProduct["WEIGHT"]
			);
		}

		return $arResult;
	}

	protected function getTimeInterval($period, $type)
	{
		return new DateInterval(
			'P'.
			($type =='H' ? 'T' : '').
			intval($period).
			$type
		);
	}

	protected function checkTimeInterval($today, $nextDate)
	{
		$interval = $today->diff($nextDate);
		return (intval($interval->format('%a')) <= 92);
	}

	protected function getDeliveryDates($from, $to, $type)
	{
		$from = intval($from);
		$to = intval($to);
		$arResult = array();

		if($from <= $to)
		{
			$today = new DateTime();

			$dateFrom = new DateTime();
			$dateFrom->add($this->getTimeInterval($from, $type));

			if($this->checkTimeInterval($today, $dateFrom))
			{
				$arResult["fromDate"] = $dateFrom->format(self::DATE_FORMAT);

				if($to > 0 && $to != $from)
				{
					$dateTo = $today->add($this->getTimeInterval($to, $type));

					if($this->checkTimeInterval($today, $dateTo))
						$arResult["toDate"] = $dateTo->format(self::DATE_FORMAT);
				}
			}
		}
		return $arResult;
	}

	protected function getDeliveryOptions($delivery, $price, $weight = 0)
	{
		$arResult = array();

		$locationId = $this->locationMapper->getLocationByCityName($delivery["region"]["name"]);

		if($locationId > 0)
		{
			foreach ($this->mapDelivery as $deliveryId => $deliveryType)
			{
				if($deliveryType == "")
					continue;

				$filter = 	array(
					"ID" => $deliveryId,
					"LID" => $this->siteId,
					"ACTIVE" => "Y",
					"LOCATION" => $locationId,
					"+<=ORDER_PRICE_FROM" => $price,
					"+>=ORDER_PRICE_TO" => $price
				);

				if(intval($weight) > 0)
				{
					$filter["+<=WEIGHT_FROM"] = $weight;
					$filter["+>=WEIGHT_TO"] = $weight;
				}

				$dbDelivery = CSaleDelivery::GetList(
					array("SORT"=>"ASC", "NAME"=>"ASC"),
					$filter
				);

				if($arDelivery = $dbDelivery->Fetch())
				{
					$arDates = $this->getDeliveryDates(
						$arDelivery["PERIOD_FROM"],
						$arDelivery["PERIOD_TO"],
						$arDelivery["PERIOD_TYPE"]
					);

					if(!empty($arDates))
					{
						$arDeliveryTmp = array(
							"id" => $arDelivery["ID"],
							"type" =>$deliveryType,
							"serviceName" => substr($arDelivery["NAME"], 0, 50),
							"price" => round(floatval($arDelivery["PRICE"]), 2),
							"dates" => $arDates
						);

						if($deliveryType == "PICKUP" && !empty($this->outlets))
							foreach($this->outlets as $outlet)
								$arDeliveryTmp["outlets"][] = array("id" => intval($outlet));

						$arResult[] = $arDeliveryTmp;
					}
				}
			}
		}

		return $arResult;
	}

	protected function getPaymentMethods()
	{
		$arResult = array();

		foreach ($this->mapPaySystems as $psType => $psId)
			if(isset($psId) && intval($psId) > 0)
				$arResult[] = $psType;

		return $arResult;
	}

	protected function checkCartStructure($arPostData)
	{
		return	isset($arPostData["cart"])
			&& isset($arPostData["cart"]["items"])
			&& is_array($arPostData["cart"]["items"])
			&& isset($arPostData["cart"]["currency"])
			&& isset($arPostData["cart"]["delivery"])
			&& is_array($arPostData["cart"]["delivery"]);
	}

	/*
	 * POST /cart
	 * max timeout 2s.
	 */
	protected function processCartRequest($arPostData)
	{
		$arResult = array();

		if( $this->checkCartStructure($arPostData))
		{
			$arResult["cart"] = array(
				"items" => array()
			);

			$cartPrice = 0;
			$cartWeight = 0;
			$arResult["cart"] = array(
				"items" => array(),
				"paymentMethods" => array(),
				"deliveryOptions" => array()
			);

			foreach ($arPostData["cart"]["items"] as $arItem)
			{
				$item = $this->getItemCartInfo($arItem, $arPostData["cart"]["currency"]);

				if(isset($item["error"]))
				{
					return array($item["error"]);
				}
				elseif(!empty($item))
				{
					$cartPrice = $item["price"]*$item["count"];
					$cartWeight = $item["weight"]*$item["count"];
					unset($item["weight"]);

					$arResult["cart"]["items"][] = $item;
				}
			}

			if(!empty($arResult["cart"]["items"]))
			{
				$arResult["cart"]["deliveryOptions"] = $this->getDeliveryOptions($arPostData["cart"]["delivery"],$cartPrice, $cartWeight);

				if(!empty($arResult["cart"]["deliveryOptions"]))
				{
					foreach($arResult["cart"]["items"] as $item)
					{
						$item["delivery"] = true;
					}

					$arResult["cart"]["paymentMethods"] = $this->getPaymentMethods($arResult["cart"]["deliveryOptions"]);
				}
				else
				{
					$arResult["cart"]["items"] = array();
				}
			}
		}
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_BAD_STRUCTURE"));
		}

		return $arResult;
	}

	protected function checkOrderAcceptStructure($arPostData)
	{
		return	isset($arPostData["order"])
			&& isset($arPostData["order"]["id"])
			&& isset($arPostData["order"]["currency"])
			&& isset($arPostData["order"]["fake"])
			&& isset($arPostData["order"]["items"]) && is_array($arPostData["order"]["items"])
			&& isset($arPostData["order"]["delivery"]) && is_array($arPostData["order"]["delivery"]);
	}


	protected function createUser($buyer, $address, $region)
	{
		$userRegister = array(
			"NAME" => $buyer["firstName"],
			"PERSONAL_MOBILE" => $buyer["phone"]
		);

		if(isset($buyer["middleName"]))
			$userRegister["LAST_NAME"] = $buyer["middleName"];

		if(isset($buyer["lastName"]))
			$userRegister["SECOND_NAME"] = $buyer["lastName"];

		$arPersonal = array("PERSONAL_MOBILE" => $buyer["phone"]);

		$arErrors = array();
		$userId = CSaleUser::DoAutoRegisterUser(
			$buyer["email"],
			$userRegister,
			$this->siteId,
			$arErrors,
			$arPersonal);

		$this->log(
			empty($arErrors) ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR,
			"YMARKET_USER_CREATE",
			$userId ? $userId : print_r($buyer, true),
			empty($arErrors) ? GetMessage("SALE_YMH_USER_PROFILE_CREATED") : print_r($arErrors, true)
		);

		return $userId;
	}

	protected function makeAdditionalOrderProps($address, $buyer, $psId, $deliveryId, $locationId)
	{
		$psId = intval($psId);

		$arResult = array();

		$arPropFilter = array(
			"PERSON_TYPE_ID" => $this->personTypeId,
			"ACTIVE" => "Y"
		);

		if ($psId != 0)
		{
			$arPropFilter["RELATED"]["PAYSYSTEM_ID"] = $psId;
			$arPropFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		if (strlen($deliveryId) > 0)
		{
			$arPropFilter["RELATED"]["DELIVERY_ID"] = $deliveryId;
			$arPropFilter["RELATED"]["TYPE"] = "WITH_NOT_RELATED";
		}

		$dbOrderProps = CSaleOrderProps::GetList(
			array(),
			$arPropFilter,
			false,
			false,
			array("ID", "CODE")
		);

		while ($arOrderProps = $dbOrderProps->Fetch())
		{
			if($arOrderProps["CODE"] == $this->orderProps["FIO"] && !empty($buyer))
			{
				$fio = $buyer["firstName"];

				if(isset($buyer["middleName"]))
					$fio .= ' '.$buyer["middleName"];

				if(isset($buyer["lastName"]))
					$fio .= ' '.$buyer["lastName"];

				$arResult[$arOrderProps["ID"]] = $fio;
			}
			elseif($arOrderProps["CODE"] == $this->orderProps["EMAIL"] && isset($buyer["email"]))
				$arResult[$arOrderProps["ID"]] = $buyer["email"];
			elseif($arOrderProps["CODE"] == $this->orderProps["PHONE"] && isset($buyer["phone"]))
				$arResult[$arOrderProps["ID"]] = $buyer["phone"];
			elseif($arOrderProps["CODE"] == $this->orderProps["ZIP" ] && isset($address["postcode"]))
				$arResult[$arOrderProps["ID"]] = $address["postcode"];
			elseif($arOrderProps["CODE"] == $this->orderProps["CITY"])
				$arResult[$arOrderProps["ID"]] = $address["city"];
			elseif($arOrderProps["CODE"] == $this->orderProps["LOCATION"])
				$arResult[$arOrderProps["ID"]] = $locationId;
			elseif($arOrderProps["CODE"] == $this->orderProps["ADDRESS"])
			{
				$strAddr = "";

				if(isset($address["postcode"]))
					$strAddr .= $address["postcode"].", ";

				$strAddr .= $address["country"].", ".$address["city"].", ";

				if(isset($address["street"]))
					$strAddr .= GetMessage("SALE_YMH_ADDRESS_STREET")." ".$address["street"].", ";

				if(isset($address["subway"]))
					$strAddr .= GetMessage("SALE_YMH_ADDRESS_SUBWAY")." ".$address["subway"].", ";

				$strAddr .= GetMessage("SALE_YMH_ADDRESS_HOUSE")." ".$address["house"];

				if(isset($address["block"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_BLOCK")." ".$address["block"];

				if(isset($address["entrance"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_ENTRANCE")." ".$address["entrance"];

				if(isset($address["entryphone"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_ENTRYPHONE")." ".$address["entryphone"];

				if(isset($address["floor"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_FLOOR")." ".$address["floor"];

				if(isset($address["apartment"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_APARTMENT")." ".$address["apartment"];

				if(isset($address["recipient"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_RECIPIENT")." ".$address["recipient"];

				if(isset($address["phone"]))
					$strAddr .= ", ".GetMessage("SALE_YMH_ADDRESS_PHONE")." ".$address["phone"];

				$arResult[$arOrderProps["ID"]] = $strAddr;
			}
		}

		return $arResult;
	}

	/*
	 *	POST /order/accept timeout 10s
	 */
	protected function processOrderAcceptRequest($arPostData)
	{

		$arResult = array();

		DiscountCompatibility::reInit(DiscountCompatibility::MODE_EXTERNAL, array('SITE_ID' => $this->siteId));

		if( $this->checkOrderAcceptStructure($arPostData))
		{

			$dbRes = \Bitrix\Sale\TradingPlatform\OrderTable::getList(array(
				"filter" => array(
					"TRADING_PLATFORM_ID" => YandexMarket::getInstance()->getId(),
					"EXTERNAL_ORDER_ID" => $arPostData["order"]["id"]
				)
			));

			if(!$orderCorrespondence = $dbRes->fetch())
			{

				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/admin_tool.php");
				$arProducts = array();

				foreach ($arPostData["order"]["items"] as $arItem)
				{
					$arProduct = $this->getProductById($arItem["offerId"], $arItem["count"]);
					$arProduct["PRODUCT_ID"] = $arItem["offerId"];
					$arProduct["MODULE"] = "catalog";
					$arProduct["PRODUCT_PROVIDER_CLASS"] = "CCatalogProductProvider";

					$dbIblockElement = CIBlockElement::GetList(array(), array("ID" => $arItem["offerId"]), false, false, array('XML_ID', 'IBLOCK_EXTERNAL_ID'));
					if($IblockElement = $dbIblockElement->Fetch())
					{
						if(strlen($IblockElement["XML_ID"]) > 0)
							$arProduct["PRODUCT_XML_ID"] = $IblockElement["XML_ID"];

						if(strlen($IblockElement["IBLOCK_EXTERNAL_ID"]) > 0)
							$arProduct["CATALOG_XML_ID"] = $IblockElement["IBLOCK_EXTERNAL_ID"];
					}

					if($arProduct["CAN_BUY"] == "Y")
						$arProducts[] = $arProduct;
				}

				$arOrderProductPrice = fGetUserShoppingCart($arProducts, $this->siteId, "N");

				$arErrors = array();
				$userId = intval(CSaleUser::GetAnonymousUserID());

				$arShoppingCart = CSaleBasket::DoGetUserShoppingCart(
					$this->siteId,
					$userId,
					$arOrderProductPrice,
					$arErrors
				);

				$deliveryId = $arPostData["order"]["delivery"]["id"];
				$paySystemId = $this->mapPaySystems[$arPostData["order"]["paymentMethod"]];
				$locationId = $this->locationMapper->getLocationByCityName($arPostData["order"]["delivery"]["region"]["name"]);

				if($locationId === false)
				{
					$this->log(
						self::LOG_LEVEL_INFO,
						"YMARKET_LOCATION_MAPPING",
						$arPostData["order"]["delivery"]["region"]["name"],
						GetMessage("SALE_YMH_LOCATION_NOT_FOUND")
					);
				}

				$arErrors = $arWarnings = array();
				$arOptions = array();
				$arOrderPropsValues = $this->makeAdditionalOrderProps(
					$arPostData["order"]["delivery"]["address"],
					array(),
					$this->mapPaySystems[$arPostData["order"]["paymentMethod"]],
					$arPostData["order"]["delivery"]["id"],
					$locationId
				);

				$CSaleOrder = new CSaleOrder();

				$arOrder = $CSaleOrder->DoCalculateOrder(
					$this->siteId,
					$userId,
					$arShoppingCart,
					$this->personTypeId,
					$arOrderPropsValues,
					$deliveryId,
					$paySystemId,
					$arOptions,
					$arErrors,
					$arWarnings
				);

				$arErrors = array();
				$arAdditionalFields = array(
					"XML_ID" => self::XML_ID_PREFIX.$arPostData["order"]["id"],
				);

				$arOrder["LID"] = $this->siteId;

				if(isset($arPostData["order"]["notes"]))
					$arAdditionalFields["USER_DESCRIPTION"] = $arPostData["order"]["notes"];

				$orderID = $CSaleOrder->DoSaveOrder($arOrder, $arAdditionalFields, 0, $arErrors);

				$res = \Bitrix\Sale\TradingPlatform\OrderTable::add(array(
					"ORDER_ID" => $orderID,
					"TRADING_PLATFORM_ID" => YandexMarket::getInstance()->getId(),
					"EXTERNAL_ORDER_ID" => $arPostData["order"]["id"]
				));

				if(!$res->isSuccess())
				{
					foreach($res->getErrors() as $error)
					{
						$this->log(
							self::LOG_LEVEL_ERROR,
							"YMARKET_PLATFORM_ORDER_ADD_ERROR",
							$orderID,
							$error
						);
					}
				}
			}
			else
			{
				$orderID = $orderCorrespondence["ORDER_ID"];
			}

			if(intval($orderID > 0))
			{
				$arResult["order"]["accepted"] = true;
				$arResult["order"]["id"] = strval($orderID);
				$this->log(
					self::LOG_LEVEL_INFO,
					"YMARKET_ORDER_CREATE",
					$arPostData["order"]["id"],
					GetMessage("SALE_YMH_ORDER_CREATED")." ".$orderID
				);
			}
			else
			{
				$arResult["order"]["accepted"] = false;
				$arResult["order"]["reason"] = "OUT_OF_DATE";
				$this->log(
					self::LOG_LEVEL_ERROR,
					"YMARKET_ORDER_CREATE",
					$arPostData["order"]["id"],
					print_r($arErrors, true)
				);
			}
		}
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_BAD_STRUCTURE"));
		}

		return $arResult;
	}


	protected function checkOrderStatusRequest($arPostData)
	{
		return
			(isset($arPostData["order"])
			&& isset($arPostData["order"]["id"])
			&& isset($arPostData["order"]["currency"])
			&& isset($arPostData["order"]["creationDate"])
			&& isset($arPostData["order"]["itemsTotal"])
			&& isset($arPostData["order"]["total"])
			&& isset($arPostData["order"]["status"])
			&& isset($arPostData["order"]["fake"])
			&& isset($arPostData["order"]["buyer"])
			&& isset($arPostData["order"]["items"]) && is_array($arPostData["order"]["items"])
			&& isset($arPostData["order"]["delivery"]) && is_array($arPostData["order"]["delivery"])) || true;
	}

	/*
	 *	POST /order/status timeout 10s
	 */
	protected function processOrderStatusRequest($arPostData)
	{
		$arResult = array();
		if($this->checkOrderStatusRequest($arPostData))
		{
			$dbOrder = CSaleOrder::GetList(
				array(),
				array("XML_ID" => self::XML_ID_PREFIX.$arPostData["order"]["id"])
			);

			if($arOrder = $dbOrder->Fetch())
			{
				$reason = "";

				switch ($arPostData["order"]["status"])
				{
					case 'PROCESSING':
						$locationId = $this->locationMapper->getLocationByCityName($arPostData["order"]["delivery"]["region"]["name"]);

						if($locationId === false)
						{
							$this->log(
								self::LOG_LEVEL_INFO,
								"YMARKET_LOCATION_MAPPING",
								$arPostData["order"]["delivery"]["region"]["name"],
								GetMessage("SALE_YMH_LOCATION_NOT_FOUND")
							);
						}

						$arOrderPropsValues = $this->makeAdditionalOrderProps(
							$arPostData["order"]["delivery"]["address"],
							$arPostData["order"]["buyer"],
							isset($this->mapPaySystems[$arPostData["order"]["paymentMethod"]]) ? $this->mapPaySystems[$arPostData["order"]["paymentMethod"]] : "",
							$arPostData["order"]["delivery"]["id"],
							$locationId
						);

						$arErrors = array();

						CSaleOrderProps::DoSaveOrderProps(
							$arOrder["ID"],
							$this->personTypeId,
							$arOrderPropsValues,
							$arErrors
						);

						$this->sendEmailNewOrder($arOrder["ID"], $arPostData["order"]["buyer"]);

						if(!empty($arErrors))
						{
							$this->log(
								self::LOG_LEVEL_ERROR,
								"YMARKET_INCOMING_ORDER_STATUS",
								$arPostData["order"]["id"],
								print_r($arErrors, true));
						}
						else
						{
							$this->log(
								self::LOG_LEVEL_INFO,
								"YMARKET_INCOMING_ORDER_STATUS",
								$arPostData["order"]["id"],
								GetMessage("SALE_YMH_INCOMING_ORDER_STATUS_PROCESSING").": ".$arOrder["ID"]
							);
						}

						if(isset($arPostData["order"]["paymentMethod"]) && $arPostData["order"]["paymentMethod"] == "YANDEX")
							CSaleOrder::PayOrder($arOrder["ID"], "Y");

						break;

					case 'UNPAID':
					case 'DELIVERY':
					case 'PICKUP':
					case 'DELIVERED ':
						break;

					case 'CANCELLED':
						if(isset($arPostData["order"]["substatus"]))
							$reason = GetMessage("SALE_YMH_SUBSTATUS_".$arPostData["order"]["substatus"]);
						break;

					default:
						$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_UNKNOWN_STATUS"));
						break;
				}

				$this->mapYandexStatusToOrder($arOrder, $arPostData["order"]["status"], $reason);
			}
		}
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_BAD_STRUCTURE"));
		}

		return $arResult;
	}

	protected function extractPostData($postData)
	{
		global $APPLICATION;
		$arResult = array();

		if($this->communicationFormat == self::JSON)
		{
			$arResult = json_decode($postData, true);
		}

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$arResult = $APPLICATION->ConvertCharsetArray($arResult, 'utf-8', SITE_CHARSET);

		return $arResult;
	}

	protected function prepareResult($arData)
	{
		global $APPLICATION;
		$result = array();

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$arData = $APPLICATION->ConvertCharsetArray($arData, SITE_CHARSET, 'utf-8');

		if($this->communicationFormat == self::JSON)
		{
			header('Content-Type: application/json');
			$result = json_encode($arData);
		}

		return $result;
	}

	/**
	 * Let's check authorization,
	 * comparing incoming token with token stored in settings.
	 * @return bool
	 */
	public function checkAuth()
	{
		$incomingToken = "";

		if($this->authType == "HEADER")
		{
			if(isset($_SERVER["REMOTE_USER"]) && strlen($_SERVER["REMOTE_USER"]) > 0)
				$incomingToken = $_SERVER["REMOTE_USER"];
			elseif(isset($_SERVER["REDIRECT_REMOTE_USER"]) && strlen($_SERVER["REDIRECT_REMOTE_USER"]) > 0)
				$incomingToken = $_SERVER["REDIRECT_REMOTE_USER"];
			elseif(isset($_SERVER["HTTP_AUTHORIZATION"]) && strlen($_SERVER["HTTP_AUTHORIZATION"]) > 0)
				$incomingToken = $_SERVER["HTTP_AUTHORIZATION"];
		}
		elseif($this->authType == "URL")
		{
			if(isset($_REQUEST["auth-token"]) && strlen($_REQUEST["auth-token"]) > 0)
				$incomingToken = $_REQUEST["auth-token"];
		}

		if($incomingToken == "" && intval($_SERVER["argc"]) > 0)
		{
			foreach ($_SERVER["argv"] as $arg)
			{
				$e = explode("=", $arg);

				if(count($e) == 2 && $e[0] == "auth-token")
					$incomingToken = $e[1];
			}
		}

		return strlen($incomingToken) > 0 && $incomingToken == $this->yandexToken;
	}

	public function processRequest($reqObject, $method, $postData)
	{
		$this->log(
			self::LOG_LEVEL_DEBUG,
			"YMARKET_INCOMING_REQUEST",
			$reqObject.":".$method,
			print_r($postData, true)
		);

		$arResult = array();

		if(!$this->isActive())
		{
			$arResult = $this->processError(self::ERROR_STATUS_503, GetMessage("SALE_YMH_ERROR_OFF"));
		}
		elseif(!$this->checkAuth())
		{
			$arResult = $this->processError(self::ERROR_STATUS_403, GetMessage("SALE_YMH_ERROR_FORBIDDEN"));
		}
		else
		{
			self::$isYandexRequest = true;
			$arPostData = $this->extractPostData($postData);
			DiscountCouponsManager::init(DiscountCouponsManager::MODE_EXTERNAL);

			switch ($reqObject)
			{
				case 'cart':
					$arResult = $this->processCartRequest($arPostData);
					break;

				case 'order':

					if($method == "accept")
						$arResult = $this->processOrderAcceptRequest($arPostData);
					elseif($method == "status")
						$arResult = $this->processOrderStatusRequest($arPostData);
					break;

				default:
					$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_UNKNOWN_REQ_OBJ"));
					break;
			}
		}

		$this->log(
			self::LOG_LEVEL_DEBUG,
			"YMARKET_INCOMING_REQUEST_RESULT",
			$reqObject.":".$method,
			print_r($arResult, true)
		);

		$arPreparedResult = $this->prepareResult($arResult);
		return  $arPreparedResult;
	}


	protected function processError($status = "", $message = "")
	{
		if($status != "")
			CHTTP::SetStatus($status);

		if($message && $this->logLevel >= self::LOG_LEVEL_ERROR)
			$this->log(
				self::LOG_LEVEL_ERROR,
				"YMARKET_REQUEST_ERROR",
				"",
				$message);

		return array("error" => $message);
	}

	public function sendStatus($orderId, $status, $substatus = false)
	{
		global $APPLICATION;

		if(
			strlen($this->yandexApiUrl) <= 0
			|| strlen($this->campaignId) <= 0
			|| intval($orderId) <= 0
			|| strlen($status) <=0
			|| strlen($this->oAuthToken) <=0
			|| strlen($this->oAuthClientId) <=0
			|| strlen($this->oAuthLogin) <=0
		)
			return false;

		$format = $this->communicationFormat == self::JSON ? 'json' : 'xml';
		$url = $this->yandexApiUrl."campaigns/".$this->campaignId."/orders/".$orderId."/status.".$format;

		$http = new \Bitrix\Main\Web\HttpClient(array(
			"version" => "1.1",
			"socketTimeout" => 30,
			"streamTimeout" => 30,
			"redirect" => true,
			"redirectMax" => 5,
		));

		$arQuery = array(
			"order" => array(
				"status" => $status,
			)
		);

		if($substatus)
			$arQuery["order"]["substatus"] = $substatus;

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$arQuery = $APPLICATION->ConvertCharsetArray($arQuery, SITE_CHARSET, 'utf-8');

		$postData = '';
		if($this->communicationFormat == self::JSON)
			$postData = json_encode($arQuery);

		$http->setHeader("Content-Type", "application/".$format);
		$http->setHeader("Authorization", 'OAuth oauth_token="'.$this->oAuthToken.
					'", oauth_client_id="'.$this->oAuthClientId.
					'", oauth_login="'.$this->oAuthLogin.'"', false);


		$result = $http->query("PUT", $url, $postData);
		$errors = $http->getError();

		if (!$result && !empty($errors))
		{
			$bResult = false;
			$message = "HTTP ERROR: ";

			foreach($errors as $errorCode => $errMes)
				$message .= $errorCode.": ".$errMes;
		}
		else
		{
			$headerStatus = $http->getStatus();

			if ($headerStatus == 200)
			{
				$message = GetMessage("SALE_YMH_STATUS").": ".$status;
				$bResult = true;
			}
			else
			{
				$res = 	$http->getResult();
				$message = "HTTP error code: ".$headerStatus."(".$res.")";

				if($headerStatus =="403")
					$this->notifyAdmin("SEND_STATUS_ERROR_403");

				$bResult = false;
			}
		}

		$this->log(
			$bResult ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR,
			"YMARKET_STATUS_CHANGE",
			$orderId,
			$message
		);

		return $bResult;
	}

	static public function getOrderInfo($orderId)
	{
		$res = \Bitrix\Sale\Internals\OrderTable::getList(array(
			'filter' => array(
				'=ID' => $orderId,
				'=SOURCE.TRADING_PLATFORM_ID' => YandexMarket::getInstance()->getId()
			),
			'select' => array("LID", "XML_ID", "YANDEX_ID" => "SOURCE.EXTERNAL_ORDER_ID"),
			'runtime' => array(
				'SOURCE' => array(
					'data_type' => '\Bitrix\Sale\TradingPlatform\OrderTable',
					'reference' => array(
						'ref.ORDER_ID' => 'this.ID',
					),
				'join_type' => 'left'
				)
			)
		));

		if($arOrder = $res->fetch())
				return $arOrder;

		return array();
	}

	public static function isOrderFromYandex($orderId)
	{
		$arOrder = self::getOrderInfo($orderId);
		return !empty($arOrder["YANDEX_ID"]);
	}

	/**
	 * Executes when order's status was changed in shop
	 * event OnSaleCancelOrder
	 * @param int $orderId Identifier
	 * @param string $status New status
	 * @param string $substatus Substatus.
	 * @return bool
	 */
	static public function onSaleStatusOrder($orderId, $status, $substatus = false)
	{
		$result = false;
		$arOrder = self::getOrderInfo($orderId);

		if(!empty($arOrder) && isset($arOrder["YANDEX_ID"]) && !self::$isYandexRequest)
		{
			$YMHandler = new CSaleYMHandler(
				array("SITE_ID"=> $arOrder["LID"])
			);

			$settings = $YMHandler->getSettingsBySiteId($arOrder["LID"]);

			if(!isset($settings["STATUS_OUT"][$status]) || strlen($settings["STATUS_OUT"][$status]) <= 0)
				return false;

			$yandexStatus = $settings["STATUS_OUT"][$status];
			$YMHandler->sendStatus($arOrder["YANDEX_ID"], $yandexStatus, $substatus);
			$result = true;
		}

		return $result;
	}

	public static function onSaleCancelOrder($orderId, $value, $description)
	{
		if($value != "Y" || self::$isYandexRequest)
			return false;

		global $USER;

		$arSubstatuses = self::getOrderSubstatuses();

		if(strlen($description) <= 0 || !$USER->IsAdmin() || !in_array(trim($description), $arSubstatuses))
			$description = "USER_CHANGED_MIND";
		else
			$description = array_search(trim($description), $arSubstatuses);

		return self::onSaleStatusOrder($orderId, "CANCELED", $description);
	}

	public static function onSaleDeliveryOrder($orderId, $value)
	{
		if($value != "Y" || self::$isYandexRequest)
			return false;

		return self::onSaleStatusOrder($orderId, "ALLOW_DELIVERY");
	}

	public static function onSalePayOrder($orderId, $value)
	{
		if($value != "Y" || self::$isYandexRequest)
			return false;

		return self::onSaleStatusOrder($orderId, "PAYED");
	}

	public static function onSaleDeductOrder($orderId, $value)
	{
		if($value != "Y" || self::$isYandexRequest)
			return false;

		return self::onSaleStatusOrder($orderId, "DEDUCTED");
	}

	public static function getOrderSubstatuses()
	{
		return array(
			"USER_UNREACHABLE" => GetMessage("SALE_YMH_SUBSTATUS_USER_UNREACHABLE"),
			"USER_CHANGED_MIND" => GetMessage("SALE_YMH_SUBSTATUS_USER_CHANGED_MIND"),
			"USER_REFUSED_DELIVERY"=> GetMessage("SALE_YMH_SUBSTATUS_USER_REFUSED_DELIVERY"),
			"USER_REFUSED_PRODUCT" => GetMessage("SALE_YMH_SUBSTATUS_USER_REFUSED_PRODUCT"),
			"SHOP_FAILED" => GetMessage("SALE_YMH_SUBSTATUS_SHOP_FAILED"),
			"REPLACING_ORDER" => GetMessage("SALE_YMH_SUBSTATUS_REPLACING_ORDER")
		);
	}

	public static function getCancelReasonsAsSelect($name, $val=false, $id=false)
	{
		$arStatuses = self::getOrderSubstatuses();
		$result = '<select width="100%" name="'.$name.'"';

		if($id !== false)
			$result .= ' id="'.$id.'"';

		$result .='>';
		foreach ($arStatuses as $statusId => $statusName)
		{
			$result .='<option value="'.$statusId.'"';

			if($val == $statusId)
				$result .= ' selected';

			$result .= '>'.$statusName.'</option>';
		}

		$result .='</select>';

		return $result;
	}

	public static function getCancelReasonsAsRadio($name, $id=false, $val=false)
	{
		$result = "";
		$arStatuses = self::getOrderSubstatuses();
		$start = 0;

		if($id === false)
			$id = "cancelreasonid_".rand();

		foreach ($arStatuses as $statusId => $statusName)
		{
			$tmpId = $id.'_'.($start++);
			$result .=
				'<label for="'.$tmpId.'">'.
					'<input id="'.$tmpId.'" type="radio" name="'.$name.'_rb" value="'.$statusId.'">'.
					'<span id="'.$tmpId.'_lbl">'.$statusName.'</span>'.
				'</label><br>'.
				'<script type="text/javascript">'.
					'BX("'.$tmpId.'").onchange=function(){if(this.checked == true) { BX("'.$id.'").innerHTML = BX("'.$tmpId.'_lbl").innerHTML; }};'.
				'</script>';
		}
		return $result;
	}

	static public function OnEventLogGetAuditTypes()
	{
		return array(
			"YMARKET_STATUS_CHANGE" => "[YMARKET_STATUS_CHANGE] ".GetMessage("SALE_YMH_LOG_TYPE_STATUS_CHANGE"),
			"YMARKET_INCOMING_ORDER_STATUS" => "[YMARKET_INCOMING_ORDER_STATUS] ".GetMessage("SALE_YMH_LOG_TYPE_INCOMING_ORDER_STATUS"),
			"YMARKET_USER_CREATE" => "[YMARKET_USER_CREATE] ".GetMessage("SALE_YMH_LOG_TYPE_USER_CREATE"),
			"YMARKET_ORDER_CREATE" => "[YMARKET_ORDER_CREATE] ".GetMessage("SALE_YMH_LOG_TYPE_ORDER_CREATE"),
			"YMARKET_REQUEST_ERROR" => "[YMARKET_REQUEST_ERROR] ".GetMessage("SALE_YMH_LOG_TYPE_REQUEST_ERROR"),
			"YMARKET_INCOMING_REQUEST" => "[YMARKET_INCOMING_REQUEST] ".GetMessage("SALE_YMH_LOG_TYPE_INCOMING_REQUEST"),
			"YMARKET_INCOMING_REQUEST_RESULT" => "[YMARKET_INCOMING_REQUEST_RESULT] ".GetMessage("SALE_YMH_LOG_TYPE_INCOMING_REQUEST_RESULT"),
			"YMARKET_LOCATION_MAPPING" => "[YMARKET_LOCATION_MAPPING] ".GetMessage("SALE_YMH_LOG_TYPE_YMARKET_LOCATION_MAPPING"),
			"YMARKET_ORDER_STATUS_CHANGE" => "[YMARKET_ORDER_STATUS_CHANGE] ".GetMessage("SALE_YMH_LOG_TYPE_ORDER_STATUS_CHANGE"),
		);
	}

	protected function log($level, $type, $itemId, $description)
	{
		if($this->logLevel < $level)
			return false;

		CEventLog::Add(array(
			"SEVERITY" => $level >= CSaleYMHandler::LOG_LEVEL_ERROR ? "WARNING" : "NOTICE",
			"AUDIT_TYPE_ID" => $type,
			"MODULE_ID" => "sale",
			"ITEM_ID" => $itemId,
			"DESCRIPTION" => $description,
		));

		return true;
	}

	protected function getLocationByCityName($cityName)
	{
		return $this->locationMapper->getLocationByCityName($cityName);
	}

	protected function mapYandexStatusToOrder($order, $yandexStatus, $cancelReason="")
	{
		global $APPLICATION;

		if(!is_array($order) || !isset($order["ID"]) || strlen($yandexStatus) <= 0)
			return false;

		$settings = $this->getSettingsBySiteId($order["LID"]);

		if(!isset($settings["STATUS_IN"][$yandexStatus]) || strlen($settings["STATUS_IN"][$yandexStatus]) <= 0)
			return false;

		$result = false;
		$bitrixStatus = $settings["STATUS_IN"][$yandexStatus];

		switch($bitrixStatus)
		{
			/* flags */
			case "CANCELED":

				$errorMessageTmp = "";
				$result = CSaleOrder::CancelOrder($order["ID"], "Y", $cancelReason);

				if (!$result)
				{
					if ($ex = $APPLICATION->GetException())
					{
						if ($ex->GetID() != "ALREADY_FLAG")
							$errorMessageTmp .= $ex->GetString();
					}
					else
						$errorMessageTmp .= GetMessage("ERROR_CANCEL_ORDER").". ";
				}

				if($errorMessageTmp != "")
				{
					$this->log(
						self::LOG_LEVEL_ERROR,
						"YMARKET_INCOMING_ORDER_STATUS",
						$order["XML_ID"],
						$errorMessageTmp
					);
				}
				else
				{
					$this->log(
						self::LOG_LEVEL_INFO,
						"YMARKET_INCOMING_ORDER_STATUS",
						$order["XML_ID"],
						GetMessage("SALE_YMH_INCOMING_ORDER_STATUS_CANCELED").": ".$order["ID"]
					);
				}

				break;

			case "ALLOW_DELIVERY":
				$result = CSaleOrder::DeliverOrder($order["ID"], "Y");
				break;

			case "PAYED":
				$result = CSaleOrder::PayOrder($order["ID"], "Y");
				break;

			case "DEDUCTED":
				$result = CSaleOrder::DeductOrder($order["ID"], "Y");
				break;

			/* statuses */
			default:
				if(CSaleStatus::GetByID($bitrixStatus))
				{
					$result = CSaleOrder::StatusOrder($order["ID"], $bitrixStatus);
				}
				break;
		}

		$this->log(
			$result ? self::LOG_LEVEL_INFO : self::LOG_LEVEL_ERROR,
			"YMARKET_ORDER_STATUS_CHANGE",
			$order["ID"],
			($result ? GetMessage("SALE_YMH_LOG_TYPE_ORDER_STATUS_CHANGE_OK") : GetMessage("SALE_YMH_LOG_TYPE_ORDER_STATUS_CHANGE_ERROR"))." (".$bitrixStatus.")"
		);

		return  $result;
	}

	/**
	 * Starts exchange information between Yandex-market and shop
	 * @return bool
	 */
	public static function eventsStart()
	{
		RegisterModuleDependences("sale", "OnSaleStatusOrder", "sale", "CSaleYMHandler", "onSaleStatusOrder", 100);
		RegisterModuleDependences("sale", "OnSaleCancelOrder", "sale", "CSaleYMHandler", "onSaleCancelOrder", 100);
		RegisterModuleDependences("sale", "OnSalePayOrder", "sale", "CSaleYMHandler", "onSalePayOrder", 100);
		RegisterModuleDependences("sale", "OnSaleDeliveryOrder", "sale", "CSaleYMHandler", "onSaleDeliveryOrder", 100);
		RegisterModuleDependences("sale", "OnSaleDeductOrder", "sale", "CSaleYMHandler", "onSaleDeductOrder", 100);

		return true;
	}

	/**
	 * Stops exchange information between Yandex-market and shop
	 * @return bool
	 */
	public static function eventsStop()
	{
		UnRegisterModuleDependences("sale", "OnSaleStatusOrder", "sale", "CSaleYMHandler", "onSaleStatusOrder");
		UnRegisterModuleDependences("sale", "OnSaleCancelOrder", "sale", "CSaleYMHandler", "onSaleCancelOrder");
		UnRegisterModuleDependences("sale", "OnSalePayOrder", "sale", "CSaleYMHandler", "onSalePayOrder");
		UnRegisterModuleDependences("sale", "OnSaleDeliveryOrder", "sale", "CSaleYMHandler", "onSaleDeliveryOrder");
		UnRegisterModuleDependences("sale", "OnSaleDeductOrder", "sale", "CSaleYMHandler", "onSaleDeductOrder");

		return true;
	}

	/**
	 * Installs service
	 * @return bool
	 */
	public static function install()
	{
		$settings = static::getSettings();

		if(empty($settings))
		{
			$res =  Bitrix\Sale\TradingPlatformTable::add(array(
				"CODE" => static::TRADING_PLATFORM_CODE,
				"ACTIVE" => "N",
				"NAME" => GetMessage("SALE_YMH_NAME"),
				"DESCRIPTION" => GetMessage("SALE_YMH_DESCRIPTION"),
				"SETTINGS" => "",
			));

			$b = "sort";
			$o = "asc";
			$dbSites = \CSite::GetList($b, $o, array("ACTIVE" => "Y"));

			while ($site = $dbSites->Fetch())
			{
				\CUrlRewriter::Add(
					array(
						"CONDITION" => "#^/bitrix/services/ymarket/#",
						"RULE" => "",
						"ID" => "",
						"PATH" => "/bitrix/services/ymarket/index.php",
						"SITE_ID" => $site["ID"]
					)
				);
			}
		}
		else
		{
			$res = true;
		}

		return $res ? true : false;
	}

	/**
	 * Uninstalls service
	 * @param bool $deleteRecord Delete, or not table record about this service
	 */
	public static function unInstall($deleteRecord = true)
	{
		static::eventsStop();

		$settings = static::getSettings();

		if(!empty($settings))
		{
			if($deleteRecord)
				Bitrix\Sale\TradingPlatformTable::delete(static::TRADING_PLATFORM_CODE);
			else
				static::setActivity(false);
		}

		\CUrlRewriter::Delete(
			array(
				"CONDITION" => "#^/bitrix/services/ymarket/#",
				"PATH" => "/bitrix/services/ymarket/index.php"
			)
		);
	}

	/**
	 * Moves settings from options to DB
	 */
	public static function settingsConverter()
	{
		$settings = static::getSettings();

		if(!empty($settings) && !empty($settings["SETTINGS"]))
		{
			return false;
		}

		if(!CSaleYMHandler::install())
		{
			return false;
		}

		$settings = array();

		$rsSites = CSite::GetList($by = "sort", $order = "asc", Array());

		while ($arSite = $rsSites->Fetch())
		{
			$serSiteSett = COption::GetOptionString("sale", "yandex_market_purchase_settings", "", $arSite["ID"], true);
			$siteSett = unserialize($serSiteSett);

			if(is_array($siteSett) && !empty($siteSett))
				$settings[$arSite["ID"]] = $siteSett;
		}

		if(empty($settings))
		{
			$serSiteSett = COption::GetOptionString("sale", "yandex_market_purchase_settings", "");
			$siteSett = unserialize($serSiteSett);

			if(is_array($siteSett) && !empty($siteSett))
				$settings[CSite::GetDefSite()] = $siteSett;
		}

		if(empty($settings))
		{
			return false;
		}

		if(!CSaleYMHandler::saveSettings($settings))
		{
			return false;
		}

		if(!CSaleYMHandler::setActivity(true))
		{
			return false;
		}

		if(!CSaleYMHandler::eventsStart())
		{
			return false;
		}

		return true;
	}

	protected function sendEmailNewOrder($newOrderId, $buyer)
	{
		global $DB;

		$strOrderList = "";
		$baseLangCurrency = CSaleLang::GetLangCurrency($this->siteId);
		$orderNew = CSaleOrder::GetByID($newOrderId);
		$orderNew["BASKET_ITEMS"] = array();

		$userEmail = $buyer["email"];
		$fio = $buyer["last-name"].(isset($buyer["first-name"]) ? $buyer["first-name"] : "");

		$dbBasketTmp = CSaleBasket::GetList(
			array("SET_PARENT_ID" => "DESC", "TYPE" => "DESC", "NAME" => "ASC"),
			array("ORDER_ID" => $newOrderId),
			false,
			false,
			array(
				"ID", "PRICE", "QUANTITY", "NAME"
			)
		);

		while ($arBasketTmp = $dbBasketTmp->GetNext())
		{
			$orderNew["BASKET_ITEMS"][] = $arBasketTmp;
		}

		$orderNew["BASKET_ITEMS"] = getMeasures($orderNew["BASKET_ITEMS"]);

		foreach ($orderNew["BASKET_ITEMS"] as $val)
		{
			if (CSaleBasketHelper::isSetItem($val))
				continue;

			$measure = (isset($val["MEASURE_TEXT"])) ? $val["MEASURE_TEXT"] : GetMessage("SALE_YMH_SHT");
			$strOrderList .= $val["NAME"]." - ".$val["QUANTITY"]." ".$measure.": ".SaleFormatCurrency($val["PRICE"], $baseLangCurrency);
			$strOrderList .= "\n";
		}

		//send mail
		$arFields = array(
			"ORDER_ID" => $orderNew["ACCOUNT_NUMBER"],
			"ORDER_DATE" => Date($DB->DateFormatToPHP(CLang::GetDateFormat("SHORT", $this->siteId))),
			"ORDER_USER" => $fio,
			"PRICE" => SaleFormatCurrency($orderNew["PRICE"], $baseLangCurrency),
			"BCC" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
			"EMAIL" => array("PAYER_NAME" => $fio, "USER_EMAIL" => $userEmail),
			"ORDER_LIST" => $strOrderList,
			"SALE_EMAIL" => COption::GetOptionString("sale", "order_email", "order@".$_SERVER['SERVER_NAME']),
			"DELIVERY_PRICE" => $orderNew["DELIVERY_PRICE"],
		);

		$eventName = "SALE_NEW_ORDER";

		$bSend = true;
		foreach(GetModuleEvents("sale", "OnOrderNewSendEmail", true) as $arEvent)
			if (ExecuteModuleEventEx($arEvent, array($newOrderId, &$eventName, &$arFields))===false)
				$bSend = false;

		if($bSend)
		{
			$event = new CEvent;
			$event->Send($eventName, $this->siteId, $arFields, "N");
		}

		CSaleMobileOrderPush::send("ORDER_CREATED", array("ORDER" => $orderNew));
	}

	protected function notifyAdmin($code)
	{
		$tag = "YANDEX_MARKET_".$code;
		$problemsCount = intval(\Bitrix\Main\Config\Option::get("sale", $tag, 0, $this->siteId));

		if($problemsCount < 3)
		{
			\Bitrix\Main\Config\Option::set("sale", $tag, $problemsCount+1, $this->siteId);
			return false;
		}

		$dbRes = CAdminNotify::GetList(array(), array("TAG" => $tag));

		if($res = $dbRes->Fetch())
			return false;

		CAdminNotify::Add(array(
				"MESSAGE" => GetMessage("SALE_YMH_ADMIN_NOTIFY_".$code, array("##LANGUAGE_ID##" => LANGUAGE_ID)),
				"TAG" => "YANDEX_MARKET_".$code,
				"MODULE_ID" => "SALE",
				"ENABLE_CLOSE" => "Y"
			)
		);

		\Bitrix\Main\Config\Option::set("sale", $tag, 0, $this->siteId);

		return true;
	}

	/*
	 * Take out correnspondence to
	 */
	public static function takeOutOrdersToCorrespondentTable()
	{
		$platformId = \Bitrix\Sale\TradingPlatform\YandexMarket::getInstance()->getId();
		$conn = \Bitrix\Main\Application::getConnection();
		$helper = $conn->getSqlHelper();

		$correspondence = $conn->query(
			'SELECT ID
				FROM '.$helper->quote(\Bitrix\Sale\TradingPlatform\OrderTable::getTableName()).'
				WHERE '.$helper->quote('TRADING_PLATFORM_ID').'='.$platformId
		);

		//check if we already tried to convert
		if ($correspondence->fetch())
			return;

		if($conn->getType() == "mssql")
			$lenOpName = "LEN";
		else
			$lenOpName = "LENGTH";

		//take out correspondence to
		$sql = 'INSERT INTO '.\Bitrix\Sale\TradingPlatform\OrderTable::getTableName().' (ORDER_ID, EXTERNAL_ORDER_ID, TRADING_PLATFORM_ID)
				SELECT ID, RIGHT(XML_ID, '.$lenOpName.'(XML_ID)-'.strlen(self::XML_ID_PREFIX).'), '.$platformId.'
					FROM '.\Bitrix\Sale\Internals\OrderTable::getTableName().'
					WHERE XML_ID LIKE "'.self::XML_ID_PREFIX.'%"';

		try
		{
			$conn->queryExecute($sql);
		}
		catch(\Bitrix\Main\DB\SqlQueryException $e)
		{
			CEventLog::Add(array(
				"SEVERITY" => "ERROR",
				"AUDIT_TYPE_ID" => "YMARKET_XML_ID_CONVERT_INSERT_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => "YMARKET",
				"DESCRIPTION" => __FILE__.': '.$e->getMessage(),
			));
		}

		return "";
	}
}