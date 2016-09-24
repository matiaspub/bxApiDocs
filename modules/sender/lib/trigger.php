<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;


abstract class Trigger
{
	protected $fieldPrefix;
	protected $fieldValues;
	protected $fieldFormName;
	protected $moduleId;
	protected $siteId;

	protected $fields;
	protected $params;
	protected $isRunForOldData = false;
	protected $recipient;


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
	 * @param string $siteId
	 * @return void
	 */
	public function setSiteId($siteId)
	{
		$this->siteId = $siteId;
	}

	/**
	 * @return string
	 */
	public function getSiteId()
	{
		return $this->siteId;
	}

	/** @return string */
	public function getId()
	{
		return $this->getModuleId().'_'.$this->getCode();
	}

	/** @return bool */
	static public function requireConfigure()
	{
		return false;
	}

	/** @return bool */
	public static function isClosed()
	{
		return false;
	}

	/** @return bool */
	public static function canBeTarget()
	{
		return true;
	}

	/** @return bool */
	public static function canRunForOldData()
	{
		return false;
	}

	/** @return bool */
	public function setRunForOldData($isRunForOldData)
	{
		if(!static::canRunForOldData())
		{
			$this->isRunForOldData = false;
		}

		$this->isRunForOldData = (bool) $isRunForOldData;
	}

	/** @return bool */
	public function isRunForOldData()
	{
		if(!$this->canRunForOldData())
		{
			return false;
		}

		return $this->isRunForOldData;
	}

	/**
	 * @param array $fieldValues
	 * @return void
	 */
	public function setFields(array $fieldValues = null)
	{
		$this->fields = $fieldValues;
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		if(is_array($this->fields))
			return $this->fields;
		else
			return array();
	}

	/** @return mixed */
	public function getFieldValue($name, $defaultValue = null)
	{
		if($this->fields && array_key_exists($name, $this->fields))
			return $this->fields[$name];
		else
			return $defaultValue;
	}

	/**
	 * @param array $fieldValues
	 * @return void
	 */
	public function setParams(array $fieldValues = null)
	{
		$this->params = $fieldValues;
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		if(is_array($this->params))
			return $this->params;
		else
			return array();
	}

	/** @return mixed */
	public function getParam($name, $defaultValue = null)
	{
		if($this->params && array_key_exists($name, $this->params))
			return $this->params[$name];
		else
			return $defaultValue;
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

	/** @return string */
	public function getFieldId($id)
	{
		$fieldPrefix = $this->getFieldPrefix();
		if($fieldPrefix)
		{
			return $fieldPrefix . '_' . $id;
		}
		else
			return $id;
	}

	/** @return string */
	public function getFieldName($name)
	{
		$fieldPrefix = $this->getFieldPrefix();
		if($fieldPrefix)
		{
			$returnName = array();
			$returnName[] = $fieldPrefix;
			$nameParsed = explode('[', $name);
			$returnName[] = '['.$nameParsed[0].']';
			if(count($nameParsed) > 1)
			{
				unset($nameParsed[0]);
				$returnName[] = '['.implode('[', $nameParsed);
			}

			return implode('', $returnName);
		}
		else
			return $name;
	}

	/**
	 * @return string
	 */
	public function getFullEventType()
	{
		return $this->getEventModuleId() .'/'. $this->getEventType();
	}


	/** @return array */
	static public function getMailEventToPrevent()
	{
		return array(
			'EVENT_NAME' => '',
			'FILTER' => array()
		);
	}

	/** @return bool */
	static public function filter()
	{
		return true;
	}

	/**
	 * @return string
	 */
	static public function getForm()
	{
		return '';
	}

	/**
	 * @return \Bitrix\Sender\ConnectorResult
	 */
	public function getRecipientResult()
	{
		$result = new ConnectorResult($this->getRecipient());

		// set fields that will be added in each item of result set
		$personalizeList = array();
		$personalizeListTmp = $this->getPersonalizeList();
		foreach($personalizeListTmp as $tag)
		{
			if(strlen($tag['CODE']) > 0)
			{
				$personalizeList[] = $tag['CODE'];
			}
		}
		$result->setFilterFields($personalizeList);

		// set same fields for all items in each item of result set
		$result->setAdditionalFields($this->getPersonalizeFields());

		return $result;
	}

	/**
	 * @return array
	 */
	static public function getPersonalizeFields()
	{
		return array();
	}

	/**
	 * @return array
	 */
	public static function getPersonalizeList()
	{
		return array();
	}

	/**
	 * @return string
	 */
	public abstract function getName();

	/**
	 * @return string
	 */
	public abstract function getCode();

	/**
	 * @return string
	 */
	public abstract function getEventModuleId();

	/**
	 * @return string
	 */
	public abstract function getEventType();

	/**
	 * @return array|\Bitrix\Main\DB\Result|\CDBResult
	 */
	public abstract function getRecipient();
}