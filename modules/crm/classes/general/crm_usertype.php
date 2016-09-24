<?php

IncludeModuleLangFile(__FILE__);

class CCrmUserType
{
	protected $cUFM = null;
	public $sEntityID = '';

	function __construct(CUserTypeManager $cUFM, $sEntityID)
	{
		$this->cUFM = $cUFM;
		$this->sEntityID = $sEntityID;
	}

	public function ListAddFilterFields(&$arFilterFields, &$arFilterLogic, $sFormName = 'form1', $bVarsFromForm = true)
	{
		global $APPLICATION;
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if ($arUserField['SHOW_FILTER'] != 'N' && $arUserField['USER_TYPE']['BASE_TYPE'] != 'file')
			{
				if($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'employee')
				{
					$arFilterFields[] = array(
						'id' => $FIELD_NAME,
						'name' => htmlspecialcharsex($arUserField['LIST_FILTER_LABEL']),
						'type' => 'user',
						'enable_settings' => false
					);
					continue;
				}

				if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' ||
					$arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_element' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_section')
				{
					// Fix #29649. Allow user to add not multiple fields with height 1 item.
					if($arUserField['MULTIPLE'] !== 'Y'
						&& isset($arUserField['SETTINGS']['LIST_HEIGHT'])
						&& intval($arUserField['SETTINGS']['LIST_HEIGHT']) > 1)
					{
						$arUserField['MULTIPLE'] = 'Y';
					}

					//as the system presets the filter can not work with the field names containing []
					if ($arUserField['SETTINGS']['DISPLAY'] == 'CHECKBOX')
						$arUserField['SETTINGS']['DISPLAY'] = '';
				}

				ob_start();
				$APPLICATION->IncludeComponent(
					'bitrix:crm.field.filter',
					$arUserField['USER_TYPE']['USER_TYPE_ID'],
					array(
						'arUserField' => $arUserField,
						'bVarsFromForm' => $bVarsFromForm,
						'form_name' => 'filter_'.$sFormName,
						'bShowNotSelected' => true
					),
					false,
					array('HIDE_ICONS' => true)
				);
				$sVal = ob_get_contents();
				ob_end_clean();

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => htmlspecialcharsex($arUserField['LIST_FILTER_LABEL']),
					'type' => 'custom',
					'value' => $sVal
				);

