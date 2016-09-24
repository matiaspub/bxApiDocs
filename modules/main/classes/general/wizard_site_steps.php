<?
class CPackageWelcome extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$package = $this->package;
		$this->SetTitle(GetMessage("MAIN_WIZARD_WELCOME_TITLE"));
	}

	static function OnPostForm()
	{
	}

	public function ShowStep()
	{
		$package = $this->package;

		if (strlen($this->content) == 0)
			$this->content = GetMessage("MAIN_WIZARD_WELCOME_TEXT");
	}
}


class CPackageLicense extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$package = $this->package;
		$this->SetTitle(GetMessage("MAIN_WIZARD_LICENSE_STEP_TITLE"));
		$this->SetSubTitle(GetMessage("MAIN_WIZARD_LICENSE_STEP_SUBTITLE"));
		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
	}

	public function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		if ($wizard->IsPrevButtonClick())
			return;

		$agree = $wizard->GetVar("__agree_license");
		if ($agree != "Y")
			$this->SetError(GetMessage("MAIN_WIZARD_LICENSE_STEP_ERROR"));
	}

	public function ShowStep()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		if (strlen($this->content) == 0)
			$this->content .= GetMessage("MAIN_WIZARD_LICENSE_STEP_CONTENT");

		$licensePath = $package->__GetLicensePath();

		$this->content .= '<div class="wizard-iframe-container"><iframe name="license_text" src="'.$licensePath.'" width="100%" height="160" border="0" frameBorder="1" vspace="5" scrolling="yes" class="wizard-license-iframe"></iframe></div>';
		$this->content .= $this->ShowCheckboxField("__agree_license", "Y", Array("id" => "agree_license_id"));
		$this->content .= '<label for="agree_license_id">'.GetMessage("MAIN_WIZARD_LICENSE_STEP_AGREE").'</label>';
	}
}

class CPackageSelectSite extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$this->SetTitle(GetMessage("MAIN_WIZARD_SELECT_SITE_TITLE"));
		$this->SetSubTitle(GetMessage("MAIN_WIZARD_SELECT_SITE_DESC"));
		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
	}

	public function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		if ($wizard->IsNextButtonClick())
		{
			$siteID = $package->siteID;

			if ($siteID != "" && array_key_exists($siteID, $package->arSites))
			{
				if ($package->_InitSubStep("select", $package->arSites[$siteID], false))
				{
					$wizard->SetCurrentStep($package->__obFirstStep->GetStepID());
					$package->__obFirstStep->SetPrevStep(BX_WIZARD_SELECT_SITE_ID);
				}
				else
				{
					if ($package->groupExists && $package->templateExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_GROUP_ID);
					elseif ($package->templateExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_TEMPLATE_ID);
					elseif ($package->serviceExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_SERVICE_ID);
					elseif ($package->structureExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_STRUCTURE_ID);
					else
						$wizard->SetCurrentStep(BX_WIZARD_START_INSTALL_ID);
				}
			}
			else
				$this->SetError(GetMessage("MAIN_WIZARD_ERROR_WRONG_SITE"), "__siteID");
		}
	}

	public function ShowStep()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		$this->content .= '<table width="100%" cellpadding="0" cellspacing="0">';

		foreach ($package->arSites as $siteID => $arSite)
		{
			if (isset($arSite["DEFAULT"]))
				$wizard->SetDefaultVar("__siteID", $siteID);

			$this->content .= '<tr>';

			$this->content .= '<td valign="top">'.$this->ShowRadioField("__siteID", $siteID, Array("id" => $siteID)).'</td>';

			$this->content .= '<td valign="top">';

			if (isset($arSite["SCREENSHOT"]) && isset($arSite["PREVIEW"]))
				$this->content .= CFile::Show2Images($package->path."/".$arSite["PREVIEW"], $package->path."/".$arSite["SCREENSHOT"], 150, 150, ' border="0"')."<br /><br />";
			elseif (isset($arSite["SCREENSHOT"]))
				$this->content .= CFile::ShowImage($package->path."/".$arSite["SCREENSHOT"], 150, 150, ' border="0"', "", true)."<br /><br />";

			$this->content .='</td>';

			$this->content .= '<td valign="top" width="100%" style="padding-left:4px;">';
			$this->content .= '<label for="'.htmlspecialcharsbx($siteID).'">';
			$this->content .= '<b>'.htmlspecialcharsEx($arSite["NAME"]).'</b></label>';

			if (isset($arSite["DESCRIPTION"]) && strlen($arSite["DESCRIPTION"]) > 0)
				$this->content .= '<br /><div style="margin-left:20px;"><label for="'.htmlspecialcharsbx($siteID).'">'.$arSite["DESCRIPTION"].'</label></div>';

			$this->content .='</td>';


			$this->content .= '</tr>';


		}

		$this->content .= '</table>';
	}

}

