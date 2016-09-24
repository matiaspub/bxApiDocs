<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CIBlockPropertyDate extends CIBlockPropertyDateTime
{
	public static function ConvertToDB($arProperty, $value)
	{
		if (strlen($value["VALUE"])>0)
			$value["VALUE"] = CDatabase::FormatDate($value["VALUE"], CLang::GetDateFormat("SHORT"), "YYYY-MM-DD");

		return $value;
	}

	public static function ConvertFromDB($arProperty, $value, $format = '')
	{
		if(strlen($value["VALUE"])>0)
			$value["VALUE"] = CDatabase::FormatDate($value["VALUE"], "YYYY-MM-DD", CLang::GetDateFormat("SHORT"));

		return $value;
	}

	public static function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
	{
		/** @var CMain */
		global $APPLICATION;

		$s = '<input type="text" name="'.htmlspecialcharsbx($strHTMLControlName["VALUE"]).'" size="25" value="'.htmlspecialcharsbx($value["VALUE"]).'" />';
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:main.calendar',
			'',
			array(
				'FORM_NAME' => $strHTMLControlName["FORM_NAME"],
				'INPUT_NAME' => $strHTMLControlName["VALUE"],
				'INPUT_VALUE' => $value["VALUE"],
				'SHOW_TIME' => "N",
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		$s .= ob_get_contents();
		ob_end_clean();
		return  $s;
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		return  CAdminCalendar::CalendarDate($strHTMLControlName["VALUE"], $value["VALUE"], 20, false).
		($arProperty["WITH_DESCRIPTION"]=="Y" && '' != trim($strHTMLControlName["DESCRIPTION"]) ?
			'&nbsp;<input type="text" size="20" name="'.$strHTMLControlName["DESCRIPTION"].'" value="'.htmlspecialcharsbx($value["DESCRIPTION"]).'">'
			:''
		);
	}
}