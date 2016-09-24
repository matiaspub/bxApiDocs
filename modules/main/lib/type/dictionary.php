<?php
namespace Bitrix\Main\Type;

class Dictionary
	implements \ArrayAccess, \Iterator, \Countable
{
	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * Creates object.
	 *
	 * @param array $values
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия.</p>
	*
	*
	* @param array $values = null 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/__construct.php
	* @author Bitrix
	*/
	public function __construct(array $values = null)
	{
		if($values !== null)
		{
			$this->values = $values;
		}
	}

	/**
	 * Returns any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает любую переменную по её имени. Возвращает <code>0</code>, если переменной не существует.</p>
	*
	*
	* @param string $name  
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/get.php
	* @author Bitrix
	*/
	public function get($name)
	{
		// this condition a bit faster
		// it is possible to omit array_key_exists here, but for uniformity...
		if (isset($this->values[$name]) || array_key_exists($name, $this->values))
		{
			return $this->values[$name];
		}

		return null;
	}

	public function set(array $values)
	{
		$this->values = $values;
	}

	public function clear()
	{
		$this->values = array();
	}

	/**
	 * Return the current element
	 */
	
	/**
	* <p>Нестатический метод возвращает текущий элемент.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/current.php
	* @author Bitrix
	*/
	public function current()
	{
		return current($this->values);
	}

	/**
	 * Move forward to next element
	 */
	
	/**
	* <p>Нестатический метод. Переход вперёд к следующему элементу.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/next.php
	* @author Bitrix
	*/
	public function next()
	{
		return next($this->values);
	}

	/**
	 * Return the key of the current element
	 */
	
	/**
	* <p>Нестатический метод возвращает ключ текущего элемента.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/key.php
	* @author Bitrix
	*/
	public function key()
	{
		return key($this->values);
	}

	/**
	 * Checks if current position is valid
	 */
	
	/**
	* <p>Нестатический метод проверяет валидность текущей позиции.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/valid.php
	* @author Bitrix
	*/
	public function valid()
	{
		return ($this->key() !== null);
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	
	/**
	* <p>Нестатический метод возвращает итератор к первому элементу.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/rewind.php
	* @author Bitrix
	*/
	public function rewind()
	{
		return reset($this->values);
	}

	/**
	 * Whether a offset exists
	 */
	
	/**
	* <p>Нестатический метод. Существует ли смещение.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/offsetexists.php
	* @author Bitrix
	*/
	public function offsetExists($offset)
	{
		return isset($this->values[$offset]) || array_key_exists($offset, $this->values);
	}

	/**
	 * Offset to retrieve
	 */
	
	/**
	* <p>Нестатический метод. Установка по получению.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/offsetget.php
	* @author Bitrix
	*/
	public function offsetGet($offset)
	{
		if (isset($this->values[$offset]) || array_key_exists($offset, $this->values))
		{
			return $this->values[$offset];
		}

		return null;
	}

	/**
	 * Offset to set
	 */
	
	/**
	* <p>Нестатический метод. Установка по смещению.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/offsetset.php
	* @author Bitrix
	*/
	public function offsetSet($offset, $value)
	{
		if($offset === null)
		{
			$this->values[] = $value;
		}
		else
		{
			$this->values[$offset] = $value;
		}
	}

	/**
	 * Offset to unset
	 */
	
	/**
	* <p>Нестатический метод. Очистка по смещению.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/offsetunset.php
	* @author Bitrix
	*/
	public function offsetUnset($offset)
	{
		unset($this->values[$offset]);
	}

	/**
	 * Count elements of an object
	 */
	
	/**
	* <p>Нестатический метод подсчитывает число элементов объекта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/count.php
	* @author Bitrix
	*/
	public function count()
	{
		return count($this->values);
	}

	/**
	 * Returns the values as an array.
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает значения как массив.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/toarray.php
	* @author Bitrix
	*/
	public function toArray()
	{
		return $this->values;
	}

	/**
	 * Returns true if the dictionary is empty.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод возвращает <i>true</i> если словарь пустой</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/dictionary/isempty.php
	* @author Bitrix
	*/
	public function isEmpty()
	{
		return empty($this->values);
	}
}
