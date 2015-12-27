<?php

namespace Bitrix\Sale\TradingPlatform;

/**
 * Class Xml2Array
 * @package Bitrix\Sale\TradingPlatform
 */
class Xml2Array
{
	/**
	 * @param string $xmlData XML.
	 * @return array Converted.
	 */
	public static function convert($xmlData, $convertCharset = true)
	{
		if(strlen($xmlData) <= 0)
			return array();

		$result = array();


		if($convertCharset && strtolower(SITE_CHARSET) != 'utf-8')
			$xmlData = \Bitrix\Main\Text\Encoding::convertEncodingArray($xmlData, SITE_CHARSET, 'UTF-8');

		//	$xmlData = preg_replace('/[[:^print:]]/', '', $xmlData);
		$results = new \SimpleXMLElement($xmlData, LIBXML_NOCDATA);

		if($results && $jsonString = json_encode($results))
			$result = json_decode($jsonString, TRUE);

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$result = \Bitrix\Main\Text\Encoding::convertEncodingArray($result, 'UTF-8', SITE_CHARSET);

		return $result;
	}

	/**
	 * @param array $branch.
	 * @return array
	 */
	public static function normalize(array $branch)
	{
		reset($branch);

		if(key($branch) !== 0)
			$branch = array( 0 => $branch);

		return $branch;
	}
} 