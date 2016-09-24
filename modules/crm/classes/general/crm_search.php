<?php

IncludeModuleLangFile(__FILE__);

class CCrmSearch
{
	static $bReIndex = false;
	static $oCallback = null;
	static $callback_method = '';
	static $arMess = array();

	static public function UpdateSearch($arFilter, $ENTITY_TYPE, $bOverWrite = false)
	{
		if (!CModule::IncludeModule('search'))
			return false;

		$limit = 1000;
		switch ($ENTITY_TYPE)
		{
			case 'CONTACT':
				$obRes = CCrmContact::GetList(array('ID' => 'ASC'), $arFilter, array(), $limit);
				$sTitleID = 'FULL_NAME';
				break;
			case 'DEAL':
				$obRes = CCrmDeal::GetList(array('ID' => 'ASC'), $arFilter, array(), $limit);
				$sTitleID = 'TITLE';
				break;
			case 'INVOICE':
				$obRes = CCrmInvoice::GetList(array('ID' => 'DESC'), $arFilter, false, array('nTopCount' => $limit), array('*'));
				$sTitleID = 'ORDER_TOPIC';
				break;
			case 'QUOTE':
				$obRes = CCrmQuote::GetList(array('ID' => 'ASC'), $arFilter, false, array('nTopCount' => intval($limit)), array());
				$sTitleID = 'TITLE';
				break;
			case 'COMPANY':
				$obRes = CCrmCompany::GetList(array('ID' => 'ASC'), $arFilter, array(), $limit);
				$sTitleID = 'TITLE';
				break;
			default:
			case 'LEAD':
				$obRes = CCrmLead::GetList(array('ID' => 'ASC'), $arFilter, array(), $limit);
				$sTitleID = 'TITLE';
				$ENTITY_TYPE = 'LEAD';
				break;
		}

		if (!isset(self::$arMess[$ENTITY_TYPE]))
			self::$arMess[$ENTITY_TYPE] = __IncludeLang($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.strtolower($ENTITY_TYPE).'.show/lang/'.LANGUAGE_ID.'/component.php', true);

		$arAllResult = array();
		$qty = 0;
		$lastItemID = '';

		if(is_object($obRes))
		{
			while (($arRow = $obRes->Fetch()) !== false)
			{
				$elementID = $arRow['ID'];
				$lastItemID = $ENTITY_TYPE.'.'.$elementID;

				if ($ENTITY_TYPE === 'INVOICE')
					$arResult = CCrmInvoice::BuildSearchCard($arRow, self::$bReIndex);
				elseif ($ENTITY_TYPE === 'QUOTE')
					$arResult = CCrmQuote::BuildSearchCard($arRow, self::$bReIndex);
				else
				{
					$multiFields = array();
					if($ENTITY_TYPE === 'CONTACT' || $ENTITY_TYPE === 'COMPANY' || $ENTITY_TYPE === 'LEAD')
					{
						$obMultiFieldRes = CCrmFieldMulti::GetList(
							array('ID' => 'asc'),
							array('ENTITY_ID' => $ENTITY_TYPE, 'ELEMENT_ID' => $elementID)
						);
						while($multiField = $obMultiFieldRes->Fetch())
						{
							$fieldValue = $multiField['VALUE'];
							$fieldTypeID = $multiField['TYPE_ID'];
							if($fieldValue === '' || ($fieldTypeID !== 'PHONE' && $fieldTypeID !== 'EMAIL'))
							{
								continue;
							}

							if(!isset($multiFields[$fieldTypeID]))
							{
								$multiFields[$fieldTypeID] = array();
							}
							$multiFields[$fieldTypeID][] = $fieldValue;
						}
					}

					$arResult = self::_buildEntityCard($arRow, $sTitleID, $ENTITY_TYPE, array('FM' => $multiFields));
				}

				if (self::$bReIndex)
				{
					if (self::$oCallback)
					{
						$res = call_user_func(array(self::$oCallback, self::$callback_method), $arResult);
						if(!$res)
						{
							return $lastItemID;
						}
					}
				}
				else
				{
					CSearch::Index(
						'crm',
						$ENTITY_TYPE.'.'.$arRow['ID'],
						$arResult,
						$bOverWrite
					);
				}

				$arAllResult[] = $arResult;
				$qty++;
			}
		}

		if (!self::$bReIndex && !empty($arFilter['ID']) && $qty === 0)
		{
			CSearch::DeleteIndex('crm', (int)$arFilter['ID']);
		}

		if (self::$bReIndex && $qty === $limit && $lastItemID !== '')
		{
			return $lastItemID;
		}

		return $arAllResult;
	}

