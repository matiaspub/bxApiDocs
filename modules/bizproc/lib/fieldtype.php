<?php
namespace Bitrix\Bizproc;

use Bitrix\Bizproc\BaseType\Base;
use Bitrix\Main;

/**
 * Class FieldType
 * @package Bitrix\Bizproc
 */
class FieldType
{

	/**
	 * Base type BOOL
	 */
	const BOOL = 'bool';

	/**
	 * Base type DATE
	 */
	const DATE = 'date';

	/**
	 * Base type DATETIME
	 */
	const DATETIME = 'datetime';

	/**
	 * Base type DOUBLE
	 */
	const DOUBLE = 'double';

	/**
	 * Base type FILE
	 */
	const FILE = 'file';

	/**
	 * Base type INT
	 */
	const INT = 'int';

	/**
	 * Base type SELECT
	 */
	const SELECT = 'select';

	/**
	 * Base type INTERNALSELECT
	 */
	const INTERNALSELECT = 'internalselect';

	/**
	 * Base type STRING
	 */
	const STRING = 'string';

	/**
	 * Base type TEXT
	 */
	const TEXT = 'text';

	/**
	 * Base type USER
	 */
	const USER = 'user';

	/**
	 * Control render mode - Bizproc Designer
	 */
	const RENDER_MODE_DESIGNER = 1;

	/**
	 * Control render mode - Admin panel
	 */
	const RENDER_MODE_ADMIN = 2;

	/**
	 * Control render mode - Mobile application
	 */
	const RENDER_MODE_MOBILE = 4;

	/** @var \Bitrix\Bizproc\BaseType\Base $typeClass */
	protected $typeClass;

	/** @var array $property */
	protected $property;

	/** @var array $documentType */
	protected $documentType;

	/** @var array $documentId */
	protected $documentId;

	/**
	 * @param array $property Document property.
	 * @param array $documentType Document type.
	 * @param string $typeClass Type class manager.
	 * @param null|array $documentId
	 */
	public function __construct(array $property, array $documentType, $typeClass, array $documentId = null)
	{
		$this->property = static::normalizeProperty($property);
		$this->documentType = $documentType;

		$this->typeClass = $typeClass;
		$this->documentId = $documentId;
	}

	/**
	 * @return null|string
	 */
	public function getType()
	{
		return isset($this->property['Type']) ? $this->property['Type'] : null;
	}

	/**
	 * @return string
	 */
	public function getTypeClass()
	{
		return $this->typeClass;
	}

	/**
	 * Set type class manager.
	 * @param string $typeClass Type class name.
	 * @return $this
	 * @throws Main\ArgumentException
	 */
	public function setTypeClass($typeClass)
	{
		if (is_subclass_of($typeClass, '\Bitrix\Bizproc\BaseType\Base'))
		{
			$this->typeClass = $typeClass;
		}
		else
			throw new Main\ArgumentException('Incorrect type class.');

		return $this;
	}

	/**
	 * @return array
	 */
	public function getDocumentType()
	{
		return $this->documentType;
	}

	/**
	 * @param array $documentType Document type.
	 * @return $this
	 */
	public function setDocumentType(array $documentType)
	{
		$this->documentType = $documentType;
		return $this;
	}

	/**
	 * @return array|null
	 */
	public function getDocumentId()
	{
		return $this->documentId;
	}

