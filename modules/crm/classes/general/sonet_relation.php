<?php
class CCrmSonetRelationType
{
	const Undefined = 0;
	//CRM ownership context (Contact to Company and etc.)
	const Ownership = 1;
	//Social Network message addresser & addressee
	const Correspondence = 2;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID >= self::Ownership && $typeID <= self::Correspondence;
	}
}

/*
 * CAllCrmSonetRelation
 */
abstract class CAllCrmSonetRelation
{
	private static $CURRENT = null;
	public static function GetCurrent()
	{
		if(self::$CURRENT === null)
		{
			self::$CURRENT = new CCrmSonetRelation();
		}
		return self::$CURRENT;
	}

	abstract public function Register($logEntityID, $logEventID, $entityTypeID, $entityID, $parentEntityTypeID, $parentEntityID, $typeID = CCrmSonetRelationType::Ownership, $level = 1);
	abstract public function RegisterBundle($logEntityID, $logEventID, $entityTypeID, $entityID, &$parents, $options = array());
	abstract public function Replace($entityTypeID, $entityID, $currentParent, $previousParent, $options = array());
	abstract public function UnRegisterByLogEntityID($logEntityID, $typeID = CCrmSonetRelationType::Undefined);
	abstract public function UnRegisterByEntity($entityTypeID, $entityID, $options = array());
	abstract public function SynchronizeLastUpdateTime($logEntityID);
	abstract public function Rebind($entityTypeID, $srcEntityID, $dstEntityID);

	public static function RegisterRelation($logEntityID, $logEventID, $entityTypeID, $entityID, $parentEntityTypeID, $parentEntityID, $typeID = CCrmSonetRelationType::Ownership, $level = 1)
	{
		self::GetCurrent()->Register($logEntityID, $logEventID, $entityTypeID, $entityID, $parentEntityTypeID, $parentEntityID, $typeID, $level);
	}
	public static function RegisterRelationBundle($logEntityID, $logEventID, $entityTypeID, $entityID, &$parents, $options = array())
	{
		self::GetCurrent()->RegisterBundle($logEntityID, $logEventID, $entityTypeID, $entityID, $parents, $options);
	}
	public static function ReplaceRelation($entityTypeID, $entityID, $currentParent, $previousParent, $options = array())
	{
		return self::GetCurrent()->Replace($entityTypeID, $entityID, $currentParent, $previousParent, $options);
	}
	public static function UnRegisterRelationsByLogEntityID($logEntityID, $typeID = CCrmSonetRelationType::Undefined)
	{
		self::GetCurrent()->UnRegisterByLogEntityID($logEntityID, $typeID);
	}
	public static function UnRegisterRelationsByEntity($entityTypeID, $entityID, $options = array())
	{
		self::GetCurrent()->UnRegisterByEntity($entityTypeID, $entityID, $options);
	}
	public static function SynchronizeRelationLastUpdateTime($logEntityID)
	{
		self::GetCurrent()->SynchronizeLastUpdateTime($logEntityID);
	}

	public static function RebindRelations($entityTypeID, $srcEntityID, $dstEntityID)
	{
		self::GetCurrent()->Rebind($entityTypeID, $srcEntityID, $dstEntityID);
	}
}
