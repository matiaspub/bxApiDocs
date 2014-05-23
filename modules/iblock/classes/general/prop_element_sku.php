<?
use Bitrix\Main\Localization\Loc as Loc;
Loc::loadMessages(__FILE__);

define('BT_UT_SKU_CODE','SKU');

class CIBlockPropertySKU extends CIBlockPropertyElementAutoComplete
{
	public function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => "E",
			"USER_TYPE" => BT_UT_SKU_CODE,
			"DESCRIPTION" => Loc::getMessage('BT_UT_SKU_DESCR'),
			"GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array(__CLASS__, "GetPropertyFieldHtml"),
			"GetPublicViewHTML" => array(__CLASS__, "GetPublicViewHTML"),
			"GetAdminListViewHTML" => array(__CLASS__,"GetAdminListViewHTML"),
			"GetAdminFilterHTML" => array(__CLASS__,'GetAdminFilterHTML'),
			"GetSettingsHTML" => array(__CLASS__,'GetSettingsHTML'),
			"PrepareSettings" => array(__CLASS__,'PrepareSettings'),
			"AddFilterFields" => array(__CLASS__,'AddFilterFields')
		);
	}

	public function GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName)
	{
		return parent::GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName);
	}

	public function GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		return parent::GetAdminListViewHTML($arProperty, $arValue, $strHTMLControlName);
	}

	public function GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName)
	{
		return parent::GetPublicViewHTML($arProperty, $arValue, $strHTMLControlName);
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
		 * remove SHOW_ADD manage
		 */
		$arResult = parent::PrepareSettings($arFields);
		$arResult['SHOW_ADD'] = 'N';
		$arFields['MULTIPLE'] = 'N';
		$arFields['USER_TYPE_SETTINGS'] = $arResult;

		return $arFields;
	}

	public function GetSettingsHTML($arFields,$strHTMLControlName, &$arPropertyFields)
	{
		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT", "MULTIPLE"),
			"SET" => array("MULTIPLE" => "N"),
			'USER_TYPE_SETTINGS_TITLE' => Loc::getMessage('BT_UT_SKU_SETTING_TITLE'),
		);

		$arSettings = self::PrepareSettings($arFields);
		if (isset($arSettings['USER_TYPE_SETTINGS']))
			$arSettings = $arSettings['USER_TYPE_SETTINGS'];

		$strResult = '<tr>
		<td>'.Loc::getMessage('BT_UT_SKU_SETTING_VIEW').'</td>
		<td>'.SelectBoxFromArray($strHTMLControlName["NAME"].'[VIEW]',self::GetPropertyViewsList(true),htmlspecialcharsbx($arSettings['VIEW'])).'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_SKU_SETTING_MAX_WIDTH').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[MAX_WIDTH]" value="'.intval($arSettings['MAX_WIDTH']).'">&nbsp;'.Loc::getMessage('BT_UT_SKU_SETTING_COMMENT_MAX_WIDTH').'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_SKU_SETTING_MIN_HEIGHT').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[MIN_HEIGHT]" value="'.intval($arSettings['MIN_HEIGHT']).'">&nbsp;'.Loc::getMessage('BT_UT_SKU_SETTING_COMMENT_MIN_HEIGHT').'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_SKU_SETTING_MAX_HEIGHT').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[MAX_HEIGHT]" value="'.intval($arSettings['MAX_HEIGHT']).'">&nbsp;'.Loc::getMessage('BT_UT_SKU_SETTING_COMMENT_MAX_HEIGHT').'</td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_SKU_SETTING_BAN_SYMBOLS').'</td>
		<td><input type="text" name="'.$strHTMLControlName["NAME"].'[BAN_SYM]" value="'.htmlspecialcharsbx($arSettings['BAN_SYM']).'"></td>
		</tr>
		<tr>
		<td>'.Loc::getMessage('BT_UT_SKU_SETTING_REP_SYMBOL').'</td>
		<td>'.SelectBoxFromArray($strHTMLControlName["NAME"].'[REP_SYM]',parent::GetReplaceSymList(true),htmlspecialcharsbx($arSettings['REP_SYM'])).'&nbsp;<input type="text" name="'.$strHTMLControlName["NAME"].'[OTHER_REP_SYM]" size="1" maxlength="1" value="'.$arSettings['OTHER_REP_SYM'].'"></td>
		</tr>';

		return $strResult;
	}

	public function GetAdminFilterHTML($arProperty, $strHTMLControlName)
	{
		return parent::GetAdminFilterHTML($arProperty, $strHTMLControlName);
	}

	public function AddFilterFields($arProperty, $strHTMLControlName, &$arFilter, &$filtered)
	{
		parent::AddFilterFields($arProperty, $strHTMLControlName, $arFilter, $filtered);
	}
}
?>