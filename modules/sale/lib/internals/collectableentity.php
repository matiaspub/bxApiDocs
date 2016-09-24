<?php
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Sale\Internals;
use Bitrix\Sale\Result;

abstract class CollectableEntity
	extends Internals\Entity
{
	/** @var EntityCollection */
	protected $collection;

	protected $internalIndex = null;

	protected $isClone = false;

	protected function onFieldModify($name, $oldValue, $value)
	{
		$collection = $this->getCollection();
		return $collection->onItemModify($this, $name, $oldValue, $value);
	}

	public function setCollection(EntityCollection $collection)
	{
		$this->collection = $collection;
	}

	/**
	 * @return EntityCollection
	 */
	public function getCollection()
	{
		return $this->collection;
	}

	/**
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 */
	public function delete()
	{
		$collection = $this->getCollection();
		if (!$collection)
		{
			throw new Main\ObjectNotFoundException('Entity "CollectableEntity" not found');
		}

		/** @var Result $r */
		$collection->deleteItem($this->getInternalIndex());

		return new Result();
	}

	/**
	 * @internal
	 *
	 * @param $index
	 * @throws Main\ArgumentTypeException
	 */
	public function setInternalIndex($index)
	{
		if (!is_numeric($index))
			throw new Main\ArgumentTypeException("index");

		$this->internalIndex = $index;
	}

	/**
	 * @return null|int
	 */
	public function getInternalIndex()
	{
		return $this->internalIndex;
	}

	/**
	 * @param bool $isMeaningfulField
	 * @return bool
	 */
	public function isStartField($isMeaningfulField = false)
	{
		$parent = $this->getEntityParent();
		if ($parent == null)
			return false;

		return $parent->isStartField($isMeaningfulField);
	}


	/**
	 * @return bool
	 */
	public function clearStartField()
	{
		$parent = $this->getEntityParent();
		if ($parent == null)
			return false;

		return $parent->clearStartField();
	}

	/**
	 * @return bool
	 */
	public function hasMeaningfulField()
	{
		$parent = $this->getEntityParent();
		if ($parent == null)
			return false;

		return $parent->hasMeaningfulField();
	}

	public function doFinalAction($hasMeaningfulField = false)
	{
		$parent = $this->getEntityParent();
		if ($parent == null)
			return false;

		return $parent->doFinalAction($hasMeaningfulField);
	}

	/**
	 * @param bool|false $value
	 * @return bool
	 */
	public function setMathActionOnly($value = false)
	{
		$parent = $this->getEntityParent();
		if ($parent == null)
			return false;

		return $parent->setMathActionOnly($value);
	}

	/**
	 * @return bool
	 */
	public function isMathActionOnly()
	{
		$parent = $this->getEntityParent();
		if ($parent == null)
			return false;

		return $parent->isMathActionOnly();
	}

	/**
	 * @internal
	 * @param array $map
	 *
	 * @return array
	 */
	public static function getAllFieldsByMap(array $map)
	{
		$fields = array();
		foreach ($map as $key => $value)
		{
			if (is_array($value) && !array_key_exists('expression', $value))
			{
				$fields[] = $key;
			}
			elseif ($value instanceof Main\Entity\ScalarField)
			{
				$fields[] = $value->getName();
			}
		}
		return $fields;
	}

	/**
	 * @return bool
	 */
	public function isClone()
	{
		return $this->isClone;
	}

}