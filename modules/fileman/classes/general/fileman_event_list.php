<?
IncludeModuleLangFile(__FILE__);

class CEventFileman
{
	public static function MakeFilemanObject()
	{
		$obj = new CEventFileman;
		return $obj;
	}

	public static function GetFilter()
	{
		$arFilter = array();
		$module_id = 'fileman';
		if(COption::GetOptionString($module_id, "log_page", "Y")=="Y")
			$arFilter["PAGE_EDIT"] = GetMessage("LOG_TYPE_PAGE_EDIT");
			
		if(COption::GetOptionString($module_id, "log_menu", "Y")=="Y")
			$arFilter["MENU_EDIT"] = GetMessage("LOG_TYPE_MENU_EDIT");
		
		return  $arFilter;
	}
	
	public static function GetAuditTypes()
	{
		return array(
			"PAGE_EDIT" => "[PAGE_EDIT] ".GetMessage("LOG_TYPE_PAGE_EDIT"), 
			"PAGE_ADD" => "[PAGE_ADD] ".GetMessage("LOG_TYPE_PAGE_ADD"),
			"PAGE_DELETE" => "[PAGE_DELETE] ".GetMessage("LOG_TYPE_PAGE_DELETE"),
			"MENU_EDIT" => "[MENU_EDIT] ".GetMessage("LOG_TYPE_MENU_EDIT"), 
			"MENU_ADD" => "[MENU_ADD] ".GetMessage("LOG_TYPE_MENU_ADD"),
			"MENU_DELETE" => "[MENU_DELETE] ".GetMessage("LOG_TYPE_MENU_DEELETE"),
			"FILE_ADD" => "[FILE_ADD] ".GetMessage("LOG_TYPE_FILE_ADD"),
			"FILE_EDIT" => "[FILE_EDIT] ".GetMessage("LOG_TYPE_FILE_EDIT"),
			"FILE_DELETE" => "[FILE_DELETE] ".GetMessage("LOG_TYPE_FILE_DEELETE"),
			"FILE_MOVE" => "[FILE_MOVE] ".GetMessage("LOG_TYPE_FILE_MOVE"),
			"FILE_COPY" => "[FILE_COPY] ".GetMessage("LOG_TYPE_FILE_COPY"),
			"FILE_RENAME" => "[FILE_RENAME] ".GetMessage("LOG_TYPE_FILE_RENAME"),
			"SECTION_ADD" => "[SECTION_ADD] ".GetMessage("LOG_TYPE_SECTION_ADD"),
			"SECTION_EDIT" => "[SECTION_EDIT] ".GetMessage("LOG_TYPE_SECTION_EDIT"),
			"SECTION_DELETE" => "[SECTION_DELETE] ".GetMessage("LOG_TYPE_SECTION_DELETE"),
			"SECTION_MOVE" => "[SECTION_MOVE] ".GetMessage("LOG_TYPE_SECTION_MOVE"),
			"SECTION_RENAME" => "[SECTION_RENAME] ".GetMessage("LOG_TYPE_SECTION_RENAME"),
			"SECTION_COPY" => "[SECTION_COPY] ".GetMessage("LOG_TYPE_SECTION_COPY"),
		);         
	}
	
	public static function GetEventInfo($row, $arParams, $arUser)
	{
		$site = CFileMan::__CheckSite($site);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);		
		$DESCRIPTION = unserialize($row['DESCRIPTION']);
		
		if (empty($DESCRIPTION['path']))
		{ 
			$DESCRIPTION['path'] = $_SERVER['HTTP_HOST'];
			$fileURL = SITE_DIR;
		}
		else
		{						
			if ((is_file($DOC_ROOT."/".$DESCRIPTION['path']) || is_dir($DOC_ROOT."/".$DESCRIPTION['path'])) && !strrpos($DESCRIPTION['path'], " "))
				$fileURL = SITE_DIR.$DESCRIPTION['path'];
		}
		
