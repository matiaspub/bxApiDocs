<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/***************************************************************************
Convertation of the standard Softkey CSV file to the
CSV file format of the Statistics module.
***************************************************************************/

/*
	Input parameters:
	INPUT_CSV_FILE - path to the source file
	OUTPUT_CSV_FILE - path to the resulting file
*/

$SEPARATOR = ","; // CSV separator

function CleanUpCsv(&$item)
{
	$item = Trim($item, "\"");
}

function PrepareQuotes(&$item)
{
	$item = "\"".str_replace("\"","\"\"", $item)."\"";
}

if ($fp_in = fopen($INPUT_CSV_FILE,"rb"))
{
	$upload_dir = $_SERVER["DOCUMENT_ROOT"]."/".COption::GetOptionString("main","upload_dir","/upload/"). "/statistic";
	if (substr($OUTPUT_CSV_FILE, 0, strlen($upload_dir))==$upload_dir && $fp_out = fopen($OUTPUT_CSV_FILE,"wb"))
	{
		$i = 0; // counter of the read valuable lines
		$j = 0; // counter of the written to the resulting  file lines
		$lang_date_format = FORMAT_DATE; // date format for the current language
		$event1 = "softkey";
		$event2 = "buy";
		$EVENT_ID = CStatEventType::ConditionSet($event1, $event2, $arEventType)." (".$event1." / ".$event2.")";
		$SITE_ID = GetEventSiteID(); // short site identifier (ID)
		while (!feof($fp_in))
		{
			$arrCSV = fgetcsv($fp_in, 4096, $SEPARATOR);
			if (is_array($arrCSV) && count($arrCSV)>1)
			{
				array_walk($arrCSV, "CleanUpCsv");
				reset($arrCSV);
				$i++;
				// if it is the first line then
				if ($arrCSV[0]=="AUTHOR_ID")
				{
					// get an array with the field numbers
					$arrS = array_flip($arrCSV);
				}
				elseif ($arrCSV[0]!="AUTHOR_ID" && is_array($arrS) && count($arrS)>0) // else form the CSV line in module format and write it to the resulting file
				{
					$arrRes = array();

					// ID of an event type;
					$arrRes[] = $EVENT_ID;

					// event3
					$arrRes[] = $arrCSV[$arrS["ORDER_ID"]]." / ".$arrCSV[$arrS["PROGRAM_ID"]]." / ".$arrCSV[$arrS["OPTION_ID"]];

					// date
					$arrRes[] = $DB->FormatDate(trim($arrCSV[$arrS["PAID_DATE"]]), "DD.MM.YYYY", $lang_date_format);

					// additional parameter
					$ADDITIONAL_PARAMETER = $arrCSV[$arrS["REFERER1"]];
					if (strpos($ADDITIONAL_PARAMETER,$SITE_ID)===false)
					{
						$ADDITIONAL_PARAMETER = $arrCSV[$arrS["REFERER2"]];
					}
					$arrRes[] = $ADDITIONAL_PARAMETER;

					// money sum
					$arrRes[] = $arrCSV[$arrS["AMOUNT"]];

					// currency
					$arrRes[] = $arrCSV[$arrS["CURRENCY"]];

					$PAID_UP = $arrCSV[$arrS["PAID_UP"]];

					// if short site identifier exists in Additional parameter then
					if (strpos($ADDITIONAL_PARAMETER,$SITE_ID)!==false && $PAID_UP=="Y")
					{
						// write the line to the resulting file
						$j++;
						array_walk($arrRes, "PrepareQuotes");
						$str = implode(",",$arrRes);
						if ($j>1) $str = "\n".$str;
						fputs($fp_out, $str);
					}
				}
			}
		}
		@fclose($fp_out);
	}
	@fclose($fp_in);
}

