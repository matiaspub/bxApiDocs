<?
class CCalendarPlanner
{
	public static function BuildDialog($Params)
	{
		global $APPLICATION;
		$id = $Params['id'];
		//$bWideDate = strpos(FORMAT_DATETIME, 'MMMM') !== false;
		$addWidthStyle = IsAmPmMode() ? ' ampm-width' : '';
?>
<div id="bx-planner-popup<?=$id?>" class="bxc-planner bxec-popup">
	<div id="<?=$id?>_plan_cont" class="bxec-plan-cont bxecpl-empty">
		<div id="<?=$id?>_plan_top_cont"  class="bxec-plan-top-cont">
			<div style="width: 700px; height: 1px;"></div>

			<div class="bxec-plan-from-to">
				<span class="bxec-date">
					<label class="bxec-date-label" for="<?=$id?>planner-from"><?=GetMessage('EC_EDEV_FROM_DATE_TIME')?></label>
					<input id="<?=$id?>planner-from" type="text" class="calendar-inp calendar-inp-cal"/>
				</span>
				<span class="bxec-time<?=$addWidthStyle?>"><?CClock::Show(array('inputId' => $id.'planner_from_time', 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM'), 'showIcon' => false));?></span>
				<span class="bxec-mdash">&mdash;</span>
				<span class="bxec-date">
					<label class="bxec-date-label" for="<?=$id?>planner-to"><?=GetMessage('EC_EDEV_TO_DATE_TIME')?></label>
					<input id="<?=$id?>planner-to" type="text" class="calendar-inp calendar-inp-cal"/>
				</span>
				<span class="bxec-time<?=$addWidthStyle?>"><?CClock::Show(array('inputId' => $id.'planner_to_time', 'inputTitle' => GetMessage('EC_EDEV_TIME_TO'), 'showIcon' => false));?></span>

				<div style="display:none;"><?$APPLICATION->IncludeComponent("bitrix:main.calendar",	"",Array("FORM_NAME" => "","INPUT_NAME" => "","INPUT_VALUE" => "","SHOW_TIME" => "N","HIDE_TIMEBAR" => "Y","SHOW_INPUT" => "N"),false, array("HIDE_ICONS" => "Y"));?></div>

				<span class="bxec-val-cnt" style="padding-right: 24px;">
					<label class="bxec-val-cnt-label" for="<?=$id?>_pl_dur"><?=GetMessage('EC_EVENT_DURATION')?></label>
					<input class="calendar-inp" style="width: 30px;" id="<?=$id?>_pl_dur" type="text"/>
					<select id="<?=$id?>_pl_dur_type" style="width: 80px;" class="calendar-select">
						<option value="min"><?=GetMessage('EC_EDEV_REM_MIN')?></option>
						<option value="hour" selected="true"><?=GetMessage('EC_EDEV_REM_HOUR')?></option>
						<option value="day"><?=GetMessage('EC_EDEV_REM_DAY')?></option>
					</select>
					<i class="bxecpl-lock-dur" id="<?=$id?>_pl_dur_lock" title="<?=GetMessage('EC_EVENT_DUR_LOCK')?>"></i>
				</span>

				<!-- Location -->
				<span class="bxec-val-cnt" style="width: 230px;">
					<label class="bxec-val-cnt-label" for="<?=$id?>_planner_location2"><?=GetMessage('EC_LOCATION')?></label>
					<input class="calendar-inp calendar-inp-time" style="width: 180px;" id="<?=$id?>_planner_location2" type="text" value="<?= GetMessage('EC_PL_SEL_MEET_ROOM')?>" />
				</span>
			</div>
			<div class="bxec-plan-field-dest">
				<?self::__ShowAttendeesDestinationHtml($Params)?>
			</div>
		</div>

		<div id="<?=$id?>_plan_grid_cont" class="bxec-plan-grid-cont">
			<table id="<?=$id?>_plan_grid_tbl" class="bxec-plan-grid-tbl">
				<tr class="bxec-header">
					<td class="bxec-scale-cont"><label for="<?=$id?>_plan_scale_sel"><?=GetMessage('EC_SCALE')?>:</label>
						<select id="<?=$id?>_plan_scale_sel">
							<option value="0">30 <?= GetMessage('EC_EDEV_REM_MIN')?></option>
							<option value="1">1 <?= GetMessage('EC_PL_DUR_HOUR1')?></option>
							<option value="2">2 <?= GetMessage('EC_PL_DUR_HOUR2')?></option>
							<option value="3">1 <?= GetMessage('EC_JS_DAY_P')?></option>
						</select>
					</td>
					<td class="bxec-separator-gr" rowSpan="2"></td>
					<td rowSpan="2"><div class="bxec-grid-cont-title"></div></td>
				</tr>
				<tr class="bxec-header">
					<td class="bxec-user">
						<div><?=GetMessage('EC_EDEV_GUESTS')?>
							<span id="<?=$id?>pl-count"></span>
						</div>
					</td>
				</tr>
				<tr>
					<td><div class="bxec-user-list-div"><div class="bxec-empty-list"> <?=GetMessage('EC_NO_ATTENDEES')?></div></div></td>
					<td class="bxec-separator"></td>
					<td><div class="bxec-grid-cont"><div class="bxec-gacc-cont"></div>
							<div class="bxecp-selection" id="<?=$id?>_plan_selection"  title="<?=GetMessage('EC_PL_EVENT')?>"><img src="/bitrix/images/1.gif" class="bxecp-sel-left" title="<?=GetMessage('EC_PL_EVENT_MOVE_LEFT')?>" /><img src="/bitrix/images/1.gif" class="bxecp-sel-right" title="<?=GetMessage('EC_PL_EVENT_MOVE_RIGHT')?>" /><img src="/bitrix/images/1.gif" class="bxecp-sel-mover" title="<?=GetMessage('EC_PL_EVENT_MOVE')?>" /></div>
						</div>
						<div class="bxec-empty-list2"><?= GetMessage('EC_NO_GUEST_MESS')?></div>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
<?
	}

	public static function Localization()
	{
		$arLangMess = array(
			'Close' => 'EC_T_CLOSE',
			'Next' => 'EC_NEXT',
			'Planner' => 'EC_PLANNER2',
			'SelectMR' => 'EC_PL_SEL_MEET_ROOM',
			'OpenMRPage' => 'EC_PL_OPEN_MR_PAGE',
			'DelAllGuestsConf' => 'EC_DEL_ALL_GUESTS_CONFIRM',
			'DelGuestTitle' => 'EC_DEL_GUEST_TITLE',
			'Acc_busy' => 'EC_ACCESSIBILITY_B',
			'Acc_quest' => 'EC_ACCESSIBILITY_Q',
			'Acc_free' => 'EC_ACCESSIBILITY_F',
			'Acc_absent' => 'EC_ACCESSIBILITY_A',
			'Importance_high' => 'EC_IMPORTANCE_H',
			'Importance_normal' => 'EC_IMPORTANCE_N',
			'Importance_low' => 'EC_IMPORTANCE_L',
			'DelOwnerConfirm' => 'EC_DEL_OWNER_CONFIRM',
			'ImpGuest' => 'EC_IMP_GUEST',
			'DurDefMin' => 'EC_EDEV_REM_MIN',
			'DurDefHour1' => 'EC_PL_DUR_HOUR1',
			'DurDefHour2' => 'EC_PL_DUR_HOUR2',
			'DurDefDay' => 'EC_JS_DAY_P',
			'Location' => 'EC_LOCATION',
			'FreeMR' => 'EC_MR_FREE',
			'DefMeetingName' => 'EC_DEF_MEETING_NAME',
			'NoGuestsErr' => 'EC_NO_GUESTS_ERR',
			'NoFromToErr' => 'EC_NO_FROM_TO_ERR',
			'Add' => 'EC_T_ADD',
			'AddAttendees' => 'EC_ADD_ATTENDEES',
			'AddGuestsDef' => 'EC_ADD_GUESTS_DEF',
			'AddGuestsEmail' => 'EC_ADD_GUESTS_EMAIL',
			'AddGroupMemb' => 'EC_ADD_GROUP_MEMBER',
			'AddGroupMembTitle' => 'EC_ADD_GROUP_MEMBER_TITLE',
			'UserEmail' => 'EC_USER_EMAIL',
			'UserAccessibility' => 'EC_ACCESSIBILITY',
			'Importance' => 'EC_IMPORTANCE',
			'FromHR' => 'EC_FROM_HR'
		);
?>
var BXPL_MESS = {0:0<?foreach($arLangMess as $m1 => $m2){echo ', '.$m1." : '".addslashes(GetMessage($m2))."'";}?>};
<?
	}

	public static function GetUserOptions()
	{
		return CUserOptions::GetOption('calendar_planner', 'settings', array(
			'width' => 700,
			'height' => 500,
			'scale' => 1
		));
	}

	public static function __ShowAttendeesDestinationHtml($Params = array())
	{
		$id = $Params['id'];
		$DESTINATION = CCalendar::GetSocNetDestination(false, $Params['event']['ATTENDEES_CODES']);
		?>
		<div id="<?= $id?>_plan_dest_cont" class="event-grid-dest-block">
			<div class="event-grid-dest-wrap-outer">
				<div class="event-grid-dest-label"><?=GetMessage("EC_EDEV_GUESTS")?>:</div>
				<div class="event-grid-dest-wrap" id="event-planner-dest-cont">
					<span id="event-planner-dest-item"></span>
					<span class="feed-add-destination-input-box" id="event-planner-dest-input-box">
						<input type="text" value="" class="feed-add-destination-inp" id="event-planner-dest-input">
					</span>
					<a href="#" class="feed-add-destination-link" id="event-planner-dest-add-link"></a>
					<script>
						BX.message({
							'BX_FPD_LINK_1':'<?=GetMessageJS("EC_DESTINATION_1")?>',
							'BX_FPD_LINK_2':'<?=GetMessageJS("EC_DESTINATION_2")?>'
						});
						window.plannerDestFormName = top.plannerDestFormName = 'bx_planner_<?=randString(6)?>';
						//
						BX.SocNetLogDestination.init({
							name : plannerDestFormName,
							searchInput : BX('event-planner-dest-input'),
							extranetUser :  false,
							bindMainPopup : { 'node' : BX('event-planner-dest-cont'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
							bindSearchPopup : { 'node' : BX('event-planner-dest-cont'), 'offsetTop' : '5px', 'offsetLeft': '15px'},
							callback : {
								select : BxPlannerSelectCallback,
								unSelect : BxPlannerUnSelectCallback,
								openDialog : BxPlannerOpenDialogCallback,
								closeDialog : BxPlannerCloseDialogCallback,
								openSearch : BxPlannerOpenDialogCallback,
								closeSearch : BxPlannerCloseSearchCallback
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
		</div>
	<?
	}
}

?>