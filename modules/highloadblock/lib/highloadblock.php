<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage highloadblock
 * @copyright  2001-2012 1C-Bitrix
 */

namespace Bitrix\Highloadblock;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB\MssqlConnection;
use Bitrix\Main\DB\OracleConnection;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class description
 * @package    bitrix
 * @subpackage highloadblock
 */
class HighloadBlockTable extends Entity\DataManager
{
	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_hlblock_entity';
	}

	public static function getMap()
	{
		IncludeModuleLangFile(__FILE__);

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
					'(SELECT COUNT(ID) FROM b_user_field WHERE b_user_field.ENTITY_ID = '.$GLOBALS['DB']->concat("'HLBLOCK_'", 'CAST(%s as char)').')', 'ID'
				)
			)
		);

		return $fieldsMap;
	}

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
			$GLOBALS['DB']->query('
				CREATE TABLE '.$sqlHelper->quote($data['TABLE_NAME']).' (ID int(11) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (ID))
			');
		}
		elseif ($dbtype == 'mssql')
		{
			$GLOBALS['DB']->query('
				CREATE TABLE '.$sqlHelper->quote($data['TABLE_NAME']).' (ID int NOT NULL IDENTITY (1, 1),
				CONSTRAINT '.$data['TABLE_NAME'].'_ibpk_1 PRIMARY KEY (ID))
			');
		}
		elseif ($dbtype == 'oracle')
		{
			$GLOBALS['DB']->query('
				CREATE TABLE '.$sqlHelper->quote($data['TABLE_NAME']).' (ID number(11) NOT NULL, PRIMARY KEY (ID))
			');

			$GLOBALS['DB']->query('
				CREATE SEQUENCE sq_'.$data['TABLE_NAME'].'
			');

			$GLOBALS['DB']->query('
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
			throw new \Exception('Unknown DB type');
		}

		return $result;
	}

	public static function update($primary, $data)
	{
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
				$connection->query("EXEC sp_rename ".$oldData['TABLE_NAME']."_ibpk_1, ".$data['TABLE_NAME']."_ibpk_1, 'OBJECT'");
			}
			elseif ($connection instanceof OracleConnection)
			{
				// rename sequence, rename trigger
				$connection->query('DROP TRIGGER '.$oldData['TABLE_NAME'].'_insert');
				$connection->query('RENAME sq_'.$oldData['TABLE_NAME'].' TO sq_'.$data['TABLE_NAME']);
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
		}

		return $result;
	}

	public static function delete($primary)
	{
		// get old data
		$oldData = static::getByPrimary($primary)->fetch();

		// get file fields
		$file_fields = array();
		$fields = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('HLBLOCK_'.$oldData['ID']);

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
			$oldEntity = static::compileEntity($oldData);

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
						\CFile::delete($row[$file_field]);
					}
				}
			}
		}

		foreach ($fields as $name => $field)
		{
			$GLOBALS['DB']->query("DELETE FROM b_user_field_lang WHERE USER_FIELD_ID = ".$field['ID'], false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$GLOBALS['DB']->query("DELETE FROM b_user_field WHERE ID = ".$field['ID'], false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		}

		// clear uf cache
		global $CACHE_MANAGER;

		if(CACHED_b_user_field !== false)
		{
			$CACHE_MANAGER->CleanDir("b_user_field");
		}

		// remove row
		$result = parent::delete($primary);

		// remove table in db
		$dbtype = strtolower($GLOBALS['DB']->type);

		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		if ($dbtype == 'oracle')
		{
			$GLOBALS['DB']->query('DROP TABLE '.$sqlHelper->quote($oldData['TABLE_NAME']).' CASCADE CONSTRAINTS');
			$GLOBALS['DB']->query('DROP SEQUENCE sq_'.$oldData['TABLE_NAME']);
		}
		else
		{
			$GLOBALS['DB']->query('DROP TABLE '.$sqlHelper->quote($oldData['TABLE_NAME']));
		}

		if (!empty($fields))
		{
			$GLOBALS['DB']->query("DROP SEQUENCE SQ_B_UTM_".'HLBLOCK_'.$oldData['ID'], true);
			$GLOBALS['DB']->query("DROP TABLE b_uts_".strtolower('HLBLOCK_'.$oldData['ID']), false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$GLOBALS['DB']->query("DROP TABLE b_utm_".strtolower('HLBLOCK_'.$oldData['ID']), false, "FILE: ".__FILE__."<br>LINE: ".__LINE__);
		}

		return $result;
	}

	public static function compileEntity($hlblock)
	{
		// generate entity & data manager
		$fieldsMap = array();

		// add ID
		$fieldsMap['ID'] = array(
			'data_type' => 'integer',
			'primary' => true,
			'autocomplete' => true
		);

		// add other fields
		$fields = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('HLBLOCK_'.$hlblock['ID']);

		foreach ($fields as $field)
		{
			$fieldsMap[$field['FIELD_NAME']] = array(
				'data_type' => \Bitrix\Main\Entity\UField::convertBaseTypeToDataType($field['USER_TYPE']['BASE_TYPE'])
			);
		}

		// build classes
		$entity_name = $hlblock['NAME'];
		$entity_data_class = $hlblock['NAME'];

		if (!class_exists($entity_data_class.'Table'))
		{
			if (!preg_match('/^[a-z0-9_]+$/i', $entity_data_class))
			{
				throw new \Exception(sprintf(
					'Invalid entity name `%s`.', $entity_data_class
				));
			}

			$entity_table_name = $hlblock['TABLE_NAME'];

			$eval = '
				class '.$entity_data_class.'Table extends '.__NAMESPACE__.'\DataManager
				{
					public static function getFilePath()
					{
						return __FILE__;
					}

					public static function getTableName()
					{
						return '.var_export($entity_table_name, true).';
					}

					public static function getMap()
					{
						return '.var_export($fieldsMap, true).';
					}
				}
			';
			eval($eval);
		}

		return \Bitrix\Main\Entity\Base::getInstance($entity_name);
	}

	public static function OnBeforeUserTypeAdd($field)
	{
		if (preg_match('/^HLBLOCK_(\d+)$/', $field['ENTITY_ID'], $matches))
		{
			// validate usertype, it should be only from bx pack for now
			$utnames = array(
				'employee', 'crm_status', 'crm', 'video', 'string', 'integer', 'double', 'datetime', 'webdav_element',
				'boolean', 'file', 'enumeration', 'iblock_section', 'iblock_element', 'string_formatted', 'vote',
				'webdav_element_history'
			);

			if (!in_array($field['USER_TYPE_ID'], $utnames, true))
			{
				$GLOBALS['APPLICATION']->throwException(sprintf(
					'Selected type "%s" hasn\'t been supported yet.', $field['USER_TYPE_ID']
				));

				return false;
			}

			if ($field['MULTIPLE'] == 'Y')
			{
				$GLOBALS['APPLICATION']->throwException('Multiple fields for highloadblock hasn\'t been supported yet.');

				return false;
			}

			// get entity info
			$hlblock_id = $matches[1];
			$hlentity = HighloadBlockTable::getById($hlblock_id)->fetch();

			if (empty($hlentity))
			{
				$GLOBALS['APPLICATION']->throwException(sprintf(
					'Entity "HLBLOCK_%s" wasn\'t found.', $hlblock_id
				));

				return false;
			}

			// get usertype info
			$uf_type_info = $GLOBALS['USER_FIELD_MANAGER']->getUserType($field['USER_TYPE_ID']);
			$uf_type_object = new $uf_type_info['CLASS_NAME'];
			$sql_column = $uf_type_object->getDBColumnType(null);

			// create field in db
			$connection = Application::getConnection();
			$sqlHelper = $connection->getSqlHelper();

			$connection->query('ALTER TABLE '.$sqlHelper->quote($hlentity['TABLE_NAME']).' ADD '.$sqlHelper->quote($field['FIELD_NAME']).' '.$sql_column);
		}

		return true;
	}

	public static function OnBeforeUserTypeDelete($field)
	{
		if (preg_match('/^HLBLOCK_(\d+)$/', $field['ENTITY_ID'], $matches))
		{
			// get entity info
			$hlblock_id = $matches[1];
			$hlentity = HighloadBlockTable::getById($hlblock_id)->fetch();

			if (empty($hlentity))
			{
				$GLOBALS['APPLICATION']->throwException(sprintf(
					'Entity "HLBLOCK_%s" wasn\'t found.', $hlblock_id
				));

				return false;
			}

			// drop db column
			$connection = Application::getConnection();
			$connection->dropColumn($hlentity['TABLE_NAME'], $field['FIELD_NAME']);
		}

		return true;
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
			)
		);
	}
}


