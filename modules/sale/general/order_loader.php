<?
IncludeModuleLangFile(__FILE__);
use \Bitrix\Main\Type\RandomSequence;

class CSaleOrderLoader
{
	var $strError = "";
	var $SumFormat = ".";
	var $QuantityFormat = ".";
	var $sdp = "";
	var $arParams = array();
	var $bNewVersion = false;
	var $arPersonTypesIDs = array();
	var $arExportInfo = array();
	var $arOrderProps = array();
	var $arIBInfo = array();

	public function elementHandler($path, $attr)
	{
		$val = $attr[GetMessage("SALE_EXPORT_FORM_SUMM")];
		if(strlen($val) > 0)
		{
			if(preg_match("#".GetMessage("SALE_EXPORT_FORM_CRD")."=(.);{0,1}#", $val, $match))
			{
				$this->sdp = $match[1];
			}
		}
		/*
		$val = $attr[GetMessage("SALE_EXPORT_FORM_QUANT")];
		if(strlen($val) > 0)
		{
			if(preg_match("#".GetMessage("SALE_EXPORT_FORM_CRD")."=(.);{0,1}#", $val, $match))
			{
				$this->sdq = $match[1];
			}
		}
		*/
	}

	public function nodeHandler(CDataXML $value)
	{
		$value = $value->GetArray();

		if(!empty($value[GetMessage("CC_BSC1_DOCUMENT")]))
		{
			$value = $value[GetMessage("CC_BSC1_DOCUMENT")];

			$arOrder = $this->collectOrderInfo($value);

			if(!empty($arOrder))
			{
				if(strlen($arOrder["ID"]) <= 0 && strlen($arOrder["ID_1C"]) > 0)//try to search order from 1C
				{
					$dbOrder = CSaleOrder::GetList(array("ID" => "DESC"), array("ID_1C" => $arOrder["ID_1C"]), false, false, array("ID", "ID_1C"));
					if($orderInfo = $dbOrder->Fetch())
					{
						$arOrder["ID"] = $orderInfo["ID"];
					}
				}
				if(strlen($arOrder["ID"]) > 0) // exists site order
				{
					$dbOrder = CSaleOrder::GetList(array(), array("ACCOUNT_NUMBER" => $arOrder["ID"]), false, false, array("ID", "LID", "PERSON_TYPE_ID", "PAYED", "DATE_PAYED", "CANCELED", "DATE_CANCELED", "REASON_CANCELED", "STATUS_ID", "DATE_STATUS", "PAY_VOUCHER_NUM", "PAY_VOUCHER_DATE", "PRICE_DELIVERY", "ALLOW_DELIVERY", "DATE_ALLOW_DELIVERY", "PRICE", "CURRENCY", "DISCOUNT_VALUE", "USER_ID", "PAY_SYSTEM_ID", "DELIVERY_ID", "DATE_INSERT", "DATE_INSERT_FORMAT", "DATE_UPDATE", "USER_DESCRIPTION", "ADDITIONAL_INFO", "COMMENTS", "TAX_VALUE", "DELIVERY_DOC_NUM", "DELIVERY_DOC_DATE", "STORE_ID", "ACCOUNT_NUMBER", "VERSION", "VERSION_1C", "ID_1C"));
					if($orderInfo = $dbOrder->Fetch())
					{
						if($arOrder["VERSION_1C"] != $orderInfo["VERSION_1C"] || (strlen($orderInfo["VERSION_1C"]) <= 0 || strlen($arOrder["VERSION_1C"]) <= 0)) // skip update if the same version
						{
							$arOrderFields = array();
							$orderId = $orderInfo["ID"];
							CSaleOrderChange::AddRecord($orderId, "ORDER_1C_IMPORT");
							if($arOrder["ID_1C"] != $orderInfo["ID_1C"])
								$arOrderFields["ID_1C"] = $arOrder["ID_1C"];
							$arOrderFields["VERSION_1C"] = $arOrder["VERSION_1C"];

							if($orderInfo["PAYED"] != "Y" && $orderInfo["ALLOW_DELIVERY"] != "Y" && $orderInfo["STATUS_ID"] != "F")
							{
								$dbOrderTax = CSaleOrderTax::GetList(
									array(),
									array("ORDER_ID" => $orderId),
									false,
									false,
									array("ID", "TAX_NAME", "VALUE", "VALUE_MONEY", "CODE", "IS_IN_PRICE")
								);
								$bTaxFound = false;
								if($arOrderTax = $dbOrderTax->Fetch())
								{
									$bTaxFound = true;
									if(IntVal($arOrderTax["VALUE_MONEY"]) != IntVal($arOrder["TAX"]["VALUE_MONEY"]) || IntVal($arOrderTax["VALUE"]) != IntVal($arOrder["TAX"]["VALUE"]) || ($arOrderTax["IS_IN_PRICE"] != $arOrder["TAX"]["IS_IN_PRICE"]))
									{
										if(IntVal($arOrder["TAX"]["VALUE"])>0)
										{
											$arFields = Array(
												"TAX_NAME" => $arOrder["TAX"]["NAME"],
												"ORDER_ID" => $orderId,
												"VALUE" => $arOrder["TAX"]["VALUE"],
												"IS_PERCENT" => "Y",
												"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
												"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"],
												"CODE" => "VAT1C",
												"APPLY_ORDER" => "100"
											);
											CSaleOrderTax::Update($arOrderTax["ID"], $arFields);
											$arOrderFields["TAX_VALUE"] = $arOrder["TAX"]["VALUE_MONEY"];
										}
										else
										{
											CSaleOrderTax::Delete($arOrderTax["ID"]);
											$arOrderFields["TAX_VALUE"] = 0;
										}
									}
								}

								if(!$bTaxFound)
								{
									if(IntVal($arOrder["TAX"]["VALUE"])>0)
									{
										$arFields = Array(
											"TAX_NAME" => $arOrder["TAX"]["NAME"],
											"ORDER_ID" => $orderId,
											"VALUE" => $arOrder["TAX"]["VALUE"],
											"IS_PERCENT" => "Y",
											"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
											"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"]
										);
										CSaleOrderTax::Add($arFields);
										$arOrderFields["TAX_VALUE"] = $arOrder["TAX"]["VALUE_MONEY"];
									}
								}

								$arShoppingCart = array();
								$bNeedUpdate = false;
								$dbBasket = CSaleBasket::GetList(
									array("NAME" => "ASC"),
									array("ORDER_ID" => $orderId),
									false,
									false,
									array(
										"ID",
										"QUANTITY",
										"CANCEL_CALLBACK_FUNC",
										"MODULE",
										"PRODUCT_ID",
										"PRODUCT_PROVIDER_CLASS",
										"RESERVED",
										"RESERVE_QUANTITY",
										"TYPE",
										"SET_PARENT_ID",
										"PRICE",
										"VAT_RATE",
										"DISCOUNT_PRICE",
										"PRODUCT_XML_ID",
									)
								);

								while ($arBasket = $dbBasket->Fetch())
								{
									$arFields = Array();
									if(!empty($arOrder["items"][$arBasket["PRODUCT_XML_ID"]]))
									{
										if($arBasket["QUANTITY"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["QUANTITY"])
											$arFields["QUANTITY"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["QUANTITY"];
										if($arBasket["PRICE"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["PRICE"])
											$arFields["PRICE"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["PRICE"];
										if($arBasket["VAT_RATE"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["VAT_RATE"])
											$arFields["VAT_RATE"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["VAT_RATE"];
										if($arBasket["DISCOUNT_PRICE"] != $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["DISCOUNT_PRICE"])
											$arFields["DISCOUNT_PRICE"] = $arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["DISCOUNT_PRICE"];

										if(count($arFields)>0)
										{
											$arFields["ID"] = $arBasket["ID"];
											if(DoubleVal($arFields["QUANTITY"]) <= 0)
												$arFields["QUANTITY"] = $arBasket["QUANTITY"];
											$bNeedUpdate = true;
											$arShoppingCart[] = $arFields;
										}
										else
										{
											$arShoppingCart[] = $arBasket;
										}
										//CSaleBasket::Update($arBasket["ID"], $arFields);

										$arOrder["items"][$arBasket["PRODUCT_XML_ID"]]["CHECKED"] = "Y";
									}
									else
									{
										if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] != "true" && $orderInfo["CANCELED"] == "N")
										{
											$bNeedUpdate = true;
											//CSaleBasket::Delete($arBasket["ID"]);
										}
									}
								}

								if(!empty($arOrder["items"]))
								{
									foreach ($arOrder["items"] as $itemID => $arItem)
									{
										if ($arItem["CHECKED"] != "Y")
										{
											if ($arItem["TYPE"] == GetMessage("CC_BSC1_ITEM"))
											{
												if ($arBasketFields = $this->prepareProduct4Basket($itemID, $arItem, $orderId, $orderInfo))
												{
													$arShoppingCart[] = $arBasketFields;
													$bNeedUpdate = true;
												}
											}
											elseif ($arItem["TYPE"] == GetMessage("CC_BSC1_SERVICE"))
											{
												if (IntVal($arItem["PRICE"]) != IntVal($orderInfo["PRICE_DELIVERY"]))
													$arOrderFields["PRICE_DELIVERY"] = $arItem["PRICE"];
											}
										}
									}
								}

								if($bNeedUpdate)
								{
									$arErrors = array();
									CSaleBasket::DoSaveOrderBasket($orderId, $orderInfo["LID"], $orderInfo["USER_ID"], $arShoppingCart, $arErrors);
								}

								if(DoubleVal($arOrder["AMOUNT"]) > 0 && $arOrder["AMOUNT"] != $orderInfo["PRICE"])
									$arOrderFields["PRICE"] = $arOrder["AMOUNT"];
								if(DoubleVal($orderInfo["DISCOUNT_VALUE"]) > 0)
									$arOrderFields["DISCOUNT_VALUE"] = 0;
								if(strlen($arOrder["COMMENT"]) > 0 && $arOrder["COMMENT"] != $orderInfo["COMMENTS"])
									$arOrderFields["COMMENTS"] = $arOrder["COMMENT"];
								$arOrderFields["UPDATED_1C"] = "Y";
								if(!empty($arOrderFields))
									CSaleOrder::Update($orderId, $arOrderFields);
							}
							else
							{
								$this->strError .= "\n".GetMessage("CC_BSC1_FINAL_NOT_EDIT", Array("#ID#" => $orderId));
							}
						}

						$arAditFields = Array();
						if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] == "true")
						{
							if($orderInfo["CANCELED"] == "N")
							{
								CSaleOrder::CancelOrder($orderInfo["ID"], "Y", $arOrder["COMMENT"]);
								$arAditFields["UPDATED_1C"] = "Y";
							}
						}
						else
						{
							if($arOrder["TRAITS"][GetMessage("CC_BSC1_CANCELED")] != "true")
							{
								if($orderInfo["CANCELED"] == "Y")
								{
									CSaleOrder::CancelOrder($orderInfo["ID"], "N", $arOrder["COMMENT"]);
									$arAditFields["UPDATED_1C"] = "Y";
								}
							}

							if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")])>1)
							{
								if($orderInfo["PAYED"]=="N")
									CSaleOrder::PayOrder($orderInfo["ID"], "Y");
								$arAditFields["PAY_VOUCHER_DATE"] = CDatabase::FormatDate(str_replace("T", " ", $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_DATE")]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG));
								if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")])>0)
									$arAditFields["PAY_VOUCHER_NUM"] = $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_PAYED_NUM")];
								$arAditFields["UPDATED_1C"] = "Y";
							}

							if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_DATE")])>1)
							{
								if($orderInfo["ALLOW_DELIVERY"]=="N")
									CSaleOrder::DeliverOrder($orderInfo["ID"], "Y");
								$arAditFields["DATE_ALLOW_DELIVERY"] = CDatabase::FormatDate(str_replace("T", " ", $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_DATE")]), "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG));
								$arAditFields["DELIVERY_DOC_DATE"] = $arAditFields["DATE_ALLOW_DELIVERY"];

								if(strlen($this->arParams["FINAL_STATUS_ON_DELIVERY"])>0 && $orderInfo["STATUS_ID"] != "F" && $orderInfo["STATUS_ID"] != $this->arParams["FINAL_STATUS_ON_DELIVERY"])
									CSaleOrder::StatusOrder($orderInfo["ID"], $this->arParams["FINAL_STATUS_ON_DELIVERY"]);
								if(strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")])>0)
									$arAditFields["DELIVERY_DOC_NUM"] = $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_DELIVERY_NUM")];
								$arAditFields["UPDATED_1C"] = "Y";
							}
						}

						if($this->arParams["CHANGE_STATUS_FROM_1C"] && strlen($arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")])>0)
						{
							if($orderInfo["STATUS_ID"] != $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")])
							{
								CSaleOrder::StatusOrder($orderInfo["ID"], $arOrder["TRAITS"][GetMessage("CC_BSC1_1C_STATUS_ID")]);
								$arAditFields["UPDATED_1C"] = "Y";
							}
						}

						if(count($arAditFields)>0)
							CSaleOrder::Update($orderInfo["ID"], $arAditFields);
					}
					else
						$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_NOT_FOUND", Array("#ID#" => $arOrder["ID"]));
				}
				elseif($this->arParams["IMPORT_NEW_ORDERS"] == "Y") // create new order (ofline 1C)
				{
					if(!empty($arOrder["AGENT"]) && strlen($arOrder["AGENT"]["ID"]) > 0)
					{
						$arOrder["PERSON_TYPE_ID"] = 0;
						$arOrder["USER_ID"] = 0;
						$arErrors = array();
						$dbUProp = CSaleOrderUserProps::GetList(array(), array("XML_ID" => $arOrder["AGENT"]["ID"]), false, false, array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "XML_ID", "VERSION_1C"));
						if($arUProp = $dbUProp->Fetch())
						{
							$arOrder["USER_ID"] = $arUProp["USER_ID"];
							$arOrder["PERSON_TYPE_ID"] = $arUProp["PERSON_TYPE_ID"];
							$arOrder["USER_PROFILE_ID"] = $arUProp["ID"];
							$arOrder["USER_PROFILE_VERSION"] = $arUProp["VERSION_1C"];

							$dbUPropValue = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" => $arUProp["ID"]));
							while($arUPropValue = $dbUPropValue->Fetch())
							{
								$arOrder["USER_PROPS"][$arUPropValue["ORDER_PROPS_ID"]] = $arUPropValue["VALUE"];
							}
						}
						else
						{
							if(strlen($arOrder["AGENT"]["ID"]) > 0)
							{
								$arAI = explode("#", $arOrder["AGENT"]["ID"]);
								if(IntVal($arAI[0]) > 0)
								{
									$dbUser = CUser::GetByID($arAI[0]);
									if($arU = $dbUser->Fetch())
									{
										if(htmlspecialcharsback(substr(htmlspecialcharsbx($arU["ID"]."#".$arU["LOGIN"]."#".$arU["LAST_NAME"]." ".$arU["NAME"]." ".$arU["SECOND_NAME"]), 0, 80)) == $arOrder["AGENT"]["ID"])
										{
											$arOrder["USER_ID"] = $arU["ID"];
										}
									}
								}
							}

							if(IntVal($arOrder["USER_ID"]) <= 0)
							{
								//create new user
								$arUser = array(
									"NAME"  => $arOrder["AGENT"]["ITEM_NAME"],
									"EMAIL" => $arOrder["AGENT"]["CONTACT"]["MAIL_NEW"],
								);

								if (strlen($arUser["NAME"]) <= 0)
									$arUser["NAME"] = $arOrder["AGENT"]["CONTACT"]["CONTACT_PERSON"];
								if (strlen($arUser["EMAIL"]) <= 0)
									$arUser["EMAIL"] = "buyer".time().GetRandomCode(2)."@".$_SERVER["SERVER_NAME"];

								$arOrder["USER_ID"] = CSaleUser::DoAutoRegisterUser($arUser["EMAIL"], $arUser["NAME"], $this->arParams["SITE_NEW_ORDERS"], $arErrors);
							}
						}

						if(empty($this->arPersonTypesIDs))
						{
							$dbPT = CSalePersonType::GetList(array(), array("ACTIVE" => "Y", "LIDS" => $this->arParams["SITE_NEW_ORDERS"]));
							while($arPT = $dbPT->Fetch())
							{
								$this->arPersonTypesIDs[] = $arPT["ID"];
							}
						}

						if(empty($this->arExportInfo))
						{
							$dbExport = CSaleExport::GetList(array(), array("PERSON_TYPE_ID" => $this->arPersonTypesIDs));
							while($arExport = $dbExport->Fetch())
							{
								$this->arExportInfo[$arExport["PERSON_TYPE_ID"]] = unserialize($arExport["VARS"]);
							}
						}

						if(IntVal($arOrder["PERSON_TYPE_ID"]) <= 0)
						{
							foreach($this->arExportInfo as $pt => $value)
							{
								if(
									(($value["IS_FIZ"] == "Y" && $arOrder["AGENT"]["TYPE"] == "FIZ")
									|| ($value["IS_FIZ"] == "N" && $arOrder["AGENT"]["TYPE"] != "FIZ"))
									)
									$arOrder["PERSON_TYPE_ID"] = $pt;
							}
						}

						if(IntVal($arOrder["PERSON_TYPE_ID"]) > 0)
						{
							$arAgent = $this->arExportInfo[$arOrder["PERSON_TYPE_ID"]];
							foreach($arAgent as $k => $v)
							{
								if(empty($v) ||
									(
										(empty($v["VALUE"]) || $v["TYPE"] != "PROPERTY") &&
										(empty($arOrder["USER_PROPS"])
											|| (is_array($v) && is_string($v["VALUE"]) && empty($arOrder["USER_PROPS"][$v["VALUE"]]))
										)
									)
								)
									unset($arAgent[$k]);
							}

							if(IntVal($arOrder["USER_ID"]) > 0)
							{
								$orderFields = array(
									"SITE_ID" => $this->arParams["SITE_NEW_ORDERS"],
									"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
									"PAYED" => "N",
									"CANCELED" => "N",
									"STATUS_ID" => "N",
									"PRICE" => $arOrder["AMOUNT"],
									"CURRENCY" => CSaleLang::GetLangCurrency($this->arParams["SITE_NEW_ORDERS"]),
									"USER_ID" => $arOrder["USER_ID"],
									"TAX_VALUE" => doubleval($arOrder["TAX"]["VALUE_MONEY"]),
									"COMMENTS" => $arOrder["COMMENT"],
									"BASKET_ITEMS" => array(),
									"TAX_LIST" => array(),
									"ORDER_PROP" => array(),
								);
								$arAditFields = array(
									"EXTERNAL_ORDER" => "Y",
									"ID_1C" => $arOrder["ID_1C"],
									"VERSION_1C" => $arOrder["VERSION_1C"],
									"UPDATED_1C" => "Y",
									"DATE_INSERT" => CDatabase::FormatDate($arOrder["DATE"]." ".$arOrder["TIME"], "YYYY-MM-DD HH:MI:SS", CLang::GetDateFormat("FULL", LANG)),
								);

								foreach($arOrder["items"] as $productID => $val)
								{
									$orderFields["BASKET_ITEMS"][] = $this->prepareProduct4Basket($productID, $val, false, $orderFields);
								}

								if(!empty($arOrder["TAX"]))
								{
									$orderFields["TAX_LIST"][] = array(
										"NAME" => $arOrder["TAX"]["NAME"],
										"IS_PERCENT" => "Y",
										"VALUE" => $arOrder["TAX"]["VALUE"],
										"VALUE_MONEY" => $arOrder["TAX"]["VALUE_MONEY"],
										"IS_IN_PRICE" => $arOrder["TAX"]["IS_IN_PRICE"],
									);
								}

								foreach($arAgent as $k => $v)
								{
									if(!empty($arOrder["ORDER_PROPS"][$k]))
									{
										$orderFields["ORDER_PROP"][$v["VALUE"]] = $arOrder["ORDER_PROPS"][$k];
									}
									if(empty($orderFields["ORDER_PROP"][$v["VALUE"]]) && !empty($arOrder["USER_PROPS"][$v["VALUE"]]))
									{
										$orderFields["ORDER_PROP"][$v["VALUE"]] = $arOrder["USER_PROPS"][$v["VALUE"]];
									}
								}

								if($arOrder["ID"] = CSaleOrder::DoSaveOrder($orderFields, $arAditFields, 0, $arErrors))
								{
									$arAditFields = array("UPDATED_1C" => "Y");
									CSaleOrder::Update($arOrder["ID"], $arAditFields);

									//add/update user profile
									if(IntVal($arOrder["USER_PROFILE_ID"]) > 0)
									{
										if($arOrder["USER_PROFILE_VERSION"] != $arOrder["AGENT"]["VERSION"])
											CSaleOrderUserProps::Update($arOrder["USER_PROFILE_ID"], array("VERSION_1C" => $arOrder["AGENT"]["VERSION"], "NAME" => $arOrder["AGENT"]["AGENT_NAME"]));
										$dbUPV = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" =>$arOrder["USER_PROFILE_ID"]));
										while($arUPV = $dbUPV->Fetch())
										{
											$arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arUPV["ORDER_PROPS_ID"]] = array("ID" => $arUPV["ID"], "VALUE" => $arUPV["VALUE"]);
										}
									}

									if(IntVal($arOrder["USER_PROFILE_ID"]) <= 0 || (IntVal($arOrder["USER_PROFILE_ID"]) > 0 && $arOrder["USER_PROFILE_VERSION"] != $arOrder["AGENT"]["VERSION"]))
									{
										$dbOrderProperties = CSaleOrderProps::GetList(
											array("SORT" => "ASC"),
											array(
												"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
												"ACTIVE" => "Y",
												"UTIL" => "N",
												"USER_PROPS" => "Y",
											),
											false,
											false,
											array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT", "MULTIPLE")
										);
										while ($arOrderProperties = $dbOrderProperties->Fetch())
										{
											$curVal = $orderFields["ORDER_PROP"][$arOrderProperties["ID"]];

											if (strlen($curVal) > 0)
											{
												if (IntVal($arOrder["USER_PROFILE_ID"]) <= 0)
												{
													$arFields = array(
														"NAME" => $arOrder["AGENT"]["AGENT_NAME"],
														"USER_ID" => $arOrder["USER_ID"],
														"PERSON_TYPE_ID" => $arOrder["PERSON_TYPE_ID"],
														"XML_ID" => $arOrder["AGENT"]["ID"],
														"VERSION_1C" => $arOrder["AGENT"]["VERSION"],
													);
													$arOrder["USER_PROFILE_ID"] = CSaleOrderUserProps::Add($arFields);
												}
												if(IntVal($arOrder["USER_PROFILE_ID"]) > 0)
												{
													$arFields = array(
														"USER_PROPS_ID" => $arOrder["USER_PROFILE_ID"],
														"ORDER_PROPS_ID" => $arOrderProperties["ID"],
														"NAME" => $arOrderProperties["NAME"],
														"VALUE" => $curVal
													);
													if(empty($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]))
													{
														CSaleOrderUserPropsValue::Add($arFields);
													}
													elseif($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["VALUE"] != $curVal)
													{
														CSaleOrderUserPropsValue::Update($arOrder["AGENT"]["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["ID"], $arFields);
													}
												}
											}
										}
									}
								}
								else
								{
									$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_ADD_PROBLEM", Array("#ID#" => $arOrder["ID_1C"]));
								}
							}
							else
							{
								$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_USER_PROBLEM", Array("#ID#" => $arOrder["ID_1C"]));
								if(!empty($arErrors))
								{
									foreach($arErrors as $v)
									{
										$this->strError .= "\n".$v["TEXT"];
									}
								}
							}
						}
						else
						{
							$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_PERSON_TYPE_PROBLEM", Array("#ID#" => $arOrder["ID_1C"]));
						}
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_ORDER_NO_AGENT_ID", Array("#ID#" => $arOrder["ID_1C"]));
					}
				}
			}
		}
		elseif($this->arParams["IMPORT_NEW_ORDERS"] == "Y")
		{

			$value = $value[GetMessage("CC_BSC1_AGENT")]["#"];
			$arAgentInfo = $this->collectAgentInfo($value);

			if(!empty($arAgentInfo["AGENT"]))
			{
				$mode = false;
				$arErrors = array();
				$dbUProp = CSaleOrderUserProps::GetList(array(), array("XML_ID" => $arAgentInfo["AGENT"]["ID"]), false, false, array("ID", "NAME", "USER_ID", "PERSON_TYPE_ID", "XML_ID", "VERSION_1C"));
				if($arUProp = $dbUProp->Fetch())
				{
					if($arUProp["VERSION_1C"] != $arAgentInfo["AGENT"]["VERSION"])
					{
						$mode = "update";
						$arAgentInfo["PROFILE_ID"] = $arUProp["ID"];
						$arAgentInfo["PERSON_TYPE_ID"] = $arUProp["PERSON_TYPE_ID"];
					}
				}
				else
				{
					$arUser = array(
						"NAME" => $arAgentInfo["AGENT"]["ITEM_NAME"],
						"EMAIL" => $arAgentInfo["AGENT"]["CONTACT"]["MAIL_NEW"],
					);

					if(strlen($arUser["NAME"]) <= 0)
						$arUser["NAME"] = $arAgentInfo["AGENT"]["CONTACT"]["CONTACT_PERSON"];

					$emServer = $_SERVER["SERVER_NAME"];
					if(strpos($_SERVER["SERVER_NAME"], ".") === false)
						$emServer .= ".bx";
					if(strlen($arUser["EMAIL"]) <= 0)
						$arUser["EMAIL"] = "buyer".time().GetRandomCode(2)."@".$emServer;
					$arAgentInfo["USER_ID"] = CSaleUser::DoAutoRegisterUser($arUser["EMAIL"], $arUser["NAME"], $this->arParams["SITE_NEW_ORDERS"], $arErrors);

					if(IntVal($arAgentInfo["USER_ID"]) > 0)
					{
						$mode = "add";
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_AGENT_USER_PROBLEM", Array("#ID#" => $arAgentInfo["AGENT"]["ID"]));
						if(!empty($arErrors))
						{
							foreach($arErrors as $v)
							{
								$this->strError .= "\n".$v["TEXT"];
							}
						}
					}
				}

				if($mode)
				{
					if(empty($this->arPersonTypesIDs))
					{
						$dbPT = CSalePersonType::GetList(array(), array("ACTIVE" => "Y", "LIDS" => $this->arParams["SITE_NEW_ORDERS"]));
						while($arPT = $dbPT->Fetch())
						{
							$this->arPersonTypesIDs[] = $arPT["ID"];
						}
					}

					if(empty($this->arExportInfo))
					{
						$dbExport = CSaleExport::GetList(array(), array("PERSON_TYPE_ID" => $this->arPersonTypesIDs));
						while($arExport = $dbExport->Fetch())
						{
							$this->arExportInfo[$arExport["PERSON_TYPE_ID"]] = unserialize($arExport["VARS"]);
						}
					}

					if(IntVal($arAgentInfo["PERSON_TYPE_ID"]) <= 0)
					{
						foreach($this->arExportInfo as $pt => $value)
						{
							if(($value["IS_FIZ"] == "Y" && $arAgentInfo["AGENT"]["TYPE"] == "FIZ")
								|| ($value["IS_FIZ"] == "N" && $arAgentInfo["AGENT"]["TYPE"] != "FIZ")
							)
								$arAgentInfo["PERSON_TYPE_ID"] = $pt;
						}
					}

					if(IntVal($arAgentInfo["PERSON_TYPE_ID"]) > 0)
					{
						$arAgentInfo["ORDER_PROPS_VALUE"] = array();
						$arAgentInfo["PROFILE_PROPS_VALUE"] = array();

						$arAgent = $this->arExportInfo[$arAgentInfo["PERSON_TYPE_ID"]];

						foreach($arAgent as $k => $v)
						{
							if(strlen($v["VALUE"]) <= 0 || $v["TYPE"] != "PROPERTY")
								unset($arAgent[$k]);
						}

						foreach($arAgent as $k => $v)
						{
							if(!empty($arAgentInfo["ORDER_PROPS"][$k]))
								$arAgentInfo["ORDER_PROPS_VALUE"][$v["VALUE"]] = $arAgentInfo["ORDER_PROPS"][$k];
						}

						if (IntVal($arAgentInfo["PROFILE_ID"]) > 0)
						{
							CSaleOrderUserProps::Update($arUProp["ID"], array("VERSION_1C" => $arAgentInfo["AGENT"]["VERSION"], "NAME" => $arAgentInfo["AGENT"]["AGENT_NAME"]));
							$dbUPV = CSaleOrderUserPropsValue::GetList(array(), array("USER_PROPS_ID" => $arAgentInfo["PROFILE_ID"]));
							while($arUPV = $dbUPV->Fetch())
							{
								$arAgentInfo["PROFILE_PROPS_VALUE"][$arUPV["ORDER_PROPS_ID"]] = array("ID" => $arUPV["ID"], "VALUE" => $arUPV["VALUE"]);
							}
						}

						if(empty($this->arOrderProps[$arAgentInfo["PERSON_TYPE_ID"]]))
						{
							$dbOrderProperties = CSaleOrderProps::GetList(
								array("SORT" => "ASC"),
								array(
									"PERSON_TYPE_ID" => $arAgentInfo["PERSON_TYPE_ID"],
									"ACTIVE" => "Y",
									"UTIL" => "N",
									"USER_PROPS" => "Y",
								),
								false,
								false,
								array("ID", "TYPE", "NAME", "CODE", "USER_PROPS", "SORT", "MULTIPLE")
							);
							while ($arOrderProperties = $dbOrderProperties->Fetch())
							{
								$this->arOrderProps[$arAgentInfo["PERSON_TYPE_ID"]][] = $arOrderProperties;
							}
						}

						foreach($this->arOrderProps[$arAgentInfo["PERSON_TYPE_ID"]] as $arOrderProperties)
						{
							$curVal = $arAgentInfo["ORDER_PROPS_VALUE"][$arOrderProperties["ID"]];

							if (strlen($curVal) > 0)
							{
								if (IntVal($arAgentInfo["PROFILE_ID"]) <= 0)
								{
									$arFields = array(
										"NAME" => $arAgentInfo["AGENT"]["AGENT_NAME"],
										"USER_ID" => $arAgentInfo["USER_ID"],
										"PERSON_TYPE_ID" => $arAgentInfo["PERSON_TYPE_ID"],
										"XML_ID" => $arAgentInfo["AGENT"]["ID"],
										"VERSION_1C" => $arAgentInfo["AGENT"]["VERSION"],
									);
									$arAgentInfo["PROFILE_ID"] = CSaleOrderUserProps::Add($arFields);
								}
								if(IntVal($arAgentInfo["PROFILE_ID"]) > 0)
								{
									$arFields = array(
										"USER_PROPS_ID" => $arAgentInfo["PROFILE_ID"],
										"ORDER_PROPS_ID" => $arOrderProperties["ID"],
										"NAME" => $arOrderProperties["NAME"],
										"VALUE" => $curVal
									);
									if(empty($arAgentInfo["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]))
									{
										CSaleOrderUserPropsValue::Add($arFields);
									}
									elseif($arAgentInfo["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["VALUE"] != $curVal)
									{
										CSaleOrderUserPropsValue::Update($arAgentInfo["PROFILE_PROPS_VALUE"][$arOrderProperties["ID"]]["ID"], $arFields);
									}
								}
							}
						}
					}
					else
					{
						$this->strError .= "\n".GetMessage("CC_BSC1_AGENT_PERSON_TYPE_PROBLEM", Array("#ID#" => $arAgentInfo["AGENT"]["ID"]));
					}
				}
			}
			else
			{
				$this->strError .= "\n".GetMessage("CC_BSC1_AGENT_NO_AGENT_ID");
			}
		}
	}

	function ToFloat($str)
	{
		static $search = false;
		static $replace = false;
		if(!$search)
		{
			if(strlen($this->sdp))
			{
				$search = array("\xc2\xa0", "\xa0", " ", $this->sdp, ",");
				$replace = array("", "", "", ".", ".");
			}
			else
			{
				$search = array("\xc2\xa0", "\xa0", " ", ",");
				$replace = array("", "", "", ".");
			}
		}

		$res1 = str_replace($search, $replace, $str);
		$res2 = doubleval($res1);

		return $res2;
	}

	function ToInt($str)
	{
		static $search = false;
		static $replace = false;
		if(!$search)
		{
			if(strlen($this->sdp))
			{
				$search = array("\xa0", " ", $this->sdp, ",");
				$replace = array("", "", ".", ".");
			}
			else
			{
				$search = array("\xa0", " ", ",");
				$replace = array("", "", ".");
			}
		}

		$res1 = str_replace($search, $replace, $str);
		$res2 = intval($res1);

		return $res2;
	}

	public function collectOrderInfo($value)
	{
		$bNeedFull = false;
		$arOrder = array();

		if($value["#"][GetMessage("CC_BSC1_OPERATION")][0]["#"] == GetMessage("CC_BSC1_ORDER"))
		{
			$accountNumberPrefix = COption::GetOptionString("sale", "1C_SALE_ACCOUNT_NUMBER_SHOP_PREFIX", "");
			$arOrder["ID"] = $value["#"][GetMessage("CC_BSC1_NUMBER")][0]["#"];

			if($accountNumberPrefix == "" || (strlen($arOrder["ID"]) > 0 && strpos($arOrder["ID"], $accountNumberPrefix) === 0) || strlen($arOrder["ID"]) <= 0)
			{
				if($accountNumberPrefix != "")
					$arOrder["ID"] = substr($arOrder["ID"], strlen($accountNumberPrefix));

				$arOrder["AMOUNT"] = $value["#"][GetMessage("CC_BSC1_SUMM")][0]["#"];
				$arOrder["AMOUNT"] = $this->ToFloat($arOrder["AMOUNT"]);

				$arOrder["COMMENT"] = $value["#"][GetMessage("CC_BSC1_COMMENT")][0]["#"];
				$arOrder["VERSION_1C"] = $value["#"][GetMessage("CC_BSC1_VERSION_1C")][0]["#"];
				$arOrder["ID_1C"] = $value["#"][GetMessage("CC_BSC1_ID_1C")][0]["#"];
				$arOrder["DATE"] = $value["#"][GetMessage("CC_BSC1_1C_DATE")][0]["#"];
				$arOrder["TRAITS"] = array();

				if(strlen($arOrder["ID"]) <= 0 && strlen($arOrder["ID_1C"]) > 0)
					$bNeedFull = true;

				if(is_array($value["#"][GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")]) && !empty($value["#"][GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")]))
				{
					foreach($value["#"][GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")] as $val)
					{
						$arOrder["TRAITS"][$val["#"][GetMessage("CC_BSC1_NAME")][0]["#"]]=$val["#"][GetMessage("CC_BSC1_VALUE")][0]["#"];
					}
				}

				$taxValue = 0;
				$taxName = "";
				$arOrder["items"] = array();
				if(is_array($value["#"][GetMessage("CC_BSC1_ITEMS")][0]["#"][GetMessage("CC_BSC1_ITEM")]))
				{
					foreach($value["#"][GetMessage("CC_BSC1_ITEMS")][0]["#"][GetMessage("CC_BSC1_ITEM")] as $val)
					{
						$val = $val["#"];
						$productID = $val[GetMessage("CC_BSC1_ID")][0]["#"];

						$discountPrice = "";
						$priceAll = $this->ToFloat($val[GetMessage("CC_BSC1_SUMM")][0]["#"]);
						$priceone = $this->ToFloat($val[GetMessage("CC_BSC1_PRICE_PER_UNIT")][0]["#"]);
						if(DoubleVal($priceone) <= 0)
							$priceone = $this->ToFloat($val[GetMessage("CC_BSC1_PRICE_ONE")][0]["#"]);

						$quantity = $this->ToFloat($val[GetMessage("CC_BSC1_QUANTITY")][0]["#"]);
						if(doubleval($quantity) > 0)
						{
							$price = roundEx($priceAll / $quantity, 4);
							$priceone = roundEx($priceone, 4);

							if($priceone != $price)
								$discountPrice = DoubleVal($priceone - $price);

							//DISCOUNTS!
							$arOrder["items"][$productID] = Array(
								"NAME" => $val[GetMessage("CC_BSC1_NAME")][0]["#"],
								"PRICE" => $price,
								"QUANTITY" => $quantity,
								"DISCOUNT_PRICE" => $discountPrice,
							);

							if(is_array($val[GetMessage("CC_BSC1_ITEM_UNIT")]) && is_array($val[GetMessage("CC_BSC1_ITEM_UNIT")][0]["#"]))
							{
								$arOrder["items"][$productID]["MEASURE_CODE"] = $val[GetMessage("CC_BSC1_ITEM_UNIT")][0]["#"][GetMessage("CC_BSC1_ITEM_UNIT_CODE")][0]["#"];
								$arOrder["items"][$productID]["MEASURE_NAME"] = $val[GetMessage("CC_BSC1_ITEM_UNIT")][0]["#"][GetMessage("CC_BSC1_ITEM_UNIT_NAME")][0]["#"];
							}

							if(is_array($val[GetMessage("CC_BSC1_PROPS_ITEMS")][0]["#"][GetMessage("CC_BSC1_PROP_ITEM")]))
							{
								foreach($val[GetMessage("CC_BSC1_PROPS_ITEMS")][0]["#"][GetMessage("CC_BSC1_PROP_ITEM")] as $val1)
									$arOrder["items"][$productID]["ATTRIBUTES"][$val1["#"][GetMessage("CC_BSC1_NAME")][0]["#"]] = $val1["#"][GetMessage("CC_BSC1_VALUE")][0]["#"];
							}

							if(is_array($val[GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")]))
							{
								foreach($val[GetMessage("CC_BSC1_REK_VALUES")][0]["#"][GetMessage("CC_BSC1_REK_VALUE")] as $val1)
								{
									if($val1["#"][GetMessage("CC_BSC1_NAME")][0]["#"] == GetMessage("CC_BSC1_ITEM_TYPE"))
										$arOrder["items"][$productID]["TYPE"] = $val1["#"][GetMessage("CC_BSC1_VALUE")][0]["#"];
								}
							}

							if(strlen($value["#"][GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_NAME")][0]["#"])>0)
							{
								$taxValueTmp = $val[GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_TAX_VALUE")][0]["#"];
								$arOrder["items"][$productID]["VAT_RATE"] = $taxValueTmp/100;

								if(IntVal($taxValueTmp) > IntVal($taxValue))
								{
									$taxName = $val[GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_NAME")][0]["#"];
									$taxValue = $taxValueTmp;
								}
							}
						}
					}
				}
				if(IntVal($taxValue)>0)
				{
					$price = $this->ToFloat($value["#"][GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_SUMM")][0]["#"]);
					$arOrder["TAX"] = Array(
						"NAME" => $taxName,
						"VALUE" =>$taxValue,
						"IS_IN_PRICE" => ($value["#"][GetMessage("CC_BSC1_TAXES")][0]["#"][GetMessage("CC_BSC1_TAX")][0]["#"][GetMessage("CC_BSC1_IN_PRICE")][0]["#"]=="true"?"Y":"N"),
						"VALUE_MONEY" => $price,
					);
				}

				if($bNeedFull)
				{
					IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/sale/general/export.php");
					$arOrder["DATE"] = $value["#"][GetMessage("CC_BSC1_1C_DATE")][0]["#"];
					$arOrder["TIME"] = $value["#"][GetMessage("CC_BSC1_1C_TIME")][0]["#"];

					if(!empty($value["#"][GetMessage("SALE_EXPORT_CONTRAGENTS")][0]["#"]))
					{
						$arAgentInfo = $this->collectAgentInfo($value["#"][GetMessage("SALE_EXPORT_CONTRAGENTS")][0]["#"][GetMessage("SALE_EXPORT_CONTRAGENT")][0]["#"]);
						$arOrder["AGENT"] = $arAgentInfo["AGENT"];
						$arOrder["ORDER_PROPS"] = $arAgentInfo["ORDER_PROPS"];

						if(strlen($arOrder["TRAITS"][GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")]) > 0)
						{
							if(!empty($arOrder["AGENT"]["REGISTRATION_ADDRESS"]))
								$arOrder["AGENT"]["REGISTRATION_ADDRESS"]["PRESENTATION"] = $arOrder["TRAITS"][GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")];
							if(!empty($arOrder["AGENT"]["ADDRESS"]))
								$arOrder["AGENT"]["ADDRESS"]["PRESENTATION"] = $arOrder["TRAITS"][GetMessage("SALE_EXPORT_DELIVERY_ADDRESS")];
						}
					}
				}
			}
		}
		return $arOrder;
	}

	public static function collectAgentInfo($data = array())
	{
		if(empty($data))
			return false;
		IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/sale/general/export.php");

		$result = array();
		$schema = array("ID", "VERSION", "ITEM_NAME", "OFICIAL_NAME", "FULL_NAME", "INN", "KPP", "OKPO_CODE", "EGRPO", "OKVED", "OKDP", "OKOPF", "OKFC", "OKPO",
			"REGISTRATION_ADDRESS" => array("PRESENTATION", "POST_CODE", "COUNTRY", "REGION", "STATE", "SMALL_CITY", "CITY", "STREET", "HOUSE", "BUILDING", "FLAT"),
			"UR_ADDRESS" => array("PRESENTATION", "POST_CODE", "COUNTRY", "REGION", "STATE", "SMALL_CITY", "CITY", "STREET", "HOUSE", "BUILDING", "FLAT"),
			"ADDRESS" => array("PRESENTATION", "POST_CODE", "COUNTRY", "REGION", "STATE", "SMALL_CITY", "CITY", "STREET", "HOUSE", "BUILDING", "FLAT"),
			"CONTACTS" => array("CONTACT" => array("WORK_PHONE_NEW", "MAIL_NEW")),
			"REPRESENTATIVES" => array("REPRESENTATIVE" => array("CONTACT_PERSON")),

		);

		foreach($schema as $k => $v)
		{
			if(is_array($v))
			{
				if(isset($data[GetMessage("SALE_EXPORT_".$k)]) && !empty($data[GetMessage("SALE_EXPORT_".$k)][0]["#"]))
				{
					$adr = $data[GetMessage("SALE_EXPORT_".$k)][0]["#"];
					foreach($v as $kk => $vv)
					{
						if(is_array($vv))
						{
							if(isset($adr[GetMessage("SALE_EXPORT_".$kk)]) && !empty($adr[GetMessage("SALE_EXPORT_".$kk)][0]["#"]) > 0)
							{
								foreach($vv as $vvv)
								{
									foreach($adr[GetMessage("SALE_EXPORT_".$kk)] as $val)
									{
										if($val["#"][GetMessage("SALE_EXPORT_TYPE")][0]["#"] == GetMessage("SALE_EXPORT_".$vvv)
											&& strlen($val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"]) > 0
										)
											$result["AGENT"][$kk][$vvv] = $val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"];
										elseif(empty($val["#"][GetMessage("SALE_EXPORT_TYPE")][0]["#"]) && $val["#"][GetMessage("SALE_EXPORT_RELATION")][0]["#"] == GetMessage("SALE_EXPORT_CONTACT_PERSON"))
											$result["AGENT"]["CONTACT"][$vvv] = $val["#"][GetMessage("SALE_EXPORT_ITEM_NAME")][0]["#"];

									}
								}
							}
						}
						else
						{
							if(isset($adr[GetMessage("SALE_EXPORT_".$vv)]) && strlen($adr[GetMessage("SALE_EXPORT_".$vv)][0]["#"]) > 0)
							{
								$result["AGENT"][$k][$vv] = $adr[GetMessage("SALE_EXPORT_".$vv)][0]["#"];
							}
							else
							{
								if(!empty($adr[GetMessage("SALE_EXPORT_ADDRESS_FIELD")]))
								{
									foreach($adr[GetMessage("SALE_EXPORT_ADDRESS_FIELD")] as $val)
									{
										if($val["#"][GetMessage("SALE_EXPORT_TYPE")][0]["#"] == GetMessage("SALE_EXPORT_".$vv)
											&& strlen($val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"]) > 0
										)
											$result["AGENT"][$k][$vv] = $val["#"][GetMessage("SALE_EXPORT_VALUE")][0]["#"];
									}
								}
							}
						}
					}
				}
			}
			else
			{
				if(isset($data[GetMessage("SALE_EXPORT_".$v)]) && strlen($data[GetMessage("SALE_EXPORT_".$v)][0]["#"]) > 0)
					$result["AGENT"][$v] = $data[GetMessage("SALE_EXPORT_".$v)][0]["#"];
			}
		}

		$result["AGENT"]["AGENT_NAME"] = $result["AGENT"]["ITEM_NAME"];
		$result["AGENT"]["CONTACT"]["EMAIL"] = $result["AGENT"]["CONTACT"]["MAIL_NEW"];
		$result["AGENT"]["CONTACT"]["PHONE"] = $result["AGENT"]["CONTACT"]["WORK_PHONE_NEW"];
		$result["AGENT"]["OKPO"] = $result["AGENT"]["OKPO_CODE"];


		$result["ORDER_PROPS"] = array();
		foreach($result["AGENT"] as $k => $v)
		{
			if(!is_array($v) && !empty($v))
				$result["ORDER_PROPS"][$k] = $v;
			else
			{
				if($k == "CONTACT")
				{
					$result["ORDER_PROPS"]["EMAIL"] = $v["MAIL_NEW"];
					$result["ORDER_PROPS"]["PHONE"] = $v["WORK_PHONE_NEW"];
				}
				elseif($k == "REPRESENTATIVE")
				{
					$result["ORDER_PROPS"]["CONTACT_PERSON"] = $v["CONTACT_PERSON"];
				}
				elseif($k == "REGISTRATION_ADDRESS" || $k == "UR_ADDRESS")
				{
					$result["ORDER_PROPS"]["ADDRESS_FULL"] = $v["PRESENTATION"];
					$result["ORDER_PROPS"]["INDEX"] = $v["POST_CODE"];
					foreach($v as $k1 => $v1)
					{
						if(strlen($v1) > 0 && empty($result["ORDER_PROPS"][$k1]))
							$result["ORDER_PROPS"][$k1] = $v1;
					}
				}
				elseif($k == "ADDRESS")
				{
					$result["ORDER_PROPS"]["F_ADDRESS_FULL"] = $v["PRESENTATION"];
					$result["ORDER_PROPS"]["F_INDEX"] = $v["POST_CODE"];
					foreach($v as $k1 => $v1)
					{
						if(strlen($v1) > 0 && empty($result["ORDER_PROPS"]["F_".$k1]))
							$result["ORDER_PROPS"]["F_".$k1] = $v1;
					}
				}
			}
		}

		if(strlen($result["AGENT"]["OFICIAL_NAME"]) > 0 && strlen($result["AGENT"]["INN"]) > 0)
			$result["AGENT"]["TYPE"] = "UR";
		elseif(strlen($result["AGENT"]["INN"]) > 0)
			$result["AGENT"]["TYPE"] = "IP";
		else
			$result["AGENT"]["TYPE"] = "FIZ";

		return $result;
	}

	public function prepareProduct4Basket($itemID, $arItem, $orderId, $orderInfo)
	{
		$arFields = array();
		if(CModule::IncludeModule("iblock"))
		{
			$dbIBlockElement = CIBlockElement::GetList(array(), array("XML_ID" => $itemID, "ACTIVE" => "Y", "CHECK_PERMISSIONS" => "Y"), false, false, array("ID", "IBLOCK_ID", "XML_ID", "NAME", "DETAIL_PAGE_URL"));
			if($arIBlockElement = $dbIBlockElement->Fetch())
			{
				if(empty($this->arIBInfo[$arIBlockElement["IBLOCK_ID"]]))
				{
					$dbIBlock = CIBlock::GetList(
						array(),
						array("ID" => $arIBlockElement["IBLOCK_ID"])
					);
					if ($arIBlock = $dbIBlock->Fetch())
					{
						$this->arIBInfo[$arIBlockElement["IBLOCK_ID"]] = $arIBlock;
					}
				}

				$arProps[] = array(
					"NAME" => "Catalog XML_ID",
					"CODE" => "CATALOG.XML_ID",
					"VALUE" => $this->arIBInfo[$arIBlockElement["IBLOCK_ID"]]["XML_ID"]
				);

				$arProps[] = array(
					"NAME" => "Product XML_ID",
					"CODE" => "PRODUCT.XML_ID",
					"VALUE" => $arIBlockElement["XML_ID"]
				);
				$arProduct = CCatalogProduct::GetByID($arIBlockElement["ID"]);

				$arFields = array(
					"ORDER_ID" => $orderId,
					"PRODUCT_ID" => $arIBlockElement["ID"],
					"PRICE" => $arItem["PRICE"],
					"CURRENCY" => $orderInfo["CURRENCY"],
					"WEIGHT" => $arProduct["WEIGHT"],
					"QUANTITY" => $arItem["QUANTITY"],
					"LID" => $orderInfo["LID"],
					"DELAY" => "N",
					"CAN_BUY" => "Y",
					"NAME" => $arIBlockElement["NAME"],
					"MODULE" => "catalog",
					"NOTES" => $arProduct["CATALOG_GROUP_NAME"],
					"PRODUCT_PROVIDER_CLASS" => "CCatalogProductProvider",
					"DETAIL_PAGE_URL" => $arIBlockElement["DETAIL_PAGE_URL"],
					"CATALOG_XML_ID" => $this->arIBInfo[$arIBlockElement["IBLOCK_ID"]]["XML_ID"],
					"PRODUCT_XML_ID" => $arIBlockElement["XML_ID"],
					"IGNORE_CALLBACK_FUNC" => "Y",
					"VAT_RATE" => $arItem["VAT_RATE"],
				);
			}
		}

		if(empty($arFields))
		{
			$arFields = array(
				"ORDER_ID" => $orderId,
				"PRICE" => $arItem["PRICE"],
				"CURRENCY" => $orderInfo["CURRENCY"],
				"QUANTITY" => $arItem["QUANTITY"],
				"LID" => $orderInfo["LID"],
				"DELAY" => "N",
				"CAN_BUY" => "Y",
				"NAME" => $arItem["NAME"],
				"MODULE" => "1c_exchange",
				"PRODUCT_PROVIDER_CLASS" => false,
				"CATALOG_XML_ID" => "1c_exchange",
				"PRODUCT_XML_ID" => $itemID,
				"IGNORE_CALLBACK_FUNC" => "Y",
				"VAT_RATE" => $arItem["VAT_RATE"],
				"DISCOUNT_PRICE" => $arItem["DISCOUNT_PRICE"],
			);
			if($this->bNewVersion)
			{
				$arFields["MEASURE_CODE"] = $arItem["MEASURE_CODE"];
				$arFields["MEASURE_NAME"] = $arItem["MEASURE_NAME"];
			}

			$ri = new RandomSequence($itemID);
			$arFields["PRODUCT_ID"] = $ri->rand(1000000, 9999999);
		}
		if(strlen($arFields["LID"]) <= 0)
			$arFields["LID"] = $orderInfo["SITE_ID"];

		return $arFields;
	}
}
?>