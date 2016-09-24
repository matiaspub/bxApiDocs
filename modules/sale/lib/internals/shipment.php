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
 * Class ShipmentTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ORDER_ID int mandatory
 * <li> STATUS_ID string(1) mandatory
 * <li> PRICE_DELIVERY unknown mandatory
 * <li> ALLOW_DELIVERY string(1) optional
 * <li> DATE_ALLOW_DELIVERY datetime optional
 * <li> EMP_ALLOW_DELIVERY int optional
 * <li> DEDUCTED string(1) optional
 * <li> DATE_DEDUCTED datetime optional
 * <li> EMP_DEDUCTED_ID int optional
 * <li> REASON_UNDO_DEDUCTED string(255) optional
 * <li> RESERVED string(1) optional
 * <li> DELIVERY_ID int mandatory
 * <li> DELIVERY_DOC_NUM string(20) optional
 * <li> DELIVERY_DOC_DATE datetime optional
 * <li> TRACKING_NUMBER string(255) optional
 * <li> XML_ID string(255) optional
 * <li> PARAMS string mandatory
 * <li> DELIVERY_NAME string(128) mandatory
 * <li> CANCELED string(1) optional
 * <li> DATE_CANCELED datetime optional
 * <li> EMP_CANCELED_ID int optional
 * <li> REASON_CANCELED string(255) optional
 * <li> MARKED string(1) optional
 * <li> DATE_MARKED datetime optional
 * <li> EMP_MARKED_ID int optional
 * <li> REASON_MARKED string(255) optional
 * </ul>
 *
 * @package Bitrix\Sale
 **/