abstract class DataManager extends Entity\DataManager
{
	public static function checkFields(Entity\Result $result, $primary, array $data)
	{
		// check data - fieldname & type & strlen etc.
		foreach ($data as $k => $v)
		{
			if (!(static::getEntity()->hasField($k) && static::getEntity()->getField($k) instanceof \Bitrix\Main\Entity\ScalarField))
			{
				throw new \Exception(sprintf(
					'Field `%s` not found in entity when trying to query %s row.',
					$k, static::getEntity()->getName()
				));
			}
		}

		// check by uf manager
		$entityName = static::getEntity()->getName();
		$hlblock = HighloadBlockTable::getList(array('select'=>array('ID'), 'filter'=>array('=NAME' => $entityName)))->fetch();

		$fields = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('HLBLOCK_'.$hlblock['ID']);

		// dear uf manager, please go fuck yourself and don't touch unchanged files
		foreach ($data as $k => $v)
		{
			// hide them from him
			$arUserField = $fields[$k];

			if ($arUserField["USER_TYPE"]["BASE_TYPE"] == 'file' && !is_array($v))
			{
				//unset($data[$k]);
			}
		}

		if (!$GLOBALS["USER_FIELD_MANAGER"]->checkFields('HLBLOCK_'.$hlblock['ID'], null, $data))
		{
			if(is_object($GLOBALS['APPLICATION']) && $GLOBALS['APPLICATION']->getException())
			{
				$e = $GLOBALS['APPLICATION']->getException();
				$result->addError(new Entity\EntityError($e->getString()));
				$GLOBALS['APPLICATION']->resetException();
			}
			else
			{
				$result->addError(new Entity\EntityError("Unknown error."));
			}
		}
	}

