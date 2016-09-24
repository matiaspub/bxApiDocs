<?
/*
##############################################
# Bitrix Site Manager                        #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
*/
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/quota.php");

/**
 * <b>CDiskQuota</b> - класс для работы с дисковыми квотами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cdiskquota/index.php
 * @author Bitrix
 */
class CDiskQuota extends CAllDiskQuota
{
	public static function SetDBSize()
	{
		global $DB;
		$DBSize = 0;
		if (($_SESSION["SESS_RECOUNT_DB"] == "Y") && (COption::GetOptionInt("main", "disk_space") > 0))
		{
			$db_res = $DB->Query("SHOW TABLE STATUS FROM `".$DB->ForSql($DB->DBName)."`");
			if ($db_res && ($res = $db_res->Fetch()))
			{
				do
				{
					$DBSize += $res["Data_length"] + $res["Index_length"];
				}
				while ($res = $db_res->Fetch());
			}
			COption::SetOptionString("main_size", "~db", $DBSize);
			$params = array("status" => "d", "time" => time());
			COption::SetOptionString("main_size", "~db_params", serialize($params));
			unset($_SESSION["SESS_RECOUNT_DB"]);
		}
		else
		{
			$params = array("status" => "d", "time" => false);
		}

		return array("status" => "done", "size" => $DBSize, "time" => $params["time"]);
	}
}
?>