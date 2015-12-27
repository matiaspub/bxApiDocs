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
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Returns request object of the context.
	 *
	 * @return HttpRequest
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
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Returns backreference to Application.
	 *
	 * @return Application
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
	public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Returns current site
	 *
	 * @return string
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
	public function setCulture(Context\Culture $culture)
	{
		$this->culture = $culture;
	}

	/**
	 * Sets language of the context.
	 *
	 * @param string $language
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
	public static function getCurrent()
	{
		$application = Application::getInstance();
		return $application->getContext();
	}
}
