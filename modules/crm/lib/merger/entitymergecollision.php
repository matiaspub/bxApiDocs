<?php
namespace Bitrix\Crm\Merger;
use Bitrix\Main;
use Bitrix\Crm;

class EntityMergeCollision
{
	const NONE = 0;
	const READ_PERMISSION_LACK = 1;
	const UPDATE_PERMISSION_LACK = 2;
	const SEED_EXTERNAL_OWNERSHIP = 4;

	const NONE_NAME = 'NONE';
	const READ_PERMISSION_LACK_NAME = 'READ_PERMISSION_LACK';
	const UPDATE_PERMISSION_LACK_NAME = 'UPDATE_PERMISSION_LACK';
	const SEED_EXTERNAL_OWNERSHIP_NAME = 'SEED_EXTERNAL_OWNERSHIP';

	protected $entityTypeID = \CCrmOwnerType::Undefined;
	protected $seedID = 0;
	protected $targID = 0;
	public function __construct($entityTypeID, $seedID, $targID, $typeID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException('Is not defined', 'entityTypeID');
		}
		$this->entityTypeID = $entityTypeID;

		if(!is_int($seedID))
		{
			$seedID = (int)$seedID;
		}
		if($seedID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'seedID');
		}
		$this->seedID = $seedID;

		if(!is_int($targID))
		{
			$targID = (int)$targID;
		}
		if($targID <= 0)
		{
			throw new Main\ArgumentException('Must be greater than zero', 'targID');
		}
		$this->targID = $targID;

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isTypeDefined($typeID))
		{
			throw new Main\ArgumentException('Is not defined', 'typeID');
		}
		$this->typeID = $typeID;
	}

	public static function isTypeDefined($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		return $typeID === self::NONE
			|| $typeID === self::READ_PERMISSION_LACK
			|| $typeID === self::UPDATE_PERMISSION_LACK
			|| $typeID === self::SEED_EXTERNAL_OWNERSHIP;
	}
	public static function resolveTypeName($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		if($typeID === self::NONE)
		{
			return self::NONE_NAME;
		}
		elseif($typeID === self::READ_PERMISSION_LACK)
		{
			return self::READ_PERMISSION_LACK_NAME;
		}
		elseif($typeID === self::UPDATE_PERMISSION_LACK)
		{
			return self::UPDATE_PERMISSION_LACK_NAME;
		}
		elseif($typeID === self::SEED_EXTERNAL_OWNERSHIP)
		{
			return self::SEED_EXTERNAL_OWNERSHIP_NAME;
		}
		return '';
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	public function getSeedID()
	{
		return $this->seedID;
	}
	public function getTargID()
	{
		return $this->targID;
	}
	public function getTypeID()
	{
		return $this->typeID;
	}
	public function getTypeName()
	{
		return self::resolveTypeName($this->typeID);
	}
}