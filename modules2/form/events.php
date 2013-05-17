<?
class CFormEventHandlers
{
	public static function sendOnAfterResultStatusChange($WEB_FORM_ID, $RESULT_ID, $NEW_STATUS_ID = false, $CHECK_RIGHTS = 'Y')
	{
		$NEW_STATUS_ID = intval($NEW_STATUS_ID);
	
		$dbRes = CForm::GetByID($WEB_FORM_ID);
		if (!$arForm = $dbRes->Fetch())
			return;
		
		CTimeZone::Disable();
		$dbRes = CFormResult::GetByID($RESULT_ID);
		CTimeZone::Enable();

		if (!($arResult = $dbRes->Fetch()) || !$arResult['USER_ID'])
			return;
		
		$dbRes = CUser::GetByID($arResult['USER_ID']);
		if (!($arUser = $dbRes->Fetch()))
			return;
		
		if (!$NEW_STATUS_ID)
			$NEW_STATUS_ID = CFormStatus::GetDefault($WEB_FORM_ID);
		
		$dbRes = CFormStatus::GetByID($NEW_STATUS_ID);
		if (!($arStatus = $dbRes->Fetch()) || strlen($arStatus['MAIL_EVENT_TYPE']) <= 0)
			return;

		$arTemplates = CFormStatus::GetMailTemplateArray($NEW_STATUS_ID);
		if (!is_array($arTemplates) || count($arTemplates) <= 0)
			return;
			
		$arEventFields = array(
			"EMAIL_TO"				=> $arUser['EMAIL'],
			"RS_FORM_ID"			=> $arForm["ID"],
			"RS_FORM_NAME"			=> $arForm["NAME"],
			"RS_FORM_VARNAME"		=> $arForm["SID"],
			"RS_FORM_SID"			=> $arForm["SID"],
			"RS_RESULT_ID"			=> $arResult["ID"],
			"RS_DATE_CREATE"		=> $arResult["DATE_CREATE"],
			"RS_USER_ID"			=> $arResult['USER_ID'],
			"RS_USER_EMAIL"			=> $arUser['EMAIL'],
			"RS_USER_NAME"			=> $arUser["NAME"]." ".$arUser["LAST_NAME"],
			"RS_STATUS_ID"			=> $arStatus["ID"],
			"RS_STATUS_NAME"		=> $arStatus["TITLE"],
		);
		
		$dbRes = CEventMessage::GetList($by="id", $order="asc", array(
			'ID' => implode('|', $arTemplates),
			"ACTIVE"		=> "Y",
			"EVENT_NAME"	=> $arStatus["MAIL_EVENT_TYPE"]
		));
		
		while ($arTemplate = $dbRes->Fetch())
			CEvent::Send($arTemplate["EVENT_NAME"], $arTemplate["SITE_ID"], $arEventFields, "Y", $arTemplate["ID"]);
	}
}
?>