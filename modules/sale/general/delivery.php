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
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/index.php
 * @author Bitrix
 */
class CAllSaleDelivery
{
	static function DoProcessOrder(&$arOrder, $deliveryId, &$arErrors)
	{
		if (!array_key_exists("DELIVERY_LOCATION", $arOrder) || intval($arOrder["DELIVERY_LOCATION"]) <= 0)
			return;

		if (strlen($deliveryId) > 0 && strpos($deliveryId, ":") !== false)
		{
			$arOrder["DELIVERY_ID"] = $deliveryId;

			$delivery = explode(":", $deliveryId);

			$arOrderTmpDel = array(
				"PRICE" => $arOrder["ORDER_PRICE"] + $arOrder["TAX_PRICE"] - $arOrder["DISCOUNT_PRICE"],
				"WEIGHT" => $arOrder["ORDER_WEIGHT"],
				"LOCATION_FROM" => COption::GetOptionInt('sale', 'location', '2961', $arOrder["SITE_ID"]),
				"LOCATION_TO" => $arOrder["DELIVERY_LOCATION"],
				"LOCATION_ZIP" => $arOrder["DELIVERY_LOCATION_ZIP"],
			);

			$arDeliveryPrice = CSaleDeliveryHandler::CalculateFull($delivery[0], $delivery[1], $arOrderTmpDel, $arOrder["CURRENCY"]);

			if ($arDeliveryPrice["RESULT"] == "ERROR")
				$arErrors[] = array("CODE" => "CALCULATE", "TEXT" => $arDeliveryPrice["TEXT"]);
			else
				$arOrder["DELIVERY_PRICE"] = roundEx($arDeliveryPrice["VALUE"], SALE_VALUE_PRECISION);
		}
		elseif (intval($deliveryId) > 0)
		{
			if ($arDelivery = CSaleDelivery::GetByID($deliveryId))
			{
				$arOrder["DELIVERY_ID"] = $deliveryId;
				$arOrder["DELIVERY_PRICE"] = roundEx(CCurrencyRates::ConvertCurrency($arDelivery["PRICE"], $arDelivery["CURRENCY"], $arOrder["CURRENCY"]), SALE_VALUE_PRECISION);
			}
			else
			{
				$arErrors[] = array("CODE" => "CALCULATE", "TEXT" => GetMessage('SKGD_DELIVERY_NOT_FOUND'));
			}
		}
	}

