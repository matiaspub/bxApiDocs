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

		$CACHE_MANAGER->RegisterTag('calendar_user_'.$USER->GetID());

		$date_from = ConvertTimeStamp();
		$ts_date_from = MakeTimeStamp($date_from);
		$ts_date_to = $ts_date_from + 86399;
		$date_to = ConvertTimeStamp($ts_date_to, 'FULL');

		$arFilter = array(
			'arFilter' => array(
				"OWNER_ID" => $USER->GetID(),
				"FROM_LIMIT" => $date_from,
				"TO_LIMIT" => $date_to
			),
			'parseRecursion' => true,
			'userId' => $USER->GetID(),
			'skipDeclined' => true,
			'fetchAttendees' => false,
			'fetchMeetings' => true
		);

		$arEvents = array();
		$eventTime = -1;

		$arNewEvents = CCalendarEvent::GetList($arFilter);
		if (count($arNewEvents) > 0)
		{
			$now = time() + CTimeZone::GetOffset();
			$today = ConvertTimeStamp($now, 'SHORT');

			$format = $DB->dateFormatToPHP(IsAmPmMode() ? 'H:MI T' : 'HH:MI');

			foreach ($arNewEvents as $arEvent)
			{
				$ts_from = MakeTimeStamp($arEvent['DT_FROM']);

				if ($arEvent['RRULE'])
				{
					$ts_to = MakeTimeStamp($arEvent['DT_TO']);

					if ($ts_to < $ts_date_from || $ts_from > $ts_date_to)
						continue;
				}

				if(($eventTime < 0 || $eventTime > $ts_from) && $ts_from >= $now)
					$eventTime = $ts_from;

				if($params['FULL'])
				{
					$arEvents[] = array(
						'ID' => $arEvent['ID'],
						'OWNER_ID' => $USER->GetID(),
						'CREATED_BY' => $arEvent['CREATED_BY'],
						'NAME' => $arEvent['NAME'],
						'DATE_FROM' => $arEvent['DT_FROM'],
						'DATE_TO' => $arEvent['DT_TO'],
						'TIME_FROM' => FormatDate($format, MakeTimeStamp($arEvent['DT_FROM'])),
						'TIME_TO' => FormatDate($format, MakeTimeStamp($arEvent['DT_TO'])),
						'IMPORTANCE' => $arEvent['IMPORTANCE'],
						'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
						'DATE_FROM_TODAY' => $today == ConvertTimeStamp(MakeTimeStamp($arEvent['DT_FROM']), 'SHORT'),
						'DATE_TO_TODAY' => $today == ConvertTimeStamp(MakeTimeStamp($arEvent['DT_TO']), 'SHORT'),
					);
				}
			}
		}

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

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $arParams['ID'],
					"DELETED" => "N"
				),
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'checkPermissions' => true
			)
		);

		if (is_array($arEvents) && count($arEvents) > 0)
		{
			$arEvent = $arEvents[0];
			if ($arEvent['IS_MEETING'])
			{
				$arGuests = $arEvent['~ATTENDEES'];
				$arEvent['GUESTS'] = array();

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

			return array(
				'ID' => $arEvent['ID'],
				'NAME' => $arEvent['NAME'],
				'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
				'DATE_FROM' => $arEvent['DT_FROM'],
				'DATE_TO' => $arEvent['DT_TO'],
				'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
				'IMPORTANCE' => $arEvent['IMPORTANCE'],
				'STATUS' => $arEvent['STATUS'],
				'IS_MEETING' => $arEvent['IS_MEETING'] ? 'Y' : 'N',
				'GUESTS' => $arEvent['GUESTS'],
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
				$today = ConvertTimeStamp(time()+CTimeZone::GetOffset(),'SHORT');
				$now = time();

				$res = array(
					'ID' => $event['ID'],
					'NAME' => $event['NAME'],
					'DESCRIPTION' => $event['DETAIL_TEXT'],
					'URL' => '/company/personal/user/'.$USER->GetID().'/calendar/?EVENT_ID=' .$event['ID'],
					'DATE_FROM' => MakeTimeStamp($event['DATE_FROM']),
					'DATE_TO' => MakeTimeStamp($event['DATE_TO']),
					'STATUS' => $event['STATUS'],
				);

				$res['DATE_FROM_TODAY'] = ConvertTimeStamp(MakeTimeStamp($res['DATE_FROM']),'SHORT') == $today;
				$res['DATE_TO_TODAY'] = ConvertTimeStamp(MakeTimeStamp($res['DATE_TO']), 'SHORT') == $today;

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

				$res['DESCRIPTION'] = HTMLToTxt($res['DESCRIPTION']);
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
}
?>