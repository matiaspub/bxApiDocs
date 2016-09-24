<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/

// ***** CAdminFileDialog *****
IncludeModuleLangFile(__FILE__);

/**
 * <b>CAdminFileDialog</b> - класс для работы с файловым диалогом в административной части системы
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminfiledialog/index.php
 * @author Bitrix
 */
class CAdminFileDialog
{
	
	/**
	* <p>Метод принимает массив конфигурационных параметров и генерирует скрипты, необходимые для показа файлового диалога. Нестатический метод.</p>
	*
	*
	* @param Array $arConfig  Строка, содержащая имя Javascript-функции, которая вызывает файловый   
	*     диалог. Функция должна быть задана в глобальной области
	* видимости.
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* <buttononclick>
	* CAdminFileDialog::ShowScript(Array
	* 	(
	* 		"event" =&gt; "OpenImage",
	* 		"arResultDest" =&gt; Array("FUNCTION_NAME" =&gt; "SetImageUrl"),
	* 		"arPath" =&gt; Array(),
	* 		"select" =&gt; 'F',
	* 		"operation" =&gt; 'O',
	* 		"showUploadTab" =&gt; true,
	* 		"showAddToMenuTab" =&gt; false,
	* 		"fileFilter" =&gt; 'image',
	* 		"allowAllFiles" =&gt; true,
	* 		"saveConfig" =&gt; true
	* 	)
	* );
	* 
	* &lt;script&gt;
	* document.getElementById("open_dialog_button").onclick = OpenImage;
	* var SetImageUrl = function(filename,path,site)
	* {
	* 	// Обработка результата
	* 	alert("filename = "+filename+"; /n path = "+path+"; /n site = "+site);
	* }
	* &lt;/script&gt;
	* </buttononclick>
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cadminfiledialog/showscript.php
	* @author Bitrix
	*/
	public static function ShowScript($arConfig)
	{
		global $USER, $APPLICATION;
		$bCloudsBrowse = is_object($USER) && $USER->CanDoOperation("clouds_browse") && $arConfig["operation"] === "O";

		CUtil::InitJSCore(array('ajax', 'window'));

		$io = CBXVirtualIo::GetInstance();
		$rootPath = "";
		$resultDest = "";

		if(CModule::IncludeModule("fileman"))
		{
			$arConfig['path'] = (isset($arConfig['arPath']['PATH']) ? $arConfig['arPath']['PATH'] : '');
			$arConfig['site'] = (isset($arConfig['arPath']['SITE']) ? $arConfig['arPath']['SITE'] : '');
			$arConfig['lang'] = (isset($arConfig['lang']) ? $arConfig['lang'] : LANGUAGE_ID);
			$arConfig['zIndex'] = isset($arConfig['zIndex']) ? $arConfig['zIndex'] : 2500;

			$path = $io->CombinePath("/", $arConfig['path']);
			$path = CFileMan::SecurePathVar($path);
			$rootPath = CSite::GetSiteDocRoot($arConfig['site']);

			while (!$io->DirectoryExists($rootPath.$path))
			{
				$rpos = strrpos($path, '/');
				if ($rpos === false || $rpos < 1)
				{
					$path = '/';
					break;
				}
				$path = rtrim(substr($path, 0, $rpos), "/\\");
			}
			if (!$path || $path == '')
				$path = '/';
			$arConfig['path'] = $path;

			$functionError = "";
			if (!isset($arConfig['event']))
			{
				$functionError .= GetMessage("BX_FD_NO_EVENT").". ";
			}
			else
			{
				$arConfig['event'] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['event']);
				if (strlen($arConfig['event']) <= 0)
					$functionError .= GetMessage("BX_FD_NO_EVENT").". ";
			}

			if (!isset($arConfig['arResultDest']) || !is_array($arConfig['arResultDest']))
			{
				$functionError .= GetMessage("BX_FD_NO_RETURN_PRM").". ";
			}
			else
			{
				if (isset($arConfig['arResultDest']["FUNCTION_NAME"]) && strlen($arConfig['arResultDest']["FUNCTION_NAME"]) > 0)
				{
					$arConfig['arResultDest']["FUNCTION_NAME"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["FUNCTION_NAME"]);
					if (strlen($arConfig['arResultDest']["FUNCTION_NAME"]) <= 0)
						$functionError .= GetMessage("BX_FD_NO_RETURN_FNC").". ";
					else
						$resultDest = "FUNCTION";
				}
				elseif (isset($arConfig['arResultDest']["FORM_NAME"]) && strlen($arConfig['arResultDest']["FORM_NAME"]) > 0
					&& isset($arConfig['arResultDest']["FORM_ELEMENT_NAME"]) && strlen($arConfig['arResultDest']["FORM_ELEMENT_NAME"]) > 0)
				{
					$arConfig['arResultDest']["FORM_NAME"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["FORM_NAME"]);
					$arConfig['arResultDest']["FORM_ELEMENT_NAME"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["FORM_ELEMENT_NAME"]);
					if (strlen($arConfig['arResultDest']["FORM_NAME"]) <= 0 || strlen($arConfig['arResultDest']["FORM_ELEMENT_NAME"]) <= 0)
						$functionError .= GetMessage("BX_FD_NO_RETURN_FRM").". ";
					else
						$resultDest = "FORM";
				}
				elseif (isset($arConfig['arResultDest']["ELEMENT_ID"]) && strlen($arConfig['arResultDest']["ELEMENT_ID"]) > 0)
				{
					$arConfig['arResultDest']["ELEMENT_ID"] = preg_replace("/[^a-zA-Z0-9_]/i", "", $arConfig['arResultDest']["ELEMENT_ID"]);
					if (strlen($arConfig['arResultDest']["ELEMENT_ID"]) <= 0)
						$functionError .= GetMessage("BX_FD_NO_RETURN_ID").". ";
					else
						$resultDest = "ID";
				}
				else
				{
					$functionError .= GetMessage("BX_FD_BAD_RETURN").". ";
				}
			}
		}
		else
		{
			$functionError = GetMessage("BX_FD_NO_FILEMAN");
		}