	static protected function _buildEntityCard($arEntity, $sTitle, $ENTITY_TYPE, $arOptions = null)
	{
		static $arEntityGroup = array();
		static $arStatuses = array();
		static $arSite = array();

		$sBody = $arEntity[$sTitle]."\n";
		$arField2status = array(
			'STATUS_ID' => 'STATUS',
			'SOURCE_ID' => 'SOURCE',
			'CURRENCY_ID' => 'CURRENCY',
			'PRODUCT_ID' => 'PRODUCT',
			'TYPE_ID' => 'CONTACT_TYPE',
			'STAGE_ID' => 'DEAL_STAGE',
			'EVENT_ID' => 'EVENT_TYPE',
			'COMPANY_TYPE' => 'COMPANY_TYPE',
			'EMPLOYEES' => 'EMPLOYEES',
			'INDUSTRY' => 'INDUSTRY'
		);
		foreach ($arEntity as $_k => $_v)
		{
			if ($_k == $sTitle || strpos($_k, '_BY_') !== false || strpos($_k, 'DATE_') === 0 || strpos($_k, 'UF_') === 0)
				continue ;

			if($ENTITY_TYPE === 'CONTACT' && ($_k === 'NAME' || $_k === 'SECOND_NAME' || $_k === 'LAST_NAME'))
			{
				//Already added as title
				continue;
			}

			if (is_array($_v))
				continue ;

			if($_k === 'COMMENTS')
			{
				$_v = CSearch::KillTags($_v);
			}
			$_v = trim($_v);

			if (isset($arField2status[$_k]))
			{
				if (!isset($arStatuses[$_k]))
					$arStatuses[$_k] = CCrmStatus::GetStatusList($arField2status[$_k]);
				$_v = $arStatuses[$_k][$_v];
			}

			if (!empty($_v) && !is_numeric($_v) && $_v != 'N' && $_v != 'Y')
				$sBody .= self::$arMess[$ENTITY_TYPE]['CRM_FIELD_'.$_k].": $_v\n";
		}

		if($ENTITY_TYPE === 'CONTACT' || $ENTITY_TYPE === 'COMPANY' || $ENTITY_TYPE === 'LEAD')
		{
			$multiFields = is_array($arOptions) && isset($arOptions['FM']) ? $arOptions['FM'] : null;
			if(is_array($multiFields))
			{
				foreach($multiFields as $typeID => $multiFieldItems)
				{
					if($typeID === 'PHONE')
					{
						$sBody .= GetMessage('CRM_PHONES').': '.implode(', ', $multiFieldItems)."\n";
					}
					elseif($typeID === 'EMAIL')
					{
						$sBody .= GetMessage('CRM_EMAILS').': '.implode(', ', $multiFieldItems)."\n";
					}
				}
			}
		}

		$sDetailURL = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_'.strtolower($ENTITY_TYPE).'_show'),
			array(
				strtolower($ENTITY_TYPE).'_id' => $arEntity['ID']
			)
		);

		$_arAttr = CCrmPerms::GetEntityAttr($ENTITY_TYPE, $arEntity['ID']);
		if (empty($arSite))
		{
			$by="sort";
			$order="asc";
			$rsSite = CSite::GetList($by, $order);
			while ($_arSite = $rsSite->Fetch())
				$arSite[] = $_arSite['ID'];
		}

		$sattr_d = '';
		$sattr_s = '';
		$sattr_u = '';
		$sattr_o = '';
		$arAttr = array();
		if (!isset($_arAttr[$arEntity['ID']]))
			$_arAttr[$arEntity['ID']] = array();

		$arAttr[] = $ENTITY_TYPE; // for perm X
		foreach ($_arAttr[$arEntity['ID']] as $_s)
		{
			if (preg_match('/^U/', $_s))
			{
				$sattr_u = $_s;
			}
			elseif (preg_match('/^D/', $_s))
			{
				$sattr_d = $_s;
			}
			elseif (preg_match('/^S/', $_s))
			{
				$sattr_s = $_s;
			}
			elseif (preg_match('/^O/', $_s))
			{
				$sattr_o = $_s;
			}
			$arAttr[] = "{$ENTITY_TYPE}_{$_s}";
		}

