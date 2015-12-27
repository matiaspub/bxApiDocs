<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Compatible\BasketCompatibility;
use Bitrix\Sale\Internals;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

class Basket
	extends BasketBase
{
	/** @var null|int */
	private $bundleIndex = null;

	/** @var bool */
	private $loadForFUserId = false;


	const BASKET_DELETE_LIMIT = 2000;

	protected function getEntityParent()
	{
		return $this->getOrder();
	}

	protected function __construct()
	{

	}

	/**
	 * @param $siteId
	 * @param null $fuserId
	 * @return static
	 */
	public static function create($siteId, $fuserId = null)
	{
		$basket = new static();
		$basket->setSiteId($siteId);

//		if ($fuserId !== null)
//			$basket->setFUserId($fuserId);

		return $basket;
	}

	/**
	 * @param $moduleId
	 * @param $productId
	 * @param null| string $basketCode
	 * @return BasketItem
	 */
	public function createItem($moduleId, $productId, $basketCode = null)
	{
		$basketItem = BasketItem::create($this, $moduleId, $productId, $basketCode);
		$this->addItem($basketItem);

		return $basketItem;
	}

	/**
	 * @internal
	 *
	 * @param BasketItem $basketItem
	 * @return bool
	 */
	public function addItem(BasketItem $basketItem)
	{
		/** @var BasketItem $basketItem */
		$basketItem = parent::addItem($basketItem);

		$basketItem->setCollection($this);

		/** @var Order $order */
		$order = $this->getOrder();
		if ($order)
		{
			$order->onBasketModify(EventActions::ADD, $basketItem);
		}

	}

	/**
	 * @param BasketItem $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return Result
	 * @throws ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function onItemModify(BasketItem $item, $name = null, $oldValue = null, $value = null)
	{
		$result = new Result();
		if ($name == "QUANTITY")
		{
			$value = (float)$value;
			$oldValue = (float)$oldValue;
			$deltaQuantity = $value - $oldValue;

			$availableQuantity = 0;

			/** @var Result $r */
			$r = Provider::checkAvailableProductQuantity($item, $deltaQuantity);
			if (!$r->isSuccess())
			{
				return $r;
			}
			else
			{
				$availableQuantityData = $r->getData();
				if (array_key_exists('AVAILABLE_QUANTITY', $availableQuantityData))
				{
					$availableQuantity = $availableQuantityData['AVAILABLE_QUANTITY'];
				}
				else
				{
					$result->addError( new ResultError(Loc::getMessage('SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY',
																	   array(
																			'#PRODUCT_NAME#' => $item->getField('NAME'),
																		)), 'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY')
									);
					return $result;
				}
			}

			$checkQuantity = $oldValue + $availableQuantity;

			if ($value != 0
				&& (($deltaQuantity > 0) && ($checkQuantity < $value)   // plus
				|| ($deltaQuantity < 0) && ($checkQuantity > $value)))   // minus
			{
				$result = new Result();
				$mess = ($deltaQuantity > 0) ? Loc::getMessage('SALE_BASKET_AVAILABLE_FOR_PURCHASE_QUANTITY',
																array(
																	'#PRODUCT_NAME#' => $item->getField('NAME'),
																	'#AVAILABLE_QUANTITY#' => $availableQuantity
																)) :
																Loc::getMessage('SALE_BASKET_AVAILABLE_FOR_DECREASE_QUANTITY',
																array(
																	'#PRODUCT_NAME#' => $item->getField('NAME'),
																	'#AVAILABLE_QUANTITY#' => (-$availableQuantity)
																));

				$result->addError(new ResultError($mess, "SALE_BASKET_AVAILABLE_QUANTITY"));
				$result->setData(array("AVAILABLE_QUANTITY" => $checkQuantity, "REQUIRED_QUANTITY" => $deltaQuantity));
				return $result;
			}

			if (!$this->getOrder() || $this->getOrder()->getId() == 0)
			{
				if ($item->getField("CUSTOM_PRICE") != "Y")
				{
					$r = static::refreshData(array("PRICE"), $item);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
			}

			if ($deltaQuantity != 0 && $item->isBundleParent())
			{
				if ($bundleCollection = $item->getBundleCollection())
				{
					$bundleBaseQuantity = $item->getBundleBaseQuantity();

					/** @var BasketItem $bundleBasketItem */
					foreach ($bundleCollection as $bundleBasketItem)
					{
						$bundleProductId = $bundleBasketItem->getProductId();

						if (!isset($bundleBaseQuantity[$bundleProductId]))
							throw new ArgumentOutOfRangeException("bundle product id");

						$quantity = $bundleBaseQuantity[$bundleProductId] * $value;

						$r = $bundleBasketItem->setField('QUANTITY', $quantity);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
				}
			}
		}
		elseif ($name == "DELAY")
		{
			if ($item->isBundleParent())
			{
				/** @var BasketBundleCollection $bundleCollection */
				if ($bundleCollection = $item->getBundleCollection())
				{
					/** @var BasketItem $bundleBasketItem */
					foreach ($bundleCollection as $bundleBasketItem)
					{
						$r = $bundleBasketItem->setField('DELAY', $value);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
				}
			}
			//throw new Main\NotImplementedException();
		}
		elseif ($name == "CAN_BUY")
		{
			if ($item->isBundleParent())
			{
				/** @var BasketBundleCollection $bundleCollection */
				if ($bundleCollection = $item->getBundleCollection())
				{
					/** @var BasketItem $bundleBasketItem */
					foreach ($bundleCollection as $bundleBasketItem)
					{
						$r = $bundleBasketItem->setField('CAN_BUY', $value);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}
				}
			}
			//throw new Main\NotImplementedException();
		}
		elseif ($name == "PRICE")
		{
			if ($value < 0)
			{
				$result->addError( new ResultError(Loc::getMessage('SALE_BASKET_ITEM_WRONG_PRICE',
																   array(
																	   '#PRODUCT_NAME#' => $item->getField('NAME'),
																   )), 'SALE_BASKET_ITEM_WRONG_PRICE')
				);
				return $result;
			}
		}

		/** @var Order $order */
		$order = $this->getOrder();
		if ($order)
		{
			$r = $order->onBasketModify(EventActions::UPDATE, $item, $name, $oldValue, $value);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param array $select
	 * @param BasketItem $refreshItem
	 * @return Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public function refreshData($select = array(), BasketItem $refreshItem = null)
	{
		$result = new Result();

		$isStartField = $this->isStartField();

		$discount = null;
		/** @var Order $order */
		if ($order = $this->getOrder())
		{
			$discount = $order->getDiscount();
		}

		$settableFields = array_fill_keys(BasketItem::getSettableFields(), true);
		$data = Provider::getProductData($this, $select, $refreshItem);
		foreach ($data as $key => $value)
		{
			//$item = $this->getItemByBasketCode($key);
			if(!($item = $this->getItemByBasketCode($key)))
			{
				continue;
			}

			if (isset($value['DELETE']) && $value['DELETE'])
			{
				$item->delete();
				if ($discount !== null)
					$discount->clearBasketItemData($key);
				continue;
			}

			$basePrice = false;
			$currency = false;
			$discountList = false;
			$value1 = array();

			$roundFields = static::getRoundFields();

			foreach ($value as $k => $v)
			{
				//TODO: create method for save data in discount
				if ($k == 'BASE_PRICE')
					$basePrice = true;
				if ($k == 'CURRENCY')
					$currency = true;
				if ($k == 'DISCOUNT_LIST' && !empty($v))
					$discountList = true;
				//TODO END
				if (isset($settableFields[$k]))
				{
					if ($item)
					{
						if ($k == "PRICE" && $item->isCustomPrice())
						{
							$v = $item->getPrice();
						}

						if (in_array($k, $roundFields))
						{
							$v = roundEx($v, SALE_VALUE_PRECISION);
						}
					}
					$value1[$k] = $v;
				}
			}

			if (!$item->isCustomPrice())
			{
				$value1['PRICE'] = $value1['BASE_PRICE'] - $value1['DISCOUNT_PRICE'];
			}

			if (!$item)
				$item = $this->createItem($value['MODULE_ID'], $value['PRODUCT_ID']);
			if (!$item)
				continue;

			if ($discount !== null)
			{
				if ($basePrice && $currency)
					$discount->setBasketItemBasePrice($key, $value['BASE_PRICE'], $value['CURRENCY']);
				if ($discountList)
					$discount->setBasketItemDiscounts($key, $value['DISCOUNT_LIST']);
			}

			/** @var Result $r */
			$r = $item->setFields($value1);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		/** @var Order $order */
		if ($order = $this->getOrder())
		{
			$r = $order->refreshData(array('PRICE', 'PRICE_DELIVERY'));
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		if ($isStartField)
		{
			$hasMeaningfulFields = $this->hasMeaningfulField();

			/** @var Result $r */
			$r = $this->doFinalAction($hasMeaningfulFields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param array $requestBasket
	 * @return Basket
	 * @throws UserMessageException
	 */
	public static function createFromRequest(array $requestBasket)
	{
		if (array_key_exists('SITE_ID', $requestBasket) && strval($requestBasket['SITE_ID']) != '')
		{
			throw new UserMessageException('site_id not found');
		}

		$basket = static::create($requestBasket['SITE_ID']);

		foreach ($requestBasket as $requestBasketItem)
		{
			$basketItem = BasketItem::create($basket, $requestBasketItem['MODULE'], $requestBasketItem['PRODUCT_ID']);
			$basketItem->initFields($requestBasketItem);

			$basket->addItem($basketItem);
		}

		return $basket;
	}

	/**
	 * @param $fUserId
	 * @param $siteId
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function loadItemsForFUser($fUserId, $siteId)
	{
		/** @var Basket $basket */
		$basket = static::create($siteId);

		$basket->setFUserId($fUserId);
		$basket->setSiteId($siteId);

		$basket->loadForFUserId = true;

		/** @var Basket $collection */
		return $basket->loadFromDB(array(
										"FUSER_ID" => $fUserId,
										"LID" => $siteId,
										"ORDER_ID" => null
									));
	}

	/**
	 * @param BasketItemBase $basketItem
	 * @return bool
	 */
	public static function loadBundleChild(BasketItemBase $basketItem)
	{

		/** @var BasketBundleCollection $collection */
		$collection = new BasketBundleCollection();

		$collection->setParentBasketItem($basketItem);
		if ($basketItem->getId() > 0)
		{
			return $collection->loadFromDB(array(
											"SET_PARENT_ID" => $basketItem->getId(),
											"TYPE" => false
										));
		}

		return $collection;

	}

	/**
	 * @param array $filter
	 * @return Basket|static
	 */
	protected function loadFromDb(array $filter)
	{

		$order = $this->getOrder();
		if ($order instanceof OrderBase)
		{
			$this->setOrder($order);
		}

		$select = array("ID", "LID", "MODULE", "PRODUCT_ID", "QUANTITY", "WEIGHT",
			"DELAY", "CAN_BUY", "PRICE", "CUSTOM_PRICE", "BASE_PRICE", 'PRODUCT_PRICE_ID', "CURRENCY", 'BARCODE_MULTI', "RESERVED", "RESERVE_QUANTITY",
			"NAME", "CATALOG_XML_ID", "VAT_RATE",
			"NOTES", "DISCOUNT_PRICE",
			"PRODUCT_PROVIDER_CLASS", "CALLBACK_FUNC", "ORDER_CALLBACK_FUNC", "PAY_CALLBACK_FUNC", "CANCEL_CALLBACK_FUNC",
			"DIMENSIONS", "TYPE", "SET_PARENT_ID",
			"DETAIL_PAGE_URL", "FUSER_ID", 'MEASURE_CODE', 'MEASURE_NAME', 'ORDER_ID',
			'DATE_INSERT', 'DATE_UPDATE', 'PRODUCT_XML_ID', 'SUBSCRIBE', 'RECOMMENDATION',
			'VAT_INCLUDED'
		);

		$basketItemList = array();
		$first = true;

		$res = static::getList(array(
			"filter" => $filter,
			"select" => $select,
			"order" => array('ID' => 'ASC'),
		));
		while($basket = $res->fetch())
		{
			$basketItem = BasketItem::create($this, $basket['MODULE'], $basket['PRODUCT_ID']);
			$basketItem->initFields($basket);

			if ($basketItem->isBundleChild())
			{
				$basketItemList[$basketItem->getId()] = $basketItem;
			}
			else
			{
				$basketItem->setCollection($this);
				$this->addItem($basketItem);
			}

			if ($first)
			{
//				$this->setFUserId($basketItem->getFUserId());
				$this->setSiteId($basketItem->getField('LID'));
				$first = false;
			}

		}

		if (!empty($basketItemList))
		{
			$this->setChildBundleCollection($basketItemList);
		}



		return $this;
	}

	/**
	 * @internal
	 * @param array $basketItemList
	 * @param BasketItem $externalParentBasketItem
	 *
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	public function setChildBundleCollection(array $basketItemList, BasketItem $externalParentBasketItem = null)
	{
		$order = null;

		$isExternalBasketParent = false;

		if ($externalParentBasketItem !== null)
		{
			if (!$externalParentBasketItem->isBundleParent())
			{
				throw new Main\ObjectException('basketItem not parent');
			}
			$isExternalBasketParent = true;
		}

		/** @var BasketItem $item */
		foreach ($basketItemList as $item)
		{
			if ($item->isBundleChild() || (!$item->isBundleParent() && $isExternalBasketParent && $externalParentBasketItem !== null))
			{
				/** @var BasketItem $parentBasketItem */
				$parentBasketItem = $item->getParentBasketItem();

				if (!$parentBasketItem && $externalParentBasketItem !== null)
				{
					/** @var BasketItem $parentBasketItem */
					$parentBasketItem = $externalParentBasketItem;
				}

				if ($parentBasketItem)
				{
					/** @var Basket $bundleCollection */
					$bundleCollection = $parentBasketItem->createBundleCollection();

					$propList = array();
					/** @var BasketPropertiesCollection $propertyCollection */
					if ($propertyCollection = $item->getPropertyCollection())
					{
						$propList = $propertyCollection->getPropertyValues();
					}

					if (static::getExistsItemInBundle($parentBasketItem, $item->getField('MODULE'), $item->getProductId(), $propList))
					{
						continue;
					}

					$bundleCollection->addItem($item);

					if ($order === null)
					{
						/** @var Basket $basket */
						if (!$basket = $parentBasketItem->getCollection())
						{
							throw new Main\ObjectNotFoundException('Entity "Basket" not found');
						}

						/** @var Order $order */
						if ($order = $basket->getOrder())
						{
							$bundleCollection->setOrder($order);
						}
					}

					$this->bundleIndex[$item->getId()] = $parentBasketItem->getId();
				}
			}
		}
	}


	/**
	 * @param array $fields
	 * @return \Bitrix\Main\Entity\AddResult
	 */
	public static function add(array $fields)
	{
		unset($fields['PROPS']);
		return Internals\BasketTable::add($fields);
	}

	/**
	 * @param $id
	 *
	 * @return Internals\CollectableEntity|bool
	 */
	public function getItemById($id)
	{
		$index = $this->getIndexById($id);
		$parentId = $this->getBundleParentId($id);

		if ($index === null && $parentId === false)
		{
			return null;
		}

		if ($index !== null && isset($this->collection[$index]))
		{
			return $this->collection[$index];
		}

		if ($parentId)
		{
			$parentIndex = $this->getIndexById($parentId);

			if ($parentIndex !== null && isset($this->collection[$parentIndex]))
			{
				/** @var BasketItem $item */
				$item = $this->collection[$parentIndex];

				if ($item->isBundleParent())
				{
					/** @var Basket $bundleCollection */
					if ($bundleCollection = $item->getBundleCollection())
					{
						return $bundleCollection->getItemById($id);
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param $id
	 * @return bool
	 */
	protected function getBundleParentId($id)
	{
		if (isset($this->bundleIndex[$id]))
		{
			return $this->bundleIndex[$id];
		}

		return false;
	}

	/**
	 * @return Entity\Result|bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function save()
	{

		$result = new Result();

		/** @var Order $order */
		$order = $this->getOrder();

		$itemsFromDb = array();
		$filter = array();



		if (!$order)
		{
			/** @var Main\Entity\Event $event */
			$event = new Main\Event('sale', EventActions::EVENT_ON_BASKET_BEFORE_SAVED, array(
				'ENTITY' => $this
			));
			$event->send();

			if ($event->getResults())
			{
				$result = new Result();
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::ERROR)
					{
						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BEFORE_BASKET_SAVED'), 'SALE_EVENT_ON_BEFORE_BASKET_SAVED');
						if (isset($eventResultData['ERROR']) && $eventResultData['ERROR'] instanceof ResultError)
						{
							$errorMsg = $eventResultData['ERROR'];
						}

						$result->addError($errorMsg);
					}
				}

				if (!$result->isSuccess())
				{
					return $result;
				}
			}
		}

		$isNew = ($order && $order->isNew()) ? true : false;

		if ($order && !$isNew)
		{
			$filter['ORDER_ID'] = $order->getId();
		}
		else
		{
			if ($this->isLoadForFuserId())
			{
				$filter = array(
					'FUSER_ID' => $this->getFUserId(),
					'ORDER_ID' => null,
					'LID' => $this->getSiteId()
				);
			}
		}

		if (!empty($filter))
		{
			$itemsFromDbList = Internals\BasketTable::getList(
				array(
					"filter" => $filter,
					"select" => array("ID", 'TYPE', 'SET_PARENT_ID', 'PRODUCT_ID', 'NAME', 'QUANTITY')
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
			{
				if (intval($itemsFromDbItem['SET_PARENT_ID']) > 0
					&& intval($itemsFromDbItem['SET_PARENT_ID']) != $itemsFromDbItem['ID'])
				{
					continue;
				}

				$itemsFromDb[$itemsFromDbItem["ID"]] = $itemsFromDbItem;
			}
		}


		/** @var BasketItem $basketItem */
		foreach ($this->collection as $index => $basketItem)
		{
			$r = $basketItem->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$basketItem->getId()]) && $basketItem->getQuantity() > 0)
			{
				unset($itemsFromDb[$basketItem->getId()]);
			}
		}


		if (!empty($filter))
		{
			foreach ($itemsFromDb as $k => $v)
			{
				if ($v['TYPE'] == static::TYPE_SET)
				{
					Internals\BasketTable::deleteBundle($k);
				}
				else
				{
					Internals\BasketTable::deleteWithItems($k);
				}

				/** @var Order $order */
				if ($order && $order->getId() > 0)
				{
					OrderHistory::addAction(
						'BASKET',
						$order->getId(),
						'BASKET_REMOVED',
						$k ,
						null,
						array(
							'NAME' => $v['NAME'],
							'QUANTITY' => $v['QUANTITY'],
							'PRODUCT_ID' => $v['PRODUCT_ID'],
						)
					);
				}

			}
		}

		if ($order && $order->getId() > 0)
			OrderHistory::collectEntityFields('BASKET', $order->getId());


		if (!$order)
		{
			/** @var Main\Entity\Event $event */
			$event = new Main\Event('sale', EventActions::EVENT_ON_BASKET_SAVED, array(
				'ENTITY' => $this
			));
			$event->send();

			if ($event->getResults())
			{
				$result = new Result();
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::ERROR)
					{
						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BASKET_SAVED'), 'SALE_EVENT_ON_BASKET_SAVED');
						if (isset($eventResultData['ERROR']) && $eventResultData['ERROR'] instanceof ResultError)
						{
							$errorMsg = $eventResultData['ERROR'];
						}

						$result->addError($errorMsg);
					}
				}

				if (!$result->isSuccess())
				{
					return $result;
				}
			}
		}

		return $result;
	}


	/**
	 * @param array $filter
	 * @return \Bitrix\Main\DB\Result
	 */
	public static function getList(array $filter = array())
	{
		return Internals\BasketTable::getList($filter);
	}

	/**
	 * @return float|int
	 */
	public function getVatRate()
	{
		return static::getMaxVatRate($this);
	}

	/**
	 * @param Basket $basketCollection
	 * @param int $vatRate
	 * @return int
	 */
	private static function getMaxVatRate(Basket $basketCollection, $vatRate = 0)
	{
		/** @var BasketItem $basketItem */
		foreach ($basketCollection as $basketItem)
		{
			// BasketItem that is removed is not involved
			if ($basketItem->getQuantity() == 0)
				continue;

			$basketVatRate = 0;
			if ($basketItem->isBundleParent())
			{
				/** @var Basket $bundleCollection */
				if (($bundleCollection = $basketItem->getBundleCollection()) && $bundleCollection instanceof BasketBundleCollection)
				{
					$basketVatRate = static::getMaxVatRate($bundleCollection);
				}

			}
			else
			{
				$basketVatRate = $basketItem->getVatRate();
			}

			if ($basketVatRate > 0)
			{
				if ($basketVatRate > $vatRate)
				{
					$vatRate = $basketVatRate;
				}
			}
		}

		return $vatRate;
	}

	/**
	 * @param $moduleId
	 * @param $productId
	 * @param array $properties
	 * @return BasketItem|bool
	 */
	public function getExistsItem($moduleId, $productId, array $properties = array())
	{
		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{

			if ($basketItem->getField('PRODUCT_ID') != $productId || $basketItem->getField('MODULE') != $moduleId)
			{
				if ($basketItem->isBundleParent())
				{
					if (static::getExistsItemInBundle($basketItem, $moduleId, $productId, $properties))
						continue;
				}
				else
				{
					continue;
				}
			}

			if (!empty($properties) && is_array($properties))
			{
				/** @var BasketPropertiesCollection $basketPropertyCollection */
				$basketPropertyCollection = $basketItem->getPropertyCollection();
				if ($basketPropertyCollection->isPropertyAlreadyExists($properties))
				{
					return $basketItem;
				}
			}
			else
			{
				return $basketItem;
			}
		}


		return null;
	}


	/**
	 * @internal
	 * @param BasketItem $basketItem
	 * @param $moduleId
	 * @param $productId
	 * @param array $properties
	 *
	 * @return BasketItem|bool
	 */
	public static function getExistsItemInBundle(BasketItem $basketItem, $moduleId, $productId, array $properties = array())
	{
		if (!$basketItem->isBundleParent())
			return null;

		if (($bundleList = $basketItem->getBundleCollection()) && count($bundleList) > 0)
		{
			/** @var BasketItem $bundleBasketItem */
			foreach($basketItem->getBundleCollection() as $bundleBasketItem)
			{
				if ($bundleBasketItem->getField('PRODUCT_ID') != $productId || $bundleBasketItem->getField('MODULE') != $moduleId)
					continue;

				if (!empty($properties) && is_array($properties))
				{
					/** @var BasketPropertiesCollection $basketPropertyCollection */
					$basketPropertyCollection = $bundleBasketItem->getPropertyCollection();
					if ($basketPropertyCollection->isPropertyAlreadyExists($properties))
					{
						return $bundleBasketItem;
					}
				}
				else
				{
					return $bundleBasketItem;
				}
			}
		}

		return null;
	}

	/**
	 * @return Basket
	 */
	public function getOrderableItems()
	{
		/** @var Basket $basket */
		$basket = static::create($this->getSiteId(), $this->getFUserId(true));

		if ($order = $this->getOrder())
		{
			$basket->setOrder($order);
		}

		/** @var BasketItem $item */
		foreach ($this->collection as $item)
		{
			if (!$item->canBuy() || $item->isDelay())
				continue;

			$item->setCollection($basket);
			$basket->addItem($item);
		}

		return $basket;
	}

	/**
	 * @return bool
	 */
	public function isLoadForFuserId()
	{
		return $this->loadForFUserId;
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getRoundFields()
	{
		return array(
			'BASE_PRICE',
			'DISCOUNT_PRICE',
			'DISCOUNT_PRICE_PERCENT',
		);
	}

	/**
	 * @param int $days
	 *
	 * @return bool
	 */
	public static function deleteOld($days)
	{
		$connection = Main\Application::getConnection();

		$expired = new Main\Type\DateTime();
		$expired->add('-'.$days.'days');
		$expiredValue = $expired->format('Y-m-d H:i:s');

		if ($connection instanceof Main\DB\MysqlConnection)
		{
			$query = "DELETE FROM b_sale_basket_props WHERE
										BASKET_ID IN (
											 SELECT ID FROM b_sale_basket WHERE
											 			FUSER_ID IN (
																SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
																		b_sale_fuser.DATE_UPDATE < '".$expiredValue."'
																		AND b_sale_fuser.USER_ID IS NULL
																)
										) LIMIT " . static::BASKET_DELETE_LIMIT;
			$connection->query($query);

			$query = "DELETE FROM b_sale_basket	WHERE
									FUSER_ID IN (
     										SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
     												b_sale_fuser.DATE_UPDATE < '".$expiredValue."'
													AND b_sale_fuser.USER_ID IS NULL
											) LIMIT " . static::BASKET_DELETE_LIMIT;
			$connection->query($query);
		}
		elseif ($connection instanceof Main\DB\MssqlConnection)
		{
			$query = "DELETE TOP (" . static::BASKET_DELETE_LIMIT . ") FROM b_sale_basket_props WHERE
										BASKET_ID IN (
     											SELECT id FROM b_sale_basket WHERE
     														FUSER_ID IN (
																	SELECT b_sale_fuser.id FROM b_sale_fuser
																	WHERE b_sale_fuser.DATE_UPDATE < CONVERT(varchar(20),'".$expiredValue."', 20)
																			AND b_sale_fuser.USER_ID IS NULL
															)
											)";

			$connection->query($query);

			$query = "DELETE TOP (" . static::BASKET_DELETE_LIMIT . ") FROM b_sale_basket WHERE
									FUSER_ID IN (
     										SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
     												b_sale_fuser.DATE_UPDATE < CONVERT(varchar(20),'".$expiredValue."', 20)
													AND b_sale_fuser.USER_ID IS NULL
											)";
			$connection->query($query);
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$query = "DELETE FROM b_sale_basket_props WHERE
										BASKET_ID NOT IN (
     											SELECT id FROM b_sale_basket WHERE
     														FUSER_ID IN (
																	SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
																			b_sale_fuser.DATE_UPDATE < TO_DATE('".$expiredValue."', 'YYYY-MM-DD HH24:MI:SS')
																			AND b_sale_fuser.USER_ID IS NULL
																)
											) AND ROWNUM <= ".static::BASKET_DELETE_LIMIT;

			$connection->query($query);

			$query = "DELETE FROM b_sale_basket WHERE
									FUSER_ID IN (
     										SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
     												b_sale_fuser.DATE_UPDATE < TO_DATE('".$expiredValue."', 'YYYY-MM-DD HH24:MI:SS')
													AND b_sale_fuser.USER_ID IS NULL
											) AND ROWNUM <= ".static::BASKET_DELETE_LIMIT;

			$connection->query($query);
		}

		return true;
	}

	/**
	 * @param $days
	 * @param $speed
	 *
	 * @return string
	 */
	public static function deleteOldAgent($days, $speed = 0)
	{
		if (!isset($GLOBALS["USER"]) || !is_object($GLOBALS["USER"]))
		{
			$tmpUser = True;
			$GLOBALS["USER"] = new \CUser();
		}

		static::deleteOld($days);

		Fuser::deleteOld($days);

		global $pPERIOD;
		if(intval($speed) > 0)
			$pPERIOD = $speed;
		else
			$pPERIOD = 3*60*60;

		if (isset($tmpUser))
		{
			unset($GLOBALS["USER"]);
		}

		return "\\Bitrix\\Sale\\Basket::deleteOldAgent(".intval(Main\Config\Option::get("sale", "delete_after", "30")).", ".IntVal($speed).");";
	}
}