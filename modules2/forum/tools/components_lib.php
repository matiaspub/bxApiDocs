<?
IncludeModuleLangFile(__FILE__); 
class CForumParameters
{
	public static function GetDateTimeFormat($name="", $parent="")
	{
		$timestamp = mktime(7,30,45,2,22,2007);
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"VALUES" => array(
				"d-m-Y H:i:s" => CForumFormat::DateFormat("d-m-Y H:i:s", $timestamp),//"22-02-2007 7:30",
				"m-d-Y H:i:s" => CForumFormat::DateFormat("m-d-Y H:i:s", $timestamp),//"02-22-2007 7:30",
				"Y-m-d H:i:s" => CForumFormat::DateFormat("Y-m-d H:i:s", $timestamp),//"2007-02-22 7:30",
				"d.m.Y H:i:s" => CForumFormat::DateFormat("d.m.Y H:i:s", $timestamp),//"22.02.2007 7:30",
				"m.d.Y H:i:s" => CForumFormat::DateFormat("m.d.Y H:i:s", $timestamp),//"02.22.2007 7:30",
				"j M Y H:i:s" => CForumFormat::DateFormat("j M Y H:i:s", $timestamp),//"22 Feb 2007 7:30",
				"M j, Y H:i:s" => CForumFormat::DateFormat("M j, Y H:i:s", $timestamp),//"Feb 22, 2007 7:30",
				"j F Y H:i:s" => CForumFormat::DateFormat("j F Y H:i:s", $timestamp),//"22 February 2007 7:30",
				"F j, Y H:i:s" => CForumFormat::DateFormat("F j, Y H:i:s", $timestamp),//"February 22, 2007",
				"d.m.y g:i A" => CForumFormat::DateFormat("d.m.y g:i A", $timestamp),//"22.02.07 1:30 PM",
				"d.m.y G:i" => CForumFormat::DateFormat("d.m.y G:i", $timestamp),//"22.02.07 7:30",
				"d.m.Y H:i:s" => CForumFormat::DateFormat("d.m.Y H:i:s", $timestamp),//"22.02.2007 07:30",
			),
			"DEFAULT" => $GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("FULL")),
			"ADDITIONAL_VALUES" => "Y",
		);
	}
	
	public static function GetDateFormat($name="", $parent="")
	{
		$timestamp = mktime(7,30,45,2,22,2007);
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"VALUES" => array(
				"d-m-Y" => CForumFormat::DateFormat("d-m-Y", $timestamp),//"22-02-2007 7:30",
				"m-d-Y" => CForumFormat::DateFormat("m-d-Y", $timestamp),//"02-22-2007 7:30",
				"Y-m-d" => CForumFormat::DateFormat("Y-m-d", $timestamp),//"2007-02-22 7:30",
				"d.m.Y" => CForumFormat::DateFormat("d.m.Y", $timestamp),//"22.02.2007 7:30",
				"m.d.Y" => CForumFormat::DateFormat("m.d.Y", $timestamp),//"02.22.2007 7:30",
				"j M Y" => CForumFormat::DateFormat("j M Y", $timestamp),//"22 Feb 2007 7:30",
				"M j, Y" => CForumFormat::DateFormat("M j, Y", $timestamp),//"Feb 22, 2007 7:30",
				"j F Y" => CForumFormat::DateFormat("j F Y", $timestamp),//"22 February 2007 7:30",
				"F j, Y" => CForumFormat::DateFormat("F j, Y", $timestamp),//"February 22, 2007",
				"d.m.y" => CForumFormat::DateFormat("d.m.y", $timestamp),//"22.02.07 1:30 PM",
			),
			"DEFAULT" => $GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("SHORT")),
			"ADDITIONAL_VALUES" => "Y",
		);
	}
	
	public static function GetForumsMultiSelect($name="", $parent="")
	{
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => CForumParameters::GetForumsList(),
			"DEFAULT" => "",
		);
	}
	
	public static function GetForumsList()
	{
		$arGroup = array();
		$arForum = array();
		$db_res = CForumGroup::GetListEx(array(), array("LID" => LANG));
		if ($db_res && ($res = $db_res->GetNext()))
		{
			do 
			{
				$arGroup[intVal($res["ID"])] = $res["~NAME"];
			}while ($res = $db_res->GetNext());
		}

		$db_res = CForumNew::GetListEx(array("FORUM_GROUP_SORT"=>"ASC", "FORUM_GROUP_ID"=>"ASC", "SORT"=>"ASC", "NAME"=>"ASC"), array());
		if ($db_res && ($res = $db_res->GetNext()))
		{
			do 
			{
				$arForum[intVal($res["ID"])] = $res["~NAME"];
				if ((intVal($res["FORUM_GROUP_ID"]) > 0) && array_key_exists($res["FORUM_GROUP_ID"], $arGroup))
				{
					$arForum[intVal($res["ID"])] .= " [".$arGroup[$res["FORUM_GROUP_ID"]]."]";
				}
				if ($res["ACTIVE"] != "Y")
				{
					$arForum[intVal($res["ID"])] .= " N/A";
				}
			}while ($res = $db_res->GetNext());
		}
		return $arForum;
	}
	
	public static function GetSendMessageRights($name="", $parent="", $default = "A", $object = "MAIL")
	{
		if ($object == "ICQ")
		{
			if ((COption::GetOptionString("forum", "SHOW_ICQ_CONTACT", "N") != "Y")):
				return array(
					"PARENT" => $parent,
					"NAME" => $name,
					"TYPE" => "LIST",
					"VALUES" => array(
						"A" => GetMessage("FORUM_NO_ONE")
					),
					"DEFAULT" => "A"
				);
			else:
				return array(
					"PARENT" => $parent,
					"NAME" => $name,
					"TYPE" => "LIST",
					"VALUES" => array(
						"A" => GetMessage("FORUM_NO_ONE"),
						"E" => GetMessage("FORUM_AUTHORIZED_USERS"),
						"Y" => GetMessage("FORUM_ALL"),
					),
					"DEFAULT" => $default
				);
			endif;
		}
		
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"VALUES" => array(
				"A" => GetMessage("FORUM_NO_ONE"),
				"E" => GetMessage("FORUM_AUTHORIZED_USERS"),
				"U" => GetMessage("FORUM_ALL_WITH_CAPTCHA"),
				"Y" => GetMessage("FORUM_ALL"),
			),
			"DEFAULT" => $default
		);
	}
	
	public static function GetSetNavigation($name="", $parent="")
	{
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "CHECKBOX",
			"MULTIPLE" => "N",
			"DEFAULT" => "Y"
		);
	}
	public static function GetWordLength($name="", $parent="ADDITIONAL_SETTINGS")
	{
		if (empty($name))
			$name = GetMessage("F_WORD_LENGTH");
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "STRING",
			"DEFAULT" => "50"
		);
		
	}
	public static function GetWordWrapCut($name="", $parent="ADDITIONAL_SETTINGS")
	{
		if (empty($name))
			$name = GetMessage("F_WORD_WRAP_CUT");
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"VALUES" => array(
				"0" => GetMessage("F_WORD_WRAP"),
				"23" => GetMessage("F_WORD_CUT")." (23)",
				),
			"DEFAULT" => "23",
			"ADDITIONAL_VALUES" => "Y",
		);
		
	}
	
	public static function GetAjaxType($name="", $parent="ADDITIONAL_SETTINGS")
	{
		if (empty($name))
			$name = GetMessage("F_AJAX_TYPE");
		return array(
			"PARENT" => $parent,
			"NAME" => $name,
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y");
	}
	
	public static function AddPagerSettings(&$arComponentParameters, $sTitle = "", $arParams = array(
			// "bAddGroupOnly" => false,
			// "bDescNumbering" => true
	))
	{
		$arParams = (!is_array($arParams) ? array($arParams) : $arParams);
		$arParamsDefault = array(
			"bAddGroupOnly" => false,
			"bDescNumbering" => true);

		foreach ($arParamsDefault as $key => $val)
			$arParams[$key] = ((is_set($arParams, $key) ? $arParams[$key]: $arParamsDefault[$key]) == true);

		$arComponentParameters["GROUPS"]["PAGER_SETTINGS"] = array(
			"NAME" => GetMessage("FORUM_PAGER_SETTINGS"));

		if (!$arParams["bAddGroupOnly"])
		{
			if ($arParams["bDescNumbering"])
			{
				$arComponentParameters["PARAMETERS"]["PAGER_DESC_NUMBERING"] = Array(
					"PARENT" => "PAGER_SETTINGS",
					"NAME" => GetMessage("FORUM_PAGER_DESC_NUMBERING"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y");
			}
			$arComponentParameters["PARAMETERS"]["PAGER_SHOW_ALWAYS"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("FORUM_PAGER_SHOW_ALWAYS"),
				"TYPE" => "CHECKBOX",
				"DEFAULT" => "N");
			$arComponentParameters["PARAMETERS"]["PAGER_TITLE"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("FORUM_PAGER_TITLE"),
				"TYPE" => "STRING",
				"DEFAULT" => $sTitle);
			$arComponentParameters["PARAMETERS"]["PAGER_TEMPLATE"] = Array(
				"PARENT" => "PAGER_SETTINGS",
				"NAME" => GetMessage("FORUM_PAGER_TEMPLATE"),
				"TYPE" => "STRING",
				"DEFAULT" => "");
		}

	}
}

class CForumFormat
{
	public static function DateFormat($format="", $timestamp="")
	{
		global $DB;

		switch($format)
		{
			case "SHORT":
				return FormatDate($DB->dateFormatToPHP(FORMAT_DATE), $timestamp);
			case "FULL":
				return FormatDate($DB->dateFormatToPHP(FORMAT_DATETIME), $timestamp);
			default:
				return FormatDate($format, $timestamp);
		}
	}
	
	public static function FormatDate($strDate, $format="DD.MM.YYYY HH:MI:SS", $new_format="DD.MM.YYYY HH:MI:SS")
	{
		$strDate = trim($strDate);

		$new_format = str_replace("MI","I", $new_format);
		$new_format = preg_replace("/([DMYIHS])\\1+/is".BX_UTF_PCRE_MODIFIER, "\\1", $new_format);
		$arFormat = preg_split("/[^0-9a-z]/is", strtoupper($format));
		$arDate = preg_split("/[^0-9]/", $strDate);
		$arParsedDate=Array();
		$bound = min(count($arFormat), count($arDate));
		
		for($i=0; $i<$bound; $i++)
		{
			//if ($intval) $r = IntVal($arDate[$i]); else
			if (preg_match("/^[0-9]/", $arDate[$i]))
				$r = CDatabase::ForSql($arDate[$i], 4);
			else
				$r = IntVal($arDate[$i]);

			$arParsedDate[substr($arFormat[$i], 0, 2)] = $r;
		}
		if (intval($arParsedDate["DD"])<=0 || intval($arParsedDate["MM"])<=0 || intval($arParsedDate["YY"])<=0) 
			return false;

		$strResult = "";
		
		if(intval($arParsedDate["YY"])>1970 && intval($arParsedDate["YY"])<2038)
		{
			$ux_time = mktime(
					intval($arParsedDate["HH"]),
					intval($arParsedDate["MI"]),
					intval($arParsedDate["SS"]),
					intval($arParsedDate["MM"]),
					intval($arParsedDate["DD"]),
					intval($arParsedDate["YY"])
					);

			for ($i=0; $i<strlen($new_format); $i++)
			{
				$simbol = substr($new_format, $i ,1);
				switch ($simbol)
				{
					case "F":$match=GetMessage("FORUM_MONTH_".date("n", $ux_time));break;
					case "M":$match=GetMessage("FORUM_MON_".date("n", $ux_time));break;
					case "l":$match=GetMessage("FORUM_DAY_OF_WEEK_".date("w", $ux_time));break;
					case "D":$match=GetMessage("FORUM_DOW_".date("w", $ux_time));break;
					default: $match = date(substr($new_format, $i ,1), $ux_time); break;
				}
				$strResult .= $match;
			}
		}
		else
		{
			if($arParsedDate["MM"]<1 || $arParsedDate["MM"]>12) 
				$arParsedDate["MM"] = 1;
			for ($i=0; $i<strLen($new_format); $i++)
			{
				$simbol = substr($new_format, $i ,1);
				switch ($simbol)
				{
					case "F":
						$match = str_pad($arParsedDate["MM"], 2, "0", STR_PAD_LEFT);
						if (intVal($arParsedDate["MM"]) > 0)
							$match=GetMessage("FORUM_MONTH_".intVal($arParsedDate["MM"]));
						break;
					case "M":
						$match = str_pad($arParsedDate["MM"], 2, "0", STR_PAD_LEFT);
						if (intVal($arParsedDate["MM"]) > 0)
							$match=GetMessage("FORUM_MON_".intVal($arParsedDate["MM"]));
						break;
					case "l":
						$match = str_pad($arParsedDate["DD"], 2, "0", STR_PAD_LEFT);
						if (intVal($arParsedDate["DD"]) > 0)
							$match = GetMessage("FORUM_DAY_OF_WEEK_".intVal($arParsedDate["DD"]));
						break;
					case "D": 
						$match = str_pad($arParsedDate["DD"], 2, "0", STR_PAD_LEFT); 
						if (intVal($arParsedDate["DD"]) > 0)
							$match = GetMessage("FORUM_DOW_".intVal($arParsedDate["DD"]));
						break;
					case "d": $match = str_pad($arParsedDate["DD"], 2, "0", STR_PAD_LEFT); break;
					case "m": $match = str_pad($arParsedDate["MM"], 2, "0", STR_PAD_LEFT); break;
					case "j": $match = intVal($arParsedDate["MM"]); break;
					case "Y": $match = str_pad($arParsedDate["YY"], 4, "0", STR_PAD_LEFT); break;
					case "y": $match = substr($arParsedDate["YY"], 2);break;
					case "H": $match = str_pad($arParsedDate["HH"], 2, "0", STR_PAD_LEFT); break;
					case "i": $match = str_pad($arParsedDate["MI"], 2, "0", STR_PAD_LEFT); break;
					case "S": $match = str_pad($arParsedDate["SS"], 2, "0", STR_PAD_LEFT); break;
					case "g": 
						$match = intVal($arParsedDate["HH"]);
						if ($match > 12)
							$match = $match-12;
					case "a": 
					case "A": 
						$match = intVal($arParsedDate["HH"]);
						if ($match > 12)
							$match = ($match-12)." PM";
						else 
							$match .= " AM";
							
						if (substr($new_format, $i ,1) == "a")
							$match = strToLower($match);
							
					default: $match = substr($new_format, $i ,1); break;
				}
				$strResult .= $match;
			}
		}
		return $strResult;
	}
}
/*
GetMessage("FORUM_BOTTOM_PAGER");
GetMessage("FORUM_DAY_OF_WEEK_0");
GetMessage("FORUM_DAY_OF_WEEK_1");
GetMessage("FORUM_DAY_OF_WEEK_2");
GetMessage("FORUM_DAY_OF_WEEK_3");
GetMessage("FORUM_DAY_OF_WEEK_4");
GetMessage("FORUM_DAY_OF_WEEK_5");
GetMessage("FORUM_DAY_OF_WEEK_6");
GetMessage("FORUM_DOW_0");
GetMessage("FORUM_DOW_1");
GetMessage("FORUM_DOW_2");
GetMessage("FORUM_DOW_3");
GetMessage("FORUM_DOW_4");
GetMessage("FORUM_DOW_5");
GetMessage("FORUM_DOW_6");
GetMessage("FORUM_MONTH_1");
GetMessage("FORUM_MONTH_10");
GetMessage("FORUM_MONTH_11");
GetMessage("FORUM_MONTH_12");
GetMessage("FORUM_MONTH_2");
GetMessage("FORUM_MONTH_3");
GetMessage("FORUM_MONTH_4");
GetMessage("FORUM_MONTH_5");
GetMessage("FORUM_MONTH_6");
GetMessage("FORUM_MONTH_7");
GetMessage("FORUM_MONTH_8");
GetMessage("FORUM_MONTH_9");
GetMessage("FORUM_MON_1");
GetMessage("FORUM_MON_10");
GetMessage("FORUM_MON_11");
GetMessage("FORUM_MON_12");
GetMessage("FORUM_MON_2");
GetMessage("FORUM_MON_3");
GetMessage("FORUM_MON_4");
GetMessage("FORUM_MON_5");
GetMessage("FORUM_MON_6");
GetMessage("FORUM_MON_7");
GetMessage("FORUM_MON_8");
GetMessage("FORUM_MON_9");
GetMessage("FORUM_NAVIGATION");
GetMessage("FORUM_TOP_PAGER");

*/
?>