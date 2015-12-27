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
		CUtil::InitJSCore(array('ajax', 'window', 'popup', 'access', 'date', 'viewer', 'socnetlogdest'));

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
			'/bitrix/js/calendar/cal-controlls.js',
			'/bitrix/js/calendar/cal-planner.js'
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
		<?CCalendarPlanner::Localization();?>

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
			//self::DialogEditEvent($Params);
			self::DialogEditSection($Params);

			self::DialogExternalCalendars($Params);
		}
		self::DialogSettings($Params);

		//self::DialogViewEvent($Params);
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
			'Refresh' => 'EC_CAL_DAV_REFRESH',
			'acc_status_absent' => 'EC_PRIVATE_ABSENT',
			'acc_status_busy' => 'EC_ACCESSIBILITY_B',
			'denyRepeted' => 'EC_DD_DENY_REPEATED',
			'ddDenyRepeted' => 'EC_DD_DENY_REPEATED',
			'ddDenyTask' => 'EC_DD_DENY_TASK',
			'ddDenyEvent' => 'EC_DD_DENY_EVENT'
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

	public static function DialogEditEvent($Params)
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");
		global $APPLICATION, $USER_FIELD_MANAGER;

		$id = $Params['id'];
		$event = $Params['event'];

		$event['~DT_FROM_TS'] = $event['DT_FROM_TS'];
		$event['~DT_TO_TS'] = $event['DT_TO_TS'];
		$event['DT_FROM_TS'] = $Params['fromTs'];
		$event['DT_TO_TS'] = $Params['fromTs'] + $event['DT_LENGTH'];

		$UF = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $event['ID'], LANGUAGE_ID);

		$event['UF_CRM_CAL_EVENT'] = $UF['UF_CRM_CAL_EVENT'];
		if (empty($event['UF_CRM_CAL_EVENT']['VALUE']))
			$event['UF_CRM_CAL_EVENT'] = false;

		$event['UF_WEBDAV_CAL_EVENT'] = $UF['UF_WEBDAV_CAL_EVENT'];
		if (empty($event['UF_WEBDAV_CAL_EVENT']['VALUE']))
			$event['UF_WEBDAV_CAL_EVENT'] = false;

		$userId = CCalendar::GetCurUserId();

		$arHost = CCalendar::GetUser($userId, true);
		$arHost['AVATAR_SRC'] = CCalendar::GetUserAvatarSrc($arHost);
		$arHost['URL'] = CCalendar::GetUserUrl($event['MEETING_HOST'], $Params["PATH_TO_USER"]);
		$arHost['DISPLAY_NAME'] = CCalendar::GetUserName($arHost);
		$Params['host'] = $arHost;

		if ($event['IS_MEETING'])
		{
			$attendees = array(
				'y' => array(
					'users' => array(),
					'count' => 4,
					'countMax' => 8,
					'title' => GetMessage('EC_ATT_Y'),
					'id' => "bxview-att-cont-y-".$event['ID']
				),
				'n' => array(
					'users' => array(),
					'count' => 2,
					'countMax' => 3,
					'title' => GetMessage('EC_ATT_N'),
					'id' => "bxview-att-cont-n-".$event['ID']
				),
				'q' => array(
					'users' => array(),
					'count' => 2,
					'countMax' => 3,
					'title' => GetMessage('EC_ATT_Q'),
					'id' => "bxview-att-cont-q-".$event['ID']
				)
			);

			$userIds = array();
			if (is_array($event['~ATTENDEES']) && count($event['~ATTENDEES']) > 0)
			{
				foreach ($event['~ATTENDEES'] as $i => $att)
				{
					$userIds[] = $att["USER_ID"];
					if ($userId == $att["USER_ID"])
						$curUserStatus = $att['STATUS'];
					$att['AVATAR_SRC'] = CCalendar::GetUserAvatarSrc($att);
					$att['URL'] = CCalendar::GetUserUrl($att["USER_ID"], $Params["PATH_TO_USER"]);
					$attendees[strtolower($att['STATUS'])]['users'][] = $att;
				}

				$acc = CCalendar::CheckUsersAccessibility(array(
					'users' => $userIds,
					'from' => $event['DT_FROM'],
					'to' => $event['DT_TO'],
					'eventId' => $event['ID']
				));

				foreach($event['~ATTENDEES'] as $i => $att)
					$event['~ATTENDEES'][$i]['ACC'] = $acc[$att['USER_ID']];
			}
		}

		if ($event['IS_MEETING'] && empty($event['ATTENDEES_CODES']))
			$event['ATTENDEES_CODES'] = CCalendarEvent::CheckEndUpdateAttendeesCodes($event);

		$Params['event'] = $event;
		$Params['UF'] = $UF;

		$arTabs = array(
			array('name' => GetMessage('EC_EDEV_EVENT'), 'title' => GetMessage('EC_EDEV_EVENT_TITLE'), 'id' => $id."ed-tab-0", 'active' => true),
			array('name' => GetMessage('EC_T_DESC'), 'title' => GetMessage('EC_T_DESC_TITLE'), 'id' => $id."ed-tab-1"),
			array('name' => GetMessage('EC_EDEV_GUESTS'), 'title' => GetMessage('EC_EDEV_GUESTS_TITLE'), 'id' => $id."ed-tab-2", "show" => !!$Params['bSocNet']),
			array('name' => GetMessage('EC_EDEV_ADD_TAB'), 'title' => GetMessage('EC_EDEV_ADD_TAB_TITLE'), 'id' => $id."ed-tab-3")
		);

		$addWidthStyle = IsAmPmMode() ? ' ampm-width' : '';
?>

