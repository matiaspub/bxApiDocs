<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/keepstatistic.php");
IncludeModuleLangFile(__FILE__);


/**
 * <b>CStatistics</b> - класс содержащий общие методы работы с модулем "Статистика". 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatistics/index.php
 * @author Bitrix
 */
class CAllStatistics extends CKeepStatistics
{
	public static function GetAdvGuestHost($ADV_ID, $GUEST_ID, $IP_NUMBER, $BACK="")
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$ADV_ID = intval($ADV_ID);
		$GID = intval($GUEST_ID);

		$strSql = "
			SELECT
				count(1) ADV_HOSTS,
				".$DB->DateToCharFunction("max(DATE_HOST_HIT)","SHORT")." MAX_DATE_HOST_HIT
			FROM	b_stat_adv_guest
			WHERE	ADV_ID=$ADV_ID and IP_NUMBER='".$DB->ForSQL($IP_NUMBER)."'
			$BACK
		";
		$rsResult=$DB->Query($strSql, false, $err_mess.__LINE__);
		if(!($arHost = $rsResult->Fetch()))
			$arHost = array("ADV_HOSTS"=>0,"MAX_DATE_HOST_HIT"=>false);

		$strSql = "
			SELECT
				count(1) ADV_GUESTS,
				".$DB->DateToCharFunction("max(DATE_GUEST_HIT)","SHORT")." MAX_DATE_GUEST_HIT
			FROM	b_stat_adv_guest
			WHERE	ADV_ID=$ADV_ID and GUEST_ID=$GID
			$BACK
		";
		$rsResult=$DB->Query($strSql, false, $err_mess.__LINE__);
		if(!($arGuest = $rsResult->Fetch()))
			$arGuest = array("ADV_GUESTS"=>0,"MAX_DATE_GUEST_HIT"=>false);

