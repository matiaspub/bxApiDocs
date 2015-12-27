<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

use Bitrix\Main;

/**
 * Base entity
 */
class Base
{
	protected
		$className,
		$module,
		$name,
		$connectionName,
		$dbTableName,
		$primary,
		$autoIncrement;

	protected
		$uf_id,
		$isUts,
		$isUtm;

	/** @var Field[] */
	protected $fields;

	protected $fieldsMap;

	/** @var UField[] */
	protected $u_fields;

	protected
		$references;

	protected static
		$instances;

	/** @var bool */
	protected $isClone = false;

	/**
	 * @static
	 *
	 * @param string $entityName
	 *
	 * @return Base
	 */
	public static function getInstance($entityName)
	{
		$entityName = static::normalizeEntityClass($entityName);

		return self::getInstanceDirect($entityName);
	}


	protected static function getInstanceDirect($className)
	{
		if (empty(self::$instances[$className]))
		{
			/** @var Base $entity */
			$entity = new static;
			$entity->initialize($className);
			$entity->postInitialize();

			self::$instances[$className] = $entity;
		}

		return self::$instances[$className];
	}

	/**
	 * Fields factory
	 * @param string $fieldName
	 * @param array  $fieldInfo
	 *
	 * @return Field
	 * @throws Main\ArgumentException
	 */
	public function initializeField($fieldName, $fieldInfo)
	{
		if ($fieldInfo instanceof Field)
		{
			$field = $fieldInfo;
		}
		elseif (!empty($fieldInfo['reference']))
		{
			if (is_string($fieldInfo['data_type']) && strpos($fieldInfo['data_type'], '\\') === false)
			{
				// if reference has no namespace, then it'is in the same namespace
				$fieldInfo['data_type'] = $this->getNamespace().$fieldInfo['data_type'];
			}

			//$refEntity = Base::getInstance($fieldInfo['data_type']."Table");
			$field = new ReferenceField($fieldName, $fieldInfo['data_type'], $fieldInfo['reference'], $fieldInfo);
		}
		elseif (!empty($fieldInfo['expression']))
		{
			$expression = array_shift($fieldInfo['expression']);
			$buildFrom =  $fieldInfo['expression'];

			$field = new ExpressionField($fieldName, $expression, $buildFrom, $fieldInfo);
		}
		elseif (!empty($fieldInfo['USER_TYPE_ID']))
		{
			$field = new UField($fieldInfo);
		}
		else
		{
			$fieldClass = Base::snake2camel($fieldInfo['data_type']) . 'Field';
			$fieldClass = __NAMESPACE__.'\\'.$fieldClass;

			if (strlen($fieldInfo['data_type']) && class_exists($fieldClass))
			{
				$field = new $fieldClass($fieldName, $fieldInfo);
			}
			elseif (strlen($fieldInfo['data_type']) && class_exists($fieldInfo['data_type']))
			{
				$fieldClass = $fieldInfo['data_type'];
				$field = new $fieldClass($fieldName, $fieldInfo);
			}
			else
			{
				throw new Main\ArgumentException(sprintf(
					'Unknown data type "%s" found for `%s` field in %s Entity.',
					$fieldInfo['data_type'], $fieldName, $this->getName()
				));
			}
		}

		$field->setEntity($this);

		return $field;
	}

	public function initialize($className)
	{
		/** @var $className \Bitrix\Main\Entity\DataManager */
		$this->className = $className;

		/** @var DataManager $className */
		$this->connectionName = $className::getConnectionName();
		$this->dbTableName = $className::getTableName();
		$this->fieldsMap = $className::getMap();
		$this->uf_id = $className::getUfId();
		$this->isUts = $className::isUts();
		$this->isUtm = $className::isUtm();
	}

	public function postInitialize()
	{
		// basic properties
		$classPath = explode('\\', ltrim($this->className, '\\'));
		$this->name = substr(end($classPath), 0, -5);

		// default db table name
		if (is_null($this->dbTableName))
		{
			$_classPath = array_slice($classPath, 0, -1);

			$this->dbTableName = 'b_';

			foreach ($_classPath as $i => $_pathElem)
			{
				if ($i == 0 && $_pathElem == 'Bitrix')
				{
					// skip bitrix namespace
					continue;
				}

				if ($i == 1 && $_pathElem == 'Main')
				{
					// also skip Main module
					continue;
				}

				$this->dbTableName .= strtolower($_pathElem).'_';
			}

			// add class
			if ($this->name !== end($_classPath))
			{
				$this->dbTableName .= Base::camel2snake($this->name);
			}
			else
			{
				$this->dbTableName = substr($this->dbTableName, 0, -1);
			}
		}

		$this->primary = array();
		$this->references = array();

		// attributes
		foreach ($this->fieldsMap as $fieldName => &$fieldInfo)
		{
			$this->addField($fieldInfo, $fieldName);
		}

		if (!empty($this->fieldsMap) && empty($this->primary))
		{
			throw new Main\SystemException(sprintf('Primary not found for %s Entity', $this->name));
		}

		// attach userfields
		if (!empty($this->uf_id))
		{
			Main\UserFieldTable::attachFields($this, $this->uf_id);
		}
	}

