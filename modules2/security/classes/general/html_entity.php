<?php

class CSecurityHtmlEntity
{
	private static $htmlMnemonics = array(
		"html" => array(
				"/&colon;/i", "/&tab;/i", "/&newline;/i"
			),
		"text" => array(
				":",        "\r",   "\n"
			),
		);

	/*
	Function is used in regular expressions in order to decode characters presented as &#123;
	*/
	protected static function decodeCb($in)
	{
		$ad = $in[2];
		if($ad == ';')
			$ad="";
		$num = intval($in[1]);
		return chr($num).$ad;
	}

	/*
	Function is used in regular expressions in order to decode characters presented as  &#xAB;
	*/
	protected static function decodeCbHex($in)
	{
		$ad = $in[2];
		if($ad==';')
			$ad="";
		$num = intval(hexdec($in[1]));
		return chr($num).$ad;
	}

	/*
	Decodes string from html codes &#***;
	One pass!
	-- Decode only a-zA-Z:().=, because only theese are used in filters
	*/
	protected static function decode($str)
	{
		$str = preg_replace_callback("/\&\#(\d+)([^\d])/is", array(__CLASS__, "decodeCb"), $str);
		$str = preg_replace_callback("/\&\#x([\da-f]+)([^\da-f])/is", array(__CLASS__, "decodeCbHex"), $str);
		$str = preg_replace(self::$htmlMnemonics["html"], self::$htmlMnemonics["text"],$str);
		return $str;
	}

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