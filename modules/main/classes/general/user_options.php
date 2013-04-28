<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CUserOptions
{
	protected static $__USER_OPTIONS_DB;
	protected static $__USER_OPTIONS_MC;
	protected static $__USER_OPTIONS_CACHE;

	public static function GetList($arOrder = array("ID" => "ASC"), $arFilter = array())
	{
		global $DB;

		$arSqlSearch = array();
		foreach ($arFilter as $key => $val)
		{
			$key = strtoupper($key);
			switch ($key)
			{
			case "ID":
				$arSqlSearch[] = "UO.ID = ".intval($val);
				break;

			case "USER_ID":
				$arSqlSearch[] = "UO.USER_ID = ".intval($val);
				break;

			case "USER_ID_EXT":
				$arSqlSearch[] = "(UO.USER_ID = ".intval($val)." OR UO.COMMON='Y')";
				break;

			case "CATEGORY":
				$arSqlSearch[] = "UO.CATEGORY = '".$DB->ForSql($val)."'";
				break;

			case "NAME":
				$arSqlSearch[] = "UO.NAME = '".$DB->ForSql($val)."'";
				break;

			case "NAME_MASK":
				$arSqlSearch[] = GetFilterQuery("UO.NAME", $val);
				break;

			case "COMMON":
				$arSqlSearch[] = "UO.COMMON = '".$DB->ForSql($val)."'";
				break;
			}
		}

		$strSqlSearch = "";
		foreach ($arSqlSearch as $condition)
			if (strlen($condition) > 0)
				$strSqlSearch.= " AND  (".$condition.") ";

		$strSql = "
			SELECT UO.ID, UO.USER_ID, UO.CATEGORY, UO.NAME, UO.COMMON, UO.VALUE
			FROM b_user_option UO
			WHERE 1 = 1
			".$strSqlSearch."
		";

		$arSqlOrder = array();
		if (is_array($arOrder))
		{
			foreach ($arOrder as $by => $order)
			{
				$by = strtoupper($by);
				$order = strtoupper($order);
				if ($order != "ASC")
					$order = "DESC";

				if ($by == "ID")
					$arSqlOrder[$by] = " UO.ID ".$order." ";
				elseif ($by == "USER_ID")
					$arSqlOrder[$by] = " UO.USER_ID ".$order." ";
				elseif ($by == "CATEGORY")
					$arSqlOrder[$by] = " UO.CATEGORY ".$order." ";
				elseif ($by == "NAME")
					$arSqlOrder[$by] = " UO.NAME ".$order." ";
				elseif ($by == "COMMON")
					$arSqlOrder[$by] = " UO.COMMON ".$order." ";
			}
		}

		if (!empty($arSqlOrder))
			$strSqlOrder = "ORDER BY ".implode(", ", $arSqlOrder);
		else
			$strSqlOrder = "";

		$res = $DB->Query($strSql.$strSqlOrder, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $res;
	}

	public static function GetOption($category, $name, $default_value = false, $user_id = false)
	{
		global $DB, $USER, $CACHE_MANAGER;
		if (!isset(self::$__USER_OPTIONS_DB))
			self::_clear_cache();

		if ($user_id === false)
			$user_id = $USER->GetID();

		$user_id = intval($user_id);
		$cache_key = $category.".".$name;

		if ($category !== 'admin_menu' && $category !== 'favorite' && $user_id > 0)
		{
			if (!isset(self::$__USER_OPTIONS_MC[$user_id]))
			{
				if ($CACHE_MANAGER->read(3600, $mcache_id = "user_option:$user_id", "user_option"))
				{
					self::$__USER_OPTIONS_MC[$user_id] = $CACHE_MANAGER->get($mcache_id);
				}
				else
				{
					$strSql = "
						SELECT CATEGORY, NAME, VALUE, COMMON
						FROM b_user_option
						WHERE (USER_ID=".$user_id." OR USER_ID IS NULL AND COMMON='Y')
						AND CATEGORY not in ('admin_menu', 'favorite')
					";

					$res = $DB->Query($strSql);
					while ($res_array = $res->Fetch())
					{
						$row_cache_key = $res_array["CATEGORY"].".".$res_array["NAME"];
						if (!isset(self::$__USER_OPTIONS_MC[$user_id][$row_cache_key]) || $res_array["COMMON"] <> 'Y')
							self::$__USER_OPTIONS_MC[$user_id][$row_cache_key] = $res_array["VALUE"];
					}

					$CACHE_MANAGER->Set($mcache_id, self::$__USER_OPTIONS_MC[$user_id]);
				}
			}

			if (!isset(self::$__USER_OPTIONS_MC[$user_id][$cache_key]))
				return $default_value;
		}
		else
		{
			if (!isset(self::$__USER_OPTIONS_DB[$user_id]))
			{
				//user (or default) options
				$strSql = "
					SELECT CATEGORY, NAME, VALUE, COMMON
					FROM b_user_option
					WHERE (USER_ID=".$user_id." OR USER_ID IS NULL AND COMMON='Y')
					AND (CATEGORY in ('admin_menu', 'favorite') OR USER_ID=0)
				";

				$res = $DB->Query($strSql);
				while ($res_array = $res->Fetch())
				{
					$row_cache_key = $res_array["CATEGORY"].".".$res_array["NAME"];
					if (!isset(self::$__USER_OPTIONS_DB[$user_id][$row_cache_key]) || $res_array["COMMON"] <> 'Y')
						self::$__USER_OPTIONS_DB[$user_id][$row_cache_key] = $res_array["VALUE"];
				}
			}

			if (!isset(self::$__USER_OPTIONS_DB[$user_id][$cache_key]))
				return $default_value;
		}

		if (!isset(self::$__USER_OPTIONS_CACHE[$user_id][$cache_key]))
		{
			if (isset(self::$__USER_OPTIONS_MC[$user_id][$cache_key]))
				self::$__USER_OPTIONS_CACHE[$user_id][$cache_key] = unserialize(self::$__USER_OPTIONS_MC[$user_id][$cache_key]);
			else
				self::$__USER_OPTIONS_CACHE[$user_id][$cache_key] = unserialize(self::$__USER_OPTIONS_DB[$user_id][$cache_key]);
		}

		return self::$__USER_OPTIONS_CACHE[$user_id][$cache_key];
	}

	public static function SetOption($category, $name, $value, $bCommon = false, $user_id = false)
	{
		global $DB, $USER;

		if ($user_id === false && $bCommon === false)
			$user_id = $USER->GetID();

		$user_id = intval($user_id);
		$arFields = array(
			"USER_ID" => ($bCommon ? false : $user_id),
			"CATEGORY" => $category,
			"NAME" => $name,
			"VALUE" => serialize($value),
			"COMMON" => ($bCommon ? "Y" : "N"),
		);
		$res = $DB->Query("
			SELECT ID FROM b_user_option
			WHERE
			".($bCommon ? "USER_ID IS NULL AND COMMON='Y' " : "USER_ID=".$user_id)."
			AND CATEGORY='".$DB->ForSql($category, 50)."'
			AND NAME='".$DB->ForSql($name, 255)."'
		");

		if ($res_array = $res->Fetch())
		{
			$strUpdate = $DB->PrepareUpdate("b_user_option", $arFields);
			if ($strUpdate != "")
			{
				$strSql = "UPDATE b_user_option SET ".$strUpdate." WHERE ID=".$res_array["ID"];
				if (!$DB->QueryBind($strSql, array("VALUE" => $arFields["VALUE"])))
					return false;
			}
		}
		else
		{
			if (!$DB->Add("b_user_option", $arFields, array("VALUE")))
				return false;
		}

		self::_clear_cache($category, $bCommon, $user_id);
		return true;
	}

	public static function SetOptionsFromArray($aOptions)
	{
		global $USER;

		foreach ($aOptions as $opt)
		{
			if ($opt["c"] <> "" && $opt["n"] <> "")
			{
				$val = $opt["v"];
				if (is_array($opt["v"]))
				{
					$val = CUserOptions::GetOption($opt["c"], $opt["n"], array());
					foreach ($opt["v"] as $k => $v)
						$val[$k] = $v;
				}
				CUserOptions::SetOption($opt["c"], $opt["n"], $val);
				if ($opt["d"] == "Y" && $USER->CanDoOperation('edit_other_settings'))
					CUserOptions::SetOption($opt["c"], $opt["n"], $val, true);
			}
		}
	}

	public static function DeleteOption($category, $name, $bCommon = false, $user_id = false)
	{
		global $DB, $USER;
		if ($user_id === false)
			$user_id = $USER->GetID();

		$user_id = intval($user_id);
		$strSql = "
			DELETE FROM b_user_option
			WHERE ".($bCommon ? "USER_ID IS NULL AND COMMON='Y' " : "USER_ID=".$user_id)."
			AND CATEGORY='".$DB->ForSql($category, 50)."'
			AND NAME='".$DB->ForSql($name, 255)."'
		";
		if ($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			self::_clear_cache($category, $bCommon, $user_id);
			return true;
		}
		return false;
	}

	public static function DeleteCommonOptions()
	{
		global $DB;
		if ($DB->Query("DELETE FROM b_user_option WHERE COMMON='Y' AND NAME NOT LIKE '~%'", false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			self::_clear_cache(true, true);
			return true;
		}
		return false;
	}

	public static function DeleteUsersOptions($user_id=false)
	{
		global $DB;
		if ($DB->Query("DELETE FROM b_user_option WHERE USER_ID IS NOT NULL AND NAME NOT LIKE '~%'  ".($user_id <> false? " AND USER_ID=".intval($user_id):""), false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			self::_clear_cache(true, ($user_id <> false? true: false), $user_id);
			return true;
		}
		return false;
	}

	public static function SetCookieOptions($cookieName)
	{
		//last user setting
		$varCookie = array();
		parse_str($_COOKIE[$cookieName], $varCookie);
		setcookie($cookieName, false, false, "/");
		if (is_array($varCookie["p"]) && $varCookie["sessid"] == bitrix_sessid())
		{
			$arOptions = $varCookie["p"];
			CUtil::decodeURIComponent($arOptions);
			CUserOptions::SetOptionsFromArray($arOptions);
		}
	}

	//*****************************
	// Events
	//*****************************

	//user deletion event
	public static function OnUserDelete($user_id)
	{
		global $DB;
		$user_id = intval($user_id);

		if ($DB->Query("DELETE FROM b_user_option WHERE USER_ID=". $user_id, false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			self::_clear_cache(true, false, $user_id);
			return true;
		}
		return false;
	}

	protected static function _clear_cache($category = false, $bCommon = false, $user_id = 0)
	{
		global $CACHE_MANAGER;

		self::$__USER_OPTIONS_CACHE = array();
		self::$__USER_OPTIONS_DB = array();
		self::$__USER_OPTIONS_MC = array();

		if ($category !== false)
		{
			if ($category !== 'admin_menu' && $category !== 'favorite')
			{
				if ($bCommon)
					$CACHE_MANAGER->cleanDir("user_option");
				else
					$CACHE_MANAGER->clean("user_option:$user_id", "user_option");
			}
		}
	}
}
