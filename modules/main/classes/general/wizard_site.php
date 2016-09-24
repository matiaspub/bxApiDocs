<?

// define("BX_WIZARD_WELCOME_ID", "__welcome");
// define("BX_WIZARD_LICENSE_ID", "__license");
// define("BX_WIZARD_SELECT_SITE_ID", "__select_site");
// define("BX_WIZARD_SELECT_GROUP_ID", "__select_group");
// define("BX_WIZARD_SELECT_TEMPLATE_ID", "__select_template");
// define("BX_WIZARD_SELECT_SERVICE_ID", "__select_service");
// define("BX_WIZARD_SELECT_STRUCTURE_ID", "__select_structure");
// define("BX_WIZARD_START_INSTALL_ID", "__start_install");
// define("BX_WIZARD_INSTALL_SITE_ID", "__install_site");
// define("BX_WIZARD_INSTALL_TEMPLATE_ID", "__install_template");
// define("BX_WIZARD_INSTALL_SERVICE_ID", "__install_service");
// define("BX_WIZARD_INSTALL_STRUCTURE_ID", "__install_structure");
// define("BX_WIZARD_FINISH_ID", "__finish");
// define("BX_WIZARD_CANCEL_ID", "__install_cancel");

class CWizard
{
	var $name;
	var $path;
	var $wizard = null;

	var $arSites = Array();
	var $arTemplates = Array();
	var $arTemplateGroups = Array();
	var $arServices = Array();
	var $arDescription = Array();
	var $arStructure = Array();
	var $arErrors = Array();

	var $pathToScript = null;

	var $siteID = null;
	var $templateID = null;
	var $groupID = null;
	var $serviceID = Array();
	var $structureID = null;

	var $licenseExists = false;
	var $siteExists = false;
	var $groupExists = false;
	var $templateExists = false;
	var $serviceExists = false;
	var $structureExists = false;

	var $siteSelected = false;
	var $templateSelected = false;
	var $serviceSelected = false;
	var $structureSelected = false;

	var $__bInited = false;
	var $__obLastStep = null;
	var $__obFirstStep = null;

