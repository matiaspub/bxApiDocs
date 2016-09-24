<?php
namespace Bitrix\Main;

/**
 * Class HttpContext extends Context with http specific methods.
 * @package Bitrix\Main
 */
class HttpContext
	extends Context
{
	protected $session;

	/**
	 * Creates new instance of context.
	 *
	 * @param HttpApplication $application
	 */
	static public function __construct(HttpApplication $application)
	{
		parent::__construct($application);
	}

	public function getSession()
	{
		return $this->session;
	}

	public function rewriteUri($url, $queryString, $redirectStatus = null)
	{
		/** @var $request HttpRequest */
		$request = $this->request;
		$request->modifyByQueryString($queryString);

		$this->server->rewriteUri($url, $queryString, $redirectStatus);
	}

	public function transferUri($url, $queryString)
	{
		/** @var $request HttpRequest */
		$request = $this->request;
		$request->modifyByQueryString($queryString);

		$this->server->transferUri($url, $queryString);
	}
}
