<?php
namespace Bitrix\Catalog;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class GroupTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> NAME string(100) mandatory
 * <li> BASE bool optional default 'N'
 * <li> SORT int optional default 100
 * <li> XML_ID string(255) optional
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * <li> LANG reference to {@link \Bitrix\Catalog\GroupLang}
 * <li> CURRENT_LANG reference to {@link \Bitrix\Catalog\GroupLang} with current lang (LANGUAGE_ID)
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class GroupTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы типов цен в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouptable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_catalog_group';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы типов цен. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouptable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('GROUP_ENTITY_ID_FIELD'),
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('GROUP_ENTITY_NAME_FIELD'),
			)),
			'BASE' => new Main\Entity\BooleanField('BASE', array(
				'values' => array('N', 'Y'),
				'title' => Loc::getMessage('GROUP_ENTITY_BASE_FIELD'),
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'title' => Loc::getMessage('GROUP_ENTITY_SORT_FIELD'),
			)),
			'XML_ID' => new Main\Entity\StringField('XML_ID', array(
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('GROUP_ENTITY_XML_ID_FIELD'),
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'title' => Loc::getMessage('GROUP_ENTITY_TIMESTAMP_X_FIELD'),
				'default_value' => new Main\Type\DateTime()
			)),
			'MODIFIED_BY' => new Main\Entity\IntegerField('MODIFIED_BY', array(
				'title' => Loc::getMessage('GROUP_ENTITY_MODIFIED_BY_FIELD'),
			)),
			'DATE_CREATE' => new Main\Entity\DatetimeField('DATE_CREATE', array(
				'title' => Loc::getMessage('GROUP_ENTITY_DATE_CREATE_FIELD'),
				'default_value' => new Main\Type\DateTime()
			)),
			'CREATED_BY' => new Main\Entity\IntegerField('CREATED_BY', array(
				'title' => Loc::getMessage('GROUP_ENTITY_CREATED_BY_FIELD'),
			)),
			'CREATED_BY_USER' => new Main\Entity\ReferenceField(
				'CREATED_BY_USER',
				'Bitrix\Main\User',
				array('=this.CREATED_BY' => 'ref.ID')
			),
			'MODIFIED_BY_USER' => new Main\Entity\ReferenceField(
				'MODIFIED_BY_USER',
				'Bitrix\Main\User',
				array('=this.MODIFIED_BY' => 'ref.ID')
			),
			'LANG' => new Main\Entity\ReferenceField(
				'LANG',
				'Bitrix\Catalog\GroupLang',
				array('=this.ID' => 'ref.CATALOG_GROUP_ID')
			),
			'CURRENT_LANG' => new Main\Entity\ReferenceField(
				'CURRENT_LANG',
				'Bitrix\Catalog\GroupLang',
				array(
					'=this.ID' => 'ref.CATALOG_GROUP_ID',
					'=ref.LANG' => new Main\DB\SqlExpression('?', LANGUAGE_ID)
				),
				array('join_type' => 'LEFT')
			)
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (внутреннее название типа цены). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouptable/validatename.php
	* @author Bitrix
	*/
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100),
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouptable/validatexmlid.php
	* @author Bitrix
	*/
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}