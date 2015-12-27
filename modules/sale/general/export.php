<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["SALE_EXPORT"] = Array();

class CAllSaleExport
{
	/*************** ADD, UPDATE, DELETE *****************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SALE_EXPORT_NO_PERSON_TYPE_ID"), "EMPTY_PERSON_TYPE_ID");
			return false;
		}

		if (is_set($arFields, "PERSON_TYPE_ID"))
		{
			$arResult = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"]);
			if (!$arResult)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], GetMessage("SALE_EXPORT_ERROR_PERSON_TYPE_ID")), "ERROR_NO_PERSON_TYPE_ID");
				return false;
			}
		}

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		unset($GLOBALS["SALE_EXPORT"]["SALE_EXPORT_CACHE_".$ID]);

		return $DB->Query("DELETE FROM b_sale_export WHERE ID = ".$ID."", true);
	}

	/*************** SELECT *********************/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		if (isset($GLOBALS["SALE_EXPORT"]["SALE_EXPORT_CACHE_".$ID]) && is_array($GLOBALS["SALE_EXPORT"]["SALE_EXPORT_CACHE_".$ID]) && is_set($GLOBALS["SALE_EXPORT_CACHE_".$ID], "ID"))
		{
			return $GLOBALS["SALE_EXPORT"]["SALE_EXPORT_CACHE_".$ID];
		}
		else
		{
			$strSql =
				"SELECT E.ID, E.PERSON_TYPE_ID, E.VARS ".
				"FROM b_sale_export E ".
				"WHERE E.ID = ".$ID."";
			$dbResult = $DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arResult = $dbResult->Fetch())
			{
				$GLOBALS["SALE_EXPORT"]["SALE_EXPORT_CACHE_".$ID] = $arResult;
				return $arResult;
			}
		}

		return False;
	}

	public static function ExportOrders2Xml($arFilter = Array(), $nTopCount = 0, $currency = "", $crmMode = false, $time_limit = 0, $version = false, $arOptions = Array())
	{
		global $DB;
		$count = false;
		if(IntVal($nTopCount) > 0)
			$count = Array("nTopCount" => $nTopCount);
		$bNewVersion = (strlen($version) > 0);
		$bExportFromCrm = (isset($arOptions["EXPORT_FROM_CRM"]) && $arOptions["EXPORT_FROM_CRM"] === "Y");

		if(IntVal($time_limit) > 0)
		{
			//This is an optimization. We assume than no step can take more than one year.
			if($time_limit > 0)
				$end_time = time() + $time_limit;
			else
				$end_time = time() + 365*24*3600; // One year

			//$version
			$lastOrderPrefix = "LAST_ORDER_ID";
			if($crmMode)
			{
				$lastOrderPrefix = md5(serialize($arFilter));
				if(!empty($_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix]) && IntVal($nTopCount) > 0)
					$count["nTopCount"] = $count["nTopCount"]+count($_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix]);
			}
			else
			{
				if(IntVal($_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix]) > 0)
				{
					$arFilter["<ID"] = $_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix];
				}
			}
		}

		$arResultStat = array(
			"ORDERS" => 0,
			"CONTACTS" => 0,
			"COMPANIES" => 0,
		);

		$accountNumberPrefix = COption::GetOptionString("sale", "1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX", "");

		$dbPaySystem = CSalePaySystem::GetList(Array("ID" => "ASC"), Array("ACTIVE" => "Y"), false, false, Array("ID", "NAME", "ACTIVE"));
		while($arPaySystem = $dbPaySystem -> Fetch())
			$paySystems[$arPaySystem["ID"]] = $arPaySystem["NAME"];

		$dbDelivery = CSaleDelivery::GetList(Array("ID" => "ASC"), Array("ACTIVE" => "Y"), false, false, Array("ID", "NAME", "ACTIVE"));
		while($arDelivery = $dbDelivery -> Fetch())
			$delivery[$arDelivery["ID"]] = $arDelivery["NAME"];

		$rsDeliveryHandlers = CSaleDeliveryHandler::GetAdminList(array("SID" => "ASC"));
		while ($arHandler = $rsDeliveryHandlers->Fetch())
		{
			if(is_array($arHandler["PROFILES"]))
			{
				foreach($arHandler["PROFILES"] as $k => $v)
				{
					$delivery[$arHandler["SID"].":".$k] = $v["TITLE"]." (".$arHandler["NAME"].")";
				}
			}
		}

		$arStore = array();
		$arMeasures = array();
		if(CModule::IncludeModule("catalog"))
		{
			$dbList = CCatalogStore::GetList(
				array("SORT" => "DESC", "ID" => "ASC"),
				array("ACTIVE" => "Y", "ISSUING_CENTER" => "Y"),
				false,
				false,
				array("ID", "SORT", "TITLE", "ADDRESS", "DESCRIPTION", "PHONE", "EMAIL", "XML_ID")
			);
			while ($arStoreTmp = $dbList->Fetch())
			{
				if(strlen($arStoreTmp["XML_ID"]) <= 0)
					$arStoreTmp["XML_ID"] = $arStoreTmp["ID"];
				$arStore[$arStoreTmp["ID"]] = $arStoreTmp;
			}

			$dbList = CCatalogMeasure::getList(array(), array(), false, false, array("CODE", "MEASURE_TITLE"));
			while($arList = $dbList->Fetch())
			{
				$arMeasures[$arList["CODE"]] = $arList["MEASURE_TITLE"];
			}
		}
		if(empty($arMeasures))
			$arMeasures[796] = GetMessage("SALE_EXPORT_SHTUKA");

		$dbExport = CSaleExport::GetList();
		while($arExport = $dbExport->Fetch())
		{
			$arAgent[$arExport["PERSON_TYPE_ID"]] = unserialize($arExport["VARS"]);
		}

		$dateFormat = CSite::GetDateFormat("FULL");

		if ($crmMode)
		{
			echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n";

			$arCharSets = array();
			$dbSitesList = CSite::GetList(($b=""), ($o=""));
			while ($arSite = $dbSitesList->Fetch())
				$arCharSets[$arSite["ID"]] = $arSite["CHARSET"];
		}
		else
			echo "<"."?xml version=\"1.0\" encoding=\"windows-1251\"?".">\n";
		?>
		<<?=GetMessage("SALE_EXPORT_COM_INFORMATION")?> <?=GetMessage("SALE_EXPORT_SHEM_VERSION")?>="<?=($bNewVersion ? "2.08" : "2.05")?>" <?=GetMessage("SALE_EXPORT_SHEM_DATE_CREATE")?>="<?=date("Y-m-d")?>T<?=date("G:i:s")?>" <?=GetMessage("SALE_EXPORT_DATE_FORMAT")?>="<?=GetMessage("SALE_EXPORT_DATE_FORMAT_DF")?>=yyyy-MM-dd; <?=GetMessage("SALE_EXPORT_DATE_FORMAT_DLF")?>=DT" <?=GetMessage("SALE_EXPORT_DATE_FORMAT_DATETIME")?>="<?=GetMessage("SALE_EXPORT_DATE_FORMAT_DF")?>=<?=GetMessage("SALE_EXPORT_DATE_FORMAT_TIME")?>; <?=GetMessage("SALE_EXPORT_DATE_FORMAT_DLF")?>=T" <?=GetMessage("SALE_EXPORT_DEL_DT")?>="T" <?=GetMessage("SALE_EXPORT_FORM_SUMM")?>="<?=GetMessage("SALE_EXPORT_FORM_CC")?>=18; <?=GetMessage("SALE_EXPORT_FORM_CDC")?>=2; <?=GetMessage("SALE_EXPORT_FORM_CRD")?>=." <?=GetMessage("SALE_EXPORT_FORM_QUANT")?>="<?=GetMessage("SALE_EXPORT_FORM_CC")?>=18; <?=GetMessage("SALE_EXPORT_FORM_CDC")?>=2; <?=GetMessage("SALE_EXPORT_FORM_CRD")?>=.">
		<?
		$arOrder = array("ID" => "DESC");
		if ($crmMode)
			$arOrder = array("DATE_UPDATE" => "ASC");

		$arSelect = array(
			"ID", "LID", "PERSON_TYPE_ID", "PAYED", "DATE_PAYED", "EMP_PAYED_ID", "CANCELED", "DATE_CANCELED",
			"EMP_CANCELED_ID", "REASON_CANCELED", "STATUS_ID", "DATE_STATUS", "PAY_VOUCHER_NUM", "PAY_VOUCHER_DATE", "EMP_STATUS_ID",
			"PRICE_DELIVERY", "ALLOW_DELIVERY", "DATE_ALLOW_DELIVERY", "EMP_ALLOW_DELIVERY_ID", "PRICE", "CURRENCY", "DISCOUNT_VALUE",
			"SUM_PAID", "USER_ID", "PAY_SYSTEM_ID", "DELIVERY_ID", "DATE_INSERT", "DATE_INSERT_FORMAT", "DATE_UPDATE", "USER_DESCRIPTION",
			"ADDITIONAL_INFO", "PS_STATUS", "PS_STATUS_CODE", "PS_STATUS_DESCRIPTION", "PS_STATUS_MESSAGE", "PS_SUM", "PS_CURRENCY", "PS_RESPONSE_DATE",
			"COMMENTS", "TAX_VALUE", "STAT_GID", "RECURRING_ID", "ACCOUNT_NUMBER", "SUM_PAID", "DELIVERY_DOC_DATE", "DELIVERY_DOC_NUM", "TRACKING_NUMBER", "STORE_ID",
			"ID_1C", "VERSION",
		);

		$bCrmModuleIncluded = false;
		if ($bExportFromCrm)
		{
			$arSelect[] = "UF_COMPANY_ID";
			$arSelect[] = "UF_CONTACT_ID";
			if (IsModuleInstalled("crm") && CModule::IncludeModule("crm"))
				$bCrmModuleIncluded = true;
		}

		$dbOrderList = CSaleOrder::GetList($arOrder, $arFilter, false, $count, $arSelect);

		while($arOrder = $dbOrderList->Fetch())
		{
			if ($crmMode)
			{
				if($bNewVersion && is_array($_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix]) && in_array($arOrder["ID"], $_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix]) && empty($arFilter["ID"]))
					continue;
				ob_start();
			}

			$arResultStat["ORDERS"]++;

			$agentParams = $arAgent[$arOrder["PERSON_TYPE_ID"]];
			$arProp = Array();
			$arProp["ORDER"] = $arOrder;

			if (IntVal($arOrder["USER_ID"]) > 0)
			{
				$dbUser = CUser::GetByID($arOrder["USER_ID"]);
				if ($arUser = $dbUser->Fetch())
					$arProp["USER"] = $arUser;
			}
			if ($bExportFromCrm)
			{
				$arProp["CRM"] = array();
				$companyID = isset($arOrder["UF_COMPANY_ID"]) ? intval($arOrder["UF_COMPANY_ID"]) : 0;
				$contactID = isset($arOrder["UF_CONTACT_ID"]) ? intval($arOrder["UF_CONTACT_ID"]) : 0;
				if ($companyID > 0)
				{
					$arProp["CRM"]["CLIENT_ID"] = "CRMCO".$companyID;
				}
				else
				{
					$arProp["CRM"]["CLIENT_ID"] = "CRMC".$contactID;
				}

				$clientInfo = array(
					"LOGIN" => "",
					"NAME" => "",
					"LAST_NAME" => "",
					"SECOND_NAME" => ""
				);

				if ($bCrmModuleIncluded)
				{
					if ($companyID > 0)
					{
						$arCompanyFilter = array('=ID' => $companyID);
						$dbCompany = CCrmCompany::GetListEx(
							array(), $arCompanyFilter, false, array("nTopCount" => 1),
							array("TITLE")
						);
						$arCompany = $dbCompany->Fetch();
						unset($dbCompany, $arCompanyFilter);
						if (is_array($arCompany))
						{
							if (isset($arCompany["TITLE"]))
								$clientInfo["NAME"] = $arCompany["TITLE"];
						}
						unset($arCompany);
					}
					else if ($contactID > 0)
					{
						$arContactFilter = array('=ID' => $contactID);
						$dbContact = CCrmContact::GetListEx(
							array(), $arContactFilter, false, array("nTopCount" => 1),
							array("NAME", "LAST_NAME", "SECOND_NAME")
						);
						$arContact = $dbContact->Fetch();
						unset($dbContact, $arContactFilter);
						if (is_array($arContact))
						{
							if (isset($arContact["NAME"]))
								$clientInfo["NAME"] = $arContact["NAME"];
							if (isset($arContact["LAST_NAME"]))
								$clientInfo["LAST_NAME"] = $arContact["LAST_NAME"];
							if (isset($arContact["SECOND_NAME"]))
								$clientInfo["SECOND_NAME"] = $arContact["SECOND_NAME"];
						}
						unset($arContact);
					}
				}

				$arProp["CRM"]["CLIENT"] = $clientInfo;
				unset($clientInfo);
			}
			if(IntVal($arOrder["PAY_SYSTEM_ID"]) > 0)
				$arProp["ORDER"]["PAY_SYSTEM_NAME"] = $paySystems[$arOrder["PAY_SYSTEM_ID"]];
			if(strlen($arOrder["DELIVERY_ID"]) > 0)
				$arProp["ORDER"]["DELIVERY_NAME"] = $delivery[$arOrder["DELIVERY_ID"]];

			$dbOrderPropVals = CSaleOrderPropsValue::GetList(
					array(),
					array("ORDER_ID" => $arOrder["ID"]),
					false,
					false,
					array("ID", "CODE", "VALUE", "ORDER_PROPS_ID", "PROP_TYPE")
				);
			$locationStreetPropertyValue = '';
			while ($arOrderPropVals = $dbOrderPropVals->Fetch())
			{
				if ($arOrderPropVals["PROP_TYPE"] == "CHECKBOX")
				{
					if ($arOrderPropVals["VALUE"] == "Y")
						$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = "true";
					else
						$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = "false";
				}
				elseif ($arOrderPropVals["PROP_TYPE"] == "TEXT" || $arOrderPropVals["PROP_TYPE"] == "TEXTAREA")
				{
					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = $arOrderPropVals["VALUE"];
				}
				elseif ($arOrderPropVals["PROP_TYPE"] == "SELECT" || $arOrderPropVals["PROP_TYPE"] == "RADIO")
				{
					$arVal = CSaleOrderPropsVariant::GetByValue($arOrderPropVals["ORDER_PROPS_ID"], $arOrderPropVals["VALUE"]);
					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = $arVal["NAME"];
				}
				elseif ($arOrderPropVals["PROP_TYPE"] == "MULTISELECT")
				{
					$curVal = explode(",", $arOrderPropVals["VALUE"]);
					foreach($curVal as $vm)
					{
						$arVal = CSaleOrderPropsVariant::GetByValue($arOrderPropVals["ORDER_PROPS_ID"], $vm);
						$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] .=  ", ".$arVal["NAME"];
					}
					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = substr($arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]], 2);
				}
				elseif ($arOrderPropVals["PROP_TYPE"] == "LOCATION")
				{
					$arVal = CSaleLocation::GetByID($arOrderPropVals["VALUE"], LANGUAGE_ID);

					if(CSaleLocation::isLocationProEnabled())
					{
						if(intval($arVal['ID']))
						{
							try
							{
								$res = \Bitrix\Sale\Location\LocationTable::getPathToNode($arVal['ID'], array('select' => array('LNAME' => 'NAME.NAME', 'TYPE_ID'), 'filter' => array('=NAME.LANGUAGE_ID' => LANGUAGE_ID)));
								$types = \Bitrix\Sale\Location\Admin\TypeHelper::getTypeCodeIdMapCached();
								$path = array();
								while($item = $res->fetch())
								{
									// copy street to STREET property
									if($types['ID2CODE'][$item['TYPE_ID']] == 'STREET')
										$locationStreetPropertyValue = $item['LNAME'];
									$path[] = $item['LNAME'];
								}

								$locationString = implode(' - ', $path);
							}
							catch(\Bitrix\Main\SystemException $e)
							{
								$locationString = '';
							}
						}
						else
							$locationString = '';
					}
					else
						$locationString =  ($arVal["COUNTRY_NAME"].((strlen($arVal["COUNTRY_NAME"])<=0 || strlen($arVal["REGION_NAME"])<=0) ? "" : " - ").$arVal["REGION_NAME"].((strlen($arVal["COUNTRY_NAME"])<=0 || strlen($arVal["CITY_NAME"])<=0) ? "" : " - ").$arVal["CITY_NAME"]);

					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = $locationString;

					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]."_CITY"] = $arVal["CITY_NAME"];
					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]."_COUNTRY"] = $arVal["COUNTRY_NAME"];
					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]."_REGION"] = $arVal["REGION_NAME"];
				}
				else
				{
					$arProp["PROPERTY"][$arOrderPropVals["ORDER_PROPS_ID"]] = $arOrderPropVals["VALUE"];
				}
			}

			$resShipment = \Bitrix\Sale\Internals\ShipmentTable::getList(
				array(
					'select' => array('DELIVERY_ID', 'ALLOW_DELIVERY', 'DEDUCTED', 'DATE_ALLOW_DELIVERY', 'DATE_DEDUCTED'),
					'filter' => array('ORDER_ID' => $arOrder['ID'], '=SYSTEM' => 'N'),
					'limit' => array(1)
				)
			);
			$arShipment = $resShipment->fetch();

			$resPayment = \Bitrix\Sale\Internals\PaymentTable::getList(
				array(
					'select' => array('PAY_VOUCHER_NUM', 'PAY_SYSTEM_ID', 'PAY_VOUCHER_DATE'),
					'filter' => array('ORDER_ID' => $arOrder['ID'], '!PAY_SYSTEM_ID' => \Bitrix\Sale\Internals\PaySystemInner::getId()),
					'limit' => array(1)
				)
			);
			$arPayment = $resPayment->fetch();

			foreach($agentParams as $k => $v)
			{
				if(strpos($k, "REKV_") !== false)
				{
					if(!is_array($v))
					{
						$agent["REKV"][$k] = $v;
					}
					else
					{
						if(strlen($v["TYPE"])<=0)
							$agent["REKV"][$k] = $v["VALUE"];
						else
							$agent["REKV"][$k] = $arProp[$v["TYPE"]][$v["VALUE"]];
					}
				}
				else
				{
					if(!is_array($v))
					{
						$agent[$k] = $v;
					}
					else
					{
						if(strlen($v["TYPE"])<=0)
							$agent[$k] = $v["VALUE"];
						else
							$agent[$k] = $arProp[$v["TYPE"]][$v["VALUE"]];

						if($k == 'STREET' && strlen($locationStreetPropertyValue))
							$agent[$k] = $locationStreetPropertyValue.(strlen($agent[$k]) ? ', ' : '').$agent[$k];
					}
				}
			}
			?>
			<<?=GetMessage("SALE_EXPORT_DOCUMENT")?>>
				<<?=GetMessage("SALE_EXPORT_ID")?>><?=$arOrder["ID"]?></<?=GetMessage("SALE_EXPORT_ID")?>>
				<<?=GetMessage("SALE_EXPORT_NUMBER")?>><?=$accountNumberPrefix.$arOrder["ACCOUNT_NUMBER"]?></<?=GetMessage("SALE_EXPORT_NUMBER")?>>
				<<?=GetMessage("SALE_EXPORT_DATE")?>><?=$DB->FormatDate($arOrder["DATE_INSERT_FORMAT"], $dateFormat, "YYYY-MM-DD");?></<?=GetMessage("SALE_EXPORT_DATE")?>>
				<<?=GetMessage("SALE_EXPORT_HOZ_OPERATION")?>><?=GetMessage("SALE_EXPORT_ITEM_ORDER")?></<?=GetMessage("SALE_EXPORT_HOZ_OPERATION")?>>
				<<?=GetMessage("SALE_EXPORT_ROLE")?>><?=GetMessage("SALE_EXPORT_SELLER")?></<?=GetMessage("SALE_EXPORT_ROLE")?>>
				<<?=GetMessage("SALE_EXPORT_CURRENCY")?>><?=htmlspecialcharsbx(((strlen($currency)>0)?substr($currency, 0, 3):substr($arOrder["CURRENCY"], 0, 3)))?></<?=GetMessage("SALE_EXPORT_CURRENCY")?>>
				<<?=GetMessage("SALE_EXPORT_CURRENCY_RATE")?>>1</<?=GetMessage("SALE_EXPORT_CURRENCY_RATE")?>>
				<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$arOrder["PRICE"]?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
				<?
				if($bNewVersion)
				{
					?>
					<<?=GetMessage("SALE_EXPORT_VERSION")?>><?=(IntVal($arOrder["VERSION"]) > 0 ? $arOrder["VERSION"] : 0)?></<?=GetMessage("SALE_EXPORT_VERSION")?>>
					<?
					if(strlen($arOrder["ID_1C"]) > 0)
					{
						?><<?=GetMessage("SALE_EXPORT_ID_1C")?>><?=htmlspecialcharsbx($arOrder["ID_1C"])?></<?=GetMessage("SALE_EXPORT_ID_1C")?>><?
					}
				}
				if ($crmMode)
				{
					?><DateUpdate><?=$DB->FormatDate($arOrder["DATE_UPDATE"], $dateFormat, "YYYY-MM-DD HH:MI:SS");?></DateUpdate><?
				}

				$deliveryAdr = CSaleExport::ExportContragents(
					$arOrder, $arProp, $agent, $arResultStat, $bNewVersion,
					$bExportFromCrm ? array("EXPORT_FROM_CRM" => "Y") : array()
				);
				?>
				<<?=GetMessage("SALE_EXPORT_TIME")?>><?=$DB->FormatDate($arOrder["DATE_INSERT_FORMAT"], $dateFormat, "HH:MI:SS");?></<?=GetMessage("SALE_EXPORT_TIME")?>>
				<<?=GetMessage("SALE_EXPORT_COMMENTS")?>><?=htmlspecialcharsbx($arOrder["COMMENTS"])?></<?=GetMessage("SALE_EXPORT_COMMENTS")?>>
				<?
				$dbOrderTax = CSaleOrderTax::GetList(
					array(),
					array("ORDER_ID" => $arOrder["ID"]),
					false,
					false,
					array("ID", "TAX_NAME", "VALUE", "VALUE_MONEY", "CODE", "IS_IN_PRICE")
				);
				$i=-1;
				$orderTax = 0;
				while ($arOrderTax = $dbOrderTax->Fetch())
				{
					$arOrderTax["VALUE_MONEY"] = roundEx($arOrderTax["VALUE_MONEY"], 2);
					$orderTax += $arOrderTax["VALUE_MONEY"];
					$i++;
					if($i == 0)
						echo "<".GetMessage("SALE_EXPORT_TAXES").">";
					?>
					<<?=GetMessage("SALE_EXPORT_TAX")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($arOrderTax["TAX_NAME"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_IN_PRICE")?>><?=(($arOrderTax["IS_IN_PRICE"]=="Y") ? "true" : "false")?></<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>
						<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$arOrderTax["VALUE_MONEY"]?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
					</<?=GetMessage("SALE_EXPORT_TAX")?>>
					<?
				}
				if($i != -1)
					echo "</".GetMessage("SALE_EXPORT_TAXES").">";
				?>
				<?if(DoubleVal($arOrder["DISCOUNT_VALUE"]) > 0)
				{
					?>
					<<?=GetMessage("SALE_EXPORT_DISCOUNTS")?>>
						<<?=GetMessage("SALE_EXPORT_DISCOUNT")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ORDER_DISCOUNT")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$arOrder["DISCOUNT_VALUE"]?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
							<<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>false</<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>
						</<?=GetMessage("SALE_EXPORT_DISCOUNT")?>>
					</<?=GetMessage("SALE_EXPORT_DISCOUNTS")?>>
					<?
				}

				$storeBasket = "";
				if(IntVal($arOrder["STORE_ID"]) > 0 && !empty($arStore[$arOrder["STORE_ID"]]))
				{
					?>
					<<?=GetMessage("SALE_EXPORT_STORIES")?>>
						<<?=GetMessage("SALE_EXPORT_STORY")?>>
							<<?=GetMessage("SALE_EXPORT_ID")?>><?=$arStore[$arOrder["STORE_ID"]]["XML_ID"]?></<?=GetMessage("SALE_EXPORT_ID")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($arStore[$arOrder["STORE_ID"]]["TITLE"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_ADDRESS")?>>
								<<?=GetMessage("SALE_EXPORT_PRESENTATION")?>><?=htmlspecialcharsbx($arStore[$arOrder["STORE_ID"]]["ADDRESS"])?></<?=GetMessage("SALE_EXPORT_PRESENTATION")?>>
								<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
									<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_STREET")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
									<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arStore[$arOrder["STORE_ID"]]["ADDRESS"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
								</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS")?>>
							<<?=GetMessage("SALE_EXPORT_CONTACTS")?>>
								<<?=GetMessage("SALE_EXPORT_CONTACT")?>>
									<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=($bNewVersion ? GetMessage("SALE_EXPORT_WORK_PHONE_NEW") : GetMessage("SALE_EXPORT_WORK_PHONE"))?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
									<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arStore[$arOrder["STORE_ID"]]["PHONE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
								</<?=GetMessage("SALE_EXPORT_CONTACT")?>>
							</<?=GetMessage("SALE_EXPORT_CONTACTS")?>>
						</<?=GetMessage("SALE_EXPORT_STORY")?>>
					</<?=GetMessage("SALE_EXPORT_STORIES")?>>
					<?
					/*
					$storeBasket = "				
						<".GetMessage("SALE_EXPORT_STORIES").">
							<".GetMessage("SALE_EXPORT_STORY").">
								<".GetMessage("SALE_EXPORT_ID").">".$arStore[$arOrder["STORE_ID"]]["XML_ID"]."</".GetMessage("SALE_EXPORT_ID").">
								<".GetMessage("SALE_EXPORT_ITEM_NAME").">".htmlspecialcharsbx($arStore[$arOrder["STORE_ID"]]["TITLE"])."</".GetMessage("SALE_EXPORT_ITEM_NAME").">
							</".GetMessage("SALE_EXPORT_STORY").">
						</".GetMessage("SALE_EXPORT_STORIES").">
						";
					*/
				}
				?>
				<<?=GetMessage("SALE_EXPORT_ITEMS")?>>
				<?
				$dbBasket = \Bitrix\Sale\Internals\BasketTable::getList(array(
					'select' => array("ID", "NOTES", "PRODUCT_XML_ID", "CATALOG_XML_ID", "NAME", "PRICE", "QUANTITY", "DISCOUNT_PRICE", "VAT_RATE", "MEASURE_CODE"),
					'filter' => array("ORDER_ID" => $arOrder["ID"]),
					'order' => array("NAME" => "ASC")
				));

				$basketSum = 0;
				$priceType = "";
				$bVat = false;
				$vatRate = 0;
				$vatSum = 0;
				while ($arBasket = $dbBasket->fetch())
				{
					if(strlen($priceType) <= 0)
						$priceType = $arBasket["NOTES"];
					?>
					<<?=GetMessage("SALE_EXPORT_ITEM")?>>
						<<?=GetMessage("SALE_EXPORT_ID")?>><?=htmlspecialcharsbx($arBasket["PRODUCT_XML_ID"])?></<?=GetMessage("SALE_EXPORT_ID")?>>
						<<?=GetMessage("SALE_EXPORT_CATALOG_ID")?>><?=htmlspecialcharsbx($arBasket["CATALOG_XML_ID"])?></<?=GetMessage("SALE_EXPORT_CATALOG_ID")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($arBasket["NAME"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<?
						if($bNewVersion)
						{
							if(IntVal($arBasket["MEASURE_CODE"]) <= 0)
								$arBasket["MEASURE_CODE"] = 796;
							?>
							<<?=GetMessage("SALE_EXPORT_UNIT")?>>
								<<?=GetMessage("SALE_EXPORT_CODE")?>><?=$arBasket["MEASURE_CODE"]?></<?=GetMessage("SALE_EXPORT_CODE")?>>
								<<?=GetMessage("SALE_EXPORT_FULL_NAME_UNIT")?>><?=htmlspecialcharsbx($arMeasures[$arBasket["MEASURE_CODE"]])?></<?=GetMessage("SALE_EXPORT_FULL_NAME_UNIT")?>>
							</<?=GetMessage("SALE_EXPORT_UNIT")?>>
							<<?=GetMessage("SALE_EXPORT_KOEF")?>>1</<?=GetMessage("SALE_EXPORT_KOEF")?>>
							<?
						}
						else
						{
							?>
							<<?=GetMessage("SALE_EXPORT_BASE_UNIT")?> <?=GetMessage("SALE_EXPORT_CODE")?>="796" <?=GetMessage("SALE_EXPORT_FULL_NAME_UNIT")?>="<?=GetMessage("SALE_EXPORT_SHTUKA")?>" <?=GetMessage("SALE_EXPORT_INTERNATIONAL_ABR")?>="<?=GetMessage("SALE_EXPORT_RCE")?>"><?=GetMessage("SALE_EXPORT_SHT")?></<?=GetMessage("SALE_EXPORT_BASE_UNIT")?>>
							<?
						}
						if(DoubleVal($arBasket["DISCOUNT_PRICE"]) > 0)
						{
							?>
							<<?=GetMessage("SALE_EXPORT_DISCOUNTS")?>>
								<<?=GetMessage("SALE_EXPORT_DISCOUNT")?>>
									<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ITEM_DISCOUNT")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
									<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$arBasket["DISCOUNT_PRICE"]?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
									<<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>true</<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>
								</<?=GetMessage("SALE_EXPORT_DISCOUNT")?>>
							</<?=GetMessage("SALE_EXPORT_DISCOUNTS")?>>
							<?
						}
						?>
						<<?=GetMessage("SALE_EXPORT_PRICE_PER_ITEM")?>><?=$arBasket["PRICE"]?></<?=GetMessage("SALE_EXPORT_PRICE_PER_ITEM")?>>
						<<?=GetMessage("SALE_EXPORT_QUANTITY")?>><?=$arBasket["QUANTITY"]?></<?=GetMessage("SALE_EXPORT_QUANTITY")?>>
						<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$arBasket["PRICE"]*$arBasket["QUANTITY"]?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
						<<?=GetMessage("SALE_EXPORT_PROPERTIES_VALUES")?>>
							<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
								<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_TYPE_NOMENKLATURA")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
								<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=GetMessage("SALE_EXPORT_ITEM")?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
								<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_TYPE_OF_NOMENKLATURA")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
								<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=GetMessage("SALE_EXPORT_ITEM")?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<?
							$dbProp = CSaleBasket::GetPropsList(Array("SORT" => "ASC", "ID" => "ASC"), Array("BASKET_ID" => $arBasket["ID"], "!CODE" => array("CATALOG.XML_ID", "PRODUCT.XML_ID")), false, false, array("NAME", "VALUE", "CODE"));
							while($arProp = $dbProp->Fetch())
							{
								?>
								<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
									<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($arProp["NAME"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
									<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arProp["VALUE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
								</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
								<?
							}
							?>
						</<?=GetMessage("SALE_EXPORT_PROPERTIES_VALUES")?>>
						<?if(DoubleVal($arBasket["VAT_RATE"]) > 0)
						{
							$bVat = true;
							$vatRate = DoubleVal($arBasket["VAT_RATE"]);
							$basketVatSum = (($arBasket["PRICE"] / ($arBasket["VAT_RATE"]+1)) * $arBasket["VAT_RATE"]);
							$vatSum += roundEx($basketVatSum * $arBasket["QUANTITY"], 2);
							?>
							<<?=GetMessage("SALE_EXPORT_TAX_RATES")?>>
								<<?=GetMessage("SALE_EXPORT_TAX_RATE")?>>
									<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_VAT")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
									<<?=GetMessage("SALE_EXPORT_RATE")?>><?=$arBasket["VAT_RATE"] * 100?></<?=GetMessage("SALE_EXPORT_RATE")?>>
								</<?=GetMessage("SALE_EXPORT_TAX_RATE")?>>
							</<?=GetMessage("SALE_EXPORT_TAX_RATES")?>>
							<<?=GetMessage("SALE_EXPORT_TAXES")?>>
								<<?=GetMessage("SALE_EXPORT_TAX")?>>
									<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_VAT")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
									<<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>true</<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>
									<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=roundEx($basketVatSum, 2)?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
								</<?=GetMessage("SALE_EXPORT_TAX")?>>
							</<?=GetMessage("SALE_EXPORT_TAXES")?>>
							<?
						}
						?>
						<?=$storeBasket?>
					</<?=GetMessage("SALE_EXPORT_ITEM")?>>
					<?
					$basketSum += $arBasket["PRICE"]*$arBasket["QUANTITY"];
				}

				if(IntVal($arOrder["PRICE_DELIVERY"]) > 0)
				{
					?>
					<<?=GetMessage("SALE_EXPORT_ITEM")?>>
						<<?=GetMessage("SALE_EXPORT_ID")?>>ORDER_DELIVERY</<?=GetMessage("SALE_EXPORT_ID")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ORDER_DELIVERY")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<?
						if($bNewVersion)
						{
							?>
							<<?=GetMessage("SALE_EXPORT_UNIT")?>>
							<<?=GetMessage("SALE_EXPORT_CODE")?>>796</<?=GetMessage("SALE_EXPORT_CODE")?>>
							<<?=GetMessage("SALE_EXPORT_FULL_NAME_UNIT")?>><?=htmlspecialcharsbx($arMeasures[796])?></<?=GetMessage("SALE_EXPORT_FULL_NAME_UNIT")?>>
							</<?=GetMessage("SALE_EXPORT_UNIT")?>>
							<<?=GetMessage("SALE_EXPORT_KOEF")?>>1</<?=GetMessage("SALE_EXPORT_KOEF")?>>
						<?
						}
						else
						{
							?>
							<<?=GetMessage("SALE_EXPORT_BASE_UNIT")?> <?=GetMessage("SALE_EXPORT_CODE")?>="796" <?=GetMessage("SALE_EXPORT_FULL_NAME_UNIT")?>="<?=GetMessage("SALE_EXPORT_SHTUKA")?>" <?=GetMessage("SALE_EXPORT_INTERNATIONAL_ABR")?>="<?=GetMessage("SALE_EXPORT_RCE")?>"><?=GetMessage("SALE_EXPORT_SHT")?></<?=GetMessage("SALE_EXPORT_BASE_UNIT")?>>
						<?
						}
						?>
						<<?=GetMessage("SALE_EXPORT_PRICE_PER_ITEM")?>><?=$arOrder["PRICE_DELIVERY"]?></<?=GetMessage("SALE_EXPORT_PRICE_PER_ITEM")?>>
						<<?=GetMessage("SALE_EXPORT_QUANTITY")?>>1</<?=GetMessage("SALE_EXPORT_QUANTITY")?>>
						<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$arOrder["PRICE_DELIVERY"]?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
						<<?=GetMessage("SALE_EXPORT_PROPERTIES_VALUES")?>>
							<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
								<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_TYPE_NOMENKLATURA")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
								<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=GetMessage("SALE_EXPORT_SERVICE")?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
								<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_TYPE_OF_NOMENKLATURA")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
								<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=GetMessage("SALE_EXPORT_SERVICE")?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTIES_VALUES")?>>
						<?if($bVat)
						{
							$deliveryTax = roundEx((($arOrder["PRICE_DELIVERY"] / ($vatRate+1)) * $vatRate), 2);
							if($orderTax > $vatSum && $orderTax == roundEx($vatSum + $deliveryTax, 2))
							{
								?>
								<<?=GetMessage("SALE_EXPORT_TAX_RATES")?>>
									<<?=GetMessage("SALE_EXPORT_TAX_RATE")?>>
										<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_VAT")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
										<<?=GetMessage("SALE_EXPORT_RATE")?>><?=$vatRate * 100?></<?=GetMessage("SALE_EXPORT_RATE")?>>
									</<?=GetMessage("SALE_EXPORT_TAX_RATE")?>>
								</<?=GetMessage("SALE_EXPORT_TAX_RATES")?>>
								<<?=GetMessage("SALE_EXPORT_TAXES")?>>
									<<?=GetMessage("SALE_EXPORT_TAX")?>>
										<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_VAT")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
										<<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>true</<?=GetMessage("SALE_EXPORT_IN_PRICE")?>>
										<<?=GetMessage("SALE_EXPORT_AMOUNT")?>><?=$deliveryTax?></<?=GetMessage("SALE_EXPORT_AMOUNT")?>>
									</<?=GetMessage("SALE_EXPORT_TAX")?>>
								</<?=GetMessage("SALE_EXPORT_TAXES")?>>
								<?
							}
						}
						?>
					</<?=GetMessage("SALE_EXPORT_ITEM")?>>
					<?
				}
				?>
				</<?=GetMessage("SALE_EXPORT_ITEMS")?>>
				<<?=GetMessage("SALE_EXPORT_PROPERTIES_VALUES")?>>
					<?if(strlen($arOrder["DATE_PAYED"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_DATE_PAID")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=$arOrder["DATE_PAYED"]?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					if(strlen($arPayment["PAY_VOUCHER_NUM"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_PAY_NUMBER")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arPayment["PAY_VOUCHER_NUM"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					if(IntVal($arPayment["PAY_SYSTEM_ID"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_PAY_SYSTEM")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($paySystems[$arPayment["PAY_SYSTEM_ID"]])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_PAY_SYSTEM_ID")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arPayment["PAY_SYSTEM_ID"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					if(strlen($arShipment["DATE_ALLOW_DELIVERY"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_DATE_ALLOW_DELIVERY")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=$arShipment["DATE_ALLOW_DELIVERY"]?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					if(strlen($arShipment["DELIVERY_ID"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_DELIVERY_SERVICE")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($delivery[$arShipment["DELIVERY_ID"]])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					?>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ORDER_PAID")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=($arOrder["PAYED"]=="Y")?"true":"false";?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ALLOW_DELIVERY")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=($arShipment["ALLOW_DELIVERY"]=="Y")?"true":"false";?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_CANCELED")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=($arOrder["CANCELED"]=="Y")?"true":"false";?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_FINAL_STATUS")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=($arOrder["STATUS_ID"]=="F")?"true":"false";?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ORDER_STATUS")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?$arStatus = CSaleStatus::GetLangByID($arOrder["STATUS_ID"]); echo htmlspecialcharsbx("[".$arOrder["STATUS_ID"]."] ".$arStatus["NAME"]);?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_ORDER_STATUS_ID")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
					<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arOrder["STATUS_ID"]);?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<?if(strlen($arOrder["DATE_CANCELED"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_DATE_CANCEL")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=$arOrder["DATE_CANCELED"]?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_CANCEL_REASON")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arOrder["REASON_CANCELED"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					if(strlen($arOrder["DATE_STATUS"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_DATE_STATUS")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=$arOrder["DATE_STATUS"]?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					if(strlen($arOrder["USER_DESCRIPTION"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
							<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_USER_DESCRIPTION")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($arOrder["USER_DESCRIPTION"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<?
					}
					$dbSite = CSite::GetByID($arOrder["LID"]);
					$arSite = $dbSite->Fetch();
					?>
					<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_SITE_NAME")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>>[<?=$arOrder["LID"]?>] <?=htmlspecialcharsbx($arSite["NAME"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
					</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
					<?
					if(!empty($agent["REKV"]))
					{
						foreach($agent["REKV"] as $k => $v)
						{
							if(strlen($agentParams[$k]["NAME"]) > 0 && strlen($v) > 0)
							{
								?>
								<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
									<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($agentParams[$k]["NAME"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
									<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($v)?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
								</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
								<?
							}
						}
					}

					if(strlen($deliveryAdr) > 0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($deliveryAdr)?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_PROPERTY_VALUE")?>>

						<?
					}
					?>
				</<?=GetMessage("SALE_EXPORT_PROPERTIES_VALUES")?>>
			</<?=GetMessage("SALE_EXPORT_DOCUMENT")?>>
			<?
			if ($crmMode)
			{
				$c = ob_get_clean();
				$c = CharsetConverter::ConvertCharset($c, $arCharSets[$arOrder["LID"]], "utf-8");
				echo $c;
				$_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix][] = $arOrder["ID"];
			}
			else
			{
				$_SESSION["BX_CML2_EXPORT"][$lastOrderPrefix] = $arOrder["ID"];
			}

			if(IntVal($time_limit) > 0 && time() > $end_time)
			{
				break;
			}
		}
		?>
		</<?=GetMessage("SALE_EXPORT_COM_INFORMATION")?>>
		<?
		return $arResultStat;
	}

	public static function UnZip($file_name, $last_zip_entry = "", $interval = 0)
	{
		global $APPLICATION;
		$start_time = time();

		$io = CBXVirtualIo::GetInstance();

		//Function and securioty checks
		if(!function_exists("zip_open"))
			return false;
		$dir_name = substr($file_name, 0, strrpos($file_name, "/")+1);
		if(strlen($dir_name) <= strlen($_SERVER["DOCUMENT_ROOT"]))
			return false;

		$hZip = zip_open($file_name);
		if(!$hZip)
			return false;
		//Skip from last step
		if($last_zip_entry)
		{
			while($entry = zip_read($hZip))
				if(zip_entry_name($entry) == $last_zip_entry)
					break;
		}

		$io = CBXVirtualIo::GetInstance();
		//Continue unzip
		while($entry = zip_read($hZip))
		{
			$entry_name = zip_entry_name($entry);
			//Check for directory
			zip_entry_open($hZip, $entry);
			if(zip_entry_filesize($entry))
			{

				$file_name = trim(str_replace("\\", "/", trim($entry_name)), "/");
				$file_name = $APPLICATION->ConvertCharset($file_name, "cp866", LANG_CHARSET);

				$bBadFile = HasScriptExtension($file_name)
					|| IsFileUnsafe($file_name)
					|| !$io->ValidatePathString("/".$file_name)
				;

				if(!$bBadFile)
				{
					$file_name =  $io->GetPhysicalName($dir_name.rel2abs("/", $file_name));
					CheckDirPath($file_name);
					$fout = fopen($file_name, "wb");
					if(!$fout)
						return false;
					while($data = zip_entry_read($entry, 102400))
					{
						$data_len = function_exists('mb_strlen') ? mb_strlen($data, 'latin1') : strlen($data);
						$result = fwrite($fout, $data);
						if($result !== $data_len)
							return false;
					}
				}
			}
			zip_entry_close($entry);

			//Jump to next step
			if($interval > 0 && (time()-$start_time) > ($interval))
			{
				zip_close($hZip);
				return $entry_name;
			}
		}
		zip_close($hZip);
		return true;
	}

	public static function ExportContragents($arOrder = array(), $arProp = array(), $agent = array(), &$arResultStat, $bNewVersion = false, $arOptions = array())
	{
		$bExportFromCrm = (isset($arOptions["EXPORT_FROM_CRM"]) && $arOptions["EXPORT_FROM_CRM"] === "Y");
		?>
		<<?=GetMessage("SALE_EXPORT_CONTRAGENTS")?>>
			<<?=GetMessage("SALE_EXPORT_CONTRAGENT")?>><?
		if ($bExportFromCrm): ?>
				<<?=GetMessage("SALE_EXPORT_ID")?>><?=htmlspecialcharsbx(substr($arProp["CRM"]["CLIENT_ID"]."#".$arProp["CRM"]["CLIENT"]["LOGIN"]."#".$arProp["CRM"]["CLIENT"]["LAST_NAME"]." ".$arProp["CRM"]["CLIENT"]["NAME"]." ".$arProp["CRM"]["CLIENT"]["SECOND_NAME"], 0, 80))?></<?=GetMessage("SALE_EXPORT_ID")?>><?
		else: ?>
				<<?=GetMessage("SALE_EXPORT_ID")?>><?=htmlspecialcharsbx(substr($arOrder["USER_ID"]."#".$arProp["USER"]["LOGIN"]."#".$arProp["USER"]["LAST_NAME"]." ".$arProp["USER"]["NAME"]." ".$arProp["USER"]["SECOND_NAME"], 0, 80))?></<?=GetMessage("SALE_EXPORT_ID")?>><?
		endif; ?>
				<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($agent["AGENT_NAME"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
				<?
				$deliveryAdr = $agent["ADDRESS_FULL"];
				$address = "<".GetMessage("SALE_EXPORT_PRESENTATION").">".htmlspecialcharsbx($agent["ADDRESS_FULL"])."</".GetMessage("SALE_EXPORT_PRESENTATION").">";
				if(strlen($agent["INDEX"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_POST_CODE")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["INDEX"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["COUNTRY"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
									<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_COUNTRY")."</".GetMessage("SALE_EXPORT_TYPE").">
									<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["COUNTRY"])."</".GetMessage("SALE_EXPORT_VALUE").">
								</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["REGION"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_REGION")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["REGION"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["STATE"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_STATE")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["STATE"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["TOWN"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_SMALL_CITY")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["TOWN"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["CITY"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_CITY")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["CITY"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["STREET"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_STREET")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["STREET"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["HOUSE"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_HOUSE")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["HOUSE"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["BUILDING"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_BUILDING")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["BUILDING"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}
				if(strlen($agent["FLAT"])>0)
				{
					$address .= "<".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">
								<".GetMessage("SALE_EXPORT_TYPE").">".GetMessage("SALE_EXPORT_FLAT")."</".GetMessage("SALE_EXPORT_TYPE").">
								<".GetMessage("SALE_EXPORT_VALUE").">".htmlspecialcharsbx($agent["FLAT"])."</".GetMessage("SALE_EXPORT_VALUE").">
							</".GetMessage("SALE_EXPORT_ADDRESS_FIELD").">";
				}

				if($agent["IS_FIZ"]=="Y")
				{
					$arResultStat["CONTACTS"]++;
					?>
					<<?=GetMessage("SALE_EXPORT_FULL_NAME")?>><?=htmlspecialcharsbx($agent["FULL_NAME"])?></<?=GetMessage("SALE_EXPORT_FULL_NAME")?>>
					<?
					if(strlen($agent["SURNAME"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_SURNAME")?>><?=htmlspecialcharsbx($agent["SURNAME"])?></<?=GetMessage("SALE_EXPORT_SURNAME")?>><?
					}
					if(strlen($agent["NAME"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_NAME")?>><?=htmlspecialcharsbx($agent["NAME"])?></<?=GetMessage("SALE_EXPORT_NAME")?>><?
					}
					if(strlen($agent["SECOND_NAME"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_MIDDLE_NAME")?>><?=htmlspecialcharsbx($agent["SECOND_NAME"])?></<?=GetMessage("SALE_EXPORT_MIDDLE_NAME")?>><?
					}
					if(strlen($agent["BIRTHDAY"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_BIRTHDAY")?>><?=htmlspecialcharsbx($agent["BIRTHDAY"])?></<?=GetMessage("SALE_EXPORT_BIRTHDAY")?>><?
					}
					if(strlen($agent["MALE"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_SEX")?>><?=htmlspecialcharsbx($agent["MALE"])?></<?=GetMessage("SALE_EXPORT_SEX")?>><?
					}
					if(strlen($agent["INN"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_INN")?>><?=htmlspecialcharsbx($agent["INN"])?></<?=GetMessage("SALE_EXPORT_INN")?>><?
					}
					if(strlen($agent["KPP"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_KPP")?>><?=htmlspecialcharsbx($agent["KPP"])?></<?=GetMessage("SALE_EXPORT_KPP")?>><?
					}
					?>
					<<?=GetMessage("SALE_EXPORT_REGISTRATION_ADDRESS")?>>
					<?=$address?>
					</<?=GetMessage("SALE_EXPORT_REGISTRATION_ADDRESS")?>>
				<?
				}
				else
				{
					$arResultStat["COMPANIES"]++;
					?>
					<<?=GetMessage("SALE_EXPORT_OFICIAL_NAME")?>><?=htmlspecialcharsbx($agent["FULL_NAME"])?></<?=GetMessage("SALE_EXPORT_OFICIAL_NAME")?>>
					<<?=GetMessage("SALE_EXPORT_UR_ADDRESS")?>>
					<?=$address?>
					</<?=GetMessage("SALE_EXPORT_UR_ADDRESS")?>>
					<?
					if(strlen($agent["INN"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_INN")?>><?=htmlspecialcharsbx($agent["INN"])?></<?=GetMessage("SALE_EXPORT_INN")?>><?
					}
					if(strlen($agent["KPP"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_KPP")?>><?=htmlspecialcharsbx($agent["KPP"])?></<?=GetMessage("SALE_EXPORT_KPP")?>><?
					}
					if(strlen($agent["EGRPO"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_EGRPO")?>><?=htmlspecialcharsbx($agent["EGRPO"])?></<?=GetMessage("SALE_EXPORT_EGRPO")?>><?
					}
					if(strlen($agent["OKVED"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_OKVED")?>><?=htmlspecialcharsbx($agent["OKVED"])?></<?=GetMessage("SALE_EXPORT_OKVED")?>><?
					}
					if(strlen($agent["OKDP"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_OKDP")?>><?=htmlspecialcharsbx($agent["OKDP"])?></<?=GetMessage("SALE_EXPORT_OKDP")?>><?
					}
					if(strlen($agent["OKOPF"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_OKOPF")?>><?=htmlspecialcharsbx($agent["OKOPF"])?></<?=GetMessage("SALE_EXPORT_OKOPF")?>><?
					}
					if(strlen($agent["OKFC"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_OKFC")?>><?=htmlspecialcharsbx($agent["OKFC"])?></<?=GetMessage("SALE_EXPORT_OKFC")?>><?
					}
					if(strlen($agent["OKPO"])>0)
					{
						?><<?=GetMessage("SALE_EXPORT_OKPO")?>><?=htmlspecialcharsbx($agent["OKPO"])?></<?=GetMessage("SALE_EXPORT_OKPO")?>><?
					}
					if(strlen($agent["ACCOUNT_NUMBER"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_MONEY_ACCOUNTS")?>>
						<<?=GetMessage("SALE_EXPORT_MONEY_ACCOUNT")?>>
						<<?=GetMessage("SALE_EXPORT_ACCOUNT_NUMBER")?>><?=htmlspecialcharsbx($agent["ACCOUNT_NUMBER"])?></<?=GetMessage("SALE_EXPORT_ACCOUNT_NUMBER")?>>
						<<?=GetMessage("SALE_EXPORT_BANK")?>>
						<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($agent["B_NAME"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
						<<?=GetMessage("SALE_EXPORT_ADDRESS")?>>
						<<?=GetMessage("SALE_EXPORT_PRESENTATION")?>><?=htmlspecialcharsbx($agent["B_ADDRESS_FULL"])?></<?=GetMessage("SALE_EXPORT_PRESENTATION")?>>
						<?
						if(strlen($agent["B_INDEX"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_POST_CODE")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_INDEX"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_COUNTRY"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_COUNTRY")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_COUNTRY"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_REGION"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_REGION")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_REGION"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_STATE"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_STATE")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_STATE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_TOWN"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_SMALL_CITY")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_TOWN"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_CITY"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_CITY")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_CITY"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_STREET"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_STREET")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_STREET"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_HOUSE"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_HOUSE")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_HOUSE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_BUILDING"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_BUILDING")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_BUILDING"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						if(strlen($agent["B_FLAT"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
							<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_FLAT")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
							<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["B_FLAT"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
							</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>><?
						}
						?>
						</<?=GetMessage("SALE_EXPORT_ADDRESS")?>>
						<?
						if(strlen($agent["B_BIK"])>0)
						{
							?><<?=GetMessage("SALE_EXPORT_BIC")?>><?=htmlspecialcharsbx($agent["B_BIK"])?></<?=GetMessage("SALE_EXPORT_BIC")?>><?
						}
						?>
						</<?=GetMessage("SALE_EXPORT_BANK")?>>
						</<?=GetMessage("SALE_EXPORT_MONEY_ACCOUNT")?>>
						</<?=GetMessage("SALE_EXPORT_MONEY_ACCOUNTS")?>>
					<?
					}
				}
				if(strlen($agent["F_ADDRESS_FULL"])>0)
				{
					$deliveryAdr = $agent["F_ADDRESS_FULL"];
					?>
					<<?=GetMessage("SALE_EXPORT_ADDRESS")?>>
					<<?=GetMessage("SALE_EXPORT_PRESENTATION")?>><?=htmlspecialcharsbx($agent["F_ADDRESS_FULL"])?></<?=GetMessage("SALE_EXPORT_PRESENTATION")?>>
					<?
					if(strlen($agent["F_INDEX"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_POST_CODE")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_INDEX"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_COUNTRY"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_COUNTRY")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_COUNTRY"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_REGION"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_REGION")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_REGION"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_STATE"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_STATE")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_STATE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_TOWN"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_SMALL_CITY")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_TOWN"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_CITY"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_CITY")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_CITY"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_STREET"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_STREET")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_STREET"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_HOUSE"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_HOUSE")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_HOUSE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_BUILDING"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_BUILDING")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_BUILDING"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					if(strlen($agent["F_FLAT"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=GetMessage("SALE_EXPORT_FLAT")?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["F_FLAT"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_ADDRESS_FIELD")?>>
					<?
					}
					?>
					</<?=GetMessage("SALE_EXPORT_ADDRESS")?>>
				<?
				}
				if(strlen($agent["PHONE"])>0 || strlen($agent["EMAIL"])>0)
				{
					?>
					<<?=GetMessage("SALE_EXPORT_CONTACTS")?>>
					<?
					if(strlen($agent["PHONE"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_CONTACT")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=($bNewVersion ? GetMessage("SALE_EXPORT_WORK_PHONE_NEW") : GetMessage("SALE_EXPORT_WORK_PHONE"))?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["PHONE"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_CONTACT")?>>
					<?
					}
					if(strlen($agent["EMAIL"])>0)
					{
						?>
						<<?=GetMessage("SALE_EXPORT_CONTACT")?>>
						<<?=GetMessage("SALE_EXPORT_TYPE")?>><?=($bNewVersion ? GetMessage("SALE_EXPORT_MAIL_NEW") : GetMessage("SALE_EXPORT_MAIL"))?></<?=GetMessage("SALE_EXPORT_TYPE")?>>
						<<?=GetMessage("SALE_EXPORT_VALUE")?>><?=htmlspecialcharsbx($agent["EMAIL"])?></<?=GetMessage("SALE_EXPORT_VALUE")?>>
						</<?=GetMessage("SALE_EXPORT_CONTACT")?>>
					<?
					}
					?>
					</<?=GetMessage("SALE_EXPORT_CONTACTS")?>>
				<?
				}
				if(strlen($agent["CONTACT_PERSON"])>0)
				{
					?>
					<<?=GetMessage("SALE_EXPORT_REPRESENTATIVES")?>>
					<<?=GetMessage("SALE_EXPORT_REPRESENTATIVE")?>>
					<<?=GetMessage("SALE_EXPORT_CONTRAGENT")?>>
					<<?=GetMessage("SALE_EXPORT_RELATION")?>><?=GetMessage("SALE_EXPORT_CONTACT_PERSON")?></<?=GetMessage("SALE_EXPORT_RELATION")?>>
					<<?=GetMessage("SALE_EXPORT_ID")?>><?=md5($agent["CONTACT_PERSON"])?></<?=GetMessage("SALE_EXPORT_ID")?>>
					<<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>><?=htmlspecialcharsbx($agent["CONTACT_PERSON"])?></<?=GetMessage("SALE_EXPORT_ITEM_NAME")?>>
					</<?=GetMessage("SALE_EXPORT_CONTRAGENT")?>>
					</<?=GetMessage("SALE_EXPORT_REPRESENTATIVE")?>>
					</<?=GetMessage("SALE_EXPORT_REPRESENTATIVES")?>>
				<?
				}?>
				<<?=GetMessage("SALE_EXPORT_ROLE")?>><?=GetMessage("SALE_EXPORT_BUYER")?></<?=GetMessage("SALE_EXPORT_ROLE")?>>
			</<?=GetMessage("SALE_EXPORT_CONTRAGENT")?>>
		</<?=GetMessage("SALE_EXPORT_CONTRAGENTS")?>>
		<?
		return $deliveryAdr;
	}

}
?>