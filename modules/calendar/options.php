<?
IncludeModuleLangFile(__FILE__);
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
CModule::IncludeModule('calendar');
CModule::IncludeModule('iblock');

if (!$USER->CanDoOperation('edit_php')) // Is admin
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("CAL_OPT_SETTINGS"), "ICON" => "calendar_settings", "TITLE" => GetMessage("CAL_SETTINGS_TITLE"),
	),
	array(
		"DIV" => "edit2", "TAB" => GetMessage("CAL_OPT_TYPES"), "ICON" => "calendar_settings", "TITLE" => GetMessage("CAL_OPT_TYPES"),
	)
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

CUtil::InitJSCore(array('ajax', 'window', 'popup', 'access'));

$arTypes = CCalendarType::GetList();
$dbSites = CSite::GetList($by = 'sort', $order = 'asc', array('ACTIVE' => 'Y'));
$arSites = array();
$default_site = '';
while ($arRes = $dbSites->GetNext())
{
	$arSites[$arRes['ID']] = '('.$arRes['ID'].') '.$arRes['NAME'];
	if ($arRes['DEF'] == 'Y')
		$default_site = $arRes['ID'];
}

$bShowPathForSites = true;
if (count($arSites) <= 1)
	$bShowPathForSites = false;

$arForums = array();
if (CModule::IncludeModule("forum"))
{
	$db = CForumNew::GetListEx();
	while ($ar = $db->GetNext())
		$arForums[$ar["ID"]] = "[".$ar["ID"]."] ".$ar["NAME"];
}

if ($REQUEST_METHOD == "POST" && isset($_REQUEST['save_type']) && $_REQUEST['save_type'] == 'Y' && check_bitrix_sessid())
{
	//CUtil::JSPostUnEscape();
	$APPLICATION->RestartBuffer();
	if (isset($_REQUEST['del_type']) && $_REQUEST['del_type'] == 'Y')
	{
		$xmlId = trim($_REQUEST['type_xml_id']);
		if ($xmlId != '')
			CCalendarType::Delete($xmlId);
	}
	else
	{
		$bNew = isset($_POST['type_new']) && $_POST['type_new'] == 'Y';
		$xmlId = trim($bNew ? $_POST['type_xml_id'] : $_POST['type_xml_id_hidden']);
		$name = trim($_POST['type_name']);

		if ($xmlId != '' && $name != '')
		{
			$XML_ID = CCalendarType::Edit(array(
				'NEW' => $bNew,
				'arFields' => array(
					'XML_ID' => $xmlId,
					'NAME' => $name,
					'DESCRIPTION' => trim($_POST['type_desc'])
				)
			));

			if ($XML_ID)
			{
				$arTypes_ = CCalendarType::GetList(array('arFilter' => array('XML_ID' => $XML_ID)));
				if ($arTypes_[0])
					OutputTypeHtml($arTypes_[0]);
			}
		}
	}
	die();
}

