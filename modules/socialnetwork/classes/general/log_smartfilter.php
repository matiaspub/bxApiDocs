<?
class CAllSocNetLogSmartFilter
{
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("USER_ID", "TYPE");

		// FIELDS -->
		$arFields = array(
			"USER_ID" => Array("FIELD" => "SLSF.USER_ID", "TYPE" => "int"),
			"TYPE" => array("FIELD" => "SLSF.TYPE", "TYPE" => "char")
		);
		// <-- FIELDS

		$arSqls = CSocNetGroup::PrepareSql($arFields, $arOrder, $arFilter, false, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_log_smartfilter SLSF ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	public static function DeleteEx($user_id)
	{
		global $DB;
		
		$user_id = intval($user_id);

		if ($user_id <= 0)
			return false;
		
		$strWhere = " USER_ID = ".$user_id;

		$strSQL = "DELETE FROM b_sonet_log_smartfilter WHERE ".$strWhere;
		if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
			return true;
		else
			return false;		
	}

	public static function GetDefaultValue($user_id)
	{
		if (intval($user_id) <= 0)
			return false;

		global $CACHE_MANAGER;

		if(defined("BX_COMP_MANAGED_CACHE"))
			$ttl = 2592000;
		else
			$ttl = 600;

		$cache_id = 'sonet_smartfilter_default_'.$user_id;
		$obCache = new CPHPCache;
		$cache_dir = '/sonet/log_smartfilter/';

		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$tmpVal = $obCache->GetVars();
			$default_value = $tmpVal["VALUE"];
			unset($tmpVal);
		}
		else
		{
			$default_value = false;
			
			if (is_object($obCache))
				$obCache->StartDataCache($ttl, $cache_id, $cache_dir);

			$rsSmartFilter = CSocNetLogSmartFilter::GetList(
				array(),
				array(
					"USER_ID" => $user_id
				),
				array("TYPE")
			);
			if ($arSmartFilter = $rsSmartFilter->Fetch())
				$default_value = $arSmartFilter["TYPE"];

			if (is_object($obCache))
			{
				$arCacheData = Array(
					"VALUE" => $default_value
				);
				$obCache->EndDataCache($arCacheData);
			}
		}
		unset($obCache);

		if (!$default_value)
			$default_value = COption::GetOptionString("socialnetwork", "sonet_log_smart_filter", "N", "");

		return $default_value;
	}
}
?>