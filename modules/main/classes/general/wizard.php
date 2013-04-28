<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");

IncludeModuleLangFile(__FILE__);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard_util.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard_site.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard_site_steps.php");

class CWizardBase
{
	var $wizardName;
	var $wizardSteps;

	var $currentStepID;
	var $firstStepID;

	//attribute 'name' for buttons
	var $nextButtonID;
	var $prevButtonID;
	var $finishButtonID;
	var $cancelButtonID;

	//attribute 'name' for hidden button vars
	var $nextStepHiddenID;
	var $prevStepHiddenID;
	var $finishStepHiddenID;
	var $cancelStepHiddenID;
	var $currentStepHiddenID;

	var $variablePrefix;
	var $formName;
	var $formActionScript;

	var $returnOutput;
	var $defaultVars;

	var $defaultTemplate;
	var $arTemplates;
	var $useAdminTemplate;

	var $package;

	public static function CWizardBase($wizardName, $package)
	{
		$this->wizardName = $wizardName;
		$this->package = $package;

		$this->wizardSteps = Array();

		$this->currentStepID = null;
		$this->firstStepID = null;

		$this->nextButtonID = "StepNext";
		$this->prevButtonID = "StepPrevious";
		$this->finishButtonID = "StepFinish";
		$this->cancelButtonID = "StepCancel";

		$this->nextStepHiddenID = "NextStepID";
		$this->prevStepHiddenID = "PreviousStepID";
		$this->finishStepHiddenID = "FinishStepID";
		$this->cancelStepHiddenID = "CancelStepID";
		$this->currentStepHiddenID = "CurrentStepID";

		$this->variablePrefix = "__wiz_";
		$this->formName = "__wizard_form";
		$this->formActionScript = $_SERVER["REQUEST_URI"];

		$this->returnOutput = false;
		$this->defaultVars = Array();

		$this->arTemplates = Array();
		$this->defaultTemplate = null;
		$this->useAdminTemplate = true;
	}

	public static function AddStep($obStep, $stepID = null)
	{
		if (!is_subclass_of($obStep, "CWizardStep"))
		{
			$this->__ShowError("Your class ".get_class($obStep)." is not a subclass of CWizardStep<br />");
			return;
		}

		$obStep->_SetWizard($this);
		$obStep->InitStep();
		$ownStepID = $obStep->GetStepID();

		if ($ownStepID == null && $stepID == null)
		{
			$this->__ShowError("Your step (class ".get_class($obStep).") has no ID<br />");
			return;
		}

		$stepID = ($stepID !== null ? $stepID : $ownStepID);
		$obStep->SetStepID($stepID);
		$this->wizardSteps[$stepID] = $obStep;

		if ($this->firstStepID === null)
			$this->SetFirstStep($stepID);
	}

	public static function AddSteps($arClasses)
	{
		if (!is_array($arClasses))
			return false;

		foreach ($arClasses as $className)
		{
			if (!class_exists($className))
				continue;

			$this->AddStep(new $className);
		}
	}

	public static function SetTemplate($obStepTemplate, $stepID = null)
	{
		if (!is_subclass_of($obStepTemplate, "CWizardTemplate"))
			return;

		$obStepTemplate->_SetWizard($this);

		if ($stepID === null)
			$this->defaultTemplate = $obStepTemplate;
		else
			$this->arTemplates[$stepID] = $obStepTemplate;
	}

	public static function DisableAdminTemplate()
	{
		$this->useAdminTemplate = false;
	}

	public static function SetFirstStep($stepID)
	{
		$this->firstStepID = $stepID;
	}

	public static function SetCurrentStep($stepID)
	{
		if (array_key_exists($stepID, $this->wizardSteps))
			$this->currentStepID = $stepID;
	}

	public static function GetCurrentStepID()
	{
		return $this->currentStepID;
	}

	/**
	 * @return CWizardStep
	 */
	public static function GetCurrentStep()
	{
		if (array_key_exists($this->currentStepID, $this->wizardSteps))
			return $this->wizardSteps[$this->currentStepID];

		return null;
	}

	public static function GetWizardSteps()
	{
		return $this->wizardSteps;
	}

	public static function GetVars($useDefault = false)
	{
		$arVars = Array();
		$prefix = $this->GetVarPrefix();
		$prefixLength = strlen($prefix);
		foreach ($_REQUEST as $varName => $varValue)
		{
			if (strncmp($prefix, $varName, $prefixLength) == 0)
				$arVars[substr($varName, $prefixLength)] = $varValue;
		}

		if ($useDefault)
		{
			$arDefault = $this->GetDefaultVars();
			$arVars = array_merge($arDefault, $arVars);
		}

		return $arVars;
	}

	public static function GetVar($varName, $useDefault = false)
	{
		$varName = str_replace("[]", "", $varName);
		$trueName = $this->GetRealName($varName);

		if (array_key_exists($trueName, $_REQUEST))
			return $_REQUEST[$trueName];
		elseif (strpos($trueName, '['))
		{
			$varValue = $this->__GetComplexVar($trueName, $_REQUEST);
			if ($varValue !== null)
				return $varValue;
			elseif ($useDefault)
				return $this->GetDefaultVar($varName);
		}
		elseif ($useDefault)
			return $this->GetDefaultVar($varName);

		return null;
	}

	public static function SetVar($varName, $varValue)
	{
		$trueName = $this->GetRealName($varName);

		if (!strpos($varName, '['))
			$_REQUEST[$trueName] = $varValue;
		else
			$this->__SetComplexVar($trueName, $varValue, $_REQUEST);
	}

	public static function UnSetVar($varName)
	{
		$trueName = $this->GetRealName($varName);

		if (!strpos($varName, '['))
			unset($_REQUEST[$trueName]);
		else
			$this->__UnSetComplexVar($trueName, $_REQUEST);
	}

	function __GetComplexVar($varName, &$arVars)
	{
		$tokens = explode("[", str_replace("]", "", $varName));
		$arValues =& $arVars;
		do
		{
			$token = array_shift($tokens);
			if (!isset($arValues[$token]))
				return null;
			$arValues =& $arValues[$token];
		} while (!empty($tokens));

		return $arValues;
	}

	function __SetComplexVar($varName, $value, &$arVars)
	{
		$tokens = explode("[", str_replace("]", "", $varName));
		$arValues =& $arVars;
		do
		{
			$token = array_shift($tokens);
			if (!isset($arValues[$token]))
				$arValues[$token] = Array();
			$arValues =& $arValues[$token];
		} while (count($tokens) > 1);
		$arValues[$tokens[0]] = $value;
	}

