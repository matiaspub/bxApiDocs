<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/blog/general/ratings_components.php");

class CRatingsComponentsBlog extends CAllRatingsComponentsBlog
{
	public static function CalcPost($arConfigs)
	{
		global $DB;

		$err_mess = (CRatings::err_mess())."<br>Function: CRatingsComponentsBlog::CalcPost<br>Line: ";

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
						FT.AUTHOR_ID  ENTITY_ID,
						'".$DB->ForSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
						SUM(RVE.VALUE)*".floatval($arConfigs['CONFIG']['COEFFICIENT'])."  CURRENT_VALUE
					FROM
						b_rating_voting RV LEFT JOIN b_blog_post FT ON RV.ENTITY_ID = FT.ID,
						b_rating_vote RVE
					WHERE
						RV.ENTITY_TYPE_ID = 'BLOG_POST' AND FT.AUTHOR_ID > 0
					AND RVE.RATING_VOTING_ID = RV.ID".(IntVal($arConfigs['CONFIG']['LIMIT']) > 0 ? " AND RVE.CREATED > DATE_SUB(NOW(), INTERVAL ".IntVal($arConfigs['CONFIG']['LIMIT'])." DAY)" : "")."
					GROUP BY AUTHOR_ID";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function CalcComment($arConfigs)
	{
		global $DB;

		$err_mess = (CRatings::err_mess())."<br>Function: CRatingsComponentsBlog::CalcComment<br>Line: ";

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
						FM.AUTHOR_ID  ENTITY_ID,
						'".$DB->ForSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
						SUM(RVE.VALUE)*".floatval($arConfigs['CONFIG']['COEFFICIENT'])."  CURRENT_VALUE
					FROM
						b_rating_voting RV LEFT JOIN b_blog_comment FM ON RV.ENTITY_ID = FM.ID,
						b_rating_vote RVE
					WHERE
						RV.ENTITY_TYPE_ID = 'BLOG_COMMENT' AND FM.AUTHOR_ID > 0
					AND RVE.RATING_VOTING_ID = RV.ID".(IntVal($arConfigs['CONFIG']['LIMIT']) > 0 ? " AND RVE.CREATED > DATE_SUB(NOW(), INTERVAL ".IntVal($arConfigs['CONFIG']['LIMIT'])." DAY)" : "")."
					GROUP BY AUTHOR_ID";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function CalcActivity($arConfigs)
	{
		global $DB;

		$err_mess = (CRatings::err_mess())."<br>Function: CRatingsComponentsBlog::CalcActivity<br>Line: ";

		CRatings::AddComponentResults($arConfigs);

		$strSql = "DELETE FROM b_rating_component_results WHERE RATING_ID = '".IntVal($arConfigs['RATING_ID'])."' AND COMPLEX_NAME = '".$DB->ForSql($arConfigs['COMPLEX_NAME'])."'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$sqlAllPost = '';
		if (isset($arConfigs['CONFIG']['ALL_POST_COEF']) && $arConfigs['CONFIG']['ALL_POST_COEF'] != 0) {
			$sqlAllPost = "
				SELECT
					AUTHOR_ID as ENTITY_ID,
					COUNT(*)*".floatval($arConfigs['CONFIG']['ALL_POST_COEF'])." as CURRENT_VALUE
				FROM b_blog_post
				WHERE DATE_PUBLISH < DATE_SUB(NOW(), INTERVAL 30 DAY)
						AND PUBLISH_STATUS = '".BLOG_PUBLISH_STATUS_PUBLISH."'
				GROUP BY AUTHOR_ID
				UNION ALL ";
		}
		$sqlAllComment = '';
		if (isset($arConfigs['CONFIG']['ALL_COMMENT_COEF']) && $arConfigs['CONFIG']['ALL_COMMENT_COEF'] != 0) {
			$sqlAllComment = "
				SELECT
					AUTHOR_ID as ENTITY_ID,
					COUNT(*)*".floatval($arConfigs['CONFIG']['ALL_COMMENT_COEF'])." as CURRENT_VALUE
				FROM b_blog_comment
				WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 30 DAY)
					AND PUBLISH_STATUS = '".BLOG_PUBLISH_STATUS_PUBLISH."'
				GROUP BY AUTHOR_ID
				UNION ALL ";
		}
		$strSql = "INSERT INTO b_rating_component_results (RATING_ID, MODULE_ID, RATING_TYPE, NAME, COMPLEX_NAME, ENTITY_ID, ENTITY_TYPE_ID, CURRENT_VALUE)
			SELECT
				'".IntVal($arConfigs['RATING_ID'])."' as RATING_ID,
				'".$DB->ForSql($arConfigs['MODULE_ID'])."' as MODULE_ID,
				'".$DB->ForSql($arConfigs['RATING_TYPE'])."' as RATING_TYPE,
				'".$DB->ForSql($arConfigs['NAME'])."' as NAME,
				'".$DB->ForSql($arConfigs['COMPLEX_NAME'])."' as COMPLEX_NAME,
				ENTITY_ID,
				'".$DB->ForSql($arConfigs['ENTITY_ID'])."'  ENTITY_TYPE_ID,
				SUM(CURRENT_VALUE) CURRENT_VALUE
			FROM (
				".$sqlAllPost."
				SELECT
					AUTHOR_ID as ENTITY_ID,
					SUM(IF(TO_DAYS(DATE_PUBLISH) > TO_DAYS(NOW())-1, 1, 0))*".floatval($arConfigs['CONFIG']['TODAY_POST_COEF'])." +
					SUM(IF(TO_DAYS(DATE_PUBLISH) > TO_DAYS(NOW())-7, 1, 0))*".floatval($arConfigs['CONFIG']['WEEK_POST_COEF'])."+
					COUNT(*)*".floatval($arConfigs['CONFIG']['MONTH_POST_COEF'])." as CURRENT_VALUE
				FROM b_blog_post
				WHERE DATE_PUBLISH  > DATE_SUB(NOW(), INTERVAL 30 DAY)
						AND PUBLISH_STATUS = '".BLOG_PUBLISH_STATUS_PUBLISH."'
				GROUP BY AUTHOR_ID

				UNION ALL
				".$sqlAllComment."
				SELECT
					AUTHOR_ID as ENTITY_ID,
					SUM(IF(TO_DAYS(DATE_CREATE) > TO_DAYS(NOW())-1, 1, 0))*".floatval($arConfigs['CONFIG']['TODAY_COMMENT_COEF'])." +
					SUM(IF(TO_DAYS(DATE_CREATE) > TO_DAYS(NOW())-7, 1, 0))*".floatval($arConfigs['CONFIG']['WEEK_COMMENT_COEF'])." +
					COUNT(*)*".floatval($arConfigs['CONFIG']['MONTH_COMMENT_COEF'])." as CURRENT_VALUE
				FROM b_blog_comment
				WHERE DATE_CREATE  > DATE_SUB(NOW(), INTERVAL 30 DAY)
					AND PUBLISH_STATUS = '".BLOG_PUBLISH_STATUS_PUBLISH."'
				GROUP BY AUTHOR_ID
			) q
			WHERE ENTITY_ID > 0
			GROUP BY ENTITY_ID";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}
}
?>