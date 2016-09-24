<?php
namespace Bitrix\Main;

/**
 * Class FileTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> MODULE_ID string(50) optional
 * <li> HEIGHT int optional
 * <li> WIDTH int optional
 * <li> FILE_SIZE int optional
 * <li> CONTENT_TYPE string(255) optional default 'IMAGE'
 * <li> SUBDIR string(255) optional
 * <li> FILE_NAME string(255) mandatory
 * <li> ORIGINAL_NAME string(255) optional
 * <li> DESCRIPTION string(255) optional
 * <li> HANDLER_ID string(50) optional
 * <li> EXTERNAL_ID string(50) optional
 * </ul>
 *
 * @package Bitrix\File
 **/
class FileTable extends Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_file';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => new Entity\IntegerField('ID', array(
				'primary' => true,
				'autocomplete' => true,
			)),
			'TIMESTAMP_X' => new Entity\DatetimeField('TIMESTAMP_X', array(
				'default_value' => new Type\DateTime
			)),
			'MODULE_ID' => new Entity\StringField('MODULE_ID', array(
				'validation' => array(__CLASS__, 'validateModuleId'),
			)),
			'HEIGHT' => new Entity\IntegerField('HEIGHT'),
			'WIDTH' => new Entity\IntegerField('WIDTH'),
			'FILE_SIZE' => new Entity\IntegerField('FILE_SIZE'),
			'CONTENT_TYPE' => new Entity\StringField('CONTENT_TYPE', array(
				'validation' => array(__CLASS__, 'validateContentType'),
			)),
			'SUBDIR' => new Entity\StringField('SUBDIR', array(
				'validation' => array(__CLASS__, 'validateSubdir'),
			)),
			'FILE_NAME' => new Entity\StringField('FILE_NAME', array(
				'validation' => array(__CLASS__, 'validateFileName'),
				'required' => true,
			)),
			'ORIGINAL_NAME' => new Entity\StringField('ORIGINAL_NAME', array(
				'validation' => array(__CLASS__, 'validateOriginalName'),
			)),
			'DESCRIPTION' => new Entity\StringField('DESCRIPTION', array(
				'validation' => array(__CLASS__, 'validateDescription'),
			)),
			'HANDLER_ID' => new Entity\StringField('HANDLER_ID', array(
				'validation' => array(__CLASS__, 'validateHandlerId'),
			)),
			'EXTERNAL_ID' => new Entity\StringField('EXTERNAL_ID', array(
				'validation' => array(__CLASS__, 'validateExternalId'),
			)),
		);
	}

	/**
	 * Returns validators for MODULE_ID field.
	 *
	 * @return array
	 */
	public static function validateModuleId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for CONTENT_TYPE field.
	 *
	 * @return array
	 */
	public static function validateContentType()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for SUBDIR field.
	 *
	 * @return array
	 */
	public static function validateSubdir()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for FILE_NAME field.
	 *
	 * @return array
	 */
	public static function validateFileName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for ORIGINAL_NAME field.
	 *
	 * @return array
	 */
	public static function validateOriginalName()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for DESCRIPTION field.
	 *
	 * @return array
	 */
	public static function validateDescription()
	{
		return array(
			new Entity\Validator\Length(null, 255),
		);
	}

	/**
	 * Returns validators for HANDLER_ID field.
	 *
	 * @return array
	 */
	public static function validateHandlerId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Returns validators for EXTERNAL_ID field.
	 *
	 * @return array
	 */
	public static function validateExternalId()
	{
		return array(
			new Entity\Validator\Length(null, 50),
		);
	}

	/**
	 * Adds row to entity table
	 *
	 * @param array $data
	 *
	 * @return Entity\AddResult Contains ID of inserted row
	 *
	 * @throws \Exception
	 */
	public static function add(array $data)
	{
		throw new NotImplementedException("Use CFile class.");
	}

	/**
	 * Updates row in entity table by primary key
	 *
	 * @param mixed $primary
	 * @param array $data
	 *
	 * @return Entity\UpdateResult
	 *
	 * @throws \Exception
	 */
	public static function update($primary, array $data)
	{
		throw new NotImplementedException("Use CFile class.");
	}

	/**
	 * Deletes row in entity table by primary key
	 *
	 * @param mixed $primary
	 *
	 * @return Entity\DeleteResult
	 *
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		throw new NotImplementedException("Use CFile class.");
	}
}