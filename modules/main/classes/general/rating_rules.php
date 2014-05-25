<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/rating_rules.php");

class CAllRatingRulesMain
{
	// return configs
	public static function OnGetRatingRuleConfigs()
	{
		$arConfigs["USER"]["CONDITION_CONFIG"][] = array(
		    "ID"	=> 'RATING',
			"NAME" => GetMessage('PP_USER_CONDITION_RATING_NAME'),
			"DESC" => GetMessage('PP_USER_CONDITION_RATING_DESC'),
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingRulesMain',
			"METHOD"	=> 'ratingCheck',
		    "FIELDS" => array(
				array(
					"TYPE" => 'SELECT_CLASS',
					"ID" => 'RATING_ID',
					"NAME" => GetMessage('PP_USER_CONDITION_RATING_RATING_ID'),
					"DEFAULT" => '1',
					"CLASS" 	=> 'CRatings',
					"METHOD"	=> 'GetList',
					"PARAMS"    => array(array("ID" => "ASC"), array("ACTIVE" => "Y", "ENTITY_ID" => "USER")),
					"FIELD_ID"	=> 'ID',
					"FIELD_VALUE"	=> 'NAME',
				),
				array(
					"TYPE" => 'SELECT_ARRAY_WITH_INPUT',
					"ID" => 'RATING_CONDITION',
					"ID_INPUT" => 'RATING_VALUE',
					"NAME" => GetMessage('PP_USER_CONDITION_RATING_RATING_CONDITION'),
					"DEFAULT" => '1',
					"DEFAULT_INPUT" => '500',
					"PARAMS" => array('1' => GetMessage('PP_USER_CONDITION_RATING_RATING_CONDITION_1'),
									  '2' => GetMessage('PP_USER_CONDITION_RATING_RATING_CONDITION_2')),
				),
			)
		);

		$arConfigs["USER"]["CONDITION_CONFIG"][] = array(
		    "ID"	=> 'RATING_INTERVAL',
			"NAME" => GetMessage('PP_USER_CONDITION_RATING_INTERVAL_NAME'),
			"DESC" => GetMessage('PP_USER_CONDITION_RATING_INTERVAL_DESC'),
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingRulesMain',
			"METHOD"	=> 'ratingCheckInterval',
		    "FIELDS" => array(
				array(
					"TYPE" => 'SELECT_CLASS',
					"ID" => 'RATING_ID',
					"NAME" => GetMessage('PP_USER_CONDITION_RATING_RATING_ID'),
					"DEFAULT" => '1',
					"CLASS" 	=> 'CRatings',
					"METHOD"	=> 'GetList',
					"PARAMS"    => array(array("ID" => "ASC"), array("ACTIVE" => "Y", "ENTITY_ID" => "USER")),
					"FIELD_ID"	=> 'ID',
					"FIELD_VALUE"	=> 'NAME',
				),
				array(
					"TYPE" => 'INPUT_INTERVAL',
					"ID" => 'RATING_VALUE_FROM',
					"ID_2" => 'RATING_VALUE_TO',
					"NAME" => GetMessage('PP_USER_CONDITION_RATING_INTERVAL'),
					"DEFAULT" => '0',
					"DEFAULT_2" => '500',
				),
			)
		);
		
		$arConfigs["USER"]["CONDITION_CONFIG"][] = array(
		    "ID"	=> 'AUTHORITY',
			"NAME" => GetMessage('PP_USER_CONDITION_AUTHORITY_NAME'),
			"DESC" => (COption::GetOptionString("main", "rating_weight_type", "auto") == "auto"? GetMessage('PP_USER_CONDITION_AUTHORITY_AUTO_DESC') : GetMessage('PP_USER_CONDITION_AUTHORITY_DESC')),
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingRulesMain',
			"METHOD"	=> 'ratingCheck',
		    "FIELDS" => array(
				array(
					"TYPE" => 'SELECT_ARRAY_WITH_INPUT',
					"ID" => 'RATING_CONDITION',
					"ID_INPUT" => 'RATING_VALUE',
					"NAME" => (COption::GetOptionString("main", "rating_weight_type", "auto") == "auto"? GetMessage('PP_USER_CONDITION_AUTHORITY_RATING_CONDITION_AUTO') : GetMessage('PP_USER_CONDITION_AUTHORITY_RATING_CONDITION')),
					"DEFAULT" => '1',
					"DEFAULT_INPUT" => '1',
					"PARAMS" => array('1' => GetMessage('PP_USER_CONDITION_RATING_RATING_CONDITION_1'),
									  '2' => GetMessage('PP_USER_CONDITION_RATING_RATING_CONDITION_2')),
				),
			)
		);

		$arConfigs["USER"]["CONDITION_CONFIG"][] = array(
		    "ID"	=> 'AUTHORITY_INTERVAL',
			"NAME" => GetMessage('PP_USER_CONDITION_AUTHORITY_INTERVAL_NAME'),
			"DESC" => (COption::GetOptionString("main", "rating_weight_type", "auto") == "auto"? GetMessage('PP_USER_CONDITION_AUTHORITY_INTERVAL_AUTO_DESC') : GetMessage('PP_USER_CONDITION_AUTHORITY_INTERVAL_DESC')),
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingRulesMain',
			"METHOD"	=> 'ratingCheckInterval',
		    "FIELDS" => array(
				array(
					"TYPE" => 'INPUT_INTERVAL',
					"ID" => 'RATING_VALUE_FROM',
					"ID_2" => 'RATING_VALUE_TO',
					"NAME" => (COption::GetOptionString("main", "rating_weight_type", "auto") == "auto"? GetMessage('PP_USER_CONDITION_AUTHORITY_INTERVAL_AUTO') : GetMessage('PP_USER_CONDITION_AUTHORITY_INTERVAL')),
					"DEFAULT" => '0',
					"DEFAULT_2" => '10',
				),
			)
		);
		
		$arConfigs["USER"]["CONDITION_CONFIG"][] = array(
		   "ID"	=> 'VOTE',
			"NAME" => GetMessage('PP_USER_CONDITION_VOTE_NAME'),
			"DESC" => '',
			"REFRESH_TIME"	=> '86400',
			"CLASS"	=> 'CRatingRulesMain',
			"METHOD"	=> 'voteCheck',
		   "FIELDS" => array(
				array(
					"TYPE" => 'TEXT',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_TEXT')
				),	
				array(
					"TYPE" => 'INPUT',
					"ID" => 'VOTE_LIMIT',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_LIMIT'),
					"NAME_DESC" => GetMessage('PP_USER_CONDITION_VOTE_LIMIT_DESC'),
					"DEFAULT" => '90',
					"SIZE" => '2'
				),	
				array(
					"TYPE" => 'INPUT',
					"ID" => 'VOTE_RESULT',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_RESULT'),
					"DEFAULT" => '10',
					"SIZE" => '2'
				),	
				array(
					"TYPE" => 'SEPARATOR',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_SEPARATOR'),
				),	
				array(
					"TYPE" => 'INPUT',
					"ID" => 'VOTE_FORUM_TOPIC',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_FT'),
					"DEFAULT" => '0.5',
					"SIZE" => '2'
				),	
				array(
					"TYPE" => 'INPUT',
					"ID" => 'VOTE_FORUM_POST',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_FP'),
					"DEFAULT" => '0.1',
					"SIZE" => '2'
				),	
				array(
					"TYPE" => 'INPUT',
					"ID" => 'VOTE_BLOG_POST',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_BP'),
					"DEFAULT" => '0.5',
					"SIZE" => '2'
				),	
				array(
					"TYPE" => 'INPUT',
					"ID" => 'VOTE_BLOG_COMMENT',
					"NAME" => GetMessage('PP_USER_CONDITION_VOTE_BC'),
					"DEFAULT" => '0.1',
					"SIZE" => '2'
				),	
			),
			'HIDE_ACTION' => true
		);
	
		$arConfigs["USER"]["ACTION_CONFIG"][] = array(
		    "ID"	=> 'ADD_TO_GROUP',
			"NAME" => GetMessage('PP_USER_ACTION_ADD_TO_GROUP'),
			"DESC" => GetMessage('PP_USER_ACTION_ADD_TO_GROUP_DESC'),
			"CLASS" => 'CRatingRulesMain',
			"METHOD"	=> 'addToGroup',
		    "FIELDS" => array(
				array(
					"ID" => 'GROUP_ID',
					"NAME" => GetMessage('PP_USER_ACTION_CHANGE_GROUP_GROUP_ID'),
					"DEFAULT" => '4',
					"TYPE" => 'SELECT_CLASS',
					"CLASS" 	=> 'CGroup',
					"METHOD"	=> 'GetList',
					"PARAMS"    => array('ID', 'DESC', array()),
					"FIELD_ID"	=> 'ID',
					"FIELD_VALUE"	=> 'NAME',
				),

			)
		);

		$arConfigs["USER"]["ACTION_CONFIG"][] = array(
		    "ID"	=> 'REMOVE_FROM_GROUP',
			"NAME" => GetMessage('PP_USER_ACTION_REMOVE_FROM_GROUP'),
			"DESC" => GetMessage('PP_USER_ACTION_REMOVE_FROM_GROUP_DESC'),
			"CLASS" => 'CRatingRulesMain',
			"METHOD"	=> 'removeFromGroup',
		    "FIELDS" => array(
				array(
					"ID" => 'GROUP_ID',
					"NAME" => GetMessage('PP_USER_ACTION_CHANGE_GROUP_GROUP_ID'),
					"DEFAULT" => '4',
					"TYPE" => 'SELECT_CLASS',
					"CLASS" 	=> 'CGroup',
					"METHOD"	=> 'GetList',
					"PARAMS"    => array('ID', 'ASC', array()),
					"FIELD_ID"	=> 'ID',
					"FIELD_VALUE"	=> 'NAME',
				),
			)
		);

		$arConfigs["USER"]["ACTION_CONFIG"][] = array(
		    "ID"	=> 'CHANGE_UF',
			"NAME" => GetMessage('PP_USER_ACTION_CHANGE_UF'),
			"DESC" => GetMessage('PP_USER_ACTION_CHANGE_UF_DESC'),
			"CLASS" => 'CRatingRulesMain',
			"METHOD"	=> 'changeUF',
		    "FIELDS" => array(
				array(
					"ID" => 'UF_ID',
					"NAME" => GetMessage('PP_USER_ACTION_CHANGE_UF_ID'),
					"DEFAULT" => '',
					"TYPE" => 'SELECT_CLASS_ARRAY',
					"CLASS" 	=> 'CRatingRulesMain',
					"METHOD"	=> 'GetUfList',
					"PARAMS"    => array(),
					"FIELD_ID"	=> 'ID',
					"FIELD_VALUE"	=> 'NAME',
				),
				array(
					"ID" => 'UF_VALUE',
					"NAME" => GetMessage('PP_USER_ACTION_CHANGE_UF_VALUE'),
					"DEFAULT" => '',
				),
			)
		);	
		return $arConfigs;
	}

