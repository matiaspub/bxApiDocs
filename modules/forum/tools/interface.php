<?
IncludeModuleLangFile(__FILE__);
class CForumHTMLPresrnation
{
	public static function GetChainString($items = false, $templates = false)
	{
		$arItemLast = array();
		$arItems = array();
		if (!is_array($items))
			return false;
		if (!is_set($templates, "T_SEPARATOR"))
			$templates["T_SEPARATOR"] = "<font class=\"forum-chain-separator\">&nbsp;/&nbsp;</font>";
		if (!is_set($templates, "T_ALL_ITEMS"))
			$templates["T_ALL_ITEMS"] = "<a href=\"#LINK#\" class=\"forum-chain-item\">#TITLE#</a>";
		if (!is_set($templates, "T_LAST_ITEM"))
			$templates["T_LAST_ITEM"] = "<font class=\"forum-chain-last\">#TITLE#</font>";
		$arItemLast = array_pop($items);
		foreach ($items as $res)
			$arItems[] = str_replace(array("#LINK#", "#TITLE#"), array(htmlspecialcharsEx($res["LINK"]), htmlspecialcharsEx($res["TITLE"])), $templates["T_ALL_ITEMS"]);
		$arItems[] =  str_replace(array("#LINK#", "#TITLE#"), array(htmlspecialcharsEx($arItemLast["LINK"]), htmlspecialcharsEx($arItemLast["TITLE"])), $templates["T_LAST_ITEM"]);
		return implode($templates["T_SEPARATOR"], $arItems);
	}
	