	function __UnSetComplexVar($varName, &$arVars)
	{
		$tokens = explode("[", str_replace("]", "", $varName));
		$arValues =& $arVars;
		do
		{
			$token = array_shift($tokens);
			if (!isset($arValues[$token]))
				return null;
			$arValues =& $arValues[$token];
		} while (count($tokens) > 1);
		unset($arValues[array_pop($tokens)]);
	}

	public static function GetRealName($varName)
	{
		return $this->GetVarPrefix().$varName;
	}

	public static function GetVarPrefix()
	{
		return $this->variablePrefix;
	}

	public static function SetVarPrefix($varPrefix)
	{
		$this->variablePrefix = $varPrefix;
	}

	public static function SetDefaultVar($varName, $varValue)
	{
		$varName = str_replace("[]", "", $varName);

		if (!strpos($varName, '['))
			$this->defaultVars[$varName] = $varValue;
		else
			$this->__SetComplexVar($varName, $varValue, $this->defaultVars);
	}

	public static function SetDefaultVars($arVars)
	{
		if (!is_array($arVars))
			return;

		foreach ($arVars as $varName => $varValue)
			$this->SetDefaultVar($varName, $varValue);
	}

	public static function GetDefaultVar($varName)
	{
		$varName = str_replace("[]", "", $varName);

		if (array_key_exists($varName, $this->defaultVars))
			return $this->defaultVars[$varName];
		elseif (strpos($varName, '['))
			return $this->__GetComplexVar($varName, $this->defaultVars);

		return null;
	}

	public static function GetDefaultVars()
	{
		return $this->defaultVars;
	}

	public static function GetWizardName()
	{
		return $this->wizardName;
	}

	public static function SetFormName($formName)
	{
		$this->formName = $formName;
	}

	public static function GetFormName()
	{
		return $this->formName;
	}

	public static function SetFormActionScript($actionScript)
	{
		$this->formActionScript = $actionScript;
	}

	public static function GetFormActionScript()
	{
		return $this->formActionScript;
	}

	public static function IsNextButtonClick()
	{
		return ( isset($_REQUEST[$this->nextButtonID]) && isset($_REQUEST[$this->nextStepHiddenID]) );
	}

	public static function IsPrevButtonClick()
	{
		return ( isset($_REQUEST[$this->prevButtonID]) && isset($_REQUEST[$this->prevStepHiddenID]) );
	}

	public static function IsFinishButtonClick()
	{
		return ( isset($_REQUEST[$this->finishButtonID]) && isset($_REQUEST[$this->finishStepHiddenID]) );
	}

	public static function IsCancelButtonClick()
	{
		return ( isset($_REQUEST[$this->cancelButtonID]) && isset($_REQUEST[$this->cancelStepHiddenID]) );
	}

	public static function SetNextButtonID($buttonID)
	{
		$this->nextButtonID = $buttonID;
	}

	public static function GetNextButtonID()
	{
		return $this->nextButtonID;
	}

	public static function GetNextStepID()
	{
		if (isset($_REQUEST[$this->nextStepHiddenID]))
			return $_REQUEST[$this->nextStepHiddenID];

		return null;
	}

	public static function SetPrevButtonID($buttonID)
	{
		$this->prevButtonID = $buttonID;
	}

	public static function GetPrevButtonID()
	{
		return $this->prevButtonID;
	}

	public static function GetPrevStepID()
	{
		if (isset($_REQUEST[$this->prevStepHiddenID]))
			return $_REQUEST[$this->prevStepHiddenID];

		return null;
	}

	public static function SetFinishButtonID($buttonID)
	{
		$this->finishButtonID = $buttonID;
	}

	public static function GetFinishButtonID()
	{
		return $this->finishButtonID;
	}

	public static function GetFinishStepID()
	{
		if (isset($_REQUEST[$this->finishStepHiddenID]))
			return $_REQUEST[$this->finishStepHiddenID];

		return null;
	}

	public static function SetCancelButtonID($buttonID)
	{
		$this->cancelButtonID = $buttonID;
	}

	public static function GetCancelButtonID()
	{
		return $this->cancelButtonID;
	}

	public static function GetCancelStepID()
	{
		if (isset($_REQUEST[$this->cancelStepHiddenID]))
			return $_REQUEST[$this->cancelStepHiddenID];

		return null;
	}

	public static function SetReturnOutput($mode = true)
	{
		$this->returnOutput = (bool)$mode;
	}

	public static function GetPackage()
	{
		return $this->package;
	}

	public static function Display()
	{
		$currentStep = &$this->currentStepID;

		//What button has just been pressed
		if ($this->IsPrevButtonClick())
			$currentStep = $this->GetPrevStepID();
		elseif ($this->IsNextButtonClick())
			$currentStep = $this->GetNextStepID();
		elseif ($this->IsCancelButtonClick())
			$currentStep = $this->GetCancelStepID();
		elseif ($this->IsFinishButtonClick())
			$currentStep = $this->GetFinishStepID();

		//Execute current step action
		if ( isset($_REQUEST[$this->currentStepHiddenID]) && isset($this->wizardSteps[$_REQUEST[$this->currentStepHiddenID]]) )
		{
			$oCurrentStep = $this->wizardSteps[$_REQUEST[$this->currentStepHiddenID]];
			if (method_exists($oCurrentStep, "OnPostForm"))
			{
				$oCurrentStep->OnPostForm();
				if (count($oCurrentStep->stepErrors)>0)
					$currentStep = $_REQUEST[$this->currentStepHiddenID];
			}
		}

		//If step is not found, show a first step
		if (!isset($this->wizardSteps[$currentStep]))
		{
			if (isset($this->wizardSteps[$this->firstStepID]))
				$currentStep = $this->firstStepID;
			else
			{
				$this->__ShowError("Wizard has no any step");
				return;
			}
		}

		return $this->_DisplayStep();
	}

