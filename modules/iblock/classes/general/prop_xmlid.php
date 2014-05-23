<?
IncludeModuleLangFile(__FILE__);

class CIBlockPropertyXmlID
{
	function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
	{
		static $cache = array();
		if(isset($strHTMLControlName['MODE']) && $strHTMLControlName["MODE"] == "CSV_EXPORT")
		{
			return $value["VALUE"];
		}
		elseif(strlen($value["VALUE"])>0)
		{
			if(!isset($cache[$value["VALUE"]]))
			{
				$db_res = CIBlockElement::GetList(
					array(),
					array("=XML_ID"=>$value["VALUE"], "SHOW_HISTORY"=>"Y"),
					false,
					false,
					array("ID", "IBLOCK_TYPE_ID", "IBLOCK_ID", "NAME", "DETAIL_PAGE_URL")
				);
				$ar_res = $db_res->GetNext();
				if($ar_res)
					$cache[$value["VALUE"]] = $ar_res;
				else
					$cache[$value["VALUE"]] = $value["VALUE"];
			}

			if (isset($strHTMLControlName['MODE']) && ($strHTMLControlName["MODE"] == "SIMPLE_TEXT" || $strHTMLControlName["MODE"] == 'ELEMENT_TEMPLATE'))
			{
				if (is_array($cache[$value["VALUE"]]))
					return $cache[$value["VALUE"]]["~NAME"];
				else
					return $cache[$value["VALUE"]];
			}
			else
			{
				if (is_array($cache[$value["VALUE"]]))
					return '<a href="'.$cache[$value["VALUE"]]["DETAIL_PAGE_URL"].'">'.$cache[$value["VALUE"]]["NAME"].'</a>';
				else
					return htmlspecialcharsex($cache[$value["VALUE"]]);
			}
		}
		else
		{
			return '';
		}
	}

	function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		static $cache = array();
		if(strlen($value["VALUE"])>0)
		{
			if(!array_key_exists($value["VALUE"], $cache))
			{
				$db_res = CIBlockElement::GetList(
					array(),
					array("=XML_ID"=>$value["VALUE"], "SHOW_HISTORY"=>"Y"),
					false,
					false,
					array("ID", "IBLOCK_TYPE_ID", "IBLOCK_ID", "NAME")
				);
				$ar_res = $db_res->GetNext();
				if($ar_res)
					$cache[$value["VALUE"]] = htmlspecialcharsbx($ar_res['NAME']).
					' [<a href="'.
					'/bitrix/admin/iblock_element_edit.php?'.
					'type='.urlencode($ar_res['IBLOCK_TYPE_ID']).
					'&amp;IBLOCK_ID='.$ar_res['IBLOCK_ID'].
					'&amp;ID='.$ar_res['ID'].
					'&amp;lang='.LANGUAGE_ID.
					'" title="'.GetMessage("IBLOCK_PROP_EL_EDIT").'">'.$ar_res['ID'].'</a>]';
				else
					$cache[$value["VALUE"]] = htmlspecialcharsbx($value["VALUE"]);
			}
			return $cache[$value["VALUE"]];
		}
		else
		{
			return '&nbsp;';
		}
	}

	//PARAMETERS:
	//$arProperty - b_iblock_property.*
	//$value - array("VALUE","DESCRIPTION") -- here comes HTML form value
	//strHTMLControlName - array("VALUE","DESCRIPTION")
	//return:
	//safe html
	function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$ar_res = false;
		if(strlen($value["VALUE"]))
		{
			$db_res = CIBlockElement::GetList(
				array(),
				array("=XML_ID"=>$value["VALUE"], "SHOW_HISTORY"=>"Y"),
				false,
				false,
				array("ID", "IBLOCK_ID", "NAME")
			);
			$ar_res = $db_res->GetNext();
		}

		if(!$ar_res)
			$ar_res = array("NAME" => "");

		return  '<input name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" id="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" value="'.htmlspecialcharsex($value["VALUE"]).'" size="20" type="text">'.
			'<input type="button" value="..." onClick="jsUtils.OpenWindow(\''.CUtil::JSEscape('/bitrix/admin/iblock_element_search.php?lang='.LANG.'&n='.urlencode($strHTMLControlName["VALUE"]).'&get_xml_id=Y&a=b').'\', 600, 500);">'.
			'&nbsp;<span id="sp_'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" >'.$ar_res['NAME'].'</span>';
	}

	function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "WITH_DESCRIPTION"),
		);
		return '';
	}
}
?>
