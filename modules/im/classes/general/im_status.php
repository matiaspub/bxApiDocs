<?
use Bitrix\Im as IM;

class CIMStatus
{
	public static $AVAILABLE_STATUSES = Array('online', 'dnd', 'away');
	public static $CACHE_USERS = null;
	public static $ONLINE_USERS = null;
	public static $FRIENDS_USERS = null;

	public static function Set($userId, $params)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		if (isset($params['STATUS']))
			$params['IDLE'] = null;

		$needToUpdate = false;

		$params = self::PrepareFields($params);
		$res = IM\Model\StatusTable::getById($userId);
		if ($status = $res->fetch())
		{
			foreach ($params as $key => $value)
			{
				$oldValue = is_object($status[$key])? $status[$key]->toString(): $status[$key];
				$newValue = is_object($value)? $value->toString(): $value;
				if ($oldValue != $newValue)
				{
					$status[$key] = $value;
					$needToUpdate = true;
				}
			}

			if ($needToUpdate)
			{
				IM\Model\StatusTable::update($userId, $params);
			}
		}
		else
		{
			$params['USER_ID'] = $userId;
			IM\Model\StatusTable::add($params);

			$needToUpdate = true;
			$status = $params;
		}

		if ($needToUpdate && self::Enable())
		{
			CPullStack::AddShared(Array(
				'module_id' => 'online',
				'command' => 'user_status',
				'expiry' => 120,
				'params' => self::PrepareToPush($status)
			));
		}

