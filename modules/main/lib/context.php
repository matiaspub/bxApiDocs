<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

/**
 * Context of current request.
 */
class Context
{
	/** @var Application */
	protected $application;

	/** @var Response */
	protected $response;

	/** @var Request */
	protected $request;

	/** @var Server */
	protected $server;

	/** @var string */
	private $language;

	/** @var string */
	private $site;

	/** @var Environment */
	protected $env;

	/** @var \Bitrix\Main\Context\Culture */
	protected $culture;

	/** @var array */
	protected $params;

	/**
	 * Creates new instance of context.
	 *
	 * @param Application $application
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести какие-то действия, при создании объекта.</p>
	*
	*
	* @param mixed $Bitrix  Приложение
	*
	* @param Bitri $Main  
	*
	* @param Application $application  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/__construct.php
	* @author Bitrix
	*/
	public function __construct(Application $application)
	{
		$this->application = $application;
	}

	/**
	 * Initializes context by request and response objects.
	 *
	 * @param Request $request
	 * @param Response $response
	 * @param Server $server
	 * @param Environment $env
	 */
	
	/**
	* <p>Нестатический метод инициализирует контекст по запросу и отклику объекта.</p>
	*
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Request $request  
	*
	* @param Request $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Response $response = null 
	*
	* @param mixed $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Server $server  
	*
	* @param Server $Bitrix  
	*
	* @param Bitri $Main  
	*
	* @param Environment $env  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/initialize.php
	* @author Bitrix
	*/
	public function initialize(Request $request, Response $response = null, Server $server, array $params = array())
	{
		$this->request = $request;
		$this->response = $response;
		$this->server = $server;
		$this->params = $params;
	}

	public function getEnvironment()
	{
		if ($this->env === null)
			$this->env = new Environment($this->params["env"]);
		return $this->env;
	}

	/**
	 * Returns response object of the context.
	 *
	 * @return HttpResponse
	 */
	
	/**
	* <p>Нестатический метод возвращает объект отклика контекста.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\HttpResponse 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getresponse.php
	* @author Bitrix
	*/
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Sets response of the context.
	 *
	 * @param Response $response Response.
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает ответ контента.</p>
	*
	*
	* @param mixed $Bitrix  Отклик.
	*
	* @param Bitri $Main  
	*
	* @param Response $response  
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/setresponse.php
	* @author Bitrix
	*/
	public function setResponse(Response $response)
	{
		$this->response = $response;
	}

	/**
	 * Returns request object of the context.
	 *
	 * @return HttpRequest
	 */
	
	/**
	* <p>Нестатический метод возвращает запрошенный объект контекста.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\HttpRequest 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getrequest.php
	* @author Bitrix
	*/
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Returns server object of the context.
	 *
	 * @return Server
	 */
	
	/**
	* <p>Нестатический метод возвращает серверный объект контекста.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Server 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getserver.php
	* @author Bitrix
	*/
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Returns backreference to Application.
	 *
	 * @return Application
	 */
	
	/**
	* <p>Нестатический метод возвращает обратную ссылку на приложение.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Application 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getapplication.php
	* @author Bitrix
	*/
	public function getApplication()
	{
		return $this->application;
	}

	/**
	 * Returns culture of the context.
	 *
	 * @return \Bitrix\Main\Context\Culture
	 */
	
	/**
	* <p>Нестатический метод возвращает региональные культурные настройки для контекста.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Context\Culture 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getculture.php
	* @author Bitrix
	*/
	public function getCulture()
	{
		if ($this->culture === null)
			$this->culture = new Context\Culture();
		return $this->culture;
	}

	/**
	 * Returns current language (en, ru)
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает текущую языковую раскладку (en, ru).</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getlanguage.php
	* @author Bitrix
	*/
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Returns current site
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает текущий сайт.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getsite.php
	* @author Bitrix
	*/
	public function getSite()
	{
		return $this->site;
	}

	/**
	 * Sets culture of the context.
	 *
	 * @param \Bitrix\Main\Context\Culture $culture
	 */
	
	/**
	* <p>Нестатический метод устанавливает региональные культурные настройки для контекста.</p>
	*
	*
	* @param mixed $Bitrix  Региональные настройки
	*
	* @param Bitri $Main  
	*
	* @param Mai $Context  
	*
	* @param Culture $culture  
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/setculture.php
	* @author Bitrix
	*/
	public function setCulture(Context\Culture $culture)
	{
		$this->culture = $culture;
	}

	/**
	 * Sets language of the context.
	 *
	 * @param string $language
	 */
	
	/**
	* <p>Нестатический метод устанавливает язык контекста.</p>
	*
	*
	* @param string $language  Язык контекста
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/setlanguage.php
	* @author Bitrix
	*/
	public function setLanguage($language)
	{
		$this->language = $language;
	}

	/**
	 * Sets site of the context.
	 *
	 * @param string $site
	 */
	
	/**
	* <p>Нестатический метод устанавливает сайт для контекста.</p>
	*
	*
	* @param string $site  Сайт
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/setsite.php
	* @author Bitrix
	*/
	public function setSite($site)
	{
		$this->site = $site;
	}

	/**
	 * Static method returns current instance of context.
	 *
	 * @static
	 * @return Context
	 */
	
	/**
	* <p>Статический метод возвращает текущий экземпляр контекста.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Context 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/context/getcurrent.php
	* @author Bitrix
	*/
	public static function getCurrent()
	{
		$application = Application::getInstance();
		return $application->getContext();
	}
}
