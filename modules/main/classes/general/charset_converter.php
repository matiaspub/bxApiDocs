<?
// define("PATH2CONVERT_TABLES", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/cvtables/");

class CharsetConverter
{
	private static $instance;

	private $arErrors = array();
	private $ignoreErrors = false;

	/**
	 * @static
	 * @return CharsetConverter
	 */
	public static function GetInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}

		return self::$instance;
	}

	public static function ConvertCharset($string, $charset_in, $charset_out, &$errorMessage = "", $ignoreErrors = false)
	{
		$string = strval($string);

		if(strcasecmp($charset_in, $charset_out) == 0)
			return $string;

		$errorMessage = '';

		if ($string == '')
			return '';

		if (extension_loaded("mbstring") && @mb_encoding_aliases($charset_in) && @mb_encoding_aliases($charset_out))
		{
			//For UTF-16 we have to detect the order of bytes
			//Default for mbstring extension is Big endian
			//Little endian have to pointed explicitly
			if (strtoupper($charset_in) == "UTF-16")
			{
				$ch = substr($string, 0, 1);
				//If Little endian found - cutoff BOF bytes and point mbstring to this fact explicitly
				if ($ch == "\xFF" && substr($string, 1, 1) == "\xFE")
					return mb_convert_encoding(substr($string, 2), $charset_out, "UTF-16LE");
				//If it is Big endian, just remove BOF bytes
				elseif ($ch == "\xFE" && substr($string, 1, 1) == "\xFF")
					return mb_convert_encoding(substr($string, 2), $charset_out, $charset_in);
				//Otherwise assime Little endian without BOF
				else
					return mb_convert_encoding($string, $charset_out, "UTF-16LE");
			}
			else
			{
				$res = mb_convert_encoding($string, $charset_out, $charset_in);
				if (strlen($res) > 0)
					return $res;
			}
		}

		if (!defined("BX_ICONV_DISABLE") || BX_ICONV_DISABLE !== true)
		{
			$utf_string = false;
			if (strtoupper($charset_in) == "UTF-16")
			{
				$ch = substr($string, 0, 1);
				if (($ch != "\xFF") || ($ch != "\xFE"))
					$utf_string = "\xFF\xFE".$string;
			}
			if (function_exists('iconv'))
			{
				if ($utf_string)
					$res = iconv($charset_in, $charset_out."//IGNORE", $utf_string);
				else
					$res = iconv($charset_in, $charset_out."//IGNORE", $string);

				if (!$res && !$ignoreErrors)
					$errorMessage .= "Iconv reported failure while converting string to requested character encoding. ";

				return $res;
			}
			elseif (function_exists('libiconv'))
			{
				if ($utf_string)
					$res = libiconv($charset_in, $charset_out, $utf_string);
				else
					$res = libiconv($charset_in, $charset_out, $string);

				if (!$res && !$ignoreErrors)
					$errorMessage .= "Libiconv reported failure while converting string to requested character encoding. ";

				return $res;
			}
		}

		$cvt = self::GetInstance();
		$cvt->ignoreErrors = $ignoreErrors;
		$res = $cvt->Convert($string, $charset_in, $charset_out);
		if (!$res)
		{
			$arErrors = $cvt->GetErrors();
			if (count($arErrors) > 0)
				$errorMessage = implode("\n", $arErrors);
		}

		return $res;
	}

	protected function HexToUtf($utfCharInHex)
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

	protected function BuildConvertTable()
	{
		static $BX_CHARSET_TABLE_CACHE = array();

		for ($i = 0, $n = func_num_args(); $i < $n; $i++)
		{
			$fileName = func_get_arg($i);

			if(isset($BX_CHARSET_TABLE_CACHE[$fileName]))
				continue;

			$BX_CHARSET_TABLE_CACHE[$fileName] = Array();

			if(!file_exists(PATH2CONVERT_TABLES.$fileName))
			{
				$this->AddError(str_replace("#FILE#", PATH2CONVERT_TABLES.$fileName, "File #FILE# is not found."));
				continue;
			}

			if (!is_file(PATH2CONVERT_TABLES.$fileName))
			{
				$this->AddError(str_replace("#FILE#", PATH2CONVERT_TABLES.$fileName, "File #FILE# is not a file."));
				continue;
			}

			if (!($hFile = fopen(PATH2CONVERT_TABLES.$fileName, "r")))
			{
				$this->AddError(str_replace("#FILE#", PATH2CONVERT_TABLES.$fileName, "Can not open file #FILE# for reading."));
				continue;
			}

			while (!feof($hFile))
			{
				if ($line = trim(fgets($hFile, 1024)))
				{
					if (substr($line, 0, 1) != "#")
					{
						$hexValue = preg_split("/[\\s,]+/", $line, 3);
						if (substr($hexValue[1], 0, 1) != "#")
						{
							$key = strtoupper(str_replace("0x", "", $hexValue[1]));
							$value = strtoupper(str_replace("0x", "", $hexValue[0]));
							$BX_CHARSET_TABLE_CACHE[$fileName][$key] = $value;
						}
					}
				}
			}

			fclose($hFile);
		}

		return $BX_CHARSET_TABLE_CACHE;
	}

	public function Convert($sourceString, $charsetFrom, $charsetTo)
	{
		$this->ClearErrors();

		if (strlen($sourceString) <= 0)
		{
			$this->AddError("Nothing to convert.");
			return false;
		}

		if (strlen($charsetFrom) <= 0)
		{
			$this->AddError("Source charset is not set.");
			return false;
		}

		if (strlen($charsetTo) <= 0)
		{
			$this->AddError("Destination charset is not set.");
			return false;
		}

		$charsetFrom = strtolower($charsetFrom);
		$charsetTo = strtolower($charsetTo);

		if($charsetFrom == $charsetTo)
			return $sourceString;

		$resultString = "";
		if($charsetFrom == "ucs-2")
		{
			$arConvertTable = $this->BuildConvertTable($charsetTo);
			for($i = 0, $n = strlen($sourceString); $i < $n; $i+=2)
			{
				$hexChar = strtoupper(dechex(ord($sourceString[$i])).dechex(ord($sourceString[$i+1])));
				$hexChar = str_pad($hexChar, 4, "0", STR_PAD_LEFT);
				if($arConvertTable[$charsetTo][$hexChar])
				{
					if($charsetTo != "utf-8")
						$resultString .= chr(hexdec($arConvertTable[$charsetTo][$hexChar]));
					else
						$resultString .= $this->HexToUtf($arConvertTable[$charsetTo][$hexChar]);
				}
			}
		}
		elseif($charsetFrom == "utf-16")
		{
			$arConvertTable = $this->BuildConvertTable($charsetTo);
			for($i = 0, $n = strlen($sourceString); $i < $n; $i+=2)
			{
				$hexChar = sprintf("%02X%02X", ord($sourceString[$i+1]), ord($sourceString[$i]));
				if($arConvertTable[$charsetTo][$hexChar])
				{
					if($charsetTo != "utf-8")
						$resultString .= chr(hexdec($arConvertTable[$charsetTo][$hexChar]));
					else
						$resultString .= $this->HexToUtf($arConvertTable[$charsetTo][$hexChar]);
				}
			}
		}
		elseif($charsetFrom != "utf-8")
		{
			if($charsetTo != "utf-8")
				$arConvertTable = $this->BuildConvertTable($charsetFrom, $charsetTo);
			else
				$arConvertTable = $this->BuildConvertTable($charsetFrom);

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
						foreach (explode("+", $unicodeHexChar) as $char)
						{
							if (array_key_exists($char, $arConvertTable[$charsetTo]))
								$resultString .= chr(hexdec($arConvertTable[$charsetTo][$char]));
							else
								$this->AddError(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in destination encoding table."));
						}
					}
					else
						$this->AddError(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in source encoding table."));
				}
				else
				{
					if(in_array("$hexChar", $arConvertTable[$charsetFrom]))
					{
						$unicodeHexChar = array_search($hexChar, $arConvertTable[$charsetFrom]);
						foreach (explode("+", $unicodeHexChar) as $char)
							$resultString .= $this->HexToUtf($char);
					}
					else
						$this->AddError(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in source encoding table."));
				}
			}
		}
		else
		{
			$arConvertTable = $this->BuildConvertTable($charsetTo);
			if(!$arConvertTable)
				return false;

			foreach($arConvertTable[$charsetTo] as $unicodeHexChar => $hexChar)
			{
				$EntitieOrChar = chr(hexdec($hexChar));
				$sourceString = str_replace($this->HexToUtf($unicodeHexChar), $EntitieOrChar, $sourceString);
			}
			$resultString = $sourceString;
		}

		return $resultString;
	}

	public function GetErrors()
	{
		return $this->arErrors;
	}

	protected function AddError($error, $errorCode = "")
	{
		if (empty($error) || $this->ignoreErrors)
			return;

		$fs = (empty($errorCode) ? "%s" : "[%s] %s");
		$this->arErrors[] = sprintf($fs, $error, $errorCode);
	}

	protected function ClearErrors()
	{
		$this->arErrors = array();
	}
}