	/**
	 * @param Field $field
	 *
	 * @return bool
	 */
	protected function appendField(Field $field)
	{
		if (isset($this->fields[$field->getName()]) && !$this->isClone)
		{
			trigger_error(sprintf(
				'Entity `%s` already has Field with name `%s`.', $this->getFullName(), $field->getName()
			), E_USER_WARNING);

			return false;
		}

		if ($field instanceof ReferenceField)
		{
			// references cache
			$this->references[$field->getRefEntityName()][] = $field;
		}

		$this->fields[$field->getName()] = $field;

		if ($field instanceof ScalarField && $field->isPrimary())
		{
			$this->primary[] = $field->getName();

			if($field->isAutocomplete())
			{
				$this->autoIncrement = $field->getName();
			}
		}

		// add reference field for UField iblock_section
		if ($field instanceof UField && $field->getTypeId() == 'iblock_section')
		{
			$refFieldName = $field->getName().'_BY';

			if ($field->isMultiple())
			{
				$localFieldName = $field->getValueFieldName();
			}
			else
			{
				$localFieldName = $field->getName();
			}

			$newFieldInfo = array(
				'data_type' => 'Bitrix\Iblock\Section',
				'reference' => array($localFieldName, 'ID')
			);

			$newRefField = new ReferenceField($refFieldName, $newFieldInfo['data_type'], $newFieldInfo['reference'][0], $newFieldInfo['reference'][1]);
			$newRefField->setEntity($this);

			$this->fields[$refFieldName] = $newRefField;
		}

		return true;
	}

	/**
	 * @param array|Field $fieldInfo
	 * @param null|string $fieldName
	 *
	 * @return Field|false
	 */
	public function addField($fieldInfo, $fieldName = null)
	{
		$field = $this->initializeField($fieldName, $fieldInfo);

		return $this->appendField($field) ? $field : false;
	}

	public function getReferencesCountTo($refEntityName)
	{
		if (array_key_exists($key = strtolower($refEntityName), $this->references))
		{
			return count($this->references[$key]);
		}

		return 0;
	}


	public function getReferencesTo($refEntityName)
	{
		if (array_key_exists($key = strtolower($refEntityName), $this->references))
		{
			return $this->references[$key];
		}

		return array();
	}

	// getters
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * @param $name
	 *
	 * @return Field
	 * @throws Main\ArgumentException
	 */
	public function getField($name)
	{
		if ($this->hasField($name))
		{
			return $this->fields[$name];
		}

		throw new Main\ArgumentException(sprintf(
			'%s Entity has no `%s` field.', $this->getName(), $name
		));
	}

	public function hasField($name)
	{
		return isset($this->fields[$name]);
	}

	/**
	 * @return ScalarField[]
	 */
	public function getScalarFields()
	{
		$scalarFields = array();

		foreach ($this->getFields() as $field)
		{
			if ($field instanceof ScalarField)
			{
				$scalarFields[$field->getName()] = $field;
			}
		}

		return $scalarFields;
	}

	/**
	 * @deprecated
	 * @param $name
	 *
	 * @return UField
	 * @throws \Exception
	 */
	public function getUField($name)
	{
		if ($this->hasUField($name))
		{
			return $this->u_fields[$name];
		}

		throw new Main\ArgumentException(sprintf(
			'%s Entity has no `%s` userfield.', $this->getName(), $name
		));
	}

	/**
	 * @deprecated
	 * @param $name
	 *
	 * @return bool
	 */
	public function hasUField($name)
	{
		if (is_null($this->u_fields))
		{
			$this->u_fields = array();

			if (strlen($this->uf_id))
			{
				/** @var \CAllUserTypeManager $USER_FIELD_MANAGER */
				global $USER_FIELD_MANAGER;

				foreach ($USER_FIELD_MANAGER->getUserFields($this->uf_id) as $info)
				{
					$this->u_fields[$info['FIELD_NAME']] = new UField($info);
					$this->u_fields[$info['FIELD_NAME']]->setEntity($this);

					// add references for ufield (UF_DEPARTMENT_BY)
					if ($info['USER_TYPE_ID'] == 'iblock_section')
					{
						$info['FIELD_NAME'] .= '_BY';
						$this->u_fields[$info['FIELD_NAME']] = new UField($info, $this);
						$this->u_fields[$info['FIELD_NAME']]->setEntity($this);
					}
				}
			}
		}

		return isset($this->u_fields[$name]);
	}