		$rsResult = new CDBResult;
		$rsResult->InitFromArray(array(array_merge($arGuest,$arHost)));
		return $rsResult;
	}

	public static function StartBuffer()
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		global $APPLICATION, $USER;
		if (defined("ADMIN_SECTION") && (ADMIN_SECTION === true))
			return;
		if(!(($USER->IsAuthorized() || $APPLICATION->ShowPanel===true) && $APPLICATION->ShowPanel!==false))
			return;
		//if($APPLICATION->GetPublicShowMode() !== "view")
		//	return;

		if ($_GET["show_link_stat"]=="Y") $_SESSION["SHOW_LINK_STAT"] = "Y";
		elseif ($_GET["show_link_stat"]=="N") $_SESSION["SHOW_LINK_STAT"] = "N";

		$arButtons = array();
		$STAT_RIGHT = $APPLICATION->GetGroupRight("statistic");
		if ($STAT_RIGHT>="R")
		{
			$width = 650;
			$height = 650;
			$CURRENT_PAGE = __GetFullRequestUri(__GetFullCurPage());

			$arButtons[] = array(
				"TEXT" => GetMessage("STAT_PAGE_GRAPH_PANEL_BUTTON"),
				"TITLE" => GetMessage("STAT_PAGE_GRAPH_PANEL_BUTTON"),
				"IMAGE" => "/bitrix/images/statistic/page_traffic.gif",
				"ACTION" => "javascript:window.open('/bitrix/admin/section_graph_list.php?lang=". LANGUAGE_ID."&public=Y&width=".$width."&height=".$height."&section=".urlencode($CURRENT_PAGE)."&set_default=Y','','target=_blank,scrollbars=yes,resizable=yes,width=".$width. ",height=".$height.",left='+Math.floor((screen.width - ".$width.")/2)+',top='+Math.floor((screen.height- ".$height.")/2))",
			);

			if ($_SESSION["SHOW_LINK_STAT"]=="Y")
			{
				$add = "show_link_stat=N";
				$alt = GetMessage("STAT_LINK_STAT_HIDE_PANEL_BUTTON");

				$arButtons[] = array(
					"TEXT" => GetMessage("STAT_LINK_STAT_PANEL_BUTTON"),
					"TITLE" => GetMessage("STAT_LINK_STAT_PANEL_BUTTON"),
					"IMAGE" => "/bitrix/images/statistic/link_stat_panel.gif",
					"ACTION" => "javascript:ShowStatLinkPage()",
				);

				ob_start();
				// define("BX_STATISTIC_BUFFER_USED", true);

			}
			else
			{
				$add = "show_link_stat=Y";
				$alt = GetMessage("STAT_LINK_STAT_SHOW_PANEL_BUTTON");
			}

			$arButtons[] = array(
				"TEXT" => $alt,
				"TITLE" => $alt,
				"IMAGE" => "/bitrix/images/statistic/link_stat_show.gif",
				"ACTION" => "jsUtils.Redirect([], '".CUtil::JSEscape($APPLICATION->GetCurPageParam($add, array("show_link_stat")))."')",
			);
		}
		if(count($arButtons) > 0)
		{
			$APPLICATION->AddPanelButton(array(
				"ICON" => "bx-panel-statistics-icon",
				"ALT" => GetMessage("STAT_PANEL_BUTTON"),
				"TEXT" => GetMessage("STAT_PANEL_BUTTON"),
				"MAIN_SORT" => 1000,
				"MENU" => $arButtons,
				"MODE" => "view",
				"HINT" => array(
					"TITLE" => GetMessage("STAT_PANEL_BUTTON"),
					"TEXT" => GetMessage("STAT_PANEL_BUTTON_HINT"),
				)
			));
		}
	}

	public static function EndBuffer()
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		global $APPLICATION, $arHashLink;
		$DB = CDatabase::GetModuleConnection('statistic');
		if (defined("ADMIN_SECTION") && ADMIN_SECTION===true) return;
		if (defined("BX_STATISTIC_BUFFER_USED") && BX_STATISTIC_BUFFER_USED===true)
		{
			$content = ob_get_contents();
			ob_end_clean();

			// this JS will open new windows with statistics data
			ob_start();
			?>
			<script language="JavaScript">
			function ShowStatLinkPage()
			{
				try
				{
					ShowStatLinkPageEx();
				}
				catch (e)
				{
					alert('<?echo GetMessage("STAT_LINK_STAT_PANEL_BUTTON_ALERT")?>');
				}
			}
			</script>
			<?
			$content .= ob_get_contents();
			ob_end_clean();

			$arUniqLink = array();
			$arHashLink = array();

			// parse the content in order to get links
			if(preg_match_all("#<a[^>]+?href\\s*=\\s*([\"'])(.*?)\\1#is", $content, $arr))
			{
				foreach($arr[2] as $link)
				{
					if (!__IsHiddenLink($link))
					{
						// relative URL found
						$link = __GetFullRequestUri(__GetFullCurPage($link));
						if (strpos($link, $_SERVER["HTTP_HOST"])!==false)
						{
							$arUniqLink[crc32ex($link)] = $link;
						}
					}
				}
			}

			// we found some links
			if (count($arUniqLink)>0)
			{
				// read database to get their data
				$SUM = 0;
				$MAX = false;
				$CURRENT_PAGE = __GetFullRequestUri(__GetFullCurPage());
				$CURRENT_PAGE_CRC32 = crc32ex($CURRENT_PAGE);
				foreach($arUniqLink as $link_crc => $link)
				{
					if ($CURRENT_PAGE != $link)
					{
						$strSql = "
							SELECT
								LAST_PAGE_HASH,
								sum(COUNTER) CNT
							FROM
								b_stat_path
							WHERE
								PREV_PAGE_HASH = '".$CURRENT_PAGE_CRC32."'
								and LAST_PAGE_HASH = '".$link_crc."'
							GROUP BY
								LAST_PAGE_HASH
						";
						$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
						$ar = $rs->Fetch();
						$CNT = intval($ar["CNT"]);
						if($CNT > 0)
						{
							$arHashLink[$link_crc] = array(
								"LINK"	=> $link,
								"CNT"	=> $CNT,
							);
							$SUM += $CNT;
							if($MAX === false || ($CNT > $MAX))
								$MAX = $CNT;
						}
					}
				}

				// если имеем массив количеств переходов по ссылкам то
				if((count($arHashLink) > 0) && ($SUM > 0))
				{
					// отсортируем ссылки в порядке убывания количества переходов и
					// 1) присвоим каждой ссылке порядковый номер
					// 2) посчитаем процент переходов по каждой ссылке
					uasort($arHashLink, "__SortLinkStat");
					$i=0;
					foreach($arHashLink as $link_crc => $arLink)
					{
						$i++;
						$arHashLink[$link_crc]["ID"] = $i;
						$arHashLink[$link_crc]["PERCENT"] = round((100*$arLink["CNT"])/$SUM, 1);
					}

					// парсим контент и добавляем к тэгам <a> желтую табличку с процентом переходов
					$pcre_backtrack_limit = intval(ini_get("pcre.backtrack_limit"));
					$content_len = function_exists('mb_strlen')? mb_strlen($content, 'latin1'): strlen($content);
					$content_len++;
					if($pcre_backtrack_limit < $content_len)
						@ini_set("pcre.backtrack_limit", $content_len);

					$content = preg_replace_callback("#(<a[^>]+?href\\s*=\\s*)([\"'])(.*?)(\\2.*?>)(.*?)(</.+?>)#is", "__ModifyATags", $content);

					// сформируем диаграмму переходов для данной страницы
					ob_start();
					?>
					<style>
					div.stat_pages h2 { background-color:#EEEEEE; font-family:Verdana,Arial,sans-serif; font-size:82%; padding:4px 10px; }
					div.stat_pages p { font-family:Verdana,Arial,sans-serif; font-size:82%; }
					div.stat_pages td { font-family:Verdana,Arial,sans-serif; font-size:70%;  border: 1px solid #BDC6E0; padding:3px; background-color: white; }
					div.stat_pages table { border-collapse:collapse; }
					div.stat_pages td.head { background-color:#E6E9F4; }
					div.stat_pages td.tail { background-color:#EAEDF7; }
					</style>
					<div class="stat_pages">
					<h2><?=GetMessage("STAT_LINK_STAT")?></h2>
					<p><?=$CURRENT_PAGE?></p>
					<table border="0" cellspacing="0" cellpadding="0" width="100%">
						<tr>
							<td class="head" align="center">#</td>
							<td class="head"><?=GetMessage("STAT_LINK")?></td>
							<td colspan="2" class="head"><?=GetMessage("STAT_CLICKS")?></td>
							<td class="head">&nbsp;</td>
						</tr>
						<?
						$max_relation = ($MAX*100)/90;
						foreach($arHashLink as $ar):
							$w = round(($ar["CNT"]*100)/$max_relation);
						?>
						<tr>
							<td valign="top" align="right" width="0%" nowrap><?=$ar["ID"]?>.</td>
							<td valign="top" width="50%"><?=InsertSpaces($ar["LINK"], 60, "<wbr>")?></td>
							<td valign="top" align="right" width="5%" nowrap><?=$ar["PERCENT"]."%"?></td>
							<td valign="top" align="right" width="5%" nowrap><?=$ar["CNT"]?></td>
							<td valign="top" nowrap width="40%"><img src="/bitrix/images/statistic/votebar.gif" width="<?echo ($w==0) ? "0" : $w."%"?>" height="10" border=0 alt=""></td>
						</tr>
						<?endforeach?>
						<tr>
							<td width="0%" colspan="3" nowrap align="right" class="tail"><?echo GetMessage("STAT_TOTAL")?></td>
							<td width="0%" nowrap align="right" class="tail"><?=$SUM?></td>
							<td width="100%" class="tail">&nbsp;</td>
						</tr>
					</table>
					<p><form><input type="button" onClick="window.close()" value="<?echo GetMessage("STAT_CLOSE")?>"></form></p>
					</div>
					<?
					$stat_table = trim(ob_get_contents());
					$js_table = "wnd.document.write('".CUtil::JSEscape($stat_table)."');";
					ob_end_clean();

					// сформируем JS открывающий отдельное окно со статистикой переходов
					ob_start();
					?>
					<script language="JavaScript">
					function ShowStatLinkPageEx()
					{
						var top=0, left=0;
						var width=800, height=600;
						if(width > screen.width-10 || height > screen.height-28) scroll = "yes";
						if(height < screen.height-28) top = Math.floor((screen.height - height)/2-14);
						if(width < screen.width-10) left = Math.floor((screen.width - width)/2-5);
						width = Math.min(width, screen.width-10);
						height = Math.min(height, screen.height-28);
						var wnd = window.open("","","scrollbars=yes,resizable=yes,width="+width+",height="+height+",left="+left+",top="+top);
						wnd.document.write("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n");
						wnd.document.write("<html><head>\n");
						wnd.document.write("<meta http-equiv=\"Content-Type\" content=\"text/html; charset=<?echo LANG_CHARSET?>\">\n");
						wnd.document.write("<"+"script language=\'JavaScript\'>\n");
						wnd.document.write("<!--\n");
						wnd.document.write("function KeyPress()\n");
						wnd.document.write("{\n");
						wnd.document.write("	if(window.event.keyCode == 27)\n");
						wnd.document.write("		window.close();\n");
						wnd.document.write("}\n");
						wnd.document.write("//-->\n");
						wnd.document.write("</"+"script>\n");
						wnd.document.write("<title><?=GetMessage("STAT_LINK_STAT_TITLE")?></title></head>\n");
						wnd.document.write("<body style=\"padding:10px;\" topmargin=\"0\" leftmargin=\"0\" marginwidth=\"0\" marginheight=\"0\" onKeyPress=\"KeyPress()\">\n");
						<?=$js_table?>
						wnd.document.write("</body>");
						wnd.document.write("</html>");
						wnd.document.close();
					}
					</script>
					<?
					$js = ob_get_contents();
					ob_end_clean();

				}
			}
			echo $content.$js;
		}

	}

public static 	function DBDateCompare($FIELD_NAME, $DATE=false, $DATE_FORMAT="SHORT")
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		if($DATE === false)
		{
			$date = $DB->CurrentDateFunction();
		}
		elseif(($DATE_FORMAT == "SHORT") && (strtolower($DB->type) == "mysql"))
		{
			$date = "cast(".$DB->CharToDateFunction($DATE, $DATE_FORMAT)." as date)";
		}
		else
		{
			$date = $DB->CharToDateFunction($DATE, $DATE_FORMAT);
		}
		return " $FIELD_NAME = $date ";
	}

public static 	function CleanUpStatistics_1()
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			CStatistics::CleanUpVisits();
			CStatistics::CleanUpEvents();
			CStatistics::CleanUpEventDynamic();
			CStatistics::CleanUpSearcherHits();
			CStatistics::CleanUpSearcherDynamic();
			CStatistics::CleanUpAdvGuests();
			CStatistics::CleanUpAdvDynamic();
			CStatistics::CleanUpPhrases();
			CStatistics::CleanUpRefererList();
			CStatistics::CleanUpReferer();
			CStatistics::CleanUpCountries();
			CStatistics::CleanUpCities();
			CStatistics::CleanUpPathDynamic();
			CStatistics::CleanUpPathCache();
			CStatistics::CleanUpGuests();
		}
		return "CStatistics::CleanUpStatistics_1();";
	}

