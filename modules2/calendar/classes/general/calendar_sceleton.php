<?
class CCalendarSceleton
{
	// Show html
	public static function Build($Params)
	{
		global $APPLICATION;
		$id = $Params['id'];

		$Tabs = array(
			array('name' => GetMessage('EC_TAB_MONTH'), 'title' => GetMessage('EC_TAB_MONTH_TITLE'), 'id' => $id."_tab_month"),
			array('name' => GetMessage('EC_TAB_WEEK'), 'title' => GetMessage('EC_TAB_WEEK_TITLE'), 'id' => $id."_tab_week"),
			array('name' => GetMessage('EC_TAB_DAY'), 'title' => GetMessage('EC_TAB_DAY_TITLE'), 'id' => $id."_tab_day")
		);

		$bCalDAV = CCalendar::IsCalDAVEnabled() && $Params['type'] == 'user';

		// Here can be added user's dialogs, scripts, html
		foreach(GetModuleEvents("calendar", "OnBeforeBuildSceleton", true) as $arEvent)
			ExecuteModuleEventEx($arEvent);

		$days = self::GetWeekDaysEx(CCalendar::GetWeekStart());
		?>
<script>
/* Event handler for user control*/
function bxcUserSelectorOnchange(arUsers){BX.onCustomEvent(window, 'onUserSelectorOnChange', [arUsers]);}
</script>
		<?if ($Params['bShowSections'] || $Params['bShowSuperpose'] || $Params['bShowBanner']):?>
<div class="bxec-sect-cont" id="<?=$id?>_sect_cont">
	<b class="r2"></b><b class="r1"></b><b class="r0"></b>
		<?if ($Params['bShowSections']):?>
		<span class="bxec-sect-cont-wrap" id="<?=$id?>sections">
			<b class="r-2"></b><b class="r-1"></b><b class="r-0"></b>
			<div class="bxec-sect-cont-inner">
				<div class="bxec-sect-title"><span class="bxec-spr bxec-flip"></span><span class="bxec-sect-title-text"><?=GetMessage('EC_T_CALENDARS')?></span>
				<a id="<?=$id?>-add-section" class="bxec-sect-top-action" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_CAL_TITLE')?>"  hidefocus="true" style="visibility:hidden;"><?= strtolower(GetMessage('EC_T_ADD'))?></a>
				</div>
				<div class="bxec-sect-cont-white">
					<div id="<?=$id?>sections-cont"></div>
					<?if($Params['bShowTasks']):?>
					<div id="<?=$id?>tasks-sections-cont"></div>
					<?endif;?>
					<div id="<?=$id?>caldav-sections-cont"></div>
				</div>
			</div>
			<i class="r-0"></i><i class="r-1"></i><i class="r-2"></i>
		</span>
		<?endif; /*bShowSections*/ ?>

		<?if ($Params['bShowSuperpose']):?>
		<span class="bxec-sect-cont-wrap" id="<?=$id?>sp-sections">
			<b class="r-2"></b><b class="r-1"></b><b class="r-0"></b>
			<div class="bxec-sect-cont-inner bxec-sect-superpose">
				<div class="bxec-sect-title"><span class="bxec-spr bxec-flip"></span><span class="bxec-sect-title-text"><?=GetMessage('EC_T_SP_CALENDARS')?></span>
				<a id="<?=$id?>-manage-superpose" class="bxec-sect-top-action" href="javascript:void(0);" title="<?=GetMessage('EC_ADD_EX_CAL_TITLE')?>"  hidefocus="true" style="visibility:hidden;"><?= strtolower(GetMessage('EC_ADD_EX_CAL'))?></a>
				</div>
				<div class="bxec-sect-cont-white"  id="<?=$id?>sp-sections-cont"></div>
			</div>
			<i class="r-0"></i><i class="r-1"></i><i class="r-2"></i>
		</span>
		<?endif; /*bShowSuperpose*/ ?>

		<?if ($Params['bShowBanner']):?>
		<span class="bxec-sect-cont-wrap" id="<?=$id?>banner">
			<div class="bxec-sect-cont-inner bxec-sect-banner">
				<div class="bxec-banner">
					<?if ($Params['bOutlook']):?>
					<div class="bxec-banner-elem bxec-ban-outlook">
						<i></i>
						<span class="bxec-banner-text" id="<?=$id?>_outl_sel"><span><?= GetMessage('EC_BAN_CONNECT_OUTL')?></span><b class="bxec-ban-arrow"></b></span>
					</div>
					<?endif;?>
					<?if ($Params['bCalDAV']):?>
					<div class="bxec-banner-elem bxec-ban-mobile">
						<i></i>
						<span class="bxec-banner-text" id="<?=$id?>_mob_sel" <?if (strlen(GetMessage('EC_BAN_CONNECT_MOBI')) < 30) {echo 'style="margin-top:9px!important;"';}?>><span><?= GetMessage('EC_BAN_CONNECT_MOBI')?></span><b class="bxec-ban-arrow"></b></span>
					</div>
					<?endif;?>

					<?if ($Params['bExchange']):?>
					<div class="bxec-banner-elem bxec-ban-exch<?if ($Params['bExchangeConnected']) {echo ' bxec-ban-exch-connected';}?>" title="<?= ($Params['bExchangeConnected'] ? GetMessage('EC_BAN_CONNECT_EXCH_TITLE') : GetMessage('EC_BAN_NOT_CONNECT_EXCH_TITLE'))?>">
						<i></i>
						<span class="bxec-banner-text">
						<span class="bxec-banner-text-ok">
							<?= GetMessage('EC_BAN_CONNECT_EXCH')?>
							<a href="javascript:void('');"  id="<?=$id?>_exch_sync" title="<?= GetMessage('EC_BAN_EXCH_SYNC_TITLE')?>"><?= GetMessage('EC_BAN_EXCH_SYNC')?></a>
						</span>
						<span class="bxec-banner-text-warn">
							<?= GetMessage('EC_BAN_NOT_CONNECT_EXCH')?>
						</span>
						</span>
						<span class="bxec-banner-status"></span>
					</div>
					<?endif;?>
					<a href="javascript:void('');" class="bxec-close"  id="<?=$id?>_ban_close" title="<?= GetMessage('EC_T_CLOSE')?>"></a>
				</div>
			</div>
		</span>
		<?endif; /*bShowBanner*/ ?>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
		<?endif; /* bShowSections || bShowSuperpose || bShowBanner*/?>

<div class="bxcal-loading" id="<?=$id?>_bxcal" style="">
<div class="bxcal-wait"></div>
<div class="bxec-tabs-cnt">
	<div class="bxec-tabs-div">
		<?foreach($Tabs as $tab):?>
		<div class="bxec-tab-div" title="<?=$tab['title']?>" id="<?=$tab['id']?>">
			<b></b><div class="bxec-tab-c"><span><?=$tab['name']?></span></div><i></i>
		</div>
		<?endforeach;?>
	</div>
	<div class="bxec-bot-bg"></div>

	<div class="bxec-view-selector-cont">
		<div id="<?=$id?>_selector" class="bxec-selector-cont">
		<a class="bxec-sel-left"  id="<?=$id?>selector-prev"></a>
		<span class="bxec-sel-cont">
			<a class="bxec-sel-but" id="<?=$id?>selector-cont"><b></b><span class="bxec-sel-but-inner" id="<?=$id?>selector-cont-inner"><span class="bxec-sel-but-arr"></span></span><i></i></a>
		</span>
		<a class="bxec-sel-right" id="<?=$id?>selector-next"></a>
		</div>
		<div id="bxec_month_win_<?=$id?>" class="bxec-month-dialog">
			<div class="bxec-md-year-selector">
				<a class="bxec-sel-left"  id="<?=$id?>md-selector-prev"></a>
				<span class="bxec-md-year-text"><span class="bxec-md-year-text-inner" id="<?=$id?>md-year"></span></span>
				<a class="bxec-sel-right" id="<?=$id?>md-selector-next"></a>
			</div>
			<div class="bxec-md-month-list"  id="<?=$id?>md-month-list"></div>
		</div>
	</div>
	<div id="<?=$id?>_buttons_cont" class="bxec-buttons-cont"></div>
</div>
<div>
	<table class="BXEC-Calendar" cellPadding="0" cellSpacing="0" id="<?=$id?>_scel_table_month" style="display:none;">
	<tr class="bxec-days-title"><td>
		<!--Don't insert spases inside DOM layout!-->
		<div id="<?=$id?>_days_title" class="bxc-month-title"><?foreach($days as $day):?><b id="<?=$id.'_'.$day['2']?>" title="<?= $day['0']?>"><i><?= $day['1']?></i></b><?endforeach;?></div>
	</td></tr>
	<tr><td class="bxec-days-grid-td"><div id="<?=$id?>_days_grid" class="bxec-days-grid-cont"></div>
	</td></tr>
	</table>
	<table class="BXEC-Calendar-week" id="<?=$id?>_scel_table_week" cellPadding="0" cellSpacing="0" style="display:none;">
		<tr class="bxec-days-tbl-title"><td class="bxec-pad"><div class="bxec-day-t-event-holder"></div><img src="/bitrix/images/1.gif" width="40" height="1"/></td><td class="bxec-pad2"><img src="/bitrix/images/1.gif" width="16" height="1"/></td></tr>
		<tr class="bxec-days-tbl-more-ev"><td class="bxec-pad"></td><td class="bxec-pad2"></td></tr>
		<tr class="bxec-days-tbl-grid"><td class="bxec-cont"><div class="bxec-timeline-div"></div></td></tr>
	</table>
	<table class="BXEC-Calendar-week" id="<?=$id?>_scel_table_day" cellPadding="0" cellSpacing="0" style="display:none;">
		<tr class="bxec-days-tbl-title"><td class="bxec-pad"><div class="bxec-day-t-event-holder"></div><img src="/bitrix/images/1.gif" width="40" height="1" /></td><td class="bxec-pad2"><img src="/bitrix/images/1.gif" width="16" height="1" /></td></tr>
		<tr class="bxec-days-tbl-more-ev"><td class="bxec-pad"></td><td class="bxec-pad2"></td></tr>
		<tr class="bxec-days-tbl-grid"><td class="bxec-cont"><div class="bxec-timeline-div"></div></td></tr>
	</table>
</div>
</div>
<?
		self::BuildDialogs($Params);

		if($Params['bShowTasks'])
		{
		?>
<script>
// Js event handlers which will be captured in calendar's js
function onPopupTaskAdded(arTask){BX.onCustomEvent(window, 'onCalendarPopupTaskAdded', [arTask]);}
function onPopupTaskChanged(arTask){BX.onCustomEvent(window, 'onCalendarPopupTaskChanged', [arTask]);}
function onPopupTaskDeleted(taskId){BX.onCustomEvent(window, 'onCalendarPopupTaskDeleted', [taskId]);}
</script>
		<?
			$APPLICATION->IncludeComponent(
				"bitrix:tasks.iframe.popup",
				"",
				array(
					"ON_TASK_ADDED" => "onPopupTaskAdded",
					"ON_TASK_CHANGED" => "onPopupTaskChanged",
					"ON_TASK_DELETED" => "onPopupTaskDeleted",
					"TASKS_LIST" => $Params['arTaskIds']
				),
				null,
				array("HIDE_ICONS" => "Y")
			);
		}

		//CCalendar::GetUserfieldsEditHtml(0);

		// Here can be added user's dialogs, scripts, html
		foreach(GetModuleEvents("calendar", "OnAfterBuildSceleton", true) as $arEvent)
			ExecuteModuleEventEx($arEvent);
	}

