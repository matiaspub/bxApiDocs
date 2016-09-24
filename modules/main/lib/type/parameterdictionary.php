<?php
namespace Bitrix\Main\Type;

use Bitrix\Main\NotSupportedException;

class ParameterDictionary
	extends Dictionary
{
	/**
	 * @var array
	 */
	protected $arRawValues = null;

	protected function setValuesNoDemand(array $values)
	{
		if ($this->arRawValues === null)
			$this->arRawValues = $this->values;
		$this->values = $values;
	}

	/**
	 * Returns original value of any variable by its name. Null if variable is not set.
	 *
	 * @param string $name
	 * @return string | null
	 */
	
	/**
	* <p>Нестатический метод возвращает оригинальное значение любой переменной по её имени. Возвращает <code>0</code>, если переменной не существует.</p>
	*
	*
	* @param string $name  
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/parameterdictionary/getraw.php
	* @author Bitrix
	*/
	public function getRaw($name)
	{
		if ($this->arRawValues === null)
		{
			if (isset($this->values[$name]) || array_key_exists($name, $this->values))
				return $this->values[$name];
		}
		else
		{
			if (isset($this->arRawValues[$name]) || array_key_exists($name, $this->arRawValues))
				return $this->arRawValues[$name];
		}

		return null;
	}

	public function toArrayRaw()
	{
		return $this->arRawValues;
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/parameterdictionary/offsetset.php
	* @author Bitrix
	*/
	static public function offsetSet($offset, $value)
	{
		throw new NotSupportedException("Can not set readonly value");
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/type/parameterdictionary/offsetunset.php
	* @author Bitrix
	*/
	static public function offsetUnset($offset)
	{
		throw new NotSupportedException("Can not unset readonly value");
	}
}