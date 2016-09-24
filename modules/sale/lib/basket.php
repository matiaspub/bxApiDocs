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
	 * Returns copy of current basket.
	 * For example, the copy will be used to calculate discounts.
	 * So, basket does not contain full information about BasketItem with bundleCollection, because now it is not
	 * necessary.
	 *
	 * Attention! Don't save the basket.
	 *
	 * @internal
	 * @return Basket
	 * @throws Main\SystemException
	 */
	public function copy()
	{
		if($this->order !== null)
		{
			throw new Main\SystemException('Could not clone basket which has order.');
		}

		$basket = Basket::create($this->siteId);
		/**@var BasketItem $item */
		foreach($this as $originalItem)
		{
			$item = $basket->createItem($originalItem->getField("MODULE"), $originalItem->getProductId());
			$item->setFields(
				array_intersect_key(
					$originalItem->getFields()->getValues(),
					array_flip(
						$item->getSettableFields()
					)
				)
			);
		}

		return $basket;
	}

	/**
	 * @param $siteId
	 * @return static
	 */
	public static function create($siteId)
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

		$basketItem->setCollection($this);
		$this->addItem($basketItem);

		return $basketItem;
	}

	/**
	 * @internal
	 *
	 * @param Internals\CollectableEntity $basketItem
	 * @return bool
	 */
	public function addItem(Internals\CollectableEntity $basketItem)
	{
		/** @var BasketItem $basketItem */
		$basketItem = parent::addItem($basketItem);

		$basketItem->setCollection($this);

		/** @var Order $order */
		if ($order = $this->getOrder())
		{
			$order->onBasketModify(EventActions::ADD, $basketItem);
		}

	}

	/**
	 * @param Internals\CollectableEntity $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 * @return Result
	 * @throws ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function onItemModify(Internals\CollectableEntity $item, $name = null, $oldValue = null, $value = null)
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
			if (!$r->isSuccess() && $item->getField('SUBSCRIBE') !== 'Y')
			{
				$result->addErrors($r->getErrors());
				$result->setData($r->getData());
				return $result;
			}
			else
			{

				if ($item->getField('SUBSCRIBE') === 'Y')
				{
					$availableQuantity = $value;
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
			}

			$checkQuantity = $oldValue + $availableQuantity;

			if ($value != 0
				&& (($deltaQuantity > 0) && (roundEx($checkQuantity, SALE_VALUE_PRECISION) < roundEx($value, SALE_VALUE_PRECISION))   // plus
				|| ($deltaQuantity < 0) && (roundEx($checkQuantity, SALE_VALUE_PRECISION) > roundEx($value, SALE_VALUE_PRECISION))))   // minus
			{
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
				if ($item->getField("CUSTOM_PRICE") != "Y" && !$item->isBundleChild())
				{
					$r = static::refreshData(array("PRICE"), $item);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
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
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		/** @var Order $order */
		if ($order = $this->getOrder())
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
			/** @var null|BasketItem $item */
			$item = null;
			//$item = $this->getItemByBasketCode($key);
