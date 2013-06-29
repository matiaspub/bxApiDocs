<?
IncludeModuleLangFile(__FILE__);

class CMainAdmin
{
	public static function GetTemplateList($rel_dir)
	{
		$arrTemplate = array();
		$arrTemplateDir = array();
		$rel_dir = str_replace("\\", "/", $rel_dir);

		$path = BX_PERSONAL_ROOT."/templates/";
		$handle=@opendir($_SERVER["DOCUMENT_ROOT"].$path);
		if($handle)
		{
			while (false!==($dir_name = readdir($handle)))
			{
				if (is_dir($_SERVER["DOCUMENT_ROOT"].$path.$dir_name) && $dir_name!="." && $dir_name!="..")
					$arrTemplateDir[] = $path.$dir_name;
			}
			closedir($handle);
		}
		$arrS = explode("/", $rel_dir);
		if (is_array($arrS) && count($arrS)>0)
		{
			$module_id = $arrS[0];
			$path = "/bitrix/modules/".$module_id."/install/templates/";
			if (is_dir($_SERVER["DOCUMENT_ROOT"].$path)) $arrTemplateDir[] = $path;
		}

		if (is_array($arrTemplateDir) && count($arrTemplateDir)>0)
		{
			foreach($arrTemplateDir as $template_dir)
			{
				$path = $template_dir."/".$rel_dir;
				$path = str_replace("\\", "/", $path);
				$path = str_replace("//", "/", $path);
				$handle=@opendir($_SERVER["DOCUMENT_ROOT"].$path);
				if($handle)
				{
					while (false!==($file_name = readdir($handle)))
					{
						if (is_file($_SERVER["DOCUMENT_ROOT"].$path.$file_name) && $file_name!="." && $file_name!="..")
							$arrTemplate[$file_name] = $file_name;
					}
					closedir($handle);
				}
			}
		}
		$arrTemplate = array_values($arrTemplate);
		usort($arrTemplate, create_function('$v1,$v2','if ($v1>$v2) return 1; elseif ($v1<$v2) return -1;'));
		return $arrTemplate;
	}
}

class CTemplates
{
	public static function GetList($arFilter = Array(), $arCurrentValues = Array(), $template_id = Array())
	{
		if(!is_set($arFilter, "FOLDER"))
		{
			$arr = CTemplates::GetFolderList();
			$arFilter["FOLDER"] = array_keys($arr);
		}

		foreach($arFilter["FOLDER"] as $folder)
		{
			$folder = _normalizePath($folder);
			$arTemplates[$folder] = Array();
			$arPath = array(
				"/bitrix/modules/".$folder."/install/templates/",
				BX_PERSONAL_ROOT."/templates/.default/",
			);

			if(is_array($template_id))
			{
				foreach($template_id as $v)
					$arPath[] = BX_PERSONAL_ROOT."/templates/"._normalizePath($v)."/";
			}
			elseif(strlen($template_id)>0)
			{
				$arPath[] = BX_PERSONAL_ROOT."/templates/"._normalizePath($template_id)."/";
			}

			foreach($arPath as $path)
				CTemplates::__FindTemplates($path, $arTemplates[$folder], $arCurrentValues, $folder);

			if(count($arTemplates[$folder])<=0)
			{
				unset($arTemplates[$folder]);
			}
			else
			{
				$arTemplate = $arTemplates[$folder];
				$arTemplateTemp = Array();
				$arSeparators = Array();
				foreach($arTemplate as $k=>$val)
					if($val["SEPARATOR"]=="Y")
						$arSeparators[$k] = $val;

				foreach($arSeparators as $sep_id=>$val_sep)
				{
					$arTemplateTemp[$sep_id] = $val_sep;
					reset($arTemplate);
					while(list($k, $val) = each($arTemplate))
					{
						if($val===false)
							continue;

						if($k==$sep_id)
						{
							while(list($k, $val) = each ($arTemplate))
							{
								if($val === false)
									continue;
								if($val["SEPARATOR"]=="Y")
									break;
								if(strlen($val["PARENT"])>0 && $val["PARENT"]!=$sep_id)
									continue;

								$arTemplateTemp[$k] = $val;
								$arTemplate[$k] = false;
							}
							//continue;
						}
						if($val["PARENT"]==$sep_id)
						{
							$arTemplateTemp[$k] = $val;
							$arTemplate[$k] = false;
						}
					}
				}

				$bW = true;
				foreach($arTemplate as $k=>$val)
				{
					if($val===false || $val["SEPARATOR"] == "Y")
						continue;
					if($bW)
					{
						if(count($arSeparators)>0)
							$arTemplateTemp[md5(uniqid(rand(), true))] = Array("NAME"=> "----------------------------", "SEPARATOR"=>"Y");
						$bW = false;
					}
					$arTemplateTemp[$k] = $val;
					$arTemplate[$k] = false;
				}

				$arTemplates[$folder] = $arTemplateTemp;
			}
		}
		return $arTemplates;
	}

