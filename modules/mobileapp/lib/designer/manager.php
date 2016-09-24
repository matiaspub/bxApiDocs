<?php
namespace Bitrix\MobileApp\Designer;


use Bitrix\Main\Application;
use Bitrix\Main\Entity\FieldError;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Text\Encoding;

class Manager
{
	const IS_ALREADY_EXISTS = 3;
	const SUCCESS = 1;
	const FAIL = 0;
	const EMPTY_REQUIRED = 4;
	const APP_TEMPLATE_IS_NOT_EXISTS = 5;
	const PREVIEW_IMAGE_SIZE = 150;

	const SIMPLE_APP_TEMPLATE = "simple";

	/**
	 * Creates a new application with "global" configuration by default
	 *
	 * @param string $appCode - application code
	 * @param array $data - application data (name, short name, folder and etc)
	 * @param array $initConfig
	 *
	 * @return int
	 * @throws \Exception
	 * @see AppTable::getMap to get a bit more  information about possible keys in $data
	 */
	public static function createApp($appCode = "", $data = array(), $initConfig = array())
	{
		$result = self::SUCCESS;
		$fields = $data;
		$fields["CODE"] = $appCode;
		$dbResult = AppTable::add($fields);
		if (!$dbResult->isSuccess())
		{
			$errors = $dbResult->getErrors();
			if ($errors[0]->getCode() == FieldError::INVALID_VALUE)
			{
				$result = self::IS_ALREADY_EXISTS;
			}
			elseif ($errors[0]->getCode() == FieldError::EMPTY_REQUIRED)
			{
				$result = self::EMPTY_REQUIRED;
			}
		}
		else
		{

			self::addConfig($appCode, "global", $initConfig);
		}

		return $result;
	}

	private static function getTemplateList()
	{
		return array(
			"simple",
			"api"
		);
	}

	/**
	 * Removes application by code
	 *
	 * @param string $appCode application code
	 *
	 * @return bool
	 */
	public static function removeApp($appCode)
	{
		$result = AppTable::delete($appCode);
		return $result->isSuccess();
	}

	/**
	 * Binds file to the application
	 *
	 * @param $fileArray - file array
	 * @param $appCode - application code
	 */
	public static function registerFileInApp(&$fileArray, $appCode)
	{
		$result = AppTable::getById($appCode);
		$appData = $result->fetchAll();
		if (count($appData) > 0)
		{
			$appData[0]["FILES"][] = $fileArray["fileID"];
			AppTable::update($appCode, array("FILES" => $appData[0]["FILES"]));
			$arImage = \CFile::ResizeImageGet(
				$fileArray["fileID"],
				array("width" => self::PREVIEW_IMAGE_SIZE, "height" => self::PREVIEW_IMAGE_SIZE),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);
			$fileArray["img_source_src"] = $arImage["src"];
		}

	}

	/**
	 *  Unbinds file
	 *
	 * @param $fileId - identifier of file in b_file table
	 * @param $appCode - application code
	 */
	public static function unregisterFileInApp($fileId, $appCode)
	{
		$result = AppTable::getById($appCode);
		$appData = $result->fetchAll();
		if (count($appData) > 0)
		{

			$index = array_search($fileId, $appData[0]["FILES"]);
			if ($index !== false)
			{
				unset($appData[0]["FILES"][$index]);
				AppTable::update($appCode, array("FILES" => $appData[0]["FILES"]));
			}
			die();

		}

	}

	/**
	 * Add configuration to application
	 *
	 * @param string $appCode - application code
	 * @param $platform - platform code
	 *
	 * @see ConfigTable::getSupportedPlatforms for details on availible platforms
	 *
	 * @param array $config - configuration
	 *
	 * @return bool
	 */
	public static function addConfig($appCode = "", $platform, $config = array())
	{
		if (ConfigTable::isExists($appCode, $platform))
		{
			return false;
		}

		$fields = array(
			"APP_CODE" => $appCode,
			"PLATFORM" => $platform,
			"PARAMS" => $config
		);

		$result = ConfigTable::add($fields);

		return $result->isSuccess();

	}

	/**
	 * Removes configuration
	 *
	 * @param string $appCode - application code
	 * @param array $platform - platform code
	 *
	 * @see ConfigTable::getSupportedPlatforms for details on availible platforms
	 * @return bool
	 */
	public static function removeConfig($appCode = "", $platform = array())
	{
		$filter = array(
			"APP_CODE" => $appCode,
			"PLATFORM" => $platform,
		);

		$result = ConfigTable::delete($filter);

		return $result->isSuccess();

	}

