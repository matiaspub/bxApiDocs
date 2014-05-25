<?
IncludeModuleLangFile(__FILE__);

class CSupportUserGroup
{
	public static function GetList($arOrder = array(), $arFilter = array())
	{
		global $DB;

		$arFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'ID',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'NAME' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'NAME',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'XML_ID' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'XML_ID',
				'FIELD_TYPE' => 'string', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'SORT' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'SORT',
				'FIELD_TYPE' => 'int', //int, double, file, enum, int, string, date, datetime
				'JOIN' => false,
			),
			'IS_TEAM_GROUP' => array(
				'TABLE_ALIAS' => 'G',
				'FIELD_NAME' => 'IS_TEAM_GROUP',
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
					$strOrder .= $arFields[$k]['TABLE_ALIAS'] . '.' . $arFields[$k]['FIELD_NAME'] . ' ' . $v;
				}
			}
		}

		$obQueryWhere = new CSQLWhere;
		$obQueryWhere->SetFields($arFields);

		$where = $obQueryWhere->GetQuery($arFilter);

		$strQuery = 'SELECT G.* FROM b_ticket_ugroups G';

		if (strlen($where) > 0)
		{
			$strQuery .= ' WHERE ' . $where;
		}

		if (strlen($strOrder) > 0)
		{
			$strQuery .= ' ORDER BY ' . $strOrder;
		}
		return $DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public function Add($arFields)
	{
		global $DB, $APPLICATION;
		if ($this->CheckFields($arFields))
		{
			return $DB->Add('b_ticket_ugroups', $arFields);
		}
		return false;
	}

	public function Update($ID, $arFields)
	{
		global $DB, $APPLICATION;
		$ID = intval($ID);
		if ($this->CheckFields($arFields, $ID))
		{
			$strUpdate = $DB->PrepareUpdate('b_ticket_ugroups', $arFields);
			$DB->Query("UPDATE b_ticket_ugroups SET $strUpdate WHERE ID=$ID");
			return true;
		}
		return false;
	}

	public static function CheckFields(&$arFields, $ID = 0)
	{
		global $APPLICATION;
		if ($ID > 0)
		{
			$rs = CSupportUserGroup::GetList(false, array('ID' => $ID));
			if (!$rs->Fetch())
			{
				$APPLICATION->ThrowException(GetMessage('SUP_ERROR_GROUP_NOT_FOUND'));
				return false;
			}
		}

		if(array_key_exists('NAME', $arFields) && $arFields['NAME'] == '')
		{
			$APPLICATION->ThrowException(GetMessage('SUP_ERROR_GROUP_NAME_EMPTY'));
			return false;
		}
		if (array_key_exists('ID', $arFields))
		{
			unset($arFields['ID']);
		}
		if (array_key_exists('SORT', $arFields) && !is_numeric($arFields['SORT']))
		{
			unset($arFields['SORT']);
		}
		if (array_key_exists('IS_TEAM_GROUP', $arFields))
		{
			$arFields['IS_TEAM_GROUP'] = ($arFields['IS_TEAM_GROUP'] == 'Y' ? 'Y' : 'N');
		}

		return true;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = intval($ID);
		if ($ID > 0)
		{
			$DB->Query('DELETE FROM b_ticket_user_ugroup WHERE GROUP_ID=' . $ID);
			$DB->Query('DELETE FROM b_ticket_ugroups WHERE ID=' . $ID);
		}
	}

	public static function GetUserGroupList($arOrder = array(), $arFilter = array())
	{
		return CSupportUser2UserGroup::GetList($arOrder, $arFilter);
	}

	public static function AddUserGroup($arFields)
	{
		return CSupportUser2UserGroup::Add($arFields);
	}

	public static function UpdateUserGroup($groupID, $userID, $arFields)
	{
		return CSupportUser2UserGroup::Update($groupID, $userID, $arFields);
	}

	public static function DeleteUserGroup($groupID, $userID)
	{
		return CSupportUser2UserGroup::Delete($groupID, $userID);
	}
}

?>
