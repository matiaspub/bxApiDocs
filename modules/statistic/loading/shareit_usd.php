<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/***************************************************************************
Convertation of the standard ShareIt CSV file to the
CSV file format of the Statistics module.

Currency: USD
***************************************************************************/

/*
	Input parameters:
	INPUT_CSV_FILE - path to the source file
	OUTPUT_CSV_FILE - path to the resulting file
*/

$SEPARATOR = ","; // CSV separator
$CURRENCY = "USD"; // Currency

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
	$upload_dir = rtrim($_SERVER["DOCUMENT_ROOT"], "/")."/".COption::GetOptionString("main","upload_dir","/upload/"). "/statistic";
	if (substr($OUTPUT_CSV_FILE, 0, strlen($upload_dir))==$upload_dir && $fp_out = fopen($OUTPUT_CSV_FILE,"wb"))
	{
		$i = 0; // counter of the read valuable lines
		$j = 0; // counter of the written to the resulting  file lines
		$lang_date_format = FORMAT_DATETIME; // date format for the current language
		$event1 = "shareit";
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
				if ($arrCSV[0]=="PADATE")
				{
					// get an array with the field numbers
					$arrS = array_flip($arrCSV);
				}
				elseif ($arrCSV[0]!="PADATE" && is_array($arrS) && count($arrS)>0) // else form the CSV line in module format and write it to the resulting file
				{
					$arrRes = array();

					// ID of an event type;
					$arrRes[] = $EVENT_ID;

					// event3
					$arrRes[] = $arrCSV[$arrS["PURCHASEID"]]." / ".$arrCSV[$arrS["PRODUCTID"]];

					// date
					$arrRes[] = $DB->FormatDate(trim($arrCSV[$arrS["TDATE"]]), "MM/DD/YYYY HH:MI:SS", $lang_date_format);

					// additional parameter
					$ADDITIONAL_PARAMETER = $arrCSV[$arrS["ADDITIONAL1"]];
					$arrRes[] = $ADDITIONAL_PARAMETER;

					// money sum
					$arrRes[] = $arrCSV[$arrS["TOTAL"]];

					// currency
					//$arrRes[] = $arrCSV[$arrS["CURRENCY"]];
					$arrRes[] = $CURRENCY;

					// if short site identifier exists in Additional parameter then
					if (strpos($ADDITIONAL_PARAMETER,$SITE_ID)!==false)
					{
						// write the line to the resulting CSV file
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

Description of column headers:

PADATE    Date on which the order was paid
TDATE Date on which the order was paid (including time)
DESCRIPTION Description of the order, for example 'Order', 'Refund', etc.
SETTLED Dummy field (for compatibility reasons)
PAYMENTTYPE Type of payment
  Payment methods available:
  CAS Cash
  CHK Check
  WTR Bank transfer
  CCA Credit card
  DBC Debit Card
  INV Purchase order
  INH Purchase order requiring approval
  SBX Online wire transfer (in Germany only)
  NPN No payment required
REFNUM Your reference number
LASTNAME Customer's last name
FIRSTNAME Customer's first name
COMPANY Company name
STREET Customer's address: Street
CITY Customer's address: City
ZIP Customer's address: Zip / Postal Code
STATE Customer's address: State/Province (for USA & Canada)
COUNTRY Customer's address: Country
EMAIL Customer's e-mail address
PHONE Customer's phone number
FAX Customer's fax number
PRODUCTID ID of the product sold
PRODUCTNAME Name of the product sold
NUMLICENSE Number of licenses sold
SINGLEPRICE Unit price
VAT The we collected for you
SHIPPING Shipping fees we collected for you
CURRENCY Currency of this transaction
TOTAL The total price for this order
PURCHASEID Purchase ID of this order
VATID Customer's ID
KEY License key (optional)
RESELLER ID of the reseller/key account
REG_NAME The product is licensed to
SALUTATION Salutation
TITLE Title
ADDITIONAL1 Additional field 1
ADDITIONAL2 Additional field 2
DISCOUNT Customer's discount
COUPONCODE Coupon code
PROMOTIONID Promotion ID
PUBLISHERS_PRODUCT_ID Product ID (in your inventory management system)
EAN_CODE EAN
RUNNING_NO Position of this item in the shopping cart.
LANGUAGE Language of the customer

*/
?>