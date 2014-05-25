<?php

class CSecurityHtmlEntity
{
	private static $htmlMnemonics = array(
		"html" => array(
				"/&colon;/i", "/&tab;/i", "/&newline;/i"
			),
		"text" => array(
				":",           "\r",       "\n"
			),
		);

	/**
	 * Decode characters presented as &#123;
	 *
	 * @param string $entity
	 * @return string
	 */
	protected static function decodeCb($entity)
	{
		$ad = $entity[2];
		if($ad == ';')
			$ad="";
		$num = intval($entity[1]);
		return chr($num).$ad;
	}

	/**
	 * Decode characters presented as  &#xAB;
	 *
	 * @param string $entity
	 * @return string
	 */
	protected static function decodeCbHex($entity)
	{
		$ad = $entity[2];
		if($ad==';')
			$ad="";
		$num = intval(hexdec($entity[1]));
		return chr($num).$ad;
	}

	/**
	 * Decodes string from html codes &#***; but only a-zA-Z:().=, because only these are used in auditors
	 * One pass!
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function decode($string)
	{
		$string = preg_replace_callback("/\&\#(\d+)([^\d])/is", array(__CLASS__, "decodeCb"), $string);
		$string = preg_replace_callback("/\&\#x([\da-f]+)([^\da-f])/is", array(__CLASS__, "decodeCbHex"), $string);
		$string = preg_replace(self::$htmlMnemonics["html"], self::$htmlMnemonics["text"],$string);
		return $string;
	}

	/**
	 * Recursive decodes string from html codes &#***; but only a-zA-Z:().=, because only these are used in auditors
	 *
	 * @param string $pString
	 * @return string
	 */
	public static function decodeString($pString)
	{
		$strY = $pString;
		$str1 = "";
		while($str1 <> $strY)
		{
			$str1 = $strY;
			$strY = self::decode($strY);
			$strY = str_replace("\x00", "", $strY);
			$strY = preg_replace("/\&\#0+(;|([^\d;]))/is", "\\2", $strY);
			$strY = preg_replace("/\&\#x0+(;|([^\da-f;]))/is", "\\2", $strY);
		}

		return $str1;
	}
}