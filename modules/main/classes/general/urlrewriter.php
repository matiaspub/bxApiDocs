<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

class CUrlRewriter
{
	static $arRules = array();

	public static function GetKey($arRule)
	{
		return md5($arRule["CONDITION"]."|".$arRule["RULE"]."|".$arRule["ID"]."|".$arRule["PATH"]);
	}

	public static function GetList($arFilter = array(), $arOrder = array())
	{
		global $APPLICATION;

		if (!array_key_exists("SITE_ID", $arFilter))
			$arFilter["SITE_ID"] = SITE_ID;

		if (array_key_exists("QUERY", $arFilter) && $arFilter["QUERY"] === false)
			$arFilter["QUERY"] = $APPLICATION->GetCurPage();

		$docRoot = CSite::GetSiteDocRoot($arFilter["SITE_ID"]);
		$arUrlRewrite = array();
		if (file_exists($docRoot."/urlrewrite.php"))
			include($docRoot."/urlrewrite.php");
		if(!is_array(self::$arRules[$docRoot]))
			self::$arRules[$docRoot] = array();
		foreach($arUrlRewrite as $arRule)
			self::$arRules[$docRoot][self::GetKey($arRule)] = $arRule;

		$bFilterQuery = array_key_exists("QUERY", $arFilter);
		$bFilterCondition = array_key_exists("CONDITION", $arFilter);
		$bFilterID = array_key_exists("ID", $arFilter);
		$bFilterPath = array_key_exists("PATH", $arFilter);

		$arResult = array();
		foreach($arUrlRewrite as $arRule)
		{
			$bMatch = true;
			if ($bFilterQuery && !preg_match($arRule["CONDITION"], $arFilter["QUERY"]))
				$bMatch = false;
			if ($bMatch && $bFilterCondition && $arRule["CONDITION"] != $arFilter["CONDITION"])
				$bMatch = false;
			if ($bMatch && $bFilterID && $arRule["ID"] != $arFilter["ID"])
				$bMatch = false;
			if ($bMatch && $bFilterPath && $arRule["PATH"] != $arFilter["PATH"])
				$bMatch = false;

			if ($bMatch)
			{
				$arResult[] = array(
					"CONDITION" => $arRule["CONDITION"],
					"ID" => $arRule["ID"],
					"PATH" => $arRule["PATH"],
					"RULE" => $arRule["RULE"],
				);
			}
		}

		if (count($arOrder) > 0 && count($arResult) > 0)
		{
			$arCondition = array();
			$arID = array();
			$arPath = array();
			$arRule = array();

			foreach ($arResult as $key => $row)
			{
				$arCondition[$key]  = $row['CONDITION'];
				$arID[$key] = $row['ID'];
				$arPath[$key] = $row['PATH'];
				$arRule[$key] = $row['RULE'];
			}

			$arOrderKeys = array_keys($arOrder);
			$orderBy = $arOrderKeys[0];
			$orderDir = $arOrder[$orderBy];

			$orderBy = StrToUpper($orderBy);
			$orderDir = StrToUpper($orderDir);

			$orderDir = (($orderDir == "DESC") ? SORT_DESC : SORT_ASC);

			if ($orderBy == "CONDITION")
				array_multisort($arCondition, $orderDir, $arID, $arPath, $arRule);
			elseif ($orderBy == "ID")
				array_multisort($arID, $orderDir, $arCondition, $arPath, $arRule);
			elseif ($orderBy == "PATH")
				array_multisort($arPath, $orderDir, $arCondition, $arID, $arRule);
			elseif ($orderBy == "RULE")
				array_multisort($arRule, $orderDir, $arCondition, $arPath, $arID);

			$arResult = array();
			foreach ($arCondition as $key => $value)
			{
				$arResult[] = array(
					"CONDITION" => $arCondition[$key],
					"ID" => $arID[$key],
					"PATH" => $arPath[$key],
					"RULE" => $arRule[$key],
				);
			}
		}

		return $arResult;
	}

