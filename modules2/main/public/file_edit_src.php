<?
// define('BX_PUBLIC_MODE', true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

// lpa is not allowed!
if (!($USER->CanDoOperation('edit_php')))
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

CUtil::JSPostUnescape();

$obJSPopup = new CJSPopup();

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/fileman/include.php");

IncludeModuleLangFile(__FILE__);

$strWarning = "";

$io = CBXVirtualIo::GetInstance();

$bVarsFromForm = false;
if (strlen($filename) > 0 && ($mess = CFileMan::CheckFileName($filename)) !== true)
{
	$filename2 = $filename;
	$filename = '';
	$strWarning = $mess;
	$bVarsFromForm = true;
}

if (CAutoSave::Allowed())
	$AUTOSAVE = new CAutoSave();

$path = $io->CombinePath("/", urldecode($path));
$site = CFileMan::__CheckSite($site);
if(!$site)
	$site = CSite::GetSiteByFullPath($_SERVER["DOCUMENT_ROOT"].$path);

$DOC_ROOT = CSite::GetSiteDocRoot($site);
$abs_path = $io->CombinePath($DOC_ROOT, $path);

if(strlen($new)>0 && strlen($filename)>0)
	$abs_path = $io->CombinePath($abs_path, $filename);

if((strlen($new) <= 0 || strlen($filename)<=0) && !$io->FileExists($abs_path))
{
	$p = strrpos($path, "/");
	if($p!==false)
	{
		$new = "Y";
		$filename = substr($path, $p+1);
		$path = substr($path, 0, $p);
	}
}

if(strlen($new) > 0 && strlen($filename) > 0 && ($io->FileExists($abs_path) || $io->DirectoryExists($abs_path)))		// если мы хотим создать новый файл, но уже такой есть - ругаемся
{
	$strWarning = GetMessage("FILEMAN_FILEEDIT_FILE_EXISTS")." ";
	$bEdit = false;
	$bVarsFromForm = true;
}
elseif(strlen($new) > 0)
{
	if (strlen($filename) < 0)
		$strWarning = GetMessage("FILEMAN_FILEEDIT_FILENAME_EMPTY")." ";
	$bEdit = false;
}
else
{
	if(!$io->FileExists($abs_path))
		$strWarning = GetMessage("FILEMAN_FILEEDIT_FOLDER_EXISTS")." ";
	else
		$bEdit = true;
}

if(strlen($strWarning)<=0)
{
	if($bEdit)
	{
		$f = $io->GetFile($abs_path);
		$filesrc_tmp = $f->GetContents();
	}
	else
	{
		$site_template = false;
		$rsSiteTemplates = CSite::GetTemplateList($site);
		while($arSiteTemplate = $rsSiteTemplates->Fetch())
		{
			if(strlen($arSiteTemplate["CONDITION"])<=0)
			{
				$site_template = $arSiteTemplate["TEMPLATE"];
				break;
			}
		}

		$arTemplates = CFileman::GetFileTemplates(LANGUAGE_ID, array($site_template));
		if(strlen($template)>0)
		{
			for ($i=0; $i<count($arTemplates); $i++)
			{
				if($arTemplates[$i]["file"] == $template)
				{
					$filesrc_tmp = CFileman::GetTemplateContent($arTemplates[$i]["file"], LANGUAGE_ID, array($site_template));
					break;
				}
			}
		}
		else
			$filesrc_tmp = CFileman::GetTemplateContent($arTemplates[0]["file"], LANGUAGE_ID, array($site_template));
	}

	if($REQUEST_METHOD=="POST" && strlen($save)>0)
	{
		if(!check_bitrix_sessid())
		{
			$strWarning = GetMessage("FILEMAN_SESSION_EXPIRED");
			$bVarsFromForm = true;
		}

		// lpa was denied earlier, so use file src as is
		$filesrc_for_save = $_POST['filesrc'];

		if(strlen($strWarning) <= 0)
		{
			if (!CFileMan::CheckOnAllowedComponents($filesrc_for_save))
			{
				$str_err = $APPLICATION->GetException();
				if($str_err && ($err = $str_err ->GetString()))
					$strWarning .= $err;
				$bVarsFromForm = true;
			}
		}

		if(strlen($strWarning) <= 0)
		{
			$f = $io->GetFile($abs_path);
			$arUndoParams = array(
				'module' => 'fileman',
				'undoType' => 'edit_file',
				'undoHandler' => 'CFileman::UndoEditFile',
				'arContent' => array(
					'absPath' => $abs_path,
					'content' => $f->GetContents()
				)
			);

			if(!$APPLICATION->SaveFileContent($abs_path, $filesrc_for_save))
			{
				if($str_err = $APPLICATION->GetException())
				{
					if ($str_err && ($err = $str_err->GetString()))
						$strWarning = $err;

					$bVarsFromForm = true;
				}

				if (empty($strWarning))
				{
					$strWarning = GetMessage("pub_src_edit_err");
				}
			}
			else
			{
				$bEdit = true;
				CUndo::ShowUndoMessage(CUndo::Add($arUndoParams));

				$module_id = "fileman";
				if(COption::GetOptionString($module_id, "log_page", "Y")=="Y")
				{
					$res_log['path'] = substr($path, 1);
					CEventLog::Log(
						"content",
						"PAGE_EDIT",
						"main",
						"",
						serialize($res_log),
						$_REQUEST["site"]
					);
				}

				if (CAutoSave::Allowed())
					$AUTOSAVE->Reset();
			}

			if(strlen($strWarning)<=0)
			{
?>
<script type="text/javascript" bxrunfirst="true">
top.BX.showWait();
top.BX.reload('<?=CUtil::JSEscape($_REQUEST["back_url"])?>', true);
top.<?=$obJSPopup->jsPopup?>.Close();
</script>
<?
				die();
			}

			$filesrc_tmp = $filesrc_for_save;
		}
	}
}

