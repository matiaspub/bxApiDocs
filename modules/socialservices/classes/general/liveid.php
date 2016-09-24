<?
IncludeModuleLangFile(__FILE__);

class CSocServLiveID extends CSocServAuth
{
	static public function GetSettings()
	{
		$liveid_disabled = !WindowsLiveLogin::IsAvailable();
		return array(
			array("liveid_appid", GetMessage("MAIN_OPTION_AUTH_LIVEID_APPLID"), "", Array("text", 40), $liveid_disabled),
			array("liveid_secret", GetMessage("MAIN_OPTION_AUTH_LIVEID_SECRET"), "", Array("text", 40), $liveid_disabled),
			array("note"=>GetMessage('MAIN_OPTION_COMMENT1')),
		);
	}

	static public function GetFormHtml($arParams)
	{
		$wll = new WindowsLiveLogin();
	
		$wll->setAppId(self::GetOption('liveid_appid'));
		$wll->setSecret(self::GetOption('liveid_secret'));
	
		$_SESSION['BX_LIVEID_LAST_PAGE'] = $GLOBALS["APPLICATION"]->GetCurPageParam('', array('logout'));

		return '<noindex><a href="'.$wll->getLoginUrl().'" rel="nofollow" class="bx-ss-button liveid-button"></a><span class="bx-spacer"></span><span>'.GetMessage("socserv_liveid_note").'</span></noindex>';
	}
}
?>