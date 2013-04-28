<?
class CSearchUser
{
	var $_user_id;

	function __construct($user_id)
	{
		$this->_user_id = intval($user_id);
	}

	public static function OnAfterUserUpdate(&$arFields)
	{
		if(array_key_exists("GROUP_ID", $arFields))
		{
			$ob = new CSearchUser($arFields["ID"]);
			$ob->DeleteGroups();
		}
	}

	public static function DeleteByUserID($USER_ID)
	{
		$ob = new CSearchUser($USER_ID);
		$ob->DeleteGroups();
	}

	public static function CheckCurrentUserGroups()
	{
		global $USER;
		$user_id = intval($USER->GetID());

		if($user_id > 0)
		{
			$ob = new CSearchUser($user_id);

			if(!$ob->IsGroupsExists())
			{
				$arGroupCodes = array('AU', 'U'.$user_id); // Authorized

				foreach($USER->GetUserGroupArray() as $group_id)
					$arGroupCodes[] = 'G'.$group_id;

				$events = GetModuleEvents("search", "OnSearchCheckPermissions");
				while($arEvent = $events->Fetch())
				{
					$arCodes = ExecuteModuleEventEx($arEvent, array($FIELD));
					if(is_array($arCodes))
						$arGroupCodes = array_merge($arGroupCodes, $arCodes);
				}

				$ob->AddGroups($arGroupCodes);
			}
		}
	}

	public static function IsGroupsExists()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$rs = $DB->Query($DB->TopSql("
			SELECT * FROM b_search_user_right
			WHERE USER_ID = ".$this->_user_id."
		", 1), false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return is_array($rs->Fetch());
	}

	public static function DeleteGroups()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$DB->Query("
			DELETE FROM b_search_user_right
			WHERE USER_ID = ".$this->_user_id."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function AddGroups($arGroups)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$arToInsert = array();
		foreach($arGroups as $group_code)
			if(strlen($group_code))
				$arToInsert[$group_code] = $group_code;

		foreach($arToInsert as $group_code)
		{
			$DB->Query("
				INSERT INTO b_search_user_right
				(USER_ID, GROUP_CODE)
				VALUES
				(".$this->_user_id.", '".$DB->ForSQL($group_code, 100)."')
			", true, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}
}
?>