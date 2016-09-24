<?
class CCalendarPlanner
{
	public static function Init($config = array())
	{
		self::InitJsCore($config);
	}

	public static function InitJsCore($config = array())
	{
		global $APPLICATION;
		CUtil::InitJSCore(array('ajax', 'window', 'popup', 'access', 'date', 'viewer', 'socnetlogdest'));

		// Config
		if (!$config['id'])
			$config['id'] = (isset($config['id']) && strlen($config['id']) > 0) ? $config['id'] : 'bx_calendar_planner'.substr(uniqid(mt_rand(), true), 0, 4);

		$APPLICATION->AddHeadScript('/bitrix/js/calendar/planner.js');
		$APPLICATION->SetAdditionalCSS("/bitrix/js/calendar/planner-style.css");

		$mess_lang = \Bitrix\Main\Localization\Loc::loadLanguageFile(__FILE__);
		?>
		<div id="<?= htmlspecialcharsbx($config['id'])?>" class="calendar-planner-wrapper"></div>
		<script type="text/javascript">
			BX.message(<?=CUtil::PhpToJSObject($mess_lang, false);?>);
			BX.ready(function()
			{
				new CalendarPlanner(<?=CUtil::PhpToJSObject($config, false);?>);
			});
		</script>
		<?
	}
}

?>