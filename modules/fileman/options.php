<?
$module_id = "fileman";
$dicsRelPath = '/bitrix/modules/fileman/dictionaries';
$gzDicsRelPath = BX_PERSONAL_ROOT.'/tmp/dics';
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");

CUtil::InitJSCore();

if (!$USER->CanDoOperation('fileman_view_all_settings'))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

function isValidLang($lang)
{
	$rsLang = CLanguage::GetList($by="sort", $order="desc");
	$is_valid_lang = false;
	while ($arLang = $rsLang->Fetch())
	{
		if ($lang==$arLang["LID"])
		{
			$is_valid_lang = true;
			break;
		}
	}
	return $is_valid_lang;
}

if ($REQUEST_METHOD=="GET" && $USER->CanDoOperation('fileman_edit_all_settings') && strlen($RestoreDefaults)>0 && check_bitrix_sessid())
{
	COption::RemoveOption("fileman");
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}


global $MESS;
IncludeModuleLangFile(__FILE__);

//Default file extensions;
$script_files_default = "php,php3,php4,php5,php6,phtml,pl,asp,aspx,cgi,exe,ico,shtm,shtml";

if($REQUEST_METHOD == "POST" && strlen($Update)>0 && $USER->CanDoOperation('fileman_edit_all_settings') && check_bitrix_sessid())
{
	if($default_edit!="html" && $default_edit!="php")
		$default_edit="text";
	COption::SetOptionString($module_id, "default_edit", $default_edit);

	COption::SetOptionString($module_id, "use_medialib", $use_medialib == 'Y' ? 'Y' : 'N');

	COption::SetOptionString($module_id, "use_editor_3", $use_editor_3 == 'Y' ? 'Y' : 'N');
	$useEditor3 = $use_editor_3 == "Y";

	if (!$useEditor3)
	{
		COption::SetOptionString($module_id, "htmleditor_fullscreen", $htmleditor_fullscreen == "Y" ? "Y" : "N");
		COption::SetOptionString($module_id, "show_untitled_styles", $show_untitled_styles);
		COption::SetOptionString($module_id, "render_styles_in_classlist", $render_styles_in_classlist);
		COption::SetOptionString($module_id, "allow_render_components", $allow_render_components == 'Y' ? 'Y' : 'N');


		/* **********  Toolbars config ************/
		if (isset($_POST['tlbr']) && is_array($_POST['tlbr']))
		{
			foreach ($_POST['tlbr'] as $type => $arToolbars)
			{
				if ($arToolbars == 'clean')
					COption::RemoveOption($module_id, "toolbar_config_".$type);

				// Global toolbar
				if (count($arToolbars) == 1 && is_array($arToolbars['global']))
					COption::SetOptionString($module_id, "toolbar_config_".$type, serialize($arToolbars['global']));
				else // Standart mode
					COption::SetOptionString($module_id, "toolbar_config_".$type, serialize($arToolbars));
			}
		}

		// LCA - limit component access
		COption::SetOptionString($module_id, "use_lca", ($use_lca == 'Y' ? 'Y' : 'N'));
	}
	COption::SetOptionString($module_id, "replace_new_lines", isset($_POST['replace_new_lines'])? "Y" : "N");

	// ******** Spell ********
	COption::SetOptionString($module_id, "use_pspell", isset($_POST['use_pspell'])? "Y" : "N");

	if (isset($_POST['user_dics_path']) && $_POST['user_dics_path']!='')
		COption::SetOptionString($module_id, "user_dics_path", $_POST['user_dics_path']);
	else
		COption::SetOptionString($module_id, "user_dics_path","/bitrix/modules/fileman/u_dics");


	if (isset($_POST['use_separeted_dics']))
		COption::SetOptionString($module_id, "use_separeted_dics", "Y");
	else
		COption::SetOptionString($module_id, "use_separeted_dics", "N");

	COption::SetOptionString($module_id, "use_custom_spell", "N");


	//Handle dictionary loading
	if (isset($_POST['dic_lang']) && isset($_FILES['dic_aff']) && isset($_FILES['dic_base']) && $_FILES['dic_aff']['name'] != '' && 	$_FILES['dic_base']['name'] != '')
	{
		$dic_lang = $_POST['dic_lang'];
		if (isValidLang($dic_lang))
		{
			$lang_dir = $_SERVER['DOCUMENT_ROOT'].$dicsRelPath.'/'.$dic_lang;
			$dics_dir = $_SERVER['DOCUMENT_ROOT'].$dicsRelPath.'/'.$dic_lang.'/dics';

			if (!file_exists($lang_dir))
				mkdir($lang_dir, BX_DIR_PERMISSIONS);

			$source=$_FILES['dic_base']['tmp_name'];
			$target = $lang_dir.'/'.$dic_lang.'.dic';
			if (file_exists($target))
				unlink ($target);
			move_uploaded_file($source, $target);

			$source=$_FILES['dic_aff']['tmp_name'];
			$target = $lang_dir.'/'.$dic_lang.'.aff';
			if (file_exists($target))
				unlink ($target);
			move_uploaded_file($source, $target);

			if (!file_exists($dics_dir))
				mkdir($dics_dir, BX_DIR_PERMISSIONS);
			COption::SetOptionString($module_id, $dic_lang."_dic_indexed", "N");
		}
	}

	//Handle dictionary removing
	if (isset($_POST['del_dic']))
	{
		$lang_dir = $_SERVER['DOCUMENT_ROOT'].$dicsRelPath.'/'.$_POST['del_dic'];
		if (file_exists($lang_dir) && is_dir($lang_dir))
		{
			$dicDir = dir($lang_dir);
			while (false !== ($entry = $dicDir->read()))
			{
				$entry_path = $dicDir->path.'/'.$entry;
				if (is_dir($entry_path) && $entry=='dics')
				{
					//Removing files from 'dics' directory
					$dicsDir = dir($entry_path);
					while (false !== ($dic = $dicsDir->read()))
					{
						$dic_path = $dicsDir->path.'/'.$dic;
						if (is_file($dic_path))
							unlink ($dic_path);
					}
					$dicsDir->close();
					//removing 'dics' directory
					rmdir($entry_path);
				}
				elseif (is_file($entry_path))
				{
					unlink ($entry_path);
				}
			}
			$dicDir->close();
			rmdir($lang_dir);
		}
	}

	//Handle dictionary indexing
	if (isset($_POST['index_dic']))
	{
		$lang_dir = $_SERVER['DOCUMENT_ROOT'].$dicsRelPath.'/'.$_POST['index_dic'];
		if (file_exists($lang_dir) && is_dir($lang_dir))
		{
			$dicsDir = dir($lang_dir.'/dics');
			while (false !== ($dic = $dicsDir->read()))
			{
				$dic_path = $dicsDir->path.'/'.$dic;
				if (is_file($dic_path))
					unlink ($dic_path);
			}
			$dicsDir->close();

			require($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/fileman/admin/spell_createDictionary.php');

			$CD = new createDictionary();
			$lang = $_POST['index_dic'];
			$CD->init($lang,$lang_dir);
			if ($CD->create())
				COption::SetOptionString($module_id, $dic_lang."_dic_indexed", "Y");
		}
	}
	// ******** Spell END ********


	//Entities
	COption::SetOptionString($module_id, "ar_entities", count($ar_entities) <= 0 ? 'none' : implode(',',$ar_entities));

	COption::SetOptionString($module_id, "editor_body_id", htmlspecialcharsbx($editor_body_id));
	COption::SetOptionString($module_id, "editor_body_class", htmlspecialcharsbx($editor_body_class));


	/* **********  Medialib ************/
	$cur_ml_width = COption::GetOptionInt($module_id, "ml_thumb_width", 140);
	$cur_ml_height = COption::GetOptionInt($module_id, "ml_thumb_height", 105);

	$ml_width = intval($medialib_thumb_width, 10);
	if ($ml_width <=0)
		$ml_width = 140;

	$ml_height = intval($medialib_thumb_height, 10);
	if ($ml_height <=0)
		$ml_height = 105;

	if (abs($cur_ml_width - $ml_width) > 10 || abs($cur_ml_height - $ml_height) > 10)
		CMedialib::DeleteThumbnails();

	COption::SetOptionInt($module_id, "ml_thumb_width", $ml_width);
	COption::SetOptionInt($module_id, "ml_thumb_height", $ml_height);

	$arMLExt = explode(',', $medialib_ext);
	$arMLExt_ = array();
	for ($i = 0, $l = count($arMLExt); $i < $l; $i++)
	{
		$ext = strtolower(trim($arMLExt[$i], ' .'));
		if (strlen($ext) > 0)
			$arMLExt_[] = $ext;
	}
	$medialib_ext = implode(',', $arMLExt_);
	COption::SetOptionString($module_id, "ml_media_extentions", $medialib_ext);

	/* Max size*/
	$ml_max_width = intval($medialib_max_width);
	if ($ml_max_width <=0)
		$ml_max_width = 1024;

	$ml_max_height = intval($medialib_max_height);
	if ($ml_max_height <=0)
		$ml_max_height = 1024;

	COption::SetOptionInt($module_id, "ml_max_width", $ml_max_width);
	COption::SetOptionInt($module_id, "ml_max_height", $ml_max_height);

	/* MEDIALIB TYPES*/
	$arMLTypes = array();
	$arMLDelTypes = array();
	$strAvExt = $medialib_ext;

	foreach ($_POST['ML_TYPE'] as $key => $type)
	{
		if ($type["DEL"] == "Y")
		{
			$arMLDelTypes[] = $key;
		}
		//elseif(trim($type["EXT"]) != "" && trim($type["NAME"]) != "" && trim($type["CODE"]) != "")
		elseif(trim($type["EXT"]) != "" && trim($type["CODE"]) != "")
		{
			if ($type["SYS"] == "Y")
			{
				$arMLTypes[] = array(
					'EXT' => $type["EXT"],
					'CODE' => $type["CODE"],
					'ID' => $key
				);
			}
			else
			{
				$arMLTypes[] = array(
					'NEW' => $type["NEW"] == "Y",
					'ID' => $key,
					'NAME' => $type["NAME"],
					'CODE' => $type["CODE"],
					'EXT' => $type["EXT"],
					'DESCRIPTION' => $type["DESC"],
					'SYSTEM' => $type["SYS"] == "Y" ? "Y" : "N"
				);
			}

			$strAvExt .= ','.$type["EXT"];
		}
	}

	CMedialib::DelTypes($arMLDelTypes);
	CMedialib::SetTypes($arMLTypes);

	$arExt_ = explode(',', $strAvExt);
	$arAvExt = array();
	for ($i = 0, $l = count($arExt_); $i < $l; $i++)
	{
		$ext = strtolower(trim($arExt_[$i], ' .'));
		if (strlen($ext) > 0 && !in_array($ext, $arAvExt))
			$arAvExt[] = $ext;
	}
	$strAvExt = implode(',', $arAvExt);
	COption::SetOptionString($module_id, "ml_media_available_ext", $strAvExt);

	/* MEDIALIB END*/

	// Using medialib (or file dialog) by default in HTML-editor and other...
	COption::SetOptionString($module_id, "ml_use_default", $medialib_use_default == 'Y');

	//File extensions
	if ($USER->CanDoOperation('edit_php'))
	{
		COption::SetOptionString($module_id, "~script_files", $script_files);
		COption::SetOptionString($module_id, "~allowed_components", $allowed_components);
	}
	$addError = false;

	$siteList_ID = unserialize($mSiteList);

	if(isset($dif_settings))
	{
		COption::SetOptionString($module_id, "different_set", "Y");

		$j = 0;
		while($j < count($siteList_ID))
		{
			COption::SetOptionInt($module_id, "num_menu_param", ${"num_".$siteList_ID[$j]["ID"]."_menu_param"}, false, $siteList_ID[$j]["ID"]);

			$menutypes = "";
			$armt = Array();
			for($i=0; $i<${"menutypes_".$siteList_ID[$j]["ID"]."_count"}; $i++)
			{
				if(strlen(${"menutypes_".$siteList_ID[$j]["ID"]."_".$i."_type"})>0)
					$armt[${"menutypes_".$siteList_ID[$j]["ID"]."_".$i."_type"}] = ${"menutypes_".$siteList_ID[$j]["ID"]."_".$i."_name"};
			}

			if(strlen(${"menutypes_".$siteList_ID[$j]["ID"]."_new_type"})>0 && $USER->CanDoOperation('fileman_edit_menu_types'))
				$armt[${"menutypes_".$siteList_ID[$j]["ID"]."_new_type"}] = ${"menutypes_".$siteList_ID[$j]["ID"]."_new_name"};

			if (strlen(addslashes(serialize($armt))) <= 2000)
				SetMenuTypes($armt, $siteList_ID[$j]["ID"]);
			else
				$addError = GetMessage("FILEMAN_OPTION_ADD_ERROR_MENU").'<br />';

			$arPT = Array();
			for($i=0; $i<${"propstypes_".$siteList_ID[$j]["ID"]."_count"}; $i++)
			{
				if(strlen(${"propstypes_".$siteList_ID[$j]["ID"]."_".$i."_type"})>0)
					$arPT[${"propstypes_".$siteList_ID[$j]["ID"]."_".$i."_type"}] = ${"propstypes_".$siteList_ID[$j]["ID"]."_".$i."_name"};
			}
			if(strlen(${"propstypes_".$siteList_ID[$j]["ID"]."_new_type"})>0)
				$arPT[${"propstypes_".$siteList_ID[$j]["ID"]."_new_type"}] = ${"propstypes_".$siteList_ID[$j]["ID"]."_new_name"};

			if(!CFileMan::SetPropstypes($arPT, false, $siteList_ID[$j]["ID"]))
				$addError .= GetMessage("FILEMAN_OPTION_ADD_ERROR_PROPS");
			$j++;
		}
	}
	else
	{
		COption::SetOptionString($module_id, "different_set", "N");
		COption::SetOptionInt($module_id, "num_menu_param", $num_menu_param);

		$armt = Array();
		$menutypes = "";
		for($i=0; $i<$menutypes_count; $i++)
		{
			if(strlen(${"menutypes_".$i."_type"})>0)
				$armt[${"menutypes_".$i."_type"}] = ${"menutypes_".$i."_name"};
		}
		if(strlen($menutypes_new_type)>0 && $USER->CanDoOperation('fileman_edit_menu_types'))
			$armt[$menutypes_new_type] = $menutypes_new_name;

		if (strlen(addslashes(serialize($armt))) <= 2000)
			SetMenuTypes($armt, $siteList_ID[$j]["ID"]);
		else
			$addError = GetMessage("FILEMAN_OPTION_ADD_ERROR_MENU").'<br />';

		$propstypes = "";
		$arPT = Array();
		for($i=0; $i<$propstypes_count; $i++)
		{
			if(strlen(${"propstypes_".$i."_type"})>0)
				$arPT[${"propstypes_".$i."_type"}] = ${"propstypes_".$i."_name"};
		}
		if(strlen($propstypes_new_type)>0)
			$arPT[$propstypes_new_type] = $propstypes_new_name;

		if(!CFileMan::SetPropstypes($arPT))
			$addError .= GetMessage("FILEMAN_OPTION_ADD_ERROR_PROPS");

		$j = 0;
		while($j < count($siteList_ID))
		{
			COption::RemoveOption($module_id, "menutypes", $siteList_ID[$j]["ID"]);
			COption::RemoveOption($module_id, "propstypes", $siteList_ID[$j]["ID"]);
			COption::RemoveOption($module_id, "num_menu_param", $siteList_ID[$j]["ID"]);
			$j++;
		}

	}

	// Search
	$search_max_open_file_size = intVal($_POST['search_max_open_file_size']);
	if ($search_max_open_file_size <= 0)
		$search_max_open_file_size = 1024;
	COption::SetOptionString($module_id, "search_max_open_file_size", $search_max_open_file_size);

	$search_max_res_count = intVal($_POST['search_max_res_count']);
	if ($search_max_res_count <= 0)
		$search_max_res_count = '';
	COption::SetOptionString($module_id, "search_max_res_count", $search_max_res_count);

	$search_time_step = intVal($_POST['search_time_step']);
	if ($search_time_step <= 0)
		$search_time_step = 5;
	COption::SetOptionString($module_id, "search_time_step", $search_time_step);

	$search_mask = $_POST['search_mask'];
	if ($search_mask == "")
		$search_mask = "*.php";
	COption::SetOptionString($module_id, "search_mask", $search_mask);

	COption::SetOptionString($module_id, "show_inc_icons", (isset($_POST['show_inc_icons']) ? 'Y' : 'N'));


	COption::SetOptionString($module_id, "hide_physical_struc", (isset($_POST['hide_physical_struc'])));
	COption::SetOptionString($module_id, "use_translit", (isset($_POST['use_translit'])));
	COption::SetOptionString($module_id, "use_translit_google", (isset($_POST['use_translit_google'])));
	COption::SetOptionString($module_id, "log_menu", (isset($_POST['log_menu']) ? 'Y' : 'N'));
	COption::SetOptionString($module_id, "log_page", (isset($_POST['log_page']) ? 'Y' : 'N'));
	COption::SetOptionString($module_id, "use_code_editor", (isset($_POST['use_code_editor']) ? 'Y' : 'N'));



	//default groups
	$sGroups = '';
	if(is_array($_POST["DEFAULT_EDIT_GROUPS"]))
		foreach($_POST["DEFAULT_EDIT_GROUPS"] as $gr)
			$sGroups .= ($sGroups <> ''? ',':'').intval($gr);
	COption::SetOptionString('fileman', 'default_edit_groups', $sGroups);

	$archive_step_time = intVal($_POST['archive_step_time']);
	if ($archive_step_time <= 0)
		$archive_step_time = 30;
	COption::SetOptionString($module_id, "archive_step_time", $archive_step_time);
}


if ($REQUEST_METHOD=="GET" && isset($_GET['load_dic']) &&
	$USER->CanDoOperation('fileman_edit_all_settings') &&
	COption::GetOptionString($module_id, "use_editor_3", "N") == "Y")
{
	if (isValidLang($_GET['load_dic']))
	{
		$l_id = $_GET['load_dic'];
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/tar_gz.php");
		$indexedDicPath = $_SERVER['DOCUMENT_ROOT'].$gzDicsRelPath.'/'.$l_id.'.tar.gz';
		$oArchiver = new CArchiver($indexedDicPath);

		if ($oArchiver->extractFiles($_SERVER['DOCUMENT_ROOT'].$dicsRelPath.'/'.$l_id))
			COption::SetOptionString($module_id, $l_id."_dic_indexed", "Y");
	}
}

$aTabs = array(
	array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_SET"), "ICON" => "fileman_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_SET")),
	array("DIV" => "edit2", "TAB" => GetMessage("MAIN_TAB_VISUAL_EDITOR"), "ICON" => "fileman_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_VIS_ED_SET")),
	array("DIV" => "edit5", "TAB" => GetMessage("MAIN_TAB_MEDIALIB"), "ICON" => "fileman_settings", "TITLE" => GetMessage("FILEMAN_SEC_MEDIALIB")),
	);

