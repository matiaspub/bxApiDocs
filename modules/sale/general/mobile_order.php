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
		$arMenu = array(
			array(
				"text" => GetMessage("SMOB_ORDERS_MANAGEMENT"),
				"type" => "section",
				"items" => array(
				array(
					"text" => GetMessage("SMOB_ALL_ORDERS"),
					"data-url" => "/bitrix/admin/mobile/sale_orders_list.php",
					"data-pageid" => "orders_list",
					"default" => true
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
					),
				/*array(
					"text" => GetMessage("SMOB_ORDERS_FILTER"),
					"data-url" => "/bitrix/admin/mobile/sale_orders_list.php?action=set_filter",
					//"data-pageid" => "orders_filter",
					)*/
				))
			/*
			array(
				"text" => GetMessage("SMOB_PRIVATE_OFFICE"),
				"type" => "separator",
				"items" => array(
				),
				array(
					"text" => GetMessage("SMOB_PUSH_SETTINGS"),
					"type" => "item",
					))),*/
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
						array("TITLE" => GetMessage("SMOB_PHONE").":", "VALUE" => $arOrder['CUSTOMER_PHONE']),
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
							"VALUE" => roundEx(DoubleVal($productListReturn['WEIGHT']/$WEIGHT_KOEF), SALE_VALUE_PRECISION)." ".$WEIGHT_UNIT);

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

	public static function getStatusesNames($arStatusIds)
	{
		$arStatusNames = array();

		$dbStat = CSaleStatus::GetList(
										array(),
										array("ID" =>$arStatusIds, "LID" => LANGUAGE_ID),
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
?>