	public static function add(array $data)
	{
		$entityName = static::getEntity()->getName();
		$hlblock = HighloadBlockTable::getList(array('select'=>array('ID'), 'filter'=>array('=NAME' => $entityName)))->fetch();

		// add other fields
		$fields = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('HLBLOCK_'.$hlblock['ID']);

		foreach ($data as $k => $v)
		{
			$arUserField = $fields[$k];

			if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave")))
			{
				$data[$k] = call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave"), array($arUserField, $data[$k]));
			}

			if(strlen($data[$k])<=0)
			{
				$data[$k] = false;
			}
			else
			{
				// convert string datetime to DateTime object
				if ($arUserField['USER_TYPE_ID'] == 'datetime')
				{
					try
					{
						$data[$k] = Type\DateTime::createFromUserTime($v);
					}
					catch(Main\ObjectException $e)
					{
						$data[$k] = '';
					}
				}
			}
		}

		return parent::add($data);
	}

	public static function update($primary, array $data)
	{
		$entityName = static::getEntity()->getName();
		$hlblock = HighloadBlockTable::getList(array('select'=>array('ID'), 'filter'=>array('=NAME' => $entityName)))->fetch();

		// add other fields
		$fields = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('HLBLOCK_'.$hlblock['ID']);

		foreach ($data as $k => $v)
		{
			$arUserField = $fields[$k];

			if(is_callable(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave")))
			{
				if ($arUserField["USER_TYPE"]['BASE_TYPE'] == 'file' && !empty($v['old_id']) && $v['error'] === 4)
				{
					// no files changed. dear uf manager, please keep current file
					$arUserField['VALUE'] = $v;
				}

				$data[$k] = call_user_func_array(array($arUserField["USER_TYPE"]["CLASS_NAME"], "onbeforesave"), array($arUserField, $data[$k]));
			}

			if(strlen($data[$k])<=0)
			{
				$data[$k] = false;
			}
			else
			{
				// convert string datetime to DateTime object
				if ($arUserField['USER_TYPE_ID'] == 'datetime')
				{
					try
					{
						$data[$k] = Type\DateTime::createFromUserTime($v);
					}
					catch(Main\ObjectException $e)
					{
						$data[$k] = '';
					}
				}
			}
		}

		return parent::update($primary, $data);
	}

	public static function delete($primary)
	{
		// get old data
		$oldData = static::getByPrimary($primary)->fetch();

		// remove row
		$result = parent::delete($primary);

		// remove files

		$entityName = static::getEntity()->getName();
		$hlblock = HighloadBlockTable::getList(array('select'=>array('ID'), 'filter'=>array('=NAME' => $entityName)))->fetch();

		// add other fields
		$fields = $GLOBALS['USER_FIELD_MANAGER']->getUserFields('HLBLOCK_'.$hlblock['ID']);

		foreach ($oldData as $k => $v)
		{
			$arUserField = $fields[$k];

			if($arUserField["USER_TYPE"]["BASE_TYPE"]=="file")
			{
				if(is_array($oldData[$k]))
				{
					foreach($oldData[$k] as $value)
						\CFile::delete($value);
				}
				else
					\CFile::delete($oldData[$k]);
			}
		}

		return $result;
	}
}