if($REQUEST_METHOD=="POST" && strlen($Update.$Apply.$RestoreDefaults)>0 && check_bitrix_sessid())
{
	if(strlen($RestoreDefaults) > 0)
	{
		COption::RemoveOption("calendar");
	}
	else
	{
		// Save permissions for calendar types
		foreach($_POST['cal_type_perm'] as $xml_id => $perm)
		{
			// Save type permissions
			CCalendarType::Edit(array(
				'NEW' => false,
				'arFields' => array(
					'XML_ID' => $xml_id,
					'ACCESS' => $perm
				)
			));
		}

		$SET = array(
			'work_time_start' => $_REQUEST['work_time_start'],
			'work_time_end' => $_REQUEST['work_time_end'],
			'year_holidays' => $_REQUEST['year_holidays'],
			'year_workdays' => $_REQUEST['year_workdays'],
			'week_holidays' => implode('|',$_REQUEST['week_holidays']),
			//'week_start' => $_REQUEST['week_start'],
			'user_name_template' => $_REQUEST['user_name_template'],
			'user_show_login' => isset($_REQUEST['user_show_login']),
			'path_to_user' => $_REQUEST['path_to_user'],
			'path_to_user_calendar' => $_REQUEST['path_to_user_calendar'],
			'path_to_group' => $_REQUEST['path_to_group'],
			'path_to_group_calendar' => $_REQUEST['path_to_group_calendar'],
			'path_to_vr' => $_REQUEST['path_to_vr'],
			'path_to_rm' => $_REQUEST['path_to_rm'],
			'rm_iblock_type' => $_REQUEST['rm_iblock_type'],
			'rm_iblock_id' => $_REQUEST['rm_iblock_id'],
			'denied_superpose_types' => array(),
			'pathes_for_sites' => isset($_REQUEST['pathes_for_sites']),
			'pathes' => $_REQUEST['pathes'],
			'dep_manager_sub' => isset($_REQUEST['dep_manager_sub']),

			'forum_id' => intVal($_REQUEST['calendar_forum_id']),
			//'comment_allow_edit' =>  isset($_REQUEST['calendar_comment_allow_edit']),
			//'comment_allow_remove' =>  isset($_REQUEST['calendar_comment_allow_remove']),
			//'max_upload_files_in_comments' =>  isset($_REQUEST['calendar_max_upload_files_in_comments'])
		);

		if (CModule::IncludeModule("video"))
		{
			$SET['vr_iblock_id'] = $_REQUEST['vr_iblock_id'];
		}

		foreach($arTypes as $type)
		{
			if (!in_array($type['XML_ID'], $_REQUEST['denied_superpose_types']))
				$SET['denied_superpose_types'][] = $type['XML_ID'];
		}

		$CUR_SET = CCalendar::GetSettings(array('getDefaultForEmpty' => false));
		foreach($CUR_SET as $key => $value)
		{
			if (!isset($SET[$key]) && isset($value))
				$SET[$key] = $value;
		}

		CCalendar::SetSettings($SET);
	}

	if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"]) > 0)
		LocalRedirect($_REQUEST["back_url_settings"]);
	else
		LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
}

$dbIBlockType = CIBlockType::GetList();
$arIBTypes = array();
$arIB = array();
while ($arIBType = $dbIBlockType->Fetch())
{
	if ($arIBTypeData = CIBlockType::GetByIDLang($arIBType["ID"], LANG))
	{
		$arIB[$arIBType['ID']] = array();
		$arIBTypes[$arIBType['ID']] = '['.$arIBType['ID'].'] '.$arIBTypeData['NAME'];
	}
}

$dbIBlock = CIBlock::GetList(array('SORT' => 'ASC'), array('ACTIVE' => 'Y'));
while ($arIBlock = $dbIBlock->Fetch())
{
	$arIB[$arIBlock['IBLOCK_TYPE_ID']][$arIBlock['ID']] = ($arIBlock['CODE'] ? '['.$arIBlock['CODE'].'] ' : '').$arIBlock['NAME'];
}

$SET = CCalendar::GetSettings(array('getDefaultForEmpty' => false));

$tabControl->Begin();
?>
<form method="post" name="cal_opt_form" action="<?= $APPLICATION->GetCurPage()?>?mid=<?= urlencode($mid)?>&amp;lang=<?= LANGUAGE_ID?>">
<?= bitrix_sessid_post();?>
<?
$arDays = Array('MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU');

