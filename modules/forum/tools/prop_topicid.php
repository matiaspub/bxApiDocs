<?
IncludeModuleLangFile(__FILE__);
class CIBlockPropertyTopicID
{
	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE"		=>"S",
			"USER_TYPE"		=>"TopicID",
			"DESCRIPTION"		=> GetMessage("IBLOCK_PROP_TOPICID_DESC"),
			"GetPropertyFieldHtml"	=>array(__CLASS__,"GetPropertyFieldHtml"),
			"GetAdminListViewHTML"	=>array(__CLASS__,"GetAdminListViewHTML"),
			//optional handlers
			"ConvertToDB"		=>array(__CLASS__,"ConvertToDB"),
			"ConvertFromDB"		=>array(__CLASS__,"ConvertFromDB"),
			"GetSettingsHTML" => array(__CLASS__,"GetSettingsHTML"),
		);
	}

	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("SEARCHABLE", "WITH_DESCRIPTION", "ROW_COUNT", "COL_COUNT"),
		);
		return '';
	}
	//PARAMETERS:
	//$arProperty - b_iblock_property.*
	//$value - array("VALUE","DESCRIPTION") -- here comes HTML form value
	//strHTMLControlName - array("VALUE","DESCRIPTION")
	//return:
	//safe html

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		if (CModule::IncludeModule("forum"))
		{
			if (intVal($value["VALUE"]) <= 0)
			{
				$value["VALUE"] = '';
				$res = '';
			}
			else
			{
				$value["VALUE"] = intVal($value["VALUE"]);
				$arTopic = CForumTopic::GetByID($value["VALUE"]);
				if ($arTopic)
					$res = "[<a title='".GetMessage("IBLOCK_PROP_FORUM_VIEW_TOPIC")."' class='tablebodylink' href='/bitrix/admin/forum_topics.php?lang=".LANG."'>".intVal($arTopic["ID"])."</a>] (".htmlspecialcharsEx($arTopic["TITLE"]).") ";
				else
					$res = "&nbsp;".GetMessage("MAIN_NOT_FOUND");
			}
			return FindTopicID(htmlspecialcharsbx($strHTMLControlName["VALUE"]), $value["VALUE"], $res, htmlspecialcharsbx($strHTMLControlName["FORM_NAME"]));
		}
		return false;
	}

	public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
	{
		static $cache = array();
		if (CModule::IncludeModule("forum"))
		{
			$value["VALUE"] = intval($value["VALUE"]);
			if ($value["VALUE"] <= 0) {
				$value["VALUE"] = '';
				$res = '';
			} else {
				if (!array_key_exists($value["VALUE"], $cache))
					$cache[$value["VALUE"]] = CForumTopic::GetByID($value["VALUE"]);
				$arTopic = $cache[$value["VALUE"]];
				$res = (!empty($arTopic) ? "[".$value["VALUE"]."] (".htmlspecialcharsEx($arTopic["TITLE"]).")" : $value["VALUE"]);
			}
			return $res;
		}
		return '';
	}

	//PARAMETERS:
	//$arProperty - b_iblock_property.*
	//$value - array("VALUE",["DESCRIPTION"]) -- here comes HTML form value
	//return:
	//array of error messages
	//PARAMETERS:
	//$arProperty - b_iblock_property.*
	//$value - array("VALUE",["DESCRIPTION"]) -- here comes HTML form value
	//return:
	//DB form of the value
	public static function ConvertToDB($arProperty, $value)
	{
		if(strlen($value["VALUE"])>0)
			$value["VALUE"] = intVal($value["VALUE"]);
		return $value;
	}

	public static function ConvertFromDB($arProperty, $value)
	{
		if(strlen($value["VALUE"])>0)
		{
			$value["VALUE"] = intVal($value["VALUE"]);
		}
		return $value;
	}
}

