<?
IncludeModuleLangFile(__FILE__);

class CAllSaleOrderChange
{
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		if ((is_set($arFields, "ORDER_ID") || $ACTION=="ADD") && strlen($arFields["ORDER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SOC_EMPTY_ORDER_ID"), "SOC_ADD_EMPTY_ORDER_ID");
			return false;
		}

		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && strlen($arFields["USER_ID"]) < 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SOC_EMPTY_USER_ID"), "SOC_ADD_EMPTY_USER_ID");
			return false;
		}

		if ((is_set($arFields, "TYPE") || $ACTION=="ADD") && strlen($arFields["TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SOC_EMPTY_TYPE"), "SOC_ADD_EMPTY_TYPE");
			return false;
		}

		return true;
	}

	static public function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);

		$strSql =
			"SELECT O.*, ".
			"	".$DB->DateToCharFunction("O.DATE_CREATE", "FULL")." as DATE_CREATE, ".
			"FROM b_sale_order_change SOC ".
			"WHERE O.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}

		return False;
	}

	static public function Delete($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		if ($ID <= 0)
			return False;

		return $DB->Query("DELETE FROM b_sale_order_change WHERE ID = ".$ID." ", true);
	}


	/**
	 * @param $id
	 *
	 * @return bool|CDBResult
	 */
	static public function deleteByOrderId($id)
	{
		global $DB;

		$id = intval($id);

		if ($id <= 0)
			return false;

		return $DB->Query("DELETE FROM b_sale_order_change WHERE ORDER_ID = ".$id." ", true);
	}

	/*
	 * Adds record to the order change history
	 * Wrapper around CSaleOrderChange::Add method
	 *
	 * @param int $orderId - order ID
	 * @param string $type - operation type (@see CSaleOrderChangeFormat for full list of supported operations)
	 * @param array $data - array of information relevant for the record type (will be used in the record description)
	 * @param null|string $entityName -
	 * @return bool
	 */
	public static function AddRecord($orderId, $type, $data = array(), $entityName = null, $entityId = null)
	{
		global $USER;

		if (is_object($USER))
			$userId = intval($USER->GetID());
		else
			$userId = 0;

		$arParams = array(
			"ORDER_ID" => intval($orderId),
			"TYPE" => $type,
			"DATA" => (is_array($data) ? serialize($data) : $data),
			"USER_ID" => $userId,
			"ENTITY" => $entityName,
			"ENTITY_ID" => $entityId,
		);

		return CSaleOrderChange::Add($arParams);
	}

	/*
	 * Automatically adds records to the order changes list based on changes in the fields of the Update method.
	 * By default changes in the CSaleOrder::Update and CSaleBasket::Update fields are supported.
	 *
	 * @see CSaleOrderChangeFormat - list of possible types of operations which will be used in analyzing incoming fields
	 *
	 * @param int $orderId - order ID
	 * @param array $OldFields - old fields with values (retrieved by entity GetById method)
	 * @param array $NewFields - new array of fields and their values
	 * @param array $arDeleteFields - array of fields to be ignored
	 * @param string $entityName - name of the entity (empty for order, "BASKET" for basket items etc). Used in filtering operations when creating records automatically
	 * @param $entity - entity
	 * @return bool
	 */
	public static function AddRecordsByFields($orderId, array $arOldFields, array $arNewFields, $arDeleteFields = array(), $entityName = "", $entityId = null, $entity = null, array $data = array())
	{
		if ($orderId <= 0)
			return false;

		if ($entityName == "") // for order
		{
			if (isset($arNewFields["ID"]))
				unset($arNewFields["ID"]);
		}

		foreach ($arNewFields as $key => $val)
		{
			if (is_array($val))
				continue;

			if (!array_key_exists($key, $arOldFields) || (array_key_exists($key, $arOldFields) && strlen($val) > 0 && $val != $arOldFields[$key]) && !in_array($key, $arDeleteFields))
			{
				$arRecord = CSaleOrderChange::MakeRecordFromField($key, $arNewFields, $entityName, $entity);
				if ($arRecord)
				{
					$data = array_merge($data, $arRecord["DATA"]);
					CSaleOrderChange::AddRecord($orderId, $arRecord["TYPE"], $data, $entityName, $entityId);
				}
			}
		}

		return true;
	}


	/*
	 * Creates an array of the order change record based on the necessary fields (DATA_FIELDS) if field is found among TRIGGER_FIELDS (@CSaleOrderChangeFormat)
	 *
	 * @param string $field - field name (if TRIGGER_FIELDS of any operation contains this field, a record about such operation will be created)
	 * @param array $arFields - any other fields which should be used for creating a record
	 * @param string $entity - name of the entity (empty for order, "BASKET" for basket items etc). Used in filtering operations when creating records automatically
	 * @return array with keys: TYPE - operation type @see CSaleOrderChangeFormat, DATA - array of the relevant parameters based on the DATA_FIELDS
	 */
	public static function MakeRecordFromField($field, $arFields, $entityName = "", $entity = null)
	{
		foreach (CSaleOrderChangeFormat::$operationTypes as $code => $arInfo)
		{
			if ($entityName != "" && (!isset($arInfo["ENTITY"]) || (isset($arInfo["ENTITY"]) && $arInfo["ENTITY"] != $entityName)))
				continue;

			if (in_array($field, $arInfo["TRIGGER_FIELDS"]))
			{
				$originalValues = array();
				if ($entity !== null)
				{
					/** @var \Bitrix\Sale\Internals\Fields $fields */
					$fields = $entity->getFields();
					$originalValues = $fields->getOriginalValues();
				}

				$arData = array();
				foreach ($arInfo["DATA_FIELDS"] as $fieldName)
				{
					$value = null;
					$isValueGetting = false;
					if (array_key_exists("DATA_METHOD", $arInfo) && isset($arInfo['DATA_METHOD'][$fieldName]))
					{
						$dataMethodCallback = $arInfo['DATA_METHOD'][$fieldName][0];
						$dataMethodFields = $arInfo['DATA_METHOD'][$fieldName][1];
						$dataMethodArgs = array();

						foreach ($dataMethodFields as $dataMethodFieldName)
						{
							if (isset($arFields[$dataMethodFieldName]))
							{
								$dataMethodArgs[] = $arFields[$dataMethodFieldName];
							}
						}

						if ($value = call_user_func_array($dataMethodCallback, $dataMethodArgs))
						{
							$isValueGetting = true;
						}
					}

					if (!$isValueGetting)
					{
						if (isset($arInfo["DATA_FIELDS"]) && in_array('OLD_'.$fieldName, $arInfo["DATA_FIELDS"]))
						{
							if (isset($originalValues[$fieldName]))
							{
								$arFields['OLD_'.$fieldName] = $originalValues[$fieldName];
							}
						}

						if (array_key_exists($fieldName, $arFields))
						{
							$value = $arFields[$fieldName];
						}
						elseif ($entity !== null)
						{
							$value = $entity->getField($fieldName);

							if ($value === null)
								continue;
						}
					}

					$arData[$fieldName] = TruncateText($value, 128);
				}

				return array(
					"TYPE" => $code,
					"DATA" => $arData
				);
			}
		}

		return false;
	}

	/*
	 * Returns full description of the order change record based on the formatting function and data
	 * saved for this record. Only works if specified type is found among existing types.
	 *
	 * Function is used in the order history in the detailed order view.
	 *
	 * @param string $type - one of the operation types (@see CSaleOrderChangeFormat)
	 * @param string $data - serialized data saved in the database for the record of this type
	 * @return array with keys: NAME - record name, INFO - full description (string)
	 */
	static public function GetRecordDescription($type, $data)
	{
		foreach (CSaleOrderChangeFormat::$operationTypes as $typeCode => $arInfo)
		{
			if ($type == $typeCode)
			{
				if (isset($arInfo["FUNCTION"]) && is_callable(array("CSaleOrderChangeFormat", $arInfo["FUNCTION"])))
				{
					$dataFields = $data;

					if (!(CheckSerializedData($data) && ($dataFields = unserialize($data)) !== false))
					{
						$dataFields = $data;
					}

					$dataFieldsNameList = array();

					if (isset($arInfo["DATA_FIELDS"]) && is_array($arInfo["DATA_FIELDS"]))
					{
						$dataFieldsNameList = array_flip($arInfo["DATA_FIELDS"]);
					}


					if (is_array($dataFields))
					{
						foreach ($dataFields as $paramName => $paramData)
						{
							if (array_key_exists($paramName, $dataFieldsNameList))
							{
								unset($dataFieldsNameList[$paramName]);
							}
						}

						if (!empty($dataFieldsNameList))
						{
							foreach($dataFieldsNameList as $fieldName => $fieldData)
							{
								$dataFields[$fieldName] = "";
							}
						}
					}

					$params = array($dataFields, $typeCode);

					if (!empty($arInfo['ENTITY']))
					{
						$params[] = $arInfo['ENTITY'];
					}

					$arResult = call_user_func_array(array("CSaleOrderChangeFormat", $arInfo["FUNCTION"]), $params);
					return $arResult;
				}
			}
		}

		return false;
	}
}

