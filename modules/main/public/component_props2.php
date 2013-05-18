<?
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
if (!array_key_exists("component_name", $_GET))
{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/public/component_props.php");
	die();
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");

function PageParams($bUrlEncode = true)
{
	$amp = $bUrlEncode ? '&amp;' : '&';
	return
		'component_name='.urlencode(CUtil::addslashes($_GET["component_name"])).
		$amp.'component_template='.urlencode(CUtil::addslashes($_GET["component_template"])).
		$amp.'template_id='.urlencode(CUtil::addslashes($_GET["template_id"])).
		$amp.'lang='.urlencode(CUtil::addslashes(LANGUAGE_ID)).
		$amp.'src_path='.urlencode(CUtil::addslashes($_GET["src_path"])).
		$amp.'src_line='.intval($_GET["src_line"]).
		$amp.'src_page='.urlencode(CUtil::addslashes($_GET["src_page"])).
		$amp.'src_site='.urlencode(CUtil::addslashes($_GET["src_site"]));
}

$io = CBXVirtualIo::GetInstance();

$src_path = $io->CombinePath("/", $_GET["src_path"]);
$src_line = intval($_GET["src_line"]);

if(!$USER->CanDoOperation('edit_php') && !$USER->CanDoFileOperation('fm_lpa', array($_GET["src_site"], $src_path)))
	die(GetMessage("ACCESS_DENIED"));

$bLimitPhpAccess = !$USER->CanDoOperation('edit_php');

IncludeModuleLangFile(__FILE__);

CUtil::JSPostUnescape();

$obJSPopup = new CJSPopup('',
	array(
		'TITLE' => GetMessage("comp_prop_title")
	)
);

$obJSPopup->ShowTitlebar();
$strWarning = "";
$arValues = array();
$arTemplate = false;
$arComponent = false;
$arComponentDescription = false;
$arParameterGroups = array();
$filesrc = "";
$abs_path = "";
$curTemplate = "";

if(!CComponentEngine::CheckComponentName($_GET["component_name"]))
	$strWarning .= GetMessage("comp_prop_error_name")."<br>";

if($strWarning == "")
{
	// try to read parameters from script file
	/* Try to open script containing the component call */
	if(!$src_path || $src_line <= 0)
	{
		$strWarning .= GetMessage("comp_prop_err_param")."<br>";
	}
	else
	{
		$abs_path = $io->RelativeToAbsolutePath($src_path);
		$f = $io->GetFile($abs_path);
		$filesrc = $f->GetContents();
		if(!$filesrc || $filesrc == "")
			$strWarning .= GetMessage("comp_prop_err_open")."<br>";
	}

	if($strWarning == "")
	{
		$arComponent = PHPParser::FindComponent($_GET["component_name"], $filesrc, $src_line);

		if ($arComponent === false)
			$strWarning .= GetMessage("comp_prop_err_comp")."<br>";
		else
			$arValues = $arComponent["DATA"]["PARAMS"];
	}
}

if($strWarning == "")
{
	if($_SERVER["REQUEST_METHOD"] == "POST" && $_GET["action"] == "refresh")
	{
		// parameters were changed by "ok" button
		// we need to refresh the component description with new values
		$arValues = array_merge($arValues, $_POST);
	}

	$curTemplate = (isset($_POST["NEW_COMPONENT_TEMPLATE"])) ? $_POST["NEW_COMPONENT_TEMPLATE"] : $_GET["component_template"];
	$arComponentDescription = CComponentUtil::GetComponentDescr($_GET["component_name"]);
	$arComponentParameters = CComponentUtil::GetComponentProps($_GET["component_name"], $arValues);
	$arTemplateParameters = CComponentUtil::GetTemplateProps($_GET["component_name"], $curTemplate, $_GET["template_id"], $arValues);

	if (isset($arComponentParameters["GROUPS"]) && is_array($arComponentParameters["GROUPS"]))
		$arParameterGroups = $arParameterGroups + $arComponentParameters["GROUPS"];

	$arParameters = array();
	if (isset($arComponentParameters["PARAMETERS"]) && is_array($arComponentParameters["PARAMETERS"]))
		$arParameters = $arParameters + $arComponentParameters["PARAMETERS"];
	if (isset($arTemplateParameters) && is_array($arTemplateParameters))
		$arParameters = $arParameters + $arTemplateParameters;
	$arComponentTemplates = CComponentUtil::GetTemplatesList($_GET["component_name"], $_GET["template_id"]);

	/* save parameters to file */
	if($_SERVER["REQUEST_METHOD"] == "POST" && $_GET["action"] == "save" && $arComponent !== false && $arComponentDescription !== false)
	{
		if (!check_bitrix_sessid())
		{
			$strWarning .= GetMessage("comp_prop_err_save")."<br>";
		}
		else
		{
			$aPostValues = $_POST;
			unset($aPostValues["__closed_sections"]);
			unset($aPostValues["sessid"]);
			unset($aPostValues["bxpiheight"]);
			unset($aPostValues["bxpiwidth"]);

			CComponentUtil::PrepareVariables($aPostValues);
			foreach($aPostValues as $name => $value)
			{
				if(is_array($value) && count($value) == 1 && isset($value[0]) && $value[0] == "")
					$aPostValues[$name] = array();
				elseif($bLimitPhpAccess && substr($value, 0, 2) == '={' && substr($value, -1) == '}')
					$aPostValues[$name] = $arValues[$name];
			}

			//check template name
			$sTemplateName = "";
			foreach($arComponentTemplates as $templ)
			{
				if($templ["NAME"] == $_POST["NEW_COMPONENT_TEMPLATE"])
				{
					$sTemplateName = $templ["NAME"];
					break;
				}
			}

			$code =  ($arComponent["DATA"]["VARIABLE"]? $arComponent["DATA"]["VARIABLE"]."=":"").
				"\$APPLICATION->IncludeComponent(\"".$arComponent["DATA"]["COMPONENT_NAME"]."\", ".
				"\"".$sTemplateName."\", ".
				"array(\n\t".PHPParser::ReturnPHPStr2($aPostValues)."\n\t)".
				",\n\t".(strlen($arComponent["DATA"]["PARENT_COMP"]) > 0? $arComponent["DATA"]["PARENT_COMP"] : "false").
				(!empty($arComponent["DATA"]["FUNCTION_PARAMS"])? ",\n\t"."array(\n\t".PHPParser::ReturnPHPStr2($arComponent["DATA"]["FUNCTION_PARAMS"])."\n\t)" : "").
				"\n);";
			$filesrc_for_save = substr($filesrc, 0, $arComponent["START"]).$code.substr($filesrc, $arComponent["END"]);

			$f = $io->GetFile($abs_path);
			$arUndoParams = array(
				'module' => 'fileman',
				'undoType' => 'edit_component_props',
				'undoHandler' => 'CFileman::UndoEditFile',
				'arContent' => array(
					'absPath' => $abs_path,
					'content' => $f->GetContents()
				)
			);

			if($APPLICATION->SaveFileContent($abs_path, $filesrc_for_save))
			{
				CUndo::ShowUndoMessage(CUndo::Add($arUndoParams));
				$obJSPopup->Close();
			}
			else
			{
				$strWarning .= GetMessage("comp_prop_err_save")."<br>";
			}
		}
	}
}
$componentPath = CComponentEngine::MakeComponentPath($_GET["component_name"]);
$arComponentDescription["ICON"] = ltrim($arComponentDescription["ICON"], "/");
$localPath = getLocalPath("components".$componentPath);
if($localPath !== false && $arComponentDescription["ICON"] <> "" && $io->FileExists($io->RelativeToAbsolutePath($localPath."/".$arComponentDescription["ICON"])))
	$sIcon = $localPath."/".$arComponentDescription["ICON"];
else
	$sIcon = "/bitrix/images/fileman/htmledit2/component.gif";
?>
<?
$obJSPopup->StartDescription($sIcon);
?>
<?if($arComponentDescription["NAME"] <> ""):?>
<p title="<?echo GetMessage("comp_prop_name")?>" class="title"><?echo htmlspecialcharsbx($arComponentDescription["NAME"])?></p>
<?endif;?>
<?if($arComponentDescription["DESCRIPTION"] <> ""):?>
<p title="<?echo GetMessage("comp_prop_desc")?>"><?echo $arComponentDescription["DESCRIPTION"]?></p>
<?endif;?>
<p class="note" title="<?echo GetMessage("comp_prop_path")?>"><a href="/bitrix/admin/fileman_admin.php?lang=<?echo LANGUAGE_ID?>&amp;path=<?echo urlencode($localPath)?>"><?echo htmlspecialcharsbx($_GET["component_name"])?></a></p>
<?
if($strWarning <> "")
{
	//ShowError($strWarning);
	$obJSPopup->ShowValidationError($strWarning);
	//echo '<script>jsPopup.AdjustShadow()</script>';
}
?>
<?if(!empty($arComponentTemplates) || !empty($arComponentParameters["PARAMETERS"]) || !empty($arTemplateParameters)):?>
<?
$obJSPopup->StartContent();
?>
<?
$sSectArr = "";
$aClosedSections = array();
if(isset($_POST["__closed_sections"]) && $_POST["__closed_sections"]<>"")
{
	$sections = preg_replace("/[^a-z0-9_,]/i", "", $_POST["__closed_sections"]);
	$aClosedSections = explode(",", $sections);
	$sSectArr = "'".implode("','", $aClosedSections)."'";
}
?>
<script>
window.__closed_sections = [<?echo $sSectArr?>];
window.ShowSection = function(el)
{
	var i;
	var bShow = (el.className == "bx-popup-sign bx-popup-plus");
	el.className = (bShow? "bx-popup-sign bx-popup-minus":"bx-popup-sign bx-popup-plus");
	var tr = jsUtils.FindParentObject(jsUtils.FindParentObject(el, "table"), "tr");
	var id = tr.id;
	while((tr = jsUtils.FindNextSibling(tr, "tr")))
	{
		if(tr.className && tr.className == 'empty')
			break;
		if(bShow)
		{
			try{tr.style.display = 'table-row';}
			catch(e){tr.style.display = 'block';}
		}
		else
			tr.style.display = 'none';
	}
	if(bShow)
	{
		for(i in window.__closed_sections)
		{
			if(window.__closed_sections[i] == id)
			{
				delete window.__closed_sections[i];
				break;
			}
		}
	}
	else
		window.__closed_sections[window.__closed_sections.length] = id;

	var form = jsUtils.FindParentObject(el, "form");
	form.__closed_sections.value = '';
	for(i in window.__closed_sections)
		if(window.__closed_sections[i])
			form.__closed_sections.value += (form.__closed_sections.value!=''? ',':'') + window.__closed_sections[i];

	if(bShow && id == "sect_SEF_MODE")
		ShowSefUrls(form["SEF_MODE"]);
};

window.ShowSefUrls = function(el)
{
	var tr = jsUtils.FindParentObject(el, "tr");
	while((tr = jsUtils.FindNextSibling(tr, "tr")))
	{
		if(!tr.className)
			continue;
		if(tr.className == 'empty')
			break;
		if(el.checked && tr.className == 'sef' || !el.checked && tr.className == 'nonsef')
		{
			try{tr.style.display = 'table-row';}
			catch(e){tr.style.display = 'block';}
		}
		else
			tr.style.display = 'none';
	}
};

window.addElement = function(arNodes, arElements)
{
	var el, name, i, l;
	l = arNodes.length;
	for(i = 0; i < l; i++)
	{
		el = arNodes[i];
		if (el.name.length <= 0  || el.name.substr(0, 2) == '__' || el.name == 'sessid')
			continue;
		if(el.name.substr(el.name.length - 2, 2) == '[]')
		{
			name = el.name.substr(0, el.name.length - 2);
			if (!arElements[name])
				arElements[name] = [];
			arElements[name].push(el);
		}
		else
			arElements[el.name] = el;
	}
	return arElements;
};

window.getCompParamvals = function()
{
	var arElements = {};
	var parentNode = document.forms['bx_popup_form'];
	arElements = window.addElement(parentNode.getElementsByTagName("SELECT"), arElements);
	arElements = window.addElement(parentNode.getElementsByTagName("INPUT"), arElements);
	arElements = window.addElement(parentNode.getElementsByTagName("TEXTAREA"), arElements);
	return arElements;
};
</script>
<table cellspacing="0" class="bx-width100">
<?
$bHidden = false;
if(!empty($arComponentTemplates)):
	$bHidden = in_array("__template_sect", $aClosedSections);
?>
	<tr class="section" id="__template_sect">
		<td colspan="2">
			<table cellspacing="0">
				<tr>
					<td><a class="bx-popup-sign <?echo ($bHidden? "bx-popup-plus":"bx-popup-minus")?>" href="javascript:void(0)" onclick="ShowSection(this)" title="<?echo GetMessage("comp_prop_sect")?>"></a></td>
					<td><?echo GetMessage("comp_prop_template")?></td>
				</tr>
			</table>
		</td>
	</tr>
	<tr<?if($bHidden) echo ' style="display:none"'?>>
		<td class="bx-popup-label bx-width50"><?= GetMessage("comp_prop_template") ?>:</td>
		<td>
			<select name="NEW_COMPONENT_TEMPLATE" onchange="<?=$obJSPopup->jsPopup.".PostParameters('".PageParams()."&amp;action=refresh&amp;scroll='+".$obJSPopup->jsPopup.".GetContent().scrollTop);"?>">
<?
$arTemplateID = array();
foreach($arComponentTemplates as $template)
	if($template["TEMPLATE"] <> '' && $template["TEMPLATE"] <> '.default')
		$arTemplateID[] = $template["TEMPLATE"];

$arTemplates = array(".default"=>GetMessage("comp_prop_default_templ"));
if(!empty($arTemplateID))
{
	$db_site_templates = CSiteTemplate::GetList(array(), array("ID"=>$arTemplateID), array());
	while($ar_site_templates = $db_site_templates->Fetch())
		$arTemplates[$ar_site_templates['ID']] = $ar_site_templates['NAME'];
}

foreach($arComponentTemplates as $template):
	$showTemplateName = ($template["TEMPLATE"] <> '' && $arTemplates[$template["TEMPLATE"]] <> ''? $arTemplates[$template["TEMPLATE"]] : GetMessage("comp_prop_template_sys"));
?>
				<option value="<?= htmlspecialcharsbx($template["NAME"])?>"<?if($template["NAME"] == $curTemplate || $curTemplate == '' && $template["NAME"] == ".default") echo " selected";?>><?= htmlspecialcharsbx($template["NAME"]." (".$showTemplateName.")") ?></option>
<?endforeach;?>
			</select>
		</td>
	</tr>
<?
endif; //!empty($arComponentTemplates)

// Fetch tooltips
CComponentUtil::__IncludeLang($localPath, "/help/.tooltips.php");
$tooltips_path = $_SERVER["DOCUMENT_ROOT"].$localPath."/help/.tooltips.php";
$arTooltips = array();
if(file_exists($tooltips_path))
	include($tooltips_path);

//check whether we have parameters without parent group
foreach($arParameters as $prop)
{
	if(!array_key_exists("PARENT", $prop) || !array_key_exists($prop["PARENT"], $arParameterGroups))
	{
		$arParameterGroups["__additional_params"] = array("NAME"=>GetMessage("comp_prop_additional"));
		break;
	}
}

$hiddenParamsHTML = '';
$prevGroupID = "";
foreach($arParameterGroups as $groupID=>$aGroup):
$bSef = false;
foreach($arParameters as $ID=>$prop):
	if($groupID == "__additional_params" && array_key_exists("PARENT", $prop) && array_key_exists($prop["PARENT"], $arParameterGroups))
		continue;
	if($groupID <> "__additional_params" && $prop["PARENT"]<>$groupID)
		continue;
	$bHide = (array_key_exists("HIDDEN", $prop) && $prop["HIDDEN"] == "Y"); // hidden param

if($prevGroupID <> $groupID && !$bHide):
	$bHidden = in_array("sect_".$groupID, $aClosedSections);
	$prevGroupID = $groupID;
?>
	<tr class="empty">
		<td colspan="2"><div class="empty"></div></td>
	</tr>
	<tr class="section" id="sect_<?echo $groupID?>">
		<td colspan="2">
			<table cellspacing="0">
				<tr>
					<td><a class="bx-popup-sign <?echo ($bHidden? "bx-popup-plus":"bx-popup-minus")?>" href="javascript:void(0)" onclick="ShowSection(this)" title="<?echo GetMessage("comp_prop_sect")?>"></a></td>
					<td><?echo htmlspecialcharsbx($aGroup["NAME"])?></td>
				</tr>
			</table>
		</td>
	</tr>
<?
endif;

if($ID == "SEF_MODE" && $arValues[$ID] == "Y")
	$bSef = true;

$bSefHidden = false;
$sSefClass = "";
if(substr($ID, 0, strlen("VARIABLE_ALIASES_")) == "VARIABLE_ALIASES_")
{
	$bSefHidden = $bSef;
	$sSefClass = "nonsef";
}
if(substr($ID, 0, strlen("SEF_URL_TEMPLATES_")) == "SEF_URL_TEMPLATES_" || $ID == "SEF_FOLDER")
{
	$bSefHidden = !$bSef;
	$sSefClass = "sef";
}
if (!$bHide):
?>
	<tr<?if($bHidden || $bSefHidden) echo ' style="display:none"'?><?if($sSefClass<>"")echo ' class="'.$sSefClass.'"'?>>
		<td class="bx-width50 bx-popup-label"><?echo htmlspecialcharsbx($prop["NAME"]).":"?></td>
		<td>
<?
endif;

if (!array_key_exists($ID, $arValues))
{
	if (SubStr($ID, 0, StrLen("SEF_URL_TEMPLATES_")) == "SEF_URL_TEMPLATES_"
		&& is_array($arValues["SEF_URL_TEMPLATES"])
		&& array_key_exists(SubStr($ID, StrLen("SEF_URL_TEMPLATES_")), $arValues["SEF_URL_TEMPLATES"]))
		$arValues[$ID] = $arValues["SEF_URL_TEMPLATES"][SubStr($ID, StrLen("SEF_URL_TEMPLATES_"))];
	elseif (SubStr($ID, 0, StrLen("VARIABLE_ALIASES_")) == "VARIABLE_ALIASES_"
		&& is_array($arValues["VARIABLE_ALIASES"])
		&& array_key_exists(SubStr($ID, StrLen("VARIABLE_ALIASES_")), $arValues["VARIABLE_ALIASES"]))
		$arValues[$ID] = $arValues["VARIABLE_ALIASES"][SubStr($ID, StrLen("VARIABLE_ALIASES_"))];
}

if(!array_key_exists($ID, $arValues) && isset($prop["DEFAULT"]))
	$arValues[$ID] = $prop["DEFAULT"];

if($arValues["SEF_FOLDER"] == "")
	$arValues["SEF_FOLDER"] = GetDirPath($_GET["src_page"]);

if($prop["MULTIPLE"]=='Y' && !is_array($arValues[$ID]))
{
	if(isset($arValues[$ID]))
		$val = Array($arValues[$ID]);
	else
		$val = Array();
}
elseif($prop["TYPE"]=="LIST" && !is_array($arValues[$ID]))
	$val = Array($arValues[$ID]);
else
	$val = $arValues[$ID];

$res = "";
if($prop["COLS"]<1)
	$prop["COLS"] = '30';

if($prop["MULTIPLE"]=='Y')
{
	$prop["CNT"] = IntVal($prop["CNT"]);
	if($prop["CNT"]<1)
		$prop["CNT"] = 1;
}
switch(strtoupper($prop["TYPE"]))
{
	case "LIST":
		$prop["SIZE"] = ($prop["MULTIPLE"]=='Y' && IntVal($prop["SIZE"])<=1 ? '3' : $prop["SIZE"]);
		if(intval($prop["SIZE"])<=0)
			$prop["SIZE"] = 1;

		$res .= '<select name="'.$ID.($prop["MULTIPLE"]=="Y"?'[]':'').'"';
		if($prop["MULTIPLE"]=="Y")
			$res .=	' multiple';
		else
		{
			if($prop['ADDITIONAL_VALUES']=='Y' || $prop["REFRESH"]=="Y")
			{
				$res .= ' onChange="';
				if($prop['ADDITIONAL_VALUES']=='Y')
					$res .=	'this.form.elements[\''.$ID.'_alt\'].disabled = (this.selectedIndex!=0); ';
				if($prop["REFRESH"]=="Y")
				{
					if($prop['ADDITIONAL_VALUES']=='Y')
						$res .= 'if(this.selectedIndex!=0)';
					$res .= $obJSPopup->jsPopup.'.PostParameters(\''.PageParams().'&amp;action=refresh&amp;scroll=\'+'.$obJSPopup->jsPopup.'.GetContent().scrollTop);';
				}
				$res .= '"';
			}
		}
		$res .=	' size="'.$prop["SIZE"].'">';

		if(!is_array($prop["VALUES"]))
			$prop["VALUES"] = Array();

		$tmp = '';
		$bFound = false;
		foreach($prop["VALUES"] as $v_id=>$v_name)
		{
			$key = array_search(strval($v_id), $val, true);
			if($key === false || $key === null)
			{
				$tmp .= '<option value="'.htmlspecialcharsbx($v_id).'">'.htmlspecialcharsbx($v_name).'</option>';
			}
			else
			{
				unset($val[$key]);
				$bFound = true;
				$tmp .= '<option value="'.htmlspecialcharsbx($v_id).'" selected>'.htmlspecialcharsbx($v_name).'</option>';
			}
		}
		if($prop['ADDITIONAL_VALUES']=='Y')
			$res .= '<option value=""'.(!$bFound?' selected':'').'>'.($prop["MULTIPLE"]=="Y"?GetMessage("comp_prop_not_sel"):GetMessage("comp_prop_other").' -&gt;').'</option>';
		$res .= $tmp;
		$res .= '</select>';
		if($prop['ADDITIONAL_VALUES']=='Y')
		{
			if($prop["MULTIPLE"]=='Y')
			{
				reset($val);
				foreach($val as $v)
				{
					if($v == "")
						continue;
					$res .= '<br>';
					if($prop['ROWS']>1)
						$res .= '<textarea name="'.$ID.'[]" cols='.$prop["COLS"].'>'.htmlspecialcharsbx($v).'</textarea>';
					else
						$res .= '<input type="text" name="'.$ID.'[]" size='.$prop["COLS"].' value="'.htmlspecialcharsbx($v).'">';
				}

				for($i=0; $i<$prop["CNT"]; $i++)
				{
					$res .= '<br>';
					if($prop['ROWS']>1)
						$res .= '<textarea name="'.$ID.'[]" cols='.$prop["COLS"].'></textarea>';
					else
						$res .= '<input type="text" name="'.$ID.'[]" size='.$prop["COLS"].' value="">';
				}
				$res .= '<input type="button" value="+" onClick="var span = document.createElement(\'SPAN\'); this.parentNode.insertBefore(span, this); span.innerHTML=\''.
						'<br>';
				if($prop['ROWS']>1)
					$res .= '<textarea name=\\\''.$ID.'[]\\\' cols=\\\''.$prop["COLS"].'\\\'></textarea>';
				else
					$res .= '<input type=\\\'text\\\' name=\\\''.$ID.'[]\\\' size=\\\''.$prop["COLS"].'\\\'>';

				$res .= '\'">';
			}
			else
			{
				$res .= '<br>';
				if($prop['ROWS']>1)
					$res .= '<textarea name="'.$ID.'_alt" '.($bFound?' disabled ':'').' cols='.$prop["COLS"].'>'.htmlspecialcharsbx(count($val) > 0 ? $val[0] : '').'</textarea>';
				else
					$res .= '<input type="text" name="'.$ID.'_alt" '.($bFound?' disabled ':'').'size='.$prop["COLS"].' value="'.htmlspecialcharsbx(count($val)>0?$val[0]:'').'">';
			}
		}
		if($prop["REFRESH"]=="Y")
			$res .= '<input type="button" value="OK" onclick="'.$obJSPopup->jsPopup.'.PostParameters(\''.PageParams().'&amp;action=refresh&amp;scroll=\'+'.$obJSPopup->jsPopup.'.GetContent().scrollTop);">';
		break;
	case "CHECKBOX":
		$res .= '<input name="'.$ID.'" value="Y" type="checkbox"'.($val == "Y"? ' checked':'');
		if($prop["REFRESH"]=="Y")
			$res .= ' onclick="'.$obJSPopup->jsPopup.'.PostParameters(\''.PageParams().'&amp;action=refresh&amp;scroll=\'+'.$obJSPopup->jsPopup.'.GetContent().scrollTop);"';
		elseif($ID == "SEF_MODE")
			$res .= ' onclick="ShowSefUrls(this);"';
		$res .= '>';
		break;
	default: // 'STRING' OR 'FILE' OR 'COLORPICKER' OR 'CUSTOM'
		if($prop["TYPE"] == 'COLORPICKER' || $prop["TYPE"] == 'FILE')
		{
			$bAutoRefresh = true;
			$prop['ROWS'] = 1;
			$prop['MULTIPLE'] = 'N';
			$prop['COLS'] = ($prop["TYPE"] == 'FILE') ? 40 : 6;
		}

		if($prop["MULTIPLE"] == 'Y')
		{
			$bBr = false;
			foreach($val as $v)
			{
				if($v == "")
					continue;
				if($bBr)
					$res .= '<br>';
				else
					$bBr = true;
				if($prop['ROWS'] > 1)
					$res .= '<textarea name="'.$ID.'[]" cols='.$prop["COLS"].'>'.htmlspecialcharsbx($v).'</textarea>';
				else
					$res .= '<input type="text" name="'.$ID.'[]" size='.$prop["COLS"].' value="'.htmlspecialcharsbx($v).'">';
			}

			for($i=0; $i<$prop["CNT"]; $i++)
			{
				if($bBr)
					$res .= '<br>';
				else
					$bBr = true;
				if($prop['ROWS']>1)
					$res .= '<textarea name="'.$ID.'[]" cols='.$prop["COLS"].'></textarea>';
				else
					$res .= '<input type="text" name="'.$ID.'[]" size='.$prop["COLS"].' value="">';
			}

			$res .= '<input type="button" value="+" onClick="var span = document.createElement(\'SPAN\'); this.parentNode.insertBefore(span, this); span.innerHTML=\''.
					'<br>';
			if($prop['ROWS']>1)
				$res .= '<textarea name=\\\''.$ID.'[]\\\' cols=\\\''.$prop["COLS"].'\\\'></textarea>';
			else
				$res .= '<input type=\\\'text\\\' name=\\\''.$ID.'[]\\\' size=\\\''.$prop["COLS"].'\\\'>';

			$res .= '\'">';
		}
		else
		{
			if($prop['ROWS'] > 1)
			{
				$res .= '<textarea name="'.$ID.'" cols='.$prop["COLS"].'>'.htmlspecialcharsbx($val).'</textarea>';
			}
			else
			{
				if ($prop["TYPE"] == 'FILE')
				{
					CAdminFileDialog::ShowScript(Array
					(
						"event" => "BX_FD_".$ID,
						"arResultDest" => Array("FUNCTION_NAME" => "BX_FD_ONRESULT_".$ID),
						"arPath" => Array(),
						"select" => isset($prop['FD_TARGET']) ? $prop['FD_TARGET'] : 'F',
						"operation" => 'O',
						"showUploadTab" => (isset($prop['FD_UPLOAD']) && $prop['FD_UPLOAD'] && $prop['FD_TARGET'] == 'F'),
						"showAddToMenuTab" => false,
						"fileFilter" => isset($prop['FD_EXT']) ? $prop['FD_EXT'] : '',
						"allowAllFiles" => true,
						"SaveConfig" => true
					));

					$bML = isset($prop['FD_USE_MEDIALIB']) && $prop['FD_USE_MEDIALIB'];
					$res .= '<input id="__FD_PARAM_'.$ID.'" name="'.$ID.'" size='.$prop["COLS"].' value="'.htmlspecialcharsbx($val).'" type="text" '.($bML ? 'style="float:left;"' : '').'>';

					// Using medialib
					if ($bML)
					{
						$res .= "<div>".CMedialib::ShowBrowseButton(
							array(
								'mode' => $prop['FD_USE_ONLY_MEDIALIB'] ? 'medialib' : 'select',
								'value' => '...',
								'event' => "BX_FD_".$ID,
								'id' => "bx_fd_input_".strtolower($ID),
								'MedialibConfig' => array(
									"event" => "bx_ml_event_".$ID,
									"arResultDest" => Array("FUNCTION_NAME" => "BX_FD_ONRESULT_".$ID),
									"types" => is_array($prop['FD_MEDIALIB_TYPES']) ? $prop['FD_MEDIALIB_TYPES'] : false
								),
								'bReturnResult' => true
							)
						)."</div>";
					}
					else
					{
						// Use old good file dialog
						$res .= '<input size='.$prop["COLS"].' value="..." type="button" onclick="window.BX_FD_'.$ID.'();">';
					}

					$res .= '<script>
					setTimeout(function(){
						if (BX("bx_fd_input_'.strtolower($ID).'"))
							BX("bx_fd_input_'.strtolower($ID).'").onclick = window.BX_FD_'.$ID.';
					}, 200);

					window.BX_FD_ONRESULT_'.$ID.' = function(filename, filepath)
					{
						var oInput = BX("__FD_PARAM_'.$ID.'");
						if (typeof filename == "object")
							oInput.value = filename.src;
						else
							oInput.value = (filepath + "/" + filename).replace(/\/\//ig, \'/\');';

					if ($prop["REFRESH"]=="Y")
						$res .= $obJSPopup->jsPopup.'.PostParameters(\''.PageParams(false).'&action=refresh&scroll=\'+'.$obJSPopup->jsPopup.'.GetContent().scrollTop);';

					$res .= "};\n";

					if ($prop["REFRESH"]=="Y")
					{
					$res .= 'if(BX("__FD_PARAM_'.$ID.'"))
					{
					BX("__FD_PARAM_'.$ID.'").onblur = function()
					{
						'.$obJSPopup->jsPopup.'.PostParameters(\''.PageParams(false).'&action=refresh&scroll=\'+'.$obJSPopup->jsPopup.'.GetContent().scrollTop);
					};}';
					}
					$res .= '</script>';
				}
				elseif ($prop["TYPE"] == 'COLORPICKER')
				{
					ob_start();
					$jsid = strtolower($ID);
					?>
					<input name="<?= $ID?>" id="<?= $jsid?>" size="10" style="float: left;" value="<?= htmlspecialcharsbx($val)?>" type="text">
					<script>function colorOnSelect<?= $jsid?>(value){BX('<?= $jsid?>').value = value;}</script>
					<style>.bx-colpic-cont{z-index: 1500 !important;}</style>
					<?$APPLICATION->IncludeComponent(
						"bitrix:main.colorpicker",
						"",
						Array(
							"SHOW_BUTTON" => "Y",
							"ID" => 'cp'.$jsid,
							"NAME" => $prop["NAME"],
							"ONSELECT" => "colorOnSelect".$jsid
						),
						null,
						array("HIDE_ICONS" => "Y")
					);?>
					<?
					$res .= ob_get_contents();
					ob_end_clean();
				}
				elseif ($prop["TYPE"] == 'CUSTOM')
				{
					if (!isset($prop['JS_FILE']) || !isset($prop['JS_EVENT']))
						break;
					$data = isset($prop['JS_DATA']) ? $prop['JS_DATA'] : '';
					$res .= '<input id="__FD_PARAM_'.$ID.'" name="'.$ID.'" value="'.htmlspecialcharsbx($val).'" type="hidden">';
					$res .= '<script type="text/javascript" src="'.$prop['JS_FILE'].'?v='.@filemtime($_SERVER['DOCUMENT_ROOT'].$prop['JS_FILE']).'"></script>';
					$res .= '
					<script>
					setTimeout(
						function(){
							var oInput = document.getElementById("__FD_PARAM_'.$ID.'");
							if (!oInput) return;
							var cell = oInput.parentNode;
							var arProps = {
								popertyID : "'.$ID.'",
								propertyParams: '.CUtil::PhpToJsObject($prop).',
								getElements : window.getCompParamvals,
								oInput : oInput,
								oCont : cell,
								data : \''.CUtil::JSEscape($data).'\'
							};
							if (window.'.$prop['JS_EVENT'].')
								window.'.$prop['JS_EVENT'].'(arProps);
						},
						50
					);
					</script>';
				}
				else
				{
					$res .= '<input name="'.$ID.'" size='.$prop["COLS"].' value="'.htmlspecialcharsbx($val).'" type="text">';
				}
			}
		}
		if($prop["REFRESH"]=="Y" && (!isset($bAutoRefresh) || !$bAutoRefresh))
			$res .= '<input type="button" value="OK" onclick="'.$obJSPopup->jsPopup.'.PostParameters(\''.PageParams().'&amp;action=refresh&amp;scroll=\'+'.$obJSPopup->jsPopup.'.GetContent().scrollTop);">';
		break;
}
if(isset($arTooltips[$ID]))
	$res .= ShowJSHint($arTooltips[$ID], array('return' => true));
elseif(isset($MESS[$ID.'_TIP']))
	$res .= ShowJSHint($MESS[$ID.'_TIP'], array('return' => true));

if ($bHide):
	$hiddenParamsHTML .= $res;
else:
	echo $res;
?>
		</td>
	</tr>
<?
endif;
endforeach;
endforeach;
?>
</table>
<?
if (strlen($hiddenParamsHTML) > 0) // if exists hidden params we display them in the non-visible div with absolute positioning, but inside the form....
	echo '<div style="position: absolute; left: -2000px; top: -2000px; visibility: hidden;">'.$hiddenParamsHTML.'</div>';
?>

<input type="hidden" name="__closed_sections" value="<?echo htmlspecialcharsbx($_POST["__closed_sections"])?>">
<?
	$obJSPopup->StartButtons();
?>
	<input type="button" value="<?echo GetMessage("comp_prop_save")?>" onclick="<?=$obJSPopup->jsPopup?>.PostParameters('<?= PageParams().'&amp;action=save'?>');" title="<?echo GetMessage("comp_prop_save_title")?>" name="save" class="adm-btn-save" />
	<input type="button" value="<?echo GetMessage("comp_prop_cancel")?>" onclick="<?=$obJSPopup->jsPopup?>.CloseDialog()" title="<?echo GetMessage("comp_prop_cancel_title")?>" />
<?
	$obJSPopup->EndButtons();
?>
<?
else: //!empty($arTemplate["PARAMS"])
	$obJSPopup->StartButtons();
?>
	<input type="button" value="<?echo GetMessage("comp_prop_close_w")?>" onclick="<?=$obJSPopup->jsPopup?>.CloseDialog()" title="<?echo GetMessage("comp_prop_close")?>">
<?
	$obJSPopup->EndButtons();
endif; //!empty($arTemplate["PARAMS"])
?>
<?if(($scroll = intval($_GET["scroll"])) > 0):?>
<script>
var content = <?=$obJSPopup->jsPopup.".GetContent()"?>;
if(content)
	content.scrollTop = <?echo $scroll?>;
</script>
<?endif?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
?>