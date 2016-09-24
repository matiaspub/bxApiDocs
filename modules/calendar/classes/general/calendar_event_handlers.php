<?
IncludeModuleLangFile(__FILE__);

/*
RegisterModuleDependences('intranet', 'OnPlannerInit', 'calendar', 'CCalendarEventHandlers', 'OnPlannerInit');
RegisterModuleDependences('intranet', 'OnPlannerAction', 'calendar', 'CCalendarEventHandlers', 'OnPlannerAction');
*/

class CCalendarEventHandlers
{
	public static function OnPlannerInit($params)
	{
		global $USER, $DB, $CACHE_MANAGER;

		if (!isset($params['USER_ID']) || intval($params['USER_ID']) <= 0)
		{
			if (!is_object($USER))
			{
				return false;
			}
			$userId = $USER->GetID();
		}
		else
		{
			$userId = $params['USER_ID'];
		}

		$CACHE_MANAGER->RegisterTag('calendar_user_'.$userId);

		$date_from = CCalendar::Date(time() - date('Z', time()) + CCalendar::GetCurrentOffsetUTC($userId), false);
		$ts_date_from = CCalendar::Timestamp($date_from) - CCalendar::GetCurrentOffsetUTC($userId);
		$date_from = CCalendar::Date($ts_date_from);
		$ts_date_to = $ts_date_from + CCalendar::GetDayLen() - 1;
		$date_to = $date_from;

		$arEvents = array();
		$eventTime = -1;

		$arNewEvents = CCalendarEvent::GetList(array(
			'arFilter' => array(
				"OWNER_ID" => $userId,
				"FROM_LIMIT" => $date_from,
				"TO_LIMIT" => $date_to
			),
			'arOrder' => Array('DATE_FROM_TS_UTC' => 'asc'),
			'parseRecursion' => true,
			'preciseLimits' => true,
			'userId' => $userId,
			'skipDeclined' => true,
			'fetchAttendees' => false,
			'fetchMeetings' => true
		));

		if (count($arNewEvents) > 0)
		{
			$now = time() + CTimeZone::GetOffset($userId);
			$today = ConvertTimeStamp($now, 'SHORT');

			$format = $DB->dateFormatToPHP(IsAmPmMode() ? 'H:MI T' : 'HH:MI');

			foreach ($arNewEvents as $arEvent)
			{
				if ($arEvent['IS_MEETING'] && $arEvent['MEETING_STATUS'] == 'N')
					continue;
				$fromTo = CCalendarEvent::GetEventFromToForUser($arEvent, $userId);

				$ts_from = $fromTo['TS_FROM'];
				//$ts_to = $fromTo['TS_TO'];

				$ts_from_utc = $arEvent['DATE_FROM_TS_UTC'];
				$ts_to_utc = $arEvent['DATE_TO_TS_UTC'];

				if ($arEvent['RRULE'])
				{
					$ts_from_utc = $fromTo['TS_FROM'] - CCalendar::GetCurrentOffsetUTC($userId);
					$ts_to_utc = $ts_from_utc + $arEvent['DT_LENGTH'];
				}

				if ($arEvent['RRULE'] && ($ts_to_utc <= $ts_date_from || $ts_from_utc >= $ts_date_to))
					continue;

				if(($eventTime < 0 || $eventTime > $ts_from) && $ts_from >= $now)
					$eventTime = $ts_from;

				if($params['FULL'])
				{
					$arEvents[] = array(
						'ID' => $arEvent['ID'],
						'OWNER_ID' => $userId,
						'CREATED_BY' => $arEvent['CREATED_BY'],
						'NAME' => $arEvent['NAME'],
						'DATE_FROM' => $fromTo['DATE_FROM'],
						'DATE_TO' => $fromTo['DATE_TO'],
						'TIME_FROM' => FormatDate($format, $fromTo['TS_FROM']),
						'TIME_TO' => FormatDate($format, $fromTo['TS_TO']),
						'IMPORTANCE' => $arEvent['IMPORTANCE'],
						'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
						'DATE_FROM_TODAY' => $today == ConvertTimeStamp($fromTo['TS_FROM'], 'SHORT'),
						'DATE_TO_TODAY' => $today == ConvertTimeStamp($fromTo['TS_TO'], 'SHORT'),
						'SORT' => $fromTo['TS_FROM']
					);
				}
			}
		}

		// Sort
		usort($arEvents, array('CCalendarEventHandlers', 'DateSort'));

		CJSCore::RegisterExt('calendar_planner_handler', array(
			'js' => '/bitrix/js/calendar/core_planner_handler.js',
			'css' => '/bitrix/js/calendar/core_planner_handler.css',
			'lang' => BX_ROOT.'/modules/calendar/lang/'.LANGUAGE_ID.'/core_planner_handler.php',
			'rel' => array('date', 'timer')
		));

		return array(
			'DATA' => array(
				'CALENDAR_ENABLED' => true,
				'EVENTS' => $arEvents,
				'EVENT_TIME' => $eventTime < 0 ? '' : (FormatDate(IsAmPmMode() ? "g:i a" : "H:i", $eventTime)),
			),
			'SCRIPTS' => array('calendar_planner_handler')
		);
	}

