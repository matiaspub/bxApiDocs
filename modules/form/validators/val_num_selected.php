<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorNumSelected
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "num_selected", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_VAL_NUM_SELECTED_DESCRIPTION'), // validator description
			"TYPES" => array("checkbox", "multiselect"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorNumSelected", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorNumSelected", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorNumSelected", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorNumSelected", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"NUMBER_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_NUM_SELECTED_SETTINGS_NUMBER_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "0",
			),

			"NUMBER_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_NUM_SELECTED_SETTINGS_NUMBER_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "2",
			),
		);
	}

	public static function ToDB($arParams)
	{
		$arParams["NUMBER_FROM"] = intval($arParams["NUMBER_FROM"]);
		$arParams["NUMBER_TO"] = intval($arParams["NUMBER_TO"]);

		if ($arParams["NUMBER_FROM"] < 0) $arParams["NUMBER_FROM"] = 0;
		if ($arParams["NUMBER_TO"] < 0) $arParams["NUMBER_TO"] = 0;

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

		if (strlen($arParams["NUMBER_FROM"]) > 0 && count($arValues) < $arParams["NUMBER_FROM"])
		{
			$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_SELECTED_ERROR_LESS"));
			return false;
		}

		if (strlen($arParams["NUMBER_TO"]) > 0 && count($arValues) > $arParams["NUMBER_TO"])
		{
			$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_SELECTED_ERROR_MORE"));
			return false;
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorNumSelected", "GetDescription"));
?>