		if (strlen($functionError) <= 0)
		{
			?>
			<script>
			var mess_SESS_EXPIRED = '<?=GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_SESS_EXPIRED')?>';
			var mess_ACCESS_DENIED = '<?=GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_NO_PERMS')?>';
			window.<?= CUtil::JSEscape($arConfig['event'])?> = function(bLoadJS, Params)
			{
				if (!Params)
					Params = {};

				<?if(!$USER->CanDoOperation('fileman_view_file_structure')):?>
					<?echo '
					alert(mess_ACCESS_DENIED);
					return;
					'?>
				<?else:?>
					var UserConfig;
				<?
				$fd_config = stripslashes(CUserOptions::GetOption("fileman", "file_dialog_config", "N"));
				if ($fd_config == "N" || $arConfig['saveConfig'] === false)
				{
				?>
					UserConfig =
					{
						site : '<?= CUtil::JSEscape($arConfig['site'])?>',
						path : '<?= CUtil::JSEscape($arConfig['path'])?>',
						view : "list",
						sort : "type",
						sort_order : "asc"
					};
				<?
				}
				else
				{
					$res = explode(";", $fd_config);
					if ($res[0])
						$arConfig['site'] = $res[0];
					if ($res[1])
						$arConfig['path'] = rtrim($res[1], " /\\");

					$rootPath = CSite::GetSiteDocRoot($arConfig['site']);

					if (!$io->DirectoryExists($rootPath.$arConfig['path']))
						$arConfig['path'] = '/';
					?>
					UserConfig =
					{
						site : '<?= CUtil::JSEscape($arConfig['site'])?>',
						path : '<?= CUtil::JSEscape($arConfig['path'])?>',
						view : '<?= CUtil::JSEscape($res[2])?>',
						sort : '<?= CUtil::JSEscape($res[3])?>',
						sort_order : '<?= CUtil::JSEscape($res[4])?>'
					};
					<?
				}
				?>
				if (!window.BXFileDialog)
				{
					if (bLoadJS !== false)
						BX.loadScript('<?=CUtil::GetAdditionalFileURL("/bitrix/js/main/file_dialog.js")?>');
					return setTimeout(function(){window['<?= CUtil::JSEscape($arConfig['event'])?>'](false, Params)}, 50);
				}

				var oConfig =
				{
					submitFuncName : '<?= CUtil::JSEscape($arConfig['event'])?>Result',
					select : '<?= CUtil::JSEscape($arConfig['select'])?>',
					operation: '<?= CUtil::JSEscape($arConfig['operation'])?>',
					showUploadTab : <?= $arConfig['showUploadTab'] ? 'true' : 'false';?>,
					showAddToMenuTab : <?= $arConfig['showAddToMenuTab'] ? 'true' : 'false';?>,
					site : '<?= CUtil::JSEscape($arConfig['site'])?>',
					path : '<?= CUtil::JSEscape($arConfig['path'])?>',
					lang : '<?= CUtil::JSEscape($arConfig['lang'])?>',
					fileFilter : '<?= CUtil::JSEscape($arConfig['fileFilter'])?>',
					allowAllFiles : <?= $arConfig['allowAllFiles'] !== false ? 'true' : 'false';?>,
					saveConfig : <?= $arConfig['saveConfig'] !== false ? 'true' : 'false';?>,
					sessid: "<?=bitrix_sessid()?>",
					checkChildren: true,
					genThumb: <?= COption::GetOptionString("fileman", "file_dialog_gen_thumb", "Y") == 'Y' ? 'true' : 'false';?>,
					zIndex: <?= CUtil::JSEscape($arConfig['zIndex'])?>
				};

				if(window.oBXFileDialog && window.oBXFileDialog.UserConfig)
				{
					UserConfig = oBXFileDialog.UserConfig;
					oConfig.path = UserConfig.path;
					oConfig.site = UserConfig.site;
				}

				if (Params.path)
					oConfig.path = Params.path;
				if (Params.site)
					oConfig.site = Params.site;

				oBXFileDialog = new BXFileDialog();
				oBXFileDialog.Open(oConfig, UserConfig);
				<?endif;?>
			};
			window.<?= CUtil::JSEscape($arConfig['event'])?>Result = function(filename, path, site, title, menu)
			{
<?
$arBuckets = array();
if($bCloudsBrowse && CModule::IncludeModule('clouds'))
{
	foreach(CCloudStorageBucket::GetAllBuckets() as $arBucket)
	{
		if($arBucket["ACTIVE"] == "Y")
		{
			$obBucket = new CCloudStorageBucket($arBucket["ID"]);
			if($obBucket->Init())
				$arBuckets[$arBucket["BUCKET"]] = rtrim($obBucket->GetFileSRC("/"), "/");
		}
	}
}
?>
				path = jsUtils.trim(path);
				path = path.replace(/\\/ig,"/");
				path = path.replace(/\/\//ig,"/");
				if (path.substr(path.length-1) == "/")
					path = path.substr(0, path.length-1);
				var full = (path + '/' + filename).replace(/\/\//ig, '/');
				if (path == '')
					path = '/';

				var arBuckets = <?echo CUtil::PhpToJSObject($arBuckets)?>;
				if(arBuckets[site])
				{
					full = arBuckets[site] + filename;
					path = arBuckets[site] + path;
				}

				if ('<?= CUtil::JSEscape($arConfig['select'])?>' == 'D')
					name = full;

				<?if ($resultDest == "FUNCTION"): ?>
					<?= CUtil::JSEscape($arConfig['arResultDest']["FUNCTION_NAME"])."(filename, path, site, title || '', menu || '');"?>
				<?elseif($resultDest == "FORM"): ?>
					document.<?= CUtil::JSEscape($arConfig['arResultDest']["FORM_NAME"])?>.<?= CUtil::JSEscape($arConfig['arResultDest']["FORM_ELEMENT_NAME"])?>.value = full;
					BX.fireEvent(document.<?= CUtil::JSEscape($arConfig['arResultDest']["FORM_NAME"])?>.<?= CUtil::JSEscape($arConfig['arResultDest']["FORM_ELEMENT_NAME"])?>, 'change');
				<?elseif($resultDest == "ID"): ?>
					BX('<?= CUtil::JSEscape($arConfig['arResultDest']["ELEMENT_ID"])?>').value = full;
					BX.fireEvent(BX('<?= CUtil::JSEscape($arConfig['arResultDest']["ELEMENT_ID"])?>'), 'change');
				<?endif;?>
			};
			<?self::AttachJSScripts();?>
			</script>
			<?
		}
		else
		{
			echo "<font color=\"#FF0000\">".htmlspecialcharsbx($functionError)."</font>";
		}
	}

	public static function AttachJSScripts()
	{
		if(!defined("BX_B_FILE_DIALOG_SCRIPT_LOADED"))
		{
			// define("BX_B_FILE_DIALOG_SCRIPT_LOADED", true);
?>
if (window.jsUtils)
{
	jsUtils.addEvent(window, 'load', function(){jsUtils.loadJSFile('<?=CUtil::GetAdditionalFileURL("/bitrix/js/main/file_dialog.js")?>');}, false);
}
<?
		}
	}

	public static function Start($Params)
	{
		global $USER;
		$bCloudsBrowse = is_object($USER) && $USER->CanDoOperation('clouds_browse') && $Params["operation"] === "O";

		$arSites = Array();
		$dbSitesList = CSite::GetList($b = "SORT", $o = "asc");
		$arSitesPP = Array();
		while($arSite = $dbSitesList->GetNext())
		{
			$arSites[$arSite["ID"]] = $arSite["NAME"] ? $arSite["NAME"] : $arSite["ID"];
			$arSitesPP[] = array(
				"ID" => $arSite["ID"],
				"TEXT" => '['.$arSite["ID"].'] '.$arSite["NAME"],
				"ONCLICK" => "oBXDialogControls.SiteSelectorOnChange('".$arSite["ID"]."')",
				"ICON" => ($arSite["ID"] == $Params['site']) ? 'checked' : ''
			);
		}

		if($bCloudsBrowse && CModule::IncludeModule('clouds'))
		{
			foreach(CCloudStorageBucket::GetAllBuckets() as $arBucket)
			{
				if($arBucket["ACTIVE"] == "Y")
				{
					$id = $arBucket["BUCKET"];
					$arSites[$id] = $arBucket["BUCKET"];
					$arSitesPP[] = array(
						"ID" => $id,
						"TEXT" => $arBucket["BUCKET"],
						"ONCLICK" => "oBXDialogControls.SiteSelectorOnChange('".$id."')",
						"ICON" => ($id == $Params['site']) ? 'checked' : ''
					);
				}
			}
		}

		$Params['arSites'] = $arSites;
		$Params['arSitesPP'] = $arSitesPP;
		$Params['site'] = ($Params['site'] && isset($arSites[$Params['site']])) ? $Params['site'] : key($arSites); // Secure site var

		if (!in_array(strtolower($Params['lang']), array('en', 'ru'))) // Secure lang var
		{
			$res = CLanguage::GetByID($Params['lang']);
			if($lang = $res->Fetch())
				$Params['lang'] = $lang['ID'];
			else
				$Params['lang'] = 'en';
		}

		if ($Params['bAddToMenu'])
		{
			$armt = self::GetMenuTypes($Params['site'], $Params['path']);
			$Params['arMenuTypes'] = $armt[0];
			$Params['arMenuTypesScript'] = $armt[1];
			$Params['menuItems'] = $armt[2];
		}

		self::BuildDialog($Params);
		self::ShowJS($Params);
	}

	public static function LoadItems($Params)
	{
		global $APPLICATION;

		echo '<script>';
		if ($Params['bAddToMenu'])
			self::GetMenuTypes($Params['site'], $Params['path'], true);

		if ($Params['loadRecursively'])
			self::GetItemsRecursively(array(
				'path' => $Params['path'],
				'site' => $Params['site'],
				'bCheckEmpty' => true,
				'getFiles' => $Params['getFiles'],
				'loadRoot' => $Params['loadRoot'],
				'bThrowException' => true,
				'operation' => $Params['operation'],
			));
		else
			self::GetItems(array(
				'path' => $Params['path'],
				'site' => $Params['site'],
				'bCheckEmpty' => true,
				'getFiles' => $Params['getFiles'],
				'operation' => $Params['operation'],
			));

		if ($e = $APPLICATION->GetException())
			echo 'window.action_warning = "'.addslashes($e->GetString()).'";';
		else
			echo 'window.load_items_correct = true;';

		echo '</script>';
	}

	public static function BuildDialog($Params)
	{
		$arSites = $Params['arSites'];
		if (count($arSites) > 1) // Site selector
		{
			$u = new CAdminPopup("fd_site_list", "fd_site_list", $Params['arSitesPP'], array('zIndex' => 3520, 'dxShadow' => 0));
			$u->Show();
		}
		?>
<form id="file_dialog" name="file_dialog" onsubmit="return false;">
<table class="bx-file-dialog">
<tr>
	<td class= "bxfd-cntrl-cell">
		<div id="__bx_fd_top_controls_container">
			<table class="bx-fd-top-contr-tbl">
				<tr>
					<?if (count($arSites) > 1):?>
						<td style="width:22px!important; padding: 0 4px 0 5px !important;">
						<div id="__bx_site_selector" bxvalue='<?= CUtil::JSEscape($Params['site'])?>' onclick="oBXDialogControls.SiteSelectorOnClick(this);" class="site_selector_div"><span><?= CUtil::JSEscape($Params['site'])?></span><span class="fd_iconkit site_selector_div_arrow">&nbsp;&nbsp;</span></div>
						</td>
					<?endif;?>
					<td style="padding: 0 2px 0 2px !important;">
						<input class="fd_input" type="text" id="__bx_dir_path_bar">
					</td>
					<td nowrap style="width:170px !important; padding: 0 2px 0 2px !important;">
						<img src="/bitrix/images/1.gif" class="fd_iconkit go_button" id="__bx_dir_path_go" title="<?=GetMessage("FD_GO_TO")?>"/>
						<img src="/bitrix/images/1.gif" __bx_disable="Y" class="fd_iconkit path_back_dis" title="<?=GetMessage("FD_GO_BACK")?>" id="__bx_dir_path_back"/>
						<img src="/bitrix/images/1.gif" __bx_disable="Y" class="fd_iconkit path_forward_dis" title="<?=GetMessage("FD_GO_FORWARD")?>" id="__bx_dir_path_forward"/>
						<img src="/bitrix/images/1.gif" class="fd_iconkit dir_path_up" title="<?=GetMessage("FD_GO_UP")?>" id="__bx_dir_path_up" />
						<img src="/bitrix/images/1.gif" class="fd_iconkit dir_path_root" title="<?=GetMessage("FD_GO_TO_ROOT")?>" id="__bx_dir_path_root" />
						<img src="/bitrix/images/1.gif" class="fd_iconkit new_dir" title="<?=GetMessage("FD_NEW_FOLDER")?>" id="__bx_new_dir" />
						<img src="/bitrix/images/1.gif" class="fd_iconkit refresh" title="<?=GetMessage("FD_REFRESH")?>" onclick="oBXDialogControls.RefreshOnclick();"/>
						<?
						$arViews = Array(
							Array("ID" => 'list', "TEXT" => GetMessage("FD_VIEW_LIST"), "ONCLICK" => "oBXDialogControls.ViewSelector.OnChange('list')"),
							Array("ID" => 'detail', "TEXT" => GetMessage("FD_VIEW_DETAIL"), "ONCLICK" => "oBXDialogControls.ViewSelector.OnChange('detail')"),
							Array("ID" => 'preview', "TEXT" => GetMessage("FD_VIEW_PREVIEW"), "ONCLICK" => "oBXDialogControls.ViewSelector.OnChange('preview')")
						);
						$u = new CAdminPopup("fd_view_list", "fd_view_list", $arViews, array('zIndex' => 2500, 'dxShadow' => 0));
						$u->Show();
						?>
						<img onclick="oBXDialogControls.ViewSelector.OnClick();" src="/bitrix/images/1.gif" id="__bx_view_selector" class="fd_iconkit view_selector"  title="<?=GetMessage("FD_SELECT_VIEW")?>"/>
					</td>
					<td nowrap style="width:180px !important; padding: 0 6px 0 3px !important; text-align:right !important;" align="right">
						<?=GetMessage("FD_SORT_BY")?>:
						<select class="fd_select" id="__bx_sort_selector" title="<?=GetMessage("FD_SORT_BY")?>" style="font-size:11px !important;">
							<option value="name"><?=GetMessage("FD_SORT_BY_NAME")?></option>
							<option value="type"><?=GetMessage("FD_SORT_BY_TYPE")?></option>
							<option value="size"><?=GetMessage("FD_SORT_BY_SIZE")?></option>
							<option value="date"><?=GetMessage("FD_SORT_BY_DATE")?></option>
						</select>
					</td>
					<td style="width:20px !important; padding: 0 6px 0 3px !important;">
						<img src="/bitrix/images/1.gif" class="fd_iconkit sort_up" title="<?=GetMessage("FD_CHANGE_SORT_ORDER")?>" __bx_value="asc" id="__bx_sort_order" />
					</td>
				</tr>
			</table>
		</div>
	</td>
</tr>
<tr>
	<td style="vertical-align:top !important; height:398px !important;">
		<div id="__bx_fd_tree_and_window" style="display:block">
			<table style="width:743px !important; height:250px !important;">
				<tr>
					<td class="bxfd-tree-cont">
						<div id="__bx_treeContainer" class="fd_window bxfd-tree-cont-div"></div>
					</td>
					<td class="bxfd-window-cont">
						<div class="fd_window" ><div id="__bx_windowContainer" class="bxfd-win-cont"></div></div>
					</td>
				</tr>
			</table>
		</div>
		<div id="__bx_fd_preview_and_panel" style="display:block;">
			<table style="width:100% !important;height:132px !important; padding:0 !important;" border="0">
				<tr>
					<td style="width:25% !important; height: 100% !important;">
							<div style="margin: 3px 8px 3px 5px;border:1px solid #C6C6C6"><div style="height:127px;">
							<div id="bxfd_previewContainer"></div>
							<div id="bxfd_addInfoContainer"></div>
						</div></div>
					</td>
					<td style="width:70% !important; vertical-align:top !important;">
						<div class="bxfd-save-cont">
							<table>
								<tr>
									<td class="bxfd-sc-cell" colspan="2">
										<input type="text" style="width:98% !important;margin-bottom:5px !important;" id="__bx_file_path_bar">
										<select style="width:98% !important; display:none; margin-bottom:5px !important;" id="__bx_file_filter"></select>
										<div id="__bx_page_title_cont" style="display:none;">
										<?=GetMessage('FD_PAGE_TITLE')?>:<br/>
										<input type="text" style="width:98% !important;" id="__bx_page_title1">
										</div>
									</td>
								</tr>
								<tr>
									<td class="bxfd-sc-cell2">
										<table id="add2menu_cont" style="display:none"><tr>
											<td><input type="checkbox" id="__bx_fd_add_to_menu"></td>
											<td><label for="__bx_fd_add_to_menu"><?=GetMessage("FD_ADD_PAGE_2_MENU")?></label></td>
										</tr></table>
									</td>
									<td  class="bxfd-sc-cell3">
										<input style="width:100px !important;" type="button" id="__bx_fd_submit_but" value="">
										<input style="width:100px !important;" type="button" onclick="oBXFileDialog.Close()" value="<?=GetMessage("FD_BUT_CANCEL");?>">
									</td>
								</tr>
							</table>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<div id="__bx_fd_load" style="display:none;">
			<div id="bxfd_upload_container"><iframe id="bxfd_iframe_upload" src="javascript:''" frameborder="0"></iframe></div>
		</div>
		<div id="__bx_fd_container_add2menu" class="bxfd-add-2-menu-tab"><? if ($Params['bAddToMenu']) :?><table class="bx-fd-add-2-menu-tbl">
			<tr>
				<td style="height:30px">
					<table class="fd_tab_title">
						<tr>
							<td class="icon"><img class="bxfd-add-to-menu-icon" src="/bitrix/images/1.gif" width="32" height="32"/></td>
							<td class="title"><?=GetMessage("FD_ADD_PAGE_2_MENU_TITLE")?></td>
						</tr>
						<tr>
							<td colspan="2" class="delimiter"></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="height:310px !important; vertical-align:top !important;">
					<table id="add2menuTable" class="bxfd-add-2-menu-tbl">
						<tr>
							<td style="width:200px !important; text-align:right !important;"><?=GetMessage("FD_FILE_NAME")?></td>
							<td style="width:250px !important;" id="__bx_fd_file_name"></td>
						</tr>
						<tr>
							<td align="right"><?=GetMessage("FD_PAGE_TITLE")?>:</td>
							<td><input type="text" id="__bx_page_title2" value=""></td>
						</tr>
						<tr>
							<td align="right"><?=GetMessage("FD_MENU_TYPE")?></td>
							<td>
								<select id="__bx_fd_menutype" name="menutype">
								<?for($i = 0, $n = count($Params['arMenuTypes']); $i < $n; $i++): ?>
								<option value='<?= CUtil::JSEscape($Params['arMenuTypes'][$i]['key'])?>'><?= CUtil::JSEscape($Params['arMenuTypes'][$i]['title'])?></option>
								<? endfor;?>
								</select>
							</td>
						</tr>
						<tr id="e0">
							<td style="vertical-align:top !important; text-align:right !important;"><?=GetMessage("FD_MENU_POINT")?></td>
							<td>
								<input type="radio" name="itemtype" id="__bx_fd_itemtype_n" value="n" checked> <label for="__bx_fd_itemtype_n"><?=GetMessage("FD_ADD_NEW")?></label><br>
								<input type="radio" name="itemtype" id="__bx_fd_itemtype_e" value="e"> <label for="__bx_fd_itemtype_e"><?=GetMessage("FD_ATTACH_2_EXISTENT")?></label>
							</td>
						</tr>
						<tr id="__bx_fd_e1">
							<td align="right"><?=GetMessage("FD_NEW_ITEM_NAME")?></td>
							<td><input type="text" name="newp" id="__bx_fd_newp" value=""></td>
						</tr>
						<tr id="__bx_fd_e2">
							<td align="right"><?=GetMessage("FD_ATTACH_BEFORE")?></td>
							<td>
								<select name="newppos" id="__bx_fd_newppos">
									<?for($i = 0, $n = count($Params['menuItems']); $i < $n; $i++):?>
									<option value="<?= $i + 1 ?>"><?= CUtil::JSEscape($Params['menuItems'][$i])?></option>
									<?endfor;?>
									<option value="0" selected="selected"><?=GetMessage("FD_LAST_POINT")?></option>
								</select>
							</td>
						</tr>
						<tr id="__bx_fd_e3" style="display:none;">
							<td  align="right"><?=GetMessage("FD_ATTACH_2_ITEM")?></td>
							<td>
								<select name="menuitem" id="__bx_fd_menuitem">
									<?for($i = 0; $i < $n; $i++):?>
									<option value="<?= $i + 1 ?>"><?= CUtil::JSEscape($Params['menuItems'][$i])?></option>
									<?endfor;?>
								</select>
							</td>
						</tr>

						<tr>
							<td>
							</td>
							<td>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td class="bx-fd-buttons-cont">
					<input type="button" id="__bx_fd_submit_but2" value="">
					<input type="button" onclick="oBXFileDialog.Close()" value="<?=GetMessage("FD_BUT_CANCEL");?>">
				</td>
			</tr>
		</table><?endif;?></div>
	</td>
</tr>
<tr>
	<td id="__bx_tab_cont" style="background-color: #D7D7D7;"></td>
</tr>
</table>
</form>
<div id="__bx_get_real_size_cont"></div>
		<?
	}

	public static function ShowJS($Params)
	{
		global $APPLICATION;
		$fd_engine_js_src = '/bitrix/js/main/file_dialog_engine.js';
		$fd_css_src = '/bitrix/themes/.default/file_dialog.css';
		$arSites = $Params['arSites'];
		?>
<script>
BXSite = "<?= CUtil::JSEscape($Params['site'])?>";
BXLang = "<?= CUtil::JSEscape($Params['lang'])?>";
if (!window.arFDDirs || !window.arFDFiles || !window.arFDPermission)
{
	arFDDirs = {};
	arFDFiles = {};
	arFDPermission = {};
}
if (!window.arFDMenuTypes)
	arFDMenuTypes = {};
<?
		if ($Params['arMenuTypesScript'])
			echo $Params['arMenuTypesScript'];

		self::GetItemsRecursively(array(
			'path' => $Params['path'],
			'site' => $Params['site'],
			'bCheckEmpty' => true,
			'getFiles' => $Params['getFiles'],
			'loadRoot' => true,
			'bFindCorrectPath' => true,
			'bThrowException' => false,
			'operation' => $Params['operation'],
		));

		if ($e = $APPLICATION->GetException())
			echo 'alert("'.CUtil::JSEscape($e->GetString()).'");';
?>

// Sites array
var arSites = [];
<?foreach ($arSites as $key => $val):?>
arSites['<?= CUtil::JSEscape($key)?>'] = '<?= CUtil::JSEscape($val)?>';
<?endforeach;?>

<?self::AppendLangMess();?>
function OnLoad()
{
	if (!window.BXWaitWindow || !window.BXDialogTree || !window.BXDialogWindow)
	{
		setTimeout(function(){OnLoad();}, 20);
		return;
	}

	window.oWaitWindow = new BXWaitWindow();
	window.oBXDialogTree = new BXDialogTree();
	if(oBXFileDialog.oConfig.operation == 'S' && oBXFileDialog.oConfig.showAddToMenuTab)
		window.oBXMenuHandling = new BXMenuHandling();
	window.oBXDialogControls = new BXDialogControls();
	window.oBXDialogWindow = new BXDialogWindow();
	window.oBXDialogTabs = new BXDialogTabs();
	window.oBXFDContextMenu = false;

	if (oBXFileDialog.oConfig.operation == 'O' && oBXFileDialog.oConfig.showUploadTab)
	{
		oBXDialogTabs.AddTab('tab1', '<?= GetMessage("FD_OPEN_TAB_TITLE")?>', _Show_tab_OPEN, true);
		oBXDialogTabs.AddTab('tab2', '<?= GetMessage("FD_LOAD_TAB_TITLE")?>',_Show_tab_LOAD, false);
	}
	else if(oBXFileDialog.oConfig.operation == 'S' && oBXFileDialog.oConfig.showAddToMenuTab)
	{
		oBXDialogTabs.AddTab('tab1', '<?= GetMessage("FD_SAVE_TAB_TITLE")?>', _Show_tab_SAVE, true);
		oBXDialogTabs.AddTab('tab2', '<?= GetMessage("FD_MENU_TAB_TITLE")?>', _Show_tab_MENU, false);
		BX('add2menu_cont').style.display = 'block';
	}
	oBXDialogTabs.DisplayTabs();

	oBXDialogTree.Append();
	oBXFileDialog.SubmitFileDialog = SubmitFileDialog;

	BX.onCustomEvent(window, 'onFileDialogLoaded');
}

// Append CSS
if (!window.fd_styles_link || !window.fd_styles_link.parentNode)
	window.fd_styles_link = jsUtils.loadCSSFile("<?=$fd_css_src.'?v='.@filemtime($_SERVER['DOCUMENT_ROOT'].$fd_css_src)?>");

// Append file with File Dialog engine
if (window.BXDialogTree)
	OnLoad();
else
	BX.loadScript("<?=$fd_engine_js_src.'?v='.@filemtime($_SERVER['DOCUMENT_ROOT'].$fd_engine_js_src)?>", OnLoad);

</script>
<?
	}

	public static function AppendLangMess()
	{
		//*  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *
		// FD_MESS - Array of messages for JS files
		//*  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *  *
		?>
var FD_MESS =
{
	FD_SAVE_TAB_TITLE : '<?=GetMessageJS('FD_SAVE_TAB_TITLE')?>',
	FD_OPEN_DIR : '<?=GetMessageJS('FD_OPEN_DIR')?>',
	FD_OPEN_TAB_TITLE : '<?=GetMessageJS('FD_OPEN_TAB_TITLE')?>',
	FD_CLOSE : '<?=GetMessageJS('FD_CLOSE')?>',
	FD_SORT_SIZE : '<?=GetMessageJS('FD_SORT_SIZE')?>',
	FD_SORT_DATE : '<?=GetMessageJS('FD_SORT_DATE')?>',
	FD_SORT_NAME : '<?=GetMessageJS('FD_SORT_NAME')?>',
	FD_SORT_TYPE : '<?=GetMessageJS('FD_SORT_TYPE')?>',
	FD_BUT_OPEN : '<?=GetMessageJS('FD_BUT_OPEN')?>',
	FD_BUT_SAVE : '<?=GetMessageJS('FD_BUT_SAVE')?>',
	FD_ALL_FILES : '<?=GetMessageJS('FD_ALL_FILES')?>',
	FD_ALL_IMAGES : '<?=GetMessageJS('FD_ALL_IMAGES')?>',
	FD_BYTE : '<?=GetMessageJS('FD_BYTE')?>',
	FD_EMPTY_FILENAME : '<?=GetMessageJS('FD_EMPTY_FILENAME')?>',
	FD_INPUT_NEW_PUNKT_NAME : '<?=GetMessageJS('FD_INPUT_NEW_PUNKT_NAME')?>',
	FD_LAST_POINT : '<?=GetMessageJS('FD_LAST_POINT')?>',
	FD_NEWFOLDER_EXISTS : '<?=GetMessageJS('FD_NEWFOLDER_EXISTS')?>',
	FD_NEWFILE_EXISTS : '<?=GetMessageJS('FD_NEWFILE_EXISTS')?>',
	FD_RENAME : '<?=GetMessageJS('FD_RENAME')?>',
	FD_DELETE : '<?=GetMessageJS('FD_DELETE')?>',
	FD_RENAME_TITLE : '<?=GetMessageJS('FD_RENAME_TITLE')?>',
	FD_DELETE_TITLE : '<?=GetMessageJS('FD_DELETE_TITLE')?>',
	FD_CONFIRM_DEL_DIR : '<?=GetMessageJS('FD_CONFIRM_DEL_DIR')?>',
	FD_CONFIRM_DEL_FILE : '<?=GetMessageJS('FD_CONFIRM_DEL_FILE')?>',
	FD_EMPTY_NAME : '<?=GetMessageJS('FD_EMPTY_NAME')?>',
	FD_INCORRECT_NAME : '<?=GetMessageJS('FD_INCORRECT_NAME')?>',
	FD_LOADIND : '<?=GetMessageJS('FD_LOADING')?>...',
	FD_EMPTY_NAME : '<?=GetMessageJS('FD_EMPTY_NAME')?>',
	FD_INCORRECT_EXT : '<?=GetMessageJS('FD_INCORRECT_EXT')?>',
	FD_LOAD_EXIST_CONFIRM : '<?=GetMessageJS('FD_LOAD_EXIST_CONFIRM')?>',
	FD_SESS_EXPIRED : '<?=GetMessageJS('BX_FD_ERROR').': '.GetMessageJS('BX_FD_SESS_EXPIRED')?>',
	FD_ERROR : '<?=GetMessageJS('BX_FD_ERROR')?>',
	FD_FILE : '<?=GetMessageJS('FD_FILE')?>',
	FD_FOLDER : '<?=GetMessageJS('FD_FOLDER')?>',
	FD_IMAGE : '<?=GetMessageJS('FD_IMAGE')?>'
};
<?
	}

	public static function GetMenuTypes($site, $path, $bEchoResult = false)
	{
		global $USER, $APPLICATION;

		if(!CModule::IncludeModule("fileman"))
		{
			$APPLICATION->ThrowException(GetMessage("BX_FD_NO_FILEMAN"));
			return false;
		}

		$path = Rel2Abs("/", $APPLICATION->UnJSEscape($path));
		$path = rtrim($path, "/")."/";

		$armt = GetMenuTypes($site);
		$arAllItems = Array();
		$arMenuTypes = Array();
		$strSelected = "";
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		foreach($armt as $key => $title)
		{
			$menuname = $path.".".$key.".menu.php";
			if(!$USER->CanDoFileOperation('fm_view_file', Array($site, $menuname)))
				continue;

			$arItems = Array();

			$res = CFileMan::GetMenuArray($DOC_ROOT.$menuname);
			$aMenuLinksTmp = $res["aMenuLinks"];

			for($j = 0, $n = count($aMenuLinksTmp); $j < $n; $j++)
			{
				$aMenuLinksItem = $aMenuLinksTmp[$j];
				$arItems[] = htmlspecialcharsbx($aMenuLinksItem[0]);
			}
			$arAllItems[$key] = $arItems;
			if($strSelected == "")
				$strSelected = $key;
			$arMenuTypes[] = array('key' => $key, 'title' => $title." [".$key."]");
		}

		$arTypes = array_keys($arAllItems);
		$strTypes="";
		$strItems="";
		for($i = 0; $i < count($arTypes); $i++)
		{
			if($i>0)
			{
				$strTypes .= ",";
				$strItems .= ",";
			}
			$strTypes.="'".CUtil::JSEscape($arTypes[$i])."'";
			$arItems = $arAllItems[$arTypes[$i]];
			$strItems .= "[";
			for($j = 0; $j < count($arItems); $j++)
			{
				if($j>0)$strItems .= ",";
				$strItems.="'".CUtil::JSEscape($arItems[$j])."'";
			}
			$strItems .= "]";
		}

		$scriptRes = "\n".'arFDMenuTypes["'.CUtil::JSEscape($path).'"] = {types: ['.$strTypes.'], items: ['.$strItems.']};'."\n";

		if ($bEchoResult)
		{
			echo $scriptRes;
			return null;
		}
		return array($arMenuTypes, $scriptRes, $arAllItems[$strSelected]);
	}

	public static function GetItems($Params)
	{
		global $APPLICATION, $USER;
		static $checkChildren, $genTmb;

		if (!isset($checkChildren, $genTmb))
		{
			$checkChildren = COption::GetOptionString("fileman", "file_dialog_check_children", "Y") == 'Y';
			$genTmb = COption::GetOptionString("fileman", "file_dialog_gen_thumb", "Y") == 'Y';
		}

		if(strlen($Params["site"]) > 2)
		{
			if (!$USER->CanDoOperation('clouds_browse'))
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_ACCESS_DENIED'), 'access_denied');
				return;
			}

			if($Params['operation'] !== 'O')
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT').' [clouds 04]', 'path_corrupt');
				return;
			}

			if(!CModule::IncludeModule('clouds'))
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT').' [clouds 01]', 'path_corrupt');
				return;
			}

			$obBucket = null;
			foreach(CCloudStorageBucket::GetAllBuckets() as $arBucket)
			{
				if($arBucket["ACTIVE"] == "Y" && $arBucket["BUCKET"] === $Params["site"])
					$obBucket = new CCloudStorageBucket($arBucket["ID"]);
			}

			if(!$obBucket)
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT').' [clouds 02]', 'path_corrupt');
				return;
			}

			if(!$obBucket->Init())
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT').' [clouds 03]', 'path_corrupt');
				return;
			}

			$path = preg_replace("#[\\\\\\/]+#", "/", "/".$APPLICATION->UnJSEscape($Params['path']));
			$path_js = $path == "" ? "/" : addslashes(htmlspecialcharsex($path));
			$path_js = str_replace("//", "/", $path_js);

			$arFiles = $obBucket->ListFiles($path);
			if(!is_array($arFiles))
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT').' [clouds 05]', 'path_corrupt');
				return;
			}
