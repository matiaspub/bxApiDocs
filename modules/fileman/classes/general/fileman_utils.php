<?
IncludeModuleLangFile(__FILE__);
class CFilemanUtils
{
	public static function InitScript($Params)
	{
		CUtil::InitJSCore(array('ajax', 'window'));

		$arLangArray = array();
		if ($Params['initSearch'])
			$arLangArray[] = 'search';

		if ($Params['initServerAccess'])
			$arLangArray[] = 'server_access';

		if ($Params['initCopy'])
			$arLangArray[] = 'copy';

		if ($Params['initPack'])
			$arLangArray[] = 'pack';

		$arSites = Array();
		$dbSitesList = CSite::GetList($b = "SORT", $o = "asc");
		while($arSite = $dbSitesList->GetNext())
		{
			$arSites[] = array(
				"id" => $arSite["ID"],
				"dir" => $arSite["DIR"],
				"text" => '['.$arSite["ID"].'] '.$arSite["~NAME"],
				"current" => $arSite["ID"] == $Params['site']
			);
		}

		$arArcTypes = array();
		$arTypesTmp = CBXArchive::GetAvailableFormats();

		foreach ($arTypesTmp as $key => $value)
		{
			$arArcTypes[]	=	array(
					"id"	=>	$key,
					"text"	=> 	ToUpper($key)
				);
		}

		?>
		<script type="text/javascript" src="/bitrix/js/fileman/fileman_utils.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/fileman/fileman_utils.js')?>"></script>

		<script>
		<?CFilemanUtils::AppendLangMessages($arLangArray);?>

		<?if ($Params['initSearch']):?>
		// * * * Search / replace * * *
		var oSearchDialog = new BX.CAdminDialog({
			title : '<?= GetMessage('FILEMAN_SEARCH')?>',
			height: 300,
			width: 570,
			resizable: false
		});

		oSearchDialog.SetButtons([
			new BX.CWindowButton(
			{
				title: "<?= GetMessage("FM_UTIL_FIND")?>",
				id: 'search',
				name: 'search',
				className: 'adm-btn-save',
				action: function(){window.oFMSearch.Search();}
			}),
			new BX.CWindowButton(
			{
				title: "<?= GetMessage("FM_UTIL_COUNT")?>",
				id: 'count',
				name: 'count',
				action: function(){window.oFMSearch.Count()}
			}),
			oSearchDialog.btnCancel
		]);

		window.SearchReplaceRun = function(path, bSearch, ssess, lastValues)
		{
			BX.addClass(oSearchDialog.PARTS.CONTENT, "bx-fm-dialog-tabed");
			oSearchDialog.Show();
			oSearchDialog.adjustSizeEx();
			oSearchDialog.PARTS.CONTENT_DATA.style.height = 'auto';

			if (!window.oFMSearch || !window.oFMSearch.bInited)
			{
				window.oFMSearch = new BXFMSearch(
				{
					oSearchDialog: oSearchDialog,
					lang: '<?= LANGUAGE_ID?>',
					site: '<?= CUtil::JSEscape($Params['site'])?>',
					sessid_get: '<?= bitrix_sessid_get()?>',
					viewMsFilePath: '<?= CUtil::JSEscape($Params['viewMsFilePath'])?>',
					viewMsFolderPath: '<?= CUtil::JSEscape($Params['viewMsFolderPath'])?>',
					dateFormat: '<?= CLang::GetDateFormat("SHORT")?>',
					oUserConfig: <?= CUtil::PhpToJSObject(CFilemanSearch::GetConfig())?>,
					arLastPathes: <?= CUtil::PhpToJSObject(CFilemanUtils::GetLastPathes())?>,
					arSites: <?= CUtil::PhpToJSObject($arSites)?>
				});
			}

			window.oFMSearch.OnOpen({
				path: path,
				bSearch: bSearch,
				ssess: ssess,
				lastValues: lastValues || false
			});
		};
		<?endif;?>

		<?if ($Params['initCopy']):?>
		// * * * Copy / Move * * *
		var oCopyDialog = new BX.CDialog({
			title : 'copy-move',
			height: 160,
			width: 500,
			resizable: false
		});

		oCopyDialog.SetButtons([
			new BX.CWindowButton(
			{
				title: "ok",
				id: 'ok',
				name: 'ok',
				action: function(){window.oBXFMCopy.Process();}
			}),
			oCopyDialog.btnCancel
		]);

		window.CopyMoveRun = function(arFiles, bCopy)
		{
			oCopyDialog.Show();

			if (!window.oBXFMCopy || !window.oBXFMCopy.bInited)
			{
				window.oBXFMCopy = new BXFMCopy(
				{
					oCopyDialog: oCopyDialog,
					lang: '<?= LANGUAGE_ID?>',
					site: '<?= CUtil::JSEscape($Params['site'])?>',
					sessid_get: '<?= bitrix_sessid_get()?>',
					viewMsFilePath: '<?= CUtil::JSEscape($Params['viewMsFilePath'])?>',
					viewMsFolderPath: '<?= CUtil::JSEscape($Params['viewMsFolderPath'])?>',
					oUserConfig: <?= CUtil::PhpToJSObject(CFilemanCopy::GetConfig())?>,
					arLastPathes: <?= CUtil::PhpToJSObject(CFilemanUtils::GetLastPathes())?>,
					arSites: <?= CUtil::PhpToJSObject($arSites)?>
				});
			}

			window.oBXFMCopy.OnOpen({
				path: BX('quick_path').value,
				arFiles: arFiles,
				bCopy: !!bCopy,
				bSearch: <?= ($_GET['search'] == "Y" ? 'true' : 'false')?>,
				ssess: "<?= CFilemanSearch::SecureSearchSess($_GET['ssess'])?>"
			});
		};
		<?endif;?>

		<?
		if ($Params['initPack']):?>
		// * * * Pack / Unpack * * *
		var oPackDialog = new BX.CDialog(
		{
			title : 'pack-unpack',
			height: 160,
			width: 500,
			resizable: false
		});

		oPackDialog.SetButtons([
			new BX.CWindowButton(
			{
				title: "ok",
				id: 'ok-pack',
				name: 'ok',
				action: function(){window.oBXFMPack.Process();}
			}),
			new BX.CWindowButton(
			{
				title: "cancel",
				id: 'cancel-pack',
				name: 'cancel'
			})
		]);

		window.PackUnpackRun = function(arFiles, bPack, qPath)
		{
			oPackDialog.Show();

			if (!window.oBXFMPack || !window.oBXFMPack.bInited)
			{
				window.oBXFMPack = new BXFMPack(
				{
					oPackDialog: oPackDialog,
					lang: '<?= LANGUAGE_ID?>',
					site: '<?= CUtil::JSEscape($Params['site'])?>',
					sessid_get: '<?= bitrix_sessid_get()?>',
					viewMsFilePath: '<?= CUtil::JSEscape($Params['viewMsFilePath'])?>',
					viewMsFolderPath: '<?= CUtil::JSEscape($Params['viewMsFolderPath'])?>',
					arLastPathes: <?= CUtil::PhpToJSObject(CFilemanUtils::GetLastPathes())?>,
					arSites: <?= CUtil::PhpToJSObject($arSites)?>,
					//archive types
					arTypes: <?= CUtil::PhpToJSObject($arArcTypes)?>
				});
			}

			window.oBXFMPack.OnOpen({
				path: BX('quick_path').value,
				arFiles: arFiles,
				bPack: !!bPack,
				bSearch: <?= ($_GET['search'] == "Y" ? 'true' : 'false')?>,
				ssess: "<?= CFilemanSearch::SecureSearchSess($_GET['ssess'])?>"
			});
		};
		<?endif;?>

		// Init hot keys
		BX.bind(BX.browser.IsIE() ? document.body : window, "keydown", function(e)
		{
			if (!e)
				e = window.event;

			<?if ($Params['initSearch']):?>
			if (e.ctrlKey && !e.shiftKey && !e.altKey && e.keyCode == 70)
			{
				var js = BX('btn_fileman_search').getAttribute('href');
				BX.evalGlobal(js.substr('javascript: '.length));
				return BX.PreventDefault(e);
			}
			<?endif;?>
		});
		</script>
		<?
	}

