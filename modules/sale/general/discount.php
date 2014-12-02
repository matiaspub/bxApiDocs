<?
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Loader;
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

	const PREPARE_CONDITIONS = 1;
	const PREPARE_ACTIONS = 2;

	static protected $cacheDiscountHandlers = array();
	static protected $usedModules = array();

	public static function DoProcessOrder(&$arOrder, $arOptions, &$arErrors)
	{
		global $DB;

		$arIDS = array();
		$rsDiscountIDs = CSaleDiscount::GetDiscountGroupList(
			array(),
			array('GROUP_ID' => CUser::GetUserGroup($arOrder['USER_ID'])),
			false,
			false,
			array('DISCOUNT_ID')
		);
		while ($arDiscountID = $rsDiscountIDs->Fetch())
		{
			$arDiscountID['DISCOUNT_ID'] = (int)$arDiscountID['DISCOUNT_ID'];
			if (0 < $arDiscountID['DISCOUNT_ID'])
				$arIDS[$arDiscountID['DISCOUNT_ID']] = true;
		}

		if (!empty($arIDS))
		{
			if (isset($arOrder['BASKET_ITEMS']) && !empty($arOrder['BASKET_ITEMS']) && is_array($arOrder['BASKET_ITEMS']))
			{
				$arExtend = array(
					'catalog' => array(
						'fields' => true,
						'props' => true,
					),
				);
				foreach (GetModuleEvents('sale', 'OnExtendBasketItems', true) as $arEvent)
				{
					ExecuteModuleEventEx($arEvent, array(&$arOrder['BASKET_ITEMS'], $arExtend));
				}

				foreach ($arOrder['BASKET_ITEMS'] as &$arOneItem)
				{
					if (
						array_key_exists('PRODUCT_PROVIDER_CLASS', $arOneItem) && empty($arOneItem['PRODUCT_PROVIDER_CLASS'])
						&& array_key_exists('CALLBACK_FUNC', $arOneItem) && empty($arOneItem['CALLBACK_FUNC'])
					)
					{
						if (isset($arOneItem['DISCOUNT_PRICE']))
						{
							$arOneItem['PRICE'] += $arOneItem['DISCOUNT_PRICE'];
							$arOneItem['DISCOUNT_PRICE'] = 0;
						}
					}
				}
				if (isset($arOneItem))
					unset($arOneItem);
			}
			$arIDS = array_keys($arIDS);
			if (empty(self::$cacheDiscountHandlers))
			{
				self::$cacheDiscountHandlers = CSaleDiscount::getDiscountHandlers($arIDS);
			}
			else
			{
				$needDiscountHandlers = array();
				foreach ($arIDS as &$discountID)
				{
					if (!isset(self::$cacheDiscountHandlers[$discountID]))
						$needDiscountHandlers[] = $discountID;
				}
				unset($discountID);
				if (!empty($needDiscountHandlers))
				{
					$discountHandlersList = CSaleDiscount::getDiscountHandlers($needDiscountHandlers);
					if (!empty($discountHandlersList))
					{
						foreach ($discountHandlersList as $discountID => $discountHandlers)
						{
							self::$cacheDiscountHandlers[$discountID] = $discountHandlers;
						}
						unset($discountHandlers, $discountID);
					}
					unset($discountHandlersList);
				}
				unset($needDiscountHandlers);
			}
			$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
			CTimeZone::Disable();
			$rsDiscounts = CSaleDiscount::GetList(
				array('PRIORITY' => 'DESC', 'SORT' => 'ASC', 'ID' => 'ASC'),
				array(
					'ID' => $arIDS,
					'LID' => $arOrder['SITE_ID'],
					'ACTIVE' => 'Y',
					'!>ACTIVE_FROM' => $strDate,
					'!<ACTIVE_TO' => $strDate
				),
				false,
				false,
				array('ID', 'PRIORITY', 'SORT', 'LAST_DISCOUNT', 'UNPACK', 'APPLICATION')
			);
			CTimeZone::Enable();
			$discountApply = array();
			$resultDiscountList = array();
			$resultDiscountKeys = array();
			$resultDiscountIndex = 0;
			while ($arDiscount = $rsDiscounts->Fetch())
			{
				if (!isset($discountApply[$arDiscount['ID']]))
				{
					$discountApply[$arDiscount['ID']] = true;
					$applyFlag = true;
					if (isset(self::$cacheDiscountHandlers[$arDiscount['ID']]))
					{
						$moduleList = self::$cacheDiscountHandlers[$arDiscount['ID']]['MODULES'];
						if (!empty($moduleList))
						{
							foreach ($moduleList as &$moduleID)
							{
								if (!isset(self::$usedModules[$moduleID]))
								{
									self::$usedModules[$moduleID] = Loader::includeModule($moduleID);
								}
								if (!self::$usedModules[$moduleID])
								{
									$applyFlag = false;
									break;
								}
							}
							unset($moduleID);
						}
						unset($moduleList);
					}
					if ($applyFlag && self::__Unpack($arOrder, $arDiscount['UNPACK']))
					{
						$oldOrder = $arOrder;
						self::__ApplyActions($arOrder, $arDiscount['APPLICATION']);
						$discountResult = self::getDiscountResult($oldOrder, $arOrder, false);
						if (!empty($discountResult))
						{
							$resultDiscountList[$resultDiscountIndex] = array(
								'ID' => $arDiscount['ID'],
								'PRIORITY' => $arDiscount['PRIORITY'],
								'SORT' => $arDiscount['SORT'],
								'LAST_DISCOUNT' => $arDiscount['LAST_DISCOUNT'],
								'UNPACK' => $arDiscount['UNPACK'],
								'APPLICATION' => $arDiscount['APPLICATION'],
								'RESULT' => $discountResult,
								'HANDLERS' => self::$cacheDiscountHandlers[$arDiscount['ID']]
							);
							$resultDiscountKeys[$arDiscount['ID']] = $resultDiscountIndex;
							$resultDiscountIndex++;
							if ($arDiscount['LAST_DISCOUNT'] == 'Y')
								break;
						}
						unset($discountResult);
					}
				}
			}
			unset($arDiscount, $rsDiscounts);

			if ($resultDiscountIndex > 0)
			{
				$rsDiscounts = CSaleDiscount::GetList(
					array(),
					array(
						'ID' => array_keys($resultDiscountKeys)
					),
					false,
					false,
					array('ID', 'NAME', 'CONDITIONS', 'ACTIONS')
				);
				while ($arDiscount = $rsDiscounts->Fetch())
				{
					$arDiscount['ID'] = (int)$arDiscount['ID'];
					if (isset($resultDiscountKeys[$arDiscount['ID']]))
					{
						$key = $resultDiscountKeys[$arDiscount['ID']];
						$resultDiscountList[$key]['NAME'] = $arDiscount['NAME'];
						$resultDiscountList[$key]['CONDITIONS'] = $arDiscount['CONDITIONS'];
						$resultDiscountList[$key]['ACTIONS'] = $arDiscount['ACTIONS'];
					}
				}
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
				if (!CSaleBasketHelper::isSetItem($arShoppingCartItem))
				{
					$arOrder["ORDER_PRICE"] += doubleval($arShoppingCartItem["PRICE"]) * doubleval($arShoppingCartItem["QUANTITY"]);
					$arOrder["ORDER_WEIGHT"] += $arShoppingCartItem["WEIGHT"] * $arShoppingCartItem["QUANTITY"];

					$arShoppingCartItem["PRICE_FORMATED"] = CCurrencyLang::CurrencyFormat($arShoppingCartItem["PRICE"], $arShoppingCartItem["CURRENCY"], true);
					$arShoppingCartItem["DISCOUNT_PRICE_PERCENT"] = $arShoppingCartItem["DISCOUNT_PRICE"]*100 / ($arShoppingCartItem["DISCOUNT_PRICE"] + $arShoppingCartItem["PRICE"]);
					$arShoppingCartItem["DISCOUNT_PRICE_PERCENT_FORMATED"] = roundEx($arShoppingCartItem["DISCOUNT_PRICE_PERCENT"], SALE_VALUE_PRECISION)."%";

					if ($arShoppingCartItem["VAT_RATE"] > 0)
					{
						$arOrder["USE_VAT"] = true;
						if ($arShoppingCartItem["VAT_RATE"] > $arOrder["VAT_RATE"])
							$arOrder["VAT_RATE"] = $arShoppingCartItem["VAT_RATE"];

						$arOrder["VAT_SUM"] += $arShoppingCartItem["VAT_VALUE"] * $arShoppingCartItem["QUANTITY"];
					}
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

		$by = "sort";
		$order = "asc";
		$dbCurrency = CCurrency::GetList($by, $order);
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
	* @return array <p>Ассоциативный массив параметров скидки с ключами:</p> <table
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
	* сортировке).</td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalediscount/csalediscount__getbyid.1af201c0.php
	* @author Bitrix
	*/
	static public function GetByID($ID)
	{
		$ID = (int)$ID;
		if ($ID > 0)
		{
			$rsDiscounts = CSaleDiscount::GetList(
				array(),
				array('ID' => $ID),
				false,
				false,
				array(
					"ID",
					"XML_ID",
					"LID",
					"SITE_ID",
					"NAME",
					"PRICE_FROM",
					"PRICE_TO",
					"CURRENCY",
					"DISCOUNT_VALUE",
					"DISCOUNT_TYPE",
					"ACTIVE",
					"SORT",
					"ACTIVE_FROM",
					"ACTIVE_TO",
					"TIMESTAMP_X",
					"MODIFIED_BY",
					"DATE_CREATE",
					"CREATED_BY",
					"PRIORITY",
					"LAST_DISCOUNT",
					"VERSION",
					"CONDITIONS",
					"UNPACK",
					"APPLICATION",
					"ACTIONS",
				)
			);
			if ($arDiscount = $rsDiscounts->Fetch())
			{
				return $arDiscount;
			}
		}
		return false;
	}

	static public function CheckFields($ACTION, &$arFields)
	{
		global $DB, $APPLICATION, $USER;

		if (empty($arFields) || !is_array($arFields))
		{
			return false;
		}
		$ACTION = strtoupper($ACTION);
		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;

		$discountID = 0;
		if ($ACTION == 'UPDATE')
		{
			if (isset($arFields['ID']))
				$discountID = (int)$arFields['ID'];
			if ($discountID <= 0)
				return false;
		}

		$clearFields = array(
			'ID',
			'~ID',
			'UNPACK',
			'~UNPACK',
			'~CONDITIONS',
			'APPLICATION',
			'~APPLICATION',
			'~ACTIONS',
			'USE_COUPONS',
			'~USE_COUPONS',
			'HANDLERS',
			'~HANDLERS',
			'~VERSION',
			'TIMESTAMP_X',
			'DATE_CREATE',
			'~DATE_CREATE',
			'~MODIFIED_BY',
			'~CREATED_BY'
		);
		if ($ACTION =='UPDATE')
			$clearFields[] = 'CREATED_BY';

		foreach ($clearFields as &$fieldName)
		{
			if (array_key_exists($fieldName, $arFields))
				unset($arFields[$fieldName]);
		}
		unset($fieldName);
		unset($clearFields);

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"]!="Y")
			$arFields["ACTIVE"] = "N";
		if ((is_set($arFields, "DISCOUNT_TYPE") || $ACTION=="ADD") && $arFields["DISCOUNT_TYPE"] != self::OLD_DSC_TYPE_PERCENT)
			$arFields["DISCOUNT_TYPE"] = self::OLD_DSC_TYPE_FIX;

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

		$useConditions = array_key_exists('CONDITIONS', $arFields) || $ACTION == 'ADD';
		$useActions = array_key_exists('ACTIONS', $arFields) || $ACTION == 'ADD';
		$updateHandlers = $useConditions || $useActions;
		$usedHandlers = array();
		$conditionHandlers = array();
		$actionHandlers = array();

		if ($useConditions)
		{
			if (!isset($arFields['CONDITIONS']) || empty($arFields['CONDITIONS']))
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_EMPTY_CONDITIONS"), "CONDITIONS");
				return false;
			}
			else
			{
				$arFields['UNPACK'] = '';
				if (!self::prepareDiscountConditions($arFields['CONDITIONS'], $arFields['UNPACK'], $conditionHandlers, self::PREPARE_CONDITIONS))
				{
					return false;
				}
			}
		}

		if ($useActions)
		{
			if (!isset($arFields['ACTIONS']) || empty($arFields['ACTIONS']))
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_EMPTY_ACTIONS_EXT"), "ACTIONS");
				return false;
			}
			else
			{
				$arFields['APPLICATION'] = '';
				if (!self::prepareDiscountConditions($arFields['ACTIONS'], $arFields['APPLICATION'], $actionHandlers, self::PREPARE_ACTIONS))
				{
					return false;
				}
			}
		}

		if ($updateHandlers)
		{
			if (!$useConditions)
			{
				$rsDiscounts = CSaleDiscount::GetList(
					array(),
					array('ID' => $discountID),
					false,
					false,
					array('ID', 'CONDITIONS')
				);
				if ($discountInfo = $rsDiscounts->Fetch())
				{
					$discountInfo['UNPACK'] = '';
					if (!self::prepareDiscountConditions($discountInfo['CONDITIONS'], $discountInfo['UNPACK'], $conditionHandlers, self::PREPARE_CONDITIONS))
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			if (!$useActions)
			{
				$rsDiscounts = CSaleDiscount::GetList(
					array(),
					array('ID' => $discountID),
					false,
					false,
					array('ID', 'ACTIONS')
				);
				if ($discountInfo = $rsDiscounts->Fetch())
				{
					$discountInfo['APPLICATION'] = '';
					if (!self::prepareDiscountConditions($discountInfo['ACTIONS'], $discountInfo['APPLICATION'], $actionHandlers, self::PREPARE_ACTIONS))
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}
			if (!empty($conditionHandlers) || !empty($actionHandlers))
			{
				if (!empty($conditionHandlers))
					$usedHandlers = $conditionHandlers;
				if (!empty($actionHandlers))
				{
					if (empty($usedHandlers))
					{
						$usedHandlers = $actionHandlers;
					}
					else
					{
						$usedHandlers['MODULES'] = array_unique(array_merge($usedHandlers['MODULES'], $actionHandlers['MODULES']));
						$usedHandlers['EXT_FILES'] = array_unique(array_merge($usedHandlers['EXT_FILES'], $actionHandlers['EXT_FILES']));
					}
				}
			}
		}

		if (!empty($usedHandlers))
			$arFields['HANDLERS'] = $usedHandlers;

		if ((is_set($arFields, 'USE_COUPONS') || $ACTION == 'ADD') && ('Y' != $arFields['USE_COUPONS']))
			$arFields['USE_COUPONS'] = 'N';

		if (array_key_exists('USER_GROUPS', $arFields) || $ACTION=="ADD")
		{
			if (empty($arFields['USER_GROUPS']) || !is_array($arFields['USER_GROUPS']))
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_USER_GROUPS_ABSENT_SHORT"), "USER_GROUPS");
				return false;
			}
			else
			{
				$result = array();
				foreach ($arFields['USER_GROUPS'] as $value)
				{
					$value = (int)$value;
					if (0 < $value)
						$result[$value] = true;
				}
				if (!empty($result))
				{
					$result = array_keys($result);
				}
				$arFields['USER_GROUPS'] = $result;
				if (empty($arFields['USER_GROUPS']))
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_USER_GROUPS_ABSENT_SHORT"), "USER_GROUPS");
					return false;
				}
			}
		}

		$intUserID = 0;
		$boolUserExist = isset($USER) && $USER instanceof CUser;
		if ($boolUserExist)
			$intUserID = (int)$USER->GetID();
		$strDateFunction = $DB->GetNowFunction();
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || (int)$arFields["MODIFIED_BY"] <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ($ACTION == 'ADD')
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || (int)$arFields["CREATED_BY"] <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}

		return true;
	}

	protected static function getDiscountResult(&$oldOrder, &$currentOrder, $extMode = false)
	{
		$extMode = ($extMode === true);
		$result = array();
		if (isset($oldOrder['PRICE_DELIVERY']) && isset($currentOrder['PRICE_DELIVERY']))
		{
			if ($oldOrder['PRICE_DELIVERY'] != $currentOrder['PRICE_DELIVERY'])
			{
				$absValue = $oldOrder['PRICE_DELIVERY'] - $currentOrder['PRICE_DELIVERY'];
				$fullValue = ($extMode && isset($currentOrder['PRICE_DELIVERY_ORIG']) ? $currentOrder['PRICE_DELIVERY_ORIG'] : $oldOrder['PRICE_DELIVERY']);
				$percValue = $absValue*100/$fullValue;
				$result[] = array(
					'TYPE' => 'D',
					'DISCOUNT_TYPE' => ($currentOrder['PRICE_DELIVERY'] < $oldOrder['PRICE_DELIVERY'] ? 'D' : 'M'),
					'VALUE' => $absValue,
					'VALUE_PERCENT' => $percValue,
				);
				unset($percValue, $fullValue, $absValue);
			}
		}
		if (isset($oldOrder['BASKET_ITEMS']) && !empty($oldOrder['BASKET_ITEMS']) && isset($currentOrder['BASKET_ITEMS']) && !empty($currentOrder['BASKET_ITEMS']))
		{
			foreach ($oldOrder['BASKET_ITEMS'] as $key => $item)
			{
				if (!isset($currentOrder['BASKET_ITEMS'][$key]))
					continue;
				if ($item['PRICE'] != $currentOrder['BASKET_ITEMS'][$key]['PRICE'])
				{
					$newItem = &$currentOrder['BASKET_ITEMS'][$key];
					$absValue = $item['PRICE'] - $newItem['PRICE'];
					$fullValue = ($extMode && isset($newItem['PRICE_ORIG']) ? $newItem['PRICE_ORIG'] : $item['PRICE']);
					$percValue = $absValue*100/$fullValue;
					$result[] = array(
						'TYPE' => 'B',
						'DISCOUNT_TYPE' => ($newItem['PRICE'] < $item['PRICE'] ? 'D' : 'M'),
						'VALUE' => $absValue,
						'VALUE_PERCENT' => $percValue,
						'BASKET_NUM' => $key,
						'BASKET_ID' => (isset($newItem['ID']) && (int)$newItem['ID'] > 0 ? $newItem['ID'] : false),
						'BASKET_PRODUCT_XML_ID' => (isset($newItem['PRODUCT_XML_ID']) && $newItem['PRODUCT_XML_ID'] != '' ? $newItem['PRODUCT_XML_ID'] : false)
					);
					unset($percValue, $fullValue, $absValue, $newItem);
				}
			}
		}
		return $result;
	}

	protected function __Unpack($arOrder, $strUnpack)
	{
		$checkOrder = null;
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
		$applyOrder = null;
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

	protected function prepareDiscountConditions(&$conditions, &$result, &$handlers, $type)
	{
		global $APPLICATION;

		$obCond = null;
		$result = '';
		$handlers = array();
		$type = (int)$type;
		if ($type != self::PREPARE_CONDITIONS && $type != self::PREPARE_ACTIONS || empty($conditions))
		{
			return false;
		}
		if (!is_array($conditions))
		{
			if (!CheckSerializedData($conditions))
			{
				if ($type == self::PREPARE_CONDITIONS)
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_CONDITIONS"), "CONDITIONS");
				}
				else
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_ACTIONS_EXT"), "ACTIONS");
				}
				return false;
			}
			$conditions = unserialize($conditions);
			if (!is_array($conditions) || empty($conditions))
			{
				if ($type == self::PREPARE_CONDITIONS)
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_CONDITIONS"), "CONDITIONS");
				}
				else
				{
					$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_ACTIONS_EXT"), "ACTIONS");
				}
				return false;
			}
		}

		if ($type == self::PREPARE_CONDITIONS)
		{
			$obCond = new CSaleCondTree();
			$boolCond = $obCond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_SALE, array());
		}
		else
		{
			$obCond = new CSaleActionTree();
			$boolCond = $obCond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_SALE_ACTIONS, array());
		}
		if (!$boolCond)
		{
			return false;
		}
		$result = $obCond->Generate(
			$conditions,
			array(
				'ORDER' => '$arOrder',
				'ORDER_FIELDS' => '$arOrder',
				'ORDER_PROPS' => '$arOrder[\'PROPS\']',
				'ORDER_BASKET' => '$arOrder[\'BASKET_ITEMS\']',
				'BASKET' => '$arBasket',
				'BASKET_ROW' => '$row',
			)
		);
		if ($result == '')
		{
			if ($type == self::PREPARE_CONDITIONS)
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_CONDITIONS"), "CONDITIONS");
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage("BT_MOD_SALE_DISC_ERR_BAD_ACTIONS_EXT"), "ACTIONS");
			}
			return false;
		}
		else
		{
			$handlers = $obCond->GetConditionHandlers();
		}
		$conditions = serialize($conditions);

		return true;
	}
}
?>