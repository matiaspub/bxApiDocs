<?
IncludeModuleLangFile(__FILE__);

class CFormValidatorINN
{
	public static function GetDescription()
	{
		return array(
			"NAME" => "INN", // validator string ID
			"DESCRIPTION" => GetMessage("FORM_VALIDATOR_VAL_INN_DESCRIPTION"), // validator description
			"TYPES" => array("text", "textarea"), //  list of types validator can be applied.
			"SETTINGS" => array("CFormValidatorINN", "GetSettings"), // method returning array of validator settings, optional
			"CONVERT_TO_DB" => array("CFormValidatorINN", "ToDB"), // method, processing validator settings to string to put to db, optional
			"CONVERT_FROM_DB" => array("CFormValidatorINN", "FromDB"), // method, processing validator settings from string from db, optional
			"HANDLER" => array("CFormValidatorINN", "DoValidate") // main validation method
		);
	}

	public static function GetSettings()
	{
		return array(
			"TYPE" => array(
				"TITLE" => GetMessage("FORM_VALIDATOR_VAL_INN_SETTINGS_TYPE"),
				"TYPE" => "DROPDOWN",
				"VALUES" => array(
					"jur" => GetMessage("FORM_VALIDATOR_VAL_INN_SETTINGS_TYPE_JUR"),
					"phys" => GetMessage("FORM_VALIDATOR_VAL_INN_SETTINGS_TYPE_PHYS"),
					"both" => GetMessage("FORM_VALIDATOR_VAL_INN_SETTINGS_TYPE_BOTH"),
				),
				"DEFAULT" => "both",
			),
		);
	}

	public static function ToDB($arParams)
	{
		$arPar = array(
			"TYPE" => $arParams["TYPE"] == "phys" || $arParams["TYPE"] == "jur"? $arParams["TYPE"] : "both"
		);

		return serialize($arPar);
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
			$value = strval($value);

			if (strlen($value) <= 0) continue;

			// check inn
			$lenValue = strlen($value);
			if ($lenValue > 0)
			{
				$res = true;

				if ((double)$value == 0)
				{
					$res = false;
				}
				else
				{
					for ($i = 0; $i < $lenValue; $i++)
					{
						if ($value[$i] !== "0" && intval($value[$i]) == 0)
						{
							$res = false;
							break;
						}
					}
				}

				if ($res)
				{
					if (
						$arParams['TYPE'] == "jur" && $lenValue != 10
						||
						$arParams['TYPE'] == "phys" && $lenValue != 12
						||
						$arParams['TYPE'] == "both" && $lenValue != 12 && $lenValue != 10
					)
					{
						$res = false;
					}
					elseif (!CFormValidatorINN::__checkINN($value))
					{
						$res = false;
					}
				}

				if (!$res)
				{
					$APPLICATION->ThrowException(GetMessage("FORM_VALIDATOR_INN_ERROR"));
					return false;
				}
			}
		}

		return true;
	}

	public static function __checkINN($value)
	{
		$arCheck = array(41,37,31,29,23,19,17,13,7,5,3);

		$lenValue = strlen($value);
		if ($lenValue == 10)
		{
			$arCheckArrays = array(
				array_values(array_slice($arCheck, 2)),
			);
		}
		elseif ($lenValue == 12)
		{
			$arCheckArrays = array(
				$arCheck,
				array_values(array_slice($arCheck, 1)),
			);
		}
		else
		{
			return false;
		}

		foreach ($arCheckArrays as $checkKey => $arCheck)
		{
			$checkSum = 0;
			foreach ($arCheck as $key => $num)
			{
				$checkSum += $num * intval($value[$key]);
			}

			$checkNum = 11 - $checkSum % 11;

			if ($checkNum == 10 || $checkNum == 11) $checkNum = 0;

			if ($checkNum != intval(substr($value, -$checkKey-1, 1)))
				return false;
		}

		return true;
	}
}

AddEventHandler("form", "onFormValidatorBuildList", array("CFormValidatorINN", "GetDescription"));
?>