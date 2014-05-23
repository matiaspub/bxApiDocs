<?
IncludeModuleLangFile(__FILE__);

class CEventIBlock
{
	function MakeIBlockObject()
	{
		$obj = new CEventIBlock;
		return $obj;
	}

	function GetFilter()
	{
		$arFilter = array();
		$res = CIBlock::GetList(
			Array(),
			Array(
				'ACTIVE'=>'Y',
			)
		);

		while($ar_res = $res->Fetch())
		{
			$arIblock = CIBlock::GetArrayByID($ar_res['ID']);
			if (
				$arIblock["FIELDS"]["LOG_SECTION_ADD"]["IS_REQUIRED"] == "Y"
				|| $arIblock["FIELDS"]["LOG_SECTION_EDIT"]["IS_REQUIRED"] == "Y"
				|| $arIblock["FIELDS"]["LOG_SECTION_DELETE"]["IS_REQUIRED"] == "Y"
				|| $arIblock["FIELDS"]["LOG_ELEMENT_ADD"]["IS_REQUIRED"] == "Y"
				|| $arIblock["FIELDS"]["LOG_ELEMENT_EDIT"]["IS_REQUIRED"] == "Y"
				|| $arIblock["FIELDS"]["LOG_ELEMENT_DELETE"]["IS_REQUIRED"] == "Y"
			)
			{
				$arFilter[$ar_res["ID"]] = $ar_res["NAME"]." (".$ar_res["LID"].")";
			}
		}

		if (COption::GetOptionString("iblock", "event_log_iblock", "N") === "Y")
			$arFilter["IBLOCK"] = GetMessage("LOG_IBLOCK_FILTER");

		return  $arFilter;
	}
	function GetAuditTypes()
	{
		AddEventHandler("main", "GetAuditTypesIblock", array("CAllIBlock", "GetAuditTypes"));
		$db_events = GetModuleEvents("main", "GetAuditTypesIblock");
		while($arEvent = $db_events->Fetch())
		{
			$AuditTypes = ExecuteModuleEventEx($arEvent);
		}
		return $AuditTypes;
	}

	function GetEventInfo($row, $arParams, $arUser, $arResult)
	{
		$DESCRIPTION = unserialize($row['DESCRIPTION']);

		$IblockURL = "";
		if (strpos($row['AUDIT_TYPE_ID'], "SECTION") !== false)
		{
			if (isset($DESCRIPTION["ID"]))
			{
				$rsSection = CIBlockSection::GetList(array(), array("=ID"=>$DESCRIPTION["ID"]), false,  array("SECTION_PAGE_URL"));
				if ($arSection = $rsSection->GetNext())
					$IblockURL = $arSection["SECTION_PAGE_URL"];
			}
		}
		elseif (strpos($row['AUDIT_TYPE_ID'], "ELEMENT") !== false)
		{
			if (isset($DESCRIPTION["ID"]))
			{
				$rsElement = CIBlockElement::GetList(array(), array("=ID"=>$DESCRIPTION["ID"]), false, false, array("DETAIL_PAGE_URL"));
				if ($arElement = $rsElement->GetNext())
					$IblockURL = $arElement["DETAIL_PAGE_URL"];
			}
		}
		else
		{
			$rsElement = CIBlock::GetList(array(), array("=ID"=>$row["ITEM_ID"]), false);
			if ($arElement = $rsElement->GetNext())
				$IblockURL = SITE_DIR."bitrix/admin/iblock_edit.php?ID=".$row["ITEM_ID"]."&type=".$arElement["IBLOCK_TYPE_ID"];
		}

		if($IblockURL)
		{
			$IblockURL = str_replace(
				"#USER_ID#",
				urlencode($DESCRIPTION["USER_ID"]),
				$IblockURL
			);
		}


		if (isset($DESCRIPTION["IBLOCK_PAGE_URL"]))
		{
			$DescriptionURL = str_replace(
				"#USER_ID#",
				urlencode($DESCRIPTION["USER_ID"]),
				$DESCRIPTION["IBLOCK_PAGE_URL"]
			);
			$resIblock = '<a href="'.$DescriptionURL.'">'.$arResult[$row['ITEM_ID']].'</a>';
		}
		else
		{
			$resIblock = $arResult[$row['ITEM_ID']];
		}

		switch($row['AUDIT_TYPE_ID'])
		{
			case "IBLOCK_SECTION_ADD":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_ADD", array("#ITEM#" => $DESCRIPTION['SECTION_NAME']));
				break;
			case "IBLOCK_SECTION_EDIT":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_EDIT", array("#ITEM#" => $DESCRIPTION['SECTION_NAME']));
				break;
			case "IBLOCK_SECTION_DELETE":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_DELETE", array("#ITEM#" => $DESCRIPTION['SECTION_NAME']));
				break;
			case "IBLOCK_ELEMENT_ADD":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_ADD", array("#ITEM#" => $DESCRIPTION['ELEMENT_NAME']));
				break;
			case "IBLOCK_ELEMENT_EDIT":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_EDIT", array("#ITEM#" => $DESCRIPTION['ELEMENT_NAME']));
				break;
			case "IBLOCK_ELEMENT_DELETE":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_DELETE", array("#ITEM#" => $DESCRIPTION['ELEMENT_NAME']));
				break;
			case "IBLOCK_ADD":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_ADD", array("#ITEM#" => GetMessage("LOG_IBLOCK")));
				break;
			case "IBLOCK_EDIT":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_EDIT", array("#ITEM#" => GetMessage("LOG_IBLOCK")));
				break;
			case "IBLOCK_DELETE":
				$EventPrint = GetMessage("LOG_IBLOCK_ITEM_DELETE", array("#ITEM#" => GetMessage("LOG_IBLOCK")));
				break;
			default:
				$EventPrint = "";
				break;
		}

		return array(
			"eventType" => $EventPrint,
			"eventName" => $DESCRIPTION['NAME'],
			"eventURL" => $IblockURL,
			"pageURL" => $resIblock
		);
	}

	function GetFilterSQL($var)
	{
		if (is_array($var))
			foreach($var as $key => $val)
				if ($val == "IBLOCK")
				{
					$ar[] = array("AUDIT_TYPE_ID" => "IBLOCK_ADD");
					$ar[] = array("AUDIT_TYPE_ID" => "IBLOCK_EDIT");
					$ar[] = array("AUDIT_TYPE_ID" => "IBLOCK_DELETE");
				}
				else
					$ar[] = array("MODULE_ID" => "iblock", "ITEM_ID" => $val);
		return $ar;
	}
}

?>