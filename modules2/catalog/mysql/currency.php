<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/catalog/general/currency.php");

class CCurrency extends CAllCurrency
{
	public static function GetList(&$by, &$order, $lang = LANG)
	{
		global $DB;

		$strSql =
			"SELECT CUR.CURRENCY, CUR.AMOUNT_CNT, CUR.AMOUNT, CUR.SORT, CUR.DATE_UPDATE, ".
			"	CURL.LID, CURL.FORMAT_STRING, CURL.FULL_NAME, CURL.DEC_POINT, ".
			"	CURL.THOUSANDS_SEP, CURL.DECIMALS ".
			"FROM b_catalog_currency CUR ".
			"	LEFT JOIN b_catalog_currency_lang CURL ON (CUR.CURRENCY = CURL.CURRENCY AND CURL.LID = '".$DB->ForSql($lang, 2)."') ";

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
		$res = $DB->Query($strSql);

		return $res;
	}
}

class CCurrencyLang extends CAllCurrencyLang
{
}

class CCurrencyRates extends CAllCurrencyRates
{
	public static function ConvertCurrency($valSum, $curFrom, $curTo, $valDate = "")
	{
		global $DB;
		if (strlen($valDate)<=0)
			$valDate = date("Y-m-d");
		list($dpYear, $dpMonth, $dpDay) = split("-", $valDate, 3);
		$dpDay += 1;
		$valDate = date("Y-m-d", mktime(0, 0, 0, $dpMonth, $dpDay, $dpYear));

		$curFromRate = 0;
		$curFromRateCnt = 0;
		$strSql = 
			"SELECT C.AMOUNT, C.AMOUNT_CNT, CR.RATE, CR.RATE_CNT ".
			"FROM b_catalog_currency C ".
			"	LEFT JOIN b_catalog_currency_rate CR ".
			"		ON (C.CURRENCY = CR.CURRENCY AND CR.DATE_RATE < '".$valDate."') ".
			"WHERE C.CURRENCY = '".$DB->ForSql($curFrom)."' ".
			"ORDER BY DATE_RATE DESC";
		$db_res = $DB->Query($strSql);
		if ($res = $db_res->Fetch())
		{
			$curFromRate = DoubleVal($res["RATE"]);
			$curFromRateCnt = IntVal($res["RATE_CNT"]);
			if ($curFromRate<=0)
			{
				$curFromRate = DoubleVal($res["AMOUNT"]);
				$curFromRateCnt = IntVal($res["AMOUNT_CNT"]);
			}
		}

		$curToRate = 0;
		$curToRateCnt = 0;
		$strSql = 
			"SELECT C.AMOUNT, C.AMOUNT_CNT, CR.RATE, CR.RATE_CNT ".
			"FROM b_catalog_currency C ".
			"	LEFT JOIN b_catalog_currency_rate CR ".
			"		ON (C.CURRENCY = CR.CURRENCY AND CR.DATE_RATE < '".$valDate."') ".
			"WHERE C.CURRENCY = '".$DB->ForSql($curTo)."' ".
			"ORDER BY DATE_RATE DESC";
		$db_res = $DB->Query($strSql);
		if ($res = $db_res->Fetch())
		{
			$curToRate = DoubleVal($res["RATE"]);
			$curToRateCnt = DoubleVal($res["RATE_CNT"]);
			if ($curToRate<=0)
			{
				$curToRate = DoubleVal($res["AMOUNT"]);
				$curToRateCnt = IntVal($res["AMOUNT_CNT"]);
			}
		}

		return DoubleVal(DoubleVal($valSum)*$curFromRate*$curToRateCnt/$curToRate/$curFromRateCnt);
	}
}
?>