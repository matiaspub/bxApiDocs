<?
if (!CModule::IncludeModule("sale"))
	return false;

IncludeModuleLangFile(__FILE__);

class CCatalogProductProvider implements IBXSaleProductProvider
{
	public static function GetProductData($arParams)
	{
		if (!is_set($arParams, "QUANTITY") || doubleval($arParams["QUANTITY"]) <= 0)
			$arParams["QUANTITY"] = 0;

		if (!is_set($arParams, "RENEWAL") || $arParams["RENEWAL"] != "Y")
			$arParams["RENEWAL"] = "N";

		if (!is_set($arParams, "USER_ID") || IntVal($arParams["USER_ID"]) <= 0)
			$arParams["USER_ID"] = 0;

		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = false;

		return CatalogBasketCallback(
			$arParams["PRODUCT_ID"],
			$arParams["QUANTITY"],
			$arParams["RENEWAL"],
			$arParams["USER_ID"],
			$arParams["SITE_ID"]
		);
	}

	public static function OrderProduct($arParams)
	{
		if (!is_set($arParams, "RENEWAL") || $arParams["RENEWAL"] != "Y")
			$arParams["RENEWAL"] = "N";

		if (!is_set($arParams, "USER_ID") || IntVal($arParams["USER_ID"]) <= 0)
			$arParams["USER_ID"] = 0;

		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = false;

		global $USER;
		global $DB;

		$productID = intval($arParams["PRODUCT_ID"]);
		$quantity = doubleval($arParams["QUANTITY"]);
		$renewal = (($arParams["RENEWAL"] == "Y") ? "Y" : "N");
		$strSiteID = $arParams["SITE_ID"];

		$intUserID = intval($arParams["USER_ID"]);
		if (0 > $intUserID)
			$intUserID = 0;

		$arResult = array();

		static $arUserCache = array();
		if (0 < $intUserID)
		{
			if (!array_key_exists($intUserID,$arUserCache))
			{
				$rsUsers = CUser::GetList(($by = 'ID'),($order = 'DESC'),array("ID_EQUAL_EXACT"=>$intUserID),array('FIELDS' => array('ID')));
				if ($arUser = $rsUsers->Fetch())
				{
					$arUserCache[$arUser['ID']] = CUser::GetUserGroup($arUser['ID']);
				}
				else
				{
					$intUserID = 0;
					return $arResult;
				}
			}

			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
						"ID" => $productID,
						"ACTIVE" => "Y",
						"ACTIVE_DATE" => "Y",
						"CHECK_PERMISSION" => "N",
					),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
				);
			if(!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;

			if ('E' == CIBlock::GetArrayByID($arProduct['IBLOCK_ID'], "RIGHTS_MODE"))
			{
				$arUserRights = CIBlockElementRights::GetUserOperations($productID,$intUserID);
				if (empty($arUserRights))
				{
					return $arResult;
				}
				elseif (!is_array($arUserRights) || !array_key_exists('element_read',$arUserRights))
				{
					return $arResult;
				}
			}
			else
			{
				if ('R' > CIBlock::GetPermission($arProduct['IBLOCK_ID'], $intUserID))
				{
					return $arResult;
				}
			}
		}
		else
		{
			$dbIBlockElement = CIBlockElement::GetList(
				array(),
				array(
					"ID" => $productID,
					"ACTIVE" => "Y",
					"ACTIVE_DATE" => "Y",
					"CHECK_PERMISSIONS" => "Y",
					"MIN_PERMISSION" => "R",
				),
				false,
				false,
				array('ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL')
			);
			if(!($arProduct = $dbIBlockElement->GetNext()))
				return $arResult;
		}

		$rsProducts = CCatalogProduct::GetList(
		array(),
		array('ID' => $productID),
		false,
		false,
		array(
			'ID',
			'CAN_BUY_ZERO',
			'QUANTITY_TRACE',
			'QUANTITY',
			'WEIGHT',
			)
		);

		if ($arCatalogProduct = $rsProducts->Fetch())
		{
			if ('Y' != $arCatalogProduct["CAN_BUY_ZERO"] && 'Y' == $arCatalogProduct["QUANTITY_TRACE"] && 0 >= doubleval($arCatalogProduct["QUANTITY"]))
				return $arResult;
		}
		else
		{
			return $arResult;
		}

