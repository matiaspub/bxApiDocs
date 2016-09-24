<?
use \Bitrix\Sale\Internals\PaySystemActionTable;
use \Bitrix\Sale\Internals\ServiceRestrictionTable;
use \Bitrix\Sale\Services\PaySystem\Restrictions\Manager;

IncludeModuleLangFile(__FILE__);

/** @deprecated */

/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/index.php
 * @author Bitrix
 * @deprecated
 */
class CAllSalePaySystemAction
{
	const GET_PARAM_VALUE = 1;
	static $relatedData = array();

	
	/**
	* <p>Метод возвращает параметры обработчика платежной системы с кодом ID. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код обработчика платежной системы.
	*
	* @return array <p>Возвращается ассоциативный массив параметров обработчика
	* платежной системы с ключами:</p><table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th>     <th>Описание</th>   </tr> <tr> <td>ID</td>     <td>Код обработчика
	* платежной системы.</td> </tr> <tr> <td>PAY_SYSTEM_ID</td>     <td>Код платежной
	* системы.</td> </tr> <tr> <td>PERSON_TYPE_ID</td>     <td>Код типа плательщика.</td> </tr> <tr>
	* <td>NAME</td>     <td>Название платежной системы.</td> </tr> <tr> <td>ACTION_FILE</td>    
	* <td>Скрипт платежной системы.</td> </tr> <tr> <td>RESULT_FILE</td>     <td>Скрипт
	* получения результатов.</td> </tr> <tr> <td>NEW_WINDOW</td>     <td>Флаг (Y/N) открывать
	* ли скрипт платежной системы в новом окне.</td> </tr> </table><p> </p>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__getbyid.3a702e2f.php
	* @author Bitrix
	*/
	public static function GetByID($id)
	{
		$id = (int)$id;

		$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getById($id);
		if ($res = $dbRes->fetch())
			return $res;

		return false;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB, $USER;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPSA_NO_NAME"), "ERROR_NO_NAME");
			return false;
		}

		if (is_set($arFields, "NEW_WINDOW") && $arFields["NEW_WINDOW"] != "Y")
			$arFields["NEW_WINDOW"] = "N";
		if (is_set($arFields, "HAVE_PAYMENT") && $arFields["HAVE_PAYMENT"] != "Y")
			$arFields["HAVE_PAYMENT"] = "N";
		if (is_set($arFields, "HAVE_ACTION") && $arFields["HAVE_ACTION"] != "Y")
			$arFields["HAVE_ACTION"] = "N";
		if (is_set($arFields, "HAVE_RESULT") && $arFields["HAVE_RESULT"] != "Y")
			$arFields["HAVE_RESULT"] = "N";
		if (is_set($arFields, "HAVE_PREPAY") && $arFields["HAVE_PREPAY"] != "Y")
			$arFields["HAVE_PREPAY"] = "N";
		if (is_set($arFields, "HAVE_RESULT_RECEIVE") && $arFields["HAVE_RESULT_RECEIVE"] != "Y")
			$arFields["HAVE_RESULT_RECEIVE"] = "N";
		if (is_set($arFields, "ENCODING") && strlen($arFields["ENCODING"]) <= 0)
			$arFields["ENCODING"] = false;