$arWorTimeList = array();
for ($i = 0; $i < 24; $i++)
{
	$arWorTimeList[strval($i)] = CCalendar::FormatTime($i, 0);
	$arWorTimeList[strval($i).'.30'] = CCalendar::FormatTime($i, 30);
}
$tabControl->BeginNextTab();
?>
	<tr>
		<td><label for="cal_work_time"><?= GetMessage("CAL_WORK_TIME")?>:</label></td>
		<td>
			<select id="cal_work_time" name="work_time_start">
				<?foreach($arWorTimeList as $key => $val):?>
					<option value="<?= $key?>" <? if ($SET['work_time_start'] == $key){echo ' selected="selected" ';}?>><?= $val?></option>
				<?endforeach;?>
			</select>
			&mdash;
			<select id="cal_work_time" name="work_time_end">
				<?foreach($arWorTimeList as $key => $val):?>
					<option value="<?= $key?>" <? if ($SET['work_time_end'] == $key){echo ' selected="selected" ';}?>><?= $val?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>

	<tr>
		<td style="vertical-align: top;"><label for="cal_week_holidays"><?= GetMessage("CAL_WEEK_HOLIDAYS")?>:</label></td>
		<td>
			<select size="7" multiple=true id="cal_week_holidays" name="week_holidays[]">
				<?foreach($arDays as $day):?>
					<option value="<?= $day?>" <?if (in_array($day, $SET['week_holidays'])){echo ' selected="selected"';}?>><?= GetMessage('CAL_OPTION_FIRSTDAY_'.$day)?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>
	<? /*
	<tr>
		<td><label for="cal_week_start"><?= GetMessage("CAL_OPTION_FIRSTDAY")?>:</label></td>
		<td>
			<select id="cal_week_start" name="week_start">
				<?foreach($arDays as $day):?>
					<option value="<?= $day?>" <? if ($SET['week_start'] == $day){echo ' selected="selected" ';}?>><?= GetMessage('CAL_OPTION_FIRSTDAY_'.$day)?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>
	*/
	?>
	<tr>
		<td><label for="cal_year_holidays"><?= GetMessage("CAL_YEAR_HOLIDAYS")?>:</label></td>
		<td>
			<input name="year_holidays" type="text" value="<?= htmlspecialcharsbx($SET['year_holidays'])?>" id="cal_year_holidays" size="60"/>
		</td>
	</tr>
	<tr>
		<td><label for="cal_year_workdays"><?= GetMessage("CAL_YEAR_WORKDAYS")?>:</label></td>
		<td>
			<input name="year_workdays" type="text" value="<?= htmlspecialcharsbx($SET['year_workdays'])?>" id="cal_year_workdays" size="60"/>
		</td>
	</tr>
	<?if (CCalendar::IsIntranetEnabled()):?>
	<tr>
		<td><label for="cal_user_name_template"><?= GetMessage("CAL_USER_NAME_TEMPLATE")?>:</label></td>
		<td>
			<input name="user_name_template" type="text" value="<?= htmlspecialcharsbx($SET['user_name_template'])?>" id="cal_user_name_template" size="60" />
		</td>
	</tr>
	<tr>
		<td><input name="user_show_login" type="checkbox" value="Y" id="cal_user_show_login" <?if($SET['user_show_login']){echo'checked';}?>/></td>
		<td>
			<label for="cal_user_show_login"><?= GetMessage("CAL_USER_SHOW_LOGIN")?></label>
		</td>
	</tr>
	<tr title="<?= GetMessage('CAL_DEP_MANAGER_SUB_TITLE')?>">
		<td><input name="dep_manager_sub" type="checkbox" value="Y" id="cal_dep_manager_sub" <?if($SET['dep_manager_sub']){echo'checked';}?>/></td>
		<td>
			<label for="cal_dep_manager_sub"><?= GetMessage("CAL_DEP_MANAGER_SUB")?></label>
		</td>
	</tr>
	<tr>
		<td style="vertical-align: top;"><label for="denied_superpose_types"><?= GetMessage("CAL_SP_TYPES")?>:</label></td>
		<td>
			<select size="3" multiple=true id="denied_superpose_types" name="denied_superpose_types[]">
				<?foreach($arTypes as $type):?>
					<option value="<?= $type["XML_ID"]?>" <?if (!in_array($type["XML_ID"], $SET['denied_superpose_types'])){echo ' selected="selected"';}?>><?= htmlspecialcharsex($type["NAME"])?></option>
				<?endforeach;?>
			</select>
		</td>
	</tr>

	<!-- Path parameters title -->
	<tr class="heading"><td colSpan="2"><?= GetMessage('CAL_PATH_TITLE')?></td></tr>

	<?
	$arPathes = CCalendar::GetPathesList();

	$commonForSites = $SET['pathes_for_sites'];
	if (count($arSites) > 1):?>
	<tr>
		<td>
		<input name="pathes_for_sites" type="checkbox"  id="cal_pathes_for_sites" <?if($commonForSites){echo 'checked=true';}?> value="Y" /></td>
		<td>
			<label for="cal_pathes_for_sites"><?= GetMessage("CAL_PATH_COMMON")?></label>