		if (0 < $intUserID)
		{
			$arCoupons = CCatalogDiscountCoupon::GetCouponsByManage($intUserID);
			CCatalogDiscountSave::SetDiscountUserID($intUserID);
		}
		else
		{
			$arCoupons = CCatalogDiscountCoupon::GetCoupons();
		}

		$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray()), $renewal, array(), (0 < $intUserID ? $strSiteID : false), $arCoupons);

		if (empty($arPrice))
		{
			if ($nearestQuantity = CCatalogProduct::GetNearestQuantityPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray())))
			{
				$quantity = $nearestQuantity;
				$arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, (0 < $intUserID ? $arUserCache[$intUserID] : $USER->GetUserGroupArray()), $renewal, array(), (0 < $intUserID ? $strSiteID : false), $arCoupons);
			}
		}

		if (empty($arPrice))
		{
			if (0 < $intUserID)
			{
				CCatalogDiscountSave::ClearDiscountUserID();
			}
			return $arResult;
		}

		$boolDiscountVat = ('N' != COption::GetOptionString('catalog', 'discount_vat', 'Y'));

		$currentPrice = $arPrice["PRICE"]["PRICE"];
		$currentDiscount = 0.0;

		if ($boolDiscountVat)
		{
			if ('N' == $arPrice['PRICE']['VAT_INCLUDED'])
			{
				$currentPrice *= (1 + $arPrice['PRICE']['VAT_RATE']);
				$arPrice['PRICE']['VAT_INCLUDED'] = 'Y';
			}
		}
		else
		{
			if ('Y' == $arPrice['PRICE']['VAT_INCLUDED'])
			{
				$currentPrice /= (1 + $arPrice['PRICE']['VAT_RATE']);
				$arPrice['PRICE']['VAT_INCLUDED'] = 'N';
			}
		}

		$arDiscountList = array();
		$arCouponList = array();

		if (!empty($arPrice["DISCOUNT_LIST"]))
		{
			$dblStartPrice = $currentPrice;

			foreach ($arPrice["DISCOUNT_LIST"] as &$arOneDiscount)
			{
				switch ($arOneDiscount['VALUE_TYPE'])
				{
				case 'F':
					if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
						$currentDiscount = $arOneDiscount['VALUE'];
					else
						$currentDiscount = CCurrencyRates::ConvertCurrency($arOneDiscount["VALUE"], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);
					$currentPrice = $currentPrice - $currentDiscount;
					break;
				case 'P':
					$currentDiscount = $currentPrice*$arOneDiscount["VALUE"]/100.0;
					if (0 < $arOneDiscount['MAX_DISCOUNT'])
					{
						if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
							$dblMaxDiscount = $arOneDiscount['MAX_DISCOUNT'];
						else
							$dblMaxDiscount = CCurrencyRates::ConvertCurrency($arOneDiscount['MAX_DISCOUNT'], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);;
						if ($currentDiscount > $dblMaxDiscount)
							$currentDiscount = $dblMaxDiscount;
					}
					$currentPrice = $currentPrice - $currentDiscount;
					break;
				case 'S':
					if ($arOneDiscount['CURRENCY'] == $arPrice["PRICE"]["CURRENCY"])
						$currentPrice = $arOneDiscount['VALUE'];
					else
						$currentPrice = CCurrencyRates::ConvertCurrency($arOneDiscount['VALUE'], $arOneDiscount["CURRENCY"], $arPrice["PRICE"]["CURRENCY"]);
					break;
				}

				$arOneList = array(
					'ID' => $arOneDiscount['ID'],
					'NAME' => $arOneDiscount['NAME'],
					'COUPON' => '',
				);

				if ($arOneDiscount['COUPON'])
				{
					$arOneList['COUPON'] = $arOneDiscount['COUPON'];
					$arCouponList[] = $arOneDiscount['COUPON'];
				}

				$arDiscountList[] = $arOneList;
			}
			if (isset($arOneDiscount))
				unset($arOneDiscount);

			$currentDiscount = $dblStartPrice - $currentPrice;
		}

		if (empty($arPrice["PRICE"]["CATALOG_GROUP_NAME"]))
		{
			if (!empty($arPrice["PRICE"]["CATALOG_GROUP_ID"]))
			{
				$rsCatGroups = CCatalogGroup::GetListEx(array(),array('ID' => $arPrice["PRICE"]["CATALOG_GROUP_ID"]),false,false,array('ID','NAME','NAME_LANG'));
				if ($arCatGroup = $rsCatGroups->Fetch())
				{
					$arPrice["PRICE"]["CATALOG_GROUP_NAME"] = (!empty($arCatGroup['NAME_LANG']) ? $arCatGroup['NAME_LANG'] : $arCatGroup['NAME']);
				}
			}
		}

		if (!$boolDiscountVat)
		{
			$currentPrice *= (1 + $arPrice['PRICE']['VAT_RATE']);
			$currentDiscount *= (1 + $arPrice['PRICE']['VAT_RATE']);
			$arPrice['PRICE']['VAT_INCLUDED'] = 'Y';
		}

		$arResult = array(
			"PRODUCT_PRICE_ID" => $arPrice["PRICE"]["ID"],
			"PRICE" => $currentPrice,
			"VAT_RATE" => $arPrice['PRICE']['VAT_RATE'],
			"CURRENCY" => $arPrice["PRICE"]["CURRENCY"],
			"QUANTITY" => $quantity,
			"WEIGHT" => intval($arCatalogProduct["WEIGHT"]),
			"NAME" => $arProduct["~NAME"],
			"CAN_BUY" => "Y",
			"DETAIL_PAGE_URL" => $arProduct['~DETAIL_PAGE_URL'],
			"NOTES" => $arPrice["PRICE"]["CATALOG_GROUP_NAME"],
			"DISCOUNT_PRICE" => $currentDiscount,
		);
		if (!empty($arPrice["DISCOUNT_LIST"]))
		{
			$arResult["DISCOUNT_VALUE"] = (100*$currentDiscount/($currentDiscount+$currentPrice))."%";
			$arResult["DISCOUNT_NAME"] = "[".$arPrice["DISCOUNT"]["ID"]."] ".$arPrice["DISCOUNT"]["NAME"];
			$arResult['DISCOUNT_LIST'] = $arDiscountList;

			if (!empty($arPrice["DISCOUNT"]["COUPON"]))
			{
				$arResult["DISCOUNT_COUPON"] = $arPrice["DISCOUNT"]["COUPON"];
			}
			if (!empty($arCouponList))
			{
				$mxApply = CCatalogDiscountCoupon::CouponApply($intUserID, $arCouponList);
			}
		}

		if (0 < $intUserID)
		{
			CCatalogDiscountSave::ClearDiscountUserID();
		}
		return $arResult;
	}

	//in case product provider class is used instead of this method
	//product quantity is changed with ReserveProduct and DeductProduct methods
	public static function CancelProduct($arParams)
	{
		return true;
	}

	public static function DeliverProduct($arParams)
	{
		return CatalogPayOrderCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"],
			$arParams["PAID"],
			$arParams["ORDER_ID"]
		);
	}

	public static function ViewProduct($arParams)
	{
		if (!is_set($arParams["SITE_ID"]))
			$arParams["SITE_ID"] = SITE_ID;

		return CatalogViewedProductCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"],
			$arParams["SITE_ID"]
		);
	}

	public static function RecurringOrderProduct($arParams)
	{
		return CatalogRecurringCallback(
			$arParams["PRODUCT_ID"],
			$arParams["USER_ID"]
		);
	}

	public static function ReserveProduct($arParams)
	{
		$arRes = array();
		$arFields = array();

		if (intval($arParams["PRODUCT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("RSRV_INCORRECT_ID"), "NO_ORDER_ID");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		$disableReservation = (COption::GetOptionString("catalog", "enable_reservation", "Y") == "N"
			&& COption::GetOptionString("sale", "product_reserve_condition", "O") != "S"
			&& COption::GetOptionString('catalog','default_use_store_control','N') != "Y") ? true : false;

		if (intval($arParams["UNDO_RESERVATION"]) != "Y")
			$arParams["UNDO_RESERVATION"] = "N";

		$arParams["QUANTITY_ADD"] = doubleval($arParams["QUANTITY_ADD"]);

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arParams["PRODUCT_ID"]),
			false,
			false,
			array('ID', 'CAN_BUY_ZERO', 'NEGATIVE_AMOUNT_TRACE', 'QUANTITY_TRACE', 'QUANTITY', 'QUANTITY_RESERVED')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			if ($disableReservation)
			{
				$startReservedQuantity = 0;

				if ($arParams["UNDO_RESERVATION"] != "Y")
					$arFields = array("QUANTITY" => $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"]);
				else
					$arFields = array("QUANTITY" => $arProduct["QUANTITY"] + $arParams["QUANTITY_ADD"]);

				$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
			}
			else
			{
				if ($arProduct["QUANTITY_TRACE"] == "N" || (isset($arParams["ORDER_DEDUCTED"]) && $arParams["ORDER_DEDUCTED"] == "Y"))
				{
					$arRes["RESULT"] = true;
					$arFields["QUANTITY_RESERVED"] = 0;
					$startReservedQuantity = 0;
				}
				else
				{
					$startReservedQuantity = $arProduct["QUANTITY_RESERVED"];

					if ($arParams["UNDO_RESERVATION"] == "N")
					{
						if ($arProduct["CAN_BUY_ZERO"] == "Y")
						{
							$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY_ADD"];

							if ($arProduct["QUANTITY"] >= $arParams["QUANTITY_ADD"])
							{
								$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
							}
							elseif ($arProduct["QUANTITY"] < $arParams["QUANTITY_ADD"])
							{
								if ($arProduct["NEGATIVE_AMOUNT_TRACE"] == "Y")
								{
									//reserve value, quantity will be negative
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
								}
								else
								{
									$arFields["QUANTITY"] = 0;
								}
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
						else //CAN_BUY_ZERO = N
						{
							if ($arProduct["QUANTITY"] >= $arParams["QUANTITY_ADD"])
							{
								$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_ADD"];
								$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY_ADD"];
							}
							elseif ($arProduct["QUANTITY"] < $arParams["QUANTITY_ADD"])
							{
								//reserve only possible value, quantity = 0

								$arRes["QUANTITY_NOT_RESERVED"] = $arParams["QUANTITY_ADD"] - $arProduct["QUANTITY"];

								$arFields["QUANTITY"] = 0;
								$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"];

								$GLOBALS["APPLICATION"]->ThrowException(GetMessage("RSRV_QUANTITY_NOT_ENOUGH_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "ERROR_NOT_ENOUGH_QUANTITY");
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
					}
					else //undo reservation
					{
						$arFields["QUANTITY"] = $arProduct["QUANTITY"] + $arParams["QUANTITY_ADD"];
						$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY_ADD"];

						$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
					}
				} //quantity trace
			}
		} //product found
		else
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("RSRV_ID_NOT_FOUND", array("#PRODUCT_ID#" => $arParams["PRODUCT_ID"])), "ID_NOT_FOUND");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		if ($arRes["RESULT"])
		{
			$arRes["QUANTITY_RESERVED"] = $arFields["QUANTITY_RESERVED"] - $startReservedQuantity;
		}
		else
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("RSRV_UNKNOWN_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "UNKNOWN_RESERVATION_ERROR");
		}

		return $arRes;
	}

	public static function DeductProduct($arParams)
	{
		global $DB;
		$arRes = array();
		$arFields = array();

		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control','N');

		$disableReservation = (COption::GetOptionString("catalog", "enable_reservation", "Y") == "N"
			&& COption::GetOptionString("sale", "product_reserve_condition", "O") != "S"
			&& $strUseStoreControl != "Y") ? true : false;

		if ($disableReservation)
		{
			$arRes["RESULT"] = true;
			return $arRes;
		}

		if (intval($arParams["PRODUCT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("RSRV_INCORRECT_ID"), "NO_ORDER_ID");
			$arRes["RESULT"] = false;
			return $arRes;
		}

		$arParams["QUANTITY"] = doubleval($arParams["QUANTITY"]);

		if (intval($arParams["UNDO_DEDUCTION"]) != "Y")
			$arParams["UNDO_DEDUCTION"] = "N";

		if (intval($arParams["EMULATE"]) != "Y")
			$arParams["EMULATE"] = "N";

		if (intval($arParams["PRODUCT_RESERVED"]) != "Y")
			$arParams["PRODUCT_RESERVED"] = "N";

		if (!isset($arParams["STORE_DATA"]))
			$arParams["STORE_DATA"] = array();

		if (!is_array($arParams["STORE_DATA"]))
			$arParams["STORE_DATA"] = array($arParams["STORE_DATA"]);

		$rsProducts = CCatalogProduct::GetList(
			array(),
			array('ID' => $arParams["PRODUCT_ID"]),
			false,
			false,
			array('ID', 'QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE')
		);

		if ($arProduct = $rsProducts->Fetch())
		{
			if ($arParams["UNDO_DEDUCTION"] == "N")
			{
				if ($arParams["EMULATE"] == "Y" || $arProduct["QUANTITY_TRACE"] == "N")
				{
					$arRes["RESULT"] = true;
				}
				else
				{
					if ($strUseStoreControl == "Y")
					{
						if (!empty($arParams["STORE_DATA"]))
						{
							$totalAmount = 0;
							foreach ($arParams["STORE_DATA"] as $id => $arRecord)
							{
								if (!isset($arRecord["STORE_ID"]) || intval($arRecord["STORE_ID"]) < 0 || !isset($arRecord["QUANTITY"]) || intval($arRecord["QUANTITY"]) < 0)
								{
									$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_STORE_ERROR");
									$arRes["RESULT"] = false;
									return $arRes;
								}

								$rsProps = CCatalogStoreProduct::GetList(
									array(),
									array(
										"PRODUCT_ID" => $arParams["PRODUCT_ID"],
										"STORE_ID" => $arRecord["STORE_ID"]
									),
									false,
									false,
									array('ID', 'AMOUNT')
								);
								if ($arProp = $rsProps->Fetch())
								{
									if ($arProp["AMOUNT"] < $arRecord["QUANTITY"])
									{
										$GLOBALS["APPLICATION"]->ThrowException(
											GetMessage(
												"DDCT_DEDUCTION_QUANTITY_STORE_ERROR",
												array_merge(self::GetProductCatalogInfo($arParams["PRODUCT_ID"]), array("#STORE_ID#" => $arRecord["STORE_ID"]))
											),
											"DDCT_DEDUCTION_QUANTITY_STORE_ERROR"
										);
										$arRes["RESULT"] = false;
										return $arRes;
									}
									else
									{
										$res = CCatalogStoreProduct::Update($arProp["ID"], array("AMOUNT" => $arProp["AMOUNT"] - $arRecord["QUANTITY"]));

										if ($res)
										{
											$arRes["STORES"][$arRecord["STORE_ID"]] = $arRecord["QUANTITY"];
											$totalAmount += $arRecord["QUANTITY"];

											//deleting barcodes
											if (isset($arRecord["BARCODE"]) && is_array($arRecord["BARCODE"]) && count($arRecord["BARCODE"]) > 0)
											{
												foreach ($arRecord["BARCODE"] as $barcodeId => $barcodeValue)
												{
													$arFields = array(
														"STORE_ID" => $arRecord["STORE_ID"],
														"BARCODE" => $barcodeValue,
														"PRODUCT_ID" => $arParams["PRODUCT_ID"]
													);

													$dbres = CCatalogStoreBarcode::GetList(
														array(),
														$arFields,
														false,
														false,
														array("ID", "STORE_ID", "BARCODE", "PRODUCT_ID")
													);

													if ($arRes = $dbres->Fetch())
													{
														CCatalogStoreBarcode::Delete($arRes["ID"]);
													}
													else
													{
														$GLOBALS["APPLICATION"]->ThrowException(
															GetMessage(
																"DDCT_DEDUCTION_BARCODE_ERROR",
																array_merge(self::GetProductCatalogInfo($arParams["PRODUCT_ID"]), array("#BARCODE#" => $barcodeValue))
															),
															"DDCT_DEDUCTION_BARCODE_ERROR"
														);
														$arRes["RESULT"] = false;
														return $arRes;
													}
												}
											}
										}
										else
										{
											$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_DEDUCTION_SAVE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_SAVE_ERROR");
											$arRes["RESULT"] = false;
											return $arRes;
										}
									}
								}
							}

							//updating total sum
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								if ($totalAmount <= $arProduct["QUANTITY_RESERVED"])
								{
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $totalAmount;
								}
								else if ($totalAmount <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY_RESERVED"] = 0;
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - ($totalAmount - $arProduct["QUANTITY_RESERVED"]);
								}
								else //not enough products - don't deduct anything
								{
									$arRes["RESULT"] = false;
									return $arRes;
								}
							}
							else //product not reserved, use main quantity field to deduct from, quantity_reserved only if there is shortage in the main field
							{
								if ($totalAmount <= $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $totalAmount;
								}
								else if ($totalAmount <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = 0;
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - ($totalAmount - $arProduct["QUANTITY"]);
								}
								else //not enough products - don't deduct anything
								{
									$arRes["RESULT"] = false;
									return $arRes;
								}
							}

							CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);

							$arRes["RESULT"] = true;
						}
						else
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DEDUCTION_STORE_ERROR1");
							$arRes["RESULT"] = false;
							return $arRes;
						}
					}
					else // store control not used
					{
						if ($arParams["QUANTITY"] <= $arProduct["QUANTITY_RESERVED"] + $arProduct["QUANTITY"])
						{
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								if ($arParams["QUANTITY"] <= $arProduct["QUANTITY_RESERVED"])
								{
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY"];
								}
								else
								{
									$arFields["QUANTITY_RESERVED"] = 0;
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - ($arParams["QUANTITY"] - $arProduct["QUANTITY_RESERVED"]);
								}
							}
							else //product not reserved, use main quantity field to deduct from, quantity_reserved only if there is shortage in the main field
							{
								if ($arParams["QUANTITY"] <= $arProduct["QUANTITY"])
								{
									$arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY"];
								}
								else
								{
									$arFields["QUANTITY"] = 0;
									$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - ($arParams["QUANTITY"] - $arProduct["QUANTITY"]);
								}
							}

							$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
						}
						else //not enough products - don't deduct anything
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_DEDUCTION_QUANTITY_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_QUANTITY_ERROR");
							$arRes["RESULT"] = false;
							return $arRes;
						}

					} //store control
				} //emulate /quantity trace
			}
			else //undo deduction
			{
				if ($arParams["EMULATE"] == "Y" || $arProduct["QUANTITY_TRACE"] == "N")
				{
					$arRes["RESULT"] = true;
				}
				else
				{
					if ($strUseStoreControl == "Y")
					{
						if (!empty($arParams["STORE_DATA"]))
						{
							$totalAddedAmount = 0;
							foreach ($arParams["STORE_DATA"] as $id => $arRecord)
							{
								$rsProps = CCatalogStoreProduct::GetList(
									array(),
									array(
										"PRODUCT_ID" => $arParams["PRODUCT_ID"],
										"STORE_ID" => $arRecord["STORE_ID"]
									),
									false,
									false,
									array('ID', 'AMOUNT')
								);

								if ($arProp = $rsProps->Fetch())
								{
									$res = CCatalogStoreProduct::Update(
										$arProp["ID"],
										array("AMOUNT" => $arProp["AMOUNT"] + $arRecord["QUANTITY"])
									);

									if ($res)
									{
										$arRes["STORES"][$arRecord["STORE_ID"]] = $arRecord["QUANTITY"];
										$totalAddedAmount += $arRecord["QUANTITY"];

										//adding barcodes
										if (isset($arRecord["BARCODE"]) && strlen($arRecord["BARCODE"]) > 0)
										{
											$arFields = array(
												"STORE_ID" => $arRecord["STORE_ID"],
												"BARCODE" => $arRecord["BARCODE"],
												"PRODUCT_ID" => $arParams["PRODUCT_ID"]
											);

											CCatalogStoreBarcode::Add($arFields);
										}
									}
									else
									{
										$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_DEDUCTION_SAVE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DDCT_DEDUCTION_SAVE_ERROR");
										$arRes["RESULT"] = false;
										return $arRes;
									}
								}
							}

							// $dbAmount = $DB->Query("SELECT SUM(AMOUNT) as AMOUNT FROM b_catalog_store_product WHERE PRODUCT_ID = ".$arParams["PRODUCT_ID"]." ", true);
							// if ($totalAddedAmount = $dbAmount->Fetch())
							// {
							// }
							if ($arParams["PRODUCT_RESERVED"] == "Y")
							{
								$arUpdateFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $totalAddedAmount;
							}
							else
							{
								$arUpdateFields["QUANTITY"] = $arProduct["QUANTITY"] + $totalAddedAmount;
							}

							CCatalogProduct::Update($arParams["PRODUCT_ID"], $arUpdateFields);

							$arRes["RESULT"] = true;
						}
						else
						{
							$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_DEDUCTION_STORE_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "DEDUCTION_STORE_ERROR2");
							$arRes["RESULT"] = false;
							return $arRes;
						}
					}
					else //store control not used
					{
						if ($arParams["PRODUCT_RESERVED"] == "Y")
						{
							$arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] + $arParams["QUANTITY"];
							// $arFields["QUANTITY"] = $arProduct["QUANTITY"] - $arParams["QUANTITY_RESERVED"];
						}
						else
						{
							$arFields["QUANTITY"] = $arProduct["QUANTITY"] + $arParams["QUANTITY"];
							// $arFields["QUANTITY_RESERVED"] = $arProduct["QUANTITY_RESERVED"] - $arParams["QUANTITY_RESERVED"];
						}

						$arRes["RESULT"] = CCatalogProduct::Update($arParams["PRODUCT_ID"], $arFields);
					}
				} //emulate or quantity trace
			}
		}
		else
		{
			$arRes["RESULT"] = false;
		}

		if (!$arRes["RESULT"])
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("DDCT_UNKNOWN_ERROR", self::GetProductCatalogInfo($arParams["PRODUCT_ID"])), "UNKNOWN_DEDUCTION_ERROR");
		}

		return $arRes;
	}

	public static function GetStoresCount($arParams = array())
	{
		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control','N');

		//without store control stores are used for information purposes only
		if ($strUseStoreControl == "N")
			return -1;

		$arStoreID = array();
		$dbStoreRes = CCatalogStore::GetList(
			array(),
			array("ACTIVE" => "Y"),
			false,
			false,
			array("ID", "ACTIVE")
		);

		while ($arStoreRes = $dbStoreRes->GetNext())
			$arStoreID[] = $arStoreRes["ID"];

		return count($arStoreID);
	}

	public static function GetProductStores($arParams)
	{
		$strUseStoreControl = COption::GetOptionString('catalog','default_use_store_control','N');

		//without store control stores are used for information purposes only and manual deduction won't work
		if ($strUseStoreControl == "N")
			return false;

		$arResult = array();

		if (intval($arParams["PRODUCT_ID"] < 0))
			return false;

		$dbStoreRes = CCatalogStore::GetList(
			array(),
			array("ACTIVE" => "Y"),
			false,
			false,
			array("ID", "ACTIVE")
		);
		while ($arStoreRes = $dbStoreRes->Fetch())
			$arStoreID[] = $arStoreRes["ID"];

		$dbRes = CCatalogStoreProduct::GetList(
			array(),
			array(
				"PRODUCT_ID" => $arParams["PRODUCT_ID"],
				// ">AMOUNT" => "0"
			),
			false,
			false,
			array("STORE_NAME", "STORE_ID", "AMOUNT", "PRODUCT_ID")
		);
		while ($arRes = $dbRes->Fetch())
		{
			if (in_array($arRes["STORE_ID"], $arStoreID))
				$arResult[] = $arRes;
		}

		return $arResult;
	}

	public static function CheckProductBarcode($arParams)
	{
		$result = false;

		$arFilter = array(
			"PRODUCT_ID" => $arParams["PRODUCT_ID"],
			"BARCODE"	 => $arParams["BARCODE"]
		);

		if (isset($arParams["STORE_ID"]))
			$arFilter["STORE_ID"] = intval($arParams["STORE_ID"]);

		$dbres = CCatalogStoreBarcode::GetList(
			array(),
			$arFilter
		);
		if ($res = $dbres->GetNext())
			$result = true;

		return $result;
	}

	private static function GetProductCatalogInfo($productID)
	{
		$productID = intval($productID);
		$result = "";
		if ($productID <= 0)
			return array();

		$dbProduct = CIBlockElement::GetList(array(), array("ID" => $productID), false, false, array('ID', 'IBLOCK_ID', 'NAME'));
		if ($arProduct = $dbProduct->Fetch())
		{
			if ($arProduct["IBLOCK_ID"] > 0)
				$arProduct["EDIT_PAGE_URL"] = CIBlock::GetAdminElementEditLink($arProduct["IBLOCK_ID"], $productID);
		}

		return array(
			"#PRODUCT_ID#"   => $arProduct["ID"],
			"#PRODUCT_NAME#" => $arProduct["NAME"],
		);
	}
}
?>
