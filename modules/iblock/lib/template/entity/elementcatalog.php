<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementCatalog extends Base
{
	protected $price = null;
	protected $sku = null;
	protected $store = null;

	/**
	 * @param integer $id Catalog product identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->fieldMap = array(
			"weight" => "WEIGHT",
			"measure" => "MEASURE",
			"store" => "STORE",
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
		if ($entity === "price")
		{
			if (!$this->price && $this->loadFromDatabase())
			{
				$this->price = new ElementPrice($this->id);
			}

			if ($this->price)
				return $this->price;
		}
		elseif ($entity === "sku")
		{
			if (!$this->sku && $this->loadFromDatabase())
			{
				$this->sku = new ElementSku($this->id);
			}

			if ($this->sku)
				return $this->sku;
		}
		elseif ($entity === "store")
		{
			if (!$this->store)
			{
				$this->store = new CatalogStore(0);
			}

			if ($this->store)
				return $this->store;
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
			&& $this->fields["MEASURE"] > 0
		)
		{
			$this->fields["MEASURE"] = new ElementCatalogMeasure($this->fields["MEASURE"]);
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
			$this->fields =\CCatalogProduct::getByID($this->id);
			if (is_array($this->fields))
			{
				if ($this->fields["MEASURE"] > 0)
					$this->fields["MEASURE"] = new ElementCatalogMeasure($this->fields["MEASURE"]);
				$this->fields["STORE"] = new ElementCatalogStoreList(0);
			}
			else
			{
				$this->fields["STORE"] = new ElementCatalogStoreList(0);
			}
		}
		return is_array($this->fields);
	}
}

class ElementCatalogMeasure extends LazyValueLoader
{
	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		$measureList = \CCatalogMeasure::getList(array(), array(
			"ID" => $this->key
		), false, false, array("MEASURE_TITLE"));
		$measure = $measureList->fetch();
		if ($measure)
			return $measure['MEASURE_TITLE'];
		else
			return "";
	}
}

class ElementCatalogStoreList extends LazyValueLoader
{
	/**
	 * Actual work method which have to retrieve data from the DB.
	 *
	 * @return mixed
	 */
	protected function load()
	{
		$storeList = \CCatalogStore::getList(array(), array(
			"ACTIVE" => "Y",
		), false, false, array("ID", "TITLE", "ACTIVE"));
		$result = array();
		while($ar = $storeList->fetch())
		{
			$result[] = $ar["TITLE"];
		}
		return $result;
	}
}
