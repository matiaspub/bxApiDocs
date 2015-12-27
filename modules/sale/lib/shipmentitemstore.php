<?php


namespace Bitrix\Sale;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Internals\ShipmentItemStoreTable;

Loc::loadMessages(__FILE__);

class ShipmentItemStore
	extends Internals\CollectableEntity
{
	/** @var  BasketItem */
	protected $basketItem;

	/** @var null|array  */
	protected $barcodeList = null;


	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array("ORDER_DELIVERY_BASKET_ID", "STORE_ID", "QUANTITY", "BARCODE", 'BASKET_ID');
	}

	/**
	 * @return array
	 */
	public static function getMeaningfulFields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public static function getAllFields()
	{
		static $fields = null;
		if ($fields == null)
			$fields = array_keys(ShipmentItemStoreTable::getMap());
		return $fields;
	}


	public static function create(ShipmentItemStoreCollection $collection, BasketItem $basketItem)
	{
		$fields = array(
			'BASKET_ID' => $basketItem->getId(),
		);

		$shipmentItemStore = new static($fields);
		$shipmentItemStore->setCollection($collection);

		$shipmentItemStore->basketItem = $basketItem;

		return $shipmentItemStore;

	}


	/**
	 * @return int
	 */
	public function getBasketId()
	{
		return $this->getField('BASKET_ID');
	}

	/**
	 * @return float
	 */
	public function getQuantity()
	{
		return floatval($this->getField('QUANTITY'));
	}

	/**
	 * @return integer
	 */
	public function getStoreId()
	{
		return $this->getField('STORE_ID');
	}

	/**
	 * @return string
	 */
	public function getBasketCode()
	{
		$basket = $this->getBasketItem();
		return $basket->getBasketCode();
	}

	/**
	 * @return string
	 */
	public function getBarcode()
	{
		return $this->getField('BARCODE');
	}

	/**
	 * @return string
	 */
	public function getItemCode()
	{
		$basketCode = $this->getBasketCode();
		$storeId = $this->getStoreId();
		$deliveryBasketId = $this->getField('ORDER_DELIVERY_BASKET_ID');
		$id = $this->getField('id');

		return $basketCode."_".$storeId."_".$deliveryBasketId."_".$id;
	}


	/**
	 * @return bool
	 */
	protected function loadBasketItem()
	{
		/** @var ShipmentItemStoreCollection $collection */
		$collection = $this->getCollection();
		$shipmentItem = $collection->getShipmentItem();
		$basketItem = $shipmentItem->getBasketItem();

		return $basketItem;
	}

	/**
	 * @return BasketItem
	 */
	public function getBasketItem()
	{
		if ($this->basketItem == null)
		{
			$this->basketItem = $this->loadBasketItem();
		}

		return $this->basketItem;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function loadForShipmentItem($id)
	{
		if (intval($id) <= 0)
			throw new Main\ArgumentNullException("id");

		$items = array();

		$itemDataList = Internals\ShipmentItemStoreTable::getList(
			array(
				'filter' => array('ORDER_DELIVERY_BASKET_ID' => $id),
				'order' => array('DATE_CREATE' => 'ASC', 'ID' => 'ASC')
			)
		);
		while ($itemData = $itemDataList->fetch())
			$items[] = new static($itemData);

		return $items;
	}

	/**
	 * @return Main\Entity\AddResult|Main\Entity\UpdateResult
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws \Exception
	 */
	public function save()
	{
		global $USER;

		$result = new Result();

		$id = $this->getId();
		$fields = $this->fields->getValues();

		/** @var ShipmentItemStoreCollection $collection */
		$collection = $this->getCollection();

		/** @var Result $r */
		$r = $collection->checkAvailableQuantity($this);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		/** @var BasketItem $basketItem */
		$basketItem = $this->getBasketItem();

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();

			if (!empty($fields) && is_array($fields))
			{
				if (isset($fields["QUANTITY"]) && (floatval($fields["QUANTITY"]) <= 0))
					throw new Main\ArgumentNullException('quantity');

				$fields['DATE_MODIFY'] = new Main\Type\DateTime();
				$fields['MODIFIED_BY'] = $USER->GetID();

				$r = Internals\ShipmentItemStoreTable::update($id, $fields);
				if (!$r->isSuccess())
					return $r;

			}

			$result = new Main\Entity\UpdateResult();

		}
		else
		{

			if (!isset($fields["ORDER_DELIVERY_BASKET_ID"]))
				$fields['ORDER_DELIVERY_BASKET_ID'] = $this->getParentShipmentItemId();

			if (!isset($fields["BASKET_ID"]))
				$fields['BASKET_ID'] = $basketItem->getId();

			$fields['DATE_CREATE'] = new Main\Type\DateTime();

			if (!isset($fields["QUANTITY"]) || (floatval($fields["QUANTITY"]) == 0))
				return new Main\Entity\AddResult();

			if ($basketItem->isBarcodeMulti() && isset($fields['BARCODE']) && strval(trim($fields['BARCODE'])) == "")
			{
				$result->addError(new ResultError(Loc::getMessage('SHIPMENT_ITEM_STORE_BARCODE_MULTI_EMPTY', array(
					'#PRODUCT_NAME#' => $basketItem->getField('NAME'),
					'#STORE_ID#' => $fields['STORE_ID'],
				)), 'SHIPMENT_ITEM_STORE_BARCODE_MULTI_EMPTY'));
				return $result;
			}

			$r = Internals\ShipmentItemStoreTable::add($fields);
			if (!$r->isSuccess())
			{
				return $r;
			}

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);

			$result = new Main\Entity\AddResult();

		}

		return $result;
	}

	/**
	 * @return int
	 */
	private function getParentShipmentItemId()
	{
		/** @var ShipmentItemStoreCollection $collection */
		$collection = $this->getCollection();
		$shipmentItem = $collection->getShipmentItem();
		return $shipmentItem->getId();
	}

	/**
	 * @param string $name
	 * @param null|string $oldValue
	 * @param null|string $value
	 * @throws Main\ObjectNotFoundException
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{
		if ($this->getId() > 0)
		{
			/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
			if (!$shipmentItemStoreCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemStoreCollection" not found');
			}

			/** @var ShipmentItem $shipmentItem */
			if (!$shipmentItem = $shipmentItemStoreCollection->getShipmentItem())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItem" not found');
			}

			/** @var ShipmentItemCollection $shipmentItemCollection */
			if (!$shipmentItemCollection = $shipmentItem->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
			}

			/** @var Shipment $shipment */
			if (!$shipment = $shipmentItemCollection->getShipment())
			{
				throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
			}

			if ($shipment->isSystem())
				return;

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			/** @var Order $order */
			if (($order = $shipmentCollection->getOrder()) && $order->getId() > 0)
			{
				$historyFields = array();

				/** @var BasketItem $basketItem */
				if ($basketItem = $shipmentItem->getBasketItem())
				{
					$historyFields = array(
						'NAME' => $basketItem->getField('NAME'),
						'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
					);
				}

				OrderHistory::addField(
					'SHIPMENT_ITEM_STORE',
					$order->getId(),
					$name,
					$oldValue,
					$value,
					$this->getId(),
					$this,
					$historyFields
					);
			}


		}
	}


}