public static 	function CleanUpStatistics_2()
	{
		__SetNoKeepStatistics();
		if ($_SESSION["SESS_NO_AGENT_STATISTIC"]!="Y" && !defined("NO_AGENT_STATISTIC"))
		{
			CStatistics::CleanUpSessions();
			CStatistics::CleanUpHits();
		}
		return "CStatistics::CleanUpStatistics_2();";
	}

	///////////////////////////////////////////////////////////////////
	// This is deprecated and unused method to handle internal search
	///////////////////////////////////////////////////////////////////
public static 	function OnSearch($search_phrase)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		if(intval($_SESSION["SESS_SEARCHER_ID"]) > 0)
			return;
		if(COption::GetOptionString("statistic", "SAVE_REFERERS") != "N")
		{
			if(!array_key_exists("SESS_PHRASE_ID", $_SESSION))
				$_SESSION["SESS_PHRASE_ID"] = array();
			$search_phrase = substr(trim($search_phrase), 0, 255);
			if(strlen($search_phrase))
			{
				// check if search of this phrase already occured in this session
				if(array_key_exists($search_phrase, $_SESSION["SESS_PHRASE_ID"]))
				{
					// return it's ID
					return "phrase_id=".$_SESSION["SESS_PHRASE_ID"][$search_phrase];
				}
				else
				{
					if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
						$sql_site = "'ad'";
					elseif(defined("SITE_ID"))
						$sql_site = "'".$DB->ForSql(SITE_ID, 2)."'";
					else
						$sql_site = "null";

					// otherwise add it
					$arFields = Array(
						"DATE_HIT" => $DB->GetNowFunction(),
						"SEARCHER_ID" => '1',
						"PHRASE" => "'".$DB->ForSql($search_phrase)."'",
						"SESSION_ID" => intval($_SESSION["SESS_SESSION_ID"]),
						"SITE_ID" => $sql_site,
					);
					$_SESSION["SESS_PHRASE_ID"][$search_phrase] = $phrase_id = $DB->Insert("b_stat_phrase_list", $arFields, "File: ".__FILE__."<br>Line: ".__LINE__);
					// let's use lru to control session data volume
					while(count($_SESSION["SESS_PHRASE_ID"]) > 10)
						array_shift($_SESSION["SESS_PHRASE_ID"]);

					stat_session_register("SESS_FROM_SEARCHERS");

					// update search engine statistic
					$arFields = Array(
						"PHRASES" => "PHRASES + 1",
					);
					$rows = $DB->Update("b_stat_searcher", $arFields, "WHERE ID=1", "File: ".__FILE__."<br>Line: ".__LINE__);
					$_SESSION["SESS_FROM_SEARCHERS"][] = 1;

					return "phrase_id=".$phrase_id;
				}
			}
		}
	}

	///////////////////////////////////////////////////////////////////
	// Обновляем счетчик по рекламной кампании
	///////////////////////////////////////////////////////////////////
