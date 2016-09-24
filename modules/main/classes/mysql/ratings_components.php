<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/ratings_components.php");
IncludeModuleLangFile(__FILE__);

class CRatingsComponentsMain extends CAllRatingsComponentsMain
{
	// Calc function
	public static function CalcVoteUser($arConfigs)
	{
		global $DB;

		$err_mess = (CRatings::err_mess())."<br>Function: CalcVoteUser<br>Line: ";

		CRatings::AddComponentResults($arConfigs);

		$strSql = "DELETE FROM b_rating_component_results WHERE RATING_ID = '".IntVal($arConfigs['RATING_ID'])."' AND COMPLEX_NAME = '".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "INSERT INTO b_rating_component_results (RATING_ID, MODULE_ID, RATING_TYPE, NAME, COMPLEX_NAME, ENTITY_ID, ENTITY_TYPE_ID, CURRENT_VALUE)
					SELECT
						'".IntVal($arConfigs['RATING_ID'])."'  RATING_ID,
						'".$DB->ForSql($arConfigs['MODULE_ID'])."'  MODULE_ID,
						'".$DB->ForSql($arConfigs['RATING_TYPE'])."'  RATING_TYPE,
						'".$DB->ForSql($arConfigs['NAME'])."'  NAME,
						'".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'  COMPLEX_NAME,
						RV.ENTITY_ID as ENTITY_ID,
						'".$DB->ForSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
						SUM(RVE.VALUE)*".floatval($arConfigs['CONFIG']['COEFFICIENT'])." CURRENT_VALUE
					FROM
						b_rating_voting RV,
						b_rating_vote RVE
					WHERE
						RV.ENTITY_TYPE_ID = 'USER' AND RV.ENTITY_ID > 0
					AND RVE.RATING_VOTING_ID = RV.ID".(intval($arConfigs['CONFIG']['LIMIT']) > 0 ? " AND RVE.CREATED > DATE_SUB(NOW(), INTERVAL ".intval($arConfigs['CONFIG']['LIMIT'])." DAY)" : "")."
					GROUP BY RV.ENTITY_ID";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function CalcUserBonus($arConfigs)
	{
		global $DB;

		$err_mess = (CRatings::err_mess())."<br>Function: CalcUserBonus<br>Line: ";

		$communityLastVisit = COption::GetOptionString("main", "rating_community_last_visit", '90');

		CRatings::AddComponentResults($arConfigs);

		$strSql = "DELETE FROM b_rating_component_results WHERE RATING_ID = '".IntVal($arConfigs['RATING_ID'])."' AND COMPLEX_NAME = '".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = "INSERT INTO b_rating_component_results (RATING_ID, MODULE_ID, RATING_TYPE, NAME, COMPLEX_NAME, ENTITY_ID, ENTITY_TYPE_ID, CURRENT_VALUE)
					SELECT
						'".IntVal($arConfigs['RATING_ID'])."'  RATING_ID,
						'".$DB->ForSql($arConfigs['MODULE_ID'])."'  MODULE_ID,
						'".$DB->ForSql($arConfigs['RATING_TYPE'])."'  RATING_TYPE,
						'".$DB->ForSql($arConfigs['NAME'])."'  NAME,
						'".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'  COMPLEX_NAME,
						RB.ENTITY_ID as ENTITY_ID,
						'".$DB->ForSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
						RB.BONUS*".floatval($arConfigs['CONFIG']['COEFFICIENT'])."  CURRENT_VALUE
					FROM
						b_rating_user RB
						LEFT JOIN b_user U ON U.ID = RB.ENTITY_ID AND U.ACTIVE = 'Y' AND U.LAST_LOGIN > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
					WHERE
						RB.RATING_ID = ".IntVal($arConfigs['RATING_ID'])."
						AND U.ID IS NOT NULL
					";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}
}
