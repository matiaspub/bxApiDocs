<?php

if(!defined('CACHED_b_crm_status')) define('CACHED_b_crm_status', 360000);

IncludeModuleLangFile(__FILE__);

class CCrmStatus
{
	protected $entityId = '';
	private static $FIELD_INFOS = null;
	private static $STATUSES = array();

	private $LAST_ERROR = '';
	function __construct($entityId)
	{
		$this->entityId = $entityId;
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
				'ENTITY_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'STATUS_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					)
				),
				'SORT' => array('TYPE' => 'integer'),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'NAME_INIT' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SYSTEM' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				)
			);
		}
		return self::$FIELD_INFOS;
	}
	public static function GetEntityTypes()
	{
		$arEntityType = Array(
			'STATUS'		=> array( 'ID' =>'STATUS', 'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS')),
			'SOURCE'		=> array( 'ID' =>'SOURCE', 'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE')),
			'CONTACT_TYPE'	=> array( 'ID' =>'CONTACT_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_CONTACT_TYPE')),
			'COMPANY_TYPE'	=> array( 'ID' =>'COMPANY_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_COMPANY_TYPE')),
			'EMPLOYEES'		=> array( 'ID' =>'EMPLOYEES', 'NAME' => GetMessage('CRM_STATUS_TYPE_EMPLOYEES')),
			'INDUSTRY'		=> array( 'ID' =>'INDUSTRY', 'NAME' => GetMessage('CRM_STATUS_TYPE_INDUSTRY')),
			'DEAL_TYPE'		=> array( 'ID' =>'DEAL_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_DEAL_TYPE')),
			'DEAL_STAGE'	=> array( 'ID' =>'DEAL_STAGE', 'NAME' => GetMessage('CRM_STATUS_TYPE_DEAL_STAGE')),
			'QUOTE_STATUS'	=> array( 'ID' =>'QUOTE_STATUS', 'NAME' => GetMessage('CRM_STATUS_TYPE_QUOTE_STATUS')),
			'EVENT_TYPE'	=> array( 'ID' =>'EVENT_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_EVENT_TYPE'))
		);

		if(self::IsDepricatedTypesEnabled())
		{
			$arEntityType['PRODUCT'] = array('ID' => 'PRODUCT', 'NAME' => GetMessage('CRM_STATUS_TYPE_PRODUCT'));
		}

		$events = GetModuleEvents("crm", "OnGetEntityTypes");

		while($arEvent = $events->Fetch())
			$arEntityType = ExecuteModuleEventEx($arEvent, array($arEntityType));

		return $arEntityType;
	}
	public static function IsDepricatedTypesEnabled()
	{
		return strtoupper(COption::GetOptionString('crm', 'enable_depricated_statuses', 'N')) !== 'N';
	}
	public static function EnableDepricatedTypes($enable)
	{
		return COption::SetOptionString('crm', 'enable_depricated_statuses', $enable ? 'Y' : 'N');
	}
	private static function GetCachedStatuses($entityId)
	{
		return isset(self::$STATUSES[$entityId]) ? self::$STATUSES[$entityId] : null;
	}
	private static function SetCachedStatuses($entityId, $items)
	{
		self::$STATUSES[$entityId] = $items;
	}
	private static function ClearCachedStatuses($entityId)
	{
		unset(self::$STATUSES[$entityId]);
	}
	public function Add($arFields, $bCheckStatusId = true)
	{
		$this->LAST_ERROR = '';

		if (!$this->CheckFields($arFields, $bCheckStatusId))
			return false;

		if (!is_set($arFields['SORT']) ||
			(is_set($arFields['SORT']) && !intval($arFields['SORT']) > 0))
			$arFields['SORT'] = 10;

		if (!is_set($arFields, 'STATUS_ID'))
			$arFields['STATUS_ID'] = '';

		if (!is_set($arFields, 'SYSTEM'))
			$arFields['SYSTEM'] = 'N';

		$arFields_i = Array(
			'ENTITY_ID'	=> $this->entityId,
			'STATUS_ID'	=> !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $this->GetNextStatusId(),
			'NAME'		=> $arFields['NAME'],
			'NAME_INIT'	=> $arFields['SYSTEM'] == 'Y' ? $arFields['NAME'] : '',
			'SORT'		=> IntVal($arFields['SORT']),
			'SYSTEM'	=> $arFields['SYSTEM'] == 'Y'? 'Y': 'N',
		);

		global $DB;
		$ID = $DB->Add('b_crm_status', $arFields_i, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		self::ClearCachedStatuses($this->entityId);
		return $ID;
	}
	public function Update($ID, $arFields, $arOptions = array())
	{
		$this->LAST_ERROR = '';

		if (!$this->CheckFields($arFields))
			return false;

		$ID = IntVal($ID);

		if (!is_set($arFields['SORT']) ||
			(is_set($arFields['SORT']) && !intval($arFields['SORT']) > 0))
			$arFields['SORT'] = 10;

		$arFields_u = Array(
			'NAME'		=> $arFields['NAME'],
			'SORT'		=> IntVal($arFields['SORT']),
		);
		if (is_set($arFields, 'SYSTEM'))
			$arFields_u['SYSTEM'] == 'Y'? 'Y': 'N';

		if(is_array($arOptions)
			&& isset($arOptions['ENABLE_STATUS_ID'])
			&& $arOptions['ENABLE_STATUS_ID']
			&& isset($arFields['STATUS_ID']))
		{
			$arFields_u['STATUS_ID'] = $arFields['STATUS_ID'];
		}

		global $DB;
		$strUpdate = $DB->PrepareUpdate('b_crm_status', $arFields_u);
		if(!$DB->Query('UPDATE b_crm_status SET '.$strUpdate.' WHERE ID='.$ID, false, array(), 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__))
			return false;

		self::ClearCachedStatuses($this->entityId);
		return $ID;
	}
	public function Delete($ID)
	{
		$this->LAST_ERROR = '';
		$ID = IntVal($ID);

		global $DB;
		$res = $DB->Query("DELETE FROM b_crm_status WHERE ID=$ID", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		self::ClearCachedStatuses($this->entityId);
		return $res;
	}
	public static function GetList($arSort=array(), $arFilter=Array())
	{
		global $DB;
		$arSqlSearch = Array();
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0, $ic=count($filter_keys); $i<$ic; $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				if (strlen($val)<=0 || $val=='NOT_REF') continue;
				switch(strtoupper($filter_keys[$i]))
				{
					case 'ID':
						$arSqlSearch[] = GetFilterQuery('CS.ID', $val, 'N');
					break;
					case 'ENTITY_ID':
						$arSqlSearch[] = GetFilterQuery('CS.ENTITY_ID', $val);
					break;
					case 'STATUS_ID':
						$arSqlSearch[] = GetFilterQuery('CS.STATUS_ID', $val);
					break;
					case 'NAME':
						$arSqlSearch[] = GetFilterQuery('CS.NAME', $val);
					break;
					case 'SORT':
						$arSqlSearch[] = GetFilterQuery('CS.SORT', $val);
					break;
					case 'SYSTEM':
						$arSqlSearch[] = ($val=='Y') ? "CS.SYSTEM='Y'" : "CS.SYSTEM='N'";
					break;
				}
			}
		}

		$sOrder = '';
		foreach($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> 'ASC'? 'DESC':'ASC');
			switch (strtoupper($key))
			{
				case 'ID':		$sOrder .= ', CS.ID '.$ord; break;
				case 'ENTITY_ID':	$sOrder .= ', CS.ENTITY_ID '.$ord; break;
				case 'STATUS_ID':	$sOrder .= ', CS.STATUS_ID '.$ord; break;
				case 'NAME':	$sOrder .= ', CS.NAME '.$ord; break;
				case 'SORT':	$sOrder .= ', CS.SORT '.$ord; break;
				case 'SYSTEM':	$sOrder .= ', CS.SYSTEM '.$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = 'CS.ID DESC';

		$strSqlOrder = ' ORDER BY '.TrimEx($sOrder,',');

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				CS.ID, CS.ENTITY_ID, CS.STATUS_ID, CS.NAME, CS.NAME_INIT, CS.SORT, CS.SYSTEM
			FROM
				b_crm_status CS
			WHERE
			$strSqlSearch
			$strSqlOrder";
		$res = $DB->Query($strSql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

		return $res;
	}
	public function CheckStatusId($statusId)
	{
		global $DB;
		$res = $DB->Query("SELECT ID FROM b_crm_status WHERE ENTITY_ID='{$DB->ForSql($this->entityId)}' AND STATUS_ID ='{$DB->ForSql($statusId)}'", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$fields = is_object($res) ? $res->Fetch() : array();
		return isset($fields['ID']);
	}
	public function GetNextStatusId()
	{
		global $DB, $DBType;
		$dbTypeUC = strtoupper($DBType);

		if($dbTypeUC === 'MYSQL')
		{
			$sql = "SELECT STATUS_ID AS MAX_STATUS_ID FROM b_crm_status WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND CAST(STATUS_ID AS UNSIGNED) > 0 ORDER BY CAST(STATUS_ID AS UNSIGNED) DESC LIMIT 1";
		}
		elseif($dbTypeUC === 'MSSQL')
		{
			$sql = "SELECT TOP 1 STATUS_ID AS MAX_STATUS_ID FROM B_CRM_STATUS WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND CAST((CASE WHEN ISNUMERIC(STATUS_ID) > 0 THEN STATUS_ID ELSE '0' END) AS INT) > 0 ORDER BY CAST((CASE WHEN ISNUMERIC(STATUS_ID) > 0 THEN STATUS_ID ELSE '0' END) AS INT) DESC";
		}
		elseif($dbTypeUC === 'ORACLE')
		{
			$sql = "SELECT STATUS_ID AS MAX_STATUS_ID FROM (SELECT STATUS_ID FROM B_CRM_STATUS WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND COALESCE(TO_NUMBER(REGEXP_SUBSTR(STATUS_ID, '^\d+(\.\d+)?')), 0) > 0 ORDER BY COALESCE(TO_NUMBER(REGEXP_SUBSTR(STATUS_ID, '^\d+(\.\d+)?')), 0) DESC) WHERE ROWNUM <= 1";
		}
		else
		{
			return 0;
		}

		$res = $DB->Query($sql, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$fields = is_object($res) ? $res->Fetch() : array();
		return (isset($fields['MAX_STATUS_ID']) ? intval($fields['MAX_STATUS_ID']) : 0) + 1;
	}
	public static function GetStatus($entityId, $internalOnly = false)
	{
		global $DB;
		$arStatus = array();

		if(!$internalOnly)
		{
			$events = GetModuleEvents("crm", "OnCrmStatusGetList");
			while($arEvent = $events->Fetch())
			{
				$arStatus = ExecuteModuleEventEx($arEvent, array($entityId));

				if(!empty($arStatus))
					return $arStatus;
			}
		}


		if(CACHED_b_crm_status===false)
		{
			$squery = "
				SELECT *
				FROM b_crm_status
				WHERE ENTITY_ID = '".$DB->ForSql($entityId)."'
				ORDER BY SORT ASC
			";
			$res = $DB->Query($squery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
			while ($row = $res->Fetch())
			{
				$arStatus[$row['STATUS_ID']] = $row;
			}
			return $arStatus;
		}
		else
		{
			$cached = self::GetCachedStatuses($entityId);
			if($cached !== null)
			{
				$arStatus = $cached;
			}
			else
			{
				$squery = "
					SELECT *
					FROM b_crm_status
					WHERE ENTITY_ID = '".$DB->ForSql($entityId)."'
					ORDER BY SORT ASC
				";
				$res = $DB->Query($squery, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
				while($row = $res->Fetch())
				{
					$arStatus[$row['STATUS_ID']] = $row;
				}
				self::SetCachedStatuses($entityId, $arStatus);
			}
			return $arStatus;
		}
	}
	public static function GetStatusList($entityId, $internalOnly = false)
	{
		$arStatusList = Array();
		$ar = self::GetStatus($entityId, $internalOnly);
		if(is_array($ar))
		{
			foreach($ar as $arStatus)
			{
				$arStatusList[$arStatus['STATUS_ID']] = $arStatus['NAME'];
			}
		}

		return $arStatusList;
	}
	public static function GetStatusListEx($entityId)
	{
		$arStatusList = Array();
		$ar = self::GetStatus($entityId);
		foreach($ar as $arStatus)
			$arStatusList[$arStatus['STATUS_ID']] = htmlspecialcharsbx($arStatus['NAME']);

		return $arStatusList;
	}
	public function GetStatusById($ID)
	{
		if(!is_int($ID))
		{
			$ID = (int)$ID;
		}

		$arStatus = self::GetStatus($this->entityId);
		foreach($arStatus as $item)
		{
			$currentID = isset($item['ID']) ? (int)$item['ID'] : 0;
			if($currentID === $ID)
			{
				return $item;
			}
		}
		return false;
	}
	public function GetStatusByStatusId($statusId)
	{
		$arStatus = self::GetStatus($this->entityId);
		return isset($arStatus[$statusId]) ? $arStatus[$statusId]: false;
	}
	private function CheckFields($arFields, $bCheckStatusId = true)
	{
		$aMsg = array();

		if(is_set($arFields, 'NAME') && trim($arFields['NAME'])=='')
			$aMsg[] = array('id'=>'NAME', 'text'=>GetMessage('CRM_STATUS_ERR_NAME'));
		if(is_set($arFields, 'SYSTEM') && !($arFields['SYSTEM'] == 'Y' || $arFields['SYSTEM'] == 'N'))
			$aMsg[] = array('id'=>'SYSTEM', 'text'=>GetMessage('CRM_STATUS_ERR_SYSTEM'));
		if(is_set($arFields, 'STATUS_ID') && trim($arFields['STATUS_ID'])=='')
			$aMsg[] = array('id'=>'STATUS_ID', 'text'=>GetMessage('CRM_STATUS_ERR_STATUS_ID'));
		if (is_set($arFields, 'STATUS_ID') && $bCheckStatusId && $this->CheckStatusId($arFields['STATUS_ID']))
			$aMsg[] = array('id'=>'STATUS_ID', 'text'=>GetMessage('CRM_STATUS_ERR_DUPLICATE_STATUS_ID'));

		if(!empty($aMsg))
		{
			foreach($aMsg as $msg)
			{
				$this->LAST_ERROR .= $msg."<br />\n";
			}

			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}
	public function GetLastError()
	{
		return $this->LAST_ERROR;
	}
	// Checking User Permissions -->
	public static function CheckCreatePermission()
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckUpdatePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckDeletePermission($ID)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}
	public static function CheckReadPermission($ID = 0)
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}
	// <-- Checking User Permissions
}

?>