//Select template group
class CPackageSelectGroup extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
		$this->SetTitle(GetMessage("MAIN_WIZARD_SELECT_GROUP_TITLE"));
		$this->SetSubTitle(GetMessage("MAIN_WIZARD_SELECT_GROUP_DESC"));
		$this->SetDisplayVars(Array("__groupID"));
	}

	public function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		if ($wizard->IsNextButtonClick())
		{
			$groupID = $package->groupID;

			if ($groupID == "")
			{
				$this->SetError(GetMessage("MAIN_WIZARD_ERROR_WRONG_GROUP"), "__groupID");
				return;
			}

			$arGroups = $package->GetTemplateGroups(Array("SITE_ID" => $package->siteID));
			if (!array_key_exists($groupID, $arGroups))
			{
				$this->SetError(GetMessage("MAIN_WIZARD_ERROR_WRONG_GROUP"), "__groupID");
				return;
			}
		}
	}

	public function ShowStep()
	{
		$package = $this->package;
		$wizard = $this->GetWizard();

		$arGroups = $package->GetTemplateGroups(Array("SITE_ID" => $package->siteID));

		if (empty($arGroups))
			return;

		$this->content .= '<table cellspacing="0" cellpadding="2" width="100%"><tr>';

		$colsNumber = 3;
		$counter = 1;
		$cellSize = count($arGroups);

		foreach ($arGroups as $groupID => $arGroup)
		{
			if (isset($arGroup["DEFAULT"]))
				$wizard->SetDefaultVar("__groupID", $groupID);

			$this->content .= '<td valign="top" style="padding-bottom:15px;" width="33%">';

			if (isset($arGroup["SCREENSHOT"]) && isset($arGroup["PREVIEW"]))
				$this->content .= CFile::Show2Images($package->path."/".$arGroup["PREVIEW"], $package->path."/".$arGroup["SCREENSHOT"], 150, 150, ' border="0"')."<br />";
			elseif (isset($arGroup["SCREENSHOT"]))
				$this->content .= CFile::ShowImage($package->path."/".$arGroup["SCREENSHOT"], 150, 150, ' border="0"', "", true)."<br />";

			$this->content .= '<table><tr><td valign="top">';
			$this->content .= $this->ShowRadioField("__groupID", $groupID, Array("id" => $groupID));
			$this->content .= '</td><td>';
			$this->content .= '<label for="'.htmlspecialcharsbx($groupID).'">'.$arGroup["NAME"].'</label>';
			$this->content .= '</td></tr></table>';

			$this->content .= "</td>";

			//Close table cells
			if (!($counter % $colsNumber) && $cellSize != $counter)
				$this->content .= "</tr><tr>";

			if ($cellSize == $counter && ($cellSize % $colsNumber)>0)
			{
				for ($a=1;$a<=($colsNumber - ($cellSize % $colsNumber) );$a++)
					$this->content .= "<td>&nbsp;</td>";
			}

			$counter++;
		}

		$this->content .= "</tr></table>";
	}
}

class CPackageSelectTemplate extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$this->SetTitle(GetMessage("MAIN_WIZARD_SELECT_TEMPLATE_TITLE"));
		$this->SetSubTitle(GetMessage("MAIN_WIZARD_SELECT_TEMPLATE_DESC"));
		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
		$this->SetDisplayVars(Array("__templateID"));
	}

	public function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		if ($wizard->IsNextButtonClick())
		{
			$templateID = $package->templateID;

			if ($templateID != "" && array_key_exists($templateID, $package->arTemplates))
			{
				if ($package->_InitSubStep("select", $package->arTemplates[$templateID], false))
				{
					$wizard->SetCurrentStep($package->__obFirstStep->GetStepID());
					$package->__obFirstStep->SetPrevStep(BX_WIZARD_SELECT_TEMPLATE_ID);
				}
				else
				{
					if ($package->serviceExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_SERVICE_ID);
					elseif ($package->structureExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_STRUCTURE_ID);
					else
						$wizard->SetCurrentStep(BX_WIZARD_START_INSTALL_ID);
				}
			}
			else
				$this->SetError(GetMessage("MAIN_WIZARD_ERROR_WRONG_TEMPLATE"), "__templateID");
		}
	}

	public function ShowStep()
	{
		$package = $this->package;
		$wizard = $this->GetWizard();

		$arTemplates = $package->GetTemplates(
			Array(
				"SITE_ID" => $package->siteID,
				"GROUP_ID" => $package->groupID
			)
		);

		if (empty($arTemplates))
			return;

		$this->content .= '<table cellspacing="0" cellpadding="2" width="100%"><tr>';

		$colsNumber = 3;
		$counter = 1;
		$cellSize = count($arTemplates);

		foreach ($arTemplates as $arTemplate)
		{
			if (isset($arTemplate["DEFAULT"]))
				$wizard->SetDefaultVar("__templateID", $arTemplate["ID"]);

			$this->content .= '<td valign="top" style="padding-bottom:15px;" width="33%">';

			if ($arTemplate["SCREENSHOT"] && $arTemplate["PREVIEW"])
				$this->content .= CFile::Show2Images($arTemplate["PREVIEW"], $arTemplate["SCREENSHOT"], 150, 150, ' border="0"')."<br />";
			else
				$this->content .= CFile::ShowImage($arTemplate["SCREENSHOT"], 150, 150, ' border="0"', "", true)."<br />";

			$this->content .= '<table><tr><td valign="top">';
			$this->content .= $this->ShowRadioField("__templateID", $arTemplate["ID"], Array("id" => $arTemplate["ID"]));
			$this->content .= '</td><td>';
			$this->content .= '<label for="'.htmlspecialcharsbx($arTemplate["ID"]).'">'.$arTemplate["NAME"].'</label></td></tr>';
			$this->content .= '</table>';

			$this->content .= (strlen($arTemplate["DESCRIPTION"]) > 0 ? "<br />".$arTemplate["DESCRIPTION"] : "").'';

			$this->content .= "</td>";

			//Close table cells
			if (!($counter % $colsNumber) && $cellSize != $counter)
				$this->content .= "</tr><tr>";

			if ($cellSize == $counter && ($cellSize % $colsNumber)>0)
			{
				for ($a=1;$a<=($colsNumber - ($cellSize % $colsNumber) );$a++)
					$this->content .= "<td>&nbsp;</td>";
			}

			$counter++;
		}

		$this->content .= "</tr></table>";
	}
}

