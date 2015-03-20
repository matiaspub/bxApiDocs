<?php
namespace Bitrix\Crm;
use Bitrix\Main;
class EntityAdapter
{
	protected $sourceEntityTypeID = \CCrmOwnerType::Undefined;
	protected $destinationEntityTypeID = \CCrmOwnerType::Undefined;
	protected $mapper = null;
	protected $nestedAdapter = null;
	protected $fields = array();

	public function __construct($sourceEntityTypeID, $destinationEntityTypeID, Mapper $mapper = null, EntityAdapter $nestedAdapter = null)
	{
		$this->sourceEntityTypeID = $sourceEntityTypeID;
		$this->destinationEntityTypeID = $destinationEntityTypeID;

		$this->setMapper($mapper);

		if($nestedAdapter !== null)
		{
			$this->nestedAdapter = $nestedAdapter;
		}
	}

	public function getSourceEntityTypeID()
	{
		return $this->sourceEntityTypeID;
	}
	public function getDestinationEntityTypeID()
	{
		return $this->destinationEntityTypeID;
	}
	public function getFields()
	{
		return $this->nestedAdapter !== null ? $this->nestedAdapter->getFields() : $this->fields;
	}
	public function setFields(array $fields = null)
	{
		if($this->nestedAdapter !== null)
		{
			$this->nestedAdapter->setFields($fields);
		}
		else
		{
			$this->fields = $fields;
		}
	}
	public function getMapper()
	{
		return $this->mapper;
	}
	public function setMapper(Mapper $mapper = null)
	{
		return $this->mapper = $mapper;
	}
	public function getFieldValue($fieldName, $default = null)
	{
		if(!is_string($fieldName))
		{
			throw new Main\ArgumentTypeException('fieldName', 'string');
		}
		
		if($this->mapper !== null)
		{
			$fieldName = $this->mapper->getMapping($fieldName);
		}

		return $this->nestedAdapter !== null
			? $this->nestedAdapter->getFieldValue($fieldName, $default)
			: (isset($this->fields[$fieldName]) ? $this->fields[$fieldName] : $default);
	}
}