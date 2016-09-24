<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorDateAge
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "date_age", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_VAL_DATE_AGE_DESCRIPTION'), // validator description
			"TYPES" => array("date"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorDateAge", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorDateAge", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorDateAge", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorDateAge", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"AGE_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_DATE_AGE_SETTINGS_DATE_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "18",
			),

			"AGE_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_DATE_AGE_SETTINGS_DATE_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "84",
			),
		);
	}

		public static function ToDB($arParams)
	{
		$arParams["AGE_FROM"] = intval($arParams["AGE_FROM"]);
		$arParams["AGE_TO"] = intval($arParams["AGE_TO"]);

		if ($arParams["AGE_FROM"] > $arParams["AGE_TO"])
		{
			$tmp = $arParams["AGE_FROM"];
			$arParams["AGE_FROM"] = $arParams["AGE_TO"];
			$arParams["AGE_TO"] = $tmp;
		}

		return serialize($arParams);
	}

	public static function FromDB($strParams)
	{
		return unserialize($strParams);
	}

	public static function DoValidate($arParams, $arQuestion, $arAnswers, $arValues)
	{
		global $APPLICATION;

		foreach ($arValues as $value)
		{
			if (strlen($value) <= 0) continue;

			// prepare check numbers
			$arValueCheck = ParseDateTime($value);
			$valueCheckSum = $arValueCheck["YYYY"] + $arValueCheck["MM"]/12 + $arValueCheck["DD"]/365;
			$currentCheckSum = date("Y") + date("n")/12 + date("j")/365;

			// check minimum age
			if (strlen($arParams["AGE_TO"]) > 0 && $valueCheckSum < $currentCheckSum-$arParams["AGE_TO"])
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_DATE_AGE_ERROR_MORE"));
				return false;
			}

			// check minimum age
			if (strlen($arParams["AGE_FROM"]) > 0 && $valueCheckSum > $currentCheckSum-$arParams["AGE_FROM"])
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_DATE_AGE_ERROR_LESS"));
				return false;
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorDateAge", "GetDescription"));
?>