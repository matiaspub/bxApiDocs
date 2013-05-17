<?
class CAllSocNetLogPages
{
	public static function GetList($arFilter = Array(), $arSelectFields = array())
	{
		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("USER_ID", "SITE_ID", "PAGE_SIZE", "PAGE_NUM", "PAGE_LAST_DATE");

		// FIELDS -->
		$arFields = array(
			"USER_ID" => Array("FIELD" => "SLP.USER_ID", "TYPE" => "int"),
			"SITE_ID" => Array("FIELD" => "SLP.SITE_ID", "TYPE" => "string"),
			"PAGE_SIZE" => array("FIELD" => "SLP.PAGE_SIZE", "TYPE" => "int"),
			"PAGE_NUM" => array("FIELD" => "SLP.PAGE_NUM", "TYPE" => "int"),
			"PAGE_LAST_DATE" => Array("FIELD" => "SLP.PAGE_LAST_DATE", "TYPE" => "datetime"),
		);
		// <-- FIELDS

		$arSqls = CSocNetGroup::PrepareSql($arFields, array(), $arFilter, false, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_log_page SLP ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	public static function DeleteEx($user_id, $site_id = '**', $page_size = false)
	{
		global $DB;
		
		$user_id = intval($user_id);

		if ($user_id <= 0)
			return false;
		
		$strWhere = " USER_ID = ".$user_id;
		if (
			strlen($site_id) > 0
			&& $site_id != "**"
		)
			$strWhere .= " AND SITE_ID = '".$site_id."'";

		if (intval($page_size) > 0)
			$strWhere .= " AND PAGE_SIZE = ".$page_size;

		$strSQL = "DELETE FROM b_sonet_log_page WHERE ".$strWhere;
		if ($DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__))
			return true;
		else
			return false;		
	}
	
	
}
?>