	public static function GetByID($id, $arCurrentValues = Array(), $templateID = Array())
	{
		$folder = substr($id, 0, strpos($id, "/"));
		$arRes = CTemplates::GetList(Array("FOLDER"=>Array($folder)), $arCurrentValues, $templateID);
		$all_templates = $arRes[$folder];
		if(is_set($all_templates, $id))
			return $all_templates[$id];
		return false;
	}

	public static function __FindTemplates($root, &$arTemplates, $arCurrentValues=Array(), $init="")
	{
		if(is_dir($_SERVER['DOCUMENT_ROOT'].$root.$init))
		{
			$arTemplateDescription = Array();
			if(file_exists($_SERVER['DOCUMENT_ROOT'].$root.$init."/.description.php"))
			{
				include($_SERVER['DOCUMENT_ROOT'].$root.$init."/.description.php");
				foreach($arTemplateDescription as $path=>$desc)
				{
					$desc["REAL_PATH"] = $root.$init."/".$path;
					if(strlen($desc["PARENT"])>0)
						$desc["PARENT"] = $init."/".$desc["PARENT"];
					$arTemplates[$init."/".$path] = $desc;
				}
			}

			if($handle = @opendir($_SERVER["DOCUMENT_ROOT"].$root.$init))
			{
				while(($file = readdir($handle)) !== false)
				{
					if($file == "." || $file == "..") continue;
					CTemplates::__FindTemplates($root, $arTemplates, $arCurrentValues, $init."/".$file);
				}
			}
		}
	}

	public static function GetFolderList($template_id = false)
	{
		$arTemplateFolders = Array();
		$arTemplateFoldersSort = Array();
		$path = "/bitrix/modules";
		if($handle = @opendir($_SERVER["DOCUMENT_ROOT"].$path))
		{
			while(($module_name = readdir($handle)) !== false)
			{
				if($module_name == "." || $module_name == "..") continue;
				if(is_dir($_SERVER["DOCUMENT_ROOT"].$path."/".$module_name))
				{
					$path_mod = $path."/".$module_name."/install/templates";
					if(file_exists($_SERVER["DOCUMENT_ROOT"].$path_mod))
					{
						if($handle_mod = @opendir($_SERVER["DOCUMENT_ROOT"].$path_mod))
						{
							while(($file_templ = readdir($handle_mod)) !== false)
							{
								if($file_templ == "." || $file_templ == ".." || $file_templ=="lang")
									continue;
								if(is_dir($_SERVER["DOCUMENT_ROOT"].$path_mod."/".$file_templ))
								{
									$sSectionName = false;
									$iSort = 500;
									if(file_exists($_SERVER["DOCUMENT_ROOT"].$path_mod."/".$file_templ."/.description.php"))
									{
										if(file_exists(($fname = $_SERVER["DOCUMENT_ROOT"].$path_mod."/lang/".LangSubst(LANGUAGE_ID)."/".$module_name."/.description.php")))
											__IncludeLang($fname);
										if(LANGUAGE_ID <> "ru" && file_exists(($fname = $_SERVER["DOCUMENT_ROOT"].$path_mod."/lang/".LANGUAGE_ID."/".$module_name."/.description.php")))
											__IncludeLang($fname);
										include($_SERVER["DOCUMENT_ROOT"].$path_mod."/".$file_templ."/.description.php");
									}
									if($sSectionName)
									{
										$arTemplateFolders[$module_name] = $sSectionName;
										$arTemplateFoldersSort[$module_name] = $iSort;
									}
								}
							}
							@closedir($handle_mod);
						}
					}
				}
			}
			@closedir($handle);
		}

		$arPath = Array(BX_PERSONAL_ROOT."/templates/.default");
		if($template_id)
			$arPath[] = BX_PERSONAL_ROOT."/templates/".$template_id;

		foreach($arPath as $path)
		{
			if($handle = @opendir($_SERVER["DOCUMENT_ROOT"].$path))
			{
				while(($folder_name = readdir($handle)) !== false)
				{
					if($folder_name == "." || $folder_name == ".." || $folder_name=="lang")
						continue;
					if(is_dir($_SERVER["DOCUMENT_ROOT"].$path."/".$folder_name))
					{
						$sSectionName = false;
						$iSort = 500;
						if(file_exists($_SERVER["DOCUMENT_ROOT"].$path."/".$folder_name."/.description.php"))
							include($_SERVER["DOCUMENT_ROOT"].$path."/".$folder_name."/.description.php");
						if($sSectionName)
						{
							$arTemplateFolders[$folder_name] = $sSectionName;
							$arTemplateFoldersSort[$folder_name] = $iSort;
						}
					}
				}
				@closedir($handle);
			}
		}
		array_multisort($arTemplateFoldersSort, $arTemplateFolders);

		return $arTemplateFolders;
	}
}

