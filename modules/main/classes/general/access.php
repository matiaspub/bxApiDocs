<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CAccess
{
	protected static $arAuthProviders = false;
	protected static $arChecked = array();
	protected $arParams = false;

	public function __construct($arParams=false)
	{
		$this->arParams = $arParams;

		if(!is_array(self::$arAuthProviders))
		{
			self::$arAuthProviders = array();

			foreach(GetModuleEvents("main", "OnAuthProvidersBuildList", true) as $arEvent)
			{
				$res = ExecuteModuleEventEx($arEvent);
				if(is_array($res))
				{
					if(!is_array($res[0]))
						$res = array($res);
					foreach($res as $provider)
						self::$arAuthProviders[$provider["ID"]] = $provider;
				}
			}
			sortByColumn(self::$arAuthProviders, "SORT");
		}
	}

	public static function Cmp($a, $b)
	{
		if($a["SORT"] == $b["SORT"])
			return 0;
		return ($a["SORT"] < $b["SORT"]? -1 : 1);
	}

	protected static function CheckUserCodes($provider, $USER_ID)
	{
		global $DB, $CACHE_MANAGER;

		$USER_ID = intval($USER_ID);

		if(!isset(self::$arChecked[$provider][$USER_ID]))
		{
			if (
				CACHED_b_user_access_check !== false
				&& $CACHE_MANAGER->Read(CACHED_b_user_access_check, "access_check".$USER_ID, "access_check")
			)
			{
				self::$arChecked = $CACHE_MANAGER->Get("access_check".$USER_ID);
			}
			else
			{
				$res = $DB->Query("
					select *
					from b_user_access_check
					where user_id=".$USER_ID."
				");

				while($arRes = $res->Fetch())
					self::$arChecked[$arRes["PROVIDER_ID"]][$USER_ID] = true;

				if (CACHED_b_user_access_check !== false)
					$CACHE_MANAGER->Set("access_check".$USER_ID, self::$arChecked);
			}

			foreach(self::$arAuthProviders as $provider_id=>$dummy)
				if(!isset(self::$arChecked[$provider_id][$USER_ID]))
					self::$arChecked[$provider_id][$USER_ID] = false;
		}

		if(self::$arChecked[$provider][$USER_ID] === true)
			return true;

		return false;
	}

	static public function UpdateCodes($arParams=false)
	{
		global $USER;

		$USER_ID = 0;
		if(isset($arParams["USER_ID"]))
			$USER_ID = intval($arParams["USER_ID"]);
		elseif(is_object($USER) && $USER->IsAuthorized())
			$USER_ID = intval($USER->GetID());

		if($USER_ID > 0)
		{
			foreach(self::$arAuthProviders as $provider_id=>$provider)
			{
				if(is_callable(array($provider["CLASS"], "UpdateCodes")))
				{
					//are there access codes for the user already?
					if(!self::CheckUserCodes($provider_id, $USER_ID))
					{
						/** @var CGroupAuthProvider $pr For example*/
						$pr = new $provider["CLASS"];

						//call provider to insert access codes
						$pr->UpdateCodes($USER_ID);

						//update cache for checking
						self::UpdateStat($provider_id, $USER_ID);
					}
				}
			}
		}
	}

	protected static function UpdateStat($provider, $USER_ID)
	{
		global $DB, $CACHE_MANAGER;
		$USER_ID = intval($USER_ID);

		$res = $DB->Query("
			INSERT INTO b_user_access_check (USER_ID, PROVIDER_ID)
			SELECT ID, '".$DB->ForSQL($provider)."'
			FROM b_user
			WHERE ID=".$USER_ID
		);
		$CACHE_MANAGER->Clean("access_check".$USER_ID, "access_check");
		$CACHE_MANAGER->Clean("access_codes".$USER_ID, "access_check");

		self::$arChecked[$provider][$USER_ID] = ($res->AffectedRowsCount() > 0);
	}

	public static function ClearStat($provider=false, $USER_ID=false)
	{
		global $DB, $CACHE_MANAGER;

		$arWhere = array();
		if($provider !== false)
			$arWhere[] = "provider_id='".$DB->ForSQL($provider)."'";
		if($USER_ID !== false)
			$arWhere[] = "user_id=".intval($USER_ID);

		$sWhere = '';
		if(!empty($arWhere))
			$sWhere = " where ".implode(" and ", $arWhere);

		$DB->Query("delete from b_user_access_check ".$sWhere);

		if($provider === false && $USER_ID === false)
		{
			self::$arChecked = array();
			$CACHE_MANAGER->CleanDir("access_check");
		}
		elseif($USER_ID === false)
		{
			unset(self::$arChecked[$provider]);
			$CACHE_MANAGER->CleanDir("access_check");
		}
		elseif($provider === false)
		{
			foreach(self::$arChecked as $pr=>$ar)
				unset(self::$arChecked[$pr][$USER_ID]);
			$CACHE_MANAGER->Clean("access_check".$USER_ID, "access_check");
			$CACHE_MANAGER->Clean("access_codes".$USER_ID, "access_check");
		}
		else
		{
			unset(self::$arChecked[$provider][$USER_ID]);
			$CACHE_MANAGER->Clean("access_check".$USER_ID, "access_check");
			$CACHE_MANAGER->Clean("access_codes".$USER_ID, "access_check");
		}
	}

	public static function GetUserCodes($USER_ID, $arFilter=array())
	{
		global $DB;

		$access = new CAccess();
		$access->UpdateCodes(array('USER_ID' => $USER_ID));

		$arWhere = array();
		foreach($arFilter as $key=>$val)
		{
			$key = strtoupper($key);
			switch($key)
			{
				case "ACCESS_CODE":
					if(!is_array($val))
						$val = array($val);
					$arIn = array();
					foreach($val as $code)
						if(trim($code) <> '')
							$arIn[] = "'".$DB->ForSQL(trim($code))."'";
					if(!empty($arIn))
						$arWhere[] = "access_code in(".implode(",", $arIn).")";
					break;
				case "PROVIDER_ID":
					$arWhere[] = "provider_id='".$DB->ForSQL($val)."'";
					break;
			}
		}

		$sWhere = '';
		if(!empty($arWhere))
			$sWhere = " and ".implode(" and ", $arWhere);

		return $DB->Query("select * from b_user_access where user_id=".intval($USER_ID).$sWhere);
	}

	public static function GetUserCodesArray($USER_ID, $arFilter=array())
	{
		global $CACHE_MANAGER;
		$USER_ID = intval($USER_ID);

		$useCache = (empty($arFilter) && CACHED_b_user_access_check !== false);

		if ($useCache && $CACHE_MANAGER->Read(CACHED_b_user_access_check, "access_codes".$USER_ID, "access_check"))
		{
			return $CACHE_MANAGER->Get("access_codes".$USER_ID);
		}
		else
		{
			$arCodes = array();
			$res = CAccess::GetUserCodes($USER_ID, $arFilter);
			while($arRes = $res->Fetch())
				$arCodes[] = $arRes["ACCESS_CODE"];

			if ($useCache)
				$CACHE_MANAGER->Set("access_codes".$USER_ID, $arCodes);

			return $arCodes;
		}
	}

	public function GetFormHtml($arParams=false)
	{
		$arHtml = array();
		foreach(self::$arAuthProviders as $provider)
		{
			$cl = new $provider["CLASS"];
			if(is_callable(array($cl, "GetFormHtml")))
			{
				$res = call_user_func_array(array($cl, "GetFormHtml"), array($this->arParams));
				if($res !== false)
					$arHtml[$provider["ID"]] = array("NAME"=>$provider["NAME"], "HTML"=>$res["HTML"], "SELECTED"=>$res["SELECTED"]);
			}
		}
		return $arHtml;
	}

	public function AjaxRequest($arParams)
	{
		if(array_key_exists($arParams["provider"], self::$arAuthProviders))
		{
			$cl = new self::$arAuthProviders[$arParams["provider"]]["CLASS"];
			if(is_callable(array($cl, "AjaxRequest")))
			{
				CUtil::JSPostUnescape();
				return call_user_func_array(array($cl, "AjaxRequest"), array($this->arParams));
			}
		}
		return false;
	}

	static public function GetNames($arCodes, $bSort=false)
	{
		$arResult = array();

		if(!is_array($arCodes) || empty($arCodes))
			return $arResult;

		foreach(self::$arAuthProviders as $provider)
		{

			$cl = new $provider["CLASS"];
			if(is_callable(array($cl, "GetNames")))
			{
				$res = call_user_func_array(array($cl, "GetNames"), array($arCodes));
				if(is_array($res))
				{
					foreach ($res as $codeId => $codeValues)
					{
						$codeValues['provider_id'] = $provider['ID'];
						$arResult[$codeId] = $codeValues;
					}
				}
			}
		}

		//possible unhandled values
		foreach($arCodes as $code)
			if(!array_key_exists($code, $arResult))
				$arResult[$code] = array("provider"=>"", "name"=>$code);

		if($bSort)
			uasort($arResult, array('CAccess', 'CmpNames'));

		return $arResult;
	}

	public static function CmpNames($a, $b)
	{
		$c = strcmp($a["provider"], $b["provider"]);
		if($c <> 0)
			return $c;

		return strcmp($a["name"], $b["name"]);
	}

	static public function GetProviderNames()
	{
		$arResult = array();
		foreach(self::$arAuthProviders as $ID=>$provider)
		{
			$arResult[$ID] = array(
				"name" => (isset($provider["PROVIDER_NAME"])? $provider["PROVIDER_NAME"] : $ID),
				"prefixes" => (isset($provider["PREFIXES"])? $provider["PREFIXES"] : array()),
			);
		}
		return $arResult;
	}

	public static function GetProviders()
	{
		return array(
			array(
				"ID" => "group",
				"NAME" => GetMessage("access_groups"),
				"PROVIDER_NAME" => GetMessage("access_group"),
				"SORT" => 100,
				"CLASS" => "CGroupAuthProvider",
			),
			array(
				"ID" => "user",
				"NAME" => GetMessage("access_users"),
				"PROVIDER_NAME" => GetMessage("access_user"),
				"SORT" => 200,
				"CLASS" => "CUserAuthProvider",
			),
			array(
				"ID" => "other",
				"NAME" => GetMessage("access_other"),
				"PROVIDER_NAME" => "",
				"SORT" => 1000,
				"CLASS" => "COtherAuthProvider",
			),
		);
	}

	public static function OnUserDelete($ID)
	{
		self::DeleteByUser($ID);
		return true;
	}

	public static function DeleteByUser($USER_ID)
	{
		global $DB;
		$USER_ID = intval($USER_ID);

		$DB->Query("delete from b_user_access where user_id=".$USER_ID);

		self::ClearStat(false, $USER_ID);
	}

	public static function SaveLastRecentlyUsed($arLRU)
	{
		foreach($arLRU as $provider=>$arRecent)
		{
			if(is_array($arRecent))
			{
				$arLastRecent = CUserOptions::GetOption("access_dialog_recent", $provider, array());

				$arItems = array_keys($arRecent);
				$arItems = array_unique(array_merge($arItems, $arLastRecent));
				$arItems = array_slice($arItems, 0, 20);

				CUserOptions::SetOption("access_dialog_recent", $provider, $arItems);
			}
		}
	}

	public static function GetLastRecentlyUsed($provider)
	{
		$res = CUserOptions::GetOption("access_dialog_recent", $provider, array());
		if(!is_array($res))
			$res = array();
		return $res;
	}
}

AddEventHandler("main", "OnAuthProvidersBuildList", array("CAccess", "GetProviders"));
