<?php

namespace Bitrix\Main\UrlPreview;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Entity\AddResult;
use Bitrix\Main\Entity\ScalarField;

class UrlMetadataTable extends Entity\DataManager
{
	const TYPE_STATIC = 'S';
	const TYPE_DYNAMIC = 'D';
	const TYPE_TEMPORARY = 'T';
	const TYPE_FILE = 'F';

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	
	/**
	* <p>Статический метод возвращает имя таблицы БД для сущности.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlmetadatatable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_urlpreview_metadata';
	}

	/**
	 * Returns entity map definition
	 *
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает описание карты сущностей.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlmetadatatable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'URL' => new Entity\StringField('URL', array(
				'required' => true,
			)),
			'TYPE' => new Entity\StringField('TYPE', array(
				'required' => true,
			)),
			'TITLE' => new Entity\StringField('TITLE'),
			'DESCRIPTION' => new Entity\TextField('DESCRIPTION'),
			'IMAGE_ID' => new Entity\IntegerField('IMAGE_ID'),
			'IMAGE' => new Entity\StringField('IMAGE'),
			'EMBED' => new Entity\TextField('EMBED'),
			'EXTRA' => new Entity\TextField('EXTRA', array(
				'serialized' => true,
			)),
			'DATE_INSERT' => new Entity\DatetimeField('DATE_INSERT', array(
				'default_value' => new Main\Type\DateTime(),
			)),
			'DATE_EXPIRE' => new Entity\DatetimeField('DATE_EXPIRE')
		);
	}

	/**
	 * Returns first record filtered by $url value
	 *
	 * @param string $url Url of the page with metadata.
	 * @return array|false
	 * @throws Main\ArgumentException
	 */
	
	/**
	* <p>Статический метод возвращает первую запись, отфильтрованную по значению переменной <code>$url</code>.</p>
	*
	*
	* @param string $url  Url страницы с матаданными.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/urlpreview/urlmetadatatable/getbyurl.php
	* @author Bitrix
	*/
	public static function getByUrl($url)
	{
		$parameters = array(
			'select' => array('*'),
			'filter' => array(
				'=URL' => $url,
			)
		);

		return static::getList($parameters)->fetch();
	}
}