class CPageTemplate
{
	public static function GetList($arSiteTemplates=array())
	{
		$arDirs = array("/templates/.default/page_templates");
		foreach($arSiteTemplates as $val)
			$arDirs[] = "/templates/".$val."/page_templates";

		$arFiles = array();
		foreach($arDirs as $dir)
		{
			$template_dir = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT.$dir;
			if(!file_exists($template_dir))
				continue;
			if($handle = opendir($template_dir))
			{
				while(($file = readdir($handle)) !== false)
				{
					if($file == "." || $file == ".." || !is_dir($template_dir."/".$file))
						continue;

					$template_file = $template_dir."/".$file."/template.php";
					if(!file_exists($template_file))
						continue;

					if($GLOBALS['APPLICATION']->GetFileAccessPermission(BX_PERSONAL_ROOT.$dir."/".$file."/template.php") < "R")
						continue;

					$arFiles[$file] = $template_file;
				}
				closedir($handle);
			}
		}

		$res = array();
		foreach($arFiles as $file=>$template_file)
		{
			$pageTemplate = false;
			include_once($template_file);

			if(!$pageTemplate || !is_callable(array($pageTemplate, 'GetDescription')))
				continue;

			$arRes = array(
				"name"=>$file,
				"description"=>"",
				"icon"=>"",
				"file"=>$file,
				"sort"=>150,
				"type"=>"",
			);

			$arDesc = $pageTemplate->GetDescription();

			if(is_array($arDesc["modules"]))
				foreach($arDesc["modules"] as $module)
					if(!IsModuleInstalled($module))
						continue 2;

			foreach($arDesc as $key=>$val)
				$arRes[$key] = $val;

			$res[$file] = $arRes;
		}

		uasort($res, array('CPageTemplate', '_templ_sort'));
		return $res;
	}

	public static function _templ_sort($a, $b)
	{
		if($a["sort"] < $b["sort"])
			return -1;
		elseif($a["sort"] > $b["sort"])
			return 1;
		else
			return strcmp($a["name"], $b["name"]);
	}

	public static function GetTemplate($template, $arSiteTemplates=array())
	{
		$arDirs = array("/templates/.default/page_templates");
		foreach($arSiteTemplates as $val)
			$arDirs[] = "/templates/".$val."/page_templates";

		$template = _normalizePath($template);

		$sFile = false;
		foreach($arDirs as $dir)
		{
			$template_dir = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT.$dir;
			$template_file = $template_dir."/".$template."/template.php";
			if(!file_exists($template_file))
				continue;

			if($GLOBALS['APPLICATION']->GetFileAccessPermission(BX_PERSONAL_ROOT.$dir."/".$template."/template.php") < "R")
				continue;

			$sFile = $template_file;
		}
		if($sFile !== false)
		{
			$pageTemplate = false;
			include_once($sFile);

			if(is_object($pageTemplate))
				return $pageTemplate;
		}
		return false;
	}

