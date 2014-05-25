<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################

class CAllVoteUser
{
	public static function err_mess()
	{
		$module_id = "vote";
		return "<br>Module: ".$module_id."<br>Class: CAllVoteUser<br>File: ".__FILE__;
	}

	public static function OnUserLogin()
	{
		$_SESSION["VOTE"] = array("VOTES" => array());
	}

	public static function Delete($USER_ID)
	{
		$err_mess = (CAllVoteUser::err_mess())."<br>Function: Delete<br>Line: ";
		global $DB;
		$USER_ID = intval($USER_ID);
		if ($USER_ID<=0) return;
		$strSql = "DELETE FROM b_vote_user WHERE ID=$USER_ID";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		return $res;
	}

	public static function GetList(&$by, &$order, $arFilter=Array(), &$is_filtered)
	{
		$err_mess = (CAllVoteUser::err_mess())."<br>Function: GetList<br>Line: ";
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0; $i<count($filter_keys); $i++)
			{
				$key = $filter_keys[$i];
				$val = $arFilter[$filter_keys[$i]];
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
				$match_value_set = (in_array($key."_EXACT_MATCH", $filter_keys)) ? true : false;
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("U.ID",$val,$match);
						break;
					case "DATE_START_1":
						$arSqlSearch[] = "U.DATE_FIRST>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_START_2":
						$arSqlSearch[] = "U.DATE_FIRST<=".$DB->CharToDateFunction($val." 23:59:59", "FULL");
						break;
					case "DATE_END_1":
						$arSqlSearch[] = "U.DATE_LAST>=".$DB->CharToDateFunction($val, "SHORT");
						break;
					case "DATE_END_2":
						$arSqlSearch[] = "U.DATE_LAST<=".$DB->CharToDateFunction($val." 23:59:59", "FULL");
						break;
					case "COUNTER_1":
						$arSqlSearch[] = "U.COUNTER>='".intval($val)."'";
						break;
					case "COUNTER_2":
						$arSqlSearch[] = "U.COUNTER<='".intval($val)."'";
						break;
					case "USER":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("U.AUTH_USER_ID,A.LOGIN,A.LAST_NAME,A.NAME",$val,$match);
						$left_join = "LEFT JOIN b_user A ON (A.ID=U.AUTH_USER_ID)";
						break;
					case "GUEST":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("U.STAT_GUEST_ID",$val,$match);
						break;
					case "IP":
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("U.LAST_IP",$val,$match,array("."));
						break;
					case "VOTE":
						$str_table = "
							INNER JOIN b_vote_event E ON (E.VOTE_USER_ID = U.ID)
							INNER JOIN b_vote V ON (V.ID = E.VOTE_ID)
							";
						$match = ($arFilter[$key."_EXACT_MATCH"]=="Y" && $match_value_set) ? "N" : "Y";
						$arSqlSearch[] = GetFilterQuery("E.VOTE_ID, V.TITLE",$val,$match);
						break;
					case "VOTE_ID":
						$str_table = "
							INNER JOIN b_vote_event E ON (E.VOTE_USER_ID = U.ID)
							INNER JOIN b_vote V ON (V.ID = E.VOTE_ID)
							";
						$match = ($arFilter[$key."_EXACT_MATCH"]=="N" && $match_value_set) ? "Y" : "N";
						$arSqlSearch[] = GetFilterQuery("E.VOTE_ID",$val,$match);
						break;
				}
			}
		}

		if ($by == "s_id")					$strSqlOrder = "ORDER BY U.ID";
		elseif ($by == "s_date_start")		$strSqlOrder = "ORDER BY U.DATE_FIRST";
		elseif ($by == "s_date_end")		$strSqlOrder = "ORDER BY U.DATE_LAST";
		elseif ($by == "s_counter")			$strSqlOrder = "ORDER BY U.COUNTER";
		elseif ($by == "s_user")			$strSqlOrder = "ORDER BY U.AUTH_USER_ID";
		elseif ($by == "s_ip")				$strSqlOrder = "ORDER BY U.LAST_IP";
		else 
		{
			$by = "s_id";
			$strSqlOrder = "ORDER BY U.ID";
		}
		if ($order!="asc")
		{
			$strSqlOrder .= " desc ";
			$order="desc";
		}

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT 
				U.ID, U.STAT_GUEST_ID, U.AUTH_USER_ID, U.COUNTER, U.LAST_IP,
				".$DB->DateToCharFunction("U.DATE_FIRST")."	DATE_FIRST,
				".$DB->DateToCharFunction("U.DATE_LAST")."	DATE_LAST
			FROM
				b_vote_user U
				$str_table
				$left_join
			WHERE
			$strSqlSearch
			GROUP BY 
				U.ID, U.STAT_GUEST_ID, U.AUTH_USER_ID, U.COUNTER, U.LAST_IP, U.DATE_FIRST, U.DATE_LAST
			$strSqlOrder
			";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$is_filtered = (IsFiltered($strSqlSearch));
		return $res;
	}
}
?>