	public static function AppendLangMessages($Config)
	{
		$arLangMess = array();
		if (in_array('search', $Config))
		{
			$arLangMess = array_merge($arLangMess, array(
				'Count' => 'FM_UTIL_COUNT',
				'Counted' => 'FM_UTIL_COUNTED',
				'CountedFiles' => 'FM_UTIL_COUNTED_FILES',
				'SearchProgress' => 'FM_UTIL_SEARCH_PROGRESS',
				'ShowRes' => 'FM_UTIL_SHOW_RES',
				'Stop' => 'FM_UTIL_STOP_SEARCH',
				'SearchEnded' => 'FM_UTIL_SEARCH_ENDED',
				'Path' => 'FM_UTIL_SEARCH_PATH',
				'Size' => 'FM_UTIL_SEARCH_SIZE',
				'Date' => 'FM_UTIL_SEARCH_DATE',
				'CountProgress' => 'FM_UTIL_COUNT_PROGRESS',
				'CountEnded' => 'FM_UTIL_COUNT_ENDED',
				'Find' => 'FM_UTIL_FIND',
				'Replace' => 'FM_UTIL_REPLACE',
				'ReplProgress' => 'FM_UTIL_REPLACE_PROGRESS',
				'ReplEnded' => 'FM_UTIL_REPLACE_ENDED',
				'ReplCounted' => 'FM_UTIL_REPLACE_COUNTED',
				'ReplCountInFiles' => 'FM_UTIL_REPLACE_COUNT_IN_FILES',
				'ReplShowRes' => 'FM_UTIL_REPLACE_SHOW_RES',
				'ReplInFileTitle' => 'FM_UTIL_REPLACE_IN_FILE_TITLE',
				'ReplaceConfirm' => 'FM_UTIL_REPLACE_CONFIRM',
				'ReplacePhraseWarn' => 'FM_UTIL_REPLACE_WARN',
				'SearchInFileTitle' => 'FM_UTIL_SEARCH_IN_FILE_TITLE',
				'CountLimitWarn' => 'FM_UTIL_RES_COUNT_LIMIT_WARN',
			));
		}

		if (in_array('server_access', $Config))
		{
			$arLangMess = array_merge($arLangMess, array(
				'Ok' => 'FM_SA_OK',
				'InProcess' => 'FM_SA_IN_PROC',
				'Stop' => 'FM_UTIL_STOP_SEARCH',
				'StopTitle' => 'FM_SA_STOP_TITLE',
				'Return' => 'FM_SA_RETURN',
				'ReturnTitle' => 'FM_SA_RETURN_TITLE',
				'Stoped' => 'FM_SA_STOPED',
				'Error' => 'FM_SA_ERROR',
			));
		}

		if (in_array('copy', $Config))
		{
			$arLangMess = array_merge($arLangMess, array(
				'Copy' => 'FM_UTIL_COPY',
				'Move' => 'FM_UTIL_MOVE',
				'CopyTitle' => 'FM_UTIL_COPY_TITLE',
				'MoveTitle' => 'FM_UTIL_MOVE_TITLE',
				'More' => 'FM_UTIL_MORE',
				'NoFolder' => 'FM_UTIL_NO_FOLDER',
				'NoFolderNoAccess' => 'FM_UTIL_NO_FOLDER_NO_ACCESS',
				'FileExist' => 'FM_UTIL_FILE_EXIST',
				'Replace' => 'FM_COPY_REPLACE',
				'Rename' => 'FM_COPY_RENAME',
				'Skip' => 'FM_COPY_SKIP',
				'ToAll' => 'FM_COPY_TO_ALL',
				'FileExistTitle' => 'FM_UTIL_FILE_EXIST_1',
				'FolderExistTitle' => 'FM_UTIL_FILE_EXIST_2',
				'RenameTitle' => 'FM_COPY_RENAME_TITLE'
			));
		}

		if (in_array('pack', $Config))
		{
			$arLangMess = array_merge($arLangMess, array(
				'Pack' => 'FM_UTIL_PACK',
				'Unpack' => 'FM_UTIL_UNPACK',
				'PackTitle' => 'FM_UTIL_PACK_TITLE',
				'UnpackTitle' => 'FM_UTIL_UNPACK_TITLE',
				'UnpackError' => 'FM_UTIL_UNPACK_ERROR',
				'PackError' => 'FM_UTIL_PACK_ERROR',
				'More' => 'FM_UTIL_MORE',
				'NoFolder' => 'FM_UTIL_NO_FOLDER',
				'NoFolderNoAccess' => 'FM_UTIL_NO_FOLDER_NO_ACCESS',
				'FileExist' => 'FM_UTIL_FILE_EXIST',
				'Replace' => 'FM_PACK_REPLACE',
				'Rename' => 'FM_PACK_RENAME',
				'Skip' => 'FM_PACK_SKIP',
				'FileExistTitle' => 'FM_UTIL_FILE_EXIST_1',
				'FolderExistTitle' => 'FM_UTIL_FILE_EXIST_2',
				'AskNewName' => 'FM_PACK_ASK_NEW_NAME',
				'PackPermsError' => 'FM_UTIL_PACK_PERMS_ERROR',
				'PackFinishing' => 'FM_UTIL_PACK_FINISH',
				'PackCancel' => 'FM_UTIL_PACK_CANCEL',
				'PackFNameError' => 'FM_UTIL_PACK_FNAME_ERROR'
			));
		}

		if (count($arLangMess) > 0):
?>
var FM_MESS = {0:0<?foreach($arLangMess as $m1 => $m2){echo ', '.$m1." : '".addslashes(GetMessage($m2))."'";}?>};
<?
		endif;
	}