if($USER->isAdmin())
{
	$rightsTab = array("DIV" => "edit3", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "fileman_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS"));
	$aTabs[] = $rightsTab;
}
	$siteList = array();
	$rsSites = CSite::GetList($by="sort", $order="asc", Array());
	$i = 0;
	while($arRes = $rsSites->Fetch())
	{
		$siteList[$i]["ID"] = $arRes["ID"];
		$siteList[$i]["NAME"] = $arRes["NAME"];
		$i++;
	}
	$siteCount = $i;
	$useEditor3 = COption::GetOptionString($module_id, "use_editor_3", "Y") == "Y";
	unset($rsSites);
	unset($arRes);

	if ($addError)
	{
		CAdminMessage::ShowMessage(array(
			"DETAILS" => $addError,
			"TYPE" => "ERROR",
		));
	}

	$tabControl = new CAdmintabControl("tabControl", $aTabs);
	$tabControl->Begin();

?>


<script>
	function SelectSite(id)
	{
		<?for($i = 0; $i < $siteCount; $i++):?>
		BX('<?= htmlspecialcharsbx($siteList[$i]["ID"]);?>_Propery').style.display='none';
		<?endfor;?>
		BX(id+'_Propery').style.display='';
	}

	function hideSite()
	{
		<?for($i = 0; $i < $siteCount; $i++):?>
		BX('<?= htmlspecialcharsbx($siteList[$i]["ID"]);?>_Propery').style.display='none';
		<?endfor;?>
	}

	function showCustomSpellSettings(id)
	{
		var checker = BX(id);
		var customSpellSettings = BX('customSpellSettings');
		if (checker.checked)
		{
			customSpellSettings.style.display = "block";
		}
		else
		{
			customSpellSettings.style.display = "none";
		}
	}
