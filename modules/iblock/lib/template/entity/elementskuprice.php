<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementSkuPrice extends Base
{
	/**
	 * @param integer $id Catalog product identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
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
		if (!isset($this->fields))
		{
			$this->fields = array();
			$pricesList =\CPrice::getListEx(array(), array(
				"=PRODUCT_ID" => $this->id,
				"+<=QUANTITY_FROM" => 1,
				"+>=QUANTITY_TO" => 1,
			), false, false, array("PRICE", "CURRENCY", "CATALOG_GROUP_ID", "CATALOG_GROUP_CODE"));
			$this->fields = array();
			while ($priceInfo = $pricesList->fetch())
			{
				$price_id = $priceInfo["CATALOG_GROUP_ID"];
				$price = \FormatCurrency($priceInfo["PRICE"], $priceInfo["CURRENCY"]);
				$this->fields[$price_id][] = $price;
				$this->addField($price_id, $price_id, $price);
				$this->addField($priceInfo["CATALOG_GROUP_CODE"], $price_id, $price);
			}
		}
		return is_array($this->fields);
	}
}
