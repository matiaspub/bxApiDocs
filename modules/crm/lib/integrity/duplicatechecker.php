<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
//IncludeModuleLangFile(__FILE__);
abstract class DuplicateChecker
{
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected function __construct($entityTypeID)
	{
		if(!is_int($entityTypeID))
		{
			throw new Main\ArgumentTypeException('entityTypeID', 'integer');
		}

		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\NotSupportedException("Entity ID: '{$entityTypeID}' is not supported in current context");
		}
		$this->entityTypeID = $entityTypeID;
	}
	public function getEntityID()
	{
		return $this->entityTypeID;
	}
	abstract public function findDuplicates(\Bitrix\Crm\EntityAdapter $adapter, DuplicateSearchParams $params);
	public function findMultifieldDuplicates($type, \Bitrix\Crm\EntityAdapter $adapter, DuplicateSearchParams $params)
	{
		if(!is_string($type))
		{
			throw new Main\ArgumentTypeException('type', 'string');
		}

		if($type !== 'EMAIL' && $type !== 'PHONE')
		{
			throw new Main\NotSupportedException("Type: '{$type}' is not supported in current context");
		}

		$allMultiFields =  $adapter->getFieldValue('FM');
		$multiFields = is_array($allMultiFields) && isset($allMultiFields[$type]) ? $allMultiFields[$type] : null;
		if(!is_array($multiFields) || empty($multiFields))
		{
			return array();
		}

		$criterions = array();
		$dups = array();
		foreach($multiFields as &$multiField)
		{
			$value = isset($multiField['VALUE']) ? $multiField['VALUE'] : '';
			if($value === '')
			{
				continue;
			}

			$criterion = new DuplicateCommunicationCriterion($type, $value);
			$isExists = false;
			foreach($criterions as $curCriterion)
			{
				/** @var DuplicateCriterion $curCriterion */
				if($criterion->equals($curCriterion))
				{
					$isExists = true;
					break;
				}
			}

			if($isExists)
			{
				continue;
			}
			$criterions[] = $criterion;
			$duplicate = $criterion->find();
			if($duplicate !== null)
			{
				$dups[] = $duplicate;
			}
		}
		unset($multiField);
		return $dups;
	}
}