	public static function DoLoadDelivery($location, $locationZip, $weight, $price, $currency, $siteId = null)
	{
		$location = intval($location);
		if ($location <= 0)
			return null;

		if ($siteId == null)
			$siteId = SITE_ID;

		$arResult = array();

		$arFilter = array(
			"COMPABILITY" => array(
				"WEIGHT" => $weight,
				"PRICE" => $price,
				"LOCATION_FROM" => COption::GetOptionString('sale', 'location', false, $siteId),
				"LOCATION_TO" => $location,
				"LOCATION_ZIP" => $locationZip,
			),
			"SITE_ID" => $siteId,
		);
		$dbDeliveryServices = CSaleDeliveryHandler::GetList(array("SORT" => "ASC"), $arFilter);
		while ($arDeliveryService = $dbDeliveryServices->GetNext())
		{
			if (!is_array($arDeliveryService) || !is_array($arDeliveryService["PROFILES"]))
				continue;

			foreach ($arDeliveryService["PROFILES"] as $profileId => $arDeliveryProfile)
			{
				if ($arDeliveryProfile["ACTIVE"] != "Y")
					continue;

				if (!array_key_exists($arDeliveryService["SID"], $arResult))
				{
					$arResult[$arDeliveryService["SID"]] = array(
						"SID" => $arDeliveryService["SID"],
						"TITLE" => $arDeliveryService["NAME"],
						"DESCRIPTION" => $arDeliveryService["DESCRIPTION"],
						"PROFILES" => array(),
					);
				}

				$arResult[$arDeliveryService["SID"]]["PROFILES"][$profileId] = array(
					"ID" => $arDeliveryService["SID"].":".$profileId,
					"SID" => $profileId,
					"TITLE" => $arDeliveryProfile["TITLE"],
					"DESCRIPTION" => $arDeliveryProfile["DESCRIPTION"],
					"FIELD_NAME" => "DELIVERY_ID",
				);

				$arDeliveryPriceTmp = CSaleDeliveryHandler::CalculateFull(
					$arDeliveryService["SID"],
					$profileId,
					array(
						"PRICE" => $price,
						"WEIGHT" => $weight,
						"LOCATION_FROM" => COption::GetOptionString('sale', 'location', false, $siteId),
						"LOCATION_TO" => $location,
						"LOCATION_ZIP" => $locationZip,
					),
					$currency
				);

				if ($arDeliveryPriceTmp["RESULT"] != "ERROR")
				{
					$arResult[$arDeliveryService["SID"]]["PROFILES"][$profileId]["DELIVERY_PRICE"] = roundEx($arDeliveryPriceTmp["VALUE"], SALE_VALUE_PRECISION);
					$arResult[$arDeliveryService["SID"]]["PROFILES"][$profileId]["CURRENCY"] = $currency;
				}
			}
		}

		$dbDelivery = CSaleDelivery::GetList(
			array("SORT" => "ASC", "NAME" => "ASC"),
			array(
				"LID" => $siteId,
				"+<=WEIGHT_FROM" => $weight,
				"+>=WEIGHT_TO" => $weight,
				"+<=ORDER_PRICE_FROM" => $price,
				"+>=ORDER_PRICE_TO" => $price,
				"ACTIVE" => "Y",
				"LOCATION" => $location,
			)
		);
		while ($arDelivery = $dbDelivery->GetNext())
		{
			$arDeliveryDescription = CSaleDelivery::GetByID($arDelivery["ID"]);
			$arDelivery["DESCRIPTION"] = $arDeliveryDescription["DESCRIPTION"];
		
			$arDelivery["FIELD_NAME"] = "DELIVERY_ID";
			if (intval($arDelivery["PERIOD_FROM"]) > 0 || intval($arDelivery["PERIOD_TO"]) > 0)
			{
				$arDelivery["PERIOD_TEXT"] = GetMessage("SALE_DELIV_PERIOD");
				if (intval($arDelivery["PERIOD_FROM"]) > 0)
					$arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_FROM")." ".intval($arDelivery["PERIOD_FROM"]);
				if (intval($arDelivery["PERIOD_TO"]) > 0)
					$arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_TO")." ".intval($arDelivery["PERIOD_TO"]);
				if ($arDelivery["PERIOD_TYPE"] == "H")
					$arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_HOUR")." ";
				elseif ($arDelivery["PERIOD_TYPE"] == "M")
					$arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_MONTH")." ";
				else
					$arDelivery["PERIOD_TEXT"] .= " ".GetMessage("SOA_DAY")." ";
			}
			$arResult[] = $arDelivery;
		}

		return $arResult;
	}

	
	/**
	 * <p>Функция возвращает параметры службы доставки с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код службы доставки.
	 *
	 *
	 *
	 * @return array <p>Возвращается ассоциативный массив параметров доставки с
	 * ключами:</p><table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
	 * </tr> <tr> <td>ID</td> <td>Код службы доставки.</td> </tr> <tr> <td>NAME</td> <td>Название
	 * доставки.</td> </tr> <tr> <td>LID</td> <td>Код сайта, к которому привязана эта
	 * доставка.</td> </tr> <tr> <td>PERIOD_FROM</td> <td>Минимальный срок доставки.</td> </tr>
	 * <tr> <td>PERIOD_TO</td> <td>Максимальный срок доставки.</td> </tr> <tr> <td>PERIOD_TYPE</td>
	 * <td>Единица измерения срока: D - дни, H - часы, M - месяцы.</td> </tr> <tr>
	 * <td>WEIGHT_FROM</td> <td>Минимальный вес заказа, для которого возможна эта
	 * доставка (единица измерения едина на сайте).</td> </tr> <tr> <td>WEIGHT_TO</td>
	 * <td>Максимальный вес заказа, для которого возможна эта доставка
	 * (единица измерения едина на сайте).</td> </tr> <tr> <td>ORDER_PRICE_FROM</td>
	 * <td>Минимальная стоимость заказа, для которой возможна эта
	 * доставка.</td> </tr> <tr> <td>ORDER_PRICE_TO</td> <td>Максимальная стоимость заказа,
	 * для которой возможна эта доставка.</td> </tr> <tr> <td>ORDER_CURRENCY</td> <td>Валюта
	 * ограничений по стоимости.</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N) активности
	 * доставки.</td> </tr> <tr> <td>PRICE</td> <td>Стоимость доставки.</td> </tr> <tr>
	 * <td>CURRENCY</td> <td>Валюта стоимости доставки.</td> </tr> <tr> <td>SORT</td> <td>Индекс
	 * сортировки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание доставки.</td> </tr>
	 * </table><p> </p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // $DELIVERY_ID - код службы доставки
	 * 
	 * $arDeliv = CSaleDelivery::GetByID($DELIVERY_ID);
	 * if ($arDeliv)
	 * {
	 *    echo "Доставка \"".$arDeliv["NAME"]."\" стоит ".CurrencyFormat($arDeliv["PRICE"], $arDeliv["CURRENCY"]);
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__getbyid.d44054be.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT * ".
			"FROM b_sale_delivery ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	
	/**
	 * <p>Функция возвращает набор местоположений по фильтру arFilter </p>
	 *
	 *
	 *
	 *
	 * @param array $arrayarFilter = Array() Фильтр представляет собой ассоциативный массив, в котором
	 * ключами являются названия параметров, а значениями - условия.<br><br>
	 * Допустимые ключи:<br><ul> <li> <b>DELIVERY_ID</b> - код доставки;</li> <li> <b>LOCATION_ID</b>
	 * - код местоположения или группы местоположений;</li> <li> <b>LOCATION_TYPE</b> -
	 * тип (L - местоположение, G - группа местоположений).</li> </ul>
	 *
	 *
	 *
	 * @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	 * ассоциативных массивов с ключами:</p><table class="tnormal" width="100%"> <tr> <th
	 * width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>DELIVERY_ID</td> <td>Код службы
	 * доставки.</td> </tr> <tr> <td>LOCATION_ID</td> <td>Код местоположения или группы
	 * местоположений.</td> </tr> <tr> <td>LOCATION_TYPE</td> <td>Тип (L - местоположение, G -
	 * группа местоположений) </td> </tr> </table>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__getlocationlist.e2a07bf9.php
	 * @author Bitrix
	 */
	public static function GetLocationList($arFilter = Array())
	{
		global $DB;
		$arSqlSearch = Array();

		if(!is_array($arFilter)) 
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		$countFilterKey = count($filter_keys);
		for($i=0; $i < $countFilterKey; $i++)
		{
			$val = $DB->ForSql($arFilter[$filter_keys[$i]]);
			if (strlen($val)<=0) continue;

			$key = $filter_keys[$i];
			if ($key[0]=="!")
			{
				$key = substr($key, 1);
				$bInvert = true;
			}
			else
				$bInvert = false;

			switch(ToUpper($key))
			{
			case "DELIVERY_ID":
				$arSqlSearch[] = "DL.DELIVERY_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
				break;
			case "LOCATION_ID":
				$arSqlSearch[] = "DL.LOCATION_ID ".($bInvert?"<>":"=")." ".IntVal($val)." ";
				break;
			case "LOCATION_TYPE":
				$arSqlSearch[] = "DL.LOCATION_TYPE ".($bInvert?"<>":"=")." '".$val."' ";
				break;
			}
		}

		$strSqlSearch = "";
		$countSqlSearch = count($arSqlSearch);
		for($i=0; $i < $countSqlSearch; $i++)
		{
			$strSqlSearch .= " AND ";
			$strSqlSearch .= " (".$arSqlSearch[$i].") ";
		}

		$strSql = 
			"SELECT DL.* ".
			"FROM b_sale_delivery2location DL ".
			"WHERE 1 = 1 ".
			"	".$strSqlSearch." ";

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGD_EMPTY_DELIVERY"), "ERROR_NO_NAME");
			return false;
		}

