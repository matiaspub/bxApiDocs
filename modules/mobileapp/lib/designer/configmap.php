<?php
/**
 * Created by PhpStorm.
 * User: carter
 * Date: 27.04.14
 * Time: 12:28
 */

namespace Bitrix\MobileApp\Designer;

use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class ConfigMap
{
	const VERSION = 1.0;
	private static $configMap;
	private static $configDescription;

	/**
	 * @return array|mixed
	 * @throws \Bitrix\Main\SystemException
	 */
	public function __construct()
	{
		$this->createMap();
	}

	private  function createMap()
	{
		$mapFilePath = Application::getDocumentRoot() . "/bitrix/modules/mobileapp/maps/config.php";
		$file = new File($mapFilePath);
		if (!$file->isExists())
		{
			throw new SystemException("The map file  '" . $mapFilePath . "' doesn't exists!", 100);
		}

		$map = include($mapFilePath);

		if (!is_array($map))
		{
			throw new SystemException("The map file does exist but has some broken structure.", 101);
		}

		self::$configMap = $map;
		self::$configMap["groups"] = array();
		$groupTypes = array(ParameterType::GROUP, ParameterType::GROUP_BACKGROUND, ParameterType::GROUP_BACKGROUND_LIGHT);

		foreach($map["types"] as $paramName=>$intType)
		{
			if(in_array($intType,$groupTypes))
			{
				self::$configMap["groups"][] = $paramName;
			}

		}
	}

	static public function getMap()
	{

		if (!self::$configMap)
		{
			self::createMap();
		}
		return self::$configMap;
	}

	/**
	 * Gets full description of the all parameters recursively
	 * @return array
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getDescriptionConfig()
	{
		if(self::$configDescription)
		{
			return self::$configDescription;
		}

		$map = $this->getMap();

		$mapTypes = $map["types"];
		self::$configDescription = array();
		foreach ($mapTypes as $name => $type)
		{
			self::$configDescription[$name] = $this->getParamDescription($name, $type);
		}

		return self::$configDescription;
	}

	public function getParamDescription($name, $type)
	{
		$desc = ParameterType::getDesc($type);
		if ($type == ParameterType::VALUE_LIST)
		{
			$desc["list"] = $this->getValueList($name);
		}

		if (!self::isGroup($name))
		{
			$desc["parent"] = $this->getGroupByParam($name);
		}

		$desc["limits"] = $this->getLimits($name);;

		return $desc;
	}

	public function getParamsByGroups()
	{
		$map = $this->getDescriptionConfig();
		$groups = array();
		foreach ($map as $key=>$desc)
		{
			$path = explode("/", $key);
			$groups[$path[0]][$key] = $desc;
		}

		return $groups;
	}

	/**
	 * Gets parameters by passed type
	 * @param $paramType
	 * @return array
	 */
	public function getParamsByType($paramType)
	{
		if (!$paramType)
		{
			return false;
		}

		$stringType = ParameterType::getStringType($paramType);
		$description = $this->getDescriptionConfig();
		$paramsByType = array();
		foreach ($description as $name => $desc)
		{
			if ($stringType == $desc["type"])
			{
				$paramsByType[$name] = $desc;
			}
		}

		return $paramsByType;
	}

	/**
	 * Gets all parameters with type "image"
	 * @return array
	 */
	public function getImageParams()
	{
		return $this->getParamsByType(ParameterType::IMAGE);
	}

	/**
	 * Gets all parameters with group types
	 * @return mixed
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getGroupParams()
	{
		$map = $this->getMap();
		return $map["groups"];
	}

	/**
	 * Checks if the parameter with passed $paramName is group
	 * @param $paramName
     * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isGroup($paramName)
	{
		$map = $this->getMap();
		$types = $map["types"];
		return (array_key_exists($paramName, $types)
			&& ParameterType::getStringType($types[$paramName]) == "group");
	}

	/**
	 * Returns group of the parameter by its name
	 * @param $paramName
	 * @return string
	 */
	public function getGroupByParam($paramName)
	{
		$groups = $this->getGroupParams();

		if(is_array($groups))
		{
			foreach ($groups as $group)
			{
				if (strpos($paramName, $group) === 0)
				{
					return $group;
				}
			}
		}

		return "";
	}

	/**
	 * Gets lang messages
	 * @return array
	 */
	static public function getLangMessages()
	{
		return Loc::loadLanguageFile(Path::normalize(__FILE__));
	}

	/**
	 * Checks if parameters with passed name is exists in the map
	 * @param $paramName
	 * @return bool
	 */
	static public function has($paramName)
	{
		return array_key_exists($paramName, self::getDescriptionConfig());
	}

	/**
	 * Gets
	 * @param $paramName
	 * @return mixed
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getValueList($paramName)
	{
		$map = $this->getMap();
		return $map["listValues"][$paramName];
	}

	private function getLimits($paramName)
	{
		$map = $this->getMap();
		$limits = $map["limits"][$paramName];
		if(!is_array($limits))
			$limits = array();
		return $limits;
	}

}