		if (!empty($sattr_s))
		{
			$arAttr[] = "{$ENTITY_TYPE}_{$sattr_s}";  // for perm X in status

			if (!empty($sattr_u))
			{
				$arAttr[] = "{$ENTITY_TYPE}_{$sattr_u}_{$sattr_s}";
			}

			if (!empty($sattr_d))
			{
				$arAttr[] = "{$ENTITY_TYPE}_{$sattr_d}_{$sattr_s}";
			}

			if (!empty($sattr_o))
			{
				$arAttr[] = "{$ENTITY_TYPE}_{$sattr_o}_{$sattr_s}";
			}
		}

		$arSitePath = array();
		foreach ($arSite as $sSite)
			$arSitePath[$sSite] = $sDetailURL;

		$arResult = Array(
			'LAST_MODIFIED' => $arEntity['DATE_MODIFY'],
			'DATE_FROM' => $arEntity['DATE_CREATE'],
			'TITLE' => GetMessage('CRM_'.$ENTITY_TYPE).': '.$arEntity[$sTitle],
			'PARAM1' => $ENTITY_TYPE,
			'PARAM2' => $arEntity['ID'],
			'SITE_ID' => $arSitePath,
			'PERMISSIONS' => $arAttr,
			'BODY' => $sBody,
			'TAGS' => 'crm,'.strtolower($ENTITY_TYPE).','.GetMessage('CRM_'.$ENTITY_TYPE)
		);

		if (self::$bReIndex)
			$arResult['ID'] = $ENTITY_TYPE.'.'.$arEntity['ID'];
		
