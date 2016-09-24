<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/forum/classes/general/ratings_components.php");

class CAllRatingsComponentsForum
{
	// return configs of component-rating
	public static function OnGetRatingConfigs()
	{
		$arConfigs = array(
			'MODULE_ID' => 'FORUM',
			'MODULE_NAME' => GetMessage('FORUM_RATING_NAME')
		);
		$arConfigs["COMPONENT"]["USER"]["VOTE"][] = array(
			"ID"	=> 'TOPIC',
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingsComponentsForum',
			"CALC_METHOD"	=> 'CalcUserVoteForumTopic',
			"NAME"	=> GetMessage('FORUM_RATING_USER_VOTE_TOPIC_NAME'),
			"DESC"  => GetMessage('FORUM_RATING_USER_VOTE_TOPIC_DESC'),
			"FIELDS" => array(
				array(
					"ID" => 'COEFFICIENT',
					"DEFAULT" => '0.5',
				),
				array(
					"ID" => 'LIMIT',
					"NAME" => GetMessage('FORUM_RATING_USER_VOTE_TOPIC_LIMIT_NAME'),
					"DEFAULT" => '30',
				),
			)
		);
		$arConfigs["COMPONENT"]["USER"]["VOTE"][] = array(
			"ID"	=> 'POST',
			"REFRESH_TIME"	=> '3600',
			"CLASS"	=> 'CRatingsComponentsForum',
			"CALC_METHOD"	=> 'CalcUserVoteForumPost',
			"NAME" => GetMessage('FORUM_RATING_USER_VOTE_POST_NAME'),
			"DESC" => GetMessage('FORUM_RATING_USER_VOTE_POST_DESC'),
			"FIELDS" => array(
				array(
					"ID" => 'COEFFICIENT',
					"DEFAULT" => '0.1',
				),
				array(
					"ID" => 'LIMIT',
					"NAME" => GetMessage('FORUM_RATING_USER_VOTE_POST_LIMIT_NAME'),
					"DEFAULT" => '30',
				),
			)
		);
		$arConfigs["COMPONENT"]["USER"]["RATING"][] = array(
			"ID"	=> 'ACTIVITY',
			"REFRESH_TIME"	=> '7200',
			"CLASS"	=> 'CRatingsComponentsForum',
			"CALC_METHOD"	=> 'CalcUserRatingForumActivity',
			"EXCEPTION_METHOD"	=> 'ExceptionUserRatingForumActivity',
			"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_NAME'),
			"DESC" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_DESC'),
			"FORMULA" => 'T<sub>1</sub> * K<sub>T1</sub> + T<sub>7</sub> * K<sub>T7</sub> + T<sub>30</sub> * K<sub>T30</sub>+ T<sub>all</sub> * K<sub>Tall</sub> + P<sub>1</sub> * K<sub>P1</sub> + P<sub>7</sub> * K<sub>P7</sub> + P<sub>30</sub> * K<sub>P30</sub> + P<sub>all</sub> * K<sub>Pall</sub>',
			"FORMULA_DESC" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FORMULA_DESC'),
			"FIELDS" => array(
				array(
					"ID" => 'TODAY_TOPIC_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_TODAY_TOPIC_COEF'),
					"DEFAULT" => '0.4',
				),
				array(
					"ID" => 'WEEK_TOPIC_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_WEEK_TOPIC_COEF'),
					"DEFAULT" => '0.2',
				),
				array(
					"ID" => 'MONTH_TOPIC_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_MONTH_TOPIC_COEF'),
					"DEFAULT" => '0.1',
				),
				array(
					"ID" => 'ALL_TOPIC_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_ALL_TOPIC_COEF'),
					"DEFAULT" => '0',
				),
				array(
					"ID" => 'TODAY_POST_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_TODAY_POST_COEF'),
					"DEFAULT" => '0.2',
				),
				array(
					"ID" => 'WEEK_POST_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_WEEK_POST_COEF'),
					"DEFAULT" => '0.1',
				),
				array(
					"ID" => 'MONTH_POST_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_MONTH_POST_COEF'),
					"DEFAULT" => '0.05',
				),
				array(
					"ID" => 'ALL_POST_COEF',
					"NAME" => GetMessage('FORUM_RATING_USER_RATING_ACTIVITY_FIELDS_ALL_POST_COEF'),
					"DEFAULT" => '0',
				),
			)
		);

		return $arConfigs;
	}

	// return support object
	public static function OnGetRatingObject()
	{
		$arRatingConfigs = CRatingsComponentsForum::OnGetRatingConfigs();
		foreach ($arRatingConfigs["COMPONENT"] as $SupportType => $value)
			$arSupportType[] = $SupportType;

		return $arSupportType;
	}

	// check the value of the component-rating which relate to the module
	public static function OnAfterAddRating($ID, $arFields)
	{
		$arFields['CONFIGS']['FORUM'] = CRatingsComponentsForum::__CheckFields($arFields['ENTITY_ID'], $arFields['CONFIGS']['FORUM']);

		return $arFields;
	}

	// check the value of the component-rating which relate to the module
	public static function OnAfterUpdateRating($ID, $arFields)
	{
		$arFields['CONFIGS']['FORUM'] = CRatingsComponentsForum::__CheckFields($arFields['ENTITY_ID'], $arFields['CONFIGS']['FORUM']);

		return $arFields;
	}

	// Utilities

	// check input values, if value does not validate, set the default value
	public static function __CheckFields($entityId, $arConfigs)
	{
		$arDefaultConfig = CRatingsComponentsForum::__AssembleConfigDefault($entityId);
		if ($entityId == "USER") {
			if (isset($arConfigs['VOTE']['TOPIC']))
			{
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['VOTE']['TOPIC']['COEFFICIENT']))
					$arConfigs['VOTE']['TOPIC']['COEFFICIENT'] = $arDefaultConfig['VOTE']['TOPIC']['COEFFICIENT']['DEFAULT'];
				if (!preg_match('/^\d{1,5}$/', $arConfigs['VOTE']['TOPIC']['LIMIT']))
					$arConfigs['VOTE']['TOPIC']['LIMIT'] = $arDefaultConfig['VOTE']['TOPIC']['LIMIT']['DEFAULT'];
			}

			if (isset($arConfigs['VOTE']['POST']))
			{
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['VOTE']['POST']['COEFFICIENT']))
					$arConfigs['VOTE']['POST']['COEFFICIENT'] = $arDefaultConfig['VOTE']['POST']['COEFFICIENT']['DEFAULT'];
					if (!preg_match('/^\d{1,5}$/', $arConfigs['VOTE']['POST']['LIMIT']))
					$arConfigs['VOTE']['POST']['LIMIT'] = $arDefaultConfig['VOTE']['POST']['LIMIT']['DEFAULT'];
			}

			if (isset($arConfigs['RATING']['ACTIVITY']))
			{
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['TODAY_TOPIC_COEF']))
					$arConfigs['RATING']['ACTIVITY']['TODAY_TOPIC_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['TODAY_TOPIC_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['WEEK_TOPIC_COEF']))
					$arConfigs['RATING']['ACTIVITY']['WEEK_TOPIC_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['WEEK_TOPIC_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['MONTH_TOPIC_COEF']))
					$arConfigs['RATING']['ACTIVITY']['MONTH_TOPIC_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['MONTH_TOPIC_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['ALL_TOPIC_COEF']))
					$arConfigs['RATING']['ACTIVITY']['ALL_TOPIC_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['ALL_TOPIC_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['TODAY_POST_COEF']))
					$arConfigs['RATING']['ACTIVITY']['TODAY_POST_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['TODAY_POST_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['WEEK_POST_COEF']))
					$arConfigs['RATING']['ACTIVITY']['WEEK_POST_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['WEEK_POST_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['MONTH_POST_COEF']))
					$arConfigs['RATING']['ACTIVITY']['MONTH_POST_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['MONTH_POST_COEF']['DEFAULT'];

				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['ACTIVITY']['ALL_POST_COEF']))
					$arConfigs['RATING']['ACTIVITY']['ALL_POST_COEF'] = $arDefaultConfig['RATING']['ACTIVITY']['ALL_POST_COEF']['DEFAULT'];
			}
		}

		return $arConfigs;
	}

	// collect the default and regular expressions for the fields component-rating
	public static function __AssembleConfigDefault($objectType = null)
	{
		$arConfigs = array();
		$arRatingConfigs = CRatingsComponentsForum::OnGetRatingConfigs();
		if (is_null($objectType))
		{
			foreach ($arRatingConfigs["COMPONENT"] as $OBJ_TYPE => $TYPE_VALUE)
				foreach ($TYPE_VALUE as $RAT_TYPE => $RAT_VALUE)
					foreach ($RAT_VALUE as $VALUE_CONFIG)
						foreach ($VALUE_CONFIG['FIELDS'] as $VALUE_FIELDS)
							$arConfigs[$OBJ_TYPE][$RAT_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT'] = $VALUE_FIELDS['DEFAULT'];
		}
		else
		{
			foreach ($arRatingConfigs["COMPONENT"][$objectType] as $RAT_TYPE => $RAT_VALUE)
				foreach ($RAT_VALUE as $VALUE_CONFIG)
					foreach ($VALUE_CONFIG['FIELDS'] as $VALUE_FIELDS)
						$arConfigs[$RAT_TYPE][$VALUE_CONFIG['ID']][$VALUE_FIELDS['ID']]['DEFAULT'] = $VALUE_FIELDS['DEFAULT'];

		}

		return $arConfigs;
	}

	public static function OnGetRatingContentOwner($arParams)
	{
		if ($arParams['ENTITY_TYPE_ID'] == 'FORUM_TOPIC')
		{
			$arTopic = CForumTopic::GetByID(IntVal($arParams['ENTITY_ID']));
			return $arTopic['USER_START_ID'];
		}
		elseif ($arParams['ENTITY_TYPE_ID'] == 'FORUM_POST')
		{
			$arMessage = CForumMessage::GetByID(IntVal($arParams['ENTITY_ID']));
			return $arMessage['AUTHOR_ID'];
		}
		return false;
	}
}

?>