	public function __construct($wizardName)
	{
		$this->name = $wizardName;

		if (!CWizardUtil::CheckName($this->name))
		{
			$this->SetError(GetMessage("MAIN_WIZARD_ERROR_WRONG_WIZ_NAME"));
			return;
		}

		$pathToWizard = CWizardUtil::MakeWizardPath($this->name);
		$this->path = CWizardUtil::GetRepositoryPath().$pathToWizard;

		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$this->path) || !is_dir($_SERVER["DOCUMENT_ROOT"].$this->path))
		{
			$this->SetError(GetMessage("MAIN_WIZARD_ERROR_NOT_FOUND"));
			return;
		}

		$this->__GetDescription();
		$this->__CheckDepends();
		$this->__GetInstallationScript();
	}

	/** @deprecated */
	static public function CWizard($wizardName)
	{
		self::__construct($wizardName);
	}

	public function Install()
	{
		if ($this->__bInited)
			return;

		$this->__bInited = true;

		if (count($this->arErrors) > 0)
		{
			/*Generate error step */
			$this->__PackageError();
		}
		elseif ($this->pathToScript)
		{
			$package = $this;

			if($this->arDescription["PARENT"] == "wizard_sol")
			{
				$lang = LANGUAGE_ID;
				$wizardPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main";
				$relativePath = "install/wizard_sol/wizard.php";

				if ($lang != "en" && $lang != "ru")
				{
					if (file_exists(($fname = $wizardPath."/lang/".LangSubst($lang)."/".$relativePath)))
						__IncludeLang($fname, false, true);
				}

				if (file_exists(($fname = $wizardPath."/lang/".$lang."/".$relativePath)))
					__IncludeLang($fname, false, true);
			}

			$this->IncludeWizardLang("wizard.php");

			include($this->pathToScript);

			if (array_key_exists("STEPS", $this->arDescription) && is_array($this->arDescription["STEPS"]))
			{
				$wizardName = (array_key_exists("NAME", $this->arDescription) ? $this->arDescription["NAME"] : "");
				$this->wizard = new CWizardBase($wizardName, $this);
				$this->wizard->AddSteps($this->arDescription["STEPS"]);
				$this->__SetTemplate();
				$this->wizard->Display();
			}
		}
		else
		{
			//Get description files
			$this->__GetSites();
			$this->__GetTemplates();
			$this->__GetServices();
			$this->__GetStructure();

			//Generate system steps
			$this->__Install();
		}
	}

	public function __Install()
	{
		//Create wizard
		$wizardName = (array_key_exists("NAME", $this->arDescription) ? $this->arDescription["NAME"] : "");
		$this->wizard = new CWizardBase($wizardName, $this);
		$this->__SetTemplate();
		$this->__InitVariables();

		$wizard = $this->wizard;

		//Welcome step
		if ($this->__GetUserStep("welcome", $userWelcome))
			$step = $userWelcome;
		else
			$step = new CPackageWelcome($this);

		$wizard->AddStep($step, BX_WIZARD_WELCOME_ID);
		$this->_SetNextStep($step, BX_WIZARD_WELCOME_ID, "select");
		$this->__SetStepDescription($step, "WELCOME");
		$step->SetCancelStep(BX_WIZARD_CANCEL_ID);
		$lastStepID = $step->GetStepID();

		//#NEW
		if ($this->_InitSubStep("static", $this->arDescription["STEPS_SETTINGS"]["WELCOME"]) )
		{
			$lastStepID = $this->__obLastStep->GetStepID();
			$this->_SetNextStep($this->__obLastStep, BX_WIZARD_WELCOME_ID, "select");
			$this->__obFirstStep->SetPrevStep(BX_WIZARD_WELCOME_ID);
			$step->SetNextStep($this->__obFirstStep->GetStepID());
		}
		//

		//License step
		if ($this->licenseExists)
		{
			$step = new CPackageLicense($this);
			$wizard->AddStep($step, BX_WIZARD_LICENSE_ID);
			$this->_SetNextStep($step, BX_WIZARD_LICENSE_ID, "select");
			$this->__SetStepDescription($step, "LICENSE");
			$step->SetPrevStep($lastStepID);
			$lastStepID = $step->GetStepID();

			//Add custom steps to wizard
			if (/*$this->siteSelected &&*/ $this->_InitSubStep("static", $this->arDescription["STEPS_SETTINGS"]["LICENSE"]) )
			{
				$lastStepID = $this->__obLastStep->GetStepID();
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_LICENSE_ID, "select");
				$this->__obFirstStep->SetPrevStep(BX_WIZARD_LICENSE_ID);
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		$siteID = $this->siteID;
		$templateID = $this->templateID;
		$arServices = $this->serviceID;
		$structureID = $this->structureID;

		//Select site step
		if ($this->siteExists)
		{
			$step = new CPackageSelectSite($this);
			$wizard->AddStep($step, BX_WIZARD_SELECT_SITE_ID);
			$this->_SetNextStep($step, BX_WIZARD_SELECT_SITE_ID, "select");
			$this->__SetStepDescription($step, "SELECT_SITE");
			$step->SetPrevStep($lastStepID);
			$lastStepID = $step->GetStepID();

			//Add custom steps to wizard
			if ($this->siteSelected && $this->_InitSubStep("select", $this->arSites[$siteID]) )
			{
				$lastStepID = $this->__obLastStep->GetStepID();
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_SELECT_SITE_ID, "select");
				$this->__obFirstStep->SetPrevStep(BX_WIZARD_SELECT_SITE_ID);
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		//Select group step
		if ($this->groupExists && $this->templateExists)
		{
			$step = new CPackageSelectGroup($this);
			$wizard->AddStep($step, BX_WIZARD_SELECT_GROUP_ID);
			$this->_SetNextStep($step, BX_WIZARD_SELECT_GROUP_ID, "select");
			$this->__SetStepDescription($step, "SELECT_GROUP");
			$step->SetPrevStep($lastStepID);
			$lastStepID = $step->GetStepID();
		}

		//Select template step
		if ($this->templateExists)
		{
			$step = new CPackageSelectTemplate($this);
			$wizard->AddStep($step, BX_WIZARD_SELECT_TEMPLATE_ID);
			$this->_SetNextStep($step, BX_WIZARD_SELECT_TEMPLATE_ID, "select");
			$this->__SetStepDescription($step, "SELECT_TEMPLATE");
			$step->SetPrevStep($lastStepID);
			$lastStepID = $step->GetStepID();

			//Add custom steps to wizard
			if ($this->templateSelected && $this->_InitSubStep("select", $this->arTemplates[$templateID]))
			{
				$lastStepID = $this->__obLastStep->GetStepID();
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_SELECT_TEMPLATE_ID, "select");
				$this->__obFirstStep->SetPrevStep(BX_WIZARD_SELECT_TEMPLATE_ID);
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		//Select service step
		if ($this->serviceExists)
		{
			$step = new CPackageSelectService($this);
			$wizard->AddStep($step, BX_WIZARD_SELECT_SERVICE_ID);
			$this->_SetNextStep($step, BX_WIZARD_SELECT_SERVICE_ID, "select");
			//$step->SetNextStep("__start_install");
			$step->SetPrevStep($lastStepID);
			$this->__SetStepDescription($step, "SELECT_SERVICE");
			$lastStepID = $step->GetStepID();

			if ($this->serviceSelected)
			{
				foreach ($arServices as $service)
				{
					if (!array_key_exists($service, $this->arServices))
						continue;

					//Add custom steps to wizard
					if ($this->_InitSubStep("select", $this->arServices[$service]))
					{
						$this->__obFirstStep->SetPrevStep($lastStepID);

						//$this->__obLastStep->SetNextStep("__start_install");
						$this->_SetNextStep($this->__obLastStep, BX_WIZARD_SELECT_SERVICE_ID, "select");
						$lastStepID = $this->__obLastStep->GetStepID();

						$step->SetNextStep($this->__obFirstStep->GetStepID());
						$step = $this->__obLastStep;
					}
				}
			}
		}

		//Select structure
		if ($this->structureExists)
		{
			$step = new CPackageSelectStructure($this);
			$wizard->AddStep($step, BX_WIZARD_SELECT_STRUCTURE_ID);
			$this->_SetNextStep($step, BX_WIZARD_SELECT_STRUCTURE_ID, "select");
			$this->__SetStepDescription($step, "SELECT_STRUCTURE");
			$step->SetPrevStep($lastStepID);
			$lastStepID = $step->GetStepID();

			//#NEW
			if ($this->_InitSubStep("select", $this->arStructure["SETTINGS"]))
			{
				$lastStepID = $this->__obLastStep->GetStepID();
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_SELECT_STRUCTURE_ID, "select");
				$this->__obFirstStep->SetPrevStep(BX_WIZARD_SELECT_STRUCTURE_ID);
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		//Start installation step
		$arSelected = Array(
			"siteID" => ($this->siteSelected ? $siteID : null),
			"templateID" => ($this->templateSelected ? $templateID : null),
			"arServices" => ($this->serviceSelected ? $arServices : Array()),
		);

		if ($this->__GetUserStep("start_install", $userStartInstall))
			$step = $userStartInstall;
		else
			$step = new CPackageStartInstall($this, $arSelected);

		$wizard->AddStep($step, BX_WIZARD_START_INSTALL_ID);
		$step->SetPrevStep($lastStepID);
		$step->SetCancelStep(BX_WIZARD_CANCEL_ID);
		$this->__SetStepDescription($step, "START_INSTALL");
		$this->_SetNextStep($step, BX_WIZARD_START_INSTALL_ID, "install");

		//#NEW
		if ($this->_InitSubStep("static", $this->arDescription["STEPS_SETTINGS"]["START_INSTALL"]) )
		{
			$lastStepID = $this->__obLastStep->GetStepID();
			$this->_SetNextStep($this->__obLastStep, BX_WIZARD_START_INSTALL_ID, "install");
			$this->__obFirstStep->SetPrevStep(BX_WIZARD_START_INSTALL_ID);
			$step->SetNextStep($this->__obFirstStep->GetStepID());
		}
		//

		//Site installation step
		if ($this->siteSelected)
		{
			$step = new CPackageInstallSite($this, $siteID);
			$wizard->AddStep($step, BX_WIZARD_INSTALL_SITE_ID);
			$this->_SetNextStep($step, BX_WIZARD_INSTALL_SITE_ID, "install");
			$this->__SetStepDescription($step, "INSTALL_SITE");

			if ($this->_InitSubStep("install", $this->arSites[$siteID]))
			{
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_INSTALL_SITE_ID, "install");
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		//Template installation step
		if ($this->templateSelected)
		{
			$step = new CPackageInstallTemplate($this, $templateID);
			$wizard->AddStep($step, BX_WIZARD_INSTALL_TEMPLATE_ID);
			$this->_SetNextStep($step, BX_WIZARD_INSTALL_TEMPLATE_ID, "install");
			$this->__SetStepDescription($step, "INSTALL_TEMPLATE");

			if ($this->_InitSubStep("install", $this->arTemplates[$templateID]))
			{
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_INSTALL_TEMPLATE_ID, "install");
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		//Service installation step
		if ($this->serviceSelected)
		{
			$obLastStep = null;
			$number = "";
			foreach ($arServices as $service)
			{
				if (!array_key_exists($service, $this->arServices))
					continue;

				if ($obLastStep !== null)
					$obLastStep->SetNextStep(BX_WIZARD_INSTALL_SERVICE_ID.$number);

				$step = new CPackageInstallService($this, $service);
				$wizard->AddStep($step, BX_WIZARD_INSTALL_SERVICE_ID.$number);
				$this->__SetStepDescription($step, "INSTALL_SERVICE");

				if ($this->_InitSubStep("install", $this->arServices[$service]))
				{
					//$this->__obLastStep->SetNextStep("__finish");
					$this->_SetNextStep($this->__obLastStep, BX_WIZARD_INSTALL_SERVICE_ID, "install");
					$obLastStep = $this->__obLastStep;
					$step->SetNextStep($this->__obFirstStep->GetStepID());
				}
				else
				{
					$obLastStep = $step;
					//$step->SetNextStep("__finish");
					$this->_SetNextStep($step, BX_WIZARD_INSTALL_SERVICE_ID, "install");
				}

				(int)$number++;
			}
		}

		//Structure installation step
		if ($this->structureSelected)
		{
			$step = new CPackageInstallStructure($structureID);
			$wizard->AddStep($step, BX_WIZARD_INSTALL_STRUCTURE_ID);
			$this->_SetNextStep($step, BX_WIZARD_INSTALL_STRUCTURE_ID, "install");
			$this->__SetStepDescription($step, "INSTALL_STRUCTURE");

			//#NEW
			if ($this->_InitSubStep("install", $this->arStructure["SETTINGS"]))
			{
				$this->_SetNextStep($this->__obLastStep, BX_WIZARD_INSTALL_STRUCTURE_ID, "install");
				$step->SetNextStep($this->__obFirstStep->GetStepID());
			}
		}

		//Finish step
		$isUserStep = false;
		if ($this->__GetUserStep("finish", $userFinish))
		{
			$step = $userFinish;
			$isUserStep = true;
		}
		else
			$step = new CPackageFinish($this);

		$wizard->AddStep($step, BX_WIZARD_FINISH_ID);
		$this->__SetStepDescription($step, "FINISH");

		if (!$isUserStep)
			$step->SetCancelStep(BX_WIZARD_FINISH_ID);

		//#NEW
		if ($this->_InitSubStep("end", $this->arDescription["STEPS_SETTINGS"]["FINISH"]) )
		{
			$this->__obFirstStep->SetPrevStep(BX_WIZARD_FINISH_ID);
			$step->SetNextStep($this->__obFirstStep->GetStepID());
		}

		//Cancel step
		$isUserStep = false;
		if ($this->__GetUserStep("cancel", $userCancel))
		{
			$isUserStep = true;
			$step = $userCancel;
		}
		else
			$step = new CPackageCancel($this);

		$wizard->AddStep($step, BX_WIZARD_CANCEL_ID);
		$this->__SetStepDescription($step, "CANCEL");

		if (!$isUserStep)
			$step->SetCancelStep(BX_WIZARD_CANCEL_ID);

		//#NEW
		if ($this->_InitSubStep("end", $this->arDescription["STEPS_SETTINGS"]["CANCEL"]) )
		{
			$this->__obFirstStep->SetPrevStep(BX_WIZARD_CANCEL_ID);
			$step->SetNextStep($this->__obFirstStep->GetStepID());
		}

		$wizard->Display();
	}

	public function __SetTemplate()
	{
		if (!array_key_exists("TEMPLATES", $this->arDescription) || !is_array($this->arDescription["TEMPLATES"]))
			return;

		foreach ($this->arDescription["TEMPLATES"] as $arTemplate)
		{
			if($arTemplate["SCRIPT"]=="wizard_sol")
			{
				$lang = LANGUAGE_ID;
				$wizardPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main";
				$relativePath = "install/wizard_sol/template.php";

				if ($lang != "en" && $lang != "ru")
				{
					if (file_exists(($fname = $wizardPath."/lang/".LangSubst($lang)."/".$relativePath)))
						__IncludeLang($fname, false, true);
				}

				if (file_exists(($fname = $wizardPath."/lang/".$lang."/".$relativePath)))
					__IncludeLang($fname, false, true);

				include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/install/wizard_sol/template.php");

				$stepID = (isset($arTemplate["STEP"]) ? $arTemplate["STEP"] : null);
				$this->wizard->SetTemplate(new WizardTemplate, $stepID);
				$this->wizard->DisableAdminTemplate();
			}
			else
			{
				if (!isset($arTemplate["SCRIPT"]) || !isset($arTemplate["CLASS"]))
					continue;

				$pathToFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/".$arTemplate["SCRIPT"];

				if (!is_file($pathToFile))
					continue;

				$this->IncludeWizardLang($arTemplate["SCRIPT"]);

				include_once($pathToFile);

				if (!class_exists($arTemplate["CLASS"]))
					continue;

				$stepID = (isset($arTemplate["STEP"]) ? $arTemplate["STEP"] : null);
				$this->wizard->SetTemplate(new $arTemplate["CLASS"], $stepID);
				$this->wizard->DisableAdminTemplate();
			}
		}

	}

	public function __InitVariables()
	{
		$this->licenseExists = ($this->__GetLicensePath() !== false);
		$this->siteExists = (count($this->arSites)>0);
		$this->groupExists = (count($this->arTemplateGroups)>0);
		$this->templateExists = (count($this->arTemplates)>0);
		$this->serviceExists = (count($this->arServices)>0);
		$this->structureExists = (count($this->arStructure)>0);

		$wizard = $this->wizard;

		$this->siteID = $wizard->GetVar("__siteID");
		$this->templateID = $wizard->GetVar("__templateID");
		$this->groupID = $wizard->GetVar("__groupID");
		$this->serviceID = $wizard->GetVar("__serviceID");
		$this->structureID = $wizard->GetVar("__structureID");

		$this->siteSelected = ($this->siteExists && $this->siteID !== null && array_key_exists($this->siteID, $this->arSites));
		$this->templateSelected = ($this->templateExists && $this->templateID !== null && array_key_exists($this->templateID, $this->arTemplates));
		$this->serviceSelected = ($this->serviceExists && is_array($this->serviceID));
		$this->structureSelected = ($this->structureExists && strlen($this->structureID) > 0);
	}

	public function _SetNextStep($obStep, $currentStep, $stepType = "select")
	{
		if ($stepType == "select")
			$arWizardStep = Array(
				BX_WIZARD_WELCOME_ID => true,
				BX_WIZARD_LICENSE_ID => $this->licenseExists,
				BX_WIZARD_SELECT_SITE_ID => $this->siteExists,
				BX_WIZARD_SELECT_GROUP_ID => ($this->groupExists && $this->templateExists),
				BX_WIZARD_SELECT_TEMPLATE_ID => $this->templateExists,
				BX_WIZARD_SELECT_SERVICE_ID => $this->serviceExists,
				BX_WIZARD_SELECT_STRUCTURE_ID => $this->structureExists,
				BX_WIZARD_START_INSTALL_ID => true,
			);

		else
			$arWizardStep = Array(
				BX_WIZARD_START_INSTALL_ID => true,
				BX_WIZARD_INSTALL_SITE_ID => $this->siteSelected,
				BX_WIZARD_INSTALL_TEMPLATE_ID => $this->templateSelected,
				BX_WIZARD_INSTALL_SERVICE_ID => $this->serviceSelected,
				BX_WIZARD_INSTALL_STRUCTURE_ID => $this->structureSelected,
				BX_WIZARD_FINISH_ID => true,
			);

		$nextStepID = null;
		$foundCurrent = false;
		foreach ($arWizardStep as $stepID => $success)
		{
			if ($foundCurrent && $success)
			{
				$nextStepID = $stepID;
				break;
			}

			if ($currentStep == $stepID)
			{
				$foundCurrent = true;
				continue;
			}
		}
		$obStep->SetNextStep($nextStepID);
	}

	public function _InitSubStep($stepType, &$arInstallation, $bInitStep = true)
	{
		if (!is_array($arInstallation))
			return false;

		if ($stepType == "install" || $stepType == "select")
		{
			$stepTypeKey = strtoupper($stepType."_steps");
			if (!array_key_exists($stepTypeKey, $arInstallation))
				return false;

			$arSteps =& $arInstallation[$stepTypeKey];
		}
		else
		{
			$arSteps =& $arInstallation;
		}

		if (!array_key_exists("SCRIPT", $arSteps) || !array_key_exists("STEPS", $arSteps))
			return false;

		$instScript = $_SERVER["DOCUMENT_ROOT"].$this->path."/".$arSteps["SCRIPT"];
		if (!is_file($instScript))
			return false;

		$package = $this;
		$this->IncludeWizardLang($arSteps["SCRIPT"]);
		include_once($instScript);

		$stepNumber = 1;
		$stepCount = count($arSteps["STEPS"]);
		$firstStepExists = false;
		$lastStepExists = false;

		foreach ($arSteps["STEPS"] as $stepID => $stepClass)
		{
			if (!class_exists($stepClass))
				continue;

			if ($bInitStep)
			{
				$subStep = new $stepClass;
				$this->wizard->AddStep($subStep, $stepID);
			}
			else
			{
				if (!array_key_exists($stepID, $this->wizard->wizardSteps))
					continue;
				$subStep = $this->wizard->wizardSteps[$stepID];
			}

			if ($stepType == "select")
			{
				$subStep->SetCancelStep(BX_WIZARD_CANCEL_ID);
			}
			elseif ($stepType == "install")
			{
				$subStep->SetAutoSubmit();
				$subStep->SetCancelStep(null);
				$subStep->SetPrevStep(null);
			}
			elseif ($stepType == "static")
			{
				$subStep->SetCancelStep(BX_WIZARD_CANCEL_ID);
			}

			//First step
			if ($stepNumber == 1)
			{
				if ($stepType == "install")
					$subStep->SetPrevStep(null); //hide previous button
				$this->__obFirstStep = $subStep;
				$firstStepExists = true;
			}

			//Last step
			if ($stepNumber == $stepCount)
			{
				$this->__obLastStep = $subStep;
				$lastStepExists = true;
			}

			$stepNumber++;
		}

		return ($firstStepExists && $lastStepExists);
	}

	public function __GetUserStep($stepName, &$step)
	{
		$stepName = strtoupper($stepName);

		if (!array_key_exists("STEPS_SETTINGS", $this->arDescription) || !array_key_exists($stepName, $this->arDescription["STEPS_SETTINGS"]))
			return false;

		if (!isset($this->arDescription["STEPS_SETTINGS"][$stepName]["SCRIPT"]) || !isset($this->arDescription["STEPS_SETTINGS"][$stepName]["CLASS"]))
			return false;

		$scriptPath = $this->arDescription["STEPS_SETTINGS"][$stepName]["SCRIPT"];
		$stepClass = $this->arDescription["STEPS_SETTINGS"][$stepName]["CLASS"];

		$pathToFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/".$scriptPath;
		if (!is_file($pathToFile))
			return false;

		$this->IncludeWizardLang($scriptPath);
		include_once($pathToFile);

		if (!class_exists($stepClass))
			return false;

		$step = new $stepClass;

		if (!is_subclass_of($step, "CWizardStep"))
			return false;

		return true;
	}

	public function __SetStepDescription($obStep, $stepName)
	{
		if (!array_key_exists("STEPS_SETTINGS", $this->arDescription) || !array_key_exists($stepName, $this->arDescription["STEPS_SETTINGS"]))
			return;

		if (isset($this->arDescription["STEPS_SETTINGS"][$stepName]["TITLE"]))
			$obStep->SetTitle($this->arDescription["STEPS_SETTINGS"][$stepName]["TITLE"]);

		if (isset($this->arDescription["STEPS_SETTINGS"][$stepName]["SUBTITLE"]))
			$obStep->SetSubTitle($this->arDescription["STEPS_SETTINGS"][$stepName]["SUBTITLE"]);

		if (isset($this->arDescription["STEPS_SETTINGS"][$stepName]["CONTENT"]))
			$obStep->content .= $this->arDescription["STEPS_SETTINGS"][$stepName]["CONTENT"];
	}

	public function __GetLicensePath()
	{
		$path = false;

		if (is_file($_SERVER["DOCUMENT_ROOT"].$this->path."/license.php"))
			$path = $this->path."/license.php";

		if (is_file($_SERVER["DOCUMENT_ROOT"].$this->path."/license_".LANGUAGE_ID.".php"))
			$path = $this->path."/license_".LANGUAGE_ID.".php";

		return $path;
	}

	public function __PackageError()
	{
		echo '<span style="color:red;">';
		foreach ($this->arErrors as $arError)
			echo $arError[0]."<br />";
		echo "</span>";
	}

	public function __GetDescription()
	{
		$descFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/.description.php";

		if (!is_file($descFile))
			return false;

		$this->IncludeWizardLang(".description.php");

		$arWizardDescription = Array();
		include($descFile);

		$this->arDescription = $arWizardDescription;

		return true;
	}

	public function __CheckDepends()
	{
		$success = true;
		if (array_key_exists("DEPENDENCIES", $this->arDescription) && is_array($this->arDescription["DEPENDENCIES"]))
		{
			$arModules = CWizardUtil::GetModules();

			foreach ($this->arDescription["DEPENDENCIES"] as $module => $version)
			{
				if (!array_key_exists($module, $arModules))
				{
					$this->SetError(
						str_replace("#MODULE#", htmlspecialcharsbx($module), GetMessage("MAIN_WIZARD_ERROR_MODULE_REQUIRED"))
					);
					$success = false;
				}
				elseif (!$arModules[$module]["IsInstalled"])
				{
					$this->SetError(
						str_replace("#MODULE#", $arModules[$module]["MODULE_NAME"], GetMessage("MAIN_WIZARD_ERROR_MODULE_REQUIRED"))
					);
					$success = false;
				}
				elseif (!CheckVersion($arModules[$module]["MODULE_VERSION"], $version))
				{
					$this->SetError(
						str_replace(Array("#MODULE#", "#VERSION#"),
										Array($arModules[$module]["MODULE_NAME"], htmlspecialcharsbx($version)),
										GetMessage("MAIN_WIZARD_ERROR_MODULE_REQUIRED2"))
					);
					$success = false;
				}
			}
		}

		return $success;
	}

	public function __GetSites()
	{
		$siteFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/.sites.php";
		if (!is_file($siteFile))
			return false;

		$this->IncludeWizardLang(".sites.php");

		$arWizardSites = Array();
		include($siteFile);
		$this->arSites = $arWizardSites;
	}

	public function __GetTemplatesPath()
	{
		$templatesPath = $this->path."/templates";
		if (file_exists($_SERVER["DOCUMENT_ROOT"].$templatesPath."/".LANGUAGE_ID))
			$templatesPath .= "/".LANGUAGE_ID;
		return $templatesPath;
	}

	public function __GetTemplates()
	{
		$settingFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/.templates.php";
		$arWizardTemplates = Array();
		if (is_file($settingFile))
		{
			$this->IncludeWizardLang(".templates.php");
			include($settingFile);
		}

		$relativePath = $this->__GetTemplatesPath();
		$absolutePath = $_SERVER["DOCUMENT_ROOT"].$relativePath;
		$absolutePath = str_replace("\\", "/", $absolutePath);

		if ($handle  = @opendir($absolutePath))
		{
			while(($dirName = @readdir($handle)) !== false)
			{
				if ($dirName == "." || $dirName == ".." || !is_dir($absolutePath."/".$dirName))
					continue;

				$arTemplate = Array(
					"DESCRIPTION"=>"",
					"NAME" => $dirName,
				);

				if (file_exists($absolutePath."/".$dirName."/description.php"))
				{
					if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
					{
						if (file_exists(($fname = $absolutePath."/".$dirName."/lang/".LangSubst(LANGUAGE_ID)."/description.php")))
							__IncludeLang($fname, false, true);
					}

					if (file_exists(($fname = $absolutePath."/".$dirName."/lang/".LANGUAGE_ID."/description.php")))
							__IncludeLang($fname, false, true);

					include($absolutePath."/".$dirName."/description.php");
				}

				$arTemplate["ID"] = $dirName;
				$arTemplate["PATH"] = $this->path."/".$dirName;
				$arTemplate["SITE_ID"] = "";
				$arTemplate["SORT"] = 0;
				$arTemplate["GROUP_ID"] = "";

				if (file_exists($absolutePath."/".$dirName."/screen.gif"))
					$arTemplate["SCREENSHOT"] = $relativePath."/".$dirName."/screen.gif";
				else
					$arTemplate["SCREENSHOT"] = false;

				if (file_exists($absolutePath."/".$dirName."/preview.gif"))
					$arTemplate["PREVIEW"] = $relativePath."/".$dirName."/preview.gif";
				else
					$arTemplate["PREVIEW"] = false;

				if (array_key_exists("TEMPLATES", $arWizardTemplates) && array_key_exists($dirName, $arWizardTemplates["TEMPLATES"]))
					$arTemplate = array_merge($arTemplate, $arWizardTemplates["TEMPLATES"][$dirName]);

				$this->arTemplates[$arTemplate["ID"]] = $arTemplate;
			}
			closedir($handle);
		}

		uasort($this->arTemplates, create_function('$a, $b', 'return strcmp($a["SORT"], $b["SORT"]);'));

		if (array_key_exists("GROUPS", $arWizardTemplates) && is_array($arWizardTemplates["GROUPS"]))
			$this->arTemplateGroups = $arWizardTemplates["GROUPS"];
	}

	public function __GetInstallationScript()
	{
		$instScript = $_SERVER["DOCUMENT_ROOT"].$this->path."/wizard.php";

		if (!is_file($instScript))
			return false;

		$this->pathToScript = $instScript;
		return true;
	}

	public function __GetServices()
	{
		$serviceFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/.services.php";
		if (!is_file($serviceFile))
			return false;

		$this->IncludeWizardLang(".services.php");

		$arWizardServices = Array();
		include($serviceFile);
		$this->arServices = $arWizardServices;
	}

	public function __GetStructure()
	{
		$structureFile = $_SERVER["DOCUMENT_ROOT"].$this->path."/.structure.php";
		if (!is_file($structureFile))
			return false;

		$this->IncludeWizardLang(".structure.php");

		$arWizardStructure = Array();
		include($structureFile);
		$this->arStructure = $arWizardStructure;
	}

	public function __InstallSite($siteID)
	{
		if (!array_key_exists($siteID, $this->arSites))
			return;

		//If the main module was not included
		global $DB, $DBType, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		//Copy files
		$this->__MoveDirFiles($this->arSites[$siteID]);
	}

	public function __InstallTemplate($templateID)
	{
		if (!array_key_exists($templateID, $this->arTemplates))
			return;

		//Copy template
		$canCopyTemplate = !(
			file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$templateID) &&
			isset($this->arTemplates[$templateID]["REWRITE"]) && $this->arTemplates[$templateID]["REWRITE"] == "N"
		);

		//If the main module was not included
		global $DB, $DBType, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		if ($canCopyTemplate)
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"].$this->__GetTemplatesPath()."/".$templateID,
				$_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$templateID,
				$rewrite = true,
				$recursive = true
			);
		}

		//Attach template to default site
		$obSite = CSite::GetList($by = "def", $order = "desc", Array("ACTIVE" => "Y"));
		if ($arSite = $obSite->Fetch())
		{
			$arTemplates = Array();
			$found = false;
			$obTemplate = CSite::GetTemplateList($arSite["LID"]);
			while($arTemplate = $obTemplate->Fetch())
			{
				if(!$found && strlen(Trim($arTemplate["CONDITION"]))<=0)
				{
					$arTemplate["TEMPLATE"] = $templateID;
					$found = true;
				}
				$arTemplates[]= $arTemplate;
			}

			if (!$found)
				$arTemplates[]= Array("CONDITION" => "", "SORT" => 150, "TEMPLATE" => $templateID);

			$arFields = Array(
				"TEMPLATE" => $arTemplates,
				"NAME" => $arSite["NAME"],
			);

			$obSite = new CSite();
			$obSite->Update($arSite["LID"], $arFields);
		}

		//Copy files
		$this->__MoveDirFiles($this->arTemplates[$templateID]);
	}

	public function __InstallService($serviceID)
	{
		if (!array_key_exists($serviceID, $this->arServices))
			return;

		//If the main module was not included
		global $DB, $DBType, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		//Copy files
		$this->__MoveDirFiles($this->arServices[$serviceID]);
	}

	public function __InstallStructure()
	{
		global $APPLICATION;

		if (strlen($this->structureID) <= 0)
			return;

		$arStructure = $this->GetStructure(
			Array(
				"SERVICE_ID" => $this->serviceID,
				"SITE_ID" => $this->siteID
			)
		);

		//If the main module was not included
		global $DB, $DBType, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		$arStructure = $this->__GetNewStructure($this->structureID, $arStructure);

		//echo "<pre>".print_r($arStructure,true)."</pre>";exit;

		public static function __CreateMenuItem($arPage)
		{
			return "\n".
						"	Array(\n".
						"		\"".$arPage["NAME"]."\", \n".
						"		\"".$arPage["LINK"]."\", \n".
						"		Array(), \n".
						"		Array(), \n".
						"		\"\"\n".
						"	),";
		}

		public function __GetFileName($fileName, $postFix)
		{
			if ($postFix == "")
				return $fileName;

			$position = strrpos($fileName, ".");

			if ($position !== false)
				$fileName = substr($fileName, 0, $position).$postFix.substr($fileName, $position);

			return $fileName;
		}

		$rootMenuType = (isset($this->arStructure["SETTINGS"]) && isset($this->arStructure["SETTINGS"]["ROOT_MENU_TYPE"])
			? $this->arStructure["SETTINGS"]["ROOT_MENU_TYPE"]
			: "top"
		);
		$childMenuType = (isset($this->arStructure["SETTINGS"]) && isset($this->arStructure["SETTINGS"]["CHILD_MENU_TYPE"])
			? $this->arStructure["SETTINGS"]["CHILD_MENU_TYPE"]
			: "left"
		);

		$strRootMenu = "";
		$arFileToMove = Array();
		$arPageCnt = Array();
		foreach ($arStructure as $rootPageID => $arPage)
		{
			//Item type "service"
			if (isset($arPage["TYPE"]) && strtoupper($arPage["TYPE"]) == "SERVICE")
			{
				$strRootMenu .= __CreateMenuItem($arPage);
			}
			else
			{
				if (isset($arPage["CHILD"]) && is_array($arPage["CHILD"]) && !empty($arPage["CHILD"]))
				{
					$strLeftMenu = "";

					if ( ($position = strrpos($rootPageID, "-")) !== false)
						$rootPageID = substr($rootPageID, $position+1, strlen($rootPageID));

					//Root item
					$arFileToMove[] = Array(
						$_SERVER["DOCUMENT_ROOT"].$this->path."/".$arPage["FILE"],
						$_SERVER["DOCUMENT_ROOT"]."/".$rootPageID."/index.php"
					);
					$arPage["LINK"] = "/".$rootPageID."/";
					$strRootMenu .= __CreateMenuItem($arPage);

					//Child items
					$arSubPageCnt = Array();
					foreach ($arPage["CHILD"] as $subPageID => $arSubPage)
					{
						$fileName = basename($arSubPage["FILE"]);

						if (array_key_exists($fileName, $arSubPageCnt))
							(int)$arSubPageCnt[$fileName]++;
						else
							$arSubPageCnt[$fileName] = "";

						$fileName = __GetFileName($fileName, $arSubPageCnt[$fileName]);

						$arFileToMove[] = Array(
							$_SERVER["DOCUMENT_ROOT"].$this->path."/".$arSubPage["FILE"],
							$_SERVER["DOCUMENT_ROOT"]."/".$rootPageID."/".$fileName
						);

						$arSubPage["LINK"] = "/".$rootPageID."/".$fileName;
						$strLeftMenu .= __CreateMenuItem($arSubPage);
					}

					if (strlen($strLeftMenu) > 0)
					{
						$strSectionName = "\$sSectionName = \"".$arPage["NAME"]."\";\n";
						$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"]."/".$rootPageID."/.section.php", "<"."?\n".$strSectionName."?".">");

						$strLeftMenu = "\$aMenuLinks = Array(".$strLeftMenu."\n);";
						$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"]."/".$rootPageID."/.".$childMenuType.".menu.php", "<"."?\n".$strLeftMenu."\n?".">");
					}
				}
				else
				{
					$fileName = basename($arPage["FILE"]);

					if (array_key_exists($fileName, $arPageCnt))
						(int)$arPageCnt[$fileName]++;
					else
						$arPageCnt[$fileName] = "";

					$fileName = __GetFileName($fileName, $arPageCnt[$fileName]);

					$arFileToMove[] = Array(
						$_SERVER["DOCUMENT_ROOT"].$this->path."/".$arPage["FILE"],
						$_SERVER["DOCUMENT_ROOT"]."/".$fileName,
					);

					$arPage["LINK"] = "/".$fileName;
					$strRootMenu .= __CreateMenuItem($arPage);
				}
			}
		}

		//Save top menu
		if (strlen($strRootMenu) > 0)
		{
			$strRootMenu = "\$aMenuLinks = Array(".$strRootMenu."\n);";
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"]."/.".$rootMenuType.".menu.php", "<"."?\n".$strRootMenu."\n?".">");
		}

		//Copy files for menu items
		foreach ($arFileToMove as $arFile)
			CopyDirFiles($arFile[0], $arFile[1]);
	}


	public static function __GetPageProperties($pageID, &$arStructure)
	{
		$arPageIDs = explode("-", $pageID);
		$arResult = Array();

		if (!isset($arPageIDs[0]) || !array_key_exists($arPageIDs[0], $arStructure))
			return Array();

		$arResult = $arStructure[$arPageIDs[0]] + Array("ID" => $pageID);

		if (isset($arPageIDs[1]))
		{
			if (!array_key_exists($arPageIDs[1], $arStructure[$arPageIDs[0]]["CHILD"]))
				return Array();

			$arResult = $arStructure[$arPageIDs[0]]["CHILD"][$arPageIDs[1]] + Array("ID" => $pageID);
		}

		unset($arResult["CHILD"]);
		return $arResult;
	}

	public function __GetNewStructure($structureID, &$arStructure)
	{
		$arNewStructure = Array();
		$rootPageCnt = Array();
		$childNumber = "";

		$arPages = explode(";", $structureID);
		foreach ($arPages as $page)
		{
			//Format: Item ID: Root ID
			if (strlen($page) <= 0)
				continue;

			$pageID = $page;
			$rootID = false;

			if ( ($position = strpos($page, ":")) !== false)
				list($pageID, $rootID) = explode(":", $pageID);

			$arPageProp = $this->__GetPageProperties($pageID, $arStructure);
			if (empty($arPageProp))
				continue;

			if (strlen($rootID) <= 0)
			{
				if (array_key_exists($pageID, $arNewStructure))
				{
					$rootPageCnt[$pageID]++; //(int)$rootNumber++;
					$arNewStructure[$pageID.$rootPageCnt[$pageID]] = $arPageProp + Array("CHILD" => Array());
				}
				else
				{
					$arNewStructure[$pageID] = $arPageProp + Array("CHILD" => Array());
					$rootPageCnt[$pageID] = "";
				}
			}
			else
			{
				//Create child
				if (isset($rootPageCnt[$rootID]) && array_key_exists($rootID.$rootPageCnt[$rootID], $arNewStructure))
				{
					if (array_key_exists($pageID, $arNewStructure[$rootID.$rootPageCnt[$rootID]]["CHILD"]))
					{
						(int)$childNumber++;
						$arNewStructure[$rootID.$rootPageCnt[$rootID]]["CHILD"][$pageID.$childNumber] = $arPageProp;
					}
					else
						$arNewStructure[$rootID.$rootPageCnt[$rootID]]["CHILD"][$pageID] = $arPageProp;
				}
				else
				{
					if (array_key_exists($pageID, $arNewStructure[$rootID]["CHILD"]))
					{
						(int)$childNumber++;
						$arNewStructure[$rootID]["CHILD"][$pageID.$childNumber] = $arPageProp;
					}
					else
						$arNewStructure[$rootID]["CHILD"][$pageID] = $arPageProp;
				}
			}
		}

		$arAddService = Array();
		foreach ($arStructure as $pageID => $arPage)
		{
			if (isset($arPage["TYPE"]) && $arPage["TYPE"] == "SERVICE" && !array_key_exists($pageID, $arNewStructure))
				$arAddService[$pageID] = $arPage;
		}

		return $arNewStructure + $arAddService;
	}



	public function __MoveDirFiles(&$arFiles)
	{
		if (!is_array($arFiles) || !array_key_exists("FILES", $arFiles))
			return;

		foreach ($arFiles["FILES"] as $arFile)
		{
			//Delete
			if (array_key_exists("DELETE", $arFile) && strlen($arFile["DELETE"]) > 0)
			{
				if ($arFile["DELETE"] == "/" || $arFile["DELETE"] == "/bitrix" || $arFile["DELETE"] == "/bitrix/")
					continue;

				DeleteDirFilesEx($arFile["DELETE"]);
				continue;
			}

			//Copy
			if (!array_key_exists("FROM", $arFile) && !array_key_exists("TO", $arFile))
				continue;

			$rewrite = (array_key_exists("REWRITE", $arFile) && $arFile["REWRITE"] == "N" ? false : true);
			$recursive = (array_key_exists("RECURSIVE", $arFile) && $arFile["RECURSIVE"] == "N" ? false : true);

			$arFile["TO"] = Rel2Abs("/", $arFile["TO"]);

			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"].$this->path."/".$arFile["FROM"],
				$_SERVER["DOCUMENT_ROOT"].$arFile["TO"],
				$rewrite,
				$recursive
			);
		}
	}

	/* Public methods */

	public function GetID()
	{
		return $this->name;
	}

	public function GetPath()
	{
		return $this->path;
	}


	public function SetError($strError, $id = false)
	{
		$this->arErrors[] = Array($strError, $id);
	}

	public function GetErrors()
	{
		return $this->arErrors;
	}

	/* Public site builder methods*/

	public function GetSiteTemplateID()
	{
		return $this->templateID;
	}

	public function GetSiteGroupID()
	{
		return $this->groupID;
	}

	public function GetSiteID()
	{
		return $this->siteID;
	}

	public function GetSiteServiceID()
	{
		return $this->serviceID;
	}

	public function GetDescription()
	{
		return $this->arDescription;
	}


	public function GetTemplateGroups($arFilter = Array())
	{
		$arResult = Array();
		$siteID = (array_key_exists("SITE_ID", $arFilter) ? $arFilter["SITE_ID"] : null);

		if (empty($arFilter) || $siteID == null)
			return $this->arTemplateGroups;

		foreach ($this->arTemplateGroups as $groupID => $arGroup)
		{
			if (is_array($arGroup["SITE_ID"]) && in_array($siteID, $arGroup["SITE_ID"]))
				$arResult[$groupID] = $arGroup;
			elseif ($arGroup["SITE_ID"] === $siteID)
				$arResult[$groupID] = $arGroup;
		}

		return $arResult;
	}

	public function GetTemplates($arFilter = Array())
	{
		$arResult = Array();
		$siteID = (array_key_exists("SITE_ID", $arFilter) ? $arFilter["SITE_ID"] : null);
		$groupID = (array_key_exists("GROUP_ID", $arFilter) ? $arFilter["GROUP_ID"] : null);

		if (empty($arFilter) || ($siteID == null && $groupID == null))
			return $this->arTemplates;

		foreach ($this->arTemplates as $arTemplate)
		{
			if (is_array($arTemplate["SITE_ID"]) && in_array($siteID, $arTemplate["SITE_ID"]))
				$arResult[] = $arTemplate;
			elseif ($arTemplate["SITE_ID"] === $siteID)
				$arResult[] = $arTemplate;
			elseif (is_array($arTemplate["GROUP_ID"]) && in_array($groupID, $arTemplate["GROUP_ID"]))
				$arResult[] = $arTemplate;
			elseif ($arTemplate["GROUP_ID"] === $groupID)
				$arResult[] = $arTemplate;
		}

		return $arResult;
	}


	public function GetServices($arFilter = Array())
	{
		$siteID = (array_key_exists("SITE_ID", $arFilter) ? $arFilter["SITE_ID"] : null);

		if (empty($arFilter) || $siteID == null)
			return $this->arServices;

		$arResult = Array();
		foreach ($this->arServices as $serviceID => $arService)
		{
			if (!array_key_exists("SITE_ID",$arService))
				continue;

			if (is_array($arService["SITE_ID"]) && in_array($siteID, $arService["SITE_ID"]))
				$arResult[$serviceID] = $arService;
			elseif ($arService["SITE_ID"] == $siteID)
				$arResult[$serviceID] = $arService;
		}

		return $arResult;
	}


	public function GetStructure($arFilter = Array())
	{
		$arResult = Array();

		if (!isset($this->arStructure["STRUCTURE"]) || !is_array($this->arStructure["STRUCTURE"]))
			return $arResult;

		$serviceID = (array_key_exists("SERVICE_ID", $arFilter) ? $arFilter["SERVICE_ID"] : null);
		$siteID = (array_key_exists("SITE_ID", $arFilter) ? $arFilter["SITE_ID"] : null);

		if (empty($arFilter) || ($serviceID == null && $siteID == null))
			return $this->arStructure["STRUCTURE"];

		if (!is_array($serviceID) && $serviceID !== null)
			$serviceID = Array($serviceID);

		if (!is_array($siteID) && $siteID !== null)
			$siteID = Array($siteID);

		foreach ($this->arStructure["STRUCTURE"] as $pageID => $arPage)
		{
			if (array_key_exists("SERVICE_ID",$arPage) && $serviceID !== null)
			{
				$result = array_intersect(!is_array($arPage["SERVICE_ID"]) ? Array($arPage["SERVICE_ID"]) : $arPage["SERVICE_ID"], $serviceID);
				if (count($result) > 0)
				{
					$arResult[$pageID] = $arPage;
					continue;
				}
			}

			if (array_key_exists("SITE_ID",$arPage) && $siteID !== null)
			{
				$result = array_intersect(!is_array($arPage["SITE_ID"]) ? Array($arPage["SITE_ID"]) : $arPage["SITE_ID"], $siteID);
				if (count($result) > 0)
				{
					$arResult[$pageID] = $arPage;
					continue;
				}
			}
		}

		return $arResult;
	}

	public function IncludeWizardLang($relativePath = "", $lang = false)
	{
		if ($lang === false)
			$lang = LANGUAGE_ID;

		$wizardPath = $_SERVER["DOCUMENT_ROOT"].$this->path;

		if ($lang != "en" && $lang != "ru")
		{
			if (file_exists(($fname = $wizardPath."/lang/".LangSubst($lang)."/".$relativePath)))
				__IncludeLang($fname, false, true);
		}

		if (file_exists(($fname = $wizardPath."/lang/".$lang."/".$relativePath)))
			__IncludeLang($fname, false, true);
	}

}

?>