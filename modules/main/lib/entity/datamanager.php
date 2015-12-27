<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Main\Entity;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Base entity data manager
 */
abstract class DataManager
{
	const EVENT_ON_BEFORE_ADD = "OnBeforeAdd";
	const EVENT_ON_ADD = "OnAdd";
	const EVENT_ON_AFTER_ADD = "OnAfterAdd";
	const EVENT_ON_BEFORE_UPDATE = "OnBeforeUpdate";
	const EVENT_ON_UPDATE = "OnUpdate";
	const EVENT_ON_AFTER_UPDATE = "OnAfterUpdate";
	const EVENT_ON_BEFORE_DELETE = "OnBeforeDelete";
	const EVENT_ON_DELETE = "OnDelete";
	const EVENT_ON_AFTER_DELETE = "OnAfterDelete";

	/** @var Base[] */
	protected static $entity;

	/**
	 * Returns entity object
	 *
	 * @return Base
	 */
	public static function getEntity()
	{
		$class = get_called_class();

		if (!isset(static::$entity[$class]))
		{
			static::$entity[$class] = Base::getInstance($class);
		}

		return static::$entity[$class];
	}

	/**
	 * Returns DB table name for entity
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return null;
	}

	/**
	 * Returns connection name for entity
	 *
	 * @return string
	 */
	public static function getConnectionName()
	{
		return 'default';
	}

	/**
	 * Returns entity map definition
	 */
	public static function getMap()
	{
		return array();
	}

	public static function getUfId()
	{
		return null;
	}

	public static function isUts()
	{
		return false;
	}

	public static function isUtm()
	{
		return false;
	}

	/**
	 * Returns selection by entity's primary key and optional parameters for getList()
	 *
	 * @param mixed $primary Primary key of the entity
	 * @param array $parameters Additional parameters for getList()
	 * @return Main\DB\Result
	 */
	public static function getByPrimary($primary, array $parameters = array())
	{
		static::normalizePrimary($primary);
		static::validatePrimary($primary);

		$primaryFilter = array();

		foreach ($primary as $k => $v)
		{
			$primaryFilter['='.$k] = $v;
		}

		if (isset($parameters['filter']))
		{
			$parameters['filter'] = array($primaryFilter, $parameters['filter']);
		}
		else
		{
			$parameters['filter'] = $primaryFilter;
		}

		return static::getList($parameters);
	}

	/**
	 * Returns selection by entity's primary key

	 * @param mixed $id Primary key of the entity
	 * @return Main\DB\Result
	 */
	public static function getById($id)
	{
		return static::getByPrimary($id);
	}