public static 	function Update_Adv()
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";

		$DB = CDatabase::GetModuleConnection('statistic');

		$REMOTE_ADDR_NUMBER = ip2number($_SERVER["REMOTE_ADDR"]);

		// если это прямой вход по рекламной кампании
		if (intval($_SESSION["SESS_ADV_ID"])>0)
		{
			// проверяем был ли уже прямой заход либо возврат у данного посетителя и с данного хоста
			$t = CStatistics::GetAdvGuestHost(
				$_SESSION["SESS_ADV_ID"],
				$_SESSION["SESS_GUEST_ID"],
				$REMOTE_ADDR_NUMBER);
			$guest_counter = 0;
			$host_counter = 0;
			while ($tr = $t->Fetch())
			{
				if ($guest_counter!=1) $guest_counter = (intval($tr["ADV_GUESTS"])>0) ? 0 : 1;
				if ($host_counter!=1) $host_counter = (intval($tr["ADV_HOSTS"])>0) ? 0 : 1;

				// дата прямого захода посетителя
				$MAX_DATE_GUEST_HIT = $tr["MAX_DATE_GUEST_HIT"];

				// дата прямого захода с хоста
				$MAX_DATE_HOST_HIT = $tr["MAX_DATE_HOST_HIT"];
			}

			// если посетитель новый, то нужно увеличить счетчик новых посетителей
			$new_guest_counter = ($_SESSION["SESS_GUEST_NEW"] == "Y") ? 1 : 0;

			$arFields = Array(
				"GUESTS"		=> "GUESTS + ".$guest_counter,
				"NEW_GUESTS"	=> "NEW_GUESTS + ".$new_guest_counter,
				"C_HOSTS"		=> "C_HOSTS + ".$host_counter,
				"SESSIONS"		=> "SESSIONS + 1"
				);
			$arFields_temp = $arFields;

			// обновляем основной обсчет рекламной кампании
			$arFields["DATE_LAST"] = $DB->GetNowFunction();

			$DB->Update("b_stat_adv", $arFields, "WHERE ID=".intval($_SESSION["SESS_ADV_ID"]), $err_mess.__LINE__,false,false,false);

			$DB->Update("b_stat_adv", array("DATE_FIRST"=>$DB->GetNowFunction()), "WHERE ID=".intval($_SESSION["SESS_ADV_ID"])." and DATE_FIRST is null", $err_mess.__LINE__,false,false,false);

			$arFields = $arFields_temp;

			// определяем возвращался ли уже сегодня данный посетитель и с данного хоста
			$now_date = GetTime(time());
			$guest_day_counter = ($MAX_DATE_GUEST_HIT!=$now_date) ? 1 : 0;
			$host_day_counter = ($MAX_DATE_HOST_HIT!=$now_date) ? 1 : 0;

			$arFields["GUESTS_DAY"] = "GUESTS_DAY + ".$guest_day_counter;
			$arFields["C_HOSTS_DAY"] = "C_HOSTS_DAY + ".$host_day_counter;

			// обновляем обсчет рекламной кампании по дням
			$rows = $DB->Update("b_stat_adv_day", $arFields, "WHERE ADV_ID=".intval($_SESSION["SESS_ADV_ID"])." and  ".CStatistics::DBDateCompare("DATE_STAT"), $err_mess.__LINE__,false,false,false);
			// если обсчета по дням нет то
			if (intval($rows)<=0)
			{
				// добавляем его
				$arFields_i = Array(
					"ADV_ID" => intval($_SESSION["SESS_ADV_ID"]),
					"DATE_STAT" => $DB->GetNowDate(),
					"GUESTS" => $guest_counter,
					"GUESTS_DAY" => 1,
					"NEW_GUESTS" => $new_guest_counter,
					"C_HOSTS" => $host_counter,
					"C_HOSTS_DAY" => 1,
					"SESSIONS" => 1
					);
				$DB->Insert("b_stat_adv_day",$arFields_i, $err_mess.__LINE__, $DEBUG);
			}
			elseif ($rows>1) // если обновили более одного дня то
			{
				// удалим лишние
				$i=0;
				$strSql = "SELECT ID FROM b_stat_adv_day WHERE ADV_ID=".intval($_SESSION["SESS_ADV_ID"])." and  ".CStatistics::DBDateCompare("DATE_STAT")." ORDER BY ID";
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($ar = $rs->Fetch())
				{
					$i++;
					if ($i>1)
					{
						$strSql = "DELETE FROM b_stat_adv_day WHERE ID = ".$ar["ID"];
						$DB->Query($strSql, false, $err_mess.__LINE__);
					}
				}
			}

			// если данный гость, либо с данного хоста еще не заходили по данной рекламной кампании то
			if (intval($guest_counter)==1 || intval($host_counter)==1)
			{
				// добавляем их в базу
				$arFields = Array(
					"ADV_ID" => intval($_SESSION["SESS_ADV_ID"]),
					"GUEST_ID" => intval($_SESSION["SESS_GUEST_ID"]),
					"DATE_GUEST_HIT" => $DB->GetNowFunction(),
					"DATE_HOST_HIT" => $DB->GetNowFunction(),
					"SESSION_ID" => intval($_SESSION["SESS_SESSION_ID"]),
					"IP_NUMBER" => "'".$DB->ForSql($REMOTE_ADDR_NUMBER)."'",
					"IP" => "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
					"BACK" => "'N'"
					);
				$DB->Insert("b_stat_adv_guest",$arFields, $err_mess.__LINE__, $DEBUG);
			}
			else // иначе
			{
				// обновляем дату прямого захода посетителя
				$arFields = Array("DATE_GUEST_HIT" => $DB->GetNowFunction());
				$DB->Update("b_stat_adv_guest", $arFields, "WHERE ADV_ID=".intval($_SESSION["SESS_ADV_ID"])." and GUEST_ID=".intval($_SESSION["SESS_GUEST_ID"])." and BACK='N'", $err_mess.__LINE__, $DEBUG);

				// обновляем дату прямого захода с хоста
				$arFields = Array("DATE_HOST_HIT" => $DB->GetNowFunction());
				$DB->Update("b_stat_adv_guest", $arFields, "WHERE ADV_ID=".intval($_SESSION["SESS_ADV_ID"])." and IP_NUMBER='".$DB->ForSql($REMOTE_ADDR_NUMBER)."' and BACK='N'", $err_mess.__LINE__, $DEBUG);
			}
			// записываем прямую рекламную кампанию в cookie
			$GLOBALS["APPLICATION"]->set_cookie("LAST_ADV", $_SESSION["SESS_ADV_ID"]."_Y");
		}
		// если это возврат по рекламной кампании
		elseif (intval($_SESSION["SESS_LAST_ADV_ID"])>0)
		{
			// проверяем был ли уже возврат у данного посетителя, либо с данного хоста
			$t = CStatistics::GetAdvGuestHost(
				$_SESSION["SESS_LAST_ADV_ID"],
				$_SESSION["SESS_GUEST_ID"],
				$REMOTE_ADDR_NUMBER,
				"and BACK='Y'");
			$guest_back_counter = 0;
			$host_back_counter = 0;
			while ($tr = $t->Fetch())
			{
				// счетчик для уникальных вернувшихся посетителей
				if ($guest_back_counter!=1) $guest_back_counter = (intval($tr["ADV_GUESTS"])>0) ? 0 : 1;

				// счетчик для уникальных вернувшихся хостов
				if ($host_back_counter!=1) $host_back_counter = (intval($tr["ADV_HOSTS"])>0) ? 0 : 1;

				// дата последнего возврата посетителя
				$MAX_DATE_GUEST_HIT = $tr["MAX_DATE_GUEST_HIT"];

				// дата последнего возврата с хоста
				$MAX_DATE_HOST_HIT = $tr["MAX_DATE_HOST_HIT"];
			}

			// обновляем обсчет рекламной кампании
			$arFields = Array(
				"GUESTS_BACK"	=> "GUESTS_BACK + ".$guest_back_counter,
				"HOSTS_BACK"	=> "HOSTS_BACK + ".$host_back_counter,
				"SESSIONS_BACK"	=> "SESSIONS_BACK + 1"
			);
			// если происходит восстановление профайла посетителя то
			if($_SESSION["SESS_LAST_ADV_ID"] > 0)
			{
				// оставляем значение счетчиков посетителей и хостов без изменений
				$arFields["GUESTS_BACK"] = "GUESTS_BACK";
				$arFields["HOSTS_BACK"] = "HOSTS_BACK";
				$guest_back_counter = 0;
				$host_back_counter = 0;
			}
			$DB->Update("b_stat_adv", $arFields, "WHERE ID=".intval($_SESSION["SESS_LAST_ADV_ID"]), $err_mess.__LINE__,false,false,false);

			// определяем возвращался ли уже сегодня данный посетитель и с данного хоста
			$now_date = GetTime(time());
			$guest_day_back_counter = ($MAX_DATE_GUEST_HIT!=$now_date) ? 1 : 0;
			$host_day_back_counter = ($MAX_DATE_HOST_HIT!=$now_date) ? 1 : 0;

			$arFields["GUESTS_DAY_BACK"] = "GUESTS_DAY_BACK + ".$guest_day_back_counter;
			$arFields["HOSTS_DAY_BACK"] = "HOSTS_DAY_BACK + ".$host_day_back_counter;

			// обновляем обсчет рекламной кампании по дням
			$rows = $DB->Update("b_stat_adv_day", $arFields, "WHERE ADV_ID=".intval($_SESSION["SESS_LAST_ADV_ID"])." and  ".CStatistics::DBDateCompare("DATE_STAT"), $err_mess.__LINE__,false,false,false);
			// если обсчета по дням нет то
			if (intval($rows)<=0)
			{
				// добавляем его
				$arFields = Array(
					"ADV_ID" => intval($_SESSION["SESS_LAST_ADV_ID"]),
					"DATE_STAT" => $DB->GetNowDate(),
					"GUESTS_BACK" => $guest_back_counter,
					"GUESTS_DAY_BACK" => 1,
					"HOSTS_BACK" => $host_back_counter,
					"HOSTS_DAY_BACK" => 1,
					"SESSIONS_BACK" => 1
					);
				$DB->Insert("b_stat_adv_day", $arFields, $err_mess.__LINE__, $DEBUG);
			}
			elseif ($rows>1) // если обновили более одного дня то
			{
				// удалим лишние
				$i=0;
				$strSql = "SELECT ID FROM b_stat_adv_day WHERE ADV_ID=".intval($_SESSION["SESS_LAST_ADV_ID"])." and  ".CStatistics::DBDateCompare("DATE_STAT")." ORDER BY ID";
				$rs = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($ar = $rs->Fetch())
				{
					$i++;
					if ($i>1)
					{
						$strSql = "DELETE FROM b_stat_adv_day WHERE ID = ".$ar["ID"];
						$DB->Query($strSql, false, $err_mess.__LINE__);
					}
				}
			}

			// если данный гость либо с данного хоста
			// еще не возвращались по данной рекламной кампании то
			if (intval($guest_back_counter)==1 || intval($host_back_counter)==1)
			{
				// добавляем их в базу
				$arFields = Array(
					"ADV_ID" => intval($_SESSION["SESS_LAST_ADV_ID"]),
					"GUEST_ID" => intval($_SESSION["SESS_GUEST_ID"]),
					"DATE_GUEST_HIT" => $DB->GetNowFunction(),
					"DATE_HOST_HIT" => $DB->GetNowFunction(),
					"SESSION_ID" => intval($_SESSION["SESS_SESSION_ID"]),
					"IP_NUMBER" => "'".$DB->ForSql($REMOTE_ADDR_NUMBER)."'",
					"IP" => "'".$DB->ForSql($_SERVER["REMOTE_ADDR"],15)."'",
					"BACK" => "'Y'"
					);
				$DB->Insert("b_stat_adv_guest",$arFields, $err_mess.__LINE__, $DEBUG);
			}
			else // иначе
			{
				// обновляем дату последнего возврата посетителя
				$arFields = Array("DATE_GUEST_HIT" => $DB->GetNowFunction());
				$DB->Update("b_stat_adv_guest",$arFields,"WHERE ADV_ID=".intval($_SESSION["SESS_LAST_ADV_ID"])." and GUEST_ID=".intval($_SESSION["SESS_GUEST_ID"])." and BACK='Y'",$err_mess.__LINE__, $DEBUG);

				// обновляем дату последнего возврата с хоста
				$arFields = Array("DATE_HOST_HIT" => $DB->GetNowFunction());
				$DB->Update("b_stat_adv_guest",$arFields,"WHERE ADV_ID=".intval($_SESSION["SESS_LAST_ADV_ID"])." and IP_NUMBER='".$DB->ForSql($REMOTE_ADDR_NUMBER)."' and BACK='Y'",$err_mess.__LINE__, $DEBUG);
			}
			// записываем возврат по рекламной кампании в cookie
			$GLOBALS["APPLICATION"]->set_cookie("LAST_ADV", $_SESSION["SESS_LAST_ADV_ID"]);
		}
	}

	///////////////////////////////////////////////////////////////////
	// Устанавливаем рекламную кампанию
	///////////////////////////////////////////////////////////////////
