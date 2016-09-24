<?php
class CCrmDateTimeHelper
{
	public static function NormalizeDateTime($str)
	{
		// Add seconds if omitted
		if(strpos(CSite::GetTimeFormat(), 'SS') !== false
			&& preg_match('/\d{1,2}\s*:\s*\d{1,2}\s*:\s*\d{1,2}/', $str) !== 1)
		{
			$str = preg_replace('/\d{1,2}\s*:\s*\d{1,2}/', '$0:00', $str);
		}

		return $str;
	}
	public static function GetMaxDatabaseDate()
	{
		global $DBType;
		$dbType = strtoupper($DBType);
		if($dbType === 'MYSQL')
		{
			return "'9999-12-31 23:59:59'";
		}
		elseif($dbType === 'MSSQL')
		{
			return "CONVERT(DATETIME, '9999-12-31 23:59:59', 121)";
		}
		elseif($dbType === 'ORACLE')
		{
			return "TO_DATE('9999-12-31 23:59:59', 'YYYY-MM-DD HH24:MI:SS')";
		}
		return "'9999-12-31 23:59:59'";
	}
	public static function IsMaxDatabaseDate($datetime, $format = false)
	{
		$parts = ParseDateTime($datetime, is_string($format) && $format !== '' ? $format : FORMAT_DATETIME);
		if(!is_array($parts))
		{
			return false;
		}

		$year = isset($parts['YYYY']) ? intval($parts['YYYY']) : 0;
		return $year === 9999;
	}
}