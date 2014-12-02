<?
class CSnippets
{
	public static function LoadList($Params)
	{
		global $CACHE_MANAGER;

		$template = CFileMan::SecurePathVar($Params["template"]);
		if ($template == '')
			$template = '.default';

		$arSNIPPETS = false;
		$CACHE_SNIPPETS = Array();

		if ($Params['bClearCache'])
			CSnippets::ClearCache();

		$ttl = 30 * 24 * 60 * 60; // 30 days
		if($CACHE_MANAGER->Read($ttl, "fileman_snippet_array"))
		{
			$CACHE_SNIPPETS = $CACHE_MANAGER->Get("fileman_snippet_array");
			if (isset($CACHE_SNIPPETS[$template]))
				$arSNIPPETS = $CACHE_SNIPPETS[$template];
		}

		if (!$arSNIPPETS || !is_array($arSNIPPETS))
		{
			$arSNIPPETS = Array();
			$arTemplateKeys = Array(); //Array contain keys of snippets for each template for correct writing .content.php

			CSnippets::HandleForTemplate('.default', $arSNIPPETS, $arTemplateKeys);
			if ($template != '.default')
				CSnippets::HandleForTemplate($template, $arSNIPPETS, $arTemplateKeys);

			$CACHE_SNIPPETS[$template] = $arSNIPPETS;
			$CACHE_MANAGER->Set("fileman_snippet_array", $CACHE_SNIPPETS);
		}

		if ($Params['returnArray'])
		{
			return $arSNIPPETS;
		}
		else
		{
			?><script>window.arSnippets = <?= CUtil::PhpToJSObject($arSNIPPETS)?>; </script><?
		}
	}

	public static function HandleForTemplate($template, &$arSNIPPETS, &$arTemplateKeys)
	{
		$arTemplateKeys[$template] = Array();
		CSnippets::ReadDir($arSNIPPETS, $arTemplateKeys[$template], "", $template);

		if (count($arSNIPPETS) > 0)
			CSnippets::UpdateContentInfo($arSNIPPETS, $arTemplateKeys[$template], $template);
	}

	public static function ReadDir(&$arSNIPPETS, &$arKeys, $path, $template, $level = 0, $parent = "")
	{
		$basePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";

		$io = CBXVirtualIo::GetInstance();

		if(!$io->DirectoryExists($basePath))
			return;
		$imagesPath = $basePath."/images";

		CSnippets::WriteHtaccess($imagesPath);
		$bpPath = $basePath.($path == "" ? "" : "/").$path;

		$d = $io->GetDirectory($bpPath);
		$arChildren = $d->GetChildren();
		foreach ($arChildren as $child)
		{
			$file = $child->GetName();
			if($file == ".htaccess" || $file == ".content.php" || ($level == 0 && $file == "images"))
				continue;

			$filePath = $child->GetPathWithName();
			if($child->IsDirectory()) //if this is subfolder
			{
				$new_path = "".$path.($path == "" ? "" : "/").$file;
				CSnippets::ReadDir($arSNIPPETS, $arKeys, $new_path, $template, $level + 1, $file);
			}
			else // File
			{
				$name = $file;
				$ext = $child->GetExtension();

				// Rename file *.* => *.snp
				if ($ext != 'snp')
				{
					$name = str_replace($ext, "snp", $name);
					if (strpos($name, ".snp") === false)
					{
						$name = $name.".snp";
					}

					if (!$io->FileExists($bpPath."/".$name))
					{
						$io->Rename($filePath, $bpPath."/".$name);
					}
					else
					{
						for ($n = 1; $n < 256; $n++)
						{
							$name_ = str_replace(".snp", "(".$n.").snp", $name);
							if (!$io->FileExists($bpPath."/".$name_))
							{
								$name = $name_;
								$io->Rename($filePath, $bpPath."/".$name);
								break;
							}
						}
					}
				}

				$key = $path.($path != '' ? '/' : '').$name;
				$arSNIPPETS[$key] = Array(
					'name' => $name,
					'path' => $path,
					'title' => $name,
					'thumb' => '',
					'code' => CSnippets::GetCode($bpPath."/".$name),
					'description' => "",
					'template' => $template,
					'level' => $level,
					'parent' => $parent
				);

				$arKeys[$key] = Array(
					'name' => $name,
					'path' => $path,
					'title' => $name,
					'description' => ""
				);
			}
		}
	}