	public static function IncludeLangFile($filepath)
	{
		$file = basename($filepath);
		$dir = dirname($filepath);

		if(LANGUAGE_ID <> "en" && LANGUAGE_ID <> "ru" && file_exists(($fname = $dir."/lang/".LangSubst(LANGUAGE_ID)."/".$file)))
			__IncludeLang($fname, false, true);

		if(file_exists(($fname = $dir."/lang/".LANGUAGE_ID."/".$file)))
			__IncludeLang($fname, false, true);
	}
}

function GetTemplateContent($filename, $lang=LANG, $arTemplates=Array())
{
	global $DOCUMENT_ROOT, $APPLICATION;

	$filename = _normalizePath($filename);

	$arDirs = Array();
	foreach($arTemplates as $val)
		$arDirs[] = "/templates/".$val."/page_templates";
	$arDirs[] = "/templates/.default/page_templates";
	$arDirs[] = "/php_interface/".$lang."templates";
	$arDirs[] = "/php_interface/templates";

	for($i=0, $n=count($arDirs); $i<$n; $i++)
		if(is_file($DOCUMENT_ROOT.BX_PERSONAL_ROOT.$arDirs[$i]."/".$filename))
			return $APPLICATION->GetFileContent($DOCUMENT_ROOT.BX_PERSONAL_ROOT.$arDirs[$i]."/".$filename);

	return false;
}

function GetFileTemplates($lang = LANG, $arTemplates = Array())
{
	global $APPLICATION;

	$arDirs = array(
		"/php_interface/".$lang."/templates",
		"/templates/.default/page_templates",
		"/php_interface/templates",
	);
	foreach($arTemplates as $val)
		$arDirs[] = "/templates/".$val."/page_templates";

	$res = array();
	foreach($arDirs as $dir)
	{
		$dirPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT.$dir;
		if(file_exists($dirPath))
		{
			$sDescFile = $dirPath."/.content.php";
			$TEMPLATE = array();
			if(file_exists($sDescFile))
				include($sDescFile);

			if($handle = @opendir($dirPath))
			{
				while(($file = readdir($handle)) !== false)
				{
					if(is_dir($dirPath."/".$file))
						continue;
					if($file[0] == ".")
						continue;

					$path = BX_PERSONAL_ROOT.$dir."/".$file;
					if($APPLICATION->GetFileAccessPermission($path) < "R")
						continue;

					$restmp = array(
						"name" => substr($file, 0, bxstrrpos($file, ".")),
						"file" => $file,
						"sort" => 150,
						"path" => $path,
					);

					if(array_key_exists($file, $TEMPLATE))
					{
						if(array_key_exists("name", $TEMPLATE[$file]))
							$restmp["name"] = $TEMPLATE[$file]["name"];
						if(array_key_exists("sort", $TEMPLATE[$file]))
							$restmp["sort"] = $TEMPLATE[$file]["sort"];
					}

					$res[$file] = $restmp;
				}
				closedir($handle);
			}
		}
	}
	sortByColumn($res, "sort");

	return array_values($res);
}