</script>

<form method="POST" enctype="multipart/form-data" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
<?=bitrix_sessid_post()?>
<?$tabControl->BeginNextTab();?>
<tr>
	<td valign="top" width="40%"><?= GetMessage('FILEMAN_OPTION_DEF_EDITOR')?></td>
	<td valign="top" width="60%">

	<select name="default_edit">
		<option value="text"><?= GetMessage('FILEMAN_OPTION_EDITOR_TEXT')?></option>
		<option value="php"<?if(COption::GetOptionString($module_id, "default_edit", "text")=="php")echo " selected"?>><?= GetMessage('FILEMAN_OPTION_EDITOR_PHP')?></option>
		<option value="html"<?if(COption::GetOptionString($module_id, "default_edit", "text")=="html")echo " selected"?>><?= GetMessage('FILEMAN_OPTION_EDITOR_HTML')?></option>
	</select>
	</td>
</tr>
	<? if (COption::GetOptionString('main', 'distributive6', 'N') != 'Y'):?>
	<tr>
		<td valign="top"><label for="show_inc_icons"><?= GetMessage('FILEMAN_OPTION_MENU_SHOW_INC')?></label></td>
		<td><input type="checkbox" name="show_inc_icons" id="show_inc_icons" size="5" value="Y" <?if(COption::GetOptionString($module_id, "show_inc_icons", "Y")=="Y")echo " checked"?>></td>
	</tr>
	<?endif;?>
	<? if ($USER->CanDoOperation('edit_php')):?>
	<tr>
		<td>
			<?= GetMessage('FILEMAN_OPTION_SCRIPT_FILES')?>:
		</td>
		<td>
			<input type="text" name="script_files" id="script_files" size="40" value="<?echo COption::GetOptionString($module_id, "~script_files", $script_files_default);?>">
		</td>
	</tr>
	<tr>
		<td valign="top">
			<?= GetMessage('FILEMAN_OPTION_ALLOWED_COMPONENTS')?>:
		</td>
		<td>
			<textarea cols="30" rows="4" name="allowed_components"><?echo COption::GetOptionString($module_id, "~allowed_components", '');?></textarea>
		</td>
	</tr>
	<?endif;?>
	<tr>
	<td valign="top" width="40%"><label for="hide_physical_struc"><?= GetMessage('FILEMAN_HIDE_PHYSICAL_STRUC')?>:</label></td>
	<td valign="top" width="60%">
	<input type="checkbox" name="hide_physical_struc" id="hide_physical_struc" <? if(COption::GetOptionString($module_id, "hide_physical_struc", false) == true) echo " checked";?>>
	</td>
	</tr>
		<tr>
		<td valign="top" width="40%"><label for="use_translit"><?= GetMessage('FILEMAN_USE_TRANSLITE')?>:</label></td>
		<td valign="top" width="60%">
		<input type="checkbox" name="use_translit" id="use_translit" <? if(COption::GetOptionString($module_id, "use_translit", true) == true) echo " checked";?>>
		</td>
	</tr>
	</tr>
		<tr>
		<td valign="top" width="40%"><label for="use_translit_google"><?= GetMessage('FILEMAN_USE_TRANSLITE_EXTERNAL')?>:</label></td>
		<td valign="top" width="60%">
		<input type="checkbox" name="use_translit_google" id="use_translit_google" <? if(COption::GetOptionString($module_id, "use_translit_google", true) == true) echo " checked";?>>
		</td>
	</tr>
	<tr>
		<td class="adm-detail-valign-top"><label for="LOGS"><?=GetMessage("FILEMAN_EVENT_LOG")?>:</label></td>
		<td>
			<?$val = COption::GetOptionString("forum", "LOGS", "Q");?>
			<div class="adm-list">
				<div class="adm-list-item">
					<div class="adm-list-control"><input type="checkbox" name="log_menu" id="log_menu" value="Y" <?if(COption::GetOptionString($module_id, "log_menu", "Y")=="Y")echo " checked"?>></div>
					<div class="adm-list-label"><label for="log_menu"><?=GetMessage("FILEMAN_EVENT_LOG_MENU")?></label></div>
				</div>
				<div class="adm-list-item">
					<div class="adm-list-control"><input type="checkbox" name="log_page" ID="log_page" value="Y" <?if(COption::GetOptionString($module_id, "log_page", "Y")=="Y")echo " checked"?>></div>
					<div class="adm-list-label"><label for="log_page"><?=GetMessage("FILEMAN_EVENT_LOG_PAGE")?></label></div>
				</div>
			</div>
		</td>
	</tr>

	</tr>
		<tr>
		<td valign="top" width="40%"><label for="use_code_editor"><?= GetMessage('FILEMAN_OPTION_USE_CODE_EDITOR')?>:</label></td>
		<td valign="top" width="60%">
		<input type="checkbox" name="use_code_editor" id="use_code_editor" <? if(COption::GetOptionString($module_id, "use_code_editor", "Y") == "Y") echo " checked";?>>
		</td>
	</tr>

	<tr>
		<td colspan=2>&nbsp;</td>
	</tr>
	<tr class="heading">
		<td colspan=2><?= GetMessage('FILEMAN_OPTION_SPECIAL_SETTINGS')?></td>
	</tr>
	<tr>
		<td><?= GetMessage('FILEMAN_OPTION_DIFFERENT_SET')?></td>
		<td><input type="checkbox" name="dif_settings" id="dif_settings_id" onClick="if(this.checked) {BX('comPropery').style.display='none'; BX('site_select_id').disabled=false; SelectSite(BX('site_select_id').value);} else { BX('site_select_id').disabled=true; BX('comPropery').style.display=''; hideSite();}" <? if(COption::GetOptionString($module_id, "different_set", "N") == "Y") echo " checked";?>></td>
	</tr>
	<tr>
		<td><?= GetMessage('FILEMAN_OPTION_FOR_SYTE')?></td>
		<td>
			<select name="site_select" id="site_select_id" onChange="SelectSite(this.value)" <? if(COption::GetOptionString($module_id, "different_set", "N") != "Y") echo " disabled"; ?>>
			<?
				for($i = 0; $i < $siteCount; $i++)
					echo "<option value=\"".htmlspecialcharsbx($siteList[$i]["ID"])."\">".htmlspecialcharsbx($siteList[$i]["NAME"])."</option>";
			?>
			</select>
		</td>
	</tr>
	<tr id="comPropery" <? if(COption::GetOptionString($module_id, "different_set", "N") == "Y") echo " style=\"display: none;\""; ?>>
		<td colspan="2">
		<table cellspacing="4"  cellpadding="0" width="100%">
		<tr>
			<td valign="top" width="40%" class="adm-detail-content-cell-l" style="padding: 4px;"><?= GetMessage('FILEMAN_OPTION_MENU_TYPES')?></td>
			<td valign="top" width="60%" class="adm-detail-content-cell-r">
			<table cellPadding="2" cellSpacing="2" border="0" width="100%">
			<tr class="heading">
				<td align="center" width="40%"><b><?= GetMessage("FILEMAN_OPTION_MENU_TYPE")?></b></td>
				<td align="center" width="60%"><b><?= GetMessage("FILEMAN_OPTION_MENU_NAME")?></b></td>
			</tr>
			<?
			$armt = GetMenuTypes('', "left=".GetMessage("FILEMAN_OPTION_LEFT_MENU_NAME").",top=".GetMessage("FILEMAN_OPTION_TOP_MENU_NAME"));


			$i = 0;
			foreach($armt as $key => $title):
				if ($USER->CanDoOperation('fileman_edit_menu_types')):
				?>
					<tr>
						<td style="padding: 2px;"><input type="text" name="menutypes_<?echo $i?>_type" value="<?= htmlspecialcharsbx($key)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
						<td style="padding: 2px;"><input type="text" name="menutypes_<?echo $i?>_name" value="<?= htmlspecialcharsbx($title)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
					</tr>
				<?else:?>
				<tr>
					<td style="padding-left: 5px">
					<?= htmlspecialcharsbx($key)?>
					<input type="hidden" name="menutypes_<?echo $i?>_type" value="<?echo htmlspecialcharsbx($key)?>">
					</td>
					<td  style="padding-left: 5px">
					<?= htmlspecialcharsbx($title)?>
					<input type="hidden" name="menutypes_<?echo $i?>_name" value="<?echo htmlspecialcharsbx($title)?>">
					</td>
				</tr>
				<?
				endif;
				$i++;
			endforeach;
			?>
			<input type="hidden" name="menutypes_count" value="<?echo $i?>">
			<?if ($USER->CanDoOperation('fileman_edit_menu_types')):?>
			<tr>
				<td><input type="text" name="menutypes_new_type" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="menutypes_new_name" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
			<?endif;?>
			</table>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?= GetMessage('FILEMAN_OPTION_MENU_PARAMS')?></td>
			<td class="adm-detail-content-cell-r"><input type="text" name="num_menu_param" size="5" value="<?echo COption::GetOptionInt($module_id, "num_menu_param", 1, "")?>"></td>
		</tr>
		<tr>
			<td valign="top" class="adm-detail-content-cell-l"><?= GetMessage('FILEMAN_OPTION_PROPS_TYPES')?></td>
			<td valign="top" class="adm-detail-content-cell-r">
			<table cellpadding="2" cellspacing="2" border="0" width="100%">
			<tr class="heading">
				<td align="center" width="40%"><b><?= GetMessage('FILEMAN_OPTION_PROPS_TYPE')?></b></td>
				<td align="center" width="60%"><b><?= GetMessage('FILEMAN_OPTION_PROPS_NAME')?></b></td>
			</tr>
			<?

			$i = 0;
			foreach (CFileMan::GetPropstypes('') as $key => $val)
			{
				?>
			<tr>
				<td><input type="text" name="propstypes_<?= $i?>_type" value="<?= htmlspecialcharsbx($key)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="propstypes_<?= $i?>_name" value="<?= htmlspecialcharsbx($val)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
			<?
				$i++;
			}
			?>
			<input type="hidden" name="propstypes_count" value="<?echo $i+1;?>">
			<tr>
				<td><input type="text" name="propstypes_new_type" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="propstypes_new_name" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
			</table>
			</td>
			</tr>
			</td>
		</tr>
		</table>
		</td>
	</tr>
	<input type="hidden" name="mSiteList" value="<?=htmlspecialcharsbx(serialize($siteList))?>">
	<?
	for($j = 0; $j < $siteCount; $j++)
	{
	?>
	<tr id="<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_Propery" style="<? if(((COption::GetOptionString($module_id, "different_set", "N") == "Y") && ($j != 0)) || (COption::GetOptionString($module_id, "different_set", "N") == "N")) echo "display: none;"?>">
		<td colSpan="2">
		<table cellspacing="4" cellpadding="0" width="100%">
		<tr>
			<td valign="top" width="40%" class="adm-detail-content-cell-l"><?= GetMessage('FILEMAN_OPTION_MENU_TYPES')?></td>
			<td valign="top" width="60%" class="adm-detail-content-cell-r">
			<table cellPadding="2" cellSpacing="2" border="0" width="100%">
			<tr class="heading">
				<td align="center" width="40%"><b><?= GetMessage('FILEMAN_OPTION_MENU_TYPE')?></b></td>
				<td align="center" width="60%"><b><?= GetMessage('FILEMAN_OPTION_MENU_NAME')?></b></td>
			</tr>
			<?
			$armt = GetMenuTypes($siteList[$j]["ID"], "left=".GetMessage("FILEMAN_OPTION_LEFT_MENU_NAME").",top=".GetMessage("FILEMAN_OPTION_TOP_MENU_NAME"));

			$i = 0;
			foreach($armt as $key => $title):
				if ($USER->CanDoOperation('fileman_edit_menu_types')):
				?>
			<tr>
				<td><input type="text" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_<?= $i?>_type" value="<?= htmlspecialcharsbx($key)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_<?= $i?>_name" value="<?= htmlspecialcharsbx($title)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
				<?else:?>
			<tr>
				<td>
				<?echo htmlspecialcharsbx($key)?>
				<input type="hidden" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_<?= $i?>_type" value="<?= htmlspecialcharsbx($key)?>">
				</td>
				<td>
				<?echo htmlspecialcharsbx($title)?>
				<input type="hidden" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_<?= $i?>_name" value="<?= htmlspecialcharsbx($title)?>">
				</td>
			</tr>
				<?
				endif;
				$i++;
			endforeach;
			?>
			<input type="hidden" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_count" value="<?= $i?>">
			<?if($USER->CanDoOperation('fileman_edit_menu_types')):?>
			<tr>
				<td><input type="text" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_new_type" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="menutypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_new_name" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
			<?endif;?>
			</table>
			</td>
		</tr>
		<tr>
			<td class="adm-detail-content-cell-l"><?= GetMessage('FILEMAN_OPTION_MENU_PARAMS')?></td>
			<td class="adm-detail-content-cell-r"><input type="text" name="num_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_menu_param" size="5" value="<?= COption::GetOptionInt($module_id, "num_menu_param", 1, $siteList[$j]["ID"])?>"></td>
		</tr>
		<tr>
			<td valign="top" class="adm-detail-content-cell-l"><?= GetMessage('FILEMAN_OPTION_PROPS_TYPES')?></td>
			<td class="adm-detail-content-cell-r">
			<table cellPadding="2" cellSpacing="2" border="0" width="100%">
			<tr class="heading">
				<td align="center" width="40%"><b><?= GetMessage('FILEMAN_OPTION_PROPS_TYPE')?></b></td>
				<td align="center" width="60%"><b><?= GetMessage('FILEMAN_OPTION_PROPS_NAME')?></b></td>
			</tr>
			<?
			$i = 0;
			foreach (CFileMan::GetPropstypes($siteList[$j]["ID"]) as $key => $val)
			{?>
			<tr>
				<td><input type="text" name="propstypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_<?echo $i?>_type" value="<?= htmlspecialcharsbx($key)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="propstypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_<?echo $i?>_name" value="<?= htmlspecialcharsbx($val)?>" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
			<?
				$i++;
			}
			?>
			<input type="hidden" name="propstypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_count" value="<?echo $i+1?>">
			<tr>
				<td><input type="text" name="propstypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_new_type" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
				<td><input type="text" name="propstypes_<?= htmlspecialcharsbx($siteList[$j]["ID"])?>_new_name" value="" style="width:100%; -moz-box-sizing: border-box; box-sizing: border-box;"></td>
			</tr>
			</table>
			</td>
		</tr>
		</table>
	</td>
	</tr>

	<? } ?>
	<tr class="heading">
		<td colspan="2"><?= GetMessage("FILEMAN_SEARCH_TITLE")?></td>
	</tr>
	<tr>
		<td><label for="search_max_open_file_size"><?= GetMessage("FILEMAN_SEARCH_MAX_FILE_SIZE")?> (<?= GetMessage("FILEMAN_SEARCH_KB")?>):</label></td>
		<td><input type="text" name="search_max_open_file_size" id="search_max_open_file_size" value="<?= COption::GetOptionString($module_id, "search_max_open_file_size", "1024")?>"></td>
	</tr>
	<tr>
		<td><label for="search_max_res_count"><?= GetMessage("FILEMAN_SEARCH_MAX_RES_CNT")?>:</label></td>
		<?
		$val = COption::GetOptionString($module_id, "search_max_res_count", "");
		$def_val = ' - '.GetMessage('FILEMAN_SEARCH_NO_LIMITS').' -';
		?>
		<td><input type="text" name="search_max_res_count" id="search_max_res_count" value="<? echo $val != "" ? $val : $def_val; ?>" <? if ($val == ""): ?>class="def-val"<?endif;?> onfocus="if (this.value == '<?= $def_val?>' || this.value == ''){this.value = ''; BX.removeClass(this, 'def-val');}" onblur="if (this.value == ''){this.value = '<?= $def_val?>'; BX.addClass(this, 'def-val');}" />
		</td>
	</tr>
	<tr>
		<td><label for="search_time_step"><?= GetMessage("FILEMAN_SEARCH_TIME_STEP")?>:</label></td>
		<td><input type="text" name="search_time_step" id="search_time_step" value="<?= COption::GetOptionString($module_id, "search_time_step", "5"); ?>"></td>
	</tr>
	<tr>
		<td><label for="search_mask"><?= GetMessage("FILEMAN_SEARCH_MASK_DEF")?>:</label></td>
		<td><input type="text" name="search_mask" id="search_mask" value="<?= COption::GetOptionString($module_id, "search_mask", "*.php"); ?>"></td>
	</tr>
	<tr class="heading">
		<td colspan="2"><?= GetMessage("FILEMAN_ARCHIVE_TITLE")?></td>
	</tr>
	<tr>
		<td><label for="archive_step_time"><?= GetMessage("FILEMAN_ARCHIVE_STEP_TIME")?>:</label></td>
		<td><input type="text" name="archive_step_time" id="archive_step_time" value="<?= COption::GetOptionString($module_id, "archive_step_time", "30")?>"></td>
	</tr>
	</tr>
	<!--end of archive-->
