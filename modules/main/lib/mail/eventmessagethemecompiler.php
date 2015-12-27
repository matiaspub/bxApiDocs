<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main\Mail;

use Bitrix\Main\Mail\Internal as MailInternal;
use Bitrix\Main\Config as Config;
use Bitrix\Main\IO as IO;
use Bitrix\Main\ObjectNotFoundException as ObjectNotFoundException;

class EventMessageThemeCompiler
{
	/**
	 * @var EventMessageThemeCompiler
	 */
	protected static $instance = null;

	protected $siteTemplateId;
	protected $siteId;
	protected $languageId;

	protected $themeProlog;
	protected $themeEpilog;
	protected $themeStylesString = '';
	protected $resultString = '';
	protected $body;
	protected $contentTypeHtml = false;

	protected $arStyle = array();
	protected $replaceCallback = array();
	protected $currentResourceOrder = 100;

	public function __construct($siteTemplateId = null, $body, $isHtml = true)
	{
		$this->contentTypeHtml = $isHtml;
		$this->siteTemplateId = $siteTemplateId;
		$this->setTheme($siteTemplateId);
		$this->setBody($body);
	}

	/**
	 * @return EventMessageThemeCompiler
	 */
	public static function createInstance($siteTemplateId = null, $body, $isHtml = true)
	{
		static::$instance = new static($siteTemplateId, $body, $isHtml);

		return static::$instance;
	}

	/**
	 * Returns current instance of the EventMessageThemeCompiler.
	 *
	 * @return EventMessageThemeCompiler
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance))
			throw new ObjectNotFoundException('createInstance() should be called before getInstance()');

		return static::$instance;
	}

	public static function unsetInstance()
	{
		if (isset(static::$instance))
			static::$instance = null;
	}

	/**
	 * @param mixed $siteTemplateId
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
	 * @param mixed $languageId
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
	 * @param mixed $siteId
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

	public function getResult()
	{
		return $this->resultString;
	}

	public function setParams(array $arParams)
	{
		$this->params = $arParams;
	}
	/**
	 * @param mixed $themeProlog
	 */
	public function setThemeProlog($themeProlog)
	{
		$this->themeProlog = $themeProlog;
	}

	/**
	 * @return mixed
	 */
	public function getThemeProlog()
	{
		return $this->themeProlog;
	}

	/**
	 * @param mixed $themeEpilog
	 */
	public function setThemeEpilog($themeEpilog)
	{
		$this->themeEpilog = $themeEpilog;
	}

	/**
	 * @return mixed
	 */
	public function getThemeEpilog()
	{
		return $this->themeEpilog;
	}


	public function setStyle($path, $sort = false)
	{
		$sort = ($sort === false ? $this->currentResourceOrder : $sort);
		$this->arStyle[$path] = $sort;
	}

	public function setStyleArray(array $arPaths, $sort = false)
	{
		foreach($arPaths as $path)
			$this->setStyle($path, $sort);
	}

	public function getStyles()
	{
		return $this->arStyle;
	}

	public function getStylesString()
	{
		$returnStylesString = $this->themeStylesString;
		$arStyle = $this->arStyle;
		asort($arStyle);
		foreach($arStyle as $path=>$sort)
		{
			$pathFull = \Bitrix\Main\Application::getDocumentRoot().$path;
			if(IO\File::isFileExists($pathFull))
			{
				$content = "/* $path */ \r\n" . IO\File::getFileContents($pathFull);
				$returnStylesString .= $content . "\r\n";
			}
		}

		if(strlen($returnStylesString)>0)
		{
			$returnStylesString = '<style type="text/css">'."\r\n".$returnStylesString."\r\n".'</style>';
		}

		return $returnStylesString;
	}

	public function showStyles()
	{
		if($this->contentTypeHtml)
		{
			$identificator = '%BITRIX_MAIL_EVENT_TEMPLATE_THEME_CALLBACK_STYLE%';
			$this->addReplaceCallback($identificator, array($this, 'getStylesString'));
		}
		else
		{
			$identificator = '';
		}

		return $identificator;
	}

