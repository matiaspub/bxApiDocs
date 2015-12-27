<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/tax.php");


/**
 * 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletax/index.php
 * @author Bitrix
 */
class CSaleTax extends CAllSaleTax
{
	
	/**
	* <p>Метод добавляет новый налог с параметрами из массива arFields. Метод динамичный.</p>
	*
	*
	* @param array $arFields  Ассоциативный массив параметров нового налога, ключами в котором
	* являются названия параметров, а значениями - соответствующие
	* значения.<br><br> Допустимые параметры:<ul> <li> <b>LID</b> - сайт;</li> <li> <b>NAME</b>
	* - название налога;</li> <li> <b>DESCRIPTION</b> - описание;</li> <li> <b>CODE</b> -
	* символьный код.</li> </ul>
	*
	* @return int <p>Возвращается код добавленного налога или <i>false</i> в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/sale/classes/csaletax/csaletax__add.b22a6ac6.php
	* @author Bitrix
	*/
	public static function Add($arFields)
	{
		global $DB;

		if (!CSaleTax::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_sale_tax", $arFields);
		$strSql =
			"INSERT INTO b_sale_tax(".$arInsert[0].", TIMESTAMP_X) ".
			"VALUES(".$arInsert[1].", ".$DB->GetNowFunction().")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = IntVal($DB->LastID());

		return $ID;
	}
}
?>