<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/stoplist.php");
class CStoplist extends CAllStopList
{
	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$arSqlSearch = Array();
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if(is_array($val))
				{
					if(count($val) <= 0)
						continue;
				}
				else
				{
					if( (strlen($val) <= 0) || ($val === "NOT_REF") )
						continue;
				}
				$match_value_set = array_key_exists($key."_EXACT_MATCH", $arFilter);
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.ID",$val,$match);
						break;
					case "DATE_START_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_START >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_START_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_START < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "DATE_END_1":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_END >= ".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_END_2":
						if (CheckDateTime($val))
							$arSqlSearch[] = "S.DATE_END < ".CStatistics::DBDateAdd($DB->CharToDateFunction($val, "SHORT"), 1);
						break;
					case "ACTIVE":
					case "SAVE_STATISTIC":
						$arSqlSearch[] = ($val=="Y") ? "S.".$key."='Y'" : "S.".$key."='N'";
						break;
					case "IP_1":
					case "IP_2":
					case "IP_3":
					case "IP_4":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.".$key,$val,$match);
						break;
					case "URL_FROM":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.URL_FROM",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "USER_AGENT":
					case "MESSAGE":
					case "COMMENTS":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.".$key, $val, $match);
						break;
					case "URL_TO":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.URL_TO",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "URL_REDIRECT":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("S.URL_REDIRECT",$val,$match,array("/","\\",".","?","#",":"));
						break;
					case "SITE_ID":
						if (is_array($val)) $val = implode(" | ", $val);
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("S.SITE_ID", $val, $match);
						break;
				}
			}
		}

		if ($order!="asc")
			$order = "desc";

		if ($by == "s_id")
			$strSqlOrder = "ORDER BY S.ID $order";
		elseif ($by == "s_date_start")
			$strSqlOrder = "ORDER BY S.DATE_START $order";
		elseif ($by == "s_site_id")
			$strSqlOrder = "ORDER BY S.SITE_ID $order";
		elseif ($by == "s_date_end")
			$strSqlOrder = "ORDER BY S.DATE_END $order";
		elseif ($by == "s_active")
			$strSqlOrder = "ORDER BY S.ACTIVE $order";
		elseif ($by == "s_save_statistic")
			$strSqlOrder = "ORDER BY S.SAVE_STATISTIC $order";
		elseif ($by == "s_ip")
			$strSqlOrder = "ORDER BY S.IP_1 $order, S.IP_2 $order, S.IP_3 $order, S.IP_4 $order";
		elseif ($by == "s_mask")
			$strSqlOrder = "ORDER BY S.MASK_1 $order, S.MASK_2 $order, S.MASK_3 $order, S.MASK_4 $order";
		elseif ($by == "s_url_to")
			$strSqlOrder = "ORDER BY S.URL_TO $order";
		elseif ($by == "s_url_from")
			$strSqlOrder = "ORDER BY S.URL_FROM $order";
		else
		{
			$strSqlOrder = "ORDER BY S.ID $order";
			$by = "s_id";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql =	"
			SELECT
				S.ID, S.ACTIVE, S.SAVE_STATISTIC,
				S.IP_1, S.IP_2, S.IP_3, S.IP_4,
				S.MASK_1, S.MASK_2, S.MASK_3, S.MASK_4,
				S.USER_AGENT, S.USER_AGENT_IS_NULL,
				S.URL_TO, S.URL_FROM, S.MESSAGE, S.MESSAGE_LID,
				S.URL_REDIRECT, S.COMMENTS, S.TEST, S.SITE_ID,
				".$DB->DateToCharFunction("S.TIMESTAMP_X")."	TIMESTAMP_X,
				".$DB->DateToCharFunction("S.DATE_END")."		DATE_END,
				".$DB->DateToCharFunction("S.DATE_START")."		DATE_START,
				if ((
					(S.DATE_START<=now() or S.DATE_START is null) and
					(S.DATE_END>=now() or S.DATE_END is null) and
					S.ACTIVE='Y'),
					'green',
					'red') as LAMP
			FROM
				b_stop_list S
			WHERE
			$strSqlSearch
			$strSqlOrder
			LIMIT ".intval(COption::GetOptionString('statistic','RECORDS_LIMIT'))."
			";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}

	public static function Check($test="N", $arParams = false)
	{
		$DB = CDatabase::GetModuleConnection('statistic');

		$test = ($test=="Y") ? "Y" : "N";

		$arStopRecord = false;
		$zr = false;
		//We did not use cache or it was cache miss
		if(!$arStopRecord)
		{
			$user_agent = "";
			$url_from = "";
			$url_to = "";
			$site_id = "";
			$site_where = "";
			$ip = array(0, 0, 0, 0);

			if ($arParams===false)
			{
				$ip = explode(".", $_SERVER["REMOTE_ADDR"]);
				$user_agent = trim($_SERVER["HTTP_USER_AGENT"]);
				$url_from = isset($_SERVER["HTTP_REFERER"])? $_SERVER["HTTP_REFERER"]: "";
				$url_to = __GetFullRequestUri();
				if (defined("SITE_ID"))
					$site_id = SITE_ID;
			}
			elseif(is_array($arParams))
			{
				$ip = explode(".", $arParams["IP"]);
				$user_agent = trim($arParams["USER_AGENT"]);
				$url_from = $arParams["URL_FROM"];
				$url_to = $arParams["URL_TO"];
				$site_id = $arParams["SITE_ID"];
			}

			$user_agent_len = strlen($user_agent);
			$user_agent = $DB->ForSql($user_agent, 500);
			$url_from = $DB->ForSql($url_from, 2000);
			$url_to = $DB->ForSql($url_to, 2000);
			if (strlen($site_id) > 0)
			{
				$site_where = "and (SITE_ID = '".$DB->ForSql($site_id, 2)."' or SITE_ID is null or length(SITE_ID)<=0)";
			}

			$strSql = "
				SELECT
					ID,
					MESSAGE,
					MESSAGE_LID,
					SAVE_STATISTIC,
					URL_REDIRECT,
					TEST
				FROM
					b_stop_list
				WHERE
					ACTIVE='Y'
				and TEST='$test'
				$site_where
				and (DATE_START<=now() or DATE_START is null)
				and (DATE_END>=now() or DATE_END is null)
				and	((((MASK_1 & ".intval($ip[0]).")=IP_1 and
						(MASK_2 & ".intval($ip[1]).")=IP_2 and
						(MASK_3 & ".intval($ip[2]).")=IP_3 and
						(MASK_4 & ".intval($ip[3]).")=IP_4)
							or (IP_1 is null and IP_2 is null and IP_3 is null and IP_4 is null))
						and (upper('".$DB->ForSql($user_agent)."') like concat('%',upper(USER_AGENT),'%')
							or length(USER_AGENT)<=0 or USER_AGENT is null)
						and ($user_agent_len=0 or USER_AGENT_IS_NULL<>'Y')
						and (upper('$url_from') like concat('%',upper(URL_FROM),'%')
							or length(URL_FROM)<=0 or URL_FROM is null)
						and (upper('$url_to') like concat('%',upper(URL_TO),'%')
							or length(URL_TO)<=0 or URL_TO is null)
					)
				";

			$z = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($zr = $z->Fetch())
			{
				$arStopRecord = array(
					"STOP" => "Y",
					"STOP_SAVE_STATISTIC" => $zr["SAVE_STATISTIC"],
					"STOP_MESSAGE" => $zr["MESSAGE"],
					"STOP_REDIRECT_URL" => $zr["URL_REDIRECT"],
					"STOP_MESSAGE_LID" => $zr["MESSAGE_LID"],
					"STOP_LIST_ID" => $zr["ID"],
				);
			}
			else
			{
				$arStopRecord = array(
					"STOP" => "N",
					"STOP_SAVE_STATISTIC" => "Y",
					"STOP_MESSAGE" => "",
					"STOP_REDIRECT_URL" => "",
					"STOP_MESSAGE_LID" => "",
					"STOP_LIST_ID" => 0,
				);
			}
		}
/*
		//Save session cache
		if($test == "N" && CACHED_b_stop_list !== false)
		{
			$_SESSION["STAT_STOP_LIST"] = array(
				"TIMESTAMP_X" => $TIMESTAMP_X,
				"DATA" => $arStopRecord,
			);
		}
*/
		if($test == "N")
		{
			foreach($arStopRecord as $key => $value)
			{
				$GLOBALS[$key] = $value;
			}
			return false;
		}
		else
		{
			if($zr)
				return intval($zr["ID"]);
			else
				return false;
		}
	}
}
?>
