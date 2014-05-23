<?
IncludeModuleLangFile(__FILE__);


/**
 *
 *
 *
 *
 *
 * @return mixed
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockformatproperties/index.php
 * @author Bitrix
 */
class CIBlockFormatProperties
{

	/**
	 *
	 *
	 *
	 *
	 *
	 * @return mixed <p></p>
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <p></p><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockformatproperties/getdisplayvalue.php
	 * @author Bitrix
	 */
	public static function GetDisplayValue($arItem, $arProperty, $event1)
	{
		static $installedStatictic = null;
		if (null === $installedStatictic)
		{
			$installedStatictic = \Bitrix\Main\ModuleManager::isModuleInstalled('statistic');
		}
		$arUserTypeFormat = false;
		if(isset($arProperty["USER_TYPE"]) && !empty($arProperty["USER_TYPE"]))
		{
			$arUserType = CIBlockProperty::GetUserType($arProperty["USER_TYPE"]);
			if(isset($arUserType["GetPublicViewHTML"]))
				$arUserTypeFormat = $arUserType["GetPublicViewHTML"];
		}

		static $CACHE = array("E"=>array(),"G"=>array());
		if($arUserTypeFormat)
		{
			if($arProperty["MULTIPLE"]=="N" || !is_array($arProperty["~VALUE"]))
				$arValues = array($arProperty["~VALUE"]);
			else
				$arValues = $arProperty["~VALUE"];
		}
		else
		{
			if(is_array($arProperty["VALUE"]))
				$arValues = $arProperty["VALUE"];
			else
				$arValues = array($arProperty["VALUE"]);
		}
		$arDisplayValue = array();
		$arFiles = array();
		$arLinkElements = array();
		$arLinkSections = array();
		foreach($arValues as $val)
		{
			if($arUserTypeFormat)
			{
				$arDisplayValue[] = call_user_func_array($arUserTypeFormat,
					array(
						$arProperty,
						array("VALUE" => $val),
						array(),
					));
			}
			elseif($arProperty["PROPERTY_TYPE"] == "E")
			{
				if(intval($val) > 0)
				{
					if(!isset($CACHE["E"][$val]))
					{
						//USED TO GET "LINKED" ELEMENTS
						$arLinkFilter = array (
							"ID" => $val,
							"ACTIVE" => "Y",
							"ACTIVE_DATE" => "Y",
							"CHECK_PERMISSIONS" => "Y",
						);
						$rsLink = CIBlockElement::GetList(
							array(),
							$arLinkFilter,
							false,
							false,
							array("ID","IBLOCK_ID","NAME","DETAIL_PAGE_URL", "PREVIEW_PICTURE", "DETAIL_PUCTURE")
						);
						$CACHE["E"][$val] = $rsLink->GetNext();
					}
					if(is_array($CACHE["E"][$val]))
					{
						$arDisplayValue[]='<a href="'.$CACHE["E"][$val]["DETAIL_PAGE_URL"].'">'.$CACHE["E"][$val]["NAME"].'</a>';
						$arLinkElements[$val] = $CACHE["E"][$val];
					}
				}
			}
			elseif($arProperty["PROPERTY_TYPE"] == "G")
			{
				if(intval($val) > 0)
				{
					if(!isset($CACHE["G"][$val]))
					{
						//USED TO GET SECTIONS NAMES
						$arSectionFilter = array (
							"ID" => $val,
						);
						$rsSection = CIBlockSection::GetList(
							array(),
							$arSectionFilter,
							false,
							array("ID", "IBLOCK_ID", "NAME", "SECTION_PAGE_URL", "PICTURE", "DETAIL_PICTURE")
						);
						$CACHE["G"][$val] = $rsSection->GetNext();
					}
					if(is_array($CACHE["G"][$val]))
					{
						$arDisplayValue[]='<a href="'.$CACHE["G"][$val]["SECTION_PAGE_URL"].'">'.$CACHE["G"][$val]["NAME"].'</a>';
						$arLinkSections[$val] = $CACHE["G"][$val];
					}
				}
			}
			elseif($arProperty["PROPERTY_TYPE"]=="L")
			{
				$arDisplayValue[] = $val;
			}
			elseif($arProperty["PROPERTY_TYPE"]=="F")
			{
				if($arFile = CFile::GetFileArray($val))
				{
					$arFiles[] = $arFile;
					if($installedStatictic)
						$arDisplayValue[] =  '<a href="'.htmlspecialcharsbx("/bitrix/redirect.php?event1=".urlencode($event1)."&event2=".urlencode($arFile["SRC"])."&event3=".urlencode($arFile["ORIGINAL_NAME"])."&goto=".urlencode($arFile["SRC"])).'">'.GetMessage('IBLOCK_DOWNLOAD').'</a>';
					else
						$arDisplayValue[] =  '<a href="'.htmlspecialcharsbx($arFile["SRC"]).'">'.GetMessage('IBLOCK_DOWNLOAD').'</a>';
				}
			}
			else
			{
				$trimmed = trim($val);
				if(strpos($trimmed, "http")===0)
				{
					if($installedStatictic)
						$arDisplayValue[] =  '<a href="'.htmlspecialcharsbx("/bitrix/redirect.php?event1=".urlencode($event1)."&event2=".urlencode($trimmed)."&event3=".urlencode($arItem["NAME"])."&goto=".urlencode($trimmed)).'">'.$trimmed.'</a>';
					else
						$arDisplayValue[] =  '<a href="'.htmlspecialcharsbx($trimmed).'">'.$trimmed.'</a>';
				}
				elseif(strpos($trimmed, "www")===0)
				{
					if($installedStatictic)
						$arDisplayValue[] =  '<a href="'.htmlspecialcharsbx("/bitrix/redirect.php?event1=".urlencode($event1)."&event2=".urlencode("http://".$trimmed)."&event3=".urlencode($arItem["NAME"])."&goto=".urlencode("http://".$trimmed)).'">'.$trimmed.'</a>';
					else
						$arDisplayValue[] =  '<a href="'.htmlspecialcharsbx("http://".$val).'">'.$val.'</a>';
				}
				else
					$arDisplayValue[] = $val;
			}
		}

		if(count($arDisplayValue)==1)
			$arProperty["DISPLAY_VALUE"] = $arDisplayValue[0];
		elseif(count($arDisplayValue)>1)
			$arProperty["DISPLAY_VALUE"] = $arDisplayValue;
		else
			$arProperty["DISPLAY_VALUE"] = false;

		if ($arProperty["PROPERTY_TYPE"]=="F")
		{
			if(count($arFiles)==1)
				$arProperty["FILE_VALUE"] = $arFiles[0];
			elseif(count($arFiles)>1)
				$arProperty["FILE_VALUE"] = $arFiles;
			else
				$arProperty["FILE_VALUE"] = false;
		}
		elseif ($arProperty['PROPERTY_TYPE'] == 'E')
		{
			$arProperty['LINK_ELEMENT_VALUE'] = (!empty($arLinkElements) ? $arLinkElements : false);
		}
		elseif ($arProperty['PROPERTY_TYPE'] == 'G')
		{
			$arProperty['LINK_SECTION_VALUE'] = (!empty($arLinkSections) ? $arLinkSections : false);
		}

		return $arProperty;
	}

	/**
	 * @param string $format
	 * @param int $timestamp
	 * @return string
	 */

	/**
	 *
	 *
	 *
	 *
	 *
	 * @return mixed
	 *
	 *
	 * <h4>Example</h4>
	 * <pre>
	 * <br><br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4>
	 * <a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockformatproperties/dateformat.php
	 * @author Bitrix
	 */
	public static function DateFormat($format, $timestamp)
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
}
?>