class CPackageSelectService extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
		$this->SetTitle(GetMessage("MAIN_WIZARD_SELECT_SERVICE_TITLE"));
		$this->SetSubTitle(GetMessage("MAIN_WIZARD_SELECT_SERVICE_DESC"));
	}

	public function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		if ($wizard->IsNextButtonClick())
		{
			$arServices = $package->serviceID;
			if (!is_array($arServices))
			{
				if ($package->serviceExists)
					$wizard->SetCurrentStep(BX_WIZARD_SELECT_STRUCTURE_ID);
				else
					$wizard->SetCurrentStep(BX_WIZARD_START_INSTALL_ID);
				return;
			}

			foreach ($arServices as $service)
			{
				if (!array_key_exists($service, $package->arServices))
						continue;

				if ($package->_InitSubStep("select", $package->arServices[$service], false))
				{
					$wizard->SetCurrentStep($package->__obFirstStep->GetStepID());
					$package->__obFirstStep->SetPrevStep(BX_WIZARD_SELECT_SERVICE_ID);
					break;
				}
				else
				{
					if ($package->serviceExists)
						$wizard->SetCurrentStep(BX_WIZARD_SELECT_STRUCTURE_ID);
					else
						$wizard->SetCurrentStep(BX_WIZARD_START_INSTALL_ID);
					//$wizard->SetCurrentStep("__start_install");
				}
			}
		}
	}

	public function ShowStep()
	{
		$package = $this->package;
		$wizard = $this->GetWizard();

		$arServices = $package->GetServices(Array("SITE_ID" => $package->siteID));

		$i = 0;

		$this->ShowCheckboxField("__serviceID[]", null);
		$this->content .= $this->ShowHiddenField("__serviceID", "");

		$this->content .= '<table width="100%" cellspacing="1" cellpadding="0">';

		foreach ($arServices as $serviceID => $arService)
		{
			$type = "checkbox";
			$group = "";

			if (array_key_exists("FORM", $arService) && is_array($arService["FORM"]))
			{
				if (array_key_exists("TYPE", $arService["FORM"]))
					$type = strtolower($arService["FORM"]["TYPE"]);

				if (array_key_exists("GROUP", $arService["FORM"]))
					$group = strtolower(preg_replace("~[^a-zA-Z0-9_]~i", "", $arService["FORM"]["GROUP"]));

				if (array_key_exists("DEFAULT", $arService["FORM"]) && $type != "required")
				{
					if ($group == "")
						$wizard->SetDefaultVar("__serviceID[".$i++."]", $serviceID);
					else
						$wizard->SetDefaultVar("__serviceID[".$group."][".$i++."]", $serviceID);
				}
			}

			if ($type == "radio")
			{
				$this->content .= '<tr>';
				$this->content .= '<td valign="top">'.$this->ShowRadioField("__serviceID[".$group."]", $serviceID, Array("id" => $serviceID)).'</td>';

				$this->content .= '<td valign="top">';
				if (isset($arService["ICON"]) && strlen($arService["ICON"]) > 0)
					$this->content .= '<label for="'.$serviceID.'"><img src="'.$package->GetPath().'/'.$arService["ICON"].'" /></label>';
				$this->content .= '</td>';

				$this->content .= '<td valign="top" width="100%">';
				$this->content .= '<label for="'.$serviceID.'">&nbsp;<b>'.$arService["NAME"].'</b></label><br />';

				if (isset($arService["DESCRIPTION"]) && strlen($arService["DESCRIPTION"]) > 0)
					$this->content .= '<div style="margin-left:20px;"><label for="'.$serviceID.'">'.$arService["DESCRIPTION"].'</label></div>';

				$this->content .= '</td>';

				$this->content .= '</tr>';
			}
			elseif ($type == "required")
			{
				$this->content .= '<tr>';
				$this->content .= '<td valign="top"><input type="checkbox" disabled="disabled" checked="checked" name="required" value="" /></td>';

				$this->content .= '<td valign="top">';
				if (isset($arService["ICON"]) && strlen($arService["ICON"]) > 0)
					$this->content .= '<img src="'.$package->GetPath().'/'.$arService["ICON"].'" />';
				$this->content .= '</td>';

				$this->content .= '<td valign="top" width="100%">';
				$this->content .= "&nbsp;<b>".$arService["NAME"].'</b><br />';

				if (isset($arService["DESCRIPTION"]) && strlen($arService["DESCRIPTION"]) > 0)
					$this->content .= '<div style="margin-left:20px;">'.$arService["DESCRIPTION"].'</div>';

				$this->content .= $this->ShowHiddenField("__serviceID[]", $serviceID);
				$this->content .= '</td>';

				$this->content .= '</tr>';
			}
			else
			{
				$this->content .= '<tr>';

				$this->content .= '<td valign="top">'.$this->ShowCheckboxField("__serviceID[]", $serviceID, Array("id" => $serviceID)).'</td>';

				$this->content .= '<td valign="top">';
				if (isset($arService["ICON"]) && strlen($arService["ICON"]) > 0)
					$this->content .= '<label for="'.$serviceID.'"><img src="'.$package->GetPath().'/'.$arService["ICON"].'" /></label>';
				$this->content .= '</td>';

				$this->content .= '<td valign="top" width="100%">';
				$this->content .= '<label for="'.$serviceID.'">&nbsp;<b>'.$arService["NAME"].'</b></label><br />';

				if (isset($arService["DESCRIPTION"]) && strlen($arService["DESCRIPTION"]) > 0)
					$this->content .= '<div style="margin-left:20px;"><label for="'.$serviceID.'">'.$arService["DESCRIPTION"].'</label></div>';

				$this->content .= '</td>';

				$this->content .= '</tr>';
			}
		}

		$this->content .= '</table>';

	}
}