//			if(!($item = $this->getItemByBasketCode($key)))
//			{
//				continue;
//			}

			$item = $this->getItemByBasketCode($key);

			if (isset($value['DELETE']) && $value['DELETE'])
			{
				if($item)
				{
					$item->delete();
					if ($discount !== null)
						$discount->clearBasketItemData($key);
				}

				continue;
			}

			$value1 = array();

			$roundFields = static::getRoundFields();

			foreach ($value as $k => $v)
			{
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
							$v = PriceMaths::roundPrecision($v);
						}
					}
					$value1[$k] = $v;
				}
			}

			if (!$item)
				$item = $this->createItem($value['MODULE_ID'], $value['PRODUCT_ID']);

			if (!$item)
				continue;

			/** @var Main\Entity\Event $event */
			$event = new Main\Event('sale', EventActions::EVENT_ON_BASKET_ITEM_REFRESH_DATA, array(
				'ENTITY' => $item,
				'VALUES' => $value
			));
			$event->send();

			if ($event->getResults())
			{
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::ERROR)
					{
						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BASKET_ITEM_REFRESH_DATA'), 'SALE_EVENT_ON_BASKET_ITEM_REFRESH_DATA');
						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData) && $eventResultData instanceof ResultError)
							{
								/** @var ResultError $errorMsg */
								$errorMsg = $eventResultData;
							}
						}

						$result->addError($errorMsg);
					}
				}
			}

			if (!$item->isCustomPrice() && array_key_exists('DISCOUNT_PRICE', $value1) && array_key_exists('BASE_PRICE', $value1))
			{
				$value1['PRICE'] = $value1['BASE_PRICE'] - $value1['DISCOUNT_PRICE'];
			}

			if ($discount instanceof Discount)
				$discount->setBasketItemData($key, $value);

			$isBundleParent = (bool)($item && $item->isBundleParent());

			/** @var Result $r */
			$r = $item->setFields($value1);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}

			if (($isBundleParent || $item->isBundleParent())
				&& array_key_exists('BUNDLE_ITEMS', $value))
			{
				/** @var BasketBundleCollection $bundleCollection */
				if ($bundleCollection = $item->getBundleCollection())
				{
					$bundleIndexList = array();
					/** @var BasketItem $bundleBasketItem */
					foreach ($bundleCollection as $bundleBasketItem)
					{
						$bundleIndexList[$bundleBasketItem->getBasketCode()] = true;
					}

					/** @var array $bundleBasketItemData */
					foreach ($value['BUNDLE_ITEMS'] as $bundleBasketItemData)
					{
						if (empty($bundleBasketItemData['MODULE']) || empty($bundleBasketItemData['PRODUCT_ID']))
							continue;

						/** @var BasketItem $bundleBasketItem */
						if ($bundleBasketItem = $bundleCollection->getExistsItem($bundleBasketItemData['MODULE'], $bundleBasketItemData['PRODUCT_ID'], (!empty($bundleBasketItemData['PROPS']) && is_array($bundleBasketItemData['PROPS']))?$bundleBasketItemData['PROPS'] : array()))
						{
							if (isset($bundleIndexList[$bundleBasketItem->getBasketCode()]))
								unset($bundleIndexList[$bundleBasketItem->getBasketCode()]);
						}
						else
						{
							/** @var BasketItem $bundleBasketItem */
							$bundleBasketItem = BasketItem::create($bundleCollection, $bundleBasketItemData['MODULE'], $bundleBasketItemData['PRODUCT_ID']);
						}

						if (!$bundleBasketItem)
							continue;

						$fields = array_intersect_key($bundleBasketItemData, $settableFields);
						$r = $bundleBasketItem->setFields($fields);
						if (!$r->isSuccess())
						{
							$result->addErrors($r->getErrors());
						}
					}

					if (!empty($bundleIndexList) && is_array($bundleIndexList))
					{
						foreach ($bundleIndexList as $bundleBasketItemCode => $bundleItemValue)
						{
							/** @var BasketItem $bundleBasketItem */
							if ($bundleBasketItem = $bundleCollection->getItemByBasketCode($bundleBasketItemCode))
							{
								$bundleBasketItem->delete();
							}
						}
					}
				}
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
	 * @param Basket $basketCollection
	 *
	 * @return array
	 */
	protected static function getActuality(Basket $basketCollection)
	{
		$basketList = array();
		/** @var BasketItem $basketItem */
		foreach ($basketCollection as $basketItem)
		{
			if ($basketItem->isBundleParent())
			{
				if ($bundleCollection = $basketItem->getBundleCollection())
				{
					foreach ($bundleCollection as $bundleBasketItem)
					{
						$basketList[] = $bundleBasketItem;
					}
				}
			}
		}

		return $basketList;
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
	 * @return Basket
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
										"=LID" => $siteId,
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
			/** @var Basket $basket */
			if ($basket = $basketItem->getCollection())
			{
				if ($order = $basket->getOrder())
				{
					$collection->setOrder($order);
				}
			}

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

		$select = array("ID", "LID", "MODULE", "PRODUCT_ID", "QUANTITY", "WEIGHT",
			"DELAY", "CAN_BUY", "PRICE", "CUSTOM_PRICE", "BASE_PRICE", 'PRODUCT_PRICE_ID', "CURRENCY", 'BARCODE_MULTI', "RESERVED", "RESERVE_QUANTITY",
			"NAME", "CATALOG_XML_ID", "VAT_RATE",
			"NOTES", "DISCOUNT_PRICE",
			"PRODUCT_PROVIDER_CLASS", "CALLBACK_FUNC", "ORDER_CALLBACK_FUNC", "PAY_CALLBACK_FUNC", "CANCEL_CALLBACK_FUNC",
			"DIMENSIONS", "TYPE", "SET_PARENT_ID",
			"DETAIL_PAGE_URL", "FUSER_ID", 'MEASURE_CODE', 'MEASURE_NAME', 'ORDER_ID',
			'DATE_INSERT', 'DATE_UPDATE', 'PRODUCT_XML_ID', 'SUBSCRIBE', 'RECOMMENDATION',
			'VAT_INCLUDED', 'SORT'
		);

		$basketItemList = array();
		$first = true;

		$res = static::getList(array(
			"filter" => $filter,
			"select" => $select,
			"order" => array('SORT' => 'ASC', 'ID' => 'ASC'),
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
						if (empty($this->bundleIndex[$item->getId()]))
						{
							$this->bundleIndex[$item->getId()] = $parentBasketItem->getId();
						}
						continue;
					}

					if ($this->getItemByBasketCode($parentBasketItem->getBasketCode()))
					{
						$bundleCollection->addItem($item);
					}
					else
					{
						$this->addItem($item);
					}

					if ($order === null)
					{
						/** @var Basket $basket */
						if (!$basket = $parentBasketItem->getCollection())
						{
							throw new Main\ObjectNotFoundException('Entity "Basket" not found');
						}

						/** @var Order $order */
						$order = $basket->getOrder();
					}

					if ($bundleCollection->getOrder() === null && $order instanceof OrderBase)
						$bundleCollection->setOrder($order);

					$this->bundleIndex[$item->getId()] = $parentBasketItem->getId();
				}
			}
		}
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
	 * @return array|null
	 */
	protected function getBundleIndexList()
	{
		return $this->bundleIndex;
	}

	/**
	 * @param array $list
	 */
	protected function setBundleIndexList(array $list)
	{
		$this->bundleIndex = $list;
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
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::ERROR)
					{
						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BEFORE_BASKET_SAVED'), 'SALE_EVENT_ON_BEFORE_BASKET_SAVED');
						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData) && $eventResultData instanceof ResultError)
							{
								/** @var ResultError $errorMsg */
								$errorMsg = $eventResultData;
							}
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

			if ($isNew)
			{
				$fUserId = $this->getFUserId(true);
				if ($fUserId <= 0)
				{
					$userId = $order->getUserId();
					if (intval($userId) > 0)
					{
						$fUserId = Fuser::getIdByUserId($userId);
						if ($fUserId > 0)
							$this->setFUserId($fUserId);
					}
				}

			}
		}

		if (!empty($filter))
		{
			$itemsFromDbList = Internals\BasketTable::getList(
				array(
					"filter" => $filter,
					"select" => array("ID", 'TYPE', 'SET_PARENT_ID', 'PRODUCT_ID', 'NAME', 'QUANTITY', 'FUSER_ID', 'ORDER_ID')
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

		if ($order && ($order->getId() > 0 && $this->getOrderId() == 0))
		{
			$this->orderId = $order->getId();
		}

		$changeMeaningfulFields = array(
			"PRODUCT_ID",
			"QUANTITY",
			"PRICE",
			"DISCOUNT_VALUE",
			"VAT_RATE",
			"NAME",
		);

		/** @var BasketItem $basketItem */
		foreach ($this->collection as $index => $basketItem)
		{
			$isNew = (bool)($basketItem->getId() <= 0);
			$isChanged = $basketItem->isChanged();

			if ($order && $order->getId() > 0 && $isChanged)
			{
				$logFields = array();

				$fields = $basketItem->getFields();
				$originalValues = $fields->getOriginalValues();

				foreach($originalValues as $originalFieldName => $originalFieldValue)
				{
					if (in_array($originalFieldName, $changeMeaningfulFields) && $basketItem->getField($originalFieldName) != $originalFieldValue)
					{
						$logFields[$originalFieldName] = $basketItem->getField($originalFieldName);
						$logFields['OLD_'.$originalFieldName] = $originalFieldValue;
					}
				}
			}

			$r = $basketItem->save();
			if ($r->isSuccess())
			{
				if ($order && $order->getId() > 0)
				{
					if ($isChanged)
					{
						OrderHistory::addLog('BASKET', $order->getId(), $isNew ? "BASKET_ITEM_ADD" : "BASKET_ITEM_UPDATE", $basketItem->getId(), $basketItem, $logFields, OrderHistory::SALE_ORDER_HISTORY_LOG_LEVEL_1);

						OrderHistory::addAction(
							'BASKET',
							$order->getId(),
							"BASKET_SAVED",
							$basketItem->getId(),
							$basketItem
						);
					}

				}
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

			if (isset($itemsFromDb[$basketItem->getId()]) && $basketItem->getQuantity() > 0)
				unset($itemsFromDb[$basketItem->getId()]);
		}


		if (!empty($filter))
		{
			$itemEventName = BasketItem::getEntityEventName();

			foreach ($itemsFromDb as $k => $v)
			{
				/** @var Main\Event $event */
				$event = new Main\Event('sale', "OnBefore".$itemEventName."Deleted", array(
						'VALUES' => $v,
				));
				$event->send();

				if ($v['TYPE'] == static::TYPE_SET)
				{
					if ($order && $order->getId() > 0)
					{
						OrderHistory::addLog('BASKET', $order->getId(), 'BASKET_ITEM_DELETE_BUNDLE', $k, null, array(
							"PRODUCT_ID" => $v["PRODUCT_ID"],
							"NAME" => $v["NAME"],
							"QUANTITY" => $v["QUANTITY"],
						), OrderHistory::SALE_ORDER_HISTORY_LOG_LEVEL_1);
					}

					Internals\BasketTable::deleteBundle($k);
				}
				else
				{
					if ($order && $order->getId() > 0)
					{
						OrderHistory::addLog('BASKET', $order->getId(), 'BASKET_ITEM_DELETED', $k, null, array(
							"PRODUCT_ID" => $v["PRODUCT_ID"],
							"NAME" => $v["NAME"],
							"QUANTITY" => $v["QUANTITY"],
						), OrderHistory::SALE_ORDER_HISTORY_LOG_LEVEL_1);
					}

					Internals\BasketTable::deleteWithItems($k);
				}

				/** @var Main\Event $event */
				$event = new Main\Event('sale', "On".$itemEventName."Deleted", array(
						'VALUES' => $v,
				));
				$event->send();

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
		{
			OrderHistory::collectEntityFields('BASKET', $order->getId());
		}


		if (!$order)
		{
			/** @var Main\Entity\Event $event */
			$event = new Main\Event('sale', EventActions::EVENT_ON_BASKET_SAVED, array(
				'ENTITY' => $this
			));
			$event->send();

			if ($event->getResults())
			{
				/** @var Main\EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == Main\EventResult::ERROR)
					{
						$errorMsg = new ResultError(Main\Localization\Loc::getMessage('SALE_EVENT_ON_BASKET_SAVED'), 'SALE_EVENT_ON_BASKET_SAVED');
						if ($eventResultData = $eventResult->getParameters())
						{
							if (isset($eventResultData) && $eventResultData instanceof ResultError)
							{
								/** @var ResultError $errorMsg */
								$errorMsg = $eventResultData;
							}
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
			$itemExists = ($basketItem->getField('PRODUCT_ID') == $productId && $basketItem->getField('MODULE') == $moduleId);
			if (!$itemExists)
			{
				if ($basketItem->isBundleParent())
				{
					if (static::getExistsItemInBundle($basketItem, $moduleId, $productId, $properties))
					{
						continue;
					}
				}
				else
				{
					continue;
				}
			}

			if ($itemExists)
			{
				/** @var BasketPropertiesCollection $basketPropertyCollection */
				$basketPropertyCollection = $basketItem->getPropertyCollection();
				if (!empty($properties) && is_array($properties))
				{
					if ($basketPropertyCollection->isPropertyAlreadyExists($properties))
					{
						return $basketItem;
					}
				}
				elseif (count($basketPropertyCollection) == 0)
				{
					return $basketItem;
				}
			}
		}


		return null;
	}

	/**
	 * @param BasketItem $item
	 *
	 * @return bool
	 */
	public function isItemExists(BasketItem $item)
	{
		$propertyList = array();
		/** @var BasketPropertiesCollection $basketPropertyCollection */
		if ($basketPropertyCollection = $item->getPropertyCollection())
		{
			/** @var BasketPropertyItem $basketPropertyItem */
			foreach($basketPropertyCollection as $basketPropertyItem)
			{
				$propertyList[$basketPropertyItem->getId()] = array(
					'ID' => $basketPropertyItem->getId(),
					'NAME' => $basketPropertyItem->getField('NAME'),
					'VALUE' => $basketPropertyItem->getField('VALUE'),
					'CODE' => $basketPropertyItem->getField('CODE'),
					'SORT' => $basketPropertyItem->getField('SORT'),
				);
			}
		}

		return (bool)($this->getExistsItem($item->getField('MODULE'), $item->getProductId(), $propertyList));
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
		$basket = static::create($this->getSiteId());

		if ($order = $this->getOrder())
		{
			$basket->setOrder($order);
		}

		$bundleIndexList = array();

		/** @var BasketItem $item */
		foreach ($this->collection as $item)
		{
			if (!$item->canBuy() || $item->isDelay())
				continue;

			$item->setCollection($basket);

			if ($item->isBundleParent())
			{
				if ($basketBundleIndexList = $this->getBundleIndexList())
				{
					foreach ($basketBundleIndexList as $childBasketItemCode => $parentBasketItemCode)
					{
						if ($item->getBasketCode() == $parentBasketItemCode)
						{
							$bundleIndexList[$childBasketItemCode] = $parentBasketItemCode;
						}
					}
				}
			}
			$basket->addItem($item);
		}

		if (!empty($bundleIndexList))
		{
			$basket->setBundleIndexList($bundleIndexList);
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
		$expired = new Main\Type\DateTime();
		$expired->add('-'.$days.'days');
		$expiredValue = $expired->format('Y-m-d H:i:s');

		/** @var Main\DB\Connection $connection */
		$connection = Main\Application::getConnection();
		/** @var Main\DB\SqlHelper $sqlHelper */
		$sqlHelper = $connection->getSqlHelper();

		$sqlExpiredDate = $sqlHelper->getDateToCharFunction("'" . $expiredValue . "'");

		if ($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$query = "DELETE FROM b_sale_basket_props WHERE
										BASKET_ID IN (
											 SELECT ID FROM b_sale_basket WHERE
											 			FUSER_ID IN (
																SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
																		b_sale_fuser.DATE_UPDATE < ".$sqlExpiredDate."
																		AND b_sale_fuser.USER_ID IS NULL
																)  AND ORDER_ID IS NULL
										) LIMIT " . static::BASKET_DELETE_LIMIT;
			$connection->queryExecute($query);

			$query = "DELETE FROM b_sale_basket	WHERE
									FUSER_ID IN (
     										SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
     												b_sale_fuser.DATE_UPDATE < ".$sqlExpiredDate."
													AND b_sale_fuser.USER_ID IS NULL
											) AND ORDER_ID IS NULL LIMIT " . static::BASKET_DELETE_LIMIT;
			$connection->queryExecute($query);
		}
		elseif ($connection instanceof Main\DB\MssqlConnection)
		{
			$query = "DELETE TOP (" . static::BASKET_DELETE_LIMIT . ") FROM b_sale_basket_props WHERE
										BASKET_ID IN (
     											SELECT id FROM b_sale_basket WHERE
     														FUSER_ID IN (
																	SELECT b_sale_fuser.id FROM b_sale_fuser
																	WHERE b_sale_fuser.DATE_UPDATE < ".$sqlExpiredDate."
																			AND b_sale_fuser.USER_ID IS NULL
															)  AND ORDER_ID IS NULL
											)";

			$connection->queryExecute($query);

			$query = "DELETE TOP (" . static::BASKET_DELETE_LIMIT . ") FROM b_sale_basket WHERE
									FUSER_ID IN (
     										SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
     												b_sale_fuser.DATE_UPDATE < ".$sqlExpiredDate."
													AND b_sale_fuser.USER_ID IS NULL
											) AND ORDER_ID IS NULL";
			$connection->queryExecute($query);
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$query = "DELETE FROM b_sale_basket_props WHERE
										BASKET_ID IN (
     											SELECT id FROM b_sale_basket WHERE
     														FUSER_ID IN (
																	SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
																			b_sale_fuser.DATE_UPDATE < ".$sqlExpiredDate."
																			AND b_sale_fuser.USER_ID IS NULL
																)  AND ORDER_ID IS NULL
											) AND ROWNUM <= ".static::BASKET_DELETE_LIMIT;

			$connection->queryExecute($query);

			$query = "DELETE FROM b_sale_basket WHERE
									FUSER_ID IN (
     										SELECT b_sale_fuser.id FROM b_sale_fuser WHERE
     												b_sale_fuser.DATE_UPDATE < ".$sqlExpiredDate."
													AND b_sale_fuser.USER_ID IS NULL
											)  AND ORDER_ID IS NULL AND ROWNUM <= ".static::BASKET_DELETE_LIMIT;

			$connection->queryExecute($query);
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
		$speed = intval($speed);
		$result = "\\Bitrix\\Sale\\Basket::deleteOldAgent(".intval(Main\Config\Option::get("sale", "delete_after", "30")).");";

		if ($speed > 0)
		{
			\CAgent::AddAgent($result, "sale", "N", $speed, "", "Y");
			$result = "";
		}

		if (isset($tmpUser))
		{
			unset($GLOBALS["USER"]);
		}

		return $result;
	}

	/**
	 * @return Result
	 */
	public function verify()
	{
		$result = new Result();

		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			$r = $basketItem->verify();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}
		return $result;
	}

	/**
	 * @internal
	 * @return array|bool
	 */
	public function getListOfFormatText()
	{
		$list = array();

		/** @var BasketItem $basketItem */
		foreach ($this->collection as $basketItem)
		{
			if ($basketItem->isBundleChild())
				continue;

			$basketItemData = $basketItem->getField("NAME");

			/** @var \Bitrix\Sale\BasketPropertiesCollection $basketPropertyCollection */
			if ($basketPropertyCollection = $basketItem->getPropertyCollection())
			{
				$basketItemDataProperty = "";
				/** @var \Bitrix\Sale\BasketPropertyItem $basketPropertyItem */
				foreach ($basketPropertyCollection as $basketPropertyItem)
				{
					if ($basketPropertyItem->getField('CODE') == "PRODUCT.XML_ID" || $basketPropertyItem->getField('CODE') == "CATALOG.XML_ID")
						continue;

					if (strval(trim($basketPropertyItem->getField('VALUE'))) == "")
						continue;


					$basketItemDataProperty .= (!empty($basketItemDataProperty) ? "; " : "").trim($basketPropertyItem->getField('NAME')).": ".trim($basketPropertyItem->getField('VALUE'));
				}

				if (!empty($basketItemDataProperty))
					$basketItemData .= " [".$basketItemDataProperty."]";
			}

			$measure = (strval($basketItem->getField("MEASURE_NAME")) != '') ? $basketItem->getField("MEASURE_NAME") : Loc::getMessage("SOA_SHT");
			$list[$basketItem->getBasketCode()] = $basketItemData." - ".BasketItem::formatQuantity($basketItem->getQuantity())." ".$measure.": ".SaleFormatCurrency($basketItem->getPrice(), $basketItem->getCurrency());

		}

		return !empty($list) ? $list : false;
	}
}