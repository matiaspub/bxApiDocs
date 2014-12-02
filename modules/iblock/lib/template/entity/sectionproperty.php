<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class SectionProperty extends Base
{
	protected $iblockId = 0;

	/**
	 * @param integer $id Iblock section identifier.
	 */
	static public function __construct($id)
	{
		parent::__construct($id);
	}

	/**
	 * Set the iblock of the section.
	 *
	 * @param integer $iblockId Iblock identifier.
	 *
	 * @return void
	 */
	public function setIblockId($iblockId)
	{
		$this->iblockId = intval($iblockId);
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
			&& $this->iblockId > 0
		)
		{
			foreach ($this->fields as $id => $value)
			{
				if (substr($id, 0, 3) === "UF_")
				{
					$propertyCode = $id;
					$fieldCode = strtolower(substr($id, 3));
					$this->fieldMap[$fieldCode] = $propertyCode;
				}
			}
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
		/** @global \CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		if (!isset($this->fields) && $this->iblockId > 0)
		{
			$userFields = $USER_FIELD_MANAGER->getUserFields(
				"IBLOCK_".$this->iblockId."_SECTION",
				$this->id
			);
			foreach ($userFields as $id => $uf)
			{
				$this->addField(substr($id, 3), $id, $uf["VALUE"]);
			}
		}
		return is_array($this->fields);
	}
}