		return True;
	}

	
	/**
	* <p>Метод удаляет обработчик платежной системы с кодом ID. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код обработчика платежной системы.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* CSalePaySystemAction::Delete(12);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__delete.fd7a43b9.php
	* @author Bitrix
	*/
	public static function Delete($id)
	{
		$id = (int)$id;

		$result = \Bitrix\Sale\Internals\PaySystemActionTable::delete($id);
		return $result->isSuccess();
	}

	static function SerializeParams($arParams)
	{
		return serialize($arParams);
	}

	static function UnSerializeParams($strParams)
	{
		$arParams = unserialize($strParams);

		if (!is_array($arParams))
			$arParams = array();

		return $arParams;
	}

	public static function GetParamValue($key, $defaultValue = null)
	{
		if (
			isset($_REQUEST["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_REQUEST)
			|| isset($_POST["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_POST)
			|| isset($_GET["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_GET)
			|| isset($_SESSION["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_SESSION)
			|| isset($_COOKIE["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_COOKIE)
			|| isset($_SERVER["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_SERVER)
			|| isset($_ENV["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_ENV)
			|| isset($_FILES["SALE_CORRESPONDENCE"]) || array_key_exists("SALE_CORRESPONDENCE", $_FILES)
			|| isset($_REQUEST["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_REQUEST)
			|| isset($_POST["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_POST)
			|| isset($_GET["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_GET)
			|| isset($_SESSION["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_SESSION)
			|| isset($_COOKIE["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_COOKIE)
			|| isset($_SERVER["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_SERVER)
			|| isset($_ENV["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_ENV)
			|| isset($_FILES["SALE_INPUT_PARAMS"]) || array_key_exists("SALE_INPUT_PARAMS", $_FILES)
			)
		{
			throw new \Bitrix\Main\SystemException('SALE_CORRESPONDENCE or SALE_INPUT_PARAMS were defined in superglobal variable!');
		}

		if($key === "BASKET_ITEMS" && isset($GLOBALS["SALE_INPUT_PARAMS"]["BASKET_ITEMS"]))
		{
			return $GLOBALS["SALE_INPUT_PARAMS"]["BASKET_ITEMS"];
		}
		elseif($key === "TAX_LIST" && isset($GLOBALS["SALE_INPUT_PARAMS"]["TAX_LIST"]))
		{
			return $GLOBALS["SALE_INPUT_PARAMS"]["TAX_LIST"];
		}

		if(!isset($GLOBALS["SALE_CORRESPONDENCE"]) || !is_array($GLOBALS["SALE_CORRESPONDENCE"]))
			return false;

		if(!isset($GLOBALS["SALE_INPUT_PARAMS"]) || !is_array($GLOBALS["SALE_INPUT_PARAMS"]))
			return false;

		if(!array_key_exists($key, $GLOBALS["SALE_CORRESPONDENCE"]))
		{
			if($defaultValue !== null)
				return $defaultValue;

			$message = GetMessage("SKGPSA_ERROR_NO_KEY", array(
				"#KEY#" => $key,
				"#ORDER_ID#" => $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"],
				"#PS_ID#" => $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAY_SYSTEM_ID"]
			))." (".__METHOD__.")";

			self::alarm( $key, $message	);
			throw new \Bitrix\Main\SystemException($message, self::GET_PARAM_VALUE);
		}

		$type = $GLOBALS["SALE_CORRESPONDENCE"][$key]["TYPE"];
		$value = $GLOBALS["SALE_CORRESPONDENCE"][$key]["VALUE"];

		if (strlen($type) > 0)
		{
			if (array_key_exists($type, $GLOBALS["SALE_INPUT_PARAMS"])
				&& is_array($GLOBALS["SALE_INPUT_PARAMS"][$type])
				&& array_key_exists($value, $GLOBALS["SALE_INPUT_PARAMS"][$type]))
			{
				$res = $GLOBALS["SALE_INPUT_PARAMS"][$type][$value];
			}
			elseif ($type == "SELECT" || $type == "RADIO" || $type == "FILE" || $type == "Y/N" || $type == "ENUM")
			{
				$res = $GLOBALS["SALE_CORRESPONDENCE"][$key]["VALUE"];
			}
			else
			{
				$res = False;
			}
		}
		else
		{
			$res = $value;
		}

		return $res;
	}

	static function alarm($itemId, $description)
	{
		self::writeToEventLog($itemId, $description);
		self::showAlarmMessage();
	}

	static function writeToEventLog($itemId, $description)
	{
		return CEventLog::Add(array(
			"SEVERITY" => "ERROR",
			"AUDIT_TYPE_ID" => "PAY_SYSTEM_ACTION_ALARM",
			"MODULE_ID" => "sale",
			"ITEM_ID" => $itemId,
			"DESCRIPTION" => $description
		));
	}

	static public function OnEventLogGetAuditTypes()
	{
		return array(
			"PAY_SYSTEM_ACTION_ALARM" => "[PAY_SYSTEM_ACTION_ALARM] ".GetMessage("SKGPSA_ALARM_EVENT_LOG")
		);
	}

	static function showAlarmMessage()
	{
		$tag = "PAY_SYSTEM_ACTION_ALARM";
		$dbRes = CAdminNotify::GetList(array(), array("TAG" => $tag));

		if($res = $dbRes->Fetch())
			return false;

		return CAdminNotify::Add(array(
				"MESSAGE" => GetMessage("SKGPSA_ALARM_MESSAGE", array("#LANGUAGE_ID#" => LANGUAGE_ID)),
				"TAG" => $tag,
				"MODULE_ID" => "SALE",
				"ENABLE_CLOSE" => "Y",
				"NOTIFY_TYPE" => CAdminNotify::TYPE_ERROR
			)
		);
	}

	public static function InitParamArrays($arOrder, $orderID = 0, $psParams = "", $relatedData = array(), $payment = array())
	{
		if(!is_array($relatedData))
			$relatedData = array();

		$GLOBALS["SALE_INPUT_PARAMS"] = array();
		$GLOBALS["SALE_CORRESPONDENCE"] = array();

		if (!is_array($arOrder) || count($arOrder) <= 0 || !array_key_exists("ID", $arOrder))
		{
			$arOrder = array();

			$orderID = IntVal($orderID);
			if ($orderID > 0)
				$arOrderTmp = CSaleOrder::GetByID($orderID);
			if (!empty($arOrderTmp))
			{
				foreach($arOrderTmp as $k => $v)
				{
					$arOrder["~".$k] = $v;
					$arOrder[$k] = htmlspecialcharsbx($v);
				}
			}
		}
		else if ($orderID == 0 && $arOrder['ID'] > 0)
		{
			$orderID = $arOrder['ID'];
		}

		if (empty($payment) && $orderID > 0)
		{
			$payment = \Bitrix\Sale\Internals\PaymentTable::getRow(
				array(
					'select' => array('*'),
					'filter' => array('ORDER_ID' => $orderID, '!PAY_SYSTEM_ID' => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId())
				)
			);
		}

		if (count($arOrder) > 0)
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"] = $arOrder;

		if (!empty($payment))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYMENT_ID"] = $payment['ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~PAYMENT_ID"] = $payment['ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"] = $payment['SUM'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~SHOULD_PAY"] = $payment['SUM'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYED"] = $payment['PAID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~PAYED"] = $payment['PAID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAY_SYSTEM_ID"] = $payment['PAY_SYSTEM_ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~PAY_SYSTEM_ID"] = $payment['PAY_SYSTEM_ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ORDER_PAYMENT_ID"] = $payment['ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~ORDER_PAYMENT_ID"] = $payment['ID'];

			$GLOBALS["SALE_INPUT_PARAMS"]["PAYMENT"] = $payment;
		}
		else
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"] = DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"]) - DoubleVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SUM_PAID"]);
		}

		$arDateInsert = explode(" ", $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"]);
		if (is_array($arDateInsert) && count($arDateInsert) > 0)
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT_DATE"] = $arDateInsert[0];
		else
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT_DATE"] = $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_INSERT"];

		if (!empty($payment))
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DATE_BILL_DATE"] = ConvertTimeStamp(MakeTimeStamp($payment["DATE_BILL"]), 'SHORT');

		$userID = IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["USER_ID"]);
		if ($userID > 0)
		{
			$dbUser = CUser::GetByID($userID);
			if ($arUser = $dbUser->GetNext())
				$GLOBALS["SALE_INPUT_PARAMS"]["USER"] = $arUser;
		}

		$arCurOrderProps = array();
		if (isset($relatedData["PROPERTIES"]) && is_array($relatedData["PROPERTIES"]))
		{
			$properties = $relatedData["PROPERTIES"];
			foreach ($properties as $key => $value)
			{
				$arCurOrderProps["~".$key] = $value;
				$arCurOrderProps[$key] = htmlspecialcharsEx($value);
			}
		}
		else
		{
			$dbOrderPropVals = CSaleOrderPropsValue::GetList(
				array(),
				array("ORDER_ID" => $GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ID"]),
				false,
				false,
				array("ID", "CODE", "VALUE", "ORDER_PROPS_ID", "PROP_TYPE")
			);

			while ($arOrderPropVals = $dbOrderPropVals->Fetch())
			{
				$arCurOrderPropsTmp = CSaleOrderProps::GetRealValue(
					$arOrderPropVals["ORDER_PROPS_ID"],
					$arOrderPropVals["CODE"],
					$arOrderPropVals["PROP_TYPE"],
					$arOrderPropVals["VALUE"],
					LANGUAGE_ID
				);

				foreach ($arCurOrderPropsTmp as $key => $value)
				{
					$arCurOrderProps["~".$key] = $value;
					$arCurOrderProps[$key] = htmlspecialcharsEx($value);
				}
			}
		}

		if (count($arCurOrderProps) > 0)
			$GLOBALS["SALE_INPUT_PARAMS"]["PROPERTY"] = $arCurOrderProps;

		$shipment = \Bitrix\Sale\Internals\ShipmentTable::getRow(
			array(
				'select' => array('DELIVERY_ID'),
				'filter' => array('ORDER_ID' => $orderID, 'SYSTEM' => 'N')
			)
		);

		if ($shipment)
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["DELIVERY_ID"] = $shipment['DELIVERY_ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~DELIVERY_ID"] = $shipment['DELIVERY_ID'];
		}

		$paySystemId = '';
		if ($payment && $payment['PAY_SYSTEM_ID'] > 0)
		{
			$paySystemId = $payment['PAY_SYSTEM_ID'];
		}
		elseif (isset($arOrder['PAY_SYSTEM_ID']) && $arOrder['PAY_SYSTEM_ID'] > 0)
		{
			$paySystemId = $arOrder['PAY_SYSTEM_ID'];
		}
		else
		{
			$psParams = unserialize($psParams);
			if (isset($psParams['BX_PAY_SYSTEM_ID']))
				$paySystemId = $psParams['BX_PAY_SYSTEM_ID']['VALUE'];
		}

		if ($paySystemId !== '')
		{
			if (!isset($arOrder['PERSON_TYPE_ID']) || $arOrder['PERSON_TYPE_ID'] <= 0)
			{
				// for crm quote compatibility
				$personTypes = CSalePaySystem::getPaySystemPersonTypeIds($paySystemId);
				$personTypeId = array_shift($personTypes);
			}
			else
			{
				$personTypeId = $arOrder['PERSON_TYPE_ID'];
			}

			$params = CSalePaySystemAction::getParamsByConsumer('PAYSYSTEM_'.$paySystemId, $personTypeId);
			foreach ($params as $key => $value)
				$params[$key]['~VALUE'] = htmlspecialcharsbx($value['VALUE']);

			$GLOBALS["SALE_CORRESPONDENCE"] = $params;
		}

		if ($payment['COMPANY_ID'] > 0)
		{
			if (!array_key_exists('COMPANY', $GLOBALS["SALE_INPUT_PARAMS"]))
				$GLOBALS["SALE_INPUT_PARAMS"]["COMPANY"] = array();

			global $USER_FIELD_MANAGER;
			$userFieldsList = $USER_FIELD_MANAGER->GetUserFields(\Bitrix\Sale\Internals\CompanyTable::getUfId(), null, LANGUAGE_ID);
			foreach ($userFieldsList as $key => $userField)
			{
				$value = $USER_FIELD_MANAGER->GetUserFieldValue(\Bitrix\Sale\Internals\CompanyTable::getUfId(), $key, $payment['COMPANY_ID']);
				$GLOBALS["SALE_INPUT_PARAMS"]["COMPANY"][$key] = $value;
				$GLOBALS["SALE_INPUT_PARAMS"]["COMPANY"]["~".$key] = $value;
			}

			$companyFieldList = \Bitrix\Sale\Internals\CompanyTable::getRowById($payment['COMPANY_ID']);
			foreach ($companyFieldList as $key => $value)
			{
				$GLOBALS["SALE_INPUT_PARAMS"]["COMPANY"][$key] = $value;
				$GLOBALS["SALE_INPUT_PARAMS"]["COMPANY"]["~".$key] = $value;
			}
		}
		// fields with no interface

		$GLOBALS["SALE_CORRESPONDENCE"]['PAYER_STREET']["TYPE"] = 'PROPERTY';
		$GLOBALS["SALE_CORRESPONDENCE"]['PAYER_STREET']["VALUE"] = 'LOCATION_STREET';
		$GLOBALS["SALE_CORRESPONDENCE"]['PAYER_STREET']["~VALUE"] = 'LOCATION_STREET';

		$GLOBALS["SALE_CORRESPONDENCE"]['PAYER_VILLAGE']["TYPE"] = 'PROPERTY';
		$GLOBALS["SALE_CORRESPONDENCE"]['PAYER_VILLAGE']["VALUE"] = 'LOCATION_VILLAGE';
		$GLOBALS["SALE_CORRESPONDENCE"]['PAYER_VILLAGE']["~VALUE"] = 'LOCATION_VILLAGE';

		$GLOBALS["SALE_CORRESPONDENCE"]['ORDER_PAYMENT_ID']["TYPE"] = 'ORDER';
		$GLOBALS["SALE_CORRESPONDENCE"]['ORDER_PAYMENT_ID']["VALUE"] = 'PAYMENT_ID';
		$GLOBALS["SALE_CORRESPONDENCE"]['ORDER_PAYMENT_ID']["~VALUE"] = 'PAYMENT_ID';

		$GLOBALS["SALE_CORRESPONDENCE"]['PAYED']["TYPE"] = 'ORDER';
		$GLOBALS["SALE_CORRESPONDENCE"]['PAYED']["VALUE"] = 'PAYED';
		$GLOBALS["SALE_CORRESPONDENCE"]['PAYED']["~VALUE"] = 'PAYED';

		if (isset($relatedData["BASKET_ITEMS"]) && is_array($relatedData["BASKET_ITEMS"]))
			$GLOBALS["SALE_INPUT_PARAMS"]["BASKET_ITEMS"] = $relatedData["BASKET_ITEMS"];

		if (isset($relatedData["TAX_LIST"]) && is_array($relatedData["TAX_LIST"]))
			$GLOBALS["SALE_INPUT_PARAMS"]["TAX_LIST"] = $relatedData["TAX_LIST"];

		if(isset($relatedData["REQUISITE"]) && is_array($relatedData["REQUISITE"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["REQUISITE"] = $relatedData["REQUISITE"];

			self::$relatedData['REQUISITE'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['REQUISITE'][$providerValue];
				}
			);
		}
		if(isset($relatedData["BANK_DETAIL"]) && is_array($relatedData["BANK_DETAIL"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["BANK_DETAIL"] = $relatedData["BANK_DETAIL"];

			self::$relatedData['BANK_DETAIL'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['BANK_DETAIL'][$providerValue];
				}
			);
		}
		if(isset($relatedData["CRM_COMPANY"]) && is_array($relatedData["CRM_COMPANY"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["CRM_COMPANY"] = $relatedData["CRM_COMPANY"];

			self::$relatedData['CRM_COMPANY'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['CRM_COMPANY'][$providerValue];
				}
			);
		}
		if(isset($relatedData["CRM_CONTACT"]) && is_array($relatedData["CRM_CONTACT"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["CRM_CONTACT"] = $relatedData["CRM_CONTACT"];

			self::$relatedData['CRM_CONTACT'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['CRM_CONTACT'][$providerValue];
				}
			);
		}
		if(isset($relatedData["MC_REQUISITE"]) && is_array($relatedData["MC_REQUISITE"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["MC_REQUISITE"] = $relatedData["MC_REQUISITE"];

			self::$relatedData['MC_REQUISITE'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['MC_REQUISITE'][$providerValue];
				}
			);
		}
		if(isset($relatedData["MC_BANK_DETAIL"]) && is_array($relatedData["MC_BANK_DETAIL"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["MC_BANK_DETAIL"] = $relatedData["MC_BANK_DETAIL"];

			self::$relatedData['MC_BANK_DETAIL'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['MC_BANK_DETAIL'][$providerValue];
				}
			);
		}
		if(isset($relatedData["CRM_MYCOMPANY"]) && is_array($relatedData["CRM_MYCOMPANY"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["CRM_MYCOMPANY"] = $relatedData["CRM_MYCOMPANY"];

			self::$relatedData['CRM_MYCOMPANY'] = array(
				'GET_INSTANCE_VALUE' => function ($providerInstance, $providerValue, $personTypeId)
				{
					return $GLOBALS['SALE_INPUT_PARAMS']['CRM_MYCOMPANY'][$providerValue];
				}
			);
		}

		if ($relatedData)
		{
			$eventManager = \Bitrix\Main\EventManager::getInstance();
			$eventManager->addEventHandler('sale', 'OnGetBusinessValueProviders', array('\CSalePaySystemAction', 'getProviders'));
		}
	}

	public static function getProviders()
	{
		return self::$relatedData;
	}

	public static function IncludePrePaySystem($fileName, $bDoPayAction, &$arPaySysResult, &$strPaySysError, &$strPaySysWarning, $BASE_LANG_CURRENCY = False, $ORDER_PRICE = 0.0, $TAX_PRICE = 0.0, $DISCOUNT_PRICE = 0.0, $DELIVERY_PRICE = 0.0)
	{
		$strPaySysError = "";
		$strPaySysWarning = "";

		$arPaySysResult = array(
				"PS_STATUS" => false,
				"PS_STATUS_CODE" => false,
				"PS_STATUS_DESCRIPTION" => false,
				"PS_STATUS_MESSAGE" => false,
				"PS_SUM" => false,
				"PS_CURRENCY" => false,
				"PS_RESPONSE_DATE" => false,
				"USER_CARD_TYPE" => false,
				"USER_CARD_NUM" => false,
				"USER_CARD_EXP_MONTH" => false,
				"USER_CARD_EXP_YEAR" => false,
				"USER_CARD_CODE" => false
			);

		if ($BASE_LANG_CURRENCY === false)
			$BASE_LANG_CURRENCY = CSaleLang::GetLangCurrency(SITE_ID);

		include($fileName);
	}

	
	/**
	* <p>Метод возвращает результат выборки записей из обработчиков платежных систем в соответствии со своими параметрами. Нестатический метод.</p>
	*
	*
	* @param array $arOrder = array() Массив, в соответствии с которым сортируются результирующие
	* записи. Массив имеет вид: 		<pre class="syntax">array( "название_поля1" =&gt;
	* "направление_сортировки1", "название_поля2" =&gt;
	* "направление_сортировки2", . . . )</pre> 		В качестве "название_поля<i>N</i>"
	* может стоять любое поле 		обработчиков платежных систем, а в
	* качестве "направление_сортировки<i>X</i>" могут быть значения "<i>ASC</i>"
	* (по возрастанию) и "<i>DESC</i>" (по убыванию).<br><br> 		Если массив
	* сортировки имеет несколько элементов, то 		результирующий набор
	* сортируется последовательно по каждому элементу (т.е. сначала
	* сортируется по первому элементу, потом результат сортируется по
	* второму и т.д.). <br><br>  Значение по умолчанию - пустой массив array() -
	* означает, что результат отсортирован не будет.
	*
	* @param array $arFilter = array() Массив, в соответствии с которым фильтруются 		записи
	* обработчиков платежных систем. Массив имеет вид: 		<pre class="syntax">array(
	* "[модификатор1][оператор1]название_поля1" =&gt; "значение1",
	* "[модификатор2][оператор2]название_поля2" =&gt; "значение2", . . . )</pre>
	* Удовлетворяющие фильтру записи возвращаются в результате, а
	* записи, которые не удовлетворяют условиям фильтра,
	* отбрасываются.<br><br> 	Допустимыми являются следующие
	* модификаторы: 		<ul> <li> <b> 	!</b>  - отрицание;</li> 			<li> <b> 	+</b>  - значения
	* null, 0 и пустая строка так же удовлетворяют условиям фильтра.</li>
	* 		</ul> 	Допустимыми являются следующие операторы: 	<ul> <li> <b>&gt;=</b> -
	* значение поля больше или равно передаваемой в фильтр величины;</li>
	* 			<li> <b>&gt;</b>  - значение поля строго больше передаваемой в фильтр
	* величины;</li> 			<li> <b>&lt;=</b> - значение поля меньше или равно
	* передаваемой в фильтр величины;</li> 			<li> <b>&lt;</b> - значение поля
	* строго меньше передаваемой в фильтр величины;</li> 			<li> <b>@</b>  -
	* значение поля находится в передаваемом в фильтр разделенном
	* запятой списке значений;</li> 			<li> <b>~</b>  - значение поля проверяется
	* на соответствие передаваемому в фильтр шаблону;</li> 			<li> <b>%</b>  -
	* значение поля проверяется на соответствие передаваемой в фильтр
	* строке в соответствии с языком запросов.</li> 	</ul> В качестве
	* "название_поляX" может стоять любое поле 		заказов.<br><br> 		Пример
	* фильтра: 		<pre class="syntax">array("!PERSON_TYPE_ID" =&gt; 5)</pre> 		Этот фильтр означает
	* "выбрать все записи, в которых значение в поле PERSON_TYPE_ID (код типа
	* плательщика) не равно 5".<br><br> 	Значение по умолчанию - пустой
	* массив array() - означает, что результат отфильтрован не будет.
	*
	* @param array $arGroupBy = false Массив полей, по которым группируются записи 		обработчиков
	* платежных систем. Массив имеет вид: 		<pre
	* class="syntax">array("название_поля1",       "группирующая_функция2" =&gt;
	* "название_поля2", ...)</pre> 	В качестве "название_поля<i>N</i>" может
	* стоять любое поле 		обработчиков платежных систем. В качестве
	* группирующей функции могут стоять: 		<ul> <li> 	<b> 	COUNT</b> - подсчет
	* количества;</li> 			<li> <b>AVG</b> - вычисление среднего значения;</li> 			<li>
	* <b>MIN</b> - вычисление минимального значения;</li> 			<li> 	<b> 	MAX</b> -
	* вычисление максимального значения;</li> 			<li> <b>SUM</b> - вычисление
	* суммы.</li> 		</ul> 		Этот фильтр означает "выбрать все записи, в которых
	* значение в поле LID (сайт системы) не равно en".<br><br> 		Значение по
	* умолчанию - <i>false</i> - означает, что результат группироваться не
	* будет.
	*
	* @param array $arNavStartParams = false Массив параметров выборки. Может содержать следующие ключи: 		<ul>
	* <li>"<b>nTopCount</b>" - количество возвращаемых методом записей будет
	* ограничено сверху значением этого ключа;</li> 			<li> 	любой ключ,
	* принимаемый методом <b> CDBResult::NavQuery</b> 				в качестве третьего
	* параметра.</li> 		</ul> Значение по умолчанию - <i>false</i> - означает, что
	* параметров выборки нет.
	*
	* @param array $arSelectFields = array() Массив полей записей, которые будут возвращены методом. Можно
	* указать только те поля, которые необходимы. Если в массиве
	* присутствует значение 		"*", то будут возвращены все доступные
	* поля.<br><br> 		Значение по умолчанию - пустой массив 		array() - означает,
	* что будут возвращены все поля основной таблицы запроса.
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, содержащий ассоциативные
	* массивы параметров обработчиков платежных систем с
	* ключами:</p><table class="tnormal" width="100%"> <tr> <th width="15%">Ключ</th>     <th>Описание</th>
	*   </tr> <tr> <td>ID</td>     <td>Код обработчика платежной системы.</td>   </tr> <tr>
	* <td>PAY_SYSTEM_ID</td>     <td>Код платежной системы.</td>   </tr> <tr> <td>PERSON_TYPE_ID</td>    
	* <td>Код типа плательщика.</td>   </tr> <tr> <td>NAME</td>     <td>Название платежной
	* системы.</td>   </tr> <tr> <td>ACTION_FILE</td>     <td>Скрипт платежной системы.</td>  
	* </tr> <tr> <td>RESULT_FILE</td>     <td>Скрипт получения результатов.</td>   </tr> <tr>
	* <td>NEW_WINDOW</td>     <td>Флаг (Y/N) открывать ли скрипт платежной системы в
	* новом окне.</td> 	</tr> <tr> <td>PARAMS</td>     <td>Параметры вызова
	* обработчика.</td>   </tr> <tr> <td>HAVE_PAYMENT</td>     <td>Есть вариант обработчика
	* для работы после оформления заказа.</td>   </tr> <tr> <td>HAVE_ACTION</td>    
	* <td>Есть вариант обработчика для мгновенного списания денег.</td>  
	* </tr> <tr> <td>HAVE_RESULT</td>     <td>Есть скрипт запроса результатов.</td>   </tr> <tr>
	* <td>HAVE_PREPAY</td>     <td>Есть вариант обработчика для работы во время
	* оформления заказа.</td>   </tr> <tr> <td>PS_LID</td>     <td>Сайт платежной
	* системы.</td>   </tr> <tr> <td>PS_CURRENCY</td>     <td>Валюта платежной системы.</td>  
	* </tr> <tr> <td>PS_NAME</td>     <td>Название платежной системы.</td>   </tr> <tr>
	* <td>PS_ACTIVE</td>     <td>Активность платежной системы.</td>   </tr> <tr> <td>PS_SORT</td>   
	*  <td>Индекс сортировки платежной системы.</td>   </tr> <tr> <td>PS_DESCRIPTION</td>    
	* <td>Описание платежной системы.</td>   </tr> <tr> <td>PT_LID</td>     <td>Сайт типа
	* плательщика.</td>   </tr> <tr> <td>PT_NAME</td>     <td>Название типа
	* плательщика.</td>   </tr> <tr> <td>PT_SORT</td>     <td>Индекс сортировки типа
	* плательщика.</td>   </tr> </table><p>Если в качестве параметра arGroupBy
	* передается пустой массив, то метод вернет число записей,
	* удовлетворяющих фильтру.</p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__getlist.324e3583.php
	* @author Bitrix
	*/
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		if (\Bitrix\Main\Config\Option::get('main', '~sale_paysystem_converted') == 'Y')
		{
			$ignoredFields = array('PERSON_TYPE_ID');
			if (!$arSelectFields)
			{
				$select = array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID", "PSA_NAME", "ACTION_FILE", "RESULT_FILE", "NEW_WINDOW", "PARAMS", "TARIF", "ENCODING", "LOGOTIP");
			}
			else
			{
				$select = array();
				foreach ($arSelectFields as $i => $field)
					$select[] = self::getAlias($field);
			}
			if (!in_array('ID', $select))
				$select[] = 'ID';
			$filter = array();
			foreach ($arFilter as $i => $field)
			{
				if (in_array($i, $ignoredFields))
					continue;
				if ($i == 'PAY_SYSTEM_ID')
					$filter['ID'] = $field;
				else
					$filter[self::getAlias($i)] = $field;
			}
			$groupBy = array();
			if ($arGroupBy !== false)
			{
				$arGroupBy = !is_array($arGroupBy) ? array($arGroupBy) : $arGroupBy;
				foreach ($arGroupBy as $field => $order)
					$groupBy[self::getAlias($field)] = $order;
			}
			$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getList(array(
					'select' => $select,
					'filter' => $filter,
					'group' => $groupBy
			));
			$limit = null;
			if (is_array($arNavStartParams) && isset($arNavStartParams['nTopCount']))
			{
				if ($arNavStartParams['nTopCount'] > 0)
					$limit = $arNavStartParams['nTopCount'];
			}
			$result = array();
			$busValEnable = (in_array('PARAMS', $select));
			while ($data = $dbRes->fetch())
			{
				if ($limit !== null && !$limit)
					break;
				$dbRestriction = ServiceRestrictionTable::getList(array(
						'filter' => array(
								'SERVICE_ID' => $data['ID'],
								'SERVICE_TYPE' => Manager::SERVICE_TYPE_PAYMENT
						)
				));
				if (isset($data['ACTION_FILE']))
				{
					$oldHandler = array_search($data['ACTION_FILE'], self::getOldToNewHandlersMap());
					if ($oldHandler !== false)
						$data['ACTION_FILE'] = $oldHandler;
				}
				while ($restriction = $dbRestriction->fetch())
				{
					if (!self::checkRestriction($restriction, $filter))
						continue(2);
				}
				if (isset($data['PAY_SYSTEM_ID']))
					$data['PAY_SYSTEM_ID'] = $data['ID'];
				if ($busValEnable)
				{
					if ($data['ID'] > 0)
					{
						$consumerId = $data['ID'];
					}
					else
					{
						$params = unserialize($data['PARAMS']);
						$consumerId = $params['BX_PAY_SYSTEM_ID']['VALUE'];
					}
					$consumer = 'PAYSYSTEM_'.$consumerId;
					if (!$data['PERSON_TYPE_ID'])
					{
						if (is_array($arFilter['PERSON_TYPE_ID']))
							$personTypeId = $arFilter['PERSON_TYPE_ID'][0];
						else
							$personTypeId = $arFilter['PERSON_TYPE_ID'];
					}
					else
					{
						$personTypeId = $data['PERSON_TYPE_ID'];
					}
					if (!in_array('ID', $arSelectFields))
					{
						$key = array_search('ID', $data);
						unset($data[$key]);
					}
					$params = static::getParamsByConsumer($consumer, $personTypeId);
					$params['BX_PAY_SYSTEM_ID'] = array(
							'TYPE' => '',
							'VALUE' => $consumerId
					);
					$data['PARAMS'] = serialize($params);
				}
				if (in_array('PS_NAME', $arSelectFields))
				{
					$data['PS_NAME'] = $data['NAME'];
					unset($data['NAME']);
				}
				if (array_key_exists('PSA_NAME', $data))
				{
					$data['NAME'] = $data['PSA_NAME'];
					unset($data['PSA_NAME']);
				}
				$result[] = $data;
				$limit--;
			}
			$dbRes = new CDBResult();
			$dbRes->InitFromArray($result);
		}
		else
		{
			global $DB;

			if (!is_array($arOrder) && !is_array($arFilter))
			{
				$arOrder = strval($arOrder);
				$arFilter = strval($arFilter);
				if (strlen($arOrder) > 0 && strlen($arFilter) > 0)
					$arOrder = array($arOrder => $arFilter);
				else
					$arOrder = array();
				if (is_array($arGroupBy))
					$arFilter = $arGroupBy;
				else
					$arFilter = array();
				$arGroupBy = false;
			}

			if (count($arSelectFields) <= 0)
				$arSelectFields = array("ID", "PAY_SYSTEM_ID", "PERSON_TYPE_ID", "NAME", "ACTION_FILE", "RESULT_FILE", "NEW_WINDOW", "PARAMS", "TARIF", "ENCODING", "LOGOTIP");

			// FIELDS -->
			$arFields = array(
					"ID" => array("FIELD" => "PSA.ID", "TYPE" => "int"),
					"PAY_SYSTEM_ID" => array("FIELD" => "PSA.PAY_SYSTEM_ID", "TYPE" => "int"),
					"PERSON_TYPE_ID" => array("FIELD" => "PSA.PERSON_TYPE_ID", "TYPE" => "int"),
					"NAME" => array("FIELD" => "PSA.NAME", "TYPE" => "string"),
					"ACTION_FILE" => array("FIELD" => "PSA.ACTION_FILE", "TYPE" => "string"),
					"RESULT_FILE" => array("FIELD" => "PSA.RESULT_FILE", "TYPE" => "string"),
					"NEW_WINDOW" => array("FIELD" => "PSA.NEW_WINDOW", "TYPE" => "char"),
					"PARAMS" => array("FIELD" => "PSA.PARAMS", "TYPE" => "string"),
					"TARIF" => array("FIELD" => "PSA.TARIF", "TYPE" => "string"),
					"HAVE_PAYMENT" => array("FIELD" => "PSA.HAVE_PAYMENT", "TYPE" => "char"),
					"HAVE_ACTION" => array("FIELD" => "PSA.HAVE_ACTION", "TYPE" => "char"),
					"HAVE_RESULT" => array("FIELD" => "PSA.HAVE_RESULT", "TYPE" => "char"),
					"HAVE_PREPAY" => array("FIELD" => "PSA.HAVE_PREPAY", "TYPE" => "char"),
					"HAVE_RESULT_RECEIVE" => array("FIELD" => "PSA.HAVE_RESULT_RECEIVE", "TYPE" => "char"),
					"ENCODING" => array("FIELD" => "PSA.ENCODING", "TYPE" => "string"),
					"LOGOTIP" => array("FIELD" => "PSA.LOGOTIP", "TYPE" => "int"),
					"PS_LID" => array("FIELD" => "PS.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
					"PS_CURRENCY" => array("FIELD" => "PS.CURRENCY", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
					"PS_NAME" => array("FIELD" => "PS.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
					"PS_ACTIVE" => array("FIELD" => "PS.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
					"PS_SORT" => array("FIELD" => "PS.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
					"PS_DESCRIPTION" => array("FIELD" => "PS.DESCRIPTION", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_pay_system PS ON (PSA.PAY_SYSTEM_ID = PS.ID)"),
					"PT_LID" => array("FIELD" => "PT.LID", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
					"PT_NAME" => array("FIELD" => "PT.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
					"PT_SORT" => array("FIELD" => "PT.SORT", "TYPE" => "int", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
					"PT_ACTIVE" => array("FIELD" => "PT.ACTIVE", "TYPE" => "char", "FROM" => "INNER JOIN b_sale_person_type PT ON (PSA.PERSON_TYPE_ID = PT.ID)"),
				);
			// <-- FIELDS

			$arSqls = CSaleOrder::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

			$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "DISTINCT", $arSqls["SELECT"]);

			if (is_array($arGroupBy) && count($arGroupBy)==0)
			{
				$strSql =
					"SELECT ".$arSqls["SELECT"]." ".
					"FROM b_sale_pay_system_action PSA ".
					"	".$arSqls["FROM"]." ";
				if (strlen($arSqls["WHERE"]) > 0)
					$strSql .= "WHERE ".$arSqls["WHERE"]." ";
				if (strlen($arSqls["GROUPBY"]) > 0)
					$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

				//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				if ($arRes = $dbRes->Fetch())
					return $arRes["CNT"];
				else
					return False;
			}

			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_sale_pay_system_action PSA ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
			if (strlen($arSqls["ORDERBY"]) > 0)
				$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])<=0)
			{
				$strSql_tmp =
					"SELECT COUNT('x') as CNT ".
					"FROM b_sale_pay_system_action PSA ".
					"	".$arSqls["FROM"]." ";
				if (strlen($arSqls["WHERE"]) > 0)
					$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
				if (strlen($arSqls["GROUPBY"]) > 0)
					$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

				$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$cnt = 0;
				if (strlen($arSqls["GROUPBY"]) <= 0)
				{
					if ($arRes = $dbRes->Fetch())
						$cnt = $arRes["CNT"];
				}
				else
				{
					$cnt = $dbRes->SelectedRowsCount();
				}

				$dbRes = new CDBResult();

				$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
			}
			else
			{
				if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"])>0)
					$strSql .= "LIMIT ".IntVal($arNavStartParams["nTopCount"]);
				$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}

		return $dbRes;
	}

	public static function getAliases()
	{
		return array(
			'PS_NAME' => 'NAME',
			'NAME' => 'PSA_NAME',
			'PS_ACTIVE' => 'ACTIVE',
			'PS_SORT' => 'SORT',
			'PS_DESCRIPTION' => 'DESCRIPTION'
		);
	}

	private static function getAlias($key)
	{
		$prefix = '';
		$pos = strpos($key, 'PS_');
		if ($pos > 0)
		{
			$prefix = substr($key, 0, $pos);
			$key = substr($key, $pos);
		}

		$aliases = self::getAliases();

		if (isset($aliases[$key]))
			$key = $aliases[$key];

		return $prefix.$key;
	}

	public static function checkRestriction($restriction, $filter)
	{
		if (isset($filter['PERSON_TYPE_ID']) && $restriction['CLASS_NAME'] == '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType')
		{
			if (is_array($filter['PERSON_TYPE_ID']))
			{
				foreach ($filter['PERSON_TYPE_ID'] as $personTypeId)
				{
					if (in_array($personTypeId, $restriction['PARAMS']['PERSON_TYPE_ID']))
						return true;
				}
				return false;
			}
			else
			{
				return in_array($filter['PERSON_TYPE_ID'], $restriction['PARAMS']['PERSON_TYPE_ID']);
			}
		}

		return true;
	}

	public static function getParamsByConsumer($consumer, $personTypeId)
	{
		$consumers = \Bitrix\Sale\BusinessValue::getConsumers();
		$params = array();

		if (is_array($consumers[$consumer]['CODES']) && $consumers[$consumer]['CODES'])
		{
			foreach ($consumers[$consumer]['CODES'] as $key => $val)
			{
				$map = \Bitrix\Sale\BusinessValue::getMapping($key, $consumer, $personTypeId);
				if ($map['PROVIDER_KEY'] == 'INPUT')
				{
					if ($val['INPUT']['TYPE'] == 'ENUM')
						$map['PROVIDER_KEY'] = 'SELECT';
					else
						$map['PROVIDER_KEY'] = $val['INPUT']['TYPE'];
				}

				$params[$key] = array(
					"TYPE" => ($map['PROVIDER_KEY'] != 'VALUE') ? $map['PROVIDER_KEY'] : '',
					"VALUE" => $map["PROVIDER_VALUE"]
				);
			}
		}

		return $params;
	}

	
	/**
	* <p>Метод добавляет новый обработчик платежной системы на основании параметров из массива arFields. Нестатический метод.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового обработчика платежной
	* системы, ключами в котором являются названия параметров, а
	* значениями - соответствующие значения.<br> 	  Допустимые ключи:<ul> <li>
	* <b>PAY_SYSTEM_ID</b> - код платежной системы;</li> 	<li> <b>PERSON_TYPE_ID</b> - код типа
	* плательщика;</li> 	<li> <b>NAME</b> - название платежной системы;</li> 	<li>
	* <b>ACTION_FILE</b> - скрипт платежной системы;</li> 	<li> <b>RESULT_FILE</b> - скрипт
	* получения результатов;</li> 	<li> <b>NEW_WINDOW</b> - флаг (Y/N) открывать ли
	* скрипт платежной системы в новом окне</li> 	<li> <b>PARAMS</b> - параметры
	* вызова обработчика</li> 	<li> <b>HAVE_PAYMENT</b> - есть вариант обработчика
	* для работы после оформления заказа</li> 	<li> <b>HAVE_ACTION</b> - есть вариант
	* обработчика для мгновенного списания денег</li> 	<li> <b>HAVE_RESULT</b> -
	* есть скрипт запроса результатов</li> 	<li> <b>HAVE_PREPAY</b> - есть вариант
	* обработчика для работы во время оформления заказа.</li> </ul>
	*
	* @return int <p>Возвращается код обновленной записи или <i>false</i> - в случае
	* ошибки.  </p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__add.d76269ee.php
	* @author Bitrix
	*/
	public static function Add($fields)
	{
		if (\Bitrix\Main\Config\Option::get('main', '~sale_paysystem_converted') == 'Y')
		{
			if (!CSalePaySystemAction::CheckFields("ADD", $fields))
				return false;

			if (isset($fields['ACTION_FILE']))
			{
				$map = self::getOldToNewHandlersMap();
				if (isset($map[$fields['ACTION_FILE']]))
					$fields['ACTION_FILE'] = $map[$fields['ACTION_FILE']];
			}

			$fields['PSA_NAME'] = $fields['NAME'];

			if (array_key_exists("LOGOTIP", $fields) && is_array($fields["LOGOTIP"]))
				$fields["LOGOTIP"]["MODULE_ID"] = "sale";
			CFile::SaveForDB($fields, "LOGOTIP", "sale/paysystem/logotip");

			if (isset($fields['PAY_SYSTEM_ID']) && $fields['PAY_SYSTEM_ID'] > 0)
			{
				$dbRes = PaySystemActionTable::getById($fields['PAY_SYSTEM_ID']);
				$data = $dbRes->fetch();
				if ($data['ACTION_FILE'] != '')
					$result = PaySystemActionTable::add($fields);
				else
					$result = PaySystemActionTable::update($fields['PAY_SYSTEM_ID'], $fields);
			}
			else
			{
				$result = PaySystemActionTable::add($fields);
			}

			if ($result->isSuccess())
			{
				if ($fields['PARAMS'])
				{
					$params = unserialize($fields['PARAMS']);
					if (!isset($params['BX_PAY_SYSTEM_ID']))
					{
						$params['BX_PAY_SYSTEM_ID'] = array(
								'TYPE' => '',
								'VALUE' => $result->getId()
						);
						PaySystemActionTable::update($result->getId(), array('PARAMS' => serialize($params)));
						$consumers = \Bitrix\Sale\BusinessValue::getConsumers();
						if (!isset($consumers['PAYSYSTEM_'.$result->getId()]))
							\Bitrix\Sale\BusinessValue::addConsumer('PAYSYSTEM_'.$result->getId(), \Bitrix\Sale\PaySystem\Manager::getHandlerDescription($fields['ACTION_FILE']));
						else
							\Bitrix\Sale\BusinessValue::changeConsumer('PAYSYSTEM_'.$result->getId(), \Bitrix\Sale\PaySystem\Manager::getHandlerDescription($fields['ACTION_FILE']));
					}
					$params = self::prepareParamsForBusVal($result->getId(), $fields);
					foreach ($params as $item)
						\Bitrix\Sale\BusinessValue::setMapping($item['CODE'], $item['CONSUMER'], $item['PERSON_TYPE_ID'], $item['MAP']);
				}
				if (isset($fields['PERSON_TYPE_ID']) && $fields['PERSON_TYPE_ID'] > 0)
				{
					$fields = array(
							"SERVICE_ID" => $result->getId(),
							"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
							"SORT" => 100,
							"PARAMS" => array(
									'PERSON_TYPE_ID' => array($fields['PERSON_TYPE_ID'])
							)
					);
					\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::save($fields);
				}
				return $result->getId();
			}

			return false;
		}
		else
		{
			global $DB;
			$arFields = $fields;

			if (!CSalePaySystemAction::CheckFields("ADD", $arFields))
				return false;

			if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
				$arFields["LOGOTIP"]["MODULE_ID"] = "sale";

			CFile::SaveForDB($arFields, "LOGOTIP", "sale/paysystem/logotip");

			$arInsert = $DB->PrepareInsert("b_sale_pay_system_action", $arFields);

			$strSql =
				"INSERT INTO b_sale_pay_system_action(".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$ID = IntVal($DB->LastID());

			return $ID;
		}
	}

	
	/**
	* <p>Метод обновляет параметры обработчика с кодом ID платежной системы в соответствии с массивом arFields. Нестатический метод.</p>
	*
	*
	* @param mixed $intID  Код обработчика платежной системы.
	*
	* @param array $arFields  Ассоциативный массив новых параметров платежной системы,
	* ключами в котором являются названия параметров, а значениями -
	* соответствующие значения.<br> 	  Допустимые ключи:<ul> <li> <b>PAY_SYSTEM_ID</b> -
	* код платежной системы;</li> 	<li> <b>PERSON_TYPE_ID</b> - код типа
	* плательщика;</li> 	<li> <b>NAME</b> - название платежной системы;</li> 	<li>
	* <b>ACTION_FILE</b> - скрипт платежной системы;</li> 	<li> <b>RESULT_FILE</b> - скрипт
	* получения результатов;</li> 	<li> <b>NEW_WINDOW</b> - флаг (Y/N) открывать ли
	* скрипт платежной системы в новом окне</li> 	<li> <b>PARAMS</b> - параметры
	* вызова обработчика</li> 	<li> <b>HAVE_PAYMENT</b> - есть вариант обработчика
	* для работы после оформления заказа</li> 	<li> <b>HAVE_ACTION</b> - есть вариант
	* обработчика для мгновенного списания денег</li> 	<li> <b>HAVE_RESULT</b> -
	* есть скрипт запроса результатов</li> 	<li> <b>HAVE_PREPAY</b> - есть вариант
	* обработчика для работы во время оформления заказа.</li> </ul>
	*
	* @return int <p>Возвращается код обновленной записи или <i>false</i> - в случае
	* ошибки.  </p><br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__update.f76269ee.php
	* @author Bitrix
	*/
	public static function Update($id, $fields)
	{
		if (\Bitrix\Main\Config\Option::get('main', '~sale_paysystem_converted') == 'Y')
		{
			$id = (int)$id;
			if (isset($fields['ACTION_FILE']))
			{
				$map = self::getOldToNewHandlersMap();
				if (isset($map[$fields['ACTION_FILE']]))
					$fields['ACTION_FILE'] = $map[$fields['ACTION_FILE']];
			}

			if (!CSalePaySystemAction::CheckFields("UPDATE", $fields))
				return false;

			if (array_key_exists("LOGOTIP", $fields) && is_array($fields["LOGOTIP"]))
				$fields["LOGOTIP"]["MODULE_ID"] = "sale";
			CFile::SaveForDB($fields, "LOGOTIP", "sale/paysystem/logotip");

			if (isset($fields['PARAMS']))
			{
				$params = unserialize($fields['PARAMS']);
				if (!isset($params['BX_PAY_SYSTEM_ID']))
					$params['BX_PAY_SYSTEM_ID'] = array('TYPE' => '', 'VALUE' => $id);
				$fields['PARAMS'] = serialize($params);
			}

			$result = PaySystemActionTable::update($id, $fields);
			if ($result->isSuccess())
			{
				if (array_key_exists('PARAMS', $fields))
				{
					$params = self::prepareParamsForBusVal($id, $fields);
					foreach ($params as $item)
						\Bitrix\Sale\BusinessValue::setMapping($item['CODE'], $item['CONSUMER'], $item['PERSON_TYPE_ID'], $item['MAP']);
				}

				if ($fields['PERSON_TYPE_ID'])
				{
					$params = array(
						'filter' => array(
							"SERVICE_ID" => $id,
							"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
							"=CLASS_NAME" => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType'
						)
					);

					$dbRes = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList($params);
					if ($data = $dbRes->fetch())
						$restrictionId = $data['ID'];
					else
						$restrictionId = 0;

					$fields = array(
						"SERVICE_ID" => $id,
						"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
						"SORT" => 100,
						"PARAMS" => array('PERSON_TYPE_ID' => array($fields['PERSON_TYPE_ID']))
					);

					\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::save($fields, $restrictionId);
				}

				return $id;
			}

			return false;
		}
		else
		{
			global $DB;

			$arFields = $fields;
			$ID = IntVal($id);
			if (!CSalePaySystemAction::CheckFields("UPDATE", $arFields))
				return false;

			if (array_key_exists("LOGOTIP", $arFields) && is_array($arFields["LOGOTIP"]))
				$arFields["LOGOTIP"]["MODULE_ID"] = "sale";

			CFile::SaveForDB($arFields, "LOGOTIP", "sale/paysystem/logotip");

			$strUpdate = $DB->PrepareUpdate("b_sale_pay_system_action", $arFields);
			$strSql = "UPDATE b_sale_pay_system_action SET ".$strUpdate." WHERE ID = ".$ID."";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			return $ID;
		}
	}

	public static function prepareParamsForBusVal($id, $fields)
	{
		if (!array_key_exists('PERSON_TYPE_ID', $fields))
		{
			$personTypeList = CSalePaySystem::getPaySystemPersonTypeIds($id);
			if ($personTypeList)
				$fields['PERSON_TYPE_ID'] = array_shift($personTypeList);
		}

		$itemParams = unserialize($fields['PARAMS']);

		$result = array();

		$result[] = array(
			'CODE' => 'BX_PAY_SYSTEM_ID',
			'CONSUMER' => 'PAYSYSTEM_'.$id,
			'PERSON_TYPE_ID' => $fields['PERSON_TYPE_ID'] ?: null,
			'MAP' => array(
				'PROVIDER_KEY' => 'VALUE',
				'PROVIDER_VALUE' => $id
		));

		if ($itemParams)
		{
			foreach ($itemParams as $code => $param)
			{
				if ($param['TYPE'] == '')
				{
					$type = 'VALUE';
				}
				elseif ($param['TYPE'] == 'FILE' || $param['TYPE'] == 'SELECT' || $param['TYPE'] == 'ENUM' || $param['TYPE'] == 'CHECKBOX')
				{
					$type = 'INPUT';
				}
				else
				{
					$type = $param['TYPE'];
				}

				$result[] = array(
					'CODE' => $code,
					'CONSUMER' => 'PAYSYSTEM_'.$id,
					'PERSON_TYPE_ID' => $fields['PERSON_TYPE_ID'] ?: null,
					'MAP' => array(
						'PROVIDER_KEY' => $type,
						'PROVIDER_VALUE' => $param['VALUE']
					)
				);
			}
		}

		return $result;
	}

	public static function convertPsBusVal()
	{
		if (\Bitrix\Main\Config\Option::get('main', '~sale_paysystem_converted') == 'Y')
			return '';

		\Bitrix\Main\Config\Option::set('main', '~sale_paysystem_converted', 'Y');

		if (!\Bitrix\Main\Loader::includeModule('sale'))
			return '';

		global $DB;
		if ($DB->TableExists('b_sale_pay_system_map') || $DB->TableExists('B_SALE_PAY_SYSTEM_MAP'))
			return '';

		$dbRes = \Bitrix\Sale\Internals\PaySystemActionTable::getList();
		$oldActionFiles = self::getOldToNewHandlersMap();
		$paySystems = array();
		while ($paySystem = $dbRes->fetch())
		{
			$codesAliases = array();
			$params = unserialize($paySystem['PARAMS']);

			if (is_array($params))
			{
				if (isset($oldActionFiles[$paySystem['ACTION_FILE']]))
					$codesAliases = self::getCodesAliases($oldActionFiles[$paySystem['ACTION_FILE']]);

				foreach ($params as $key => $value)
				{
					if (isset($oldActionFiles[$paySystem['ACTION_FILE']]))
					{
						if ($key == 'IS_TEST' || $key == 'CHANGE_STATUS_PAY' || $key == 'TEST' || $key == 'DEMO' || $key == 'AUTOPAY')
						{
							$keyValue = ($value['VALUE'] != 'Y' && $value['VALUE'] != 'N') ? 'N' : $value['VALUE'];
							$value = array('TYPE' => 'INPUT', 'VALUE' => $keyValue);
						}

						if ($key == 'TEST_MODE')
							$value = array('TYPE' => 'INPUT', 'VALUE' => ($value['VALUE'] == 'TEST' ? 'Y' : 'N'));
					}

					if ($value['TYPE'] == 'SELECT' || $value['TYPE'] == 'FILE')
						$value['TYPE'] = 'INPUT';

					if (isset($codesAliases[$key]))
					{
						$params[$codesAliases[$key]] = $value;
						unset($params[$key]);
					}
					else
					{
						$params[$key] = $value;
					}
				}
			}

			if (isset($oldActionFiles[$paySystem['ACTION_FILE']]) && !IsModuleInstalled('intranet'))
			{
				if (isset($params['PAYMENT_ID']))
				{
					$value = ($params['PAYMENT_ID']['VALUE'] == 'ACCOUNT_NUMBER') ? 'ACCOUNT_NUMBER' : 'ID';
					$params['PAYMENT_ID'] = array('TYPE' => 'PAYMENT', 'VALUE' => $value);
				}

				if (isset($params['PAYMENT_CURRENCY']))
					$params['PAYMENT_CURRENCY'] = array('TYPE' => 'PAYMENT', 'VALUE' => 'CURRENCY');

				if (isset($params['PAYMENT_DATE_INSERT']))
				{
					if ($params['PAYMENT_DATE_INSERT']['VALUE'] == 'DATE_INSERT_DATE' || $params['PAYMENT_DATE_INSERT']['VALUE'] == 'DATE_BILL_DATE')
						$date = 'DATE_BILL_DATE';
					else
						$date = 'DATE_BILL';

					$params['PAYMENT_DATE_INSERT'] = array('TYPE' => 'PAYMENT', 'VALUE' => $date);
				}

				if (isset($params['PAYMENT_SHOULD_PAY']))
					$params['PAYMENT_SHOULD_PAY'] = array('TYPE' => 'PAYMENT', 'VALUE' => 'SUM');

				if (isset($params['PAYMENT_VALUE']))
					$paySystem['PS_MODE'] = $params['PAYMENT_VALUE']['VALUE'];
			}

			if (isset($oldActionFiles[$paySystem['ACTION_FILE']]))
				$paySystem['ACTION_FILE'] = $oldActionFiles[$paySystem['ACTION_FILE']];

			$paySystem['PARAMS'] = $params;

			if (!isset($paySystems[$paySystem['PAY_SYSTEM_ID']]))
				$paySystems[$paySystem['PAY_SYSTEM_ID']] = array();

			if (!isset($paySystems[$paySystem['PAY_SYSTEM_ID']][$paySystem['ACTION_FILE']]))
				$paySystems[$paySystem['PAY_SYSTEM_ID']][$paySystem['ACTION_FILE']] = array();

			$paySystems[$paySystem['PAY_SYSTEM_ID']][$paySystem['ACTION_FILE']][] = $paySystem;
		}

		$codes = array();
		foreach ($paySystems as $items)
		{
			foreach ($items as $psItem)
			{
				foreach ($psItem as $item)
				{
					$params = $item['PARAMS'];
					if ($params)
					{
						foreach ($params as $code => $value)
						{
							if ($value['VALUE'] == '')
								continue;
							if ($value['TYPE'] == '')
								$key = 'VALUE|'.$value['VALUE'];
							else
								$key = $value['TYPE'].'|'.$value['VALUE'];
							if (!isset($codes[$code][$key]))
								$codes[$code][$key] = 0;
							$codes[$code][$key]++;
						}
					}
				}
			}
		}

		$generalBusVal = array();
		foreach ($codes as $code => $values)
		{
			$generalBusVal[$code] = null;
			foreach ($values as $i => $cnt)
			{
				if ($generalBusVal[$code] === null || $values[$generalBusVal[$code]] < $cnt)
					$generalBusVal[$code] = $i;
			}
		}

		//set general
		foreach ($generalBusVal as $code => $param)
		{
			list($type, $value) = explode('|', $param);
			\Bitrix\Sale\BusinessValue::setMapping($code, null, null, array('PROVIDER_KEY' => $type, 'PROVIDER_VALUE' => $value));
		}

		$mustDeleted = array();
		$duplicateRecords = array();
		foreach ($paySystems as $actions)
		{
			foreach ($actions as $items)
			{
				$firstItem = current($items);

				if (!array_key_exists($firstItem['ID'], $duplicateRecords))
					$duplicateRecords[$firstItem['ID']] = array();

				if (!array_key_exists('PERSON_TYPE_ID', $duplicateRecords[$firstItem['ID']]))
					$duplicateRecords[$firstItem['ID']]['PERSON_TYPE_ID'] = array();

				if ($firstItem['PERSON_TYPE_ID'] > 0)
					$duplicateRecords[$firstItem['ID']]['PERSON_TYPE_ID'][] = $firstItem['PERSON_TYPE_ID'];

				$duplicateRecords[$firstItem['ID']]['EXTERNAL_ID'] = $firstItem['PAY_SYSTEM_ID'];

				foreach ($items as $ps)
				{
					if (in_array($ps['ACTION_FILE'], array('yandex', 'roboxchange')) && $firstItem['PS_MODE'] && $firstItem['PS_MODE'] != $ps['PS_MODE'])
					{
						if (!array_key_exists($ps['ID'], $duplicateRecords))
							$duplicateRecords[$ps['ID']] = array();

						if (!array_key_exists('PERSON_TYPE_ID', $duplicateRecords[$firstItem['ID']]))
							$duplicateRecords[$ps['ID']]['PERSON_TYPE_ID'] = array();

						if ($ps['PERSON_TYPE_ID'] > 0)
							$duplicateRecords[$ps['ID']]['PERSON_TYPE_ID'][] = $ps['PERSON_TYPE_ID'];

						$duplicateRecords[$ps['ID']]['EXTERNAL_ID'] = $ps['PAY_SYSTEM_ID'];
						$duplicateRecords[$ps['ID']]['NEW_PS'] = 'Y';
					}
					else
					{
						if ($ps['ID'] == $firstItem['ID'])
							continue;

						if ($ps['PERSON_TYPE_ID'] > 0)
							$duplicateRecords[$firstItem['ID']]['PERSON_TYPE_ID'][] = $ps['PERSON_TYPE_ID'];

						if (!isset($mustDeleted[$firstItem['ID']]))
							$mustDeleted[$firstItem['ID']] = array();

						$mustDeleted[$firstItem['ID']][] = $ps['ID'];
					}
				}

				foreach ($items as $item)
				{
					$itemParams = array();
					if ($item['PARAMS'])
					{
						$itemParams = $item['PARAMS'];
						if ($itemParams)
						{
							foreach ($itemParams as $code => $param)
							{
								$type = $param['TYPE'] ?: 'VALUE';
								$pT = null;
								$pS = null;

								if (in_array($item['ACTION_FILE'], array('yandex', 'roboxchange')) && $firstItem['PS_MODE'] && $firstItem['PS_MODE'] != $item['PS_MODE'])
									$consumer = 'PAYSYSTEM_'.$item['ID'];
								else
									$consumer = 'PAYSYSTEM_'.$firstItem['ID'];

								$cases = array(
									1 => array('PS' => null, 'PT' => null),
									2 => array('PS' => $consumer, 'PT' => $item['PERSON_TYPE_ID'])
								);

								foreach ($cases as $case)
								{
									if (\Bitrix\Sale\BusinessValue::isSetMapping($code, $case['PS'], $case['PT']))
									{
										$map = \Bitrix\Sale\BusinessValue::getMapping($code);
										if ($map && $map['PROVIDER_KEY'] == $type && $map['PROVIDER_VALUE'] == $param['VALUE'])
											continue(2);
									}
									else
									{
										$pT = $case['PT'];
										$pS = $case['PS'];
										break;
									}
								}

								$value = (is_array($param['VALUE'])) ? key($param['VALUE']) : $param['VALUE'];
								\Bitrix\Sale\BusinessValue::setMapping($code, $pS, $pT, array('PROVIDER_KEY' => $type, 'PROVIDER_VALUE' => $value), true);
							}
						}
					}

					if (!isset($mustDeleted[$firstItem['ID']][$item['ID']]))
					{
						$itemParams['BX_PAY_SYSTEM_ID'] = array('TYPE' => '', 'VALUE' => $item['ID']);
						$item['PARAMS'] = serialize($itemParams);
						$itemId = $item['ID'];
						unset($item['ID']);
						\Bitrix\Sale\Internals\PaySystemActionTable::update($itemId, $item);
					}
				}
			}
		}

		global $DB;
		if ($DB->TableExists('b_sale_pay_system_map'))
			$DB->Query('DROP TABLE b_sale_pay_system_map');

		if ($DB->type == 'MYSQL')
		{
			$DB->Query('
				create table if not exists b_sale_pay_system_map
				(
					PS_ID_OLD int null,
					PS_ID int null,
					PT_ID int null,
					NEW_PS char(1) not null default \'N\'
				)'
			);
		}

		if ($DB->type == 'MSSQL')
		{
			$DB->Query('
				CREATE TABLE B_SALE_PAY_SYSTEM_MAP
				(
					PS_ID int NULL,
					PS_ID_OLD int NULL,
					PT_ID int NULL,
					NEW_PS char(1) NOT NULL DEFAULT \'N\'
				)');
		}

		if ($DB->type == 'ORACLE')
		{
			$DB->Query('
				CREATE TABLE B_SALE_PAY_SYSTEM_MAP
				(
					PS_ID NUMBER(18) NULL,
					PS_ID_OLD NUMBER(18) NULL,
					PT_ID NUMBER(18) NULL,
					NEW_PS CHAR(1 CHAR) DEFAULT \'N\' NOT NULL
				)'
			);
		}

		foreach ($duplicateRecords as $id => $data)
		{
			if ($data['PERSON_TYPE_ID'])
			{
				$params = array(
					'filter' => array(
						"SERVICE_ID" => $id,
						"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
						"=CLASS_NAME" => '\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType'
					)
				);

				$dbRes = \Bitrix\Sale\Internals\ServiceRestrictionTable::getList($params);
				if (!$dbRes->fetch())
				{
					$fields = array(
						"SERVICE_ID" => $id,
						"SERVICE_TYPE" => \Bitrix\Sale\Services\PaySystem\Restrictions\Manager::SERVICE_TYPE_PAYMENT,
						"SORT" => 100,
						"PARAMS" => array(
							'PERSON_TYPE_ID' => $data['PERSON_TYPE_ID']
						)
					);
					\Bitrix\Sale\Services\PaySystem\Restrictions\PersonType::save($fields);
				}
			}
		}

		foreach ($duplicateRecords as $id => $data)
		{
			if ($data['EXTERNAL_ID'] <= 0)
				continue;

			$newPs = ($data['NEW_PS']) ?: 'N';
			foreach ($data['PERSON_TYPE_ID'] as $personTypeId)
			{
				$DB->Query('INSERT INTO b_sale_pay_system_map(PS_ID, PS_ID_OLD, PT_ID, NEW_PS) VALUES('.$id.', '.$data['EXTERNAL_ID'].', '.$personTypeId.', \''.$newPs.'\' )');
			}
		}

		$DB->Query('
			UPDATE b_sale_order SET
			PAY_SYSTEM_ID = (
				SELECT bspm.PS_ID
				FROM b_sale_pay_system_map bspm
				WHERE bspm.PT_ID=PERSON_TYPE_ID AND bspm.PS_ID_OLD=PAY_SYSTEM_ID
			)'
		);

		if ($DB->type == 'MYSQL' || $DB->type == 'ORACLE')
		{
			$DB->Query('
				UPDATE b_sale_order_payment bsop SET
				PAY_SYSTEM_ID = (
					SELECT bspm.PS_ID
					FROM b_sale_pay_system_map bspm, b_sale_order bso
					WHERE bspm.PS_ID_OLD = bsop.PAY_SYSTEM_ID
						AND bso.ID = bsop.ORDER_ID
						AND bspm.PT_ID = bso.PERSON_TYPE_ID
				)'
			);
		}
		elseif ($DB->type == 'MSSQL')
		{
			$DB->Query('
				UPDATE bsop SET
				PAY_SYSTEM_ID = (
					SELECT bspm.PS_ID
					FROM b_sale_pay_system_map bspm, b_sale_order bso
					WHERE bspm.PS_ID_OLD = bsop.PAY_SYSTEM_ID
						AND bso.ID = bsop.ORDER_ID
						AND bspm.PT_ID = bso.PERSON_TYPE_ID
				)
				FROM b_sale_order_payment bsop'
			);
		}

//		\Bitrix\Main\Config\Option::set('main', '~sale_paysystem_converted', 'Y');

		foreach ($mustDeleted as $items)
		{
			foreach ($items as $id)
				PaySystemActionTable::delete($id);
		}

		/** DELIVERY2PAYSYSTEM */
		if ($DB->type == 'MYSQL')
		{
			$DB->Query('
				UPDATE b_sale_delivery2paysystem bsd2p
				SET bsd2p.PAYSYSTEM_ID=(SELECT bspm.PS_ID FROM b_sale_pay_system_map bspm WHERE bspm.PS_ID_OLD=bsd2p.PAYSYSTEM_ID AND bspm.NEW_PS=\'N\' LIMIT 1)'
			);
		}

		if ($DB->type == 'ORACLE')
		{
			$DB->Query('
				UPDATE b_sale_delivery2paysystem bsd2p
				SET bsd2p.PAYSYSTEM_ID=(SELECT bspm.PS_ID FROM b_sale_pay_system_map bspm WHERE bspm.PS_ID_OLD=bsd2p.PAYSYSTEM_ID AND bspm.NEW_PS=\'N\' AND ROWNUM=1)'
			);
		}

		if ($DB->type == 'MSSQL')
		{
			$DB->Query('
				UPDATE bsd2p
				SET bsd2p.PAYSYSTEM_ID=(SELECT TOP(1) bspm.PS_ID FROM b_sale_pay_system_map bspm WHERE bspm.PS_ID_OLD=bsd2p.PAYSYSTEM_ID AND bspm.NEW_PS=\'N\')
				FROM b_sale_delivery2paysystem bsd2p'
			);
		}

		$DB->Query('
			INSERT INTO b_sale_delivery2paysystem(DELIVERY_ID, PAYSYSTEM_ID, LINK_DIRECTION)
			SELECT d2p.DELIVERY_ID, pm1.PS_ID, d2p.LINK_DIRECTION
			FROM b_sale_delivery2paysystem d2p
				INNER JOIN b_sale_pay_system_map pm ON d2p.PAYSYSTEM_ID = pm.PS_ID AND pm.NEW_PS = \'N\'
				INNER JOIN b_sale_pay_system_map pm1 ON pm1.PS_ID_OLD = pm.PS_ID_OLD AND pm1.NEW_PS = \'Y\''
		);

		/** ORDER_PROPS_REL */
		if ($DB->type == 'MYSQL')
		{
			$DB->Query('
				UPDATE b_sale_order_props_relation bsopr
				SET bsopr.ENTITY_ID=(SELECT bspm.PS_ID FROM b_sale_pay_system_map bspm WHERE bspm.PS_ID_OLD=bsopr.ENTITY_ID AND bspm.NEW_PS=\'N\' LIMIT 1)
				WHERE bsopr.ENTITY_TYPE=\'P\''
			);
		}

		if ($DB->type == 'ORACLE')
		{
			$DB->Query('
				UPDATE b_sale_order_props_relation bsopr
				SET bsopr.ENTITY_ID=(SELECT bspm.PS_ID FROM b_sale_pay_system_map bspm WHERE bspm.PS_ID_OLD=bsopr.ENTITY_ID AND bspm.NEW_PS=\'N\' AND ROWNUM=1)
				WHERE bsopr.ENTITY_TYPE=\'P\''
			);
		}

		if ($DB->type == 'MSSQL')
		{
			$DB->Query('
				UPDATE bsopr
				SET bsopr.ENTITY_ID=(SELECT TOP(1) bspm.PS_ID FROM b_sale_pay_system_map bspm WHERE bspm.PS_ID_OLD=bsopr.ENTITY_ID AND bspm.NEW_PS=\'N\')
				FROM b_sale_order_props_relation bsopr
				WHERE bsopr.ENTITY_TYPE=\'P\''
			);
		}

		$DB->Query('
			INSERT INTO b_sale_order_props_relation(ENTITY_ID, ENTITY_TYPE, PROPERTY_ID)
			SELECT pm1.PS_ID, opr.ENTITY_TYPE, opr.PROPERTY_ID
			FROM b_sale_order_props_relation opr
				INNER JOIN b_sale_pay_system_map pm ON pm.PS_ID = opr.ENTITY_ID AND pm.NEW_PS = \'N\'
				INNER JOIN b_sale_pay_system_map pm1 ON pm1.PS_ID_OLD = pm.PS_ID_OLD AND pm1.NEW_PS = \'Y\'
			WHERE opr.ENTITY_TYPE = \'P\''
		);

		$DB->Query('
			INSERT INTO b_sale_service_rstr(SERVICE_ID, SORT, CLASS_NAME, PARAMS, SERVICE_TYPE)
			SELECT bsd2p.PAYSYSTEM_ID, 100, \'Bitrix\\\\Sale\\\\Services\\\\PaySystem\\\\Restrictions\\\\Delivery\', \'a:0:{}\', 1 FROM b_sale_delivery2paysystem bsd2p GROUP BY bsd2p.PAYSYSTEM_ID'
		);

		$DB->Query('UPDATE b_sale_pay_system_action SET psa_name=name');

		if ($DB->type == 'MYSQL' || $DB->type == 'ORACLE')
		{
			$DB->Query('UPDATE b_sale_pay_system_action psa
				SET psa.name=(
					SELECT name
					FROM b_sale_pay_system ps
					WHERE ps.ID=psa.PAY_SYSTEM_ID
				)'
			);
		}
		else if ($DB->type == 'MSSQL')
		{
			$DB->Query('UPDATE psa
				SET psa.name=(
					SELECT name
					FROM b_sale_pay_system ps
					WHERE ps.ID=psa.PAY_SYSTEM_ID
				)
				FROM b_sale_pay_system_action psa'
			);
		}

		return '';
	}

	public static function getOldToNewHandlersMap()
	{
		return array(
			'/bitrix/modules/sale/payment/yandex_3x' => 'yandex',
			'/bitrix/modules/sale/payment/webmoney_web' => 'webmoney',
			'/bitrix/modules/sale/payment/assist' => 'assist',
			'/bitrix/modules/sale/payment/qiwi' => 'qiwi',
			'/bitrix/modules/sale/payment/paymaster' => 'paymaster',
			'/bitrix/modules/sale/payment/paypal' => 'paypal',
			'/bitrix/modules/sale/payment/roboxchange' => 'roboxchange',
			'/bitrix/modules/sale/payment/sberbank_new' => 'sberbank',
			'/bitrix/modules/sale/payment/bill' => 'bill',
			'/bitrix/modules/sale/payment/bill_en' => 'billen',
			'/bitrix/modules/sale/payment/bill_de' => 'billde',
			'/bitrix/modules/sale/payment/bill_ua' => 'billua',
			'/bitrix/modules/sale/payment/bill_la' => 'billla',
			'/bitrix/modules/sale/payment/liqpay' => 'liqpay',
			'/bitrix/modules/sale/payment/payment_forward_calc' => 'cashondeliverycalc',
			'/bitrix/modules/sale/payment/payment_forward' => 'cashondelivery',
			'/bitrix/modules/sale/payment/cash' => 'cash',
			'INNER_BUDGET' => 'inner'
		);
	}

	public static function getCodesAliases($handler)
	{
		$psAliases = array(
			'general' => array(
				'ORDER_ID' => 'PAYMENT_ID',
				'SHOULD_PAY' => 'PAYMENT_SHOULD_PAY',
				'DATE_INSERT' => 'PAYMENT_DATE_INSERT'
			),
			'yandex' => array(
				'SHOP_ID' => 'YANDEX_SHOP_ID',
				'SCID' => 'YANDEX_SCID',
				'SHOP_KEY' => 'YANDEX_SHOP_KEY',
				'IS_TEST' => 'PS_IS_TEST',
				'CHANGE_STATUS_PAY' => 'PS_CHANGE_STATUS_PAY',
				'ORDER_DATE' => 'PAYMENT_DATE_INSERT'
			),
			'webmoney' => array(
				'SHOP_ACCT' => 'WEBMONEY_SHOP_ACCT',
				'TEST_MODE' => 'PS_IS_TEST',
				'CNST_SECRET_KEY' => 'WEBMONEY_CNST_SECRET_KEY',
				'HASH_ALGO' => 'WEBMONEY_HASH_ALGO',
				'RESULT_URL' => 'WEBMONEY_RESULT_URL',
				'SUCCESS_URL' => 'WEBMONEY_SUCCESS_URL',
				'FAIL_URL' => 'WEBMONEY_FAIL_URL',
				'CHANGE_STATUS_PAY' => 'PS_CHANGE_STATUS_PAY',
			),
			'roboxchange' => array(
				'ShopLogin' => 'ROBOXCHANGE_SHOPLOGIN',
				'ShopPassword' => 'ROBOXCHANGE_SHOPPASSWORD',
				'ShopPassword2' => 'ROBOXCHANGE_SHOPPASSWORD2',
				'OrderDescr' => 'ROBOXCHANGE_ORDERDESCR',
				'IS_TEST' => 'PS_IS_TEST',
				'CHANGE_STATUS_PAY' => 'PS_CHANGE_STATUS_PAY'
			),
			'qiwi' => array(
				'CHANGE_STATUS_PAY' => 'PS_CHANGE_STATUS_PAY',
				'FAIL_URL' => 'QIWI_FAIL_URL',
				'SUCCESS_URL' => 'QIWI_SUCCESS_URL',
				'AUTHORIZATION' => 'QIWI_AUTHORIZATION',
				'BILL_LIFETIME' => 'QIWI_BILL_LIFETIME',
				'API_LOGIN' => 'QIWI_API_LOGIN',
				'SHOP_ID' => 'QIWI_SHOP_ID',
				'API_PASSWORD' => 'QIWI_API_PASSWORD',
				'NOTICE_PASSWORD' => 'QIWI_NOTICE_PASSWORD',
				'CURRENCY' => 'PAYMENT_CURRENCY'
			),
			'paypal' => array(
				'USER' => 'PAYPAL_USER',
				'PWD' => 'PAYPAL_PWD',
				'SIGNATURE' => 'PAYPAL_SIGNATURE',
				'NOTIFY_URL' => 'PAYPAL_NOTIFY_URL',
				'SSL_ENABLE' => 'PAYPAL_SSL_ENABLE',
				'BUTTON_SRC' => 'PAYPAL_BUTTON_SRC',
				'ON1' => 'PAYPAL_ON1',
				'BUSINESS' => 'PAYPAL_BUSINESS',
				'IDENTITY_TOKEN' => 'PAYPAL_IDENTITY_TOKEN',
				'RETURN' => 'PAYPAL_RETURN',
				'TEST' => 'PS_IS_TEST',
				'ON0' => 'PAYPAL_ON0'
			),
			'paymaster' => array(
				'SHOP_ACCT' => 'PAYMASTER_SHOP_ACCT',
				'CNST_SECRET_KEY' => 'PAYMASTER_CNST_SECRET_KEY',
				'RESULT_URL' => 'PAYMASTER_RESULT_URL',
				'SUCCESS_URL' => 'PAYMASTER_SUCCESS_URL',
				'FAIL_URL' => 'PAYMASTER_FAIL_URL',
				'TEST_MODE' => 'PS_IS_TEST'
			),
			'liqpay' => array(
				'MERCHANT_ID' => 'LIQPAY_MERCHANT_ID',
				'SIGN' => 'LIQPAY_SIGN',
				'PATH_TO_RESULT_URL' => 'LIQPAY_PATH_TO_RESULT_URL',
				'PATH_TO_SERVER_URL' => 'LIQPAY_PATH_TO_SERVER_URL',
				'PAY_METHOD' => 'LIQPAY_PAY_METHOD',
				'CURRENCY' => 'PAYMENT_CURRENCY',
			),
			'assist' => array(
				'SHOP_IDP' => 'ASSIST_SHOP_IDP',
				'SHOP_LOGIN' => 'ASSIST_SHOP_LOGIN',
				'SHOP_PASSWORD' => 'ASSIST_SHOP_PASSWORD',
				'SHOP_SECRET_WORLD' => 'ASSIST_SHOP_SECRET_WORLD',
				'SUCCESS_URL' => 'ASSIST_SUCCESS_URL',
				'FAIL_URL' => 'ASSIST_FAIL_URL',
				'PAYMENT_CardPayment' => 'ASSIST_PAYMENT_CardPayment',
				'PAYMENT_YMPayment' => 'ASSIST_PAYMENT_YMPayment',
				'PAYMENT_WebMoneyPayment' => 'ASSIST_PAYMENT_WebMoneyPayment',
				'PAYMENT_QIWIPayment' => 'ASSIST_PAYMENT_QIWIPayment',
				'PAYMENT_AssistIDCCPayment' => 'ASSIST_PAYMENT_AssistIDCCPayment',
				'DELAY' => 'ASSIST_DELAY',
				'DEMO' => 'PS_IS_TEST',
				'AUTOPAY' => 'PS_CHANGE_STATUS_PAY',
				'CURRENCY' => 'PAYMENT_CURRENCY'
			),
			'sberbank' => array(
				'CURRENCY' => 'PAYMENT_CURRENCY',
				'COMPANY_NAME' => 'SELLER_COMPANY_NAME',
				'INN' => 'SELLER_COMPANY_INN',
				'KPP' => 'SELLER_COMPANY_KPP',
				'SETTLEMENT_ACCOUNT' => 'SELLER_COMPANY_BANK_ACCOUNT',
				'BANK_NAME' => 'SELLER_COMPANY_BANK_NAME',
				'BANK_BIC' => 'SELLER_COMPANY_BANK_BIC',
				'BANK_COR_ACCOUNT' => 'SELLER_COMPANY_BANK_ACCOUNT_CORR',
				'PAYER_CONTACT_PERSON' => 'BUYER_PERSON_FIO',
				'PAYER_ZIP_CODE' => 'BUYER_PERSON_ZIP',
				'PAYER_COUNTRY' => 'BUYER_PERSON_COUNTRY',
				'PAYER_REGION' => 'BUYER_PERSON_REGION',
				'PAYER_CITY' => 'BUYER_PERSON_CITY',
				'PAYER_ADDRESS_FACT' => 'BUYER_PERSON_ADDRESS_FACT'
			),
			'bill' => array(
				'ORDER_SUBJECT' => 'BILL_ORDER_SUBJECT',
				'DATE_PAY_BEFORE' => 'PAYMENT_DATE_PAY_BEFORE',
				'SELLER_NAME' => 'SELLER_COMPANY_NAME',
				'SELLER_ADDRESS' => 'SELLER_COMPANY_ADDRESS',
				'SELLER_PHONE' => 'SELLER_COMPANY_PHONE',
				'SELLER_INN' => 'SELLER_COMPANY_INN',
				'SELLER_KPP' => 'SELLER_COMPANY_KPP',
				'SELLER_RS' => 'SELLER_COMPANY_BANK_ACCOUNT',
				'SELLER_BANK' => 'SELLER_COMPANY_BANK_NAME',
				'SELLER_BCITY' => 'SELLER_COMPANY_BANK_CITY',
				'SELLER_KS' => 'SELLER_COMPANY_BANK_ACCOUNT_CORR',
				'SELLER_BIK' => 'SELLER_COMPANY_BANK_BIC',
				'SELLER_DIR_POS' => 'SELLER_COMPANY_DIRECTOR_POSITION',
				'SELLER_ACC_POS' => 'SELLER_COMPANY_ACCOUNTANT_POSITION',
				'SELLER_DIR' => 'SELLER_COMPANY_DIRECTOR_NAME',
				'SELLER_ACC' => 'SELLER_COMPANY_ACCOUNTANT_NAME',
				'BUYER_NAME' => 'BUYER_PERSON_COMPANY_NAME',
				'BUYER_INN' => 'BUYER_PERSON_COMPANY_INN',
				'BUYER_ADDRESS' => 'BUYER_PERSON_COMPANY_ADDRESS',
				'BUYER_PHONE' => 'BUYER_PERSON_COMPANY_PHONE',
				'BUYER_FAX' => 'BUYER_PERSON_COMPANY_FAX',
				'BUYER_PAYER_NAME' => 'BUYER_PERSON_COMPANY_NAME_CONTACT',
				'COMMENT1' => 'BILL_COMMENT1',
				'COMMENT2' => 'BILL_COMMENT2',
				'PATH_TO_LOGO' => 'BILL_PATH_TO_LOGO',
				'LOGO_DPI' => 'BILL_LOGO_DPI',
				'PATH_TO_STAMP' => 'BILL_PATH_TO_STAMP',
				'SELLER_DIR_SIGN' => 'SELLER_COMPANY_DIR_SIGN',
				'SELLER_ACC_SIGN' => 'SELLER_COMPANY_ACC_SIGN',
				'BACKGROUND' => 'BILL_BACKGROUND',
				'BACKGROUND_STYLE' => 'BILL_BACKGROUND_STYLE',
				'MARGIN_TOP' => 'BILL_MARGIN_TOP',
				'MARGIN_RIGHT' => 'BILL_MARGIN_RIGHT',
				'MARGIN_BOTTOM' => 'BILL_MARGIN_BOTTOM',
				'MARGIN_LEFT' => 'BILL_MARGIN_LEFT'
			),
			'billen' => array(
				'DATE_PAY_BEFORE' => 'PAYMENT_DATE_PAY_BEFORE',
				'SELLER_NAME' => 'SELLER_COMPANY_NAME',
				'SELLER_ADDRESS' => 'SELLER_COMPANY_ADDRESS',
				'SELLER_PHONE' => 'SELLER_COMPANY_PHONE',
				'SELLER_BANK_ACCNO' => 'SELLER_COMPANY_BANK_ACCOUNT',
				'SELLER_BANK' => 'SELLER_COMPANY_BANK_NAME',
				'SELLER_BANK_ADDR' => 'SELLER_COMPANY_BANK_ADDR',
				'SELLER_BANK_PHONE' => 'SELLER_COMPANY_BANK_PHONE',
				'SELLER_BANK_ROUTENO' => 'SELLER_COMPANY_BANK_ACCOUNT_CORR',
				'SELLER_BANK_SWIFT' => 'SELLER_COMPANY_BANK_SWIFT',
				'SELLER_DIR_POS' => 'SELLER_COMPANY_DIRECTOR_POSITION',
				'SELLER_ACC_POS' => 'SELLER_COMPANY_ACCOUNTANT_POSITION',
				'SELLER_DIR' => 'SELLER_COMPANY_DIRECTOR_NAME',
				'SELLER_ACC' => 'SELLER_COMPANY_ACCOUNTANT_NAME',
				'BUYER_NAME' => 'BUYER_PERSON_COMPANY_NAME',
				'BUYER_ADDRESS' => 'BUYER_PERSON_COMPANY_ADDRESS',
				'BUYER_PHONE' => 'BUYER_PERSON_COMPANY_PHONE',
				'BUYER_FAX' => 'BUYER_PERSON_COMPANY_FAX',
				'BUYER_PAYER_NAME' => 'BUYER_PERSON_COMPANY_NAME_CONTACT',
				'COMMENT1' => 'BILLEN_COMMENT1',
				'COMMENT2' => 'BILLEN_COMMENT2',
				'PATH_TO_LOGO' => 'BILLEN_PATH_TO_LOGO',
				'LOGO_DPI' => 'BILLEN_LOGO_DPI',
				'PATH_TO_STAMP' => 'BILLEN_PATH_TO_STAMP',
				'SELLER_DIR_SIGN' => 'SELLER_COMPANY_DIR_SIGN',
				'SELLER_ACC_SIGN' => 'SELLER_COMPANY_ACC_SIGN',
				'BACKGROUND' => 'BILLEN_BACKGROUND',
				'BACKGROUND_STYLE' => 'BILLEN_BACKGROUND_STYLE',
				'MARGIN_TOP' => 'BILLEN_MARGIN_TOP',
				'MARGIN_RIGHT' => 'BILLEN_MARGIN_RIGHT',
				'MARGIN_BOTTOM' => 'BILLEN_MARGIN_BOTTOM',
				'MARGIN_LEFT' => 'BILLEN_MARGIN_LEFT'
			),
			'billde' => array(
				'DATE_PAY_BEFORE' => 'PAYMENT_DATE_PAY_BEFORE',
				'SELLER_NAME' => 'SELLER_COMPANY_NAME',
				'SELLER_ADDRESS' => 'SELLER_COMPANY_ADDRESS',
				'SELLER_PHONE' => 'SELLER_COMPANY_PHONE',
				'SELLER_EMAIL' => 'SELLER_COMPANY_EMAIL',
				'SELLER_BANK_ACCNO' => 'SELLER_COMPANY_BANK_ACCOUNT',
				'SELLER_BANK' => 'SELLER_COMPANY_BANK_NAME',
				'SELLER_BANK_BLZ' => 'SELLER_COMPANY_BANK_BIC',
				'SELLER_BANK_IBAN' => 'SELLER_COMPANY_BANK_IBAN',
				'SELLER_BANK_SWIFT' => 'SELLER_COMPANY_BANK_SWIFT',
				'SELLER_EU_INN' => 'SELLER_COMPANY_EU_INN',
				'SELLER_INN' => 'SELLER_COMPANY_INN',
				'SELLER_REG' => 'SELLER_COMPANY_REG',
				'SELLER_DIR_POS' => 'SELLER_COMPANY_DIRECTOR_POSITION',
				'SELLER_ACC_POS' => 'SELLER_COMPANY_ACCOUNTANT_POSITION',
				'SELLER_DIR' => 'SELLER_COMPANY_DIRECTOR_NAME',
				'SELLER_ACC' => 'SELLER_COMPANY_ACCOUNTANT_NAME',
				'BUYER_ID' => 'BUYER_PERSON_COMPANY_ID',
				'BUYER_NAME' => 'BUYER_PERSON_COMPANY_NAME',
				'BUYER_ADDRESS' => 'BUYER_PERSON_COMPANY_ADDRESS',
				'BUYER_PHONE' => 'BUYER_PERSON_COMPANY_PHONE',
				'BUYER_FAX' => 'BUYER_PERSON_COMPANY_FAX',
				'BUYER_PAYER_NAME' => 'BUYER_PERSON_COMPANY_PAYER_NAME',
				'COMMENT1' => 'BILLDE_COMMENT1',
				'COMMENT2' => 'BILLDE_COMMENT2',
				'PATH_TO_LOGO' => 'BILLDE_PATH_TO_LOGO',
				'LOGO_DPI' => 'BILLDE_LOGO_DPI',
				'PATH_TO_STAMP' => 'BILLDE_PATH_TO_STAMP',
				'SELLER_DIR_SIGN' => 'SELLER_COMPANY_DIR_SIGN',
				'SELLER_ACC_SIGN' => 'SELLER_COMPANY_ACC_SIGN',
				'BACKGROUND' => 'BILLDE_BACKGROUND',
				'BACKGROUND_STYLE' => 'BILLDE_BACKGROUND_STYLE',
				'MARGIN_TOP' => 'BILLDE_MARGIN_TOP',
				'MARGIN_RIGHT' => 'BILLDE_MARGIN_RIGHT',
				'MARGIN_BOTTOM' => 'BILLDE_MARGIN_BOTTOM',
				'MARGIN_LEFT' => 'BILLDE_MARGIN_LEFT'
			),
			'billua' => array(
				'DATE_PAY_BEFORE' => 'PAYMENT_DATE_PAY_BEFORE',
				'SELLER_NAME' => 'SELLER_COMPANY_NAME',
				'SELLER_RS' => 'SELLER_COMPANY_BANK_ACCOUNT',
				'SELLER_BANK' => 'SELLER_COMPANY_BANK_NAME',
				'SELLER_MFO' => 'SELLER_COMPANY_MFO',
				'SELLER_ADDRESS' => 'SELLER_COMPANY_ADDRESS',
				'SELLER_PHONE' => 'SELLER_COMPANY_PHONE',
				'SELLER_EDRPOY' => 'SELLER_COMPANY_EDRPOY',
				'SELLER_IPN' => 'SELLER_COMPANY_IPN',
				'SELLER_PDV' => 'SELLER_COMPANY_PDV',
				'SELLER_SYS' => 'SELLER_COMPANY_SYS',
				'SELLER_ACC' => 'SELLER_COMPANY_ACCOUNTANT_NAME',
				'SELLER_ACC_POS' => 'SELLER_COMPANY_ACCOUNTANT_POSITION',
				'SELLER_ACC_SIGN' => 'SELLER_COMPANY_ACC_SIGN',
				'BUYER_NAME' => 'BUYER_PERSON_COMPANY_NAME',
				'BUYER_ADDRESS' => 'BUYER_PERSON_COMPANY_ADDRESS',
				'BUYER_PHONE' => 'BUYER_PERSON_COMPANY_PHONE',
				'BUYER_FAX' => 'BUYER_PERSON_COMPANY_FAX',
				'BUYER_DOGOVOR' => 'BUYER_PERSON_COMPANY_DOGOVOR',
				'COMMENT1' => 'BILLUA_COMMENT1',
				'COMMENT2' => 'BILLUA_COMMENT2',
				'PATH_TO_STAMP' => 'BILLUA_PATH_TO_STAMP',
				'BACKGROUND' => 'BILLUA_BACKGROUND',
				'BACKGROUND_STYLE' => 'BILLUA_BACKGROUND_STYLE',
				'MARGIN_TOP' => 'BILLUA_MARGIN_TOP',
				'MARGIN_RIGHT' => 'BILLUA_MARGIN_RIGHT',
				'MARGIN_BOTTOM' => 'BILLUA_MARGIN_BOTTOM',
				'MARGIN_LEFT' => 'BILLUA_MARGIN_LEFT'
			),
			'billla' => array(
				'DATE_PAY_BEFORE' => 'PAYMENT_DATE_PAY_BEFORE',
				'SELLER_NAME' => 'SELLER_COMPANY_NAME',
				'SELLER_ADDRESS' => 'SELLER_COMPANY_ADDRESS',
				'SELLER_PHONE' => 'SELLER_COMPANY_PHONE',
				'SELLER_BANK_ACCNO' => 'SELLER_COMPANY_BANK_ACCOUNT',
				'SELLER_BANK' => 'SELLER_COMPANY_BANK_NAME',
				'SELLER_BANK_ADDR' => 'SELLER_COMPANY_BANK_ADDR',
				'SELLER_BANK_PHONE' => 'SELLER_COMPANY_BANK_PHONE',
				'SELLER_BANK_ROUTENO' => 'SELLER_COMPANY_BANK_ACCOUNT_CORR',
				'SELLER_BANK_SWIFT' => 'SELLER_COMPANY_BANK_SWIFT',
				'SELLER_DIR_POS' => 'SELLER_COMPANY_DIRECTOR_POSITION',
				'SELLER_ACC_POS' => 'SELLER_COMPANY_ACCOUNTANT_POSITION',
				'SELLER_DIR' => 'SELLER_COMPANY_DIRECTOR_NAME',
				'SELLER_ACC' => 'SELLER_COMPANY_ACCOUNTANT_NAME',
				'BUYER_NAME' => 'BUYER_PERSON_COMPANY_NAME',
				'BUYER_ADDRESS' => 'BUYER_PERSON_COMPANY_ADDRESS',
				'BUYER_PHONE' => 'BUYER_PERSON_COMPANY_PHONE',
				'BUYER_FAX' => 'BUYER_PERSON_COMPANY_FAX',
				'BUYER_PAYER_NAME' => 'BUYER_PERSON_COMPANY_NAME_CONTACT',
				'COMMENT1' => 'BILLLA_COMMENT1',
				'COMMENT2' => 'BILLLA_COMMENT2',
				'PATH_TO_LOGO' => 'BILLLA_PATH_TO_LOGO',
				'LOGO_DPI' => 'BILLLA_LOGO_DPI',
				'PATH_TO_STAMP' => 'BILLLA_PATH_TO_STAMP',
				'SELLER_DIR_SIGN' => 'SELLER_COMPANY_DIR_SIGN',
				'SELLER_ACC_SIGN' => 'SELLER_COMPANY_ACC_SIGN',
				'BACKGROUND' => 'BILLLA_BACKGROUND',
				'BACKGROUND_STYLE' => 'BILLLA_BACKGROUND_STYLE',
				'MARGIN_TOP' => 'BILLLA_MARGIN_TOP',
				'MARGIN_RIGHT' => 'BILLLA_MARGIN_RIGHT',
				'MARGIN_BOTTOM' => 'BILLLA_MARGIN_BOTTOM',
				'MARGIN_LEFT' => 'BILLLA_MARGIN_LEFT'
			)
		);

		$handlerAliases = $psAliases[ToLower($handler)];
		if (is_array($handlerAliases))
			return array_merge($psAliases['general'], $handlerAliases);

		return $psAliases['general'];
	}
}
?>