	public static function InitJS($JSConfig)
	{
		global $APPLICATION;
		CUtil::InitJSCore(array('ajax', 'window', 'popup', 'access', 'date'));

		$JSConfig['days'] = self::GetWeekDays();
		$JSConfig['month'] = array(GetMessage('EC_JAN'), GetMessage('EC_FEB'), GetMessage('EC_MAR'), GetMessage('EC_APR'), GetMessage('EC_MAY'), GetMessage('EC_JUN'), GetMessage('EC_JUL'), GetMessage('EC_AUG'), GetMessage('EC_SEP'), GetMessage('EC_OCT'), GetMessage('EC_NOV'), GetMessage('EC_DEC'));
		$JSConfig['month_r'] = array(GetMessage('EC_JAN_R'), GetMessage('EC_FEB_R'), GetMessage('EC_MAR_R'), GetMessage('EC_APR_R'), GetMessage('EC_MAY_R'), GetMessage('EC_JUN_R'), GetMessage('EC_JUL_R'), GetMessage('EC_AUG_R'), GetMessage('EC_SEP_R'), GetMessage('EC_OCT_R'), GetMessage('EC_NOV_R'), GetMessage('EC_DEC_R'));

		$JSConfig['planner_js_src'] = '/bitrix/js/calendar/cal-planner.js?v='.filemtime($_SERVER['DOCUMENT_ROOT']."/bitrix/js/calendar/cal-planner.js");

		$APPLICATION->SetAdditionalCSS("/bitrix/js/calendar/cal-style.css");
		// Add scripts
		$arJS = array(
			'/bitrix/js/calendar/cal-core.js',
			'/bitrix/js/calendar/cal-dialogs.js',
			'/bitrix/js/calendar/cal-week.js',
			'/bitrix/js/calendar/cal-events.js',
			'/bitrix/js/calendar/cal-controlls.js'
		);
		$arCSS = array();

		//if (!$USER->IsAuthorized()) // For anonymus  users
		//{
		//	$arJS[] = '/bitrix/js/main/utils.js';
		//	$arCSS[] = '/bitrix/themes/.default/pubstyles.css';
		//}

		for($i = 0, $l = count($arJS); $i < $l; $i++)
			$arJS[$i] .= '?v='.filemtime($_SERVER['DOCUMENT_ROOT'].$arJS[$i]);

		for($i = 0, $l = count($arCSS); $i < $l; $i++)
			$arCSS[$i] .= '?v='.filemtime($_SERVER['DOCUMENT_ROOT'].$arCSS[$i]);

		?>
		<script>
		<?self::Localization();?>
		<?CCalendarPlanner::Localization();?>

		BX.ready(function()
			{
				window.bxRunEC = function()
				{
					if (!window.JCEC || !window.ECMonthSelector || !window.ECUserControll  || !window.JSECEvent)
						return setTimeout(window.bxRunEC, 100);

					<? /* new JCEC(<?=$Params['JSConfig']?>, <?=$Params['JS_arEvents']?>, <?=$Params['JS_arSPEvents']?>); */?>
					new JCEC(<?= CUtil::PhpToJSObject($JSConfig)?>);
				};

				<?if (count($arCSS) > 0):?>
				BX.loadCSS(<?= '["'.implode($arCSS, '","').'"]'?>);
				<?endif;?>
				BX.loadScript(<?= '["'.implode($arJS, '","').'"]'?>, bxRunEC);
			}
		);
		</script>
		<?
	}

	private static function BuildDialogs($Params)
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");
		$id = $Params['id'];
		?><div id="<?=$id?>_dialogs_cont" style="display: none;"><?
		if (!$Params['bReadOnly'])
		{
			self::DialogAddEventSimple($Params);
			self::DialogEditEvent($Params);
			self::DialogEditSection($Params);

			self::DialogExternalCalendars($Params);
		}
		self::DialogSettings($Params);

		self::DialogViewEvent($Params);
		self::DialogExportCalendar($Params);
		self::DialogMobileCon($Params);

		if ($Params['bShowSuperpose'])
			self::DialogSuperpose($Params);