class ShipmentTable extends Main\Entity\DataManager
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/getfilepath.php
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
	 * @throws Main\ArgumentNullException
	 */
	public static function deleteWithItems($id)
	{
		$id = intval($id);
		if ($id <= 0)
			throw new Main\ArgumentNullException("id");

		$itemsList = ShipmentItemTable::getList(
			array(
				"filter" => array("ORDER_DELIVERY_ID" => $id),
				"select" => array("ID")
			)
		);
		while ($item = $itemsList->fetch())
			ShipmentItemTable::deleteWithItems($item["ID"]);

		return ShipmentTable::delete($id);
	}

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы документов отгрузок в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_sale_order_delivery';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы документов отгрузок. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/getmap.php
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
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_ID_FIELD'),
			),
			'ORDER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_ORDER_ID_FIELD'),
			),
			new Main\Entity\StringField(
					'ACCOUNT_NUMBER',
					array(
							'size' => 100
					)
			),
			'ORDER' => array(
				'data_type' => 'Order',
				'reference' => array(
					'=ref.ID' => 'this.ORDER_ID'
				)
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime'
			),

			new Main\Entity\ExpressionField(
				'DATE_INSERT_SHORT',
				$DB->datetimeToDateFunction('%s'),
				array('DATE_INSERT')
			),

			new Main\Entity\StringField(
				'STATUS_ID',
				array('size' => 2)
			),

			new Main\Entity\StringField(
				'DELIVERY_LOCATION',
				array('size' => 50)
			),

			new Main\Entity\FloatField(
				'BASE_PRICE_DELIVERY'
			),

			new Main\Entity\FloatField(
				'PRICE_DELIVERY'
			),

			new Main\Entity\BooleanField(
				'CUSTOM_PRICE_DELIVERY',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),


			new Main\Entity\StringField(
				'CURRENCY',
				array(
					'size' => 3
				)
			),

			new Main\Entity\FloatField(
				'DISCOUNT_PRICE'
			),

			new Main\Entity\BooleanField(
				'ALLOW_DELIVERY',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),

			new Main\Entity\DatetimeField('DATE_ALLOW_DELIVERY'),

			new Main\Entity\ExpressionField(
				'DATE_ALLOW_DELIVERY_SHORT',
				$DB->datetimeToDateFunction('%s'),
				array('DATE_ALLOW_DELIVERY')
			),

			'EMP_ALLOW_DELIVERY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_EMP_ALLOW_DELIVERY_FIELD'),
			),
			'EMP_ALLOW_DELIVERY_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_ALLOW_DELIVERY_ID' => 'ref.ID'
				)
			),

			new Main\Entity\BooleanField(
				'DEDUCTED',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),

			'DATE_DEDUCTED' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_DATE_DEDUCTED_FIELD'),
			),
			new Main\Entity\ExpressionField(
				'DATE_DEDUCTED_SHORT',
				$DB->datetimeToDateFunction('%s'),
				array('DATE_DEDUCTED')
			),
			'EMP_DEDUCTED_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_EMP_DEDUCTED_ID_FIELD'),
			),
			'EMP_DEDUCTED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_DEDUCTED_ID' => 'ref.ID'
				)
			),
			'REASON_UNDO_DEDUCTED' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateReasonUndoDeducted'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_REASON_UNDO_DEDUCTED_FIELD'),
			),

			new Main\Entity\BooleanField(
				'RESERVED',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),

			new Main\Entity\IntegerField(
				'DELIVERY_ID',
                 array(
                     'required' => true,
	                 'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_DELIVERY_ID_FIELD'),
                 )
			),

			'DELIVERY' => array(
				'data_type' => '\Bitrix\Sale\Delivery\Services\Table',
				'reference' => array(
					'=this.DELIVERY_ID' => 'ref.ID'
				)
			),
			'DELIVERY_DOC_NUM' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateDeliveryDocNum'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_DELIVERY_DOC_NUM_FIELD'),
			),

			new Main\Entity\DatetimeField('DELIVERY_DOC_DATE'),

			new Main\Entity\ExpressionField(
				'DELIVERY_DOC_DATE_SHORT',
				$DB->datetimeToDateFunction('%s'),
				array('DELIVERY_DOC_DATE')
			),

			'TRACKING_NUMBER' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTrackingNumber'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_TRACKING_NUMBER_FIELD'),
			),
			'TRACKING_STATUS' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_TRACKING_STATUS_FIELD'),
			),
			'TRACKING_DESCRIPTION' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateTrackingDescription'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_TRACKING_DESCRIPTION_FIELD'),
			),
			'TRACKING_LAST_CHECK' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_TRACKING_LAST_CHECK_FIELD'),
			),
			'TRACKING_LAST_CHANGE' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_TRACKING_LAST_CHANGE_FIELD'),
			),

			'XML_ID' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_XML_ID_FIELD'),
			),
			'PARAMS' => array(
				'data_type' => 'text',
				'serialized' => true,
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_PARAMETERS_FIELD'),
			),
			'DELIVERY_NAME' => array(
				'data_type' => 'string',
//				'required' => true,
				'validation' => array(__CLASS__, 'validateDeliveryName'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_DELIVERY_NAME_FIELD'),
			),

			new Main\Entity\BooleanField(
				'CANCELED',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),
			'DATE_CANCELED' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_DATE_CANCELED_FIELD'),
			),
			'EMP_CANCELED_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_EMP_CANCELED_ID_FIELD'),
			),
			'REASON_CANCELED' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateReasonCanceled'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_REASON_CANCELED_FIELD'),
			),
			'EMP_CANCELED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_CANCELED_ID' => 'ref.ID'
				)
			),
			new Main\Entity\BooleanField(
				'MARKED',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),
			'DATE_MARKED' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_DATE_MARKED_FIELD'),
			),
			'EMP_MARKED_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_EMP_MARKED_ID_FIELD'),
			),
			'EMP_MARKED_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_MARKED_ID' => 'ref.ID'
				)
			),
			'REASON_MARKED' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateReasonMarked'),
				'title' => Loc::getMessage('ORDER_SHIPMENT_ENTITY_REASON_MARKED_FIELD'),
			),

			new Main\Entity\BooleanField(
				'SYSTEM',
				array(
					'values' => array('N','Y'),
					'default_value' => 'N'
				)
			),

			'RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_RESPONSIBLE_ID_FIELD')
			),
			'RESPONSIBLE_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.RESPONSIBLE_ID' => 'ref.ID'
				)
			),
			'EMP_RESPONSIBLE_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_EMP_RESPONSIBLE_ID_FIELD')
			),
			'EMP_RESPONSIBLE_BY' => array(
				'data_type' => 'Bitrix\Main\User',
				'reference' => array(
					'=this.EMP_RESPONSIBLE_ID' => 'ref.ID'
				)
			),
			'DATE_RESPONSIBLE_ID' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_DATE_RESPONSIBLE_ID_FIELD')
			),
			'COMMENTS' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_COMMENTS_FIELD')
			),
			'COMPANY_ID' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('ORDER_PAYMENT_ENTITY_COMPANY_ID_FIELD')
			),
			'COMPANY_BY' => array(
				'data_type' => 'Bitrix\Sale\Internals\Company',
				'reference' => array(
					'=this.COMPANY_ID' => 'ref.ID'
				)
			),
			'STATUS' => array(
				'data_type' => 'Bitrix\Sale\Internals\StatusTable',
				'reference' => array(
					'=this.STATUS_ID' => 'ref.ID'
				)
			),
			'SHIPMENT_ITEM' => array(
					'data_type' => 'ShipmentItem',
					'reference' => array(
							'this.ID' => 'ref.ORDER_DELIVERY_ID',
					)
			),
			new Main\Entity\BooleanField(
				'UPDATED_1C',
				array(
					'values' => array('N', 'Y')
				)
			),

			new Main\Entity\StringField('ID_1C'),

			new Main\Entity\StringField('VERSION_1C'),

			new Main\Entity\BooleanField(
				'EXTERNAL_DELIVERY',
				array(
					'values' => array('N', 'Y')
				)
			),
		);
	}
	/**
	 * Returns validators for ALLOW_DELIVERY field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>ALLOW_DELIVERY</code> (флаг разрешения доставки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validateallowdelivery.php
	* @author Bitrix
	*/
	public static function validateAllowDelivery()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for DEDUCTED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>DEDUCTED</code> (флаг отгрузки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatededucted.php
	* @author Bitrix
	*/
	public static function validateDeducted()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for REASON_UNDO_DEDUCTED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>REASON_UNDO_DEDUCTED</code> (причина снятия флага отгрузки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatereasonundodeducted.php
	* @author Bitrix
	*/
	public static function validateReasonUndoDeducted()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for RESERVED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>RESERVED</code> (товары зарезервированы). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatereserved.php
	* @author Bitrix
	*/
	public static function validateReserved()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for DELIVERY_DOC_NUM field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>DELIVERY_DOC_NUM</code> (номер документа отгрузки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatedeliverydocnum.php
	* @author Bitrix
	*/
	public static function validateDeliveryDocNum()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}
	/**
	 * Returns validators for TRACKING_NUMBER field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>TRACKING_NUMBER</code> (номер для отслеживания). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatetrackingnumber.php
	* @author Bitrix
	*/
	public static function validateTrackingNumber()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>XML_ID</code> (внешний код). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatexmlid.php
	* @author Bitrix
	*/
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for DELIVERY_NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>DELIVERY_NAME</code> (название службы доставки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatedeliveryname.php
	* @author Bitrix
	*/
	public static function validateDeliveryName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for CANCELED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>CANCELED</code> (флаг отмены). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatecanceled.php
	* @author Bitrix
	*/
	public static function validateCanceled()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for REASON_CANCELED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>REASON_CANCELED</code> (причина отмены отгрузки). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatereasoncanceled.php
	* @author Bitrix
	*/
	public static function validateReasonCanceled()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for MARKED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>MARKED</code> (флаг проблемы с отгрузкой). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatemarked.php
	* @author Bitrix
	*/
	public static function validateMarked()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for REASON_MARKED field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>REASON_MARKED</code> (причина проблемы с отгрузкой). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatereasonmarked.php
	* @author Bitrix
	*/
	public static function validateReasonMarked()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
	/**
	 * Returns validators for SYSTEM field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>SYSTEM</code> (флаг, определяющий отгрузка системная (т.е. внутренняя) или обычная). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatesystem.php
	* @author Bitrix
	*/
	public static function validateSystem()
	{
		return array(
			new Main\Entity\Validator\Length(null, 1),
		);
	}
	/**
	 * Returns validators for TRACKING_DESCRIPTION field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>TRACKING_DESCRIPTION</code> (описание отслеживания). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/internals/shipmenttable/validatetrackingdescription.php
	* @author Bitrix
	*/
	public static function validateTrackingDescription()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

}