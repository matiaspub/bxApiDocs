<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
if($STAT_RIGHT=="D") $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

/***************************************************************************
Convertation of the standard RegNow CSV file to the
CSV file format of the Statistics module.
***************************************************************************/

/*
	Input parameters:
	INPUT_CSV_FILE - path to the source file
	OUTPUT_CSV_FILE - path to the target file
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
		$event1 = "regnow";
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
				if ($i==1)
				{
					// get an array with the field numbers
					$arrS = array_flip($arrCSV);
				}
				elseif (is_array($arrS) && count($arrS)>0) // else form the CSV line in module format and write it to the resulting file
				{
					$arrRes = array();

					// ID of an event type;
					$arrRes[] = $EVENT_ID;

					// event3
					$arrRes[] = $arrCSV[$arrS["orderid"]]." / ".$arrCSV[$arrS["item"]];

					// date
					$arrRes[] = $DB->FormatDate(trim($arrCSV[$arrS["date"]]), "MM/DD/YYYY", $lang_date_format);

					// additional parameter
					$ADDITIONAL_PARAMETER = $arrCSV[$arrS["custom_linkid"]];
					$arrRes[] = $ADDITIONAL_PARAMETER;

					// money sum
					$arrRes[] = $arrCSV[$arrS["total"]];

					// currency
					$arrRes[] = $arrCSV[$arrS["currency"]];

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

"orderid","date","status","referrer","custom_linkid","ip","currency","received_via","payment_method","payment_delivery_method","asp","product_choice","hear","cdrom","heardabout","ordertype","item","total","item_status","quantity","orderitem_profit","regname","regcode","reginfo","delivered","affiliate_fees","offer_id","affiliateid","user","accept_offers","languageid","fname","lname","company","add1","add2","city","state","zip","country","phone","email","ship_fname","ship_lname","ship_company","ship_add1","ship_add2","ship_city","ship_state","ship_zip","ship_country","ship_phone"

*/
?>