		return true;
	}

	public static function SetIdle($userId, $result = true, $min = 10)
	{
		$date = null;
		$min = intval($min);
		if ($result && $min > 0)
		{
			$date = new Bitrix\Main\Type\DateTime();
			$date->add('-'.$min.' MINUTE');
		}
		CIMStatus::Set($userId, Array('IDLE' => $date));
	}

	public static function SetMobile($userId, $result = true)
	{
		$date = null;
		if ($result)
		{
			$date = new Bitrix\Main\Type\DateTime();
		}
		CIMStatus::Set($userId, Array('MOBILE_LAST_DATE' => $date));
	}

	public static function SetColor($userId, $color)
	{
		CIMStatus::Set($userId, Array('COLOR' => $color));

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->ClearByTag('IM_CONTACT_LIST');
		}
	}

	private static function PrepareToPush($params)
	{
		foreach($params as $key => $value)
		{
			if ($key == 'STATUS')
			{
				$params[$key] = in_array($value, self::$AVAILABLE_STATUSES)? $value: 'online';
			}
			else if (in_array($key, Array('IDLE', 'DESKTOP_LAST_DATE', 'MOBILE_LAST_DATE', 'EVENT_UNTIL_DATE')))
			{
				$params[$key] = is_object($value)? $value->getTimestamp(): 0;
			}
			else if ($key == 'COLOR')
			{
				$params[$key] = IM\Color::getColor($value);
				if (!$params[$key])
				{
					unset($params[$key]);
				}
			}
			else
			{
				$params[$key] = $value;
			}
		}

		return $params;
	}

	private static function PrepareFields($params)
	{
		$arValues = Array();

		$arFields = IM\Model\StatusTable::getMap();
		foreach($params as $key => $value)
		{
			if (!isset($arFields[$key]))
				continue;

			if ($key == 'STATUS')
			{
				$arValues[$key] = in_array($value, self::$AVAILABLE_STATUSES)? $value: 'online';
			}
			else if ($key == 'COLOR')
			{
				$colors = IM\Color::getSafeColors();
				if (isset($colors[$value]))
				{
					$arValues[$key] = $value;
				}
			}
			else
			{
				$arValues[$key] = $value;
			}
		}

		return $arValues;
	}

	public static function GetList($arParams = Array())
	{
		if (!is_array($arParams))
			$arParams = Array();

		$arID = Array();
		if (isset($arParams['ID']) && is_array($arParams['ID']) && !empty($arParams['ID']))
		{
			foreach ($arParams['ID'] as $key => $value)
				$arID[] = intval($value);
		}
		else if (isset($arParams['ID']) && intval($arParams['ID']) > 0)
		{
			$arID[] = intval($arParams['ID']);
		}

		if (isset($arParams['CLEAR_CACHE']) && $arParams['CLEAR_CACHE'] == 'Y')
		{
			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/imc/online');
		}

		$arParams['GET_OFFLINE'] = !empty($arID) && isset($arParams['GET_OFFLINE']) && $arParams['GET_OFFLINE'] == 'Y'? 'Y': 'N';

		global $USER;
		$userId = is_object($USER)? intval($USER->GetID()): 0;

		$bBusShowAll = !IsModuleInstalled('intranet') && COption::GetOptionInt('im', 'contact_list_show_all_bus');
		if (!$bBusShowAll && !isset($arParams['ID']) && $userId > 0 && !isset($arParams['SKIP_CHECK']))
		{
			if (isset(self::$FRIENDS_USERS[$userId]))
			{
				$arID = self::$FRIENDS_USERS[$userId];
			}
			else if (CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
			{
				$arID = Array($userId);
				$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $userId, "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID"));
				if ($dbFriends)
				{
					while ($arFriends = $dbFriends->Fetch())
					{
						$arID[] = ($userId == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"];
					}
				}
				self::$FRIENDS_USERS[$userId] = $arID;
			}
		}

		$arUsers = Array();
		if (self::$ONLINE_USERS && $arParams['GET_OFFLINE'] == 'N')
		{
			$arUsers = self::$ONLINE_USERS;
		}
		else if (!self::$ONLINE_USERS || $arParams['GET_OFFLINE'] == 'Y')
		{
			$arUsers = Array();
			$loadFromCache = false;
			if (!empty($arID))
			{
				foreach($arID as $_id => $_uid)
				{
					if (isset(self::$CACHE_USERS[$_uid]))
					{
						$loadFromCache = true;
						unset($arID[$_id]);

						$arUsers[$_uid] = self::$CACHE_USERS[$_uid];
					}
				}
			}

			if (!empty($arID) || !$loadFromCache && empty($arID))
			{
				$enable = self::Enable();

				$query = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());
				$query->registerRuntimeField('', new \Bitrix\Main\Entity\ReferenceField('ref', 'Bitrix\Im\StatusTable', array('=this.ID' => 'ref.USER_ID')));
				$query->addSelect('ID')->addSelect('ref.COLOR', 'COLOR')->addSelect('PERSONAL_GENDER');
				if ($enable)
				{
					$query->addSelect('ref.STATUS', 'STATUS')->addSelect('ref.IDLE', 'IDLE')->addSelect('ref.MOBILE_LAST_DATE', 'MOBILE_LAST_DATE');
				}
				if ($arParams['GET_OFFLINE'] == 'N')
				{
					$query->addFilter('>LAST_ACTIVITY_DATE', new \Bitrix\Main\DB\SqlExpression(Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('-180')));
				}
				else
				{
					$sago = Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('-180');
					$query->registerRuntimeField('', new \Bitrix\Main\Entity\ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN LAST_ACTIVITY_DATE > '.$sago.' THEN \'Y\' ELSE \'N\' END'));

					$query->addSelect('IS_ONLINE_CUSTOM');
					$query->addFilter('=ID', $arID);
				}
				$result = $query->exec();

				while ($arUser = $result->fetch())
				{
					$color = null;
					if (isset($arUser['COLOR']) && strlen($arUser['COLOR']) > 0)
					{
						$color = IM\Color::getColor($arUser['COLOR']);
					}
					if (!$color)
					{
						$color = \CIMContactList::GetUserColor($arUser["ID"], $arUser['PERSONAL_GENDER'] == 'M'? 'M': 'F');
					}
					$arUsers[$arUser["ID"]] = Array(
						'id' => $arUser["ID"],
						'status' => $enable && in_array($arUser['STATUS'], self::$AVAILABLE_STATUSES)? $arUser['STATUS']: 'online',
						'color' => $color,
						'idle' => $enable && is_object($arUser['IDLE'])? $arUser['IDLE']->getTimestamp(): 0,
						'mobileLastDate' => $enable && is_object($arUser['MOBILE_LAST_DATE'])? $arUser['MOBILE_LAST_DATE']->getTimestamp(): 0,
					);
					if ($arParams['GET_OFFLINE'] == 'Y')
					{
						if ($arUser['IS_ONLINE_CUSTOM'] == 'N')
						{
							$arUsers[$arUser["ID"]]['status'] = 'offline';
							$arUsers[$arUser["ID"]]['idle'] = 0;
							$arUsers[$arUser["ID"]]['mobileLastDate'] = 0;
						}
						self::$CACHE_USERS[$arUser["ID"]] = $arUsers[$arUser["ID"]];
					}
				}
			}

			if ($arParams['GET_OFFLINE'] == 'N')
			{
				self::$ONLINE_USERS = $arUsers;
			}
		}

		$arResult = Array();
		if (empty($arID))
		{
			$arResult = $arUsers;
		}
		else
		{
			foreach	($arID as $userId)
			{
				if (isset($arUsers[$userId]))
				{
					$arResult[$userId] = $arUsers[$userId];
				}
			}
		}

		return Array('users' => $arResult);
	}

	public static function GetOnline()
	{
		$obCLCache = new CPHPCache;
		$cache_id = 'im_user_online_v1';
		$cache_dir = '/bx/imc/online';
		if($obCLCache->InitCache(120, $cache_id, $cache_dir))
		{
			$arOnline = $obCLCache->GetVars();
		}
		else
		{
			$arOnline = self::GetList();

			if($obCLCache->StartDataCache())
			{
				$obCLCache->EndDataCache($arOnline);
			}
		}

		return $arOnline;
	}

	public static function Enable()
	{
		return CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus()? true: false;
	}
}
?>