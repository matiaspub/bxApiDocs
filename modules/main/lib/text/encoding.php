<?php
namespace Bitrix\Main\Text;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Configuration;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;

class Encoding
{
	const PATH_TO_CONVERT_TABLES = "/bitrix/modules/main/cvtables/";

	/** @var ErrorCollection */
	protected $errors;

	protected function __construct()
	{
		$this->errors = new ErrorCollection();
	}

	/**
	 * Converts data from a source encoding to a target encoding.
	 *
	 * @param string|array|\SplFixedArray $data The data to convert. From main 16.0.10 data can be an array.
	 * @param string $charsetFrom The source encoding.
	 * @param string $charsetTo The target encoding.
	 * @param string $errorMessage Reference to a variable containing error messages.
	 * @return string|array|\SplFixedArray|bool Returns converted data or false on error.
	 */
	
	/**
	* <p>Статический метод конвертирует данные из кодировки источника в целевую кодировку. Возвращает сконвертированные данные или <i>false</i> в случае ошибки.</p>
	*
	*
	* @param mixed $string  Данные для конвертации. С версии 16.0.10 данные могут быть массивом.
	*
	* @param strin $array  Кодировка источника
	*
	* @param SplFixedArray $data  Целевая кодировка
	*
	* @param string $charsetFrom  Ссылка на переменную, содержащую сообщения об ошибках.
	*
	* @param string $charsetTo  
	*
	* @param string $errorMessage = "" 
	*
	* @return string|array|\SplFixedArray|boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/text/encoding/convertencoding.php
	* @author Bitrix
	*/
	public static function convertEncoding($data, $charsetFrom, $charsetTo, &$errorMessage = "")
	{
		if(strcasecmp($charsetFrom, $charsetTo) == 0)
		{
			//no need to convert
			return $data;
		}

		if(is_array($data) || $data instanceof \SplFixedArray)
		{
			//let's do a recursion
			foreach($data as $key => $value)
			{
				$newKey = self::convertEncoding($key, $charsetFrom, $charsetTo, $errorMessage);
				$newValue = self::convertEncoding($value, $charsetFrom, $charsetTo, $errorMessage);

				$data[$newKey] = $newValue;

				if($newKey != $key)
				{
					unset($data[$key]);
				}
			}
			return $data;
		}
		elseif(is_string($data))
		{
			if($data == '')
			{
				return '';
			}

			$cvt = new static;

			$res = $cvt->convertByMbstring($data, $charsetFrom, $charsetTo);
			if($res === '')
			{
				$res = $cvt->convertByIconv($data, $charsetFrom, $charsetTo);
				if($res === '')
				{
					$res = $cvt->convertByTables($data, $charsetFrom, $charsetTo);
				}
			}

			$errors = $cvt->getErrors();
			if (!empty($errors))
			{
				$errorMessage .= implode("\n", $errors);
			}

			return $res;
		}
		return $data;
	}

	/**
	 * @deprecated Deprecated in main 16.0.10. Use Encoding::convertEncoding().
	 * @param $data
	 * @param $charsetFrom
	 * @param $charsetTo
	 * @param string $errorMessage
	 * @return mixed
	 */
	public static function convertEncodingArray($data, $charsetFrom, $charsetTo, &$errorMessage = "")
	{
		return self::convertEncoding($data, $charsetFrom, $charsetTo, $errorMessage);
	}

	/**
	 * @param string $string
	 * @return bool|string
	 */
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

	/**
	 * @param string $string
	 * @return bool
	 */
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

