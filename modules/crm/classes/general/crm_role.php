<?php

IncludeModuleLangFile(__FILE__);

class CCrmRole
{
	protected $cdb = null;
	private static $PERMISSIONS_BY_USER = array();

	function __construct()
	{
		global $DB;

		$this->cdb = $DB;
	}

	static public function GetList($arOrder = Array('ID' => 'DESC'), $arFilter = Array())
	{
		global $DB;

		// where
		$arWhereFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'NAME' => array(
				'TABLE_ALIAS' => 'R',
				'FIELD_NAME' => 'R.NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			)
		);

		$obQueryWhere = new CSQLWhere();
		$obQueryWhere->SetFields($arWhereFields);
		if(!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		if(!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('ID' => 'DESC');
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtolower($order);
			if($order != 'asc')
				$order = 'desc';

			if(isset($arWhereFields[$by]))
				$arSqlOrder[$by] = " R.$by $order ";
			else
			{
				$by = 'id';
				$arSqlOrder[$by] = " R.ID $order ";
			}
		}

		if (count($arSqlOrder) > 0)
			$sSqlOrder = "\n\t\t\t\tORDER BY ".implode(', ', $arSqlOrder);
		else
			$sSqlOrder = '';

		$sSql = "
			SELECT
				ID, NAME
			FROM
				b_crm_role R
			WHERE
				1=1 $sSqlSearch
			$sSqlOrder";

		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		return $obRes;
	}

	static public function GetRelation()
	{
		global $DB;
		$sSql = '
			SELECT RR.* FROM b_crm_role R, b_crm_role_relation RR
			WHERE R.ID = RR.ROLE_ID
			ORDER BY R.ID asc';
		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		return $obRes;
	}

	public function SetRelation($arRelation)
	{
		global $DB;
		$sSql = 'DELETE FROM b_crm_role_relation';
		$DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		foreach ($arRelation as $sRel => $arRole)
		{
			foreach ($arRole as $iRoleID)
			{
				$arFields = array(
					'ROLE_ID' => (int)$iRoleID,
					'RELATION' => $DB->ForSql($sRel)
				);
				$DB->Add('b_crm_role_relation', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			}
		}

		self::ClearCache();
	}

	static public function GetRolePerms($ID)
	{
		global $DB;
		$ID = (int)$ID;
		$sSql = 'SELECT * FROM b_crm_role_perms WHERE role_id = '.$ID;
		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$_arResult = array();
		while ($arRow = $obRes->Fetch())
		{
			if (!isset($arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']]))
				if ($arRow['FIELD'] != '-')
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']] = trim($arRow['ATTR']);
				else
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']] = trim($arRow['ATTR']);
		}
		return $_arResult;
	}

	// BX_CRM_PERM_NONE  - not supported
	static public function GetRoleByAttr($permEntity, $permAttr = CCrmPerms::PERM_SELF, $permType = 'READ')
	{
		global $DB;
		$permEntity = $DB->ForSql($permEntity);
		$permAttr = $DB->ForSql($permAttr);
		$permType = $DB->ForSql($permType);
		$sSql = "
			SELECT ROLE_ID
			FROM b_crm_role_perms
			WHERE ENTITY = '$permEntity' AND PERM_TYPE = '$permType' AND ATTR >= '$permAttr'";

		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$arResult = array();
		while ($arRow = $obRes->Fetch())
			$arResult[] = $arRow['ROLE_ID'];
		return $arResult;
	}

	static public function GetCalculateRolePermsByRelation($arRel)
	{
		global $DB;
		static $arResult = array();

		if (empty($arRel))
			return $arRel;

		foreach ($arRel as &$sRel)
			$sRel = $DB->ForSql(strtoupper($sRel));
		$sin = implode("','", $arRel);

		if (isset($arResult[$sin]))
			return $arResult[$sin];

		$sSql = "
			SELECT RP.*
			FROM b_crm_role_perms RP, b_crm_role_relation RR
			WHERE RP.ROLE_ID = RR.ROLE_ID AND RR.RELATION IN('$sin')";
		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$_arResult = array();
		while ($arRow = $obRes->Fetch())
		{
			$arRow['ATTR'] = trim($arRow['ATTR']);
			if ($arRow['FIELD'] == '-')
			{
				if (!isset($_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']])
					|| $arRow['ATTR'] > $_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']])
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']] = $arRow['ATTR'];
			}
			else
				if (!isset($_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']])
					|| $arRow['ATTR'] > $_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']])
					$_arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']] = $arRow['ATTR'];
		}
		$arResult[$sin] = $_arResult;
		return $_arResult;
	}

	static public function GetUserPerms($userID)
	{
		global $DB;

		$userID = intval($userID);
		if($userID <= 0)
		{
			return array();
		}

		// Prepare user codes if need
		$CAccess = new CAccess();
		$CAccess->UpdateCodes(array('USER_ID' => $userID));

		$obRes = $DB->Query(
			"SELECT RP.* FROM b_crm_role_perms RP INNER JOIN b_crm_role_relation RR ON RR.ROLE_ID = RP.ROLE_ID INNER JOIN b_user_access UA ON UA.ACCESS_CODE = RR.RELATION AND UA.USER_ID = $userID",
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		$arResult = array();
		while ($arRow = $obRes->Fetch())
		{
			$arRow['ATTR'] = trim($arRow['ATTR']);
			if ($arRow['FIELD'] == '-')
			{
				if (!isset($arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']])
					|| $arRow['ATTR'] > $arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']])
					$arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']] = $arRow['ATTR'];
			}
			else
				if (!isset($arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']])
					|| $arRow['ATTR'] > $arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']])
					$arResult[$arRow['ENTITY']][$arRow['PERM_TYPE']][$arRow['FIELD']][$arRow['FIELD_VALUE']] = $arRow['ATTR'];
		}
		return $arResult;
	}

	private static function ClearCache()
	{
		// Clean up cached permissions
		self::$PERMISSIONS_BY_USER = array();
		CrmClearMenuCache();
	}

	public function Add(&$arFields)
	{
		global $DB;

		$this->LAST_ERROR = '';
		$result = true;
		if(!$this->CheckFields($arFields))
		{
			$result = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if (!isset($arFields['RELATION']) || !is_array($arFields['RELATION']))
				$arFields['RELATION'] = array();
			$ID = (int)$DB->Add('b_crm_role', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$this->SetRoleRelation($ID, $arFields['RELATION']);
			$result = $arFields['ID'] = $ID;
		}
		return $result;
	}

	protected function SetRoleRelation($ID, $arRelation)
	{
		global $DB;
		$ID = (int)$ID;

		$sSql = 'DELETE FROM b_crm_role_perms WHERE ROLE_ID = '.$ID;
		$DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		foreach ($arRelation as $sEntity => $arPerms)
		{
			foreach ($arPerms as $sPerm => $arFields)
			{
				foreach ($arFields as $sField => $arFieldValue)
				{
					if ($sField == '-')
					{
						$arFieldValue = trim($arFieldValue);
						if ($arFieldValue != '-')
						{
							$arInsert = array();
							$arInsert['ROLE_ID'] = $ID;
							$arInsert['ENTITY'] = $sEntity;
							$arInsert['FIELD'] = '-';
							$arInsert['PERM_TYPE'] = $sPerm;
							$arInsert['ATTR'] = $arFieldValue;
							$DB->Add('b_crm_role_perms', $arInsert, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
						}
					}
					else
					{
						foreach ($arFieldValue as $fieldValue => $sAttr)
						{
							$sAttr = trim($sAttr);
							if ($sAttr != '-')
							{
								$arInsert = array();
								$arInsert['ROLE_ID'] = $ID;
								$arInsert['ENTITY'] = $sEntity;
								$arInsert['FIELD'] = $sField;
								$arInsert['FIELD_VALUE'] = $fieldValue;
								$arInsert['PERM_TYPE'] = $sPerm;
								$arInsert['ATTR'] = $sAttr;
								$DB->Add('b_crm_role_perms', $arInsert, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
							}
						}
					}
				}
			}
		}

		self::ClearCache();
	}

	public function Update($ID, &$arFields)
	{
		global $DB;

		$ID = (int)$ID;
		$this->LAST_ERROR = '';
		$bResult = true;
		if(!$this->CheckFields($arFields, $ID))
		{
			$bResult = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if (!isset($arFields['RELATION']) || !is_array($arFields['RELATION']))
				$arFields['RELATION'] = array();
			$sUpdate = $DB->PrepareUpdate('b_crm_role', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if (strlen($sUpdate) > 0)
				$DB->Query("UPDATE b_crm_role SET $sUpdate WHERE ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

			$this->SetRoleRelation($ID, $arFields['RELATION']);
			$arFields['ID'] = $ID;
		}

		return $bResult;
	}

	public function Delete($ID)
	{
		global $DB;
		$ID = (int)$ID;
		$sSql = 'DELETE FROM b_crm_role_relation WHERE ROLE_ID = '.$ID;
		$DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$sSql = 'DELETE FROM b_crm_role_perms WHERE ROLE_ID = '.$ID;
		$DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$sSql = 'DELETE FROM b_crm_role WHERE ID = '.$ID;
		$DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		self::ClearCache();
	}

	public function CheckFields(&$arFields, $ID = false)
	{
		$this->LAST_ERROR = '';
		if (($ID == false || isset($arFields['NAME'])) && empty($arFields['NAME']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_NAME')))."<br />";

		if(strlen($this->LAST_ERROR) > 0)
			return false;

		return true;
	}
}