<script>
BX.ready(function(){
	BX('cal_pathes_for_sites').onclick = function()
	{
		BX('bx-cal-opt-sites-pathes-tr').style.display = this.checked ? 'none' : '';
		<?foreach($arPathes as $pathName):?>
			BX('bx-cal-opt-path-<?= $pathName?>').style.display = this.checked ? '' : 'none';
		<?endforeach;?>
	};
});
</script>
		</td>
	</tr>
	<tr id="bx-cal-opt-sites-pathes-tr" <?if($commonForSites){echo'style="display:none;"';}?>>
		<td colSpan="2" align="center">
		<?
		$aSubTabs = array();
		foreach($arSites as $siteId => $siteName)
			$aSubTabs[] = array("DIV" => "opt_cal_path_".$siteId, "TAB" => $siteName, 'TITLE' => $siteName);

		$arChildTabControlUserCommon = new CAdminViewTabControl("childTabControlUserCommon", $aSubTabs);
		$arChildTabControlUserCommon->Begin();?>
		<?foreach($arSites as $siteId => $siteName):?>
		<?$arChildTabControlUserCommon->BeginNextTab();?>
			<table>
			<?
			foreach($arPathes as $pathName):
				$val = $SET['pathes'][$siteId][$pathName];
				if (!isset($val) || empty($val))
					$val = $SET[$pathName];
				?>
				<tr>
					<td class="field-name"><label for="cal_<?= $pathName?>"><?= GetMessage("CAL_".strtoupper($pathName))?>:</label></td>
					<td>
						<input name="pathes[<?= $siteId?>][<?= $pathName?>]" type="text" value="<?= htmlspecialcharsbx($val)?>" id="cal_<?= $pathName?>" size="60"/>
					</td>
				</tr>
			<?endforeach;?>
			</table>
		<?endforeach;?>
		<?$arChildTabControlUserCommon->End();?>
		</td>
	</tr>
	<?endif; /* if (count($arSites) > 1)*/?>

	<?
	/* common pathes for all sites*/
	if (count($arSites) <= 1)
		$commonForSites = true;

	foreach($arPathes as $pathName):?>
	<tr id="bx-cal-opt-path-<?= $pathName?>"  <?if(!$commonForSites){echo'style="display:none;"';}?>>
		<td><label for="cal_<?=$pathName?>"><?= GetMessage("CAL_".strtoupper($pathName))?>:</label></td>
		<td>
			<input name="<?= $pathName?>" type="text" value="<?= htmlspecialcharsbx($SET[$pathName])?>" id="cal_<?= $pathName?>" size="60"/>
		</td>
	</tr>
	<?endforeach;?>

	<!-- Reserve meetings and video reserve meetings -->
	<tr class="heading"><td colSpan="2"><?= GetMessage('CAL_RESERVE_MEETING')?></td></tr>
	<tr>
		<td><label for="cal_rm_iblock_type"><?= GetMessage("CAL_RM_IBLOCK_TYPE")?>:</label></td>
		<td>
			<select name="rm_iblock_type" onchange="changeIblockList(this.value)">
				<option value=""><?= GetMessage('CAL_NOT_SET')?></option>
			<?foreach ($arIBTypes as $ibtype_id => $ibtype_name):?>
				<option value="<?= $ibtype_id?>" <?if($ibtype_id == $SET['rm_iblock_type']){echo ' selected="selected"';}?>><?= $ibtype_name?></option>
			<?endforeach;?>
			</select>
		</td>
	</tr>
	<tr>
		<td><label for="cal_rm_iblock_id"><?= GetMessage("CAL_RM_IBLOCK_ID")?>:</label></td>
		<td>
			<select id="cal_rm_iblock_id" name="rm_iblock_id">
<?if ($SET['rm_iblock_type']):?>
	<option value=""><?= GetMessage('CAL_NOT_SET')?></option>
	<?foreach ($arIB[$SET['rm_iblock_type']] as $iblock_id => $iblock):?>
		<option value="<?= $iblock_id?>"<? if($iblock_id == $SET['rm_iblock_id']){echo ' selected="selected"';}?>><?= $iblock?></option>
	<?endforeach;?>
<?else:?>
	<option value=""><?= GetMessage('CAL_NOT_SET')?></option>
<?endif;?>

			</select>
		</td>
	</tr>

	<?if (CModule::IncludeModule("video")):?>
	<tr>
		<td><label for="cal_vr_iblock_id"><?= GetMessage("CAL_VR_IBLOCK_ID")?>:</label></td>
		<td>
			<select id="cal_vr_iblock_id" name="vr_iblock_id"">
<?if ($SET['rm_iblock_type']):?>
	<option value=""><?= GetMessage('CAL_NOT_SET')?></option>
	<?foreach ($arIB[$SET['rm_iblock_type']] as $iblock_id => $iblock):?>
		<option value="<?= $iblock_id?>"<? if($iblock_id == $SET['vr_iblock_id']){echo ' selected="selected"';}?>><?= $iblock?></option>
	<?endforeach;?>
