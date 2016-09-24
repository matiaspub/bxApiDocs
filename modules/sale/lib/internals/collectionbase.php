<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

abstract class CollectionBase
	implements \ArrayAccess, \Countable, \IteratorAggregate
{
	protected $collection = array();

	public function getIterator()
	{
		return new \ArrayIterator($this->collection);
	}


	/**
	 * Whether a offset exists
	 */
	
	/**
	* <p>Метод определяет, существует или нет заданное смещение (ключ). Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/offsetexists.php
	* @author Bitrix
	*/
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]) || array_key_exists($offset, $this->collection);
	}

	/**
	 * Offset to retrieve
	 */
	
	/**
	* <p>Метод возвращает заданное смещение (ключ). Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/offsetget.php
	* @author Bitrix
	*/
	public function offsetGet($offset)
	{
		if (isset($this->collection[$offset]) || array_key_exists($offset, $this->collection))
		{
			return $this->collection[$offset];
		}

		return null;
	}

	/**
	 * Offset to set
	 */
	
	/**
	* <p>Метод устанавливает заданное смещение (ключ). Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/offsetset.php
	* @author Bitrix
	*/
	public function offsetSet($offset, $value)
	{
		if($offset === null)
		{
			$this->collection[] = $value;
		}
		else
		{
			$this->collection[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 */
	
	/**
	* <p>Метод удаляет смещение. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/offsetunset.php
	* @author Bitrix
	*/
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	
	/**
	* <p>Метод возвращает количество элементов объекта. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/count.php
	* @author Bitrix
	*/
	public function count()
	{
		return count($this->collection);
	}

	/**
	 * Return the current element
	 */
	
	/**
	* <p>Метод возвращает текущий элемент. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/current.php
	* @author Bitrix
	*/
	public function current()
	{
		return current($this->collection);
	}

	/**
	 * Move forward to next element
	 */
	
	/**
	* <p>Метод выполняет перемещение вперед к следующему элементу. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/next.php
	* @author Bitrix
	*/
	public function next()
	{
		return next($this->collection);
	}

	/**
	 * Return the key of the current element
	 */
	
	/**
	* <p>Метод возвращает ключ текущего элемента. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/key.php
	* @author Bitrix
	*/
	public function key()
	{
		return key($this->collection);
	}

	/**
	 * Checks if current position is valid
	 */
	
	/**
	* <p>Метод проверяет, корректна ли текущая позиция. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/valid.php
	* @author Bitrix
	*/
	public function valid()
	{
		$key = $this->key();
		return $key !== null;
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	
	/**
	* <p>Метод возвращает итератор на первый элемент. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/collectionbase/rewind.php
	* @author Bitrix
	*/
	public function rewind()
	{
		return reset($this->collection);
	}


}