		if(!$Params['bReadOnly'] && $Params['bSocNet'])
			CCalendarPlanner::BuildDialog($Params);
		?></div><?
	}

	public static function Localization()
	{
		$arLangMess = array(
			'DelMeetingConfirm' => 'EC_JS_DEL_MEETING_CONFIRM',
			'DelMeetingGuestConfirm' => 'EC_JS_DEL_MEETING_GUEST_CONFIRM',
			'DelEventConfirm' => 'EC_JS_DEL_EVENT_CONFIRM',
			'DelEventError' => 'EC_JS_DEL_EVENT_ERROR',
			'EventNameError' => 'EC_JS_EV_NAME_ERR',
			'EventSaveError' => 'EC_JS_EV_SAVE_ERR',
			'EventDatesError' => 'EC_JS_EV_DATES_ERR',
			'NewEvent' => 'EC_JS_NEW_EVENT',
			'EditEvent' => 'EC_JS_EDIT_EVENT',
			'DelEvent' => 'EC_JS_DEL_EVENT',
			'ViewEvent' => 'EC_JS_VIEW_EVENT',
			'From' => 'EC_JS_FROM',
			'To' => 'EC_JS_TO',
			'From_' => 'EC_JS_FROM_',
			'To_' => 'EC_JS_TO_',
			'EveryM' => 'EC_JS_EVERY_M',
			'EveryF' => 'EC_JS_EVERY_F',
			'EveryN' => 'EC_JS_EVERY_N',
			'EveryM_' => 'EC_JS_EVERY_M_',
			'EveryF_' => 'EC_JS_EVERY_F_',
			'EveryN_' => 'EC_JS_EVERY_N_',
			'DeDot' => 'EC_JS_DE_DOT',
			'DeAm' => 'EC_JS_DE_AM',
			'DeDes' => 'EC_JS_DE_DES',
			'_J' => 'EC_JS__J',
			'_U' => 'EC_JS__U',
			'WeekP' => 'EC_JS_WEEK_P',
			'DayP' => 'EC_JS_DAY_P',
			'MonthP' => 'EC_JS_MONTH_P',
			'YearP' => 'EC_JS_YEAR_P',
			'DateP_' => 'EC_JS_DATE_P_',
			'MonthP_' => 'EC_JS_MONTH_P_',
			'ShowPrevYear' => 'EC_JS_SHOW_PREV_YEAR',
			'ShowNextYear' => 'EC_JS_SHOW_NEXT_YEAR',
			'AddCalen' => 'EC_JS_ADD_CALEN',
			'AddCalenTitle' => 'EC_JS_ADD_CALEN_TITLE',
			'Edit' => 'EC_JS_EDIT',
			'Delete' => 'EC_JS_DELETE',
			'EditCalendarTitle' => 'EC_JS_EDIT_CALENDAR',
			'DelCalendarTitle' => 'EC_JS_DEL_CALENDAR',
			'NewCalenTitle' => 'EC_JS_NEW_CALEN_TITLE',
			'EditCalenTitle' => 'EC_JS_EDIT_CALEN_TITLE',
			'EventDiapStartError' => 'EC_JS_EV_FROM_ERR',
			'EventDiapEndError' => 'EC_JS_EV_DIAP_END_ERR',
			'CalenNameErr' => 'EC_JS_CALEN_NAME_ERR',
			'CalenSaveErr' => 'EC_JS_CALEN_SAVE_ERR',
			'DelCalendarConfirm' => 'EC_JS_DEL_CALENDAR_CONFIRM',
			'DelCalendarErr' => 'EC_JS_DEL_CALEN_ERR',
			'AddNewEvent' => 'EC_JS_ADD_NEW_EVENT',
			'SelectMonth' => 'EC_JS_SELECT_MONTH',
			'ShowPrevMonth' => 'EC_JS_SHOW_PREV_MONTH',
			'ShowNextMonth' => 'EC_JS_SHOW_NEXT_MONTH',
			'LoadEventsErr' => 'EC_JS_LOAD_EVENTS_ERR',
			'MoreEvents' => 'EC_JS_MORE',
			'Item' => 'EC_JS_ITEM',
			'Export' => 'EC_JS_EXPORT',
			'ExportTitle' => 'EC_JS_EXPORT_TILE',
			'CalHide' => 'EC_CAL_HIDE',
			'CalHideTitle' => 'EC_CAL_HIDE_TITLE',
			'CalAdd2SP' => 'EC_ADD_TO_SP',
			'CalAdd2SPTitle' => 'EC_CAL_ADD_TO_SP_TITLE',
			'HideSPCalendarErr' => 'EC_HIDE_SP_CALENDAR_ERR',
			'AppendSPCalendarErr' => 'EC_APPEND_SP_CALENDAR_ERR',
			'FlipperHide' => 'EC_FLIPPER_HIDE',
			'FlipperShow' => 'EC_FLIPPER_SHOW',
			'SelectAll' => 'EC_SHOW_All_CALS',
			'DeSelectAll' => 'EC_HIDE_All_CALS',
			'ExpDialTitle' => 'EC_EXP_DIAL_TITLE',
			'ExpDialTitleSP' => 'EC_EXP_DIAL_TITLE_SP',
			'ExpText' => 'EC_EXP_TEXT',
			'ExpTextSP' => 'EC_EXP_TEXT_SP',
			'UserCalendars' => 'EC_USER_CALENDARS',
			'DeleteDynSPGroupTitle' => 'EC_DELETE_DYN_SP_GROUP_TITLE',
			'DeleteDynSPGroup' => 'EC_DELETE_DYN_SP_GROUP',
			'CalsAreAbsent' => 'EC_CALS_ARE_ABSENT',
			'DelAllTrackingUsersConfirm' => 'EC_DEL_ALL_TRACK_USERS_CONF',
			'ShowPrevWeek' => 'EC_SHOW_PREV_WEEK',
			'ShowNextWeek' => 'EC_SHOW_NEXT_WEEK',
			'CurTime' => 'EC_CUR_TIME',
			'GoToDay' => 'EC_GO_TO_DAY',
			'DelGuestTitle' => 'EC_DEL_GUEST_TITLE',
			'DelGuestConf' => 'EC_DEL_GUEST_CONFIRM',
			'DelAllGuestsConf' => 'EC_DEL_ALL_GUESTS_CONFIRM',
			'GuestStatus_q' => 'EC_GUEST_STATUS_Q',
			'GuestStatus_y' => 'EC_GUEST_STATUS_Y',
			'GuestStatus_n' => 'EC_GUEST_STATUS_N',
			'UserProfile' => 'EC_USER_PROFILE',
			'AllGuests' => 'EC_ALL_GUESTS',
			'ShowAllGuests' => 'EC_ALL_GUESTS_TITLE',
			'DelEncounter' => 'EC_DEL_ENCOUNTER',
			'ConfirmEncY' => 'EC_ACCEPT_MEETING',
			'ConfirmEncN' => 'EC_EDEV_CONF_N',
			'ConfirmEncYTitle' => 'EC_EDEV_CONF_Y_TITLE',
			'ConfirmEncNTitle' => 'EC_EDEV_CONF_N_TITLE',
			'Confirmed' => 'EC_EDEV_CONFIRMED',
			'NotConfirmed' => 'EC_NOT_CONFIRMED',
			'NoLimits' => 'EC_T_DIALOG_NEVER',
			'Acc_busy' => 'EC_ACCESSIBILITY_B',
			'Acc_quest' => 'EC_ACCESSIBILITY_Q',
			'Acc_free' => 'EC_ACCESSIBILITY_F',
			'Acc_absent' => 'EC_ACCESSIBILITY_A',
			'Importance' => 'EC_IMPORTANCE',
			'Importance_high' => 'EC_IMPORTANCE_H',
			'Importance_normal' => 'EC_IMPORTANCE_N',
			'Importance_low' => 'EC_IMPORTANCE_L',
			'PrivateEvent' => 'EC_PRIVATE_EVENT',
			'LostSessionError' => 'EC_LOST_SESSION_ERROR',
			'ConnectToOutlook' => 'EC_CONNECT_TO_OUTLOOK',
			'ConnectToOutlookTitle' => 'EC_CONNECT_TO_OUTLOOK_TITLE',
			'UsersNotFound' => 'EC_USERS_NOT_FOUND',
			'UserBusy' => 'EC_USER_BUSY',
			'UsersNotAvailable' => 'EC_USERS_NOT_AVAILABLE',
			'UserAccessibility' => 'EC_ACCESSIBILITY',
			'CantDelGuestTitle' => 'EC_CANT_DEL_GUEST_TITLE',
			'Host' => 'EC_EDEV_HOST',
			'ViewingEvent' => 'EC_T_VIEW_EVENT',
			'NoCompanyStructure' => 'EC_NO_COMPANY_STRUCTURE',
			'DelOwnerConfirm' => 'EC_DEL_OWNER_CONFIRM',
			'MeetTextChangeAlert' => 'EC_MEET_TEXT_CHANGE_ALERT',
			'ImpGuest' => 'EC_IMP_GUEST',
			'NotImpGuest' => 'EC_NOT_IMP_GUEST',
			'DurDefMin' => 'EC_EDEV_REM_MIN',
			'DurDefHour1' => 'EC_PL_DUR_HOUR1',
			'DurDefHour2' => 'EC_PL_DUR_HOUR2',
			'DurDefDay' => 'EC_JS_DAY_P',
			'SelectMR' => 'EC_PL_SEL_MEET_ROOM',
			'OpenMRPage' => 'EC_PL_OPEN_MR_PAGE',
			'Location' => 'EC_LOCATION',
			'FreeMR' => 'EC_MR_FREE',
			'MRNotReservedErr' => 'EC_MR_RESERVE_ERR_BUSY',
			'MRReserveErr' => 'EC_MR_RESERVE_ERR',
			'FirstInList' => 'EC_FIRST_IN_LIST',
			'Settings' => 'EC_BUT_SET',
			'AddNewEventPl' => 'EC_JS_ADD_NEW_EVENT_PL',
			'DefMeetingName' => 'EC_DEF_MEETING_NAME',
			'NoGuestsErr' => 'EC_NO_GUESTS_ERR',
			'NoFromToErr' => 'EC_NO_FROM_TO_ERR',
			'MRNotExpireErr' => 'EC_MR_EXPIRE_ERR_BUSY',
			'CalDavEdit' => 'EC_CALDAV_EDIT',
			'NewExCalendar' => 'EC_NEW_EX_CAL',
			'CalDavDel' => 'EC_CALDAV_DEL',
			'CalDavCollapse' => 'EC_CALDAV_COLLAPSE',
			'CalDavRestore' => 'EC_CALDAV_RESTORE',
			'CalDavNoChange' => 'EC_CALDAV_NO_CHANGE',
			'CalDavTitle' => 'EC_MANAGE_CALDAV',
			'SyncOk' => 'EC_CALDAV_SYNC_OK',
			'SyncDate' => 'EC_CALDAV_SYNC_DATE',
			'SyncError' => 'EC_CALDAV_SYNC_ERROR',
			'AllCalendars' => 'EC_ALL_CALENDARS',
			'DelConCalendars' => 'DEL_CON_CALENDARS',
			'CloseBannerNotify' => 'EC_CLOSE_BANNER_NOTIFY',
			'ExchNoSync' => 'EC_BAN_EXCH_NO_SYNC',
			'Add' => 'EC_T_ADD',
			'Save' => 'EC_T_SAVE',
			'Close' => 'EC_T_CLOSE',
			'GoExt' => 'EC_EXT_DIAL',
			'GoExtTitle' => 'EC_GO_TO_EXT_DIALOG',
			'Event' => 'EC_NEW_EVENT',
			'EventPl' => 'EC_NEW_EV_PL',
			'NewTask' => 'EC_NEW_TASK',
			'NewTaskTitle' => 'EC_NEW_TASK_TITLE',
			'NewSect' => 'EC_NEW_SECT',
			'NewSectTitle' => 'EC_NEW_SECT_TITLE',
			'NewExtSect' => 'EC_NEW_EX_SECT',
			'NewExtSectTitle' => 'EC_NEW_EX_SECT_TITLE',
			'DelSect' => 'EC_T_DELETE_CALENDAR',
			'Clear' => 'EC_CLEAR',
			'TaskView' => 'EC_TASKS_VIEW',
			'TaskEdit' => 'EC_TASKS_EDIT',
			'MyTasks' => 'EC_MY_TASKS',
			'NoAccessRights' => 'EC_NO_ACCESS_RIGHTS',
			'AddAttendees' => 'EC_ADD_ATTENDEES',
			'AddGuestsDef' => 'EC_ADD_GUESTS_DEF',
			'AddGuestsEmail' => 'EC_ADD_GUESTS_EMAIL',
			'AddGroupMemb' => 'EC_ADD_GROUP_MEMBER',
			'AddGroupMembTitle' => 'EC_ADD_GROUP_MEMBER_TITLE',
			'UserEmail' => 'EC_USER_EMAIL',
			'AttSumm' => 'EC_ATT_SUM',
			'AttAgr' => 'EC_ATT_AGR',
			'AttDec' => 'EC_ATT_DEC',
			'CalDavDialogTitle' => 'EC_CALDAV_TITLE',
			'AddCalDav' => 'EC_ADD_CALDAV',
			'MobileHelpTitle' => 'EC_MOBILE_HELP_TITLE',
			'UserSettings' => 'EC_SET_TAB_PERSONAL_TITLE',
			'ClearUserSetConf' => 'EC_CLEAR_SET_CONFIRM',
			'Adjust' => 'EC_ADD_EX_CAL',
			'ItIsYou' => 'EC_IT_IS_YOU',
			'DefaultColor' => 'EC_DEFAULT_COLOR',
			'SPCalendars' => 'EC_T_SP_CALENDARS',
			'NoCalendarsAlert' => 'EC_NO_CALENDARS_ALERT',
			'EventMRCheckWarn' => 'EC_MR_CHECK_PERIOD_WARN',
			'CalDavConWait' => 'EC_CAL_DAV_CON_WAIT',
			'Refresh' => 'EC_CAL_DAV_REFRESH'
		);

?>
var EC_MESS = {0:0<?foreach($arLangMess as $m1 => $m2){echo ', '.$m1." : '".GetMessageJS($m2)."'";}?>};
<?
	}

	private static function DialogAddEventSimple($Params)
	{
		$id = $Params['id'];
?>
<div id="bxec_add_ed_<?=$id?>" class="bxec-popup" style="width:380px;">
	<div class="bxec-popup-row" style="text-align:center;">
		<div class="bxec-txt" id="<?=$id?>_add_ed_per_text"></div>
	</div>
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>_add_ed_name"><b><?= GetMessage('EC_T_NAME')?>:</b></label></span>
		<span class="bxec-field-val-2 bxec-field-title-inner" style="width: 240px;"><input type="text" id="<?=$id?>_add_ed_name" /></span>
	</div>

	<?if($Params['bIntranet'] && ($Params['type'] != 'user' || $Params['inPersonalCalendar'])):?>
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2">
			<label for="<?=$id?>_add_ed_acc"><?=GetMessage('EC_ACCESSIBILITY_S')?>:</label>
		</span>
		<span  class="bxec-field-val-2">
			<select id="<?=$id?>_add_ed_acc" style="max-width: 250px;">
				<option value="busy" ><?=GetMessage('EC_ACCESSIBILITY_B')?></option>
				<option value="quest"><?=GetMessage('EC_ACCESSIBILITY_Q')?></option>
				<option value="free"><?=GetMessage('EC_ACCESSIBILITY_F')?></option>
				<option value="absent"><?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)</option>
			</select>
		</span>
	</div>
	<?endif;?>
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>_add_ed_calend_sel"><?=GetMessage('EC_T_CALENDAR')?>:</label></span>
		<span class="bxec-field-val-2"><select id="<?=$id?>_add_ed_calend_sel" style="max-width: 250px;"></select></span>
		<span id="<?=$id?>_add_sect_sel_warn" class="bxec-warn" style="display: none;"><?=GetMessage('EC_T_CALEN_DIS_WARNING')?></span>
	</div>
