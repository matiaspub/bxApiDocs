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
		<?if ($Params['bShowSections'] || $Params['bShowSuperpose']):?>
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
		<?if ($Params['syncPannel']):?>
		<div class="bxec-sect-cont-inner">
			<div class="bxec-sect-title">
				<span class="bxec-sect-title-text"><?= GetMessage('EC_CAL_SYNC_TITLE')?></span>
			</div>
			<div class="bxec-sect-cont-white" id="<?=$id?>-sync-inner-wrap"></div>
		</div>
		<?endif; /*syncPannel*/ ?>
		<span class="bxec-access-settings-wrap" id="<?=$id?>-access-settings-wrap">
			<a hidefocus="true" href="javascript:void(0);" class="bxec-access-settings" id="<?=$id?>-access-settings"><?=GetMessage('EC_CAL_ACCESS_SETTINGS')?></a>
		</span>
	<i class="r0"></i><i class="r1"></i><i class="r2"></i>
</div>
		<?endif; /* bShowSections || bShowSuperpose*/?>


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
		<tr class="bxec-days-tbl-title"><td class="bxec-pad"><div class="bxec-day-t-event-holder"></div><img src="/bitrix/images/1.gif" width="40" height="1" /></td></tr>
		<tr class="bxec-days-tbl-more-ev"><td class="bxec-pad"></td></tr>
		<tr class="bxec-days-tbl-grid"><td class="bxec-cont" colSpan="2"><div class="bxec-timeline-div"></div></td></tr>
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

		// Here can be added user's dialogs, scripts, html
		foreach(GetModuleEvents("calendar", "OnAfterBuildSceleton", true) as $arEvent)
			ExecuteModuleEventEx($arEvent);
	}

	public static function InitJS($JSConfig)
	{
		global $APPLICATION;
		CUtil::InitJSCore(array('ajax', 'window', 'popup', 'access', 'date', 'viewer', 'socnetlogdest'));

		$JSConfig['days'] = self::GetWeekDays();
		$JSConfig['month'] = array(GetMessage('EC_JAN'), GetMessage('EC_FEB'), GetMessage('EC_MAR'), GetMessage('EC_APR'), GetMessage('EC_MAY'), GetMessage('EC_JUN'), GetMessage('EC_JUL'), GetMessage('EC_AUG'), GetMessage('EC_SEP'), GetMessage('EC_OCT'), GetMessage('EC_NOV'), GetMessage('EC_DEC'));
		$JSConfig['month_r'] = array(GetMessage('EC_JAN_R'), GetMessage('EC_FEB_R'), GetMessage('EC_MAR_R'), GetMessage('EC_APR_R'), GetMessage('EC_MAY_R'), GetMessage('EC_JUN_R'), GetMessage('EC_JUL_R'), GetMessage('EC_AUG_R'), GetMessage('EC_SEP_R'), GetMessage('EC_OCT_R'), GetMessage('EC_NOV_R'), GetMessage('EC_DEC_R'));

		$APPLICATION->SetAdditionalCSS("/bitrix/js/calendar/cal-style.css");
		// Add scripts
		$arJS = array(
			'/bitrix/js/calendar/cal-core.js',
			'/bitrix/js/calendar/cal-dialogs.js',
			'/bitrix/js/calendar/cal-week.js',
			'/bitrix/js/calendar/cal-events.js',
			'/bitrix/js/calendar/cal-controlls.js'
		);

		// Drag & drop
		$arJS[] = '/bitrix/js/main/dd.js';

		for($i = 0, $l = count($arJS); $i < $l; $i++)
		{
			$APPLICATION->AddHeadScript($arJS[$i]);
		}

		?>
		<script type="text/javascript">
		<?self::Localization();?>

		BX.ready(function(){
			new JCEC(<?= CUtil::PhpToJSObject($JSConfig)?>);
		});
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
			self::DialogEditSection($Params);
			self::DialogExternalCalendars($Params);
		}
		self::DialogSettings($Params);
		self::DialogExportCalendar($Params);
		self::DialogMobileCon($Params);

		if ($Params['bShowSuperpose'])
			self::DialogSuperpose($Params);
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
			'UserSettings' => 'EC_SET_TAB_PERSONAL_TITLE',
			'ClearUserSetConf' => 'EC_CLEAR_SET_CONFIRM',
			'Adjust' => 'EC_ADD_EX_CAL',
			'ItIsYou' => 'EC_IT_IS_YOU',
			'DefaultColor' => 'EC_DEFAULT_COLOR',
			'SPCalendars' => 'EC_T_SP_CALENDARS',
			'NoCalendarsAlert' => 'EC_NO_CALENDARS_ALERT',
			'EventMRCheckWarn' => 'EC_MR_CHECK_PERIOD_WARN',
			'CalDavConWait' => 'EC_CAL_DAV_CON_WAIT',
			'Refresh' => 'EC_CAL_DAV_REFRESH',
			'acc_status_absent' => 'EC_PRIVATE_ABSENT',
			'acc_status_busy' => 'EC_ACCESSIBILITY_B',
			'denyRepeted' => 'EC_DD_DENY_REPEATED',
			'ddDenyRepeted' => 'EC_DD_DENY_REPEATED',
			'ddDenyTask' => 'EC_DD_DENY_TASK',
			'ddDenyEvent' => 'EC_DD_DENY_EVENT',
			'eventTzHint' => 'EC_EVENT_TZ_HINT',
			'eventTzDefHint' => 'EC_EVENT_TZ_DEF_HINT',
			'reservePeriodWarn' => 'EC_RESERVE_PERIOD_WARN',
			'OpenCalendar' => 'EC_CAL_OPEN_LINK',
			'accessSettingsWarn' => 'EC_CAL_ACCESS_SETTINGS_WARN',
			'googleHide' => 'EC_CAL_GOOGLE_HIDE',
			'googleHideConfirm' => 'EC_CAL_GOOGLE_HIDE_CONFIRM',
			'googleDisconnectConfirm' => 'EC_CAL_REMOVE_GOOGLE_SYNC_CONFIRM',
			'syncConnect' => 'EC_CAL_SYNC_CONNECT',
			'syncDisconnect' => 'EC_CAL_SYNC_DISCONNECT',
			'syncMac' => 'EC_CAL_SYNC_MAC',
			'syncIphone' => 'EC_CAL_SYNC_IPHONE',
			'syncAndroid' => 'EC_CAL_SYNC_ANDROID',
			'syncOutlook' => 'EC_CAL_SYNC_OUTLOOK',
			'syncOffice365' => 'EC_CAL_SYNC_OFFICE_365',
			'syncGoogle' => 'EC_CAL_SYNC_GOOGLE',
			'syncExchange' => 'EC_CAL_SYNC_EXCHANGE',
			'syncOk' => 'EC_CAL_SYNC_OK',
			'connectMore' => 'EC_CAL_CONNECT_MORE',
			'showLess' => 'EC_CAL_SHOW_LESS',
			'SyncTitleMacOSX' => 'EC_MOBILE_SYNC_TITLE_MACOSX',
			'SyncTitleIphone' => 'EC_MOBILE_SYNC_TITLE_IPHONE',
			'SyncTitleAndroid' => 'EC_MOBILE_SYNC_TITLE_ANDROID',
			'disconnectOutlook' => 'EC_CAL_DISCONNECT_OUTLOOK',
			'disconnectIphone' => 'EC_CAL_DISCONNECT_IPHONE',
			'disconnectMac' => 'EC_CAL_DISCONNECT_MAC',
			'disconnectAndroid' => 'EC_CAL_DISCONNECT_ANDROID',
			'connectExchange' => 'EC_CAL_CONNECT_EXCHANGE',
			'disconnectExchange' => 'EC_CAL_DISCONNECT_EXCHANGE',
			'syncExchangeTitle' => 'EC_BAN_EXCH_SYNC_TITLE'
		);
