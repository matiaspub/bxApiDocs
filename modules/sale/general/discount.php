<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/index.php
 * @author Bitrix
 */
class CAllSaleDiscount
{
	const VERSION_OLD = 1;
	const VERSION_NEW = 2;

	const OLD_DSC_TYPE_PERCENT = 'P';
	const OLD_DSC_TYPE_FIX = 'V';

	public static function DoProcessOrder(&$arOrder, $arOptions, &$arErrors)
	{
		global $DB;

		if (!array_key_exists("COUNT_DISCOUNT_4_ALL_QUANTITY", $arOptions))
			$arOptions["COUNT_DISCOUNT_4_ALL_QUANTITY"] = COption::GetOptionString("sale", "COUNT_DISCOUNT_4_ALL_QUANTITY", "N");

		$arIDS = array();
		$rsDiscountIDs = CSaleDiscount::GetDiscountGroupList(
			array(),
			array('GROUP_ID' => CUser::GetUserGroup($arOrder["USER_ID"])),
			false,
			false,
			array('DISCOUNT_ID')
		);
		while ($arDiscountID = $rsDiscountIDs->Fetch())
		{
			$arDiscountID['DISCOUNT_ID'] = intval($arDiscountID['DISCOUNT_ID']);
			if (0 < $arDiscountID['DISCOUNT_ID'])
				$arIDS[] = $arDiscountID['DISCOUNT_ID'];
		}

		if (!empty($arIDS))
		{
			$arIDS = array_values(array_unique($arIDS));
			$rsDiscounts = CSaleDiscount::GetList(
				array("PRIORITY" => "DESC", "SORT" => "ASC"),
				array(
					'ID' => $arIDS,
					"LID" => $arOrder["SITE_ID"],
					"ACTIVE" => "Y",
					"!>ACTIVE_FROM" => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL"))),
					"!<ACTIVE_TO" => date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL"))),
				),
				false,
				false,
				array("ID", "PRIORITY", "LAST_DISCOUNT", "UNPACK", "APPLICATION")
			);

			while ($arDiscount = $rsDiscounts->Fetch())
			{
				if (self::__Unpack($arOrder, $arDiscount['UNPACK']))
				{
					self::__ApplyActions($arOrder, $arDiscount['APPLICATION']);
				}
				if ('Y' == $arDiscount['LAST_DISCOUNT'])
					break;
			}

			$arOrder["ORDER_PRICE"] = 0;
			$arOrder["ORDER_WEIGHT"] = 0;
			$arOrder["USE_VAT"] = false;
			$arOrder["VAT_RATE"] = 0;
			$arOrder["VAT_SUM"] = 0;
			$arOrder["DISCOUNT_PRICE"] = 0.0;
			$arOrder["DISCOUNT_VALUE"] = $arOrder["DISCOUNT_PRICE"];
			$arOrder["DELIVERY_PRICE"] = $arOrder["PRICE_DELIVERY"];

			foreach ($arOrder['BASKET_ITEMS'] as &$arShoppingCartItem)
			{
				$arOrder["ORDER_PRICE"] += $arShoppingCartItem["PRICE"] * $arShoppingCartItem["QUANTITY"];
				$arOrder["ORDER_WEIGHT"] += $arShoppingCartItem["WEIGHT"] * $arShoppingCartItem["QUANTITY"];

				if ($arShoppingCartItem["VAT_RATE"] > 0)
				{
					$arOrder["USE_VAT"] = true;
					if ($arShoppingCartItem["VAT_RATE"] > $arOrder["VAT_RATE"])
						$arOrder["VAT_RATE"] = $arShoppingCartItem["VAT_RATE"];

					$arOrder["VAT_SUM"] += $arShoppingCartItem["VAT_VALUE"] * $arShoppingCartItem["QUANTITY"];
				}
			}
			if (isset($arShoppingCartItem))
				unset($arShoppingCartItem);
		}
	}

	static public function PrepareCurrency4Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		$val = doubleval($val);

		$baseSiteCurrency = "";
		if (isset($arFilter["LID"]) && strlen($arFilter["LID"]) > 0)
			$baseSiteCurrency = CSaleLang::GetLangCurrency($arFilter["LID"]);
		elseif (isset($arFilter["CURRENCY"]) && strlen($arFilter["CURRENCY"]) > 0)
			$baseSiteCurrency = $arFilter["CURRENCY"];