</div>
<?
	}

	private static function DialogEditEvent($Params)
	{
		global $APPLICATION;
		$id = $Params['id'];
		$bWideDate = strpos(FORMAT_DATETIME, 'MMMM') !== false;

		$arTabs = array(
			array('name' => GetMessage('EC_EDEV_EVENT'), 'title' => GetMessage('EC_EDEV_EVENT_TITLE'), 'id' => $id."ed-tab-0", 'active' => true),
			array('name' => GetMessage('EC_T_DESC'), 'title' => GetMessage('EC_T_DESC_TITLE'), 'id' => $id."ed-tab-1"),
			array('name' => GetMessage('EC_EDEV_GUESTS'), 'title' => GetMessage('EC_EDEV_GUESTS_TITLE'), 'id' => $id."ed-tab-2", "show" => !!$Params['bSocNet']),
			array('name' => GetMessage('EC_EDEV_ADD_TAB'), 'title' => GetMessage('EC_EDEV_ADD_TAB_TITLE'), 'id' => $id."ed-tab-3")
		);
?>
<div id="bxec_edit_ed_<?=$id?>" class="bxec-popup">
	<div style="width: 520px; height: 1px;"></div>
	<div class="bxec-d-tabs" id="<?=$id?>_edit_tabs">
		<?foreach($arTabs as $tab):?>
			<div class="bxec-d-tab <?if($tab['active'])echo'bxec-d-tab-act';?>" title="<?=$tab['title']?>" id="<?=$tab['id']?>" <?if($tab['show'] === false)echo'style="display:none;"';?>>
				<b></b><div><span><?=$tab['name']?></span></div><i></i>
			</div>
		<?endforeach;?>
	</div>
	<div class="bxec-d-cont"  id="<?=$id?>_edit_ed_d_tabcont">
		<?/* ####### TAB 0 : MAIN ####### */?>
		<div id="<?=$id?>ed-tab-0-cont" class="bxec-d-cont-div<?= ($Params['bAMPM'] ? ' bxec-d-cont-div-ampm' : '')?><?= ($bWideDate ? ' bxec-d-cont-div-wide-date' : '')?>">
			<div class="bxc-meeting-edit-note"><?= GetMessage('EC_EDIT_MEETING_NOTE')?></div>

			<div class="bxec-popup-row bxec-popup-row-from-to">
				<div style="margin-top: 16px;">
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-calendar">
					<label class="bxec-from-to-lbl bxec-field-lbl-imp" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_DATE_FROM')?></label>
					<input id="<?=$id?>edev-from"  type="text"/></span>
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-time"><?CClock::Show(array('inputId' => $id.'edev_from_time', 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM')));?><i class="bxec-time-icon"></i></span>
				&mdash;
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-calendar">
					<label class="bxec-from-to-lbl" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_DATE_TO')?></label>
					<input id="<?=$id?>edev-to"  type="text"/></span>
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-time"><?CClock::Show(array('inputId' => $id.'edev_to_time', 'inputTitle' => GetMessage('EC_EDEV_TIME_TO')));?><i class="bxec-time-icon"></i></span>
				<div class="bxec-cal-icon-bogus"><?$APPLICATION->IncludeComponent("bitrix:main.calendar",	"",Array("FORM_NAME" => "","INPUT_NAME" => "","INPUT_VALUE" => "","SHOW_TIME" => "N","HIDE_TIMEBAR" => "Y","SHOW_INPUT" => "N"),false, array("HIDE_ICONS" => "Y"));?></div>
				</div>

				<div class="bxec-popup-row-checkbox bxec-popup-row-full-day">
					<input type="checkbox" id="<?=$id?>_full_day" value="Y" />
					<label style="display: inline-block;" for="<?=$id?>_full_day"><?= GetMessage('EC_FULL_DAY')?></label>
				</div>
			</div>

			<div class="bxec-popup-row">
				<div class="bxec-field-label"><label class="bxec-field-lbl-imp" for="<?=$id?>_edit_ed_name"><?= GetMessage('EC_T_NAME')?></label></div>
				<span class="bxec-field-val-2 bxec-field-title-inner" style="width: <?= ($Params['bAMPM'] ? 495 : 440)?>px;"><input type="text" id="<?=$id?>_edit_ed_name" /></span>
			</div>

			<div class="bxec-popup-row" id="<?=$id?>_location_cnt">
				<span class="bxec-field-label-edev"><label for="<?=$id?>_planner_location1"><?=GetMessage('EC_LOCATION')?>:</label></span>
				<span class="bxec-field-val-2 bxecpl-loc-cont" >
				<input size="37" style="width: 246px;" id="<?=$id?>_planner_location1" type="text"  title="<?=GetMessage('EC_LOCATION_TITLE')?>" value="<?= GetMessage('EC_PL_SEL_MEET_ROOM')?>" class="ec-label" />
				</span>
			</div>

			<?if($Params['bIntranet']):?>
			<div class="bxec-popup-row bxec-ed-meeting-vis">
				<span class="bxec-field-label-edev"><label for="<?=$id?>_bxec_accessibility"><?=GetMessage('EC_ACCESSIBILITY')?>:</label></span>
				<span class="bxec-field-val-2" >
				<select id="<?=$id?>_bxec_accessibility" style="width: 210px;">
					<option value="busy" title="<?=GetMessage('EC_ACCESSIBILITY_B')?>"><?=GetMessage('EC_ACCESSIBILITY_B')?></option>
					<option value="quest" title="<?=GetMessage('EC_ACCESSIBILITY_Q')?>"><?=GetMessage('EC_ACCESSIBILITY_Q')?></option>
					<option value="free" title="<?=GetMessage('EC_ACCESSIBILITY_F')?>"><?=GetMessage('EC_ACCESSIBILITY_F')?></option>
					<option value="absent" title="<?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)"><?=GetMessage('EC_ACCESSIBILITY_A')?> (<?=GetMessage('EC_ACC_EX')?>)</option>
				</select>
				</span>
			</div>
			<?endif;?>

			<div class="bxec-popup-row" id="<?=$id?>_sect_cnt">
				<span class="bxec-field-label-edev"><label for="<?=$id?>_edit_ed_calend_sel"><?=GetMessage('EC_T_CALENDAR')?>:</label></span>
				<span class="bxec-field-val-2" >
				<select id="<?=$id?>_edit_ed_calend_sel"></select><span id="<?=$id?>_edit_sect_sel_warn" class="bxec-warn" style="display: none;"><?=GetMessage('EC_T_CALEN_DIS_WARNING')?></span>
				</span>
			</div>

			<!-- Color -->
			<div class="bxec-popup-row">
				<span class="bxec-field-label-edev" style="vertical-align:top;"><label for="<?=$id?>-event-color-inp"><?=GetMessage('EC_T_COLOR')?>:</label></span>
				<span  class="bxec-field-val-2" style="width: 300px;">
				<?CCalendarSceleton::DisplayColorSelector($id, 'event', $Params['colors']);?>
				</span>
			</div>
		</div>
		<?/* ####### END TAB 0 ####### */?>

		<?/* ####### TAB 1 : DESCRIPTION - LHE ####### */?>
		<div id="<?=$id?>ed-tab-1-cont" class="bxec-d-cont-div bxec-lhe">
		<?
		CModule::IncludeModule("fileman");
		$LHE = new CLightHTMLEditor;
		$LHE->Show(array(
			'id' => 'LHEEvDesc',
			'width' => '500',
			'height' => '285',
			'inputId' => $id.'_edit_ed_desc',
			'content' => '',
			'bUseFileDialogs' => false,
			'bFloatingToolbar' => false,
			'toolbarConfig' => array(
				'Bold', 'Italic', 'Underline', 'RemoveFormat',
				'CreateLink', 'DeleteLink', 'Image',
				'BackColor', 'ForeColor',
				'InsertOrderedList', 'InsertUnorderedList',
				'FontSizeList'
				,'Source'
			),
			'BBCode' => true,
			'jsObjName' => 'pLHEEvDesc',
			'bInitByJS' => true,
			'bSaveOnBlur' => false
		));
		?>
		</div>
		<?/* ####### END TAB 1 ####### */?>

		<?
		/* ####### TAB 2 : GUESTS ####### */
		if($Params['bSocNet']):?>
		<div id="<?=$id?>ed-tab-2-cont" class="bxec-d-cont-div">
			<div class="bxc-att-cont-cont">
				<a id="<?=$id?>_planner_link" href="javascript:void(0);" title="<?=GetMessage('EC_PLANNER_TITLE')?>" class="bxex-planner-link"><i></i><?=GetMessage('EC_PLANNER2')?></a>
			<?
			$isExtranetGroup = false;

			if ($Params["bSocNet"] && $Params["type"] == "group" && intval($Params["ownerId"]) > 0 && CModule::IncludeModule("extranet"))
				$isExtranetGroup = CExtranet::IsExtranetSocNetGroup($Params["ownerId"]);

			$APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", "", array(
					"MULTIPLE" => "Y",
					"NAME" => "BXCalUserSelect",
					"VALUE" => array(),
					"POPUP" => "Y",
					"ON_CHANGE" => "bxcUserSelectorOnchange",
					"NAME_TEMPLATE" => CCalendar::GetUserNameTemplate(),
					"SITE_ID" => SITE_ID,
					"SHOW_EXTRANET_USERS" => $isExtranetGroup ? "FROM_EXACT_GROUP" : "NONE",
					"EX_GROUP" => $isExtranetGroup ? $Params["ownerId"] : ""
				), null, array("HIDE_ICONS" => "Y")
			);
			?>
				<span class="bxc-add-guest-link"  id="<?=$id?>_user_control_link"></span>
				<div id="<?=$id?>_attendees_cont" class="bxc-attendees-cont">
					<div class="bxc-owner-cont">
						<div class="bxc-owner-cont">
							<span class="bxc-owner-title"><span><?= GetMessage('EC_EDEV_HOST')?>:</span></span>
							<span class="bxc-owner-value"><a id="<?=$id?>edit_host_link" href="javascript:void(0);"></a></span>
						</div>
					</div>
					<div class="bxc-no-att-notice"> - <?= GetMessage('EC_NO_ATTENDEES')?> - </div>
					<div class="bxc-att-title">
						<span><?= GetMessage('EC_EDEV_GUESTS')?>:</span>
						<div id="<?=$id?>_att_summary"></div>
					</div>
					<div class="bxc-att-cont" id="<?=$id?>_attendees_list" style="height: 200px;"></div>
				</div>
			</div>

			<div id="<?=$id?>_add_meeting_params">
				<div class="bxec-add-meet-text"><a id="<?=$id?>_add_meet_text" href="javascript:void(0);"><?=GetMessage('EC_ADD_METTING_TEXT')?></a></div>
				<div class="bxec-meet-text" id="<?=$id?>_meet_text_cont">
					<div class="bxec-mt-d"><?=GetMessage('EC_METTING_TEXT')?> (<a id="<?=$id?>_hide_meet_text" href="javascript:void(0);" title="<?=GetMessage('EC_HIDE_METTING_TEXT_TITLE')?>"><?=GetMessage('EC_HIDE')?></a>): </div><br />
					<textarea  class="bxec-mt-t" cols="63" id="<?=$id?>_meeting_text" rows="3"></textarea>
				</div>

				<div class="bxec-popup-row bxec-popup-row-checkbox">
					<input type="checkbox" id="<?=$id?>_ed_open_meeting" value="Y" />
					<label style="display: inline-block;" for="<?=$id?>_ed_open_meeting"><?=GetMessage('EC_OPEN_MEETING')?></label>
				</div>
				<div class="bxec-popup-row bxec-popup-row-checkbox">
					<input type="checkbox" id="<?=$id?>_ed_notify_status" value="Y"/>
					<label for="<?=$id?>_ed_notify_status"><?=GetMessage('EC_NOTIFY_STATUS')?></label>
				</div>
				<div class="bxec-popup-row bxec-popup-row-checkbox" id="<?=$id?>_ed_reivite_cont">
					<input type="checkbox" id="<?=$id?>_ed_reivite" value="Y"/>
					<label for="<?=$id?>_ed_reivite"><?=GetMessage('EC_REINVITE')?></label>
				</div>
			</div>
		</div>
		<?/* ####### END TAB 2 ####### */?>
		<?endif; /* bSocNet */?>

		<?/* ####### TAB 3 : ADDITIONAL INFO ####### */?>
		<div id="<?=$id?>ed-tab-3-cont" class="bxec-d-cont-div">
			<?if($Params['bSocNet']):?>
			<!-- Remind cont -->
			<div class="bxec-popup-row bxec-ed-meeting-vis" id="<?=$id?>_remind_cnt">
				<div class="bxec-popup-row-title"><?=GetMessage('EC_EDEV_REMINDER')?></div>
				<div>
					<input id="<?=$id?>_bxec_reminder" type="checkbox" value="Y">
					<label for="<?=$id?>_bxec_reminder"><?=GetMessage('EC_EDEV_REMIND_EVENT')?></label>
					<span id="<?=$id?>_bxec_rem_cont" style="display: none;">
					<?=GetMessage('EC_EDEV_FOR')?>
					<input id="<?=$id?>_bxec_rem_count" type="text" style="width: 30px" size="2">
					<select id="<?=$id?>_bxec_rem_type">
						<option value="min" selected="true"><?=GetMessage('EC_EDEV_REM_MIN')?></option>
						<option value="hour"><?=GetMessage('EC_EDEV_REM_HOUR')?></option>
						<option value="day"><?=GetMessage('EC_EDEV_REM_DAY')?></option>
					</select>
					<?=GetMessage('EC_JS_DE_VORHER')?>
					</span>
				</div>
			</div>
			<?endif;?>
			<table class="bxec-reminder-table" style="width: 100%;">
				<tr class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_T_REPEATING')?></td></tr>
				<?/* Repeat row start*/?>
				<tr id="<?=$id?>_edit_ed_rep_tr"  class="bxec-edit-ed-rep"><td class="bxec-edit-ed-repeat"><?=GetMessage('EC_T_REPEAT')?>:</td><td class="bxec-ed-lp">
				<select id="<?=$id?>_edit_ed_rep_sel">
					<option value="NONE"><?=GetMessage('EC_T_REPEAT_NONE')?></option>
					<option value="DAILY"><?=GetMessage('EC_T_REPEAT_DAILY')?></option>
					<option value="WEEKLY"><?=GetMessage('EC_T_REPEAT_WEEKLY')?></option>
					<option value="MONTHLY"><?=GetMessage('EC_T_REPEAT_MONTHLY')?></option>
					<option value="YEARLY"><?=GetMessage('EC_T_REPEAT_YEARLY')?></option>
				</select>
				<div id="<?=$id?>_edit_ed_repeat_sect" style="display: none; width: 310px;">
				<span id="<?=$id?>_edit_ed_rep_phrase1"></span>
				<select id="<?=$id?>_edit_ed_rep_count">
					<?for ($i = 1; $i < 36; $i++):?>
						<option value="<?=$i?>"><?=$i?></option>
					<?endfor;?>
				</select>
				<span id="<?=$id?>_edit_ed_rep_phrase2"></span>
				<br>
				<div id="<?=$id?>_edit_ed_rep_week_days" class="bxec-rep-week-days">
					<?for($i = 0; $i < 7; $i++):
						$id_ = $id.'bxec_week_day_'.$i;?>
					<input id="<?=$id_?>" type="checkbox" value="<?= $Params['week_days'][$i][2]?>">
					<label for="<?=$id_?>" title="<?=$Params['week_days'][$i][0]?>"><?=$Params['week_days'][$i][1]?></label>
					<?endfor;?>
				</div>
				<div>
					<label for="<?=$id_?>edit-ev-rep-diap-to" style="display: inline-block; margin: 3px 0 0 0; vertical-align:top;"><?=GetMessage('EC_T_DIALOG_STOP_REPEAT')?>:</label>
					<span class="bxec-rep2-inner"><input id="<?=$id?>edit-ev-rep-diap-to"  type="text"/></span>
				</div>
				</td></tr>
				<?/* Repeat row end*/?>

				<tr class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_EDDIV_SPECIAL_NOTES')?></td></tr>
				<tr>
					<td colspan="2">
					<?=GetMessage('EC_IMPORTANCE_TITLE')?>:
					<select id="<?=$id?>_bxec_importance">
						<option value="high" style="font-weight: bold;"><?=GetMessage('EC_IMPORTANCE_H')?></option>
						<option value="normal" selected="true"><?=GetMessage('EC_IMPORTANCE_N')?></option>
						<option value="low" style="color: #909090;"><?=GetMessage('EC_IMPORTANCE_L')?></option>
					</select>
					</td>
				</tr>
				<?if($Params['type'] == 'user'):?>
				<tr>
					<td colspan="2">
					<input id="<?=$id?>_bxec_private" type="checkbox" value="Y" title="<?=GetMessage('EC_PRIVATE_NOTICE')?>">
					<label for="<?=$id?>_bxec_private" title="<?=GetMessage('EC_PRIVATE_NOTICE')?>"><?=GetMessage('EC_PRIVATE_EVENT')?></label>
					<div style="font-size: 90%; color: #5D5D5D;"><?= GetMessage('EC_PRIVATE_NOTICE')?></div>
					</td>
				</tr>
				<?endif;?>
			</table>
			<div id="<?=$id?>bxec_uf_group" class="bxec-popup-row" style="display: none">
				<div class="bxec-popup-row-title"><?= GetMessage('EC_EDEV_ADD_TAB')?></div>
				<div id="<?=$id?>bxec_uf_cont"></div>
			</div>
		</div>
		<?/* ####### END TAB 3 ####### */?>
	</div>
