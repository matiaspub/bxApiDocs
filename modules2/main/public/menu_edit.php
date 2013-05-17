<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

if(!CModule::IncludeModule('fileman'))
	die();

if(!$USER->CanDoOperation('fileman_edit_menu_elements'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

CUtil::JSPostUnescape();

IncludeModuleLangFile(__FILE__);

$site = CFileMan::__CheckSite($site);
$DOC_ROOT = CSite::GetSiteDocRoot($site);

$io = CBXVirtualIo::GetInstance();

$path = $io->CombinePath("/", $path);

$arParsedPath = CFileMan::ParsePath(Array($site, $path), true, false, "", $logical == "Y");
$menufilename = $path;

$name = preg_replace("/[^a-z0-9_]/i", "", $_REQUEST["name"]);
$menufilename = $io->CombinePath($path, ".".$name.".menu.php");
$arPath_m = array($site, $menufilename);
$abs_path = $io->CombinePath($DOC_ROOT, $menufilename);

$strWarning = "";
$module_id = "fileman";
//delete menu file
if($_REQUEST["action"] == "delete" && check_bitrix_sessid())
{
	$success = false;
	if($io->FileExists($abs_path) && $USER->CanDoFileOperation('fm_delete_file', $arPath_m))
	{
		$f = $io->GetFile($abs_path);
		$arUndoParams = array(
			'module' => 'fileman',
			'undoType' => 'delete_menu',
			'undoHandler' => 'CFileman::UndoFileDelete',
			'arContent' => array(
				'site' => $site,
				'path' => $menufilename,
				'content' => $f->GetContents(),
				'perm' => CFileMan::FetchFileAccessPerm($arPath_m, true),
			)
		);

		if (COption::GetOptionInt("main", "disk_space") > 0)
		{
			$quota = new CDiskQuota();
			$quota->UpdateDiskQuota("file", $f->GetFileSize(), "delete");
		}

		$f->MarkWritable();
		$success = $io->Delete($abs_path);

		if(COption::GetOptionString($module_id, "log_menu", "Y")=="Y")
		{
			$mt = COption::GetOptionString("fileman", "menutypes", $default_value, $site);
			$mt = unserialize(str_replace("\\", "", $mt));
			$res_log['menu_name'] = $mt[$name];
			$res_log['path'] = substr($path, 1);
			CEventLog::Log(
				"content",
				"MENU_DELETE",
				"main",
				"",
				serialize($res_log)
			);
		}
		if($success)
		{
			$GLOBALS["APPLICATION"]->RemoveFileAccessPermission($arPath_m);

			CUndo::ShowUndoMessage(CUndo::Add($arUndoParams));
		}
	}
?>
<script bxrunfirst="true">
<?if($success):?>
	top.BX.reload('<?=CUtil::JSEscape($back_url);?>', true);
<?else:?>
	top.BX.closeWait();
	alert('<?=CUtil::JSEscape(GetMessage("pub_menu_edit_err_del").' '.$menufilename);?>');
<?endif;?>
</script>
<?
	die();
}

if($io->FileExists($abs_path) && strlen($new)<=0)
	$bEdit = true;
else
	$bEdit = false;

$only_edit = !$USER->CanDoOperation('fileman_add_element_to_menu') || !$USER->CanDoFileOperation('fm_create_new_file', $arPath_m);

/******* POST **********/
//проверим права на доступ в эту папку
if(!$USER->CanDoOperation('fileman_edit_existent_files') || !$USER->CanDoFileOperation('fm_edit_existent_file', $arPath_m) || (!$bEdit && $only_edit))
{
	$strWarning = GetMessage("ACCESS_DENIED");
}
else
{
	if($REQUEST_METHOD=="POST" && $_REQUEST['save'] == 'Y')
	{
		if (!is_array($ids)) $ids = array();

		$arValues = $_POST;

		$res = CFileMan::GetMenuArray($abs_path);

		$aMenuLinksTmp = $res["aMenuLinks"];
		$aMenuLinksTmp_ = Array();

		//соберем $aMenuLinksTmp из того что пришло с формы
		$aMenuSort = Array();
		for($i=0; $i<count($ids); $i++)
		{
			$num = $ids[$i];
			if (!isset($aMenuLinksTmp[$num-1]) && $only_edit)
				continue;

			if(${"del_".$num}=="Y" && !$only_edit)
				continue;

			$aMenuItem = Array($arValues["text_".$num], $arValues["link_".$num]);

			if ($arValues['additional_params_'.$num])
				$arAdditionalParams = @unserialize($arValues['additional_params_'.$num]);
			else
				$arAdditionalParams = array(array(), array());

			$aMenuItem = array_merge($aMenuItem, $arAdditionalParams);

			/*
			$arAddLinks = Array();
			$additional_link = $arValues["additional_link_".$num];
			$arAddLinksTmp = explode("\n", $additional_link);
			for($j=0; $j<count($arAddLinksTmp); $j++)
			{
				if(strlen(trim($arAddLinksTmp[$j]))>0)
					$arAddLinks[] = trim($arAddLinksTmp[$j]);
			}
			$aMenuItem[] = $arAddLinks;

			$arParams = Array();
			$param_cnt = IntVal(${"param_cnt_".$num});
			for($j=1; $j<=IntVal($param_cnt); $j++)
			{
				$param_name = trim($arValues["param_name_".$num."_".$j]);
				$param_value = trim($arValues["param_value_".$num."_".$j]);
				if(strlen($param_name)>0 || strlen($param_value)>0)
					$arParams[$param_name]=$param_value;
			}
			$aMenuItem[] = $arParams;
			*/

			$aMenuLinksTmp_[] = $aMenuItem;
			$aMenuSort[] = IntVal(${"sort_".$num});
		}

		$aMenuLinksTmp = $aMenuLinksTmp_;

		for($i=0; $i<count($aMenuSort)-1; $i++)
			for($j=$i+1; $j<count($aMenuSort); $j++)
				if($aMenuSort[$i]>$aMenuSort[$j])
				{
					$tmpSort = $aMenuLinksTmp[$i];
					$aMenuLinksTmp[$i] = $aMenuLinksTmp[$j];
					$aMenuLinksTmp[$j] = $tmpSort;

					$tmpSort = $aMenuSort[$i];
					$aMenuSort[$i] = $aMenuSort[$j];
					$aMenuSort[$j] = $tmpSort;
				}

		//теперь $aMenuLinksTmp прямо в таком готовом виде, что хоть меню рисуй :-)
		if (!check_bitrix_sessid())
		{
			$strWarning = GetMessage('MENU_EDIT_SESSION_EXPIRED');
		}
		else
		{
			$f = $io->GetFile($abs_path);

			if ($io->FileExists($abs_path))
				$arUndoParams = array(
					'module' => 'fileman',
					'undoType' => 'edit_menu',
					'undoHandler' => 'CFileman::UndoEditFile',
					'arContent' => array(
						'absPath' => $abs_path,
						'content' => $f->GetContents()
					)
				);
			else
				$arUndoParams = array(
					'module' => 'fileman',
					'undoType' => 'edit_menu',
					'undoHandler' => 'CFileman::UndoNewFile',
					'arContent' => array(
						'absPath' => $abs_path,
						'path' => $menufilename,
						'site' => $site
					)
				);

			CFileMan::SaveMenu(Array($site, $menufilename), $aMenuLinksTmp, $sMenuTemplateTmp);

			if(COption::GetOptionString($module_id, "log_menu", "Y")=="Y")
			{
				$mt = COption::GetOptionString("fileman", "menutypes", false, $site);
				$mt = unserialize(str_replace("\\", "", $mt));
				$res_log['menu_name'] = $mt[$name];
				$res_log['path'] = substr($path, 1);
				if ($bEdit)
					CEventLog::Log(
						"content",
						"MENU_EDIT",
						"main",
						"",
						serialize($res_log)
					);
				else
					CEventLog::Log(
						"content",
						"MENU_ADD",
						"main",
						"",
						serialize($res_log)
					);
			}
			if($e = $APPLICATION->GetException())
				$strWarning = $e->GetString();

			if($strWarning == '')
			{
				CUndo::ShowUndoMessage(CUndo::Add($arUndoParams));
				$bEdit = true;
		?>
<script bxrunfirst="true">
top.BX.WindowManager.Get().Close();
top.BX.showWait();
top.BX.reload('<?=CUtil::JSEscape($back_url);?>', true);
</script>
<?
				die();
			}
		}
	}
}
/******* /POST **********/

$arMenuTypes = GetMenuTypes($site);

$TITLE = GetMessage("MENU_EDIT_TITLE_".($bEdit ? "EDIT" : "ADD"));
$DESCRIPTION = str_replace(
	array("#TYPE#", "#DIR#"),
	array(strlen($arMenuTypes[$name]) > 0 ? $arMenuTypes[$name] : $name, $path),
	GetMessage("MENU_EDIT_DESCRIPTION_".($bEdit ? "EDIT" : "ADD"))
);

$obJSPopup = new CJSPopup('',
	array(
		'TITLE' => GetMessage('MENU_EDIT_TITLE'),
		'ARGS' => "lang=".urlencode($_GET["lang"])."&site=".urlencode($_GET["site"])."&back_url=".urlencode($_GET["back_url"])."&path=".urlencode($_GET["path"])."&name=".urlencode($_GET["name"])
	)
);

// ======================== Show titlebar ============================= //
$obJSPopup->ShowTitlebar();
?>
<script src="/bitrix/js/main/dd.js" type="text/javascript"></script>

<?
// ======================== Show description ============================= //
$obJSPopup->StartDescription('bx-core-edit-menu');
?>
<p class="title"><?=$TITLE?></p>
<p class="note"><?=$DESCRIPTION?>
</p><p>
<a href="/bitrix/admin/fileman_menu_edit.php?<?="lang=".urlencode($_GET["lang"])."&site=".urlencode($_GET["site"])."&back_url=".urlencode($_GET["back_url"])."&path=".urlencode($_GET["path"])."&name=".urlencode($_GET["name"])?>"><?=GetMessage('MENU_EDIT_OLD_STYLE')?></a>
</p>
<?
if($strWarning <> "")
	$obJSPopup->ShowValidationError($strWarning);

?>

<?
// ======================== Show content ============================= //
$obJSPopup->StartContent();

if($bEdit && strlen($strWarning)<=0)
{
	$res = CFileMan::GetMenuArray($abs_path);
	$aMenuLinksTmp = $res["aMenuLinks"];
}

if(!is_array($aMenuLinksTmp))
	$aMenuLinksTmp = Array();
?>
	<input type="hidden" name="save" value="Y" />
	<table border="0" cellpadding="0" cellspacing="0" class="bx-width100" class="menu-table">
	<thead>
		<tr class="section">
			<td width="0"></td>
			<td width="50%"><b><?echo GetMessage("MENU_EDIT_NAME")?></b></td>
			<td width="50%"><b><?echo GetMessage("MENU_EDIT_LINK")?></b></td>
			<td width="0"></td>
			<td width="0"></td>
			<td width="0"></td>
			<td width="0"></td>
		</tr>
	</thead>
	</table>

	<div id="bx_menu_layout" class="bx-menu-layout"><?
	$itemcnt = 0;
	for($i=1; $i<=count($aMenuLinksTmp); $i++):
		$itemcnt++;
		$aMenuLinksItem = $aMenuLinksTmp[$i-1];
	?><div class="bx-menu-placement" id="bx_menu_placement_<?=$i?>"><div class="bx-edit-menu-item" id="bx_menu_row_<?=$i?>"><table border="0" cellpadding="0" cellspacing="0" class="bx-width100 internal" class="menu-table"><tbody>
	<tr>

		<td><input type="hidden" name="sort_<?=$i?>" value="<?echo $i*10?>" />
		<input type="hidden" name="ids[]" value="<?=$i?>" />
		<input type="hidden" name="del_<?=$i?>" value="N" />
		<input type="hidden" name="additional_params_<?=$i?>" value="<?=htmlspecialcharsex(serialize(array($aMenuLinksItem[2], $aMenuLinksItem[3], $aMenuLinksItem[4])))?>" />
		<span class="rowcontrol drag" title="<?=GetMessage('MENU_EDIT_TOOLTIP_DRAG')?>"></span>
		</td>
		</td><td>
			<div onmouseout="rowMouseOut(this)" onmouseover="rowMouseOver(this)" class="edit-field view-area" id="view_area_text_<?=$i?>" onclick="editArea('text_<?=$i?>')" title="<?=GetMessage('MENU_EDIT_TOOLTIP_TEXT_EDIT')?>"><?=strlen($aMenuLinksItem[0]) > 0 ? htmlspecialcharsbx($aMenuLinksItem[0]) : GetMessage('MENU_EDIT_JS_NONAME')?></div>
			<div class="edit-area" id="edit_area_text_<?=$i?>" style="display: none;"><input type="text" style="width: 220px;" name="text_<?echo $i?>" value="<?=htmlspecialcharsbx($aMenuLinksItem[0])?>" onblur="viewArea('text_<?=$i?>')" />
</div>
		</td>
		<td>
			<div onmouseout="rowMouseOut(this)" onmouseover="rowMouseOver(this)" class="edit-field view-area" id="view_area_link_<?=$i?>" onclick="editArea('link_<?=$i?>')" title="<?=GetMessage('MENU_EDIT_TOOLTIP_LINK_EDIT')?>"><?=strlen($aMenuLinksItem[1]) > 0 ? htmlspecialcharsbx($aMenuLinksItem[1]) : GetMessage('MENU_EDIT_JS_NONAME')?></div>
			<div class="edit-area" id="edit_area_link_<?=$i?>" style="display: none;"><input type="text" style="width: 220px;" name="link_<?echo $i?>" value="<?=htmlspecialcharsbx($aMenuLinksItem[1])?>" onblur="viewArea('link_<?=$i?>')" /></div>
		</td>
		<td>
<?
CAdminFileDialog::ShowScript(
	Array
	(
		"event" => "OpenFileBrowserWindFile_".$i,
		"arResultDest" => Array("FUNCTION_NAME" => 'setLink'),
		"arPath" => Array("SITE" => $site, 'PATH' => $aMenuLinksItem[1]),
		"select" => 'F',// F - file only, D - folder only, DF - files & dirs
		"operation" => 'O',// O - open, S - save
		"showUploadTab" => false,
		"showAddToMenuTab" => false,
		"fileFilter" => 'php, html',
		"allowAllFiles" => true,
		"SaveConfig" => true
	)
);
?>
			<span onclick="if (!GLOBAL_bDisableActions) {currentLink = '<?=$i?>'; OpenFileBrowserWindFile_<?=$i?>();}" class="rowcontrol folder" title="<?=GetMessage('MENU_EDIT_TOOLTIP_FD')?>"></span>
		</td>
		<td><span onclick="menuMoveUp(<?=$i?>)" class="rowcontrol up" style="visibility: <?=($i == 1 ? 'hidden' : 'visible')?>" title="<?=GetMessage('MENU_EDIT_TOOLTIP_UP')?>"></span></td>
		<td><span onclick="menuMoveDown(<?=$i?>)" class="rowcontrol down" style="visibility: <?=($i == count($aMenuLinksTmp) ? 'hidden' : 'visible')?>" title="<?=GetMessage('MENU_EDIT_TOOLTIP_DOWN')?>"></span></td>
		<td><span onclick="menuDelete(<?=$i?>)" class="rowcontrol delete" title="<?=GetMessage('MENU_EDIT_TOOLTIP_DELETE')?>"></span></td>
	</tr>
	</tbody></table></div></div><?endfor?>
</div>
	<?if(!$only_edit):?><br /><input type="button" onClick="menuAdd()" value="<?echo GetMessage("MENU_EDIT_ADD_ITEM")?>" /><?endif;?>
	<input type="hidden" name="itemcnt" value="<?echo $itemcnt?>" />
<script type="text/javascript">
var currentLink = -1;
var currentRow = null;

var GLOBAL_bDisableActions = false;
var GLOBAL_bDisableDD = false;

var jsMenuMess = {
	noname: '<?=CUtil::JSEscape(GetMessage('MENU_EDIT_JS_NONAME'))?>'
}

function setLink(filename, path, site)
{
	<?echo $obJSPopup->jsPopup?>.GetForm()['link_' + currentLink].value = ((path == '' || path == '/') ? '/' : path + '/') + filename;
	editArea('link_' + currentLink, true);
	viewArea('link_' + currentLink, true);
}

function menuCheckIcons()
{
	var obLayout = BX('bx_menu_layout');

	for (var i = 0, num = obLayout.childNodes.length; i < num; i++)
	{
		if (
			obLayout.childNodes[i].tagName
			&& obLayout.childNodes[i].tagName == 'DIV'
			&& obLayout.childNodes[i].className == 'bx-menu-placement'
		)
		{
			var obTbody = obLayout.childNodes[i].firstChild.firstChild.tBodies[0];

			obTbody.rows[0].cells[4].firstChild.style.visibility = (i == 0 ? 'hidden' : 'visible');
			obTbody.rows[0].cells[5].firstChild.style.visibility = (i == num-1 ? 'hidden' : 'visible');
			obTbody.rows[0].cells[0].firstChild.value = 10 * (i+1);
		}
	}
}

function menuMoveUp(i)
{
	if (GLOBAL_bDisableActions)
		return;

	var obRow = BX('bx_menu_row_' + i);
	var obPlacement = obRow.parentNode;

	var index = obPlacement.id.substring(18);
	if (1 >= index)
		return;

	var obNewPlacement = obPlacement.previousSibling;
	var obSwap = obNewPlacement.firstChild;

	obPlacement.removeChild(obRow);
	obNewPlacement.removeChild(obSwap);
	obPlacement.appendChild(obSwap);
	obNewPlacement.appendChild(obRow);

	setCurrentRow(obRow);
	menuCheckIcons();
}

function menuMoveDown(i)
{
	if (GLOBAL_bDisableActions)
		return;

	var obRow = BX('bx_menu_row_' + i);
	var obPlacement = obRow.parentNode;
	var obNewPlacement = obPlacement.nextSibling;
	if (null == obNewPlacement)
		return;

	var obSwap = obNewPlacement.firstChild;

	obPlacement.removeChild(obRow);
	obNewPlacement.removeChild(obSwap);
	obPlacement.appendChild(obSwap);
	obNewPlacement.appendChild(obRow);

	setCurrentRow(obRow);
	menuCheckIcons();
}

function menuDelete(i)
{
	if (GLOBAL_bDisableActions)
		return;

	var obInput = <?echo $obJSPopup->jsPopup?>.GetForm()['del_' + i];
	var obPlacement = BX('bx_menu_row_' + i).parentNode;

	obInput.value = 'Y';

	if (obPlacement.firstChild == currentRow) currentRow = null;

	obPlacement = BX.remove(obPlacement);
	menuCheckIcons();
}

function menuAdd()
{
	var obCounter = <?echo $obJSPopup->jsPopup?>.GetForm().itemcnt;
	var nums = parseInt(obCounter.value);
	obCounter.value = ++nums;

	var obPlacement = BX.create('DIV', {props: {className: 'bx-menu-placement', id: 'bx_menu_placement_' + nums}});

	document.getElementById('bx_menu_layout').appendChild(obPlacement);

	var obRow = BX.create('DIV', {props: {className: 'bx-edit-menu-item', id: 'bx_menu_row_' + nums}});
	obPlacement.appendChild(obRow);

	<?
	ob_start();
	CAdminFileDialog::ShowScript(
		Array
		(
			"event" => "OpenFileBrowserWindFile____NUMS___",
			"arResultDest" => Array("FUNCTION_NAME" => 'setLink'),
			"arPath" => Array("SITE" => $site, 'PATH' => $path),
			"select" => 'F',// F - file only, D - folder only, DF - files & dirs
			"operation" => 'O',// O - open, S - save
			"showUploadTab" => false,
			"showAddToMenuTab" => false,
			"fileFilter" => 'php, html',
			"allowAllFiles" => true,
			"SaveConfig" => true
		)
	);
	$out = ob_get_contents();
	ob_end_clean();
	$out = trim($out);
	$unscript_pos = strpos($out, '</script>');
	$out = substr($out, 8, $unscript_pos-8);
	$out = trim($out);

	$out = CUtil::JSEscape($out);
	$out = str_replace('___NUMS___', "' + nums + '", $out);
	echo 'eval(\''.$out.'\');';
?>

	var arCellsHTML = [
		'<span class="rowcontrol drag" title="<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_DRAG'))?>"></span>',
		getAreaHTML('text_' + nums, '', '<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_TEXT_EDIT'))?>'),
		getAreaHTML('link_' + nums, '', '<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_LINK_EDIT'))?>'),
		'<span onclick="if (!GLOBAL_bDisableActions) {currentLink = \'' + nums + '\'; OpenFileBrowserWindFile_' + nums + '();}" class="rowcontrol folder" title="<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_FD'))?>"></span>',
		'<span onclick="menuMoveUp(' + nums + ')" class="rowcontrol up" style="visibility: ' + (nums == 1 ? 'hidden' : 'visible') + '" title="<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_UP'))?>"></span>',
		'<span onclick="menuMoveDown(' + nums + ')" class="rowcontrol down" style="visibility: hidden" title="<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_DOWN'))?>"></span>',
		'<span onclick="menuDelete(' + nums + ')" class="rowcontrol delete" title="<?=CUtil::JSEscape(GetMessage('MENU_EDIT_TOOLTIP_DELETE'))?>"></span>'
	];

	var row_content = '<table border="0" cellpadding="0" cellspacing="0" class="bx-width100 internal" class="menu-table"><tbody><tr>';

	for (var i = 0; i < arCellsHTML.length; i++)
		row_content += '<td>' + arCellsHTML[i] + '</td>';

	row_content += '</tr></tbody></table>';

	obRow.innerHTML = row_content;

	var arInputs = [
		['ids[]', nums],
		['del_' + nums, 'N'],
		['sort_' + nums, 2 * nums * 10]
	];

	for (var i = 0; i<arInputs.length; i++)
	{
		var obInput = BX.create('INPUT', {
			props: {type: 'hidden', name: arInputs[i][0], value: arInputs[i][1]}
		});

		obInput.value = arInputs[i][1];
		var obFirstCell = obRow.firstChild.tBodies[0].rows[0].cells[0];
		obFirstCell.insertBefore(obInput, obFirstCell.firstChild);
	}

	jsDD.registerDest(obPlacement);

	obRow.onbxdragstart = BXDD_DragStart;
	obRow.onbxdragstop = BXDD_DragStop;
	obRow.onbxdraghover = BXDD_DragHover;

	jsDD.registerObject(obRow);

	setCurrentRow(nums);
	menuCheckIcons();
}

