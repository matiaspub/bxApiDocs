<?php


namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ShipmentItemStoreCollection
	extends Internals\EntityCollection
{
	/** @var  ShipmentItem */
	private $shipmentItem;

	private static $errors = array();

	/**
	 * @return ShipmentItem
	 */
	protected function getEntityParent()
	{
		return $this->getShipmentItem();
	}

	/**
	 * @param ShipmentItem $shipmentItem
	 * @return ShipmentItemCollection
	 */
	public static function load(ShipmentItem $shipmentItem)
	{
		/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
		$shipmentItemStoreCollection = new static();
		$shipmentItemStoreCollection->shipmentItem = $shipmentItem;

		if ($shipmentItem->getId() > 0)
		{
			$basketItem = $shipmentItem->getBasketItem();
			$shipmentItemStoreList = ShipmentItemStore::loadForShipmentItem($shipmentItem->getId());
			/** @var ShipmentItemStore $shipmentItemStoreDat */
			foreach ($shipmentItemStoreList as $shipmentItemStoreDat)
			{
				$shipmentItemStore = ShipmentItemStore::create($shipmentItemStoreCollection, $basketItem);

				$fields = $shipmentItemStoreDat->getFieldValues();

				$shipmentItemStore->initFields($fields);
				$shipmentItemStoreCollection->addItem($shipmentItemStore);

			}
		}

		return $shipmentItemStoreCollection;
	}

	/**
	 * @param BasketItem $basketItem
	 * @return static
	 * @throws \Exception
	 */
	public function createItem(BasketItem $basketItem)
	{
		/** @var ShipmentItemStore $item */
		$shipmentItemStore = ShipmentItemStore::create($this, $basketItem);

		$this->addItem($shipmentItemStore);

		return $shipmentItemStore;
	}

	/**
	 * @param ShipmentItemStore $shipmentItemStore
	 * @return bool|void
	 */
	static public function addItem(ShipmentItemStore $shipmentItemStore)
	{
		parent::addItem($shipmentItemStore);
	}

	/**
	 * @internal
	 *
	 * @param $index
	 * @return bool
	 */
	static public function deleteItem($index)
	{
		$oldItem = parent::deleteItem($index);

	}

	/**
	 * @param $basketCode
	 * @return float|int
	 */
	public function getQuantityByBasketCode($basketCode)
	{
		$quantity = 0;

		/** @var ShipmentItemStore $item */
		foreach ($this->collection as $item)
		{
			if ($item->getBasketCode() == $basketCode)
			{
				$quantity += $item->getQuantity();
			}
		}

		return $quantity;
	}


	/**
	 * @return ShipmentItem
	 */
	public function getShipmentItem()
	{
		return $this->shipmentItem;
	}

	/**
	 * @param ShipmentItemStore $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return bool
	 */
	public function onItemModify(ShipmentItemStore $item, $name = null, $oldValue = null, $value = null)
	{
//		$shipmentItem = $this->getShipmentItem();

		if ($name == "QUANTITY")
		{
			return $this->checkAvailableQuantity($item);
		}

		return new Result();
	}

	/**
	 * @param ShipmentItemStore $item
	 * @return Result
	 * @throws \Exception
	 */
	public function checkAvailableQuantity(ShipmentItemStore $item)
	{
		$result = new Result();
		$shipmentItem = $this->getShipmentItem();

		$basketItem = $shipmentItem->getBasketItem();

		$itemStoreQuantity = floatval($this->getQuantityByBasketCode($shipmentItem->getBasketCode()));

		if (($shipmentItem->getQuantity() !== null)
			&& (( floatval($item->getQuantity()) > floatval($shipmentItem->getQuantity()))
			|| ( $itemStoreQuantity > floatval($shipmentItem->getQuantity()))))
		{

			if (isset(static::$errors[$basketItem->getBasketCode()][$item->getField('ORDER_DELIVERY_BASKET_ID')]['STORE_QUANTITY_LARGER_ALLOWED']))
			{
				static::$errors[$basketItem->getBasketCode()][$item->getField('ORDER_DELIVERY_BASKET_ID')]['STORE_QUANTITY_LARGER_ALLOWED'] += $item->getQuantity();
			}
			else
			{
				$result->addError(new ResultError(
										Loc::getMessage('SALE_SHIPMENT_ITEM_STORE_QUANTITY_LARGER_ALLOWED', array(
										  '#PRODUCT_NAME#' => $basketItem->getField('NAME'),
										)),
										'SALE_SHIPMENT_ITEM_STORE_QUANTITY_LARGER_ALLOWED')
				);

				static::$errors[$basketItem->getBasketCode()][$item->getField('ORDER_DELIVERY_BASKET_ID')]['STORE_QUANTITY_LARGER_ALLOWED'] = $item->getQuantity();
			}

		}

		return $result;
	}


	/**
	 * @return Main\Entity\Result
	 */
	public function save()
	{
		$result = new Main\Entity\Result();

		$oldBarcodeList = array();

		$itemsFromDb = array();
		if ($this->getShipmentItem() && $this->getShipmentItem()->getId() > 0)
		{
			$itemsFromDbList = Internals\ShipmentItemStoreTable::getList(
				array(
					"filter" => array("ORDER_DELIVERY_BASKET_ID" => $this->getShipmentItem()->getId()),
					"select" => array("*")
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
				$itemsFromDb[$itemsFromDbItem["ID"]] = true;
		}

		/** @var ShipmentItemStore $shipmentItemStore */
		foreach ($this->collection as $shipmentItemStore)
		{
			$r = $shipmentItemStore->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$shipmentItemStore->getId()]))
				unset($itemsFromDb[$shipmentItemStore->getId()]);
		}

		foreach ($itemsFromDb as $k => $v)
			Internals\ShipmentItemStoreTable::delete($k);

		return $result;
	}


	/**
	 * @param array $values
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws \Exception
	 */
	public function setBarcodeQuantityFromArray(array $values)
	{
		$result = new Result();
		$requestBarcodeList = static::getBarcodeListFromArray($values);

		$plusList = array();
		$oldQuantityList = $this->getAllBarcodeList();

		foreach ($requestBarcodeList as $storeId => $barcodeDat)
		{
			foreach ($barcodeDat as $barcodeValue => $barcode)
			{
				if (isset($oldQuantityList[$storeId][$barcodeValue])
					&& $oldQuantityList[$storeId][$barcodeValue]['ID'] == $barcode['ID'])
				{
					$oldBarcode = $oldQuantityList[$storeId][$barcodeValue];
					if ($barcode['QUANTITY'] == $oldBarcode['QUANTITY'])
					{
						continue;
					}
					elseif ($barcode['QUANTITY'] < $oldBarcode['QUANTITY'])
					{
						/** @var ShipmentItemStore $item */
						$item = $this->getItemById($oldBarcode['ID']);
						if ($item)
							$item->setField('QUANTITY', $barcode['QUANTITY']);
					}
					else
					{
						$plusList[$barcodeValue] = array(
							'ID' => $barcode['ID'],
							'QUANTITY' => $barcode['QUANTITY']
						);
					}
				}
			}
		}

		foreach ($plusList as $barcode)
		{
			$item = $this->getItemById($barcode['ID']);
			if ($item)
			{
				/** @var Result $r */
				$r = $item->setField('QUANTITY', $barcode['QUANTITY']);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}


	/**
	 * @param array $values
	 * @return array
	 */
	private function getBarcodeListFromArray(array $values)
	{

		$result = array();

		foreach ($values['BARCODE_INFO'] as $barcodeDat)
		{
			$storeId = $barcodeDat['STORE_ID'];

			if (!isset($barcodeDat['BARCODE']) || !is_array($barcodeDat['BARCODE']))
				continue;

			if (count($barcodeDat['BARCODE']) > 1)
			{
				$quantity = floatval($barcodeDat['QUANTITY'] / count($barcodeDat['BARCODE']));
			}
			else
			{
				$quantity = floatval($barcodeDat['QUANTITY']);
			}

			foreach ($barcodeDat['BARCODE'] as $barcode)
			{
				if (!isset($result[$storeId]))
					$result[$storeId] = array();

				$result[$storeId][$barcode['VALUE']] = array(
					"QUANTITY" => $quantity,
				);

				if (isset($barcode['ID']) && intval($barcode['ID']) > 0)
				{
					$result[$storeId][$barcode['VALUE']]['ID'] = intval($barcode['ID']);
				}
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getAllBarcodeList()
	{
		$result = array();
		/** @var ShipmentItemStore $item */
		foreach ($this->collection as $item)
		{
			if (!isset($result[$item->getField('STORE_ID')]))
			{
				$result[$item->getField('STORE_ID')] = array();
			}

			$result[$item->getField('STORE_ID')][$item->getField('BARCODE')] = array(
				'ID' => $item->getField('ID'),
				'QUANTITY' => $item->getField('QUANTITY'),
			);
		}

		return $result;
	}

	/**
	 * @param string $barcode
	 * @param $basketCode
	 * @param $storeId
	 *
	 * @return ShipmentItemStore|null
	 */
	public function getItemByBarcode($barcode, $basketCode, $storeId = null)
	{
		/** @var ShipmentItemStore $shipmentItemStore */
		foreach ($this->collection as $shipmentItemStore)
		{

			//$storeId == $shipmentItemStore->getStoreId()
			if ($shipmentItemStore->getBarcode() == $barcode)
			{
				/** @var BasketItem $basketItem */
				$basketItem = $shipmentItemStore->getBasketItem();

				if ($basketItem->getBasketCode() != $basketCode)
						continue;

				return $shipmentItemStore;
			}
		}

		return null;
	}

} 