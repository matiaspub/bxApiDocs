<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/classes/general/ratings_components.php");

class CAllRatingsComponentsMain
{
	// return configs of component-rating
	public static function OnGetRatingConfigs()
	{
		$arConfigs = array(
			'MODULE_ID' => 'MAIN',
			'MODULE_NAME' => GetMessage('MAIN_RATING_NAME'),
		);
		$arConfigs["COMPONENT"]["USER"]["VOTE"][] = array(
			"ID" => 'USER',
			"REFRESH_TIME" => '3600',
			"CLASS" => 'CRatingsComponentsMain',
			"CALC_METHOD" => 'CalcVoteUser',
			"NAME" => GetMessage('MAIN_RATING_USER_VOTE_USER_NAME'),
			"DESC" => GetMessage('MAIN_RATING_USER_VOTE_USER_DESC'),
			"FIELDS" => array(
				array(
					"ID" => 'COEFFICIENT',
					"DEFAULT" => '1',
				),
				array(
					"ID" => 'LIMIT',
					"NAME" => GetMessage('MAIN_RATING_USER_VOTE_USER_LIMIT_NAME'),
					"DEFAULT" => '30',
				),
			)
		);
		$arConfigs["COMPONENT"]["USER"]["RATING"][] = array(
			"ID" => 'BONUS',
			"REFRESH_TIME" => '3600',
			"CLASS" => 'CRatingsComponentsMain',
			"CALC_METHOD" => 'CalcUserBonus',
			"NAME" => GetMessage('FORUM_RATING_USER_RATING_BONUS_NAME'),
			"DESC" => GetMessage('FORUM_RATING_USER_RATING_BONUS_DESC'),
			"FORMULA" => "StartValue * K",
			"FORMULA_DESC" => GetMessage('FORUM_RATING_USER_RATING_BONUS_FORMULA_DESC'),
			"FIELDS" => array(
				array(
					"ID" => 'COEFFICIENT',
					"DEFAULT" => '1',
				),
			)
		);
		return $arConfigs;
	}


	// return support object
	public static function OnGetRatingObject()
	{
		$arRatingConfigs = CRatingsComponentsMain::OnGetRatingConfigs();
		foreach ($arRatingConfigs["COMPONENT"] as $SupportType => $value)
			$arSupportType[] = $SupportType;

		return $arSupportType;
	}

	// check the value of the component-rating which relate to the module
	public static function OnAfterAddRating($ID, $arFields)
	{
		$arFields['CONFIGS']['MAIN'] = CRatingsComponentsMain::__CheckFields($arFields['ENTITY_ID'], $arFields['CONFIGS']['MAIN']);

		return $arFields;
	}

	// check the value of the component-rating which relate to the module
	public static function OnAfterUpdateRating($ID, $arFields)
	{
		$arFields['CONFIGS']['MAIN'] = CRatingsComponentsMain::__CheckFields($arFields['ENTITY_ID'], $arFields['CONFIGS']['MAIN']);

		return $arFields;
	}

	// Utilities

	// check input values, if value does not validate, set the default value
	public static function __CheckFields($entityId, $arConfigs)
	{
		$arDefaultConfig = CRatingsComponentsMain::__AssembleConfigDefault($entityId);

		if ($entityId == "USER") {
			if (isset($arConfigs['VOTE']['USER']))
			{
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['VOTE']['USER']['COEFFICIENT']))
					$arConfigs['VOTE']['USER']['COEFFICIENT'] = $arDefaultConfig['VOTE']['USER']['COEFFICIENT']['DEFAULT'];
				if (!preg_match('/^\d{1,5}$/', $arConfigs['VOTE']['USER']['LIMIT']))
					$arConfigs['VOTE']['USER']['LIMIT'] = $arDefaultConfig['VOTE']['USER']['LIMIT']['DEFAULT'];
			}
			if (isset($arConfigs['RATING']['BONUS']))
			{
				if (!preg_match('/^\d{1,7}\.?\d{0,4}$/', $arConfigs['RATING']['BONUS']['COEFFICIENT']))
					$arConfigs['RATING']['BONUS']['COEFFICIENT'] = $arDefaultConfig['RATING']['BONUS']['COEFFICIENT']['DEFAULT'];
			}
		}

		return $arConfigs;
	}

	// collect the default and regular expressions for the fields component-rating
	public static function __AssembleConfigDefault($objectType = null)
	{
		$arConfigs = array();
		$arRatingConfigs = CRatingsComponentsMain::OnGetRatingConfigs();
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
		if ($arParams['ENTITY_TYPE_ID'] == 'USER')
		{
			return IntVal($arParams['ENTITY_ID']);
		}
		return false;
	}

	// auto enabler rating vote
	public static function GetShowRating(&$arParams)
	{
		if (isset($arParams['SHOW_RATING']) && trim($arParams['SHOW_RATING']) != '')
			$arParams['SHOW_RATING'] = $arParams['SHOW_RATING'] == 'Y'? 'Y': 'N';
		else
			$arParams['SHOW_RATING'] = COption::GetOptionString('main', 'rating_vote_show', 'N');

		return $arParams['SHOW_RATING'];
	}
}
