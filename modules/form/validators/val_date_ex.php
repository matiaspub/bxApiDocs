<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorDateEx
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "date_ext", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_VAL_DATE_EX_DESCRIPTION'), // validator description
			"TYPES" => array("date"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorDateEx", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorDateEx", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorDateEx", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorDateEx", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"DATE_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_DATE_EX_SETTINGS_DATE_FROM")." (".FORMAT_DATE.")",
				"TYPE" => "DATE",
				"DEFAULT" => ConvertTimeStamp(time()-365*86400),
			),

			"DATE_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_DATE_EX_SETTINGS_DATE_TO")." (".FORMAT_DATE.")",
				"TYPE" => "DATE",
				"DEFAULT" => ConvertTimeStamp(time()+365*86400),
			),
		);
	}

	public static function ToDB($arParams)
	{
		if (strlen($arParams["DATE_FROM"]) > 0) $arParams["DATE_FROM"] = MakeTimeStamp($arParams["DATE_FROM"]);
		if (strlen($arParams["DATE_TO"]) > 0) $arParams["DATE_TO"] = MakeTimeStamp($arParams["DATE_TO"]);

		if ($arParams["DATE_FROM"] > $arParams["DATE_TO"] && strlen($arParams["DATE_TO"]) > 0)
		{
			$tmp = $arParams["DATE_FROM"];
			$arParams["DATE_FROM"] = $arParams["DATE_TO"];
			$arParams["DATE_TO"] = $tmp;
		}

		return serialize($arParams);
	}

	public static function FromDB($strParams)
	{
		$arParams = unserialize($strParams);
		if (strlen($arParams["DATE_FROM"]) > 0) $arParams["DATE_FROM"] = ConvertTimeStamp($arParams["DATE_FROM"], "SHORT");
		if (strlen($arParams["DATE_TO"]) > 0) $arParams["DATE_TO"] = ConvertTimeStamp($arParams["DATE_TO"], "SHORT");

		return $arParams;
	}

	public static function DoValidate($arParams, $arQuestion, $arAnswers, $arValues)
	{
		global $APPLICATION;

		foreach ($arValues as $value)
		{
			// check minimum date
			if (strlen($arParams["DATE_FROM"]) > 0 && MakeTimeStamp($value) < MakeTimeStamp($arParams["DATE_FROM"]))
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_DATE_EX_ERROR_LESS"));
				return false;
			}

			// check maximum date
			if (strlen($arParams["DATE_TO"]) > 0 && MakeTimeStamp($value) > MakeTimeStamp($arParams["DATE_TO"]))
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_DATE_EX_ERROR_MORE"));
				return false;
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorDateEx", "GetDescription"));
?>