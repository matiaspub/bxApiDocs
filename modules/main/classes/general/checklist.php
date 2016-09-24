<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

IncludeModuleLangFile(__FILE__);

class CCheckList
{
	public function __construct($ID = false)
	{
		$this->current_result = false;
		$this->started = false;
		$this->report_id = false;
		$this->report_info = "";
		$this->checklist_path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/checklist_structure.php";
		if (file_exists($this->checklist_path))
			$arCheckList = include($this->checklist_path);
		else
			return;
		//bind custom checklist
		foreach(GetModuleEvents('main', 'OnCheckListGet', true) as $arEvent)
		{
			$arCustomChecklist = ExecuteModuleEventEx($arEvent, array($arCheckList));

			if (is_array($arCustomChecklist["CATEGORIES"]) && count($arCustomChecklist["CATEGORIES"])>0)
				foreach($arCustomChecklist["CATEGORIES"] as $section_id => $arSectionFields)
				{
					if (!$arCheckList["CATEGORIES"][$section_id])
						$arCheckList["CATEGORIES"][$section_id] = $arSectionFields;
				}
			if (is_array($arCustomChecklist["POINTS"]) && count($arCustomChecklist["POINTS"])>0)
			foreach($arCustomChecklist["POINTS"] as $point_id => $arPointFields)
			{
				$parent = $arCustomChecklist["POINTS"][$point_id]["PARENT"];
				if (!$arCheckList["POINTS"][$point_id] && array_key_exists($parent, $arCheckList["CATEGORIES"]))
					$arCheckList["POINTS"][$point_id] = $arPointFields;
			}
		}
		//end bind custom checklist
		$this->checklist = $arCheckList;
		$arFilter["REPORT"] = "N";
		if (intval($ID)>0)
		{
			$arFilter["ID"] = $ID;
			$arFilter["REPORT"] = "Y";
		}

		$dbResult = CCheckListResult::GetList(array(), $arFilter);
		if ($arCurrentResult = $dbResult->Fetch())
		{
			$this->current_result = unserialize($arCurrentResult["STATE"]);
			if (intval($ID)>0)
			{
				$this->report_id = intval($ID);
				unset($arCurrentResult["STATE"]);
				$this->report_info = $arCurrentResult;
			}

			foreach($arCheckList["POINTS"] as $key => $arFields)
			{
				if (!$this->current_result[$key])
				{
					if ($this->report_id)
						unset($this->checklist["POINTS"][$key]);
					else
						$this->current_result[$key] = array(
							"STATUS" => "W");
				}
				////$this->current_result[$key] = array("STATUS" => "A");
			}
		}
		if ($this->current_result != false && $this->report_id == false)
			$this->started = true;
	}

	public function GetSections()
	{
		$arSections = $this->checklist["CATEGORIES"];
		$arResult = array();
		foreach($arSections as $key => $arFields)
		{
			$arResult[$key] = array_merge($this->GetDescription($key), $arFields);
			$arResult[$key]["STATS"] = $this->GetSectionStat($key);
		}
		return $arResult;
	}

	//getting sections statistic
	public function GetSectionStat($ID = false)
	{
		$arResult = array(
			"CHECK" => 0,
			"CHECK_R" => 0,
			"FAILED" => 0,
			"WAITING" => 0,
			"TOTAL" => 0,
			"REQUIRE_CHECK" => 0,
			"REQUIRE_SKIP" => 0,
			"NOT_REQUIRE_CHECK"=>0,
			"NOT_REQUIRE_SKIP"=>0,
			"CHECKED" => "N",
			"REQUIRE" => 0,
		);

		if (($ID!=false && array_key_exists($ID, $this->checklist["CATEGORIES"])) || $ID == false)
		{
			$arPoints = $this->GetPoints($ID);
			$arSections = $this->checklist["CATEGORIES"];
			if (count($arPoints)>0)
				foreach ($arPoints as $arPointFields)
				{
					if ($arPointFields["STATE"]["STATUS"] == "A")
					{
						$arResult["CHECK"]++;
						if (isset($arPointFields['REQUIRE']) && $arPointFields['REQUIRE']=='Y')
							$arResult["CHECK_R"]++;
					}
					if ($arPointFields["STATE"]["STATUS"] == "F")
						$arResult["FAILED"]++;
					if ($arPointFields["STATE"]["STATUS"] == "W")
						$arResult["WAITING"]++;
					if ($arPointFields["REQUIRE"] == "Y")
					{
						$arResult["REQUIRE"]++;
						if ($arPointFields["STATE"]["STATUS"] == "A")
							$arResult["REQUIRE_CHECK"]++;
						elseif($arPointFields["STATE"]["STATUS"] == "S")
							$arResult["REQUIRE_SKIP"]++;
					}
					else
					{
						if ($arPointFields["STATE"]["STATUS"] == "A")
							$arResult["NOT_REQUIRE_CHECK"]++;
						elseif($arPointFields["STATE"]["STATUS"] == "S")
							$arResult["NOT_REQUIRE_SKIP"]++;
					}
				}
			$arResult["TOTAL"] = count($arPoints);

			if ($ID)
			{
				foreach($arSections as $key => $arFields)
				{
					if ($arFields["PARENT"] == $ID)
					{
						$arSubSectionStat = $this->GetSectionStat($key);
						$arResult["TOTAL"]+=$arSubSectionStat["TOTAL"];
						$arResult["CHECK"]+=$arSubSectionStat["CHECK"];
						$arResult["FAILED"]+=$arSubSectionStat["FAILED"];
						$arResult["WAITING"]+=$arSubSectionStat["WAITING"];
						$arResult["REQUIRE"]+=$arSubSectionStat["REQUIRE"];
						$arResult["REQUIRE_CHECK"]+=$arSubSectionStat["REQUIRE_CHECK"];
						$arResult["REQUIRE_SKIP"]+=$arSubSectionStat["REQUIRE_SKIP"];
					}
				}
			}
			if ((($arResult["REQUIRE"]>0 && $arResult["FAILED"] == 0)&&($arResult["REQUIRE"] == $arResult["REQUIRE_CHECK"] || ($arResult["REQUIRE_SKIP"]>0 && $arResult["REQUIRE"] == $arResult["REQUIRE_CHECK"])))
				||(($arResult["REQUIRE"] == 0) && ($arResult["FAILED"] == 0)&&($arResult["TOTAL"]>0))
				||($arResult["CHECK"] == $arResult["TOTAL"] && $arResult["TOTAL"]>0)
			)
					$arResult["CHECKED"] = "Y";

		}

		return $arResult;
	}