		if (strlen($baseSiteCurrency) <= 0)
			return False;

		$strSqlSearch = "";

		$dbCurrency = CCurrency::GetList(($by = "sort"), ($order = "asc"));
		while ($arCurrency = $dbCurrency->Fetch())
		{
			$val1 = roundEx(CCurrencyRates::ConvertCurrency($val, $baseSiteCurrency, $arCurrency["CURRENCY"]), SALE_VALUE_PRECISION);
			if (strlen($strSqlSearch) > 0)
				$strSqlSearch .= " OR ";

			$strSqlSearch .= "(D.CURRENCY = '".$arCurrency["CURRENCY"]."' AND ";
			if ($negative == "Y")
				$strSqlSearch .= "NOT";
			$strSqlSearch .= "(".$field." ".$operation." ".$val1." OR ".$field." IS NULL OR ".$field." = 0)";
			$strSqlSearch .= ")";
		}

		return "(".$strSqlSearch.")";
	}

	
	/**
	 * <p>Функция возвращает параметры скидки с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код скидки.
	 *
	 *
	 *
	 * @return array <p>Ассоциативный массив параметров скидки с ключами:</p><table
	 * class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	 * <td>Код скидки.</td> </tr> <tr> <td>LID</td> <td>Код сайта, к которому привязана
	 * эта скидка.</td> </tr> <tr> <td>PRICE_FROM</td> <td>Общая стоимость заказа, начиная
	 * с которой предоставляется эта скидка.</td> </tr> <tr> <td>PRICE_TO</td> <td>Общая
	 * стоимость заказа, до достижения которой предоставляется эта
	 * скидка.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта денежных полей в записи.</td> </tr>
	 * <tr> <td>DISCOUNT_VALUE</td> <td>Величина скидки.</td> </tr> <tr> <td>DISCOUNT_TYPE</td> <td>Тип
	 * величины скидки (P - величина задана в процентах, V - величина
	 * задана в абсолютной сумме).</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N)
	 * активности скидки.</td> </tr> <tr> <td>SORT</td> <td>Индекс сортировки (если по
	 * сумме заказа доступно несколько скидок, то берется первая по
	 * сортировке).</td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/csalediscount__getbyid.1af201c0.php
	 * @author Bitrix
	 */
	static public function GetByID($ID)
	{
		global $DB;

		$ID = intval($ID);
		if (0 < $ID)
		{
			$rsDiscounts = CSaleDiscount::GetList(array(),array('ID' => $ID),false,false,array());
			if ($arDiscount = $rsDiscounts->Fetch())
			{
				return $arDiscount;
			}
		}
		return false;
	}

	static public function CheckFields($ACTION, &$arFields)
	{
		global $DB;
		global $APPLICATION;

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"] = "N";
		if ((is_set($arFields, "DISCOUNT_TYPE") || $ACTION=="ADD") && $arFields["DISCOUNT_TYPE"]!="P")
			$arFields["DISCOUNT_TYPE"] = "V";

		if ((is_set($arFields, "SORT") || $ACTION=="ADD") && IntVal($arFields["SORT"])<=0)
			$arFields["SORT"] = 100;

		if ((is_set($arFields, "LID") || $ACTION=="ADD") && strlen($arFields["LID"])<=0)
			return false;

		if (is_set($arFields, "LID"))
		{
			$dbSite = CSite::GetByID($arFields["LID"]);
			if (!$dbSite->Fetch())
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["LID"], GetMessage("SKGD_NO_SITE")), "ERROR_NO_SITE");
				return false;
			}
			$arFields['CURRENCY'] = CSaleLang::GetLangCurrency($arFields["LID"]);
		}

		if ((is_set($arFields, "CURRENCY") || $ACTION=="ADD") && strlen($arFields["CURRENCY"])<=0)
			return false;

		if (is_set($arFields, "CURRENCY"))
		{
			if (!($arCurrency = CCurrency::GetByID($arFields["CURRENCY"])))
			{
				$APPLICATION->ThrowException(str_replace("#ID#", $arFields["CURRENCY"], GetMessage("SKGD_NO_CURRENCY")), "ERROR_NO_CURRENCY");
				return false;
			}
		}

		if (is_set($arFields, "DISCOUNT_VALUE") || $ACTION=="ADD")
		{
			if (!is_set($arFields["DISCOUNT_VALUE"]))
				$arFields["DISCOUNT_VALUE"] = '';
			$arFields["DISCOUNT_VALUE"] = str_replace(",", ".", $arFields["DISCOUNT_VALUE"]);
			$arFields["DISCOUNT_VALUE"] = doubleval($arFields["DISCOUNT_VALUE"]);
		}

		if (is_set($arFields, "PRICE_FROM"))
		{
			$arFields["PRICE_FROM"] = str_replace(",", ".", $arFields["PRICE_FROM"]);
			$arFields["PRICE_FROM"] = doubleval($arFields["PRICE_FROM"]);
		}

		if (is_set($arFields, "PRICE_TO"))
		{
			$arFields["PRICE_TO"] = str_replace(",", ".", $arFields["PRICE_TO"]);
			$arFields["PRICE_TO"] = doubleval($arFields["PRICE_TO"]);
		}

		if ((is_set($arFields, "ACTIVE_FROM") || $ACTION=="ADD") && (!$DB->IsDate($arFields["ACTIVE_FROM"], false, LANGUAGE_ID, "FULL")))
			$arFields["ACTIVE_FROM"] = false;
		if ((is_set($arFields, "ACTIVE_TO") || $ACTION=="ADD") && (!$DB->IsDate($arFields["ACTIVE_TO"], false, LANGUAGE_ID, "FULL")))
			$arFields["ACTIVE_TO"] = false;

		if ((is_set($arFields, 'PRIORITY') || $ACTION == 'ADD') && intval($arFields['PRIORITY']) <= 0)
			$arFields['PRIORITY'] = 1;
		if ((is_set($arFields, 'LAST_DISCOUNT') || $ACTION == 'ADD') && $arFields["LAST_DISCOUNT"] != "N")
			$arFields["LAST_DISCOUNT"] = 'Y';

		$arFields['VERSION'] = self::VERSION_NEW;
		if (is_set($arFields, 'UNPACK'))
			unset($arFields['UNPACK']);
		if (is_set($arFields, 'APPLICATION'))
			unset($arFields['APPLICATION']);

		if (is_set($arFields, 'CONDITIONS') || $ACTION == 'ADD')
		{
			if (empty($arFields['CONDITIONS']))
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_EMPTY_CONDITIONS"), "CONDITIONS");
				return false;
			}
			else
			{
				if (!is_array($arFields['CONDITIONS']))
				{
					if (!CheckSerializedData($arFields['CONDITIONS']))
					{
						$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_CONDITIONS"), "CONDITIONS");
						return false;
					}
					$arFields['CONDITIONS'] = unserialize($arFields['CONDITIONS']);
					if (!is_array($arFields['CONDITIONS']) || empty($arFields['CONDITIONS']))
					{
						$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_CONDITIONS"), "CONDITIONS");
						return false;
					}
				}
				$obCond = new CSaleCondTree();
				$boolCond = $obCond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_SALE, array());
				if (!$boolCond)
				{
					return false;
				}
				$strEval = $obCond->Generate(
					$arFields['CONDITIONS'],
					array(
						'ORDER' => '$arOrder',
						'ORDER_FIELDS' => '$arOrder',
						'ORDER_PROPS' => '$arOrder[\'PROPS\']',
						'ORDER_BASKET' => '$arOrder[\'BASKET_ITEMS\']',
						'BASKET' => '$arBasket',
						'BASKET_ROW' => '$row',
						'IBLOCK' => '$row[\'IBLOCK\']',
					)
				);
				if ('' == $strEval)
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_CONDITIONS"), "CONDITIONS");
					return false;
				}
				$arFields['UNPACK'] = $strEval;
				$arFields['CONDITIONS'] = serialize($arFields['CONDITIONS']);
			}
		}

		if (is_set($arFields, 'ACTIONS') || $ACTION == 'ADD')
		{
			if (empty($arFields['ACTIONS']))
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_EMPTY_ACTIONS"), "ACTIONS");
				return false;
			}
			else
			{
				if (!is_array($arFields['ACTIONS']))
				{
					if (!CheckSerializedData($arFields['ACTIONS']))
					{
						$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_ACTIONS"), "ACTIONS");
						return false;
					}
					$arFields['ACTIONS'] = unserialize($arFields['ACTIONS']);
					if (!is_array($arFields['ACTIONS']) || empty($arFields['ACTIONS']))
					{
						$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_ACTIONS"), "ACTIONS");
						return false;
					}
				}
				$obAct = new CSaleActionTree();
				$boolAct = $obAct->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_SALE_ACTIONS, array());
				if (!$boolAct)
				{
					return false;
				}
				$strEval = $obAct->Generate(
					$arFields['ACTIONS'],
					array(
						'ORDER' => '$arOrder',
						'ORDER_FIELDS' => '$arOrder',
						'ORDER_BASKET' => '$arOrder[\'BASKET_ITEMS\']',
						'BASKET' => '$arBasket',
						'BASKET_ROW' => '$row',
						'IBLOCK' => '$row[\'IBLOCK\']',
					)
				);
				if ('' == $strEval)
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_ACTIONS"), "ACTIONS");
					return false;
				}
				$arFields['APPLICATION'] = $strEval;
				$arFields['ACTIONS'] = serialize($arFields['ACTIONS']);
			}
		}

		if ((is_set($arFields, 'USE_COUPONS') || $ACTION == 'ADD') && ('Y' != $arFields['USE_COUPONS']))
			$arFields['USE_COUPONS'] = 'N';

		if ((is_set($arFields, 'USER_GROUPS') || $ACTION=="ADD") && (!is_array($arFields['USER_GROUPS']) || empty($arFields['USER_GROUPS'])))
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_USER_GROUPS_ABSENT_SHORT"), "USER_GROUPS");
			return false;
		}

		return true;
	}

	protected function __Unpack($arOrder, $strUnpack)
	{
		if (empty($strUnpack))
			return false;
		eval('$checkOrder='.$strUnpack.';');
		if (!is_callable($checkOrder))
			return false;
		$boolRes = $checkOrder($arOrder);
		unset($checkOrder);
		return $boolRes;
	}

	protected function __ApplyActions(&$arOrder, $strActions)
	{
		if (!empty($strActions))
		{
			eval('$applyOrder='.$strActions.';');
			if (is_callable($applyOrder))
				$applyOrder($arOrder);
		}
	}

	protected function __ConvertOldFormat($strAction, &$arFields)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$arNeedFields = array(
			'LID',
			'CURRENCY',
			'DISCOUNT_TYPE',
			'DISCOUNT_VALUE',
			'PRICE_FROM',
			'PRICE_TO',
		);
		$arUpdateFields = array(
			'DISCOUNT_VALUE',
			'PRICE_FROM',
			'PRICE_TO',
		);

		$strAction = ToUpper($strAction);
		if (!array_key_exists('CONDITIONS', $arFields) && !array_key_exists('ACTIONS', $arFields))
		{
			$strSiteCurrency = '';
			$boolUpdate = false;

			if ('UPDATE' == $strAction)
			{
				$boolNeedQuery = false;
				foreach ($arUpdateFields as &$strFieldID)
				{
					if (array_key_exists($strFieldID, $arFields))
					{
						$boolUpdate = true;
						break;
					}
				}
				if (isset($strFieldID))
					unset($strFieldID);
				if ($boolUpdate)
				{
					foreach ($arNeedFields as &$strFieldID)
					{
						if (!array_key_exists($strFieldID, $arFields))
						{
							$boolNeedQuery = true;
							break;
						}
					}
					if (isset($strFieldID))
						unset($strFieldID);

					if ($boolNeedQuery)
					{
						$rsDiscounts = CSaleDiscount::GetList(array(), array('ID' => $arFields['ID']), false, false, $arNeedFields);
						if ($arDiscount = $rsDiscounts->Fetch())
						{
							foreach ($arNeedFields as &$strFieldID)
							{
								if (!array_key_exists($strFieldID, $arFields))
								{
									$arFields[$strFieldID] = $arDiscount[$strFieldID];
								}
							}
							if (isset($strFieldID))
								unset($strFieldID);
						}
						else
						{
							$boolUpdate = false;
							$boolResult = false;
							$arMsg[] = array('id' => 'ID', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_ABSENT'));
						}
					}
				}
			}

			if ('ADD' == $strAction || $boolUpdate)
			{
				if (!array_key_exists('LID', $arFields))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'LID','text' => GetMessage('BT_MOD_SALE_ERR_DSC_SITE_ID_ABSENT'));
				}
				else
				{
					$arFields['LID'] = strval($arFields['LID']);
					if ('' == $arFields['LID'])
					{
						$boolResult = false;
						$arMsg[] = array('id' => 'LID','text' => GetMessage('BT_MOD_SALE_ERR_DSC_SITE_ID_ABSENT'));
					}
					else
					{
						$rsSites = CSite::GetByID($arFields["LID"]);
						if (!$arSite = $rsSites->Fetch())
						{
							$boolResult = false;
							$arMsg[] = array('id' => 'LID', 'text' => str_replace("#ID#", $arFields["LID"], GetMessage("SKGD_NO_SITE")));
						}
						else
						{
							$strSiteCurrency = CSaleLang::GetLangCurrency($arFields['LID']);
						}
					}
				}

				if (!array_key_exists('CURRENCY', $arFields))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_CURRENCY_ABSENT'));
				}
				else
				{
					$arFields['CURRENCY'] = strval($arFields['CURRENCY']);
					if ('' == $arFields['CURRENCY'])
					{
						$boolResult = false;
						$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_CURRENCY_ABSENT'));
					}
					else
					{
						if (!($arCurrency = CCurrency::GetByID($arFields["CURRENCY"])))
						{
							$boolResult = false;
							$arMsg[] = array('id' => 'CURRENCY', 'text' => str_replace("#ID#", $arFields["CURRENCY"], GetMessage("SKGD_NO_CURRENCY")));
						}
					}
				}

				if (!array_key_exists("DISCOUNT_TYPE", $arFields))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'DISCOUNT_TYPE', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_TYPE_ABSENT'));
				}
				else
				{
					$arFields["DISCOUNT_TYPE"] = strval($arFields["DISCOUNT_TYPE"]);
					if (CSaleDiscount::OLD_DSC_TYPE_PERCENT != $arFields["DISCOUNT_TYPE"] && CSaleDiscount::OLD_DSC_TYPE_FIX != $arFields["DISCOUNT_TYPE"])
					{
						$boolResult = false;
						$arMsg[] = array('id' => 'DISCOUNT_TYPE', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_TYPE_BAD'));
					}
				}

				if (!array_key_exists('DISCOUNT_VALUE', $arFields))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'DISCOUNT_VALUE', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_VALUE_ABSENT'));
				}
				else
				{
					$arFields['DISCOUNT_VALUE'] = doubleval(str_replace(',', '.', $arFields['DISCOUNT_VALUE']));
					if (0 >= $arFields['DISCOUNT_VALUE'])
					{
						$boolResult = false;
						$arMsg[] = array('id' => 'DISCOUNT_VALUE', 'text' => GetMessage('BT_MOD_SALE_ERR_DSC_VALUE_BAD'));
					}
				}

				if ($boolResult)
				{
					$arConditions = array(
						'CLASS_ID' => 'CondGroup',
						'DATA' => array(
							'All' => 'AND',
							'True' => 'True',
						),
						'CHILDREN' => array(),
					);
					$arActions = array(
						'CLASS_ID' => 'CondGroup',
						'DATA' => array(
							'All' => 'AND',
							'True' => 'True',
						),
						'CHILDREN' => array(),
					);

					$boolCurrency = ($arFields['CURRENCY'] == $strSiteCurrency);

					if (array_key_exists('PRICE_FROM', $arFields))
					{
						$arFields["PRICE_FROM"] = str_replace(",", ".", strval($arFields["PRICE_FROM"]));
						$arFields["PRICE_FROM"] = doubleval($arFields["PRICE_FROM"]);
						if (0 < $arFields["PRICE_FROM"])
						{
							$dblValue = roundEx(($boolCurrency ? $arFields['PRICE_FROM'] : CCurrencyRates::ConvertCurrency($arFields['PRICE_FROM'], $arFields['CURRENCY'], $strSiteCurrency)), SALE_VALUE_PRECISION);
							$arConditions['CHILDREN'][] = array(
								'CLASS_ID' => 'CondBsktAmtGroup',
								'DATA' => array(
									'logic' => 'EqGr',
									'Value' => (string)$dblValue,
									'All' => 'AND',
								),
								'CHILDREN' => array(
								),
							);
							$arFields["PRICE_FROM"] = $dblValue;
						}
					}
					if (array_key_exists('PRICE_TO', $arFields))
					{
						$arFields["PRICE_TO"] = str_replace(",", ".", strval($arFields["PRICE_TO"]));
						$arFields["PRICE_TO"] = doubleval($arFields["PRICE_TO"]);
						if (0 < $arFields["PRICE_TO"])
						{
							$dblValue = roundEx(($boolCurrency ? $arFields['PRICE_TO'] : CCurrencyRates::ConvertCurrency($arFields['PRICE_TO'], $arFields['CURRENCY'], $strSiteCurrency)), SALE_VALUE_PRECISION);
							$arConditions['CHILDREN'][] = array(
								'CLASS_ID' => 'CondBsktAmtGroup',
								'DATA' => array(
									'logic' => 'EqLs',
									'Value' => (string)$dblValue,
									'All' => 'AND',
								),
								'CHILDREN' => array(
								),
							);
							$arFields["PRICE_TO"] = $dblValue;
						}
					}
					if (self::OLD_DSC_TYPE_PERCENT == $arFields['DISCOUNT_TYPE'])
					{
						$arActions['CHILDREN'][] = array(
							'CLASS_ID' => 'ActSaleBsktGrp',
							'DATA' => array(
								'Type' => 'Discount',
								'Value' => (string)roundEx($arFields['DISCOUNT_VALUE'], SALE_VALUE_PRECISION),
								'Unit' => 'Perc',
								'All' => 'AND',
							),
							'CHILDREN' => array(
							),
						);
					}
					else
					{
						$dblValue = roundEx(($boolCurrency ? $arFields['DISCOUNT_VALUE'] : CCurrencyRates::ConvertCurrency($arFields['DISCOUNT_VALUE'], $arFields['CURRENCY'], $strSiteCurrency)), SALE_VALUE_PRECISION);
						$arActions['CHILDREN'][] = array(
							'CLASS_ID' => 'ActSaleBsktGrp',
							'DATA' => array(
								'Type' => 'Discount',
								'Value' => (string)$dblValue,
								'Unit' => 'CurAll',
								'All' => 'AND',
							),
							'CHILDREN' => array(
							),
						);
						$arFields['DISCOUNT_VALUE'] = $dblValue;
					}

					$arFields['CONDITIONS'] = $arConditions;
					$arFields['ACTIONS'] = $arActions;
					$arFields['CURRENCY'] = $strSiteCurrency;
				}
				else
				{
					$obError = new CAdminException($arMsg);
					$APPLICATION->ThrowException($obError);
				}
			}
		}
		return $boolResult;
	}

	protected function __SetOldFields($strAction, &$arFields)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$strAction = ToUpper($strAction);
		if (array_key_exists('CONDITIONS', $arFields) && !empty($arFields['CONDITIONS']))
		{
			$arConditions = false;
			if (!is_array($arFields['CONDITIONS']))
			{
				if (CheckSerializedData($arFields['CONDITIONS']))
				{
					$arConditions = unserialize($arFields['CONDITIONS']);
				}
			}
			else
			{
				$arConditions = $arFields['CONDITIONS'];
			}

			if (is_array($arConditions) && !empty($arConditions))
			{
				$obCond = new CSaleCondTree();
				$boolCond = $obCond->Init(BT_COND_MODE_SEARCH, BT_COND_BUILD_SALE, array());
				if ($boolCond)
				{
					$arResult = $obCond->GetConditionValues($arConditions);

				}
			}
		}
		if (array_key_exists('ACTIONS', $arFields) && !empty($arFields['ACTIONS']))
		{
			$arActions = false;
			if (!is_array($arFields['ACTIONS']))
			{
				if (CheckSerializedData($arFields['ACTIONS']))
				{
					$arActions = unserialize($arFields['ACTIONS']);
				}
			}
			else
			{
				$arActions = $arFields['ACTIONS'];
			}

			if (is_array($arActions) && !empty($arActions))
			{
				$obAct = new CSaleActionTree();
				$boolAct = $obAct->Init(BT_COND_MODE_SEARCH, BT_COND_BUILD_SALE_ACTIONS, array());
				if ($boolAct)
				{
					$arResult = $obAct->GetConditionValues($arActions);
				}
			}
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ThrowException($obError);
		}

		return $boolResult;
	}
}
?>