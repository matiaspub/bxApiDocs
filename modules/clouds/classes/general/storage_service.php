<?
abstract class CCloudStorageService
{
	/**
	 * @return CCloudStorageService
	*/
	abstract public function GetObject();
	/**
	 * @return string
	*/
	abstract public function GetID();
	/**
	 * @return string
	*/
	abstract public function GetName();
	/**
	 * @return array[string]string
	*/
	abstract public function GetLocationList();
	/**
	 * @param array[string]string $arBucket
	 * @param bool $bServiceSet
	 * @param string $cur_SERVICE_ID
	 * @param bool $bVarsFromForm
	 * @return string
	*/
	abstract public function GetSettingsHTML($arBucket, $bServiceSet, $cur_SERVICE_ID, $bVarsFromForm);
	/**
	 * @param array[string]string $arBucket
	 * @param array[string]string $arSettings
	 * @return bool
	*/
	abstract public function CheckSettings($arBucket, &$arSettings);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	abstract public function CreateBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	abstract public function DeleteBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @return bool
	*/
	abstract public function IsEmptyBucket($arBucket);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @return string
	*/
	abstract public function GetFileSRC($arBucket, $arFile);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function FileExists($arBucket, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function FileCopy($arBucket, $arFile, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $arFile
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function DownloadToFile($arBucket, $arFile, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @return bool
	*/
	abstract public function DeleteFile($arBucket, $filePath);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @param mixed $arFile
	 * @return bool
	*/
	abstract public function SaveFile($arBucket, $filePath, $arFile);
	/**
	 * @param array[string]string $arBucket
	 * @param string $filePath
	 * @param bool $bRecursive
	 * @return array[string][int]string
	*/
	abstract public function ListFiles($arBucket, $filePath, $bRecursive = false);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $NS
	 * @param string $filePath
	 * @param float $fileSize
	 * @param string $ContentType
	 * @return bool
	*/
	abstract public function InitiateMultipartUpload($arBucket, &$NS, $filePath, $fileSize, $ContentType);
	/**
	 * @return float
	*/
	abstract public function GetMinUploadPartSize();
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $NS
	 * @param string $data
	 * @return bool
	*/
	abstract public function UploadPart($arBucket, &$NS, $data);
	/**
	 * @param array[string]string $arBucket
	 * @param mixed $NS
	 * @return bool
	*/
	abstract public function CompleteMultipartUpload($arBucket, &$NS);
	/**
	 * @param string $name
	 * @param string $value
	 * @return void
	*/
	static public function SetHeader($name, $value)
	{
	}
	/**
	 * @param string $name
	 * @return void
	 */
	static public function UnsetHeader($name)
	{
	}
	/**
	 * @param bool $public
	 * @return void
	 */
	static public function SetPublic($public)
	{
	}
}
?>