	/**
	 * Updates configuration
	 *
	 * @param string $appCode  application code
	 * @param array $platform  platform code
	 * @param array $config  new configuration
	 *
	 * @see ConfigTable::getSupportedPlatforms
	 *
	 * @return bool
	 */
	public static function updateConfig($appCode = "", $platform = "", $config = array())
	{
		if (!ConfigTable::isExists($appCode, $platform))
		{
			return false;
		}

		$map = new ConfigMap();
		foreach ($config as $paramName => $value)
		{
			if (!$map->has($paramName))
			{
				unset($config[$paramName]);
			}
		}

		$data = array(
			"PARAMS" => $config
		);

		$result = ConfigTable::update(array("APP_CODE" => $appCode, "PLATFORM" => $platform), $data);

		return $result->isSuccess();

	}

	/**
	 * Return configuration in JSON format
	 *
	 * @param $appCode - application code
	 * @param bool $platform - platform code
	 *
	 * @see ConfigTable::getSupportedPlatforms for details on availible platforms
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getConfigJSON($appCode, $platform = false)
	{
		$map = new \Bitrix\MobileApp\Designer\ConfigMap();
		$res = ConfigTable::getList(array(
			"filter" => array(
				"APP_CODE" => $appCode,
			)
		));

		$configs = $res->fetchAll();
		$targetConfig = array();

		for ($i = 0; $i < count($configs); $i++)
		{
			if ($configs[$i]["PLATFORM"] == $platform)
			{
				$targetConfig = $configs[$i];
				break;
			}
			elseif ($configs[$i]["PLATFORM"] == "global")
			{
				$targetConfig = $configs[$i];
			}
		}

		$params = array_key_exists("PARAMS", $targetConfig) ? $targetConfig["PARAMS"]: array() ;
		$imageParamList = $map->getParamsByType(ParameterType::IMAGE);
		$imageSetParamList = $map->getParamsByType(ParameterType::IMAGE_SET);
		$structuredConfig = array();

		foreach ($params as $key => $value)
		{
			if (!$map->has($key))
			{
				continue;
			}

			if (array_key_exists($key, $imageParamList))
			{
				$imagePath = \CFile::GetPath($value);
				if(strlen($imagePath)>0)
					$value = $imagePath;
				else
					continue;
			}

			if (array_key_exists($key, $imageSetParamList))
			{
				$tmpValue = array();
				foreach ($value as $imageCode => $imageId)
				{
					$imagePath = \CFile::GetPath($imageId);
					if(strlen($imagePath)>0)
						$tmpValue[$imageCode] = $imagePath;
					else
						continue;
				}
				$value = $tmpValue;
			}
			$structuredConfig = array_merge_recursive(self::nameSpaceToArray($key, $value), $structuredConfig);
		}

		if(toUpper(SITE_CHARSET) != "UTF-8")
		{
			$structuredConfig = Encoding::convertEncodingArray($structuredConfig, SITE_CHARSET, "UTF-8");
		}

		self::addVirtualParams($structuredConfig, $platform);

		return json_encode($structuredConfig);
	}

	/**
	 * Checks if the configuration is already exists
	 *
	 * @param $folder
	 * @param $appCode - application code
	 * @param bool $useOffline
	 * @param string $templateCode
	 * @return bool
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 * @see ConfigTable::getSupportedPlatforms for details on availible platforms
	 */

	public static function copyFromTemplate($folder, $appCode, $useOffline = false, $templateCode = "simple")
	{
		if(!in_array($templateCode, self::getTemplateList()))
		{
			$templateCode = "simple";
		}

		$appFolderPath = Application::getDocumentRoot() . "/" . $folder . "/";
		$offlineTemplate = Application::getDocumentRoot() . "/bitrix/modules/mobileapp/templates_app/offline/";
		$templatePath = Application::getDocumentRoot() . "/bitrix/modules/mobileapp/templates_app/".$templateCode."/";

		$directory = new Directory($templatePath);
		if($directory->isExists())
		{
			if (!Directory::isDirectoryExists($appFolderPath))
			{
				if($useOffline)
				{
					CopyDirFiles($offlineTemplate, $appFolderPath."/offline");
				}

				$items = $directory->getChildren();
				foreach ($items as $entry)
				{
					/**
					 * @var $entry \Bitrix\Main\IO\FileSystemEntry
					 */
					$filePath = $entry->getPath();
					$appFilePath = $appFolderPath . $entry->getName();

					if($entry instanceof Directory)
					{
						CopyDirFiles($filePath, $appFolderPath."/".$entry->getName(),true,true);
					}
					else
					{
						$file = new File($entry->getPath());
						File::putFileContents(
							$appFilePath,
							str_replace(Array("#folder#", "#code#"), Array($folder, $appCode),$file->getContents())
						);
					}
				}
			}
		}
	}

