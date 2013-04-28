<?
IncludeModuleLangFile(__FILE__);

/***********************************************************************/
/***********  CPrice  **************************************************/
/***********************************************************************/

/**
 * 
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/index.php
 * @author Bitrix
 */
class CAllPrice
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "PRODUCT_ID") || $ACTION=="ADD") && IntVal($arFields["PRODUCT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("KGP_EMPTY_PRODUCT"), "EMPTY_PRODUCT_ID");
			return false;
		}
		if ((is_set($arFields, "CATALOG_GROUP_ID") || $ACTION=="ADD") && IntVal($arFields["CATALOG_GROUP_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("KGP_EMPTY_CATALOG_GROUP"), "EMPTY_CATALOG_GROUP_ID");
			return false;
		}
		if ((is_set($arFields, "CURRENCY") || $ACTION=="ADD") && strlen($arFields["CURRENCY"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("KGP_EMPTY_CURRENCY"), "EMPTY_CURRENCY");
			return false;
		}

		if (is_set($arFields, "PRICE") || $ACTION=="ADD")
		{
			$arFields["PRICE"] = str_replace(",", ".", $arFields["PRICE"]);
			$arFields["PRICE"] = DoubleVal($arFields["PRICE"]);
		}

		if ((is_set($arFields, "QUANTITY_FROM") || $ACTION=="ADD") && IntVal($arFields["QUANTITY_FROM"]) <= 0)
			$arFields["QUANTITY_FROM"] = False;
		if ((is_set($arFields, "QUANTITY_TO") || $ACTION=="ADD") && IntVal($arFields["QUANTITY_TO"]) <= 0)
			$arFields["QUANTITY_TO"] = False;

		return True;
	}

	
	/**
	 * <p>Метод изменяет параметры ценового предложения (цены) для товара с кодом ID на значения из массива arFields.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код ценового предложения.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив новых параметров ценового предложения,
	 * ключами в котором являются названия полей предложения, а
	 * значениями - новые значения. <br> Допустимые ключи: <ul> <li> <b>PRODUCT_ID</b> -
	 * код товара или торгового предложения (ID элемента инфоблока);</li> <li>
	 * <b>EXTRA_ID</b> - код наценки;</li> <li> <b>CATALOG_GROUP_ID</b> - код типа цены;</li> <li>
	 * <b>PRICE </b>- цена;</li> <li> <b>CURRENCY</b> - валюта цены</li> <li> <b>QUANTITY_FROM</b> -
	 * количество товара, начиная с приобретения которого действует эта
	 * цена.</li> <li> <b>QUANTITY_TO</b> - количество товара, при приобретении
	 * которого заканчивает действие эта цена. <p class="note">Если необходимо,
	 * чтобы значения параметров <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> не были заданы,
	 * необходимо указать у них в качестве значения <i>false</i> либо не
	 * задавать поля <b>QUANTITY_FROM</b> и <b>QUANTITY_TO</b> в <b>Update</b> вообще. </p> </li> </ul>
	 * Если установлен код наценки, то появляется возможность
	 * автоматически пересчитывать эту цену при изменении базовой цены
	 * или процента наценки.
	 *
	 *
	 *
	 * @param boolean $boolRecalc = false Пересчитать цены. Если передать <i>true</i>, то включается механизм
	 * пересчета цен. <br> Если обновляется базовая цена (в <b>CATALOG_GROUP_ID</b>
	 * задан тип цен, являющийся базовым), будут пересчитаны все
	 * остальные типы цен для товара, если у них задан код наценки. <br>
	 * Если обновляется иная цена (не базовая), для нее задан код наценки
	 * и уже существует базовая - значения <b>PRICE</b> и <b>CURRENCY</b> буду
	 * пересчитаны. <br> Необязательный параметр. По умолчанию - <i>false</i>.
	 *
	 *
	 *
	 * @return bool <p>Возвращает ID обновляемой цены в случае успешного сохранения
	 * цены и <i>false</i> - в противном случае. Для получения детальной
	 * информации об ошибке следует вызвать
	 * <b>$APPLICATION-&gt;GetException()</b>.</p><h4>События</h4><p>Метод работает с событиями
	 * <a href="http://dev.1c-bitrix.ruapi_help/catalog/events/onbeforepriceupdate.php">OnBeforePriceUpdate</a> и
	 * OnPriceUpdate.</p><h4>Примечания</h4><p>Если параметр $boolRecalc = true, все равно
	 * необходимо указывать цену и валюту (в том случае, когда тип цены -
	 * не базовый). Если существует базовая цена, значения цены и валюты
	 * будут изменены, если нет - код наценки будет изменен на ноль.</p><p>В
	 * обработчиках события OnBeforePriceUpdate можно запретить или, наоборот,
	 * включить пересчет цены. За это отвечает ключ RECALC массива данных,
	 * передаваемых в обработчик.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Установим для товара с кодом 15 цену типа 2 в значение 29.95 USD
	 * $PRODUCT_ID = 15;
	 * $PRICE_TYPE_ID = 2;
	 * 
	 * $arFields = Array(
	 *     "PRODUCT_ID" =&gt; $PRODUCT_ID,
	 *     "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID,
	 *     "PRICE" =&gt; 29.95,
	 *     "CURRENCY" =&gt; "USD",
	 *     "QUANTITY_FROM" =&gt; 1,
	 *     "QUANTITY_TO" =&gt; 10
	 * );
	 * 
	 * $res = CPrice::GetList(
	 *         array(),
	 *         array(
	 *                 "PRODUCT_ID" =&gt; $PRODUCT_ID,
	 *                 "CATALOG_GROUP_ID" =&gt; $PRICE_TYPE_ID
	 *             )
	 *     );
	 * 
	 * if ($arr = $res-&gt;Fetch())
	 * {
	 *     CPrice::Update($arr["ID"], $arFields);
	 * }
	 * else
	 * {
	 *     CPrice::Add($arFields);
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a href="http://dev.1c-bitrix.ruapi_help/catalog/fields.php">Структура таблицы</a></li>
	 * <li>CPrice::CheckFields</li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/add.php">CPrice::Add</a></li> <li><a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/events/onbeforepriceupdate.php">Событие
	 * OnBeforePriceUpdate</a></li> <li>Событие OnPriceUpdate</li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields,$boolRecalc = false)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		if (!CPrice::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$boolBase = false;
		$arFields['RECALC'] = ($boolRecalc === true ? true : false);

		$db_events = GetModuleEvents("catalog", "OnBeforePriceUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, &$arFields))===false)
				return false;

		if (!empty($arFields['RECALC']) && $arFields['RECALC'] === true)
		{
			CPrice::ReCountFromBase($arFields,$boolBase);
		}

		$strUpdate = $DB->PrepareUpdate("b_catalog_price", $arFields);
		$strSql = "UPDATE b_catalog_price SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($boolBase == true)
		{
			CPrice::ReCountForBase($arFields);
		}

		$events = GetModuleEvents("catalog", "OnPriceUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));

		return $ID;
	}

	
	/**
	 * <p>Метод удаляет ценовое предложение с кодом ID. </p> <p><b>Примечание</b>: метод работает с двумя событиями: OnBeforePriceDelete и OnPriceDelete. Событие OnBeforePriceDelete позволяет отменить удаление ценового предложения. Событие OnPriceDelete дает возможность провести какие-то операции одновременно с удалением цены.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код ценового предложения.
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение <i>true</i> в случае успешного удаления и <i>false</i>
	 * - в противном случае.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>// Удалим цену с кодом 11<br>CPrice::Delete(11);<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p><b>Методы</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/deletebyproduct.php">CPrice::DeleteByProduct</a> </li>
	 * </ul><p><b>События</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/events/onbeforepricedelete.php">OnProductPriceDelete</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/events/onpricedelete.php">OnPriceDelete</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		if ($ID <= 0)
			return false;

		$db_events = GetModuleEvents("catalog", "OnBeforePriceDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$mxRes = $DB->Query("DELETE FROM b_catalog_price WHERE ID = ".$ID." ", true);

		$events = GetModuleEvents("catalog", "OnPriceDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		return $mxRes;
	}

	
	/**
	 * <p>Функция возвращает базовую цену товара с кодом PRODUCT_ID. Базовая цена - это цена базового типа цен.</p>
	 *
	 *
	 *
	 *
	 * @param int $PRODUCT_ID  Код товара или торгового предложения (ID элемента инфоблока).
	 *
	 *
	 *
	 * @param  $int  Количество товара, начиная с приобретения которого действует эта
	 * цена.
	 *
	 *
	 *
	 * @param quantityFro $m = false[ Количество товара, при приобретении которого заканчивает
	 * действие эта цена.
	 *
	 *
	 *
	 * @param int $quantityTo = false]] 
	 *
	 *
	 *
	 * @return array <p>Возвращает ассоциативный массив с ключами: </p><table class="tnormal"
	 * width="100%"> <tr> <th width="15%">Код</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код
	 * ценового предложения.</td> </tr> <tr> <td>PRODUCT_ID</td> <td>Код товара или
	 * торгового предложения (ID элемента инфоблока).</td> </tr> <tr> <td>EXTRA_ID</td>
	 * <td>Код наценки.</td> </tr> <tr> <td>CATALOG_GROUP_ID</td> <td>Код типа цены.</td> </tr> <tr>
	 * <td>PRICE</td> <td>Базовая цена.</td> </tr> <tr> <td>CURRENCY</td> <td>Валюта базовой
	 * цены.</td> </tr> <tr> <td>QUANTITY_FROM</td> <td>Количество товара, начиная с
	 * приобретения которого действует эта цена.</td> </tr> <tr> <td>QUANTITY_TO</td>
	 * <td>Количество товара, при приобретении которого заканчивает
	 * действие эта цена.</td> </tr> </table><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $ar_res = CPrice::GetBasePrice(11, 1, 10);
	 * echo "Базовая цена товара с кодом 11 (при приобретении от ".
	 *       $ar_res["QUANTITY_FROM"]." до ".
	 *       $ar_res["QUANTITY_TO"]." единиц товара) равна ".
	 *       $ar_res["PRICE"]." ".$ar_res["CURRENCY"]."&lt;br&gt;";
	 * echo "Отформатированая базовая цена: ".
	 *       CurrencyFormat($ar_res["PRICE"], $ar_res["CURRENCY"]);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__getbaseprice.9dc276c9.php
	 * @author Bitrix
	 */
	public static function GetBasePrice($productID, $quantityFrom = false, $quantityTo = false)
	{
		global $DB;

		$productID = IntVal($productID);
		if ($quantityFrom !== false)
			$quantityFrom = IntVal($quantityFrom);
		if ($quantityTo !== false)
			$quantityTo = IntVal($quantityTo);

		$arFilter = array(
				"BASE" => "Y",
				"PRODUCT_ID" => $productID
			);

		if ($quantityFrom !== false)
			$arFilter["QUANTITY_FROM"] = $quantityFrom;
		if ($quantityTo !== false)
			$arFilter["QUANTITY_TO"] = $quantityTo;

		$db_res = CPrice::GetList(
				array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
				$arFilter
			);
		if ($res = $db_res->Fetch())
			return $res;

		return false;
	}

	
	/**
	 * <p>Функция устанавливает базовую цену товара с кодом PRODUCT_ID. Базовая цена - это цена базового типа цен. </p>
	 *
	 *
	 *
	 *
	 * @param int $PRODUCT_ID  Код товара или торгового предложения (ID элемента инфоблока).
	 *
	 *
	 *
	 * @param float $PRICE  Новая базовая цена.
	 *
	 *
	 *
	 * @param string $CURRENCY  Валюта новой базовой цены.
	 *
	 *
	 *
	 * @param  $int  Количество товара, начиная с приобретения которого действует эта
	 * цена.
	 *
	 *
	 *
	 * @param QUANTITY_FRO $M = 0[ Количество товара, при приобретении которого заканчивает
	 * действие эта цена.
	 *
	 *
	 *
	 * @param int $QUANTITY_TO = 0]] 
	 *
	 *
	 *
	 * @return bool <p>Возвращает значение <i>true</i> в случае успешного сохранения цены и
	 * <i>false</i> - в противном случае. </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/cprice__setbaseprice.a8de1fcf.php
	 * @author Bitrix
	 */
	public static function SetBasePrice($ProductID, $Price, $Currency, $quantityFrom = 0, $quantityTo = 0, $bGetID = false)
	{
		global $DB;

		$bGetID = ($bGetID == true ? true : false);

		$arFields = array();
		$arFields["PRICE"] = DoubleVal($Price);
		$arFields["CURRENCY"] = $Currency;
		$arFields["QUANTITY_FROM"] = IntVal($quantityFrom);
		$arFields["QUANTITY_TO"] = IntVal($quantityTo);
		$arFields["EXTRA_ID"] = False;

		$ID = false;
		if ($arBasePrice = CPrice::GetBasePrice($ProductID, $quantityFrom, $quantityTo))
		{
			//CPrice::Update($arBasePrice["ID"], $arFields);
			$ID = CPrice::Update($arBasePrice["ID"], $arFields);
		}
		else
		{
			$arBaseGroup = CCatalogGroup::GetBaseGroup();
			$arFields["CATALOG_GROUP_ID"] = $arBaseGroup["ID"];
			$arFields["PRODUCT_ID"] = $ProductID;

			//CPrice::Add($arFields);
			$ID = CPrice::Add($arFields);
		}
		if (!$ID)
		{
			return false;
		}
		else
		{
			return ($bGetID ? $ID : true);
		}
	}

	public static function ReCalculate($TYPE, $ID, $VAL)
	{
		$ID = IntVal($ID);
		if ($TYPE=="EXTRA")
		{
			$db_res = CPrice::GetList(
					array("EXTRA_ID" => "ASC"),
					array("EXTRA_ID" => $ID)
				);
			while ($res = $db_res->Fetch())
			{
				unset($arFields);
				$arFields = array();
				if ($arBasePrice = CPrice::GetBasePrice($res["PRODUCT_ID"], $res["QUANTITY_FROM"], $res["QUANTITY_TO"]))
				{
					$arFields["PRICE"] = RoundEx($arBasePrice["PRICE"] * (1 + 1 * $VAL / 100), 2);
					$arFields["CURRENCY"] = $arBasePrice["CURRENCY"];
					CPrice::Update($res["ID"], $arFields);
				}
			}
		}
		else
		{
			$db_res = CPrice::GetList(array("PRODUCT_ID" => "ASC"), array("PRODUCT_ID" => $ID));
			while ($res = $db_res->Fetch())
			{
				if (IntVal($res["EXTRA_ID"])>0)
				{
					$res1 = CExtra::GetByID($res["EXTRA_ID"]);
					unset($arFields);
					$arFields["PRICE"] = $VAL * (1 + 1 * $res1["PERCENTAGE"] / 100);
					CPrice::Update($res["ID"], $arFields);
				}
			}
		}
	}

	public static function OnCurrencyDelete($Currency)
	{
		global $DB;
		if (strlen($Currency)<=0) return false;

		$strSql =
			"DELETE FROM b_catalog_price ".
			"WHERE CURRENCY = '".$DB->ForSql($Currency)."' ";

		return $DB->Query($strSql, true);
	}

	public static function OnIBlockElementDelete($ProductID)
	{
		global $DB;
		$ProductID = IntVal($ProductID);
		$strSql =
			"DELETE ".
			"FROM b_catalog_price ".
			"WHERE PRODUCT_ID = ".$ProductID." ";
		return $DB->Query($strSql, true);
	}

	
	/**
	 * <p>Метод удаляет цены для товара. В качестве аргументов методу передаются код (ID) товара и, опционально, массив кодов (ID) цен, которые необходимо оставить. Если второй аргумент - пустой, удаляются все цены.</p> <p><b>Примечание</b>: метод работает с двумя событиями: OnBeforeProductPriceDelete и OnProductPriceDelete. Событие OnBeforeProductPriceDelete позволяет отменить удаление либо изменить перечень цен, которые будут оставлены. Событие OnProductPriceDelete дает возможность провести какие-то операции одновременно с удалением цен.</p>
	 *
	 *
	 *
	 *
	 * @param int $ProductID  Код товара или торгового предложения (ID элемента инфоблока), у
	 * которого необходимо удалить цены
	 *
	 *
	 *
	 * @param array $arExceptionIDs = array() Массив кодов (ID) цен, которые будут оставлены. Если массив пуст,
	 * будут удалены все цены товара.
	 *
	 *
	 *
	 * @return boolean <ul> <li> <i>true</i> в случае успеха </li> <li> <i>false</i>, если произошла ошибка
	 * или удаление было отменено. </li> </ul>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * <b>Удаление всех цен товара</b>$boolResult = CPrice::DeleteByProduct(241);<br>
<b>Удаление всех цен товара, кроме двух</b>$boolResult = CPrice::DeleteByProduct(241,array(426,456));<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <p><b>Методы</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/classes/cprice/cprice__delete.9afc6f2b.php">CPrice::Delete</a> </li>
	 * </ul><p><b>События</b></p><ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/catalog/events/onbeforeproductpricedelete.php">OnBeforeProductPriceDelete</a>
	 * </li> <li> <a href="http://dev.1c-bitrix.ruapi_help/catalog/events/onproductpricedelete.php">OnProductPriceDelete</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/catalog/classes/cprice/deletebyproduct.php
	 * @author Bitrix
	 */
	public static function DeleteByProduct($ProductID, $arExceptionIDs = array())
	{
		global $DB;

		$ProductID = IntVal($ProductID);
		if ($ProductID <= 0)
			return false;
		$db_events = GetModuleEvents("catalog", "OnBeforeProductPriceDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ProductID,&$arExceptionIDs))===false)
				return false;

		for ($i = 0, $intCount = count($arExceptionIDs); $i < $intCount; $i++)
			$arExceptionIDs[$i] = intval($arExceptionIDs[$i]);
		$arExceptionIDs[] = 0;

		$strExceptionIDs = implode(',',$arExceptionIDs);

		$strSql =
			"DELETE ".
			"FROM b_catalog_price ".
			"WHERE PRODUCT_ID = ".$ProductID." ".
			"	AND ID NOT IN (".$strExceptionIDs.") ";

		$mxRes = $DB->Query($strSql, true);

		$events = GetModuleEvents("catalog", "OnProductPriceDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ProductID,$arExceptionIDs));

		return $mxRes;
	}

	function ReCountForBase(&$arFields)
	{
		static $arExtraList = array();
		$boolSearch = false;

		$arFilter = array('PRODUCT_ID' => $arFields['PRODUCT_ID'],'!CATALOG_GROUP_ID' => $arFields['CATALOG_GROUP_ID']);
		if (isset($arFields['QUANTITY_FROM']))
			$arFilter['QUANTITY_FROM'] = $arFields['QUANTITY_FROM'];
		if (isset($arFields['QUANTITY_TO']))
			$arFilter['QUANTITY_TO'] = $arFields['QUANTITY_TO'];

		$rsPrices = CPrice::GetList(array('CATALOG_GROUP_ID' => 'asc',"QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),$arFilter,false,false,array('ID','EXTRA_ID'));
		while ($arPrice = $rsPrices->Fetch())
		{
			$arPrice['EXTRA_ID'] = intval($arPrice['EXTRA_ID']);
			if ($arPrice['EXTRA_ID'] > 0)
			{
				$boolSearch = array_key_exists($arPrice['EXTRA_ID'],$arExtraList);
				if (!$boolSearch)
				{
					$arExtra = CExtra::GetByID($arPrice['EXTRA_ID']);
					if (!empty($arExtra))
					{
						$boolSearch = true;
						$arExtraList[$arExtra['ID']] = $arExtra['PERCENTAGE'];
					}
				}
				if ($boolSearch)
				{
					$arNewPrice = array(
						'CURRENCY' => $arFields['CURRENCY'],
						'PRICE' => RoundEx($arFields["PRICE"] * (1 + DoubleVal($arExtraList[$arPrice['EXTRA_ID']])/100), CATALOG_VALUE_PRECISION),
					);
					CPrice::Update($arPrice['ID'],$arNewPrice,false);
				}
			}
		}
	}

	public static function ReCountFromBase(&$arFields, &$boolBase)
	{
		$arBaseGroup = CCatalogGroup::GetBaseGroup();
		if (!empty($arBaseGroup))
		{
			if ($arFields['CATALOG_GROUP_ID'] == $arBaseGroup['ID'])
			{
				$boolBase = true;
			}
			else
			{
				if (!empty($arFields['EXTRA_ID']) && intval($arFields['EXTRA_ID']) > 0)
				{
					$arExtra = CExtra::GetByID($arFields['EXTRA_ID']);
					if (!empty($arExtra))
					{
						$arFilter = array('PRODUCT_ID' => $arFields['PRODUCT_ID'],'CATALOG_GROUP_ID' => $arBaseGroup['ID']);
						if (isset($arFields['QUANTITY_FROM']))
							$arFilter['QUANTITY_FROM'] = $arFields['QUANTITY_FROM'];
						if (isset($arFields['QUANTITY_TO']))
							$arFilter['QUANTITY_TO'] = $arFields['QUANTITY_TO'];
						$rsBasePrices = CPrice::GetList(array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC"),
													$arFilter,false,array('nTopCount' => 1),array('PRICE','CURRENCY'));
						if ($arBasePrice = $rsBasePrices->Fetch())
						{
							$arFields['CURRENCY'] = $arBasePrice['CURRENCY'];
							$arFields['PRICE'] = RoundEx($arBasePrice["PRICE"] * (1 + DoubleVal($arExtra["PERCENTAGE"])/100), CATALOG_VALUE_PRECISION);
						}
					}
					else
					{
						$arFields['EXTRA_ID'] = 0;
					}
				}
			}
		}
	}
}
?>