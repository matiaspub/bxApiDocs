<?
class CSearchUser
{
	protected $_user_id;

	public function __construct($user_id)
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
		$user_id = is_object($USER)? intval($USER->GetID()): 0;

		if($user_id > 0)
		{
			$arGroupCodes = array('AU', 'U'.$user_id); // Authorized
			foreach($USER->GetUserGroupArray() as $group_id)
			{
				$arGroupCodes[] = 'G'.$group_id;
			}

			foreach (GetModuleEvents("search", "OnSearchCheckPermissions", true) as $arEvent)
			{
				$arCodes = ExecuteModuleEventEx($arEvent, array(null));
				if(is_array($arCodes))
				{
					$arGroupCodes = array_merge($arGroupCodes, $arCodes);
				}
			}

			$ob = new CSearchUser($user_id);
			$ob->SetGroups($arGroupCodes);
		}
	}

	public function IsGroupsExists()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$rs = $DB->Query($DB->TopSql("
			SELECT * FROM b_search_user_right
			WHERE USER_ID = ".$this->_user_id."
		", 1), false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return is_array($rs->Fetch());
	}

	public function DeleteGroups()
	{
		$DB = CDatabase::GetModuleConnection('search');
		$DB->Query("
			DELETE FROM b_search_user_right
			WHERE USER_ID = ".$this->_user_id."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public function AddGroups($arGroups)
	{
		$DB = CDatabase::GetModuleConnection('search');

		$arToInsert = array();
		foreach($arGroups as $group_code)
		{
			if($group_code != "")
				$arToInsert[$group_code] = $group_code;
		}

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

	public function SetGroups($arGroups)
	{
		$DB = CDatabase::GetModuleConnection('search');
		$dbCodes = $DB->Query("
			SELECT GROUP_CODE
			FROM b_search_user_right
			WHERE USER_ID = ".$this->_user_id."
		", false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arGroupsToCheck = array_flip($arGroups);
		while ($dbCode = $dbCodes->Fetch())
		{
			if (!array_key_exists($dbCode["GROUP_CODE"], $arGroupsToCheck))
			{
				$DB->Query("
					DELETE FROM b_search_user_right
					WHERE USER_ID = ".$this->_user_id."
					AND GROUP_CODE = '".$DB->ForSQL($dbCode["GROUP_CODE"])."'
				", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				unset($arGroups[$arGroupsToCheck[$dbCode["GROUP_CODE"]]]);
			}
		}
		$this->AddGroups($arGroups);
	}
}
?>