	public static function BuildDialogContent($site)
	{
		global $APPLICATION;
		$actUri = $APPLICATION->GetCurPage()."?search=Y&ssess=".CFilemanSearch::GetSearchSess();

		$s = DeleteParam(array('search', 'ssess'));
		if ($s != '')
			$actUri .= '&'.$s;

		$defMask = COption::GetOptionString("fileman", "search_mask", "*.php");
		// Search dialog
		$aTabs[] = array("DIV" => "bx_fm_tab_search", "TAB" => GetMessage("FM_UTIL_FIND"), "ICON" => "", "TITLE" => GetMessage("FM_UTIL_FIND_TITLE"), "ONSELECT" => "window.oFMSearch.SetTab('search');");
		$aTabs[] = array("DIV" => "bx_fm_tab_replace", "TAB" => GetMessage("FM_UTIL_REPLACE"), "ICON" => "", "TITLE" => GetMessage("FM_UTIL_REPLACE_TITLE"), "ONSELECT" => "window.oFMSearch.SetTab('replace');");

		$searchTabControl = new CAdmintabControl("searchTabControl", $aTabs, false, true);
		$searchTabControl->SetPublicMode('oSearchDialog');
		$searchTabControl->Begin();
		?>
		<?$searchTabControl->BeginNextTab();?>
		<tr><td>
		<div id="bx_search_cont" class="bxfm-search-cont"><form name="bx_search_form" action="<?= $actUri?>" method="POST">
		<table  id="bx_fms_tbl" class="bxfm-d-params bxfm-d-params-add-hide">
			<tr id="bx_search_in_res_tr">
				<td class="bxfm-d-label"><input  id="bx_search_in_res" type="checkbox" value="Y" checked="checked" /></td>
				<td class="bxfm-d-value"><label for="bx_search_in_res" style="font-weight: bold;"><?= GetMessage("FM_UTIL_IN_SEARCH_RESULT")?></label></td>
			</tr>
			<tr title='<?= GetMessage("FM_UTIL_FILE_NAME_TITLE")?>'>
				<td class="bxfm-d-label"><label for="bx_search_file"><?= GetMessage("FM_UTIL_FILE_NAME")?>:</label></td>
				<td class="bxfm-d-value"><input id="bx_search_file" name="bx_search_file" value="<?= $defMask?>" style="width: 220px;" type="text"/></td>
			</tr>
			<tr title="<?= GetMessage("FM_UTIL_PHRASE_TITLE")?>">
				<td class="bxfm-d-label"><label for="bx_search_phrase"><?= GetMessage("FM_UTIL_PHRASE")?>:</label></td>
				<td class="bxfm-d-value"><input id="bx_search_phrase" name="bx_search_phrase" value="" style="width: 220px;" type="text"/></td>
			</tr>
			<tr class="bxfm-d-only-replace" title="<?= GetMessage("FM_UTIL_REPLACE_INP_TITLE")?>">
				<td class="bxfm-d-label"><label for="bx_replace_phrase"><?= GetMessage("FM_UTIL_REPLACE_INP")?>:</label></td>
				<td class="bxfm-d-value"><input id="bx_replace_phrase" name="bx_replace_phrase" value=""  style="width: 220px;" type="text"/></td>
			</tr>

			<tr title="<?= GetMessage("FM_UTIL_DIR_TITLE")?>">
				<td class="bxfm-d-label"><label for="bx_search_dir"><?= GetMessage("FM_UTIL_DIR")?>:</label></td>
				<td class="bxfm-d-value"><input id="bx_search_dir" name="bx_search_dir" value="" style="width: 220px;" type="text"/> <input id="bx_search_fd_but" type="button" value="..." title="<?= GetMessage('FD_OPEN_DIR')?>" onclick="FMFD_SearchDirOpen(true, {site: window.oFMSearch.oSiteSel.value, path: window.oFMSearch.pSearchDir.value});" /><span class="bxfm-site-sel" id="bx_search_site_sel"></span></td>
			</tr>
			<tr>
				<td class="bxfm-d-label"><label for="bx_search_subdir"><?= GetMessage("FM_UTIL_INCLUDE_SUBFOLDER")?></label></td>
				<td class="bxfm-d-value"><input id="bx_search_subdir" name="bx_search_subdir" type="checkbox" value="Y" checked="checked" /></td>
			</tr>
			<tr class="bxfm-d-title">
				<td colSpan="2">
				<a class="bxfm-add-link" id="bx_fms_add_lnk" href="javascript: void(0);"><?= GetMessage("FM_UTIL_ADDITIONAL")?> <span class="bxfm-d-span-hide">(<?= GetMessage("FM_UTIL_HIDE")?>)</span></a>
				</td>
			</tr>
			<tr class="bxfm-add-hide" title="<?= GetMessage("FM_UTIL_DATE_TITLE")?>">
				<td class="bxfm-d-label"><label for="bx_search_date_sel"><?= GetMessage("FM_UTIL_DATE")?>:</label></td>
				<td class="bxfm-d-value">
					<select id="bx_search_date_sel" name="bx_search_date_sel">
						<option value=""> - <?= GetMessage("FM_UTIL_NO_VAL")?> - </option>
						<option value="day"><?= GetMessage("FM_UTIL_DAY")?></option>
						<option value="week"><?= GetMessage("FM_UTIL_WEEK")?></option>
						<option value="month"><?= GetMessage("FM_UTIL_MONTH")?></option>
						<option value="year"><?= GetMessage("FM_UTIL_YEAR")?></option>
						<option value="set"><?= GetMessage("FM_UTIL_SET_EXACTLY")?></option>
					</select>
					<div id="bx_search_date_div" class="bxfm-d-adjust-div">
					<?= GetMessage("FM_UTIL_FROM")?>: <input name="bx_search_date_from" id="bx_search_date_from" value="" style="width: 90px;" type="text"/><?= CAdminCalendar::Calendar("bx_search_date_from", "bx_search_form");?>
					<?= GetMessage("FM_UTIL_TO")?>: <input name="bx_search_date_to" id="bx_search_date_to" value="" style="width: 90px;" type="text"/><?= CAdminCalendar::Calendar("bx_search_date_to", "bx_search_form");?>
					</div>
				</td>
			</tr>
			<tr class="bxfm-add-hide" title="<?= GetMessage("FM_UTIL_SIZE_TITLE")?>">
				<td class="bxfm-d-label"><label for="bx_search_size_sel"><?= GetMessage("FM_UTIL_SIZE")?>:</label></td>
				<td class="bxfm-d-value">
				<select id="bx_search_size_sel" name="bx_search_size_sel">
					<option value=""> - <?= GetMessage("FM_UTIL_NO_VAL")?> - </option>
					<option value="100"><?= GetMessage("FM_UTIL_SIZE_100")?></option>
					<option value="100_500"><?= GetMessage("FM_UTIL_SIZE_100_500")?></option>
					<option value="500"><?= GetMessage("FM_UTIL_SIZE_500")?></option>
					<option value="set"><?= GetMessage("FM_UTIL_SET_EXACTLY")?></option>
				</select>
				<div id="bx_search_size_div" class="bxfm-d-adjust-div">
				<?= GetMessage("FM_UTIL_SIZE_FROM")?>: <input id="bx_search_size_from" name="bx_search_size_from" value="" style="width: 70px;" type="text"/><?= GetMessage("FM_UTIL_KB")?> &nbsp;
				<?= GetMessage("FM_UTIL_SIZE_TO")?>: <input id="bx_search_size_to" name="bx_search_size_to" value="" style="width: 70px;" type="text"/><?= GetMessage("FM_UTIL_KB")?>
				</div>
				</td>
			</tr>
			<tr class="bxfm-add-hide bxfm-d-only-search">
				<td class="bxfm-d-label"><label for="bx_search_dirs_too"><?= GetMessage("FM_UTIL_SEARCH_DIRS")?></label></td>
				<td class="bxfm-d-value"><input id="bx_search_dirs_too" name="bx_search_dirs_too" type="checkbox" value="Y" checked="checked" /></td>
			</tr>
			<tr class="bxfm-add-hide-TEMP" style="display: none;">
				<td class="bxfm-d-label"><label for="bx_search_entire"><?= GetMessage("FM_UTIL_ENTIRE")?></label></td>
				<td class="bxfm-d-value"><input  id="bx_search_entire" type="checkbox" value="Y" /></td>
			</tr>
			<tr class="bxfm-add-hide">
				<td class="bxfm-d-label"><label for="bx_search_case"><?= GetMessage("FM_UTIL_CASE_SENS")?></td>
				<td class="bxfm-d-value"><input id="bx_search_case" name="bx_search_case" type="checkbox" value="Y" /></label></td>
			</tr>
		</table>
		<input type="hidden" name="search" value="Y" />
		<div id="bx_search_res_cont"></div>
<?
		CAdminFileDialog::ShowScript(Array
			(
				"event" => "FMFD_SearchDirOpen",
				"arResultDest" => Array("ELEMENT_ID" => "bx_search_dir"),
				"arPath" => Array("SITE" => $site, "PATH" => "/"),
				"select" => 'D',
				"operation" => 'O',// O - open, S - save
				"showUploadTab" => false,
				"showAddToMenuTab" => false,
				"allowAllFiles" => false,
				"SaveConfig" => true
			)
		);
?>
		</form></div>
		</td></tr>
		<?$searchTabControl->BeginNextTab();?>
		<tr><td>
		<div id="bx_replace_cont" class="bxfm-replace-cont"></div>
		</td></tr>
		<?
		$searchTabControl->End();

		// ***  Copy / move dialog ***
		?>
		<div id="bx_copy_dialog" class="bx-copy-cont">
		<table class="bx-copy-cont-tbl bx-copy-cont-tbl-add-hide" id="bx_copy_table" style="width: 470px;">
			<tr>
				<td class="bxfm-d-label"><label for="bx_copy_file_list"><?= GetMessage("FM_UTIL_FILE")?>:</label></td>
				<td class="bxfm-d-value"><div id="bx_copy_file_list" style="margin-top: 2px;"><div></td>
			</tr>
			<tr>
				<td class="bxfm-d-label"><label for="bx_copy_to"><?= GetMessage("FM_COPY_TO")?>:</label></td>
				<td class="bxfm-d-value">
					<div style="width: 340px;">
						<input id="bx_copy_to" style="width: 255px;" value="/" type="text" /><input type="button" value="..." title="<?= GetMessage('FD_OPEN_DIR')?>" onclick="FMFD_CopyMoveOpen(true, {site: window.oBXFMCopy.oSiteSel.value, path: window.oBXFMCopy.oCopyTo.pInput.value});"  />
						<span class="bxfm-site-sel" id="bx_copy_site_sel"></span>
					</div>
				</td>
			</tr>

			<tr class="bx-copy-d-title">
				<td colSpan="2">
				<a class="bx-copy-add-link" id="bx_copy_add_lnk" href="javascript: void(0);"><?= GetMessage("FM_UTIL_ADDITIONAL")?> <span class="bx-copy-d-span-hide">(<?= GetMessage("FM_UTIL_HIDE")?>)</span></a>
				</td>
			</tr>
			<tr class="bxfm-add-hide">
				<td colSpan="2" style="padding: 5px 0 5px 120px !important;"><?= GetMessage("FM_COPY_COINCID")?>:</td>
			</tr>
			<tr class="bxfm-add-hide">
				<td class="bxfm-d-label">
					<input id="bx_copy_ask_user" type="radio" name="bx_copy_coincidence" value="ask" checked="checked"/>
				</td>
				<td class="bxfm-d-value">
					<label for="bx_copy_ask_user"><?= GetMessage("FM_COPY_ASK_USER")?></label>
				</td>
			</tr>
			<tr class="bxfm-add-hide">
				<td class="bxfm-d-label">
					<input id="bx_copy_replace" type="radio" name="bx_copy_coincidence" value="replace" />
				</td>
				<td class="bxfm-d-value">
					<label for="bx_copy_replace"><?= GetMessage("FM_COPY_REPLACE")?></label>
				</td>
			</tr>
			<tr class="bxfm-add-hide">
				<td class="bxfm-d-label">
					<input id="bx_copy_auto_rename" type="radio" name="bx_copy_coincidence" value="auto_rename" />
				</td>
				<td class="bxfm-d-value">
					<label for="bx_copy_auto_rename"><?= GetMessage("FM_COPY_AUTO_RENAME")?></label>
				</td>
			</tr>
			<tr class="bxfm-add-hide">
				<td class="bxfm-d-label">
					<input id="bx_copy_skip" type="radio" name="bx_copy_coincidence" value="skip" />
				</td>
				<td class="bxfm-d-value">
					<label for="bx_copy_skip"><?= GetMessage("FM_COPY_SKIP")?></label>
				</td>
			</tr>
		</table>
<?
CAdminFileDialog::ShowScript(Array
	(
		"event" => "FMFD_CopyMoveOpen",
		"arResultDest" => Array("ELEMENT_ID" => "bx_copy_to"),
		"arPath" => Array("SITE" => $GLOBALS['site'], "PATH" => "/"),
		"select" => 'D',
		"operation" => 'O',// O - open, S - save
		"showUploadTab" => false,
		"showAddToMenuTab" => false,
		"allowAllFiles" => false,
		"SaveConfig" => true
	)
);
?>
		</div>
		<div id="bx_copy_ask_dialog" class="bx-copy-cont">
		<div style="margin: 0 70px 0 70px; width: 460px; padding: 10px 0 5px;">
		<?= GetMessage("FM_UTIL_FILE_EXIST", array("#NAME#" => "<span id='bx_copy_ask_file_name'>#NAME#</span>", "#FOLDER#" => "<span id='bx_copy_ask_folder'>#FOLDER#</span>"))?>:
		</div>
		<div style="margin: 0 50px;">
		<table  class="bx-copy-compare-tbl">
			<tr class="bx-copy-title">
				<td><?= GetMessage("FM_UTIL_NEW_FILE")?>:</td>
				<td rowSpan="4" class="bx-copy-sep-td"></td>
				<td><?= GetMessage("FM_UTIL_ORIGINAL_FILE")?>:</td>
			</tr>
			<tr>
				<td>
				<div class="bx-copy-name-cnt">
				<a id="bx_copy_ask_file1" href="javascript:void();">file1</a>
				<div id="bxc_ask_nn_cont1" class="bx-copy-new-name bxcnn-0"></div>
				</div>
				</td>
				<td>
				<div class="bx-copy-name-cnt"><a id="bx_copy_ask_file2" href="javascript:void();">file2</a></div>
				</td>
			</tr>
			<tr id="bx_copy_ask_size_row">
				<td><?= GetMessage("FM_UTIL_SIZE")?>: <span id="bx_copy_ask_size1"></span></td>
				<td><?= GetMessage("FM_UTIL_SIZE")?>: <span id="bx_copy_ask_size2"></span></td>
			</tr>
			<tr class="bx-copy-bottom">
				<td><?= GetMessage("FM_UTIL_DATE")?>: <span id="bx_copy_ask_date1"></span></td>
				<td><?= GetMessage("FM_UTIL_DATE")?>: <span id="bx_copy_ask_date2"></span></td>
			</tr>
		</table>
		</div>
		</div>
		<?
		$searchTabControl->BeginNextTab();

		// ***  Pack / unpack dialog ***
		?>
		<div id="bx_pack_dialog" class="bx-pack-cont">
		<table class="bx-pack-cont-tbl bx-pack-cont-tbl-add-hide" id="bx_pack_table" style="width: 470px;">
			<tr>
				<td class="bxfm-d-label"><label for="bx_pack_file_list"><?= GetMessage("FM_PACK_FILE")?>:</label></td>
				<td class="bxfm-d-value"><div id="bx_pack_file_list" style="margin-top: 2px;"><div></td>
			</tr>
			<tr id="bxfm-arctype-line">
				<td class="bxfm-d-label"><label for="bx_pack_arc_type"><?= GetMessage("FM_PACK_ARC_TYPE")?>:</label></td>
				<td class="bxfm-d-value"><div id="bx_pack_arc_type" class="bxfm-arctype-sel" style="margin-top: 4px;"><div></td>
			</tr>
			<tr>
				<!-- if pack "to archive", if not - "to folder" -->
				<td class="bxfm-d-label"><label for="bx_pack_to"><?= GetMessage("FM_PACK_TO")?>:</label></td>
				<td class="bxfm-d-value">
					<div style="width: 340px;">
						<input id="bx_pack_to" style="width: 260px;" value="/" type="text"/>&nbsp;<input type="button" value="..." title="<?= GetMessage('FD_OPEN_DIR')?>" onclick="FMFD_PackUnpackOpen(true, {site: window.oBXFMPack.oSiteSel.value, path: window.oBXFMPack.GetFolderPath(window.oBXFMPack.oPackTo.pInput.value)});"  />
						<span class="bxfm-site-sel" id="bx_pack_site_sel"></span>
					</div>
				</td>
			</tr>
			<tr class="bx-pack-d-title" id="bx-pack-d-title-label">
				<td></td>
				<td><?=GetMessage("FM_PACK_ADDITIONAL")?></td>
			</tr>
			<!--<tr class="bxfm-add-hide" id="bxfm-pack-option-ask">
				<td class="bxfm-d-label">
					<input id="bx_pack_ask_user" type="radio" name="bx_pack_coincidence" value="ask" checked="checked"/>
				</td>
				<td class="bxfm-d-value">
					<label for="bx_pack_ask_user"><?= GetMessage("FM_PACK_ASK_USER")?></label>
				</td>
			</tr>-->
			<tr class="bxfm-add-hide" id="bxfm-pack-option-replace">
				<td class="bxfm-d-label">
					<input id="bx_pack_replace" type="radio" name="bx_pack_coincidence" value="replace" />
				</td>
				<td class="bxfm-d-value">
					<label for="bx_pack_replace"><?= GetMessage("FM_PACK_REPLACE")?></label>
				</td>
			</tr>
			<!--<tr class="bxfm-add-hide" id="bxfm-pack-option-rename">
				<td class="bxfm-d-label">
					<input id="bx_pack_auto_rename" type="radio" name="bx_pack_coincidence" value="auto_rename" />
				</td>
				<td class="bxfm-d-value">
					<label for="bx_pack_auto_rename"><?= GetMessage("FM_PACK_AUTO_RENAME")?></label>
				</td>
			</tr>-->
			<tr class="bxfm-add-hide" id="bxfm-pack-option-skip">
				<td class="bxfm-d-label">
					<input id="bx_pack_skip" type="radio" name="bx_pack_coincidence" value="skip" />
				</td>
				<td class="bxfm-d-value">
					<label for="bx_pack_skip"><?= GetMessage("FM_PACK_SKIP")?></label>
				</td>
			</tr>
		</table>
<?
CAdminFileDialog::ShowScript(Array
	(
		"event" => "FMFD_PackUnpackOpen",
		"arResultDest" => Array("FUNCTION_NAME" => "MakeArchivePathFromFolderPath"),
		"arPath" => Array("SITE" => $GLOBALS['site'], "PATH" => "/"),
		"select" => 'D',
		"operation" => 'O',// O - open, S - save
		"showUploadTab" => false,
		"showAddToMenuTab" => false,
		"allowAllFiles" => false,
		"SaveConfig" => true
	)
);
?>
		</div>
		<script type="text/javascript">
			function MakeArchivePathFromFolderPath(filename, path, site)
			{
				var
					oldArchivePath = BX('bx_pack_to').value,
					newArchivePath,
					archiveName;

				archiveName = oldArchivePath.substr(oldArchivePath.lastIndexOf('/') + 1);
				newArchivePath = "/" + path + "/" + filename + "/" + archiveName;

				newArchivePath = newArchivePath.replace(/[\/]+$/g, "");
				newArchivePath = newArchivePath.replace(/[\/]+/g, '/');
				newArchivePath = newArchivePath.replace(/[\\]+/g, '/');

				if (newArchivePath == '')
					newArchivePath = '/';

				BX('bx_pack_to').value = newArchivePath;
			}
		</script>
		<div id="bx_pack_ask_dialog" class="bx-pack-cont">
		<div style="margin: 0 20px 0 20px; width: 450px; padding: 10px 0 5px;">
		<?= GetMessage("FM_UTIL_FILE_EXIST", array("#NAME#" => "<span id='bx_pack_ask_file_name'>#NAME#</span>", "#FOLDER#" => "<span id='bx_pack_ask_folder'>#FOLDER#</span>"))?>:
		</div>
		<table style="margin: 0 20px 0 20px">
			<tr>
				<td>
					<div class="bx-pack-name-cnt">
					<a id="bx_pack_ask_file2" href="javascript:void('');">file1</a>
					<div id="bxc_ask_nn_cont2" class="bx-pack-new-name bxcnn-0"></div>
					</div>
				</td>
			</tr>
			<tr id="bx_pack_ask_size_row">
				<td><?= GetMessage("FM_UTIL_SIZE")?>: <span id="bx_pack_ask_size2"></span></td>
			</tr>
			<tr class="bx-pack-bottom">
				<td><?= GetMessage("FM_UTIL_DATE")?>: <span id="bx_pack_ask_date2"></span></td>
			</tr>
		</table>
		<br/>
		</div><?
	}