	public static function ShowFilter($arForm = false, $arFields = false, $prefix = false, $arTable = array())
	{
		$outPut = array();
		$arGroupCookie = array();
		$forum_cookie = COption::GetOptionString("main", "cookie_name", "BITRIX_SM")."_FORUM_FILTER";
		if (!empty($_COOKIE[$forum_cookie]))
		{
			$result = explode("/", $_COOKIE[$forum_cookie]);
			if (!empty($result))
			{
				foreach ($result as $res)
				{
					$res_ = explode("-", $res);
					$arGroupCookie[$res_[0]] = $res_[1];
				}
			}
		}
		
		if ((empty($arFields)) || !is_array($arFields["params"]))
			return false;
		if (strLen($arForm["formName"]) <= 0)
			$arForm["formName"] = "form1";
		if (empty($arForm["formMethod"]))
			$arForm["formMethod"] = "get";
		$res_first = array();
		$all_checkbox = true;
		$prefix = "f";
		if (!empty($arFields["prefix"]))
			$prefix = $arFields["prefix"];
		if (empty($arFields["visible"]))
			$arFields["visible"] = 2;
		$res_visible = array();
		for ($ii = 0; $ii < $arFields["visible"]; $ii++)
			$res_visible[] = array_shift($arFields["params"]);
		$res_other = $arFields["params"];
	
	$JSFile = "";
	$prefix = preg_replace("/[^a-z0-9]/is", "_", $prefix);
	ob_start();
	$path = str_replace(array("\\", "//"), "/", dirname(__FILE__)."/interface_js.php");
	include_once($path);
	$JSFile = ob_get_contents();
	ob_end_clean();

	if (!empty($res_other))
	{
		$outPut["body"] .= '
		<script type="text/javascript">
			window.'.$prefix.'_popup_window = new ForumPopupMenu("'.$prefix.'_popup_window");
			'.$JSFile.'
		</script>
		<div id="'.$prefix.'_popup_window" style="position:absolute; left:0; top:0; visibility:hidden;">
		<table class="forum-main forum-filter-popup">';
		foreach ($res_other as $res)
		{
			$name = $prefix.$res["id"];
			$all_checkbox = ($arGroupCookie[$name] != "Y") ? false : $all_checkbox;
			$outPut["body"] .= 
			'<tr><td><div onMouseOver="this.className=\'filter-check-hover\';" onMouseOut="this.className=\'filter-check\';" class="filter-check" onclick="SectionSH(\''.$name.'\', \'\', \''.$prefix.'\');"><input type="checkbox" name="'.$name.'_checkbox" id="'.$name.'_checkbox" onclick="this.checked=(this.checked==true) ? false:true;"'.(($arGroupCookie[$name] != "Y") ? "" : " checked").' />'.$res["title"].'</div></td></tr>';
		}
		$outPut["body"] .= 
			'<tr><th><div onMouseOver="this.className=\'filter-check-hover\';" onMouseOut="this.className=\'filter-check\';" class="filter-check" onclick="var checkbox=document.getElementById(\''.$prefix.'_all_checkbox\'); checkbox.checked=(checkbox.checked ? false : true); SectionGA(\''.$prefix.'\', document.getElementById(\''.$prefix.'_all_checkbox\'));"><input type="checkbox" name="'.$prefix.'_all_checkbox" id="'.$prefix.'_all_checkbox" '.($all_checkbox ? "checked" : "").' onclick="this.checked=(this.checked==true) ? false:true;" />'.GetMessage("FORUM_SHOW_ALL_FILTER").'</div></th></tr>
		</table></div>';
	}
	$outPut["body"] .= 
		'<form name="'.$arForm["formName"].'" action="'.$arForm["formAction"].'" method="'.$arForm["formMethod"].'" class="forum-form">'.
		'<table class="forum-main forum-filter" name="'.$prefix.'_table" id="'.$prefix.'_table" width="100%">';
	if ($arForm["showTitle"] != "N")
	{
		$outPut["body"] .= 
			'<tr><th colspan="2">'.
				((!empty($res_other)) ? '<span class="filter-more" onClick="'.$prefix.'_popup_window.ShowMenu(this, null);" ></span>':'').
				GetMessage("FORUM_FILTER").
			'</th></tr>';
	}
	elseif (!empty($res_other)) 
	{
		$outPut["title"] = '<span class="filter-more" onClick="'.$prefix.'_popup_window.ShowMenu(this, null);" ></span>';
	}
	foreach ($res_visible as $res):
			$outPut["body"] .= 
			'<tr><td align="right">'.$res["title"].':</td><td>'.$res["body"].'</td></tr>';
	endforeach;
	foreach ($res_other as $res):
			$name = $prefix.$res["id"];
			$outPut["body"] .= 
			'<tr name="'.$name.'" id="'.$name.'"'.(($arGroupCookie[$name] != "Y") ? " style=\"display:none;\"" : "").'>
				<td align="right">'.$res["title"].':</td>
				<td nowrap="nowrap">
					<span class="filter-hide" onclick="SectionSH(\''.$name.'\', \'\', \''.$prefix.'\'); return false;"></span>'.$res["body"].
				'</td></tr>';
	endforeach;
	$outPut["body"] .= 
			'<tr><td colspan="2" align="center" nowrap="nowrap">';
	if (!empty($arForm["buttons"])):
		foreach ($arForm["buttons"] as $res):
				$outPut["body"] .= 
				'<input type="submit" name="'.$res["NAME"].'" value="'.$res["VALUE"].'" />';
		endforeach;
	else:
			$outPut["body"] .= 
			'<input type="submit" name="set_filter" value="'.GetMessage("FORUM_BUTTON_FILTER").'"/>&nbsp;
			<input type="submit" name="del_filter" value="'.GetMessage("FORUM_BUTTON_RESET").'"/>';
	endif;
	$outPut["body"] .= 
			'</td>
		</tr>
	</table>';		
	
	foreach ($arForm["addInputs"] as $res)
	{
		$outPut["body"] .= 
		'<input type="'.$res["TYPE"].'" name="'.$res["NAME"].'" value="'.$res["VALUE"].'" />';
	}
	$outPut["body"] .= 
		'</form>';
		return $outPut;
	}
}

?>