?>
arFDDirs['<?=$path_js?>'] = [];
arFDFiles['<?=$path_js?>'] = [];
<?
			foreach ($arFiles["dir"] as $ind => $dir)
			{
?>
arFDDirs['<?=$path_js?>'][<?=$ind?>] =
{
	name : '<?=CUtil::JSEscape($dir)?>',
	path : '<?=CUtil::JSEscape(preg_replace("#[\\\\\\/]+#", "/", $path."/".$dir));?>',
	empty: false,
	permission : {del : false, ren : false},
	date : '',
	timestamp : '',
	size : 0
};
<?
			}


			if ($Params['getFiles'])
			{
				foreach ($arFiles['file'] as $ind => $file)
				{
?>
arFDFiles['<?=$path_js?>'][<?=$ind?>] =
{
	name : '<?=CUtil::JSEscape($file)?>',
	path : '<?=CUtil::JSEscape($obBucket->GetFileSRC($path."/".$file))?>',
	permission : {del : false, ren : false},
	date : '',
	timestamp : '',
	size : '<?=$arFiles["file_size"][$ind];?>'
};
<?
				}
			}

		?>
arFDPermission['<?=$path_js?>'] = {
	new_folder : false,
	upload : false
};
<?

			return;
		}

		$io = CBXVirtualIo::GetInstance();

		$site = $Params['site'];
		$path = $io->CombinePath("/", $APPLICATION->UnJSEscape($Params['path']));
		$path_js = $path == "" ? "/" : addslashes(htmlspecialcharsex($path));
		$path_js = str_replace("//", "/", $path_js);
		$bCheckEmpty = $Params['bCheckEmpty'];

		$rootPath = CSite::GetSiteDocRoot($site);
		if (!$io->FileExists($rootPath.$path) && !$io->DirectoryExists($rootPath.$path) && $Params['bThrowException'] === true)
		{
			$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT'), 'path_corrupt');
			return;
		}
		elseif (!$USER->CanDoFileOperation('fm_view_listing', array($site, $path)))
		{
			$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_ACCESS_DENIED'), 'access_denied');
			return;
		}

		$arDirs = array(); $arFiles = array();
		GetDirList(array($site, $path), $arDirs, $arFiles, array(), array("name_nat" => "asc"), "DF", false, true);