	public static function Request($action, $site)
	{
		global $USER;
		if (!$USER->CanDoOperation('fileman_view_file_structure'))
			return;

		$io = CBXVirtualIo::GetInstance();

		CUtil::JSPostUnescape();
		switch($action)
		{
			case "count":
			case "search":
			case "replace":

				$oSearch = new CFilemanSearch;
				$oSearch->Init(array(
					'lastPath' => isset($_POST['last_path']) ? $_POST['last_path'] : false,
					'bCount' => $action == "count",
					'bReplace' => $action == "replace",
					"fileName" => trim($_POST['file']),
					"phrase" => trim($_POST['phrase']),
					"replacePhrase" => trim($_POST['replace_phrase']),
					"dir" => CFilemanUtils::NormalizePath($_POST['dir']),
					"bSubdir" => $_POST['subdir'],
					"dateFrom" => $_POST['date_from'],
					"dateTo" => $_POST['date_to'],
					"sizeFrom" => intVal($_POST['size_from']),
					"sizeTo" => intVal($_POST['size_to']),
					"entire" => $_POST['entire'],
					"bCaseSens" => $_POST['case_sens'],
					"bDirsToo" => $_POST['dirs_too'],
					"ssess" => $_POST['ssess'],
					"bInResult" => $_POST['in_result'],
					"site" => CFileMan::__CheckSite($_GET['fu_site'])
					//"site" => $site
				));

				CFileMan::SaveLastPath(CFilemanUtils::NormalizePath($_POST['dir']));
			break;

			case "clean_old":
				CFilemanSearch::CleanOldSearchResult();
			break;

			case "search_save_config":
				CFilemanSearch::SaveConfig(array(
					"advMode" => (boolean) $_POST['adv_mode'],
					"bSubdir" => (boolean) $_POST['subdir'],
					"entire" => (boolean) $_POST['entire'],
					"bCaseSens" => (boolean) $_POST['case_sens'],
					"bDirsToo" => (boolean) $_POST['dirs_too']
				));
			break;

			case "copy_save_config":
				CFilemanCopy::SaveConfig(array(
					"advMode" => (boolean) $_POST['adv_mode'],
					"caseOption" => $_POST['case_option']
				));
			break;

			case "copy":
			case "move":
				$oCopy = new CFilemanCopy;
				$oCopy->Init(array(
					"bCopy" => $action == "copy",
					"caseOption" => $_POST['case_option'],
					"arFiles" => $_POST['files'],
					"copyTo" => $_POST['copy_to'],
					"createCopyTo" => $_POST['create_copy_to'] == "Y",

					"userCaseAnswer" => isset($_POST['uc_answer']) ? $_POST['uc_answer'] : false,
					"userCaseToAll" => isset($_POST['uc_to_all']) ? $_POST['uc_to_all'] : false,
					"userCaseLastPath" => isset($_POST['uc_last_path']) ? $_POST['uc_last_path'] : false,

					"bSearch" => $_POST['search'] == "Y",
					"ssess" => $_POST['ssess'],
					"siteTo" => CFileMan::__CheckSite($_GET['fu_site'])
				));

				CFileMan::SaveLastPath($_POST['copy_to']);
			break;

			case "get_last_pathes":
				?>
				<script>window.bx_last_pathes = <?= CUtil::PhpToJSObject(CFilemanUtils::GetLastPathes())?>;</script>
				<?
			break;
			case "pack":

				if (isset($_POST["startFile"]))
					$startFile = trim($_POST["startFile"]);

				if (isset($_POST["quickPath"]))
					$quickPath = trim($_POST["quickPath"]);

				if (isset($_POST["packTo"]))
				{
					if (substr($_POST["packTo"], 0, 1) == "/")
						$pack_to = $_POST["packTo"];
					else
						$pack_to = "/".$_POST["packTo"];
				}

				$siteTo = CFileMan::__CheckSite($_POST['siteTo']);
				$docRootTo = CSite::GetSiteDocRoot($siteTo) ? CSite::GetSiteDocRoot($siteTo) : '';
				$siteFrom = CFileMan::__CheckSite($site);
				$docRootFrom = CSite::GetSiteDocRoot($siteFrom);

				if (!$USER->IsAdmin())
					$pack_to = RemoveScriptExtension($pack_to);

				//check writing permissions
				if (!$USER->CanDoFileOperation('fm_create_new_file', array($siteTo, $pack_to)))
				{
					?>
					<script>
						window.BXFM_archivePermsError = true;
					</script>
					<?
					return;
				}

				if(IsFileUnsafe($docRootTo.$pack_to) || CFileMan::CheckFileName(GetFileName($pack_to)) !== true)
				{
					?>
					<script>
						window.BXFM_archiveFNameError = true;
					</script>
					<?
					return;
				}

				//ask if the file already exists
				if (file_exists($io->GetPhysicalName($docRootTo.$pack_to)))
				{
					if  (empty($startFile))
					{
						if ($_POST["bPackReplace"] != "replace")
						{
							?>
							<script>
								window.BXFM_archiveExists = {
									fileOld:
									{
										name: "<?= CUtil::JSEscape(basename($pack_to))?>",
										path: "<?= CUtil::JSEscape($pack_to)?>",
										site: "<?= CUtil::JSEscape($siteTo)?>",
										size: "<?= CFile::FormatSize(filesize($io->GetPhysicalName($docRootTo.$pack_to)))?>",
										date: "<?= date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL')), filemtime($io->GetPhysicalName($docRootTo.$pack_to))+CTimeZone::GetOffset())?>"
									}
								};
							</script>
							<?
							return;
						}
					}
				}

				$packarc = CBXArchive::GetArchive($docRootTo.$pack_to, trim($_POST['arcType']));

				if ($packarc instanceof IBXArchive)
				{
					//this set of options is common for every archive type
					$packarc->SetOptions(
						array(
							"COMPRESS"			=> true,
							"STEP_TIME"			=> COption::GetOptionString("fileman", "archive_step_time", 30),
							"ADD_PATH"			=> false,
							"REMOVE_PATH"		=> $docRootFrom.$quickPath,
							"CHECK_PERMISSIONS" => $USER->IsAdmin() ? false : true
							)
						);

					$arPackFiles = array();
					foreach ($_POST["files"] as $path2file)
					{
						$arPackFiles[] = $docRootFrom.$path2file['path'];
					}

					@set_time_limit(0);

					$pRes = $packarc->Pack($arPackFiles, $startFile);

					switch ($pRes)
					{
						case IBXArchive::StatusContinue:
						?>
							<script>
							window.fmPackTimeout = true;
							window.fmPackLastFile = '<?= CUtil::JSEscape($packarc->GetStartFile())?>';
							</script>
						<?
							break;
						case IBXArchive::StatusSuccess:
						?>
							<script>
							window.fmPackTimeout = false;
							window.fmPackLastFile = '';
							window.fmPackSuccess = true;
							</script>
						<?
							break;
						case IBXArchive::StatusError:
						$arErrors = $packarc->GetErrors();
						?>
							<script>
							window.fmPackTimeout = false;
							window.fmPackSuccess = false;
							window.fmPackErrors = <?=CUtil::PhpToJSObject($arErrors)?>;
							</script>
						<?
							break;
					}
				}
				else
				{
					?>
					<script>
					window.fmPackSuccess = false;
					window.fmPackErrors = <?=CUtil::PhpToJsObject(GetMessage("FM_UTIL_ARC_ERROR"))?>;
					</script>
					<?
				}
			break;
			case "unpack":

				global $USER;

				if (isset($_POST["packTo"]))
				{
					if (substr($_POST["packTo"], 0, 1) == "/")
						$pack_to = $_POST["packTo"];
					else
						$pack_to = "/".$_POST["packTo"];
				}

				$siteFrom = CFileMan::__CheckSite($_GET['site']);
				$siteTo = CFileMan::__CheckSite($_POST['siteTo']);
				$docRootFrom = CSite::GetSiteDocRoot($siteFrom);
				$docRootTo = CSite::GetSiteDocRoot($siteTo);

				if (!($USER->CanDoFileOperation('fm_create_new_file', array($siteTo, $_POST["packTo"])) ||
				$USER->CanDoFileOperation('fm_create_new_folder', array($siteTo, $_POST["packTo"]))))
				{
					?>
					<script>
						window.BXFM_archivePermsError = true;
					</script>
					<?
					return;
				}

				$bReplaceFiles = $_POST["case_option"] == "replace" ? true : false;

				$arc = CBXArchive::GetArchive($docRootFrom.$_POST["files"][0]);

				if ($arc instanceof IBXArchive)
				{
					global $USER;

					$arc->SetOptions
						(
						array(
							"REMOVE_PATH"		=> $docRootFrom,
							"UNPACK_REPLACE"	=> $bReplaceFiles,
							"CHECK_PERMISSIONS" => $USER->IsAdmin() ? false : true
							)
						);

					$uRes = $arc->Unpack($docRootTo.$pack_to);

					if (!$uRes)
					{
						$uErrors = $arc->GetErrors();
					?>
						<script>
						window.fmUnpackSuccess = false;
						window.fmUnpackErrors = <?=CUtil::PhpToJSObject($uErrors)?>
						</script>
					<?
					}
					else
					{
					?>
						<script>
						window.fmUnpackSuccess = true;
						</script>
					<?
					}
				}
				else
				{
					?>
					<script>
					window.fmUnpackSuccess = false;
					window.fmUnpackErrors = <?=CUtil::PhpToJsObject(GetMessage("FM_UTIL_ARC_ERROR"))?>;
					</script>
					<?
				}
			break;
		}
	}