	public function GetPoints($arSectionCode = false)
	{
		$arCheckList = $this->GetCurrentState();
		$arResult = array();
		if (is_array($arCheckList) && !empty($arCheckList))
		foreach ($arCheckList["POINTS"] as $key => $arFields)
		{
			$arFields = array_merge($this->GetDescription($key), $arFields);

			if ($arFields["PARENT"] == $arSectionCode || $arSectionCode  == false)
			$arResult[$key] = $arFields;
			if ($arResult[$key]["STATE"]['COMMENTS'] && is_array($arResult[$key]["STATE"]['COMMENTS']))
				$arResult[$key]["STATE"]['COMMENTS_COUNT'] = count($arResult[$key]["STATE"]['COMMENTS']);

		}

		return $arResult;
	}

	public function GetStructure()
	{ //build checklist stucture with section statistic & status info
		$arSections = $this->GetSections();
		foreach ($arSections as $key => $arSectionFields)
		{
			if (!$arSectionFields["CATEGORIES"])
			{
				$arSections[$key]["CATEGORIES"] = array();
				$arSectionFields["CATEGORIES"] = array();
			}
			if (!$arSectionFields["PARENT"])
			{
				$arSections[$key]["POINTS"] = $this->GetPoints($key);
				$arSections[$key] = array_merge($arSections[$key], $this->GetSectionStat($key));
				continue;
			}

			$arFields = $arSectionFields;
			$arFields["POINTS"] = $this->GetPoints($key);
			$arFields = array_merge($arFields, $this->GetSectionStat($key));
			$arSections[$arFields["PARENT"]]["CATEGORIES"][$key] = $arFields;
			unset($arSections[$key]);
		}

		$arResult["STRUCTURE"] = $arSections;
		$arResult["STAT"] = $this->GetSectionStat();
		return $arResult;
	}



	public function PointUpdate($arTestID, $arPointFields = array())
	{//update test info in the object property
		if (!$arTestID || empty($arPointFields) || $this->report_id)
			return false;
		$currentFields = $this->current_result[$arTestID];
		if (!$arPointFields["STATUS"])
			$arPointFields["STATUS"] = $currentFields["STATUS"];
		$this->current_result[$arTestID] = $arPointFields;

		return true;
	}

	public static function GetDescription($ID)
	{//getting description of sections and points
		$file = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/".LANG."/admin/checklist/".$ID.".html";
		$arHowTo = "";
		if(file_exists($file))
		{
			if ($f = @fopen($file, "r"))
				$arHowTo = fread($f, filesize($file));
		}
		$arDesc = array(
			"NAME" => GetMessage("CL_".$ID),
			"DESC" => GetMessage("CL_".$ID."_DESC"),
			"AUTOTEST_DESC" => GetMessage("CL_".$ID."_AUTOTEST_DESC"),
			"HOWTO" => (strlen($arHowTo)>0)?$arHowTo:"",
			"LINKS" => GetMessage("CL_".$ID."_LINKS")
		);

		return $arDesc;
	}


	public function Save()
	{//saving current state
		if (!$this->report_id)
		{
			$res = CCheckListResult::Save(array("STATE" => $this->current_result));
			if(!is_array($res))
				CUserOptions::SetOption("checklist", "autotest_start", "Y", true, false);
			return $res;
		}
		return false;
	}

	public function GetCurrentState()
	{//getting current state
		$arCheckList = $this->checklist;
		$currentState = $this->current_result;
		foreach ($arCheckList["POINTS"] as $testID => $arTestFields)
		{
			if ($currentState[$testID])
				$arCheckList["POINTS"][$testID]["STATE"] = $currentState[$testID];
			else
				$arCheckList["POINTS"][$testID]["STATE"] = array(
					"STATUS" => "W"
				);
		}

		return $arCheckList;
	}

	public function AutoCheck($arTestID, $arParams = array())
	{//execute point autotest
		$arParams["TEST_ID"] = $arTestID;
		$arPoints = $this->GetPoints();
		$arPoint = $arPoints[$arTestID];
		$result = false;
		if (!$arPoint || $arPoint["AUTO"] !="Y")
			return false;
		if (is_array($arPoints[$arTestID]["PARAMS"]))
			$arParams = array_merge($arParams, $arPoints[$arTestID]["PARAMS"]);
		$arClass = 	$arPoint["CLASS_NAME"];
		$arMethod = $arPoint["METHOD_NAME"];

		if (strlen($arPoint["FILE_PATH"])>0 && file_exists($_SERVER["DOCUMENT_ROOT"].$arPoint["FILE_PATH"]))
			include($_SERVER["DOCUMENT_ROOT"].$arPoint["FILE_PATH"]);

		if(is_callable(array($arClass, $arMethod)))
			$result = call_user_func_array(array($arClass, $arMethod), array("PARAM" => $arParams));

		$arResult = array();
		if ($result && is_array($result))
		{
			if (array_key_exists("STATUS", $result))
			{
				$arFields["STATUS"] = "F";
				if ($result['STATUS'] == "true")
					$arFields["STATUS"] = "A";

				$arFields["COMMENTS"] = $arPoint["STATE"]["COMMENTS"];
				$arFields["COMMENTS"]["SYSTEM"] = array();
				if (array_key_exists("PREVIEW", $result["MESSAGE"]))
					$arFields["COMMENTS"]["SYSTEM"]["PREVIEW"]= $result["MESSAGE"]["PREVIEW"];
				if (array_key_exists("DETAIL", $result["MESSAGE"]))
					$arFields["COMMENTS"]["SYSTEM"]["DETAIL"]= $result["MESSAGE"]["DETAIL"];

				if ($this->PointUpdate($arTestID, $arFields))
					if ($this->Save())
					{
						$arResult = array(
							"STATUS" => $arFields["STATUS"],
							"COMMENTS_COUNT" => count($arFields["COMMENTS"]),
							"ERROR" => $result["ERROR"],
							"SYSTEM_MESSAGE" => $arFields["COMMENTS"]["SYSTEM"]
						);

					}
			}
			elseif($result["IN_PROGRESS"] == "Y")
			{
				$arResult= array(
						"IN_PROGRESS" => "Y",
						"PERCENT" => $result["PERCENT"]
					);
			}
		}
		else
			$arResult = array("STATUS" => "W");

		return $arResult;
	}

