<?php
namespace Bitrix\Main;

use \Bitrix\Main\Text;
use \Bitrix\Main\IO;

abstract class Request
	extends \Bitrix\Main\System\ReadonlyDictionary
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
		if ($this->requestedFile != null)
			return $this->requestedFile;

		$page = $this->getScriptName();

		$page = IO\Path::normalize($page);

		if (IO\Path::validate($page))
			return $this->requestedFile = $page;

		throw new SystemException("Script name is not valid");
	}

	public function getRequestedPageDirectory()
	{
		if ($this->requestedFileDirectory != null)
			return $this->requestedFileDirectory;

		$requestedFile = $this->getRequestedPage();

		return $this->requestedFileDirectory = IO\Path::getDirectory($requestedFile);
	}
}