	public function getName()
	{
		return $this->name;
	}

	public function getFullName()
	{
		return substr($this->className, 0, -5);
	}

	public function getNamespace()
	{
		return substr($this->className, 0, strrpos($this->className, '\\')+1);
	}

	public function getModule()
	{
		if($this->module === null)
		{
			// \Bitrix\Main\Site -> "main"
			// \Partner\Module\Thing -> "partner.module"
			// \Thing -> ""
			$parts = explode("\\", $this->className);
			if($parts[1] == "Bitrix")
				$this->module = strtolower($parts[2]);
			elseif(!empty($parts[1]) && isset($parts[2]))
				$this->module = strtolower($parts[1].".".$parts[2]);
			else
				$this->module = "";
		}
		return $this->module;
	}

	/**
	 * @return DataManager
	 */
	public function getDataClass()
	{
		return $this->className;
	}

	/**
	 * @return Main\DB\Connection
	 */
	public function getConnection()
	{
		return \Bitrix\Main\Application::getInstance()->getConnectionPool()->getConnection($this->connectionName);
	}

	public function getDBTableName()
	{
		return $this->dbTableName;
	}

	public function getPrimary()
	{
		return count($this->primary) == 1 ? $this->primary[0] : $this->primary;
	}

	public function getPrimaryArray()
	{
		return $this->primary;
	}

	public function getAutoIncrement()
	{
		return $this->autoIncrement;
	}

	public function isUts()
	{
		return $this->isUts;
	}

	public function isUtm()
	{
		return $this->isUtm;
	}

	public function getUfId()
	{
		return $this->uf_id;
	}

	public static function isExists($name)
	{
		return class_exists(static::normalizeEntityClass($name));
	}

	public static function normalizeEntityClass($entityName)
	{
		if (strtolower(substr($entityName, -5)) !== 'table')
		{
			$entityName .= 'Table';
		}

		if (substr($entityName, 0, 1) !== '\\')
		{
			$entityName = '\\'.$entityName;
		}

		return $entityName;
	}

	public function getCode()
	{
		$code = '';

		// get absolute path to class
		$class_path = explode('\\', strtoupper(ltrim($this->className, '\\')));

		// cut class name to leave namespace only
		$class_path = array_slice($class_path, 0, -1);

		// cut Bitrix namespace
		if ($class_path[0] === 'BITRIX')
		{
			$class_path = array_slice($class_path, 1);
		}

		// glue module name
		if (count($class_path))
		{
			$code = join('_', $class_path).'_';
		}

		// glue entity name
		$code .= strtoupper(Base::camel2snake($this->getName()));

		return $code;
	}

	public function getLangCode()
	{
		return $this->getCode().'_ENTITY';
	}

	public static function camel2snake($str)
	{
		return strtolower(preg_replace('/(.)([A-Z])/', '$1_$2', $str));
	}

	public static function snake2camel($str)
	{
		$str = str_replace('_', ' ', strtolower($str));
		return str_replace(' ', '', ucwords($str));
	}

	public static function normalizeName($entityName)
	{
		if (substr($entityName, 0, 1) !== '\\')
		{
			$entityName = '\\'.$entityName;
		}

		if (strtolower(substr($entityName, -5)) === 'table')
		{
			$entityName = substr($entityName, 0, -5);
		}

		return $entityName;
	}

	public function __clone()
	{
		$this->isClone = true;
	}

