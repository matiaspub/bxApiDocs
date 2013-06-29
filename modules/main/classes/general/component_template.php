<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

global $arBXAvailableTemplateEngines;
global $arBXRuntimeTemplateEngines;

$arBXAvailableTemplateEngines = array(
	"php" => array(
		"templateExt" => array("php"),
		"function" => ""
	)
);

$arBXRuntimeTemplateEngines = false;

class CBitrixComponentTemplate
{
	var $__name = "";
	var $__page = "";
	var $__engineID = "";

	var $__file = "";
	var $__fileAlt = "";
	var $__folder = "";
	var $__siteTemplate = "";
	var $__templateInTheme = false;
	var $__hasCSS = null;
	var $__hasJS = null;

	/** @var CBitrixComponent */
	var $__component = null;
	var $__component_epilog = false;

	var $__bInited = false;
	private $__view = array();

	public function CBitrixComponentTemplate()
	{
		$this->__bInited = false;

		$this->__file = "";
		$this->__fileAlt = "";
		$this->__folder = "";
	}

	/***********  GET  ***************/
	
	/**
	 * <p> Метод возвращает имя шаблона компонента. </p> <a name="examples"></a>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * // В коде компонента
	 * $template = &amp; $this-&gt;GetTemplate();
	 * $templateName = $template-&gt;GetName();
	 * </pre>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getname.php
	 * @author Bitrix
	 */
	public function GetName()
	{
		if (!$this->__bInited)
			return null;

		return $this->__name;
	}

	public function GetPageName()
	{
		if (!$this->__bInited)
			return null;

		return $this->__page;
	}

	
	/**
	 * <p> Метод возвращает путь к файлу шаблона относительно корня сайта. </p> <a name="examples"></a>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * // В коде компонента
	 * $template = &amp; $this-&gt;GetTemplate();
	 * $templateFile = $template-&gt;GetFile();
	 * </pre>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getfile.php
	 * @author Bitrix
	 */
	public function GetFile()
	{
		if (!$this->__bInited)
			return null;

		return $this->__file;
	}

	
	/**
	 * <p> Метод возвращает путь к папке шаблона относительно корня сайта, если шаблон лежит в папке. Если шаблон представляет собой самостоятельный файл, то метод возвращает пустую строку. </p> <a name="examples"></a>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * // В коде компонента
	 * $template = &amp; $this-&gt;GetTemplate();
	 * $templateFolder = $template-&gt;GetFolder();
	 * </pre>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getfolder.php
	 * @author Bitrix
	 */
	public function GetFolder()
	{
		if (!$this->__bInited)
			return null;

		return $this->__folder;
	}

	
	/**
	 * <p> Метод возвращает шаблон сайта, в котором лежит шаблон компонента. Если это системный шаблон компонента (т.е. лежит в папке компонента), то возвращается пустая строка. </p> <a name="examples"></a>
	 *
	 *
	 *
	 *
	 * @return string 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * // В коде компонента
	 * $template = &amp; $this-&gt;GetTemplate();
	 * $siteTemplate = $template-&gt;GetSiteTemplate();
	 * </pre>
	 *
	 *
	 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getsitetemplate.php
	 * @author Bitrix
	 */
	public function GetSiteTemplate()
	{
		if (!$this->__bInited)
			return null;

		return $this->__siteTemplate;
	}

	public function IsInTheme()
	{
		if (!$this->__bInited)
			return null;

		return $this->__templateInTheme;
	}

	function &GetCachedData()
	{
		$arReturn = null;

		if (!$this->__bInited)
			return $arReturn;

		$arReturn = array();

		if($this->__folder <> '')
		{
			$fname = $_SERVER["DOCUMENT_ROOT"].$this->__folder."/style.css";
			if (file_exists($fname))
				$arReturn["additionalCSS"] = $this->__folder."/style.css";

			$fname = $_SERVER["DOCUMENT_ROOT"].$this->__folder."/script.js";
			if (file_exists($fname))
				$arReturn["additionalJS"] = $this->__folder."/script.js";
		}

		return $arReturn;
	}

