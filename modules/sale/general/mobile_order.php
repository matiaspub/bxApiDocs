<?
IncludeModuleLangFile(__FILE__);

class CSaleMobileOrderUtils
{
	public static function GetFormatedUserName($userId)
	{
		static $userCache = array();

		$userId = IntVal($userId);

		if($userId > 0)
		{
			if (!isset($userCache[$userId]) || !is_array($userCache[$userId]))
			{
				$dbUser = CUser::GetByID($userId);
				if ($arUser = $dbUser->Fetch())
				{
					$userCache[$userId] = CUser::FormatName(
							CSite::GetNameFormat(false),
							array(
								"NAME" => $arUser["NAME"],
								"LAST_NAME" => $arUser["LAST_NAME"],
								"SECOND_NAME" => $arUser["SECOND_NAME"],
								"LOGIN" => $arUser["LOGIN"]
							),
							true
						);
				}
			}
		}

		return $userCache[$userId];
	}

	public static function getMobileReports()
	{
		// define('COLUMNS_COUNT_FOR_SIMPLE_TEMPLATE', 3);
		// define('PATH_TO_MOBILE_REPORTS', '/bitrix/admin/mobile/sale_reports_view.php');

		// Using report module
		if (!CModule::IncludeModule('report'))
			return false;

		// Using catalog module
		if (!CModule::IncludeModule('catalog'))
			return false;

		CBaseSaleReportHelper::initOwners();

		$dbRepList = Bitrix\Report\ReportTable::getList(array(
			'select' => array('ID', 'TITLE', 'SETTINGS'),
			'filter' => array('=CREATED_BY' => $GLOBALS["USER"]->GetID(), '=OWNER_ID' => CBaseSaleReportHelper::getOwners())
		));

		$arMenuItems = array();

		while ($arReport = $dbRepList->fetch())
		{
			$settings = unserialize($arReport['SETTINGS']);

			if(isset($settings['mobile'])
				&& is_array($settings['mobile'])
				&& isset($settings['mobile']['enabled'])
				&& $settings['mobile']['enabled'] == true)
				{
					if(count($settings['select']) <= COLUMNS_COUNT_FOR_SIMPLE_TEMPLATE)
						$template = "admin_mobile";
					else
						$template = "admin_mobile_encl";

					$arMenuItems[] = array(
						"text" => htmlspecialcharsbx($arReport['TITLE']),
						"data-url" => PATH_TO_MOBILE_REPORTS.'?ID='.$arReport['ID'].'&rep_templ='.$template
					);
				}
		}

		if(empty($arMenuItems))
			return false;
		else
			return array(
					"text" => GetMessage("SMOB_REPORTS"),
					"type" => "section",
					"items" => $arMenuItems
				);
	}

	//	RegisterModuleDependences("mobileapp", "OnBeforeAdminMobileMenuBuild",
	//								"sale", "CSaleMobileOrderUtils", "buildSaleAdminMobileMenu");
	public static function buildSaleAdminMobileMenu()
	{
		$items = array(
			array(
				"text" => GetMessage("SMOB_ALL_ORDERS"),
				"data-url" => "/bitrix/admin/mobile/sale_orders_list.php",
				"data-pageid" => "orders_list",
				"default" => true,
				"push-param" => "sl"
			),
			array(
				"text" => GetMessage("SMOB_WAITING_FOR_PAY"),
				"data-url" => "/bitrix/admin/mobile/sale_orders_list.php?action=get_filtered&filter_name=waiting_for_pay",
				"data-pageid" => "orders_waiting_for_pay",
			),
			array(
				"text" => GetMessage("SMOB_WAITING_FOR_DELIVERY"),
				"data-url" => "/bitrix/admin/mobile/sale_orders_list.php?action=get_filtered&filter_name=waiting_for_delivery",
				"data-pageid" => "orders_waiting_for_delivery",
			)
		);

		if (\Bitrix\Main\ModuleManager::isModuleInstalled("pull"))
		{
			$items[] = array(
				"text" => GetMessage("SMOB_PUSH_SETTINGS"),
				"data-url" => "/bitrix/admin/mobile/sale_orders_push.php",
				"data-pageid" => "orders_push"
			);
		}


		$arMenu = array(
			array(
				"text" => GetMessage("SMOB_ORDERS_MANAGEMENT"),
				"type" => "section",
				"items" => $items
			)
		);

		$arRepMenu = self::getMobileReports();

		if($arRepMenu)
			$arMenu[] = $arRepMenu;

		$startSortMenuPosition = 200;

		foreach ($arMenu as $key => $item)
		{
			$item["sort"] = $key+$startSortMenuPosition;
			CAdminMobileMenu::addItem($item);
		}

		return true;
	}