?>
var EC_MESS = {
	0:0<?
		foreach($arLangMess as $m1 => $m2)
		{
			echo ', '.$m1." : '".GetMessageJS($m2)."'";
		}
	?>};<?
	}

	private static function DialogAddEventSimple($Params)
	{
		global $APPLICATION;
		$APPLICATION->IncludeComponent("bitrix:calendar.event.simple.add", "", $Params);
	}

	public static function DialogEditEvent($Params)
	{
		if (CCalendarSceleton::CheckBitrix24Limits($Params))
		{
			global $APPLICATION;
			$APPLICATION->IncludeComponent("bitrix:calendar.event.edit", "", $Params);
		}
	}

	public static function DialogViewEvent($Params)
	{
		if (CCalendarSceleton::CheckBitrix24Limits($Params))
		{
			global $APPLICATION;
			$APPLICATION->IncludeComponent("bitrix:calendar.event.view", "", $Params);
		}
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
	<div style="width: 560px; height: 1px;"></div>
	<div class="popup-window-tabs" id="<?=$id?>_editsect_tabs">
		<?foreach($arTabs as $tab):?>
			<span class="popup-window-tab<? if($tab['active']) echo ' popup-window-tab-selected';?>" title="<?= (isset($tab['title']) ? $tab['title'] : $tab['name'])?>" id="<?=$tab['id']?>" <?if($tab['show'] === false)echo'style="display:none;"';?>>
				<?=$tab['name']?>
			</span>
		<?endforeach;?>
	</div>
	<div class="popup-window-tabs-content">
<?/* ####### TAB 0 : MAIN ####### */?>
<div id="<?=$id?>sect-tab-0-cont" class="popup-window-tab-content popup-window-tab-content-selected">
	<!-- Title -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>_edcal_name"><?=GetMessage('EC_T_NAME')?>:</label></span>
		<span  class="bxec-field-val-2"><input type="text" id="<?=$id?>_edcal_name" style="width: 350px;"/></span>
	</div>
	<!-- Description -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>_edcal_desc"><?=GetMessage('EC_T_DESC')?>:</label></span>
		<span  class="bxec-field-val-2"><textarea cols="32" id="<?=$id?>_edcal_desc" rows="2" style="width: 350px; resize: none;"></textarea></span>
	</div>
	<!-- Color -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-2"><label for="<?=$id?>-sect-color-inp"><?=GetMessage('EC_T_COLOR')?>:</label></span>
		<span  class="bxec-field-val-2" style="width: 360px;">
		<?CCalendarSceleton::DisplayColorSelector($id, 'sect', $Params['colors']);?>
		</span>
	</div>

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
<div id="<?=$id?>sect-tab-1-cont" class="popup-window-tab-content">
	<div class="bxec-popup-row">
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
	<span class="bxec-excal-notice-hide">
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
			array('name' => GetMessage('EC_SET_TAB_BASE'), 'title' => GetMessage('EC_SET_TAB_BASE_TITLE'), 'id' => $id."set-tab-1", 'show' => CCalendarType::CanDo('calendar_type_edit_access', $Params['type'])),
			array('name' => GetMessage('EC_SECT_ACCESS_TAB'), 'title' => GetMessage('EC_SECT_ACCESS_TAB'), 'id' => $id."set-tab-2", 'show' => CCalendarType::CanDo('calendar_type_edit_access', $Params['type']))
		);

		$arDays = self::GetWeekDays();
		$arWorTimeList = array();
		for ($i = 0; $i < 24; $i++)
		{
			$arWorTimeList[strval($i)] = CCalendar::FormatTime($i, 0);
			$arWorTimeList[strval($i).'.30'] = CCalendar::FormatTime($i, 30);
		}

		$timezoneList = CCalendar::GetTimezoneList();
		$bInPersonal = $Params['inPersonalCalendar'];