	/***********  INIT  ***************/
	public function ApplyCachedData($arData)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if ($arData && is_array($arData))
		{
			if (array_key_exists("additionalCSS", $arData) && strlen($arData["additionalCSS"]) > 0)
			{
				$APPLICATION->SetAdditionalCSS($arData["additionalCSS"]);

				//Check if parent component exists and plug css it to it's "collection"
				if($this->__component && $this->__component->__parent)
					$this->__component->__parent->addChildCSS($this->__folder."/style.css");
			}

			if (array_key_exists("additionalJS", $arData) && strlen($arData["additionalJS"]) > 0)
			{
				$APPLICATION->AddHeadScript($arData["additionalJS"]);

				//Check if parent component exists and plug js it to it's "collection"
				if($this->__component && $this->__component->__parent)
					$this->__component->__parent->addChildJS($this->__folder."/script.js");
			}
		}
	}

	public static function InitTemplateEngines($arTemplateEngines = array())
	{
		global $arBXAvailableTemplateEngines, $arBXRuntimeTemplateEngines;

		if (array_key_exists("arCustomTemplateEngines", $GLOBALS)
			&& is_array($GLOBALS["arCustomTemplateEngines"])
			&& count($GLOBALS["arCustomTemplateEngines"]) > 0)
		{
			$arBXAvailableTemplateEngines = $arBXAvailableTemplateEngines + $GLOBALS["arCustomTemplateEngines"];
		}

		if (is_array($arTemplateEngines) && count($arTemplateEngines) > 0)
			$arBXAvailableTemplateEngines = $arBXAvailableTemplateEngines + $arTemplateEngines;

		$arBXRuntimeTemplateEngines = array();

		foreach ($arBXAvailableTemplateEngines as $engineID => $engineValue)
			foreach ($engineValue["templateExt"] as $ext)
				$arBXRuntimeTemplateEngines[$ext] = $engineID;
	}

	public function Init(&$component, $siteTemplate = false, $customTemplatePath = "")
	{
		global $arBXRuntimeTemplateEngines;

		$this->__bInited = false;

		if ($siteTemplate === false && defined("SITE_TEMPLATE_ID"))
			$this->__siteTemplate = SITE_TEMPLATE_ID;
		else
			$this->__siteTemplate = $siteTemplate;

		if (strlen($this->__siteTemplate) <= 0)
			$this->__siteTemplate = ".default";

		$this->__file = "";
		$this->__fileAlt = "";
		$this->__folder = "";

		if (!$arBXRuntimeTemplateEngines)
			$this->InitTemplateEngines();

		if (!($component instanceof cbitrixcomponent))
			return false;

		$this->__component = &$component;

		$this->__name = $this->__component->GetTemplateName();
		if (strlen($this->__name) <= 0)
			$this->__name = ".default";

		$this->__name = preg_replace("'[\\\\/]+'", "/", $this->__name);
		$this->__name = trim($this->__name, "/");

		if (!$this->CheckName($this->__name))
			$this->__name = ".default";

		$this->__page = $this->__component->GetTemplatePage();
		if (strlen($this->__page) <= 0)
			$this->__page = "template";

		if (!$this->__SearchTemplate($customTemplatePath))
			return false;

		$this->__GetTemplateEngine();

		$this->__bInited = true;

		return true;
	}

	public static function CheckName($name)
	{
		return preg_match("#^([A-Za-z0-9_.-]+)(/[A-Za-z0-9_.-]+)?$#i", $name);
	}

	/***********  SEARCH  ***************/
	// Search file by its path and name without extention
	public function __SearchTemplateFile($path, $fileName)
	{
		global $arBXRuntimeTemplateEngines;

		if (!$arBXRuntimeTemplateEngines)
			$this->InitTemplateEngines();

		$fname = $_SERVER["DOCUMENT_ROOT"].$path."/".$fileName.".php";
		if (file_exists($fname) && is_file($fname))
		{
			return $fileName.".php";
		}
		else
		{
			// Look at glob() function for PHP >= 4.3.0 !!!

			foreach ($arBXRuntimeTemplateEngines as $templateExt => $engineID)
			{
				if ($templateExt == "php")
					continue;

				if (file_exists($_SERVER["DOCUMENT_ROOT"].$path."/".$fileName.".".$templateExt)
					&& is_file($_SERVER["DOCUMENT_ROOT"].$path."/".$fileName.".".$templateExt))
				{
					return $fileName.".".$templateExt;
				}
			}
		}

		return false;
	}

	public function __SearchTemplate($customTemplatePath = "")
	{
		$this->__file = "";
		$this->__fileAlt = "";
		$this->__folder = "";
		$this->__hasCSS = null;
		$this->__hasJS = null;

		$arFolders = array();
		$relativePath = $this->__component->GetRelativePath();

		$parentRelativePath = "";
		$parentTemplateName = "";
		$parentComponent = & $this->__component->GetParent();
		$defSiteTemplate = ($this->__siteTemplate == ".default");
		if ($parentComponent)
		{
			$parentRelativePath = $parentComponent->GetRelativePath();
			$parentTemplateName = $parentComponent->GetTemplate()->GetName();

			if(!$defSiteTemplate)
			{
				$arFolders[] = array(
					"path" => "/local/templates/".$this->__siteTemplate."/components".$parentRelativePath."/".$parentTemplateName.$relativePath,
					"in_theme" => true,
				);
			}
			$arFolders[] = array(
				"path" => "/local/templates/.default/components".$parentRelativePath."/".$parentTemplateName.$relativePath,
				"in_theme" => true,
				"site_template" => ".default",
			);
			$arFolders[] = array(
				"path" => "/local/components".$parentRelativePath."/templates/".$parentTemplateName.$relativePath,
				"in_theme" => true,
				"site_template" => "",
			);
		}
		if(!$defSiteTemplate)
		{
			$arFolders[] = array(
				"path" => "/local/templates/".$this->__siteTemplate."/components".$relativePath,
			);
		}
		$arFolders[] = array(
			"path" => "/local/templates/.default/components".$relativePath,
			"site_template" => ".default",
		);
		$arFolders[] = array(
			"path" => "/local/components".$relativePath."/templates",
			"site_template" => "",
		);

		if ($parentComponent)
		{
			if(!$defSiteTemplate)
			{
				$arFolders[] = array(
					"path" => BX_PERSONAL_ROOT."/templates/".$this->__siteTemplate."/components".$parentRelativePath."/".$parentTemplateName.$relativePath,
					"in_theme" => true,
				);
			}
			$arFolders[] = array(
				"path" => BX_PERSONAL_ROOT."/templates/.default/components".$parentRelativePath."/".$parentTemplateName.$relativePath,
				"in_theme" => true,
				"site_template" => ".default",
			);
			$arFolders[] = array(
				"path" => "/bitrix/components".$parentRelativePath."/templates/".$parentTemplateName.$relativePath,
				"in_theme" => true,
				"site_template" => "",
			);
		}
		if(!$defSiteTemplate)
		{
			$arFolders[] = array(
				"path" => BX_PERSONAL_ROOT."/templates/".$this->__siteTemplate."/components".$relativePath,
			);
		}
		$arFolders[] = array(
			"path" => BX_PERSONAL_ROOT."/templates/.default/components".$relativePath,
			"site_template" => ".default",
		);
		$arFolders[] = array(
			"path" => "/bitrix/components".$relativePath."/templates",
			"site_template" => "",
		);

		if (strlen($customTemplatePath) > 0 && $templatePageFile = $this->__SearchTemplateFile($customTemplatePath, $this->__page))
		{
			$this->__fileAlt = $customTemplatePath."/".$templatePageFile;

			foreach ($arFolders as $folder)
			{
				if (is_dir($_SERVER["DOCUMENT_ROOT"].$folder["path"]."/".$this->__name))
				{
					$this->__file = $folder["path"]."/".$this->__name."/".$templatePageFile;
					$this->__folder = $folder["path"]."/".$this->__name;
				}

				if (strlen($this->__file) > 0)
				{
					if(isset($folder["site_template"]))
						$this->__siteTemplate = $folder["site_template"];

					if(isset($folder["in_theme"]) && $folder["in_theme"] === true)
						$this->__templateInTheme = true;
					else
						$this->__templateInTheme = false;

					break;
				}
			}
			return (strlen($this->__file) > 0);
		}

		static $cache = array();
		$cache_id = $relativePath."|".$this->__siteTemplate."|".$parentRelativePath."|".$parentTemplateName."|".$this->__page."|".$this->__name;
		if(!isset($cache[$cache_id]))
		{
			foreach ($arFolders as $folder)
			{
				$fname = $folder["path"]."/".$this->__name;
				if (file_exists($_SERVER["DOCUMENT_ROOT"].$fname))
				{
					if (is_dir($_SERVER["DOCUMENT_ROOT"].$fname))
					{
						if ($templatePageFile = $this->__SearchTemplateFile($fname, $this->__page))
						{
							$this->__file = $fname."/".$templatePageFile;
							$this->__folder = $fname;
							$this->__hasCSS = file_exists($_SERVER["DOCUMENT_ROOT"].$fname."/style.css");
							$this->__hasJS = file_exists($_SERVER["DOCUMENT_ROOT"].$fname."/script.js");
						}
					}
					elseif (is_file($_SERVER["DOCUMENT_ROOT"].$fname))
					{
						$this->__file = $fname;
						if (strpos($this->__name, "/") !== false)
							$this->__folder = $folder["path"]."/".substr($this->__name, 0, bxstrrpos($this->__name, "/"));
					}
				}
				else
				{
					if ($templatePageFile = $this->__SearchTemplateFile($folder["path"], $this->__name))
						$this->__file = $folder["path"]."/".$templatePageFile;
				}

				if ($this->__file != "")
				{
					if(isset($folder["site_template"]))
						$this->__siteTemplate = $folder["site_template"];

					if(isset($folder["in_theme"]) && $folder["in_theme"] === true)
						$this->__templateInTheme = true;
					else
						$this->__templateInTheme = false;

					break;
				}
			}
			$cache[$cache_id] = array(
				$this->__folder,
				$this->__file,
				$this->__siteTemplate,
				$this->__templateInTheme,
				$this->__hasCSS,
				$this->__hasJS,
			);
		}
		else
		{
			$this->__folder = $cache[$cache_id][0];
			$this->__file = $cache[$cache_id][1];
			$this->__siteTemplate = $cache[$cache_id][2];
			$this->__templateInTheme = $cache[$cache_id][3];
			$this->__hasCSS = $cache[$cache_id][4];
			$this->__hasJS = $cache[$cache_id][5];
		}
		return ($this->__file != "");
	}

	/***********  INCLUDE  ***************/
	public function __IncludePHPTemplate(&$arResult, &$arParams, $parentTemplateFolder = "")
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB;

		if (!$this->__bInited)
			return false;

		// these vars are used in the template file
		$templateName = $this->__name;
		$templateFile = $this->__file;
		$templateFolder = $this->__folder;

		$componentPath = $this->__component->GetPath();

		$component = & $this->__component;

		if ($this->__fileAlt <> '')
		{
			include($_SERVER["DOCUMENT_ROOT"].$this->__fileAlt);
			return null;
		}

		$templateData = false;

		include($_SERVER["DOCUMENT_ROOT"].$this->__file);

		$component_epilog = $this->__folder."/component_epilog.php";
		if(file_exists($_SERVER["DOCUMENT_ROOT"].$component_epilog))
		{
			//These will be available with extract then component will
			//execute epilog without template
			$component->SetTemplateEpilog(array(
				"epilogFile" => $component_epilog,
				"templateName" => $this->__name,
				"templateFile" => $this->__file,
				"templateFolder" => $this->__folder,
				"templateData" => $templateData,
			));
		}
		return null;
	}

	public function IncludeTemplate(&$arResult)
	{
		global $arBXAvailableTemplateEngines;

		if (!$this->__bInited)
			return false;

		$arParams = $this->__component->arParams;

		if($this->__folder <> '')
		{
			$arLangMessages = $this->IncludeLangFile();
			$this->__IncludeMutatorFile($arResult, $arParams);
			if (!isset($this->__hasCSS) || $this->__hasCSS)
				$this->__IncludeCSSFile();
			if (!isset($this->__hasJS) || $this->__hasJS)
				$this->__IncludeJSFile();
		}
		else
		{
			$arLangMessages = array();
		}

		$parentTemplateFolder = "";
		$parentComponent = $this->__component->GetParent();
		if ($parentComponent)
		{
			$parentTemplate = $parentComponent->GetTemplate();
			$parentTemplateFolder = $parentTemplate->GetFolder();
		}

		if (strlen($arBXAvailableTemplateEngines[$this->__engineID]["function"]) > 0
			&& function_exists($arBXAvailableTemplateEngines[$this->__engineID]["function"]))
		{
			$result = call_user_func(
				$arBXAvailableTemplateEngines[$this->__engineID]["function"],
				$this->__file,
				$arResult,
				$arParams,
				$arLangMessages,
				$this->__folder,
				$parentTemplateFolder,
				$this
			);
		}
		else
		{
			$result = $this->__IncludePHPTemplate($arResult, $arParams, $parentTemplateFolder);
		}

		return $result;
	}

	function __IncludeLangFile($path)
	{
		static $messCache = array();
		if (!isset($messCache[$path]))
			$messCache[$path] = __IncludeLang($path, true);

		global $MESS;
		//$MESS = $messCache[$path] + $MESS; //  T_O_O   S_L_O_O_O_O_W
		foreach($messCache[$path] as $key => $value)
			$MESS[$key] = $value;

		return $messCache[$path];
	}

	public function IncludeLangFile($relativePath = "", $lang = false)
	{
		$arLangMessages = array();

		if($this->__folder <> '')
		{
			$absPath = $_SERVER["DOCUMENT_ROOT"].$this->__folder."/lang/";

			if ($lang === false)
				$lang = LANGUAGE_ID;

			if ($relativePath == "")
				$relativePath = bx_basename($this->__file);

			if ($lang != "en" && $lang != "ru")
				$arLangMessages = $this->__IncludeLangFile($absPath.LangSubst($lang)."/".$relativePath);

			$arLangMessages = $this->__IncludeLangFile($absPath.$lang."/".$relativePath) + $arLangMessages;
		}

		return $arLangMessages;
	}

	public function __IncludeMutatorFile(&$arResult, &$arParams)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB;

		if($this->__folder <> '')
		{
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$this->__folder."/result_modifier.php"))
				include($_SERVER["DOCUMENT_ROOT"].$this->__folder."/result_modifier.php");
		}
	}

	public function __IncludeCSSFile()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(
			$this->__folder <> ''
			&& (
				$this->__hasCSS
				|| file_exists($_SERVER["DOCUMENT_ROOT"].$this->__folder."/style.css")
			)
		)
		{
			$APPLICATION->SetAdditionalCSS($this->__folder."/style.css");

			//Check if parent component exists and plug css it to it's "collection"
			if($this->__component && $this->__component->__parent)
				$this->__component->__parent->addChildCSS($this->__folder."/style.css");
		}
	}

	public function __IncludeJSFile()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if($this->__folder <> '')
		{
			if (
				$this->__hasJS
				|| file_exists($_SERVER["DOCUMENT_ROOT"].$this->__folder."/script.js")
			)
			{
				$APPLICATION->AddHeadScript($this->__folder."/script.js");
				//Check if parent component exists and plug js it to it's "collection"
				if($this->__component && $this->__component->__parent)
					$this->__component->__parent->addChildJS($this->__folder."/script.js");
			}
		}
	}

	/***********  UTIL  ***************/
	public static function __GetTemplateExtension($templateName)
	{
		$templateName = trim($templateName, ". \r\n\t");
		$arTemplateName = explode(".", $templateName);
		return strtolower($arTemplateName[count($arTemplateName) - 1]);
	}

	public function __GetTemplateEngine()
	{
		global $arBXRuntimeTemplateEngines;

		if (!$arBXRuntimeTemplateEngines)
			$this->InitTemplateEngines();

		$templateExt = $this->__GetTemplateExtension($this->__file);

		if (array_key_exists($templateExt, $arBXRuntimeTemplateEngines))
			$this->__engineID = $arBXRuntimeTemplateEngines[$templateExt];
		else
			$this->__engineID = "php";
	}

	public function SetViewTarget($target, $pos = 500)
	{
		$this->EndViewTarget();
		$view = &$this->__view;

		if(!isset($view[$target]))
			$view[$target] = array();
		$view[$target][] = array(false, $pos);

		ob_start();
	}

	public function EndViewTarget()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$view = &$this->__view;
		if(!empty($view))
		{
			//Get the key to last started view target
			end($view);
			$target_key = key($view);

			//Get the key to last added "sub target"
			//in most cases there will be only one
			end($view[$target_key]);
			$sub_target_key = key($view[$target_key]);

			$sub_target = &$view[$target_key][$sub_target_key];
			if($sub_target[0] === false)
			{
				$sub_target[0] = ob_get_contents();
				$APPLICATION->AddViewContent($target_key, $sub_target[0], $sub_target[1]);
				$this->__component->addViewTarget($target_key, $sub_target[0], $sub_target[1]);
				ob_end_clean();
			}
		}
	}

	/**** EDIA AREA ICONS ************/
	/*
	inside template.php:

	$this->AddEditAction(
		'USER'.$arUser['ID'], // entry id. prefix like 'USER' needed only in case when template has two or more lists of differrent editable entities

		$arUser['EDIT_LINK'], // edit link, should be set in a component. will be open in js popup.

		GetMessage('INTR_ISP_EDIT_USER'), // button caption

		array( // additional params
			'WINDOW' => array("width"=>780, "height"=>500), // popup params
			'ICON' => 'bx-context-toolbar-edit-icon' // icon css
			'SRC' => '/bitrix/images/myicon.gif' // icon image
		)
	);

	icon css is set to "edit" icon by default. button caption too.

	$this->GetEditAreaId with the same id MUST be used for marking entry contaner or row, like this:
	<tr id="<?=$this->GetEditAreaId('USER'.$arUser['ID']);?>">
	*/
	public function GetEditAreaId($entryId)
	{
		return $this->__component->GetEditAreaId($entryId);
	}

	public function AddEditAction($entryId, $editLink, $editTitle = false, $arParams = array())
	{
		$this->__component->addEditButton(array('AddEditAction', $entryId, $editLink, $editTitle, $arParams));
	}

	/*
	$arParams['CONFIRM'] = false - disable confirm;
	$arParams['CONFIRM'] = 'Text' - confirm with custom text;
	no $arParams['CONFIRM'] at all - confirm with default text
	*/
	public function AddDeleteAction($entryId, $deleteLink, $deleteTitle = false, $arParams = array())
	{
		$this->__component->addEditButton(array('AddDeleteAction', $entryId, $deleteLink, $deleteTitle, $arParams));
	}
}