<?$tabControl->BeginNextTab();?>
<tr>
	<td valign="top"><label for="use_editor_3"><?= GetMessage('FILEMAN_OPTION_USE_EDITOR_3')?></label></td>
	<td><input type="checkbox" name="use_editor_3" id="use_editor_3" value="Y" <?if($useEditor3) echo " checked"?>></td>
</tr>

<? if (!$useEditor3):?>
<tr>
	<td valign="top"><label for="show_untitled_styles"><?= GetMessage('FILEMAN_OPTION_USE_ONLY_DEFINED_STYLES')?></label></td>
	<td><input type="checkbox" name="show_untitled_styles" id="show_untitled_styles" value="Y" <?if(COption::GetOptionString($module_id, "show_untitled_styles", "N")=="Y")echo " checked"?>></td>
</tr>
<tr>
	<td valign="top"><label for="render_styles_in_classlist"><?= GetMessage('FILEMAN_OPTION_RENDER_CLASSLIST_STYLE')?>:</label></td>
	<td><input type="checkbox" name="render_styles_in_classlist" id="render_styles_in_classlist" value="Y" <?if(COption::GetOptionString($module_id, "render_styles_in_classlist", "N") == "Y") echo " checked"?>></td>
</tr>
<tr>
	<td valign="top"><label for="htmleditor_fullscreen"><?= GetMessage('FILEMAN_OPT_FULLSCREEN')?></label></td>
	<td><input type="checkbox" name="htmleditor_fullscreen" id="htmleditor_fullscreen" value="Y" <?if(COption::GetOptionString($module_id, "htmleditor_fullscreen", "N")=="Y")echo " checked"?>></td>
</tr>
<tr>
	<td valign="top"><label for="allow_render_components"><?= GetMessage('FILEMAN_OPT_ALLOW_RENDER_COMPONENTS')?>:</label></td>
	<td><input type="checkbox" name="allow_render_components" id="allow_render_components" value="Y" <?if(COption::GetOptionString($module_id, "allow_render_components", "N") == "Y") echo " checked"?>></td>
