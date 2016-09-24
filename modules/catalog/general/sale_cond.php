<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class CCatalogCondCtrlBasketProductFields extends CGlobalCondCtrlComplex
{
	public static function GetControlShow($arParams)
	{
		$arControls = static::GetControls();
		$arResult = array(
			'controlgroup' => true,
			'group' =>  false,
			'label' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CONTROLGROUP_LABEL'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'children' => array()
		);
		foreach ($arControls as &$arOneControl)
		{
			$arResult['children'][] = array(
				'controlId' => $arOneControl['ID'],
				'group' => false,
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX'],
					),
					static::GetLogicAtom($arOneControl['LOGIC']),
					static::GetValueAtom($arOneControl['JS_VALUE']),
				),
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strParentResult = '';
		$strResult = '';
		$parentResultValues = array();
		$resultValues = array();

		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arValues = static::Check($arOneCondition, $arOneCondition, $arControl, false);
			$boolError = ($arValues === false);
		}

		if (!$boolError)
		{
			$arLogic = static::SearchLogic($arValues['logic'], $arControl['LOGIC']);
			if (!isset($arLogic['OP'][$arControl['MULTIPLE']]) || empty($arLogic['OP'][$arControl['MULTIPLE']]))
			{
				$boolError = true;
			}
			else
			{
				$useParent = ($arControl['PARENT'] && isset($arLogic['PARENT']));
				$strParent = $arParams['BASKET_ROW'].'[\'CATALOG\'][\'PARENT_'.$arControl['FIELD'].'\']';
				$strField = $arParams['BASKET_ROW'].'[\'CATALOG\'][\''.$arControl['FIELD'].'\']';
				switch ($arControl['FIELD_TYPE'])
				{
					case 'int':
					case 'double':
						if (is_array($arValues['value']))
						{
							if (!isset($arLogic['MULTI_SEP']))
							{
								$boolError = true;
							}
							else
							{
								foreach ($arValues['value'] as &$value)
								{
									if ($useParent)
										$parentResultValues[] = str_replace(
											array('#FIELD#', '#VALUE#'),
											array($strParent, $value),
											$arLogic['OP'][$arControl['MULTIPLE']]
										);
									$resultValues[] = str_replace(
										array('#FIELD#', '#VALUE#'),
										array($strField, $value),
										$arLogic['OP'][$arControl['MULTIPLE']]
									);
								}
								unset($value);
								if ($useParent)
									$strParentResult = '('.implode($arLogic['MULTI_SEP'], $parentResultValues).')';
								$strResult = '('.implode($arLogic['MULTI_SEP'], $resultValues).')';
								unset($resultValues, $parentResultValues);
							}
						}
						else
						{
							if ($useParent)
								$strParentResult = str_replace(
									array('#FIELD#', '#VALUE#'),
									array($strParent, $arValues['value']),
									$arLogic['OP'][$arControl['MULTIPLE']]
								);
							$strResult = str_replace(
								array('#FIELD#', '#VALUE#'),
								array($strField, $arValues['value']),
								$arLogic['OP'][$arControl['MULTIPLE']]
							);
						}
						break;
					case 'char':
					case 'string':
					case 'text':
						if (is_array($arValues['value']))
						{
							$boolError = true;
						}
						else
						{
							if ($useParent)
								$strParentResult = str_replace(
									array('#FIELD#', '#VALUE#'),
									array($strParent, '"'.EscapePHPString($arValues['value']).'"'),
									$arLogic['OP'][$arControl['MULTIPLE']]
								);
							$strResult = str_replace(
								array('#FIELD#', '#VALUE#'),
								array($strField, '"'.EscapePHPString($arValues['value']).'"'),
								$arLogic['OP'][$arControl['MULTIPLE']]
							);
						}
						break;
					case 'date':
					case 'datetime':
						if (is_array($arValues['value']))
						{
							$boolError = true;
						}
						else
						{
							if ($useParent)
								$strParentResult = str_replace(
									array('#FIELD#', '#VALUE#'),
									array($strParent, $arValues['value']),
									$arLogic['OP'][$arControl['MULTIPLE']]
								);
							$strResult = str_replace(
								array('#FIELD#', '#VALUE#'),
								array($strField, $arValues['value']),
								$arLogic['OP'][$arControl['MULTIPLE']]
							);
						}
						break;
				}
				$existBasket = 'isset('.$arParams['BASKET_ROW'].'[\'CATALOG\'])';
				$strResult = 'isset('.$strField.') && '.$strResult;
				if ($arControl['PARENT'])
				{
					$strResult = '(isset('.$strParent.') ? (('.$strResult.')'.$arLogic['PARENT'].$strParentResult.') : ('.$strResult.'))';
				}
				$strResult = '('.$existBasket.' && '.$strResult.')';
			}
		}

		return (!$boolError ? $strResult : false);
	}

	public static function GetShowIn($arControls)
	{
		if (!empty($arControls))
		{
			$strDisableKey = CSaleCondCtrlGroup::GetControlID();
			$arControlsMap = array_fill_keys($arControls, true);
			if (array_key_exists($strDisableKey, $arControlsMap))
				unset($arControlsMap[$strDisableKey]);
			$arControls = array_keys($arControlsMap);
		}
		return $arControls;
	}

	/**
	 * @param bool|string $strControlID
	 * @return bool|array
	 */
	public static function GetControls($strControlID = false)
	{
		$vatList = array();
		$vatIterator = Catalog\VatTable::getList(array('select' => array('ID', 'NAME', 'SORT'), 'order' => array('SORT' => 'ASC')));
		while ($vat = $vatIterator->fetch())
		{
			$vat['ID'] = (int)$vat['ID'];
			$vatList[$vat['ID']] = $vat['NAME'];
		}
		unset($vat, $vatIterator);

		$arControlList = array(
			'CondIBElement' => array(
				'ID' => 'CondIBElement',
				'FIELD' => 'ID',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ELEMENT_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ELEMENT_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'multiDialog',
					'popup_url' =>  '/bitrix/admin/cat_product_search_dialog.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
						'caller' => 'discount_rules',
						'allow_select_parent' => 'Y'
					),
					'param_id' => 'n',
					'show_value' => 'Y'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'element'
				)
			),
			'CondIBIBlock' => array(
				'ID' => 'CondIBIBlock',
				'FIELD' => 'IBLOCK_ID',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_IBLOCK_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_IBLOCK_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'popup',
					'popup_url' =>  '/bitrix/admin/cat_iblock_search.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
						'discount' => 'Y'
					),
					'param_id' => 'n',
					'show_value' => 'Y',
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'iblock'
				),
			),
			'CondIBSection' => array(
				'ID' => 'CondIBSection',
				'PARENT' => false,
				'FIELD' => 'SECTION_ID',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SECTION_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SECTION_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'popup',
					'popup_url' =>  '/bitrix/admin/cat_section_search.php',
					'popup_params' => array(
						'lang' => LANGUAGE_ID,
						'discount' => 'Y'
					),
					'param_id' => 'n',
					'show_value' => 'Y',
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'section'
				),
			),
			'CondIBCode' => array(
				'ID' => 'CondIBCode',
				'FIELD' => 'CODE',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CODE_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CODE_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondIBXmlID' => array(
				'ID' => 'CondIBXmlID',
				'FIELD' => 'XML_ID',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_XML_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_XML_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondIBName' => array(
				'ID' => 'CondIBName',
				'FIELD' => 'NAME',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_NAME_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_NAME_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondIBDateActiveFrom' => array(
				'ID' => 'CondIBDateActiveFrom',
				'FIELD' => 'DATE_ACTIVE_FROM',
				'FIELD_TYPE' => 'datetime',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DATE_ACTIVE_FROM_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DATE_ACTIVE_FROM_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'datetime',
					'format' => 'datetime'
				),
				'PHP_VALUE' => ''
			),
			'CondIBDateActiveTo' => array(
				'ID' => 'CondIBDateActiveTo',
				'FIELD' => 'DATE_ACTIVE_TO',
				'FIELD_TYPE' => 'datetime',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DATE_ACTIVE_TO_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DATE_ACTIVE_TO_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'datetime',
					'format' => 'datetime'
				),
				'PHP_VALUE' => ''
			),
			'CondIBSort' => array(
				'ID' => 'CondIBSort',
				'FIELD' => 'SORT',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SORT_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SORT_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondIBPreviewText' => array(
				'ID' => 'CondIBPreviewText',
				'FIELD' => 'PREVIEW_TEXT',
				'FIELD_TYPE' => 'text',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_PREVIEW_TEXT_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_PREVIEW_TEXT_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondIBDetailText' => array(
				'ID' => 'CondIBDetailText',
				'FIELD' => 'DETAIL_TEXT',
				'FIELD_TYPE' => 'text',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DETAIL_TEXT_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DETAIL_TEXT_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondIBDateCreate' => array(
				'ID' => 'CondIBDateCreate',
				'FIELD' => 'DATE_CREATE',
				'FIELD_TYPE' => 'datetime',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DATE_CREATE_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_DATE_CREATE_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'datetime',
					'format' => 'datetime'
				),
				'PHP_VALUE' => ''
			),
			'CondIBCreatedBy' => array(
				'ID' => 'CondIBCreatedBy',
				'FIELD' => 'CREATED_BY',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CREATED_BY_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_CREATED_BY_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'user'
				)
			),
			'CondIBTimestampX' => array(
				'ID' => 'CondIBTimestampX',
				'FIELD' => 'TIMESTAMP_X',
				'FIELD_TYPE' => 'datetime',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_TIMESTAMP_X_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_TIMESTAMP_X_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'datetime',
					'format' => 'datetime'
				),
				'PHP_VALUE' => ''
			),
			'CondIBModifiedBy' => array(
				'ID' => 'CondIBModifiedBy',
				'FIELD' => 'MODIFIED_BY',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_MODIFIED_BY_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_MODIFIED_BY_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'user'
				)
			),
			'CondIBTags' => array(
				'ID' => 'CondIBTags',
				'FIELD' => 'TAGS',
				'FIELD_TYPE' => 'string',
				'FIELD_LENGTH' => 255,
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_TAGS_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_TAGS_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
				'PHP_VALUE' => '',
			),
			'CondCatQuantity' => array(
				'ID' => 'CondCatQuantity',
				'PARENT' => false,
				'MODULE_ENTITY' => 'catalog',
				'ENTITY' => 'PRODUCT',
				'FIELD' => 'CATALOG_QUANTITY',
				'FIELD_TABLE' => 'QUANTITY',
				'FIELD_TYPE' => 'double',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_QUANTITY_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_QUANTITY_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'input',
				),
			),
			'CondCatWeight' => array(
				'ID' => 'CondCatWeight',
				'PARENT' => false,
				'MODULE_ENTITY' => 'catalog',
				'ENTITY' => 'PRODUCT',
				'FIELD' => 'CATALOG_WEIGHT',
				'FIELD_TABLE' => 'WEIGHT',
				'FIELD_TYPE' => 'double',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_WEIGHT_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_WEIGHT_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS)),
				'JS_VALUE' => array(
					'type' => 'input'
				),
				'PHP_VALUE' => ''
			),
			'CondCatVatID' => array(
				'ID' => 'CondCatVatID',
				'PARENT' => false,
				'MODULE_ENTITY' => 'catalog',
				'ENTITY' => 'PRODUCT',
				'FIELD' => 'CATALOG_VAT_ID',
				'FIELD_TABLE' => 'VAT_ID',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_VAT_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_VAT_ID_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'select',
					'values' => $vatList
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
				)
			),
			'CondCatVatIncluded' => array(
				'ID' => 'CondCatVatIncluded',
				'PARENT' => false,
				'MODULE_ENTITY' => 'catalog',
				'ENTITY' => 'PRODUCT',
				'FIELD' => 'CATALOG_VAT_INCLUDED',
				'FIELD_TABLE' => 'VAT_INCLUDED',
				'FIELD_TYPE' => 'char',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_VAT_INCLUDED_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_VAT_INCLUDED_PREFIX'),
				'LOGIC' => static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ)),
				'JS_VALUE' => array(
					'type' => 'select',
					'values' => array(
						'Y' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_VAT_INCLUDED_VALUE_YES'),
						'N' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_CATALOG_VAT_INCLUDED_VALUE_NO')
					)
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
				)
			)
		);
		if (empty($vatList))
		{
			unset($arControlList['CondCatVatID']);
			unset($arControlList['CondCatVatIncluded']);
		}
		foreach ($arControlList as &$control)
		{
			if (!isset($control['PARENT']))
				$control['PARENT'] = true;
			$control['EXIST_HANDLER'] = 'Y';
			$control['MODULE_ID'] = 'catalog';
			if (!isset($control['MODULE_ENTITY']))
				$control['MODULE_ENTITY'] = 'iblock';
			if (!isset($control['ENTITY']))
				$control['ENTITY'] = 'ELEMENT';
			if (!isset($control['FIELD_TABLE']))
				$control['FIELD_TABLE'] = false;
			$control['MULTIPLE'] = 'N';
			$control['GROUP'] = 'N';
		}
		unset($control);
		$arControlList['CondIBSection']['MULTIPLE'] = 'Y';

		if ($strControlID === false)
		{
			return $arControlList;
		}
		elseif (isset($arControlList[$strControlID]))
		{
			return $arControlList[$strControlID];
		}
		else
		{
			return false;
		}
	}
}

