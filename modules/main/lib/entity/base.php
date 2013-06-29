<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Base entity
 * @package bitrix
 * @subpackage main
 */
class Base
{
	protected
		$className,
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

	protected
		$fieldsMap,
		$u_fields;

	protected
		$references;

	protected
		$filePath;

	protected static
		$instances;


	/**
	 * @static
	 *
	 * @param string $entityName
	 *
	 * @return Base
	 */
	public static function getInstance($entityName)
	{
		if (strtolower(substr($entityName, -5)) !== 'table')
		{
			$entityName .= 'Table';
		}

		return self::getInstanceDirect($entityName);
	}


	protected static function getInstanceDirect($className)
	{
		if (empty(self::$instances[$className]))
		{
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
	 * @return BooleanField|ScalarField|ExpressionField|ReferenceField|UField
	 * @throws \Exception
	 */
	public function initializeField($fieldName, $fieldInfo)
	{
		if (!empty($fieldInfo['reference']))
		{
			if (strpos($fieldInfo['data_type'], '\\') === false)
			{
				// if reference has no namespace, then it'is in the same namespace
				$fieldInfo['data_type'] = $this->getNamespace().$fieldInfo['data_type'];
			}

			//$refEntity = Base::getInstance($fieldInfo['data_type']."Table");
			$field = new ReferenceField($fieldName, $this, $fieldInfo['data_type'], $fieldInfo['reference'], $fieldInfo);
		}
		elseif (!empty($fieldInfo['expression']))
		{
			$field = new ExpressionField($fieldName, $fieldInfo['data_type'], $this, $fieldInfo['expression'], $fieldInfo);
		}
		elseif (!empty($fieldInfo['USER_TYPE_ID']))
		{
			$field = new UField($fieldInfo, $this);
		}
		else
		{
			$fieldClass = Base::snake2camel($fieldInfo['data_type']) . 'Field';
			$fieldClass = __NAMESPACE__.'\\'.$fieldClass;

			if (strlen($fieldInfo['data_type']) && class_exists($fieldClass))
			{
				$field = new $fieldClass($fieldName, $fieldInfo['data_type'], $this, $fieldInfo);
			}
			else
			{
				throw new \Exception(sprintf(
					'Unknown data type "%s" found for `%s` field in %s Entity.',
					$fieldInfo['data_type'], $fieldName, $this->getName()
				));
			}
		}

		return $field;
	}

	public function initialize($className)
	{
		$this->className = $className;
		//TODO: don't use $this->filePath
		$this->filePath = $className::getFilePath();
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
			$field = $this->initializeField($fieldName, $fieldInfo);

			if ($field instanceof ReferenceField)
			{
				// references cache
				$this->references[strtolower($fieldInfo['data_type'])][] = $field;
			}

			$this->fields[$fieldName] = $field;

			if ($field instanceof ScalarField && $field->isPrimary())
			{
				$this->primary[] = $fieldName;

				if($field->isAutocomplete())
					$this->autoIncrement = $fieldName;
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

				//$refEntity = Base::getInstance($newFieldInfo['data_type']."Table");
				$newRefField = new ReferenceField($refFieldName, $this, $newFieldInfo['data_type'], $newFieldInfo['reference'][0], $newFieldInfo['reference'][1]);

				$this->fields[$refFieldName] = $newRefField;
			}
		}

		if (empty($this->primary))
		{
			throw new \Exception(sprintf('Primary not found for %s Entity', $this->name));
		}
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
	 * @throws \Exception
	 */
	public function getField($name)
	{
		if ($this->hasField($name))
		{
			return $this->fields[$name];
		}

		throw new \Exception(sprintf(
			'%s Entity has no `%s` field.', $this->getName(), $name
		));
	}

	public function hasField($name)
	{
		return isset($this->fields[$name]);
	}

	public function getUField($name)
	{
		if ($this->hasUField($name))
		{
			return $this->u_fields[$name];
		}

		throw new \Exception(sprintf(
			'%s Entity has no `%s` userfield.', $this->getName(), $name
		));
	}

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
					$this->u_fields[$info['FIELD_NAME']] = new UField($info, $this);

					// add references for ufield (UF_DEPARTMENT_BY)
					if ($info['USER_TYPE_ID'] == 'iblock_section')
					{
						$info['FIELD_NAME'] .= '_BY';
						$this->u_fields[$info['FIELD_NAME']] = new UField($info, $this);
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
		// Bitrix\Main\Site -> main
		// Partner\Module\Thing -> partner.module
		$parts = explode("\\", $this->className);
		if($parts[0] == "Bitrix")
			return strtolower($parts[1]);
		else
			return strtolower($parts[0].".".$parts[1]);
	}

	public function getDataClass()
	{
		return $this->className;
	}

	public function getConnection()
	{
		return \Bitrix\Main\Application::getInstance()->getDbConnectionPool()->getConnection($this->connectionName);
	}

	public function getFilePath()
	{
		return $this->filePath;
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
		return class_exists($name . 'Table');
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
		return strtolower(preg_replace('/(.)([A-Z])(.*?)/', '$1_$2$3', $str));
	}

	public static function snake2camel($str)
	{
		$str = str_replace('_', ' ', strtolower($str));
		return str_replace(' ', '', ucwords($str));
	}

	public static function getInstanceByQuery(Query $query, &$entity_name = null)
	{
		if ($entity_name === null)
		{
			$entity_name = 'Tmp'.randString();
		}
		elseif (!preg_match('/^[a-z0-9_]+$/i', $entity_name))
		{
			throw new \Exception(sprintf(
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
				$fieldDefinition = is_numeric($k) ? $v : $k;
				$fieldsMap[$fieldDefinition] = array('data_type' => $query_chains[$fieldDefinition]->getLastElement()->getValue()->getDataType());
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
		$eval .= 'public static function getFilePath() {'.PHP_EOL;
		$eval .= 'return null;'.PHP_EOL;
		$eval .= '}';
		$eval .= '}';

		eval($eval);

		return self::getInstance($entity_name);
	}
}