?>
arFDDirs['<?=$path_js?>'] = [];
arFDFiles['<?=$path_js?>'] = [];
<?
		$ind = -1;
		foreach ($arDirs as $Dir)
		{
			$name = addslashes(htmlspecialcharsex($Dir["NAME"]));
			$path_i = addslashes(htmlspecialcharsex($path))."/".$name;
			$path_i = str_replace("//", "/", $path_i);
			$arPath_i = Array($site, $path_i);

			if (!$USER->CanDoFileOperation('fm_view_listing', $arPath_i))
				continue;
			$ind++;

			$empty = true;
			if ($bCheckEmpty) // Find subfolders inside
			{
				$dirTmp = $io->GetDirectory($rootPath.$path.'/'.$name);
				$arDirTmpChildren = $dirTmp->GetChildren();
				foreach ($arDirTmpChildren as $child)
				{
					if(!$child->IsDirectory())
						continue;
					$empty = false;
					break;
				}
			}
			$perm_del = $USER->CanDoFileOperation('fm_delete_folder', $arPath_i) ? 'true' : 'false';
			$perm_ren = $USER->CanDoFileOperation('fm_rename_folder', $arPath_i) ? 'true' : 'false';

?>
arFDDirs['<?=$path_js?>'][<?=$ind?>] =
{
	name : '<?= $name?>',
	path : '<?=$path_i?>',
	empty: <?= $empty ? 'true' : 'false';?>,
	permission : {del : <?=$perm_del?>, ren : <?=$perm_ren?>},
	date : '<?=$Dir["DATE"];?>',
	timestamp : '<?=$Dir["TIMESTAMP"];?>',
	size : 0
};
<?
		}

		if ($Params['getFiles'])
		{
			$ind = -1;
			foreach ($arFiles as $File)
			{
				$name = addslashes(htmlspecialcharsex($File["NAME"]));
				$path_i = addslashes(htmlspecialcharsex($File["ABS_PATH"]));
				$path_i = str_replace("//", "/", $path_i);
				$arPath_i = Array($site, $path_i);

				if (!$USER->CanDoFileOperation('fm_view_file', $arPath_i))
					continue;
				$ind++;

				$perm_del = $USER->CanDoFileOperation('fm_delete_file', $arPath_i) ? 'true' : 'false';
				$perm_ren = $USER->CanDoFileOperation('fm_rename_file', $arPath_i) ? 'true' : 'false';

				$imageAddProps = '';
				if ($genTmb)
				{
					$ext = strtolower(GetFileExtension($name));
					if (in_array($ext, array('gif','jpg','jpeg','png','jpe','bmp'))) // It is image
					{
						$upload_dir = COption::GetOptionString("main", "upload_dir", "upload");
						$tmbPath = "/".$upload_dir."/tmp/fd_tmb".$path_i;
						$destinationFile = $rootPath.$tmbPath;
						if (!file_exists($destinationFile))
						{
							$sourceFile = $File['PATH'];
							if (CFile::ResizeImageFile($sourceFile, $destinationFile, array('width' => 140, 'height' => 110)))
								$imageAddProps = ",\n".'tmb_src : \''.CUtil::JSEscape($tmbPath).'\'';
						}
						else
							$imageAddProps = ",\n".'tmb_src : \''.CUtil::JSEscape($tmbPath).'\'';
					}
				}
?>
arFDFiles['<?=$path_js?>'][<?=$ind?>] =
{
	name : '<?=$name?>',
	path : '<?=$path_i?>',
	permission : {del : <?=$perm_del?>, ren : <?=$perm_ren?>},
	date : '<?=$File["DATE"];?>',
	timestamp : '<?=$File["TIMESTAMP"];?>',
	size : '<?=$File["SIZE"];?>'<?=$imageAddProps?>
};
<?
			}
		}

		$arPath = array($site, $path);
		?>
