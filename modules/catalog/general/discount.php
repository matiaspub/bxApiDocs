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
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/index.php
 * @author Bitrix
 */
class CAllCatalogDiscount
{
	const TYPE_PERCENT = 'P';
	const TYPE_FIX = 'F';
	const TYPE_SALE = 'S';

	static protected $arCacheProduct = array();
	static protected $arCacheDiscountFilter = array();
	static protected $arCacheDiscountResult = array();
	static protected $arCacheProductSectionChain = array();
	static protected $arCacheProductSections = array();
	static protected $arCacheProductProperties = array();

	static public function GetDiscountTypes($boolFull = false)
	{
		$boolFull = (true == $boolFull);
		if ($boolFull)
		{
			return array(
				self::TYPE_PERCENT => GetMessage('BT_CAT_DISCOUNT_TYPE_PERCENT'),
				self::TYPE_FIX => GetMessage('BT_CAT_DISCOUNT_TYPE_FIX'),
				self::TYPE_SALE => GetMessage('BT_CAT_DISCOUNT_TYPE_SALE'),
			);
		}
		return array(
			self::TYPE_PERCENT,
			self::TYPE_FIX,
			self::TYPE_SALE,
		);
	}

	static public function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		global $DB;
		global $USER;

		$boolResult = true;
		$arMsg = array();

		$ACTION = strtoupper($ACTION);
		if ('UPDATE' != $ACTION && 'ADD' != $ACTION)
			return false;

		$boolValueType = false;
		$boolValue = false;
		$arCurrent = array(
			'VALUE' => 0,
			'VALUE_TYPE' => ''
		);

		if (array_key_exists('ID', $arFields))
			unset($arFields['ID']);
		if (array_key_exists('UNPACK', $arFields))
			unset($arFields['UNPACK']);
		if (array_key_exists('USE_COUPONS', $arFields))
			unset($arFields['USE_COUPONS']);

		$arFields['TYPE'] = DISCOUNT_TYPE_STANDART;
		$arFields['VERSION'] = CATALOG_DISCOUNT_NEW_VERSION;

