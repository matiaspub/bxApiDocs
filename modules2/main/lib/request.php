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

	static public function __construct(Server $server, array $request)
	{
		parent::__construct($request);

		$this->server = $server;
	}

	static public function getPhpSelf()
	{
		return $this->server->getPhpSelf();
	}

	static public function getScriptName()
	{
		return $this->server->getScriptName();
	}

	static public function getRequestedPage()
	{
		if ($this->requestedFile != null)
			return $this->requestedFile;

		$page = $this->getScriptName();

		$page = IO\Path::normalize($page);

		if (IO\Path::validate($page))
			return $this->requestedFile = $page;

		throw new SystemException("Script name is not valid");
	}

	static public function getRequestedPageDirectory()
	{
		if ($this->requestedFileDirectory != null)
			return $this->requestedFileDirectory;

		$requestedFile = $this->getRequestedPage();

		return $this->requestedFileDirectory = IO\Path::getDirectory($requestedFile);
	}
}