</tr>
<? endif; // $useEditor3?>
	<tr>
		<td valign="top"><label for="editor_body_id"><?= GetMessage('FILEMAN_OPTION_EDITOR_BODY_ID')?>:</label></td>
		<td><input type="text" id="editor_body_id" name="editor_body_id" value="<?= COption::GetOptionString($module_id, "editor_body_id", "")?>" /></td>
	</tr>
	<tr>
		<td valign="top"><label for="editor_body_class"><?= GetMessage('FILEMAN_OPTION_EDITOR_BODY_CLASS')?>:</label></td>
		<td><input type="text" id="editor_body_class" name="editor_body_class" value="<?= COption::GetOptionString($module_id, "editor_body_class", "")?>" /></td>
	</tr>

<? if (!$useEditor3):?>
	<tr class="heading">
		<td colspan="2"><?= GetMessage("FILEMAN_EDITOR_TOOLBAR_SETTINGS");?></td>
	</tr>
<?
$arEdTypes = array(
	array(
		"code" => "filesrc_pub",
		"editors"=> array("filesrc_pub"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_PUBLIC"),
		"public" => true
	),
	array(
		"code" => "filesrc",
		"editors"=> array("filesrc"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_ADMIN"),
		"public" => false),
	array(
		"code" => "template_edit",
		"editors"=> array("CONTENT"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_TEMPLATE"),
		"public" => false
	)
);

if (IsModuleInstalled("iblock"))
{
	$arEdTypes[] = array(
		"code" => "iblock_public",
		"editors"=> array("PREVIEW_TEXT", "DETAIL_TEXT"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_IBLOCK_PUBLIC"),
		"public" => true
	);
	$arEdTypes[] = array(
		"code" => "iblock_admin",
		"editors"=> array("PREVIEW_TEXT", "DETAIL_TEXT"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_IBLOCK_ADMIN"),
		"public" => false
	);
}

if (IsModuleInstalled("subscribe"))
{
	$arEdTypes[] = array(
		"code" => "subscribe",
		"editors"=> array(),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_SUBSCRIBE"),
		"public" => false
	);
}

if (IsModuleInstalled("learning"))
{
	$arEdTypes[] = array(
		"code" => "learning_public",
		"editors"=> array(),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_LEARNING_PUBLIC"),
		"public" => true
	);
	$arEdTypes[] = array(
		"code" => "learning_admin",
		"editors"=> array(),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_LEARNING_ADMIN"),
		"public" => false
	);
}

if (IsModuleInstalled("form"))
{
	$arEdTypes[] = array(
		"code" => "form_edit",
		"editors"=> array("FORM_TEMPLATE"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_FORM"),
		"public" => false
	);
	$arEdTypes[] = array(
		"code" => "form_edit_public",
		"editors"=> array("FORM_TEMPLATE"),
		"title" => GetMessage("FILEMAN_EDITOR_TYPE_FORM_PUBLIC"),
		"public" => false
	);
}

$arCurConfig = array();
for ($i = 0, $l = count($arEdTypes); $i < $l; $i++)
{
	$arCurConfig[$arEdTypes[$i]['code']] = CFileMan::GetEditorToolbarConfig($arEdTypes[$i]['code']);
	// for ($j = 0, $n = count($arEdTypes[$i]['editors']); $j < $n; $j++)
	// {
		// $arJS = Array();
		// $arCSS = Array();

		// $events = GetModuleEvents("fileman", "OnBeforeHTMLEditorScriptsGet");
		// while($arEvent = $events->Fetch())
		// {
			// $tmp = ExecuteModuleEventEx($arEvent, array($arEdTypes[$i]['editors'][$j], array()));
			// if (!is_array($tmp))
				// continue;

			// if (is_array($tmp['JS']))
				// $arJS = array_merge($arJS, $tmp['JS']);
			// if (is_array($tmp['CSS']))
				// $arCSS = array_merge($arCSS, $tmp['CSS']);
		// }
	// }
}
?>

	<tr>
		<td width="50%"><label for='ed_toolbar_type'><?= GetMessage("FILEMAN_EDITOR_TYPE");?>:</td>
		<td  valign="top">
			<select name="ed_toolbar_type" id="ed_toolbar_type">
				<?for ($i = 0, $l = count($arEdTypes); $i < $l; $i++):?>
				<option value="<?= $arEdTypes[$i]["code"]?>"><?= $arEdTypes[$i]["title"]?></option>
				<? endfor;?>
			</select>
		</td>
	</tr>
	<tr>
		<td></td>
		<td  valign="top">

<style>
div.bxopt-cont{width: 350px; height: 300px; border: 1px solid #E0E4F1;overflow: auto;}
table.bxopt-tbl{border-collapse: collapse;width: 100%;}
table.bxopt-tbl td{padding: 0!important;}
table.bxopt-tbl td.bxopt-toolbar-title{background-color: #E0E8EA; font-weight: bold; padding: 4px 0!important;}
table.bxopt-tbl td.bxopt-toolbar-sep{height: 10px;}
table.bxopt-tbl td.bxopt-toolbar-title label{margin-left:31px; cursor: default;}
table.bxopt-tbl td.bxopt-check-sell{width: 20px; padding-left: 5px!important;}
table.bxopt-tbl td.bxopt-separator{color: #808080;font-style:italic;font-size: 11px;text-align: center;}
table.bxopt-tbl td.bxopt-but-icon{width: 25px; padding: 2px 0 0 5px !important;}
table.bxopt-tbl td.bxopt-but-icon .bxed-button{width: 20px; height: 20px; background-image: url(/bitrix/images/fileman/htmledit2/_global_iconkit.gif); background-repeat: norepeat;}
table.bxopt-list{border-collapse: collapse; border:1px solid #A5ACB2 !important; margin-left: 3px;}
table.bxopt-list td{background: #FFFFFF !important;}
table.bxopt-list .bxopt-listtitle{background:#FFFFFF!important; font-family:Tahoma,Courier New !important; font-size:11px !important; margin:2px 4px 0!important; overflow:hidden; white-space:nowrap;}
table.bxopt-list td.bx-listbutton{background:url("/bitrix/images/fileman/htmledit2/_global_iconkit.gif") no-repeat scroll -63px -59px #E0DEDA !important; border-left:1px solid #A5ACB2 !important; font-size:1pt !important; height:18px !important; width:14px !important;}
table.bxopt-tbl td.bxopt-but-title{}
table.bxopt-tbl td.bxopt-but-title label{margin: 3px 2px; cursor: default;}
</style>
			<div id="bxopt_but_title" class="bxopt-cont-title"></div>
			<div id="bxopt_but_div" class="bxopt-cont">
			</div>

<?
// Get lang messages for editor for displaying buttons
function _GtFMess()
{
	if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/admin/fileman_js.php'))
		include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/'.LANGUAGE_ID.'/admin/fileman_js.php');
	else
		include($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/lang/en/admin/fileman_js.php');
	return $MESS;
}

$sMess = "";
$aMess = _GtFMess();
$aMess = array_keys($aMess);

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/fileman/admin/fileman_js.php');
$l = count($aMess);
for($i = 0; $i < $l; $i++)
	if(substr($aMess[$i], 0, strlen("FILEMAN_JS_"))=="FILEMAN_JS_")
		$sMess .= "'".substr($aMess[$i], strlen("FILEMAN_JS_"))."': '".CUtil::addslashes(GetMessage($aMess[$i]))."',";

$sMess = rtrim($sMess,',');

?>

<script>
var BX_MESS = {<?=$sMess?>};
function BXButtonConfig()
{
	this.Init();
}

BXButtonConfig.prototype =
{
	Init: function()
	{
		this.pEditorType = BX("ed_toolbar_type");
		this.pTitle = BX("bxopt_but_title");
		this.pWnd = BX("bxopt_but_div");
		this.arChecks = {};
		this.types = <?= CUtil::PhpToJSObject($arEdTypes)?>;
		this.curConfig = <?= CUtil::PhpToJSObject($arCurConfig)?>;

		var
			curCode = '<?= isset($_REQUEST['ed_toolbar_type']) ? CUtil::JSEscape($_REQUEST['ed_toolbar_type']) : ''?>',
			_this = this, i, l = this.types.length;

		this.pEditorType.onchange = function(){_this.ChangeEditorType(this.value);};

		window.pPropertybarHandlers = {};
		window.oBXEditorUtils = {addDOMHandler: function(){}, appendButton: function(){}};
		window._showAllButtons = true;

		this.oTypes = {};
		for (i = 0; i < l; i++)
			this.oTypes[this.types[i].code] = {oType: this.types[i]};

		// Load buttons
		BX.loadScript('/bitrix/admin/htmleditor2/toolbarbuttons.js', function()
			{
				_this.buttons = arButtons;
				_this.toolbars = window.arToolbars;
				_this.globalToolbar = window.arGlobalToolbar;
				_this.ChangeEditorType(curCode || _this.types[0].code);
			}
		);

		BX.loadCSS('/bitrix/admin/htmleditor2/editor.css');
	},

	BuildButtons: function(typeCode)
	{
		var tableCont = this.pWnd.appendChild(BX.create("TABLE", {props: {className: "bxopt-tbl"}, style: {display: 'none'}}));
		this.oTypes[typeCode].pTable = tableCont;
		this.oTypes[typeCode].arChecks = {};
		this.oTypes[typeCode].arTlbrChecks = {};
		// Store
		this.oTypes[typeCode].pInputCont = this.pWnd.appendChild(BX.create("DIV"));

		var k, i, l, row, chId, checkCell, checkBox, but, chBox, j, val, n;
		var _this = this;

		if (this.oTypes[typeCode].oType['public'])
		{
			toolbarId = 'global';
			this.AddButton(typeCode, 'new_line', -1, toolbarId);

			// Global toolbar
			for (i = 0, l = this.globalToolbar.length; i < l; i++)
				this.AddButton(typeCode, this.globalToolbar[i], i, toolbarId);

			if (this.curConfig[typeCode])
			{
				for(j = 0, n = this.curConfig[typeCode].length; j < n ; j++)
				{
					val = this.curConfig[typeCode][j];
					if (val.indexOf("-") != -1 && (chBox = BX("bxbut~" + typeCode + "~" + 'global' + "~" + val.substr(1))))
						chBox.checked = false;
				}
			}
		}
		else
		{
			for (k in this.toolbars)
			{
				if (typeof this.toolbars[k] != 'object')
					continue;

				// Add toolbar title
				chId = "bxedtlbr~" + typeCode + "~" + k;
				row = tableCont.insertRow(-1);

				checkCell = BX.adjust(row.insertCell(-1), {props: {className: "bxopt-check-sell bxopt-toolbar-title"}});
				checkBox = checkCell.appendChild(BX.create("INPUT", {props: {type: "checkbox", id: chId, checked: true}}));
				this.oTypes[typeCode].arTlbrChecks[k] = checkBox;

				//Just on/off button checkboxes
				checkBox.onclick = function()
				{
					var
						bCheck = !!this.checked,
						_id = this.id.split("~"),
						typeCode = _id[1],
						tlbrCode = _id[2],
						arCh = _this.oTypes[typeCode].arChecks[tlbrCode],
						j, k = arCh.length;

					for (j = 0; j < k; j++)
						arCh[j].checked = bCheck;
					_this.SaveConfig(typeCode);
				};

				BX.adjust(row.insertCell(-1), {props: {className: "bxopt-toolbar-title", colSpan: 2}, html: "<label for=\"" + chId + "\">" + this.toolbars[k][0] + "</label>"});

				// Add buttons
				for (i = 0, l = this.toolbars[k][1].length; i < l; i++)
					this.AddButton(typeCode, this.toolbars[k][1][i], i, k);

				// Add toolbar separator
				BX.adjust(tableCont.insertRow(-1).insertCell(-1),{props: {className: "bxopt-toolbar-sep", colSpan: 3}});

				if (this.curConfig[typeCode] && this.curConfig[typeCode][k])
				{
					for(j = 0, n = this.curConfig[typeCode][k].length; j < n ; j++)
					{
						val = this.curConfig[typeCode][k][j];
						if (val.indexOf("-") != -1 && (chBox = BX("bxbut~" + typeCode + "~" + k + "~" + val.substr(1))))
							chBox.checked = false;
					}
				}

				this.CheckToolbar(typeCode, k);
			}
		}
	},

	AddButton: function(typeCode, but, ind, toolbarId)
	{
		if (typeof but == 'undefined')
			return;

		var
			tableCont = this.oTypes[typeCode].pTable,
			row = tableCont.insertRow(-1),
			chId = "bxbut~" + typeCode + "~" + toolbarId + "~" + (but == 'line_begin' ? but : ind),
			_this = this;

		if (!this.oTypes[typeCode].arChecks[toolbarId])
			this.oTypes[typeCode].arChecks[toolbarId] = [];

		if (but == 'new_line' && ind >= 0)
		{
			// Add toolbar separator
			BX.adjust(row.insertCell(-1), {props: {className: "bxopt-toolbar-sep", colSpan: 3}});
			row = tableCont.insertRow(-1);
		}

		var
			checkCell = BX.adjust(row.insertCell(-1), {props: {className: "bxopt-check-sell"}}),
			checkBox = checkCell.appendChild(BX.create("INPUT", {props: {type: "checkbox", id: chId}}));

		checkBox.checked = true;

		if (ind >= 0)
		{
			this.oTypes[typeCode].arChecks[toolbarId].push(checkBox);

			checkBox.onclick = function()
			{
				var _id = this.id.split("~");
				_this.CheckToolbar(_id[1], _id[2]);
				_this.SaveConfig(typeCode);
			}
		}
		else
		{
			checkBox.checked = true;
			checkBox.disabled = true;
		}

		if (but == 'new_line')
		{
			BX.addClass(checkCell, "bxopt-toolbar-title");
			var sepCell = BX.adjust(row.insertCell(-1), {props: {className: "bxopt-toolbar-title", colSpan: 2}, html: "<label for=\"" + chId + "\">" + '<?= GetMessage('FILEMAN_EDITOR_BUT_CONF_NEW_LINE')?>' + "</label>"});
		}
		else if (but == 'separator')
		{
			var sepCell = BX.adjust(row.insertCell(-1), {props: {className: "bxopt-separator", colSpan: 2}, html: "<label for=\"" + chId + "\">" + '------------ <?= GetMessage('FILEMAN_EDITOR_BUT_CONF_SEP')?> ------------' + "</label>"});
		}
		else
		{
			var iconCell = BX.adjust(row.insertCell(-1), {props: {className: "bxopt-but-icon"}});
			var text = but[1].name || but[1].title || but[1].id;

			if (but[0] == 'BXButton' || but[0] == 'BXEdColorPicker')
			{
				var pIcon = iconCell.appendChild(BX.create("IMG", {props: {id: "bx_btn_" + but[1].id, src: "/bitrix/images/1.gif", className: "bxed-button"}}));

				var titleCell = BX.adjust(row.insertCell(-1), {props: {className: "bxopt-but-title"}, html: "<label for=\"" + chId + "\">" + text + "</label>"});

			}
			else if (but[0] == 'BXEdList' || but[0] == 'BXStyleList')
			{
				iconCell.colSpan = "2";
				var listTable = iconCell.appendChild(BX.create("TABLE", {props: {className: "bxopt-list"}, style: {width: (but[1].field_size || 120) + "px"}}));

				var r = listTable.insertRow(-1);
				r.insertCell(-1).appendChild(BX.create("DIV", {props: {className: "bxopt-listtitle"}, html: "<label for=\"" + chId + "\">" + text + "</label>"}));
				BX.adjust(r.insertCell(-1), {props: {className: "bx-listbutton"}, html: "&nbsp;"});
			}
		}
	},

	ChangeEditorType: function(type)
	{
		this.pEditorType.value = type;
		for (var k in this.oTypes)
		{
			if (typeof this.oTypes[k] == 'object' /* && this.oTypes[k].pTable */)
			{
				if (k == type)
				{
					if (!this.oTypes[k].pTable)
						this.BuildButtons(k);

					this.oTypes[k].pTable.style.display = '';
				}
				else
				{
					if (this.oTypes[k].pTable)
						this.oTypes[k].pTable.style.display = 'none';
				}
			}
		}
	},

	SaveConfig: function(typeCode)
	{
		// Clean inputs
		BX.cleanNode(this.oTypes[typeCode].pInputCont);

		// For global toolbars
		if (this.oTypes[typeCode].oType['public'])
		{
			var
				bChanged = false,
				value = [],
				pCh, _id, ind,
				arCh = this.oTypes[typeCode].arChecks['global'],
				i, l = arCh.length;

			for (i = 0; i < l; i++)
			{
				ind = arCh[i].id.split("~")[3];
				but = this.globalToolbar[ind];
				value.push((arCh[i].checked ? "" : "-") + ind.toString());

				if (!arCh[i].checked)
					bChanged = true;
			}

			if (bChanged)
			{
				l = value.length;
				for(i = 0; i < l; i++)
				{
					this.oTypes[typeCode].pInputCont.appendChild(BX.create("INPUT", {props: {type: 'hidden', name: 'tlbr[' + typeCode + '][global][' + i + ']', value: value[i]}}));
				}
			}
			else
			{
				this.oTypes[typeCode].pInputCont.appendChild(BX.create("INPUT", {props: {type: 'hidden', name: 'tlbr[' + typeCode + ']', value: 'clean'}}));
			}
		}
		else
		{
			var
				bChanged = false,
				value = {},
				pCh, _id, ind,
				arCh = this.oTypes[typeCode].arChecks,
				i, l, k;

			// For each toolbar
			for (k in arCh)
			{
				if (typeof arCh[k] != 'object')
					continue;

				l = arCh[k].length;
				for (i = 0; i < l; i++)
				{
					ind = arCh[k][i].id.split("~")[3];

					this.oTypes[typeCode].pInputCont.appendChild(BX.create("INPUT", {props: {type: 'hidden', name: 'tlbr[' + typeCode + '][' + k + '][' + i + ']', value: (arCh[k][i].checked ? "" : "-") + ind.toString()}}));

					if (!arCh[k][i].checked)
						bChanged = true;
				}
			}

			if (!bChanged)
			{
				BX.cleanNode(this.oTypes[typeCode].pInputCont);
				this.oTypes[typeCode].pInputCont.appendChild(BX.create("INPUT", {props: {type: 'hidden', name: 'tlbr[' + typeCode + ']', value: 'clean'}}));
			}
		}
	},

	CheckToolbar: function(typeCode, tlbr)
	{
		if (this.oTypes[typeCode].oType.public)
			return;

		var
			tlbrCh = BX("bxedtlbr~" + typeCode + "~" + tlbr),
			bCheck = false,
			arCh = this.oTypes[typeCode].arChecks[tlbr],
			i, l = arCh.length;

		for (i = 0; i < l; i++)
		{
			if (arCh[i].checked)
			{
				bCheck = true;
				break;
			}
		}

		if (tlbrCh)
			tlbrCh.checked = !!bCheck;
	}
};

new BXButtonConfig();

</script>
		</td>
	</tr>

<?endif; // $useEditor3?>
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("FILEMAN_EDITOR_CONVERT_SETTINGS");?></td>
	</tr>

	<? if ($useEditor3):?>
	<tr>
		<td width="50%" valign="top"><label for='replace_new_lines'><?echo GetMessage("FILEMAN_REPLACE_NEW_LINES_WITH_BR");?>:</td>
		<td  valign="top">
			<input type="checkbox" name="replace_new_lines" id='replace_new_lines' value="Y" <? if (COption::GetOptionString($module_id, "replace_new_lines", 'Y') == 'Y') echo 'checked';?>>
		</td>
	</tr>
	<? else:?>
	<tr>
		<td width="50%" valign="top"><label for='use_lca'><?echo GetMessage("FILEMAN_USE_LCA");?>:</td>
		<td  valign="top">
			<input type="checkbox" name="use_lca" id='use_lca' value="Y" <? if (COption::GetOptionString($module_id, "use_lca", 'N') == 'Y') echo 'checked';?>>
		</td>
	</tr>
	<tr>
		<td width="50%" valign="top"><?echo GetMessage("FILEMAN_ENTITIES_GROUPS");?>:</td>
		<td  valign="top">
			<?
			$opt = COption::GetOptionString($module_id, "ar_entities", 'umlya,greek,other');
			if ($opt == 'none')
				$ar_entities = array();
			else
				$ar_entities = explode(',', $opt);
			?>
			<table border="0" style="width:100%">
			<tr>
				<td><input type="checkbox" name="ar_entities[]" id='ent_umlya' value="umlya" <? if(in_array('umlya',$ar_entities)) echo 'checked';?>></td>
				<td><label for='ent_umlya'><?echo GetMessage("FILEMAN_ENTITIES_UMLYA");?></label></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="ar_entities[]" id="ent_greek" value="greek" <? if(in_array('greek',$ar_entities)) echo 'checked';?>></td>
				<td><label for='ent_greek'><?echo GetMessage("FILEMAN_ENTITIES_GREEK");?></label></td>
			</tr>
			<tr>
				<td><input type="checkbox" name="ar_entities[]" value="other" id="ent_other" <? if(in_array('other',$ar_entities)) echo 'checked';?>></td>
				<td><label for='ent_other'><?echo GetMessage("FILEMAN_ENTITIES_OTHER");?></label></td>
			</tr>
			</table>
		</td>
	</tr>
	<? endif; //$useEditor3 ?>

<? if ($useEditor3):?>

	<tr class="heading">
		<td colspan="2"><? echo GetMessage("FILEMAN_OPTION_SPELL_SET");?></td>
	</tr>

	<?
	if (function_exists('pspell_config_create')):
		$use_pspell_checked = (COption::GetOptionString($module_id, "use_pspell", "Y")=="Y") ? "checked" : "";
	?>
	<tr>
		<td valign="top"><label for="use_pspell"><?echo GetMessage("FILEMAN_OPTION_USE_PSPELL");?></label><br>
						<a title="<?echo GetMessage("FILEMAN_OPTION_ADDISH_DICS_TITLE");?>" href="http://aspell.sourceforge.net/" target="blank"><?echo GetMessage("FILEMAN_OPTION_ADDISH_DICS");?></a><br>
		</td>
		<td>
			<input type="checkbox" name="use_pspell" id="use_pspell" value="Y" <?echo $use_pspell_checked;?>>
		</td>
	</tr>
	<tr>
		<td><? echo GetMessage("FILEMAN_OPTION_USER_DIC_DIR");?></td>
		<td>
			<input type="text" name="user_dics_path" style="width: 100%" value="<? echo COption::GetOptionString($module_id, "user_dics_path", "/bitrix/modules/fileman/u_dics")?>">
		</td>
	</tr>
	<tr>
		<td><label for="use_separeted_dics"><?echo GetMessage("FILEMAN_OPTION_USE_SEP_DICS");?></label></td>
		<td>
			<input type="checkbox" name="use_separeted_dics" id="use_separeted_dics" value="Y" <?echo (COption::GetOptionString($module_id, "use_separeted_dics", "Y")=="Y") ? "checked" : "";?>>
		</td>
	</tr>
	<?else:
			COption::SetOptionString($module_id, "use_pspell", "N");
	?>
	<tr>
		<td valign="top"><?echo GetMessage("FILEMAN_OPTION_USE_PSPELL");?><br>
			<a title="<?echo GetMessage("FILEMAN_OPTION_INSTALL_PSPELL_TITLE");?>" href="http://php.net/manual/en/ref.pspell.php" target="blank"><?echo GetMessage("FILEMAN_OPTION_INSTALL_PSPELL");?></a><br>
			<a title="<?echo GetMessage("FILEMAN_OPTION_ADDISH_DICS_TITLE");?>" href="http://aspell.sourceforge.net/" target="blank"><?echo GetMessage("FILEMAN_OPTION_ADDISH_DICS");?></a><br>
		</td>
		<td valign="top">
			<?echo GetMessage("FILEMAN_OPTION_NOT_INSTALLED");?>
		</td>
	</tr>
	<?endif; // function_exists('pspell_config_create')?>
<? endif; //$useEditor3?>

<?
/* MEDIALIB TAB*/
$tabControl->BeginNextTab();

function _MLGetTypeHTML($type = array())
{
	$name = "ML_TYPE[".$type["id"]."]";
	ob_start();
?>
<div class="bx-ml-type"  id="type_cont_<?= $type["id"]?>">
<div class="bx-ml-type-label">
	<?if ($type["b_new"]):?>
		<input type="hidden" name="<?= $name."[NEW]"?>" value="Y" />
	<?endif;?>

	<? /* <input id="type_real_name_inp_<?= $type["id"]?>" type="hidden" name="<?= $name."[NAME]"?>" value="<?= $type["name"]?>" />  */?>
	<input type="hidden" name="<?= $name."[SYS]"?>" value="<?= $type["system"] ? "Y" : "N"?>" />

	<? if($type["system"]):?>
		<div><?= htmlspecialcharsex($type["name"])?></div>
	<? else:?>
		<div id="type_name_<?= $type["id"]?>" class="bx-ml-editable"><?= htmlspecialcharsex($type["name"])?></div>
		<? /*<input id="type_name_inp_<?= $type["id"]?>" type="text" size="30" value="<?= $type["name"]?>" style="display: none;" /> */ ?>
	<?endif;?>

	<? if($type["code"] != "image" || !$type["system"]):?>
		<a  id="type_del_<?= $type["id"]?>" class="bx-ml-type-del" href="javascript:void(0);"><?= GetMessage("FILEMAN_OPTION_DELETE")?></a>
	<?endif;?>
</div>

<? if($type["code"] != "image" || !$type["system"]):?>
	<div class="bx-ml-type-label-deleted">
	<input id="type_empty_<?= $type["id"]?>" type="hidden"  value="<?= $type['empty'] ? 'Y' : 'N'?>" />
	<input id="type_del_inp_<?= $type["id"]?>" type="hidden"  name="<?= $name."[DEL]"?>" value="N" />
	<div id="type_del_name_<?= $type["id"]?>"><?= htmlspecialcharsex($type["name"])?></div>
	<a  id="type_restore_<?= $type["id"]?>" class="bx-ml-type-restore" href="javascript:void(0);"><?= GetMessage("FILEMAN_ML_TYPE_RESTORE")?></a>
</div>
<?endif;?>

<div class="bx-ml-type-params">
	<table border="0" width="100%">
		<tr<?if(!$type["system"]):?> class="adm-detail-required-field"<?endif;?>><td class="adm-detail-content-cell-l bx-ml-td-left" width="40%">
			<label for="type_name_inp_<?= $type["id"]?>"><?= GetMessage('FILEMAN_OPTION_PROPS_NAME')?>:</label>
		</td><td class="adm-detail-content-cell-r" width="60%">
			<? if($type["system"]):?>
				<span class="bx-sys-value"><?= htmlspecialcharsex($type["name"])?></span>
				<input type="hidden" id="type_name_inp_<?= $type["id"]?>" value="<?= $type["name"]?>" />

			<? else:?>
				<input type="text"  name="<?= $name."[NAME]"?>" id="type_name_inp_<?= $type["id"]?>" value="<?= $type["name"]?>" size="40" />
			<?endif;?>
		</td></tr>

		<tr<?if(!$type["system"]):?> class="adm-detail-content-cell-l adm-detail-required-field"<?endif;?>><td class="bx-ml-td-left" width="40%">
			<input type="hidden" name="<?= $name."[CODE]"?>" value="<?= $type["code"]?>" />
			<label for="type_code_inp_<?= $type["id"]?>"><?= GetMessage('FILEMAN_ML_ADD_TYPE_CODE')?><? if(!$type["system"]):?><span class="required"><sup>1</sup></span><?endif;?>:</label>
		</td><td class="adm-detail-content-cell-r" width="60%">
			<? if($type["system"]):?>
				<span class="bx-sys-value"><?= htmlspecialcharsex($type["code"])?></span>
				<input type="hidden" name="<?= $name."[CODE]"?>" value="<?= $type["code"]?>" />
			<? else:?>
				<input type="text" name="<?= $name."[CODE]"?>" id="type_code_inp_<?= $type["id"]?>" value="<?= $type["code"]?>" size="40" />
			<?endif;?>
		</td></tr>

		<tr class="adm-detail-required-field"><td class="adm-detail-content-cell-l bx-ml-td-left" width="40%">
			<label for="type_ext_inp_<?= $type["id"]?>"><?= GetMessage('FILEMAN_ML_ADD_TYPE_EXT')?><span class="required"><sup>1</sup></span>:</label>
		</td><td class="adm-detail-content-cell-r" width="60%">
			<input type="text" name="<?= $name."[EXT]"?>" id="type_ext_inp_<?= $type["id"]?>" value="<?= $type["ext"]?>" size="40" />
		</td></tr>
		<tr><td class="adm-detail-content-cell-l bx-ml-td-left" width="40%">
			<label for="type_desc_inp_<?= $type["id"]?>"><?= GetMessage('FILEMAN_ML_ADD_TYPE_DESC')?>:</label>
		</td><td class="adm-detail-content-cell-r" style="height: 50px;" width="60%">
			<? if($type["system"]):?>
				<input name="<?= $name."[DESC]"?>" type="hidden" value="<?= htmlspecialcharsbx($type["desc"])?>" />
				<span><?= $type["desc"]?></span>
			<? else:?>
				<textarea name="<?= $name."[DESC]"?>" id="type_desc_inp_<?= $type["id"]?>" style="width: 260px;" rows="2" cols="30"><?= htmlspecialcharsbx($type["desc"])?></textarea>
			<?endif;?>
		</td></tr>
	</table>
</div>
</div>
<?
	$s = ob_get_contents();
	ob_end_clean();
	return $s;
}
?>

	<tr>
		<?
		$useML = (COption::GetOptionString($module_id, "use_medialib", "Y") == "Y");
		$displ = $useML ? '' : 'style="display:none;"';
		?>
		<td width="40%">
		<label for="use_medialib"><?= GetMessage("FILEMAN_OPTION_USE_MEDIALIB")?>:</label></td>
		<td width="60%"><input type="checkbox" name="use_medialib" id="use_medialib" value="Y" <?if($useML) echo " checked";?> onclick="BX('edit5_edit_table').className = 'edit-table' + (this.checked ? '' : ' edit-table-ml-hidden');">
		<img src="/bitrix/images/1.gif" style="width: 110px; height: 1px;" />
		</td>
	</tr>
	<tr <?= $displ?> class="bx-ml-hidden-row">
		<td><label for="medialib_thumb_width"><?= GetMessage("FILEMAN_MEDIALIB_THUMB_SIZE")?>:</label></td>
		<td><input type="text" name="medialib_thumb_width" id="medialib_thumb_width" value="<?= COption::GetOptionInt($module_id, "ml_thumb_width", 140)?>" size="6"/> x <input type="text" name="medialib_thumb_height" value="<?= COption::GetOptionInt($module_id, "ml_thumb_height", 105)?>" size="6"/></td>
	</tr>
	<tr <?= $displ?> class="bx-ml-hidden-row">
		<td><label for="medialib_ext"><?= GetMessage("FILEMAN_MEDIA_EXT")?>:</label></td>
		<td><input type="text" value="<?= COption::GetOptionString($module_id, "ml_media_extentions", CMedialib::GetDefaultMediaExtentions())?>" size="40" id="medialib_ext" name="medialib_ext"/></td>
	</tr>
	<tr <?= $displ?> class="bx-ml-hidden-row">
		<td><label for="medialib_max_width"><?= GetMessage("FILEMAN_MEDIALIB_MAX_SIZE")?>:</label></td>
		<td><input type="text" name="medialib_max_width" id="medialib_max_width" value="<?= COption::GetOptionInt($module_id, "ml_max_width", 1024)?>" size="6"/> x <input type="text" name="medialib_max_height" value="<?= COption::GetOptionInt($module_id, "ml_max_height", 1024)?>" size="6"/></td>
	</tr>
	<tr <?= $displ?> class="bx-ml-hidden-row">
		<td><label for="medialib_use_default"><?= GetMessage("FILEMAN_MEDIA_USE_DEF")?>:</label></td>
		<td><input type="checkbox" value="Y" <?if(COption::GetOptionString($module_id, "ml_use_default", true)) echo " checked";?> id="medialib_use_default" name="medialib_use_default"/></td>
	</tr>

<?
CMedialib::Init();
$arMLTypes = CMedialib::GetTypes(array(), true);
$maxCount = 0;
?>
	<tr class="heading bx-ml-hidden-row" <?= $displ?>>
		<td colspan=2><?= GetMessage("FILEMAN_SEC_MEDIALIB_TYPES")?></td>
	</tr>
	<tr <?= $displ?> class="bx-ml-hidden-row"><td colspan="2" align="center">

		<table id="bxml_type_tbl">
		<?for ($i = 0, $l = count($arMLTypes); $i < $l; $i++):?>
			<tr><td>
			<?= _MLGetTypeHTML($arMLTypes[$i]);?>
			<?
			if ($maxCount <= $arMLTypes[$i]['id'])
				$maxCount = $arMLTypes[$i]['id'] + 1;
			?>
			</td></tr>
		<?endfor;?>
			<tr><td align="right">
				<input onclick="addType();" type="button" value="<?= GetMessage("FILEMAN_ML_ADD_TYPE")?> >>" title="<?= GetMessage("FILEMAN_ML_ADD_TYPE_TITLE")?>" />
			</td></tr>
			<tr><td align="left">
				<?= BeginNote();?>
				<span class="required"><sup>1</sup></span><?= GetMessage("FILEMAN_ONLY_LATIN")?><br>
				<?= EndNote();?>
			</td></tr>
		</table>

<script>
window.onload = function(){setTimeout(function()
{
	window.oMLSet = {
		pTypeTbl: BX("bxml_type_tbl"),
		curCount: <?= $maxCount?>
	};

	<?for ($i = 0, $l = count($arMLTypes); $i < $l; $i++):?>
		InitEventForType('<?= $arMLTypes[$i]['id']?>');
	<?endfor;?>
},
50);};

function addType()
{
	var id = window.oMLSet.curCount++;
	var newCell = window.oMLSet.pTypeTbl.insertRow(window.oMLSet.pTypeTbl.rows.length - 1).insertCell(-1);
	var typeHtml = '<?= CUtil::JSEscape(_MLGetTypeHTML(array("id" => "tmp_ml_type_id", "name" => "", "code" => "", "ext" => "", "icon" => "", "desc" => "", "b_new" => true, "empty" => true)));?>';

	// Replace id, and increase "curCount"
	typeHtml = typeHtml.replace(/tmp_ml_type_id/ig, id);

	var code = [], start, end, i, cnt;
	while((start = typeHtml.indexOf('<' + 'script>')) != -1)
	{
		var end = typeHtml.indexOf('</' + 'script>', start);
		if(end == -1)
			break;
		code[code.length] = typeHtml.substr(start + 8, end - start - 8);
		typeHtml = typeHtml.substr(0, start) + typeHtml.substr(end + 9);
	}

	for(var i = 0, cnt = code.length; i < cnt; i++)
		if(code[i] != '')
			jsUtils.EvalGlobal(code[i]);
	newCell.innerHTML = typeHtml;

	setTimeout(function(){InitEventForType(id);}, 50);
}

function InitEventForType(id)
{
	var oType =
	{
		pCont: BX('type_cont_' + id),
		pName: BX('type_name_' + id),
		pNameInp: BX('type_name_inp_' + id),
		pDelLink: BX('type_del_' + id),
		pRestoreLink: BX('type_restore_' + id),
		pDelInput: BX('type_del_inp_' + id),
		pEmpty: BX('type_empty_' + id)
	};

	if (oType.pName && oType.pNameInp)
	{
		oType.pNameInp.onkeyup = function()
		{
			while (oType.pName.firstChild)
				oType.pName.removeChild(oType.pName.firstChild);

			oType.pName.appendChild(document.createTextNode(oType.pNameInp.value));
		};

		if (oType.pNameInp.value == "")
		{
			oType.pNameInp.value = "<?= GetMessage("FILEMAN_ML_ADD_TYPE_NEW")?>";
			oType.pName.innerHTML = "<?= GetMessage("FILEMAN_ML_ADD_TYPE_NEW")?>";
			oType.pNameInp.focus();
			oType.pNameInp.select();
		}
	}

	if (oType.pDelLink)
	{
		oType.pDelLink.onclick = function()
		{
			if (oType.pEmpty.value == "N")
				return alert("<?= GetMessage("FILEMAN_ML_TYPE_CANT_DEL")?>");

			oType.pCont.className = "bx-ml-type bx-ml-type-deleted";

			if (!oType.pDelName)
				oType.pDelName = BX("type_del_name_" + id);

			while (oType.pDelName.firstChild)
				oType.pDelName.removeChild(oType.pDelName.firstChild);

			oType.pDelName.appendChild(document.createTextNode(oType.pNameInp.value));
			oType.pDelInput.value = "Y";
		};
	}

	if (oType.pRestoreLink)
	{
		oType.pRestoreLink.onclick = function()
		{
			oType.pCont.className = "bx-ml-type";
			oType.pDelInput.value = "N";
		};
	}
}

</script>
	</td></tr>

<?if ($USER->IsAdmin()):?>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights2.php");?>

	<tr class="heading">
		<td colspan="2"><a name="limitaccess"></a><?= GetMessage('fileman_opt_restricted')?></td>
	</tr>
	<tr>
		<td valign="top"><?= GetMessage('fileman_opt_restricted_rights')?></td>
		<td>
<?
$arGroups = explode(",", COption::GetOptionString('fileman', 'default_edit_groups', ''));
$gr = CGroup::GetList(($v1="sort"), ($v2="asc"), array("ACTIVE"=>"Y", "ADMIN"=>"N", "ANONYMOUS"=>"N"));
$sOptions = '';
$sSel = '';
while($group = $gr->Fetch())
{
	$sel = in_array($group["ID"], $arGroups);
	if($sel)
		$sSel .= ($sSel <> ''? ', ':'').htmlspecialcharsbx($group["NAME"]);
	$sOptions .= '<option value="'.$group["ID"].'"'.($sel? " selected":"").'>'.htmlspecialcharsbx($group["NAME"])." [".$group["ID"]."]".'</option>'."\n";
}
?>
			<?if($sSel <> ''):?>
				<?= GetMessage('fileman_opt_sel')?> <b><?=$sSel?></b><br><br>
			<?endif?>
			<select name="DEFAULT_EDIT_GROUPS[]" size="7" multiple>
				<?echo $sOptions;?>
			</select>
		</td>
	</tr>
<?endif;?>

<?$tabControl->Buttons();?>
<script>
	function RestoreDefaults()
	{
		if(confirm('<?= AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
			window.location = "<?= $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>&<?=bitrix_sessid_get()?>";
	}
</script>
<input type="submit" class="adm-btn-save" <?if (!$USER->CanDoOperation('fileman_edit_all_settings')) echo "disabled" ?> name="Update" value="<?= GetMessage('FILEMAN_OPTION_SAVE')?>">
<input type="reset" name="reset" onClick="BX('site_select_id').disabled=<? if(COption::GetOptionString($module_id, "different_set", "N") != "Y") echo "true"; else echo "false"; ?>; SelectSite('<?echo htmlspecialcharsbx($siteList[0]["ID"])?>');" value="<?= GetMessage('FILEMAN_OPTION_RESET')?>">
<input type="hidden" name="Update" value="Y">
<input <?if (!$USER->CanDoOperation('fileman_edit_all_settings')) echo "disabled" ?> type="button" title="<?= GetMessage('MAIN_HINT_RESTORE_DEFAULTS')?>" OnClick="RestoreDefaults();" value="<?= GetMessage('MAIN_RESTORE_DEFAULTS')?>">
<?$tabControl->End();?>
</form>