	/**
	 * Binds (and creates if it's necessary) template to the application folder
	 *
	 * @param $templateId - symbolic code of the template
	 * @param $folder - the application folder
	 * @param bool $createNew - flag of the necessity of creating a new template
	 */
	public static function bindTemplate($templateId, $folder, $createNew)
	{
		$arFields = Array("TEMPLATE" => Array());
		if ($createNew)
		{
			CopyDirFiles(
				Application::getDocumentRoot() . "/bitrix/modules/mobileapp/templates/default_app/",
				Application::getDocumentRoot() . "/bitrix/templates/" . $templateId, True, True
			);

			File::putFileContents(
				Application::getDocumentRoot() . "/bitrix/templates/" . $templateId . "/description.php",
				str_replace(Array("#mobile_template_name#"), Array($templateId), File::getFileContents(Application::getDocumentRoot() . "/bitrix/templates/" . $templateId . "/description.php"))
			);

			$arFields["TEMPLATE"][] = Array(
				"SORT" => 1,
				"CONDITION" => "CSite::InDir('/" . $folder . "/')",
				"TEMPLATE" => $templateId
			);
		}

		$default_site_id = \CSite::GetDefSite();
		if ($default_site_id)
		{
			$dbTemplates = \CSite::GetTemplateList($default_site_id);
			$arFields["LID"] = $default_site_id;
			$isTemplateFound = false;
			while ($template = $dbTemplates->Fetch())
			{
				$arFields["TEMPLATE"][] = array(
					"TEMPLATE" => $template['TEMPLATE'],
					"SORT" => $template['SORT'],
					"CONDITION" => $template['CONDITION']
				);

				if ($template["TEMPLATE"] == $templateId && !$createNew && !$isTemplateFound)
				{
					$isTemplateFound = true;

					$arFields["TEMPLATE"][] = Array(
						"SORT" => 1,
						"CONDITION" => "CSite::InDir('/" . $folder . "/')",
						"TEMPLATE" => $templateId
					);
				}
			}

			$obSite = new \CSite;
			$obSite->Update($default_site_id, $arFields);
		}
	}

	/**
	 * Return files of the application
	 * @param $appCode - application code
	 * @return array
	 */
	public static function getAppFiles($appCode)
	{
		$result = AppTable::getById($appCode);
		$appData = $result->fetchAll();
		$files = array();
		if (count($appData) > 0)
		{
			//TODO fix, use module_id in the filter
			$result = \CFile::GetList(array("ID" => "desc"), Array("@ID" => implode(",", $appData[0]["FILES"])));
			while ($file = $result->Fetch())
			{
				$image = \CFile::ResizeImageGet(
					$file["ID"],
					array("width" => self::PREVIEW_IMAGE_SIZE, "height" => self::PREVIEW_IMAGE_SIZE),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				$files["file_" . $file["ID"]] = array(
					"id" => $file["ID"],
					"src" => \CFile::GetFileSRC($file),
					"preview" => $image["src"]
				);

			}
		}

		return $files;
	}

	/**
	 * Converts the namespace string to the array
	 * and assigns the given value to the key of deepest level of the namespace
	 *
	 * @param $namespace - namespace string
	 * @param $value - value
	 *
	 * <br>
	 *
	 * Here is an example:
	 * <code>
	 * $namespace = "\depth0\depth1\depth3"
	 * $value = "value1";
	 * $resultArray = \Bitrix\MobileApp\Designer\Manager\nameSpaceToArray($namespace,$value));
	 *
	 * <b>Result:</b>
	 * array(
	 *  "depth0"=>array(
	 *    "depth1"=>array(
	 *      "depth2"=>"value1"
	 *    )
	 *  )
	 *);
	 *
	 * </code>
	 *
	 * @return array
	 */
	private function nameSpaceToArray($namespace, $value)
	{
		$keys = explode("/", $namespace);
		$result = array();
		$temp = & $result;
		for ($i = 0; $i < count($keys); $i++)
		{
			$temp = & $temp[$keys[$i]];
		}

		$temp = $value;

		return $result;

	}

	private static function addVirtualParams(&$structuredConfig, $platform)
	{
		if($structuredConfig["offline"] && !empty($structuredConfig["offline"]["file_list"]))
		{
			$offlineParams = &$structuredConfig["offline"];
			$offlineParams["file_list"]["bitrix_mobile_core.js"] = Tools::getMobileJSCorePath();
			$changeMark = Tools::getArrayFilesHash($offlineParams["file_list"]);
			$offlineParams["change_mark"] = $changeMark;
		}

		if($structuredConfig["buttons"]["badge"])
		{
			$structuredConfig["buttons_badge"] = $structuredConfig["buttons"]["badge"];
			unset($structuredConfig["buttons"]["badge"]);
		}

		$structuredConfig["info"] = array(
			"designer_version"=> ConfigMap::VERSION,
			"platform"=>$platform
		);
	}


}