class CSaleOrderChangeFormat
{
	public static $operationTypes = array(
		"ORDER_DEDUCTED" => array(
			"TRIGGER_FIELDS" => array("DEDUCTED"),
			"FUNCTION" => "FormatOrderDeducted",
			"DATA_FIELDS"   => array("DEDUCTED", "REASON_UNDO_DEDUCTED"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_MARKED" => array(
			"TRIGGER_FIELDS" => array("MARKED"),
			"FUNCTION" => "FormatOrderMarked",
			"DATA_FIELDS"   => array("REASON_MARKED", "MARKED"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_RESERVED" => array(
			"TRIGGER_FIELDS" => array("RESERVED"),
			"FUNCTION" => "FormatOrderReserved",
			"DATA_FIELDS" => array("RESERVED"),
			"ENTITY" => 'SHIPMENT',
		),
		"ORDER_CANCELED" => array(
			"TRIGGER_FIELDS" => array("CANCELED"),
			"FUNCTION" => "FormatOrderCanceled",
			"DATA_FIELDS"   => array("CANCELED", "REASON_CANCELED"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_COMMENTED" => array(
			"TRIGGER_FIELDS" => array("COMMENTS"),
			"FUNCTION" => "FormatOrderCommented",
			"DATA_FIELDS" => array("COMMENTS"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_STATUS_CHANGED" => array(
			"TRIGGER_FIELDS" => array("STATUS_ID"),
			"FUNCTION" => "FormatOrderStatusChanged",
			"DATA_FIELDS" => array("STATUS_ID"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_DELIVERY_ALLOWED" => array(
			"TRIGGER_FIELDS" => array("ALLOW_DELIVERY"),
			"FUNCTION" => "FormatOrderDeliveryAllowed",
			"DATA_FIELDS" => array("ALLOW_DELIVERY"),
			"ENTITY" => 'SHIPMENT',
		),
		"ORDER_DELIVERY_DOC_CHANGED" => array(
			"TRIGGER_FIELDS" => array("DELIVERY_DOC_NUM"),
			"FUNCTION" => "FormatOrderDeliveryDocChanged",
			"DATA_FIELDS" => array("DELIVERY_DOC_NUM", "DELIVERY_DOC_DATE"),
			"ENTITY" => 'SHIPMENT',
		),
		"ORDER_PAYMENT_SYSTEM_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PAY_SYSTEM_ID"),
			"FUNCTION" => "FormatOrderPaymentSystemChanged",
			"DATA_FIELDS" => array("PAY_SYSTEM_ID"),
			"ENTITY" => 'PAYMENT',
		),
		"ORDER_PAYMENT_VOUCHER_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PAY_VOUCHER_NUM"),
			"FUNCTION" => "FormatOrderPaymentVoucherChanged",
			"DATA_FIELDS" => array("PAY_VOUCHER_NUM", "PAY_VOUCHER_DATE"),
			"ENTITY" => 'PAYMENT',
		),
		"ORDER_DELIVERY_SYSTEM_CHANGED" => array(
			"TRIGGER_FIELDS" => array("DELIVERY_ID"),
			"FUNCTION" => "FormatOrderDeliverySystemChanged",
			"DATA_FIELDS" => array("DELIVERY_ID", "DELIVERY_NAME"),
			"ENTITY" => 'SHIPMENT',
		),
		"ORDER_PERSON_TYPE_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PERSON_TYPE_ID"),
			"FUNCTION" => "FormatOrderPersonTypeChanged",
			"DATA_FIELDS" => array("PERSON_TYPE_ID"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_PAYED" => array(
			"TRIGGER_FIELDS" => array("PAYED"),
			"FUNCTION" => "FormatOrderPayed",
			"DATA_FIELDS" => array("PAYED"),
			"ENTITY" => 'PAYMENT',
		),
		"ORDER_TRACKING_NUMBER_CHANGED" => array(
			"TRIGGER_FIELDS" => array("TRACKING_NUMBER"),
			"FUNCTION" => "FormatOrderTrackingNumberChanged",
			"DATA_FIELDS" => array("TRACKING_NUMBER"),
			"ENTITY" => 'SHIPMENT',
		),
		"ORDER_USER_DESCRIPTION_CHANGED" => array(
			"TRIGGER_FIELDS" => array("USER_DESCRIPTION"),
			"FUNCTION" => "FormatOrderUserDescriptionChanged",
			"DATA_FIELDS" => array("USER_DESCRIPTION"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_PRICE_DELIVERY_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PRICE_DELIVERY"),
			"FUNCTION" => "FormatOrderPriceDeliveryChanged",
			"DATA_FIELDS" => array("PRICE_DELIVERY", "CURRENCY"),
			"ENTITY" => 'SHIPMENT',
		),
		"ORDER_PRICE_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PRICE"),
			"FUNCTION" => "FormatOrderPriceChanged",
			"DATA_FIELDS" => array("PRICE", "OLD_PRICE", "CURRENCY"),
			"ENTITY" => 'ORDER',
		),
		"ORDER_1C_IMPORT" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrder1CImport",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'ORDER',
		),
		"ORDER_ADDED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderAdded",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'ORDER',
		),

		"ORDER_UPDATED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderUpdated",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'ORDER',
		),

		"ORDER_RESPONSIBLE_CHANGE" => array(
			"TRIGGER_FIELDS" => array("RESPONSIBLE_ID"),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array("RESPONSIBLE_ID", "RESPONSIBLE_NAME", "OLD_RESPONSIBLE_ID", "OLD_RESPONSIBLE_NAME"),
			"DATA_METHOD" => array(
				"RESPONSIBLE_NAME" => array('CSaleOrderChangeFormat::getOrderResponsibleName', array("RESPONSIBLE_ID")),
				"OLD_RESPONSIBLE_NAME" => array('CSaleOrderChangeFormat::getOrderResponsibleName', array("OLD_RESPONSIBLE_ID"))
			),
			"ENTITY" => 'ORDER',
		),

		"BASKET_ADDED" => array(
			"ENTITY" => "BASKET",
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatBasketAdded",
			"DATA_FIELDS" => array("PRODUCT_ID", "NAME", "QUANTITY", "SET_PARENT_ID"),
		),
		"BASKET_REMOVED" => array(
			"ENTITY" => "BASKET",
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatBasketRemoved",
			"DATA_FIELDS" => array("PRODUCT_ID", "NAME")
		),
		"BASKET_QUANTITY_CHANGED" => array(
			"ENTITY" => "BASKET",
			"TRIGGER_FIELDS" => array("QUANTITY"),
			"FUNCTION" => "FormatBasketQuantityChanged",
			"DATA_FIELDS" => array("PRODUCT_ID", "NAME", "QUANTITY")
		),
		"BASKET_PRICE_CHANGED" => array(
			"ENTITY" => "BASKET",
			"TRIGGER_FIELDS" => array("PRICE"),
			"FUNCTION" => "FormatBasketPriceChanged",
			"DATA_FIELDS" => array("PRODUCT_ID", "NAME", "PRICE", "CURRENCY")
		),


		"BASKET_SAVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'BASKET'
		),

		"ORDER_DELIVERY_REQUEST_SENT" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderDeliveryRequestSent",
			"DATA_FIELDS" => array()
		),

		"PAYMENT_ADDED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatPaymentAdded",
			"DATA_FIELDS" => array("PAY_SYSTEM_NAME", "SUM"),
			"ENTITY" => 'PAYMENT'
		),

		"PAYMENT_REMOVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatPaymentRemoved",
			"DATA_FIELDS" => array("PAY_SYSTEM_ID", "PAY_SYSTEM_NAME"),
			"ENTITY" => "PAYMENT",
		),

		"PAYMENT_PAID" => array(
			"TRIGGER_FIELDS" => array("PAID"),
			"FUNCTION" => "FormatPaymentPaid",
			"DATA_FIELDS" => array("PAID", "ID", "PAY_SYSTEM_NAME"),
			"ENTITY" => 'PAYMENT'
		),

		"PAYMENT_SYSTEM_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PAY_SYSTEM_ID"),
			"FUNCTION" => "FormatPaymentSystemChanged",
			"DATA_FIELDS" => array("PAY_SYSTEM_ID"),
			"ENTITY" => 'PAYMENT'
		),
		"PAYMENT_VOUCHER_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PAY_VOUCHER_NUM"),
			"FUNCTION" => "FormatPaymentVoucherChanged",
			"DATA_FIELDS" => array("PAY_VOUCHER_NUM", "PAY_VOUCHER_DATE"),
			"ENTITY" => 'PAYMENT'
		),

		"PAYMENT_PRICE_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PRICE"),
			"FUNCTION" => "FormatPaymentPriceChanged",
			"DATA_FIELDS" => array("PRICE", "CURRENCY"),
			"ENTITY" => 'PAYMENT'
		),

		"PAYMENT_SAVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'PAYMENT'
		),

		"SHIPMENT_ADDED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatShipmentAdded",
			"DATA_FIELDS" => array('DELIVERY_NAME'),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_REMOVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatShipmentRemoved",
			"DATA_FIELDS" => array("ID", "DELIVERY_NAME"),
			"ENTITY" => "SHIPMENT",
		),

		"SHIPMENT_ITEM_BASKET_ADDED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatShipmentItemBasketAdded",
			"DATA_FIELDS" => array("PRODUCT_ID", "NAME", "QUANTITY"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_ITEM_BASKET_REMOVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatShipmentItemBasketRemoved",
			"DATA_FIELDS" => array("PRODUCT_ID", "NAME", "QUANTITY"),
			"ENTITY" => 'SHIPMENT'
		),


		"SHIPMENT_DELIVERY_ALLOWED" => array(
			"TRIGGER_FIELDS" => array("ALLOW_DELIVERY"),
			"FUNCTION" => "FormatShipmentDeliveryAllowed",
			"DATA_FIELDS" => array("ALLOW_DELIVERY"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_SHIPPED" => array(
			"TRIGGER_FIELDS" => array("DEDUCTED"),
			"FUNCTION" => "FormatShipmentDeducted",
			"DATA_FIELDS" => array("DELIVERY_NAME", "DEDUCTED"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_MARKED" => array(
			"TRIGGER_FIELDS" => array("MARKED"),
			"FUNCTION" => "FormatShipmentMarked",
			"DATA_FIELDS" => array("REASON_MARKED", "MARKED"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_RESERVED" => array(
			"TRIGGER_FIELDS" => array("RESERVED"),
			"FUNCTION" => "FormatShipmentReserved",
			"DATA_FIELDS" => array("RESERVED"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_CANCELED" => array(
			"TRIGGER_FIELDS" => array("CANCELED"),
			"FUNCTION" => "FormatShipmentCanceled",
			"DATA_FIELDS"   => array("CANCELED", "REASON_CANCELED"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_STATUS_CHANGED" => array(
			"TRIGGER_FIELDS" => array("STATUS_ID"),
			"FUNCTION" => "FormatShipmentStatusChanged",
			"DATA_FIELDS" => array("STATUS_ID"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_DELIVERY_DOC_CHANGED" => array(
			"TRIGGER_FIELDS" => array("DELIVERY_DOC_NUM"),
			"FUNCTION" => "FormatShipmentDeliveryDocChanged",
			"DATA_FIELDS" => array("DELIVERY_DOC_NUM", "DELIVERY_DOC_DATE"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_TRACKING_NUMBER_CHANGED" => array(
			"TRIGGER_FIELDS" => array("TRACKING_NUMBER"),
			"FUNCTION" => "FormatShipmentTrackingNumberChanged",
			"DATA_FIELDS" => array("TRACKING_NUMBER"),
			"ENTITY" => 'SHIPMENT',
		),

		"SHIPMENT_PRICE_DELIVERY_CHANGED" => array(
			"TRIGGER_FIELDS" => array("PRICE_DELIVERY"),
			"FUNCTION" => "FormatShipmentPriceDeliveryChanged",
			"DATA_FIELDS" => array("PRICE_DELIVERY", "CURRENCY"),
			"ENTITY" => 'SHIPMENT',
		),

		"SHIPMENT_AMOUNT_CHANGED" => array(
			"TRIGGER_FIELDS" => array("QUANTITY"),
			"FUNCTION" => "FormatShipmentQuantityChanged",
			"DATA_FIELDS" => array("QUANTITY"),
			"ENTITY" => 'SHIPMENT_ITEM_STORE',
		),

		"SHIPMENT_QUANTITY_CHANGED" => array(
			"TRIGGER_FIELDS" => array("QUANTITY"),
			"FUNCTION" => "FormatShipmentQuantityChanged",
			"DATA_FIELDS" => array("QUANTITY", "ORDER_DELIVERY_ID", "NAME", "PRODUCT_ID"),
			"ENTITY" => 'SHIPMENT_ITEM',
		),


		"SHIPMENT_RESPONSIBLE_CHANGE" => array(
			"TRIGGER_FIELDS" => array("RESPONSIBLE_ID"),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array("RESPONSIBLE_ID", "RESPONSIBLE_NAME", "OLD_RESPONSIBLE_ID", "OLD_RESPONSIBLE_NAME"),
			"DATA_METHOD" => array(
				"RESPONSIBLE_NAME" => array('CSaleOrderChangeFormat::getOrderResponsibleName', array("RESPONSIBLE_ID")),
				"OLD_RESPONSIBLE_NAME" => array('CSaleOrderChangeFormat::getOrderResponsibleName', array("OLD_RESPONSIBLE_ID"))
			),
			"ENTITY" => 'SHIPMENT',
		),


		"SHIPMENT_SAVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'SHIPMENT'
		),

		"ORDER_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"PERSON_TYPE_ID",
				"CANCELED",
				"STATUS_ID",
				"MARKED",
				"PRICE",
				"SUM_PAID",
				"USER_ID",
				"EXTERNAL_ORDER",
			),
			"ENTITY" => "ORDER",
		),

		"BASKET_ITEM_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"QUANTITY",
				"PRICE",
				"PRODUCT_ID",
				"DISCOUNT_VALUE",
				"VAT_RATE",
				"OLD_QUANTITY",
				"OLD_PRICE",
				"OLD_PRODUCT_ID",
				"OLD_DISCOUNT_VALUE",
				"OLD_VAT_RATE"
			),
			"ENTITY" => "BASKET",
		),

		"BASKET_ITEM_DELETE_BUNDLE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "BASKET",
		),

		"BASKET_ITEM_DELETED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "BASKET",
		),

		"PAYMENT_ADD" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"PAID",
				"PAY_SYSTEM_ID",
				"PAY_SYSTEM_NAME",
				"SUM",
				"IS_RETURN",
				"ACCOUNT_NUMBER",
				"EXTERNAL_PAYMENT",
			),
			"ENTITY" => "PAYMENT",
		),

		"PAYMENT_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"PAID",
				"PAY_SYSTEM_ID",
				"PAY_SYSTEM_NAME",
				"SUM",
				"IS_RETURN",
				"ACCOUNT_NUMBER",
				"EXTERNAL_PAYMENT",
				"OLD_PAID",
				"OLD_PAY_SYSTEM_ID",
				"OLD_PAY_SYSTEM_NAME",
				"OLD_SUM",
				"OLD_IS_RETURN",
				"OLD_ACCOUNT_NUMBER",
				"OLD_EXTERNAL_PAYMENT",
			),
			"ENTITY" => "PAYMENT",
		),

		"SHIPMENT_ADD" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"DELIVERY_LOCATION",
				"PRICE_DELIVERY",
				"CUSTOM_PRICE_DELIVERY",
				"ALLOW_DELIVERY",
				"DEDUCTED",
				"RESERVED",
				"DELIVERY_NAME",
				"DELIVERY_ID",
				"CANCELED",
				"MARKED",
				"SYSTEM",
				"COMPANY_ID",
				"DISCOUNT_PRICE",
				"BASE_PRICE_DELIVERY",
				"EXTERNAL_DELIVERY",

				"OLD_DELIVERY_LOCATION",
				"OLD_PRICE_DELIVERY",
				"OLD_CUSTOM_PRICE_DELIVERY",
				"OLD_ALLOW_DELIVERY",
				"OLD_DEDUCTED",
				"OLD_RESERVED",
				"OLD_DELIVERY_NAME",
				"OLD_DELIVERY_ID",
				"OLD_CANCELED",
				"OLD_MARKED",
				"OLD_SYSTEM",
				"OLD_COMPANY_ID",
				"OLD_DISCOUNT_PRICE",
				"OLD_BASE_PRICE_DELIVERY",
				"OLD_EXTERNAL_DELIVERY",
				),
			"ENTITY" => "SHIPMENT",
		),

		"SHIPMENT_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array("DELIVERY_NAME", "DELIVERY_ID", "OLD_DELIVERY_NAME", "OLD_DELIVERY_ID"),
			"ENTITY" => "SHIPMENT",
		),

		"SHIPMENT_ITEM_ADD" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"QUANTITY",
				"RESERVED_QUANTITY",
				"BASKET_ID",
				"BASKET_ITEM_NAME",
				"BASKET_ITEM_PRODUCT_ID",
				"ORDER_DELIVERY_ID",
			),
			"ENTITY" => "SHIPMENT_ITEM",
		),

		"SHIPMENT_ITEM_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"BASKET_ID",
				"BASKET_ITEM_NAME",
				"BASKET_ITEM_PRODUCT_ID",
				"ORDER_DELIVERY_ID",
				"QUANTITY",
				"RESERVED_QUANTITY",
				"OLD_QUANTITY",
				"OLD_RESERVED_QUANTITY",
				),
			"ENTITY" => "SHIPMENT_ITEM",
		),

		"TAX_ADD" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "TAX",
		),

		"TAX_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "TAX",
		),

		"TAX_DELETED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "TAX",
		),

		"TAX_DUPLICATE_DELETED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "TAX",
		),


		"TAX_SAVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'TAX'
		),

		"PROPERTY_ADD" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array("NAME", "VALUE", "CODE"),
			"ENTITY" => "PROPERTY",
		),

		"PROPERTY_UPDATE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(
				"NAME",
				"VALUE",
				"CODE",
				"OLD_NAME",
				"OLD_VALUE",
				"OLD_CODE"
			),
			"ENTITY" => "PROPERTY",
		),

		"PROPERTY_REMOVE" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array("NAME", "CODE", "VALUE"),
			"ENTITY" => "PROPERTY",
		),

		"PROPERTY_SAVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatOrderChange",
			"DATA_FIELDS" => array(),
			"ENTITY" => 'PROPERTY'
		),

		"DISCOUNT_SAVED" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatLog",
			"DATA_FIELDS" => array(),
			"ENTITY" => "DISCOUNT",
		),

		"ORDER_UPDATE_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'ORDER'
		),

		"BASKET_ITEM_ADD_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'BASKET_ITEM'
		),

		"BASKET_ITEM_UPDATE_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'BASKET_ITEM'
		),

		"SHIPMENT_ADD_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_UPDATE_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT'
		),

		"SHIPMENT_ITEM_ADD_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT_ITEM'
		),

		"SHIPMENT_ITEM_UPDATE_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT_ITEM'
		),

		"SHIPMENT_ITEM_STORE_ADD_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT_ITEM_STORE'
		),

		"SHIPMENT_ITEM_STORE_UPDATE_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT_ITEM_STORE'
		),

		"SHIPMENT_ITEM_BASKET_ITEM_EMPTY_ERROR" => array(
			"TRIGGER_FIELDS" => array(),
			"FUNCTION" => "FormatErrorLog",
			"DATA_FIELDS" => array("ERROR"),
			"ENTITY" => 'SHIPMENT_ITEM'
		),

	);

	public static function FormatBasketAdded($data)
	{
		$info = GetMessage("SOC_BASKET_ADDED_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_BASKET_ADDED"),
			"INFO" => $info,
		);
	}

	public static function FormatBasketRemoved($data)
	{
		$info = GetMessage("SOC_BASKET_REMOVED_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_BASKET_REMOVED"),
			"INFO" => $info,
		);
	}

	public static function FormatOrderMarked($data)
	{
		if (is_array($data) && isset($data["REASON_MARKED"]) && strlen($data["REASON_MARKED"]) > 0)
		{
			$info = GetMessage("SOC_ORDER_MARKED_INFO");

			$info = static::doProcessLogMessage($info, $data);
		}
		else
			$info = GetMessage("SOC_ORDER_NOT_MARKED");

		return array(
			"NAME" => GetMessage("SOC_ORDER_MARKED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderReserved($data)
	{
		return array(
			"NAME" => GetMessage("SOC_ORDER_RESERVED"),
			"INFO" => (is_array($data) && $data["RESERVED"] == "Y") ? GetMessage("SOC_ORDER_RESERVED_Y") : GetMessage("SOC_ORDER_RESERVED_N")
		);
	}

	public static function FormatOrderDeducted($data)
	{
		if (is_array($data) && $data["DEDUCTED"] == "Y")
		{
			$info = GetMessage("SOC_ORDER_DEDUCTED_Y");
			$info = static::doProcessLogMessage($info, $data);
		}
		else
		{
			$info = GetMessage("SOC_ORDER_DEDUCTED_N");
			$info = static::doProcessLogMessage($info, $data);
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_DEDUCTED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderCanceled($data)
	{
		if (is_array($data) && $data["CANCELED"] == "Y")
		{
			$info = GetMessage("SOC_ORDER_CANCELED_Y");
			$info = static::doProcessLogMessage($info, $data);
		}
		else
		{
			$info = GetMessage("SOC_ORDER_CANCELED_N");
			$info = static::doProcessLogMessage($info, $data);
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_CANCELED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderCommented($data)
	{
		$info = GetMessage("SOC_ORDER_COMMENTED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_ORDER_COMMENTED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderStatusChanged($data)
	{
		$info = GetMessage("SOC_ORDER_STATUS_CHANGED_INFO");
		if (is_array($data))
		{
			foreach ($data as $param => $value)
			{
				if ($param == "STATUS_ID")
				{
					$res = CSaleStatus::GetByID($value);
					$value = "\"".$res["NAME"]."\"";
				}

				$info = str_replace("#".$param."#", $value, $info);
			}
		}
		else
		{
			$info = $data;
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_STATUS_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderDeliveryAllowed($data)
	{
		return array(
			"NAME" => GetMessage("SOC_ORDER_DELIVERY_ALLOWED"),
			"INFO" => (is_array($data) && $data["ALLOW_DELIVERY"] == "Y") ? GetMessage("SOC_ORDER_DELIVERY_ALLOWED_Y") : GetMessage("SOC_ORDER_DELIVERY_ALLOWED_N")
		);
	}

	public static function FormatOrderDeliveryDocChanged($data)
	{
		$info = GetMessage("SOC_ORDER_DELIVERY_DOC_CHANGED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_ORDER_DELIVERY_DOC_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderPaymentSystemChanged($data)
	{
		$info = GetMessage("SOC_ORDER_PAYMENT_SYSTEM_CHANGED_INFO");

		if (is_array($data))
		{
			foreach ($data as $param => $value)
			{
				if ($param == "PAY_SYSTEM_ID")
				{
					$res = CSalePaySystem::GetByID($value);
					$value = "\"".$res["NAME"]."\"";
				}

				$info = str_replace("#".$param."#", $value, $info);
			}
		}
		else
		{
			$info = $data;
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_PAYMENT_SYSTEM_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderDeliverySystemChanged($data)
	{
		$isOrderConverted = \Bitrix\Main\Config\Option::get("main", "~sale_converted_15", 'N');
		$info = GetMessage("SOC_ORDER_DELIVERY_SYSTEM_CHANGED_INFO");
		if (is_array($data))
		{
			foreach ($data as $param => $value)
			{
				if ($param == "DELIVERY_ID")
				{
					if (!array_key_exists('DELIVERY_NAME', $arData) && strval($arData['DELIVERY_NAME']) != '')
					{
						if (strpos($value, ":") !== false)
						{
							$arId = explode(":", $value);
							$dbDelivery = CSaleDeliveryHandler::GetBySID($arId[0]);
							$arDelivery = $dbDelivery->Fetch();

							$value =  "\"".htmlspecialcharsEx($arDelivery["NAME"])."\"";
						}
						elseif (intval($value) > 0)
						{
							if ($isOrderConverted == "Y")
							{
								$arDelivery = \Bitrix\Sale\Delivery\Services\Manager::getById($value);
							}
							else
							{
								$arDelivery = CSaleDelivery::GetByID($value);
							}
							$value = "\"".$arDelivery["NAME"]."\"";
						}
					}
					else
					{
						$value = "\"".$arData['DELIVERY_NAME']."\"";
					}
				}
				elseif($param == "DELIVERY_NAME")
				{
					$value = "\"".$value."\"";
				}
				else
				{
					continue;
				}

				$info = str_replace("#".$param."#", $value, $info);
			}
		}
		else
		{
			$info = $data;
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_DELIVERY_SYSTEM_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderPersonTypeChanged($data)
	{
		$info = GetMessage("SOC_ORDER_PERSON_TYPE_CHANGED_INFO");

		if (is_array($data))
		{
			foreach ($data as $param => $value)
			{
				if ($param == "PERSON_TYPE_ID")
				{
					$res = CSalePersonType::GetByID($value);
					$value = "\"".$res["NAME"]."\"";
				}

				$info = str_replace("#".$param."#", $value, $info);
			}
		}
		else
		{
			$info = $data;
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_PERSON_TYPE_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderPaymentVoucherChanged($data)
	{
		$info = GetMessage("SOC_ORDER_PAYMENT_VOUCHER_CHANGED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_ORDER_PAYMENT_VOUCHER_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderPayed($data)
	{
		return array(
			"NAME" => GetMessage("SOC_ORDER_PAYED"),
			"INFO" => (is_array($data) && $data["PAYED"] == "Y") ? GetMessage("SOC_ORDER_PAYED_Y") : GetMessage("SOC_ORDER_PAYED_N")
		);
	}

	public static function FormatOrderTrackingNumberChanged($data)
	{
		$info = GetMessage("SOC_ORDER_TRACKING_NUMBER_CHANGED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_ORDER_TRACKING_NUMBER_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderUserDescriptionChanged($data)
	{
		$info = GetMessage("SOC_ORDER_USER_DESCRIPTION_CHANGED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_ORDER_USER_DESCRIPTION_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderPriceDeliveryChanged($data)
	{
		if (is_array($data))
		{
			$info = GetMessage("SOC_ORDER_PRICE_DELIVERY_CHANGED_INFO", array("#AMOUNT#" => CCurrencyLang::CurrencyFormat($data["PRICE_DELIVERY"], $data["CURRENCY"], true)));
		}
		else
		{
			$info = GetMessage("SOC_ORDER_PRICE_DELIVERY_CHANGED_INFO");
		}


		return array(
			"NAME" => GetMessage("SOC_ORDER_PRICE_DELIVERY_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrderPriceChanged($data)
	{
		if (is_array($data))
		{
			$info = GetMessage("SOC_ORDER_PRICE_CHANGED_INFO",
						   array(
							   "#AMOUNT#" => CCurrencyLang::CurrencyFormat($data["PRICE"], $data["CURRENCY"], true),
							   "#OLD_AMOUNT#" => CCurrencyLang::CurrencyFormat($data["OLD_PRICE"], $data["CURRENCY"], true),
						   ));
		}
		else
		{
			$info = GetMessage("SOC_ORDER_PRICE_CHANGED_INFO");
		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_PRICE_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatBasketQuantityChanged($data)
	{
		$info = GetMessage("SOC_BASKET_QUANTITY_CHANGED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_BASKET_QUANTITY_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatOrder1CImport($data)
	{
		return array(
			"NAME" => GetMessage("SOC_ORDER_1C_IMPORT"),
			"INFO" => "",
		);
	}

	public static function FormatOrderAdded($data)
	{
		return array(
			"NAME" => GetMessage("SOC_ORDER_ADDED"),
			"INFO" => "",
		);
	}

	public static function FormatOrderUpdated($data)
	{
		return array(
			"NAME" => GetMessage("SOC_ORDER_UPDATED"),
			"INFO" => "",
		);
	}

	public static function FormatBasketPriceChanged($data)
	{
		$info = GetMessage("SOC_BASKET_PRICE_CHANGED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		if (is_array($data))
		{
			$info = str_replace("#AMOUNT#", CCurrencyLang::CurrencyFormat($data["PRICE"], $data["CURRENCY"], true), $info);
		}

		return array(
			"NAME" => GetMessage("SOC_BASKET_PRICE_CHANGED"),
			"INFO" => $info
		);
	}
	public static function FormatOrderDeliveryRequestSent($data)
	{
		if(is_array($data) && $data["RESULT"] == "OK")
		{
			$reqDescription = GetMessage("SOC_ORDER_DELIVERY_REQUEST_SENT_SUCCESS");
		}
		else
		{
			$reqDescription = GetMessage("SOC_ORDER_DELIVERY_REQUEST_SENT_ERROR");

			if (is_array($data))
			{
				if(isset($data["TEXT"]))
					$reqDescription .=": ".$data["TEXT"].".";

				if(isset($data["DATA"]))
					$reqDescription .= GetMessage("SOC_ORDER_DELIVERY_REQUEST_SENT_ADD_INFO").": ".serialize($arData["DATA"]);
			}

		}

		return array(
			"NAME" => GetMessage("SOC_ORDER_DELIVERY_REQUEST_SENT"),
			"INFO" => $reqDescription,
		);
	}


	public static function FormatPaymentPaid($data)
	{
		$info = (is_array($data) && $data["PAID"] == "Y") ? GetMessage("SOC_PAYMENT_PAID_Y") : GetMessage("SOC_PAYMENT_PAID_N");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_PAYMENT_PAID"),
			"INFO" => $info
		);
	}

	public static function FormatShipmentDeliveryAllowed($data)
	{
		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_ALLOWED"),
			"INFO" => (is_array($data) && $data["ALLOW_DELIVERY"] == "Y") ? GetMessage("SOC_SHIPMENT_ALLOWED_Y") : GetMessage("SOC_SHIPMENT_ALLOWED_N")
		);
	}

	public static function FormatShipmentAdded($data)
	{
		$info = GetMessage("SOC_SHIPMENT_CREATE_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_CREATE"),
			"INFO" => $info,
		);
	}

	public static function FormatShipmentMarked($data)
	{
		$info = "";
		if (is_array($data) && isset($data["REASON_MARKED"]) && strlen($data["REASON_MARKED"]) > 0)
		{
			$info = GetMessage("SOC_SHIPMENT_MARKED_INFO");
			$info = static::doProcessLogMessage($info, $data);
		}

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_MARKED"),
			"INFO" => $info
		);
	}


	public static function FormatShipmentItemBasketAdded($data)
	{
		$info = GetMessage("SOC_SHIPMENT_ITEM_BASKET_ADDED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_ITEM_BASKET_ADDED"),
			"INFO" => $info,
		);
	}

	public static function FormatShipmentItemBasketRemoved($data)
	{
		$info = GetMessage("SOC_SHIPMENT_ITEM_BASKET_REMOVED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_ITEM_BASKET_REMOVED"),
			"INFO" => $info,
		);
	}

	public static function FormatShipmentRemoved($data)
	{
		$info = GetMessage("SOC_SHIPMENT_REMOVED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_REMOVED"),
			"INFO" => $info,
		);
	}


	public static function FormatShipmentCanceled($data)
	{
		if (is_array($data) && $data["CANCELED"] == "Y")
		{
			$info = GetMessage("SOC_SHIPMENT_CANCELED_Y");
			$info = static::doProcessLogMessage($info, $data);
		}
		else
		{
			$info = GetMessage("SOC_SHIPMENT_CANCELED_N");
			$info = static::doProcessLogMessage($info, $data);
		}

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_CANCELED"),
			"INFO" => $info
		);
	}

	public static function FormatPaymentAdded($data)
	{
		$info = GetMessage("SOC_PAYMENT_CREATE_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_PAYMENT_CREATE"),
			"INFO" => $info,
		);
	}

	public static function FormatPaymentRemoved($data)
	{
		$info = GetMessage("SOC_PAYMENT_REMOVED_INFO");
		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_PAYMENT_REMOVED"),
			"INFO" => $info,
		);
	}

	public static function FormatShipmentDeducted($data)
	{
		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_DEDUCTED"),
			"INFO" => ($data["DEDUCTED"] == "Y") ? GetMessage("SOC_SHIPMENT_DEDUCTED_Y") : GetMessage("SOC_SHIPMENT_DEDUCTED_N")
		);
	}

	public static function FormatShipmentReserved($data)
	{
		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_RESERVED"),
			"INFO" => ($data["RESERVED"] == "Y") ? GetMessage("SOC_SHIPMENT_RESERVED_Y") : GetMessage("SOC_SHIPMENT_RESERVED_N")
		);
	}

	public static function FormatPaymentSystemChanged($data)
	{
		$info = GetMessage("SOC_PAYMENT_PAY_SYSTEM_CHANGE_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_PAYMENT_PAY_SYSTEM_CHANGE"),
			"INFO" => $info
		);
	}

	public static function FormatShipmentPriceDeliveryChanged($data)
	{
		$info = GetMessage("SOC_SHIPMENT_PRICE_DELIVERY_CHANGED_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_PRICE_DELIVERY_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatPaymentVoucherChanged($arData)
	{
		return self::FormatOrderPaymentVoucherChanged($arData);
	}

	public static function FormatPaymentPriceChanged($data)
	{
		$info = GetMessage("SOC_SHIPMENT_PRICE_DELIVERY_CHANGED_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_PRICE_DELIVERY_CHANGED"),
			"INFO" => $info
		);
	}

	public static function FormatShipmentTrackingNumberChanged($arData)
	{
		return self::FormatOrderTrackingNumberChanged($arData);
	}

	public static function FormatShipmentDeliveryDocChanged($arData)
	{
		return self::FormatOrderDeliveryDocChanged($arData);
	}

	public static function FormatShipmentStatusChanged($arData)
	{
		$info = GetMessage("SOC_SHIPMENT_STATUS_CHANGE_INFO");

		foreach ($arData as $param => $value)
		{
			$status = \Bitrix\Sale\Helpers\Admin\Blocks\OrderShipmentStatus::getShipmentStatusList($arData['STATUS_ID']);
			$info = str_replace("#".$param."#", $status[$value], $info);
		}

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_STATUS_CHANGE"),
			"INFO" => $info
		);
	}

	public static function FormatShipmentQuantityChanged($data)
	{
		$info = GetMessage("SOC_SHIPMENT_ITEM_QUANTITY_CHANGE_INFO");

		$info = static::doProcessLogMessage($info, $data);

		return array(
			"NAME" => GetMessage("SOC_SHIPMENT_ITEM_QUANTITY_CHANGE"),
			"INFO" => $info
		);
	}

	/**
	 * @param $data
	 * @param $type
	 * @param null $entity
	 *
	 * @return array
	 */
	public static function FormatLog($data, $type, $entity = null)
	{
		$info = "";
		if (!empty($data))
		{
			$info = GetMessage("SOC_".ToUpper($type)."_INFO");

			if (is_array($data))
			{
				if (strval($info) != "")
				{
					foreach ($data as $param => $value)
					{
						$info = str_replace("#".$param."#", $value, $info);

						if (array_key_exists("OLD_".$param, $data))
						{
							$info = str_replace("#OLD_".$param."#", $data["OLD_".$param], $info);
						}
					}
				}
				else
				{
					foreach ($data as $param => $value)
					{
						if (strpos($param, "OLD_") === 0)
							continue;

						$info .=(strval($info) != "" ? "; " : ""). $param.": ".$value;

						if (array_key_exists("OLD_".$param, $data))
						{
							$info.= " OLD_".$param.": ".$data["OLD_".$param];
						}
					}
				}
			}
			else
			{
				$info = $data;
			}
		}

		$title = GetMessage("SOC_".ToUpper($type)."_TITLE");

		if (strval($title) == "")
			$title = GetMessage("SOC_".ToUpper($entity)."_TITLE");

		return array(
			"NAME" => $title,
			"INFO" => $info
		);
	}
	/**
	 * @param $data
	 * @param $type
	 * @param null $entity
	 *
	 * @return array
	 */
	public static function FormatOrderChange($data, $type, $entity = null)
	{
		$info = "";
		if (!empty($data))
		{
			$info = GetMessage("SOC_".ToUpper($type)."_INFO");

			if (is_array($data))
			{
				if (strval($info) != "")
				{
					foreach ($data as $param => $value)
					{
						$info = str_replace("#".$param."#", $value, $info);

						if (array_key_exists("OLD_".$param, $data))
						{
							$info = str_replace("#OLD_".$param."#", $data["OLD_".$param], $info);
						}
						else
						{
							$info = str_replace("#OLD_".$param."#", "", $info);
						}
					}
				}
				else
				{
					foreach ($data as $param => $value)
					{
						if (strpos($param, "OLD_") === 0)
							continue;

						$info .=(strval($info) != "" ? "; " : ""). $param.": ".$value;

						if (array_key_exists("OLD_".$param, $data))
						{
							$info.= " OLD_".$param.": ".$data["OLD_".$param];
						}
					}
				}
			}
			else
			{
				$info = $data;
			}
		}

		$title = GetMessage("SOC_".ToUpper($type)."_TITLE");

		if (strval($title) == "")
			$title = GetMessage("SOC_".ToUpper($entity)."_TITLE");

		return array(
			"NAME" => $title,
			"INFO" => $info
		);
	}

	/**
	 * @param $data
	 * @param $type
	 * @param null $entity
	 *
	 * @return array
	 */
	public static function FormatErrorLog($data, $type, $entity = null)
	{
		$info = "";
		if (!empty($data))
		{
			$info = GetMessage("SOC_".ToUpper($type)."_INFO");

			if (is_array($data))
			{

				foreach ($data as $param => $value)
				{
					if (is_array($value) &&  !empty($value))
					{
						$errorList = $value;
						$value = "";
						foreach ($errorList as $errorMsg)
						{
							$value .= (strval($value) != "" ? "\n" : ""). $errorMsg;
						}
					}

					$info = str_replace("#".$param."#", $value, $info);
				}
			}
			else
			{
				$info = $data;
			}
		}

		$title = GetMessage("SOC_".ToUpper($type)."_TITLE");

		if (strval($title) == "")
			$title = GetMessage("SOC_".ToUpper($entity)."_TITLE");

		return array(
			"NAME" => $title,
			"INFO" => $info
		);
	}

	/**
	 * @internal
	 * @param $id
	 *
	 * @return bool|mixed|string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getOrderResponsibleName($id = null)
	{
		if (intval($id) < 0)
			return false;
		
		static $orderResponsibleList = array();

		if (isset($orderResponsibleList[$id]))
		{
			return $orderResponsibleList[$id];
		}
		$userIterator = \Bitrix\Main\UserTable::getList(array(
			'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'EMAIL'),
			'filter' => array('=ID' => intval($id))
		));

		$userName = false;
		if ($userData = $userIterator->fetch())
		{
			$userName = \CUser::FormatName(\CSite::GetNameFormat(true), $userData, true);
			$orderResponsibleList[$id] = $userName;
		}

		return $userName;
	}

	/**
	 * @param $text
	 * @param $data
	 *
	 * @return mixed
	 */
	private static function doProcessLogMessage($text, $data)
	{
		if (is_array($data))
		{
			foreach ($data as $param => $value)
			{
				$text = str_replace("#".$param."#", $value, $text);
			}
		}

		return $text;
	}
}