	public static function getInstanceByQuery(Query $query, &$entity_name = null)
	{
		if ($entity_name === null)
		{
			$entity_name = 'Tmp'.randString();
		}
		elseif (!preg_match('/^[a-z0-9_]+$/i', $entity_name))
		{
			throw new Main\ArgumentException(sprintf(
				'Invalid entity name `%s`.', $entity_name
			));
		}

		$query_string = '('.$query->getQuery().')';
		$query_chains = $query->getChains();

		$replaced_aliases = array_flip($query->getReplacedAliases());

		// generate fieldsMap
		$fieldsMap = array('TMP_ID' => array('data_type' => 'integer', 'primary' => true));

		foreach ($query->getSelect() as $k => $v)
		{
			if (is_array($v))
			{
				// expression
				$fieldsMap[$k] = array('data_type' => $v['data_type']);
			}
			else
			{
				if ($v instanceof ExpressionField)
				{
					$fieldDefinition = $v->getName();
				}
				else
				{
					$fieldDefinition = is_numeric($k) ? $v : $k;
				}

				// better to initialize fields as objects after entity is created
				$dataType = Field::getOldDataTypeByField($query_chains[$fieldDefinition]->getLastElement()->getValue());

				$fieldsMap[$fieldDefinition] = array('data_type' => $dataType);
			}

			if (isset($replaced_aliases[$k]))
			{
				$fieldsMap[$k]['column_name'] = $replaced_aliases[$k];
			}
		}

		// generate class content
		$eval = 'class '.$entity_name.'Table extends '.__NAMESPACE__.'\DataManager {'.PHP_EOL;
		$eval .= 'public static function getMap() {'.PHP_EOL;
		$eval .= 'return '.var_export($fieldsMap, true).';'.PHP_EOL;
		$eval .= '}';
		$eval .= 'public static function getTableName() {'.PHP_EOL;
		$eval .= 'return '.var_export($query_string, true).';'.PHP_EOL;
		$eval .= '}';
		$eval .= '}';

		eval($eval);

		return self::getInstance($entity_name);
	}

	/**
	 * @param string               $entityName
	 * @param null|array[]|Field[] $fields
	 * @param array                $parameters [namespace, table_name, uf_id]
	 *
	 * @return Base
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function compileEntity($entityName, $fields = null, $parameters = array())
	{
		$classCode = '';
		$classCodeEnd = '';

		if (strtolower(substr($entityName, -5)) !== 'table')
		{
			$entityName .= 'Table';
		}

		// validation
		if (!preg_match('/^[a-z0-9_]+$/i', $entityName))
		{
			throw new Main\ArgumentException(sprintf(
				'Invalid entity classname `%s`.', $entityName
			));
		}

		$fullEntityName = $entityName;

		// namespace configuration
		if (!empty($parameters['namespace']) && $parameters['namespace'] !== '\\')
		{
			$namespace = $parameters['namespace'];

			if (!preg_match('/^[a-z0-9\\\\]+$/i', $namespace))
			{
				throw new Main\ArgumentException(sprintf(
					'Invalid namespace name `%s`', $namespace
				));
			}

			$classCode = $classCode."namespace {$namespace} "."{";
			$classCodeEnd = '}'.$classCodeEnd;

			$fullEntityName = '\\'.$namespace.'\\'.$fullEntityName;
		}

		// build entity code
		$classCode = $classCode."class {$entityName} extends \\Bitrix\\Main\\Entity\\DataManager "."{";
		$classCodeEnd = '}'.$classCodeEnd;

		if (!empty($parameters['table_name']))
		{
			$classCode .= 'public static function getTableName(){return '.var_export($parameters['table_name'], true).';}';
		}

		if (!empty($parameters['uf_id']))
		{
			$classCode .= 'public static function getUfId(){return '.var_export($parameters['uf_id'], true).';}';
		}

		// create entity
		eval($classCode.$classCodeEnd);

		$entity = self::getInstance($fullEntityName);

		// add fields
		if (!empty($fields))
		{
			foreach ($fields as $fieldName => $field)
			{
				$entity->addField($field, $fieldName);
			}
		}

		return $entity;
	}

	/**
	 * @return string[] Array of SQL queries
	 */
	public function compileDbTableStructureDump()
	{
		$fields = $this->getScalarFields();
		$connection = $this->getConnection();

		$autocomplete = array();

		foreach ($fields as $field)
		{
			if ($field->isAutocomplete())
			{
				$autocomplete[] = $field->getColumnName();
			}
		}

		// start collecting queries
		$connection->disableQueryExecuting();

		// create table
		$connection->createTable($this->getDBTableName(), $fields, $this->getPrimaryArray(), $autocomplete);

		// stop collecting queries
		$connection->enableQueryExecuting();

		return $connection->getDisabledQueryExecutingDump();
	}

	/**
	 * Creates table according to Fields collection
	 *
	 * @return void
	 */
	public function createDbTable()
	{
		foreach ($this->compileDbTableStructureDump() as $sqlQuery)
		{
			$this->getConnection()->query($sqlQuery);
		}
	}

	/**
	 * @param Base|string $entity
	 *
	 * @return bool
	 */
	public static function destroy($entity)
	{
		if ($entity instanceof Base)
		{
			$entityName = $entity->getDataClass();
		}
		else
		{
			$entityName = static::normalizeEntityClass($entity);
		}

		if (isset(self::$instances[$entityName]))
		{
			unset(self::$instances[$entityName]);
			return true;
		}

		return false;
	}
}
