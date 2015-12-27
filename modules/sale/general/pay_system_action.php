<?
IncludeModuleLangFile(__FILE__);


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/index.php
 * @author Bitrix
 */
class CAllSalePaySystemAction
{
	const GET_PARAM_VALUE = 1;

	
	/**
	* <p>Метод возвращает параметры обработчика платежной системы с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код обработчика платежной системы.
	*
	* @return array <p>Возвращается ассоциативный массив параметров обработчика
	* платежной системы с ключами:</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Описание</th> </tr> <tr> <td>ID</td> <td>Код обработчика
	* платежной системы.</td> </tr> <tr> <td>PAY_SYSTEM_ID</td> <td>Код платежной
	* системы.</td> </tr> <tr> <td>PERSON_TYPE_ID</td> <td>Код типа плательщика.</td> </tr> <tr>
	* <td>NAME</td> <td>Название платежной системы.</td> </tr> <tr> <td>ACTION_FILE</td>
	* <td>Скрипт платежной системы.</td> </tr> <tr> <td>RESULT_FILE</td> <td>Скрипт
	* получения результатов.</td> </tr> <tr> <td>NEW_WINDOW</td> <td>Флаг (Y/N) открывать
	* ли скрипт платежной системы в новом окне.</td> </tr> </table> <p> </p
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csalepaysystemaction/csalepaysystemaction__getbyid.3a702e2f.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$strSql =
			"SELECT * ".
			"FROM b_sale_pay_system_action ".
			"WHERE ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		global $DB, $USER;

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPSA_NO_NAME"), "ERROR_NO_NAME");
			return false;
		}
		if ((is_set($arFields, "PAY_SYSTEM_ID") || $ACTION=="ADD") && IntVal($arFields["PAY_SYSTEM_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPSA_NO_CODE"), "ERROR_NO_PAY_SYSTEM_ID");
			return false;
		}
		if ((is_set($arFields, "PERSON_TYPE_ID") || $ACTION=="ADD") && IntVal($arFields["PERSON_TYPE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SKGPSA_NO_ID_TYPE"), "ERROR_NO_PERSON_TYPE_ID");
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

		if (is_set($arFields, "PAY_SYSTEM_ID"))
		{
			if (!($arPaySystem = CSalePaySystem::GetByID($arFields["PAY_SYSTEM_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["PAY_SYSTEM_ID"], GetMessage("SKGPSA_NO_PS")), "ERROR_NO_PAY_SYSTEM");
				return false;
			}
		}

		if (is_set($arFields, "PERSON_TYPE_ID"))
		{
			if (!($arPersonType = CSalePersonType::GetByID($arFields["PERSON_TYPE_ID"])))
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["PERSON_TYPE_ID"], GetMessage("SKGPSA_NO_PERS_TYPE")), "ERROR_NO_PERSON_TYPE");
				return false;
			}
		}

		return True;
	}

	
	/**
	* <p>Метод удаляет обработчик платежной системы с кодом ID. Метод динамичный.</p>
	*
	*
	* @param int $ID  Код обработчика платежной системы.
	*
	* @return bool <p>Возвращается <i>true</i> в случае успешного удаления и <i>false</i> - в
	* противном случае.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
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
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);
		return $DB->Query("DELETE FROM b_sale_pay_system_action WHERE ID = ".$ID."", true);
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
			elseif ($type == "SELECT" || $type == "RADIO" || $type == "FILE")
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
				"TYPE" => CAdminNotify::TYPE_ERROR
			)
		);
	}

	public static function InitParamArrays($arOrder, $orderID = 0, $psParams = "", $relatedData = array(), $payment = array())
	{
		if(!is_array($relatedData))
		{
			$relatedData = array();
		}

		$GLOBALS["SALE_INPUT_PARAMS"] = array();
		$GLOBALS["SALE_CORRESPONDENCE"] = array();

		if (!is_array($arOrder) || count($arOrder) <= 0 || !array_key_exists("ID", $arOrder))
		{
			$arOrder = array();

			$orderID = IntVal($orderID);
			if ($orderID > 0)
				$arOrderTmp = CSaleOrder::GetByID($orderID);
			if(!empty($arOrderTmp))
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


		if (empty($payment))
		{
			$payment = \Bitrix\Sale\Internals\PaymentTable::getRow(
				array(
					'select' => array('ID', 'PAY_SYSTEM_ID', 'SUM', 'PAID', 'DATE_BILL'),
					'filter' => array('ORDER_ID' => $orderID, '!PAY_SYSTEM_ID' => \Bitrix\Sale\Internals\PaySystemInner::getId())
				)
			);
		}

		if (count($arOrder) > 0)
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"] = $arOrder;

		if (!empty($payment))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["SHOULD_PAY"] = $payment['SUM'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PRICE"] = $payment['SUM'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYMENT_ID"] = $payment['ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAYED"] = $payment['PAID'];
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
		if(isset($relatedData["PROPERTIES"]) && is_array($relatedData["PROPERTIES"]))
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

		if (!empty($payment))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAY_SYSTEM_ID"] = $payment['PAY_SYSTEM_ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~PAY_SYSTEM_ID"] = $payment['PAY_SYSTEM_ID'];

			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["ORDER_PAYMENT_ID"] = $payment['ID'];
			$GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["~ORDER_PAYMENT_ID"] = $payment['ID'];
		}
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

		if (count($arCurOrderProps) > 0)
			$GLOBALS["SALE_INPUT_PARAMS"]["PROPERTY"] = $arCurOrderProps;

		if (strlen($psParams) <= 0)
		{
			$dbPaySysAction = CSalePaySystemAction::GetList(
				array(),
				array(
					"PAY_SYSTEM_ID" => (!empty($payment)) ? intval($payment['PAY_SYSTEM_ID']) : intval($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PAY_SYSTEM_ID"]),
					"PERSON_TYPE_ID" => IntVal($GLOBALS["SALE_INPUT_PARAMS"]["ORDER"]["PERSON_TYPE_ID"])
				),
				false,
				false,
				array("PARAMS")
			);

			if ($arPaySysAction = $dbPaySysAction->Fetch())
			{
				$psParams = $arPaySysAction["PARAMS"];
			}
		}

		$GLOBALS["SALE_CORRESPONDENCE"] = CSalePaySystemAction::UnSerializeParams($psParams);

		foreach($GLOBALS["SALE_CORRESPONDENCE"] as $key => $val)
		{
			$GLOBALS["SALE_CORRESPONDENCE"][$key]["~VALUE"] = $val["VALUE"];
			$GLOBALS["SALE_CORRESPONDENCE"][$key]["VALUE"] = htmlspecialcharsEx($val["VALUE"]);
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

		if(isset($relatedData["BASKET_ITEMS"]) && is_array($relatedData["BASKET_ITEMS"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["BASKET_ITEMS"] = $relatedData["BASKET_ITEMS"];
		}

		if(isset($relatedData["TAX_LIST"]) && is_array($relatedData["TAX_LIST"]))
		{
			$GLOBALS["SALE_INPUT_PARAMS"]["TAX_LIST"] = $relatedData["TAX_LIST"];
		}
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
}
?>