</div>
<?
	}

	private static function DialogViewEvent($Params)
	{
		$id = $Params['id'];
		$arTabs = array(
			array('name' => GetMessage('EC_BASIC'), 'title' => GetMessage('EC_BASIC_TITLE'), 'id' => $id."view-tab-0", 'active' => true),
			array('name' => GetMessage('EC_T_DESC'), 'title' => GetMessage('EC_T_DESC_TITLE'), 'id' => $id."view-tab-1"),
			array('name' => GetMessage('EC_EDEV_ADD_TAB'), 'title' => GetMessage('EC_EDEV_ADD_TAB_TITLE'), 'id' => $id."view-tab-2")
		);
		?>
<div id="bxec_view_ed_<?=$id?>" class="bxec-popup">
	<div style="width: 500px; height: 1px;"></div>
	<div class="bxec-d-tabs" id="<?=$id?>_viewev_tabs">
		<?foreach($arTabs as $tab):?>
			<div class="bxec-d-tab <?if($tab['active'])echo'bxec-d-tab-act';?>" title="<?= (isset($tab['title']) ? $tab['title'] : $tab['name'])?>" id="<?=$tab['id']?>" <?if($tab['show'] === false)echo'style="display:none;"';?>>
				<b></b><div><span><?=$tab['name']?></span></div><i></i>
			</div>
		<?endforeach;?>
	</div>
	<div class="bxec-d-cont"  id="<?=$id?>_edit_sect_tabcont">
<?/* ####### TAB 0 : BASIC ####### */?>
<div id="<?=$id?>view-tab-0-cont" class="bxec-d-cont-div">
	<table style="width: 100%;">
		<tr><td align="left" class="bxec-ed-lp" style="height: 23px; width: 60px;"><?=GetMessage('EC_T_NAME')?>:</td><td class="bxec-ed-lp" style="width: 380px;"><div class="bxec-view-name" id="<?=$id?>view-name-cnt"></div></td></tr>
		<tr><td colSpan="2" class="bxec-view-ed-per" id="<?=$id?>view-period"></td></tr>
		<tr id="<?=$id?>view-repeat-cnt"><td class="bxec-par-name"><?=GetMessage('EC_T_REPEAT')?>:</td><td class="bxec-par-cont"></td></tr>
		<tr id="<?=$id?>view-loc-cnt"><td align="left" class="bxec-ed-lp" title="<?=GetMessage('EC_LOCATION_TITLE')?>" style="white-space: nowrap;"><?=GetMessage('EC_LOCATION')?>:</td><td class="bxec-ed-lp" style="padding-left: 5px;" id="<?=$id?>view-location"></td></tr>

		<?if($Params['bSocNet']):?>
		<tr id="<?=$id?>view-meet-text-cnt"><td class="bxec-par-name" colSpan="2"><span class="bxec-meet-text-lbl"><?=GetMessage('EC_MEETING_TEXT2')?>:</span><div class="bxec-vd-meet-text" id="<?=$id?>_view_ed_meet_text"></div></td></tr>

		<tr id="<?=$id?>attendees_cnt"><td colSpan="2" class="bxc-att-cell">
			<div id="<?=$id?>view_att_cont" class="bxc-attendees-cont bxc-attendees-cont-view">
				<div class="bxc-owner-cont">
					<span class="bxc-owner-title"><span><?= GetMessage('EC_EDEV_HOST')?>:</span></span>
					<span class="bxc-owner-value"><a id="<?=$id?>view_host_link" href="javascript:void(0);"></a></span>
				</div>
				<div class="bxc-no-att-notice"> - <?= GetMessage('EC_NO_ATTENDEES')?> - </div>
				<div class="bxc-att-title">
					<span><?= GetMessage('EC_EDEV_GUESTS')?>:</span>
					<div id="<?=$id?>view_att_summary"></div>
				</div>
				<div class="bxc-att-cont" id="<?=$id?>view_att_list"></div>
			</div>
		</td></tr>

		<tr id="<?=$id?>confirm_cnt"><td colSpan="2" class="bxc-confirm-row">
			<div id="<?=$id?>status-conf-cnt1" class="bxc-conf-cnt">
				<span><?= GetMessage('EC_ACCEPTED_STATUS')?></span>
				<a class="bxc-decline-link" href="javascript:void(0)" title="<?= GetMessage('EC_EDEV_CONF_N_TITLE')?>" id="<?=$id?>decline-link-1"><?= GetMessage('EC_EDEV_CONF_N')?></a>
			</div>

			<div id="<?=$id?>status-conf-cnt2" class="bxc-conf-cnt">
				<span class="popup-window-button popup-window-button-accept" id="<?=$id?>accept-link-2" title="<?= GetMessage('EC_EDEV_CONF_Y_TITLE')?>"><span class="popup-window-button-left"></span><span class="popup-window-button-text"><?= GetMessage('EC_ACCEPT_MEETING')?></span><span class="popup-window-button-right"></span></span>
				<a class="bxc-decline-link" href="javascript:void(0)" title="<?= GetMessage('EC_EDEV_CONF_N_TITLE')?>" id="<?=$id?>decline-link-2"><?= GetMessage('EC_EDEV_CONF_N')?></a>
			</div>

			<div id="<?=$id?>status-conf-cnt3" class="bxc-conf-cnt">
				<span class="bxc-conf-label" title="<?= GetMessage('EC_OPEN_MEETING_TITLE')?>"><?= GetMessage('EC_OPEN_MEETING')?>:</span>
				<span class="popup-window-button popup-window-button-accept" id="<?=$id?>accept-link-3" title="<?= GetMessage('EC_EDEV_CONF_Y_TITLE')?>"><span class="popup-window-button-left"></span><span class="popup-window-button-text"><?= GetMessage('EC_ACCEPT_MEETING')?></span><span class="popup-window-button-right"></span></span>
			</div>

			<div id="<?=$id?>status-conf-cnt4" class="bxc-conf-cnt">
				<span class="bxc-conf-label"><?= GetMessage('EC_DECLINE_INFO')?></span>. <a href="javascript:void(0)" title="<?= GetMessage('EC_ACCEPT_MEETING_2')?>" id="<?=$id?>accept-link-4"><?= GetMessage('EC_ACCEPT_MEETING')?></a>
				<span class="bxc-decline-notice" id="<?=$id?>decline-notice"><?= GetMessage('EC_DECLINE_NOTICE')?></span>
			</div>

			<div id="<?=$id?>status-conf-comment" class="bxc-conf-cnt">
				<span class="bxec-status-com">
					<input class="bxc-st-dis" type="text"  title="<?= GetMessage('EC_STATUS_COMMENT_TITLE')?>"  id="<?=$id?>conf-comment-inp" value=" - <?= GetMessage('EC_STATUS_COMMENT')?> - "/>
				</span>
			</div>
		</td></tr>
	<?endif; /*if($Params['bSocNet'])*/?>
	</table>
</div>
<?/* ####### END TAB 0 ####### */?>

<?/* ####### TAB 1 : DESCRIPTION ####### */?>
<div id="<?=$id?>view-tab-1-cont" class="bxec-d-cont-div">
	<div class="bxec-view-ed-desc-cont" id="<?=$id?>_view_ed_desc"></div>
</div>
<?/* ####### END TAB 1 ####### */?>

<?/* ####### TAB 2 : ADDITIONAL ####### */?>
<div id="<?=$id?>view-tab-2-cont" class="bxec-d-cont-div">
	<table style="width: 100%;">
		<tr id="<?=$id?>view-sect-cnt"><td class="bxec-par-name" colSpan="2"><?=GetMessage('EC_T_CALENDAR')?>:&nbsp;&nbsp;<span id="<?=$id?>view-ed-sect"></span></td></tr>
		<tr id="<?=$id?>view-spec-cnt" class="bxec-edev-ad-title"><td colSpan="2"><?=GetMessage('EC_EDDIV_SPECIAL_NOTES')?></td></tr>
		<tr id="<?=$id?>view-import-cnt"><td class="bxec-par-name"  colSpan="2"><?=GetMessage('EC_IMPORTANCE_TITLE')?>:&nbsp;&nbsp;<span id="<?=$id?>_view_ed_imp"></span></td></tr>
		<?if($Params['bIntranet']):?>
		<tr id="<?=$id?>view-accessab-cnt"><td class="bxec-par-name"  colSpan="2"><?=GetMessage('EC_ACCESSIBILITY_TITLE')?>:&nbsp;&nbsp;<span id="<?=$id?>_view_ed_accessibility"></span></td></tr>
		<tr id="<?=$id?>view-priv-cnt"><td class="bxec-par-name"  colSpan="2" style="font-weight: bold;"><?=GetMessage('EC_PRIVATE_EVENT')?></td></tr>
		<?endif;?>
	</table>
	<div id="<?=$id?>bxec_view_uf_group" class="bxec-popup-row" style="display: none;">
		<div class="bxec-popup-row-title"><?= GetMessage('EC_EDEV_ADD_TAB')?></div>
		<div id="<?=$id?>bxec_view_uf_cont"></div>
	</div>
</div>
<?/* ####### END TAB 2 ####### */?>
	</div>
</div>
<?
	}

	public static function DialogEditSection($Params)
	{
		$id = $Params['id'];
		$arTabs = array(
			array('name' => GetMessage('EC_SECT_BASE_TAB'), 'id' => $id."sect-tab-0", 'active' => true),
			array('name' => GetMessage('EC_SECT_ACCESS_TAB'), 'id' => $id."sect-tab-1")
		);
		?>

<div id="bxec_sect_d_<?=$id?>" class="bxec-popup">
	<div style="width: 500px; height: 1px;"></div>
	<div class="bxec-d-tabs" id="<?=$id?>_editsect_tabs">
		<?foreach($arTabs as $tab):?>
			<div class="bxec-d-tab <?if($tab['active'])echo'bxec-d-tab-act';?>" title="<?= (isset($tab['title']) ? $tab['title'] : $tab['name'])?>" id="<?=$tab['id']?>" <?if($tab['show'] === false)echo'style="display:none;"';?>>
				<b></b><div><span><?=$tab['name']?></span></div><i></i>
			</div>
		<?endforeach;?>
	</div>
	<div class="bxec-d-cont"  id="<?=$id?>_edit_sect_tabcont">
<?/* ####### TAB 0 : MAIN ####### */?>
<div id="<?=$id?>sect-tab-0-cont" class="bxec-d-cont-div">
	<!-- Title -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>_edcal_name"><?=GetMessage('EC_T_NAME')?>:</label></span>
		<span  class="bxec-field-val-2"><input type="text" id="<?=$id?>_edcal_name" style="width: 290px;"/></span>
	</div>
	<!-- Description -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>_edcal_desc"><?=GetMessage('EC_T_DESC')?>:</label></span>
		<span  class="bxec-field-val-2"><textarea cols="32" id="<?=$id?>_edcal_desc" rows="2" style="width: 290px; resize: none;"></textarea></span>
	</div>
	<!-- Color -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>-sect-color-inp"><?=GetMessage('EC_T_COLOR')?>:</label></span>
		<span  class="bxec-field-val-2" style="width: 300px;">
		<?CCalendarSceleton::DisplayColorSelector($id, 'sect', $Params['colors']);?>
		</span>
	</div>

	<?if($Params['type'] == 'user' && $Params['inPersonalCalendar']):?>
	<div class="bxec-popup-row" style="width: 480px;">
		<input id="<?=$id?>_bxec_meeting_calendar" type="checkbox" value="Y"><label for="<?=$id?>_bxec_meeting_calendar"><?=GetMessage('EC_MEETING_CALENDAR')?></label>
	</div>
	<?endif;?>

	<div class="bxec-popup-row">
		<input id="<?=$id?>_bxec_cal_exp_allow" type="checkbox" value="Y"><label for="<?=$id?>_bxec_cal_exp_allow"><?=GetMessage('EC_T_ALLOW_CALEN_EXP')?></label>
		<div id="<?=$id?>_bxec_calen_exp_div" style="margin-top: 4px;">
		<?=GetMessage('EC_T_CALEN_EXP_SET')?>:
		<select id="<?=$id?>_bxec_calen_exp_set">
			<option value="all"><?=GetMessage('EC_T_CALEN_EXP_SET_ALL')?></option>
			<option value="3_9"><?=GetMessage('EC_T_CALEN_EXP_SET_3_9')?></option>
			<option value="6_12"><?=GetMessage('EC_T_CALEN_EXP_SET_6_12')?></option>
		</select>
		</div>
	</div>

	<?if ($Params['bExchangeConnected'] && CCalendar::IsExchangeEnabled() && $Params['type'] == 'user' && $Params['inPersonalCalendar']):?>
	<div class="bxec-popup-row">
		<input id="<?=$id?>_bxec_cal_exch" type="checkbox" value="Y" checked="checked"><label for="<?=$id?>_bxec_cal_exch"><?=GetMessage('EC_CALENDAR_TO_EXCH')?></label>
	</div>
	<?endif;?>

	<?if($Params['bShowSuperpose'] && $Params['inPersonalCalendar']):?>
	<div class="bxec-popup-row" id="<?=$id?>_bxec_cal_add2sp_cont">
		<input id="<?=$id?>_bxec_cal_add2sp" type="checkbox" value="Y"><label for="<?=$id?>_bxec_cal_add2sp"><?=GetMessage('EC_T_ADD_TO_SP')?></label>
	</div>
	<?endif;?>
</div>
<?/* ####### END TAB 0 ####### */?>

<?/* ####### TAB 1 : ACCESS ####### */?>
<div id="<?=$id?>sect-tab-1-cont" class="bxec-d-cont-div">
	<? /*
	<?=GetMessage('EC_CAL_STATUS')?>:
	<select id="<?=$id?>_cal_priv_status" style="width: 230px">
		<option value="private" title="<?=GetMessage('EC_CAL_STATUS_PRIVATE')?>"><?=GetMessage('EC_CAL_STATUS_PRIVATE')?></option>
		<option value="time" title="<?=GetMessage('EC_CAL_STATUS_TIME')?>"><?=GetMessage('EC_CAL_STATUS_TIME')?></option>
		<option value="title" title="<?=GetMessage('EC_CAL_STATUS_TITLE')?>"><?=GetMessage('EC_CAL_STATUS_TITLE')?></option>
		<option value="full" selected="selected" title="<?=GetMessage('EC_CAL_STATUS_FULL')?>"><?=GetMessage('EC_CAL_STATUS_FULL')?></option>
	</select>
	*/?>

	<div class="bxec-popup-row">
		<div class="bxec-popup-row-title"><?= GetMessage('EC_SECT_ACCESS_TAB')?></div>
		<div class="bxec-access-cont-row">
			<div id="<?= $id?>access-values-cont" class="bxec-access-cont"></div>
			<?self::GetAccessHTML('calendar_section');?>
			<div class="bxec-access-link-cont"><a href="javascript:void(0);" id="<?= $id?>access-link" class="bxec-access-link"><?= GetMessage('EC_T_ADD')?></a></div>
		</div>
	</div>
</div>
<?/* ####### END TAB 1 ####### */?>
	</div>
</div>
<?
	}

	public static function DialogExportCalendar($Params)
	{
		$id = $Params['id'];
?>
<div id="bxec_excal_<?=$id?>" class="bxec-popup">
	<span id="<?=$id?>_excal_text"></span><br />
	<div class="bxec-exp-link-cont">
		<a href="javascript:void(0);" target="_blank" id="<?=$id?>_excal_link">&ndsp;</a>
		<span id="<?=$id?>_excal_warning" class="bxec-export-warning"><?=GetMessage('EC_EDEV_EXP_WARN')?></span>
	</div>
	<span>
		<a title="<?=GetMessage('EC_T_EXPORT_NOTICE_OUTLOOK_TITLE')?>" href="javascript:void(0);" id="<?=$id?>_excal_link_outlook"><?=GetMessage('EC_T_EXPORT_NOTICE_OUTLOOK_LINK')?></a>
		<div class="bxec-excal-notice-outlook"><?=GetMessage('EC_T_EXPORT_NOTICE_OUTLOOK')?></div>
	</span>
</div>
<?
	}

	public static function DialogSuperpose($Params)
	{
		global $APPLICATION;
		$id = $Params['id'];

		$arTypes = array(
			array("TITLE" => "EC_SUPERPOSE_GR_USER", "ID" => "user")
		);
?>
<div id="bxec_superpose_<?=$id?>" class="bxec-popup bxec-popup-sp-dialog">
	<div class="bxc-spd-type">
		<div class="bxc-spd-type-title" onclick="BX.toggleClass(this.parentNode, 'bxc-spd-type-collapsed');">
			<span class="bxc-spd-type-title-plus"></span>
			<span class="bxc-spd-type-title-inner"><?= GetMessage("EC_SUPERPOSE_GR_USER")?></span>
			<a href="javascript:void(0);" class="bxc-spd-del-cat" title="<?= GetMessage('EC_DELETE_ALL_USER_CALENDARS')?>" style="display: none;" id="bxec_sp_dell_all_sp_<?=$id?>"><?= GetMessage('EC_DELETE_DYN_SP_GROUP')?></a>
		</div>
		<div class="bxc-spd-type-cont" id="bxec_sp_type_user_cont_<?=$id?>"></div>
		<?
		$isExtranetGroup = false;

		if ($Params["bSocNet"] && $Params["type"] == "group" && intval($Params["ownerId"]) > 0 && CModule::IncludeModule("extranet"))
			$isExtranetGroup = CExtranet::IsExtranetSocNetGroup($Params["ownerId"]);

		$APPLICATION->IncludeComponent(
			"bitrix:intranet.user.selector.new", "", array(
				"MULTIPLE" => "Y",
				"NAME" => "BXCalUserSelectSP",
				"VALUE" => array(),
				"POPUP" => "Y",
				"ON_CHANGE" => "bxcUserSelectorOnchange",
				"NAME_TEMPLATE" => CCalendar::GetUserNameTemplate(),
				"SITE_ID" => SITE_ID,
				"SHOW_EXTRANET_USERS" => $isExtranetGroup ? "FROM_EXACT_GROUP" : "NONE",
				"EX_GROUP" => $isExtranetGroup ? $Params["ownerId"] : ""
			), null, array("HIDE_ICONS" => "Y")
		);
		?>
		<span class="bxc-add-guest-link bxc-add-guest-link-sp"  id="<?=$id?>_user_control_link_sp"><i></i><span><?=GetMessage('EC_USER_ADD_SP_TRACKING')?></span></span>
		<div id="<?=$id?>_sp_user_nf_notice" class="bxec-sprpose-users-nf"><?=GetMessage('EC_SP_DIALOG_USERS_NOT_FOUND')?></div>
	</div>
	<div class="bxc-spd-type" id="bxec_sp_type_group_<?=$id?>" style="display: none;">
		<div class="bxc-spd-type-title" onclick="BX.toggleClass(this.parentNode, 'bxc-spd-type-collapsed');">
			<span class="bxc-spd-type-title-plus"></span>
			<span class="bxc-spd-type-title-inner"><?= GetMessage("EC_SUPERPOSE_GR_GROUP")?></span>
		</div>
		<div class="bxc-spd-type-cont" id="bxec_sp_type_group_cont_<?=$id?>"></div>
	</div>
	<div  class="bxc-spd-type-com" id="bxec_sp_type_common_<?=$id?>" style="display: none;"></div>
</div>
<?
	}

	public static function DialogSettings($Params)
	{
		$id = $Params['id'];
		$arTabs = array(
			array('name' => GetMessage('EC_SET_TAB_PERSONAL'), 'title' => GetMessage('EC_SET_TAB_PERSONAL_TITLE'), 'id' => $id."set-tab-0"),
			array('name' => GetMessage('EC_SET_TAB_BASE'), 'title' => GetMessage('EC_SET_TAB_BASE_TITLE'), 'id' => $id."set-tab-1", 'show' => CCalendarType::CanDo('calendar_type_access', $Params['type'])),
			array('name' => GetMessage('EC_SECT_ACCESS_TAB'), 'title' => GetMessage('EC_SECT_ACCESS_TAB'), 'id' => $id."set-tab-2", 'show' => CCalendarType::CanDo('calendar_type_access', $Params['type']))
		);

		$arDays = self::GetWeekDays();
		$arWorTimeList = array();
		for ($i = 0; $i < 24; $i++)
		{
			$arWorTimeList[strval($i)] = CCalendar::FormatTime($i, 0);
			$arWorTimeList[strval($i).'.30'] = CCalendar::FormatTime($i, 30);
		}

		$bInPersonal = $Params['inPersonalCalendar'];
?>
<div id="bxec_uset_<?=$id?>" class="bxec-popup">
	<div style="width: 500px; height: 1px;"></div>
	<div class="bxec-d-tabs" id="<?=$id?>_set_tabs">
		<?foreach($arTabs as $tab):?>
			<div class="bxec-d-tab <?if($tab['active'])echo'bxec-d-tab-act';?>" title="<?= (isset($tab['title']) ? $tab['title'] : $tab['name'])?>" id="<?=$tab['id']?>" <?if($tab['show'] === false)echo'style="display:none;"';?>>
				<b></b><div><span><?=$tab['name']?></span></div><i></i>
			</div>
		<?endforeach;?>
	</div>
	<div class="bxec-d-cont"  id="<?=$id?>_set_tabcont">
<?/* ####### TAB 0 : PERSONAL ####### */?>
<div id="<?=$id?>set-tab-0-cont" class="bxec-d-cont-div">

	<!-- default meeting calendar -->
	<?if($bInPersonal):?>
	<div class="bxec-popup-row">
		<span class="bxec-field-label-3"><label for="<?=$id?>_uset_calend_sel"><?=GetMessage('EC_ADV_MEETING_CAL')?>:</label></span>
		<span  class="bxec-field-val-2">
			<select id="<?=$id?>_set_sect_sel"></select>
		</span>
	</div>

	<!-- blinking option -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-1"><input id="<?=$id?>_uset_blink" type="checkbox" /></span>
		<span  class="bxec-field-val-2">
			<label for="<?=$id?>_uset_blink"><?=GetMessage('EC_BLINK_SET')?></label>
		</span>
	</div>

	<!-- show banner -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-1"><input id="<?=$id?>_show_banner" type="checkbox" /></span>
		<span  class="bxec-field-val-2">
			<label for="<?=$id?>_show_banner"><?= GetMessage('EC_SHOW_BANNER', array('#DAV_EXAMPLE#' => CCalendar::IsBitrix24() ? 'CalDAV, MS Outlook' : 'Exchange, CalDAV, MS Outlook'))?></label>
		</span>
	</div>
	<!-- show declined -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-1"><input id="<?=$id?>_show_declined" type="checkbox" /></span>
		<span  class="bxec-field-val-2">
			<label for="<?=$id?>_show_declined"><?=GetMessage('EC_OPTION_SHOW_DECLINED')?></label>
		</span>
	</div>
	<?endif;/*if($bInPersonal)*/?>

	<!-- show declined -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-1"><input id="<?=$id?>_show_muted" type="checkbox" /></span>
		<span  class="bxec-field-val-2">
			<label for="<?=$id?>_show_muted"><?=GetMessage('EC_OPTION_SHOW_MUTED')?></label>
		</span>
	</div>

	<?if($Params['bShowSuperpose']):?>
	<div class="bxec-popup-row">
		<a id="<?=$id?>-set-manage-sp" href="javascript:void(0);" title="<?=GetMessage('EC_MANAGE_SP_CALENDARS_TITLE')?>"><?= GetMessage('EC_MANAGE_SP_CALENDARS')?></a>
	</div>
	<?endif;?>

	<?if($Params['bCalDAV'] && $bInPersonal):?>
	<div class="bxec-popup-row">
		<a id="<?=$id?>_manage_caldav" href="javascript:void(0);" title="<?=GetMessage('EC_MANAGE_CALDAV_TITLE')?>"><?= GetMessage('EC_MANAGE_CALDAV')?></a>
	</div>
	<?endif;?>

	<div class="bxec-popup-row">
		<a id="<?=$id?>_uset_clear" href="javascript:void(0);"><?= GetMessage('EC_CLEAR_PERS_SETTINGS')?></a>
	</div>
</div>
<?/* ####### END TAB 0 ####### */?>

<?/* ####### TAB 1 : CALENDAR SETTINGS ####### */?>
<div id="<?=$id?>set-tab-1-cont" class="bxec-d-cont-div">

	<!-- Work time -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-3"><label for="<?=$id?>work_time_start"><?= GetMessage("EC_WORK_TIME")?>:</label></span>
		<span  class="bxec-field-val-2">
			<select id="<?=$id?>work_time_start">
					<?foreach($arWorTimeList as $key => $val):?>
						<option value="<?= $key?>" <? if ($work_time_start == $key){echo ' selected="selected" ';}?>><?= $val?></option>
					<?endforeach;?>
				</select>
				&mdash;
				<select id="<?=$id?>work_time_end">
					<?foreach($arWorTimeList as $key => $val):?>
						<option value="<?= $key?>" <? if ($work_time_end == $key){echo ' selected="selected" ';}?>><?= $val?></option>
					<?endforeach;?>
				</select>
		</span>
	</div>

<!-- Week holidays -->
<div class="bxec-popup-row">
	<span class="bxec-field-label-3"><label for="<?=$id?>week_holidays"><?=GetMessage('EC_WEEK_HOLIDAYS')?>:</label></span>
	<span  class="bxec-field-val-2">
		<select size="7" multiple=true id="<?=$id?>week_holidays">
			<?foreach($arDays as $day):?>
				<option value="<?= $day[2]?>" ><?= $day[0]?></option>
			<?endforeach;?>
		</select>
	</span>
</div>
<!-- year holidays -->
<div class="bxec-popup-row">
	<span class="bxec-field-label-3"><label for="<?=$id?>year_holidays"><?=GetMessage('EC_YEAR_HOLIDAYS')?>:</label></span>
	<span  class="bxec-field-val-2"><input type="text" id="<?=$id?>year_holidays" value=""/></span>
</div>
<!-- year workdays -->
<div class="bxec-popup-row">
	<span class="bxec-field-label-3"><label for="<?=$id?>year_workdays"><?=GetMessage('EC_YEAR_WORKDAYS')?>:</label></span>
	<span  class="bxec-field-val-2"><input type="text" id="<?=$id?>year_workdays" value=""/></span>
</div>

<!-- week start -->
<div class="bxec-popup-row" style="display: none;">
	<span class="bxec-field-label-3"><label for="<?=$id?>week_start"><?=GetMessage('EC_WEEK_START')?>:</label></span>
	<span  class="bxec-field-val-2">
		<select id="<?=$id?>week_start">
			<?foreach($arDays as $day):?>
				<option value="<?= $day[2]?>" ><?= $day[0]?></option>
			<?endforeach;?>
		</select>
	</span>
</div>

<?if (!CCalendar::IsBitrix24()):?>
<div class="bxec-popup-row">
	<a href="/bitrix/admin/settings.php?mid=calendar&tabControl_active_tab=edit2" title="<?=GetMessage('EC_MANAGE_CALENDAR_TYPES_TITLE')?>" target="_blank"><?= GetMessage('EC_MANAGE_CALENDAR_TYPES')?></a>
</div>
<div class="bxec-popup-row">
	<a href="/bitrix/admin/settings.php?mid=calendar&tabControl_active_tab=edit1" title="<?=GetMessage('EC_MANAGE_SETTING_TITLE')?>" target="_blank"><?= GetMessage('EC_MANAGE_SETTING')?></a>
</div>
<?endif;?>

</div>
<?/* ####### END TAB 1 ####### */?>

<?/* ####### TAB 2 : PERMISSIONS ####### */?>
<div id="<?=$id?>set-tab-2-cont" class="bxec-d-cont-div">

	<div class="bxec-popup-row">
		<div class="bxec-popup-row-title"><?= GetMessage('EC_SECT_ACCESS_TAB')?></div>
		<div class="bxec-access-cont-row">
			<div id="<?= $id?>type-access-values-cont" class="bxec-access-cont"></div>
			<?self::GetAccessHTML('calendar_type');?>
			<div class="bxec-access-link-cont"><a href="javascript:void(0);" id="<?= $id?>type-access-link" class="bxec-access-link"><?= GetMessage('EC_T_ADD')?></a></div>
		</div>
	</div>

</div>
<?/* ####### END TAB 2 ####### */?>
	</div>
</div>
<?
	}

	public static function DialogExternalCalendars($Params)
	{
		$id = $Params['id'];
?>
<div id="bxec_cdav_<?=$id?>" class="bxec-popup">
	<div class="bxec-dav-list" id="<?=$id?>_bxec_dav_list"></div>
	<div class="bxec-dav-notice">
		<?= GetMessage('EC_CALDAV_NOTICE')?><br><?= GetMessage('EC_CALDAV_NOTICE_GOOGLE')?>
	</div>
	<div class="bxec-dav-new" id="<?=$id?>_bxec_dav_new">
		<table>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_name"><?= GetMessage('EC_ADD_CALDAV_NAME')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_name" value="" style="width: 420px;" size="47"/></td>
			</tr>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_link"><?= GetMessage('EC_ADD_CALDAV_LINK')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_link" value="" style="width: 420px;" size="47"/></td>
			</tr>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_username"><?= GetMessage('EC_ADD_CALDAV_USER_NAME')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_username" value="" style="width: 200px;" size="30"/></td>
			</tr>
			<tr>
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_password"><?= GetMessage('EC_ADD_CALDAV_PASS')?>:</label></td>
				<td class="bxec-dav-inp"><input type="password" id="<?=$id?>_bxec_dav_password" value="" style="width: 200px;" size="30"/></td>
			</tr>
		</table>
	</div>
</div>
<?
	}

	public static function DialogMobileCon($Params)
	{
		$id = $Params['id'];
?>
<div id="bxec_mobile_<?=$id?>" class="bxec-popup" style="width: 560px;">
	<div class="bxec-mobile-cont">
		<div class="bxec-mobile-header"><?= GetMessage('EC_MOBILE_HELP_HEADER');?></div>
		<a id="bxec_mob_link_iphone_<?=$id?>" class="bxec-mobile-link bxec-link-hidden" href="javascript: void(0)"><div class="bxec-iconkit bxec-arrow"></div><?= GetMessage('EC_MOBILE_APPLE');?></a>
		<div id="bxec_mobile_iphone_all<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_IPHONE_ALL_HELP', array('#POINT_SET_PORT#' => GetMessage(CCalendar::IsBitrix24() ? 'EC_SET_PORT_BX24' : 'EC_SET_PORT')))?></div>
		<div id="bxec_mobile_iphone_one<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_IPHONE_ONE_HELP', array('#POINT_SET_PORT#' => GetMessage(CCalendar::IsBitrix24() ? 'EC_SET_PORT_BX24' : 'EC_SET_PORT')))?></div>
		<a id="bxec_mob_link_mac_<?=$id?>" class="bxec-mobile-link bxec-link-hidden" href="javascript: void(0)"><div class="bxec-iconkit bxec-arrow"></div><?= GetMessage('EC_MOBILE_MAC_OS');?></a>
		<div id="bxec_mobile_mac_cont<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_MAC', array('#POINT_SET_PORT#' => GetMessage(CCalendar::IsBitrix24() ? 'EC_SET_PORT_BX24' : 'EC_SET_PORT')))?></div>
		<a id="bxec_mob_link_bird_<?=$id?>" class="bxec-mobile-link bxec-link-hidden" href="javascript: void(0)"><div class="bxec-iconkit bxec-arrow"></div><?= GetMessage('EC_MOBILE_SUNBIRD');?></a>
		<div id="bxec_mobile_sunbird_all<?=$id?>" style="display: none;"><?= GetMessage("EC_MOBILE_HELP_SUNBIRD_ALL_HELP")?></div>
		<div id="bxec_mobile_sunbird_one<?=$id?>" style="display: none;"><?= GetMessage("EC_MOBILE_HELP_SUNBIRD_ONE_HELP")?></div>
	</div>
</div>
<?
	}

	public static function GetWeekDays()
	{
		return array(
			array(GetMessage('EC_MON_F'), GetMessage('EC_MON'), 'MO'),
			array(GetMessage('EC_TUE_F'), GetMessage('EC_TUE'), 'TU'),
			array(GetMessage('EC_WEN_F'), GetMessage('EC_WEN'), 'WE'),
			array(GetMessage('EC_THU_F'), GetMessage('EC_THU'), 'TH'),
			array(GetMessage('EC_FRI_F'), GetMessage('EC_FRI'), 'FR'),
			array(GetMessage('EC_SAT_F'), GetMessage('EC_SAT'), 'SA'),
			array(GetMessage('EC_SAN_F'), GetMessage('EC_SAN'), 'SU')
		);
	}

	public static function GetWeekDaysEx($weekStart = 'MO')
	{
		$days = self::GetWeekDays();
		if ($weekStart == 'MO')
			return $days;
		$res = array();
		$start = false;
		while(list($k, $day) = each($days))
		{
			if ($day[2] == $weekStart)
			{
				$start = !$start;
				if (!$start)
					break;
			}
			if ($start)
				$res[] = $day;

			if ($start && $k == 6)
				reset($days);
		}
		return $res;
	}

	public static function GetAccessHTML($binging = 'calendar_section', $id = false)
	{
		if ($id === false)
			$id = 'bxec-'.$binging;
		$arTasks = CCalendar::GetAccessTasks($binging);
		?>
		<span style="display:none;">
		<select id="<?= $id?>" class="bxec-task-select">
			<?foreach ($arTasks as $taskId => $task):?>
				<option value="<?=$taskId?>"><?= htmlspecialcharsex($task['title']);?></option>
			<?endforeach;?>
		</select>
		</span>
		<?
	}

	public static function GetUserfieldsEditHtml($eventId, $url = '')
	{
		global $USER_FIELD_MANAGER, $APPLICATION;
		$USER_FIELDS = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $eventId, LANGUAGE_ID);
		if (!$USER_FIELDS || count($USER_FIELDS) == 0)
			return;

		$url = CHTTP::urlDeleteParams($url, array("action", "sessid", "bx_event_calendar_request", "event_id", "reqId"));
		$url = $url.(strpos($url,'?') === false ? '?' : '&').'action=userfield_save&bx_event_calendar_request=Y&'.bitrix_sessid_get();