	public static function printArray($arr)
	{
		$output = "\$arUrlRewrite = array(\n";

		foreach ($arr as $val)
		{
			$output .= "\tarray(\n";
			foreach ($val as $key1 => $val1)
				$output .= "\t\t\"".EscapePHPString($key1)."\" => \"".EscapePHPString($val1)."\",\n";
			$output .= "\t),\n";
		}

		$output .= ");\n";

		return $output;
	}

	public static function __RecordsCompare($a, $b)
	{
		$len_a = strlen($a["CONDITION"]);
		$len_b = strlen($b["CONDITION"]);
		if ($len_a < $len_b)
			return 1;
		elseif ($len_a > $len_b)
			return -1;
		else
			return 0;
	}

	public static function Add($arFields)
	{
		if (!array_key_exists("SITE_ID", $arFields))
			$arFields["SITE_ID"] = SITE_ID;

		$docRoot = CSite::GetSiteDocRoot($arFields["SITE_ID"]);
		$arUrlRewrite = array();
		if (file_exists($docRoot."/urlrewrite.php"))
			include($docRoot."/urlrewrite.php");
		if(!is_array(self::$arRules[$docRoot]))
			self::$arRules[$docRoot] = array();
		foreach($arUrlRewrite as $arRule)
			self::$arRules[$docRoot][self::GetKey($arRule)] = $arRule;

		self::$arRules[$docRoot][self::GetKey($arFields)] = array(
			"CONDITION" => $arFields["CONDITION"],
			"RULE" => $arFields["RULE"],
			"ID" => $arFields["ID"],
			"PATH" => $arFields["PATH"]
		);

		uasort(self::$arRules[$docRoot], array("CUrlRewriter", "__RecordsCompare"));
		if ($f = fopen($docRoot."/urlrewrite.php", "w"))
		{
			fwrite($f, "<"."?\n".CUrlRewriter::printArray(self::$arRules[$docRoot])."\n?".">");
			fclose($f);
			bx_accelerator_reset();
			return true;
		}

		return false;
	}