		$EventName = $DESCRIPTION['path'];	
		switch($row['AUDIT_TYPE_ID'])
		{	
			case "PAGE_ADD":
				$EventPrint = GetMessage("LOG_PAGE_ADD");
				break;
			case "PAGE_EDIT":
				$EventPrint = GetMessage("LOG_PAGE_EDIT");
				break;
			case "PAGE_DELETE":
				$EventPrint = GetMessage("LOG_PAGE_DELETE");
				break;
			case "MENU_ADD":
				$EventPrint = GetMessage("LOG_MENU_ADD", array("#MENU#" => $DESCRIPTION['menu_name']));
				break;
			case "MENU_EDIT":
				$EventPrint = GetMessage("LOG_MENU_EDIT", array("#MENU#" => $DESCRIPTION['menu_name']));
				break;
			case "MENU_DELETE":
				$EventPrint = GetMessage("LOG_MENU_DELETE", array("#MENU#" => $DESCRIPTION['menu_name']));
				break;
			case "FILE_ADD":
				$EventPrint = GetMessage("LOG_FILE_ADD");
				break;	
			case "FILE_EDIT":
				$EventPrint = GetMessage("LOG_FILE_EDIT");
				break;
			case "FILE_DELETE":
				$EventPrint = GetMessage("LOG_FILE_DELETE", array("#FILENAME#" => $DESCRIPTION['file_name']));
				break;
			case "FILE_MOVE":
				$EventPrint = GetMessage("LOG_FILE_MOVE", array("#SECTION#" => $DESCRIPTION["copy_to"]));
				break;	
			case "FILE_COPY":
				$EventPrint = GetMessage("LOG_FILE_COPY", array("#SECTION#" => $DESCRIPTION["copy_to"]));
				break;	
			case "FILE_RENAME":
				$EventPrint = GetMessage("LOG_FILE_RENAME");
				break;	
			case "SECTION_ADD":
				$EventPrint = GetMessage("LOG_SECTION_ADD");
				break;
			case "SECTION_EDIT":
				$EventPrint = GetMessage("LOG_SECTION_EDIT");
				break;
			case "SECTION_DELETE":
				$EventPrint = GetMessage("LOG_SECTION_DELETE");
				break;	
			case "SECTION_MOVE":
				$EventPrint = GetMessage("LOG_SECTION_MOVE", array("#SECTION#" => $DESCRIPTION["copy_to"]));
				break;	
			case "SECTION_COPY":
				$EventPrint = GetMessage("LOG_SECTION_COPY", array("#SECTION#" => $DESCRIPTION["copy_to"]));
				break;
			case "SECTION_RENAME":
				$EventPrint = GetMessage("LOG_SECTION_RENAME");
				break;	
		}
		
		return array(
					"eventType" => $EventPrint,
					"eventName" => $EventName,
					"eventURL" => $fileURL
				);     
	}
	
	public static function GetFilterSQL($var)
	{
		if (is_array($var))
			foreach($var as $key => $val)
			{
				if ($val == "PAGE_EDIT"):				
					$ar[] = array("AUDIT_TYPE_ID" => "PAGE_ADD");	
					$ar[] = array("AUDIT_TYPE_ID" => "PAGE_EDIT");
					$ar[] = array("AUDIT_TYPE_ID" => "PAGE_DELETE");
					$ar[] = array("AUDIT_TYPE_ID" => "FILE_ADD");
					$ar[] = array("AUDIT_TYPE_ID" => "FILE_EDIT");
					$ar[] = array("AUDIT_TYPE_ID" => "FILE_DELETE");
					$ar[] = array("AUDIT_TYPE_ID" => "FILE_MOVE");
					$ar[] = array("AUDIT_TYPE_ID" => "FILE_COPY");
					$ar[] = array("AUDIT_TYPE_ID" => "FILE_RENAME");
					$ar[] = array("AUDIT_TYPE_ID" => "SECTION_ADD");
					$ar[] = array("AUDIT_TYPE_ID" => "SECTION_EDIT");
					$ar[] = array("AUDIT_TYPE_ID" => "SECTION_DELETE");
					$ar[] = array("AUDIT_TYPE_ID" => "SECTION_MOVE");
					$ar[] = array("AUDIT_TYPE_ID" => "SECTION_COPY");
					$ar[] = array("AUDIT_TYPE_ID" => "SECTION_RENAME");
				elseif ($val == "MENU_EDIT"):
					$ar[] = array("AUDIT_TYPE_ID" => "MENU_ITEM_ADD");
					$ar[] = array("AUDIT_TYPE_ID" => "MENU_ITEM_EDIT");
					$ar[] = array("AUDIT_TYPE_ID" => "MENU_ITEM_DELETE");	
					$ar[] = array("AUDIT_TYPE_ID" => "MENU_ADD");
					$ar[] = array("AUDIT_TYPE_ID" => "MENU_EDIT");
					$ar[] = array("AUDIT_TYPE_ID" => "MENU_DELETE");				
				else:
					$ar[] = array("AUDIT_TYPE_ID" => $val);
				endif;
				
			}
		return $ar;
	}
}
?>