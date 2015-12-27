<?
IncludeModuleLangFile(__FILE__);
class CMedialib
{
	private static
		$bCache = true,
		$cacheTime = 360000,
		$cachePath = "medialib/";

	function Init(){}
	public static function GetOperations($collectionId, $menu = false)
	{
		global $USER;
		static $oCollections;
		static $arOp;

		$userGroups = $USER->GetUserGroupArray();
		$key = $collectionId.'|'.implode('-', $userGroups);

		if (!is_array($arOp[$key]))
		{
			if (!is_array($arOp))
				$arOp = array();

			if (!is_array($oCollections))
			{
				$res = CMedialib::GetCollectionTree(array('menu' => $menu));
				$oCollections = $res['Collections'];
			}

			$userGroups = $USER->GetUserGroupArray();
			$res = CMedialib::GetAccessPermissionsArray($collectionId, $oCollections);

			$arOp[$key]  = array();
			foreach ($res as $group_id => $task_id)
			{
				if (in_array($group_id, $userGroups))
					$arOp[$key] = array_merge($arOp[$key], CTask::GetOperations($task_id, true));
			}
		}
		return $arOp[$key];
	}

	public static function CanDoOperation($operation, $collectionId=0, $userId = false, $menu = false)
	{
		if ($GLOBALS["USER"]->IsAdmin())
			return true;

		$arOp = CMedialib::GetOperations($collectionId, $menu);
		return in_array($operation, $arOp);
	}

	public static function GetAccessPermissionsArray($collectionId = 0, $oCollections = false)
	{
		static $arAllTasks;
		if (is_array($arAllTasks[$collectionId]))
			return $arAllTasks[$collectionId];

		$col = $oCollections[$collectionId];
		$arCols = array();
		$resTask = array();

		if ($col || $collectionId == 0)
		{
			$arCols[] = $collectionId;
			if (intVal($col['PARENT_ID']) > 0)
			{
				$col_ = $col;
				while($col_ && intVal($col_['PARENT_ID']) > 0)
				{
					$arCols[] = $col_['PARENT_ID'];
					$col_ = $oCollections[$col_['PARENT_ID']];
				}
			}
			$arCols[] = 0;
			$arPerm = CMedialib::_GetAccessPermissions($arCols);

			for($i = count($arCols); $i >= 0; $i--)
			{
				$colId = $arCols[$i];
				if (is_array($arPerm[$colId]))
				{
					for ($j = 0, $n = count($arPerm[$colId]); $j < $n; $j++)
						$resTask[$arPerm[$colId][$j]['GROUP_ID']] = $arPerm[$colId][$j]['TASK_ID'];
				}
			}
		}

		if (!is_array($arAllTasks))
			$arAllTasks = array();
		$arAllTasks[$collectionId] = $resTask;

		return $resTask;
	}

	public static function _GetAccessPermissions($arCols = array())
	{
		global $DB;

		$s = '0';
		for($i = 0, $l = count($arCols); $i < $l; $i++)
			$s .= ",".IntVal($arCols[$i]);

		$strSql = 'SELECT *
			FROM b_group_collection_task GCT
			WHERE GCT.COLLECTION_ID in ('.$s.')';

		$res = $DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arResult = array();
		while($arRes = $res->Fetch())
		{
			$colid = $arRes['COLLECTION_ID'];
			if (!is_array($arResult[$colid]))
				$arResult[$colid] = array();

			unset($arRes['COLLECTION_ID']);
			$arResult[$colid][] = $arRes;
		}

		return $arResult;
	}

	public static function getMaximumFileUploadSize()
	{
		return min(CUtil::Unformat(ini_get('post_max_size')), CUtil::Unformat(ini_get('upload_max_filesize')));
	}

	public static function ShowDialogScript($arConfig = array())
	{
		global $USER;

		CUtil::InitJSCore(array('ajax'));

		$strWarn = '';
		$arConfig['bReadOnly'] = false;
		$arConfig['lang'] = LANGUAGE_ID;

		$event = '';
		if (isset($arConfig['event']))
			$event = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['event']);
		if (strlen($event) <= 0)
			$strWarn .= GetMessage('ML_BAD_EVENT').'. ';

		$resultDest = "";
		$bDest = is_array($arConfig['arResultDest']);
		if ($bDest)
		{
			if (isset($arConfig['arResultDest']["FUNCTION_NAME"]))
			{
				$arConfig['arResultDest']["FUNCTION_NAME"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["FUNCTION_NAME"]);
				$bDest = strlen($arConfig['arResultDest']["FUNCTION_NAME"]) > 0;
				$resultDest = "FUNCTION";
			}
			elseif (isset($arConfig['arResultDest']["FORM_NAME"], $arConfig['arResultDest']["FORM_ELEMENT_NAME"]))
			{
				$arConfig['arResultDest']["FORM_NAME"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["FORM_NAME"]);
				$arConfig['arResultDest']["FORM_ELEMENT_NAME"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["FORM_ELEMENT_NAME"]);
				$bDest = strlen($arConfig['arResultDest']["FORM_NAME"]) > 0 && strlen($arConfig['arResultDest']["FORM_ELEMENT_NAME"]) > 0;
				$resultDest = "FORM";
			}
			elseif (isset($arConfig['arResultDest']["ELEMENT_ID"]))
			{
				$arConfig['arResultDest']["ELEMENT_ID"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["ELEMENT_ID"]);
				$bDest = strlen($arConfig['arResultDest']["ELEMENT_ID"]) > 0;
				$resultDest = "ID";
			}
			else
			{
				$bDest = false;
			}
		}
		if (!$bDest)
			$strWarn .= GetMessage('ML_BAD_RETURN').'. ';