	public static function makeDetailClassFromOrder($arOrder)
	{
		$saleModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("sale");

		$mad = new CAdminMobileDetail;
		$arSection =	array(
					"TITLE" => GetMessage("SMOB_ORDER_INFO"),
					"OPEN" => true,
					"ROWS" => array(
						array("TITLE" => GetMessage("SMOB_CREATED").":", "VALUE" => self::getDateTime($arOrder['DATE_INSERT'])),
						array("TITLE" => GetMessage("SMOB_CHANGED").":", "VALUE" => self::getDateTime($arOrder['DATE_UPDATE'])),
						array("TITLE" => GetMessage("SMOB_STATUS").":", "VALUE" => $arOrder['STATUS_NAME']),
						),
					);

		$dateCChanged = false;

		if(strlen($arOrder["DATE_CANCELED"]) > 0)
			$dateCChanged = self::getDateTime($arOrder["DATE_CANCELED"]);

		if(IntVal($arOrder["EMP_CANCELED_ID"]) > 0)
			$dateCChanged .= " ".self::GetFormatedUserName($arOrder["EMP_CANCELED_ID"]);

		if($arOrder['CANCELED'] == 'Y')
			$arSection["BOTTOM"] = array("STYLE" => "red", "VALUE" => GetMessage("SMOB_ORDER_CANCELED"));

		if($arOrder['CANCELED'] == 'N' && IntVal($arOrder["EMP_CANCELED_ID"]) > 0)
			$arSection["BOTTOM"] = array("STYLE" => "green", "VALUE" => GetMessage("SMOB_ORDER_CANCEL_CANCELED"));

		if($dateCChanged)
			$arSection["BOTTOM"]["VALUE"] .= "<br>".$dateCChanged;

		$mad->addSection($arSection);

		$arSection = array(
					"TITLE" => GetMessage("SMOB_BUYER"),
					"ROWS" => array(
						array("TITLE" => GetMessage("SMOB_LOGIN").":", "VALUE" => $arOrder['USER_LOGIN']),
						array("TITLE" => GetMessage("SMOB_PAYER_TYPE").":", "VALUE" => $arOrder['PERSON_TYPE_NAME']),
						array("TITLE" => GetMessage("SMOB_FIO").":", "VALUE" => $arOrder['CUSTOMER_FIO']),
						array("TITLE" => GetMessage("SMOB_EMAIL").":",
							"VALUE" => '<a href="mailto:'.$arOrder['CUSTOMER_EMAIL'].'">'.
							$arOrder['CUSTOMER_EMAIL'].'</a>'),
						array("TITLE" => GetMessage("SMOB_PHONE").":",
							"VALUE" => '<a href="tel:'.$arOrder['CUSTOMER_PHONE'].'">'.
							$arOrder['CUSTOMER_PHONE'].'</a>'),
						array("TITLE" => GetMessage("SMOB_ZIP").":", "VALUE" => $arOrder['CUSTOMER_ZIP']),
						array("TITLE" => GetMessage("SMOB_LOCATION").":", "VALUE" => $arOrder['CUSTOMER_LOCATION'])
						),
					);

		if(strlen(trim($arOrder['CUSTOMER_CITY']))>0)
			$arSection["ROWS"][] = array("TITLE" => GetMessage("SMOB_CITY").":", "VALUE" => $arOrder['CUSTOMER_CITY']);

		$arSection["ROWS"][] = array("TITLE" => GetMessage("SMOB_ADDRESS").":", "VALUE" => $arOrder['CUSTOMER_ADDRESS']);

		$mad->addSection($arSection);

		$dateDChange = false;

		if(strlen($arOrder["DATE_ALLOW_DELIVERY"])>0)
			$dateDChange = self::getDateTime($arOrder["DATE_ALLOW_DELIVERY"]);

		if(IntVal($arOrder["EMP_ALLOW_DELIVERY_ID"]) > 0)
			$dateDChange .= " ".self::GetFormatedUserName($arOrder["EMP_ALLOW_DELIVERY_ID"]);

		$arSection =array(
					"TITLE" => GetMessage("SMOB_DELIVERY"),
					"ROWS" => array(
						array("TITLE" => GetMessage("SMOB_D_SERVICE").":", "VALUE" => $arOrder['DELIVERY_NAME'])
					));

		if($arOrder['ALLOW_DELIVERY'] == 'Y')
			$arSection["BOTTOM"] = array("STYLE" => "green", "VALUE" => GetMessage("SMOB_D_ALLOWED"));

		if($arOrder['ALLOW_DELIVERY'] == 'N' && !is_null($arOrder["DATE_ALLOW_DELIVERY"]))
			$arSection["BOTTOM"] = array("STYLE" => "red", "VALUE" => GetMessage("SMOB_D_DISALLOWED"));

		if($dateDChange)
			$arSection["BOTTOM"]["VALUE"] .= "<br>".$dateDChange;

		$mad->addSection($arSection);

		$arSection = array(
					"TITLE" => GetMessage("SMOB_PAYMENT"),
					"ROWS" => array(
						array("TITLE" => GetMessage("SMOB_P_METHOD").":", "VALUE" => $arOrder['PAY_SYSTEM_NAME']),
						array("TITLE" => GetMessage("SMOB_P_PRICE").":", "VALUE" => $arOrder['PRICE_STR']),
					));

		$datePChange = false;

		if(strlen($arOrder['DATE_PAYED'])>0)
			$datePChange = self::getDateTime($arOrder['DATE_PAYED']);

		if(IntVal($arOrder["EMP_PAYED_ID"]) > 0)
			$datePChange .= " ".self::GetFormatedUserName($arOrder["EMP_PAYED_ID"]);

		if($arOrder['PAYED'] == 'Y')
			$arSection["BOTTOM"] = array(
				"STYLE" => "green",
				"VALUE" => GetMessage("SMOB_PAYED"));

		if($arOrder['PAYED'] == 'N' && !is_null($arOrder['DATE_PAYED']))
			$arSection["BOTTOM"] = array(
				"STYLE" => "red",
				"VALUE" => GetMessage("SMOB_PAY_CANCELED"));

		if($datePChange)
			$arSection["BOTTOM"]["VALUE"] .= "<br>".$datePChange;

		$mad->addSection($arSection);

		$arSection = array(
					"TITLE" => GetMessage("SMOB_COMMENTS"),
					"ROWS" => array(
						array("TITLE" => GetMessage("SMOB_C_BUYER").":", "VALUE" => $arOrder['USER_DESCRIPTION']),
						)
					);

		if($saleModulePermissions > D)
			$arSection["ROWS"][] = array("TITLE" => GetMessage("SMOB_C_MANAGER").":", "VALUE" => $arOrder['COMMENTS']);

		$mad->addSection($arSection);

		if($arOrder['MARKED'] == 'Y')
		{
			$arSection = array(
						"TITLE" => GetMessage("SMOB_MARK"),
						"OPEN" => true,
						);

			$reason = strlen($arOrder["REASON_MARKED"]) > 0 ? $arOrder["REASON_MARKED"] : GetMessage("SMOB_MARK_NO_DESCRIPTION");

			if(strlen($arOrder['DATE_MARKED']) > 0)
				$reason .= '<br>'.self::getDateTime($arOrder['DATE_MARKED']);

			if(intval($arOrder['EMP_MARKED_ID']) > 0)
				$reason .= " (".self::GetFormatedUserName($arOrder["EMP_MARKED_ID"]).")";

			$arSection["BOTTOM"] = array(
				"STYLE" => "red",
				"VALUE" => $reason
			);

			$mad->addSection($arSection);
		}

		$arSection =array(
					"TITLE" => GetMessage("SMOB_DEDUCT"),
					);

		if($arOrder['DEDUCTED'] == 'Y')
			$arSection["BOTTOM"] = array("STYLE" => "green", "VALUE" => GetMessage("SMOB_ORDER_DEDUCTED"));
		elseif($arOrder['DEDUCTED'] == 'N' && strlen($arOrder["DATE_DEDUCTED"]) > 0 )
			$arSection["BOTTOM"] = array("STYLE" => "red", "VALUE" => GetMessage("SMOB_ORDER_DEDUCTED_UNDO"));
		else
			$arSection["ROWS"][] = array("TITLE" => GetMessage("SMOB_ORDER_NOT_DEDUCTED"), "VALUE" => "");

		if(strlen($arOrder["REASON_UNDO_DEDUCTED"]) > 0)
			$arSection["BOTTOM"]["VALUE"] .= '<br>'.$arOrder["REASON_UNDO_DEDUCTED"];

		if(strlen($arOrder["DATE_DEDUCTED"]) > 0)
			$arSection["BOTTOM"]["VALUE"] .= '<br>'.self::getDateTime($arOrder["DATE_DEDUCTED"]);

		if(intval($arOrder['EMP_DEDUCTED_ID']) > 0)
			$arSection["BOTTOM"]["VALUE"] .= " (".self::GetFormatedUserName($arOrder["EMP_DEDUCTED_ID"]).")";

		$mad->addSection($arSection);

		ob_start();
		$productListReturn = $GLOBALS["APPLICATION"]->IncludeComponent(
			'bitrix:sale.mobile.product.list',
			'.default',
			array("ORDER_ID" => $arOrder['ID']),
			false
		);
		$productListHtml = ob_get_contents();
		ob_end_clean();

		$mad->addSection(array(
					"TITLE" => GetMessage("SMOB_PRODUCT_LIST"),
					"TYPE" => "container",
					"HTML" => $productListHtml
					));

		$WEIGHT_UNIT = htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_unit', "", $arOrder['LID']));
		$WEIGHT_KOEF = htmlspecialcharsbx(COption::GetOptionString('sale', 'weight_koef', 1, $arOrder['LID']));

		$arSection = array(
					"TITLE" => GetMessage("SMOB_TOTAL"),
					"TOTAL" => true,
					"ROWS" => array(
						array(
							"TITLE" => GetMessage("SMOB_PRICE_ALL").":",
							"VALUE" => SaleFormatCurrency($productListReturn['PRICE_TOTAL'], $arOrder["CURRENCY"])),
						array(
							"TITLE" => GetMessage("SMOB_P_DELIVERY").":",
							"VALUE" => SaleFormatCurrency($productListReturn['PRICE'], $arOrder["CURRENCY"])),
						array(
							"TITLE" => GetMessage("SMOB_DELIVERY").":",
							"VALUE" => SaleFormatCurrency($arOrder['PRICE_DELIVERY'], $arOrder["CURRENCY"]))
						));

		if($arOrder['TAX_VALUE']>0)
			$arSection["ROWS"][] = array(
				"TITLE" => GetMessage("SMOB_TAX").":",
				"VALUE" => SaleFormatCurrency($arOrder['TAX_VALUE'], $arOrder["CURRENCY"]));

		if($arOrder['DISCOUNT_VALUE']>0)
			$arSection["ROWS"][] = array(
				"TITLE" => GetMessage("SMOB_DISCOUNT").":",
				"VALUE" => SaleFormatCurrency($arOrder['DISCOUNT_VALUE'], $arOrder["CURRENCY"]));

		$arSection["ROWS"][] = 	array(
							"TITLE" => GetMessage("SMOB_WEIGHT").":",
							"VALUE" => roundEx(DoubleVal($productListReturn['WEIGHT']/$WEIGHT_KOEF), SALE_WEIGHT_PRECISION)." ".$WEIGHT_UNIT);

		$arSection["ROWS"][] = array(
			"TITLE" => GetMessage("SMOB_TOTALLY").":",
			"VALUE" => SaleFormatCurrency($arOrder['PRICE'], $arOrder["CURRENCY"]), "HIGLIGHTED" => true);

		$mad->addSection($arSection);

		/*
		alternative:
		$arDetail = $mad->getItem();
		return  CAdminMobileDetailTmpl::getHtml($arDetail);
		*/

		return $mad->getHtml();
	}

