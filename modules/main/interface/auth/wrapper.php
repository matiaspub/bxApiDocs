<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @param string $inc_file From CMain::AuthForm()
 * @param array $arAuthResult From CMain::AuthForm()
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

IncludeModuleLangFile(__FILE__);

$arFormsList = array("authorize", "forgot_password", "change_password");
if (!in_array($inc_file, $arFormsList))
	$inc_file = $arFormsList[0];

function dump_post_var($vname, $vvalue, $var_stack=array())
{
	if(is_array($vvalue))
	{
		$str = "";
		foreach($vvalue as $key=>$value)
			$str .= ($str == "" ? '' : '&').dump_post_var($key, $value, array_merge($var_stack ,array($vname)));
		return $str;
	}
	else
	{
		if(count($var_stack)>0)
		{
			$var_name=$var_stack[0];
			$varStackCount = count($var_stack);
			for($i = 1; $i < $varStackCount; $i++)
				$var_name.="[".$var_stack[$i]."]";
			$var_name.="[".$vname."]";
		}
		else
			$var_name=$vname;

		return urlencode($var_name).'='.urlencode($vvalue);
	}
}

if (isset($_REQUEST['bxsender']))
{
	if ($_REQUEST['bxsender'] != 'core_autosave')
		require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/auth/wrapper_popup.php");

	return;
}

if ($arAuthResult && defined('ADMIN_SECTION_LOAD_AUTH') && ADMIN_SECTION_LOAD_AUTH || $_REQUEST['AUTH_FORM'])
{
	$APPLICATION->RestartBuffer();
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/auth/wrapper_auth_result.php");
	die();
}

$post_data = '';
foreach($_POST as $vname=>$vvalue)
{
	if($vname=="USER_LOGIN" || $vname=="USER_PASSWORD")
		continue;
	$post_data .= ($post_data == '' ? '' : '&').dump_post_var($vname, $vvalue);
}

if(!CMain::IsHTTPS() && COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
{
	$sec = new CRsaSecurity();
	if(($arKeys = $sec->LoadKeys()))
	{
		$sec->SetKeys($arKeys);
		$sec->AddToForm('form_auth', array('USER_PASSWORD', 'USER_CONFIRM_PASSWORD'));
		$bSecure = true;
	}
}

$sDocPath = $APPLICATION->GetCurPage();
$authUrl = (defined('BX_ADMIN_SECTION_404') && BX_ADMIN_SECTION_404 == 'Y') ? '/bitrix/admin/' : $sDocPath;
?>
<script type="text/javascript">
BX.message({
	'admin_authorize_error': '<?=GetMessageJS("admin_authorize_error")?>',
	'admin_forgot_password_error': '<?=GetMessageJS("admin_forgot_password_error")?>',
	'admin_change_password_error': '<?=GetMessageJS("admin_change_password_error")?>',
	'admin_authorize_info': '<?=GetMessageJS("admin_authorize_info")?>'
});

new BX.adminLogin({
	form: 'form_auth',
	start_form: '<?=CUtil::JSEscape($inc_file)?>',
	post_data: '<?=CUtil::JSEscape($post_data)?>',
	popup_alignment: 'popup_alignment',
	login_wrapper: 'login_wrapper',
	window_wrapper: 'window_wrapper',
	auth_form_wrapper: 'auth_form_wrapper',
	login_variants: 'login_variants',
	url: '<?echo CUtil::JSEscape($sDocPath.(($s=DeleteParam(array("logout", "login"))) == ""? "":"?".$s));?>'
});
</script>

	<table class="login-popup-alignment">
		<tr>
			<td class="login-popup-alignment-2" id="popup_alignment">
				<div class="login-header">
					<a href="/" class="login-logo">
						<span class="login-logo-img"></span><span class="login-logo-text"><?=$_SERVER["SERVER_NAME"]?></span>
					</a>
					<div class="login-language-btn-wrap"><div class="login-language-btn" id="login_lang_button"><?=$arLangButton['TEXT']?></div></div>
				</div>

				<div class="login-footer">
					<div class="login-footer-left"><?=$sCopyright?></div>
					<div class="login-footer-right">
						<?if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/this_site_support.php")):?><?include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/this_site_support.php");?><?else:?><?echo $sLinks?><?endif;?>
					</div>
				</div>
				<form name="form_auth" method="post" target="auth_frame" class="bx-admin-auth-form" action="" novalidate>
					<input type="hidden" name="AUTH_FORM" value="Y">

					<div id="auth_form_wrapper"><?require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/auth/".$inc_file.'.php')?></div>

					<?=bitrix_sessid_post()?>
				</form>
			</td>
		</tr>
	</table>

<iframe name="auth_frame" src="" style="display:none;"></iframe>

<div id="login_variants" style="display: none;">
<?
foreach ($arFormsList as $form)
{
	if ($form != $inc_file)
	{
		require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/auth/".$form.".php");
	}
}
?>

<div id="forgot_password_message" class="login-popup-wrap login-popup-ifo-wrap">
	<div class="login-popup">
		<div class="login-popup-title"><?=GetMessage('AUTH_FORGOT_PASSWORD')?></div>
		<div class="login-popup-title-description"><?=GetMessage("AUTH_GET_CHECK_STRING_SENT")?></div>
		<div class="login-popup-message-wrap">
			<div class="adm-info-message-wrap adm-info-message-green">
				<div class="adm-info-message" id="forgot_password_message_inner"></div>
			</div>
		</div>
		<a class="login-popup-link" href="javascript:void(0)" onclick="BX.adminLogin.toggleAuthForm('change_password')"><?=GetMessage('AUTH_GOTO_CHANGE_FORM')?></a>
	</div>
</div>

<div id="change_password_message" class="login-popup-wrap login-popup-ifo-wrap">
	<div class="login-popup">
		<div class="login-popup-title"><?=GetMessage('AUTH_CHANGE_PASSWORD')?></div>
		<div class="login-popup-message-wrap">
			<div class="adm-info-message-wrap adm-info-message-green">
				<div class="adm-info-message" id="change_password_message_inner"></div>
			</div>
		</div>
		<a class="login-popup-link" href="javascript:void(0)" onclick="BX.adminLogin.toggleAuthForm('authorize')"><?=GetMessage('AUTH_GOTO_AUTH_FORM')?></a>
	</div>
</div>

</div>
<?
if ($arAuthResult)
{
	$bOnHit = true;
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/auth/wrapper_auth_result.php");
}
?>