function getAreaHTML(area, value, title)
{
	if (null === value) value = '';

	return '<div onmouseout="rowMouseOut(this)" onmouseover="rowMouseOver(this)" class="edit-field view-area" ' + /*style="width: 220px; padding: 2px; display: block; border: 1px solid white; cursor: text; -moz-box-sizing: border-box; background-position: right center; background-repeat: no-repeat;"*/' id="view_area_' + area + '" onclick="editArea(\'' + area + '\')" title="' + title + '">' + (value ? value : jsMenuMess.noname) + '</div>' +
			'<div class="edit-area" id="edit_area_' + area + '" style="display: none;"><input type="text" style="width: 220px;" name="' + area + '" value="' + value + '" onblur="viewArea(\'' + area + '\')" /></div>';
}

var currentEditingRow = null;

function editArea(area, bSilent)
{
	if (GLOBAL_bDisableActions)
		return;

	jsDD.Disable();
	GLOBAL_bDisableDD = true;

	jsDD.allowSelection();
	l = BX('bx_menu_layout');
	l.ondrag = l.onselectstart = null;
	l.style.MozUserSelect = '';

	if (null == bSilent) bSilent = false;

	var obEditArea = BX('edit_area_' + area);
	var obViewArea = BX('view_area_' + area);

	obEditArea.style.display = 'block';
	obViewArea.style.display = 'none';

	if (!bSilent)
	{
		obEditArea.firstChild.focus();

		if (BX.browser.IsIE())
			setTimeout(function () {setCurrentRow(obViewArea.parentNode.parentNode.parentNode.parentNode.parentNode)}, 30);
		else
			setCurrentRow(obViewArea.parentNode.parentNode.parentNode.parentNode.parentNode);
	}

	return obEditArea;
}