public static 	function Set_Adv()
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		stat_session_register("SESS_ADV_ID"); // ID рекламной кампании
		$DB = CDatabase::GetModuleConnection('statistic');

		// если это начало сессии
		if (intval($_SESSION["SESS_SESSION_ID"])<=0 && intval($_SESSION["SESS_ADV_ID"])<=0)
		{
			$arrADV = array(); // массив рекламных кампаний

			// проверяем страницу на которую пришел посетитель
			$page_to = __GetFullRequestUri();
			CAdv::SetByPage($page_to, $arrADV, $ref1, $ref2, "TO");

			// если посетитель пришел с ссылающегося сайта то
			if (__GetReferringSite($PROT, $SN, $SN_WithoutPort, $PAGE_FROM))
			{
				$site_name = $PROT.$SN;
				// проверяем поисковики
				$strSql = "
					SELECT
						A.REFERER1,
						A.REFERER2,
						S.ADV_ID
					FROM
						b_stat_adv A,
						b_stat_adv_searcher S,
						b_stat_searcher_params P
					WHERE
						S.ADV_ID = A.ID
					and P.SEARCHER_ID = S.SEARCHER_ID
					and upper('".$DB->ForSql(trim($site_name),2000)."')
					like ".$DB->Concat("'%'", "upper(P.DOMAIN)", "'%'")."
					";
				$w = $DB->Query($strSql, false, $err_mess.__LINE__);
				while ($wr=$w->Fetch())
				{
					$ref1 = $wr["REFERER1"];
					$ref2 = $wr["REFERER2"];
					$arrADV[] = intval($wr["ADV_ID"]);
				}

				// проверяем ссылающиеся страницы
				$site_name = $PROT.$SN.$PAGE_FROM;
				CAdv::SetByPage($site_name, $arrADV, $ref1, $ref2, "FROM");
			}

			// если гость пришел с referer1, либо referer2 то
			if (strlen($_SESSION["referer1"])>0 || strlen($_SESSION["referer2"])>0)
			{
				CAdv::SetByReferer(trim($_SESSION["referer1"]), trim($_SESSION["referer2"]), $arrADV, $ref1, $ref2);
			}
			//Handle Openstat if enabled
			if(COption::GetOptionString("statistic", "OPENSTAT_ACTIVE") === "Y" && strlen($_REQUEST["_openstat"])>0)
			{
				$openstat = $_REQUEST["_openstat"];
				if(strpos($openstat, ";")===false)
					$openstat = base64_decode($openstat);
				$openstat = explode(";", $openstat);
				CAdv::SetByReferer(
					trim(str_replace(
						array("#service-name#", "#campaign-id#", "#ad-id#", "#source-id#"),
						$openstat,
						COption::GetOptionString("statistic", "OPENSTAT_R1_TEMPLATE")
					)),
					trim(str_replace(
						array("#service-name#", "#campaign-id#", "#ad-id#", "#source-id#"),
						$openstat,
						COption::GetOptionString("statistic", "OPENSTAT_R2_TEMPLATE")
					)),
					$arrADV, $ref1, $ref2
				);
			}
			$arrADV = array_unique($arrADV);

			// если было выявлено более одной рекламной кампании подходящей под условия то
			if (count($arrADV)>1)
			{
				// выберем рекламную кампанию по наивысшему приоритету (либо по наивысшему ID)
				$str = implode(",",$arrADV);
				$strSql = "SELECT ID, REFERER1, REFERER2 FROM b_stat_adv WHERE ID in ($str) ORDER BY PRIORITY desc, ID desc";
				$z = $DB->Query($strSql, false, $err_mess.__LINE__);
				$zr = $z->Fetch();
				$_SESSION["SESS_ADV_ID"] = intval($zr["ID"]);
				$_SESSION["referer1"] = $zr["REFERER1"];
				$_SESSION["referer2"] = $zr["REFERER2"];
			}
			else
			{
				list(,$value) = each($arrADV);
				$_SESSION["SESS_ADV_ID"] = intval($value);
				$_SESSION["referer1"] = $ref1;
				$_SESSION["referer2"] = $ref2;
			}
		}
		if (intval($_SESSION["SESS_ADV_ID"])>0) $_SESSION["SESS_LAST_ADV_ID"] = $_SESSION["SESS_ADV_ID"];
		$_SESSION["SESS_LAST_ADV_ID"] = intval($_SESSION["SESS_LAST_ADV_ID"]);
	}

	///////////////////////////////////////////////////////////////////
	// Устанавливаем ID гостя
	///////////////////////////////////////////////////////////////////