	public static function OnPlannerAction($action, $params)
	{
		switch($action)
		{
			case 'calendar_add':

				return self::plannerActionAdd(array(
					'NAME' => $_REQUEST['name'],
					'FROM' => $_REQUEST['from'],
					'TO' => $_REQUEST['to'],
					'ABSENCE' => $_REQUEST['absence']
				));

			break;

			case 'calendar_show':

				return self::plannerActionShow(array(
					'ID' => intval($_REQUEST['id']),
					'SITE_ID' => $params['SITE_ID']
				));

			break;
		}
	}

	protected static function getEvent($arParams)
	{
		global $USER;

		$userId = $USER->GetID();
		$date_from = CCalendar::Date(time() - date('Z', time()) + CCalendar::GetCurrentOffsetUTC(), false);
		$ts_date_from = CCalendar::Timestamp($date_from) - CCalendar::GetCurrentOffsetUTC();
		$date_from = CCalendar::Date($ts_date_from);
		$date_to = $date_from;
		$ts_date_to = $ts_date_from + CCalendar::GetDayLen() - 1;

		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $arParams['ID'],
					"FROM_LIMIT" => $date_from,
					"TO_LIMIT" => $date_to
				),
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'checkPermissions' => true,
				'skipDeclined' => true
			)
		);

		$arEvents = array();
		foreach ($res as $arEvent)
		{
			if ($arEvent['IS_MEETING'] && $arEvent['MEETING_STATUS'] == 'N')
				continue;
			$fromTo = CCalendarEvent::GetEventFromToForUser($arEvent, $userId);

			if ($arEvent['RRULE'])
			{
				$ts_from_utc = $fromTo['TS_FROM'] - CCalendar::GetCurrentOffsetUTC();
				$ts_to_utc = $ts_from_utc + $arEvent['DT_LENGTH'];
				if ($ts_to_utc <= $ts_date_from || $ts_from_utc >= $ts_date_to)
					continue;
			}

			$arEvents[] = $arEvent;
		}


		if (is_array($arEvents) && count($arEvents) > 0)
		{
			$arEvent = $arEvents[0];

			$arEvent['GUESTS'] = array();
			if ($arEvent['IS_MEETING'] && is_array($arEvent['~ATTENDEES']))
			{
				$arGuests = $arEvent['~ATTENDEES'];
				foreach ($arGuests as $guest)
				{
					$arEvent['GUESTS'][] = array(
						'id' => $guest['USER_ID'],
						'name' => CUser::FormatName(CSite::GetNameFormat(null, $arParams['SITE_ID']), $guest, true),
						'status' => $guest['STATUS'],
						'accessibility' => $guest['ACCESSIBILITY'],
						'bHost' => $guest['USER_ID'] == $arEvent['MEETING_HOST'],

					);

					if ($guest['USER_ID'] == $USER->GetID())
					{
						$arEvent['STATUS'] = $guest['STATUS'];
					}
				}
			}

			$set = CCalendar::GetSettings();
			$url = str_replace(
				'#user_id#', $arEvent['CREATED_BY'], $set['path_to_user_calendar']
			).'?EVENT_ID='.$arEvent['ID'];

			$fromTo = CCalendarEvent::GetEventFromToForUser($arEvent, $USER->GetID());

			return array(
				'ID' => $arEvent['ID'],
				'NAME' => $arEvent['NAME'],
				'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
				'DATE_FROM' => $fromTo['DATE_FROM'],
				'DATE_TO' => $fromTo['DATE_TO'],
				'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
				'IMPORTANCE' => $arEvent['IMPORTANCE'],
				'STATUS' => $arEvent['STATUS'],
				'IS_MEETING' => $arEvent['IS_MEETING'] ? 'Y' : 'N',
				'GUESTS' => $arEvent['GUESTS'],
				'UF_WEBDAV_CAL_EVENT' => $arEvent['UF_WEBDAV_CAL_EVENT'],
				'URL' => $url,
			);
		}
	}

	protected static function MakeDateTime($date, $time)
	{
		global $DB;

		if (!IsAmPmMode())
		{
			$date_start = $date.' '.$time.':00';
			$date_start = FormatDate(
				$DB->DateFormatToPhp(FORMAT_DATETIME),
				MakeTimeStamp(
					$date.' '.$time,
					FORMAT_DATE.' HH:MI'
				)
			);
		}
		else
		{
			$date_start = FormatDate(
				$DB->DateFormatToPhp(FORMAT_DATETIME),
				MakeTimeStamp(
					$date.' '.$time,
					FORMAT_DATE.' H:MI T'
				)
			);
		}

		return $date_start;
	}

	protected static function plannerActionAdd($arParams)
	{
		global $USER;

		$today = ConvertTimeStamp(time()+CTimeZone::GetOffset(), 'SHORT');

		$data = array(
			'CAL_TYPE' => 'user',
			'OWNER_ID' => $USER->GetID(),
			'NAME' => $arParams['NAME'],
			'DT_FROM' => self::MakeDateTime($today, $arParams['FROM']),
			'DT_TO' => self::MakeDateTime($today, $arParams['TO']),
		);

		if ($arParams['ABSENCE'] == 'Y')
		{
			$data['ACCESSIBILITY'] = 'absent';
		}

		CCalendar::SaveEvent(array(
			'arFields' => $data,
			'userId' => $USER->GetID(),
			'autoDetectSection' => true,
			'autoCreateSection' => true
		));
	}

	protected static function plannerActionShow($arParams)
	{
		global $DB, $USER;

		$res = false;

		if($arParams['ID'] > 0)
		{
			$event = self::getEvent(array(
				'ID' => $arParams['ID'],
				'SITE_ID' => $arParams['SITE_ID']
			));


			if ($event)
			{
				$today = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'SHORT');
				$now = time();

				$res = array(
					'ID' => $event['ID'],
					'NAME' => $event['NAME'],
					'DESCRIPTION' => CCalendarEvent::ParseText($event['DETAIL_TEXT'], $event['ID'], $event['UF_WEBDAV_CAL_EVENT']),
					'URL' => '/company/personal/user/'.$USER->GetID().'/calendar/?EVENT_ID=' .$event['ID'],
					'DATE_FROM' => MakeTimeStamp($event['DATE_FROM']),
					'DATE_TO' => MakeTimeStamp($event['DATE_TO']),
					'STATUS' => $event['STATUS'],
				);

				$res['DATE_FROM_TODAY'] = ConvertTimeStamp($res['DATE_FROM'],'SHORT') == $today;
				$res['DATE_TO_TODAY'] = ConvertTimeStamp($res['DATE_TO'], 'SHORT') == $today;

				if ($res['DATE_FROM_TODAY'])
				{
					if (IsAmPmMode())
					{
						$res['DATE_F'] = FormatDate("today g:i a", $res['DATE_FROM']);
						$res['DATE_T'] = FormatDate("g:i a", $res['DATE_TO']);
					}
					else
					{
						$res['DATE_F'] = FormatDate("today H:i", $res['DATE_FROM']);
						$res['DATE_T'] = FormatDate("H:i", $res['DATE_TO']);
					}

					if ($res['DATE_TO_TODAY'])
						$res['DATE_F'] .= ' - '.$res['DATE_T'];

					if ($res['DATE_FROM'] > $now)
					{

						$res['DATE_F_TO'] = GetMessage('TM_IN').' '.FormatDate('Hdiff', time()*2-($res['DATE_FROM'] - CTimeZone::GetOffset()));
					}
				}
				else if ($res['DATE_TO_TODAY'])
				{
					$res['DATE_F'] = FormatDate(str_replace(
						array('#today#', '#time#'),
						array('today', 'H:i'),
						GetMessage('TM_TILL')
					), $res['DATE_TO']);
				}
				else
				{
					$fmt = preg_replace('/:s$/', '', $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
					$res['DATE_F'] = FormatDate($fmt, $res['DATE_FROM']);
					$res['DATE_F_TO'] = FormatDate($fmt, $res['DATE_TO']);
				}

				if ($event['IS_MEETING'] == 'Y')
				{
					$arGuests = array('Y' => array(), 'N' => array(), 'Q' => array());
					foreach ($event['GUESTS'] as $key => $guest)
					{
						$guest['url'] = str_replace(
							array('#ID#', '#USER_ID#'),
							$guest['id'],
							COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $arParams['SITE_ID'])
						);

						if ($guest['bHost'])
						{
							$res['HOST'] = $guest;
						}
						else
						{
							$arGuests[$guest['status']][] = $guest;
						}
					}

					$res['GUESTS'] = array_merge($arGuests['Y'], $arGuests['N'], $arGuests['Q']);
				}

				if (strlen($res['DESCRIPTION']) > 150)
				{
					$res['DESCRIPTION'] = CUtil::closetags(substr($res['DESCRIPTION'], 0, 150)).'...';
				}

				$res = array('EVENT' => $res);
			}
		}
		else
		{
			$res = array('error' => 'event not found');
		}

		return $res;
	}

	private static function DateSort($a, $b)
	{
		if ($a['SORT'] == $b['SORT'])
			return 0;
		if ($a['SORT'] < $b['SORT'])
			return -1;
		return 1;
	}
}
?>