<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;


abstract class Connector
{
	protected $fieldPrefix;
	protected $fieldPrefixExtended;
	protected $fieldValues;
	protected $fieldFormName;
	protected $moduleId;

	/**
	 * @param string $moduleId
	 * @return void
	 */
	public function setModuleId($moduleId)
	{
		$this->moduleId = $moduleId;
	}

	/**
	 * @return mixed
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * @param string $fieldFormName
	 * @return void
	 */
	public function setFieldFormName($fieldFormName)
	{
		$this->fieldFormName = $fieldFormName;
	}
	/** @return string */
	public function getFieldFormName()
	{
		return $this->fieldFormName;
	}

	/**
	 * @param string $fieldPrefix
	 * @return void
	 */
	public function setFieldPrefix($fieldPrefix)
	{
		$this->fieldPrefix = $fieldPrefix;
	}
	/** @return string */
	public function getFieldPrefix()
	{
		return $this->fieldPrefix;
	}

	/**
	 * @param string $fieldPrefixExtended
	 * @return void
	 */
	public function setFieldPrefixExtended($fieldPrefixExtended)
	{
		$this->fieldPrefixExtended = $fieldPrefixExtended;
	}
	/** @return string */
	public function getFieldPrefixExtended()
	{
		return $this->fieldPrefixExtended;
	}

	/**
	 * @param array $fieldValues
	 * @return void
	 */
	public function setFieldValues(array $fieldValues = null)
	{
		$this->fieldValues = $fieldValues;
	}

	/**
	 * @return array $fieldValues
	 */
	public function getFieldValues()
	{
		if(is_array($this->fieldValues))
			return $this->fieldValues;
		else
			return array();
	}

	/** @return string */
	public function getFieldId($id)
	{
		$fieldPrefix = $this->getFieldPrefix();
		$fieldPrefixExtended = $this->getFieldPrefixExtended();
		if($fieldPrefix)
		{
			$moduleId = str_replace('.', '_', $this->getModuleId());
			return $fieldPrefix . '_' . $moduleId . '_' . $this->getCode() . '_%CONNECTOR_NUM%_' . $id;
		}
		elseif($fieldPrefixExtended)
		{
			return str_replace(array('][', '[', ']'), array('_', '', ''), $fieldPrefixExtended) .'_'. $id;
		}
		else
			return $id;
	}

	/** @return string */
	public function getFieldName($name)
	{
		$fieldPrefix = $this->getFieldPrefix();
		$fieldPrefixExtended = $this->getFieldPrefixExtended();
		if($fieldPrefix || $fieldPrefixExtended)
		{
			$arReturnName = array();
			if($fieldPrefix)
				$arReturnName[] = $fieldPrefix.'['.$this->getModuleId().']['.$this->getCode().'][%CONNECTOR_NUM%]';
			else
				$arReturnName[] = $fieldPrefixExtended;

			$arName = explode('[', $name);
			$arReturnName[] = '['.$arName[0].']';
			if(count($arName)>1)
			{
				unset($arName[0]);
				$arReturnName[] = '['.implode('[', $arName);
			}

			return implode('', $arReturnName);
		}
		else
			return $name;
	}

	/** @return mixed */
	public function getFieldValue($name, $defaultValue = null)
	{
		if($this->fieldValues && array_key_exists($name, $this->fieldValues))
			return $this->fieldValues[$name];
		else
			return $defaultValue;
	}

	/** @return string */
	public function getId()
	{
		return $this->getModuleId().'_'.$this->getCode();
	}

	/** @return integer */
	public function getDataCount()
	{
		return $this->getResult()->getSelectedRowsCount();
	}

	public final function getResult()
	{
		$personalizeList = array();
		$personalizeListTmp = $this->getPersonalizeList();
		foreach($personalizeListTmp as $tag)
		{
			if(strlen($tag['CODE']) > 0)
			{
				$personalizeList[] = $tag['CODE'];
			}
		}

		$result = new ConnectorResult($this->getData());
		$result->setFilterFields($personalizeList);

		return $result;
	}

	/** @return bool */
	static public function requireConfigure()
	{
		return false;
	}

	/**
	 * @return array
	 */
	public static function getPersonalizeList()
	{
		return array();
	}

	/** @return string */
	public abstract function getName();

	/** @return string */
	public abstract function getCode();

	/** @return \CDBResult */
	public abstract function getData();

	/** @return string */
	public abstract function getForm();
}