	public static function UpdateContentInfo(&$ar, &$arKeys, $template)
	{
		$path = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";

		if (file_exists($path."/.content.php"))
		{
			@include($path."/.content.php");
			$arK = array_keys($SNIPPETS);
			for ($i=0, $len = count($arK); $i<$len;$i++)
			{
				$name = $arK[$i];
				$pos = strrpos($name,".");
				$f_name = ($pos !== FALSE) ? substr($name,0,$pos) : $name;
				if ($ar[$f_name.".snp"])
				{
					$ar[$f_name.".snp"]['title'] = stripslashes($SNIPPETS[$name]['title']);
					$ar[$f_name.".snp"]['description'] = stripslashes($SNIPPETS[$name]['description']);
				}
			}
		}
	}

	public static function WriteHtaccess($path)
	{
		$io = CBXVirtualIo::GetInstance();
		if($io->DirectoryExists($path) && !$io->FileExists($path."/.htaccess"))
			$GLOBALS['APPLICATION']->SaveFileContent($path."/.htaccess", "Allow from All");
	}

	public static function ClearCache()
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->Clean("fileman_snippet_array");
		$CACHE_MANAGER->Clean("fileman_snippet_group");
	}

	public static function GetCode($path)
	{
		$io = CBXVirtualIo::GetInstance();
		return $io->FileExists($path) ? $GLOBALS['APPLICATION']->GetFileContent($path) : '';
	}

	public static function Edit($Params)
	{
		global $APPLICATION;
		$name = CFileMan::SecurePathVar($Params['name']);
		$title = $Params['title'];
		$description = $Params['description'];
		$path = CFileMan::SecurePathVar($Params['path']);
		$template = CFileMan::SecurePathVar($Params['template']);
		$site = $Params['site'];
		$code = $Params['code'];
		$contPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
		$thumb = $Params['thumb'] === false ? false : CFileMan::SecurePathVar($Params['thumb']);

		if (!file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template))
		{
			?><script>alert('Error: Incorrect template Id: <?= CUtil::JSEscape($template)?>');</script><?
			return;
		}

		if ($Params['bNew'])
		{
			$location = CUtil::addslashes(CFileMan::SecurePathVar($Params["location"]));
			$newGroup = CUtil::addslashes(CFileMan::SecurePathVar($Params["newGroup"]));
			$path = trim(($location ? $location.'/' : '').($newGroup ? $newGroup.'/' : ''), ' /');

			if ($name == '')
			{
				$name = CSnippets::GetDefaultFileName($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets/".$path);
				?><script>window.__bx_res_sn_filename = "<?= CUtil::JSEscape($name);?>";</script><?
			}
			$name = $name.'.snp';
		}
		$key = $path.($path != '' ? '/' : '').$name;

		// 1. Save new snippet with new content
		if ($code)
			$APPLICATION->SaveFileContent($contPath.'/'.$key, $code);

		// 2. Rewrite title & description in .content.php
		if ($title || $description)
		{
			if (file_exists($contPath."/.content.php"))
				@include($contPath."/.content.php");
			else
				$SNIPPETS = array();

			if ($title)
				$SNIPPETS[$key]['title'] = $title;
			if ($description)
				$SNIPPETS[$key]['description'] = $description;

			$contentSrc = '<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>'.chr(10);
			$contentSrc .= '<?'.chr(10).'$SNIPPETS = Array();'.chr(10);
			foreach ($SNIPPETS as $k=>$_arSn)
			{
				if (CSnippets::CheckFile(array('site' => $Params["site"], 'template' => $Params['template'], 'path' => $k)))
					$contentSrc .= '$SNIPPETS[\''.CUtil::addslashes($k).'\'] = Array("title"=>\''.Cutil::addslashes($_arSn['title']).'\', "description"=>\''.Cutil::addslashes($_arSn['description']).'\');'.chr(10);
			}
			$contentSrc .= '?>';

			$APPLICATION->SaveFileContent($contPath."/.content.php", $contentSrc);
		}

		CSnippets::ClearCache();

		// 3. Handle thumbnail
		if ($thumb !== false)
		{
			if (substr($thumb,0,1) == '/')
				$thumb = substr($thumb,1);

			$pos = strrpos($name,".");
			if ($pos === FALSE)
				return true;

			//delete existent thumbnail
			$f_name = substr($name, 0, $pos);
			$img_path1 = BX_PERSONAL_ROOT.'/templates/'.$template.'/snippets/images/'.$path.($path == '' ?  '' : '/').$f_name;
			$DOC_ROOT = CSite::GetSiteDocRoot($site);
			$arExt = array("gif", "jpg", "jpeg", "png", "bmp");
			for ($i = 0, $c = count($arExt); $i < $c; $i++)
			{
				$p_ = $img_path1.".".$arExt[$i];
				if(file_exists($DOC_ROOT.$p_))
					CFileman::DeleteFile(Array($site, $p_));
			}

			if (empty($thumb) || strrpos($thumb, '.') === FALSE)
				return true;

			// Copy Thumbnail
			$path_from_1 = $DOC_ROOT."/".$thumb;
			$path_from = '/'.$thumb;

			if (file_exists($path_from_1))
			{
				$pos = strrpos($thumb,".");
				$f_ext = ($pos !== FALSE) ? strtolower(substr($thumb, $pos + 1)) : '';

				if (in_array($f_ext, $arExt))
				{
					$path_to = $img_path1.'.'.$f_ext;
					$strWarning_tmp = CFileMan::CopyEx(Array($site, $path_from), Array($site, $path_to));
				}
			}
		}
	}

	public static function Delete($Params)
	{
		global $APPLICATION;

		$snPath = BX_PERSONAL_ROOT."/templates/".CFileMan::SecurePathVar($Params['template'])."/snippets";
		$contPath = $_SERVER["DOCUMENT_ROOT"].$snPath;
		$path = CFileMan::SecurePathVar($Params["path"]);
		$key = $Params["path"].($Params["path"] == '' ? '' : '/').CFileMan::SecurePathVar($Params["name"]);

		//Delete snippet file
		CFileman::DeleteFile(Array($Params["site"], $snPath.'/'.$key));

		//Delete thumbnail
		if ($Params["thumb"] != '')
		{
			CFileman::DeleteFile(Array($Params["site"], $snPath.'/images/'.$path.($path == '' ?  '' : '/').CFileMan::SecurePathVar($Params["thumb"])));
		}

		if (file_exists($contPath."/.content.php"))
		{
			@include($contPath."/.content.php");
			$contentSrc = '<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>'.chr(10);
			$contentSrc .= '<?'.chr(10).'$SNIPPETS = Array();'.chr(10);
			foreach ($SNIPPETS as $k=>$_arSn)
			{
				if ($k != $key && CSnippets::CheckFile(array('site' => $Params["site"], 'template' => $Params['template'], 'path' => $k)))
					$contentSrc .= '$SNIPPETS[\''.CUtil::JSEscape($k).'\'] = Array("title"=>\''.CUtil::JSEscape($_arSn['title']).'\', "description"=>\''.CUtil::JSEscape($_arSn['description']).'\');'.chr(10);
			}
			$contentSrc .= '?>';
			$APPLICATION->SaveFileContent($contPath."/.content.php", $contentSrc);
		}

		CSnippets::ClearCache();
?>
<script>
window.operation_success = true;
</script>
<?
	}

	public static function CheckFile($params)
	{
		$contPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$params['template']."/snippets";
		return file_exists(CFileMan::SecurePathVar($contPath.'/'.$params['path']));
	}

	public static function GetGroups($Params)
	{
		$template = CFileMan::SecurePathVar($Params['template']);
		$arSnGroups = Array();
		CSnippets::InspectDir($arSnGroups, "", $template);
		CSnippets::DisplayJSGroups($template, $arSnGroups);
	}

	public static function GetGroupList($Params)
	{
		global $CACHE_MANAGER;
		$template = CFileMan::SecurePathVar($Params['template']);

		$arGroups = false;
		$CACHE_SNIPPETS = Array();

		if ($Params['bClearCache'])
			CSnippets::ClearCache();

		$ttl = 30 * 24 * 60 * 60; // 30 days
		if($CACHE_MANAGER->Read($ttl, "fileman_snippet_group"))
		{
			$CACHE_SNIPPETS = $CACHE_MANAGER->Get("fileman_snippet_group");
			if (isset($CACHE_SNIPPETS[$template]))
				$arGroups = $CACHE_SNIPPETS[$template];
		}

		if (!$arGroups || !is_array($arGroups))
		{
			$arGroups = Array();
			CSnippets::InspectDir($arGroups, "", $template);
			$CACHE_SNIPPETS[$template] = $arGroups;
			$CACHE_MANAGER->Set("fileman_snippet_group", $CACHE_SNIPPETS);
		}
		return $arGroups;
	}

	public static function SaveGroupList($Params)
	{

	}

	public static function InspectDir(&$arSnGroups, $path, $template, $level = 0, $parent = '')
	{
		$io = CBXVirtualIo::GetInstance();

		$basePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
		if(!$io->DirectoryExists($basePath))
			return;

		$bpPath = $basePath.($path == "" ? "" : "/").$path;
		if (!$level)
			$level = 0;

		$d = $io->GetDirectory($bpPath);
		$arChildren = $d->GetChildren();
		foreach ($arChildren as $child)
		{
			$file = $child->GetName();
			if($file == ".htaccess" || $file == ".content.php" || ($level == 0 && $file == "images") || !$child->IsDirectory())
				continue;

			$filePath = $child->GetPathWithName();
			$arSnGroups[] = Array
			(
				'path' => $path,
				'name' => $file,
				'level' => $level,
				'default_name' => CSnippets::GetDefaultFileName($filePath)
			);

			$new_path = "".$path.($path == "" ? "" : "/").$file;
			CSnippets::InspectDir($arSnGroups, $new_path, $template, $level + 1, $parent);
		}
	}

	public static function GetDefaultFileName($path)
	{
		$io = CBXVirtualIo::GetInstance();
		for ($i = 1; $i <= 9999; $i++)
		{
			$name = 'snippet'.str_pad($i, 4, "0", STR_PAD_LEFT);
			if (!$io->FileExists($path.'/'.$name.'.snp'))
				break;
		}
		return $name;
	}

	public static function DisplayJSGroups($template, $ar = array())
	{
		$template = CUtil::JSEscape(htmlspecialcharsex($template));
		?><script>
		window.arSnGroups['<?= $template?>'] = {};
		window.rootDefaultName['<?= $template?>'] = '<?= CSnippets::GetDefaultFileName($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets")?>';
		<?
		for($i=0,$len = count($ar); $i < $len; $i++)
		{
			$key = CUtil::JSEscape($ar[$i]['path'].($ar[$i]['path'] != '' ? '/' : '').$ar[$i]['name']);
		?>
window.arSnGroups['<?=$template?>']['<?= $key?>'] =
{
	name: '<?=CUtil::JSEscape($ar[$i]['name'])?>',
	path: '<?=CUtil::JSEscape($ar[$i]['path'])?>',
	level: '<?=CUtil::JSEscape($ar[$i]['level'])?>',
	default_name: '<?=CUtil::JSEscape($ar[$i]['default_name'])?>'
};
		<?
		}
		?></script><?
	}

	/**
	 * Adds a new snippet
	 *
	 * @param array - list of params
	 * @return bool or object - result
	 */
	public static function Add($params = array())
	{
		$params['new'] = true;
		return self::Update($params);
	}

	/**
	 * Updates existent snippet
	 *
	 * @param array - list of params
	 * @return bool or object - result
	 */
	public static function Update($params = array())
	{
		global $APPLICATION;
		$res = false;
		$title = $params['title'];
		$description = $params['description'];
		$currentPath = $params['new'] ? '' : CFileMan::SecurePathVar($params['current_path']);
		$path = CFileMan::SecurePathVar($params['path']);
		$template = CFileMan::SecurePathVar($params['template']);
		$code = $params['code'];
		$contPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
		$snippetPath = $contPath.($path == '' ? '' : '/'.$path);

		$io = CBXVirtualIo::GetInstance();
		if(!$io->DirectoryExists($contPath))
		{
			$io->CreateDirectory($contPath);
		}

		if ($params['new'])
		{
			$fileName = CSnippets::GetDefaultFileName($snippetPath).'.snp';
		}
		else
		{
			$currentPath = $contPath.'/'.$currentPath;
			$oldSnippetPath = $io->ExtractPathFromPath($currentPath);

			if ($snippetPath !== $oldSnippetPath && $io->FileExists($currentPath))
			{
				$io->Delete($currentPath);
				$fileName = CSnippets::GetDefaultFileName($snippetPath).'.snp';
			}
			else
			{
				$fileName = $io->ExtractNameFromPath($currentPath);
			}
		}
		$key = ($path === '' ? '' : $path.'/').$fileName;

		if (!$io->ValidatePathString($snippetPath.'/'.$fileName) ||
			IsFileUnsafe($snippetPath.'/'.$fileName) ||
			HasScriptExtension($snippetPath.'/'.$fileName))
		{
			return false;
		}

		// 1. Save new snippet with new content
		if ($code)
		{
			$APPLICATION->SaveFileContent($snippetPath.'/'.$fileName, $code);
		}

		// 2. Rewrite title & description in .content.php
		if ($title || $description)
		{
			$SNIPPETS = array();
			if ($io->FileExists($contPath."/.content.php"))
				@include($contPath."/.content.php");

			if ($title)
				$SNIPPETS[$key]['title'] = $title;
			if ($description)
				$SNIPPETS[$key]['description'] = $description;

			$contentSrc = '<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>'.chr(10);
			$contentSrc .= '<?'.chr(10).'$SNIPPETS = Array();'.chr(10);
			foreach ($SNIPPETS as $k => $snip)
			{
				if($io->FileExists(CFileMan::SecurePathVar($contPath.'/'.$k)))
				{
					$contentSrc .= '$SNIPPETS[\''.CUtil::addslashes($k).'\'] = Array(';

					if (isset($snip['title']) && $snip['title'] !== '')
					{
						$contentSrc .= '\'title\' => \''.Cutil::addslashes($snip['title']).'\'';
						if (isset($snip['description']) && $snip['description'] !== '')
							$contentSrc .= ', ';
					}
					if (isset($snip['description']) && $snip['description'] !== '')
					{
						$contentSrc .= '\'description\' => \''.Cutil::addslashes($snip['description']).'\'';
					}
					$contentSrc .= ');'.chr(10);
				}
			}
			$contentSrc .= '?>';

			$APPLICATION->SaveFileContent($contPath."/.content.php", $contentSrc);
		}
		$res = array('result' => true);

		CSnippets::ClearCache();

		return $res;
	}

	/**
	 * Removes snippet
	 *
	 * @param array - list of params (template, path)
	 * @return bool - result
	 */
	public static function Remove($params = array())
	{
		global $APPLICATION;
		$res = false;
		$template = CFileMan::SecurePathVar($params['template']);
		$path = CFileMan::SecurePathVar($params["path"]);
		$contPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
		$snippetPath = $contPath.($path == '' ? '' : '/'.$path);

		$io = CBXVirtualIo::GetInstance();

		if (!$io->ValidatePathString($snippetPath) ||
			IsFileUnsafe($snippetPath) ||
			HasScriptExtension($snippetPath))
		{
			return false;
		}

		//Delete snippet file
		if($io->FileExists($snippetPath))
		{
			$res = $io->Delete($snippetPath);
		}

		$SNIPPETS = array();
		if ($io->FileExists($contPath."/.content.php"))
			@include($contPath."/.content.php");

		$contentSrc = '<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>'.chr(10);
		$contentSrc .= '<?'.chr(10).'$SNIPPETS = Array();'.chr(10);
		foreach ($SNIPPETS as $k => $snip)
		{
			if($io->FileExists(CFileMan::SecurePathVar($contPath.'/'.$k)))
			{
				$contentSrc .= '$SNIPPETS[\''.CUtil::addslashes($k).'\'] = Array(';

				if (isset($snip['title']) && $snip['title'] !== '')
				{
					$contentSrc .= '\'title\' => \''.Cutil::addslashes($snip['title']).'\'';
					if (isset($snip['description']) && $snip['description'] !== '')
						$contentSrc .= ', ';
				}
				if (isset($snip['description']) && $snip['description'] !== '')
				{
					$contentSrc .= '\'description\' => \''.Cutil::addslashes($snip['description']).'\'';
				}
				$contentSrc .= ');'.chr(10);
			}
		}
		$contentSrc .= '?>';
		$APPLICATION->SaveFileContent($contPath."/.content.php", $contentSrc);

		CSnippets::ClearCache();

		return $res;
	}

	public static function CreateCategory($params = array())
	{
		$res = false;
		if (is_array($params) && isset($params['name']))
		{
			$name = CFileMan::SecurePathVar($params['name']);
			$template = (isset($params['template']) && $params['template'] !== '') ? CFileMan::SecurePathVar($params['template']) : '.default';
			$templatePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template;
			$basePath = $templatePath."/snippets";

			$io = CBXVirtualIo::GetInstance();
			if($io->DirectoryExists($templatePath))
			{
				if(!$io->DirectoryExists($basePath))
				{
					$io->CreateDirectory($basePath);
				}

				$parentPath = (isset($params['parent']) && $params['parent'] !== '') ? '/'.CFileMan::SecurePathVar($params['parent']) : '';
				$categoryPath = $basePath.$parentPath.'/'.$name;
				if (!$io->DirectoryExists($categoryPath))
				{
					$res = $io->CreateDirectory($categoryPath);
				}
			}
			CSnippets::ClearCache();
		}
		return $res;
	}

	public static function RenameCategory($params)
	{
		$res = false;
		if (is_array($params) && isset($params['path'], $params['new_name']))
		{
			$path = CFileMan::SecurePathVar($params['path']);
			$template = (isset($params['template']) && $params['template'] !== '') ? CFileMan::SecurePathVar($params['template']) : '.default';
			$basePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
			$categoryPath = $basePath.'/'.$path;

			$io = CBXVirtualIo::GetInstance();
			$newCategoryPath = $io->ExtractPathFromPath($categoryPath).'/'.$params['new_name'];
			if($io->DirectoryExists($categoryPath) && !$io->DirectoryExists($newCategoryPath))
			{
				$res = $io->Rename($categoryPath, $newCategoryPath);
			}

			CSnippets::ClearCache();
		}
		return $res;
	}

	public static function RemoveCategory($params)
	{
		$res = false;
		if (is_array($params) && isset($params['path']))
		{
			$path = CFileMan::SecurePathVar($params['path']);
			$template = (isset($params['template']) && $params['template'] !== '') ? CFileMan::SecurePathVar($params['template']) : '.default';
			$basePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
			$categoryPath = $basePath.'/'.$path;

			$io = CBXVirtualIo::GetInstance();
			if($io->DirectoryExists($categoryPath))
			{
				$res = $io->Delete($categoryPath);
			}

			CSnippets::ClearCache();
		}
		return $res;
	}
}
?>