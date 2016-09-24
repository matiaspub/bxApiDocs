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
		"function" => "",
		"sort" => 100
	)
);

$arBXRuntimeTemplateEngines = false;


/**
 * <p>С версии 15.5.1 стало возможным использования внешних файлов css без дополнительных манипуляций с кодом. Для этого достаточно в файле <b>template.php</b> нужного компонента прописать:</p> <pre class="syntax">$this-&gt;addExternalCss("/local/styles.css"); $this-&gt;addExternalJS("/local/liba.js");</pre> <br><table width="100%" class="tnormal"><tbody> <tr> <th width="25%">Метод</th> 	<th>Описание</th> <th>С версии</th> </tr> <tr> <td><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getsitetemplate.php">GetSiteTemplate</a></td> <td>Метод возвращает шаблон сайта, в котором лежит шаблон компонента. </td> <td></td> </tr> <tr> <td><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getname.php">GetName</a></td> <td>Метод возвращает имя шаблона компонента. </td> <td></td> </tr> <tr> <td><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getfolder.php">GetFolder</a></td> <td>Метод возвращает путь к папке шаблона относительно корня сайта.</td> <td></td> </tr> <tr> <td><a href="http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getfile.php">GetFile</a></td> <td>Метод возвращает путь к файлу шаблона относительно корня сайта.</td> <td></td> </tr> <tr> <td>addExternalCss</td> <td>Метод для подключения стороннего css.</td> <td>15.5.1</td> </tr> <tr> <td>addExternalJs</td> <td>Метод для подключения стороннего JS.</td> <td>15.5.1</td> </tr> </tbody></table> <br><br>
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/index.php
 * @author Bitrix
 */
class CBitrixComponentTemplate
{
	public $__name = "";
	public $__page = "";
	public $__engineID = "";

	public $__file = "";
	public $__fileAlt = "";
	public $__folder = "";
	public $__siteTemplate = "";
	public $__templateInTheme = false;
	public $__hasCSS = null;
	public $__hasJS = null;

	/** @var CBitrixComponent */
	public $__component = null;
	public $__component_epilog = false;

	public $__bInited = false;
	private $__view = array();
	private $frames = array();
	private $frameMode = null;

	private $languageId = false;
	private $externalCss = array();
	private $externalJs = array();

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->__bInited = false;

