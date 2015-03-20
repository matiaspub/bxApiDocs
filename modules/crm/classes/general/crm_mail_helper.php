<?php
class CCrmMailHelper
{
	public static function ParseEmail($str)
	{
		$str = strval($str);

		$result = array(
			'NAME'=> '',
			'EMAIL'=> '',
			'ORIGINAL'=> $str
		);

		if($str === '')
		{
			return $result;
		}

		$lbrpos = strpos($str, '<');
		$rbrpos = strpos($str, '>');
		if($lbrpos !== false && $rbrpos !== false)
		{
			$result['NAME'] = trim(substr($str, 0, $lbrpos));
			$result['EMAIL'] = strtolower(trim(substr($str, $lbrpos + 1, $rbrpos - $lbrpos - 1)));
		}
		else
		{
			$result['EMAIL'] = strtolower(trim($str));
		}

		return $result;
	}
	public static function ExtractEmail($str)
	{
		if(!(IsModuleInstalled('mail') && CModule::IncludeModule('mail')))
		{
			$result = self::ParseEmail($str);
			return $result['EMAIL'];
		}

		return CMailUtil::ExtractMailAddress($str);
	}
	public static function IsEmail($str)
	{
		return preg_match('/\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', trim(strval($str))) === 1;
	}
}