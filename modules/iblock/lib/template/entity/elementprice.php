<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class ElementPrice extends Base
{
	/**
	 * @param integer $id Catalog product identifier.
	 */
	static public function __construct($id)
	{
		parent::__construct($id);
	}

	/**
	 * Used to find entity for template processing.
	 *
	 * @param string $entity What to find.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	
	/**
	* <p>Метод используется для поиска сущности для обработки шаблона. Нестатический метод.</p>
	*
	*
	* @param string $entity  Сущность, которую необходимо найти.
	*
	* @return \Bitrix\Iblock\Template\Entity\Base 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/elementprice/resolve.php
	* @author Bitrix
	*/
	static public function resolve($entity)
	{
		return parent::resolve($entity);
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
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/elementprice/setfields.php
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
				$this->addField($priceId, $priceId, $price);
				$this->addField($priceInfo["CATALOG_GROUP_CODE"], $priceId, $price);
			}
		}
		return is_array($this->fields);
	}
}
