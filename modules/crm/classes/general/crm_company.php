<?php
IncludeModuleLangFile(__FILE__);

class CAllCrmCompany
{
	static public $sUFEntityID = 'CRM_COMPANY';
	public $LAST_ERROR = '';
	public $cPerms = null;
	protected $bCheckPermission = true;
	const TABLE_ALIAS = 'L';
	protected static $TYPE_NAME = 'COMPANY';
	private static $FIELD_INFOS = null;

	function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_COMPANY_FIELD_{$fieldName}");
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
				'COMPANY_TYPE' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'COMPANY_TYPE'
				),
				'LOGO' => array(
					'TYPE' => 'file'
				),
				'ADDRESS' => array(
					'TYPE' => 'string'
				),
				'ADDRESS_LEGAL' => array(
					'TYPE' => 'string'
				),
				'BANKING_DETAILS' => array(
					'TYPE' => 'string'
				),
				'INDUSTRY' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'INDUSTRY'
				),
				'EMPLOYEES' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'EMPLOYEES'
				),
				'CURRENCY_ID' => array(
					'TYPE' => 'crm_currency'
				),
				'REVENUE' => array(
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
				'LEAD_ID' => array(
					'TYPE' => 'crm_lead',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ORIGINATOR_ID' => array(
					'TYPE' => 'string'
				),
				'ORIGIN_ID' => array(
					'TYPE' => 'string'
				)
			);
		}

		return self::$FIELD_INFOS;
	}
	public static function GetFields($arOptions = null)
	{
		$assignedByJoin = 'LEFT JOIN b_user U ON L.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON L.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON L.MODIFY_BY_ID = U3.ID';

		$result = array(
			'ID' => array('FIELD' => 'L.ID', 'TYPE' => 'int'),
			'COMPANY_TYPE' => array('FIELD' => 'L.COMPANY_TYPE', 'TYPE' => 'string'),
			'TITLE' => array('FIELD' => 'L.TITLE', 'TYPE' => 'string'),
			'LOGO' => array('FIELD' => 'L.LOGO', 'TYPE' => 'string'),
			'LEAD_ID' => array('FIELD' => 'L.LEAD_ID', 'TYPE' => 'int'),

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

			'ADDRESS' => array('FIELD' => 'L.ADDRESS', 'TYPE' => 'string'),
			'ADDRESS_LEGAL' => array('FIELD' => 'L.ADDRESS_LEGAL', 'TYPE' => 'string'),
			'BANKING_DETAILS' => array('FIELD' => 'L.BANKING_DETAILS', 'TYPE' => 'string'),

			'INDUSTRY' => array('FIELD' => 'L.INDUSTRY', 'TYPE' => 'string'),
			'REVENUE' => array('FIELD' => 'L.REVENUE', 'TYPE' => 'string'),
			'CURRENCY_ID' => array('FIELD' => 'L.CURRENCY_ID', 'TYPE' => 'string'),
			'EMPLOYEES' => array('FIELD' => 'L.EMPLOYEES', 'TYPE' => 'string'),
			'COMMENTS' => array('FIELD' => 'L.COMMENTS', 'TYPE' => 'string'),

			'DATE_CREATE' => array('FIELD' => 'L.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => 'L.DATE_MODIFY', 'TYPE' => 'datetime'),

			'OPENED' => array('FIELD' => 'L.OPENED', 'TYPE' => 'char'),
			'ORIGINATOR_ID' => array('FIELD' => 'L.ORIGINATOR_ID', 'TYPE' => 'string'), //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => array('FIELD' => 'L.ORIGIN_ID', 'TYPE' => 'string') //ITEM ID IN EXTERNAL SYSTEM
		);

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		$additionalFields = is_array($arOptions) && isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('ACTIVITY', $additionalFields, true))
			{
				$commonActivityJoin = CCrmActivity::PrepareJoin(0, CCrmOwnerType::Company, 'L', 'AC', 'UAC', 'ACUSR');

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
					$activityJoin = CCrmActivity::PrepareJoin($userID, CCrmOwnerType::Company, 'L', 'A', 'UA', '');

					$result['ACTIVITY_ID'] = array('FIELD' => 'UA.ACTIVITY_ID', 'TYPE' => 'int', 'FROM' => $activityJoin);
					$result['ACTIVITY_TIME'] = array('FIELD' => 'UA.ACTIVITY_TIME', 'TYPE' => 'datetime', 'FROM' => $activityJoin);
					$result['ACTIVITY_SUBJECT'] = array('FIELD' => 'A.SUBJECT', 'TYPE' => 'string', 'FROM' => $activityJoin);
				}
			}
		}

		return $result;
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
			CCrmCompany::DB_TYPE,
			CCrmCompany::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'COMPANY',
			array('CCrmCompany', 'BuildPermSql')
		);
		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function CreateListBuilder(array $arFieldOptions = null)
	{
		return new CCrmEntityListBuilder(
			CCrmCompany::DB_TYPE,
			CCrmCompany::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields($arFieldOptions),
			self::$sUFEntityID,
			'COMPANY',
			array('CCrmCompany', 'BuildPermSql')
		);
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

    /**
     *Функция получает список компаний CRM
     *
     * @param array $arOrder - Порядок сортировки. массив вида
     * @param array $arFilter - Массив вида array("фильтруемое поле"=>"значения фильтра" [, ...]). "фильтруемое поле" может принимать значения:
     *
     * <ul>
     *<li><b>ID</b> - ид</li>
     *<li><b>ADDRESS</b> - физический адрес</li>
     *<li><b>ADDRESS_LEGAL</b> - юридический адрес</li>
     *<li><b>BANKING_DETAILS</b> - банковские реквизиты</li>
     *<li><b>TITLE</b> - название</li>
     *<li><b>COMPANY_TYPE</b> - тип компании</li>
     *<li><b>INDUSTRY</b> - сфера деятельности</li>
     *<li><b>REVENUE</b> - годовой оборот</li>
     *<li><b>CURRENCY_ID</b> - валюта</li>
     *<li><b>EMPLOYEES</b> - количество сотрудников</li>
     *<li><b>COMMENTS</b> - комментарии</li>
     *<li><b>CREATED_BY_ID</b> - кем создана</li>
     *<li><b>MODIFY_BY_ID</b> - кем изменена</li>
     *<li><b>DATE_CREATE</b> - дата создания</li>
     *<li><b>DATE_MODIFY</b> - дата изменения</li>
     *<li><b>OPENED</b> - открытая да/нет</li>
     *<li><b>CREATED_BY_LOGIN</b></li>
     *<li><b>CREATED_BY_NAME</b></li>
     *<li><b>CREATED_BY_LAST_NAME</b></li>
     *<li><b>CREATED_BY_SECOND_NAME</b></li>
     *<li><b>MODIFY_BY_LOGIN</b></li>
     *<li><b>MODIFY_BY_NAME</b></li>
     *<li><b>MODIFY_BY_LAST_NAME</b></li>
     *<li><b>MODIFY_BY_SECOND_NAME</b></li>
     * </ul>
     *
     * Также в фильтре присутствуют пользовательские поля типа <b>UF_*</b>
     *
     *
     * @param array $arSelect - Массив возвращаемых полей элемента. Содержит поля элемента, а также пользовательские поля. Для выбора всех пользовательских полей необходимо указать UF_*.
     * @param array $nPageTop - Необязательный параметр, поумолчанию равен <b>false</b>. Количество возвращаемых записей "сверху" выборки.
     *
     * @return CDBResult - Возвращает объект класса CDBResult.
     */
	public static function GetList($arOrder = Array('DATE_CREATE' => 'DESC'), $arFilter = Array(), $arSelect = Array(), $nPageTop = false)
	{
		global $DB, $USER_FIELD_MANAGER;

		// fields
		$arFields = array(
			'ID' => 'L.ID',
			'LEAD_ID' => 'L.LEAD_ID',
			'ADDRESS' => 'L.ADDRESS',
			'ADDRESS_LEGAL' => 'L.ADDRESS_LEGAL',
			'BANKING_DETAILS' => 'L.BANKING_DETAILS',
			'TITLE' => 'L.TITLE',
			'LOGO' => 'L.LOGO',
			'COMPANY_TYPE' => 'L.COMPANY_TYPE',
			'INDUSTRY' => 'L.INDUSTRY',
			'REVENUE' => 'L.REVENUE',
			'CURRENCY_ID' => 'L.CURRENCY_ID',
			'EMPLOYEES' => 'L.EMPLOYEES',
			'COMMENTS' => 'L.COMMENTS',
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'CREATED_BY_ID' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'MODIFY_BY_ID' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => $DB->DateToCharFunction('L.DATE_CREATE'),
			'DATE_MODIFY' => $DB->DateToCharFunction('L.DATE_MODIFY'),
			'OPENED' => 'L.OPENED',
			'ORIGINATOR_ID' => 'L.ORIGINATOR_ID', //EXTERNAL SYSTEM THAT OWNS THIS ITEM
			'ORIGIN_ID' => 'L.ORIGIN_ID', //ITEM ID IN EXTERNAL SYSTEM
			'CREATED_BY_LOGIN' => 'U2.LOGIN',
			'CREATED_BY_NAME' => 'U2.NAME',
			'CREATED_BY_LAST_NAME' => 'U2.LAST_NAME',
			'CREATED_BY_SECOND_NAME' => 'U2.SECOND_NAME',
			'MODIFY_BY_LOGIN' => 'U3.LOGIN',
			'MODIFY_BY_NAME' => 'U3.NAME',
			'MODIFY_BY_LAST_NAME' => 'U3.LAST_NAME',
			'MODIFY_BY_SECOND_NAME' => 'U3.SECOND_NAME'
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
			$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'FILTER' => $arFilter['FM']));
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
			&& (!array_key_exists('CHECK_PERMISSIONS', $arFilter) || $arFilter['CHECK_PERMISSIONS'] !== 'N')
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
			'LEAD_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.LEAD_ID',
				'FIELD_TYPE' => 'int',
				'JOIN' => false
			),
			'COMPANY_TYPE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.COMPANY_TYPE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'INDUSTRY' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.INDUSTRY',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'TITLE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.TITLE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'REVENUE' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.REVENUE',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'CURRENCY_ID' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.CURRENCY_ID',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'EMPLOYEES' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.EMPLOYEES',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDRESS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDRESS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'BANKING_DETAILS' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.BANKING_DETAILS',
				'FIELD_TYPE' => 'string',
				'JOIN' => false
			),
			'ADDRESS_LEGAL' => array(
				'TABLE_ALIAS' => 'L',
				'FIELD_NAME' => 'L.ADDRESS_LEGAL',
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
		);

		$obQueryWhere->SetFields($arWhereFields);
		if (!is_array($arFilter))
			$arFilter = array();
		$sQueryWhereFields = $obQueryWhere->GetQuery($arFilter);

		$sSqlSearch = '';
		foreach($arSqlSearch as $r)
			if(strlen($r) > 0)
				$sSqlSearch .= "\n\t\t\t\tAND  ($r) ";
		$CCrmUserType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], self::$sUFEntityID);
		$CCrmUserType->ListPrepareFilter($arFilter);
		$r = $obUserFieldsSql->GetFilter();
		if (strlen($r) > 0)
			$sSqlSearch .= "\n\t\t\t\tAND ($r) ";

		if (!empty($sQueryWhereFields))
			$sSqlSearch .= "\n\t\t\t\tAND ($sQueryWhereFields) ";

		$arFieldsOrder = array(
			'CREATED_BY' => 'L.CREATED_BY_ID',
			'MODIFY_BY' => 'L.MODIFY_BY_ID',
			'DATE_CREATE' => 'L.DATE_CREATE',
			'DATE_MODIFY' => 'L.DATE_MODIFY'
		);

		// order
		$arSqlOrder = Array();
		if (!is_array($arOrder))
			$arOrder = Array('DATE_CREATE' => 'DESC');
		foreach($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtolower($order);
			if ($order != 'asc')
				$order = 'desc';

			if (isset($arFieldsOrder[$by]))
				$arSqlOrder[$by] = " {$arFieldsOrder[$by]} $order ";
			elseif(isset($arFields[$by]) && $by != 'ADDRESS')
				$arSqlOrder[$by] = " L.$by $order ";
			elseif($s = $obUserFieldsSql->GetOrder($by))
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
				b_crm_company L $sSqlJoin
				{$obUserFieldsSql->GetJoin('L.ID')}
			WHERE
				1=1 $sSqlSearch
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

		$dbRes = CCrmCompany::GetListEx(array(), $arFilter);
		return $dbRes->Fetch();
	}

	static public function BuildPermSql($sAliasPrefix = 'L', $mPermType = 'READ', $arOptions = array())
	{
		return CCrmPerms::BuildSql('COMPANY', $sAliasPrefix, $mPermType, $arOptions);
	}

	public function Add(array &$arFields, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		if(!is_array($options))
		{
			$options = array();
		}

		$this->LAST_ERROR = '';
		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		if (isset($arFields['ID']))
			unset($arFields['ID']);

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		if (!isset($arFields['CREATED_BY_ID']) || intval($arFields['CREATED_BY_ID']) <= 0)
			$arFields['CREATED_BY_ID'] = $iUserId;
		if (!isset($arFields['MODIFY_BY_ID']) || intval($arFields['MODIFY_BY_ID']) <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;
		if (!isset($arFields['ASSIGNED_BY_ID']) || intval($arFields['ASSIGNED_BY_ID']) <= 0)
			$arFields['ASSIGNED_BY_ID'] = $iUserId;

		if (isset($arFields['REVENUE']))
			$arFields['REVENUE'] = floatval($arFields['REVENUE']);

		if (!$this->CheckFields($arFields, false, $options))
		{
			$result = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			$arAttr = array();
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
				$sEntityPerm = $userPerms->GetPermType('COMPANY', $sPermission, $arEntityAttr);
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
			$sEntityPerm = $userPerms->GetPermType('COMPANY', $sPermission, $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

			if(isset($arFields['LOGO'])
				&& is_array($arFields['LOGO'])
				&& strlen(CFile::CheckImageFile($arFields['LOGO'])) === 0)
			{
				$arFields['LOGO']['MODULE_ID'] = 'crm';
				CFile::SaveForDB($arFields, 'LOGO', 'crm');
			}

			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmCompanyAdd');
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
						$this->LAST_ERROR = GetMessage('CRM_COMPANY_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$ID = intval($DB->Add('b_crm_company', $arFields, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__));
			$result = $arFields['ID'] = $ID;
			CCrmPerms::UpdateEntityAttr('COMPANY', $ID, $arEntityAttr);

			\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::register(CCrmOwnerType::Company, $ID, $arFields['TITLE']);
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('COMPANY', $ID, $arFields['FM']);

				$emails = \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'EMAIL');
				if(!empty($emails))
				{
					\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Company, $ID, 'EMAIL', $emails);
				}

				$phones = Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'PHONE');
				if(!empty($phones))
				{
					\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Company, $ID, 'PHONE', $phones);
				}
			}
			\Bitrix\Crm\Integrity\DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Company, $ID, $arFields);

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'COMPANY', true);
			}
			if (isset($arFields['CONTACT_ID']) && is_array($arFields['CONTACT_ID']))
			{
					$CCrmContact = new CCrmContact();
					$CCrmContact->UpdateCompanyId($arFields['CONTACT_ID'], $arFields['ID']);

					if (isset($GLOBALS["USER"]))
					{
						if (!class_exists('CUserOptions'))
							include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/'.$GLOBALS['DBType'].'/favorites.php');

						CUserOptions::SetOption('crm', 'crm_contact_search', array('last_selected' => implode(',', $arFields['CONTACT_ID'])));
					}
			}

			if(isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true)
			{
				$revenue = round((isset($arFields['REVENUE']) ? doubleval($arFields['REVENUE']) : 0.0), 2);
				$currencyID = isset($arFields['CURRENCY_ID']) ? $arFields['CURRENCY_ID'] : '';
				if($currencyID === '')
				{
					$currencyID = CCrmCurrency::GetBaseCurrencyID();
				}

				$multiFields = isset($arFields['FM']) ? $arFields['FM'] : null;
				$phones = CCrmFieldMulti::ExtractValues($multiFields, 'PHONE');
				$emails = CCrmFieldMulti::ExtractValues($multiFields, 'EMAIL');
				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				$createdByID = intval($arFields['CREATED_BY_ID']);

				$liveFeedFields = array(
					'USER_ID' => $createdByID,
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $ID,
					'TITLE' => GetMessage('CRM_COMPANY_EVENT_ADD'),
					'MESSAGE' => '',
					'PARAMS' => array(
						'TITLE' => $arFields['TITLE'],
						'LOGO_ID' => isset($arFields['LOGO']) ? $arFields['LOGO'] : '',
						'TYPE' => isset($arFields['COMPANY_TYPE']) ? $arFields['COMPANY_TYPE'] : '',
						'REVENUE' => strval($revenue),
						'CURRENCY_ID' => $currencyID,
						'PHONES' => $phones,
						'EMAILS' => $emails,
						'AUTHOR_ID' => intval($arFields['CREATED_BY_ID']),
						'RESPONSIBLE_ID' => $assignedByID
					)
				);
				CCrmSonetSubscription::RegisterSubscription(
					CCrmOwnerType::Company,
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
					$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Company, $ID);
					$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

					$arMessageFields = array(
						"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
						"TO_USER_ID" => $assignedByID,
						"FROM_USER_ID" => $createdByID,
						"NOTIFY_TYPE" => IM_NOTIFY_FROM,
						"NOTIFY_MODULE" => "crm",
						"LOG_ID" => $logEventID,
						"NOTIFY_EVENT" => "company_add",
						"NOTIFY_TAG" => "CRM|COMPANY_RESPONSIBLE|".$ID,
						"NOTIFY_MESSAGE" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
						"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
					);
					CIMNotify::Add($arMessageFields);
				}
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmCompanyAdd');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			}

			if(isset($arFields['ORIGIN_ID']) && $arFields['ORIGIN_ID'] !== '')
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterExternalCrmCompanyAdd');
				while ($arEvent = $afterEvents->Fetch())
				{
					ExecuteModuleEventEx($arEvent, array(&$arFields));
				}
			}
		}
		
		return $result;
	}
	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$arUserAttr = CCrmPerms::BuildUserEntityAttr($userID);
		return array_merge($arResult, $arUserAttr['INTRANET']);
	}
	private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	public function Update($ID, array &$arFields, $bCompare = true, $bUpdateSearch = true, $options = array())
	{
		global $DB;

		$this->LAST_ERROR = '';
		$ID = (int) $ID;
		if(!is_array($options))
		{
			$options = array();
		}

		$arFilterTmp = Array('ID' => $ID);
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

		if(!isset($arFields['MODIFY_BY_ID']) || $arFields['MODIFY_BY_ID'] <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;
		if (isset($arFields['REVENUE']))
			$arFields['REVENUE'] = floatval($arFields['REVENUE']);
		if (isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
			unset($arFields['ASSIGNED_BY_ID']);

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;
		if (!$this->CheckFields($arFields, $ID, $options))
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		else
		{
			if($this->bCheckPermission && !CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $this->cPerms))
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$beforeEvents = GetModuleEvents('crm', 'OnBeforeCrmCompanyUpdate');
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
						$this->LAST_ERROR = GetMessage('CRM_COMPANY_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			$arAttr = array();
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$sEntityPerm = $this->cPerms->GetPermType('COMPANY', 'WRITE', $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
			//Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
			if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
			{
				$arFields['OPENED'] = 'Y';
			}

			if(isset($arFields['LOGO'])
				&& is_array($arFields['LOGO'])
				&& strlen(CFile::CheckImageFile($arFields['LOGO'])) === 0)
			{
				$arFields['LOGO']['MODULE_ID'] = 'crm';
				if($arFields['LOGO_del'] == 'Y' && !empty($arRow['LOGO']))
					CFile::Delete($arRow['LOGO']);

				CFile::SaveForDB($arFields, 'LOGO', 'crm');
				if($arFields['LOGO_del'] == 'Y' && !isset($arFields['LOGO']))
					$arFields['LOGO'] = '';
			}

			$sonetEventData = array();
			if ($bCompare)
			{
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $ID)
				);
				$arRow['FM'] = array();
				while($ar = $res->Fetch())
					$arRow['FM'][$ar['TYPE_ID']][$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);

				$arEvents = self::CompareFields($arRow, $arFields);
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'COMPANY';
					$arEvent['ENTITY_ID'] = $ID;
					$arEvent['EVENT_TYPE'] = 1;
					if (!isset($arEvent['USER_ID']))
						$arEvent['USER_ID'] = $iUserId;

					$CCrmEvent = new CCrmEvent();
					$eventID = $CCrmEvent->Add($arEvent, $this->bCheckPermission);
					if(is_int($eventID) && $eventID > 0)
					{
						$fieldID = isset($arEvent['ENTITY_FIELD']) ? $arEvent['ENTITY_FIELD'] : '';
						if($fieldID === '')
						{
							continue;
						}

						switch($fieldID)
						{
							case 'ASSIGNED_BY_ID':
							{
								$sonetEventData[] = array(
									'TYPE' => CCrmLiveFeedEvent::Responsible,
									'FIELDS' => array(
										//'EVENT_ID' => $eventID,
										'TITLE' => GetMessage('CRM_COMPANY_EVENT_UPDATE_ASSIGNED_BY'),
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
										'TITLE' => GetMessage('CRM_COMPANY_EVENT_UPDATE_TITLE'),
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

			unset($arFields["ID"]);

			$sUpdate = $DB->PrepareUpdate('b_crm_company', $arFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			if (strlen($sUpdate) > 0)
			{
				$DB->Query("UPDATE b_crm_company SET {$sUpdate} WHERE ID = {$ID}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				$bResult = true;
				$newTitle = isset($arFields['TITLE']) ? $arFields['TITLE'] : '';
				$oldTitle = isset($arRow['TITLE']) ? $arRow['TITLE'] : '';
				if($newTitle !== '' && $newTitle !== $oldTitle)
				{
					\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::register(CCrmOwnerType::Company, $ID, $newTitle);
				}
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
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Company."_".$ID);
				}
			}

			CCrmPerms::UpdateEntityAttr('COMPANY', $ID, $arEntityAttr);

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('COMPANY', $ID, $arFields['FM']);

				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(
					CCrmOwnerType::Company,
					$ID,
					'EMAIL',
					\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'EMAIL')
				);
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(
					CCrmOwnerType::Company,
					$ID,
					'PHONE',
					\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::extractMultifieldsValues($arFields['FM'], 'PHONE')
				);
			}
			\Bitrix\Crm\Integrity\DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Company, $ID, array_merge($arRow, $arFields));

			if($bUpdateSearch)
			{
				CCrmSearch::UpdateSearch(array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), 'COMPANY', true);
			}

			$arFields['ID'] = $ID;
			if (isset($arFields['CONTACT_ID']) && is_array($arFields['CONTACT_ID']))
			{
				if (!empty($arFields['CONTACT_ID']))
				{
					$arCurrentContact = Array();
					$res = CCrmContact::GetContactByCompanyId($arFields['ID']);
					while($ar = $res->Fetch())
						$arCurrentContact[] = $ar['ID'];

					$arAdd = array_diff($arFields['CONTACT_ID'], $arCurrentContact);
					$arDelete = array_diff($arCurrentContact, $arFields['CONTACT_ID']);

					$CCrmContact = new CCrmContact();
					$CCrmContact->UpdateCompanyId($arAdd, $arFields['ID']);
					$CCrmContact->UpdateCompanyId($arDelete, 0);

					if (isset($GLOBALS["USER"]))
					{
						if (!class_exists('CUserOptions'))
							include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/classes/".$GLOBALS['DBType']."/favorites.php");

						CUserOptions::SetOption("crm", "crm_contact_search", array('last_selected' => implode(',', $arAdd)));
					}
				}
				else
				{
					$arDelete = Array();
					$res = CCrmContact::GetContactByCompanyId($arFields['ID']);
					while($ar = $res->Fetch())
						$arDelete[] = $ar['ID'];

					$CCrmContact = new CCrmContact();
					$CCrmContact->UpdateCompanyId($arDelete, 0);
				}
			}

			$registerSonetEvent = isset($options['REGISTER_SONET_EVENT']) && $options['REGISTER_SONET_EVENT'] === true;

			if($bResult && isset($arFields['ASSIGNED_BY_ID']))
			{
				CCrmSonetSubscription::ReplaceSubscriptionByEntity(
					CCrmOwnerType::Company,
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
					$sonetEventFields['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
					$sonetEventFields['ENTITY_ID'] = $ID;
					$sonetEventFields['USER_ID'] = $modifiedByID;

					$logEventID = CCrmLiveFeed::CreateLogEvent($sonetEventFields, $sonetEvent['TYPE']);

					if (
						$logEventID
						&& $sonetEvent['TYPE'] == CCrmLiveFeedEvent::Responsible
						&& CModule::IncludeModule("im")
					)
					{
						$url = CCrmOwnerType::GetShowUrl(CCrmOwnerType::Company, $ID);
						$serverName = (CMain::IsHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0) ? SITE_SERVER_NAME : COption::GetOptionString("main", "server_name", ""));

						if ($sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'] != $modifiedByID)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['FINAL_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "company_update",
								"NOTIFY_TAG" => "CRM|COMPANY_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_COMPANY_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}

						if ($sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'] != $modifiedByID)
						{
							$arMessageFields = array(
								"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
								"TO_USER_ID" => $sonetEventFields['PARAMS']['START_RESPONSIBLE_ID'],
								"FROM_USER_ID" => $modifiedByID,
								"NOTIFY_TYPE" => IM_NOTIFY_FROM,
								"NOTIFY_MODULE" => "crm",
								"LOG_ID" => $logEventID,
								"NOTIFY_EVENT" => "company_update",
								"NOTIFY_TAG" => "CRM|COMPANY_RESPONSIBLE|".$ID,
								"NOTIFY_MESSAGE" => GetMessage("CRM_COMPANY_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => "<a href=\"".$url."\" class=\"bx-notifier-item-action\">".htmlspecialcharsbx($arFields['TITLE'])."</a>")),
								"NOTIFY_MESSAGE_OUT" => GetMessage("CRM_COMPANY_NOT_RESPONSIBLE_IM_NOTIFY", Array("#title#" => htmlspecialcharsbx($arFields['TITLE'])))." (".$serverName.$url.")"
							);

							CIMNotify::Add($arMessageFields);
						}
					}

					unset($sonetEventFields);
				}
				unset($sonetEvent);
			}

			if($bResult)
			{
				$afterEvents = GetModuleEvents('crm', 'OnAfterCrmCompanyUpdate');
				while ($arEvent = $afterEvents->Fetch())
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}
		return $bResult;
	}
	public static function RebuildEntityAccessAttrs($IDs)
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
			array('ID', 'ASSIGNED_BY_ID', 'OPENED')
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

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			CCrmPerms::UpdateEntityAttr('COMPANY', $ID, $entityAttrs);
		}
	}

    /**
     * Удаляет выранную компанию из CRM.<br/>
     * Перед изменением компании вызываются обработчики события <b>OnBeforeCrmDealDelete</b> из которых можно изменить значения полей или отменить изменение компании вернув сообщение об ошибке.<br/>
     * После изменения элемента вызывается само событие <b>OnAfterCrmDealDelete</b>.<br/>
     *
     * @param $ID - id компании.
     * @param array $arOptions - массив опций, необязательный параметр.
     *
     * @return bool - возвращает true, если компания удаленаб иначе false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentTypeException
     */

    public function Delete($ID, $arOptions = array())
	{
		global $DB, $APPLICATION;

		$ID = intval($ID);
		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		$sWherePerm = '';
		if ($this->bCheckPermission)
		{
			$arEntityAttr = $this->cPerms->GetEntityAttr('COMPANY', $ID);
			$sEntityPerm = $this->cPerms->GetPermType('COMPANY', 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$APPLICATION->ResetException();
		$events = GetModuleEvents('crm', 'OnBeforeCrmCompanyDelete');
		while ($arEvent = $events->Fetch())
			if(ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}

		$obRes = $DB->Query("DELETE FROM b_crm_company WHERE ID = {$ID}{$sWherePerm}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($obRes) && $obRes->AffectedRowsCount() > 0)
		{
			CCrmSearch::DeleteSearch('COMPANY', $ID);

			$DB->Query("DELETE FROM b_crm_entity_perms WHERE ENTITY='COMPANY' AND ENTITY_ID = $ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);
			$CCrmFieldMulti = new CCrmFieldMulti();
			$CCrmFieldMulti->DeleteByElement('COMPANY', $ID);
			$CCrmEvent = new CCrmEvent();
			$CCrmEvent->DeleteByElement('COMPANY', $ID);

			\Bitrix\Crm\Integrity\DuplicateEntityRanking::unregisterEntityStatistics(CCrmOwnerType::Company, $ID);
			\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::unregister(CCrmOwnerType::Company, $ID);
			\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::unregister(CCrmOwnerType::Company, $ID);
			\Bitrix\Crm\Integrity\DuplicateIndexMismatch::unregisterEntity(CCrmOwnerType::Company, $ID);

			$enableDupIndexInvalidation = is_array($arOptions) && isset($arOptions['ENABLE_DUP_INDEX_INVALIDATION'])
				? (bool)$arOptions['ENABLE_DUP_INDEX_INVALIDATION'] : true;
			if($enableDupIndexInvalidation)
			{
				\Bitrix\Crm\Integrity\DuplicateIndexBuilder::markAsJunk(CCrmOwnerType::Company, $ID);
			}

			CCrmActivity::DeleteByOwner(CCrmOwnerType::Company, $ID);

			CCrmSonetSubscription::UnRegisterSubscriptionByEntity(CCrmOwnerType::Company, $ID);
			CCrmLiveFeed::DeleteLogEvents(
				array(
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $ID
				)
			);

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Company."_".$ID);
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

		if (isset($arFields['FM']) && is_array($arFields['FM']))
		{
			$CCrmFieldMulti = new CCrmFieldMulti();
			if (!$CCrmFieldMulti->CheckComplexFields($arFields['FM']))
			{
				$this->LAST_ERROR .= $CCrmFieldMulti->LAST_ERROR;
			}
		}

		if (isset($arFields['LOGO']) && is_array($arFields['LOGO']))
		{
			if (($strError = CFile::CheckFile($arFields['LOGO'], 0, 0, CFile::GetImageExtensions())) != '')
				$this->LAST_ERROR .= $strError."<br />";
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

		if(strlen($this->LAST_ERROR) > 0)
			return false;

		return true;
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif)
	{
		$arMsg = Array();

		if (isset($arFieldsOrig['TITLE']) && isset($arFieldsModif['TITLE'])
			&& $arFieldsOrig['TITLE'] != $arFieldsModif['TITLE'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'TITLE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_TITLE'),
				'EVENT_TEXT_1' => $arFieldsOrig['TITLE'],
				'EVENT_TEXT_2' => $arFieldsModif['TITLE'],
			);

		if (isset($arFieldsOrig['FM']) && isset($arFieldsModif['FM']))
			$arMsg = array_merge($arMsg, CCrmFieldMulti::CompareFields($arFieldsOrig['FM'], $arFieldsModif['FM']));

		if (isset($arFieldsOrig['ADDRESS']) && isset($arFieldsModif['ADDRESS'])
			&& $arFieldsOrig['ADDRESS'] != $arFieldsModif['ADDRESS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'ADDRESS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_ADDRESS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['ADDRESS'])? $arFieldsOrig['ADDRESS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['ADDRESS'])? $arFieldsModif['ADDRESS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['ADDRESS_LEGAL']) && isset($arFieldsModif['ADDRESS_LEGAL'])
			&& $arFieldsOrig['ADDRESS_LEGAL'] != $arFieldsModif['ADDRESS_LEGAL'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'ADDRESS_LEGAL',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_ADDRESS_LEGAL'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['ADDRESS_LEGAL'])? $arFieldsOrig['ADDRESS_LEGAL']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['ADDRESS_LEGAL'])? $arFieldsModif['ADDRESS_LEGAL']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['BANKING_DETAILS']) && isset($arFieldsModif['BANKING_DETAILS'])
			&& $arFieldsOrig['BANKING_DETAILS'] != $arFieldsModif['BANKING_DETAILS'])
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'BANKING_DETAILS',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_BANKING_DETAILS'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['BANKING_DETAILS'])? $arFieldsOrig['BANKING_DETAILS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['BANKING_DETAILS'])? $arFieldsModif['BANKING_DETAILS']: GetMessage('CRM_FIELD_COMPARE_EMPTY'),
			);

		if (isset($arFieldsOrig['COMPANY_TYPE']) && isset($arFieldsModif['COMPANY_TYPE'])
			&& $arFieldsOrig['COMPANY_TYPE'] != $arFieldsModif['COMPANY_TYPE'])
		{
			$arStatus = CCrmStatus::GetStatusList('COMPANY_TYPE');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'COMPANY_TYPE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_COMPANY_TYPE'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['COMPANY_TYPE'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['COMPANY_TYPE']))
			);
		}
		if (isset($arFieldsOrig['INDUSTRY']) && isset($arFieldsModif['INDUSTRY'])
			&& $arFieldsOrig['INDUSTRY'] != $arFieldsModif['INDUSTRY'])
		{
			$arStatus = CCrmStatus::GetStatusList('INDUSTRY');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'INDUSTRY',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_INDUSTRY'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['INDUSTRY'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['INDUSTRY']))
			);
		}
		if ((isset($arFieldsOrig['REVENUE']) && isset($arFieldsModif['REVENUE']) && $arFieldsOrig['REVENUE'] != $arFieldsModif['REVENUE'])
			|| (isset($arFieldsOrig['CURRENCY_ID']) && isset($arFieldsModif['CURRENCY_ID']) && $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID']))
		{
			$arStatus = CCrmCurrencyHelper::PrepareListItems();
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'REVENUE',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_REVENUE'),
				'EVENT_TEXT_1' => floatval($arFieldsOrig['REVENUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
				'EVENT_TEXT_2' => floatval($arFieldsModif['REVENUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
			);
		}
		if (isset($arFieldsOrig['EMPLOYEES']) && isset($arFieldsModif['EMPLOYEES'])
			&& $arFieldsOrig['EMPLOYEES'] != $arFieldsModif['EMPLOYEES'])
		{
			$arStatus = CCrmStatus::GetStatusList('EMPLOYEES');
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'EMPLOYEES',
				'EVENT_NAME' => GetMessage('CRM_FIELD_COMPARE_EMPLOYEES'),
				'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsOrig['EMPLOYEES'])),
				'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $arFieldsModif['EMPLOYEES']))
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

		if (isset($arFieldsOrig['ASSIGNED_BY_ID']) && isset($arFieldsModif['ASSIGNED_BY_ID'])
			&& (int)$arFieldsOrig['ASSIGNED_BY_ID'] != (int)$arFieldsModif['ASSIGNED_BY_ID'])
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

		return $arMsg;
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

	public static function SetDefaultResponsible($safe = true)
	{
		global $DB;
		$tableName = CCrmCompany::TABLE_NAME;

		if($safe && !$DB->Query("SELECT ASSIGNED_BY_ID FROM {$tableName} WHERE 1=0", true))
		{
			return false;
		}

		$DB->Query(
			"UPDATE {$tableName} SET ASSIGNED_BY_ID = CREATED_BY_ID WHERE ASSIGNED_BY_ID IS NULL",
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		return true;
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		if(!is_array($arFilter2Logic))
		{
			$arFilter2Logic = array('TITLE', 'ADDRESS_LEGAL', 'BANKING_DETAILS', 'ADDRESS', 'COMMENTS');
		}

		// converts data from filter
		if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
		{
			$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
			unset($arFilter['FIND_list'], $arFilter['FIND']);
		}

		static $arImmutableFilters = array('FM', 'ID', 'CURRENCY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'GRID_FILTER_APPLIED', 'GRID_FILTER_ID');
		foreach ($arFilter as $k => $v)
		{
			if(in_array($k, $arImmutableFilters, true))
			{
				continue;
			}

			$arMatch = array();

			if($k === 'ORIGINATOR_ID')
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
					if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
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
			elseif ($k != 'ID' && $k != 'LOGIC' && strpos($k, 'UF_') !== 0 && strpos($k, '%') !== 0)
			{
				$arFilter['%'.$k] = $v;
				unset($arFilter[$k]);
			}
		}
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
			array('ID', 'TITLE')
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
				'=ENTITY_ID' => CCrmOwnerType::CompanyName,
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

			$title = isset($fields['TITLE']) ? $fields['TITLE'] : '';
			if($title !== '')
			{
				\Bitrix\Crm\Integrity\DuplicateOrganizationCriterion::register(CCrmOwnerType::Company, $ID, $title);
			}

			$key = strval($ID);
			if(isset($emails[$key]))
			{
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Company, $ID, 'EMAIL', $emails[$key]);
			}

			if(isset($phones[$key]))
			{
				\Bitrix\Crm\Integrity\DuplicateCommunicationCriterion::register(CCrmOwnerType::Company, $ID, 'PHONE', $phones[$key]);
			}

			Bitrix\Crm\Integrity\DuplicateEntityRanking::registerEntityStatistics(CCrmOwnerType::Company, $ID, $fields);
		}
	}
}
?>