<?else:?>
	<option value=""><?= GetMessage('CAL_NOT_SET')?></option>
<?endif;?>
			</select>
		</td>
	</tr>
	<?endif?>
	<?endif?>


	<!-- Comments settings -->
	<tr class="heading"><td colSpan="2"><?= GetMessage('CAL_COMMENTS_SETTINGS')?></td></tr>
	<tr>
		<td align="right"><?= GetMessage("CAL_COMMENTS_FORUM")?>:</td>
		<td>
			<select name="calendar_forum_id">
				<option value="0">&nbsp;</option>
				<?foreach ($arForums as $key => $value):?>
					<option value="<?= $key ?>"<?= $SET['forum_id'] == $key ? " selected" : "" ?>><?=  $value?></option>
				<? endforeach?>
			</select>
		</td>
	</tr>
<?/*
	<tr>
		<td align="right"><?= GetMessage("CAL_COMMENTS_ALLOW_EDIT")?>:</td>
		<td>
			<input type="checkbox" name="calendar_comment_allow_edit" value="Y"<?= $SET['comment_allow_edit'] ? " checked" : "" ?> />
		</td>
	</tr>
	<tr>
		<td align="right"><?= GetMessage("CAL_COMMENTS_ALLOW_REMOVE")?>:</td>
		<td>
			<input type="checkbox" name="calendar_comment_allow_remove" value="Y"<?= $SET['comment_allow_remove'] ? " checked" : "" ?> />
		</td>
	</tr>
	<tr>
		<td align="right"><?= GetMessage('CAL_MAX_UPLOAD_FILES_IN_COMMENTS')?>:</td>
		<td><input type="text" size="40" value="<?= $SET['max_upload_files_in_comments']?>" name="calendar_max_upload_files_in_comments">
		</td>
	</tr>
*/?>
	<!-- END Comments settings -->



<?$tabControl->BeginNextTab();?>
	<tr class="">
		<td colspan="2" style="text-align: left;">
			<a class="bxco-add-type" href="javascript:void(0);" onclick="addType(); return false;" title="<?= GetMessage('CAL_ADD_TYPE_TITLE')?>"><i></i><span><?= GetMessage('CAL_ADD_TYPE')?></span></a>
		</td>
	</tr>
	<tr><td colspan="2" align="center">
<?
$APPLICATION->SetAdditionalCSS("/bitrix/js/calendar/cal-style.css");
$GLOBALS['APPLICATION']->AddHeadScript("/bitrix/js/calendar/cal-controlls.js");
?>
	<table id="bxcal_type_tbl" style="width: 650px;">
		<?
		$actionUrl = '/bitrix/admin/settings.php?mid=calendar&lang='.LANG;
		$arXML_ID = array();
		for ($i = 0, $l = count($arTypes); $i < $l; $i++):
			$type = $arTypes[$i];
			$arXML_ID[$type['XML_ID']] = true;
		?>
			<tr><td>
			<?= OutputTypeHtml($type)?>
			</td></tr>
		<?endfor;?>
	</table>
	</td></tr>

<?$tabControl->BeginNextTab();?>

<?$tabControl->Buttons();?>
	<input type="submit" class="adm-btn-save" name="Update" value="<?=GetMessage("MAIN_SAVE")?>" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>" />
	<input type="submit" name="Apply" value="<?=GetMessage("MAIN_APPLY")?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>">
	<?if(strlen($_REQUEST["back_url_settings"])>0):?>
		<input type="button" name="Cancel" value="<?=GetMessage("MAIN_OPT_CANCEL")?>" title="<?=GetMessage("MAIN_OPT_CANCEL_TITLE")?>" onclick="window.location='<?= htmlspecialcharsbx(CUtil::addslashes($_REQUEST["back_url_settings"]))?>'">
		<input type="hidden" name="back_url_settings" value="<?=htmlspecialcharsbx($_REQUEST["back_url_settings"])?>">
	<?endif?>
	<input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" onclick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("CAL_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>

