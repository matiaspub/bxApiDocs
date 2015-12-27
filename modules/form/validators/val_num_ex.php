<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorNumberEx
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "number_ext", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_VAL_NUM_EX_DESCRIPTION'), // validator description
			"TYPES" => array("text", "textarea"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorNumberEx", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorNumberEx", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorNumberEx", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorNumberEx", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"NUMBER_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_NUM_EX_SETTINGS_NUMBER_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "0",
			),

			"NUMBER_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_NUM_EX_SETTINGS_NUMBER_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "100",
			),

			"NUMBER_FLOAT" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_NUM_EX_SETTINGS_NUMBER_FLOAT"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "Y",
			),
		);
	}

	public static function ToDB($arParams)
	{
		$arParams["NUMBER_FLOAT"] = $arParams["NUMBER_FLOAT"] == "Y" ? "Y" : "N";
		$arParams["NUMBER_FROM"] = $arParams["NUMBER_FLOAT"] == "Y" ? floatval($arParams["NUMBER_FROM"]) : intval($arParams["NUMBER_FROM"]);
		$arParams["NUMBER_TO"] = $arParams["NUMBER_FLOAT"] == "Y" ? floatval($arParams["NUMBER_TO"]) : intval($arParams["NUMBER_TO"]);

		if ($arParams["NUMBER_FROM"] > $arParams["NUMBER_TO"])
		{
			$tmp = $arParams["NUMBER_FROM"];
			$arParams["NUMBER_FROM"] = $arParams["NUMBER_TO"];
			$arParams["NUMBER_TO"] = $tmp;
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

			// do not return error if NaN, but set it to number -


			// empty string is not a number but we won't return error - crossing with "required" mark
			if (!is_numeric($value))
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_EX_ERROR_NAN"));
				return false;
			}

			if ($arParams["NUMBER_FLOAT"] != "Y" && strval($value + 0) !== strval($value))
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_EX_ERROR_NOTINT"));
				return false;
			}

			// check minimum number
			if (strlen($arParams["NUMBER_FROM"]) > 0 && $value < $arParams["NUMBER_FROM"])
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_EX_ERROR_LESS"));
				return false;
			}

			// check maximum number
			if (strlen($arParams["NUMBER_TO"]) > 0 && $value > $arParams["NUMBER_TO"])
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_EX_ERROR_MORE"));
				return false;
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorNumberEx", "GetDescription"));
?>