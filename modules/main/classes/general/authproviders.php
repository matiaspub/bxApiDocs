<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CAuthProvider
{
	protected $id;

	public function DeleteByUser($USER_ID)
	{
		global $DB;
		$USER_ID = intval($USER_ID);

		$DB->Query("delete from b_user_access where user_id=".$USER_ID." and provider_id='".$DB->ForSQL($this->id)."'");

		CAccess::ClearStat($this->id, $USER_ID);
	}

	public function DeleteAll()
	{
		global $DB;

		$DB->Query("delete from b_user_access where provider_id='".$DB->ForSQL($this->id)."' AND user_id > 0");

		CAccess::ClearStat($this->id);
	}
}

interface IProviderInterface
{
	static public function GetFormHtml($arParams=false);
	static public function GetNames($arCodes);
}

class CGroupAuthProvider extends CAuthProvider implements IProviderInterface
{
	const ID = 'group';
	
	public function __construct()
	{
		$this->id = self::ID;
	}
	
	public function UpdateCodes($USER_ID)
	{
		global $DB;
		$USER_ID = intval($USER_ID);
		
		$DB->Query("
			INSERT INTO b_user_access (USER_ID, PROVIDER_ID, ACCESS_CODE)
			SELECT UG.USER_ID, '".$DB->ForSQL($this->id)."', ".$DB->Concat("'G'", ($DB->type == "MSSQL" ? "CAST(UG.GROUP_ID as varchar(17))": "UG.GROUP_ID"))."
			FROM b_user_group UG, b_group G 
			WHERE UG.USER_ID=".$USER_ID."
				AND G.ID=UG.GROUP_ID
				AND G.ACTIVE='Y'
				AND ((UG.DATE_ACTIVE_FROM IS NULL) OR (UG.DATE_ACTIVE_FROM <= ".$DB->CurrentTimeFunction().")) 
				AND ((UG.DATE_ACTIVE_TO IS NULL) OR (UG.DATE_ACTIVE_TO >= ".$DB->CurrentTimeFunction().")) 
			UNION 
			SELECT ID, '".$DB->ForSQL($this->id)."', 'G2' 
			FROM b_user
			WHERE ID=".$USER_ID."
		");
	}
	
	public static function OnBeforeGroupUpdate($ID, &$arFields)
	{
		if(array_key_exists("ACTIVE", $arFields) || array_key_exists("USER_ID", $arFields))
			self::DeleteByGroup($ID);
		return true;
	}

	public static function OnAfterGroupAdd(&$arFields)
	{
		if(count($arFields["USER_ID"]) > 0)
			self::DeleteByGroup($arFields["ID"]);
	}
	
	public static function OnBeforeGroupDelete($ID)
	{
		self::DeleteByGroup($ID);
		return true;
	}
	
	public static function OnAfterSetUserGroup($USER_ID)
	{
		$provider = new CGroupAuthProvider();
		$provider->DeleteByUser($USER_ID);
	}

	public static function OnUserLogin($USER_ID)
	{
		global $USER;
		
		$arGroups = $USER->GetUserGroupArray();
		
		$arCodes = array();
		$res = CAccess::GetUserCodes($USER_ID, array("PROVIDER_ID"=>self::ID));
		while($arCode = $res->Fetch())
			$arCodes[] = substr($arCode["ACCESS_CODE"], 1);
		
		sort($arCodes);
		
		if($arCodes <> $arGroups)
		{
			$provider = new CGroupAuthProvider();
			$provider->DeleteByUser($USER_ID);
		}
	}

	protected static function DeleteByGroup($ID)
	{
		global $DB;

		$DB->Query("delete from b_user_access where user_id in (select user_id from b_user_group where group_id=".intval($ID).") and provider_id='".self::ID."'");

		CAccess::ClearStat(self::ID);
	}

	public function AjaxRequest()
	{
		global $USER;
		if(!$USER->CanDoOperation('view_groups'))
			return false;
			
		$elements = "";
		$arFinderParams = array(
			"PROVIDER" => $this->id,
			"TYPE" => 1,
		);
		
		$search = urldecode($_REQUEST['search']);
		
		$dbRes = CGroup::GetList(($by="sort"), ($order=""), array("ANONYMOUS"=>"N", "NAME"=>$search));
		$dbRes->NavStart(13);
		while ($arGroup = $dbRes->NavNext(false))
		{
			$arItem = array(
				"ID" => "G".$arGroup["ID"],
				"NAME" => $arGroup["NAME"],
			);
			
			$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}
		return $elements;
	}

	public function GetFormHtml($arParams=false)
	{
		global $USER;

		if(is_array($arParams["groups"]) && $arParams["groups"]["disabled"] == "true")
			return false;

		if(!$USER->CanDoOperation('view_groups'))
			return false;
			
		$elements = $last = "";
		$arFinderParams = array(
			"PROVIDER" => $this->id,
			"TYPE" => 1,
		);
		
		$arLRU = CAccess::GetLastRecentlyUsed($this->id);
		
		$res = CGroup::GetList(($by="sort"), ($order=""), array("ANONYMOUS"=>"N"));
		while($arGroup = $res->Fetch())
		{
			$arItem = array(
				"ID" => "G".$arGroup["ID"],
				"NAME" => $arGroup["NAME"],
			);
			
			$element = CFinder::GetFinderItem($arFinderParams, $arItem);
			$elements .= $element;

			if(in_array($arItem["ID"], $arLRU))
				$last .= $element;
		}

		$arPanels = array(
			array(
				"NAME" => GetMessage("authprov_last"),
				"ELEMENTS" => $last,
			),
			array(
				"NAME" => GetMessage("authprov_all_groups"),
				"ELEMENTS" => $elements,
			),
			array(
				"NAME" => GetMessage("authprov_search"),
				"ELEMENTS" => CFinder::GetFinderItem(array("TYPE" => "text"), array("TEXT" => GetMessage("authprov_group_name"))),
				"SEARCH" => "Y",
			),
		);
		$html = CFinder::GetFinderAppearance($arFinderParams, $arPanels);
		
		return array("HTML"=>$html);
	}

	static public function GetNames($arCodes)
	{
		$aID = array();
		foreach($arCodes as $code)
			if(preg_match('/^G[0-9]+$/', $code))
				$aID[] = substr($code, 1);
		
		if(!empty($aID))
		{
			$arResult = array();
			$res = CGroup::GetList(($by="id"), ($order=""), array("ANONYMOUS"=>"N", "ID"=>implode("|", $aID)));
			while($arGroup = $res->Fetch())
			{
				$arResult["G".$arGroup["ID"]] = array("provider" => GetMessage("authprov_group_prov"), "name"=>$arGroup["NAME"]);
			}
				
			return $arResult;
		}
		return false;
	}
}

class CUserAuthProvider extends CAuthProvider implements IProviderInterface
{
	public function __construct()
	{
		$this->id = 'user';
	}

