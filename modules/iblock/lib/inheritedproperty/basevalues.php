<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage iblock
 */
namespace Bitrix\Iblock\InheritedProperty;

abstract class BaseValues
{
	/** @var integer */
	protected $iblockId = null;

	/** @var array[string][string]string */
	protected $values = false;

	/**
	 * @param integer $iblockId Iblock identifier.
	 */
	public function __construct($iblockId)
	{
		$this->iblockId = intval($iblockId);
	}

	/**
	 * Returns the identifier of the iblock of the entity.
	 *
	 * @return integer
	 */
	public function getIblockId()
	{
		return $this->iblockId;
	}

	/**
	 * Returns the table name where values will be stored.
	 *
	 * @return string
	 */
	static public function getValueTableName()
	{
		return "";
	}

	/**
	 * Returns type of the entity which will be stored into DB.
	 *
	 * @return string
	 */
	abstract public function getType();

	/**
	 * Returns unique identifier of the entity
	 *
	 * @return integer
	 */
	abstract public function getId();

	/**
	 * Creates an entity which will be used to process the templates.
	 *
	 * @return \Bitrix\Iblock\Template\Entity\Base
	 */
	abstract public function  createTemplateEntity();

	/**
	 * Returns all the parents of the entity.
	 *
	 * @return array[]\Bitrix\Iblock\InheritedProperty\BaseValues
	 */
	static public function getParents()
	{
		return array();
	}

	/**
	 * Returns it's first parent if exists one.
	 * Otherwise returns null.
	 *
	 * @return \Bitrix\Iblock\InheritedProperty\BaseValues|null
	 */
	public function getParent()
	{
		$parents = $this->getParents();
		if (isset($parents[0]))
			return $parents[0];
		else
			return null;
	}

	/**
	 * Returns all calculated values of inherited properties
	 * for this entity.
	 *
	 * @return array[string]string
	 */
	public function getValues()
	{
		if ($this->values === false)
			$this->values = $this->queryValues();

		$result = array();
		foreach ($this->values as $CODE => $row)
		{
			$result[$CODE] = htmlspecialcharsEx($row["VALUE"]);
		}
		return $result;
	}

	/**
	 * Returns value of the inherited property.
	 * The result is html encoded string.
	 *
	 * @param string $propertyCode Mnemonic code.
	 * @return string
	 */
	public function getValue($propertyCode)
	{
		if ($this->values === false)
			$this->values = $this->queryValues();

		if (isset($this->values[$propertyCode]))
			return htmlspecialcharsEx($this->values[$propertyCode]["VALUE"]);
		else
			return "";
	}

	/**
	 * Queries templates for this entity.
	 * Then processes them in order to get
	 * calculated values.
	 *
	 * @return array[string][string]string
	 */
	public function queryValues()
	{
		$templateInstance = new BaseTemplate($this);
		$templates = $templateInstance->findTemplates();
		foreach ($templates as $CODE => $row)
		{
			$templates[$CODE]["VALUE"] = \Bitrix\Iblock\Template\Engine::process($this->createTemplateEntity(), $row["TEMPLATE"]);
		}
		return $templates;
	}

	/**
	 * Checks if there are some templates exists for this set of values.
	 *
	 * @return boolean
	 */
	static public function hasTemplates()
	{
		$templateInstance = new BaseTemplate($this);
		return $templateInstance->hasTemplates($this);
	}

	/**
	 * Clears entity values DB cache
	 *
	 * @return void
	 */
	abstract function clearValues();

	/**
	 * Must be called on template delete.
	 *
	 * @param integer $ipropertyId Identifier of the inherited property.
	 *
	 * @return void
	 */
	static public function deleteValues($ipropertyId)
	{
		$ipropertyId = intval($ipropertyId);
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->query("
			DELETE FROM b_iblock_iblock_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
		$connection->query("
			DELETE FROM b_iblock_section_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
		$connection->query("
			DELETE FROM b_iblock_element_iprop
			WHERE IPROP_ID = ".$ipropertyId."
		");
	}
}
