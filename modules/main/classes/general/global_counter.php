<?
class CGlobalCounter
{
	const ALL_SITES = '**';

	public static function GetValue($code, $site_id = SITE_ID)
	{
		return CUserCounter::GetValue(CUserCounter::SYSTEM_USER_ID, $code, $site_id);
	}

	public static function GetValues($site_id = SITE_ID, &$arLastDate = array())
	{
		return CUserCounter::GetValues(CUserCounter::SYSTEM_USER_ID, $site_id, $arLastDate);
	}

	public static function GetAllValues()
	{
		return CUserCounter::GetAllValues(CUserCounter::SYSTEM_USER_ID);
	}

	public static function GetLastDate($code, $site_id = SITE_ID)
	{
		return CUserCounter::GetLastDate(CUserCounter::SYSTEM_USER_ID, $code, $site_id);
	}

	public static function Set($code, $value, $site_id = SITE_ID, $tag = '', $sendPull = true)
	{
		return CUserCounter::Set(CUserCounter::SYSTEM_USER_ID, $code, $value, $site_id, $tag, $sendPull);
	}

	public static function Increment($code, $site_id = SITE_ID, $sendPull = true, $increment = 1)
	{
		return CUserCounter::Increment(CUserCounter::SYSTEM_USER_ID, $code, $site_id, $sendPull, $increment);
	}

	public static function Decrement($code, $site_id = SITE_ID, $sendPull = true, $decrement = 1)
	{
		return CUserCounter::Decrement(CUserCounter::SYSTEM_USER_ID, $code, $site_id, $sendPull, $decrement);
	}

	public static function IncrementWithSelect($sub_select, $sendPull = true, $arParams = array())
	{
		CUserCounter::IncrementWithSelect($sub_select, $sendPull, $arParams);
	}

	public static function Clear($code, $site_id = SITE_ID, $sendPull = true)
	{
		return CUserCounter::Clear(CUserCounter::SYSTEM_USER_ID, $code, $site_id, $sendPull);
	}

	public static function ClearAll($site_id = SITE_ID, $sendPull = true)
	{
		return CUserCounter::ClearAll(CUserCounter::SYSTEM_USER_ID, $site_id, $sendPull);
	}

	public static function ClearByTag($tag, $code, $site_id = SITE_ID, $sendPull = true)
	{
		return CUserCounter::ClearByTag($tag, $code, $site_id, $sendPull);
	}
}
?>