<?
class CCalendarPlanner
{
	public static function BuildDialog($Params)
	{
		global $APPLICATION;
		$id = $Params['id'];
		$bWideDate = strpos(FORMAT_DATETIME, 'MMMM') !== false;
?>
<div id="bx-planner-popup<?=$id?>" class="bxc-planner bxec-popup<?= ($bWideDate ? ' bxec-d-cont-div-wide-date' : '')?>">

<table class="bxec-edcal-frame">
	<tr>
		<td  colSpan="2">
		<div id="<?=$id?>_plan_cont" class="bxec-plan-cont bxecpl-empty">
		<div id="<?=$id?>_plan_top_cont"  class="bxec-plan-top-cont">
			<div style="width: 650px; height: 1px;"></div>
			<div class="bxec-popup-row bxec-popup-row-from-to">
				<div class="bxec-field-label">
					<label class="bxec-from-lbl" for="<?=$id?>planner-from"><?=GetMessage('EC_EDEV_DATE_FROM')?></label>
					<label class="bxec-from-lbl" for="<?=$id?>planner-to"><?=GetMessage('EC_EDEV_DATE_TO')?></label>

					<label for="<?=$id?>_pl_dur"><?=GetMessage('EC_EVENT_DURATION')?></label>
				</div>
				<div>
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-calendar"><input id="<?=$id?>planner-from"  type="text"/></span>
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-time"><?CClock::Show(array('inputId' => $id.'planner_from_time', 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM')));?><i class="bxec-time-icon"></i></span>
				&mdash;
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-calendar"><input id="<?=$id?>planner-to"  type="text"/></span>
				<span class="bxec-field-val-2 bxec-field-title-inner bxec-field-time"><?CClock::Show(array('inputId' => $id.'planner_to_time', 'inputTitle' => GetMessage('EC_EDEV_TIME_FROM')));?><i class="bxec-time-icon"></i></span>

				<span class="bxec-field-duration" title="<?=GetMessage('EC_EVENT_DURATION_TITLE')?>">
					<span class="bxec-field-val-2 bxec-field-title-inner"><input style="width: 57px;" id="<?=$id?>_pl_dur" type="text" title="<?=GetMessage('EC_EVENT_DURATION_TITLE')?>"/>
					</span>
					<span class="bxec-field-val-2 bxec-field-title-inner" style="height: 20px;">
					<select id="<?=$id?>_pl_dur_type" style="width: 70px;">
						<option value="min"><?=GetMessage('EC_EDEV_REM_MIN')?></option>
						<option value="hour" selected="true"><?=GetMessage('EC_EDEV_REM_HOUR')?></option>
						<option value="day"><?=GetMessage('EC_EDEV_REM_DAY')?></option>
					</select>
					</span>
					<img src="/bitrix/images/1.gif" class="bxecpl-lock-dur" id="<?=$id?>_pl_dur_lock" title="<?=GetMessage('EC_EVENT_DUR_LOCK')?>"/>
				</span>
				<div class="bxec-cal-icon-bogus"><?$APPLICATION->IncludeComponent("bitrix:main.calendar", "", Array("FORM_NAME" => "","INPUT_NAME" => "","INPUT_VALUE" => "","SHOW_TIME" => "N","HIDE_TIMEBAR" => "Y","SHOW_INPUT" => "N"),false, array("HIDE_ICONS" => "Y"));?></div>
				</div>
			</div>

			<div style="padding: 5px 0 0 0;">
				<!-- Add users-->
				<span class="bxc-add-guest-link"  id="<?=$id?>pl_user_control_link"></span>
				<!-- Location -->
				<span title="<?=GetMessage('EC_LOCATION_TITLE')?>" class="bxecpl-loc-cont bxec-field-location">
					<label for="<?=$id?>_planner_location2"><?=GetMessage('EC_LOCATION')?>:</label>
					<input style="width: 200px;" id="<?=$id?>_planner_location2" type="text"  title="<?=GetMessage('EC_LOCATION_TITLE')?>" value="<?= GetMessage('EC_PL_SEL_MEET_ROOM')?>" class="ec-label" />
				</span>
			</div>
		</div>


		<div id="<?=$id?>_plan_grid_cont" class="bxec-plan-grid-cont"><table class="bxec-plan-grid-tbl">
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
							<i class="bxplan-del bxplan-del-all" id="<?=$id?>_planner_del_all" title="<?=GetMessage('EC_DEL_ALL_GUESTS_TITLE')?>"></i>
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
		</td>
	</tr>
</table>
<script>
function BXPlannerAttendeeOnchange(arUsers){BX.onCustomEvent(window, 'onPlannerAttendeeOnChange', [arUsers]);}
</script>
<?

$isExtranetGroup = false;

if ($Params["bSocNet"] && $Params["type"] == "group" && intval($Params["ownerId"]) > 0 && CModule::IncludeModule("extranet"))
	$isExtranetGroup = CExtranet::IsExtranetSocNetGroup($Params["ownerId"]);

$APPLICATION->IncludeComponent(
	"bitrix:intranet.user.selector.new", "", array(
			"MULTIPLE" => "Y",
			"NAME" => "BXPlannerUserSelect",
			"VALUE" => array(),
			"POPUP" => "Y",
			"ON_CHANGE" => "BXPlannerAttendeeOnchange",
			"SITE_ID" => SITE_ID,
			"NAME_TEMPLATE" => CCalendar::GetUserNameTemplate(),
			"SHOW_EXTRANET_USERS" => $isExtranetGroup ? "FROM_EXACT_GROUP" : "NONE",
			"EX_GROUP" => $isExtranetGroup ? $Params["ownerId"] : ""
		), null, array("HIDE_ICONS" => "Y")
	);
?>
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
}

?>