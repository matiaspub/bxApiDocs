<?
if(!CModule::IncludeModule('rest') || !CModule::IncludeModule('calendar'))
{
	return;
}

IncludeModuleLangFile(__FILE__);
/**
 * This class used for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */
final class CCalendarRestService extends IRestService
{
	const SCOPE_NAME = 'calendar';

	public static function OnRestServiceBuildDescription()
	{
		$methods = array(
			"calendar.event.get" => array(__CLASS__, "EventGet"),
			"calendar.event.add" => array(__CLASS__, "EventAdd"),
			"calendar.event.update" => array(__CLASS__, "EventUpdate"),
			"calendar.event.delete" => array(__CLASS__, "EventDelete"),
			"calendar.event.get.nearest" => array(__CLASS__, "EventGetNearest"),

			"calendar.section.get" => array(__CLASS__, "SectionGet"),
			"calendar.section.add" => array(__CLASS__, "SectionAdd"),
			"calendar.section.update" => array(__CLASS__, "SectionUpdate"),
			"calendar.section.delete" => array(__CLASS__, "SectionDelete"),

			"calendar.meeting.status.set" => array(__CLASS__, "MeetingStatusSet"),
			"calendar.meeting.status.get" => array(__CLASS__, "MeetingStatusGet"),
			"calendar.meeting.params.set" => array(__CLASS__, "MeetingParamsSet"),
			"calendar.accessibility.get" => array(__CLASS__, "MeetingAccessibilityGet"),
			"calendar.settings.get" => array(__CLASS__, "SettingsGet"),
			//"calendar.settings.set" => array(__CLASS__, "SettingsSet"),
			"calendar.user.settings.get" => array(__CLASS__, "UserSettingsGet"),
			"calendar.user.settings.set" => array(__CLASS__, "UserSettingsSet")
		);

		return array(self::SCOPE_NAME => $methods);
	}

	/*
	 * Returns array of events
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['type'] - (required) calendar type ('user'|'group')
	 * $arParams['ownerId'] - owner id
	 * $arParams['from'] - datetime, "from" limit, default value - 1 month before current date
	 * $arParams['to'] - datetime, "to" limit, default value - 3 month after current date
	 * $arParams['section'] - inline or array of sections
	 * @return array of events
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.event.get",
	 * {
	 * 		type: 'user',
	 *		ownerId: '1',
	 * 		from: '2013-06-20',
	 * 		to: '2013-08-20',
	 * 		section: [21, 44]
	 * });
	 *
	 */
	public static function EventGet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.event.get";

		$necessaryParams = array('type');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$type = $arParams['type'];
		$ownerId = intval($arParams['ownerId']);
		$from = false;
		$to = false;
		if (isset($arParams['from']))
			$from = CRestUtil::unConvertDateTime($arParams['from']);
		if (isset($arParams['to']))
			$to = CRestUtil::unConvertDateTime($arParams['to']);

		// Default values for from-to period
		if ($from === false && $to === false)
		{
			// Limits
			$ts = time();
			$pastDays = 30;
			$futureDays = 90;
			$from = CCalendar::Date($ts - CCalendar::DAY_LENGTH * $pastDays, false);
			$to = CCalendar::Date($ts + CCalendar::DAY_LENGTH * $futureDays, false);
		}
		elseif($from !== false && $to === false)
		{
			$to = CCalendar::Date(CCalendar::GetMaxTimestamp(), false);
		}

		$arSectionIds = array();

		$sections = CCalendarSect::GetList(array('arFilter' => array('CAL_TYPE' => $type,'OWNER_ID' => $ownerId)));
		foreach($sections as $section)
		{
			if ($section['PERM']['view_full'] || $section['PERM']['view_title'] || $section['PERM']['view_time'])
				$arSectionIds[] = $section['ID'];
		}

		if (isset($arParams['section']))
		{
			if (!is_array($arParams['section']) && $arParams['section'] > 0)
				$arParams['section'] = array($arParams['section']);
			$arSectionIds = array_intersect($arSectionIds, $arParams['section']);
		}

		$params = array(
			'type' => $type,
			'ownerId' => $ownerId,
			'userId' => $userId,
			'section' => $arSectionIds,
			'fromLimit' => $from,
			'toLimit' => $to
		);

		$arAttendees = array();
		$arEvents = CCalendar::GetEventList($params, $arAttendees);