class CPackageSelectStructure extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$this->SetTitle(GetMessage("MAIN_WIZARD_SELECT_STRUCTURE_TITLE"));
		$this->SetSubTitle(GetMessage("MAIN_WIZARD_SELECT_STRUCTURE_DESC"));
		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
	}

	public function DisplayTree(&$arStructure, $systemTree = false)
	{
		$strTree = "";

		if (!is_array($arStructure))
			return $strTree;

		static $labelID = 0;

		foreach ($arStructure as $pageID => $arPage)
		{
			$pageID = (isset($arPage["ID"]) ? $arPage["ID"] : $pageID);
			$isService = (isset($arPage["TYPE"]) && $arPage["TYPE"] == "SERVICE");
			$isRoot = (!$isService && isset($arPage["CHILD"]) && is_array($arPage["CHILD"]) && !empty($arPage["CHILD"]));

			$labelID++;
			$strTree .= '<li><input id="page'.$labelID.'" type="checkbox"'.($isService ? ' class="locked"'.($systemTree ? ' disabled="disabled"' : '') : '').' value="'.$pageID.'" onclick="WizOnCheckBoxClick(this)">';

			if (isset($arPage["ICON"]) && strlen($arPage["ICON"]) > 3 )
				$strTree .= '<img src="'.$this->package->GetPath().'/'.$arPage["ICON"].'" />&nbsp;';
			else
			{
				if ($isService)
					$strTree .= '<img src="/bitrix/images/main/wizard/service.gif" width="16" height="16" border="0" alt="" />';
				else
					$strTree .= '<img src="/bitrix/images/main/wizard/page.gif" width="16" height="16" border="0" alt="" />';
			}

			$strTree .= '<label for="page'.$labelID.'">&nbsp;'.$arPage["NAME"].'</label>';

			if ($isRoot)
			{
				$strTree .= "<ul>";
				foreach ($arPage["CHILD"] as $subPageID => $arSubPage)
				{
					$labelID++;
					$subPageID = (isset($arSubPage["ID"]) ? $arSubPage["ID"] : $pageID."-".$subPageID);
					$strTree .= '<li><input id="page'.$labelID.'" type="checkbox" value="'.$subPageID.'" onclick="WizOnCheckBoxClick(this)" />';

					if (isset($arSubPage["ICON"]) && strlen($arSubPage["ICON"]) > 3 )
						$strTree .= '<img src="'.$this->package->GetPath().'/'.$arSubPage["ICON"].'" />';
					else
						$strTree .= '<img src="/bitrix/images/main/wizard/page.gif" width="16" height="16" border="0" alt="" />';

					$strTree .=  '<label for="page'.$labelID.'">&nbsp;'.$arSubPage["NAME"].'</label></li>';
				}
				$strTree .= "</ul>";
			}

			$strTree .='</li>';
		}

		return $strTree;
	}

	public function ShowStep()
	{
		$wizard = $this->GetWizard();
		$package = $this->package;

		$structureID = $package->structureID;

		$arOriginalStructure = $package->GetStructure(
			Array(
				"SITE_ID" => $package->siteID,
				"SERVICE_ID" => $package->serviceID
			)
		);

		if ($structureID !== null)
			$arStructure = $package->__GetNewStructure($structureID, $arOriginalStructure);
		else
			$arStructure = $arOriginalStructure;

		$strTree = $this->DisplayTree($arStructure);
		$strTrash = $this->DisplayTree($arOriginalStructure, $systemTree = true);

		$formName = $wizard->GetFormName();
		$nextButton = $wizard->GetNextButtonID();
		$prevButton = $wizard->GetPrevButtonID();
		$prefix = $wizard->GetVarPrefix();

		$langStandartPages = GetMessage("MAIN_WIZARD_STANDART_PAGES");
		$langSiteStructure = GetMessage("MAIN_WIZARD_SITE_STRUCTURE");
		$langPagePosition = GetMessage("MAIN_WIZARD_PAGE_POSITION");

		$this->content .= <<<TABLE

			<style type="text/css">
				ul.site-tree
				{
					list-style:none;
					margin:0;
					padding:0;
				}

				ul.site-tree ul
				{
					list-style:none;
					margin-top:0;
					margin-bottom:0;
				}

			</style>

				<div style="overflow:auto;">
				<table width="100%" class="wizard-data-table" cellpadding="0">
				<tr height="20">
					<th width="50%" align="left"><input type="checkbox" name="" onclick="WizSelectAll(document.getElementById('system-pages'), this.checked);WizDisableCopyButton();">&nbsp;{$langStandartPages}</th>
					<th></th>
					<th width="50%" align="left"><input type="checkbox" name="" onclick="WizSelectAll(document.getElementById('site-tree'), this.checked);WizDisableButtons();">&nbsp;{$langSiteStructure}</th>
					<th>{$langPagePosition}</th>
				</tr>
				<tr>
					<td valign="top">
						<ul class="site-tree" id="system-pages">{$strTrash}</ul>
					</td>
					<td align="center">
						<br /><input type="button" id="move-button-copy" value="&rarr;"  onclick="WizCopyItems();WizDisableCopyButton();WizDisableButtons();" disabled="disabled" style="width:50px; font-size:14px;" /><br /><br />
						<input type="button" id="move-button-delete" value="&larr;" onclick="WizDeleteItems();WizDisableButtons();" disabled="disabled" style="width:50px; font-size:14px;" /><br /><br />
					</td>
					<td valign="top">
						<ul class="site-tree" id="site-tree" style="height:100%;">{$strTree}</ul>
					</td>
					<td valign="top" align="center">
						<br /><input type="button" id="sort-button-up" value="&uarr;" onclick="WizSortUp();WizDisableButtons();" disabled="disabled" style="width:40px; font-size:14px;" /><br /><br />
						<input type="button" id="sort-button-down" value="&darr;" onclick="WizSortDown();WizDisableButtons();" disabled="disabled" style="width:40px; font-size:14px;" /><br /><br />
						<input type="button" id="sort-button-left" value="&larr;" onclick="WizSortLeft();WizDisableButtons();" disabled="disabled" style="width:40px; font-size:14px;" /><br /><br />
						<input type="button" id="sort-button-right" value="&rarr;" onclick="WizSortRight();WizDisableButtons();" disabled="disabled" style="width:40px; font-size:14px;" /><br /><br />
					</td>
				</tr>
			</table>
			</div>

			<script type="text/javascript">

				function WizSaveSiteTree()
				{
					var source = document.getElementById("site-tree");
					if (!source)
						return;

					var hiddenValue = "";

					for (var i = 0; i < source.childNodes.length; i++)
					{
						var page = source.childNodes[i];
						hiddenValue += page.childNodes[0].value + ";";

						var subUL = page.getElementsByTagName("UL");
						if (subUL.length == 1)
						{
							subPages = subUL[0].getElementsByTagName("LI");
							for (var j = 0; j < subPages.length; j++)
							{
								var subPage = subPages[j];
								hiddenValue += subPage.childNodes[0].value + ":" + page.childNodes[0].value + ";";
							}
						}
					}

					hiddenField = document.createElement("INPUT");
					hiddenField.type = "hidden";
					hiddenField.name = "{$prefix}" + "__structureID";
					hiddenField.value = hiddenValue;
					document.forms["{$formName}"].appendChild(hiddenField);
				}


				function WizOnCheckBoxClick(checkbox)
				{
					var li = checkbox.parentNode;
					WizSelectAll(li, checkbox.checked);

					var systemPages = document.getElementById("system-pages");
					if (li.parentNode == systemPages || li.parentNode.parentNode.parentNode == systemPages)
						WizDisableCopyButton();
					else
						WizDisableButtons();
				}

				function WizSelectAll(source, checked)
				{
					var items = source.getElementsByTagName("INPUT");

					for (var i = 0; i < items.length; i++)
						if (!items[i].disabled)
							items[i].checked = checked;
				}

				function WizDisableCopyButton()
				{
					var source = document.getElementById("system-pages");
					if (!source)
						return;

					var items = source.getElementsByTagName("INPUT");

					if (items.length <= 0)
						return;

					var disableCopyButton = true;
					for (var i = 0; i < items.length; i++)
					{
						if (items[i].checked && !items[i].disabled)
						{
							disableCopyButton = false;
							break;
						}
					}

					document.getElementById("move-button-copy").disabled = disableCopyButton;

				}

				function WizDisableButtons()
				{
					var source = document.getElementById("site-tree");
					if (!source)
						return;

					var items = source.getElementsByTagName("INPUT");

					if (items.length <= 0)
						return;

					var upButtonDisable = false;
					var downButtonDisable = false;
					var leftButtonDisable = false;
					var rightButtonDisable = false;

					var deleteButtonDisable = false;

					var isOneChecked = false;
					for (var i = 0; i < items.length; i++)
					{
						if (items[i].checked)
						{
							isOneChecked = true;

							if (items[i].className == "locked")
							{
								leftButtonDisable = true;
								rightButtonDisable = true;
								deleteButtonDisable = true;
							}

							li = items[i].parentNode;

							if (!li.previousSibling)
								upButtonDisable = true;

							if (!li.nextSibling)
								downButtonDisable = true;

							if (li.parentNode != source || !li.previousSibling || li.previousSibling.childNodes[0].className == "locked" || li.getElementsByTagName("UL").length > 0)
								rightButtonDisable = true;

							if (li.parentNode == source)
								leftButtonDisable = true;
						}
					}

					if (!isOneChecked)
						upButtonDisable = downButtonDisable = leftButtonDisable = rightButtonDisable = true;

					if (!isOneChecked)
						deleteButtonDisable = true;
					/*else if (items[0].checked)
					{
						upButtonDisable = true;
						rightButtonDisable = true;
						leftButtonDisable = true;
					}*/

					document.getElementById("sort-button-up").disabled = upButtonDisable;
					document.getElementById("sort-button-down").disabled = downButtonDisable;
					document.getElementById("sort-button-left").disabled = leftButtonDisable;
					document.getElementById("sort-button-right").disabled = rightButtonDisable;
					document.getElementById("move-button-delete").disabled = deleteButtonDisable;
				}


				var wizLabelID = 1;
				function WizCopyItems()
				{
					var source = document.getElementById("system-pages");
					var dest = document.getElementById("site-tree");
					if (!source || !dest)
						return;

					var items = source.getElementsByTagName("INPUT");

					for (var i = 0; i < items.length; i++)
					{
						if (items[i].checked)
						{
							if (items[i].className == "locked")
								continue;

							var li = items[i].parentNode;
							var newItem = li.cloneNode(true);

							//var ul = newItem.childNodes[newItem.childNodes.length-1];

							/*if (ul.nodeName == "UL")
							{
								var subItems = ul.getElementsByTagName("INPUT");
								var itemsToDelete = [];
								for (var j = 0; j < subItems.length; j++)
								{
									if (!subItems[j].checked)
										itemsToDelete[itemsToDelete.length] = subItems[j].parentNode;
										//ul.removeChild(subItems[j].parentNode);
								}

								if (itemsToDelete.length == subItems.length)
								{
									alert(111);
									newItem.removeChild(ul);
								}
								else
								{
									for (var j = 0; j < itemsToDelete.length; j++)
										ul.removeChild(itemsToDelete[j]);
								}
							}*/

							var inputs = newItem.getElementsByTagName("INPUT");
							for (j = 0; j < inputs.length; j++)
							{
								wizLabelID++;
								inputs[j].id = "n" + wizLabelID;
								inputs[j].nextSibling.nextSibling.setAttribute("for", "n" + wizLabelID);
								inputs[j].nextSibling.nextSibling.htmlFor = "n" + wizLabelID;
							}

							dest.appendChild(newItem);
							WizSelectAll(li, false);
							WizSelectAll(newItem, true);
						}
					}
				}

				function WizDeleteItems()
				{
					var source = document.getElementById("site-tree");

					var items = source.getElementsByTagName("INPUT");
					var itemsToMove = [];

					for (var i = 0; i < items.length; i++)
					{
						if (items[i].checked && items[i].className != "locked")
						{
							items[i].checked = false;
							itemsToMove[itemsToMove.length] = items[i].parentNode;
						}
					}

					for (var i = 0; i < itemsToMove.length; i++)
					{
						var ul = itemsToMove[i].parentNode;
						ul.removeChild(itemsToMove[i]);

						if (ul != source && ul.getElementsByTagName("li").length <= 0)
							ul.parentNode.removeChild(ul);
					}
				}

				function WizSortRight()
				{
					var source = document.getElementById("site-tree");
					if (!source)
						return;

					var items = source.getElementsByTagName("INPUT");

					for (var i = 0; i < items.length; i++)
					{
						var li = items[i].parentNode;

						if (!items[i].checked || li.getElementsByTagName("UL").length > 0 || items[i].className == "locked")
							continue;

						if (li.parentNode == source && li.previousSibling && li.previousSibling.childNodes[0].className != "locked")
						{
							var ulTags = li.previousSibling.getElementsByTagName("UL");
							if (ulTags.length > 0)
								var newItem = ulTags[0].appendChild(li);
							else
							{
								var ul = document.createElement("UL");
								li.previousSibling.appendChild(ul);
								var newItem = ul.appendChild(li);
							}

							newItem.childNodes[0].checked = true;
						}
					}
				}

				function WizSortLeft()
				{
					var source = document.getElementById("site-tree");
					if (!source)
						return;

					var items = source.getElementsByTagName("INPUT");
					var itemsToMove = [];

					for (var i = 0; i < items.length; i++)
					{
						var li = items[i].parentNode;
						if (!items[i].checked || li.parentNode == source)
							continue;

						itemsToMove[itemsToMove.length] = li;
					}

					for (var i = 0; i < itemsToMove.length; i++)
					{
						var li = itemsToMove[i];
						var ul = li.parentNode;

						var parentLI = li.parentNode.parentNode;

						if (parentLI.nextSibling)
							var newItem = parentLI.parentNode.insertBefore(li, parentLI.nextSibling);
						else
							var newItem = source.appendChild(li);

						newItem.childNodes[0].checked = true;

						if (ul.getElementsByTagName("LI").length <= 0)
							ul.parentNode.removeChild(ul);
					}
				}

				function WizSortUp()
				{
					var source = document.getElementById("site-tree");
					if (!source)
						return;

					var items = source.getElementsByTagName("INPUT");

					for (var i = 0; i < items.length; i++)
					{
						if (items[i].checked)
						{
							var li = items[i].parentNode;
							if (li.previousSibling)
							{
								var newItem = li.parentNode.insertBefore(li, li.previousSibling);
								newItem.childNodes[0].checked = true;
							}
						}

					}
				}

				function WizSortDown()
				{
					var source = document.getElementById("site-tree");
					if (!source)
						return;

					var items = source.getElementsByTagName("INPUT");

					for (var i = items.length - 1; i >= 0; i--)
					{
						if (items[i].checked)
						{
							var li = items[i].parentNode;

							if (li.nextSibling)
							{
								if (li.nextSibling.nextSibling)
									var newItem = li.parentNode.insertBefore(li, li.nextSibling.nextSibling);
								else
									var newItem = li.parentNode.appendChild(li);

								newItem.childNodes[0].checked = true;
							}
						}
					}
				}

				function WizAttachEvent()
				{
					var form = document.forms["{$formName}"];
					if (!form)
						return;

					var nextButton = form.elements["{$nextButton}"];
					var prevButton = form.elements["{$prevButton}"];
					if (nextButton)
						nextButton.onclick = WizSaveSiteTree;

					if (prevButton)
						prevButton.onclick = WizSaveSiteTree;

				}

				if (window.addEventListener)
					window.addEventListener("load", WizAttachEvent, false);
				else if (window.attachEvent)
					window.attachEvent("onload", WizAttachEvent);
				else
					setTimeout(WizAttachEvent, 500);

			</script>
TABLE;
	}
}


