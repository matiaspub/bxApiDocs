<?
// define("STOP_STATISTICS", true);
// define("NOT_CHECK_PERMISSIONS", true);

if(isset($_GET["admin_section"]) && $_GET["admin_section"] == "Y")
	;// define("ADMIN_SECTION", true);
else
	;// define("BX_PUBLIC_TOOLS", true);

require_once(dirname(__FILE__)."/../include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

$sDocPath = $APPLICATION->GetCurPage();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title><?echo GetMessage("calend_title")?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET?>">
<style type="text/css">
.headtext{font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; color:#1A4D80;}
.headbg{background-color:#EBEBEB;}
.daytext{font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; color:#1F6F9A;}
.holidaytext{font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; color:#970000;}
</style>
<?
$form = preg_replace("/[^a-z0-9_\\[\\]]/i", "", $_REQUEST["form"]);
$name = preg_replace("/[^a-z0-9_\\[\\]]/i", "", $_REQUEST["name"]);
$from = preg_replace("/[^a-z0-9_\\[\\]]/i", "", $_REQUEST["from"]);
$to = preg_replace("/[^a-z0-9_\\[\\]]/i", "", $_REQUEST["to"]);
?>
<script type="text/javascript">
function KeyPress()
{
	if(window.event.keyCode == 27)
		window.close();
}
function InsertDate(valDate)
{
	window.opener.document['<?echo $form?>']['<?echo $name?>'].value = valDate;
	window.close();
}
function InsertPeriod(valDate1, valDate2)
{
<?if($from <> "" && $to <> ""):?>
	window.opener.document['<?echo $form?>']['<?echo $from?>'].value = valDate1;
	window.opener.document['<?echo $form?>']['<?echo $to?>'].value = valDate2;
	window.close();
<?else:?>
	InsertDate(valDate1);
<?endif;?>
}
</script>
</head>
<body bgcolor="#FFFFFF" text="#000000" leftmargin="8" topmargin="8" marginwidth="8" marginheight="8" link="#be6602" alink="#de8601" vlink="#6c4500" onKeyPress="KeyPress()">
<?
$aMonths = array(GetMessage("calend_jan"), GetMessage("calend_feb"), GetMessage("calend_mar"), GetMessage("calend_apr"), GetMessage("calend_may"), GetMessage("calend_jun"), GetMessage("calend_jul"), GetMessage("calend_aug"), GetMessage("calend_sep"), GetMessage("calend_okt"), GetMessage("calend_nov"), GetMessage("calend_des"));

global $DB;
$iH=IntVal(FmtDate($date,"H"));
$iI=IntVal(FmtDate($date,"I"));
$iS=IntVal(FmtDate($date,"S"));
if($iH+$iI+$iS>0)
	$dtformat = "FULL";
else
	$dtformat = "SHORT";

$aDate = ParseDate(FmtDate($date,"D.M.Y"),"dmy");
if(is_array($aDate) && $aDate[2] > 1971 && $aDate[2] < 2037) //unix 32-bit timestamp
	$currDate = mktime($iH, $iI, $iS, $aDate[1], $aDate[0], $aDate[2]);
else
	$currDate = time();

$y1 = intval(date("Y", $currDate));
$m1 = intval(date("n", $currDate));
$d1 = intval(date("j", $currDate));

$aInitDate = ParseDate(FmtDate($initdate,"D.M.Y"), "dmy");
if(is_array($aInitDate) && $aInitDate[2] > 1971 && $aInitDate[2] < 2037)
{
	$initDate = mktime($iH, $iI, $iS, $aInitDate[1], $aInitDate[0], $aInitDate[2]);
	$init_y = intval(date("Y", $initDate));
	$init_m = intval(date("n", $initDate));
	$init_d = intval(date("j", $initDate));
}
else
	$init_y = $init_m = $init_d = 0;

$today = time();
$today_y = intval(date("Y", $today));
$today_m = intval(date("n", $today));
$today_d = intval(date("j", $today));

$sParam = DeleteParam(array("date"));
if($sParam <> "")
	$sParam = "&amp;".$sParam;
?>
<table width="100%" border="0" cellspacing="1" cellpadding="2">
<tr>
	<td class="headbg" nowrap align="center"><font class="headtext">
		<a class="headtext" title="<?echo GetMessage("calend_prev_mon")?>" style="text-decoration:none; color:red;" href="<?echo $sDocPath."?date=".GetTime(mktime($iH, $iI, $iS, $m1-1, 1, $y1), $dtformat).$sParam?>">&laquo;</a>
		<a title="<?echo GetMessage("calend_per_mon")?>" href="javascript:InsertPeriod('<?echo GetTime(mktime($iH, $iI, $iS,  $m1, 1, $y1), $dtformat)?>','<?echo GetTime(mktime($iH, $iI, $iS,  $m1+1, 0, $y1), $dtformat)?>')" class="headtext"><?echo $aMonths[$m1-1]?></a>
		<a class="headtext" title="<?echo GetMessage("calend_next_mon")?>" style="text-decoration:none; color:red;" href="<?echo $sDocPath."?date=".GetTime(mktime($iH, $iI, $iS, $m1+1, 1, $y1), $dtformat).$sParam?>">&raquo;</a>
	</font></td>
	<td align="center" class="headbg" nowrap><font class="headtext">
		<a class="headtext" title="<?echo GetMessage("calend_prev_year")?>" style="text-decoration:none; color:red;" href="<?echo $sDocPath."?date=".GetTime(mktime($iH, $iI, $iS, $m1, 1, $y1-1), $dtformat).$sParam?>">&laquo;</a>
		<a title="<?echo GetMessage("calend_per_year")?>" href="javascript:InsertPeriod('<?echo GetTime(mktime($iH, $iI, $iS, 1, 1, $y1), $dtformat)?>','<?echo GetTime(mktime($iH, $iI, $iS, 1, 0, $y1+1), $dtformat)?>')" class="headtext"><?echo $y1?></a>
		<a class="headtext" title="<?echo GetMessage("calend_next_year")?>" style="text-decoration:none; color:red;" href="<?echo $sDocPath."?date=".GetTime(mktime($iH, $iI, $iS, $m1, 1, $y1+1), $dtformat).$sParam?>">&raquo;</a>
	</font></td>
	<td class="headbg" align="center"><a title="<?echo GetMessage("calend_curr")?>" href="<?echo $sDocPath."?date=".GetTime($today, $dtformat).$sParam?>" class="headtext" style="text-decoration:none; color:red;">*</a></td>
</tr>
</table>
<table width="100%" border="0" cellspacing="2" cellpadding="1">
<tr align="center">
	<td></td>
	<td class="headtext"><?echo GetMessage("calend_mo")?></td>
	<td class="headtext"><?echo GetMessage("calend_tu")?></td>
	<td class="headtext"><?echo GetMessage("calend_we")?></td>
	<td class="headtext"><?echo GetMessage("calend_th")?></td>
	<td class="headtext"><?echo GetMessage("calend_fr")?></td>
	<td class="headtext"><?echo GetMessage("calend_sa")?></td>
	<td class="headtext"><?echo GetMessage("calend_su")?></td>
</tr>
<?
	$firstDate = mktime($iH, $iI, $iS, $m1, 1, $y1);
	$firstDay = intval(date("w", $firstDate)-1);
	if($firstDay == -1)
		$firstDay = 6;

	$bBreak = false;
	for($i=0; $i<6; $i++)
	{
		$row = $i*7;
		if($i > 0 && intval(date("j", mktime($iH, $iI, $iS, $m1, 1-$firstDay+$row, $y1))) == 1)
			break;

		echo "<tr align=\"center\">\n".
			"<td><a title=\"".GetMessage("calend_per_week")."\" href=\"javascript:InsertPeriod('".GetTime(mktime($iH, $iI, $iS, $m1, 1-$firstDay+$row, $y1), $dtformat)."','".GetTime(mktime($iH, $iI, $iS, $m1, 1-$firstDay+$row+6, $y1), $dtformat)."')\" class=\"headtext\" style=\"text-decoration:none\">&gt;&nbsp;</a></td>";
		for($j=0; $j<7; $j++)
		{
			$date = mktime($iH, $iI, $iS, $m1, 1-$firstDay+$row+$j, $y1);
			$y = intval(date("Y", $date));
			$m = intval(date("n", $date));
			$d = intval(date("j", $date));

			if($i > 0 && $d == 1)
				$bBreak = true;

			$sStyle = "";
			if($row+$j+1 > $firstDay && !$bBreak)
			{
				if($d == $today_d && $m == $today_m && $y == $today_y)
					$sStyle .= "background-color:#EBEBEB; ";
				if($d == $init_d && $m == $init_m && $y == $init_y)
					$sStyle .= "border:1px solid #1E5995; ";
			}
			echo "<td style=\"".$sStyle."\">";
			if($row+$j+1 > $firstDay && !$bBreak)
			{
				echo
					"<font class=\"".($j==5 || $j==6? "holidaytext":"daytext")."\">".
					"<a title=\"".GetMessage("calend_date")."\" class=\"".($j==5 || $j==6? "holidaytext":"daytext")."\" href=\"javascript:InsertDate('".GetTime($date, $dtformat)."')\">".$d."</a>".
					"</font>";
			}
			else
				echo "<font class=\"daytext\">&nbsp;</font>";
			echo "</td>";
		}
		echo "</tr>";
		if($bBreak)
			break;
	}

?>
</table>
</body>
</html>
<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>