function ParsePath($path, $bLast=false, $url=false, $param="", $bLogical = false)
{
	CMain::InitPathVars($site, $path);
	$DOC_ROOT = CSite::GetSiteDocRoot($site);

	if($url===false)
		$url = BX_ROOT."/admin/fileman_admin.php";

	global $MESS;
	if($site!==false && strlen($site)>0)
	{
		$res = CSite::GetByID($site);
		if(!($arSite = $res->Fetch()))
			$site=false;
	}

	$addUrl = ($bLogical?"logical=Y":"");

	$arDirPath = explode("/", $path);
	$full_path = "";
	$prev_path = "";
	$arPath = array();
	if($bLast || strlen($path)>0 || strlen($site)>0)
	{
		$html_path = '<a href="'.$url.'?lang='.LANG.'&'.$addUrl.'">'.GetMessage("MAIN_ROOT_FOLDER").'</a>/';
		/*
		$arPath[] = array(
			"LINK" => $url.'?lang='.LANG,
			"TITLE" => GetMessage("MAIN_ROOT_FOLDER")
		);
		*/
	}
	else
	{
		$html_path = GetMessage("MAIN_ROOT_FOLDER")."/";
		/*
		$arPath[] = array(
			"LINK" => "",
			"TITLE" => GetMessage("MAIN_ROOT_FOLDER")
		);
		*/
	}

	if($site!==false)
	{
		if($bLast || strlen($path)>0)
		{
			$html_path .= '<a href="'.$url.'?lang='.LANG.'&'.$addUrl.'&amp;site='.$site.'">'.$arSite["NAME"].'</a>/';
			/*
			$arPath[] = array(
				"LINK" => $url.'?lang='.LANG.'&amp;site='.$site,
				"TITLE" => $arSite["NAME"]
			);
			*/
		}
		else
		{
			$html_path .= $arSite["NAME"]."/";
			/*
			$arPath[] = array(
				"LINK" => "",
				"TITLE" => $arSite["NAME"]
			);
			*/
		}
	}

	$io = CBXVirtualIo::GetInstance();
	$pathLast = count($arDirPath)-1;
	foreach($arDirPath as $i => $pathPart)
	{
		if(strlen($pathPart)<=0)
			continue;

		$prev_path = $full_path;
		$full_path .= "/".$pathPart;
		$last = $pathPart;

		$sSectionName = $pathPart;
		if($bLogical && $io->DirectoryExists($DOC_ROOT.$full_path))
		{
			if(!$io->FileExists($DOC_ROOT.$full_path."/.section.php"))
				continue;

			include($io->GetPhysicalName($DOC_ROOT.$full_path."/.section.php"));
			if(strlen($sSectionName)<=0)
				$sSectionName = GetMessage("admin_tools_no_name");
		}

		if($i==$pathLast && (!$bLast || !$io->DirectoryExists($DOC_ROOT.$full_path)))
		{
			$html_path .= $sSectionName;
			$arPath[] = array(
				"LINK" => "",
				"TITLE" => $sSectionName
			);
		}
		else
		{
			$html_path .= "<a href=\"".$url."?lang=".LANG.'&'.$addUrl."&path=".UrlEncode($full_path).($site?"&site=".$site : "").($param<>""? "&".$param:"")."\">".$sSectionName."</a>/";
			if(!$arSite || !$bLogical || ($bLogical && rtrim($arSite["DIR"], "/") != rtrim($full_path, "/")))
				$arPath[] = array(
					"LINK" => $url."?lang=".LANG."&".$addUrl."&path=".UrlEncode($full_path).($site?"&site=".$site : "").($param<>""? "&".$param:""),
					"TITLE" => $sSectionName
				);
		}
	}

	return array(
		"PREV" => $prev_path,
		"FULL" => $full_path,
		"HTML" => $html_path,
		"LAST" => $last,
		"AR_PATH" => $arPath,
	);
}

function CompareFiles($f1, $f2, $sort=Array())
{
	$by = key($sort);
	$order = $sort[$by];
	if(strtolower($order)=="desc")
	{
		if($by=="size")	return IntVal($f1["SIZE"])<IntVal($f2["SIZE"]);
		if($by=="timestamp") return IntVal($f1["TIMESTAMP"])<IntVal($f2["TIMESTAMP"]);
		return $f1["NAME"]<$f2["NAME"];
	}
	else
	{
		if($by=="size")	return IntVal($f1["SIZE"])>IntVal($f2["SIZE"]);
		if($by=="timestamp") return IntVal($f1["TIMESTAMP"])>IntVal($f2["TIMESTAMP"]);
		return $f1["NAME"]>$f2["NAME"];
	}
}

