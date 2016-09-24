<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\DB\ArrayResult;
use Bitrix\Main\DB\Result;

class ConnectorResult
{
	/** @var \Bitrix\Main\DB\Result $resource */
	public $resource;

	/** @var \CDBResult $resourceCDBResult */
	public $resourceCDBResult;

	/** fields filter */
	protected $fields;

	/** additional fields */
	protected $additionalFields;

	/** disallowed fields */
	protected $fieldsDisallowed = array('EMAIL', 'NAME', 'USER_ID');

	/**
	 * @param Array|\CDBResult|\Bitrix\Main\DB\Result $resource
	 * @param Array|null $fields
	 */
	public function __construct($resource)
	{
		if(is_array($resource))
		{
			$isSingleArray = false;
			$arrayKeyList = array_keys($resource);
			foreach($arrayKeyList as $key)
			{
				if(is_string($key))
				{
					$isSingleArray = true;
					break;
				}
			}

			if($isSingleArray)
			{
				$resource = array($resource);
			}

			$this->resource = new ArrayResult($resource);
		}
		elseif($resource instanceof Result)
		{
			$this->resource = $resource;
		}
		elseif($resource instanceof \CDBResult)
		{
			$this->resourceCDBResult = $resource;
		}

	}

	/**
	 * @param Array $fields
	 */
	public function setFilterFields(array $fields)
	{
		$this->fields = $fields;
	}

	/**
	 * @return array
	 */
	public function getFilterFields()
	{
		return $this->fields;
	}

	/**
	 * @return self
	 */
	public function setAdditionalFields(array $additionalFields)
	{
		$this->additionalFields = $additionalFields;
	}

	/**
	 * @return Array|null
	 */
	public function fetch()
	{
		$result = null;
		if($this->resource)
		{
			$result = $this->resource->fetch();
		}
		elseif($this->resourceCDBResult)
		{
			$result = $this->resourceCDBResult->Fetch();
		}

		if($result)
		{
			$result = $this->fetchModifierFields($result);
		}

		return ($result && count($result) > 0) ? $result : null;
	}

	protected function fetchModifierFields(array $result)
	{
		$fieldsList = array();
		foreach($result as $key => $value)
		{
			if(is_object($value))
			{
				$value = (string) $value;
				$result[$key] = $value;
			}

			if(in_array($key, $this->fieldsDisallowed))
			{
				continue;
			}

			if($this->fields && in_array($key, $this->fields))
			{
				$fieldsList[$key] = $value;
			}

			unset($result[$key]);
		}

		if($this->additionalFields)
		{
			$fieldsList = $fieldsList + $this->additionalFields;
		}

		if(count($fieldsList) > 0)
		{
			$result['FIELDS'] = $fieldsList;
		}

		return $result;
	}

	/** @return Int */
	public function getSelectedRowsCount()
	{
		if($this->resource)
		{
			return $this->resource->getSelectedRowsCount();
		}
		elseif($this->resourceCDBResult)
		{
			if(!($this->resourceCDBResult instanceof \CDBResultMysql))
			{
				$this->resourceCDBResult->NavStart(0);
			}

			return $this->resourceCDBResult->SelectedRowsCount();
		}

		return 0;
	}
}