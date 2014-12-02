<?php
class CWizardUtil
{
	public static function GetRepositoryPath()
	{
		return BX_ROOT."/wizards";
	}

	public static function MakeWizardPath($wizardName)
	{
		if (!CWizardUtil::CheckName($wizardName))
			return "";

		return Rel2Abs("/", "/".str_replace(":", "/", $wizardName));
	}

	public static function CheckName($wizardName)
	{
		return (
			strlen($wizardName) > 0
			&& preg_match("#^([A-Za-z0-9_.-]+:)?([A-Za-z0-9_-]+\\.)*([A-Za-z0-9_-]+)$#i", $wizardName)
		);
	}

	public static function GetWizardList($filterNamespace = false, $bLoadFromModules = false)
	{
		$arWizards = array();
		$arLoadedWizards = array();

		$wizardPath = $_SERVER["DOCUMENT_ROOT"].CWizardUtil::GetRepositoryPath();

		if ($handle = @opendir($wizardPath))
		{
			while (($dirName = readdir($handle)) !== false)
			{
				if ($dirName == "." || $dirName == ".." || !is_dir($wizardPath."/".$dirName))
					continue;

				if (file_exists($wizardPath."/".$dirName."/.description.php"))
				{
					//Skip component without namespace
					if ($filterNamespace !== false && strlen($filterNamespace) > 0)
						continue;

					if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
					{
						if (file_exists(($fname = $wizardPath."/".$dirName."/lang/".LangSubst(LANGUAGE_ID)."/.description.php")))
							__IncludeLang($fname, false, true);
					}

					if (file_exists(($fname = $wizardPath."/".$dirName."/lang/".LANGUAGE_ID."/.description.php")))
						__IncludeLang($fname, false, true);

					$arWizardDescription = array();
					include($wizardPath."/".$dirName."/.description.php");
					$arWizards[] = array("ID" => $dirName) + $arWizardDescription;
					$arLoadedWizards[] = $dirName;
				}
				else
				{
					if ($filterNamespace !== false && (strlen($filterNamespace) <= 0 || $filterNamespace != $dirName))
							continue;

					if ($nspaceHandle = @opendir($wizardPath."/".$dirName))
					{
						while (($file = readdir($nspaceHandle)) !== false)
						{
							$pathToWizard = $wizardPath."/".$dirName."/".$file;

							if ($file == "." || $file == ".." || !is_dir($pathToWizard))
								continue;

							if (file_exists($pathToWizard."/.description.php"))
							{
								if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
								{
									if (file_exists(($fname = $pathToWizard."/lang/".LangSubst(LANGUAGE_ID)."/.description.php")))
										__IncludeLang($fname, false, true);
								}

								if (file_exists(($fname = $pathToWizard."/lang/".LANGUAGE_ID."/.description.php")))
									__IncludeLang($fname, false, true);

								$arWizardDescription = array();
								include($pathToWizard."/.description.php");
								$arWizards[] = array("ID" => $dirName.":".$file) + $arWizardDescription;
								$arLoadedWizards[] = $dirName.":".$file;
							}
						}

						@closedir($nspaceHandle);
					}
				}
			}
			@closedir($handle);
		}

		if ($bLoadFromModules)
		{
			$modulesPath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules";

			if ($handle = @opendir($modulesPath))
			{
				while (($moduleName = readdir($handle)) !== false)
				{
					if ($moduleName == "." || $moduleName == ".." || !is_dir($modulesPath."/".$moduleName))
						continue;

					if (!file_exists($modulesPath."/".$moduleName."/install/wizards"))
						continue;

					if ($handle1 = @opendir($modulesPath."/".$moduleName."/install/wizards"))
					{
						while (($dirName = readdir($handle1)) !== false)
						{
							if ($dirName == "." || $dirName == ".." || !is_dir($modulesPath."/".$moduleName."/install/wizards/".$dirName))
								continue;

							if ($filterNamespace !== false && (strlen($filterNamespace) <= 0 || $filterNamespace != $dirName))
								continue;

							if ($handle2 = @opendir($modulesPath."/".$moduleName."/install/wizards/".$dirName))
							{
								while (($file = readdir($handle2)) !== false)
								{
									$pathToWizard = $modulesPath."/".$moduleName."/install/wizards/".$dirName."/".$file;

									if ($file == "." || $file == ".." || !is_dir($pathToWizard))
										continue;

									if (in_array($dirName.":".$file, $arLoadedWizards))
										continue;

									if (file_exists($pathToWizard."/.description.php"))
									{
										if (LANGUAGE_ID != "en" && LANGUAGE_ID != "ru")
										{
											if (file_exists(($fname = $pathToWizard."/lang/".LangSubst(LANGUAGE_ID)."/.description.php")))
												__IncludeLang($fname, false, true);
										}

										if (file_exists(($fname = $pathToWizard."/lang/".LANGUAGE_ID."/.description.php")))
											__IncludeLang($fname, false, true);

										$arWizardDescription = array();
										include($pathToWizard."/.description.php");
										$arWizards[] = array("ID" => $moduleName.":".$dirName.":".$file) + $arWizardDescription;
										$arLoadedWizards[] = $dirName.":".$file;
									}
								}

								@closedir($handle2);
							}
						}
						@closedir($handle1);
					}
				}
				@closedir($handle);
			}
		}

		return $arWizards;
	}