class CPackageStartInstall extends CWizardStep
{
	var $package;
	var $arSelected;

	public function __construct($package, $arSelected)
	{
		$this->package = $package;
		$this->arSelected = $arSelected;
		parent::__construct();
	}

	public function InitStep()
	{
		$package = $this->package;
		$this->SetTitle(GetMessage("MAIN_WIZARD_START_INSTALL_TITLE"));
		$this->SetNextCaption(GetMessage("MAIN_WIZARD_INSTALL_CAPTION"));
	}

	static function OnPostForm()
	{

	}

	public function ShowStep()
	{
		$package = $this->package;

		if (strlen($this->content) == 0)
			$this->content .= GetMessage("MAIN_WIZARD_START_INSTALL_DESC");

		$siteID = $this->arSelected["siteID"];
		if (isset($siteID) && isset($package->arSites[$siteID]["NAME"]))
			$this->content .= "&nbsp;&nbsp;".GetMessage("MAIN_WIZARD_SITE_TYPE").": <i>".$package->arSites[$siteID]["NAME"]."</i><br />";

		$templateID = $this->arSelected["templateID"];
		if (isset($templateID) && isset($package->arTemplates[$templateID]["NAME"]))
			$this->content .= "&nbsp;&nbsp;".GetMessage("MAIN_WIZARD_SITE_TEMPLATE").": <i>".$package->arTemplates[$templateID]["NAME"]."</i><br />";

		$arServices = $this->arSelected["arServices"];
		$strService = "";
		foreach ($arServices as $serviceID)
		{
			if (array_key_exists($serviceID, $package->arServices) && isset($package->arServices[$serviceID]["NAME"]))
				$strService .= (strlen($strService) > 0 ? ", " : "")."<i>".$package->arServices[$serviceID]["NAME"]."</i>";
		}

		if (strlen($strService) > 0)
			$this->content .= "&nbsp;&nbsp;".GetMessage("MAIN_WIZARD_SITE_SERVICES").": ".$strService;

	}
}

