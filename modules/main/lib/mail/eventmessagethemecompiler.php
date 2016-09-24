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

	protected $themePath = '';
	protected $themeProlog;
	protected $themeEpilog;
	protected $themeStylesString = '';
	protected $resultString = '';
	protected $body;
	protected $contentTypeHtml = false;

	protected $params = array();
	protected $arStyle = array();
	protected $replaceCallback = array();
	protected $currentResourceOrder = 100;

	/**
	 * Constructor.
	 *
	 * @param string|null $siteTemplateId
	 * @param string $body
	 * @param bool $isHtml
	 * @return EventMessageThemeCompiler
	 */
	
	/**
	* <p>Нестатический метод - конструктор.</p>
	*
	*
	* @param mixed $string  
	*
	* @param null $siteTemplateId = null 
	*
	* @param string $body  
	*
	* @param boolean $isHtml = true 
	*
	* @return \Bitrix\Main\Mail\EventMessageThemeCompiler 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/__construct.php
	* @author Bitrix
	*/
	public function __construct($siteTemplateId = null, $body, $isHtml = true)
	{
		$this->contentTypeHtml = $isHtml;
		$this->siteTemplateId = $siteTemplateId;
		$this->setTheme($siteTemplateId);
		$this->setBody($body);
	}

	/**
	 * Create instance.
	 *
	 * @param string|null $siteTemplateId
	 * @param string $body
	 * @param bool $isHtml
	 * @return EventMessageThemeCompiler
	 */
	
	/**
	* <p>Статический метод создаёт экземпляр класса.</p>
	*
	*
	* @param mixed $string  Идентификатор шаблона сайта
	*
	* @param null $siteTemplateId = null Тело
	*
	* @param string $body  В виде HTML. По умолчанию true.
	*
	* @param boolean $isHtml = true 
	*
	* @return \Bitrix\Main\Mail\EventMessageThemeCompiler 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/createinstance.php
	* @author Bitrix
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
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	
	/**
	* <p>Статический метод возвращает текущий экземпляр класса.</p> <p>Без параметров</p>
	*
	*
	* @return \Bitrix\Main\Mail\EventMessageThemeCompiler 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getinstance.php
	* @author Bitrix
	*/
	public static function getInstance()
	{
		if (!isset(static::$instance))
			throw new ObjectNotFoundException('createInstance() should be called before getInstance()');

		return static::$instance;
	}

	/**
	 * Unset current instance of the EventMessageThemeCompiler.
	 *
	 * @return void
	 */
	
	/**
	* <p>Статический метод сбрасывает текущий экземпляр класса.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/unsetinstance.php
	* @author Bitrix
	*/
	public static function unsetInstance()
	{
		if (isset(static::$instance))
			static::$instance = null;
	}

	/**
	 * Set site template id.
	 *
	 * @param mixed $siteTemplateId
	 */
	
	/**
	* <p>Нестатический метод устанавливает ID шаблона сайта.</p>
	*
	*
	* @param mixed $siteTemplateId  Идентификатор шаблона сайта
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setsitetemplateid.php
	* @author Bitrix
	*/
	public function setSiteTemplateId($siteTemplateId)
	{
		$this->siteTemplateId = $siteTemplateId;
	}

	/**
	 * Get site template id.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод возвращает идентификатор шаблона сайта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getsitetemplateid.php
	* @author Bitrix
	*/
	public function getSiteTemplateId()
	{
		return $this->siteTemplateId;
	}

	/**
	 * Set language id.
	 *
	 * @param mixed $languageId
	 */
	
	/**
	* <p>Нестатический метод устанавливает идентификатор языка сайта.</p>
	*
	*
	* @param mixed $languageId  Идентификатор языка
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setlanguageid.php
	* @author Bitrix
	*/
	public function setLanguageId($languageId)
	{
		$this->languageId = $languageId;
	}

	/**
	 * Get language id.
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод возвращает идентификатор языка.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getlanguageid.php
	* @author Bitrix
	*/
	public function getLanguageId()
	{
		return $this->languageId;
	}

	/**
	 * Set site id.
	 *
	 * @param mixed $siteId
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает ID сайта.</p>
	*
	*
	* @param mixed $siteId  Идентификатор сайта
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setsiteid.php
	* @author Bitrix
	*/
	public function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}

	/**
	 * Return site id.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает идентификатор сайта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getsiteid.php
	* @author Bitrix
	*/
	public function getSiteId()
	{
		return $this->siteId;
	}

	/**
	 * Return result.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает результат типа строка, в которой результат замены шаблона значениями. Шаблон - это почтовый шаблон и тема оформления.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getresult.php
	* @author Bitrix
	*/
	public function getResult()
	{
		return $this->resultString;
	}

	/**
	 * Set params that will be used for replacing placeholders.
	 *
	 * @param array $params
	 */
	
	/**
	* <p>Нестатический метод устанавливает параметры, которые будут использоваться для замены плейсхолдеров.</p>
	*
	*
	* @param array $params  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setparams.php
	* @author Bitrix
	*/
	public function setParams(array $params)
	{
		$this->params = $params;
	}

	/**
	 * Set theme prolog.
	 *
	 * @param mixed $themeProlog
	 */
	
	/**
	* <p>Нестатический метод устанавливает пролог темы.</p>
	*
	*
	* @param mixed $themeProlog  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setthemeprolog.php
	* @author Bitrix
	*/
	public function setThemeProlog($themeProlog)
	{
		$this->themeProlog = $themeProlog;
	}

	/**
	 * Return theme prolog.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод возвращает пролог темы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getthemeprolog.php
	* @author Bitrix
	*/
	public function getThemeProlog()
	{
		return $this->themeProlog;
	}

	/**
	 * Set theme epilog.
	 *
	 * @param mixed $themeEpilog
	 */
	
	/**
	* <p>Нестатический метод устанавливает эпилог темы.</p>
	*
	*
	* @param mixed $themeEpilog  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setthemeepilog.php
	* @author Bitrix
	*/
	public function setThemeEpilog($themeEpilog)
	{
		$this->themeEpilog = $themeEpilog;
	}

	/**
	 * Return theme epilog.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод возвращает эпилог темы.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getthemeepilog.php
	* @author Bitrix
	*/
	public function getThemeEpilog()
	{
		return $this->themeEpilog;
	}

	/**
	 * Set style.
	 *
	 * @param array $arPaths
	 * @param bool $sort
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает стиль.</p>
	*
	*
	* @param array $arPaths  Массив путей
	*
	* @param boolean $sort = false Сортировка
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setstyle.php
	* @author Bitrix
	*/
	public function setStyle($path, $sort = false)
	{
		$sort = ($sort === false ? $this->currentResourceOrder : $sort);
		$this->arStyle[$path] = $sort;
	}

	/**
	 * Set style list.
	 *
	 * @param array $arPaths
	 * @param bool $sort
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает список стилей.</p>
	*
	*
	* @param array $arPaths  Массив путей к файлам стилей
	*
	* @param boolean $sort = false Сортировка
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/setstylearray.php
	* @author Bitrix
	*/
	public function setStyleArray(array $arPaths, $sort = false)
	{
		foreach($arPaths as $path)
			$this->setStyle($path, $sort);
	}

	/**
	 * Return style list that will be added by template.
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает список стилей для добавления в шаблон.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getstyles.php
	* @author Bitrix
	*/
	public function getStyles()
	{
		return $this->arStyle;
	}

	/**
	 * Return styles as string that will be added by template.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает стили в виде строки для добавления в шаблон.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/getstylesstring.php
	* @author Bitrix
	*/
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

	/**
	 * Show styles that will be added by template.
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод отображает стили которые будут использованы в почтовом шаблоне.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/showstyles.php
	* @author Bitrix
	*/
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
				$this->themePath = $templateFields['PATH'];
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
	 * Function includes language files from within the theme directory.
	 *
	 * <p>For example: $this->includeThemeLang("header.php") will include "lang/en/header.php" file. </p>
	 * <p>Note: theme must be inited by setTheme method.</p>
	 * @param string $relativePath
	 * @return void
	 *
	 */
	
	/**
	* <p>Нестатический метод подключает языковые файлы из каталога темы.</p> <p>Например: <code>$this-&gt;includeThemeLang("header.php")</code> подключит файл <code>lang/en/header.php</code>. </p> <p class="note">Примечание: тема должна быть инициирована методом <code>\Bitrix\Main\Mail\EventMessageThemeCompiler::setTheme</code>.</p>
	*
	*
	* @param string $relativePath = "" Относительный путь к файлу
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/includethemelang.php
	* @author Bitrix
	*/
	final public function includeThemeLang($relativePath = "")
	{
		if ($relativePath == "")
		{
			$relativePath = ".description.php";
		}

		$path = $_SERVER["DOCUMENT_ROOT"].$this->themePath."/".$relativePath;
		\Bitrix\Main\Localization\Loc::loadMessages($path);
	}

	/**
	 * Execute prolog, body and epilog.
	 *
	 * @param
	 */
	
	/**
	* <p>Нестатический метод выполняет пролог, тело и эпилог.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/execute.php
	* @author Bitrix
	*/
	public function execute()
	{
		$resultThemeProlog = '';
		$resultThemeEpilog = '';

		if(!$this->themeProlog && $this->contentTypeHtml)
			$this->body = '<?=$this->showStyles()?>' . $this->body;

		$resultBody = $this->executePhp($this->body, 100);
		if($this->themeProlog)
		{
			$this->includeThemeLang('header.php');
			$resultThemeProlog = $this->executePhp($this->themeProlog, 50);
		}

		if($this->themeEpilog)
		{
			$this->includeThemeLang('footer.php');
			$resultThemeEpilog = $this->executePhp($this->themeEpilog, 150);
		}

		$this->resultString = $resultThemeProlog . $resultBody . $resultThemeEpilog;
		$this->executeReplaceCallback();
	}


	protected function executePhp($template, $resourceOrder = 100)
	{
		$this->currentResourceOrder = $resourceOrder;

		try
		{
			$arParams = $this->params;
			$result = eval('use \Bitrix\Main\Mail\EventMessageThemeCompiler; ob_start();?>' . $template . '<? return ob_get_clean();');
		}
		catch(StopException $e)
		{
			ob_clean();
			throw $e;
		}

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

	/**
	 * Include mail component.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Статический метод подключает почтовый компонент.</p>
	*
	*
	* @param mixed $componentName  Название компонента.
	*
	* @param $componentNam $componentTemplate  Шаблон компонента.
	*
	* @param $componentTemplat $arParams = array() Массив параметров.
	*
	* @param mixed $parentComponent = null Родительский компонент.
	*
	* @param array $arFunctionParams = array() .
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/mail/eventmessagethemecompiler/includecomponent.php
	* @author Bitrix
	*/
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

				try
				{
					$result = $component->IncludeComponent($componentTemplate, $arParams, $parentComponent);
				}
				catch(StopException $e)
				{
					$component->AbortResultCache();
					throw $e;
				}

				$arThemeCss = array(); // TODO: use styles array from $component
				foreach($arThemeCss as $cssPath)
					static::getInstance()->setStyle($cssPath);
			}
		}

		return $result;
	}

	/**
	 * Stop execution of template. Throws an exception if instance is exists.
	 *
	 * @return void
	 * @throws \Bitrix\Main\Mail\StopException
	 */
	public static function stop()
	{
		if (static::$instance)
		{
			throw new StopException;
		}
	}
}
