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
	protected function __construct(array $fields = array())
	{
		parent::__construct($fields);
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return static::getAllFields();
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
			$fields = array_keys(Internals\BasketPropertyTable::getMap());
		return $fields;
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

		$id = $this->getId();
		$fields = $this->fields->getValues();

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();

			if (!empty($fields) && is_array($fields))
			{
				$r = Internals\BasketPropertyTable::update($id, $fields);
				if (!$r->isSuccess())
					return $r;
			}

			$result = new Entity\UpdateResult();

		}
		else
		{

			$fields['BASKET_ID'] = $this->getCollection()->getBasketId();

			$r = Internals\BasketPropertyTable::add($fields);
			if (!$r->isSuccess())
				return $r;

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);

			$result = new Entity\AddResult();

		}

		return $result;

	}
}