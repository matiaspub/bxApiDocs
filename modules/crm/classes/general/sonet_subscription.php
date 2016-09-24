<?php
/*
 * CAllCrmEntityRelation
 */
abstract class CAllCrmSonetSubscription
{
	const TABLE_ALIAS = 'S';
	private static $CURRENT = null;
	private static $FIELDS = null;

	public static function GetCurrent()
	{
		if(self::$CURRENT === null)
		{
			self::$CURRENT = new CCrmSonetSubscription();
		}
		return self::$CURRENT;
	}

	abstract public function Register($entityTypeID, $entityID, $typeID, $userID);
	abstract public function UpdateByEntity($entityTypeID, $entityID, $typeID, $userID);
	abstract public function UnRegister($entityTypeID, $entityID, $typeID, $userID, $options = array());
	abstract public function UnRegisterByEntity($entityTypeID, $entityID);
	abstract public function ImportResponsibility($entityTypeID, $userID, $top);

	public static function RegisterSubscription($entityTypeID, $entityID, $typeID, $userID)
	{
		return self::GetCurrent()->Register($entityTypeID, $entityID, $typeID, $userID);
	}
	public static function UpdateSubscriptionByEntity($entityTypeID, $entityID, $typeID, $userID)
	{
		return self::GetCurrent()->UpdateByEntity($entityTypeID, $entityID, $typeID, $userID);
	}
	public static function UnRegisterSubscription($entityTypeID, $entityID, $typeID, $userID, $options = array())
	{
		return self::GetCurrent()->UnRegister($entityTypeID, $entityID, $typeID, $userID, $options);
	}
	public static function UnRegisterSubscriptionByEntity($entityTypeID, $entityID)
	{
		return self::GetCurrent()->UnRegisterByEntity($entityTypeID, $entityID);
	}
	public static function UnRegisterSubscriptionByType($entityTypeID, $entityID, $typeID)
	{
		return self::GetCurrent()->UnRegisterByType($entityTypeID, $entityID, $typeID);
	}
	public static function ImportResponsibilitySubscriptions($entityTypeID, $userID, $top)
	{
		return self::GetCurrent()->ImportResponsibility($entityTypeID, $userID, $top);
	}
	public static function EnsureResponsibilityImported($entityTypeID, $userID, $reset = false)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUser();
		}

		$reset = (bool)$reset;
		$optionName = strtolower(CCrmOwnerType::ResolveName($entityTypeID)).'_sl_subscr_import';
		if($reset || CUserOptions::GetOption('crm', $optionName, 'N', $userID) !== 'Y')
		{
			self::GetCurrent()->ImportResponsibility($entityTypeID, $userID, 5000);
			CUserOptions::SetOption('crm', $optionName, 'Y', false, $userID);
		}
		return true;
	}
	public static function EnsureAllResponsibilityImported($userID, $reset = false)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			$userID = CCrmSecurityHelper::GetCurrentUser();
		}

		$reset = (bool)$reset;
		$optionName = 'sl_subscr_import';
		if($reset || CUserOptions::GetOption('crm', $optionName, 'N', $userID) !== 'Y')
		{
			self::EnsureResponsibilityImported(CCrmOwnerType::Lead, $userID);
			self::EnsureResponsibilityImported(CCrmOwnerType::Contact, $userID);
			self::EnsureResponsibilityImported(CCrmOwnerType::Company, $userID);
			self::EnsureResponsibilityImported(CCrmOwnerType::Deal, $userID);
			self::EnsureResponsibilityImported(CCrmOwnerType::Activity, $userID);

			CUserOptions::SetOption('crm', $optionName, 'Y', false, $userID);
		}
	}

	public static function ReplaceSubscriptionByEntity($entityTypeID, $entityID, $typeID, $currentUserID, $previousUserID, $force = false)
	{
		$currentUserID = max(intval($currentUserID), 0);
		$previousUserID = max(intval($previousUserID), 0);
		if($currentUserID === $previousUserID)
		{
			return;
		}

		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return;
		}

		$entityID = intval($entityID);
		if($entityID <= 0)
		{
			return;
		}

		$typeID = intval($typeID);
		if(!CCrmSonetSubscriptionType::IsDefined($typeID))
		{
			$typeID = CCrmSonetSubscriptionType::Observation;
		}

		$current = self::GetCurrent();
		if($currentUserID > 0)
		{
			$result = $current->UpdateByEntity($entityTypeID, $entityID, $typeID, $currentUserID);
			if(!$result && $force)
			{
				$current->Register($entityTypeID, $entityID, $typeID, $currentUserID);
			}
		}
		elseif($previousUserID > 0)
		{
			$current->UnRegister($entityTypeID, $entityID, $typeID, $previousUserID);
		}
	}
	public static function IsRelationRegistered($entityTypeID, $entityID, $typeID, $userID)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return false;
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return false;
		}

		$filter = array(
			'SL_ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID),
			'ENTITY_ID' => $entityID,
			'USER_ID' => $userID
		);

		$typeID = intval($typeID);
		if(CCrmSonetSubscriptionType::IsDefined($typeID))
		{
			$filter['TYPE_ID'] = $typeID;
		}

		$dbResult = CCrmSonetSubscription::GetList(array(), $filter, array(), false, array('TYPE_ID'));
		return is_int($dbResult) ? $dbResult > 0 : false;
	}
	public static function GetRegistationTypes($entityTypeID, $entityID, $userID)
	{
		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			return array();
		}

		$userID = intval($userID);
		$entityID = intval($entityID);
		if($userID <= 0 || $entityID <= 0)
		{
			return array();
		}

		$filter = array(
			'SL_ENTITY_TYPE' => CCrmLiveFeedEntity::GetByEntityTypeID($entityTypeID),
			'ENTITY_ID' => $entityID,
			'USER_ID' => $userID
		);

		$dbResult = CCrmSonetSubscription::GetList(array(), $filter, false, false, array('TYPE_ID'));
		if(!is_object($dbResult))
		{
			return array();
		}

		$result = array();
		while($fields = $dbResult->Fetch())
		{
			$result[] = intval($fields['TYPE_ID']);
		}
		return $result;
	}
	protected  static function GetFields()
	{
		if(!isset(self::$FIELDS))
		{
			self::$FIELDS = array(
				'USER_ID' => array('FIELD' => 'S.USER_ID', 'TYPE' => 'int'),
				'SL_ENTITY_TYPE' => array('FIELD' => 'S.SL_ENTITY_TYPE', 'TYPE' => 'string'),
				'ENTITY_ID' => array('FIELD' => 'S.ENTITY_ID', 'TYPE' => 'int'),
				'TYPE_ID' => array('FIELD' => 'S.TYPE_ID', 'TYPE' => 'int')
			);
		}
		return self::$FIELDS;
	}
}

class CCrmSonetSubscriptionType
{
	const Undefined = 0;
	const Observation = 1;
	const Responsibility = 2;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID >= self::Observation && $typeID <= self::Responsibility;
	}
}