	public function AddReport($arReportFields = array(), $errorCheck = false)
	{//saving current state to a report
		if ($this->report_id)
			return false;

		if ($errorCheck && !$arReportFields["TESTER"] && !$arReportFields["COMPANY_NAME"])
			return array("ERROR" => GetMessage("EMPTY_NAME"));

		$arStats = $this->GetSectionStat();
		$arFields = array(
				"TESTER" => $arReportFields["TESTER"],
				"COMPANY_NAME" => $arReportFields["COMPANY_NAME"],
				"PHONE" => $arReportFields["PHONE"],
				"EMAIL" => $arReportFields["EMAIL"],
				"PICTURE" => $arReportFields["PICTURE"],
				"REPORT_COMMENT" => $arReportFields["COMMENT"],
				"STATE" => $this->current_result,
				"TOTAL" => $arStats["TOTAL"],
				"SUCCESS" => $arStats["CHECK"],
				"SUCCESS_R" => $arStats["CHECK_R"],
				"FAILED" => $arStats["FAILED"],
				"PENDING" => $arStats["WAITING"],
				"REPORT" => true
			);

			$arReportID = CCheckListResult::Save($arFields);
			if ($arReportID>0)
			{
				$dbres = CCheckListResult::GetList(array(), array("REPORT" => "N"));
				if ($res = $dbres->Fetch())
				{
					CCheckListResult::Delete($res["ID"]);
					CUserOptions::SetOption("checklist", "autotest_start", "N", true, false);
				}
				return $arReportID;
			}

			return false;
	}

	public function GetReportInfo()
	{//getting report information
		if ($this->report_id)
		{
			$checklist = new CCheckList($this->report_id);
			if ($checklist->current_result == false)
				return false;
			$arResult = $checklist->GetStructure();

			//removing empty sections
			/*foreach($arResult["STRUCTURE"] as $key => $rFields)
			{
				$arsCategories = array();
				foreach ($rFields["CATEGORIES"] as $skey => $sFields)
				{
					if (count($sFields["POINTS"])>0)
						$arsCategories[$skey] = $sFields;
				}
				if (count($arsCategories)>0)
				{
					$rFields["CATEGORIES"] = $arsCategories;
					$arTmpStructure[$key] = $rFields;
				}
			}
			$arResult["STRUCTURE"] = $arTmpStructure;*/
			$arResult["POINTS"] = $checklist->GetPoints();
			$arResult["INFO"] = $checklist->report_info;

			return $arResult;
		}
		return false;
	}
}

class CCheckListResult
{
	public static function Save($arFields = array())
	{
		global $DB;

		$arResult = array();
		if ($arFields["STATE"] && is_array($arFields["STATE"]))
			$arFields["STATE"] = serialize($arFields["STATE"]);
		else
			$arResult["ERRORS"][] = GetMessage("ERROR_DATA_RECEIVED");

		$currentState = false;
		if ($arFields["REPORT"] != true)
		{
			$arFields["REPORT"] = "N";
			$db_result = $DB->Query("SELECT ID FROM b_checklist WHERE REPORT <> 'Y'");
			$currentState = $db_result->Fetch();
		}
		else
			$arFields["REPORT"] = "Y";

		if ($arResult["ERRORS"])
			return $arResult;

		if ($currentState)
		{
			$strUpdate = $DB->PrepareUpdate("b_checklist", $arFields);
			$strSql = "UPDATE b_checklist SET ".$strUpdate." WHERE ID=".$currentState["ID"];
		}
		else
		{
			$arInsert = $DB->PrepareInsert("b_checklist", $arFields);
			$strSql ="INSERT INTO b_checklist(".$arInsert[0].", DATE_CREATE) ".
					"VALUES(".$arInsert[1].", '".ConvertTimeStamp(mktime(), "FULL")."')";
		}

		$arBinds = array(
				"STATE" => $arFields["STATE"],
			);
		$arResult = $DB->QueryBind($strSql, $arBinds);

		return $arResult;

	}

	public static function GetList($arOrder = array(), $arFilter = array())
	{
		global $DB;

		$arSqlWhereStr = '';
		if (is_array($arFilter) && count($arFilter)>0)
		{
			$arSqlWhere = "";
			$arSqlFields=array("ID", "REPORT", "HIDDEN", "SENDED_TO_BITRIX");
			foreach($arFilter as $key => $value):
				if (in_array($key, $arSqlFields))
					$arSqlWhere[] = $key."='".$DB->ForSql($value)."'";
			endforeach;
			$arSqlWhereStr = GetFilterSqlSearch($arSqlWhere);
		}

		$strSql = "SELECT * FROM b_checklist";
		if ($arSqlWhereStr <> '')
			$strSql.= " WHERE ".$arSqlWhereStr;
			$strSql.= " ORDER BY ID desc";
		$arResult = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $arResult;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);

		$strUpdate = $DB->PrepareUpdate("b_checklist", $arFields);

		$strSql =
			"UPDATE b_checklist SET ".$strUpdate." WHERE ID = ".$ID." ";
		$DB->Query($strSql);
		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		if (!$ID>0)
			return false;
		$strSql = "DELETE FROM b_checklist where ID=".$ID;
		if (($arResult = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__)))
			return true;
		return false;
	}

}

