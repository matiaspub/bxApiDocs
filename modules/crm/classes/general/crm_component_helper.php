<?php
class CCrmComponentHelper
{
	private static $USER_NAME_FORMATS = null;
	public static function TrimZeroTime($str)
	{
		$str = trim($str);
		if(substr($str, -9) == ' 00:00:00')
		{
			return substr($str, 0, -9);
		}
		elseif(substr($str, -3) == ':00')
		{
			return substr($str, 0, -3);
		}
		return $str;
	}

	public static function RemoveSeconds($str, $options = null)
	{
		$ary = array();
		if(preg_match('/(\d{1,2}):(\d{1,2}):(\d{1,2})/', $str, $ary, PREG_OFFSET_CAPTURE) !== 1)
		{
			return $str;
		}

		$time = "{$ary[1][0]}:{$ary[2][0]}";
		//Treat tail as part of time (AM/PM)
		$tailPos = $ary[3][1] + 2;
		if($tailPos < strlen($str))
		{
			$time .= substr($str, $tailPos);
		}
		$timeFormat = is_array($options) && isset($options['TIME_FORMAT']) ? strval($options['TIME_FORMAT']) : '';
		return substr($str, 0, $ary[0][1]).($timeFormat === '' ? $time : str_replace('#TIME#', $time, $timeFormat));
	}

	public static function TrimDateTimeString($str, $options = null)
	{
		return self::RemoveSeconds($str, $options);
	}