?>
<div id="bxec_uset_<?=$id?>" class="bxec-popup">
	<div style="width: 500px; height: 1px;"></div>
	<div class="popup-window-tabs" id="<?=$id?>_set_tabs">
		<?foreach($arTabs as $tab):?>
			<span class="popup-window-tab<? if($tab['active']) echo ' popup-window-tab-selected';?>" title="<?= (isset($tab['title']) ? $tab['title'] : $tab['name'])?>" id="<?=$tab['id']?>" <?if($tab['show'] === false)echo'style="display:none;"';?>>
				<?=$tab['name']?>
			</span>
		<?endforeach;?>
	</div>
	<div class="popup-window-tabs-content"  id="<?=$id?>_set_tabcont">
<?/* ####### TAB 0 : PERSONAL ####### */?>
<div id="<?=$id?>set-tab-0-cont" class="popup-window-tab-content popup-window-tab-content-selected">

	<!-- default meeting calendar -->
	<?if($bInPersonal):?>
	<div class="bxec-popup-row">
		<span class="bxec-field-label-3"><label for="<?=$id?>_set_tz_sel"><?=GetMessage('EC_TIMEZONE')?>:</label></span>
		<span  class="bxec-field-val-2">
			<select id="<?=$id?>_set_tz_sel" style="max-width: 235px;">
				<option value=""> - </option>
				<?foreach($timezoneList as $tz):?>
					<option value="<?= $tz['timezone_id']?>"><?= htmlspecialcharsEx($tz['title'])?></option>
				<?endforeach;?>
			</select>
		</span>
	</div>
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
<div id="<?=$id?>set-tab-1-cont" class="popup-window-tab-content">

	<!-- Work time -->
	<div class="bxec-popup-row">
		<span class="bxec-field-label-3"><label for="<?=$id?>work_time_start"><?= GetMessage("EC_WORK_TIME")?>:</label></span>
		<span  class="bxec-field-val-2">
			<select id="<?=$id?>work_time_start">
					<?foreach($arWorTimeList as $key => $val):?>
						<option value="<?= $key?>"><?= $val?></option>
					<?endforeach;?>
				</select>
				&mdash;
				<select id="<?=$id?>work_time_end">
					<?foreach($arWorTimeList as $key => $val):?>
						<option value="<?= $key?>"><?= $val?></option>
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
<div id="<?=$id?>set-tab-2-cont" class="popup-window-tab-content">

	<div class="bxec-popup-row">
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
	<?/*
	<div class="bxec-dav-notice">
		<?= GetMessage('EC_CALDAV_NOTICE')?><br><?= GetMessage('EC_CALDAV_NOTICE_GOOGLE')?>
	</div>
	*/?>
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
			<tr id="<?=$id?>_bxec_dav_username_cont">
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_username"><?= GetMessage('EC_ADD_CALDAV_USER_NAME')?>:</label></td>
				<td class="bxec-dav-inp"><input type="text" id="<?=$id?>_bxec_dav_username" value="" style="width: 200px;" size="30"/></td>
			</tr>
			<tr id="<?=$id?>_bxec_dav_password_cont">
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_password"><?= GetMessage('EC_ADD_CALDAV_PASS')?>:</label></td>
				<td class="bxec-dav-inp"><input type="password" id="<?=$id?>_bxec_dav_password" value="" style="width: 200px;" size="30"/></td>
			</tr>
			<tr id="<?=$id?>_bxec_dav_sections_cont">
				<td class="bxec-dav-lab"><label for="<?=$id?>_bxec_dav_sections"><?= GetMessage('EC_ADD_CALDAV_SECTIONS')?>:</label></td>
				<td class="bxec-dav-inp" id="<?=$id?>_bxec_dav_sections"></td>
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
		<div id="bxec-sync-iphone-<?=$id?>"><?= GetMessage('EC_MOBILE_HELP_IPHONE', array('#POINT_SET_PORT#' => GetMessage('EC_SET_PORT')))?></div>
		<div id="bxec-sync-mac-<?=$id?>"><?= GetMessage('EC_MOBILE_HELP_MAC', array('#POINT_SET_PORT#' => GetMessage('EC_SET_PORT')))?></div>
		<div id="bxec-sync-android-<?=$id?>"><?= GetMessage("EC_MOBILE_HELP_ANDROID")?></div>
	</div>
