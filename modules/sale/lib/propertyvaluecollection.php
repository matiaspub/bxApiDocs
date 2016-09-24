<?php
/**
 * Created by PhpStorm.
 * User: Alexey
 * Date: 09.01.2015
 * Time: 17:39
 */

namespace Bitrix\Sale;

use Bitrix\Main;
use	Bitrix\Sale\Internals\Input,
	Bitrix\Sale\Internals\OrderPropsGroupTable,
	Bitrix\Main\ArgumentOutOfRangeException,
	Bitrix\Main\Entity,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PropertyValueCollection
	extends Internals\EntityCollection
{
	/** @var Order */
	protected $order;

	private $attributes = array(
		'IS_EMAIL'        => null,
		'IS_PAYER'        => null,
		'IS_LOCATION'     => null,
		'IS_LOCATION4TAX' => null,
		'IS_PROFILE_NAME' => null,
		'IS_ZIP'          => null,
		'IS_PHONE'        => null,
		'IS_ADDRESS'      => null,
	);

	private $propertyGroupMap = array();
	private $propertyGroups = array();

	/**
	 * @return Order
	 */
	protected function getEntityParent()
	{
		return $this->getOrder();
	}

	public function createItem(array $prop)
	{
		$property = PropertyValue::create($this, $prop);
		$this->addItem($property);

		return $property;
	}

	public function addItem(Internals\CollectableEntity $property)
	{
		/** @var PropertyValue $property */
		$property = parent::addItem($property);

		$order = $this->getOrder();
		return $order->onPropertyValueCollectionModify(EventActions::ADD, $property);
	}

	/**
	 * @internal
	 *
	 * @param $index
	 * @return bool
	 */
	public function deleteItem($index)
	{
		$oldItem = parent::deleteItem($index);

		/** @var Order $order */
		$order = $this->getOrder();
		return $order->onPropertyValueCollectionModify(EventActions::DELETE, $oldItem);
	}

	/**
	 * @param Internals\CollectableEntity $item
	 * @param null $name
	 * @param null $oldValue
	 * @param null $value
	 *
	 * @return bool
	 */
	public function onItemModify(Internals\CollectableEntity $item, $name = null, $oldValue = null, $value = null)
	{
		$this->setAttributes($item);

		/** @var Order $order */
		$order = $this->getOrder();
		return $order->onPropertyValueCollectionModify(EventActions::UPDATE, $item, $name, $oldValue, $value);
	}

	static public function onOrderModify($name, $oldValue, $value)
	{
		return new Result();
	}

	public function getOrder()
	{
		return $this->order;
	}

	/**
	 * @param OrderBase $order
	 */
	public function setOrder(OrderBase $order)
	{
		$this->order = $order;
	}

	public static function load(OrderBase $order)
	{
		/** @var PropertyValueCollection $propertyCollection */
		$propertyCollection = new static();
		$propertyCollection->setOrder($order);

		static $groups = array();

		$personTypeId = $order->getPersonTypeId();

		if (empty($groups[$personTypeId]))
		{

			$groupRes = OrderPropsGroupTable::getList(array(
				'select' => array('ID', 'NAME', 'PERSON_TYPE_ID'),
				'filter' => array('PERSON_TYPE_ID' => $order->getPersonTypeId()),
				'order'  => array('SORT' => 'ASC'),
			));
			while ($row = $groupRes->fetch())
			{
				$groups[$personTypeId][$row['ID']] = $row;
			}
		}

		$props = PropertyValue::loadForOrder($order);

		/** @var PropertyValue $prop */
		foreach ($props as $prop)
		{
			$prop->setCollection($propertyCollection);
			$propertyCollection->addItem($prop);

			$propertyCollection->setAttributes($prop);
			$propertyCollection->propertyGroupMap[$prop->getGroupId() > 0 && isset($groups[$personTypeId][$prop->getGroupId()])? $prop->getGroupId() : 0][] = $prop;
		}

		return $propertyCollection;
	}

	/**
	 * @return Entity\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectNotFoundException
	 * @throws \Exception
	 */
	public function save()
	{
		$result = new Entity\Result();

		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		$itemsFromDb = array();
		if ($order->getId() > 0)
		{
			$itemsFromDbList = Internals\OrderPropsValueTable::getList(
				array(
					"filter" => array("ORDER_ID" => $this->getOrder()->getId()),
					"select" => array("ID", "NAME", "CODE", "VALUE")
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
				$itemsFromDb[$itemsFromDbItem["ID"]] = $itemsFromDbItem;
		}

		$isChanged = false;

		/** @var PropertyValue $property */
		foreach ($this->collection as $property)
		{
			$isNew = (bool)($property->getId() <= 0);
			if (!$isChanged && $property->isChanged())
			{
				$isChanged = true;
			}

			if ($order->getId() > 0 && $isChanged)
			{
				$logFields = array(
					"NAME" => $property->getField("NAME"),
					"VALUE" => $property->getField("VALUE"),
					"CODE" => $property->getField("CODE"),
				);

				if (!$isNew)
				{
					$fields = $property->getFields();
					$originalValues = $fields->getOriginalValues();
					if (array_key_exists("NAME", $originalValues))
						$logFields['OLD_NAME'] = $originalValues["NAME"];

					if (array_key_exists("VALUE", $originalValues))
						$logFields['OLD_VALUE'] = $originalValues["VALUE"];

					if (array_key_exists("CODE", $originalValues))
						$logFields['OLD_CODE'] = $originalValues["CODE"];
				}

			}

			$r = $property->save();
			if ($r->isSuccess())
			{
				if ($order->getId() > 0)
				{
					if ($isChanged)
					{
						OrderHistory::addLog('PROPERTY', $order->getId(), $isNew ? 'PROPERTY_ADD' : 'PROPERTY_UPDATE', $property->getId(), $property,
										 $logFields, OrderHistory::SALE_ORDER_HISTORY_LOG_LEVEL_1);
					}
				}
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

			if (isset($itemsFromDb[$property->getId()]))
				unset($itemsFromDb[$property->getId()]);
		}

		if ($result->isSuccess() && $order->getId() > 0 && $isChanged)
		{
			OrderHistory::addAction(
				'PROPERTY',
				$order->getId(),
				"PROPERTY_SAVED"
			);
		}

		$itemEventName = PropertyValue::getEntityEventName();
		foreach ($itemsFromDb as $k => $v)
		{
			/** @var Main\Event $event */
			$event = new Main\Event('sale', "OnBefore".$itemEventName."Deleted", array(
					'VALUES' => $v,
			));
			$event->send();

			Internals\OrderPropsValueTable::delete($k);

			/** @var Main\Event $event */
			$event = new Main\Event('sale', "On".$itemEventName."Deleted", array(
					'VALUES' => $v,
			));
			$event->send();

			if ($order->getId() > 0)
			{
				OrderHistory::addAction('PROPERTY', $order->getId(), 'PROPERTY_REMOVE', $k, null, array(
					"NAME" => $v['NAME'],
					"CODE" => $v['CODE'],
					"VALUE" => $v['VALUE'],
				));
			}
		}

		if ($order->getId() > 0)
		{
			OrderHistory::collectEntityFields('PROPERTY', $order->getId());
		}

		return $result;
	}

	static function initJs()
	{
		Input\Manager::initJs();
		\CJSCore::RegisterExt('SaleOrderProperties', array(
			'js'   => '/bitrix/js/sale/orderproperties.js',
			'lang' => '/bitrix/modules/sale/lang/'.LANGUAGE_ID.'/lib/propertyvaluecollection.php',
			'rel'  => array('input'),
		));
		\CJSCore::Init(array('SaleOrderProperties'));
	}

	protected function  __construct()
	{
	}

	private function setAttributes(PropertyValue $propValue)
	{
		$prop = $propValue->getProperty();
		foreach ($this->attributes as $k => &$v)
		{
			if ($prop[$k] == 'Y')
				$v = $propValue;
		}
	}

	/**
	 * @param $name
	 * @return PropertyValue
	 * @throws ArgumentOutOfRangeException
	 */
	public function getAttribute($name)
	{
		if (!array_key_exists($name, $this->attributes))
			throw new ArgumentOutOfRangeException("name");

		if ($this->attributes[$name] !== null)
			return $this->attributes[$name];

		return null;
	}

	public function getUserEmail()
	{
		return $this->getAttribute('IS_EMAIL');
	}

	public function getPayerName()
	{
		return $this->getAttribute('IS_PAYER');
	}

	public function getDeliveryLocation()
	{
		return $this->getAttribute('IS_LOCATION');
	}

	public function getTaxLocation()
	{
		return $this->getAttribute('IS_LOCATION4TAX');
	}

	public function getProfileName()
	{
		return $this->getAttribute('IS_PROFILE_NAME');
	}

	public function getDeliveryLocationZip()
	{
		return $this->getAttribute('IS_ZIP');
	}

	public function getPhone()
	{
		return $this->getAttribute('IS_PHONE');
	}

	public function getAddress()
	{
		return $this->getAttribute('IS_ADDRESS');
	}

	public function setValuesFromPost($post, $files)
	{
		$post = Input\File::getPostWithFiles($post, $files);

		$result = new Result();

		/** @var PropertyValue $property */
		foreach ($this->collection as $property)
		{
			$r = $property->setValueFromPost($post);
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param $fields
	 * @param $files
	 * @param $skipUtils
	 *
	 * @return Result
	 */
	public function checkErrors($fields, $files, $skipUtils = false)
	{
		$fields = Input\File::getPostWithFiles($fields, $files);

		$result = new Result();

		/** @var PropertyValue $property */
		foreach ($this->collection as $property)
		{
			if ($skipUtils && $property->isUtil())
				continue;

			$propertyData = $property->getProperty();

			$key = isset($propertyData["ID"]) ? $propertyData["ID"] : "n".$property->getId();
			$value = isset($fields['PROPERTIES'][$key]) ? $fields['PROPERTIES'][$key] : null;

			if (!isset($fields['PROPERTIES'][$key]))
			{
				$value = $property->getValue();
			}

			$r = $property->checkValue($key, $value);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param array $rules
	 * @param array $fields
	 *
	 * @return Result
	 */
	public function checkRequired(array $rules, array $fields)
	{
		$result = new Result();

		/** @var PropertyValue $property */
		foreach ($this->collection as $property)
		{
			$propertyData = $property->getProperty();

			$key = isset($propertyData["ID"]) ? $propertyData["ID"] : "n".$property->getId();

			if (!in_array($key, $rules))
			{
				continue;
			}

			$value = isset($fields['PROPERTIES'][$key]) ? $fields['PROPERTIES'][$key] : null;
			if (!isset($fields['PROPERTIES'][$key]))
			{
				$value = $property->getValue();
			}

			$r = $property->checkRequiredValue($key, $value);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	public function getGroups()
	{
		if (empty($this->propertyGroups))
		{
			$result = OrderPropsGroupTable::getList(array(
				'select' => array('ID', 'NAME', 'PERSON_TYPE_ID'),
				'filter' => array('=ID' => array_keys($this->propertyGroupMap)),
				'order'  => array('SORT' => 'ASC'),
			));
			while ($row = $result->fetch())
				$this->propertyGroups[] = $row;

			if ($unknown = $this->propertyGroupMap[0])
				$this->propertyGroups[] = array('NAME' => Loc::getMessage('SOP_UNKNOWN_GROUP'), 'ID' => 0);
		}

		return $this->propertyGroups;
	}

	public function getGroupProperties($groupId)
	{
		return $this->propertyGroupMap[$groupId];
	}

	public function getArray()
	{
		$groups = $this->getGroups();

		$properties = array();

		/** @var PropertyValue $property */
		foreach ($this->collection as $k => $property)
		{
			$p = $property->getProperty();

			if (!isset($p["ID"]))
				$p["ID"] = "n".$property->getId();

			$value = $property->getValue();

			$value = $property->getValueId() ? $value : ($value ? $value : $p['DEFAULT_VALUE']);

			$value = array_values(Input\Manager::asMultiple($p, $value));

			$p['VALUE'] = $value;

			$properties []= $p;
		}

		return array('groups' => $groups, 'properties' => $properties);
	}

	/**
	 * @param $orderPropertyId
	 * @return PropertyValue
	 */
	public function getItemByOrderPropertyId($orderPropertyId)
	{
		/** @var PropertyValue $property */
		foreach ($this->collection as $k => $property)
		{
			if($property->getField('ORDER_PROPS_ID') == $orderPropertyId)
				return $property;
		}
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return PropertyValueCollection
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$propertyValueCollectionClone = clone $this;
		$propertyValueCollectionClone->isClone = true;

		if ($this->order)
		{
			if ($cloneEntity->contains($this->order))
			{
				$propertyValueCollectionClone->order = $cloneEntity[$this->order];
			}
		}

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $propertyValueCollectionClone;
		}

		/**
		 * @var int key
		 * @var PropertyValue $propertyValue
		 */
		foreach ($propertyValueCollectionClone->collection as $key => $propertyValue)
		{
			if (!$cloneEntity->contains($propertyValue))
			{
				$cloneEntity[$propertyValue] = $propertyValue->createClone($cloneEntity);
			}

			$propertyValueCollectionClone->collection[$key] = $cloneEntity[$propertyValue];
		}

		return $propertyValueCollectionClone;
	}
}
