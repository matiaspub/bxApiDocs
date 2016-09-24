<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;

/**
 * Entity field class for user field
 * @deprecated
 * @package bitrix
 * @subpackage main
 */
class UField extends Field
{
	protected
		$is_multiple,
		$type_id,
		$base_type,
		$field_id;

	public function __construct(array $info)
	{
		global $USER_FIELD_MANAGER;

		$user_type = $USER_FIELD_MANAGER->getUserType($info['USER_TYPE_ID']);

		$this->base_type = $user_type['BASE_TYPE'];

		if (in_array($this->base_type, array('int', 'enum', 'file'), true))
		{
			$data_type = 'integer';
		}
		elseif ($this->base_type == 'double')
		{
			$data_type = 'float';
		}
		elseif ($this->base_type == 'string')
		{
			$data_type = 'string';
		}
		elseif (in_array($this->base_type, array('date', 'datetime'), true))
		{
			$data_type = 'datetime';
		}
		else
		{
			$data_type = 'string';
		}

		parent::__construct($info['FIELD_NAME'], $info);

		$this->dataType = $data_type;

		$this->is_multiple = $info['MULTIPLE'] === 'Y';
		$this->type_id = $info['USER_TYPE_ID'];
		$this->field_id = $info['ID'];
	}

	static public function validateValue($value, $primary, $row, Result $result)
	{
		return true;
	}

	public function getTypeId()
	{
		return $this->type_id;
	}

	public function isMultiple()
	{
		return $this->is_multiple;
	}

	public function getBaseType()
	{
		return $this->base_type;
	}

	public function getFieldId()
	{
		return $this->field_id;
	}

	public function getValueFieldName()
	{
		if ($this->isMultiple())
		{
			$utm_fname = 'VALUE';

			if ($this->getDataType() == 'integer')
			{
				$utm_fname .= '_INT';
			}
			elseif ($this->getDataType() == 'float')
			{
				$utm_fname .= '_DOUBLE';
			}
			elseif ($this->getDataType() == 'datetime')
			{
				$utm_fname .= '_DATE';
			}

			return $utm_fname;
		}
		else
		{
			return $this->getName();
		}
	}

	//public static function getDataTypeByBaseType($baseType)
	public static function convertBaseTypeToDataType($baseType)
	{
		if (in_array($baseType, array('int', 'enum', 'file'), true))
		{
			$data_type = 'integer';
		}
		elseif ($baseType == 'double')
		{
			$data_type = 'float';
		}
		elseif ($baseType == 'string')
		{
			$data_type = 'string';
		}
		elseif (in_array($baseType, array('date', 'datetime'), true))
		{
			$data_type = 'datetime';
		}
		else
		{
			$data_type = 'string';
		}

		return $data_type;
	}

	public function getColumnName()
	{
		return $this->name;
	}
}


