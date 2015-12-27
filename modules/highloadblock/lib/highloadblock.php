<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage highloadblock
 * @copyright  2001-2014 1C-Bitrix
 */

namespace Bitrix\Highloadblock;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class description
 * @package    bitrix
 * @subpackage highloadblock
 */
class HighloadBlockTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_hlblock_entity';
	}

	public static function getMap()
	{
		IncludeModuleLangFile(__FILE__);

		$sqlHelper = Application::getConnection()->getSqlHelper();

		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName')
			),
			'TABLE_NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateTableName')
			),
			'FIELDS_COUNT' => array(
				'data_type' => 'integer',
				'expression' => array(
					'(SELECT COUNT(ID) FROM b_user_field WHERE b_user_field.ENTITY_ID = '.$sqlHelper->getConcatFunction("'HLBLOCK_'", 'CAST(%s as char)').')', 'ID'
				)
			)
		);

		return $fieldsMap;
	}

	/**
	 * @param array $data
	 *
	 * @return Entity\AddResult
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function add($data)
	{
		$result = parent::add($data);

		if (!$result->isSuccess(true))
		{
			return $result;
		}

		// create table in db
		$dbtype = strtolower($GLOBALS['DB']->type);

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		if ($dbtype == 'mysql')
		{
			$connection->query('
				CREATE TABLE '.$sqlHelper->quote($data['TABLE_NAME']).' (ID int(11) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (ID))
			');
		}
		elseif ($dbtype == 'mssql')
		{
			$connection->query('
				CREATE TABLE '.$sqlHelper->quote($data['TABLE_NAME']).' (ID int NOT NULL IDENTITY (1, 1),
				CONSTRAINT '.$data['TABLE_NAME'].'_ibpk_1 PRIMARY KEY (ID))
			');
		}
		elseif ($dbtype == 'oracle')
		{
			$connection->query('
				CREATE TABLE '.$sqlHelper->quote($data['TABLE_NAME']).' (ID number(11) NOT NULL, PRIMARY KEY (ID))
			');

			$connection->query('
				CREATE SEQUENCE sq_'.$data['TABLE_NAME'].'
			');

			$connection->query('
				CREATE OR REPLACE TRIGGER '.$data['TABLE_NAME'].'_insert
					BEFORE INSERT
					ON '.$sqlHelper->quote($data['TABLE_NAME']).'
					FOR EACH ROW
						BEGIN
						IF :NEW.ID IS NULL THEN
							SELECT sq_'.$data['TABLE_NAME'].'.NEXTVAL INTO :NEW.ID FROM dual;
						END IF;
					END;
			');
		}
		else
		{
			throw new Main\SystemException('Unknown DB type');
		}

		return $result;
	}

	/**
	 * @param mixed $primary
	 * @param array $data
	 *
	 * @return Entity\UpdateResult
	 */
	public static function update($primary, $data)
	{
		global $USER_FIELD_MANAGER;

		// get old data
		$oldData = static::getByPrimary($primary)->fetch();

		// update row
		$result = parent::update($primary, $data);

		if (!$result->isSuccess(true))
		{
			return $result;
		}

		// rename table in db
		if ($data['TABLE_NAME'] !== $oldData['TABLE_NAME'])
		{
			$connection = Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();
			$connection->renameTable($oldData['TABLE_NAME'], $data['TABLE_NAME']);

			if ($connection instanceof MssqlConnection)
			{
				// rename constraint
				$connection->query(sprintf(
					"EXEC sp_rename %s, %s, 'OBJECT'",
					$sqlHelper->quote($oldData['TABLE_NAME'].'_ibpk_1'),
					$sqlHelper->quote($data['TABLE_NAME'].'_ibpk_1')
				));
			}

			// rename also uf multiple tables and its constraints, sequences, and triggers
			foreach ($USER_FIELD_MANAGER->getUserFields('HLBLOCK_'.$oldData['ID']) as $field)
			{
				if ($field['MULTIPLE'] == 'Y')
				{
					$oldUtmTableName = static::getMultipleValueTableName($oldData, $field);
					$newUtmTableName = static::getMultipleValueTableName($data, $field);

					$connection->renameTable($oldUtmTableName, $newUtmTableName);
				}
			}
		}

		return $result;
	}

	/**
	 * @param mixed $primary
	 *
	 * @return Main\DB\Result|Entity\DeleteResult
	 */
	public static function delete($primary)
	{
		global $USER_FIELD_MANAGER;

		// get old data
		$hlblock = static::getByPrimary($primary)->fetch();

		// get file fields
		$file_fields = array();
		$fields = $USER_FIELD_MANAGER->getUserFields('HLBLOCK_'.$hlblock['ID']);

		foreach ($fields as $name => $field)
		{
			if ($field['USER_TYPE']['BASE_TYPE'] === 'file')
			{
				$file_fields[] = $name;
			}
		}

		// delete files
		if (!empty($file_fields))
		{
			$oldEntity = static::compileEntity($hlblock);

			$query = new Entity\Query($oldEntity);

			// select file ids
			$query->setSelect($file_fields);

			// if they are not empty
			$filter = array('LOGIC' => 'OR');

			foreach ($file_fields as $file_field)
			{
				$filter['!'.$file_field] = false;
			}

			$query->setFilter($filter);

			// go
			$result = $query->exec();

			while ($row = $result->fetch())
			{
				foreach ($file_fields as $file_field)
				{
					if (!empty($row[$file_field]))
					{
						if (is_array($row[$file_field]))
						{
							foreach ($row[$file_field] as $value)
							{
								\CFile::delete($value);
							}
						}
						else
						{
							\CFile::delete($row[$file_field]);
						}
					}
				}
			}
		}

		$connection = Application::getConnection();

		foreach ($fields as $field)
		{
			// delete from uf registry
			if ($field['USER_TYPE']['BASE_TYPE'] === 'enum')
			{
				$enumField = new \CUserFieldEnum;
				$enumField->DeleteFieldEnum($field['ID']);
			}

			$connection->query("DELETE FROM b_user_field_lang WHERE USER_FIELD_ID = ".$field['ID']);
			$connection->query("DELETE FROM b_user_field WHERE ID = ".$field['ID']);

			// if multiple - drop utm table
			if ($field['MULTIPLE'] == 'Y')
			{
				$utmTableName = static::getMultipleValueTableName($hlblock, $field);
				$connection->dropTable($utmTableName);
			}
		}

		// clear uf cache
		global $CACHE_MANAGER;

		if(CACHED_b_user_field !== false)
		{
			$CACHE_MANAGER->cleanDir("b_user_field");
		}

		// remove row
		$result = parent::delete($primary);

		// drop hl table
		$connection->dropTable($hlblock['TABLE_NAME']);

		return $result;
	}

	/**
	 * @param array $hlblock
	 *
	 * @return Entity\Base
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function compileEntity($hlblock)
	{
		global $USER_FIELD_MANAGER;

		// generate entity & data manager
		$fieldsMap = array();

		// add ID
		$fieldsMap['ID'] = array(
			'data_type' => 'integer',
			'primary' => true,
			'autocomplete' => true
		);

		// build datamanager class
		$entity_name = $hlblock['NAME'];
		$entity_data_class = $hlblock['NAME'];

		if (!class_exists($entity_data_class.'Table'))
		{
			if (!preg_match('/^[a-z0-9_]+$/i', $entity_data_class))
			{
				throw new Main\SystemException(sprintf(
					'Invalid entity name `%s`.', $entity_data_class
				));
			}

			$entity_data_class .= 'Table';
			$entity_table_name = $hlblock['TABLE_NAME'];

			// make with an empty map
			$eval = '
				class '.$entity_data_class.' extends '.__NAMESPACE__.'\DataManager
				{
					public static function getTableName()
					{
						return '.var_export($entity_table_name, true).';
					}

					public static function getMap()
					{
						return '.var_export($fieldsMap, true).';
					}

					public static function getHighloadBlock()
					{
						return '.var_export($hlblock, true).';
					}
				}
			';

			eval($eval);

			// then configure and attach fields
			/** @var \Bitrix\Main\Entity\DataManager $entity_data_class */
			$entity = $entity_data_class::getEntity();

			$uFields = $USER_FIELD_MANAGER->getUserFields('HLBLOCK_'.$hlblock['ID']);

			foreach ($uFields as $uField)
			{
				if ($uField['MULTIPLE'] == 'N')
				{
					// just add single field
					$field = $USER_FIELD_MANAGER->getEntityField($uField, $uField['FIELD_NAME']);
					$entity->addField($field);

					foreach ($USER_FIELD_MANAGER->getEntityReferences($uField, $field) as $reference)
					{
						$entity->addField($reference);
					}
				}
				else
				{
					// build utm entity
					static::compileUtmEntity($entity, $uField);
				}
			}
		}

		return Entity\Base::getInstance($entity_name);
	}

	public static function OnBeforeUserTypeAdd($field)
	{
		if (preg_match('/^HLBLOCK_(\d+)$/', $field['ENTITY_ID'], $matches))
		{
			return array('PROVIDE_STORAGE' => false);
		}

		return true;
	}

	public static function onAfterUserTypeAdd($field)
	{
		global $APPLICATION, $USER_FIELD_MANAGER;

		if (preg_match('/^HLBLOCK_(\d+)$/', $field['ENTITY_ID'], $matches))
		{
			$field['USER_TYPE'] = $USER_FIELD_MANAGER->getUserType($field['USER_TYPE_ID']);

			// get entity info
			$hlblock_id = $matches[1];
			$hlblock = HighloadBlockTable::getById($hlblock_id)->fetch();

			if (empty($hlblock))
			{
				$APPLICATION->throwException(sprintf(
					'Entity "HLBLOCK_%s" wasn\'t found.', $hlblock_id
				));

				return false;
			}

			// get usertype info
			$sql_column_type = $USER_FIELD_MANAGER->getUtsDBColumnType($field);

			// create field in db
			$connection = Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$connection->query(sprintf(
				'ALTER TABLE %s ADD %s %s',
				$sqlHelper->quote($hlblock['TABLE_NAME']), $sqlHelper->quote($field['FIELD_NAME']), $sql_column_type
			));

			if ($field['MULTIPLE'] == 'Y')
			{
				// create table for this relation
				$hlentity = static::compileEntity($hlblock);
				$utmEntity = Entity\Base::getInstance(HighloadBlockTable::getUtmEntityClassName($hlentity, $field));

				$utmEntity->createDbTable();

				// add indexes
				$connection->query(sprintf(
					'CREATE INDEX %s ON %s (%s)',
					$sqlHelper->quote('IX_UTM_HL'.$hlblock['ID'].'_'.$field['ID'].'_ID'),
					$sqlHelper->quote($utmEntity->getDBTableName()),
					$sqlHelper->quote('ID')
				));

				$connection->query(sprintf(
					'CREATE INDEX %s ON %s (%s)',
					$sqlHelper->quote('IX_UTM_HL'.$hlblock['ID'].'_'.$field['ID'].'_VALUE'),
					$sqlHelper->quote($utmEntity->getDBTableName()),
					$sqlHelper->quote('VALUE')
				));
			}

			return array('PROVIDE_STORAGE' => false);
		}

		return true;
	}

	public static function OnBeforeUserTypeDelete($field)
	{
		global $APPLICATION, $USER_FIELD_MANAGER;

		if (preg_match('/^HLBLOCK_(\d+)$/', $field['ENTITY_ID'], $matches))
		{
			// get entity info
			$hlblock_id = $matches[1];
			$hlblock = HighloadBlockTable::getById($hlblock_id)->fetch();

			if (empty($hlblock))
			{
				$APPLICATION->throwException(sprintf(
					'Entity "HLBLOCK_%s" wasn\'t found.', $hlblock_id
				));

				return false;
			}

			$fieldType = $USER_FIELD_MANAGER->getUserType($field["USER_TYPE_ID"]);

			if ($fieldType['BASE_TYPE'] == 'file')
			{
				// if it was file field, then delete all files
				$entity = static::compileEntity($hlblock);

				/** @var DataManager $dataClass */
				$dataClass = $entity->getDataClass();

				$rows = $dataClass::getList(array('select' => array($field['FIELD_NAME'])));

				while ($oldData = $rows->fetch())
				{
					if (empty($oldData[$field['FIELD_NAME']]))
					{
						continue;
					}

					if(is_array($oldData[$field['FIELD_NAME']]))
					{
						foreach($oldData[$field['FIELD_NAME']] as $value)
						{
							\CFile::delete($value);
						}
					}
					else
					{
						\CFile::delete($oldData[$field['FIELD_NAME']]);
					}
				}
			}

			// drop db column
			$connection = Application::getConnection();
			$connection->dropColumn($hlblock['TABLE_NAME'], $field['FIELD_NAME']);

			// if multiple - drop utm table
			if ($field['MULTIPLE'] == 'Y')
			{
				$utmTableName = static::getMultipleValueTableName($hlblock, $field);
				$connection->dropTable($utmTableName);
			}

			return array('PROVIDE_STORAGE' => false);
		}

		return true;
	}

	protected static function compileUtmEntity(Entity\Base $hlentity, $userfield)
	{
		global $USER_FIELD_MANAGER;

		// build utm entity
		/** @var DataManager $hlDataClass */
		$hlDataClass = $hlentity->getDataClass();
		$hlblock = $hlDataClass::getHighloadBlock();

		$utmClassName = static::getUtmEntityClassName($hlentity, $userfield);
		$utmTableName = static::getMultipleValueTableName($hlblock, $userfield);

		// main fields
		$utmValueField = $USER_FIELD_MANAGER->getEntityField($userfield, 'VALUE');

		$utmEntityFields = array(
			new Entity\IntegerField('ID'),
			$utmValueField
		);

		// references
		$references = $USER_FIELD_MANAGER->getEntityReferences($userfield, $utmValueField);

		foreach ($references as $reference)
		{
			$utmEntityFields[] = $reference;
		}

		// create entity
		$utmEntity = Entity\Base::compileEntity($utmClassName, $utmEntityFields, array(
			'table_name' => $utmTableName,
			'namespace' => $hlentity->getNamespace()
		));

		// add original entity reference
		$referenceField = new Entity\ReferenceField(
			'OBJECT',
			$hlentity,
			array('=this.ID' => 'ref.ID')
		);

		$utmEntity->addField($referenceField);

		// add short alias for back-reference
		$aliasField = new Entity\ExpressionField(
			$userfield['FIELD_NAME'].'_SINGLE',
			'%s',
			$utmEntity->getFullName().':'.'OBJECT.VALUE',
			array('data_type' => get_class($utmEntity->getField('VALUE')))
		);

		$hlentity->addField($aliasField);

		// add aliases to references
		/*foreach ($references as $reference)
		{
			// todo after #44924 is resolved
			// actually no. to make it work expression should support linking to references
		}*/

		// add seriazed cache-field
		$cacheField = new Entity\TextField($userfield['FIELD_NAME']);

		Main\UserFieldTable::setMultipleFieldSerialization($cacheField, $userfield);

		$hlentity->addField($cacheField);

		return $utmEntity;
	}

	public static function getUtmEntityClassName(Entity\Base $hlentity, $userfield)
	{
		return $hlentity->getName().'Utm'.Entity\Base::snake2camel($userfield['FIELD_NAME']);
	}

	public static function getMultipleValueTableName($hlblock, $userfield)
	{
		return $hlblock['TABLE_NAME'].'_'.strtolower($userfield['FIELD_NAME']);
	}

	public static function validateName()
	{
		return array(
			new Entity\Validator\Unique,
			new Entity\Validator\Length(
				null,
				100,
				array('MAX' => GetMessage('HIGHLOADBLOCK_HIGHLOAD_BLOCK_ENTITY_NAME_FIELD_LENGTH_INVALID'))
			),
			new Entity\Validator\RegExp(
				'/^[A-Z][A-Za-z0-9]*$/',
				GetMessage('HIGHLOADBLOCK_HIGHLOAD_BLOCK_ENTITY_NAME_FIELD_REGEXP_INVALID')
			),
			new Entity\Validator\RegExp(
				'/(?<!Table)$/i',
				GetMessage('HIGHLOADBLOCK_HIGHLOAD_BLOCK_ENTITY_NAME_FIELD_TABLE_POSTFIX_INVALID')
			)
		);
	}

	public static function validateTableName()
	{
		return array(
			new Entity\Validator\Unique,
			new Entity\Validator\Length(
				null,
				64,
				array('MAX' => GetMessage('HIGHLOADBLOCK_HIGHLOAD_BLOCK_ENTITY_TABLE_NAME_FIELD_LENGTH_INVALID'))
			),
			new Entity\Validator\RegExp(
				'/^[a-z0-9_]+$/',
				GetMessage('HIGHLOADBLOCK_HIGHLOAD_BLOCK_ENTITY_TABLE_NAME_FIELD_REGEXP_INVALID')
			),
			array(__CLASS__, 'validateTableExisting')
		);
	}

	public static function validateTableExisting($value, $primary, array $row, Entity\Field $field)
	{
		$checkName = null;

		if (empty($primary))
		{
			// new row
			$checkName = $value;
		}
		else
		{
			// update row
			$oldData = static::getByPrimary($primary)->fetch();

			if ($value != $oldData['TABLE_NAME'])
			{
				// table name has been changed for existing row
				$checkName = $value;
			}
		}

		if (!empty($checkName))
		{
			if (Application::getConnection()->isTableExists($checkName))
			{
				return GetMessage('HIGHLOADBLOCK_HIGHLOAD_BLOCK_ENTITY_TABLE_NAME_ALREADY_EXISTS',
					array('#TABLE_NAME#' => $value)
				);
			}
		}

		return true;
	}
}

