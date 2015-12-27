<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/statistic/classes/general/guest.php");

/**
 * <b>CGuest</b> - класс для получения данных по <a href="http://dev.1c-bitrix.ru/api_help/statistic/terms.php#guest">посетителям</a> сайта. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/statistic/classes/cguest/index.php
 * @author Bitrix
 */
class CGuest extends CAllGuest
{
	public static function GetLastByID($ID)
	{
		$DB = CDatabase::GetModuleConnection('statistic');
		$ID = intval($ID);
		if($ID>0)
		{
			$strSql = "
				SELECT
					G.ID,
					G.FAVORITES,
					G.LAST_USER_ID,
					A.ID as LAST_ADV_ID,
					if(to_days(curdate())=to_days(G.LAST_DATE), 'Y', 'N') LAST
				FROM b_stat_guest G
				LEFT JOIN b_stat_adv A ON A.ID = G.LAST_ADV_ID
				WHERE G.ID='$ID'
				";
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$res = new CDBResult;
			$res->InitFromArray(array());
		}
		return $res;
	}
}
?>