	/**
	 * Returns one row (or null) by entity's primary key
	 *
	 * @param mixed $id Primary key of the entity
	 * @return array|null
	 */
	public static function getRowById($id)
	{
		$result = static::getByPrimary($id);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	/**
	 * Returns one row (or null) by parameters for getList()
	 *
	 * @param array $parameters Primary key of the entity
	 * @return array|null
	 */
	public static function getRow(array $parameters)
	{
		$parameters['limit'] = 1;
		$result = static::getList($parameters);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	/**
	 * Executes the query and returns selection by parameters of the query. This function is an alias to the Query object functions
	 *
	 * @param array $parameters Array of query parameters, available keys are:
	 * 		"select" => array of fields in the SELECT part of the query, aliases are possible in the form of "alias"=>"field"
	 * 		"filter" => array of filters in the WHERE part of the query in the form of "(condition)field"=>"value"
	 * 		"group" => array of fields in the GROUP BY part of the query
	 * 		"order" => array of fields in the ORDER BY part of the query in the form of "field"=>"asc|desc"
	 * 		"limit" => integer indicating maximum number of rows in the selection (like LIMIT n in MySql)
	 * 		"offset" => integer indicating first row number in the selection (like LIMIT n, 100 in MySql)
	 *		"runtime" => array of entity fields created dynamically
	 * @return Main\DB\Result
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getList(array $parameters = array())
	{
		$query = static::query();

		if(!isset($parameters['select']))
		{
			$query->setSelect(array('*'));
		}

		foreach($parameters as $param => $value)
		{
			switch($param)
			{
				case 'select':
					$query->setSelect($value);
					break;
				case 'filter':
					$query->setFilter($value);
					break;
				case 'group':
					$query->setGroup($value);
					break;
				case 'order';
					$query->setOrder($value);
					break;
				case 'limit':
					$query->setLimit($value);
					break;
				case 'offset':
					$query->setOffset($value);
					break;
				case 'count_total':
					$query->countTotal($value);
					break;
				case 'runtime':
					foreach ($value as $name => $fieldInfo)
					{
						$query->registerRuntimeField($name, $fieldInfo);
					}
					break;
				case 'data_doubling':
					if($value)
						$query->enableDataDoubling();
					else
						$query->disableDataDoubling();
					break;
				default:
					throw new Main\ArgumentException("Unknown parameter: ".$param, $param);
			}
		}

		return $query->exec();
	}

	/**
	 * Performs COUNT query on entity and returns the result.
	 *
	 * @param array $filter
	 * @return int
	 */
	public static function getCount(array $filter = array())
	{
		$query = static::query();

		$query->addSelect(new ExpressionField('CNT', 'COUNT(1)'));
		$query->setFilter($filter);

		$result = $query->exec()->fetch();

		return $result['CNT'];
	}

	/**
	 * Creates and returns the Query object for the entity
	 *
	 * @return Query
	 */
	public static function query()
	{
		return new Query(static::getEntity());
	}

	protected static function replaceFieldName($data = array())
	{
		foreach ($data as $fieldName => $value)
		{
			/** @var ScalarField $field */
			$field = static::getEntity()->getField($fieldName);
			$columnName = $field->getColumnName();
			if($columnName != $fieldName)
			{
				$data[$columnName] = $data[$fieldName];
				unset($data[$fieldName]);
			}
		}

		return $data;
	}

	protected static function normalizePrimary(&$primary, $data = array())
	{
		$entity = static::getEntity();
		$entity_primary = $entity->getPrimaryArray();

		if ($primary === null)
		{
			$primary = array();

			// extract primary from data array
			foreach ($entity_primary as $key)
			{
				/** @var ScalarField $field  */
				$field = $entity->getField($key);
				if ($field->isAutocomplete())
				{
					continue;
				}

				if (!isset($data[$key]))
				{
					throw new Main\ArgumentException(sprintf(
						'Primary `%s` was not found when trying to query %s row.', $key, static::getEntity()->getName()
					));
				}

				$primary[$key] = $data[$key];
			}
		}
		elseif (is_scalar($primary))
		{
			if (count($entity_primary) > 1)
			{
				throw new Main\ArgumentException(sprintf(
					'Require multi primary {`%s`}, but one scalar value "%s" found when trying to query %s row.',
					join('`, `', $entity_primary), $primary, static::getEntity()->getName()
				));
			}

			$primary = array($entity_primary[0] => $primary);
		}
	}

	protected static function validatePrimary($primary)
	{
		if (is_array($primary))
		{
			if(empty($primary))
			{
				throw new Main\ArgumentException(sprintf(
					'Empty primary found when trying to query %s row.', static::getEntity()->getName()
				));
			}

			$entity_primary = static::getEntity()->getPrimaryArray();

			foreach (array_keys($primary) as $key)
			{
				if (!in_array($key, $entity_primary, true))
				{
					throw new Main\ArgumentException(sprintf(
						'Unknown primary `%s` found when trying to query %s row.',
						$key, static::getEntity()->getName()
					));
				}
			}
		}
		else
		{
			throw new Main\ArgumentException(sprintf(
				'Unknown type of primary "%s" found when trying to query %s row.', gettype($primary), static::getEntity()->getName()
			));
		}

		// primary values validation
		foreach ($primary as $key => $value)
		{
			if (!is_scalar($value) && !($value instanceof Main\Type\Date))
			{
				throw new Main\ArgumentException(sprintf(
					'Unknown value type "%s" for primary "%s" found when trying to query %s row.',
					gettype($value), $key, static::getEntity()->getName()
				));
			}
		}
	}

	/**
	 * Checks the data fields before saving to DB. Result stores in the $result object
	 *
	 * @param Result $result
	 * @param mixed $primary
	 * @param array $data
	 * @throws Main\ArgumentException
	 */
	public static function checkFields(Result $result, $primary, array $data)
	{
		//checks required fields
		foreach (static::getEntity()->getFields() as $field)
		{
			if ($field instanceof ScalarField && $field->isRequired())
			{
				$fieldName = $field->getName();
				if (
					(empty($primary) && (!isset($data[$fieldName]) || $field->isValueEmpty($data[$fieldName])))
					|| (!empty($primary) && isset($data[$fieldName]) && $field->isValueEmpty($data[$fieldName]))
				)
				{
					$result->addError(new FieldError(
						$field,
						Loc::getMessage("MAIN_ENTITY_FIELD_REQUIRED", array("#FIELD#"=>$field->getTitle())),
						FieldError::EMPTY_REQUIRED
					));
				}
			}
		}

		// checks data - fieldname & type & strlen etc.
		foreach ($data as $k => $v)
		{
			if (static::getEntity()->hasField($k) && static::getEntity()->getField($k) instanceof ScalarField)
			{
				$field = static::getEntity()->getField($k);
			}
			elseif (static::getEntity()->hasUField($k))
			{
				// should be continue
				// checking is inside uf manager
				$field = static::getEntity()->getUField($k);
			}
			elseif (static::getEntity()->hasField($k) && static::getEntity()->getField($k) instanceof FileField)
			{
				// why not
				$field = static::getEntity()->getField($k);
			}
			else
			{
				throw new Main\ArgumentException(sprintf(
					'Field `%s` not found in entity when trying to query %s row.',
					$k, static::getEntity()->getName()
				));
			}

			$field->validateValue($v, $primary, $data, $result);
		}
	}

	/**
	 * Adds row to entity table
	 *
	 * @param array $data
	 *
	 * @return AddResult Contains ID of inserted row
	 *
	 * @throws \Exception
	 */
	public static function add(array $data)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		$entity = static::getEntity();
		$result = new AddResult();

		try
		{
			//event before adding
			$event = new Event($entity, self::EVENT_ON_BEFORE_ADD, array("fields" => $data));
			$event->send();
			$event->getErrors($result);
			$data = $event->mergeFields($data);

			//event before adding (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_BEFORE_ADD, array("fields" => $data), true);
			$event->send();
			$event->getErrors($result);
			$data = $event->mergeFields($data);

			// set fields with default values
			foreach (static::getEntity()->getFields() as $field)
			{
				if ($field instanceof ScalarField && !array_key_exists($field->getName(), $data))
				{
					$defaultValue = $field->getDefaultValue();

					if ($defaultValue !== null)
					{
						$data[$field->getName()] = $field->getDefaultValue();
					}
				}
			}

			// uf values
			$ufdata = array();

			// separate userfields
			if (static::getEntity()->getUfId())
			{
				// collect uf data
				$userfields = $USER_FIELD_MANAGER->GetUserFields(static::getEntity()->getUfId());

				foreach ($userfields as $userfield)
				{
					if (array_key_exists($userfield['FIELD_NAME'], $data))
					{
						// copy value
						$ufdata[$userfield['FIELD_NAME']] = $data[$userfield['FIELD_NAME']];

						// remove original
						unset($data[$userfield['FIELD_NAME']]);
					}
				}
			}

			// check data
			static::checkFields($result, null, $data);

			// check uf data
			if (!empty($ufdata))
			{
				if (!$USER_FIELD_MANAGER->CheckFields(static::getEntity()->getUfId(), false, $ufdata))
				{
					if (is_object($APPLICATION) && $APPLICATION->getException())
					{
						$e = $APPLICATION->getException();
						$result->addError(new EntityError($e->getString()));
						$APPLICATION->resetException();
					}
					else
					{
						$result->addError(new EntityError("Unknown error while checking userfields"));
					}
				}
			}

			// check if there is still some data
			if (!count($data + $ufdata))
			{
				$result->addError(new EntityError("There is no data to add."));
			}

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}

			//event on adding
			$event = new Event($entity, self::EVENT_ON_ADD, array("fields" => $data + $ufdata));
			$event->send();

			//event on adding (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_ADD, array("fields" => $data + $ufdata), true);
			$event->send();

			// use save modifiers
			foreach ($data as $fieldName => $value)
			{
				$field = static::getEntity()->getField($fieldName);
				$data[$fieldName] = $field->modifyValueBeforeSave($value, $data);
			}

			// save data
			$connection = $entity->getConnection();

			$tableName = $entity->getDBTableName();
			$identity = $entity->getAutoIncrement();

			$dataReplacedColumn = static::replaceFieldName($data);
			$id = $connection->add($tableName, $dataReplacedColumn, $identity);

			$result->setId($id);
			$result->setData($data);

			// build stamdard primary
			$primary = null;

			if (!empty($id))
			{
				$primary = array($entity->getAutoIncrement() => $id);
				static::normalizePrimary($primary);
			}
			else
			{
				static::normalizePrimary($primary, $data);
			}

			// save uf data
			if (!empty($ufdata))
			{
				$USER_FIELD_MANAGER->update(static::getEntity()->getUfId(), end($primary), $ufdata);
			}

			//event after adding
			$event = new Event($entity, self::EVENT_ON_AFTER_ADD, array("id" => $id, "fields" => $data));
			$event->send();

			//event after adding (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_AFTER_ADD, array("id" => $id, "primary" => $primary, "fields" => $data), true);
			$event->send();
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	/**
	 * Updates row in entity table by primary key
	 *
	 * @param mixed $primary
	 * @param array $data
	 *
	 * @return UpdateResult
	 *
	 * @throws \Exception
	 */
	public static function update($primary, array $data)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;

		// check primary
		static::normalizePrimary($primary, $data);
		static::validatePrimary($primary);

		$entity = static::getEntity();
		$result = new UpdateResult();

		try
		{
			//event before update
			$event = new Event($entity, self::EVENT_ON_BEFORE_UPDATE, array("id" => $primary, "fields" => $data));
			$event->send();
			$event->getErrors($result);
			$data = $event->mergeFields($data);

			//event before update (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_BEFORE_UPDATE, array("id" => $primary, "primary" => $primary, "fields" => $data), true);
			$event->send();
			$event->getErrors($result);
			$data = $event->mergeFields($data);

			// uf values
			$ufdata = array();

			// separate userfields
			if (static::getEntity()->getUfId())
			{
				// collect uf data
				$userfields = $USER_FIELD_MANAGER->GetUserFields(static::getEntity()->getUfId());

				foreach ($userfields as $userfield)
				{
					if (array_key_exists($userfield['FIELD_NAME'], $data))
					{
						// copy value
						$ufdata[$userfield['FIELD_NAME']] = $data[$userfield['FIELD_NAME']];

						// remove original
						unset($data[$userfield['FIELD_NAME']]);
					}
				}
			}

			// check data
			static::checkFields($result, $primary, $data);

			// check uf data
			if (!empty($ufdata))
			{
				if (!$USER_FIELD_MANAGER->CheckFields(static::getEntity()->getUfId(), end($primary), $ufdata))
				{
					if (is_object($APPLICATION) && $APPLICATION->getException())
					{
						$e = $APPLICATION->getException();
						$result->addError(new EntityError($e->getString()));
						$APPLICATION->resetException();
					}
					else
					{
						$result->addError(new EntityError("Unknown error while checking userfields"));
					}
				}
			}

			// check if there is still some data
			if (!count($data + $ufdata))
			{
				$result->addError(new EntityError("There is no data to update."));
			}

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}

			//event on update
			$event = new Event($entity, self::EVENT_ON_UPDATE, array("id" => $primary, "fields" => $data + $ufdata));
			$event->send();

			//event on update (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_UPDATE, array("id" => $primary, "primary" => $primary, "fields" => $data + $ufdata), true);
			$event->send();

			// use save modifiers
			foreach ($data as $fieldName => $value)
			{
				$field = static::getEntity()->getField($fieldName);
				$data[$fieldName] = $field->modifyValueBeforeSave($value, $data);
			}

			// save data
			$connection = $entity->getConnection();
			$helper = $connection->getSqlHelper();

			$tableName = $entity->getDBTableName();

			$dataReplacedColumn = static::replaceFieldName($data);
			$update = $helper->prepareUpdate($tableName, $dataReplacedColumn);

			$replacedPrimary = static::replaceFieldName($primary);
			$id = array();
			foreach ($replacedPrimary as $k => $v)
			{
				$id[] = $helper->prepareAssignment($tableName, $k, $v);
			}
			$where = implode(' AND ', $id);

			$sql = "UPDATE ".$tableName." SET ".$update[0]." WHERE ".$where;
			$connection->queryExecute($sql, $update[1]);

			$result->setAffectedRowsCount($connection);
			$result->setData($data);
			$result->setPrimary($primary);

			// save uf data
			if (!empty($ufdata))
			{
				$USER_FIELD_MANAGER->update(static::getEntity()->getUfId(), end($primary), $ufdata);
			}

			//event after update
			$event = new Event($entity, self::EVENT_ON_AFTER_UPDATE, array("id" => $primary, "fields" => $data));
			$event->send();

			//event after update (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_AFTER_UPDATE, array("id" => $primary, "primary" => $primary, "fields" => $data), true);
			$event->send();
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	/**
	 * Deletes row in entity table by primary key
	 *
	 * @param mixed $primary
	 *
	 * @return DeleteResult
	 *
	 * @throws \Exception
	 */
	public static function delete($primary)
	{
		global $USER_FIELD_MANAGER;

		// check primary
		static::normalizePrimary($primary);
		static::validatePrimary($primary);

		$entity = static::getEntity();
		$result = new DeleteResult();

		try
		{
			//event before delete
			$event = new Event($entity, self::EVENT_ON_BEFORE_DELETE, array("id" => $primary));
			$event->send();
			$event->getErrors($result);

			//event before delete (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_BEFORE_DELETE, array("id" => $primary, "primary" => $primary), true);
			$event->send();
			$event->getErrors($result);

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}

			//event on delete
			$event = new Event($entity, self::EVENT_ON_DELETE, array("id" => $primary));
			$event->send();

			//event on delete (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_DELETE, array("id" => $primary, "primary" => $primary), true);
			$event->send();

			// delete
			$connection = $entity->getConnection();
			$helper = $connection->getSqlHelper();

			$tableName = $entity->getDBTableName();

			$replacedPrimary = static::replaceFieldName($primary);
			$id = array();
			foreach ($replacedPrimary as $k => $v)
			{
				$id[] = $helper->prepareAssignment($tableName, $k, $v);
			}
			$where = implode(' AND ', $id);

			$sql = "DELETE FROM ".$tableName." WHERE ".$where;
			$connection->queryExecute($sql);

			// delete uf data
			if (static::getEntity()->getUfId())
			{
				$USER_FIELD_MANAGER->delete(static::getEntity()->getUfId(), end($primary));
			}

			//event after delete
			$event = new Event($entity, self::EVENT_ON_AFTER_DELETE, array("id" => $primary));
			$event->send();

			//event after delete (modern with namespace)
			$event = new Event($entity, self::EVENT_ON_AFTER_DELETE, array("id" => $primary, "primary" => $primary), true);
			$event->send();
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	/*
	An inheritor class can define the event handlers for own events.
	Why? To prevent from rewriting the add/update/delete functions.
	These handlers are triggered in the Bitrix\Main\Entity\Event::send() function
	*/
	public static function onBeforeAdd(Event $event){}
	public static function onAdd(Event $event){}
	public static function onAfterAdd(Event $event){}
	public static function onBeforeUpdate(Event $event){}
	public static function onUpdate(Event $event){}
	public static function onAfterUpdate(Event $event){}
	public static function onBeforeDelete(Event $event){}
	public static function onDelete(Event $event){}
	public static function onAfterDelete(Event $event){}
}
