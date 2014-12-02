<?
IncludeModuleLangFile(__FILE__);

// fix HTTP_REFERER, r1, r2 for  google ads
function __GoogleAd($set_new_adv=false, $r1=false, $r2=false, $s="http://pagead2.googlesyndication.com/")
{
	if (intval($_SESSION["SESS_SESSION_ID"])<=0 &&
		strlen($_SERVER["HTTP_REFERER"])>0 &&
		strncmp($s, $_SERVER["HTTP_REFERER"], strlen($s))==0)
	{
		$arr = parse_url($_SERVER["HTTP_REFERER"]);
		if (strlen($arr["query"])>0)
		{
			parse_str($arr["query"], $ar);
			if (strlen($ar["url"])>0)
			{
				$_SERVER["HTTP_REFERER"] = $ar["url"];
				if ($set_new_adv)
				{
					__SetReferer("referer1", "REFERER1_SYN");
					__SetReferer("referer2", "REFERER2_SYN");
					if (strlen($_SESSION["referer1"])<=0 && strlen($_SESSION["referer2"])<=0)
					{
						__GetReferringSite($protocol, $site_port, $site, $page, $_SERVER["HTTP_REFERER"]);
						$_SESSION["referer1"] = ($r1!==false) ? $r1 : "google_adwords";
						if ($r2!==false) $_SESSION["referer2"] = $r2;
						else
						{
							$_ar = explode(".", $site);
							if (is_array($_ar))
							{
								$_ar = array_reverse($_ar);
								$_SESSION["referer2"] = $_ar[1].".".$_ar[0];
							}
						}
					}
				}
				return true;
			}
		}
	}
	return false;
}

// returns referer site parameters
function __GetReferringSite(
	&$protocol, // http or https
	&$server_name, // www.site.ru:7900
	&$server_name_wo_port, // www.site.ru
	&$PAGE_FROM, // page with out site (uri)
	$URL_FROM = false
	)
{
	if($URL_FROM === false)
		$URL_FROM = $_SERVER["HTTP_REFERER"];

	if(!empty($URL_FROM))
	{
		$protocol = substr($URL_FROM, 0, 7);
		if($protocol == "http://")
		{
			$server_name = substr($URL_FROM, 7);
		}
		else
		{
			$protocol = substr($URL_FROM, 0, 8);
			if($protocol == "https://")
			{
				$server_name = substr($URL_FROM, 8);
			}
			else
			{
				$server_name = "";
				$protocol = "";
			}
		}

		if(!empty($server_name))
		{
			$p = strpos($server_name, "/");
			if($p > 0)
				$server_name = substr($server_name, 0, $p);

			$server_name = strtolower($server_name);

			$p = strpos($server_name,":");
			if($p > 0)
				$server_name_wo_port = substr($server_name, 0, $p);
			else
				$server_name_wo_port = $server_name;

			$PAGE_FROM = substr($URL_FROM, strlen($protocol.$server_name));
			if(strlen($PAGE_FROM) <= 0)
				$PAGE_FROM = "/";
		}

		return true;
	}
	else
	{
		return false;
	}
}

// referer1 and referer2 initialization
function __SetReferer($referer, $syn)
{
	stat_session_register($referer);
	global $$referer;
	if (strlen($_SESSION[$referer])<=0)
	{
		$_SESSION[$referer] = $$referer;
		$arr=explode(",",COption::GetOptionString("statistic", $syn));
		foreach ($arr as $s)
		{
			$s = trim($s);
			global $$s;
			if (strlen($$s)>0)
			{
				$_SESSION[$referer] = $$s;
				break;
			}
		}
	}
}

function __SetNoKeepStatistics()
{
	if (!isset($_SESSION["SESS_NO_KEEP_STATISTIC"]) || $_SESSION["SESS_NO_KEEP_STATISTIC"] == '')
	{
		$key_to_check = "no_keep_statistic_".LICENSE_KEY;
		if (isset($_REQUEST[$key_to_check]) && $_REQUEST[$key_to_check] <> '')
		{
			$_SESSION["SESS_NO_KEEP_STATISTIC"] = $_REQUEST[$key_to_check];
			if (!isset($_SESSION["SESS_NO_AGENT_STATISTIC"]) || $_SESSION["SESS_NO_AGENT_STATISTIC"] == '')
				$_SESSION["SESS_NO_AGENT_STATISTIC"] = $_REQUEST[$key_to_check];
		}
	}

	$key_to_check = "no_agent_statistic_".LICENSE_KEY;
	if (isset($_REQUEST[$key_to_check]) && $_REQUEST[$key_to_check] <> '')
	{
		if (!isset($_SESSION["SESS_NO_AGENT_STATISTIC"]) || $_SESSION["SESS_NO_AGENT_STATISTIC"] == '')
			$_SESSION["SESS_NO_AGENT_STATISTIC"] =  $_REQUEST[$key_to_check];
	}
}

function __SortLinkStat($ar1, $ar2)
{
	if ($ar1["CNT"]<$ar2["CNT"]) return 1;
	if ($ar1["CNT"]>$ar2["CNT"]) return -1;
	return 0;
}

function __IsHiddenLink($link)
{
	return preg_match("#(/bitrix/admin/|show_link_stat|bitrix_include_areas|logout|javascript)#", $link);
}