public static 	function Set_Guest()
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		stat_session_register("SESS_GUEST_ID");			// ID гостя
		stat_session_register("SESS_GUEST_NEW");		// флаг "новый гость"
		stat_session_register("SESS_LAST_USER_ID");		// под кем гость был авторизован в последний раз
		stat_session_register("SESS_LAST_ADV_ID");		// по какой рекламной кампании был в последний раз
		stat_session_register("SESS_GUEST_FAVORITES");	// флаг добавлял ли гость сайт в фавориты
		stat_session_register("SESS_LAST");				// Y - гость сегодня уже заходил; N - еще не заходил

		global $USER, $APPLICATION;
		$DB = CDatabase::GetModuleConnection('statistic');
		$last_referer1 = "";
		$last_referer2 = "";

		if (defined("ADMIN_SECTION") && ADMIN_SECTION===true) $sql_site = "null";
		elseif (defined("SITE_ID")) $sql_site = "'".$DB->ForSql(SITE_ID,2)."'";
		else $sql_site = "null";

		$ERROR_404 = (defined("ERROR_404") && ERROR_404=="Y") ? "Y" : "N";
		$REPAIR_COOKIE_GUEST = "N";
		if (!isset($_SESSION["SESS_GUEST_NEW"])) $_SESSION["SESS_GUEST_NEW"] = "N";
		$_SESSION["SESS_GUEST_ID"] = intval($_SESSION["SESS_GUEST_ID"]);

		$COOKIE_GUEST_ID = intval($APPLICATION->get_cookie("GUEST_ID"));
		if($COOKIE_GUEST_ID==0) $COOKIE_GUEST_ID = intval($_SESSION["SESS_GUEST_ID"]);

		// если сессия только открылась
		if (intval($_SESSION["SESS_SESSION_ID"])<=0)
		{
			// выбираем из базы параметры гостя
			$q = CGuest::GetLastByID($COOKIE_GUEST_ID);
			// если ничего не выбрали то
			if (!($qr=$q->Fetch()))
			{
				// считаем гостя новым
				$_SESSION["SESS_GUEST_ID"] = 0;
				$_SESSION["SESS_GUEST_NEW"] = "Y";
				$_SESSION["SESS_GUEST_FAVORITES"] = "N";
				// если у него в cookie хранится GUEST_ID то
				if ($COOKIE_GUEST_ID>0)
				{
					$_SESSION["SESS_GUEST_NEW"] = "N";
					// получаем дату последнего посещения сайта данным гостем
					// если формат корректный то
					if ($LAST_VISIT = MkDateTime($GLOBALS["APPLICATION"]->get_cookie("LAST_VISIT"),"d.m.Y H:i:s"))
					{
						// получаем дату последней инсталляции таблиц модуля
						$DATE_INSTALL = COption::GetOptionString("main", "INSTALL_STATISTIC_TABLES", "NOT_FOUND");
						if ($DATE_INSTALL=="NOT_FOUND")
						{
							$DATE_INSTALL = date("d.m.Y H:i:s",time());
							COption::SetOptionString("main", "INSTALL_STATISTIC_TABLES", $DATE_INSTALL, "Installation date of Statistics module tables");
						}
						if ($DATE_INSTALL = MkDateTime($DATE_INSTALL,"d.m.Y H:i:s"))
						{
							// если таблицы были инсталлированы после последнего посещения сайта то
							if ($DATE_INSTALL>$LAST_VISIT)
							{
								// посетитель считается новым т.к. он нигде не был учтен
								$_SESSION["SESS_GUEST_NEW"] = "Y";
							}
						}
					}
					// устанавливаем флаг того что мы восстанавливаем гостя
					$REPAIR_COOKIE_GUEST = "Y";
					// получаем идентификатор его последней рекламной кампании
					$COOKIE_ADV = $GLOBALS["APPLICATION"]->get_cookie("LAST_ADV");
				}
			}
			else // иначе если выбрали параметры гостя то
			{
				// то запоминаем их в сессии
				$_SESSION["SESS_GUEST_FAVORITES"] = $qr["FAVORITES"];
				$_SESSION["SESS_GUEST_FAVORITES"] = ($_SESSION["SESS_GUEST_FAVORITES"]=="Y") ? "Y" : "N";
				if (!isset($_SESSION["SESS_GUEST_NEW"])) $_SESSION["SESS_GUEST_NEW"] = "N";
				$_SESSION["SESS_GUEST_ID"] = intval($qr["ID"]);
				$_SESSION["SESS_LAST_ADV_ID"]=intval($qr["LAST_ADV_ID"]);
				$_SESSION["SESS_LAST_USER_ID"] = intval($qr["LAST_USER_ID"]);
				$_SESSION["SESS_LAST"] = $qr["LAST"];
				if ($_SESSION["SESS_LAST_ADV_ID"]>0)
				{
					$strSql = "SELECT REFERER1, REFERER2 FROM b_stat_adv WHERE ID=".$_SESSION["SESS_LAST_ADV_ID"];
					$w = $DB->Query($strSql, false, $err_mess.__LINE__);
					if ($wr = $w->Fetch())
					{
						$last_referer1 = $wr["REFERER1"];
						$last_referer2 = $wr["REFERER2"];
					}
				}
			}

		}
		// если есть необходимость то
		if ($_SESSION["SESS_GUEST_ID"]<=0)
		{
			// вставляем гостя в базу
			$arFields = Array(
				"FIRST_DATE"		=> $DB->GetNowFunction(),
				"FIRST_URL_FROM"	=> "'".$DB->ForSql($_SERVER["HTTP_REFERER"],2000)."'",
				"FIRST_URL_TO"		=> "'".$DB->ForSql(__GetFullRequestUri(),2000)."'",
				"FIRST_URL_TO_404"	=> "'".$DB->ForSql($ERROR_404)."'",
				"FIRST_SITE_ID"		=> $sql_site,
				"FIRST_ADV_ID"		=> intval($_SESSION["SESS_ADV_ID"])	,
				"FIRST_REFERER1"	=> "'".$DB->ForSql($_SESSION["referer1"],255)."'",
				"FIRST_REFERER2"	=> "'".$DB->ForSql($_SESSION["referer2"],255)."'",
				"FIRST_REFERER3"	=> "'".$DB->ForSql($_SESSION["referer3"],255)."'"
				);
			// если мы восстанавливаем гостя по данным записаным в его cookie то
			if ($REPAIR_COOKIE_GUEST=="Y")
			{
				// если гость не считается новым то добавим ему одну сессию
				if ($_SESSION["SESS_GUEST_NEW"]=="N") $arFields["SESSIONS"] = 1;
				// если у него в cookie была рекламная кампания то
				$COOKIE_ADV = intval($COOKIE_ADV);
				if ($COOKIE_ADV>0)
				{
					// проверяем есть ли такая кампания в базе
					$strSql = "SELECT REFERER1, REFERER2 FROM b_stat_adv WHERE ID='".$COOKIE_ADV."'";
					$w = $DB->Query($strSql, false, $err_mess.__LINE__);
					// если в базе есть такая рекламная кампания то
					if ($wr = $w->Fetch())
					{
						// считаем что гость вернулся по данной рекламной кампании
						$_SESSION["SESS_LAST_ADV_ID"] = $COOKIE_ADV;
						// если последний вход записанный в cookie
						// не был прямым входом по рекламной кампании то
						$arFields["FIRST_ADV_ID"] = $COOKIE_ADV;
						$arFields["FIRST_REFERER1"]	= "'".$DB->ForSql($wr["REFERER1"],255)."'";
						$arFields["FIRST_REFERER2"]	= "'".$DB->ForSql($wr["REFERER2"],255)."'";
						$arFields["LAST_ADV_ID"] = $COOKIE_ADV;
						$arFields["LAST_ADV_BACK"] = "'Y'";
						$arFields["LAST_REFERER1"] = "'".$DB->ForSql($wr["REFERER1"],255)."'";
						$arFields["LAST_REFERER2"] = "'".$DB->ForSql($wr["REFERER2"],255)."'";
						$last_referer1 = $wr["REFERER1"];
						$last_referer2 = $wr["REFERER2"];
					}
				}
			}
			$_SESSION["SESS_GUEST_ID"] = $DB->Insert("b_stat_guest",$arFields, $err_mess.__LINE__);
			if ($ERROR_404=="N")
			{
				CStatistics::Set404("b_stat_guest", "ID = ".intval($_SESSION["SESS_GUEST_ID"]), array("FIRST_URL_TO_404" => "Y"));
			}
		}

		// если гость авторизовался то
		if (is_object($USER) && intval($USER->GetID())>0)
		{
			// запоминаем кто он
			$_SESSION["SESS_LAST_USER_ID"] = intval($USER->GetID());
		}
		if (intval($_SESSION["SESS_LAST_USER_ID"])<=0) $_SESSION["SESS_LAST_USER_ID"] = "";


		if ($_SESSION["SESS_GUEST_ID"]>0)
		{
			// сохраним ID посетителя в куках
			$GLOBALS["APPLICATION"]->set_cookie("GUEST_ID", $_SESSION["SESS_GUEST_ID"]);
		}
		// сохраним в cookie дату последнего посещения данным гостем сайта
		$GLOBALS["APPLICATION"]->set_cookie("LAST_VISIT", date("d.m.Y H:i:s",time()));

		return array(
			"last_referer1" => $last_referer1,
			"last_referer2" => $last_referer2,
		);
	}

	///////////////////////////////////////////////////////////////////
	//	функция блокировки посетителя по превышению лимита активности,
	//	возвращает true если посетителя пора блокировать
	///////////////////////////////////////////////////////////////////
	public static function BlockVisitorActivity()
	{
		global $USER;
		if(is_object($USER) && $USER->IsAdmin())
			return false;
		if(defined("STATISTIC_SKIP_ACTIVITY_CHECK"))
			return false;
		if(COption::GetOptionString("statistic", "DEFENCE_ON")=="Y")
		{
			$_SESSION["SESS_SEARCHER_CHECK_ACTIVITY"] = ($_SESSION["SESS_SEARCHER_CHECK_ACTIVITY"]=="N") ? "N" : "Y";
			// если это не поисковик или поисковик, но с установленным флагом "проверять лимит активности"
			if (
				intval($_SESSION["SESS_SEARCHER_ID"]) <= 0
				|| $_SESSION["SESS_SEARCHER_CHECK_ACTIVITY"] == "Y"
			)
			{
				// если установлен максимальный интервал времени для стэка защиты то
				$DEFENCE_DELAY = intval(COption::GetOptionString("statistic", "DEFENCE_DELAY"));
				$STACK_TIME = COption::GetOptionString("statistic", "DEFENCE_STACK_TIME");
				$MAX_STACK_HITS = COption::GetOptionString("statistic", "DEFENCE_MAX_STACK_HITS");
				if (intval($STACK_TIME)>0)
				{
					// если лимит активности уже превышался то
					if (strlen($_SESSION["SESS_GRABBER_STOP_TIME"])>0)
					{
						// если время задержки еще не истекло то
						if ((time()-$_SESSION["SESS_GRABBER_STOP_TIME"])<=$DEFENCE_DELAY)
						{
							// держим дальше
							$_SESSION["SESS_GRABBER_DEFENCE_STACK"] = array();
							return true;
						}
						else // иначе
						{
							// обнуляем время блокирования
							$_SESSION["SESS_GRABBER_STOP_TIME"] = "";
						}
					}
					// запомним время текущего хита в стэке
					$_SESSION["SESS_GRABBER_DEFENCE_STACK"][] = time();
					// почистим стэк до заданного максимального интервала времени
					$first_element = reset($_SESSION["SESS_GRABBER_DEFENCE_STACK"]);
					$stmp = time();
					$current_stack_length = $stmp-$first_element;
					while(is_array($_SESSION["SESS_GRABBER_DEFENCE_STACK"]) && $current_stack_length>$STACK_TIME && count($_SESSION["SESS_GRABBER_DEFENCE_STACK"])>0)
					{
						$first_element = array_shift($_SESSION["SESS_GRABBER_DEFENCE_STACK"]);
						$current_stack_length = $stmp-$first_element;
					}
					$STACK_HITS = count($_SESSION["SESS_GRABBER_DEFENCE_STACK"]);
					// проверим стэк на превышение максимального кол-ва хитов
					if (intval($STACK_HITS)>$MAX_STACK_HITS)
					{
						// инициализируем превышение активности
						$stmp = time();
						$_SESSION["SESS_GRABBER_STOP_TIME"] = $stmp;

						if(COption::GetOptionString("statistic", "DEFENCE_LOG") === "Y")
							CEventLog::Log("WARNING", "STAT_ACTIVITY_LIMIT", "statistic", "", GetMessage("STAT_DEFENCE_LOG_MESSAGE", array(
								"#ACTIVITY_TIME_LIMIT#" => intval($STACK_TIME),
								"#ACTIVITY_HITS#" => intval($STACK_HITS),
								"#ACTIVITY_EXCEEDING#" => (intval($STACK_HITS) - intval($MAX_STACK_HITS)),
							)));

						// если в этой сессии письмо еще не отсылали то
						if ($_SESSION["ACTIVITY_EXCEEDING_NOTIFIED"]!="Y")
						{
							if (intval($_SESSION["SESS_SESSION_ID"])>0)
								$SESSION_LINK = "/bitrix/admin/session_list.php?lang=". $arSite["LANGUAGE_ID"]."&find_id=".$_SESSION["SESS_SESSION_ID"]."&find_id_exact_match=Y&set_filter=Y";

							if (intval($_SESSION["SESS_GUEST_ID"])>0)
								$VISITOR_LINK = "/bitrix/admin/guest_list.php?lang=". $arSite["LANGUAGE_ID"]."&find_id=".$_SESSION["SESS_GUEST_ID"]."&find_id_exact_match=Y&set_filter=Y";

							$arr = explode(".",$_SERVER["REMOTE_ADDR"]);
							$STOPLIST_LINK = "/bitrix/admin/stoplist_edit.php?lang=". $arSite["LANGUAGE_ID"]."&net1=".intval($arr[0])."&net2=".intval($arr[1])."&net3=". intval($arr[2])."&net4=".intval($arr[3])."&user_agent=".urlencode($_SERVER["HTTP_USER_AGENT"]);

							if (intval($_SESSION["SESS_SEARCHER_ID"])>0)
								$SEARCHER_LINK = "/bitrix/admin/hit_searcher_list.php?lang=". $arSite["LANGUAGE_ID"]."&find_searcher_id=".$_SESSION["SESS_SEARCHER_ID"]."&set_filter=Y";

							$arEventFields = array(
								"ACTIVITY_TIME_LIMIT"	=> intval($STACK_TIME),
								"ACTIVITY_HITS"			=> intval($STACK_HITS),
								"ACTIVITY_HITS_LIMIT"	=> intval($MAX_STACK_HITS),
								"ACTIVITY_EXCEEDING"	=> (intval($STACK_HITS) - intval($MAX_STACK_HITS)),
								"CURRENT_TIME"			=> GetTime($stmp,"FULL",$arSite["ID"]),
								"DELAY_TIME"			=> $DEFENCE_DELAY,
								"USER_AGENT"			=> $_SERVER["HTTP_USER_AGENT"],
								"SESSION_ID"			=> $_SESSION["SESS_SESSION_ID"],
								"SESSION_LINK"			=> $SESSION_LINK,
								"SERACHER_ID"			=> $_SESSION["SESS_SEARCHER_ID"],
								"SEARCHER_NAME"			=> $_SESSION["SESS_SEARCHER_NAME"],
								"SEARCHER_LINK"			=> $SEARCHER_LINK,
								"VISITOR_ID"			=> $_SESSION["SESS_GUEST_ID"],
								"VISITOR_LINK"			=> $VISITOR_LINK,
								"STOPLIST_LINK"			=> $STOPLIST_LINK,
								"EMAIL_TO"			=> COption::GetOptionString("main", "email_from", ""),
							);
							if (defined("SITE_ID") && strlen(SITE_ID)>0) $site_id = SITE_ID;
							else
							{
								$rsSite = CSite::GetDefList();
								$arSite = $rsSite->Fetch();
								$site_id = $arSite["ID"];
							}
							CEvent::Send("STATISTIC_ACTIVITY_EXCEEDING", $site_id, $arEventFields);

							$_SESSION["ACTIVITY_EXCEEDING_NOTIFIED"] = "Y";
						}
					}
				}
			}
		}
		return false;
	}