arFDPermission['<?=$path_js?>'] = {
	new_folder : <?= ($USER->CanDoFileOperation('fm_create_new_folder',$arPath) ? 'true' : 'false');?>,
	upload : <?= ($USER->CanDoFileOperation('fm_upload_file',$arPath) ? 'true' : 'false');?>
};
<?
	}

	public static function GetItemsRecursively($Params)
	{
		global $APPLICATION;

		$io = CBXVirtualIo::GetInstance();

		$path = $io->CombinePath("/", $APPLICATION->UnJSEscape($Params['path']));
		$rootPath = CSite::GetSiteDocRoot($Params['site']);

		if (!$io->FileExists($rootPath.$path) && !$io->DirectoryExists($rootPath.$path))
		{
			if ($Params['bThrowException'] === true)
			{
				$APPLICATION->ThrowException(GetMessage('BX_FD_ERROR').': '.GetMessage('BX_FD_PATH_CORRUPT'), 'path_corrupt');
				return;
			}
			$path = '/';
		}

		$arPath = explode('/', $path);

		if ($Params['loadRoot'] !== false)
		{
			$Params['path'] = '/';
			self::GetItems($Params);
		}

		$curPath = '';
		for ($i = 0, $l = count($arPath); $i < $l; $i++)
		{
			$catalog = trim($arPath[$i], "/\\");

			if ($catalog != "")
			{
				$curPath .= '/'.$catalog;
				$Params['path'] = $curPath;
				self::GetItems($Params);
			}
		}
	}

	public static function MakeNewDir($Params)
	{
		global $USER, $APPLICATION;

		$io = CBXVirtualIo::GetInstance();
		$path = $io->CombinePath("/", $APPLICATION->UnJSEscape($Params['path']));
		$site = $Params['site'];

		if(CModule::IncludeModule("fileman"))
		{
			$arPath = Array($site, $path);
			$DOC_ROOT = CSite::GetSiteDocRoot($site);
			$abs_path = $DOC_ROOT.$path;
			$dirname = str_replace("/", "_", $APPLICATION->UnJSEscape($Params['name']));
			$strWarning = '';

			//Check access to folder
			if (!$USER->CanDoFileOperation('fm_create_new_folder', $arPath))
			{
				$strWarning = GetMessage("ACCESS_DENIED");
			}
			elseif(!$io->DirectoryExists($abs_path))
			{
				$strWarning = GetMessage("FD_FOLDER_NOT_FOUND", array('#PATH#' => addslashes(htmlspecialcharsbx($path))));
			}
			else
			{
				if (strlen($dirname) > 0 && ($mess = self::CheckFileName($dirname)) !== true)
				{
					$strWarning = $mess;
				}
				elseif(strlen($dirname) <= 0)
				{
					$strWarning = GetMessage("FD_NEWFOLDER_ENTER_NAME");
				}
				else
				{
					$pathto = Rel2Abs($path, $dirname);
					if($io->DirectoryExists($DOC_ROOT.$pathto))
						$strWarning = GetMessage("FD_NEWFOLDER_EXISTS");
					else
						$strWarning = CFileMan::CreateDir(Array($site, $pathto));
				}
			}
		}
		else
		{
			$strWarning = GetMessage("BX_FD_NO_FILEMAN");
		}

		self::EchoActionStatus($strWarning);

		if ($strWarning == '')
			self::LoadItems(array('path' => $path, 'site' => $site, 'bAddToMenu' => $Params['bAddToMenu'], 'loadRecursively' => false, 'getFiles' => $Params['getFiles']));
	}

	public static function Remove($Params)
	{
		global $USER, $APPLICATION;

		$path = $site = '';

		if(CModule::IncludeModule("fileman"))
		{
			$io = CBXVirtualIo::GetInstance();
			$path = Rel2Abs("/", $APPLICATION->UnJSEscape($Params['path']));
			$path = CFileMan::SecurePathVar($path);
			$site = $Params['site'];

			$arPath = Array($site, $path);
			$DOC_ROOT = CSite::GetSiteDocRoot($site);
			$abs_path = $DOC_ROOT.$path;
			$strWarning = '';

			$type = false;
			if ($io->DirectoryExists($abs_path))
				$type = 'folder';
			if ($io->FileExists($abs_path))
				$type = 'file';

			//Check access to folder or file
			if (!$type) // Not found
				$strWarning = GetMessage("FD_ELEMENT_NOT_FOUND", array('#PATH#' => addslashes(htmlspecialcharsbx($path))));
			elseif (!$USER->CanDoFileOperation('fm_delete_'.$type, $arPath)) // Access denied
				$strWarning = GetMessage("ACCESS_DENIED");
			else // Ok, delete it!
				$strWarning = CFileMan::DeleteEx($path);
		}
		else
		{
			$strWarning = GetMessage("BX_FD_NO_FILEMAN");
		}

		self::EchoActionStatus($strWarning);

		if ($strWarning == '')
		{
			// get parent dir path and load content
			$parPath = substr($path, 0, strrpos($path, '/'));
			self::LoadItems(array('path' => $parPath, 'site' => $site, 'bAddToMenu' => $Params['bAddToMenu'], 'loadRecursively' => false, 'getFiles' => $Params['getFiles']));
		}
	}

	public static function Rename($Params)
	{
		global $USER, $APPLICATION;

		$path = $site = '';

		if(CModule::IncludeModule("fileman"))
		{
			$io = CBXVirtualIo::GetInstance();
			$path = Rel2Abs("/", $APPLICATION->UnJSEscape($Params['path']));
			$path = CFileMan::SecurePathVar($path);
			$site = $Params['site'];

			$name = str_replace("/", "_", $APPLICATION->UnJSEscape($Params['name']));
			$oldName = str_replace("/", "_", $APPLICATION->UnJSEscape($Params['old_name']));

			$DOC_ROOT = CSite::GetSiteDocRoot($site);

			$oldPath = Rel2Abs($path, $oldName);
			$newPath = Rel2Abs($path, $name);
			$oldAbsPath = $DOC_ROOT.$oldPath;
			$newAbsPath = $DOC_ROOT.$newPath;
			$arPath1 = Array($site, $oldPath);
			$arPath2 = Array($site, $newPath);
			$strWarning = '';

			$type = false;
			if ($io->DirectoryExists($oldAbsPath))
				$type = 'folder';
			if ($io->FileExists($oldAbsPath))
				$type = 'file';

			if (
				$type == 'file' &&
				!$USER->CanDoOperation('edit_php') &&
				(
					substr($oldName, 0, 1) == "."
					||
					substr($name, 0, 1) == "."
					||
					(
						HasScriptExtension($oldName) &&
						!HasScriptExtension($name)
					)
					||
					(
						HasScriptExtension($name) &&
						!HasScriptExtension($oldName)
					)
				)
			)
			{
				$strWarning = GetMessage("ACCESS_DENIED");
			}
			elseif (!$type)
				$strWarning = GetMessage("FD_ELEMENT_NOT_FOUND", array('#PATH#' => addslashes(htmlspecialcharsbx($path))));
			elseif (!$USER->CanDoFileOperation('fm_rename_'.$type,$arPath1) || !$USER->CanDoFileOperation('fm_rename_'.$type,$arPath2))
				$strWarning = GetMessage("ACCESS_DENIED");
			else
			{
				if (strlen($name) > 0 && ($mess = self::CheckFileName($name)) !== true)
					$strWarning = $mess;
				else if(strlen($name) <= 0)
					$strWarning = GetMessage("FD_ELEMENT_ENTER_NAME");
				else
				{
					if($io->FileExists($DOC_ROOT.$newPath) || $io->DirectoryExists($DOC_ROOT.$newPath))
						$strWarning = GetMessage("FD_ELEMENT_EXISTS");
					elseif(!$io->Rename($oldAbsPath, $newAbsPath))
						$strWarning = GetMessage("FD_RENAME_ERROR");
				}
			}
		}
		else
		{
			$strWarning = GetMessage("BX_FD_NO_FILEMAN");
		}

		self::EchoActionStatus($strWarning);

		if ($strWarning == '')
			self::LoadItems(array('path' => $path, 'site' => $site, 'bAddToMenu' => $Params['bAddToMenu'], 'loadRecursively' => false, 'getFiles' => $Params['getFiles']));
	}

	public static function CheckFileName($str)
	{
		$io = CBXVirtualIo::GetInstance();
		if (!$io->ValidateFilenameString($str))
			return GetMessage("FD_INCORRECT_NAME");
		return true;
	}

	public static function EchoActionStatus($strWarning = '')
	{
?>
		<script>
		<? if ($strWarning == ''): ?>
			window.action_status = true;
		<?else: ?>
			window.action_warning = '<?= CUtil::JSEscape($strWarning)?>';
			window.action_status = false;
		<?endif;?>
		</script>
<?
	}

	public static function SetUserConfig($Params)
	{
		global $APPLICATION;
		$Params['path'] = $APPLICATION->UnJSEscape($Params['path']);
		$Params['site'] = $APPLICATION->UnJSEscape($Params['site']);
		$Params['view'] = in_array($Params['view'], array('detail', 'preview')) ? $Params['view'] : 'list';
		$Params['sort'] = in_array($Params['sort'], array('size', 'type', 'date')) ? $Params['sort'] : 'name';
		$Params['sort_order'] = ($Params['sort_order'] == 'asc') ? 'asc' : 'des';

		CUserOptions::SetOption("fileman", "file_dialog_config", addslashes($Params['site'].';'.$Params['path'].';'.$Params['view'].';'.$Params['sort'].';'.$Params['sort_order']));
	}

	public static function PreviewFlash($Params)
	{
		if(CModule::IncludeModule("fileman"))
		{
			global $APPLICATION, $USER;

			if(CModule::IncludeModule("compression"))
				CCompress::Disable2048Spaces();

			$path = $Params['path'];
			$path = CFileMan::SecurePathVar($path);
			$path = Rel2Abs("/", $path);
			$arPath = Array($Params['site'], $path);

			if(!$USER->CanDoFileOperation('fm_view_file', $arPath))
				$path = '';

			if ($path == "")
				return;

			$APPLICATION->RestartBuffer();
?>
<HTML>
<HEAD></HEAD>
<BODY id="__flash" style="margin:0; border-width: 0;">
<embed id="__flash_preview" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" name="__flash_preview" quality="high" width="<?=$Params['width']?>" height="<?=$Params['height']?>" src="<?=htmlspecialcharsex($path)?>"></embed>
</BODY>
</HTML>
<?
			die();
		}
	}

	public static function ShowUploadForm($Params)
	{
		$lang = htmlspecialcharsex($Params['lang']);
		$site = htmlspecialcharsex($Params['site']);
		$res = $Params['file'] ? self::UploadFile($Params) : '';
		?>
<HTML>
<HEAD><?=$res?></HEAD>
<BODY style="margin:0 !important; background-color:#F4F4F4; font-family:Verdana,serif;">
<form name="frmLoad" action="file_dialog.php?action=uploader&lang=<?=$lang?>&site=<?=$site?>&<?=bitrix_sessid_get()?>" onsubmit="return parent.oBXDialogControls.Uploader.OnSubmit();" method="post" enctype="multipart/form-data">
	<input id="__bx_fd_server_site" type="hidden" name="cur_site" value="<?=$site?>" />
	<table style="width: 540px; height: 123px; font-size:70%">
		<tr height="0%">
			<td style="width:40%;" align="left">
				<?=GetMessage('FD_LOAD_FILE')?>:
			</td>
			<td style="width:60%; padding-top: 0;" valign="top" align="left">
				<input id="__bx_fd_load_file" size="45" type="file" name="load_file">
			</td>
		</tr>
		<tr height="0%">
			<td style="width:40%;" align="left">
				<?=GetMessage("FD_FILE_NAME_ON_SERVER");?>
			</td>
			<td style="width:60%;" align="left">
				<input id="__bx_fd_server_file_name" style="width:100%;" type="text">
			</td>
		</tr>
		<tr height="100%">
			<td style="width:100%;" valign="top" align="left" colspan="2">
				<table style="font-size:100%"><tr>
				<td><input id="_bx_fd_upload_and_open" value="Y" type="checkbox" name="upload_and_open" checked="checked"></td>
				<td><label for="_bx_fd_upload_and_open"> <?=GetMessage("FD_UPLOAD_AND_OPEN");?></label></td>
				</tr></table>
			</td>
		</tr>
		<tr height="0%">
			<td style="width:100%; padding:0 8px 5px 0" valign="bottom" align="right" colSpan="2">
				<input  type="submit" value="<?=GetMessage("FD_BUT_LOAD");?>">
				<input style="width:100px;" type="button" onclick="parent.oBXFileDialog.Close()" value="<?=GetMessage("FD_BUT_CANCEL");?>">
			</td>
		</tr>
	</table>
	<input type="hidden" name="MAX_FILE_SIZE" value="1000000000">
	<input type="hidden" name="lang" value="<?=$lang?>">
	<input type="hidden" name="site" value="<?=$site?>">
	<input id="__bx_fd_rewrite" type="hidden" name="rewrite" value="N">
	<input id="__bx_fd_upload_path" type="hidden" name="path" value="">
	<input id="__bx_fd_upload_fname" type="hidden" name="filename" value="">
</form>
</BODY>
</HTML>
<?
	}

	public static function UploadFile($Params)
	{
		$buffer = 'parent.oWaitWindow.Hide();';
		$F = $Params['file'];

		$io = CBXVirtualIo::GetInstance();

		if (isset($F["tmp_name"]) && strlen($F["tmp_name"]) > 0 && strlen($F["name"]) > 0 || is_uploaded_file($F["tmp_name"]))
		{
			global $APPLICATION, $USER;
			$strWarning = '';
			$filename = $Params['filename'];
			$path = $Params['path'];
			$site = $Params['site'];
			$upload_and_open = $Params['upload_and_open'];
			$rootPath = CSite::GetSiteDocRoot($site);

			if($filename == '')
				$filename = $F["name"];

			$pathto = Rel2Abs($path, $filename);

			if (strlen($filename) > 0 && ($mess = self::CheckFileName($filename)) !== true)
				$strWarning = $mess;

			if($strWarning == '')
			{
				$fn = $io->ExtractNameFromPath($pathto);
				if($USER->CanDoFileOperation('fm_upload_file', array($site, $pathto)) &&
					($USER->IsAdmin() || (!HasScriptExtension($fn) && substr($fn, 0, 1) != "." && $io->ValidateFilenameString($fn)))
				)
				{
					if(!$io->FileExists($rootPath.$pathto) || $_REQUEST["rewrite"] == "Y")
					{
						//************************** Quota **************************//
						$bQuota = true;
						$quota = null;
						if(COption::GetOptionInt("main", "disk_space") > 0)
						{
							$bQuota = false;
							$quota = new CDiskQuota();
							if ($quota->checkDiskQuota(array("FILE_SIZE"=>filesize($F["tmp_name"]))))
								$bQuota = true;
						}
						//************************** Quota **************************//
						if ($bQuota)
						{
							$io->Copy($F["tmp_name"], $rootPath.$pathto);
							$flTmp = $io->GetFile($rootPath.$pathto);
							$flTmp->MarkWritable();

							if(COption::GetOptionInt("main", "disk_space") > 0)
								CDiskQuota::updateDiskQuota("file", $flTmp->GetFileSize(), "copy");

							$buffer = 'setTimeout(function(){parent.oBXDialogControls.Uploader.OnAfterUpload("'.$filename.'", '.($upload_and_open == "Y" ? 'true' : 'false').');}, 50);';
						}
						else
						{
							$strWarning = $quota->LAST_ERROR;
						}
					}
					else
					{
						$strWarning = GetMessage("FD_LOAD_EXIST_ALERT");
					}
				}
				else
				{
					$strWarning = GetMessage("FD_LOAD_DENY_ALERT");
				}
			}
		}
		else
		{
			$strWarning = GetMessage("FD_LOAD_ERROR_ALERT");
		}

		if ($strWarning <> '')
			$buffer = 'alert("'.addslashes(htmlspecialcharsex($strWarning)).'");';

		return '<script>'.$buffer.'</script>';
	}

}