function __ModifyATags($matches)
{

	global $arHashLink;
	$link = $matches[3];

	if (strlen($link) && !__IsHiddenLink($link) && !preg_match("/<img/i", $matches[0]))
	{

		$link = __GetFullRequestUri(__GetFullCurPage($link));
		$crc32 = crc32ex($link);
		if(array_key_exists($crc32, $arHashLink))
		{
			$id = $arHashLink[$crc32]["ID"];
			$percent = $arHashLink[$crc32]["PERCENT"]."%";
			$cnt = $arHashLink[$crc32]["CNT"];
			$link = $arHashLink[$crc32]["LINK"];

			$title = str_replace("#CNT#", "$cnt", GetMessage("STAT_LABEL_TITLE"));
			$title = str_replace("#LINK#", "$link", $title);
			$title = str_replace("#PERCENT#", "$percent", $title);

			$max_width = 44;
			$wpx = round($max_width*($arHashLink[$crc32]["PERCENT"]/100.0));

			$tag = '
				<div style="position:relative; width:100%;">
					<div style="position:relative; border:black solid 1px; color:#FC9C05; width:100%; padding: 0px;" onmouseover="this.style.color=\'#000000\';"  onmouseout="this.style.color=\'#FC9C05\';">
					'.$matches[0].'
					</div>
					<div title="'.$title.'" OnClick="this.style.display=\'none\'" style="position:relative; z-index: 1; top: 0px; right: 0px; padding: 1px; width:100%; height: auto;" align="left">
						<table style="cursor:default; border:none; height:19px;" cellpadding="0" cellspacing="0" width="0%">
							<tr>
								<td style="padding:0px; border:none;">
								<table style="border-collapse:collapse;" cellpadding="0" cellspacing="0" width="0%">
									<tr>
										<td rowspan="2" width="0%" valign="middle" align="center" style="border:#000000 solid 1px; background-color:#A8A8A8; padding-top:0px; padding-bottom:0px; padding-left:2px; padding-right:2px;"><font style="font-family:Verdana; font-weight:normal; font-size:9px; color:#FFFFFF"><b>'.$id.'</b></font></td>
										<td align="center" width="0%" style="background-color:#FFFEE0; padding:0px; border:#000000 solid 1px;"><nobr><font style="font-family:Verdana; font-weight:normal; font-size:9px;"><font color="#000000">'.$percent.'</font></nobr><br><img src="/bitrix/images/1.gif" width="'.$max_width.'" height="1" border="0" alt=""></td>
									</tr>
									<tr>
										<td style="padding:0px; border:#000000 solid 1px;background-color:#FFFFFF;"><span style="display:block; width:'.$wpx.'px; overflow:hidden"><img src="/bitrix/images/statistic/scale.gif" height="5" border="0" alt=""></span></td>
									</tr>
								</table>
								</td>
							</tr>
						</table>
					</div>
				</div>
				';
			return $tag;
		}
	}
	return $matches[0];
}

function GetCookieString($arrCookie=false)
{
	$res = "";

	if ($arrCookie===false)
		$arrCookie = $_COOKIE;

	if (is_array($arrCookie))
	{
		foreach($arrCookie as $key => $value)
			$res .= "[".$key."] = ".$value."\n";
	}

	return $res;
}

function __GetCurrentPage()
{
	if (CModule::IncludeModule("wacko"))
		return CgeneralWacko::GetCurPage();
	else
		return __GetPage();
}

function __GetCurrentDir()
{
	global $APPLICATION;
	if (CModule::IncludeModule("wacko"))
		return CgeneralWacko::GetCurDir();
	else
		return $APPLICATION->GetCurDir();
}

function __GetPage($page=false, $with_imp_params=true, $curdir=false)
{
	if($page===false)
	{
		$page = $_SERVER["REQUEST_URI"];
		$check_path = false;
	}
	else
	{
		$page = str_replace("\\","/",$page);
		if(substr($page, 0, 1)!=="/" && strpos($page, "://")===false)
		{
			$curdir = ($curdir!==false) ? $curdir : __GetCurrentDir();
			$page = Rel2Abs($curdir, $page);
		}
		$check_path = true;
	}

	$found = strpos($page, "?");
	$sPath = ($found? substr($page, 0, $found) : $page);
	if ($check_path)
	{
		$sPath = str_replace("\\","/",$sPath);
		$last_char = substr($sPath, -1);
		if($last_char != "/" && @is_dir($_SERVER["DOCUMENT_ROOT"].$sPath))
			$sPath .= "/";
	}

	if ($with_imp_params)
	{
		$arImpParams = array_map("trim", explode(",", COption::GetOptionString("statistic", "IMPORTANT_PAGE_PARAMS")));
		$ar = @parse_url("".$page."");

		$arVars = array();
		parse_str($ar["query"], $arVars);
		foreach($arVars as $key => $value)
		{
			$key = str_replace("amp;", "", $key);
			$arVars[$key] = $value;
		}

		$i = 0;
		foreach($arImpParams as $key)
		{
			if (array_key_exists($key, $arVars) && !is_array($arVars[$key]))
			{
				if($i > 0)
					$sPath .= "&";
				else
					$sPath .= "?";

				$sPath .= urlencode($key)."=".urlencode($arVars[$key]);
				$i++;
			}
		}
	}

	$ar = explode("?", $sPath);
	if(strlen($ar[0]) > 0)
	{
		$arTail = explode(",", COption::GetOptionString("statistic", "DIRECTORY_INDEX"));
		foreach($arTail as $tail)
		{
			$tail = "/".trim($tail);
			if(substr($ar[0], -strlen($tail))==$tail)
			{
				$ar[0] = substr($ar[0], 0, strlen($ar[0])-strlen($tail)+1);
				break;
			}
		}
	}

	return implode("?", $ar);
}

function __GetFullCurPage($page=false, $with_imp_params=true)
{
	return __GetPage($page, $with_imp_params);
}

function __GetFullReferer($referer=false)
{
	if ($referer===false) $referer = $_SERVER["HTTP_REFERER"];
	$referer = __GetPage($referer);
	return $referer;
}

function __GetFullRequestUri($url=false, $host=false, $port=false, $protocol=false)
{
	global $HTTP_HOST, $SERVER_PORT, $APPLICATION;

	if ($url===false) $url = $_SERVER["REQUEST_URI"];
	if ($host===false) $host = $_SERVER["HTTP_HOST"];
	if ($port===false) $port = $_SERVER["SERVER_PORT"];
	if ($protocol===false) $protocol = CMain::IsHTTPS() ? "https" : "http";

	$res = "";
	$host_exists = (strpos($url, "http://")===false && strpos($url, "https://")===false) ? false : true;
	if (!$host_exists)
	{
		if (strlen($protocol)>0) $res = $protocol."://";
		if (strlen($host)>0) $res .= $host;
		if (intval($port)>0 && intval($port)!=80 && intval($port)!=443 && strpos($host, ":")===false) $res .= ":".$port;
	}
	if (strlen($url)>0) $res .= $url;

	if(strpos($res, "/bitrix/admin/")!==false)
	{
		$res = str_replace("&mode=list", "", $res);
		$res = str_replace("&mode=frame", "", $res);
	}

	return $res;
}

// returns base currency
function GetStatisticBaseCurrency()
{
	$base_currency = trim(COption::GetOptionString("statistic", "BASE_CURRENCY"));
	if ($base_currency!="xxx" && strlen($base_currency)>0)
	{
		if (CModule::IncludeModule("currency"))
		{
			if (CCurrency::GetByID($base_currency)) return $base_currency;
		}
	}
	return "";
}