	public static function SynchronizeFormSettings($formID, $userFieldEntityID, $options = array())
	{
		$formID = strval($formID);
		$userFieldEntityID = strval($userFieldEntityID);
		$options = is_array($options) ? $options : array();

		if($formID === '')
		{
			return;
		}

		$arOptions = CUserOptions::GetOption('main.interface.form', $formID, array());
		if(isset($arOptions['settings_disabled'])
			&& $arOptions['settings_disabled'] === 'Y'
			|| !(isset($arOptions['tabs']) && is_array($arOptions['tabs'])))
		{
			return;
		}

		$changed = false;

		$normalizeTabs = isset($options['NORMALIZE_TABS']) ? $options['NORMALIZE_TABS'] : array();
		if(!empty($normalizeTabs))
		{
			if(COption::GetOptionString('crm', strtolower($formID).'_normalized', 'N') !== 'Y')
			{
				foreach($arOptions['tabs'] as &$tab)
				{
					if(!in_array($tab['id'], $normalizeTabs, true))
					{
						continue;
					}

					$tabName = $tab['name'];
					// remove counter from tab name
					$tabName = preg_replace('/\s\(\d+\)$/', '', $tabName);
					if($tabName !== $tab['name'])
					{
						$tab['name'] = $tabName;
						if(!$changed)
						{
							$changed = true;
						}
					}
				}
				unset($tab);
				reset($arOptions['tabs']);
				if($changed)
				{
					CUserOptions::SetOption('main.interface.form', $formID, $arOptions);
					$changed = false;
				}
				COption::SetOptionString('crm', strtolower($formID).'_normalized', 'Y');
			}
		}

		if($userFieldEntityID === '')
		{
			return;
		}

		$bRemoveFields = (isset($options['REMOVE_FIELDS']) && is_array($options['REMOVE_FIELDS']));
		$bAddFields = (isset($options['ADD_FIELDS']) && is_array($options['ADD_FIELDS']));
		if ($bRemoveFields)
		{
			foreach($arOptions['tabs'] as &$tab)
			{
				if (is_array($tab) && isset($tab['id']) && isset($tab['fields']) && is_array($tab['fields'])
					&& isset($options['REMOVE_FIELDS'][$tab['id']]) && is_array($options['REMOVE_FIELDS'][$tab['id']]))
				{
					foreach($tab['fields'] as $key => $item)
					{
						if (is_array($item) && isset($item['id']))
						{
							if (in_array($item['id'], $options['REMOVE_FIELDS'][$tab['id']]))
							{
								unset($tab['fields'][$key]);
								$changed = true;
							}
						}
					}
				}
			}
		}
		if ($bAddFields)
		{
			foreach($arOptions['tabs'] as &$tab)
			{
				if (is_array($tab) && isset($tab['id']) && isset($tab['fields']) && is_array($tab['fields'])
					&& isset($options['ADD_FIELDS'][$tab['id']]) && is_array($options['ADD_FIELDS'][$tab['id']]))
				{
					$addFieldsNames = array();
					foreach ($options['ADD_FIELDS'][$tab['id']] as $key => $item)
					{
						if (is_array($item) && isset($item['id']))
							$addFieldsNames[$item['id']] = $key;
					}
					unset($key, $item);
					$addIndex = array();
					$removeIndex = array();
					foreach($tab['fields'] as $key => $item)
					{
						if (is_array($item) && isset($item['id']))
						{
							if (isset($options['ADD_FIELDS'][$tab['id']][$item['id']]))
								$addIndex[$item['id']] = $key;
							if ($addFieldsNames[$item['id']])
								$removeIndex[] = $addFieldsNames[$item['id']];
						}
					}
					unset($key, $item);
					foreach ($removeIndex as $key)
						unset($addIndex[$key]);
					unset($key);
					if (count($addIndex) > 0)
					{
						foreach ($addIndex as $key => $index)
						{
							array_splice(
								$tab['fields'], $index + 1, 0,
								array($index + 1 => $options['ADD_FIELDS'][$tab['id']][$key])
							);
							$changed = true;
						}
						unset($key, $index);
					}
				}
			}
		}

		global $USER_FIELD_MANAGER;
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields($userFieldEntityID);
		if(is_array($arUserFields) && count($arUserFields) > 0)
		{
			foreach($arOptions['tabs'] as &$tab)
			{
				if(!(isset($tab['fields']) && is_array($tab['fields'])))
				{
					continue;
				}

				$arJunkKeys = array();

				foreach($tab['fields'] as $itemKey => $item)
				{
					$itemID = isset($item['id']) ? strtoupper($item['id']) : '';
					if(strpos($itemID, 'UF_CRM_') === 0 && !isset($arUserFields[$itemID]))
					{
						$arJunkKeys[] = $itemKey;
					}
				}

				if(count($arJunkKeys) > 0)
				{
					if(!$changed)
					{
						$changed = true;
					}

					foreach($arJunkKeys as $key)
					{
						unset($tab['fields'][$key]);
					}
				}
			}
			unset($tab);
		}

		if($changed)
		{
			CUserOptions::SetOption('main.interface.form', $formID, $arOptions);
		}
	}

	public static function PrepareEntityFields($arValues, $arFields)
	{
		$result = array();
		foreach($arValues as $k => &$v)
		{
			if(!isset($arFields[$k]))
			{
				$result[$k] = $v;
			}
			else
			{
				$type = isset($arFields[$k]['TYPE']) ? strtolower($arFields[$k]['TYPE']) : '';
				if($type !== 'string' )
				{
					$result["~{$k}"] = $result[$k] = $v;
				}
				else
				{
					if(is_string($v))
					{
						$result["~{$k}"] = $v;
						$result[$k] = htmlspecialcharsbx($v);
					}
					else
					{
						$result["~{$k}"] = $result[$k] = $v;
					}
				}
			}
		}
		unset($v);
		return $result;
	}

	public static function PrepareExportFieldsList(&$arSelect, $arFieldMap, $processMultiFields = true)
	{
		if($processMultiFields)
		{
			$arMultiFieldTypes = CCrmFieldMulti::GetEntityTypes();
			foreach($arMultiFieldTypes as $typeID => &$arType)
			{
				if(isset($arFieldMap[$typeID]))
				{
					continue;
				}

				$arFieldMap[$typeID] = array();
				$arValueTypes = array_keys($arType);
				foreach($arValueTypes as $valueType)
				{
					$arFieldMap[$typeID][] = "{$typeID}_{$valueType}";
				}
			}
			unset($arType);
		}

		foreach($arFieldMap as $fieldID => &$arFieldReplace)
		{
			$offset = array_search($fieldID, $arSelect, true);
			if($offset === false)
			{
				continue;
			}

			array_splice(
				$arSelect,
				$offset,
				1,
				array_diff($arFieldReplace, $arSelect)
			);
		}
		unset($arFieldReplace);
	}

