<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/ratings.php");
IncludeModuleLangFile(__FILE__);


/**
 * <b>CRatings</b> - класс для работы с рейтингами.
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/index.php
 * @author Bitrix
 */
class CRatings extends CAllRatings
{
	public static function err_mess()
	{
		return "<br>Class: CRatings<br>File: ".__FILE__;
	}

	// building rating on computed components
	public static function BuildRating($ID)
	{
		global $DB;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: BuildRating<br>Line: ";

		$resRating = CRatings::GetByID($ID);
		$arRating = $resRating->Fetch();
		if ($arRating && $arRating['ACTIVE'] == 'Y')
		{
			$DB->Query("UPDATE b_rating SET CALCULATED = 'C' WHERE id = ".$ID, false, $err_mess.__LINE__);

			// Insert new results
			$sqlFunc = ($arRating['CALCULATION_METHOD'] == 'SUM') ? 'SUM' : 'AVG';
			$strSql  = "
				INSERT INTO b_rating_results
					(RATING_ID, ENTITY_TYPE_ID, ENTITY_ID, CURRENT_VALUE, PREVIOUS_VALUE)
				SELECT
					".$ID." RATING_ID,
					'".$arRating['ENTITY_ID']."' ENTITY_TYPE_ID,
					RC.ENTITY_ID,
					".$sqlFunc."(RC.CURRENT_VALUE) CURRENT_VALUE,
					0 PREVIOUS_VALUE
				FROM
					b_rating_component_results RC LEFT JOIN b_rating_results RR ON RR.RATING_ID = RC.RATING_ID and RR.ENTITY_ID = RC.ENTITY_ID
				WHERE
					RC.RATING_ID = ".$ID." and RR.ID IS NULL
				GROUP BY RC.ENTITY_ID";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);

			// Update current results
			$strSql =  "
					UPDATE
						b_rating_results RR,
						(	SELECT '".$arRating['ENTITY_ID']."' ENTITY_TYPE_ID,	RC.ENTITY_ID, ".$sqlFunc."(RC.CURRENT_VALUE) CURRENT_VALUE
							FROM b_rating_component_results RC INNER JOIN b_rating_results RR on RR.RATING_ID = RC.RATING_ID and RR.ENTITY_ID = RC.ENTITY_ID
							WHERE RC.RATING_ID = ".$ID."
							GROUP BY RC.ENTITY_ID
						) as RCR
					SET
						RR.PREVIOUS_VALUE = IF(RR.CURRENT_VALUE = RCR.CURRENT_VALUE, RR.PREVIOUS_VALUE, RR.CURRENT_VALUE),
						RR.CURRENT_VALUE = RCR.CURRENT_VALUE
					WHERE
						RR.RATING_ID=".$ID."
					and	RR.ENTITY_TYPE_ID = RCR.ENTITY_TYPE_ID
					and	RR.ENTITY_ID = RCR.ENTITY_ID
					";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);

			// Calculation position in rating
			if ($arRating['POSITION'] == 'Y') {
				$strSql =  "
					UPDATE
						b_rating_results RR,
						(	SELECT ENTITY_TYPE_ID, ENTITY_ID, CURRENT_VALUE, @nPos:=@nPos+1  as POSITION
							FROM b_rating_results, (select @nPos:=0) tmp
							WHERE RATING_ID = ".$ID."
							ORDER BY CURRENT_VALUE DESC
						) as RP
					SET
						RR.PREVIOUS_POSITION = IF(RR.CURRENT_POSITION = RP.POSITION, RR.PREVIOUS_POSITION, RR.CURRENT_POSITION),
						RR.CURRENT_POSITION = RP.POSITION
					WHERE
						RR.RATING_ID=".$ID."
					and	RR.ENTITY_TYPE_ID = RP.ENTITY_TYPE_ID
					and	RR.ENTITY_ID = RP.ENTITY_ID
					";
				$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			}

			// Insert new user rating prop
			$strSql  = "
				INSERT INTO b_rating_user
					(RATING_ID, ENTITY_ID)
				SELECT
					".$ID." RATING_ID,
					U.ID as ENTITY_ID
				FROM
					b_user U LEFT JOIN b_rating_user RU ON RU.RATING_ID = ".$ID." and RU.ENTITY_ID = U.ID
				WHERE RU.ID IS NULL	";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			// authority calc
			if ($arRating['AUTHORITY'] == 'Y')
			{
				$sRatingAssignType = COption::GetOptionString("main", "rating_assign_type", "manual");
				if ($sRatingAssignType == 'auto')
				{
					// auto assign for rating group
					$assignRatingGroup = COption::GetOptionString("main", "rating_assign_rating_group", 0);
					$assignRatingValueAdd = COption::GetOptionString("main", "rating_assign_rating_group_add", 1);
					$assignRatingValueDelete = COption::GetOptionString("main", "rating_assign_rating_group_delete", 1);

					CRatings::AutoAssignGroup($assignRatingGroup, $assignRatingValueAdd, $assignRatingValueDelete);

					// auto assign for authority group
					$assignAuthorityGroup = COption::GetOptionString("main", "rating_assign_authority_group", 0);
					$assignAuthorityValueAdd = COption::GetOptionString("main", "rating_assign_authority_group_add", 2);
					$assignAuthorityValueDelete = COption::GetOptionString("main", "rating_assign_authority_group_delete", 2);

					CRatings::AutoAssignGroup($assignAuthorityGroup, $assignAuthorityValueAdd, $assignAuthorityValueDelete);
				}

				$sRatingWeightType = COption::GetOptionString("main", "rating_weight_type", "auto");
				if ($sRatingWeightType == 'auto')
				{
					$arCI = CRatings::GetCommunityInfo($ID);
					$communitySize = $arCI['COMMUNITY_SIZE'];
					$communityAuthority = $arCI['COMMUNITY_AUTHORITY'];

					$sRatingNormalizationType = COption::GetOptionString("main", "rating_normalization_type", "auto");
					if ($sRatingNormalizationType == 'manual')
						$ratingNormalization = COption::GetOptionString("main", "rating_normalization", 1000);
					else
					{
						if ($communitySize <= 10)
							$ratingNormalization = 10;
						else if ($communitySize > 10 && $communitySize <= 1000)
							$ratingNormalization = 100;
						else if ($communitySize > 1000)
							$ratingNormalization = 1000;
						COption::SetOptionString("main", "rating_normalization", $ratingNormalization);
					}

					$voteWeight = 1;
					if ($communitySize > 0)
						$voteWeight = $ratingNormalization/$communitySize;

					COption::SetOptionString("main", "rating_community_size", $communitySize);
					COption::SetOptionString("main", "rating_community_authority", $communityAuthority);
					COption::SetOptionString("main", "rating_vote_weight", $voteWeight);

					$ratingCountVote = COption::GetOptionString("main", "rating_count_vote", 10);
					$strSql =  "UPDATE b_rating_user SET VOTE_COUNT = 0, VOTE_WEIGHT =0 WHERE RATING_ID=".$ID;
					$res = $DB->Query($strSql, false, $err_mess.__LINE__);
					// default vote count + user authority
					$strSql =  "
						UPDATE
							b_rating_user RU,
							(	SELECT ENTITY_ID, CURRENT_VALUE
								FROM b_rating_results
								WHERE RATING_ID = ".$ID."
							) as RP
						SET
							RU.VOTE_COUNT = ".intval($ratingCountVote)."+RP.CURRENT_VALUE,
							RU.VOTE_WEIGHT = RP.CURRENT_VALUE*".$voteWeight."
						WHERE
							RU.RATING_ID=".$ID."
							and	RU.ENTITY_ID = RP.ENTITY_ID
					";
					$res = $DB->Query($strSql, false, $err_mess.__LINE__);
				}
				else
				{
					// Depending on current authority set correct weight votes
					// Depending on current authority set correct vote count
					$strSql =  "UPDATE b_rating_user SET VOTE_COUNT = 0, VOTE_WEIGHT =0 WHERE RATING_ID=".$ID;
					$res = $DB->Query($strSql, false, $err_mess.__LINE__);
					$strSql =  "
						UPDATE
							b_rating_user RU,
							(	SELECT
									RW.RATING_FROM, RW.RATING_TO, RW.WEIGHT, RW.COUNT, RR.ENTITY_ID
								FROM
									b_rating_weight RW,
									b_rating_results RR
								WHERE
									RR.RATING_ID = ".$ID."
								and RR.CURRENT_VALUE BETWEEN RW.RATING_FROM AND RW.RATING_TO
							) as RP
						SET
							RU.VOTE_COUNT = RP.COUNT,
							RU.VOTE_WEIGHT = RP.WEIGHT
						WHERE
							RU.RATING_ID=".$ID."
							and RU.ENTITY_ID = RP.ENTITY_ID
					";
					$res = $DB->Query($strSql, false, $err_mess.__LINE__);
				}
			}
			global $CACHE_MANAGER;
			$CACHE_MANAGER->CleanDir("b_rating_user");

			$DB->Query("UPDATE b_rating SET CALCULATED = 'Y', LAST_CALCULATED = ".$DB->GetNowFunction()." WHERE id = ".$ID, false, $err_mess.__LINE__);
		}
		return true;
	}

