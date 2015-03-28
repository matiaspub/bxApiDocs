<?php
IncludeModuleLangFile(__FILE__);

class CCrmViewHelper
{
	private static $DEAL_STAGES = null;
	private static $LEAD_STATUSES = null;
	private static $QUOTE_STATUSES = null;
	private static $INVOICE_STATUSES = null;
	private static $USER_INFO_PROVIDER_MESSAGES_REGISTRED = false;

	public static function PrepareClientBaloonHtml($arParams)
	{
		return self::PrepareEntityBaloonHtml($arParams);
	}
	public static function PrepareEntityBaloonHtml($arParams)
	{
		if(!is_array($arParams))
		{
			return '';
		}

		$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
		$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : '';

		if($entityTypeID <= 0 || $entityID <= 0)
		{
			return '';
		}

		$showPath = isset($arParams['SHOW_URL']) ? $arParams['SHOW_URL'] : '';

		if($entityTypeID === CCrmOwnerType::Company)
		{
			if($showPath === '')
			{
				$showPath = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array('company_id' => $entityID)
				);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Company, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			$baloonID = $prefix !== '' ? "BALLOON_{$prefix}_CO_{$entityID}" : "BALLOON_CO_{$entityID}";
			return '<a href="'.htmlspecialcharsbx($showPath).'" id="'.$baloonID.'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').'>'.htmlspecialcharsbx($title).'</a>'.
				'<script type="text/javascript">BX.tooltip("COMPANY_'.$entityID.'", "'.$baloonID.'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>';
		}
		elseif($entityTypeID === CCrmOwnerType::Contact)
		{
			if($showPath === '')
			{
				$showPath = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array('contact_id' => $entityID)
				);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Contact, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			$baloonID = $prefix !== '' ? "BALLOON_{$prefix}_C_{$entityID}" : "BALLOON_C_{$entityID}";
			return '<a href="'.htmlspecialcharsbx($showPath).'" id="'.$baloonID.'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').'>'.htmlspecialcharsbx($title).'</a>'.
				'<script type="text/javascript">BX.tooltip("CONTACT_'.$entityID.'", "'.$baloonID.'", "/bitrix/components/bitrix/crm.contact.show/card.ajax.php", "crm_balloon_contact", true);</script>';
		}
		elseif($entityTypeID === CCrmOwnerType::Lead)
		{
			if($showPath === '')
			{
				$showPath = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => $entityID)
				);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CUser::FormatName(
					\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
					array(
						'LOGIN' => '',
						'NAME' => isset($arParams['NAME']) ? $arParams['NAME'] : '',
						'LAST_NAME' => isset($arParams['LAST_NAME']) ? $arParams['LAST_NAME'] : '',
						'SECOND_NAME' => isset($arParams['SECOND_NAME']) ? $arParams['SECOND_NAME'] : ''
					),
					false, false
				);
			}

			$baloonID = $prefix !== '' ? "BALLOON_{$prefix}_L_{$entityID}" : "BALLOON_L_{$entityID}";
			return '<a href="'.htmlspecialcharsbx($showPath).'" id="'.$baloonID.'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').'>'.htmlspecialcharsbx($title).'</a>'.
				'<script type="text/javascript">BX.tooltip("LEAD_'.$entityID.'", "'.$baloonID.'", "/bitrix/components/bitrix/crm.lead.show/card.ajax.php", "crm_balloon_lead", true);</script>';
		}
		elseif($entityTypeID === CCrmOwnerType::Deal)
		{
			if($showPath === '')
			{
				$showPath = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array('deal_id' => $entityID)
				);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			$baloonID = $prefix !== '' ? "BALLOON_{$prefix}_D_{$entityID}" : "BALLOON_D_{$entityID}";
			return '<a href="'.htmlspecialcharsbx($showPath).'" id="'.$baloonID.'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').'>'.htmlspecialcharsbx($title).'</a>'.
				'<script type="text/javascript">BX.tooltip("DEAL_'.$entityID.'", "'.$baloonID.'", "/bitrix/components/bitrix/crm.deal.show/card.ajax.php", "crm_balloon_no_photo", true);</script>';

		}
		elseif($entityTypeID === CCrmOwnerType::Quote)
		{
			if($showPath === '')
			{
				$showPath = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_show'),
					array('quote_id' => $entityID)
				);
			}

			$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
			if($title === '')
			{
				$title = CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $entityID, (isset($arParams['CHECK_PERMISSIONS']) && $arParams['CHECK_PERMISSIONS'] == 'N' ? false : true));
			}

			$baloonID = $prefix !== '' ? "BALLOON_{$prefix}_".CCrmQuote::OWNER_TYPE."_{$entityID}" : "BALLOON_".CCrmQuote::OWNER_TYPE."_{$entityID}";
			return '<a href="'.htmlspecialcharsbx($showPath).'" id="'.$baloonID.'"'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '').'>'.htmlspecialcharsbx($title).'</a>'.
				'<script type="text/javascript">BX.tooltip("QUOTE_'.$entityID.'", "'.$baloonID.'", "/bitrix/components/bitrix/crm.quote.show/card.ajax.php", "crm_balloon_no_photo", true);</script>';

		}
		return '';
	}
	public static function GetFormattedUserName($userID, $format = '', $htmlEncode = false)
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			return '';
		}

		$format = strval($format);
		if($format === '')
		{
			$format = CSite::GetNameFormat(false);
		}

		$dbUser = CUser::GetList(
			($by = 'id'),
			($order = 'asc'),
			array('ID'=> $userID),
			array(
				'FIELDS'=> array(
					'ID',
					'LOGIN',
					'EMAIL',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME'
				)
			)
		);

		$user = $dbUser ? $dbUser->Fetch() : null;
		return is_array($user) ? CUser::FormatName($format, $user, true, $htmlEncode) : '';
	}
	public static function RenderInfo($url, $titleHtml, $descriptionHtml, $target = '_blank', $onclick = '')
	{
		$url = strval($url);
		$titleHtml = strval($titleHtml);
		$descriptionHtml = strval($descriptionHtml);
		$target = strval($target);
		$onclick = strval($onclick);

		$result = '';
		if($url !== '' || $titleHtml !== '')
		{
			$result .= '<div class="crm-info-title-wrapper">';
			if($url !== '')
			{
				$result .= '<a target="'.htmlspecialcharsbx($target).'" href="'.$url.'"';
				if($onclick !== '')
				{
					$result .= ' onclick="'.CUtil::JSEscape($onclick).'"';
				}

				$result .= '>'.($titleHtml !== '' ? $titleHtml : $url).'</a>';
			}
			elseif($titleHtml !== '')
			{
				$result .= $titleHtml;
			}
			$result .= '</div>';
		}
		if($descriptionHtml !== '')
		{
			$result .= '<div class="crm-info-description-wrapper">'.$descriptionHtml.'</div>';
		}

		return '<div class="crm-info-wrapper">'.$result.'</div>';
	}
	public static function PrepareClientInfo($arParams)
	{
		$result = '<div class="crm-info-title-wrapper">';
		$result .= self::PrepareClientBaloonHtml($arParams);
		$result .= '</div>';

		$description = isset($arParams['DESCRIPTION']) ? $arParams['DESCRIPTION'] : '';
		if($description !== '')
		{
			$result .= '<div class="crm-info-description-wrapper">'.htmlspecialcharsbx($description).'</div>';
		}

		return '<div class="crm-info-wrapper">'.$result.'</div>';
	}
	public static function PrepareClientInfoV2($arParams)
	{
		$showUrl = isset($arParams['SHOW_URL']) ? $arParams['SHOW_URL'] : '';
		if($showUrl === '')
		{
			$entityTypeID = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
			$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
			if($entityTypeID > 0 && $entityID > 0)
			{
				$showUrl = CCrmOwnerType::GetShowUrl($entityTypeID, $entityID);
			}
		}

		$photoID = isset($arParams['PHOTO_ID']) ? intval($arParams['PHOTO_ID']) : 0;
		$photoUrl = $photoID > 0
			? CFile::ResizeImageGet($photoID, array('width' => 30, 'height' => 30), BX_RESIZE_IMAGE_EXACT)
			: '';

		$name = isset($arParams['NAME']) ? $arParams['NAME'] : '';
		$description = isset($arParams['DESCRIPTION']) ? $arParams['DESCRIPTION'] : '';
		$html = isset($arParams['ADDITIONAL_HTML']) ? $arParams['ADDITIONAL_HTML'] : '';

		if($showUrl !== '')
		{
			return '<a class="crm-item-client-block" href="'
				.htmlspecialcharsbx($showUrl).'"><div class="crm-item-client-img">'
				.(isset($photoUrl['src']) ? '<img alt="" src="'.htmlspecialcharsbx($photoUrl['src']).'"/>' : '')
				.'</div>'
				.'<span class="crm-item-client-alignment"></span>'
				.'<span class="crm-item-client-alignment-block">'
				.'<div class="crm-item-client-name">'
				.htmlspecialcharsbx($name).'</div><div class="crm-item-client-description">'
				.htmlspecialcharsbx($description).$html.'</div></span></a>';
		}

		return '<span class="crm-item-client-block"><div class="crm-item-client-img">'
			.(isset($photoUrl['src']) ? '<img alt="" src="'.htmlspecialcharsbx($photoUrl['src']).'"/>' : '')
			.'</div>'
			.'<span class="crm-item-client-alignment"></span>'
			.'<span class="crm-item-client-alignment-block">'
			.'<div class="crm-item-client-name">'
			.htmlspecialcharsbx($name).'</div><div class="crm-item-client-description">'
			.htmlspecialcharsbx($description).$html.'</div></span></span>';
	}
	public static function RenderClientSummary($url, $titleHtml, $descriptionHtml, $photoHtml = '', $target = '_self')
	{
		$url = strval($url);
		$titleHtml = strval($titleHtml);
		$descriptionHtml = strval($descriptionHtml);
		$photoHtml = strval($photoHtml);

		$result = '<div class="crm-client-photo-wrapper">'.($photoHtml !== ''
			? $photoHtml
			: '<img src="/bitrix/js/tasks/css/images/avatar.png" alt=""/>').'</div>';

		$result .= '<div class="crm-client-info-wrapper">';
		if($url !== '' || $titleHtml !== '')
		{
			$result .= '<div class="crm-client-title-wrapper">';
			if($url !== '')
			{
				$result .= '<a target="'.htmlspecialcharsbx($target).'" href="'.$url.'">'
					.($titleHtml !== '' ? $titleHtml : htmlspecialcharsbx($url)).'</a>';
			}
			elseif($titleHtml !== '')
			{
				$result .= $titleHtml;
			}
			$result .= '</div>';
		}
		if($descriptionHtml !== '')
		{
			$result .= '<div class="crm-client-description-wrapper">'.$descriptionHtml.'</div>';
		}
		$result .= '</div>';

		return '<div class="crm-client-summary-wrapper">'.$result.'<div style="clear:both;"></div></div>';
	}
	public static function RenderClientSummaryPanel($arParams, $arOptions = array())
	{
		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$showUrl = isset($arParams['SHOW_URL']) ? $arParams['SHOW_URL'] : '';
		$title = isset($arParams['TITLE']) ? $arParams['TITLE'] : '';
		if($title === '')
		{
			$title = GetMessage('CRM_ENTITY_INFO_CONTACT');
		}

		echo '<div class="crm-detail-info-resp-block">';
		echo '<div class="crm-detail-info-resp-header">';
		echo '<span class="crm-detail-info-resp-text">', htmlspecialcharsbx($title), '</span>';
		echo '</div>';

		$containerID = isset($arParams['CONTAINER_ID']) ? $arParams['CONTAINER_ID'] : '';
		if($containerID === '')
		{
			$containerID = $prefix !== '' ? "{$prefix}_client_container" : 'client_container';
		}
		echo '<a class="crm-detail-info-resp" id="', htmlspecialcharsbx($containerID), '" target="_blank" href="', htmlspecialcharsbx($showUrl), '">';

		echo '<div class="crm-detail-info-resp-img">';
		$imageUrl = isset($arParams['IMAGE_URL']) ? $arParams['IMAGE_URL'] : '';
		$imageID = isset($arParams['IMAGE']) ? intval($arParams['IMAGE']) : 0;
		if($imageUrl === '' && $imageID > 0)
		{
			$imageInfo = CFile::ResizeImageGet($imageID, array('width' => 32, 'height' => 32), BX_RESIZE_IMAGE_EXACT);
			$imageUrl = is_array($imageInfo) && isset($imageInfo['src']) ? $imageInfo['src'] : '';
		}

		if($imageUrl !== '')
		{
			echo '<img alt="" src="', htmlspecialcharsbx($imageUrl), '"/>';
		}

		echo '</div>';

		echo '<span class="crm-detail-info-resp-name">', (isset($arParams['NAME']) ? htmlspecialcharsbx($arParams['NAME']) : ''), '</span>';

		echo '<span class="crm-detail-info-resp-descr">', (isset($arParams['DESCRIPTION']) ? htmlspecialcharsbx($arParams['DESCRIPTION']) : ''), '</span>';
		echo '</a>';

		$arEntityTypes = CCrmFieldMulti::GetEntityTypes();

		$fields = isset($arParams['FM']) ? $arParams['FM'] : null;
		if(isset($fields['PHONE']) && is_array($fields['PHONE']) && !empty($fields['PHONE']))
		{
			echo '<div class="crm-detail-info-item">';
			echo '<span class="crm-detail-info-item-name">', GetMessage('CRM_ENTITY_INFO_PHONE'), ':', '</span>';
			echo self::PrepareFormMultiField(array('FM'=>array('PHONE' => $fields['PHONE'])), 'PHONE', $prefix, $arEntityTypes, $arOptions);
			echo '</div>';
		}

		if(isset($fields['EMAIL']) && is_array($fields['EMAIL']) && !empty($fields['EMAIL']))
		{
			echo '<div class="crm-detail-info-item">';
			echo '<span class="crm-detail-info-item-name">', GetMessage('CRM_ENTITY_INFO_EMAIL'), ':', '</span>';
			echo self::PrepareFormMultiField(array('FM'=>array('EMAIL' => $fields['EMAIL'])), 'EMAIL', $prefix, $arEntityTypes, $arOptions);
			echo '</div>';
		}
		echo '</div>';
	}
	public static function RenderNearestActivity($arParams)
	{
		$gridManagerID = isset($arParams['GRID_MANAGER_ID']) ? $arParams['GRID_MANAGER_ID'] : '';
		$mgrID = strtolower($gridManagerID);

		$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? strtolower($arParams['ENTITY_TYPE_NAME']) : '';
		$entityID = isset($arParams['ENTITY_ID']) ? $arParams['ENTITY_ID'] : '';

		$allowEdit = isset($arParams['ALLOW_EDIT']) ? $arParams['ALLOW_EDIT'] : false;
		$menuItems = isset($arParams['MENU_ITEMS']) ? $arParams['MENU_ITEMS'] : array();
		$menuID = CUtil::JSEscape("bx_{$mgrID}_{$entityTypeName}_{$entityID}_activity_add");

		$ID = isset($arParams['ACTIVITY_ID']) ? intval($arParams['ACTIVITY_ID']) : 0;
		if($ID > 0)
		{
			$subject = isset($arParams['ACTIVITY_SUBJECT']) ? $arParams['ACTIVITY_SUBJECT'] : '';


			$time = isset($arParams['ACTIVITY_TIME']) ? MakeTimeStamp($arParams['ACTIVITY_TIME']) : 0;
			$timeFormatted = $time > 0 ? CCrmComponentHelper::TrimDateTimeString(FormatDate('FULL', $time)) : '';
			$isExpired = isset($arParams['ACTIVITY_EXPIRED']) ? $arParams['ACTIVITY_EXPIRED'] : ($time <= (time() + CTimeZone::GetOffset()));

			$result = '<div class="crm-nearest-activity-wrapper"><div class="crm-list-deal-date crm-nearest-activity-time'.($isExpired ? '-expiried' : '').'"><a class="crm-link" target = "_self" href = "#"
				onclick="BX.CrmInterfaceGridManager.viewActivity(\''.CUtil::JSEscape($gridManagerID).'\', '.$ID.', { enableEditButton:'.($allowEdit ? 'true' : 'false').' }); return false;">'
				.htmlspecialcharsbx($timeFormatted).'</a></div><div class="crm-nearest-activity-subject">'.htmlspecialcharsbx($subject).'</div>';

			if($allowEdit && !empty($menuItems))
			{
				$result .= '<div class="crm-nearest-activity-plus" onclick="BX.CrmInterfaceGridManager.showMenu(\''.$menuID.'\', this);"></div>
					<script type="text/javascript">BX.CrmInterfaceGridManager.createMenu("'.$menuID.'", '.CUtil::PhpToJSObject($menuItems).');</script>';
			}

			$result .= '</div>';

			$responsibleID = isset($arParams['ACTIVITY_RESPONSIBLE_ID']) ? intval($arParams['ACTIVITY_RESPONSIBLE_ID']) : 0;
			if($responsibleID > 0)
			{
				$nameTemplate = isset($arParams['NAME_TEMPLATE']) ? $arParams['NAME_TEMPLATE'] : '';
				if($nameTemplate === '')
				{
					$nameTemplate = CSite::GetNameFormat(false);
				}

				$responsibleFullName = CUser::FormatName(
					$nameTemplate,
					array(
						'LOGIN' => isset($arParams['ACTIVITY_RESPONSIBLE_LOGIN']) ? $arParams['ACTIVITY_RESPONSIBLE_LOGIN'] : '',
						'NAME' => isset($arParams['ACTIVITY_RESPONSIBLE_NAME']) ? $arParams['ACTIVITY_RESPONSIBLE_NAME'] : '',
						'LAST_NAME' => isset($arParams['ACTIVITY_RESPONSIBLE_LAST_NAME']) ? $arParams['ACTIVITY_RESPONSIBLE_LAST_NAME'] : '',
						'SECOND_NAME' => isset($arParams['ACTIVITY_RESPONSIBLE_SECOND_NAME']) ? $arParams['ACTIVITY_RESPONSIBLE_SECOND_NAME'] : ''
					),
					true, false
				);

				$responsibleShowUrl = '';
				$pathToUserProfile = isset($arParams['PATH_TO_USER_PROFILE']) ? $arParams['PATH_TO_USER_PROFILE'] : '';
				if($pathToUserProfile !== '')
				{
					$responsibleShowUrl = CComponentEngine::MakePathFromTemplate(
						$pathToUserProfile,
						array('user_id' => $responsibleID)
					);
				}
				$result .= '<div class="crm-list-deal-responsible"><span class="crm-list-deal-responsible-grey">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_ACTIVITY_FOR_RESPONSIBLE')).'</span><a class="crm-list-deal-responsible-name" target="_blank" href="'.htmlspecialcharsbx($responsibleShowUrl).'">'.htmlspecialcharsbx($responsibleFullName).'</a></div>';
			}
			return $result;
		}
		elseif($allowEdit && !empty($menuItems))
		{
			return '<span class="crm-activity-add-hint">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_ADD_ACTIVITY_HINT')).'</span>
				<a class="crm-activity-add" onclick="BX.CrmInterfaceGridManager.showMenu(\''.$menuID.'\', this); return false;">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_ADD_ACTIVITY')).'</a>
				<script type="text/javascript">BX.CrmInterfaceGridManager.createMenu("'.$menuID.'", '.CUtil::PhpToJSObject($menuItems).');</script>';
		}

		return '';
	}
	public static function RenderListMultiFields(&$arFields, $prefix = '', $arOptions = null)
	{
		$result = array();

		$arEntityTypes = CCrmFieldMulti::GetEntityTypes();

		$arInfos = CCrmFieldMulti::GetEntityTypeInfos();
		foreach($arInfos as $typeID => &$arInfo)
		{
			$result[$typeID] = self::RenderListMultiField($arFields, $typeID, $prefix, $arEntityTypes, $arOptions);
		}
		unset($arInfo);
		return $result;
	}
	public static function RenderListMultiField(&$arFields, $typeName, $prefix = '', $arEntityTypes = null, $arOptions = null)
	{
		$typeName = strtoupper(strval($typeName));
		$prefix = strval($prefix);

		if(!is_array($arEntityTypes))
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
		}

		$result = '';

		$arValueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
		if(!empty($arValueTypes))
		{
			$values = self::PrepareListMultiFieldValues($arFields, $typeName, $arValueTypes);
			$result .= '<div class="bx-crm-multi-field-wrapper">'
				.self::RenderListMultiFieldValues("{$prefix}{$typeName}", $values, $typeName, $arValueTypes, $arOptions)
				.'</div>';
		}

		return $result;
	}
	public static function PrepareFormMultiField($arEntityFields, $typeName, $prefix = '', $arEntityTypes = null, $arOptions = null)
	{
		$typeName = strtoupper(strval($typeName));
		$prefix = strval($prefix);

		if(!is_array($arEntityTypes))
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
		}

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$result = '';
		$qty = 0;
		$nableSip = false;
		$valueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
		if(!empty($valueTypes))
		{
			$values = array();
			$fields = isset($arEntityFields['FM']) && $arEntityFields['FM'][$typeName] ? $arEntityFields['FM'][$typeName] : null;

			$firstItemParams = null;
			if(is_array($fields))
			{
				foreach($fields as &$field)
				{
					$valueType = $field['VALUE_TYPE'];
					$value = $field['VALUE'];

					if($firstItemParams === null)
					{
						$firstItemParams = array('VALUE' => $value, 'VALUE_TYPE_ID' => $valueType);
						if(isset($valueTypes[$valueType]))
						{
							$firstItemParams['VALUE_TYPE'] = $valueTypes[$valueType];
						}
					}

					if(!isset($values[$valueType]))
					{
						$values[$valueType] = array();
					}
					$values[$valueType][] = $value;
					$qty++;
				}
				unset($field);
			}

			if($firstItemParams !== null)
			{
				$itemData = self::PrepareMultiFieldValueItemData($typeName, $firstItemParams, $arOptions);
				$result = $itemData['value'];
				if($typeName === 'PHONE' && isset($itemData['sipCallHtml']) && $itemData['sipCallHtml'] !== '')
				{
					$result .= $itemData['sipCallHtml'];
					$nableSip = true;
				}
			}

			if($qty > 1)
			{
				$anchorID = $prefix.'_'.strtolower($typeName);
				$result .= '<span class="crm-item-tel-list" id="'.htmlspecialcharsbx($anchorID).'" onclick="'
					.CCrmViewHelper::PrepareMultiFieldValuesPopup($anchorID, $anchorID, $typeName, $values, $valueTypes, array_merge($arOptions, array('SKIP_FIRST' => true)))
					.'"><span>';
			}
		}

		$containerClassName = 'crm-detail-info-item-text';
		if($qty > 1)
		{
			$containerClassName .= ' crm-detail-info-item-list';
		}
		if($nableSip)
		{
			$containerClassName .= ' crm-detail-info-item-handset';
		}

		return "<span class=\"{$containerClassName}\">{$result}</span>";
	}
	public static function PrepareMultiFieldCalltoLink($phone)
	{
		$linkAttrs = CCrmCallToUrl::PrepareLinkAttributes($phone);
		return '<a class="crm-fld-text" href="'
			.htmlspecialcharsbx($linkAttrs['HREF'])
			.'" onclick="'.htmlspecialcharsbx($linkAttrs['ONCLICK']).'">'
			.htmlspecialcharsbx($phone).'</a>';
	}
	public static function PrepareMultiFieldHtml($typeName, $arParams, $arOptions = array())
	{
		$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';
		$valueUrl = $value;
		if($typeName === 'PHONE')
		{
			$additionalHtml = '';
			$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
			if($enableSip)
			{
				$sipParams =  isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;
				$additionalHtml = self::PrepareSipCallHtml($value, $sipParams);
			}

			$linkAttrs = CCrmCallToUrl::PrepareLinkAttributes($value);
			$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : 'crm-item-tel-num';
			return '<a'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '')
				.' title="'.htmlspecialcharsbx($value).'"'
				.' href="'.htmlspecialcharsbx($linkAttrs['HREF']).'"'
				.' onclick="'.htmlspecialcharsbx($linkAttrs['ONCLICK'])
				.'">'
				.htmlspecialcharsbx($value).'</a>'.$additionalHtml;
		}
		elseif($typeName === 'EMAIL')
		{
			$crmEmail = strtolower(trim(COption::GetOptionString('crm', 'mail', '')));
			if($crmEmail !== '')
			{
				$valueUrl = $valueUrl.'?cc='.urlencode($crmEmail);
			}

			$className = isset($arParams['CLASS_NAME']) ? $arParams['CLASS_NAME'] : 'crm-item-tel-num';
			return '<a'.($className !== '' ? ' class="'.htmlspecialcharsbx($className).'"' : '')
				.' title="'.htmlspecialcharsbx($value).'"'
				.' href="mailto:'.htmlspecialcharsbx($valueUrl).'">'
				.htmlspecialcharsbx($value).'</a>';
		}
		elseif($typeName === 'WEB')
		{
			$valueUrl = preg_replace('/^\s*http(s)?:\/\//i', '', $value);
		}

		$valueTypeID = isset($arParams['VALUE_TYPE_ID']) ? $arParams['VALUE_TYPE_ID'] : '';
		$valueType = isset($arParams['VALUE_TYPE']) ? $arParams['VALUE_TYPE'] : null;
		if(!$valueType && $valueTypeID !== '')
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
			$arValueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;
		}

		if(!($valueType && !empty($valueType['TEMPLATE'])))
		{
			return htmlspecialcharsbx($value);
		}

		return str_replace(
			array(
				'#VALUE#',
				'#VALUE_URL#',
				'#VALUE_HTML#'
			),
			array(
				$value,
				htmlspecialcharsbx($valueUrl),
				htmlspecialcharsbx($value)
			),
			$valueType['TEMPLATE']
		);
	}
	public static function PrepareListMultiFieldValues(&$arFields, $typeName, &$arValueTypes)
	{
		$typeName = strtoupper(strval($typeName));

		$result = array();
		foreach($arValueTypes as $valueTypeID => &$arValueType)
		{
			$key1 = "~{$typeName}_{$valueTypeID}";
			$key2 = "{$typeName}_{$valueTypeID}";
			if(isset($arFields[$key1]))
			{
				$result[$valueTypeID] = $arFields[$key1];
			}
			elseif(isset($arFields[$key2]))
			{
				$result[$valueTypeID] = $arFields[$key2];
			}
		}
		unset($arValueType);

		return $result;
	}
	public static function PrepareSipCallHtml($phone, $params = null)
	{
		if(!CCrmSipHelper::checkPhoneNumber($phone))
		{
			return '';
		}

		$entityType = is_array($params) && isset($params['ENTITY_TYPE']) ? $params['ENTITY_TYPE'] : '';
		$entityID = is_array($params) && isset($params['ENTITY_ID']) ? intval($params['ENTITY_ID']) : 0;
		return '<span class="crm-tel-btn" onclick="BX.CrmSipManager.startCall({ number:\''.CUtil::JSEscape($phone).'\', enableInfoLoading: true }, { ENTITY_TYPE: \''.CUtil::JSEscape($entityType).'\', ENTITY_ID: \''.CUtil::JSEscape($entityID).'\' }, true, this);"></span>';
	}
	private static function RenderListMultiFieldValues($ID, &$arValues, $typeName, &$arValueTypes, $arOptions = null)
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$ID = strval($ID);
		if($ID === '')
		{
			$ID = uniqid('CRM_MULTI_FIELD_');
		}

		$typeName = strtoupper(strval($typeName));
		$result = '';
		$arValueData = array();
		foreach($arValueTypes as $valueTypeID => &$arValueType)
		{
			if(!isset($arValues[$valueTypeID]) || empty($arValues[$valueTypeID]))
			{
				continue;
			}

			foreach($arValues[$valueTypeID] as $value)
			{
				$arValueData[] = array(
					'VALUE_TYPE_ID' => $valueTypeID,
					'VALUE' => $value
				);
			}
		}
		unset($arValueType);

		$qty = count($arValueData);
		if($qty === 0)
		{
			return '';
		}

		$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
		$sipParams =  $enableSip && isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;

		$first = $arValueData[0];
		$firstValueType = isset($arValueTypes[$first['VALUE_TYPE_ID']]) ? $arValueTypes[$first['VALUE_TYPE_ID']] : null;
		if($firstValueType)
		{
			if($typeName === 'PHONE' && $enableSip)
			{
				$additionalHtml = self::PrepareSipCallHtml($first['VALUE'], $sipParams);
				$result .= '<div class="crm-multi-field-value-wrapper" style="white-space:nowrap;">'
					.self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE' => $firstValueType, 'VALUE' => $first['VALUE']))
					.$additionalHtml.'</div>';
			}
			else
			{
				$result .= '<div class="crm-multi-field-value-wrapper">'
					.self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE' => $firstValueType, 'VALUE' => $first['VALUE']))
					.'</div>';
			}
		}

		if($qty > 1)
		{
			$arPopupItems = array();
			for($i = 1; $i < $qty; $i++)
			{
				$current = $arValueData[$i];
				$valueType = isset($arValueTypes[$current['VALUE_TYPE_ID']]) ? $arValueTypes[$current['VALUE_TYPE_ID']] : null;
				if(!$valueType)
				{
					continue;
				}

				$popupItemData = array(
					'value' => htmlspecialcharsbx(
						self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE' => $valueType, 'VALUE' => $current['VALUE']))
					),
					'type' => htmlspecialcharsbx(
						isset($valueType['SHORT']) ? strtolower($valueType['SHORT']) : ''
					)
				);

				if($typeName === 'PHONE' && $enableSip)
				{
					$popupItemData['sipCallHtml'] = htmlspecialcharsbx(self::PrepareSipCallHtml($current['VALUE'], $sipParams));
				}

				$arPopupItems[] = &$popupItemData;
				unset($popupItemData);
			}

			$buttonID = $ID.'_BTN';
			$result .= '<div class="crm-multi-field-popup-wrapper">';
			$result .= '<span id="'.htmlspecialcharsbx($buttonID)
				.'" class="crm-multi-field-popup-button" onclick="BX.CrmMultiFieldViewer.ensureCreated(\''
				.CUtil::JSEscape($ID).'\', { \'anchorId\':\''.CUtil::JSEscape($buttonID).'\', \'items\':'.CUtil::PhpToJSObject($arPopupItems).', \'typeName\':\''.CUtil::JSEscape($typeName).'\' }).show();">'.htmlspecialcharsbx(GetMessage('CRM_ENTITY_MULTI_FIELDS_MORE')).' '.($qty - 1).'</span>';
			$result .= '</div>';
		}

		return $result;
	}
	public static function PrepareFirstMultiFieldHtml($typeName, $arValues, $arValueTypes, $arParams = array(), $arOptions = array())
	{
		foreach($arValues as $valueTypeID => $values)
		{
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;

			foreach($values as $value)
			{
				if($value !== '')
				{
					if(!is_array($arParams))
					{
						$arParams = array();
					}
					$arParams['VALUE_TYPE'] = $valueType;
					$arParams['VALUE'] = $value;
					return self::PrepareMultiFieldHtml($typeName, $arParams, $arOptions);
				}
			}
		}
		return '';
	}
	public static function PrepareMultiFieldValuesPopup($popupID, $achorID, $typeName, $arValues, $arValueTypes, $arOptions = array())
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
		$sipParams =  $enableSip && isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;
		$skipFirst =  isset($arOptions['SKIP_FIRST']) ? $arOptions['SKIP_FIRST'] : false;
		$isSkipped = false;
		$arPopupItems = array();
		foreach($arValues as $valueTypeID => $values)
		{
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;

			foreach($values as $value)
			{
				if($skipFirst && !$isSkipped)
				{
					$isSkipped = true;
					continue;
				}

				$popupItemData = array(
					'value' => htmlspecialcharsbx(
						self::PrepareMultiFieldHtml($typeName, array('VALUE_TYPE' => $valueType, 'VALUE' => $value))
					),
					'type' => htmlspecialcharsbx(
						isset($valueType['SHORT']) ? strtolower($valueType['SHORT']) : ''
					)
				);

				if($enableSip)
				{
					$popupItemData['sipCallHtml'] = htmlspecialcharsbx(self::PrepareSipCallHtml($value, $sipParams));
				}

				$arPopupItems[] = &$popupItemData;
				unset($popupItemData);
			}
		}

		$topmost =  isset($arOptions['TOPMOST']) ? $arOptions['TOPMOST'] : false;
		return 'BX.CrmMultiFieldViewer.ensureCreated(\''
			.CUtil::JSEscape($popupID).'\', { \'anchorId\':\''
			.CUtil::JSEscape($achorID).'\', \'items\':'
			.CUtil::PhpToJSObject($arPopupItems)
			.', \'typeName\':\''.CUtil::JSEscape($typeName).'\''
			.', \'topmost\':'.($topmost ? 'true' : 'false')
			.' }).show();';
	}
	public static function PrepareMultiFieldValueItemData($typeName, $params, $arOptions = array())
	{
		$enableSip = is_array($arOptions) && isset($arOptions['ENABLE_SIP']) && (bool)$arOptions['ENABLE_SIP'];
		$sipParams =  $enableSip && isset($arOptions['SIP_PARAMS']) ? $arOptions['SIP_PARAMS'] : null;
		$value = isset($params['VALUE']) ? $params['VALUE'] : '';
		$valueTypeID = isset($params['VALUE_TYPE_ID']) ? $params['VALUE_TYPE_ID'] : '';
		$valueType = isset($params['VALUE_TYPE']) ? $params['VALUE_TYPE'] : null;
		if(!$valueType && $valueTypeID !== '')
		{
			$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
			$arValueTypes = isset($arEntityTypes[$typeName]) ? $arEntityTypes[$typeName] : array();
			$valueType = isset($arValueTypes[$valueTypeID]) ? $arValueTypes[$valueTypeID] : null;
		}

		$itemData = array(
			'value' =>
				self::PrepareMultiFieldHtml($typeName, $params),
			'type' => htmlspecialcharsbx(
				is_array($valueType) && isset($valueType['SHORT']) ? strtolower($valueType['SHORT']) : ''
			)
		);

		if($typeName === 'PHONE' && $enableSip)
		{
			$itemData['sipCallHtml'] = self::PrepareSipCallHtml($value, $sipParams);
		}

		return $itemData;
	}
	public static function PrepareFormResponsible($userID, $nameTemplate, $userProfileUrlTemplate)
	{
		$userID = (int)$userID;
		if($userID <= 0)
		{
			return '';
		}


		$dbUsers = CUser::GetList(
			($by = 'id'), ($sort = 'asc'),
			array('ID' => $userID),
			array('FIELDS' =>  array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'EMAIL', 'PERSONAL_PHOTO'))
		);

		$user = $dbUsers->Fetch();
		if(!is_array($user))
		{
			return '';
		}

		$name = CUser::FormatName(
			//\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			$nameTemplate,
			$user,
			true,
			true
		);

		$photoID = isset($user['PERSONAL_PHOTO']) ? intval($user['PERSONAL_PHOTO']) : 0;
		$photoUrl = '';
		if($photoID > 0)
		{
			$photoInfo = CFile::ResizeImageGet(
				$photoID,
				array('width' => 32, 'height' => 32),
				BX_RESIZE_IMAGE_EXACT
			);
			$photoUrl = is_array($photoInfo) ? $photoInfo['src'] : '';
		}

		$showUrl = $userID > 0 && $userProfileUrlTemplate !== '' ? str_replace('#user_id#', $userID, $userProfileUrlTemplate) : '#';

		return "<span class=\"crm-detail-info-resp\"><div class=\"crm-detail-info-resp-img\"><a href=\"{$showUrl}\" target=\"_blank\"><img alt=\"\" src=\"{$photoUrl}\" /></a></div><a class=\"crm-detail-info-resp-name\" target=\"_blank\">{$name}</a></span>";
	}
	public static function RenderResponsiblePanel($arParams)
	{
		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$editable = isset($arParams['EDITABLE']) ? $arParams['EDITABLE'] : false;
		$userProfileUrlTemplate = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : '';
		$userID = isset($arParams['USER_ID']) ? $arParams['USER_ID'] : '';
		$showUrl = $userID > 0 && $userProfileUrlTemplate !== '' ? str_replace('#user_id#', $userID, $userProfileUrlTemplate) : '#';

		echo '<div class="crm-detail-info-resp-block">';
		echo '<div class="crm-detail-info-resp-header">';
		echo '<span class="crm-detail-info-resp-text">', htmlspecialcharsbx(GetMessage('CRM_ENTITY_INFO_RESPONSIBLE')), '</span>';

		$editButtonID = '';
		if($editable)
		{
			$editButtonID = isset($arParams['EDIT_BUTTON_ID']) ? $arParams['EDIT_BUTTON_ID'] : '';
			if($editButtonID === '')
			{
				$editButtonID = $prefix !== '' ? "{$prefix}_responsible_edit" : 'responsible_edit';
			}
			echo '<span class="crm-detail-info-resp-edit" id="', htmlspecialcharsbx($editButtonID), '">', htmlspecialcharsbx(GetMessage('CRM_ENTITY_INFO_RESPONSIBLE_CHANGE')), '</span>';
		}

		echo '</div>';

		$containerID = isset($arParams['CONTAINER_ID']) ? $arParams['CONTAINER_ID'] : '';
		if($containerID === '')
		{
			$containerID = $prefix !== '' ? "{$prefix}_responsible_container" : 'responsible_container';
		}
		echo '<a class="crm-detail-info-resp" id="', htmlspecialcharsbx($containerID), '" target="_blank" href="', htmlspecialcharsbx($showUrl), '">';

		echo '<div class="crm-detail-info-resp-img">';
		$photoID = isset($arParams['PHOTO']) ? intval($arParams['PHOTO']) : 0;
		if($photoID > 0)
		{
			$photoUrl = CFile::ResizeImageGet($photoID, array('width' => 32, 'height' => 32), BX_RESIZE_IMAGE_EXACT);
			echo '<img alt="" src="', htmlspecialcharsbx($photoUrl['src']), '"/>';
		}
		echo '</div>';

		echo '<span class="crm-detail-info-resp-name">', (isset($arParams['NAME']) ? htmlspecialcharsbx($arParams['NAME']) : ''), '</span>';

		echo '<span class="crm-detail-info-resp-descr">', (isset($arParams['WORK_POSITION']) ? htmlspecialcharsbx($arParams['WORK_POSITION']) : ''), '</span>';
		echo '</a>';

		$serviceUrl = isset($arParams['SERVICE_URL']) ? $arParams['SERVICE_URL'] : '';
		$userInfoProviderID = isset($arParams['USER_INFO_PROVIDER_ID']) ? $arParams['USER_INFO_PROVIDER_ID'] : '';
		if($userInfoProviderID === '')
		{
			$userInfoProviderID = $serviceUrl !== '' ? md5(strtolower($serviceUrl)) : '';
		}

		if($userInfoProviderID !== '')
		{
			if(!self::$USER_INFO_PROVIDER_MESSAGES_REGISTRED)
			{
				echo '<script type="text/javascript">',
					'BX.ready(function(){',
					'BX.CrmUserInfoProvider.messages = ',
						'{ "generalError":"', GetMessageJS('CRM_GET_USER_INFO_GENERAL_ERROR'), '" }',
					'});',
					'</script>';

				self::$USER_INFO_PROVIDER_MESSAGES_REGISTRED = true;
			}

			echo '<script type="text/javascript">',
				'BX.ready(function(){',
				'BX.CrmUserInfoProvider.createIfNotExists(',
					'"', CUtil::JSEscape($userInfoProviderID), '",',
					'{ "serviceUrl":"', CUtil::JSEscape($serviceUrl), '", "userProfileUrlTemplate":"', CUtil::JSEscape($userProfileUrlTemplate) , '" }',
				');',
				'});',
				'</script>';
		}

		$instantEditorID = isset($arParams['INSTANT_EDITOR_ID']) ? $arParams['INSTANT_EDITOR_ID'] : '';
		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';

		if(!$editable)
		{
			echo '<script type="text/javascript">',
				'BX.ready(function(){',
				'BX.CrmUserLinkField.create(',
					'{',
					'"containerId":"', CUtil::JSEscape($containerID), '"',
					', "userInfoProviderId":"', CUtil::JSEscape($userInfoProviderID), '"',
					', "editorId":"', CUtil::JSEscape($instantEditorID), '"',
					', "fieldId":"', CUtil::JSEscape($fieldID), '"',
					'}',
				');',
				'});',
				'</script>';
		}
		else
		{
			$userSelectorName = isset($arParams['USER_SELECTOR_NAME']) ? $arParams['USER_SELECTOR_NAME'] : '';
			if($userSelectorName === '')
			{
				$userSelectorName = $prefix !== '' ? "{$prefix}_responsible_selector" : 'responsible_selector';
			}

			$enableLazyLoad = isset($arParams['ENABLE_LAZY_LOAD']) ? $arParams['ENABLE_LAZY_LOAD'] : false;
			if($enableLazyLoad)
			{
				$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js');
				$GLOBALS['APPLICATION']->SetAdditionalCSS('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/style.css');
			}
			else
			{
				$GLOBALS['APPLICATION']->IncludeComponent(
					'bitrix:intranet.user.selector.new',
					'.default',
					array(
						'MULTIPLE' => 'N',
						'NAME' => $userSelectorName,
						'POPUP' => 'Y',
						'SITE_ID' => SITE_ID
					),
					null,
					array('HIDE_ICONS' => 'Y')
				);
			}

			echo '<script type="text/javascript">';
			echo 'BX.ready(function(){';
			echo 'BX.CrmSidebarUserSelector.create(',
				'"', $userSelectorName, '", ',
				'BX("', CUtil::JSEscape($editButtonID), '"), ',
				'BX("', CUtil::JSEscape($containerID), '"), ',
				'"', CUtil::JSEscape($userSelectorName), '", ',
				'{',
				'"userInfoProviderId":"', CUtil::JSEscape($userInfoProviderID), '"',
				', "editorId":"', CUtil::JSEscape($instantEditorID),'"',
				', "fieldId":"', CUtil::JSEscape($fieldID), '"',
				', "enableLazyLoad":', $enableLazyLoad ? 'true' : 'false',
				', "serviceUrl":"', CUtil::JSEscape($serviceUrl), '"',
				'}',
				');';
			echo '});';
			echo '</script>';
		}

		echo '</div>';

	}
	public static function RenderInstantEditorField($arParams)
	{
		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
		$type = isset($arParams['TYPE']) ? $arParams['TYPE'] : '';

		if($type === 'TEXT')
		{
			$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';
			$suffixHtml = isset($arParams['SUFFIX_HTML']) ? $arParams['SUFFIX_HTML'] : '';
			if($suffixHtml === '')
			{
				$suffix = isset($arParams['SUFFIX']) ? $arParams['SUFFIX'] : '';
				if($suffix !== '')
				{
					$suffixHtml = htmlspecialcharsbx($suffix);
				}
			}
			$inputWidth = isset($arParams['INPUT_WIDTH']) ? intval($arParams['INPUT_WIDTH']) : 0;

			echo '<span class="crm-instant-editor-fld crm-instant-editor-fld-input">',
				'<span class="crm-instant-editor-fld-text">', htmlspecialcharsbx($value), '</span>';

			echo '<input class="crm-instant-editor-data-input" type="text" value="', htmlspecialcharsbx($value),
				'" style="display:none;', ($inputWidth > 0 ? "width:{$inputWidth}px;" : ''), '" />',
				'<input class="crm-instant-editor-data-name" type="hidden" value="', htmlspecialcharsbx($fieldID), '" />';

			if($suffixHtml !== '')
			{
				echo '<span class="crm-instant-editor-fld-suffix">', $suffixHtml, '</span>';
			}

			echo '</span><span class="crm-instant-editor-fld-btn crm-instant-editor-fld-btn-input"></span>';
		}
		elseif($type === 'LHE')
		{
			$editorID = isset($arParams['EDITOR_ID']) ? $arParams['EDITOR_ID'] : '';
			if($editorID ==='')
			{
				$editorID = uniqid('LHE_');
			}

			$editorJsName = isset($arParams['EDITOR_JS_NAME']) ? $arParams['EDITOR_JS_NAME'] : '';
			if($editorJsName ==='')
			{
				$editorJsName = $editorID;
			}


			$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';

			/*if($value === '<br />')
			{
				$value = '';
			}*/

			echo '<span class="crm-instant-editor-fld-text">';
			echo $value;
			echo '</span>';
			echo '<div class="crm-instant-editor-fld-btn crm-instant-editor-fld-btn-lhe"></div>';
			echo '<input class="crm-instant-editor-data-name" type="hidden" value="', htmlspecialcharsbx($fieldID), '" />';
			echo '<input class="crm-instant-editor-data-value" type="hidden" value="', htmlspecialcharsbx($value), '" />';

			$wrapperID = isset($arParams['WRAPPER_ID']) ? $arParams['WRAPPER_ID'] : '';
			if($wrapperID ==='')
			{
				$wrapperID = $editorID.'_WRAPPER';
			}

			$toolbarConfig = is_array($arParams['TOOLBAR_CONFIG']) ? $arParams['TOOLBAR_CONFIG'] : '';
			if ($toolbarConfig === '')
			{
				$toolbarConfig = array(
					'Bold', 'Italic', 'Underline', 'Strike',
					'BackColor', 'ForeColor',
					'CreateLink', 'DeleteLink',
					'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
				);
			}

			echo '<input class="crm-instant-editor-lhe-data" type="hidden" value="',
			htmlspecialcharsbx('{ "id":"'.CUtil::JSEscape($editorID).'", "wrapperId":"'.CUtil::JSEscape($wrapperID).'", "jsName":"'.CUtil::JSEscape($editorJsName).'" }'),
			'" />';

			echo '<div id="', htmlspecialcharsbx($wrapperID),'" style="display:none;">';

			CModule::IncludeModule('fileman');
			$editor = new CLightHTMLEditor;
			$editor->Show(
				array(
					'id' => $editorID,
					'width' => '600',
					'height' => '200',
					'bUseFileDialogs' => false,
					'bFloatingToolbar' => false,
					'bArisingToolbar' => false,
					'bResizable' => false,
					'jsObjName' => $editorJsName,
					'bInitByJS' => false, // TODO: Lazy initialization
					'bSaveOnBlur' => true,
					'bHandleOnPaste'=> false,
					'toolbarConfig' => $toolbarConfig
				)
			);
			echo '</div>';
		}
	}
	public static function RenderSelector($arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';
		//Items must be html encoded
		$items = isset($arParams['ITEMS']) ? $arParams['ITEMS'] : array();
		$encodeItems = isset($arParams['ENCODE_ITEMS']) ? (bool)$arParams['ENCODE_ITEMS'] : true;
		$resultItems = array();
		foreach($items as $id => $caption)
		{
			$resultItems[] = array(
				'id' => $id,
				'caption' => !$encodeItems ? $caption : htmlspecialcharsbx($caption)
			);
		}

		$text =  $value !== '' && isset($items[$value]) ? $items[$value] : '';

		if($text === '')
		{
			$text = isset($arParams['UNDEFINED']) ? htmlspecialcharsbx($arParams['UNDEFINED']) : '';
		}

		$editable = isset($arParams['EDITABLE']) ? $arParams['EDITABLE'] : false;
		if($editable)
		{
			$selectorName = isset($arParams['SELECTOR_ID']) ? $arParams['SELECTOR_ID'] : 'selector';
			$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
			//$containerID = isset($arParams['CONTAINER_ID']) ? $arParams['CONTAINER_ID'] : 'sidebar';

			$containerClassName = isset($arParams['CONTAINER_CLASS']) ? $arParams['CONTAINER_CLASS'] : '';
			echo '<span',
				($containerClassName !== '' ? ' class="'.htmlspecialcharsbx($containerClassName).'"' : ''),
				'>';

			$uniqueID = uniqid();

			$itemID = "{$selectorName}_{$uniqueID}";
			$textClassName = isset($arParams['TEXT_CLASS']) ? $arParams['TEXT_CLASS'] : '';
			echo '<span id="', htmlspecialcharsbx($itemID), '"';
			if($textClassName !== '')
			{
				echo ' class="', htmlspecialcharsbx($textClassName), '"';
			}

			echo '>', $text, '</span>';

			$buttonID = '';
			$arrowClassName = isset($arParams['ARROW_CLASS']) ? $arParams['ARROW_CLASS'] : '';
			if($arrowClassName !== '')
			{
				$buttonID = "{$selectorName}_btn_{$uniqueID}";
				echo '<span id="', htmlspecialcharsbx($buttonID),'" class="', htmlspecialcharsbx($arrowClassName), '"></span>';
			}

			echo '<script type="text/javascript">';
			echo 'BX.ready(function(){',
				'BX.CmrSidebarFieldSelector.create(',
				'"', CUtil::JSEscape($selectorName), '",',
				'"', CUtil::JSEscape($fieldID), '",',
				'BX("', CUtil::JSEscape($itemID) ,'"),',
				'{
					"options": ', CUtil::PhpToJSObject($resultItems), ',
					"buttonId":', CUtil::JSEscape($buttonID) ,'
				});});';
			echo '</script>';

			echo '</span>';
		}
		else
		{
			echo htmlspecialcharsbx($text);
		}
	}
	public static function PrepareHtml(&$arData)
	{
		if(!is_array($arData))
		{
			return '';
		}

		if(isset($arData['HTML']))
		{
			return $arData['HTML'];
		}
		elseif(isset($arData['TEXT']))
		{
			return htmlspecialcharsbx($arData['TEXT']);
		}

		return '';
	}
	public static function RenderUserCustomSearch($arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$ID = isset($arParams['ID']) ? strval($arParams['ID']) : '';
		$searchInputID = isset($arParams['SEARCH_INPUT_ID']) ? strval($arParams['SEARCH_INPUT_ID']) : '';
		$searchInputName = isset($arParams['SEARCH_INPUT_NAME']) ? strval($arParams['SEARCH_INPUT_NAME']) : '';
		if($searchInputName === '')
		{
			$searchInputName = $searchInputID;
		}

		$dataInputID = isset($arParams['DATA_INPUT_ID']) ? strval($arParams['DATA_INPUT_ID']) : '';
		$dataInputName = isset($arParams['DATA_INPUT_NAME']) ? strval($arParams['DATA_INPUT_NAME']) : '';
		if($dataInputName === '')
		{
			$dataInputName = $dataInputID;
		}

		$componentName = isset($arParams['COMPONENT_NAME']) ? strval($arParams['COMPONENT_NAME']) : '';

		$siteID = isset($arParams['SITE_ID']) ? strval($arParams['SITE_ID']) : '';
		if($siteID === '')
		{
			$siteID = SITE_ID;
		}

		$nameFormat = isset($arParams['NAME_FORMAT']) ? strval($arParams['NAME_FORMAT']) : '';
		if($nameFormat === '')
		{
			$nameFormat = CSite::GetNameFormat(false);
		}

		$user = isset($arParams['USER']) && is_array($arParams['USER']) ? $arParams['USER'] : array();
		$zIndex = isset($arParams['ZINDEX']) ? intval($arParams['ZINDEX']) : 0;

		/*
		//new style with user clear support
		echo '<span class="webform-field webform-field-textbox webform-field-textbox-empty webform-field-textbox-clearable">',
			'<span class="webform-field-textbox-inner">',
			'<input type="text" class="webform-field-textbox" id="', htmlspecialcharsbx($searchInputID) ,'" name="', htmlspecialcharsbx($searchInputName), '">',
			'<a class="webform-field-textbox-clear" href="#"></a>',
			'</span></span>',
			'<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputName), '" value="">';
		*/
		$searchInputHint = isset($arParams['SEARCH_INPUT_HINT']) ? strval($arParams['SEARCH_INPUT_HINT']) : '';
		if($searchInputHint !== '')
		{
			$searchInputHint = 'BX.hint(this, \''.CUtil::JSEscape($searchInputHint).'\');';
		}
		echo '<input type="text" id="', htmlspecialcharsbx($searchInputID) ,'" name="', htmlspecialcharsbx($searchInputName), '" style="width:200px;" autocomplete="off"',
		$searchInputHint !== '' ? ' onmouseover="'.$searchInputHint.'">' : '>',
		'<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputName), '" value="">';

		$delay = isset($arParams['DELAY']) ? intval($arParams['DELAY']) : 0;

		echo '<script type="text/javascript">',
		'BX.ready(function(){',
		'BX.CrmUserSearchPopup.deletePopup("', $ID, '");',
		'BX.CrmUserSearchPopup.create("', $ID, '", { searchInput: BX("', CUtil::JSEscape($searchInputID), '"), dataInput: BX("', CUtil::JSEscape($dataInputID),'"), componentName: "', CUtil::JSEscape($componentName),'", user: ', CUtil::PhpToJSObject(array_change_key_case($user, CASE_LOWER)) ,', zIndex: ', $zIndex,' }, ', $delay,');',
		'}); </script>';

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputID,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteID,
				'NAME_TEMPLATE' => $nameFormat
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	public static function RenderUserSearch($ID, $searchInputID, $dataInputID, $componentName, $siteID = '', $nameFormat = '', $delay = 0)
	{
		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

		$ID = strval($ID);
		$searchInputID = strval($searchInputID);
		$dataInputID = strval($dataInputID);
		$componentName = strval($componentName);

		$siteID = strval($siteID);
		if($siteID === '')
		{
			$siteID = SITE_ID;
		}

		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = CSite::GetNameFormat(false);
		}

		$delay = intval($delay);
		if($delay < 0)
		{
			$delay = 0;
		}

		echo '<input type="text" id="', htmlspecialcharsbx($searchInputID) ,'" style="width:200px;"   >',
		'<input type="hidden" id="', htmlspecialcharsbx($dataInputID),'" name="', htmlspecialcharsbx($dataInputID),'" value="">';

		echo '<script type="text/javascript">',
			'BX.ready(function(){',
			'BX.CrmUserSearchPopup.deletePopup("', $ID, '");',
			'BX.CrmUserSearchPopup.create("', $ID, '", { searchInput: BX("', CUtil::JSEscape($searchInputID), '"), dataInput: BX("', CUtil::JSEscape($dataInputID),'"), componentName: "', CUtil::JSEscape($componentName),'", user: {} }, ', $delay,');',
			'});</script>';

		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputID,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteID,
				'NAME_TEMPLATE' => $nameFormat
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
	}
	public static function RenderFiles($fileIDs, $fileUrlTemplate = '', $fileMaxWidth = 0, $fileMaxHeight = 0)
	{
		if(!is_array($fileIDs))
		{
			return 0;
		}
		$fileUrlTemplate = strval($fileUrlTemplate);
		$fileMaxWidth = intval($fileMaxWidth);
		if($fileMaxWidth <= 0)
		{
			$fileMaxWidth = 350;
		}
		$fileMaxHeight = intval($fileMaxHeight);
		if($fileMaxHeight <= 350)
		{
			$fileMaxHeight = 350;
		}

		$file = new CFile();
		$processed = 0;
		foreach($fileIDs as $fileID)
		{
			$fileInfo = $file->GetFileArray($fileID);
			if (!is_array($fileInfo))
			{
				continue;
			}

			if($processed > 0)
			{
				echo '<span class="bx-br-separator"><br/></span>';
			}

			echo '<span class="fields files">';

			$fileInfo['name'] = $fileInfo['ORIGINAL_NAME'];

			if ($file->IsImage($fileInfo['ORIGINAL_NAME'], $fileInfo['CONTENT_TYPE']))
			{
				echo $file->ShowImage($fileInfo, $fileMaxWidth, $fileMaxHeight, '', '', true, false, 0, 0, $fileUrlTemplate);
			}
			else
			{
				echo '<span class="crm-entity-file-info"><a target="_blank" class="crm-entity-file-link" href="',
					htmlspecialcharsbx(
						CComponentEngine::MakePathFromTemplate(
							$fileUrlTemplate,
							array('file_id' => $fileInfo['ID'])
						)
					), '">',
					htmlspecialcharsbx($fileInfo['ORIGINAL_NAME']).'</a><span class="crm-entity-file-size">',
					CFile::FormatSize($fileInfo['FILE_SIZE']).'</span></span>';
			}

			echo '</span>';
			$processed++;
		}

		return $processed;
	}
	public static function RenderDealStageSettings()
	{
		if(!self::$DEAL_STAGES)
		{
			self::$DEAL_STAGES = CCrmStatus::GetStatus('DEAL_STAGE');
		}

		$result = array();
		$isTresholdPassed = false;
		foreach(self::$DEAL_STAGES as &$stage)
		{
			$info = array(
				'id' => $stage['STATUS_ID'],
				'name' => $stage['NAME'],
				'sort' => intval($stage['SORT'])
			);

			if($stage['STATUS_ID'] === 'WON')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_DEAL_STAGE_MANAGER_WON_STEP_HINT');
			}
			elseif($stage['STATUS_ID'] === 'LOSE')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}
		unset($stage);

		$messages = array(
			'dialogTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_DLG_TTL'),
			//'apologyTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_APOLOGY_TTL'),
			'failureTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_DEAL_STAGE_MANAGER_SELECTOR_TTL')
		);

		return '<script type="text/javascript">'
		.'BX.ready(function(){ if(typeof(BX.CrmDealStageManager) === "undefined") return; BX.CrmDealStageManager.infos = '.CUtil::PhpToJSObject($result).'; BX.CrmDealStageManager.messages = '.CUtil::PhpToJSObject($messages).'; });'
		.'</script>';
	}
	public static function RenderLeadStatusSettings()
	{
		if(!self::$LEAD_STATUSES)
		{
			self::$LEAD_STATUSES = CCrmStatus::GetStatus('STATUS');
		}

		$result = array();
		$isTresholdPassed = false;
		foreach(self::$LEAD_STATUSES as &$status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT'])
			);

			if($status['STATUS_ID'] === 'CONVERTED')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['name'] = GetMessage('CRM_LEAD_STATUS_MANAGER_CONVERTED_STEP_NAME');
				$info['hint'] = GetMessage('CRM_LEAD_STATUS_MANAGER_CONVERTED_STEP_HINT');
				$info['isFrozen'] = true;
			}
			elseif($status['STATUS_ID'] === 'JUNK')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}
		unset($status);

		$messages = array(
			'dialogTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_DLG_TTL'),
			//'apologyTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_APOLOGY_TTL'),
			'failureTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_LEAD_STATUS_MANAGER_SELECTOR_TTL')
		);

		return '<script type="text/javascript">'
		.'BX.ready(function(){ if(typeof(BX.CrmLeadStatusManager) === "undefined") return; BX.CrmLeadStatusManager.infos = '.CUtil::PhpToJSObject($result).'; BX.CrmLeadStatusManager.messages = '.CUtil::PhpToJSObject($messages).'; });'
		.'</script>';
	}
	public static function RenderInvoiceStatusSettings()
	{
		if(!self::$INVOICE_STATUSES)
		{
			self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
		}

		$result = array();
		$isTresholdPassed = false;
		foreach(self::$INVOICE_STATUSES as &$status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT'])
			);

			if($status['STATUS_ID'] === 'P')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_INVOICE_STATUS_MANAGER_F_STEP_HINT');
				$info['hasParams'] = true;
			}
			elseif($status['STATUS_ID'] === 'D')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}
		unset($status);

		$settings = array(
			'imagePath' => '/bitrix/js/crm/images/',
			'serverTime' => time() + CTimeZone::GetOffset()
		);

		$messages = array(
			'dialogTitle' => GetMessage('CRM_INVOICE_STATUS_MANAGER_DLG_TTL'),
			'failureTitle' => GetMessage('CRM_INVOICE_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_INVOICE_STATUS_MANAGER_SELECTOR_TTL'),
			'setDate' =>  GetMessage('CRM_INVOICE_STATUS_MANAGER_SET_DATE'),
			'dateLabelText' => GetMessage('CRM_INVOICE_STATUS_MANAGER_DATE_LABEL'),
			'payVoucherNumLabelText' => GetMessage('CRM_INVOICE_STATUS_MANAGER_PAY_VOUCHER_NUM_LABEL'),
			'commentLabelText' => GetMessage('CRM_INVOICE_STATUS_MANAGER_COMMENT_LABEL'),
			'notSpecified' => GetMessage('CRM_INVOICE_STATUS_MANAGER_NOT_SPECIFIED')
		);

		return '<script type="text/javascript">'
			.'BX.ready(function(){ if(typeof(BX.CrmInvoiceStatusManager) === "undefined") return;'
			.'BX.CrmInvoiceStatusManager.infos = '.CUtil::PhpToJSObject($result).';'.PHP_EOL
			.'BX.CrmInvoiceStatusManager.messages = '.CUtil::PhpToJSObject($messages).';'.PHP_EOL
			.'BX.CrmInvoiceStatusManager.settings = '.CUtil::PhpToJSObject($settings).';'.PHP_EOL
			.'BX.CrmInvoiceStatusManager.failureDialogEventsBind();});'.PHP_EOL
			.'</script>';
	}
	public static function RenderInvoiceStatusInfo($params)
	{
		$html = '<div id="'.$params['id'].'">'.PHP_EOL;
		foreach ($params['items'] as $k => $item)
		{
			$style = '';
			if (empty($item['value'])
				|| (!$params['statusFailed'] && !$params['statusSuccess'])
				|| ($params['statusSuccess'] && $item['status'] === 'failed')
				|| ($params['statusFailed'] && $item['status'] === 'success'))
			{
				$style = ' style="display: none;"';
			}

			$html .= "\t".'<div id="INVOICE_STATUS_INFO_'.$k.'_block" class="crm-detail-info-item"'.$style.'>'.PHP_EOL;
			$html .= "\t\t".'<span class="crm-detail-info-item-name">'.htmlspecialcharsbx(GetMessage('CRM_INVOICE_FIELD_'.$k)).':</span>'.PHP_EOL;
			$html .= "\t\t".'<span id="INVOICE_STATUS_INFO_'.$k.'_value" class="crm-detail-info-item-text">'.htmlspecialcharsbx($item['value']).'</span>'.PHP_EOL;
			$html .= "\t".'</div>'.PHP_EOL;
		}
		$html .= '</div>'.PHP_EOL;

		return $html;
	}
	public static function RenderDealPaidSumField($params)
	{
		if ($params['id'] != '')
			$html = '<div id="'.$params['id'].'">'.PHP_EOL;
		else
			$html = '<div>'.PHP_EOL;
		$html .= "\t".'<div class="crm-detail-info-item">'.PHP_EOL;
		$html .= "\t\t".'<span class="crm-detail-info-item-name">'.htmlspecialcharsbx(GetMessage('CRM_DEAL_SUM_PAID_FIELD')).':</span>'.PHP_EOL;
		$html .= "\t\t".'<span class="crm-detail-info-item-text crm-sum-paid">'.htmlspecialcharsbx($params['value']).'</span>'.PHP_EOL;
		$html .= "\t".'</div>'.PHP_EOL;
		$html .= '</div>'.PHP_EOL;

		return $html;
	}
	public static function RenderDealStageControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		if(!self::$DEAL_STAGES)
		{
			self::$DEAL_STAGES = CCrmStatus::GetStatus('DEAL_STAGE');
		}
		$arParams['INFOS'] = self::$DEAL_STAGES;
		$arParams['FINAL_ID'] = 'WON';
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Deal);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderLeadStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		if(!self::$LEAD_STATUSES)
		{
			self::$LEAD_STATUSES = CCrmStatus::GetStatus('STATUS');
		}
		$arParams['INFOS'] = self::$LEAD_STATUSES;
		$arParams['FINAL_ID'] = 'CONVERTED';
		$arParams['FINAL_URL'] = isset($arParams['LEAD_CONVERT_URL']) ? $arParams['LEAD_CONVERT_URL'] : '';
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Lead);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderProgressControl($arParams)
	{
		if(!is_array($arParams))
		{
			return '';
		}

		CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/progress_control.js');

		$entityTypeName = isset($arParams['ENTITY_TYPE_NAME']) ? $arParams['ENTITY_TYPE_NAME'] : '';
		$leadTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Lead);
		$dealTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Deal);
		$invoiceTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Invoice);
		$quoteTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Quote);

		$infos = isset($arParams['INFOS']) ? $arParams['INFOS'] : null;
		if(!is_array($infos) || empty($infos))
		{
			if($entityTypeName === $leadTypeName)
			{
				if(!self::$LEAD_STATUSES)
				{
					self::$LEAD_STATUSES = CCrmStatus::GetStatus('STATUS');
				}
				$infos = self::$LEAD_STATUSES;
			}
			elseif($entityTypeName === $dealTypeName)
			{
				if(!self::$DEAL_STAGES)
				{
					self::$DEAL_STAGES = CCrmStatus::GetStatus('DEAL_STAGE');
				}
				$infos = self::$DEAL_STAGES;
			}
			elseif($entityTypeName === $quoteTypeName)
			{
				if(!self::$QUOTE_STATUSES)
				{
					self::$QUOTE_STATUSES = CCrmStatus::GetStatus('QUOTE_STATUS');
				}
				$infos = self::$QUOTE_STATUSES;
			}
			elseif($entityTypeName === $invoiceTypeName)
			{
				if(!self::$INVOICE_STATUSES)
				{
					self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
				}
				$infos = self::$INVOICE_STATUSES;
			}
		}

		if(!is_array($infos) || empty($infos))
		{
			return '';
		}

		$registerSettings = isset($arParams['REGISTER_SETTINGS']) && is_bool($arParams['REGISTER_SETTINGS'])
			? $arParams['REGISTER_SETTINGS'] : false;

		$registrationScript = '';
		if($registerSettings)
		{
			if($entityTypeName === $leadTypeName)
			{
				$registrationScript = self::RenderLeadStatusSettings();
			}
			elseif($entityTypeName === $dealTypeName)
			{
				$registrationScript = self::RenderDealStageSettings();
			}
			elseif($entityTypeName === $quoteTypeName)
			{
				$registrationScript = self::RenderQuoteStatusSettings();
			}
			elseif($entityTypeName === $invoiceTypeName)
			{
				$registrationScript = self::RenderInvoiceStatusSettings();
			}
		}

		$finalID = isset($arParams['FINAL_ID']) ? $arParams['FINAL_ID'] : '';
		if($finalID === '')
		{
			if($entityTypeName === $leadTypeName)
			{
				$finalID = 'CONVERTED';
			}
			elseif($entityTypeName === $dealTypeName)
			{
				$finalID = 'WON';
			}
			elseif($entityTypeName === $quoteTypeName)
			{
				$finalID = 'APPROVED';
			}
			elseif($entityTypeName === $invoiceTypeName)
			{
				$finalID = 'P';
			}
		}

		$finalUrl = isset($arParams['FINAL_URL']) ? $arParams['FINAL_URL'] : '';
		if($finalUrl === '' && $entityTypeName === $leadTypeName)
		{
			$arParams['FINAL_URL'] = isset($arParams['LEAD_CONVERT_URL']) ? $arParams['LEAD_CONVERT_URL'] : '';
		}

		$currentInfo = null;
		$currentID = isset($arParams['CURRENT_ID']) ? $arParams['CURRENT_ID'] : '';
		if($currentID !== '' && isset($infos[$currentID]))
		{
			$currentInfo = $infos[$currentID];
		}
		$currentSort = is_array($currentInfo) && isset($currentInfo['SORT']) ? intval($currentInfo['SORT']) : -1;

		$finalInfo = null;
		if($finalID !== '' && isset($infos[$finalID]))
		{
			$finalInfo = $infos[$finalID];
		}
		$finalSort = is_array($finalInfo) && isset($finalInfo['SORT']) ? intval($finalInfo['SORT']) : -1;

		$isSuccessful = $currentSort === $finalSort;
		$isFailed = $currentSort > $finalSort;

		$stepHtml = '';
		foreach($infos as &$info)
		{
			$ID = isset($info['STATUS_ID']) ? $info['STATUS_ID'] : '';

			$sort = isset($info['SORT']) ? intval($info['SORT']) : 0;
			if($sort > $finalSort)
			{
				break;
			}

			$stepHtml .= '<td class="crm-list-stage-bar-part';
			if($sort <= $currentSort)
			{
				$stepHtml .= ' crm-list-stage-passed';
			}
			$stepHtml .= '"><div class="crm-list-stage-bar-block  crm-stage-'.htmlspecialcharsbx(strtolower($ID)).'"><div class="crm-list-stage-bar-btn"></div></div></td>';
		}
		unset($info);

		$wrapperClass = '';
		if($isSuccessful)
		{
			$wrapperClass = ' crm-list-stage-end-good';
		}
		elseif($isFailed)
		{
			$wrapperClass =' crm-list-stage-end-bad';
		}


		$prefix = isset($arParams['PREFIX']) ? $arParams['PREFIX'] : '';
		$entityID = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;
		$controlID = isset($arParams['CONTROL_ID']) ? $arParams['CONTROL_ID'] : '';

		if($controlID === '')
		{
			$controlID = $entityTypeName !== '' && $entityID > 0 ? "{$prefix}{$entityTypeName}_{$entityID}" : uniqid($prefix);
		}

		$isReadOnly = isset($arParams['READ_ONLY']) ? (bool)$arParams['READ_ONLY'] : false;
		$legendHtml = (!isset($arParams['DISPLAY_LEGEND']) || $arParams['DISPLAY_LEGEND'])
			? '<div class="crm-list-stage-bar-title">'.htmlspecialcharsbx(isset($infos[$currentID]) && isset($infos[$currentID]['NAME']) ? $infos[$currentID]['NAME'] : $currentID).'</div>' : '';

		return $registrationScript.'<div class="crm-list-stage-bar'.$wrapperClass.'" id="'.htmlspecialcharsbx($controlID).'"><table class="crm-list-stage-bar-table"><tr>'
			.$stepHtml
			.'</tr></table>'
			.'<script type="text/javascript">BX.ready(function(){ BX.CrmProgressControl.create("'
			.CUtil::JSEscape($controlID).'"'
			.', BX.CrmParamBag.create({"containerId": "'.CUtil::JSEscape($controlID).'"'
			.', "entityType":"'.CUtil::JSEscape($entityTypeName).'"'
			.', "entityId":"'.CUtil::JSEscape($entityID).'"'
			.', "serviceUrl":"'.(isset($arParams['SERVICE_URL']) ? CUtil::JSEscape($arParams['SERVICE_URL']) : '').'"'
			.', "finalUrl":"'.(isset($arParams['FINAL_URL']) ? CUtil::JSEscape($arParams['FINAL_URL']) : '').'"'
			.', "currentStepId":"'.CUtil::JSEscape($currentID).'"'
			.', "readOnly":'.($isReadOnly ? 'true' : 'false')
			.' }));});</script>'
			.'</div>'.$legendHtml;
	}
	public static function RenderQuoteStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		if(!self::$QUOTE_STATUSES)
		{
			self::$QUOTE_STATUSES = CCrmStatus::GetStatus('QUOTE_STATUS');
		}
		$arParams['INFOS'] = self::$QUOTE_STATUSES;
		$arParams['FINAL_ID'] = 'APPROVED';
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Quote);
		return self::RenderProgressControl($arParams);
	}
	public static function RenderInvoiceStatusControl($arParams)
	{
		if(!is_array($arParams))
		{
			$arParams = array();
		}

		if(!self::$INVOICE_STATUSES)
		{
			self::$INVOICE_STATUSES = CCrmStatus::GetStatus('INVOICE_STATUS');
		}
		$arParams['INFOS'] = self::$INVOICE_STATUSES;
		$arParams['FINAL_ID'] = 'P';
		$arParams['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName(CCrmOwnerType::Invoice);
		return self::RenderProgressControl($arParams);
	}
	public static function PrepareFormTabFields($tabID, &$arSrcFields, &$arFormOptions, $ignoredFieldIDs = array(), $arFieldOptions = array())
	{
		$arTabFields = isset($arSrcFields[$tabID]) ? $arSrcFields[$tabID] : array();
		$arResult = array();

		$enableFormSettings = !(isset($arFormOptions['settings_disabled']) && $arFormOptions['settings_disabled'] === 'Y');
		if($enableFormSettings && isset($arFormOptions['tabs']) && !empty($arFormOptions['tabs']))
		{
			$arFields = array();
			foreach($arSrcFields as &$tabFields)
			{
				foreach($tabFields as &$field)
				{
					if($field['type'] === 'section')
					{
						continue;
					}

					$fieldID = isset($field['id']) ? $field['id'] : '';
					if($fieldID !== '')
					{
						$arFields[$fieldID] = $field;
					}
				}
				unset($tabFields);
			}
			unset($field);

			if(isset($arFormOptions['tabs']) && is_array($arFormOptions['tabs']))
			{
				foreach($arFormOptions['tabs'] as &$formTab)
				{
					if($formTab['id'] !== $tabID
						|| !isset($formTab['fields'])
						|| !is_array($formTab['fields']))
					{
						continue;
					}

					foreach($formTab['fields'] as &$formField)
					{
						if($formField['type'] === 'section')
						{
							continue;
						}

						$fieldID = isset($formField['id']) ? $formField['id'] : '';

						if(in_array($fieldID, $ignoredFieldIDs, true))
						{
							continue;
						}

						$field = isset($arFields[$fieldID]) ? $arFields[$fieldID] : null;
						if(!$field)
						{
							continue;
						}

						$item = array(
							'ID' => $fieldID,
							'TITLE' => isset($field['name']) ? $field['name'] : $fieldID,
							'VALUE' => isset($field['value']) ? $field['value'] : ''
						);

						if(isset($arFieldOptions[$fieldID]))
						{
							foreach($arFieldOptions[$fieldID] as $k => $v)
							{
								$item[$k] = $v;
							}
						}

						$arResult[] = &$item;
						unset($item);
					}
					unset($formField);
				}
				unset($formTab);
			}
		}
		else
		{
			foreach($arTabFields as &$field)
			{
				if($field['type'] === 'section')
				{
					continue;
				}

				$fieldID = isset($field['id']) ? $field['id'] : '';

				if(in_array($fieldID, $ignoredFieldIDs, true))
				{
					continue;
				}

				$item = array(
					'ID' => $fieldID,
					'TITLE' => isset($field['name']) ? $field['name'] : $fieldID,
					'VALUE' => isset($field['value']) ? $field['value'] : ''
				);

				if(isset($arFieldOptions[$fieldID]))
				{
					foreach($arFieldOptions[$fieldID] as $k => $v)
					{
						$item[$k] = $v;
					}
				}

				$arResult[] = &$item;
				unset($item);
			}
			unset($field);
		}
		return $arResult;
	}
	public static function getGridOptionalColumns($gridID)
	{
		$aOptions = CUserOptions::GetOption('main.interface.grid', $gridID, array());
		if(!is_array($aOptions['views']))
			$aOptions['views'] = array();
		if(!array_key_exists('default', $aOptions['views']))
			$aOptions['views']['default'] = array('columns'=>'');
		if($aOptions['current_view'] == '' || !array_key_exists($aOptions['current_view'], $aOptions['views']))
			$aOptions['current_view'] = 'default';
		$aCurView = $aOptions['views'][$aOptions['current_view']];
		$aColsTmp = explode(',', $aCurView['columns']);
		$aCols = array();
		foreach($aColsTmp as $col)
			if(trim($col)<>'')
				$aCols[] = trim($col);

		return $aCols;
	}
	public static function prepareSelectItemsForJS($items)
	{
		$result = array();
		if (is_array($items))
			foreach ($items as $id => $name)
				$result[] = array('id' => $id, 'title' => $name);

		return $result;
	}
	public static function RenderQuoteStatusSettings()
	{
		if(!self::$QUOTE_STATUSES)
		{
			self::$QUOTE_STATUSES = CCrmStatus::GetStatus('QUOTE_STATUS');
		}

		$result = array();
		$isTresholdPassed = false;
		foreach(self::$QUOTE_STATUSES as &$status)
		{
			$info = array(
				'id' => $status['STATUS_ID'],
				'name' => $status['NAME'],
				'sort' => intval($status['SORT'])
			);

			if($status['STATUS_ID'] === 'APPROVED')
			{
				$isTresholdPassed = true;
				$info['semantics'] = 'success';
				$info['hint'] = GetMessage('CRM_QUOTE_STATUS_MANAGER_APPROVED_STEP_HINT');
			}
			elseif($status['STATUS_ID'] === 'DECLAINED')
			{
				$info['semantics'] = 'failure';
			}
			elseif(!$isTresholdPassed)
			{
				$info['semantics'] = 'process';
			}
			else
			{
				$info['semantics'] = 'apology';
			}
			$result[] = $info;
		}
		unset($status);

		$messages = array(
			'dialogTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_DLG_TTL'),
			'failureTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_FAILURE_TTL'),
			'selectorTitle' => GetMessage('CRM_QUOTE_STATUS_MANAGER_SELECTOR_TTL')
		);

		return '<script type="text/javascript">'
		.'BX.ready(function(){ if(typeof(BX.CrmQuoteStatusManager) === "undefined") return;  BX.CrmQuoteStatusManager.infos = '.CUtil::PhpToJSObject($result).'; BX.CrmQuoteStatusManager.messages = '.CUtil::PhpToJSObject($messages).'; });'
		.'</script>';
	}
	public static function getCurrencyText($currencyID)
	{
		$currencyText = '?';
		
		$str = CCrmCurrency::MoneyToString(101.01, $currencyID);
		$position1 = strpos($str, '101');
		if ($position1 !== false)
		{
			$position2 = strpos($str, '01', $position1 + 3);
			if ($position2)
			{
				$str = trim(substr($str, 0, $position1).substr($str, $position2 + 2));
				$currencyText = $str;
			}
		}

		return $currencyText;
	}
}
