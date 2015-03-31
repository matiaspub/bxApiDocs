<?php

if (!defined('CACHED_b_field_multi')) define('CACHED_b_field_multi', 360000);

IncludeModuleLangFile(__FILE__);

class CCrmFieldMulti
{
	protected $cdb = null;
	public $LAST_ERROR = '';
	private static $FIELDS = null;
	private static $ENTITY_TYPES = null;
	private static $ENTITY_TYPE_INFOS = null;

	const PHONE = 'PHONE';
	const EMAIL = 'EMAIL';
	const WEB = 'WEB';
	const IM = 'IM';

	function __construct()
	{
		global $DB;

		$this->cdb = $DB;
	}

	public static function GetEntityTypeInfos()
	{
		if(self::$ENTITY_TYPE_INFOS === null)
		{
			self::$ENTITY_TYPE_INFOS = array(
				'PHONE' => array('NAME' => GetMessage('CRM_FM_ENTITY_PHONE')),
				'EMAIL' => array('NAME' => GetMessage('CRM_FM_ENTITY_EMAIL')),
				'WEB' => array('NAME' => GetMessage('CRM_FM_ENTITY_WEB')),
				'IM' => array('NAME' => GetMessage('CRM_FM_ENTITY_IM'))
			);
		}
		return self::$ENTITY_TYPE_INFOS;
	}
	public static function GetEntityTypes()
	{
		if(self::$ENTITY_TYPES === null)
		{
			self::$ENTITY_TYPES = Array(
				'PHONE' => Array(
					'WORK'  => Array('FULL' => GetMessage('CRM_FM_ENTITY_PHONE_WORK'), 	'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_WORK_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_WORK_ABBR'), 'TEMPLATE' => '<a href="'.CCrmCallToUrl::Format('#VALUE#').'">#VALUE_HTML#</a>'),
					'MOBILE'=> Array('FULL' => GetMessage('CRM_FM_ENTITY_PHONE_MOBILE'), 'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_MOBILE_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_MOBILE_ABBR'), 'TEMPLATE' => '<a href="'.CCrmCallToUrl::Format('#VALUE#').'">#VALUE_HTML#</a>'),
					'FAX' 	=> Array('FULL' => GetMessage('CRM_FM_ENTITY_PHONE_FAX'), 'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_FAX_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_FAX_ABBR'), 'TEMPLATE' => '<a href="'.CCrmCallToUrl::Format('#VALUE#').'">#VALUE_HTML#</a>'),
					'HOME' 	=> Array('FULL' => GetMessage('CRM_FM_ENTITY_PHONE_HOME'), 'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_HOME_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_HOME_ABBR'), 'TEMPLATE' => '<a href="'.CCrmCallToUrl::Format('#VALUE#').'">#VALUE_HTML#</a>'),
					'PAGER' => Array('FULL' => GetMessage('CRM_FM_ENTITY_PHONE_PAGER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_PAGER_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_PAGER_ABBR'), 'TEMPLATE' => '<a href="'.CCrmCallToUrl::Format('#VALUE#').'">#VALUE_HTML#</a>'),
					'OTHER' => Array('FULL' => GetMessage('CRM_FM_ENTITY_PHONE_OTHER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_PHONE_OTHER_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_PHONE_OTHER_ABBR'), 'TEMPLATE' => '<a href="'.CCrmCallToUrl::Format('#VALUE#').'">#VALUE_HTML#</a>'),
				),
				'WEB' => Array(
					'WORK' 		=> Array('FULL' => GetMessage('CRM_FM_ENTITY_WEB_WORK'), 'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_WORK_SHORT'), 'TEMPLATE' => '<a href="http://#VALUE_URL#" target="_blank">#VALUE_HTML#</a>'),
					'HOME' 		=> Array('FULL' => GetMessage('CRM_FM_ENTITY_WEB_HOME'), 'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_HOME_SHORT'), 'TEMPLATE' => '<a href="http://#VALUE_URL#" target="_blank">#VALUE_HTML#</a>'),
					'FACEBOOK' 	=> Array('FULL' =>  GetMessage('CRM_FM_ENTITY_WEB_FACEBOOK'), 'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_FACEBOOK_SHORT'), 'TEMPLATE' => '<a href="http://www.facebook.com/#VALUE_URL#/" target="_blank">#VALUE_HTML#</a>'),
					'LIVEJOURNAL' => Array('FULL' =>  GetMessage('CRM_FM_ENTITY_WEB_LIVEJOURNAL'), 'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_LIVEJOURNAL_SHORT'), 'TEMPLATE' => '<a href="http://#VALUE_URL#.livejournal.com/" target="_blank">#VALUE_HTML#</a>'),
					'TWITTER' 	=> Array('FULL' =>  GetMessage('CRM_FM_ENTITY_WEB_TWITTER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_TWITTER_SHORT'), 'TEMPLATE' => '<a href="http://twitter.com/#VALUE_URL#/" target="_blank">#VALUE_HTML#</a>'),
					'OTHER' 	=> Array('FULL' =>  GetMessage('CRM_FM_ENTITY_WEB_OTHER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_WEB_OTHER_SHORT'), 'TEMPLATE' => '<a href="http://#VALUE_URL#" target="_blank">#VALUE_HTML#</a>'),
				),
				'EMAIL' => Array(
					'WORK'  => Array('FULL' => GetMessage('CRM_FM_ENTITY_EMAIL_WORK'), 'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_WORK_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_WORK_ABBR'), 'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>'),
					'HOME' 	=> Array('FULL' => GetMessage('CRM_FM_ENTITY_EMAIL_HOME'), 'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_HOME_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_HOME_ABBR'), 'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>'),
					'OTHER' => Array('FULL' =>  GetMessage('CRM_FM_ENTITY_EMAIL_OTHER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_EMAIL_OTHER_SHORT'), 'ABBR' => GetMessage('CRM_FM_ENTITY_EMAIL_OTHER_ABBR'), 'TEMPLATE' => '<a href="mailto:#VALUE_URL#">#VALUE_HTML#</a>'),
				),
				'IM' => Array(
					'SKYPE'	=> Array('FULL' => GetMessage('CRM_FM_ENTITY_IM_SKYPE'), 'SHORT' => GetMessage('CRM_FM_ENTITY_IM_SKYPE_SHORT'), 'TEMPLATE' => '<a href="skype:#VALUE_URL#?chat">#VALUE_HTML#</a>'),
					'ICQ'	=> Array('FULL' => GetMessage('CRM_FM_ENTITY_IM_ICQ'), 'SHORT' => GetMessage('CRM_FM_ENTITY_IM_ICQ_SHORT'), 'TEMPLATE' => '<a href="http://www.icq.com/people/#VALUE_URL#/" target="_blank">#VALUE_HTML#</a>'),
					'MSN'	=> Array('FULL' => GetMessage('CRM_FM_ENTITY_IM_MSN'), 'SHORT' => GetMessage('CRM_FM_ENTITY_IM_MSN_SHORT'), 'TEMPLATE' => '<a href="msn:#VALUE_URL#">#VALUE_HTML#</a>'),
					'JABBER'=> Array('FULL' => GetMessage('CRM_FM_ENTITY_IM_JABBER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_IM_JABBER_SHORT'), 'TEMPLATE' => '#VALUE_HTML#'),
					'OTHER' => Array('FULL' => GetMessage('CRM_FM_ENTITY_IM_OTHER'), 'SHORT' => GetMessage('CRM_FM_ENTITY_IM_OTHER_SHORT'), 'TEMPLATE' => '#VALUE_HTML#'),
				),
			);
		}
		return self::$ENTITY_TYPES;
	}

	public function Add($arFields)
	{
		$err_mess = (self::err_mess()).'<br />Function: Add<br>Line: ';

		if (!$this->CheckFields($arFields))
			return false;

		$arFields_i = Array(
			'ENTITY_ID'	=> $arFields['ENTITY_ID'],
			'ELEMENT_ID'=> intval($arFields['ELEMENT_ID']),
			'TYPE_ID'	=> $arFields['TYPE_ID'],
			'VALUE_TYPE'=> $arFields['VALUE_TYPE'],
			'COMPLEX_ID'=> $arFields['TYPE_ID'].'_'.$arFields['VALUE_TYPE'],
			'VALUE'		=> $arFields['VALUE'],
		);
		$ID = $this->cdb->Add('b_crm_field_multi', $arFields_i);

		return $ID;
	}

	public function Update($ID, $arFields)
	{
		$err_mess = (self::err_mess()).'<br />Function: Update<br>Line: ';

		$ID = IntVal($ID);

		if (!$this->CheckFields($arFields))
			return false;

		$arFields_u = Array(
			'TYPE_ID'	=> $arFields['TYPE_ID'],
			'VALUE_TYPE'=> $arFields['VALUE_TYPE'],
			'COMPLEX_ID'=> $arFields['TYPE_ID'].'_'.$arFields['VALUE_TYPE'],
			'VALUE'		=> $arFields['VALUE'],
		);
		$strUpdate = $this->cdb->PrepareUpdate('b_crm_field_multi', $arFields_u);
		if (!$this->cdb->Query("UPDATE b_crm_field_multi SET $strUpdate WHERE ID=$ID", false, $err_mess.__LINE__))
			return false;

		return $ID;
	}

	public function Delete($ID)
	{
		$err_mess = (self::err_mess()).'<br />Function: Delete<br>Line: ';

		$ID = IntVal($ID);

		$res = $this->cdb->Query("DELETE FROM b_crm_field_multi WHERE ID=$ID", false, $err_mess.__LINE__);

		return $res;
	}

	public function DeleteByElement($entityId, $elementId)
	{
		$err_mess = (self::err_mess()).'<br>Function: DeleteByElement<br>Line: ';

		$elementId = IntVal($elementId);

		if ($entityId == '' || $elementId == 0)
			return false;

		$res = $this->cdb->Query("DELETE FROM b_crm_field_multi WHERE ENTITY_ID = '".$this->cdb->ForSql($entityId)."' AND ELEMENT_ID = '".$elementId."'", false, $err_mess.__LINE__);

		return $res;
	}

	public function SetFields($entityId, $elementId, $arFieldData)
	{
		if (!is_array($arFieldData))
			return false;

		foreach($arFieldData as $typeId => $arValue)
		{
			$arList = Array();
			$res = self::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityId, 'ELEMENT_ID' => $elementId, 'TYPE_ID' =>  $typeId)
			);
			while($ar = $res->Fetch())
				$arList[$ar['ID']] = $ar;

			$bResult = true;
			foreach($arValue as $id => $arValue)
			{
				if (substr($id, 0, 1) == 'n')
				{
					if (trim($arValue['VALUE']) == "")
						continue;

					$arAdd = Array(
						'ENTITY_ID' => $entityId,
						'ELEMENT_ID' => $elementId,
						'TYPE_ID' => $typeId,
						'VALUE_TYPE' => $arValue['VALUE_TYPE'],
						'VALUE' => $arValue['VALUE'],
					);
					if (!$this->Add($arAdd))
						$bResult = false;
				}
				else
				{
					if (!isset($arValue['VALUE']) || trim($arValue['VALUE']) == "")
						$this->Delete($id);
					else
					{
						if (trim($arValue['VALUE']) != $arList[$id]['VALUE']
						|| trim($arValue['VALUE_TYPE']) != $arList[$id]['VALUE_TYPE'])
						{
							$arUpdate = Array(
								'TYPE_ID' => $typeId,
								'VALUE_TYPE' => $arValue['VALUE_TYPE'],
								'VALUE' => $arValue['VALUE'],
							);
							if (!$this->Update($id, $arUpdate));
								$bResult = false;
						}
					}
				}
			}
		}

		return $bResult;
	}

	public static function GetList($arSort=array(), $arFilter=array())
	{
		global $DB;

		$arSqlSearch = array();
		$err_mess = (self::err_mess()).'<br />Function: GetList<br>Line: ';
		if (is_array($arFilter))
		{
			self::PrepareSearchQuery($arFilter, $arSqlSearch);
		}

		$sOrder = '';
		foreach ($arSort as $key=>$val)
		{
			$ord = (strtoupper($val) <> 'ASC' ? 'DESC' : 'ASC');
			switch (strtoupper($key))
			{
				case 'ID':		$sOrder .= ', CFM.ID '.$ord; break;
				case 'ENTITY_ID':	$sOrder .= ', CFM.ENTITY_ID '.$ord; break;
				case 'ELEMENT_ID':	$sOrder .= ', CFM.ELEMENT_ID '.$ord; break;
				case 'TYPE_ID':	$sOrder .= ', CFM.TYPE_ID '.$ord; break;
				case 'VALUE_TYPE':	$sOrder .= ', CFM.VALUE_TYPE '.$ord; break;
				case 'COMPLEX_ID':	$sOrder .= ', CFM.COMPLEX_ID '.$ord; break;
				case 'VALUE':	$sOrder .= ', CFM.VALUE '.$ord; break;
			}
		}

		if (strlen($sOrder)<=0)
			$sOrder = 'CFM.ID DESC';

		$strSqlOrder = ' ORDER BY '.TrimEx($sOrder,",");

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT
				CFM.ID, CFM.ENTITY_ID, CFM.ELEMENT_ID, CFM.TYPE_ID, CFM.VALUE_TYPE, CFM.COMPLEX_ID, CFM.VALUE
			FROM
				b_crm_field_multi CFM
			WHERE
			$strSqlSearch
			$strSqlOrder";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);

		return $res;
	}

	public static function GetListEx($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		$lb = new CCrmEntityListBuilder(
			'',
			'b_crm_field_multi',
			'CFM',
			self::GetFields(),
			'',
			'',
			null,
			null
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	public static function GetFields()
	{
		if(self::$FIELDS === null)
		{
			self::$FIELDS = array(
				'ID' => array('FIELD' => 'CFM.ID', 'TYPE' => 'int'),
				'ENTITY_ID' => array('FIELD' => 'CFM.ENTITY_ID', 'TYPE' => 'string'),
				'ELEMENT_ID' => array('FIELD' => 'CFM.ELEMENT_ID', 'TYPE' => 'int'),
				'TYPE_ID' => array('FIELD' => 'CFM.TYPE_ID', 'TYPE' => 'string'),
				'VALUE_TYPE' => array('FIELD' => 'CFM.VALUE_TYPE', 'TYPE' => 'string'),
				'COMPLEX_ID' => array('FIELD' => 'CFM.COMPLEX_ID', 'TYPE' => 'string'),
				'VALUE' => array('FIELD' => 'CFM.VALUE', 'TYPE' => 'string')
			);
		}
		return self::$FIELDS;
	}

	public static function PrepareExternalFilter(&$filter, $params = array())
	{
		if(!isset($filter['FM']) || empty($filter['FM']) || !is_array($params) || empty($params))
		{
			return;
		}

		$entityID = isset($params['ENTITY_ID']) ? $params['ENTITY_ID'] : '';

		$masterAlias = isset($params['MASTER_ALIAS']) ? $params['MASTER_ALIAS'] : '';
		if($masterAlias === '')
		{
			$masterAlias = 'L';
		}

		$masterIdentity = isset($params['MASTER_IDENTITY']) ? $params['MASTER_IDENTITY'] : '';
		if($masterIdentity === '')
		{
			$masterIdentity = 'ID';
		}

		$fields = self::GetFields();
		$joins = array();
		$c = 0;
		foreach($filter['FM'] as $filterPart)
		{
			if($entityID !== '')
			{
				$filterPart['ENTITY_ID'] = $entityID;
			}

			$c++;
			$alias = "CFM{$c}";
			$where = CSqlUtil::PrepareWhere($fields, $filterPart, $joins);
			$joins[] = array(
				'TYPE' => 'INNER',
				'SQL' => "INNER JOIN (SELECT DISTINCT CFM.ELEMENT_ID FROM b_crm_field_multi CFM WHERE {$where}) {$alias} ON {$masterAlias}.{$masterIdentity} = {$alias}.ELEMENT_ID"
			);
		}

		if(!empty($joins))
		{
			if(!isset($filter['__JOINS']))
			{
				$filter['__JOINS'] = $joins;
			}
			else
			{
				$filter['__JOINS'] = array_merge($filter['__JOINS'], $joins);
			}
		}
	}

	private static function PrepareSearchQuery(&$arFilter, &$arSqlSearch)
	{
		global $DB;

		$filter_keys = array_keys($arFilter);
		for ($i=0, $ic=count($filter_keys); $i < $ic; $i++)
		{
			$val = $arFilter[$filter_keys[$i]];

			if (!is_array($val) && strlen($val)<=0 || $val=="NOT_REF")
				continue;

			$key = strtoupper($filter_keys[$i]);
			$operationInfo = CSqlUtil::GetFilterOperation($key);
			$operation = $operationInfo['OPERATION'];
			// Process only like operation
			$isLikeOperation = $operation === 'LIKE' ? 'Y' : 'N';
			$fieldName = $operationInfo['FIELD'];

			switch($fieldName)
			{
				case 'ID':
					$arSqlSearch[] = GetFilterQuery('CFM.ID', $val, 'N');
					break;
				case 'ENTITY_ID':
					$arSqlSearch[] = GetFilterQuery('CFM.ENTITY_ID', $val, $isLikeOperation);
					break;
				case 'ELEMENT_ID':
					if (is_array($val))
					{
						$ar = array();
						foreach($val as $v)
							$ar[] = intval($v);
						if (!empty($ar))
							$arSqlSearch[] = 'CFM.ELEMENT_ID IN ('.implode(',', $ar).')';
					}
					else
						$arSqlSearch[] = 'CFM.ELEMENT_ID = '.intval($val);
					break;
				case 'TYPE_ID':
					$arSqlSearch[] = GetFilterQuery('CFM.TYPE_ID', $val, $isLikeOperation);
					break;
				case 'VALUE_TYPE':
					if (is_array($val))
					{
						$valueTypeFilter = '';
						foreach($val as $v)
						{
							$v = $DB->ForSql(trim(strval($v)));
							if($v === '')
							{
								continue;
							}

							if($valueTypeFilter !== '')
							{
								$valueTypeFilter .= ', ';
							}

							$valueTypeFilter .= "'{$v}'";
						}
						
						if ($valueTypeFilter !== '')
						{
							$arSqlSearch[] = "CFM.VALUE_TYPE IN ({$valueTypeFilter})";
						}
					}
					else
						$arSqlSearch[] = GetFilterQuery('CFM.VALUE_TYPE', $val, $isLikeOperation);
					break;
				case 'COMPLEX_ID':
					$arSqlSearch[] = GetFilterQuery('CFM.COMPLEX_ID', $val, $isLikeOperation);
					break;
				case 'VALUE':
					$arSqlSearch[] = GetFilterQuery('CFM.VALUE', $val, $isLikeOperation);
					break;
				case 'FILTER':
				{
					$arSqlFilterSearch = array();
					if(is_array($val))
					{
						// Processing of filter parts
						foreach($val as $v)
						{
							// Prepering filter part - items are joined by 'AND'
							$arSqlInnerSearch = array();
							self::PrepareSearchQuery($v, $arSqlInnerSearch);
							if(!empty($arSqlInnerSearch))
							{
								$arSqlFilterSearch[] = '('.implode(' AND ', $arSqlInnerSearch).')';
							}
						}
					}
					if (!empty($arSqlFilterSearch))
					{
						//$logic = isset($arFilter['LOGIC']) && is_string($arFilter['LOGIC']) ? strtoupper($arFilter['LOGIC']) : '';
						//$logic = '';
						//if($logic === '')
						//{
						//	$logic = 'OR';
						//}

						// Prepering filter - parts are joined by 'OR'
						//$arSqlSearch[] = '('.implode(" {$logic} ", $arSqlFilterSearch).')';
						$arSqlSearch[] = '('.implode(" OR ", $arSqlFilterSearch).')';
					}
				}
				break;
			}
		}
	}

	public static function PrepareFields(&$arFields)
	{
		$i = 1;
		$arList = Array();

		$arEntityType = self::GetEntityTypes();

		foreach($arEntityType as $entityId => $ar)
		{
			foreach($ar as $valueType => $arValue)
			{
				$key = "{$entityId}_{$valueType}";
				if(!isset($arFields[$key]))
				{
					continue;
				}

				$arData = explode(';', $arFields[$key]);
				if (($entityId == 'EMAIL' || $entityId == 'PHONE') && count($arData) == 1)
				{
					$arData = explode(',', $arFields[$key]);
					if ($entityId == 'EMAIL' && count($arData) == 1)
					{
						$arData = explode(' ', $arFields[$key]);
					}
				}
				foreach($arData as $data)
				{
					if (!empty($data))
					{
						$arList[$entityId]['n'.$i]['VALUE'] = trim($data);
						$arList[$entityId]['n'.$i]['VALUE_TYPE'] = $valueType;
						$i++;
					}
				}
				unset($arFields[$entityId.'_'.$valueType]);
			}
		}

		if (!empty($arList))
			$arFields['FM'] = $arList;

		return $arList;
	}

	public static function ParseComplexName($complexName, $enableCheck = true)
	{
		$ary = explode('_', $complexName);
		if(count($ary) !== 2)
		{
			array();
		}

		if(!$enableCheck)
		{
			return array('TYPE' => $ary[0], 'VALUE_TYPE' => $ary[1]);
		}

		$type = $ary[0];
		$valueType = $ary[1];
		$entityTypes = self::GetEntityTypes();
		return isset($entityTypes[$type]) && isset($entityTypes[$type][$valueType])
			? array('TYPE' => $type, 'VALUE_TYPE' => $valueType) : array();
	}

	public static function GetEntityTypeList($entityType = '', $bFullName = true)
	{
		$arList = Array();
		static $arEntityType = array();

		$nameType = $bFullName? 'FULL': 'SHORT';
		$arEntityType[$nameType] = array();
		if (empty($arEntityType[$nameType]))
			$arEntityType[$nameType] = self::GetEntityTypes();

		if ($entityType == '')
			foreach($arEntityType[$nameType] as $entity => $ar)
				foreach($ar as $type => $ar)
					$arList[$entity][$type] = $ar[$nameType];
		elseif (isset($arEntityType[$nameType][$entityType]))
			foreach($arEntityType[$nameType][$entityType] as $type => $ar)
				$arList[$type] = $ar[$nameType];

		return $arList;
	}

	public static function GetEntityComplexList($entityType = '', $bFullName = true)
	{
		$arList = Array();
		static $arEntityType = array();

		$nameType = $bFullName? 'FULL': 'SHORT';
		if (empty($arEntityType[$nameType]))
			$arEntityType[$nameType] = self::GetEntityTypes();

		if ($entityType == '')
			foreach($arEntityType[$nameType] as $entity => $ar)
				foreach($ar as $type => $ar)
					$arList[$entity.'_'.$type] = $ar[$nameType];
		elseif (isset($arEntityType[$nameType][$entityType]))
			foreach($arEntityType[$nameType][$entityType] as $type => $ar)
				$arList[$entityType.'_'.$type] = $ar[$nameType];

		return $arList;
	}

	public static function GetEntityName($typeID, $valueType, $bFullName = true)
	{
		$typeID = strval($typeID);
		$valueType = strval($valueType);

		return self::GetEntityNameByComplex("{$typeID}_{$valueType}", $bFullName);
	}

	public static function GetEntityNameByComplex($complexName, $bFullName = true)
	{
		if ($complexName == '')
			return false;

		static $arList = array();

		$nameType = $bFullName? 'FULL': 'SHORT';

		$arList[$nameType] = array();
		if (empty($arList[$nameType]))
			$arList[$nameType] = self::GetEntityComplexList('', $bFullName);

		if (isset($arList[$nameType][$complexName]))
			return $arList[$nameType][$complexName];
		else
			return false;
	}

	// Obsolete. Please use PrepareListHeaders.
	public function ListAddHeaders(&$arHeaders, $skipTypes = array(), $skipValueTypes = array())
	{
		if(!is_array($skipTypes))
		{
			$skipTypes = array();
		}

		if(!is_array($skipValueTypes))
		{
			$skipValueTypes = array();
		}

		$ar =  CCrmFieldMulti::GetEntityTypeList();
		foreach($ar as $typeId => $arFields)
		{
			if(in_array($typeId, $skipTypes, true))
			{
				continue;
			}

			foreach($arFields as $valueType => $valueName)
			{
				if(in_array($valueType, $skipValueTypes, true))
				{
					continue;
				}

				$arHeaders[] = array(
					'id' => $typeId.'_'.$valueType,
					'name' => $valueName,
					'sort' => false,
					'default' => $valueType == 'WORK' && ($typeId == 'PHONE' || $typeId == 'EMAIL'),
					'editable' => false,
					'type' => 'string'
				);
			}
		}
	}

	public function PrepareListHeaders(&$arHeaders, $skipTypeIDs = array(), $prefix = '')
	{
		if(!is_array($skipTypeIDs))
		{
			$skipTypeIDs = array();
		}

		$arTypeInfos = self::GetEntityTypeInfos();
		foreach($arTypeInfos as $typeID => &$info)
		{
			if(in_array($typeID, $skipTypeIDs, true))
			{
				continue;
			}

			$arHeaders[] = array(
				'id' => "{$prefix}{$typeID}",
				'name' => $info['NAME'],
				'sort' => false,
				'default' => $typeID === 'PHONE' || $typeID === 'EMAIL',
				'editable' => false,
				'type' => 'custom'
			);
		}
		unset($info);
	}

	public function ListAddFilterFields(&$arFilterFields, &$arFilterLogic, $sFormName = 'form1', $bVarsFromForm = true)
	{
		$ar = CCrmFieldMulti::GetEntityComplexList();
		foreach($ar as $complexId=>$complexName)
		{
			$arFilterFields[] = array(
				'id' => $complexId,
				'name' => htmlspecialcharsex($complexName),
				'type' => 'string',
				'value' => ''
			);
			$arFilterLogic[] = $complexId;
		}
	}

	public static function GetTemplate($typeID, $valueType, $value)
	{
		$typeID = strval($typeID);
		$valueType = strval($valueType);
		$value = strval($value);

		return self::GetTemplateByComplex("{$typeID}_{$valueType}", $value);
	}

	public static function GetTemplateByComplex($complexName, $value)
	{
		if ($complexName == '' || $value == '')
			return false;

		static $arList = Array();
		static $arEntityType = array();

		if (empty($arList))
		{
			if (empty($arEntityType))
				$arEntityType = self::GetEntityTypes();

			foreach($arEntityType as $entity => $ar)
				foreach($ar as $type => $ar)
					$arList[$entity.'_'.$type] = $ar['TEMPLATE'];
		}

		$valuer = $value;
		$valueUrl = $value;
		if (strpos($complexName, 'PHONE_') === 0)
		{
			$valuer = preg_replace('/[^+0-9]/', '', $valuer);
		}
		if (strpos($complexName, 'EMAIL_') === 0)
		{
			$crmEmail = strtolower(trim(COption::GetOptionString('crm', 'mail', '')));
			if($crmEmail !== '')
			{
				$valueUrl .= '?cc='.urlencode($crmEmail);
			}
		}

		else if ($pos = strpos($value, '://'))
		{
			$value_tmp = substr($value, $pos + 3);
			return str_replace(array('#VALUE#', '#VALUE_HTML#', '#VALUE_URL#'), array($value_tmp, htmlspecialcharsbx($value_tmp), htmlspecialcharsbx($valueUrl)), '<a href="#VALUE_URL#" target="_blank">#VALUE_HTML#</a>');
		}

		if (isset($arList[$complexName]))
		{
			return str_replace(array('#VALUE#', '#VALUE_HTML#', '#VALUE_URL#'), array($valuer, htmlspecialcharsbx($value), htmlspecialcharsbx($valueUrl)), $arList[$complexName]);
		}
		else
			return false;
	}

	public function CheckFields($arFields, $bCheckStatusId = true)
	{
		$aMsg = array();

		if (!is_set($arFields, 'TYPE_ID') || !is_set($arFields, 'VALUE_TYPE'))
			$aMsg[] = array('id'=>'VALUE', 'text'=>GetMessage('CRM_MF_ERR_GET_NAME'));
		else
		{
			$fieldName = self::GetEntityNameByComplex($arFields['TYPE_ID'].'_'.$arFields['VALUE_TYPE']);
			if (is_set($arFields, 'VALUE') && trim($arFields['VALUE']) == '')
				$aMsg[] = array('id'=>'VALUE', 'text'=>GetMessage('CRM_MF_ERR_VALUE', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'VALUE') && strlen($arFields['VALUE']) > 250)
				$aMsg[] = array('id'=>'VALUE', 'text'=>GetMessage('CRM_MF_ERR_VALUE_STRLEN', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'TYPE_ID') && trim($arFields['TYPE_ID']) == '')
				$aMsg[] = array('id'=>'TYPE_ID', 'text'=>GetMessage('CRM_MF_ERR_TYPE_ID', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'ENTITY_ID') && trim($arFields['ENTITY_ID']) == '')
				$aMsg[] = array('id'=>'ENTITY_ID', 'text'=>GetMessage('CRM_MF_ERR_ENTITY_ID', array('#FIELD_NAME#' => $fieldName)));
			if (is_set($arFields, 'ELEMENT_ID') && intval($arFields['ELEMENT_ID']) <= 0)
				$aMsg[] = array('id'=>'ELEMENT_ID', 'text'=>GetMessage('CRM_MF_ERR_ELEMENT_ID', array('#FIELD_NAME#' => $fieldName)));
			if ($arFields['TYPE_ID'] == 'EMAIL' && !check_email($arFields['VALUE']))
				$aMsg[] = array('id'=>'ELEMENT_ID', 'text'=>GetMessage('CRM_MF_ERR_EMAIL_VALUE', array('#FIELD_NAME#' => $fieldName)));

		}

		if (!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);
			return false;
		}

		return true;
	}

	public function CheckComplexFields($arFields)
	{
		foreach($arFields as $fieldType => $ar)
			foreach($ar as $fieldId => $arValue)
			{
				$fieldName = self::GetEntityNameByComplex($fieldType.'_'.$arValue['VALUE_TYPE']);
				if (strlen($arValue['VALUE']) > 250)
					$this->LAST_ERROR .= GetMessage('CRM_MF_ERR_VALUE_STRLEN', array('#FIELD_NAME#' => $fieldName))."<br />";
				if ($fieldType == 'EMAIL' && strlen($arValue['VALUE']) > 0 && !check_email($arValue['VALUE']))
					$this->LAST_ERROR .= GetMessage('CRM_MF_ERR_EMAIL_VALUE', array('#FIELD_NAME#' => $fieldName))."<br />";
			}

		if (strlen($this->LAST_ERROR) > 0)
			return false;

		return true;
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif)
	{
		$arMsg = Array();

		// prepare diff format
		$arField = Array();
		foreach($arFieldsOrig as $typeId => $arTypes)
			foreach($arTypes as $valueId => $arValues)
				$arField['original'][$valueId] = array_merge($arValues, Array('COMPLEX'=>$typeId.'_'.$arValues['VALUE_TYPE']));

		$addCnt = 1;
		foreach($arFieldsModif as $typeId => $arTypes)
			foreach($arTypes as $valueId => $arValues)
			{
				if ($valueId != 'n0' && substr($valueId, 0, 1) == 'n')
					$arField['modified']['add'.($addCnt++)] = array_merge($arValues, Array('COMPLEX'=>$typeId.'_'.$arValues['VALUE_TYPE']));
				elseif ($valueId != 'n0')
					$arField['modified'][$valueId] = array_merge($arValues, Array('COMPLEX'=>$typeId.'_'.$arValues['VALUE_TYPE']));
			}

		if(isset($arField['modified']))
		{
			foreach ($arField['modified'] as $fieldId => $arValue)
			{
				if (isset($arField['original'][$fieldId]))
				{
					if ($arValue['VALUE'] == "")
					{
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_DELETE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']))),
							'EVENT_TEXT_1' => $arField['original'][$fieldId]['VALUE'],
						);
					}
					else if ($arField['original'][$fieldId]['COMPLEX'] != $arValue['COMPLEX']
					&& $arField['original'][$fieldId]['VALUE'] != $arValue['VALUE'] && $arValue['VALUE'] != "")
					{

						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_DELETE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']))),
							'EVENT_TEXT_1' => $arField['original'][$fieldId]['VALUE'],
						);
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_ADD', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arValue['COMPLEX']))),
							'EVENT_TEXT_1' => $arValue['VALUE'],
						);
					}
					else if ($arField['original'][$fieldId]['COMPLEX'] != $arValue['COMPLEX'])
					{
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_MODIFY_TYPE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']))),
							'EVENT_TEXT_1' => self::GetEntityNameByComplex($arField['original'][$fieldId]['COMPLEX']),
							'EVENT_TEXT_2' => self::GetEntityNameByComplex($arValue['COMPLEX']),
						);
					}
					else if ($arField['original'][$fieldId]['VALUE'] != $arValue['VALUE'])
					{
						$arMsg[] = Array(
							'EVENT_NAME' => GetMessage('CRM_CF_FIELD_MODIFY_VALUE', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arValue['COMPLEX']))),
							'EVENT_TEXT_1' => $arField['original'][$fieldId]['VALUE'],
							'EVENT_TEXT_2' => $arValue['VALUE'],
						);
					}
				}
				elseif ($arValue['VALUE'] != "")
				{
					$arMsg[] = Array(
						'EVENT_NAME' => GetMessage('CRM_CF_FIELD_ADD', Array('#FIELD_NAME#' => self::GetEntityNameByComplex($arValue['COMPLEX']))),
						'EVENT_TEXT_1' => $arValue['VALUE'],
					);
				}
			}
		}
		return $arMsg;
	}

	public static function GetEntityFields($entityID, $elementID, $typeID, $bIgnoreEmpty = false, $bFullName = true)
	{
		$rsFields = self::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => $entityID,
				'ELEMENT_ID' => $elementID,
				'TYPE_ID' =>  $typeID
			)
		);

		$result = array();
		while($arField = $rsFields->Fetch())
		{
			if($bIgnoreEmpty && (!isset($arField['VALUE']) || strlen($arField['VALUE']) === 0))
			{
				continue;
			}

			$arField['ENTITY_NAME'] = self::GetEntityNameByComplex($arField['COMPLEX_ID'], $bFullName);
			$result[] = $arField;
		}

		return $result;
	}

	public static function ExtractValues(&$fields, $typeName)
	{
		if(!(is_array($fields) && $typeName !== ''))
		{
			return array();
		}

		$values = array();
		$data = isset($fields[$typeName]) ? $fields[$typeName] : null;
		if(is_array($data))
		{
			foreach($data as &$item)
			{
				$value = isset($item['VALUE']) ? $item['VALUE'] : '';
				if($value === '')
				{
					continue;
				}

				$valueType = isset($item['VALUE_TYPE']) ? $item['VALUE_TYPE'] : '';
				if(!isset($values[$valueType]))
				{
					$values[$valueType] = array();
				}

				if(isset($item['VALUE']))
				{
					$values[$valueType][] = $item['VALUE'];
				}
			}
			unset($item);
		}
		return $values;
	}

	public static function PrepareEntityInfoBatch($typeID, $entityID, array &$entityInfos, array $options = null)
	{
		global $DB;

		if(empty($entityInfos))
		{
			return;
		}

		$enableNormalization = is_array($options) && isset($options['ENABLE_NORMALIZATION']) && $options['ENABLE_NORMALIZATION'];

		$elementIDs = array_keys($entityInfos);
		$elementSql = implode(',', $elementIDs);
		$sql = "SELECT m1.ELEMENT_ID AS ELEMENT_ID, m1.VALUE AS VALUE, m2.CNT AS CNT FROM b_crm_field_multi m1 INNER JOIN (SELECT MIN(ID) AS MIN_ID, COUNT(*) AS CNT FROM b_crm_field_multi m0 WHERE ENTITY_ID = '{$entityID}' AND ELEMENT_ID IN ({$elementSql}) AND TYPE_ID = '{$typeID}' GROUP BY ENTITY_ID, ELEMENT_ID) m2 ON m1.ID = m2.MIN_ID";

		$err_mess = (self::err_mess()).'<br />Function: GetInfoBatch<br>Line: ';
		$dbResult = $DB->Query($sql, false, $err_mess.__LINE__);
		if(is_object($dbResult))
		{
			while($fields = $dbResult->Fetch())
			{
				$elementID = (int)$fields['ELEMENT_ID'];
				if(isset($entityInfos[$elementID]))
				{
					$value = $fields['VALUE'];
					if($enableNormalization && $typeID === 'PHONE')
					{
						$value = NormalizePhone($value, 1);
					}

					$entityInfos[$elementID][$typeID] = array(
						'FIRST_VALUE' => $value,
						'TOTAL' => (int)$fields['CNT']
					);
				}
			}
		}
	}
	private static function err_mess()
	{
		return '<br />Class: CCrmFieldMulti<br>File: '.__FILE__;
	}
}

?>
