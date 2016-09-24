<?php
namespace Bitrix\MobileApp\Designer;

class ParameterType
{

	/**
	 * Group types
	 */
	const GROUP_BACKGROUND = 1;
	const GROUP_BACKGROUND_LIGHT = 2;
	const GROUP = 13;
	/**
	 * Value types
	 */
	const SIZE = 3;
	const IMAGE_SET = 4;
	const VALUE_SET = 5;
	const IMAGE = 6;
	const COLOR = 7;
	const STRING = 8;
	const BOOLEAN = 9;
	const BUTTON_STRETCH = 10;
	const FILL_LIST = 11;
	const VALUE_LIST = 12;

	public static function getDesc($type, $paramName = false)
	{
		$desc = array();
		switch ($type)
		{
			case self::GROUP:
				$desc = array("type" => self::getStringType($type));
				break;
			case self::GROUP_BACKGROUND:
				$desc = array("type" => self::getStringType($type),
					"interface" => "background",
					"primary" => "color",
				);
				break;
			case self::GROUP_BACKGROUND_LIGHT:
				$desc = array(
					"type" => self::getStringType($type),
					"interface" => "background_light",
					"primary" => "color",
				);
				break;
			case self::VALUE_LIST:
			case self::FILL_LIST:
				$desc = array(
					"type" => self::getStringType($type),
				);
				break;
			case self::COLOR:
			case self::IMAGE:
			case self::IMAGE_SET:
			case self::SIZE:
			case self::VALUE_SET:
			case self::STRING:
			case self::BOOLEAN:
				$desc = array("type" => self::getStringType($type));
				break;
		}

		return $desc;
	}

	public static function getStringType($intType)
	{
		switch ($intType)
		{
			case self::GROUP_BACKGROUND:
			case self::GROUP_BACKGROUND_LIGHT:
			case self::GROUP:
				$stringType = "group";
				break;
			case self::BUTTON_STRETCH:
			case self::VALUE_SET:
				$stringType = "value_set";
				break;
			case self::COLOR:
				$stringType = "color";
				break;
			case self::IMAGE:
				$stringType = "image";
				break;
			case self::IMAGE_SET:
				$stringType = "image_set";
				break;
			case self::SIZE:
				$stringType = "size";
				break;
			case self::FILL_LIST:
				$stringType = "fill_list";
				break;
			case self::VALUE_LIST:
				$stringType = "value_list";
				break;
			case self::BOOLEAN:
				$stringType = "boolean";
				break;
			default:
				$stringType = "string";
		}

		return $stringType;
	}


}