	public static function FindFieldKey($fieldID, &$arFields)
	{
		$fieldID = strval($fieldID);
		if(!is_array($arFields) || empty($arFields))
		{
			return false;
		}

		$result = false;
		foreach($arFields as $k => &$v)
		{
			$id = isset($v['id']) ? $v['id'] : '';
			if($id === $fieldID)
			{
				$result = $k;
				break;
			}
		}
		unset($v);
		return $result;
	}

	public static function FindField($fieldID, &$arFields)
	{
		$key = self::FindFieldKey($fieldID, $arFields);
		return $key !== false ? $arFields[$key] : null;
	}

	public static function RegisterScriptLink($url)
	{
		$url = trim(strtolower(strval($url)));
		if($url === '')
		{
			return false;
		}
		$GLOBALS['APPLICATION']->AddHeadScript($url);
		return true;
	}

	public static function EnsureFormTabPresent(&$tabs, $tab, $index = -1)
	{
		if(!is_array($tabs) || empty($tabs) || !is_array($tab))
		{
			return false;
		}

		$tabID = isset($tab['id']) ? $tab['id'] : '';
		if($tabID === '')
		{
			return false;
		}

		$isFound = false;
		foreach($tabs as &$curTab)
		{
			$curTabID = isset($curTab['id']) ? $curTab['id'] : '';
			if($curTabID === $tabID)
			{
				$isFound = true;
				break;
			}
		}
		unset($curTab);

		if($isFound)
		{
			return false;
		}

		foreach($tab['fields'] as &$field)
		{
			if(isset($field['value']))
			{
				unset($field['value']);
			}
		}
		unset($field);

		$index = intval($index);
		if($index < 0 || $index >= count($tabs))
		{
			$tabs[] = $tab;
		}
		else
		{
			array_splice($tabs, $index, 0, array($tab));
		}
		return true;
	}
}

class CCrmInstantEditorHelper
{
	private static $TEMPLATES = array(
		'_LINK_' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-#FIELD_TYPE#">
		<a class="crm-fld-text" href="#VIEW_VALUE#" target="_blank" >#VALUE#</a>
		<span class="crm-fld-value">
			<input class="crm-fld-element-input" type="text" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-#FIELD_TYPE#"></span>
</span>',
		'INPUT' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-input">
		<span class="crm-fld-text">#VALUE#</span>
		<span class="crm-fld-value">
			<input class="crm-fld-element-input" type="text" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-input"></span>
</span>',
		'SELECT' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-select">
		<span class="crm-fld-text">#TEXT#</span>
		<span class="crm-fld-value">
			<select class="crm-fld-element-select#CLASS#">#OPTIONS_HTML#</select>
			<input class="crm-fld-element-value" type="hidden" value="#VALUE#"/>
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-select"></span>
</span>',
		'TEXT_AREA' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-textarea">
		<span class="crm-fld-text">#VALUE#</span>
		<span class="crm-fld-value">
			<textarea class="crm-fld-element-textarea" rows="25" cols="50" style="display: none;">#VALUE#</textarea>
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-textarea"></span>
</span>',
		'FULL_NAME' => '<span class="crm-fld-block crm-fld-block-multi-input">
	<span class="crm-fld-multi-input">
		<span class="crm-fld-multi-input-text">#VALUE_1#</span>
		<input class="crm-fld-element-input" type="text" value="#VALUE_1#" style="display: none;"/>
		<input class="crm-fld-element-name" type="hidden" value="#NAME_1#"/>
	</span>
	<span class="crm-fld-multi-input">
		<span class="crm-fld-multi-input-text">#VALUE_2#</span>
		<input class="crm-fld-element-input" type="text" value="#VALUE_2#" style="display: none;"/>
		<input class="crm-fld-element-name" type="hidden" value="#NAME_2#"/>
	</span>
	<span class="crm-fld-multi-input">
		<span class="crm-fld-multi-input-text">#VALUE_3#</span>
		<input class="crm-fld-element-input" type="text" value="#VALUE_3#" style="display: none;"/>
		<input class="crm-fld-element-name" type="hidden" value="#NAME_3#"/>
	</span>
	<span class="crm-fld-icon crm-fld-icon-multiple-input"></span>
</span>',
		'LHE' => '<span class="crm-fld-block">
	<span class="crm-fld crm-fld-lhe">
		<span class="crm-fld-text crm-fld-text-lhe">#TEXT#</span>
		<span class="crm-fld-value">
			<input class="crm-fld-element-value" type="hidden" value="#VALUE#" />
			<input class="crm-fld-element-name" type="hidden" value="#NAME#"/>
			<input class="crm-fld-element-lhe-data" type="hidden" value="#SETTINGS#"/>
		</span>
	</span>
	<span class="crm-fld-icon crm-fld-icon-lhe"></span>
</span>
<div id="#WRAPPER_ID#" style="display:none;" class="crm-fld-lhe-wrap" >#HTML#</div>'
	);

