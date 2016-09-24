<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class DedupeParams
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $userID = 0;
	protected $enablePermissionCheck = false;

	public function __construct($entityTypeID, $userID, $enablePermissionCheck = false)
	{
		$this->setEntityTypeID($entityTypeID);
		$this->setUserID($userID);
		$this->enabledPermissionCheck($enablePermissionCheck);
	}

	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function setEntityTypeID($entityTypeID)
	{
		if(!is_integer($entityTypeID))
		{
			$entityTypeID = intval($entityTypeID);
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			$entityTypeID = \CCrmOwnerType::Undefined;
		}

		if($this->entityTypeID === $entityTypeID)
		{
			return;
		}

		$this->entityTypeID = $entityTypeID;
	}
	public function getUserID()
	{
		return $this->userID;
	}
	public function setUserID($userID)
	{
		if(!is_integer($userID))
		{
			$userID = intval($userID);
		}
		$userID = max($userID, 0);

		if($this->userID === $userID)
		{
			return;
		}

		$this->userID = $userID;
	}
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	public function enabledPermissionCheck($enable)
	{
		$this->enablePermissionCheck = is_bool($enable) ? $enable : (bool)$enable;
	}
}