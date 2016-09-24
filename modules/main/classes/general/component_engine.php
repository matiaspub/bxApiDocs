<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */


/**
 * Класс <b> CComponentEngine </b>инкапсулирует вспомогательные методы, полезные при разработке компонентов.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/ccomponentengine/index.php
 * @author Bitrix
 */
class CComponentEngine
{
	private $component = null;
	private $greedyParts = array();
	private $resolveCallback = false;
	/**
	 * Constructor.
	 *
	 * <p>Takes component as parameter and initializing the object.</p>
	 * @param CBitrixComponent $component
	 */
	public function __construct($component = null)
	{
		if ($component instanceof CBitrixComponent)
			$this->component = $component;
	}
	/**
	 * Returns associated component.
	 *
	 * @return CBitrixComponent
	 *
	 */
	public function getComponent()
	{
		return $this->component;
	}
	/**
	 * Marks one of the page templates parts as greedy (may contain "/" separator).
	 *
	 * @param string $part
	 * @return void
	 *
	 */
	public function addGreedyPart($part)
	{
		$part = trim($part, " \t\n\r#");
		if ($part != "")
			$this->greedyParts[$part] = preg_quote($part, "'");
	}
	/**
	 * Registers callback with will be called on page indetermination.
	 *
	 * @param callback $resolveCallback
	 * @return void
	 *
	 */
	public function setResolveCallback($resolveCallback)
	{
		if (is_callable($resolveCallback))
			$this->resolveCallback = $resolveCallback;
	}
	/**
	 * Checks if component name is valid.
	 *
	 * <p>Component name consists of namespace part, separator and name. Example: bitrix:news.list</p>
	 * @param string $componentName
	 * @return bool
	 *
	 */
	public static function checkComponentName($componentName)
	{
		return ($componentName <> '' && preg_match("#^([A-Za-z0-9_.-]+:)?([A-Za-z0-9_-]+\\.)*([A-Za-z0-9_-]+)$#i", $componentName));
	}
	/**
	 * Makes filesystem relative path out of com name.
	 *
	 * @param string $componentName
	 * @return string
	 *
	 */
	public static function makeComponentPath($componentName)
	{
		if(!CComponentEngine::checkComponentName($componentName))
			return "";

		return "/".str_replace(":", "/", $componentName);
	}
	/**
	 * Checks if page template has templates in it.
	 *
	 * @param string $pageTemplate
	 * @return bool
	 *
	 */
	static public function hasNoVariables($pageTemplate)
	{
		return strpos($pageTemplate, "#") === false;
	}
	/**
	 * Checks if page template.has greedy templates it it.
	 *
	 * @param string $pageTemplate
	 * @return bool
	 *
	 */
	public function hasGreedyParts($pageTemplate)
	{
		if (
			!empty($this->greedyParts)
			&& preg_match("'#(?:".implode("|", $this->greedyParts).")#'", $pageTemplate)
		)
			return true;
		else
			return false;
	}
	/**
	 * Sorts templates for inspection.
	 *
	 * <p>First will be templates without any variables. Then templates without greedy parts. Then greedy ones.</p>
	 * @param array[string]string $arUrlTemplates
	 * @param bool &$bHasGreedyPartsInTemplates
	 * @return array[string]string
	 *
	 */
	protected function sortUrlTemplates($arUrlTemplates, &$bHasGreedyPartsInTemplates)
	{
		$resultNoHash = array();
		$resultWithHash = array();
		$resultWithGreedy = array();

		foreach ($arUrlTemplates as $pageID => $pageTemplate)
		{
			$pos = strpos($pageTemplate, "?");
			if ($pos !== false)
				$pageTemplate = substr($pageTemplate, 0, $pos);

			if ($this->hasNoVariables($pageTemplate))
				$resultNoHash[$pageID] = $pageTemplate;
			elseif ($this->hasGreedyParts($pageTemplate))
				$resultWithGreedy[$pageID] = $pageTemplate;
			else
				$resultWithHash[$pageID] = $pageTemplate;
		}
		$bHasGreedyPartsInTemplates = !empty($resultWithGreedy);
		return array_merge($resultNoHash, $resultWithHash, $resultWithGreedy);
	}
	/**
	 * Checks if page template matches current URL.
	 *
	 * <p>In case of succsessful match fills in parsed variables.</p>
	 * @param string $pageTemplate
	 * @param string $currentPageUrl
	 * @param array[string]string &$arVariables
	 * @return bool
	 *
	 */
	protected function __checkPath4Template($pageTemplate, $currentPageUrl, &$arVariables)
	{
		if (!empty($this->greedyParts))
		{
			$pageTemplateReg = preg_replace("'#(?:".implode("|", $this->greedyParts).")#'", "(.+?)", $pageTemplate);
			$pageTemplateReg = preg_replace("'#[^#]+?#'", "([^/]+?)", $pageTemplateReg);
		}
		else
		{
			$pageTemplateReg = preg_replace("'#[^#]+?#'", "([^/]+?)", $pageTemplate);
		}

		if (substr($pageTemplateReg, -1, 1) == "/")
			$pageTemplateReg .= "index\\.php";

		$arValues = array();
		if (preg_match("'^".$pageTemplateReg."$'", $currentPageUrl, $arValues))
		{
			$arMatches = array();
			if (preg_match_all("'#([^#]+?)#'", $pageTemplate, $arMatches))
			{
				for ($i = 0, $cnt = count($arMatches[1]); $i < $cnt; $i++)
					$arVariables[$arMatches[1][$i]] = $arValues[$i + 1];
			}
			return true;
		}

		return false;
	}
	/**
	 * Finds match between requestURL and on of the url templates.
	 *
	 * @param string $folder404
	 * @param array[string]string $arUrlTemplates
	 * @param array[string]string &$arVariables
	 * @param string|bool $requestURL
	 * @return string
	 *
	 */
	public static function parseComponentPath($folder404, $arUrlTemplates, &$arVariables, $requestURL = false)
	{
		$engine = new CComponentEngine();
		return $engine->guessComponentPath($folder404, $arUrlTemplates, $arVariables, $requestURL);
	}
	/**
	 * Finds match between requestURL and on of the url templates.
	 *
	 * <p>Lets using the engine object and greedy templates.</p>
	 * @param string $folder404
	 * @param array[string]string $arUrlTemplates
	 * @param array[string]string &$arVariables
	 * @param string|bool $requestURL
	 * @return string
	 *
	 */
	public function guessComponentPath($folder404, $arUrlTemplates, &$arVariables, $requestURL = false)
	{
		if (!isset($arVariables) || !is_array($arVariables))
			$arVariables = array();

		if ($requestURL === false)
			$requestURL = Bitrix\Main\Context::getCurrent()->getRequest()->getRequestedPage();

		$folder404 = str_replace("\\", "/", $folder404);
		if ($folder404 != "/")
			$folder404 = "/".trim($folder404, "/ \t\n\r\0\x0B")."/";

		//SEF base URL must match curent URL (several components on the same page)
		if(strpos($requestURL, $folder404) !== 0)
			return false;

		$currentPageUrl = substr($requestURL, strlen($folder404));

		$pageCandidates = array();
		$arUrlTemplates = $this->sortUrlTemplates($arUrlTemplates, $bHasGreedyPartsInTemplates);
		if (
			$bHasGreedyPartsInTemplates
			&& is_callable($this->resolveCallback)
		)
		{
			foreach ($arUrlTemplates as $pageID => $pageTemplate)
			{
				$arVariablesTmp = $arVariables;
				if ($this->__CheckPath4Template($pageTemplate, $currentPageUrl, $arVariablesTmp))
				{
					if ($this->hasNoVariables($pageTemplate))
					{
						$arVariables = $arVariablesTmp;
						return $pageID;
					}
					else
					{
						$pageCandidates[$pageID] = $arVariablesTmp;
					}
				}
			}
		}
		else
		{
			foreach ($arUrlTemplates as $pageID => $pageTemplate)
			{
				if ($this->__CheckPath4Template($pageTemplate, $currentPageUrl, $arVariables))
				{
					return $pageID;
				}
			}
		}

		if (!empty($pageCandidates) && is_callable($this->resolveCallback))
		{
			return call_user_func_array($this->resolveCallback, array($this, $pageCandidates, &$arVariables));
		}

		return false;
	}
	/**
	 * Initializes component variables from $_REQUEST based on component page selected.
	 *
	 * @param string $componentPage
	 * @param array[string]string $arComponentVariables
	 * @param array[string]string $arVariableAliases
	 * @param array[string]string &$arVariables
	 * @return void
	 *
	 */
	public static function initComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, &$arVariables)
	{
		if (!isset($arVariables) || !is_array($arVariables))
			$arVariables = array();

		if ($componentPage)
		{
			if (array_key_exists($componentPage, $arVariableAliases) && is_array($arVariableAliases[$componentPage]))
			{
				foreach ($arVariableAliases[$componentPage] as $variableName => $aliasName)
					if (!array_key_exists($variableName, $arVariables))
						$arVariables[$variableName] = $_REQUEST[$aliasName];
			}
		}
		else
		{
			foreach ($arVariableAliases as $variableName => $aliasName)
				if (!array_key_exists($variableName, $arVariables))
					if (is_string($aliasName) && array_key_exists($aliasName, $_REQUEST))
						$arVariables[$variableName] = $_REQUEST[$aliasName];
		}

		for ($i = 0, $cnt = count($arComponentVariables); $i < $cnt; $i++)
			if (!array_key_exists($arComponentVariables[$i], $arVariables)
				&& array_key_exists($arComponentVariables[$i], $_REQUEST))
			{
				$arVariables[$arComponentVariables[$i]] = $_REQUEST[$arComponentVariables[$i]];
			}
	}
	/**
	 * Prepares templates based on default and provided.
	 *
	 * @param array[string]string $arDefaultUrlTemplates
	 * @param array[string]string $arCustomUrlTemplates
	 * @return array[string]string
	 *
	 */
	public static function makeComponentUrlTemplates($arDefaultUrlTemplates, $arCustomUrlTemplates)
	{
		if (!is_array($arCustomUrlTemplates))
			$arCustomUrlTemplates = array();

		return array_merge($arDefaultUrlTemplates, $arCustomUrlTemplates);
	}
	/**
	 * Prepares variables based on default and provided.
	 *
	 * @param array[string]string $arDefaultVariableAliases
	 * @param array[string]string $arCustomVariableAliases
	 * @return array[string]string
	 *
	 */
	public static function makeComponentVariableAliases($arDefaultVariableAliases, $arCustomVariableAliases)
	{
		if (!is_array($arCustomVariableAliases))
			$arCustomVariableAliases = array();

		return array_merge($arDefaultVariableAliases, $arCustomVariableAliases);
	}
	/**
	 * Replaces templates in provided string based on parameters
	 *
	 * @param string $template
	 * @param array[string]string $arParams
	 * @return string
	 *
	 */
	public static function makePathFromTemplate($template, $arParams = array())
	{
		$arPatterns = array("#SITE_DIR#", "#SITE#", "#SERVER_NAME#");
		$arReplace = array(SITE_DIR, SITE_ID, SITE_SERVER_NAME);
		foreach ($arParams as $key => $value)
		{
			$arPatterns[] = "#".$key."#";
			$arReplace[] = $value;
		}
		return str_replace($arPatterns, $arReplace, $template);
	}
}