public static 	function GetAuditTypes()
	{
		return array(
			"STAT_ACTIVITY_LIMIT" => "[STAT_ACTIVITY_LIMIT] ".GetMessage("STAT_DEFENCE_LOG_EVENT"),
		);
	}

	fpublic static unction Set404($table = false, $where = false, $arrUpdate = false)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		static $STAT_DB_404 = array();

		if($table !== false)
		{
			if(strlen($table) > 0 && strlen($where) > 0 && is_array($arrUpdate))
			{
				foreach($arrUpdate as $field => $value)
				{
					$STAT_DB_404[$table][$where][$field] = "'".$DB->ForSql($value)."'";
				}
			}
		}
		else
		{
			if(defined("ERROR_404") && ERROR_404=="Y")
			{
				foreach($STAT_DB_404 as $table => $arrWhere)
				{
					foreach($arrWhere as $where => $arFields)
					{
						$DB->Update($table, $arFields, "WHERE ".$where, "File: ".__FILE__."<br>Line: ".__LINE__);
						unset($STAT_DB_404[$table][$where]);
					}
					unset($STAT_DB_404[$table]);
				}
				$STAT_DB_404 = array();
			}
		}
	}

	///////////////////////////////////////////////////////////////////
	// очистка статистики до определенной даты
	///////////////////////////////////////////////////////////////////

	/**
	* <p>Очищает собранные статистические данные.</p>
	*
	*
	* @param string $date = "" Дата в <a href="http://dev.1c-bitrix.ru/api_help/main/general/constants.php#format_date">формате
	* текущего сайта</a> (или языка) до которой (включительно) необходимо
	* очистить статистику. Если в данном параметре не указать дату, то
	* будут очищены все накопленные данные статистики.
	*
	* @param array &$errors  Если параметр <i>date</i> не содержит даты, то в данном параметре будут
	* возвращены возможные ошибки которые могут возникнуть в процессе
	* полной очистки данных статистики.
	*
	* @return bool <p>Метод возвращает "true", в случае успешного выполнения и "false" - в
	* противном случае.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // дата в формате текущего сайта или языка
	* // до которой включительно будет очищена вся собранная статистика
	* $date = "31.12.2007";
	* 
	* // очищаем
	* <b>CStatistics::CleanUp</b>($date);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a href="http://www.1c-bitrix.ru/user_help/statistic/settings.php">Настройки модуля
	* "Статистика"</a> </li> </ul> <a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cstatistics/cleanup.php
	* @author Bitrix
	*/
	public static 	function CleanUp($cleanup_date="", &$arErrors)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		if (strlen($cleanup_date)<=0)
		{
			$fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/install/db/".strtolower($DB->type)."/clean_up.sql";
			if (file_exists($fname))
			{
				$arErrors = $DB->RunSQLBatch($fname);
				if (!$arErrors)
				{
					$fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/install/db/".strtolower($DB->type)."/adv.sql";
					$arErrors2 = $DB->RunSQLBatch($fname);
					if (!$arErrors2) return true; else
					{
						$arErrors = array_merge($arErrors, $arErrors2);
						return false;
					}
				}
				else return false;
			}
		}
		else
		{
			$stmp = MkDateTime(ConvertDateTime($cleanup_date,"D.M.Y"),"d.m.Y");
			if ($stmp)
			{
				$arrTables = array(
					"b_stat_adv_guest"		=> "DATE_GUEST_HIT",
					"b_stat_adv_guest"		=> "DATE_HOST_HIT",
					"b_stat_adv_day"		=> "DATE_STAT",
					"b_stat_adv_event_day"	=> "DATE_STAT",
					"b_stat_day"			=> "DATE_STAT",
					"b_stat_day_site"		=> "DATE_STAT",
					"b_stat_event_day"		=> "DATE_STAT",
					"b_stat_event_list"		=> "DATE_ENTER",
					"b_stat_guest"			=> "LAST_DATE",
					"b_stat_hit"			=> "DATE_HIT",
					"b_stat_searcher_hit"	=> "DATE_HIT",
					"b_stat_phrase_list"	=> "DATE_HIT",
					"b_stat_referer"		=> "DATE_LAST",
					"b_stat_referer_list"	=> "DATE_HIT",
					"b_stat_searcher_day"	=> "DATE_STAT",
					"b_stat_session"		=> "DATE_LAST",
					"b_stat_page"			=> "DATE_STAT",
					"b_stat_country_day"	=> "DATE_STAT",
					"b_stat_path"			=> "DATE_STAT"
					);
				reset($arrTables);
				while (list($table_name, $date_name) = each($arrTables))
				{
					CStatistics::CleanUpTableByDate($cleanup_date, $table_name, $date_name);
				}
			}
		}
		return true;
	}

	///////////////////////////////////////////////////////////////////
	// пересчет финансовых показателей при смене базовой валюты
	///////////////////////////////////////////////////////////////////
