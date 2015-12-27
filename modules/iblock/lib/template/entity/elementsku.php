<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementSku extends Base
{
	protected $elementFields = null;
	protected $skuIblockId = null;
	protected $skuList = null;
	protected $property = null;
	protected $price = null;
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
				if ($this->skuIblockId)
				{
					$this->property = new ElementSkuProperty($this->fields["ID"]);
					$this->property->setIblockId($this->skuIblockId);
				}
			}

			if ($this->property)
				return $this->property;
		}
		elseif ($entity === "price")
		{
			if (!$this->price && $this->loadFromDatabase())
			{
				if ($this->skuIblockId)
				{
					$this->price = new ElementSkuPrice($this->fields["ID"]);
				}
			}

			if ($this->price)
				return $this->price;
		}
		return parent::resolve($entity);
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
			$this->fields = array();
			$select = array_values($this->fieldMap);

			$elementList = \Bitrix\Iblock\ElementTable::getList(array(
				"select" => $select,
				"filter" => array("=ID" => $this->id),
			));
			$this->elementFields = $elementList->fetch();
			if ($this->elementFields)
			{
				$catalog = \CCatalogSKU::getInfoByProductIBlock($this->elementFields["IBLOCK_ID"]);
				if (!empty($catalog))
				{
					$this->skuIblockId = $catalog["IBLOCK_ID"];
					$skuList = \CIBlockElement::getList(array(), array(
						"IBLOCK_ID" => $catalog["IBLOCK_ID"],
						"=PROPERTY_".$catalog["SKU_PROPERTY_ID"] => $this->id,
					), false, false, $select);
					while ($sku = $skuList->fetch())
					{
						$this->skuList[] = $sku;
						foreach($sku as $fieldName => $fieldValue)
						{
							$this->fields[$fieldName][] = $fieldValue;
						}
					}
				}
			}
		}
		return is_array($this->fields);
	}
}
