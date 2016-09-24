<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */


/**
 * Класс CBitrixComponent является оболочкой компонента. Для каждого подключаемого компонента создаётся свой экземпляр класса CBitrixComponent, который живет до конца подключения компонента. Внутри компонента методы этого класса доступны через переменную-псевдоним <b>$this</b>.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponent/index.php
 * @author Bitrix
 */
class CBitrixComponent
{
	public $__name = "";
	private $__relativePath = "";
	public $__path = "";

	private $__templateName = "";
	public $__templatePage = "";

	/** @var CBitrixComponentTemplate */
	public $__template = null;

	private $__component_epilog = false;

	public $arParams = array();
	public $arResult = array();
	/** @var array */
	public $arResultCacheKeys = false;

	/** @var CBitrixComponent */
	public $__parent = null;

	private $__bInited = false;

	private $__arIncludeAreaIcons = array();

	private $__NavNum = false;

	/** @var CPHPCache */
	private $__cache = null;
	private $__cacheID = "";
	private $__cachePath = "";

	private $__children_css = array();
	private $__children_js = array();
	private $__children_epilogs = array();

	private $__view = array();

	private static $__componentCounter = array();
	private $__currentCounter = 0;
	private $__editButtons = array();
	private static $__classes_map = array();
	private $classOfComponent = "";
	private $randomSequence = null;
	private $frameMode = null;

	/** @var  \Bitrix\Main\HttpRequest */
	protected $request;

	private $siteId = false;
	private $siteTemplateId = false;
	private $languageId = false;

	/**
	 * Event called from includeComponent before component execution.
	 *
	 * <p>Takes component parameters as argument and should return it formatted as needed.</p>
	 * @param array[string]mixed $arParams
	 * @return array[string]mixed
	 *
	 */
	static public function onPrepareComponentParams($arParams)
	{
		return $arParams;
	}
	/**
	 * Event called from includeComponent before component execution.
	 *
	 * <p>Includes component.php from within lang directory of the component.</p>
	 * @return void
	 *
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang();
	}
	/**
	 * Function calls __includeComponent in order to execute the component.
	 *
	 * @return mixed
	 *
	 */
	public function executeComponent()
	{
		return $this->__includeComponent();
	}
	/**
	 * Constructor with ability to copy the component.
	 *
	 * @param CBitrixComponent $component
	 */
	public function __construct($component = null)
	{
		if(is_object($component) && ($component instanceof cbitrixcomponent))
		{
			$this->__name = $component->__name;
			$this->__relativePath = $component->__relativePath;
			$this->__path = $component->__path;
			$this->__templateName = $component->__templateName;
			$this->__templatePage = $component->__templatePage;
			$this->__template = $component->__template;
			$this->__component_epilog = $component->__component_epilog;
			$this->arParams = $component->arParams;
			$this->arResult = $component->arResult;
			$this->arResultCacheKeys = $component->arResultCacheKeys;
			$this->__parent = $component->__parent;
			$this->__bInited = $component->__bInited;
			$this->__arIncludeAreaIcons = $component->__arIncludeAreaIcons;
			$this->__NavNum = $component->__NavNum;
			$this->__cache = $component->__cache;
			$this->__cacheID = $component->__cacheID;
			$this->__cachePath = $component->__cachePath;
			$this->__children_css = $component->__children_css;
			$this->__children_js = $component->__children_js;
			$this->__children_epilogs = $component->__children_epilogs;
			$this->__view = $component->__view;
			$this->__currentCounter = $component->__currentCounter;
			$this->__editButtons = $component->__editButtons;
			$this->classOfComponent = $component->classOfComponent;
			$this->setSiteId($component->getSiteId());
			$this->setLanguageId($component->getLanguageId());
			$this->setSiteTemplateId($component->getSiteTemplateId());
		}
		else
		{
			$this->setSiteId(SITE_ID);
			$this->setLanguageId(LANGUAGE_ID);
			$this->setSiteTemplateId(SITE_TEMPLATE_ID);
		}

		$this->request = \Bitrix\Main\Context::getCurrent()->getRequest();
	}
	/**
	 * Function returns component name in form bitrix:component.name
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return string
	 *
	 */
	final public function getName()
	{
		if ($this->__bInited)
			return $this->__name;
		else
			return null;
	}
	/**
	 * Function returns path to component in form /bitrix/component.name
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return string
	 *
	 */
	final public function getRelativePath()
	{
		if ($this->__bInited)
			return $this->__relativePath;
		else
			return null;
	}
	/**
	 * Function returns path to component relative to Web server DOCUMENT_ROOT in form /bitrix/components/bitrix/component.name
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return string
	 *
	 */
	final public function getPath()
	{
		if ($this->__bInited)
			return $this->__path;
		else
			return null;
	}
	/**
	 * Function returns the name of the template
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return string
	 *
	 */
	final public function getTemplateName()
	{
		if ($this->__bInited)
			return $this->__templateName;
		else
			return null;
	}
	/**
	 * Function sets the name of the template. Returns true on success.
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @param string $templateName
	 * @return bool
	 *
	 */
	final public function setTemplateName($templateName)
	{
		if (!$this->__bInited)
			return null;

		$this->__templateName = $templateName;
		return true;
	}

