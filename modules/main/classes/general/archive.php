<?php

interface IBXArchive
{
	const StatusError = 0;
	const StatusSuccess = 1;
	const StatusContinue = 2;

	static public function Pack($arFileList, $curFile = "");
	static public function Unpack($strArchivePath);
	public function GetErrors();
	static public function GetOptions();
	public function SetOptions($arOptions);
	static public function GetStartFile();
}

class CBXArchive
{
	protected function __construct(){}

	/**
	* Creates an object of the archive 
	* @static
	* @param string $strArcName full path to the archive file to be created
	* @param string $strType one of the supported archive types (TAR.GZ, ZIP etc)
	* @return archive object of a specific class, false if type is not supported or path is incorrect
	*/
	public static function GetArchive($strArcName, $strType = "")
	{
		//at first trying to detect the archive type
		if ($strType == "")
		{
			$strType = self::DetectTypeByFilename($strArcName);

			if (!$strType)
				$strType = "TAR.GZ";
		}

		$arFormats = self::GetAvailableFormats();

		foreach ($arFormats as $type => $data)
		{
			if ($strType == $type)
			{
				if (file_exists($_SERVER["DOCUMENT_ROOT"].$arFormats[$type]["path"]))
				{
					include_once($_SERVER["DOCUMENT_ROOT"].$arFormats[$type]["path"]);

					$object = new $arFormats[$type]["classname"]($strArcName);
					return $object;
				}
			}
		}

		return false;
	}

	/**
	* Finds the type of the archive by its filename
	* @static
	* @param string $filename full path to the file
	* @return mixed $type code of the type if it's supported (TAR.GZ, ZIP), false if archive type is not found for the file
	*/
	public static function DetectTypeByFilename($filename)
	{
		$arFormats = self::GetAvailableFormats();
		$filename = ToLower($filename);

		foreach ($arFormats as $type => $data)
		{
			if (in_array(GetFileExtension($filename), $data["ext"]))
				return $type;
		}
		return false;
	}

	/**
	* Contains information about archives supported by the system
	* @static
	* @return array containing archive type code, classname, extensions, classpath for each type
	*/
	public static function GetAvailableFormats()
	{
		$arFormats = array(
						"TAR.GZ"	=>	array("classname" 	=>	"CArchiver", 
													"ext"	=>	array("gz", "tgz"),
													"path"	=>	"/bitrix/modules/main/classes/general/tar_gz.php")
						,
						"ZIP"		=>	array("classname"	=>	"CZip",
													"ext"	=> 	array("zip"),
													"path"	=>	"/bitrix/modules/main/classes/general/zip.php"));

		return $arFormats;
	}

	/**
	* Returns the array of file extensions which are considered as archives
	* @static
	* @return array containing extensions in lower case
	*/
	public static function GetArchiveExtensions()
	{
		$arFormats = self::GetAvailableFormats();
		$arExt = array();

		foreach ($arFormats as $type => $data)
		{
			$arExt = array_merge($arExt, $data["ext"]);
		}
		return $arExt;
	}

	/**
	* Checks if the file is archive of the suppoted type
	* @static
	* @param string $strFilename full path to the archive to be checked
	* @return boolean
	*/
	public static function IsArchive($strFilename)
	{
		$result = false;
		$strFileExt = ToLower(GetFileExtension($strFilename));
		$arFormats = self::GetAvailableFormats();

		foreach ($arFormats as $type => $data)
		{
			if (in_array($strFileExt, $data["ext"]))
				return true;
		}
		return false;
	}

	/**
	* Checks if current user has access to the file or folder according to Bitrix permissions
	* @static
	* @param string $strFilename full path to the file
	* @param boolean $isFile true if we check file permissions, false if folder permissions should be checked
	* @return boolean
	*/
	public static function HasAccess($strFilename, $isFile)
	{
		$result = false;
		$path = removeDocRoot($strFilename);

		global $USER;

		if (!$isFile)
		{
			if ($USER->CanDoFileOperation("fm_view_listing", array(SITE_ID, $path)))
				$result = true;
		}
		else
		{
			if ($USER->CanDoFileOperation('fm_view_file', array(SITE_ID,$path)) &&
			($USER->CanDoOperation('edit_php') || $USER->CanDoFileOperation('fm_lpa', array(SITE_ID, $path)) ||
			!(HasScriptExtension($path) || substr(GetFileName($path), 0, 1) == ".")))
			{
				$result = true;
			}
		}
		return $result;
	}
}

?>