	function _DisplayStep()
	{
		$oStep = $this->GetCurrentStep();
		$oStep->ShowStep();

		$formStart = '<form action="'.htmlspecialcharsbx($this->formActionScript).'" enctype="multipart/form-data" method="post" name="'.htmlspecialcharsbx($this->formName).'" id="'.htmlspecialcharsbx($this->formName).'">';
		$formStart .= '<input type="hidden" name="'.htmlspecialcharsbx($this->currentStepHiddenID).'" value="'.htmlspecialcharsbx($this->currentStepID).'">';
		$formStart .= $this->__DisplayHiddenVars($this->GetVars(), $oStep);

		/*
		if ($oStep->prevStepID !== null)
			$strResult .= '<input type="submit" name="'.$this->prevButtonID.'" value="'.$oStep->prevCaption.'">
						<input type="hidden" name="'.$this->prevStepHiddenID.'" value="'.$oStep->prevStepID.'">';

		if ($oStep->nextStepID !== null)
			$strResult .= '<input type="submit" name="'.$this->nextButtonID.'" value="'.$oStep->nextCaption.'">
						<input type="hidden" name="'.$this->nextStepHiddenID.'" value="'.$oStep->nextStepID.'">';

		if ($oStep->finishStepID !== null)
			$strResult .= '<input type="submit" name="'.$this->finishButtonID.'" value="'.$oStep->finishCaption.'">
						<input type="hidden" name="'.$this->finishStepHiddenID.'" value="'.$oStep->finishStepID.'">';

		if ($oStep->cancelStepID !== null)
			$strResult .= '&nbsp;&nbsp;<input type="submit" name="'.$this->cancelButtonID.'" value="'.$oStep->cancelCaption.'">
						<input type="hidden" name="'.$this->cancelStepHiddenID.'" value="'.$oStep->cancelStepID.'">';
		*/

		$formEnd = "</form>";

		$stepLayout = $this->__GetStepLayout();
		$stepLayout = $stepLayout->GetLayout();

		$buttonsHtml = "";
		$prevButtonHtml = "";
		if ($oStep->prevStepID !== null)
		{
			$formStart .= '<input type="hidden" name="'.$this->prevStepHiddenID.'" value="'.$oStep->prevStepID.'">';
			$prevButtonHtml = '<input type="submit" class="wizard-prev-button" name="'.$this->prevButtonID.'" value="'.$oStep->prevCaption.'">';
			$buttonsHtml .= $prevButtonHtml;
		}

		$nextButtonHtml = "";
		if ($oStep->nextStepID !== null)
		{
			$formStart .= '<input type="hidden" name="'.$this->nextStepHiddenID.'" value="'.$oStep->nextStepID.'">';
			$nextButtonHtml = '<input type="submit" class="wizard-next-button" name="'.$this->nextButtonID.'" value="'.$oStep->nextCaption.'">';
			$buttonsHtml .= (strlen($buttonsHtml)>0 ? "&nbsp;" : "").$nextButtonHtml;
		}

		$finishButtonHtml = "";
		if ($oStep->finishStepID !== null)
		{
			$formStart .= '<input type="hidden" name="'.$this->finishStepHiddenID.'" value="'.$oStep->finishStepID.'">';
			$finishButtonHtml = '<input type="submit" class="wizard-finish-button" name="'.$this->finishButtonID.'" value="'.$oStep->finishCaption.'">';
			$buttonsHtml .= (strlen($buttonsHtml)>0 ? "&nbsp;" : "").$finishButtonHtml;
		}

		$cancelButtonHtml = "";
		if ($oStep->cancelStepID !== null)
		{
			$formStart .= '<input type="hidden" name="'.$this->cancelStepHiddenID.'" value="'.$oStep->cancelStepID.'">';
			$cancelButtonHtml = '<input type="submit" class="wizard-cancel-button" name="'.$this->cancelButtonID.'" value="'.$oStep->cancelCaption.'">';
			$buttonsHtml .= (strlen($buttonsHtml)>0 ? "&nbsp;&nbsp;&nbsp;" : "").$cancelButtonHtml;
		}

		$output = str_replace("{#FORM_START#}", $formStart, $stepLayout);
		$output = str_replace("{#FORM_END#}", $formEnd, $output);
		$output = str_replace("{#CONTENT#}", $oStep->content, $output);

		//$output = str_replace("{#BUTTONS#}", $this->__DisplayButtons(), $output);
		$output = str_replace("{#BUTTONS#}", $buttonsHtml, $output);
		$output = str_replace("{#BUTTON_PREVIOUS#}", $prevButtonHtml, $output);
		$output = str_replace("{#BUTTON_NEXT#}", $nextButtonHtml, $output);
		$output = str_replace("{#BUTTON_CANCEL#}", $finishButtonHtml, $output);
		$output = str_replace("{#BUTTON_FINISH#}", $cancelButtonHtml, $output);

		if ($this->returnOutput)
			return $output;
		else
			echo $output;
	}

	function __GetStepLayout()
	{
		if (defined("ADMIN_SECTION") && ADMIN_SECTION === true && $this->useAdminTemplate === true)
		{
			$template = new CWizardAdminTemplate;
			$template->_SetWizard($this);
			return $template;
		}
		elseif (isset($this->arTemplates[$this->currentStepID]))
		{
			return $this->arTemplates[$this->currentStepID];
		}
		elseif (is_object($this->defaultTemplate))
		{
			return $this->defaultTemplate;
		}
		else
		{
			$template = new CWizardTemplate;
			$template->_SetWizard($this);
			return $template;
		}
	}

	function __DisplayHiddenVars($arVars, $oStep, $concatString = null)
	{
		$strReturn = "";

		foreach ($arVars as $varName => $varValue)
		{
			if ($concatString !== null)
				$varName = $concatString."[".$varName."]";

			if ($oStep->DisplayVarExists($varName))
				continue;

			if (is_array($varValue))
				$strReturn .= $this->__DisplayHiddenVars($varValue, $oStep, $varName);
			else
				$strReturn .= '<input type="hidden" name="'.htmlspecialcharsbx($this->GetVarPrefix().$varName).'" value="'.htmlspecialcharsEx($varValue).'">
			';
		}

		return $strReturn;
	}

	function __ShowError($errorMessage)
	{
		if (strlen($errorMessage) > 0)
			echo '<span style="color:#FF0000">'.$errorMessage.'</span>';
	}

	/* Old  compatible Methods*/
	public static function GetID()
	{
		if ($this->package === null)
			return "";
		return $this->package->GetID();
	}

	public static function GetPath()
	{
		if ($this->package === null)
			return "";
		return $this->package->GetPath();
	}

	public static function GetSiteTemplateID()
	{
		if ($this->package === null)
			return null;

		return $this->package->GetSiteTemplateID();
	}

	public static function GetSiteGroupID()
	{
		if ($this->package === null)
			return null;

		return $this->package->GetSiteGroupID();
	}

	public static function GetSiteID()
	{
		if ($this->package === null)
			return null;

		return $this->package->GetSiteID();
	}

