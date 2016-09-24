<?php
namespace Bitrix\Iblock;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class PropertyTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> IBLOCK_ID int mandatory
 * <li> NAME string(255) mandatory
 * <li> ACTIVE bool optional default 'Y'
 * <li> SORT int optional default 500
 * <li> CODE string(50) optional
 * <li> DEFAULT_VALUE text optional
 * <li> PROPERTY_TYPE enum ('S', 'N', 'L', 'F', 'E' or 'G') optional default 'S'
 * <li> ROW_COUNT int optional default 1
 * <li> COL_COUNT int optional default 30
 * <li> LIST_TYPE enum ('C' or 'L') optional default 'L'
 * <li> MULTIPLE bool optional default 'N'
 * <li> XML_ID string(100) optional
 * <li> FILE_TYPE string(200) optional
 * <li> MULTIPLE_CNT int optional
 * <li> TMP_ID string(40) optional
 * <li> LINK_IBLOCK_ID int optional
 * <li> WITH_DESCRIPTION bool optional default 'N'
 * <li> SEARCHABLE bool optional default 'N'
 * <li> FILTRABLE bool optional default 'N'
 * <li> IS_REQUIRED bool optional default 'N'
 * <li> VERSION enum (1 or 2) optional default 1
 * <li> USER_TYPE string(255) optional
 * <li> USER_TYPE_SETTINGS string optional
 * <li> HINT string(255) optional
 * <li> LINK_IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * </ul>
 *
 * @package Bitrix\Iblock
 **/

class PropertyTable extends Main\Entity\DataManager
{
	const CHECKBOX = 'C';
	const LISTBOX = 'L';

	const TYPE_STRING = 'S';
	const TYPE_NUMBER = 'N';
	const TYPE_FILE = 'F';
	const TYPE_ELEMENT = 'E';
	const TYPE_SECTION = 'G';
	const TYPE_LIST = 'L';

