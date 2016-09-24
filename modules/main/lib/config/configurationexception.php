<?php
namespace Bitrix\Main\Config;

/**
 * Exception is thrown when a configuration error has occurred (i.e. system is frustrated).
 */
class ConfigurationException
	extends \Bitrix\Main\SystemException
{
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта. Исключение выводится если произошла ошибка конфигурации (т.е. система расстроена). Код ошибки выставляется 180. Расширение класса <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/systemexception/index.php">Bitrix\Main\SystemException</a>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @param mixed $message = "" 
	*
	* @param Exception $previous = null 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/config/configurationexception/__construct.php
	* @author Bitrix
	*/
	static public function __construct($message = "", \Exception $previous = null)
	{
		parent::__construct($message, 180, '', '', $previous);
	}
}
