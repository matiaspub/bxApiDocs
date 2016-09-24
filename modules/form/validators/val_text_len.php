<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorTextLen
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "text_len", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_VAL_TEXT_LEN_DESCRIPTION'), // validator description
			"TYPES" => array("text", "textarea", "password", "email", "url"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorTextLen", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorTextLen", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorTextLen", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorTextLen", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"LENGTH_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_TEXT_LEN_SETTINGS_LENGTH_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "0",
			),

			"LENGTH_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_TEXT_LEN_SETTINGS_LENGTH_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "100",
			),
		);
	}

	public static function ToDB($arParams)
	{
		$arParams["LENGTH_FROM"] = intval($arParams["LENGTH_FROM"]);
		$arParams["LENGTH_TO"] = intval($arParams["LENGTH_TO"]);

		if ($arParams["LENGTH_FROM"] > $arParams["LENGTH_TO"])
		{
			$tmp = $arParams["LENGTH_FROM"];
			$arParams["LENGTH_FROM"] = $arParams["LENGTH_TO"];
			$arParams["LENGTH_TO"] = $tmp;
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
			// check minimum length
			if (strlen($value) < $arParams["LENGTH_FROM"])
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_TEXT_LEN_ERROR_LESS"));
				return false;
			}

			// check maximum length
			if (strlen($value) > $arParams["LENGTH_TO"])
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_TEXT_LEN_ERROR_MORE"));
				return false;
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorTextLen", "GetDescription"));
?>