	public function UpdateCodes($USER_ID)
	{
		global $DB;
		$USER_ID = intval($USER_ID);

		$DB->Query("
			insert into b_user_access (user_id, provider_id, access_code)
			select ID, '".$DB->ForSQL($this->id)."', 'U".$USER_ID."'
			from b_user
			where id=".$USER_ID."
		");
	}
	
	public function AjaxRequest()
	{
		global $USER;
		if(!$USER->CanDoOperation('view_all_users'))
			return false;
			
		$search = urldecode($_REQUEST['search']);
		$elements = "";
		$arFinderParams = array(
			"PROVIDER" => $this->id,
			"TYPE" => 2,
		);

		$nameFormat = CSite::GetNameFormat(false);

		$arFilter = array('ACTIVE' => 'Y', 'NAME_SEARCH' => $search);

		if (
			IsModuleInstalled('intranet')
			|| COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y"
		)
		{
			$arFilter['CONFIRM_CODE'] = false;
		}

		//be careful with field list because of CUser::FormatName()
		$dbRes = CUser::GetList(($by = 'last_name'), ($order = 'asc'),
			$arFilter,
			array("FIELDS" => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL')),
			array('NAV_PARAMS' => array('nTopCount' => 20))
		);
		while ($arUser = $dbRes->NavNext(false))
		{
			$arItem = array(
				"ID" => "U".$arUser["ID"],
				"NAME" => CUser::FormatName($nameFormat, $arUser, true, false),
			);
			$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}
		return $elements;
	}
	
	public function GetFormHtml($arParams=false)
	{
		global $USER;

		if(is_array($arParams["user"]) && $arParams["user"]["disabled"] == "true")
			return false;

		if(!$USER->CanDoOperation('view_all_users'))
			return false;

		$elements = "";
		$arFinderParams = array(
			"PROVIDER" => $this->id,
			"TYPE" => 2,
		);
		
		$arLRU = CAccess::GetLastRecentlyUsed($this->id);
		if(!empty($arLRU))
		{
			foreach($arLRU as $i=>$val)
				$arLRU[$i] = substr($val, 1);

			$nameFormat = CSite::GetNameFormat(false);

			//be careful with field list because of CUser::FormatName()
			$res = CUser::GetList(($by="LAST_NAME"), ($order="asc"),
				array("ID" => implode("|", $arLRU)),
				array("FIELDS" => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL'))
			);
			while($arUser = $res->Fetch())
			{
				$arItem = array(
					"ID" => "U".$arUser["ID"],
					"NAME" => CUser::FormatName($nameFormat, $arUser, true, false),
				);
				$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
			}
		}
		
		$arPanels = array(
			array(
				"NAME" => GetMessage("authprov_last"),
				"ELEMENTS" => $elements,
			),
			array(
				"NAME" => GetMessage("authprov_search"),
				"ELEMENTS" => CFinder::GetFinderItem(array("TYPE" => "text"), array("TEXT" => GetMessage("authprov_user"))),
				"SEARCH" => "Y",
			),
		);
		$html = CFinder::GetFinderAppearance($arFinderParams, $arPanels);

		return array("HTML"=>$html);
	}

	static public function GetNames($arCodes)
	{
		$aID = array();
		foreach($arCodes as $code)
		{
			if(!isset($aID[$code]) && preg_match('/^U[0-9]+$/', $code))
			{
				$aID[$code] = substr($code, 1);
			}
		}

		if(!empty($aID))
		{
			$nameFormat = CSite::GetNameFormat(false);

			$arResult = array();
			//be careful with field list because of CUser::FormatName()
			$res = CUser::GetList(($by="id"), ($order=""),
				array("ID" => implode("|", $aID)),
				array("FIELDS" => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'EMAIL'))
			);
			while($arUser = $res->Fetch())
			{
				$arResult["U".$arUser["ID"]] = array(
					"provider" => GetMessage("authprov_user1"),
					"name" => CUser::FormatName($nameFormat, $arUser, true, false),
				);
			}

			return $arResult;
		}
		return false;
	}
}

class COtherAuthProvider implements IProviderInterface
{
	static public function GetFormHtml($arParams=false)
	{
		global $USER;

		if(is_array($arParams["other"]) && $arParams["other"]["disabled"] == "true")
			return false;
		
		$elements = "";
		$arFinderParams = array(
			"PROVIDER" => "other",
			"TYPE" => 3,
		);

		$arItem = array(
			"ID" => "U".$USER->GetID(),
			"AVATAR" => "/bitrix/js/main/core/images/access/avatar-user-auth.png",
			"NAME" => (($s = trim($USER->GetFormattedName(false, false))) <> ''? $s : $USER->GetLogin()),
			"DESC" => GetMessage("authprov_user_curr"),
		);
		$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);

		if(!is_array($arParams["other"]) || $arParams["other"]["disabled_cr"] != "true")
		{
			$arItem = array(
				"ID" => "CR",
				"AVATAR" => "/bitrix/js/main/core/images/access/avatar-user-author.png",
				"NAME" => GetMessage("authprov_author"),
				"DESC" => GetMessage("authprov_author_desc"),
			);
			$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}
		
		if(!is_array($arParams["other"]) || $arParams["other"]["disabled_g2"] != "true")
		{
			$arItem = array(
				"ID" => "G2",
				"AVATAR" => "/bitrix/js/main/core/images/access/avatar-user-everyone.png",
				"NAME" => GetMessage("authprov_all"),
				"DESC" => GetMessage("authprov_all_desc"),
			);
			$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}
		
		if(!is_array($arParams["other"]) || $arParams["other"]["disabled_au"] != "true")
		{
			$arItem = array(
				"ID" => "AU",
				"AVATAR" => "/bitrix/js/main/core/images/access/avatar-user-auth.png",
				"NAME" => GetMessage("authprov_authorized"),
				"DESC" => GetMessage("authprov_authorized_desc"),
			);
			$elements .= CFinder::GetFinderItem($arFinderParams, $arItem);
		}
		
		$arPanels = array(
			array(
				"NAME" => GetMessage("authprov_other"),
				"ELEMENTS" => $elements,
				"SELECTED" => "Y",
			),
		);
		$html = CFinder::GetFinderAppearance($arFinderParams, $arPanels);
		
		return array("HTML"=>$html);
	}

	static public function GetNames($arCodes)
	{
		return array(
			"CR" => array("provider"=>"", "name"=>GetMessage("authprov_author")),
			"G2" => array("provider"=>"", "name"=>GetMessage("authprov_all")),
			"AU" => array("provider"=>"", "name"=>GetMessage("authprov_authorized")),
		);
	}
}