/*

Описание CSV полей:

AUTHOR_ID - код компании автора;
AUTHOR_NAME - название компании автора;
ORDER_ID - код заказа;
BASKET_ID - код позиции заказа;
DATE_INSERT - дата создания заказа;
PROGRAM_ID - код программы;
OPTION_ID - код опции программы;
PROGRAM_NAME - название программы;
LID - регион продаж;
BUYER_NAME - полное имя покупателя;
BUYER_COMPANY_ID - код компании покупателя;
BUYER_COMPANY - название компании покупателя;
QUANTITY - количество копий;
AMOUNT - стоимость заказа;
CURRENCY - валюта заказа;
CURRENCY_RATE_USD - курс валюты заказа по отношению к USD;
AMOUNT_USER_CURRENCY - стоимость в дополнительной валюте;
CURRENCY_RATE_USER_CURRENCY - курс дополнительной валюты к USD;
STATUS - текущий статус заказа;
DATE_STATUS - дата последнего изменения статуса;
DATE_DISPATCH - дата установления статуса "отгружен";
CANCEL_REASON - причина отмены заказа;
CANCEL_REASON_ANOTHER - произвольная причина отмены;
PAID_UP - оплачен ли заказ (Y/N)
PAID_DATE - дата оплаты заказа;
DEALER_AGREEMENT_ID - код дилерского договора;
DEALER_DISCOUNT - дилерская скидка;
BUYER_AGREEMENT_ID - код договора покупательской скидки;
BUYER_DISCOUNT - покупательская скидка;
COUPON - значение купона для скидки;
AUTHOR_AMOUNT - авторское вознаграждение;
AUTHOR_AMOUNT_USER_CURRENCY - авторское вознаграждение в дополнительной валюте;
COMMISSION_AGREEMENT_ID - код авторского договора;
COMMISSION_AMOUNT - величина комиссии;
COMMISSION_CURRENCY - валюта выплаты авторского вознаграждения;
DELIVERY_LID - представительство, ответственное за отгрузку заказа;
TRANSFER - переведены ли средства на внутренний счет автора (Y/N);
AFFILIATE_ID - код компании аффилиата;
AFFILIATE_NAME - название компании аффилиата;
AFFILIATE_URL_FROM - с какой страницы аффилиата перешел покупатель;
AFFILIATE_URL_TO - на какую страницу перешел покупатель от аффилиата;
AFFILIATE_DATE - дата перехода от аффилиата;
AFFILIATE_AGREEMENT_ID - код аффилиатского договора;
AFFILIATE_AMOUNT - комиссия аффилиата;
IP_ADDRESS - IP адрес покупателя;
HTTP_HOST - хост покупателя;
HTTP_REFERER - с какой страницы покупатель оформил заказ;
HTTP_ACCEPT_LANGUAGE - языки браузера покупателя;
HTTP_USER_AGENT - название браузера покупателя;
REG_NAME - имя для регистрации;
REG_COMPANY - название компании для регистрации;
REG_EMAIL - email для регистрации;
REG_ZIPCODE - почтовый индекс для регистрации;
REG_LOCATION - местоположение для регистрации;
REG_CITY - город для регистрации;
REG_ADDRESS - адрес для регистрации;
REG_PHONE - телефон для регистрации;
REG_PARAM1 - дополнительное поле 1;
REG_PARAM2 - дополнительное поле 2;
REG_PARAM3 - дополнительное поле 3;
REFERER1 - параметр referer1 из ссылки на заказ (идентификатор рекламной кампании);
REFERER2 - параметр referer2 из ссылки на заказ;
REF_URL_FROM - откуда перешел покупатель по рекламной кампании;
REF_URL_TO - куда перешел покупатель по рекламной кампании;
REF_DATE - дата перехода по рекламной камапнии;
SESSION_REFERER - откуда покупатель пришел на сервер;
ORD_EMAIL - контактный email в заказе;
ORD_CONTACT_PERSON - контактное лицо в заказе;
ORD_COMPANY_NAME - название компании в заказе;
ORD_INN - ИНН в заказе;
ORD_LOCATION - местоположение в заказе;
ORD_COUNTRY - страна в заказе;
ORD_ZIP_CODE - почтовый индекс в заказе;
ORD_STATE - штат в заказе;
ORD_CITY - город в заказе;
ORD_ADDRESS - юридический адрес в заказе;
ORD_ADDRESS_FACT - фактический адрес в заказе;
ORD_PHONE - телефон в заказе;
ORD_FAX - факс в заказе;
ORD_OKONH - ОКОНХ в заказе;
ORD_OKPO - ОКПО в заказе;
ORG_TYPE_NAME - организационная форма плательщика;
PAYMENT_SYS_NAME - название метода оплаты.
*/
?>