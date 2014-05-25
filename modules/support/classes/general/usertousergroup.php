<?
IncludeModuleLangFile(__FILE__);

class CSupportUser2UserGroup
{
	public static function GetList($arOrder = array(), $arFilter = array())
	{
		global $DB;
		$arFields = array(
			'GROUP_ID' => array(
				'TABLE_ALIAS' => 'UG',
				'FIELD_NAME' => 'UG.GROUP_ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'USER_ID' => array(
				'TABLE_ALIAS' => 'UG',
				'FIELD_NAME' => 'UG.USER_ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'CAN_VIEW_GROUP_MESSAGES' => array(
				'TABLE_ALIAS' => 'UG',
				'FIELD_NAME' => 'UG.CAN_VIEW_GROUP_MESSAGES',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'CAN_MAIL_GROUP_MESSAGES' => array(
				'TABLE_ALIAS' => 'UG',
				'FIELD_NAME' => 'UG.CAN_MAIL_GROUP_MESSAGES',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'CAN_MAIL_UPDATE_GROUP_MESSAGES' => array(
				'TABLE_ALIAS' => 'UG',
				'FIELD_NAME' => 'UG.CAN_MAIL_UPDATE_GROUP_MESSAGES',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'GROUP_NAME' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'G.NAME',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'IS_TEAM_GROUP' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'G.IS_TEAM_GROUP',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),

			'LOGIN' => array(
				'TABLE_ALIAS' => 'U',
				'FIELD_NAME' => 'U.LOGIN',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'FIRST_NAME' => array(
				'TABLE_ALIAS' => 'U',
				'FIELD_NAME' => 'U.NAME',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'LAST_NAME' => array(
				'TABLE_ALIAS' => 'U',
				'FIELD_NAME' => 'U.LAST_NAME',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),

		);

		$strOrder = '';
		if (is_array($arOrder) && count($arOrder) > 0)
		{
			foreach ($arOrder as $k => $v)
			{
				if (array_key_exists($k, $arFields))
				{
					$v = strtoupper($v);
					if($v != 'DESC')
					{
						$v  ='ASC';
					}
					if (strlen($strOrder) > 0)
					{
						$strOrder .= ', ';
					}
					$strOrder .= $arFields[$k]['FIELD_NAME'] . ' ' . $v;
				}
			}
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arFields);

		$where = $obQueryWhere->GetQuery($arFilter);

		$strQuery = 'SELECT ' .
			'UG.GROUP_ID,
			UG.USER_ID,
			UG.CAN_VIEW_GROUP_MESSAGES,
			UG.CAN_MAIL_GROUP_MESSAGES,
			UG.CAN_MAIL_UPDATE_GROUP_MESSAGES as UG_CMUGM, '.
			'G.NAME GROUP_NAME, G.IS_TEAM_GROUP, '.
			'U.LOGIN, U.NAME FIRST_NAME, U.LAST_NAME ' .
			'FROM b_ticket_user_ugroup UG ' .
			'INNER JOIN b_ticket_ugroups G ON (UG.GROUP_ID=G.ID) ' .
			'INNER JOIN b_user U ON (UG.USER_ID=U.ID) ';

		if (strlen($where) > 0)
		{
			$strQuery .= ' WHERE ' . $where;
		}
		if (strlen($strOrder) > 0)
		{
			$strQuery .= ' ORDER BY ' . $strOrder;
		}

		$res = $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$res->arReplacedAliases = array('UG_CMUGM' => 'CAN_MAIL_UPDATE_GROUP_MESSAGES');

		return $res;
	}

	public static function Add($arFields)
	{
		global $DB;
		if (CSupportUser2UserGroup::CheckFields($arFields))
		{
			$arInsert = $DB->PrepareInsert('b_ticket_user_ugroup', $arFields);
			return $DB->Query('INSERT INTO b_ticket_user_ugroup ('.$arInsert[0].') VALUES ('.$arInsert[1].')', false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return false;
	}

	public static function Update($groupID, $userID, $arFields)
	{
		if (CSupportUser2UserGroup::CheckFields($arFields, $groupID, $userID))
		{
			global $DB;
			$groupID = intval($groupID);
			$userID = intval($userID);

			$strUpdate = $DB->PrepareUpdate('b_ticket_user_ugroup', $arFields);
			if (strlen($strUpdate) > 0)
			{
				$strSql = "UPDATE b_ticket_user_ugroup SET $strUpdate WHERE USER_ID=$userID AND GROUP_ID=$groupID";
				return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		return false;
	}

	public static function CheckFields(&$arFields, $groupID = 0, $userID = 0)
	{
		global $APPLICATION, $DB, $USER;
		$groupID = intval($groupID);
		$userID = intval($userID);
		if (!is_array($arFields))
		{
			$arFields = array();
		}

		//if update
		if ($userID > 0 || $groupID > 0)
		{
			if ($userID <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_USER_ID_EMPTY'));
				return false;
			}
			if ($groupID <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_GROUP_ID_EMPTY'));
				return false;
			}

			if (array_key_exists('GROUP_ID', $arFields))
			{
				unset($arFields['GROUP_ID']);
			}
			if (array_key_exists('USER_ID', $arFields))
			{
				unset($arFields['USER_ID']);
			}
		}

		//if add
		if ($userID <= 0 && $groupID <= 0)
		{
			$arFields['GROUP_ID'] = array_key_exists('GROUP_ID', $arFields) ? intval($arFields['GROUP_ID']) : 0;
			$arFields['USER_ID'] = array_key_exists('USER_ID', $arFields) ? intval($arFields['USER_ID']) : 0;

			if ($arFields['USER_ID'] <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_USER_ID_EMPTY'));
				return false;
			}
			if ($arFields['GROUP_ID'] <= 0)
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_GROUP_ID_EMPTY'));
				return false;
			}

			$rs = $USER->GetByID($arFields['USER_ID']);
			if (!$rs->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_NO_USER'));
				return false;
			}
			$rs = CSupportUserGroup::GetList(false, array('ID' => $arFields['GROUP_ID']));
			if(!$arGroup = $rs->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_NO_GROUP'));
				return false;
			}
			if (CTicket::IsAdmin($arFields['USER_ID']) || CTicket::IsSupportTeam($arFields['USER_ID']))
			{
				if ($arGroup['IS_TEAM_GROUP'] <> 'Y')
				{
					$APPLICATION->ThrowException(GetMessage('SUP_ERROR_USER_NO_CLIENT'));
					return false;
				}
			}
			elseif (CTicket::IsSupportClient($arFields['USER_ID']))
			{
				if ($arGroup['IS_TEAM_GROUP'] == 'Y')
				{
					$APPLICATION->ThrowException(GetMessage('SUP_ERROR_USER_NO_TEAM'));
					return false;
				}
			}
			else
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_NO_SUPPORT_USER'));
				return false;
			}

			$rs = CSupportUser2UserGroup::GetList(false, array('GROUP_ID' => $arFields['GROUP_ID'], 'USER_ID' => $arFields['USER_ID']));
			if ($rs->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_USERGROUP_EXISTS'));
				return false;
			}
		}

		if (array_key_exists('CAN_VIEW_GROUP_MESSAGES', $arFields))
		{
			$arFields['CAN_VIEW_GROUP_MESSAGES'] = $arFields['CAN_VIEW_GROUP_MESSAGES'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($userID <= 0 && $groupID <= 0)
		{
			$arFields['CAN_VIEW_GROUP_MESSAGES'] = 'N';
		}

		if (array_key_exists('CAN_MAIL_GROUP_MESSAGES', $arFields))
		{
			$arFields['CAN_MAIL_GROUP_MESSAGES'] = $arFields['CAN_MAIL_GROUP_MESSAGES'] == 'Y' ? 'Y' : 'N';
		}
		elseif ($userID <= 0 && $groupID <= 0)
		{
			$arFields['CAN_MAIL_GROUP_MESSAGES'] = 'N';
		}

		return true;
	}

	public static function Delete($groupID, $userID)
	{
		$groupID = intval($groupID);
		$userID = intval($userID);
		if ($groupID > 0 && $userID > 0)
		{
			global $DB;
			return $DB->Query("DELETE FROM b_ticket_user_ugroup WHERE USER_ID=$userID AND GROUP_ID=$groupID");
		}
		return false;
	}

	public static function SetGroupUsers($groupID, $arUsers)
	{
		global $APPLICATION;
		$groupID = intval($groupID);

		$ret = array();

		if ($groupID > 0)
		{
			global $DB;
			$DB->Query('DELETE FROM b_ticket_user_ugroup WHERE GROUP_ID=' . $groupID);
			if (is_array($arUsers) && count($arUsers) > 0)
			{
				foreach ($arUsers as $user)
				{
					if (is_array($user) && isset($user['USER_ID']) && intval($user['USER_ID']) > 0)
					{
						$arr = array(
							'GROUP_ID' => $groupID,
							'USER_ID' => $user['USER_ID'],
							'CAN_VIEW_GROUP_MESSAGES' => $user['CAN_VIEW_GROUP_MESSAGES'] == 'Y' ? 'Y' : 'N',
							'CAN_MAIL_GROUP_MESSAGES' => $user['CAN_MAIL_GROUP_MESSAGES'] == 'Y' ? 'Y' : 'N',
							'CAN_MAIL_UPDATE_GROUP_MESSAGES' => $user['CAN_MAIL_UPDATE_GROUP_MESSAGES'] == 'Y' ? 'Y' : 'N'
						);

						if (!CSupportUser2UserGroup::Add($arr))
						{
							if ($e = $APPLICATION->GetException())
							{
								$ret[] = $e->GetString();
							}
						}
					}
				}
			}
		}

		return $ret;
	}
}

?>
