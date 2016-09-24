<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for string data type
 * @package bitrix
 * @subpackage main
 */
class StringField extends ScalarField
{
	/**
	 * Shortcut for Regexp validator
	 * @var null|string
	 */
	protected $format = null;

	/** @var int|null  */
	protected $size = null;

	public function __construct($name, $parameters = array())
	{
		parent::__construct($name, $parameters);

		if (!empty($parameters['format']))
		{
			$this->format = $parameters['format'];
		}
		if(isset($parameters['size']) && intval($parameters['size']) > 0)
		{
			$this->size = intval($parameters['size']);
		}
	}

	/**
	 * Shortcut for Regexp validator
	 * @return null|string
	 */
	
	/**
	* <p>Нестатический метод. Ссылка на валидатор <a href="https://ru.wikipedia.org/wiki/%D0%A0%D0%B5%D0%B3%D1%83%D0%BB%D1%8F%D1%80%D0%BD%D1%8B%D0%B5_%D0%B2%D1%8B%D1%80%D0%B0%D0%B6%D0%B5%D0%BD%D0%B8%D1%8F" >Regexp</a>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/stringfield/getformat.php
	* @author Bitrix
	*/
	public function getFormat()
	{
		return $this->format;
	}

	public function getValidators()
	{
		$validators = parent::getValidators();

		if ($this->format !== null)
		{
			$validators[] = new Validator\RegExp($this->format);
		}

		return $validators;
	}

	/**
	 * Returns the size of the field in a database (in characters).
	 * @return int|null
	 */
	
	/**
	* <p>Нестатический метод возвращает размер поля в БД в символах.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/stringfield/getsize.php
	* @author Bitrix
	*/
	public function getSize()
	{
		return $this->size;
	}
}