	public static function GetSiteServiceID()
	{
		if ($this->package === null)
			return null;

		return $this->package->GetSiteServiceID();
	}
	/* Old compatible methods */

}

class CWizardStep
{
	var $stepTitle;
	var $stepSubTitle;
	var $stepID;

	var $nextStepID;
	var $prevStepID;
	var $finishStepID;
	var $cancelStepID;

	var $nextCaption;
	var $prevCaption;
	var $finishCaption;
	var $cancelCaption;

	var $displayVars;
	var $stepErrors;

	var $wizard; // reference to wizard object
	var $content;

	var $autoSubmit;

	public static function CWizardStep()
	{
		$this->stepTitle = "";
		$this->stepSubTitle = "";
		$this->stepID = null;

		$this->prevCaption = GetMessage("MAIN_WIZARD_PREV_CAPTION");
		$this->nextCaption = GetMessage("MAIN_WIZARD_NEXT_CAPTION");
		$this->finishCaption = GetMessage("MAIN_WIZARD_FINISH_CAPTION");
		$this->cancelCaption = GetMessage("MAIN_WIZARD_CANCEL_CAPTION");

		$this->nextStepID = null;
		$this->prevStepID = null;
		$this->finishStepID = null;
		$this->cancelStepID = null;

		$this->wizard = null;
		$this->displayVars = Array();
		$this->stepErrors = Array();

		$this->content = "";

		$this->autoSubmit = false;
	}

	//Step initialization
	public static function InitStep()
	{
		//should be overloaded
	}

	//Step action
	public static function OnPostForm()
	{
		//should be overloaded
	}

	//Step output
	public static function ShowStep()
	{
		//should be overloaded
	}

	public static function SetTitle($title)
	{
		$this->stepTitle = $title;
	}

	public static function GetTitle()
	{
		return $this->stepTitle;
	}

	public static function SetSubTitle($stepSubTitle)
	{
		$this->stepSubTitle = $stepSubTitle;
	}

	public static function GetSubTitle()
	{
		return $this->stepSubTitle;
	}

	public static function SetStepID($stepID)
	{
		$this->stepID = $stepID;
	}

	public static function GetStepID()
	{
		return $this->stepID;
	}

	public static function SetNextStep($stepID)
	{
		$this->nextStepID = $stepID;
	}

	public static function GetNextStepID()
	{
		return $this->nextStepID;
	}

	public static function SetNextCaption($caption)
	{
		$this->nextCaption = $caption;
	}

	public static function GetNextCaption()
	{
		return $this->nextCaption;
	}

	public static function SetPrevStep($stepID)
	{
		$this->prevStepID = $stepID;
	}

	public static function GetPrevStepID()
	{
		return $this->prevStepID;
	}

	public static function SetPrevCaption($caption)
	{
		$this->prevCaption = $caption;
	}

	public static function GetPrevCaption()
	{
		return $this->prevCaption;
	}

	public static function SetFinishStep($stepID)
	{
		$this->finishStepID = $stepID;
	}

	public static function GetFinishStepID()
	{
		return $this->finishStepID;
	}

	public static function SetFinishCaption($caption)
	{
		$this->finishCaption = $caption;
	}

	public static function GetFinishCaption()
	{
		return $this->finishCaption;
	}

	public static function SetCancelStep($stepID)
	{
		$this->cancelStepID = $stepID;
	}

	public static function GetCancelStepID()
	{
		return $this->cancelStepID;
	}

	public static function SetCancelCaption($caption)
	{
		$this->cancelCaption = $caption;
	}

	public static function GetCancelCaption()
	{
		return $this->cancelCaption;
	}

	public static function SetDisplayVars($arVars)
	{
		if (!is_array($arVars))
			return;

		$wizard = $this->GetWizard();
		foreach ($arVars as $varName)
		{
			$varName = str_replace("[]", "", $varName);
			if (!in_array($varName, $this->displayVars))
				$this->displayVars[] = $varName;
		}
	}

	public static function DisplayVarExists($varName)
	{
		$varName = str_replace("[]", "", $varName);

		if (in_array($varName, $this->displayVars, true))
			return true;
		return null;
	}

	public static function GetDisplayVars()
	{
		return $this->displayVars;
	}

	public static function SetError($strError, $id = false)
	{
		$this->stepErrors[] = Array($strError, $id);
	}

	public static function GetErrors()
	{
		return $this->stepErrors;
	}

	//Text and textarea controls
	public static function ShowInputField($type, $name, $arAttributes = Array())
	{
		$strReturn = "";
		$wizard = $this->GetWizard();
		$prefixName = $wizard->GetRealName($name);
		$value = ($wizard->GetVar($name) ? $wizard->GetVar($name) : $wizard->GetDefaultVar($name));

		$this->SetDisplayVars(Array($name));

		switch ($type)
		{
			case "text":
				if (!isset($arAttributes["size"]))
					$arAttributes["size"] = 10;
				$strReturn .= '<input type="text" name="'.htmlspecialcharsbx($prefixName).'" value="'.htmlspecialcharsEx($value).'"'.$this->_ShowAttributes($arAttributes).' />';
			break;

			case "password":
				if (!isset($arAttributes["size"]))
					$arAttributes["size"] = 10;
				$strReturn .= '<input type="password" name="'.htmlspecialcharsbx($prefixName).'" value="'.htmlspecialcharsEx($value).'"'.$this->_ShowAttributes($arAttributes).' />';
			break;

			case "textarea":
				$strReturn .= '<textarea name="'.htmlspecialcharsbx($prefixName).'"'.$this->_ShowAttributes($arAttributes).'>'.htmlspecialcharsEx($value).'</textarea>';
			break;
		}

		return $strReturn;
	}

	//Checkbox control
	public static function ShowCheckboxField($name, $value, $arAttributes = Array())
	{
		$this->SetDisplayVars(Array($name));
		$wizard = $this->GetWizard();

		$valueFromPost = $wizard->GetVar($name);
		if ($valueFromPost !== null && !is_array($valueFromPost))
			$valueFromPost = Array($valueFromPost);

		$valueFromDefault = $wizard->GetDefaultVar($name);
		if ($valueFromDefault !== null && !is_array($valueFromDefault))
			$valueFromDefault = Array($valueFromDefault);

		$checked = (
			(($valueFromPost !== null && in_array($value, $valueFromPost)) ||
			($valueFromDefault !== null && $valueFromPost == "" && in_array($value, $valueFromDefault)))
				&&
			($arAttributes["checked"] !== false)
		);

		static $arViewedField = Array();
		$viewName = str_replace("[]", "", $name);
		$strReturn = "";

		if (!in_array($viewName, $arViewedField) /*&& !$valueWasViewed*/)
		{
			$arViewedField[] = $viewName;
			$strReturn .= '<input name="'.htmlspecialcharsbx($wizard->GetRealName($viewName)).'" value="" type="hidden" />';
		}

		$prefixName = $wizard->GetRealName($name);
		$strReturn .= '<input name="'.htmlspecialcharsbx($prefixName).'" '.($checked ?"checked=\"checked\" ":"").'type="checkbox" value="'.htmlspecialcharsEx($value).'"'.$this->_ShowAttributes($arAttributes).' />';

		return $strReturn;

	}