	public static function ratingCheck($arConfigs)
	{
		global $DB;
		$err_mess = "File: ".__FILE__."<br>Function: ratingCheck<br>Line: ";

		$ruleId = IntVal($arConfigs['ID']);
		if (isset($arConfigs['CONDITION_CONFIG']['RATING']))
		{
			$ratingValue = IntVal($arConfigs['CONDITION_CONFIG']['RATING']['RATING_VALUE']);
			$ratingCondition = ($arConfigs['CONDITION_CONFIG']['RATING']['RATING_CONDITION'] == 1 ? '>=' : '<');
			$ratingId = IntVal($arConfigs['CONDITION_CONFIG']['RATING']['RATING_ID']);
		}
		else
		{
			$ratingVoteWeight = COption::GetOptionString("main", "rating_vote_weight", 1);
			$ratingValue = IntVal($arConfigs['CONDITION_CONFIG']['AUTHORITY']['RATING_VALUE'])*$ratingVoteWeight;
			$ratingCondition = ($arConfigs['CONDITION_CONFIG']['AUTHORITY']['RATING_CONDITION'] == 1 ? '>=' : '<');
			$ratingId = CRatings::GetAuthorityRating();
		}

		$strSql = "INSERT INTO b_rating_rule_vetting (RULE_ID, ENTITY_TYPE_ID, ENTITY_ID)
					SELECT
						'$ruleId' as RULE_ID,
						rr.ENTITY_TYPE_ID as ENTITY_TYPE_ID,
						rr.ENTITY_ID as ENTITY_ID
					FROM b_rating_results rr
					WHERE rr.RATING_ID = $ratingId
					  AND rr.CURRENT_VALUE $ratingCondition $ratingValue";

		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function ratingCheckInterval($arConfigs)
	{
		global $DB;
		$err_mess = "File: ".__FILE__."<br>Function: ratingCheckInterval<br>Line: ";

		$ruleId = IntVal($arConfigs['ID']);	
		if (isset($arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']))
		{
			$ratingValueFrom = IntVal($arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_FROM']);
			$ratingValueTo = IntVal($arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_TO']);
			$ratingId = IntVal($arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_ID']);
		}
		else
		{
			$ratingVoteWeight = COption::GetOptionString("main", "rating_vote_weight", 1);
			$ratingValueFrom = IntVal($arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_FROM'])*$ratingVoteWeight;
			$ratingValueTo = IntVal($arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_TO'])*$ratingVoteWeight;
			$ratingId = CRatings::GetAuthorityRating();
		}	
			
			
		$strSql = "INSERT INTO b_rating_rule_vetting (RULE_ID, ENTITY_TYPE_ID, ENTITY_ID)
					SELECT
						'$ruleId' as RULE_ID,
						rr.ENTITY_TYPE_ID as ENTITY_TYPE_ID,
						rr.ENTITY_ID as ENTITY_ID
					FROM b_rating_results rr
					WHERE rr.RATING_ID = $ratingId
					  AND rr.CURRENT_VALUE BETWEEN $ratingValueFrom AND $ratingValueTo";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return true;
	}

	public static function addToGroup($arConfigs)
	{
		global $DB;
		$err_mess = "File: ".__FILE__."<br>Function: addToGroup<br>Line: ";

		$ruleId = IntVal(IntVal($arConfigs['ID']));
		$groupId = IntVal($arConfigs['ACTION_CONFIG']['ADD_TO_GROUP']['GROUP_ID']);
		$entityTypeId = $DB->ForSql($arConfigs['ENTITY_TYPE_ID']);

		// add a group to all users who do not, but you need to add it
		$strSql = "INSERT INTO b_user_group (USER_ID, GROUP_ID)
					SELECT prv.ENTITY_ID, '$groupId' as GROUP_ID
					FROM b_rating_rule_vetting prv
					WHERE
						prv.RULE_ID = $ruleId
					AND prv.ENTITY_TYPE_ID = '$entityTypeId'
					AND prv.ENTITY_ID NOT IN (
					   SELECT ug.USER_ID FROM b_user_group ug WHERE ug.GROUP_ID = $groupId
					)
					AND prv.APPLIED = 'N'
					GROUP BY prv.ENTITY_ID";

		$DB->Query($strSql, false, $err_mess.__LINE__);

		CRatingRule::ApplyVetting($arConfigs);

		return true;
	}

	public static function removeFromGroup($arConfigs)
	{
		global $DB;
		$err_mess = "File: ".__FILE__."<br>Function: addToGroup<br>Line: ";

		$ruleId = IntVal(IntVal($arConfigs['ID']));
		$groupId = IntVal($arConfigs['ACTION_CONFIG']['REMOVE_FROM_GROUP']['GROUP_ID']);
		$entityTypeId = $DB->ForSql($arConfigs['ENTITY_TYPE_ID']);

		// remove the group from all users who it is, but you need to remove it
		$strSql = "SELECT prv.ENTITY_ID
					FROM b_rating_rule_vetting prv
					WHERE
						prv.RULE_ID = $ruleId
					and prv.ENTITY_TYPE_ID = '$entityTypeId'
					and prv.ENTITY_ID IN (
					   SELECT ug.USER_ID FROM b_user_group ug WHERE ug.GROUP_ID = $groupId
					)
					and prv.APPLIED = 'N'";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$arDelete = array();
		while($row = $res->Fetch())
			$arDelete[] = $row['ENTITY_ID'];
		if (!empty($arDelete))
		{
			$strSql = "DELETE FROM b_user_group WHERE GROUP_ID = $groupId and USER_ID IN (".implode(',', $arDelete).")";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}

		CRatingRule::ApplyVetting($arConfigs);

		return true;
	}

	public static function GetUfList()
	{
		$arFields = array();
		$rsData = CUserTypeEntity::GetList(array(), array('ENTITY_ID' => 'USER', 'LANG' => LANG));
		while($arRes = $rsData->Fetch())
		{
			if ($arRes['MULTIPLE'] == 'N' && in_array($arRes['USER_TYPE_ID'], array('integer', 'string_formatted', 'string', 'double')))
				$arFields[$arRes['FIELD_NAME']] = empty($arRes['LIST_FILTER_LABEL']) ? $arRes['FIELD_NAME'] : $arRes['LIST_FILTER_LABEL'].' ('.$arRes['FIELD_NAME'].')';
		}
		return $arFields;

	}

	public static function changeUF($arConfigs)
	{
		global $DB;
		$err_mess = "File: ".__FILE__."<br>Function: changeUF<br>Line: ";

		$ruleId = IntVal(IntVal($arConfigs['ID']));
		$entityTypeId = $DB->ForSql($arConfigs['ENTITY_TYPE_ID']);
		$userFieldId = $DB->ForSql($arConfigs['ACTION_CONFIG']['CHANGE_UF']['UF_ID']);
		$userFieldValue = $DB->ForSql($arConfigs['ACTION_CONFIG']['CHANGE_UF']['UF_VALUE']);
		if (!empty($userFieldId))
		{
			$strSql = "UPDATE b_uts_user uts SET uts.$userFieldId = '$userFieldValue'
						WHERE uts.VALUE_ID IN (
							SELECT prv.ENTITY_ID
							FROM b_rating_rule_vetting prv
							WHERE
								prv.RULE_ID = $ruleId
							AND prv.ENTITY_TYPE_ID = '$entityTypeId'
							AND prv.APPLIED = 'N'
						)";
			$DB->Query($strSql, false, $err_mess.__LINE__);

			$strSql = "INSERT INTO b_uts_user (VALUE_ID, $userFieldId)
						SELECT prv.ENTITY_ID, '$userFieldValue' as UF_VALUE
						FROM b_rating_rule_vetting prv
						WHERE
							prv.RULE_ID = $ruleId
						and prv.ENTITY_TYPE_ID = '$entityTypeId'
						and prv.ENTITY_ID NOT IN (
							SELECT uf.VALUE_ID FROM b_uts_user uf
						)
						and prv.APPLIED = 'N'
						GROUP BY ENTITY_ID
						";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}

		CRatingRule::ApplyVetting($arConfigs);

		return true;
	}

	// return support object
	public static function OnGetRatingRuleObjects()
	{
		$arRatingRulesConfigs = CRatingRulesMain::OnGetRatingRuleConfigs();
		foreach ($arRatingRulesConfigs as $SupportType => $value)
			$arSupportType[] = $SupportType;

		return $arSupportType;
	}

	// check the value which relate to the module
	public static function OnAfterAddRatingRule($ID, $arFields)
	{
		$arFields = CRatingRulesMain::__CheckFields($arFields['ENTITY_TYPE_ID'], $arFields);

		return $arFields;
	}

	// check the value which relate to the module
	public static function OnAfterUpdateRatingRule($ID, $arFields)
	{
		$arFields = CRatingRulesMain::__CheckFields($arFields['ENTITY_TYPE_ID'], $arFields);

		return $arFields;
	}

	// check input values, if value does not validate, set the default value
	public static function __CheckFields($entityId, $arConfigs)
	{
		$arDefaultConfig = CRatingRulesMain::__AssembleConfigDefault($entityId);

		if ($entityId == "USER") {
			if (isset($arConfigs['CONDITION_CONFIG']['RATING']))
			{
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['RATING']['RATING_ID']))
					$arConfigs['CONDITION_CONFIG']['RATING']['RATING_ID'] = $arDefaultConfig['CONDITION_CONFIG']['RATING']['RATING_ID']['DEFAULT'];
				if (!in_array($arConfigs['CONDITION_CONFIG']['RATING']['RATING_CONDITION'], array(1,2)))
					$arConfigs['CONDITION_CONFIG']['RATING']['RATING_CONDITION'] = $arDefaultConfig['CONDITION_CONFIG']['RATING']['RATING_CONDITION']['DEFAULT'];
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['RATING']['RATING_VALUE']))
					$arConfigs['CONDITION_CONFIG']['RATING']['RATING_VALUE'] = $arDefaultConfig['CONDITION_CONFIG']['RATING']['RATING_CONDITION']['DEFAULT_INPUT'];
			}
			if (isset($arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']))
			{
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_ID']))
					$arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_ID'] = $arDefaultConfig['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_ID']['DEFAULT'];
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_FROM']))
					$arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_FROM'] = $arDefaultConfig['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_FROM']['DEFAULT'];
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_TO']))
					$arConfigs['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_TO'] = $arDefaultConfig['CONDITION_CONFIG']['RATING_INTERVAL']['RATING_VALUE_FROM']['DEFAULT_2'];
			}
			if (isset($arConfigs['CONDITION_CONFIG']['AUTHORITY']))
			{
				if (!in_array($arConfigs['CONDITION_CONFIG']['AUTHORITY']['RATING_CONDITION'], array(1,2)))
					$arConfigs['CONDITION_CONFIG']['AUTHORITY']['RATING_CONDITION'] = $arDefaultConfig['CONDITION_CONFIG']['AUTHORITY']['RATING_CONDITION']['DEFAULT'];
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['AUTHORITY']['RATING_VALUE']))
					$arConfigs['CONDITION_CONFIG']['AUTHORITY']['RATING_VALUE'] = $arDefaultConfig['CONDITION_CONFIG']['AUTHORITY']['RATING_CONDITION']['DEFAULT_INPUT'];
			}
			if (isset($arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']))
			{
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_FROM']))
					$arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_FROM'] = $arDefaultConfig['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_FROM']['DEFAULT'];
				if (!preg_match('/^\d{1,11}$/', $arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_TO']))
					$arConfigs['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_TO'] = $arDefaultConfig['CONDITION_CONFIG']['AUTHORITY_INTERVAL']['RATING_VALUE_FROM']['DEFAULT_2'];
			}
			if (isset($arConfigs['CONDITION_CONFIG']['VOTE']))
			{
				if (!preg_match('/^\d{1,3}$/', $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT']))
					$arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT'] = $arDefaultConfig['CONDITION_CONFIG']['VOTE']['VOTE_LIMIT']['DEFAULT'];
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_RESULT']) || $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_RESULT'] < 0)
					$arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_RESULT'] = $arDefaultConfig['CONDITION_CONFIG']['VOTE']['VOTE_RESULT']['DEFAULT'];				
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_TOPIC']))
					$arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_TOPIC'] = $arDefaultConfig['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_TOPIC']['DEFAULT'];
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_POST']))
					$arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_POST'] = $arDefaultConfig['CONDITION_CONFIG']['VOTE']['VOTE_FORUM_POST']['DEFAULT'];
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_POST']))
					$arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_POST'] = $arDefaultConfig['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_POST']['DEFAULT'];
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_COMMENT']))
					$arConfigs['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_COMMENT'] = $arDefaultConfig['CONDITION_CONFIG']['VOTE']['VOTE_BLOG_COMMENT']['DEFAULT'];
			}
			if (isset($arConfigs['ACTION_CONFIG']['CHANGE_GROUP']))
			{
				if (!preg_match('/^\d{1,11}$/', $arConfigs['ACTION_CONFIG']['CHANGE_GROUP']['GROUP_ID']))
					$arConfigs['ACTION_CONFIG']['CHANGE_GROUP']['GROUP_ID'] = $arDefaultConfig['ACTION_CONFIG']['CHANGE_GROUP']['GROUP_ID']['DEFAULT'];
			}

			if (isset($arConfigs['ACTION_CONFIG']['CHANGE_UF']))
			{
				if (!preg_match('/^[0-9A-Z_]+$/', $arConfigs['ACTION_CONFIG']['CHANGE_UF']['UF_ID']))
					$arConfigs['ACTION_CONFIG']['CHANGE_UF']['UF_ID'] = $arDefaultConfig['ACTION_CONFIG']['CHANGE_UF']['UF_ID']['DEFAULT'];
			}
		}

		return $arConfigs;
	}

	// assemble config default value
	public static function __AssembleConfigDefault($objectType = null)
	{
		$arConfigs = array();
		$arRatingRuleConfigs = CRatingRulesMain::OnGetRatingRuleConfigs();
		if (is_null($objectType))
		{
			foreach ($arRatingRuleConfigs as $OBJ_TYPE => $TYPE_VALUE)
				foreach ($TYPE_VALUE as $RULE_TYPE => $RULE_VALUE)
					foreach ($RULE_VALUE as $VALUE_CONFIG)
				   		foreach ($VALUE_CONFIG['FIELDS'] as $VALUE_FIELDS)
							{
								$arConfigs[$OBJ_TYPE][$RULE_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT'] = $VALUE_FIELDS['DEFAULT'];
								if (isset($arConfigs[$OBJ_TYPE][$RULE_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT_INPUT']))
									$arConfigs[$OBJ_TYPE][$RULE_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT_INPUT'] = $VALUE_FIELDS['DEFAULT_INPUT'];
							 }
		}
		else
		{
			foreach ($arRatingRuleConfigs[$objectType] as $RULE_TYPE => $RULE_VALUE)
				foreach ($RULE_VALUE as $VALUE_CONFIG)
					foreach ($VALUE_CONFIG['FIELDS'] as $VALUE_FIELDS)
					{
				   		$arConfigs[$RULE_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT'] = $VALUE_FIELDS['DEFAULT'];
						if (isset($arConfigs[$RULE_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT_INPUT']))
							$arConfigs[$RULE_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT_INPUT'] = $VALUE_FIELDS['DEFAULT_INPUT'];	
					}
		}

		return $arConfigs;
	}
}

?>