	public static function GetTimeout()
	{
		return COption::GetOptionString("fileman", "search_time_step", 5);
	}

	public static function NormalizePath($path)
	{
		$path = CFileMan::SecurePathVar(Rel2Abs("/", $path));
		$path = rtrim($path, '/');
		return $path;
	}

	public static function TrimPath($path, $docRoot = false)
	{
		if ($docRoot === false)
			$docRoot = $_SERVER["DOCUMENT_ROOT"];
		$l = strlen($docRoot);
		return strlen($path) > $l ? substr($path, $l) : '/';
	}

	public static function GetLastPathes()
	{
		$arPathes = CFileMan::GetLastPathes();
		$arRes = array(
			array("name" => "/", "desc" => GetMessage("FM_UTIL_ROOT_DIR"))
		);
		for($i = 0, $l = count($arPathes); $i < $l; $i++)
			$arRes[] = array("name" => $arPathes[$i]);

		return $arRes;
	}

	public static function GetModifyTime($path)
	{
		$path = CBXVirtualIoFileSystem::ConvertCharset($path);

		if (file_exists($path))
		{
			$stat = stat($path);
			return $stat["mtime"];
		}

		return null;
	}
}

//
class CFilemanSearch
{
	public function Init($Params)
	{
		$this->maxFileOpenSize = 1024 * COption::GetOptionString("fileman", "search_max_open_file_size", 1024);
		$this->maxResultCount = COption::GetOptionString("fileman", "search_max_res_count", false);

		$this->startTime = time();
		$this->Params = $Params;
		$this->Result = array();
		$this->docRoot = CSite::GetSiteDocRoot($Params['site']);

		$this->bSkip = $this->Params['lastPath'] ? true : false;
		if ($this->bSkip)
		{
			$this->Params['lastPath'] = Rel2Abs("/", $this->Params['lastPath']);
			$this->Params['lastPath'] = $this->docRoot.$this->Params['lastPath'];
		}

		$this->sSess = $this->Params['ssess'] ? $this->Params['ssess'] : false;
		$this->bReplace = $this->Params['bReplace'] && strlen($this->Params['phrase']) > 0;

		if ($this->bReplace)
		{
			$this->Params['bDirsToo'] = false;
		}

		$bSuccess = false;
		$bBreak = false;
		$nextPath = '';

		// Search in results of the previous search
		if ($this->Params['bInResult'])
		{
			$searchRes = CFilemanSearch::GetSearchResult($this->Params['ssess'], array('id', 'asc'));
			for($i = 0, $l = count($searchRes); $i < $l; $i++)
			{
				$path = $this->docRoot.$searchRes[$i]['path'];
				if ($this->CheckBreak())
				{
					$bBreak = true;
					$nextPath = $path;
					break;
				}
				else
				{
					$this->Search($path);
				}
			}
			$bSuccess = true;
			$bStoped = false;
		}
		else
		{
			$path = Rel2Abs("/", $this->Params["dir"]);
			$path = $this->docRoot.$path;
			$oDir = new CFilemanUtilDir($path, array(
				'obj' => $this,
				'site' => $Params['site'],
				'callBack' => "Search",
				'checkBreak' => "CheckBreak",
				'checkSubdirs' => $this->Params["bSubdir"]
			));
			$bSuccess = $oDir->Start();

			$bBreak = $oDir->bBreak;
			$nextPath = $oDir->nextPath;
			$bStoped = $oDir->bStoped;

			if ($bStoped)
				$bBreak = false;
		}

		if ($bSuccess)
		{
?>
<script>
<?if ($bBreak):  // Execution breaks on timeout?>
	window.fmsBtimeout = true;
	window.fmsLastPath = '<?= CUtil::JSEscape(CFilemanUtils::TrimPath($nextPath, $this->docRoot))?>';
<? else: ?>
	window.fmsBtimeout = false;
<? endif; ?>

<?if ($bStoped):  // Execution breaks on timeout?>
	window.fmsBstoped = true;
<? else: ?>
	window.fmsBstoped = false;
<? endif; ?>

<? if ($this->Params['bCount']): ?>
	window.fmsResult = <?= count($this->Result)?>;
<? else: ?>
	window.fmsResult = <?= CUtil::PhpToJSObject($this->Result)?>;
<? endif; ?>
</script>
<?
		}
		else
		{
?>
<script>
<? if ($this->Params['bCount']): ?>
	window.fmsResult = 0;
<? else: ?>
	window.fmsResult = [];
<? endif; ?>
</script>
<?
		}
	}