function viewArea(area, bSilent)
{
	if (GLOBAL_bDisableActions)
		return;

	jsDD.Enable();
	GLOBAL_bDisableDD = false;

	l = BX('bx_menu_layout');
	l.ondrag = l.onselectstart = BX.False;
	l.style.MozUserSelect = 'none';

	if (null == bSilent) bSilent = false;

	var obEditArea = BX('edit_area_' + area);
	var obViewArea = BX('view_area_' + area);

	obEditArea.firstChild.value = BX.util.trim(obEditArea.firstChild.value);

	obViewArea.innerHTML = '';
	BX.adjust(obViewArea, {text:obEditArea.firstChild.value.length > 0 ? obEditArea.firstChild.value : jsMenuMess.noname})

	obEditArea.style.display = 'none';
	obViewArea.style.display = 'block';

	currentEditingRow = null;
	setCurrentRow(obViewArea.parentNode.parentNode.parentNode.parentNode.parentNode);

	return obViewArea;
}

function setCurrentRow(i)
{
	i = BX(i);

	if (null != currentRow) BX.removeClass(currentRow, 'bx-menu-current-row')

	BX.addClass(i, 'bx-menu-current-row');
	currentRow = i;
}

function rowMouseOut(obArea)
{
	obArea.className = 'edit-field view-area';
	obArea.style.backgroundColor = '';
}