	protected function convertByMbstring($data, $charsetFrom, $charsetTo)
	{
		$res = '';
		if (extension_loaded("mbstring") && mb_encoding_aliases($charsetFrom) !== false && mb_encoding_aliases($charsetTo) !== false)
		{
			//For UTF-16 we have to detect the order of bytes
			//Default for mbstring extension is Big endian
			//Little endian have to pointed explicitly
			if (strtoupper($charsetFrom) == "UTF-16")
			{
				$ch = substr($data, 0, 1);
				if ($ch == "\xFF" && substr($data, 1, 1) == "\xFE")
				{
					//If Little endian found - cutoff BOF bytes and point mbstring to this fact explicitly
					$res = mb_convert_encoding(substr($data, 2), $charsetTo, "UTF-16LE");
				}
				elseif ($ch == "\xFE" && substr($data, 1, 1) == "\xFF")
				{
					//If it is Big endian, just remove BOF bytes
					$res = mb_convert_encoding(substr($data, 2), $charsetTo, $charsetFrom);
				}
				else
				{
					//Otherwise assime Little endian without BOF
					$res = mb_convert_encoding($data, $charsetTo, "UTF-16LE");
				}
			}
			else
			{
				$res = mb_convert_encoding($data, $charsetTo, $charsetFrom);
			}
		}
		return $res;
	}

	protected function convertByIconv($data, $charsetFrom, $charsetTo)
	{
		$res = '';
		if (Configuration::getValue("disable_iconv") !== true)
		{
			$utfString = false;
			if (strtoupper($charsetFrom) == "UTF-16")
			{
				$ch = substr($data, 0, 1);
				if (($ch != "\xFF") || ($ch != "\xFE"))
				{
					$utfString = "\xFF\xFE".$data;
				}
			}
			if (function_exists('iconv'))
			{
				if ($utfString)
				{
					$res = iconv($charsetFrom, $charsetTo."//IGNORE", $utfString);
				}
				else
				{
					$res = iconv($charsetFrom, $charsetTo."//IGNORE", $data);
				}

				if (!$res)
				{
					$this->errors[] = new Error("Iconv reported failure while converting string to requested character encoding.");
				}
			}
			elseif (function_exists('libiconv'))
			{
				if ($utfString)
				{
					$res = libiconv($charsetFrom, $charsetTo, $utfString);
				}
				else
				{
					$res = libiconv($charsetFrom, $charsetTo, $data);
				}

				if (!$res)
				{
					$this->errors[] = new Error("Libiconv reported failure while converting string to requested character encoding.");
				}
			}
		}
		return $res;
	}

