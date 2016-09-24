<?php
namespace Bitrix\Report\Internals;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SharingTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 * @return string
	 */
	
	/**
	* <p>Статический метод возвращает имя таблицы базы данных, соответствующей данной сущности.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/internals/sharingtable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_report_sharing';
	}

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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/report/internals/sharingtable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'REPORT_ID' => array(
				'data_type' => 'integer',
				'required' => true
			),
			'LINK_REPORT' => array(
				'data_type' => 'Bitrix\Report\ReportTable',
				'reference' => array(
					'=this.REPORT_ID' => 'ref.ID'
				),
			),
			'ENTITY' => array(
				'data_type' => 'string',
				'required' => true
			),
			'RIGHTS' => array(
				'data_type' => 'string',
				'required' => true
			)
		);

		return $fieldsMap;
	}

}
