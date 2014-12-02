<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage highloadblock
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Highloadblock;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

abstract class DataManager extends Entity\DataManager
{
	/**
	 * Being redefined in HL classes
	 * @return null
	 */
	public static function getHighloadBlock()
	{
		return null;
	}

	public static function checkFields(Entity\Result $result, $primary, array $data)
	{
		// check for unknown fields
		foreach ($data as $k => $v)
		{
			if (!(static::getEntity()->hasField($k) && static::getEntity()->getField($k) instanceof Entity\ScalarField))
			{
				throw new Main\SystemException(sprintf(
					'Field `%s` not found in entity when trying to query %s row.',
					$k, static::getEntity()->getName()
				));
			}
		}
	}

	/**
	 * @param array $data
	 *
	 * @return Entity\AddResult
	 */
	public static function add(array $data)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\AddResult;
		$hlblock = static::getHighloadBlock();
		$entity = static::getEntity();

		//event before adding
		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_ADD, array("fields"=>$data));
		$event->send();
		$event->getErrors($result);
		$data = $event->mergeFields($data);

		//event before adding (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_ADD, array("fields"=>$data), true);
		$event->send();
		$event->getErrors($result);
		$data = $event->mergeFields($data);

		// check data by uf manager
		if (!$USER_FIELD_MANAGER->checkFields('HLBLOCK_'.$hlblock['ID'], null, $data))
		{
			if(is_object($APPLICATION) && $APPLICATION->getException())
			{
				$e = $APPLICATION->getException();
				$result->addError(new Entity\EntityError($e->getString()));
				$APPLICATION->resetException();
			}
			else
			{
				$result->addError(new Entity\EntityError("Unknown error while checking userfields"));
			}
		}

		// return if any error
		if (!$result->isSuccess(true))
		{
			return $result;
		}

		//event on adding
		$event = new Entity\Event($entity, self::EVENT_ON_ADD, array("fields"=>$data));
		$event->send();

		//event on adding (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_ADD, array("fields"=>$data), true);
		$event->send();

		// format data before save
		$fields = $USER_FIELD_MANAGER->getUserFields('HLBLOCK_'.$hlblock['ID']);
		list($data, $multiValues) = static::convertValuesBeforeSave($data, $fields);

		// use save modifiers
		foreach ($data as $fieldName => $value)
		{
			$field = static::getEntity()->getField($fieldName);
			$data[$fieldName] = $field->modifyValueBeforeSave($value, $data);
		}

		// save data
		$connection = Main\Application::getConnection();

		$tableName = $entity->getDBTableName();
		$identity = $entity->getAutoIncrement();

		$id = $connection->add($tableName, $data, $identity);

		$result->setId($id);
		$result->setData($data);

		// save multi values
		if (!empty($multiValues))
		{
			foreach ($multiValues as $userfieldName => $values)
			{
				$utmTableName = HighloadBlockTable::getMultipleValueTableName($hlblock, $fields[$userfieldName]);

				foreach ($values as $value)
				{
					$connection->add($utmTableName, array('ID' => $id, 'VALUE' => $value));
				}
			}
		}

		// build stamdard primary
		$primary = null;

		if (!empty($id))
		{
			$primary = $id;
			static::normalizePrimary($primary);
		}
		else
		{
			static::normalizePrimary($primary, $data);
		}

		//event after adding
		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_ADD, array("id"=>$id, "fields"=>$data));
		$event->send();

		//event after adding (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_ADD, array("id"=>$id, "primary"=>$primary, "fields"=>$data), true);
		$event->send();

		return $result;
	}

	/**
	 * @param mixed $primary
	 * @param array $data
	 *
	 * @return Entity\UpdateResult
	 */
	public static function update($primary, array $data)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\UpdateResult();

		static::normalizePrimary($primary, $data);
		static::validatePrimary($primary);

		$oldData = static::getByPrimary($primary)->fetch();
		$hlblock = static::getHighloadBlock();
		$entity = static::getEntity();

		//event before update
		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_UPDATE, array("id"=>$primary, "fields"=>$data));
		$event->send();
		$event->getErrors($result);
		$data = $event->mergeFields($data);

		//event before update (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_UPDATE, array(
			"id"=>$primary, "primary"=>$primary, "fields"=>$data, "oldFields" => $oldData
		), true);
		$event->send();
		$event->getErrors($result);
		$data = $event->mergeFields($data);

		// check data by uf manager CheckFieldsWithOldData
		if (!$USER_FIELD_MANAGER->checkFieldsWithOldData('HLBLOCK_'.$hlblock['ID'], $oldData, $data))
		{
			if(is_object($APPLICATION) && $APPLICATION->getException())
			{
				$e = $APPLICATION->getException();
				$result->addError(new Entity\EntityError($e->getString()));
				$APPLICATION->resetException();
			}
			else
			{
				$result->addError(new Entity\EntityError("Unknown error while checking userfields"));
			}
		}

		// return if any error
		if (!$result->isSuccess(true))
		{
			return $result;
		}

		//event on update
		$event = new Entity\Event($entity, self::EVENT_ON_UPDATE, array("id"=>$primary, "fields"=>$data));
		$event->send();

		//event on update (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_UPDATE, array(
			"id"=>$primary, "primary"=>$primary, "fields"=>$data, "oldFields" => $oldData
		), true);
		$event->send();

		// format data before save
		$fields = $USER_FIELD_MANAGER->getUserFieldsWithReadyData('HLBLOCK_'.$hlblock['ID'], $oldData, LANGUAGE_ID, false, 'ID');
		list($data, $multiValues) = static::convertValuesBeforeSave($data, $fields);

		// use save modifiers
		foreach ($data as $fieldName => $value)
		{
			$field = static::getEntity()->getField($fieldName);
			$data[$fieldName] = $field->modifyValueBeforeSave($value, $data);
		}

		// save data
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$tableName = $entity->getDBTableName();

		$update = $helper->prepareUpdate($tableName, $data);

		$id = array();
		foreach ($primary as $k => $v)
		{
			$id[] = $helper->prepareAssignment($tableName, $k, $v);
		}
		$where = implode(' AND ', $id);

		$sql = "UPDATE ".$tableName." SET ".$update[0]." WHERE ".$where;
		$connection->queryExecute($sql, $update[1]);

		$result->setAffectedRowsCount($connection);
		$result->setData($data);
		$result->setPrimary($primary);

		// save multi values
		if (!empty($multiValues))
		{
			foreach ($multiValues as $userfieldName => $values)
			{
				$utmTableName = HighloadBlockTable::getMultipleValueTableName($hlblock, $fields[$userfieldName]);

				// first, delete old values
				$connection->query(sprintf(
					'DELETE FROM %s WHERE %s = %d',
					$helper->quote($utmTableName), $helper->quote('ID'), $primary['ID']
				));

				// insert new values
				foreach ($values as $value)
				{
					$connection->add($utmTableName, array('ID' => $primary['ID'], 'VALUE' => $value));
				}
			}
		}

		//event after update
		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_UPDATE, array("id"=>$primary, "fields"=>$data));
		$event->send();

		//event after update (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_UPDATE, array(
			"id"=>$primary, "primary"=>$primary, "fields"=>$data, "oldFields" => $oldData
		), true);
		$event->send();

		return $result;
	}

	/**
	 * @param mixed $primary
	 *
	 * @return Entity\DeleteResult
	 */
	public static function delete($primary)
	{
		global $USER_FIELD_MANAGER;

		// check primary
		static::normalizePrimary($primary);
		static::validatePrimary($primary);

		// get old data
		$oldData = static::getByPrimary($primary)->fetch();

		$hlblock = static::getHighloadBlock();
		$entity = static::getEntity();
		$result = new Entity\DeleteResult();

		//event before delete
		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_DELETE, array("id"=>$primary));
		$event->send();
		$event->getErrors($result);

		//event before delete (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_BEFORE_DELETE, array(
			"id"=>$primary, "primary"=>$primary, "oldFields" => $oldData
		), true);
		$event->send();
		$event->getErrors($result);

		// return if any error
		if (!$result->isSuccess(true))
		{
			return $result;
		}

		//event on delete
		$event = new Entity\Event($entity, self::EVENT_ON_DELETE, array("id"=>$primary));
		$event->send();

		//event on delete (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_DELETE, array(
			"id"=>$primary, "primary"=>$primary, "oldFields" => $oldData
		), true);
		$event->send();

		// remove row
		$connection = Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$tableName = $entity->getDBTableName();

		$id = array();
		foreach ($primary as $k => $v)
		{
			$id[] = $k." = '".$helper->forSql($v)."'";
		}
		$where = implode(' AND ', $id);

		$sql = "DELETE FROM ".$tableName." WHERE ".$where;
		$connection->queryExecute($sql);

		$fields = $USER_FIELD_MANAGER->getUserFields('HLBLOCK_'.$hlblock['ID']);

		foreach ($oldData as $k => $v)
		{
			$userfield = $fields[$k];

			// remove multi values
			if ($userfield['MULTIPLE'] == 'Y')
			{
				$utmTableName = HighloadBlockTable::getMultipleValueTableName($hlblock, $userfield);

				$connection->query(sprintf(
					'DELETE FROM %s WHERE %s = %d',
					$helper->quote($utmTableName), $helper->quote('ID'), $primary['ID']
				));
			}

			// remove files
			if ($userfield["USER_TYPE"]["BASE_TYPE"]=="file")
			{
				if(is_array($oldData[$k]))
				{
					foreach($oldData[$k] as $value)
					{
						\CFile::delete($value);
					}
				}
				else
				{
					\CFile::delete($oldData[$k]);
				}
			}
		}

		//event after delete
		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_DELETE, array("id"=>$primary));
		$event->send();

		//event after delete (modern with namespace)
		$event = new Entity\Event($entity, self::EVENT_ON_AFTER_DELETE, array(
			"id"=>$primary, "primary" => $primary, "oldFields" => $oldData
		), true);
		$event->send();

		return $result;
	}

	protected function convertValuesBeforeSave($data, $userfields)
	{
		$multiValues = array();

		foreach ($data as $k => $v)
		{
			if ($k == 'ID')
			{
				continue;
			}

			$userfield = $userfields[$k];

			if ($userfield['MULTIPLE'] == 'N')
			{
				$inputValue = array($v);
			}
			else
			{
				$inputValue = $v;
			}

			$tmpValue = array();

			foreach ($inputValue as $singleValue)
			{
				$tmpValue[] = static::convertSingleValueBeforeSave($singleValue, $userfield);
			}

			// write value back
			if ($userfield['MULTIPLE'] == 'N')
			{
				$data[$k] = $tmpValue[0];
			}
			else
			{
				// remove empty (false) values
				$tmpValue = array_filter($tmpValue, 'strlen');

				$data[$k] = $tmpValue;
				$multiValues[$k] = $tmpValue;
			}
		}

		return array($data, $multiValues);
	}

	protected function convertSingleValueBeforeSave($value, $userfield)
	{
		if(is_callable(array($userfield["USER_TYPE"]["CLASS_NAME"], "onbeforesave")))
		{
			$value = call_user_func_array(
				array($userfield["USER_TYPE"]["CLASS_NAME"], "onbeforesave"), array($userfield, $value)
			);
		}

		if(strlen($value)<=0)
		{
			$value = false;
		}

		return $value;
	}
}