	protected function buildConvertTable()
	{
		static $cvTables = array();

		for($i = 0, $cnt = func_num_args(); $i < $cnt; $i++)
		{
			$fileName = func_get_arg($i);

			if(isset($cvTables[$fileName]))
			{
				continue;
			}

			$pathToTable = Loader::getDocumentRoot().self::PATH_TO_CONVERT_TABLES.$fileName;
			if (!file_exists($pathToTable))
			{
				$this->errors[] = new Error(str_replace("#FILE#", $pathToTable, "File #FILE# is not found."));
				return false;
			}

			if (!is_file($pathToTable))
			{
				$this->errors[] = new Error(str_replace("#FILE#", $pathToTable, "File #FILE# is not a file."));
				return false;
			}

			if (!($hFile = fopen($pathToTable, "r")))
			{
				$this->errors[] = new Error(str_replace("#FILE#", $pathToTable, "Can not open file #FILE# for reading."));
				return false;
			}

			$cvTables[$fileName] = array();

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
							$cvTables[$fileName][$key] = $value;
						}
					}
				}
			}

			fclose($hFile);
		}

		return $cvTables;
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

	/**
	 * @param string $sourceString
	 * @param string $charsetFrom
	 * @param string $charsetTo
	 * @return bool|string
	 */
	protected function convertByTables($sourceString, $charsetFrom, $charsetTo)
	{
		if($charsetFrom == '')
		{
			$this->errors[] = new Error("Source charset is not set.");
			return false;
		}

		if($charsetTo == '')
		{
			$this->errors[] = new Error("Destination charset is not set.");
			return false;
		}

		$charsetFrom = strtolower($charsetFrom);
		$charsetTo = strtolower($charsetTo);

		$resultString = "";
		if($charsetFrom == "ucs-2")
		{
			$convertTable = $this->buildConvertTable($charsetTo);
			if(!$convertTable)
			{
				return false;
			}
			$len = strlen($sourceString);
			for($i = 0; $i < $len; $i+=2)
			{
				$hexChar = strtoupper(dechex(ord($sourceString[$i])).dechex(ord($sourceString[$i+1])));
				$hexChar = str_pad($hexChar, 4, "0", STR_PAD_LEFT);
				if($convertTable[$charsetTo][$hexChar])
				{
					if($charsetTo != "utf-8")
					{
						$resultString .= chr(hexdec($convertTable[$charsetTo][$hexChar]));
					}
					else
					{
						$resultString .= $this->hexToUtf($convertTable[$charsetTo][$hexChar]);
					}
				}
			}
		}
		elseif($charsetFrom == "utf-16")
		{
			$convertTable = $this->buildConvertTable($charsetTo);
			if(!$convertTable)
			{
				return false;
			}

			$len = strlen($sourceString);
			for($i = 0; $i < $len; $i+=2)
			{
				$hexChar = sprintf("%02X%02X", ord($sourceString[$i+1]), ord($sourceString[$i]));
				if($convertTable[$charsetTo][$hexChar])
				{
					if($charsetTo != "utf-8")
					{
						$resultString .= chr(hexdec($convertTable[$charsetTo][$hexChar]));
					}
					else
					{
						$resultString .= $this->hexToUtf($convertTable[$charsetTo][$hexChar]);
					}
				}
			}
		}
		elseif($charsetFrom != "utf-8")
		{
			if($charsetTo != "utf-8")
			{
				$convertTable = $this->buildConvertTable($charsetFrom, $charsetTo);
			}
			else
			{
				$convertTable = $this->buildConvertTable($charsetFrom);
			}

			if(!$convertTable)
			{
				return false;
			}

			$stringLength = BinaryString::getLength($sourceString);

			for ($i = 0; $i < $stringLength; $i++)
			{
				$hexChar = strtoupper(dechex(ord($sourceString[$i])));

				if(strlen($hexChar) == 1)
				{
					$hexChar = "0".$hexChar;
				}

				if(($charsetFrom == "gsm0338") && ($hexChar == '1B'))
				{
					$i++;
					$hexChar .= strtoupper(dechex(ord($sourceString[$i])));
				}

				if($charsetTo != "utf-8")
				{
					if(in_array($hexChar, $convertTable[$charsetFrom]))
					{
						$unicodeHexChar = array_search($hexChar, $convertTable[$charsetFrom]);
						$arUnicodeHexChar = explode("+", $unicodeHexChar);
						$len = count($arUnicodeHexChar);
						for ($j = 0; $j < $len; $j++)
						{
							if (array_key_exists($arUnicodeHexChar[$j], $convertTable[$charsetTo]))
							{
								$resultString .= chr(hexdec($convertTable[$charsetTo][$arUnicodeHexChar[$j]]));
							}
							else
							{
								$this->errors[] = new Error(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in destination encoding table."));
							}
						}
					}
					else
					{
						$this->errors[] =  new Error(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in source encoding table."));
					}
				}
				else
				{
					if(in_array($hexChar, $convertTable[$charsetFrom]))
					{
						$unicodeHexChar = array_search($hexChar, $convertTable[$charsetFrom]);
						$arUnicodeHexChar = explode("+", $unicodeHexChar);
						$len = count($arUnicodeHexChar);
						for ($j = 0; $j < $len; $j++)
						{
							$resultString .= $this->hexToUtf($arUnicodeHexChar[$j]);
						}
					}
					else
					{
						$this->errors[] = new Error(str_replace("#CHAR#", $sourceString[$i], "Cannot find matching char \"#CHAR#\" in source encoding table."));
					}
				}
			}
		}
		else
		{
			$convertTable = $this->buildConvertTable($charsetTo);
			if(!$convertTable)
			{
				return false;
			}

			foreach($convertTable[$charsetTo] as $unicodeHexChar => $hexChar)
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
		return $this->errors->toArray();
	}
}
