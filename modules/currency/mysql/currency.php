<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/currency/general/currency.php");


/**
 * <b>CCurrency</b> - класс для управления валютами: добавление, удаление, перечисление.</body> </html>
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/currency/developer/ccurrency/index.php
 * @author Bitrix
 */
class CCurrency extends CAllCurrency
{
	static public function __GetList(&$by, &$order, $lang = LANGUAGE_ID)
	{
		global $DB;

		$strSql =
			"SELECT CUR.CURRENCY, CUR.AMOUNT_CNT, CUR.AMOUNT, CUR.SORT, CUR.DATE_UPDATE, CUR.DATE_CREATE, CUR.BASE, CUR.NUMCODE, CUR.CREATED_BY, CUR.MODIFIED_BY, ".
			$DB->DateToCharFunction('CUR.DATE_UPDATE', 'FULL').' as DATE_UPDATE_FORMAT, '.
			$DB->DateToCharFunction('CUR.DATE_CREATE', 'FULL').' as DATE_CREATE_FORMAT, '.
			"	CURL.LID, CURL.FORMAT_STRING, CURL.FULL_NAME, CURL.DEC_POINT, CURL.THOUSANDS_SEP, CURL.DECIMALS, CURL.HIDE_ZERO ".
			"FROM b_catalog_currency CUR LEFT JOIN b_catalog_currency_lang CURL ON (CUR.CURRENCY = CURL.CURRENCY AND CURL.LID = '".$DB->ForSql($lang, 2)."')";

		if (strtolower($by) == "currency") $strSqlOrder = " ORDER BY CUR.CURRENCY ";
		elseif (strtolower($by) == "name") $strSqlOrder = " ORDER BY CURL.FULL_NAME ";
		else
		{
			$strSqlOrder = " ORDER BY CUR.SORT ";
			$by = "sort";
		}

		if ($order=="desc")
			$strSqlOrder .= " desc ";
		else
			$order = "asc";

		$strSql .= $strSqlOrder;
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}
}
?>