?>
<form method="post" name="calendar-event-uf-form<?=$eventId?>" action="<?= $url?>" enctype="multipart/form-data" encoding="multipart/form-data">
<input name="event_id" type="hidden" value="" />
<input name="reqId" type="hidden" value="" />
<table cellspacing="0" class="bxc-prop-layout">
	<?foreach ($USER_FIELDS as $arUserField):?>
		<tr>
			<td class="bxc-prop"><?= htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</td>
			<td class="bxc-prop">
				<?$APPLICATION->IncludeComponent(
					"bitrix:system.field.edit",
					$arUserField["USER_TYPE"]["USER_TYPE_ID"],
					array(
						"bVarsFromForm" => false,
						"arUserField" => $arUserField,
						"form_name" => "calendar-event-uf-form".$eventId
					), null, array("HIDE_ICONS" => "Y")
				);?>
			</td>
		</tr>
	<?endforeach;?>
</table>
</form>
<?
	}

	public static function GetUserfieldsViewHtml($eventId)
	{
		global $USER_FIELD_MANAGER, $APPLICATION;
		$USER_FIELDS = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $eventId, LANGUAGE_ID);
		if (!$USER_FIELDS || count($USER_FIELDS) == 0)
			return;
		$bFound = false;

		foreach ($USER_FIELDS as $arUserField)
		{
			if ($arUserField['VALUE'] == "" || (is_array($arUserField['VALUE']) && !count($arUserField['VALUE'])))
				continue;

			if (!$bFound)
			{
				$bFound = true;
				?><table cellspacing="0" class="bxc-prop-layout"><?
			}
			?>

			<tr>
				<td class="bxc-prop-name"><?= htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</td>
				<td class="bxc-prop-value">
					<?$APPLICATION->IncludeComponent(
						"bitrix:system.field.view",
						$arUserField["USER_TYPE"]["USER_TYPE_ID"],
						array("arUserField" => $arUserField),
						null,
						array("HIDE_ICONS"=>"Y")
					);?>
				</td>
			</tr>
		<?
		}

		if ($bFound)
		{
			?></table><?
		}
	}

	public static function DisplayColorSelector($id, $key = 'sect', $colors)
	{
		?>
		<div  class="bxec-color-inp-cont">
			<input class="bxec-color-inp" id="<?=$id?>-<?=$key?>-color-inp"/>
			<a  id="<?=$id?>-<?=$key?>-text-color-inp" href="javascript:void('');" class="bxec-color-text-link"><?= GetMessage('EC_TEXT_COLOR')?></a>
		</div>
		<div class="bxec-color-cont" id="<?=$id?>-<?=$key?>-color-cont">
		<?foreach($colors as $i => $color):?><span class="bxec-color-it"><a id="<?=$id?>-<?=$key?>-color-<?=$i?>" style="background-color:<?= $color?>" href="javascript:void(0);"></a></span><?endforeach;?>
		</div>
		<?
	}
}
?>