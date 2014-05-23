<?
use Bitrix\Main\Localization\Loc as Loc;
Loc::loadMessages(__FILE__);

define ('BT_UT_AUTOCOMPLETE_CODE','EAutocomplete');
define ('BT_UT_AUTOCOMPLETE_REP_SYM_OTHER','other');

class CIBlockPropertyElementAutoComplete
{
	public function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "E",
			"USER_TYPE" => BT_UT_AUTOCOMPLETE_CODE,
			"DESCRIPTION" => Loc::getMessage('BT_UT_EAUTOCOMPLETE_DESCR'),
			"GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array(__CLASS__,'GetPropertyFieldHtmlMulty'),
			"GetAdminListViewHTML" => array(__CLASS__,"GetAdminListViewHTML"),
			"GetPublicViewHTML" => array(__CLASS__, "GetPublicViewHTML"),
			"GetAdminFilterHTML" => array(__CLASS__,'GetAdminFilterHTML'),
			"GetSettingsHTML" => array(__CLASS__,'GetSettingsHTML'),
			"PrepareSettings" => array(__CLASS__,'PrepareSettings'),
			"AddFilterFields" => array(__CLASS__,'AddFilterFields'),
		);
	}

	protected function GetLinkElement($intElementID, $intIBlockID)
	{
		static $cache = array();

		$intIBlockID = intval($intIBlockID);
		if (0 >= $intIBlockID)
			$intIBlockID = 0;
		$intElementID = intval($intElementID);
		if (0 >= $intElementID)
			return false;
		if (!isset($cache[$intElementID]))
		{
			$arFilter = array();
			if (0 < $intIBlockID)
				$arFilter['IBLOCK_ID'] = $intIBlockID;
			$arFilter['ID'] = $intElementID;
			$arFilter['SHOW_HISTORY'] = 'Y';
			$rsElements = CIBlockElement::GetList(array(),$arFilter,false,false,array('IBLOCK_ID','ID','NAME'));
			if ($arElement = $rsElements->GetNext())
			{
				$arResult = array(
					'ID' => $arElement['ID'],
					'NAME' => $arElement['NAME'],
					'~NAME' => $arElement['~NAME'],
					'IBLOCK_ID' => $arElement['IBLOCK_ID'],
				);
				$cache[$intElementID] = $arResult;
			}
			else
			{
				$cache[$intElementID] = false;
			}
		}
		return $cache[$intElementID];
	}

	protected function GetPropertyValue($arProperty, $arValue)
	{
		$mxResult = false;

		if (0 < intval($arValue['VALUE']))
		{
			$mxResult = self::GetLinkElement($arValue['VALUE'],$arProperty['LINK_IBLOCK_ID']);
			if (is_array($mxResult))
			{
				$mxResult['PROPERTY_ID'] = $arProperty['ID'];
				if (isset($arProperty['PROPERTY_VALUE_ID']))
				{
					$mxResult['PROPERTY_VALUE_ID'] = $arProperty['PROPERTY_VALUE_ID'];
				}
				else
				{
					$mxResult['PROPERTY_VALUE_ID'] = false;
				}
			}
		}
		return $mxResult;
	}

	protected function GetPropertyViewsList($boolFull)
	{
		$boolFull = (true === $boolFull);
		if ($boolFull)
		{
			return array(
				'REFERENCE' => array(
					Loc::getMessage('BT_UT_EAUTOCOMPLETE_VIEW_AUTO'),
					Loc::getMessage('BT_UT_EAUTOCOMPLETE_VIEW_TREE'),
					Loc::getMessage('BT_UT_EAUTOCOMPLETE_VIEW_ELEMENT'),
				),
				'REFERENCE_ID' => array(
					'A','T','E'
				),
			);
		}
		return array('A','T','E');
	}

	protected function GetReplaceSymList($boolFull = false)
	{
		$boolFull = (true === $boolFull);
		if ($boolFull)
		{
			return array(
				'REFERENCE' => array(
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_SPACE'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_GRID'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_STAR'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_UNDERLINE'),
					Loc::getMessage('BT_UT_AUTOCOMPLETE_SYM_OTHER'),

				),
				'REFERENCE_ID' => array(
					' ',
					'#',
					'*',
					'_',
					BT_UT_AUTOCOMPLETE_REP_SYM_OTHER,
				),
			);
		}
		return array(' ', '#', '*','_');
	}

	public function GetValueForAutoComplete($arProperty, $arValue, $arBanSym = "", $arRepSym = "")
	{
		$strResult = '';
		$mxResult = self::GetPropertyValue($arProperty,$arValue);
		if (is_array($mxResult))
		{
			$strResult = htmlspecialcharsbx(str_replace($arBanSym,$arRepSym,$mxResult['~NAME'])).' ['.$mxResult['ID'].']';
		}
		return $strResult;
	}

	public function GetValueForAutoCompleteMulti($arProperty, $arValues, $arBanSym = "", $arRepSym = "")
	{
		$arResult = false;

		if (is_array($arValues))
		{
			foreach ($arValues as $intPropertyValueID => $arOneValue)
			{
				if (!is_array($arOneValue))
				{
					$strTmp = $arOneValue;
					$arOneValue = array(
						'VALUE' => $strTmp,
					);
				}
				$mxResult = self::GetPropertyValue($arProperty,$arOneValue);
				if (is_array($mxResult))
				{
					$arResult[$intPropertyValueID] = htmlspecialcharsbx(str_replace($arBanSym,$arRepSym,$mxResult['~NAME'])).' ['.$mxResult['ID'].']';
				}
			}
		}
		return $arResult;
	}

	protected function GetSymbols($arSettings)
	{
		$strBanSym = $arSettings['BAN_SYM'];
		$strRepSym = (BT_UT_AUTOCOMPLETE_REP_SYM_OTHER == $arSettings['REP_SYM'] ? $arSettings['OTHER_REP_SYM'] : $arSettings['REP_SYM']);
		$arBanSym = str_split($strBanSym,1);
		$arRepSym = array_fill(0,sizeof($arBanSym),$strRepSym);
		$arResult = array(
			'BAN_SYM' => $arBanSym,
			'REP_SYM' => array_fill(0,sizeof($arBanSym),$strRepSym),
			'BAN_SYM_STRING' => $strBanSym,
			'REP_SYM_STRING' => $strRepSym,
		);
		return $arResult;
	}

	public function GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName)
	{
		global $APPLICATION;

		$arSettings = self::PrepareSettings($arProperty);
		$arSymbols = self::GetSymbols($arSettings);

		$strResult = '';

		if (isset($strHTMLControlName['MODE']) && ('iblock_element_admin' == $strHTMLControlName['MODE']))
		{
			$mxElement = self::GetPropertyValue($arProperty,$arValue);
			if (!is_array($mxElement))
			{
				$strResult = '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" id="'.$strHTMLControlName["VALUE"].'" value="" size="5">'.
					'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.intval($arProperty["LINK_IBLOCK_ID"]).'&amp;n='.urlencode($strHTMLControlName["VALUE"]).'\', 800, 600);">'.
					'&nbsp;<span id="sp_'.$strHTMLControlName["VALUE"].'" ></span>';
			}
			else
			{
				$strResult = '<input type="text" name="'.$strHTMLControlName["VALUE"].'" id="'.$strHTMLControlName["VALUE"].'" value="'.$arValue['VALUE'].'" size="5">'.
					'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$arProperty["LINK_IBLOCK_ID"].'&amp;n='.urlencode($strHTMLControlName["VALUE"]).'\', 800, 600);">'.
					'&nbsp;<span id="sp_'.$strHTMLControlName["VALUE"].'" >'.$mxElement['NAME'].'</span>';
			}
		}
		else
		{
			ob_start();
			?><?
			$strRandControlID = $strHTMLControlName["VALUE"].'_'.mt_rand(0, 10000);
			$control_id = $APPLICATION->IncludeComponent(
				"bitrix:main.lookup.input",
				"iblockedit",
				array(
					"CONTROL_ID" => preg_replace("/[^a-zA-Z0-9_]/i", "x", $strRandControlID),
					"INPUT_NAME" => $strHTMLControlName["VALUE"],
					"INPUT_NAME_STRING" => "inp_".$strHTMLControlName["VALUE"],
					"INPUT_VALUE_STRING" => htmlspecialcharsback(self::GetValueForAutoComplete($arProperty,$arValue,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM'])),
					"START_TEXT" => Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_INVITE'),
					"MULTIPLE" => $arProperty["MULTIPLE"],
					"MAX_WIDTH" => $arSettings['MAX_WIDTH'],
					"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
					'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
					'REP_SYM' => $arSymbols['REP_SYM_STRING'],
					'FILTER' => 'Y',
				), null, array("HIDE_ICONS" => "Y")
			);
			?><?
			if ('T' == $arSettings['VIEW'])
			{
				$name = $APPLICATION->IncludeComponent(
					'bitrix:main.tree.selector',
					'iblockedit',
					array(
						"INPUT_NAME" => $strHTMLControlName["VALUE"],
						'ONSELECT' => 'jsMLI_'.$control_id.'.SetValue',
						'MULTIPLE' => $arProperty["MULTIPLE"],
						'SHOW_INPUT' => 'N',
						'SHOW_BUTTON' => 'Y',
						'GET_FULL_INFO' => 'Y',
						"START_TEXT" => Loc::getMessage("BT_UT_EAUTOCOMPLETE_MESS_LIST_INVITE"),
						'BUTTON_CAPTION' => Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_CHOOSE_ELEMENT'),
						'BUTTON_TITLE' => Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_CHOOSE_ELEMENT_DESCR'),
						"NO_SEARCH_RESULT_TEXT" => Loc::getMessage("BT_UT_EAUTOCOMPLETE_MESS_NO_SEARCH_RESULT_TEXT"),
						"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
						'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
						'REP_SYM' => $arSymbols['REP_SYM_STRING'],
					), null, array("HIDE_ICONS" => "Y")
				);
				?><?
			}
			elseif ('E' == $arSettings['VIEW'])
			{
				?><input
				style="float: left; margin-right: 10px; margin-top: 5px;"
				type="button" value="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_SEARCH_ELEMENT'); ?>"
				title="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_SEARCH_ELEMENT_DESCR'); ?>"
				onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=<? echo LANGUAGE_ID; ?>&IBLOCK_ID=<? echo $arProperty["LINK_IBLOCK_ID"]; ?>&n=&k=&lookup=<? echo 'jsMLI_'.$control_id; ?>', 900, 600);"><?
			}
			if ('Y' == $arProperty['USER_TYPE_SETTINGS']['SHOW_ADD'])
			{
				if ('Y' == $arSettings['IBLOCK_MESS'])
				{
					$arLangMess = CIBlock::GetMessages($arProperty["LINK_IBLOCK_ID"]);
					$strButtonCaption = $arLangMess['ELEMENT_ADD'];
					if ('' == $strButtonCaption)
					{
						$strButtonCaption = Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_NEW_ELEMENT');
					}
				}
				else
				{
					$strButtonCaption = Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_NEW_ELEMENT');
				}
				?><input
					type="button"
					style="margin-top: 5px;"
					value="<? echo htmlspecialcharsbx($strButtonCaption); ?>"
					title="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_NEW_ELEMENT_DESCR'); ?>"
					onclick="jsUtils.OpenWindow('<? echo CIBlock::GetAdminElementEditLink(
						$arProperty["LINK_IBLOCK_ID"],
						null,
						array(
							'menu' => null,
							'IBLOCK_SECTION_ID' => -1,
							'find_section_section' => -1,
							'lookup' => 'jsMLI_'.$control_id
						)); ?>', 900, 600);"
					><?
			}
			$strResult = ob_get_contents();
			ob_end_clean();
		}
		return $strResult;
	}

	public function GetPropertyFieldHtmlMulty($arProperty, $arValues, $strHTMLControlName)
	{
		global $APPLICATION;

		$arSettings = self::PrepareSettings($arProperty);
		$arSymbols = self::GetSymbols($arSettings);

		$strResult = '';
		if (isset($strHTMLControlName['MODE']) && ('iblock_element_admin' == $strHTMLControlName['MODE']))
		{
			$arResult = false;
			foreach ($arValues as $intPropertyValueID => $arOneValue)
			{
				$mxElement = self::GetPropertyValue($arProperty,$arOneValue);
				if (is_array($mxElement))
				{
					$arResult[] = '<input type="text" name="'.$strHTMLControlName["VALUE"].'['.$intPropertyValueID.']" id="'.$strHTMLControlName["VALUE"].'['.$intPropertyValueID.']" value="'.$arOneValue['VALUE'].'" size="5">'.
					'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$arProperty["LINK_IBLOCK_ID"].'&amp;n='.urlencode($strHTMLControlName["VALUE"].'['.$intPropertyValueID.']').'\', 800, 600);">'.
					'&nbsp;<span id="sp_'.$strHTMLControlName["VALUE"].'['.$intPropertyValueID.']" >'.$mxElement['NAME'].'</span>';
				}
			}

			if (0 < intval($arProperty['MULTIPLE_CNT']))
			{
				for ($i = 0; $i < $arProperty['MULTIPLE_CNT']; $i++)
				{
					$arResult[] = '<input type="text" name="'.$strHTMLControlName["VALUE"].'[n'.$i.']" id="'.$strHTMLControlName["VALUE"].'[n'.$i.']" value="" size="5">'.
					'<input type="button" value="..." onClick="jsUtils.OpenWindow(\'iblock_element_search.php?lang='.LANGUAGE_ID.'&amp;IBLOCK_ID='.$arProperty["LINK_IBLOCK_ID"].'&amp;n='.urlencode($strHTMLControlName["VALUE"].'[n'.$i.']').'\', 800, 600);">'.
					'&nbsp;<span id="sp_'.$strHTMLControlName["VALUE"].'[n'.$i.']" ></span>';
				}
			}

			$strResult = implode('<br />',$arResult);
		}
		else
		{
			$mxResultValue = self::GetValueForAutoCompleteMulti($arProperty,$arValues,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM']);
			$strResultValue = (is_array($mxResultValue) ? htmlspecialcharsback(implode("\n",$mxResultValue)) : '');

			ob_start();
			?><?
			$strRandControlID = $strHTMLControlName["VALUE"].'_'.mt_rand(0, 10000);
			$control_id = $APPLICATION->IncludeComponent(
				"bitrix:main.lookup.input",
				"iblockedit",
				array(
					"CONTROL_ID" => preg_replace("/[^a-zA-Z0-9_]/i", "x", $strRandControlID),
					"INPUT_NAME" => $strHTMLControlName['VALUE'].'[]',
					"INPUT_NAME_STRING" => "inp_".$strHTMLControlName['VALUE'],
					"INPUT_VALUE_STRING" => $strResultValue,
					"START_TEXT" => Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_INVITE'),
					"MULTIPLE" => $arProperty["MULTIPLE"],
					"MAX_WIDTH" => $arSettings['MAX_WIDTH'],
					"MIN_HEIGHT" => $arSettings['MIN_HEIGHT'],
					"MAX_HEIGHT" => $arSettings['MAX_HEIGHT'],
					"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
					'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
					'REP_SYM' => $arSymbols['REP_SYM_STRING'],
					'FILTER' => 'Y',
				), null, array("HIDE_ICONS" => "Y")
			);
			?><?
			if ('T' == $arSettings['VIEW'])
			{
				$name = $APPLICATION->IncludeComponent(
					'bitrix:main.tree.selector',
					'iblockedit',
					array(
						"INPUT_NAME" => $strHTMLControlName['VALUE'],
						'ONSELECT' => 'jsMLI_'.$control_id.'.SetValue',
						'MULTIPLE' => $arProperty["MULTIPLE"],
						'SHOW_INPUT' => 'N',
						'SHOW_BUTTON' => 'Y',
						'GET_FULL_INFO' => 'Y',
						"START_TEXT" => Loc::getMessage("BT_UT_EAUTOCOMPLETE_MESS_LIST_INVITE"),
						'BUTTON_CAPTION' => Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_CHOOSE_ELEMENT'),
						'BUTTON_TITLE' => Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_CHOOSE_ELEMENT_MULTI_DESCR'),
						"NO_SEARCH_RESULT_TEXT" => Loc::getMessage("BT_UT_EAUTOCOMPLETE_MESS_NO_SEARCH_RESULT_TEXT"),
						"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
						'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
						'REP_SYM' => $arSymbols['REP_SYM_STRING'],
					), null, array("HIDE_ICONS" => "Y")
				);
				?><?
			}
			elseif ('E' == $arSettings['VIEW'])
			{
				?><input
					style="float: left; margin-right: 10px; margin-top: 5px;"
					type="button" value="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_SEARCH_ELEMENT'); ?>"
					title="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_SEARCH_ELEMENT_MULTI_DESCR'); ?>"
					onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=<? echo LANGUAGE_ID; ?>&IBLOCK_ID=<? echo $arProperty["LINK_IBLOCK_ID"]; ?>&n=&k=&m=y&lookup=<? echo 'jsMLI_'.$control_id; ?>', 900, 600);"><?
			}
			if ('Y' == $arProperty['USER_TYPE_SETTINGS']['SHOW_ADD'])
			{
				if ('Y' == $arSettings['IBLOCK_MESS'])
				{
					$arLangMess = CIBlock::GetMessages($arProperty["LINK_IBLOCK_ID"]);
					$strButtonCaption = $arLangMess['ELEMENT_ADD'];
					if ('' == $strButtonCaption)
					{
						$strButtonCaption = Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_NEW_ELEMENT');
					}
				}
				else
				{
					$strButtonCaption = Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_NEW_ELEMENT');
				}
				?><input
				type="button"
				style="margin-top: 5px;"
				value="<? echo htmlspecialcharsbx($strButtonCaption); ?>"
				title="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_NEW_ELEMENT_DESCR'); ?>"
				onclick="jsUtils.OpenWindow('<? echo CIBlock::GetAdminElementEditLink(
					$arProperty["LINK_IBLOCK_ID"],
					null,
					array(
						'menu' => null,
						'IBLOCK_SECTION_ID' => -1,
						'find_section_section' => -1,
						'lookup' => 'jsMLI_'.$control_id
					)); ?>', 900, 600);"
				><?
			}
			$strResult = ob_get_contents();
			ob_end_clean();
		}
		return $strResult;
	}

	public function GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		$strResult = '';
		$mxResult = self::GetPropertyValue($arProperty,$arValue);
		if (is_array($mxResult))
		{
			$strResult = $mxResult['NAME'].' [<a href="'.
				CIBlock::GetAdminElementEditLink(
					$mxResult['IBLOCK_ID'],
					$mxResult['ID'],
					array(
						'WF' => 'Y'
					)
				).'" title="'.Loc::getMessage("BT_UT_EAUTOCOMPLETE_MESS_ELEMENT_EDIT").'">'.$mxResult['ID'].'</a>]';
		}
		return $strResult;
	}

	public function GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		static $cache = array();

		$strResult = '';
		$arValue['VALUE'] = intval($arValue['VALUE']);
		if (0 < $arValue['VALUE'])
		{
			if (!isset($cache[$arValue['VALUE']]))
			{
				$arFilter = array();
				$intIBlockID = intval($arProperty['LINK_IBLOCK_ID']);
				if (0 < $intIBlockID) $arFilter['IBLOCK_ID'] = $intIBlockID;
				$arFilter['ID'] = $arValue['VALUE'];
				$arFilter["ACTIVE"] = "Y";
				$arFilter["ACTIVE_DATE"] = "Y";
				$arFilter["CHECK_PERMISSIONS"] = "Y";
				$arFilter["MIN_PERMISSION"] = "R";
				$rsElements = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID","IBLOCK_ID","NAME","DETAIL_PAGE_URL"));
				$cache[$arValue['VALUE']] = $rsElements->GetNext(true,true);
			}
			if (is_array($cache[$arValue['VALUE']]))
			{
				if (isset($strHTMLControlName['MODE']) && 'CSV_EXPORT' == $strHTMLControlName['MODE'])
				{
					$strResult = $cache[$arValue['VALUE']]['ID'];
				}
				elseif (isset($strHTMLControlName['MODE']) && ('SIMPLE_TEXT' == $strHTMLControlName['MODE'] || 'ELEMENT_TEMPLATE' == $strHTMLControlName['MODE']))
				{
					$strResult = $cache[$arValue['VALUE']]["~NAME"];
				}
				else
				{
					$strResult = '<a href="'.$cache[$arValue['VALUE']]["DETAIL_PAGE_URL"].'">'.$cache[$arValue['VALUE']]["NAME"].'</a>';
				}
			}
		}
		return $strResult;
	}

	public function PrepareSettings($arFields)
	{
		/*
		 * VIEW				- view type
		 * SHOW_ADD			- show button for add new values in linked iblock
		 * MAX_WIDTH		- max width textarea and input in pixels
		 * MIN_HEIGHT		- min height textarea in pixels
		 * MAX_HEIGHT		- max height textarea in pixels
		 * BAN_SYM			- banned symbols string
		 * REP_SYM			- replace symbol
		 * OTHER_REP_SYM	- non standart replace symbol
		 * IBLOCK_MESS		- get lang mess from linked iblock
		 */
		$arViewsList = self::GetPropertyViewsList(false);
		$strView = '';
		$strView = (isset($arFields['USER_TYPE_SETTINGS']['VIEW']) && in_array($arFields['USER_TYPE_SETTINGS']['VIEW'],$arViewsList) ? $arFields['USER_TYPE_SETTINGS']['VIEW'] : current($arViewsList));

		$strShowAdd = (isset($arFields['USER_TYPE_SETTINGS']['SHOW_ADD']) ? $arFields['USER_TYPE_SETTINGS']['SHOW_ADD'] : '');
		$strShowAdd = ('Y' == $strShowAdd ? 'Y' : 'N');

		$intMaxWidth = intval(isset($arFields['USER_TYPE_SETTINGS']['MAX_WIDTH']) ? $arFields['USER_TYPE_SETTINGS']['MAX_WIDTH'] : 0);
		if (0 >= $intMaxWidth) $intMaxWidth = 0;

		$intMinHeight = intval(isset($arFields['USER_TYPE_SETTINGS']['MIN_HEIGHT']) ? $arFields['USER_TYPE_SETTINGS']['MIN_HEIGHT'] : 0);
		if (0 >= $intMinHeight) $intMinHeight = 24;

		$intMaxHeight = intval(isset($arFields['USER_TYPE_SETTINGS']['MAX_HEIGHT']) ? $arFields['USER_TYPE_SETTINGS']['MAX_HEIGHT'] : 0);
		if (0 >= $intMaxHeight) $intMaxHeight = 1000;

		$strBannedSymbols = trim(isset($arFields['USER_TYPE_SETTINGS']['BAN_SYM']) ? $arFields['USER_TYPE_SETTINGS']['BAN_SYM'] : ',;');
		$strBannedSymbols = str_replace(' ','',$strBannedSymbols);
		if (false === strpos($strBannedSymbols,','))
			$strBannedSymbols .= ',';
		if (false === strpos($strBannedSymbols,';'))
			$strBannedSymbols .= ';';

		$strOtherReplaceSymbol = '';
		$strReplaceSymbol = (isset($arFields['USER_TYPE_SETTINGS']['REP_SYM']) ? $arFields['USER_TYPE_SETTINGS']['REP_SYM'] : ' ');
		if (BT_UT_AUTOCOMPLETE_REP_SYM_OTHER == $strReplaceSymbol)
		{
			$strOtherReplaceSymbol = (isset($arFields['USER_TYPE_SETTINGS']['OTHER_REP_SYM']) ? substr($arFields['USER_TYPE_SETTINGS']['OTHER_REP_SYM'],0,1) : '');
			if ((',' == $strOtherReplaceSymbol) || (';' == $strOtherReplaceSymbol))
				$strOtherReplaceSymbol = '';
			if (('' == $strOtherReplaceSymbol) || in_array($strOtherReplaceSymbol,self::GetReplaceSymList()))
			{
				$strReplaceSymbol = $strOtherReplaceSymbol;
				$strOtherReplaceSymbol = '';
			}
		}
		if ('' == $strReplaceSymbol)
		{
			$strReplaceSymbol = ' ';
			$strOtherReplaceSymbol = '';
		}

		$strIBlockMess = (isset($arFields['USER_TYPE_SETTINGS']['IBLOCK_MESS']) ? $arFields['USER_TYPE_SETTINGS']['IBLOCK_MESS'] : '');
		if ('Y' != $strIBlockMess) $strIBlockMess = 'N';

		return array(
			'VIEW' => $strView,
			'SHOW_ADD' => $strShowAdd,
			'MAX_WIDTH' => $intMaxWidth,
			'MIN_HEIGHT' => $intMinHeight,
			'MAX_HEIGHT' => $intMaxHeight,
			'BAN_SYM' => $strBannedSymbols,
			'REP_SYM' => $strReplaceSymbol,
			'OTHER_REP_SYM' => $strOtherReplaceSymbol,
			'IBLOCK_MESS' => $strIBlockMess,
		);
	}

	public function GetSettingsHTML($arFields,$strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT","MULTIPLE_CNT"),
			'USER_TYPE_SETTINGS_TITLE' => Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_TITLE'),
		);

		$arSettings = self::PrepareSettings($arFields);

		return '<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_VIEW').'</td>
		<td>'.SelectBoxFromArray($strHTMLControlName["NAME"].'[VIEW]',self::GetPropertyViewsList(true),htmlspecialcharsbx($arSettings['VIEW'])).'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_SHOW_ADD').'</td>
		<td>'.InputType('checkbox',$strHTMLControlName["NAME"].'[SHOW_ADD]','Y',htmlspecialcharsbx($arSettings["SHOW_ADD"])).'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_IBLOCK_MESS').'</td>
		<td>'.InputType('checkbox',$strHTMLControlName["NAME"].'[IBLOCK_MESS]','Y',htmlspecialcharsbx($arSettings["IBLOCK_MESS"])).'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_MAX_WIDTH').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[MAX_WIDTH]" value="'.intval($arSettings['MAX_WIDTH']).'">&nbsp;'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_COMMENT_MAX_WIDTH').'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_MIN_HEIGHT').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[MIN_HEIGHT]" value="'.intval($arSettings['MIN_HEIGHT']).'">&nbsp;'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_COMMENT_MIN_HEIGHT').'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_MAX_HEIGHT').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[MAX_HEIGHT]" value="'.intval($arSettings['MAX_HEIGHT']).'">&nbsp;'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_COMMENT_MAX_HEIGHT').'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_BAN_SYMBOLS').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[BAN_SYM]" value="'.htmlspecialcharsbx($arSettings['BAN_SYM']).'"></td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_EAUTOCOMPLETE_SETTING_REP_SYMBOL').'</td>
		<td>'.SelectBoxFromArray($strHTMLControlName["NAME"].'[REP_SYM]',self::GetReplaceSymList(true),htmlspecialcharsbx($arSettings['REP_SYM'])).'&nbsp;<input type="text" name="'.$strHTMLControlName["NAME"].'[OTHER_REP_SYM]" size="1" maxlength="1" value="'.$arSettings['OTHER_REP_SYM'].'"></td>
		</tr>
		';
	}

	public function GetAdminFilterHTML($arProperty, $strHTMLControlName)
	{
		global $APPLICATION;

		$strResult = '';
		$arSettings = self::PrepareSettings($arProperty);
		$arSymbols = self::GetSymbols($arSettings);

		$strValue = '';

		if (isset($_REQUEST[$strHTMLControlName["VALUE"]]) && (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) || (0 < intval($_REQUEST[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) ? $_REQUEST[$strHTMLControlName["VALUE"]] : array($_REQUEST[$strHTMLControlName["VALUE"]]));
			$mxResultValue = self::GetValueForAutoCompleteMulti($arProperty,$arFilterValues,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM']);
			$strValue = (is_array($mxResultValue) ? htmlspecialcharsback(implode("\n",$mxResultValue)) : '');
		}
		elseif (isset($GLOBALS[$strHTMLControlName["VALUE"]]) && (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) || (0 < intval($GLOBALS[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) ? $GLOBALS[$strHTMLControlName["VALUE"]] : array($GLOBALS[$strHTMLControlName["VALUE"]]));
			$mxResultValue = self::GetValueForAutoCompleteMulti($arProperty,$arFilterValues,$arSymbols['BAN_SYM'],$arSymbols['REP_SYM']);
			$strValue = (is_array($mxResultValue) ? htmlspecialcharsback(implode("\n",$mxResultValue)) : '');
		}
		ob_start();
		?><?
		$control_id = $APPLICATION->IncludeComponent(
			"bitrix:main.lookup.input",
			"iblockedit",
			array(
				"INPUT_NAME" => $strHTMLControlName['VALUE'].'[]',
				"INPUT_NAME_STRING" => "inp_".$strHTMLControlName['VALUE'],
				"INPUT_VALUE_STRING" => $strValue,
				"START_TEXT" => '',
				"MULTIPLE" => 'Y',
				'MAX_WIDTH' => '200',
				'MIN_HEIGHT' => '24',
				"IBLOCK_ID" => $arProperty["LINK_IBLOCK_ID"],
				'BAN_SYM' => $arSymbols['BAN_SYM_STRING'],
				'REP_SYM' => $arSymbols['REP_SYM_STRING'],
				'FILTER' => 'Y',
			), null, array("HIDE_ICONS" => "Y")
		);
		?><input
			style="float: left; margin-right: 10px;"
			type="button" value="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_SEARCH_ELEMENT'); ?>"
			title="<? echo Loc::getMessage('BT_UT_EAUTOCOMPLETE_MESS_SEARCH_ELEMENT_MULTI_DESCR'); ?>"
			onclick="jsUtils.OpenWindow('/bitrix/admin/iblock_element_search.php?lang=<? echo LANGUAGE_ID; ?>&IBLOCK_ID=<? echo $arProperty["LINK_IBLOCK_ID"]; ?>&n=&k=&m=y&lookup=<? echo 'jsMLI_'.$control_id; ?>', 900, 600);">
		<script type="text/javascript">
		if (!!arClearHiddenFields)
		{
			indClearHiddenFields = arClearHiddenFields.length;
			arClearHiddenFields[indClearHiddenFields] = 'jsMLI_<? echo $control_id; ?>';
		}
		</script><?
		$strResult = ob_get_contents();
		ob_end_clean();

		return $strResult;
	}

	public function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered)
	{
		$filtered = false;

		$arFilterValues = array();

		if (isset($_REQUEST[$strHTMLControlName["VALUE"]]) && (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) || (0 < intval($_REQUEST[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($_REQUEST[$strHTMLControlName["VALUE"]]) ? $_REQUEST[$strHTMLControlName["VALUE"]] : array($_REQUEST[$strHTMLControlName["VALUE"]]));
		}
		elseif (isset($GLOBALS[$strHTMLControlName["VALUE"]]) && (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) || (0 < intval($GLOBALS[$strHTMLControlName["VALUE"]]))))
		{
			$arFilterValues = (is_array($GLOBALS[$strHTMLControlName["VALUE"]]) ? $GLOBALS[$strHTMLControlName["VALUE"]] : array($GLOBALS[$strHTMLControlName["VALUE"]]));
		}

		foreach ($arFilterValues as $key => $value)
		{
			if (0 >= intval($value))
				unset($arFilterValues[$key]);
		}

		if (!empty($arFilterValues))
		{
			$arFilter["=PROPERTY_".$arProperty["ID"]] = $arFilterValues;
			$filtered = true;
		}
	}
}
?>