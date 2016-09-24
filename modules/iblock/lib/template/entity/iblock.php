<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\Template\Entity;

class Iblock extends Base
{
	protected $catalog = null;

	/**
	 * @param integer $id Iblock identifier.
	 */
	public function __construct($id)
	{
		parent::__construct($id);
		$this->fieldMap = array(
			"name" => "NAME",
			"previewtext" => "DESCRIPTION",
			"detailtext" => "DESCRIPTION",
			"code" => "CODE",
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
	* <p>Метод используется для поиска инфоблока для обработки шаблона. Нестатический метод.</p>
	*
	*
	* @param string $entity  Инфоблок, который необходимо найти.
	*
	* @return \Bitrix\Iblock\Template\Entity\Base 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/iblock/template/entity/iblock/resolve.php
	* @author Bitrix
	*/
	public function resolve($entity)
	{
		if ($entity === "catalog")
		{
			if (!$this->catalog && $this->loadFromDatabase())
			{
				if (\Bitrix\Main\Loader::includeModule('catalog'))
					$this->catalog = new ElementCatalog(0);
			}

			if ($this->catalog)
				return $this->catalog;
		}
		return parent::resolve($entity);
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
			$elementList = \Bitrix\Iblock\IblockTable::getList(array(
				"select" => array_values($this->fieldMap),
				"filter" => array("=ID" => $this->id),
			));
			$this->fields = $elementList->fetch();
		}
		return is_array($this->fields);
	}
}
