<?
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

	private $communicationFormat = self::JSON;
	private $siteId = "";
	private $authType = "HEADER"; // or URL

	const LOG_LEVEL_DISABLE = 0;
	const LOG_LEVEL_ERROR = 10;
	const LOG_LEVEL_INFO = 20;
	const LOG_LEVEL_DEBUG = 30;

	private $logLevel = self::LOG_LEVEL_ERROR;

	private $oAuthToken = null;
	private $oAuthClientId = null;
	private $oAuthLogin = null;

	private $mapDelivery = array();
	private $outlets = array();
	private $mapPaySystems = array();

	private $personTypeId = null;
	private $campaignId = null;
	private $yandexApiUrl = null;
	private $yandexToken = null;

	private $locationMapper = null;

	private static $isYandexRequest = false;

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

		$this->locationMapper = new CSaleYMLocation;
	}

	private function checkSiteId($siteId)
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

	private function getSiteId($arParams)
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

	public static function getSettings()
	{
		$result = array();
		$siteList = array();
		$rsSites = CSite::GetList($by = "sort", $order = "asc", Array());

		while ($arSite = $rsSites->Fetch())
			$result[$arSite["ID"]] = self::getSettingsBySiteId($arSite["ID"]);

		if(empty($result))
			$result = unserialize(COption::GetOptionString("sale", "yandex_market_purchase_settings", ""));

		return $result;
	}

	public static  function getSettingsBySiteId($siteId)
	{
		return unserialize(COption::GetOptionString("sale", "yandex_market_purchase_settings", "", $siteId, true));
	}

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

			COption::SetOptionString("sale", "yandex_market_purchase_settings", serialize($arSettings[$siteId]), "", $siteId);
		}

		return true;
	}

	private function getProductById($productId, $quantity)
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

	private function getItemCartInfo($arItem, $currency, $bDelivery)
	{
		$arResult = array();
		$arProduct = $this->getProductById($arItem["offerId"], $arItem["count"]);

		if(isset($arProduct["error"]))
		{
			$arResult = $arProduct;
		}
		elseif(!empty($arProduct))
		{
			$arResult = array(
				"feedId" => $arItem["feedId"],
				"offerId" => $arItem["offerId"],
				"price" => round(floatval($arProduct["PRICE"]), 2),
				"count" => $arProduct["QUANTITY"],
				"delivery" => $bDelivery
			);
		}

		return $arResult;
	}

	private function getTimeInterval($period, $type)
	{
		return new DateInterval(
			'P'.
			($type =='H' ? 'T' : '').
			intval($period).
			$type
		);
	}

	private function checkTimeInterval($today, $nextDate)
	{
		$interval = $today->diff($nextDate);
		return (intval($interval->format('%a')) <= 92);
	}

	private function getDeliveryDates($from, $to, $type)
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
					$dateTo = new DateTime();
					$dateTo = $today->add($this->getTimeInterval($to, $type));

					if($this->checkTimeInterval($today, $dateTo))
						$arResult["toDate"] = $dateTo->format(self::DATE_FORMAT);
				}
			}
		}
		return $arResult;
	}

	private function getDeliveryOptions($delivery)
	{
		$arResult = array();

		$locationId = $this->locationMapper->getLocationByCityName($delivery["region"]["name"]);

		if($locationId > 0)
		{

			foreach ($this->mapDelivery as $deliveryId => $deliveryType)
			{
				if($deliveryType == "")
					continue;

				$dbDelivery = CSaleDelivery::GetList(
					array("SORT"=>"ASC", "NAME"=>"ASC"),
					array(
						"ID" => $deliveryId,
						"LID" => $this->siteId,
						"ACTIVE" => "Y",
						"LOCATION" => $locationId
					)
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

	private function getPaymentMethods()
	{
		$arResult = array();

		foreach ($this->mapPaySystems as $psType => $psId)
			if(isset($psId) && intval($psId) > 0)
				$arResult[] = $psType;

		return $arResult;
	}

	private function checkCartStructure($arPostData)
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
	private function processCartRequest($arPostData)
	{

		$arResult = array();

		if( $this->checkCartStructure($arPostData))
		{
			$arResult["cart"] = array(
				"items" => array()
			);

			$arResult["cart"]["deliveryOptions"] = $this->getDeliveryOptions($arPostData["cart"]["delivery"]);

			$bDelivery = !empty($arResult["cart"]["deliveryOptions"]) ? true : false;

			if($bDelivery)
			{
				foreach ($arPostData["cart"]["items"] as $arItem)
				{
					$item = $this->getItemCartInfo($arItem, $arPostData["cart"]["currency"], $bDelivery);

					if(!isset($item["error"]))
						$arResult["cart"]["items"][] = $item;
					else
						return array($item["error"]);
				}
			}
			else
			{
				$arResult["cart"]["items"] = array();
			}

			$arResult["cart"]["paymentMethods"] = $this->getPaymentMethods($arResult["cart"]["deliveryOptions"]);
		}
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_BAD_STRUCTURE"));
		}

		return $arResult;
	}

	private function checkOrderAcceptStructure($arPostData)
	{
		return	isset($arPostData["order"])
			&& isset($arPostData["order"]["id"])
			&& isset($arPostData["order"]["currency"])
			&& isset($arPostData["order"]["fake"])
			&& isset($arPostData["order"]["items"]) && is_array($arPostData["order"]["items"])
			&& isset($arPostData["order"]["delivery"]) && is_array($arPostData["order"]["delivery"]);
	}


	private function createUser($buyer, $address, $region)
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

	private function makeAdditionalOrderProps($address, $buyer, $psId, $deliveryId, $locationId)
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
			array("ID" => "ASC"),
			$arPropFilter,
			false,
			false,
			array("ID", "NAME", "TYPE", "REQUIED", "IS_LOCATION", "IS_EMAIL", "IS_PROFILE_NAME", "IS_PAYER", "IS_LOCATION4TAX", "CODE", "SORT")
		);

		while ($arOrderProps = $dbOrderProps->Fetch())
		{

			if($arOrderProps["CODE"] == "FIO" && !empty($buyer))
			{
				$fio = $buyer["firstName"];

				if(isset($buyer["middleName"]))
					$fio .= ' '.$buyer["middleName"];

				if(isset($buyer["lastName"]))
					$fio .= ' '.$buyer["lastName"];

				$arResult[$arOrderProps["ID"]] = $fio;
			}
			elseif($arOrderProps["CODE"] == "EMAIL" && isset($buyer["email"]))
				$arResult[$arOrderProps["ID"]] = $buyer["email"];
			elseif($arOrderProps["CODE"] == "PHONE" && isset($buyer["phone"]))
				$arResult[$arOrderProps["ID"]] = $buyer["phone"];
			elseif($arOrderProps["CODE"] == "ZIP" && isset($address["postcode"]))
				$arResult[$arOrderProps["ID"]] = $address["postcode"];
			elseif($arOrderProps["CODE"] == "CITY")
				$arResult[$arOrderProps["ID"]] = $address["city"];
			elseif($arOrderProps["CODE"] == "LOCATION")
				$arResult[$arOrderProps["ID"]] = $locationId;
			elseif($arOrderProps["CODE"] == "ADDRESS")
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
	private function processOrderAcceptRequest($arPostData)
	{

		$arResult = array();

		if( $this->checkOrderAcceptStructure($arPostData))
		{
			$dbOrder = CSaleOrder::GetList(
				array(),
				array("XML_ID" => self::XML_ID_PREFIX.$arPostData["order"]["id"]),
				false,
				false,
				array("ID")
			);

			if(!$arOrder = $dbOrder->Fetch())
			{

				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/admin_tool.php");
				$arProducts = array();

				foreach ($arPostData["order"]["items"] as $arItem)
				{
					$arProduct = $this->getProductById($arItem["offerId"], $arItem["count"]);
					$arProduct["PRODUCT_ID"] = $arItem["offerId"];

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

				$arOrderPropsValues = array();
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
					"XML_ID" => self::XML_ID_PREFIX.$arPostData["order"]["id"]
				);

				$arOrder["LID"] = $this->siteId;

				if(isset($arPostData["order"]["notes"]))
					$arAdditionalFields["USER_DESCRIPTION"] = $arPostData["order"]["notes"];

				$orderID = $CSaleOrder->DoSaveOrder($arOrder, $arAdditionalFields, 0, $arErrors);
			}
			else
			{
				$orderID = $arOrder["ID"];
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


	private function checkOrderStatusRequest($arPostData)
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
	private function processOrderStatusRequest($arPostData)
	{
		global $APPLICATION;
		$arResult = array();
		if($this->checkOrderStatusRequest($arPostData))
		{
			$dbOrder = CSaleOrder::GetList(
				array(),
				array("XML_ID" => self::XML_ID_PREFIX.$arPostData["order"]["id"])
			);

			if($arOrder = $dbOrder->Fetch())
			{

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

						break;

					case 'DELIVERY':
					case 'PICKUP':
					case 'DELIVERED ':
						break;

					case 'CANCELLED':

						if(isset($arPostData["order"]["substatus"]))
							$reason = GetMessage("SALE_YMH_SUBSTATUS_".$arPostData["order"]["substatus"]);
						else
							$reason = "";

						$errorMessageTmp = "";
						if (!CSaleOrder::CancelOrder($arOrder["ID"], "Y", $reason))
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
								$arPostData["order"]["id"],
								$errorMessageTmp
							);
						}
						else
						{
							$this->log(
								self::LOG_LEVEL_INFO,
								"YMARKET_INCOMING_ORDER_STATUS",
								$arPostData["order"]["id"],
								GetMessage("SALE_YMH_INCOMING_ORDER_STATUS_CANCELED").": ".$arOrder["ID"]
							);
						}

						break;

					default:
						$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_UNKNOWN_STATUS"));
						break;
				}
			}
		}
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_400, GetMessage("SALE_YMH_ERROR_BAD_STRUCTURE"));
		}

		return $arResult;
	}

	private function extractPostData($postData)
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

	private function prepareResult($arData)
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

		if($this->checkAuth())
		{
			self::$isYandexRequest = true;
			$arPostData = $this->extractPostData($postData);

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
		else
		{
			$arResult = $this->processError(self::ERROR_STATUS_401, GetMessage("SALE_YMH_ERROR_UNAUTH"));
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


	private function processError($status = "", $message = "")
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

		$arQuery = array(
			"order" => array(
				"status" => $status,
			)
		);

		if($substatus)
			$arQuery["order"]["substatus"] = $substatus;

		$postdata = '';
		if($this->communicationFormat == self::JSON)
			$postdata = json_encode($arQuery);

		$opts = array(
			'http' => array(
				"method"  => "PUT",
				"header" => array(
					'Authorization: OAuth oauth_token="'.$this->oAuthToken.
					'", oauth_client_id="'.$this->oAuthClientId.
					'", oauth_login="'.$this->oAuthLogin.'"',
					'Content-Type: application/'.$format
				),
				"content" => $postdata,
				"ignore_errors" => true
			)
		);

		$context = stream_context_create($opts);
		$result = file_get_contents($url, false, $context);

		$matches = array();

		preg_match('#HTTP/\d+\.\d+ (\d+)#', $http_response_header[0], $matches);
		$headerStatus = $matches[1];


		if($headerStatus == "200")
		{
			$bResult = true;
			$message = GetMessage("SALE_YMH_STATUS").": ".$status;
		}
		else
		{
			$message = $http_response_header[0].":".$result;
			$bResult = false;
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
		$arOrder = array();

		$dbOrder = CSaleOrder::GetList(
			array(),
			array("ID" => $orderId),
			false,
			false,
			array("XML_ID", "LID")
		);

		if($arOrder = $dbOrder->Fetch())
			if(!is_null($arOrder["XML_ID"]) && strpos($arOrder["XML_ID"], self::XML_ID_PREFIX) !== false)
				$arOrder["YANDEX_ID"] = substr($arOrder["XML_ID"], strlen(self::XML_ID_PREFIX));

		return $arOrder;
	}

	public static function isOrderFromYandex($orderId)
	{
		$arOrder = self::getOrderInfo($orderId);
		return isset($arOrder["YANDEX_ID"]);
	}

	static public function onSaleStatusOrder($orderId, $status)
	{
		$result = false;

		if(self::isOrderFromYandex($orderId) && ($status == "F" || $status == "P") && !self::$isYandexRequest)
		{
			$arOrder = self::getOrderInfo($orderId);

			if(!empty($arOrder))
			{
				$YMHandler = new CSaleYMHandler(
					array("SITE_ID"=> $arOrder["LID"])
				);

				$YMHandler->sendStatus($arOrder["YANDEX_ID"], "DELIVERY");
				$YMHandler->sendStatus($arOrder["YANDEX_ID"], "DELIVERED");
			}

			$result = true;
		}

		return $result;
	}

	static public function onSaleCancelOrder($orderId, $value, $description)
	{
		global $USER;
		$result = false;

		if(self::isOrderFromYandex($orderId) && $value == "Y" && !self::$isYandexRequest)
		{
			$arOrder = self::getOrderInfo($orderId);

			if(!empty($arOrder))
			{
				$arSubstatuses = self::getOrderSubstatuses();
				if(strlen($description) <= 0 || !$USER->IsAdmin() || !in_array(trim($description), $arSubstatuses))
					$description = "USER_CHANGED_MIND";
				else
					$description = array_search(trim($description), $arSubstatuses);

				$YMHandler = new CSaleYMHandler(
					array("SITE_ID"=> $arOrder["LID"])
				);

				$YMHandler->sendStatus($arOrder["YANDEX_ID"], "CANCELLED", $description);
			}

			$result = true;
		}

		return $result;
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
		);
	}

	private function log($level, $type, $itemId, $description)
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

	private function getLocationByCityName($cityName)
	{
		return $this->locationMapper->getLocationByCityName($cityName);
	}
}
?>