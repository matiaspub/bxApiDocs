<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorFileSize
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "file_size", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_FILE_SIZE_DESCRIPTION'), // validator description
			"TYPES" => array("file", "image"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorFileSize", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorFileSize", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorFileSize", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorFileSize", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"SIZE_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_FILE_SIZE_SETTINGS_SIZE_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "0",
			),

			"SIZE_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_FILE_SIZE_SETTINGS_SIZE_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "5242880",
			),

		);
	}

	public static function ToDB($arParams)
	{
		$arParams["SIZE_FROM"] = intval($arParams["SIZE_FROM"]);
		$arParams["SIZE_TO"] = intval($arParams["SIZE_TO"]);

		if ($arParams["SIZE_FROM"] > $arParams["SIZE_TO"])
		{
			$tmp = $arParams["SIZE_FROM"];
			$arParams["SIZE_FROM"] = $arParams["SIZE_TO"];
			$arParams["SIZE_TO"] = $tmp;
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

		if (count($arValues) > 0)
		{
			foreach ($arValues as $arFile)
			{
				if (strlen($arFile["tmp_name"]) > 0 && $arFile["error"] == "0")
				{
					if ($arParams["SIZE_FROM"] > 0 && $arFile["size"] < $arParams["SIZE_FROM"])
					{
						$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_FILE_SIZE_ERROR_LESS"));
						return false;
					}

					if ($arParams["SIZE_TO"] > 0 && $arFile["size"] > $arParams["SIZE_TO"])
					{
						$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_FILE_SIZE_ERROR_MORE"));
						return false;
					}
				}
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorFileSize", "GetDescription"));
?>