class CCatalogCondCtrlBasketProductProps extends CGlobalCondCtrlComplex
{
	public static function GetClassName()
	{
		return __CLASS__;
	}

	public static function GetControlShow($arParams)
	{
		$arControls = static::GetControls();
		$arResult = array();
		$intCount = -1;
		foreach ($arControls as &$arOneControl)
		{
			if (isset($arOneControl['SEP']) && 'Y' == $arOneControl['SEP'])
			{
				$intCount++;
				$arResult[$intCount] = array(
					'controlgroup' => true,
					'group' =>  false,
					'label' => $arOneControl['SEP_LABEL'],
					'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
					'children' => array()
				);
			}

			$arResult[$intCount]['children'][] = array(
				'controlId' => $arOneControl['ID'],
				'group' => false,
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX'],
					),
					static::GetLogicAtom($arOneControl['LOGIC']),
					static::GetValueAtom($arOneControl['JS_VALUE']),
				),
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);

		return $arResult;
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$strResult = '';
		$resultValues = array();
		$arValues = false;

		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arValues = static::Check($arOneCondition, $arOneCondition, $arControl, false);
			$boolError = ($arValues === false);
		}

		if (!$boolError)
		{
			$arLogic = static::SearchLogic($arValues['logic'], $arControl['LOGIC']);
			if (!isset($arLogic['OP'][$arControl['MULTIPLE']]) || empty($arLogic['OP'][$arControl['MULTIPLE']]))
			{
				$boolError = true;
			}
			else
			{
				$strField = $arParams['BASKET_ROW'].'[\'CATALOG\'][\''.$arControl['FIELD'].'\']';
				switch ($arControl['FIELD_TYPE'])
				{
					case 'int':
					case 'double':
						if (is_array($arValues['value']))
						{
							if (!isset($arLogic['MULTI_SEP']))
							{
								$boolError = true;
							}
							else
							{
								foreach ($arValues['value'] as &$value)
								{
									$resultValues[] = str_replace(
										array('#FIELD#', '#VALUE#'),
										array($strField, $value),
										$arLogic['OP'][$arControl['MULTIPLE']]
									);
								}
								unset($value);
								$strResult = '('.implode($arLogic['MULTI_SEP'], $resultValues).')';
								unset($resultValues);
							}
						}
						else
						{
							$strResult = str_replace(
								array('#FIELD#', '#VALUE#'),
								array($strField, $arValues['value']),
								$arLogic['OP'][$arControl['MULTIPLE']]
							);
						}
						break;
					case 'char':
					case 'string':
					case 'text':
						if (is_array($arValues['value']))
						{
							$boolError = true;
						}
						else
						{
							$strResult = str_replace(
								array('#FIELD#', '#VALUE#'),
								array($strField, '"'.EscapePHPString($arValues['value']).'"'),
								$arLogic['OP'][$arControl['MULTIPLE']]
							);
						}
						break;
					case 'date':
					case 'datetime':
						if (is_array($arValues['value']))
						{
							$boolError = true;
						}
						else
						{
							$strResult = str_replace(
								array('#FIELD#', '#VALUE#'),
								array($strField, $arValues['value']),
								$arLogic['OP'][$arControl['MULTIPLE']]
							);
						}
						break;
				}
				$strResult = '(isset('.$arParams['BASKET_ROW'].'[\'CATALOG\']) && isset('.$strField.') && '.$strResult.')';
			}
		}

		return (!$boolError ? $strResult : false);
	}

	public static function GetShowIn($arControls)
	{
		if (!empty($arControls))
		{
			$strDisableKey = CSaleCondCtrlGroup::GetControlID();
			$arControlsMap = array_fill_keys($arControls, true);
			if (array_key_exists($strDisableKey, $arControlsMap))
				unset($arControlsMap[$strDisableKey]);
			$arControls = array_keys($arControlsMap);
		}
		return $arControls;
	}

	/**
	 * @param bool|string $strControlID
	 * @return bool|array
	 */
	public static function GetControls($strControlID = false)
	{
		$arControlList = array();
		$arIBlockList = array();
		$rsIBlocks = CCatalog::GetList(array(), array(), false, false, array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID'));
		while ($arIBlock = $rsIBlocks->Fetch())
		{
			$arIBlock['IBLOCK_ID'] = (int)$arIBlock['IBLOCK_ID'];
			$arIBlock['PRODUCT_IBLOCK_ID'] = (int)$arIBlock['PRODUCT_IBLOCK_ID'];
			if ($arIBlock['IBLOCK_ID'] > 0)
				$arIBlockList[$arIBlock['IBLOCK_ID']] = true;
			if ($arIBlock['PRODUCT_IBLOCK_ID'] > 0)
				$arIBlockList[$arIBlock['PRODUCT_IBLOCK_ID']] = true;
		}
		unset($arIBlock, $rsIBlocks);
		if (!empty($arIBlockList))
		{
			$arIBlockList = array_keys($arIBlockList);
			sort($arIBlockList);
			foreach ($arIBlockList as &$intIBlockID)
			{
				$strName = CIBlock::GetArrayByID($intIBlockID, 'NAME');
				if (false !== $strName)
				{
					$boolSep = true;
					$rsProps = CIBlockProperty::GetList(array('SORT' => 'ASC', 'NAME' => 'ASC'), array('IBLOCK_ID' => $intIBlockID));
					while ($arProp = $rsProps->Fetch())
					{
						if ('CML2_LINK' == $arProp['XML_ID'] || 'F' == $arProp['PROPERTY_TYPE'])
							continue;
						if ('L' == $arProp['PROPERTY_TYPE'])
						{
							$arProp['VALUES'] = array();
							$rsPropEnums = CIBlockPropertyEnum::GetList(array('DEF' => 'DESC', 'SORT' => 'ASC'), array('PROPERTY_ID' => $arProp['ID']));
							while ($arPropEnum = $rsPropEnums->Fetch())
							{
								$arProp['VALUES'][] = $arPropEnum;
							}
							if (empty($arProp['VALUES']))
								continue;
						}

						$strFieldType = '';
						$arLogic = array();
						$arValue = array();
						$arPhpValue = '';

						$boolUserType = false;
						if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE']))
						{
							switch ($arProp['USER_TYPE'])
							{
								case 'DateTime':
									$strFieldType = 'datetime';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array(
										'type' => 'datetime',
										'format' => 'datetime'
									);
									$boolUserType = true;
									break;
								case 'Date':
									$strFieldType = 'date';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array(
										'type' => 'datetime',
										'format' => 'date'
									);
									$boolUserType = true;
									break;
								default:
									$boolUserType = false;
									break;
							}
						}

						if (!$boolUserType)
						{
							switch ($arProp['PROPERTY_TYPE'])
							{
								case 'N':
									$strFieldType = 'double';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array('type' => 'input');
									break;
								case 'S':
									$strFieldType = 'text';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT));
									$arValue = array('type' => 'input');
									break;
								case 'L':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'select',
										'values' => array(),
									);
									foreach ($arProp['VALUES'] as &$arOnePropValue)
									{
										$arValue['values'][$arOnePropValue['ID']] = $arOnePropValue['VALUE'];
									}
									if (isset($arOnePropValue))
										unset($arOnePropValue);
									$arPhpValue = array('VALIDATE' => 'list');
									break;
								case 'E':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'popup',
										'popup_url' =>  '/bitrix/admin/iblock_element_search.php',
										'popup_params' => array(
											'lang' => LANGUAGE_ID,
											'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
											'discount' => 'Y'
										),
										'param_id' => 'n',
									);
									$arPhpValue = array('VALIDATE' => 'element');
									break;
								case 'G':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'popup',
										'popup_url' =>  '/bitrix/admin/cat_section_search.php',
										'popup_params' => array(
											'lang' => LANGUAGE_ID,
											'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
											'discount' => 'Y'
										),
										'param_id' => 'n',
									);
									$arPhpValue = array('VALIDATE' => 'section');
									break;
							}
						}
						$arControlList['CondIBProp:'.$intIBlockID.':'.$arProp['ID']] = array(
							'ID' => 'CondIBProp:'.$intIBlockID.':'.$arProp['ID'],
							'PARENT' => false,
							'EXIST_HANDLER' => 'Y',
							'MODULE_ID' => 'catalog',
							'MODULE_ENTITY' => 'iblock',
							'ENTITY' => 'ELEMENT_PROPERTY',
							'IBLOCK_ID' => $intIBlockID,
							'FIELD' => 'PROPERTY_'.$arProp['ID'].'_VALUE',
							'FIELD_TABLE' => $intIBlockID.':'.$arProp['ID'],
							'FIELD_TYPE' => $strFieldType,
							'MULTIPLE' => 'Y',
							'GROUP' => 'N',
							'SEP' => ($boolSep ? 'Y' : 'N'),
							'SEP_LABEL' => ($boolSep ? str_replace(array('#ID#', '#NAME#'), array($intIBlockID, $strName), Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_PROP_LABEL')) : ''),
							'LABEL' => $arProp['NAME'],
							'PREFIX' => str_replace(array('#NAME#', '#IBLOCK_ID#', '#IBLOCK_NAME#'), array($arProp['NAME'], $intIBlockID, $strName), Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ONE_PROP_PREFIX')),
							'LOGIC' => $arLogic,
							'JS_VALUE' => $arValue,
							'PHP_VALUE' => $arPhpValue,
						);

						$boolSep = false;
					}
				}
			}
			unset($intIBlockID);
		}

		if ($strControlID === false)
		{
			return $arControlList;
		}
		elseif (isset($arControlList[$strControlID]))
		{
			return $arControlList[$strControlID];
		}
		else
		{
			return false;
		}
	}
}

