<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main\Localization;

use Bitrix\Main\Entity;

Loc::loadMessages(__FILE__);

class CultureTable extends Entity\DataManager
{
	const LEFT_TO_RIGHT = 'Y';
	const RIGHT_TO_LEFT = 'N';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_culture';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage("culture_entity_name"),
			),
			'FORMAT_DATE' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage("culture_entity_date_format"),
			),
			'FORMAT_DATETIME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage("culture_entity_datetime_format"),
			),
			'FORMAT_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage("culture_entity_name_format"),
			),
			'WEEK_START' => array(
				'data_type' => 'integer',
			),
			'CHARSET' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage("culture_entity_charset"),
			),
			'DIRECTION' => array(
				'data_type' => 'boolean',
				'values' => array(self::RIGHT_TO_LEFT, self::LEFT_TO_RIGHT),
			),
		);
	}

	public static function update($primary, array $data)
	{
		$result = parent::update($primary, $data);
		if(CACHED_b_lang !== false && $result->isSuccess())
		{
			$cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
			$cache->cleanDir("b_lang");
		}
		return $result;
	}

	public static function delete($id)
	{
		//We know for sure that languages and sites can refer to the culture.
		//Other entities should place CultureOnBeforeDelete event handler.

		$result = new Entity\DeleteResult();

		$res = LanguageTable::getList(array('filter' => array('=CULTURE_ID' => $id)));
		while(($language = $res->fetch()))
		{
			$result->addError(new Entity\EntityError(Loc::getMessage("culture_err_del_lang", array("#LID#" => $language["LID"]))));
		}

		$res = \Bitrix\Main\SiteTable::getList(array('filter' => array('=CULTURE_ID' => $id)));
		while(($site = $res->fetch()))
		{
			$result->addError(new Entity\EntityError(Loc::getMessage("culture_err_del_site", array("#LID#" => $site["LID"]))));
		}

		if(!$result->isSuccess())
		{
			return $result;
		}

		return parent::delete($id);
	}
}
