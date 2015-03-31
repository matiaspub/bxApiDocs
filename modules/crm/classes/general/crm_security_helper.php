<?php

class CCrmSecurityHelper
{
	public static function GetCurrentUserID()
	{
		//CUser::GetID may return null
		return intval(self::GetCurrentUser()->GetID());
	}

	/** @return CUser */
	public static function GetCurrentUser()
	{
		return isset($USER) && ((get_class($USER) === 'CUser') || ($USER instanceof CUser))
			? $USER : new CUser();
	}

	public static function IsAuthorized()
	{
		return self::GetCurrentUser()->IsAuthorized();
	}
}