	public static function DeleteByUser($ID)
	{
		global $DB, $CACHE_MANAGER;

		$ID = intval($ID);
		$err_mess = (CRatings::err_mess())."<br>Function: DeleteByUser<br>Line: ";

		$strSql =  "
			UPDATE
				b_rating_voting RV,
				(
					SELECT
						RATING_VOTING_ID, SUM(case when USER_ID <> $ID then VALUE else '0' end) as TOTAL_VALUE,
						SUM(case when USER_ID <> $ID then '1' else '0' end) as TOTAL_VOTES,
						SUM(case when VALUE > 0 AND USER_ID <> $ID then '1' else '0' end) as TOTAL_POSITIVE_VOTES,
						SUM(case when VALUE < 0 AND USER_ID <> $ID then '1' else '0' end) as TOTAL_NEGATIVE_VOTES
					FROM b_rating_vote
					WHERE RATING_VOTING_ID IN (
						SELECT DISTINCT RV0.RATING_VOTING_ID FROM b_rating_vote RV0 WHERE RV0.USER_ID=$ID
					)
					GROUP BY RATING_VOTING_ID
				) as RP
			SET
				RV.TOTAL_VALUE = RP.TOTAL_VALUE,
				RV.TOTAL_VOTES = RP.TOTAL_VOTES,
				RV.TOTAL_POSITIVE_VOTES = RP.TOTAL_POSITIVE_VOTES,
				RV.TOTAL_NEGATIVE_VOTES = RP.TOTAL_NEGATIVE_VOTES
			WHERE
				RV.ID = RP.RATING_VOTING_ID
		";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		$DB->Query("DELETE FROM b_rating_vote WHERE USER_ID=$ID", false, $err_mess.__LINE__);
		$DB->Query("DELETE FROM b_rating_user WHERE ENTITY_ID=$ID", false, $err_mess.__LINE__);
		$CACHE_MANAGER->ClearByTag('RV_CACHE');

		return true;
	}

