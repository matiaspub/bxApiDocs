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
	
	/**
	* <p>Метод используется для поиска склада для обработки шаблона. Нестатический метод.</p>
	*
	*
	* @param string $entity  Склад для поиска.
	*
	* @return \Bitrix\Iblock\Template\Entity\Base 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/catalogstore/resolve.php
	* @author Bitrix
	*/
	static public function resolve($entity)
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
	
	/**
	* <p>Используется для инициализации полей сущности из некоторого внешнего источника. Нестатический метод.</p>
	*
	*
	* @param array $fields  Массив, содержащий поля сущности.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/catalogstore/setfields.php
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
