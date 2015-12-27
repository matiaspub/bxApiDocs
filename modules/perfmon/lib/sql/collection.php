<?php
namespace Bitrix\Perfmon\Sql;

/**
 * Class Collection
 * This class represents collection of database objects such as table columns or indexes, schema procedures or sequences.
 * @package Bitrix\Perfmon\Sql
 */
class Collection
{
	/** @var array[BaseObject]  */
	private $list = array();

	/**
	 * Add object into the tail of the collection.
	 *
	 * @param BaseObject $object Object to add.
	 *
	 * @return void
	 */
	public function add(BaseObject $object)
	{
		$this->list[] = $object;
	}

	/**
	 * Searches collection for an object by it's name.
	 *
	 * @param string $name Object name to look up.
	 *
	 * @return BaseObject|null
	 */
	public function search($name)
	{
		/** @var BaseObject $object */
		foreach ($this->list as $object)
		{
			if ($object->compareName($name) == 0)
				return $object;
		}
		return null;
	}

	/**
	 * Returns all collection objects.
	 *
	 * @return array[BaseObject]
	 */
	public function getList()
	{
		return $this->list;
	}

	/**
	 * Compares two collections of objects and returns array of pairs.
	 * <p>
	 * Pair is the two element array:
	 * - First element with index "0" is the object from the source collection.
	 * - Second element with index "1" is the object from $targetList.
	 * - if pair element is null when no such element found (by name) in the collection.
	 *
	 * @param Collection $targetList Collection to compare.
	 * @param bool $compareBody Whenever to compare objects bodies or not.
	 *
	 * @return array
	 */
	public function compare(Collection $targetList, $compareBody = true)
	{
		$difference = array();
		/** @var BaseObject $source */
		foreach ($this->list as $source)
		{
			if (!$targetList->search($source->name))
			{
				$difference[] = array(
					$source,
					null,
				);
			}
		}
		/** @var BaseObject $target */
		foreach ($targetList->list as $target)
		{
			$source = $this->search($target->name);
			if (!$source)
			{
				$difference[] = array(
					null,
					$target,
				);
			}
			elseif (
				!$compareBody
				|| $source->body !== $target->body
			)
			{
				$difference[] = array(
					$source,
					$target,
				);
			}
		}
		return $difference;
	}
}