class CAutoCheck
{
	public static function CheckCustomComponents($arParams)
	{
		$arResult["STATUS"] = false;
		$arComponentFolders = array(
			"/bitrix/components",
			"/local/components"
		);
		$components = array();
		foreach($arComponentFolders as $componentFolder)
		{
			if (file_exists($_SERVER['DOCUMENT_ROOT'].$componentFolder) && ($handle = opendir($_SERVER['DOCUMENT_ROOT'].$componentFolder)))
			{
				while (($file = readdir($handle)) !== false)
				{
					if ($file == "bitrix" || $file ==".." || $file == ".")
						continue;

					$dir = $componentFolder."/".$file;
					if (is_dir($_SERVER['DOCUMENT_ROOT'].$dir))
					{
						if(CComponentUtil::isComponent($dir))
						{
							$components[] = array(
								"path" => $dir,
								"name" => $file,
							);
						}
						elseif($comp_handle = opendir($_SERVER['DOCUMENT_ROOT'].$dir))
						{
							while (($subdir = readdir($comp_handle)) !== false)
							{
								if ($subdir == ".." || $subdir == "." || $subdir == ".svn")
									continue;

								if(CComponentUtil::isComponent($dir."/".$subdir))
								{
									$components[] = array(
										"path" => $dir."/".$subdir,
										"name" => $file.":".$subdir,
									);
								}
							}
							closedir($comp_handle);
						}
					}
				}
				closedir($handle);
			}
		}
		if ($arParams["ACTION"] == "FIND")
		{
			foreach($components as $component)
			{
				$arResult["MESSAGE"]["DETAIL"] .= $component["name"]." \n";
			}

			if (strlen($arResult["MESSAGE"]["DETAIL"]) == 0)
			{
				$arResult["MESSAGE"]["PREVIEW"] = GetMessage("CL_HAVE_NO_CUSTOM_COMPONENTS");
			}
			else
			{
				$arResult = array(
					"STATUS" => true,
					"MESSAGE" => array(
						"PREVIEW" => GetMessage("CL_HAVE_CUSTOM_COMPONENTS")." (".count($components).")",
						"DETAIL" => $arResult["MESSAGE"]["DETAIL"]
					)
				);
			}
		}
		else
		{
			foreach($components as $component)
			{
				$desc = $_SERVER['DOCUMENT_ROOT'].$component["path"]."/.description.php";
				if (!file_exists($desc) || filesize($desc) === 0)
					$arResult["MESSAGE"]["DETAIL"] .= GetMessage("CL_EMPTY_DESCRIPTION")." ".$component["name"]." \n";
			}

			if (strlen($arResult["MESSAGE"]["DETAIL"]) == 0)
			{
				$arResult["STATUS"] = true;
				$arResult["MESSAGE"]["PREVIEW"] = GetMessage("CL_HAVE_CUSTOM_COMPONENTS_DESC");
			}
			else
			{
				$arResult = array(
					"STATUS" => false,
					"MESSAGE" => array(
						"PREVIEW" => GetMessage("CL_ERROR_FOUND_SHORT"),
						"DETAIL" => $arResult["MESSAGE"]["DETAIL"]
					)
				);
			}
		}
		return $arResult;
	}

	public static function CheckBackup()
	{
		$arCount = 0;
		$arResult = array();
		$arResult["STATUS"] = false;
		$bMcrypt = function_exists('mcrypt_encrypt');
		$bBitrixCloud = $bMcrypt && CModule::IncludeModule('bitrixcloud') && CModule::IncludeModule('clouds');

		$site = CSite::GetSiteByFullPath(DOCUMENT_ROOT);
		$path = BX_ROOT."/backup";
		$arTmpFiles = array();
		$arFilter = array();
		GetDirList(array($site, $path), $arDir, $arTmpFiles, $arFilter, array('sort' => 'asc'), "F");

		foreach($arTmpFiles as $ar)
		{
			if (strpos($ar['NAME'], ".enc.gz") || strpos($ar['NAME'], ".tar.gz") || strpos($ar['NAME'], ".tar") || strpos($ar['NAME'], ".enc"))
				$arCount++;
		}

		if ($bBitrixCloud)
		{
			$backup = CBitrixCloudBackup::getInstance();
			try
			{
				foreach($backup->listFiles() as $ar)
				{
					if (strpos($ar['FILE_NAME'], ".enc.gz") || strpos($ar['FILE_NAME'], ".tar.gz") || strpos($ar['FILE_NAME'], ".tar") || strpos($ar['FILE_NAME'], ".enc"))
						$arCount++;
				}
			}
			catch (Exception $e)
			{
			}
		}
		if ($arCount>0)
		{
			$arResult["STATUS"] = true;
			$arResult["MESSAGE"]["PREVIEW"] = GetMessage("CL_FOUND_BACKUP", array("#count#" => $arCount));
		}
		else
		{
			$arResult["MESSAGE"]["PREVIEW"] = GetMessage("CL_NOT_FOUND_BACKUP");
		}
		return $arResult;
	}

	public static function CheckTemplates()
	{
		$arFolders = array(
			$_SERVER['DOCUMENT_ROOT']."/bitrix/templates",
			$_SERVER['DOCUMENT_ROOT']."/local/templates",
		);
		$arResult["STATUS"] = false;
		$arCount = 0;
		$arRequireFiles = array("header.php", "footer.php");
		$arFilter = array(".svn", ".", "..");
		$arMessage = '';
		foreach($arFolders as $folder)
		{
			if (file_exists($folder) && ($arTemplates = scandir($folder)))
			{
				foreach ($arTemplates as $dir)
				{
					$arTemplateFolder = $folder."/".$dir;
					if (in_array($dir, $arFilter) || !is_dir($arTemplateFolder))
						continue;
					$arRequireFilesTmp = $arRequireFiles;

					foreach($arRequireFilesTmp as $k => $file)
					{
						if (!file_exists($arTemplateFolder."/".$file))
						{
							$arMessage .= GetMessage("NOT_FOUND_FILE", array("#template#" => $dir, "#file_name#" => $file))."\n";
							unset($arRequireFilesTmp[$k]);
						}
					}

					if (in_array("header.php", $arRequireFilesTmp))
					{
						if($f = fopen($arTemplateFolder."/header.php", "r"))
						{
							$arHeader = fread($f, filesize($arTemplateFolder."/header.php"));

							preg_match('/\$APPLICATION->ShowHead\(/im', $arHeader, $arShowHead);
							preg_match('/\$APPLICATION->ShowTitle\(/im', $arHeader, $arShowTitle);
							preg_match('/\$APPLICATION->ShowPanel\(/im', $arHeader, $arShowPanel);
							if (count($arShowHead) == 0)
							{
								preg_match_all('/\$APPLICATION->(ShowCSS|ShowHeadScripts|ShowHeadStrings)\(/im', $arHeader, $arShowHead);
								if (!$arShowHead[0] || count($arShowHead[0]) != 3)
									$arMessage .= GetMessage("NO_SHOWHEAD", array("#template#" => $dir))."\n";
							}
							if (!in_array($dir, array('empty')) && count($arShowTitle) == 0)
								$arMessage .= GetMessage("NO_SHOWTITLE", array("#template#" => $dir))."\n";
							if (!in_array($dir, array('mobile_app', 'desktop_app', 'empty', 'learning_10_0_0')) && count($arShowPanel) == 0)
								$arMessage .= GetMessage("NO_SHOWPANEL", array("#template#" => $dir))."\n";
						}
					}

					$arCount++;
				}
			}
		}

		if ($arCount == 0)
		{
			$arResult["MESSAGE"]["PREVIEW"] = GetMessage("NOT_FOUND_TEMPLATE");
		}
		elseif (strlen($arMessage) == 0)
		{
			$arResult["STATUS"] = true;
		}

		$arResult["MESSAGE"] = array (
			"PREVIEW" => GetMessage("TEMPLATE_CHECK_COUNT", array("#count#" => $arCount)),
			"DETAIL" => $arMessage
		);

		return $arResult;
	}