	protected function setTheme($site_template_id)
	{
		if(strlen($site_template_id)>0)
		{
			$result = \CSiteTemplate::GetByID($site_template_id);
			if($templateFields = $result->Fetch())
			{
				$template_path_header = \Bitrix\Main\Application::getDocumentRoot().$templateFields['PATH'].'/header.php';
				$template_path_footer = \Bitrix\Main\Application::getDocumentRoot().$templateFields['PATH'].'/footer.php';
				if($templateFields['PATH']!='' && IO\File::isFileExists($template_path_footer)  && IO\File::isFileExists($template_path_header))
				{
					$this->themeStylesString .= $templateFields['TEMPLATE_STYLES']."\r\n";
					$this->themeStylesString .= $templateFields['STYLES']."\r\n";

					$this->setThemeProlog(IO\File::getFileContents($template_path_header));
					$this->setThemeEpilog(IO\File::getFileContents($template_path_footer));
				}
			}
		}
	}

	protected function setBody($body)
	{
		$this->body = $body;
	}


	/**
	 * @param
	 */
	public function execute()
	{
		$resultThemeProlog = '';
		$resultThemeEpilog = '';

		if(!$this->themeProlog && $this->contentTypeHtml)
			$this->body = '<?=$this->showStyles()?>' . $this->body;

		$resultBody = $this->executePhp($this->body, 100);
		if($this->themeProlog)
			$resultThemeProlog = $this->executePhp($this->themeProlog, 50);
		if($this->themeEpilog)
			$resultThemeEpilog = $this->executePhp($this->themeEpilog, 150);

		$this->resultString = $resultThemeProlog . $resultBody . $resultThemeEpilog;
		$this->executeReplaceCallback();
	}


	protected function executePhp($template, $resourceOrder = 100)
	{
		$this->currentResourceOrder = $resourceOrder;

		$arParams = $this->params;
		$result = eval('use \Bitrix\Main\Mail\EventMessageThemeCompiler; ob_start();?>' . $template . '<? return ob_get_clean();');

		return $result;
	}

	protected function addReplaceCallback($identificator, $callback)
	{
		$this->replaceCallback[$identificator] = $callback;
	}

	protected function executeReplaceCallback()
	{
		$arReplaceIdentificators = array();
		$arReplaceStrings = array();
		foreach($this->replaceCallback as $identificator => $callback)
		{
			$result = call_user_func_array($callback, array());
			if($result === false)
				$result = '';

			$arReplaceIdentificators[] = $identificator;
			$arReplaceStrings[] = $result;
		}

		$this->resultString = str_replace($arReplaceIdentificators, $arReplaceStrings, $this->resultString);
	}

	public static function includeComponent($componentName, $componentTemplate, $arParams = array(), $parentComponent = null, $arFunctionParams = array())
	{
		$componentRelativePath = \CComponentEngine::MakeComponentPath($componentName);
		if (StrLen($componentRelativePath) <= 0)
			return False;

		if (is_object($parentComponent))
		{
			if (!($parentComponent instanceof \cbitrixcomponent))
				$parentComponent = null;
		}

		$result = null;
		$bComponentEnabled = (!isset($arFunctionParams["ACTIVE_COMPONENT"]) || $arFunctionParams["ACTIVE_COMPONENT"] <> "N");

		$component = new \CBitrixComponent();
		if($component->InitComponent($componentName))
		{
			$obAjax = null;
			if($bComponentEnabled)
			{
				$component->setSiteId(static::getInstance()->getSiteId());
				$component->setLanguageId(static::getInstance()->getLanguageId());
				$component->setSiteTemplateId(static::getInstance()->getSiteTemplateId());

				$result = $component->IncludeComponent($componentTemplate, $arParams, $parentComponent);

				$arThemeCss = array(); // TODO: use styles array from $component
				foreach($arThemeCss as $cssPath)
					static::getInstance()->setStyle($cssPath);
			}
		}

		return $result;
	}

}
