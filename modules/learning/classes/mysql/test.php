<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/learning/classes/general/test.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/learning/classes/ctest/index.php
 * @author Bitrix
 */
class CTest extends CAllTest
{
	public static function GetRandFunction()
	{
		return " RAND(".rand(0, 1000000).") ";
	}
}