class CPackageInstallSite extends CWizardStep
{
	var $package;
	var $siteID;

	public function __construct($package, $siteID)
	{
		$this->package = $package;
		$this->siteID = $siteID;
		parent::__construct();
		$this->SetAutoSubmit();
		$this->SetTitle(GetMessage("MAIN_WIZARD_RUN_INSTALLATION"));
	}

	public function OnPostForm()
	{
		$package = $this->package;
		$package->__InstallSite($this->siteID);
	}

	public function ShowStep()
	{
		if (strlen($this->content) == 0)
			$this->content = GetMessage("MAIN_WIZARD_SITE_INSTALL");
	}

}

class CPackageInstallTemplate extends CWizardStep
{
	var $package;
	var $templateID;

	public function __construct($package, $templateID)
	{
		$this->package = $package;
		$this->templateID = $templateID;
		parent::__construct();
		$this->SetAutoSubmit();
		$this->SetTitle(GetMessage("MAIN_WIZARD_RUN_INSTALLATION"));
	}

	public function OnPostForm()
	{
		$package = $this->package;
		$package->__InstallTemplate($this->templateID);
	}

	public function ShowStep()
	{
		if (strlen($this->content) == 0)
			$this->content = GetMessage("MAIN_WIZARD_TEMPLATE_INSTALL");
	}
}

