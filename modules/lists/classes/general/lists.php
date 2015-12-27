<?
class CLists
{
	public static function SetPermission($iblock_type_id, $arGroups)
	{
		global $DB, $CACHE_MANAGER;

		$grp = array();
		foreach($arGroups as $group_id)
		{
			$group_id = intval($group_id);
			if($group_id)
				$grp[$group_id] = $group_id;
		}

		$DB->Query("
			delete from b_lists_permission
			where IBLOCK_TYPE_ID = '".$DB->ForSQL($iblock_type_id)."'
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		if(count($grp))
		{
			$DB->Query("
				insert into b_lists_permission
				select ibt.ID, ug.ID
				from
					b_iblock_type ibt
					,b_group ug
				where
					ibt.ID =  '".$DB->ForSQL($iblock_type_id)."'
					and ug.ID in (".implode(", ", $grp).")
			", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		if(CACHED_b_lists_permission !== false)
			$CACHE_MANAGER->Clean("b_lists_permission");
	}

	public static function GetPermission($iblock_type_id = false)
	{
		global $DB, $CACHE_MANAGER;

		$arResult = false;
		if(CACHED_b_lists_permission !== false)
		{
			if($CACHE_MANAGER->Read(CACHED_b_lists_permission, "b_lists_permission"))
				$arResult = $CACHE_MANAGER->Get("b_lists_permission");
		}

		if($arResult === false)
		{
			$arResult = array();
			$res = $DB->Query("select IBLOCK_TYPE_ID, GROUP_ID from b_lists_permission", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			while($ar = $res->Fetch())
				$arResult[$ar["IBLOCK_TYPE_ID"]][] = $ar["GROUP_ID"];

			if(CACHED_b_lists_permission !== false)
				$CACHE_MANAGER->Set("b_lists_permission", $arResult);
		}

		if($iblock_type_id === false)
			return $arResult;
		else
			return $arResult[$iblock_type_id];
	}

	public static function GetDefaultSocnetPermission()
	{
		return array(
			"A" => "X", //Group owner
			"E" => "W", //Group moderator
			"K" => "W", //Group member
			"L" => "D", //Authorized users
			"N" => "D", //Everyone
			"T" => "D", //Banned
			"Z" => "D", //Request?
		);
	}

	public static function SetSocnetPermission($iblock_id, $arRoles)
	{
		global $DB, $CACHE_MANAGER;
		$iblock_id = intval($iblock_id);

		$arToDB = CLists::GetDefaultSocnetPermission();
		foreach($arToDB as $role => $permission)
			if(isset($arRoles[$role]))
				$arToDB[$role] = substr($arRoles[$role], 0, 1);
		$arToDB["A"] = "X"; //Group owner always in charge
		$arToDB["T"] = "D"; //Banned
		$arToDB["Z"] = "D"; //and Request never get to list

		$DB->Query("
			delete from b_lists_socnet_group
			where IBLOCK_ID = ".$iblock_id."
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		foreach($arToDB as $role => $permission)
		{
			$DB->Query("
				insert into b_lists_socnet_group
				(IBLOCK_ID, SOCNET_ROLE, PERMISSION)
				values
				(".$iblock_id.", '".$role."', '".$permission."')
			", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		if(CACHED_b_lists_permission !== false)
			$CACHE_MANAGER->Clean("b_lists_perm".$iblock_id);
	}

	public static function GetSocnetPermission($iblock_id)
	{
		global $DB, $CACHE_MANAGER;
		$iblock_id = intval($iblock_id);

		$arCache = array();
		if(!array_key_exists($iblock_id, $arCache))
		{
			$arCache[$iblock_id] = CLists::GetDefaultSocnetPermission();

			if(CACHED_b_lists_permission !== false)
			{
				$cache_id = "b_lists_perm".$iblock_id;

				if($CACHE_MANAGER->Read(CACHED_b_lists_permission, $cache_id))
				{
					$arCache[$iblock_id] = $CACHE_MANAGER->Get($cache_id);
				}
				else
				{
					$res = $DB->Query("
						select SOCNET_ROLE, PERMISSION
						from b_lists_socnet_group
						where IBLOCK_ID=".$iblock_id."
					", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					while($ar = $res->Fetch())
						$arCache[$iblock_id][$ar["SOCNET_ROLE"]] = $ar["PERMISSION"];

					$CACHE_MANAGER->Set($cache_id, $arCache[$iblock_id]);
				}
			}
			else
			{
				$res = $DB->Query("
					select SOCNET_ROLE, PERMISSION
					from b_lists_socnet_group
					where IBLOCK_ID=".$iblock_id."
				", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				while($ar = $res->Fetch())
					$arCache[$iblock_id][$ar["SOCNET_ROLE"]] = $ar["PERMISSION"];
			}

			$arCache[$iblock_id]["A"] = "X"; //Group owner always in charge
			$arCache[$iblock_id]["T"] = "D"; //Banned
			$arCache[$iblock_id]["Z"] = "D"; //and Request never get to list
		}

		return $arCache[$iblock_id];
	}

	public static function GetIBlockPermission($iblock_id, $user_id)
	{
		global $USER;

		//IBlock permissions by default
		$Permission = CIBlock::GetPermission($iblock_id, $user_id);
		if($Permission < "W")
		{
			$arIBlock = CIBlock::GetArrayByID($iblock_id);
			if($arIBlock)
			{
				//Check if iblock is list
				$arListsPerm = CLists::GetPermission($arIBlock["IBLOCK_TYPE_ID"]);
				if(count($arListsPerm))
				{
					//User groups
					if($user_id == $USER->GetID())
						$arUserGroups = $USER->GetUserGroupArray();
					else
						$arUserGroups = $USER->GetUserGroup($user_id);

					//One of lists admins
					if(count(array_intersect($arListsPerm, $arUserGroups)))
						$Permission = "X";
				}
			}
		}

		if(
			$Permission < "W"
			&& $arIBlock["SOCNET_GROUP_ID"]
			&& CModule::IncludeModule('socialnetwork')
		)
		{
			$arSocnetPerm = CLists::GetSocnetPermission($iblock_id);
			$socnet_role = CSocNetUserToGroup::GetUserRole($USER->GetID(), $arIBlock["SOCNET_GROUP_ID"]);
			$Permission = $arSocnetPerm[$socnet_role];
		}
		return $Permission;
	}

	public static function GetIBlockTypes($language_id = false)
	{
		global $DB;
		$res = $DB->Query("
			SELECT IBLOCK_TYPE_ID, NAME
			FROM b_iblock_type_lang
			WHERE
				LID = '".$DB->ForSQL($language_id===false? LANGUAGE_ID: $language_id)."'
				AND EXISTS (
					SELECT *
					FROM b_lists_permission
					WHERE b_lists_permission.IBLOCK_TYPE_ID = b_iblock_type_lang.IBLOCK_TYPE_ID
				)
		", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $res;
	}

	public static function GetIBlocks($iblock_type_id, $check_permissions, $socnet_group_id = false)
	{
		$arOrder = array(
			"SORT" => "ASC",
			"NAME" => "ASC",
		);

		$arFilter = array(
			"ACTIVE" => "Y",
			"SITE_ID" => SITE_ID,
			"TYPE" => $iblock_type_id,
			"CHECK_PERMISSIONS" => ($check_permissions? "Y": "N"), //This cancels iblock permissions for trusted users
		);
		if($socnet_group_id > 0)
			$arFilter["=SOCNET_GROUP_ID"] = $socnet_group_id;

		$arResult = array();
		$rsIBlocks = CIBlock::GetList($arOrder, $arFilter);
		while($ar = $rsIBlocks->Fetch())
		{
			$arResult[$ar["ID"]] = $ar["NAME"];
		}
		return $arResult;
	}

	public static function OnIBlockDelete($iblock_id)
	{
		global $DB, $CACHE_MANAGER;
		$iblock_id = intval($iblock_id);

		$DB->Query("delete from b_lists_url where IBLOCK_ID=".$iblock_id, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$DB->Query("delete from b_lists_socnet_group where IBLOCK_ID=".$iblock_id, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		$CACHE_MANAGER->Clean("b_lists_perm".$iblock_id);

		CListFieldList::DeleteFields($iblock_id);
	}

	public static function OnAfterIBlockDelete($iblock_id)
	{
		if(CModule::includeModule('bizproc'))
			BizProcDocument::deleteDataIblock($iblock_id);
	}

	public static function IsEnabledSocnet()
	{
		$bActive = false;
		foreach (GetModuleEvents("socialnetwork", "OnFillSocNetFeaturesList", true) as $arEvent)
		{
			if(
				$arEvent["TO_MODULE_ID"] == "lists"
				&& $arEvent["TO_CLASS"] == "CListsSocnet"
			)
			{
				$bActive = true;
				break;
			}
		}
		return $bActive;
	}

	public static function EnableSocnet($bActive = false)
	{
		if($bActive)
		{
			if(!CLists::IsEnabledSocnet())
			{
				RegisterModuleDependences("socialnetwork", "OnFillSocNetFeaturesList", "lists", "CListsSocnet", "OnFillSocNetFeaturesList");
				RegisterModuleDependences("socialnetwork", "OnFillSocNetMenu", "lists", "CListsSocnet", "OnFillSocNetMenu");
				RegisterModuleDependences("socialnetwork", "OnParseSocNetComponentPath", "lists", "CListsSocnet", "OnParseSocNetComponentPath");
				RegisterModuleDependences("socialnetwork", "OnInitSocNetComponentVariables", "lists", "CListsSocnet", "OnInitSocNetComponentVariables");
			}
		}
		else
		{
			if(CLists::IsEnabledSocnet())
			{
				UnRegisterModuleDependences("socialnetwork", "OnFillSocNetFeaturesList", "lists", "CListsSocnet", "OnFillSocNetFeaturesList");
				UnRegisterModuleDependences("socialnetwork", "OnFillSocNetMenu", "lists", "CListsSocnet", "OnFillSocNetMenu");
				UnRegisterModuleDependences("socialnetwork", "OnParseSocNetComponentPath", "lists", "CListsSocnet", "OnParseSocNetComponentPath");
				UnRegisterModuleDependences("socialnetwork", "OnInitSocNetComponentVariables", "lists", "CListsSocnet", "OnInitSocNetComponentVariables");
			}
		}
	}

	public static function OnSharepointCreateProperty($arInputFields)
	{
		global $DB;
		$iblock_id = intval($arInputFields["IBLOCK_ID"]);
		if($iblock_id > 0)
		{
			//Check if there is at list one field defined for given iblock
			$rsFields = $DB->Query("
				SELECT * FROM b_lists_field
				WHERE IBLOCK_ID = ".$iblock_id."
				ORDER BY SORT ASC
			", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($rsFields->Fetch())
			{

				$arNewFields = array(
					"SORT" => 500,
					"NAME" => $arInputFields["SP_FIELD"],
				);

				if(substr($arInputFields["FIELD_ID"], 0, 9) == "PROPERTY_")
				{
					$arNewFields["ID"] = substr($arInputFields["FIELD_ID"], 9);
					$arNewFields["TYPE"] = "S";
				}
				else
					$arNewFields["TYPE"] = $arInputFields["FIELD_ID"];

				//Publish property on the list
				$obList = new CList($iblock_id);
				$obList->AddField($arNewFields);
			}
		}
	}

	public static function OnSharepointCheckAccess($iblock_id)
	{
		global $USER;
		$arIBlock = CIBlock::GetArrayByID($iblock_id);
		if($arIBlock)
		{
			//Check if iblock is list
			$arListsPerm = CLists::GetPermission($arIBlock["IBLOCK_TYPE_ID"]);
			if(count($arListsPerm))
			{
				//User groups
				$arUserGroups = $USER->GetUserGroupArray();
				//One of lists admins
				if(count(array_intersect($arListsPerm, $arUserGroups)))
					return true;
				else
					return false;
			}
		}
	}

	public static function setLiveFeed($checked, $iblockId)
	{
		global $DB;
		$iblockId = intval($iblockId);
		$checked = intval($checked);

		$resultQuery = $DB->Query("SELECT LIVE_FEED FROM b_lists_url WHERE IBLOCK_ID = ".$iblockId);
		$resultData = $resultQuery->fetch();

		if($resultData)
		{
			if($resultData["LIVE_FEED"] != $checked)
				$DB->Query("UPDATE b_lists_url SET LIVE_FEED = '".$checked."' WHERE IBLOCK_ID = ".$iblockId);
		}
		else
		{
			$url = '/'.$iblockId.'/element/#section_id#/#element_id#/';
			$DB->Query("INSERT INTO b_lists_url (IBLOCK_ID, URL, LIVE_FEED) values (".$iblockId.", '".$DB->ForSQL($url)."', ".$checked.")");
		}
	}

    public static function getLiveFeed($iblockId)
	{
		global $DB;
		$iblockId = intval($iblockId);

		$resultQuery = $DB->Query("SELECT LIVE_FEED FROM b_lists_url WHERE IBLOCK_ID = ".$iblockId);
		$resultData = $resultQuery->fetch();

		if ($resultData)
			return $resultData["LIVE_FEED"];
		else
			return "";
	}

	public static function getCountProcessesUser($userId, $iblockTypeId)
	{
		$userId = intval($userId);
		return CIBlockElement::getList(
			array(),
			array('CREATED_BY' => $userId, 'IBLOCK_TYPE' => $iblockTypeId),
			true,
			false,
			array('ID')
		);
	}

	public static function generateMnemonicCode($integerCode = 0)
	{
		if(!$integerCode)
			$integerCode = time();

		$code = '';
		for ($i = 1; $integerCode >= 0 && $i < 10; $i++)
		{
			$code = chr(0x41 + ($integerCode % pow(26, $i) / pow(26, $i - 1))) . $code;
			$integerCode -= pow(26, $i);
		}
		return $code;
	}
}
?>