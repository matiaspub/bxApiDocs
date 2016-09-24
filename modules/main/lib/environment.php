<?php
namespace Bitrix\Main;

use Bitrix\Main\Type\ParameterDictionary;

class Environment
	extends ParameterDictionary
{
	/**
	 * Creates env object.
	 *
	 * @param array $arEnv
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести  при создании объекта какие-то действия.</p>
	*
	*
	* @param array $arEnv  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/environment/__construct.php
	* @author Bitrix
	*/
	static public function __construct(array $arEnv)
	{
		parent::__construct($arEnv);
	}
}