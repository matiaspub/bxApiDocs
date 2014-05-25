<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/***************************************************************************
Convertation of the standard Plimus CSV file to the
CSV file format of the Statistics module.
***************************************************************************/

/*
	Input parameters:
	INPUT_CSV_FILE - path to the source file
	OUTPUT_CSV_FILE - path to the target file
*/

$SEPARATOR = ";"; // CSV separator
$CURRENCY = "USD"; // Currency

function CleanUpCsv(&$item)
{
	$item = Trim($item, "\"");
}

function PrepareQuotes(&$item)
{
	$item = "\"".str_replace("\"","\"\"", $item)."\"";
}

$arMonth = array(
	"Jan"	=> "01",
	"Feb"	=> "02",
	"Mar"	=> "03",
	"Apr"	=> "04",
	"May"	=> "05",
	"Jun"	=> "06",
	"Jul"	=> "07",
	"Aug"	=> "08",
	"Sep"	=> "09",
	"Oct"	=> "10",
	"Nov"	=> "11",
	"Dec"	=> "12",
	);

if ($fp_in = fopen($INPUT_CSV_FILE,"rb"))
{
	$upload_dir = $_SERVER["DOCUMENT_ROOT"]."/".COption::GetOptionString("main","upload_dir","/upload/"). "/statistic";
	if (substr($OUTPUT_CSV_FILE, 0, strlen($upload_dir))==$upload_dir && $fp_out = fopen($OUTPUT_CSV_FILE,"wb"))
	{
		$i = 0; // counter of the read valuable lines
		$j = 0; // counter of the written to the resulting  file lines
		$lang_date_format = FORMAT_DATETIME; // date format for the current language
		$event1 = "plimus";
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
				if ($arrCSV[0]=="Reference No")
				{
					// get an array with the field numbers
					$arrS = array_flip($arrCSV);
				}
				elseif ($arrCSV[0]!="Reference No" && is_array($arrS) && count($arrS)>0) // else form the CSV line in module format and write it to the resulting file
				{
					$arrRes = array();

					// ID of an event type;
					$arrRes[] = $EVENT_ID;

					// event3
					$arrRes[] = $arrCSV[$arrS["Reference No"]]." / ".$arrCSV[$arrS["Product ID"]];

					// date
					$ar = explode(" ", $arrCSV[$arrS["Date"]]); // 11-Jul-2005 07:54:48
					$arDate = explode("-", $ar[0]); // 11-Jul-2005
					$arTime = explode(":", $ar[1]); // 07:54:48
					$date_time = $arDate[0].".".$arMonth[$arDate[1]].".".$arDate[2]." ".$ar[1]; // 11.07.2005 07:54:48

					// extended trim
					$date_time = preg_replace("#^[^0-9]#", "", $date_time);
					$date_time = preg_replace("#[^0-9]$#", "", $date_time);

					$date = $DB->FormatDate($date_time, "DD.MM.YYYY HH:MI:SS", $lang_date_format);
					$arrRes[] = $date;

					// additional parameter
					$ADDITIONAL_PARAMETER = $arrCSV[$arrS["Custom1"]];
					$arrRes[] = $ADDITIONAL_PARAMETER;

					// money sum
					$arrRes[] = str_replace(",", "", $arrCSV[$arrS["Total"]]);

					// currency
					//$arrRes[] = $arrCSV[$arrS["currency"]];
					$arrRes[] = $CURRENCY;

					// if short site identifier exists in Additional parameter then
					if (strpos($ADDITIONAL_PARAMETER,$SITE_ID)!==false)
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

Column headers:

Reference No;Date;Company Name;First Name;Last Name;Email;Product ID;Product Name;Contract ID;Contract Name;Quantity;Unit Price;Additional Charges;Coupon Amount;Total;License Key;Commission;Discount Rate %;Discount Transaction Fee;Payment Frequency;Address 1;Address 2;City;State;Country;Zip Code;Work Phone;Work Extension;Mobile Phone;Fax;Home Phone;Custom1;Custom2;Custom3;Custom4;Custom5;Referrer;Original Ref#;IP Address;Affiliate Commission;Armadillo Hardware Id;Account Id

*/
?>