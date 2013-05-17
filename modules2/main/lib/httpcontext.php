<?php
namespace Bitrix\Main;

class HttpContext
	extends Context
{
	/** @var \Bitrix\Main\Security\CurrentUser */
	protected $user;

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

	/**
	 * Sets current user for the context.
	 *
	 * @param Security\CurrentUser $user
	 */
	static public function setUser(\Bitrix\Main\Security\CurrentUser $user)
	{
		$this->user = $user;
	}

	/**
	 * Returns current user.
	 *
	 * @return \Bitrix\Main\Security\CurrentUser
	 */
	static public function getUser()
	{
		return $this->user;
	}

	static public function getSession()
	{
		return $this->session;
	}

	static public function rewriteUri($url, $queryString, $redirectStatus = null)
	{
		/** @var $request HttpRequest */
		$request = $this->request;
		$request->modifyByQueryString($queryString);

		$this->server->rewriteUri($url, $queryString, $redirectStatus);
	}

	static public function transferUri($url, $queryString)
	{
		/** @var $request HttpRequest */
		$request = $this->request;
		$request->modifyByQueryString($queryString);

		$this->server->transferUri($url, $queryString);
	}
}
