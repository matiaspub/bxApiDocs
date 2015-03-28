<?php
class CCrmOwnerType
{
	const Undefined = 0;
	const Lead = 1;    // refresh FirstOwnerType and LastOwnerType constants
	const Deal = 2;
	const Contact = 3;
	const Company = 4;
	const Invoice = 5;
	const Activity = 6;
	const Quote = 7;    // refresh FirstOwnerType and LastOwnerType constants
	const FirstOwnerType = 1;
	const LastOwnerType = 7;

	const LeadName = 'LEAD';
	const DealName = 'DEAL';
	const ContactName = 'CONTACT';
	const CompanyName = 'COMPANY';
	const InvoiceName = 'INVOICE';
	const ActivityName = 'ACTIVITY';
	const QuoteName = 'QUOTE';

	private static $ALL_DESCRIPTIONS = array();
	private static $ALL_CATEGORY_CAPTION = array();
	private static $CAPTIONS = array();
	private static $RESPONSIBLES = array();
	private static $INFOS = array();
	private static $INFO_STUB = null;
	private static $COMPANY_TYPE = null;
	private static $COMPANY_INDUSTRY = null;


	public static function IsDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::FirstOwnerType && $typeID <= self::LastOwnerType;
	}

	public static function ResolveID($name)
	{
		$name = strtoupper(trim(strval($name)));
		if($name == '')
		{
			return self::Undefined;
		}

		switch($name)
		{
			case CCrmOwnerTypeAbbr::Lead:
			case self::LeadName:
				return self::Lead;

			case CCrmOwnerTypeAbbr::Deal:
			case self::DealName:
				return self::Deal;

			case CCrmOwnerTypeAbbr::Contact:
			case self::ContactName:
				return self::Contact;

			case CCrmOwnerTypeAbbr::Company:
			case self::CompanyName:
				return self::Company;

			case self::InvoiceName:
				return self::Invoice;

			case self::ActivityName:
				return self::Activity;

			case CCrmOwnerTypeAbbr::Quote:
			case self::QuoteName:
				return self::Quote;

			default:
				return self::Undefined;
		}
	}

	public static function ResolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Lead:
				return self::LeadName;

			case self::Deal:
				return self::DealName;

			case self::Contact:
				return self::ContactName;

			case self::Company:
				return self::CompanyName;

			case self::Invoice:
				return self::InvoiceName;

			case self::Activity:
				return self::ActivityName;

			case self::Quote:
				return self::QuoteName;

			case self::Undefined:
			default:
				return '';
		}
	}

	public static function GetAllNames()
	{
		return array(self::ContactName, self::CompanyName, self::LeadName, self::DealName, self::InvoiceName, self::ActivityName, self::QuoteName);
	}

	public static function GetNames($types)
	{
		$result = array();
		if(is_array($types))
		{
			foreach($types as $typeID)
			{
				$typeID = intval($typeID);
				$name = self::ResolveName($typeID);
				if($name !== '')
				{
					$result[] = $name;
				}
			}
		}
		return $result;
	}

	public static function GetDescriptions($types)
	{
		$result = array();
		if(is_array($types))
		{
			foreach($types as $typeID)
			{
				$typeID = intval($typeID);
				$descr = self::GetDescription($typeID);
				if($descr !== '')
				{
					$result[$typeID] = $descr;
				}
			}
		}
		return $result;
	}

	public static function GetAll()
	{
		return array(self::Contact, self::Company, self::Lead, self::Deal, self::Invoice, self::Activity, self::Quote);
	}

	public static function GetAllDescriptions()
	{
		if(!self::$ALL_DESCRIPTIONS[LANGUAGE_ID])
		{
			IncludeModuleLangFile(__FILE__);
			self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = array(
				self::Lead => GetMessage('CRM_OWNER_TYPE_LEAD'),
				self::Deal => GetMessage('CRM_OWNER_TYPE_DEAL'),
				self::Contact => GetMessage('CRM_OWNER_TYPE_CONTACT'),
				self::Company => GetMessage('CRM_OWNER_TYPE_COMPANY'),
				self::Invoice => GetMessage('CRM_OWNER_TYPE_INVOICE'),
				self::Quote => GetMessage('CRM_OWNER_TYPE_QUOTE'),
			);
		}

		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}

	public static function GetAllCategoryCaptions()
	{
		if(!self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID])
		{
			IncludeModuleLangFile(__FILE__);
			self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID] = array(
				self::Lead => GetMessage('CRM_OWNER_TYPE_LEAD_CATEGORY'),
				self::Deal => GetMessage('CRM_OWNER_TYPE_DEAL_CATEGORY'),
				self::Contact => GetMessage('CRM_OWNER_TYPE_CONTACT_CATEGORY'),
				self::Company => GetMessage('CRM_OWNER_TYPE_COMPANY_CATEGORY'),
				self::Invoice => GetMessage('CRM_OWNER_TYPE_INVOICE_CATEGORY'),
				self::Quote => GetMessage('CRM_OWNER_TYPE_QUOTE_CATEGORY'),
			);
		}
		return self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID];
	}

	public static function GetDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : '';
	}

	public static function GetShowUrl($typeID, $ID, $bCheckPermissions = false)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Lead:
			{
				if ($bCheckPermissions && !CCrmLead::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => $ID)
				);
			}
			case self::Contact:
			{
				if ($bCheckPermissions && !CCrmContact::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array('contact_id' => $ID)
				);
			}
			case self::Company:
			{
				if ($bCheckPermissions && !CCrmCompany::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array('company_id' => $ID)
				);
			}
			case self::Deal:
			{
				if ($bCheckPermissions && !CCrmDeal::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array('deal_id' => $ID)
				);
			}
			case self::Activity:
			{
				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_activity_show'),
					array('activity_id' => $ID)
				);
			}
			case self::Invoice:
			{
				if ($bCheckPermissions && !CCrmInvoice::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_invoice_show'),
					array('invoice_id' => $ID)
				);
			}
			case self::Quote:
			{
				if ($bCheckPermissions && !CCrmQuote::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_show'),
					array('quote_id' => $ID)
				);
			}
			default:
				return '';
		}
	}
	public static function GetEditUrl($typeID, $ID, $bCheckPermissions = false)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			$ID = 0;
		}

		switch($typeID)
		{
			case self::Lead:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmLead::CheckUpdatePermission($ID) : CCrmLead::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_edit'),
					array('lead_id' => $ID)
				);
			}
			case self::Contact:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmContact::CheckUpdatePermission($ID) : CCrmContact::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_edit'),
					array('contact_id' => $ID)
				);
			}
			case self::Company:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmCompany::CheckUpdatePermission($ID) : CCrmCompany::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_edit'),
					array('company_id' => $ID)
				);
			}
			case self::Deal:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmDeal::CheckUpdatePermission($ID) : CCrmDeal::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_edit'),
					array('deal_id' => $ID)
				);
			}
			case self::Invoice:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmInvoice::CheckUpdatePermission($ID) : CCrmInvoice::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_invoice_edit'),
					array('invoice_id' => $ID)
				);
			}
			case self::Quote:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmQuote::CheckUpdatePermission($ID) : CCrmQuote::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_edit'),
					array('quote_id' => $ID)
				);
			}
			case self::Activity:
			{
				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_activity_edit'),
					array('activity_id' => $ID)
				);
			}
			default:
				return '';
		}
	}
	public static function GetCaption($typeID, $ID, $checkRights = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			return '';
		}

		$key = "{$typeID}_{$ID}";

		if(isset(self::$CAPTIONS[$key]))
		{
			return self::$CAPTIONS[$key];
		}

		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['TITLE'] : '');
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('NAME', 'SECOND_NAME', 'LAST_NAME'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!$arRes)
				{
					return (self::$CAPTIONS[$key] = '');
				}
				else
				{
					return (self::$CAPTIONS[$key] = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '',
							'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
							'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
							'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
						),
						false,
						false
					));
				}
			}
			case self::Company:
			{

				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['TITLE'] : '');
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['TITLE'] : '');
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, array('ORDER_TOPIC'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['ORDER_TOPIC'] : '');
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('QUOTE_NUMBER', 'TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$quoteTitle = empty($arRes['QUOTE_NUMBER']) ? '' : $arRes['QUOTE_NUMBER'];
				$quoteTitle = empty($arRes['TITLE']) ?
					$quoteTitle : (empty($quoteTitle) ? $arRes['TITLE'] : $quoteTitle.' - '.$arRes['TITLE']);
				$quoteTitle = empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle);
				return $quoteTitle;
			}
		}

		return '';
	}
	public static function TryGetEntityInfo($typeID, $ID, &$info, $checkPermissions = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if(self::$INFO_STUB === null)
		{
			self::$INFO_STUB = array('TITLE' => '', 'LEGEND' => '', 'IMAGE_FILE_ID' => 0, 'RESPONSIBLE_ID' => 0, 'SHOW_URL' => '');
		}

		if($ID <= 0)
		{
			$info = self::$INFO_STUB;
			return false;
		}

		$key = "{$typeID}_{$ID}";

		if($checkPermissions && !CCrmAuthorizationHelper::CheckReadPermission($typeID, $ID))
		{
			$info = self::$INFO_STUB;
			return false;
		}

		if(isset(self::$INFOS[$key]))
		{
			if(is_array(self::$INFOS[$key]))
			{
				$info = self::$INFOS[$key];
				return true;
			}
			else
			{
				$info = self::$INFO_STUB;
				return false;
			}
		}

		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ASSIGNED_BY_ID')
				);
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => CCrmLead::PrepareFormattedName($arRes),
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_lead_show'),
							array('lead_id' => $ID)
						)
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE', 'PHOTO', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => CCrmContact::PrepareFormattedName($arRes),
					'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_contact_show'),
							array('contact_id' => $ID)
						)
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(
					array(),
					array(
						'=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				if(self::$COMPANY_TYPE === null)
				{
					self::$COMPANY_TYPE = CCrmStatus::GetStatusList('COMPANY_TYPE');
				}
				if(self::$COMPANY_INDUSTRY === null)
				{
					self::$COMPANY_INDUSTRY = CCrmStatus::GetStatusList('INDUSTRY');
				}

				$legendParts = array();

				$typeID = isset($arRes['COMPANY_TYPE']) ? $arRes['COMPANY_TYPE'] : '';
				if($typeID !== '' && isset(self::$COMPANY_TYPE[$typeID]))
				{
					$legendParts[] = self::$COMPANY_TYPE[$typeID];
				}

				$industryID = isset($arRes['INDUSTRY']) ? $arRes['INDUSTRY'] : '';
				if($industryID !== '' && isset(self::$COMPANY_INDUSTRY[$industryID]))
				{
					$legendParts[] = self::$COMPANY_INDUSTRY[$industryID];
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => !empty($legendParts) ? implode(', ', $legendParts) : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['LOGO']) ? intval($arRes['LOGO']) : 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_company_show'),
							array('company_id' => $ID)
						)
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_deal_show'),
							array('deal_id' => $ID)
						)
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(
					array(),
					array('ID' => $ID),
					false,
					false,
					array('ORDER_TOPIC', 'RESPONSIBLE_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['ORDER_TOPIC']) ? $arRes['ORDER_TOPIC'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['RESPONSIBLE_ID']) ? intval($arRes['RESPONSIBLE_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_invoice_show'),
							array('invoice_id' => $ID)
						)
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_quote_show'),
							array('quote_id' => $ID)
						)
				);

				$info = self::$INFOS[$key];
				return true;
			}
		}

		$info = self::$INFO_STUB;
		return false;
	}
	public static function PrepareEntityInfoBatch($typeID, array &$entityInfos, $checkPermissions = true, $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$IDs = array_keys($entityInfos);
		$dbRes = null;
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'TITLE', 'COMPANY_TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ASSIGNED_BY_ID')
				);
				break;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE', 'PHOTO', 'ASSIGNED_BY_ID')
				);
				break;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'ASSIGNED_BY_ID')
				);
				break;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID')
				);
				break;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ORDER_TOPIC', 'RESPONSIBLE_ID')
				);
				break;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID')
				);
				break;
			}
		}

		if(!is_object($dbRes))
		{
			return;
		}

		$enableResponsible = isset($options['ENABLE_RESPONSIBLE']) && $options['ENABLE_RESPONSIBLE'] === true;
		$userIDs = null;
		while($arRes = $dbRes->Fetch())
		{
			$ID = intval($arRes['ID']);
			if(!isset($entityInfos[$ID]))
			{
				continue;
			}

			$info = self::PrepareEntityInfo($typeID, $ID, $arRes, $options);
			if(!is_array($info) || empty($info))
			{
				continue;
			}

			if($enableResponsible)
			{
				$responsibleID = $info['RESPONSIBLE_ID'];
				if($responsibleID > 0)
				{
					if($userIDs === null)
					{
						$userIDs = array($responsibleID);
					}
					elseif(!in_array($responsibleID, $userIDs, true))
					{
						$userIDs[] = $responsibleID;
					}
				}
			}

			$entityInfos[$ID] = array_merge($entityInfos[$ID], $info);
		}

		if($enableResponsible && is_array($userIDs) && !empty($userIDs))
		{
			$enablePhoto = isset($options['ENABLE_RESPONSIBLE_PHOTO']) ? $options['ENABLE_RESPONSIBLE_PHOTO'] : true;
			$userSelect = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'EMAIL', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE');
			if($enablePhoto)
			{
				$userSelect[] = 'PERSONAL_PHOTO';
			}

			$dbUsers = CUser::GetList(
				($by = 'id'), ($sort = 'asc'),
				array('ID' => implode('|', $userIDs)),
				array('FIELDS' => $userSelect)
			);

			$photoSize = null;
			if($enablePhoto)
			{
				$photoSize = isset($options['PHOTO_SIZE']) ? $options['PHOTO_SIZE'] : array();
				if(!isset($photoSize['WIDTH']) || !isset($photoSize['HEIGHT']))
				{
					if(isset($photoSize['WIDTH']))
					{
						$photoSize['HEIGHT'] = $photoSize['WIDTH'];
					}
					elseif(isset($photoSize['HEIGHT']))
					{
						$photoSize['WIDTH'] = $photoSize['HEIGHT'];
					}
					else
					{
						$photoSize['WIDTH'] = $photoSize['HEIGHT'] = 50;
					}
				}
			}

			$userInfos = array();
			while($user = $dbUsers->Fetch())
			{
				$userID = intval($user['ID']);
				$personalPhone =  isset($user['PERSONAL_PHONE']) ? $user['PERSONAL_PHONE'] : '';
				$personalMobile =  isset($user['PERSONAL_MOBILE']) ? $user['PERSONAL_MOBILE'] : '';
				$workPhone =  isset($user['WORK_PHONE']) ? $user['WORK_PHONE'] : '';
				$userPhone = $workPhone !== '' ? $workPhone : ($personalMobile !== '' ? $personalMobile : $personalPhone);

				$userInfo = array(
					'FORMATTED_NAME' => CUser::FormatName(
						//\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						CSite::GetNameFormat(false),
						$user,
						true,
						false
					),
					'EMAIL' => isset($user['EMAIL']) ? $user['EMAIL'] : '',
					'PHONE' => $userPhone
				);

				if($enablePhoto)
				{
					$photoID = isset($user['PERSONAL_PHOTO']) ? intval($user['PERSONAL_PHOTO']) : 0;
					if($photoID > 0)
					{
						$photoUrl = CFile::ResizeImageGet(
							$photoID,
							array('width' => $photoSize['WIDTH'], 'height' => $photoSize['HEIGHT']),
							BX_RESIZE_IMAGE_EXACT
						);
						$userInfo['PHOTO_URL'] = $photoUrl['src'];
					}
				}

				$userInfos[$userID] = &$userInfo;
				unset($userInfo);
			}

			if(!empty($userInfos))
			{
				foreach($entityInfos as &$info)
				{
					$responsibleID = $info['RESPONSIBLE_ID'];
					if($responsibleID > 0 && isset($userInfos[$responsibleID]))
					{
						$userInfo = $userInfos[$responsibleID];
						$info['RESPONSIBLE_FULL_NAME'] = $userInfo['FORMATTED_NAME'];

						if(isset($userInfo['PHOTO_URL']))
						{
							$info['RESPONSIBLE_PHOTO_URL'] = $userInfo['PHOTO_URL'];
						}

						if(isset($userInfo['EMAIL']))
						{
							$info['RESPONSIBLE_EMAIL'] = $userInfo['EMAIL'];
						}

						if(isset($userInfo['PHONE']))
						{
							$info['RESPONSIBLE_PHONE'] = $userInfo['PHONE'];
						}
					}
				}
				unset($info);
			}
		}
	}
	private static function PrepareEntityInfo($typeID, $ID, &$arRes, $options = null)
	{
		$enableEditUrl = is_array($options) && isset($options['ENABLE_EDIT_URL']) && $options['ENABLE_EDIT_URL'] === true;
		switch($typeID)
		{
			case self::Lead:
			{
				$treatAsContact = false;
				$treatAsCompany = false;

				if(is_array($options))
				{
					$treatAsContact = isset($options['TREAT_AS_CONTACT']) && $options['TREAT_AS_CONTACT'];
					$treatAsCompany = isset($options['TREAT_AS_COMPANY']) && $options['TREAT_AS_COMPANY'];
				}

				if($treatAsContact)
				{
					$result = array(
						'TITLE' => CCrmLead::PrepareFormattedName($arRes),
						'LEGEND' => isset($arRes['TITLE']) ? $arRes['TITLE'] : ''
					);
				}
				elseif($treatAsCompany)
				{
					$result = array(
						'TITLE' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
						'LEGEND' => isset($arRes['TITLE']) ? $arRes['TITLE'] : ''
					);
				}
				else
				{
					$result = array(
						'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'LEGEND' => CCrmLead::PrepareFormattedName($arRes)
					);
				}

				$result['RESPONSIBLE_ID'] = isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				$result['IMAGE_FILE_ID'] = 0;
				$result['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => $ID)
				);

				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_lead_edit'),
							array('lead_id' => $ID)
						);
				}
				return $result;
			}
			case self::Contact:
			{
				$result = array(
					'TITLE' => CCrmContact::PrepareFormattedName($arRes),
					'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_contact_show'),
							array('contact_id' => $ID)
						)
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_contact_edit'),
							array('contact_id' => $ID)
						);
				}
				return $result;
			}
			case self::Company:
			{
				if(self::$COMPANY_TYPE === null)
				{
					self::$COMPANY_TYPE = CCrmStatus::GetStatusList('COMPANY_TYPE');
				}
				if(self::$COMPANY_INDUSTRY === null)
				{
					self::$COMPANY_INDUSTRY = CCrmStatus::GetStatusList('INDUSTRY');
				}

				$legendParts = array();

				$typeID = isset($arRes['COMPANY_TYPE']) ? $arRes['COMPANY_TYPE'] : '';
				if($typeID !== '' && isset(self::$COMPANY_TYPE[$typeID]))
				{
					$legendParts[] = self::$COMPANY_TYPE[$typeID];
				}

				$industryID = isset($arRes['INDUSTRY']) ? $arRes['INDUSTRY'] : '';
				if($industryID !== '' && isset(self::$COMPANY_INDUSTRY[$industryID]))
				{
					$legendParts[] = self::$COMPANY_INDUSTRY[$industryID];
				}

				$result = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => !empty($legendParts) ? implode(', ', $legendParts) : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['LOGO']) ? intval($arRes['LOGO']) : 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_company_show'),
							array('company_id' => $ID)
						)
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_company_edit'),
							array('company_id' => $ID)
						);
				}
				return $result;
			}
			case self::Deal:
			{
				$result = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_deal_show'),
							array('deal_id' => $ID)
						)
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_deal_edit'),
							array('deal_id' => $ID)
						);
				}
				return $result;
			}
			case self::Invoice:
			{
				$result = array(
					'TITLE' => isset($arRes['ORDER_TOPIC']) ? $arRes['ORDER_TOPIC'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['RESPONSIBLE_ID']) ? intval($arRes['RESPONSIBLE_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_invoice_show'),
							array('invoice_id' => $ID)
						)
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_invoice_edit'),
							array('invoice_id' => $ID)
						);
				}
				return $result;
			}
			case self::Quote:
			{
				$result = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_quote_show'),
							array('quote_id' => $ID)
						)
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_quote_edit'),
							array('quote_id' => $ID)
						);
				}
				return $result;
			}
		}
		return null;
	}

	public static function ResolveUserFieldEntityID($typeID)
	{
		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Lead:
				return CAllCrmLead::$sUFEntityID;
			case self::Deal:
				return CAllCrmDeal::$sUFEntityID;
			case self::Contact:
				return CAllCrmContact::$sUFEntityID;
			case self::Company:
				return CAllCrmCompany::$sUFEntityID;
			case self::Invoice:
				return CAllCrmInvoice::$sUFEntityID;
			case self::Undefined:
			case self::Quote:
				return CAllCrmQuote::$sUFEntityID;
			default:
				return '';
		}
	}

	private static function GetFields($typeID, $ID, $options = array())
	{
		$typeID = intval($typeID);
		$ID = intval($ID);
		$options = is_array($options) ? $options : array();

		$select = isset($options['SELECT']) ? $options['SELECT'] : array();
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
		}

		return null;
	}

	public static function GetFieldsInfo($typeID)
	{
		$typeID = intval($typeID);

		switch($typeID)
		{
			case self::Lead:
			{
				return CCrmLead::GetFieldsInfo();
			}
			case self::Contact:
			{
				return CCrmContact::GetFieldsInfo();
			}
			case self::Company:
			{
				return CCrmCompany::GetFieldsInfo();
			}
			case self::Deal:
			{
				return CCrmDeal::GetFieldsInfo();
			}
			case self::Quote:
			{
				return CCrmQuote::GetFieldsInfo();
			}
		}

		return null;
	}

	public static function GetFieldIntValue($typeID, $ID, $fieldName)
	{
		$fields = self::GetFields($typeID, $ID, array('SELECT' => array($fieldName)));
		return is_array($fields) && isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
	}

	public static function GetResponsibleID($typeID, $ID, $checkRights = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if(!(self::IsDefined($typeID) && $ID > 0))
		{
			return 0;
		}

		$key = "{$typeID}_{$ID}";
		if(isset(self::$RESPONSIBLES[$key]))
		{
			return self::$RESPONSIBLES[$key];
		}

		$result = 0;
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, array('RESPONSIBLE_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['RESPONSIBLE_ID']) : 0;
				break;
			}
			case self::Activity:
			{
				$dbRes = CCrmActivity::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('RESPONSIBLE_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['RESPONSIBLE_ID']) : 0;
				break;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
		}

		self::$RESPONSIBLES[$key] = $result;
		return $result;
	}

	public static function IsOpened($typeID, $ID, $checkRights = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
		}

		return false;
	}

	public static function TryGetOwnerInfos($typeID, $ID, &$owners, $options = array())
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeIDKey = isset($options['ENTITY_TYPE_ID_KEY']) ? $options['ENTITY_TYPE_ID_KEY'] : '';
		if($entityTypeIDKey === '')
		{
			$entityTypeIDKey = 'ENTITY_TYPE_ID';
		}

		$entityIDKey = isset($options['ENTITY_ID_KEY']) ? $options['ENTITY_ID_KEY'] : '';
		if($entityIDKey === '')
		{
			$entityIDKey = 'ENTITY_ID';
		}

		$additionalData = isset($options['ADDITIONAL_DATA']) && is_array($options['ADDITIONAL_DATA']) ? $options['ADDITIONAL_DATA'] : null;

		switch($typeID)
		{
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID), false, false, array('COMPANY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;

				if(!is_array($arRes))
				{
					return false;
				}

				$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
				if($companyID <= 0)
				{
					return false;
				}

				$info = array(
					$entityTypeIDKey => self::Company,
					$entityIDKey => $companyID
				);

				if($additionalData !== null)
				{
					$info = array_merge($info, $additionalData);
				}

				$owners[] = &$info;
				unset($info);
				return true;
			}
			//break;
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID), false, false, array('CONTACT_ID', 'COMPANY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;

				if(!is_array($arRes))
				{
					return false;
				}

				$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
				$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
				if($contactID <= 0 && $companyID <= 0)
				{
					return false;
				}

				if($contactID > 0)
				{
					$info = array(
						$entityTypeIDKey => self::Contact,
						$entityIDKey => $contactID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					$owners[] = &$info;
					unset($info);
				}
				if($companyID > 0)
				{
					$info =  array(
						$entityTypeIDKey => self::Company,
						$entityIDKey => $companyID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					$owners[] = &$info;
					unset($info);
				}
				return true;
			}
			//break;
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID), false, false, array('CONTACT_ID', 'COMPANY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;

				if(!is_array($arRes))
				{
					return false;
				}

				$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
				$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
				if($contactID <= 0 && $companyID <= 0)
				{
					return false;
				}

				if($contactID > 0)
				{
					$info = array(
						$entityTypeIDKey => self::Contact,
						$entityIDKey => $contactID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					$owners[] = &$info;
					unset($info);
				}
				if($companyID > 0)
				{
					$info =  array(
						$entityTypeIDKey => self::Company,
						$entityIDKey => $companyID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					$owners[] = &$info;
					unset($info);
				}
				return true;
			}
			//break;
		}
		return false;
	}

	public static function TryGetInfo($typeID, $ID, &$info, $bCheckPermissions = false)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			return array();
		}

		$result = null;
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('NAME', 'SECOND_NAME', 'LAST_NAME', 'PHOTO'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => CUser::FormatName(
							\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
							array(
								'LOGIN' => '',
								'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
								'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
								'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
							), false, false),
						'IMAGE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0
					);
					return true;
				}
				break;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE', 'LOGO'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => isset($arRes['LOGO']) ? intval($arRes['LOGO']) : 0
					);
					return true;
				}
				break;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, array('ORDER_TOPIC'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['ORDER_TOPIC']) ? $arRes['ORDER_TOPIC'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
		}
		return false;
	}
}

