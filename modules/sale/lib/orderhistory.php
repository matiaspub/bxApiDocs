<?php


namespace Bitrix\Sale;


use Bitrix\Main;
use Bitrix\Sale\Internals\Entity;

class OrderHistory
{
	protected static $pool = array();
	protected static $poolFields = array();

	const SALE_ORDER_HISTORY_UPDATE = 'UPDATE';

	const SALE_ORDER_HISTORY_RECORD_TYPE_ACTION = 'ACTION';
	const SALE_ORDER_HISTORY_RECORD_TYPE_FIELD = 'FIELD';
	const SALE_ORDER_HISTORY_RECORD_TYPE_DEBUG = 'DEBUG';

	const FIELD_TYPE_NAME = 'NAME';
	const FIELD_TYPE_TYPE = 'TYPE';

	const SALE_ORDER_HISTORY_LOG_LEVEL_0 = 0;
	const SALE_ORDER_HISTORY_LOG_LEVEL_1 = 1;

	protected function __construct()
	{

	}

	/**
	 * @param string $entityName
	 * @param int $orderId
	 * @param string $field
	 * @param null|string $oldValue
	 * @param null|string $value
	 * @param int $id
	 * @param $entity
	 * @param array $fields
	 */
	public static function addField($entityName, $orderId, $field, $oldValue = null, $value = null, $id = null, $entity = null, array $fields = array())
	{
		if ($field == "ID")
			return;

		if ($value !== null && static::isDate($value))
		{
			$value = $value->toString();
		}

		if ($oldValue !== null && static::isDate($oldValue))
		{
			$oldValue = $oldValue->toString();
		}

		if (!empty($fields))
		{
			foreach($fields as $fieldName => $fieldValue)
			{
				if (static::isDate($fieldValue))
				{
					$fields[$fieldName] = $fieldValue->toString();
				}
			}
		}

		static::$pool[$entityName][$orderId][$id][$field][] = array(
			'RECORD_TYPE' => static::SALE_ORDER_HISTORY_RECORD_TYPE_FIELD,
			'ENTITY_NAME' => $entityName,
			'ENTITY' => $entity,
			'ORDER_ID' => $orderId,
			'ID' => $id,
			'NAME' => $field,
			'OLD_VALUE' => $oldValue,
			'VALUE' => $value,
			'DATA' => $fields
		);
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null $entity
	 * @param array $fields
	 */
	public static function addAction($entityName, $orderId, $type, $id = null, $entity = null, array $fields = array())
	{
		static::$pool[$entityName][$orderId][$id][$type][] = array(
			'RECORD_TYPE' => static::SALE_ORDER_HISTORY_RECORD_TYPE_ACTION,
			'ENTITY_NAME' => $entityName,
			'ENTITY' => $entity,
			'ID' => $id,
			'TYPE' => $type,
			'DATA' => $fields
		);
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param null|int $id
	 * @return bool
	 */
	public static function collectEntityFields($entityName, $orderId, $id = null)
	{
		if (!$poolEntity = static::getPoolByEntity($entityName, $orderId))
		{
			return false;
		}

		if ($id !== null)
		{
			$found = false;
			foreach ($poolEntity as $entityId => $fieldValue)
			{
				if ($entityId == $id)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
				return false;
		}

		foreach ($poolEntity as $entityId => $fieldValue)
		{
			if ($id !== null && $entityId != $id)
				continue;

			$entity = null;

			$dataFields = array();
			$oldFields = array();
			$fields = array();

			foreach ($fieldValue as $dataList)
			{
				foreach ($dataList as $key => $data)
				{
					if ($data['RECORD_TYPE'] == static::SALE_ORDER_HISTORY_RECORD_TYPE_ACTION
						|| $data['RECORD_TYPE'] == static::SALE_ORDER_HISTORY_RECORD_TYPE_DEBUG)
					{
						static::addRecord($entityName, $orderId, $data['TYPE'], $data['ID'], $data['ENTITY'], $data['DATA']);
						unset(static::$pool[$entityName][$orderId][$data['ID']][$data['TYPE']][$key]);

						if (empty(static::$pool[$entityName][$orderId][$data['ID']][$data['TYPE']]))
							unset(static::$pool[$entityName][$orderId][$data['ID']][$data['TYPE']]);

						continue;
					}

					$value = $data['VALUE'];
					$oldValue = $data['OLD_VALUE'];

					if (static::isDate($value))
						$value = static::convertDateField($value);

					if (static::isDate($oldValue))
						$oldValue = static::convertDateField($oldValue);

					$oldFields[$data['NAME']] = $oldValue;
					$fields[$data['NAME']] = $value;

					if (!empty($data['DATA']) && is_array($data['DATA']))
					{
						$dataFields = array_merge($dataFields, $data['DATA']);
					}

					$dataType = static::FIELD_TYPE_TYPE;
					if (isset($data['RECORD_TYPE']) == static::SALE_ORDER_HISTORY_RECORD_TYPE_FIELD)
					{
						$dataType = static::FIELD_TYPE_NAME;
					}

					if (isset($data[$dataType]))
					{
						unset(static::$pool[$entityName][$orderId][$data['ID']][$data[$dataType]][$key]);

						if (empty(static::$pool[$entityName][$orderId][$data['ID']][$data[$dataType]]))
							unset(static::$pool[$entityName][$orderId][$data['ID']][$data[$dataType]]);
					}

					if ($entity === null && array_key_exists('ENTITY', $data))
					{
						$entity = $data['ENTITY'];
					}

				}

			}

			\CSaleOrderChange::AddRecordsByFields($orderId, $oldFields, $fields, array(), $entityName, $id, $entity, $dataFields);

			if (empty(static::$pool[$entityName][$orderId][$entityId]))
				unset(static::$pool[$entityName][$orderId][$entityId]);
		}



		if (empty(static::$pool[$entityName][$orderId]))
			unset(static::$pool[$entityName][$orderId]);

		if (empty(static::$pool[$entityName]))
			unset(static::$pool[$entityName]);

	}

	/**
	 * @param $entity
	 * @param $orderId
	 * @return bool|array
	 */
	protected static function getPoolByEntity($entity, $orderId)
	{
		if (empty(static::$pool[$entity])
			|| empty(static::$pool[$entity][$orderId])
			|| !is_array(static::$pool[$entity][$orderId]))
		{
			return false;
		}

		return static::$pool[$entity][$orderId];
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null|Entity $entity
	 * @param array $data
	 */
	protected static function addRecord($entityName, $orderId, $type, $id = null, $entity = null, array $data = array())
	{
		if ($entity !== null
			&& ($operationType = static::getOperationType($entityName, $type))
			&& (!empty($operationType["DATA_FIELDS"]) && is_array($operationType["DATA_FIELDS"])))
		{
			foreach ($operationType["DATA_FIELDS"] as $fieldName)
			{
				if (!array_key_exists($fieldName, $data) && ($value = $entity->getField($fieldName)))
				{
					$data[$fieldName] = TruncateText($value, 128);
				}
			}
		}

		\CSaleOrderChange::AddRecord($orderId, $type, $data, $entityName, $id);
	}

	/**
	 * @param $entityName
	 * @param $type
	 *
	 * @return bool
	 */
	protected static function getOperationType($entityName, $type)
	{
		if (!empty(\CSaleOrderChangeFormat::$operationTypes) && !empty(\CSaleOrderChangeFormat::$operationTypes[$type]))
		{
			if (!empty(\CSaleOrderChangeFormat::$operationTypes[$type]['ENTITY'])
				&& $entityName == \CSaleOrderChangeFormat::$operationTypes[$type]['ENTITY'])
			{
				return \CSaleOrderChangeFormat::$operationTypes[$type];
			}
		}

		return false;
	}

	/**
	 * @param $value
	 * @return bool
	 */
	private static function isDate($value)
	{
		return ($value instanceof Main\Type\DateTime) || ($value instanceof Main\Type\Date);
	}

	/**
	 * @param $value
	 * @return string
	 */
	private static function convertDateField($value)
	{
		if (($value instanceof Main\Type\DateTime)
			|| ($value instanceof Main\Type\Date))
		{
			return $value->toString();
		}

		return $value;
	}

	/**
	 * @param $id
	 *
	 * @return bool|\CDBResult
	 */
	public static function deleteByOrderId($id)
	{
		if (intval($id) <= 0)
			return false;

		return \CSaleOrderChange::deleteByOrderId($id);
	}

	/**
	 * @param $entityName
	 * @param $orderId
	 * @param $type
	 * @param null $id
	 * @param null $entity
	 * @param array $fields
	 * @param null $level
	 */
	public static function addLog($entityName, $orderId, $type, $id = null, $entity = null, array $fields = array(), $level = null)
	{
		if ($level === null)
		{
			$level = static::SALE_ORDER_HISTORY_LOG_LEVEL_0;
		}

		if (!static::checkLogLevel($level))
			return;

		if (!empty($fields))
		{
			foreach($fields as $fieldName => $fieldValue)
			{
				if (static::isDate($fieldValue))
				{
					$fields[$fieldName] = $fieldValue->toString();
				}
			}
		}

		static::$pool[$entityName][$orderId][$id][$type][] = array(
			'RECORD_TYPE' => static::SALE_ORDER_HISTORY_RECORD_TYPE_DEBUG,
			'ENTITY_NAME' => $entityName,
			'ENTITY' => $entity,
			'ID' => $id,
			'TYPE' => $type,
			'DATA' => $fields,
			'LEVEL' => $level
		);
	}

	/**
	 * @param $level
	 *
	 * @return bool
	 * @throws Main\ArgumentNullException
	 */
	public static function checkLogLevel($level)
	{
		$orderHistoryLogLevel = Main\Config\Option::get('sale', 'order_history_log_level', static::SALE_ORDER_HISTORY_LOG_LEVEL_0);

		if ($level > $orderHistoryLogLevel)
			return false;

		return true;
	}

	/**
	 * @return array
	 */
	public static function getManagerLogItems()
	{
		return array(
			"ORDER_ADDED",
			"ORDER_DEDUCTED",
			"ORDER_MARKED",
			"ORDER_RESERVED",
			"ORDER_CANCELED",
			"ORDER_COMMENTED",
			"ORDER_STATUS_CHANGED",
			"ORDER_DELIVERY_ALLOWED",
			"ORDER_DELIVERY_DOC_CHANGED",
			"ORDER_PAYMENT_SYSTEM_CHANGED",
			"ORDER_PAYMENT_VOUCHER_CHANGED",
			"ORDER_DELIVERY_SYSTEM_CHANGED",
			"ORDER_PERSON_TYPE_CHANGED",
			"ORDER_PAYED",
			"ORDER_TRACKING_NUMBER_CHANGED",
			"ORDER_USER_DESCRIPTION_CHANGED",
			"ORDER_PRICE_DELIVERY_CHANGED",
			"ORDER_PRICE_CHANGED",
			"ORDER_RESPONSIBLE_CHANGE",

			"BASKET_ADDED",
			"BASKET_REMOVED",
			"BASKET_QUANTITY_CHANGED",
			"BASKET_PRICE_CHANGED",
			"PAYMENT_ADDED",
			"PAYMENT_REMOVED",
			"PAYMENT_PAID",
			"PAYMENT_SYSTEM_CHANGED",
			"PAYMENT_VOUCHER_CHANGED",
			"PAYMENT_PRICE_CHANGED",

			"SHIPMENT_ADDED",
			"SHIPMENT_REMOVED",
			"SHIPMENT_ITEM_BASKET_ADDED",
			"SHIPMENT_ITEM_BASKET_REMOVED",
			"SHIPMENT_DELIVERY_ALLOWED",
			"SHIPMENT_SHIPPED",
			"SHIPMENT_MARKED",
			"SHIPMENT_RESERVED",
			"SHIPMENT_CANCELED",
			"SHIPMENT_STATUS_CHANGED",
			"SHIPMENT_DELIVERY_DOC_CHANGED",
			"SHIPMENT_TRACKING_NUMBER_CHANGED",
			"SHIPMENT_PRICE_DELIVERY_CHANGED",
			"SHIPMENT_AMOUNT_CHANGED",
			"SHIPMENT_QUANTITY_CHANGED",
			"SHIPMENT_RESPONSIBLE_CHANGE",

			"ORDER_UPDATE_ERROR",
			"BASKET_ITEM_ADD_ERROR",
			"BASKET_ITEM_UPDATE_ERROR",
			"SHIPMENT_ADD_ERROR",
			"SHIPMENT_UPDATE_ERROR",
			"SHIPMENT_ITEM_ADD_ERROR",
			"SHIPMENT_ITEM_UPDATE_ERROR",
			"SHIPMENT_ITEM_STORE_ADD_ERROR",
			"SHIPMENT_ITEM_STORE_UPDATE_ERROR",
			"SHIPMENT_ITEM_BASKET_ITEM_EMPTY_ERROR",
			
		);
	}

}