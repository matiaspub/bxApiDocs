<?php
namespace Bitrix\Main\IO;

use \Bitrix\Main\Text as Text;

/**
 *
 */
class Path
{
	const DIRECTORY_SEPARATOR = '/';
	const DIRECTORY_SEPARATOR_ALT = '\\';
	const PATH_SEPARATOR = PATH_SEPARATOR;

	const LOGICAL_TO_PHYSICAL = 1;
	const PHYSICAL_TO_LOGICAL = 2;

	const INVALID_FILENAME_CHARS = "\\\\/:*?\"\\'<>|~\\0";

	public static function normalize($path)
	{
		if (!is_string($path) || ($path == ""))
			return null;

		$pathTmp = preg_replace("'[\\\\/]+'", "/", $path);

		if (($p = strpos($pathTmp, "\0")) !== false)
			$pathTmp = substr($pathTmp, 0, $p);
		if (($p = strpos($pathTmp, self::PATH_SEPARATOR)) !== false)
			$pathTmp = substr($pathTmp, 0, $p);

		while (strpos($pathTmp, ".../") !== false)
			$pathTmp = str_replace(".../", "../", $pathTmp);

		$arPathTmp = explode('/', $pathTmp);
		$arPathStack = array();

		for ($i = 0, $cnt = count($arPathTmp); $i < $cnt; $i++)
		{
			if ($arPathTmp[$i] === '.')
				continue;
			if (($i !== 0) && ($arPathTmp[$i] === ''))
				continue;

			if ($arPathTmp[$i] === "..")
			{
				if (array_pop($arPathStack) === null)
					throw new InvalidPathException($path);
			}
			else
			{
				array_push($arPathStack, $arPathTmp[$i]);
			}
		}

		$pathTmp = implode("/", $arPathStack);

		$pathTmp = preg_replace("'[\\\\/]+'", "/", $pathTmp);
		$pathTmp = rtrim($pathTmp, "\0.\\/+ ");

		if (substr($path, 0, 1) === "/" && substr($pathTmp, 0, 1) !== "/")
			$pathTmp = "/".$pathTmp;

		if ($pathTmp === '')
			$pathTmp = "/";

		return $pathTmp;
	}

	public static function getExtension($path)
	{
		$path = self::getName($path);
		if ($path != '')
		{
			$pos = Text\String::strrpos($path, '.');
			if ($pos !== false)
				return substr($path, $pos + 1);
		}
		return '';
	}

	public static function getName($path)
	{
		$path = self::normalize($path);

		$p = Text\String::strrpos($path, self::DIRECTORY_SEPARATOR);
		if ($p !== false)
			return substr($path, $p + 1);

		return $path;
	}

	public static function getDirectory($path)
	{
		return substr($path, 0, -strlen(self::getName($path)) - 1);
	}

	protected static function convertCharset($path, $direction = 1)
	{
		static $physicalEncoding = "";
		if ($physicalEncoding == "")
			$physicalEncoding = self::getPhysicalEncoding();

		static $logicalEncoding = "";
		if ($logicalEncoding == "")
			$logicalEncoding = self::getLogicalEncoding();

		if ($physicalEncoding == $logicalEncoding)
			return $path;

		if ($direction == self::LOGICAL_TO_PHYSICAL)
			$result = Text\Encoding::convertEncoding($path, $logicalEncoding, $physicalEncoding);
		else
			$result = Text\Encoding::convertEncoding($path, $physicalEncoding, $logicalEncoding);

		return $result;
	}

	protected static function getLogicalEncoding()
	{
		if (defined('BX_UTF'))
			$logicalEncoding = "utf-8";
		elseif (defined("SITE_CHARSET") && (strlen(SITE_CHARSET) > 0))
			$logicalEncoding = SITE_CHARSET;
		elseif (defined("LANG_CHARSET") && (strlen(LANG_CHARSET) > 0))
			$logicalEncoding = LANG_CHARSET;
		elseif (defined("BX_DEFAULT_CHARSET"))
			$logicalEncoding = BX_DEFAULT_CHARSET;
		else
			$logicalEncoding = "windows-1251";

		return strtolower($logicalEncoding);
	}

	protected static function getPhysicalEncoding()
	{
		$physicalEncoding = defined("BX_FILE_SYSTEM_ENCODING") ? BX_FILE_SYSTEM_ENCODING : "";
		if ($physicalEncoding == "")
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN")
				$physicalEncoding = "windows-1251";
			else
				$physicalEncoding = "utf-8";
		}
		return strtolower($physicalEncoding);
	}

	public static function combine()
	{
		$numArgs = func_num_args();
		if ($numArgs <= 0)
			return "";

		$arParts = array();
		for ($i = 0; $i < $numArgs; $i++)
		{
			$arg = func_get_arg($i);
			if (is_array($arg))
			{
				if (empty($arg))
					continue;

				foreach ($arg as $v)
				{
					if (!is_string($v) || $v == "")
						continue;
					$arParts[] = $v;
				}
			}
			elseif (is_string($arg))
			{
				if ($arg == "")
					continue;

				$arParts[] = $arg;
			}
		}

		$result = "";
		foreach ($arParts as $part)
		{
			if ($result !== "")
				$result .= self::DIRECTORY_SEPARATOR;
			$result .= $part;
		}

		$result = self::normalize($result);

		return $result;
	}

	public static function convertRelativeToAbsolute($relativePath)
	{
		if (!is_string($relativePath))
			throw new \Bitrix\Main\ArgumentTypeException("relativePath", "string");
		if ($relativePath == "")
			throw new \Bitrix\Main\ArgumentNullException("relativePath");

		return self::combine($_SERVER["DOCUMENT_ROOT"], $relativePath);
	}

	public static function convertSiteRelativeToAbsolute($relativePath, $site = null)
	{
		if (!is_string($relativePath) || $relativePath == "")
			$site = SITE_ID;

		$basePath = CSite::getSiteDocRoot($site);

		return self::combine($basePath, $relativePath);
	}

	public static function convertLogicalToPhysical($path)
	{
		return self::convertCharset($path, self::LOGICAL_TO_PHYSICAL);
	}

	public static function convertPhysicalToLogical($path)
	{
		return self::convertCharset($path, self::PHYSICAL_TO_LOGICAL);
	}

	public static function validate($path)
	{
		if (!is_string($path))
			return false;

		$p = trim($path);
		if ($p == "")
			return false;

		if (strpos($path, "\0") !== false)
			return false;

		return (preg_match("#^([a-z]:)?/([^".self::INVALID_FILENAME_CHARS."]+/?)*$#is", $path) > 0);
	}

	public static function validateFilename($filename)
	{
		if (!is_string($filename))
			return false;

		$fn = trim($filename);
		if ($fn == "")
			return false;

		if (strpos($filename, "\0") !== false)
			return false;

		return (preg_match("#^[^".self::INVALID_FILENAME_CHARS."]+$#is", $filename) > 0);
	}

	public static function randomizeInvalidFilename($filename)
	{
		return preg_replace('#(['.self::INVALID_FILENAME_CHARS.'])#e', "chr(rand(97, 122))", $filename);
	}
}