function CleanUpResultCsv(&$item)
{
	$item = TrimEx($item, "\"");
}

function PrepareResultQuotes(&$item)
{
	$item = "\"".str_replace("\"","\"\"", $item)."\"";
}

function LoadEventsBySteps(
	$csvfile,			// CSV file name
	$time_step,			// one step duration
	$next_line,			// line number to start
	&$step_processed,	// number of lines handled
	&$step_loaded,		// loaded in one step
	&$step_duplicate,	// duplicates skipped in this step
	$check_unique="Y",	// check uniquness
	$base_currency="",	// module base currency
	&$next_pos
	)
{
	if ($fp = fopen($csvfile,"rb"))
	{
		if($next_pos>0) fseek($fp, $next_pos);
		$start = getmicrotime();
		$all_loaded = "";
		$next_line = intval($next_line);
		$read_lines = 0;
		$step_loaded = 0;
		$step_processed = 0;
		$step_duplicate = 0;
		while (!feof($fp))
		{
			$arrCSV = fgetcsv($fp, 4096, ",");
			if (is_array($arrCSV) && count($arrCSV)>0)
			{
				array_walk($arrCSV, "CleanUpResultCsv");

				$read_lines++;

				$step_processed++;

				$EVENT_ID	= $arrCSV[0];
				$EVENT3		= $arrCSV[1];
				$DATE_ENTER	= $arrCSV[2];
				$PARAMETER	= $arrCSV[3];
				$MONEY		= $arrCSV[4];
				$CURRENCY	= $arrCSV[5];
				$CHARGEBACK	= $arrCSV[6];
				$RES_MONEY	= $MONEY;
				$EVENT_ID	= intval($EVENT_ID);
				$CHARGEBACK = ($CHARGEBACK=="Y") ? "Y" : "N";
				if ($EVENT_ID>0)
				{
					if (strlen($base_currency)<=0)
					{
						$base_currency = GetStatisticBaseCurrency();
					}
					if (strlen($base_currency)>0)
					{
						if ($CURRENCY!=$base_currency && strlen(trim($CURRENCY))>0)
						{
							if (CModule::IncludeModule("currency"))
							{
								$stmp = MkDateTime(ConvertDateTime($DATE_ENTER,"D.M.Y H:I:S"),"d.m.Y H:i:s");
								$valDate = date("Y-m-d", $stmp);
								$rate = CCurrencyRates::GetConvertFactor($CURRENCY, $base_currency, $valDate);
								if ($rate>0) $RES_MONEY = $MONEY * $rate;
							}
						}
					}
					$RES_MONEY = round($RES_MONEY,2);
					$add_event="Y";
					if ($check_unique=="Y")
					{
						$arr = CStatEvent::DecodeGid($PARAMETER);
						$arFilter = array(
							"EVENT_ID"					=> $EVENT_ID,
							"EVENT3"					=> $EVENT3,
							"DATE"						=> $DATE_ENTER,
							"SESSION_ID"				=> $arr["SESSION_ID"],
							"GUEST_ID"					=> $arr["GUEST_ID"],
							"COUNTRY_ID"				=> $arr["COUNTRY_ID"],
							"ADV_ID"					=> $arr["ADV_ID"],
							"ADV_BACK"					=> $arr["ADV_BACK"],
							"SITE_ID"					=> $arr["SITE_ID"],
							);
						$rsEvents = CStatEvent::GetListUniqueCheck($arFilter);
						if ($arEvent = $rsEvents->Fetch())
						{
							$add_event="N";
							$step_duplicate++;
						}
					}
					if ($add_event=="Y")
					{
						CStatEvent::AddByID($EVENT_ID, $EVENT3, $DATE_ENTER, $PARAMETER, $RES_MONEY, "", $CHARGEBACK);
						$step_loaded++;
					}
					$end = getmicrotime();
					if (intval($time_step)>0 && ($end-$start)>intval($time_step))
					{
						$all_loaded = "N";
						break;
					}
				}
			}
		}
		if($all_loaded=="N")
			$next_pos=ftell($fp);
		else
			$next_pos=0;
		@fclose($fp);
		if ($all_loaded!="N")
		{
			$all_loaded = "Y";
			@unlink($csvfile);
		}
		return $all_loaded;
	}
}

function GetStatPathID($URL, $PREV_PATH_ID="")
{
	return crc32ex($URL.strval($PREV_PATH_ID));
}

function stat_session_register($var_name)
{
	static $arrSTAT_SESSION = array();
	if($var_name === false)
	{
		foreach($arrSTAT_SESSION as $key => $value)
		{
			global $$key;
			unset(${$key});
			unset($_SESSION[$key]);
		}
		$arrSTAT_SESSION = array();
	}
	elseif($var_name === true)
	{
		foreach($arrSTAT_SESSION as $key => $value)
			$arrSTAT_SESSION[$key] = $_SESSION[$key];
		return $arrSTAT_SESSION;
	}
	else
	{
		$arrSTAT_SESSION[$var_name] = 0;
	}
}

function get_guest_md5()
{
	$md5 = md5(
		$_SERVER["HTTP_USER_AGENT"].
		$_SERVER["REMOTE_ADDR"].
		$_SERVER["HTTP_X_FORWARDED_FOR"]
		);
	return $md5;
}

function GetEventSiteID()
{
	return GetStatGroupSiteID();
}

function GetStatGroupSiteID()
{
	$site_id = COption::GetOptionString("statistic", "EVENT_GID_SITE_ID");
	return $site_id;
}

