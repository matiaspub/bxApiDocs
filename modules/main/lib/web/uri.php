<?php
namespace Bitrix\Main\Web;

use \Bitrix\Main\IO;

class Uri
{
	protected $uri;
	protected $uriType;

	protected static $directoryIndex = "index.php";

	public function __construct($uri, $uriType)
	{
		if ($uriType < UriType::UNKNOWN || $uriType > UriType::RELATIVE)
			throw new \Bitrix\Main\ArgumentOutOfRangeException("uriType", UriType::getTypeNamesArray());

		if ($uri == null)
			$uri = "";

		$this->uri = $uri;
		$this->uriType = $uriType;
	}

	public function parse($uriPart = -1)
	{
		return parse_url($this->uri, $uriPart);
	}

	public function convertToPath()
	{
		if ($this->uriType != UriType::RELATIVE)
		{
			$path = $this->parse(UriPart::PATH);
		}
		else
		{
			$path = $this->uri;
			$p = strpos($path, "?");
			if ($p !== false)
				$path = substr($path, 0, $p);
		}

		if (substr($path, -1, 1) === "/")
			$path = self::addDirectoryIndex($path);

		$path = IO\Path::normalize($path);

		if (IO\Path::validate($path))
			return $path;

		throw new \Bitrix\Main\SystemException("Uri is not valid");
	}

	public static function addDirectoryIndex($dir)
	{
		if (!is_string($dir))
			throw new \Bitrix\Main\ArgumentTypeException("dir", "string");

		$dir = rtrim($dir, "/");

		return $dir."/".self::$directoryIndex;
	}

	public static function isPathTraversalUri($uri)
	{
		if (($pos = strpos($uri, "?")) !== false)
			$uri = substr($uri, 0, $pos);

		$uri = trim($uri);
		return preg_match("#(?:/|2f|^)(?:(?:%0*(25)*2e)|\.){2,}(?:/|%0*(25)*2f|$)#", $uri) ? true : false;
	}
}

class UriType
{
	const UNKNOWN = 0;
	const ABSOLUTE = 1;
	const RELATIVE = 2;

	public static function getTypeNamesArray()
	{
		return array("UriType.UNKNOWN", "UriType.ABSOLUTE", "UriType.RELATIVE");
	}
}

class UriPart
{
	const ALL = -1;
	const SCHEME = PHP_URL_SCHEME;
	const HOST = PHP_URL_HOST;
	const PORT = PHP_URL_PORT;
	const USER = PHP_URL_USER;
	const PASSWORD = PHP_URL_PASS;
	const PATH = PHP_URL_PATH;
	const QUERY = PHP_URL_QUERY;
	const FRAGMENT = PHP_URL_FRAGMENT;
}