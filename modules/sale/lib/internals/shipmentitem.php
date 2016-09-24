<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale\Internals;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class OrderDeliveryBasketTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_DELIVERY_ID int mandatory
 * <li> BASKET_ID int mandatory
 * <li> QUANTITY unknown mandatory
 * <li> RESERVED_QUANTITY unknown mandatory
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class ShipmentItemTable extends Main\Entity\DataManager
{

	/**
	 * Returns path to the file which contains definition of the class.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает путь к файлу, содержащему определение класса. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmentitemtable/getfilepath.php
	* @author Bitrix
	*/
	public static function getFilePath()
	{
		return __FILE__;
	}


	/**
	 * @param $id
	 * @return Main\Entity\DeleteResult
	 * @throws Main\ArgumentException
	 */
	public static function deleteWithItems($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new Main\ArgumentNullException("id");

		$itemsFromDbList = ShipmentItemStoreTable::getList(
			array(
				"filter" => array(
					'ORDER_DELIVERY_BASKET_ID' => $id,
				),
				"select" => array("ID")
			)
		);
		while ($itemsFromDbItem = $itemsFromDbList->fetch())
			ShipmentItemStoreTable::delete($itemsFromDbItem['ID']);

		return ShipmentItemTable::delete($id);
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы базы данных, в которой хранятся составы отгрузок. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmentitemtable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_sale_order_dlv_basket';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы, в которой хранятся составы отгрузок. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmentitemtable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		global $DB;

		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_ID_FIELD'),
			),
			'ORDER_DELIVERY_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_ORDER_DELIVERY_ID_FIELD'),
			),
			'DELIVERY' => array(
				'data_type' => 'Shipment',
				'reference' => array(
					'=this.ORDER_DELIVERY_ID' => 'ref.ID'
				)
			),
			'BASKET_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_BASKET_ID_FIELD'),
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime'
			),
			'DATE_INSERT_SHORT' => array(
				'data_type' => 'datetime',
				'expression' => array(
					$DB->datetimeToDateFunction('%s'), 'DATE_INSERT'
				)
			),
			'QUANTITY' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_QUANTITY_FIELD'),
			),
			'RESERVED_QUANTITY' => array(
				'data_type' => 'float',
				'required' => true,
				'title' => Loc::getMessage('ORDER_DELIVERY_BASKET_ENTITY_RESERVED_QUANTITY_FIELD'),
			),
		);
	}
}