		$this->__file = "";
		$this->__fileAlt = "";
		$this->__folder = "";
	}

	/**
	 * Returns name of the template.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|string
	 *
	 * @see CBitrixComponentTemplate::Init
	 */
	
	/**
	* <p>Метод возвращает имя шаблона компонента. Нестатический метод.</p> <a name="examples"></a>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* // В коде компонента
	* $template = &amp; $this-&gt;GetTemplate();
	* $templateName = $template-&gt;GetName();
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getname.php
	* @author Bitrix
	*/
	public function GetName()
	{
		if (!$this->__bInited)
			return null;

		return $this->__name;
	}

	/**
	 * Returns template page.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|string
	 *
	 * @see CBitrixComponentTemplate::Init
	 */
	public function GetPageName()
	{
		if (!$this->__bInited)
			return null;

		return $this->__page;
	}

	/**
	 * Returns path to the template file within DOCUMENT_ROOT.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|string
	 *
	 * @see CBitrixComponentTemplate::Init
	 */
	
	/**
	* <p>Метод возвращает путь к файлу шаблона относительно корня сайта. Нестатический метод.</p> <a name="examples"></a>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* // В коде компонента
	* $template = &amp; $this-&gt;GetTemplate();
	* $templateFile = $template-&gt;GetFile();
	* </pre>
	*
	*
	* @static
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
	 * Returns path to the template folder within DOCUMENT_ROOT.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|string
	 *
	 * @see CBitrixComponentTemplate::Init
	 */
	
	/**
	* <p>Метод возвращает путь к папке шаблона относительно корня сайта, если шаблон лежит в папке. Если шаблон представляет собой самостоятельный файл, то метод возвращает пустую строку. Нестатический метод.</p> <a name="examples"></a>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* // В коде компонента
	* $template = &amp; $this-&gt;GetTemplate();
	* $templateFolder = $template-&gt;GetFolder();
	* </pre>
	*
	*
	* @static
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
	 * Returns site template name.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|string
	 *
	 * @see CBitrixComponentTemplate::Init
	 */
	
	/**
	* <p>Метод возвращает шаблон сайта, в котором лежит шаблон компонента. Если это системный шаблон компонента (т.е. лежит в папке компонента), то возвращается пустая строка. Нестатический метод.</p> <a name="examples"></a>
	*
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* // В коде компонента
	* $template = &amp; $this-&gt;GetTemplate();
	* $siteTemplate = $template-&gt;GetSiteTemplate();
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponenttemplate/getsitetemplate.php
	* @author Bitrix
	*/
	public function GetSiteTemplate()
	{
		if (!$this->__bInited)
			return null;

		return $this->__siteTemplate;
	}

	/**
	 * Returns true if template belongs to another template of an complex component.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|boolean
	 *
	 * @see CBitrixComponentTemplate::Init
	 */
	public function IsInTheme()
	{
		if (!$this->__bInited)
			return null;

		return $this->__templateInTheme;
	}

	/**
	 * Sets template language identifier.
	 *
	 * @param string $languageId
	 *
	 * @return void
	 */
	public function setLanguageId($languageId)
	{
		$this->languageId = $languageId;
	}

	/**
	 * Returns template language.
	 *
	 * @return string
	 *
	 * @see CBitrixcomponentTemplate::setLanguageId
	 */
	public function getLanguageId()
	{
		return $this->languageId;
	}

	/**
	 * Returns data to be stored in the component cache.
	 *
	 * Requires Init call before usage.
	 *
	 * @return null|array
	 *
	 * @see CBitrixComponentTemplate::Init
	 * @see CBitrixComponentTemplate::ApplyCachedData
	 */
	public function GetCachedData()
	{
		if (!$this->__bInited)
			return null;

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

		if (!empty($this->frames))
		{
			$arReturn["frames"] = array();
			/** @var \Bitrix\Main\Page\FrameHelper $frame */
			foreach($this->frames as $frame)
			{
				$arReturn["frames"][] = $frame->getCachedData();
			}
		}

		$arReturn["frameMode"] = $this->frameMode;
		if (!$this->frameMode)
		{
			$arReturn["frameModeCtx"] = $this->__file;
		}

		if ($this->externalCss)
		{
			$arReturn["externalCss"] = $this->externalCss;
		}

		if ($this->externalJs)
		{
			$arReturn["externalJs"] = $this->externalJs;
		}

		return $arReturn;
	}

	/**
	 * Performs actions on cached hit.
	 *
	 * @param array $arData
	 *
	 * @return void
	 * @see CBitrixComponentTemplate::GetCachedData
	 */
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

			if (array_key_exists("frames", $arData) && is_array($arData["frames"]))
			{
				foreach($arData["frames"] as $frameState)
				{
					\Bitrix\Main\Page\FrameStatic::applyCachedData($frameState);
				}
			}

			if (array_key_exists("frameMode", $arData))
			{
				$this->setFrameMode($arData["frameMode"]);

				if ($this->getFrameMode() === false)
				{
					$context = isset($arData["frameModeCtx"]) ? "(from component cache) ".$arData["frameModeCtx"] : "";
					\Bitrix\Main\Data\StaticHtmlCache::applyComponentFrameMode($context);
				}

			}

			if (isset($arData["externalCss"]))
			{
				foreach ($arData["externalCss"] as $cssPath)
				{
					$APPLICATION->SetAdditionalCSS($cssPath);
					//Check if parent component exists and plug css it to it's "collection"
					if($this->__component && $this->__component->__parent)
						$this->__component->__parent->addChildCSS($cssPath);
				}
			}

			if (isset($arData["externalJs"]))
			{
				foreach ($arData["externalJs"] as $jsPath)
				{
					$APPLICATION->AddHeadScript($jsPath);
					//Check if parent component exists and plug js it to it's "collection"
					if($this->__component && $this->__component->__parent)
						$this->__component->__parent->addChildJS($jsPath);
				}
			}
		}
	}

	/**
	 * Called automatically on first usage of related functions.
	 *
	 * @param array $arTemplateEngines Array of engines to add.
	 *
	 * @return void
	 */
	static public function InitTemplateEngines($arTemplateEngines = array())
	{
		global $arBXAvailableTemplateEngines, $arBXRuntimeTemplateEngines;

		if (
			array_key_exists("arCustomTemplateEngines", $GLOBALS)
			&& is_array($GLOBALS["arCustomTemplateEngines"])
			&& count($GLOBALS["arCustomTemplateEngines"]) > 0
		)
		{
			$arBXAvailableTemplateEngines = $arBXAvailableTemplateEngines + $GLOBALS["arCustomTemplateEngines"];
		}

		if (is_array($arTemplateEngines) && count($arTemplateEngines) > 0)
		{
			$arBXAvailableTemplateEngines = $arBXAvailableTemplateEngines + $arTemplateEngines;
		}

		\Bitrix\Main\Type\Collection::sortByColumn($arBXAvailableTemplateEngines, "sort", "", 200);

		$arBXRuntimeTemplateEngines = array();

		foreach ($arBXAvailableTemplateEngines as $engineID => $engineValue)
		{
			foreach ($engineValue["templateExt"] as $ext)
			{
				$arBXRuntimeTemplateEngines[$ext] = $engineID;
			}
		}
	}

	/**
	 * Have to be called before any template usage.
	 * Returns true on success.
	 *
	 * @param CBitrixComponent $component Parent component.
	 * @param boolean|string $siteTemplate Site template name.
	 * @param string $customTemplatePath Additional path to look for template in.
	 *
	 * @return boolean
	 */
	public function Init(&$component, $siteTemplate = false, $customTemplatePath = "")
	{
		global $arBXRuntimeTemplateEngines;

		$this->__bInited = false;

		if ($siteTemplate === false)
		{
			$this->__siteTemplate = $component->getSiteTemplateId();
		}
		else
		{
			$this->__siteTemplate = $siteTemplate;
		}

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

		if (!self::CheckName($this->__name))
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

	/**
	 * Checks the template name for correctness.
	 * Letters, digits, minus, underscore and dots are allowed.
	 *
	 * @param string $name Name of the template.
	 *
	 * @return boolean
	 */
	public static function CheckName($name)
	{
		return preg_match("#^([A-Za-z0-9_.-]+)(/[A-Za-z0-9_.-]+)?$#i", $name) > 0;
	}

	/**
	 * Search file by its path and name without extention.
	 *
	 * @param string $path Directory.
	 * @param string $fileName File name (without extention).
	 *
	 * @return false|string
	 */
	public function __SearchTemplateFile($path, $fileName)
	{
		global $arBXRuntimeTemplateEngines;

		if (!$arBXRuntimeTemplateEngines)
			$this->InitTemplateEngines();

		$filePath = $_SERVER["DOCUMENT_ROOT"].$path."/".$fileName.".php";
		if (count($arBXRuntimeTemplateEngines) === 1 && file_exists($filePath) && is_file($filePath))
		{
			return $fileName.".php";
		}
		else
		{
			foreach ($arBXRuntimeTemplateEngines as $templateExt => $engineID)
			{
				$filePath = $_SERVER["DOCUMENT_ROOT"].$path."/".$fileName.".".$templateExt;
				if (file_exists($filePath) && is_file($filePath))
				{
					return $fileName.".".$templateExt;
				}
			}
		}

		return false;
	}

	/**
	 * Search template by its name in various locations.
	 * <ol>
	 * <li>/local/templates/&lt;site template&gt;/components/&lt;parent template&gt;/&lt;component path&gt;/
	 * <li>/local/templates/.default/components/&lt;parent template&gt;/&lt;component path&gt;/
	 * <li>/local/components/&lt;parent template&gt;/&lt;component path&gt;/
	 * <li>/local/templates/&lt;site template&gt;/components/&lt;component path&gt;/
	 * <li>/local/templates/.default/components/&lt;component path&gt;/
	 * <li>/local/components/&lt;component path&gt;/
	 * <li>/&lt;BX_PERSONAL_ROOT&gt;/templates/&lt;site template&gt;/components/&lt;parent template&gt;/&lt;component path&gt;/
	 * <li>/&lt;BX_PERSONAL_ROOT&gt;/templates/.default/components/&lt;parent template&gt;/&lt;component path&gt;/
	 * <li>/bitrix/components/&lt;parent template&gt;/&lt;component path&gt;/
	 * <li>/&lt;BX_PERSONAL_ROOT&gt;/templates/&lt;site template&gt;/components/&lt;component path&gt;/
	 * <li>/&lt;BX_PERSONAL_ROOT&gt;/templates/.default/components/&lt;component path&gt;/
	 * <li>/bitrix/components/&lt;component path&gt;/
	 * </ol>
	 *
	 * @param string $customTemplatePath
	 *
	 * @return false|string
	 */
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
		if ($parentComponent && $parentComponent->GetTemplate())
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

	/**
	 * Executes template.php via include function.
	 *
	 * Requires Init call before usage.
	 *
	 * @param array &$arResult Result of the component calculations.
	 * @param array &$arParams Parameters of the component call.
	 * @param string $parentTemplateFolder Parent template.
	 *
	 * @return false|void
	 * @throws \Bitrix\Main\NotSupportedException
	 * @see CBitrixComponentTemplate::Init
	 */
	public function __IncludePHPTemplate(/** @noinspection PhpUnusedParameterInspection */
		&$arResult, &$arParams, $parentTemplateFolder = "")
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB;

		if (!$this->__bInited)
			return false;

		// these vars are used in the template file
		/** @noinspection PhpUnusedLocalVariableInspection */
		$templateName = $this->__name;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$templateFile = $this->__file;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$templateFolder = $this->__folder;
		/** @noinspection PhpUnusedLocalVariableInspection */
		$componentPath = $this->__component->GetPath();

		$component = &$this->__component;

		if ($this->__fileAlt <> '')
		{
			include($_SERVER["DOCUMENT_ROOT"].$this->__fileAlt);
			return null;
		}

		$templateData = false;

		include($_SERVER["DOCUMENT_ROOT"].$this->__file);

		/** @var \Bitrix\Main\Page\FrameHelper $frame */
		foreach($this->frames as $frame)
		{
			if ($frame->isStarted() && !$frame->isEnded())
				$frame->end();
		}

		if (!$this->getFrameMode())
		{
			\Bitrix\Main\Data\StaticHtmlCache::applyComponentFrameMode($this->__file);
		}

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

	/**
	 * Executes template using appropriate template engine.
	 *
	 * Requires Init call before usage.
	 * @param array &$arResult
	 *
	 * @return false|void
	 * @see CBitrixComponentTemplate::Init
	 */
	public function IncludeTemplate(&$arResult)
	{
		global $arBXAvailableTemplateEngines;

		if (!$this->__bInited)
			return false;

		$arLangMessages = null;
		$externalEngine = ($arBXAvailableTemplateEngines[$this->__engineID]["function"] <> '' && function_exists($arBXAvailableTemplateEngines[$this->__engineID]["function"]));

		$arParams = $this->__component->arParams;

		if($this->__folder <> '')
		{
			if ($externalEngine)
			{
				$arLangMessages = $this->IncludeLangFile("", false, true);
			}
			else
			{
				$this->IncludeLangFile();
			}
			$this->__IncludeMutatorFile($arResult, $arParams);
			if (!isset($this->__hasCSS) || $this->__hasCSS)
				$this->__IncludeCSSFile();
			if (!isset($this->__hasJS) || $this->__hasJS)
				$this->__IncludeJSFile();
		}

		$parentTemplateFolder = "";
		$parentComponent = $this->__component->GetParent();
		if ($parentComponent)
		{
			$parentTemplate = $parentComponent->GetTemplate();
			if ($parentTemplate)
				$parentTemplateFolder = $parentTemplate->GetFolder();
		}

		if ($externalEngine)
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

	/**
	 * Includes template language file.
	 *
	 * @param string $relativePath
	 * @param false|string $lang
	 * @param boolean $return
	 *
	 * @return array
	 */
	public function IncludeLangFile($relativePath = "", $lang = false, $return = false)
	{
		$arLangMessages = array();

		if($this->__folder <> '')
		{
			if ($relativePath == "")
			{
				$relativePath = bx_basename($this->__file);
			}

			$absPath = $_SERVER["DOCUMENT_ROOT"].$this->__folder."/".$relativePath;

			if ($lang === false && $return === false)
			{
				\Bitrix\Main\Localization\Loc::loadMessages($absPath);
			}
			else
			{
				if ($lang === false)
				{
					$lang = $this->getLanguageId();
				}
				$arLangMessages = \Bitrix\Main\Localization\Loc::loadLanguageFile($absPath, $lang);
			}
		}

		return $arLangMessages;
	}

	/**
	 * @param array &$arResult
	 * @param array &$arParams
	 *
	 * @return void
	 * @internal
	 */
	public function __IncludeMutatorFile(/** @noinspection PhpUnusedParameterInspection */
		&$arResult, &$arParams)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB;

		if($this->__folder <> '')
		{
			if (file_exists($_SERVER["DOCUMENT_ROOT"].$this->__folder."/result_modifier.php"))
			{
				include($_SERVER["DOCUMENT_ROOT"].$this->__folder."/result_modifier.php");
			}
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function __IncludeCSSFile()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if ($this->__folder <> '')
		{
			if (
				$this->__hasCSS
				|| file_exists($_SERVER["DOCUMENT_ROOT"].$this->__folder."/style.css")
			)
			{
				$APPLICATION->SetAdditionalCSS($this->__folder."/style.css");

				//Check if parent component exists and plug css it to it's "collection"
				if ($this->__component && $this->__component->__parent)
					$this->__component->__parent->addChildCSS($this->__folder."/style.css");
			}
		}
	}

	/**
	 * @return void
	 * @internal
	 */
	public function __IncludeJSFile()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if ($this->__folder <> '')
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

	/**
	 * @param string $templateName File name.
	 *
	 * @return string
	 * @internal
	 */
	static public function __GetTemplateExtension($templateName)
	{
		$templateName = trim($templateName, ". \r\n\t");
		$arTemplateName = explode(".", $templateName);
		return strtolower($arTemplateName[count($arTemplateName) - 1]);
	}

	/**
	 * @return void
	 * @internal
	 */
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

	/**
	 * Begins special output which will be showed by $APPLICATION->ShowViewContent.
	 *
	 * @param string $target Code name of the area.
	 * @param integer $pos Sort index.
	 *
	 * @return void
	 * @see CMain::ShowViewContent
	 * @see CBitrixcomponentTemplate::EndViewTarget
	 */
	public function SetViewTarget($target, $pos = 500)
	{
		$this->EndViewTarget();
		$view = &$this->__view;

		if(!isset($view[$target]))
			$view[$target] = array();
		$view[$target][] = array(false, $pos);

		ob_start();
	}

	/**
	 * Ends special output which will be showed by $APPLICATION->ShowViewContent.
	 *
	 * @return void
	 * @see CMain::ShowViewContent
	 * @see CBitrixcomponentTemplate::SetViewTarget
	 */
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

	/**
	 * Shows menu with edit action in edit mode.
	 * <code>
	 * $this->AddEditAction(
	 * 	'USER'.$arUser['ID'],
	 * 	$arUser['EDIT_LINK'],
	 * 	GetMessage('INTR_ISP_EDIT_USER'),
	 * 	array(
	 * 		'WINDOW' => array("width"=>780, "height"=>500), // popup params
	 * 		'ICON' => 'bx-context-toolbar-edit-icon' // icon css
	 * 		'SRC' => '/bitrix/images/myicon.gif' // icon image
	 * 	)
	 * );
	 * </code>
	 *
	 * @param string $entryId Entry identifier. prefix like 'USER' needed only in case when template has two or more lists of different editable entities.
	 * @param string $editLink Edit form link, Should be set in a component. Will be opened in js popup.
	 * @param false|string $editTitle Button caption.
	 * @param array $arParams Additional parameters.
	 *
	 * @return void
	 * @see CBitrixcomponentTemplate::GetEditAreaId
	 */
	public function AddEditAction($entryId, $editLink, $editTitle = false, $arParams = array())
	{
		$this->__component->addEditButton(array('AddEditAction', $entryId, $editLink, $editTitle, $arParams));
	}

	/**
	 * Shows menu with delete action in edit mode.
	 * <ul>
	 * $arParams['CONFIRM'] = false - disable confirm;
	 * $arParams['CONFIRM'] = 'Text' - confirm with custom text;
	 * no $arParams['CONFIRM'] at all - confirm with default text
	 * </ul>
	 *
	 * @param string $entryId Entry identifier. prefix like 'USER' needed only in case when template has two or more lists of different editable entities.
	 * @param string $deleteLink Delete action link, Should be set in a component.
	 * @param false|string $deleteTitle Button caption.
	 * @param array $arParams Additional parameters.
	 *
	 * @return void
	 * @see CBitrixcomponentTemplate::GetEditAreaId
	 */
	public function AddDeleteAction($entryId, $deleteLink, $deleteTitle = false, $arParams = array())
	{
		$this->__component->addEditButton(array('AddDeleteAction', $entryId, $deleteLink, $deleteTitle, $arParams));
	}

	/**
	 * Returns identifier to mark an html element as a container for highlight.
	 *
	 * <code>
	 * &lt;tr id="&lt;?=$this-&gt;GetEditAreaId('USER'.$arUser['ID']);?&gt;"&gt;
	 * </code>
	 *
	 * @param $entryId
	 *
	 * @return string
	 * @see CBitrixcomponentTemplate::AddEditAction
	 */
	public function GetEditAreaId($entryId)
	{
		return $this->__component->GetEditAreaId($entryId);
	}

	/**
	 * Function returns next pseudo random value.
	 *
	 * @param int $length
	 *
	 * @return string
	 * @see \Bitrix\Main\Type\RandomSequence::randString
	 */
	public function randString($length = 6)
	{
		return $this->__component->randString($length);
	}

	/**
	 * Marks a template as capable of composite mode.
	 *
	 * @param bool $mode
	 *
	 * @return void
	 */
	public function setFrameMode($mode)
	{
		if (in_array($mode, array(true, false, null), true))
		{
			$this->frameMode = $mode;
		}
	}

	/**
	 * Returns frame mode
	 * @return bool
	 */
	public function getFrameMode()
	{
		if ($this->frameMode !== null)
		{
			return $this->frameMode;
		}

		if (!$this->__component)
		{
			//somebody has stolen the instance of component
			return false;
		}

		$frameMode = $this->__component->getDefaultFrameMode();
		if ($frameMode === null)
		{
			$frameMode = false;
		}

		return $frameMode;
	}

	public function getRealFrameMode()
	{
		return $this->frameMode;
	}

	/**
	 * Returns new frame helper object to work with composite frame.
	 *
	 * <code>
	 * $frame = $this->createFrame()->begin("");
	 * echo "10@".(time()+15);
	 * $frame->end();
	 * </code>
	 *
	 * @param string $id
	 * @param bool $autoContainer
	 *
	 * @return Bitrix\Main\Page\FrameHelper
	 * @see Bitrix\Main\Page\FrameHelper
	 */
	public function createFrame($id = null, $autoContainer = true)
	{
		$this->frameMode = true;
		if ($id === null)
			$id = $this->randString();
		$frame = new Bitrix\Main\Page\FrameBuffered($id, $autoContainer);
		array_unshift($this->frames, $frame);
		return $frame;
	}

	/**
	 * Shows css file in the head of html.
	 * Supports caching.
	 *
	 * @param string $cssPath Path to css file.
	 *
	 * @return void
	 * @see CMain::SetAdditionalCSS
	 */
	public function addExternalCss($cssPath)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$this->externalCss[] = $cssPath;
		$APPLICATION->SetAdditionalCSS($cssPath);
		//Check if parent component exists and plug css it to it's "collection"
		if ($this->__component && $this->__component->__parent)
			$this->__component->__parent->addChildCSS($cssPath);
	}

	/**
	 * Shows js file in the head of html.
	 * Supports caching.
	 *
	 * @param string $jsPath Path to js file.
	 *
	 * @return void
	 * @see CMain::AddHeadScript
	 */
	public function addExternalJs($jsPath)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;
		$this->externalJs[] = $jsPath;
		$APPLICATION->AddHeadScript($jsPath);
		//Check if parent component exists and plug js it to it's "collection"
		if($this->__component && $this->__component->__parent)
			$this->__component->__parent->addChildJS($jsPath);
	}

	/**
	 * A bit more civilised method of getting the parent component.
	 * @return CBitrixComponent
	 */
	public function getComponent()
	{
		return $this->__component;
	}
}