class CCrmOwnerTypeAbbr
{
	const Undefined = '';
	const Lead = 'L';
	const Deal = 'D';
	const Contact = 'C';
	const Company = 'CO';
	const Invoice = 'I';
	const Quote = 'Q';

	public static function ResolveByTypeID($typeID)
	{
		$typeID = intval($typeID);

		switch($typeID)
		{
			case CCrmOwnerType::Lead:
				return self::Lead;
			case CCrmOwnerType::Deal:
				return self::Deal;
			case CCrmOwnerType::Contact:
				return self::Contact;
			case CCrmOwnerType::Company:
				return self::Company;
			case CCrmOwnerType::Invoice:
				return self::Invoice;
			case CCrmOwnerType::Quote:
				return self::Quote;
			default:
				return self::Undefined;
		}
	}

	public static function ResolveName($typeAbbr)
	{
		$typeAbbr = strtoupper(trim(strval($typeAbbr)));
		if($typeAbbr === '')
		{
			return '';
		}

		switch($typeAbbr)
		{
			case self::Lead:
				return 'LEAD';
			case self::Deal:
				return 'DEAL';
			case self::Contact:
				return 'CONTACT';
			case self::Company:
				return 'COMPANY';
			case self::Invoice:
				return 'INVOICE';
			case self::Quote:
				return 'QUOTE';
			default:
				return '';
		}
	}
}

