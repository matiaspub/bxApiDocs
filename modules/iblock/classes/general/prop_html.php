<?
IncludeModuleLangFile(__FILE__);

class CIBlockPropertyHTML
{
	function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		if(!is_array($value["VALUE"]))
			$value = CIBlockPropertyHTML::ConvertFromDB($arProperty, $value);
		$ar = $value["VALUE"];
		if($ar)
			return FormatText($ar["TEXT"], $ar["TYPE"]);
		else
			return "";
	}

	function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		if(!is_array($value["VALUE"]))
			$value = CIBlockPropertyHTML::ConvertFromDB($arProperty, $value);
		$ar = $value["VALUE"];
		if($ar)
		{
			//if (strToLower($ar["TYPE"]) != "text")
			//	return $ar["TEXT"];
			//else
				return htmlspecialcharsex($ar["TYPE"].":".$ar["TEXT"]);
		}
		else
			return "&nbsp;";
	}

	function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
	{
		if (!CModule::IncludeModule("fileman"))
			return GetMessage("IBLOCK_PROP_HTML_NOFILEMAN_ERROR");

		if (!is_array($value["VALUE"]))
			$value = CIBlockPropertyHTML::ConvertFromDB($arProperty, $value);

		$settings = CIBlockPropertyHTML::PrepareSettings($arProperty);

		$id = preg_replace("/[^a-z0-9]/i", '', $strHTMLControlName['VALUE']);

		ob_start();
		echo '<input type="hidden" name="'.$strHTMLControlName["VALUE"].'[TYPE]" value="html">';
		$LHE = new CLightHTMLEditor;
		$LHE->Show(array(
			'id' => $id,
			'width' => '100%',
			'height' => $settings['height'].'px',
			'inputName' => $strHTMLControlName["VALUE"].'[TEXT]',
			'content' => $value["VALUE"]['TEXT'],
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'bArisingToolbar' => false,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'RemoveFormat',
				'CreateLink', 'DeleteLink', 'Image', 'Video',
				'BackColor', 'ForeColor',
				'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyFull',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent',
				'StyleList', 'HeaderList',
				'FontList', 'FontSizeList',
			),
		));
		$s = ob_get_contents();
		ob_end_clean();
		return  $s;
	}

	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		global $APPLICATION;

		$strHTMLControlName["VALUE"] = htmlspecialcharsEx($strHTMLControlName["VALUE"]);
		if (!is_array($value["VALUE"]))
			$value = CIBlockPropertyHTML::ConvertFromDB($arProperty, $value);
		$ar = $value["VALUE"];
		if (strToLower($ar["TYPE"]) != "text")
			$ar["TYPE"] = "html";
		else
			$ar["TYPE"] = "text";

		$settings = CIBlockPropertyHTML::PrepareSettings($arProperty);

		ob_start();
		?><table><?
		if($strHTMLControlName["MODE"]=="FORM_FILL" && COption::GetOptionString("iblock", "use_htmledit", "Y")=="Y" && CModule::IncludeModule("fileman")):
		?><tr>
			<td colspan="2" align="center">
			<input type="hidden" name="<?=$strHTMLControlName["VALUE"]?>" value="">
				<?
				$text_name = preg_replace("/([^a-z0-9])/is", "_", $strHTMLControlName["VALUE"]."[TEXT]");
				$text_type = preg_replace("/([^a-z0-9])/is", "_", $strHTMLControlName["VALUE"]."[TYPE]");
				CFileMan::AddHTMLEditorFrame($text_name, $ar["TEXT"], $text_type, strToLower($ar["TYPE"]), $settings['height'], "N", 0, "", "");
				?>
			</td>
		</tr>
		<?else:?>
		<tr>
			<td><?echo GetMessage("IBLOCK_DESC_TYPE")?></td>
			<td>
				<input type="radio" name="<?=$strHTMLControlName["VALUE"]?>[TYPE]" id="<?=$strHTMLControlName["VALUE"]?>[TYPE][TEXT]" value="text" <?if($ar["TYPE"]!="html")echo " checked"?>>
				<label for="<?=$strHTMLControlName["VALUE"]?>[TYPE][TEXT]"><?echo GetMessage("IBLOCK_DESC_TYPE_TEXT")?></label> /
				<input type="radio" name="<?=$strHTMLControlName["VALUE"]?>[TYPE]" id="<?=$strHTMLControlName["VALUE"]?>[TYPE][HTML]" value="html"<?if($ar["TYPE"]=="html")echo " checked"?>>
				<label for="<?=$strHTMLControlName["VALUE"]?>[TYPE][HTML]"><?echo GetMessage("IBLOCK_DESC_TYPE_HTML")?></label>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center"><textarea cols="60" rows="10" name="<?=$strHTMLControlName["VALUE"]?>[TEXT]" style="width:100%"><?=$ar["TEXT"]?></textarea></td>
		</tr>
		<?endif;
		if (($arProperty["WITH_DESCRIPTION"]=="Y") && ('' != trim($strHTMLControlName["DESCRIPTION"]))):?>
		<tr>
			<td colspan="2">
				<span title="<?echo GetMessage("IBLOCK_PROP_HTML_DESCRIPTION_TITLE")?>"><?echo GetMessage("IBLOCK_PROP_HTML_DESCRIPTION_LABEL")?>:<input type="text" name="<?=$strHTMLControlName["DESCRIPTION"]?>" value="<?=$value["DESCRIPTION"]?>" size="18"></span>
			</td>
		</tr>
		<?endif;?>
		</table>
		<?
		$return = ob_get_contents();
		ob_end_clean();
		return  $return;
	}

	function ConvertToDB($arProperty, $value)
	{
		global $DB;
		$return = false;

		if(
			is_array($value)
			&& array_key_exists("VALUE", $value)
		)
		{
			$text = trim($value["VALUE"]["TEXT"]);
			$len = strlen($text);
			if ($len > 0)
			{
				if ($DB->type === "MYSQL")
					$limit = 63200;
				else
					$limit = 1950;

				if ($len > $limit)
					$value["VALUE"]["TEXT"] = substr($text, 0, $limit);

				$val = CIBlockPropertyHTML::CheckArray($value["VALUE"]);
				$return = array(
					"VALUE" => serialize($value["VALUE"]),
				);
				if(strlen(trim($value["DESCRIPTION"])) > 0)
					$return["DESCRIPTION"] = trim($value["DESCRIPTION"]);
			}
		}
		return $return;
	}

	function ConvertFromDB($arProperty, $value)
	{
		$return = false;
		if(!is_array($value["VALUE"]))
		{
			$return = array(
				"VALUE" => unserialize($value["VALUE"]),
			);
			if($value["DESCRIPTION"])
				$return["DESCRIPTION"] = trim($value["DESCRIPTION"]);
		}
		return $return;
	}

	function CheckArray($arFields = false)
	{
		$return = false;
		if (!is_array($arFields))
		{
			$return = unserialize($arFields);
		}
		else
		{
			$return = $arFields;
		}

		if ($return)
		{
			if (is_set($return, "TEXT") && (strLen(trim($return["TEXT"])) > 0))
			{
				$return["TYPE"] = strToUpper($return["TYPE"]);
				if (($return["TYPE"] != "TEXT") && ($return["TYPE"] != "HTML"))
					$return["TYPE"] = "HTML";
			}
			else
			{
				$return = false;
			}
		}
		return $return;
	}

	function GetLength($arProperty, $value)
	{
		if(is_array($value) && array_key_exists("VALUE", $value))
			return strLen(trim($value["VALUE"]["TEXT"]));
		else
			return 0;
	}

	function PrepareSettings($arProperty)
	{
		$height = 0;
		if(is_array($arProperty["USER_TYPE_SETTINGS"]))
			$height = intval($arProperty["USER_TYPE_SETTINGS"]["height"]);
		if($height <= 0)
			$height = 200;

		return array(
			"height" =>  $height,
		);
	}

	function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT"),
		);

		$height = 0;
		if(is_array($arProperty["USER_TYPE_SETTINGS"]))
			$height = intval($arProperty["USER_TYPE_SETTINGS"]["height"]);
		if($height <= 0)
			$height = 200;

		return '
		<tr valign="top">
			<td>'.GetMessage("IBLOCK_PROP_HTML_SETTING_HEIGHT").':</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[height]" value="'.$height.'">px</td>
		</tr>
		';
	}

}
?>