	public static function CheckKernel($arParams)
	{

		$time_start = time();
		global $DB;
		$arCompare = array(
			"install/components/bitrix/" => "/bitrix/components/bitrix/",
			"install/js/" => "/bitrix/js/",
			"install/activities/" => "/bitrix/activities/",
			"install/admin/" => "/bitrix/admin/",
			"install/wizards/" => "/bitrix/wizards/",
		);

		if(!$_SESSION["BX_CHECKLIST"][$arParams["TEST_ID"]])
			$_SESSION["BX_CHECKLIST"][$arParams["TEST_ID"]] = array();
		$NS = &$_SESSION["BX_CHECKLIST"][$arParams["TEST_ID"]];
		if ($arParams["STEP"] == false)
		{
			$NS = array();
			$rsInstalledModules = CModule::GetList();
			while ($ar = $rsInstalledModules->Fetch())
			{
				if (!strpos($ar["ID"], "."))
					$NS["MLIST"][] = $ar["ID"];
			}
			$NS["MNUM"] = 0;
			$NS["FILE_LIST"] = array();
			$NS["FILES_COUNT"] = 0;
			$NS["MODFILES_COUNT"] = 0;
		}
		$arError = false;
		$module_id = $NS["MLIST"][$NS["MNUM"]];
		$module_folder = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$module_id."/";
		$dbtype = strtolower($DB->type);
		if ($module_id == "main")
		{
			$ver = SM_VERSION;
		}
		else
		{
			$arModuleVersion = array();
			@include($module_folder."install/version.php");
			$ver = $arModuleVersion["VERSION"];
		}

		$arFilesCount = 0;
		$arModifiedFilesCount = 0;
		$state = array();
		$Skip = false;

		if (!$ver)
		{
			$state = array(
				"STATUS" => false,
				"MESSAGE" =>  GetMessage("CL_MODULE_VERSION_ERROR", array("#module_id#" => $module_id))."\n"
			);
			$arError = true;
		}
		else
		{
			if(count($NS["FILE_LIST"]) == 0)
			{
				$sHost = COption::GetOptionString("main", "update_site", "www.bitrixsoft.com");
				$proxyAddr = COption::GetOptionString("main", "update_site_proxy_addr", "");
				$proxyPort = COption::GetOptionString("main", "update_site_proxy_port", "");
				$proxyUserName = COption::GetOptionString("main", "update_site_proxy_user", "");
				$proxyPassword = COption::GetOptionString("main", "update_site_proxy_pass", "");

				$http = new \Bitrix\Main\Web\HttpClient();
				$http->setProxy($proxyAddr, $proxyPort, $proxyUserName, $proxyPassword);

				$data = $http->get("http://".$sHost."/bitrix/updates/checksum.php?check_sum=Y&module_id=".$module_id."&ver=".$ver."&dbtype=".$dbtype."&mode=2");

				$NS["FILE_LIST"] = $result = unserialize(gzinflate($data));
				$NS["MODULE_FILES_COUNT"] = count($NS["FILE_LIST"]);
			}
			else
			{
				$result = $NS["FILE_LIST"];
			}
			$arMessage = "";
			$timeout = COption::GetOptionString("main", "update_load_timeout", "30");
			if (is_array($result) && !$result["error"])
			{
				foreach($result as $file => $checksum)
				{
					$arFile = $module_folder.$file;
					unset($NS["FILE_LIST"][$file]);
					if (!file_exists($arFile))
						continue;
					$arFilesCount++;
					if (md5_file($arFile)!=$checksum)
					{
						$arMessage.= str_replace(array("//", "\\\\"), array("/", "\\"), $arFile)."\n";
						$arModifiedFilesCount++;
					}
					$arTmpCompare = $arCompare;
					foreach ($arTmpCompare as $key => $value)
					if (strpos($file, $key) === 0)
					{
						$arFile = str_replace($key, $_SERVER["DOCUMENT_ROOT"].$value, $file);
						if (file_exists($arFile) && md5_file($arFile)!=$checksum)
						{
							$arModifiedFilesCount++;
							$arMessage.= str_replace(array("//", "\\\\"), array("/", "\\"), $arFile)."\n";
						}
						$arFilesCount++;
					}
					if ((time()-$time_start)>=$timeout)
						break;
				}
				if (strlen($arMessage)> 0)
				{
					$state = array(
						"MESSAGE" => $arMessage,
						"STATUS" => false
					);
				}
			}
			else
			{
				if($result["error"]!= "unknow module id")
				{
					$state["MESSAGE"] = GetMessage("CL_CANT_CHECK", array("#module_id#" => $module_id))."\n";
					$arError = true;
				}
				else
					$Skip = true;
			}
		}
		if ($state["MESSAGE"])
			$NS["MESSAGE"][$module_id].=$state["MESSAGE"];
		if (!$arError && !$Skip)
		{
			if (count($NS["FILE_LIST"]) == 0)
			{
				if (strlen($NS["MESSAGE"][$module_id]) == 0)
					$NS["MESSAGE"][$module_id] = GetMessage("CL_NOT_MODIFIED", array("#module_id#" => $module_id))."\n";
				else
					$NS["MESSAGE"][$module_id] = GetMessage("CL_MODIFIED_FILES", array("#module_id#" => $module_id))."\n".$NS["MESSAGE"][$module_id];
			}
			$NS["FILES_COUNT"]+=$arFilesCount;
			$NS["MODFILES_COUNT"]+=$arModifiedFilesCount;
		}
		if ($state["STATUS"] === false || $arError == true || $Skip)
		{
			if ($state["STATUS"] === false || $arError == true)
				$NS["STATUS"] = false;
			$NS["FILE_LIST"] = array();
			$NS["MODULE_FILES_COUNT"] = 0;
		}

		if (($NS["MNUM"]+1)>=(count($NS["MLIST"])) && !$NS["LAST_FILE"])
		{
			$arDetailReport = "";
			foreach($NS["MESSAGE"] as $module_message)
				$arDetailReport.="<div class=\"checklist-dot-line\"></div>".$module_message;
			$arResult = array(
				"MESSAGE" => array(
					"PREVIEW" => GetMessage("CL_KERNEL_CHECK_FILES").$NS["FILES_COUNT"]."\n".
					GetMessage("CL_KERNEL_CHECK_MODULE").count($NS["MLIST"])."\n".
					GetMessage("CL_KERNEL_CHECK_MODIFIED").$NS["MODFILES_COUNT"],
					"DETAIL" => $arDetailReport
					),
				"STATUS" => ($NS["STATUS"] === false?false:true)
			);

		}
		else
		{
			$percent =  round(($NS["MNUM"])/(count($NS["MLIST"])*0.01), 0);
			$module_percent = 0;
			if ($NS["MODULE_FILES_COUNT"]>0)
				$module_percent =  (1/(count($NS["MLIST"])*0.01))*((($NS["MODULE_FILES_COUNT"]-count($NS["FILE_LIST"]))/($NS["MODULE_FILES_COUNT"]*0.01))*0.01);
			$percent += $module_percent;
			$arResult = array(
				"IN_PROGRESS" => "Y",
				"PERCENT" => number_format($percent, 2),
			);
			if (count($NS["FILE_LIST"]) == 0)
			{
				$NS["MNUM"]++;
				$NS["MODULE_FILES_COUNT"] = 0;
			}
		}
		return $arResult;
	}

