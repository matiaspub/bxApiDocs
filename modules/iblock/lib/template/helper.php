<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template;
/**
 * Class Helper
 * Provides some helper functions.
 * @package Bitrix\Iblock\Template
 */
class Helper
{
	/**
	 * Returns array with two elements.
	 * First with template and second with optional modifiers.
	 *
	 * @param string $template Template string. For example: "{=this.name}/lt-".
	 *
	 * @return array
	 */
	public static function splitTemplate($template)
	{
		if (preg_match("/\\/(l|t.?)+\$/", $template, $match))
		{
			return array(substr($template, 0, -strlen($match[0])), substr($match[0], 1));
		}
		else
		{
			return array($template, "");
		}
	}

	/**
	 * Returns array of modifiers if any found.
	 *
	 * @param string $modifiers Modifiers string. for example: "lt-".
	 *
	 * @return array
	 */
	public static function splitModifiers($modifiers)
	{
		if (preg_match_all("/(l|t.?)/", $modifiers, $match))
			return $match[0];
		else
			return array();
	}

	/**
	 * Returns TEMPLATE with modifiers encoded and concatenated.
	 *
	 * @param array[string]string $template Template information as it comes from DB.
	 *
	 * @return string
	 */
	public static function convertArrayToModifiers(array $template)
	{
		$TEMPLATE = $template["TEMPLATE"];
		$modifiers = "";
		if ($template["LOWER"] === "Y")
			$modifiers .= "l";
		if ($template["TRANSLIT"] === "Y")
		{
			$modifiers .= "t";
			if ($template["SPACE"] != "")
				$modifiers .= $template["SPACE"];
		}
		if ($modifiers != "")
			$modifiers = "/".$modifiers;
		return $TEMPLATE.$modifiers;
	}

	/**
	 * Returns $template with additional information.
	 * TEMPLATE field without modifiers
	 * and each modifier as distinct field.
	 *
	 * @param array[string]string $template Template information as it comes from DB.
	 *
	 * @return array[string]string
	 */
	public static function convertModifiersToArray(array $template = null)
	{
		if ($template === null)
		{
			$template = array(
				"TEMPLATE" => "",
			);
		}

		$TEMPLATE = $template["TEMPLATE"];
		$LOWER = "N";
		$TRANSLIT = "N";
		$SPACE = "";

		list($TEMPLATE, $modifiers) = self::splitTemplate($TEMPLATE);
		foreach(self::splitModifiers($modifiers) as $mod)
		{
			if ($mod == "l")
			{
				$LOWER = "Y";
			}
			else
			{
				$TRANSLIT = "Y";
				$SPACE = substr($mod, 1);
			}
		}

		$template["TEMPLATE"] = $TEMPLATE;
		$template["LOWER"] = $LOWER;
		$template["TRANSLIT"] = $TRANSLIT;
		$template["SPACE"] = $SPACE;

		return $template;
	}

	/**
	 * Function returns file name formatted by the template.
	 *
	 * @param \Bitrix\Iblock\InheritedProperty\BaseTemplate $ipropTemplates Templates to lookup.
	 * @param string $templateName Name of the template to choose from $ipropTemplates.
	 * @param array $fields Array contains fields for processing the template.
	 * @param array $file Array contains information about file in format of $_FILES.
	 *
	 * @return string
	 */
	public static function makeFileName(
		\Bitrix\Iblock\InheritedProperty\BaseTemplate $ipropTemplates,
		$templateName,
		array $fields,
		array $file
	)
	{
		if (preg_match("/^(.+)(\\.[a-zA-Z0-9]+)\$/", $file["name"], $fileName))
		{
			if (!isset($fields["IPROPERTY_TEMPLATES"]) || $fields["IPROPERTY_TEMPLATES"][$templateName] == "")
			{
				$templates = $ipropTemplates->findTemplates();
				$TEMPLATE = $templates[$templateName]["TEMPLATE"];
			}
			else
			{
				$TEMPLATE = $fields["IPROPERTY_TEMPLATES"][$templateName];
			}

			if ($TEMPLATE != "")
			{
				list($template, $modifiers) = Helper::splitTemplate($TEMPLATE);
				if ($template != "")
				{
					$values = $ipropTemplates->getValuesEntity();
					$entity = $values->createTemplateEntity();
					$entity->setFields($fields);
					return \Bitrix\Iblock\Template\Engine::process($entity, $TEMPLATE).$fileName[2];
				}
				elseif ($modifiers != "")
				{
					$simpleTemplate = new NodeRoot;
					$simpleTemplate->addChild(new NodeText($fileName[1]));
					$simpleTemplate->setModifiers($modifiers);
					$baseEntity = new Entity\Base(0);
					return $simpleTemplate->process($baseEntity).$fileName[2];
				}
			}
		}
		return $file["name"];
	}
}