function SendDailyStatistics()
{
	__SetNoKeepStatistics();
	if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
	{
		global $MESS;

		$rsSite = CSite::GetDefList();
		$arSite = $rsSite->Fetch();
		$charset = $arSite["CHARSET"];

		$now_full_date = GetTime(time(), "FULL", $arSite["ID"], true);
		$now_date = GetTime(time(), "SHORT", $arSite["ID"], true);
		$yesterday_date = GetTime(time()-86400, "SHORT", $arSite["ID"], true);
		$bef_yesterday_date = GetTime(time()-172800, "SHORT", $arSite["ID"], true);

		$arComm = CTraffic::GetCommonValues();
		$adv = CAdv::GetList($a_by, $a_order, array(), $is_filtered, "", $arrGROUP_DAYS, $v);
		$events = CStatEventType::GetList(($e_by="s_stat"),($e_order="desc"),array(), $is_filtered);
		$referers = CTraffic::GetRefererList($by, $order, array(), $is_filtered);
		$phrases = CTraffic::GetPhraseList($s_by, $s_order, array(), $is_filtered);
		$searchers = CSearcher::GetList(($f_by="s_stat"), ($f_order="desc"), array(), $is_filtered);

		$OLD_MESS = $MESS;
		$MESS = array();
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/include.php", $arSite["LANGUAGE_ID"]);
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/admin/stat_list.php", $arSite["LANGUAGE_ID"]);

		$HTML_HEADER = '
			<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
			<html>
			<head>
			<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">
			<style>
			.tablehead1 {background-color:#C2DBED; padding:2px; border-top: 1px solid #A8C2D7; border-bottom: 1px solid #A8C2D7; border-left: 1px solid #A8C2D7;}
			.tablehead2 {background-color:#C2DBED; padding:2px; border-top: 1px solid #A8C2D7; border-bottom: 1px solid #A8C2D7;}
			.tablehead3 {background-color:#C2DBED; padding:2px; border-top: 1px solid #A8C2D7; border-bottom: 1px solid #A8C2D7; border-right: 1px solid #A8C2D7;}
			.tablebody1 {background-color:#F0F1F2; padding:2px; border-left:#B9D3E6 solid 1px; border-bottom:#B9D3E6 solid 1px;}
			.tablebody2 {background-color:#F0F1F2; padding:2px; border-bottom:#B9D3E6 solid 1px;}
			.tablebody3 {background-color:#F0F1F2; padding:2px; border-right:#B9D3E6 solid 1px; border-bottom:#B9D3E6 solid 1px;}
			.tablebodytext {font-family: Arial, Helvetica, sans-serif; font-size:12px; color:#000000;}
			.tableheadtext {font-family: Arial, Helvetica, sans-serif; font-size:12px; color:#000000;}
			.tablelinebottom {border-bottom:1pt solid #D1D1D1}
			.notesmall {font-family: Arial, Helvetica, sans-serif; font-size:11px; color:#008400; font-weight:normal;}
			.tablebody1_sel {background-color:#E0EBF1; padding:2px; border-left:#B9D3E6 solid 1px; border-bottom:#B9D3E6 solid 1px;}
			.tablebody2_sel {background-color:#E0EBF1; padding:2px; border-bottom:#B9D3E6 solid 1px;}
			.tablebody3_sel {background-color:#E0EBF1; padding:2px; border-right:#B9D3E6 solid 1px; border-bottom:#B9D3E6 solid 1px;}
			</style>
			</head>
			<body bgcolor="FFFFFF" leftmargin="2" topmargin="2" marginwidth="2" marginheight="2">
			';

		$HTML_COMMON = '
					<table border="0" cellspacing="1" cellpadding="3" width="100%">
						<tr>
							<td valign="top" align="center" class="tablehead1" width="48%" nowrap><font class="tableheadtext">'.GetMessage("STAT_VISIT").'</font></td>
							<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_TODAY").'</font><br><font class="notesmall">'.$now_date.'</font></td>
							<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_YESTERDAY").'</font><br><font class="notesmall">'.$yesterday_date.'</font></td>
							<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_BEFORE_YESTERDAY").'</font><br><font class="notesmall">'.$bef_yesterday_date.'</font></td>
							<td valign="top" align="center" class="tablehead3" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_TOTAL_1").'</font></td>
						</tr>
						<tr valign="top">
							<td valign="top" class="tablebody1" width="48%" nowrap><font class="tablebodytext">'.GetMessage("STAT_HITS").'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["TODAY_HITS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["YESTERDAY_HITS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["B_YESTERDAY_HITS"].'</font></td>
							<td valign="top" align="right" class="tablebody3" width="13%" nowrap><font class="tablebodytext">'.$arComm["TOTAL_HITS"].'&nbsp;&nbsp;</font></td>
						</tr>
						<tr valign="top">
							<td valign="top" class="tablebody1" width="48%" nowrap><font class="tablebodytext">'.GetMessage("STAT_HOSTS").'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["TODAY_HOSTS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["YESTERDAY_HOSTS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["B_YESTERDAY_HOSTS"].'</font></td>
							<td valign="top" align="right" class="tablebody3" width="13%" nowrap><font class="tablebodytext">'.$arComm["TOTAL_HOSTS"].'&nbsp;&nbsp;</font></td>
						</tr>
						<tr valign="top">
							<td valign="top" class="tablebody1" width="48%" nowrap><font class="tablebodytext">'.GetMessage("STAT_SESSIONS").'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["TODAY_SESSIONS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["YESTERDAY_SESSIONS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["B_YESTERDAY_SESSIONS"].'</font></td>
							<td valign="top" align="right" class="tablebody3" width="13%" nowrap><font class="tablebodytext">'.$arComm["TOTAL_SESSIONS"].'&nbsp;&nbsp;</font></td>
						</tr>
						<tr valign="top">
							<td valign="top" class="tablebody1" width="48%" nowrap><font class="tablebodytext">'.GetMessage("STAT_C_EVENTS").'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["TODAY_EVENTS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["YESTERDAY_EVENTS"].'</font></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap><font class="tablebodytext">'.$arComm["B_YESTERDAY_EVENTS"].'</font></td>
							<td valign="top" align="right" class="tablebody3" width="13%" nowrap><font class="tablebodytext">'.$arComm["TOTAL_EVENTS"].'&nbsp;&nbsp;</font></td>
						</tr>
						<tr valign="top">
							<td valign="top" class="tablebody1" width="48%" nowrap>
								<table border="0" cellspacing="0" cellpadding="0" width="100%">
									<tr>
										<td width="100%"><font class="tablebodytext">'.GetMessage("STAT_GUESTS").'</font></td>
										<td width="0%" align="right" class="tablelinebottom" nowrap><font class="tablebodytext">'.GetMessage("STAT_TOTAL").'</font></td>
									</tr>
									<tr>
										<td></td>
										<td class="tablelinebottom" align="right" nowrap><font class="tablebodytext">'.GetMessage("STAT_NEW").'</font></td>
									</tr>
									<tr>
										<td></td>
										<td align="right" nowrap><font class="tablebodytext">'.GetMessage("STAT_ONLINE").'</font></td>
									</tr>
								</table></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap>
								<table cellspacing=0 cellpadding=0 width="100%">
									<tr><td class="tablelinebottom" align="right" width="100%"><font class="tablebodytext">'.$arComm["TODAY_GUESTS"].'</font></td></tr>
									<tr><td class="tablelinebottom" align="right"><font class="tablebodytext">'.$arComm["TODAY_NEW_GUESTS"].'</font></td></tr>
									<tr><td align="right"><font class="tablebodytext">'.$arComm["ONLINE_GUESTS"].'</font></td></tr>
								</table></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap>
								<table cellspacing=0 cellpadding=0 width="100%">
									<tr><td class="tablelinebottom" align="right" width="100%"><font class="tablebodytext">'.$arComm["YESTERDAY_GUESTS"].'</font></td></tr>
									<tr><td class="tablelinebottom" align="right"><font class="tablebodytext">'.$arComm["YESTERDAY_NEW_GUESTS"].'</font></td></tr>
								</table></td>
							<td valign="top" align="right" class="tablebody2" width="13%" nowrap>
								<table cellspacing=0 cellpadding=0 width="100%">
									<tr><td align="right" class="tablelinebottom" width="100%"><font class="tablebodytext">'.$arComm["B_YESTERDAY_GUESTS"].'</font></td></tr>
									<tr><td class="tablelinebottom" align="right"><font class="tablebodytext">'.$arComm["B_YESTERDAY_NEW_GUESTS"].'</font></td></tr>
								</table></td>
							<td valign="top" align="right" class="tablebody3" width="13%" nowrap>
								<table cellspacing=0 cellpadding=0 width="100%">
									<tr><td class="tablelinebottom" align="right" width="100%"><font class="tablebodytext">'.$arComm["TOTAL_GUESTS"].'&nbsp;&nbsp;</font></td></tr>
									<tr><td class="tablelinebottom" align="right"><font class="tablebodytext">&nbsp;</font></td></tr>
								</table></td>
						</tr>
					</table>
				</td>
			</tr>
			</table>
			';

		$HTML_ADV = '
			<font class="tablebodytext">'.GetMessage("STAT_ADV").' ('.GetMessage("STAT_DIRECT_SESSIONS").') (Top 10):</font><br>
			<table border="0" cellspacing="1" cellpadding="3" width="100%">
				<tr>
					<td valign="top" align="center" class="tablehead1" width="48%" nowrap><font class="tableheadtext">'.GetMessage("STAT_ADV_NAME").'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_TODAY").'</font><br><font class="notesmall">'.$now_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_YESTERDAY").'</font><br><font class="notesmall">'.$yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_BEFORE_YESTERDAY").'</font><br><font class="notesmall">'.$bef_yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead3" width="13%" nowrap><font class="tableheadtext">'.GetMessage("STAT_TOTAL_1").'</font></td>
				</tr>
			';
			$i = 0;
			$total_SESSIONS_TODAY = 0;
			$total_SESSIONS_YESTERDAY = 0;
			$total_SESSIONS_BEF_YESTERDAY = 0;
			$total_SESSIONS = 0;
			while ($ar = $adv->Fetch()) :
				$i++;
				$total_SESSIONS_TODAY += $ar["SESSIONS_TODAY"];
				$total_SESSIONS_YESTERDAY += $ar["SESSIONS_YESTERDAY"];
				$total_SESSIONS_BEF_YESTERDAY += $ar["SESSIONS_BEF_YESTERDAY"];
				$total_SESSIONS += $ar["SESSIONS"];
				if ($i<=10) :
			$HTML_ADV .= '
				<tr>
					<td valign="top" class="tablebody1"><font class="tablebodytext">['.$ar["ID"].']&nbsp;'.$ar["REFERER1"].'&nbsp;/&nbsp;'.$ar["REFERER2"].'</font></td>
					<td valign="top" align="right" class="tablebody2"><font class="tablebodytext">&nbsp;'.($ar["SESSIONS_TODAY"]>0 ? $ar["SESSIONS_TODAY"] : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody2"><font class="tablebodytext">&nbsp;'.($ar["SESSIONS_YESTERDAY"]>0 ? $ar["SESSIONS_YESTERDAY"] : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody2"><font class="tablebodytext">&nbsp;'.($ar["SESSIONS_BEF_YESTERDAY"]>0 ? $ar["SESSIONS_BEF_YESTERDAY"] : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody3"><font class="tablebodytext">&nbsp;'.($ar["SESSIONS"]>0 ? $ar["SESSIONS"] : "&nbsp;").'</font></td>
				</tr>
				';
		endif;
	endwhile;
			$HTML_ADV .= '
				<tr>
					<td valign="top" align="right" class="tablebody1_sel" style="padding:3px"><font class="tablebodytext">'.GetMessage("STAT_TOTAL").'</font></td>
					<td valign="top" align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">&nbsp;'.($total_SESSIONS_TODAY>0 ? $total_SESSIONS_TODAY : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">&nbsp;'.($total_SESSIONS_YESTERDAY>0 ? $total_SESSIONS_YESTERDAY : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">&nbsp;'.($total_SESSIONS_BEF_YESTERDAY>0 ? $total_SESSIONS_BEF_YESTERDAY : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody3_sel" style="padding:3px"><font class="tablebodytext">&nbsp;'.($total_SESSIONS>0 ? $total_SESSIONS : "&nbsp;").'</font></td>
			</table>
			';

		$HTML_EVENTS = '
			<font class="tablebodytext">'.GetMessage("STAT_EVENTS_2").' (Top 10):</font><br>
			<table border="0" cellspacing="1" cellpadding="3" width="100%">
				<tr>
					<td valign="top" align="center" class="tablehead1" width="48%" nowrap><font class="tableheadtext">'.GetMessage("STAT_EVENT").'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap>
						<font class="tablebodytext">'.GetMessage("STAT_TODAY").'</font><br><font class="notesmall">'.$now_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap>
						<font class="tablebodytext">'.GetMessage("STAT_YESTERDAY").'</font><br><font class="notesmall">'.$yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap>
						<font class="tablebodytext">'.GetMessage("STAT_BEFORE_YESTERDAY").'</font><br><font class="notesmall">'.$bef_yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead3" width="13%" nowrap>
						<font class="tablebodytext">'.GetMessage("STAT_TOTAL_1").'</font></td>
				</tr>
			';
			$i = 0;
			$total_TODAY_COUNTER = 0;
			$total_YESTERDAY_COUNTER = 0;
			$total_B_YESTERDAY_COUNTER = 0;
			$total_TOTAL_COUNTER = 0;
			while ($er = $events->Fetch()) :
				$i++;
				$total_TODAY_COUNTER += intval($er["TODAY_COUNTER"]);
				$total_YESTERDAY_COUNTER += intval($er["YESTERDAY_COUNTER"]);
				$total_B_YESTERDAY_COUNTER += intval($er["B_YESTERDAY_COUNTER"]);
				$total_TOTAL_COUNTER += intval($er["TOTAL_COUNTER"]);
				if ($i<=10) :
				$HTML_EVENTS .= '
				<tr valign="top">
					<td valign="top" class="tablebody1" width="0%" nowrap><font class="tablebodytext">'.$er["EVENT"].'</font></td>
					<td valign="top" align="right" class="tablebody2" width="0%" nowrap><font class="tablebodytext">'.($er["TODAY_COUNTER"]>0 ? $er["TODAY_COUNTER"] : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody2" width="0%" nowrap><font class="tablebodytext">'.($er["YESTERDAY_COUNTER"]>0 ? $er["YESTERDAY_COUNTER"] : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody2" width="0%" nowrap><font class="tablebodytext">'.($er["B_YESTERDAY_COUNTER"]>0 ? $er["B_YESTERDAY_COUNTER"] : "&nbsp;").'</font></td>
					<td valign="top" align="right" class="tablebody3" width="0%" nowrap><font class="tablebodytext">'.($er["TOTAL_COUNTER"]>0 ? $er["TOTAL_COUNTER"] : "&nbsp;").'</font></td>
				</tr>
				';
					endif;
				endwhile;
				$HTML_EVENTS .= '
				<tr valign="top">
					<td align="right" class="tablebody1_sel" style="padding:3px"><font class="tablebodytext">'.GetMessage("STAT_TOTAL").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_TODAY_COUNTER>0 ? $total_TODAY_COUNTER : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_YESTERDAY_COUNTER>0 ? $total_YESTERDAY_COUNTER : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_B_YESTERDAY_COUNTER>0 ? $total_B_YESTERDAY_COUNTER : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3_sel" style="padding:3px"><font class="tablebodytext">'.($total_TOTAL_COUNTER>0 ? $total_TOTAL_COUNTER : "&nbsp;").'</font></td>
			</table>
			';

		$HTML_REFERERS = '
			<font class="tablebodytext">'.GetMessage("STAT_REFERERS").' (Top 10):</font><br>
			<table border="0" cellspacing="1" cellpadding="3" width="100%">
				<tr>
					<td valign="top" align="center" class="tablehead1" width="48%" nowrap><font class="tableheadtext">'.GetMessage("STAT_SERVER").'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_TODAY").'</font><br><font class="notesmall">'.$now_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_YESTERDAY").'</font><br><font class="notesmall">'.$yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_BEFORE_YESTERDAY").'</font><br><font class="notesmall">'.$bef_yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead3" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_TOTAL_1").'</font></td>
				</tr>
			';
			$i = 0;
			$total_TODAY_REFERERS = 0;
			$total_YESTERDAY_REFERERS = 0;
			$total_B_YESTERDAY_REFERERS = 0;
			$total_TOTAL_REFERERS = 0;
			while ($rr = $referers->Fetch()) :
				$i++;
				$total_TODAY_REFERERS += $rr["TODAY_REFERERS"];
				$total_YESTERDAY_REFERERS += $rr["YESTERDAY_REFERERS"];
				$total_B_YESTERDAY_REFERERS += $rr["B_YESTERDAY_REFERERS"];
				$total_TOTAL_REFERERS += $rr["TOTAL_REFERERS"];
				if ($i<=10) :
				$HTML_REFERERS .= '
				<tr>
					<td valign="top" class="tablebody1" nowrap><font class="tablebodytext">'.$rr["SITE_NAME"].'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($rr["TODAY_REFERERS"]>0 ? $rr["TODAY_REFERERS"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($rr["YESTERDAY_REFERERS"]>0 ? $rr["YESTERDAY_REFERERS"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($rr["B_YESTERDAY_REFERERS"]>0 ? $rr["B_YESTERDAY_REFERERS"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3"><font class="tablebodytext">'.($rr["TOTAL_REFERERS"]>0 ? $rr["TOTAL_REFERERS"] : "&nbsp;").'</font></td>
				</tr>
				';
				endif;
			endwhile;
				$HTML_REFERERS .= '
				<tr valign="top">
					<td align="right" class="tablebody1_sel" style="padding:3px"><font class="tablebodytext">'.GetMessage("STAT_TOTAL").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_TODAY_REFERERS>0 ? $total_TODAY_REFERERS : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_YESTERDAY_REFERERS>0 ? $total_YESTERDAY_REFERERS : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_B_YESTERDAY_REFERERS>0 ? $total_B_YESTERDAY_REFERERS : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3_sel" style="padding:3px"><font class="tablebodytext">'.($total_TOTAL_REFERERS>0 ? $total_TOTAL_REFERERS : "&nbsp;").'</font></td>
			</table>
			';

		$HTML_PHRASES = '
			<font class="tablebodytext">'.GetMessage("STAT_PHRASES").' (Top 10):</font><br>
			<table border="0" cellspacing="1" cellpadding="3" width="100%">
				<tr>
					<td valign="top" align="center" class="tablehead1" width="48%" nowrap><font class="tableheadtext">'.GetMessage("STAT_PHRASE").'</td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_TODAY").'</font><br><font class="notesmall">'.$now_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_YESTERDAY").'</font><br><font class="notesmall">'.$yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_BEFORE_YESTERDAY").'</font><br><font class="notesmall">'.$bef_yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead3" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_TOTAL_1").'</font></td>
				</tr>
			';
				$i = 0;
				$total_TODAY_PHRASES = 0;
				$total_YESTERDAY_PHRASES = 0;
				$total_B_YESTERDAY_PHRASES = 0;
				$total_TOTAL_PHRASES = 0;
				while ($pr = $phrases->GetNext()) :
					$i++;
					$total_TODAY_PHRASES += $pr["TODAY_PHRASES"];
					$total_YESTERDAY_PHRASES += $pr["YESTERDAY_PHRASES"];
					$total_B_YESTERDAY_PHRASES += $pr["B_YESTERDAY_PHRASES"];
					$total_TOTAL_PHRASES += $pr["TOTAL_PHRASES"];
					if ($i<=10) :
				$HTML_PHRASES .= '
				<tr valign="top">
					<td valign="top" class="tablebody1" width="0%" nowrap><font class="tablebodytext">'.TruncateText($pr["PHRASE"],50).'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($pr["TODAY_PHRASES"]>0 ? $pr["TODAY_PHRASES"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($pr["YESTERDAY_PHRASES"]>0 ? $pr["YESTERDAY_PHRASES"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($pr["B_YESTERDAY_PHRASES"]>0 ? $pr["B_YESTERDAY_PHRASES"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3"><font class="tablebodytext">'.($pr["TOTAL_PHRASES"]>0 ? $pr["TOTAL_PHRASES"] : "&nbsp;").'</font></td>
				</tr>
				';
					endif;
				endwhile;
				$HTML_PHRASES .= '
				<tr valign="top">
					<td align="right" class="tablebody1_sel" style="padding:3px"><font class="tablebodytext">'.GetMessage("STAT_TOTAL").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_TODAY_PHRASES>0 ? $total_TODAY_PHRASES : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_YESTERDAY_PHRASES>0 ? $total_YESTERDAY_PHRASES : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_B_YESTERDAY_PHRASES>0 ? $total_B_YESTERDAY_PHRASES : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3_sel" style="padding:3px"><font class="tablebodytext">'.($total_TOTAL_PHRASES>0 ? $total_TOTAL_PHRASES : "&nbsp;").'</font></td>
			</table>
			';

		$HTML_SEARCHERS = '
			<font class="tablebodytext">'.GetMessage("STAT_SITE_INDEXING").' (Top 10):</font><br>
			<table border="0" cellspacing="1" cellpadding="3" width="100%">
				<tr>
					<td valign="top" align="center" class="tablehead1" width="48%" nowrap><font class="tableheadtext">'.GetMessage("STAT_SEARCHER").'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_TODAY").'</font><br><font class="notesmall">'.$now_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_YESTERDAY").'</font><br><font class="notesmall">'.$yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead2" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_BEFORE_YESTERDAY").'</font><br><font class="notesmall">'.$bef_yesterday_date.'</font></td>
					<td valign="top" align="center" class="tablehead3" width="13%" nowrap><font class="tablebodytext">'.GetMessage("STAT_TOTAL_1").'</font></td>
				</tr>
			';
			$i = 0;
			$total_TODAY_HITS = 0;
			$total_YESTERDAY_HITS = 0;
			$total_B_YESTERDAY_HITS = 0;
			$total_TOTAL_HITS = 0;
			while ($fr = $searchers->Fetch()) :
				$i++;
				$total_TODAY_HITS += $fr["TODAY_HITS"];
				$total_YESTERDAY_HITS += $fr["YESTERDAY_HITS"];
				$total_B_YESTERDAY_HITS += $fr["B_YESTERDAY_HITS"];
				$total_TOTAL_HITS += $fr["TOTAL_HITS"];
				if ($i<=10) :
				$HTML_SEARCHERS .= '
				<tr valign="top">
					<td valign="top" class="tablebody1" width="0%" nowrap><font class="tablebodytext">'.$fr["NAME"].'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($fr["TODAY_HITS"]>0 ? $fr["TODAY_HITS"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($fr["YESTERDAY_HITS"]>0 ? $fr["YESTERDAY_HITS"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2"><font class="tablebodytext">'.($fr["B_YESTERDAY_HITS"]>0 ? $fr["B_YESTERDAY_HITS"] : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3"><font class="tablebodytext">'.($fr["TOTAL_HITS"]>0 ? $fr["TOTAL_HITS"] : "&nbsp;").'</font></td>
				</tr>
				';
					endif;
				endwhile;
				$HTML_SEARCHERS .= '
				<tr valign="top">
					<td align="right" class="tablebody1_sel" style="padding:3px"><font class="tablebodytext">'.GetMessage("STAT_TOTAL").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_TODAY_HITS>0 ? $total_TODAY_HITS : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_YESTERDAY_HITS>0 ? $total_YESTERDAY_HITS : "&nbsp;").'</font></td>
					<td align="right" class="tablebody2_sel" style="padding:3px"><font class="tablebodytext">'.($total_B_YESTERDAY_HITS>0 ? $total_B_YESTERDAY_HITS : "&nbsp;").'</font></td>
					<td align="right" class="tablebody3_sel" style="padding:3px"><font class="tablebodytext">'.($total_TOTAL_HITS>0 ? $total_TOTAL_HITS : "&nbsp;").'</font></td>
			</table>
			';

		$HTML_FOOTER = '
			</body>
			</html>
			';

		$arEventFields = array(
			"SERVER_TIME"		=> $now_full_date,
			"HTML_HEADER"		=> $HTML_HEADER,
			"HTML_FOOTER"		=> $HTML_FOOTER,
			"HTML_COMMON"		=> $HTML_COMMON,
			"HTML_ADV"			=> $HTML_ADV,
			"HTML_EVENTS"		=> $HTML_EVENTS,
			"HTML_REFERERS"		=> $HTML_REFERERS,
			"HTML_PHRASES"		=> $HTML_PHRASES,
			"HTML_SEARCHERS"	=> $HTML_SEARCHERS,
			"EMAIL_TO"			=> COption::GetOptionString("main", "email_from", "")
			);

		/*
		echo $HTML_HEADER."<br>";
		echo $HTML_FOOTER."<br>";
		echo $HTML_COMMON."<br>";
		echo $HTML_ADV."<br>";
		echo $HTML_EVENTS."<br>";
		echo $HTML_REFERERS."<br>";
		echo $HTML_PHRASES."<br>";
		echo $HTML_SEARCHERS."<br>";
		die();
		*/

		CEvent::Send("STATISTIC_DAILY_REPORT", $arSite["ID"], $arEventFields);
		$MESS = $OLD_MESS;
	}
	return "SendDailyStatistics();";
}

function crc32ex($s)
{
	$c = crc32($s);
	if($c > 0x7FFFFFFF)
		$c = -(0xFFFFFFFF - $c + 1);
	return $c;
}

function AdminListCheckDate(&$lAdmin, $arDates)
{
	$DB = CDatabase::GetModuleConnection('statistic');

	$ok1 = false;
	list($id1, $date1) = each($arDates);
	if(strlen($date1)>0)
	{
		if(!CheckDateTime($date1))
		{
			if(is_object($lAdmin))
				$lAdmin->AddFilterError(GetMessage("STAT_WRONG_DATE_FROM"));
			else
				$lAdmin.=GetMessage("STAT_WRONG_DATE_FROM")."<br>";
		}
		else
		{
			$ok1 = true;
		}
	}

	$ok2 = false;
	list($id2, $date2) = each($arDates);
	if(strlen($date2)>0)
	{
		if(!CheckDateTime($date2))
		{
			if(is_object($lAdmin))
				$lAdmin->AddFilterError(GetMessage("STAT_WRONG_DATE_TILL"));
			else
				$lAdmin.=GetMessage("STAT_WRONG_DATE_TILL")."<br>";
		}
		else
		{
			$ok2 = true;
		}
	}

	if($ok1 && $ok2 && $DB->CompareDates($date1, $date2)==1)
	{
		if(is_object($lAdmin))
			$lAdmin->AddFilterError(GetMessage("STAT_FROM_TILL_DATE"));
		else
			$lAdmin.=GetMessage("STAT_FROM_TILL_DATE")."<br>";
	}

	return true;
}

function StatAdminListFormatURL($url, $arOptions = array())
{
	$new_window = false;
	if(isset($arOptions["new_window"]) && $arOptions["new_window"] == true)
		$new_window = true;

	$href_class = '';
	if(isset($arOptions["attention"]) && $arOptions["attention"] == true)
		$href_class = 'stat_attention';

	$href_title = '';
	if(isset($arOptions["title"]))
		$href_title = htmlspecialcharsex($arOptions["title"]);

	$max_display_chars = 0;
	if(isset($arOptions["max_display_chars"]))
	{
		if($arOptions["max_display_chars"] === 'default')
			$max_display_chars = 80;
		elseif($arOptions["max_display_chars"] > 0)
			$max_display_chars = $arOptions["max_display_chars"];
	}

	$chars_per_line = 0;
	if(isset($arOptions["chars_per_line"]))
	{
		if($arOptions["chars_per_line"] === 'default')
			$chars_per_line = 33;
		elseif($arOptions["chars_per_line"] > 0)
			$chars_per_line = $arOptions["chars_per_line"];
	}

	$line_delimiter = '<br />';
	if(isset($arOptions["line_delimiter"]))
		$line_delimiter = $arOptions["line_delimiter"];

	$kill_sessid = true;
	if(isset($arOptions["kill_sessid"]))
		$kill_sessid = $arOptions["kill_sessid"];

	if($kill_sessid)
	{
		$url = preg_replace('/(sessid=[a-zA-Z0-9]+)/', '', $url);
		$url = str_replace('&&', '&', $url);
		$url = str_replace('?&', '?', $url);
		$url = trim($url, "?&");
	}

	$htmlA = '<a href="'.htmlspecialcharsex($url).'"';

	if($new_window)
		$htmlA .= ' target="_blank"';

	if($href_class)
		$htmlA .= ' class="'.$href_class.'"';

	if($href_title)
		$htmlA .= ' title="'.$href_title.'"';

	$htmlA .= '>';

	$url_display = $url;
	if($max_display_chars > 0 && strlen($url) >= $max_display_chars)
		$url_display = substr($url, 0, intval($max_display_chars*0.7)).'...'.substr($url, -intval($max_display_chars*0.2));

	if($chars_per_line > 0)
	{
		$url_display = InsertSpaces($url_display, $chars_per_line, "\x01");
		$url_display = htmlspecialcharsbx($url_display);
		$url_display = str_replace("\x01", $line_delimiter, $url_display);
	}
	else
	{
		$url_display = htmlspecialcharsbx($url_display);
	}

	return $htmlA.$url_display.'</a>';
}

function is_utf8_url($url)
{
	//http://mail.nl.linux.org/linux-utf8/1999-09/msg00110.html
	if(preg_match_all("/(%[0-9A-F]{2})/i", $url, $match))
	{
		$arBytes = array();
		foreach($match[1] as $hex)
			$arBytes[] = hexdec(substr($hex, 1));
		$is_utf = 0;
		foreach($arBytes as $i => $byte)
		{
			if( ($byte & 0xC0) == 0x80 )
			{
				if( ($i > 0) && (($arBytes[$i-1] & 0xC0) == 0xC0) )
					$is_utf++;
				elseif( ($i > 0) && (($arBytes[$i-1] & 0x80) == 0x00) )
					$is_utf--;
			}
			elseif( ($i > 0) && (($arBytes[$i-1] & 0xC0) == 0xC0) )
			{
					$is_utf--;
			}
		}
		return $is_utf > 0;
	}
	else
	{
		return false;
	}
}
/*
$arTest = array(
	"http://bsm6.business.ru.mysql.max/bitrix/admin/php_command_line.php?lang=ru", //ASCII
	"http://www.yandex.ru/yandsearch?text=%D0%B1%D1%8B%D0%BB%D0%BE+", //Yndex utf
	"http://www.yandex.ru/yandsearch?text=%E1%E8%F2&rpt=rad", //Yandex koi
	"http://www.google.cn/search?hl=zh-CN&ie=GB2312&q=%CB%F9%D3%D0%CD%F8%D2%B3&btnG=Google+%CB%D1%CB%F7&meta=", //China multibyte
	"http://www.google.ru/search?hl=ru&q=%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82&btnG=%D0%9F%D0%BE%D0%B8%D1%81%D0%BA+%D0%B2+Google&lr=&aq=f", //russian utf
);
foreach($arTest as $test)
{
	echo $test,":",(is_utf8_url($test)? "Y": "N"),"<hr>\n";
}
*/

class CStatisticSort
{
	var $field = false;

	public function __construct($field = "")
	{
		return $this->CStatisticSort($field);
	}

	public function CStatisticSort($field = "")
	{
		$this->field = $field;
	}

	public static function Sort(&$ar, $field)
	{
		$sort = new CStatisticSort($field);
		uasort($ar, array($sort, "Compare"));
	}

	public function Compare($ar1, $ar2)
	{
		if($ar1[$this->field] < $ar2[$this->field])
			return 1;
		if($ar1[$this->field] > $ar2[$this->field])
			return -1;
		if($ar1["CITY_ID"] < $ar2["CITY_ID"])
			return -1;
		if($ar1["CITY_ID"] > $ar2["CITY_ID"])
			return 1;
		return 0;
	}
}

?>