	public static function CheckSecurity($arParams)
	{
		global $DB;
		$err = 0;
		$arResult['STATUS'] = false;
		$arMessage = '';
		switch ($arParams["ACTION"])
		{
			case "SECURITY_LEVEL":
				if (CModule::IncludeModule("security"))
				{
						if ($arMask = CSecurityFilterMask::GetList()->Fetch())
							$arMessage .= (++$err).". ".GetMessage("CL_FILTER_EXEPTION_FOUND")."\n";
						if(!CSecurityFilter::IsActive())
							$arMessage .= (++$err).". ".GetMessage("CL_FILTER_NON_ACTIVE")."\n";
						if(COption::GetOptionString("main", "captcha_registration", "N") == "N")
							$arMessage .= (++$err).". ".GetMessage("CL_CAPTCHA_NOT_USE")."\n";

					if (CCheckListTools::AdminPolicyLevel() != "high")
						$arMessage .= (++$err).". ".GetMessage("CL_ADMIN_SECURITY_LEVEL")."\n";
					if (COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) != (E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) && COption::GetOptionString("main","error_reporting","") != 0)
						$arMessage .= (++$err).". ".GetMessage("CL_ERROR_REPORTING_LEVEL")."\n";
					if($DB->debug)
						$arMessage .= (++$err).". ".GetMessage("CL_DBDEBUG_TURN_ON")."\n";
					if ($arMessage)
					{
						$arResult["STATUS"] = false;
						$arResult["MESSAGE"]=array(
								"PREVIEW" => GetMessage("CL_MIN_LEVEL_SECURITY"),
								"DETAIL" => GetMessage("CL_ERROR_FOUND")."\n".$arMessage
						);
					}
					else
					{
						$arResult["STATUS"] = true;
						$arResult["MESSAGE"]=array(
								"PREVIEW" => GetMessage("CL_LEVEL_SECURITY")."\n"
						);
					}
				}
				else
					$arResult = array(
						"STATUS" => false,
						"MESSAGE" => array(
							"PREVIEW" => GetMessage("CL_SECURITY_MODULE_NOT_INSTALLED")."\n"
						)
					);
			break;
			case "ADMIN_POLICY":
				if (CCheckListTools::AdminPolicyLevel() != "high")
					$arResult["MESSAGE"]["PREVIEW"] = GetMessage("CL_ADMIN_SECURITY_LEVEL")."\n";
				else
					$arResult = array(
						"STATUS" => true,
						"MESSAGE" => array(
							"PREVIEW" => GetMessage("CL_ADMIN_SECURITY_LEVEL_IS_HIGH")."\n"
						)
					);
			break;
		}