	public function Search($file)
	{
		global $APPLICATION, $USER;

		if ($this->maxResultCount && count($this->Result) >= $this->maxResultCount)
			return "stop";

		if ($this->bSkip)
		{
			if ($file == $this->Params['lastPath'])
				$this->bSkip = false; // continue handle files from last path
			else
				return; // Files was handled earlier
		}

		$io = CBXVirtualIo::GetInstance();

		$bIsDir = $io->DirectoryExists($file);
		$replFileCount = 0;
		if ($bIsDir && !$this->Params['bDirsToo'])
			return;

		$entity = $bIsDir ? $io->GetDirectory($file) : $io->GetFile($file);

		$path = CFilemanUtils::TrimPath($file, $this->docRoot);
		$arPath = array($this->Params['site'], $path);

		// Check access
		if (!$USER->CanDoFileOperation('fm_view_file', $arPath))
			return;

		$name = CFileman::GetFileName($file); // Name of file or dir

		// Check filename
		if ($this->Params['fileName'] != "")
		{
			if (!$this->Params['bCaseSens'])
			{
				$name = strtolower($name);
				$this->Params['fileName'] = strtolower($this->Params['fileName']);
			}

			// Simple find in file name
			if (strpos($this->Params['fileName'], "*") === false)
			{
				if (strpos($name, $this->Params['fileName']) === false)
					return;
			}
			else // name pattern with "*"
			{
				$pattern = str_replace('.', '\.',$this->Params['fileName']);
				$pattern = str_replace('/', '', $pattern);
				$pattern = str_replace('*', '.*', $pattern);
				if (!preg_match('/^'.$pattern.'$/i', $io->ExtractNameFromPath($file)))
					return;
			}
		}

		if (!$bIsDir)
		{
			// Check filesize
			$size = $entity->GetFileSize();
			// Filesize limits in Kb
			if (
				($this->Params['sizeFrom'] > 0 && $size < $this->Params['sizeFrom'] * 1024)
				||
				($this->Params['sizeTo'] > 0 && $size > $this->Params['sizeTo'] * 1024)
			)
				return;
		}
		else
		{
			$size = 0;
		}

		// Check filetime
		$time = $entity->GetModificationTime()+CTimeZone::GetOffset();
		if (
			($this->Params['dateFrom'] && $time < MakeTimeStamp($this->Params['dateFrom'], CLang::GetDateFormat("FULL")))
			||
			($this->Params['dateTo'] && $time > MakeTimeStamp($this->Params['dateTo'], CLang::GetDateFormat("FULL")))
		)
			return;

		if ($this->Params['phrase'] != "")
		{
			// File size limits or it's dir or access denied
			if ($size > $this->maxFileOpenSize || $bIsDir || ($this->bReplace && !$USER->CanDoFileOperation('fm_edit_existent_file', $arPath)))
				return;

			$fTmp = $io->GetFile($file);

			$phrase = $this->Params['phrase'];
			$fileContent = str_replace("\r\n","\n", $fTmp->GetContents());
			$origFileContent = $fileContent;
			$isPHP = CFileman::IsPHP($fileContent) || HasScriptExtension($path) || substr($name, 0, 1) == ".";

			if (!$this->Params['bCaseSens'])
			{
				$phrase = strtolower($phrase);
				$fileContent = strtolower($fileContent);
			}

			$I_PCRE_MODIFIER = $this->Params['bCaseSens'] ? '' : 'i';

			// TODO: Add check Entire word
			//$this->Params['entire']

			if (strpos($fileContent, $phrase) === false)
				return;

			if ($this->bReplace) // Replace
			{
				if ($isPHP && !$USER->CanDoOperation('edit_php'))
					return; // User can't write PHP files

				$pattern = '/'.preg_quote($this->Params['phrase'], '/').'/'.$I_PCRE_MODIFIER.BX_UTF_PCRE_MODIFIER;

				$res = array();
				preg_match_all($pattern, $origFileContent, $res);
				$origFileContent = preg_replace($pattern, $this->Params['replacePhrase'], $origFileContent);
				$replFileCount = count($res[0]);

				$APPLICATION->SaveFileContent($file, $origFileContent);
			}
			else
			{
				if ($isPHP && !($USER->CanDoOperation('edit_php') || $USER->CanDoFileOperation('fm_lpa', $arPath)))
					return; // User can't read PHP files

				$pattern = '/'.preg_quote($this->Params['phrase'], '/').'/'.$I_PCRE_MODIFIER.BX_UTF_PCRE_MODIFIER;

				// Only for LPA. All php fragments will be cutted off
				if ($USER->CanDoFileOperation('fm_lpa', $arPath) && !$USER->CanDoOperation('edit_php'))
					$origFileContent = CMain::ProcessLPA($origFileContent, '');

				$res = array();
				preg_match_all($pattern, $origFileContent, $res);
				$replFileCount = count($res[0]);
			}
		}

		$this->Result[] = array(
			'path' => $path,
			'size' => $size,
			'b_dir' => $bIsDir,
			'time' => $time,
			'str_date' => date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL")), $time),
			'str_size' => $bIsDir ? "" : CFile::FormatSize($size),
			'type_src' => "/bitrix/images/fileman/types/".($bIsDir ? "folder" : CFileMan::GetFileTypeEx($file)).".gif",
			'repl_count' => $replFileCount // used only in replace-mode to count matches
		);
	}

	public function CheckBreak()
	{
		return time() - $this->startTime > CFilemanUtils::GetTimeout();
	}

	public static function trimPath($path, $docRoot = false)
	{
		if ($docRoot === false)
			$docRoot = $_SERVER["DOCUMENT_ROOT"];
		$l = strlen($docRoot);
		return strlen($path) > $l ? substr($path, $l) : '/';
	}

	public static function CheckSearchSess($searchSess)
	{
		global $DB;

		switch(strtoupper($DB->type))
		{
			case "MYSQL":
				$res = $DB->Query("SELECT * FROM b_file_search WHERE SESS_ID='".$DB->ForSql($searchSess)."' LIMIT 1", false);
			break;
			case "MSSQL":
				$res = $DB->Query("SELECT TOP 1 * FROM b_file_search WHERE SESS_ID='".$DB->ForSql($searchSess)."'", false);
			break;
			case "ORACLE":
				$res = $DB->Query("SELECT * FROM b_file_search WHERE SESS_ID='".$DB->ForSql($searchSess)."' AND ROWNUM <= 1", false);
			break;
		}

		return !$res->Fetch();
	}

	public static function GetSearchResult($searchSess = '', $arOrder = array('date', 'desc'))
	{
		global $DB;

		// TODO: check $searchRes initialization
		$arFields = array(
			'SESS_ID' => $searchSess,
			'F_PATH' => $searchRes[$i]['path'],
			'B_DIR' => $searchRes[$i]['b_dir'],
			'F_TIME' => $searchRes[$i]['time'],
			'F_SIZE' => $searchRes[$i]['size']
		);
		$q = "SELECT * FROM b_file_search WHERE SESS_ID='".$DB->ForSql($searchSess)."'";

		$strOrderBy = '';
		if ($arOrder[0] == 'timestamp')
			$by = 'F_TIME';
		else if ($arOrder[0] == 'size')
			$by = 'F_SIZE';
		else if ($arOrder[0] == 'name')
			$by = 'F_PATH';
		else if ($arOrder[0] == 'id')
			$by = 'ID';
		else
			$by = false;

		$order = strtolower($arOrder[1]);
		if ($by)
		{
			$strOrderBy = $by.' '.($order == 'desc' ? 'desc'.(strtoupper($DB->type) == "ORACLE" ? " NULLS LAST" : "") : 'asc'.(strtoupper($DB->type) == "ORACLE" ? " NULLS FIRST":""));
		}

		if($strOrderBy != "")
			$q .= " ORDER BY ".$strOrderBy;

		$res = $DB->Query($q, false);

		$searchRes = array();
		while($arRes = $res->Fetch())
		{
			$searchRes[] = array(
				'path' => $arRes['F_PATH'],
				'b_dir' => $arRes['B_DIR'],
				'time' => $arRes['F_TIME'],
				'size' => $arRes['F_SIZE']
			);
		}

		return $searchRes;
	}

