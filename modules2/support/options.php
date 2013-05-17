<?
// Clear Category table
function ClearCategoryTable()
{ 
	global $DB;
	$err_mess = "<br>Module: support<br>File: options<br>Function: ClearCategoryTable<br>Line: ";
	$DB->Query("DELETE FROM b_ticket_sla_2_category", false, $err_mess.__LINE__);
	$arrSLA = array();
	$strSql = "
		SELECT
			SLA.ID
		FROM
			b_ticket_sla SLA
	";
	$z = $DB->Query( $strSql, false, $err_mess . __LINE__ );
	while( $zr = $z->Fetch() ) 
	{
		$arFields_i = array(
			"SLA_ID"		=> intval( $zr["ID"] ),
			"CATEGORY_ID"	=> 0,
		);
		$ID = $DB->Insert( "b_ticket_sla_2_category", $arFields_i, $err_mess . __LINE__ );
	}
		
}
$module_id = "support";
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
IncludeModuleLangFile(__FILE__);
$SUP_RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($SUP_RIGHT>="R") :

if ($REQUEST_METHOD=="GET" && $SUP_RIGHT>="W" && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
{
	COption::RemoveOption("support");
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}
$message = false;
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/include.php");
if($REQUEST_METHOD=="POST" && strlen($Update)>0 && $SUP_RIGHT>="W" && check_bitrix_sessid())
{
	$SUPPORT_DIR = str_replace("\\", "/", $SUPPORT_DIR);
	$SUPPORT_DIR = str_replace("//", "/", $SUPPORT_DIR);
	COption::SetOptionString($module_id, "SUPPORT_DIR", $SUPPORT_DIR);
	COption::SetOptionString($module_id, "SUPPORT_EDIT", $SUPPORT_EDIT);
	COption::SetOptionString($module_id, "SUPPORT_MAX_FILESIZE", intval($SUPPORT_MAX_FILESIZE));
	COption::SetOptionString($module_id, "ONLINE_INTERVAL", intval($ONLINE_INTERVAL));
	COption::SetOptionString($module_id, "DEFAULT_VALUE_HIDDEN", $DEFAULT_VALUE_HIDDEN);
	COption::SetOptionString($module_id, "DEFAULT_RESPONSIBLE_ID", $DEFAULT_RESPONSIBLE_ID);
	COption::SetOptionString($module_id, "DEFAULT_AUTO_CLOSE_DAYS", $DEFAULT_AUTO_CLOSE_DAYS);
	COption::SetOptionString($module_id, "VIEW_TICKET_DEFAULT_MODE", $VIEW_TICKET_DEFAULT_MODE);
	COption::SetOptionString($module_id, "ONLINE_AUTO_REFRESH", $ONLINE_AUTO_REFRESH);
	COption::SetOptionString($module_id, "TICKETS_PER_PAGE", $TICKETS_PER_PAGE);
	COption::SetOptionString($module_id, "MESSAGES_PER_PAGE", $MESSAGES_PER_PAGE);
	COption::SetOptionString($module_id, "SOURCE_MAIL", $SOURCE_MAIL);
	COption::SetOptionString($module_id, "REINDEX_MSG_S", $REINDEX_MSG_S);
	if (preg_match_all('|#|'.BX_UTF_PCRE_MODIFIER, $SUPERTICKET_COUPON_FORMAT, $_tmp) && is_array($_tmp[0]) && count($_tmp[0]) >= 6)
	{
		COption::SetOptionString($module_id, "SUPERTICKET_COUPON_FORMAT", $SUPERTICKET_COUPON_FORMAT);
	}
	else 
	{
		$message = new CAdminMessage(GetMessage('SUP_SUPERTICKET_ERROR'));
	}
	COption::SetOptionString($module_id, "SUPERTICKET_DEFAULT_SLA", $SUPERTICKET_DEFAULT_SLA);
	
	$SUPPORT_OLD_FUNCTIONALITY_OLD = COption::GetOptionString($module_id, "SUPPORT_OLD_FUNCTIONALITY");
	$SUPPORT_OLD_FUNCTIONALITY = ( $SUPPORT_OLD_FUNCTIONALITY == "Y" ? "Y" : "N" );
	if( $SUPPORT_OLD_FUNCTIONALITY_OLD <> $SUPPORT_OLD_FUNCTIONALITY ) ClearCategoryTable();
	COption::SetOptionString($module_id, "SUPPORT_OLD_FUNCTIONALITY",  $SUPPORT_OLD_FUNCTIONALITY);
	COption::SetOptionString($module_id, "SUPPORT_DEFAULT_SLA_ID", intval(  $SUPPORT_DEFAULT_SLA_ID ) );
	COption::SetOptionString($module_id, "SUPPORT_CACHE_DAYS_FORWARD", intval(  $SUPPORT_CACHE_DAYS_FORWARD ) );
	COption::SetOptionString($module_id, "SUPPORT_CACHE_DAYS_BACKWARD", intval(  $SUPPORT_CACHE_DAYS_BACKWARD ) );
	
}
$SUPPORT_DIR = COption::GetOptionString($module_id, "SUPPORT_DIR");
$SUPPORT_EDIT = COption::GetOptionString($module_id, "SUPPORT_EDIT");
$SUPPORT_MAX_FILESIZE = COption::GetOptionString($module_id, "SUPPORT_MAX_FILESIZE");
$ONLINE_INTERVAL = COption::GetOptionString($module_id, "ONLINE_INTERVAL");
$DEFAULT_VALUE_HIDDEN = COption::GetOptionString($module_id, "DEFAULT_VALUE_HIDDEN");
$DEFAULT_RESPONSIBLE_ID = COption::GetOptionString($module_id, "DEFAULT_RESPONSIBLE_ID");
$DEFAULT_AUTO_CLOSE_DAYS = COption::GetOptionString($module_id, "DEFAULT_AUTO_CLOSE_DAYS");
$VIEW_TICKET_DEFAULT_MODE = COption::GetOptionString($module_id, "VIEW_TICKET_DEFAULT_MODE");
$ONLINE_AUTO_REFRESH = COption::GetOptionString($module_id, "ONLINE_AUTO_REFRESH");
$TICKETS_PER_PAGE = COption::GetOptionString($module_id, "TICKETS_PER_PAGE");
$MESSAGES_PER_PAGE = COption::GetOptionString($module_id, "MESSAGES_PER_PAGE");
$SOURCE_MAIL = COption::GetOptionString($module_id, "SOURCE_MAIL");
$SUPERTICKET_COUPON_FORMAT = COption::GetOptionString($module_id, "SUPERTICKET_COUPON_FORMAT");
$SUPERTICKET_DEFAULT_SLA = COption::GetOptionString($module_id, "SUPERTICKET_DEFAULT_SLA");
$SUPPORT_OLD_FUNCTIONALITY = COption::GetOptionString($module_id, "SUPPORT_OLD_FUNCTIONALITY");
$SUPPORT_DEFAULT_SLA_ID = COption::GetOptionString($module_id, "SUPPORT_DEFAULT_SLA_ID");
$SUPPORT_CACHE_DAYS_FORWARD = COption::GetOptionString($module_id, "SUPPORT_CACHE_DAYS_FORWARD");
$SUPPORT_CACHE_DAYS_BACKWARD = COption::GetOptionString($module_id, "SUPPORT_CACHE_DAYS_BACKWARD");
$REINDEX_MSG_S = COption::GetOptionString($module_id, "REINDEX_MSG_S",8);

if ($message)
	echo $message->Show();

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "support_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "support_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<?
$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?=LANGUAGE_ID?>">
<?=bitrix_sessid_post()?>
<?
$tabControl->BeginNextTab();
?>
	<tr>
		<td valign="top" width="50%"><?=GetMessage("SUP_URL_PUBLIC")?></td>
		<td valign="top" width="50%"><input type="text" size="40" value="<?echo htmlspecialcharsbx($SUPPORT_DIR)?>" name="SUPPORT_DIR"></td>
	</tr>
	<tr>
		<td valign="top" width="50%"><?=GetMessage("SUP_URL_PUBLIC_EDIT")?></td>
		<td valign="top" width="50%"><input type="text" size="40" value="<?echo htmlspecialcharsbx($SUPPORT_EDIT)?>" name="SUPPORT_EDIT"></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_MAX_FILESIZE")?></td>
		<td valign="top"><input type="text" size="5" value="<?echo htmlspecialcharsbx($SUPPORT_MAX_FILESIZE)?>" name="SUPPORT_MAX_FILESIZE"></td>
	</tr>
	<tr>
		<td valign="top"><label for="DEFAULT_VALUE_HIDDEN"><?=GetMessage("SUP_DEFAULT_VALUE_HIDDEN")?></label></td>
		<td valign="top"><?echo InputType("checkbox", "DEFAULT_VALUE_HIDDEN", "Y", $DEFAULT_VALUE_HIDDEN, false, "", 'id="DEFAULT_VALUE_HIDDEN"')?></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_DEFAULT_RESPONSIBLE")?></td>
		<td valign="top"><?
			echo SelectBox("DEFAULT_RESPONSIBLE_ID", CTicket::GetSupportTeamList(), " ", $DEFAULT_RESPONSIBLE_ID);
			?></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_DEFAULT_AUTO_CLOSE_DAYS")?></td>
		<td valign="top"><?
		$ref_id = array("-1", "0");
		$ref = array(GetMessage("SUP_NOT_CHANGE"), GetMessage("SUP_SET_NULL"));
		for ($i=1;$i<=90;$i++)
		{
			$ref[] = $i." ".GetMessage("SUP_DAY");
			$ref_id[] = $i;
		}
		$arr = Array("reference" => $ref, "reference_id" => $ref_id);
		echo SelectBoxFromArray("DEFAULT_AUTO_CLOSE_DAYS", $arr, $DEFAULT_AUTO_CLOSE_DAYS, "");
		?></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_DEFAULT_MODE")?></td>
		<td valign="top"><?
		$ref_id = array("", "view", "answer");
		$ref = array(GetMessage("SUP_NO_MODE"), GetMessage("SUP_VIEW_MODE"), GetMessage("SUP_ANSWER_MODE"));
		$arr = Array("reference" => $ref, "reference_id" => $ref_id);
		echo SelectBoxFromArray("VIEW_TICKET_DEFAULT_MODE", $arr, $VIEW_TICKET_DEFAULT_MODE, "");
		?></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_ONLINE_INTERVAL")?></td>
		<td valign="top"><?
		$ref_id = array(
			"60",
			"120",
			"180",
			"240",
			"300",
			"360",
			"420",
			"480",
			"540",
			"600",
			"900",
			);
		$ref = array(
			"1 ".GetMessage("SUP_MIN"),
			"2 ".GetMessage("SUP_MIN"),
			"3 ".GetMessage("SUP_MIN"),
			"4 ".GetMessage("SUP_MIN"),
			"5 ".GetMessage("SUP_MIN"),
			"6 ".GetMessage("SUP_MIN"),
			"7 ".GetMessage("SUP_MIN"),
			"8 ".GetMessage("SUP_MIN"),
			"9 ".GetMessage("SUP_MIN"),
			"10 ".GetMessage("SUP_MIN"),
			"15 ".GetMessage("SUP_MIN"),
			);
		$arr = Array("reference" => $ref, "reference_id" => $ref_id);
		echo SelectBoxFromArray("ONLINE_INTERVAL", $arr, $ONLINE_INTERVAL, "");
		?></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_ONLINE_AUTO_REFRESH")?></td>
		<td valign="top"><?
		$ref_id = array(
			"0",
			"10",
			"20",
			"30",
			"40",
			"50",
			"60",
			"120",
			"180",
			"240",
			"300",
			);
		$ref = array(
			GetMessage("SUP_NO_AUTO_REFRESH"),
			"10 ".GetMessage("SUP_SEC"),
			"20 ".GetMessage("SUP_SEC"),
			"30 ".GetMessage("SUP_SEC"),
			"40 ".GetMessage("SUP_SEC"),
			"50 ".GetMessage("SUP_SEC"),
			"1 ".GetMessage("SUP_MIN"),
			"2 ".GetMessage("SUP_MIN"),
			"3 ".GetMessage("SUP_MIN"),
			"4 ".GetMessage("SUP_MIN"),
			"5 ".GetMessage("SUP_MIN"),
			);
		$arr = Array("reference" => $ref, "reference_id" => $ref_id);
		echo SelectBoxFromArray("ONLINE_AUTO_REFRESH", $arr, $ONLINE_AUTO_REFRESH, "");
		?></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage("SUP_MESSAGES_PER_PAGE")?></td>
		<td valign="top"><input type="text" size="5" value="<?=intval($MESSAGES_PER_PAGE)?>" name="MESSAGES_PER_PAGE"></td>
	</tr>
	<tr>
		<td valign="top"><?=GetMessage('SUP_SUPERTICKET_COUPON_FORMAT')?></td>
		<td valign="top"><input type="text" size="30" value="<?echo htmlspecialcharsbx($SUPERTICKET_COUPON_FORMAT)?>" name="SUPERTICKET_COUPON_FORMAT"></td>
	</tr>
	<?
	$arr = Array("reference" => array(), "reference_id" => array());
	$rs = CTicketSLA::GetList($a = array('NAME' => 'ASC'), array(), $__is_f);
	while ($arSla = $rs->GetNext())
	{
		$arr['reference'][] = htmlspecialcharsback($arSla['NAME']) . ' ['.$arSla['ID'].']';
		$arr['reference_id'][] = $arSla['ID'];
	}
	$arr2['reference_id'][] = "Y";
	$arr2['reference'][] =GetMessage('SUP_OLD_FUNCTIONALITY_Y');
	$arr2['reference_id'][] = "N";
	$arr2['reference'][] =GetMessage('SUP_OLD_FUNCTIONALITY_N');
	?>
	<tr>
		<td valign="top"><?=GetMessage('SUP_SUPERTICKET_DEFAULT_SLA')?></td>
		<td valign="top"><?=SelectBoxFromArray("SUPERTICKET_DEFAULT_SLA", $arr, $SUPERTICKET_DEFAULT_SLA , "")?></td>
	</tr>
	
	<tr>
		<td valign="top"><?=GetMessage('SUP_DEFAULT_SLA_ID')?></td>
		<td valign="top"><?=SelectBoxFromArray("SUPPORT_DEFAULT_SLA_ID", $arr, $SUPPORT_DEFAULT_SLA_ID , "")?></td>
	</tr>
				
	<tr>
		<td valign="top"><?=GetMessage('SUP_OLD_FUNCTIONALITY')?></td>
		<td valign="top"><?=SelectBoxFromArray("SUPPORT_OLD_FUNCTIONALITY", $arr2, $SUPPORT_OLD_FUNCTIONALITY , "")?></td>
	</tr>
	
	<?
	$arr = Array("reference" => array(), "reference_id" => array());
	$rs = CTicketDictionary::GetDropDown("SR");
	$arEmail = array();
	while ($arDict = $rs->GetNext())
	{
		if (!isset($arEmail[$arDict['SID']]) && isset($arDict['SID']) && !empty($arDict['SID'])) 
		{
			$arEmail[$arDict['SID']] = htmlspecialcharsback($arDict['NAME']);
			$arr['reference'][] = htmlspecialcharsback($arDict['NAME']);
			$arr['reference_id'][] = $arDict['SID'];			
		}

	}
	?>
	<tr>
		<td valign="top"><?=GetMessage('SUP_SOURCE_MAIL')?></td>
		<td valign="top"><?=SelectBoxFromArray("SOURCE_MAIL", $arr, $SOURCE_MAIL, "")?></td>
	</tr>
	
	<tr>
		<td valign="top"><? echo GetMessage('SUP_REINDEX_MSG_TIME'); ?></td>
		<td valign="top">
			<script type="text/javascript">
				var reindexAlreadyRunning = false;
				var reindexStep = 0;
				function callbackFnOK(datum)
				{
					//alert(datum);
					a = document.getElementById( "REINDEX_A" );
					var obj = JSON.parse( datum );
					if( !obj.hasOwnProperty( "LAST_ID" ) )
					{
						a.innerHTML = "<? echo GetMessage("SUP_REINDEX_MSG_BTN"); ?>";
						reindexAlreadyRunning = false;
						reindexStep = 0;
						alert( "<? echo GetMessage("SUP_REINDEX_STRANGE_ANSWER"); ?>" );
						return;
					}
					if( obj.LAST_ID == (-1) )
					{
						a.innerHTML = "<? echo GetMessage("SUP_REINDEX_MSG_BTN"); ?>";
						reindexAlreadyRunning = false;
						reindexStep = 0;
						alert( "<? echo GetMessage("SUP_REINDEX_END"); ?>" );
						return;
					}
					reindexStep = reindexStep + 1;
					a.innerHTML = "<? echo GetMessage("SUP_REINDEX_MSG_BTN_IN_PROGRESS"); ?>"+reindexStep;
					reindexMessagesJS( obj.LAST_ID );
				}
				function callbackFnNoOK(datum)
				{
					//alert(datum);
					a = document.getElementById( "REINDEX_A" );
					a.innerHTML = "<? echo GetMessage("SUP_REINDEX_MSG_BTN"); ?>";
					reindexAlreadyRunning = false;
					reindexStep = 0;
					alert( "<? echo GetMessage("SUP_REINDEX_NO_OK"); ?>" );
				}
				function reindexMessagesJS( firstID = 0 )
				{
					if( reindexAlreadyRunning == true && firstID == 0 ) return;
					if( firstID == 0 )
					{
						a = document.getElementById( "REINDEX_A" );
						reindexStep = reindexStep + 1;
						a.innerHTML = "<? echo GetMessage("SUP_REINDEX_MSG_BTN_IN_PROGRESS"); ?>"+reindexStep;
					}
					reindexAlreadyRunning = true; 
					nextFieldF = document.getElementById( "REINDEX_MSG_S" );
					var data = {
						'MY_AJAX' : 'reindexMessAJAX',
						"b_sessid": '<? echo bitrix_sessid(); ?>',
						'reindexMessAJAXData' : { 
							"periodS" : nextFieldF.value,
							"firstID" : firstID, 
						}
					};
					
					return BX.ajax({
						'method': 'POST',
						'dataType': 'html',
						'url': "ticket_messages_reindex.php",
						'data':  data,
						'onsuccess': callbackFnOK,
						'onfailure': callbackFnNoOK
					});
					
				}
			</script>
			<input id="REINDEX_MSG_S" name="REINDEX_MSG_S" size="5" value="<? echo $REINDEX_MSG_S; ?>"><a id="REINDEX_A" href="#nul" onClick="javascript: reindexMessagesJS();"><? echo GetMessage('SUP_REINDEX_MSG_BTN'); ?></a>
		</td>
	</tr>
		
	<tr>
		<td valign="top"><?=GetMessage('SUP_CACHE_DAYS_BACKWARD')?></td>
		<td valign="top"><input type="text" size="30" value="<? echo intval( $SUPPORT_CACHE_DAYS_BACKWARD ); ?>" name="SUPPORT_CACHE_DAYS_BACKWARD"></td>
	</tr>
	
	<tr>
		<td valign="top"><?=GetMessage('SUP_CACHE_DAYS_FORWARD')?></td>
		<td valign="top"><input type="text" size="30" value="<? echo intval( $SUPPORT_CACHE_DAYS_FORWARD ); ?>" name="SUPPORT_CACHE_DAYS_FORWARD"></td>
	</tr>
		
	<tr>
		<td valign="top">&nbsp;</td>
		<td valign="top">
			<script type="text/javascript">
				var reindexAlreadyRunning = false;
				var reindexStep = 0;
				function callbackFnRAOK(datum)
				{
					var obj = JSON.parse( datum );
					if( obj.hasOwnProperty( "ALL_OK" ) && obj.ALL_OK == "OK" )
					{
						alert( "<? echo GetMessage("SUP_RESTART_AGENTS_OK"); ?>" );
						return;
					}
					alert( "<? echo GetMessage("SUP_RESTART_AGENTS_NO_OK"); ?>" );
				}
				function callbackFnRANoOK(datum)
				{
					alert( "<? echo GetMessage("SUP_RESTART_AGENTS_NO_OK"); ?>" );
				}
				function restartAgentsJS( firstID = 0 )
				{
					var data = {
						'MY_AJAX' : 'restartAgentsAJAX',
						"b_sessid": '<? echo bitrix_sessid(); ?>'
					};
					
					return BX.ajax({
						'method': 'POST',
						'dataType': 'html',
						'url': "ticket_messages_reindex.php",
						'data':  data,
						'onsuccess': callbackFnRAOK,
						'onfailure': callbackFnRANoOK
					});
					
				}
			</script>
			<a id="RESTART_AGENTS" href="#nul" onClick="javascript: restartAgentsJS();"><? echo GetMessage('SUP_RESTART_AGENTS_BTN'); ?></a>
		</td>
	</tr>
	
	
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if(confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>&<?echo bitrix_sessid_get()?>";
}
</script>
<input <?if ($SUP_RIGHT<"W") echo "disabled" ?> type="submit" name="Update" value="<?=GetMessage("SUP_SAVE")?>">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?=GetMessage("SUP_RESET")?>">
<input <?if ($SUP_RIGHT<"W") echo "disabled" ?> type="button" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;?>