	private static $IS_FILEMAN_INCLUDED = false;
	public static function CreateMultiFields($fieldTypeID, &$fieldValues, &$formFields, $fieldParams = array(), $readOnlyMode = true)
	{
		$fieldTypeID = strtoupper(strval($fieldTypeID));
		if($fieldTypeID === '' || !is_array($fieldValues) || count($fieldValues) === 0 || !is_array($formFields))
		{
			return false;
		}

		if(!is_array($fieldParams))
		{
			$fieldParams = array();
		}

		foreach($fieldValues as $ID => &$data)
		{
			$valueType = isset($data['VALUE_TYPE']) ? strtoupper($data['VALUE_TYPE']) : '';
			$value = isset($data['VALUE']) ? $data['VALUE'] : '';

			$fieldID = "FM.{$fieldTypeID}.{$valueType}";
			$field = array(
				'id' => $fieldID,
				'name' => CCrmFieldMulti::GetEntityName($fieldTypeID, $valueType, true)
			);

			if($readOnlyMode)
			{
				$field['type'] = 'label';
				$field['value'] = CCrmFieldMulti::GetTemplate($fieldTypeID, $valueType, $value);
			}
			else
			{
				$templateType = 'INPUT';
				$editorFieldType = strtolower($fieldTypeID);

				if($fieldTypeID === 'PHONE' || $fieldTypeID === 'EMAIL' || $fieldTypeID === 'WEB')
				{
					$templateType = '_LINK_';

					if($fieldTypeID === 'WEB')
					{
						if($valueType !== 'WORK' && $valueType !== 'HOME' && $valueType !== 'OTHER')
						{
							$editorFieldType .= '-'.strtolower($valueType);
						}
					}
				}
				elseif($fieldTypeID === 'IM')
				{
					$templateType = $valueType === 'SKYPE' || $valueType === 'ICQ' || $valueType === 'MSN' ? '_LINK_' : 'INPUT';
					$editorFieldType .= '-'.strtolower($valueType);
				}

				$template = isset(self::$TEMPLATES[$templateType]) ? self::$TEMPLATES[$templateType] : '';

				if($template === '')
				{
					$field['type'] = 'label';
					$field['value'] = CCrmFieldMulti::GetTemplate($fieldTypeID, $valueType, $value);
				}
				else
				{
					$viewValue = $value;
					if($fieldTypeID === 'PHONE')
					{
						$viewValue = CCrmCallToUrl::Format($value);
					}
					elseif($fieldTypeID === 'EMAIL')
					{
						$viewValue = "mailto:{$value}";
					}
					elseif($fieldTypeID === 'WEB')
					{
						if($valueType === 'OTHER' || $valueType === 'WORK' || $valueType === 'HOME')
						{
							$hasProto = preg_match('/^http(?:s)?:\/\/(.+)/', $value, $urlMatches) > 0;
							if($hasProto)
							{
								$value = $urlMatches[1];
							}
							else
							{
								$viewValue = "http://{$value}";
							}
						}
						elseif($valueType === 'FACEBOOK')
						{
							$viewValue = "http://www.facebook.com/{$value}/";
						}
						elseif($valueType === 'TWITTER')
						{
							$viewValue = "http://twitter.com/{$value}/";
						}
						elseif($valueType === 'LIVEJOURNAL')
						{
							$viewValue = "http://{$value}.livejournal.com/";
						}
					}
					elseif($fieldTypeID === 'IM')
					{
						if($valueType === 'SKYPE')
						{
							$viewValue = "skype:{$value}?chat";
						}
						elseif($valueType === 'ICQ')
						{
							$viewValue = "http://www.icq.com/people/{$value}/";
						}
						elseif($valueType === 'MSN')
						{
							$viewValue = "msn:{$value}";
						}
					}

					$field['type'] = 'custom';
					$field['value'] = str_replace(
						array('#NAME#', '#FIELD_TYPE#', '#VALUE#', '#VIEW_VALUE#'),
						array($fieldID, htmlspecialcharsbx($editorFieldType), htmlspecialcharsbx($value), htmlspecialcharsbx($viewValue)),
						$template
					);
				}
			}

			$formFields[] = !empty($fieldParams) ? array_merge($field, $fieldParams) : $field;
		}
		unset($data);

		return true;
	}