	public static function SetSearchResult($searchRes, $searchSess, $bClean = true)
	{
		global $DB;
		$DB->Query("DELETE FROM b_file_search WHERE SESS_ID='".$DB->ForSql($searchSess)."'", false);

		for ($i = 0, $l = count($searchRes); $i < $l; $i++)
		{
			$arFields = array(
				'SESS_ID' => $searchSess,
				'F_PATH' => $searchRes[$i]['path'],
				'B_DIR' => $searchRes[$i]['b_dir'],
				'F_TIME' => $searchRes[$i]['time'],
				'F_SIZE' => $searchRes[$i]['size']
			);

			unset($arFields['NEW']);
			CDatabase::Add("b_file_search", $arFields);
		}
		return $searchRes;
	}

	public static function DelFromSearchResult($searchSess, $path)
	{
		global $DB;
		return $DB->Query("DELETE FROM b_file_search WHERE SESS_ID='".$DB->ForSql($searchSess)."' AND F_PATH='".$DB->ForSql($path)."'", false);
	}

	public static function RenameInSearchResult($searchSess, $pathFrom, $pathTo)
	{
		global $DB;

		$strSql =
			"UPDATE b_file_search SET ".
				$DB->PrepareUpdate("b_file_search", array('F_PATH' => $pathTo)).
			" WHERE SESS_ID='".$DB->ForSql($searchSess)."' AND F_PATH='".$DB->ForSql($pathFrom)."'";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function SecureSearchSess($ssess = '')
	{
		return preg_replace("/[^a-z0-9]/i", "", $ssess);
	}

	public static function GetSearchSess()
	{
		return md5($GLOBALS["USER"]->GetID()."+".uniqid(rand(), true));
	}

	// Delete all results older than 2 days
	public static function CleanOldSearchResult()
	{
		global $DB;
		$d = date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL")), mktime(0, 0, 0, date("m")  , date("d") - 2, date("Y")));
		$q = "DELETE FROM b_file_search WHERE TIMESTAMP_X<=".$DB->CharToDateFunction($d, "SHORT")."";
		$DB->Query($q, false);
	}

	public static function SaveConfig($arConfig)
	{
		CUserOptions::SetOption("fileman", "file_search_config", serialize($arConfig));
	}

	public static function GetConfig()
	{
		$arConfig = array();
		$strAr = CUserOptions::GetOption("fileman", "file_search_config", false);
		if ($strAr && CheckSerializedData($strAr))
			$arConfig = unserialize($strAr);
		return $arConfig;
	}
}

class CFilemanUtilDir
{
	var $checkBreak = '';
	var $bBreak = false;
	var $bStoped = false;
	var $bFound = false;
	var $nextPath = '';
	var $startPath = '';
	var $dir;
	var $callback;
	var $processDir;
	var $bSkipSymLinks = true;
	var $bDirFirst = false;
	var $err = array();
	var $cntDir = 0;
	var $cntFile = 0;

	public function CFilemanUtilDir($dir, $Params)
	{
		$this->dir = str_replace('\\','/',$dir);
		$this->obj = $Params['obj'];
		$this->site = $Params['site'];
		$this->docRoot = CSite::GetSiteDocRoot($Params['site']);
		$this->checkSubdirs = isset($Params['checkSubdirs']) ? $Params['checkSubdirs'] : true;
		$this->callBack = isset($Params['callBack']) ? $Params['callBack'] : false;
		$this->checkBreak = isset($Params['checkBreak']) ? $Params['checkBreak'] : false;
		$this->processDir = isset($Params['processDir']) ? $Params['processDir'] : false;
	}

	public function Start($dir = false)
	{
		if ($dir)
			$this->dir = str_replace('\\','/',$dir);

		$io = CBXVirtualIo::GetInstance();

		if ($io->DirectoryExists($this->dir))
		{
			if ($this->Recursion($this->dir))
			{
				if ($this->startPath && !$this->bFound)
				{
					$this->err[] = 'Error: start path not found!';
					return false;
				}
				return true;
			}
			return false;
		}

		$r = $this->CallMethod($this->callBack, $this->dir);
		if ($r === false)
			$this->err[] = 'Error on entry: '.$this->dir;

		return $r;
	}

	public function Recursion($dir)
	{
		//error_reporting(0);
		global $USER;

		$io = CBXVirtualIo::GetInstance();

		if ($this->bDirFirst && $this->processDir && (!$this->startPath || $this->bFound))
			if (!$this->CallMethod($this->processDir, $dir))
			{
				$this->err[] = 'Error on dir: '.$dir;
				return false;
			}

		$dTmp = $io->GetDirectory($dir);
		$arChildren = $dTmp->GetChildren();

		$arItems = array();
		foreach ($arChildren as $child)
			$arItems[] = $child->GetName();

		sort($arItems);
		$s_dir = CFilemanUtils::TrimPath($dir, $this->docRoot);

		foreach($arItems as $item)
		{
			$f = $dir."/".$item;
			if ($this->startPath && !$this->bFound)
			{
				if ($this->startPath == $f)
					$this->bFound = true;
				elseif (substr($this->startPath,0,strlen($f)+1) != $f.'/')
					continue;
			}

			$arPath = array($this->site, $s_dir."/".$item);
			$this->nextPath = $f;

			if ($this->checkBreak && $this->CallMethod($this->checkBreak))
			{
				$this->bBreak = true;
				return true;
			}

			if ($this->bSkipSymLinks && is_link($f))
				continue;

			if ($io->DirectoryExists($f))
			{
				if (!$USER->CanDoFileOperation('fm_view_listing', $arPath)) // access
					continue;

				$r = $this->CallMethod($this->callBack, $f);
				if ($r == 'stop')
				{
					$this->bBreak = true;
					$this->bStoped = true;
					return true;
				}

				if ($this->checkSubdirs)
				{
					$r = $this->Recursion($f);
					if ($this->bBreak)
						return $r;
					if ($r===false)
						return false;
				}
			}
			elseif ($this->callBack)
			{
				$r = $this->CallMethod($this->callBack, $f);
				if ($r == 'stop')
				{
					$this->bBreak = true;
					$this->bStoped = true;
					return true;
				}

				if ($r===false)
				{
					$this->err[] = 'Error on file: <b>'.$f.'</b>';
					return false;
				}
				$this->cntFile++;
			}
		}

		if ($this->processDir && !$this->bDirFirst)
		{
			if (!$this->CallMethod($this->processDir, $dir))
			{
				$this->err[] = 'Error on folder: '.$dir;
				return false;
			}
		}
		return true;
	}

	public function CallMethod($method = '', $param = false)
	{
		$obj = $this->obj;
		if ($param)
			return $obj->$method($param);

		return $obj->$method();
	}
}

class CFilemanChmod
{
	public function Init($Params)
	{
		$this->value = $Params['value'];
		$this->startTime = time();
		$this->Params = $Params;
		$this->Result = array();

		$this->bSkip = $this->Params['lastPath'] ? true : false;
		if ($this->bSkip)
		{
			$this->Params['lastPath'] = Rel2Abs("/", $this->Params['lastPath']);
			$this->Params['lastPath'] = $_SERVER["DOCUMENT_ROOT"].$this->Params['lastPath'];
		}
	}

	public function CheckBreak()
	{
		return time() - $this->startTime > CFilemanUtils::GetTimeout();
	}

