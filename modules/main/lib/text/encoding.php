<?php
namespace Bitrix\Main\Text;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;

class Encoding
{
	const PATH_TO_CONVERT_TABLES = "/bitrix/modules/main/cvtables/";

	private static $instance;

	private $arErrors = array();

	public static function convertEncoding($string, $charsetFrom, $charsetTo, &$errorMessage = "")
	{
		$string = strval($string);

		if (strcasecmp($charsetFrom, $charsetTo) == 0)
			return $string;

		$errorMessage = '';

		if ($string == '')
			return '';

		if (extension_loaded("mbstring") && mb_encoding_aliases($charsetFrom) && mb_encoding_aliases($charsetTo))
		{
			//For UTF-16 we have to detect the order of bytes
			//Default for mbstring extension is Big endian
			//Little endian have to pointed explicitly
			if (strtoupper($charsetFrom) == "UTF-16")
			{
				$ch = substr($string, 0, 1);
				//If Little endian found - cutoff BOF bytes and point mbstring to this fact explicitly
				if ($ch == "\xFF" && substr($string, 1, 1) == "\xFE")
					return mb_convert_encoding(substr($string, 2), $charsetTo, "UTF-16LE");
				//If it is Big endian, just remove BOF bytes
				elseif ($ch == "\xFE" && substr($string, 1, 1) == "\xFF")
					return mb_convert_encoding(substr($string, 2), $charsetTo, $charsetFrom);
				//Otherwise assime Little endian without BOF
				else
					return mb_convert_encoding($string, $charsetTo, "UTF-16LE");
			}
			else
			{
				$res = mb_convert_encoding($string, $charsetTo, $charsetFrom);
				if (strlen($res) > 0)
					return $res;
			}
		}

		if (!defined("BX_ICONV_DISABLE") || BX_ICONV_DISABLE !== true)
		{
			$utf_string = false;
			if (strtoupper($charsetFrom) == "UTF-16")
			{
				$ch = substr($string, 0, 1);
				if (($ch != "\xFF") || ($ch != "\xFE"))
					$utf_string = "\xFF\xFE".$string;
			}
			if (function_exists('iconv'))
			{
				if ($utf_string)
					$res = iconv($charsetFrom, $charsetTo."//IGNORE", $utf_string);
				else
					$res = iconv($charsetFrom, $charsetTo."//IGNORE", $string);

				if (!$res)
					$errorMessage .= "Iconv reported failure while converting string to requested character encoding. ";

				return $res;
			}
			elseif (function_exists('libiconv'))
			{
				if ($utf_string)
					$res = libiconv($charsetFrom, $charsetTo, $utf_string);
				else
					$res = libiconv($charsetFrom, $charsetTo, $string);

				if (!$res)
					$errorMessage .= "Libiconv reported failure while converting string to requested character encoding. ";

				return $res;
			}
		}

		$cvt = self::getInstance();
		$res = $cvt->convert($string, $charsetFrom, $charsetTo);
		if (!$res)
		{
			$arErrors = $cvt->getErrors();
			if (count($arErrors) > 0)
				$errorMessage = implode("\n", $arErrors);
		}

		return $res;
	}

	public static function convertEncodingArray($arData, $charsetFrom, $charsetTo, &$errorMessage = "")
	{
		if (!is_array($arData))
		{
			if (is_string($arData))
			{
				$arData = self::convertEncoding($arData, $charsetFrom, $charsetTo, $errorMessage);
			}
		}
		else
		{
			foreach ($arData as $key => $value)
			{
				$s = '';

				$newKey = self::convertEncoding($key, $charsetFrom, $charsetTo, $s);
				$arData[$newKey] = self::convertEncodingArray($value, $charsetFrom, $charsetTo, $s);

				if($newKey != $key)
					unset($arData[$key]);

				if($s!=='')
				{
					$errorMessage .= ($errorMessage == "" ? "" : "\n").$s;
				}
			}
		}

		return $arData;
	}

	public static function convertEncodingToCurrent($string)
	{
		$isUtf8String = self::detectUtf8($string);
		$isUtf8Config = Application::isUtfMode();

		$currentCharset = null;

		$context = Application::getInstance()->getContext();
		if ($context != null)
		{
			$culture = $context->getCulture();
			if ($culture != null && method_exists($culture, "getCharset"))
				$currentCharset = $culture->getCharset();
		}

		if ($currentCharset == null)
			$currentCharset = Configuration::getValue("default_charset");

		if ($currentCharset == null)
			$currentCharset = "Windows-1251";

		$fromCp = "";
		$toCp = "";
		if ($isUtf8Config && !$isUtf8String)
		{
			$fromCp = $currentCharset;
			$toCp = "UTF-8";
		}
		elseif (!$isUtf8Config && $isUtf8String)
		{
			$fromCp = "UTF-8";
			$toCp = $currentCharset;
		}

		if ($fromCp !== $toCp)
			$string = self::convertEncoding($string, $fromCp, $toCp);

		return $string;
	}

