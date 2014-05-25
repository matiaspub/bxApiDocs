<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

function JSPopupRedirectHandler(&$url, $skip_security_check)
{
	if(preg_match("#^/bitrix/admin/#", $url))
	{
		ob_end_clean();
		echo '<script type="text/javascript">top.BX.WindowManager.Get().Close(); '.(!$_REQUEST['subdialog'] ? 'top.BX.reload(true);' : '').'</script>';
		die();
	}
	else
	{
		ob_end_clean();
		echo '<script type="text/javascript">top.BX.WindowManager.Get().Close(); '.(!$_REQUEST['subdialog'] ? 'top.BX.reload(\''.CUtil::JSEscape($url).'\', true);' : '').'</script>';
		die();
	}
}

AddEventHandler('main', 'OnBeforeLocalRedirect', 'JSPopupRedirectHandler');
?>