	public function Chmod($file)
	{
		if ($this->bSkip)
		{
			if ($file == $this->Params['lastPath'])
				$this->bSkip = false; // continue handle files from last path
			else
				return; // Files was handled earlier
		}

		$this->Result[] = array(CFilemanUtils::TrimPath($file), false);
	}
}

class CFilemanCopy
{
	public static function Init($Params)
	{
		global $USER;
		$arWarnings = array();

		if (!$USER->CanDoOperation('fileman_admin_files') && !$USER->CanDoOperation('fileman_admin_folders'))
			$arWarnings[] = GetMessage('FM_UTIL_ACCESS_DENIED');

		$io = CBXVirtualIo::GetInstance();

		if (count($arWarnings) == 0)
		{
			$pathTo = trim($Params['copyTo'], " /");
			$site = CFileMan::__CheckSite($_GET['site']);
			$siteTo = $Params['siteTo'] ? $Params['siteTo'] : $site;
			$pathTo = $pathTo == "" ? "/" : "/".$pathTo."/";

			$absPathTo = CSite::GetSiteDocRoot($siteTo).$pathTo;
			$docRootFrom = CSite::GetSiteDocRoot($site);

			if (!$io->DirectoryExists($absPathTo))
			{
				// Create destination directory
				$bAccess = $USER->CanDoOperation('fileman_admin_folders') && $USER->CanDoFileOperation('fm_create_new_folder',$pathTo);

				if ($Params['createCopyTo'])
				{
					if ($bAccess)
						CFileMan::CreateDir(Array($siteTo, $pathTo));
				}
				else // Ask to user - create destination folder or no?
				{
					?><script>window.BXFM_NoCopyToDir = "<?= ($bAccess ? "ask_user" : "access_denied")?>";</script><?
					return;
				}
			}

			foreach ($Params['arFiles'] as $file)
			{
				$filePath = $file['path'];
				$caseOption = $Params['caseOption'];

				if($Params["userCaseLastPath"])
				{
					if ($Params["userCaseLastPath"] != $filePath)
						continue;

					$caseOption = $Params['userCaseAnswer'];
					if ($Params["userCaseToAll"])
						$Params['caseOption'] = $caseOption;

					$Params["userCaseLastPath"] = false;
				}

				$arPath_i = Array($site, $filePath);
				$absPath_i = $docRootFrom.$filePath;
				$bDir_i = $io->DirectoryExists($absPath_i);
				$name_i = CFileman::GetFileName($filePath);
				$strWarn = "";

				// Check if file already exists in destination folder
				if ($io->FileExists($absPathTo.$name_i) || ( $bDir_i == $io->DirectoryExists($absPathTo.$name_i) && $bDir_i))
				{
					$fTmp = $io->GetFile($absPathTo.$name_i);
					$fTmp1 = $io->GetFile($absPath_i);
					$altName = CFilemanCopy::GetAltFileName($absPathTo, $name_i, $bDir_i);

					if ($caseOption == 'ask')
					{
						?><script>
						window.BXFM_fileExist = {
							fileOld: {
								name: "<?= CUtil::JSEscape($name_i)?>",
								path: "<?= CUtil::JSEscape($pathTo.$name_i)?>",
								site: "<?= CUtil::JSEscape($siteTo)?>",
								bDir: <?= $bDir_i ? "true" : "false"?>,
								size: "<?= $bDir_i ? '-' : CFile::FormatSize($fTmp->GetFileSize())?>",
								date: "<?= date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL")), CFilemanUtils::GetModifyTime($absPathTo.$name_i)+CTimeZone::GetOffset())?>"
							},
							fileNew: {
								alt_name: "<?= CUtil::JSEscape($altName)?>",
								name: "<?= CUtil::JSEscape($name_i)?>",
								path: "<?= CUtil::JSEscape($filePath)?>",
								site: "<?= CUtil::JSEscape($site)?>",
								bDir: <?= $bDir_i ? "true" : "false"?>,
								size: "<?= $bDir_i ? '-' : CFile::FormatSize($fTmp1->GetFileSize())?>",
								date: "<?= date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL")), CFilemanUtils::GetModifyTime($absPath_i)+CTimeZone::GetOffset())?>"
							}
						};
						</script><?
						return;
					}
					elseif ($caseOption == 'replace')
					{
						// We don't need to replace, couse it's one file
						if (CFileMan::NormalizePath($pathTo."/".$name_i) == $filePath)
							continue;

						$strWarn = CFileMan::CopyEx($arPath_i, Array($siteTo, CFileMan::NormalizePath($pathTo."/".$name_i)), !$Params['bCopy'], true);

						if ($strWarn != "")
						{
							$arWarnings[] = $strWarn;
							$log = false;
						}
						elseif ($Params['bSearch'] && $Params['ssess'] && !$Params['bCopy'])
							CFilemanSearch::DelFromSearchResult($Params['ssess'], $filePath);
					}
					elseif ($caseOption == 'auto_rename')
					{
						$strWarn = CFileMan::CopyEx($arPath_i, Array($siteTo, CFileMan::NormalizePath($pathTo."/".$altName)), !$Params['bCopy']);

						if ($strWarn != "")
						{
							$arWarnings[] = $strWarn;
							$log = false;
						}
						elseif ($Params['bSearch'] && $Params['ssess'] && !$Params['bCopy'])
							CFilemanSearch::DelFromSearchResult($Params['ssess'], $filePath);
					}
					elseif ($caseOption == 'skip')
					{
						continue;
					}
				}
				else // File not exist
				{
					$strWarn = CFileMan::CopyEx($arPath_i, Array($siteTo, CFileMan::NormalizePath($pathTo."/".$name_i)), !$Params['bCopy']);

					if ($strWarn != "")
					{
						$arWarnings[] = $strWarn;
						$log = false;
					}
					elseif ($Params['bSearch'] && $Params['ssess'] && !$Params['bCopy'])
						CFilemanSearch::DelFromSearchResult($Params['ssess'], $filePath);
				}

				$module_id = "fileman";
				if(COption::GetOptionString($module_id, "log_page", "Y")=="Y" && $log)
				{
					$res_log['copy_to'] = substr($pathTo, 1);
					$res_log['path'] = substr($filePath, 1);
					if ($Params['bCopy'] == "copy")
					{
						if (!$bDir_i)
							CEventLog::Log(
								"content",
								"FILE_COPY",
								"fileman",
								"",
								serialize($res_log)
							);
						else
							CEventLog::Log(
								"content",
								"SECTION_COPY",
								"fileman",
								"",
								serialize($res_log)
							);
					}
					else
					{
						if (!$bDir_i)
							CEventLog::Log(
								"content",
								"FILE_MOVE",
								"fileman",
								"",
								serialize($res_log)
							);
						else
							CEventLog::Log(
								"content",
								"SECTION_MOVE",
								"fileman",
								"",
								serialize($res_log)
							);
					}
				}
			}
		}

		$arWarnings
		?><script>
		window.BXFM_result = {
			status: "<?= (count($arWarnings) > 0 ? 'errors' : 'ok')?>",
			errors: <?= (count($arWarnings) > 0 ? CUtil::PhpToJSObject($arWarnings) : '{}')?>
		};
		</script><?
	}

	public static function SaveConfig($arConfig)
	{
		CUserOptions::SetOption("fileman", "file_copy_move_config", serialize($arConfig));
	}

	public static function GetConfig()
	{
		$arConfig = array();
		$strAr = CUserOptions::GetOption("fileman", "file_copy_move_config", false);
		if ($strAr && CheckSerializedData($strAr))
			$arConfig = unserialize($strAr);

		return $arConfig;
	}

	public static function GetAltFileName($absPath, $name, $bDir = false)
	{
		$io = CBXVirtualIo::GetInstance();
		for ($i=1; $i <= 9999; $i++)
		{
			$dotPos = strpos($name, ".");
			if ($bDir || $dotPos === false)
				$new_name = $name."(".$i.")";
			else
				$new_name = substr($name, 0, $dotPos)."(".$i.")".substr($name, $dotPos);

			if (!$io->FileExists($absPath.$new_name) && !$io->DirectoryExists($absPath.$new_name))
				break;
		}
		return $new_name;
	}
}

class CFilemanTransliterate
{
	public static function Init($Params)
	{
		echo CUtil::InitJSCore(array('translit'), true);
?>
<script>
function InitTranslitInputs(params)
{
	var
		bLinked = params.bLinked !== false,
		translitTimeout,
		fromInput = params.fromInput,
		toInput = params.toInput,
		prevValue = fromInput,
		translit = function()
		{
			if (prevValue != fromInput.value)
				BX.translit(fromInput.value, {
					max_len : params.max_len,
					change_case : params.change_case,
					replace_space : params.replace_space,
					replace_other : params.replace_other,
					delete_repeat_replace : params.delete_repeat_replace,
					use_google : params.use_google,
					callback : function(result)
					{
						if (params.ext && params.ext)
							result = result + "." + params.ext;
						toInput.value = result;
						prevValue = fromInput.value;
					}
				});
		};

	if (params.bLinkInputs)
	{
		if (!params.unlinkedTitle)
			params.unlinkedTitle = '';
		if (!params.linkedTitle)
			params.linkedTitle = '';

		var
			pLink1 = BX.create("IMG", {props: {src: "/bitrix/images/1.gif", className: "bxfm-linked", title: params.linkedTitle}}),
			pLink2 = BX.create("IMG", {props: {src: "/bitrix/images/1.gif", className: "bxfm-linked", title: params.linkedTitle}});

		if (fromInput.nextSibling)
			fromInput.parentNode.insertBefore(pLink1, fromInput.nextSibling);
		else
			fromInput.parentNode.appendChild(pLink1);

		if (toInput.nextSibling)
			toInput.parentNode.insertBefore(pLink2, toInput.nextSibling);
		else
			toInput.parentNode.appendChild(pLink2);

		pLink1.onclick = pLink2.onclick = function()
		{
			pLink1.className = pLink2.className = bLinked ? "bxfm-unlinked" : "bxfm-linked";
			pLink1.title = pLink2.title = bLinked ? params.unlinkedTitle : params.linkedTitle;
			bLinked = !bLinked;
			if (params.pLinked)
				params.pLinked.value = bLinked ? "Y" : "N";
			if (bLinked)
				translit();
		};

		if (!bLinked)
		{
			bLinked = true;
			pLink1.onclick();
		}
	}

	fromInput.onmousedown =
	fromInput.onkeydown =
	fromInput.onchange = function()
	{
		if (bLinked)
		{
			if (translitTimeout)
				clearTimeout(translitTimeout);
			translitTimeout = setTimeout(translit, 150);
		}
	}

	if (bLinked)
		translit();
}

InitTranslitInputs({
	fromInput: BX('<?= CUtil::JSEscape($Params['fromInputId'])?>'),
	toInput: BX('<?= CUtil::JSEscape($Params['toInputId'])?>'),
	pLinked: BX('<?= CUtil::JSEscape($Params['linkedId'])?>'),
	bLinkInputs: true,
	bLinked: <?= $Params['linked'] ? 'true' : 'false'?>,
	linkedTitle : '<?= CUtil::JSEscape($Params['linkedTitle'])?>',
	unlinkedTitle: '<?= CUtil::JSEscape($Params['unlinkedTitle'])?>',
	ext: '<?= CUtil::JSEscape($Params['ext'])?>',
	max_len : 100,
	change_case : 'L',
	replace_space : '-',
	replace_other : '-',
	delete_repeat_replace : true,
	use_google : <?= COption::GetOptionString("fileman", "use_translit_google", true) ? 'true' : 'false'?>
});
</script>
<?
	}
}
?>