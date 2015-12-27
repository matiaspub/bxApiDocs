<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Sale;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BasketPropertiesCollection
	extends Internals\EntityCollection
{
	/** @var BasketItem */
	protected $basketItem;

	protected function getEntityParent()
	{
		return $this->getBasketItem();
	}

	/**
	 * @param BasketItem $basketItem
	 */
	public function setBasketItem(BasketItem $basketItem)
	{
		$this->basketItem = $basketItem;
	}

	/**
	 * @return BasketItem
	 */
	public function getBasketItem()
	{
		return $this->basketItem;
	}

	/**
	 * @return bool|int
	 */
	public function getBasketId()
	{
		if ($this->basketItem)
		{
			return $this->basketItem->getId();
		}

		return false;
	}

	/**
	 * @param BasketItem $basketItem
	 * @return static
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function load(BasketItem $basketItem)
	{

		$basketPropertyCollection = new static();
		$basketPropertyCollection->basketItem = $basketItem;
		
		if ($basketItem->getId() <= 0)
			return $basketPropertyCollection;

		$res = Internals\BasketPropertyTable::getList(array(
	         'order' => array(
	             "SORT" => "ASC",
	             "ID" => "ASC"),
	         'filter' => array(
	             "BASKET_ID" => $basketItem->getId(),
	         ),
			));

		while($property = $res->fetch())
		{
			$basketPropertyItem = BasketPropertyItem::create($basketPropertyCollection);
			$basketPropertyItem->initFields($property);

			$basketPropertyCollection->addItem($basketPropertyItem);
		}

		return $basketPropertyCollection;

	}

	public function createItem()
	{
		$basketPropertyItem = BasketPropertyItem::create($this);
		$this->addItem($basketPropertyItem);

		return $basketPropertyItem;
	}

	/**
	 * @param array $values
	 */
	public function setProperty(array $values)
	{
		foreach ($values as $value)
		{
			if (!is_array($value))
				continue;

			$propertyItem = false;
			if (isset($value['ID']) && intval($value['ID']) > 0)
			{
				$propertyItem = $this->getItemById($value['ID']);
			}
			else
			{
				$propertyItem = $this->getPropertyItemByValue($value);
			}

			if (!$propertyItem)
			{
				$propertyItem = $this->createItem($this);
			}

			$fields = array();
			foreach ($value as $k => $v)
			{
				if (strpos($k, '~') === false)
				{
					$fields[$k] = $v;
				}
			}

			$propertyItem->setFields($fields);
		}
	}

	/**
	 *
	 */
	public function save()
	{
		$result = new Sale\Result();

		$itemsFromDb = array();

		$itemsFromDbList = Internals\BasketPropertyTable::getList(
			array(
				"filter" => array("BASKET_ID" => $this->getBasketItem()->getId()),
				"select" => array("ID")
			)
		);
		while ($itemsFromDbItem = $itemsFromDbList->fetch())
			$itemsFromDb[$itemsFromDbItem["ID"]] = true;

		/** @var BasketPropertyItem $basketProperty */
		foreach ($this->collection as $basketProperty)
		{
			$r = $basketProperty->save();
			if (!$r->isSuccess())
				$result->addErrors($r->getErrors());

			if (isset($itemsFromDb[$basketProperty->getId()]))
				unset($itemsFromDb[$basketProperty->getId()]);
		}

		foreach ($itemsFromDb as $k => $v)
			Internals\BasketPropertyTable::delete($k);

		return $result;
	}

	/**
	 * @param array $values
	 * @return bool
	 */
	public function isPropertyAlreadyExists(array $values)
	{
		if (!($propertyValues = $this->getPropertyValues()))
		{
			return false;
		}

		$requestValues = array();
		foreach ($values as $value)
		{
			if (!($propertyValue = static::bringingPropertyValue($value)))
				continue;

			$requestValues[$propertyValue['CODE']] = $propertyValue["VALUE"];
		}

		$found = true;
		foreach($requestValues as $key => $val)
		{
			if (array_key_exists($key, $propertyValues) && $propertyValues[$key]['VALUE'] != $val)
			{
				$found = false;
				break;
			}
		}

		return $found;
	}

	/**
	 * @param array $value
	 * @return BasketPropertyItem|bool
	 */
	public function getPropertyItemByValue(array $value)
	{
		if (!($propertyValue = static::bringingPropertyValue($value)))
			return false;

		/** @var BasketPropertyItem $propertyItem */
		foreach ($this->collection as $propertyItem)
		{
			$propertyItemValues = $propertyItem->getFieldValues();

			if (!($propertyItemValue = static::bringingPropertyValue($propertyItemValues)))
				continue;


			if ($propertyItemValue['CODE'] == $propertyValue['CODE'])
				return $propertyItem;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getPropertyValues()
	{

		$result = array();
		/** @var BasketPropertyItem $property */
		foreach($this->collection as $property)
		{
			$value = $property->getFieldValues();

			if (!($propertyValue = static::bringingPropertyValue($value)))
				continue;

//			if ($propertyItem)
			$result[$propertyValue['CODE']] = $propertyValue;
		}

		return $result;
	}


	/**
	 * @param array $value
	 * @return bool|array
	 */
	private static function bringingPropertyValue(array $value)
	{
		$result = false;
		if (array_key_exists('VALUE', $value)&& strval($value["VALUE"]) != '')
		{
			$propID = '';
			if (array_key_exists('CODE', $value) && strval($value["CODE"]) != '')
			{
				$propID = $value["CODE"];
			}
			elseif (array_key_exists('NAME', $value) && strval($value["NAME"]) != '')
			{
				$propID = $value["NAME"];
			}

			if (strval($propID) != '')
			{
				$result = array(
					'CODE' => $propID,
					'VALUE' => $value["VALUE"],
					'SORT' => $value["SORT"],
					'NAME' => $value["NAME"],
				);
			}
		}

		return $result;
	}




}