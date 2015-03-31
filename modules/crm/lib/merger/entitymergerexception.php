<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
class EntityMergerException extends Main\SystemException
{
	const GENERAL = 10;
	const READ_DENIED = 20;
	const UPDATE_DENIED = 30;
	const DELETE_DENIED = 40;
	const NOT_FOUND = 50;
	const UPDATE_FAILED = 60;
	const DELETE_FAILED = 70;

	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $entityID = 0;
	protected $roleID = 0;

	public function __construct($entityTypeID = 0, $entityID = 0, $roleID = 0, $code = 0, $file = '', $line = 0, \Exception $previous = null)
	{
		$this->entityTypeID = $entityTypeID;
		$this->entityID = $entityID;
		$this->roleID = $roleID;

		$name = 'Entity';
		if($this->roleID === EntityMerger::ROLE_SEED)
		{
			$name = 'Seed entity';
		}
		elseif($this->roleID === EntityMerger::ROLE_TARG)
		{
			$name = 'Target entity';
		}

		if($code === self::READ_DENIED)
		{
			$message = "{$name} [{$entityID}] read permission denied";
		}
		elseif($code === self::UPDATE_DENIED)
		{
			$message = "{$name} [{$entityID}] update permission denied";
		}
		elseif($code === self::DELETE_DENIED)
		{
			$message = "{$name} [{$entityID}] delete permission denied";
		}
		elseif($code === self::NOT_FOUND)
		{
			$message = "{$name} [{$entityID}] is not found";
		}
		elseif($code === self::UPDATE_FAILED)
		{
			$message = "{$name} [{$entityID}] update operation failed";
		}
		elseif($code === self::DELETE_FAILED)
		{
			$message = "{$name} [{$entityID}] delete operation failed";
		}
		else
		{
			$message = 'General error';
		}

		parent::__construct($message, $code, $file, $line, $previous);
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function getEntityTypeName()
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeID);
	}
	public function getEntityID()
	{
		return $this->entityID;
	}
	public function getRoleID()
	{
		return $this->roleID;
	}
}