		return $arEvents;
	}

	/*
	 * Add new event
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['type'] - (required), number, calendar type
	 * $arParams['ownerId'] - (required), number, owner id
	 * $arParams['from'] - (required) datetime, "from" limit
	 * $arParams['to'] - (required) datetime, "to" limit
	 * $arParams['from_ts'] - timestamp, "from" limit, can be set instead of $arParams['from']
	 * $arParams['to_ts'] - timestamp, "to" limit, can be set instead of $arParams['to']
	 * $arParams['section'] - (required), number, id of the section
	 * $arParams['name'] - (required), string, name of the event
	 * $arParams['skip_time'] - "Y"|"N",
	 * $arParams['description'] - string, description of the event
	 * $arParams['color'] - background color of the event
	 * $arParams['text_color'] - text color of the event
	 * $arParams['accessibility'] - 'busy'|'quest'|'free'|'absent' - accessibility for user
	 * $arParams['importance'] - 'high' | 'normal' | 'low' - importance for the event
	 * $arParams['private_event'] - "Y" | "N"
	 * $arParams['rrule'] - array of the recurence Rule
	 * $arParams['is_meeting'] - "Y" | "N"
	 * $arParams['location'] - location
	 * $arParams['remind'] - array(
	 * 	array(
	 * 		'type' => 'min'|'hour'|'day', type of reminder
	 * 		'count' => count of time
	 * 	)
	 * ) - reminders
	 * $arParams['attendees'] - array of the attendees for meeting if ($arParams['is_meeting'] == "Y")
	 * $arParams['host'] - host of the event
	 * $arParams['meeting'] = array(
		'text' =>  inviting text,
		'open' => true|false if meeting is open,
		'notify' => true|false,
		'reinvite' => true|false
	)
	 * @return id of the new event.
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.event.add",
	 * {
	 *		type: 'user',
	 *	 	ownerId: '2',
	 * 		name: 'New Event Name',
	 * 		description: 'Description for event',
	 * 		from: '2013-06-14',
	 * 		to: '2013-06-14',
	 * 		skipTime: 'Y',
	 * 		section: 5,
	 * 		color: '#9cbe1c',
	 * 		text_color: '#283033',
	 * 		accessibility: 'absent',
	 * 		importance: 'normal',
	 * 		is_meeting: 'Y',
	 * 		private_event: 'N',
	 * 		remind: [{type: 'min', count: 20}],
	 * 		location: 'Kaliningrad',
	 * 		attendees: [1, 2, 3],
	 *		host: 2,
	 * 		meeting: {
	 * 			text: 'inviting text',
	 * 			open: true,
	 * 			notify: true,
	 * 			reinvite: false
	 * 		}
	 * });
	 */
	public static function EventAdd($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.event.add";

		if (isset($arParams['from']))
			$arParams['from'] = CRestUtil::unConvertDateTime($arParams['from']);

		if (isset($arParams['to']))
			$arParams['to'] = CRestUtil::unConvertDateTime($arParams['to']);

		if (isset($arParams['from_ts']) && !isset($arParams['from']))
			$arParams['from'] = CCalendar::Date($arParams['from_ts']);

		if (isset($arParams['to_ts']) && !isset($arParams['to']))
			$arParams['to'] = CCalendar::Date($arParams['to_ts']);

		$necessaryParams = array('from', 'to', 'name', 'ownerId', 'type', 'section');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$type = $arParams['type'];
		$ownerId = intval($arParams['ownerId']);

		$sectionId = $arParams['section'];
		$res = CCalendarSect::GetList(array('arFilter' => array('CAL_TYPE' => $type,'OWNER_ID' => $ownerId, 'ID' => $sectionId)));
		if ($res && is_array($res) && isset($res[0]))
		{
			if (!$res[0]['PERM']['add'])
				throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));
		}
		else
		{
			throw new Exception('CAL_REST_SECTION_ERROR');
		}

		$arFields = array(
			"CAL_TYPE" => $type,
			"OWNER_ID" => $ownerId,
			"NAME" => trim($arParams['name']),
			"DATE_FROM" => $arParams['from'],
			"DATE_TO" => $arParams['to'],
			"SECTIONS" => $sectionId
		);

		if (isset($arParams['skip_time']))
			$arFields["SKIP_TIME"] = $arParams['skip_time'] == 'Y';

		if (isset($arParams['description']))
			$arFields["DESCRIPTION"] = trim($arParams['description']);

		if (isset($arParams['color']))
		{
			$color = CCalendar::Color($arParams['color']);
			if ($color)
				$arFields["COLOR"] = $color;
		}

		if (isset($arParams['text_color']))
		{
			$color = CCalendar::Color($arParams['text_color']);
			if ($color)
				$arFields["TEXT_COLOR"] = $color;
		}

		if (isset($arParams['accessibility']))
			$arFields["ACCESSIBILITY"] = $arParams['accessibility'];

		if (isset($arParams['importance']))
			$arFields["IMPORTANCE"] = $arParams['importance'];

		if (isset($arParams['private_event']))
			$arFields["PRIVATE_EVENT"] = $arParams['private_event'] == "Y";

		if (isset($arParams['rrule']))
			$arFields["RRULE"] = $arParams['rrule'];

		if (isset($arParams['is_meeting']))
			$arFields["IS_MEETING"] = $arParams['is_meeting'] == "Y";

		if (isset($arParams['location']))
			$arFields["LOCATION"] = $arParams['location'];

		if (isset($arParams['remind']))
			$arFields["REMIND"] = $arParams['remind'];

		if ($arFields['IS_MEETING'])
		{
			$arFields['ATTENDEES'] = isset($arParams['attendees']) ? $arParams['attendees'] : false;
			$meeting = isset($arParams['meeting']) ? $arParams['meeting'] : array();
			$arFields['MEETING_HOST'] = isset($arParams['host']) ? intVal($arParams['host']) : $userId;
			$arFields['MEETING'] = array(
				'HOST_NAME' => CCalendar::GetUserName($arFields['MEETING_HOST']),
				'TEXT' => $meeting['text'],
				'OPEN' => (boolean) $meeting['open'],
				'NOTIFY' => (boolean) $meeting['notify'],
				'REINVITE' => (boolean) $meeting['reinvite']
			);
		}

		$newId = CCalendar::SaveEvent(array(
			'arFields' => $arFields
		));

		if (!$newId)
			throw new Exception(GetMessage("CAL_REST_EVENT_NEW_ERROR"));

		return $newId;
	}

	/*
	 * Edit existent event
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['id'] - (required) event id,
	 * $arParams['type'] - number, (required) calendar type
	 * $arParams['ownerId'] - number, owner id
	 * $arParams['from'] - datetime, "from" limit
	 * $arParams['to'] - datetime, "to" limit
	 * $arParams['from_ts'] - timestamp, "from" limit,
	 * $arParams['to_ts'] - timestamp, "to" limit
	 * $arParams['section'] - number,(required) id of the section
	 * $arParams['name'] - string, (required) name of the event
	 * $arParams['skip_time'] - "Y"|"N",
	 * $arParams['description'] - string, description of the event
	 * $arParams['color'] - background color of the event
	 * $arParams['text_color'] - text color of the event
	 * $arParams['accessibility'] - 'busy'|'quest'|'free'|'absent' - accessibility for user
	 * $arParams['importance'] - 'high' | 'normal' | 'low' - importance for the event
	 * $arParams['private_event'] - "Y" | "N"
	 * $arParams['rrule'] - array of the recurence Rule
	 * $arParams['is_meeting'] - "Y" | "N"
	 * $arParams['location'] - location
	 * $arParams['remind'] - array(
	 * 	array(
	 * 		'type' => 'min'|'hour'|'day', type of reminder
	 * 		'count' => count of time
	 * 	)
	 * ) - reminders
	 * $arParams['attendees'] - array of the attendees for meeting if ($arParams['is_meeting'] == "Y")
	 * $arParams['host'] - host of the event
	 * $arParams['meeting'] = array(
	 * 		'text' =>  inviting text,
	 * 		'open' => true|false if meeting is open,
	 * 		'notify' => true|false,
	 * 		'reinvite' => true|false
	 * 	)
	 * @return id of edited event
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.event.update",
	 * {
	 * 		id: 699
	 *		type: 'user',
	 *	 	ownerId: '2',
	 * 		name: 'Changed Event Name',
	 * 		description: 'New description for event',
	 * 		from: '2013-06-17',
	 * 		to: '2013-06-17',
	 * 		skipTime: 'Y',
	 * 		section: 5,
	 * 		color: '#9cbe1c',
	 * 		text_color: '#283033',
	 * 		accessibility: 'free',
	 * 		importance: 'normal',
	 * 		is_meeting: 'N',
	 * 		private_event: 'Y',
	 * 		remind: [{type: 'min', count: 10}]
	 * });
	 */
	public static function EventUpdate($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.event.update";

		$necessaryParams = array('id', 'ownerId', 'type');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$id = intVal($arParams['id']);
		$type = $arParams['type'];
		$ownerId = intval($arParams['ownerId']);
		$arFields = array(
			"ID" => $id
		);

		if (isset($arParams['from_ts']))
			$arFields["DATE_FROM"] = CCalendar::Date($arParams['from_ts']);

		if (isset($arParams['to_ts']))
			$arFields["DATE_TO"] = CCalendar::Date($arParams['to_ts']);

		if (isset($arParams['skip_time']))
			$arFields["SKIP_TIME"] = $arParams['skip_time'] == 'Y';

		if (isset($arParams['name']))
			$arFields["NAME"] = trim($arParams['name']);

		if (isset($arParams['description']))
			$arFields["DESCRIPTION"] = trim($arParams['description']);

		if (isset($arParams['section']))
		{
			$sectionId = $arParams['section'];
			$arFields["SECTIONS"] = array($sectionId);

			$res = CCalendarSect::GetList(array('arFilter' => array('CAL_TYPE' => $type,'OWNER_ID' => $ownerId, 'ID' => $arParams['section'])));
			if ($res && is_array($res) && isset($res[0]))
			{
				if (!$res[0]['PERM']['edit'])
					throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));
			}
			else
			{
				throw new Exception('CAL_REST_SECTION_ERROR');
			}
		}

		if (isset($arParams['color']))
		{
			$color = CCalendar::Color($arParams['color']);
			if ($color)
				$arFields["COLOR"] = $color;
		}

		if (isset($arParams['text_color']))
		{
			$color = CCalendar::Color($arParams['text_color']);
			if ($color)
				$arFields["TEXT_COLOR"] = $color;
		}

		if (isset($arParams['accessibility']))
			$arFields["ACCESSIBILITY"] = $arParams['accessibility'];

		if (isset($arParams['importance']))
			$arFields["IMPORTANCE"] = $arParams['importance'];

		if (isset($arParams['private_event']))
			$arFields["PRIVATE_EVENT"] = $arParams['private_event'] == "Y";

		if (isset($arParams['rrule']))
			$arFields["RRULE"] = $arParams['rrule'];

		if (isset($arParams['is_meeting']))
			$arFields["IS_MEETING"] = $arParams['is_meeting'] == "Y";

		if (isset($arParams['location']))
			$arFields["LOCATION"] = $arParams['LOCATION'];

		if (isset($arParams['remind']))
			$arFields["REMIND"] = $arParams['REMIND'];

		if ($arFields['IS_MEETING'])
		{
			$arFields['ATTENDEES'] = isset($arParams['attendees']) ? $arParams['attendees'] : false;
			$meeting = isset($arParams['meeting']) ? $arParams['meeting'] : array();
			$arFields['MEETING_HOST'] = isset($arParams['host']) ? intVal($arParams['host']) : $userId;
			$arFields['MEETING'] = array(
				'HOST_NAME' => CCalendar::GetUserName($arFields['MEETING_HOST']),
				'TEXT' => $meeting['text'],
				'OPEN' => (boolean) $meeting['open'],
				'NOTIFY' => (boolean) $meeting['notify'],
				'REINVITE' => (boolean) $meeting['reinvite']
			);
		}

		$newId = CCalendar::SaveEvent(array(
			'arFields' => $arFields
		));

		if (!$newId)
			throw new Exception(GetMessage("CAL_REST_EVENT_UPDATE_ERROR"));

		return $newId;
	}

	/*
	 * Delete event
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['type'] (required) calendar type
	 * $arParams['ownerId'] (required) owner id
	 * $arParams['id'] (required) event id
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.event.delete",
	 * {
	 * 		id: 698
	 *		type: 'user',
	 *	 	ownerId: '2'
	 * });
	 */
	public static function EventDelete($arParams = array(), $nav = null, $server = null)
	{
		$methodName = "calendar.event.delete";
		if (isset($arParams['id']) && intVal($arParams['id']) > 0)
			$id = intVal($arParams['id']);
		else
			throw new Exception(GetMessage('CAL_REST_EVENT_ID_EXCEPTION'));

		$res = CCalendar::DeleteEvent($id, false);

		if ($res !== true)
		{
			if ($res === false)
				throw new Exception(GetMessage('CAL_REST_EVENT_DELETE_ERROR'));
			else
				throw new Exception($res);
		}

		return $res;
	}

	/*
	 * Return array of bearest events for current user
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['ownerId'] - owner id
	 * $arParams['type'] - calendar type
	 * $arParams['days'] - future days count (default - 60)
	 * $arParams['forCurrentUser'] - true/false - list of nearest events for current user
	 * $arParams['maxEventsCount'] - maximum events count
	 * $arParams['detailUrl'] - url for calendar
	 *
	 * @return array of events
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.event.get.nearest",
	 * {
	 *		type: 'user',
	 *	 	ownerId: '2',
	 * 		days: 10,
	 * 		forCurrentUser: true,
	 *		detailUrl: '/company/personal/user/#user_id#/calendar/'
	 * });
	 *
	 */
	public static function EventGetNearest($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.event.get.nearest";

		if (!isset($arParams['type'], $arParams['ownerId']) || $arParams['forCurrentUser'])
		{
			$arParams['type'] = 'user';
			$arParams['ownerId'] = $userId;
			$arParams['forCurrentUser'] = true;
		}

		if (!isset($arParams['days']))
			$arParams['days'] = 60;

		// Limits
		$ts = time();
		$fromLimit = CCalendar::Date($ts, false);
		$toLimit = CCalendar::Date($ts + CCalendar::DAY_LENGTH * $arParams['days'], false);

		$arEvents = CCalendar::GetNearestEventsList(
			array(
				'bCurUserList' => !!$arParams['forCurrentUser'],
				'fromLimit' => $fromLimit,
				'toLimit' => $toLimit,
				'type' => $arParams['CALENDAR_TYPE'],
				'sectionId' => $arParams['CALENDAR_SECTION_ID']
			));

		if ($arEvents == 'access_denied' || $arEvents == 'inactive_feature')
		{
			throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));
		}
		elseif (is_array($arEvents))
		{

			if (isset($arParams['detailUrl']))
			{
				if (strpos($arParams['detailUrl'], '?') !== FALSE)
					$arParams['detailUrl'] = substr($arParams['detailUrl'], 0, strpos($arParams['detailUrl'], '?'));
				$arParams['detailUrl'] = str_replace('#user_id#', $userId, strtolower($arParams['detailUrl']));

				for ($i = 0, $l = count($arEvents); $i < $l; $i++)
				{
					$arEvents[$i]['~detailUrl'] = $arParams['detailUrl'].'?EVENT_ID='.$arEvents[$i]['ID'].'&EVENT_DATE='.$arEvents[$i]['DT_FROM'];
				}
			}

			if (isset($arParams['maxEventsCount']))
				array_splice($arEvents, intVal($arParams['maxEventsCount']));
		}

		return $arEvents;
	}

	/*
	 * Return list of sections
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['type'] (required) calendar type
	 * $arParams['ownerId'] (required) owner id
	 *
	 * @return array of sections
	 *
	 *  @example (Javascript)
	 * BX24.callMethod("calendar.section.get",
	 * {
	 * 		type: 'user',
	 *		ownerId: '1'
	 * });
	 */
	public static function SectionGet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.section.get";

		if (isset($arParams['type']))
			$type = $arParams['type'];
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'type')));

		if (isset($arParams['ownerId']))
			$ownerId = intval($arParams['ownerId']);
		elseif($type == 'user')
			$ownerId = $userId;
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'ownerId')));

		$arFilter = array(
			'CAL_TYPE' => $type,
			'OWNER_ID' => $ownerId,
			'ACTIVE' => "Y"
		);

		$res = CCalendarSect::GetList(array('arFilter' => $arFilter));

		foreach($res as $i => $section)
		{
			unset(
			$res[$i]['OUTLOOK_JS'],
			$res[$i]['DAV_EXCH_CAL'],
			$res[$i]['DAV_EXCH_MOD'],
			$res[$i]['SORT'],
			$res[$i]['PARENT_ID'],
			$res[$i]['IS_EXCHANGE'],
			$res[$i]['EXTERNAL_ID'],
			$res[$i]['ACTIVE'],
			$res[$i]['CAL_DAV_MOD'],
			$res[$i]['CAL_DAV_CAL'],
			$res[$i]['XML_ID']
			);
		}

		return $res;
	}

	/*
	 * Add new section
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['type'] - (required), number, calendar type
	 * $arParams['ownerId'] - (required), number, owner id
	 * $arParams['name'] - string, (required) name of the section
	 * $arParams['description'] - string, description of the section
	 * $arParams['color']
	 * $arParams['text_color']
	 * $arParams['export'] = array(
		'ALLOW' => true|false,
		'SET' => array
	)
	 * $arParams['access'] - array of access data
	 *
	 * @return id of created section
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.section.add",
	 * {
	 * 		type: 'user',
	 *	 	ownerId: '2',
	 * 		name: 'New Section',
	 * 		description: 'Description for section',
	 * 		color: '#9cbeee',
	 * 		text_color: '#283000',
	 * 		export: [{ALLOW: false}]
	 * 		access: {
	 * 			'D114': 17,
	 * 			'G2': 13,
	 * 			'U2':15
	 * 		}
	 * });
	 */
	public static function SectionAdd($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.section.add";
		$DEFAULT_COLOR = '#E6A469';
		$DEFAULT_TEXT_COLOR = '#000000';

		if (isset($arParams['type']))
			$type = $arParams['type'];
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'type')));

		if (isset($arParams['ownerId']))
			$ownerId = intval($arParams['ownerId']);
		elseif($type == 'user')
			$ownerId = $userId;
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'ownerId')));

		$perm = CCalendar::GetPermissions(array(
			'type' => $type,
			'ownerId' => $ownerId,
			'userId' => $userId,
			'setProperties' => false
		));

		if (($type == 'group' || $type == 'user'))
		{
			if (!$perm['section_edit'])
				throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));
		}
		else // other types
		{
			if (!CCalendarType::CanDo('calendar_type_edit_section'))
				throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));
		}

		$arFields = Array(
			'CAL_TYPE' => $type,
			'OWNER_ID' => $ownerId,
			'NAME' => (isset($arParams['name']) && trim($arParams['name']) != '') ? trim($arParams['name']) : '',
			'DESCRIPTION' => (isset($arParams['description']) && trim($arParams['description']) != '') ? trim($arParams['description']) : ''
		);

		if (isset($arParams['export']) && isset($arParams['export']['ALLOW']) && isset($arParams['export']['SET']))
		{
			$arFields['EXPORT'] = array(
				'ALLOW' => !!$arParams['export']['ALLOW'],
				'SET' => $arParams['export']['SET']
			);
		}

		if (isset($arParams['color']))
			$arFields['COLOR'] = CCalendar::Color($arParams['color'], $DEFAULT_COLOR);
		else
			$arFields['COLOR'] = $DEFAULT_COLOR;

		if (isset($arParams['text_color']))
			$arFields['TEXT_COLOR'] = CCalendar::Color($arParams['text_color'], $DEFAULT_TEXT_COLOR);
		else
			$arFields['TEXT_COLOR'] = $DEFAULT_TEXT_COLOR;

		if (isset($arParams['access']) && is_array($arParams['access']))
			$arFields['ACCESS'] = $arParams['access'];

		$id = CCalendar::SaveSection(
			array(
				'bAffectToDav' => false,
				'arFields' => $arFields
			)
		);

		if (!$id)
			throw new Exception(GetMessage('CAL_REST_SECTION_NEW_ERROR'));

		CCalendarSect::SetClearOperationCache(true);
		return $id;
	}

	/*
	 * Update section
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['id'] - (required) number, calendar type
	 * $arParams['type'] - (required) number, calendar type
	 * $arParams['ownerId'] - (required) number, owner id
	 * $arParams['name'] - string, name of the section
	 * $arParams['description'] - string, description of the section
	 * $arParams['color']
	 * $arParams['text_color']
	 * $arParams['export'] = array(
		'ALLOW' => true|false,
		'SET' => array
	)
	 * $arParams['access'] - array of access data
	 *
	 * @return id of modified section
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.section.update",
	 * {
	 * 		id: 325,
	 * 		type: 'user',
	 *	 	ownerId: '2',
	 * 		name: 'Changed Section Name',
	 * 		description: 'New description for section',
	 * 		color: '#9cbeAA',
	 * 		text_color: '#283099',
	 * 		export: [{ALLOW: false}]
	 * 		access: {
	 * 			'D114': 17,
	 * 			'G2': 13,
	 * 			'U2':15
	 * 		}
	 * });
	 */
	public static function SectionUpdate($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.section.update";

		if (isset($arParams['type']))
			$type = $arParams['type'];
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'type')));

		if (isset($arParams['ownerId']))
			$ownerId = intval($arParams['ownerId']);
		elseif($type == 'user')
			$ownerId = $userId;
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'ownerId')));

		if (isset($arParams['id']) && intVal($arParams['id']) > 0)
			$id = intVal($arParams['id']);
		else
			throw new Exception(GetMessage('CAL_REST_SECT_ID_EXCEPTION'));

		if (!CCalendar::IsPersonal($type, $ownerId, $userId) && !CCalendarSect::CanDo('calendar_edit_section', $id, $userId))
			throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));

		$arFields = Array(
			'ID' => $id,
			'CAL_TYPE' => $type,
			'OWNER_ID' => $ownerId
		);

		if (isset($arParams['name']) && trim($arParams['name']) != '')
			$arFields['NAME'] = trim($arParams['name']);

		if (isset($arParams['description']) && trim($arParams['description']) != '')
			$arFields['DESCRIPTION'] = trim($arParams['description']);

		if (isset($arParams['color']))
			$arFields['COLOR'] = CCalendar::Color($arParams['color']);

		if (isset($arParams['text_color']))
			$arFields['TEXT_COLOR'] = CCalendar::Color($arParams['text_color']);

		if (isset($arParams['access']) && is_array($arParams['access']))
			$arFields['ACCESS'] = $arParams['access'];

		$id = intVal(CCalendar::SaveSection(
			array(
				'bAffectToDav' => false,
				'arFields' => $arFields
			)
		));

		if (!$id)
			throw new Exception(GetMessage('CAL_REST_SECTION_SAVE_ERROR'));

		return $id;
	}

	/*
	 * Delete section
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['type'] (required) calendar type
	 * $arParams['ownerId'] (required) owner id
	 * $arParams['id'] (required) section id
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.section.delete",
	 * {
	 * 		type: 'user',
	 *	 	ownerId: '2',
	 * 		id: 521
	 * });
	 */
	public static function SectionDelete($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.section.delete";

		if (isset($arParams['type']))
			$type = $arParams['type'];
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'type')));

		if (isset($arParams['ownerId']))
			$ownerId = intval($arParams['ownerId']);
		elseif($type == 'user')
			$ownerId = $userId;
		else
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#REST_METHOD#' => $methodName, '#PARAM_NAME#' => 'ownerId')));

		if (isset($arParams['id']) && intVal($arParams['id']) > 0)
			$id = intVal($arParams['id']);
		else
			throw new Exception(GetMessage('CAL_REST_SECT_ID_EXCEPTION'));

		if (!CCalendar::IsPersonal($type, $ownerId, $userId) && !CCalendarSect::CanDo('calendar_edit_section', $id, $userId))
			throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));

		$res = CCalendar::DeleteSection($id);

		if (!$res)
			throw new Exception(GetMessage('CAL_REST_SECTION_DELETE_ERROR'));

		return $res;
	}

	/*
	 * Set meeting status for current user
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['eventId'] - event id
	 * $arParams['status'] = 'Y' | 'N' | 'Q'
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.meeting.status.set",
	 * {
	 * 		eventId: '651',
	 *	 	status: 'Y'
	 * });
	 */
	public static function MeetingStatusSet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.meeting.status.set";

		$necessaryParams = array('eventId', 'status');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$arParams['status'] = strtoupper($arParams['status']);
		if (!in_array($arParams['status'], array('Y', 'N', 'Q')))
			throw new Exception(GetMessage('CAL_REST_PARAM_ERROR', array('#PARAM_NAME#')));

		CCalendarEvent::SetMeetingStatus(
			$userId,
			$arParams['eventId'],
			$arParams['status']
		);

		return true;
	}

	/*
	 * Return meeting status for current user for given event
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['eventId'] - (required) event id
	 *
	 * @return status - "Y" | "N" | "Q"
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.meeting.status.get",
	 * {
	 * 		eventId: '651'
	 * });
	 */
	public static function MeetingStatusGet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.meeting.status.get";

		$necessaryParams = array('eventId');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$status = CCalendarEvent::GetMeetingStatus(
			$userId,
			$arParams['eventId'],
			$arParams['status']
		);

		if ($status === false)
			throw new Exception(GetMessage('CAL_REST_GET_STATUS_ERROR'));

		return $status;
	}

	/*
	 * Set meeting params for current user
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['eventId'] - event id
	 * $arParams['accessibility']
	 * $arParams['remind']
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.meeting.params.set",
	 * {
	 * 		eventId: '651',
	 *	 	accessibility: 'free',
	 * 		remind: [{type: 'min', count: 20}]
	 * });
	 */
	public static function MeetingParamsSet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.meeting.params.set";

		$necessaryParams = array('eventId');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$result = CCalendarEvent::SetMeetingParams(
			$userId,
			intVal($arParams['eventId']),
			array(
				'ACCESSIBILITY' => $arParams['accessibility'],
				'REMIND' =>  $arParams['remind']
			)
		);

		if (!$result)
			throw new Exception(GetMessage('CAL_REST_GET_DATA_ERROR'));

		return true;
	}

	/*
	 * Allow to get user's accessibility
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['users'] - (required) array of user ids
	 * $arParams['from'] - (required) date, from limit
	 * $arParams['to'] - (required) date, to limit
	 *
	 * @return array - array('user_id' => array()) - information about accessibility for each asked user
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.accessibility.get",
	 * {
	 * 		from: '2013-06-20',
	 * 		to: '2013-12-20',
	 * 		users: [1, 2, 34]
	 * });
	 */
	public static function MeetingAccessibilityGet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.accessibility.get";

		$necessaryParams = array('from', 'to', 'users');
		foreach ($necessaryParams as $param)
		{
			if (!isset($arParams[$param]) || empty($arParams[$param]))
				throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => $param,'#REST_METHOD#' => $methodName)));
		}

		$from = CRestUtil::unConvertDate($arParams['from']);
		$to = CRestUtil::unConvertDate($arParams['to']);

		$res = CCalendar::GetAccessibilityForUsers(array(
			'users' => $arParams['users'],
			'from' => $from,
			'to' => $to,
			'getFromHR' => true
		));

		return $res;
	}

	/*
	 * Return calendar general settings
	 *
	 * @return array of settings
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.settings.get", {});
	 */
	public static function SettingsGet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.settings.get";
		$settings = CCalendar::GetSettings();
		return $settings;
	}

	/*
	 * Set calendar settings
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['settings'] - (required) array of user's settings
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.settings.set",
	 * {
	 * 		settings: {
	 * 			work_time_start: 9,
	 * 			work_time_end: 19,
	 * 			year_holidays: '1.01,2.01,7.01,23.02,8.03,1.05,9.05,12.06,4.11,12.12,03.04,05.04',
	 * 			week_holidays:['SA','SU'],
	 *			week_start: 'MO'
	 * 		}
	 * });
	 */
	public static function SettingsSet($arParams = array(), $nav = null, $server = null)
	{
		global $USER;
		$methodName = "calendar.settings.set";

		if (!$USER->CanDoOperation('bitrix24_config') && !$USER->CanDoOperation('edit_php'))
			throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));

		if (!isset($arParams['settings']))
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => 'settings','#REST_METHOD#' => $methodName)));

		CCalendar::SetSettings($arParams['settings']);

		return true;
	}

	/*
	 * Clears calendar settings
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.settings.clear",{});
	 */
	public static function SettingsClear($arParams = array(), $nav = null, $server = null)
	{
		global $USER;
		$methodName = "calendar.settings.clear";

		if (!$USER->CanDoOperation('bitrix24_config') && !$USER->CanDoOperation('edit_php'))
			throw new Exception(GetMessage('CAL_REST_ACCESS_DENIED'));

		CCalendar::SetSettings(array(), true);
		return true;
	}

	/*
	 * Returns user's settings
	 *
	 * @return array of settings
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.user.settings.get",{});
	 */
	public static function UserSettingsGet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.user.settings.get";
		$settings = CCalendar::GetUserSettings($userId);
		return $settings;
	}

	/*
	 * Saves user's settings
	 *
	 * @param array $arParams - incomoning params:
	 * $arParams['settings'] - (required) array of user's settings
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.user.settings.set",
	 * {
	 * 		settings: {
	 * 			tabId: 'month',
	 * 			meetSection: '23',
	 * 			blink: true,
	 * 			showDeclined: false,
	 *			showMuted: true
	 * 		}
	 * });
	 */
	public static function UserSettingsSet($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.user.settings.set";

		if (!isset($arParams['settings']))
			throw new Exception(GetMessage('CAL_REST_PARAM_EXCEPTION', array('#PARAM_NAME#' => 'settings','#REST_METHOD#' => $methodName)));

		CCalendar::SetUserSettings($arParams['settings'], $userId);
		return true;
	}

	/*
	 * Clears user's settings
	 *
	 * @return true if everything ok
	 *
	 * @example (Javascript)
	 * BX24.callMethod("calendar.settings.clear",{});
	 */
	public static function UserSettingsClear($arParams = array(), $nav = null, $server = null)
	{
		$userId = CCalendar::GetCurUserId();
		$methodName = "calendar.user.settings.clear";
		CCalendar::SetUserSettings(false, $userId);
		return true;
	}
}
