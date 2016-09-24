<?
use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Highloadblock\HighloadBlockTable,
	Bitrix\Currency,
	Bitrix\Iblock,
	Bitrix\Catalog,
	Bitrix\Main;

Loc::loadMessages(__FILE__);

class CIBlockPriceTools
{
	protected static $catalogIncluded = null;
	protected static $highLoadInclude = null;
	protected static $needDiscountCache = null;
	protected static $calculationDiscounts = 0;

	/**
	 * @param int $IBLOCK_ID
	 * @param array $arPriceCode
	 * @return array
	 * @throws Main\LoaderException
	 */
	
	/**
	* <p>Метод возвращает перечень типов цен с параметрами типа и с указанием, возможен ли просмотр и покупка этого типа цен для групп текущего пользователя. Метод статический.</p>
	*
	*
	* @param int $IBLOCK_ID  Идентификатор инфоблока. В действительности параметр
	* используется только в редакциях <b>без</b> модуля <b>Торговый
	* каталог</b>, несмотря на то, что является обязательным.
	*
	* @param array $arPriceCode  Массив, зависящий от редакции продукта: 		<ul> <li>если редакция с
	* модулем <b>Торговый каталог</b>, то это массив кодов (поле NAME) типов
	* цен, для которых надо выбрать информацию.</li> 		<li>если редакция без
	* модуля <b>Торговый каталог</b>, то это массив символьных кодов
	* свойств типа <b>Число</b> инфоблока <i>IBLOCK_ID</i>.</li> 		</ul>
	*
	* @return array <p>Возвращает массив. В случае ошибки массив будет пустым. Если же
	* информация есть, то вернется массив следующей структуры:</p>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getcatalogprices.php
	* @author Bitrix
	*/
	public static function GetCatalogPrices($IBLOCK_ID, $arPriceCode)
	{
		global $USER;
		$arCatalogPrices = array();
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			$arCatalogGroupCodesFilter = array();
			foreach($arPriceCode as $value)
			{
				$t_value = trim($value);
				if ('' != $t_value)
					$arCatalogGroupCodesFilter[$value] = true;
			}
			$arCatalogGroupsFilter = array();
			$arCatalogGroups = CCatalogGroup::GetListArray();
			foreach ($arCatalogGroups as $key => $value)
			{
				if (isset($arCatalogGroupCodesFilter[$value['NAME']]))
				{
					$arCatalogGroupsFilter[] = $key;
					$arCatalogPrices[$value["NAME"]] = array(
						"ID" => (int)$value["ID"],
						"TITLE" => htmlspecialcharsbx($value["NAME_LANG"]),
						"SELECT" => "CATALOG_GROUP_".$value["ID"],
					);
				}
			}
			$userGroups = array(2);
			if (isset($USER) && $USER instanceof CUser)
				$userGroups = $USER->GetUserGroupArray();
			$arPriceGroups = CCatalogGroup::GetGroupsPerms($userGroups, $arCatalogGroupsFilter);
			foreach($arCatalogPrices as $name=>$value)
			{
				$arCatalogPrices[$name]["CAN_VIEW"] = in_array($value["ID"], $arPriceGroups["view"]);
				$arCatalogPrices[$name]["CAN_BUY"] = in_array($value["ID"], $arPriceGroups["buy"]);
			}
		}
		else
		{
			$arPriceGroups = array(
				"view" => array(),
			);
			$rsProperties = CIBlockProperty::GetList(array(), array(
				"IBLOCK_ID" => $IBLOCK_ID,
				"CHECK_PERMISSIONS" => "N",
				"PROPERTY_TYPE" => "N",
				"MULTIPLE" => "N"
			));
			while ($arProperty = $rsProperties->Fetch())
			{
				if (in_array($arProperty["CODE"], $arPriceCode))
				{
					$arPriceGroups["view"][]=htmlspecialcharsbx("PROPERTY_".$arProperty["CODE"]);
					$arCatalogPrices[$arProperty["CODE"]] = array(
						"ID" => (int)$arProperty["ID"],
						"TITLE" => htmlspecialcharsbx($arProperty["NAME"]),
						"SELECT" => "PROPERTY_".$arProperty["ID"],
						"CAN_VIEW"=>true,
						"CAN_BUY"=>false,
					);
				}
			}
		}
		return $arCatalogPrices;
	}

	/**
	 * @param array $arPriceTypes
	 * @return array
	 */
	public static function GetAllowCatalogPrices($arPriceTypes)
	{
		$arResult = array();
		if (!empty($arPriceTypes) && is_array($arPriceTypes))
		{
			foreach ($arPriceTypes as &$arOnePriceType)
			{
				if ($arOnePriceType['CAN_VIEW'] || $arOnePriceType['CAN_BUY'])
					$arResult[] = (int)$arOnePriceType['ID'];
			}
			unset($arOnePriceType);
		}
		return $arResult;
	}

	public static function SetCatalogDiscountCache($arCatalogGroups, $arUserGroups)
	{
		global $DB;
		$result = false;

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			if (!is_array($arCatalogGroups) || !is_array($arUserGroups))
				return false;
			Main\Type\Collection::normalizeArrayValuesByInt($arCatalogGroups, true);
			if (empty($arCatalogGroups))
				return false;
			Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups, true);
			if (empty($arUserGroups))
				return false;

			$arRestFilter = array(
				'PRICE_TYPES' => $arCatalogGroups,
				'USER_GROUPS' => $arUserGroups,
			);
			$arRest = CCatalogDiscount::GetRestrictions($arRestFilter, false, false);
			$arDiscountFilter = array();
			$arDiscountResult = array();
			if (empty($arRest) || (array_key_exists('DISCOUNTS', $arRest) && empty($arRest['DISCOUNTS'])))
			{
				foreach ($arCatalogGroups as &$intOneGroupID)
				{
					$strCacheKey = CCatalogDiscount::GetDiscountFilterCacheKey(array($intOneGroupID), $arUserGroups, false);
					$arDiscountFilter[$strCacheKey] = array();
				}
				unset($intOneGroupID);
			}
			else
			{
				$arResultDiscountList = array();

				$arSelect = array(
					'ID', 'TYPE', 'SITE_ID', 'ACTIVE', 'ACTIVE_FROM', 'ACTIVE_TO',
					'RENEWAL', 'NAME', 'SORT', 'MAX_DISCOUNT', 'VALUE_TYPE', 'VALUE', 'CURRENCY',
					'PRIORITY', 'LAST_DISCOUNT',
					'COUPON', 'COUPON_ONE_TIME', 'COUPON_ACTIVE', 'UNPACK', 'CONDITIONS'
				);
				$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat('FULL')));
				$discountRows = array_chunk($arRest['DISCOUNTS'], 500);
				foreach ($discountRows as &$row)
				{
					$arFilter = array(
						'@ID' => $row,
						'SITE_ID' => SITE_ID,
						'TYPE' => DISCOUNT_TYPE_STANDART,
						'RENEWAL' => 'N',
						'+<=ACTIVE_FROM' => $strDate,
						'+>=ACTIVE_TO' => $strDate,
						'+COUPON' => array()
					);
					$rsPriceDiscounts = CCatalogDiscount::GetList(array(), $arFilter, false, false, $arSelect);
					while ($arPriceDiscount = $rsPriceDiscounts->Fetch())
					{
						$arPriceDiscount['ID'] = (int)$arPriceDiscount['ID'];
						$arResultDiscountList[$arPriceDiscount['ID']] = $arPriceDiscount;
					}
					unset($arPriceDiscount, $rsPriceDiscounts, $arFilter);
				}
				unset($row, $discountRows);
				foreach ($arCatalogGroups as &$intOneGroupID)
				{
					$strCacheKey = CCatalogDiscount::GetDiscountFilterCacheKey(array($intOneGroupID), $arUserGroups, false);
					$arDiscountDetailList = array();
					$arDiscountList = array();
					foreach ($arRest['RESTRICTIONS'] as $intDiscountID => $arDiscountRest)
					{
						if (empty($arDiscountRest['PRICE_TYPE']) || array_key_exists($intOneGroupID, $arDiscountRest['PRICE_TYPE']))
						{
							$arDiscountList[] = $intDiscountID;
							if (isset($arResultDiscountList[$intDiscountID]))
								$arDiscountDetailList[] = $arResultDiscountList[$intDiscountID];
						}
					}
					sort($arDiscountList);
					$arDiscountFilter[$strCacheKey] = $arDiscountList;
					$strResultCacheKey = CCatalogDiscount::GetDiscountResultCacheKey($arDiscountList, SITE_ID, 'N');
					$arDiscountResult[$strResultCacheKey] = $arDiscountDetailList;
				}
				if (isset($intOneGroupID))
					unset($intOneGroupID);
			}
			$boolFlag = CCatalogDiscount::SetAllDiscountFilterCache($arDiscountFilter, false);
			$boolFlagExt = CCatalogDiscount::SetAllDiscountResultCache($arDiscountResult);
			$result = $boolFlag && $boolFlagExt;
			self::$needDiscountCache = $result;
		}
		return $result;
	}

	
	/**
	* <p>Метод возвращает рассчитанные с учетом скидок (если это торговый каталог) цены для элемента. Метод статический.</p>
	*
	*
	* @param int $IBLOCK_ID  Идентификатор инфоблока элемента. В действительности в коде
	* сейчас не используется.
	*
	* @param array $arCatalogPrices  Массив с данными, зависящий от редакции продукта: 		<ul> <li>если
	* редакция с модулем <b>Торговый каталог</b>, то это массив типов цен,
	* которые вернул метод <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getcatalogprices.php">CIBlockPriceTools::GetCatalogPrices</a>.</li>
	* 		<li>если редакция без модуля <b>Торговый каталог</b>, то это массив
	* свойств, которые вернул метод <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getcatalogprices.php">CIBlockPriceTools::GetCatalogPrices</a>.</li>
	* 		</ul>
	*
	* @param array $arItem  Элемент инфоблока, который был получен с помощью <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">CIBlockElement::GetList</a>: 		<ul>
	* <li>для редакций с модулем <b>Торговый каталог</b> в элементе должны
	* присутствовать данные по ценам.</li> 		<li>для редакций без модуля
	* <b>Торговый каталог</b> - свойства типа <b>Число</b>, упомянутые в <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getcatalogprices.php">CIBlockPriceTools::GetCatalogPrices</a>.</li>
	* 		</ul>
	*
	* @param bool $bVATInclude = true (<i>true/false</i>) Флаг определяет включать ли НДС в цену, если он не был
	* включен.
	*
	* @param array $arCurrencyParams = array() Массив параметров, отвечающий за конвертацию цен в одну валюту.
	*
	* @param int $USER_ID = 0 Идентификатор пользователя (если отсутствует или равен нулю, то
	* группы берутся для текущего пользователя, а если задан, то
	* рассчитываются для этого конкретного пользователя). Параметр
	* влияет на выборку скидок. Необязательный.
	*
	* @param string $LID = SITE_ID Идентификатор сайта, для которого ведутся расчеты (если не задан,
	* то берется текущий сайт). Параметр влияет на выборку скидок.
	* Необязательный.
	*
	* @return array <p>Возвращает массив. В случае ошибки или отсутствия доступных
	* типов цен (ни купить, ни посмотреть либо сами цены не заданы)
	* массив будет пустым. В случае успешного выполнения вернется
	* массив следующей структуры:</p><ol> <li>Для редакций с модулем
	* <b>Торговый каталог</b>: <p>Ключ - код типа цены. Значение - массив с
	* полями:</p> <ul> <li> <b>ID</b> - идентификатор ценового предложения;</li> <li>
	* <b>PRICE_ID</b> - идентификатор типа цены;</li> <li> <b>CAN_ACCESS</b> - (<i>true/false</i>)
	* флаг определяющий возможность просмотра цены этого типа;</li> <li>
	* <b>CAN_BUY</b> - (<i>true/false</i>) флаг определяющий возможность покупки по
	* цене этого типа;</li> <li> <b>MIN_PRICE</b> - (<i>Y/N</i>) значение <i>Y</i> задается
	* для наименьшей из цен, которые можно хотя бы смотреть;</li> <li>
	* <b>VALUE_NOVAT</b> - значение цены без НДС;</li> <li> <b>PRINT_VALUE_NOVAT</b> -
	* отформатированное значение цены без НДС;</li> <li> <b>VALUE_VAT</b> - цена с
	* НДС;</li> <li> <b>PRINT_VALUE_VAT</b> - отформатированная цена с НДС;</li> <li>
	* <b>VATRATE_VALUE</b> - абсолютная величина НДС (не проценты);</li> <li>
	* <b>PRINT_VATRATE_VALUE</b> - отформатированная абс. величина НДС;</li> <li>
	* <b>DISCOUNT_VALUE_NOVAT</b> - цена со скидкой без НДС;</li> <li> <b>PRINT_DISCOUNT_VALUE_NOVAT</b> -
	* отформатированная цена со скидкой без НДС;</li> <li> <b>DISCOUNT_VALUE_VAT</b> -
	* НДС скидки;</li> <li> <b>PRINT_DISCOUNT_VALUE_VAT</b> - отформатированный НДС
	* скидки;</li> <li> <b>DISCOUNT_VATRATE_VALUE</b> - цена со скидкой с НДС;</li> <li>
	* <b>PRINT_DISCOUNT_VATRATE_VALUE</b> - отформатированная цена со скидкой с НДС;</li>
	* <li> <b>CURRENCY</b> - код валюты;</li> <li>следующие параметры зависят от
	* значения параметра <i>$bVATInclude</i>. Если он принимает значение <i>true</i>,
	* то в них копируются данные с включенным НДС, если нет - без НДС: <ul>
	* <li> <b>VALUE</b> - цена для вывода;</li> <li> <b>PRINT_VALUE</b> - отформатированная
	* цена для вывода;</li> <li> <b>DISCOUNT_VALUE</b> - цена со скидкой;</li> <li>
	* <b>PRINT_DISCOUNT_VALUE</b> - отформатированная цена со скидкой;</li> <li>
	* <b>DISCOUNT_DIFF</b> - величина скидки;</li> <li> <b>DISCOUNT_DIFF_PERCENT</b> - процент
	* скидки с округлением до целого</li> <li> <b>PRINT_DISCOUNT_DIFF</b> -
	* отформатированная величина скидки;</li> </ul> </li> <li>в случае, когда
	* включено приведение к одной валюте и исходная валюта не равна
	* той, в которую надо сконвертировать, появляются ключи с префиксом
	* <b>ORIG_</b>. Такие ключи содержат исходные данные.<br><br> </li> </ul> </li> <li>
	* Для редакций без модуля <b>Торговый каталог</b> конвертация валют и
	* НДС не используются: <p>Ключ - символьный код свойства. Значение -
	* массив с полями:</p> <ul> <li> <b>CURRENCY</b> - берется из <b>DESCRIPTION</b> (описания)
	* значения свойства;</li> <li> <b>CAN_ACCESS</b> - всегда <i>true</i>;</li> <li> <b>CAN_BUY</b> -
	* всегда <i>false</i>;</li> <li> <b>PRICE_ID</b> - идентификатор свойства;</li> <li> <b>ID</b>
	* - идентификатор значения свойства;</li> <li> <b>VALUE</b> - значение
	* свойства;</li> <li> <b>PRINT_VALUE</b> - значение свойства и значение описания
	* (типа валюта);</li> <li> <b>DISCOUNT_VALUE</b> - всегда <i>VALUE</i>;</li> <li>
	* <b>PRINT_DISCOUNT_VALUE</b> - всегда <i>PRINT_VALUE</i>;</li> <li> <b>MIN_PRICE</b> - Y для
	* минимального из значений свойств;</li> <li> <b>DISCOUNT_DIFF_PERCENT</b> - всегда
	* <i>0</i>;</li> <li> <b>DISCOUNT_DIFF</b> - всегда <i>0</i>;</li> <li> <b>PRINT_DISCOUNT_DIFF</b> - всегда
	* <i>0</i> и описание значения (типа валюта).</li> </ul> </li> </ol><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getitemprices.php
	* @author Bitrix
	*/
	public static function GetItemPrices(
		/** @noinspection PhpUnusedParameterInspection */$IBLOCK_ID,
		$arCatalogPrices,
		$arItem, $bVATInclude = true,
		$arCurrencyParams = array(),
		$USER_ID = 0,
		$LID = SITE_ID
	)
	{
		$arPrices = array();

		if (empty($arCatalogPrices) || !is_array($arCatalogPrices))
			return $arPrices;

		global $USER;
		static $arCurUserGroups = array();
		static $strBaseCurrency = '';

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			$existUser = (isset($USER) && $USER instanceof CUser);
			$USER_ID = (int)$USER_ID;
			$intUserID = ($USER_ID > 0 ? $USER_ID : 0);
			if ($intUserID == 0 && $existUser)
				$intUserID = (int)$USER->GetID();
			if (!isset($arCurUserGroups[$intUserID]))
			{
				$arUserGroups = array(2);
				if ($intUserID > 0)
					$arUserGroups = CUser::GetUserGroup($intUserID);
				elseif ($existUser)
					$arUserGroups = $USER->GetUserGroupArray();
				Main\Type\Collection::normalizeArrayValuesByInt($arUserGroups);
				$arCurUserGroups[$intUserID] = $arUserGroups;
				unset($arUserGroups);
			}
			$arUserGroups = $arCurUserGroups[$intUserID];

			$boolConvert = false;
			$resultCurrency = '';
			if (isset($arCurrencyParams['CURRENCY_ID']) && !empty($arCurrencyParams['CURRENCY_ID']))
			{
				$boolConvert = true;
				$resultCurrency = $arCurrencyParams['CURRENCY_ID'];
			}
			if (!$boolConvert && '' == $strBaseCurrency)
				$strBaseCurrency = Currency\CurrencyManager::getBaseCurrency();

			$percentVat = $arItem['CATALOG_VAT'] * 0.01;
			$percentPriceWithVat = 1 + $arItem['CATALOG_VAT'] * 0.01;

			$strMinCode = '';
			$boolStartMin = true;
			$dblMinPrice = 0;
			$strMinCurrency = ($boolConvert ? $resultCurrency : $strBaseCurrency);
			CCatalogDiscountSave::Disable();
			foreach ($arCatalogPrices as $key => $value)
			{
				$catalogPriceValue = 'CATALOG_PRICE_'.$value['ID'];
				$catalogCurrencyValue = 'CATALOG_CURRENCY_'.$value['ID'];
				if (
					!$value['CAN_VIEW']
					|| !isset($arItem[$catalogPriceValue])
					|| $arItem[$catalogPriceValue] == ''
				)
					continue;

				$arItem[$catalogPriceValue] = (float)$arItem[$catalogPriceValue];
				// get final price with VAT included.
				if ($arItem['CATALOG_VAT_INCLUDED'] != 'Y')
					$arItem[$catalogPriceValue] *= $percentPriceWithVat;

				$originalCurrency = $arItem[$catalogCurrencyValue];
				$calculateCurrency = $arItem[$catalogCurrencyValue];
				$calculatePrice = $arItem[$catalogPriceValue];
				$cnangeCurrency = ($boolConvert && $resultCurrency != $calculateCurrency);
				if ($cnangeCurrency)
				{
					$calculateCurrency = $resultCurrency;
					$calculatePrice = CCurrencyRates::ConvertCurrency($calculatePrice, $originalCurrency, $resultCurrency);
				}

				// so discounts will include VAT
				$discounts = array();
				if (self::isEnabledCalculationDiscounts())
				{
					$discounts = CCatalogDiscount::GetDiscount(
						$arItem['ID'],
						$arItem['IBLOCK_ID'],
						array($value['ID']),
						$arUserGroups,
						'N',
						$LID,
						array()
					);
				}
				$discountPrice = CCatalogProduct::CountPriceWithDiscount(
					$calculatePrice,
					$calculateCurrency,
					$discounts
				);
				unset($discounts);
				if ($discountPrice === false)
					continue;

				$originalPriceWithVat = $arItem[$catalogPriceValue];
				$priceWithVat = $calculatePrice;
				$discountPriceWithVat = $discountPrice;

				if ($cnangeCurrency)
					$originalDiscountPrice = CCurrencyRates::ConvertCurrency($discountPrice, $calculateCurrency, $arItem[$catalogCurrencyValue]);
				else
					$originalDiscountPrice = $discountPrice;
				$originalDiscountPriceWithVat = $originalDiscountPrice;

				$arItem[$catalogPriceValue] /= $percentPriceWithVat;
				$calculatePrice /= $percentPriceWithVat;
				$originalDiscountPrice /= $percentPriceWithVat;
				$discountPrice /= $percentPriceWithVat;

				$originalVatValue = $originalPriceWithVat - $arItem[$catalogPriceValue];
				$vatValue = $priceWithVat - $calculatePrice;
				$originalDiscountVatValue = $originalDiscountPriceWithVat - $originalDiscountPrice;
				$discountVatValue = $discountPriceWithVat - $discountPrice;

				$roundPriceWithVat = Catalog\Product\Price::roundPrice($value['ID'], $discountPriceWithVat, $calculateCurrency);
				$roundPrice = Catalog\Product\Price::roundPrice($value['ID'], $discountPrice, $calculateCurrency);

				$roundValueWithVat = $roundPriceWithVat - $discountPriceWithVat;
				$roundValue = $roundPrice - $discountPrice;

				$priceResult = array(
					'VALUE_NOVAT' => $calculatePrice,
					'PRINT_VALUE_NOVAT' => CCurrencyLang::CurrencyFormat($calculatePrice, $calculateCurrency, true),

					'VALUE_VAT' => $priceWithVat,
					'PRINT_VALUE_VAT' => CCurrencyLang::CurrencyFormat($priceWithVat, $calculateCurrency, true),

					'VATRATE_VALUE' => $vatValue,
					'PRINT_VATRATE_VALUE' => CCurrencyLang::CurrencyFormat($vatValue, $calculateCurrency, true),

					'DISCOUNT_VALUE_NOVAT' => $discountPrice,
					'PRINT_DISCOUNT_VALUE_NOVAT' => CCurrencyLang::CurrencyFormat($discountPrice, $calculateCurrency, true),

					'DISCOUNT_VALUE_VAT' => $discountPriceWithVat,
					'PRINT_DISCOUNT_VALUE_VAT' => CCurrencyLang::CurrencyFormat($discountPriceWithVat, $calculateCurrency, true),

					'DISCOUNT_VATRATE_VALUE' => $discountVatValue,
					'PRINT_DISCOUNT_VATRATE_VALUE' => CCurrencyLang::CurrencyFormat($discountVatValue, $calculateCurrency, true),

					'CURRENCY' => $calculateCurrency,

					'ROUND_VALUE_VAT' => $roundPriceWithVat,
					'ROUND_VALUE_NOVAT' => $roundPrice,
					'ROUND_VATRATE_VAT' => $roundValueWithVat,
					'ROUND_VATRATE_NOVAT' => $roundValue,
				);

				if ($cnangeCurrency)
				{
					$priceResult['ORIG_VALUE_NOVAT'] = $arItem[$catalogPriceValue];
					$priceResult['ORIG_VALUE_VAT'] = $originalPriceWithVat;
					$priceResult['ORIG_VATRATE_VALUE'] = $originalVatValue;
					$priceResult['ORIG_DISCOUNT_VALUE_NOVAT'] = $originalDiscountPrice;
					$priceResult['ORIG_DISCOUNT_VALUE_VAT'] = $originalDiscountPriceWithVat;
					$priceResult['ORIG_DISCOUNT_VATRATE_VALUE'] = $originalDiscountVatValue;
					$priceResult['ORIG_CURRENCY'] = $originalCurrency;
				}

				$priceResult['PRICE_ID'] = $value['ID'];
				$priceResult['ID'] = $arItem['CATALOG_PRICE_ID_'.$value['ID']];
				$priceResult['CAN_ACCESS'] = $arItem['CATALOG_CAN_ACCESS_'.$value['ID']];
				$priceResult['CAN_BUY'] = $arItem['CATALOG_CAN_BUY_'.$value['ID']];
				$priceResult['MIN_PRICE'] = 'N';

				if ($bVATInclude)
				{
					$priceResult['VALUE'] = $priceWithVat;
					$priceResult['PRINT_VALUE'] = $priceResult['PRINT_VALUE_VAT'];
					$priceResult['UNROUND_DISCOUNT_VALUE'] = $discountPriceWithVat;
					$priceResult['DISCOUNT_VALUE'] = $roundPriceWithVat;
					$priceResult['PRINT_DISCOUNT_VALUE'] = CCurrencyLang::CurrencyFormat(
						$roundPriceWithVat,
						$calculateCurrency,
						true
					);
				}
				else
				{
					$priceResult['VALUE'] = $calculatePrice;
					$priceResult['PRINT_VALUE'] = $priceResult['PRINT_VALUE_NOVAT'];
					$priceResult['UNROUND_DISCOUNT_VALUE'] = $discountPrice;
					$priceResult['DISCOUNT_VALUE'] = $roundPrice;
					$priceResult['PRINT_DISCOUNT_VALUE'] = CCurrencyLang::CurrencyFormat(
						$roundPrice,
						$calculateCurrency,
						true
					);;
				}

				if ((roundEx($priceResult['VALUE'], 2) - roundEx($priceResult['UNROUND_DISCOUNT_VALUE'], 2)) < 0.01)
				{
					$priceResult['VALUE'] = $priceResult['DISCOUNT_VALUE'];
					$priceResult['PRINT_VALUE'] = $priceResult['PRINT_DISCOUNT_VALUE'];
					$priceResult['DISCOUNT_DIFF'] = 0;
					$priceResult['DISCOUNT_DIFF_PERCENT'] = 0;
				}
				else
				{
					$priceResult['DISCOUNT_DIFF'] = $priceResult['VALUE'] - $priceResult['DISCOUNT_VALUE'];
					$priceResult['DISCOUNT_DIFF_PERCENT'] = roundEx(100*$priceResult['DISCOUNT_DIFF']/$priceResult['VALUE'], 0);
				}
				$priceResult['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat(
					$priceResult['DISCOUNT_DIFF'],
					$calculateCurrency,
					true
				);

				if ($boolStartMin)
				{
					$dblMinPrice = ($boolConvert || ($calculateCurrency == $strMinCurrency)
						? $priceResult['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($priceResult['DISCOUNT_VALUE'], $calculateCurrency, $strMinCurrency)
					);
					$strMinCode = $key;
					$boolStartMin = false;
				}
				else
				{
					$dblComparePrice = ($boolConvert || ($calculateCurrency == $strMinCurrency)
						? $priceResult['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($priceResult['DISCOUNT_VALUE'], $calculateCurrency, $strMinCurrency)
					);
					if ($dblMinPrice > $dblComparePrice)
					{
						$dblMinPrice = $dblComparePrice;
						$strMinCode = $key;
					}
				}
				unset($calculateCurrency);
				unset($originalCurrency);

				$arPrices[$key] = $priceResult;
				unset($priceResult);
			}
			if ($strMinCode != '')
				$arPrices[$strMinCode]['MIN_PRICE'] = 'Y';
			CCatalogDiscountSave::Enable();

			unset($percentPriceWithVat);
			unset($percentVat);
		}
		else
		{
			$strMinCode = '';
			$boolStartMin = true;
			$dblMinPrice = 0;
			foreach($arCatalogPrices as $key => $value)
			{
				if (!$value['CAN_VIEW'])
					continue;

				$dblValue = round(doubleval($arItem["PROPERTY_".$value["ID"]."_VALUE"]), 2);
				if ($boolStartMin)
				{
					$dblMinPrice = $dblValue;
					$strMinCode = $key;
					$boolStartMin = false;
				}
				else
				{
					if ($dblMinPrice > $dblValue)
					{
						$dblMinPrice = $dblValue;
						$strMinCode = $key;
					}
				}
				$arPrices[$key] = array(
					"ID" => $arItem["PROPERTY_".$value["ID"]."_VALUE_ID"],
					"VALUE" => $dblValue,
					"PRINT_VALUE" => $dblValue." ".$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
					"DISCOUNT_VALUE" => $dblValue,
					"PRINT_DISCOUNT_VALUE" => $dblValue." ".$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
					"CURRENCY" => $arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
					"CAN_ACCESS" => true,
					"CAN_BUY" => false,
					'DISCOUNT_DIFF_PERCENT' => 0,
					'DISCOUNT_DIFF' => 0,
					'PRINT_DISCOUNT_DIFF' => '0 '.$arItem["PROPERTY_".$value["ID"]."_DESCRIPTION"],
					"MIN_PRICE" => "N",
					'PRICE_ID' => $value['ID']
				);
			}
			if ($strMinCode != '')
				$arPrices[$strMinCode]['MIN_PRICE'] = 'Y';
		}
		return $arPrices;
	}

	/**
	 * @param int $IBLOCK_ID
	 * @param array $arCatalogPrices
	 * @param array $arItem
	 * @return bool
	 */
	
	/**
	* <p>Метод статический.</p>
	*
	*
	* @param int $IBLOCK_ID   
	*
	* @param array $arCatalogPrices   
	*
	* @param array $arItem   
	*
	* @return array <p></p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/canbuy.php
	* @author Bitrix
	*/
	public static function CanBuy(
		/** @noinspection PhpUnusedParameterInspection */$IBLOCK_ID,
		$arCatalogPrices,
		$arItem
	)
	{
		if (isset($arItem['CATALOG_AVAILABLE']) && 'N' == $arItem['CATALOG_AVAILABLE'])
			return false;

		if (!empty($arItem["PRICE_MATRIX"]) && is_array($arItem["PRICE_MATRIX"]))
		{
			return $arItem["PRICE_MATRIX"]["AVAILABLE"] == "Y";
		}
		else
		{
			if (empty($arCatalogPrices) || !is_array($arCatalogPrices))
				return false;

			foreach ($arCatalogPrices as $arPrice)
			{
				//if ($arPrice["CAN_BUY"] && isset($arItem["CATALOG_PRICE_".$arPrice["ID"]]) && $arItem["CATALOG_PRICE_".$arPrice["ID"]] !== null)
				if ($arPrice["CAN_BUY"] && isset($arItem["CATALOG_PRICE_".$arPrice["ID"]]))
					return true;
			}
		}
		return false;
	}

	public static function GetProductProperties(
		$IBLOCK_ID,
		/** @noinspection PhpUnusedParameterInspection */$ELEMENT_ID,
		$arPropertiesList,
		$arPropertiesValues
	)
	{
		static $cache = array();
		static $userTypeList = array();
		$propertyTypeSupport = array(
			'Y' => array(
				'N' => true,
				'S' => true,
				'L' => true,
				'G' => true,
				'E' => true
			),
			'N' => array(
				'L' => true,
				'E' => true
			)
		);

		$result = array();
		foreach ($arPropertiesList as $pid)
		{
			$prop = $arPropertiesValues[$pid];
			$prop['ID'] = (int)$prop['ID'];
			if (!isset($propertyTypeSupport[$prop['MULTIPLE']][$prop['PROPERTY_TYPE']]))
			{
				continue;
			}
			$emptyValues = true;
			$productProp = array('VALUES' => array(), 'SELECTED' => false, 'SET' => false);

			$userTypeProp = false;
			$userType = null;
			if (isset($prop['USER_TYPE']) && !empty($prop['USER_TYPE']))
			{
				if (!isset($userTypeList[$prop['USER_TYPE']]))
				{
					$userTypeDescr = CIBlockProperty::GetUserType($prop['USER_TYPE']);
					if (isset($userTypeDescr['GetPublicViewHTML']))
					{
						$userTypeList[$prop['USER_TYPE']] = $userTypeDescr['GetPublicViewHTML'];
					}
				}
				if (isset($userTypeList[$prop['USER_TYPE']]))
				{
					$userTypeProp = true;
					$userType = $userTypeList[$prop['USER_TYPE']];
				}
			}

			if ($prop["MULTIPLE"] == "Y" && !empty($prop["VALUE"]) && is_array($prop["VALUE"]))
			{
				if ($userTypeProp)
				{
					$countValues = 0;
					foreach($prop["VALUE"] as $value)
					{
						if (!is_scalar($value))
							continue;
						$value = (string)$value;
						$displayValue = (string)call_user_func_array($userType,
							array(
								$prop,
								array('VALUE' => $value),
								array(array('MODE' => 'SIMPLE_TEXT'))
							));
						if ('' !== $displayValue)
						{
							if ($productProp["SELECTED"] === false)
								$productProp["SELECTED"] = $value;
							$productProp["VALUES"][$value] = htmlspecialcharsbx($displayValue);
							$emptyValues = false;
							$countValues++;
						}
					}
					$productProp['SET'] = ($countValues === 1);
				}
				else
				{
					switch($prop["PROPERTY_TYPE"])
					{
					case "S":
					case "N":
						$countValues = 0;
						foreach($prop["VALUE"] as $value)
						{
							if (!is_scalar($value))
								continue;
							$value = (string)$value;
							if($value !== '')
							{
								if($productProp["SELECTED"] === false)
									$productProp["SELECTED"] = $value;
								$productProp["VALUES"][$value] = $value;
								$emptyValues = false;
								$countValues++;
							}
							$productProp['SET'] = ($countValues === 1);
						}
						break;
					case "G":
						$ar = array();
						foreach($prop["VALUE"] as $value)
						{
							$value = (int)$value;
							if($value > 0)
								$ar[] = $value;
						}
						if (!empty($ar))
						{
							$countValues = 0;
							$rsSections = CIBlockSection::GetList(
								array("LEFT_MARGIN"=>"ASC"),
								array("=ID" => $ar),
								false,
								array('ID', 'NAME')
							);
							while ($arSection = $rsSections->GetNext())
							{
								$arSection["ID"] = (int)$arSection["ID"];
								if ($productProp["SELECTED"] === false)
									$productProp["SELECTED"] = $arSection["ID"];
								$productProp["VALUES"][$arSection["ID"]] = $arSection["NAME"];
								$emptyValues = false;
								$countValues++;
							}
							$productProp['SET'] = ($countValues === 1);
						}
						break;
					case "E":
						$ar = array();
						foreach($prop["VALUE"] as $value)
						{
							$value = (int)$value;
							if($value > 0)
								$ar[] = $value;
						}
						if (!empty($ar))
						{
							$countValues = 0;
							$rsElements = CIBlockElement::GetList(
								array("ID" => "ASC"),
								array("=ID" => $ar),
								false,
								false,
								array("ID", "NAME")
							);
							while($arElement = $rsElements->GetNext())
							{
								$arElement['ID'] = (int)$arElement['ID'];
								if($productProp["SELECTED"] === false)
									$productProp["SELECTED"] = $arElement["ID"];
								$productProp["VALUES"][$arElement["ID"]] = $arElement["NAME"];
								$emptyValues = false;
								$countValues++;
							}
							$productProp['SET'] = ($countValues === 1);
						}
						break;
					case "L":
						$countValues = 0;
						foreach($prop["VALUE"] as $i => $value)
						{
							$prop["VALUE_ENUM_ID"][$i] = (int)$prop["VALUE_ENUM_ID"][$i];
							if($productProp["SELECTED"] === false)
								$productProp["SELECTED"] = $prop["VALUE_ENUM_ID"][$i];
							$productProp["VALUES"][$prop["VALUE_ENUM_ID"][$i]] = $value;
							$emptyValues = false;
							$countValues++;
						}
						$productProp['SET'] = ($countValues === 1);
						break;
					}
				}
			}
			elseif($prop["MULTIPLE"] == "N")
			{
				switch($prop["PROPERTY_TYPE"])
				{
				case "L":
					if (0 == (int)$prop["VALUE_ENUM_ID"])
					{
						if (isset($cache[$prop['ID']]))
						{
							$productProp = $cache[$prop['ID']];
							$emptyValues = false;
						}
						else
						{
							$rsEnum = CIBlockPropertyEnum::GetList(
								array("SORT"=>"ASC", "VALUE"=>"ASC"),
								array("IBLOCK_ID"=>$IBLOCK_ID, "PROPERTY_ID" => $prop['ID'])
							);
							while ($arEnum = $rsEnum->GetNext())
							{
								$arEnum["ID"] = (int)$arEnum["ID"];
								$productProp["VALUES"][$arEnum["ID"]] = $arEnum["VALUE"];
								if ($arEnum["DEF"] == "Y")
									$productProp["SELECTED"] = $arEnum["ID"];
								$emptyValues = false;
							}
							if (!$emptyValues)
							{
								$cache[$prop['ID']] = $productProp;
							}
						}
					}
					else
					{
						$prop['VALUE_ENUM_ID'] = (int)$prop['VALUE_ENUM_ID'];
						$productProp['VALUES'][$prop['VALUE_ENUM_ID']] = $prop['VALUE'];
						$productProp['SELECTED'] = $prop['VALUE_ENUM_ID'];
						$productProp['SET'] = true;
						$emptyValues = false;
					}
					break;
				case "E":
					if (0 == (int)$prop['VALUE'])
					{
						if (isset($cache[$prop['ID']]))
						{
							$productProp = $cache[$prop['ID']];
							$emptyValues = false;
						}
						else
						{
							if($prop["LINK_IBLOCK_ID"] > 0)
							{
								$rsElements = CIBlockElement::GetList(
									array("NAME"=>"ASC", "SORT"=>"ASC"),
									array("IBLOCK_ID"=>$prop["LINK_IBLOCK_ID"], "ACTIVE"=>"Y"),
									false, false,
									array("ID", "NAME")
								);
								while ($arElement = $rsElements->GetNext())
								{
									$arElement['ID'] = (int)$arElement['ID'];
									if($productProp["SELECTED"] === false)
										$productProp["SELECTED"] = $arElement["ID"];
									$productProp["VALUES"][$arElement["ID"]] = $arElement["NAME"];
									$emptyValues = false;
								}
								if (!$emptyValues)
								{
									$cache[$prop['ID']] = $productProp;
								}
							}
						}
					}
					else
					{
						$rsElements = CIBlockElement::GetList(
							array(),
							array('ID' => $prop["VALUE"], 'ACTIVE' => 'Y'),
							false,
							false,
							array('ID', 'NAME')
						);
						if ($arElement = $rsElements->GetNext())
						{
							$arElement['ID'] = (int)$arElement['ID'];
							$productProp['VALUES'][$arElement['ID']] = $arElement['NAME'];
							$productProp['SELECTED'] = $arElement['ID'];
							$productProp['SET'] = true;
							$emptyValues = false;
						}
					}
					break;
				}
			}

			if (!$emptyValues)
			{
				$result[$pid] = $productProp;
			}
		}

		return $result;
	}

	public static function getFillProductProperties($productProps)
	{
		$result = array();
		if (!empty($productProps) && is_array($productProps))
		{
			foreach ($productProps as $propID => $propInfo)
			{
				if (isset($propInfo['SET']) && $propInfo['SET'])
				{
					$result[$propID] = array(
						'ID' => $propInfo['SELECTED'],
						'VALUE' => $propInfo['VALUES'][$propInfo['SELECTED']]
					);
				}
			}
		}
		return $result;
	}

	/*
	Checks arPropertiesValues against DB values
	returns array on success
	or number on fail (may be used for debug)
	*/
	public static function CheckProductProperties($iblockID, $elementID, $propertiesList, $propertiesValues, $enablePartialList = false)
	{
		$propertyTypeSupport = array(
			'Y' => array(
				'N' => true,
				'S' => true,
				'L' => true,
				'G' => true,
				'E' => true
			),
			'N' => array(
				'L' => true,
				'E' => true
			)
		);
		$iblockID = (int)$iblockID;
		$elementID = (int)$elementID;
		if (0 >= $iblockID || 0 >= $elementID)
			return 6;
		$enablePartialList = (true === $enablePartialList);
		$sortIndex = 1;
		$result = array();
		if (!is_array($propertiesList))
			$propertiesList = array();
		if (empty($propertiesList))
			return $result;
		$checkProps = array_fill_keys($propertiesList, true);
		$propCodes = $checkProps;
		$existProps =  array();
		$rsProps = CIBlockElement::GetProperty($iblockID, $elementID, 'sort', 'asc', array());
		while ($oneProp = $rsProps->Fetch())
		{
			if (!isset($propCodes[$oneProp['CODE']]) && !isset($propCodes[$oneProp['ID']]))
				continue;
			$propID = (isset($propCodes[$oneProp['CODE']]) ? $oneProp['CODE'] : $oneProp['ID']);
			if (!isset($checkProps[$propID]))
				continue;

			if (!isset($propertyTypeSupport[$oneProp['MULTIPLE']][$oneProp['PROPERTY_TYPE']]))
			{
				return ($oneProp['MULTIPLE'] == 'Y' ? 2 : 3);
			}

			if (null !== $oneProp['VALUE'])
			{
				$existProps[$propID] = true;
			}

			if (!isset($propertiesValues[$propID]))
			{
				if ($enablePartialList)
				{
					continue;
				}
				return 1;
			}

			if (!is_scalar($propertiesValues[$propID]))
					return 5;

			$propertiesValues[$propID] = (string)$propertiesValues[$propID];
			$existValue = ('' != $propertiesValues[$propID]);
			if (!$existValue)
				return 1;

			$userTypeProp = false;
			$userType = null;
			if (isset($oneProp['USER_TYPE']) && !empty($oneProp['USER_TYPE']))
			{
				$userTypeDescr = CIBlockProperty::GetUserType($oneProp['USER_TYPE']);
				if (isset($userTypeDescr['GetPublicViewHTML']))
				{
					$userTypeProp = true;
					$userType = $userTypeDescr['GetPublicViewHTML'];
				}
			}

			if ($oneProp["MULTIPLE"] == "Y")
			{
				if ($userTypeProp)
				{
					if ($oneProp["VALUE"] == $propertiesValues[$propID])
					{
						$displayValue = (string)call_user_func_array($userType,
							array(
								$oneProp,
								array('VALUE' => $oneProp['VALUE']),
								array('MODE' => 'SIMPLE_TEXT')
							));
						$result[] = array(
							"NAME" => $oneProp["NAME"],
							"CODE" => $propID,
							"VALUE" => $displayValue,
							"SORT" => $sortIndex++,
						);
						unset($checkProps[$propID]);//mark as found
					}
				}
				else
				{
					switch($oneProp["PROPERTY_TYPE"])
					{
					case "S":
					case "N":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$result[] = array(
								"NAME" => $oneProp["NAME"],
								"CODE" => $propID,
								"VALUE" => $oneProp["VALUE"],
								"SORT" => $sortIndex++,
							);
							unset($checkProps[$propID]);//mark as found
						}
						break;
					case "G":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$rsSection = CIBlockSection::GetList(
								array(),
								array("=ID" => $oneProp["VALUE"]),
								false,
								array('ID', 'NAME')
							);
							if($arSection = $rsSection->Fetch())
							{
								$result[] = array(
									"NAME" => $oneProp["NAME"],
									"CODE" => $propID,
									"VALUE" => $arSection["NAME"],
									"SORT" => $sortIndex++,
								);
								unset($checkProps[$propID]);//mark as found
							}
						}
						break;
					case "E":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$rsElement = CIBlockElement::GetList(
								array(),
								array("=ID" => $oneProp["VALUE"]),
								false,
								false,
								array("ID", "NAME")
							);
							if ($arElement = $rsElement->Fetch())
							{
								$result[] = array(
									"NAME" => $oneProp["NAME"],
									"CODE" => $propID,
									"VALUE" => $arElement["NAME"],
									"SORT" => $sortIndex++,
								);
								unset($checkProps[$propID]);//mark as found
							}
						}
						break;
					case "L":
						if ($oneProp["VALUE"] == $propertiesValues[$propID])
						{
							$rsEnum = CIBlockPropertyEnum::GetList(
								array(),
								array( "ID" => $propertiesValues[$propID], "IBLOCK_ID" => $iblockID, "PROPERTY_ID" => $oneProp['ID'])
							);
							if ($arEnum = $rsEnum->Fetch())
							{
								$result[] = array(
									"NAME" => $oneProp["NAME"],
									"CODE" => $propID,
									"VALUE" => $arEnum["VALUE"],
									"SORT" => $sortIndex++,
								);
								unset($checkProps[$propID]);//mark as found
							}
						}
						break;
					}
				}
			}
			else
			{
				switch ($oneProp["PROPERTY_TYPE"])
				{
				case "L":
					if (0 < (int)$propertiesValues[$propID])
					{
						$rsEnum = CIBlockPropertyEnum::GetList(
							array(),
							array("ID" => $propertiesValues[$propID], "IBLOCK_ID" => $iblockID, "PROPERTY_ID" => $oneProp['ID'])
						);
						if ($arEnum = $rsEnum->Fetch())
						{
							$result[] = array(
								"NAME" => $oneProp["NAME"],
								"CODE" => $propID,
								"VALUE" => $arEnum["VALUE"],
								"SORT" => $sortIndex++,
							);
							unset($checkProps[$propID]);//mark as found
						}
					}
					break;
				case "E":
					if (0 < (int)$propertiesValues[$propID])
					{
						$rsElement = CIBlockElement::GetList(
							array(),
							array("=ID" => $propertiesValues[$propID]),
							false,
							false,
							array("ID", "NAME")
						);
						if ($arElement = $rsElement->Fetch())
						{
							$result[] = array(
								"NAME" => $oneProp["NAME"],
								"CODE" => $propID,
								"VALUE" => $arElement["NAME"],
								"SORT" => $sortIndex++,
							);
							unset($checkProps[$propID]);//mark as found
						}
					}
					break;
				}
			}
		}

		if ($enablePartialList && !empty($checkProps))
		{
			$nonExistProps = array_keys($checkProps);
			foreach ($nonExistProps as &$oneCode)
			{
				if (!isset($existProps[$oneCode]))
					unset($checkProps[$oneCode]);
			}
			unset($oneCode);
		}

		if(!empty($checkProps))
			return 4;

		return $result;
	}

	public static function GetOffersIBlock($IBLOCK_ID)
	{
		$arResult = false;
		$IBLOCK_ID = (int)$IBLOCK_ID;
		if (0 < $IBLOCK_ID)
		{
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Loader::includeModule('catalog');
			if (self::$catalogIncluded)
			{
				$arCatalog = CCatalogSKU::GetInfoByProductIBlock($IBLOCK_ID);
				if (!empty($arCatalog) && is_array($arCatalog))
				{
					$arResult = array(
						'OFFERS_IBLOCK_ID' => $arCatalog['IBLOCK_ID'],
						'OFFERS_PROPERTY_ID' => $arCatalog['SKU_PROPERTY_ID'],
					);
				}
			}
		}
		return $arResult;
	}

	public static function GetOfferProperties($offerID, $iblockID, $propertiesList, $skuTreeProps = '')
	{
		$iblockInfo = false;
		$result = array();

		$iblockID = (int)$iblockID;
		$offerID = (int)$offerID;
		if (0 >= $iblockID || 0 >= $offerID)
			return $result;

		$skuPropsList = array();
		if (!empty($skuTreeProps))
		{
			if (is_array($skuTreeProps))
			{
				$skuPropsList = $skuTreeProps;
			}
			else
			{
				$skuTreeProps = base64_decode((string)$skuTreeProps);
				if (false !== $skuTreeProps && CheckSerializedData($skuTreeProps))
				{
					$skuPropsList = unserialize($skuTreeProps);
					if (!is_array($skuPropsList))
					{
						$skuPropsList = array();
					}
				}
			}
		}

		if (!is_array($propertiesList))
		{
			$propertiesList = array();
		}
		if (!empty($skuPropsList))
		{
			$propertiesList = array_unique(array_merge($propertiesList, $skuPropsList));
		}
		if (empty($propertiesList))
			return $result;
		$propCodes = array_fill_keys($propertiesList, true);

		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Loader::includeModule('catalog');
		if (self::$catalogIncluded)
		{
			$iblockInfo = CCatalogSKU::GetInfoByProductIBlock($iblockID);
		}
		if (empty($iblockInfo))
			return $result;

		$sortIndex = 1;
		$rsProps = CIBlockElement::GetProperty(
			$iblockInfo['IBLOCK_ID'],
			$offerID,
			array("sort"=>"asc", "enum_sort" => "asc", "value_id"=>"asc"),
			array("EMPTY"=>"N")
		);

		while ($oneProp = $rsProps->Fetch())
		{
			if (!isset($propCodes[$oneProp['CODE']]) && !isset($propCodes[$oneProp['ID']]))
				continue;
			$propID = (isset($propCodes[$oneProp['CODE']]) ? $oneProp['CODE'] : $oneProp['ID']);

			$userTypeProp = false;
			$userType = null;
			if (isset($oneProp['USER_TYPE']) && !empty($oneProp['USER_TYPE']))
			{
				$userTypeDescr = CIBlockProperty::GetUserType($oneProp['USER_TYPE']);
				if (isset($userTypeDescr['GetPublicViewHTML']))
				{
					$userTypeProp = true;
					$userType = $userTypeDescr['GetPublicViewHTML'];
				}
			}

			if ($userTypeProp)
			{
				$displayValue = (string)call_user_func_array($userType,
					array(
						$oneProp,
						array('VALUE' => $oneProp['VALUE']),
						array('MODE' => 'SIMPLE_TEXT')
					));
				$result[] = array(
					"NAME" => $oneProp["NAME"],
					"CODE" => $propID,
					"VALUE" => $displayValue,
					"SORT" => $sortIndex++,
				);
			}
			else
			{
				switch ($oneProp["PROPERTY_TYPE"])
				{
				case "S":
				case "N":
					$result[] = array(
						"NAME" => $oneProp["NAME"],
						"CODE" => $propID,
						"VALUE" => $oneProp["VALUE"],
						"SORT" => $sortIndex++,
					);
					break;
				case "G":
					$rsSection = CIBlockSection::GetList(
						array(),
						array("=ID"=>$oneProp["VALUE"]),
						false,
						array('ID', 'NAME')
					);
					if ($arSection = $rsSection->Fetch())
					{
						$result[] = array(
							"NAME" => $oneProp["NAME"],
							"CODE" => $propID,
							"VALUE" => $arSection["NAME"],
							"SORT" => $sortIndex++,
						);
					}
					break;
				case "E":
					$rsElement = CIBlockElement::GetList(
						array(),
						array("=ID"=>$oneProp["VALUE"]),
						false,
						false,
						array("ID", "NAME")
					);
					if ($arElement = $rsElement->Fetch())
					{
						$result[] = array(
							"NAME" => $oneProp["NAME"],
							"CODE" => $propID,
							"VALUE" => $arElement["NAME"],
							"SORT" => $sortIndex++,
						);
					}
					break;
				case "L":
					$result[] = array(
						"NAME" => $oneProp["NAME"],
						"CODE" => $propID,
						"VALUE" => $oneProp["VALUE_ENUM"],
						"SORT" => $sortIndex++,
					);
					break;
				}
			}
		}
		return $result;
	}

	
	/**
	* <p>Метод возвращает массив торговых предложений для одного или нескольких товаров одного информационного блока. Метод статический.</p>
	*
	*
	* @param mixed $arFilter  Целое число - идентификатор инфоблока или ассоциативный массив с
	* ключами: <ul> <li>IBLOCK_ID - идентификатор инфоблока;</li> <li>HIDE_NOT_AVAILABLE -
	* флаг "Скрывать предложения, отсутствующие на складе" (Y/N);</li>
	* <li>CHECK_PERMISSIONS - флаг проверки прав доступа к инфоблоку (Y/N).</li> </ul> До
	* версии <b>12.5.4</b> мог задаваться только код инфоблока.
	*
	* @param array $arElementID  Массив элементов инфоблока. Пустой массив array() означает, что
	* будут возвращены все торговые предложения.
	*
	* @param array $arOrder  Ассоциативный массив для сортировки результирующего набора
	* торговых предложений. Набор сортируется последовательно по
	* каждой паре ключ-значение массива. Ключами массива являются
	* названия параметров торгового предложения, по значениям которых
	* осуществляется сортировка. Значениями являются направления
	* сортировки.  <br><br> Допустимые ключи и значения аналогичны ключам и
	* значениям массива <b>arOrder</b> метода <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php">CIBlockElement::GetList</a>.
	*
	* @param array $arSelectFields  Массив полей торговых предложений, которые должны быть
	* возвращены методом. Допустимые ключи - все <a
	* href="http://dev.1c-bitrix.ru/api_help/iblock/fields.php#felement">поля элементов</a> инфоблока
	* торговых предложений.<br><br> Пустой массив array() означает, что будут
	* возвращены следующие поля: <ul> <li> <b>ID</b> - код торгового
	* предложения;</li> <li> <b>IBLOCK_ID</b> - код инфоблока торгового
	* предложения;</li> <li> <b>PROPERTY_<i>код_свойства</i></b> - значение свойства
	* привязки торгового предложения (фактически - идентификатор
	* товара);</li> <li> <b>CATALOG_<i>XXX</i></b> (где <i>XXX</i> - это QUANTITY, QUANTITY_TRACE,
	* QUANTITY_TRACE_ORIG, CAN_BUY_ZERO и т.д.) - все поля класса <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/index.php">CCatalogProduct</a> для
	* торгового предложения.</li> </ul>
	*
	* @param array $arSelectProperties  Массив символьных или цифровых кодов тех свойств торговых
	* предложений, которые должны быть возвращены методом. Если массив
	* задан непустым, то в ключе <b>PROPERTIES</b> будут возвращены значения
	* всех свойств торгового предложения, а в ключе <b>DISPLAY_PROPERTIES</b> -
	* непустые значения свойств, отформатированные для показа в
	* публичных компонентах и перечисленные в этом массиве.
	*
	* @param int $limit  Максимальное число предложений для одного товара. Если задано
	* значение <b>0</b>, то будут возвращены все торговые предложения для
	* указанных товаров.
	*
	* @param array $arPrices  Массив типов цен, возвращенный методом <b>CIBlockPriceTools::GetCatalogPrices</b>.
	* Для типов цен считаются скидки, минимальная цена и т.п.
	*
	* @param bool $vat_include  Признак включения НДС в цену при показе, если он еще не включен.
	*
	* @param array $arCurrencyParams = array() Массив параметров для показа цен в одной валюте. Если в
	* переданном массиве заполнено поле <i>CURRENCY_ID</i>, то произойдет
	* конвертация цен в валюту <i>CURRENCY_ID</i> по текущему курсу.
	* Необязательный параметр.
	*
	* @param int $USER_ID = 0 Идентификатор пользователя. Значение непусто, если расчеты
	* проводятся не для текущего пользователя. Необязательный
	* параметр.
	*
	* @param string $LID = SITE_ID Идентификатор сайта. Значение непусто, если расчеты проводятся
	* не для текущего сайта. Необязательный параметр.
	*
	* @return array <p>Ассоциативный массив торговых предложений, включающий в себя
	* все запрошенные в методе поля, поля класса <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogproduct/index.php">CCatalogProduct</a>, а также
	* следующие ключи:</p><ul> <li> <b>PRICES</b> - цены, которые вернет
	* <b>CIBlockPriceTools::GetItemPrice</b>;</li> <li> <b>MIN_PRICE</b> - массив, описывающий
	* минимальную цену;</li> <li> <b>CAN_BUY</b> - доступность к покупке (true/false);</li>
	* <li> <b>LINK_ELEMENT_ID</b> - код товара для предложения;</li> <li> <b>PROPERTIES</b> -
	* неотформатированные значения всех свойств элемента инфоблока, в
	* т.ч. пустые (массив непуст, если запросили хоть одно свойство в
	* <b>$arSelectProperties</b>);</li> <li> <b>DISPLAY_PROPERTIES</b> - только те свойства, что
	* непусты (из перечня <b>$arSelectProperties</b>);</li> <li> <b>CATALOG_MEASURE_NAME</b> -
	* название единицы измерения (сокращенное);</li> <li> <b>CATALOG_MEASURE</b> - код
	* единицы измерения;</li> <li> <b>CATALOG_RATIO</b> - коэффициент единицы
	* измерения.</li> </ul><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockpricetools/getoffersarray.php
	* @author Bitrix
	*/
	public static function GetOffersArray($arFilter, $arElementID, $arOrder, $arSelectFields, $arSelectProperties, $limit, $arPrices, $vat_include, $arCurrencyParams = array(), $USER_ID = 0, $LID = SITE_ID)
	{
		global $USER;

		$arResult = array();

		$boolCheckPermissions = false;
		$boolHideNotAvailable = false;
		$showPriceCount = false;
		$IBLOCK_ID = 0;
		if (!empty($arFilter) && is_array($arFilter))
		{
			if (isset($arFilter['IBLOCK_ID']))
				$IBLOCK_ID = $arFilter['IBLOCK_ID'];
			if (isset($arFilter['HIDE_NOT_AVAILABLE']))
				$boolHideNotAvailable = ($arFilter['HIDE_NOT_AVAILABLE'] === 'Y');
			if (isset($arFilter['CHECK_PERMISSIONS']))
				$boolCheckPermissions = ($arFilter['CHECK_PERMISSIONS'] === 'Y');
			if (isset($arFilter['SHOW_PRICE_COUNT']))
			{
				$showPriceCount = (int)$arFilter['SHOW_PRICE_COUNT'];
				if ($showPriceCount <= 0)
					$showPriceCount = false;
			}
		}
		else
		{
			$IBLOCK_ID = $arFilter;
		}

		if (self::$needDiscountCache === null)
		{
			$pricesAllow = CIBlockPriceTools::GetAllowCatalogPrices($arPrices);
			if (empty($pricesAllow))
			{
				self::$needDiscountCache = false;
			}
			else
			{
				$USER_ID = (int)$USER_ID;
				$userGroups = array(2);
				if ($USER_ID > 0)
					$userGroups = CUser::GetUserGroup($USER_ID);
				elseif (isset($USER) && $USER instanceof CUser)
					$userGroups = $USER->GetUserGroupArray();
				self::$needDiscountCache = CIBlockPriceTools::SetCatalogDiscountCache($pricesAllow, $userGroups);
				unset($userGroups);
			}
			unset($pricesAllow);
		}

		$arOffersIBlock = CIBlockPriceTools::GetOffersIBlock($IBLOCK_ID);
		if($arOffersIBlock)
		{
			$arDefaultMeasure = CCatalogMeasure::getDefaultMeasure(true, true);

			$limit = (int)$limit;
			if (0 > $limit)
				$limit = 0;

			if (!isset($arOrder["ID"]))
				$arOrder["ID"] = "DESC";

			$intOfferIBlockID = $arOffersIBlock["OFFERS_IBLOCK_ID"];

			$productProperty = 'PROPERTY_'.$arOffersIBlock['OFFERS_PROPERTY_ID'];
			$productPropertyValue = $productProperty.'_VALUE';

			$propertyList = array();
			if (!empty($arSelectProperties))
			{
				$selectProperties = array_fill_keys($arSelectProperties, true);
				$propertyIterator = Iblock\PropertyTable::getList(array(
					'select' => array('ID', 'CODE'),
					'filter' => array('=IBLOCK_ID' => $intOfferIBlockID, '=ACTIVE' => 'Y'),
					'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
				));
				while ($property = $propertyIterator->fetch())
				{
					$code = (string)$property['CODE'];
					if ($code == '')
						$code = $property['ID'];
					if (!isset($selectProperties[$code]))
						continue;
					$propertyList[] = $code;
					unset($code);
				}
				unset($property, $propertyIterator);
				unset($selectProperties);
			}

			$arFilter = array(
				"IBLOCK_ID" => $intOfferIBlockID,
				$productProperty => $arElementID,
				"ACTIVE" => "Y",
				"ACTIVE_DATE" => "Y",
			);
			if ($boolHideNotAvailable)
				$arFilter['CATALOG_AVAILABLE'] = 'Y';
			if ($boolCheckPermissions)
			{
				$arFilter['CHECK_PERMISSIONS'] = "Y";
				$arFilter['MIN_PERMISSION'] = "R";
			}

			$arSelect = array(
				"ID" => 1,
				"IBLOCK_ID" => 1,
				$productProperty => 1,
				"CATALOG_QUANTITY" => 1
			);
			//if(!$arParams["USE_PRICE_COUNT"])
			{
				foreach($arPrices as $value)
				{
					if (!$value['CAN_VIEW'] && !$value['CAN_BUY'])
						continue;
					$arSelect[$value["SELECT"]] = 1;
					if ($showPriceCount !== false)
					{
						$arFilter['CATALOG_SHOP_QUANTITY_'.$value['ID']] = $showPriceCount;
					}
				}
			}

			if (!empty($arSelectFields))
			{
				foreach ($arSelectFields as &$code)
					$arSelect[$code] = 1; //mark to select
				unset($code);
			}
			$checkFields = array();
			foreach (array_keys($arOrder) as $code)
			{
				$code = strtoupper($code);
				$arSelect[$code] = 1;
				if ($code == 'ID' || $code == 'CATALOG_AVAILABLE')
					continue;
				$checkFields[] = $code;
			}
			unset($code);

			if (!isset($arSelect['PREVIEW_PICTURE']))
				$arSelect['PREVIEW_PICTURE'] = 1;
			if (!isset($arSelect['DETAIL_PICTURE']))
				$arSelect['DETAIL_PICTURE'] = 1;

			$arOfferIDs = array();
			$arMeasureMap = array();
			$intKey = 0;
			$arOffersPerElement = array();
			$arOffersLink = array();
			$extPrices = array();
			$rsOffers = CIBlockElement::GetList($arOrder, $arFilter, false, false, array_keys($arSelect));
			while($arOffer = $rsOffers->GetNext())
			{
				$arOffer['ID'] = (int)$arOffer['ID'];
				$element_id = (int)$arOffer[$productPropertyValue];
				//No more than limit offers per element
				if($limit > 0)
				{
					$arOffersPerElement[$element_id]++;
					if($arOffersPerElement[$element_id] > $limit)
						continue;
				}

				if($element_id > 0)
				{
					$arOffer['SORT_HASH'] = 'ID';
					if (!empty($checkFields))
					{
						$checkValues = '';
						foreach ($checkFields as &$code)
							$checkValues .= (isset($arOffer[$code]) ? $arOffer[$code] : '').'|';
						unset($code);
						if ($checkValues != '')
							$arOffer['SORT_HASH'] = md5($checkValues);
						unset($checkValues);
					}
					$arOffer["LINK_ELEMENT_ID"] = $element_id;
					$arOffer["PROPERTIES"] = array();
					$arOffer["DISPLAY_PROPERTIES"] = array();

					Iblock\Component\Tools::getFieldImageData(
						$arOffer,
						array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
						Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
						''
					);

					$arOffer['CHECK_QUANTITY'] = ('Y' == $arOffer['CATALOG_QUANTITY_TRACE'] && 'N' == $arOffer['CATALOG_CAN_BUY_ZERO']);
					$arOffer['CATALOG_TYPE'] = CCatalogProduct::TYPE_OFFER;
					$arOffer['CATALOG_MEASURE_NAME'] = $arDefaultMeasure['SYMBOL_RUS'];
					$arOffer['~CATALOG_MEASURE_NAME'] = $arDefaultMeasure['SYMBOL_RUS'];
					$arOffer["CATALOG_MEASURE_RATIO"] = 1;
					if (!isset($arOffer['CATALOG_MEASURE']))
						$arOffer['CATALOG_MEASURE'] = 0;
					$arOffer['CATALOG_MEASURE'] = (int)$arOffer['CATALOG_MEASURE'];
					if (0 > $arOffer['CATALOG_MEASURE'])
						$arOffer['CATALOG_MEASURE'] = 0;
					if (0 < $arOffer['CATALOG_MEASURE'])
					{
						if (!isset($arMeasureMap[$arOffer['CATALOG_MEASURE']]))
							$arMeasureMap[$arOffer['CATALOG_MEASURE']] = array();
						$arMeasureMap[$arOffer['CATALOG_MEASURE']][] = $intKey;
					}

					$arOfferIDs[] = $arOffer['ID'];
					$arResult[$intKey] = $arOffer;
					if (!isset($arOffersLink[$arOffer['ID']]))
					{
						$arOffersLink[$arOffer['ID']] = &$arResult[$intKey];
					}
					else
					{
						if (!isset($extPrices[$arOffer['ID']]))
						{
							$extPrices[$arOffer['ID']] = array();
						}
						$extPrices[$arOffer['ID']][] = &$arResult[$intKey];
					}
					$intKey++;
				}
			}
			if (!empty($arOfferIDs))
			{
				$rsRatios = CCatalogMeasureRatio::getList(
					array(),
					array('@PRODUCT_ID' => $arOfferIDs),
					false,
					false,
					array('PRODUCT_ID', 'RATIO')
				);
				while ($arRatio = $rsRatios->Fetch())
				{
					$arRatio['PRODUCT_ID'] = (int)$arRatio['PRODUCT_ID'];
					if (isset($arOffersLink[$arRatio['PRODUCT_ID']]))
					{
						$intRatio = (int)$arRatio['RATIO'];
						$dblRatio = (float)$arRatio['RATIO'];
						$mxRatio = ($dblRatio > $intRatio ? $dblRatio : $intRatio);
						if (CATALOG_VALUE_EPSILON > abs($mxRatio))
							$mxRatio = 1;
						elseif (0 > $mxRatio)
							$mxRatio = 1;
						$arOffersLink[$arRatio['PRODUCT_ID']]['CATALOG_MEASURE_RATIO'] = $mxRatio;
					}
				}

				if (!empty($propertyList))
				{
					CIBlockElement::GetPropertyValuesArray($arOffersLink, $intOfferIBlockID, $arFilter);
					foreach ($arResult as &$arOffer)
					{
						if (self::$needDiscountCache)
							CCatalogDiscount::SetProductPropertiesCache($arOffer['ID'], $arOffer["PROPERTIES"]);

						foreach ($propertyList as &$pid)
						{
							if (!isset($arOffer["PROPERTIES"][$pid]))
								continue;
							$prop = &$arOffer["PROPERTIES"][$pid];
							$boolArr = is_array($prop["VALUE"]);
							if(
								($boolArr && !empty($prop["VALUE"])) ||
								(!$boolArr && strlen($prop["VALUE"])>0))
							{
								$arOffer["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arOffer, $prop, "catalog_out");
							}
							unset($prop);
						}
						unset($pid);
					}
					unset($arOffer);
				}

				if (!empty($extPrices))
				{
					foreach ($extPrices as $origID => $prices)
					{
						foreach ($prices as $oneRow)
						{
							$oneRow['PROPERTIES'] = $arOffersLink[$origID]['PROPERTIES'];
							$oneRow['DISPLAY_PROPERTIES'] = $arOffersLink[$origID]['DISPLAY_PROPERTIES'];
							$oneRow['CATALOG_MEASURE_RATIO'] = $arOffersLink[$origID]['CATALOG_MEASURE_RATIO'];
						}
					}
				}
				if (self::$needDiscountCache)
				{
					CCatalogDiscount::SetProductSectionsCache($arOfferIDs);
					CCatalogDiscount::SetDiscountProductCache($arOfferIDs, array('IBLOCK_ID' => $intOfferIBlockID, 'GET_BY_ID' => 'Y'));
				}
				foreach ($arResult as &$arOffer)
				{
					$arOffer['CATALOG_QUANTITY'] = (
						0 < $arOffer['CATALOG_QUANTITY'] && is_float($arOffer['CATALOG_MEASURE_RATIO'])
						? (float)$arOffer['CATALOG_QUANTITY']
						: (int)$arOffer['CATALOG_QUANTITY']
					);
					$arOffer['MIN_PRICE'] = false;
					$arOffer["PRICES"] = CIBlockPriceTools::GetItemPrices($arOffersIBlock["OFFERS_IBLOCK_ID"], $arPrices, $arOffer, $vat_include, $arCurrencyParams, $USER_ID, $LID);
					if (!empty($arOffer["PRICES"]))
					{
						foreach ($arOffer['PRICES'] as &$arOnePrice)
						{
							if ($arOnePrice['MIN_PRICE'] == 'Y')
							{
								$arOffer['MIN_PRICE'] = $arOnePrice;
								break;
							}
						}
						unset($arOnePrice);
					}
					$arOffer["CAN_BUY"] = CIBlockPriceTools::CanBuy($arOffersIBlock["OFFERS_IBLOCK_ID"], $arPrices, $arOffer);
				}
				if (isset($arOffer))
					unset($arOffer);
			}
			if (!empty($arMeasureMap))
			{
				$rsMeasures = CCatalogMeasure::getList(
					array(),
					array('@ID' => array_keys($arMeasureMap)),
					false,
					false,
					array('ID', 'SYMBOL_RUS')
				);
				while ($arMeasure = $rsMeasures->GetNext())
				{
					$arMeasure['ID'] = (int)$arMeasure['ID'];
					if (isset($arMeasureMap[$arMeasure['ID']]) && !empty($arMeasureMap[$arMeasure['ID']]))
					{
						foreach ($arMeasureMap[$arMeasure['ID']] as &$intOneKey)
						{
							$arResult[$intOneKey]['CATALOG_MEASURE_NAME'] = $arMeasure['SYMBOL_RUS'];
							$arResult[$intOneKey]['~CATALOG_MEASURE_NAME'] = $arMeasure['~SYMBOL_RUS'];
						}
						unset($intOneKey);
					}
				}
			}
		}

		return $arResult;
	}

	/**
	 * @deprecated since 14.5.0
	 * @see CCatalogMeasure::getDefaultMeasure
	 *
	 * @return array|null
	 * @throws Main\LoaderException
	 */
	public static function GetDefaultMeasure()
	{
		if (self::$catalogIncluded === null)
			self::$catalogIncluded = Loader::includeModule('catalog');
		return (self::$catalogIncluded ? array() : CCatalogMeasure::getDefaultMeasure(true, true));
	}

	public static function setRatioMinPrice(&$item, $replaceMinPrice = false)
	{
		$replaceMinPrice = ($replaceMinPrice !== false);
		if (isset($item['MIN_PRICE']) && !empty($item['MIN_PRICE']) && isset($item['CATALOG_MEASURE_RATIO']))
		{
			if ($item['CATALOG_MEASURE_RATIO'] === 1)
			{
				$item['RATIO_PRICE'] = array(
					'VALUE' => $item['MIN_PRICE']['VALUE'],
					'DISCOUNT_VALUE' => $item['MIN_PRICE']['DISCOUNT_VALUE'],
					'PRINT_VALUE' => $item['MIN_PRICE']['PRINT_VALUE'],
					'PRINT_DISCOUNT_VALUE' => $item['MIN_PRICE']['PRINT_DISCOUNT_VALUE'],
					'DISCOUNT_DIFF' => $item['MIN_PRICE']['DISCOUNT_DIFF'],
					'PRINT_DISCOUNT_DIFF' => $item['MIN_PRICE']['PRINT_DISCOUNT_DIFF'],
					'DISCOUNT_DIFF_PERCENT' => $item['MIN_PRICE']['DISCOUNT_DIFF_PERCENT'],
					'CURRENCY' => $item['MIN_PRICE']['CURRENCY']
				);
			}
			else
			{
				$item['RATIO_PRICE'] = array(
					'VALUE' => $item['MIN_PRICE']['VALUE']*$item['CATALOG_MEASURE_RATIO'],
					'DISCOUNT_VALUE' => $item['MIN_PRICE']['DISCOUNT_VALUE']*$item['CATALOG_MEASURE_RATIO'],
					'CURRENCY' => $item['MIN_PRICE']['CURRENCY']
				);
				$item['RATIO_PRICE']['PRINT_VALUE'] = CCurrencyLang::CurrencyFormat(
					$item['RATIO_PRICE']['VALUE'],
					$item['RATIO_PRICE']['CURRENCY'],
					true
				);
				$item['RATIO_PRICE']['PRINT_DISCOUNT_VALUE'] = CCurrencyLang::CurrencyFormat(
					$item['RATIO_PRICE']['DISCOUNT_VALUE'],
					$item['RATIO_PRICE']['CURRENCY'],
					true
				);
				if ($item['MIN_PRICE']['VALUE'] == $item['MIN_PRICE']['DISCOUNT_VALUE'])
				{
					$item['RATIO_PRICE']['DISCOUNT_DIFF'] = 0;
					$item['RATIO_PRICE']['DISCOUNT_DIFF_PERCENT'] = 0;
					$item['RATIO_PRICE']['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat(0, $item['RATIO_PRICE']['CURRENCY'], true);
				}
				else
				{
					$item['RATIO_PRICE']['DISCOUNT_DIFF'] = $item['RATIO_PRICE']['VALUE'] - $item['RATIO_PRICE']['DISCOUNT_VALUE'];
					$item['RATIO_PRICE']['DISCOUNT_DIFF_PERCENT'] = roundEx(100*$item['RATIO_PRICE']['DISCOUNT_DIFF']/$item['RATIO_PRICE']['VALUE'], 0);
					$item['RATIO_PRICE']['PRINT_DISCOUNT_DIFF'] = CCurrencyLang::CurrencyFormat(
						$item['RATIO_PRICE']['DISCOUNT_DIFF'],
						$item['RATIO_PRICE']['CURRENCY'],
						true
					);
				}
			}
			if ($replaceMinPrice)
			{
				$item['MIN_PRICE'] = $item['RATIO_PRICE'];
				unset($item['RATIO_PRICE']);
			}
		}
	}

	public static function checkPropDirectory(&$property, $getPropInfo = false)
	{
		if (empty($property) || !is_array($property))
			return false;
		if (!isset($property['USER_TYPE_SETTINGS']['TABLE_NAME']) || empty($property['USER_TYPE_SETTINGS']['TABLE_NAME']))
			return false;
		if (self::$highLoadInclude === null)
			self::$highLoadInclude = Loader::includeModule('highloadblock');
		if (!self::$highLoadInclude)
			return false;

		$highBlock = HighloadBlockTable::getList(array(
			'filter' => array('=TABLE_NAME' => $property['USER_TYPE_SETTINGS']['TABLE_NAME'])
		))->fetch();
		if (!isset($highBlock['ID']))
			return false;

		$entity = HighloadBlockTable::compileEntity($highBlock);
		$fieldsList = $entity->getFields();
		if (empty($fieldsList))
			return false;

		$requireFields = array(
			'ID',
			'UF_XML_ID',
			'UF_NAME',
		);
		foreach ($requireFields as &$fieldCode)
		{
			if (!isset($fieldsList[$fieldCode]) || empty($fieldsList[$fieldCode]))
				return false;
		}
		unset($fieldCode);
		if ($getPropInfo)
		{
			$property['USER_TYPE_SETTINGS']['FIELDS_MAP'] = $fieldsList;
			$propInfo['USER_TYPE_SETTINGS']['ENTITY'] = $entity;
		}
		return true;
	}

	public static function getTreeProperties($skuInfo, $propertiesCodes, $defaultFields = array())
	{
		$requireFields = array(
			'ID',
			'UF_XML_ID',
			'UF_NAME',
		);

		$result = array();
		if (empty($skuInfo))
			return $result;
		if (!is_array($skuInfo))
		{
			$skuInfo = (int)$skuInfo;
			if ($skuInfo <= 0)
				return $result;
			if (self::$catalogIncluded === null)
				self::$catalogIncluded = Loader::includeModule('catalog');
			if (!self::$catalogIncluded)
				return $result;
			$skuInfo = CCatalogSKU::GetInfoByProductIBlock($skuInfo);
			if (empty($skuInfo))
				return $result;
		}
		if (empty($propertiesCodes) || !is_array($propertiesCodes))
			return $result;

		$showMode = '';

		$propertyIterator = Iblock\PropertyTable::getList(array(
			'select' => array(
				'ID', 'IBLOCK_ID', 'CODE', 'NAME', 'SORT', 'LINK_IBLOCK_ID', 'PROPERTY_TYPE', 'USER_TYPE', 'USER_TYPE_SETTINGS'
			),
			'filter' => array(
				'=IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
				'=PROPERTY_TYPE' => array(
					Iblock\PropertyTable::TYPE_LIST,
					Iblock\PropertyTable::TYPE_ELEMENT,
					Iblock\PropertyTable::TYPE_STRING
				),
				'=ACTIVE' => 'Y', '=MULTIPLE' => 'N'
			),
			'order' => array(
				'SORT' => 'ASC', 'ID' => 'ASC'
			)
		));
		while ($propInfo = $propertyIterator->fetch())
		{
			$propInfo['ID'] = (int)$propInfo['ID'];
			if ($propInfo['ID'] == $skuInfo['SKU_PROPERTY_ID'])
				continue;
			$propInfo['CODE'] = (string)$propInfo['CODE'];
			if ($propInfo['CODE'] === '')
				$propInfo['CODE'] = $propInfo['ID'];
			if (!in_array($propInfo['CODE'], $propertiesCodes))
				continue;
			$propInfo['SORT'] = (int)$propInfo['SORT'];
			$propInfo['USER_TYPE'] = (string)$propInfo['USER_TYPE'];
			if ($propInfo['PROPERTY_TYPE'] == Iblock\PropertyTable::TYPE_STRING)
			{
				if ('directory' != $propInfo['USER_TYPE'])
					continue;
				$propInfo['USER_TYPE_SETTINGS'] = (string)$propInfo['USER_TYPE_SETTINGS'];
				if ($propInfo['USER_TYPE_SETTINGS'] == '')
					continue;
				$propInfo['USER_TYPE_SETTINGS'] = unserialize($propInfo['USER_TYPE_SETTINGS']);
				if (!isset($propInfo['USER_TYPE_SETTINGS']['TABLE_NAME']) || empty($propInfo['USER_TYPE_SETTINGS']['TABLE_NAME']))
					continue;
				if (self::$highLoadInclude === null)
					self::$highLoadInclude = Loader::includeModule('highloadblock');
				if (!self::$highLoadInclude)
					continue;

				$highBlock = HighloadBlockTable::getList(array(
					'filter' => array('=TABLE_NAME' => $propInfo['USER_TYPE_SETTINGS']['TABLE_NAME'])
				))->fetch();
				if (!isset($highBlock['ID']))
					continue;

				$entity = HighloadBlockTable::compileEntity($highBlock);
				$fieldsList = $entity->getFields();
				if (empty($fieldsList))
					continue;

				$flag = true;
				foreach ($requireFields as $fieldCode)
				{
					if (!isset($fieldsList[$fieldCode]) || empty($fieldsList[$fieldCode]))
					{
						$flag = false;
						break;
					}
				}
				unset($fieldCode);
				if (!$flag)
					continue;
				$propInfo['USER_TYPE_SETTINGS']['FIELDS_MAP'] = $fieldsList;
				$propInfo['USER_TYPE_SETTINGS']['ENTITY'] = $entity;
			}
			switch ($propInfo['PROPERTY_TYPE'])
			{
				case Iblock\PropertyTable::TYPE_ELEMENT:
					$showMode = 'PICT';
					break;
				case Iblock\PropertyTable::TYPE_LIST:
					$showMode = 'TEXT';
					break;
				case Iblock\PropertyTable::TYPE_STRING:
					$showMode = (isset($fieldsList['UF_FILE']) ? 'PICT' : 'TEXT');
					break;
			}
			$treeProp = array(
				'ID' => $propInfo['ID'],
				'CODE' => $propInfo['CODE'],
				'NAME' => $propInfo['NAME'],
				'SORT' => $propInfo['SORT'],
				'PROPERTY_TYPE' => $propInfo['PROPERTY_TYPE'],
				'USER_TYPE' => $propInfo['USER_TYPE'],
				'LINK_IBLOCK_ID' => $propInfo['LINK_IBLOCK_ID'],
				'USER_TYPE_SETTINGS' => $propInfo['USER_TYPE_SETTINGS'],
				'VALUES' => array(),
				'SHOW_MODE' => $showMode,
				'DEFAULT_VALUES' => array(
					'PICT' => false,
					'NAME' => '-'
				)
			);
			if ($showMode == 'PICT')
			{
				if (isset($defaultFields['PICT']))
					$treeProp['DEFAULT_VALUES']['PICT'] = $defaultFields['PICT'];
			}
			if (isset($defaultFields['NAME']))
			{
				$treeProp['DEFAULT_VALUES']['NAME'] = $defaultFields['NAME'];
			}
			$result[$treeProp['CODE']] = $treeProp;
		}
		return $result;
	}

	public static function getTreePropertyValues(&$propList, &$propNeedValues)
	{
		$result = array();
		if (!empty($propList) && is_array($propList))
		{
			foreach ($propList as $oneProperty)
			{
				$values = array();
				$valuesExist = false;
				$pictMode = ('PICT' == $oneProperty['SHOW_MODE']);
				$needValuesExist = !empty($propNeedValues[$oneProperty['ID']]) && is_array($propNeedValues[$oneProperty['ID']]);
				$filterValuesExist = ($needValuesExist && count($propNeedValues[$oneProperty['ID']]) <= 500);
				$needValues = array();
				if ($needValuesExist)
					$needValues = array_fill_keys($propNeedValues[$oneProperty['ID']], true);
				switch($oneProperty['PROPERTY_TYPE'])
				{
					case Iblock\PropertyTable::TYPE_LIST:
						$propEnums = CIBlockProperty::GetPropertyEnum(
							$oneProperty['ID'],
							array('SORT' => 'ASC', 'VALUE' => 'ASC')
						);
						while ($oneEnum = $propEnums->Fetch())
						{
							$oneEnum['ID'] = (int)$oneEnum['ID'];
							if ($needValuesExist && !isset($needValues[$oneEnum['ID']]))
								continue;
							$values[$oneEnum['ID']] = array(
								'ID' => $oneEnum['ID'],
								'NAME' => $oneEnum['VALUE'],
								'SORT' => (int)$oneEnum['SORT'],
								'PICT' => false
							);
							$valuesExist = true;
						}
						$values[0] = array(
							'ID' => 0,
							'SORT' => PHP_INT_MAX,
							'NA' => true,
							'NAME' => $oneProperty['DEFAULT_VALUES']['NAME'],
							'PICT' => $oneProperty['DEFAULT_VALUES']['PICT']
						);
						break;
					case Iblock\PropertyTable::TYPE_ELEMENT:
						$selectFields = array('ID', 'NAME');
						if ($pictMode)
							$selectFields[] = 'PREVIEW_PICTURE';
						$filterValues = (
							$filterValuesExist
							? array('ID' => array_values($propNeedValues[$oneProperty['ID']]), 'IBLOCK_ID' => $oneProperty['LINK_IBLOCK_ID'], 'ACTIVE' => 'Y')
							: array('IBLOCK_ID' => $oneProperty['LINK_IBLOCK_ID'], 'ACTIVE' => 'Y')
						);
						$propEnums = CIBlockElement::GetList(
							array('SORT' => 'ASC', 'NAME' => 'ASC'),
							$filterValues,
							false,
							false,
							$selectFields
						);
						while ($oneEnum = $propEnums->Fetch())
						{
							if ($needValuesExist && !$filterValuesExist)
							{
								if (!isset($needValues[$oneEnum['ID']]))
									continue;
							}
							if ($pictMode)
							{
								$oneEnum['PICT'] = false;
								if (!empty($oneEnum['PREVIEW_PICTURE']))
								{
									$previewPict = CFile::GetFileArray($oneEnum['PREVIEW_PICTURE']);
									if (!empty($previewPict))
									{
										$oneEnum['PICT'] = array(
											'SRC' => $previewPict['SRC'],
											'WIDTH' => (int)$previewPict['WIDTH'],
											'HEIGHT' => (int)$previewPict['HEIGHT']
										);
									}
								}
								if (empty($oneEnum['PICT']))
								{
									$oneEnum['PICT'] = $oneProperty['DEFAULT_VALUES']['PICT'];
								}
							}
							$oneEnum['ID'] = (int)$oneEnum['ID'];
							$values[$oneEnum['ID']] = array(
								'ID' => $oneEnum['ID'],
								'NAME' => $oneEnum['NAME'],
								'SORT' => (int)$oneEnum['SORT'],
								'PICT' => ($pictMode ? $oneEnum['PICT'] : false)
							);
							$valuesExist = true;
						}
						$values[0] = array(
							'ID' => 0,
							'SORT' => PHP_INT_MAX,
							'NA' => true,
							'NAME' => $oneProperty['DEFAULT_VALUES']['NAME'],
							'PICT' => ($pictMode ? $oneProperty['DEFAULT_VALUES']['PICT'] : false)
						);
						break;
					case Iblock\PropertyTable::TYPE_STRING:
						if (self::$highLoadInclude === null)
							self::$highLoadInclude = Loader::includeModule('highloadblock');
						if (!self::$highLoadInclude)
							continue;
						$xmlMap = array();
						$sortExist = isset($oneProperty['USER_TYPE_SETTINGS']['FIELDS_MAP']['UF_SORT']);

						$directorySelect = array('ID', 'UF_NAME', 'UF_XML_ID');
						$directoryOrder = array();
						if ($pictMode)
						{
							$directorySelect[] = 'UF_FILE';
						}
						if ($sortExist)
						{
							$directorySelect[] = 'UF_SORT';
							$directoryOrder['UF_SORT'] = 'ASC';
						}
						$directoryOrder['UF_NAME'] = 'ASC';
						$sortValue = 100;

						/** @var Main\Entity\Base $entity */
						$entity = $oneProperty['USER_TYPE_SETTINGS']['ENTITY'];
						if (!($entity instanceof Main\Entity\Base))
							continue;
						$entityDataClass = $entity->getDataClass();
						$entityGetList = array(
							'select' => $directorySelect,
							'order' => $directoryOrder
						);
						if ($filterValuesExist)
							$entityGetList['filter'] = array('=UF_XML_ID' => array_values($propNeedValues[$oneProperty['ID']]));
						$propEnums = $entityDataClass::getList($entityGetList);
						while ($oneEnum = $propEnums->fetch())
						{
							if ($needValuesExist && !$filterValuesExist)
							{
								if (!isset($needValues[$oneEnum['UF_XML_ID']]))
									continue;
							}
							$oneEnum['ID'] = (int)$oneEnum['ID'];
							$oneEnum['UF_SORT'] = ($sortExist ? (int)$oneEnum['UF_SORT'] : $sortValue);
							$sortValue += 100;

							if ($pictMode)
							{
								if (!empty($oneEnum['UF_FILE']))
								{
									$arFile = CFile::GetFileArray($oneEnum['UF_FILE']);
									if (!empty($arFile))
									{
										$oneEnum['PICT'] = array(
											'SRC' => $arFile['SRC'],
											'WIDTH' => (int)$arFile['WIDTH'],
											'HEIGHT' => (int)$arFile['HEIGHT']
										);
									}
								}
								if (empty($oneEnum['PICT']))
									$oneEnum['PICT'] = $oneProperty['DEFAULT_VALUES']['PICT'];
							}
							$values[$oneEnum['ID']] = array(
								'ID' => $oneEnum['ID'],
								'NAME' => $oneEnum['UF_NAME'],
								'SORT' => (int)$oneEnum['UF_SORT'],
								'XML_ID' => $oneEnum['UF_XML_ID'],
								'PICT' => ($pictMode ? $oneEnum['PICT'] : false)
							);
							$valuesExist = true;
							$xmlMap[$oneEnum['UF_XML_ID']] = $oneEnum['ID'];
						}
						$values[0] = array(
							'ID' => 0,
							'SORT' => PHP_INT_MAX,
							'NA' => true,
							'NAME' => $oneProperty['DEFAULT_VALUES']['NAME'],
							'XML_ID' => '',
							'PICT' => ($pictMode ? $oneProperty['DEFAULT_VALUES']['PICT'] : false)
						);
						if ($valuesExist)
							$oneProperty['XML_MAP'] = $xmlMap;
					break;
				}
				if (!$valuesExist)
					continue;
				$oneProperty['VALUES'] = $values;
				$oneProperty['VALUES_COUNT'] = count($values);

				$result[$oneProperty['CODE']] = $oneProperty;
			}
		}
		$propList = $result;
		unset($arFilterProp);
	}

	public static function getMinPriceFromOffers(&$offers, $currency, $replaceMinPrice = true)
	{
		$replaceMinPrice = ($replaceMinPrice === true);
		$result = false;
		$minPrice = 0;
		if (!empty($offers) && is_array($offers))
		{
			$doubles = array();
			foreach ($offers as $oneOffer)
			{
				$oneOffer['ID'] = (int)$oneOffer['ID'];
				if (isset($doubles[$oneOffer['ID']]))
					continue;
				if (!$oneOffer['CAN_BUY'])
					continue;

				CIBlockPriceTools::setRatioMinPrice($oneOffer, $replaceMinPrice);

				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_RATIO'] = $oneOffer['CATALOG_MEASURE_RATIO'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE'] = $oneOffer['CATALOG_MEASURE'];
				$oneOffer['MIN_PRICE']['CATALOG_MEASURE_NAME'] = $oneOffer['CATALOG_MEASURE_NAME'];
				$oneOffer['MIN_PRICE']['~CATALOG_MEASURE_NAME'] = $oneOffer['~CATALOG_MEASURE_NAME'];

				if (empty($result))
				{
					$minPrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					$result = $oneOffer['MIN_PRICE'];
				}
				else
				{
					$comparePrice = ($oneOffer['MIN_PRICE']['CURRENCY'] == $currency
						? $oneOffer['MIN_PRICE']['DISCOUNT_VALUE']
						: CCurrencyRates::ConvertCurrency($oneOffer['MIN_PRICE']['DISCOUNT_VALUE'], $oneOffer['MIN_PRICE']['CURRENCY'], $currency)
					);
					if ($minPrice > $comparePrice)
					{
						$minPrice = $comparePrice;
						$result = $oneOffer['MIN_PRICE'];
					}
				}
				$doubles[$oneOffer['ID']] = true;
			}
		}
		return $result;
	}

	public static function getDoublePicturesForItem(&$item, $propertyCode, $encode = true)
	{
		$encode = ($encode === true);
		$result = array(
			'PICT' => false,
			'SECOND_PICT' => false
		);

		if (!empty($item) && is_array($item))
		{
			if (!empty($item['PREVIEW_PICTURE']))
			{
				if (!is_array($item['PREVIEW_PICTURE']))
					$item['PREVIEW_PICTURE'] = CFile::GetFileArray($item['PREVIEW_PICTURE']);
				if (isset($item['PREVIEW_PICTURE']['ID']))
				{
					$result['PICT'] = array(
						'ID' => (int)$item['PREVIEW_PICTURE']['ID'],
						'SRC' => Iblock\Component\Tools::getImageSrc($item['PREVIEW_PICTURE'], $encode),
						'WIDTH' => (int)$item['PREVIEW_PICTURE']['WIDTH'],
						'HEIGHT' => (int)$item['PREVIEW_PICTURE']['HEIGHT']
					);
				}
			}
			if (!empty($item['DETAIL_PICTURE']))
			{
				$keyPict = (empty($result['PICT']) ? 'PICT' : 'SECOND_PICT');
				if (!is_array($item['DETAIL_PICTURE']))
					$item['DETAIL_PICTURE'] = CFile::GetFileArray($item['DETAIL_PICTURE']);
				if (isset($item['DETAIL_PICTURE']['ID']))
				{
					$result[$keyPict] = array(
						'ID' => (int)$item['DETAIL_PICTURE']['ID'],
						'SRC' => Iblock\Component\Tools::getImageSrc($item['DETAIL_PICTURE'], $encode),
						'WIDTH' => (int)$item['DETAIL_PICTURE']['WIDTH'],
						'HEIGHT' => (int)$item['DETAIL_PICTURE']['HEIGHT']
					);
				}
			}
			if (empty($result['SECOND_PICT']))
			{
				if (
					'' != $propertyCode &&
					isset($item['PROPERTIES'][$propertyCode]) &&
					'F' == $item['PROPERTIES'][$propertyCode]['PROPERTY_TYPE']
				)
				{
					if (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) &&
						!empty($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE'])
					)
					{
						$fileValues = (
							isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']['ID']) ?
							array(0 => $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) :
							$item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']
						);
						foreach ($fileValues as &$oneFileValue)
						{
							$keyPict = (empty($result['PICT']) ? 'PICT' : 'SECOND_PICT');
							$result[$keyPict] = array(
								'ID' => (int)$oneFileValue['ID'],
								'SRC' => Iblock\Component\Tools::getImageSrc($oneFileValue, $encode),
								'WIDTH' => (int)$oneFileValue['WIDTH'],
								'HEIGHT' => (int)$oneFileValue['HEIGHT']
							);
							if ('SECOND_PICT' == $keyPict)
								break;
						}
						if (isset($oneFileValue))
							unset($oneFileValue);
					}
					else
					{
						$propValues = $item['PROPERTIES'][$propertyCode]['VALUE'];
						if (!is_array($propValues))
							$propValues = array($propValues);
						foreach ($propValues as &$oneValue)
						{
							$oneFileValue = CFile::GetFileArray($oneValue);
							if (isset($oneFileValue['ID']))
							{
								$keyPict = (empty($result['PICT']) ? 'PICT' : 'SECOND_PICT');
								$result[$keyPict] = array(
									'ID' => (int)$oneFileValue['ID'],
									'SRC' => Iblock\Component\Tools::getImageSrc($oneFileValue, $encode),
									'WIDTH' => (int)$oneFileValue['WIDTH'],
									'HEIGHT' => (int)$oneFileValue['HEIGHT']
								);
								if ('SECOND_PICT' == $keyPict)
									break;
							}
						}
						if (isset($oneValue))
							unset($oneValue);
					}
				}
			}
		}
		return $result;
	}

	public static function getSliderForItem(&$item, $propertyCode, $addDetailToSlider, $encode = true)
	{
		$encode = ($encode === true);
		$result = array();

		if (!empty($item) && is_array($item))
		{
			if (
				'' != $propertyCode &&
				isset($item['PROPERTIES'][$propertyCode]) &&
				'F' == $item['PROPERTIES'][$propertyCode]['PROPERTY_TYPE']
			)
			{
				if ('MORE_PHOTO' == $propertyCode && isset($item['MORE_PHOTO']) && !empty($item['MORE_PHOTO']))
				{
					foreach ($item['MORE_PHOTO'] as &$onePhoto)
					{
						$result[] = array(
							'ID' => (int)$onePhoto['ID'],
							'SRC' => Iblock\Component\Tools::getImageSrc($onePhoto, $encode),
							'WIDTH' => (int)$onePhoto['WIDTH'],
							'HEIGHT' => (int)$onePhoto['HEIGHT']
						);
					}
					unset($onePhoto);
				}
				else
				{
					if (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) &&
						!empty($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE'])
					)
					{
						$fileValues = (
						isset($item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']['ID']) ?
							array(0 => $item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']) :
							$item['DISPLAY_PROPERTIES'][$propertyCode]['FILE_VALUE']
						);
						foreach ($fileValues as &$oneFileValue)
						{
							$result[] = array(
								'ID' => (int)$oneFileValue['ID'],
								'SRC' => Iblock\Component\Tools::getImageSrc($oneFileValue, $encode),
								'WIDTH' => (int)$oneFileValue['WIDTH'],
								'HEIGHT' => (int)$oneFileValue['HEIGHT']
							);
						}
						if (isset($oneFileValue))
							unset($oneFileValue);
					}
					else
					{
						$propValues = $item['PROPERTIES'][$propertyCode]['VALUE'];
						if (!is_array($propValues))
							$propValues = array($propValues);

						foreach ($propValues as &$oneValue)
						{
							$oneFileValue = CFile::GetFileArray($oneValue);
							if (isset($oneFileValue['ID']))
							{
								$result[] = array(
									'ID' => (int)$oneFileValue['ID'],
									'SRC' => Iblock\Component\Tools::getImageSrc($oneFileValue, $encode),
									'WIDTH' => (int)$oneFileValue['WIDTH'],
									'HEIGHT' => (int)$oneFileValue['HEIGHT']
								);
							}
						}
						if (isset($oneValue))
							unset($oneValue);
					}
				}
			}
			if ($addDetailToSlider || empty($result))
			{
				if (!empty($item['DETAIL_PICTURE']))
				{
					if (!is_array($item['DETAIL_PICTURE']))
						$item['DETAIL_PICTURE'] = CFile::GetFileArray($item['DETAIL_PICTURE']);
					if (isset($item['DETAIL_PICTURE']['ID']))
					{
						array_unshift(
							$result,
							array(
								'ID' => (int)$item['DETAIL_PICTURE']['ID'],
								'SRC' => Iblock\Component\Tools::getImageSrc($item['DETAIL_PICTURE'], $encode),
								'WIDTH' => (int)$item['DETAIL_PICTURE']['WIDTH'],
								'HEIGHT' => (int)$item['DETAIL_PICTURE']['HEIGHT']
							)
						);
					}
				}
			}
		}
		return $result;
	}

	public static function getLabel(&$item, $propertyCode)
	{
		static $propertyEnum = array();

		if (!empty($item) && is_array($item))
		{
			$item['LABEL'] = false;
			$item['LABEL_VALUE'] = '';
			$propertyCode = (string)$propertyCode;
			if ('' !== $propertyCode && isset($item['PROPERTIES'][$propertyCode]))
			{
				$prop = $item['PROPERTIES'][$propertyCode];
				if (!empty($prop['VALUE']))
				{
					$useName = false;
					if ($prop['PROPERTY_TYPE'] == 'L' && $prop['MULTIPLE'] == 'N')
					{
						if (!isset($propertyEnum[$prop['ID']]))
						{
							$count = 0;
							$enumList = CIBlockPropertyEnum::GetList(
								array(),
								array('PROPERTY_ID' => $prop['ID'])
							);
							while ($enum = $enumList->Fetch())
							{
								$count++;
							}
							$propertyEnum[$prop['ID']] = $count;
							unset($enum, $enumList, $count);
						}
						$useName = ($propertyEnum[$prop['ID']] == 1);
					}
					if ($useName)
					{
						$item['LABEL_VALUE'] = $prop['NAME'];
					}
					else
					{
						$item['LABEL_VALUE'] = (is_array($prop['VALUE'])
							? implode(' / ', $prop['VALUE'])
							: $prop['VALUE']
						);
					}
					unset($useName);
					$item['LABEL'] = true;

					if (isset($item['DISPLAY_PROPERTIES'][$propertyCode]))
						unset($item['DISPLAY_PROPERTIES'][$propertyCode]);
				}
				unset($prop);
			}
		}
	}

	public static function clearProperties(&$properties, $clearCodes)
	{
		if (!empty($properties) && is_array($properties) && !empty($clearCodes))
		{
			if (!is_array($clearCodes))
				$clearCodes = array($clearCodes);

			foreach ($clearCodes as &$oneCode)
			{
				if (isset($properties[$oneCode]))
					unset($properties[$oneCode]);
			}
			unset($oneCode);
		}
		return !empty($properties);
	}

	public static function getMinPriceFromList($priceList)
	{
		if (empty($priceList) || !is_array($priceList))
			return false;
		$result = false;
		foreach ($priceList as &$price)
		{
			if (isset($price['MIN_PRICE']) && $price['MIN_PRICE'] == 'Y')
			{
				$result = $price;
				break;
			}
		}
		unset($price);
		return $result;
	}

	public static function isEnabledCalculationDiscounts()
	{
		return (self::$calculationDiscounts >= 0);
	}

	public static function enableCalculationDiscounts()
	{
		self::$calculationDiscounts++;
	}

	public static function disableCalculationDiscounts()
	{
		self::$calculationDiscounts--;
	}
}