class CPackageInstallService extends CWizardStep
{
	var $package;
	var $serviceID;

	public function __construct($package, $serviceID)
	{
		$this->package = $package;
		$this->serviceID = $serviceID;
		parent::__construct();
		$this->SetAutoSubmit();
		$this->SetTitle(GetMessage("MAIN_WIZARD_RUN_INSTALLATION"));
	}

	public function OnPostForm()
	{
		$package = $this->package;
		$package->__InstallService($this->serviceID);
	}

	public function ShowStep()
	{
		$package = $this->package;

		$serviceName = "";
		if (array_key_exists($this->serviceID, $package->arServices) && array_key_exists("NAME", $package->arServices[$this->serviceID]))
			$serviceName = $package->arServices[$this->serviceID]["NAME"];

		if (strlen($this->content) == 0)
			$this->content = GetMessage("MAIN_WIZARD_SERVICE_INSTALL").' "'.htmlspecialcharsEx($serviceName).'" ...';
	}
}

class CPackageInstallStructure extends CWizardStep
{
	static public function __construct()
	{
		parent::__construct();
	}

	public function InitStep()
	{
		$this->SetAutoSubmit();
		$this->SetTitle(GetMessage("MAIN_WIZARD_RUN_INSTALLATION"));
	}

	public function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$package = $wizard->GetPackage();
		$package->__InstallStructure();
	}

	public function ShowStep()
	{
		if (strlen($this->content) == 0)
			$this->content = GetMessage("MAIN_WIZARD_INSTALL_STRUCTURE");
	}
}

