<?php
IncludeModuleLangFile(__FILE__);

class CCrmEntitySelectorHelper
{
	public static function PrepareEntityInfo($entityTypeName, $entityID, $options = array())
	{
		$entityTypeName = strtoupper(strval($entityTypeName));
		$entityID = intval($entityID);
		if(!is_array($options))
		{
			$options = array();
		}

		$result = array(
			'TITLE' => "{$entityTypeName}_{$entityID}",
			'URL' => ''
		);

		if($entityTypeName === '' || $entityID <= 0)
		{
			return $result;
		}

		if($entityTypeName === 'CONTACT')
		{
			$obRes = CCrmContact::GetList(array(), array('=ID'=> $entityID), array('NAME', 'SECOND_NAME', 'LAST_NAME'));
			if($arRes = $obRes->Fetch())
			{
				$nameTemplate = isset($options['NAME_TEMPLATE']) ? $options['NAME_TEMPLATE'] : '';
				if($nameTemplate === '')
				{
					$nameTemplate = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
				}
				$result['TITLE'] = CUser::FormatName(
					$nameTemplate,
					array(
						'LOGIN' => '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : ''
					),
					false,
					false
				);

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array(
						'contact_id' => $entityID
					)
				);
			}
		}
		elseif($entityTypeName === 'COMPANY')
		{
			$obRes = CCrmCompany::GetList(array(), array('=ID'=> $entityID), array('TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = $arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array(
						'company_id' => $entityID
					)
				);
			}
		}
		elseif($entityTypeName === 'DEAL')
		{
			$obRes = CCrmDeal::GetList(array(), array('=ID'=> $entityID), array('TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = $arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array(
						'deal_id' => $entityID
					)
				);
			}
		}
		elseif($entityTypeName === 'QUOTE')
		{
			$obRes = CCrmQuote::GetList(array(), array('=ID'=> $entityID), false, false, array('QUOTE_NUMBER', 'TITLE'));
			if($arRes = $obRes->Fetch())
			{
				$result['TITLE'] = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

				$result['URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_show'),
					array(
						'quote_id' => $entityID
					)
				);
			}
		}

		return $result;
	}

	public static function PreparePopupItems($entityTypeNames, $addPrefix = true, $nameFormat = '', $count = 50)
	{
		if(!is_array($entityTypeNames))
		{
			$entityTypeNames = array(strval($entityTypeNames));

		}

		$addPrefix =  (bool)$addPrefix;
		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		}
		$count = intval($count);
		if($count <= 0)
		{
			$count = 50;
		}

		$arItems = array();
		foreach($entityTypeNames as $typeName)
		{
			$typeName = strtoupper(strval($typeName));

			if($typeName === 'CONTACT')
			{
				$obRes = CCrmContact::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arImg = array();
					if (!empty($arRes['PHOTO']) && !isset($arFiles[$arRes['PHOTO']]))
					{
						if(intval($arRes['PHOTO']) > 0)
						{
							$arImg = CFile::ResizeImageGet($arRes['PHOTO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
						}
					}

					$arRes['SID'] = $addPrefix ? 'C_'.$arRes['ID']: $arRes['ID'];
					$arItems[] = array(
						'title' => CUser::FormatName(
							$nameFormat,
							array(
								'LOGIN' => '',
								'NAME' => $arRes['NAME'],
								'SECOND_NAME' => $arRes['SECOND_NAME'],
								'LAST_NAME' => $arRes['LAST_NAME']
							),
							false,
							false
						),
						'desc'  => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
							array(
								'contact_id' => $arRes['ID']
							)
						),
						'image' => $arImg['src'],
						'type'  => 'contact',
						'selected' => 'N'
					);
				}
			}
			elseif($typeName === 'COMPANY')
			{
				$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
				$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');
				$obRes = CCrmCompany::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO')
				);

				$arFiles = array();
				while ($arRes = $obRes->Fetch())
				{
					$arImg = array();
					if (!empty($arRes['LOGO']) && !isset($arFiles[$arRes['LOGO']]))
					{
						if(intval($arRes['LOGO']) > 0)
							$arImg = CFile::ResizeImageGet($arRes['LOGO'], array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);

						$arFiles[$arRes['LOGO']] = $arImg['src'];
					}

					$arRes['SID'] = $addPrefix ? 'CO_'.$arRes['ID']: $arRes['ID'];

					$arDesc = Array();
					if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
						$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
					if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
						$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];


					$arItems[] = array(
						'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
						'desc' => implode(', ', $arDesc),
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
							array(
								'company_id' => $arRes['ID']
							)
						),
						'image' => $arImg['src'],
						'type'  => 'company',
						'selected' => 'N'
					);
				}
			}
			elseif($typeName === 'LEAD')
			{
				$obRes = CCrmLead::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arRes['SID'] = $addPrefix ? 'L_'.$arRes['ID']: $arRes['ID'];

					$arItems[] = array(
						'title' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'desc' => CUser::FormatName(
							$nameFormat,
							array(
								'LOGIN' => '',
								'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
								'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
								'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
							),
							false,
							false
						),
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
							array(
								'lead_id' => $arRes['ID']
							)
						),
						'type'  => 'lead',
						'selected' => 'N'
					);
				}
			}
			elseif($typeName === 'DEAL')
			{
				$obRes = CCrmDeal::GetListEx(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'TITLE', 'STAGE_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arRes['SID'] = $addPrefix ? 'D_'.$arRes['ID']: $arRes['ID'];

					$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
					$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

					$arItems[] = array(
						'title' => isset($arRes['TITLE']) ? str_replace(array(';', ','), ' ', $arRes['TITLE']) : '',
						'desc' => $clientTitle,
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'),
							array(
								'deal_id' => $arRes['ID']
							)
						),
						'type'  => 'deal',
						'selected' => 'N'
					);
				}
			}
			elseif($typeName === 'QUOTE')
			{
				$obRes = CCrmQuote::GetList(
					array('ID' => 'DESC'),
					array(),
					false,
					array('nTopCount' => $count),
					array('ID', 'QUOTE_NUMBER', 'TITLE', 'COMPANY_TITLE', 'CONTACT_FULL_NAME')
				);

				while ($arRes = $obRes->Fetch())
				{
					$arRes['SID'] = $addPrefix ? CCrmQuote::OWNER_TYPE.'_'.$arRes['ID']: $arRes['ID'];

					$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
					$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

					$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

					$arItems[] = array(
						'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
						'desc' => $clientTitle,
						'id' => $arRes['SID'],
						'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_quote_show'),
							array(
								'quote_id' => $arRes['ID']
							)
						),
						'type'  => 'quote',
						'selected' => 'N'
					);
				}
			}
		}
		unset($typeName);

		return $arItems;
	}

	public static function PrepareListItems($arSource)
	{
		$result = array();
		if(is_array($arSource))
		{
			foreach($arSource as $k => &$v)
			{
				$result[] = array('value' => $k, 'text' => $v);
			}
			unset($v);
		}
		return $result;
	}

	public static function PrepareCommonMessages()
	{
		return array(
			'lead'=> GetMessage('CRM_FF_LEAD'),
			'contact' => GetMessage('CRM_FF_CONTACT'),
			'company' => GetMessage('CRM_FF_COMPANY'),
			'deal'=> GetMessage('CRM_FF_DEAL'),
			'quote'=> GetMessage('CRM_FF_QUOTE'),
			'ok' => GetMessage('CRM_FF_OK'),
			'cancel' => GetMessage('CRM_FF_CANCEL'),
			'close' => GetMessage('CRM_FF_CLOSE'),
			'wait' => GetMessage('CRM_FF_WAIT'),
			'noresult' => GetMessage('CRM_FF_NO_RESULT'),
			'add' => GetMessage('CRM_FF_CHOISE'),
			'edit' => GetMessage('CRM_FF_CHANGE'),
			'search' => GetMessage('CRM_FF_SEARCH'),
			'last' => GetMessage('CRM_FF_LAST')
		);
	}
}