	/**
	 * @param array $documentId Document id.
	 * @return $this
	 */
	public function setDocumentId(array $documentId)
	{
		$this->documentId = $documentId;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isMultiple()
	{
		return !empty($this->property['Multiple']);
	}

	/**
	 * @param bool $value Multiple flag.
	 * @return $this
	 */
	public function setMultiple($value)
	{
		$this->property['Multiple'] = (bool)$value;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRequired()
	{
		return !empty($this->property['Required']);
	}

	/**
	 * @return null|mixed
	 */
	public function getOptions()
	{
		return isset($this->property['Options']) ? $this->property['Options'] : null;
	}

	/**
	 * @param mixed $options Options data.
	 * @return $this
	 */
	public function setOptions($options)
	{
		$this->property['Options'] = $options;
		return $this;
	}

	/**
	 * @param mixed $value Field value.
	 * @param string $format Format name.
	 * @return mixed|null|string
	 */
	public function formatValue($value, $format = 'printable')
	{
		$typeClass = $this->typeClass;

		if ($this->isMultiple())
		{
			return $typeClass::formatValueMultiple($this, $value, $format);
		}
		else
		{
			return $typeClass::formatValueSingle($this, $value, $format);
		}
	}

	/**
	 * @param mixed $value Field value.
	 * @param string $toTypeClass Type class name.
	 * @return array|bool|float|int|string
	 */
	public function convertValue($value, $toTypeClass)
	{
		$typeClass = $this->typeClass;

		if ($this->isMultiple())
		{
			return $typeClass::convertValueMultiple($this, $value, $toTypeClass);
		}
		else
		{
			return $typeClass::convertValueSingle($this, $value, $toTypeClass);
		}
	}

	/**
	 * @param int $renderMode Control render mode.
	 * @return bool
	 */
	public function canRenderControl($renderMode)
	{
		$typeClass = $this->typeClass;
		return $typeClass::canRenderControl($renderMode);
	}

	/**
	 * @param array $field Form field.
	 * @param mixed $value Field value.
	 * @param bool $allowSelection Allow selection flag.
	 * @param int $renderMode Control render mode.
	 * @return string
	 */
	public function renderControl(array $field, $value, $allowSelection, $renderMode)
	{
		$typeClass = $this->typeClass;

		if ($this->isMultiple())
		{
			return $typeClass::renderControlMultiple($this, $field, $value, $allowSelection, $renderMode);
		}
		else
		{
			return $typeClass::renderControlSingle($this, $field, $value, $allowSelection, $renderMode);
		}
	}

	/**
	 * @param string $callbackFunctionName Client callback function name.
	 * @param mixed $value Field value.
	 * @return string
	 */
	public function renderControlOptions($callbackFunctionName, $value)
	{
		$typeClass = $this->typeClass;
		return $typeClass::renderControlOptions($this, $callbackFunctionName, $value);
	}

	/**
	 * @param array $field Form field.
	 * @param array $request Request data.
	 * @param array &$errors Errors collection.
	 * @return array|null
	 */
	public function extractValue(array $field, array $request, array &$errors = null)
	{
		$typeClass = $this->typeClass;

		if ($this->isMultiple())
		{
			$result = $typeClass::extractValueMultiple($this, $field, $request);
		}
		else
		{
			$result = $typeClass::extractValueSingle($this, $field, $request);
		}
		$errors = $typeClass::getErrors();

		return $result;
	}

	/**
	 * @param mixed $value Field value.
	 * @return void
	 */
	public function clearValue($value)
	{
		$typeClass = $this->typeClass;

		if ($this->isMultiple())
		{
			$typeClass::clearValueMultiple($this, $value);
		}
		else
		{
			$typeClass::clearValueSingle($this, $value);
		}
	}

	/**
	 * Get list of supported base types.
	 * @return array
	 */
	public static function getBaseTypesMap()
	{
		return array(
			static::BOOL => '\Bitrix\Bizproc\BaseType\Bool',
			static::DATE => '\Bitrix\Bizproc\BaseType\Date',
			static::DATETIME => '\Bitrix\Bizproc\BaseType\Datetime',
			static::DOUBLE => '\Bitrix\Bizproc\BaseType\Double',
			static::FILE => '\Bitrix\Bizproc\BaseType\File',
			static::INT => '\Bitrix\Bizproc\BaseType\Int',
			static::SELECT => '\Bitrix\Bizproc\BaseType\Select',
			static::STRING => '\Bitrix\Bizproc\BaseType\String',
			static::TEXT => '\Bitrix\Bizproc\BaseType\Text',
			static::USER => '\Bitrix\Bizproc\BaseType\User',
			static::INTERNALSELECT => '\Bitrix\Bizproc\BaseType\InternalSelect',
		);
	}

	/**
	 * Normalize property structure.
	 * @param string|array $property Document property.
	 * @return array
	 */
	public static function normalizeProperty($property)
	{
		$normalized = array('Type' => null, 'Multiple' => false, 'Required' => false, 'Options' => null);
		if (is_array($property))
		{
			foreach ($property as $key => $val)
			{
				switch (strtoupper($key))
				{
					case 'TYPE':
					case '0':
						$normalized['Type'] = (string) $val;
						break;
					case 'MULTIPLE':
					case '1':
						$normalized['Multiple'] = (!$val || is_int($val) && ($val == 0) || (strtoupper($val) == 'N')) ? false : true;
						break;
					case 'REQUIRED':
					case '2':
						$normalized['Required'] = (!$val || is_int($val) && ($val == 0) || (strtoupper($val) == 'N')) ? false : true;
						break;
					case 'OPTIONS':
					case '3':
						$normalized['Options'] = is_array($val) ? $val : (string)$val;
						break;
				}
			}
		}
		else
			$normalized['Type'] = (string) $property;

		return $normalized;
	}
}