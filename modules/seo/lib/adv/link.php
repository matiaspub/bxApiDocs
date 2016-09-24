<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Seo\Adv;

use Bitrix\Main\Entity;

/**
 * Class LinkTable
 *
 * Fields:
 * <ul>
 * <li> LINK_TYPE string(1) mandatory
 * <li> LINK_ID int mandatory
 * <li> BANNER_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class LinkTable extends Entity\DataManager
{
	const TYPE_IBLOCK_ELEMENT = 'I';

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы привязок товаров к объявлениям в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/seo/adv/linktable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_seo_adv_link';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>	Метод возвращает список полей таблицы привязок товаров к объявлениям. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/seo/adv/linktable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'LINK_TYPE' => array(
				'data_type' => 'enum',
				'primary' => true,
				'values' => array(static::TYPE_IBLOCK_ELEMENT),
			),
			'LINK_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'BANNER_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'BANNER' => array(
				'data_type' => 'Bitrix\Seo\Adv\YandexBannerTable',
				'reference' => array('=this.BANNER_ID' => 'ref.ID'),
			),
			'IBLOCK_ELEMENT' => array(
				'data_type' => 'Bitrix\Iblock\ElementTable',
				'reference' => array('=this.LINK_ID' => 'ref.ID'),
			),
		);
	}
}