class CCatalogCondCtrlCatalogSettings extends CGlobalCondCtrlComplex
{
	public static function GetControlShow($arParams)
	{
		$controlList = static::GetControls();
		$result = array(
			'controlgroup' => true,
			'group' =>  false,
			'label' => Loc::getMessage('BX_COND_CATALOG_SETTINGS_CONTROLGROUP_LABEL'),
			'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
			'children' => array()
		);
		foreach ($controlList as &$control)
		{
			$jsControl = array(
				'controlId' => $control['ID'],
				'group' => ($control['GROUP'] == 'Y'),
				'label' => $control['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array()
			);
			if ($control['ID'] == 'CondCatalogRenewal')
			{
				$jsControl['control'] = array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $control['PREFIX'],
					),
					static::GetValueAtom($control['JS_VALUE'])
				);
			}
			else
			{
				$jsControl['control'] = array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $control['PREFIX'],
					),
					static::GetLogicAtom($control['LOGIC']),
					static::GetValueAtom($control['JS_VALUE'])
				);
			}
			$result['children'][] = $jsControl;
		}
		unset($jsControl, $control, $controlList);
		return $result;
	}

	public static function GetConditionShow($arParams)
	{
		if (!isset($arParams['ID']))
			return false;

		if ($arParams['ID'] == 'CondCatalogRenewal')
		{
			$control = static::GetControls($arParams['ID']);
			if ($control === false)
				return false;

			return array(
				'id' => $arParams['COND_NUM'],
				'controlId' => $control['ID'],
				'values' => array('value' => 'Y')
			);
		}
		else
		{
			return parent::GetConditionShow($arParams);
		}
	}

	public static function Parse($arOneCondition)
	{
		if (!isset($arOneCondition['controlId']))
			return false;
		if ($arOneCondition['controlId'] == 'CondCatalogRenewal')
		{
			$control = static::GetControls($arOneCondition['controlId']);
			if ($control === false)
				return false;
			return array('value' => 'Y');
		}
		else
		{
			return parent::Parse($arOneCondition);
		}
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		return '(1 = 1)';
	}

	public static function GetShowIn($arControls)
	{
		return array(CSaleCondCtrlGroup::GetControlID());
	}

	public static function GetControlID()
	{
		return array(
			'CondCatalogPriceType',
			'CondCatalogRenewal'
		);
	}
	/**
	 * @param bool|string $strControlID
	 * @return bool|array
	 */
	public static function GetControls($strControlID = false)
	{
		$priceTypeList = array(
			-1 => Loc::getMessage('BX_COND_CATALOG_PRICE_TYPE_ALL')
		);
		$priceTypeIterator = Catalog\GroupTable::getList(array(
			'select' => array('ID', 'NAME', 'SORT', 'LANG_NAME' => 'CURRENT_LANG.NAME'),
			'order' => array('SORT' => 'ASC', 'ID' => 'ASC')
		));
		while ($priceType = $priceTypeIterator->fetch())
		{
			$priceType['ID'] = (int)$priceType['ID'];
			$priceType['LANG_NAME'] = (string)$priceType['LANG_NAME'];
			$priceTypeList[$priceType['ID']] = $priceType['NAME'].($priceType['LANG_NAME'] != '' ? ' ('.$priceType['LANG_NAME'].')' : '');
		}
		unset($priceType, $priceTypeIterator);

		$priceTypeLogic = array(
			BT_COND_LOGIC_EQ => Loc::getMessage('BX_COND_CATALOG_PRICE_TYPE_LOGIC_EQ_LABEL'),
			BT_COND_LOGIC_NOT_EQ => Loc::getMessage('BX_COND_CATALOG_PRICE_TYPE_LOGIC_NOT_EQ_LABEL'),
		);

		$controlList = array(
			'CondCatalogPriceType' => array(
				'ID' => 'CondCatalogPriceType',
				'PARENT' => false,
				'EXECUTE_MODULE' => 'catalog',
				'EXIST_HANDLER' => 'Y',
				'MODULE_ID' => 'catalog',
				'MODULE_ENTITY' => 'catalog',
				'ENTITY' => 'PRICE',
				'FIELD' => 'CATALOG_GROUP_ID',
				'FIELD_TABLE' => 'CATALOG_GROUP_ID',
				'FIELD_TYPE' => 'int',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => Loc::getMessage('BX_COND_CATALOG_PRICE_TYPE_LABEL'),
				'PREFIX' => Loc::getMessage('BX_COND_CATALOG_PRICE_TYPE_PREFIX'),
				'LOGIC' => static::GetLogicEx(array_keys($priceTypeLogic), $priceTypeLogic),
				'JS_VALUE' => array(
					'type' => 'select',
					'values' => $priceTypeList,
					'multiple' => 'Y',
					'show_value' => 'Y',
				),
				'PHP_VALUE' => array(
					'VALIDATE' => 'list'
				)
			),
			'CondCatalogRenewal' => array(
				'ID' => 'CondCatalogRenewal',
				'PARENT' => false,
				'EXECUTE_MODULE' => 'catalog',
				'EXIST_HANDLER' => 'Y',
				'MODULE_ID' => 'catalog',
				'MODULE_ENTITY' => 'catalog',
				'ENTITY' => 'DISCOUNT',
				'FIELD' => 'RENEWAL',
				'FIELD_TABLE' => 'RENEWAL',
				'FIELD_TYPE' => 'char',
				'MULTIPLE' => 'N',
				'GROUP' => 'N',
				'LABEL' => Loc::getMessage('BX_COND_CATALOG_RENEWAL_LABEL'),
				'PREFIX' => Loc::getMessage('BX_COND_CATALOG_RENEWAL_PREFIX'),
				'JS_VALUE' => array(
					'type' => 'hidden',
					'value' => 'Y',
				),
			)
		);
		if ($strControlID === false)
		{
			return $controlList;
		}
		elseif (isset($controlList[$strControlID]))
		{
			return $controlList[$strControlID];
		}
		else
		{
			return false;
		}
	}
}