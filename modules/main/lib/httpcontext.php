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
	public function setUser(\Bitrix\Main\Security\CurrentUser $user)
	{
		$this->user = $user;
	}

	/**
	 * Returns current user.
	 *
	 * @return \Bitrix\Main\Security\CurrentUser
	 */
	public function getUser()
	{
		return $this->user;
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
