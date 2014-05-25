<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class Section extends Base
{
	protected $property = null;
	protected $iblock = null;
	protected $parent = null;
	protected $sections = null;
	protected $catalog = null;

	/**
	 * @param integer $id Iblock section identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->fieldMap = array(
			"name" => "NAME",
			"previewtext" => "DESCRIPTION",
			"detailtext" => "DESCRIPTION",
			"code" => "CODE",
			//not accessible from template engine
			"ID" => "ID",
			"IBLOCK_ID" => "IBLOCK_ID",
			"IBLOCK_SECTION_ID" => "IBLOCK_SECTION_ID",
		);
	}

	/**
	 * Used to find entity for template processing.
	 *
	 * @param string $entity What to find.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	public function resolve($entity)
	{
		if ($entity === "property")
		{
			if (!$this->property && $this->loadFromDatabase())
			{
				if ($this->fields["IBLOCK_ID"] > 0)
				{
					$this->property = new SectionProperty($this->id);
					$this->property->setIblockId($this->fields["IBLOCK_ID"]);
				}
			}

			if ($this->property)
				return $this->property;
		}
		elseif ($entity === "iblock")
		{
			if (!$this->iblock && $this->loadFromDatabase())
			{
				if ($this->fields["IBLOCK_ID"] > 0)
					$this->iblock = new Iblock($this->fields["IBLOCK_ID"]);
			}

			if ($this->iblock)
				return $this->iblock;
		}
		elseif ($entity === "parent")
		{
			if (!$this->parent && $this->loadFromDatabase())
			{
				if ($this->fields["IBLOCK_SECTION_ID"] > 0)
					$this->parent = new Section($this->fields["IBLOCK_SECTION_ID"]);
				else
					return $this->resolve("iblock");
			}

			if ($this->parent)
				return $this->parent;
		}
		elseif ($entity === "sections")
		{
			if (!$this->sections && $this->loadFromDatabase())
			{
				if ($this->fields["IBLOCK_SECTION_ID"] > 0)
					$this->sections = new SectionPath($this->fields["IBLOCK_SECTION_ID"]);
			}

			if ($this->sections)
				return $this->sections;
		}
		elseif ($entity === "catalog")
		{
			if (!$this->catalog && $this->loadFromDatabase())
			{
				if (\Bitrix\Main\Loader::includeModule('catalog'))
					$this->catalog = new ElementCatalog(0);
			}

			if ($this->catalog)
				return $this->catalog;
		}
		return parent::resolve($entity);
	}

	/**
	 * Used to initialize entity fields from some external source.
	 *
	 * @param array $fields Entity fields.
	 *
	 * @return void
	 */
	public function setFields(array $fields)
	{
		parent::setFields($fields);
		if (
			is_array($this->fields)
			&& $this->fields["IBLOCK_ID"] > 0
		)
		{
			$properties = array();
			foreach ($this->fields as $id => $value)
			{
				if (substr($id, 0, 3) === "UF_")
					$properties[$id] = $value;
			}
			$this->property = new SectionProperty($this->id);
			$this->property->setIblockId($this->fields["IBLOCK_ID"]);
			$this->property->setFields($properties);

			$this->iblock = new Iblock($fields["IBLOCK_ID"]);

			if (
				isset($fields["IBLOCK_SECTION_ID"])
				&& $fields["IBLOCK_SECTION_ID"] > 0
			)
			{
				$this->parent = new Section($fields["IBLOCK_SECTION_ID"]);
				$this->sections = new SectionPath($fields["IBLOCK_SECTION_ID"]);
			}

			if (\Bitrix\Main\Loader::includeModule('catalog'))
				$this->catalog = new ElementCatalog($this->id);
		}
	}

	/**
	 * Loads values from database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	protected function loadFromDatabase()
	{
		if (!isset($this->fields))
		{
			$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
				"select" => array_values($this->fieldMap),
				"filter" => array("=ID" => $this->id),
			));
			$this->fields = $sectionList->fetch();
		}
		return is_array($this->fields);
	}
}