	public static function CreateField($fieldID, $fieldName, $fieldTemplateName, $fieldValues, &$formFields, $fieldParams = array(), $ignoreIfEmpty = true)
	{
		$fieldID = strval($fieldID);
		$fieldName = strval($fieldName);
		$fieldTemplateName = strval($fieldTemplateName);

		if(!isset(self::$TEMPLATES[$fieldTemplateName]))
		{
			return false;
		}

		$field = array(
			'id' => $fieldID,
			'name' => $fieldName
		);

		if($fieldTemplateName === 'FULL_NAME')
		{
			$field['type'] = 'custom';
			$field['value'] = str_replace(
				array(
					'#VALUE_1#', '#NAME_1#',
					'#VALUE_2#', '#NAME_2#',
					'#VALUE_3#', '#NAME_3#',
				),
				array(
					isset($fieldValues['LAST_NAME']) ? htmlspecialcharsbx($fieldValues['LAST_NAME']) : '', 'LAST_NAME',
					isset($fieldValues['NAME']) ? htmlspecialcharsbx($fieldValues['NAME']) : '', 'NAME',
					isset($fieldValues['SECOND_NAME']) ? htmlspecialcharsbx($fieldValues['SECOND_NAME']) : '', 'SECOND_NAME'
				),
				self::$TEMPLATES[$fieldTemplateName]
			);
		}
		elseif($fieldTemplateName === 'INPUT' || $fieldTemplateName === 'TEXT_AREA')
		{
			$value = isset($fieldValues['VALUE']) ? htmlspecialcharsbx($fieldValues['VALUE']) : '';
			if($value === '' && $ignoreIfEmpty)
			{
				// IGNORE EMPTY VALUES (INSERT STUB ONLY)
				$field['type'] = 'label';
				$field['value'] = '';
			}
			else
			{
				if($fieldTemplateName === 'TEXT_AREA')
				{
					//Convert NL, CR chars to BR tags
					$value = str_replace(array("\r", "\n"), '', nl2br($value));
				}

				$field['type'] = 'custom';
				$field['value'] = str_replace(
					array(
						'#VALUE#',
						'#NAME#'
					),
					array(
						$value,
						$fieldID
					),
					self::$TEMPLATES[$fieldTemplateName]
				);
			}
		}
		elseif($fieldTemplateName === 'SELECT')
		{
			$value = isset($fieldValues['VALUE']) ? $fieldValues['VALUE'] : '';
			$text = isset($fieldValues['TEXT']) ? $fieldValues['TEXT'] : '';
			$class = isset($fieldValues['CLASS']) ? $fieldValues['CLASS'] : '';

			$options = isset($fieldValues['OPTIONS']) && is_array($fieldValues['OPTIONS']) ? $fieldValues['OPTIONS'] : array();
			$optionHtml = '';
			if(!empty($options))
			{
				foreach($options as $k => &$v)
				{
					$optionHtml .= '<option value="'.htmlspecialcharsbx($k).'"'.($value === $v ? 'selected="selected"' : '').'>'.htmlspecialcharsbx($v).'</option>';
				}
				unset($v);
			}
			if($class !== '')
			{
				$class = ' '.$class;
			}

			$field['type'] = 'custom';
			$field['value'] = str_replace(
				array(
					'#NAME#',
					'#VALUE#',
					'#TEXT#',
					'#CLASS#',
					'#OPTIONS_HTML#'
				),
				array(
					$fieldID,
					htmlspecialcharsbx($value),
					htmlspecialcharsbx($text),
					htmlspecialcharsbx($class),
					$optionHtml
				),
				self::$TEMPLATES[$fieldTemplateName]
			);
		}
		elseif($fieldTemplateName === 'LHE')
		{
			$value = isset($fieldValues['VALUE']) ? $fieldValues['VALUE'] : '';
			if($value === '' && $ignoreIfEmpty)
			{
				// IGNORE EMPTY VALUES (INSERT STUB ONLY)
				$field['type'] = 'label';
				$field['value'] = '';
			}
			else
			{
				$editorID = isset($fieldValues['EDITOR_ID']) ? $fieldValues['EDITOR_ID'] : '';
				if($editorID ==='')
				{
					$editorID = uniqid('LHE_');
				}

				$editorJsName = isset($fieldValues['EDITOR_JS_NAME']) ? $fieldValues['EDITOR_JS_NAME'] : '';
				if($editorJsName ==='')
				{
					$editorJsName = $editorID;
				}

				if(!self::$IS_FILEMAN_INCLUDED)
				{
					CModule::IncludeModule('fileman');
					self::$IS_FILEMAN_INCLUDED = true;
				}

				ob_start();
				$editor = new CLightHTMLEditor;
				$editor->Show(
					array(
						'id' => $editorID,
						'height' => '150',
						'bUseFileDialogs' => false,
						'bFloatingToolbar' => false,
						'bArisingToolbar' => false,
						'bResizable' => false,
						'jsObjName' => $editorJsName,
						'bInitByJS' => false, // TODO: Lazy initialization
						'bSaveOnBlur' => true,
						'bHandleOnPaste'=> false,
						'toolbarConfig' => array(
							'Bold', 'Italic', 'Underline', 'Strike',
							'BackColor', 'ForeColor',
							'CreateLink', 'DeleteLink',
							'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
						)
					)
				);
				$lheHtml = ob_get_contents();
				ob_end_clean();

				$wrapperID = isset($fieldValues['WRAPPER_ID']) ? $fieldValues['WRAPPER_ID'] : '';
				if($wrapperID ==='')
				{
					$wrapperID = $editorID.'_WRAPPER';
				}

				$field['type'] = 'custom';
				$field['value'] = str_replace(
					array(
						'#TEXT#',
						'#VALUE#',
						'#NAME#',
						'#SETTINGS#',
						'#WRAPPER_ID#',
						'#HTML#'
					),
					array(
						$value,
						htmlspecialcharsbx($value),
						$fieldID,
						htmlspecialcharsbx('{ "id":"'.CUtil::JSEscape($editorID).'", "wrapperId":"'.CUtil::JSEscape($wrapperID).'", "jsName":"'.CUtil::JSEscape($editorJsName).'" }'),
						$wrapperID,
						$lheHtml
					),
					self::$TEMPLATES[$fieldTemplateName]
				);
			}
		}
		$formFields[] = !empty($fieldParams) ? array_merge($field, $fieldParams) : $field;
		return true;
	}