<script>
	window.__ATTENDEES_ACC = null;
		<?if($event['IS_MEETING'] && is_array($event['~ATTENDEES'])):?>
	window.__ATTENDEES_ACC = <?= CUtil::PhpToJSObject($event['~ATTENDEES'])?>;
	<?endif;?>
</script>
<form enctype="multipart/form-data" method="POST" name="event_edit_form" id="<?=$id?>_form">
<input type="hidden" value="Y" name="skip_unescape"/>
<input id="event-id<?=$id?>" type="hidden" value="0" name="id"/>
<input id="event-month<?=$id?>" type="hidden" value="0" name="month"/>
<input id="event-year<?=$id?>" type="hidden" value="0" name="year"/>
<div id="bxec_edit_ed_<?=$id?>" class="bxec-popup">
	<div style="width: 610px; height: 1px;"></div>
	<div class="bxec-d-tabs" id="<?=$id?>_edit_tabs">
		<?foreach($arTabs as $tab):?>
			<div class="bxec-d-tab <?if($tab['active'])echo'bxec-d-tab-act';?>" title="<?=$tab['title']?>" id="<?=$tab['id']?>" <?if($tab['show'] === false) echo'style="display:none;"';?>>
				<b></b><div><span><?=$tab['name']?></span></div><i></i>
			</div>
		<?endforeach;?>
	</div>
	<div class="bxec-d-cont"  id="<?=$id?>_edit_ed_d_tabcont">
		<?/* ####### TAB 0 : MAIN ####### */?>
		<div id="<?=$id?>ed-tab-0-cont" class="bxec-d-cont-div" style="display: block;">
			<div class="bxc-meeting-edit-note"><?= GetMessage('EC_EDIT_MEETING_NOTE')?></div>

			<div class="bxec-from-to-reminder" id="feed-cal-from-to-cont<?=$id?>">
				<input id="event-from-ts<?=$id?>" type="hidden" value="" name="from_ts"/>
				<input id="event-to-ts<?=$id?>" type="hidden" value="" name="to_ts"/>
				<div class="bxec-from-to-reminder-inner">
			<span class="bxec-date">
				<label class="bxec-date-label" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_FROM_DATE_TIME')?></label>
				<label class="bxec-date-label-full-day" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_DATE_FROM')?></label>
				<input id="feed-cal-event-from<?=$id?>" type="text" class="calendar-inp calendar-inp-cal"/>
			</span>
			<span class="bxec-time<?=$addWidthStyle?>"><?CClock::Show(array('inputId' => 'feed_cal_event_from_time'.$id, 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM'), 'showIcon' => false));?></span>
			<span class="bxec-mdash">&mdash;</span>
			<span class="bxec-date">
				<label class="bxec-date-label" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_TO_DATE_TIME')?></label>
				<label class="bxec-date-label-full-day" for="<?=$id?>edev-from"><?=GetMessage('EC_EDEV_DATE_TO')?></label>
				<input id="feed-cal-event-to<?=$id?>" type="text" class="calendar-inp calendar-inp-cal"/>
			</span>
			<span class="bxec-time<?=$addWidthStyle?>"><?CClock::Show(array('inputId' => 'feed_cal_event_to_time'.$id, 'inputTitle' => GetMessage('EC_EDEV_TIME_TO'), 'showIcon' => false));?></span>

					<div class="bxec-reminder-collapsed" id="feed-cal-reminder-cont<?=$id?>">
						<input class="bxec-check" type="checkbox" id="event-reminder<?=$id?>" value="Y" name="remind[checked]"/>
						<label class="bxec-rem-lbl" for="event-reminder<?=$id?>"><?= GetMessage('EC_EDEV_REMIND_EVENT')?></label>
						<label class="bxec-rem-lbl-for" for="event-reminder<?=$id?>"><?= GetMessage('EC_EDEV_REMIND_FOR')?>:</label>
						<span class="bxec-rem-value">
							<input class="calendar-inp" id="event_remind_count<?=$id?>" type="text" style="width: 30px" size="2" name="remind[count]">
							<select id="event_remind_type<?=$id?>" class="calendar-select" name="remind[type]" style="width: 106px;">
								<option value="min" selected="true"><?=GetMessage('EC_EDEV_REM_MIN')?></option>
								<option value="hour"><?=GetMessage('EC_EDEV_REM_HOUR')?></option>
								<option value="day"><?=GetMessage('EC_EDEV_REM_DAY')?></option>
							</select>
							<?=GetMessage('ECLF_REM_DE_VORHER')?>
						</span>
					</div>

					<div style="display:none;"><?$APPLICATION->IncludeComponent("bitrix:main.calendar",	"",Array("FORM_NAME" => "","INPUT_NAME" => "","INPUT_VALUE" => "","SHOW_TIME" => "N","HIDE_TIMEBAR" => "Y","SHOW_INPUT" => "N"),false, array("HIDE_ICONS" => "Y"));?></div>
				</div>

				<div class="bxec-full-day">
					<input type="checkbox" id="event-full-day<?=$id?>" value="Y" name="skip_time"/>
					<label style="display: inline-block;" for="event-full-day<?=$id?>"><?= GetMessage('EC_FULL_DAY')?></label>
				</div>
			</div>

			<div class="bxec-popup-row">
				<input name="name" placeholder="<?= GetMessage('EC_T_EVENT_NAME')?>" type="text" id="<?=$id?>_edit_ed_name" class="calendar-inp bxec-inp-active" style="width: 500px; font-size: 18px!important;"/>
			</div>

			<div class="bxec-popup-row" id="<?=$id?>_location_cnt">
				<span class="bxec-field-label-edev"><label for="<?=$id?>_planner_location1"><?=GetMessage('EC_LOCATION')?>:</label></span>
				<span class="bxec-field-val-2 bxecpl-loc-cont" >
				<input class="calendar-inp" style="width: 272px;" id="<?=$id?>_planner_location1" type="text"  title="<?=GetMessage('EC_LOCATION_TITLE')?>" value="<?= GetMessage('EC_PL_SEL_MEET_ROOM')?>" class="ec-label" />
				</span>
				<input id="event-location-old<?=$id?>" type="hidden" value="" name="location[OLD]"/>
				<input id="event-location-new<?=$id?>" type="hidden" value="" name="location[NEW]"/>
			</div>

			<?if($Params['bIntranet']):?>
			<div class="bxec-popup-row bxec-ed-meeting-vis">
				<span class="bxec-field-label-edev"><label for="<?=$id?>_bxec_accessibility"><?=GetMessage('EC_ACCESSIBILITY')?>:</label></span>
				<span class="bxec-field-val-2" >
				<select  class="calendar-select" id="<?=$id?>_bxec_accessibility" name="accessibility" style="width: 310px;">
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
				<select name="section" id="<?=$id?>_edit_ed_calend_sel" class="calendar-select" style="width: 310px;"></select><span id="<?=$id?>_edit_sect_sel_warn" class="bxec-warn" style="display: none;"><?=GetMessage('EC_T_CALEN_DIS_WARNING')?></span>
				</span>
			</div>

		</div>
		<?/* ####### END TAB 0 ####### */?>

		<?/* ####### TAB 1 : DESCRIPTION - LHE ####### */?>
		<div id="<?=$id?>ed-tab-1-cont" class="bxec-d-cont-div bxec-d-cont-div-lhe">
			<!-- Description + files -->
			<?
			$APPLICATION->IncludeComponent(
				"bitrix:main.post.form",
				"",
				array(
					"FORM_ID" => "event_edit_form",
					"SHOW_MORE" => "Y",
					"PARSER" => Array(
						"Bold", "Italic", "Underline", "Strike", "ForeColor",
						"FontList", "FontSizeList", "RemoveFormat", "Quote",
						"Code", "CreateLink",
						"Image", "UploadFile",
						"InputVideo",
						"Table", "Justify", "InsertOrderedList",
						"InsertUnorderedList",
						"Source", "MentionUser", "Spoiler"
					),
					"BUTTONS" => Array(
						"UploadFile",
						"CreateLink",
						"InputVideo",
						"Quote",
						//"MentionUser"
					),
					"TEXT" => Array(
						"ID" => $id.'_edit_ed_desc',
						"NAME" => "desc",
						"VALUE" => $Params['event']['DESCRIPTION'],
						"HEIGHT" => "280px"
					),
					"UPLOAD_WEBDAV_ELEMENT" => $Params['UF']['UF_WEBDAV_CAL_EVENT'],
					"UPLOAD_FILE_PARAMS" => array("width" => 400, "height" => 400),
					"FILES" => Array(
						"VALUE" => array(),
						"DEL_LINK" => '',
						"SHOW" => "N"
					),
					"SMILES" => Array("VALUE" => array()),
					"LHE" => array(

						"id" => $Params['id'].'_event_editor',
						"documentCSS" => "",
						"jsObjName" => $Params['id'].'_event_editor',
						"fontFamily" => "'Helvetica Neue', Helvetica, Arial, sans-serif",
						"fontSize" => "12px",
						"lazyLoad" => false,
						"setFocusAfterShow" => false
					)
				),
				false,
				array(
					"HIDE_ICONS" => "Y"
				)
			);
			?>
		</div>
		<?/* ####### END TAB 1 ####### */?>

		<?
		/* ####### TAB 2 : GUESTS ####### */
		if($Params['bSocNet']):?>
		<div id="<?=$id?>ed-tab-2-cont" class="bxec-d-cont-div">
			<a id="<?=$id?>_planner_link" href="javascript:void(0);" title="<?=GetMessage('EC_PLANNER_TITLE')?>" class="bxex-planner-link"><i></i><?=GetMessage('EC_PLANNER2')?></a>
			<?CCalendarSceleton::__ShowAttendeesDestinationHtml($Params)?>
			<div class="bxc-att-cont-cont">
				<span class="bxc-add-guest-link"  id="<?=$id?>_user_control_link"></span>
				<div id="<?=$id?>_attendees_cont" class="bxc-attendees-cont" style="display: none;">
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

		</div>
		<?/* ####### END TAB 2 ####### */?>
		<?endif; /* bSocNet */?>

		<?/* ####### TAB 3 : ADDITIONAL INFO ####### */?>
		<div id="<?=$id?>ed-tab-3-cont" class="bxec-d-cont-div">
			<div class="bxec-popup-row-title"><?= GetMessage('EC_T_REPEATING')?></div>

			<div class="bxec-popup-row-repeat" id="<?=$id?>_edit_ed_rep_cont">
				<div class="bxec-popup-row-2" id="<?=$id?>_edit_ed_rep_tr">
					<input id="event-rrule-byday<?=$id?>" type="hidden" value="0" name="rrule[BYDAY]"/>
					<input id="event-rrule-until<?=$id?>" type="hidden" value="0" name="rrule[UNTIL]"/>
					<input id="<?=$id?>_edit_ed_rep_check" type="checkbox" value="Y" name="rrule_enabled"/>
					<label for="<?=$id?>_edit_ed_rep_check" style="display: inline-block; margin: 3px 0 0 0; vertical-align:top;"><?=GetMessage('EC_T_REPEAT_CHECK_LABEL')?></label>
				</div>

				<div class="bxec-popup-row-bordered bxec-popup-repeat-details">

					<label for="<?=$id?>_edit_ed_rep_sel" class="event-grid-repeat-label"><?=GetMessage('EC_T_REPEAT')?>:</label>
					<select id="<?=$id?>_edit_ed_rep_sel" class="calendar-select" name="rrule[FREQ]" style="width: 175px;">
						<option value="DAILY"><?=GetMessage('EC_T_REPEAT_DAILY')?></option>
						<option value="WEEKLY"><?=GetMessage('EC_T_REPEAT_WEEKLY')?></option>
						<option value="MONTHLY"><?=GetMessage('EC_T_REPEAT_MONTHLY')?></option>
						<option value="YEARLY"><?=GetMessage('EC_T_REPEAT_YEARLY')?></option>
					</select>

					<span class="event-grid-repeat-cont">
						<span class="event-grid-rep-phrases" id="<?=$id?>_edit_ed_rep_phrase1"></span>
						<select id="<?=$id?>_edit_ed_rep_count" class="calendar-select" name="rrule[INTERVAL]">
							<?for ($i = 1; $i < 36; $i++):?>
								<option value="<?=$i?>"><?=$i?></option>
							<?endfor;?>
						</select>
						<span class="event-grid-rep-phrases" id="<?=$id?>_edit_ed_rep_phrase2"></span>

						<span id="<?=$id?>_edit_ed_rep_week_days" class="bxec-rep-week-days">
							<?
							$week_days = CCalendarSceleton::GetWeekDays();
							for($i = 0; $i < 7; $i++):
								$id_ = $id.'bxec_week_day_'.$i;?>
								<input id="<?=$id_?>" type="checkbox" value="<?= $week_days[$i][2]?>">
								<label for="<?=$id_?>" title="<?=$week_days[$i][0]?>"><?=$week_days[$i][1]?></label>
								<?if($i == 2)
								{
									echo '<br>';
								}?>
							<?endfor;?>
						</span>
					</span>

				</div>

				<div class="bxec-popup-row-bordered bxec-popup-repeat-details">
					<label for="<?=$id_?>edit-ev-rep-diap-to" style="display: inline-block; margin: 8px 3px 0 0; vertical-align:top;"><?=GetMessage('EC_T_DIALOG_STOP_REPEAT')?>:</label>
					<input class="calendar-inp calendar-inp-cal" id="<?=$id?>edit-ev-rep-diap-to" type="text" style="width: 150px;"/>
				</div>
			</div>

			<div class="bxec-popup-row-title"><?= GetMessage('EC_EDEV_ADD_TAB')?></div>
			<div class="bxec-popup-row-2">
				<?=GetMessage('EC_IMPORTANCE_TITLE')?>:
				<select id="<?=$id?>_bxec_importance" class="calendar-select" name="importance" style="width: 250px;">
					<option value="high" style="font-weight: bold;"><?=GetMessage('EC_IMPORTANCE_H')?></option>
					<option value="normal" selected="true"><?=GetMessage('EC_IMPORTANCE_N')?></option>
					<option value="low" style="color: #909090;"><?=GetMessage('EC_IMPORTANCE_L')?></option>
				</select>
			</div>

			<?if($Params['type'] == 'user'):?>
			<div class="bxec-popup-row-bordered bxec-popup-row-private">
				<input id="<?=$id?>_bxec_private" type="checkbox" value="Y" title="<?=GetMessage('EC_PRIVATE_NOTICE')?>" name="private_event">
				<label for="<?=$id?>_bxec_private" title="<?=GetMessage('EC_PRIVATE_NOTICE')?>"><?=GetMessage('EC_PRIVATE_EVENT')?></label>
				<div><?= GetMessage('EC_PRIVATE_NOTICE')?></div>
			</div>
			<?endif;?>

			<!-- Color -->
			<div class="bxec-popup-row-bordered bxec-popup-row-color">
				<input id="<?=$id?>_bxec_color" type="hidden" value="" name="color" />
				<input id="<?=$id?>_bxec_text_color" type="hidden" value="" name="text_color" />
				<label class="bxec-color-label" for="<?=$id?>-event-color-inp"><?=GetMessage('EC_T_COLOR')?>:</label>
				<div class="bxec-color-selector-cont">
				<?CCalendarSceleton::DisplayColorSelector($id, 'event');?>
				</div>
			</div>

			<!-- Userfields -->
			<? if (isset($UF['UF_CRM_CAL_EVENT'])):?>
			<div id="<?=$id?>bxec_uf_group" class="bxec-popup-row-bordered">
				<?$crmUF = $UF['UF_CRM_CAL_EVENT'];?>
				<label for="event-crm<?=$id?>" class="bxec-uf-crm-label"><?= htmlspecialcharsbx($crmUF["EDIT_FORM_LABEL"])?>:</label>
				<div class="bxec-uf-crm-cont">
					<?$APPLICATION->IncludeComponent(
						"bitrix:system.field.edit",
						$crmUF["USER_TYPE"]["USER_TYPE_ID"],
						array(
							"bVarsFromForm" => false,
							"arUserField" => $crmUF,
							"form_name" => 'event_edit_form'
						), null, array("HIDE_ICONS" => "Y")
					);?>
				</div>
			</div>
			<?endif;?>
		</div>
		<?/* ####### END TAB 3 ####### */?>
	</div>
</div>
</form>
<?
	}

	public static function DialogViewEvent($Params)
	{
		global $APPLICATION, $USER_FIELD_MANAGER;

		$id = $Params['id'];
		$event = $Params['event'];

		$event['~DT_FROM_TS'] = $event['DT_FROM_TS'];
		$event['~DT_TO_TS'] = $event['DT_TO_TS'];
		$event['DT_FROM_TS'] = $Params['fromTs'];
		$event['DT_TO_TS'] = $Params['fromTs'] + $event['DT_LENGTH'];

		$UF = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $event['ID'], LANGUAGE_ID);

		$event['UF_CRM_CAL_EVENT'] = $UF['UF_CRM_CAL_EVENT'];
		if (empty($event['UF_CRM_CAL_EVENT']['VALUE']))
			$event['UF_CRM_CAL_EVENT'] = false;

		$event['UF_WEBDAV_CAL_EVENT'] = $UF['UF_WEBDAV_CAL_EVENT'];
		if (empty($event['UF_WEBDAV_CAL_EVENT']['VALUE']))
			$event['UF_WEBDAV_CAL_EVENT'] = false;

		$event['FROM_WEEK_DAY'] = FormatDate('D', $event['DT_FROM_TS']);
		$event['FROM_MONTH_DAY'] = FormatDate('j', $event['DT_FROM_TS']);
		$event['FROM_MONTH'] = FormatDate('n', $event['DT_FROM_TS']);

		$arHost = CCalendar::GetUser($event['MEETING_HOST'], true);
		$arHost['AVATAR_SRC'] = CCalendar::GetUserAvatarSrc($arHost);
		$arHost['URL'] = CCalendar::GetUserUrl($event['MEETING_HOST'], $Params["PATH_TO_USER"]);
		$arHost['DISPLAY_NAME'] = CCalendar::GetUserName($arHost);
		$curUserStatus = '';
		$userId = CCalendar::GetCurUserId();

		$viewComments = CCalendar::IsPersonal($event['CAL_TYPE'], $event['OWNER_ID'], $userId) || CCalendarSect::CanDo('calendar_view_full', $event['SECT_ID'], $userId);

		if ($event['IS_MEETING'] && empty($event['ATTENDEES_CODES']))
			$event['ATTENDEES_CODES'] = CCalendarEvent::CheckEndUpdateAttendeesCodes($event);

		if ($event['IS_MEETING'])
		{
			$attendees = array(
				'y' => array(
					'users' => array(),
					'count' => 4,
					'countMax' => 8,
					'title' => GetMessage('EC_ATT_Y'),
					'id' => "bxview-att-cont-y-".$event['ID']
				),
				'n' => array(
					'users' => array(),
					'count' => 2,
					'countMax' => 3,
					'title' => GetMessage('EC_ATT_N'),
					'id' => "bxview-att-cont-n-".$event['ID']
				),
				'q' => array(
					'users' => array(),
					'count' => 2,
					'countMax' => 3,
					'title' => GetMessage('EC_ATT_Q'),
					'id' => "bxview-att-cont-q-".$event['ID']
				)
			);

			if (is_array($event['~ATTENDEES']))
			{
				foreach ($event['~ATTENDEES'] as $att)
				{
					if ($userId == $att["USER_ID"])
					{
						$curUserStatus = $att['STATUS'];
						$viewComments = true;
					}
					$att['AVATAR_SRC'] = CCalendar::GetUserAvatarSrc($att);
					$att['URL'] = CCalendar::GetUserUrl($att["USER_ID"], $Params["PATH_TO_USER"]);
					$attendees[strtolower($att['STATUS'])]['users'][] = $att;
				}
			}
		}

		$arTabs = array(
			array('name' => GetMessage('EC_BASIC'), 'title' => GetMessage('EC_BASIC_TITLE'), 'id' => $id."view-tab-0", 'active' => true),
			array('name' => GetMessage('EC_EDEV_ADD_TAB'), 'title' => GetMessage('EC_EDEV_ADD_TAB_TITLE'), 'id' => $id."view-tab-1")
		);
		?>
<div id="bxec_view_ed_<?=$id?>" class="bxec-popup">
	<div style="width: 700px; height: 1px;"></div>
	<div class="bxec-d-tabs" id="<?=$id?>_viewev_tabs">
		<?foreach($arTabs as $tab):?>
			<div class="bxec-d-tab <?if($tab['active']) echo'bxec-d-tab-act';?>" title="<?= (isset($tab['title']) ? $tab['title'] : $tab['name'])?>" id="<?= $tab['id']?>" <?if($tab['show'] === false) echo'style="display:none;"';?>>
				<b></b><div><span><?=$tab['name']?></span></div><i></i>
			</div>
		<?endforeach;?>
	</div>
	<div class="bxec-d-cont">
<?/* ####### TAB 0 : BASIC ####### */?>
<div id="<?=$id?>view-tab-0-cont" class="bxec-d-cont-div" style="display: block;">
	<div class="bx-cal-view-icon">
		<div class="bx-cal-view-icon-day"><?= $event['FROM_WEEK_DAY']?></div>
		<div class="bx-cal-view-icon-date"><?= $event['FROM_MONTH_DAY']?></div>
	</div>
	<div class="bx-cal-view-text">
		<table>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?= GetMessage('EC_T_NAME')?>:</td>
				<td class="bx-cal-view-text-cell-r"><span class="bx-cal-view-name"><?= htmlspecialcharsEx($event['NAME'])?></span></td>
			</tr>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?= GetMessage('EC_DATE')?>:</td>
				<td class="bx-cal-view-text-cell-r bx-cal-view-from-to">
					<?= CCalendar::GetFromToHtml($event['DT_FROM_TS'], $event['DT_TO_TS'], $event['DT_SKIP_TIME'] == 'Y', $event['DT_LENGTH']);?>
				</td>
			</tr>
			<?
			if ($event['RRULE']):?>
				<?
				$event['RRULE'] = CCalendarEvent::ParseRRULE($event['RRULE']);
				switch ($event['RRULE']['FREQ'])
				{
					case 'DAILY':
						if ($event['RRULE']['INTERVAL'] == 1)
							$repeatHTML = GetMessage('EC_RRULE_EVERY_DAY');
						else
							$repeatHTML = GetMessage('EC_RRULE_EVERY_DAY_1', array('#DAY#' => $event['RRULE']['INTERVAL']));
						break;
					case 'WEEKLY':
						$daysList = array();
						foreach ($event['RRULE']['BYDAY'] as $day)
							$daysList[] = GetMessage('EC_'.$day);
						$daysList = implode(', ', $daysList);
						if ($event['RRULE']['INTERVAL'] == 1)
							$repeatHTML = GetMessage('EC_RRULE_EVERY_WEEK', array('#DAYS_LIST#' => $daysList));
						else
							$repeatHTML = GetMessage('EC_RRULE_EVERY_WEEK_1', array('#WEEK#' => $event['RRULE']['INTERVAL'], '#DAYS_LIST#' => $daysList));
						break;
					case 'MONTHLY':
						if ($event['RRULE']['INTERVAL'] == 1)
							$repeatHTML = GetMessage('EC_RRULE_EVERY_MONTH');
						else
							$repeatHTML = GetMessage('EC_RRULE_EVERY_MONTH_1', array('#MONTH#' => $event['RRULE']['INTERVAL']));
						break;
					case 'YEARLY':
						if ($event['RRULE']['INTERVAL'] == 1)
							$repeatHTML = GetMessage('EC_RRULE_EVERY_YEAR', array('#DAY#' => $event['FROM_MONTH_DAY'], '#MONTH#' => $event['FROM_MONTH']));
						else
							$repeatHTML = GetMessage('EC_RRULE_EVERY_YEAR_1', array('#YEAR#' => $event['RRULE']['INTERVAL'], '#DAY#' => $event['FROM_MONTH_DAY'], '#MONTH#' => $event['FROM_MONTH']));
						break;
				}

				$repeatHTML .= '<br>'.GetMessage('EC_RRULE_FROM', array('#FROM_DATE#' => FormatDate(CCalendar::DFormat(false), $event['~DT_FROM_TS'])));
				if (date('dmY', $event['RRULE']['UNTIL']) != '01012038')
				{
					$repeatHTML .= ' '.GetMessage('EC_RRULE_UNTIL', array('#UNTIL_DATE#' => FormatDate(CCalendar::DFormat(false), $event['RRULE']['UNTIL'])));
				}
				?>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?=GetMessage('EC_T_REPEAT')?>:</td>
				<td class="bx-cal-view-text-cell-r"><?= $repeatHTML?></td>
			</tr>
			<?endif;?>
			<?if (!empty($event['LOCATION'])):?>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?= GetMessage('EC_LOCATION')?>:</td>
				<td class="bx-cal-view-text-cell-r"><span class="bx-cal-location"><?= htmlspecialcharsEx(CCalendar::GetTextLocation($event['LOCATION']))?></span></td>
			</tr>
			<?endif;?>
		</table>
	</div>

	<?if (!empty($event['~DESCRIPTION'])):?>
	<div class="bx-cal-view-description">
		<div class="feed-cal-view-desc-title"><?= GetMessage('EC_T_DESC')?>:</div>
		<div class="bx-cal-view-desc-cont"><?= $event['~DESCRIPTION']?></div>
	</div>
	<?endif;?>

	<?if ($event['UF_WEBDAV_CAL_EVENT']):?>
	<div class="bx-cal-view-files" id="bx-cal-view-files-<?=$id?><?=$event['ID']?>">
		<?$APPLICATION->IncludeComponent(
			"bitrix:system.field.view",
			$event['UF_WEBDAV_CAL_EVENT']["USER_TYPE"]["USER_TYPE_ID"],
			array("arUserField" => $event['UF_WEBDAV_CAL_EVENT']),
			null,
			array("HIDE_ICONS"=>"Y")
		);?>
	</div>
	<?endif;?>

	<?if ($event['UF_CRM_CAL_EVENT']):?>
	<div class="bx-cal-view-crm">
		<div class="bxec-crm-title"><?= htmlspecialcharsbx($event['UF_CRM_CAL_EVENT']["EDIT_FORM_LABEL"])?>:</div>
		<?$APPLICATION->IncludeComponent(
			"bitrix:system.field.view",
			$event['UF_CRM_CAL_EVENT']["USER_TYPE"]["USER_TYPE_ID"],
			array("arUserField" => $event['UF_CRM_CAL_EVENT']),
			null,
			array("HIDE_ICONS"=>"Y")
		);?>
	</div>
	<?endif;?>

	<div id="<?=$id?>bxec_view_uf_group" class="bxec-popup-row" style="display: none;">
		<div class="bxec-popup-row-title"><?= GetMessage('EC_EDEV_ADD_TAB')?></div>
		<div id="<?=$id?>bxec_view_uf_cont"></div>
	</div>

	<?if($Params['bSocNet'] && $event['IS_MEETING']):?>
	<div class="bx-cal-view-meeting-cnt">
		<table>
			<tr>
				<td class="bx-cal-view-att-cell-l bx-cal-bot-border"><span><?= GetMessage('EC_EDEV_HOST')?>:</span></td>
				<td class="bx-cal-view-att-cell-r bx-cal-bot-border">
					<a title="<?= htmlspecialcharsbx($arHost['DISPLAY_NAME'])?>" href="<?= $arHost['URL']?>" target="_blank" class="bxcal-att-popup-img bxcal-att-popup-att-full"><span class="bxcal-att-popup-avatar-outer"><span class="bxcal-att-popup-avatar"><img src="<?= $arHost['AVATAR_SRC']?>" width="<?= $Params['AVATAR_SIZE']?>" height="<?= $Params['AVATAR_SIZE']?>" /></span></span><span class="bxcal-att-name"><?= htmlspecialcharsbx($arHost['DISPLAY_NAME'])?></span></a>
				</td>
			</tr>
			<tr>
				<td class="bx-cal-view-att-cell-l"></td>
				<td class="bx-cal-view-att-cell-r" style="padding-top: 5px;">
					<div class="bx-cal-view-title"><?= GetMessage('EC_EDEV_GUESTS')?></div>
					<div class="bx-cal-att-dest-cont">
						<?
						$arDest = CCalendar::GetFormatedDestination($event['ATTENDEES_CODES']);
						$cnt = count($arDest);
						for($i = 0; $i < $cnt; $i++ )
						{
							$dest = $arDest[$i];
							?><span class="bx-cal-att-dest-block"><?= $dest['TITLE']?></span><?
							if ($i < count($arDest) - 1)
								echo ', ';
						}
						?>
					</div>
				</td>
			</tr>

			<?foreach($attendees as $arAtt):
				if (empty($arAtt['users']))
					continue;
				?>
			<tr>
				<td class="bx-cal-view-att-cell-l"><?= $arAtt['title']?>:</td>
				<td class="bx-cal-view-att-cell-r">
					<div class="bx-cal-view-att-cont" id="<?= $arAtt['id']?>">
						<?
						$cnt = 0;
						$bShowAll = count($arAtt['users']) <= $arAtt['countMax'];
						foreach($arAtt['users'] as $att)
						{
							$cnt++;
							if (!$bShowAll && $cnt > $arAtt['count'])
							{
								?>
								<a title="<?= htmlspecialcharsbx($att['DISPLAY_NAME'])?>" href="<?= $att['URL']?>" target="_blank" class="bxcal-att-popup-img bxcal-att-popup-img-hidden"><span class="bxcal-att-popup-avatar-outer"><span class="bxcal-att-popup-avatar"><img src="<?= $att['AVATAR_SRC']?>" width="<?= $Params['AVATAR_SIZE']?>" height="<?= $Params['AVATAR_SIZE']?>" /></span></span><span class="bxcal-att-name"><?= htmlspecialcharsbx($att['DISPLAY_NAME'])?></span></a>
								<?
							}
							else // Display attendee
							{
								?>
								<a title="<?= htmlspecialcharsbx($att['DISPLAY_NAME'])?>" href="<?= $att['URL']?>" target="_blank" class="bxcal-att-popup-img"><span class="bxcal-att-popup-avatar-outer"><span class="bxcal-att-popup-avatar"><img src="<?= $att['AVATAR_SRC']?>" width="<?= $Params['AVATAR_SIZE']?>" height="<?= $Params['AVATAR_SIZE']?>" /></span></span><span class="bxcal-att-name"><?= htmlspecialcharsbx($att['DISPLAY_NAME'])?></span></a>
								<?
							}
						}
						if (!$bShowAll)
						{
							?>
							<span data-bx-more-users="<?= $arAtt['id']?>" class="bxcal-more-attendees"><?= CCalendar::GetMoreAttendeesMessage(count($arAtt['users']) - $arAtt['count'])?></span>
						<?
						}?>
					</div>
				</td>
			</tr>
			<?endforeach;/*foreach($attendees as $arAtt)*/?>

			<?if (!empty($event['MEETING']['TEXT'])):?>
			<tr>
				<td class="bx-cal-view-att-cell-l" style="padding-top: 3px;"><?=GetMessage('EC_MEETING_TEXT2')?>:</td>
				<td class="bx-cal-view-att-cell-r"><pre><?= htmlspecialcharsEx($event['MEETING']['TEXT'])?></pre></td>
			</tr>
			<?endif; /*if (!empty($event['MEETING']['TEXT']))*/?>
		</table>

		<div class="bxc-confirm-row">
			<?if($curUserStatus == 'Q'): /* User still haven't take a decision*/?>
				<div id="<?=$id?>status-conf-cnt2" class="bxc-conf-cnt">
					<span data-bx-set-status="Y" class="popup-window-button popup-window-button-accept" title="<?= GetMessage('EC_EDEV_CONF_Y_TITLE')?>"><span class="popup-window-button-left"></span><span class="popup-window-button-text"><?= GetMessage('EC_ACCEPT_MEETING')?></span><span class="popup-window-button-right"></span></span>
					<a data-bx-set-status="N" class="bxc-decline-link" href="javascript:void(0)" title="<?= GetMessage('EC_EDEV_CONF_N_TITLE')?>" id="<?=$id?>decline-link-2"><?= GetMessage('EC_EDEV_CONF_N')?></a>
				</div>
			<?elseif($curUserStatus == 'Y'):/* User accepts inviting */?>
				<div id="<?=$id?>status-conf-cnt1" class="bxc-conf-cnt">
					<span><?= GetMessage('EC_ACCEPTED_STATUS')?></span>
					<a data-bx-set-status="N" class="bxc-decline-link" href="javascript:void(0)" title="<?= GetMessage('EC_EDEV_CONF_N_TITLE')?>"><?= GetMessage('EC_EDEV_CONF_N')?></a>
				</div>
			<?elseif($curUserStatus == 'N'): /* User declines inviting*/?>
				<div class="bxc-conf-cnt">
					<span class="bxc-conf-label"><?= GetMessage('EC_DECLINE_INFO')?></span>. <a data-bx-set-status="Y" href="javascript:void(0)" title="<?= GetMessage('EC_ACCEPT_MEETING_2')?>"><?= GetMessage('EC_ACCEPT_MEETING')?></a>
				</div>
			<?elseif ($event['MEETING']['OPEN']): /* it's open meeting*/?>
				<div class="bxc-conf-cnt">
					<span class="bxc-conf-label" title="<?= GetMessage('EC_OPEN_MEETING_TITLE')?>"><?= GetMessage('EC_OPEN_MEETING')?>:</span>
					<span data-bx-set-status="Y" class="popup-window-button popup-window-button-accept" title="<?= GetMessage('EC_EDEV_CONF_Y_TITLE')?>"><span class="popup-window-button-left"></span><span class="popup-window-button-text"><?= GetMessage('EC_ACCEPT_MEETING')?></span><span class="popup-window-button-right"></span></span>
				</div>
			<?endif;?>
		</div>
	</div>

	<?endif; /*$event['IS_MEETING'])*/?>
</div>
<?/* ####### END TAB 0 ####### */?>

<?/* ####### TAB 1 : ADDITIONAL ####### */?>
<div id="<?=$id?>view-tab-1-cont" class="bxec-d-cont-div">
	<div class="bx-cal-view-text-additional">
		<table>
			<?if ($Params['sectionName'] != ''):?>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?=GetMessage('EC_T_CALENDAR')?>:</td>
				<td class="bx-cal-view-text-cell-r"><?= htmlspecialcharsEx($Params['sectionName'])?></td>
			</tr>
			<?endif;?>
			<?if ($event['IMPORTANCE'] != ''):?>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?=GetMessage('EC_IMPORTANCE_TITLE')?>:</td>
				<td class="bx-cal-view-text-cell-r"><?= GetMessage("EC_IMPORTANCE_".strtoupper($event['IMPORTANCE']))?></td>
			</tr>
			<?endif;?>
			<?if ($event['ACCESSIBILITY'] != '' && $Params['bIntranet']):?>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?=GetMessage('EC_ACCESSIBILITY_TITLE')?>:</td>
				<td class="bx-cal-view-text-cell-r"><?= GetMessage("EC_ACCESSIBILITY_".strtoupper($event['ACCESSIBILITY']))
					?></td>
			</tr>
			<?endif;?>
			<?if ($event['PRIVATE_EVENT'] && $Params['bIntranet']):?>
			<tr>
				<td class="bx-cal-view-text-cell-l"><?=GetMessage('EC_EDDIV_SPECIAL_NOTES')?>:</td>
				<td class="bx-cal-view-text-cell-r"><?=GetMessage('EC_PRIVATE_EVENT')?></td>
			</tr>
			<?endif;?>
		</table>
	</div>
</div>
<?/* ####### END TAB 1 ####### */?>
	</div>

	<?if ($viewComments):?>
	<div class="bxec-d-cont-comments-title">
		<?= GetMessage('EC_COMMENTS')?>
	</div>
	<div class="bxec-d-cont bxec-d-cont-comments">
		<?
		if ($userId == $event['OWNER_ID'])
			$permission = "Y";
		else
			$permission = 'M';
		$set = CCalendar::GetSettings();

		// A < E < I < M < Q < U < Y
		// A - NO ACCESS, E - READ, I - ANSWER
		// M - NEW TOPIC
		// Q - MODERATE, U - EDIT, Y - FULL_ACCESS
		$APPLICATION->IncludeComponent("bitrix:forum.comments", "bitrix24", array(
				"FORUM_ID" => $set['forum_id'],
				"ENTITY_TYPE" => "EV", //
				"ENTITY_ID" => $event['ID'], //Event id
				"ENTITY_XML_ID" => "EVENT_".$event['ID'], //
				"PERMISSION" => $permission, //
				"URL_TEMPLATES_PROFILE_VIEW" => $set['path_to_user'],
				"SHOW_RATING" => "Y",
				"SHOW_LINK_TO_MESSAGE" => "N",
				"BIND_VIEWER" => "Y"
			),
			false,
			array('HIDE_ICONS' => 'Y')
		);
		?>
	</div>
	<?endif;?>
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
	<div class="bxec-d-cont">
<?/* ####### TAB 0 : MAIN ####### */?>
<div id="<?=$id?>sect-tab-0-cont" class="bxec-d-cont-div" style="display: block;">
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
<div id="<?=$id?>set-tab-0-cont" class="bxec-d-cont-div" style="display: block;">

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
		<div id="bxec_mobile_iphone_all<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_IPHONE_ALL_HELP', array('#POINT_SET_PORT#' => GetMessage('EC_SET_PORT')))?></div>
		<div id="bxec_mobile_iphone_one<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_IPHONE_ONE_HELP', array('#POINT_SET_PORT#' => GetMessage('EC_SET_PORT')))?></div>
		<a id="bxec_mob_link_mac_<?=$id?>" class="bxec-mobile-link bxec-link-hidden" href="javascript: void(0)"><div class="bxec-iconkit bxec-arrow"></div><?= GetMessage('EC_MOBILE_MAC_OS');?></a>
		<div id="bxec_mobile_mac_cont<?=$id?>" style="display: none;"><?= GetMessage('EC_MOBILE_HELP_MAC_1', array('#POINT_SET_PORT#' => GetMessage('EC_SET_PORT')))?></div>
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
			$colors = array(
				'#CEE669','#E6A469','#98AEF6','#7DDEC2','#B592EC','#D98E85','#F6EA68','#DDBFEB','#FF8D89','#FFCEFF',
				'#3ABF54','#BF793A','#1C1CD8','#4BB798','#855CC5','#B25D52','#FFBD26','#C48297','#E53D37','#7DF5FF'
			);

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
							itemsSelected : <?=(empty($DESTINATION['SELECTED'])? '{}': CUtil::PhpToJSObject($DESTINATION['SELECTED']))?>
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
}
?>