function GetDirList($path, &$arDirs, &$arFiles, $arFilter=Array(), $sort=Array(), $type="DF", $bLogical=false,$task_mode=false)
{
	global $USER, $APPLICATION;

	CMain::InitPathVars($site, $path);
	$DOC_ROOT = CSite::GetSiteDocRoot($site);

	$arDirs=Array();
	$arFiles=Array();

	$exts = strtolower($arFilter["EXTENSIONS"]);
	$arexts=explode(",", $exts);
	if(isset($arFilter["TYPE"]))
		$type = strtoupper($arFilter["TYPE"]);

	$io = CBXVirtualIo::GetInstance();
	$path = $io->CombinePath("/", $path);
	$abs_path = $io->CombinePath($DOC_ROOT, $path);

	if(!$io->DirectoryExists($abs_path))
		return false;

	$date_format = CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL"));
	$tzOffset = CTimeZone::GetOffset();

	$dir = $io->GetDirectory($abs_path);
	$arChildren = $dir->GetChildren();
	foreach ($arChildren as $child)
	{
		$arFile = Array();

		if(($type=="F" || $type=="") && $child->IsDirectory())
			continue;
		if(($type=="D" || $type=="") && !$child->IsDirectory())
			continue;

		$file = $child->GetName();

		if($bLogical)
		{
			if($child->IsDirectory())
			{
				$sSectionName = "";
				$fsn = $io->CombinePath($abs_path, $file, ".section.php");
				if(!$io->FileExists($fsn))
					continue;

				include($io->GetPhysicalName($fsn));
				$arFile["LOGIC_NAME"] = $sSectionName;
			}
			else
			{
				if(CFileMan::GetFileTypeEx($file) != "php")
					continue;

				if($file=='.section.php')
					continue;

				if(!preg_match('/^\.(.*)?\.menu\.(php|html|php3|php4|php5|php6|phtml)$/', $file, $regs))
				{
					$f = $io->GetFile($abs_path."/".$file);
					$filesrc = $f->GetContents();

					$title = PHPParser::getPageTitle($filesrc);
					if($title===false)
						continue;
					$arFile["LOGIC_NAME"] = $title;
				}
			}
		}

		$arFile["PATH"] = $abs_path."/".$file;
		$arFile["ABS_PATH"] = $path."/".$file;
		$arFile["NAME"] = $file;

		$arPerm = $APPLICATION->GetFileAccessPermission(Array($site, $path."/".$file), $USER->GetUserGroupArray(),$task_mode);
		if ($task_mode)
		{
			$arFile["PERMISSION"] = $arPerm[0];
			if (count($arPerm[1]) > 0)
				$arFile["PERMISSION_EX"] = $arPerm[1];
		}
		else
			$arFile["PERMISSION"] = $arPerm;

		$arFile["TIMESTAMP"] = $child->GetModificationTime() + $tzOffset;
		$arFile["DATE"] = date($date_format, $arFile["TIMESTAMP"]);

		if (isset($arFilter["TIMESTAMP_1"]) && strtotime($arFile["DATE"]) < strtotime($arFilter["TIMESTAMP_1"]))
			continue;
		if (isset($arFilter["TIMESTAMP_2"]) && strtotime($arFile["DATE"]) > strtotime($arFilter["TIMESTAMP_2"]))
			continue;

		if(is_set($arFilter, "MIN_PERMISSION") && $arFile["PERMISSION"]<$arFilter["MIN_PERMISSION"] && !$task_mode)
			continue;

		if(!$child->IsDirectory() && $arFile["PERMISSION"]<="R" && !$task_mode)
			continue;

		if ($bLogical)
		{
			if(strlen($arFilter["NAME"])>0 && strpos($arFile["LOGIC_NAME"], $arFilter["NAME"])===false)
				continue;
		}
		else
		{
			if(strlen($arFilter["NAME"])>0 && strpos($arFile["NAME"], $arFilter["NAME"])===false)
				continue;
		}

		//if(strlen($arFilter["NAME"])>0 && strpos($arFile["NAME"], $arFilter["NAME"])===false)
		//	continue;

		if(substr($arFile["ABS_PATH"], 0, strlen(BX_ROOT."/modules"))==BX_ROOT."/modules" && !$USER->CanDoOperation('edit_php') && !$task_mode)
			continue;

		if ($arFile["PERMISSION"]=="U" && !$task_mode)
		{
			$ftype = GetFileType($arFile["NAME"]);
			if ($ftype!="SOURCE" && $ftype!="IMAGE" && $ftype!="UNKNOWN") continue;
			if (substr($arFile["NAME"], 0,1)==".") continue;
		}

		if($child->IsDirectory())
		{
			$arFile["SIZE"] = 0;
			$arFile["TYPE"] = "D";
			$arDirs[]=$arFile;
		}
		else
		{
			if($exts!="")
				if(!in_array(strtolower(substr($file, bxstrrpos($file,".")+1)), $arexts))
					continue;

			$arFile["TYPE"] = "F";
			$arFile["SIZE"] = $child->GetFileSize();
			$arFiles[]=$arFile;
		}
	}

	if(is_array($sort) && count($sort)>0)
	{
		$by = key($sort);
		$order = strtolower($sort[$by]);
		$by = strtolower($by);
		if($order!="desc")
			$order="asc";
		if($by!="size" && $by!="timestamp")
			$by="name";

		usort($arDirs, Array("_FilesCmp", "cmp_".$by."_".$order));
		usort($arFiles, Array("_FilesCmp", "cmp_".$by."_".$order));
	}
}

