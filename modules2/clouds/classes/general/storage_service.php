<?
abstract class CCloudStorageService
{
	/**
	 * @return CCloudStorageService
	*/
	static abstract public function GetObject();
	/**
	 * @return string
	*/
	static abstract public function GetID();
	/**
	 * @return string
	*/
	static abstract public function GetName();
	/**
	 * @return array[string]string
	*/
	static abstract public function GetLocationList();
	/**
	 * @param array[string]string $arBucket
	 * @param bool $bServiceSet
	 * @param string $cur_SERVICE_ID
	 * @param bool $bVarsFromForm
	 * @return string
	*/
	static abstract public function GetSettingsHTML($arBucket, $bServiceSet, $cur_SERVICE_ID, $bVarsFromForm);
	/**
	 * @param array[string]string $arBucket
	 * @param array[string]string $arSettings
	 * @return bool
	*/
	static abstract public function CheckSettings($arBucket, &$arSettings);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	static abstract public function CreateBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	static abstract public function DeleteBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	static abstract public function IsEmptyBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @return string
	*/
	static abstract public function GetFileSRC($arBucket, $arFile);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	static abstract public function FileExists($arBucket, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @param string $filePath
	 * @return bool
	*/
	static abstract public function FileCopy($arBucket, $arFile, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @param string $filePath
	 * @return bool
	*/
	static abstract public function DownloadToFile($arBucket, $arFile, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	static abstract public function DeleteFile($arBucket, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @param mixed $arFile
	 * @return bool
	*/
	static abstract public function SaveFile($arBucket, $filePath, $arFile);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @param bool $bRecursive
	 * @return array[string][int]string
	*/
	static abstract public function ListFiles($arBucket, $filePath, $bRecursive = false);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $NS
	 * @param string $filePath
	 * @param float $fileSize
	 * @param string $ContentType
	 * @return bool
	*/
	static abstract public function InitiateMultipartUpload($arBucket, &$NS, $filePath, $fileSize, $ContentType);
	/**
	 * @return float
	*/
	static abstract public function GetMinUploadPartSize();
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $NS
	 * @param string $data
	 * @return bool
	*/
	static abstract public function UploadPart($arBucket, &$NS, $data);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $NS
	 * @return bool
	*/
	static abstract public function CompleteMultipartUpload($arBucket, &$NS);
	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	*/
	static public function SetHeader($name, $value)
	{
	}
}
?>