		if ('ADD' == $ACTION)
		{
			$boolValueType = true;
			$boolValue = true;

			if (!array_key_exists('SITE_ID', $arFields))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'SITE_ID', 'text' => GetMessage("KGD_EMPTY_SITE"));
			}
			if (!array_key_exists('CURRENCY', $arFields))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('KGD_EMPTY_CURRENCY'));
			}
			if (!array_key_exists('NAME', $arFields))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('KGD_EMPTY_NAME'));
			}
			if (!array_key_exists('ACTIVE', $arFields))
				$arFields['ACTIVE'] = 'Y';
			if (!array_key_exists('RENEWAL', $arFields))
				$arFields['RENEWAL'] = 'N';
			if (!array_key_exists('MAX_USES', $arFields))
				$arFields['MAX_USES'] = 0;
			if (!array_key_exists('COUNT_USES', $arFields))
				$arFields['COUNT_USES'] = 0;
			if (!array_key_exists('SORT', $arFields))
				$arFields['SORT'] = 100;
			if (!array_key_exists('MAX_DISCOUNT', $arFields))
				$arFields['MAX_DISCOUNT'] = 0;
			if (!array_key_exists('VALUE_TYPE', $arFields))
				$arFields['VALUE_TYPE'] = self::TYPE_PERCENT;
			if (!array_key_exists('VALUE', $arFields))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'VALUE', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_VALUE'));
			}
			if (!array_key_exists('MIN_ORDER_SUM', $arFields))
				$arFields['MIN_ORDER_SUM'] = 0;
			if (!array_key_exists('PRIORITY', $arFields))
				$arFields['PRIORITY'] = 1;
			if (!array_key_exists('LAST_DISCOUNT', $arFields))
				$arFields['LAST_DISCOUNT'] = 'Y';
			if (!array_key_exists('CONDITIONS', $arFields))
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_EMPTY_CONDITIONS'));
			}
			$arFields['USE_COUPONS'] = 'N';
		}

		if ('UPDATE' == $ACTION)
		{
			$ID = intval($ID);
			if (0 >= $ID)
			{
				$boolResult = false;
				$arMsg[] = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_ID', array('#ID#', $ID)));
			}
			else
			{
				$boolValueType = array_key_exists('VALUE_TYPE', $arFields);
				$boolValue = array_key_exists('VALUE', $arFields);
				if ($boolValueType != $boolValue)
				{
					$rsDiscounts = CCatalogDiscount::GetList(
						array(),
						array('ID' => $ID),
						false,
						false,
						array('ID', 'VALUE_TYPE', 'VALUE')
					);
					if ($arCurrent = $rsDiscounts->Fetch())
					{
						$arCurrent['VALUE'] = doubleval($arCurrent['VALUE']);
					}
					else
					{
						$boolResult = false;
						$arMsg[] = array('id' => 'ID', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_ID', array('#ID#', $ID)));
					}
				}
			}
		}

		if ($boolResult)
		{
			if (array_key_exists('SITE_ID', $arFields))
			{
				if (empty($arFields["SITE_ID"]))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'SITE_ID', 'text' => GetMessage('KGD_EMPTY_SITE'));
				}
			}
			if (array_key_exists('CURRENCY', $arFields))
			{
				if (empty($arFields['CURRENCY']))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'CURRENCY', 'text' => GetMessage('KGD_EMPTY_CURRENCY'));
				}
			}
			if (array_key_exists('NAME', $arFields))
			{
				$arFields['NAME'] = trim($arFields['NAME']);
				if ('' == $arFields['NAME'])
				{
					$boolresult = false;
					$arMsg[] = array('id' => 'NAME', 'text' => GetMessage('KGD_EMPTY_NAME'));
				}
			}
			if (array_key_exists('ACTIVE', $arFields))
			{
				$arFields["ACTIVE"] = ('N' != $arFields["ACTIVE"] ? 'Y' : 'N');
			}
			if (array_key_exists('ACTIVE_FROM', $arFields))
			{
				if (!$DB->IsDate($arFields['ACTIVE_FROM'], false, LANGUAGE_ID, 'FULL'))
				{
					$arFields['ACTIVE_FROM'] = false;
				}
			}
			if (array_key_exists('ACTIVE_TO', $arFields))
			{
				if (!$DB->IsDate($arFields['ACTIVE_TO'], false, LANGUAGE_ID, 'FULL'))
				{
					$arFields['ACTIVE_TO'] = false;
				}
			}
			if (array_key_exists('RENEWAL', $arFields))
			{
				$arFields['RENEWAL'] = ('Y' == $arFields['RENEWAL'] ? 'Y' : 'N');
			}
			if (array_key_exists('MAX_USES', $arFields))
			{
				$arFields['MAX_USES'] = intval($arFields['MAX_USES']);
				if (0 > $arFields['MAX_USES'])
					$arFields['MAX_USES'] = 0;
			}
			if (array_key_exists('COUNT_USES', $arFields))
			{
				$arFields['COUNT_USES'] = intval($arFields['COUNT_USES']);
				if (0 > $arFields['COUNT_USES'])
					$arFields['COUNT_USES'] = 0;
			}
			if (array_key_exists('CATALOG_COUPONS', $arFields))
			{
				if (empty($arFields['CATALOG_COUPONS']) && !is_array($arFields['CATALOG_COUPONS']))
					unset($arFields['CATALOG_COUPONS']);
			}
			if (array_key_exists('SORT', $arFields))
			{
				$arFields['SORT'] = intval($arFields['SORT']);
				if (0 >= $arFields['SORT'])
					$arFields['SORT'] = 100;
			}
			if (array_key_exists('MAX_DISCOUNT', $arFields))
			{
				$arFields['MAX_DISCOUNT'] = str_replace(',', '.', $arFields['MAX_DISCOUNT']);
				$arFields['MAX_DISCOUNT'] = doubleval($arFields['MAX_DISCOUNT']);
				if (0 > $arFields['MAX_DISCOUNT'])
					$arFields['MAX_DISCOUNT'] = 0;
			}

			if ($boolValueType)
			{
				if (!in_array($arFields['VALUE_TYPE'], CCatalogDiscount::GetDiscountTypes()))
					$arFields['VALUE_TYPE'] = self::TYPE_PERCENT;
			}
			if ($boolValue)
			{
				$arFields['VALUE'] = str_replace(',', '.', $arFields['VALUE']);
				$arFields['VALUE'] = doubleval($arFields['VALUE']);
				if (!(0 < $arFields['VALUE']))
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'VALUE', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_BAD_VALUE'));
				}
			}
			if ('UPDATE' == $ACTION)
			{
				if ($boolValue != $boolValueType)
				{
					if (!$boolValue)
						$arFields['VALUE'] = $arCurrent['VALUE'];
					if (!$boolValueType)
						$arFields['VALUE_TYPE'] = $arCurrent['VALUE_TYPE'];
				}
				if (self::TYPE_PERCENT == $arFields['VALUE_TYPE'] && 100 < $arFields['VALUE'])
				{
					$boolResult = false;
					$arMsg[] = array('id' => 'VALUE', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_VALUE"));
				}
			}
			if (array_key_exists('MIN_ORDER_SUM', $arFields))
			{
				$arFields['MIN_ORDER_SUM'] = str_replace(',', '.', $arFields['MIN_ORDER_SUM']);
				$arFields['MIN_ORDER_SUM'] = doubleval($arFields['MIN_ORDER_SUM']);
			}
			if (array_key_exists('PRIORITY', $arFields))
			{
				$arFields['PRIORITY'] = intval($arFields['PRIORITY']);
				if (0 >= $arFields['PRIORITY'])
					$arFields['PRIORITY'] = 1;
			}
			if (array_key_exists('LAST_DISCOUNT', $arFields))
			{
				$arFields['LAST_DISCOUNT'] = ('N' != $arFields['LAST_DISCOUNT'] ? 'Y' : 'N');
			}
		}
		if ($boolResult)
		{
			if (array_key_exists('CONDITIONS', $arFields))
			{
				$boolCond = true;
				if (empty($arFields['CONDITIONS']))
				{
					$boolCond = false;
					$boolResult = false;
					$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_EMPTY_CONDITIONS"));
				}
				else
				{
					if (!is_array($arFields['CONDITIONS']))
					{
						if (!CheckSerializedData($arFields['CONDITIONS']))
						{
							$boolCond = false;
							$boolResult = false;
							$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_CONDITIONS"));
						}
						else
						{
							$arFields['CONDITIONS'] = unserialize($arFields['CONDITIONS']);
							if (empty($arFields['CONDITIONS']) || !is_array($arFields['CONDITIONS']))
							{
								$boolCond = false;
								$boolResult = false;
								$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_CONDITIONS"));
							}
						}
					}
					if ($boolCond)
					{
						$obCond = new CCatalogCondTree();
						$boolCond = $obCond->Init(BT_COND_MODE_GENERATE, BT_COND_BUILD_CATALOG, array());
						if (!$boolCond)
						{
							return false;
						}
						$strEval = $obCond->Generate($arFields['CONDITIONS'], array('FIELD' => '$arProduct'));
						if (empty($strEval) || 'false' == $strEval)
						{
							$boolCond = false;
							$boolResult = false;
							$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage("BT_MOD_CATALOG_DISC_ERR_BAD_CONDITIONS"));
						}
					}
					if ($boolCond)
					{
						$arFields['UNPACK'] = $strEval;
						$arFields['CONDITIONS'] = serialize($arFields['CONDITIONS']);
						if ('mysql' == strtolower($DB->type))
						{
							if (64000 < CUtil::BinStrlen($arFields['UNPACK']) || 64000 < CUtil::BinStrlen($arFields['CONDITIONS']))
							{
								$boolResult = false;
								$arMsg[] = array('id' => 'CONDITIONS', 'text' => GetMessage('BT_MOD_CATALOG_DISC_ERR_CONDITIONS_TOO_LONG'));
								unset($arFields['UNPACK']);
								$arFields['CONDITIONS'] = unserialize($arFields['CONDITIONS']);
							}
						}
					}
				}
			}
		}

		$intUserID = 0;
		$boolUserExist = CCatalog::IsUserExists();
		if ($boolUserExist)
			$intUserID = intval($USER->GetID());
		$strDateFunction = $DB->GetNowFunction();
		if (array_key_exists('TIMESTAMP_X', $arFields))
			unset($arFields['TIMESTAMP_X']);
		if (array_key_exists('DATE_CREATE', $arFields))
			unset($arFields['DATE_CREATE']);
		$arFields['~TIMESTAMP_X'] = $strDateFunction;
		if ($boolUserExist)
		{
			if (!array_key_exists('MODIFIED_BY', $arFields) || intval($arFields["MODIFIED_BY"]) <= 0)
				$arFields["MODIFIED_BY"] = $intUserID;
		}
		if ('ADD' == $ACTION)
		{
			$arFields['~DATE_CREATE'] = $strDateFunction;
			if ($boolUserExist)
			{
				if (!array_key_exists('CREATED_BY', $arFields) || intval($arFields["CREATED_BY"]) <= 0)
					$arFields["CREATED_BY"] = $intUserID;
			}
		}
		if ('UPDATE' == $ACTION)
		{
			if (array_key_exists('CREATED_BY', $arFields))
				unset($arFields['CREATED_BY']);
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
		}
		return $boolResult;
	}

	
	/**
	* <p>Метод добавляет новую скидку в соответствии с данными из массива arFields.</p>
	*
	*
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой скидки, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения. Допустимые ключи: <ul> <li> <b>SITE_ID</b> - сайт, обязательное
	* поле;</li> <li> <b>ACTIVE</b> - флаг активности;</li> <li> <b>NAME</b> - название
	* скидки, обязательное поле;</li> <li> <b>COUPON</b> - код купона;</li> <li> <b>SORT</b> -
	* индекс сортировки;</li> <li> <b>MAX_DISCOUNT</b> - максимальная величина
	* скидки;</li> <li> <b>VALUE_TYPE</b> - тип скидки (P - в процентах, F -
	* фиксированная величина, S - фиксированная цена);</li> <li> <b>VALUE</b> -
	* величина скидки;</li> <li> <b>CURRENCY</b> - валюта, обязательное поле;</li> <li>
	* <b>RENEWAL</b> - флаг "Скидка на продление";</li> <li> <b>ACTIVE_FROM</b> - дата начала
	* действия скидки;</li> <li> <b>ACTIVE_TO</b> - дата окончания действия
	* скидки;</li> <li> <b>IBLOCK_IDS</b> - массив кодов инфоблоков, на которые
	* действует скидка (если скидка действует не на все инфоблоки). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>PRODUCT_IDS</b> - массив
	* кодов товаров, на которые действует скидка (если скидка действует
	* не на все товары). Ключ является устаревшим с версии <b>12.0.0</b>;</li> <li>
	* <b>SECTION_IDS</b> - массив кодов групп товаров, на которые действует
	* скидка (если скидка действует не на все группы товары). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>GROUP_IDS</b> - массив
	* кодов групп пользователей, на которые действует скидка (если
	* скидка действует не на все группы пользователей);</li> <li>
	* <b>CATALOG_GROUP_IDS</b> - массив кодов типов цен, на которые действует
	* скидка (если скидка действует не на все типы цен).</li> <li>
	* <b>CATALOG_COUPONS</b> - массив купонов скидки.</li> <li> <b>PRIORITY</b> - приоритет
	* применимости;</li> <li> <b>CONDITIONS</b> - массив для создания условий
	* использования скидки. Ключ доступен с версии <b>12.0.0</b>. <br><br> Если он
	* задан и не пуст, то массивы <b>PRODUCT_IDS</b>, <b>SECTION_IDS</b> и <b>IBLOCK_IDS</b>
	* использоваться не будут. Чтобы задать параметры скидки через эти
	* 3 ключа, то <b>CONDITIONS</b> в массиве <b>arFields</b> должен отсутствовать. <br><br>
	* Каждое условие массива <b>CONDITIONS</b> описывается массивом следующей
	* структуры: <ul> <li> <i>CLASS_ID</i> - идентификатор (строка);</li> <li> <i>DATA =&gt;
	* array()</i> - массив параметров условий;</li> <li> <i>CHILDREN =&gt; array()</i> - массив
	* подусловий, каждое из которых является массивом аналогичной
	* структуры, где ключами являются значения 0,1,2,3,.. </li> </ul> <br>
	* Возможные логические условия: <ul> <li>Equal - равно;</li> <li>Not - не
	* равно;</li> <li>Great - больше;</li> <li>Less - меньше;</li> <li>EqGr - больше либо
	* равно;</li> <li>EqLs - меньше либо равно.</li> </ul> <br> Наименования условий:
	* <ul> <li>CondIBElement - товар;</li> <li>CondIBIBlock - инфоблок;</li> <li>CondIBSection -
	* раздел;</li> <li>CondIBCode - символьный код;</li> <li>CondIBXmlID - внешний код;</li>
	* <li>CondIBName - название;</li> <li>CondIBActive - активность;</li> <li>CondIBDateActiveFrom -
	* начало активности;</li> <li>CondIBDateActiveTo - окончание активности;</li>
	* <li>CondIBSort - сортировка;</li> <li>CondIBPreviewText - описание для анонса;</li>
	* <li>CondIBDetailText - детальное описание;</li> <li>CondIBDateCreate - дата создания;</li>
	* <li>CondIBCreatedBy - автор;</li> <li>CondIBTimestampX - дата изменения;</li> <li>CondIBModifiedBy -
	* изменивший;</li> <li>CondIBTags - теги;</li> <li>CondCatQuantity - количество товара на
	* складе;</li> <li>CondCatWeight - вес товара;</li> <li>CondCatVatID - НДС;</li> <li>CondCatVatIncluded
	* - НДС включен в цену.</li> </ul> <br> Кроме того, возможна привязка
	* условий к свойствам товара. <br><br> Верхний элемент массива
	* <b>CONDITIONS</b> всегда один и тот же (для скидок каталога может быть
	* получен методом <b>CCatalogCondTree::GetDefaultConditions()</b>): <pre class="syntax"> array( 'CLASS_ID'
	* =&gt; 'CondGroup', 'DATA' =&gt; array('All' =&gt; 'AND', 'True' =&gt; 'True'), 'CHILDREN' =&gt; array() ); </pre> </li>
	* </ul>
	*
	*
	*
	* @return bool <p>Метод возвращает код вставленной записи или <i>false</i> в случае
	* ошибки.</p>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* Получить детальную информацию об ошибке при сохранении можно следующим образом:
	* 
	* 
	* $ID = CCatalogDiscount::Add($arFields);
	* $res = $ID&gt;0;
	* if (!$res) { 
	*     $ex = $APPLICATION-&gt;GetException();  
	*     $ex-&gt;GetString(); 
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount_add.php
	* @author Bitrix
	*/
	static public function Add($arFields)
	{
		foreach (GetModuleEvents("catalog", "OnBeforeDiscountAdd", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array(&$arFields)))
				return false;
		}

		$mxRows = self::__ParseArrays($arFields);
		if (!is_array($mxRows) || empty($mxRows))
			return false;

		$boolNewVersion = true;
		if (!array_key_exists('CONDITIONS', $arFields))
		{
			self::__ConvertOldConditions('ADD', $arFields);
			$boolNewVersion = false;
		}

		$ID = CCatalogDiscount::_Add($arFields);
		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		if ($boolNewVersion)
		{
			$arValuesList = self::__GetConditionValues($arFields);
			if (is_array($arValuesList) && !empty($arValuesList))
			{
				self::__GetOldOneEntity($arFields, $arValuesList, 'IBLOCK_IDS', 'CondIBIBlock');
				self::__GetOldOneEntity($arFields, $arValuesList, 'SECTION_IDS', 'CondIBSection');
				self::__GetOldOneEntity($arFields, $arValuesList, 'PRODUCT_IDS', 'CondIBElement');
			}
		}

		if (!CCatalogDiscount::__UpdateSubdiscount($ID, $mxRows))
			return false;

		CCatalogDiscount::__UpdateOldEntities($ID, $arFields, false);

		if (array_key_exists('CATALOG_COUPONS', $arFields))
		{
			if (!is_array($arFields["CATALOG_COUPONS"]))
			{
				$arFields["CATALOG_COUPONS"] = array(
					"DISCOUNT_ID" => $ID,
					"ACTIVE" => "Y",
					"ONE_TIME" => "Y",
					"COUPON" => $arFields["CATALOG_COUPONS"],
					"DATE_APPLY" => false
				);
			}

			$arKeys = array_keys($arFields["CATALOG_COUPONS"]);
			if (!is_array($arFields["CATALOG_COUPONS"][$arKeys[0]]))
				$arFields["CATALOG_COUPONS"] = array($arFields["CATALOG_COUPONS"]);

			foreach ($arFields["CATALOG_COUPONS"] as &$arOneCoupon)
			{
				if (!empty($arOneCoupon['COUPON']))
				{
					$arOneCoupon['DISCOUNT_ID'] = $ID;
					CCatalogDiscountCoupon::Add($arOneCoupon, false);
				}
				if (isset($arOneCoupon))
					unset($arOneCoupon);
			}
		}


		CCatalogDiscount::SaveFilterOptions();

		foreach (GetModuleEvents("catalog", "OnDiscountAdd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}

	
	/**
	* <p>Метод изменяет параметры скидки с кодом ID в соответствии с данными из массива arFields.</p>
	*
	*
	*
	*
	* @param int $ID  Код скидки.
	*
	*
	*
	* @param array $arFields  Ассоциативный массив параметров новой скидки, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения. Допустимые ключи: <ul> <li> <b>SITE_ID</b> - сайт;</li> <li> <b>ACTIVE</b> -
	* флаг активности;</li> <li> <b>NAME</b> - название скидки;</li> <li> <b>COUPON</b> - код
	* купона;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li> <b>MAX_DISCOUNT</b> -
	* максимальная величина скидки;</li> <li> <b>VALUE_TYPE</b> - тип скидки (P - в
	* процентах, F - фиксированная величина);</li> <li> <b>VALUE</b> - величина
	* скидки;</li> <li> <b>CURRENCY</b> - валюта;</li> <li> <b>RENEWAL</b> - флаг "Скидка на
	* продление";</li> <li> <b>ACTIVE_FROM</b> - дата начала действия скидки;</li> <li>
	* <b>ACTIVE_TO</b> - дата окончания действия скидки;</li> <li> <b>IBLOCK_IDS</b> - массив
	* кодов инфоблоков, на которые действует скидка (если скидка
	* действует не на все инфоблоки). Ключ является устаревшим с версии
	* <b>12.0.0</b>;</li> <li> <b>PRODUCT_IDS</b> - массив кодов товаров, на которые
	* действует скидка (если скидка действует не на все товары). Ключ
	* является устаревшим с версии <b>12.0.0</b>;</li> <li> <b>SECTION_IDS</b> - массив
	* кодов групп товаров, на которые действует скидка (если скидка
	* действует не на все группы товары). Ключ является устаревшим с
	* версии <b>12.0.0</b>;</li> <li> <b>GROUP_IDS</b> - массив кодов групп пользователей,
	* на которые действует скидка (если скидка действует не на все
	* группы пользователей);</li> <li> <b>CATALOG_GROUP_IDS</b> - массив кодов типов
	* цен, на которые действует скидка (если скидка действует не на все
	* типы цен);</li> <li> <b>CONDITIONS</b> - массив для изменения условий
	* использования скидки. Массив перезаписывается, поэтому при
	* обновлении скидки следует добавлять в массив все необходимые
	* данные. Ключ доступен с версии <b>12.0.0</b>. <br><br> Если он задан и не
	* пуст, то массивы <b>PRODUCT_IDS</b>, <b>SECTION_IDS</b> и <b>IBLOCK_IDS</b> использоваться
	* не будут. Чтобы задать параметры скидки через эти 3 ключа, то
	* <b>CONDITIONS</b> в массиве <b>arFields</b> должен отсутствовать, а старые
	* данные будут изменены в соответствии <b>PRODUCT_IDS</b>, <b>SECTION_IDS</b> и
	* <b>IBLOCK_IDS</b>. <br><br> Каждое условие массива <b>CONDITIONS</b> описывается
	* массивом следующей структуры: <ul> <li> <i>CLASS_ID</i> - идентификатор
	* (строка);</li> <li> <i>DATA =&gt; array()</i> - массив параметров условий;</li> <li>
	* <i>CHILDREN =&gt; array()</i> - массив подусловий, каждое из которых является
	* массивом аналогичной структуры, где ключами являются значения
	* 0,1,2,3,.. </li> </ul> <br> Возможные логические условия: <ul> <li>Equal - равно;</li>
	* <li>Not - не равно;</li> <li>Great - больше;</li> <li>Less - меньше;</li> <li>EqGr - больше
	* либо равно;</li> <li>EqLs - меньше либо равно.</li> </ul> <br> Наименования
	* условий: <ul> <li>CondIBElement - товар;</li> <li>CondIBIBlock - инфоблок;</li> <li>CondIBSection -
	* раздел;</li> <li>CondIBCode - символьный код;</li> <li>CondIBXmlID - внешний код;</li>
	* <li>CondIBName - название;</li> <li>CondIBActive - активность;</li> <li>CondIBDateActiveFrom -
	* начало активности;</li> <li>CondIBDateActiveTo - окончание активности;</li>
	* <li>CondIBSort - сортировка;</li> <li>CondIBPreviewText - описание для анонса;</li>
	* <li>CondIBDetailText - детальное описание;</li> <li>CondIBDateCreate - дата создания;</li>
	* <li>CondIBCreatedBy - автор;</li> <li>CondIBTimestampX - дата изменения;</li> <li>CondIBModifiedBy -
	* изменивший;</li> <li>CondIBTags - теги;</li> <li>CondCatQuantity - количество товара на
	* складе;</li> <li>CondCatWeight - вес товара;</li> <li>CondCatVatID - НДС;</li> <li>CondCatVatIncluded
	* - НДС включен в цену.</li> </ul> <br> Кроме того, возможна привязка
	* условий к свойствам товара. <br><br> Верхний элемент массива
	* <b>CONDITIONS</b> всегда один и тот же (для скидок каталога может быть
	* получен методом <b>CCatalogCondTree::GetDefaultConditions()</b>): <pre class="syntax"> array( 'CLASS_ID'
	* =&gt; 'CondGroup', 'DATA' =&gt; array('All' =&gt; 'AND', 'True' =&gt; 'True'), 'CHILDREN' =&gt; array() ); </pre> </li>
	* </ul>
	*
	*
	*
	* @return bool <p>Метод возвращает код измененной записи или <i>false</i> в случае
	* ошибки.</p>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* Получить детальную информацию об ошибке при изменении можно следующим образом:
	* 
	* 
	* $res = CCatalogDiscount::Update($ID, $arFields);  
	* if (!$res) { 
	*     $ex = $APPLICATION-&gt;GetException();  
	*     $ex-&gt;GetString(); 
	* }
	* 
	* $arFields = array(
	*    "SITE_ID" =&gt; "s1",
	*    "MAX_DISCOUNT" =&gt; 0,
	*    "VALUE" =&gt; 15,
	*    "ACTIVE" =&gt; "Y",
	*    "CONDITIONS" =&gt;  array (
	*       'CLASS_ID' =&gt; 'CondGroup',
	*       'DATA' =&gt;
	*       array (
	*          'All' =&gt; 'AND',
	*          'True' =&gt; 'True',
	*       ),
	*       'CHILDREN' =&gt;
	*       array (
	*          0 =&gt;
	*          array (
	*             'CLASS_ID' =&gt; 'CondIBElement',
	*             'DATA' =&gt;
	*             array (
	*                'logic' =&gt; 'Equal',
	*                'value' =&gt; 2975, //товар с ID=2975
	*             ),
	*          ),
	*          1 =&gt;
	*          array (
	*             'CLASS_ID' =&gt; 'CondCatQuantity',
	*             'DATA' =&gt;
	*             array (
	*                'logic' =&gt; 'Equal',
	*                'value' =&gt; 10, //остаток на складе равен 10
	*             ),
	*          ),
	*       ),  
	*    )
	* );
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.update.php
	* @author Bitrix
	*/
	static public function Update($ID, $arFields)
	{
		$ID = intval($ID);
		if (0 >= $ID)
			return false;

		foreach (GetModuleEvents("catalog", "OnBeforeDiscountUpdate", true) as $arEvent)
		{
			if (false === ExecuteModuleEventEx($arEvent, array($ID, &$arFields)))
				return false;
		}

		$boolExistUserGroups = (isset($arFields['GROUP_IDS']) && is_array($arFields['GROUP_IDS']));
		$boolExistPriceTypes = (isset($arFields['CATALOG_GROUP_IDS']) && is_array($arFields['CATALOG_GROUP_IDS']));
		$boolUpdateRestrictions = $boolExistUserGroups || $boolExistPriceTypes;

		if ($boolUpdateRestrictions)
		{
			if (!$boolExistUserGroups)
			{
				if (!CCatalogDiscount::__FillArrays($ID, $arFields, 'GROUP_IDS'))
					return false;
			}
			if (!$boolExistPriceTypes)
			{
				if (!CCatalogDiscount::__FillArrays($ID, $arFields, 'CATALOG_GROUP_IDS'))
					return false;
			}
			$mxRows = self::__ParseArrays($arFields);
			if (!is_array($mxRows) || empty($mxRows))
				return false;
		}

		$boolNewVersion = true;
		if (!array_key_exists('CONDITIONS', $arFields))
		{
			self::__ConvertOldConditions('UPDATE', $arFields);
			$boolNewVersion = false;
		}

		if (!CCatalogDiscount::_Update($ID, $arFields))
			return false;

		if ($boolNewVersion)
		{
			$arValuesList = self::__GetConditionValues($arFields);
			if (is_array($arValuesList) && !empty($arValuesList))
			{
				self::__GetOldOneEntity($arFields, $arValuesList, 'IBLOCK_IDS', 'CondIBIBlock');
				self::__GetOldOneEntity($arFields, $arValuesList, 'SECTION_IDS', 'CondIBSection');
				self::__GetOldOneEntity($arFields, $arValuesList, 'PRODUCT_IDS', 'CondIBElement');
			}
		}

		if ($boolUpdateRestrictions)
		{
			if (!CCatalogDiscount::__UpdateSubdiscount($ID, $mxRows))
				return false;
		}

		CCatalogDiscount::__UpdateOldEntities($ID, $arFields, true);

		if (array_key_exists('CATALOG_COUPONS', $arFields))
		{
			if (!is_array($arFields["CATALOG_COUPONS"]))
			{
				$arFields["CATALOG_COUPONS"] = array(
					"DISCOUNT_ID" => $ID,
					"ACTIVE" => "Y",
					"ONE_TIME" => "Y",
					"COUPON" => $arFields["CATALOG_COUPONS"],
					"DATE_APPLY" => false
				);
			}

			$arKeys = array_keys($arFields["CATALOG_COUPONS"]);
			if (!is_array($arFields["CATALOG_COUPONS"][$arKeys[0]]))
				$arFields["CATALOG_COUPONS"] = array($arFields["CATALOG_COUPONS"]);

			foreach ($arFields["CATALOG_COUPONS"] as &$arOneCoupon)
			{
				if (!empty($arOneCoupon['COUPON']))
				{
					$arOneCoupon['DISCOUNT_ID'] = $ID;
					CCatalogDiscountCoupon::Add($arOneCoupon, false);
				}
				if (isset($arOneCoupon))
					unset($arOneCoupon);
			}
		}

		CCatalogDiscount::SaveFilterOptions();

		foreach (GetModuleEvents("catalog", "OnDiscountUpdate", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
		}

		return $ID;
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::SetCoupon()
*/
	
	/**
	* <p>Метод добавляет код купона <i> coupon</i> в массив доступных для получения скидки купонов текущего покупателя. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @param string $coupon  Код купона.
	*
	*
	*
	* @return bool <p>Метод возвращает <i>true</i> в случае успешного добавления кода
	* купона и <i>false</i> в случае ошибки.</p> <h4>Примечание</h4> <p>С версии 12.0
	* считаются устаревшим. Оставлен для совместимости. Рекомендуется
	* использовать <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/setcoupon.php">аналогичный
	* метод</a> класса <b>CCatalogDiscountCoupon</b>.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.setcoupon.php
	* @author Bitrix
	*/
	static public function SetCoupon($coupon)
	{
		return CCatalogDiscountCoupon::SetCoupon($coupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::GetCoupons()
*/
	
	/**
	* <p>Метод возвращает массив доступных для получения скидки купонов текущего покупателя. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @return array <p>Метод возвращает массив купонов текущего пользователя.</p>
	* <h4>Примечание</h4> <p>С версии 12.0 метод считается устаревшим.
	* Оставлен для совместимости. Рекомендуется использовать <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/getcoupons.php">аналогичный
	* метод</a> класса <b>CCatalogDiscountCoupon</b>.</p> <br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getcoupons.php
	* @author Bitrix
	*/
	static public function GetCoupons()
	{
		return CCatalogDiscountCoupon::GetCoupons();
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::EraseCoupon()
*/
	static public function EraseCoupon($strCoupon)
	{
		return CCatalogDiscountCoupon::EraseCoupon($strCoupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::ClearCoupon()
*/
	
	/**
	* <p>Метод очищает массив купонов, введенных текущим покупателем. Система вычисляет минимальную для данного покупателя цену товара с учётом всех его скидок и купонов.</p>
	*
	*
	*
	*
	* @return void <p>Метод не возвращает значений.</p> <h4>Примечание</h4> <p>С версии 12.0
	* метод считается устаревшим. Оставлен для совместимости.
	* Рекомендуется использовать <a
	* href="http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscountcoupon/clearcoupon.php">аналогичный
	* метод</a> класса <b>CCatalogDiscountCoupon</b>.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.clearcoupon.php
	* @author Bitrix
	*/
	static public function ClearCoupon()
	{
		CCatalogDiscountCoupon::ClearCoupon();
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::SetCouponByManage()
*/
	static public function SetCouponByManage($intUserID,$strCoupon)
	{
		return CCatalogDiscountCoupon::SetCouponByManage($intUserID,$strCoupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::GetCouponsByManage()
*/
	static public function GetCouponsByManage($intUserID)
	{
		return CCatalogDiscountCoupon::GetCouponsByManage($intUserID);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::EraseCouponByManage()
*/
	static public function EraseCouponByManage($intUserID,$strCoupon)
	{
		return CCatalogDiscountCoupon::EraseCouponByManage($intUserID,$strCoupon);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::ClearCouponsByManage()
*/
	static public function ClearCouponsByManage($intUserID)
	{
		return CCatalogDiscountCoupon::ClearCouponsByManage($intUserID);
	}

	static public function OnCurrencyDelete($Currency)
	{
		global $DB;
		if (empty($Currency)) return false;

		$dbDiscounts = CCatalogDiscount::GetList(array(), array("CURRENCY" => $Currency), false, false, array("ID"));
		while ($arDiscounts = $dbDiscounts->Fetch())
		{
			CCatalogDiscount::Delete($arDiscounts["ID"]);
		}

		return true;
	}

	static public function OnGroupDelete($GroupID)
	{
		global $DB;
		$GroupID = intval($GroupID);

		return $DB->Query("DELETE FROM b_catalog_discount2group WHERE GROUP_ID = ".$GroupID." ", true);
	}

/*
* @deprecated deprecated since catalog 12.0.0
*/
	static public function GenerateDataFile($ID)
	{
	}

/*
* @deprecated deprecated since catalog 12.0.0
*/
	static public function ClearFile($ID, $strDataFileName = false)
	{
	}

	
	/**
	* <p>Метод вычисляет скидку на цену с кодом productPriceID товара для пользователя, принадлежащего группам пользователей arUserGroups.</p>
	*
	*
	*
	*
	* @param int $productPriceID  Код цены.</bod
	*
	*
	*
	* @param  $array  Массив групп, которым принадлежит пользователь. Для текущего
	* пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	*
	*
	*
	* @param arUserGroup $s = array()[ Флаг "Продление подписки".
	*
	*
	*
	* @param string $renewal = "N"[ Сайт (по умолчанию текущий).
	*
	*
	*
	* @param string $siteID = false[ Массив купонов, которые влияют на выборку скидок. Если задано
	* значение <i>false</i>, то массив купонов будет взят из <b>
	* CCatalogDiscountCoupon::GetCoupons</b>
	*
	*
	*
	* @param array $arDiscountCoupons = false]]]] 
	*
	*
	*
	* @return bool <p>Метод возвращает массив ассоциативных массивов скидок или
	* <i>false</i> в случае ошибки. В массиве содержится ассоциативный
	* массив параметров максимальной процентной скидки (если есть) и
	* ассоциативный массив параметров максимальной фиксированной
	* скидки (если есть). <a name="examples"></a> </p>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $dbPrice = CPrice::GetList(
	*     array("QUANTITY_FROM" =&gt; "ASC", "QUANTITY_TO" =&gt; "ASC", 
	*           "SORT" =&gt; "ASC"),
	*     array("PRODUCT_ID" =&gt; $ID),
	*     false,
	*     false,
	*     array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", 
	*           "QUANTITY_FROM", "QUANTITY_TO")
	* );
	* while ($arPrice = $dbPrice-&gt;Fetch())
	* {
	*     $arDiscounts = CCatalogDiscount::GetDiscountByPrice(
	*             $arPrice["ID"],
	*             $USER-&gt;GetUserGroupArray(),
	*             "N",
	*             SITE_ID
	*         );
	*     $discountPrice = CCatalogProduct::CountPriceWithDiscount(
	*             $arPrice["PRICE"],
	*             $arPrice["CURRENCY"],
	*             $arDiscounts
	*         );
	*     $arPrice["DISCOUNT_PRICE"] = $discountPrice;
	* 
	*     echo "&lt;pre&gt;&amp;quot;;
	*     print_r($arPrice);
	*     echo &amp;quot;&lt;/pre&gt;";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyprice.php
	* @author Bitrix
	*/
	static public function GetDiscountByPrice($productPriceID, $arUserGroups = array(), $renewal = "N", $siteID = false, $arDiscountCoupons = false)
	{
		global $APPLICATION;

		foreach (GetModuleEvents("catalog", "OnGetDiscountByPrice", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($productPriceID, $arUserGroups, $renewal, $siteID, $arDiscountCoupons));
			if (true !== $mxResult)
				return $mxResult;
		}

		$productPriceID = intval($productPriceID);
		if (0 >= $productPriceID)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_PRICE_ID_ABSENT"), "NO_PRICE_ID");
			return false;
		}

		$dbPrice = CPrice::GetListEx(
			array(),
			array("ID" => $productPriceID),
			false,
			false,
			array("ID", "PRODUCT_ID", "CATALOG_GROUP_ID", "ELEMENT_IBLOCK_ID")
		);
		if ($arPrice = $dbPrice->Fetch())
		{
			return CCatalogDiscount::GetDiscount($arPrice["PRODUCT_ID"], $arPrice["ELEMENT_IBLOCK_ID"], $arPrice["CATALOG_GROUP_ID"], $arUserGroups, $renewal, $siteID, $arDiscountCoupons);
		}
		else
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $productPriceID, GetMessage("BT_MOD_CATALOG_DISC_ERR_PRICE_ID_NOT_FOUND")), "NO_PRICE");
			return false;
		}
	}

	
	/**
	* <p>Метод вычисляет скидку на товар с кодом productID для пользователя, принадлежащего группам пользователей arUserGroups.</p>
	*
	*
	*
	*
	* @param int $productID = 0[ Код товара.
	*
	*
	*
	* @param array $arUserGroups = array()[ Массив групп, которым принадлежит пользователь. Для текущего
	* пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	*
	*
	*
	* @param string $renewal = "N"[ Флаг "Продление подписки"
	*
	*
	*
	* @param array $arCatalogGroups = array()[ Массив типов цен, для которых искать скидку.
	*
	*
	*
	* @param string $siteID = false[ Сайт (по умолчанию текущий)
	*
	*
	*
	* @param array $arDiscountCoupons = false]]]] Массив купонов, которые влияют на выборку скидок. Если задано
	* значение <i>false</i>, то массив купонов будет взят из <b>
	* CCatalogDiscountCoupon::GetCoupons</b>
	*
	*
	*
	* @return mixed <p>Метод возвращает массив ассоциативных массивов скидок или
	* <i>false</i> в случае ошибки. В массиве содержится ассоциативный
	* массив параметров максимальной процентной скидки (если есть) и
	* ассоциативный массив параметров максимальной фиксированной
	* скидки (если есть).</p> <a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arDiscounts = CCatalogDiscount::GetDiscountByProduct(
	*         150,
	*         $USER-&gt;GetUserGroupArray(),
	*         "N",
	*         2,
	*         SITE_ID
	*     );
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/catalog/classes/ccatalogdiscount/ccatalogdiscount.getdiscountbyproduct.php
	* @author Bitrix
	*/
	static public function GetDiscountByProduct($productID = 0, $arUserGroups = array(), $renewal = "N", $arCatalogGroups = array(), $siteID = false, $arDiscountCoupons = false)
	{
		global $APPLICATION;

		foreach (GetModuleEvents("catalog", "OnGetDiscountByProduct", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($productID, $arUserGroups, $renewal, $arCatalogGroups, $siteID, $arDiscountCoupons));
			if (true !== $mxResult)
				return $mxResult;
		}

		$productID = intval($productID);
		if (0 >= $productID)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $productID, GetMessage("BT_MOD_CATALOG_DISC_ERR_ELEMENT_ID_NOT_FOUND")), "NO_ELEMENT");
			return false;
		}

		$intIBlockID = intval(CIBlockElement::GetIBlockByID($productID));
		if (0 >= $intIBlockID)
		{
			$APPLICATION->ThrowException(str_replace("#ID#", $productID, GetMessage("BT_MOD_CATALOG_DISC_ERR_ELEMENT_ID_NOT_FOUND")), "NO_ELEMENT");
			return false;
		}

		return CCatalogDiscount::GetDiscount($productID, $intIBlockID, $arCatalogGroups, $arUserGroups, $renewal, $siteID, $arDiscountCoupons);
	}

	static public function GetDiscount($intProductID, $intIBlockID, $arCatalogGroups = array(), $arUserGroups = array(), $strRenewal = "N", $siteID = false, $arDiscountCoupons = false, $boolSKU = true, $boolGetIDS = false)
	{
		global $DB;
		global $APPLICATION;

		foreach (GetModuleEvents("catalog", "OnGetDiscount", true) as $arEvent)
		{
			$mxResult = ExecuteModuleEventEx($arEvent, array($intProductID, $intIBlockID, $arCatalogGroups, $arUserGroups, $strRenewal, $siteID, $arDiscountCoupons, $boolSKU, $boolGetIDS));
			if (true !== $mxResult)
				return $mxResult;
		}

		$boolSKU = (true === $boolSKU ? true : false);
		$boolGetIDS = (true === $boolGetIDS ? true : false);

		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_PRODUCT_ID_ABSENT"), "NO_PRODUCT_ID");
			return false;
		}

		$intIBlockID = intval($intIBlockID);
		if (0 >= $intIBlockID)
		{
			$APPLICATION->ThrowException(GetMessage("BT_MOD_CATALOG_DISC_ERR_IBLOCK_ID_ABSENT"), "NO_IBLOCK_ID");
			return false;
		}

		if (!empty($arCatalogGroups))
		{
			if (!is_array($arCatalogGroups))
			{
				$arCatalogGroups = (intval($arCatalogGroups)."|" == $arCatalogGroups."|" ? array($arCatalogGroups) : array());
			}
		}
		else
		{
			$arCatalogGroups = array();
		}
		if (!empty($arCatalogGroups))
		{
			CatalogClearArray($arCatalogGroups);
		}

		if (!is_array($arUserGroups))
		{
			$arUserGroups = (intval($arUserGroups)."|" == $arUserGroups."|" ? array($arUserGroups) : array());
		}
		$arUserGroups[] = 2;
		if (!empty($arUserGroups))
		{
			CatalogClearArray($arUserGroups);
		}

		$strRenewal = (($strRenewal == "Y") ? "Y" : "N");

		if ($siteID === false)
			$siteID = SITE_ID;

		if ($arDiscountCoupons === false)
			$arDiscountCoupons = CCatalogDiscountCoupon::GetCoupons();

		$arSKU = false;

		$arResult = array();
		$arResultID = array();

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		if (!isset(self::$arCacheDiscountFilter[$strCacheKey]))
		{
			$arFilter = array(
				'PRICE_TYPE_ID' => $arCatalogGroups,
				'USER_GROUP_ID' => $arUserGroups,
			);
			$arDiscountIDs = CCatalogDiscount::__GetDiscountID($arFilter);
			if (!empty($arDiscountIDs))
			{
				sort($arDiscountIDs);
			}
			self::$arCacheDiscountFilter[$strCacheKey] = $arDiscountIDs;
		}
		else
		{
			$arDiscountIDs = self::$arCacheDiscountFilter[$strCacheKey];
		}

		$arProduct = array();

		if (!empty($arDiscountIDs))
		{
			$boolGenerate = false;

			$strCacheKey = 'D'.implode('_', $arDiscountIDs).'-'.'S'.$siteID.'-R'.$strRenewal;
			if (!empty($arDiscountCoupons))
			{
				$strCacheKey .= '-C'.implode('|', $arDiscountCoupons);
			}
			$strCacheKey = md5($strCacheKey);

			if (!isset(self::$arCacheDiscountResult[$strCacheKey]))
			{
				$arSelect = array(
					"ID", "TYPE", "SITE_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO",
					"RENEWAL", "NAME", "SORT", "MAX_DISCOUNT", "VALUE_TYPE", "VALUE", "CURRENCY",
					"PRIORITY", "LAST_DISCOUNT",
					"COUPON", "COUPON_ONE_TIME", "COUPON_ACTIVE", 'UNPACK'
				);
				$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
				$arFilter = array(
					"ID" => $arDiscountIDs,
					"SITE_ID" => $siteID,
					"TYPE" => DISCOUNT_TYPE_STANDART,
					"ACTIVE" => "Y",
					"RENEWAL" => $strRenewal,
					"+<=ACTIVE_FROM" => $strDate,
					"+>=ACTIVE_TO" => $strDate
				);

				if (is_array($arDiscountCoupons))
				{
					$arFilter["+COUPON"] = $arDiscountCoupons;
				}

				$arDiscountList = array();
				CTimeZone::Disable();
				$rsPriceDiscounts = CCatalogDiscount::GetList(
					array(),
					$arFilter,
					false,
					false,
					$arSelect
				);
				CTimeZone::Enable();
				while ($arPriceDiscount = $rsPriceDiscounts->Fetch())
				{
					$arDiscountList[] = $arPriceDiscount;
				}
				self::$arCacheDiscountResult[$strCacheKey] = $arDiscountList;
			}
			else
			{
				$arDiscountList = self::$arCacheDiscountResult[$strCacheKey];
			}

			if (!empty($arDiscountList))
			{
				$discountApply = array();
				foreach ($arDiscountList as &$arPriceDiscount)
				{
					if (!isset($discountApply[$arPriceDiscount['ID']]) && $arPriceDiscount['COUPON_ACTIVE'] != 'N')
					{
						if (!$boolGenerate)
						{
							if (!isset(self::$arCacheProduct[$intProductID]))
							{
								$arProduct = array('ID' => $intProductID, 'IBLOCK_ID' => $intIBlockID);
								if (!self::__GenerateFields($arProduct))
									return false;
								$boolGenerate = true;
								self::$arCacheProduct[$intProductID] = $arProduct;
							}
							else
							{
								$arProduct = self::$arCacheProduct[$intProductID];
							}
						}
						$discountApply[$arPriceDiscount['ID']] = true;
						if (CCatalogDiscount::__Unpack($arProduct, $arPriceDiscount['UNPACK']))
						{
							unset($arPriceDiscount['UNPACK']);
							$arResult[] = $arPriceDiscount;
							$arResultID[] = $arPriceDiscount['ID'];

						}
					}
				}
				if (isset($arPriceDiscount))
					unset($arPriceDiscount);
				unset($discountApply);
			}
		}

		if ($boolSKU)
		{
			$boolSKU = false;
			$arSKU = false;
			$arSKUExt = CCatalogSKU::GetInfoByOfferIBlock($intIBlockID);
			if (!empty($arSKUExt) && is_array($arSKUExt))
			{
				if (isset($arProduct['PROPERTY_'.$arSKUExt['SKU_PROPERTY_ID'].'_VALUE']))
				{
					$arVal = $arProduct['PROPERTY_'.$arSKUExt['SKU_PROPERTY_ID'].'_VALUE'];
					$arSKU = array(
						'ID' => $arVal[0],
						'IBLOCK_ID' => $arSKUExt['PRODUCT_IBLOCK_ID'],
					);
					$boolSKU = true;
				}
			}
		}
		if ($boolSKU)
		{
			$arDiscountParent = CCatalogDiscount::GetDiscount($arSKU['ID'], $arSKU['IBLOCK_ID'], $arCatalogGroups, $arUserGroups, $strRenewal, $siteID, $arDiscountCoupons, false, false);
			if (!empty($arDiscountParent))
			{
				if (empty($arResult))
				{
					$arResult = $arDiscountParent;
				}
				else
				{
					foreach ($arDiscountParent as &$arOneParentDiscount)
					{
						if (in_array($arOneParentDiscount['ID'], $arResultID))
							continue;
						$arResult[] = $arOneParentDiscount;
						$arResultID[] = $arOneParentDiscount['ID'];
					}
					if (isset($arOneParentDiscount))
						unset($arOneParentDiscount);
				}
			}
		}

		if (!$boolGetIDS)
		{
			$arDiscSave = CCatalogDiscountSave::GetDiscount(array(
				'USER_ID' => 0,
				'USER_GROUPS' => $arUserGroups,
				'SITE_ID' => $siteID
			));
			if (!empty($arDiscSave))
			{
				$arResult = (!empty($arResult) ? array_merge($arResult, $arDiscSave) : $arDiscSave);
			}
		}
		else
		{
			$arResult = $arResultID;
		}

		foreach (GetModuleEvents("catalog", "OnGetDiscountResult", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, array(&$arResult));
		}

		return $arResult;
	}

	static public function HaveCoupons($ID, $excludeID = 0)
	{
		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$arFilter = array("DISCOUNT_ID" => $ID);

		$excludeID = intval($excludeID);
		if ($excludeID > 0)
			$arFilter["!ID"] = $excludeID;

		$dbRes = CCatalogDiscountCoupon::GetList(array(), $arFilter, false, array("nTopCount" => 1), array("ID"));
		if ($dbRes->Fetch())
			return true;
		else
			return false;
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::OnSetCouponList()
*/
	static public function OnSetCouponList($intUserID, $arCoupons, $arModules)
	{
		return CCatalogDiscountCoupon::OnSetCouponList($intUserID, $arCoupons, $arModules);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::OnClearCouponList()
*/
	static public function OnClearCouponList($intUserID, $arCoupons, $arModules)
	{
		return CCatalogDiscountCoupon::OnClearCouponList($intUserID, $arCoupons, $arModules);
	}

/*
* @deprecated deprecated since catalog 12.0.0
* @see CCatalogDiscountCoupon::OnDeleteCouponList()
*/
	static public function OnDeleteCouponList($intUserID, $arModules)
	{
		return CCatalogDiscountCoupon::OnDeleteCouponList($intUserID, $arModules);
	}

	static public function GetDiscountForProduct($arProduct, $arParams = false)
	{
		global $DB;

		$arResult = array();
		$arResultID = array();
		if (is_array($arProduct) && !empty($arProduct))
		{
			if (!is_array($arParams))
				$arParams = array();

			if (!isset($arProduct['ID']))
				$arProduct['ID'] = 0;
			$arProduct['ID'] = intval($arProduct['ID']);
			if (!isset($arProduct['IBLOCK_ID']))
				$arProduct['IBLOCK_ID'] = 0;
			$arProduct['IBLOCK_ID'] = intval($arProduct['IBLOCK_ID']);
			if (0 >= $arProduct['IBLOCK_ID'])
				return $arResult;

			$arFieldsParams = array();
			if (isset($arParams['TIME_ZONE']))
				$arFieldsParams['TIME_ZONE'] = $arParams['TIME_ZONE'];
			if (isset($arParams['PRODUCT']))
				$arFieldsParams['PRODUCT'] = $arParams['PRODUCT'];
			$boolGenerate = false;

			$arSelect = array("ID", "SITE_ID", "SORT", "NAME", "VALUE_TYPE", "VALUE", "CURRENCY", 'UNPACK');
			if (isset($arParams['DISCOUNT_FIELDS']) && !empty($arParams['DISCOUNT_FIELDS']) && is_array($arParams['DISCOUNT_FIELDS']))
				$arSelect = $arParams['DISCOUNT_FIELDS'];
			if (!in_array('UNPACK', $arSelect))
				$arSelect[] = 'UNPACK';

			$strDate = date($DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
			if (isset($arParams['CURRENT_DATE']))
				$strDate = $arParams['CURRENT_DATE'];

			$strRenewal = 'N';
			if (isset($arParams['RENEWAL']))
			{
				$strRenewal = $arParams['RENEWAL'];
			}
			if ('Y' != $strRenewal)
				$strRenewal = 'N';

			$arSiteList = array();
			if (isset($arParams['SITE_ID']))
			{
				if (!is_array($arParams['SITE_ID']))
					$arParams['SITE_ID'] = array($arParams['SITE_ID']);
				if (!empty($arParams['SITE_ID']))
					$arSiteList = $arParams['SITE_ID'];
			}
			if (empty($arSiteList))
			{
				$rsIBlockSites = CIBlock::GetSite($arProduct['IBLOCK_ID']);
				while ($arIBlockSite = $rsIBlockSites->Fetch())
				{
					$arSiteList[] = $arIBlockSite['SITE_ID'];
				}
			}

			$arFilter = array(
				"SITE_ID" => $arSiteList,
				"TYPE" => DISCOUNT_TYPE_STANDART,
				"ACTIVE" => "Y",
				"RENEWAL" => $strRenewal,
				"+<=ACTIVE_FROM" => $strDate,
				"+>=ACTIVE_TO" => $strDate,
				'COUPON' => ''
			);
			CTimeZone::Disable();
			$rsPriceDiscounts = CCatalogDiscount::GetList(
				array(),
				$arFilter,
				false,
				false,
				$arSelect
			);
			CTimeZone::Enable();
			while ($arPriceDiscount = $rsPriceDiscounts->Fetch())
			{
				if ($arPriceDiscount['COUPON_ACTIVE'] != 'N')
				{
					if (!$boolGenerate)
					{
						if (!isset(self::$arCacheProduct[$arProduct['ID']]))
						{
							if (!self::__GenerateFields($arProduct, $arFieldsParams))
								return $arResult;
							$boolGenerate = true;
							self::$arCacheProduct[$arProduct['ID']] = $arProduct['ID'];
						}
						else
						{
							$arProduct = self::$arCacheProduct[$arProduct['ID']];
						}
					}
					if (CCatalogDiscount::__Unpack($arProduct, $arPriceDiscount['UNPACK']))
					{
						unset($arPriceDiscount['UNPACK']);
						$arResult[] = $arPriceDiscount;
						$arResultID[] = $arPriceDiscount['ID'];
					}
				}
			}

			if (isset($arParams['SKU']) && 'Y' == $arParams['SKU'])
			{
				$arSKU = (
					isset($arParams['SKU_PARAMS'])
					? $arParams['SKU_PARAMS']
					: CCatalogSKU::GetInfoByOfferIBlock($arProduct['IBLOCK_ID'])
				);
				if (!empty($arSKU) && is_array($arSKU))
				{
					$arParent = array();
					$arParent['ID'] = 0;
					$arParent['IBLOCK_ID'] = $arSKU['PRODUCT_IBLOCK_ID'];
					if (
						isset($arProduct['PROPERTY_'.$arSKU['SKU_PROPERTY_ID'].'_VALUE'])
						&& is_array($arProduct['PROPERTY_'.$arSKU['SKU_PROPERTY_ID'].'_VALUE'])
					)
					{
						$intParentID = intval(current($arProduct['PROPERTY_'.$arSKU['SKU_PROPERTY_ID'].'_VALUE']));
						if (0 < $intParentID)
						{
							$arParent['ID'] = $intParentID;
						}
					}
					$arParentParams = array();
					if (isset($arParams['TIME_ZONE']))
						$arParentParams['TIME_ZONE'] = $arParams['TIME_ZONE'];
					if (isset($arParams['DISCOUNT_FIELDS']))
						$arParentParams['DISCOUNT_FIELDS'] = $arParams['DISCOUNT_FIELDS'];
					$arParentParams['RENEWAL'] = $strRenewal;
					$arParentParams['SITE_ID'] = $arSiteList;
					$arParentParams['CURRENT_DATE'] = $strDate;
					$arDiscountParent = self::GetDiscountForProduct($arParent, $arParentParams);
					if (!empty($arDiscountParent))
					{
						if (empty($arResult))
						{
							$arResult = $arDiscountParent;
						}
						else
						{
							foreach ($arDiscountParent as &$arOneParentDiscount)
							{
								if (in_array($arOneParentDiscount['ID'], $arResultID))
									continue;
								$arResult[] = $arOneParentDiscount;
								$arResultID[] = $arOneParentDiscount['ID'];
							}
							if (isset($arOneParentDiscount))
								unset($arOneParentDiscount);
						}
					}
				}
			}
		}
		return $arResult;
	}

	static public function GetRestrictions($arParams, $boolKeys = true, $boolRevert = true)
	{
		$boolKeys = !!$boolKeys;
		$boolRevert = !!$boolRevert;
		if (!is_array($arParams) || empty($arParams))
			return array();
		$arFilter = array('RESTRICTIONS' => true);
		if (isset($arParams['USER_GROUPS']) && !empty($arParams['USER_GROUPS']))
		{
			$arFilter['USER_GROUP_ID'] = $arParams['USER_GROUPS'];
		}
		if (isset($arParams['PRICE_TYPES']) && !empty($arParams['PRICE_TYPES']))
		{
			$arFilter['PRICE_TYPE_ID'] = $arParams['PRICE_TYPES'];
		}
		if ($boolKeys)
		{
			return CCatalogDiscount::__GetDiscountID($arFilter);
		}
		else
		{
			$arResult = CCatalogDiscount::__GetDiscountID($arFilter);
			if (!empty($arResult) && !empty($arResult['RESTRICTIONS']))
			{
				if ($boolRevert)
				{
					foreach ($arResult['RESTRICTIONS'] as &$arOneDiscount)
					{
						$arOneDiscount['USER_GROUP'] = array_keys($arOneDiscount['USER_GROUP']);
						$arOneDiscount['PRICE_TYPE'] = array_keys($arOneDiscount['PRICE_TYPE']);
					}
					if (isset($arOneDiscount))
						unset($arOneDiscount);
				}
			}
			return $arResult;
		}
	}

	static public function CheckDiscount($arProduct, $arDiscount)
	{
		if (empty($arProduct) || !is_array($arProduct))
			return false;
		if (empty($arDiscount) || !is_array(empty($arDiscount)) || !isset($arDiscount['UNPACK']))
			return false;
		return CCatalogDiscount::__Unpack($arProduct, $arDiscount['UNPACK']);
	}

	static public function ExtendBasketItems(&$arBasket, $arExtend)
	{
		$arFields = array(
			'ID',
			'IBLOCK_ID',
			'XML_ID',
			'CODE',
			'TAGS',
		);
		$arCatFields = array(
			'ID',
			'QUANTITY'
		);
		if (!empty($arExtend) && is_array($arExtend))
		{
			if (isset($arExtend['catalog']) && !empty($arExtend['catalog']) && is_array($arExtend['catalog']))
			{
				$boolFields = false;
				if (isset($arExtend['catalog']['fields']))
					$boolFields = (boolean)$arExtend['catalog']['fields'];
				$boolProps = false;
				if (isset($arExtend['catalog']['props']))
					$boolProps = (boolean)$arExtend['catalog']['props'];
				if ($boolFields || $boolProps)
				{
					$boolExtend = false;
					$arMap = array();
					$arItemID = array();
					foreach ($arBasket as $strKey => $arOneRow)
					{
						if (isset($arOneRow['MODULE']) && 'catalog' == $arOneRow['MODULE'])
						{
							$boolExtend = true;
							$intProductID = intval($arOneRow['PRODUCT_ID']);
							if (0 < $intProductID)
							{
								$arIDS[$intProductID] = true;
								if (!isset($arMap[$intProductID]))
									$arMap[$intProductID] = array();
								$arMap[$intProductID][] = $strKey;
							}
						}
					}
					if ($boolExtend)
					{
						$arBasketResult = array();
						$arIDS = array_keys($arIDS);
						$rsItems = CIBlockElement::GetList(array(), array('ID' => $arIDS), false, false, $arFields);
						while ($obItem = $rsItems->GetNextElement())
						{
							$arBasketData = array();
							$arItem = $obItem->GetFields();
							$arItem['ID'] = intval($arItem['ID']);
							if ($boolFields)
							{
								$arBasketData['IBLOCK_ID'] = intval($arItem['IBLOCK_ID']);
								$arBasketData['XML_ID'] = (string)$arItem['~XML_ID'];
								$arBasketData['CODE'] = (string)$arItem['~CODE'];
								$arBasketData['TAGS'] = (string)$arItem['~TAGS'];
								$arProductSections = self::__GetSectionList($arItem['IBLOCK_ID'], $arItem['ID']);
								if (false !== $arProductSections)
									$arBasketData['SECTION_ID'] = $arProductSections;
								else
									$arBasketData['SECTION_ID'] = array();
							}
							if ($boolProps)
							{
								$arProps = $obItem->GetProperties(array(), array('ACTIVE' => 'Y'));
								self::__ConvertProperties($arBasketData, $arProps, array('TIME_ZONE' => 'N'));
							}
							$arBasketResult[$arItem['ID']] = array();
							$arBasketResult[$arItem['ID']] = $arBasketData;
						}
						$rsProducts = CCatalogProduct::GetList(array(), array('ID' => $arIDS), false, false, $arCatFields);
						while ($arProduct = $rsProducts->Fetch())
						{
							$arProduct['ID'] = intval($arProduct['ID']);
							if (!isset($arBasketResult[$arProduct['ID']]))
								$arBasketResult[$arProduct['ID']] = array();
							$arBasketResult[$arProduct['ID']]['CATALOG_QUANTITY'] = doubleval($arProduct['QUANTITY']);
						}
						if (!empty($arBasketResult))
						{
							foreach ($arBasketResult as $intProductID => $arBasketData)
							{
								foreach ($arMap[$intProductID] as $mxRowID)
								{
									$arBasket[$mxRowID]['CATALOG'] = $arBasketData;
								}
							}
						}
					}
				}
			}
		}
	}

	protected function __GenerateFields(&$arProduct, $arParams = false)
	{
		$boolResult = false;
		if (!empty($arProduct) && is_array($arProduct))
		{
			if (!isset($arProduct['IBLOCK_ID']))
				$arProduct['IBLOCK_ID'] = 0;
			$arProduct['IBLOCK_ID'] = intval($arProduct['IBLOCK_ID']);
			if (0 < $arProduct['IBLOCK_ID'])
			{
				if (!is_array($arParams))
					$arParams = array();

				if (!isset($arProduct['ID']))
					$arProduct['ID'] = 0;
				$arProduct['ID'] = intval($arProduct['ID']);
				if (0 < $arProduct['ID'])
				{
					if (isset($arParams['PRODUCT']) && 'Y' == $arParams['PRODUCT'])
					{
						$arDefaultProduct = array(
							'DATE_ACTIVE_FROM' => '',
							'DATE_ACTIVE_TO' => '',
							'SORT' => 0,
							'PREVIEW_TEXT' => '',
							'DETAIL_TEXT' => '',
							'TAGS' => '',
							'DATE_CREATE' => '',
							'TIMESTAMP_X' => '',
							'CREATED_BY' => 0,
							'MODIFIED_BY' => 0,
							'CATALOG_QUANTITY' => '',
							'CATALOG_WEIGHT' => '',
							'CATALOG_VAT_ID' => '',
							'CATALOG_VAT_INCLUDED' => ''
						);
						$arProduct = array_merge($arDefaultProduct, $arProduct);

						static $intTimeOffset = false;
						if (false === $intTimeOffset)
							$intTimeOffset = CTimeZone::GetOffset();
						if (isset($arParams['TIME_ZONE']) && 'N' == $arParams['TIME_ZONE'])
							$intTimeOffset = 0;

						if (!isset($arProduct['SECTION_ID']))
						{
							$arProductSections = self::__GetSectionList($arProduct['IBLOCK_ID'], $arProduct['ID']);
							if (false !== $arProductSections)
								$arProduct['SECTION_ID'] = $arProductSections;
							else
								$arProduct['SECTION_ID'] = array();
						}
						else
						{
							if (!is_array($arProduct['SECTION_ID']))
								$arProduct['SECTION_ID'] = array($arProduct['SECTION_ID']);
							CatalogClearArray($arProduct['SECTION_ID']);
						}

						if (!empty($arProduct['DATE_ACTIVE_FROM']))
						{
							$intStackTimestamp = intval($arProduct['DATE_ACTIVE_FROM']);
							if ($intStackTimestamp.'!' != $arProduct['DATE_ACTIVE_FROM'].'!')
								$arProduct['DATE_ACTIVE_FROM'] = intval(MakeTimeStamp($arProduct['DATE_ACTIVE_FROM'])) - $intTimeOffset;
							else
								$arProduct['DATE_ACTIVE_FROM'] = $intStackTimestamp;
						}

						if (!empty($arProduct['DATE_ACTIVE_TO']))
						{
							$intStackTimestamp = intval($arProduct['DATE_ACTIVE_TO']);
							if ($intStackTimestamp.'!' != $arProduct['DATE_ACTIVE_TO'].'!')
								$arProduct['DATE_ACTIVE_TO'] = intval(MakeTimeStamp($arProduct['DATE_ACTIVE_TO'])) - $intTimeOffset;
							else
								$arProduct['DATE_ACTIVE_TO'] = $intStackTimestamp;
						}

						$arProduct['SORT'] = intval($arProduct['SORT']);

						if (!empty($arProduct['DATE_CREATE']))
						{
							$intStackTimestamp = intval($arProduct['DATE_CREATE']);
							if ($intStackTimestamp.'!' != $arProduct['DATE_CREATE'].'!')
								$arProduct['DATE_CREATE'] = intval(MakeTimeStamp($arProduct['DATE_CREATE'])) - $intTimeOffset;
							else
								$arProduct['DATE_CREATE'] = $intStackTimestamp;
						}

						if (!empty($arProduct['TIMESTAMP_X']))
						{
							$intStackTimestamp = intval($arProduct['TIMESTAMP_X']);
							if ($intStackTimestamp.'!' != $arProduct['TIMESTAMP_X'].'!')
								$arProduct['TIMESTAMP_X'] = intval(MakeTimeStamp($arProduct['TIMESTAMP_X'])) - $intTimeOffset;
							else
								$arProduct['TIMESTAMP_X'] = $intStackTimestamp;
						}

						$arProduct['CREATED_BY'] = intval($arProduct['CREATED_BY']);
						$arProduct['MODIFIED_BY'] = intval($arProduct['MODIFIED_BY']);

						if (isset($arProduct['QUANTITY']))
						{
							$arProduct['CATALOG_QUANTITY'] = $arProduct['QUANTITY'];
							unset($arProduct['QUANTITY']);
						}
						if ('' != $arProduct['CATALOG_QUANTITY'])
							$arProduct['CATALOG_QUANTITY'] = doubleval($arProduct['CATALOG_QUANTITY']);

						if (isset($arProduct['WEIGHT']))
						{
							$arProduct['CATALOG_WEIGHT'] = $arProduct['WEIGHT'];
							unset($arProduct['WEIGHT']);
						}
						if ('' != $arProduct['CATALOG_WEIGHT'])
						$arProduct['CATALOG_WEIGHT'] = doubleval($arProduct['CATALOG_WEIGHT']);

						if (isset($arProduct['VAT_ID']))
						{
							$arProduct['CATALOG_VAT_ID'] = $arProduct['VAT_ID'];
							unset($arProduct['VAT_ID']);
						}
						if ('' != $arProduct['CATALOG_VAT_ID'])
							$arProduct['CATALOG_VAT_ID'] = intval($arProduct['CATALOG_VAT_ID']);

						if (isset($arProduct['VAT_INCLUDED']))
						{
							$arProduct['CATALOG_VAT_INCLUDED'] = $arProduct['VAT_INCLUDED'];
							unset($arProduct['VAT_INCLUDED']);
						}

						$arPropParams = array();
						if (isset($arParams['TIME_ZONE']) && 'N' == $arParams['TIME_ZONE'])
							$arPropParams['TIME_ZONE'] = 'N';

						if (isset($arProduct['PROPERTIES']))
						{
							if (!empty($arProduct['PROPERTIES']) && is_array($arProduct['PROPERTIES']))
							{
								self::__ConvertProperties($arProduct, $arProduct['PROPERTIES'], $arPropParams);
							}
							unset($arProduct['PROPERTIES']);
						}
					}
					else
					{
						$arSelect = array('ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO',
							'SORT', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DATE_CREATE', 'DATE_CREATE_UNIX', 'CREATED_BY', 'TIMESTAMP_X', 'TIMESTAMP_X_UNIX', 'MODIFIED_BY', 'TAGS', 'CATALOG_QUANTITY');
						CTimeZone::Disable();
						$rsProducts = CIBlockElement::GetList(array(), array('ID' => $arProduct['ID'], 'IBLOCK_ID' => $arProduct['IBLOCK_ID']), false, false, $arSelect);
						CTimeZone::Enable();
						if (!($obProduct = $rsProducts->GetNextElement(false,true)))
							return $boolResult;

						$arProduct = array();
						$arProductFields = $obProduct->GetFields();

						$arProduct['ID'] = intval($arProductFields['ID']);
						$arProduct['IBLOCK_ID'] = intval($arProductFields['IBLOCK_ID']);

						$arProduct['SECTION_ID'] = array();
						$arProductSections = self::__GetSectionList($arProduct['IBLOCK_ID'], $arProduct['ID']);
						if (false !== $arProductSections)
							$arProduct['SECTION_ID'] = $arProductSections;

						$arProduct['CODE'] = (string)$arProductFields['~CODE'];
						$arProduct['XML_ID'] = (string)$arProductFields['~XML_ID'];
						$arProduct['NAME'] = $arProductFields['~NAME'];

						$arProduct['ACTIVE'] = $arProductFields['ACTIVE'];

						$arProduct['DATE_ACTIVE_FROM'] = (string)$arProductFields['DATE_ACTIVE_FROM'];
						if (!empty($arProduct['DATE_ACTIVE_FROM']))
							$arProduct['DATE_ACTIVE_FROM'] = intval(MakeTimeStamp($arProduct['DATE_ACTIVE_FROM']));

						$arProduct['DATE_ACTIVE_TO'] = (string)$arProductFields['DATE_ACTIVE_TO'];
						if (!empty($arProduct['DATE_ACTIVE_TO']))
							$arProduct['DATE_ACTIVE_TO'] = intval(MakeTimeStamp($arProduct['DATE_ACTIVE_TO']));

						$arProduct['SORT'] = intval($arProductFields['SORT']);

						$arProduct['PREVIEW_TEXT'] = (string)$arProductFields['~PREVIEW_TEXT'];
						$arProduct['DETAIL_TEXT'] = (string)$arProductFields['~DETAIL_TEXT'];
						$arProduct['TAGS'] = (string)$arProductFields['~TAGS'];

						if (isset($arProductFields['DATE_CREATE_UNIX']))
						{
							$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE_UNIX'];
							if ('' != $arProduct['DATE_CREATE'])
								$arProduct['DATE_CREATE'] = intval($arProduct['DATE_CREATE']);
						}
						else
						{
							$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE'];
							if ('' != $arProduct['DATE_CREATE'])
								$arProduct['DATE_CREATE'] = intval(MakeTimeStamp($arProduct['DATE_CREATE']));
						}

						if (isset($arProductFields['TIMESTAMP_X_UNIX']))
						{
							$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X_UNIX'];
							if ('' != $arProduct['TIMESTAMP_X'])
								$arProduct['TIMESTAMP_X'] = intval($arProduct['TIMESTAMP_X']);
						}
						else
						{
							$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X'];
							if ('' != $arProduct['TIMESTAMP_X'])
								$arProduct['TIMESTAMP_X'] = intval(MakeTimeStamp($arProduct['TIMESTAMP_X']));
						}

						$arProduct['CREATED_BY'] = intval($arProductFields['CREATED_BY']);
						$arProduct['MODIFIED_BY'] = intval($arProductFields['MODIFIED_BY']);

						$arProduct['CATALOG_QUANTITY'] = (string)$arProductFields['CATALOG_QUANTITY'];
						if ('' != $arProduct['CATALOG_QUANTITY'])
							$arProduct['CATALOG_QUANTITY'] = doubleval($arProduct['CATALOG_QUANTITY']);
						$arProduct['CATALOG_WEIGHT'] = (string)$arProductFields['CATALOG_WEIGHT'];
						if ('' != $arProduct['CATALOG_WEIGHT'])
							$arProduct['CATALOG_WEIGHT'] = doubleval($arProduct['CATALOG_WEIGHT']);

						$arProduct['CATALOG_VAT_ID'] = (string)$arProductFields['CATALOG_VAT_ID'];
						if ('' != $arProduct['CATALOG_VAT_ID'])
							$arProduct['CATALOG_VAT_ID'] = intval($arProduct['CATALOG_VAT_ID']);

						$arProduct['CATALOG_VAT_INCLUDED'] = (string)$arProductFields['CATALOG_VAT_INCLUDED'];

						unset($arProductFields);
						if (!isset(self::$arCacheProductProperties[$arProduct['ID']]))
						{
							$arProps = $obProduct->GetProperties(array(), array('ACTIVE' => 'Y'));
						}
						else
						{
							$arProps = self::$arCacheProductProperties[$arProduct['ID']];
						}
						self::__ConvertProperties($arProduct, $arProps, array('TIME_ZONE' => 'N'));
						if (isset(self::$arCacheProductProperties[$arProduct['ID']]))
							unset(self::$arCacheProductProperties[$arProduct['ID']]);
						if (isset(self::$arCacheProductSections[$arProduct['ID']]))
							unset(self::$arCacheProductSections[$arProduct['ID']]);
					}
				}
				else
				{
					$arProduct['ID'] = 0;
					if (!isset($arProduct['SECTION_ID']))
						$arProduct['SECTION_ID'] = array();
					if (!is_array($arProduct['SECTION_ID']))
						$arProduct['SECTION_ID'] = array($arProduct['SECTION_ID']);
					CatalogClearArray($arProduct['SECTION_ID']);

					$arProduct['DATE_ACTIVE_FROM'] = '';
					$arProduct['DATE_ACTIVE_TO'] = '';
					$arProduct['SORT'] = 500;

					$arProduct['PREVIEW_TEXT'] = '';
					$arProduct['DETAIL_TEXT'] = '';
					$arProduct['TAGS'] = '';

					$arProduct['DATE_CREATE'] = '';
					$arProduct['TIMESTAMP_X'] = '';

					$arProduct['CREATED_BY'] = 0;
					$arProduct['MODIFIED_BY'] = 0;

					$arProduct['CATALOG_QUANTITY'] = '';
					$arProduct['CATALOG_WEIGHT'] = '';
					$arProduct['CATALOG_VAT_ID'] = '';
					$arProduct['CATALOG_VAT_INCLUDED'] = '';
				}
				$boolResult = true;
			}
		}
		return $boolResult;
	}

	protected function __GetSectionList($intIBlockID, $intProductID)
	{
		$mxResult = false;
		$intIBlockID = intval($intIBlockID);
		$intProductID = intval($intProductID);
		if (0 < $intIBlockID && 0 < $intProductID)
		{
			$mxResult = array();
			$arProductSections = array();
			if (!isset(self::$arCacheProductSections[$intProductID]))
			{
				$rsSections = CIBlockElement::GetElementGroups($intProductID, true, array("ID", "IBLOCK_SECTION_ID", "IBLOCK_ELEMENT_ID"));
				while ($arSection = $rsSections->Fetch())
				{
					$arSection['ID'] = intval($arSection['ID']);
					$arSection['IBLOCK_SECTION_ID'] = intval($arSection['IBLOCK_SECTION_ID']);
					$arProductSections[] = $arSection;
				}
				if (isset($arSection))
					unset($arSection);
				self::$arCacheProductSections[$intProductID] = $arProductSections;
			}
			else
			{
				$arProductSections = self::$arCacheProductSections[$intProductID];
			}
			if (!empty($arProductSections))
			{
				foreach ($arProductSections as &$arSection)
				{
					$mxResult[$arSection['ID']] = true;
					if (0 < $arSection['IBLOCK_SECTION_ID'])
					{
						if (!isset(self::$arCacheProductSectionChain[$arSection['ID']]))
						{
							self::$arCacheProductSectionChain[$arSection['ID']] = array();
							$rsParents = CIBlockSection::GetNavChain($intIBlockID, $arSection['ID'], array('ID'));
							while ($arParent = $rsParents->Fetch())
							{
								$arParent["ID"] = intval($arParent["ID"]);
								$mxResult[$arParent["ID"]] = true;
								self::$arCacheProductSectionChain[$arSection['ID']][] = $arParent["ID"];
							}
						}
						else
						{
							foreach (self::$arCacheProductSectionChain[$arSection['ID']] as $intOneID)
							{
								$mxResult[$intOneID] = true;
							}
							if (isset($intOneID))
								unset($intOneID);
						}
					}
				}
				if (isset($arSection))
					unset($arSection);
			}
			if (!empty($mxResult))
			{
				$mxResult = array_keys($mxResult);
				sort($mxResult);
			}
		}
		return $mxResult;
	}

	protected function __ConvertProperties(&$arProduct, &$arProps, $arParams = false)
	{
		if (is_array($arProps) && !empty($arProps))
		{
			if (!is_array($arParams))
				$arParams = array();
			static $intTimeOffset = false;
			if (false === $intTimeOffset)
				$intTimeOffset = CTimeZone::GetOffset();
			if (isset($arParams['TIME_ZONE']) && 'N' == $arParams['TIME_ZONE'])
				$intTimeOffset = 0;

			foreach ($arProps as &$arOneProp)
			{
				if ('F' == $arOneProp['PROPERTY_TYPE'])
					continue;
				$boolCheck = false;
				if ('N' == $arOneProp['MULTIPLE'])
				{
					if (isset($arOneProp['USER_TYPE']) && !empty($arOneProp['USER_TYPE']))
					{
						switch($arOneProp['USER_TYPE'])
						{
							case 'DateTime':
								$arOneProp['VALUE'] = (string)$arOneProp['VALUE'];
								if ('' != $arOneProp['VALUE'])
								{
									$intStackTimestamp = intval($arOneProp['VALUE']);
									if ($intStackTimestamp.'!' != $arOneProp['VALUE'].'!')
										$arOneProp['VALUE'] = intval(MakeTimeStamp($arOneProp['VALUE'])) - $intTimeOffset;
									else
										$arOneProp['VALUE'] = $intStackTimestamp;
								}
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
								$boolCheck = true;
								break;
						}
					}
					if (!$boolCheck)
					{
						if ('L' == $arOneProp['PROPERTY_TYPE'])
						{
							$arOneProp['VALUE_ENUM_ID'] = intval($arOneProp['VALUE_ENUM_ID']);
							if (0 < $arOneProp['VALUE_ENUM_ID'])
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE_ENUM_ID'];
							else
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = -1;
						}
						elseif ('E' == $arOneProp['PROPERTY_TYPE'] || 'G' == $arOneProp['PROPERTY_TYPE'])
						{
							$arOneProp['VALUE'] = intval($arOneProp['VALUE']);
							if (0 < $arOneProp['VALUE'])
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
							else
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = -1;
						}
						else
						{
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
						}
					}
				}
				else
				{
					if (isset($arOneProp['USER_TYPE']) && !empty($arOneProp['USER_TYPE']))
					{
						switch($arOneProp['USER_TYPE'])
						{
							case 'DateTime':
								$arValues = array();
								if (is_array($arOneProp['VALUE']) && !empty($arOneProp['VALUE']))
								{
									foreach ($arOneProp['VALUE'] as &$strOneValue)
									{
										$strOneValue = (string)$strOneValue;
										if ('' != $strOneValue)
										{
											$intStackTimestamp = intval($strOneValue);
											if ($intStackTimestamp.'!' != $strOneValue.'!')
												$strOneValue = intval(MakeTimeStamp($strOneValue)) - $intTimeOffset;
											else
												$strOneValue = $intStackTimestamp;
										}
										$arValues[] = $strOneValue;
									}
									if (isset($strOneValue))
										unset($strOneValue);
								}
								$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arValues;
								$boolCheck = true;
								break;
						}
					}
					if (!$boolCheck)
					{
						if ('L' == $arOneProp['PROPERTY_TYPE'])
						{
							$arValues = array();
							if (is_array($arOneProp['VALUE_ENUM_ID']) && !empty($arOneProp['VALUE_ENUM_ID']))
							{
								foreach ($arOneProp['VALUE_ENUM_ID'] as &$intOneValue)
								{
									$intOneValue = intval($intOneValue);
									if (0 < $intOneValue)
										$arValues[] = $intOneValue;
								}
								if (isset($intOneValue))
									unset($intOneValue);
							}
							if (empty($arValues))
								$arValues = array(-1);
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arValues;
						}
						elseif ('E' == $arOneProp['PROPERTY_TYPE'] || 'G' == $arOneProp['PROPERTY_TYPE'])
						{
							$arValues = array();
							if (is_array($arOneProp['VALUE']) && !empty($arOneProp['VALUE']))
							{
								foreach ($arOneProp['VALUE'] as &$intOneValue)
								{
									$intOneValue = intval($intOneValue);
									if (0 < $intOneValue)
										$arValues[] = $intOneValue;
								}
								if (isset($intOneValue))
									unset($intOneValue);
							}
							if (empty($arValues))
								$arValues = array(-1);
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arValues;
						}
						else
						{
							$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = $arOneProp['VALUE'];
						}
					}
				}
				if (!is_array($arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE']))
					$arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE'] = array($arProduct['PROPERTY_'.$arOneProp['ID'].'_VALUE']);
			}
			if (isset($arOneProp))
				unset($arOneProp);
		}
	}

	protected function __ParseArrays(&$arFields)
	{
		global $APPLICATION;

		$arMsg = array();
		$boolResult = true;

		$arResult = array(
		);

		if (!self::__CheckOneEntity($arFields, 'GROUP_IDS'))
		{
			$arMsg[] = array('id' => 'GROUP_IDS', "text" => GetMessage('BT_MOD_CATALOG_DISC_ERR_PARSE_USER_GROUP'));
			$boolResult = false;
		}
		if (!self::__CheckOneEntity($arFields, 'CATALOG_GROUP_IDS'))
		{
			$arMsg[] = array('id' => 'CATALOG_GROUP_IDS', "text" => GetMessage('BT_MOD_CATALOG_DISC_ERR_PARSE_PRICE_TYPE'));
			$boolResult = false;
		}

		if ($boolResult)
		{
			$arTempo = array(
				'USER_GROUP_ID' => $arFields['GROUP_IDS'],
				'PRICE_TYPE_ID' => $arFields['CATALOG_GROUP_IDS'],
			);

			$arOrder = array(
				'USER_GROUP_ID',
				'PRICE_TYPE_ID',
			);

			self::__ArrayMultiple($arOrder, $arResult, $arTempo);
			unset($arTempo);
		}

		if (!$boolResult)
		{
			$obError = new CAdminException($arMsg);
			$APPLICATION->ResetException();
			$APPLICATION->ThrowException($obError);
			return $boolResult;
		}
		else
		{
			return $arResult;
		}
	}

	protected function __CheckOneEntity(&$arFields, $strEntityID)
	{
		$boolResult = false;
		$strEntityID = trim(strval($strEntityID));
		if (!empty($strEntityID))
		{
			if (is_array($arFields) && !empty($arFields))
			{
				if (is_set($arFields, $strEntityID))
				{
					if (!is_array($arFields[$strEntityID]))
						$arFields[$strEntityID] = array($arFields[$strEntityID]);
					$arValid = array();
					foreach ($arFields[$strEntityID] as &$value)
					{
						$value = intval($value);
						if ($value > 0)
							$arValid[] = $value;
					}
					if (isset($value))
						unset($value);
					if (!empty($arValid))
					{
						$arValid = array_unique($arValid);
					}
					$arFields[$strEntityID] = $arValid;

					if (empty($arFields[$strEntityID]))
					{
						$arFields[$strEntityID] = array(-1);
					}
				}
				else
				{
					$arFields[$strEntityID] = array(-1);
				}
			}
			else
			{
				$arFields[$strEntityID] = array(-1);
			}
			$boolResult = true;
		}
		return $boolResult;
	}

	protected function __ArrayMultiple($arOrder, &$arResult, $arTuple, $arTemp = array())
	{
		if (empty($arTuple))
		{
			$arResult[] = array(
				'EQUAL' => array_combine($arOrder, $arTemp),
			);
		}
		else
		{
			$head = array_shift($arTuple);
			$arTemp[] = false;
			if (is_array($head))
			{
				if (empty($head))
				{
					$arTemp[count($arTemp)-1] = -1;
					self::__ArrayMultiple($arOrder, $arResult, $arTuple, $arTemp);
				}
				else
				{
					foreach ($head as &$value)
					{
						$arTemp[count($arTemp)-1] = $value;
						self::__ArrayMultiple($arOrder, $arResult, $arTuple, $arTemp);
					}
					if (isset($value))
						unset($value);
				}
			}
			else
			{
				$arTemp[count($arTemp)-1] = $head;
				self::__ArrayMultiple($arOrder, $arResult, $arTuple, $arTemp);
			}
		}
	}

	protected function __Unpack($arProduct, $strUnpack)
	{
		if (empty($strUnpack))
			return false;
		return eval('return '.$strUnpack.';');
	}

	protected function __ConvertOldConditions($strAction, &$arFields)
	{
		$strAction = ToUpper($strAction);
		if (!is_set($arFields, 'CONDITIONS'))
		{
			$arIBlockList = array();
			$arSectionList = array();
			$arElementList = array();
			$arConditions = array(
				'CLASS_ID' => 'CondGroup',
				'DATA' => array(
					'All' => 'AND',
					'True' => 'True',
				),
				'CHILDREN' => array(),
			);
			$intEntityCount = 0;

			$arIBlockList = self::__ConvertOldOneEntity($arFields, 'IBLOCK_IDS');
			if (!empty($arIBlockList))
			{
				$intEntityCount++;
			}

			$arSectionList = self::__ConvertOldOneEntity($arFields, 'SECTION_IDS');
			if (!empty($arSectionList))
			{
				$intEntityCount++;
			}

			$arElementList = self::__ConvertOldOneEntity($arFields, 'PRODUCT_IDS');
			if (!empty($arElementList))
			{
				$intEntityCount++;
			}

			if (0 < $intEntityCount)
			{
				self::__AddOldOneEntity($arConditions, 'CondIBIBlock', $arIBlockList, (1 == $intEntityCount));
				self::__AddOldOneEntity($arConditions, 'CondIBSection', $arSectionList, (1 == $intEntityCount));
				self::__AddOldOneEntity($arConditions, 'CondIBElement', $arElementList, (1 == $intEntityCount));
			}

			if ('ADD' == $strAction)
			{
				$arFields['CONDITIONS'] = $arConditions;
			}
			else
			{
				if (0 < $intEntityCount)
				{
					$arFields['CONDITIONS'] = $arConditions;
				}
			}
		}
	}

	protected function __ConvertOldOneEntity(&$arFields, $strEntityID)
	{
		$arResult = false;
		if (!empty($strEntityID))
		{
			$arResult = array();
			if (isset($arFields[$strEntityID]))
			{
				if (!is_array($arFields[$strEntityID]))
					$arFields[$strEntityID] = array($arFields[$strEntityID]);
				foreach ($arFields[$strEntityID] as &$value)
				{
					$value = intval($value);
					if ($value > 0)
						$arResult[] = $value;
				}
				if (isset($value))
					unset($value);
				if (!empty($arResult))
				{
					$arResult = array_values(array_unique($arResult));
				}
			}
		}
		return $arResult;
	}

	protected function __AddOldOneEntity(&$arConditions, $strCondID, $arEntityValues, $boolOneEntity)
	{
		if (!empty($strCondID))
		{
			$boolOneEntity = (true == $boolOneEntity ? true : false);
			if (!empty($arEntityValues))
			{
				if (1 < count($arEntityValues))
				{
					$arList = array();
					foreach ($arEntityValues as &$intItemID)
					{
						$arList[] = array(
							'CLASS_ID' => $strCondID,
							'DATA' => array(
								'logic' => 'Equal',
								'value' => $intItemID
							),
						);
					}
					if (isset($intItemID))
						unset($intItemID);
					if ($boolOneEntity)
					{
						$arConditions = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
					else
					{
						$arConditions['CHILDREN'][] = array(
							'CLASS_ID' => 'CondGroup',
							'DATA' => array(
								'All' => 'OR',
								'True' => 'True',
							),
							'CHILDREN' => $arList,
						);
					}
				}
				else
				{
					$arConditions['CHILDREN'][] = array(
						'CLASS_ID' => $strCondID,
						'DATA' => array(
							'logic' => 'Equal',
							'value' => current($arEntityValues)
						),
					);
				}
			}
		}
	}

	protected function __GetConditionValues(&$arFields)
	{
		$arResult = false;
		if (isset($arFields['CONDITIONS']) && !empty($arFields['CONDITIONS']))
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
				$obCond = new CCatalogCondTree();
				$boolCond = $obCond->Init(BT_COND_MODE_SEARCH, BT_COND_BUILD_CATALOG, array());
				if ($boolCond)
				{
					$arResult = $obCond->GetConditionValues($arConditions);
				}
			}
		}
		return $arResult;
	}

	protected function __GetOldOneEntity(&$arFields, &$arCondList, $strEntityID, $strCondID)
	{
		if (is_array($arCondList) && !empty($arCondList))
		{
			$arFields[$strEntityID] = array();
			if (isset($arCondList[$strCondID]) && !empty($arCondList[$strCondID]) && is_array($arCondList[$strCondID]))
			{
				if (isset($arCondList[$strCondID]['VALUES']) && !empty($arCondList[$strCondID]['VALUES']) && is_array($arCondList[$strCondID]['VALUES']))
				{
					$arCheck = array();
					foreach ($arCondList[$strCondID]['VALUES'] as &$intValue)
					{
						$intValue = intval($intValue);
						if (0 < $intValue)
							$arCheck[] = $intValue;
					}
					if (isset($intValue))
						unset($intValue);
					$arCheck = array_values(array_unique($arCheck));
					$arFields[$strEntityID] = $arCheck;
				}
			}
		}
	}

	protected function __UpdateOldOneEntity($intID, &$arFields, $arParams, $boolUpdate)
	{
		global $DB;

		$boolUpdate = (false === $boolUpdate ? false : true);
		$intID = intval($intID);
		if (0 >= $intID)
			return;
		if (is_array($arParams) && !empty($arParams))
		{
			if (!empty($arParams['ENTITY_ID']) && !empty($arParams['TABLE_ID']) && !empty($arParams['FIELD_ID']))
			{
				if (isset($arFields[$arParams['ENTITY_ID']]))
				{
					if ($boolUpdate)
					{
						$DB->Query("DELETE FROM ".$arParams['TABLE_ID']." WHERE DISCOUNT_ID = ".$intID, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
					if (!empty($arFields[$arParams['ENTITY_ID']]))
					{
						foreach ($arFields[$arParams['ENTITY_ID']] as &$intValue)
						{
							$strSql = "INSERT INTO ".$arParams['TABLE_ID']."(DISCOUNT_ID, ".$arParams['FIELD_ID'].") VALUES(".$intID.", ".$intValue.")";
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						}
						if (isset($intValue))
							unset($intValue);
					}
				}
			}
		}
	}

	public static function SetDiscountFilterCache($arDiscountIDs, $arCatalogGroups, $arUserGroups)
	{
		if (!is_array($arDiscountIDs))
			return false;
		CatalogClearArray($arDiscountIDs);

		if (!is_array($arCatalogGroups))
			return false;
		CatalogClearArray($arCatalogGroups);
		if (empty($arCatalogGroups))
			return false;

		if (!is_array($arUserGroups))
			return false;
		CatalogClearArray($arUserGroups);
		if (empty($arUserGroups))
			return false;

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		self::$arCacheDiscountFilter[$strCacheKey] = $arDiscountIDs;

		return true;
	}

	public static function SetAllDiscountFilterCache($arDiscountCache, $boolNeedClear = true)
	{
		if (empty($arDiscountCache) || !is_array($arDiscountCache))
			return false;
		$boolNeedClear = !!$boolNeedClear;
		foreach ($arDiscountCache as $strCacheKey => $arDiscountIDs)
		{
			if (!is_array($arDiscountIDs))
				continue;
			if ($boolNeedClear)
				CatalogClearArray($arDiscountIDs);
			self::$arCacheDiscountFilter[$strCacheKey] = $arDiscountIDs;
		}
		return true;
	}

	public static function GetDiscountFilterCache($arCatalogGroups, $arUserGroups)
	{
		if (!is_array($arCatalogGroups))
			return false;
		CatalogClearArray($arCatalogGroups);
		if (empty($arCatalogGroups))
			return false;

		if (!is_array($arUserGroups))
			return false;
		CatalogClearArray($arUserGroups);
		if (empty($arUserGroups))
			return false;

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		return (isset(self::$arCacheDiscountFilter[$strCacheKey]) ? self::$arCacheDiscountFilter[$strCacheKey] : false);
	}

	public static function IsExistsDiscountFilterCache($arCatalogGroups, $arUserGroups)
	{
		if (!is_array($arCatalogGroups))
			return false;
		CatalogClearArray($arCatalogGroups);
		if (empty($arCatalogGroups))
			return false;

		if (!is_array($arUserGroups))
			return false;
		CatalogClearArray($arUserGroups);
		if (empty($arUserGroups))
			return false;

		$strCacheKey = md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
		return isset(self::$arCacheDiscountFilter[$strCacheKey]);
	}

	public static function GetDiscountFilterCacheByKey($strCacheKey)
	{
		if (empty($strCacheKey))
			return false;
		$strCacheKey = md5($strCacheKey);
		return (isset(self::$arCacheDiscountFilter[$strCacheKey]) ? self::$arCacheDiscountFilter[$strCacheKey] : false);
	}

	public static function IsExistsDiscountFilterCacheByKey($strCacheKey)
	{
		if (empty($strCacheKey))
			return false;
		$strCacheKey = md5($strCacheKey);
		return isset(self::$arCacheDiscountFilter[$strCacheKey]);
	}

	public static function GetDiscountFilterCacheKey($arCatalogGroups, $arUserGroups, $boolNeedClear = true)
	{
		$boolNeedClear = !!$boolNeedClear;
		if ($boolNeedClear)
		{
			if (!is_array($arCatalogGroups))
				return false;
			CatalogClearArray($arCatalogGroups);
			if (empty($arCatalogGroups))
				return false;

			if (!is_array($arUserGroups))
				return false;
			CatalogClearArray($arUserGroups);
			if (empty($arUserGroups))
				return false;
		}

		return md5('C'.implode('_', $arCatalogGroups).'-'.'U'.implode('_', $arUserGroups));
	}

	public static function SetDiscountResultCache($arDiscountList, $arDiscountIDs, $strSiteID, $strRenewal)
	{
		if (!is_array($arDiscountList))
			return false;
		if (!is_array($arDiscountIDs))
			return false;
		CatalogClearArray($arDiscountIDs);
		if (empty($arDiscountIDs))
			return false;
		if ('' == $strSiteID)
			return false;
		$strRenewal = ('Y' == $strRenewal ? 'Y' : 'N');
		$strCacheKey = md5('D'.implode('_', $arDiscountIDs).'-'.'S'.$strSiteID.'-R'.$strRenewal);
		self::$arCacheDiscountResult[$strCacheKey] = $arDiscountIDs;

		return true;
	}

	public static function SetAllDiscountResultCache($arDiscountResultCache)
	{
		if (empty($arDiscountResultCache) || !is_array($arDiscountResultCache))
			return false;
		foreach ($arDiscountResultCache as $strCacheKey => $arDiscountIDs)
		{
			self::$arCacheDiscountResult[$strCacheKey] = $arDiscountIDs;
		}
		return true;

	}

	public static function GetDiscountResultCacheKey($arDiscountIDs, $strSiteID, $strRenewal, $boolNeedClear = true)
	{
		$boolNeedClear = !!$boolNeedClear;
		if ($boolNeedClear)
		{
			if (!is_array($arDiscountIDs))
				return false;
			CatalogClearArray($arDiscountIDs);
			if (empty($arDiscountIDs))
				return false;

			if ('' == $strSiteID)
				return false;
			$strRenewal = ('Y' == $strRenewal ? 'Y' : 'N');
		}
		return md5('D'.implode('_', $arDiscountIDs).'-'.'S'.$strSiteID.'-R'.$strRenewal);
	}

	public static function SetDiscountProductCache($arItem, $arParams = array())
	{
		if (empty($arItem) || !is_array($arItem))
		{
			return;
		}
		if (!empty($arParams) && is_array($arParams) && isset($arParams['GET_BY_ID']) && 'Y' == $arParams['GET_BY_ID'])
		{
			$filter = array('ID' => $arItem);
			if (isset($arParams['IBLOCK_ID']))
			{
				$filter['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
			}
			$select = array('ID', 'IBLOCK_ID', 'CODE', 'XML_ID', 'NAME', 'ACTIVE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO',
				'SORT', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DATE_CREATE', 'DATE_CREATE_UNIX', 'CREATED_BY', 'TIMESTAMP_X', 'TIMESTAMP_X_UNIX',
				'MODIFIED_BY', 'TAGS', 'CATALOG_QUANTITY'
			);
			CTimeZone::Disable();
			$rsProducts = CIBlockElement::GetList(array(), $filter, false, false, $select);
			CTimeZone::Enable();
			while ($arProductFields = $rsProducts->GetNext(false, true))
			{
				$arProduct = array();

				$arProduct['ID'] = intval($arProductFields['ID']);
				$arProduct['IBLOCK_ID'] = intval($arProductFields['IBLOCK_ID']);

				$arProduct['SECTION_ID'] = array();
				$arProductSections = self::__GetSectionList($arProduct['IBLOCK_ID'], $arProduct['ID']);
				if (false !== $arProductSections)
					$arProduct['SECTION_ID'] = $arProductSections;

				$arProduct['CODE'] = (string)$arProductFields['~CODE'];
				$arProduct['XML_ID'] = (string)$arProductFields['~XML_ID'];
				$arProduct['NAME'] = $arProductFields['~NAME'];

				$arProduct['ACTIVE'] = $arProductFields['ACTIVE'];

				$arProduct['DATE_ACTIVE_FROM'] = (string)$arProductFields['DATE_ACTIVE_FROM'];
				if (!empty($arProduct['DATE_ACTIVE_FROM']))
					$arProduct['DATE_ACTIVE_FROM'] = intval(MakeTimeStamp($arProduct['DATE_ACTIVE_FROM']));

				$arProduct['DATE_ACTIVE_TO'] = (string)$arProductFields['DATE_ACTIVE_TO'];
				if (!empty($arProduct['DATE_ACTIVE_TO']))
					$arProduct['DATE_ACTIVE_TO'] = intval(MakeTimeStamp($arProduct['DATE_ACTIVE_TO']));

				$arProduct['SORT'] = intval($arProductFields['SORT']);

				$arProduct['PREVIEW_TEXT'] = (string)$arProductFields['~PREVIEW_TEXT'];
				$arProduct['DETAIL_TEXT'] = (string)$arProductFields['~DETAIL_TEXT'];
				$arProduct['TAGS'] = (string)$arProductFields['~TAGS'];

				if (isset($arProductFields['DATE_CREATE_UNIX']))
				{
					$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE_UNIX'];
					if ('' != $arProduct['DATE_CREATE'])
						$arProduct['DATE_CREATE'] = intval($arProduct['DATE_CREATE']);
				}
				else
				{
					$arProduct['DATE_CREATE'] = (string)$arProductFields['DATE_CREATE'];
					if ('' != $arProduct['DATE_CREATE'])
						$arProduct['DATE_CREATE'] = intval(MakeTimeStamp($arProduct['DATE_CREATE']));
				}

				if (isset($arProductFields['TIMESTAMP_X_UNIX']))
				{
					$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X_UNIX'];
					if ('' != $arProduct['TIMESTAMP_X'])
						$arProduct['TIMESTAMP_X'] = intval($arProduct['TIMESTAMP_X']);
				}
				else
				{
					$arProduct['TIMESTAMP_X'] = (string)$arProductFields['TIMESTAMP_X'];
					if ('' != $arProduct['TIMESTAMP_X'])
						$arProduct['TIMESTAMP_X'] = intval(MakeTimeStamp($arProduct['TIMESTAMP_X']));
				}

				$arProduct['CREATED_BY'] = intval($arProductFields['CREATED_BY']);
				$arProduct['MODIFIED_BY'] = intval($arProductFields['MODIFIED_BY']);

				$arProduct['CATALOG_QUANTITY'] = (string)$arProductFields['CATALOG_QUANTITY'];
				if ('' != $arProduct['CATALOG_QUANTITY'])
					$arProduct['CATALOG_QUANTITY'] = doubleval($arProduct['CATALOG_QUANTITY']);
				$arProduct['CATALOG_WEIGHT'] = (string)$arProductFields['CATALOG_WEIGHT'];
				if ('' != $arProduct['CATALOG_WEIGHT'])
					$arProduct['CATALOG_WEIGHT'] = doubleval($arProduct['CATALOG_WEIGHT']);

				$arProduct['CATALOG_VAT_ID'] = (string)$arProductFields['CATALOG_VAT_ID'];
				if ('' != $arProduct['CATALOG_VAT_ID'])
					$arProduct['CATALOG_VAT_ID'] = intval($arProduct['CATALOG_VAT_ID']);

				$arProduct['CATALOG_VAT_INCLUDED'] = (string)$arProductFields['CATALOG_VAT_INCLUDED'];

				$arProps = self::$arCacheProductProperties[$arProduct['ID']];

				self::__ConvertProperties($arProduct, $arProps, array('TIME_ZONE' => 'N'));
				if (isset(self::$arCacheProductProperties[$arProduct['ID']]))
					unset(self::$arCacheProductProperties[$arProduct['ID']]);
				if (isset(self::$arCacheProductSections[$arProduct['ID']]))
					unset(self::$arCacheProductSections[$arProduct['ID']]);

				self::$arCacheProduct[$arProduct['ID']] = $arProduct;
			}
		}
		else
		{
			if (!isset(self::$arCacheProduct[$arItem['ID']]))
			{
				$arParams = array(
					'PRODUCT' => 'Y'
				);
				if (!self::__GenerateFields($arItem, $arParams))
				{
					return;
				}
				self::$arCacheProduct[$arItem['ID']] = $arItem;
			}
		}
	}

	public static function SetProductSectionsCache($arItemIDs)
	{
		if (empty($arItemIDs) || !is_array($arItemIDs))
			return;
		CatalogClearArray($arItemIDs);
		if (empty($arItemIDs))
			return;
		if (empty(self::$arCacheProductSections))
		{
			self::$arCacheProductSections = array_fill_keys($arItemIDs, array());
		}
		else
		{
			foreach ($arItemIDs as &$intOneID)
			{
				self::$arCacheProductSections[$intOneID] = array();
			}
			unset($intOneID);
		}
		$rsSections = CIBlockElement::GetElementGroups($arItemIDs, true, array("ID", "IBLOCK_SECTION_ID", "IBLOCK_ELEMENT_ID"));
		while ($arSection = $rsSections->Fetch())
		{
			$arSection['ID'] = intval($arSection['ID']);
			$arSection['IBLOCK_SECTION_ID'] = intval($arSection['IBLOCK_SECTION_ID']);
			$arSection['IBLOCK_ELEMENT_ID'] = intval($arSection['IBLOCK_ELEMENT_ID']);
			self::$arCacheProductSections[$arSection['IBLOCK_ELEMENT_ID']][] = $arSection;
		}
		unset($arSection);
	}

	public static function SetProductPropertiesCache($intProductID, $arProps)
	{
		$intProductID = intval($intProductID);
		if (0 >= $intProductID)
			return;
		if (!is_array($arProps))
			return;
		self::$arCacheProductProperties[$intProductID] = $arProps;
	}

	public static function ClearDiscountCache($arTypes)
	{
		if (empty($arTypes) || !is_array($arTypes))
			return;
		if (isset($arTypes['PRODUCT']))
			self::$arCacheProduct = array();
		if (isset($arTypes['SECTIONS']))
			self::$arCacheProductSections = array();
		if (isset($arTypes['SECTION_CHAINS']))
			self::$arCacheProductSectionChain = array();
		if (isset($arTypes['PROPERTIES']))
			self::$arCacheProductProperties = array();
	}
}
?>