<div id="edit_type_dialog" class="bxco-popup">
<form method="POST" name="caltype_dialog_form" id="caltype_dialog_form" action="<?= $APPLICATION->GetCurPage()?>?mid=<?= urlencode($mid)?>&amp;lang=<?=LANGUAGE_ID?>&amp;save_type=Y"  ENCTYPE="multipart/form-data">
	<?=bitrix_sessid_post()?>
	<input type="hidden"  name="type_new" id="type_new_inp" value="Y" size="32" />
	<table border="0" cellSpacing="0" class="bxco-popup-tbl">
		<tr>
			<td class="bxco-2-right">
				<label for="type_name_inp"><b><?= GetMessage('CAL_TYPE_NAME')?></b>:</label>
			</td>
			<td><input type="text"  name="type_name" id="type_name_inp" value="" size="32" /></td>
		</tr>
		<tr>
			<td class="bxco-2-right">
				<label for="type_xml_id_inp"><b><?= GetMessage('CAL_TYPE_XML_ID')?></b>:</label>
				<br>
				<span class="bxco-lbl-note"><?= GetMessage('CAL_ONLY_LATIN')?></span>
			</td>
			<td>
				<input type="hidden"  name="type_xml_id_hidden" id="type_xml_id_hidden_inp" value="" size="32" />
				<input type="text"  name="type_xml_id" id="type_xml_id_inp" value="" size="32" />
			</td>
		</tr>
		<tr>
			<td class="bxco-2-right"><label for="type_desc_inp"><?= GetMessage('CAL_TYPE_DESCRIPTION')?>:</label></td>
			<td><textarea name="type_desc" id="type_desc_inp" rows="3" cols="30" style="resize:none;"></textarea></td>
		</tr>
	</table>
</form>
</div>

<script>

var arIblocks = <?= CUtil::PhpToJsObject($arIB)?>;
function changeIblockList(value, index)
{
	if (null == index)
		index = 0;

	var
		i, j,
		arControls = [
			BX('cal_rm_iblock_id'),
			BX('cal_vr_iblock_id')
		];

	for (i = 0; i < arControls.length; i++)
	{
		if (arControls[i])
			arControls[i].options.length = 0;

		arControls[i].options[0] = new Option('<?= GetMessage('CAL_NOT_SET')?>', '');

		for (j in arIblocks[value])
			arControls[i].options[arControls[i].options.length] = new Option(arIblocks[value][j], j);
	}
}

function addType(oType)
{
	if (!window.BXCEditType)
	{
		window.arXML_ID = <?= CUtil::PhpToJsObject($arXML_ID)?>;
		window.BXCEditType = new BX.PopupWindow("BXCEditType", null, {
			autoHide: true,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			draggable: true,
			bindOnResize: false,
			closeByEsc : true,
			titleBar: {content: BX.create("span", {html: '<?= GetMessage("CAL_EDIT_TYPE_DIALOG")?>'})},
			closeIcon: { right : "12px", top : "10px"},
			className: 'bxc-popup-window',
			buttons: [
				new BX.PopupWindowButton({
					text: '<?= GetMessage("MAIN_SAVE")?>',
					className: "popup-window-button-accept",
					events: {click : function()
					{
						// Check form
						// Check name
						if (BX.util.trim(BX('type_name_inp').value) == '')
						{
							alert('<?= GetMessage('CAL_TYPE_NAME_WARN')?>');
							BX.focus(BX('type_xml_id_inp'));
							return;
						}

						// Check xml_id
						var bNew = BX('type_new_inp').value == 'Y', xmlId;
						if (bNew)
						{
							xmlId = BX.util.trim(BX('type_xml_id_inp').value);
							if (xmlId == '' || window.arXML_ID[xmlId] || xmlId.replace(new RegExp('[^a-z0-9_\-]', 'ig'), "") != xmlId)
							{
								alert('<?= GetMessage('CAL_TYPE_XML_ID_WARN')?>');
								BX.focus(BX('type_xml_id_inp'));
								return;
							}
						}
						else
						{
							xmlId = BX.util.trim(BX('type_xml_id_hidden_inp').value);
						}

						// Post
						BX.ajax.submit(BX('caltype_dialog_form'), function(result)
						{
							window.arXML_ID[xmlId] = true;
							if (bNew)
							{
								BX('bxcal_type_tbl').insertRow(-1).insertCell(-1).innerHTML = result;
							}
							else
							{
								var pCont = BX('type-cont-' + xmlId);
								if (pCont && pCont.parentNode)
									pCont.parentNode.innerHTML = result;
							}
						});
						window.BXCEditType.close();
					}}
				}),
				new BX.PopupWindowButtonLink({
					text: '<?= GetMessage("CAL_CLOSE")?>',
					className: "popup-window-button-link-cancel",
					events: {click : function(){window.BXCEditType.close();}}
				})
			],
			content: BX('edit_type_dialog')
		});
	}

	var bNew = !oType;
	BX('type_new_inp').value = bNew ? 'Y' : 'N';
	BX('type_name_inp').value = bNew ? '' : oType.NAME;
	BX('type_desc_inp').value = bNew ? '' : oType.DESCRIPTION;
	BX('type_xml_id_inp').value = bNew ? '' : oType.XML_ID;
	BX('type_xml_id_hidden_inp').value = bNew ? '' : oType.XML_ID;
	BX('type_xml_id_inp').disabled = !bNew;
	window.BXCEditType.show();
}