	public static function getOrderInfoDetail($orderId)
	{
		if(!$orderId)
			return false;

		if(!$GLOBALS["USER"]->IsAuthorized())
			return false;

		$arFilter["ID"] = $orderId;

		$saleModulePermissions = $GLOBALS["APPLICATION"]->GetGroupRight("sale");

		if ($saleModulePermissions == "D")
			$arFilter["USER_ID"] = IntVal($GLOBALS["USER"]->GetID());
		elseif ($saleModulePermissions != "W")
		{
			$arFilter["STATUS_PERMS_GROUP_ID"] = $GLOBALS["USER"]->GetUserGroupArray();
			$arFilter[">=STATUS_PERMS_PERM_VIEW"] = "Y";
		}

		$arOrder = array();

		$dbOrder = CSaleOrder::GetList(array(), $arFilter);
		$arOrder = $dbOrder->GetNext();

		if(!$arOrder)
			return false;

		$arOrder["STATUS"] = CSaleStatus::GetLangByID($arOrder["STATUS_ID"]);
		$arOrder["STATUS_NAME"] = $arOrder["STATUS"]["NAME"];
		$arOrder["PRICE_IN_ALL_NUM"] = floatval(($arOrder["~PRICE"])+floatval($arOrder["~PRICE_DELIVERY"]));
		$arOrder["PRICE_IN_ALL"] = SaleFormatCurrency($arOrder["PRICE_IN_ALL_NUM"], $arOrder["CURRENCY"]);
		$arOrder["PRICE_STR"] = SaleFormatCurrency($arOrder["PRICE"], $arOrder["CURRENCY"]);

		$arOrder["PERSON_TYPE"] = CSalePersonType::GetByID($arOrder["PERSON_TYPE_ID"]);
		$arOrder["PERSON_TYPE_NAME"] = htmlspecialcharsbx($arOrder["PERSON_TYPE"]["NAME"]);

		$arOrder = self::getOrderProps($arOrder);
		$arOrder["DELIVERY_NAME"] = self::getDeliveriesInfo(array($arOrder["DELIVERY_ID"]));
		$arOrder["DELIVERY_NAME"] = $arOrder["DELIVERY_NAME"][$arOrder["DELIVERY_ID"]];

		$arPaySys = CSalePaySystem::GetByID($arOrder["PAY_SYSTEM_ID"], $arOrder["PERSON_TYPE_ID"]);
		$arOrder["PAY_SYSTEM_NAME"] = $arPaySys["NAME"];

		$dbUserAccount = CSaleUserAccount::GetList(
			array(),
			array(
				"USER_ID" => $arOrder["USER_ID"],
				"CURRENCY" => $arOrder["CURRENCY"],
				"LOCKED" => "N"
			)
		);

		$arUserAccount = $dbUserAccount->GetNext();

		$arOrder["CURRENT_BUDGET"] = $arUserAccount["CURRENT_BUDGET"] ? $arUserAccount["CURRENT_BUDGET"] : 0;
		$arOrder["CURRENT_BUDGET_STRING"] = SaleFormatCurrency($arOrder["CURRENT_BUDGET"], $arOrder["CURRENCY"]);

		return $arOrder;
	}

