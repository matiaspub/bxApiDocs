<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main\Entity;
use Bitrix\Sale\Internals;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BasketPropertyItem
	extends Internals\CollectableEntity
{
	protected static $mapFields = array();

	protected function __construct(array $fields = array())
	{
		parent::__construct($fields);
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array(
			'NAME',
			'VALUE',
			'CODE',
			'SORT',
		);
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
		if (empty(static::$mapFields))
		{
			static::$mapFields = parent::getAllFieldsByMap(Internals\BasketPropertyTable::getMap());
		}
		return static::$mapFields;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->getField('ID');
	}

	/**
	 * @param BasketPropertiesCollection $basketPropertiesCollection
	 * @return static
	 */
	public static function create(BasketPropertiesCollection $basketPropertiesCollection)
	{
		$basketPropertyItem = new static();
		$basketPropertyItem->setCollection($basketPropertiesCollection);

		return $basketPropertyItem;
	}

	/**
	 * @return Entity\AddResult|Entity\UpdateResult|Result
	 */
	public function save()
	{
		$result = new Result();
		static $map = array();

		$id = $this->getId();

		if (empty($map))
		{
			$map = Internals\BasketPropertyTable::getMap();
		}

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();
		}
		else
		{
			$fields = $this->fields->getValues();
		}

		if (!empty($fields) && is_array($fields))
		{
			foreach ($map as $key => $value)
			{
				if ($value instanceof Entity\StringField)
				{
					$fieldName = $value->getName();
					if (array_key_exists($fieldName, $fields))
					{
						if (!empty($fields[$fieldName]) && strlen($fields[$fieldName]) > $value->getSize())
						{
							$fields[$fieldName] = substr($fields[$fieldName], 0, $value->getSize());
						}
					}
				}
			}
		}

		if ($id > 0)
		{
			if (!empty($fields) && is_array($fields))
			{
				$r = Internals\BasketPropertyTable::update($id, $fields);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}

				if ($resultData = $r->getData())
					$result->setData($resultData);
			}

		}
		else
		{

			$fields['BASKET_ID'] = $this->getCollection()->getBasketId();
			$this->setFieldNoDemand('BASKET_ID', $fields['BASKET_ID']);

			$r = Internals\BasketPropertyTable::add($fields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

			if ($resultData = $r->getData())
				$result->setData($resultData);

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);

		}

		if ($id > 0)
		{
			$result->setId($id);
		}

		return $result;

	}


	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return Basket
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$basketPropertyItemClone = clone $this;
		$basketPropertyItemClone->isClone = true;

		/** @var Internals\Fields $fields */
		if ($fields = $this->fields)
		{
			$basketPropertyItemClone->fields = $fields->createClone($cloneEntity);
		}

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $basketPropertyItemClone;
		}

		if ($collection = $this->getCollection())
		{
			if (!$cloneEntity->contains($collection))
			{
				$cloneEntity[$collection] = $collection->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($collection))
			{
				$basketPropertyItemClone->collection = $cloneEntity[$collection];
			}
		}

		return $basketPropertyItemClone;
	}


	/**
	 * @return Result
	 */
	public function verify()
	{

		$result = new Result();

		static $map = array();

		if (empty($map))
		{
			$map = Internals\BasketPropertyTable::getMap();
		}

		$fieldValues = $fields = $this->fields->getValues();

		$propertyName = (!empty($fieldValues['NAME'])) ? $fieldValues['NAME'] : "";
		if ($this->getId() > 0)
		{
			$fields = $this->fields->getChangedValues();
		}

		foreach ($map as $key => $value)
		{
			if ($value instanceof Entity\StringField)
			{
				$fieldName = $value->getName();
				if (array_key_exists($fieldName, $fields))
				{
					if (array_key_exists($fieldName, $fields))
					{
						if (!empty($fields[$fieldName]) && strlen($fields[$fieldName]) > $value->getSize())
						{
							if ($fieldName === 'NAME')
							{
								$propertyName = substr($propertyName, 0, 50)."...";
							}

							$result->addError(new ResultWarning(Loc::getMessage("SALE_BASKET_ITEM_PROPERTY_MAX_LENGTH_ERROR", array("#PROPERTY_NAME#" => $propertyName, "#FIELD_TITLE#" => $fieldName, "#MAX_LENGTH#" => $value->getSize()))));
						}
					}


				}
			}
		}

		return $result;
	}

}