<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class CatalogStore extends Base
{
	/**
	 * @param integer $id Catalog store identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->fieldMap = array(
			"name" => "TITLE",
			//not accessible from template engine
			"ID" => "ID",
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
		if (intval($entity) > 0)
		{
			if (\Bitrix\Main\Loader::includeModule('catalog'))
				return new CatalogStore(intval($entity));
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
			//&& $this->fields["MEASURE"] > 0
		)
		{
			//$this->fields["MEASURE"] = new ElementCatalogMeasure($this->fields["MEASURE"]);
			//TODO
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
		if (!isset($this->fields) && ($this->id > 0))
		{
			$storeList = \CCatalogStore::getList(array(), array(
				"ID" => $this->id,
			), false, false, array("ID", "TITLE"));
			$this->fields = $storeList->fetch();
		}
		return is_array($this->fields);
	}
}