		return $arResult;
	}

	static public function OnSearchReindex($NS = array(), $oCallback = null, $callback_method = '')
	{
		$arFilter = array();
		$ENTITY_TYPE = 'LEAD';
		if (isset($NS['ID']) && strlen($NS['ID']) > 0 && preg_match('/^[A-Z]+\.\d+$/'.BX_UTF_PCRE_MODIFIER, $NS['ID']))
		{
			$arTemp = explode('.', $NS['ID']);
			$ENTITY_TYPE = $arTemp[0];
			//Start processing from next entity
			$arFilter['>ID'] = intval($arTemp[1]);
		}

		self::$oCallback = $oCallback;
		self::$callback_method = $callback_method;
		self::$bReIndex = true;

		$arAllResult = array();
		if ($ENTITY_TYPE == 'LEAD')
		{
			$arResult = self::UpdateSearch($arFilter, 'LEAD');
			if(is_array($arResult))
			{
				//Save leads and go to contacts
				$arAllResult = array_merge($arAllResult, $arResult);
				$ENTITY_TYPE = 'CONTACT';
				if(!empty($arFilter))
				{
					$arFilter = array();
				}
			}
			else
			{
				//Termination of process
				self::$bReIndex = false;
				self::$oCallback = null;
				self::$callback_method = '';

				return $arResult;
			}
		}

		if ($ENTITY_TYPE == 'CONTACT')
		{
			$arResult = self::UpdateSearch($arFilter, 'CONTACT');
			if (is_array($arResult))
			{
				//Save contacts and go to companies
				$arAllResult = array_merge($arAllResult, $arResult);
				$ENTITY_TYPE = 'COMPANY';
				if(!empty($arFilter))
				{
					$arFilter = array();
				}
			}
			else
			{
				//Termination of process
				self::$bReIndex = false;
				self::$oCallback = null;
				self::$callback_method = '';

				return $arResult;
			}
		}

		if ($ENTITY_TYPE == 'COMPANY')
		{
			$arResult = self::UpdateSearch($arFilter, 'COMPANY');
			if (is_array($arResult))
			{
				//Save companies and go to deals
				$arAllResult = array_merge($arAllResult, $arResult);
				$ENTITY_TYPE = 'DEAL';
				if(!empty($arFilter))
				{
					$arFilter = array();
				}
			}
			else
			{
				//Termination of process
				self::$bReIndex = false;
				self::$oCallback = null;
				self::$callback_method = '';

				return $arResult;
			}
		}

		if ($ENTITY_TYPE == 'DEAL')
		{
			$arResult = self::UpdateSearch($arFilter, 'DEAL');
			if (is_array($arResult))
			{
				//Save deals and go to invoices
				$arAllResult = array_merge($arAllResult, $arResult);
				$ENTITY_TYPE = 'INVOICE';
			}
			else
			{
				self::$bReIndex = false;
				self::$oCallback = null;
				self::$callback_method = '';

				return $arResult;
			}
		}

		if ($ENTITY_TYPE == 'INVOICE')
		{
			$arResult = self::UpdateSearch($arFilter, 'INVOICE');
			if (is_array($arResult))
			{
				//Save deals and go to quotes
				$arAllResult = array_merge($arAllResult, $arResult);
				$ENTITY_TYPE = 'QUOTE';
			}
			else
			{
				self::$bReIndex = false;
				self::$oCallback = null;
				self::$callback_method = '';

				return $arResult;
			}
		}

		if ($ENTITY_TYPE == 'QUOTE')
		{
			$arResult = self::UpdateSearch($arFilter, 'QUOTE');
			if (is_array($arResult))
			{
				$arAllResult = array_merge($arAllResult, $arResult);
			}
			else
			{
				self::$bReIndex = false;
				self::$oCallback = null;
				self::$callback_method = '';

				return $arResult;
			}
		}

		self::$bReIndex = false;
		self::$oCallback = null;
		self::$callback_method = '';

		if($oCallback)
		{
			return false;
		}

		return $arAllResult;
	}

	function OnSearchCheckPermissions($FIELD)
	{
		global $USER;

		$CCrmPerms = new CCrmPerms($USER->GetID());
		$arAttr['LEAD'] = $CCrmPerms->GetUserAttrForSelectEntity('LEAD', 'READ');
		$arAttr['DEAL'] = $CCrmPerms->GetUserAttrForSelectEntity('DEAL', 'READ');
		$arAttr['INVOICE'] = $CCrmPerms->GetUserAttrForSelectEntity('INVOICE', 'READ');
		$arAttr['QUOTE'] = $CCrmPerms->GetUserAttrForSelectEntity('QUOTE', 'READ');
		$arAttr['CONTACT'] = $CCrmPerms->GetUserAttrForSelectEntity('CONTACT', 'READ');
		$arAttr['COMPANY'] = $CCrmPerms->GetUserAttrForSelectEntity('COMPANY', 'READ');

		$arRel = array();
		foreach ($arAttr as $ENTITY_TYPE => $_arRel)
		{
			foreach ($_arRel as $arRelType)
			{
				if (empty($arRelType))
				{
					$arRel[] = $ENTITY_TYPE;
					continue ;
				}
				$arattr_d = array();
				$sattr_s = '';
				$sattr_u = '';
				$sattr_o = '';
				foreach ($arRelType as $_s)
				{
					if ($_s[0] == 'U')
						$sattr_u = $_s;
					else if ($_s[0] == 'D')
						$arattr_d[] = $_s;
					else if ($_s[0] == 'S')
						$sattr_s = $_s;
					else if ($_s[0] == 'O')
						$sattr_o = $_s;
				}

				$sattr = $ENTITY_TYPE;
				if (!empty($arattr_d))
				{
					foreach ($arattr_d as $sattr_d)
					{
						$sattr = $sattr_u !== ''
							? "{$ENTITY_TYPE}_{$sattr_u}_{$sattr_d}"
							: "{$ENTITY_TYPE}_{$sattr_d}";

						if (!empty($sattr_s))
						{
							$sattr .= '_'.$sattr_s;
						}

						$arRel[] = $sattr;
					}
					if (!empty($sattr_o))
					{
						$sattr  .= '_'.$sattr_o;
						$arRel[] = $sattr;
					}
				}
				else
				{
					if (!empty($sattr_u))
						$sattr .= '_'.$sattr_u;
					if (!empty($sattr_s))
						$sattr .= '_'.$sattr_s;
					if (!empty($sattr_o))
						$sattr .= '_'.$sattr_o;
					$arRel[] = $sattr;
				}
			}
		}

		return $arRel;
	}

	static public function DeleteSearch($ENTITY_TYPE, $ENTITY_ID)
	{
		if (CModule::IncludeModule('search'))
		{
			CSearch::DeleteIndex('crm', $ENTITY_TYPE.'.'.$ENTITY_ID);
		}
	}
}

?>
