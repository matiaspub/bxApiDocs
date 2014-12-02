<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class Element extends Base
{
	protected $property = null;
	protected $iblock = null;
	protected $parent = null;
	protected $sections = null;
	protected $catalog = null;
	/**
	 * @param integer $id Iblock element identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->fieldMap = array(
			"name" => "NAME",
			"previewtext" => "PREVIEW_TEXT",
			"detailtext" => "DETAIL_TEXT",
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
					$this->property = new ElementProperty($this->id);
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
					$this->catalog = new ElementCatalog($this->id);
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
			if (
				isset($fields["PROPERTY_VALUES"])
				&& is_array($fields["PROPERTY_VALUES"])
			)
			{
				$this->property = new ElementProperty($this->id);
				$this->property->setIblockId($this->fields["IBLOCK_ID"]);
				$this->property->setFields($fields["PROPERTY_VALUES"]);
			}

			$this->iblock = new Iblock($fields["IBLOCK_ID"]);

			if (
				isset($fields["IBLOCK_SECTION_ID"])
				&& $fields["IBLOCK_SECTION_ID"] > 0
			)
			{
				$this->parent = new Section($fields["IBLOCK_SECTION_ID"]);
				$this->sections = new SectionPath($fields["IBLOCK_SECTION_ID"]);
			}
			elseif (
				isset($fields["IBLOCK_SECTION"])
				&& is_array($fields["IBLOCK_SECTION"])
			)
			{
				$section = -1;
				foreach ($fields["IBLOCK_SECTION"] as $sectionId)
				{
					if ($sectionId > 0)
					{
						if ($section < 0 || $section > $sectionId)
							$section = $sectionId;
					}
				}

				if ($section > 0)
				{
					$this->parent = new Section($section);
					$this->sections = new SectionPath($section);
				}
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
			//Element fields
			$elementList = \Bitrix\Iblock\ElementTable::getList(array(
				"select" => array_values($this->fieldMap),
				"filter" => array("=ID" => $this->id),
			));
			$this->fields = $elementList->fetch();
		}
		return is_array($this->fields);
	}
}