	public static function PrepareUserInfo($userID, &$userInfo, $options = array())
	{
		$userID = intval($userID);
		if($userID <= 0)
		{
			return false;
		}

		// Check if extranet user request intranet user info
		if(IsModuleInstalled('extranet')
			&& CModule::IncludeModule('extranet')
			&& $userID != CCrmSecurityHelper::GetCurrentUserID()
			&& !CExtranet::IsProfileViewableByID($userID))
		{
			return false;
		}

		$dbUser = CUser::GetList(
			$by = 'ID',
			$order = 'ASC',
			array('ID' => $userID)
		);

		$arUser = $dbUser->Fetch();
		if(!is_array($arUser))
		{
			return false;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$photoW = isset($options['PHOTO_WIDTH']) ? intval($options['PHOTO_WIDTH']) : 0;
		$photoH = isset($options['PHOTO_HEIGHT']) ? intval($options['PHOTO_HEIGHT']) : 0;

		$photoInfo = CFile::ResizeImageGet(
			$arUser['PERSONAL_PHOTO'],
			array(
				'width' => $photoW > 0 ? $photoW : 32,
				'height'=> $photoH > 0 ? $photoH : 32
			),
			BX_RESIZE_IMAGE_EXACT
		);

		$nameTemplate = isset($options['NAME_TEMPLATE']) ? $options['NAME_TEMPLATE'] : '';

		$userInfo['ID'] = $userID;
		$userInfo['FULL_NAME'] = CUser::FormatName(
			$nameTemplate !== '' ? $nameTemplate : CSite::GetNameFormat(false),
			$arUser,
			true,
			false
		);

		$urlTemplate = isset($options['USER_PROFILE_URL_TEMPLATE']) ? $options['USER_PROFILE_URL_TEMPLATE'] : '';
		$userInfo['USER_PROFILE'] = $urlTemplate !== ''
			? CComponentEngine::MakePathFromTemplate(
				$urlTemplate,
				array('user_id' => $userID)
			)
			: '';

		$userInfo['WORK_POSITION'] = isset($arUser['WORK_POSITION']) ? $arUser['WORK_POSITION'] : '';
		$userInfo['PERSONAL_PHOTO'] = isset($photoInfo['src']) ? $photoInfo['src'] : '';

		return true;
	}

	public static function PrepareUpdate($ownerTypeID, &$arFields, &$arFieldNames, &$arFieldValues)
	{
		$sanitizer = null;
		$count = count($arFieldNames);
		for($i = 0; $i < $count; $i++)
		{
			$fieldName = $arFieldNames[$i];
			$fieldValue = isset($arFieldValues[$i]) ? $arFieldValues[$i] : '';

			if($fieldName === 'COMMENTS' || $fieldName === 'USER_DESCRIPTION')
			{
				if($sanitizer === null)
				{
					$sanitizer = new CBXSanitizer();
					$sanitizer->ApplyDoubleEncode(false);
					$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
					//Crutch for for Chrome line break behaviour in HTML editor and background button.
					$sanitizer->AddTags(array('div' => array(), 'span' => array('style')));
				}
				$arFields[$fieldName] = $sanitizer->SanitizeHtml($fieldValue);
			}
			elseif(strpos($fieldName, 'FM.') === 0)
			{
				// Processing of multifield name (FM.[TYPE].[VALUE_TYPE].[ID])
				$fmParts = explode('.', substr($fieldName, 3));
				if(count($fmParts) === 3)
				{
					list($fmType, $fmValueType, $fmID) = $fmParts;

					$fmType = strval($fmType);
					$fmValueType = strval($fmValueType);
					$fmID = intval($fmID);

					if($fmType !== '' && $fmValueType !== '' && $fmID > 0)
					{
						if(!isset($arFields['FM']))
						{
							$arFields['FM'] = array();
						}

						if(!isset($arFields['FM'][$fmType]))
						{
							$arFields['FM'][$fmType] = array();
						}

						$arFields['FM'][$fmType][$fmID] = array('VALUE_TYPE' => $fmValueType, 'VALUE' => $fieldValue);
					}
				}
			}
			elseif(array_key_exists($fieldName, $arFields))
			{
				$arFields[$fieldName] = $fieldValue;
			}
		}

		if($ownerTypeID === CCrmOwnerType::Lead
			|| $ownerTypeID === CCrmOwnerType::Deal
			|| $ownerTypeID === CCrmOwnerType::Contact
			|| $ownerTypeID === CCrmOwnerType::Company)
		{
			if(isset($arFields['CREATED_BY_ID']))
			{
				unset($arFields['CREATED_BY_ID']);
			}

			if(isset($arFields['DATE_CREATE']))
			{
				unset($arFields['DATE_CREATE']);
			}

			if(isset($arFields['MODIFY_BY_ID']))
			{
				unset($arFields['MODIFY_BY_ID']);
			}

			if(isset($arFields['DATE_MODIFY']))
			{
				unset($arFields['DATE_MODIFY']);
			}
		}
	}

	public static function RenderHtmlEditor(&$arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
		$value = isset($arParams['VALUE']) ? $arParams['VALUE'] : '';

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

		$toolbarConfig = isset($arParams['TOOLBAR_CONFIG']) ? $arParams['TOOLBAR_CONFIG'] : null;
		if(!is_array($toolbarConfig))
		{
			$toolbarConfig = array(
				'Bold', 'Italic', 'Underline', 'Strike',
				'BackColor', 'ForeColor',
				'CreateLink', 'DeleteLink',
				'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
			);
		}

		if(!self::$IS_FILEMAN_INCLUDED)
		{
			CModule::IncludeModule('fileman');
			self::$IS_FILEMAN_INCLUDED = true;
		}

		ob_start();
		$editor = new CLightHTMLEditor;
		$editor->Show(
			array(
				'id' => $editorID,
				'height' => '150',
				'bUseFileDialogs' => false,
				'bFloatingToolbar' => false,
				'bArisingToolbar' => false,
				'bResizable' => false,
				'jsObjName' => $editorJsName,
				'bInitByJS' => false, // TODO: Lazy initialization
				'bSaveOnBlur' => true,
				'toolbarConfig' => $toolbarConfig
			)
		);
		$lheHtml = ob_get_contents();
		ob_end_clean();

		$wrapperID = isset($arParams['WRAPPER_ID']) ? $arParams['WRAPPER_ID'] : '';
		if($wrapperID ==='')
		{
			$wrapperID = $editorID.'_WRAPPER';
		}

		echo str_replace(
			array(
				'#TEXT#',
				'#VALUE#',
				'#NAME#',
				'#SETTINGS#',
				'#WRAPPER_ID#',
				'#HTML#'
			),
			array(
				$value,
				htmlspecialcharsbx($value),
				$fieldID,
				htmlspecialcharsbx('{ "id":"'.CUtil::JSEscape($editorID).'", "wrapperId":"'.CUtil::JSEscape($wrapperID).'", "jsName":"'.CUtil::JSEscape($editorJsName).'" }'),
				$wrapperID,
				$lheHtml
			),
			self::$TEMPLATES['LHE']
		);
	}

	public static function RenderTextArea(&$arParams)
	{
		if(!is_array($arParams))
		{
			return;
		}

		$fieldID = isset($arParams['FIELD_ID']) ? $arParams['FIELD_ID'] : '';
		$value = isset($arParams['VALUE']) ? htmlspecialcharsbx($arParams['VALUE']) : '';
		//Convert NL, CR chars to BR tags
		$value = str_replace(array("\r", "\n"), '', nl2br($value));

		echo str_replace(
			array(
				'#VALUE#',
				'#NAME#'
			),
			array(
				$value,
				$fieldID
			),
			self::$TEMPLATES['TEXT_AREA']
		);
	}
}
