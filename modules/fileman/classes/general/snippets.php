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

		?><script> window.arSnippets = <?= CUtil::PhpToJSObject($arSNIPPETS)?>; </script><?
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
		if (!file_exists($basePath))
			return;
		$imagesPath = $basePath."/images";

		CSnippets::WriteHtaccess($imagesPath);

		$bpPath = $basePath.($path == "" ? "" : "/").$path;
		$handle  = @opendir($bpPath);

		while(false !== ($file = @readdir($handle)))
		{
			if($file == "." || $file == ".." || $file == ".htaccess" || $file == ".content.php" || ($level == 0 && $file == "images"))
				continue;

			$fullPath = $bpPath."/".$file;
			if (is_dir($fullPath))
			{
				$new_path = "".$path.($path == "" ? "" : "/").$file;
				CSnippets::ReadDir($arSNIPPETS, $arKeys, $new_path, $template, $level + 1, $file);
			}
			elseif(is_file($fullPath))
			{
				$name = $file;
				$pos = strrpos($name, ".");
				$f_name = ($pos !== FALSE) ? substr($name, 0, $pos) : $name;
				$f_ext = ($pos !== FALSE) ? substr($name, $pos + 1) : '';

				// Rename file *.* => *.snp
				if ($f_ext != 'snp')
				{
					$name = $f_name.".snp";

					if (!file_exists($bpPath."/".$name))
					{
						rename($fullPath, $bpPath."/".$name);
					}
					else
					{
						for ($n = 1; $n < 256; $n++)
						{
							$test_f_name = $f_name."(".$n.")";
							$name = $test_f_name.".snp";
							if (!file_exists($bpPath."/".$name))
							{
								rename($fullPath, $bpPath."/".$name);
								break;
							}
						}
					}
					$f_ext = 'snp';
				}
				$imgPath = $imagesPath."/".$path;
				//Check thumbnail
				if(file_exists($imgPath."/".$f_name.".gif"))
					$thumb = $f_name.".gif";
				elseif(file_exists($imgPath."/".$f_name.".jpg"))
					$thumb = $f_name.".jpg";
				elseif(file_exists($imgPath."/".$f_name.".jpeg"))
					$thumb = $f_name.".jpeg";
				elseif(file_exists($imgPath."/".$f_name.".png"))
					$thumb = $f_name.".png";
				elseif(file_exists($imgPath."/".$f_name.".bmp"))
					$thumb = $f_name.".bmp";
				else
					$thumb = "";

				$key = $path.($path != '' ? '/' : '').$name;
				$arSNIPPETS[$key] = Array(
					'name' => $name,
					'path' => $path,
					'title' => $name,
					'thumb' => $thumb,
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
		if(file_exists($path) && !file_exists($path."/.htaccess"))
			$GLOBALS['APPLICATION']->SaveFileContent($path."/.htaccess", "Allow from All");
	}

	public static function ClearCache()
	{
		global $CACHE_MANAGER;
		$CACHE_MANAGER->Clean("fileman_snippet_array");
	}

	public static function GetCode($path)
	{
		return file_exists($path) ? $GLOBALS['APPLICATION']->GetFileContent($path) : '';
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
			CFileman::DeleteFile(Array($Params["site"], $snPath.'/images/'.$path.($path == '' ?  '' : '/').CFileMan::SecurePathVar($Params["thumb"])));

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

	public static function CheckFile($Params)
	{
		$contPath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$Params['template']."/snippets";
		return file_exists(CFileMan::SecurePathVar($contPath.'/'.$Params['path']));
	}

	public static function GetGroups($Params)
	{
		$template = CFileMan::SecurePathVar($Params['template']);
		$arSnGroups = Array();
		CSnippets::InspectDir($arSnGroups, "", $template);
		CSnippets::DisplayJSGroups($template, $arSnGroups);
	}

	public static function InspectDir(&$arSnGroups, $path, $template, $level = 0, $parent = '')
	{
		$basePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/templates/".$template."/snippets";
		if (!file_exists($basePath))
			return;

		$bpPath = $basePath.($path == "" ? "" : "/").$path;
		$handle  = @opendir($bpPath);
		if (!$level)
			$level = 0;

		if (!$parent)
			$parent = "";

		while(false !== ($file = @readdir($handle)))
		{
			if($file == "." || $file == ".." || $file == ".htaccess" || $file == ".content.php" || ($level == 0 && $file == "images"))
				continue;

			$fullPath = $bpPath."/".$file;
			if (!is_dir($fullPath))
				continue;

			$arSnGroups[] = Array
			(
				'path' => $path,
				'name' => $file,
				'level' => $level,
				'default_name' => CSnippets::GetDefaultFileName($fullPath)
			);

			$new_path = "".$path.($path == "" ? "" : "/").$file;
			CSnippets::InspectDir($arSnGroups, $new_path, $template, $level + 1,$parent);
		}
	}

	public static function GetDefaultFileName($path)
	{
		for ($i=1; $i <= 9999; $i++)
		{
			$name = 'snippet'.str_pad($i, 4, "0", STR_PAD_LEFT);
			if (!file_exists($path.'/'.$name.'.snp'))
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
}
?>