		if ((is_set($arFields, "LID") || $ACTION=="ADD") && strlen($arFields["LID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGD_EMPTY_SITE"), "ERROR_NO_SITE");
			return false;
		}

		if (is_set($arFields, "ACTIVE") && $arFields["ACTIVE"] != "Y")
			$arFields["ACTIVE"] = "N";
		if ((is_set($arFields, "SORT") || $ACTION=="ADD") && IntVal($arFields["SORT"]) <= 0)
			$arFields["SORT"] = 100;

		if (is_set($arFields, "PRICE"))
		{
			$arFields["PRICE"] = str_replace(",", ".", $arFields["PRICE"]);
			$arFields["PRICE"] = DoubleVal($arFields["PRICE"]);
		}
		if ((is_set($arFields, "PRICE") || $ACTION=="ADD") && DoubleVal($arFields["PRICE"]) < 0)
			return false;

		if ((is_set($arFields, "CURRENCY") || $ACTION=="ADD") && strlen($arFields["CURRENCY"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGD_EMPTY_CURRENCY"), "ERROR_NO_CURRENCY");
			return false;
		}

		if (is_set($arFields, "ORDER_PRICE_FROM"))
		{
			$arFields["ORDER_PRICE_FROM"] = str_replace(",", ".", $arFields["ORDER_PRICE_FROM"]);
			$arFields["ORDER_PRICE_FROM"] = DoubleVal($arFields["ORDER_PRICE_FROM"]);
		}

		if (is_set($arFields, "ORDER_PRICE_TO"))
		{
			$arFields["ORDER_PRICE_TO"] = str_replace(",", ".", $arFields["ORDER_PRICE_TO"]);
			$arFields["ORDER_PRICE_TO"] = DoubleVal($arFields["ORDER_PRICE_TO"]);
		}

		if ((is_set($arFields, "LOCATIONS") || $ACTION=="ADD") && (!is_array($arFields["LOCATIONS"]) || count($arFields["LOCATIONS"]) <= 0))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGD_EMPTY_LOCATION"), "ERROR_NO_LOCATIONS");
			return false;
		}

		if (is_set($arFields, "LID"))
		{
			$dbSite = CSite::GetByID($arFields["LID"]);
			if (!$dbSite->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["LID"], GetMessage("SKGD_NO_SITE")), "ERROR_NO_SITE");
				return false;
			}
		}

		if (is_set($arFields, "CURRENCY"))
		{
			if (!($arCurrency = CCurrency::GetByID($arFields["CURRENCY"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["CURRENCY"], GetMessage("SKGD_NO_CURRENCY")), "ERROR_NO_CURRENCY");
				return false;
			}
		}

		if (is_set($arFields, "LOCATIONS"))
		{
			$countField = count($arFields["LOCATIONS"]);
			for ($i = 0; $i < $countField; $i++)
			{
				if ($arFields["LOCATIONS"][$i]["LOCATION_TYPE"] != "G")
					$arFields["LOCATIONS"][$i]["LOCATION_TYPE"] = "L";
			}
		}

		return True;
	}

	
	/**
	 * <p>Функция изменяет параметры доставки с кодом ID на новые значения из массива arFields</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код доставки.
	 *
	 *
	 *
	 * @param array $arFields  Ассоциативный массив новых параметров доставки, ключами в
	 * котором являются названия параметров доставки, а значениями -
	 * значения параметров. <br> Допустимые ключи: <br><ul> <li> <b>NAME</b> -
	 * название доставки (обязательное, задается на языке сайта, к
	 * которому привязана эта доставка);</li> <li> <b>LID</b> - код сайта, к
	 * которому привязана эта доставка;</li> <li> <b>PERIOD_FROM</b> - минимальный
	 * срок доставки;</li> <li> <b>PERIOD_TO</b> - максимальный срок доставки;</li> <li>
	 * <b>PERIOD_TYPE</b> - единица измерения срока: D - дни, H - часы, M - месяцы;</li> <li>
	 * <b>WEIGHT_FROM</b> - минимальный вес заказа, для которого возможна эта
	 * доставка (единица измерения должна быть едина на сайте);</li> <li>
	 * <b>WEIGHT_TO</b> - максимальный вес заказа, для которого возможна эта
	 * доставка (единица измерения должна быть едина на сайте);</li> <li>
	 * <b>ORDER_PRICE_FROM</b> - минимальная стоимость заказа, для которой возможна
	 * эта доставка;</li> <li> <b>ORDER_PRICE_TO</b> - максимальная стоимость заказа,
	 * для которой возможна эта доставка;</li> <li> <b>ORDER_CURRENCY</b> - валюта
	 * ограничений по стоимости;</li> <li> <b>ACTIVE</b> - флаг (Y/N) активности
	 * доставки;</li> <li> <b>PRICE</b> - стоимость доставки;</li> <li> <b>CURRENCY</b> - валюта
	 * стоимости доставки;</li> <li> <b>SORT</b> - индекс сортировки;</li> <li>
	 * <b>DESCRIPTION</b> - описание доставки;</li> <li> <b>LOCATIONS</b> - массив массивов
	 * вида: <pre class="syntax">array("LOCATION_ID" =&gt; "код местоположения или <br> группы
	 * местоположений",<br> "LOCATION_TYPE"=&gt;"L - для местоположения, <br> G - для
	 * группы")</pre> содержащий местоположения и группы местоположений,
	 * для которых работает эта доставка</li> </ul>
	 *
	 *
	 *
	 * @return int <p>Возвращает код изменяемой записи или <i>false</i> в случае ошибки.</p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>$arFields = array(<br>   "NAME" =&gt; "Доставка курьером",<br>   "LID" =&gt; "ru",<br>   "PERIOD_FROM" =&gt; 1,<br>   "PERIOD_TO" =&gt; 3,<br>   "PERIOD_TYPE" =&gt; "D",<br>   "WEIGHT_FROM" =&gt; 0,<br>   "WEIGHT_TO" =&gt; 2500,<br>   "ORDER_PRICE_FROM" =&gt; 0,<br>   "ORDER_PRICE_TO" =&gt; 10000,<br>   "ORDER_CURRENCY" =&gt; "RUB",<br>   "ACTIVE" =&gt; "Y",<br>   "PRICE" =&gt; 58,<br>   "CURRENCY" =&gt; "RUB",<br>   "SORT" =&gt; 100,<br>   "DESCRIPTION" =&gt; "Заказ будет доставлен Вам в течение 3 - 10 рабочих дней после передачи его в курьерскую службу.",<br>   "LOCATIONS" =&gt; array(<br>      array("LOCATION_ID"=&gt;1, "LOCATION_TYPE"=&gt;"L"),<br>      array("LOCATION_ID"=&gt;3, "LOCATION_TYPE"=&gt;"G")<br>      )<br>);<br><br>if (!CSaleDelivery::Update($ID, $arFields))<br>   echo "Ошибка изменения доставки";<br>?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__update.dcec3766.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);
		
		if ($ID <= 0 || !CSaleDelivery::CheckFields("UPDATE", $arFields)) 
			return false;

		if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
			$arFields["LOGOTIP"]["MODULE_ID"] = "sale";

		CFile::SaveForDB($arFields, "LOGOTIP", "sale/delivery/logotip");

		$strUpdate = $DB->PrepareUpdate("b_sale_delivery", $arFields);

		$strSql = "UPDATE b_sale_delivery SET ".$strUpdate." WHERE ID = ".$ID."";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (is_set($arFields, "LOCATIONS"))
		{
			$DB->Query("DELETE FROM b_sale_delivery2location WHERE DELIVERY_ID = ".$ID."");

			$countarFieldLoc = count($arFields["LOCATIONS"]);
			for ($i = 0; $i < $countarFieldLoc; $i++)
			{
				$arInsert = $DB->PrepareInsert("b_sale_delivery2location", $arFields["LOCATIONS"][$i]);

				$strSql =
					"INSERT INTO b_sale_delivery2location(DELIVERY_ID, ".$arInsert[0].") ".
					"VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		if (is_set($arFields, "PAY_SYSTEM"))
		{
			CSaleDelivery::UpdateDeliveryPay($ID, $arFields["PAY_SYSTEM"]);
		}

		return $ID;
	}

	
	/**
	 * <p>Функция удаляет доставку с кодом ID </p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код доставки.
	 *
	 *
	 *
	 * @return bool <p>Возвращает <i>true</i> в случае успешного удаления и <i>false</i> - в
	 * противном случае.</p><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * CSaleDelivery::Delete(8);
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__delete.cb546e37.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		
		$db_orders = CSaleOrder::GetList(
				array("DATE_UPDATE" => "DESC"),
				array("DELIVERY_ID" => $ID)
			);
		if ($db_orders->Fetch())
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPS_ORDERS_TO_DELIVERY"), "SKGPS_ORDERS_TO_DELIVERY");
			return False;
		}
		
		$DB->Query("DELETE FROM b_sale_delivery2location WHERE DELIVERY_ID = ".$ID."", true);
		$DB->Query("DELETE FROM b_sale_delivery2paysystem WHERE DELIVERY_ID = ".$ID."", true);
		return $DB->Query("DELETE FROM b_sale_delivery WHERE ID = ".$ID."", true);
	}


	/**
	* The function select delivery and paysystem
	*
	* @param array $arFilter - array to filter
	* @return object $dbRes - object result
	*/
	public static function GetDelivery2PaySystem($arFilter = array())
	{
		global $DB;

		$strSqlSearch = "";

		foreach ($arFilter as $key => $val)
		{
			$val = $DB->ForSql($val);

			switch(ToUpper($key))
			{
			case "DELIVERY_ID":
				$strSqlSearch .= " AND DELIVERY_ID = '".trim($val)."' ";
				break;
			case "PAYSYSTEM_ID":
				$strSqlSearch .= " AND PAYSYSTEM_ID = '".IntVal($val)."' ";
				break;
			}
		}

		$strSql =
			"SELECT * ".
			"FROM b_sale_delivery2paysystem ".
			"WHERE 1 = 1";

		if (strlen($strSqlSearch) > 0)
			$strSql .= " ".$strSqlSearch;

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	/**
	* The function select delivery and paysystem
	*
	* @param int $ID - code delivery
	* @param array $arFields - paysytem
	* @return int $ID - code delivery
	*/
	public static function UpdateDeliveryPay($ID, $arFields)
	{
		global $DB;

		$ID = trim($ID);

		if (strlen($ID) <= 0)
			return false;

		if ($arFields[0] == "")
			unset($arFields[0]);

		$DB->Query("DELETE FROM b_sale_delivery2paysystem WHERE DELIVERY_ID = '".$DB->ForSql($ID)."'");

		foreach ($arFields as $val)
		{
			$arTmp = array("PAYSYSTEM_ID" => $val);
			$arInsert = $DB->PrepareInsert("b_sale_delivery2paysystem", $arTmp);

			$strSql =
				"INSERT INTO b_sale_delivery2paysystem (DELIVERY_ID, ".$arInsert[0].") ".
				"VALUES('".$ID."', ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}
}
?>