function FindTopicID($tag_name, $tag_value, $topic_name="", $form_name = "form1", $tag_size = "3", $tag_maxlength="", $button_value = "...", $tag_class="typeinput", $button_class="tablebodybutton", $search_page="/bitrix/admin/forum_topics_search.php")
{
	global $APPLICATION;
	$form_name = trim($form_name);
	$tag_name_x = preg_replace("/([^a-z0-9]|\[|\])/is", "x", $tag_name);
	$tag_name_js = CUtil::JSEscape($tag_name);
	$form_name_js = CUtil::JSEscape($form_name);

	$button_class = htmlspecialcharsbx($button_class);
	$button_value = htmlspecialcharsbx($button_value);
	$form_name = htmlspecialcharsbx($form_name);
	$search_page = htmlspecialcharsbx($search_page);
	$tag_class = htmlspecialcharsbx($tag_class);
	$tag_maxlength = htmlspecialcharsbx($tag_maxlength);
	$tag_name = htmlspecialcharsbx($tag_name);
	$tag_size = htmlspecialcharsbx($tag_size);
	$tag_value = htmlspecialcharsbx($tag_value);
	//$topic_name = htmlspecialcharsbx($topic_name); // contains link to topics list

	if($APPLICATION->GetGroupRight("forum") >= "R")
	{
		$strReturn = "
<input type=\"text\" name=\"".$tag_name."\" id=\"".$tag_name."\" value=\"".$tag_value."\" size=\"".$tag_size."\" maxlength=\"".$tag_maxlength."\" class=\"".$tag_class."\">
<IFRAME style=\"width:0px; height:0px; border: 0px\" src=\"javascript:void(0)\" name=\"hiddenframe".$tag_name."\" id=\"hiddenframe".$tag_name."\"></IFRAME>
<input class=\"".$button_class."\" type=\"button\" name=\"FindTopic\" id=\"FindTopic\" OnClick=\"window.open('".CUtil::addslashes($search_page)."?lang=".LANGUAGE_ID."&FN=".CUtil::addslashes($form_name)."&FC=".CUtil::addslashes($tag_name)."', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\" value=\"".$button_value."\">
<span id=\"div_".$tag_name."\">".$topic_name."</span>
<script type=\"text/javascript\">
top.tv".$tag_name_x."='".($topic_name=="" ? '' : CUtil::JSEscape($tag_value))."';
top.Change".$tag_name_x." = function()
{
	var DV_".$tag_name_x." = top.BX('div_".$tag_name_js."');
	if (!!DV_".$tag_name_x.") {
".(empty($form_name) ? "
	var val = (top.BX('".$tag_name_js."') ? top.BX('".$tag_name_js."').value : false);
" : "
	var
		form = (top.BX('".$form_name_js."') ? top.BX('".$form_name_js."') : false),
		val = (!!form && !!form['".$tag_name_js."'] ? form['".$tag_name_js."'].value : false);
")."
		if (val!==false && top.tv".$tag_name_x."!=val) {
			top.tv".$tag_name_x."=val;
			if (top.tv".$tag_name_x."!='')
			{
				DV_".$tag_name_x.".innerHTML = '<i>".GetMessage("MAIN_WAIT")."</i>';
				top.BX('hiddenframe".$tag_name_js."').src='/bitrix/admin/get_topics.php?ID='+top.tv".$tag_name_x."+'&strName=".$tag_name_js."&lang=".LANG.(defined("ADMIN_SECTION") && ADMIN_SECTION===true?"&admin_section=Y":"")."';
			}
			else
				DV_".$tag_name_x.".innerHTML = '';
		}
	}
	setTimeout(function(){if (top.Change".$tag_name_x."){top.Change".$tag_name_x."();}},1000);
}
top.BX.ready(function(){if (top.Change".$tag_name_x."){top.Change".$tag_name_x."();}});
</script>
";
	}
	else
	{
		$strReturn = "
			<input type=\"text\" name=\"$tag_name\" id=\"$tag_name\" value=\"$tag_value\" size=\"$tag_size\" maxlength=\"strMaxLenght\">
			<input type=\"button\" name=\"FindTopic\" id=\"FindTopic\" OnClick=\"window.open('".CUtil::addslashes($search_page)."?lang=".LANGUAGE_ID."&FN=".CUtil::addslashes($form_name)."&FC=".CUtil::addslashes($tag_name)."', '', 'scrollbars=yes,resizable=yes,width=760,height=560,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));\" value=\"$button_value\">
			$topic_name
			";
	}
	return $strReturn;
}
?>