	public static function Update($arFilter, $arFields)
	{
		global $APPLICATION;

		if (!array_key_exists("SITE_ID", $arFilter))
			$arFilter["SITE_ID"] = SITE_ID;

		if (array_key_exists("QUERY", $arFilter) && $arFilter["QUERY"] === false)
			$arFilter["QUERY"] = $APPLICATION->GetCurPage();

		$docRoot = CSite::GetSiteDocRoot($arFilter["SITE_ID"]);
		$arUrlRewrite = array();
		if (file_exists($docRoot."/urlrewrite.php"))
			include($docRoot."/urlrewrite.php");
		if(!is_array(self::$arRules[$docRoot]))
			self::$arRules[$docRoot] = array();
		foreach($arUrlRewrite as $arRule)
			self::$arRules[$docRoot][self::GetKey($arRule)] = $arRule;

		$bFilterQuery = array_key_exists("QUERY", $arFilter);
		$bFilterCondition = array_key_exists("CONDITION", $arFilter);
		$bFilterID = array_key_exists("ID", $arFilter);
		$bFilterPath = array_key_exists("PATH", $arFilter);
		$bFound = false;

		foreach(self::$arRules[$docRoot] as $key => $arRule)
		{
			if ($bFilterQuery && !preg_match($arRule["CONDITION"], $arFilter["QUERY"]))
			{
				continue;
			}
			if ($bFilterCondition && $arRule["CONDITION"] != $arFilter["CONDITION"])
			{
				continue;
			}
			if ($bFilterID && $arRule["ID"] != $arFilter["ID"])
			{
				continue;
			}
			if ($bFilterPath && $arRule["PATH"] != $arFilter["PATH"])
			{
				continue;
			}

			$bFound = true;
			if (array_key_exists("CONDITION", $arFields))
				self::$arRules[$docRoot][$key]["CONDITION"] = $arFields["CONDITION"];
			if (array_key_exists("RULE", $arFields))
				self::$arRules[$docRoot][$key]["RULE"] = $arFields["RULE"];
			if (array_key_exists("ID", $arFields))
				self::$arRules[$docRoot][$key]["ID"] = $arFields["ID"];
			if (array_key_exists("PATH", $arFields))
				self::$arRules[$docRoot][$key]["PATH"] = $arFields["PATH"];
		}

		if ($bFound)
		{
			uasort(self::$arRules[$docRoot], array("CUrlRewriter", "__RecordsCompare"));
			if ($f = fopen($docRoot."/urlrewrite.php", "w"))
			{
				fwrite($f, "<"."?\n".CUrlRewriter::printArray(self::$arRules[$docRoot])."\n?".">");
				fclose($f);
				bx_accelerator_reset();
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}

	public static function Delete($arFilter)
	{
		global $APPLICATION;

		if (!array_key_exists("SITE_ID", $arFilter))
			$arFilter["SITE_ID"] = SITE_ID;

		if (array_key_exists("QUERY", $arFilter) && $arFilter["QUERY"] === false)
			$arFilter["QUERY"] = $APPLICATION->GetCurPage();


		$docRoot = CSite::GetSiteDocRoot($arFilter["SITE_ID"]);

		if (!file_exists($docRoot."/urlrewrite.php"))
			return true;

		$arUrlRewrite = array();
		if (file_exists($docRoot."/urlrewrite.php"))
			include($docRoot."/urlrewrite.php");
		if(!is_array(self::$arRules[$docRoot]))
			self::$arRules[$docRoot] = array();
		foreach($arUrlRewrite as $arRule)
			self::$arRules[$docRoot][self::GetKey($arRule)] = $arRule;

		$bFilterQuery = array_key_exists("QUERY", $arFilter);
		$bFilterCondition = array_key_exists("CONDITION", $arFilter);
		$bFilterID = array_key_exists("ID", $arFilter);
		$bFilterPath = array_key_exists("PATH", $arFilter);
		$bFound = false;

		foreach (self::$arRules[$docRoot] as $key => $arRule)
		{
			if ($bFilterQuery && !preg_match($arRule["CONDITION"], $arFilter["QUERY"]))
			{
				continue;
			}
			if ($bFilterCondition && $arRule["CONDITION"] != $arFilter["CONDITION"])
			{
				continue;
			}
			if (
				$bFilterID
				&& (
					($arFilter["ID"] != "NULL" && $arRule["ID"] != $arFilter["ID"])
					|| ($arFilter["ID"] == "NULL" && strlen($arRule["ID"]) <= 0)
				)
			)
			{
				continue;
			}
			if ($bFilterPath && $arRule["PATH"] != $arFilter["PATH"])
			{
				continue;
			}

			unset(self::$arRules[$docRoot][$key]);
			$bFound = true;
		}

		if ($bFound)
		{
			uasort(self::$arRules[$docRoot], array("CUrlRewriter", "__RecordsCompare"));
			if ($f = fopen($docRoot."/urlrewrite.php", "w"))
			{
				fwrite($f, "<"."?\n".CUrlRewriter::printArray(self::$arRules[$docRoot])."\n?".">");
				fclose($f);
				bx_accelerator_reset();
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
		}
	}

	public static function ReIndexAll($max_execution_time = 0, $NS = array())
	{
		@set_time_limit(0);
		if(!is_array($NS))
			$NS = array();

		if($max_execution_time<=0)
		{
			$NS_OLD=$NS;
			$NS = array(
				"CLEAR" => "N",
				"ID" => "",
				"FLG" => "",
				"SESS_ID" => md5(uniqid("")),
				"max_execution_time" => $NS_OLD["max_execution_time"],
				"stepped" => $NS_OLD["stepped"],
				"max_file_size" => $NS_OLD["max_file_size"]
			);

			if($NS_OLD["SITE_ID"]!="") $NS["SITE_ID"]=$NS_OLD["SITE_ID"];
		}
		$NS["CNT"] = intval($NS["CNT"]);

		$arLangDirs = array();
		$arFilter = array("ACTIVE"=>"Y");
		if($NS["SITE_ID"]!="")
			$arFilter["ID"]=$NS["SITE_ID"];
		$r = CSite::GetList($by="sort", $order="asc", $arFilter);
		while($arR = $r->Fetch())
		{
			$path = trim($arR["DIR"], "/");
			$key = trim($arR["ABS_DOC_ROOT"], "/")."/".(strlen($path) > 0 ? $path."/" : "");
			if (!array_key_exists($key, $arLangDirs))
				$arLangDirs[$key] = $arR;
		}

		if($NS["CLEAR"]!="Y")
		{
			foreach($arLangDirs as $arR)
			{
				CUrlRewriter::Delete(
					array(
						"SITE_ID" => $arR["ID"],
						"ID" => "NULL"
					)
				);
			}
		}
		$NS["CLEAR"] = "Y";

		//get rid of duplicates
		$dub = array();
		foreach($arLangDirs as $path=>$arR)
		{
			foreach($arLangDirs as $path2=>$arR2)
			{
				if($path==$path2) continue;
				if(substr($path, 0, strlen($path2)) == $path2)
					$dub[] = $path;
			}
		}

		foreach($dub as $p)
			unset($arLangDirs[$p]);

		clearstatcache();

		foreach($arLangDirs as $arR)
		{
			$site = $arR["ID"];
			$path = $arR["DIR"];

			while(($l=strlen($path))>0 && $path[$l-1]=="/")
				$path = substr($path, 0, $l-1);

			if($max_execution_time>0 && strlen($NS["FLG"]) > 0 && substr($NS["ID"]."/", 0, strlen($site."|".$path."/")) != $site."|".$path."/")
				continue;

			CUrlRewriter::RecurseIndex(array($site, $path), $max_execution_time, $NS);

			if($max_execution_time>0 && strlen($NS["FLG"]) > 0)
				return $NS;
		}

		return $NS["CNT"];
	}

	public static function RecurseIndex($path=array(), $max_execution_time = 0, &$NS)
	{
		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);
		$abs_path = $DOC_ROOT.$path;

		if(strlen($site)<=0)
			return 0;

		if(!file_exists($abs_path) || !is_dir($abs_path))
			return 0;
		$handle  = @opendir($abs_path);
		while(false !== ($file = @readdir($handle)))
		{
			if($file == "." || $file == "..") continue;

			$full_path = $path."/".$file;

			if(is_dir($abs_path."/".$file))
			{
				if(
					$full_path == "/bitrix"
					|| $full_path == "/".COption::GetOptionString("main", "upload_dir", "upload")
					|| $full_path == "/local"
				)
				{
					continue;
				}

				//this is not first step and we had stopped here, so go on to reindex
				if($max_execution_time<=0 || strlen($NS["FLG"])<=0 || (strlen($NS["FLG"]) > 0 && substr($NS["ID"]."/", 0, strlen($site."|".$full_path."/")) == $site."|".$full_path."/"))
				{
					$new_site = CSite::GetSiteByFullPath($DOC_ROOT.$full_path);
					if(CUrlRewriter::RecurseIndex(array($new_site, $full_path), $max_execution_time, $NS)===false)
						return false;
				}
				else //all done
				{
					continue;
				}
			}
			else
			{
				//not the first step and we found last file from previos one

				if($max_execution_time>0
					&& strlen($NS["FLG"])>0
					&& $NS["ID"] == $site."|".$full_path
					)
				{
					$NS["FLG"] = "";
				}
				elseif(strlen($NS["FLG"])<=0)
				{
					$ID = CUrlRewriter::ReindexFile(array($site, $full_path), $NS["SESS_ID"], $NS["max_file_size"]);
					if($ID)
					{
						$NS["CNT"] = intval($NS["CNT"]) + 1;
					}
				}

				if($max_execution_time>0 && (getmicrotime() - START_EXEC_TIME > $max_execution_time))
				{
					$NS["FLG"] = "Y";
					$NS["ID"] = $site."|".$full_path;
					return false;
				}
			}
		}
		return true;
	}

	public static function ReindexFile($path, $SEARCH_SESS_ID="", $max_file_size = 0)
	{
		global $APPLICATION;

		CMain::InitPathVars($site, $path);
		$DOC_ROOT = CSite::GetSiteDocRoot($site);

		if(!CUrlRewriter::CheckPath($path))
			return 0;

		if($max_file_size>0 && filesize($DOC_ROOT."/".$path)>$max_file_size*1024)
			return 0;

		$filesrc = $APPLICATION->GetFileContent($DOC_ROOT."/".$path);

		if(!$filesrc || $filesrc == "")
			return 0;

		$arComponents = PHPParser::ParseScript($filesrc);
		for ($i = 0, $cnt = count($arComponents); $i < $cnt; $i++)
		{
			if ($arComponents[$i]["DATA"]["PARAMS"]["SEF_MODE"] == "Y")
			{
				if (array_key_exists("SEF_RULE", $arComponents[$i]["DATA"]["PARAMS"]))
				{
					$ruleMaker = new \Bitrix\Main\UrlRewriterRuleMaker;
					$ruleMaker->process($arComponents[$i]["DATA"]["PARAMS"]["SEF_RULE"]);

					CUrlRewriter::Add(array(
						"SITE_ID" => $site,
						"CONDITION" => $ruleMaker->getCondition(),
						"RULE" => $ruleMaker->getRule(),
						"ID" => $arComponents[$i]["DATA"]["COMPONENT_NAME"],
						"PATH" => $path
					));
				}
				else
				{
					CUrlRewriter::Add(array(
						"SITE_ID" => $site,
						"CONDITION" => "#^".$arComponents[$i]["DATA"]["PARAMS"]["SEF_FOLDER"]."#",
						"RULE" => "",
						"ID" => $arComponents[$i]["DATA"]["COMPONENT_NAME"],
						"PATH" => $path
					));
				}
			}
		}

		return true;
	}

	public static function CheckPath($path)
	{
		static $SEARCH_MASKS_CACHE = false;
		if(is_array($SEARCH_MASKS_CACHE))
		{
			$arExc = $SEARCH_MASKS_CACHE["exc"];
			$arInc = $SEARCH_MASKS_CACHE["inc"];
		}
		else
		{
			$arInc = $arExc = array();
			$inc = COption::GetOptionString("main", "urlrewrite_include_mask", "*.php");
			$inc = str_replace("'", "\\'", str_replace("*", ".*?", str_replace("?", ".", str_replace(".", "\\.", str_replace("\\", "/", $inc)))));
			$arIncTmp = explode(";", $inc);
			for($i = 0, $n = count($arIncTmp); $i < $n; $i++)
				if(strlen(trim($arIncTmp[$i]))>0)
					$arInc[] = "'^".trim($arIncTmp[$i])."$'";

			$exc = COption::GetOptionString("main", "urlrewrite_exclude_mask", "/bitrix/*;");
			$exc = str_replace("'", "\\'", str_replace("*", ".*?", str_replace("?", ".", str_replace(".", "\\.", str_replace("\\", "/", $exc)))));
			$arExcTmp = explode(";", $exc);
			for($i = 0, $n = count($arExcTmp); $i < $n; $i++)
				if(strlen(trim($arExcTmp[$i]))>0)
					$arExc[] = "'^".trim($arExcTmp[$i])."$'";

			$SEARCH_MASKS_CACHE = array("exc"=>$arExc, "inc"=>$arInc);
		}

		$file = basename($path);
		if(substr($file, 0, 1)==".") return 0;

		for($i = 0, $n = count($arExc); $i < $n; $i++)
			if(preg_match($arExc[$i], $path))
				return false;

		for($i = 0, $n = count($arInc); $i < $n; $i++)
			if(preg_match($arInc[$i], $path))
				return true;

		return false;
	}
}
