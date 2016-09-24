<?
class CCloudUtil
{
	/**
	 * @param string $str
	 * @param string $charset
	 * @return string
	*/
	public static function URLEncode($str, $charset)
	{
		global $APPLICATION;
		$strEncodedURL = '';
		$arUrlComponents = preg_split("#(://|/|\\?|=|&)#", $str, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($arUrlComponents as $i => $part_of_url)
		{
			if((intval($i) % 2) == 1)
				$strEncodedURL .= (string)$part_of_url;
			else
				$strEncodedURL .= urlencode($APPLICATION->ConvertCharset(urldecode($part_of_url), LANG_CHARSET, $charset));
		}
		return $strEncodedURL;
	}
}
?>