	//Radio button control
	public static function ShowRadioField($name, $value, $arAttributes = Array())
	{
		$this->SetDisplayVars(Array($name));
		$wizard = $this->GetWizard();

		$valueFromPost = $wizard->GetVar($name);
		if ($valueFromPost !== null && !is_array($valueFromPost))
			$valueFromPost = Array($valueFromPost);

		$valueFromDefault = $wizard->GetDefaultVar($name);
		if ($valueFromDefault !== null && !is_array($valueFromDefault))
			$valueFromDefault = Array($valueFromDefault);

		static $arCheckedField = Array();
		$checked = false;
		$checkName = str_replace("[]", "", $name);

		if (!in_array($checkName, $arCheckedField))
		{
			$checked = (
				($valueFromPost !== null && in_array($value, $valueFromPost)) ||
				($valueFromDefault !== null && $valueFromPost === null && in_array($value, $valueFromDefault))
			);

			if ($checked)
				$arCheckedField[] = $checkName;
		}

		$prefixName = $wizard->GetRealName($name);
		return '<input name="'.htmlspecialcharsbx($prefixName).'" type="radio" '.($checked ?"checked=\"checked\" ":"").'value="'.htmlspecialcharsEx($value).'"'.$this->_ShowAttributes($arAttributes).' />';
	}

	//Dropdown and multiple controls
	public static function ShowSelectField($name, $arValues = Array(), $arAttributes = Array())
	{
		$wizard = $this->GetWizard();
		$this->SetDisplayVars(Array($name));

		$varValue = $wizard->GetVar($name);
		$selectedValues = (
			$varValue !== null && $varValue != "" ?
			$varValue :
			(
				$varValue === "" ?
				Array() :
				$wizard->GetDefaultVar($name)
			)
		);

		if (!is_array($selectedValues))
			$selectedValues = Array($selectedValues);

		$viewName = $wizard->GetRealName(str_replace("[]", "", $name));
		$strReturn = '<input name="'.htmlspecialcharsbx($viewName).'" value="" type="hidden" />';

		$prefixName = $wizard->GetRealName($name);
		$strReturn .= '<select name="'.htmlspecialcharsbx($prefixName).'"'.$this->_ShowAttributes($arAttributes).'>';

		foreach ($arValues as $optionValue => $optionName)
			$strReturn .= '<option value="'.htmlspecialcharsEx($optionValue).'"'.(in_array($optionValue, $selectedValues) ? " selected=\"selected\"" :"").'>'.htmlspecialcharsEx($optionName).'</option>
			';

		$strReturn .= '</select>';

		return $strReturn;
	}

	//Hidden control
	public static function ShowHiddenField($name, $value, $arAttributes = Array())
	{
		$wizard = $this->GetWizard();

		$this->SetDisplayVars(Array($name));
		$trueName = $wizard->GetRealName($name);

		$strReturn = '<input type="hidden" name="'.htmlspecialcharsbx($trueName).'" value="'.htmlspecialcharsEx($value).'"'.$this->_ShowAttributes($arAttributes).' />';

		return $strReturn;
	}

	//File control
	public static function ShowFileField($name, $arAttributes = Array())
	{
		$wizard = $this->GetWizard();
		$strReturn = "";

		if (array_key_exists("max_file_size", $arAttributes))
		{
			$strReturn .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.intval($arAttributes["max_file_size"]).'" />';
			unset($arAttributes["max_file_size"]);
		}

		$strReturn .= '<input type="file" name="'.htmlspecialcharsbx($wizard->GetRealName($name."_new")).'"'.$this->_ShowAttributes($arAttributes).' />';

		$fileID = intval($wizard->GetVar($name));
		if ($fileID > 0)
		{
			$obFile = CFile::GetByID($fileID);
			if ($arFile = $obFile->Fetch())
			{
				$deleteName = $wizard->GetRealName($name."_del");
				$oldName = $wizard->GetRealName($name."_old");

				$show_file_info = (isset($arAttributes["show_file_info"]) && $arAttributes["show_file_info"] == "N" ? false : true);

				if ($show_file_info)
				{
					$strReturn .= "<br />&nbsp;".GetMessage("MAIN_WIZARD_FILE_NAME").": ".htmlspecialcharsEx($arFile["ORIGINAL_NAME"]);

					if ($arFile["HEIGHT"] > 0 && $arFile["WIDTH"])
					{
						$strReturn .= "<br />&nbsp;".GetMessage("MAIN_WIZARD_FILE_WIDTH").": ".intval($arFile["WIDTH"]);
						$strReturn .= "<br />&nbsp;".GetMessage("MAIN_WIZARD_FILE_HEIGHT").": ".intval($arFile["HEIGHT"]);
					}

					$sizes = array("b", "Kb", "Mb", "Gb");
					$pos = 0;
					$size = $arFile["FILE_SIZE"];
					while($size >= 1024)
					{
						$size /= 1024;
						$pos++;
					}
					$strReturn .= "<br />&nbsp;".GetMessage("MAIN_WIZARD_FILE_SIZE").": ".round($size, 2)." ".$sizes[$pos];
				}

				$strReturn .= '<br />';
				$strReturn .= '<input type="checkbox" name="'.$deleteName.'" value="Y" id="'.$deleteName.'" />';
				$strReturn .= '<label for="'.$deleteName.'">'.GetMessage("MAIN_WIZARD_FILE_DELETE").'</label>';
			}
		}

		return $strReturn;

	}