	private function getOrderProps($arOrder)
	{
		$dbOrderProps = CSaleOrderPropsValue::GetOrderProps($arOrder["ID"]);
		$orderPropsCodes = array("FIO", "EMAIL", "PHONE", "ZIP", "CITY", "ADDRESS");

		while($arOrderProps = $dbOrderProps->GetNext())
		{
			$arOrder["PROPS"][] = $arOrderProps;

			if(in_array($arOrderProps["CODE"], $orderPropsCodes))
			{
				$idx = "CUSTOMER_".$arOrderProps["CODE"];
				$arOrder[$idx] = $arOrderProps["VALUE"];
			}

			if($arOrderProps["TYPE"] == "LOCATION")
			{
				$arVal = CSaleLocation::GetByID($arOrderProps["VALUE"], LANG);

				if(strlen($arOrderProps["CODE"]) > 0)
					$arOrder["CUSTOMER_LOCATION"] = htmlspecialcharsEx($arVal["COUNTRY_NAME"].
							((strlen($arVal["COUNTRY_NAME"])<=0 || strlen($arVal["CITY_NAME"])<=0) ? "" : " - ").
							$arVal["CITY_NAME"]);
				else
					$arOrder["CUSTOMER_LOCATION"] = htmlspecialcharsEx($arVal["COUNTRY_NAME"].
							((strlen($arVal["COUNTRY_NAME"])<=0 || strlen($arVal["CITY_NAME"])<=0) ? "" : " - ").
							$arVal["CITY_NAME"]);
			}
		}

		return $arOrder;
	}

