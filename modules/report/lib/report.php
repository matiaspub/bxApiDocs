<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage report
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Report;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class ReportTable extends Entity\DataManager
{
	/**
	 * Returns entity map definition.
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает список полей с типами.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/reporttable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'OWNER_ID' => array(
				'data_type' => 'string'
			),
			'TITLE' => array(
				'data_type' => 'string'
			),
			'DESCRIPTION' => array(
				'data_type' => 'string'
			),
			'CREATED_DATE' => array(
				'data_type' => 'datetime',
				'default_value' => new DateTime(),
			),
			'CREATED_BY' => array(
				'data_type' => 'integer'
			),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array('=this.CREATED_BY' => 'ref.ID')
			),
			'SETTINGS' => array(
				'data_type' => 'string'
			),
			'MARK_DEFAULT' => array(
				'data_type' => 'integer'
			)
		);

		return $fieldsMap;
	}

}