	public static function SaveFile($name, $arRestriction = Array())
	{
		$wizard = $this->GetWizard();
		$deleteFile = $wizard->GetVar($name."_del");
		$wizard->UnSetVar($name."_del");
		$oldFileID = $wizard->GetVar($name);
		$fileNew = $wizard->GetRealName($name."_new");

		if (!array_key_exists($fileNew, $_FILES) || (strlen($_FILES[$fileNew]["name"]) <= 0 && $deleteFile === null))
			return;

		if (strlen($_FILES[$fileNew]["tmp_name"]) <= 0 && $deleteFile === null)
		{
			$this->SetError(GetMessage("MAIN_WIZARD_FILE_UPLOAD_ERROR"), $name."_new");
			return;
		}

		$arFile = $_FILES[$fileNew] + Array(
			"del" => ($deleteFile == "Y" ? "Y" : ""),
			"old_file" => (intval($oldFileID) > 0 ? intval($oldFileID): 0 ),
			"MODULE_ID" => "tmp_wizard"
		);

		$max_file_size = (array_key_exists("max_file_size", $arRestriction) ? intval($arRestriction["max_file_size"]) : 0);
		$max_width = (array_key_exists("max_width", $arRestriction) ? intval($arRestriction["max_width"]) : 0);
		$max_height = (array_key_exists("max_height", $arRestriction) ? intval($arRestriction["max_height"]) : 0);
		$extensions = (array_key_exists("extensions", $arRestriction) && strlen($arRestriction["extensions"]) > 0 ? trim($arRestriction["extensions"]) : false);
		$make_preview = (array_key_exists("make_preview", $arRestriction) && $arRestriction["make_preview"] == "Y" ? true : false);

		$error = CFile::CheckFile($arFile, $max_file_size, false, $extensions);
		if (strlen($error)>0)
		{
			$this->SetError($error, $name."_new");
			return;
		}

		if ($make_preview && $max_width > 0 && $max_height > 0)
		{
			list($sourceWidth, $sourceHeight, $type, $attr) = CFile::GetImageSize($arFile["tmp_name"]);

			if ($sourceWidth > $max_width || $sourceHeight > $max_height)
			{
				$success = CWizardUtil::CreateThumbnail($arFile["tmp_name"], $arFile["tmp_name"], $max_width, $max_height);
				if ($success)
					$arFile["size"] = @filesize($arFile["tmp_name"]);
			}
		}
		elseif ($max_width > 0 || $max_height > 0)
		{
			$error = CFile::CheckImageFile($arFile, $max_file_size, $max_width, $max_height);
			if (strlen($error)>0)
			{
				$this->SetError($error, $name."_new");
				return;
			}
		}

		$fileID = (int)CFile::SaveFile($arFile, "tmp");
		if ($fileID > 0)
			$wizard->SetVar($name, $fileID);
		else
			$wizard->UnSetVar($name);

		return $fileID;
	}

	function _ShowAttributes($arAttributes)
	{
		if (!is_array($arAttributes))
			return "";

		$strReturn = "";
		foreach ($arAttributes as $name => $value)
			$strReturn .= ' '.htmlspecialcharsbx($name).'="'.htmlspecialcharsEx($value).'"';

		return $strReturn;
	}

	/**
	 * Returns wizard reference
	 *
	 * @return CWizardBase
	 */
	public static function GetWizard()
	{
		return $this->wizard;
	}

	function _SetWizard($wizard)
	{
		$this->wizard = $wizard;
	}

	public static function SetAutoSubmit($bool = true)
	{
		$this->autoSubmit = (bool)$bool;
	}

	public static function IsAutoSubmit()
	{
		return (bool)$this->autoSubmit;
	}

}

class CWizardTemplate
{
	var $wizard;

