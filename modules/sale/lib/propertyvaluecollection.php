<?php
/**
 * Created by PhpStorm.
 * User: Alexey
 * Date: 09.01.2015
 * Time: 17:39
 */

namespace Bitrix\Sale;

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

	public function addItem(PropertyValue $property)
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

	public function onItemModify(PropertyValue $item, $name = null, $oldValue = null, $value = null)
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

		$props = PropertyValue::loadForOrder($order);

		/** @var PropertyValue $prop */
		foreach ($props as $prop)
		{
			$prop->setCollection($propertyCollection);
			$propertyCollection->addItem($prop);

			$propertyCollection->setAttributes($prop);
			$propertyCollection->propertyGroupMap[$prop->getGroupId() > 0 ? $prop->getGroupId() : 0][] = $prop;
		}

		return $propertyCollection;
	}


	public function dump($i)
	{
		$s = '';
		/** @var PropertyValue $item */
		foreach ($this->collection as $item)
		{
			$s .= $item->dump($i);
		}
		return $s;
	}

	/**
	 * @return Entity\Result
	 */
	public function save()
	{
		$result = new Entity\Result();

		$itemsFromDb = array();
		if ($this->getOrder()->getId() > 0)
		{
			$itemsFromDbList = Internals\OrderPropsValueTable::getList(
				array(
					"filter" => array("ORDER_ID" => $this->getOrder()->getId()),
					"select" => array("ID")
				)
			);
			while ($itemsFromDbItem = $itemsFromDbList->fetch())
				$itemsFromDb[$itemsFromDbItem["ID"]] = true;
		}

		/** @var PropertyValue $property */
		foreach ($this->collection as $property)
		{
			$r = $property->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$property->getId()]))
				unset($itemsFromDb[$property->getId()]);
		}

		foreach ($itemsFromDb as $k => $v)
			Internals\OrderPropsValueTable::delete($k);

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

//	function getValues()
//	{
//		$values = array();
//
//		foreach ($this->collection as $property)
//			if ($propertyId = $property->getId())
//				$values[$propertyId] = $property->getValue();
//
//		return $values;
//	}

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

//	function getErrors()
//	{
//		$errors = array();
//
//		foreach ($this->collection as $property)
//			if ($error = $property->getError())
//				$errors[$property->getId()] = $error;
//
//		return $errors;
//	}

//	function addErrorsTo(Entity\Result $result)
//	{
//		foreach ($this->collection as $property)
//			$property->addErrorTo($result);
//	}

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
}
