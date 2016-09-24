<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main;

use Bitrix\Main\Entity;
use Bitrix\Main\Type;

/**
 * Class description
 * @package bitrix
 * @subpackage main
 */
class UserFieldTable extends Entity\DataManager
{
	// to use in uts serialized fields
	const MULTIPLE_DATE_FORMAT = 'Y-m-d';
	const MULTIPLE_DATETIME_FORMAT = 'Y-m-d H:i:s';

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'ENTITY_ID' => array(
				'data_type' => 'string'
			),
			'FIELD_NAME' => array(
				'data_type' => 'string'
			),
			'USER_TYPE_ID' => array(
				'data_type' => 'string'
			),
			'XML_ID' => array(
				'data_type' => 'string'
			),
			'SORT' => array(
				'data_type' => 'integer'
			),
			'MULTIPLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'MANDATORY' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'SHOW_FILTER' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'SHOW_IN_LIST' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'EDIT_IN_LIST' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'IS_SEARCHABLE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'SETTINGS' => array(
				'data_type' => 'text',
				'serialized' => true
			)
		);
	}

	public static function add(array $data)
	{
		throw new NotImplementedException('Use \CUserTypeEntity API instead.');
	}

	public static function update($primary, array $data)
	{
		throw new NotImplementedException('Use \CUserTypeEntity API instead.');
	}

	public static function delete($primary)
	{
		throw new NotImplementedException('Use \CUserTypeEntity API instead.');
	}

	public static function attachFields(Entity\Base $entity, $ufId)
	{
		global $USER_FIELD_MANAGER;

		$utsFields = array();
		$utsFieldNames = array();

		$utmFields = array();
		$utmFieldNames = array();

		$fields = $USER_FIELD_MANAGER->getUserFields($ufId);

		foreach ($fields as $field)
		{
			if ($field['MULTIPLE'] === 'Y')
			{
				$utmFields[] = $field;
				$utmFieldNames[$field['FIELD_NAME']] = true;
			}
			else
			{
				$utsFields[] = $field;
				$utsFieldNames[$field['FIELD_NAME']] = true;
			}
		}

		if (!empty($utsFields) || !empty($utmFields))
		{
			// create uts entity & put fields into it
			$utsEntity = static::createUtsEntity($entity, $utsFields, $utmFields);

			// create reference to uts entity
			$utsReference = new Entity\ReferenceField('UTS_OBJECT', $utsEntity->getDataClass(), array(
				'=this.ID' => 'ref.VALUE_ID'
			));

			$entity->addField($utsReference);

			// add UF_* aliases
			foreach ($fields as $userfield)
			{
				$utsFieldName = $userfield['FIELD_NAME'];

				/** @var Entity\ScalarField $utsField */
				$utsField = $utsEntity->getField($utsFieldName);

				$aliasField = new Entity\ExpressionField(
					$utsFieldName,
					'%s',
					'UTS_OBJECT.'.$utsFieldName,
					array('data_type' => get_class($utsField))
				);

				if ($userfield['MULTIPLE'] == 'Y')
				{
					static::setMultipleFieldSerialization($aliasField, $userfield);
				}

				$entity->addField($aliasField);
			}


			if (!empty($utsFields))
			{
				foreach ($utsFields as $utsField)
				{
					$utsEntityField = $utsEntity->getField($utsField['FIELD_NAME']);

					foreach ($USER_FIELD_MANAGER->getEntityReferences($utsField, $utsEntityField) as $reference)
					{
						// rewrite reference from this.field to this.uts_object.field
						$referenceDesc = static::rewriteUtsReference($reference->getReference());

						$aliasReference = new Entity\ReferenceField(
							$reference->getName(),
							$reference->getRefEntityName(),
							$referenceDesc
						);

						$entity->addField($aliasReference);
					}
				}
			}

			if (!empty($utmFields))
			{
				// create utm entity & put base fields into it
				$utmEntity = static::createUtmEntity($entity, $utmFields);

				// add UF_* aliases
				foreach ($utmFieldNames as $utmFieldName => $true)
				{
					/** @var Entity\ScalarField $utmField */
					$utmField = $utmEntity->getField($utmFieldName);

					$aliasField = new Entity\ExpressionField(
						$utmFieldName.'_SINGLE',
						'%s',
						$utmEntity->getFullName().':PARENT_'.$utmFieldName.'.'.$utmField->getColumnName(),
						array('data_type' => get_class($utmField))
					);

					$entity->addField($aliasField);
				}
			}
		}
	}

	protected static function createUtsEntity(Entity\Base $srcEntity, array $utsFields, array $utmFields)
	{
		global $USER_FIELD_MANAGER;

		// get namespace & class
		/** @var Entity\DataManager $utsClassFull */
		$utsClassFull = static::getUtsEntityClassNameBySrcEntity($srcEntity);
		$utsClassPath = explode('\\', ltrim($utsClassFull, '\\'));

		$utsNamespace = join('\\', array_slice($utsClassPath, 0, -1));
		$utsClass = end($utsClassPath);

		// get table name
		$utsTable = static::getUtsEntityTableNameBySrcEntity($srcEntity);

		// base fields
		$fieldsMap = array(
			'VALUE_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'PARENT' => array(
				'data_type' => $srcEntity->getDataClass(),
				'reference' => array(
					'=this.VALUE_ID' => 'ref.ID'
				)
			)
		);

		// initialize entity
		if (class_exists($utsNamespace."\\".$utsClass))
		{
			Entity\Base::destroy($utsNamespace."\\".$utsClass);
			$entity = Entity\Base::getInstance($utsNamespace."\\".$utsClass);

			foreach ($fieldsMap as $fieldName => $field)
			{
				$entity->addField($field, $fieldName);
			}
		}
		else
		{
			$entity = Entity\Base::compileEntity($utsClass, $fieldsMap, array(
				'namespace' => $utsNamespace, 'table_name' => $utsTable
			));
		}

		foreach ($utsFields as $utsField)
		{
			$field = $USER_FIELD_MANAGER->getEntityField($utsField);
			$entity->addField($field);

			foreach ($USER_FIELD_MANAGER->getEntityReferences($utsField, $field) as $reference)
			{
				$entity->addField($reference);
			}
		}

		foreach ($utmFields as $utmField)
		{
			// add seriazed utm cache-fields
			$cacheField = new Entity\TextField($utmField['FIELD_NAME']);
			static::setMultipleFieldSerialization($cacheField, $utmField);
			$entity->addField($cacheField);
		}

		return $entity;
	}

	/**
	 * @param Entity\Field       $entityField
	 * @param Entity\Field|array $fieldAsType
	 */
	public static function setMultipleFieldSerialization(Entity\Field $entityField, $fieldAsType)
	{
		global $USER_FIELD_MANAGER;

		if (!($fieldAsType instanceof Entity\Field))
		{
			$fieldAsType = $USER_FIELD_MANAGER->getEntityField($fieldAsType);
		}

		if ($fieldAsType instanceof Entity\DatetimeField)
		{
			$entityField->addSaveDataModifier(array(__CLASS__, 'serializeMultipleDatetime'));
			$entityField->addFetchDataModifier(array(__CLASS__, 'unserializeMultipleDatetime'));
		}
		elseif ($fieldAsType instanceof Entity\DateField)
		{
			$entityField->addSaveDataModifier(array(__CLASS__, 'serializeMultipleDate'));
			$entityField->addFetchDataModifier(array(__CLASS__, 'unserializeMultipleDate'));
		}
		else
		{
			$entityField->setSerialized();
		}
	}

	public static function rewriteUtsReference($referenceDesc)
	{
		$new = array();

		foreach ($referenceDesc as $k => $v)
		{
			if (is_array($v))
			{
				$new[$k] = static::rewriteUtsReference($v);
			}
			else
			{
				$k = str_replace('this.', 'this.UTS_OBJECT.', $k);
				$new[$k] = $v;
			}
		}

		return $new;
	}

	protected static function getUtsEntityClassNameBySrcEntity(Entity\Base $srcEntity)
	{
		return $srcEntity->getFullName().'UtsTable';
	}

	protected static function getUtsEntityTableNameBySrcEntity(Entity\Base $srcEntity)
	{
		return 'b_uts_'.strtolower($srcEntity->getUfId());
	}

	protected static function createUtmEntity(Entity\Base $srcEntity, array $utmFields)
	{
		global $USER_FIELD_MANAGER;

		/** @var Entity\DataManager $utmClassFull */
		$utmClassFull = static::getUtmEntityClassNameBySrcEntity($srcEntity);
		$utmClassPath = explode('\\', ltrim($utmClassFull, '\\'));

		$utmNamespace = join('\\', array_slice($utmClassPath, 0, -1));
		$utmClass = end($utmClassPath);

		// get table name
		$utmTable = static::getUtmEntityTableNameBySrcEntity($srcEntity);

		// collect fields
		$fieldsMap = array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			),
			'VALUE_ID' => array(
				'data_type' => 'integer',
				'primary' => true
			),
			'PARENT' => array(
				'data_type' => $srcEntity->getDataClass(),
				'reference' => array(
					'=this.VALUE_ID' => 'ref.ID'
				)
			),
			'FIELD_ID' => array(
				'data_type' => 'integer'
			),

			// base values fields
			'VALUE' => array(
				'data_type' => 'text'
			),
			'VALUE_INT' => array(
				'data_type' => 'integer'
			),
			'VALUE_DOUBLE' => array(
				'data_type' => 'float'
			),
			'VALUE_DATE' => array(
				'data_type' => 'datetime'
			)
		);

		// initialize entity
		if (class_exists($utmNamespace."\\".$utmClass))
		{
			Entity\Base::destroy($utmNamespace."\\".$utmClass);
			$entity = Entity\Base::getInstance($utmNamespace."\\".$utmClass);

			foreach ($fieldsMap as $fieldName => $field)
			{
				$entity->addField($field, $fieldName);
			}
		}
		else
		{
			$entity = Entity\Base::compileEntity($utmClass, $fieldsMap, array(
				'namespace' => $utmNamespace, 'table_name' => $utmTable
			));
		}

		// add utm fields being mapped on real column name
		foreach ($utmFields as $utmField)
		{
			$field = $USER_FIELD_MANAGER->getEntityField($utmField);

			if ($field instanceof Entity\IntegerField)
			{
				$columnName = 'VALUE_INT';
			}
			elseif ($field instanceof Entity\FloatField)
			{
				$columnName = 'VALUE_DOUBLE';
			}
			elseif ($field instanceof Entity\DateField || $field instanceof Entity\DatetimeField)
			{
				$columnName = 'VALUE_DATE';
			}
			else
			{
				$columnName = 'VALUE';
			}

			$field->setColumnName($columnName);

			$entity->addField($field);

			foreach ($USER_FIELD_MANAGER->getEntityReferences($utmField, $field) as $reference)
			{
				$entity->addField($reference);
			}

			// add back-reference
			$refField = new Entity\ReferenceField(
				'PARENT_'.$utmField['FIELD_NAME'],
				$srcEntity->getDataClass(),
				array('=this.VALUE_ID' => 'ref.ID', '=this.FIELD_ID' => array('?i', $utmField['ID']))
			);

			$entity->addField($refField);
		}

		return $entity;
	}

	protected static function getUtmEntityClassNameBySrcEntity(Entity\Base $srcEntity)
	{
		return $srcEntity->getFullName().'UtmTable';
	}

	protected static function getUtmEntityTableNameBySrcEntity(Entity\Base $srcEntity)
	{
		return 'b_utm_'.strtolower($srcEntity->getUfId());
	}

	/**
	 * @param Type\DateTime[] $value
	 *
	 * @return string
	 */
	public static function serializeMultipleDatetime($value)
	{
		if (is_array($value) || $value instanceof \Traversable)
		{
			$tmpValue = array();

			foreach ($value as $k => $singleValue)
			{
				/** @var Type\DateTime $singleValue */
				$tmpValue[$k] = $singleValue->format(static::MULTIPLE_DATETIME_FORMAT);
			}

			return serialize($tmpValue);
		}

		return $value;
	}

	/**
	 * @param string $value
	 *
	 * @return array
	 */
	public static function unserializeMultipleDatetime($value)
	{
		if (strlen($value))
		{
			$value = unserialize($value);

			foreach ($value as &$singleValue)
			{
				try
				{
					//try new independent datetime format
					$singleValue = new Type\DateTime($singleValue, static::MULTIPLE_DATETIME_FORMAT);
				}
				catch (ObjectException $e)
				{
					//try site format
					$singleValue = new Type\DateTime($singleValue);
				}
			}
		}

		return $value;
	}

	/**
	 * @param Type\Date[] $value
	 *
	 * @return string
	 */
	public static function serializeMultipleDate($value)
	{
		if (is_array($value) || $value instanceof \Traversable)
		{
			$tmpValue = array();

			foreach ($value as $k => $singleValue)
			{
				/** @var Type\Date $singleValue */
				$tmpValue[$k] = $singleValue->format(static::MULTIPLE_DATE_FORMAT);
			}

			return serialize($tmpValue);
		}

		return $value;
	}

	/**
	 * @param string $value
	 *
	 * @return array
	 */
	public static function unserializeMultipleDate($value)
	{
		if (strlen($value))
		{
			$value = unserialize($value);

			foreach ($value as &$singleValue)
			{
				try
				{
					//try new independent datetime format
					$singleValue = new Type\Date($singleValue, static::MULTIPLE_DATE_FORMAT);
				}
				catch (ObjectException $e)
				{
					//try site format
					$singleValue = new Type\Date($singleValue);
				}
			}
		}

		return $value;
	}
}