		return $arResult;
	}

	public static function CheckErrorReport()
	{
		global $DBDebug;
		$err = 0;
		$arResult["STATUS"] = true;
		$arMessage = '';
		if ($DBDebug)
			$arMessage .= (++$err).". ".GetMessage("CL_DBDEBUG_TURN_ON")."\n";
		if (COption::GetOptionString("main", "error_reporting", "")!=0 && ini_get("display_errors"))
			$arMessage .= (++$err).". ".GetMessage("CL_ERROR_REPORT_TURN_ON")."\n";

		if($arMessage)
		{
			$arResult["STATUS"] = false;
			$arResult["MESSAGE"] = array(
				"PREVIEW" => GetMessage("CL_ERROR_FOUND_SHORT")."\n",
				"DETAIL" => $arMessage
			);
		}
		return $arResult;
	}

	public static function IsCacheOn()
	{
		$arResult["STATUS"] = true;
		if (COption::GetOptionString("main", "component_cache_on", "Y") == "N")
		{
			$arResult["STATUS"] = false;
			$arResult["MESSAGE"]=array(
				"PREVIEW" => GetMessage("CL_TURNOFF_AUTOCACHE")."\n"
			);
		}
		else
			$arResult["MESSAGE"]=array(
				"PREVIEW" => GetMessage("CL_TURNON_AUTOCACHE")."\n"
			);

		return $arResult;
	}

	public static function CheckDBPassword()
	{
		global $DBPassword;
		$err = 0;
		$arMessage = "";
		$sign = ",.#!*%$:-^@{}[]()'\"-+=<>?`&;";
		$dit = "1234567890";
		$have_sign = false;
		$have_dit = false;
		$arResult["STATUS"] = true;
		if (strlen($DBPassword)==0)
			$arMessage.=GetMessage("CL_EMPTY_PASS")."\n";
		else
		{
			if ($DBPassword == strtolower($DBPassword))
				$arMessage .= (++$err).". ".GetMessage("CL_SAME_REGISTER")."\n";

			for($j=0, $c=strlen($DBPassword); $j<$c; $j++)
			{
				if (strpos($sign, $DBPassword[$j])!==false)
					$have_sign = true;
				if (strpos($dit, $DBPassword[$j])!==false)
					$have_dit = true;
				if ($have_dit == true && $have_sign == true)
					break;
			}

			if (!$have_dit)
				$arMessage .= (++$err).". ".GetMessage("CL_HAVE_NO_DIT")."\n";
			if (!$have_sign)
				$arMessage .= (++$err).". ".GetMessage("CL_HAVE_NO_SIGN")."\n";
			if (strlen($DBPassword)<8)
				$arMessage .= (++$err).". ".GetMessage("CL_LEN_MIN")."\n";
		}
		if($arMessage)
		{
			$arResult["STATUS"] = false;
			$arResult["MESSAGE"]=array(
				"PREVIEW" => GetMessage("CL_ERROR_FOUND_SHORT"),
				"DETAIL" => $arMessage
			);
		}
		else
			$arResult["MESSAGE"]=array(
				"PREVIEW" => GetMessage("CL_NO_ERRORS"),
			);
		return $arResult;
	}

	public static function CheckPerfomance($arParams)
	{
		if (!IsModuleInstalled("perfmon"))
			return array(
				"STATUS" => false,
				"MESSAGE" => array(
					"PREVIEW" => GetMessage("CL_CHECK_PERFOM_NOT_INSTALLED")
				)
			);
		$arResult = array(
			"STATUS" => true
		);
		switch($arParams["ACTION"])
		{
			case "PHPCONFIG":
				if(COption::GetOptionString("perfmon", "mark_php_is_good", "N") == "N")
				{
					$arResult["STATUS"] = false;
					$arResult["MESSAGE"]=array(
						"PREVIEW" => GetMessage("CL_PHP_NOT_OPTIMAL", array("#LANG#" => LANG))."\n"
					);
				}
				else
				{
					$arResult["MESSAGE"]=array(
						"PREVIEW" => GetMessage("CL_PHP_OPTIMAL")."\n"
					);
				}
			break;
			case "PERF_INDEX":
			$arPerfIndex = COption::GetOptionString("perfmon", "mark_php_page_rate", "N");
			if($arPerfIndex == "N")
			{
				$arResult["STATUS"] = false;
				$arResult["MESSAGE"]=array(
					"PREVIEW" => GetMessage("CL_CHECK_PERFOM_FAILED", array("#LANG#" => LANG))."\n"
				);
			}
			elseif($arPerfIndex<15)
			{
				$arResult["STATUS"] = false;
				$arResult["MESSAGE"]=array(
					"PREVIEW" => GetMessage("CL_CHECK_PERFOM_LOWER_OPTIMAL", array("#LANG#" => LANG))."\n"
				);
			}
			else
			{
				$arResult["MESSAGE"]=array(
					"PREVIEW" => GetMessage("CL_CHECK_PERFOM_PASSED")."\n"
				);
			}
			break;
		}
		return $arResult;
	}

	public static function CheckQueryString($arParams = array())
	{
		$time = time();
		$arPath = array(
			$_SERVER["DOCUMENT_ROOT"],
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/"
		);
		$arExept = array(
			"FOLDERS" => array("images", "bitrix", "upload", ".svn"),
			"EXT" => array("php"),
			"FILES" => array(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/dbconn.php",
				"after_connect.php"
			)
		);

		$arParams["STEP"] = (intval($arParams["STEP"])>=0)?intval($arParams["STEP"]):0;
		if (!$_SESSION["BX_CHECKLIST"] || $arParams["STEP"] == 0)
		{
			$_SESSION["BX_CHECKLIST"] = array(
				"LAST_FILE" => "",
				"FOUND" => "",
				"PERCENT" => 0,
			);
			$files = array();
			$arPathTmp = $arPath;
			foreach($arPathTmp as $path)
			{
				CCheckListTools::__scandir($path, $files, $arExept);
			}
			$_SESSION["BX_CHECKLIST"]["COUNT"] = count($files);
		}

		$arFileNum = 0;
		foreach ($arPath as $namespace)
		{
			$files = array();
			CCheckListTools::__scandir($namespace, $files, $arExept);
			foreach($files as $file)
			{
				$arFileNum++;
				//this is not first step?
				if (strlen($_SESSION["BX_CHECKLIST"]["LAST_FILE"])>0)
				{
					if ($_SESSION["BX_CHECKLIST"]["LAST_FILE"] == $file)
						$_SESSION["BX_CHECKLIST"]["LAST_FILE"] = "";
					continue;
				}
				$queries = array();
				if ($f = @fopen($file, "r"))
				{
					if ($content = @fread($f, filesize($file)))
						//preg_match('/\<\?[^(\?\>)]*?(?:mysql_query|odbc_exec)\(/ism', $content, $queries);
						preg_match('/((?:mysql_query|odbc_exec|oci_execute|odbc_execute)\(.*\))/ism', $content, $queries);
				}
				if ($queries && count($queries[0])>0)
					$_SESSION["BX_CHECKLIST"]["FOUND"].=str_replace(array("//", "\\\\"), array("/", "\\"), $file)."\n";

				if (time()-$time>=20)
				{
					$_SESSION["BX_CHECKLIST"]["LAST_FILE"] = $file;
					return array(
						"IN_PROGRESS" => "Y",
						"PERCENT" => round($arFileNum/($_SESSION["BX_CHECKLIST"]["COUNT"]*0.01), 2)
					);
				}
			}
		}
		$arResult = array("STATUS" => true);
		if (strlen($_SESSION["BX_CHECKLIST"]["FOUND"])>0)
		{
			$arResult["STATUS"] = false;
			$arResult["MESSAGE"]=array(
				"PREVIEW" => GetMessage("CL_KERNEL_CHECK_FILES").$arFileNum.".\n".GetMessage("CL_ERROR_FOUND_SHORT")."\n",
				"DETAIL" => GetMessage("CL_DIRECT_QUERY_TO_DB")."\n".$_SESSION["BX_CHECKLIST"]["FOUND"],
			);
		}
		else
		{
			$arResult["MESSAGE"]=array(
				"PREVIEW" => GetMessage("CL_KERNEL_CHECK_FILES").$arFileNum."\n"
			);
		}
		unset($_SESSION["BX_CHECKLIST"]);
		return $arResult;
	}

	public static function KeyCheck()
	{
		$arResult = array("STATUS" => false);
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
		$arUpdateList = CUpdateClient::GetUpdatesList($errorMessage, LANG);
		if(array_key_exists("CLIENT", $arUpdateList)&&$arUpdateList["CLIENT"][0]["@"]["RESERVED"] == "N")
		{
			$arResult = array(
				"STATUS" => true,
				"MESSAGE" => array("PREVIEW" => GetMessage("CL_LICENSE_KEY_ACTIVATE"))
			);
		}
		else
			$arResult["MESSAGE"] = array("PREVIEW" => GetMessage("CL_LICENSE_KEY_NONE_ACTIVATE", array("#LANG#" => LANG)));

		return $arResult;
	}
}

class CCheckListTools
{
	public static function __scandir($pwd, &$arFiles, $arExept = false)
	{
		if(file_exists($pwd))
		{
			$dir = scandir($pwd);
			foreach ($dir as $file)
			{
				if ($file == ".." || $file == ".")
					continue;
				if (is_dir($pwd."$file"))
				{
					if (!in_array($file, $arExept["FOLDERS"]))
						CCheckListTools::__scandir($pwd."$file/", $arFiles, $arExept);
				}
				elseif(in_array(substr(strrchr($file, '.'), 1), $arExept["EXT"])
					&& !in_array($pwd.$file, $arExept["FILES"])
					&& !in_array($file, $arExept["FILES"])
					)
				{
					$arFiles[] = $pwd."/$file";
				}
			}
		}
	}