if (strlen($strWarning) > 0)
	$obJSPopup->ShowValidationError($strWarning);

if(!$bVarsFromForm)
{
	if(!$bEdit && strlen($filename)<=0)
		$filename = "untitled.php";

	$filesrc = $filesrc_tmp;
}
else
	$filesrc = $_POST['filesrc'];


/*************************************************/

$obJSPopup->ShowTitlebar(($bEdit ? GetMessage("FILEMAN_FILEEDIT_PAGE_TITLE") : GetMessage("FILEMAN_NEWFILEEDIT_TITLE")).": ".htmlspecialcharsbx($path));

$obJSPopup->StartDescription();

echo '<a href="/bitrix/admin/fileman_file_edit.php?path='.urlencode($path).'&amp;full_src=Y&amp;site='.$site.'&amp;lang='.LANGUAGE_ID.'&amp;back_url='.urlencode($_GET["back_url"]).(!$bEdit? '&amp;new=Y&amp;filename='.urlencode($filename).'&amp;template='.urlencode($template):'').($_REQUEST["templateID"]<>''? '&amp;templateID='.urlencode($_REQUEST["templateID"]):'').'" title="'.htmlspecialcharsbx($path).'">'.GetMessage("public_file_edit_edit_cp").'</a>';

$obJSPopup->StartContent();
if (CAutoSave::Allowed())
{
	echo CJSCore::Init(array('autosave'), true);
	$AUTOSAVE->Init();
?><script type="text/javascript">BX.WindowManager.Get().setAutosave();</script><?
}
?>

<input type="hidden" name="site" value="<?= htmlspecialcharsbx($site) ?>">
<input type="hidden" name="path" value="<?= htmlspecialcharsbx(urlencode($path)) ?>">
<input type="hidden" name="save" value="Y">
<input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
<input type="hidden" name="save" value="Y">
<input type="hidden" name="template" value="<?echo htmlspecialcharsbx($template)?>">
<input type="hidden" name="back_url" value="<?=htmlspecialcharsbx($back_url)?>">
<input type="hidden" name="templateID" value="<?=htmlspecialcharsbx($_REQUEST["templateID"])?>">

<?=bitrix_sessid_post()?>

<?if(!$bEdit):?>
<div id="bx_additional_params">
	<input type="hidden" name="new" value="y">
	<?echo GetMessage("FILEMAN_FILEEDIT_NAME")?><br>
	<?
	if (isset($filename2))
		$filename = $filename2;
	?>
	<input type="text" name="filename" style="width:100%" size="40" maxlength="255" value="<?echo htmlspecialcharsbx($filename)?>"><br><br>
</div>
<?endif;?>

<textarea id="bx-filesrc" name="filesrc" style="height: 99%; width: 100%;"><?= htmlspecialcharsbx($filesrc)?></textarea>

<?
$ceid = false;
if(COption::GetOptionString('fileman', "use_code_editor", "Y") == "Y" && CModule::IncludeModule('fileman'))
	$ceid = CCodeEditor::Show(array('textareaId' => 'bx-filesrc'));
?>

<script type="text/javascript">
var border = null, ta = null, wnd = BX.WindowManager.Get();

function TAResize(data)
{
	<?if ($ceid):?>
		var CE = window.BXCodeEditors['<?= $ceid?>'];
		if (CE && CE.Resize)
		{
			CE.Resize(data.width - 10, data.height - 60);
			return;
		}
	<?endif;?>

	if (null == ta)
		ta = BX('bx-filesrc');
	if (null == border)
		border = parseInt(BX.style(ta, 'border-left-width')) + parseInt(BX.style(ta, 'border-right-width'));
	if (isNaN(border))
		border = 0;

	var add = BX('bx_additional_params');

	if (data.height)
		ta.style.height = (data.height - border - wnd.PARTS.HEAD.offsetHeight - (add ? add.offsetHeight : 0) - 35) + 'px';
	if (data.width)
		ta.style.width = (data.width - border - 10) + 'px';
}

BX.addCustomEvent(wnd, 'onWindowResizeExt', TAResize);
TAResize(wnd.GetInnerPos());

<?if ($ceid):?>
BX.addCustomEvent(window, 'OnCodeEditorReady', function(){TAResize(wnd.GetInnerPos());});
<?endif;?>
</script>

<?
$obJSPopup->StartButtons();
$obJSPopup->ShowStandardButtons(array('save', 'cancel'));
$obJSPopup->EndButtons();

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>