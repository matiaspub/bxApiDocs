<?php
namespace Bitrix\Catalog;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class GroupLangTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> CATALOG_GROUP_ID int mandatory
 * <li> LANG string(2) mandatory
 * <li> NAME string(100) optional
 * <li> CATALOG_GROUP reference to {@link \Bitrix\Catalog\CatalogGroupTable}
 * </ul>
 *
 * @package Bitrix\Catalog
 **/

class GroupLangTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы языкозависимых параметров типов цен. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouplangtable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_catalog_group_lang';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы языкозависимых параметров типов цен. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouplangtable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_ID_FIELD')
			)),
			'CATALOG_GROUP_ID' => new Main\Entity\IntegerField('CATALOG_GROUP_ID', array(
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_CATALOG_GROUP_ID_FIELD')
			)),
			'LANG' => new Main\Entity\StringField('LANG', array(
				'validation' => array(__CLASS__, 'validateLang'),
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_LANG_FIELD')
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('GROUP_LANG_ENTITY_NAME_FIELD')
			)),
			'CATALOG_GROUP' => new Main\Entity\ReferenceField(
				'CATALOG_GROUP',
				'Bitrix\Catalog\Group',
				array('=this.CATALOG_GROUP_ID' => 'ref.ID')
			)
		);
	}
	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>LID</code> (код языка интерфейса). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouplangtable/validatelang.php
	* @author Bitrix
	*/
	public static function validateLang()
	{
		return array(
			new Main\Entity\Validator\Length(2, 2),
		);
	}
	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (название типа цен в зависимости от языка интерфейса). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/catalog/grouplangtable/validatename.php
	* @author Bitrix
	*/
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100),
		);
	}
}