</div>
<?
	}

	public static function GetWeekDays()
	{
		return array(
			array(GetMessage('EC_MO_F'), GetMessage('EC_MO'), 'MO'),
			array(GetMessage('EC_TU_F'), GetMessage('EC_TU'), 'TU'),
			array(GetMessage('EC_WE_F'), GetMessage('EC_WE'), 'WE'),
			array(GetMessage('EC_TH_F'), GetMessage('EC_TH'), 'TH'),
			array(GetMessage('EC_FR_F'), GetMessage('EC_FR'), 'FR'),
			array(GetMessage('EC_SA_F'), GetMessage('EC_SA'), 'SA'),
			array(GetMessage('EC_SU_F'), GetMessage('EC_SU'), 'SU')
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

	public static function DisplayColorSelector($id, $key = 'sect', $colors = false)
	{
		if (!$colors)
		{
			$colors = array(
				'#DAA187','#78D4F1','#C8CDD3','#43DAD2','#EECE8F','#AEE5EC','#B6A5F6','#F0B1A1','#82DC98','#EE9B9A',
				'#B47153','#2FC7F7','#A7ABB0','#04B4AB','#FFA801','#5CD1DF','#6E54D1','#F73200','#29AD49','#FE5957'
			);
		}

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

	public static function __ShowAttendeesDestinationHtml($Params = array())
	{
		CSocNetTools::InitGlobalExtranetArrays();

		$id = $Params['id'];
		$DESTINATION = CCalendar::GetSocNetDestination(false, $Params['event']['ATTENDEES_CODES']);
		?>
		<div id="event-grid-att<?= $id?>" class="event-grid-dest-block">
			<div class="event-grid-dest-wrap-outer">
				<div class="event-grid-dest-label"><?=GetMessage("EC_EDEV_GUESTS")?>:</div>
				<div class="event-grid-dest-wrap" id="event-grid-dest-cont">
					<span id="event-grid-dest-item"></span>
					<span class="feed-add-destination-input-box" id="event-grid-dest-input-box">
						<input type="text" value="" class="feed-add-destination-inp" id="event-grid-dest-input">
					</span>
					<a href="#" class="feed-add-destination-link" id="event-grid-dest-add-link"></a>
					<script>
						<?
						if (is_array($GLOBALS["arExtranetGroupID"]))
						{
							?>
							if (typeof window['arExtranetGroupID'] == 'undefined')
							{
								window['arExtranetGroupID'] = <?=CUtil::PhpToJSObject($GLOBALS["arExtranetGroupID"])?>;
							}
							<?
						}
						?>
						BX.message({
							'BX_FPD_LINK_1':'<?=GetMessageJS("EC_DESTINATION_1")?>',
							'BX_FPD_LINK_2':'<?=GetMessageJS("EC_DESTINATION_2")?>'
						});
						window.editEventDestinationFormName = top.editEventDestinationFormName = 'edit_event_<?=randString(6)?>';
						//
						BX.SocNetLogDestination.init({
							name : editEventDestinationFormName,
							searchInput : BX('event-grid-dest-input'),
							extranetUser :  false,
							userSearchArea: 'I',
							bindMainPopup : { 'node' : BX('event-grid-dest-cont'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
							bindSearchPopup : { 'node' : BX('event-grid-dest-cont'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
							callback : {
								select : BxEditEventGridSelectCallback,
								unSelect : BxEditEventGridUnSelectCallback,
								openDialog : BxEditEventGridOpenDialogCallback,
								closeDialog : BxEditEventGridCloseDialogCallback,
								openSearch : BxEditEventGridOpenDialogCallback,
								closeSearch : BxEditEventGridCloseSearchCallback
							},
							items : {
								users : <?=(empty($DESTINATION['USERS'])? '{}': CUtil::PhpToJSObject($DESTINATION['USERS']))?>,
								groups : <?=(
									$DESTINATION["EXTRANET_USER"] == 'Y'
								|| (array_key_exists("DENY_TOALL", $DESTINATION) && $DESTINATION["DENY_TOALL"])
									? '{}'
									: "{'UA' : {'id':'UA','name': '".(!empty($DESTINATION['DEPARTMENT']) ? GetMessageJS("MPF_DESTINATION_3"): GetMessageJS("MPF_DESTINATION_4"))."'}}"
								)?>,
								sonetgroups : <?=(empty($DESTINATION['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($DESTINATION['SONETGROUPS']))?>,
								department : <?=(empty($DESTINATION['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($DESTINATION['DEPARTMENT']))?>,
								departmentRelation : <?=(empty($DESTINATION['DEPARTMENT_RELATION'])? '{}': CUtil::PhpToJSObject($DESTINATION['DEPARTMENT_RELATION']))?>
							},
							itemsLast : {
								users : <?=(empty($DESTINATION['LAST']['USERS'])? '{}': CUtil::PhpToJSObject($DESTINATION['LAST']['USERS']))?>,
								sonetgroups : <?=(empty($DESTINATION['LAST']['SONETGROUPS'])? '{}': CUtil::PhpToJSObject($DESTINATION['LAST']['SONETGROUPS']))?>,
								department : <?=(empty($DESTINATION['LAST']['DEPARTMENT'])? '{}': CUtil::PhpToJSObject($DESTINATION['LAST']['DEPARTMENT']))?>,
								groups : <?=($DESTINATION["EXTRANET_USER"] == 'Y'? '{}': "{'UA':true}")?>
							},
							itemsSelected : <?=(empty($DESTINATION['SELECTED'])? '{}': CUtil::PhpToJSObject($DESTINATION['SELECTED']))?>,
							destSort : <?=(empty($DESTINATION['DEST_SORT'])? '{}': CUtil::PhpToJSObject($DESTINATION['DEST_SORT']))?>
						});
					</script>
				</div>
			</div>

			<!-- Meeting host -->
			<div class="event-grid-host-cont">
				<span class="event-grid-host-cont-label"><?= GetMessage('EC_EDEV_HOST')?>:</span>
				<a title="<?= htmlspecialcharsbx($Params['host']['DISPLAY_NAME'])?>" href="<?= $Params['host']['URL']?>" target="_blank" class="bxcal-user"><span class="bxcal-user-avatar-outer"><span class="bxcal-user-avatar"><img src="<?= $Params['host']['AVATAR_SRC']?>" width="<?= $Params['AVATAR_SIZE']?>" height="<?= $Params['AVATAR_SIZE']?>" /></span></span><span class="bxcal-user-name"><?= htmlspecialcharsbx($Params['host']['DISPLAY_NAME'])?></span></a>

			</div>

			<!-- Attendees cont -->
			<div class="event-grid-attendees-cont">
				<div id="event-edit-att-y" class="event-grid-attendees-cont-y"></div>
				<div id="event-edit-att-n" class="event-grid-attendees-cont-n"></div>
				<div id="event-edit-att-q" class="event-grid-attendees-cont-q"></div>
			</div>
		</div>

		<div id="event-grid-meeting-params<?= $id?>" class="event-grid-params">
			<div class="bxec-add-meet-text"><a id="<?=$id?>_add_meet_text" href="javascript:void(0);"><?=GetMessage('EC_ADD_METTING_TEXT')?></a></div>
			<div class="bxec-meet-text" id="<?=$id?>_meet_text_cont">
				<div class="bxec-mt-d"><?=GetMessage('EC_METTING_TEXT')?> (<a id="<?=$id?>_hide_meet_text" href="javascript:void(0);" title="<?=GetMessage('EC_HIDE_METTING_TEXT_TITLE')?>"><?=GetMessage('EC_HIDE')?></a>): </div><br />
				<textarea name="meeting_text" class="bxec-mt-t" cols="63" id="<?=$id?>_meeting_text" rows="3"></textarea>
			</div>

			<div class="bxec-popup-row bxec-popup-row-checkbox">
				<input type="checkbox" id="<?=$id?>_ed_open_meeting" value="Y" name="open_meeting"/>
				<label style="display: inline-block;" for="<?=$id?>_ed_open_meeting"><?=GetMessage('EC_OPEN_MEETING')?></label>
			</div>
			<div class="bxec-popup-row bxec-popup-row-checkbox">
				<input type="checkbox" id="<?=$id?>_ed_notify_status" value="Y" name="meeting_notify"/>
				<label for="<?=$id?>_ed_notify_status"><?=GetMessage('EC_NOTIFY_STATUS')?></label>
			</div>
			<div class="bxec-popup-row bxec-popup-row-checkbox" id="<?=$id?>_ed_reivite_cont">
				<input type="checkbox" id="<?=$id?>_ed_reivite" value="Y" name="meeting_reinvite"/>
				<label for="<?=$id?>_ed_reivite"><?=GetMessage('EC_REINVITE')?></label>
			</div>
		</div>
		<?
	}

	public static function CheckBitrix24Limits($Params)
	{
		global $APPLICATION;
		$result = !CCalendar::IsBitrix24() || CBitrix24BusinessTools::isToolAvailable(CCalendar::GetCurUserId(), "calendar");
		if (!$result)
		{
			$id = $Params['id'];
			?><div id="<?=$id?>-bitrix24-limit" class="bxec-b24-limit-wrap"><?
			$APPLICATION->IncludeComponent("bitrix:bitrix24.business.tools.info", "", array("SHOW_TITLE" => "Y"));
			?></div><?
		}
		return $result;
	}
}
?>