	public static function getCurrenciesNames($arCurIds = array())
	{
		if (!CModule::IncludeModule('catalog') || !CModule::IncludeModule('currency'))
			return array();

		static $arCurrCache = false;

		if($arCurrCache === false)
		{
			$arCurrCache = array();
			$dbCurr = CCurrency::GetList(($by="sort"), ($order="asc"));

			while($arCurr = $dbCurr->Fetch())
				$arCurrCache[$arCurr["CURRENCY"]] = $arCurr["FULL_NAME"];
		}

		$arRetSite = array();

		if(!empty($arCurIds))
		{
			foreach ($arCurIds as $currId)
				if(isset($arCurrCache[$currId]))
					$arRetCur[$currId] = $arCurrCache[$currId];
		}
		else
		{
			$arRetCur = $arCurrCache;
		}

		return $arRetCur;
	}

	public static function getDeliveriesInfo($arDeliveryIds)
	{
		if(!is_array($arDeliveryIds))
			return false;

		static $arDeliveries = array();

		$arTmpSD = array();

		foreach ($arDeliveryIds as $deliveryId)
		{
			if(!$deliveryId || is_null($deliveryId))
				continue;

			if(!isset($arDeliveries[$deliveryId]))
			{
				if (strpos($deliveryId, ":") !== false)
				{
					$arId = explode(":", $deliveryId);
					$dbDelivery = CSaleDeliveryHandler::GetList(array(), array("SID" => $arId[0]));

					if($arDelivery = $dbDelivery->Fetch())
					{
						$arDeliveries[$deliveryId] = htmlspecialcharsEx($arDelivery["NAME"]);

						if(strlen($arId[1]) > 0 && isset($arDelivery["PROFILES"][$arId[1]]["TITLE"]))
							$arDeliveries[$deliveryId] .= " / ".htmlspecialcharsEx($arDelivery["PROFILES"][$arId[1]]["TITLE"]);
					}
				}
				else
				{
					$arTmpSD[] = $deliveryId;
				}
			}
		}

		if(is_array($arTmpSD))
		{
			$dbDelivery = CSaleDelivery::GetList(array(), array("ID" => $arTmpSD), false, false, array("ID", "NAME"));

			while($arDelivery = $dbDelivery->Fetch())
				$arDeliveries[$arDelivery["ID"]] = htmlspecialcharsbx($arDelivery["NAME"]);
		}

		return $arDeliveries;
	}

	public static function getSitesNames($arSitesIds = array())
	{
		static $arSiteCache = false;

		if($arSiteCache === false)
		{
			$arSiteCache = array();
			$dbSite = CSite::GetList($by = "sort", $order = "asc", Array());

			while($arSite = $dbSite->Fetch())
				$arSiteCache[$arSite["LID"]] = $arSite["NAME"];
		}

		$arRetSite = array();

		if(!empty($arSitesIds))
		{
			foreach ($arSitesIds as $siteId)
				if(isset($arSiteCache[$siteId]))
					$arRetSite[$siteId] = $arSiteCache[$siteId];
		}
		else
		{
			$arRetSite = $arSiteCache;
		}

		return $arRetSite;
	}

	public static function getPaySystemsNames($arPaySystemsIds)
	{
		$arPaySystemsNames = array();

		$dbPS = CSalePaySystem::GetList(array(), array("ID" => $arPaySystemsIds), false, false, array("ID", "NAME"));

		while($arPS = $dbPS->Fetch())
			$arPaySystemsNames[$arPS["ID"]] = htmlspecialcharsbx($arPS["NAME"]);

		return $arPaySystemsNames;
	}

	public static function getPersonTypesNames($arPersonTypeIds)
	{
		$arPersonTypes = array();

		$dbPers = CSalePersonType::GetList(array(), array("ID" => $arPersonTypeIds), false, false, array("ID", "NAME"));

		while($arPers = $dbPers->Fetch())
			$arPersonTypes[$arPers["ID"]] = htmlspecialcharsbx($arPers["NAME"]);

		return $arPersonTypes;
	}