	// insert result calculate rating
	public static function AddResults($arResults)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: AddComponentResults<br>Line: ";

		// Only Mysql
		$strSqlPrefix = "
				INSERT INTO b_rating_results
				(RATING_ID, ENTITY_TYPE_ID, ENTITY_ID, CURRENT_VALUE, PREVIOUS_VALUE)
				VALUES
		";
		$maxValuesLen = 2048;
		$strSqlValues = "";

		foreach($arResults as $arResult)
		{
			$strSqlValues .= ",\n(".IntVal($arResult['RATING_ID']).", '".$DB->ForSql($arResult['ENTITY_TYPE_ID'])."', '".$DB->ForSql($arResult['ENTITY_ID'])."', '".$DB->ForSql($arResult['CURRENT_VALUE'])."', '".$DB->ForSql($arResult['PREVIOUS_VALUE'])."')";
			if(strlen($strSqlValues) > $maxValuesLen)
			{
				$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, $err_mess.__LINE__);
				$strSqlValues = "";
			}
		}
		if(strlen($strSqlValues) > 0)
		{
			$DB->Query($strSqlPrefix.substr($strSqlValues, 2), false, $err_mess.__LINE__);
			$strSqlValues = "";
		}

		return true;
	}

	// insert result calculate rating-components
	
	/**
	* <p>Обновляет дату следующего подсчета критерия рейтингования.  Нестатический метод.</p>
	*
	*
	* @param array $fields  Массив значений параметров. В качестве ключей данного массива
	* допустимо использовать: <ul> <li> <b>RATING_ID</b> – идентификатор
	* рейтинга</li>     <li> <b>COMPLEX_NAME</b> – комплексное имя критерия</li>     <li>
	* <b>REFRESH_INTERVAL</b> – периодичность перерасчета критерия (в минутах)</li>
	* </ul> Все поля являются обязательными.
	*
	* @return bool 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* &lt;?
	* // обновляем дату следующего расчета критерия рейтингования
	* $arConfigs = array(
	* 	"RATING_ID" =&gt; "4",
	* 	"COMPLEX_NAME" =&gt; "USER_FORUM_VOTE_TOPIC",
	* 	"REFRESH_INTERVAL" =&gt; 3600
	* );
	* CRatings::AddComponentResults($arConfigs);
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/main/reference/cratings/addcomponentresults.php
	* @author Bitrix
	*/
	public static function AddComponentResults($arComponentConfigs)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: AddComponentResults<br>Line: ";

		if (!is_array($arComponentConfigs))
			return false;

		$strSql  = "
			UPDATE b_rating_component
			SET LAST_CALCULATED = ".$DB->GetNowFunction().",
				NEXT_CALCULATION = '".date('Y-m-d H:i:s', time()+$arComponentConfigs['REFRESH_INTERVAL'])."'
			WHERE RATING_ID = ".IntVal($arComponentConfigs['RATING_ID'])." AND COMPLEX_NAME = '".$DB->ForSql($arComponentConfigs['COMPLEX_NAME'])."'";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function SetAuthorityRating($ratingId)
	{
		global $DB, $stackCacheManager;

		$err_mess = (CRatings::err_mess())."<br>Function: SetAuthorityRating<br>Line: ";

		$ratingId = intval($ratingId);

		$DB->Query("UPDATE b_rating SET AUTHORITY = IF(ID <> $ratingId, 'N', 'Y')", false, $err_mess.__LINE__);

		COption::SetOptionString("main", "rating_authority_rating", $ratingId);

		$stackCacheManager->Clear("b_rating");

		return true;
	}

	public static function GetCommunityInfo($ratingId)
	{
		global $DB;

		$bAllGroups = false;
		$arInfo = Array();
		$arGroups = Array();
		$communityLastVisit = COption::GetOptionString("main", "rating_community_last_visit", '90');
		$res = CRatings::GetVoteGroup();
		while ($arVoteGroup = $res->Fetch())
		{
			if ($arVoteGroup['GROUP_ID'] == 2)
			{
				$bAllGroups = true;
				break;
			}
			$arGroups[] = $arVoteGroup['GROUP_ID'];
		}

		$strModulesSql = '';
		if (IsModuleInstalled("forum"))
		{
			$strModulesSql .= "
					SELECT USER_START_ID as ENTITY_ID
					FROM b_forum_topic
					WHERE START_DATE > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
					GROUP BY USER_START_ID
				UNION ALL
					SELECT AUTHOR_ID as ENTITY_ID
					FROM b_forum_message
					WHERE POST_DATE > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
					GROUP BY AUTHOR_ID
				UNION ALL
			";
		}
		if (IsModuleInstalled("blog"))
		{
			$strModulesSql .= "
					SELECT	AUTHOR_ID as ENTITY_ID
					FROM b_blog_post
					WHERE DATE_PUBLISH > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
					GROUP BY AUTHOR_ID
				UNION ALL
					SELECT AUTHOR_ID as ENTITY_ID
					FROM b_blog_comment
					WHERE DATE_CREATE > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
					GROUP BY AUTHOR_ID
				UNION ALL";
		}
		if (IsModuleInstalled("intranet"))
		{
			$ratingId = COption::GetOptionString("main", "rating_authority_rating", 0);
			$strModulesSql .= "
					SELECT ENTITY_ID
					FROM b_rating_subordinate
					WHERE RATING_ID = $ratingId
				UNION ALL";
		}
		if (!empty($strModulesSql))
		{
			$strModulesSql = "
				(
					".$strModulesSql."
					SELECT USER_ID as ENTITY_ID
					FROM b_rating_vote
					WHERE CREATED > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
					GROUP BY USER_ID
				) MS,
			";
		}

		$DB->Query("TRUNCATE TABLE b_rating_prepare", false, $err_mess.__LINE__);
		if ($bAllGroups || empty($arGroups))
		{
			$strSql .= "
				INSERT INTO b_rating_prepare (ID)
				SELECT DISTINCT U.ID
				FROM ".$strModulesSql."
					b_user U
				WHERE ".(!empty($strModulesSql)? "U.ID = MS.ENTITY_ID AND": "")."
				U.ACTIVE = 'Y'
				AND U.LAST_LOGIN > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
			";
		}
		else
		{
			$strSql .= "
				INSERT INTO b_rating_prepare (ID)
				SELECT DISTINCT U.ID
				FROM ".$strModulesSql."
					b_user U
				WHERE ".(!empty($strModulesSql)? "U.ID = MS.ENTITY_ID AND": "")."
				U.ACTIVE = 'Y'
				AND U.LAST_LOGIN > DATE_SUB(NOW(), INTERVAL ".intval($communityLastVisit)." DAY)
			";
		}
		$DB->Query($strSql, false, $err_mess.__LINE__);

		$strSql = 'SELECT COUNT(*) as COMMUNITY_SIZE, SUM(CURRENT_VALUE) COMMUNITY_AUTHORITY
						FROM b_rating_results RC LEFT JOIN b_rating_prepare TT ON RC.ENTITY_ID = TT.ID
						WHERE RATING_ID = '.intval($ratingId).' AND TT.ID IS NOT NULL';
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res->Fetch();
	}

	public static function CheckAllowVote($arVoteParam)
	{
		global $USER;

		$userId = $USER->GetId();
		$bUserAuth = $USER->IsAuthorized();
		$bAllGroups = false;

		$arInfo = array(
			'RESULT' => true,
			'ERROR_TYPE' => '',
			'ERROR_MSG' => '',
		);

		$bSelfVote = COption::GetOptionString("main", "rating_self_vote", 'N');
		if ($bSelfVote == 'N' && IntVal($arVoteParam['OWNER_ID']) == $userId)
		{
			$arInfo = array(
				'RESULT' => false,
				'ERROR_TYPE' => 'SELF',
				'ERROR_MSG' => GetMessage('RATING_ALLOW_VOTE_SELF'),
			);
		}
		else if (!$bUserAuth)
		{
			$arInfo = array(
				'RESULT' => false,
				'ERROR_TYPE' => 'GUEST',
				'ERROR_MSG' => GetMessage('RATING_ALLOW_VOTE_GUEST'),
			);
		}
		else
		{
			static $cacheAllowVote = array();
			static $cacheUserVote = array();
			static $cacheVoteSize = 0;
			if(!array_key_exists($userId, $cacheAllowVote))
			{
				global $DB;
				$arGroups = array();
				$sVoteType = $arVoteParam['ENTITY_TYPE_ID'] == 'USER'? 'A': 'R';

				$userVoteGroup = Array();
				$ar = CRatings::GetVoteGroupEx();
				foreach($ar as $group)
					if ($sVoteType == $group['TYPE'])
						$userVoteGroup[] = $group['GROUP_ID'];

				$userGroup = $USER->GetUserGroupArray();

				$result = array_intersect($userGroup, $userVoteGroup);
				if (empty($result))
				{
					$arInfo = $cacheAllowVote[$userId] = array(
						'RESULT' => false,
						'ERROR_TYPE' => 'ACCESS',
						'ERROR_MSG' => GetMessage('RATING_ALLOW_VOTE_ACCESS'),
					);
				}

				$authorityRatingId	 = CRatings::GetAuthorityRating();
				$arAuthorityUserProp = CRatings::GetRatingUserPropEx($authorityRatingId, $userId);
				if ($arAuthorityUserProp['VOTE_WEIGHT'] <= 0)
				{
					$arInfo = $cacheAllowVote[$userId] = array(
						'RESULT' => false,
						'ERROR_TYPE' => 'ACCESS',
						'ERROR_MSG' => GetMessage('RATING_ALLOW_VOTE_LOW_WEIGHT'),
					);
				}

				if ($arInfo['RESULT'] && $sVoteType == 'A')
				{
					$strSql = '
						SELECT COUNT(*) as VOTE
						FROM b_rating_vote RV
						WHERE RV.USER_ID = '.$userId.'
						AND RV.CREATED > DATE_SUB(NOW(), INTERVAL 1 DAY)';
					$res = $DB->Query($strSql, false, $err_mess.__LINE__);
					$countVote = $res->Fetch();
					$cacheVoteSize = $_SESSION['RATING_VOTE_COUNT'] = $countVote['VOTE'];

					$cacheUserVote[$userId] = $_SESSION['RATING_USER_VOTE_COUNT'] = $arAuthorityUserProp['VOTE_COUNT'];
					if ($cacheVoteSize >= $cacheUserVote[$userId])
					{
						$arInfo = $cacheAllowVote[$userId] = array(
							'RESULT' => false,
							'ERROR_TYPE' => 'COUNT_VOTE',
							'ERROR_MSG' => GetMessage('RATING_ALLOW_VOTE_COUNT_VOTE'),
						);
					}
				}
			}
			else
			{
				if ($cacheAllowVote[$userId]['RESULT'])
				{
					if ($cacheVoteSize >= $cacheUserVote[$userId])
					{
						$arInfo = $cacheAllowVote[$userId] = array(
							'RESULT' => false,
							'ERROR_TYPE' => 'COUNT_VOTE',
							'ERROR_MSG' => GetMessage('RATING_ALLOW_VOTE_COUNT_VOTE'),
						);
					}
				}
				$arInfo = $cacheAllowVote[$userId];
			}
		}

		static $handlers;
		if (!isset($handlers))
			$handlers = GetModuleEvents("main", "OnAfterCheckAllowVote", true);

		foreach ($handlers as $arEvent)
		{
			$arEventResult = ExecuteModuleEventEx($arEvent, array($arVoteParam));
			if (is_array($arEventResult) && isset($arEventResult['RESULT']) && $arEventResult['RESULT'] === false
				&& isset($arEventResult['ERROR_TYPE']) && strlen($arEventResult['ERROR_MSG']) > 0
				&& isset($arEventResult['ERROR_MSG']) && strlen($arEventResult['ERROR_MSG']) > 0)
			{
				$arInfo = array(
					'RESULT' => false,
					'ERROR_TYPE' => $arEventResult['ERROR_TYPE'],
					'ERROR_MSG' => $arEventResult['ERROR_MSG'],
				);
			}
		}
		return $arInfo;
	}

	public static function SetAuthorityDefaultValue($arParams)
	{
		global $DB;

		$rsRatings = CRatings::GetList(array('ID' => 'ASC'), array('ENTITY_ID' => 'USER'));
		while ($arRatingsTmp = $rsRatings->GetNext())
			$arRatingList[] = $arRatingsTmp['ID'];

		if (isset($arParams['DEFAULT_USER_ACTIVE']) && $arParams['DEFAULT_USER_ACTIVE'] == 'Y' && IsModuleInstalled("forum") && is_array($arRatingList) && !empty($arRatingList))
		{
			$ratingStartValue = 0;
			if (isset($arParams['DEFAULT_CONFIG_NEW_USER']) && $arParams['DEFAULT_CONFIG_NEW_USER'] == 'Y')
				$ratingStartValue = COption::GetOptionString("main", "rating_start_authority", 3);

			$strSql =  "UPDATE b_rating_user SET BONUS = $ratingStartValue WHERE RATING_ID IN (".implode(',', $arRatingList).")";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			$strSql =  "
				UPDATE
					b_rating_user RU,
					(	SELECT
							TO_USER_ID as ENTITY_ID, COUNT(*) as CNT
						FROM
							b_forum_user_points FUP
						GROUP BY TO_USER_ID
					) as RP
				SET
					RU.BONUS = ".$DB->IsNull('RP.CNT', '0')."+".$ratingStartValue."
				WHERE
					RU.RATING_ID IN (".implode(',', $arRatingList).")
				and	RU.ENTITY_ID = RP.ENTITY_ID
			";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		}
		else if (isset($arParams['DEFAULT_CONFIG_NEW_USER']) && $arParams['DEFAULT_CONFIG_NEW_USER'] == 'Y' && is_array($arRatingList) && !empty($arRatingList))
		{
			$ratingStartValue = COption::GetOptionString("main", "rating_start_authority", 3);
			$strSql =  "UPDATE b_rating_user SET BONUS = ".$ratingStartValue." WHERE RATING_ID IN (".implode(',', $arRatingList).")";
			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		}

		return true;
	}

	public static function AutoAssignGroup($groupId, $authorityValueAdd, $authorityValueDelete)
	{
		global $DB;
		$err_mess = (CRatings::err_mess())."<br>Function: AutoAssignGroup<br>Line: ";

		$groupId = IntVal($groupId);
		if ($groupId == 0)
			return false;

		$ratingId = CRatings::GetAuthorityRating();
		$ratingValueAdd = IntVal($authorityValueAdd);
		$ratingValueDelete = IntVal($authorityValueDelete);
		$sRatingWeightType = COption::GetOptionString("main", "rating_weight_type", "auto");
		if ($sRatingWeightType == 'auto') {
			$ratingValueAdd = $ratingValueAdd*COption::GetOptionString("main", "rating_vote_weight", 1);
			$ratingValueDelete = $ratingValueDelete*COption::GetOptionString("main", "rating_vote_weight", 1);
		}
		// remove the group from all users who it is, but you need to remove it
		$strSql = "
			DELETE ug
			FROM b_user_group ug
			INNER JOIN (
				SELECT
					rr.ENTITY_ID as USER_ID
				FROM
					b_rating_results rr
				WHERE
					rr.RATING_ID = $ratingId
				AND rr.CURRENT_VALUE < $ratingValueDelete
			) R ON
			ug.USER_ID = R.USER_ID AND ug.GROUP_ID = $groupId";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		// add a group to all users who do not, but you need to add it
		$strSql = "
			INSERT INTO b_user_group (USER_ID, GROUP_ID)
			SELECT
				rr.ENTITY_ID, '$groupId'
			FROM
				b_rating_results rr
				LEFT JOIN b_user_group ug ON ug.GROUP_ID = $groupId AND ug.USER_ID = rr.ENTITY_ID
			WHERE
				rr.RATING_ID = $ratingId
			and rr.CURRENT_VALUE >= $ratingValueAdd
			and ug.USER_ID IS NULL";
		$DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function GetRatingVoteListSQL($arParam, $bplus, $bIntranetInstalled)
	{
		global $DB, $USER;

		return "
			SELECT
				U.ID,
				U.NAME,
				U.LAST_NAME,
				U.SECOND_NAME,
				U.LOGIN,
				U.PERSONAL_PHOTO,
				RV.VALUE AS VOTE_VALUE,
				RV.USER_ID,
				SUM(case when RV0.ID is not null then 1 else 0 end) RANK
			FROM
				b_rating_vote RV LEFT JOIN b_rating_vote RV0 ON RV0.USER_ID = ".intval($USER->GetId())." and RV0.OWNER_ID = RV.USER_ID,
				b_user U
			WHERE
				RV.ENTITY_TYPE_ID = '".$DB->ForSql($arParam['ENTITY_TYPE_ID'])."'
				and RV.ENTITY_ID =  ".intval($arParam['ENTITY_ID'])."
				and RV.USER_ID = U.ID
				".($bplus? " and RV.VALUE > 0 ": " and RV.VALUE < 0 ")."
			GROUP BY RV.USER_ID
			ORDER BY ".($bIntranetInstalled? "RV.VALUE DESC, RANK DESC, RV.ID DESC": "RANK DESC, RV.VALUE DESC, RV.ID DESC");
	}

	public static function GetRatingVoteListSQLExtended($arParam, $bplus, $bIntranetInstalled)
	{
		global $DB, $USER;

		return "
			SELECT
				U.ID,
				RV.VALUE AS VOTE_VALUE,
				RV.USER_ID,
				SUM(case when RV0.ID is not null then 1 else 0 end) RANK
			FROM
				b_rating_vote RV LEFT JOIN b_rating_vote RV0 ON RV0.USER_ID = ".intval($USER->GetId())." and RV0.OWNER_ID = RV.USER_ID,
				b_user U
			WHERE
				RV.ENTITY_TYPE_ID = '".$DB->ForSql($arParam['ENTITY_TYPE_ID'])."'
				and RV.ENTITY_ID =  ".intval($arParam['ENTITY_ID'])."
				and RV.USER_ID = U.ID
				".($bplus? " and RV.VALUE > 0 ": " and RV.VALUE < 0 ")."
			GROUP BY RV.USER_ID
			ORDER BY ".($bIntranetInstalled? "RV.VALUE DESC, RANK DESC, RV.ID DESC": "RANK DESC, RV.VALUE DESC, RV.ID DESC");
	}
}