	public static function AdminPolicyLevel()
	{
		$arGroupPolicy = array(
			"parent" => array(
				"SESSION_TIMEOUT" => "",
				"SESSION_IP_MASK" => "",
				"MAX_STORE_NUM" => "",
				"STORE_IP_MASK" => "",
				"STORE_TIMEOUT" => "",
				"CHECKWORD_TIMEOUT" => "",
				"PASSWORD_LENGTH" => "",
				"PASSWORD_UPPERCASE" => "N",
				"PASSWORD_LOWERCASE" => "N",
				"PASSWORD_DIGITS" => "N",
				"PASSWORD_PUNCTUATION" => "N",
				"LOGIN_ATTEMPTS" => "",
			),
			"low" => array(
				"SESSION_TIMEOUT" => 30, //minutes
				"SESSION_IP_MASK" => "0.0.0.0",
				"MAX_STORE_NUM" => 20,
				"STORE_IP_MASK" => "255.0.0.0",
				"STORE_TIMEOUT" => 60*24*93, //minutes
				"CHECKWORD_TIMEOUT" => 60*24*185,  //minutes
				"PASSWORD_LENGTH" => 6,
				"PASSWORD_UPPERCASE" => "N",
				"PASSWORD_LOWERCASE" => "N",
				"PASSWORD_DIGITS" => "N",
				"PASSWORD_PUNCTUATION" => "N",
				"LOGIN_ATTEMPTS" => 0,
			),
			"middle" => array(
				"SESSION_TIMEOUT" => 20, //minutes
				"SESSION_IP_MASK" => "255.255.0.0",
				"MAX_STORE_NUM" => 10,
				"STORE_IP_MASK" => "255.255.0.0",
				"STORE_TIMEOUT" => 60*24*30, //minutes
				"CHECKWORD_TIMEOUT" => 60*24*1,  //minutes
				"PASSWORD_LENGTH" => 8,
				"PASSWORD_UPPERCASE" => "Y",
				"PASSWORD_LOWERCASE" => "Y",
				"PASSWORD_DIGITS" => "Y",
				"PASSWORD_PUNCTUATION" => "N",
				"LOGIN_ATTEMPTS" => 0,
			),
			"high" => array(
				"SESSION_TIMEOUT" => 15, //minutes
				"SESSION_IP_MASK" => "255.255.255.255",
				"MAX_STORE_NUM" => 1,
				"STORE_IP_MASK" => "255.255.255.255",
				"STORE_TIMEOUT" => 60*24*3, //minutes
				"CHECKWORD_TIMEOUT" => 60,  //minutes
				"PASSWORD_LENGTH" => 10,
				"PASSWORD_UPPERCASE" => "Y",
				"PASSWORD_LOWERCASE" => "Y",
				"PASSWORD_DIGITS" => "Y",
				"PASSWORD_PUNCTUATION" => "Y",
				"LOGIN_ATTEMPTS" => 3,
			),
		);
		$arAdminPolicy = CUser::GetGroupPolicy(1);
		$level = 'high';
		if (is_array($arGroupPolicy))
		{
			foreach($arGroupPolicy['parent'] as $key => $value)
			{
				$el2_value = $arAdminPolicy[$key];
				$el2_checked = $arAdminPolicy[$key] === "Y";
				$clevel = '';

				switch($key)
				{
				case "SESSION_TIMEOUT":
				case "MAX_STORE_NUM":
				case "STORE_TIMEOUT":
				case "CHECKWORD_TIMEOUT":
					if(intval($el2_value) <= intval($arGroupPolicy['high'][$key]))
						$clevel = 'high';
					elseif(intval($el2_value) <= intval($arGroupPolicy['middle'][$key]))
						$clevel = 'middle';
					else
						$clevel = 'low';
					break;
				case "PASSWORD_LENGTH":
					if(intval($el2_value) >= intval($arGroupPolicy['high'][$key]))
						$clevel = 'high';
					elseif(intval($el2_value) >= intval($arGroupPolicy['middle'][$key]))
						$clevel = 'middle';
					else
						$clevel = 'low';
					break;
				case "LOGIN_ATTEMPTS":
					if(intval($el2_value) > 0)
					{
						if(intval($el2_value) <= intval($arGroupPolicy['high'][$key]))
							$clevel = 'high';
						elseif(intval($el2_value) <= intval($arGroupPolicy['middle'][$key]))
							$clevel = 'middle';
						else
							$clevel = 'low';
					}
					else
					{
						if(intval($arGroupPolicy['high'][$key]) <= 0)
							$clevel = 'high';
						elseif(intval($arGroupPolicy['middle'][$key]) <= 0)
							$clevel = 'middle';
						else
							$clevel = 'low';
					}
					break;
				case "PASSWORD_UPPERCASE":
				case "PASSWORD_LOWERCASE":
				case "PASSWORD_DIGITS":
				case "PASSWORD_PUNCTUATION":
					if($el2_checked)
					{
						if($arGroupPolicy['high'][$key] == 'Y')
							$clevel = 'high';
						elseif($arGroupPolicy['middle'][$key] == 'Y')
							$clevel = 'middle';
						else
							$clevel = 'low';
					}
					else
					{
						if($arGroupPolicy['high'][$key] == 'N')
							$clevel = 'high';
						elseif($arGroupPolicy['middle'][$key] == 'N')
							$clevel = 'middle';
						else
							$clevel = 'low';
					}
					break;
					case "SESSION_IP_MASK":
					case "STORE_IP_MASK":
						$gp_ip = ip2long($el2_value);
						$high_ip = ip2long($arGroupPolicy['high'][$key]);
						$middle_ip = ip2long($arGroupPolicy['middle'][$key]);
						if(($gp_ip & $high_ip) == (0xFFFFFFFF & $high_ip))
							$clevel = 'high';
						elseif(($gp_ip & $middle_ip) == (0xFFFFFFFF & $middle_ip))
							$clevel = 'middle';
						else
							$clevel = 'low';
					break;
					default:
					break;
				}

				if($clevel == 'low')
					$level = $clevel;
				elseif($clevel == 'middle' && $level == 'high')
					$level = $clevel;
			}
		}

		return $level;
	}
}
