<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorImageSize
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "image_size", // unique validator string ID
			"DESCRIPTION" => GetMessage('FORM_VALIDATOR_IMAGE_SIZE_DESCRIPTION'), // validator description
			"TYPES" => array("image"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorImageSize", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorImageSize", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorImageSize", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorImageSize", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"WIDTH_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_IMAGE_SIZE_SETTINGS_WIDTH_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "0",
			),

			"WIDTH_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_IMAGE_SIZE_SETTINGS_WIDTH_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "768",
			),

			"HEIGHT_FROM" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_IMAGE_SIZE_SETTINGS_HEIGHT_FROM"),
				"TYPE" => "TEXT",
				"DEFAULT" => "0",
			),

			"HEIGHT_TO" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_IMAGE_SIZE_SETTINGS_HEIGHT_TO"),
				"TYPE" => "TEXT",
				"DEFAULT" => "1024",
			),
		);
	}

	public static function ToDB($arParams)
	{
		$arParams["WIDTH_FROM"] = intval($arParams["WIDTH_FROM"]);
		$arParams["WIDTH_TO"] = intval($arParams["WIDTH_TO"]);

		if ($arParams["WIDTH_FROM"] > $arParams["WIDTH_TO"])
		{
			$tmp = $arParams["WIDTH_FROM"];
			$arParams["WIDTH_FROM"] = $arParams["WIDTH_TO"];
			$arParams["WIDTH_TO"] = $tmp;
		}

		$arParams["HEIGHT_FROM"] = intval($arParams["HEIGHT_FROM"]);
		$arParams["HEIGHT_TO"] = intval($arParams["HEIGHT_TO"]);

		if ($arParams["HEIGHT_FROM"] > $arParams["HEIGHT_TO"])
		{
			$tmp = $arParams["HEIGHT_FROM"];
			$arParams["HEIGHT_FROM"] = $arParams["HEIGHT_TO"];
			$arParams["HEIGHT_TO"] = $tmp;
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
			foreach ($arValues as $arImage)
			{
				// if image successfully uploaded
				if (
					strlen($arImage["tmp_name"]) > 0
					&& ($arImageInfo = CFile::GetImageSize($arImage["tmp_name"]))
				)
				{
					// check minimum image width
					if ($arParams["WIDTH_FROM"] > 0 && $arImageInfo[0] < $arParams["WIDTH_FROM"])
					{
						$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_IMAGE_SIZE_ERROR_WIDTH_LESS"));
						return false;
					}

					// check maximum image width
					if ($arParams["WIDTH_TO"] > 0 && $arImageInfo[0] > $arParams["WIDTH_TO"])
					{
						$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_IMAGE_SIZE_ERROR_WIDTH_MORE"));
						return false;
					}

					// check minimum image height
					if ($arParams["HEIGHT_FROM"] > 0 && $arImageInfo[1] < $arParams["HEIGHT_FROM"])
					{
						$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_IMAGE_SIZE_ERROR_HEIGHT_LESS"));
						return false;
					}

					// check maximum image height
					if ($arParams["HEIGHT_TO"] > 0 && $arImageInfo[1] > $arParams["HEIGHT_TO"])
					{
						$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_IMAGE_SIZE_ERROR_HEIGHT_MORE"));
						return false;
					}
				}
			}
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorImageSize", "GetDescription"));
?>