	public static function GetLayout()
	{
		$wizard = $this->GetWizard();
		$obStep = $wizard->GetCurrentStep();

		$wizardName = htmlspecialcharsEx($wizard->GetWizardName());
		$formName = htmlspecialcharsbx($wizard->GetFormName());

		$nextButtonID = htmlspecialcharsbx($wizard->GetNextButtonID());
		$prevButtonID = htmlspecialcharsbx($wizard->GetPrevButtonID());
		$cancelButtonID = htmlspecialcharsbx($wizard->GetCancelButtonID());
		$finishButtonID = htmlspecialcharsbx($wizard->GetFinishButtonID());

		if (isset($GLOBALS["APPLICATION"]) && is_object($GLOBALS["APPLICATION"]))
		{
			$GLOBALS["APPLICATION"]->AddHeadString($styles);
			IncludeAJAX();
		}
		//IncludeAJAX();

		$styles = <<<STYLES
<style type="text/css">
			/*Data table*/
			table.wizard-data-table
			{
				border:1px solid #7d7d7d;
				border-collapse:collapse;
			}

			/*Any cell*/
			table.wizard-data-table td
			{
				border:1px solid #7d7d7d;
				background-color:#FFFFFF;
				padding:3px 5px;
			}

			/*Head cell*/
			table.wizard-data-table thead td, table.wizard-data-table th
			{
				background-color:#F2F2EA;
				font-weight:normal;
				background-image:none;
				border:1px solid #7d7d7d;
				padding:4px;
			}

			/*Body cell*/
			table.wizard-data-table tbody td
			{
				background-color:#FFF;
				background-image:none;
			}

			/*Foot cell*/
			table.wizard-data-table tfoot td
			{
				background-color:#fff;
				padding:4px;
			}

			.wizard-note-box
			{
				background:#EAE9E4;
				padding:7px;
				border:1px solid #797672;
			}

			.wizard-required
			{
				color:red;
			}
</style>
STYLES;
		//$GLOBALS["APPLICATION"]->AddHeadString($styles);

		$arErrors = $obStep->GetErrors();
		$strError = "";
		if (count($arErrors) > 0)
		{
			foreach ($arErrors as $arError)
				$strError .= $arError[0]."<br />";

			$strError = '<tr><td style="padding-top: 10px; padding-left: 20px; color:red;">'.$strError.'</td></tr>';
		}

		$stepTitle = $obStep->GetTitle();
		$stepSubTitle = $obStep->GetSubTitle();

		$autoSubmit = "";
		if ($obStep->IsAutoSubmit())
			$autoSubmit = 'setTimeout("WizardAutoSubmit();", 500);';

		$BX_ROOT = BX_ROOT;

		$alertText = GetMessage("MAIN_WIZARD_WANT_TO_CANCEL");
		$loadingText = GetMessage("MAIN_WIZARD_WAIT_WINDOW_TEXT");

		return <<<HTML

{#FORM_START#}
<table style="border:2px outset #D4D0C8; background-color: #D4D0C8;" border="0" cellpadding="0" cellspacing="0" height="370" width="100%">
	<tr>
		<td style="background-color: #142F73" height="1"><span style="color:white; font-weight:bold; text-align:left; padding-left: 2px;">{$wizardName}</span></td>
	</tr>

	<tr>
		<td style="height: 60px; border-bottom:2px groove  #aca899; background-color: #ffffff; padding: 8px;" valign="top">
			<div style="padding-top: 5px; padding-left: 20px;"><b>{$stepTitle}</b></div>
			<div style="padding-left: 40px;">{$stepSubTitle}</div>
		</td>
	</tr>

	{$strError}

	<tr>
		<td style="padding: 20px; padding-left: 28px;padding-right: 28px;" valign="top" id="wizard-content-area" height="100%">{#CONTENT#}</td>
	</tr>

	<tr>
		<td style="height: 40px; border-top:2px groove #ffffff; padding-right: 15px;" align="right">{#BUTTONS#}</td>
	</tr>
</table>
{#FORM_END#}

<script type="text/javascript">

function WizardAutoSubmit()
{
	var nextButton = document.forms["{$formName}"].elements["{$nextButtonID}"];
	if (nextButton)
	{
		WaitWindow.Show();

		nextButton.click();
		nextButton.disabled=true;
	}
}

function WizardOnLoad()
{
	{$autoSubmit}

	var cancelButton = document.forms["{$formName}"].elements["{$cancelButtonID}"];
	var nextButton = document.forms["{$formName}"].elements["{$nextButtonID}"];
	var prevButton = document.forms["{$formName}"].elements["{$prevButtonID}"];
	var finishButton = document.forms["{$formName}"].elements["{$finishButtonID}"];

	if (cancelButton && !nextButton && !prevButton && !finishButton)
		cancelButton.onclick = CloseWindow;
	else if(cancelButton)
		cancelButton.onclick = ConfirmCancel;
}

function CloseWindow()
{
	window.location = '/';
	return false;
}

function ConfirmCancel()
{
	return (confirm("{$alertText}"));
}

function CWaitWindow()
{
	this.Show = function()
	{
		try
		{
			var oDiv = document.createElement("DIV");
			oDiv.id = "__bx_wait_window";
			oDiv.style.width = "170px";
			oDiv.style.border = "1px solid #EACB6B";
			oDiv.style.textAlign = "center";
			oDiv.style.backgroundColor = "#FCF7D1";
			oDiv.style.position = "relative";
			oDiv.style.padding = "10px";
			oDiv.style.backgroundImage = "url({$BX_ROOT}/themes/.default/images/wait.gif)";
			oDiv.style.backgroundPosition = "10px center";
			oDiv.style.backgroundRepeat = "no-repeat";
			oDiv.style.left = "35%";
			oDiv.style.top = "50%";
			oDiv.style.zIndex = "3000";
			oDiv.innerHTML = "{$loadingText}";
			document.getElementById("wizard-content-area").appendChild(oDiv);
		}
		catch(e){}
	}

	this.Hide = function()
	{
		try
		{
			var oDiv = document.getElementById("__bx_wait_window");
			oDiv.parentNode.removeChild(oDiv);
			oDiv = null;
		}catch(e){}
	}
}

var WaitWindow = new CWaitWindow();
WizardOnLoad();

</script>

HTML;
	}

	/**
	 * Returns wizard reference
	 *
	 * @return CWizardBase
	 */
	public static function GetWizard()
	{
		return $this->wizard;
	}

	function _SetWizard($wizard)
	{
		$this->wizard = $wizard;
	}

}


class CWizardAdminTemplate extends CWizardTemplate
{

	public static function GetLayout()
	{
		$wizard = $this->GetWizard();

		$formName = htmlspecialcharsbx($wizard->GetFormName());

		$adminScript = CAdminPage::ShowScript();

		$charset = LANG_CHARSET;
		$wizardName = htmlspecialcharsEx($wizard->GetWizardName());

		$nextButtonID = htmlspecialcharsbx($wizard->GetNextButtonID());
		$prevButtonID = htmlspecialcharsbx($wizard->GetPrevButtonID());
		$cancelButtonID = htmlspecialcharsbx($wizard->GetCancelButtonID());
		$finishButtonID = htmlspecialcharsbx($wizard->GetFinishButtonID());

		IncludeAJAX();
		$ajaxScripts = $GLOBALS["APPLICATION"]->GetHeadStrings();
		$ajaxScripts .= $GLOBALS["APPLICATION"]->GetHeadScripts();

		$obStep = $wizard->GetCurrentStep();
		$arErrors = $obStep->GetErrors();
		$strError = $strJsError = "";
		if (count($arErrors) > 0)
		{
			foreach ($arErrors as $arError)
			{
				$strError .= $arError[0]."<br />";

				if ($arError[1] !== false)
					$strJsError .= ($strJsError <> ""? ", ":"")."{'name':'".CUtil::addslashes($wizard->GetRealName($arError[1]))."', 'title':'".CUtil::addslashes(htmlspecialcharsback($arError[0]))."'}";
			}

			if (strlen($strError) > 0)
				$strError = '<div id="step_error">'.$strError."</div>";

			$strJsError = '
			<script type="text/javascript">
				ShowWarnings(['.$strJsError.']);
			</script>';
		}

		$stepTitle = $obStep->GetTitle();
		$stepSubTitle = $obStep->GetSubTitle();

		$autoSubmit = "";
		if ($obStep->IsAutoSubmit())
			$autoSubmit = 'setTimeout("AutoSubmit();", 500);';

		$alertText = GetMessage("MAIN_WIZARD_WANT_TO_CANCEL");
		$loadingText = GetMessage("MAIN_WIZARD_WAIT_WINDOW_TEXT");

		$package = $wizard->GetPackage();

		if ($package !== null)
		{
			$wizardPath = $package->GetPath();
			$arDescription = $package->GetDescription();
			$masterIcon = "";
			if (isset($arDescription["ICON"]) && strlen($arDescription["ICON"]) > 0)
				$masterIcon = ' style="background-image:url('.$wizardPath.'/'.$arDescription["ICON"].')"';
		}
		$themeID = ADMIN_THEME_ID;

		return <<<HTML
<html>
	<head>
		<title>{$wizardName}</title>
		<meta http-equiv="Content-Type" content="text/html; charset={$charset}">
		{$ajaxScripts}
		<style type="text/css">
			body
			{
				margin:0;
				padding:0;
				background-color: #DDE8F1;
				font-family:Verdana,Arial,helvetica,sans-serif;
				font-size:75%;
			}
			table {font-size:100%;}
			form {margin:0;}

			#border-box
			{
				margin:2px 2px 0 2px;
				border:1px solid #A9BBC8;
			}

			#step_info
			{
				height:45px;
				padding:8px 30px 8px 55px;
				border-bottom:1px solid #ccc;
				box-sizing:border-box;
				-moz-box-sizing:border-box;
				overflow:hidden;
				background:#F2F5F9 url(/bitrix/themes/{$themeID}/images/wizard/wizard.gif) 10px center no-repeat;

			}

			#step_title
			{
				font-weight:bold;
			}

			#step_description
			{
				font-size:95%;
				margin-left:10px;
			}

			#step_content
			{
				padding:20px 20px;
				box-sizing:border-box;
				-moz-box-sizing:border-box;
				float:left;
			}
			#step_buttons
			{
				height:50px;
				text-align:right;
				padding-right:20px;
				padding-top:5px;
				overflow:hidden;
				box-sizing:border-box;
				-moz-box-sizing:border-box;
			}

			#step_content_container
			{
				height:290px;
				overflow:auto;
				background:#fff;
			}

			#step_error
			{
				color:red;
				background:white;
				border-bottom:1px solid #ccc;
				padding:2px 30px;
			}

			#hidden-layer
			{
				background:#F8F9FC none repeat scroll 0%;
				height:100%;
				left:0pt;
				opacity:0.01;
				filter:alpha(opacity=1);
				-moz-opacity:0.01;
				position:absolute;
				top:0pt;
				width:100%;
				z-index:10001;
			}

			/*Data table*/
			table.wizard-data-table
			{
				border:1px solid #B2C4DD;
				border-collapse:collapse;
			}

			/*Any cell*/
			table.wizard-data-table td
			{
				border:1px solid #B2C4DD;
				background-color:#FFFFFF;
				padding:3px 5px;
			}

			/*Head cell*/
			table.wizard-data-table thead td, table.wizard-data-table th
			{
				background-color:#E4EDF5;
				font-weight:normal;
				background-image:none;
				border:1px solid #B2C4DD;
				padding:4px;
			}

			/*Body cell*/
			table.wizard-data-table tbody td
			{
				background-color:#FFF;
				background-image:none;
			}

			/*Foot cell*/
			table.wizard-data-table tfoot td
			{
				background-color:#F2F5F9;
				padding:4px;
			}

			.wizard-note-box
			{
				background:#FEFDEA;
				padding:7px;
				border:1px solid #D7D6BA;
			}

			.wizard-required
			{
				color:red;
			}

		</style>

		{$adminScript}

		<script type="text/javascript">

			function OnLoad()
			{
				var title = self.parent.window.document.getElementById("wizard_dialog_title");
				if (title)
					title.innerHTML = "{$wizardName}";

				var form = document.forms["{$formName}"];

				if (form)
					form.onsubmit = OnFormSubmit;

				var cancelButton = document.forms["{$formName}"].elements["{$cancelButtonID}"];
				var nextButton = document.forms["{$formName}"].elements["{$nextButtonID}"];
				var prevButton = document.forms["{$formName}"].elements["{$prevButtonID}"];
				var finishButton = document.forms["{$formName}"].elements["{$finishButtonID}"];

				if (cancelButton && !nextButton && !prevButton && !finishButton)
					cancelButton.onclick = CloseWindow;
				else if(cancelButton)
					cancelButton.onclick = ConfirmCancel;

				{$autoSubmit}
			}

			function OnFormSubmit()
			{
				var div = document.body.appendChild(document.createElement("DIV"));
				div.id = "hidden-layer";
			}

			function AutoSubmit()
			{
				var nextButton = document.forms["{$formName}"].elements["{$nextButtonID}"];
				if (nextButton)
				{
					var wizard = self.parent.window.WizardWindow;
					if (wizard)
					{
						wizard.messLoading = "{$loadingText}";
						wizard.ShowWaitWindow();
					}

					nextButton.click();
					nextButton.disabled=true;
				}
			}

			function ConfirmCancel()
			{
				return (confirm("{$alertText}"));
			}

			function ShowWarnings(warnings)
			{
				var form = document.forms["{$formName}"];
				if(!form)
					return;

				for(var i in warnings)
				{
					var e = form.elements[warnings[i]["name"]];
					if(!e)
						continue;

					var type = (e.type? e.type.toLowerCase():"");
					var bBefore = false;
					if(e.length > 1 && type != "select-one" && type != "select-multiple")
					{
						e = e[0];
						bBefore = true;
					}
					if(type == "textarea" || type == "select-multiple")
						bBefore = true;

					var td = e.parentNode;
					var img;
					if(bBefore)
					{
						img = td.insertBefore(new Image(), e);
						td.insertBefore(document.createElement("BR"), e);
					}
					else
					{
						img = td.insertBefore(new Image(), e.nextSibling);
						img.hspace = 2;
						img.vspace = 2;
						img.style.verticalAlign = "bottom";
					}
					img.src = "/bitrix/themes/"+phpVars.ADMIN_THEME_ID+"/images/icon_warn.gif";
					img.title = warnings[i]["title"];
				}
			}

			document.onkeydown = EnterKeyPress;

			function EnterKeyPress(event)
			{
				if (!document.getElementById)
					return;

				if (window.event)
					event = window.event;

				if (!event.ctrlKey)
					return;

				var key = (event.keyCode ? event.keyCode : (event.which ? event.which : null) );

				if (!key)
					return;

				if (key == 13 || key == 39)
				{
					var nextButton = document.forms["{$formName}"].elements["{$nextButtonID}"];
					if (nextButton)
						nextButton.click();
				}
				else if (key == 37)
				{
					var prevButton = document.forms["{$formName}"].elements["{$prevButtonID}"];
					if (prevButton)
						prevButton.click();
				}
			}

			function CloseWindow()
			{
				if (self.parent.window.WizardWindow)
					self.parent.window.WizardWindow.Close();
			}

		</script>

	</head>

	<body onload="OnLoad();">

		{#FORM_START#}
		<div id="border-box">
			<div id="step_info"{$masterIcon}>
				<div id="step_title">{$stepTitle}</div>
				<div id="step_description">{$stepSubTitle}</div>
			</div>

			<div id="step_content_container">
				{$strError}
				<div id="step_content">{#CONTENT#}</div>
			</div>
		</div>

		<div id="step_buttons">{#BUTTONS#}</div>

		{#FORM_END#}
		{$strJsError}

	</body>
</html>
HTML;

	}

}

?>