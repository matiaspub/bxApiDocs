<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm;
//IncludeModuleLangFile(__FILE__);
class CompanyDuplicateChecker extends DuplicateChecker
{
	protected $useStrictComparison = false;

	public function __construct()
	{
		parent::__construct(\CCrmOwnerType::Company);
	}
	public function findDuplicates(Crm\EntityAdapter $adapter, DuplicateSearchParams $params)
	{
		$result = array();
		$fieldNames = $params->getFieldNames();
		$processAllFields = empty($fieldNames);

		$title = ($processAllFields || in_array('TITLE', $fieldNames, true)) ? $adapter->getFieldValue('TITLE', '') : '';
		if($title !== '')
		{
			$criterion = new DuplicateOrganizationCriterion($title);
			$criterion->setStrictComparison($this->useStrictComparison);

			$duplicate = $criterion->find();
			if($duplicate !== null)
			{
				$result[] = $duplicate;
			}
		}

		if($processAllFields || in_array('FM.PHONE', $fieldNames, true))
		{
			$phones = $this->findMultifieldDuplicates('PHONE', $adapter, $params);
			if(!empty($phones))
			{
				$result = array_merge($result, $phones);
			}
		}
		if($processAllFields || in_array('FM.EMAIL', $fieldNames, true))
		{
			$email = $this->findMultifieldDuplicates('EMAIL', $adapter, $params);
			if(!empty($email))
			{
				$result = array_merge($result, $email);
			}
		}
		return $result;
	}
	public function isStrictComparison()
	{
		return $this->useStrictComparison;
	}
	public function setStrictComparison($useStrictComparison)
	{
		if(!is_bool($useStrictComparison))
		{
			throw new Main\ArgumentTypeException('useStrictComparison', 'boolean');
		}

		$this->useStrictComparison = $useStrictComparison;
	}
}