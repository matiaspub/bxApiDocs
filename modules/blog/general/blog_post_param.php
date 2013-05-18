<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage blog
 * @copyright 2001-2013 Bitrix
 */

class CBlogUserOptions
{
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
			case "POST_ID":
			case "USER_ID":
				$val = intval($val);
				$arSqlSearch[] = ($val > 0 ? "BPP.".$key." IS NULL" : "BPP.".$key."=".$val);
				break;
			case "NAME":
				$arSqlSearch[] = "BPP.NAME = '".$DB->ForSql($val, 50)."'";
				break;
			case "NAME_MASK":
				$arSqlSearch[] = GetFilterQuery("BPP.NAME", $val);
				break;
			}
		}

		$strSql = "
			SELECT BPP.ID, BPP.USER_ID, BPP.POST_ID, BPP.NAME, BPP.VALUE
			FROM b_blog_post_param BPP
			WHERE 1 = 1
			".(empty($arSqlSearch) ? "" : " AND (".implode($arSqlSearch, ") AND (").")")."
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
					$arSqlOrder[$by] = " BP.ID ".$order." ";
				elseif ($by == "USER_ID")
					$arSqlOrder[$by] = " BP.USER_ID ".$order." ";
				elseif ($by == "POST_ID")
					$arSqlOrder[$by] = " BP.POST_ID ".$order." ";
				elseif ($by == "NAME")
					$arSqlOrder[$by] = " BP.NAME ".$order." ";
			}
		}
		$strSqlOrder = (!empty($arSqlOrder) ? "ORDER BY ".implode(", ", $arSqlOrder) : "");
		return $DB->Query($strSql.$strSqlOrder, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
	}

	public static function GetOption($post_id, $name, $default_value = false, $user_id = false)
	{
		global $DB, $USER, $CACHE_MANAGER;

		$post_id = intval($post_id);
		if ($user_id === false)
			$user_id = $USER->GetID();
		$user_id = intval($user_id);
		$cache_key = $post_id.":".$name;

		if (!isset(self::$__USER_OPTIONS_CACHE[$user_id]))
		{
			$mcache_id = "user_option:$user_id";
			if ($CACHE_MANAGER->read(3600, $mcache_id, "blog_post_param") && false)
			{
				self::$__USER_OPTIONS_CACHE[$user_id] = $CACHE_MANAGER->get($mcache_id);
			}
			else
			{
				$strSql = "
					SELECT POST_ID, USER_ID, NAME, VALUE
					FROM b_blog_post_param
					WHERE (USER_ID=".$user_id." OR USER_ID IS NULL)";
				$db_res = $DB->Query($strSql);

				while ($res = $db_res->Fetch())
				{
					$row_cache_key = $res["POST_ID"].":".$res["NAME"];
					$res["USER_ID"] = intval($res["USER_ID"]);

					if (!isset(self::$__USER_OPTIONS_CACHE[$res["USER_ID"]][$row_cache_key]))
						self::$__USER_OPTIONS_CACHE[$res["USER_ID"]][$row_cache_key] = $res["VALUE"];
				}
				$CACHE_MANAGER->Set($mcache_id, self::$__USER_OPTIONS_CACHE[$user_id]);
			}
		}
		if (!isset(self::$__USER_OPTIONS_CACHE[$user_id][$cache_key]))
			return $default_value;
		return self::$__USER_OPTIONS_CACHE[$user_id][$cache_key];
	}

	public static function SetOption($post_id, $name, $value, $user_id = false)
	{
		global $DB, $USER;

		$post_id = intval($post_id);
		if ($user_id === false)
			$user_id = $USER->GetID();

		$user_id = intval($user_id);
		$arFields = array(
			"POST_ID" => ($post_id > 0 ? $post_id : false),
			"USER_ID" => ($user_id > 0 ? $user_id : false),
			"NAME" => $name,
			"VALUE" => $value
		);
		$res = $DB->Query(
			"SELECT ID FROM b_blog_post_param
			WHERE
			".($post_id <= 0 ? "POST_ID IS NULL" : "POST_ID=".$post_id)." AND
			".($user_id <= 0 ? "USER_ID IS NULL" : "USER_ID=".$user_id)." AND
			NAME='".$DB->ForSql($name, 50)."'");
			if ($res_array = $res->Fetch())
		{
			$strUpdate = $DB->PrepareUpdate("b_blog_post_param", $arFields);
			if ($strUpdate != "")
			{
				$strSql = "UPDATE b_blog_post_param SET ".$strUpdate." WHERE ID=".$res_array["ID"];
				if (!$DB->QueryBind($strSql, array() ))
					return false;
			}
		}
		else
		{
			if (!$DB->Add("b_blog_post_param", $arFields, array()))
				return false;
		}

		self::_clear_cache($user_id);
		return true;
	}

	public static function DeleteOption($post_id, $name, $user_id = false)
	{
		global $DB, $USER;
		$post_id = intval($post_id);
		if ($user_id === false)
			$user_id = $USER->GetID();
		$user_id = intval($user_id);

		$strSql = "
			DELETE FROM b_blog_post_param
			WHERE
				".($post_id <= 0 ? "POST_ID IS NULL " : "POST_ID=".$post_id)."
			AND ".($user_id <= 0 ? "USER_ID IS NULL " : "USER_ID=".$user_id)."
			AND NAME='".$DB->ForSql($name, 50)."'
		";
		if ($DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			self::_clear_cache($user_id);
			return true;
		}
		return false;
	}

	public static function DeleteUsersOptions($user_id=false)
	{
		global $DB;
		$user_id = intval($user_id);
		if ($DB->Query("DELETE FROM b_blog_post_param WHERE ".($user_id <= 0 ? "USER_ID IS NULL" : "USER_ID=".$user_id), false, "File: ".__FILE__."<br>Line: ".__LINE__))
		{
			self::_clear_cache($user_id);
			return true;
		}
		return false;
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
			self::_clear_cache($user_id);
			return true;
		}
		return false;
	}

	protected static function _clear_cache($user_id = 0)
	{
		global $CACHE_MANAGER;

		self::$__USER_OPTIONS_CACHE = array();

		if ($user_id > 0)
			$CACHE_MANAGER->cleanDir("blog_post_param");
		else
			$CACHE_MANAGER->clean("user_option:$user_id", "blog_post_param");
	}
}
?>