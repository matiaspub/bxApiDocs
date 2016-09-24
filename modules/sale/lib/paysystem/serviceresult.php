<?php

namespace Bitrix\Sale\PaySystem;

use Bitrix\Main\Entity\Result;

/**
 * Class ServiceResult
 * @package Bitrix\Sale\PaySystem
 */
class ServiceResult extends Result
{
	const MONEY_COMING = 'money_coming';
	const MONEY_LEAVING = 'money_leaving';

	private $psData = array();
	private $resultApplied = true;
	private $operationType = null;
	private $template = '';

	/**
	 * @param array $psData
	 */
	public function setPsData($psData)
	{
		$this->psData = $psData;
	}

	/**
	 * @return array
	 */
	public function getPsData()
	{
		return $this->psData;

	}

	/**
	 * @return bool
	 */
	public function isResultApplied()
	{
		return $this->resultApplied;
	}

	/**
	 * @param $operationType
	 */
	public function setOperationType($operationType)
	{
		$this->operationType = $operationType;
	}

	/**
	 * @return null
	 */
	public function getOperationType()
	{
		return $this->operationType;
	}

	/**
	 * @param $resultApplied
	 */
	public function setResultApplied($resultApplied)
	{
		$this->resultApplied = $resultApplied;
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		return $this->template;
	}

	/**
	 * @param string $template
	 */
	public function setTemplate($template)
	{
		$this->template = $template;
	}
}