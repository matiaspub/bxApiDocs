<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorNumber
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "number", // validator string ID
			"DESCRIPTION" => GetMessage("FORM_VALIDATOR_VAL_NUM_DESCRIPTION"), // validator description
			"TYPES" => array("text", "textarea"), //  list of types validator can be applied.
			"HANDLER" => array("CFormValidatorNumber", "DoValidate") // main validation method
		);
	}

	public static function DoValidate($arParams, $arQuestion, $arAnswers, $arValues)
	{
		global $APPLICATION;

		foreach ($arValues as $value)
		{
			// empty string is not a number but we won't return error - crossing with "required" mark
			if ($value != "" && (($value !==0  && intval($value) == 0) || strval($value + 0) != strval($value)))
			{
				$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_VAL_NUM_ERROR"));
				return false;
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorNumber", "GetDescription"));
?>