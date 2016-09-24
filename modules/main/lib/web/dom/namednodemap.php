<?php
namespace Bitrix\Main\Web\DOM;

/*
class NamedNodeMap extends NodeList
{
	public function getNamedItem($name)
	{
		for($index = 0; $index < $this->count(); $index++)
		{
			if($this->item($index)->getName() === $name)
			{
				return $this->item($index);
			}
		}

		return null;
	}

	public function setNamedItem(Node $node)
	{
		$this->offsetSet(null, $node);
	}

	public function removeNamedItem($name)
	{
		for($index = 0; $index < $this->count(); $index++)
		{
			if($this->item($index)->getName() === $name)
			{
				$this->offsetUnset($index);
			}
		}

		$this->rewind();
	}
}
*/

class NamedNodeMap extends NodeList
{
	protected $names = array();
	protected $nameByPosition = array();
	protected $positionByName = array();

	public function set(array $values)
	{
		if($values !== null)
		{
			$this->nameByPosition = array();
			$this->positionByName = array();

			parent::set($values);
		}
	}

	public function get()
	{
		return $this->values;
	}

	public function key()
	{
		return $this->nameByPosition[$this->position];
	}

	public function getNamedItem($name)
	{
		if(!isset($this->positionByName[$name]))
		{
			return null;
		}

		return $this->item($this->positionByName[$name]);
	}

	/*
	 * Adds a node using its nodeName attribute.
	 * If a node with that name is already present in this map, it is replaced by the new one.
	 * Replacing a node by itself has no effect.
	 * As the nodeName attribute is used to derive the name which the node must be stored under,
	 * multiple nodes of certain types (those that have a "special" string value) cannot be stored as the names would clash.
	 * This is seen as preferable to allowing nodes to be aliased.
	 */
	public function setNamedItem(Attr $node)
	{
		$result = null;
		$name = $node->getName();
		if(isset($this->positionByName[$name]))
		{
			$position = $this->positionByName[$name];
			$result = $this->item($position);
		}
		else
		{
			$position = $this->getLength();
		}

		$this->values[$position] = $node;
		$this->nameByPosition[$position] = $name;
		$this->positionByName[$name] = $position;

		$this->length = count($this->values);

		return $result;
	}

	/*
	 * Removes a node specified by name.
	 * When this map contains the attributes attached to an element,
	 * if the removed attribute is known to have a default value,
	 * an attribute immediately appears containing the default value as well
	 * as the corresponding namespace URI, local name, and prefix when applicable.
	 */
	public function removeNamedItem($name)
	{
		$item = $this->getNamedItem($name);
		if($item)
		{
			$position = $this->positionByName[$name];
			unset($this->values[$position]);
			unset($this->nameByPosition[$position]);
			unset($this->positionByName[$name]);
			$this->length = count($this->values);

			return $item;
		}
		else
		{
			throw new \Bitrix\Main\Web\DOM\DomException('', \Bitrix\Main\Web\DOM\DomException::NOT_FOUND_ERR);
		}
	}
}