	public static function getStatusesNames($arStatusIds = false)
	{
		$arStatusNames = array();
		$arFilter = array("LID" => LANGUAGE_ID);

		if(is_array($arStatusIds))
			$arFilter["ID"] = $arStatusIds;

		$dbStat = CSaleStatus::GetList(
										array(),
										$arFilter,
										false,
										false,
										array("ID", "NAME")
		);

		while($arStat = $dbStat->Fetch())
			$arStatusNames[$arStat["ID"]] = htmlspecialcharsbx($arStat["NAME"]);

		return $arStatusNames;
	}

	public static function getDateTime($strDate)
	{
		$stmp = MakeTimeStamp($strDate, "DD.MM.YYYY HH:MI:SS");
		return date("d.m.Y", $stmp).' '.date("H:i", $stmp);
	}

	public static function getPreparedTemplate($template, $arFields)
	{
		$retStr = $template;

		foreach ($arFields as $key => $field)
			$retStr = str_replace('##'.$key.'##', $field, $retStr);

		return $retStr;
	}
}

class CSaleMobileOrderPull
{
	public static function InitEventHandlers()
	{
		if(!CModule::IncludeModule("pull"))
			return false;

		static $inited = false;

		if($inited)
			return true;

		CPullWatch::Add($GLOBALS['USER']->GetID(), 'saleOrder');

		$inited = true;

		return true;
	}

	public static function onOrderDelete($orderId, $bSuccess)
	{
		if(!$bSuccess)
			return false;

		if(!CModule::IncludeModule("pull"))
			return false;

		CPullWatch::AddToStack('saleOrder',
								array(
									'module_id' => 'sale',
									'command' => 'orderDelete',
									'params' => Array("id" => $orderId)
								)
		);
	}

	public static function onOrderAdd($orderId, $arFields)
	{
		if(!CModule::IncludeModule("pull"))
			return false;

		CPullWatch::AddToStack('saleOrder',
								array(
									'module_id' => 'sale',
									'command' => 'orderAdd',
									'params' => Array("id" => $orderId)
								)
		);
	}

	public static function onOrderUpdate($orderId, $arFields)
	{
		if(!CModule::IncludeModule("pull"))
			return false;

		CPullWatch::AddToStack('saleOrder',
								array(
									'module_id' => 'sale',
									'command' => 'orderUpdate',
									'params' => Array("id" => $orderId)
								)
		);
	}
}

class CSaleMobileOrderFilter
{
	static public function adaptFields($arFields)
	{
		foreach ($arFields as $fieldId => $fieldValue)
		{
			if($fieldId == "DATE_TO")
				$arFields["DATE_TO"] = self::addLastTimeToDate($fieldValue);
			elseif($fieldId == "DATE_UPDATE_TO")
				$arFields["DATE_UPDATE_TO"] = self::addLastTimeToDate($fieldValue);
			elseif($fieldId == "DATE_STATUS_TO")
				$arFields["DATE_STATUS_TO"] = self::addLastTimeToDate($fieldValue);
			elseif($fieldId == "DATE_PAYED_TO")
				$arFields["DATE_PAYED_TO"] = self::addLastTimeToDate($fieldValue);
			elseif($fieldId == "DATE_ALLOW_DELIVERY_TO")
			{
				$arFields["<=DATE_ALLOW_DELIVERY"] = self::addLastTimeToDate($fieldValue);
				unset($arFields["DATE_ALLOW_DELIVERY_TO"]);
			}
			elseif($fieldId == "DATE_ALLOW_DELIVERY_FROM")
			{
				$arFields[">=DATE_ALLOW_DELIVERY"] = $arFields["DATE_ALLOW_DELIVERY_FROM"];
				unset($arFields["DATE_ALLOW_DELIVERY_FROM"]);
			}

			elseif($fieldId == "ORDER_ID")
			{
				$arCommaEnums = explode(",", $arFields["ORDER_ID"]);
				$commaCount = count($arCommaEnums);

				if($commaCount > 1)
					$arFields["ID"] = $arCommaEnums;
				elseif($commaCount == 1)
				{
					$arHypEnum = explode("-", $arFields["ORDER_ID"]);

					if(count($arHypEnum) == 2)
					{
						$arFields[">=ID"] =$arHypEnum[0];
						$arFields["<=ID"] =$arHypEnum[1];
					}
					else
					{
						$arFields["ID"] = $arHypEnum;
					}
				}

				unset($arFields["ORDER_ID"]);
			}
			elseif($fieldId == "PRICE_FROM")
			{
				$arFields[">=PRICE"] = $fieldValue;
				unset($arFields["PRICE_FROM"]);
			}
			elseif($fieldId == "PRICE_TO")
			{
				$arFields["<PRICE"] = $fieldValue;
				unset($arFields["PRICE_TO"]);
			}
		}

		return $arFields;
	}

	private function parseOrderId($strOrderId)
	{
		return $arResult;
	}

