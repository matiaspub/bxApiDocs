<?
// define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!$USER->IsAuthorized() || !check_bitrix_sessid())
	die();

$start_time = intval($_REQUEST['start_time'])%86400;

if ($start_time > 0)
	$start_time = str_pad(intval($start_time/3600), 2, '0', STR_PAD_LEFT).':'.str_pad(intval(($start_time%3600)/60), 2, '0', STR_PAD_LEFT);
else
	$start_time = '';

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");

$clock_input_id = 'clock_'.rand(0, 100000);

CClock::Show(
	array(
		'inputId' => $clock_input_id,
		'inputName' => $_REQUEST['clock_id'],
		'view' => 'inline',
		'showIcon' => false,
		'initTime' => $start_time
	)
);

?><script type="text/javascript">BX.onCustomEvent('onClockRegister',[{<?=CUtil::JSEscape($_REQUEST['clock_id'])?>:'<?=$clock_input_id?>'}])</script><?

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>