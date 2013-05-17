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

	/** @var Environment */
	protected $env;

	/** @var \Bitrix\Main\Context\Culture */
	protected $culture;

	/**
	 * Creates new instance of context.
	 *
	 * @param Application $application
	 */
	static public function __construct(Application $application)
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
	static public function initialize(Request $request, Response $response, Server $server, Environment $env)
	{
		$this->request = $request;
		$this->response = $response;
		$this->server = $server;
		$this->env = $env;
	}

	/**
	 * Returns response object of the context.
	 *
	 * @return Response
	 */
	static public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Returns request object of the context.
	 *
	 * @return Request
	 */
	static public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Returns server object of the context.
	 *
	 * @return Server
	 */
	static public function getServer()
	{
		return $this->server;
	}

	/**
	 * Returns backreference to Application.
	 *
	 * @return Application
	 */
	static public function getApplication()
	{
		return $this->application;
	}

	/**
	 * Returns culture of the context.
	 *
	 * @return \Bitrix\Main\Context\Culture
	 */
	static public function getCulture()
	{
		return $this->culture;
	}

	/**
	 * Returns current language (en, ru)
	 *
	 * @return string
	 */
	static public function getLanguage()
	{
		return $this->language;
	}

	/**
	 * Sets culture of the context.
	 *
	 * @param \Bitrix\Main\Context\Culture $culture
	 */
	static public function setCulture(Context\Culture $culture)
	{
		$this->culture = $culture;
	}

	/**
	 * Sets language of the context.
	 *
	 * @param string $language
	 */
	static public function setLanguage($language)
	{
		$this->language = $language;
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
