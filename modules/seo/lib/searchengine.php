<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\Entity;

class SearchEngineTable extends Entity\DataManager
{
	const INACTIVE = 'N';
	const ACTIVE = 'Y';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_seo_search_engine';
	}

	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array(self::INACTIVE, self::ACTIVE)
			),
			'SORT' => array(
				'data_type' => 'integer',
			),
			'NAME' => array(
				'data_type' => 'string',
			),
			'CLIENT_ID' => array(
				'data_type' => 'string',
			),
			'CLIENT_SECRET' => array(
				'data_type' => 'string',
			),
			'REDIRECT_URI' => array(
				'data_type' => 'string',
			),
			'SETTINGS' => array(
				'data_type' => 'text',
			),
		);

		return $fieldsMap;
	}

	public static function getByCode($code)
	{
		return SearchEngineTable::getList(array(
			'filter' => array('CODE' => $code),
		));
	}
}
