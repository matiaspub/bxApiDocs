<?php
namespace Bitrix\Security\Codec;

use Bitrix\Main\ArgumentTypeException;


class Base32
{

	/**
	 * Table for encoding/decoding base32, RFC 4648/3548
	 *
	 * @var array $alphabet
	 */
	private static $alphabet = array(
		'A' => 0,
		'B' => 1,
		'C' => 2,
		'D' => 3,
		'E' => 4,
		'F' => 5,
		'G' => 6,
		'H' => 7,
		'I' => 8,
		'J' => 9,
		'K' => 10,
		'L' => 11,
		'M' => 12,
		'N' => 13,
		'O' => 14,
		'P' => 15,
		'Q' => 16,
		'R' => 17,
		'S' => 18,
		'T' => 19,
		'U' => 20,
		'V' => 21,
		'W' => 22,
		'X' => 23,
		'Y' => 24,
		'Z' => 25,
		2 => 26,
		3 => 27,
		4 => 28,
		5 => 29,
		6 => 30,
		7 => 31,
		'=' => 32,
	);

	private static $encodeAlphabet = null;

	/**
	 * Encode data to Base32 string
	 *
	 * @param string $string Data to encode.
	 * @return string Base32 encoded data.
	 * @throws ArgumentTypeException
	 */
	public static function encode($string)
	{
		if (!$string)
			return '';

		if (!is_string($string))
			throw new ArgumentTypeException('base32String', 'string');

		if (self::$encodeAlphabet === null)
			self::$encodeAlphabet = array_flip(self::$alphabet);

		// Convert string to binary
		$binaryString = '';

		foreach (str_split($string) as $s)
		{
			// Return each character as an 8-bit binary string
			$s = decbin(ord($s));
			$binaryString .= str_pad($s, 8, 0, STR_PAD_LEFT);
		}

		// Break into 5-bit chunks, then break that into an array
		$binaryArray = self::chunk($binaryString, 5);

		// Pad array to be divisible by 8
		while (count($binaryArray) % 8 !== 0)
		{
			$binaryArray[] = null;
		}

		$base32String = '';

		// Encode in base32
		foreach ($binaryArray as $bin)
		{
			$char = 32;

			if (!is_null($bin))
			{
				// Pad the binary strings
				$bin = str_pad($bin, 5, 0, STR_PAD_RIGHT);
				$char = bindec($bin);
			}

			// Base32 character
			$base32String .= self::$encodeAlphabet[$char];
		}

		return $base32String;
	}

	/**
	 * Decode Base32 encoded string
	 *
	 * @param string $base32String Base32 encoded string.
	 * @throws ArgumentTypeException
	 * @throws DecodingException
	 * @return string Original data.
	 */
	public static function decode($base32String)
	{
		if (!$base32String)
			return '';

		if (!is_string($base32String))
			throw new ArgumentTypeException('base32String', 'string');

		$base32String = strtoupper($base32String);
		$base32Array = str_split($base32String);

		$string = '';

		foreach ($base32Array as $str)
		{
			// skip padding
			if ($str === '=')
				continue;

			if (!isset(self::$alphabet[$str]))
				throw new DecodingException(sprintf('Illegal character: %s', $str));

			$char = self::$alphabet[$str];
			$char = decbin($char);
			$string .= str_pad($char, 5, 0, STR_PAD_LEFT);
		}

		while (\CUtil::binStrlen($string) % 8 !== 0)
		{
			$string = \CUtil::binSubstr($string, 0, -1);
		}

		$binaryArray = self::chunk($string, 8);

		$realString = '';

		foreach ($binaryArray as $bin)
		{
			// Pad each value to 8 bits
			$bin = str_pad($bin, 8, 0, STR_PAD_RIGHT);
			// Convert binary strings to ASCII
			$realString .= chr(bindec($bin));
		}

		return $realString;
	}

	/**
	 * Split binary string to chunks
	 *
	 * @param string $binaryString The string to be chunked.
	 * @param int $bits The chunk length.
	 * @return array
	 */
	private static function chunk($binaryString, $bits)
	{
		$binaryString = chunk_split($binaryString, $bits, ' ');

		if (\CUtil::binSubstr($binaryString, -1)  == ' ')
		{
			$binaryString = \CUtil::binSubstr($binaryString, 0, -1);
		}

		return explode(' ', $binaryString);
	}
}