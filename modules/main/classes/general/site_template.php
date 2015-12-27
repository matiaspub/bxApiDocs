<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CSiteTemplate
{
	var $LAST_ERROR;

	public static function GetList($arOrder=array(), $arFilter=array(), $arSelect=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(isset($arFilter["ID"]) && !is_array($arFilter["ID"]))
			$arFilter["ID"] = array($arFilter["ID"]);
		if(isset($arFilter["TYPE"]) && !is_array($arFilter["TYPE"]))
			$arFilter["TYPE"] = array($arFilter["TYPE"]);

		$folders = array(
			"/local/templates",
			BX_PERSONAL_ROOT."/templates",
		);
		$arRes = array();
		foreach($folders as $folder)
		{
			$path = $_SERVER["DOCUMENT_ROOT"].$folder;
			if(is_dir($path))
			{
				$handle = opendir($path);
				if($handle)
				{
					while(($file = readdir($handle)) !== false)
					{
						if($file == "." || $file == ".." || !is_dir($path."/".$file))
							continue;

						if($file == ".default")
							continue;

						if(isset($arRes[$file]))
							continue;

						if(isset($arFilter["ID"]) && !in_array($file, $arFilter["ID"]))
							continue;

						$arTemplate = array("DESCRIPTION" => "");

						if(file_exists(($fname = $path."/".$file."/lang/".LANGUAGE_ID."/description.php")))
							__IncludeLang($fname, false, true);
						elseif(file_exists(($fname = $path."/".$file."/lang/".LangSubst(LANGUAGE_ID)."/description.php")))
							__IncludeLang($fname, false, true);

						if(file_exists(($fname = $path."/".$file."/description.php")))
							include($fname);

						if(!isset($arTemplate["TYPE"])) $arTemplate["TYPE"] = '';
						if(isset($arFilter["TYPE"]) && !in_array($arTemplate["TYPE"], $arFilter["TYPE"]))
							continue;

						$arTemplate["ID"] = $file;
						$arTemplate["PATH"] = $folder."/".$file;

						if(!isset($arTemplate["NAME"]))
							$arTemplate["NAME"] = $file;

						if($arSelect === false || in_array("SCREENSHOT", $arSelect))
						{
							if(file_exists($path."/".$file."/lang/".LANGUAGE_ID."/screen.gif"))
								$arTemplate["SCREENSHOT"] = $folder."/".$file."/lang/".LANGUAGE_ID."/screen.gif";
							elseif(file_exists($path."/".$file."/screen.gif"))
								$arTemplate["SCREENSHOT"] = $folder."/".$file."/screen.gif";
							else
								$arTemplate["SCREENSHOT"] = false;

							if(file_exists($path."/".$file."/lang/".LANGUAGE_ID."/preview.gif"))
								$arTemplate["PREVIEW"] = $folder."/".$file."/lang/".LANGUAGE_ID."/preview.gif";
							elseif(file_exists($path."/".$file."/preview.gif"))
								$arTemplate["PREVIEW"] = $folder."/".$file."/preview.gif";
							else
								$arTemplate["PREVIEW"] = false;
						}

						if($arSelect === false || in_array("CONTENT", $arSelect))
						{
							$arTemplate["CONTENT"] = $APPLICATION->GetFileContent($path."/".$file."/header.php")."#WORK_AREA#".$APPLICATION->GetFileContent($path."/".$file."/footer.php");
						}

						if($arSelect === false || in_array("STYLES", $arSelect))
						{
							if(file_exists($path."/".$file."/styles.css"))
							{
								$arTemplate["STYLES"] = $APPLICATION->GetFileContent($path."/".$file."/styles.css");
								$arTemplate["STYLES_TITLE"] = CSiteTemplate::__GetByStylesTitle($path."/".$file."/.styles.php");
							}

							if(file_exists($path."/".$file."/template_styles.css"))
								$arTemplate["TEMPLATE_STYLES"] = $APPLICATION->GetFileContent($path."/".$file."/template_styles.css");
						}

						$arRes[$file] = $arTemplate;
					}
					closedir($handle);
				}
			}
		}

		if(is_array($arOrder))
		{
			$columns = array();
			static $fields = array("ID"=>1, "NAME"=>1, "DESCRIPTION"=>1, "SORT"=>1);
			foreach($arOrder as $key => $val)
			{
				$key = strtoupper($key);
				if(isset($fields[$key]))
				{
					$columns[$key] = (strtoupper($val) == "DESC"? SORT_DESC : SORT_ASC);
				}
			}
			if(!empty($columns))
			{
				\Bitrix\Main\Type\Collection::sortByColumn($arRes, $columns);
			}
		}

		$db_res = new CDBResult;
		$db_res->InitFromArray($arRes);

		return $db_res;
	}

	public static function __GetByStylesTitle($file)
	{
		$io = CBXVirtualIo::GetInstance();
		if ($io->FileExists($file))
			return include($file);
		return false;
	}

	public static function GetByID($ID)
	{
		return CSiteTemplate::GetList(array(), array("ID"=>$ID));
	}

	public function CheckFields($arFields, $ID=false)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$this->LAST_ERROR = "";
		$arMsg = array();

		if($ID === false)
		{
			if($arFields["ID"] == '')
				$this->LAST_ERROR .= GetMessage("MAIN_ENTER_TEMPLATE_ID")." ";
			elseif(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$arFields["ID"]))
				$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_ID_EX")." ";

			if(!isset($arFields["CONTENT"]))
				$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_CONTENT_NA")." ";
		}

		if(isset($arFields["CONTENT"]) && $arFields["CONTENT"] == '')
		{
			$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_CONTENT_NA")." ";
			$arMsg[] = array("id"=>"CONTENT", "text"=> GetMessage("MAIN_TEMPLATE_CONTENT_NA"));
		}
		elseif(isset($arFields["CONTENT"]) && strpos($arFields["CONTENT"], "#WORK_AREA#") === false)
		{
			$this->LAST_ERROR .= GetMessage("MAIN_TEMPLATE_WORKAREA_NA")." ";
			$arMsg[] = array("id"=>"CONTENT", "text"=> GetMessage("MAIN_TEMPLATE_WORKAREA_NA"));
		}

		if(!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$APPLICATION->ThrowException($e);
		}

		if($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	public function Add($arFields)
	{
		if(!$this->CheckFields($arFields))
			return false;

		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$path = BX_PERSONAL_ROOT."/templates/".$arFields["ID"];
		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$path);

		if(isset($arFields["CONTENT"]))
		{
			$p = strpos($arFields["CONTENT"], "#WORK_AREA#");
			$header = substr($arFields["CONTENT"], 0, $p);
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/header.php", $header);
			$footer = substr($arFields["CONTENT"], $p + strlen("#WORK_AREA#"));
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/footer.php", $footer);
		}
		if(isset($arFields["STYLES"]))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/styles.css", $arFields["STYLES"]);
		}

		if(isset($arFields["TEMPLATE_STYLES"]))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/template_styles.css", $arFields["TEMPLATE_STYLES"]);
		}

		if(isset($arFields["NAME"]) || isset($arFields["DESCRIPTION"]) || isset($arFields["SORT"]))
		{
			self::SaveDescription($arFields, $_SERVER["DOCUMENT_ROOT"].$path."/description.php");
		}

		self::SaveStyleDescription($arFields["STYLES_DESCRIPTION"], $_SERVER["DOCUMENT_ROOT"].$path."/.styles.php");

		return $arFields["ID"];
	}


	public function Update($ID, $arFields)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!$this->CheckFields($arFields, $ID))
		{
			return false;
		}

		$path = getLocalPath("templates/".$ID, BX_PERSONAL_ROOT);
		if($path === false)
		{
			return false;
		}
		if(isset($arFields["CONTENT"]))
		{
			$p = strpos($arFields["CONTENT"], "#WORK_AREA#");
			$header = substr($arFields["CONTENT"], 0, $p);
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/header.php", $header);
			$footer = substr($arFields["CONTENT"], $p + strlen("#WORK_AREA#"));
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/footer.php", $footer);
		}
		if(isset($arFields["STYLES"]))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/styles.css", $arFields["STYLES"]);
		}

		if(isset($arFields["TEMPLATE_STYLES"]))
		{
			$APPLICATION->SaveFileContent($_SERVER["DOCUMENT_ROOT"].$path."/template_styles.css", $arFields["TEMPLATE_STYLES"]);
		}

		if(isset($arFields["NAME"]) || isset($arFields["DESCRIPTION"]) || isset($arFields["SORT"]) || isset($arFields["TYPE"]))
		{
			$db_t = CSiteTemplate::GetList(array(), array("ID" => $ID), array("NAME", "DESCRIPTION", "SORT"));
			$ar_t = $db_t->Fetch();

			if(!isset($arFields["NAME"]))
				$arFields["NAME"] = $ar_t["NAME"];
			if(!isset($arFields["DESCRIPTION"]))
				$arFields["DESCRIPTION"] = $ar_t["DESCRIPTION"];
			if(!isset($arFields["SORT"]))
				$arFields["SORT"] = $ar_t["SORT"];
			if(!isset($arFields["TYPE"]))
				$arFields["TYPE"] = $ar_t["TYPE"];
			if(!isset($arFields["EDITOR_STYLES"]))
				$arFields["EDITOR_STYLES"] = $ar_t["EDITOR_STYLES"];

			self::SaveDescription($arFields, $_SERVER["DOCUMENT_ROOT"].$path."/description.php");
		}

		self::SaveStyleDescription($arFields["STYLES_DESCRIPTION"], $_SERVER["DOCUMENT_ROOT"].$path."/.styles.php");

		return true;
	}

	public static function Delete($ID)
	{
		if($ID == ".default")
		{
			return false;
		}

		$path = getLocalPath("templates/".$ID, BX_PERSONAL_ROOT);
		if($path === false)
		{
			return false;
		}

		DeleteDirFilesEx($path);
		return true;
	}

	public static function GetContent($ID)
	{
		if(strlen($ID)<=0)
			$arRes = array();
		else
			$arRes = CSiteTemplate::DirsRecursive($ID);
		$db_res = new CDBResult;
		$db_res->InitFromArray($arRes);
		return $db_res;
	}

	public static function DirsRecursive($ID, $path="", $depth=0, $maxDepth=1)
	{
		$arRes = array();
		$depth++;

		$templPath = getLocalPath("templates/".$ID, BX_PERSONAL_ROOT);
		if($templPath === false)
		{
			return $arRes;
		}

		GetDirList($templPath."/".$path, $arDirsTmp, $arResTmp);

		foreach($arResTmp as $file)
		{
			switch($file["NAME"])
			{
				case "chain_template.php":
					$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_NAV");
					break;
				case "":
					$file["DESCRIPTION"] = "";
					break;
				default:
					if(($p=strpos($file["NAME"], ".menu_template.php"))!==false)
						$file["DESCRIPTION"] = str_replace("#MENU_TYPE#", substr($file["NAME"], 0, $p), GetMessage("MAIN_TEMPLATE_MENU"));
					elseif(($p=strpos($file["NAME"], "authorize_registration.php"))!==false)
						$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_AUTH_REG");
					elseif(($p=strpos($file["NAME"], "forgot_password.php"))!==false)
						$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_SEND_PWD");
					elseif(($p=strpos($file["NAME"], "change_password.php"))!==false)
						$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_CHN_PWD");
					elseif(($p=strpos($file["NAME"], "authorize.php"))!==false)
						$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_AUTH");
					elseif(($p=strpos($file["NAME"], "registration.php"))!==false)
						$file["DESCRIPTION"] = GetMessage("MAIN_TEMPLATE_REG");
			}
			$arRes[] = $file;
		}

		$nTemplateLen = strlen($templPath."/");
		foreach($arDirsTmp as $dir)
		{
			$arDir = $dir;
			$arDir["DEPTH_LEVEL"] = $depth;
			$arRes[] = $arDir;

			if($depth < $maxDepth)
			{
				$dirPath = substr($arDir["ABS_PATH"], $nTemplateLen);
				$arRes = array_merge($arRes, CSiteTemplate::DirsRecursive($ID, $dirPath, $depth, $maxDepth));
			}
		}
		return $arRes;
	}

	public static function SaveStyleDescription($stylesDesc = array(), $stylesPath)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(isset($stylesDesc) && is_array($stylesDesc))
		{
			$curStylesDesc = CSiteTemplate::__GetByStylesTitle($stylesPath);
			if (is_array($curStylesDesc))
			{
				foreach($curStylesDesc as $code => $val)
				{
					if (!is_array($curStylesDesc[$code]))
						unset($curStylesDesc[$code]);
				}
			}
			foreach($stylesDesc as $code => $val)
			{
				if (!isset($curStylesDesc[EscapePHPString($code)]) || !is_array($curStylesDesc[EscapePHPString($code)]))
				{
					$curStylesDesc[EscapePHPString($code)] = EscapePHPString($val);
				}
			}
			$APPLICATION->SaveFileContent($stylesPath, '<'.'?'."\nreturn ".var_export($curStylesDesc, 1).";\n".'?'.'>');
		}
	}

	public static function SaveDescription($arFields = array(), $descPath)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		$arDescription = array(
			"NAME" => EscapePHPString($arFields['NAME']),
			"DESCRIPTION" => EscapePHPString($arFields['DESCRIPTION']),
			"SORT" => (intval($arFields['SORT']) > 0? intval($arFields['SORT']) : ''),
			"TYPE" => $arFields['TYPE']
		);
		if (isset($arFields['EDITOR_STYLES']))
			$arDescription["EDITOR_STYLES"] = $arFields['EDITOR_STYLES'];
		$APPLICATION->SaveFileContent($descPath, '<'.'?'."\n\$arTemplate = ".var_export($arDescription, 1).";\n".'?'.'>');
	}

}
