<?
use Bitrix\Im as IM;

class CIMStatus
{
	public static $AVAILABLE_STATUSES = Array('online', 'dnd', 'away');

	public static function Set($userId, $params)
	{
		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		if (isset($params['STATUS']))
			$params['IDLE'] = null;

		$needToUpdate = false;

		$params = self::PrepereFields($params);
		$res = IM\StatusTable::getById($userId);
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
				IM\StatusTable::update($userId, $params);
			}
		}
		else
		{
			$params['USER_ID'] = $userId;
			IM\StatusTable::add($params);

			$needToUpdate = true;
			$status = $params;
		}

		if ($needToUpdate && self::Enable())
		{
			CPullStack::AddShared(Array(
				'module_id' => 'online',
				'command' => 'user_status',
				'params' => self::PrepereToPush($status)
			));
		}

		return true;
	}

	public static function SetIdle($userId, $result)
	{
		$date = null;
		if ($result)
		{
			$date = new Bitrix\Main\Type\DateTime();
			$date->add('-10 MINUTE');
		}
		CIMStatus::Set($userId, Array('IDLE' => $date));
	}

	private static function PrepereToPush($params)
	{
		foreach($params as $key => $value)
		{
			if ($key == 'STATUS')
			{
				$params[$key] = in_array($value, self::$AVAILABLE_STATUSES)? $value: 'online';
			}
			else if (in_array($key, Array('IDLE', 'DESKTOP_LAST_DATE', 'MOBILE_LAST_DATE', 'EVENT_UNTIL_DATE')))
			{
				$params[$key] = $value? $value->getTimestamp(): 0;
			}
			else
			{
				$params[$key] = $value;
			}
		}

		return $params;
	}

	private static function PrepereFields($params)
	{
		$arValues = Array();

		$arFields = IM\StatusTable::getMap();
		foreach($params as $key => $value)
		{
			if (!isset($arFields[$key]))
				continue;

			if ($key == 'STATUS')
			{
				$arValues[$key] = in_array($value, self::$AVAILABLE_STATUSES)? $value: 'online';
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

		global $USER;
		if(!isset($arParams['ID']) && !IsModuleInstalled('intranet') && is_object($USER))
		{
			$arID[] = $USER->GetID();
			if (CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
			{
				$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $USER->GetID(), "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID"));
				if ($dbFriends)
				{
					while ($arFriends = $dbFriends->GetNext(true, false))
					{
						$friendId = $pref = (IntVal($USER->GetID()) == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"];
						$arID[] = $friendId;
					}
				}
			}
		}

		$enable = self::Enable();

		$arUsers = Array();
		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());
		if ($enable)
		{
			$query->registerRuntimeField('', new \Bitrix\Main\Entity\ReferenceField('ref', 'Bitrix\Im\StatusTable', array('=this.ID' => 'ref.USER_ID')));
		}
		$query->addSelect('ID');
		if ($enable)
		{
			$query->addSelect('ref.STATUS', 'STATUS')->addSelect('ref.IDLE', 'IDLE');
		}
		$query->addFilter('>LAST_ACTIVITY_DATE', new \Bitrix\Main\DB\SqlExpression(Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('-180')));
		$result = $query->exec();

		while ($arUser = $result->fetch())
		{
			if (!empty($arID) && !in_array($arUser["ID"], $arID))
				continue;

			$arUsers[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'status' => $enable && in_array($arUser['STATUS'], self::$AVAILABLE_STATUSES)? $arUser['STATUS']: 'online',
				'idle' => $enable && is_object($arUser['IDLE'])? $arUser['IDLE']->getTimestamp(): 0,
			);
		}

		return Array('users' => $arUsers);
	}

	public static function Enable()
	{
		return CModule::IncludeModule('pull') && CPullOptions::GetNginxStatus()? true: false;
	}
}
?>