				// Fix issue #49771 - do not treat 'crm' type values as strings. To suppress filtration by LIKE.
				// Fix issue #56844 - do not treat 'crm_status' type values as strings. To suppress filtration by LIKE.
				if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'string' && $arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'crm' && $arUserField['USER_TYPE']['USER_TYPE_ID'] !== 'crm_status')
					$arFilterLogic[] = $FIELD_NAME;
			}
		}
	}

	public function AddFields(&$arFilterFields, $ID, $sFormName = 'form1', $bVarsFromForm = false, $bShow = false, $bParentComponent = false, $arOptions = array())
	{
		$arOptions = is_array($arOptions) ? $arOptions : array();
		$fileUrlTemplate = isset($arOptions['FILE_URL_TEMPLATE']) ? $arOptions['FILE_URL_TEMPLATE'] : '';
		$skipRendering = isset($arOptions['SKIP_RENDERING']) ? $arOptions['SKIP_RENDERING'] : array();
		$isTactile = isset($arOptions['IS_TACTILE']) ? $arOptions['IS_TACTILE'] : false;


		global $APPLICATION;
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, $ID, LANGUAGE_ID);
		$count = 0;

		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			if(!isset($arUserField['ENTITY_VALUE_ID']))
			{
				$arUserField['ENTITY_VALUE_ID'] = intval($ID);
			}

			$viewMode = $bShow;
			if(!$viewMode && $arUserField['EDIT_IN_LIST'] === 'N')
			{
				//Editing is not allowed for this field
				$viewMode = true;
			}
			$userTypeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];

			if(in_array($userTypeID, $skipRendering, true))
			{
				$value = isset($arUserField['VALUE']) ? $arUserField['VALUE'] : '';
				if($userTypeID === 'string' || $userTypeID === 'double')
				{
					$fieldType = 'text';
				}
				elseif($userTypeID === 'boolean')
				{
					$fieldType = 'checkbox';
					$value = intval($value) > 0 ? 'Y' : 'N';
				}
				elseif($userTypeID === 'datetime')
				{
					$fieldType = 'date';
				}
				else
				{
					$fieldType = $userTypeID;
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					'name' => ('' != $arUserField['EDIT_FORM_LABEL'] ? $arUserField['EDIT_FORM_LABEL'] : $arUserField['FIELD_NAME']),
					'type' => $fieldType,
					'value' => $value,
					'required' => !$viewMode && $arUserField['MANDATORY'] == 'Y' ? true : false,
					'isTactile' => $isTactile
				);
			}
			else
			{
				if ($userTypeID === 'employee')
				{
					if ($viewMode)
					{
						if (!is_array($arUserField['VALUE']))
							$arUserField['VALUE'] = array($arUserField['VALUE']);
						ob_start();
						foreach ($arUserField['VALUE'] as $k)
						{
							$APPLICATION->IncludeComponent('bitrix:main.user.link',
								'',
								array(
									'ID' => $k,
									'HTML_ID' => 'crm_'.$FIELD_NAME,
									'USE_THUMBNAIL_LIST' => 'Y',
									'SHOW_YEAR' => 'M',
									'CACHE_TYPE' => 'A',
									'CACHE_TIME' => '3600',
									'NAME_TEMPLATE' => '',//$arParams['NAME_TEMPLATE'],
									'SHOW_LOGIN' => 'Y',
								),
								false,
								array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
							);
						}
						$sVal = ob_get_contents();
						ob_end_clean();
					}
					else
					{
						$val = !$bVarsFromForm ? $arUserField['VALUE'] : (isset($_REQUEST[$FIELD_NAME]) ? $_REQUEST[$FIELD_NAME] : '');
						$val_string = '';
						if (is_array($val))
							foreach ($val as $_val)
							{	if (empty($_val))
									continue;
								$rsUser = CUser::GetByID($_val);
								$val_string .=  CUser::FormatName(CSite::GetNameFormat(false).' [#ID#], ', $rsUser->Fetch(), true, false);
							}
						else if (!empty($val))
						{
							$rsUser = CUser::GetByID($val);
							$val_string .=  CUser::FormatName(CSite::GetNameFormat(false).' [#ID#], ', $rsUser->Fetch(), true, false);
						}
						ob_start();
						$GLOBALS['APPLICATION']->IncludeComponent('bitrix:intranet.user.selector',
							'',
							array(
								'INPUT_NAME' => $FIELD_NAME,
								'INPUT_VALUE' => $val,
								'INPUT_VALUE_STRING' => $val_string,
								'MULTIPLE' => $arUserField['MULTIPLE']
							),
							false,
							array('HIDE_ICONS' => 'Y')
						);
						$sVal = ob_get_contents();
						ob_end_clean();
					}
				}
				else
				{
					if($viewMode && $userTypeID === 'file' && $fileUrlTemplate !== '')
					{
						// In view mode we have to use custom rendering for hide real file URL's ('bitrix:system.field.view' can't do it)
						$fileIDs = isset($arUserField['VALUE'])
							? (is_array($arUserField['VALUE'])
								? $arUserField['VALUE']
								: array($arUserField['VALUE']))
							: array();

						ob_start();
						CCrmViewHelper::RenderFiles(
							$fileIDs,
							CComponentEngine::MakePathFromTemplate(
								$fileUrlTemplate,
								array('owner_id' => $ID, 'field_name' => $FIELD_NAME)
							),
							480,
							480
						);
						$sVal = ob_get_contents();
						ob_end_clean();
					}
					else
					{
						ob_start();
						$APPLICATION->IncludeComponent(
							'bitrix:system.field.'.($viewMode ? 'view' : 'edit'),
							$userTypeID,
							array(
								'arUserField' => $arUserField,
								'bVarsFromForm' => $bVarsFromForm,
								'form_name' => 'form_'.$sFormName,
								'FILE_MAX_HEIGHT' => 480,
								'FILE_MAX_WIDTH' => 480,
								'FILE_SHOW_POPUP' => true,
								'SHOW_FILE_PATH' => false,
								'FILE_URL_TEMPLATE' => CComponentEngine::MakePathFromTemplate(
									$fileUrlTemplate,
									array('owner_id' => $ID, 'field_name' => $FIELD_NAME)
								)
							),
							false,
							array('HIDE_ICONS' => 'Y')
						);
						$sVal = ob_get_contents();
						ob_end_clean();
					}
				}

				$arFilterFields[] = array(
					'id' => $FIELD_NAME,
					//'name' => htmlspecialcharsbx($arUserField['EDIT_FORM_LABEL']),
					'name' => ('' != $arUserField['EDIT_FORM_LABEL'] ? $arUserField['EDIT_FORM_LABEL'] : $arUserField['FIELD_NAME']),
					'type' => 'custom',
					'value' => $sVal,
					'required' => !$viewMode && $arUserField['MANDATORY'] == 'Y' ? true : false,
					'isTactile' => $isTactile
				);
			}
			$count++;
		}
		unset($arUserField);

		return $count;
	}

	private static function TryResolveEnumerationID($value, &$enums, &$ID, $fieldName = 'VALUE')
	{
		$fieldName = strval($fieldName);
		if($fieldName === '')
		{
			$fieldName = 'VALUE';
		}

		// 1. Try to interpret value as enum ID
		if(isset($enums[$value]))
		{
			$ID = $value;
			return true;
		}

		// 2. Try to interpret value as enum VALUE
		$uv = strtoupper(trim($value));

		$success = false;
		foreach($enums as $enumID => &$enum)
		{
			if(strtoupper($enum[$fieldName]) === $uv)
			{
				$ID = $enumID;
				$success = true;
				break;
			}
		}
		unset($enum);
		return $success;
	}

	private static function InternalizeEnumValue(&$value, &$enums, $fieldName = 'VALUE')
	{
		$enumID = '';
		if(is_array($value))
		{
			foreach($value as $k => $v)
			{
				if(self::TryResolveEnumerationID($v, $enums, $enumID, $fieldName))
				{
					$value[$k] = $enumID;
				}
			}
		}
		elseif(is_string($value) && $value !== '')
		{
			if(self::TryResolveEnumerationID($value, $enums, $enumID, $fieldName))
			{
				$value = $enumID;
			}
		}
	}

	private static function TryInternalizeCrmEntityID($type, $value, &$ID)
	{
		if(preg_match('/^\[([A-Z]+)\]/i', $value, $m) > 0)
		{
			$valueType = CCrmOwnerType::Undefined;
			$prefix = strtoupper($m[1]);
			if($prefix === 'L')
			{
				$valueType = CCrmOwnerType::Lead;
			}
			elseif($prefix === 'C')
			{
				$valueType = CCrmOwnerType::Contact;
			}
			elseif($prefix === 'CO')
			{
				$valueType = CCrmOwnerType::Company;
			}
			elseif($prefix === 'D')
			{
				$valueType = CCrmOwnerType::Deal;
			}

			if($valueType !== CCrmOwnerType::Undefined && $valueType !== $type)
			{
				return false;
			}

			$value = substr($value, strlen($m[0]));
		}

		// 1. Try to interpret data as entity ID
		// 2. Try to interpret data as entity name
		if($type === CCrmOwnerType::Lead)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmLead::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmLead::GetList(array(), array('=TITLE'=> $value), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}
		}
		elseif($type === CCrmOwnerType::Contact)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmContact::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			// Try to interpret value as FULL_NAME
			$rsEntities = CCrmContact::GetListEx(array(), array('=FULL_NAME'=> $value), false, false, array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}

			if(preg_match('/\s*([^\s]+)\s+([^\s]+)\s*/', $value, $match) > 0)
			{
				// Try to interpret value as '#NAME# #LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=NAME'=> $match[1], '=LAST_NAME'=> $match[2]),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}

				// Try to interpret value as '#LAST_NAME# #NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $match[1], '=NAME'=> $match[2]),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}
			else
			{
				// Try to interpret value as '#LAST_NAME#'
				$rsEntities = CCrmContact::GetListEx(array(), array('=LAST_NAME'=> $value),  false, false, array('ID'));
				while($arEntity = $rsEntities->Fetch())
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}
		}
		elseif($type === CCrmOwnerType::Company)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmCompany::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmCompany::GetList(array(), array('=TITLE'=> $value), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}
		}
		elseif($type === CCrmOwnerType::Deal)
		{
			if(is_numeric($value))
			{
				$arEntity = CCrmDeal::GetByID($value);
				if($arEntity)
				{
					$ID = intval($arEntity['ID']);
					return true;
				}
			}

			$rsEntities = CCrmDeal::GetList(array(), array('=TITLE'=> $value), array('ID'));
			while($arEntity = $rsEntities->Fetch())
			{
				$ID = intval($arEntity['ID']);
				return true;
			}
		}
		return false;
	}

	private static function InternalizeCrmEntityValue(&$value, &$settings)
	{
		if(is_array($value))
		{
			foreach($value as $k => $v)
			{
				$entityID = 0;
				if(isset($settings['LEAD'])
					&& strtoupper($settings['LEAD']) === 'Y'
					&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Lead, $v, $entityID))
				{
					$value[$k] = "L_$entityID";
				}
				elseif(isset($settings['CONTACT'])
					&& strtoupper($settings['CONTACT']) === 'Y'
					&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Contact, $v, $entityID))
				{
					$value[$k] = "C_$entityID";
				}
				elseif(isset($settings['COMPANY'])
					&& strtoupper($settings['COMPANY']) === 'Y'
					&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Company, $v, $entityID))
				{
					$value[$k] = "CO_$entityID";
				}
				elseif(isset($settings['DEAL'])
					&& strtoupper($settings['DEAL']) === 'Y'
					&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Deal, $v, $entityID))
				{
					$value[$k] = "D_$entityID";
				}
			}
		}
		elseif(is_string($value) && $value !== '')
		{
			$entityID = 0;
			if(isset($settings['LEAD'])
				&& strtoupper($settings['LEAD']) === 'Y'
				&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Lead, $value, $entityID))
			{
				$value = "L_$entityID";
			}
			elseif(isset($settings['CONTACT'])
				&& strtoupper($settings['CONTACT']) === 'Y'
				&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Contact, $value, $entityID))
			{
				$value = "C_$entityID";
			}
			elseif(isset($settings['COMPANY'])
				&& strtoupper($settings['COMPANY']) === 'Y'
				&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Company, $value, $entityID))
			{
				$value = "CO_$entityID";
			}
			elseif(isset($settings['DEAL'])
				&& strtoupper($settings['DEAL']) === 'Y'
				&& self::TryInternalizeCrmEntityID(CCrmOwnerType::Deal, $value, $entityID))
			{
				$value = "D_$entityID";
			}
		}
	}

	public function Internalize($name, $data, $delimiter = ',')
	{
		$delimiter = strval($delimiter);
		if($delimiter === '')
		{
			$delimiter = ',';
		}

		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		$arUserField = isset($arUserFields[$name]) ? $arUserFields[$name] : null;
		if(!$arUserField)
		{
			return $data; // return original data
		}

		$data = strval($data);

		if ($arUserField['MULTIPLE'] === 'Y')
		{
			$data = explode($delimiter, $data);
			foreach ($data as &$v)
			{
				$v = trim($v);
			}
			unset($v);
		}

		$typeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];
		if ($typeID === 'file')
		{
			if(is_array($data))
			{
				$files = array();
				foreach($data as &$filePath)
				{
					$file = null;
					if(CCrmFileProxy::TryResolveFile($filePath, $file))
					{
						$files[] = $file;
					}
				}
				unset($filePath);
				$data = $files;
			}
			elseif($data !== '')
			{
				$file = null;
				if(CCrmFileProxy::TryResolveFile($data, $file))
				{
					$data = $file;
				}
			}
		}
		elseif($typeID === 'enumeration')
		{
			// Processing for type 'enumeration'

			$enums = array();
			$rsEnum = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => $arUserField['ID']));
			while ($arEnum = $rsEnum->Fetch())
			{
				$enums[$arEnum['ID']] = $arEnum;
			}

			self::InternalizeEnumValue($data, $enums);

		}
		elseif($typeID === 'employee')
		{
			// Processing for type 'employee' (we have to implement custom processing since CUserTypeEmployee::GetList doesn't provide VALUE property)
			$enums = array();

			$rsEnum = CUser::GetList($by = 'last_name', $order = 'asc');
			while ($arEnum = $rsEnum->Fetch())
			{
				$arEnum['VALUE'] = CUser::FormatName(CSite::GetNameFormat(false), $arEnum, false, true);
				$enums[$arEnum['ID']] = $arEnum;
			}

			self::InternalizeEnumValue($data, $enums);
		}
		elseif($typeID === 'crm')
		{
			// Processing for type 'crm' (is link to LEAD, CONTACT, COMPANY or DEAL)
			if(isset($arUserField['SETTINGS']))
			{
				self::InternalizeCrmEntityValue($data, $arUserField['SETTINGS']);
			}
		}
		elseif($typeID === 'boolean')
		{
			$yes = strtoupper(GetMessage('MAIN_YES'));
			//$no = strtoupper(GetMessage('MAIN_NO'));

			if(is_array($data))
			{
				foreach($data as &$v)
				{
					$s = strtoupper($v);
					$v = ($s === $yes || $s === 'Y' || $s === 'YES' || (is_numeric($s) && intval($s) > 0)) ? 1 : 0;
				}
				unset($v);
			}
			elseif(is_string($data) && $data !== '')
			{
				$s = strtoupper($data);
				$data = ($s === $yes || $s === 'Y' || $s === 'YES' || (is_numeric($s) && intval($s) > 0)) ? 1 : 0;
			}
			elseif(isset($arUserField['SETTINGS']['DEFAULT_VALUE']))
			{
				$data = $arUserField['SETTINGS']['DEFAULT_VALUE'];
			}
			else
			{
				$data = 0;
			}
		}
		elseif($typeID === 'datetime')
		{
			if(is_array($data))
			{
				foreach($data as &$v)
				{
					if(!CheckDateTime($v))
					{
						$timestamp = strtotime($v);
						$v = is_int($timestamp) && $timestamp > 0 ? ConvertTimeStamp($timestamp, 'FULL') : '';
					}
				}
				unset($v);
			}
			elseif(is_string($data) && $data !== '')
			{
				if(!CheckDateTime($data))
				{
					$timestamp = strtotime($data);
					$data = is_int($timestamp) && $timestamp > 0 ? ConvertTimeStamp($timestamp, 'FULL') : '';
				}
			}
		}
		elseif (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'getlist')))
		{
			// Processing for type user defined class

			$rsEnum = call_user_func_array(
				array($arUserField['USER_TYPE']['CLASS_NAME'], 'getlist'),
				array($arUserField)
			);

			$enums = array();
			while($arEnum = $rsEnum->GetNext())
			{
				$enums[strval($arEnum['ID'])] = $arEnum;
			}

			$fieldName = 'VALUE';
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'iblock_section'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'iblock_element')
			{
				$fieldName = '~NAME';
			}

			self::InternalizeEnumValue($data, $enums, $fieldName);
		}
		return $data;
	}

	public function ListAddEnumFieldsValue($arParams, &$arValue, &$arReplaceValue, $delimiter = '<br />', $textonly = false, $arOptions = array())
	{
		global $APPLICATION;
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		$bSecondLoop = false;
		$arValuePrepare = array();

		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		// The first loop to collect all the data fields
		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			foreach ($arValue as $ID => $data)
			{
				if (!isset($arValue[$ID][$FIELD_NAME]) && $arUserField['USER_TYPE']['USER_TYPE_ID'] != 'boolean')
					continue;

				if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
				{
					if (isset($arValue[$ID][$FIELD_NAME]))
						$arValue[$ID][$FIELD_NAME] == ($arValue[$ID][$FIELD_NAME] == 1 || $arValue[$ID][$FIELD_NAME] == 'Y' ? 'Y' : 'N');

					$arVal = $arValue[$ID][$FIELD_NAME];
					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $val)
					{
						$val = (string)$val;

						if (strlen($val) <= 0)
						{
							//Empty value is always 'N' (not default field value)
							$val = 'N';
						}

						$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').($val == 1 ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'));
						if ($arUserField['MULTIPLE'] == 'Y')
							$arValue[$ID][$FIELD_NAME][] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';
						else
							$arValue[$ID][$FIELD_NAME] = ($val == 1 || $val == 'Y') ? 'Y' : 'N';

					}
				}
				elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
				{
					$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
					$arReplaceValue[$ID][$FIELD_NAME] = isset($ar[$arValue[$ID][$FIELD_NAME]])? $ar[$arValue[$ID][$FIELD_NAME]]: '';
				}
				else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm')
				{
					$arParams['CRM_ENTITY_TYPE'] = Array();
					if ($arUserField['SETTINGS']['LEAD'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'LEAD';
					if ($arUserField['SETTINGS']['CONTACT'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'CONTACT';
					if ($arUserField['SETTINGS']['COMPANY'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'COMPANY';
					if ($arUserField['SETTINGS']['DEAL'] == 'Y')
						$arParams['CRM_ENTITY_TYPE'][] = 'DEAL';

					$arParams['CRM_PREFIX'] = false;
					if (count($arParams['CRM_ENTITY_TYPE']) > 1)
						$arParams['CRM_PREFIX'] = true;

					$bSecondLoop = true;
					$arVal = $arValue[$ID][$FIELD_NAME];
					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $value)
					{
						if($arParams['CRM_PREFIX'])
						{
							$ar = explode('_', $value);
							$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
							$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][CUserTypeCrm::GetLongEntityType($ar[0])][intval($ar[1])] = intval($ar[1]);
						}
						else
						{
							if (is_numeric($value))
							{
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][$arParams['CRM_ENTITY_TYPE'][0]][] = $value;
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][$arParams['CRM_ENTITY_TYPE'][0]][$value] = $value;
							}
							else
							{
								$ar = explode('_', $value);
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']][CUserTypeCrm::GetLongEntityType($ar[0])][] = intval($ar[1]);
								$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][CUserTypeCrm::GetLongEntityType($ar[0])][intval($ar[1])] = intval($ar[1]);
							}
						}
					}
					$arReplaceValue[$ID][$FIELD_NAME] = '';
				}
				else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'file'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_element'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'enumeration'
					|| $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'iblock_section')
				{
					$bSecondLoop = true;
					$arVal = $arValue[$ID][$FIELD_NAME];
					$arReplaceValue[$ID][$FIELD_NAME] = '';

					if (!is_array($arVal))
						$arVal = array($arVal);

					foreach ($arVal as $value)
					{
						$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['FIELD'][$ID][$FIELD_NAME][$value] = $value;
						$arValuePrepare[$arUserField['USER_TYPE']['USER_TYPE_ID']]['ID'][] = $value;
					}
				}
				else if ($arUserField['MULTIPLE'] == 'Y' && is_array($arValue[$ID][$FIELD_NAME]))
				{
					array_walk($arValue[$ID][$FIELD_NAME], create_function('&$v',  '$v = htmlspecialcharsbx($v);'));
					$arReplaceValue[$ID][$FIELD_NAME] = implode($delimiter, $arValue[$ID][$FIELD_NAME]);
				}
			}
		}
		unset($arUserField);


		// The second loop for special field
		if($bSecondLoop)
		{
			$arValueReplace = Array();
			$arList = Array();
			foreach($arValuePrepare as $KEY => $VALUE)
			{
				// collect multi data
				if ($KEY == 'iblock_section')
				{
					$dbRes = CIBlockSection::GetList(array('left_margin' => 'asc'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'file')
				{
					$dbRes = CFile::GetList(Array(), array('@ID' => implode(',', $VALUE['ID'])));
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'iblock_element')
				{
					$dbRes = CIBlockElement::GetList(array('SORT' => 'DESC', 'NAME' => 'ASC'), array('ID' => $VALUE['ID']), false);
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'employee')
				{
					$dbRes = CUser::GetList($by = 'last_name', $order = 'asc', array('ID' => implode('|', $VALUE['ID'])));
					while ($arRes = $dbRes->Fetch())
						$arList[$KEY][$arRes['ID']] = $arRes;
				}
				elseif ($KEY == 'enumeration')
				{
					foreach ($VALUE['ID'] as $___value)
					{
						$rsEnum = CUserFieldEnum::GetList(array(), array('ID' => $___value));
						while ($arRes = $rsEnum->Fetch())
							$arList[$KEY][$arRes['ID']] = $arRes;
					}
				}
				elseif ($KEY == 'crm')
				{
					if (isset($VALUE['LEAD']) && !empty($VALUE['LEAD']))
					{
						$dbRes = CCrmLead::GetList(Array('TITLE' => 'ASC', 'LAST_NAME' => 'ASC', 'NAME' => 'ASC'), array('ID' => $VALUE['LEAD']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['LEAD'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['CONTACT']) && !empty($VALUE['CONTACT']))
					{
						$dbRes = CCrmContact::GetList(Array('LAST_NAME' => 'ASC', 'NAME' => 'ASC'), array('ID' => $VALUE['CONTACT']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['CONTACT'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['COMPANY']) && !empty($VALUE['COMPANY']))
					{
						$dbRes = CCrmCompany::GetList(Array('TITLE' => 'ASC'), array('ID' => $VALUE['COMPANY']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['COMPANY'][$arRes['ID']] = $arRes;
					}
					if (isset($VALUE['DEAL']) && !empty($VALUE['DEAL']))
					{
						$dbRes = CCrmDeal::GetList(Array('TITLE' => 'ASC'), array('ID' => $VALUE['DEAL']));
						while ($arRes = $dbRes->Fetch())
							$arList[$KEY]['DEAL'][$arRes['ID']] = $arRes;
					}
				}

				// assemble multi data
				foreach ($VALUE['FIELD'] as $ID => $arFIELD_NAME)
				{
					foreach ($arFIELD_NAME as $FIELD_NAME => $FIELD_VALUE)
					{
						foreach ($FIELD_VALUE as $FIELD_VALUE_NAME => $FIELD_VALUE_ID)
						{
							if ($KEY == 'iblock_section')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							if ($KEY == 'iblock_element')
							{
								$sname = htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['NAME']);
								if(!$textonly)
								{
									$surl = GetIBlockElementLinkById($arList[$KEY][$FIELD_VALUE_ID]['ID']);
									if ($surl && strlen($surl) > 0)
									{
										$sname = '<a href="'.$surl.'">'.$sname.'</a>';
									}
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'employee')
							{
								$sname = CUser::FormatName(CSite::GetNameFormat(false), $arList[$KEY][$FIELD_VALUE_ID], false, true);
								if(!$textonly)
								{
									$ar['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_user_profile'), array('user_id' => $arList[$KEY][$FIELD_VALUE_ID]['ID']));
									$sname = 	'<a href="'.$ar['PATH_TO_USER_PROFILE'].'" id="balloon_'.$arParams['GRID_ID'].'_'.$arList[$KEY][$FIELD_VALUE_ID]['ID'].'">'.$sname.'</a>'.
										'<script type="text/javascript">BX.tooltip('.$arList[$KEY][$FIELD_VALUE_ID]['ID'].', "balloon_'.$arParams['GRID_ID'].'_'.$arList[$KEY][$FIELD_VALUE_ID]['ID'].'", "");</script>';
								}
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'enumeration')
							{
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['VALUE']);
							}
							else if ($KEY == 'file')
							{
								$fileInfo = $arList[$KEY][$FIELD_VALUE_ID];
								if($textonly)
								{
									$fileUrl = CFile::GetFileSRC($fileInfo);
								}
								else
								{
									$fileUrlTemplate = isset($arOptions['FILE_URL_TEMPLATE'])
										? $arOptions['FILE_URL_TEMPLATE'] : '';

									$fileUrl = $fileUrlTemplate === ''
										? CFile::GetFileSRC($fileInfo)
										: CComponentEngine::MakePathFromTemplate(
											$fileUrlTemplate,
											array('owner_id' => $ID, 'field_name' => $FIELD_NAME, 'file_id' => $fileInfo['ID'])
										);
								}

								$sname = $textonly ? $fileUrl : '<a href="'.htmlspecialcharsbx($fileUrl).'" target="_blank">'.htmlspecialcharsbx($arList[$KEY][$FIELD_VALUE_ID]['FILE_NAME']).'</a>';
								$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
							}
							else if ($KEY == 'crm')
							{
								foreach($FIELD_VALUE_ID as $CID)
								{
									$link = '';
									$title = '';
									$prefix = '';
									if ($FIELD_VALUE_NAME == 'LEAD')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'), array('lead_id' => $CID));
										$title = $arList[$KEY]['LEAD'][$CID]['TITLE'];
										$prefix = 'L';
									}
									elseif ($FIELD_VALUE_NAME == 'CONTACT')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'), array('contact_id' => $CID));
										$title = CCrmContact::GetFullName($arList[$KEY]['CONTACT'][$CID], true);
										$prefix = 'C';
									}
									elseif ($FIELD_VALUE_NAME == 'COMPANY')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'), array('company_id' => $CID));
										$title = $arList[$KEY]['COMPANY'][$CID]['TITLE'];
										$prefix = 'CO';
									}
									elseif ($FIELD_VALUE_NAME == 'DEAL')
									{
										$link = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_deal_show'), array('deal_id' => $CID));
										$title = $arList[$KEY]['DEAL'][$CID]['TITLE'];
										$prefix = 'D';
									}

									$sname = htmlspecialcharsbx($title);
									if(!$textonly)
									{
										$tooltip = '<script type="text/javascript">BX.tooltip('.$CID.', "balloon_'.$ID.'_'.$FIELD_NAME.'_'.$FIELD_VALUE_NAME.'_'.$CID.'", "/bitrix/components/bitrix/crm.'.strtolower($FIELD_VALUE_NAME).'.show/card.ajax.php", "crm_balloon'.($FIELD_VALUE_NAME == 'LEAD' || $FIELD_VALUE_NAME == 'DEAL' || $FIELD_VALUE_NAME == 'QUOTE' ? '_no_photo': '_'.strtolower($FIELD_VALUE_NAME)).'", true);</script>';
										$sname = '<a href="'.$link.'" target="_blank" id="balloon_'.$ID.'_'.$FIELD_NAME.'_'.$FIELD_VALUE_NAME.'_'.$CID.'">'.$sname.'</a>'.$tooltip;
									}
									else
									{
										$sname = "[$prefix]$sname";
									}
									$arReplaceValue[$ID][$FIELD_NAME] .= (!empty($arReplaceValue[$ID][$FIELD_NAME]) ? $delimiter : '').$sname;
								}
							}
						}
					}
				}
			}
		}
	}

	public function ListAddHeaders(&$arHeaders, $bImport = false)
	{
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$editable = true;
			$sType = $arUserField['USER_TYPE']['BASE_TYPE'];
			if ($arUserField['EDIT_IN_LIST'] === 'N'
				|| $arUserField['MULTIPLE'] === 'Y'
				||$arUserField['USER_TYPE']['BASE_TYPE'] === 'file'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'employee'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'crm')
				$editable = false;
			else if (in_array($arUserField['USER_TYPE']['USER_TYPE_ID'], array('enumeration', 'iblock_section', 'iblock_element')))
			{
				$sType = 'list';
				$editable = array(
					'items' => array('' => '')
				);
				if (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					while($ar = $rsEnum->GetNext())
						$editable['items'][$ar['ID']] = htmlspecialcharsback($ar['VALUE']);
				}
			}
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
			{
				$sType = 'list';
				$editable = array(
					'items' => array('1' => GetMessage('MAIN_YES'), '0' => GetMessage('MAIN_NO') )
				);
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
				$sType = 'date';
			elseif ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'crm_status')
			{
				$ar = CCrmStatus::GetStatusList($arUserField['SETTINGS']['ENTITY_TYPE']);
				$sType = 'list';
				$editable = array(
					'items' => Array('' => '') + $ar
				);
			}

			if ($arUserField['SHOW_IN_LIST']=='Y' || $bImport == true)
			{
				$arHeaders[$FIELD_NAME] = array(
					'id' => $FIELD_NAME,
					'name' => htmlspecialcharsbx($arUserField['LIST_COLUMN_LABEL']),
					'sort' => $arUserField['MULTIPLE'] == 'N' ? $FIELD_NAME : false,
					'default' => false,
					'editable' => $editable,
					'type' => $sType
				);
				if ($bImport)
					$arHeaders[$FIELD_NAME]['mandatory'] = $arUserField['MANDATORY'] === 'Y' ? 'Y' : 'N';
			}
		}
	}

	// Get Fields Metadata
	public function PrepareFieldsInfo(&$fieldsInfo)
	{
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);

		foreach($arUserFields as $FIELD_NAME => &$arUserField)
		{
			$userTypeID = $arUserField['USER_TYPE_ID'];
			$settings = isset($arUserField['SETTINGS']) ? $arUserField['SETTINGS'] : array();

			$info = array(
				'TYPE' => $userTypeID,
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Dynamic),
				'LABELS' => array(
					'LIST' => isset($arUserField['LIST_COLUMN_LABEL']) ? $arUserField['LIST_COLUMN_LABEL'] : '',
					'FORM' => isset($arUserField['EDIT_FORM_LABEL']) ? $arUserField['EDIT_FORM_LABEL'] : '',
					'FILTER' => isset($arUserField['LIST_FILTER_LABEL']) ? $arUserField['LIST_FILTER_LABEL'] : ''
				)
			);

			$isMultuple = isset($arUserField['MULTIPLE']) && $arUserField['MULTIPLE'] === 'Y';
			$isRequired = isset($arUserField['MANDATORY']) && $arUserField['MANDATORY'] === 'Y';
			if($isMultuple || $isRequired)
			{
				if($isMultuple)
				{
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Multiple;
				}

				if($isRequired)
				{
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Required;
				}
			}

			if($userTypeID === 'enumeration'
				&& isset($arUserField['USER_TYPE'])
				&& isset($arUserField['USER_TYPE']['CLASS_NAME']))
			{
				$enumResult = call_user_func_array(
					array($arUserField['USER_TYPE']['CLASS_NAME'], 'getlist'),
					array($arUserField)
				);

				$info['ITEMS'] = array();
				while($enum = $enumResult->GetNext())
				{
					$info['ITEMS'][] = array(
						'ID' => $enum['~ID'],
						'VALUE' => $enum['~VALUE']
					);
				}
			}
			elseif($userTypeID === 'crm_status')
			{
				$info['CRM_STATUS_TYPE'] = isset($settings['ENTITY_TYPE']) ? $settings['ENTITY_TYPE'] : '';
			}

			$fieldsInfo[$FIELD_NAME] = &$info;
			unset($info);

		}
		unset($arUserField);
	}

	public function AddBPFields(&$arHeaders, $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$beditable = true;
			$editable = array();

			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
			{
				$sType = "UF:boolean";
				$editable = $arUserField['SETTINGS'];
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'employee')
				$sType = 'user';
			else if (in_array($arUserField['USER_TYPE']['USER_TYPE_ID'], array('string', 'double', 'boolean', 'integer', 'datetime', 'file', 'employee'/*, 'enumeration'*/)))
			{
				if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum')
					$arUserField['USER_TYPE']['BASE_TYPE'] = 'select';
				$sType = $arUserField['USER_TYPE']['USER_TYPE_ID'];
				if($sType === 'employee')
				{
					//Fix for #37173
					$sType = 'user';
				}

				if ($sType === 'datetime')
				{
					$arUserField['SETTINGS']['EDIT_IN_LIST'] = $arUserField['EDIT_IN_LIST'];
					$editable = $arUserField['SETTINGS'];
				}
			}
			else
			{
				$userTypeID =  $arUserField['USER_TYPE']['USER_TYPE_ID'];
				if ($userTypeID == 'enumeration')
					$sType = 'select';
				else
					$sType = 'UF:'.$userTypeID;
				$editable = array();
				if ('iblock_element' == $userTypeID || 'iblock_section' == $userTypeID ||
					'crm_status' == $userTypeID || 'crm' == $userTypeID)
				{
					$editable = $arUserField['SETTINGS'];
				}
				elseif (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$fl = (COption::GetOptionString("crm", "bp_version", 2) == 2);
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					while($ar = $rsEnum->GetNext())
						$editable[$ar[$fl ? 'XML_ID' : 'ID']] = $ar['VALUE'];
				}
			}

			$fieldTitle = trim($arUserField['EDIT_FORM_LABEL']) !== '' ? $arUserField['EDIT_FORM_LABEL'] : $arUserField['FIELD_NAME'];

			$arHeaders[$FIELD_NAME] = array(
				'Name' => $fieldTitle,
				'Options' => $editable,
				'Type' => $sType,
				'Filterable' => $arUserField['MULTIPLE'] != 'Y',
				'Editable' => $beditable,
				'Multiple' => $arUserField['MULTIPLE'] == 'Y',
				'Required' => $arUserField['MANDATORY'] == 'Y',
			);

			if($arUserField['USER_TYPE']['USER_TYPE_ID'] === 'enumeration'
				|| $arUserField['USER_TYPE']['USER_TYPE_ID'] === 'crm')
			{
				$arHeaders[$FIELD_NAME.'_PRINTABLE'] = array(
					'Name' => $fieldTitle.' ('.(isset($arOptions['PRINTABLE_SUFFIX']) ? $arOptions['PRINTABLE_SUFFIX'] : 'text').')',
					'Options' => $editable,
					'Type' => $sType,
					'Filterable' => $arUserField['MULTIPLE'] != 'Y',
					'Editable' => false,
					'Multiple' => $arUserField['MULTIPLE'] == 'Y',
					'Required' => false,
				);
			}
		}
	}

	public function AddWebserviceFields(&$obFields)
	{
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$defVal = '';
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee')
				continue;
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum')
			{
				$sType = 'int';
				if (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					$obFieldValues = new CXMLCreator('CHOISES');
					while($ar = $rsEnum->GetNext())
					{
						$obFieldValue = new CXMLCreator('CHOISE', true);
						$obFieldValue->setAttribute('id', $ar['ID']);
						$obFieldValue->setData(htmlspecialcharsbx($ar['VALUE']));
						$obFieldValues->addChild($obFieldValue);
					}
				}
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'file')
				$sType = 'file';
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'boolean')
				$sType = 'boolean';
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'double' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'integer')
				$sType = 'int';
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
			{
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE']['VALUE'];
				$sType = 'datetime';
			}
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'string')
				$sType = 'string';
			else
				$sType = 'string';

			if (empty($defVal) && isset($arUserField['SETTINGS']['DEFAULT_VALUE']) && !is_array($arUserField['SETTINGS']['DEFAULT_VALUE']))
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE'];

			$obField = CXMLCreator::createTagAttributed('Field id="'.$FIELD_NAME.'" name="'.htmlspecialcharsbx($arUserField['EDIT_FORM_LABEL']).'" type="'.$sType.'" default="'.$defVal.'" require="'.($arUserField['MANDATORY'] == 'Y' ? 'true' : 'false').'" multy="'.($arUserField['MULTIPLE'] == 'Y' ? 'true' : 'false').'"', '');
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' && $obFieldValues instanceof CXMLCreator)
			{
				$obField->addChild($obFieldValues);
				unset($obFieldValues);
			}
			$obFields->addChild($obField);
		}
	}

	public function AddRestServiceFields(&$arFields)
	{
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$defVal = '';
			if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'employee')
				continue;
			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum')
			{
				$sType = 'enum';
				if (is_callable(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList')))
				{
					$rsEnum = call_user_func_array(array($arUserField['USER_TYPE']['CLASS_NAME'], 'GetList'), array($arUserField));
					$arValues = array();
					while($ar = $rsEnum->GetNext())
					{
						$arValues[] = array('ID' => $ar['ID'], 'NAME' => $ar['VALUE']);
					}
				}
			}
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'file')
				$sType = 'file';
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'boolean')
				$sType = 'boolean';
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'double' || $arUserField['USER_TYPE']['USER_TYPE_ID'] == 'integer')
				$sType = 'int';
			else if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'datetime')
			{
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE']['VALUE'];
				$sType = 'datetime';
			}
			else if ($arUserField['USER_TYPE']['USER_TYPE_ID'] == 'string')
				$sType = 'string';
			else
				$sType = 'string';

			if (empty($defVal) && isset($arUserField['SETTINGS']['DEFAULT_VALUE']) && !is_array($arUserField['SETTINGS']['DEFAULT_VALUE']))
				$defVal = $arUserField['SETTINGS']['DEFAULT_VALUE'];

			$arField = array('ID' => $FIELD_NAME, 'NAME' => $arUserField['EDIT_FORM_LABEL'], 'TYPE' => $sType, 'DEFAULT' => $defVal, 'REQUIRED' => $arUserField['MANDATORY'] == 'Y', 'MULTIPLE' => $arUserField['MULTIPLE'] == 'Y');

			if ($arUserField['USER_TYPE']['BASE_TYPE'] == 'enum' && is_array($arValues) && count($arValues) > 0)
			{
				$arField['VALUES'] = $arValues;
			}

			$arFields[] = $arField;
		}
	}

	public function PrepareUpdate(&$arFields, $arOptions = null)
	{
		$isNew = is_array($arOptions) && isset($arOptions['IS_NEW']) && $arOptions['IS_NEW'];
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID, 0, LANGUAGE_ID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			$typeID = $arUserField['USER_TYPE']['USER_TYPE_ID'];

			// Skip datetime - there is custom logic.
			if($typeID === 'datetime')
			{
				continue;
			}

			if($isNew && $arUserField['EDIT_IN_LIST'] === 'N' && isset($arUserField['SETTINGS']['DEFAULT_VALUE']) && !isset($arFields[$FIELD_NAME]))
			{
				$arFields[$FIELD_NAME] = $arUserField['SETTINGS']['DEFAULT_VALUE'];
			}

			if ($typeID == 'boolean' && isset($arFields[$FIELD_NAME]))
			{
				if ($arUserField['MULTIPLE'] == 'Y' && is_array($arFields[$FIELD_NAME]))
				{
					foreach ($arFields[$FIELD_NAME] as $k => $val)
					{
						if (!empty($val) && ($val == 'Y' || $val == 1 || $val === true))
							$arFields[$FIELD_NAME][$k] = 1;
						else
							$arFields[$FIELD_NAME][$k] = 0;
					}
				}
				else
				{
					if (!empty($arFields[$FIELD_NAME]) && ($arFields[$FIELD_NAME] == 'Y' || $arFields[$FIELD_NAME] == '1' || $arFields[$FIELD_NAME] === true))
						$arFields[$FIELD_NAME] = 1;
					else
						$arFields[$FIELD_NAME] = 0;
				}
			}
			elseif ($typeID == 'employee' && $arUserField['MULTIPLE'] == 'N')
			{
				if (is_array($arFields[$FIELD_NAME]))
					list(, $arFields[$FIELD_NAME]) = each($arFields[$FIELD_NAME]);
			}
			elseif ($typeID == 'crm' && isset($arFields[$FIELD_NAME]))
			{
				if (!is_array($arFields[$FIELD_NAME]))
					$arFields[$FIELD_NAME] = explode(';', $arFields[$FIELD_NAME]);
				else
				{
					$ar = Array();
					foreach ($arFields[$FIELD_NAME] as $value)
						foreach(explode(';', $value) as $val)
							if (!empty($val))
								$ar[$val] = $val;
					$arFields[$FIELD_NAME] = $ar;
				}

				if ($arUserField['MULTIPLE'] != 'Y')
				{
					if (isset($arFields[$FIELD_NAME][0]))
						$arFields[$FIELD_NAME] = $arFields[$FIELD_NAME][0];
					else
						$arFields[$FIELD_NAME] = '';
				}
			}
			elseif($typeID == 'file' && isset($arFields[$FIELD_NAME]))
			{
				// We have to prevent direct modification of file ID (issue #37940)
				if($arUserField['MULTIPLE'] != 'Y')
				{
					//Uploaded file array is allowed
					if(!(is_array($arFields[$FIELD_NAME]) && isset($arFields[$FIELD_NAME]['tmp_name'])))
					{
						unset($arFields[$FIELD_NAME]);
					}
				}
				elseif(is_array($arFields[$FIELD_NAME]))
				{
					foreach($arFields[$FIELD_NAME] as $k => $v)
					{
						if(!(is_array($v) && isset($v['tmp_name'])))
						{
							unset($arFields[$FIELD_NAME][$k]);
						}
					}
				}
				else
				{
					unset($arFields[$FIELD_NAME]);
				}
			}
		}
	}

	public function PrepareImport(&$arFields, $delimiter = '<br />')
	{
		foreach($arFields as $name => &$data)
		{
			$data = self::Internalize($name, $data, $delimiter);
		}
		unset($data);
	}

	public function NormalizeFields(&$arFields)
	{
		if (empty($arFields))
			return;

		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID);
		$bNorm = false;
		foreach($arFields as $k => $FIELD_NAME)
		{
			if (strpos($FIELD_NAME, 'UF_') === 0)
			{
				if (!isset($arUserFields[$FIELD_NAME]))
				{
					$bNorm = true;
					unset($arFields[$k]);
				}
			}
		}
		return $bNorm;
	}

	function ListPrepareFilter(&$arFilter)
	{
		$arUserFields = $this->cUFM->GetUserFields($this->sEntityID);
		foreach($arUserFields as $FIELD_NAME => $arUserField)
		{
			if (isset($arFilter[$FIELD_NAME]))
			{
				$value = $arFilter[$FIELD_NAME];
				unset($arFilter[$FIELD_NAME]);
			}
			else
				continue;

			if (
				$arUserField['SHOW_FILTER'] != 'N'
				&& $arUserField['USER_TYPE']['BASE_TYPE'] != 'file'
				&& (is_array($value) || strlen($value) > 0)
			)
			{
				if ($arUserField['SHOW_FILTER'] == 'I')
					$arFilter['='.$FIELD_NAME] = $value;
				else if($arUserField['SHOW_FILTER'] == 'E')
					$arFilter['%'.$FIELD_NAME] = $value;
				else
					$arFilter[$FIELD_NAME] = $value;
			}
		}
	}

	public function CheckFields($arFields, $ID = 0)
	{
		return $this->cUFM->CheckFields($this->sEntityID, $ID, $arFields);
	}

	public static function GetTaskBindingField()
	{
		$dbResult = CUserTypeEntity::GetList(
			array(),
			array(
			'ENTITY_ID' => 'TASKS_TASK',
				'FIELD_NAME' => 'UF_CRM_TASK',
			)
		);

		return $dbResult ? $dbResult->Fetch() : null;
	}
	public static function GetCalendarEventBindingField()
	{
		$dbResult = CUserTypeEntity::GetList(
			array(),
			array(
				'ENTITY_ID' => 'CALENDAR_EVENT',
				'FIELD_NAME' => 'UF_CRM_CAL_EVENT',
			)
		);

		return $dbResult ? $dbResult->Fetch() : null;
	}
}

?>