public static 	function RecountBaseCurrency($new_base_currency)
	{
		$err_mess = "File: ".__FILE__."<br>Line: ";
		$DB = CDatabase::GetModuleConnection('statistic');
		$base_currency = GetStatisticBaseCurrency();
		if ($base_currency!="xxx" && strlen($base_currency)>0)
		{
			if (CModule::IncludeModule("currency"))
			{
				if (CCurrency::GetByID($base_currency))
				{
					$rate = CCurrencyRates::GetConvertFactor($base_currency, $new_base_currency);
					if ($rate!=1 && $rate>0)
					{
						$arUpdate = array(
							array("TABLE" => "b_stat_adv", "FIELDS" => array("COST", "REVENUE")),
							array("TABLE" => "b_stat_event", "FIELDS" => array("MONEY")),
							array("TABLE" => "b_stat_event_day", "FIELDS" => array("MONEY")),
							array("TABLE" => "b_stat_event_list", "FIELDS" => array("MONEY"))
							);
						set_time_limit(0);
						ignore_user_abort(true);
						$DB->StartTransaction();
						foreach ($arUpdate as $arr)
						{
							$arFields = $arr["FIELDS"];
							$strSql = "UPDATE ".$arr["TABLE"]." SET ";
							$i = 0;
							$str = "";
							foreach ($arFields as $field)
							{
								if ($i>0) $str .= ", ";
								$str .= $field." = round(".$field."*".$rate.",2)";
								$i++;
							}
							$DB->Query($strSql.$str, false, $err_mess.__LINE__);
						}
						$DB->Commit();
					}
				}
			}
		}
	}

	// функции для совместимости
public static 	function GetEventParam($site_id=false) { return CStatEvent::GetGID($site_id); }
public static 	function Set_Event($event1, $event2="", $event3="", $goto="", $money="", $currency="", $chargeback="N", $site_id=false) { return CStatEvent::AddCurrent($event1, $event2, $event3, $money, $currency, $goto, $chargeback, $site_id); }
public static 	function CheckForDDL()
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$rs=$DB->Query("select count(*) CNT from b_stat_ddl", true);
		if($rs)
		{
			$ar=$rs->Fetch();
			if($ar && intval($ar["CNT"])>0)
			{
				return true;
			}
		}
		return false;
	}
public static 	function GetDDL()
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$result = array();
		$rs=$DB->Query("select * from b_stat_ddl order by ID", true);
		if($rs)
		{
			while($ar=$rs->Fetch())
				$result[]=$ar;
		}
		return $result;
	}
public static 	function ExecuteDDL($ID)
	{
		$ID = intval($ID);
		$DB = CDatabase::GetModuleConnection('statistic');
		$rs=$DB->Query("select * from b_stat_ddl where ID=".$ID, true);
		if($rs)
		{
			if($ar=$rs->Fetch())
			{
				if($DB->Query($ar["SQL_TEXT"], true))
				{
					$bSuccess = true;
				}
				else
				{
					$bSuccess = false;
					if(strpos($DB->db_Error,"Duplicate key name")===0) $bSuccess=true;
					if(strpos($DB->db_Error,"Can't DROP")===0) $bSuccess=true;

					if(strpos($DB->db_Error,"ORA-00955")===0) $bSuccess=true;
					if(strpos($DB->db_Error,"ORA-01418")===0) $bSuccess=true;

					if(strpos($DB->db_Error,"#S0011")===0) $bSuccess=true;
					if(strpos($DB->db_Error,"#S0002")===0) $bSuccess=true;
				}
			}
			if($bSuccess)
			{
				$DB->Query("delete from b_stat_ddl where ID=".$ID, true);
				return true;
			}

		}
		return false;
	}
}

?>
