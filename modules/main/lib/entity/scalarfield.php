<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Entity;
use Bitrix\Main\DB\SqlExpression;

/**
 * Scalar entity field class for non-array and non-object data types
 * @package bitrix
 * @subpackage main
 */
abstract class ScalarField extends Field
{
	protected $is_primary;

	protected $is_unique;

	protected $is_required;

	protected $is_autocomplete;

	protected $column_name = '';

	/** @var null|callable|mixed  */
	protected $default_value;

	public function __construct($name, $parameters = array())
	{
		parent::__construct($name, $parameters);

		$this->is_primary = (isset($parameters['primary']) && $parameters['primary']);
		$this->is_unique = (isset($parameters['unique']) && $parameters['unique']);
		$this->is_required = (isset($parameters['required']) && $parameters['required']);
		$this->is_autocomplete = (isset($parameters['autocomplete']) && $parameters['autocomplete']);

		$this->column_name = isset($parameters['column_name']) ? $parameters['column_name'] : $this->name;
		$this->default_value = isset($parameters['default_value']) ? $parameters['default_value'] : null;
	}

	public function isPrimary()
	{
		return $this->is_primary;
	}

	public function isRequired()
	{
		return $this->is_required;
	}

	public function isUnique()
	{
		return $this->is_unique;
	}

	public function isAutocomplete()
	{
		return $this->is_autocomplete;
	}

	public function getColumnName()
	{
		return $this->column_name;
	}

	/**
	 * @param string $column_name
	 */
	public function setColumnName($column_name)
	{
		$this->column_name = $column_name;
	}

	static public function isValueEmpty($value)
	{
		if ($value instanceof SqlExpression)
		{
			$value = $value->compile();
		}

		return (strval($value) === '');
	}

	/**
	 * @return callable|mixed|null
	 */
	public function getDefaultValue()
	{
		if (is_callable($this->default_value))
		{
			return call_user_func($this->default_value);
		}
		else
		{
			return $this->default_value;
		}
	}
}