function delType(xml_id)
{
	if (confirm('<?= GetMessage('CAL_DELETE_CONFIRM')?>'))
	{
		BX.ajax.post('<?= $APPLICATION->GetCurPage()?>?mid=<?= urlencode($mid)?>&lang=<?=LANGUAGE_ID?>&save_type=Y&del_type=Y&type_xml_id=' + xml_id, {sessid: BX.bitrix_sessid()}, function()
		{
			var pCont = BX('type-cont-' + xml_id);
			if (pCont && pCont.parentNode)
				BX.cleanNode(pCont.parentNode, true);
		});
	}
}
</script>

<?
function OutputTypeHtml($type)
{
	$XML_ID = preg_replace("/[^a-zA-Z0-9_]/i", "", $type['XML_ID']);
	CCalendarSceleton::GetAccessHTML('calendar_type', 'bxec-calendar-type-'.$XML_ID);
?>
	<div class="bxcopt-type-cont" id="type-cont-<?= $XML_ID?>"">
		<div class="bxcopt-type-cont-title">
			<span class="bxcopt-type-title-label"><?= htmlspecialcharsbx($type['NAME'])?> [<?= $XML_ID?>]</span>
			<a href="javascript:void(0);" onclick="delType('<?= $XML_ID?>'); return false;"><?= GetMessage('CAL_DELETE')?></a>
			<a href="javascript:void(0);" onclick="addType(<?= CUtil::PhpToJsObject($type)?>); return false;"><?= GetMessage('CAL_CHANGE')?></a>
		</div>
		<? if(strlen($type['DESCRIPTION']) > 0):?>
			<span class="bxcopt-type-desc"><?= htmlspecialcharsbx($type['DESCRIPTION'])?></span>
		<? endif;?>
		<div class="bxcopt-type-access-cont">
			<span class="bxcopt-type-access-cont-title"><?= GetMessage('CAL_TYPE_PERMISSION_ACCESS')?>:</span>
			<div class="bxcopt-type-access-values-cont" id="type-access-values-cont<?= $XML_ID?>"></div>
			<a class="bxcopt-add-access-link" href="javascript:void(0);" id="type-access-link<?= $XML_ID?>"><?= GetMessage('CAL_ADD_ACCESS')?></a>
		</div>
<script>
BX = top.BX;
BX.ready(function()
{
	setTimeout(function(){
		top.accessNames = {};
		var code, arNames = <?= CUtil::PhpToJsObject(CCalendar::GetAccessNames())?>;
		for (code in arNames)
			top.accessNames[code] = arNames[code];

		top.BXCalAccess<?= $XML_ID?> = new top.ECCalendarAccess({
			bind: 'calendar-type-<?= $XML_ID?>',
			GetAccessName: function(code){return top.accessNames[code] || code;},
			inputName: 'cal_type_perm[<?= $XML_ID?>]',
			pCont: BX('type-access-values-cont<?= $XML_ID?>'),
			pLink: BX('type-access-link<?= $XML_ID?>'),
			delTitle: '<?= GetMessage("CAL_DELETE")?>',
			noAccessRights: '<?= GetMessage("CAL_NOT_SET")?>'
		});
		top.BXCalAccess<?= $XML_ID?>.SetSelected(<?= CUtil::PhpToJsObject($type['ACCESS'])?>);
	}, 100);
});
</script>
	</div>

<?
}

?>