class _FilesCmp
{
	public static function cmp_size_asc($a, $b)
	{
		if($a["SIZE"] == $b["SIZE"])
			return 0;
		return ($a["SIZE"] < $b["SIZE"]) ? -1 : 1;
	}
	public static function cmp_size_desc($a, $b)
	{
		if ($a["SIZE"] == $b["SIZE"])
			return 0;
		return ($a["SIZE"] > $b["SIZE"]) ? -1 : 1;
	}
	public static function cmp_timestamp_asc($a, $b)
	{
		if($a["TIMESTAMP"] == $b["TIMESTAMP"])
			return 0;
		return ($a["TIMESTAMP"] < $b["TIMESTAMP"]) ? -1 : 1;
	}
	public static function cmp_timestamp_desc($a, $b)
	{
		if ($a["TIMESTAMP"] == $b["TIMESTAMP"])
			return 0;
		return ($a["TIMESTAMP"] > $b["TIMESTAMP"]) ? -1 : 1;
	}
	public static function cmp_name_asc($a, $b)
	{
		if($a["NAME"] == $b["NAME"])
			return 0;
		return ($a["NAME"] < $b["NAME"]) ? -1 : 1;
	}
	public static function cmp_name_desc($a, $b)
	{
		if($a["NAME"] == $b["NAME"])
			return 0;
		return ($a["NAME"] > $b["NAME"]) ? -1 : 1;
	}
}

function SetPrologTitle($prolog, $title)
{
	if(preg_match('/
		(\$APPLICATION->SetTitle\()
		(
			"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"                           # match double quoted string
			|
			\'[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*\'                       # match single quoted string
		)
		(\);)
		/ix', $prolog, $regs)
	)
	{
		$prolog = str_replace($regs[0], $regs[1]."\"".EscapePHPString($title)."\");", $prolog);
	}
	else
	{
		$p = strpos($prolog, "prolog_before");
		if($p===false)
			$p = strpos($prolog, "prolog.php");
		if($p===false)
			$p = strpos($prolog, "header.php");

		if($p===false)
		{
			if(strlen($title)<=0)
				$prolog = preg_replace("#<title>[^<]*</title>#i", "", $prolog);
			elseif(preg_match("#<title>[^<]*</title>#i", $prolog))
				$prolog = preg_replace("#<title>[^<]*</title>#i", "<title>".$title."</title>", $prolog);
			else
				$prolog = $prolog."\n<title>".htmlspecialcharsbx($title)."</title>\n";
		}
		else
		{
			$p = strpos(substr($prolog, $p), ")") + $p;
			$prolog = substr($prolog, 0, $p+1).";\n\$APPLICATION->SetTitle(\"".EscapePHPString($title)."\")".substr($prolog, $p+1);
		}
	}
	return $prolog;
}

function SetPrologProperty($prolog, $property_key, $property_val)
{
	if (preg_match("'(\\\$APPLICATION->SetPageProperty\(\"".preg_quote(EscapePHPString($property_key), "'")."\" *, *\")(.*?)(?<!\\\\)(\"\);[\r\n]*)'i", $prolog, $regs))
	{
		if (strlen($property_val)<=0)
			$prolog = str_replace($regs[1].$regs[2].$regs[3], "", $prolog);
		else
			$prolog = str_replace($regs[1].$regs[2].$regs[3], $regs[1].EscapePHPString($property_val).$regs[3], $prolog);
	}
	else
	{
		if (strlen($property_val)>0)
		{
			$p = strpos($prolog, "prolog_before");
			if($p===false)
				$p = strpos($prolog, "prolog.php");
			if($p===false)
				$p = strpos($prolog, "header.php");
			if($p!==false)
			{
				$p = strpos(substr($prolog, $p), ")") + $p;
				$prolog = substr($prolog, 0, $p+1).";\n\$APPLICATION->SetPageProperty(\"".EscapePHPString($property_key)."\", \"".EscapePHPString($property_val)."\")".substr($prolog, $p+1);
			}
		}
	}
	return $prolog;
}

function IsPHP($src)
{
	if(strpos($src, "<?")!==false)
		return true;
	if(preg_match("/(<script[^>]*language\s*=\s*)('|\"|)php('|\"|)([^>]*>)/i", $src))
		return true;
	return false;
}
?>