	const DEFAULT_MULTIPLE_CNT = 5;

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название таблицы свойств инфоблоков в базе данных. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/gettablename.php
	* @author Bitrix
	*/
	public static function getTableName()
	{
		return 'b_iblock_property';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает список полей для таблицы свойств инфоблоков. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/getmap.php
	* @author Bitrix
	*/
	public static function getMap()
	{
		return array(
			'ID' => new Main\Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_ID_FIELD'),
			)),
			'TIMESTAMP_X' => new Main\Entity\DatetimeField('TIMESTAMP_X', array(
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_TIMESTAMP_X_FIELD'),
			)),
			'IBLOCK_ID' => new Main\Entity\IntegerField('IBLOCK_ID', array(
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_IBLOCK_ID_FIELD'),
			)),
			'NAME' => new Main\Entity\StringField('NAME', array(
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_NAME_FIELD'),
			)),
			'ACTIVE' => new Main\Entity\BooleanField('ACTIVE', array(
				'values' => array('N','Y'),
				'default_value' => 'Y',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_ACTIVE_FIELD'),
			)),
			'SORT' => new Main\Entity\IntegerField('SORT', array(
				'default_value' => 500,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_SORT_FIELD'),
			)),
			'CODE' => new Main\Entity\StringField('CODE', array(
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_CODE_FIELD'),
			)),
			'DEFAULT_VALUE' => new Main\Entity\TextField('DEFAULT_VALUE', array(
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_DEFAULT_VALUE_FIELD'),
			)),
			'PROPERTY_TYPE' => new Main\Entity\EnumField('PROPERTY_TYPE', array(
				'values' => array(
					self::TYPE_STRING,
					self::TYPE_NUMBER,
					self::TYPE_FILE,
					self::TYPE_ELEMENT,
					self::TYPE_SECTION,
					self::TYPE_LIST
				),
				'default_value' => self::TYPE_STRING,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_PROPERTY_TYPE_FIELD'),
			)),
			'ROW_COUNT' => new Main\Entity\IntegerField('ROW_COUNT', array(
				'default_value' => 1,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_ROW_COUNT_FIELD'),
			)),
			'COL_COUNT' => new Main\Entity\IntegerField('COL_COUNT', array(
				'default_value' => 30,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_COL_COUNT_FIELD'),
			)),
			'LIST_TYPE' => new Main\Entity\EnumField('LIST_TYPE', array(
				'values' => array(self::LISTBOX, self::CHECKBOX),
				'default_value' => self::LISTBOX,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_LIST_TYPE_FIELD'),
			)),
			'MULTIPLE' => new Main\Entity\BooleanField('MULTIPLE', array(
				'values' => array('N','Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_MULTIPLE_FIELD'),
			)),
			'XML_ID' => new Main\Entity\StringField('XML_ID', array(
				'validation' => array(__CLASS__, 'validateXmlId'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_XML_ID_FIELD'),
			)),
			'FILE_TYPE' => new Main\Entity\StringField('FILE_TYPE', array(
				'validation' => array(__CLASS__, 'validateFileType'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_FILE_TYPE_FIELD'),
			)),
			'MULTIPLE_CNT' => new Main\Entity\IntegerField('MULTIPLE_CNT', array(
				'default_value' => self::DEFAULT_MULTIPLE_CNT,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_MULTIPLE_CNT_FIELD'),
			)),
			'TMP_ID' => new Main\Entity\StringField('TMP_ID', array(
				'validation' => array(__CLASS__, 'validateTmpId'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_TMP_ID_FIELD'),
			)),
			'LINK_IBLOCK_ID' => new Main\Entity\IntegerField('LINK_IBLOCK_ID', array(
				'default_value' => 0,
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_LINK_IBLOCK_ID_FIELD'),
			)),
			'WITH_DESCRIPTION' => new Main\Entity\BooleanField('WITH_DESCRIPTION', array(
				'values' => array('N','Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_WITH_DESCRIPTION_FIELD'),
			)),
			'SEARCHABLE' => new Main\Entity\BooleanField('SEARCHABLE', array(
				'values' => array('N','Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_SEARCHABLE_FIELD'),
			)),
			'FILTRABLE' => new Main\Entity\BooleanField('FILTRABLE', array(
				'values' => array('N','Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_FILTRABLE_FIELD'),
			)),
			'IS_REQUIRED' => new Main\Entity\BooleanField('IS_REQUIRED', array(
				'values' => array('N','Y'),
				'default_value' => 'N',
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_IS_REQUIRED_FIELD'),
			)),
			'VERSION' => new Main\Entity\EnumField('VERSION', array(
				'values' => array(1, 2),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_VERSION_FIELD'),
			)),
			'USER_TYPE' => new Main\Entity\StringField('USER_TYPE', array(
				'validation' => array(__CLASS__, 'validateUserType'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_USER_TYPE_FIELD'),
			)),
			'USER_TYPE_SETTINGS' => new Main\Entity\TextField('USER_TYPE_SETTINGS', array(
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_USER_TYPE_SETTINGS_FIELD'),
			)),
			'HINT' => new Main\Entity\StringField('HINT', array(
				'validation' => array(__CLASS__, 'validateHint'),
				'title' => Loc::getMessage('IBLOCK_PROPERTY_ENTITY_HINT_FIELD'),
			)),
			'LINK_IBLOCK' => new Main\Entity\ReferenceField(
				'LINK_IBLOCK',
				'Bitrix\Iblock\Iblock',
				array('=this.LINK_IBLOCK_ID' => 'ref.ID')
			),
			'IBLOCK' => new Main\Entity\ReferenceField(
				'IBLOCK',
				'Bitrix\Iblock\Iblock',
				array('=this.IBLOCK_ID' => 'ref.ID')
			),
		);
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>NAME</code> (название свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validatename.php
	* @author Bitrix
	*/
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>CODE</code> (символьный код свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validatecode.php
	* @author Bitrix
	*/
	public static function validateCode()
	{
		return array(
			new Main\Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for XML_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>XML_ID</code> (внешний код свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validatexmlid.php
	* @author Bitrix
	*/
	public static function validateXmlId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100),
		);
	}

	/**
	 * Returns validators for FILE_TYPE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>FILE_TYPE</code> (список допустимых расширений для свойств <b>Файл</b>). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validatefiletype.php
	* @author Bitrix
	*/
	public static function validateFileType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 200),
		);
	}

	/**
	 * Returns validators for TMP_ID field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>TMP_ID</code> (временный символьный идентификатор, используемый для служебных целей). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validatetmpid.php
	* @author Bitrix
	*/
	public static function validateTmpId()
	{
		return array(
			new Main\Entity\Validator\Length(null, 40),
		);
	}

	/**
	 * Returns validators for USER_TYPE field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>USER_TYPE</code> (идентификатор пользовательского типа свойства). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validateusertype.php
	* @author Bitrix
	*/
	public static function validateUserType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for HINT field.
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает валидатор для поля <code>HINT</code> (подсказка). Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/propertytable/validatehint.php
	* @author Bitrix
	*/
	public static function validateHint()
	{
		return array(
			new Main\Entity\Validator\Length(null, 255),
		);
	}
}