	static public function addLastTimeToDate($strDate)
	{
		$retStrDateTime = '';

		if (strlen($strDate) <= 0)
			return $retStrDateTime;

		if ($arDate = ParseDateTime($strDate, CSite::GetDateFormat("FULL", SITE_ID)))
		{
			if (StrLen($strDate) < 11)
			{
				$arDate["HH"] = 23;
				$arDate["MI"] = 59;
				$arDate["SS"] = 59;
			}

			$retStrDateTime = date($GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("FULL", SITE_ID)), mktime($arDate["HH"], $arDate["MI"], $arDate["SS"], $arDate["MM"], $arDate["DD"], $arDate["YYYY"]));
		}

		return $retStrDateTime;
	}

	static public function setFieldsValues($arFields, $customFilter)
	{
		if(!is_array($arFields) || !is_array($customFilter))
			return false;

		foreach ($arFields as $fieldId => $arField)
			$arFields[$fieldId]["VALUE"] = isset($customFilter[$fieldId]) ? $customFilter[$fieldId] : '';

		return $arFields;
	}

	static public function buildFieldsParams()
	{
		return array(
			"ORDER_ID" => array(
				"NAME" => GetMessage("SMOB_FILTER_ORDER_ID"),
				"TYPE" => "TEXT"
			),
			"USER_ID" => array(
				"NAME" => GetMessage("SMOB_FILTER_USER_ID"),
				"TYPE" => "TEXT"
			),
			"DATE_FROM" => array(
				"NAME" => GetMessage("SMOB_FILTER_DATE_ORDER_FROM"),
				"TYPE" => "DATE"
			),
			"DATE_TO" => array(
				"NAME" => GetMessage("SMOB_FILTER_DATE_ORDER_TO"),
				"TYPE" => "DATE"
			),
			"LID" => array(
				"NAME" => GetMessage("SMOB_FILTER_SITE"),
				"TYPE" => "ONE_SELECT",
				"ADD_ALL_SELECT" => "Y",
				"OPTIONS" => CSaleMobileOrderUtils::getSitesNames()
			),
			"CURRENCY" => array(
				"NAME" => GetMessage("SMOB_FILTER_CURRENCY"),
				"TYPE" => "ONE_SELECT",
				"OPTIONS" => CSaleMobileOrderUtils::getCurrenciesNames()
			),
			"PRICE_FROM" => array(
				"NAME" => GetMessage("SMOB_FILTER_PRICE_FROM"),
				"TYPE" => "TEXT"
			),
			"PRICE_TO" => array(
				"NAME" => GetMessage("SMOB_FILTER_PRICE_TO"),
				"TYPE" => "TEXT"
			),
			"STATUS_ID" => array(
				"NAME" => GetMessage("SMOB_FILTER_STATUS_ID"),
				"TYPE" => "MULTI_SELECT",
				"OPTIONS" => CSaleMobileOrderUtils::getStatusesNames()
			),
			"PAYED" => array(
				"NAME" => GetMessage("SMOB_FILTER_PAYED"),
				"TYPE" => "ONE_SELECT",
				"ADD_ALL_SELECT" => "Y",
				"OPTIONS" => array(
					"Y" => GetMessage("SMOB_FILTER_PAYED_Y"),
					"N" => GetMessage("SMOB_FILTER_PAYED_N")
				),
			),
			"ALLOW_DELIVERY" => array(
				"NAME" => GetMessage("SMOB_FILTER_ALLOW_DELIVERY"),
				"TYPE" => "ONE_SELECT",
				"ADD_ALL_SELECT" => "Y",
				"OPTIONS" => array(
					"Y" => GetMessage("SMOB_FILTER_ALLOW_DELIVERY_Y"),
					"N" => GetMessage("SMOB_FILTER_ALLOW_DELIVERY_N")
				),
			),
			"DEDUCTED" => array(
				"NAME" => GetMessage("SMOB_FILTER_DEDUCTED"),
				"TYPE" => "ONE_SELECT",
				"ADD_ALL_SELECT" => "Y",
				"OPTIONS" => array(
					"Y" => GetMessage("SMOB_FILTER_DEDUCTED_Y"),
					"N" => GetMessage("SMOB_FILTER_DEDUCTED_N")
				),
			),
			"MARKED" => array(
				"NAME" => GetMessage("SMOB_FILTER_MARKED"),
				"TYPE" => "ONE_SELECT",
				"ADD_ALL_SELECT" => "Y",
				"OPTIONS" => array(
					"Y" => GetMessage("SMOB_FILTER_MARKED_Y"),
					"N" => GetMessage("SMOB_FILTER_MARKED_N")
				),
			),
		);
	}
}

class CSaleMobileOrderPush
{
	private static $arEvents = array(
		"ORDER_STATUS_CHANGED",
		"ORDER_DELIVERY_ALLOWED",
		"ORDER_PAYED",
		"ORDER_COMPLETED",
		"ORDER_DEDUCTED",
		"ORDER_CANCELED",
		"ORDER_CREATED",
		"ORDER_MARKED"
	);

	private static $arSubscriptions = array();

	public static function getEvents()
	{
		return self::$arEvents;
	}

	private static function &getData()
	{
		if(empty(self::$arSubscriptions))
			self::$arSubscriptions = unserialize(COption::GetOptionString("sale", "pushEventsSubscriptions", ""));

		return self::$arSubscriptions;
	}

