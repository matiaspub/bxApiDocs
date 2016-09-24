<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);

class CCatalogActionCtrlBasketProductFields extends CGlobalCondCtrlComplex
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
					static::GetValueAtom($arOneControl['JS_VALUE'])
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
				if ($useParent)
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
		$key = array_search(CSaleActionCtrlAction::GetControlID(), $arControls);
		if (false !== $key)
		{
			unset($arControls[$key]);
			$arControls = array_values($arControls);
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

class CCatalogActionCtrlBasketProductProps extends CGlobalCondCtrlComplex
{
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
			$arLogic = static::GetLogicAtom($arOneControl['LOGIC']);
			$arValue = static::GetValueAtom($arOneControl['JS_VALUE']);

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
					$arLogic,
					$arValue,
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
		$key = array_search(CSaleActionCtrlAction::GetControlID(), $arControls);
		if (false !== $key)
		{
			unset($arControls[$key]);
			$arControls = array_values($arControls);
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

class CCatalogGifterProduct extends CGlobalCondCtrlAtoms
{
	public static function GetShowIn($params)
	{
		return array();
	}

	public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false)
	{
		$mxResult = '';
		if (is_string($arControl))
		{
			$arControl = static::GetControls($arControl);
		}
		$boolError = !is_array($arControl);

		if (!$boolError)
		{
			$arControl['ATOMS'] = static::GetAtomsEx($arControl['ID'], true);
			$arValues = static::CheckAtoms($arOneCondition, $arOneCondition, $arControl, true);
			$boolError = ($arValues === false);
		}

		if (!$boolError)
		{
			$stringDataArray = 'array(' . implode(',', array_map('intval', (array)$arOneCondition['Value'])) . ')';
			$type = $arOneCondition['Type'];

			$mxResult = static::GetClassName() . "::GenerateApplyCallableFilter('{$arControl['ID']}', {$stringDataArray}, '{$type}')";
		}

		return $mxResult;
	}

	public static function GetControls($strControlID = false)
	{
		$arAtoms = static::GetAtomsEx();
		$arControlList = array(
			'GifterCondIBElement' => array(
				'ID' => 'GifterCondIBElement',
				'PARENT' => true,
				'FIELD' => 'ID',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ELEMENT_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ELEMENT_ID_LABEL'),
				'ATOMS' => $arAtoms['GifterCondIBElement'],
			),
			'GifterCondIBSection' => array(
				'ID' => 'GifterCondIBSection',
				'PARENT' => false,
				'FIELD' => 'SECTION_ID',
				'FIELD_TYPE' => 'int',
				'LABEL' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SECTION_ID_LABEL'),
				'PREFIX' => Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_SECTION_ID_LABEL'),
				'ATOMS' => $arAtoms['GifterCondIBSection'],
			),
		);

		foreach ($arControlList as &$control)
		{
			$control['EXIST_HANDLER'] = 'Y';
			$control['MODULE_ID'] = 'catalog';
			$control['MODULE_ENTITY'] = 'iblock';
			$control['ENTITY'] = 'ELEMENT';
		}
		unset($control);

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

	public static function GetAtomsEx($strControlID = false, $boolEx = false)
	{
		$boolEx = (true === $boolEx ? true : false);
		$atomList = array(
			'GifterCondIBElement' => array(
				'Type' => array(
					'JS' => array(
						'id' => 'Type',
						'name' => 'logic',
						'type' => 'select',
						'values' => array(
							CSaleDiscountActionApply::GIFT_SELECT_TYPE_ONE => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_ONE'),
							CSaleDiscountActionApply::GIFT_SELECT_TYPE_ALL => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_ALL'),
						),
						'defaultText' => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_DEF'),
						'defaultValue' => CSaleDiscountActionApply::GIFT_SELECT_TYPE_ONE,
						'first_option' => '...'
					),
					'ATOM' => array(
						'ID' => 'Type',
						'FIELD_TYPE' => 'string',
						'FIELD_LENGTH' => 255,
						'MULTIPLE' => 'N',
						'VALIDATE' => 'list'
					)
				),
				'Value' => array(
					'JS' => array(
						'id' => 'Value',
						'name' => 'Value',
						'type' => 'multiDialog',
						'popup_url' => '/bitrix/admin/cat_product_search_dialog.php',
						'popup_params' => array(
							'lang' => LANGUAGE_ID,
							'caller' => 'discount_rules',
							'allow_select_parent' => 'Y',
						),
						'param_id' => 'n',
						'show_value' => 'Y'
					),
					'ATOM' => array(
						'ID' => 'Value',
						'FIELD_TYPE' => 'int',
						'MULTIPLE' => 'Y',
						'VALIDATE' => 'element'
					)
				)
			),
			'GifterCondIBSection' => array(
				'Type' => array(
					'JS' => array(
						'id' => 'Type',
						'name' => 'logic',
						'type' => 'select',
						'values' => array(
							CSaleDiscountActionApply::GIFT_SELECT_TYPE_ONE => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_ONE'),
							CSaleDiscountActionApply::GIFT_SELECT_TYPE_ALL => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_ALL'),
						),
						'defaultText' => Loc::getMessage('BT_SALE_ACT_GIFT_SELECT_TYPE_SELECT_DEF'),
						'defaultValue' => CSaleDiscountActionApply::GIFT_SELECT_TYPE_ONE,
						'first_option' => '...'
					),
					'ATOM' => array(
						'ID' => 'Type',
						'FIELD_TYPE' => 'string',
						'FIELD_LENGTH' => 255,
						'MULTIPLE' => 'N',
						'VALIDATE' => 'list'
					)
				),
				'Value' => array(
					'JS' => array(
						'id' => 'Value',
						'name' => 'Value',
						'type' => 'popup',
						'popup_url' => '/bitrix/admin/cat_section_search.php',
						'popup_params' => array(
							'lang' => LANGUAGE_ID,
							'discount' => 'Y'
						),
						'param_id' => 'n',
						'show_value' => 'Y',
					),
					'ATOM' => array(
						'ID' => 'Value',
						'FIELD_TYPE' => 'int',
						'MULTIPLE' => 'N',
						'VALIDATE' => 'section'
					)
				)
			)
		);

		if(!$boolEx)
		{
			foreach($atomList as &$arOneAtom)
			{
				foreach ($arOneAtom as &$one)
				{
					$one = $one['JS'];
				}
				unset($one);
			}
			if(isset($arOneAtom))
			{
				unset($arOneAtom);
			}
		}
		if ($strControlID === false)
		{
			return $atomList;
		}
		elseif (isset($atomList[$strControlID]))
		{
			return $atomList[$strControlID];
		}

		return false;
	}

	public static function GenerateApplyCallableFilter($controlId, array $gifts, $type)
	{
		$gifts = array_combine($gifts, $gifts);
		return function(&$row) use($controlId, $gifts, $type)
		{
			static $isApplied = false;
			if($isApplied && $type === CSaleDiscountActionApply::GIFT_SELECT_TYPE_ONE)
			{
				return false;
			}
			$right = false;
			switch($controlId)
			{
				case 'GifterCondIBElement':
					$right =
						(
							(isset($row['CATALOG']['PARENT_ID']) && isset($gifts[$row['CATALOG']['PARENT_ID']])) ||
							(isset($row['CATALOG']['ID']) && isset($gifts[$row['CATALOG']['ID']]))
						) &&
						isset($row['QUANTITY']) && $row['QUANTITY'] == 1
						&& isset($row['PRICE']) && $row['PRICE'] > 0
					;
					break;

				case 'GifterCondIBSection':
					$right =
						(
							isset($row['CATALOG']['SECTION_ID']) && array_intersect($gifts, (array)$row['CATALOG']['SECTION_ID'])
						) &&
						isset($row['QUANTITY']) && $row['QUANTITY'] == 1
						&& isset($row['PRICE']) && $row['PRICE'] > 0
					;

					break;
			}

			if($right)
			{
				$isApplied = true;
			}

			return $right;
		};
	}

	public static function ProvideGiftData(array $actionData)
	{
		$type = $actionData['DATA']['Type'];
		$values = (array)$actionData['DATA']['Value'];

		switch($actionData['CLASS_ID'])
		{
			case 'GifterCondIBElement':
				return array(
					'Type' => $type,
					'GiftValue' => $values,
				);
			case 'GifterCondIBSection':
				return array(
					'Type' => $type,
					'GiftValue' => static::GetProductIdsBySection(array_pop($values)),
				);
		}

		return array();
	}

	protected static function GetProductIdsBySection($sectionId)
	{
		if(!\Bitrix\Main\Loader::includeModule('iblock'))
		{
			return array();
		}
		$ids = array();
		$query = CIBlockElement::getList(array(), array(
			'ACTIVE_DATE' => 'Y',
			'SECTION_ID' => $sectionId,
			'CHECK_PERMISSIONS' => 'Y',
			'MIN_PERMISSION' => 'R',
			'ACTIVE' => 'Y',
		), false, false, array('ID'));

		while($row = $query->fetch())
		{
			$ids[] = $row['ID'];
		}

		return $ids;
	}

	public static function ExtendProductIds(array $giftedProductIds)
	{
		$products = CCatalogSku::getProductList($giftedProductIds);
		if (empty($products))
			return $giftedProductIds;

		foreach($products as $product)
			$giftedProductIds[] = $product['ID'];
		unset($product);

		return $giftedProductIds;
	}
}