		if (strlen($strWarn) <= 0)
		{
			?>
			<script>
			if (!window.BX && top.BX)
				window.BX = top.BX;

			<?CMedialib::AppendLangMessages();?>
			window.<?= $arConfig['event']?> = function(bLoadJS)
			{
				if (window.oBXMedialib && window.oBXMedialib.bOpened)
					return false;

				<?if(!CMedialib::CanDoOperation('medialib_view_collection', 0)):?>
					return alert(ML_MESS.AccessDenied);
				<?else:?>

				if (!window.BXMediaLib)
				{
					if (bLoadJS !== false)
					{
						// Append CSS
						BX.loadCSS("/bitrix/js/fileman/medialib/medialib.css");

						var arJS = [];
						if (!window.jsAjaxUtil)
							arJS.push("/bitrix/js/main/ajax.js?v=<?= filemtime($_SERVER["DOCUMENT_ROOT"].'/bitrix/js/main/ajax.js')?>");
						if (!window.jsUtils)
							arJS.push("/bitrix/js/main/utils.js?v=<?= filemtime($_SERVER["DOCUMENT_ROOT"].'/bitrix/js/main/utils.js')?>");
						if (!window.CHttpRequest)
							arJS.push("/bitrix/js/main/admin_tools.js?v=<?= filemtime($_SERVER["DOCUMENT_ROOT"].'/bitrix/js/main/admin_tools.js')?>");

						arJS.push("/bitrix/js/fileman/medialib/common.js?v=<?= filemtime($_SERVER["DOCUMENT_ROOT"].'/bitrix/js/fileman/medialib/common.js')?>");
						arJS.push("/bitrix/js/fileman/medialib/core.js?v=<?= filemtime($_SERVER["DOCUMENT_ROOT"].'/bitrix/js/fileman/medialib/core.js')?>");
						BX.loadScript(arJS);
					}
					return setTimeout(function(){<?=$arConfig['event']?>(false)}, 50);
				}

				<?CMedialib::ShowJS()?>
				<?
					$arSet = explode(',' , CUserOptions::GetOption("fileman", "medialib_user_set", '600,450,0'));
					$width = $arSet[0] ? intVal($arSet[0]) : 600;
					$height = $arSet[1] ? intVal($arSet[1]) : 450;
					$coll_id = $arSet[2] ? intVal($arSet[2]) : 0;
				?>
				window._mlUserSettings = window._mlUserSettings || {width: <?=$width?>, height: <?=$height?>, coll_id: <?=$coll_id?>}

				var oConfig =
				{
					sessid: "<?=bitrix_sessid()?>",
					thumbWidth : <?= COption::GetOptionInt('fileman', "ml_thumb_width", 140)?>,
					thumbHeight : <?= COption::GetOptionInt('fileman', "ml_thumb_height", 105) ?>,
					userSettings : window._mlUserSettings,
					resType: "<?= $resultDest?>",
					Types : <?= CUtil::PhpToJSObject(CMedialib::GetTypes($arConfig['types']))?>,
					arResultDest : <?= CUtil::PhpToJSObject($arConfig['arResultDest'])?>,
					rootAccess: {
						new_col: '<?= CMedialib::CanDoOperation('medialib_new_collection', 0)?>',
						edit: '<?= CMedialib::CanDoOperation('medialib_edit_collection', 0)?>',
						del: '<?= CMedialib::CanDoOperation('medialib_del_collection', 0)?>',
						new_item: '<?= CMedialib::CanDoOperation('medialib_new_item', 0)?>',
						edit_item: '<?= CMedialib::CanDoOperation('medialib_edit_item', 0)?>',
						del_item: '<?= CMedialib::CanDoOperation('medialib_del_item', 0)?>',
						access: '<?= CMedialib::CanDoOperation('medialib_access', 0)?>'
					},
					bCanUpload: <?= $USER->CanDoOperation('fileman_upload_files') ? 'true' : 'false'?>,
					bCanViewStructure: <?= $USER->CanDoOperation('fileman_view_file_structure') ? 'true' : 'false'?>,
					strExt : "<?= CUtil::JSEscape(CMedialib::GetMediaExtentions())?>",
					lang : "<?= $arConfig['lang']?>",
					description_id : '<?= CUtil::JSEscape($arConfig['description_id'])?>'
				};

				window.oBXMedialib = new BXMediaLib(oConfig);
				oBXMedialib.Open();
				<?endif;?>
			};
			</script>
			<?
		}
		else
		{
			echo '<font color="#FF0000">'.htmlspecialcharsbx($strWarn).'</font>';
		}
	}

	static function AttachJSScripts()
	{
		if(!defined("BX_B_MEDIALIB_SCRIPT_LOADED"))
		{
			// define("BX_B_MEDIALIB_SCRIPT_LOADED", true);
?>
BX.loadScript("/bitrix/js/main/file_dialog.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/main/file_dialog.js')?>");
<?
		}
	}

	public static function AppendLangMessages()
	{
?>
if (typeof ML_MESS === "undefined")
{
	var ML_MESS =
	{
		AccessDenied : '<?= GetMessageJS('ML_ACCESS_DENIED')?>',
		SessExpired : '<?= GetMessageJS('ML_SESS_EXPIRED')?>',
		DelCollection : '<?= GetMessageJS('ML_DEL_COLLECTION')?>',
		DelItem : '<?= GetMessageJS('ML_DEL_ITEM')?>',
		DelCollectionConf : '<?= GetMessageJS('ML_DEL_COLLECTION_CONFIRM')?>',
		DelItemConf : '<?= GetMessageJS('ML_DEL_ITEM_CONFIRM')?>',
		EditCollection : '<?= GetMessageJS('ML_EDIT_COLLECTION')?>',
		EditItem : '<?= GetMessageJS('ML_EDIT_ITEM')?>',
		NewCollection : '<?= GetMessageJS('ML_NEW_COLLECTION')?>',
		Collection : '<?= GetMessageJS('ML_COLLECTION')?>',
		ColLocEr : '<?= GetMessageJS('ML_COL_LOC_ER')?>',
		ColLocEr2 : '<?= GetMessageJS('ML_COL_LOC_ER2')?>',
		Item : '<?= GetMessageJS('ML_ITEM')?>',
		NewItem : '<?= GetMessageJS('ML_NEW_ITEM')?>',
		DelColFromItem : '<?= GetMessageJS('ML_DEL_COL2ITEM')?>',
		ItemNoColWarn : '<?= GetMessageJS('ML_COL2ITEM_WARN')?>',
		DateModified : '<?= GetMessageJS('ML_DATE_MODIFIED')?>',
		FileSize : '<?= GetMessageJS('ML_FILE_SIZE')?>',
		ImageSize : '<?= GetMessageJS('ML_IMAGE_SIZE')?>',
		CheckedColTitle : '<?= GetMessageJS('ML_CHECKED_COL_TITLE')?>',
		ItSourceError : '<?= GetMessageJS('ML_SOURCE_ERROR')?>',
		ItFileSizeError : '<?= GetMessageJS('ML_FILESIZE_ERROR')?>',
		ItNameError : '<?= GetMessageJS('ML_NAME_ERROR')?>',
		ItCollsError : '<?= GetMessageJS('ML_COLLS_ERROR')?>',
		ColNameError : '<?= GetMessageJS('ML_COL_NAME_ERROR')?>',
		DelItConfTxt : '<?= GetMessageJS('ML_DEL_CONF_TEXT')?>',
		DelItB1 : '<?= GetMessageJS('ML_DEL_IT_B1')?>',
		DelItB2 : '<?= GetMessageJS('ML_DEL_IT_B2')?>',
		CollAccessDenied : '<?= GetMessageJS('ML_COLL_ACCESS_DENIED')?>',
		CollAccessDenied2 : '<?= GetMessageJS('ML_COLL_ACCESS_DENIED2')?>',
		CollAccessDenied3: '<?= GetMessageJS('ML_COLL_ACCESS_DENIED3')?>',
		CollAccessDenied4: '<?= GetMessageJS('ML_COLL_ACCESS_DENIED4')?>',
		BadSubmit: '<?= GetMessageJS('ML_BAD_SUBMIT')?>',
		ItemExtError: '<?= GetMessageJS('ML_ITEM_EXT_ERROR')?>',
		EditItemError: '<?= GetMessageJS('ML_EDIT_ITEM_ERROR')?>',
		SearchResultEx: '<?= GetMessageJS('ML_SEARCH_RESULT_EX')?>',
		DelElConfirm: '<?= GetMessageJS('ML_DEL_EL_CONFIRM')?>',
		DelElConfirmYes: '<?= GetMessageJS('ML_DEL_EL_CONFIRM_YES')?>',
		SearchDef: '<?= GetMessageJS('ML_SEARCH_DEF')?>',
		NoResult: '<?= GetMessageJS('ML_SEARCH_NO_RESULT')?>',
		ViewItem : '<?= GetMessageJS('ML_VIEW_ITEM')?>',
		FileExt : '<?= GetMessageJS('ML_FILE_EXT')?>',
		CheckExtTypeConf : '<?= GetMessageJS('ML_CHECK_TYPE_EXT_CONF')?>'
	};
}
<?
	}

	public static function AppendLangMessagesEx()
	{
?>
ML_MESS.Edit = '<?= GetMessageJS('ML_EDIT')?>';
ML_MESS.Delete = '<?= GetMessageJS('ML_DELETE')?>';
ML_MESS.Access = '<?= GetMessageJS('ML_ACCESS')?>';
ML_MESS.AccessTitle = '<?= GetMessageJS('ML_ACCESS_TITLE')?>';

ML_MESS.AddElement = '<?= GetMessageJS('ML_ADD_ELEMENT')?>';
ML_MESS.AddElementTitle = '<?= GetMessageJS('ML_ADD_ELEMENT_TITLE')?>';
ML_MESS.AddCollection = '<?= GetMessageJS('ML_ADD_COLLECTION')?>';
ML_MESS.AddCollectionTitle = '<?= GetMessageJS('ML_ADD_COLLECTION_TITLE')?>';
ML_MESS.MultiDelConfirm = '<?= GetMessageJS('ML_MULTI_DEL_CONFIRM')?>';
ML_MESS.Decreased = '<?= GetMessageJS('ML_DECREASED')?>';

ML_MESS.ChangeType = '<?= GetMessageJS('ML_CHANGE_TYPE')?>';
ML_MESS.ChangeTypeTitle = '<?= GetMessageJS('ML_CHANGE_TYPE_TITLE')?>';
ML_MESS.ChangeTypeError = '<?= GetMessageJS('ML_CHANGE_TYPE_ERROR')?>';
ML_MESS.ChangeTypeChildConf = '<?= GetMessageJS('ML_CHANGE_TYPE_CHILD_CONF')?>';
ML_MESS.Save = '<?= GetMessageJS('ML_SAVE')?>';
<?
	}

	public static function Start($Params)
	{
		$Params['bReadOnly'] = false;
		CMedialib::BuildDialog($Params);

		// TODO: Check access
		?>#ML_SUBDIALOGS_BEGIN#<?
		CMedialib::BuildAddCollectionDialog($Params);
		CMedialib::BuildAddItemDialog($Params);
		CMedialib::BuildConfirmDialog($Params);
		CMedialib::BuildViewItemDialog($Params);
		$exParams = array('types' => $Params['types']);
		?>#ML_SUBDIALOGS_END#

		<script><?CMedialib::GetCollections($exParams);?></script>
		<?
	}

	public static function BuildDialog($Params)
	{
		?>
		#ML_MAIN_DIALOG_BEGIN#
		<form name="medialib_form"><table  id="ml_frame" class="ml-frame"><tr>
		<td class="ml-title-cell">
			<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('bxmedialib'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="ml-iconkit ml-dd-dot" src="/bitrix/images/1.gif" /></td><td class="ml-diad-title" id="ml_diag_title"><?=GetMessage('ML_MEDIALIB')?></td><td id="bxml_close" class="ml-close" title="<?=GetMessage('ML_CLOSE')?>"><img src="/bitrix/images/1.gif"></td></tr></table>
		</td></tr>
		<tr><td class="ml-content-cell">
		<div class="ml-head-cont" id="ml_head_cont">
			<table><tr><td class="ml-left">
			<div class="ml-breadcrumbs" id="ml_breadcrumbs"></div>
			</td><td class="ml-right">
			<input class="ml-search ml-search-empty" id="medialib_search" type="text" value="<?=GetMessage('ML_SEARCH_DEF')?>"/>
			</td></tr></table>
		</div>
		<div class="ml-left-sec" id="ml_left_cont">
			<div id="ml_type_cont" class="ml-type-cont"></div>
			<div class="ml-collect-cont" id="ml_coll_cont"><div class="ml-no-colls"> - <?= GetMessage('ML_NO_COLS')?> - </div></div>
		</div>
		<div class="ml-right-sec" id="ml_right_cont">
			<div class="ml-list-cont" id="ml_list_cont"><div class="ml-list-noitems"> - <?= GetMessage('ML_NO_ITEMS')?> - </div></div>
			<div class="ml-info-cont"  id="ml_info_wnd">
				<div class="ml-info-noinfo"> - <?= GetMessage('ML_NO_ITEM_INFO')?> - </div>
				<table class="ml-info-tbl">
					<tr>
						<td colSpan="2">
							<div class="ml-info-name" id="ml_info_name"></div>
							<div class="ml-info-collections" id="ml_info_colls"></div>
						</td>
					</tr>
					<tr>
						<td style="width: 50%;">
							<span><?= GetMessage('ML_KEYWORDS')?>:</span>
							<span id="ml_info_keys"></span>
						</td>
						<td rowSpan="2" style="width: 50%; vertical-align: top;">
							<span><?= GetMessage('ML_DESC')?>:</span>
							<div class="ml-info-desc" id="ml_info_desc"></div>
						</td>
					</tr>
					<tr>
						<td style="vertical-align: top;">
							<div class="ml-info-details" id="ml_info_details"></div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="ml-buttons-cont" id="ml_but_cont">
			<table><tr>
				<td class="ml-left">
				<?if (!$Params['bReadOnly']):?>
				<a id="ml_add_collection" href="javascript:void(0)" title="<?=GetMessage('ML_ADD_COLLECTION_TITLE')?>" class="ml-add-el-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('ML_ADD_COLLECTION')?></a>

				<a id="ml_add_item" href="javascript:void(0)" title="<?=GetMessage('ML_ADD_ELEMENT_TITLE')?>" class="ml-add-el-link"><img src="/bitrix/images/1.gif" /><?=GetMessage('ML_ADD_ELEMENT')?></a>
				<?endif;?>
				</td><td class="ml-right">
				<input id="medialib_but_save" type="button" value="<?=GetMessage('ML_SELECT')?>" />
				<input id="medialib_but_cancel" type="button" value="<?=GetMessage('ML_CANCEL')?>" />
			</td></tr></table>
		</div>
		</td></tr>
		</table>
		</form>
		<div id="bxml_resizer" class="ml-resizer"></div>
		#ML_MAIN_DIALOG_END#
		<?
	}

	public static function BuildAddCollectionDialogAdmin($Params)
	{
		?>
		<div id="mlsd_coll" class="mlsd_admin">
			<table>
			<tr><td><b><?=GetMessage('ML_NAME')?>:</b></td><td><input type="text" id="mlsd_coll_name" /></td></tr>
			<tr><td style="vertical-align: top;"><?=GetMessage('ML_DESC')?>:</td><td><textarea id="mlsd_coll_desc" rows="2" cols="21"></textarea></td></tr>
			<tr><td><?=GetMessage('ML_KEYWORDS')?>:</td><td><input type="text" id="mlsd_coll_keywords" /></td></tr>
			<tr><td><?=GetMessage('ML_PLACE')?>:</td>
			<td><select id="mlsd_coll_parent" style="width: 190px;"><option value="0"><?= GetMessage('ML_UPPER_LEVEL')?></option></select></td></tr>
			</table>
		</div>
		<?
	}

	public static function BuildAddCollectionDialog($Params)
	{
		?>
		<div id="mlsd_coll" class="mlsd"><table class="mlsd-frame"><tr>
		<td class="ml-title-cell">
			<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('mlsd_coll'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="ml-iconkit ml-dd-dot" src="/bitrix/images/1.gif" /></td><td class="ml-diad-title"><span id="mlsd_coll_title"></span></td><td id="mlsd_coll_close" class="ml-close" title="<?=GetMessage('ML_CLOSE')?>"><img src="/bitrix/images/1.gif"></td></tr></table>
		</td></tr>
		<tr><td class="ml-content-cell">
			<table class="mlsd-fields-tbl">
			<tr><td><b><?=GetMessage('ML_NAME')?>:</b></td><td><input type="text" id="mlsd_coll_name" /></td></tr>
			<tr><td style="vertical-align: top;"><?=GetMessage('ML_DESC')?>:</td><td><textarea id="mlsd_coll_desc" rows="2" cols="21"></textarea></td></tr>
			<tr><td><?=GetMessage('ML_KEYWORDS')?>:</td><td><input type="text" id="mlsd_coll_keywords" /></td></tr>
			<tr><td><?=GetMessage('ML_PLACE')?>:</td>
			<td><select id="mlsd_coll_parent" style="width: 190px;"><option value="0"><?= GetMessage('ML_UPPER_LEVEL')?></option></select></td></tr>
			</table>
		</td></tr>
		<tr><td class="ml-buttons-cell">
			<input id="mlsd_coll_save" type="button" value="<?=GetMessage('ML_SAVE')?>">
			<input id="mlsd_coll_cancel" type="button" value="<?=GetMessage('ML_CANCEL')?>">
		</td></tr>
		</table>
		</div>
		<?
	}

	public static function BuildAddItemDialogAdmin($Params)
	{
		?>
		<div id="mlsd_item" class="mlsd_admin">
		<table>
		<tr><td class="ml-content-cell">
<form name="ml_item_form" action="/bitrix/admin/fileman_medialib.php?action=edit_item&<?=bitrix_sessid_get()?>" onsubmit="return parent.oBXMediaLib.EditItemDialogOnsubmit();" method="post" enctype="multipart/form-data"><table class="mlsd-ifrm-tbl">
		<tr><td colSpan="2">
			<div id="mlsd_fname_cont">
				<b><?=GetMessage('ML_FILE')?>:</b><span style="padding: 0px 15px" id="ml_file_name"></span>
			</div>
			<div id="mlsd_load_cont">
				<b><label for="ml_load_file"><?=GetMessage('ML_FILE')?>:</label></b>
				<input id="ml_load_file" type="file" name="load_file" style="margin-left: 15px; width:200px;">
			</div>
			<div id="mlsd_select_cont" style="display: none;">
				<b><label for="mlsd_item_path"><?=GetMessage('ML_FILE')?>:</label></b>
				<input type="text" size="25" value="" id="mlsd_item_path" style="margin-left: 15px;  width: 280px;" name="item_path">
				<input type="button" id="mlsd_open_fd" value="..." style="width: 30px;">
			</div>
			<div style="text-align: right; padding-right: 20px;">
			<div style="float: left; text-align: left; margin-top: -2px;">
			<a id="mlsd_fname_change" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_CHANGE_FILE_TITLE')?>">(<?=GetMessage('ML_CHANGE')?>)</a>
			<a id="mlsd_fname_change_back" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_CHANGE_UNDO_TITLE')?>">(<?=GetMessage('ML_CHANGE_UNDO')?>)</a>
			</div>
			<a id="mlsd_select_fd" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_SELECT_FILE_TITLE')?>"><?=GetMessage('ML_SELECT_FILE')?></a>
			<a id="mlsd_select_pc" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_LOAD_FILE_TITLE')?>" style="display: none;"><?=GetMessage('ML_LOAD_FILE')?></a>
			</div>
		</td></tr>
		<tr><td><b><label for="mlsd_item_name"><?=GetMessage('ML_NAME')?>:</label></b><br /><input type="text" id="mlsd_item_name" name="item_name"/></td>
			<td rowSpan="3" style="padding-top: 10px;">
			<div class="mlsd-prev-cont"><span id="mlsd_no_preview"><?= GetMessage('ML_NO_PREVIEW')?></span><img id="mlsd_item_thumb" src="/bitrix/images/1.gif" /></div>
			<div class="mlsd-size-cont" id="mlsd_item_size"  title="<?=GetMessage('ML_SIZE_IN_PX')?>"></div>
			</td></tr>
		<tr>
			<td style="vertical-align: top;"><label for="mlsd_item_desc"><?=GetMessage('ML_DESC')?>:</label><br />
			<textarea id="mlsd_item_desc" rows="2" cols="26" name="item_desc"></textarea></td>
		</tr>
		<tr><td><label for="mlsd_item_keywords"><?=GetMessage('ML_KEYWORDS')?>:<br /></label><input type="text" id="mlsd_item_keywords" name="item_keywords"/></td></tr>
		<tr><td colSpan="2">
		<div class="mlsd-col-cont">
			<div class="mlsd-col-label"><label for="mlsd_coll_sel"><?=GetMessage('ML_COLLECTIONS')?>:</label></div>
			<div class="mlsd-col-sel"><select title="<?= GetMessage('ML_ADD_COL2ITEM')?>" id="mlsd_coll_sel"><option value="0"><?= GetMessage('ML_COL_SELECT')?></option></select></div>
		</div>
		</td></tr>
	</table>

	<? /* <input type="hidden" name="MAX_FILE_SIZE" value="1000000000">*/?>
	<input id="mlsd_item_collections" type="hidden" name="item_collections" value="">
	<input id="mlsd_item_id" type="hidden" name="id" value="">
	<input id="mlsd_source_type" type="hidden" name="source_type" value="PC">
</form>
		</td></tr>
		</table>
		</div>
		<?
		CAdminFileDialog::ShowScript(Array
			(
				"event" => "mlOpenFileDialog",
				"arResultDest" => Array("FUNCTION_NAME" => "mlOnFileDialogSave"),
				"arPath" => Array(),
				"select" => 'F',
				"operation" => 'O',// O - open, S - save
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"fileFilter" => CMedialib::GetMediaExtentions(),
				"allowAllFiles" => false,
				"SaveConfig" => true
			)
		);
	}

	public static function BuildAddItemDialog($Params)
	{
		?>
		<div id="mlsd_item" class="mlsd"><table class="mlsd-frame"><tr>
		<td class="ml-title-cell">
			<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('mlsd_item'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="ml-iconkit ml-dd-dot" src="/bitrix/images/1.gif" /></td><td class="ml-diad-title"><span id="mlsd_item_title"></span></td><td id="mlsd_item_close" class="ml-close" title="<?=GetMessage('ML_CLOSE')?>"><img src="/bitrix/images/1.gif"></td></tr></table>
		</td></tr>
		<tr><td class="ml-content-cell">
			<div id="mlsd_item_upload" style="overflow: hidden;"><iframe class="mlsd-iframe" id="mlsd_iframe_upload" src="javascript:''" frameborder="0"></iframe></div>
		</td></tr>
		<tr><td class="ml-buttons-cell">
			<input id="mlsd_item_save" type="button" value="<?=GetMessage('ML_SAVE')?>">
			<input id="mlsd_item_cancel" type="button" value="<?=GetMessage('ML_CANCEL')?>">
		</td></tr>
		</table>
		</div>
		<?
		CAdminFileDialog::ShowScript(Array
			(
				"event" => "mlOpenFileDialog",
				"arResultDest" => Array("FUNCTION_NAME" => "mlOnFileDialogSave"),
				"arPath" => Array(),
				"select" => 'F',
				"operation" => 'O',// O - open, S - save
				"showUploadTab" => true,
				"showAddToMenuTab" => false,
				"fileFilter" => CMedialib::GetMediaExtentions(),
				"allowAllFiles" => false,
				"SaveConfig" => true
			)
		);
	}

	public static function ShowUploadForm($Params)
	{
		?>
<HTML>
<HEAD>
<style>
	body {margin:0px !important; overflow: hidden;}
	body *{font-family:Verdana,Arial,Helvetica,sans-serif; font-size: 13px; color: #000;}
	form {margin:0px !important;}
	table.mlsd-ifrm-tbl {width: 400px; height: 265px; margin: 3px;}
	a.mlsd-up-link{text-decoration: none; color: #6E8C9B; font-size: 11px;}
	table.mlsd-ifrm-tbl input{width: 220px;}
	div.mlsd-col-cont{height: 70px;}
	div.mlsd-col-label, div.mlsd-col-sel{font-weight: bold; float: left; padding: 2px; margin: 2px;}
	div.mlsd-col-sel select{width: 90px; display: block; margin-top: -2px;}
	div.mlsd-ch-col{float: left; border: 1px solid #6E8C9B; width: 80px; height: 20px; padding: 0px; overflow: hidden; margin: 2px; position: relative; background: url(/bitrix/images/fileman/medialib/group_bg.gif) repeat-x scroll left top;}
	div.mlsd-ch-col span{white-space: nowrap; font-size: 12px !important; display: block; margin: 2px 0 0 2px;}
	div.mlsd-ch-col img.ml-col-del{width: 17px; height: 18px; background-image: url(/bitrix/images/fileman/medialib/iconkit.gif); position: absolute; display: none; background-position: 0px -60px; top: 1px; right: 1px; cursor: pointer;}
	div.col-over img.ml-col-del{display: block !important;}
	div.mlsd-prev-cont{width: 150px; height: 140px; border: 1px solid #6E8C9B; text-align: center;}
	div.mlsd-prev-cont img{margin: 2px;}
	div.mlsd-prev-cont span{color: #6E8C9B; font-size: 11px; display: block; padding: 2px;}
	select option.opt-checked{color: #808080; font-weight: bold; background-color: #F2F6F8;}
	div.mlsd-size-cont{text-align: center; color: #808080;}
</style>
</HEAD>
<BODY style="margin:0px !important;">
<form name="ml_item_form" action="/bitrix/admin/fileman_medialib.php?action=edit_item&<?=bitrix_sessid_get()?>" onsubmit="return parent.oBXMediaLib.EditItemDialogOnsubmit();" method="post" enctype="multipart/form-data"><table class="mlsd-ifrm-tbl">
		<tr><td colSpan="2">
			<div id="mlsd_fname_cont">
				<b><?=GetMessage('ML_FILE')?>:</b><span style="padding: 0px 15px" id="ml_file_name"></span>
			</div>
			<div id="mlsd_load_cont">
				<b><label for="ml_load_file"><?=GetMessage('ML_FILE')?>:</label></b>
				<input id="ml_load_file" type="file" name="load_file" style="margin-left: 15px; width:200px;">
				<input id="ml_load_max_size" type="hidden" name="ml_load_max_size" value="<?=CMedialib::getMaximumFileUploadSize()?>">
			</div>
			<div id="mlsd_select_cont" style="display: none;">
				<b><label for="mlsd_item_path"><?=GetMessage('ML_FILE')?>:</label></b>
				<input type="text" size="25" value="" id="mlsd_item_path" style="margin-left: 15px;  width: 280px;" name="item_path">
				<input type="button" id="mlsd_open_fd" value="..." style="width: 30px;">
			</div>
			<div style="text-align: right; padding-right: 20px;">
			<div style="float: left; text-align: left; margin-top: -2px;">
			<a id="mlsd_fname_change" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_CHANGE_FILE_TITLE')?>">(<?=GetMessage('ML_CHANGE')?>)</a>
			<a id="mlsd_fname_change_back" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_CHANGE_UNDO_TITLE')?>">(<?=GetMessage('ML_CHANGE_UNDO')?>)</a>
			</div>
			<a id="mlsd_select_fd" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_SELECT_FILE_TITLE')?>"><?=GetMessage('ML_SELECT_FILE')?></a>
			<a id="mlsd_select_pc" href="javascript:void(0)" class="mlsd-up-link" title="<?=GetMessage('ML_LOAD_FILE_TITLE')?>" style="display: none;"><?=GetMessage('ML_LOAD_FILE')?></a>
			</div>
		</td></tr>
		<tr><td><b><label for="mlsd_item_name"><?=GetMessage('ML_NAME')?>:</label></b><br /><input type="text" id="mlsd_item_name" name="item_name"/></td>
			<td rowSpan="3" style="padding-top: 10px;">
			<div class="mlsd-prev-cont"><span id="mlsd_no_preview"><?= GetMessage('ML_NO_PREVIEW')?></span><img id="mlsd_item_thumb" src="/bitrix/images/1.gif" /></div>
			<div class="mlsd-size-cont" id="mlsd_item_size"  title="<?=GetMessage('ML_SIZE_IN_PX')?>"></div>
			</td></tr>
		<tr>
			<td style="vertical-align: top;"><label for="mlsd_item_desc"><?=GetMessage('ML_DESC')?>:</label><br />
			<textarea id="mlsd_item_desc" rows="2" cols="26" name="item_desc"></textarea></td>
		</tr>
		<tr><td><label for="mlsd_item_keywords"><?=GetMessage('ML_KEYWORDS')?>:<br /></label><input type="text" id="mlsd_item_keywords" name="item_keywords"/></td></tr>
		<tr><td colSpan="2">
		<div class="mlsd-col-cont">
			<div class="mlsd-col-label"><label for="mlsd_coll_sel"><?=GetMessage('ML_COLLECTIONS')?>:</label></div>
			<div class="mlsd-col-sel"><select title="<?= GetMessage('ML_ADD_COL2ITEM')?>" id="mlsd_coll_sel"><option value="0"><?= GetMessage('ML_COL_SELECT')?></option></select></div>
		</div>
		</td></tr>
	</table>

	<? /* <input type="hidden" name="MAX_FILE_SIZE" value="1000000000">*/?>
	<input id="mlsd_item_collections" type="hidden" name="item_collections" value="">
	<input id="mlsd_item_id" type="hidden" name="id" value="">
	<input id="mlsd_source_type" type="hidden" name="source_type" value="PC">
</form>
</BODY>
</HTML>
<?
	}

	public static function BuildConfirmDialog($Params)
	{
		?>
		<div id="ml_colfirm_dialog" class="mlsd mlsd-confirm">
			<div id="ml_confd_text" class="ml-confd-text"></div>
			<input id="ml_confd_b1" type="button" value="b1" />
			<input id="ml_confd_b2" type="button" value="b2" />
			<input id="ml_confd_cancel" type="button" value="<?=GetMessage('ML_CANCEL')?>" />
		</div>
		<?
	}

	public static function BuildViewItemDialog($Params)
	{
		?>
		<div id="mlsd_view_item" class="mlsd"><table class="mlsd-frame"><tr>
		<td class="ml-title-cell">
			<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('mlsd_view_item'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="ml-iconkit ml-dd-dot" src="/bitrix/images/1.gif" /></td><td class="ml-diad-title"><?= GetMessage('ML_VIEW_ITEM')?></td><td id="mlsd_viewit_close" class="ml-close" title="<?=GetMessage('ML_CLOSE')?>"><img src="/bitrix/images/1.gif"></td></tr></table>
		</td></tr>
		<tr><td class="ml-content-cell">
			<div id="mlsd_info_cont" class="mlvi-info-cnt">
				<table class="mlvi-info-tbl">
					<tr><td><div class="mlvi-info-name" id="mlvi_info_name"></div></td></tr>
					<tr><td><a id="mlvi_info_link" href="javascript: void(0);" title="<?= GetMessage("ML_DOWNLOAD_LINK")?>"><?= GetMessage('ML_DOWNLOAD_LINK')?></a></td></tr>
					<tr><td><a id="mlvi_info_copy_link" href="javascript: void(0);" title="<?= GetMessage("ML_DOWNLOAD_LINK_TITLE")?>"><?= GetMessage("ML_DOWNLOAD_LINK_TITLE")?></a></td></tr>
					<tr><td><input style="display:none;" id="mlvi_info_copy_input" value></td></tr>
					<tr><td class="mlvi-new-row">
						<div class="mlvi-info-details" id="mlvi_info_details"></div>
					</td></tr>
					<tr><td class="small-grey" id="mlvi_info_colls">
						<span><?= GetMessage('ML_COLLECTIONS')?>: </span>
					</td></tr>
					<tr>
						<td class="small-grey" ><span><?= GetMessage('ML_KEYWORDS')?>:</span>
						<span id="mlvi_info_keys"></span></td>
					</tr>
					<tr><td class="mlvi-new-row">
						<span style="font-size: 11px !important;"><?= GetMessage('ML_DESC')?>:</span>
						<div class="ml-info-desc" id="mlvi_info_desc"></div>
					</td></tr>
				</table>
			</div>
			<div id="mlsd_item_cont" class="mlvi-img-cnt"><?/*<img id="mlsd_viewit_img" src="/bitrix/images/1.gif" />*/?></div>
		</td></tr>
		<tr><td class="ml-buttons-cell">
			<input id="mlsd_viewit_del" type="button" value="<?=GetMessage('ML_DELETE')?>">
			<input id="mlsd_viewit_edit" type="button" value="<?=GetMessage('ML_EDIT')?>">
			<input id="mlsd_viewit_cancel" type="button" value="<?=GetMessage('ML_CANCEL')?>">
		</td></tr>
		</table>
		</div>
		<?
	}

	public static function BuildChangeType($Params)
	{
		?>
		<div id="mlsd_change_type" class="mlsd"><table class="mlsd-frame"><tr>
		<td class="ml-title-cell">
			<table onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('mlsd_change_type'));"><tr><td style="width: 10px; padding-left: 3px;"><img class="ml-iconkit ml-dd-dot" src="/bitrix/images/1.gif" /></td><td class="ml-diad-title"><?= GetMessage('ML_CHANGE_TYPE_DIALOG')?></td><td id="mlsd_chtype_close" class="ml-close" title="<?=GetMessage('ML_CLOSE')?>"><img src="/bitrix/images/1.gif"></td></tr></table>
		</td></tr>
		<tr><td class="ml-content-cell">
			<table class="mlsd-fields-tbl">
				<tr>
					<td><b><?=GetMessage('ML_CHOOSE_TYPE')?>:</b></td>
					<td><select id="mlsd_chtype_type" style="width: 190px;"><option value="none">- <?= GetMessage('ML_COL_SELECT')?> -</option></select></td>
				</tr>
				<tr>
					<td><?=GetMessage('ML_PLACE')?>:</td>
					<td><select id="mlsd_chtype_parent" style="width: 190px;"><option value="0"><?= GetMessage('ML_UPPER_LEVEL')?></option></select></td>
				</tr>
			</table>
		</td></tr>
		<tr><td class="ml-buttons-cell">
			<input id="mlsd_chtype_save" type="button" value="<?=GetMessage('ML_SAVE')?>">
			<input id="mlsd_chtype_cancel" type="button" value="<?=GetMessage('ML_CANCEL')?>">
		</td></tr>
		</table>
		</div>
		<?
	}

	public static function ShowJS()
	{
		?>
		BX.loadCSS("/bitrix/js/fileman/medialib/medialib.css");
		if (!window.jsUtils && top.jsUtils)
			window.jsUtils = top.jsUtils;
		if (!window.jsUtils)
			BX.loadScript('/bitrix/js/main/utils.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/main/utils.js')?>');

		if (!window.CHttpRequest && top.CHttpRequest)
			window.CHttpRequest = top.CHttpRequest;
		if (!window.CHttpRequest)
			BX.loadScript('/bitrix/js/main/admin_tools.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/main/admin_tools.js')?>');

		if (!window.jsAjaxUtil && top.jsAjaxUtil)
			window.jsAjaxUtil = top.jsAjaxUtil;
		if (!window.jsAjaxUtil)
			BX.loadScript('/bitrix/js/main/ajax.js?v=<?=@filemtime($_SERVER['DOCUMENT_ROOT'].'/bitrix/js/main/ajax.js')?>');
		<?
	}

	public static function GetCollections(&$exParams)
	{
		$bCountPermissions = isset($exParams['bCountPermissions']) && $exParams['bCountPermissions'] === true;
		$exParams['arCountPerm'] = array('new_col' => 0, 'edit' => 0, 'del' => 0, 'new_item' => 0, 'edit_item' => 0, 'del_item' => 0, 'access' => 0);

		?>window.MLCollections = [<?
		$arCol = CMedialibCollection::GetList(array('arFilter' =>
			array(
				'ACTIVE' => 'Y',
				'TYPES' => $exParams['types']
			)
		));
		$commonEdit = false;
		$commonItemEdit = false;
		$arResCol = array();
		for ($i = 0, $l = count($arCol); $i < $l; $i++)
		{
			if (!CMedialibCollection::IsViewable($arCol[$i], $arCol))
				continue;

			$id = $arCol[$i]['ID'];
			$arCol[$i]['PERMISSIONS'] = array(
				'new_col' => CMedialib::CanDoOperation('medialib_new_collection', $arCol[$i]['ID']),
				'edit' => CMedialib::CanDoOperation('medialib_edit_collection', $arCol[$i]['ID']),
				'del' => CMedialib::CanDoOperation('medialib_del_collection', $arCol[$i]['ID']),
				'new_item' => CMedialib::CanDoOperation('medialib_new_item', $arCol[$i]['ID']),
				'edit_item' => CMedialib::CanDoOperation('medialib_edit_item', $arCol[$i]['ID']),
				'del_item' => CMedialib::CanDoOperation('medialib_del_item', $arCol[$i]['ID']),
				'access' => CMedialib::CanDoOperation('medialib_access', $arCol[$i]['ID'])
			);

			$accStr = '';
			foreach($exParams['arCountPerm'] as $key => $el)
			{
				if ($bCountPermissions)
					$exParams['arCountPerm'][$key] += intVal($arCol[$i]['PERMISSIONS'][$key]);
				$accStr .= $key.": '".$arCol[$i]['PERMISSIONS'][$key]."', ";
			}
			$accStr = rtrim($accStr, ' ,');
			?>
{
	id: <?= $arCol[$i]['ID']?>,
	name: '<?= CMedialib::Escape($arCol[$i]['NAME'])?>',
	desc: '<?= CMedialib::Escape($arCol[$i]['DESCRIPTION'])?>',
	date: '<?= $arCol[$i]['DATE_UPDATE']?>',
	keywords: '<?= CMedialib::Escape($arCol[$i]['KEYWORDS'])?>',
	parent: <?= intVal($arCol[$i]['PARENT_ID']) > 0 ? intVal($arCol[$i]['PARENT_ID']) : '0'?>,
	access: {<?= $accStr?>},
	type: '<?= $arCol[$i]['ML_TYPE']?>'
}
			<?
			if ($i != $l - 1)
				echo ',';
			$arResCol[] = $arCol[$i];
		}
		?>];<?
		return $arResCol;
	}

	public static function DelCollection($id, $arIds = array())
	{
		if (!CMedialib::CanDoOperation('medialib_del_collection', $id))
			return false;

		for($i = 0, $l = count($arIds); $i < $l; $i++)
		{
			if (CMedialib::CanDoOperation('medialib_del_collection', $arIds[$i]))
				CMedialibCollection::Delete($arIds[$i], false);
		}

		return CMedialibCollection::Delete($id);
	}

	public static function EditCollection($Params)
	{
		if ($Params['id'] && !CMedialib::CanDoOperation('medialib_edit_collection', $Params['id']) ||
			!$Params['id'] && !CMedialib::CanDoOperation('medialib_new_collection', $Params['parent']))
			return;

		return CMedialibCollection::Edit(array(
			'arFields' => array(
				'ID' => $Params['id'],
				'NAME' => $Params['name'],
				'DESCRIPTION' => $Params['desc'],
				'OWNER_ID' => $GLOBALS['USER']->GetId(),
				'PARENT_ID' => $Params['parent'],
				'KEYWORDS' => $Params['keywords'],
				'ACTIVE' => "Y",
				'ML_TYPE' => $Params['type']
			)
		));
	}

	public static function EditItem($Params)
	{
		$bOpName = $Params['id'] ? 'medialib_edit_item' : 'medialib_new_item';
		$arCols_ = explode(',', $Params['item_collections']);
		$arCols = array();
		for ($i = 0, $l = count($arCols_); $i < $l; $i++)
		{
			if (intVal($arCols_[$i]) > 0 && CMedialib::CanDoOperation($bOpName, $arCols_[$i])) // Check access
				$arCols[] = intVal($arCols_[$i]);
		}

		if (count($arCols) > 0)
		{
			if ($Params['source_type'] == 'PC')
				$Params['path'] = false;
			else if($Params['source_type'] == 'FD')
				$Params['file'] = false;

			$res = CMedialibItem::Edit(array(
				'file' => $Params['file'],
				'path' => $Params['path'],
				'arFields' => array(
					'ID' => $Params['id'],
					'NAME' => $Params['name'],
					'DESCRIPTION' => $Params['desc'],
					'KEYWORDS' => $Params['keywords']
				),
				'arCollections' => $arCols
			));

			if ($res):

			if (!isset($res['DATE_UPDATE']) && isset($res['TIMESTAMP_X']))
				$res['DATE_UPDATE'] = $res['TIMESTAMP_X'];
			?>
			<script>
			top.bx_req_res = {
				id: <?=intVal($res['ID'])?>,
				name: '<?= CMedialib::Escape($res['NAME'])?>',
				desc: '<?= CMedialib::Escape($res['DESCRIPTION'])?>',
				keywords: '<?= CMedialib::Escape($res['KEYWORDS'])?>',
				<?if (isset($res['FILE_NAME'])):?>file_name: '<?= CMedialib::Escape($res['FILE_NAME'])?>',<?endif;?>
				<?if (isset($res['DATE_UPDATE'])):?>date_mod: '<?= CMedialib::GetUsableDate($res['DATE_UPDATE'])?>',<?endif;?>
				<?if (isset($res['FILE_SIZE'])):?>file_size: '<?= CMedialib::GetUsableSize($res['FILE_SIZE'])?>',<?endif;?>
				<?if (isset($res['THUMB_PATH'])):?>thumb_path: '<?= CMedialib::Escape($res['THUMB_PATH'])?>',<?endif;?>
				<?if (isset($res['PATH'])):?>path: '<?= CMedialib::Escape($res['PATH'])?>',<?endif;?>
				<?if (isset($res['TYPE'])):?>type: '<?= $res['TYPE']?>',<?endif;?>
				height: <?= ($res['HEIGHT'] ? $res['HEIGHT'] : '0')?>,
				width: <?= ($res['WIDTH'] ? $res['WIDTH'] : '0')?>
			};

			top._ml_items_colls = [<?
			for ($i = 0, $l = count($arCols); $i < $l; $i++)
				echo $arCols[$i].($i != $l - 1 ? ',' : '');
			?>];
			</script>
			<? else: ?>
			<script>top.bx_req_res = false;</script>
			<?endif;
		}
	}

	public static function GetCollectionTree($Params = array())
	{
		$arColTree = array();
		$arColTemp = array();
		$Collections = array();
		$arCol = $Params['menu'] ? CMedialibCollection::GetList(array('arFilter' => array('ACTIVE' => 'Y', 'PARENT_ID' => 0))) : CMedialibCollection::GetList(array('arFilter' => array('ACTIVE' => 'Y')));
		$iter = 0;

		for ($i = 0, $l = count($arCol); $i < $l; $i++)
		{
			if (isset($Params['CheckAccessFunk']) && !call_user_func($Params['CheckAccessFunk'], $arCol[$i]['ID']))
				continue;

			if (!CMedialib::_buildCollection($arCol[$i], $i, $arColTree, $Collections, $Params))
				$arColTemp[] = array($arCol[$i], $i);
		}

		while(count($arColTemp) > 0 && $iter < 50)
		{
			$newAr = array();
			for ($i = 0, $l = count($arColTemp); $i < $l; $i++)
			{
				if (isset($Params['CheckAccessFunk']) && !call_user_func($Params['CheckAccessFunk'], $arCol[$i]['ID']))
					continue;

				if (!CMedialib::_buildCollection($arColTemp[$i][0], $arColTemp[$i][1], $arColTree, $Collections, $Params))
					$newAr[] = $arColTemp[$i];
			}
			$arColTemp = $newAr;
			$iter++;
		}

		if ($Params['checkByType'] && $Params['typeId'] > 0)
		{
			$arType = CMedialib::GetTypeById($Params['typeId']);
			if ($arType)
			{
				foreach ($Collections as $id => $col)
				{
					// Del collection escription if it has another type
					if (!CMedialib::CompareTypesEx($Collections[$id]['ML_TYPE'], $arType))
						unset($Collections[$id]);
				}
			}
		}

		return array('arColTree' => $arColTree, 'Collections' => $Collections);
	}

	public static function _buildCollection($Col, $ind, &$arColTree, &$Collections, $Params = array())
	{
		if ($Params['CHECK_ACCESS'] === true && !CMedialib::CanDoOperation('medialib_view_collection', $Col['ID']))
			return true;

		if (!$Col['PARENT_ID']) // Root element
			$arColTree[] = array('id' => $Col['ID'], 'child' => array());
		else if ($Collections[$Col['PARENT_ID']])
			CMedialib::_findChildInColTree($arColTree, $Col['PARENT_ID'], $Col['ID']);
		else
			return false;

		$Collections[$Col['ID']] = $Col;
		return true;
	}

	public static function _findChildInColTree(&$arr, $id, $colId)
	{
		for ($i = 0, $l = count($arr); $i < $l; $i++)
		{
			if ($arr[$i]['id'] == $id)
			{
				$arr[$i]['child'][] = array('id' => $colId, 'child' => array());
				return true;
			}
			else if (count($arr[$i]['child']) > 0)
			{
				if (CMedialib::_findChildInColTree($arr[$i]['child'], $id, $colId))
					return true;
			}
		}
		return false;
	}

	public static function _BuildCollectionsSelectOptions($Collections = false, $arColTree = false, $level = 0, $selected = false)
	{
		if ($Collections === false && $arColTree === false)
		{
			$res = CMedialib::GetCollectionTree();
			$Collections = $res['Collections'];
			$arColTree = $res['arColTree'];
		}

		$str = '';
		for ($i = 0, $l = count($arColTree); $i < $l; $i++)
		{
			//if ($type !== false && )
			$col = $Collections[$arColTree[$i]['id']];
			if (!is_array($col))
				continue;
			$html = str_repeat(" . ", $level);
			$s = ($selected !== false && $selected == $arColTree[$i]['id']) ? ' selected' : '';
			$str .= '<option value="'.$arColTree[$i]['id'].'"'.$s.'>'.$html.htmlspecialcharsex($col['NAME']).'</option>';

			if (count($arColTree[$i]['child']))
				$str .= CMedialib::_BuildCollectionsSelectOptions($Collections, $arColTree[$i]['child'], $level + 1, $selected);
		}
		return $str;
	}

	public static function GetItems($Params)
	{
		$arCollections = array();
		if (!CMedialib::CanDoOperation('medialib_view_collection', $Params['collectionId']))
			return false;

		if (isset($Params['collectionId']) && $Params['collectionId'] > 0)
			$arCollections[] = $Params['collectionId'];

		$arItems = CMedialibItem::GetList(array(
			'arCollections' => $arCollections
		));

		?>
		<script>
		window.MLItems[<?=$Params['collectionId']?>] = [<?
		for ($i = 0, $l = count($arItems); $i < $l; $i++)
		{
		?>
		{
			id: <?=intVal($arItems[$i]['ID'])?>,
			name: '<?= CMedialib::Escape($arItems[$i]['NAME'])?>',
			desc: '<?= CMedialib::Escape($arItems[$i]['DESCRIPTION'])?>',
			keywords: '<?= CMedialib::Escape($arItems[$i]['KEYWORDS'])?>',
			file_name: '<?= CMedialib::Escape($arItems[$i]['FILE_NAME'])?>',
			date_mod: '<?= CMedialib::GetUsableDate($arItems[$i]['DATE_UPDATE2'])?>',
			height: <?= ($arItems[$i]['HEIGHT'] ? $arItems[$i]['HEIGHT'] : '0')?>,
			width: <?= ($arItems[$i]['WIDTH'] ? $arItems[$i]['WIDTH'] : '0')?>,
			file_size: '<?= CMedialib::GetUsableSize($arItems[$i]['FILE_SIZE'])?>',
			thumb_path: '<?= CMedialib::Escape($arItems[$i]['THUMB_PATH'])?>',
			path: '<?= CMedialib::Escape($arItems[$i]['PATH'])?>',
			type: '<?= $arItems[$i]['TYPE']?>'
		}
		<?
			if ($i != $l - 1)
				echo ',';
		}
		?>];
		</script>
		<?
	}

	public static function DelItem($id, $bCurrent = false, $colId = false)
	{
		return CMedialibItem::Delete($id, $bCurrent, $colId);
	}

	public static function DeleteThumbnails()
	{
		CFileman::DeleteEx(BX_PERSONAL_ROOT."/tmp/medialibrary");
	}

	public static function GetItemCollectionList($Params)
	{
		if(!CMedialib::CanDoOperation('medialib_view_collection', 0))
			return false;

		$ar = CMedialibItem::GetItemCollections($Params);
		?>
		<script>
		window._ml_items_colls = [<?
		for ($i = 0, $l = count($ar); $i < $l; $i++)
			echo $ar[$i].($i != $l - 1 ? ',' : '');
		?>];
		</script>
		<?
	}

	public static function SaveUserSettings($Params)
	{
		if ($GLOBALS["USER"]->IsAuthorized())
			CUserOptions::SetOption("fileman", "medialib_user_set", intVal($Params['width']).','.intVal($Params['height']).','.intVal($Params['coll_id']));
	}

	public static function SaveAccessPermissions($colId, $arTaskPerm)
	{
		global $DB;
		$DB->Query("DELETE FROM b_group_collection_task WHERE COLLECTION_ID=".intVal($colId), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		foreach($arTaskPerm as $group_id => $task_id)
		{
			$arInsert = $DB->PrepareInsert("b_group_collection_task", array("GROUP_ID" => $group_id, "TASK_ID" => $task_id, "COLLECTION_ID" => intVal($colId)));
			$strSql = "INSERT INTO b_group_collection_task(".$arInsert[0].") VALUES(".$arInsert[1].")";
			$DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function MultiActionDelete($Params = array())
	{
		global $DB;

		if (count($Params['Cols']) > 0) // Del collections
		{
			$strCols = "0";
			for($i = 0, $l = count($Params['Cols']); $i < $l; $i++)
			{
				$colId = $Params['Cols'][$i];
				if (CMedialib::CanDoOperation('medialib_del_collection', $colId)) // Access
					$strCols .= ",".IntVal($colId);
			}

			if ($strCols != "0")
			{
				$strSql = "DELETE FROM b_medialib_collection WHERE ID in (".$strCols.")";
				$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

				$strSql = "DELETE FROM b_medialib_collection_item WHERE COLLECTION_ID in (".$strCols.")";
				$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}
		}

		if (count($Params['Items']) > 0) // Del items
		{
			foreach($Params['Items'] as $colId => $arItems)
			{
				if (!CMedialib::CanDoOperation('medialib_del_item', $colId)) // Access
					return false;

				$strItems = "0";
				for($i = 0, $l = count($arItems); $i < $l; $i++)
					$strItems .= ",".IntVal($arItems[$i]);

				$strSql = "DELETE FROM b_medialib_collection_item WHERE ITEM_ID IN (".$strItems.") AND COLLECTION_ID=".$colId;
				$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}
		}

		CMedialibItem::DeleteEmpty(); // Del all items which are absent in 'b_medialib_collection_item'
		return true;
	}

	public static function ShowBrowseButton($Params = array())
	{
		$value = isset($Params['value']) ? $Params['value'] : '...';
		$buttonId = isset($Params['button_id']) ? $Params['button_id'] : '';
		$inputId = isset($Params['id']) ? $Params['id'] : '';
		$title = isset($Params['title']) ? $Params['title'] : '';
		$event = $Params['event'];
		$mode = isset($Params['mode']) ? $Params['mode'] : '';

		if (!isset($Params['useMLDefault']))
			$useMLDefault = COption::GetOptionString('fileman', "ml_use_default", true);
		else
			$useMLDefault = $Params['useMLDefault'];

		if ($mode == 'file_dialog' || COption::GetOptionString('fileman', "use_medialib", "Y") == "N" || !CMedialib::CanDoOperation('medialib_view_collection', 0))
			$mode = 'file_dialog';
		else if ($mode == 'medialib' || !$GLOBALS["USER"]->CanDoOperation('fileman_view_file_structure'))
			$mode = 'medialib';
		else
			$mode = 'select';

		if ($Params['bReturnResult'])
			ob_start();

		if ($mode == 'medialib' || $mode == 'select')
		{
			$arMLConfig = $Params['MedialibConfig'];
			if (!isset($arMLConfig['event']))
				$arMLConfig['event'] = 'BXOpenMLEvent';
			CMedialib::ShowDialogScript($arMLConfig);
		}

		if ($mode == 'medialib')
		{
			$title = isset($Params['title']) ? $Params['title'] : GetMessage('ML_BR_BUT_ML_TITLE');
			?>
			<input id="<?= 'bx_ml_'.$inputId?>" type="button" value="<?= $value?>" title="<?= $title?>" onclick="<?= $arMLConfig['event']?>();"/>
			<?
		}
		elseif ($mode == 'file_dialog')
		{
			$title = isset($Params['title']) ? $Params['title'] : GetMessage('ML_BR_BUT_FD_TITLE');
			?><input type="button" class="adm-btn" style="float:left;" value="<?= $value?>" id="<?= $inputId?>" title="<?= $title?>" onclick="<?= $Params['event']?>();"/><?
		}
		else
		{
			$cid = 'bxmlbut'.$inputId;
		?>

<script>
<?=self::AttachJSScripts();?>
if (!window.<?= $cid?>_onclick)
{
	window.<?= $cid?>_onclick = function (pEl)
	{
		if (!!pEl.OPENER)
			return true;

		pEl.OPENER = new BX.COpener({
			DIV: pEl,
			TYPE: 'click',
			MENU: [
				{ICONCLASS : 'bxml-empty-icon', DEFAULT: <?= $useMLDefault ? 'true' : 'false'?>, TEXT : '<?= GetMessageJS('ML_BR_BUT_ML')?>', TITLE: '<?= GetMessageJS('ML_BR_BUT_ML_TITLE')?>', ONCLICK: '<?= $arMLConfig['event']?>();'},
				{ICONCLASS : 'bxml-empty-icon', DEFAULT: <?= $useMLDefault ? 'false' : 'true'?>, TEXT : '<?= GetMessageJS('ML_BR_BUT_FD')?>',TITLE: '<?= GetMessageJS('ML_BR_BUT_FD_TITLE')?>', ONCLICK: '<?= $event?>();'}
			]
		});

		pEl.OPENER.Toggle();
	}
}
</script>

<div id="<?= $buttonId?>" class="bx-ml-pnbutton">
<div class="bx-pn1" title="<?= GetMessage('ML_BR_BUT_ML_TITLE')?>" onclick="<?= ($useMLDefault ? $arMLConfig['event'] : $event)?>();"></div>
<div class="bx-pn2" title="<?= GetMessage('ML_BR_BUT_SEL')?>" onclick="<?= $cid?>_onclick(this);"></div>
</div>
		<?
		}

		if ($Params['bReturnResult'])
		{
			$s = ob_get_contents();
			ob_end_clean();
			return $s;
		}
	}

	public static function GetUsableSize($size = 0)
	{
		$size = intVal($size);
		if ($size < 1024)
			return $size." ".GetMessage('ML_BYTE');

		$size = round($size / 1024);
		if ($size < 1024)
			return $size." K".GetMessage('ML_BYTE');

		$size = round($size / 1024);
		if ($size < 1024)
			return $size." M".GetMessage('ML_BYTE');

		return $size;
	}

	public static function GetUsableDate($date = '')
	{
		return ConvertDateTime($date, "DD.MM.YYYY HH:MI");
	}

	public static function GetMediaExtentions($bStr = true)
	{
		$strExt = COption::GetOptionString('fileman', "ml_media_available_ext", CMedialib::GetDefaultMediaExtentions());

		$arExt_ = explode(',', $strExt);

		$arTypes = CMedialib::GetTypes();
		for($i = 0, $l = count($arTypes); $i < $l; $i++)
			$arExt_ = array_merge($arExt_, explode(',', $arTypes[$i]["ext"]));

		$arExt = array();
		for ($i = 0, $l = count($arExt_); $i < $l; $i++)
		{
			$ext = strtolower(trim($arExt_[$i], ' .'));
			if (strlen($ext) > 0 && !in_array($ext, $arExt))
				$arExt[] = $ext;
		}

		if ($bStr)
			return implode(",", $arExt);

		return $arExt;
	}

	public static function GetDefaultMediaExtentions()
	{
		return 'jpg,jpeg,gif,png,flv,mp4,wmv,wma,mp3,ppt';
	}

	public static function CheckFileExtention($strPath = '', $arExt = false)
	{
		if (!$arExt)
			$arExt = CMedialib::GetMediaExtentions(false);
		$ext = strtolower(CFileman::GetFileExtension($strPath));
		return in_array($ext, $arExt);
	}

	public static function Escape($str, $bHtmlSpCh = true)
	{
		return CUtil::JSEscape($str);

		if (strlen($str) <= 0)
			return $str;

		if ($bHtmlSpCh)
			$str = htmlspecialcharsex($str);

		$str = str_replace("script>","script_>", $str);
		$str = str_replace("\r","",$str);
		$str = str_replace("\n","\\n",$str);
		$str = str_replace("'","\'",$str);
		$str = str_replace("\"","\\\"",$str);

		return $str;
	}

	public static function SearchItems($Params)
	{
		if (!CModule::IncludeModule("search"))
			return;

		$arQuery = array_keys(stemming($Params['query'], LANGUAGE_ID));
		$arItems = CMedialibItem::Search($arQuery, $Params['types']);
?>
<script>
window.MLSearchResult = [
<?
		for ($i = 0, $l = count($arItems); $i < $l; $i++)
		{
?>
{
	id: <?=intVal($arItems[$i]['ID'])?>,
	name: '<?= CMedialib::Escape($arItems[$i]['NAME'])?>',
	desc: '<?= CMedialib::Escape($arItems[$i]['DESCRIPTION'])?>',
	keywords: '<?= CMedialib::Escape($arItems[$i]['KEYWORDS'])?>',
	file_name: '<?= CMedialib::Escape($arItems[$i]['FILE_NAME'])?>',
	height: <?= ($arItems[$i]['HEIGHT'] ? $arItems[$i]['HEIGHT'] : '0')?>,
	width: <?= ($arItems[$i]['WIDTH'] ? $arItems[$i]['WIDTH'] : '0')?>,
	file_size: '<?= CMedialib::GetUsableSize($arItems[$i]['FILE_SIZE'])?>',
	date_mod: '<?= CMedialib::GetUsableDate($arItems[$i]['DATE_UPDATE2'])?>',
	thumb_path: '<?= CMedialib::Escape($arItems[$i]['THUMB_PATH'])?>',
	path: '<?= CMedialib::Escape($arItems[$i]['PATH'])?>',
	type: '<?= $arItems[$i]['TYPE']?>',
	perm: {edit: <?= $arItems[$i]['perm']['edit'] ? 'true' : 'false'?>, del: <?= $arItems[$i]['perm']['del'] ? 'true' : 'false'?>},
	collections: <?= count($arItems[$i]['collections']) == 1 ? "['".$arItems[$i]['collections'][0]."']" : CUtil::PhpToJSObject($arItems[$i]['collections'])?>

}<?
			if ($i != $l - 1)
				echo ",\n";
		}
?>
];
</script>
<?
	}

	/*
		$strInputName //the name of element
		$strImageID // the file identifier or path to the file from site root
		$showInfo => array(
			"IMAGE" => "Y",
			"MAX_SIZE" => array("W" => ww, "H" => hh)
			"IMAGE_POPUP" => "Y",
			"PATH" => "Y",
			"DIMENSIONS" => "Y",
			"FILE_SIZE" => "Y",
		),
		$fileInput => array( // if false then loading from computer don't used
			"NAME" => "...", //the name for INPUT has "file" type. For loading from computer. It equals $strInputName for defaults
			["ID" => "...",] //the identifier of INPUT. It equals $strInputName."_file" for defaults with replacement all symbols except a-zA-z0-9_ by _
			["SIZE" => NN,] // optional param, default 35
			["SHOW_INFO" => "Y",] //to show information about image or not. Default not
			["LABEL" => "INPUT title"],
		),
		$servInput => array( //if false, then choose file from server not used. Default equals $strInputName
			"NAME" => "...", //name for INPUT type of text. For chosing file from server.
			["ID" => "...",] //INPUT identifier.Default equals $strInputName."_serv" with replacement all symbols except a-zA-z0-9_ by _
			["SIZE" => NN,] // optional param, default 35
			["SHOW_INFO" => "Y",] //to show information about image, or not, default not
			["LABEL" => "INPUT title"],
		),
		$pathInput => array( //if false, then library not use.
			["NAME" => "NNN",] //INPUT name type text. For selection from media library. Default $strInputName.
			["ID" => "...",] //INPUT identifier. Default equals $strInputName."_path" with replacement all symbols except a-zA-z0-9_ by _
			["SIZE" => NN,] // optional param, default 35
			["LABEL" => "INPUT title"],
		),
		$descInput => array( //if false, then the describtion field will not showed.
			["NAME" => "NNN",] //The INPUT's name type of text. File description. Default equals $strInputName."_descr" without array indexes influence.
			["SIZE" => NN,] // optional param, default 35
			["VALUE" => "...",] //Value for file description. if undefined, it will be got from $strImageID
			["LABEL" => "INPUT title"],
		),
		$delInput => array( //if false, then delition flag will not be shown
			["NAME" => "NNN",] //The INPUT's name type of checkbox. File delition flag/
					// Default it equals $strInputName."_del" without array indexes influence.
			["LABEL" => "INPUT title"],
		),
		$scaleIcon => array( //if false, then icon with help will not be shown.
			"SCALE" => Y|N // Y - show scale (zoom) icon N - save size icon
			"WIDTH" => xxx // information for hint over icon
			"HEIGHT" => yyy // information for hint over icon
		),
	*/
	public static function InputFile(
		$strInputName,
		$strImageID = "",
		$showInfo = false,
		$fileInput = false,
		$servInput = false,
		$pathInput = false,
		$descInput = false,
		$delInput = false,
		$scaleIcon = false,
		$cloudInput = false
	)
	{
		return CFileInput::Show($strInputName,
			$strImageID,
			$showInfo,
			array(
				'upload' => $fileInput,
				'medialib' => $pathInput,
				'file_dialog' => $servInput,
				'cloud' => $cloudInput,
				'del' => $delInput,
				'description' => $descInput
			)
		);
	}

	public static function GetTypeById($id, $arMLTypes = false)
	{
		if ($arMLTypes === false)
			$arMLTypes = CMedialib::GetTypes();

		for ($i = 0, $l = count($arMLTypes); $i < $l; $i++)
			if ($arMLTypes[$i]['id'] == $id)
				return $arMLTypes[$i];

		return false;
	}

	public static function GetTypes($arConfigTypes = array(), $bGetEmpties = false)
	{
		global $DB;

		if (self::$bCache)
		{
			$cache = new CPHPCache;
			$cacheId = 'medialib_types_'.$bGetEmpties;
			$cachePath = self::$cachePath.'types';

			if ($cache->InitCache(self::$cacheTime, $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arMLTypes = $res["arMLTypes"];
			}
		}

		if (!self::$bCache || !isset($arMLTypes))
		{
			if ($bGetEmpties)
				$q = "SELECT MT.*, MC.ML_TYPE FROM b_medialib_type MT LEFT JOIN b_medialib_collection MC ON (MT.ID=MC.ML_TYPE)";
			else
				$q = "SELECT * FROM b_medialib_type";

			$err_mess = CMedialibCollection::GetErrorMess()."<br>Function: CMedialib::GetTypes<br>Line: ";
			$res = $DB->Query($q, false, $err_mess);
			$arMLTypes = array();
			$arMLTypesInd = array();

			while($arRes = $res->Fetch())
			{
				if ($arMLTypesInd[$arRes["ID"]])
					continue;

				$typeIcon = "/bitrix/images/fileman/medialib/type_".strtolower($arRes["CODE"]).".gif";
				if (!file_exists($_SERVER['DOCUMENT_ROOT'].$typeIcon))
					$typeIcon = "/bitrix/images/fileman/medialib/type_default.gif";

				if ($arRes["SYSTEM"] == "Y")
				{
					$arRes["NAME"] = GetMessage("ML_TYPE_".strtoupper($arRes["NAME"]));
					$arRes["DESCRIPTION"] = GetMessage("ML_TYPE_".strtoupper($arRes["DESCRIPTION"]));
				}

				$arMLTypesInd[$arRes["ID"]] = true;

				$arMLTypes[] = array(
					"id" => $arRes["ID"],
					"code" => $arRes["CODE"],
					"name" => $arRes["NAME"],
					"ext" => $arRes["EXT"],
					"system" => $arRes["SYSTEM"] == "Y",
					"desc" => $arRes["DESCRIPTION"],
					"type_icon" => $typeIcon,
					"empty" => !$arRes['ML_TYPE'] && ($arRes["CODE"] != "image" || $arRes["SYSTEM"] != "Y")
				);
			}

			if (self::$bCache)
			{
				$cache->StartDataCache(self::$cacheTime, $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arMLTypes" => $arMLTypes
				));
			}
		}

		$result = array();
		if (count($arConfigTypes) > 0)
		{
			foreach($arMLTypes as $type)
			{
				if (in_array(strtolower($type["code"]), $arConfigTypes))
					$result[] = $type;
			}
		}
		else
		{
			$result = $arMLTypes;
		}

		return $result;
	}

	public static function SetTypes($arTypes = array())
	{
		global $DB;

		for ($i = 0, $l = count($arTypes); $i < $l; $i++)
		{
			$arFields = $arTypes[$i];

			$arFields["CODE"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arFields["CODE"]);
			$arFields["EXT"] = preg_replace("/[^a-zA-Z0-9_\,]/i", "", $arFields["EXT"]);

			//if ($arFields["CODE"] == '' || $arFields["EXT"] == '' || $arFields["NAME"] == '')
			if ($arFields["CODE"] == '' || $arFields["EXT"] == '')
				continue;

			$id = IntVal($arFields['ID']);
			unset($arFields['ID']);

			if ($arFields['NEW']) // Add
			{
				unset($arFields['NEW']);
				CDatabase::Add("b_medialib_type", $arFields, array("DESCRIPTION"));
			}
			else // Update
			{
				// Edit only non system types
				//if ($arFields['SYSTEM'] == 'Y')
				//	continue;

				//$strSql =
				//	"UPDATE b_medialib_type SET ".
				//		$DB->PrepareUpdate("b_medialib_type", $arFields).
				//	" WHERE SYSTEM<>'Y' AND ID=".$id;

				$strSql =
					"UPDATE b_medialib_type SET ".
						$DB->PrepareUpdate("b_medialib_type", $arFields).
					" WHERE ID=".$id;

				$DB->QueryBind($strSql,
					array('DESCRIPTION' => $arFields['DESCRIPTION']),
					false, "File: ".__FILE__."<br>Line: ".__LINE__
					);
			}
		}

		self::ClearCache(array("types"));
	}

	public static function DelTypes($arIds = array())
	{
		if (count($arIds) == 0)
			return;

		global $DB;
		$strItems = "0";
		for($i = 0, $l = count($arIds); $i < $l; $i++)
			$strItems .= ",".IntVal($arIds[$i]);

		$res = $DB->Query("DELETE FROM b_medialib_type WHERE ID in (".$strItems.")", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		self::ClearCache(array("types"));

		return $res;
	}

	public static function GetItemViewHTML($itemId)
	{
		$arItem = CMedialibItem::GetList(array('id' => $itemId));
		if (is_array($arItem) && count($arItem) > 0)
		{
			$events = GetModuleEvents("fileman", "OnMedialibItemView");
			$bHandled = false;
			while($arEvent = $events->Fetch())
			{
				$arRes = ExecuteModuleEventEx($arEvent, array($arItem[0]));
				if (!$arRes || !is_array($arRes))
					continue;
				$bHandled = true;
			}
		}

		if (!$bHandled)
		{
			$item = $arItem[0];

			// Default view
			$ext = strtolower(GetFileExtension($item['PATH']));
			$videoExt = array('flv', 'mp4', 'wmv', 'avi');
			$soundExt = array('aac', 'mp3', 'wma');

			if ($item['TYPE'] == 'image' || strpos($item['CONTENT_TYPE'], 'image') !== false)
			{
				// It's image
				$arRes = array(
					"html" => "<img src=\"".htmlspecialcharsex($item['PATH'])."\" width=\"".intVal($item['WIDTH'])."\" height=\"".intVal($item['HEIGHT'])."\" title=\"".htmlspecialcharsex($item['NAME'])."\" />",
					"width" => intVal($item['WIDTH']),
					"height" => intVal($item['HEIGHT'])
				);
			}
			else if (strpos($item['CONTENT_TYPE'], 'video') !== false || in_array($ext, $videoExt))
			{
				global $APPLICATION;
				$item['WIDTH'] = 400;
				$item['HEIGHT'] = 300;

				ob_start();
				$APPLICATION->IncludeComponent(
					"bitrix:player",
					"",
					array(
						"PLAYER_TYPE" => "auto",
						"PATH" => $item['PATH'],
						"WIDTH" => $item['WIDTH'],
						"HEIGHT" => $item['HEIGHT'],
						"FILE_TITLE" => $item['NAME'],
						"FILE_DESCRIPTION" => "",
						//"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
						//"SKIN" => "bitrix.swf",
						"WMODE" => "transparent",
						"WMODE_WMV" => "windowless",
						"SHOW_CONTROLS" => "Y",
						"BUFFER_LENGTH" => "3",
						"ALLOW_SWF" => "N"
					),
					false,
					array('HIDE_ICONS' => 'Y')
				);
				$s = ob_get_contents();
				ob_end_clean();

				$arRes = array(
					"html" => $s,
					"width" => $item['WIDTH'],
					"height" => $item['HEIGHT']
				);
			}
			else if (strpos($item['CONTENT_TYPE'], 'audio') !== false || in_array($ext, $soundExt))
			{
				global $APPLICATION;
				$item['WIDTH'] = 300;
				$item['HEIGHT'] = 24;

				ob_start();
				$APPLICATION->IncludeComponent(
					"bitrix:player",
					"",
					array(
						"PROVIDER" => "sound",
						"PLAYER_TYPE" => "auto",
						"PATH" => $item['PATH'],
						"WIDTH" => $item['WIDTH'],
						"HEIGHT" => $item['HEIGHT'],
						"FILE_TITLE" => $item['NAME'],
						"FILE_DESCRIPTION" => "",
						"WMODE" => "transparent",
						"WMODE_WMV" => "windowless",
						"SHOW_CONTROLS" => "Y",
						"BUFFER_LENGTH" => "3",
						"ALLOW_SWF" => "N"
					),
					false,
					array('HIDE_ICONS' => 'Y')
				);
				$s = "<div style='margin-top: 10px;'>".ob_get_contents()."</div>";
				ob_end_clean();
				$arRes = array(
					"html" => $s,
					"width" => $item['WIDTH'],
					"height" => $item['HEIGHT']
				);
			}
		}
?>
<script>
window.bx_req_res = {
	html: '<?= CUtil::JSEscape($arRes['html'])?>',
	width: '<?= intVal($arRes['width'])?>',
	height: '<?= intVal($arRes['height'])?>',
	bReplaceAll: <?= $arRes['bReplaceAll'] === true ? 'true' : 'false'?>
};
</script>
<?
	}

	public static function ChangeColType($Params)
	{
		if (
			CMedialib::CanDoOperation('medialib_edit_collection', $Params['col']) &&
			CMedialib::CanDoOperation('medialib_edit_collection', $Params['parent']) &&
			$Params['col'] > 0 && $Params['type'] > 0
		)
		{
			$arChild = array();
			for($i = 0, $l = count($Params['childCols']); $i < $l; $i++)
			{
				if (intVal($Params['childCols'][$i]) > 0 &&
				CMedialib::CanDoOperation('medialib_edit_collection', $Params['childCols'][$i]))
					$arChild[] = intVal($Params['childCols'][$i]);
			}
			$Params['childCols'] = $arChild;

			CMedialibCollection::ChangeType($Params);
			?><script>top.bx_req_res = true;</script><?
		}
		else
		{
			?><script>top.bx_req_res = false;</script><?
		}
	}

	public static function CompareTypesEx($typeMix, $arType)
	{
		if ($typeMix == $arType['id'] || (!$typeMix && $arType['code'] == 'image' && $arType['system']))
			return true;

		return false;
	}

	public static function ClearCache($arPath = false)
	{
		if ($arPath === false)
			$arPath = array('types');
		elseif (!is_array($arPath))
			$arPath = array($arPath);

		if (is_array($arPath) && count($arPath) > 0)
		{
			$cache = new CPHPCache;
			foreach($arPath as $path)
				if ($path != '')
					$cache->CleanDir(self::$cachePath.$path);
		}
	}
}

class CMedialibCollection
{
	public static function GetList($Params = array())
	{
		global $DB, $USER;
		$arFilter = $Params['arFilter'];
		$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array('ID' => 'asc');

		static $arFields = array(
			"ID" => Array("FIELD_NAME" => "MLC.ID", "FIELD_TYPE" => "int"),
			"NAME" => Array("FIELD_NAME" => "MLC.NAME", "FIELD_TYPE" => "string"),
			"ACTIVE" => Array("FIELD_NAME" => "MLC.ACTIVE", "FIELD_TYPE" => "string"),
			"DATE_UPDATE" => Array("FIELD_NAME" => "MLC.DATE_UPDATE", "FIELD_TYPE" => "date"),
			"KEYWORDS" => Array("FIELD_NAME" => "MLC.KEYWORDS", "FIELD_TYPE" => "string"),
			"DESCRIPTION" => Array("FIELD_NAME" => "MLC.DESCRIPTION", "FIELD_TYPE" => "string"),
			"OWNER_ID" => Array("FIELD_NAME" => "MLC.OWNER_ID", "FIELD_TYPE" => "int"),
			"PARENT_ID" => Array("FIELD_NAME" => "MLC.PARENT_ID", "FIELD_TYPE" => "int"),
			"ML_TYPE" => Array("FIELD_NAME" => "MLC.ML_TYPE", "FIELD_TYPE" => "string")
		);

		$err_mess = (CMedialibCollection::GetErrorMess())."<br>Function: GetList<br>Line: ";
		$arSqlSearch = array();
		$strSqlSearch = "";
		if(is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for($i=0, $l = count($filter_keys); $i<$l; $i++)
			{
				$n = strtoupper($filter_keys[$i]);
				$val = $arFilter[$filter_keys[$i]];
				if(is_string($val)  && strlen($val) <=0 || strval($val)=="NOT_REF")
					continue;
				if ($n == 'ID' || $n == 'PARENT_ID' || $n == 'OWNER_ID')
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val, 'N');
				elseif(isset($arFields[$n]))
					$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
			}
		}

		$strOrderBy = '';
		foreach($arOrder as $by=>$order)
			if(isset($arFields[strtoupper($by)]))
				$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

		if(strlen($strOrderBy)>0)
			$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		if (isset($arFilter['TYPES']) && is_array($arFilter['TYPES']))
		{
			$strTypes = "";
			for($i = 0, $l = count($arFilter['TYPES']); $i < $l; $i++)
				$strTypes .= ",".IntVal($arFilter['TYPES'][$i]);
			$strSqlSearch .= "\n AND ML_TYPE in (".trim($strTypes, ", ").")";
		}

		$strSql = "
			SELECT
				MLC.*
			FROM
				b_medialib_collection MLC
			WHERE
				$strSqlSearch
			$strOrderBy";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$arResult = Array();
		while($arRes = $res->Fetch())
			$arResult[]=$arRes;

		return $arResult;
	}

	public static function CheckFields($arFields)
	{
		if (!isset($arFields['NAME']) || strlen($arFields['NAME']) <= 0)
			return false;

		/*
		ID int not null auto_increment,
		NAME varchar(255) not null,
		DESCRIPTION text null,
		ACTIVE char(1) not null default 'Y',
		DATE_UPDATE datetime not null,
		OWNER_ID int null,
		PARENT_ID int null,
		SITE_ID char(2) not null,
		KEYWORDS varchar(255) null
		*/
		return true;
	}

	public static function Edit($Params)
	{
		global $DB;
		$arFields = $Params['arFields'];

		if (!isset($arFields['~DATE_UPDATE']))
			$arFields['~DATE_UPDATE'] = $DB->CurrentTimeFunction();

		if(!CMedialibCollection::CheckFields($arFields))
			return false;

		if (!isset($arFields['ML_TYPE']))
			$arFields['ML_TYPE'] = '';

		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;
		if ($bNew) // Add
		{
			unset($arFields['ID']);
			$ID = CDatabase::Add("b_medialib_collection", $arFields, array("DESCRIPTION"));
		}
		else // Update
		{
			$ID = $arFields['ID'];
			unset($arFields['ID']);
			$strUpdate = $DB->PrepareUpdate("b_medialib_collection", $arFields);
			$strSql =
				"UPDATE b_medialib_collection SET ".
					$strUpdate.
				" WHERE ID=".IntVal($ID);

			$DB->QueryBind($strSql,
				array('DESCRIPTION' => $arFields['DESCRIPTION']),
				false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $ID;
	}

	public static function Delete($ID, $bDelEmpty = true)
	{
		global $DB;
		$ID = intval($ID);

		$strSql = "DELETE FROM b_medialib_collection WHERE ID=".$ID;
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$strSql = "DELETE FROM b_medialib_collection_item WHERE COLLECTION_ID=".$ID;
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if ($bDelEmpty)
			CMedialibItem::DeleteEmpty();

		return $z;
	}

	public static function GetErrorMess()
	{
		return "<br>Class: CMedialibCollection<br>File: ".__FILE__;
	}

	public static function IsViewable($oCol, $arCol=false)
	{
		if(!$arCol)
			$arCol = CMedialibCollection::GetList(array('arFilter' => array('ACTIVE' => 'Y')));

		if (!CMedialib::CanDoOperation('medialib_view_collection', $oCol['ID']))
			return false;

		$l = count($arCol);
		if ($oCol['PARENT_ID'])
		{
			$parId = $oCol['PARENT_ID'];
			while(intVal($parId) > 0)
			{
				$bFind = false;
				for($i = 0; $i < $l; $i++) // Find parent
				{
					if ($arCol[$i]['ID'] == $parId)
					{
						if (!CMedialib::CanDoOperation('medialib_view_collection', $arCol[$i]['ID']))
							return false;
						$parId = $arCol[$i]['PARENT_ID'];
						$bFind = true;
						break;
					}
				}
				if (!$bFind)
					return false;
			}
		}
		return true;
	}

	public static function ChangeType($Params)
	{
		global $DB;
		$arFields = array(
			'ML_TYPE' => $Params['type'],
			'PARENT_ID' => $Params['parent']
		);

		$strUpdate = $DB->PrepareUpdate("b_medialib_collection", $arFields);
		$strSql =
			"UPDATE b_medialib_collection SET ".
				$strUpdate.
			" WHERE ID=".IntVal($Params['col']);

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (count($Params['childCols']) > 0 && $res)
		{
			$strIds = "0";
			for($i = 0, $l = count($Params['childCols']); $i < $l; $i++)
				$strIds .= ",".IntVal($Params['childCols'][$i]);

			$strUpdate = $DB->PrepareUpdate("b_medialib_collection", array('ML_TYPE' => $Params['type']));
			$strSql =
				"UPDATE b_medialib_collection SET ".
					$strUpdate.
				" WHERE ID in (".$strIds.")";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $res;
	}
}

class CMedialibItem
{
	public static function CheckFields($arFields)
	{
		if (!isset($arFields['NAME']) || strlen($arFields['NAME']) <= 0)
			return false;

		return true;
	}

	public static function GetList($Params)
	{
		global $DB;

		$q = '';
		if (is_array($Params['arCollections']))
		{
			if (count($Params['arCollections']) == 1)
			{
				$q = 'WHERE MCI.COLLECTION_ID='.intVal($Params['arCollections'][0]);
			}
			elseif (count($Params['arCollections']) > 1)
			{
				$strCollections = "0";
				for($i = 0, $l = count($Params['arCollections']); $i < $l; $i++)
					$strCollections .= ",".IntVal($Params['arCollections'][$i]);
				$q = 'WHERE MCI.COLLECTION_ID in ('.$strCollections.')';
			}
		}

		if (isset($Params['id']) && $Params['id'] > 0)
			$q = 'WHERE MI.ID='.intVal($Params['id']);

		if (isset($Params['minId']) && $Params['minId'] > 0)
		{
			if (strlen($q) > 0)
				$q = trim($q)." AND MI.ID>=".intVal($Params['minId']);
			else
				$q .= "WHERE MI.ID>=".intVal($Params['minId']);
		}

		$err_mess = CMedialibCollection::GetErrorMess()."<br>Function: CMedialibItem::GetList<br>Line: ";
		$strSql = "SELECT
					MI.*,MCI.COLLECTION_ID, F.HEIGHT, F.WIDTH, F.FILE_SIZE, F.CONTENT_TYPE, F.SUBDIR, F.FILE_NAME, F.HANDLER_ID,
					".$DB->DateToCharFunction("MI.DATE_UPDATE")." as DATE_UPDATE2
				FROM b_medialib_collection_item MCI
				INNER JOIN b_medialib_item MI ON (MI.ID=MCI.ITEM_ID)
				INNER JOIN b_file F ON (F.ID=MI.SOURCE_ID) ".$q;

		$res = $DB->Query($strSql, false, $err_mess);
		$arResult = Array();
		$rootPath = CSite::GetSiteDocRoot(false);
		$tmbW = COption::GetOptionInt('fileman', "ml_thumb_width", 140);
		$tmbH = COption::GetOptionInt('fileman', "ml_thumb_height", 105);

		while($arRes = $res->Fetch())
		{
			CMedialibItem::GenerateThumbnail($arRes, array('rootPath' => $rootPath, 'width' => $tmbW, 'height' => $tmbH));
			$arRes['PATH'] = CFile::GetFileSRC($arRes);
			$arResult[]=$arRes;
		}

		return $arResult;
	}

	// Add or edit ITEM
	public static function Edit($Params)
	{
		global $DB;
		$source_id = false;
		$arFields = $Params['arFields'];
		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;
		$bFile_FD = $Params['path'] && strlen($Params['path']) > 0;
		$bFile_PC = $Params['file'] && strlen($Params['file']['name']) > 0 && $Params['file']['size'] > 0;

		$io = CBXVirtualIo::GetInstance();

		if ($bFile_FD || $bFile_PC)
		{
			if ($bFile_FD)
			{
				$DocRoot = CSite::GetSiteDocRoot(false);
				$tmp_name = $DocRoot.$Params['path'];

				if ($io->FileExists($tmp_name))
				{
					$flTmp = $io->GetFile($tmp_name);
					$file_name = substr($Params['path'], strrpos($Params['path'], '/') + 1);
					$arFile = array(
						"name" => $file_name,
						"size" => $flTmp->GetFileSize(),
						"tmp_name" => $tmp_name,
						"type" => CFile::IsImage($file_name) ? 'image' : 'file'
					);
				}
			}
			else if ($bFile_PC)
			{
				$arFile = $Params['file'];
			}

			if (!CMedialib::CheckFileExtention($arFile["name"]))
				return false;

			if (!$bNew) // Del old file
			{
				$arFile["old_file"] = CMedialibItem::GetSourceId($arFields['ID']);
				$arFile["del"] = "Y";
			}

			// Resizing Image
			if (CFile::IsImage($arFile["name"]))
			{
				$arSize = array(
					'width' => COption::GetOptionInt('fileman', "ml_max_width", 1024),
					'height' => COption::GetOptionInt('fileman', "ml_max_height", 1024)
				);
				$res = CFile::ResizeImage($arFile, $arSize);
			}

			$arFile["MODULE_ID"] = "fileman";
			$source_id = CFile::SaveFile($arFile, "medialibrary");

			if ($source_id) // Get file
			{
				$r = CFile::GetByID($source_id);
				if ($arFile = $r->Fetch())
				{
					if (CFile::IsImage($arFile['FILE_NAME']))
						CMedialibItem::GenerateThumbnail($arFile, array('width' => COption::GetOptionInt('fileman', "ml_thumb_width", 140), 'height' => COption::GetOptionInt('fileman', "ml_thumb_height", 105)));

					$arFile['PATH'] = CMedialibItem::GetFullPath($arFile);
				}
			}
		}

		// TODO: Add error handling
		if ($bNew && !$source_id)
			return false;

		// 2. Add to b_medialib_item
		if (!isset($arFields['~DATE_UPDATE']))
			$arFields['~DATE_UPDATE'] = $DB->CurrentTimeFunction();

		if(!CMedialibItem::CheckFields($arFields))
			return false;

		if (CModule::IncludeModule("search"))
		{
			$arStem = stemming($arFields['NAME'].' '.$arFields['DESCRIPTION'].' '.$arFields['KEYWORDS'], LANGUAGE_ID);
			if (count($arStem) > 0)
				$arFields['SEARCHABLE_CONTENT'] = '{'.implode('}{', array_keys($arStem)).'}';
			else
				$arFields['SEARCHABLE_CONTENT'] = '';
		}

		if ($bNew) // Add
		{
			unset($arFields['ID']);
			$arFields['SOURCE_ID'] = $source_id;
			$arFields['~DATE_CREATE'] = $arFields['~DATE_UPDATE'];
			$arFields['ITEM_TYPE'] = '';
			$ID = CDatabase::Add("b_medialib_item", $arFields, array("DESCRIPTION","SEARCHABLE_CONTENT"));
		}
		else // Update
		{
			if ($source_id)
				$arFields['SOURCE_ID'] = $source_id;
			$ID = $arFields['ID'];
			unset($arFields['ID']);

			$strUpdate = $DB->PrepareUpdate("b_medialib_item", $arFields);
			$strSql =
				"UPDATE b_medialib_item SET ".
					$strUpdate.
				" WHERE ID=".IntVal($ID);

			$DB->QueryBind($strSql,
				array(
					"DESCRIPTION" => $arFields["DESCRIPTION"],
					"SEARCHABLE_CONTENT" => $arFields["SEARCHABLE_CONTENT"]
				),
				false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		// 3. Set fields to b_medialib_collection_item
		if (!$bNew) // Del all rows if
		{
			$strSql = "DELETE FROM b_medialib_collection_item WHERE ITEM_ID=".IntVal($ID);
			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		$strCollections = "0";

		for($i = 0, $l = count($Params['arCollections']); $i < $l; $i++)
			$strCollections .= ",".IntVal($Params['arCollections'][$i]);

		$strSql =
			"INSERT INTO b_medialib_collection_item(ITEM_ID, COLLECTION_ID) ".
			"SELECT ".intVal($ID).", ID ".
			"FROM b_medialib_collection ".
			"WHERE ID in (".$strCollections.")";

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (!$arFields['ID'])
			$arFields['ID'] = $ID;

		if ($source_id)
			$arFields = array_merge($arFile, $arFields);

		return $arFields;
	}

	public static function GenerateThumbnail(&$arFile, $Params = array())
	{
		$rootPath = isset($Params['rootPath']) ? $Params['rootPath'] : CSite::GetSiteDocRoot(false);
		if (CFile::IsImage($arFile['FILE_NAME']))
		{
			$arResized = CFile::ResizeImageGet($arFile, array('width' => $Params['width'], 'height' => $Params['height']));
			if($arResized)
				$arFile['THUMB_PATH'] = $arResized['src'];
			$arFile['TYPE'] = 'image';
		}
		else
			$arFile['TYPE'] = 'file';
	}

	public static function GetItemCollections($Params)
	{
		global $DB;
		$strSql = 'SELECT MCI.COLLECTION_ID
			FROM b_medialib_collection_item MCI
			WHERE MCI.ITEM_ID='.intVal($Params['ID']);
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$arResult = array();
		while($arRes = $res->Fetch())
			$arResult[]=$arRes['COLLECTION_ID'];
		return $arResult;
	}

	public static function Delete($ID, $bCurrent, $colId)
	{
		global $DB;
		if ($bCurrent) // Del from one collection
		{
			if (!CMedialib::CanDoOperation('medialib_del_item', $colId))
				return false;
			$strSql = "DELETE FROM b_medialib_collection_item WHERE ITEM_ID=".IntVal($ID)." AND COLLECTION_ID=".IntVal($colId);
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
		else // Del from all collections
		{
			$arCols = CMedialibItem::GetItemCollections(array('ID' => $ID));
			for ($i = 0, $l = count($arCols); $i < $l; $i++)
			{
				if (!CMedialib::CanDoOperation('medialib_del_item', $arCols[$i])) // Check access
					return false;
			}
			$strSql = "DELETE FROM b_medialib_collection_item WHERE ITEM_ID=".IntVal($ID);
			$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		CMedialibItem::DeleteEmpty();

		return $z;
	}

	public static function DeleteEmpty()
	{
		global $DB;

		$strSql = 'SELECT MI.*,MCI.COLLECTION_ID
			FROM b_medialib_item MI
			LEFT JOIN b_medialib_collection_item MCI ON (MI.ID=MCI.ITEM_ID)
			WHERE MCI.COLLECTION_ID is null';
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$strItems = "0";
		while($arRes = $res->Fetch())
		{
			$strItems .= ",".IntVal($arRes['ID']);

			if ($arRes['SOURCE_ID'] > 0) // Clean from 'b_file'
				CFile::Delete(IntVal($arRes['SOURCE_ID']));
		}

		// Clean from 'b_medialib_item'
		if ($strItems != "0")
			$DB->Query("DELETE FROM b_medialib_item WHERE ID in (".$strItems.")", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}

	public static function GetThumbPath($arImage)
	{
		return BX_PERSONAL_ROOT."/tmp/".$arImage['SUBDIR'].'/'.$arImage['FILE_NAME'];
	}

	public static function GetFullPath($arImage, $upload_dir = false)
	{
		return CFile::GetFileSRC($arImage, $upload_dir);
	}

	public static function GetSourceId($id)
	{
		global $DB;
		$strSql = 'SELECT SOURCE_ID
			FROM b_medialib_item
			WHERE ID='.intVal($id);
		$r = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $r->Fetch())
			return $res['SOURCE_ID'];

		return false;
	}

	public static function Search($arQuery, $arTypes = array())
	{
		global $DB;
		$err_mess = CMedialibCollection::GetErrorMess()."<br>Function: CMedialibItem::Search<br>Line: ";

		$strSql = "SELECT
					MI.*, MI.*,MCI.COLLECTION_ID, F.HEIGHT, F.WIDTH, F.FILE_SIZE, F.CONTENT_TYPE, F.SUBDIR, F.FILE_NAME, F.HANDLER_ID,
					".$DB->DateToCharFunction("MI.DATE_UPDATE")." as DATE_UPDATE2
				FROM b_medialib_item MI
				INNER JOIN b_medialib_collection_item MCI ON (MI.ID=MCI.ITEM_ID)
				INNER JOIN b_file F ON (F.ID=MI.SOURCE_ID)
				WHERE 1=1";

		$l = count($arQuery);
		if ($l == 0)
			return array();

		for ($i = 0; $i < $l; $i++)
			$strSql .= " AND MI.SEARCHABLE_CONTENT LIKE '%".$DB->ForSql($arQuery[$i])."%'";

		$strSql .= " ORDER BY MI.ID DESC";

		$res = $DB->Query($strSql, false, $err_mess);
		$arResult = Array();
		$rootPath = CSite::GetSiteDocRoot(false);
		$tmbW = COption::GetOptionInt('fileman', "ml_thumb_width", 140);
		$tmbH = COption::GetOptionInt('fileman', "ml_thumb_height", 105);

		$elId2Index = array();
		$colId2Index = array();
		$arCol = CMedialibCollection::GetList(array('arFilter' => array('ACTIVE' => 'Y', "TYPES" => $arTypes)));

		for ($i = 0, $l = count($arCol); $i < $l; $i++)
			$colId2Index[$arCol[$i]['ID']] = $i;

		while($arRes = $res->Fetch())
		{
			$colId = $arRes['COLLECTION_ID'];
			if (!isset($colId2Index[$colId]) || !CMedialibCollection::IsViewable($arCol[$colId2Index[$colId]], $arCol))
				continue;

			if (isset($elId2Index[$arRes['ID']]))
			{
				$arResult[$elId2Index[$arRes['ID']]]['collections'][] = $colId;
			}
			else
			{
				$elId2Index[$arRes['ID']] = count($arResult);
				$arRes['collections'] = array($colId);
				$arRes['perm'] = array
				(
					'edit' => true,
					'del' => true
				);

				CMedialibItem::GenerateThumbnail($arRes, array('rootPath' => $rootPath, 'width' => $tmbW, 'height' => $tmbH));
				$arRes['PATH'] = CFile::GetFileSRC($arRes);
				$arResult[]=$arRes;
			}
		}

		return $arResult;
	}
}

// Deprecated and unused class. Placed here to prevent fatal errors in customized forms
class CMedialibTabControl
{
	public static function ShowScript()
	{
	}
}

?>