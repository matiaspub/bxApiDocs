<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class SectionPath extends Base
{
	protected $dbPath = null;

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
	 * Loads values from database.
	 * Returns true on success.
	 *
	 * @return boolean
	 */
	public function loadFromDatabase()
	{
		if (!isset($this->fields))
		{
			//From down to up
			$select = array_values($this->fieldMap);
			$this->dbPath = array();
			$id = $this->id;
			while ($id > 0)
			{
				$sectionList = \Bitrix\Iblock\SectionTable::getList(array(
					"select" => $select,
					"filter" => array("=ID" => $id),
				));
				$section = $sectionList->fetch();
				if ($section)
					$this->dbPath[] = $section;
				else
					break;
				$id = $section["IBLOCK_SECTION_ID"];
			}
			//Reversed from up to down
			//and laid by fields
			$this->fields = array();
			for($i = count($this->dbPath)-1; $i >= 0; $i--)
			{
				foreach($this->dbPath[$i] as $fieldName => $fieldValue)
				{
					$this->fields[$fieldName][] = $fieldValue;
				}
			}
			$this->loadProperty();
		}
		return is_array($this->fields);
	}

	/**
	 * Helper method for loading user defined properties from DB.
	 *
	 * @return void
	 */
	protected function loadProperty()
	{
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		foreach ($this->fields["ID"] as $i => $sectionId)
		{
			$userFields = $USER_FIELD_MANAGER->getUserFields(
				"IBLOCK_".$this->fields["IBLOCK_ID"][$i]."_SECTION",
				$sectionId
			);
			foreach ($userFields as $id => $uf)
			{
				//TODO $uf["USER_TYPE"]["BASE_TYPE"] == "enum"
				$propertyCode = $id;
				$fieldCode = "property.".strtolower(substr($id, 3));
				$this->fieldMap[$fieldCode] = $propertyCode;
				if (is_array($uf["VALUE"]))
				{
					foreach ($uf["VALUE"] as $value)
						$this->fields[$propertyCode][] = $value;
				}
				else
				{
					$this->fields[$propertyCode][] = $uf["VALUE"];
				}
			}
		}
	}
}
