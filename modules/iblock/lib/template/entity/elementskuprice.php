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
	static public function __construct($id)
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
	
	/**
	* <p>Используется для инициализации полей сущности из некоторого внешнего источника. Нестатический метод.</p>
	*
	*
	* @param array $fields  Массив полей сущности.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/elementskuprice/setfields.php
	* @author Bitrix
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
				$priceId = $priceInfo["CATALOG_GROUP_ID"];
				$price = \CCurrencyLang::currencyFormat($priceInfo["PRICE"], $priceInfo["CURRENCY"], true);
				$this->fields[$priceId][] = $price;
				$this->addField($priceId, $priceId, $price);
				$this->addField($priceInfo["CATALOG_GROUP_CODE"], $priceId, $price);
			}
		}
		return is_array($this->fields);
	}
}