	private static function saveData()
	{
		return COption::SetOptionString("sale", "pushEventsSubscriptions", serialize(self::$arSubscriptions));
	}

	public static function getSubscribers($eventId, $arParams)
	{
		$arResult = array();

		$arSubscriptions = self::getData();

		if(is_array($arSubscriptions))
			foreach ($arSubscriptions as $subsId => $arSubscription)
				if(
					$arSubscription["E"] == self::$arEvents[$eventId]
					&&
					$arSubscription["V"] == "Y"
					)
					$arResult[] = $arSubscription["U"];

		return $arResult;
	}

	public static function updateSubscriptions($userId, $arSubs)
	{
		$arOldSubs = &self::getData();
		$arTmpSubs = array();

		foreach ($arOldSubs as $subId => $subItem)
			if($subItem["U"] == $userId)
				$arTmpSubs[$subId] = &$arOldSubs[$subId];

		foreach ($arTmpSubs as $subId => &$subItem)
		{
			if(isset($arSubs[self::$arEvents[$subItem["E"]]]))
			{
				$subItem["V"] = $arSubs[self::$arEvents[$subItem["E"]]];
				unset($arSubs[self::$arEvents[$subItem["E"]]]);
			}

			unset($arTmpSubs[$subId]);
		}

		if(!empty($arSubs))
			foreach ($arSubs as $eventId => $value)
				self::addSubscription($userId, $eventId, $value);

		self::saveData();

		return true;
	}

	public static function addSubscription($userId, $eventId, $value)
	{
		$result = false;
		$evtKey = array_search($eventId, self::$arEvents);

		if($evtKey !== false)
		{
			$arSubscriptions = &self::getData();
			$arSubscriptions[] = array(
				"U" => $userId,
				"E" => $evtKey,
				"V" => $value
			);

			$result = true;
		}

		return $result;
	}

	private static function checkRights($userId, $eventId, $arParams)
	{
		$orderId = $arParams["ORDER_ID"];
		$arUserGroups = CUser::GetUserGroup($userId);
		return CSaleOrder::CanUserViewOrder($orderId, $arUserGroups, $userId);
	}

	public static function getSubscriptions($userId)
	{
		$arResult = array();
		$userId = intval($userId);

		$arSubscriptions = self::getData();

		if(is_array($arSubscriptions))
			foreach ($arSubscriptions as $arSubscription)
				if(intval($arSubscription["U"]) == $userId)
					$arResult[self::$arEvents[$arSubscription["E"]]] = $arSubscription["V"];

		return $arResult;
	}

	private static function makeMessage($eventId, $arParams)
	{
		global $DB;

		$strResult = GetMessage('SMOB_PUSH_MES_'.$eventId, array(
			"#ACCOUNT_NUMBER#" => $arParams["ORDER"]["ACCOUNT_NUMBER"],
			"#DATE_INSERT#" => FormatDate($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), strtotime($arParams["ORDER"]["DATE_INSERT"])),
			"#PRICE#" => $arParams["ORDER"]["PRICE"],
			"#CURRENCY#" => $arParams["ORDER"]["CURRENCY"]
		));

		if($eventId == "ORDER_STATUS_CHANGED")
		{
			$arFilter = array(
				"LID" => LANGUAGE_ID,
				"ID" => $arParams["ORDER"]["STATUS_ID"]
			);

			$dbStatusListTmp = CSaleStatus::GetList(
				array("SORT" => "ASC"),
				$arFilter,
				false,
				false,
				array("NAME")
			);

			if($arStatus = $dbStatusListTmp->GetNext())
				$strResult = str_replace("#STATUS_NAME#", $arStatus["NAME"], $strResult);
		}

		return $strResult;
	}

	private static function makeTitle($eventId, $arParams)
	{
		return "";
	}

	public static function send($eventId, $arParams)
	{
		$result = false;

		if(!isset($arParams["ORDER"]) && isset($arParams["ORDER_ID"]))
			$arParams["ORDER"] = CSaleOrder::GetById($arParams["ORDER_ID"]);

		if(
			in_array($eventId, self::$arEvents)
			&& isset($arParams["ORDER"])
			&& CModule::IncludeModule("pull")
			)
		{
			$arUsers = self::getSubscribers($eventId, $arParams);

			if(!empty($arUsers))
			{
				$text = self::makeMessage($eventId, $arParams);
				$title = self::makeTitle($eventId, $arParams);
				$arMessages = array();

				foreach ($arUsers as $userId)
				{
					if(!self::checkRights($userId, $eventId, array("ORDER_ID" => $arParams["ORDER"]["ID"])))
						continue;

					$arMessages[] = array(
						"USER_ID" => $userId,
						"TITLE" => $title,
						"APP_ID"=>"BitrixAdmin",
						"MESSAGE" => $text,
						"PARAMS" =>"sl_".$arParams["ORDER"]["ID"]
					);
				}

				$pushMe = new CPushManager();
				$result = $pushMe->SendMessage($arMessages);
			}
		}

		return $result;
	}
}
?>