	/**
	 * @param string $siteTemplateId
	 */
	public function setSiteTemplateId($siteTemplateId)
	{
		$this->siteTemplateId = $siteTemplateId;
	}

	/**
	 * @return mixed
	 */
	public function getSiteTemplateId()
	{
		return $this->siteTemplateId;
	}

	/**
	 * @param string $siteId
	 */
	public function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}

	/**
	 * @return mixed
	 */
	public function getSiteId()
	{
		return $this->siteId;
	}

	/**
	 * @param string $languageId
	 */
	public function setLanguageId($languageId)
	{
		$this->languageId = $languageId;
	}

	/**
	 * @return mixed
	 */
	public function getLanguageId()
	{
		return $this->languageId;
	}


	/**
	 * Function returns the template page witch was set with initComponentTemplate
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return string
	 *
	 */
	final public function getTemplatePage()
	{
		if ($this->__bInited)
			return $this->__templatePage;
		else
			return null;
	}
	/**
	 * Function returns the template object
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return CBitrixComponentTemplate
	 *
	 */
	final public function getTemplate()
	{
		if ($this->__bInited && $this->__template)
			return $this->__template;
		else
			return null;
	}
	/**
	 * Function returns the parent component (if exists)
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return CBitrixComponent
	 *
	 */
	final public function getParent()
	{
		if ($this->__bInited && $this->__parent)
			return $this->__parent;
		else
			return null;
	}
	/**
	 * Function returns current template css files or null if there is no template.
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return array[string][int]string
	 *
	 */
	final public function getTemplateCachedData()
	{
		if ($this->__bInited && $this->__template)
			return $this->__template->GetCachedData();
		else
			return null;
	}
	/**
	 * Function applies collection of the css files to the current template.
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @param array[string][int]string $templateCachedData
	 * @return void
	 *
	 */
	final public function setTemplateCachedData($templateCachedData)
	{
		if ($this->__bInited && $this->__template)
			$this->__template->ApplyCachedData($templateCachedData);
	}
	/**
	 * Function includes class of the component by component name bitrix:component.base
	 *
	 * @param string $componentName
	 * @return string
	 *
	 */
	final public static function includeComponentClass($componentName)
	{
		$component = new CBitrixComponent;
		$component->initComponent($componentName);
	}
	/**
	 * Function returns class name of the component by it's path.
	 *
	 * <p>At first class.php is checked and if exists then included.
	 * Then if there is subsclass of CBitrixComponent found? it's name is returned.</p>
	 * @param string $componentPath
	 * @return string
	 *
	 */
	final private function __getClassForPath($componentPath)
	{
		if (!isset(self::$__classes_map[$componentPath]))
		{
			$fname = $_SERVER["DOCUMENT_ROOT"].$componentPath."/class.php";
			if (file_exists($fname) && is_file($fname))
			{
				$beforeClasses = get_declared_classes();
				$beforeClassesCount = count($beforeClasses);
				include_once($fname);
				$afterClasses = get_declared_classes();
				$afterClassesCount = count($afterClasses);
				for ($i = $beforeClassesCount; $i < $afterClassesCount; $i++)
				{
					if (is_subclass_of($afterClasses[$i], "cbitrixcomponent"))
						self::$__classes_map[$componentPath] = $afterClasses[$i];
				}
			}
			else
			{
				self::$__classes_map[$componentPath] = "";
			}
		}
		return self::$__classes_map[$componentPath];
	}
	/**
	 * Function initializes the component. Returns true on success.
	 *
	 * <p>It is absolutly necessery to call this function before any component usage.</p>
	 * @param string $componentName
	 * @param string|bool $componentTemplate
	 * @return bool
	 *
	 */
	final public function initComponent($componentName, $componentTemplate = false)
	{
		$this->__bInited = false;

		$componentName = trim($componentName);
		if ($componentName == '')
		{
			$this->__ShowError("Empty component name");
			return false;
		}

		$path2Comp = CComponentEngine::MakeComponentPath($componentName);
		if ($path2Comp == '')
		{
			$this->__ShowError(sprintf("'%s' is not a valid component name", $componentName));
			return false;
		}

		$componentPath = getLocalPath("components".$path2Comp);
		$this->classOfComponent = self::__getClassForPath($componentPath);

		if($this->classOfComponent === "")
		{
			$componentFile = $_SERVER["DOCUMENT_ROOT"].$componentPath."/component.php";
			if (!file_exists($componentFile) || !is_file($componentFile))
			{
				$this->__ShowError(sprintf("'%s' is not a component", $componentName));
				return false;
			}
		}

		if (!isset(self::$__componentCounter[$componentName]))
			self::$__componentCounter[$componentName] = 1;
		else
			self::$__componentCounter[$componentName]++;

		$this->__currentCounter = self::$__componentCounter[$componentName];

		$this->__name = $componentName;
		$this->__relativePath = $path2Comp;
		$this->__path = $componentPath;
		$this->arResult = array();
		$this->arParams = array();
		$this->__parent = null;
		$this->__arIncludeAreaIcons = array();
		$this->__cache = null;
		if ($componentTemplate !== false)
			$this->__templateName = $componentTemplate;

		$this->__bInited = true;

		return true;
	}
	/**
	 * Helper function for component parameters safe html escaping.
	 *
	 * @param array[string]mixed &$arParams
	 * @return void
	 *
	 */
	final protected function __prepareComponentParams(&$arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$p = $arParams; //this avoids endless loop
		foreach($p as $k => $v)
		{
			$arParams["~".$k] = $v;
			if (isset($v))
			{
				if (is_string($v))
				{
					if (preg_match("/[;&<>\"]/", $v))
					{
						$arParams[$k] = htmlspecialcharsEx($v);
					}
				}
				elseif (is_array($v))
				{
					//one more cycle, php 7 bug https://bugs.php.net/bug.php?id=71969
					foreach($v as $kk => $vv)
					{
						if (is_string($vv))
						{
							$arParams[$k][$kk] = htmlspecialcharsEx($vv);
						}
					}
				}
			}
		}
	}
	/**
	 * Function includes language files from within the component directory.
	 *
	 * <p>For example: $this->includeComponentLang("ajax.php") will include "lang/en/ajax.php" file. </p>
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @param string $relativePath
	 * @param string|bool $lang
	 * @return void
	 *
	 */
	final public function includeComponentLang($relativePath = "", $lang = false)
	{
		if (!$this->__bInited)
			return null;

		if ($relativePath == "")
			$relativePath = "component.php";

		$path = $_SERVER["DOCUMENT_ROOT"].$this->__path."/".$relativePath;

		if($lang === false)
		{
			\Bitrix\Main\Localization\Loc::loadMessages($path);
		}
		else
		{
			\Bitrix\Main\Localization\Loc::loadLanguageFile($path, $lang);
		}
	}
	/**
	 * Function includes component.php file thus executing the component. Returns what component.php returns.
	 *
	 * <p>Before include there is some helper variables made available for component.php scope.</p>
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return mixed
	 *
	 */
	final protected function __includeComponent()
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB;

		if (!$this->__bInited)
			return null;

		//these vars are used in the component file
		$arParams = &$this->arParams;
		$arResult = &$this->arResult;

		$componentPath = $this->__path;
		$componentName = $this->__name;
		$componentTemplate = $this->getTemplateName();

		if ($this->__parent)
		{
			$parentComponentName = $this->__parent->__name;
			$parentComponentPath = $this->__parent->__path;
			$parentComponentTemplate = $this->__parent->getTemplateName();
		}
		else
		{
			$parentComponentName = "";
			$parentComponentPath = "";
			$parentComponentTemplate = "";
		}

		return include($_SERVER["DOCUMENT_ROOT"].$this->__path."/component.php");
	}
	/**
	 * Function executes the component. Returns the result of it's execution.
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @param string $componentTemplate
	 * @param array[string]mixed $arParams
	 * @param CBitrixComponent $parentComponent
	 * @return mixed
	 *
	 */
	final public function includeComponent($componentTemplate, $arParams, $parentComponent)
	{
		if (!$this->__bInited)
			return null;

		if ($componentTemplate !== false)
			$this->setTemplateName($componentTemplate);

		if ($parentComponent instanceof cbitrixcomponent)
			$this->__parent = $parentComponent;

		if ($arParams["CACHE_TYPE"] != "Y" && $arParams["CACHE_TYPE"] != "N")
			$arParams["CACHE_TYPE"] = "A";

		if($this->classOfComponent)
		{
			/** @var CBitrixComponent $component  */
			$component = new $this->classOfComponent($this);
			$component->onIncludeComponentLang();
			$component->arParams = $component->onPrepareComponentParams($arParams);
			$component->__prepareComponentParams($component->arParams);

			$componentFrame = new Bitrix\Main\Page\FrameComponent($component);
			$componentFrame->start();

			$result = $component->executeComponent();
			$this->__arIncludeAreaIcons = $component->__arIncludeAreaIcons;
			$frameMode = $component->getFrameMode();

			$componentFrame->end();
		}
		else
		{
			$this->includeComponentLang();
			$this->__prepareComponentParams($arParams);
			$this->arParams = $arParams;

			$componentFrame = new Bitrix\Main\Page\FrameComponent($this);
			$componentFrame->start();

			$result = $this->__IncludeComponent();
			$frameMode = $this->getFrameMode();

			$componentFrame->end();
		}

		if (!$frameMode)
		{
			\Bitrix\Main\Data\StaticHtmlCache::applyComponentFrameMode($this->__name);
		}

		return $result;
	}
	/**
	 * Function executes the template.
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @param string $templatePage
	 * @param string $customTemplatePath
	 * @return void
	 *
	 */
	final public function includeComponentTemplate($templatePage = "", $customTemplatePath = "")
	{
		if (!$this->__bInited)
			return null;

		if ($this->initComponentTemplate($templatePage, $this->getSiteTemplateId(), $customTemplatePath))
		{
			$this->showComponentTemplate();
			if($this->__component_epilog)
				$this->includeComponentEpilog($this->__component_epilog);
		}
		else
		{
			$this->abortResultCache();
			$this->__showError(str_replace(
				array("#PAGE#", "#NAME#"),
				array($templatePage, $this->getTemplateName()),
				"Cannot find '#NAME#' template with page '#PAGE#'"
			));
		}
	}
	/**
	 * Function initializes the template of the component. Returns true on success.
	 *
	 * <p>Instansiates the template object and calls it's init function.</p>
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @param string $templatePage
	 * @param string|bool $siteTemplate
	 * @param string $customTemplatePath
	 * @return bool
	 *
	 */
	final public function initComponentTemplate($templatePage = "", $siteTemplate = false, $customTemplatePath = "")
	{
		if (!$this->__bInited)
			return null;

		$this->__templatePage = $templatePage;

		$this->__template = new CBitrixComponentTemplate();
		$this->__template->setLanguageId($this->getLanguageId());
		if ($this->__template->Init($this, $siteTemplate, $customTemplatePath))
			return true;
		else
			return false;
	}
	/**
	 * Function executes initialized template of the component.
	 *
	 * <p>Note: component must be inited by initComponent method.</p>
	 * @return void
	 *
	 */
	final public function showComponentTemplate()
	{
		if (!$this->__bInited)
			return null;

		if ($this->__template)
			$this->__template->includeTemplate($this->arResult);

		if(is_array($this->arResultCacheKeys))
		{
			$arNewResult = array();
			foreach($this->arResultCacheKeys as $key)
				if(array_key_exists($key, $this->arResult))
					$arNewResult[$key] = $this->arResult[$key];
			$this->arResult = $arNewResult;
		}

		if(!empty($this->__editButtons))
		{
			foreach($this->__editButtons as $button)
			{
				if($button[0] == 'AddEditAction')
					$this->addEditAction($button[1], $button[2], $button[3], $button[4]);
				else
					$this->addDeleteAction($button[1], $button[2], $button[3], $button[4]);
			}
		}

		$this->__template->endViewTarget();

		$this->endResultCache();
	}
	/**
	 * Function adds an Icon to the component area in the editing mode.
	 *
	 * @param array[string]mixed $arIcon
	 * @return void
	 *
	 */
	final public function addIncludeAreaIcon($arIcon)
	{
		if (!isset($this->__arIncludeAreaIcons) || !is_array($this->__arIncludeAreaIcons))
			$this->__arIncludeAreaIcons = array();

		$this->__arIncludeAreaIcons[] = $arIcon;
	}
	/**
	 * Function replaces Icons displayed for the component by an collection.
	 *
	 * @param array[int][string]mixed $arIcon
	 * @return void
	 *
	 */
	final public function addIncludeAreaIcons($arIcons)
	{
		if(is_array($arIcons))
			$this->__arIncludeAreaIcons = $arIcons;
	}
	/**
	 * Function returns the collection of the Icons displayed for the component.
	 *
	 * @return array[int][string]mixed
	 *
	 */
	final public function getIncludeAreaIcons()
	{
		return $this->__arIncludeAreaIcons;
	}
	/**
	 * Function returns an cache identifier based on component parameters and environment.
	 *
	 * @param mixed $additionalCacheID
	 * @return string
	 */
	public function getCacheID($additionalCacheID = false)
	{
		if(!$this->getSiteId())
			$SITE_ID = SITE_ID;
		else
			$SITE_ID = $this->getSiteId();

		if(!$this->getLanguageId())
			$LANGUAGE_ID = LANGUAGE_ID;
		else
			$LANGUAGE_ID = $this->getLanguageId();

		if(!$this->getSiteTemplateId())
			$SITE_TEMPLATE_ID = (defined("SITE_TEMPLATE_ID")? SITE_TEMPLATE_ID:"");
		else
			$SITE_TEMPLATE_ID = $this->getSiteTemplateId();

		$cacheID = $SITE_ID."|".$LANGUAGE_ID.($SITE_TEMPLATE_ID != "" ? "|".$SITE_TEMPLATE_ID:"")."|".$this->__name."|".$this->getTemplateName()."|";

		foreach($this->arParams as $k=>$v)
			if(strncmp("~", $k, 1))
				$cacheID .= ",".$k."=".serialize($v);

		if(($offset = CTimeZone::getOffset()) <> 0)
			$cacheID .= "|".$offset;

		if ($additionalCacheID !== false)
			$cacheID .= "|".serialize($additionalCacheID);

		return $cacheID;
	}
	/**
	 * Function starts the caching block of the component execution.
	 *
	 * @param int|bool $cacheTime
	 * @param mixed $additionalCacheID
	 * @param string|bool $cachePath
	 * @return string
	 *
	 */
	final public function startResultCache($cacheTime = false, $additionalCacheID = false, $cachePath = false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION, $CACHE_MANAGER;

		if (!$this->__bInited)
			return null;

		if ($this->arParams["CACHE_TYPE"] == "N" || ($this->arParams["CACHE_TYPE"] == "A" && COption::getOptionString("main", "component_cache_on", "Y") == "N"))
			return true;

		if ($cacheTime === false)
			$cacheTime = intval($this->arParams["CACHE_TIME"]);

		$this->__cacheID = $this->getCacheID($additionalCacheID);
		$this->__cachePath = $cachePath;
		if ($this->__cachePath === false)
			$this->__cachePath = $CACHE_MANAGER->getCompCachePath($this->__relativePath);

		$this->__cache = \Bitrix\Main\Data\Cache::createInstance();
		if ($this->__cache->startDataCache($cacheTime, $this->__cacheID, $this->__cachePath))
		{
			$this->__NavNum = $GLOBALS["NavNum"];
			if (defined("BX_COMP_MANAGED_CACHE") && $this->__cache->isStarted())
				$CACHE_MANAGER->startTagCache($this->__cachePath);

			return true;
		}
		else
		{
			$arCache = $this->__cache->GetVars();
			$this->arResult = $arCache["arResult"];
			if (array_key_exists("templateCachedData", $arCache))
			{
				$templateCachedData = & $arCache["templateCachedData"];

				if ($templateCachedData && is_array($templateCachedData))
				{
					if (array_key_exists("additionalCSS", $templateCachedData) && strlen($templateCachedData["additionalCSS"]) > 0)
					{
						$APPLICATION->SetAdditionalCSS($templateCachedData["additionalCSS"]);
						if($this->__parent)
							$this->__parent->addChildCSS($templateCachedData["additionalCSS"]);
					}

					if (array_key_exists("additionalJS", $templateCachedData) && strlen($templateCachedData["additionalJS"]) > 0)
					{
						$APPLICATION->AddHeadScript($templateCachedData["additionalJS"]);
						if($this->__parent)
							$this->__parent->addChildJS($templateCachedData["additionalJS"]);
					}

					if (array_key_exists("frames", $templateCachedData) && is_array($templateCachedData["frames"]))
					{
						foreach($templateCachedData["frames"] as $frameState)
						{
							\Bitrix\Main\Page\FrameStatic::applyCachedData($frameState);
						}
					}

					if (array_key_exists("frameMode", $templateCachedData))
					{
						$templateFrameMode = $templateCachedData["frameMode"];

						if ($this->getRealFrameMode() !== false)
						{
							$this->setFrameMode($templateFrameMode);
						}

						if ($this->getRealFrameMode() === false)
						{
							$context = isset($templateCachedData["frameModeCtx"])
								? "(from component cache) ".$templateCachedData["frameModeCtx"]
								: $this->__name." - a cached template set frameMode=false";

							\Bitrix\Main\Data\StaticHtmlCache::applyComponentFrameMode($context);
						}
					}

					if (isset($templateCachedData["externalCss"]))
					{
						foreach ($templateCachedData["externalCss"] as $cssPath)
						{
							$APPLICATION->SetAdditionalCSS($cssPath);
							//Check if parent component exists and plug css it to it's "collection"
							if($this->__parent)
								$this->__parent->addChildCSS($cssPath);
						}
					}

					if (isset($templateCachedData["externalJs"]))
					{
						foreach ($templateCachedData["externalJs"] as $jsPath)
						{
							$APPLICATION->AddHeadScript($jsPath);
							//Check if parent component exists and plug js it to it's "collection"
							if($this->__parent)
								$this->__parent->addChildJS($jsPath);
						}
					}
				}

				if ($templateCachedData["__editButtons"])
				{
					foreach ($templateCachedData["__editButtons"] as $button)
					{
						if ($button[0] == 'AddEditAction')
							$this->addEditAction($button[1], $button[2], $button[3], $button[4]);
						else
							$this->addDeleteAction($button[1], $button[2], $button[3], $button[4]);
					}
				}

				if ($templateCachedData["__view"])
					foreach ($templateCachedData["__view"] as $view_id => $target)
						foreach ($target as $view_content)
							$APPLICATION->addViewContent($view_id, $view_content[0], $view_content[1]);

				if (array_key_exists("__NavNum", $templateCachedData))
					$GLOBALS["NavNum"]+= $templateCachedData["__NavNum"];

				if (array_key_exists("__children_css", $templateCachedData))
				{
					foreach ($templateCachedData["__children_css"] as $css_url)
						$APPLICATION->setAdditionalCSS($css_url);
				}

				if (array_key_exists("__children_js", $templateCachedData))
				{
					foreach ($templateCachedData["__children_js"] as $js_url)
						$APPLICATION->addHeadScript($js_url);
				}

				if (array_key_exists("__children_epilogs", $templateCachedData))
				{
					foreach ($templateCachedData["__children_epilogs"] as $component_epilog)
						$this->includeComponentEpilog($component_epilog);
				}

				if (array_key_exists("component_epilog", $templateCachedData))
				{
					$this->includeComponentEpilog($templateCachedData["component_epilog"]);
				}
			}
			return false;
		}
	}
	/**
	 * Function ends the caching block of the component execution.
	 *
	 * <p>Note: automaticly called by includeComponentTemplate.</p>
	 * @return void
	 *
	 */
	final public function endResultCache()
	{
		global $NavNum, $CACHE_MANAGER;

		if (!$this->__bInited)
			return null;

		if (!$this->__cache)
		{
			if ($this->__parent)
			{
				foreach($this->__children_css as $cssPath)
					$this->__parent->addChildCSS($cssPath);
				foreach($this->__children_js as $jsPath)
					$this->__parent->addChildJS($jsPath);
				foreach($this->__children_epilogs as $epilogFile)
					$this->__parent->addChildEpilog($epilogFile);
			}
			return null;
		}

		$arCache = array(
			"arResult" => $this->arResult,
		);
		if ($this->__template)
		{
			$arCache["templateCachedData"] = & $this->__template->getCachedData();
			if ($this->__component_epilog)
				$arCache["templateCachedData"]["component_epilog"] = $this->__component_epilog;
		}
		else
		{
			$arCache["templateCachedData"] = array();
		}

		if (($this->__NavNum !== false) && ($this->__NavNum !== $NavNum))
		{
			$arCache["templateCachedData"]["__NavNum"] = $NavNum - $this->__NavNum;
		}

		if (!empty($this->__children_css))
		{
			$arCache["templateCachedData"]["__children_css"] = $this->__children_css;
			if ($this->__parent)
			{
				foreach($this->__children_css as $cssPath)
					$this->__parent->addChildCSS($cssPath);
			}
		}

		if (!empty($this->__children_js))
		{
			$arCache["templateCachedData"]["__children_js"] = $this->__children_js;
			if ($this->__parent)
			{
				foreach($this->__children_js as $jsPath)
					$this->__parent->addChildJS($jsPath);
			}
		}

		if (!empty($this->__children_epilogs))
		{
			$arCache["templateCachedData"]["__children_epilogs"] = $this->__children_epilogs;
			if ($this->__parent)
			{
				foreach($this->__children_epilogs as $epilogFile)
					$this->__parent->addChildEpilog($epilogFile);
			}
		}

		if (!empty($this->__view))
			$arCache["templateCachedData"]["__view"] = $this->__view;

		if (!empty($this->__editButtons))
			$arCache["templateCachedData"]["__editButtons"] = $this->__editButtons;

		$cacheWasStarted = $this->__cache->isStarted();
		$this->__cache->endDataCache($arCache);

		if (defined("BX_COMP_MANAGED_CACHE") && $cacheWasStarted)
			$CACHE_MANAGER->endTagCache();

		$this->__cache = null;
	}
	/**
	 * Function aborts the cache after it's start.
	 *
	 * <p>Note: must be called if component returns before endResultCache or includeComponentTemplate called.</p>
	 * @return void
	 *
	 */
	final public function abortResultCache()
	{
		global $CACHE_MANAGER;

		if (!$this->__bInited)
			return null;

		if (!$this->__cache)
			return null;

		$cacheWasStarted = $this->__cache->isStarted();
		$this->__cache->abortDataCache();

		if(defined("BX_COMP_MANAGED_CACHE") && $cacheWasStarted)
			$CACHE_MANAGER->abortTagCache();

		$this->__cache = null;
	}
	/**
	 * Function deletes the cache created before.
	 *
	 * <p>Note: parameters must exactly match to startResultCache call.</p>
	 * @param mixed $additionalCacheID
	 * @param string|bool $cachePath
	 * @return void
	 *
	 */
	final public function clearResultCache($additionalCacheID = false, $cachePath = false)
	{
		global $CACHE_MANAGER;

		if (!$this->__bInited)
			return null;

		$this->__cacheID = $this->getCacheID($additionalCacheID);

		$this->__cachePath = $cachePath;
		if ($this->__cachePath === false)
			$this->__cachePath = $CACHE_MANAGER->getCompCachePath($this->__relativePath);

		$cache = new CPHPCache();
		$cache->clean($this->__cacheID, $this->__cachePath);
	}
	/**
	 * Function clears entire component cache.
	 *
	 * <p>Note: parameters must exactly match to startResultCache call.</p>
	 * @param string $componentName
	 * @param string $siteId
	 * @return void
	 *
	 */
	final public static function clearComponentCache($componentName, $siteId = "")
	{
		/** @global CCacheManager $CACHE_MANAGER */
		global $CACHE_MANAGER;

		$componentRelativePath = CComponentEngine::MakeComponentPath($componentName);
		if ($componentRelativePath != "")
		{
			$obCache = new CPHPCache;
			$obCache->CleanDir($componentRelativePath, "cache");
			BXClearCache(true, $componentRelativePath);

			if ($siteId == "")
			{
				$rsSite = \Bitrix\Main\SiteTable::getList(array('order' => array('SORT' => 'ASC')));
				while ($site = $rsSite->fetch())
				{
					$componentCachePath = "/".$site["LID"].$componentRelativePath;
					$obCache = new CPHPCache;
					$obCache->CleanDir($componentCachePath, "cache");
					BXClearCache(true, $componentCachePath);
				}
			}
			else
			{
				$componentCachePath = "/".$siteId.$componentRelativePath;
				$obCache = new CPHPCache;
				$obCache->CleanDir($componentCachePath, "cache");
				BXClearCache(true, $componentCachePath);
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->ClearByTag($componentName);
		}
	}
	/**
	 * Function returns component cache path.
	 *
	 * @return string
	 *
	 */
	final public function getCachePath()
	{
		return $this->__cachePath;
	}
	/**
	 * Function marks the arResult keys to be saved to cache. Just like __sleep magic method do.
	 *
	 * <p>Note: it's call adds key, not replacing.</p>
	 * @param array[int]string $arResultCacheKeys
	 * @return void
	 *
	 */
	
	/**
	* <p><code>$arResultCacheKeys</code> - это список ключей массива <b>$arResult</b>, которые должны кэшироваться при использовании встроенного кэширования компонентов. Нестатический метод.</p>   <a name="example"></a>
	*
	*
	* @param  $arResultCacheKeys  
	*
	* @return mixed 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* $this-&gt;SetResultCacheKeys(array(
	*    "IBLOCK_ID",
	*    "ID",
	*    "IBLOCK_SECTION_ID",
	*    "NAME",
	*    "PROPERTIES",
	*    "SECTION",
	* ));
	* $this-&gt;IncludeComponentTemplate();
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cbitrixcomponent/setresultcachekeys.php
	* @author Bitrix
	*/
	final public function setResultCacheKeys($arResultCacheKeys)
	{
		if ($this->arResultCacheKeys === false)
			$this->arResultCacheKeys = $arResultCacheKeys;
		else
			$this->arResultCacheKeys = array_merge($this->arResultCacheKeys, $arResultCacheKeys);
	}
	/**
	 * Function returns component area id for editing mode.
	 *
	 * @param string $entryId
	 * @return string
	 *
	 */
	final public function getEditAreaId($entryId)
	{
		return 'bx_'.abs(crc32($this->GetName().'_'.$this->__currentCounter)).'_'.$entryId;
	}
	/**
	 * Function adds an edit action to some area inside the component.
	 *
	 * @param string $entryId
	 * @param string $editLink
	 * @param string|bool $editTitle
	 * @param array[string]mixed $arParams
	 * @return void
	 *
	 */
	final public function addEditAction($entryId, $editLink, $editTitle = false, $arParams = array())
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!$entryId || !$editLink)
			return;

		if (!$editTitle)
		{
			IncludeModuleLangFile(__FILE__);
			$editTitle = GetMessage('EDIT_ACTION_TITLE_DEFAULT');
		}

		if (!is_array($arParams))
			$arParams = array();

		if (!$arParams['WINDOW'])
			$arParams['WINDOW'] = array(
				"width" => 780,
				"height" => 500,
			);

		if (!$arParams['ICON'] && !$arParams['SRC'] && !$arParams['IMAGE'])
			$arParams['ICON'] = 'bx-context-toolbar-edit-icon';

		$arBtn = array(
			'URL' => 'javascript:'.$APPLICATION->getPopupLink(array(
				'URL' => $editLink,
				"PARAMS" => $arParams['WINDOW'],
			)),
			'TITLE' => $editTitle,
		);

		if ($arParams['ICON'])
			$arBtn['ICON'] = $arParams['ICON'];
		elseif ($arParams['SRC'] || $arParams['IMAGE'])
			$arBtn['SRC'] = $arParams['IMAGE'] ? $arParams['IMAGE'] : $arParams['SRC'];

		$APPLICATION->setEditArea($this->getEditAreaId($entryId), array(
			$arBtn,
		));
	}
	/**
	 * Function adds an delete action to some area inside the component.
	 *
	 * <ul>
	 * <li>$arParams['CONFIRM'] = false - disable confirm;
	 * <li>$arParams['CONFIRM'] = 'Text' - confirm with custom text;
	 * <li>no $arParams['CONFIRM'] at all - confirm with default text
	 * </ul>
	 * @param string $entryId
	 * @param string $deleteLink
	 * @param string|bool $deleteTitle
	 * @param array[string]mixed $arParams
	 * @return void
	 *
	 */
	final public function addDeleteAction($entryId, $deleteLink, $deleteTitle = false, $arParams = array())
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!$entryId || !$deleteLink)
			return;

		includeModuleLangFile(__FILE__);
		if (!$deleteTitle)
		{
			$deleteTitle = GetMessage('DELETE_ACTION_TITLE_DEFAULT');
		}

		if (!is_array($arParams))
			$arParams = array();

		if (!$arParams['ICON'] && !$arParams['SRC'] && !$arParams['IMAGE'])
			$arParams['ICON'] = 'bx-context-toolbar-delete-icon';

		if (substr($deleteLink, 0, 11) != 'javascript:')
		{
			if (false === strpos($deleteLink, 'return_url='))
				$deleteLink.= '&return_url='.urlencode($APPLICATION->getCurPageParam());

			$deleteLink.= '&'.bitrix_sessid_get();
			if ($arParams['CONFIRM'] !== false)
			{
				$confirmText = $arParams['CONFIRM'] ? $arParams['CONFIRM'] : GetMessage('DELETE_ACTION_CONFIRM');
				$deleteLink = 'javascript:if(confirm(\''.CUtil::JSEscape($confirmText).'\')) jsUtils.Redirect([], \''.CUtil::JSEscape($deleteLink).'\');';
			}
		}

		$arBtn = array(
			'URL' => $deleteLink,
			'TITLE' => $deleteTitle,
		);

		if ($arParams['ICON'])
			$arBtn['ICON'] = $arParams['ICON'];
		elseif ($arParams['SRC'] || $arParams['IMAGE'])
			$arBtn['SRC'] = $arParams['IMAGE'] ? $arParams['IMAGE'] : $arParams['SRC'];

		$APPLICATION->setEditArea($this->getEditAreaId($entryId), array(
			$arBtn,
		));
	}
	/**
	 * Function saves component epilog environment
	 *
	 * @param array[string]mixed $arEpilogInfo
	 * @return void
	 *
	 */
	final public function setTemplateEpilog($arEpilogInfo)
	{
		$this->__component_epilog = $arEpilogInfo;
		//Check if parent component exists and plug epilog it to it's "collection"
		if ($this->__parent)
			$this->__parent->addChildEpilog($this->__component_epilog);
	}
	/**
	 * Function restores component epilog environment and executes it.
	 *
	 * @param array[string]mixed $arEpilogInfo
	 * @return void
	 *
	 */
	final public function includeComponentEpilog($arEpilogInfo)
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER, $DB;

		// available variables in the epilog file:
		// $templateName, $templateFile, $templateFolder, $templateData
		/** @var $epilogFile */
		extract($arEpilogInfo);
		if ($epilogFile <> '' && file_exists($_SERVER["DOCUMENT_ROOT"].$epilogFile))
		{
			//these vars can be used in the epilog file
			$arParams = $this->arParams;
			$arResult = $this->arResult;
			$componentPath = $this->GetPath();
			$component = $this;
			include($_SERVER["DOCUMENT_ROOT"].$epilogFile);
		}
	}
	/**
	 * Function shows an internal error message.
	 *
	 * @param string $errorMessage
	 * @param string $errorCode
	 * @return void
	 *
	 */
	static public function __showError($errorMessage, $errorCode = "")
	{
		if ($errorMessage <> '')
			echo "<font color=\"#FF0000\">".$errorMessage.($errorCode <> '' ? " [".$errorCode."]" : "")."</font>";
	}
	/**
	 * Function registers children css file for cache.
	 *
	 * @param string $cssPath
	 * @return void
	 *
	 */
	final public function addChildCSS($cssPath)
	{
		$this->__children_css[] = $cssPath;
	}
	/**
	 * Function registers children js file for cache.
	 *
	 * @param string $jsPath
	 * @return void
	 *
	 */
	final public function addChildJS($jsPath)
	{
		$this->__children_js[] = $jsPath;
	}
	/**
	 * Function registers children epilog file for cache.
	 *
	 * @param string $epilogFile
	 * @return void
	 *
	 */
	final public function addChildEpilog($epilogFile)
	{
		$this->__children_epilogs[] = $epilogFile;
	}
	/**
	 * Function adds a button to be displayed.
	 *
	 * @param array[int]string $arButton
	 * @return void
	 *
	 */
	final public function addEditButton($arButton)
	{
		$this->__editButtons[] = $arButton;
	}
	/**
	 * Function registers new view target for the cache.
	 *
	 * @param string $target
	 * @param string $content
	 * @param int $pos
	 * @return void
	 *
	 */
	final public function addViewTarget($target, $content, $pos)
	{
		if(!isset($this->__view[$target]))
			$this->__view[$target] = array();

		$this->__view[$target][] = array($content, $pos);
	}

	/**
	 * Function returns next pseudo random value.
	 *
	 * @param int $length
	 * @return string
	 *
	 * @see \Bitrix\Main\Type\RandomSequence::randString
	 */
	public function randString($length = 6)
	{
		if (!$this->randomSequence)
		{
			$seed = $this->__name."|".self::$__componentCounter[$this->__name];
			$this->randomSequence = new \Bitrix\Main\Type\RandomSequence($seed);
		}
		return $this->randomSequence->randString($length);
	}

	/**
	 * Marks a component as capable of composite mode.
	 * You should use is to mark a whole component as
	 * composite incompatible.
	 *
	 * @param bool $mode
	 * @return void
	 *
	 */
	public function setFrameMode($mode)
	{
		if (in_array($mode, array(true, false, null), true))
		{
			$this->frameMode = $mode;
		}
	}

	public function getFrameMode()
	{
		if ($this->frameMode !== null)
		{
			return $this->frameMode;
		}

		return true;
	}

	public function getRealFrameMode()
	{
		return $this->frameMode;
	}

	public function getDefaultFrameMode()
	{
		$frameMode = null;

		$compositeOptions = CHTMLPagesCache::getOptions();
		$componentParams = $this->arParams;
		
		if (
			isset($componentParams["COMPOSITE_FRAME_MODE"]) &&
			in_array($componentParams["COMPOSITE_FRAME_MODE"], array("Y", "N"))
		)
		{
			$frameMode = $componentParams["COMPOSITE_FRAME_MODE"] === "Y";
		}
		else if (isset($compositeOptions["FRAME_MODE"]))
		{
			$frameMode = $compositeOptions["FRAME_MODE"] === "Y";
		}
		
		return $frameMode;
	}
}
