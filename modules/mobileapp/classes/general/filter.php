<?
IncludeModuleLangFile(__FILE__);
class CAdminMobileFilter
{
	const SELECT_ALL = "AMFSelectAll";

	public static function getFields ($filterId)
	{
		return CUserOptions::GetOption("mobileapp", "filter_".$filterId, array());
	}

	public static function setFields ($filterId, $arFields)
	{
		return CUserOptions::SetOption("mobileapp", "filter_".$filterId, $arFields);
	}

	public static function getNonemptyFields ($filterId, $arFieldsParams = false)
	{
		$arFilter = self::getFields ($filterId);
		$arNonemptyFields = array();

		foreach ($arFilter as $fieldId => $fieldValue)
		{
			if(strlen($fieldValue) <= 0)
				continue;

			$arNonemptyFields[$fieldId] = $fieldValue;

			//BX.userOptions.save saves array as string coma delimited
			if(
				$arFieldsParams !== false
				&& isset($arFieldsParams[$fieldId])
				&& $arFieldsParams[$fieldId]["TYPE"] == "MULTI_SELECT"
				&& is_string($fieldValue)
			)
			{
				$arNonemptyFields[$fieldId] = explode(",", $fieldValue);
			}
		}

		return $arNonemptyFields;
	}

	public static function getHtml($arFields)
	{
		global $APPLICATION;

		$arData = array();

		foreach ($arFields as $fieldID => $arField)
		{
			if($arField["TYPE"] == "TEXT")
			{
				$arItem = array(
							"TYPE" => "TEXT",
							"ID" => "field_id_".$fieldID,
							"VALUE" => $arField["VALUE"]
						);
			}
			elseif($arField["TYPE"] == "DATE")
			{
				$arItem = array(
					"TYPE" => "TEXT",
					"ID" => "field_id_".$fieldID,
					"VALUE" => $arField["VALUE"],
					"CUSTOM_ATTRS" => array(
						"onclick" => "maAdminFilter.getDatePickerHtml(this);"
					)
				);

			}
			elseif($arField["TYPE"] == "ONE_SELECT")
			{
				if(isset($arField["ADD_ALL_SELECT"]) && $arField["ADD_ALL_SELECT"] == "Y")
				{
					$arField["OPTIONS"] = array_merge(
						array(self::SELECT_ALL => GetMessage("MOBILEAPP_FILTER_ALL")),
						$arField["OPTIONS"]
					);
				}

				$arItem = array(
					"TYPE" => "RADIO",
					"VALUES" => $arField["OPTIONS"],
					"SELECTED" => $arField["OPTIONS"][$arField["VALUE"]],
					"NAME" => "field_name_".$fieldID,
				);
			}

			elseif($arField["TYPE"] == "MULTI_SELECT")
			{
				$checked = array();
				if(is_array($arField["VALUE"]))
					$checked = $arField["VALUE"];
				else if(is_string($arField["VALUE"]) && strlen(trim($arField["VALUE"])) > 0)
					$checked = explode(',', $arField["VALUE"]);

				$arItem = array(
					"TYPE" => "CHECKBOXES",
					"VALUES" => $arField["OPTIONS"],
					"NAME" => "field_name_".$fieldID,
				);

				if(!empty($checked))
					$arItem["CHECKED"] = $checked;
			}

			$arData[] =	array(
				"TITLE" => $arField["NAME"],
				"TYPE" => "BLOCK",
				"FORM_ID" => "mapp_filter_form_id",
				"DATA" => array( $arItem )
			);
		}

		$compParams = array(
				"FORM_ID" => 'mapp_filter_form_id',
				"DATA" => $arData,
				);

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:mobileapp.edit',
			'.default',
			$compParams,
			false
		);

		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}
}
?>