	public static function detectUtf8($string)
	{
		//http://mail.nl.linux.org/linux-utf8/1999-09/msg00110.html

		if(preg_match_all("/(?:%)([0-9A-F]{2})/i", $string, $match))
		{
			$string = pack("H*", strtr(implode('', $match[1]), 'abcdef', 'ABCDEF'));
		}

		//valid UTF-8 octet sequences
		//0xxxxxxx
		//110xxxxx 10xxxxxx
		//1110xxxx 10xxxxxx 10xxxxxx
		//11110xxx 10xxxxxx 10xxxxxx 10xxxxxx

		$prevBits8and7 = 0;
		$isUtf = 0;
		foreach(unpack("C*", $string) as $byte)
		{
			$hiBits8and7 = $byte & 0xC0;
			if ($hiBits8and7 == 0x80)
			{
				if ($prevBits8and7 == 0xC0)
					$isUtf++;
				elseif (($prevBits8and7 & 0x80) == 0x00)
					$isUtf--;
			}
			elseif ($prevBits8and7 == 0xC0)
			{
					$isUtf--;
			}
			$prevBits8and7 = $hiBits8and7;
		}
		return ($isUtf > 0);
	}

	/**
	 * @static
	 * @return Encoding
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	protected function hexToUtf($utfCharInHex)
	{
		$result = "";

		$utfCharInDec = hexdec($utfCharInHex);
		if ($utfCharInDec < 128)
			$result .= chr($utfCharInDec);
		elseif ($utfCharInDec < 2048)
			$result .= chr(($utfCharInDec >> 6) + 192).chr(($utfCharInDec & 63) + 128);
		elseif ($utfCharInDec < 65536)
			$result .= chr(($utfCharInDec >> 12) + 224).chr((($utfCharInDec >> 6) & 63) + 128).chr(($utfCharInDec & 63) + 128);
		elseif ($utfCharInDec < 2097152)
			$result .= chr($utfCharInDec >> 18 + 240).chr((($utfCharInDec >> 12) & 63) + 128).chr(($utfCharInDec >> 6) & 63 + 128). chr($utfCharInDec & 63 + 128);

		return $result;
	}

	protected function buildConvertTable()
	{
		static $arCvTables = array();

		for ($i = 0, $cnt = func_num_args(); $i < $cnt; $i++)
		{
			$fileName = func_get_arg($i);

			if (isset($arCvTables[$fileName]))
				continue;

			$arCvTables[$fileName] = array();

			$pathToTable = $_SERVER["DOCUMENT_ROOT"].self::PATH_TO_CONVERT_TABLES.$fileName;
			if (!file_exists($pathToTable))
			{
				$this->addError(str_replace("#FILE#", $pathToTable, "File #FILE# is not found."));
				continue;
			}

			if (!is_file($pathToTable))
			{
				$this->addError(str_replace("#FILE#", $pathToTable, "File #FILE# is not a file."));
				continue;
			}

			if (!($hFile = fopen($pathToTable, "r")))
			{
				$this->addError(str_replace("#FILE#", $pathToTable, "Can not open file #FILE# for reading."));
				continue;
			}

			while (!feof($hFile))
			{
				if ($line = trim(fgets($hFile, 1024)))
				{
					if (substr($line, 0, 1) != "#")
					{
						$hexValue = preg_split("/[\s,]+/", $line, 3);
						if (substr($hexValue[1], 0, 1) != "#")
						{
							$key = strtoupper(str_replace("0x", "", $hexValue[1]));
							$value = strtoupper(str_replace("0x", "", $hexValue[0]));
							$arCvTables[func_get_arg($i)][$key] = $value;
						}
					}
				}
			}

			fclose($hFile);
		}

		return $arCvTables;
	}

	public function convert($sourceString, $charsetFrom, $charsetTo)
	{
		$this->clearErrors();

		if (strlen($sourceString) <= 0)
		{
			$this->addError("Nothing to convert.");
			return false;
		}

		if (strlen($charsetFrom) <= 0)
		{
			$this->addError("Source charset is not set.");
			return false;
		}

		if (strlen($charsetTo) <= 0)
		{
			$this->addError("Destination charset is not set.");
			return false;
		}

		$charsetFrom = strtolower($charsetFrom);
		$charsetTo = strtolower($charsetTo);

		if($charsetFrom == $charsetTo)
			return $sourceString;

		$resultString = "";
		if($charsetFrom == "ucs-2")
		{
			$arConvertTable = $this->buildConvertTable($charsetTo);
			$l = strlen($sourceString);
			for($i = 0; $i < $l; $i+=2)
			{
				$hexChar = strtoupper(dechex(ord($sourceString[$i])).dechex(ord($sourceString[$i+1])));
				$hexChar = str_pad($hexChar, 4, "0", STR_PAD_LEFT);
				if($arConvertTable[$charsetTo][$hexChar])
				{
					if($charsetTo != "utf-8")
						$resultString .= chr(hexdec($arConvertTable[$charsetTo][$hexChar]));
					else
						$resultString .= $this->hexToUtf($arConvertTable[$charsetTo][$hexChar]);
				}
			}
		}
		elseif($charsetFrom == "utf-16")
		{
			$arConvertTable = $this->buildConvertTable($charsetTo);
			$l = strlen($sourceString);
			for($i = 0; $i < $l; $i+=2)
			{
				$hexChar = sprintf("%02X%02X", ord($sourceString[$i+1]), ord($sourceString[$i]));
				if($arConvertTable[$charsetTo][$hexChar])
				{
					if($charsetTo != "utf-8")
						$resultString .= chr(hexdec($arConvertTable[$charsetTo][$hexChar]));
					else
						$resultString .= $this->hexToUtf($arConvertTable[$charsetTo][$hexChar]);
				}
			}
		}
		elseif($charsetFrom != "utf-8")
		{
			if($charsetTo != "utf-8")
				$arConvertTable = $this->buildConvertTable($charsetFrom, $charsetTo);
			else
				$arConvertTable = $this->buildConvertTable($charsetFrom);

			if(!$arConvertTable)
				return false;

			$stringLength = function_exists('mb_strlen') ? mb_strlen($sourceString, '8bit') : strlen($sourceString);

			for ($i = 0; $i < $stringLength; $i++)
			{
				$hexChar = strtoupper(dechex(ord($sourceString[$i])));

				if(strlen($hexChar) == 1)
					$hexChar = "0".$hexChar;

				if(($charsetFrom == "gsm0338") && ($hexChar == '1B'))
				{
					$i++;
					$hexChar .= strtoupper(dechex(ord($sourceString[$i])));
				}

				if($charsetTo != "utf-8")
				{
					if(in_array($hexChar, $arConvertTable[$charsetFrom]))
					{
						$unicodeHexChar = array_search($hexChar, $arConvertTable[$charsetFrom]);
						$arUnicodeHexChar = explode("+", $unicodeHexChar);
						$l = count($arUnicodeHexChar);
						for ($j = 0; $j < $l; $j++)
						{
							if (array_key_exists($arUnicodeHexChar[$j], $arConvertTable[$charsetTo]))
								$resultString .= chr(hexdec($arConvertTable[$charsetTo][$arUnicodeHexChar[$j]]));
							else
								$this->addError(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in destination encoding table."));
						}
					}
					else
						$this->addError(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in source encoding table."));
				}
				else
				{
					if(in_array("$hexChar", $arConvertTable[$charsetFrom]))
					{
						$unicodeHexChar = array_search($hexChar, $arConvertTable[$charsetFrom]);
						$arUnicodeHexChar = explode("+", $unicodeHexChar);
						$l = count($arUnicodeHexChar);
						for ($j = 0; $j < $l; $j++)
							$resultString .= $this->hexToUtf($arUnicodeHexChar[$j]);
					}
					else
						$this->addError(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in source encoding table."));
				}
			}
		}
		else
		{
			$arConvertTable = $this->buildConvertTable($charsetTo);
			if(!$arConvertTable)
				return false;

			foreach($arConvertTable[$charsetTo] as $unicodeHexChar => $hexChar)
			{
				$EntitieOrChar = chr(hexdec($hexChar));
				$sourceString = str_replace($this->hexToUtf($unicodeHexChar), $EntitieOrChar, $sourceString);
			}
			$resultString = $sourceString;
		}

		return $resultString;
	}

	public function getErrors()
	{
		return $this->arErrors;
	}

	protected function addError($error, $errorCode = "")
	{
		if (empty($error))
			return;

		$fs = (empty($errorCode) ? "%s" : "[%s] %s");
		$this->arErrors[] = sprintf($fs, $error, $errorCode);
	}

	protected function clearErrors()
	{
		$this->arErrors = array();
	}
}
?>