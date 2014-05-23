<?
IncludeModuleLangFile(__FILE__);

class CIBlockPropertyFileMan
{
	function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" =>"S",
			"USER_TYPE" =>"FileMan",
			"GetPropertyFieldHtml" =>array("CIBlockPropertyFileMan","GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array('CIBlockPropertyFileMan','GetPropertyFieldHtmlMulty'),
			"ConvertToDB" => array("CIBlockPropertyFileMan","ConvertToDB"),
			"ConvertFromDB" => array("CIBlockPropertyFileMan","ConvertFromDB"),
			"GetSettingsHTML" => array("CIBlockPropertyFileMan","GetSettingsHTML"),
		);
	}

	public function GetPropertyFieldHtmlMulty($arProperty, $arValues, $strHTMLControlName)
	{
		if($strHTMLControlName["MODE"]=="FORM_FILL" && CModule::IncludeModule('fileman'))
		{
			$inputName = array();
			$description = array();
			foreach ($arValues as $intPropertyValueID => $arOneValue)
			{
				$key = $strHTMLControlName["VALUE"]."[".$intPropertyValueID."]";
				$inputName[$key."[VALUE]"] = $arOneValue["VALUE"];
				$description[$key."[DESCRIPTION]"] = $arOneValue["DESCRIPTION"];
			}

			return CFileInput::ShowMultiple($inputName, $strHTMLControlName["VALUE"]."[n#IND#][VALUE]", array(
				"PATH" => "Y",
				"IMAGE" => "N",
				"MAX_SIZE" => array(
					"W" => COption::GetOptionString("iblock", "detail_image_size"),
					"H" => COption::GetOptionString("iblock", "detail_image_size"),
				),
			), false, array(
				'upload' => false,
				'medialib' => true,
				'file_dialog' => true,
				'cloud' => true,
				'del' => true,
				'description' => $arProperty["WITH_DESCRIPTION"]=="Y"? array(
					"VALUES" => $description,
					'NAME_TEMPLATE' => $strHTMLControlName["VALUE"]."[n#IND#][DESCRIPTION]",
				): false,
			));
		}
		else
		{
			$table_id = md5($strHTMLControlName["VALUE"]);
			$return = '<table id="tb'.$table_id.'" border=0 cellpadding=0 cellspacing=0>';
			foreach ($arValues as $intPropertyValueID => $arOneValue)
			{
				$return .= '<tr><td>';

				$return .= '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]."[$intPropertyValueID][VALUE]").'" size="'.$arProperty["COL_COUNT"].'" value="'.htmlspecialcharsEx($arOneValue["VALUE"]).'">';

				if (($arProperty["WITH_DESCRIPTION"]=="Y") && ('' != trim($strHTMLControlName["DESCRIPTION"])))
					$return .= ' <span title="'.GetMessage("IBLOCK_PROP_FILEMAN_DESCRIPTION_TITLE").'">'.GetMessage("IBLOCK_PROP_FILEMAN_DESCRIPTION_LABEL").':<input name="'.htmlspecialcharsEx($strHTMLControlName["DESCRIPTION"]."[$intPropertyValueID][DESCRIPTION]").'" value="'.htmlspecialcharsEx($arOneValue["DESCRIPTION"]).'" size="18" type="text"></span>';

				$return .= '</td></tr>';
			}

			$return .= '<tr><td>';
			$return .= '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]."[n0][VALUE]").'" size="'.$arProperty["COL_COUNT"].'" value="">';
			if (($arProperty["WITH_DESCRIPTION"]=="Y") && ('' != trim($strHTMLControlName["DESCRIPTION"])))
				$return .= ' <span title="'.GetMessage("IBLOCK_PROP_FILEMAN_DESCRIPTION_TITLE").'">'.GetMessage("IBLOCK_PROP_FILEMAN_DESCRIPTION_LABEL").':<input name="'.htmlspecialcharsEx($strHTMLControlName["DESCRIPTION"]."[n0][DESCRIPTION]").'" value="" size="18" type="text"></span>';
			$return .= '</td></tr>';

			$return .= '<tr><td><input type="button" value="'.GetMessage("IBLOCK_PROP_FILEMAN_ADD").'" onClick="addNewRow(\'tb'.$table_id.'\')"></td></tr>';
			return $return.'</table>';
		}
	}

	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;

		if (strLen(trim($strHTMLControlName["FORM_NAME"])) <= 0)
			$strHTMLControlName["FORM_NAME"] = "form_element";
		$name = preg_replace("/[^a-zA-Z0-9_]/i", "x", htmlspecialcharsbx($strHTMLControlName["VALUE"]));

		if(is_array($value["VALUE"]))
		{
			$value["VALUE"] = $value["VALUE"]["VALUE"];
			$value["DESCRIPTION"] = $value["DESCRIPTION"]["VALUE"];
		}

		if($strHTMLControlName["MODE"]=="FORM_FILL" && CModule::IncludeModule('fileman'))
		{
			return CFileInput::Show($strHTMLControlName["VALUE"], $value["VALUE"],
				array(
					"PATH" => "Y",
					"IMAGE" => "N",
					"MAX_SIZE" => array(
						"W" => COption::GetOptionString("iblock", "detail_image_size"),
						"H" => COption::GetOptionString("iblock", "detail_image_size"),
					),
				), array(
					'upload' => false,
					'medialib' => true,
					'file_dialog' => true,
					'cloud' => true,
					'del' => true,
					'description' => $arProperty["WITH_DESCRIPTION"]=="Y"? array(
						"VALUE" => $value["DESCRIPTION"],
						"NAME" => $strHTMLControlName["DESCRIPTION"],
					): false,
				)
			);
		}
		else
		{
			$return = '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" id="'.$name.'" size="'.$arProperty["COL_COUNT"].'" value="'.htmlspecialcharsEx($value["VALUE"]).'">';

			if (($arProperty["WITH_DESCRIPTION"]=="Y") && ('' != trim($strHTMLControlName["DESCRIPTION"])))
			{
				$return .= ' <span title="'.GetMessage("IBLOCK_PROP_FILEMAN_DESCRIPTION_TITLE").'">'.GetMessage("IBLOCK_PROP_FILEMAN_DESCRIPTION_LABEL").':<input name="'.htmlspecialcharsEx($strHTMLControlName["DESCRIPTION"]).'" value="'.htmlspecialcharsEx($value["DESCRIPTION"]).'" size="18" type="text"></span>';
			}

			return $return;
		}
	}

	function ConvertToDB($arProperty, $value)
	{
		$result = array();
		$return = array();
		if(is_array($value["VALUE"]))
		{
			$result["VALUE"] = $value["VALUE"]["VALUE"];
			$result["DESCRIPTION"] = $value["DESCRIPTION"]["VALUE"];
		}
		else
		{
			$result["VALUE"] = $value["VALUE"];
			$result["DESCRIPTION"] = $value["DESCRIPTION"];
		}
		$return["VALUE"] = trim($result["VALUE"]);
		$return["DESCRIPTION"] = trim($result["DESCRIPTION"]);
		return $return;
	}

	function ConvertFromDB($arProperty, $value)
	{
		$return = array();
		if (strLen(trim($value["VALUE"])) > 0)
			$return["VALUE"] = $value["VALUE"];
		if (strLen(trim($value["DESCRIPTION"])) > 0)
			$return["DESCRIPTION"] = $value["DESCRIPTION"];
		return $return;
	}

	function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("MULTIPLE_CNT"),
		);

		return '';
	}

}
?>