	public static function GetNamespaceList()
	{
		$arNamespaces = array();
		$namespacePath = $_SERVER["DOCUMENT_ROOT"].CWizardUtil::GetRepositoryPath();

		if ($handle = @opendir($namespacePath))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == "..")
					continue;

				if (is_dir($namespacePath."/".$file))
				{
					if (!file_exists($namespacePath."/".$file."/.description.php"))
						$arNamespaces[] = $file;
				}
			}
			@closedir($handle);
		}

		return $arNamespaces;
	}

	public static function DeleteWizard($wizardName)
	{
		if (!CWizardUtil::CheckName($wizardName))
			return false;

		$wizardPath = CWizardUtil::GetRepositoryPath().CWizardUtil::MakeWizardPath($wizardName);
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].$wizardPath))
			return false;

		$success = DeleteDirFilesEx($wizardPath);
		return $success;
	}

	public static function CopyWizard($wizardName, $newName)
	{
		if (!CWizardUtil::CheckName($wizardName) || !CWizardUtil::CheckName($newName))
			return false;

		$wizardPath = $_SERVER["DOCUMENT_ROOT"].CWizardUtil::GetRepositoryPath().CWizardUtil::MakeWizardPath($wizardName);
		$newNamePath = $_SERVER["DOCUMENT_ROOT"].CWizardUtil::GetRepositoryPath().CWizardUtil::MakeWizardPath($newName);
		if (!file_exists($wizardPath) || file_exists($newNamePath))
			return false;

		CopyDirFiles(
			$wizardPath, 
			$newNamePath, 
			$rewrite = false, 
			$recursive = true
		);

		return true;
	}

	public static function ReplaceMacros($filePath, $arReplace, $skipSharp = false)
	{
		clearstatcache();

		if (!is_file($filePath) || !is_writable($filePath) || !is_array($arReplace))
			return;

		@chmod($filePath, BX_FILE_PERMISSIONS);

		if (!$handle = @fopen($filePath, "rb"))
			return;

		$content = @fread($handle, filesize($filePath));
		@fclose($handle);

		if (!($handle = @fopen($filePath, "wb")))
			return;

		if (flock($handle, LOCK_EX))
		{
			$arSearch = array();
			$arValue = array();

			foreach ($arReplace as $search => $replace)
			{
				if ($skipSharp)
					$arSearch[] = $search;
				else
					$arSearch[] = "#".$search."#";

				$arValue[] = $replace;
			}

			$content = str_replace($arSearch, $arValue, $content);
			@fwrite($handle, $content);
			@flock($handle, LOCK_UN);
		}
		@fclose($handle);
	}

	public static function ReplaceMacrosRecursive($filePath, $arReplace)
	{
		clearstatcache();

		if ((!is_dir($filePath) && !is_file($filePath)) || !is_array($arReplace))
			return;

		if ($handle = @opendir($filePath))
		{
			while (($file = readdir($handle)) !== false)
			{
				if ($file == "." || $file == ".." || (trim($filePath, "/") == trim($_SERVER["DOCUMENT_ROOT"], "/") && ($file == "bitrix" || $file == "upload"))) 
					continue;
					
				if (is_dir($filePath."/".$file))
				{
					self::ReplaceMacrosRecursive($filePath.$file."/", $arReplace);
				}
				elseif (is_file($filePath."/".$file))
				{
					if(GetFileExtension($file) <> "php")
						continue;

					if (!is_writable($filePath."/".$file))
						continue;

					@chmod($filePath."/".$file, BX_FILE_PERMISSIONS);

					if (!$handleFile = @fopen($filePath."/".$file, "rb"))
						continue;

					$content = @fread($handleFile, filesize($filePath."/".$file));
					@fclose($handleFile);

					if (!($handleFile = @fopen($filePath."/".$file, "wb")))
						continue;

					if (flock($handleFile, LOCK_EX))
					{
						$arSearch = array();
						$arValue = array();

						foreach ($arReplace as $search => $replace)
						{
							$arSearch[] = "#".$search."#";
							$arValue[] = $replace;
						}

						$content = str_replace($arSearch, $arValue, $content);
						@fwrite($handleFile, $content);
						@flock($handleFile, LOCK_UN);
					}
					@fclose($handleFile);

				}
			}
			@closedir($handle);
		}
	}

	public static function CopyFile($fileID, $destPath, $deleteAfterCopy = true)
	{
		$arFile = CFile::GetFileArray($fileID);
		if (!$arFile)
			return false;

		$filePath = $_SERVER["DOCUMENT_ROOT"].$arFile["SRC"];
		if (!is_file($filePath))
			return false;

		CheckDirPath($_SERVER["DOCUMENT_ROOT"].$destPath);
		if(!@copy($filePath, $_SERVER["DOCUMENT_ROOT"].$destPath))
			return false;

		if ($deleteAfterCopy)
			CFile::Delete($fileID);

		return true;
	}

	public static function GetModules()
	{
		$arModules = array();

		$arModules["main"] = array(
			"MODULE_ID" => "main",
			"MODULE_NAME" => GetMessage("MAIN_WIZARD_MAIN_MODULE_NAME"),
			"MODULE_DESCRIPTION" => GetMessage("MAIN_WIZARD_MAIN_MODULE_DESC"),
			"MODULE_VERSION" => SM_VERSION,
			"MODULE_VERSION_DATE" => SM_VERSION_DATE,
			"IsInstalled" => true,
		);

		$handle=@opendir($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules");
		if($handle)
		{
			while (false !== ($dir = readdir($handle)))
			{
				if(is_dir($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/".$dir) && $dir!="." && $dir!="..")
				{
					if($info = CModule::CreateModuleObject($dir))
					{
						$arModules[$dir]["MODULE_ID"] = $info->MODULE_ID;
						$arModules[$dir]["MODULE_NAME"] = $info->MODULE_NAME;
						$arModules[$dir]["MODULE_DESCRIPTION"] = $info->MODULE_DESCRIPTION;
						$arModules[$dir]["MODULE_VERSION"] = $info->MODULE_VERSION;
						$arModules[$dir]["MODULE_VERSION_DATE"] = $info->MODULE_VERSION_DATE;
						$arModules[$dir]["MODULE_SORT"] = $info->MODULE_SORT;
						$arModules[$dir]["IsInstalled"] = $info->IsInstalled();
					}
				}
			}
			closedir($handle);
		}

		return $arModules;
	}

	public static function CreateThumbnail($sourcePath, $previewPath, $maxWidth, $maxHeight)
	{
		if (!is_file($sourcePath))
			return false;

		$maxWidth = intval($maxWidth);
		$maxHeight = intval($maxHeight);

		if ($maxWidth <= 0 || $maxHeight <= 0)
			return false;

		list($sourceWidth, $sourceHeight, $type) = @getimagesize($sourcePath);

		//Image type
		if ($type == 1)
			$imageType = "gif";
		elseif ($type == 2)
			$imageType = "jpeg";
		elseif ($type == 3)
			$imageType = "png";
		else
			return false;

		$imageFunction = "imagecreatefrom".$imageType;
		$sourceImage = @$imageFunction($sourcePath);

		if (!$sourceImage)
			return false;

		$ratioWidth = $sourceWidth / $maxWidth;
		$ratioHeight = $sourceHeight / $maxHeight;
		$ratio = ($ratioWidth > $ratioHeight) ? $ratioWidth : $ratioHeight;

		//Biggest side
		if ($ratio > 0)
		{
			$previewWidth = $sourceWidth / $ratio;
			$previewHeight = $sourceHeight / $ratio;
		}
		else
		{
			$previewWidth = $maxWidth;
			$previewHeight = $maxHeight;
		}

		//GD library version
		$bGD2 = false;
		if (function_exists("gd_info"))
		{
			$arGDInfo = gd_info();
			$bGD2 = ((strpos($arGDInfo['GD Version'], "2.") !== false) ? true : false);
		}

		//Create Preview
		if ($bGD2)
		{
			$previewImage = imagecreatetruecolor($previewWidth, $previewHeight);
			imagecopyresampled($previewImage, $sourceImage, 0, 0, 0, 0, $previewWidth, $previewHeight, $sourceWidth, $sourceHeight);
		}
		else
		{
			$previewImage = imagecreate($previewWidth, $previewHeight);
			imagecopyresized($previewImage, $sourceImage, 0, 0, 0, 0, $previewWidth, $previewHeight, $sourceWidth, $sourceHeight);
		}

		//Save preview
		$imageFunction = "image".$imageType;

		if ($imageType == "jpeg")
			$success = @$imageFunction($previewImage, $previewPath, 95);
		else
			$success = @$imageFunction($previewImage, $previewPath);

		@imagedestroy($previewImage);
		@imagedestroy($sourceImage);

		return $success;

	}
}