function rowMouseOver (obArea)
{
	if (GLOBAL_bDisableActions || jsDD.bPreStarted)
		return;

	//obArea.className = 'edit-field-active view-area';
	//obArea.style.backgroundColor = 'white';
}

/* DD handlers */
function BXDD_DragStart()
{
	if (GLOBAL_bDisableDD)
		return false;

	this.BXOldPlacement = this.parentNode;

	var id = this.id.substring(12);
	rowMouseOut(viewArea('link_' + id));
	rowMouseOut(viewArea('text_' + id));

	GLOBAL_bDisableActions = true;

	return true;
}

function BXDD_DragStop()
{
	this.BXOldPlacement = false;

	setTimeout('GLOBAL_bDisableActions = false', 50);

	return true;
}

function BXDD_DragHover(obPlacement, x, y)
{
	if (GLOBAL_bDisableDD)
		return false;

	if (obPlacement == this.BXOldPlacement)
		return false;

	var obSwap = obPlacement.firstChild;

	this.BXOldPlacement.removeChild(this);
	obPlacement.removeChild(obSwap);
	this.BXOldPlacement.appendChild(obSwap);
	obPlacement.appendChild(this);

	this.BXOldPlacement = obPlacement;

	menuCheckIcons();

	return true;
}

BX.ready(function ()
{
	jsDD.Reset();

<?
for($i=1; $i<=count($aMenuLinksTmp); $i++):
?>
	jsDD.registerDest(BX('bx_menu_placement_<?=$i?>'));

	var obEl = BX('bx_menu_row_<?=$i?>');
	obEl.onbxdragstart = BXDD_DragStart;
	obEl.onbxdragstop = BXDD_DragStop;
	obEl.onbxdraghover = BXDD_DragHover;
	jsDD.registerObject(obEl);
<?
endfor;
?>
	jsDD.registerContainer(BX.WindowManager.Get().GetContent());
	l = BX('bx_menu_layout');
	l.ondrag = l.onselectstart = BX.False;
	l.style.MozUserSelect = 'none';
});
</script>
<?
// ======================== Show buttons ============================= //
$obJSPopup->ShowStandardButtons();
?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>