class CPackageFinish extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$package = $this->package;
		$this->SetTitle(GetMessage("MAIN_WIZARD_FINISH_TITLE"));
		$this->SetCancelCaption(GetMessage("MAIN_WIZARD_FINISH_CAPTION"));
	}

	static function OnPostForm()
	{
	}

	public function ShowStep()
	{
		if (strlen($this->content) == 0)
			$this->content = GetMessage("MAIN_WIZARD_FINISH_DESC");
	}
}

class CPackageCancel extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$package = $this->package;
		$this->SetTitle(GetMessage("MAIN_WIZARD_CANCEL_TITLE"));
		$this->SetCancelCaption(GetMessage("MAIN_WIZARD_FINISH_CAPTION"));
	}

	static function OnPostForm()
	{
	}

	public function ShowStep()
	{
		if (strlen($this->content) == 0)
			$this->content .= GetMessage("MAIN_WIZARD_CANCEL_DESC");
	}
}

class CPackageError extends CWizardStep
{
	var $package;

	public function __construct($package)
	{
		$this->package = $package;
		parent::__construct();
	}

	public function InitStep()
	{
		$package = $this->package;

		$this->SetCancelStep(BX_WIZARD_CANCEL_ID);
		$this->SetTitle(GetMessage("MAIN_WIZARD_ERROR_STEP_TITLE"));

		foreach ($package->arErrors as $arError)
			$this->SetError($arError[0], $arError[1]);

	}

	function OnPostForm()
	{
	}

	public static function ShowStep()
	{


	}
}

?>