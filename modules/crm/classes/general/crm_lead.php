<?php

IncludeModuleLangFile(__FILE__);

class CAllCrmLead
{
	static public $sUFEntityID = 'CRM_LEAD';
	public $LAST_ERROR = '';
	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'LEAD';
	protected static $FIELD_INFOS = null;
	protected static $LEAD_STATUSES = null;
	protected static $LEAD_STATUSES_BY_GROUP = null;

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_LEAD_FIELD_{$fieldName}");
		return is_string($result) ? $result : '';
	}
	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'TITLE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'NAME' => array(
					'TYPE' => 'string'
				),
				'SECOND_NAME' => array(
					'TYPE' => 'string'
				),
				'LAST_NAME' => array(
					'TYPE' => 'string'
				),
				'BIRTHDATE' => array(
					'TYPE' => 'date'
				),
				'BIRTHDAY_SORT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'COMPANY_TITLE' => array(
					'TYPE' => 'string'
				),
				'SOURCE_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'SOURCE'
				),
				'SOURCE_DESCRIPTION' => array(
					'TYPE' => 'string'
				),
				'STATUS_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'STATUS'
				),
				'STATUS_DESCRIPTION' => array(
					'TYPE' => 'string'
				),
				'POST' => array(
					'TYPE' => 'string'
				),
				'ADDRESS' => array(
					'TYPE' => 'string'
				),
				'CURRENCY_ID' => array(
					'TYPE' => 'crm_currency'
				),
				'OPPORTUNITY' => array(
					'TYPE' => 'double'
				),
				'OPENED' => array(
					'TYPE' => 'char'
				),
				'COMMENTS' => array(
					'TYPE' => 'string'
				),
				'ASSIGNED_BY_ID' => array(
					'TYPE' => 'user'
				),
				'CREATED_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_CREATE' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'COMPANY_ID' => array(
					'TYPE' => 'crm_company',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'CONTACT_ID' => array(
					'TYPE' => 'crm_contact',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_CLOSED' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ORIGINATOR_ID' => array(
					'TYPE' => 'string'
				),
				'ORIGIN_ID' => array(
					'TYPE' => 'string'
				)
				/*'DISCOUNT_TYPE_ID' => array(
					'TYPE' => 'integer'
				),
				'DISCOUNT_RATE' => array(
					'TYPE' => 'double'
				),
				'DISCOUNT_SUM' => array(
					'TYPE' => 'double'
				)*/
			);
		}

		return self::$FIELD_INFOS;
	}

	public static function GetFields($arOptions = null)
	{
		$companyJoin = 'LEFT JOIN b_crm_company CO ON L.COMPANY_ID = CO.ID';
		$contactJoin = 'LEFT JOIN b_crm_contact C ON L.CONTACT_ID = C.ID';
		$assignedByJoin = 'LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON L.MODIFY_BY_ID = U3.ID';

		$result = array(
			'ID' => array('FIELD' => 'L.ID', 'TYPE' => 'int'),
			'TITLE' => array('FIELD' => 'L.TITLE', 'TYPE' => 'string'),
			'NAME' => array('FIELD' => 'L.NAME', 'TYPE' => 'string'),
			'SECOND_NAME' => array('FIELD' => 'L.SECOND_NAME', 'TYPE' => 'string'),
			'LAST_NAME' => array('FIELD' => 'L.LAST_NAME', 'TYPE' => 'string'),
			'FULL_NAME' => array('FIELD' => 'L.FULL_NAME', 'TYPE' => 'string'),
			'COMPANY_TITLE' => array('FIELD' => 'L.COMPANY_TITLE', 'TYPE' => 'string'),

			'COMPANY_ID' => array('FIELD' => 'L.COMPANY_ID', 'TYPE' => 'int'),
			'ASSOCIATED_COMPANY_TITLE' => array('FIELD' => 'CO.TITLE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'CONTACT_ID' => array('FIELD' => 'L.CONTACT_ID', 'TYPE' => 'int'),
			'CONTACT_FULL_NAME' => array('FIELD' => 'C.FULL_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'BIRTHDATE' => array('FIELD' => 'L.BIRTHDATE', 'TYPE' => 'date'),
			'BIRTHDAY_SORT' => array('FIELD' => 'L.BIRTHDAY_SORT', 'TYPE' => 'int'),

			'SOURCE_ID' => array('FIELD' => 'L.SOURCE_ID', 'TYPE' => 'string'),
			'SOURCE_DESCRIPTION' => array('FIELD' => 'L.SOURCE_DESCRIPTION', 'TYPE' => 'string'),
			'STATUS_ID' => array('FIELD' => 'L.STATUS_ID', 'TYPE' => 'string'),
			'STATUS_DESCRIPTION' => array('FIELD' => 'L.STATUS_DESCRIPTION', 'TYPE' => 'string'),

			'POST' => array('FIELD' => 'L.POST', 'TYPE' => 'string'),
			'ADDRESS' => array('FIELD' => 'L.ADDRESS', 'TYPE' => 'string'),
			'COMMENTS' => array('FIELD' => 'L.COMMENTS', 'TYPE' => 'string'),

			'CURRENCY_ID' => array('FIELD' => 'L.CURRENCY_ID', 'TYPE' => 'string'),
			'EXCH_RATE' => array('FIELD' => 'L.EXCH_RATE', 'TYPE' => 'double'),
			'OPPORTUNITY' => array('FIELD' => 'L.OPPORTUNITY', 'TYPE' => 'double'),
			'ACCOUNT_CURRENCY_ID' => array('FIELD' => 'L.ACCOUNT_CURRENCY_ID', 'TYPE' => 'string'),
			'OPPORTUNITY_ACCOUNT' => array('FIELD' => 'L.OPPORTUNITY_ACCOUNT', 'TYPE' => 'double'),

			'ASSIGNED_BY_ID' => array('FIELD' => 'L.ASSIGNED_BY_ID', 'TYPE' => 'int'),
			'ASSIGNED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_WORK_POSITION' => array('FIELD' => 'U.WORK_POSITION', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'string', 'FROM' => $assignedByJoin),

			'CREATED_BY_ID' => array('FIELD' => 'L.CREATED_BY_ID', 'TYPE' => 'int'),
			'CREATED_BY_LOGIN' => array('FIELD' => 'U2.LOGIN', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_NAME' => array('FIELD' => 'U2.NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_LAST_NAME' => array('FIELD' => 'U2.LAST_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U2.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),

			'MODIFY_BY_ID' => array('FIELD' => 'L.MODIFY_BY_ID', 'TYPE' => 'int'),
			'MODIFY_BY_LOGIN' => array('FIELD' => 'U3.LOGIN', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_NAME' => array('FIELD' => 'U3.NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_LAST_NAME' => array('FIELD' => 'U3.LAST_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_SECOND_NAME' => array('FIELD' => 'U3.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),

			'DATE_CREATE' => array('FIELD' => 'L.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => 'L.DATE_MODIFY', 'TYPE' => 'datetime'),
			'DATE_CLOSED' => array('FIELD' => 'L.DATE_CLOSED', 'TYPE' => 'datetime'),

			//'DISCOUNT_TYPE_ID' => array('FIELD' => 'L.DISCOUNT_TYPE_ID', 'TYPE' => 'int'),
			//'DISCOUNT_RATE' => array('FIELD' => 'L.DISCOUNT_RATE', 'TYPE' => 'double'),
			//'DISCOUNT_SUM' => array('FIELD' => 'L.DISCOUNT_SUM', 'TYPE' => 'double'),

			'OPENED' => array('FIELD' => 'L.OPENED', 'TYPE' => 'char'),
			'ORIGINATOR_ID' => array('FIELD' => 'L.ORIGINATOR_ID', 'TYPE' => 'string'), //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => array('FIELD' => 'L.ORIGIN_ID', 'TYPE' => 'string'), //ITEM ID IN EXTERNAL SYSTEM

			// For compatibility only
			'PRODUCT_ID' => array('FIELD' => 'L.PRODUCT_ID', 'TYPE' => 'string')
		);

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		$additionalFields = is_array($arOptions) && isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('STATUS_SORT', $additionalFields, true))
			{
				$statusJoin = "LEFT JOIN b_crm_status ST ON ST.ENTITY_ID = 'STATUS' AND L.STATUS_ID = ST.STATUS_ID";
				$result['STATUS_SORT'] = array('FIELD' => 'ST.SORT', 'TYPE' => 'int', 'FROM' => $statusJoin);
			}

			if(in_array('ACTIVITY', $additionalFields, true))
			{
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Lead, 'L', 'AC', 'UAC', 'ACUSR');

				$result['C_ACTIVITY_ID'] = array('FIELD' => 'UAC.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_TIME'] = array('FIELD' => 'UAC.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_SUBJECT'] = array('FIELD' => 'AC.SUBJECT', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_ID'] = array('FIELD' => 'AC.RESPONSIBLE_ID', 'TYPE' => 'int', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LOGIN'] = array('FIELD' => 'ACUSR.LOGIN', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_NAME'] = array('FIELD' => 'ACUSR.NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_LAST_NAME'] = array('FIELD' => 'ACUSR.LAST_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);
				$result['C_ACTIVITY_RESP_SECOND_NAME'] = array('FIELD' => 'ACUSR.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $commonActivityJoin);

				$userID = CCrmPerms::GetCurrentUserID();
				if($userID > 0)
				{
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Lead, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
				}
			}
		}

		return $result;
	}

	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		// Applying filter by PRODUCT_ID
		$prodID = isset($arFilter['PRODUCT_ROW_PRODUCT_ID']) ? intval($arFilter['PRODUCT_ROW_PRODUCT_ID']) : 0;
		if($prodID <= 0)
		{
			return false;
		}

		$a = $sender->GetTableAlias();
		return array(
			'WHERE' => "$a.ID IN (SELECT LP.OWNER_ID from b_crm_product_row LP where LP.OWNER_TYPE = 'L' and LP.OWNER_ID = $a.ID and LP.PRODUCT_ID = $prodID)"
		);
	}
	// <-- Service

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}

	public static function GetUserFields()
	{
		global $USER_FIELD_MANAGER;
		return $USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID);
	}

	public static function Exists($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return false;
		}

		$dbRes = self::GetListEx(
			array(),
			array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID')
		);

		return is_array($dbRes->Fetch());
	}

	public static function GetRightSiblingID($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return 0;
		}

		$dbRes = self::GetListEx(
			array('ID' => 'ASC'),
			array('>ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			array('nTopCount' => 1),
			array('ID')
		);

		$arRes =  $dbRes->Fetch();
		if(!is_array($arRes))
		{
			return 0;
		}

		return intval($arRes['ID']);
	}

	public static function GetLeftSiblingID($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return 0;
		}

		$dbRes = self::GetListEx(
			array('ID' => 'DESC'),
			array('<ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			array('nTopCount' => 1),
			array('ID')
		);

		$arRes =  $dbRes->Fetch();
		if(!is_array($arRes))
		{
			return 0;
		}

		return intval($arRes['ID']);
	}

	// GetList with navigation support
	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(!isset($arOptions['PERMISSION_SQL_TYPE']))
		{
			$arOptions['PERMISSION_SQL_TYPE'] = 'FROM';
			$arOptions['PERMISSION_SQL_UNION'] = 'DISTINCT';
		}

		$lb = new CCrmEntityListBuilder(
			CCrmLead::DB_TYPE,
			CCrmLead::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'LEAD',
			array('CCrmLead', 'BuildPermSql'),
			array('CCrmLead', '__AfterPrepareSql')
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function CreateListBuilder(array $arFieldOptions = null)
	{
		return new CCrmEntityListBuilder(
			CCrmLead::DB_TYPE,
			CCrmLead::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields($arFieldOptions),
			self::$sUFEntityID,
			'LEAD',
			array('CCrmLead', 'BuildPermSql'),
			array('CCrmLead', '__AfterPrepareSql')
		);
	}

	/**
	 *
	 * @param array $arOrder
	 * @param array $arFilter
	 * @param array $arSelect
	 * @return CDBResult
	 */
	public static function GetList($arOrder = Array('DATE_CREATE' => 'DESC'), $arFilter = Array(), $arSelect = Array(), $nPageTop = false)
	{
		global $DB, $USER_FIELD_MANAGER;

		// fields
		$arFields = array(
			'ID' => 'L.ID',
			'CONTACT_ID' => 'L.CONTACT_ID',
			'COMPANY_ID' => 'L.COMPANY_ID',
			'POST' => 'L.POST',
			'ADDRESS' => 'L.ADDRESS',
			'COMMENTS' => 'L.COMMENTS',
			'NAME' => 'L.NAME',
			'COMPANY_TITLE' => 'L.COMPANY_TITLE',
			'TITLE' => 'L.TITLE',
			'PRODUCT_ID' => 'L.PRODUCT_ID',
			'SOURCE_ID' => 'L.SOURCE_ID',
			'SOURCE_DESCRIPTION' => 'L.SOURCE_DESCRIPTION',
			'STATUS_ID' => 'L.STATUS_ID',
			'STATUS_DESCRIPTION' => 'L.STATUS_DESCRIPTION',
			'SECOND_NAME' => 'L.SECOND_NAME',
			'LAST_NAME' => 'L.LAST_NAME',
			'FULL_NAME' => 'L.FULL_NAME',
			'OPPORTUNITY' => 'L.OPPORTUNITY',
			'CURRENCY_ID' => 'L.CURRENCY_ID',
			'OPPORTUNITY_ACCOUNT' => 'L.OPPORTUNITY_ACCOUNT',
			'ACCOUNT_CURRENCY_ID' => 'L.ACCOUNT_CURRENCY_ID',
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'ASSIGNED_BY_ID' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'CREATED_BY_ID' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'MODIFY_BY_ID' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => $DB->DateToCharFunction('L.DATE_CREATE'),
			'DATE_MODIFY' => $DB->DateToCharFunction('L.DATE_MODIFY'),
			'BIRTHDATE' => $DB->DateToCharFunction('L.BIRTHDATE'),
			'OPENED' => 'L.OPENED',
			'ASSIGNED_BY_LOGIN' => 'U.LOGIN',
			'ASSIGNED_BY_NAME' => 'U.NAME',
			'ASSIGNED_BY_LAST_NAME' => 'U.LAST_NAME',
			'ASSIGNED_BY_SECOND_NAME' => 'U.SECOND_NAME',
			'CREATED_BY_LOGIN' => 'U2.LOGIN',
			'CREATED_BY_NAME' => 'U2.NAME',
			'CREATED_BY_LAST_NAME' => 'U2.LAST_NAME',
			'CREATED_BY_SECOND_NAME' => 'U2.SECOND_NAME',
			'MODIFY_BY_LOGIN' => 'U3.LOGIN',
			'MODIFY_BY_NAME' => 'U3.NAME',
			'MODIFY_BY_LAST_NAME' => 'U3.LAST_NAME',
			'MODIFY_BY_SECOND_NAME' => 'U3.SECOND_NAME',
			'EXCH_RATE' => 'L.EXCH_RATE',
			'ORIGINATOR_ID' => 'L.ORIGINATOR_ID', //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => 'L.ORIGIN_ID', //ITEM ID IN EXTERNAL SYSTEM
			'DATE_CLOSED' => $DB->DateToCharFunction('L.DATE_CLOSED')
		);

		$arSqlSelect = array();
		$sSqlJoin = '';
		if (count($arSelect) == 0)
			$arSelect = array_merge(array_keys($arFields), array('UF_*'));

		$obQueryWhere = new CSQLWhere();
		$arFilterField = $arSelect;
		foreach ($arFilter as $sKey => $sValue)
		{
			$arField = $obQueryWhere->MakeOperation($sKey);
			$arFilterField[] = $arField['FIELD'];
		}

		if (in_array('ASSIGNED_BY_LOGIN', $arFilterField) || in_array('ASSIGNED_BY', $arFilterField))
		{
			$arSelect[] = 'ASSIGNED_BY_LOGIN';
			$arSelect[] = 'ASSIGNED_BY_NAME';
			$arSelect[] = 'ASSIGNED_BY_LAST_NAME';
			$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID ';
		}
		if (in_array('CREATED_BY_LOGIN', $arFilterField) || in_array('CREATED_BY_LOGIN', $arFilterField))
		{
			$arSelect[] = 'CREATED_BY';
			$arSelect[] = 'CREATED_BY_LOGIN';
			$arSelect[] = 'CREATED_BY_NAME';
			$arSelect[] = 'CREATED_BY_LAST_NAME';
			$arSelect[] = 'CREATED_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID ';
		}
		if (in_array('MODIFY_BY_LOGIN', $arFilterField) || in_array('MODIFY_BY_LOGIN', $arFilterField))
		{
			$arSelect[] = 'MODIFY_BY';
			$arSelect[] = 'MODIFY_BY_LOGIN';
			$arSelect[] = 'MODIFY_BY_NAME';
			$arSelect[] = 'MODIFY_BY_LAST_NAME';
			$arSelect[] = 'MODIFY_BY_SECOND_NAME';
			$sSqlJoin .= ' LEFT JOIN b_user U3 ON  L.MODIFY_BY_ID = U3.ID ';
		}

		foreach($arSelect as $field)
		{
			$field = strtoupper($field);
			if(array_key_exists($field, $arFields))
				$arSqlSelect[$field] = $arFields[$field].($field != '*' ? ' AS '.$field : '');
		}

		if (!isset($arSqlSelect['ID']))
			$arSqlSelect['ID'] = $arFields['ID'];
		$sSqlSelect = implode(",\n", $arSqlSelect);

		if (isset($arFilter['FM']) && !empty($arFilter['FM']))
		{
			$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'FILTER' => $arFilter['FM']));
			$ids = array();
			while($ar = $res->Fetch())
			{
				$ids[] = $ar['ELEMENT_ID'];
			}

			if(count($ids) == 0)
			{
				// Fix for #26789 (nothing found)
				$rs = new CDBResult();
				$rs->InitFromArray(array());
				return $rs;
			}

			$arFilter['ID'] = $ids;
		}

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity(self::$sUFEntityID, 'L.ID');
		$obUserFieldsSql->SetSelect($arSelect);
		$obUserFieldsSql->SetFilter($arFilter);
		$obUserFieldsSql->SetOrder($arOrder);

		$arSqlSearch = array();
		// check permissions
		$sSqlPerm = '';
		if (!CCrmPerms::IsAdmin()
			&& (!array_key_exists('CHECK_PERMISSIONS', $arFilter) ||  $arFilter['CHECK_PERMISSIONS'] !== 'N')
		)
		{
			$arPermType = array();
			if (!isset($arFilter['PERMISSION']))
				$arPermType[] = 'READ';
			else
				$arPermType	= is_array($arFilter['PERMISSION']) ? $arFilter['PERMISSION'] : array($arFilter['PERMISSION']);

			$sSqlPerm = self::BuildPermSql('L', $arPermType);
			if ($sSqlPerm === false)
			{
				$CDBResult = new CDBResult();
				$CDBResult->InitFromArray(array());
				return $CDBResult;
			}
			if(strlen($sSqlPerm) > 0)
			{
				$sSqlPerm = ' AND '.$sSqlPerm;
			}
		}

		// where
		$arWhereFields = array(
			'ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'CONTACT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CONTACT_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'COMPANY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'SOURCE_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.SOURCE_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'STATUS_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.STATUS_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CURRENCY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CURRENCY_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'OPPORTUNITY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPPORTUNITY',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ACCOUNT_CURRENCY_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPPORTUNITY_ACCOUNT',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'PRODUCT_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.PRODUCT_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'SECOND_NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.SECOND_NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'LAST_NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.LAST_NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'FULL_NAME' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.FULL_NAME',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'COMPANY_TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'POST' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.POST',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDRESS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDRESS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'COMMENTS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMMENTS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'DATE_CREATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'DATE_MODIFY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_MODIFY',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			),
			'CREATED_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CREATED_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ASSIGNED_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ASSIGNED_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'OPENED' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.OPENED',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'MODIFY_BY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.MODIFY_BY_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'EXCH_RATE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EXCH_RATE',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'ORIGINATOR_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ORIGINATOR_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ORIGIN_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ORIGIN_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'DATE_CLOSED' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.DATE_CLOSED',
				'FIELD_TYPE' => 'datetime',
				'JOIN' => false
			)
		);

		$obQueryWhere->SetFields($arWhereFields);
		if(!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		foreach($arSqlSearch as $r)
			if(strlen($r) > 0)
				$sSqlSearch .= "\n\t\t\t\tAND  ($r) ";
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$CCrmUserType->ListPrepareFilter($arFilter);
		$r = $obUserFieldsSql->GetFilter();
		if(strlen($r) > 0)
			$sSqlSearch .= "\n\t\t\t\tAND ($r) ";

		if(!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$arFieldsOrder = array(
			'ASSIGNED_BY' => 'L.ASSIGNED_BY_ID',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => 'L.DATE_CREATE',
			'DATE_MODIFY' => 'L.DATE_MODIFY',
			'DATE_CLOSED' => 'L.DATE_CLOSED'
		);

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('DATE_CREATE' => 'DESC');
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtolower($order);
			if($order != 'asc')
				$order = 'desc';

			if (isset($arFieldsOrder[$by]))
				$arSqlOrder[$by] = " {$arFieldsOrder[$by]} $order ";
			else if(isset($arFields[$by]) && $by != 'ADDRESS')
				$arSqlOrder[$by] = " L.$by $order ";
			else if($s = $obUserFieldsSql->GetOrder($by))
				$arSqlOrder[$by] = " $s $order ";
			else
			{
				$by = 'date_create';
				$arSqlOrder[$by] = " L.DATE_CREATE $order ";
			}
		}

		if (count($arSqlOrder) > 0)
			$sSqlOrder = "\n\t\t\t\tORDER BY ".implode(', ', $arSqlOrder);
		else
			$sSqlOrder = '';

		$sSql = "
			SELECT
				$sSqlSelect
				{$obUserFieldsSql->GetSelect()}
			FROM
				b_crm_lead L $sSqlJoin
				{$obUserFieldsSql->GetJoin('L.ID')}
			WHERE
				1=1
				$sSqlSearch
				$sSqlPerm
			$sSqlOrder";

		if ($nPageTop !== false)
		{
			$nPageTop = (int) $nPageTop;
			$sSql = $DB->TopSql($sSql, $nPageTop);
		}

		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$obRes->SetUserFields($USER_FIELD_MANAGER->GetUserFields(self::$sUFEntityID));
		return $obRes;
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		$arFilter = array('=ID' => intval($ID));
		if (!$bCheckPerms)
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = CCrmLead::GetListEx(array(), $arFilter);
		return $dbRes->Fetch();
	}

	static public function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = array())
	{
		return CCrmPerms::BuildSql('LEAD', $sAliasPrefix, $mPermType, $arOptions);
	}
	public function Add(array &$arFields, $bUpdateSearch = true, $arOptions = array())
	{
		global $DB;

		$this->LAST_ERROR = '';

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(isset($arOptions['CURRENT_USER']))
		{
			$iUserId = intval($arOptions['CURRENT_USER']);
		}
		else
		{
			$iUserId = CCrmPerms::GetCurrentUserID();
		}

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		if(!isset($arFields['CREATED_BY_ID']) || (int)$arFields['CREATED_BY_ID'] <= 0)
			$arFields['CREATED_BY_ID'] = $iUserId;
		if(!isset($arFields['MODIFY_BY_ID']) || (int)$arFields['MODIFY_BY_ID'] <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;
		if(!isset($arFields['ASSIGNED_BY_ID']) || (int)$arFields['ASSIGNED_BY_ID'] <= 0)
			$arFields['ASSIGNED_BY_ID'] = $iUserId;

		if(!isset($arFields['OPPORTUNITY']))
		{
			$arFields['OPPORTUNITY'] = 0.0;
		}

		if(!$this->CheckFields($arFields, false, $arOptions))
		{
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
			return false;
		}

		if(isset($arFields['BIRTHDATE']))
		{
			if($arFields['BIRTHDATE'] !== '')
			{
				$birthDate = $arFields['BIRTHDATE'];
				$arFields['~BIRTHDATE'] = $DB->CharToDateFunction($birthDate, 'SHORT', false);
				$arFields['BIRTHDAY_SORT'] = Bitrix\Crm\BirthdayReminder::prepareSorting($birthDate);
			}
			else
			{
				$arFields['BIRTHDAY_SORT'] = Bitrix\Crm\BirthdayReminder::prepareSorting('');
			}
			unset($arFields['BIRTHDATE']);
		}
		else
		{
			$arFields['BIRTHDAY_SORT'] = Bitrix\Crm\BirthdayReminder::prepareSorting('');
		}

		if (!isset($arFields['STATUS_ID']) || $arFields['STATUS_ID'] === '')
			$arFields['STATUS_ID'] = 'NEW';

		if (isset($arFields['DATE_CLOSED']))
			unset($arFields['DATE_CLOSED']);
		self::EnsureStatusesLoaded();
		if (in_array($arFields['STATUS_ID'], self::$LEAD_STATUSES_BY_GROUP['FINISHED']))
			$arFields['~DATE_CLOSED'] = $DB->CurrentTimeFunction();

		$arAttr = array();

		$arAttr['STATUS_ID'] = $arFields['STATUS_ID'];
		if (!empty($arFields['OPENED']))
			$arAttr['OPENED'] = $arFields['OPENED'];

		$sPermission = 'ADD';
		if (isset($arFields['PERMISSION']))
		{
			if ($arFields['PERMISSION'] == 'IMPORT')
				$sPermission = 'IMPORT';
			unset($arFields['PERMISSION']);
		}

		if($this->bCheckPermission)
		{
			$arEntityAttr = self::BuildEntityAttr($iUserId, $arAttr);
			$userPerms =  $iUserId == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($iUserId);
			$sEntityPerm = $userPerms->GetPermType('LEAD', $sPermission, $arEntityAttr);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			if ($sEntityPerm == BX_CRM_PERM_SELF && $assignedByID != $iUserId)
			{
				$arFields['ASSIGNED_BY_ID'] = $iUserId;
			}
			if ($sEntityPerm == BX_CRM_PERM_OPEN && $iUserId == $assignedByID)
			{
				$arFields['OPENED'] = 'Y';
			}
		}

		$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
		$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
		$userPerms =  $assignedByID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($assignedByID);
		$sEntityPerm = $userPerms->GetPermType('LEAD', $sPermission, $arEntityAttr);
		$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

		// Calculation of Account Data
		$accData = CCrmAccountingHelper::PrepareAccountingData(
			array(
				'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : null,
				'SUM' => isset($arFields['OPPORTUNITY']) ? $arFields['OPPORTUNITY'] : null,
				'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : null
			)
		);

		if(is_array($accData))
		{
			$arFields['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
			$arFields['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
		}

		if (isset($arFields['NAME']) || isset($arFields['LAST_NAME']))
		{
			$arFields['FULL_NAME'] = trim((isset($arFields['NAME'])? $arFields['NAME']: '').' '.(isset($arFields['LAST_NAME'])? $arFields['LAST_NAME']: ''));
		}

		$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmLeadAdd');
		while ($arEvent = $beforeEvents->Fetch())
		{
			if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
			{
				if(isset($arFields['RESULT_MESSAGE']))
				{
					$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
				}
				else
				{
					$this->LAST_ERROR = GetMessage('CRM_LEAD_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
					$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				}
				return false;
			}
		}

		$ID = intval($DB->Add('b_crm_lead', $arFields, array(), '', false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__));
		$arFields['ID'] = $ID;
		CCrmPerms::UpdateEntityAttr('LEAD', $ID, $arEntityAttr);

		$companyTitle = isset($arFields['COMPANY_TITLE']) ? $arFields['COMPANY_TITLE'] : '';
		if($companyTitle !== '')
		{
			\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::register(CCrmOwnerType::Lead, $ID, $companyTitle);
		}

		$lastName = isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : '';
		if($lastName !== '')
		{
			\Bitrix\Crm\Integrity\DuplicatePersonCriterion::register(
				CCrmOwnerType::Lead,
				$ID,
				$lastName,
				isset($arFields['NAME']) ? $arFields['NAME'] : '',
				isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : ''
			);
		}

		CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
		$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			$CCrmFieldMulti->SetFields('LEAD', $ID, $arFields['FM']);

			$emails = \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'EMAIL');
			if(!empty($emails))
			{
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Lead, $ID, 'EMAIL', $emails);
			}

			$phones = \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'PHONE');
			if(!empty($phones))
			{
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Lead, $ID, 'PHONE', $phones);
			}
		}
		\Bitrix\Crm\Integrity\DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Lead, $ID, $arFields);

		if($bUpdateSearch)
		{
			CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'LEAD', true);
		}

		if(isset($arOptions['REGISTER_SONET_EVENT']) && $arOptions['REGISTER_SONET_EVENT'] === true)
		{
			$opportunity = round((isset($arFields['OPPORTUNITY']) ? doubleval($arFields['OPPORTUNITY']) : 0.0), 2);
			$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
			if($currencyID === '')
			{
				$currencyID = CCrmCurrency::GetBaseCurrencyID();
			}
			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			$createdByID = intval($arFields['CREATED_BY_ID']);

			$liveFeedFields = array(
				'USER_ID' => $createdByID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
				'ENTITY_ID' => $ID,
				//'EVENT_ID' => $eventID,
				'TITLE' => GetMessage('CRM_LEAD_EVENT_ADD'),
				'MESSAGE' => '',
				'PARAMS' => array(
					'TITLE' => $arFields['TITLE'],
					'NAME' => isset($arFields['NAME']) ? $arFields['NAME'] : '',
					'SECOND_NAME' => isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : '',
					'LAST_NAME' => isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : '',
					'COMPANY_TITLE' => isset($arFields['COMPANY_TITLE']) ? $arFields['COMPANY_TITLE'] : '',
					'STATUS_ID' => $arFields['STATUS_ID'],
					'OPPORTUNITY' => strval($opportunity),
					'CURRENCY_ID' => $currencyID,
					'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
					'RESPONSIBLE_ID' => $assignedByID
				)
			);

			CCrmSonetSubscription::RegisterSubscription(
				CCrmOwnerType::Lead,
				$ID,
				CCrmSonetSubscriptionType::Responsibility,
				$assignedByID
			);
			$logEventID = CCrmLiveFeed::CreateLogEvent($liveFeedFields, CCrmLiveFeedEvent::Add);

			if (
				$logEventID
				&& $assignedByID != $createdByID
				&& CModule::IncludeModule("im")
			)
			{
				$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Lead, $ID);
				$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

				$arMessageFields = array(
					"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
					"TO_USER_ID" => $assignedByID,
					"FROM_USER_ID" => $createdByID,
					"NOTIFY_TYPE" => IM_NOTIFY_FROM,
					"NOTIFY_MODULE" => "crm",
					"LOG_ID" => $logEventID,
					"NOTIFY_EVENT" => "lead_add",
					"NOTIFY_TAG" => "CRM|LEAD|".$ID,
					"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
					"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
				);
				CIMNotify::Add($arMessageFields);
			}
		}

		$afterEvents = GetModuleEvents('crm', 'OnAfterCrmLeadAdd');
		while ($arEvent = $afterEvents->Fetch())
		{
			ExecuteModuleEventEx($arEvent, array(&$arFields));
		}

		if(isset($arFields['ORIGIN_ID']) && $arFields['ORIGIN_ID'] !== '')
		{
			$afterEvents = GetModuleEvents('crm', 'OnAfterExternalCrmLeadAdd');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}

		return $ID;
	}

	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$statusID = isset($arAttr['STATUS_ID']) ? $arAttr['STATUS_ID'] : '';
		if($statusID !== '')
		{
			$arResult[] = "STATUS_ID{$statusID}";
		}

		$arUserAttr = CCrmPerms::BuildUserEntityAttr($userID);
		return array_merge($arResult, $arUserAttr['INTRANET']);
	}

	static public function RebuildEntityAccessAttrs($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID', 'OPENED', 'STATUS_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);
			$assignedByID = isset($fields['ASSIGNED_BY_ID']) ? intval($fields['ASSIGNED_BY_ID']) : 0;
			if($assignedByID <= 0)
			{
				continue;
			}

			$attrs = array();
			if(isset($fields['OPENED']))
			{
				$attrs['OPENED'] = $fields['OPENED'];
			}

			if(isset($fields['STATUS_ID']))
			{
				$attrs['STATUS_ID'] = $fields['STATUS_ID'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			CCrmPerms::UpdateEntityAttr('LEAD', $ID, $entityAttrs);
		}
	}

	private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $arOptions = array())
	{
		global $DB;

		$this->LAST_ERROR = '';
		$ID = (int) $ID;
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arFilterTmp = array('ID' => $ID);
		if (!$this->bCheckPermission)
			$arFilterTmp['CHECK_PERMISSIONS'] = 'N';

		$obRes = self::GetListEx(array(), $arFilterTmp);
		if (!($arRow = $obRes->Fetch()))
			return false;

		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		if (isset($arFields['DATE_MODIFY']))
			unset($arFields['DATE_MODIFY']);
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		if (isset($arFields['DATE_CLOSED']))
			unset($arFields['DATE_CLOSED']);
		if (!empty($arFields['STATUS_ID']) && $arFields['STATUS_ID'] !== $arRow['STATUS_ID'])
		{
			self::EnsureStatusesLoaded();
			if (in_array($arFields['STATUS_ID'], self::$LEAD_STATUSES_BY_GROUP['FINISHED']))
				$arFields['~DATE_CLOSED'] = $DB->CurrentTimeFunction();
		}

		if(!isset($arFields['MODIFY_BY_ID']) || $arFields['MODIFY_BY_ID'] <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;
		if(isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
			unset($arFields['ASSIGNED_BY_ID']);

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;
		if(!$this->CheckFields($arFields, $ID, $arOptions))
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		else
		{
			if($this->bCheckPermission && !CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $this->cPerms))
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmLeadUpdate');
			while ($arEvent = $beforeEvents->Fetch())
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_LEAD_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$arAttr = array();
			$arAttr['STATUS_ID'] = !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $arRow['STATUS_ID'];
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$sEntityPerm = $this->cPerms->GetPermType('LEAD', 'WRITE', $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
			//Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
			if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
			{
				$arFields['OPENED'] = 'Y';
			}

			if (isset($arFields['ASSIGNED_BY_ID']) && $arRow['ASSIGNED_BY_ID'] != $arFields['ASSIGNED_BY_ID'])
				CCrmEvent::SetAssignedByElement($arFields['ASSIGNED_BY_ID'], 'LEAD', $ID);

			$sonetEventData = array();
			if ($bCompare)
			{
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $ID)
				);
				$arRow['FM'] = array();
				while($ar = $res->Fetch())
					$arRow['FM'][$ar['TYPE_ID']][$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);

				$arEvents = self::CompareFields($arRow, $arFields);
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'LEAD';
					$arEvent['ENTITY_ID'] = $ID;
					$arEvent['EVENT_TYPE'] = 1;

					$CCrmEvent = new CCrmEvent();
					$eventID = $CCrmEvent->Add($arEvent);
					if(is_int($eventID) && $eventID > 0)
					{
						$fieldID = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';
						if($fieldID === '')
						{
							continue;
						}

						switch($fieldID)
						{
							case 'STATUS_ID':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Progress,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_LEAD_EVENT_UPDATE_STATUS'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_STATUS_ID' => $arRow['STATUS_ID'],
											'FINAL_STATUS_ID' => $arFields['STATUS_ID']
										)
									)
								);
							}
							break;
							case 'ASSIGNED_BY_ID':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Responsible,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_LEAD_EVENT_UPDATE_ASSIGNED_BY'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_RESPONSIBLE_ID' => $arRow['ASSIGNED_BY_ID'],
											'FINAL_RESPONSIBLE_ID' => $arFields['ASSIGNED_BY_ID']
										)
									)
								);
							}
							break;
							case 'TITLE':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Denomination,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_LEAD_EVENT_UPDATE_TITLE'),
										'MESSAGE' => '',
										'PARAMS' => array(
											'START_TITLE' => $arRow['TITLE'],
											'FINAL_TITLE' => $arFields['TITLE']
										)
									)
								);
							}
							break;
						}
					}
				}
			}

			if (isset($arFields['BIRTHDAY_SORT']))
				unset($arFields['BIRTHDAY_SORT']);

			if(isset($arFields['BIRTHDATE']))
			{
				if($arFields['BIRTHDATE'] !== '')
				{
					$birthDate = $arFields['BIRTHDATE'];
					$arFields['~BIRTHDATE'] = $DB->CharToDateFunction($birthDate, 'SHORT', false);
					$arFields['BIRTHDAY_SORT'] = Bitrix\Crm\BirthdayReminder::prepareSorting($birthDate);
					unset($arFields['BIRTHDATE']);
				}
				else
				{
					$arFields['BIRTHDAY_SORT'] = Bitrix\Crm\BirthdayReminder::prepareSorting('');
				}
			}

			// Calculation of Account Data
			$accData = CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : (isset($arRow['CURRENCY_ID']) ? $arRow['CURRENCY_ID'] : null),
					'SUM' => isset($arFields['OPPORTUNITY']) ? $arFields['OPPORTUNITY'] : (isset($arRow['OPPORTUNITY']) ? $arRow['OPPORTUNITY'] : null),
					'EXCH_RATE' => isset($arFields['EXCH_RATE']) ? $arFields['EXCH_RATE'] : (isset($arRow['EXCH_RATE']) ? $arRow['EXCH_RATE'] : null)
				)
			);

			if(is_array($accData))
			{
				$arFields['ACCOUNT_CURRENCY_ID'] = $accData['ACCOUNT_CURRENCY_ID'];
				$arFields['OPPORTUNITY_ACCOUNT'] = $accData['ACCOUNT_SUM'];
			}

			unset($arFields['ID']);
			if (isset($arFields['NAME']) && isset($arFields['LAST_NAME']))
				$arFields['FULL_NAME'] = trim($arFields['NAME'].' '.$arFields['LAST_NAME']);
			else
			{
				$dbRes = $DB->Query("SELECT NAME, LAST_NAME FROM b_crm_lead WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				$arRes = $dbRes->Fetch();
				$arFields['FULL_NAME'] = trim((isset($arFields['NAME'])? $arFields['NAME']: $arRes['NAME']).' '.(isset($arFields['LAST_NAME'])? $arFields['LAST_NAME']: $arRes['LAST_NAME']));
			}

			$sUpdate = $DB->PrepareUpdate('b_crm_lead', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if (strlen($sUpdate) > 0)
			{
				$DB->Query("UPDATE b_crm_lead SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				if(isset($arFields['COMPANY_TITLE']))
				{
					$newCompanyTitle = $arFields['COMPANY_TITLE'];
					$oldCompanyTitle = isset($arRow['COMPANY_TITLE']) ? $arRow['COMPANY_TITLE'] : '';
					if($newCompanyTitle !== $oldCompanyTitle)
					{
						\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::register(CCrmOwnerType::Lead, $ID, $newCompanyTitle);
					}
				}

				if(isset($arFields['LAST_NAME']) || isset($arFields['NAME']) || isset($arFields['SECOND_NAME']))
				{
					$lastName = isset($arFields['LAST_NAME'])
						? $arFields['LAST_NAME'] : (isset($arRow['LAST_NAME']) ? $arRow['LAST_NAME'] : '');
					$name = isset($arFields['NAME'])
						? $arFields['NAME'] : (isset($arRow['NAME']) ? $arRow['NAME'] : '');
					$secondName = isset($arFields['SECOND_NAME'])
						? $arFields['SECOND_NAME'] : (isset($arRow['SECOND_NAME']) ? $arRow['SECOND_NAME'] : '');

					\Bitrix\Crm\Integrity\DuplicatePersonCriterion::register(CCrmOwnerType::Lead, $ID, $lastName, $name, $secondName);
				}
				$bResult = true;
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				static $arNameFields = array("TITLE");
				$bClear = false;
				foreach($arNameFields as $val)
				{
					if(isset($arFields[$val]))
					{
						$bClear = true;
						break;
					}
				}
				if ($bClear)
				{
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Lead."_".$ID);
				}
			}

			CCrmPerms::UpdateEntityAttr('LEAD', $ID, $arEntityAttr);

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('LEAD', $ID, $arFields['FM']);

				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(
					CCrmOwnerType::Lead,
					$ID,
					'EMAIL',
					\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'EMAIL')
				);
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(
					CCrmOwnerType::Lead,
					$ID,
					'PHONE',
					\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'PHONE')
				);
			}
			\Bitrix\Crm\Integrity\DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Lead, $ID, array_merge($arRow, $arFields));

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'LEAD', true);
			}

			$arFields['ID'] = $ID;

			// Responsible user sync
			//CCrmActivity::Synchronize(CCrmOwnerType::Lead, $ID);
			// Sonet events registration
			$registerSonetEvent = isset($arOptions['REGISTER_SONET_EVENT']) && $arOptions['REGISTER_SONET_EVENT'] === true;

			if($bResult && isset($arFields['ASSIGNED_BY_ID']))
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Lead,
					$ID,
					CCrmSonetSubscriptionType::Responsibility,
					$arFields['ASSIGNED_BY_ID'],
					$arRow['ASSIGNED_BY_ID'],
					$registerSonetEvent
				);
			}

			if($bResult && $bCompare && $registerSonetEvent && !empty($sonetEventData))
			{
				$modifiedByID = intval($arFields['MODIFY_BY_ID']);
				foreach($sonetEventData as &$sonetEvent)
				{
					$sonetEventFields = &$sonetEvent['FIELDS'];
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Lead;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEvent['TYPE']);

					if (
						$logEventID
						&& CModule::IncludeModule("im")
					)
					{
						$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Lead, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if (
							$sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID
							&& $sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "lead_update",
								"NOTIFY_TAG" => "CRM|LEAD_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
							&& $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'] != $modifiedByID
						)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "lead_update",
								"NOTIFY_TAG" => "CRM|LEAD_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if (
							$sonetEvent['TYPE'] == CCrmLiveFeedEvent::Progress						
							&& $sonetEventFields['PARAMS']['START_STATUS_ID']
							&& $sonetEventFields['PARAMS']['FINAL_STATUS_ID']

						)
						{
							$assignedByID = (isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);
							$infos = CCrmStatus::GetStatus('STATUS');

							if (
								$assignedByID != $modifiedByID
								&& array_key_exists($sonetEventFields['PARAMS']['START_STATUS_ID'], $infos)
								&& array_key_exists($sonetEventFields['PARAMS']['FINAL_STATUS_ID'], $infos)
							)
							{
								$arMessageFields = array(
									"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
									"TO_USER_ID" => $assignedByID,
									"FROM_USER_ID" => $modifiedByID,
									"NOTIFY_TYPE" => IM_NOTIFY_FROM,
									"NOTIFY_MODULE" => "crm",
									"LOG_ID" => $logEventID,
									"NOTIFY_EVENT" => "lead_update",
									"NOTIFY_TAG" => "CRM|LEAD_PROGRESS|".$ID,
									"NOTIFY_MESSAGE" => GetMessage("CRM_LEAD_PROGRESS_IM_NOTIFY", Array(
										"#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>", 
										"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
										"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
									)),
									"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_LEAD_PROGRESS_IM_NOTIFY", Array(
										"#title#" => htmlspecialcharsbx($arFields['TITLE']), 
										"#start_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['START_STATUS_ID']]['NAME']),
										"#final_status_title#" => htmlspecialcharsbx($infos[$sonetEventFields['PARAMS']['FINAL_STATUS_ID']]['NAME'])
									))." (".$serverName.$url.")"
								);

								CIMNotify::Add($arMessageFields);
							}
						}
					}

					unset($sonetEventFields);
				}
				unset($sonetEvent);
			}

			if($bResult)
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmLeadUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}

		return $bResult;
	}

	public function Delete($ID, $arOptions = array())
	{
		global $DB, $APPLICATION;
		$ID = intval($ID);
		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		$this->LAST_ERROR = '';
		$APPLICATION->ResetException();

		if(is_array($arOptions)
			&& isset($arOptions['CHECK_DEPENDENCIES'])
			&& (bool)$arOptions['CHECK_DEPENDENCIES'])
		{
			$dbRes = self::GetListEx(
				array(),
				array('=ID' => $ID),
				false,
				false,
				array('TITLE', 'STATUS_ID', 'COMPANY_ID', 'CONTACT_ID')
			);
			$arFields = $dbRes ? $dbRes->Fetch() : null;
			if(is_array($arFields)
				&& isset($arFields['STATUS_ID'])
				&& $arFields['STATUS_ID'] === 'CONVERTED'
				&& (CCrmCompany::Exists(isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : 0)
					|| CCrmContact::Exists(isset($arFields['CONTACT_ID']) ? intval($arFields['CONTACT_ID']) : 0))
			)
			{
				$title = isset($arFields['TITLE']) && $arFields['TITLE'] !== '' ? $arFields['TITLE'] : $ID;
				$err = GetMessage('CRM_LEAD_DELETION_DEPENDENCIES_FOUND', array('#TITLE#' => $title));
				$this->LAST_ERROR = $err;
				$APPLICATION->throwException($err);
				return false;
			}
		}

		$sWherePerm = '';
		if ($this->bCheckPermission)
		{
			$arEntityAttr = $this->cPerms->GetEntityAttr('LEAD', $ID);
			$sEntityPerm = $this->cPerms->GetPermType('LEAD', 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$events = GetModuleEvents('crm', 'OnBeforeCrmLeadDelete');
		while ($arEvent = $events->Fetch())
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				$this->LAST_ERROR = $err;
				return false;
			}

		$tableName = CCrmLead::TABLE_NAME;
		$sSql = "DELETE FROM {$tableName} WHERE ID = {$ID}{$sWherePerm}";
		$obRes = $DB->Query($sSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($obRes) && $obRes->AffectedRowsCount() > 0)
		{
			CCrmSearch::DeleteSearch('LEAD', $ID);
			
			$DB->Query("DELETE FROM b_crm_entity_perms WHERE ENTITY='LEAD' AND ENTITY_ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);
			$CCrmFieldMulti = new CCrmFieldMulti();
			$CCrmFieldMulti->DeleteByElement('LEAD', $ID);
			$CCrmEvent = new CCrmEvent();
			$CCrmEvent->DeleteByElement('LEAD', $ID);

			\Bitrix\Crm\Integrity\DuplicateEntityRanking::unregisterEntityStatistics(CCrmOwnerType::Lead, $ID);
			\Bitrix\Crm\Integrity\DuplicatePersonCriterion::unregister(CCrmOwnerType::Lead, $ID);
			\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::unregister(CCrmOwnerType::Lead, $ID);
			\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::unregister(CCrmOwnerType::Lead, $ID);
			\Bitrix\Crm\Integrity\DuplicateIndexMismatch::unregisterEntity(CCrmOwnerType::Lead, $ID);

			$enableDupIndexInvalidation = is_array($arOptions) && isset($arOptions['ENABLE_DUP_INDEX_INVALIDATION'])
				? (bool)$arOptions['ENABLE_DUP_INDEX_INVALIDATION'] : true;
			if($enableDupIndexInvalidation)
			{
				\Bitrix\Crm\Integrity\DuplicateIndexBuilder::markAsJunk(CCrmOwnerType::Lead, $ID);
			}

			// Deletion of lead details
			CCrmProductRow::DeleteByOwner('L', $ID);
			CCrmProductRow::DeleteSettings('L', $ID);
			CCrmActivity::DeleteByOwner(CCrmOwnerType::Lead, $ID);

			CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Lead, $ID);
			CCrmLiveFeed::DeleteLogEvents(
				array(
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $ID
				)
			);

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Lead."_".$ID);
			}
		}
		return true;
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER;
		$this->LAST_ERROR = '';

		if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_FIELD_TITLE')))."<br />";

		if(is_string($arFields['OPPORTUNITY']) && $arFields['OPPORTUNITY'] !== '')
		{
			$arFields['OPPORTUNITY'] = str_replace(array(',', ' '), array('.', ''), $arFields['OPPORTUNITY']);
			//HACK: MSSQL returns '.00' for zero value
			if(strpos($arFields['OPPORTUNITY'], '.') === 0)
			{
				$arFields['OPPORTUNITY'] = '0'.$arFields['OPPORTUNITY'];
			}

			if (!preg_match('/^\d{1,}(\.\d{1,})?$/', $arFields['OPPORTUNITY']))
			{
				$this->LAST_ERROR .= GetMessage('CRM_LEAD_FIELD_OPPORTUNITY_INVALID')."<br />";
			}
		}

		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			if (!$CCrmFieldMulti->CheckComplexFields($arFields['FM']))
			{
				$this->LAST_ERROR .= $CCrmFieldMulti->LAST_ERROR;
			}
		}

		if(isset($arFields['BIRTHDATE']) && $arFields['BIRTHDATE'] !== '' && !CheckDateTime($arFields['BIRTHDATE']))
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => self::GetFieldCaption('BIRTHDATE')))."<br />";
		}

		$enableUserFildCheck = !(is_array($options) && isset($options['DISABLE_USER_FIELD_CHECK']) && $options['DISABLE_USER_FIELD_CHECK'] === true);
		if ($enableUserFildCheck)
		{
			// We have to prepare field data before check (issue #22966)
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false)));
			if(!$USER_FIELD_MANAGER->CheckFields(self::$sUFEntityID, $ID, $arFields))
			{
				$e = $APPLICATION->GetException();
				$this->LAST_ERROR .= $e->GetString();
			}
		}

		return $this->LAST_ERROR === '';
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif)
	{
		$arMsg = Array();

		if (isset($arFieldsOrig['TITLE']) && isset($arFieldsModif['TITLE'])
			&& $arFieldsOrig['TITLE'] != $arFieldsModif['TITLE'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'TITLE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_TITLE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['TITLE'])? $arFieldsOrig['TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['TITLE'])? $arFieldsModif['TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['NAME']) && isset($arFieldsModif['NAME'])
			&& $arFieldsOrig['NAME'] != $arFieldsModif['NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['NAME'])? $arFieldsOrig['NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['NAME'])? $arFieldsModif['NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['LAST_NAME']) && isset($arFieldsModif['LAST_NAME'])
			&& $arFieldsOrig['LAST_NAME'] != $arFieldsModif['LAST_NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'LAST_NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_LAST_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['LAST_NAME'])? $arFieldsOrig['LAST_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['LAST_NAME'])? $arFieldsModif['LAST_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['SECOND_NAME']) && isset($arFieldsModif['SECOND_NAME'])
			&& $arFieldsOrig['SECOND_NAME'] != $arFieldsModif['SECOND_NAME'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SECOND_NAME',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SECOND_NAME'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['SECOND_NAME'])? $arFieldsOrig['SECOND_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['SECOND_NAME'])? $arFieldsModif['SECOND_NAME']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['FM']) && isset($arFieldsModif['FM']))
			$arMsg = array_merge($arMsg, CCrmFieldMulti::CompareFields($arFieldsOrig['FM'], $arFieldsModif['FM']));

		if (isset($arFieldsOrig['POST']) && isset($arFieldsModif['POST'])
			&& $arFieldsOrig['POST'] != $arFieldsModif['POST'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'POST',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_POST'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['POST'])? $arFieldsOrig['POST']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['POST'])? $arFieldsModif['POST']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['ADDRESS']) && isset($arFieldsModif['ADDRESS'])
			&& $arFieldsOrig['ADDRESS'] != $arFieldsModif['ADDRESS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'ADDRESS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_ADDRESS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['ADDRESS'])? $arFieldsOrig['ADDRESS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['ADDRESS'])? $arFieldsModif['ADDRESS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['STATUS_ID']) && isset($arFieldsModif['STATUS_ID'])
			&& $arFieldsOrig['STATUS_ID'] != $arFieldsModif['STATUS_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('STATUS');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'STATUS_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_STATUS_ID'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['STATUS_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['STATUS_ID']))
			);
		}
		if (isset($arFieldsOrig['COMMENTS']) && isset($arFieldsModif['COMMENTS'])
			&& $arFieldsOrig['COMMENTS'] != $arFieldsModif['COMMENTS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMMENTS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMMENTS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMMENTS'])? $arFieldsOrig['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMMENTS'])? $arFieldsModif['COMMENTS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['STATUS_DESCRIPTION']) && isset($arFieldsModif['STATUS_DESCRIPTION'])
			&& $arFieldsOrig['STATUS_DESCRIPTION'] != $arFieldsModif['STATUS_DESCRIPTION'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'STATUS_DESCRIPTION',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_STATUS_DESCRIPTION'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['STATUS_DESCRIPTION'])? $arFieldsOrig['STATUS_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['STATUS_DESCRIPTION'])? $arFieldsModif['STATUS_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if ((isset($arFieldsOrig['OPPORTUNITY']) && isset($arFieldsModif['OPPORTUNITY']) && $arFieldsOrig['OPPORTUNITY'] != $arFieldsModif['OPPORTUNITY'])
			|| (isset($arFieldsOrig['CURRENCY_ID']) && isset($arFieldsModif['CURRENCY_ID']) && $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID']))
		{
			$arStatus = CCrmCurrencyHelper::PrepareListItems();
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'OPPORTUNITY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_OPPORTUNITY'),
				'EVENT_TEXT_1' => floatval($arFieldsOrig['OPPORTUNITY']).(($val = CrmCompareFieldsList($arStatus, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
				'EVENT_TEXT_2' => floatval($arFieldsModif['OPPORTUNITY']).(($val = CrmCompareFieldsList($arStatus, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
			);
		}

		if (isset($arFieldsOrig['SOURCE_ID']) && isset($arFieldsModif['SOURCE_ID'])
			&& $arFieldsOrig['SOURCE_ID'] != $arFieldsModif['SOURCE_ID'])
		{
			$arStatus = CCrmStatus::GetStatusList('SOURCE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SOURCE_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SOURCE_ID'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['SOURCE_ID'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['SOURCE_ID']))
			);
		}

//		if (isset($arFieldsOrig['PRODUCT_ID']) && isset($arFieldsModif['PRODUCT_ID'])
//			&& $arFieldsOrig['PRODUCT_ID'] != $arFieldsModif['PRODUCT_ID'])
//		{
//			$arStatus = CCrmStatus::GetStatusList('PRODUCT');
//			$arMsg[] = Array(
//				'ENTITY_FIELD' => 'PRODUCT_ID',
//				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_PRODUCT'),
//				'EVENT_TEXT_1' => CrmCompareFieldsList($arStatus, $arFieldsOrig['PRODUCT_ID']),
//				'EVENT_TEXT_2' => CrmCompareFieldsList($arStatus, $arFieldsModif['PRODUCT_ID'])
//			);
//		}

		if (isset($arFieldsOrig['COMPANY_TITLE']) && isset($arFieldsModif['COMPANY_TITLE'])
			&& $arFieldsOrig['COMPANY_TITLE'] != $arFieldsModif['COMPANY_TITLE'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMPANY_TITLE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANY_TITLE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['COMPANY_TITLE'])? $arFieldsOrig['COMPANY_TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['COMPANY_TITLE'])? $arFieldsModif['COMPANY_TITLE']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['SOURCE_DESCRIPTION']) && isset($arFieldsModif['SOURCE_DESCRIPTION'])
			&& $arFieldsOrig['SOURCE_DESCRIPTION'] != $arFieldsModif['SOURCE_DESCRIPTION'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'SOURCE_DESCRIPTION',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_SOURCE_DESCRIPTION'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['SOURCE_DESCRIPTION'])? $arFieldsOrig['SOURCE_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['SOURCE_DESCRIPTION'])? $arFieldsModif['SOURCE_DESCRIPTION']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['ASSIGNED_BY_ID']) && isset($arFieldsModif['ASSIGNED_BY_ID'])
			&& $arFieldsOrig['ASSIGNED_BY_ID'] != $arFieldsModif['ASSIGNED_BY_ID'])
		{
			$arUser = Array();
			$dbUsers = CUser::GetList(
				($sort_by = 'last_name'), ($sort_dir = 'asc'),
				array('ID' => implode('|', array(intval($arFieldsOrig['ASSIGNED_BY_ID']), intval($arFieldsModif['ASSIGNED_BY_ID'])))),
				array('SELECT' => array('NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'EMAIL'))
			);
			while ($arRes = $dbUsers->Fetch())
				$arUser[$arRes['ID']] = CUser::FormatName(CSite::GetNameFormat(false), $arRes);

			$arMsg[] = Array(
				'ENTITY_FIELD' => 'ASSIGNED_BY_ID',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_ASSIGNED_BY_ID'),
				'EVENT_TEXT_1' => CrmCompareFieldsList($arUser, $arFieldsOrig['ASSIGNED_BY_ID']),
				'EVENT_TEXT_2' => CrmCompareFieldsList($arUser, $arFieldsModif['ASSIGNED_BY_ID'])
			);
		}

		if (isset($arFieldsOrig['BIRTHDATE']) || isset($arFieldsModif['BIRTHDATE']))
		{
			$origBirthdate = isset($arFieldsOrig['BIRTHDATE']) ? $arFieldsOrig['BIRTHDATE'] : '';
			$modifBirthdate = isset($arFieldsModif['BIRTHDATE']) ? $arFieldsModif['BIRTHDATE'] : '';
			if($origBirthdate !== $modifBirthdate)
			{
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'BIRTHDATE',
					'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_BIRTHDATE'),
					'EVENT_TEXT_1' => $origBirthdate !== '' ? $origBirthdate : GetMessage('CRM_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => $modifBirthdate !== '' ? $modifBirthdate : GetMessage('CRM_FIELD_COMPARE_EMPTY')
				);
			}
		}

		return $arMsg;
	}

	public static function LoadProductRows($ID)
	{
		return CCrmProductRow::LoadRows('L', $ID);
	}

	public static function SaveProductRows($ID, $arRows, $checkPerms = true, $regEvent = true, $syncOwner = true)
	{
		$context = array();
		$arParams = self::GetByID($ID);
		if(is_array($arParams))
		{
			if(isset($arParams['CURRENCY_ID']))
			{
				$context['CURRENCY_ID'] = $arParams['CURRENCY_ID'];
			}

			if(isset($arParams['EXCH_RATE']))
			{
				$context['EXCH_RATE'] = $arParams['EXCH_RATE'];
			}
		}

		return CCrmProductRow::SaveRows('L', $ID, $arRows, $context, $checkPerms, $regEvent, $syncOwner);
	}

	public static function OnAccountCurrencyChange()
	{
		$accountCurrencyID = CCrmCurrency::GetAccountCurrencyID();
		if(!isset($accountCurrencyID[0]))
		{
			return;
		}

		$rs = self::GetList(
			array('ID' => 'ASC'),
			//array('!ACCOUNT_CURRENCY_ID' => $accountCurrencyID),
			array(),
			array('ID', 'CURRENCY_ID', 'OPPORTUNITY', 'EXCH_RATE')
		);

		$entity = new CCrmLead(false);
		while($arParams = $rs->Fetch())
		{
			$ID = intval($arParams['ID']);
			$entity->Update($ID, $arParams, false, false);
			$arRows = CCrmProductRow::LoadRows('D', $ID);

			$context = array();
			if(isset($arParams['CURRENCY_ID']))
			{
				$context['CURRENCY_ID'] = $arParams['CURRENCY_ID'];
			}

			if(isset($arParams['EXCH_RATE']))
			{
				$context['EXCH_RATE'] = $arParams['EXCH_RATE'];
			}

			if(count($arRows) > 0)
			{
				CCrmProductRow::SaveRows('D', $ID, $arRows, $context);
			}
		}
	}

	public function SynchronizeProductRows($ID)
	{

		$arTotalInfo = CCrmProductRow::CalculateTotalInfo('L', $ID);

		if (is_array($arTotalInfo))
		{
			$arFields = array(
				'OPPORTUNITY' => isset($arTotalInfo['OPPORTUNITY']) ? $arTotalInfo['OPPORTUNITY'] : 0.0,
				'TAX_VALUE' => isset($arTotalInfo['TAX_VALUE']) ? $arTotalInfo['TAX_VALUE'] : 0.0
			);

			$entity = new CCrmLead();
			$entity->Update($ID, $arFields);
		}
	}

	public static function CheckCreatePermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckUpdatePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckDeletePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckDeletePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckReadPermission($ID = 0, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'COMMENTS', 'COMPANY_TITLE');
		}

		// converts data from filter
		if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
		{
			if ($arFilter['FIND_list'] == 't_n_ln')
			{
				$find = $arFilter['FIND'];
				$arFilter['__INNER_FILTER'] = array(
					'LOGIC' => 'OR',
					'%TITLE' => $find,
					'$NAME' => $find,
					'%LAST_NAME' => $find,
					'%COMPANY_TITLE' => $find
				);
			}
			else
			{
				$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
			}
			unset($arFilter['FIND_list'], $arFilter['FIND']);
		}

		static $arImmutableFilters = array('FM', 'ID', 'CURRENCY_ID', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID', 'GRID_FILTER_APPLIED', 'GRID_FILTER_ID');
		foreach ($arFilter as $k => $v)
		{
			if(in_array($k, $arImmutableFilters, true))
			{
				continue;
			}

			$arMatch = array();

			if(in_array($k, array('PRODUCT_ID', 'STATUS_ID', 'SOURCE_ID', 'COMPANY_ID', 'CONTACT_ID')))
			{
				// Bugfix #23121 - to suppress comparison by LIKE
				$arFilter['='.$k] = $v;
				unset($arFilter[$k]);
			}
			elseif($k === 'ORIGINATOR_ID')
			{
				// HACK: build filter by internal entities
				$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if(strlen($v) > 0)
				{
					$arFilter['>='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if(strlen($v) > 0)
				{
					if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY' || $arMatch[1] == 'DATE_CLOSED')
						&& !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
					{
						$v .=  ' 23:59:59';
					}
					$arFilter['<='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (in_array($k, $arFilter2Logic))
			{
				// Bugfix #26956 - skip empty values in logical filter
				$v = trim($v);
				if($v !== '')
				{
					$arFilter['?'.$k] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif($k === 'STATUS_CONVERTED')
			{
				if($v !== '')
				{
					$arFilter[$v === 'N' ? '!@STATUS_ID' : '@STATUS_ID'] = array('JUNK', 'CONVERTED');
				}
				unset($arFilter['STATUS_CONVERTED']);
			}
			elseif ($k != 'LOGIC' && $k != '__INNER_FILTER' && strpos($k, 'UF_') !== 0 && strpos($k, '%') !== 0)
			{
				$arFilter['%'.$k] = $v;
				unset($arFilter[$k]);
			}
		}
	}

	public static function EnsureStatusesLoaded()
	{
		if (self::$LEAD_STATUSES === null)
		{
			$bFinished = $bFailed = false;
			$statuses = CCrmStatus::GetStatus('STATUS');
			self::$LEAD_STATUSES = $statuses;
			$statusesWork = $statusesSuccess = $statusesFailed = $statusesFinished = array();
			foreach ($statuses as $statuInfo)
			{
				if (!$bFinished && $statuInfo['STATUS_ID'] === 'CONVERTED')
				{
					$statusesSuccess[] = $statuInfo['STATUS_ID'];
					$bFinished = true;
				}
				if (!$bFailed && $statuInfo['STATUS_ID'] === 'JUNK')
					$bFailed = true;
				if ($bFinished)
					$statusesFinished[] = $statuInfo['STATUS_ID'];
				else
					$statusesWork[] = $statuInfo['STATUS_ID'];
				if ($bFailed)
					$statusesFailed[] = $statuInfo['STATUS_ID'];
			}
			self::$LEAD_STATUSES_BY_GROUP['WORK'] = $statusesWork;
			self::$LEAD_STATUSES_BY_GROUP['SUCCESS'] = $statusesSuccess;
			self::$LEAD_STATUSES_BY_GROUP['FAILED'] = $statusesFailed;
			self::$LEAD_STATUSES_BY_GROUP['FINISHED'] = $statusesFinished;
		}
	}

	public static function IsStatusFinished($statusID)
	{
		self::EnsureStatusesLoaded();
		return in_array($statusID, self::$LEAD_STATUSES_BY_GROUP['FINISHED'], true);
	}

	public static function PrepareFormattedName(&$arFields)
	{
		return CUser::FormatName(
			\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			array(
				'LOGIN' => '',
				'NAME' => isset($arFields['NAME']) ? $arFields['NAME'] : '',
				'SECOND_NAME' => isset($arFields['SECOND_NAME']) ? $arFields['SECOND_NAME'] : '',
				'LAST_NAME' => isset($arFields['LAST_NAME']) ? $arFields['LAST_NAME'] : ''
			),
			false,
			false
		);
	}

	public static function RebuildDuplicateIndex($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetListEx(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TITLE', 'COMPANY_TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ADDRESS', 'DATE_MODIFY')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		$emails = array();
		$phones = array();

		$dbResultMultiFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'=ENTITY_ID' => CCrmOwnerType::LeadName,
				'@TYPE_ID' => array('EMAIL', 'PHONE'),
				'@ELEMENT_ID' => $IDs
			)
		);

		if(is_object($dbResultMultiFields))
		{
			while($multiFields = $dbResultMultiFields->Fetch())
			{
				$elementID = isset($multiFields['ELEMENT_ID']) ? $multiFields['ELEMENT_ID'] : '';
				$typeID = isset($multiFields['TYPE_ID']) ? $multiFields['TYPE_ID'] : '';
				$value = isset($multiFields['VALUE']) ? $multiFields['VALUE'] : '';

				if($elementID === '' || $typeID === '' || $value === '')
				{
					continue;
				}

				if($typeID === 'EMAIL')
				{
					if(!isset($emails[$elementID]))
					{
						$emails[$elementID] = array();
					}
					$emails[$elementID][] = $value;
				}
				elseif($typeID === 'PHONE')
				{
					if(!isset($phones[$elementID]))
					{
						$phones[$elementID] = array();
					}
					$phones[$elementID][] = $value;
				}
			}
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);

			$companyTitle = isset($fields['COMPANY_TITLE']) ? $fields['COMPANY_TITLE'] : '';
			if($companyTitle !== '')
			{
				\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::register(CCrmOwnerType::Lead, $ID, $companyTitle);
			}

			$lastName = isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : '';
			if($lastName !== '')
			{
				\Bitrix\Crm\Integrity\DuplicatePersonCriterion::register(
					CCrmOwnerType::Lead,
					$ID,
					$lastName,
					isset($fields['NAME']) ? $fields['NAME'] : '',
					isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : ''
				);
			}

			$key = strval($ID);
			if(isset($emails[$key]))
			{
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Lead, $ID, 'EMAIL', $emails[$key]);
			}

			if(isset($phones[$key]))
			{
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Lead, $ID, 'PHONE', $phones[$key]);
			}

			\Bitrix\Crm\Integrity\DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Lead, $ID, $fields);
		}
	}
}
?>
