<?php
namespace Bitrix\Main;

use Bitrix\Main\Type;
use Bitrix\Main\Text;
use Bitrix\Main\IO;

/**
 * Class Request contains current request
 * @package Bitrix\Main
 */
abstract class Request
	extends Type\ParameterDictionary
{
	/**
	 * @var Server
	 */
	protected $server;

	protected $requestedFile = null;
	protected $requestedFileDirectory = null;

	public function __construct(Server $server, array $request)
	{
		parent::__construct($request);

		$this->server = $server;
	}

	public function addFilter(Type\IRequestFilter $filter)
	{
		$filteredValues = $filter->filter($this->values);

		if ($filteredValues != null)
			$this->setValuesNoDemand($filteredValues);
	}

	public function getPhpSelf()
	{
		return $this->server->getPhpSelf();
	}

	public function getScriptName()
	{
		return $this->server->getScriptName();
	}

	public function getRequestedPage()
	{
		if ($this->requestedFile !== null)
			return $this->requestedFile;

		$page = $this->getScriptName();
		if (empty($page))
		{
			return $this->requestedFile = $page;
		}

		$page = IO\Path::normalize($page);

		if (substr($page, 0, 1) !== "/" && !preg_match("#^[a-z]:[/\\\\]#i", $page))
			$page = "/".$page;

		return $this->requestedFile = $page;
	}

	public function getRequestedPageDirectory()
	{
		if ($this->requestedFileDirectory != null)
			return $this->requestedFileDirectory;

		$requestedFile = $this->getRequestedPage();

		return $this->requestedFileDirectory = IO\Path::getDirectory($requestedFile);
	}

	public function isAdminSection()
	{
		$requestedDir = $this->getRequestedPageDirectory();
		return (substr($requestedDir, 0, strlen("/bitrix/admin/")) == "/bitrix/admin/"
			|| substr($requestedDir, 0, strlen("/bitrix/updates/")) == "/bitrix/updates/"
			|| (defined("ADMIN_SECTION") &&  ADMIN_SECTION == true)
			|| (defined("BX_PUBLIC_TOOLS") && BX_PUBLIC_TOOLS === true)
		);
	}
}
