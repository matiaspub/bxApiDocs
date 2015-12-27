<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\DB;
use Bitrix\Sale\Location;
use Bitrix\Sale\Shipment;
use Bitrix\Sale\Internals\DeliveryPaySystemTable;
use Bitrix\Sale\Location\Admin\LocationHelper as Helper;

/**
 * Class CAllSaleDelivery
 * @deprecated
 */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/index.php
 * @author Bitrix
 * @deprecated
 */
class CAllSaleDelivery
{
	const CONN_ENTITY_NAME = 'Bitrix\Sale\Delivery\DeliveryLocation';

	/**
	 * @param $val
	 * @param $key
	 * @param $operation
	 * @param $negative
	 * @param $field
	 * @param $arField
	 * @param $arFilter
	 * @return bool|string
	 * @deprecated
	 * @internal
	 */
	public static function PrepareLocation24Where($val, $key, $operation, $negative, $field, &$arField, &$arFilter)
	{
		try
		{
			$class = self::CONN_ENTITY_NAME.'Table';
			return $field." in (".$class::getConnectedEntitiesQuery(IntVal($val), 'id', array('select' => array('ID'))).")";
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	/**
	 * @param $arOrder
	 * @param $deliveryCode
	 * @param $arErrors
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 * @internal
	 * @deprecated
	 */
	static function DoProcessOrder(&$arOrder, $deliveryCode, &$arErrors)
	{
		if(strlen($deliveryCode) <= 0 || $deliveryCode == '0')
			return false;

		if ($service = \Bitrix\Sale\Delivery\Services\Manager::getServiceByCode($deliveryCode))
		{
			$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');

			$arOrderTmpDel = array(
				"PRICE" => $arOrder["ORDER_PRICE"] + $arOrder["TAX_PRICE"] - $arOrder["DISCOUNT_PRICE"],
				"WEIGHT" => $arOrder["ORDER_WEIGHT"],
				"LOCATION_FROM" => COption::GetOptionString('sale', 'location', '2961', $arOrder["SITE_ID"]),
				"LOCATION_TO" => isset($arOrder["DELIVERY_LOCATION"]) ? $arOrder["DELIVERY_LOCATION"] : 0,
				"LOCATION_ZIP" => $arOrder["DELIVERY_LOCATION_ZIP"],
				"ITEMS" => $arOrder["BASKET_ITEMS"],
				"CURRENCY" => $arOrder["CURRENCY"]
			);

			if ($isOrderConverted == "Y"
				&& !empty($arOrder['ORDER_PROP']) && is_array($arOrder['ORDER_PROP']))
			{
				$arOrderTmpDel['PROPERTIES'] = $arOrder['ORDER_PROP'];
			}

			//$r = $propCollection->setValuesFromPost($fields, $_FILES);

			$arOrder["DELIVERY_ID"] = $deliveryCode;
			$shipment = self::convertOrderOldToNew($arOrderTmpDel);

			if(isset($arOrder["DELIVERY_EXTRA_SERVICES"]))
				$service->getExtraServices()->setValues($arOrder["DELIVERY_EXTRA_SERVICES"]);

			$calculationResult = $service->calculate($shipment);

			if (!$calculationResult->isSuccess())
				$arErrors[] = array("CODE" => "CALCULATE", "TEXT" => implode("<br>\n", $calculationResult->getErrorMessages()));
			else
				$arOrder["DELIVERY_PRICE"] = roundEx($calculationResult->getPrice(), SALE_VALUE_PRECISION);
		}
		else
		{
			$arErrors[] = array("CODE" => "CALCULATE", "TEXT" => GetMessage('SKGD_DELIVERY_NOT_FOUND'));
		}
	}

	/**
	 * @deprecated Use \Bitrix\Sale\Delivery\Services\Manager
	 */
	public static function DoLoadDelivery($location, $locationZip, $weight, $price, $currency, $siteId = null, $arShoppingCart = array())
	{
		$location = intval($location);
		if ($location <= 0)
			return null;

		if ($siteId == null)
			$siteId = SITE_ID;

		$arResult = array();
		$arMaxDimensions = array();

		foreach ($arShoppingCart as $arBasketItem)
		{
			if (!is_array($arBasketItem["DIMENSIONS"]))
			{
				$arDim = unserialize($arBasketItem["~DIMENSIONS"]);
				$arBasketItem["DIMENSIONS"] = $arDim;
				unset($arBasketItem["~DIMENSIONS"]);
			}
			else
				$arDim = $arBasketItem["DIMENSIONS"];

			if (is_array($arDim))
			{
				$arMaxDimensions = CSaleDeliveryHelper::getMaxDimensions(
					array($arDim["WIDTH"], $arDim["HEIGHT"], $arDim["LENGTH"]),
					$arMaxDimensions
				);
			}
		}

		$arFilter = array(
			"COMPABILITY" => array(
				"WEIGHT" => $weight,
				"PRICE" => $price,
				"LOCATION_FROM" => COption::GetOptionString('sale', 'location', false, $siteId),
				"LOCATION_TO" => $location,
				"LOCATION_ZIP" => $locationZip,
				"MAX_DIMENSIONS" => $arMaxDimensions,
				"ITEMS" => $arShoppingCart
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
						"DESCRIPTION" => $arDeliveryService["~DESCRIPTION"],
						"PROFILES" => array(),
					);
				}

				$arResult[$arDeliveryService["SID"]]["PROFILES"][$profileId] = array(
					"ID" => $arDeliveryService["SID"].":".$profileId,
					"SID" => $profileId,
					"TITLE" => $arDeliveryProfile["TITLE"],
					"DESCRIPTION" => $arDeliveryProfile["~DESCRIPTION"],
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
						"ITEMS" => $arShoppingCart
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
	 * @deprecated Use \Bitrix\Sale\Delivery\Services\Table::getById().
	 */
	
	/**
	* <p>Метод возвращает параметры службы доставки с кодом ID. Метод статический.</p>
	*
	*
	* @param int $ID  Код службы доставки.
	*
	* @return array <p>Возвращается ассоциативный массив параметров доставки с
	* ключами:</p> <table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th> <th>Описание</th>
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
	* сортировки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание доставки.</td> </tr> </table>
	* <p> </p<a name="examples"></a>
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
	* @deprecated Use \Bitrix\Sale\Delivery\Services\Table::getById().
	*/
	public static function GetByID($ID)
	{
		$res = self::GetList(array(), array("ID" => $ID));
		return $res->Fetch();
	}


	/**
	 * @param array $arFilter
	 * @return bool|CDBResult
	 * @deprecated
	 */
	
	/**
	* <p>Метод возвращает набор местоположений по фильтру arFilter. Метод динамичный.</p>
	*
	*
	* @param array $arrayarFilter = Array() Фильтр представляет собой ассоциативный массив, в котором
	* ключами являются названия параметров, а значениями - условия.<br><br>
	* Допустимые ключи:<br><ul> <li> <b>DELIVERY_ID</b> - код доставки;</li> <li> <b>LOCATION_ID</b>
	* - код местоположения или группы местоположений;</li> <li> <b>LOCATION_TYPE</b> -
	* тип (L - местоположение, G - группа местоположений).</li> </ul>
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>DELIVERY_ID</td> <td>Код службы
	* доставки.</td> </tr> <tr> <td>LOCATION_ID</td> <td>Код местоположения или группы
	* местоположений.</td> </tr> <tr> <td>LOCATION_TYPE</td> <td>Тип (L - местоположение, G -
	* группа местоположений) </td> </tr> </table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__getlocationlist.e2a07bf9.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetLocationList($arFilter = Array())
	{
		$deliveryId = 0;

		if(!empty($arFilter['DELIVERY_ID']))
		{
			$deliveryId = $arFilter['DELIVERY_ID'];
			$arFilter['DELIVERY_ID'] = \Bitrix\Sale\Delivery\Services\Table::getIdByCode($deliveryId);
		}

		try
		{
			$locations = array();
			$res =  CSaleLocation::getDenormalizedLocationList(self::CONN_ENTITY_NAME, $arFilter);

			while($loc = $res->Fetch())
			{
				 $loc['DELIVERY_ID'] = $deliveryId;
				 $locations[] = $loc;
			}
		}
		catch(Exception $e)
		{
			$locations = array();
		}

		$dbResult = new CDBResult();
		$dbResult->InitFromArray($locations);
		return $dbResult;
	}

	/**
	 * @deprecated
	 * @internal
	 */
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
	 * @param $ID
	 * @param $locations
	 * @internal
	 * @deprecated
	 */
	public static function SetDeliveryLocationPro($ID, $locations)
	{
		$class = self::CONN_ENTITY_NAME.'Table';

		$links = Helper::prepareLinksForSaving($class, $locations);
		$class::resetMultipleForOwner($ID, $links);
	}

	/**
	 * @deprecated
	 */
	
	/**
	* <p>Метод изменяет параметры доставки с кодом ID на новые значения из массива arFields. Метод статический.</p>
	*
	*
	* @param int $ID  Код доставки.
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
	* @return int <p>Возвращает код изменяемой записи или <i>false</i> в случае ошибки.</p>
	* <a name="examples"></a>
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
	* @deprecated
	*/
	public static function Update($oldId, $arFields, $arOptions = array())
	{
		if(strlen($oldId) <= 0)
			return false;

		$dbRes = Bitrix\Sale\Delivery\Services\Table::getList(array(
			'filter' => array(
				"CODE" => $oldId,
				"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Services\Configurable'
			)
		));

		$oldData = $dbRes->fetch();

		if(!$oldData)
			return false;

		$newId = $oldData["ID"];

		$fields = array_intersect_key($arFields, Bitrix\Sale\Delivery\Services\Table::getMap());

		if(!empty($fields))
		{
			if(array_key_exists("LOGOTIP", $fields) && is_array($fields["LOGOTIP"]))
			{
				$fields["LOGOTIP"]["MODULE_ID"] = "sale";
				CFile::SaveForDB($fields, "LOGOTIP", "sale/delivery/logotip");
			}

			$fields["CONFIG"] = array(
				"MAIN" => array(
					"PRICE" => isset($arFields["PRICE"]) ? $arFields["PRICE"] : $oldData["CONFIG"]["MAIN"]["PRICE"],
					"PERIOD" => array(
						"FROM" => isset($arFields["PERIOD_FROM"]) ? $arFields["PERIOD_FROM"] : $oldData["CONFIG"]["MAIN"]["PERIOD"]["FROM"],
						"TO" => isset($arFields["PERIOD_TO"]) ? $arFields["PERIOD_TO"] : $oldData["CONFIG"]["MAIN"]["PERIOD"]["TO"],
						"TYPE" => isset($arFields["PERIOD_TYPE"]) ? $arFields["PERIOD_TYPE"] : $oldData["CONFIG"]["MAIN"]["PERIOD"]["TYPE"]
					)
				)
			);

			$res = \Bitrix\Sale\Delivery\Services\Table::update($newId, $fields);

			if(!$res->isSuccess())
				return false;
		}

		if(is_set($arFields, "LOCATIONS"))
			Helper::resetLocationsForEntity($newId, $arFields['LOCATIONS'], self::CONN_ENTITY_NAME, !!$arOptions['EXPECT_LOCATION_CODES']);

		if (is_set($arFields, "PAY_SYSTEM"))
			CSaleDelivery::UpdateDeliveryPay($newId, $arFields["PAY_SYSTEM"]);

		if(isset($arFields["LID"]))
		{
			$rfields = array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite',
				"PARAMS" => array(
					"SITE_ID" => $arFields["LID"]
				)
			);

			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $newId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		if(isset($arFields["LID"]) && strlen($arFields["LID"]) > 0)
		{
			$rfields = array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite',
				"PARAMS" => array(
					"SITE_ID" => $arFields["LID"]
				)
			);

			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $newId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite'
				)
			));

			if($restrict = $rstrRes->fetch())
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
			else
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
		}

		if(isset($arFields["WEIGHT_FROM"]) || isset($arFields["WEIGHT_TO"]))
		{
			$rfields = array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByWeight',
				"PARAMS" => array(
					"MIN_WEIGHT" => isset($arFields["WEIGHT_FROM"]) ? $arFields["WEIGHT_FROM"] : 0,
					"MAX_WEIGHT" => isset($arFields["WEIGHT_TO"]) ? $arFields["WEIGHT_TO"] : 0
				)
			);

			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $newId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByWeight'
				)
			));

			if($restrict = $rstrRes->fetch())
			{
				if(floatval($arFields["WEIGHT_FROM"]) <= 0 && floatval($arFields["WEIGHT_TO"]) <= 0)
				{
					$rres = \Bitrix\Sale\Delivery\Restrictions\Table::delete($restrict["ID"]);
				}
				else
				{
					if(!isset($arFields["WEIGHT_FROM"]))
						$rfields["PARAMS"]["MIN_WEIGHT"] = $restrict["PARAMS"]["MIN_WEIGHT"];

					if(!isset($arFields["WEIGHT_TO"]))
						$rfields["PARAMS"]["MAX_WEIGHT"] = $restrict["PARAMS"]["MAX_WEIGHT"];

					$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
				}
			}
			else
			{
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
			}

		}

		if(isset($arFields["ORDER_PRICE_FROM"]) || isset($arFields["ORDER_PRICE_TO"]) || isset($arFields["ORDER_CURRENCY"]))
		{
			$rfields = array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPrice',
				"PARAMS" => array(
					"MIN_PRICE" => isset($arFields["ORDER_PRICE_FROM"]) ? $arFields["ORDER_PRICE_FROM"] : 0,
					"MAX_PRICE" => isset($arFields["ORDER_PRICE_TO"]) ? $arFields["ORDER_PRICE_TO"] : 0,
					"CURRENCY" => isset($arFields["ORDER_CURRENCY"]) ? $arFields["ORDER_CURRENCY"] : ""
				)
			);

			$rstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' =>array(
					"DELIVERY_ID" => $newId,
					"=CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPrice'
				)
			));

			if($restrict = $rstrRes->fetch())
			{
				if(floatval($arFields["ORDER_PRICE_FROM"]) <= 0 && floatval($arFields["ORDER_PRICE_TO"]) <= 0 && strlen($arFields["ORDER_CURRENCY"]) <= 0)
				{
					$rres = \Bitrix\Sale\Delivery\Restrictions\Table::delete($restrict["ID"]);
				}
				else
				{
					if(!isset($arFields["ORDER_PRICE_FROM"]))
						$rfields["PARAMS"]["MIN_PRICE"] = $restrict["PARAMS"]["MIN_PRICE"];

					if(!isset($arFields["ORDER_PRICE_TO"]))
						$rfields["PARAMS"]["MAX_PRICE"] = $restrict["PARAMS"]["MAX_PRICE"];

					if(!isset($arFields["ORDER_CURRENCY"]))
						$rfields["PARAMS"]["CURRENCY"] = $restrict["PARAMS"]["CURRENCY"];

					$rres = \Bitrix\Sale\Delivery\Restrictions\Table::update($restrict["ID"], $rfields);
				}
			}
			else
			{
				$rres = \Bitrix\Sale\Delivery\Restrictions\Table::add($rfields);
			}
		}

		if(isset($arFields["STORE"]))
		{
			$stores = unserialize($arFields["STORE"]);

			if($stores)
				\Bitrix\Sale\Delivery\ExtraServices\Manager::saveStores($newId, $stores);
		}


		return $oldId;
	}

	/**
	 * @deprecated
	 */
	
	/**
	* <p>Метод удаляет доставку с кодом ID. Метод статический.</p>
	*
	*
	* @param int $ID  Код доставки.
	*
	* @return bool <p>Возвращает <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <a name="examples"></a>
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
	* @deprecated
	*/
	public static function Delete($ID)
	{
		$newId = \Bitrix\Sale\Delivery\Services\Table::getIdByCode($ID);

		try
		{
			$res = \Bitrix\Sale\Delivery\Services\Table::delete($newId);
		}
		catch(\Bitrix\Main\SystemException $e)
		{
			$GLOBALS["APPLICATION"]->ThrowException($e->getMessage());
			return false;
		}

		return new CDBResult($res);
	}


	/**
	 * The function select delivery and paysystem
	 *
	 * @param array $arFilter - array to filter
	 * @return object $dbRes - object result
	 * @deprecated
	 */
	public static function GetDelivery2PaySystem($arFilter = array())
	{
		if(isset($arFilter["DELIVERY_ID"]))
			$arFilter["DELIVERY_ID"] = \Bitrix\Sale\Delivery\Services\Table::getIdByCode($arFilter["DELIVERY_ID"]);

		return CSaleDelivery2PaySystem::GetList(
			$arFilter,
			array("DELIVERY_ID", "PAYSYSTEM_ID"),
			array("DELIVERY_ID", "PAYSYSTEM_ID")
		);
	}

	/**
	 * The function updates delivery and paysystem
	 *
	 * @param int $ID - code delivery
	 * @param array $arFields - paysytem
	 * @return int $ID - code delivery
	 * @deprecated
	 */
	static function UpdateDeliveryPay($ID, $arFields)
	{
		$ID = trim($ID);

		if (strlen($ID) <= 0 || !is_array($arFields) || empty($arFields))
			return false;

		if ($arFields[0] == "")
			unset($arFields[0]);

		return CSaleDelivery2PaySystem::UpdateDelivery($ID, array("PAYSYSTEM_ID" => $arFields));
	}

	/**
	 * @param $fieldName
	 * @param $filter
	 * @return bool
	 */
	protected static function getFilterValue($fieldName, $filter)
	{
		$result = false;

		foreach($filter as $fName => $fValue)
			if(preg_replace('/[^A-Z_]/', '', $fName) == $fieldName)
				return $fValue;

		return $result;
	}

	/**
	 * @param $fieldName
	 * @param $filter
	 * @return bool
	 */
	protected static function isFieldInFilter($fieldName, $filter)
	{
		$res = array_key_exists(preg_replace('/[^A-Z_]/', '', $fieldName), $filter);
		return $res;
	}

	/**
	 * @param $fieldName
	 * @param $filter
	 * @return bool
	 */
	protected static function isFieldInFilter2($fieldName, $filter)
	{
		$result = false;

		foreach($filter as $key => $value)
			if(preg_replace('/[^A-Z_]/', '', $key) == $fieldName)
				return true;

		return $result;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	protected static function hasNewServiceField($name)
	{
		$serviceFields = \Bitrix\Sale\Delivery\Services\Table::getMap();
		return self::isFieldInFilter($name, $serviceFields);
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	protected static function convertFilterOldToNew(array $filter = array())
	{
		if(empty($filter))
			return array();

		$result = array();

		if(isset($filter["ID"]))
		{
			$filter["CODE"] = $filter["ID"];
			unset($filter["ID"]);
		}

		foreach($filter as $fieldName => $fieldValue)
			if(self::hasNewServiceField($fieldName))
				$result[$fieldName] = $fieldValue;

		return $result;
	}

	/**
	 * @param $groupBy
	 * @return array
	 */
	protected static function convertGroupOldToNew($groupBy)
	{
		if(!is_array($groupBy) || empty($groupBy))
			return array();

		$result = array();
		$serviceFields = Bitrix\Sale\Delivery\Services\Table::getMap();

		foreach($groupBy as $group)
			if(array_key_exists($group, $serviceFields))
				$result[] = $group;

		return $result;
	}

	/**
	 * @param array $selectFields
	 * @return array
	 */
	protected static function convertSelectOldToNew(array $selectFields = array())
	{
		if(empty($selectFields))
			return array();

		if(in_array('*', $selectFields))
			return array('*');

		$result = array();
		$serviceFields = Bitrix\Sale\Delivery\Services\Table::getMap();

		if(isset($selectFields["ID"]))
		{
			$selectFields["CODE"] = $selectFields["ID"];
			unset($selectFields["ID"]);
		}

		foreach($selectFields as $select)
			if(array_key_exists($select, $serviceFields))
				$result[] = $select;

		return $result;
	}

	/**
	 * @param $fieldName
	 * @param array $select
	 * @return bool
	 */
	protected static function isFieldSelected($fieldName , array $select)
	{
		$result = empty($select) || in_array($fieldName, $select) || in_array("*", $select);
		return $result;
	}

	/**
	 * @param array $restriction
	 * @param array $filter
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function checkRestrictionFilter(array $restriction, array $filter)
	{
		$result = true;

		switch($restriction["CLASS_NAME"])
		{
			case '\Bitrix\Sale\Delivery\Restrictions\BySite':

				$fieldInFilter = self::isFieldInFilter2("LID", $filter);
				$value = self::getFilterValue("LID", $filter);

				if(!$fieldInFilter)
					break;

				if(is_array($restriction["PARAMS"]["SITE_ID"]))
					$result = in_array($value, $restriction["PARAMS"]["SITE_ID"]);
				else
					$result = ($value == $restriction["PARAMS"]["SITE_ID"]);

				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByWeight':
				$result = !(self::isFieldInFilter2("WEIGHT_FROM", $filter) && floatval(self::getFilterValue("WEIGHT_FROM", $filter)) < floatval($restriction["PARAMS"]["MIN_WEIGHT"]));
				$result = $result && !(self::isFieldInFilter2("WEIGHT_TO", $filter) && floatval(self::getFilterValue("WEIGHT_TO", $filter)) > floatval($restriction["PARAMS"]["MAX_WEIGHT"]));
				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByPrice':

				$fieldInFilter = self::isFieldInFilter2("ORDER_PRICE_FROM", $filter);
				$value = self::getFilterValue("ORDER_PRICE_FROM", $filter);
				$value = floatval($value);

				if($fieldInFilter && $value > 0 && floatval($restriction["PARAMS"]["MIN_PRICE"]) > 0)
				{
					$result = floatval($value) > floatval($restriction["PARAMS"]["MIN_PRICE"]);

					if(!$result)
						break;
				}

				$fieldInFilter = self::isFieldInFilter2("ORDER_PRICE_TO", $filter);
				$value = self::getFilterValue("ORDER_PRICE_TO", $filter);
				$value = floatval($value);

				if($fieldInFilter && $value > 0 && floatval($restriction["PARAMS"]["MAX_PRICE"]) > 0)
				{
					$result = floatval($value) < floatval($restriction["PARAMS"]["MAX_PRICE"]);

					if(!$result)
						break;
				}

				$fieldInFilter = self::isFieldInFilter2("ORDER_CURRENCY", $filter);
				$value = self::getFilterValue("ORDER_CURRENCY", $filter);

				if($fieldInFilter && strlen($value) > 0 && strlen($restriction["PARAMS"]["CURRENCY"]) > 0)
				{
					$result = ($value == $restriction["PARAMS"]["CURRENCY"]);

					if(!$result)
						break;
				}

				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByLocation':
				$fieldInFilter = self::isFieldInFilter2("LOCATION", $filter);
				$value = self::getFilterValue("LOCATION", $filter);

				if($fieldInFilter && strlen($value) > 0)
				{
					$result = \Bitrix\Sale\Delivery\Services\Manager::checkServiceRestriction(
						$restriction['DELIVERY_ID'],
						$value,
						'\Bitrix\Sale\Delivery\Restrictions\ByLocation'
					);
				}
				break;

			default:
				break;
		}

		return $result;
	}

	/**
	 * @param array $service
	 * @param array $restriction
	 * @param array $selectedFields
	 * @return array
	 */
	protected static function getSelectedRestrictionField(array $service, array $restriction, array $selectedFields)
	{
		$fields = array();

		switch($restriction["CLASS_NAME"])
		{
			case '\Bitrix\Sale\Delivery\Restrictions\BySite':

				if(self::isFieldSelected("LID", $selectedFields))
				{
					$lids = $restriction["PARAMS"]["SITE_ID"];

					if(is_array($lids))
					{
						reset($lids);
						$fields["LID"] = current($lids);
					}
					else
					{
						$fields["LID"] = $lids;
					}
				}

				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByWeight':

				if(self::isFieldSelected("WEIGHT_FROM", $selectedFields))
					$fields["WEIGHT_FROM"] = $restriction["PARAMS"]["MIN_WEIGHT"];

				if(self::isFieldSelected("WEIGHT_TO", $selectedFields))
					$fields["WEIGHT_TO"] = $restriction["PARAMS"]["MAX_WEIGHT"];

				break;

			case '\Bitrix\Sale\Delivery\Restrictions\ByPrice':

				if(self::isFieldSelected("ORDER_PRICE_FROM", $selectedFields))
					$fields["ORDER_PRICE_FROM"] = $restriction["PARAMS"]["MIN_PRICE"];

				if(self::isFieldSelected("ORDER_PRICE_TO", $selectedFields))
					$fields["ORDER_PRICE_TO"] = $restriction["PARAMS"]["MAX_PRICE"];

				if(self::isFieldSelected("ORDER_CURRENCY", $selectedFields))
					$fields["ORDER_CURRENCY"] = $restriction["PARAMS"]["CURRENCY"];

				break;

			default:
				break;
		}

		if(!empty($fields))
			$service = array_merge($service, $fields);

		return $service;
	}

	/**
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param bool $arGroupBy
	 * @param bool $arNavStartParams
	 * @param array $arSelectFields
	 * @return \CDBResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @deprecated
	 */
	
	/**
	* <p>Метод возвращает результат выборки записей из служб доставки в соответствии со своими параметрами. Метод статический.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: <pre class="syntax">array(<br>"название_поля1" =&gt;
	* "направление_сортировки1",<br>"название_поля2" =&gt;
	* "направление_сортировки2",<br>. . .<br>)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле корзины, а в качестве
	* "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>" (по
	* возрастанию) и "<i>DESC</i>" (по убыванию). <br><br> Если массив сортировки
	* имеет несколько элементов, то результирующий набор сортируется
	* последовательно по каждому элементу (т.е. сначала сортируется по
	* первому элементу, потом результат сортируется по второму и т.д.). 
	* <br><br> Значение по умолчанию - пустой массив array() - означает, что
	* результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются записи службы
	* доставки. Массив имеет вид: <pre
	* class="syntax">array(<br>"[модификатор1][оператор1]название_поля1" =&gt;
	* "значение1",<br>"[модификатор2][оператор2]название_поля2" =&gt;
	* "значение2",<br>. . .<br>)</pre> Удовлетворяющие фильтру записи
	* возвращаются в результате, а записи, которые не удовлетворяют
	* условиям фильтра, отбрасываются. <br><br> Допустимыми являются
	* следующие модификаторы: <ul> <li> <b> !</b> - отрицание;</li> <li> <b> +</b> -
	* значения null, 0 и пустая строка так же удовлетворяют условиям
	* фильтра.</li> </ul> Допустимыми являются следующие операторы: <ul> <li>
	* <b>&gt;= </b> - значение поля больше или равно передаваемой в фильтр
	* величины;</li> <li> <b>&gt;</b> - значение поля строго больше передаваемой
	* в фильтр величины;</li> <li> <b>&lt;=</b> - значение поля меньше или равно
	* передаваемой в фильтр величины;</li> <li> <b>&lt;</b> - значение поля
	* строго меньше передаваемой в фильтр величины;</li> <li> <b>@</b> -
	* значение поля находится в передаваемом в фильтр разделенном
	* запятой списке значений;</li> <li> <b>~</b> - значение поля проверяется на
	* соответствие передаваемому в фильтр шаблону;</li> <li> <b>%</b> -
	* значение поля проверяется на соответствие передаваемой в фильтр
	* строке в соответствии с языком запросов.</li> </ul> В качестве
	* "название_поляX" может стоять любое поле корзины. <br><br> Пример
	* фильтра: <pre class="syntax">array("+&lt;=WEIGHT_FROM" =&gt; 1000)</pre> Этот фильтр означает
	* "выбрать все записи, в которых значение в поле WEIGHT_FROM (вес от)
	* меньше либо равно 1000 или значение не установлено (null или ноль)".
	* <br><br> Значение по умолчанию - пустой массив array() - означает, что
	* результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи служб доставки.
	* Массив имеет вид: <pre class="syntax">array("название_поля1",<br>
	* "группирующая_функция2" =&gt; "название_поля2", ...)</pre> В качестве
	* "название_поля<i>N</i>" может стоять любое поле служб доставки. В
	* качестве группирующей функции могут стоять: <ul> <li> <b> COUNT</b> -
	* подсчет количества;</li> <li> <b>AVG</b> - вычисление среднего значения;</li>
	* <li> <b>MIN</b> - вычисление минимального значения;</li> <li> <b> MAX</b> -
	* вычисление максимального значения;</li> <li> <b>SUM</b> - вычисление
	* суммы.</li> </ul> Если массив пустой, то метод вернет число записей,
	* удовлетворяющих фильтру. <br><br> Значение по умолчанию - <i>false</i> -
	* означает, что результат группироваться не будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: <ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> <li> любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> в качестве третьего
	* параметра.</li> </ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение "*", то будут возвращены все доступные поля.
	* <br><br> Значение по умолчанию - пустой массив array() - означает, что
	* будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий набор
	* ассоциативных массивов параметров доставки с ключами:</p> <table
	* width="100%" class="tnormal"><tbody> <tr> <th width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td>
	* <td>Код службы доставки.</td> </tr> <tr> <td>NAME</td> <td>Название доставки.</td>
	* </tr> <tr> <td>LID</td> <td>Код сайта, к которому привязана эта доставка.</td>
	* </tr> <tr> <td>PERIOD_FROM</td> <td>Минимальный срок доставки.</td> </tr> <tr> <td>PERIOD_TO</td>
	* <td>Максимальный срок доставки.</td> </tr> <tr> <td>PERIOD_TYPE</td> <td>Единица
	* измерения срока: D - дни, H - часы, M - месяцы.</td> </tr> <tr> <td>WEIGHT_FROM</td>
	* <td>Минимальный вес заказа, для которого возможна эта доставка
	* (единица измерения едина на сайте).</td> </tr> <tr> <td>WEIGHT_TO</td>
	* <td>Максимальный вес заказа, для которого возможна эта доставка
	* (единица измерения едина на сайте).</td> </tr> <tr> <td>ORDER_PRICE_FROM</td>
	* <td>Минимальная стоимость заказа, для которой возможна эта
	* доставка.</td> </tr> <tr> <td>ORDER_PRICE_TO</td> <td>Максимальная стоимость заказа,
	* для которой возможна эта доставка.</td> </tr> <tr> <td>ORDER_CURRENCY</td> <td>Валюта
	* ограничений по стоимости.</td> </tr> <tr> <td>ACTIVE</td> <td>Флаг (Y/N) активности
	* доставки.</td> </tr> <tr> <td>PRICE</td> <td>Стоимость доставки.</td> </tr> <tr>
	* <td>CURRENCY</td> <td>Валюта стоимости доставки.</td> </tr> <tr> <td>SORT</td> <td>Индекс
	* сортировки.</td> </tr> <tr> <td>DESCRIPTION</td> <td>Описание доставки.</td> </tr>
	* </tbody></table> <p>Если в качестве параметра <b> arGroupBy</b> передается пустой
	* массив, то метод вернет число записей, удовлетворяющих фильтру.</p>
	* <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>// Выберем отсортированные по индексу сортировки, а потом (при равных индексах) по имени<br>// активные службы доставки, доступные для текущего сайта, заказа с весом $ORDER_WEIGHT и <br>// стоимостью $ORDER_PRICE (в базовой валюте текущего сайта), доставки в <br>// местоположение $DELIVERY_LOCATION<br>$db_dtype = CSaleDelivery::GetList(<br>    array(<br>            "SORT" =&gt; "ASC",<br>            "NAME" =&gt; "ASC"<br>        ),<br>    array(<br>            "LID" =&gt; SITE_ID,<br>            "+&lt;=WEIGHT_FROM" =&gt; $ORDER_WEIGHT,<br>            "+&gt;=WEIGHT_TO" =&gt; $ORDER_WEIGHT,<br>            "+&lt;=ORDER_PRICE_FROM" =&gt; $ORDER_PRICE,<br>            "+&gt;=ORDER_PRICE_TO" =&gt; $ORDER_PRICE,<br>            "ACTIVE" =&gt; "Y",<br>            "LOCATION" =&gt; $DELIVERY_LOCATION<br>        ),<br>    false,<br>    false,<br>    array()<br>);<br>if ($ar_dtype = $db_dtype-&gt;Fetch())<br>{<br>   echo "Вам доступны следующие способы доставки:&lt;br&gt;";<br>   do<br>   {<br>      echo $ar_dtype["NAME"]." - стоимость ".CurrencyFormat($ar_dtype["PRICE"], $ar_dtype["CURRENCY"])."&lt;br&gt;";<br>   }<br>   while ($ar_dtype = $db_dtype-&gt;Fetch());<br>}<br>else<br>{<br>   echo "Доступных способов доставки не найдено&lt;br&gt;";<br>}<br>?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__getlist.28cc1782.php
	* @author Bitrix
	* @deprecated
	*/
	public static function GetList($arOrder = array("SORT" => "ASC", "NAME" => "ASC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array('*'))
	{
		if(empty($arSelectFields))
			$arSelectFields = array('*');

		$params = array(
			'order' => $arOrder,
			'filter' => self::convertFilterOldToNew($arFilter),
			'group' => self::convertGroupOldToNew($arGroupBy),
			'select' => self::convertSelectOldToNew($arSelectFields)
		);

		$services = array();
		$params['filter']['=CLASS_NAME'] = '\Bitrix\Sale\Delivery\Services\Configurable';
		$dbRes = \Bitrix\Sale\Delivery\Services\Table::getList($params);

		if (isset($arFilter["WEIGHT"]) && DoubleVal($arFilter["WEIGHT"]) > 0)
		{
			if (!isset($arFilter["WEIGHT_FROM"]) || floatval($arFilter["WEIGHT"]) > floatval($arFilter["WEIGHT_FROM"]))
				$arFilter["+<=WEIGHT_FROM"] = $arFilter["WEIGHT"];
			if (!isset($arFilter["WEIGHT_TO"]) || floatval($arFilter["WEIGHT"]) < floatval($arFilter["WEIGHT_TO"]))
				$arFilter["+>=WEIGHT_TO"] = $arFilter["WEIGHT"];
		}

		if (isset($arFilter["ORDER_PRICE"]) && IntVal($arFilter["ORDER_PRICE"]) > 0)
		{
			if (!isset($arFilter["ORDER_PRICE_FROM"]) || floatval($arFilter["ORDER_PRICE"]) > floatval($arFilter["ORDER_PRICE_FROM"]))
				$arFilter["+<=ORDER_PRICE_FROM"] = $arFilter["ORDER_PRICE"];
			if (!isset($arFilter["ORDER_PRICE_TO"]) || floatval($arFilter["ORDER_PRICE"]) < floatval($arFilter["ORDER_PRICE_TO"]))
				$arFilter["+>=ORDER_PRICE_TO"] = $arFilter["ORDER_PRICE"];
		}

		while($service = $dbRes->fetch())
		{
			$dbRstrRes = \Bitrix\Sale\Delivery\Restrictions\Table::getList(array(
				'filter' => array(
					"=DELIVERY_ID" => $service["ID"],
				)
			));

			while($restr = $dbRstrRes->fetch())
			{
				if(!self::checkRestrictionFilter($restr, $arFilter))
					continue(2);

				$service = self::getSelectedRestrictionField($service, $restr, $arSelectFields);
			}

			$selectAsterisk = in_array('*', $arSelectFields);
			$mofifiedFields = array("LID", "WEIGHT_FROM", "WEIGHT_TO","ORDER_PRICE_FROM", "ORDER_PRICE_TO", "ORDER_CURRENCY");

			foreach($mofifiedFields as $field)
				if(($selectAsterisk || in_array($field, $arSelectFields)) && !array_key_exists($field, $service))
					$service[$field] = "";

			if($selectAsterisk || in_array("PERIOD_FROM", $arSelectFields))
				$service["PERIOD_FROM"] = $service["CONFIG"]["MAIN"]["PERIOD"]["FROM"];

			if($selectAsterisk || in_array("PERIOD_TO", $arSelectFields))
				$service["PERIOD_TO"] = $service["CONFIG"]["MAIN"]["PERIOD"]["TO"];

			if($selectAsterisk || in_array("PERIOD_TYPE", $arSelectFields))
				$service["PERIOD_TYPE"] = $service["CONFIG"]["MAIN"]["PERIOD"]["TYPE"];

			if($selectAsterisk || in_array("PRICE", $arSelectFields))
			{
				$service["CLASS_NAME"] = '\Bitrix\Sale\Delivery\Services\Configurable';
				$tmpSrv = \Bitrix\Sale\Delivery\Services\Manager::createServiceObject($service);
				$res = $tmpSrv->calculate();
				$service["PRICE"] = $res->getPrice();
			}

			if($selectAsterisk || in_array("STORE", $arSelectFields))
			{
				$stores = \Bitrix\Sale\Delivery\ExtraServices\Manager::getStoresList($service["ID"]);
				$service["STORE"] = count($stores) > 0 ? serialize($stores) : "";
			}

			if(intval($service["CODE"]) > 0)
				$service["ID"] = $service["CODE"];

			unset($service["CODE"], $service["CLASS_NAME"], $service["CONFIG"], $service["PARENT_ID"]);
			$services[] = $service;
		}

		$result = new \CDBResult;
		$result->InitFromArray($services);

		return $result;
	}

	/**
	 * @param $arFields
	 * @param array $arOptions
	 * @return bool|int
	 * @throws Exception
	 * @deprecated
	 */
	
	/**
	* <p>Метод добавляет новый способ (службу) доставки с параметрами из массива arFields. Метод статический.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров доставки, ключами в котором
	* являются названия параметров доставки, а значениями - значения
	* параметров. <br><br> Допустимые ключи: <br><ul> <li> <b>NAME</b> - название
	* доставки (обязательное, задается на языке сайта, к которому
	* привязана эта доставка);</li> <li> <b>LID</b> - код сайта, к которому
	* привязана эта доставка;</li> <li> <b>PERIOD_FROM</b> - минимальный срок
	* доставки;</li> <li> <b>PERIOD_TO</b> - максимальный срок доставки;</li> <li>
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
	* для которых работает эта доставка</li> <li> <b>LOGOTIP</b> - логотип.</li> </ul>
	*
	* @return int <p>Возвращает код добавленной записи или <i>false</i> в случае ошибки.</p>
	* <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?<br>$arFields = array(<br>   "NAME" =&gt; "Доставка курьером",<br>   "LID" =&gt; "ru",<br>   "PERIOD_FROM" =&gt; 1,<br>   "PERIOD_TO" =&gt; 3,<br>   "PERIOD_TYPE" =&gt; "D",<br>   "WEIGHT_FROM" =&gt; 0,<br>   "WEIGHT_TO" =&gt; 2500,<br>   "ORDER_PRICE_FROM" =&gt; 0,<br>   "ORDER_PRICE_TO" =&gt; 10000,<br>   "ORDER_CURRENCY" =&gt; "RUB",<br>   "ACTIVE" =&gt; "Y",<br>   "PRICE" =&gt; 58,<br>   "CURRENCY" =&gt; "RUB",<br>   "SORT" =&gt; 100,<br>   "DESCRIPTION" =&gt; "Заказ будет доставлен Вам в течение 3 - 10 рабочих дней после передачи его в курьерскую службу.",<br>   "LOCATIONS" =&gt; array(<br>      array("LOCATION_ID"=&gt;1, "LOCATION_TYPE"=&gt;"L"),<br>      array("LOCATION_ID"=&gt;3, "LOCATION_TYPE"=&gt;"G")<br>      )<br>);<br><br>$ID = CSaleDelivery::Add($arFields);<br>if ($ID&lt;=0)<br>   echo "Ошибка добавления доставки";<br>?&gt;<br>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaledelivery/csaledelivery__add.564001a4.php
	* @author Bitrix
	* @deprecated
	*/
	static function Add($arFields, $arOptions = array())
	{
		$fields = array_intersect_key($arFields, Bitrix\Sale\Delivery\Services\Table::getMap());

		if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
		{
			$arFields["LOGOTIP"]["MODULE_ID"] = "sale";
			CFile::SaveForDB($arFields, "LOGOTIP", "sale/delivery/logotip");
			$fields["LOGOTIP"] = $arFields["LOGOTIP"];
		}

		$fields["CODE"] = isset($arFields["CODE"]) ? $arFields["CODE"] : strval(mktime());
		$fields["PARENT_ID"] = 0;
		$fields["CLASS_NAME"] = '\Bitrix\Sale\Delivery\Services\Configurable';
		$fields["CONFIG"] = array(
			"MAIN" => array(
				"PRICE" => $arFields["PRICE"],
				"PERIOD" => array(
					"FROM" => $arFields["PERIOD_FROM"],
					"TO" => $arFields["PERIOD_TO"],
					"TYPE" => $arFields["PERIOD_TYPE"],
				)
			)
		);

		$res = \Bitrix\Sale\Delivery\Services\Table::add($fields);

		if(!$res->isSuccess())
			return false;

		$newId = $res->getId();

		$res = \Bitrix\Sale\Delivery\Restrictions\Table::add(array(
			"DELIVERY_ID" => $newId,
			"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\BySite',
			"PARAMS" => array(
				"SITE_ID" => array($arFields["LID"]),
			)
		));

		if(intval($arFields["WEIGHT_FROM"]) > 0 || intval($arFields["WEIGHT_TO"]) > 0)
		{
			$res = \Bitrix\Sale\Delivery\Restrictions\Table::add(array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByWeight',
				"PARAMS" => array(
					"MIN_WEIGHT" => $arFields["WEIGHT_FROM"],
					"MAX_WEIGHT" => $arFields["WEIGHT_TO"]
				)
			));
		}

		if(intval($arFields["ORDER_PRICE_FROM"]) > 0 || intval($arFields["ORDER_PRICE_TO"]) > 0)
		{
			$res = \Bitrix\Sale\Delivery\Restrictions\Table::add(array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPrice',
				"PARAMS" => array(
					"MIN_PRICE" => $arFields["ORDER_PRICE_FROM"],
					"MAX_PRICE" => $arFields["ORDER_PRICE_TO"],
					"CURRENCY" => $arFields["ORDER_CURRENCY"]
				)
			));
		}

		if(isset($arFields["LOCATIONS"]) && is_array($arFields["LOCATIONS"]))
			Helper::resetLocationsForEntity($newId, $arFields['LOCATIONS'], self::CONN_ENTITY_NAME, !!$arOptions['EXPECT_LOCATION_CODES']);

		if (isset($arFields["PAY_SYSTEM"]))
			CSaleDelivery::UpdateDeliveryPay($newId, $arFields["PAY_SYSTEM"]);

		if(isset($arFields["STORE"]))
		{
			$stores = unserialize($arFields["STORE"]);

			if($stores)
				\Bitrix\Sale\Delivery\ExtraServices\Manager::saveStores($newId, $stores);
		}

		return $newId;
	}

	/**
	 * @param bool|false $renameTable
	 * @return \Bitrix\Sale\Result
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function convertToNew($renameTable = false)
	{
		$result = new \Bitrix\Sale\Result();
		$con = \Bitrix\Main\Application::getConnection();

		if(!$con->isTableExists("b_sale_delivery"))
			return $result;

		if(!$con->isTableExists("b_sale_delivery2location_tmp"))
		{
			$fields = $con->getTableFields('b_sale_delivery2location');
			$con->createTable('b_sale_delivery2location_tmp', $fields, array('DELIVERY_ID', 'LOCATION_CODE', 'LOCATION_TYPE'));
			$con->queryExecute('
				INSERT INTO
					b_sale_delivery2location_tmp(DELIVERY_ID, LOCATION_CODE, LOCATION_TYPE)
				SELECT
					DELIVERY_ID, LOCATION_CODE, LOCATION_TYPE FROM b_sale_delivery2location
			');

			$con->queryExecute('DELETE FROM b_sale_delivery2location');
		}

		$sqlHelper = $con->getSqlHelper();
		$deliveryRes = $con->query('SELECT * FROM b_sale_delivery WHERE CONVERTED != \'Y\'');

		while($delivery = $deliveryRes->fetch())
		{
			$delivery["CODE"] = $delivery["ID"];
			unset($delivery["ID"]);

			$newId = \CSaleDelivery::Add($delivery);

			if(intval($newId) <= 0)
			{
				$result->addError( new \Bitrix\Main\Entity\EntityError("Can't convert old delivery id: ".$delivery["CODE"]));
				continue;
			}

			$res = \Bitrix\Sale\Delivery\Restrictions\Table::add(array(
				"DELIVERY_ID" => $newId,
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByLocation',
				"SORT" => 100
			));

			$result->addErrors($res->getErrors());

			if($result->isSuccess())
			{
				$con->queryExecute('UPDATE b_sale_delivery SET CONVERTED=\'Y\' WHERE ID='.$sqlHelper->forSql($delivery["CODE"]));
				$con->queryExecute("UPDATE b_sale_order SET DELIVERY_ID=".$sqlHelper->forSql($newId)." WHERE DELIVERY_ID = ".$sqlHelper->forSql($delivery["CODE"]));
				$con->queryExecute("UPDATE b_sale_order_history SET DELIVERY_ID=".$sqlHelper->forSql($newId)." WHERE DELIVERY_ID = ".$sqlHelper->forSql($delivery["CODE"]));
				$con->queryExecute("UPDATE b_sale_delivery2paysystem SET DELIVERY_ID=".$sqlHelper->forSql($newId).", LINK_DIRECTION='".DeliveryPaySystemTable::LINK_DIRECTION_DELIVERY_PAYSYSTEM."' WHERE DELIVERY_ID = ".$sqlHelper->forSql($delivery["CODE"]));

				$con->queryExecute('
					INSERT INTO
						b_sale_delivery2location(DELIVERY_ID, LOCATION_CODE, LOCATION_TYPE)
					SELECT
						'.$sqlHelper->forSql($newId).', LOCATION_CODE, LOCATION_TYPE FROM b_sale_delivery2location_tmp
					WHERE
						DELIVERY_ID = '.$sqlHelper->forSql($delivery["CODE"]).'
				');

				$con->queryExecute('DELETE FROM b_sale_delivery2location_tmp WHERE DELIVERY_ID = '.$sqlHelper->forSql($delivery["CODE"]));

				$d2pRes = \Bitrix\Sale\Internals\DeliveryPaySystemTable::getList(array(
					'filter' => array(
						'DELIVERY_ID' => $newId
					),
					'select' => array("DELIVERY_ID"),
					'group' => array("DELIVERY_ID")
				));

				if($d2p = $d2pRes->fetch())
				{
					$res = \Bitrix\Sale\Delivery\Restrictions\Table::add(array(
						"DELIVERY_ID" => $d2p["DELIVERY_ID"],
						"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByPaySystem',
						"SORT" => 100
					));

					if(!$res->isSuccess())
						$result->addErrors($res->getErrors());
				}
			}
		}

		if($result->isSuccess())
		{
			$con->dropTable('b_sale_delivery2location_tmp');

			if($renameTable)
				$con->renameTable("b_sale_delivery", "b_sale_delivery_old");
		}

		return $result;
	}

	/**
	 * @param bool|false $renameTable
	 * @return string
	 * @internal
	 */
	public static function convertToNewAgent($renameTable = false)
	{
		self::convertToNew($renameTable);
		return "";
	}

	/**
	 * @return string
	 * @internal
	 */
	public static function convertPSRelationsAgent()
	{
		self::convertPSRelations();
		return "";
	}

	/**
	 * @return \Bitrix\Sale\Result
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentException
	 * @internal
	 */
	public static function convertPSRelations()
	{
		$result = new \Bitrix\Sale\Result();
		$con = \Bitrix\Main\Application::getConnection();

		if(!$con->isTableExists("b_sale_delivery2paysystem"))
			return $result;

		$query = new \Bitrix\Main\Entity\Query(DeliveryPaySystemTable::getEntity());
		$query->setSelect(array('DELIVERY_ID'));
		$query->addFilter('LINK_DIRECTION', NULL);
		$query->setLimit(1);
		$res = $query->exec();

		if (!$res->fetch())
			return $result;

		$con->queryExecute('UPDATE b_sale_delivery2paysystem SET LINK_DIRECTION=\''.DeliveryPaySystemTable::LINK_DIRECTION_DELIVERY_PAYSYSTEM.'\'');
		$res = DeliveryPaySystemTable::getList(array());

		while($rec = $res->fetch())
		{
			unset($rec["ID"]);
			$rec["LINK_DIRECTION"] = DeliveryPaySystemTable::LINK_DIRECTION_PAYSYSTEM_DELIVERY;
			DeliveryPaySystemTable::Add($rec);
		}

		return $result;
	}



	/**
	 * @param array $oldOrder
	 * @return Shipment
	 * @internal
	 */
	public static function convertOrderOldToNew(array $oldOrder)
	{
		$siteId = isset($oldOrder["SITE_ID"]) ? $oldOrder["SITE_ID"] : SITE_ID;
		$newOrder = \Bitrix\Sale\Order::create($siteId, null, $oldOrder["CURRENCY"]);
		$dbPersonType = \CSalePersonType::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array("ACTIVE" => "Y", "LID"=> $siteId));

		if($arPersonType = $dbPersonType->GetNext())
			$personTypeId = $arPersonType["ID"];
		else
			$personTypeId = 1;

		$newOrder->setPersonTypeId($personTypeId);
		$newOrder->setFieldNoDemand("PRICE", $oldOrder["PRICE"]);
		$basket = \Bitrix\Sale\Basket::create($siteId);
		$settableFields = array_flip(\Bitrix\Sale\BasketItemBase::getSettableFields());

		if (!empty($oldOrder["ITEMS"]) && is_array($oldOrder["ITEMS"]))
		{
			foreach($oldOrder["ITEMS"] as $oldBasketItem)
			{
				$newBasketItem = $basket->createItem($oldBasketItem["MODULE"], $oldBasketItem["PRODUCT_ID"]);
				$oldBasketItem = array_intersect_key($oldBasketItem, $settableFields);
				$newBasketItem->setFieldsNoDemand($oldBasketItem);
			}
		}

		$props = $newOrder->getPropertyCollection();

		if (!empty($oldOrder['PROPERTIES']) && is_array($oldOrder['PROPERTIES']))
		{
			$r = $props->setValuesFromPost($oldOrder, $_FILES);
		}

		$newOrder->setBasket($basket);


		if($loc = $props->getDeliveryLocation())
			$loc->setValue($oldOrder["LOCATION_TO"]);

		if($loc = $props->getDeliveryLocationZip())
			$loc->setValue($oldOrder["LOCATION_ZIP"]);

		/** @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
		$shipmentCollection = $newOrder->getShipmentCollection();
		$shipment = $shipmentCollection->createItem();
		$shipment->setField("CURRENCY", $oldOrder["CURRENCY"]);
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		foreach($newOrder->getBasket() as $item)
		{
			$shipmentItem = $shipmentItemCollection->createItem($item);
			$shipmentItem->setQuantity($item->getQuantity());

			if(strlen($shipmentItem->getField("DIMENSIONS")))
			{
				$shipmentItem->setField("DIMENSIONS", unserialize($shipmentItem->getField("DIMENSIONS")));
			}
		}

		if (isset($arOrder["DELIVERY_EXTRA_SERVICES"]))
			$shipment->setExtraServices($arOrder["DELIVERY_EXTRA_SERVICES"]);

		return $shipment;
	}

	/**
	 * @return string
	 * @throws Exception
	 * @internal
	 */
	public static function createNoDeliveryServiceAgent()
	{
		$id = \Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();

		if ($id <= 0)
		{
			Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/sale/lib/delivery/helper.php', 'ru');

			$fields = array();
			$fields["NAME"] = \Bitrix\Main\Localization\Loc::getMessage('SALE_DELIVERY_HELPER_NO_DELIVERY_SERVICE');
			$fields["CLASS_NAME"] = '\Bitrix\Sale\Delivery\Services\EmptyDeliveryService';
			$fields["CURRENCY"] = 'RUB';
			$fields["ACTIVE"] = "Y";
			$fields["CONFIG"] = array(
				'MAIN' => array(
					'CURRENCY' => 'RUB',
					'PRICE' => 0,
					'PERIOD' => array(
						'FROM' => 0,
						'TO' => 0,
						'TYPE' => 'D'
					)
				)
			);
			$res = \Bitrix\Sale\Delivery\Services\Table::add($fields);
			$id = $res->getId();
			$fields = array(
				'SORT' => 100,
				'DELIVERY_ID' => $id,
				'PARAMS' => array(
					'PUBLIC_SHOW' => 'N'
				)
			);
			$rstrPM = new \Bitrix\Sale\Delivery\Restrictions\ByPublicMode();
			$rstrPM->save($fields);
		}

		return "";
	}
}
?>