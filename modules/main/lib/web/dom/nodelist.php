<?php
namespace Bitrix\Main\Web\DOM;

/*
class NodeList extends \Bitrix\Main\Type\Dictionary implements \Traversable
{
	public function item($index)
	{
		return $this->offsetGet($index);
	}

	public function haveItem(Node $item)
	{
		for($i = 0; $i < $this->count(); $i++)
		{
			if($item->isEqual($this->item($i)))
			{
				return true;
			}
		}

		return false;
	}

	public function removeItem(Node $item)
	{
		for($i = 0; $i < $this->count(); $i++)
		{
			if($item === $this->item($i))
			{
				$this->offsetUnset($i);
				break;
			}
		}
	}
}
*/

class NodeList implements \Iterator {
	protected $length = 0;
	protected $position = 0;
	protected $values = array();

	public function __construct(array $values = null)
	{
		if($values === null)
		{
			$this->position = 0;
			$this->set($values);
		}
	}

	public function getLength()
	{
		return $this->length;
	}

	/*
	* @return Node|null
	*/
	public function item($index)
	{
		if($this->valid($index))
		{
			return $this->values[$index];
		}

		return null;
	}

	public function set(array $values)
	{
		if($values !== null)
		{
			$this->values = array_values($values);
			$this->length = count($this->values);
		}
	}

	public function get()
	{
		return $this->values;
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function current()
	{
		return $this->values[$this->position];
	}

	public function key()
	{
		return $this->position;
	}

	public function next()
	{
		++$this->position;
	}

	public function valid($index = null)
	{
		if($index === null)
		{
			$index = $this->position;
		}

		return isset($this->values[$index]);
	}
}