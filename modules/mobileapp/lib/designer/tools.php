<?php
namespace Bitrix\MobileApp\Designer;


use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;

class Tools
{
	private static $jsMobileCorePath = "/bitrix/cache/js/mobileapp_designer/mobile_core.js";

	public static function getMobileJSCorePath()
	{
		self::generateMobileJSFile();
		return self::$jsMobileCorePath;
	}

	private static function generateMobileJSFile()
	{
		$lastModificationHash = Option::get("mobileapp","mobile_core_modification","");
		$coreMobileFileList = array(
			"/bitrix/js/main/core/core.js",
			"/bitrix/js/main/core/core_ajax.js",
			"/bitrix/js/main/core/core_db.js",
			"/bitrix/js/mobileapp/bitrix_mobile.js",
			"/bitrix/js/mobileapp/mobile_lib.js"
		);

		$modificationHash = self::getArrayFilesHash($coreMobileFileList);

		$coreFile = new File(Application::getDocumentRoot().self::$jsMobileCorePath);

		if($modificationHash == $lastModificationHash && $coreFile->isExists())
			return;

		CheckDirPath(Application::getDocumentRoot()."/bitrix/cache/js/mobileapp_designer/");

		$content = "";
		foreach ($coreMobileFileList as $filePath)
		{
			$file = new \Bitrix\Main\IO\File(Application::getDocumentRoot().$filePath);
			if($file->isExists())
			{
				$fileContent = $file->getContents();
				$content.="\n\n".$fileContent;

			}
		}


		$coreFile->open("w+");
		$coreFile->putContents($content);
		$coreFile->close();

		Option::set("mobileapp","mobile_core_modification", $modificationHash);

	}

	public static function getArrayFilesHash($fileList = array())
	{
		$fileModificationString = "";
		foreach ($fileList as $item)
		{
			$file = new File(Application::getDocumentRoot().$item);
			$fileModificationString .= $item."|";
			if($file->isExists())
			{
				$file->getModificationTime();
				$fileModificationString .= "|".$file->getModificationTime();
			}	
		}

		return md5($fileModificationString);
	}


}