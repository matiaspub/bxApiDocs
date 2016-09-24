<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Config;
use Bitrix\Main\Entity;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

class BasketItem
	extends BasketItemBase
{
	/** @var null|BasketBundleCollection */
	protected $bundleCollection = null;

	/** @var null|BasketItem */
	protected $parentBasketItem = null;

	/** @var null|int */
	protected $parentId = null;

	/** @var bool|null */
	private $isNew = null;

	/** @var array */
	protected static $mapFields = array();


	/**
	 * @param Basket $basket
	 * @param string $moduleId
	 * @param int $productId
	 * @param null|string $basketCode
	 * @return BasketItem
	 */
	public static function create(Basket $basket, $moduleId, $productId, $basketCode = null)
	{
		$fields = array(
			"MODULE" => $moduleId,
			"PRODUCT_ID" => $productId,
		);

		$basketItem = new static($fields);

		if ($basketCode !== null)
		{
			$basketItem->internalId = $basketCode;
			if (strpos($basketCode, 'n') === 0)
			{
				$internalId = intval(substr($basketCode, 1));
				if ($internalId > static::$idBasket)
				{
					static::$idBasket = $internalId;
				}
			}
		}

		$basketItem->setCollection($basket);

		return $basketItem;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return Result|bool|void
	 */
	public function setField($name, $value)
	{
		if ($this->parentId == null
			&& ($name == "TYPE" && $value == static::TYPE_SET))
		{
			$this->parentId = $this->getBasketCode();
		}

		return parent::setField($name, $value);
	}

	/**
	 *
	 * @internal
	 *
	 * @param $name
	 * @param $value
	 * @throws ObjectNotFoundException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function initField($name, $value)
	{
		if ($this->parentId == null
			&& ($name == "TYPE" && $value == static::TYPE_SET)
		)
		{
			$this->parentId = $this->getBasketCode();
		}

		parent::initField($name, $value);

		if ($this->parentId == null && $name == "SET_PARENT_ID"
			&& intval($value) > 0 && $value != $this->getId())
		{
			/** @var BasketItem $parentBasketItem */
			if ($parentBasketItem = $this->getParentBasketItem())
			{
				$this->parentId = $parentBasketItem->getBasketCode();
			}
		}

	}

	static public function initFields(array $values)
	{
		if (!isset($values['BASE_PRICE']) || doubleval($values['BASE_PRICE']) == 0)
			$values['BASE_PRICE'] = $values['PRICE'] + $values['DISCOUNT_PRICE'];

		parent::initFields($values);
	}

	/**
	 * @return Entity\AddResult|Entity\UpdateResult
	 * @throws ArgumentException
	 * @throws ArgumentNullException
	 * @throws \Exception
	 */
	public function save()
	{
		$result = new Result();
		$id = $this->getId();
		$changedFields = $this->fields->getChangedValues();
		$this->isNew = ($id == 0);


		if (!empty($changedFields))
		{
			/** @var array $oldEntityValues */
			$oldEntityValues = $this->fields->getOriginalValues();

			/** @var Event $event */
			$event = new Event('sale', EventActions::EVENT_ON_BASKET_ITEM_BEFORE_SAVED, array(
				'ENTITY' => $this,
				'IS_NEW' => $this->isNew(),
				'VALUES' => $oldEntityValues,
			));
			$event->send();

			if ($event->getResults())
			{
				/** @var EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == EventResult::ERROR)
					{
						$errorMsg = new ResultError(Loc::getMessage('SALE_EVENT_ON_BEFORE_BASKET_ITEM_SAVED'), 'SALE_EVENT_ON_BEFORE_BASKET_ITEM_SAVED');
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
		}


		$fields = $this->fields->getValues();

		if ($this->isBundleParent())
		{
			$bundleBasketCollection = $this->getBundleCollection();
		}

		/** @var Basket $basket */
		if (!$basket = $this->getCollection())
		{
			throw new ObjectNotFoundException('Entity "Basket" not found');
		}

		if ($id > 0)
		{
			$fields = $changedFields;
			$includedOrderId = false;

			if (!isset($fields["ORDER_ID"]) || intval($fields["ORDER_ID"]) == 0)
			{
				$orderId = null;
				if ($this->getParentOrderId() > 0)
				{
					$orderId = $this->getParentOrderId();
				}

				if ($this->isBundleChild() && $orderId === null)
				{
					/** @var BasketItem $parentBasket */
					if (!$parentBasket = $this->getParentBasketItem())
					{
						throw new ObjectNotFoundException('Entity parent "BasketItem" not found');
					}
					$orderId = $parentBasket->getParentOrderId();
				}

				if (intval($orderId) > 0 && $this->getField('ORDER_ID') != $orderId)
				{
					$fields['ORDER_ID'] = $orderId;
					$includedOrderId = true;

					$this->setFieldNoDemand('ORDER_ID', $orderId);
				}
			}

			if (!empty($fields) && is_array($fields))
			{
				if (isset($fields["QUANTITY"]) && (floatval($fields["QUANTITY"]) == 0))
					return $result;

				$fields['DATE_UPDATE'] = new DateTime();
				$this->setFieldNoDemand('DATE_UPDATE', $fields['DATE_UPDATE']);

				$r = Internals\BasketTable::update($id, $fields);
				if (!$r->isSuccess())
				{
					if (($order = $basket->getOrder()) && $basket->getOrderId() > 0)
					{
						OrderHistory::addAction(
							'BASKET',
							$order->getId(),
							'BASKET_ITEM_UPDATE_ERROR',
							null,
							$this,
							array("ERROR" => $r->getErrorMessages())
						);
					}

					$result->addErrors($r->getErrors());
					return $result;
				}
				else
				{
					if ($includedOrderId && $r->getAffectedRowsCount() == 0)
					{
						$this->delete();

						if ($order = $basket->getOrder())
						{
							$oldErrorText = $order->getField('REASON_MARKED');
							$oldErrorText .= (strval($oldErrorText) != '' ? "\n" : "").Loc::getMessage("SALE_BASKET_ITEM_NOT_UPDATED_BECAUSE_NOT_EXISTS", array('#PRODUCT_NAME#' => $this->getField("NAME")));

							Internals\OrderTable::update($order->getId(), array(
								"MARKED" => "Y",
								"REASON_MARKED" => $oldErrorText
							));
						}
					}
				}

				if ($resultData = $r->getData())
					$result->setData($resultData);
			}
		}
		else
		{

			$fields['ORDER_ID'] = $this->getParentOrderId();
			$this->setFieldNoDemand('ORDER_ID', $fields['ORDER_ID']);

			$fields['DATE_INSERT'] = new DateTime();
			$this->setFieldNoDemand('DATE_INSERT', $fields['DATE_INSERT']);

			$fields['DATE_UPDATE'] = new DateTime();
			$this->setFieldNoDemand('DATE_UPDATE', $fields['DATE_UPDATE']);

			if (!$this->isBundleChild() && (!isset($fields["FUSER_ID"]) || intval($fields["FUSER_ID"]) <= 0))
			{
				$fUserId = intval($basket->getFUserId(true));
				/** @var Order $order */
				if (($order = $basket->getOrder()) || $fUserId > 0)
				{
					$fields["FUSER_ID"] = $fUserId;
				}
				else
				{
					throw new ArgumentNullException('FUSER_ID');
				}

			}

			/** @var Order $order */
			if ($order = $basket->getOrder())
			{
				if (!isset($fields["LID"]) || strval($fields["LID"]) == '')
				{
					$fields['LID'] = $order->getField('LID');
				}
			}
			else
			{
				if ($siteId = $basket->getSiteId())
				{
					$fields['LID'] = $siteId;
				}
			}

			if ($this->isBundleChild())
			{
				if (!$parentBasketItem = $this->getParentBasketItem())
				{
					throw new ObjectNotFoundException('Entity parent "BasketItem" not found');
				}

				$fields['LID'] = $parentBasketItem->getField('LID');

				if (!isset($fields["FUSER_ID"]) || intval($fields["FUSER_ID"]) <= 0)
				{
					$fields['FUSER_ID'] = intval($parentBasketItem->getField('FUSER_ID'));
				}

			}

			if (!isset($fields["LID"]) || strval(trim($fields["LID"])) == '')
				throw new ArgumentNullException('LID');

			if ($this->isBundleChild()
				&& (!isset($fields["SET_PARENT_ID"]) || (intval($fields["QUANTITY"]) <= 0))
			)
			{
				$fields["SET_PARENT_ID"] = $this->getParentBasketItemId();
				$this->setFieldNoDemand('SET_PARENT_ID', $fields['SET_PARENT_ID']);
			}

			if (!isset($fields["QUANTITY"]) || (floatval($fields["QUANTITY"]) == 0))
				return $result;

			if (!isset($fields["CURRENCY"]) || strval(trim($fields["CURRENCY"])) == '')
				throw new ArgumentNullException('CURRENCY');

			$r = Internals\BasketTable::add($fields);
			if (!$r->isSuccess())
			{
				if (($order = $basket->getOrder()) && $basket->getOrderId() > 0)
				{
					OrderHistory::addAction(
						'BASKET',
						$order->getId(),
						'BASKET_ITEM_ADD_ERROR',
						null,
						$this,
						array("ERROR" => $r->getErrorMessages())
					);
				}

				$result->addErrors($r->getErrors());
				return $result;
			}

			if ($resultData = $r->getData())
				$result->setData($resultData);

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);
			$this->setFieldNoDemand('LID', $fields['LID']);
			$this->setFieldNoDemand('FUSER_ID', $fields['FUSER_ID']);

			if ($basket->getOrder() && $basket->getOrderId() > 0)
			{
				OrderHistory::addAction(
					'BASKET',
					$order->getId(),
					'BASKET_ADDED',
					$id,
					$this
				);
			}

		}

		if ($id > 0)
		{
			$result->setId($id);
		}

		if ($this->isNew() || !empty($changedFields))
		{
			/** @var array $oldEntityValues */
			$oldEntityValues = $this->fields->getOriginalValues();

			/** @var Event $event */
			$event = new Event('sale', EventActions::EVENT_ON_BASKET_ITEM_SAVED, array(
				'ENTITY' => $this,
				'IS_NEW' => $this->isNew(),
				'VALUES' => $oldEntityValues,
			));
			$event->send();

			if ($event->getResults())
			{
				/** @var EventResult $eventResult */
				foreach($event->getResults() as $eventResult)
				{
					if($eventResult->getType() == EventResult::ERROR)
					{
						$errorMsg = new ResultError(Loc::getMessage('SALE_EVENT_ON_BASKET_ITEM_SAVED_ERROR'), 'SALE_EVENT_ON_BASKET_ITEM_SAVED_ERROR');
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

		// bundle

		if ($this->isBundleParent())
		{

			if (!empty($bundleBasketCollection))
			{
				if (!$order = $bundleBasketCollection->getOrder())
				{
					/** @var Basket $basketCollection */
					$basketCollection = $this->getCollection();
					if ($order = $basketCollection->getOrder())
					{
						$bundleBasketCollection->setOrder($order);
					}
				}

				$itemsFromDb = array();

				if (!$this->isNew())
				{
					$itemsFromDbList = Internals\BasketTable::getList(
						array(
							"filter" => array(
								"SET_PARENT_ID" => $id,
							),
							"select" => array("ID")
						)
					);
					while ($itemsFromDbItem = $itemsFromDbList->fetch())
					{
						if ($itemsFromDbItem["ID"] == $id)
							continue;

						$itemsFromDb[$itemsFromDbItem["ID"]] = true;
					}
				}


				/** @var BasketItem $bundleBasketItem */
				foreach ($bundleBasketCollection as $bundleBasketItem)
				{
					$r = $bundleBasketItem->save();
					if (!$r->isSuccess())
						$result->addErrors($r->getErrors());

					if (isset($itemsFromDb[$bundleBasketItem->getId()]))
						unset($itemsFromDb[$bundleBasketItem->getId()]);
				}

				foreach ($itemsFromDb as $k => $v)
					Internals\BasketTable::delete($k);

			}

		}

		/** @var BasketPropertiesCollection $basketPropertyCollection */
		$basketPropertyCollection = $this->getPropertyCollection();
		$r = $basketPropertyCollection->save();
		if (!$r->isSuccess())
			$result->addErrors($r->getErrors());

		if ($eventName = static::getEntityEventName())
		{
			/** @var array $oldEntityValues */
			$oldEntityValues = $this->fields->getOriginalValues();

			if (!empty($oldEntityValues))
			{
				/** @var Event $event */
				$event = new Event('sale', 'On'.$eventName.'EntitySaved', array(
					'ENTITY' => $this,
					'VALUES' => $oldEntityValues,
				));
				$event->send();
			}
		}

		$this->fields->clearChanged();

		return $result;
	}

	/**
	 *
	 */
	public function delete()
	{
		$result = new Result();
		/** @var Basket $basket */
		if (!$basket = $this->getCollection())
		{
			throw new ObjectNotFoundException('Entity "Basket" not found');
		}

		$eventName = static::getEntityEventName();

		/** @var array $oldEntityValues */
		$oldEntityValues = $this->fields->getOriginalValues();

		/** @var Event $event */
		$event = new Event('sale', "OnBefore".$eventName."EntityDeleted", array(
				'ENTITY' => $this,
				'VALUES' => $oldEntityValues,
		));
		$event->send();

		if ($event->getResults())
		{
			/** @var EventResult $eventResult */
			foreach($event->getResults() as $eventResult)
			{
				if($eventResult->getType() == EventResult::ERROR)
				{
					$errorMsg = new ResultError(Loc::getMessage('SALE_EVENT_ON_BEFORE_'.ToUpper($eventName).'_ENTITY_DELETED_ERROR'), 'SALE_EVENT_ON_BEFORE_'.ToUpper($eventName).'_ENTITY_DELETED_ERROR');
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

		/** @var Order $order */
		if ($order = $basket->getOrder())
		{
			/** @var ShipmentCollection $shipmentCollection */
			if ($shipmentCollection = $order->getShipmentCollection())
			{
				/** @var Shipment $shipment */
				foreach ($shipmentCollection as $shipment)
				{
					if ($shipment->isSystem())
					{
						continue;
					}

					/** @var ShipmentItemCollection $shipmentItemCollection */
					if ($shipmentItemCollection = $shipment->getShipmentItemCollection())
					{
						if ($shipmentItemCollection->getItemByBasketCode($this->getBasketCode()) && $shipment->isShipped())
						{
							$result->addError( new ResultError(Loc::getMessage('SALE_BASKET_ITEM_REMOVE_IMPOSSIBLE_BECAUSE_SHIPPED', array(
																		'#PRODUCT_NAME#' => $this->getField('NAME')
																)), 'SALE_BASKET_ITEM_REMOVE_IMPOSSIBLE_BECAUSE_SHIPPED') );
							return $result;
						}
					}
				}
			}
		}

		$r = $this->setField("QUANTITY", 0);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		$bundleCollection = null;
		if ($this->isBundleParent())
		{
			/** @var Basket $bundleCollection */
			$bundleCollection = $this->getBundleCollection();
		}

		/** @var Result $r */
		$r = parent::delete();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		/** @var array $oldEntityValues */
		$oldEntityValues = $this->fields->getOriginalValues();

		/** @var Event $event */
		$event = new Event('sale', "On".$eventName."EntityDeleted", array(
				'ENTITY' => $this,
				'VALUES' => $oldEntityValues,
		));
		$event->send();

		if ($event->getResults())
		{
			/** @var EventResult $eventResult */
			foreach($event->getResults() as $eventResult)
			{
				if($eventResult->getType() == EventResult::ERROR)
				{
					$errorMsg = new ResultError(Loc::getMessage('SALE_EVENT_ON_'.ToUpper($eventName).'_ENTITY_DELETED_ERROR'), 'SALE_EVENT_ON_'.ToUpper($eventName).'_ENTITY_DELETED_ERROR');
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

		if ($bundleCollection !== null)
		{
			/** @var BasketItem $bundleBasketItem */
			foreach ($bundleCollection as $bundleBasketItem)
			{
				/** @var Result $r */
				$r = $bundleBasketItem->delete();
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}


	/**
	 * @param array $fields
	 * @return array
	 */
	static public function clearBundleItemFields(array $fields)
	{
		$removeFields = array(
			'ID',
			'ITEM_ID',
			'SORT',
			'MEASURE',
			'PROPS',
			'DISCOUNT_PERCENT',
			'SET_DISCOUNT_PERCENT',
			'IBLOCK_ID',
			'IBLOCK_SECTION_ID',
			'PREVIEW_PICTURE',
			'DETAIL_PICTURE',
			'PROPS',
		);

		foreach ($removeFields as $field)
		{
			if (array_key_exists($field, $fields))
				unset($fields[$field]);
		}

		return $fields;
	}


	/**
	 * @return int|null
	 */
	private function getParentOrderId()
	{
		/** @var PaymentCollection $collection */
		if ($collection = $this->getCollection())
		{
			if ($order = $collection->getOrder())
			{
				return $order->getId();
			}
		}

		return null;
	}

	/**
	 * @return int
	 */
	public function getParentId()
	{
		if ($this->parentId === null)
		{
			$parentBasketId = $this->getField('SET_PARENT_ID');
			/** @var BasketItem $parentBasketItem */
			$parentBasketItem = $this->getParentBasketItem();

			if($parentBasketId > 0 && $parentBasketId != $this->getId() && $parentBasketItem)
			{
				$this->parentId = $parentBasketItem->getBasketCode();
			}
			elseif($this->getField('TYPE') > 0)
			{
				$this->parentId = $this->getBasketCode();
			}
			else
			{
				/** @var BasketItem $parentBasketItem */
				if ($parentBasketItem = $this->getParentBasketItem())
				{
					$this->parentId = $parentBasketItem->getBasketCode();
				}
			}
		}
		return $this->parentId;
	}

	/**
	 * @return BasketItem|null
	 */
	public function getParentBasketItem()
	{
		if ($this->parentBasketItem === null)
		{

			$parentId = $this->getField('SET_PARENT_ID');

			/** @var Basket $collection */
			$collection = $this->getCollection();

			if ($parentId > 0 && $parentId != $this->getId())
			{
				/** @var BasketItem parentBasketItem */
				$this->parentBasketItem = $collection->getItemById($parentId);
			}
			elseif($this->parentId > 0)
			{
				$this->parentBasketItem = $collection->getItemByBasketCode($this->parentId);
			}

			if ($collection instanceof BasketBundleCollection &&  !$this->parentBasketItem)
			{
				$this->parentBasketItem = $collection->getParentBasketItem();
			}
		}

		return $this->parentBasketItem;
	}

	/**
	 * @return bool|int
	 */
	public function getParentBasketItemId()
	{
		if ($parentBasketItem = $this->getParentBasketItem())
		{
			return $parentBasketItem->getId();
		}
		return null;
	}

	/**
	 * @return BasketPropertiesCollection
	 */
	public function getPropertyCollection()
	{
		if (empty($this->propertyCollection))
		{
			$this->propertyCollection = BasketPropertiesCollection::load($this);
		}
		return $this->propertyCollection;
	}

	/**
	 * @return bool
	 */
	public function isBundleParent()
	{
		if ($this->getParentId() == $this->getBasketCode())
		{
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isBundleChild()
	{
		$parentId = $this->getParentId();
		if ((strval($parentId) != '' && $parentId != $this->getBasketCode())
			|| (intval($this->getField('SET_PARENT_ID')) > 0 && intval($this->getField('SET_PARENT_ID')) != $this->getId()) )
		{
			return true;
		}

		return false;
	}

	/**
	 * @return array|bool
	 * @throws ObjectNotFoundException
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function getBundleBaseQuantity()
	{
		if ($this->isBundleParent())
		{
			/** @var BasketBundleCollection $bundleCollection */
			if (!($bundleCollection = $this->getBundleCollection()))
			{
				throw new ObjectNotFoundException('Entity "BasketBundleCollection" not found');
			}

			$bundleChildList = array();
			$result = array();

			$originalQuantity = $this->getQuantity();
			$originalValues = $this->fields->getOriginalValues();
			if (array_key_exists('QUANTITY', $originalValues) && $originalValues['QUANTITY'] !== null)
			{
				$originalQuantity = $originalValues['QUANTITY'];
			}
			/** @var BasketItem $bundleBasketItem */
			foreach ($bundleCollection as $bundleBasketItem)
			{
				$originalBundleQuantity = $bundleBasketItem->getQuantity();
				$originalBundleValues = $bundleBasketItem->getFields()->getOriginalValues();
				if (array_key_exists('QUANTITY', $originalBundleValues)  && $originalBundleValues['QUANTITY'] !== null)
				{
					$originalBundleQuantity = $originalBundleValues['QUANTITY'];
				}

				$bundleChildList[]["ITEMS"][] = array(
						"PRODUCT_ID" => $bundleBasketItem->getProductId(),
						"QUANTITY" => $originalBundleQuantity / $originalQuantity
				);

			}

			if (empty($bundleChildList))
				return false;

			foreach ($bundleChildList as $bundleBasketListDat)
			{
				foreach ($bundleBasketListDat["ITEMS"] as $bundleDat)
				{
					$result[$bundleDat['PRODUCT_ID']] = $bundleDat['QUANTITY'];
				}
			}

			return $result;
		}

		return false;
	}

	/**
	 * @return Basket
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function getBundleChildElements()
	{
		if ($this->bundleCollection !== null)
		{
			$this->bundleCollection = $this->loadBundleChildElements();
		}

		return $this->bundleCollection;
	}

	/**
	 * @return BasketBundleCollection
	 */
	public function getBundleCollection()
	{
		if ($this->bundleCollection === null)
		{
			if ($this->getId() > 0)
			{
				$this->bundleCollection = $this->loadBundleCollection();
			}
			else
			{
				$this->bundleCollection = $this->loadBundleChildElements();
			}
		}
		return $this->bundleCollection;
	}

	public function createBundleCollection()
	{
		if ($this->bundleCollection === null)
		{
			/** @var Basket $basket */
			$basket = $this->getCollection();

			$this->bundleCollection = BasketBundleCollection::create($basket->getSiteId());
		}
		return $this->bundleCollection;
	}

	/**
	 * @return bool
	 */
	protected function loadBundleCollection()
	{
		return Basket::loadBundleChild($this);
	}

	/**
	 * @return bool
	 */
	protected function loadBundleChildElements()
	{
		$bundleChildList = Provider::getSetItems($this);

		if (empty($bundleChildList))
		{
			return null;
		}

		/** @var Basket $baseBasketCollection */
		$baseBasketCollection = $this->getCollection();

		/** @var Order $order */
		$order = $baseBasketCollection->getOrder();

		/** @var Basket $bundleCollection */
		$bundleCollection = BasketBundleCollection::create($baseBasketCollection->getSiteId());

		if ($order !== null)
		{
			$bundleCollection->setOrder($order);
		}

		foreach ($bundleChildList as $bundleBasketListDat)
		{
			foreach ($bundleBasketListDat["ITEMS"] as $bundleDat)
			{
				$bundleFields = static::clearBundleItemFields($bundleDat);
				$bundleFields['CURRENCY'] = $this->getCurrency();

				if ($this->getId() > 0)
				{
					$bundleFields['SET_PARENT_ID'] = $this->getId();
				}

				/** @var BasketItem $basketItem */
				$bundleBasketItem = BasketItem::create($bundleCollection, $bundleFields['MODULE'], $bundleFields['PRODUCT_ID']);

				if (!empty($bundleDat["PROPS"]) && is_array($bundleDat["PROPS"]))
				{
					/** @var BasketPropertiesCollection $property */
					$property = $bundleBasketItem->getPropertyCollection();
					$property->setProperty($bundleDat["PROPS"]);
				}

				$bundleCollection->isItemExists($bundleBasketItem);

				$bundleQuantity = $bundleFields['QUANTITY'] * $this->getQuantity();
				unset($bundleFields['QUANTITY']);

				$bundleBasketItem->parentBasketItem = $this;
				$bundleBasketItem->parentId = $this->getBasketCode();

				$bundleBasketItem->setFieldsNoDemand($bundleFields);
				$bundleBasketItem->setField('QUANTITY', $bundleQuantity);


				$bundleCollection->addItem($bundleBasketItem);
			}
		}

		if ($productList = Provider::getProductData($bundleCollection, array('QUANTITY', 'PRICE')))
		{
			foreach ($productList as $productBasketCode => $productDat)
			{
				if ($bundleBasketItem = $bundleCollection->getItemByBasketCode($productBasketCode))
				{
					unset($productDat['DISCOUNT_LIST']);
					$bundleBasketItem->setFieldsNoDemand($productDat);
				}
			}
		}

		$this->bundleCollection = $bundleCollection;
		return $bundleCollection;
	}

	/**
	 * @return bool
	 */
	public function isEmptyItem()
	{
		return (strval($this->getField('MODULE')) == '');
	}

	/**
	 * @param string $name
	 * @param null $oldValue
	 * @param null $value
	 * @throws ObjectNotFoundException
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{
		if ($this->getId() > 0)
		{
			$fields = array();
			/** @var Basket $basket */
			if (!$basket = $this->getCollection())
			{
				throw new ObjectNotFoundException('Entity "Basket" not found');
			}

			if ($basket->getOrder() && $basket->getOrderId() > 0)
			{
				if ($name == "QUANTITY")
				{
					if (floatval($value) == 0)
					{
						return;
					}
					$fields = array(
						'PRODUCT_ID' => $this->getProductId(),
						'QUANTITY' => $this->getQuantity(),
						'NAME' => $this->getField('NAME'),
					);
				}

				OrderHistory::addField(
					'BASKET',
					$basket->getOrderId(),
					$name,
					$oldValue,
					$value,
					$this->getId(),
					$this,
					$fields);
			}
		}
	}

	/**
	 * @param $quantity
	 *
	 * @return float
	 * @throws ArgumentNullException
	 */
	public static function formatQuantity($quantity)
	{
		$format = Config\Option::get('sale', 'format_quantity', 'AUTO');
		if ($format == 'AUTO' || intval($format) <= 0)
		{
			$quantity = round($quantity, SALE_VALUE_PRECISION);
		}
		else
		{
			$quantity = number_format($quantity, intval($format), '.', '');
		}

		return $quantity;
	}

	/**
	 * @return array
	 */
	public static function getAllFields()
	{
		if (empty(static::$mapFields))
		{
			static::$mapFields = parent::getAllFieldsByMap(Internals\BasketTable::getMap());
		}
		return static::$mapFields;
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return BasketItem
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$basketItemClone = clone $this;
		$basketItemClone->isClone = true;

		/** @var Internals\Fields $fields */
		if ($fields = $this->fields)
		{
			$basketItemClone->fields = $fields->createClone($cloneEntity);
		}

		/** @var Internals\Fields $calculatedFields */
		if ($calculatedFields = $this->calculatedFields)
		{
			$basketItemClone->calculatedFields = $calculatedFields->createClone($cloneEntity);
		}

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $basketItemClone;
		}

		/** @var BasketItem $parentbasketItem */
		if ($parentbasketItem = $this->parentBasketItem)
		{
			if (!$cloneEntity->contains($parentbasketItem))
			{
				$cloneEntity[$parentbasketItem] = $parentbasketItem->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($parentbasketItem))
			{
				$basketItemClone->parentBasketItem = $cloneEntity[$parentbasketItem];
			}
		}

		/** @var BasketPropertiesCollection $propertyCollection */
		if ($propertyCollection = $this->getPropertyCollection())
		{
			if (!$cloneEntity->contains($propertyCollection))
			{
				$cloneEntity[$propertyCollection] = $propertyCollection->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($propertyCollection))
			{
				$basketItemClone->propertyCollection = $cloneEntity[$propertyCollection];
			}
		}

		if ($collection = $this->getCollection())
		{
			if (!$cloneEntity->contains($collection))
			{
				$cloneEntity[$collection] = $collection->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($collection))
			{
				$basketItemClone->collection = $cloneEntity[$collection];
			}
		}

		/** @var BasketBundleCollection $bundleCollection */
		if ($bundleCollection = $this->getBundleCollection())
		{
			if (!$cloneEntity->contains($bundleCollection))
			{
				$cloneEntity[$bundleCollection] = $bundleCollection->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($bundleCollection))
			{
				$basketItemClone->bundleCollection = $cloneEntity[$bundleCollection];
			}
		}
		
		

		return $basketItemClone;
	}


	/**
	 * @internal
	 * @return null|bool
	 */
	public function isNew()
	{
		return $this->isNew;
	}

	/**
	 * @return Result
	 */
	public function verify()
	{
		$result = new Result();

		if ($basketPropertyCollection = $this->getPropertyCollection())
		{
			$r = $basketPropertyCollection->verify();
			if (!$r->isSuccess())
			{
				if ($r instanceof ResultWarning)
				{
					$result->addWarnings($r->getErrors());
				}
				else
				{
					$result->addErrors($r->getErrors());
				}
			}
		}
		
		return $result;
	}

}