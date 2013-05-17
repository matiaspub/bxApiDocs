<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/rating_rules.php");
IncludeModuleLangFile(__FILE__);

class CRatingRulesMain extends CAllRatingRulesMain
{
	public static function err_mess()
	{
		return "<br>Class: CRatingRulesMain<br>File: ".__FILE__;
	}

	public static function voteCheck($arConfigs)
	{
		global $DB;
			
		$err_mess = "File: ".__FILE__."<br>Function: voteCheck<br>Line: ";
		
		$ratingId = CRatings::GetAuthorityRating();
		if ($ratingId == 0)
			return true;
		
		// 1. UPDATE OLD VOTE (< 90 day)
		$strSql = "
			UPDATE
				b_rating_vote
			SET
				ACTIVE = 'N',
				USER_ID = 0
			WHERE 
				ENTITY_TYPE_ID = 'USER' and CREATED < DATE_SUB(NOW(), INTERVAL ".intval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT'])." DAY)
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		
		// 2. INSERT NEW VOTE FOR AUTHORITY
		$sRatingUser = "";
		$sRatingWeightType = COption::GetOptionString("main", "rating_weight_type", "auto");
		if ($sRatingWeightType == 'auto')
		{
			$sRatingAuthrorityWeight = COption::GetOptionString("main", "rating_authority_weight_formula", 'Y');
			if ($sRatingAuthrorityWeight == 'Y')
			{
				$communitySize = COption::GetOptionString("main", "rating_community_size", 1);
				$communityAuthority = COption::GetOptionString("main", "rating_community_authority", 1);
				$voteWeight = COption::GetOptionString("main", "rating_vote_weight", 1);
				$sValue = "($communitySize*(RR.VOTE_WEIGHT/".round($voteWeight, 4).")/".round($communityAuthority).") as VALUE";
				
				$ratingId = CRatings::GetAuthorityRating();
				$sRatingUser = "LEFT JOIN b_rating_user RR ON RR.RATING_ID = ".intval($ratingId)." AND RR.ENTITY_ID = RV.USER_ID";
			}
			else
				$sValue = "1 as VALUE";
		}
		else
		{		
			$ratingId = CRatings::GetAuthorityRating();
			$sRatingUser = "LEFT JOIN b_rating_user RR ON RR.RATING_ID = ".intval($ratingId)." AND RR.ENTITY_ID = RV.USER_ID";
			$sValue = "RR.VOTE_WEIGHT as VALUE";
		}
		
		$strSql = "
			INSERT INTO b_rating_vote (RATING_VOTING_ID, VALUE, ACTIVE, CREATED, USER_ID, USER_IP, ENTITY_TYPE_ID, ENTITY_ID, OWNER_ID)
			SELECT 
				0 as RATING_VOTING_ID,
			   $sValue,
				'N' as ACTIVE,
		   	".$DB->GetNowFunction()." as CREATED,
				RV.USER_ID, 
				'auto' as USER_IP, 
				'USER' as ENTITY_TYPE_ID,
				RV.OWNER_ID as ENTITY_ID, 
				RV.OWNER_ID
			FROM  
				b_rating_vote RV 
				$sRatingUser
				LEFT JOIN b_rating_vote RV2 ON RV2.USER_ID = RV.USER_ID AND RV2.ENTITY_TYPE_ID = 'USER' AND RV2.ENTITY_ID = RV.OWNER_ID
			WHERE 
				RV.CREATED > DATE_SUB(NOW(), INTERVAL ".intval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT'])." DAY)
			and RV.VALUE > 0 and RV2.VALUE IS NULL and RV.OWNER_ID > 0
			GROUP BY RV.USER_ID, RV.OWNER_ID
			HAVING 
				SUM(case
					when RV.ENTITY_TYPE_ID = 'FORUM_TOPIC' then ".floatval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_TOPIC'])."
					when RV.ENTITY_TYPE_ID = 'FORUM_POST' then ".floatval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_POST'])."
					when RV.ENTITY_TYPE_ID = 'BLOG_POST' then ".floatval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_POST'])."
					when RV.ENTITY_TYPE_ID = 'BLOG_COMMENT' then ".floatval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_COMMENT'])."
				else 0 end) >= ".floatval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_RESULT'])."
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		
		// 3.INSERT NEW VOTING GROUP (FROM STEP 2)
		$strSql = "
			INSERT INTO b_rating_voting (ENTITY_TYPE_ID, ENTITY_ID, ACTIVE, CREATED, LAST_CALCULATED, TOTAL_VALUE, TOTAL_VOTES, TOTAL_POSITIVE_VOTES, TOTAL_NEGATIVE_VOTES, OWNER_ID)
			SELECT 
				RV.ENTITY_TYPE_ID, 
				RV.ENTITY_ID,
				'Y' as ACTIVE,
				".$DB->GetNowFunction()." as CREATED,
				".$DB->GetNowFunction()." as LAST_CALCULATED,  
				SUM(VALUE) as TOTAL_VALUE,
				SUM(1) as TOTAL_VOTES,
				SUM(case when RV.VALUE > '0' then 1 else 0 end) as TOTAL_POSITIVE_VOTES, 	
				SUM(case when RV.VALUE > '0' then 0 else 1 end) as TOTAL_NEGATIVE_VOTES, 		
				RV.ENTITY_ID as OWNER_ID
			FROM  
				b_rating_vote RV 
				LEFT JOIN b_rating_voting RVG ON RVG.ENTITY_TYPE_ID = RV.ENTITY_TYPE_ID AND RVG.ENTITY_ID = RV.ENTITY_ID
			WHERE 
				RATING_VOTING_ID = 0
			and RV.CREATED > DATE_SUB(NOW(), INTERVAL ".intval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT'])." DAY)
			and RVG.ID IS NULL and RV.OWNER_ID > 0
			GROUP BY RV.ENTITY_TYPE_ID, RV.ENTITY_ID
		";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		
		// 4 UPDATE FIELD RATING_VOTE_ID (FROM STEP 3)
		$strSql = "
			UPDATE
				b_rating_vote RV,
				b_rating_voting RVG
			SET
				RV.RATING_VOTING_ID = RVG.ID,
				RV.ACTIVE = 'Y'
			WHERE 
				RV.ENTITY_TYPE_ID = RVG.ENTITY_TYPE_ID
			and RV.ENTITY_ID = RVG.ENTITY_ID
			and RV.RATING_VOTING_ID = 0";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		
		// 5 INSERT TEMP TABLE VOTE RESULTS 
		$DB->Query("TRUNCATE b_rating_voting_prepare", false, $err_mess.__LINE__);
		$strSql = "
			INSERT INTO b_rating_voting_prepare (RATING_VOTING_ID, TOTAL_VALUE, TOTAL_VOTES, TOTAL_POSITIVE_VOTES, TOTAL_NEGATIVE_VOTES)
			SELECT 				
				RV.RATING_VOTING_ID,
				SUM(RV.VALUE) as TOTAL_VALUE,
				SUM(1) as TOTAL_VOTES,
				SUM(case when RV.VALUE > '0' then 1 else 0 end) as TOTAL_POSITIVE_VOTES, 	
				SUM(case when RV.VALUE > '0' then 0 else 1 end) as TOTAL_NEGATIVE_VOTES 		
			FROM  
				b_rating_vote RV 
			WHERE 
				RV.RATING_VOTING_ID IN (SELECT DISTINCT RV0.RATING_VOTING_ID FROM b_rating_vote RV0 WHERE RV0.ACTIVE='N')
			and RV.USER_ID > 0
			GROUP BY RV.RATING_VOTING_ID";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		
		// 6 UPDATE VOTE_RESULTS FROM TEMP TABLE
		$strSql = "
			UPDATE
				b_rating_voting RVG,
				b_rating_voting_prepare RVG0
			SET
				RVG.TOTAL_VALUE = RVG0.TOTAL_VALUE,
				RVG.TOTAL_VOTES = RVG0.TOTAL_VOTES,
				RVG.TOTAL_POSITIVE_VOTES = RVG0.TOTAL_POSITIVE_VOTES,
				RVG.TOTAL_NEGATIVE_VOTES = RVG0.TOTAL_NEGATIVE_VOTES
			WHERE 
				RVG.ID = RVG0.RATING_VOTING_ID";
		$DB->Query($strSql, false, $err_mess.__LINE__);	
		
		// 7 DELETE OLD POST
		$strSql = "DELETE FROM b_rating_vote WHERE ENTITY_TYPE_ID = 'USER' and CREATED < DATE_SUB(NOW(), INTERVAL ".intval($arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT'])." DAY)";
		$DB->Query($strSql, false, $err_mess.__LINE__);
		return true;
	}
}
?>