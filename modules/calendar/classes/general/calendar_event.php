<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/calendar/classes/general/calendar.php");
class CCalendarEvent
{
	private static $Fields = array(), $lastAttendeesList = array();
	public static $eventUFDescriptions;
	public static $TextParser;

	private static function GetFields()
	{
		CTimeZone::Disable();
		global $DB;
		if (!count(self::$Fields))
			self::$Fields = array(
				"ID" => Array("FIELD_NAME" => "CE.ID", "FIELD_TYPE" => "int"),
				"ACTIVE" => Array("FIELD_NAME" => "CE.ACTIVE", "FIELD_TYPE" => "string"),
				"DELETED" => Array("FIELD_NAME" => "CE.DELETED", "FIELD_TYPE" => "string"),
				"CAL_TYPE" => Array("FIELD_NAME" => "CE.CAL_TYPE", "FIELD_TYPE" => "string"),
				"OWNER_ID" => Array("FIELD_NAME" => "CE.OWNER_ID", "FIELD_TYPE" => "int"),
				"CREATED_BY" => Array("FIELD_NAME" => "CE.CREATED_BY", "FIELD_TYPE" => "int"),
				"NAME" => Array("FIELD_NAME" => "CE.NAME", "FIELD_TYPE" => "string"),
				"DESCRIPTION" => Array("FIELD_NAME" => "CE.DESCRIPTION", "FIELD_TYPE" => "string"),
				"TIMESTAMP_X" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.TIMESTAMP_X").' as TIMESTAMP_X', "FIELD_TYPE" => "date"),
				"DATE_CREATE" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DATE_CREATE").' as DATE_CREATE', "FIELD_TYPE" => "date"),
				"DT_FROM" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DT_FROM").' as DT_FROM', "FIELD_TYPE" => "date"),
				"DT_TO" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DT_TO").' as DT_TO', "FIELD_TYPE" => "date"),
				"DT_SKIP_TIME" => Array("FIELD_NAME" => "CE.DT_SKIP_TIME", "FIELD_TYPE" => "string"),
				"DT_LENGTH" => Array("FIELD_NAME" => "CE.DT_LENGTH", "FIELD_TYPE" => "int"),
				"PRIVATE_EVENT" => Array("FIELD_NAME" => "CE.PRIVATE_EVENT", "FIELD_TYPE" => "string"),
				"ACCESSIBILITY" => Array("FIELD_NAME" => "CE.ACCESSIBILITY", "FIELD_TYPE" => "string"),
				"IMPORTANCE" => Array("FIELD_NAME" => "CE.IMPORTANCE", "FIELD_TYPE" => "string"),
				"IS_MEETING" => Array("FIELD_NAME" => "CE.IS_MEETING", "FIELD_TYPE" => "string"),
				"MEETING_HOST" => Array("FIELD_NAME" => "CE.MEETING_HOST", "FIELD_TYPE" => "int"),
				"MEETING" => Array("FIELD_NAME" => "CE.MEETING", "FIELD_TYPE" => "string"),
				"LOCATION" => Array("FIELD_NAME" => "CE.LOCATION", "FIELD_TYPE" => "string"),
				"REMIND" => Array("FIELD_NAME" => "CE.REMIND", "FIELD_TYPE" => "string"),
				"EXTERNAL_ID" => Array("FIELD_NAME" => "CE.EXTERNAL_ID", "FIELD_TYPE" => "string"),
				"COLOR" => Array("FIELD_NAME" => "CE.COLOR", "FIELD_TYPE" => "string"),
				"TEXT_COLOR" => Array("FIELD_NAME" => "CE.TEXT_COLOR", "FIELD_TYPE" => "string"),
				"RRULE" => Array("FIELD_NAME" => "CE.RRULE", "FIELD_TYPE" => "string"),
				"EXRULE" => Array("FIELD_NAME" => "CE.EXRULE", "FIELD_TYPE" => "string"),
				"RDATE" => Array("FIELD_NAME" => "CE.RDATE", "FIELD_TYPE" => "string"),
				"EXDATE" => Array("FIELD_NAME" => "CE.EXDATE", "FIELD_TYPE" => "string"),
				"ATTENDEES_CODES" => Array("FIELD_NAME" => "CE.ATTENDEES_CODES", "FIELD_TYPE" => "string"),
				"DAV_XML_ID" => Array("FIELD_NAME" => "CE.DAV_XML_ID", "FIELD_TYPE" => "string"), //
				"DAV_EXCH_LABEL" => Array("FIELD_NAME" => "CE.DAV_EXCH_LABEL", "FIELD_TYPE" => "string"), // Exchange sync label
				"CAL_DAV_LABEL" => Array("FIELD_NAME" => "CE.CAL_DAV_LABEL", "FIELD_TYPE" => "string"), // CalDAV sync label
				"VERSION" => Array("FIELD_NAME" => "CE.VERSION", "FIELD_TYPE" => "string") // Version used for outlook sync
			);
		CTimeZone::Enable();
		return self::$Fields;
	}

	public static function GetList($Params = array())
	{
		global $DB, $USER_FIELD_MANAGER;
		$getUF = $Params['getUserfields'] !== false;
		$checkPermissions = $Params['checkPermissions'] !== false;
		$bCache = CCalendar::CacheTime() > 0;
		$bCache = false;
		$Params['setDefaultLimit'] = $Params['setDefaultLimit'] === true;
		$userId = isset($Params['userId']) ? intVal($Params['userId']) : CCalendar::GetCurUserId();

		CTimeZone::Disable();
		if($bCache)
		{
			$cache = new CPHPCache;
			if ($checkPermissions)
				$cacheId = 'event_list_'.md5(serialize(array($Params, CCalendar::GetCurUserId())));
			else
				$cacheId = 'event_list_'.md5(serialize(array($Params)));

			$cacheId .= CCalendar::GetOffset();
			$cachePath = CCalendar::CachePath().'event_list';

			if ($cache->InitCache(CCalendar::CacheTime(), $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arResult = $res["arResult"];
				$arAttendees = $res["arAttendees"];
			}
		}

		if (!$bCache || !isset($arResult))
		{
			$arFilter = $Params['arFilter'];
			if ($getUF)
			{
				$obUserFieldsSql = new CUserTypeSQL();
				$obUserFieldsSql->SetEntity("CALENDAR_EVENT", "CE.ID");
				$obUserFieldsSql->SetSelect(array("UF_*"));
				$obUserFieldsSql->SetFilter($arFilter);
			}

			$fetchMeetings = $Params['fetchMeetings'];
			$Params['fetchAttendees'] = $Params['fetchAttendees'] !== false;
			$skipDeclined = $Params['skipDeclined'] === true;

			if ($Params['setDefaultLimit'] !== false) // Deprecated
			{
				if (!isset($arFilter["FROM_LIMIT"])) // default 3 month back
					$arFilter["FROM_LIMIT"] = CCalendar::Date(time() - 31 * 3 * 24 * 3600, false);

				if (!isset($arFilter["TO_LIMIT"])) // default one year into the future
					$arFilter["TO_LIMIT"] = CCalendar::Date(time() + 365 * 24 * 3600, false);
			}

			$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array('SORT' => 'asc');
			$arFields = self::GetFields();

			if (!isset($arFilter["DELETED"]))
				$arFilter["DELETED"] = "N";

			$ownerId = isset($arFilter['OWNER_ID']) ? $arFilter['OWNER_ID'] : CCalendar::GetOwnerId();

			if ($fetchMeetings)
			{
				// We fetch all events for user where it was attented
				$strInvIds = "";
				$arUserMeeting = CCalendarEvent::GetAttendeesList(array('userKey' => $ownerId), $strInvIds);
			}

			$arSqlSearch = array();

			if(is_array($arFilter))
			{
				$filter_keys = array_keys($arFilter);
				for($i = 0, $l = count($filter_keys); $i<$l; $i++)
				{
					$n = strtoupper($filter_keys[$i]);
					$val = $arFilter[$filter_keys[$i]];
					if(is_string($val) && strlen($val) <=0 || strval($val)=="NOT_REF")
						continue;

					if($n == 'FROM_LIMIT')
					{
						if (strtoupper($DB->type) == "MYSQL")
							$arSqlSearch[] = "CE.DT_TO>=FROM_UNIXTIME('".CCalendar::Timestamp($val, false)."')";
						elseif(strtoupper($DB->type) == "MSSQL")
							$arSqlSearch[] = "CE.DT_TO>=".$DB->CharToDateFunction($val, "SHORT");
						elseif(strtoupper($DB->type) == "ORACLE")
							$arSqlSearch[] = "CE.DT_TO>=TO_DATE('".$DB->FormatDate($val, CSite::GetDateFormat("SHORT", SITE_ID), "D.M.Y")." 00:00:00','dd.mm.yyyy hh24:mi:ss')";
					}
					elseif($n == 'TO_LIMIT')
					{
						if (strtoupper($DB->type) == "MYSQL")
							$arSqlSearch[] = "CE.DT_FROM<=FROM_UNIXTIME('".(CCalendar::Timestamp($val, false) + 86399)."')";
						elseif(strtoupper($DB->type) == "MSSQL")
							$arSqlSearch[] = "CE.DT_FROM<=dateadd(day, 1, ".$DB->CharToDateFunction($val, "SHORT").")";
						elseif(strtoupper($DB->type) == "ORACLE")
							$arSqlSearch[] = "CE.DT_FROM<=TO_DATE('".$DB->FormatDate($val, CSite::GetDateFormat("SHORT", SITE_ID), "D.M.Y")." 23:59:59','dd.mm.yyyy hh24:mi:ss')";
					}
					elseif($n == 'TIMESTAMP_X')
					{
					}
					elseif($n == 'OWNER_ID' && intVal($val) > 0)
					{
						$q = "CE.OWNER_ID=".intVal($val);

						if ($fetchMeetings && $strInvIds != "")
						{
							if ($q != "")
								$q .= ' OR ';
							$q .= 'CE.ID in ('.$strInvIds.')';
						}

						$arSqlSearch[] = $q;
					}
					elseif($n == 'SECTION')
					{
						if (!is_array($val))
							$val = array($val);

						$q = "";
						if (is_array($val))
						{
							$sval = '';
							foreach($val as $sectid)
								if (intVal($sectid) > 0)
									$sval .= intVal($sectid).',';
							$sval = trim($sval, ' ,');
							if ($sval != '')
								$q = 'CES.SECT_ID in ('.$sval.')';
						}

						if ($fetchMeetings && $strInvIds != "")
						{
							if ($q != "")
								$q .= ' OR ';
							$q .= 'CE.ID in ('.$strInvIds.')';
						}

						if ($q != "")
							$arSqlSearch[] = $q;
					}
					elseif($n == 'DAV_XML_ID' && is_array($val))
					{
						array_walk($val, array($DB, 'ForSql'));
						$arSqlSearch[] = 'CE.DAV_XML_ID IN (\''.implode('\',\'', $val).'\')';
					}
					elseif(isset($arFields[$n]))
					{
						$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val, 'N');
					}
				}
			}

			if ($getUF)
			{
				$r = $obUserFieldsSql->GetFilter();
				if (strlen($r) > 0)
					$arSqlSearch[] = "(".$r.")";
			}

			$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
			$strOrderBy = '';
			foreach($arOrder as $by=>$order)
				if(isset($arFields[strtoupper($by)]))
					$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type) == "ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

			if(strlen($strOrderBy) > 0)
				$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

			$selectList = "";
			foreach($arFields as $field)
				$selectList .= $field['FIELD_NAME'].", ";

			$strSql = "
				SELECT ".
					$selectList.
					"CES.SECT_ID, CES.REL
					".($getUF ? $obUserFieldsSql->GetSelect() : '')."
				FROM
					b_calendar_event CE
				INNER JOIN b_calendar_event_sect CES ON (CE.ID=CES.EVENT_ID)
				".($getUF ? $obUserFieldsSql->GetJoin("CE.ID") : '')."
				WHERE
					$strSqlSearch
				$strOrderBy";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if ($getUF)
				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT"));

			$arResult = Array();

			$arMeetingIds = array();
			$arEvents = array();
			$bIntranet = CCalendar::IsIntranetEnabled();

			while($event = $res->Fetch())
			{
				$event['IS_MEETING'] = intVal($event['IS_MEETING']) > 0;
				$arEvents[] = $event;
				if ($bIntranet && $event['IS_MEETING'])
					$arMeetingIds[] = $event['ID'];
			}

			if ($Params['fetchAttendees'] && count($arMeetingIds) > 0)
				$arAttendees = self::GetAttendees($arMeetingIds);
			else
				$arAttendees = array();

			foreach($arEvents as $event)
			{
				$event["ACCESSIBILITY"] = trim($event["ACCESSIBILITY"]);
				if ($bIntranet && $event['IS_MEETING'])
				{
					if (isset($event['MEETING']) && $event['MEETING'] != "")
					{
						$event['MEETING'] = unserialize($event['MEETING']);
						if (!is_array($event['MEETING']))
							$event['MEETING'] = array();
					}

					if ($arUserMeeting[$event['ID']])
					{
						$status = $arUserMeeting[$event['ID']]['STATUS'];
						if ($skipDeclined && $status == "N")
							continue;

						if ($status == "Y" || $userId == $ownerId)
						{
							$event['USER_MEETING'] = array(
								'ATTENDEE_ID' => $ownerId,
								'ACCESSIBILITY' => $arUserMeeting[$event['ID']]['ACCESSIBILITY'],
								'COLOR' => $arUserMeeting[$event['ID']]['COLOR'],
								'TEXT_COLOR' => $arUserMeeting[$event['ID']]['TEXT_COLOR'],
								'DESCRIPTION' => $arUserMeeting[$event['ID']]['DESCRIPTION'],
								'STATUS' => $status,
								'REMIND' => array()
							);

							if (isset($arUserMeeting[$event['ID']]['REMIND']) && $arUserMeeting[$event['ID']]['REMIND'] != "")
							{
								$event['USER_MEETING']['REMIND'] = unserialize($arUserMeeting[$event['ID']]['REMIND']);
								if (!is_array($event['USER_MEETING']['REMIND']))
									$event['USER_MEETING']['REMIND'] = array();
							}
						}
						else if (is_array($arFilter['SECTION']) && !in_array($event['SECT_ID'], $arFilter['SECTION']))
						{
							continue;
						}
					}
				}

				if (isset($event['REMIND']) && $event['REMIND'] != "")
				{
					$event['REMIND'] = unserialize($event['REMIND']);
					if (!is_array($event['REMIND']))
						$event['REMIND'] = array();
				}

				if ($bIntranet && $event['IS_MEETING'] && isset($arAttendees[$event['ID']]) && count($arAttendees[$event['ID']]) > 0)
				{
					$event['~ATTENDEES'] = $arAttendees[$event['ID']];
				}
				$checkPermissionsForEvent = $userId != $event['CREATED_BY']; // It's creator

				// It's event in user's calendar
				if ($checkPermissionsForEvent && $event['CAL_TYPE'] == 'user' && $userId == $event['OWNER_ID'])
					$checkPermissionsForEvent = false;
				if ($checkPermissionsForEvent && $event['IS_MEETING'] && $event['USER_MEETING'] && $event['USER_MEETING']['ATTENDEE_ID'] == $userId)
					$checkPermissionsForEvent = false;

				if ($checkPermissionsForEvent && $event['IS_MEETING'] && is_array($event['~ATTENDEES']))
				{
					foreach($event['~ATTENDEES'] as $att)
					{
						if ($att['USER_ID'] == $userId)
						{
							$checkPermissionsForEvent = false;
							break;
						}
					}
				}

				if ($checkPermissions && $checkPermissionsForEvent)
					$event = self::ApplyAccessRestrictions($event, $userId);

				if ($event === false)
					continue;

				$event = self::PreHandleEvent($event);

				if ($Params['parseRecursion'] && self::CheckRecurcion($event))
				{
					self::ParseRecursion($arResult, $event, array(
						'fromLimit' => $arFilter["FROM_LIMIT"],
						'toLimit' => $arFilter["TO_LIMIT"],
						'instanceCount' => isset($Params['maxInstanceCount']) ? $Params['maxInstanceCount'] : false,
						'preciseLimits' => isset($Params['preciseLimits']) ? $Params['preciseLimits'] : false
					));
				}
				else
				{
					self::HandleEvent($arResult, $event);
				}
			}

			if ($bCache)
			{
				$cache->StartDataCache(CCalendar::CacheTime(), $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arResult" => $arResult,
					"arAttendees" => $arAttendees
				));
			}
		}

		CTimeZone::Enable();

		//self::$lastAttendeesList = $arAttendees;
		if (!is_array(self::$lastAttendeesList))
		{
			self::$lastAttendeesList = $arAttendees;
		}
		elseif(is_array($arAttendees))
		{
			foreach($arAttendees as $eventId => $att)
				self::$lastAttendeesList[$eventId] = $att;
		}

		return $arResult;
	}

	public static function GetAttendeesList($Params = array(), &$strInvIds)
	{
		global $DB;
		$userKey = intVal($Params['userKey']);

		$bCache = CCalendar::CacheTime() > 0;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = 'attendees_list_'.$userKey;
			$cachePath = CCalendar::CachePath().'attendees_list';

			if ($cache->InitCache(CCalendar::CacheTime(), $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$strInvIds = $res["strInvIds"];
				$arUserMeeting = $res["arUserMeeting"];
			}
		}

		if (!$bCache || !isset($arUserMeeting))
		{
			$strSql = "SELECT * FROM b_calendar_attendees WHERE USER_KEY='".$userKey."'";
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			$strInvIds = "";
			$arUserMeeting = array();
			while($ev = $res->Fetch())
			{
				$ev["STATUS"] = trim($ev["STATUS"]);
				$ev["DESCRIPTION"] = trim($ev["DESCRIPTION"]);
				$ev["COLOR"] = trim($ev["COLOR"]);
				$ev["TEXT_COLOR"] = trim($ev["TEXT_COLOR"]);
				$ev["ACCESSIBILITY"] = trim($ev["ACCESSIBILITY"]);
				$arUserMeeting[$ev['EVENT_ID']] = $ev;
				$strInvIds .= ','.intVal($ev['EVENT_ID']);
			}
			$strInvIds = trim($strInvIds, " ,");

			if ($bCache)
			{
				$cache->StartDataCache(CCalendar::CacheTime(), $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"strInvIds" => $strInvIds,
					"arUserMeeting" => $arUserMeeting
				));
			}
		}

		return $arUserMeeting;
	}

	public static function GetById($ID, $checkPermissions = true)
	{
		if ($ID > 0)
		{
			$Event = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"ID" => $ID,
						"DELETED" => "N"
					),
					'parseRecursion' => false,
					'fetchAttendees' => $checkPermissions,
					'checkPermissions' => $checkPermissions,
					'setDefaultLimit' => false
				)
			);
			if ($Event && is_array($Event[0]))
				return $Event[0];
		}
		return false;
	}

	public static function GetLastAttendees()
	{
		$res = array();
		if (isset(self::$lastAttendeesList) && is_array(self::$lastAttendeesList))
		{
			foreach(self::$lastAttendeesList as $id => $attendees)
			{
				$res[$id] = array();
				foreach($attendees as $user)
				{
					$name = trim($user["USER_NAME"]);

					$type = (intVal($user["USER_ID"]) > 0) ? "int" : "ext";
					if ($type == "int")
					{
						$user["ID"] = intVal($user["USER_ID"]);
						$name = CCalendar::GetUserName($user);
					}

					$res[$id][] = array(
						"type" => $type,
						"id" => intVal($user["USER_ID"]),
						"name" => $name,
						"email" => trim($user["USER_EMAIL"]), // For ext only
						"photo" => $user["PERSONAL_PHOTO"],
						"status" => trim($user["STATUS"]),
						"desc" => trim($user["DESCRIPTION"]),
						"color" => trim($user["COLOR"]),
						"text_color" => trim($user["TEXT_COLOR"]),
						"accessibility" => trim($user["ACCESSIBILITY"])
					);
				}
			}
		}
		return $res;
	}

	public static function SetLastAttendees($attendees)
	{
		self::$lastAttendeesList = $attendees;
	}

	public static function GetAttendees($arEventIds = array())
	{
		global $DB;

		$arAttendees = array();
		if (!is_array($arEventIds))
			$arEventIds = array($arEventIds);
		$strMeetIds = "";
		foreach($arEventIds as $id)
			if (intVal($id) > 0)
				$strMeetIds .= ','.intVal($id);
		$strMeetIds = trim($strMeetIds, ', ');

		if ($strMeetIds != '')
		{
			$strSql = "
			SELECT
				CA.*,
				U.LOGIN, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.EMAIL, U.PERSONAL_PHOTO, U.WORK_POSITION,
				BUF.UF_DEPARTMENT
			FROM
				b_calendar_attendees CA
				LEFT JOIN b_user U ON (U.ID=CA.USER_ID)
				LEFT JOIN b_uts_user BUF ON (BUF.VALUE_ID = CA.USER_ID)
			WHERE
				U.ACTIVE = 'Y' AND
				CA.EVENT_ID in (".$strMeetIds.")";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while($attendee = $res->Fetch())
			{
				if (!isset($arAttendees[$attendee['EVENT_ID']]))
					$arAttendees[$attendee['EVENT_ID']] = array();

				$attendee["STATUS"] = trim($attendee["STATUS"]);
				$attendee["DESCRIPTION"] = trim($attendee["DESCRIPTION"]);
				$attendee["COLOR"] = trim($attendee["COLOR"]);
				$attendee["TEXT_COLOR"] = trim($attendee["TEXT_COLOR"]);
				$attendee["ACCESSIBILITY"] = trim($attendee["ACCESSIBILITY"]);

				if (empty($attendee["ACCESSIBILITY"]))
					$attendee["ACCESSIBILITY"] = 'busy';
				CCalendar::SetUserDepartment($attendee["USER_ID"], (empty($attendee['UF_DEPARTMENT']) ? array() : unserialize($attendee['UF_DEPARTMENT'])));
				$attendee['DISPLAY_NAME'] = CCalendar::GetUserName($attendee);
				$attendee['URL'] = CCalendar::GetUserUrl($attendee["USER_ID"]);
				$attendee['AVATAR'] = CCalendar::GetUserAvatarSrc($attendee);
				$arAttendees[$attendee['EVENT_ID']][] = $attendee;
			}
		}

		return $arAttendees;
	}

	public static function CheckRecurcion($event)
	{
		return $event['RRULE'] != '' || $event['RDATE'] != '';
	}

	public static function ParseRecursion(&$res, $event, $Params = array())
	{
		$event['DT_LENGTH'] = intVal($event['DT_LENGTH']);// length in seconds
		$length = $event['DT_LENGTH'];

		$rrule = self::ParseRRULE($event['RRULE']);

		$h24 = CCalendar::GetDayLen();
		$instanceCount = ($Params['instanceCount'] && $Params['instanceCount'] > 0) ? $Params['instanceCount'] : false;
		$preciseLimits = $Params['preciseLimits'];

		if ($length < 0) // Protection from infinite recursion
			$length = $h24;

		// Time boundaries
		if (isset($Params['fromLimitTs']))
			$limitFromTS = intVal($Params['fromLimitTs']);
		else
			$limitFromTS = CCalendar::Timestamp($Params['fromLimit']);

		if (isset($Params['toLimitTs']))
			$limitToTS = intVal($Params['toLimitTs']);
		else
			$limitToTS = CCalendar::Timestamp($Params['toLimit']);

		$limitFromTSReal = $limitFromTS;
		$evFromTS = isset($event['DT_FROM_TS']) ? $event['DT_FROM_TS'] : CCalendar::Timestamp($event['DT_FROM']);
		$evToTS = isset($event['DT_TO_TS']) ? $event['DT_TO_TS'] : CCalendar::Timestamp($event['DT_TO']);

		if ($limitFromTS < $evFromTS)
			$limitFromTS = $evFromTS;
		if ($limitToTS > $evToTS)
			$limitToTS = $evToTS;

		$fromTS = $evFromTS;

		$offset = CCalendar::GetOffset();
		if ($event['DT_SKIP_TIME'] == 'N' && $offset != 0)
			$fromTS += $offset;

		$event['~DT_FROM'] = $event['DT_FROM'];
		$event['~DT_FROM_TS'] = $event['DT_FROM_TS'];
		if ($event['DT_SKIP_TIME'] == 'Y') // All days events
		{
			if ($event['DT_LENGTH'] == $h24)
			{
				$event['~DT_TO'] = $event['DT_FROM'];
				$event['~DT_TO_TS'] = $event['DT_FROM_TS'];
			}
			else
			{
				$event['~DT_TO_TS'] = mktime(date("H", $evFromTS), date("i", $evFromTS), date("s", $evFromTS) + $event['DT_LENGTH'] - $h24, date("m", $evFromTS), date("d", $evFromTS), date("Y", $evFromTS));
				$event['~DT_TO'] = CCalendar::Date($event['~DT_TO_TS']);
			}
		}
		else // Events with time
		{
			$event['~DT_TO_TS'] = mktime(date("H", $evFromTS), date("i", $evFromTS), date("s", $evFromTS) + $event['DT_LENGTH'], date("m", $evFromTS), date("d", $evFromTS), date("Y", $evFromTS));

			$event['~DT_TO'] = CCalendar::Date($event['~DT_TO_TS']);
		}

		$hour = date("H", $fromTS);
		$min = date("i", $fromTS);
		$sec = date("s", $fromTS);

		$orig_d = date("d", $fromTS);
		$orig_m = date("m", $fromTS);
		$orig_y = date("Y", $fromTS);

		$count = 0;
		$realCount = 0;
		$dispCount = 0;

		while(true)
		{
			$d = date("d", $fromTS);
			$m = date("m", $fromTS);
			$y = date("Y", $fromTS);
			$toTS = mktime($hour, $min, $sec + $length, $m, $d, $y);

			if (
				(!$fromTS || $fromTS < $evFromTS - CCalendar::GetDayLen()) || // Emergensy exit (mantis: 56981)
				($rrule['COUNT'] > 0 && $count >= $rrule['COUNT']) ||
				(!$rrule['COUNT'] && $fromTS >= $limitToTS) ||
				($instanceCount && $dispCount >= $instanceCount)
			)
			{
				break;
			}

			if ($rrule['FREQ'] == 'WEEKLY')
			{
				$weekDay = CCalendar::WeekDayByInd(date("w", $fromTS));

				if ($rrule['BYDAY'][$weekDay])
				{
					if (($preciseLimits && $fromTS > $limitFromTSReal) || (!$preciseLimits && $fromTS > $limitFromTS - $h24))
					{
						if ($event['DT_SKIP_TIME'] == 'N' && $offset != 0)
						{
							$fromTS -= $offset;
							$toTS -= $offset;
						}

						self::HandleREvent($res, $event, array(
							'DT_FROM_TS' => $fromTS,
							'DT_TO_TS' => $toTS,
							'RINDEX' => $count,
							'RRULE' => $rrule
						));
						$dispCount++;
					}
					$realCount++;
				}

				if (isset($weekDay) && $weekDay == 'SU')
					$delta = ($rrule['INTERVAL'] - 1) * 7 + 1;
				else
					$delta = 1;

				$fromTS = mktime($hour, $min, $sec, $m, $d + $delta, $y);
			}
			else // HOURLY, DAILY, MONTHLY, YEARLY
			{
				if (($preciseLimits && $fromTS > $limitFromTSReal) || (!$preciseLimits && $fromTS > $limitFromTS - $h24))
				{
					if ($event['DT_SKIP_TIME'] == 'N' && $offset != 0)
					{
						$fromTS -= $offset;
						$toTS -= $offset;
					}

					self::HandleREvent($res, $event, array(
						'DT_FROM_TS' => $fromTS,
						'DT_TO_TS' => $toTS,
						'RINDEX' => $count,
						'RRULE' => $rrule
					));

					$dispCount++;
				}
				$realCount++;

				switch ($rrule['FREQ'])
				{
//					case 'HOURLY':
//						$fromTS = mktime($hour + $rrule['INTERVAL'], $min, $sec, $m, $d, $y);
//						break;
					case 'DAILY':
						$fromTS = mktime($hour, $min, $sec, $m, $d + $rrule['INTERVAL'], $y);
						break;
					case 'MONTHLY':
						$durOffset = $realCount * $rrule['INTERVAL'];

						$day = $orig_d;
						$month = $orig_m + $durOffset;
						$year = $orig_y;

						if ($month > 12)
						{
							$delta_y = floor($month / 12);
							$delta_m = $month - $delta_y * 12;

							$month = $delta_m;
							$year = $orig_y + $delta_y;
						}

						// 1. Check only for 29-31 dates. 2.We are out of range in this month
						if ($orig_d > 28 && $orig_d > date("t", mktime($hour, $min, $sec, $month, 1, $year)))
						{
							$month++;
							$day = 1;
						}

						$fromTS = mktime($hour, $min, $sec, $month, $day, $year);
						break;
					case 'YEARLY':
						$fromTS = mktime($hour, $min, $sec, $orig_m, $orig_d, $y + $rrule['INTERVAL']);
						break;
				}
			}
			$count++;
		}
	}

	private static function PreHandleEvent($item)
	{
		if ($item['IS_MEETING'] && $item['MEETING'] != "" && !is_array($item['MEETING']))
		{
			$item['MEETING'] = unserialize($item['MEETING']);
			if (!is_array($item['MEETING']))
				$item['MEETING'] = array();
		}


		if ($item['IS_MEETING'])
		{
			if ($item['ATTENDEES_CODES'] != '')
			{
				$item['ATTENDEES_CODES'] = explode(',', $item['ATTENDEES_CODES']);
			}
		}

		if (!isset($item['~IS_MEETING']))
			$item['~IS_MEETING'] = $item['IS_MEETING'];

		$item['DT_FROM_TS'] = CCalendar::Timestamp($item['DT_FROM']);
		$item['DT_TO_TS'] = CCalendar::Timestamp($item['DT_TO']);

		if ($item['DT_SKIP_TIME'] != 'Y' && $item['DT_SKIP_TIME'] != 'N')
			$item['DT_SKIP_TIME'] = (date('H:i', $item['DT_FROM_TS']) == '00:00' && date('H:i', $item['DT_TO_TS']) == '00:00') ? 'Y' : 'N';

		$item['ACCESSIBILITY'] = trim($item['ACCESSIBILITY']);
		$item['IMPORTANCE'] = trim($item['IMPORTANCE']);
		if ($item['IMPORTANCE'] == '')
			$item['IMPORTANCE'] = 'normal';
		$item['PRIVATE_EVENT'] = trim($item['PRIVATE_EVENT']);

		$item['~DESCRIPTION'] = self::ParseText($item['DESCRIPTION'], $item['ID'], $item['UF_WEBDAV_CAL_EVENT']);

		if (isset($item['UF_CRM_CAL_EVENT']) && is_array($item['UF_CRM_CAL_EVENT']) && count($item['UF_CRM_CAL_EVENT']) == 0)
			$item['UF_CRM_CAL_EVENT'] = '';

		return $item;
	}

	private static function HandleREvent(&$res, $item = array(), $Params)
	{
		// *** $item['~DT_FROM_TS'] & $item['~DT_TO_TS'] - are real FROM AND TO timestamps of the first original event
		$item['RRULE'] = $Params['RRULE'];
		$item['DT_FROM_TS'] = $Params['DT_FROM_TS'];
		$item['DT_TO_TS'] = $Params['DT_TO_TS'];

		$item['~DT_FROM_TS'] += date("Z", $item['~DT_FROM_TS']) - date("Z");
		$item['~DT_TO_TS'] += date("Z", $item['~DT_TO_TS']) - date("Z");

		$offset = CCalendar::GetOffset();
		if ($item['DT_SKIP_TIME'] == 'N' && $offset != 0)
		{
			$item['~DT_FROM_TS'] += $offset;
			$item['~DT_TO_TS'] += $offset;

			$item['~DT_FROM'] = CCalendar::Date($item['~DT_FROM_TS']);
			$item['~DT_TO'] = CCalendar::Date($item['~DT_TO_TS']);
		}

		if ($item['DT_SKIP_TIME'] == 'Y')
			$item['DT_TO_TS'] -= CCalendar::GetDayLen();

		if ($item['DT_SKIP_TIME'] == 'Y' || $offset == 0)
		{
			$item['DT_FROM'] = CCalendar::Date($item['DT_FROM_TS']);
			$item['DT_TO'] = CCalendar::Date($item['DT_TO_TS']);
		}

		$item['RINDEX'] = $Params['RINDEX'];

		self::HandleEvent($res, $item);
	}

	private static function HandleEvent(&$res, $item = array())
	{
		$offset = CCalendar::GetOffset();

		if ($item['RRULE'] || $item['DT_SKIP_TIME'] == 'Y')
		{
			$item['DT_FROM_TS'] += date("Z", $item['DT_FROM_TS']) - date("Z");
			$item['DT_TO_TS'] += date("Z", $item['DT_TO_TS']) - date("Z");
		}

		if ($item['DT_SKIP_TIME'] == 'N' && $offset != 0)
		{
			$item['DT_FROM_TS'] += $offset;
			$item['DT_TO_TS'] += $offset;

			$item['DT_FROM'] = CCalendar::Date($item['DT_FROM_TS']);
			$item['DT_TO'] = CCalendar::Date($item['DT_TO_TS']);
		}

		$res[] = $item;
	}

	public static function ParseRRULE($rule = '')
	{
		$res = array();
		$arRule = explode(";", $rule);
		foreach($arRule as $par)
		{
			$arPar = explode("=", $par);
			if ($arPar[0])
			{
				switch($arPar[0])
				{
					case 'FREQ':
						if (in_array($arPar[1], array('HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY')))
							$res['FREQ'] = $arPar[1];
						break;
					case 'COUNT':
					case 'INTERVAL':
						if (intVal($arPar[1]) > 0)
							$res[$arPar[0]] = intVal($arPar[1]);
						break;
					case 'UNTIL':
						$res['UNTIL'] = $arPar[1];
						break;
					case 'BYDAY':
						$res[$arPar[0]] = array();
						foreach(explode(',', $arPar[1]) as $day)
						{
							$matches = array();
							if (preg_match('/((\-|\+)?\d+)?(MO|TU|WE|TH|FR|SA|SU)/', $day, $matches))
								$res[$arPar[0]][$matches[3]] = $matches[1] == '' ? $matches[3] : $matches[1];
						}
						if (count($res[$arPar[0]]) == 0)
							unset($res[$arPar[0]]);
						break;
					case 'BYMONTHDAY':
						$res[$arPar[0]] = array();
						foreach(explode(',', $arPar[1]) as $day)
							if (abs($day) > 0 && abs($day) <= 31)
								$res[$arPar[0]][intVal($day)] = intVal($day);
						if (count($res[$arPar[0]]) == 0)
							unset($res[$arPar[0]]);
						break;
					case 'BYYEARDAY':
					case 'BYSETPOS':
						$res[$arPar[0]] = array();
						foreach(explode(',', $arPar[1]) as $day)
							if (abs($day) > 0 && abs($day) <= 366)
								$res[$arPar[0]][intVal($day)] = intVal($day);
						if (count($res[$arPar[0]]) == 0)
							unset($res[$arPar[0]]);
						break;
					case 'BYWEEKNO':
						$res[$arPar[0]] = array();
						foreach(explode(',', $arPar[1]) as $day)
							if (abs($day) > 0 && abs($day) <= 53)
								$res[$arPar[0]][intVal($day)] = intVal($day);
						if (count($res[$arPar[0]]) == 0)
							unset($res[$arPar[0]]);
						break;
					case 'BYMONTH':
						$res[$arPar[0]] = array();
						foreach(explode(',', $arPar[1]) as $m)
							if ($m > 0 && $m <= 12)
								$res[$arPar[0]][intVal($m)] = intVal($m);
						if (count($res[$arPar[0]]) == 0)
							unset($res[$arPar[0]]);
						break;
				}
			}
		}

		if ($res['FREQ'] == 'WEEKLY' && (!isset($res['BYDAY']) || !is_array($res['BYDAY']) || count($res['BYDAY']) == 0))
			$res['BYDAY'] = array('MO' => 'MO');

		if ($res['FREQ'] != 'WEEKLY' && isset($res['BYDAY']))
			unset($res['BYDAY']);

		$res['INTERVAL'] = intVal($res['INTERVAL']);
		if ($res['INTERVAL'] <= 1)
			$res['INTERVAL'] = 1;

		return $res;
	}

	private static function PackRRule($RRule = array())
	{
		$strRes = "";
		if (is_array($RRule))
		{
			foreach($RRule as $key => $val)
				$strRes .= $key.'='.$val.';';
		}
		$strRes = trim($strRes, ', ');
		return $strRes;
	}

	public static function CheckRRULE($RRule = array())
	{
		if ($RRule['FREQ'] != 'WEEKLY' && isset($RRule['BYDAY']))
			unset($RRule['BYDAY']);
		return $RRule;
	}

	public static function ParseText($text = "", $eventId = 0, $arUFWDValue = array())
	{
		if ($text != "")
		{
			if (!is_object(self::$TextParser))
			{
				self::$TextParser = new CTextParser();
				//self::$TextParser->pathToUser = CCalendar::pathToUser;


				self::$TextParser->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "N", "ALIGN" => "Y", "USER" => "Y", "USERFIELDS" => self::__GetUFForParseText($eventId, $arUFWDValue));
			}

			self::$TextParser->allow["USERFIELDS"] = self::__GetUFForParseText($eventId, $arUFWDValue);
			$text = self::$TextParser->convertText($text);
		}
		return $text;
	}

	public static function __GetUFForParseText($eventId = 0, $arUFWDValue = array())
	{
		if (!isset(self::$eventUFDescriptions))
		{
			global $USER_FIELD_MANAGER;
			$USER_FIELDS = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $eventId, LANGUAGE_ID);
			$USER_FIELDS = array(
				'UF_WEBDAV_CAL_EVENT' => $USER_FIELDS['UF_WEBDAV_CAL_EVENT']
			);
			self::$eventUFDescriptions = $USER_FIELDS;
		}
		else
		{
			$USER_FIELDS = self::$eventUFDescriptions;
		}

		$USER_FIELDS['UF_WEBDAV_CAL_EVENT']['VALUE'] = $arUFWDValue;
		$USER_FIELDS['UF_WEBDAV_CAL_EVENT']['ENTITY_VALUE_ID'] = $eventId;

		return $USER_FIELDS;
	}

	public static function CheckFields(&$arFields)
	{
		// Check dates
//		$arFields['DT_FROM'] = CCalendar::CutZeroTime($arFields['DT_FROM']);
//		$arFields['DT_TO'] = CCalendar::CutZeroTime($arFields['DT_TO']);
		$fromTs = CCalendar::Timestamp($arFields['DT_FROM']);
		$toTs = CCalendar::Timestamp($arFields['DT_TO']);

		if (!isset($arFields['DT_FROM_TS']))
			$arFields['DT_FROM_TS'] = $fromTs;
		if (!isset($arFields['DT_TO_TS']))
			$arFields['DT_TO_TS'] = $toTs;

		$h24 = 60 * 60 * 24; // 24 hours

		// Skip time
		if (isset($arFields['SKIP_TIME']))
		{
			$arFields['DT_SKIP_TIME'] = $arFields['SKIP_TIME'] ? 'Y' : 'N';
			unset($arFields['SKIP_TIME']);
		}
		elseif(isset($arFields['DT_SKIP_TIME']) && $arFields['DT_SKIP_TIME'] != 'Y' && $arFields['DT_SKIP_TIME'] != 'N')
		{
			unset($arFields['DT_SKIP_TIME']);
		}

		// Event length in seconds
		if (!isset($arFields['DT_LENGTH']))
		{
			//if($fromTs == $toTs && date('H:i', $fromTs) == '00:00') // One day
			if($fromTs == $toTs && date('H:i', $fromTs) == '00:00' && $arFields['DT_SKIP_TIME'] == 'Y') // One day
			{
				$arFields['DT_LENGTH'] = $h24;
			}
			else
			{
				$arFields['DT_LENGTH'] = intVal($toTs - $fromTs);

				if ($arFields['DT_SKIP_TIME'] == "Y") // We have dates without times
					$arFields['DT_LENGTH'] += $h24;
			}
		}

		if (!$arFields['VERSION'])
			$arFields['VERSION'] = 1;

		// Accessibility
		$arFields['ACCESSIBILITY'] = trim(strtolower($arFields['ACCESSIBILITY']));
		if (!in_array($arFields['ACCESSIBILITY'], array('busy', 'quest', 'free', 'absent')))
			$arFields['ACCESSIBILITY'] = 'busy';

		// Importance
		$arFields['IMPORTANCE'] = trim(strtolower($arFields['IMPORTANCE']));
		if (!in_array($arFields['IMPORTANCE'], array('high', 'normal', 'low')))
			$arFields['IMPORTANCE'] = 'normal';

		// Color
		$arFields['COLOR'] = CCalendar::Color($arFields['COLOR'], false);

		// Section
		if (!is_array($arFields['SECTIONS']) && intVal($arFields['SECTIONS']) > 0)
			$arFields['SECTIONS'] = array(intVal($arFields['SECTIONS']));

		// Check rrules
		if (is_array($arFields['RRULE']) && isset($arFields['RRULE']['FREQ']) && in_array($arFields['RRULE']['FREQ'], array('HOURLY','DAILY','MONTHLY','YEARLY','WEEKLY')))
		{
			// Interval
			if (isset($arFields['RRULE']['INTERVAL']) && intVal($arFields['RRULE']['INTERVAL']) > 1)
				$arFields['RRULE']['INTERVAL'] = intVal($arFields['RRULE']['INTERVAL']);

			// Until date
			if ($arFields['RRULE']['UNTIL'])
			{
				if (!preg_match('/[^\d]/', $arFields['RRULE']['UNTIL']))
				{
					$periodTs = $arFields['RRULE']['UNTIL'];
					if ($periodTs > CCalendar::GetMaxTimestamp())
						$periodTs = CCalendar::GetMaxTimestamp();
				}
				else
					$periodTs = CCalendar::Timestamp($arFields['RRULE']['UNTIL']);
			}
			else
			{
				$periodTs = CCalendar::GetMaxTimestamp();
			}

			$arFields['RRULE']['UNTIL'] = $periodTs;
			$arFields['DT_TO'] = CCalendar::Date($periodTs);
			$arFields['DT_TO_TS'] = $periodTs;

			if (isset($arFields['RRULE']['BYDAY']))
			{
				$BYDAY = array();
				$days = array('SU','MO','TU','WE','TH','FR','SA');
				$bydays = explode(',', $arFields['RRULE']['BYDAY']);
				foreach($bydays as $day)
				{
					$day = strtoupper($day);
					if (in_array($day, $days))
						$BYDAY[] = $day;
				}
				$arFields['RRULE']['BYDAY'] = implode(',',$BYDAY);
			}

			$arFields['RRULE'] = self::PackRRule($arFields['RRULE']);
		}
		else
		{
			$arFields['RRULE'] = '';
		}
		$arFields['EXRULE'] = "";
		$arFields['RDATE'] = "";
		$arFields['EXDATE'] = "";

		// Location
		if (!is_array($arFields['LOCATION']))
			$arFields['LOCATION'] = Array("NEW" => is_string($arFields['LOCATION']) ? $arFields['LOCATION'] : "");

		// Private
		$arFields['PRIVATE_EVENT'] = isset($arFields['PRIVATE_EVENT']) && $arFields['PRIVATE_EVENT'];

		return true;
	}

	public static function Edit($Params = array())
	{
		global $DB, $CACHE_MANAGER;
		$arFields = $Params['arFields'];

		// Get current user id
		$userId = (isset($Params['userId']) && intVal($Params['userId']) > 0) ? intVal($Params['userId']) : CCalendar::GetCurUserId();
		if (!$userId && isset($arFields['CREATED_BY']))
			$userId = intVal($arFields['CREATED_BY']);
		$path = !empty($Params['path']) ? $Params['path'] : CCalendar::GetPath($arFields['CAL_TYPE'], $arFields['OWNER_ID'], true);
		if ($userId < 0)
			return false;

		if(!self::CheckFields($arFields))
			return false;

		if ($arFields['CAL_TYPE'] == 'user')
			$CACHE_MANAGER->ClearByTag('calendar_user_'.$arFields['OWNER_ID']);

		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;

		$arFields['TIMESTAMP_X'] = CCalendar::Date(mktime(), true, false);
		if ($bNew)
		{
			if (!isset($arFields['CREATED_BY']))
				$arFields['CREATED_BY'] = $userId;

			if (!isset($arFields['DATE_CREATE']))
				$arFields['DATE_CREATE'] = $arFields['TIMESTAMP_X'];
		}
		$attendees = is_array($arFields['ATTENDEES']) ? $arFields['ATTENDEES'] : array();

		if (!isset($arFields['OWNER_ID']) || !$arFields['OWNER_ID'])
			$arFields['OWNER_ID'] = 0;

		if (!isset($arFields['LOCATION']['OLD']) && !$bNew)
		{
			// Select meeting info about event
			if (isset($Params['currentEvent']))
				$oldEvent = $Params['currentEvent'];
			else
				$oldEvent = CCalendarEvent::GetById($arFields['ID']);
			if ($oldEvent)
				$arFields['LOCATION']['OLD'] = $oldEvent['LOCATION'];
		}

		$offset = CCalendar::GetOffset();
		$arFields['LOCATION'] = CCalendar::SetLocation(
			$arFields['LOCATION']['OLD'],
			$arFields['LOCATION']['NEW'],
			array(
				'dateFrom' => CCalendar::Date($arFields['DT_FROM_TS'] + $offset),
				'dateTo' => CCalendar::Date($arFields['DT_TO_TS'] + $offset),
				'name' => $arFields['NAME'],
				'persons' => count($attendees),
				'attendees' => $attendees,
				'bRecreateReserveMeetings' => $arFields['LOCATION']['RE_RESERVE'] !== 'N'
			)
		);

		$bSendInvitations = false;
		if (!isset($arFields['IS_MEETING']) && isset($arFields['ATTENDEES']) && is_array($arFields['ATTENDEES']) && empty($arFields['ATTENDEES']))
			$arFields['IS_MEETING'] = false;

		$attendeesCodes = array();
		if ($arFields['IS_MEETING'] && is_array($arFields['MEETING']))
		{
			if (!empty($arFields['ATTENDEES_CODES']))
			{
				$attendeesCodes = $arFields['ATTENDEES_CODES'];
				$arFields['ATTENDEES_CODES'] = implode(',', $arFields['ATTENDEES_CODES']);
			}

			// Organizer
			$bSendInvitations = $Params['bSendInvitations'] !== false;
			$arFields['~MEETING'] = array(
				'HOST_NAME' => $arFields['MEETING']['HOST_NAME'],
				'TEXT' => $arFields['MEETING']['TEXT'],
				'OPEN' => $arFields['MEETING']['OPEN'],
				'NOTIFY' => $arFields['MEETING']['NOTIFY'],
				'REINVITE' => $arFields['MEETING']['REINVITE']
			);
			$arFields['MEETING'] = serialize($arFields['~MEETING']);
		}

		$arReminders = array();
		if ($arFields['REMIND'] && is_array($arFields['REMIND']))
		{
			foreach ($arFields['REMIND'] as $remind)
				if (in_array($remind['type'], array('min', 'hour', 'day')))
					$arReminders[] = array('type' => $remind['type'],'count' => floatVal($remind['count']));
		}
		$arFields['REMIND'] = count($arReminders) > 0 ? serialize($arReminders) : '';

		$AllFields = self::GetFields();
		$dbFields = array();
		foreach($arFields as $field => $val)
			if(isset($AllFields[$field]) && $field != "ID")
				$dbFields[$field] = $arFields[$field];

		CTimeZone::Disable();

		if ($bNew) // Add
		{
			$ID = CDatabase::Add("b_calendar_event", $dbFields, array('DESCRIPTION', 'MEETING', 'RDATE', 'EXDATE'));
		}
		else // Update
		{
			$ID = $arFields['ID'];
			$strUpdate = $DB->PrepareUpdate("b_calendar_event", $dbFields);
			$strSql =
				"UPDATE b_calendar_event SET ".
					$strUpdate.
					" WHERE ID=".IntVal($arFields['ID']);

			$DB->QueryBind($strSql, array(
				'DESCRIPTION' => $arFields['DESCRIPTION'],
				'MEETING' => $arFields['MEETING'],
				'RDATE' => $arFields['RDATE'],
				'EXDATE' => $arFields['EXDATE']
			));
		}

		CTimeZone::Enable();

		if ($bNew && !isset($dbFields['DAV_XML_ID']))
		{
			$strSql =
				"UPDATE b_calendar_event SET ".
					$DB->PrepareUpdate("b_calendar_event", array('DAV_XML_ID' => $ID)).
					" WHERE ID=".IntVal($ID);
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		// Clean links
		// Del link from table
		if (!$bNew) // Del all rows if
		{
			$arAffectedSections = CCalendarEvent::GetCurrentSectionIds($ID);
			$DB->Query("DELETE FROM b_calendar_event_sect WHERE EVENT_ID=".IntVal($ID), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}
		else
		{
			$arAffectedSections = array();
		}

		$strSections = "0";
		foreach($arFields['SECTIONS'] as $sect)
		{
			if (IntVal($sect) > 0)
			{
				$strSections .= ",".IntVal($sect);
				$arAffectedSections[] = IntVal($sect);
			}
		}

		if (count($arAffectedSections) > 0)
			CCalendarSect::UpdateModificationLabel($arAffectedSections);

		// We don't have any section for this event
		// and we have to create default one.
		if($strSections == "0")
		{
			$defCalendar = CCalendarSect::CreateDefault(array(
				'type' => CCalendar::GetType(),
				'ownerId' => CCalendar::GetOwnerId()
			));
			$strSections .= ",".IntVal($defCalendar['ID']);
		}

		// Add links
		$strSql =
			"INSERT INTO b_calendar_event_sect(EVENT_ID, SECT_ID) ".
				"SELECT ".intVal($ID).", ID ".
				"FROM b_calendar_section ".
				"WHERE ID in (".$strSections.")";
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$bPull = CModule::IncludeModule("pull");
		if ($arFields['IS_MEETING'])
		{
			if(isset($arFields['ATTENDEES']))
			{
				self::InviteAttendees($ID, $arFields, $arFields['ATTENDEES'], is_array($Params['attendeesStatuses']) ? $Params['attendeesStatuses'] : array(), $bSendInvitations, $userId);

				if ($bPull)
				{
					// TODO: CACHE IT!
					$attendees = self::GetAttendees($ID);
					$attendees = $attendees[$ID];
					foreach($attendees as $user)
					{
						CPullStack::AddByUser($user['USER_ID'], Array(
							'module_id' => 'calendar',
							'command' => 'event_update',
							'params' => array(
								'EVENT' => CCalendarEvent::OnPullPrepareArFields($arFields),
								'ATTENDEES' => $attendees,
								'NEW' => $bNew ? 'Y' : 'N'
							)
						));
					}
				}
			}
		}
		else
		{
			if ($bPull)
			{
				CPullStack::AddByUser($userId, Array(
					'module_id' => 'calendar',
					'command' => 'event_update',
					'params' => array(
						'EVENT' => CCalendarEvent::OnPullPrepareArFields($arFields),
						'ATTENDEES' => array(),
						'NEW' => $bNew ? 'Y' : 'N'
					)
				));
			}
		}

		// Clean old reminders and add new reminders
		self::UpdateReminders(
			array(
				'id' => $ID,
				'reminders' => $arReminders,
				'arFields' => $arFields,
				'userId' => $userId,
				'path' => $path,
				'bNew' => $bNew
			)
		);

		if ($arFields['CAL_TYPE'] == 'user' && $arFields['IS_MEETING'] && !empty($attendeesCodes))
			CCalendarLiveFeed::OnEditCalendarEventEntry($ID, $arFields, $attendeesCodes);

		CCalendar::ClearCache('event_list');
		return $ID;
	}

	public static function GetCurrentSectionIds($ID)
	{
		global $DB;
		$strSql = "SELECT SECT_ID FROM b_calendar_event_sect WHERE EVENT_ID=".intVal($ID);
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$result = array();
		while($e = $res->Fetch())
			$result[] = intVal($e['SECT_ID']);

		return $result;
	}



	public static function OnPullPrepareArFields($arFields = array())
	{
		$arFields['~DESCRIPTION'] = self::ParseText($arFields['DESCRIPTION']);

		$arFields['~LOCATION'] = '';
		if ($arFields['LOCATION'] !== '')
		{
			$arFields['~LOCATION'] = $arFields['LOCATION'];
			$arFields['LOCATION'] = CCalendar::GetTextLocation($arFields["LOCATION"]);
		}

		if ($arFields['IS_MEETING'])
		{
			if (isset($arFields['~MEETING']))
				$arFields['MEETING'] = $arFields['~MEETING'];
		}

		if ($arFields['REMIND'] !== '' && !is_array($arFields['REMIND']))
		{
			$arFields['REMIND'] = unserialize($arFields['REMIND']);
			if (!is_array($arFields['REMIND']))
				$arFields['REMIND'] = array();
		}

		if ($arFields['RRULE'] !== '')
			$arFields['RRULE'] = self::ParseRRULE($arFields['RRULE']);

		return $arFields;
	}


	public static function UpdateUserFields($ID, $arFields = array())
	{
		$ID = intVal($ID);
		if (!is_array($arFields) || count($arFields) == 0 || $ID <= 0)
			return false;

		global $USER_FIELD_MANAGER;
		if ($USER_FIELD_MANAGER->CheckFields("CALENDAR_EVENT", $ID, $arFields))
			$USER_FIELD_MANAGER->Update("CALENDAR_EVENT", $ID, $arFields);

		foreach(GetModuleEvents("calendar", "OnAfterCalendarEventUserFieldsUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array('ID' => $ID,'arFields' => $arFields));

		return true;
	}

	public static function InviteAttendees($ID, $arFields = array(), $arAttendees = array(), $arStatuses = array(), $bSendInvitations = true, $userId = 0)
	{
		global $DB, $CACHE_MANAGER;
		$ID = intVal($ID);
		if (!$ID)
			return;

		// It's new event
		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;
		$curAttendeesIndex = array();
		$deletedAttendees = array();
		$arAffectedSections = array();

		if (!$bNew)
		{
			$curAttendees = self::GetAttendees($ID);
			$curAttendees = $curAttendees[$ID];
			if (is_array($curAttendees))
			{
				foreach($curAttendees as $user)
				{
					$curAttendeesIndex[$user['USER_KEY']] = $user;
					$deletedAttendees[$user['USER_KEY']] = $user['USER_KEY'];
					$arAffectedSections[] = CCalendar::GetMeetingSection($user['USER_KEY']);
				}
			}
		}

		$dbAttendees = array();
		$bReinvite = $arFields['~MEETING']['REINVITE'] !== false;
		$userId = $userId > 0 ? $userId : $arFields['MEETING_HOST'];
		$CACHE_MANAGER->ClearByTag('calendar_user_'.$userId);

		$delIdStr = "";
		// List of all attendees for event
		foreach($arAttendees as $userKey)
		{
			$bExternal = false;
			$key = false;

			if (substr($userKey, 0, strlen('BXEXT:')) == 'BXEXT:') // USER BY EMAIL
			{
				$email = substr($userKey, strlen('BXEXT:'));
				if (!check_email($email, true))
					continue;
				$key = $email;
				$bExternal = true;
			}

			if (!$bExternal && intVal($userKey) > 0) // User by ID
				$key = intVal($userKey);

			if (!$key) // Incorrect user
				continue;

			unset($deletedAttendees[$key]); // Unset item from deleted list

			if (!$curAttendeesIndex[$key])
				$arAffectedSections[] = CCalendar::GetMeetingSection($key);

			if ($curAttendeesIndex[$key] && !$bReinvite) // We already have this user in list
			{
				if (!$bNew && $key != $userId && $bSendInvitations)
				{
					if (!$bExternal && $curAttendeesIndex[$key]['STATUS'] == 'Y')
					{
						// Just send changing event notification
						CCalendar::SendMessage(array(
							'mode' => 'change_notify',
							'name' => $arFields['NAME'],
							"from" => $arFields["DT_FROM"],
							"to" => $arFields["DT_TO"],
							"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
							"meetingText" => $arFields["~MEETING"]["TEXT"],
							"guestId" => $key,
							"eventId" => $ID,
							"userId" => $userId
						));
					}
				}
				continue;
			}

			if ($bExternal || $arFields['MEETING_HOST'] == $key)
				$status = 'Y';
			else
				$status = 'Q';

			$dbAttendees[$key] = array(
				"bExternal" => $bExternal,
				"bReinvite" => isset($curAttendeesIndex[$key]) && $curAttendeesIndex[$key],
				"USER_KEY" => $key,
				"USER_ID" => $bExternal ? 0 : $key,
				"USER_EMAIL" => $bExternal ? $key : '',
				"USER_NAME" => "",
				"STATUS" => $status,
				"ACCESSIBILITY" => $arFields["ACCESSIBILITY"],
				"REMIND" => $arFields["REMIND"]
			);

			if (!$dbAttendees[$key]['bReinvite'])
				$delIdStr .= ','.((intVal($key) == $key) ? intVal($key) : "'".CDatabase::ForSql($key)."'");
		}

		$delIdStr = trim($delIdStr, ', ');

		// Delete users from attendees list
		if (count($deletedAttendees) > 0)
		{
			foreach($deletedAttendees as $key)
			{
				$att = $curAttendeesIndex[$key];
				if (!$att['EVENT_ID'])
				{
					//Send email DELETE
					//echo "Send EMAIL DELETE \n";
				}
				elseif ($bSendInvitations)
				{
					$CACHE_MANAGER->ClearByTag('calendar_user_'.$att["USER_ID"]);
					CCalendar::SendMessage(array(
						'mode' => 'cancel',
						'name' => $arFields['NAME'],
						"from" => $arFields["DT_FROM"],
						"to" => $arFields["DT_TO"],
						"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
						"guestId" => $att["USER_ID"],
						"eventId" => $ID,
						"userId" => $arFields['MEETING_HOST']
					));
				}

				$curAttendeesIndex[$user['USER_KEY']] = $user;
				$delIdStr .= ','.((intVal($key) == $key) ? intVal($key) : "'".CDatabase::ForSql($key)."'");
			}
		}

		$delIdStr = trim($delIdStr, ', ');
		if ($delIdStr != '')
		{
			$strSql = "DELETE from b_calendar_attendees WHERE EVENT_ID=".$ID." AND USER_KEY in(".$delIdStr.")";
			$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		if (CModule::IncludeModule("im"))
			CIMNotify::DeleteBySubTag("CALENDAR|INVITE|".$ID);

		// Add new attendees
		foreach ($dbAttendees as $att)
		{
			if ($att['bReinvite'])
			{
				$status = 'Q';
				if ($att['bExternal'])
				{
					$status = 'Y';
					// Send email CHANGE
				}
				else
				{
					if ($arFields['MEETING_HOST'] == $att['USER_KEY'])
						$status = 'Y';

					if ($att["USER_ID"] != $userId && $bSendInvitations)
					{
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$att["USER_ID"]);

						CCalendar::SendMessage(array(
							'mode' => 'change',
							'name' => $arFields['NAME'],
							"from" => $arFields["DT_FROM"],
							"to" => $arFields["DT_TO"],
							"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
							"meetingText" => $arFields["~MEETING"]["TEXT"],
							"guestId" => $att["USER_ID"],
							"eventId" => $ID,
							"userId" => $userId
						));
					}
				}

				if (isset($arStatuses[$att['USER_KEY']]) && in_array($arStatuses[$att['USER_KEY']], array('Y', 'N', 'Q')))
					$status = $arStatuses[$att['USER_KEY']];

				$strSql =
					"UPDATE b_calendar_attendees SET ".
						$DB->PrepareUpdate("b_calendar_attendees", array("STATUS" => $status)).
						" WHERE EVENT_ID=".$ID." AND USER_KEY=".($att['bExternal'] ? intVal($att['USER_KEY']) : "'".CDatabase::ForSql($att['USER_KEY'])."'");

				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				if ($att['bExternal'])
				{
					// Send email INVITE
				}
				else
				{
					if ($att["USER_ID"] != $userId && $bSendInvitations)
					{
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$att["USER_ID"]);

						CCalendar::SendMessage(array(
							'mode' => 'invite',
							'name' => $arFields['NAME'],
							"from" => $arFields["DT_FROM"],
							"to" => $arFields["DT_TO"],
							"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
							"meetingText" => $arFields["~MEETING"]["TEXT"],
							"guestId" => $att["USER_ID"],
							"eventId" => $ID,
							"userId" => $userId
						));
					}
				}

				if (isset($arStatuses[$att['USER_KEY']]) && in_array($arStatuses[$att['USER_KEY']], array('Y', 'N', 'Q')))
					$att["STATUS"] = $arStatuses[$att['USER_KEY']];

				$strSql = "INSERT INTO b_calendar_attendees(EVENT_ID, USER_KEY, USER_ID, USER_EMAIL, USER_NAME, STATUS, ACCESSIBILITY, REMIND) ".
					"VALUES (".$ID.", '".$DB->ForSql($att["USER_KEY"])."', ".intVal($att["USER_ID"]).", '".$DB->ForSql($att["USER_EMAIL"])."', '".$DB->ForSql($att["USER_NAME"])."', '".$DB->ForSql($att["STATUS"])."','".$DB->ForSql($att["ACCESSIBILITY"])."','".$DB->ForSql($att["REMIND"])."')";

				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}
		}

		if (count($arAffectedSections) > 0)
			CCalendarSect::UpdateModificationLabel($arAffectedSections);

		CCalendar::ClearCache('attendees_list');
	}

	public static function UpdateReminders($Params = array())
	{
		$eventId = intVal($Params['id']);
		$reminders = $Params['reminders'];
		$arFields = $Params['arFields'];
		$userId = $Params['userId'];
		$bNew = $Params['bNew'];

		$path = $Params['path'];
		$path = CHTTP::urlDeleteParams($path, array("action", "sessid", "bx_event_calendar_request", "EVENT_ID"));
		$viewPath = CHTTP::urlAddParams($path, array('EVENT_ID' => $eventId));

		$remAgentParams = array(
			'eventId' => $eventId,
			'userId' => $userId,
			'viewPath' => $viewPath,
			'calendarType' => $arFields["CAL_TYPE"],
			'ownerId' => $arFields["OWNER_ID"]
		);

		// 1. clean reminders
		if (!$bNew) // if we edit event here can be "old" reminders
			CCalendar::RemoveAgent($remAgentParams);

		// 2. Set new reminders
		$startTs = $arFields['DT_FROM_TS'];
		$agentTime = 0;

		foreach($reminders as $reminder)
		{
			$delta = intVal($reminder['count']) * 60; //Minute
			if ($reminder['type'] == 'hour')
				$delta = $delta * 60; //Hour
			elseif ($reminder['type'] == 'day')
				$delta =  $delta * 60 * 24; //Day

			$agentTime = $startTs + CCalendar::GetOffset($userId);
			if (($startTs - $delta) >= (time() - 60 * 5)) // Inaccuracy - 5 min
			{
				CCalendar::AddAgent(CCalendar::Date($agentTime - $delta), $remAgentParams);
			}
			elseif($arFields['RRULE'] != '')
			{
				$arEvents = CCalendarEvent::GetList(
					array(
						'arFilter' => array(
							"ID" => $eventId,
							"DELETED" => "N",
							"FROM_LIMIT" => CCalendar::Date(time() - 3600, false),
							"TO_LIMIT" => CCalendar::Date(2145938400, false)
						),
						'userId' => $userId,
						'parseRecursion' => true,
						'maxInstanceCount' => 2,
						'preciseLimits' => true,
						'fetchAttendees' => true,
						'checkPermissions' => false,
						'setDefaultLimit' => false
					)
				);

				if ($arEvents && is_array($arEvents[0]))
				{
					$nextEvent = $arEvents[0];
					if (($arEvents[0]['DT_FROM_TS']) < (time() - 60 * 5) && $arEvents[1]) // Inaccuracy - 5 min)
					{
						$nextEvent = $arEvents[1];
					}

					$startTs = $nextEvent['DT_FROM_TS'];
					$reminder = $nextEvent['REMIND'][0];
					if ($reminder)
					{
						$delta = intVal($reminder['count']) * 60; //Minute
						if ($reminder['type'] == 'hour')
							$delta = $delta * 60; //Hour
						elseif ($reminder['type'] == 'day')
							$delta =  $delta * 60 * 24; //Day

						$agentTime = $startTs - CCalendar::GetOffset($userId);
						if (($startTs - $delta) >= (time() - 60 * 5)) // Inaccuracy - 5 min
							CCalendar::AddAgent(CCalendar::Date($agentTime - $delta), $remAgentParams);
					}
				}
			}
		}

		// reminders for attendees
		if ($arFields['IS_MEETING'])
		{
			foreach ($arFields['ATTENDEES'] as $attendeeId)
			{
				if ($attendeeId == $userId)
					continue;

				$viewPath = CCalendar::GetPath('user', $attendeeId, true);

				$remAgentParams = array(
					'eventId' => $eventId,
					'userId' => $attendeeId,
					'viewPath' => $viewPath,
					'calendarType' => $arFields["CAL_TYPE"],
					'ownerId' => $arFields["OWNER_ID"]
				);

				foreach($reminders as $reminder)
				{
					$delta = intVal($reminder['count']) * 60; //Minute
					if ($reminder['type'] == 'hour')
						$delta = $delta * 60; //Hour
					elseif ($reminder['type'] == 'day')
						$delta =  $delta * 60 * 24; //Day

					if (($startTs - $delta) >= (time() - 60 * 5)) // Inaccuracy - 5 min
						CCalendar::AddAgent(CCalendar::Date($agentTime - $delta), $remAgentParams);
				}
			}
		}
	}

	public static function Delete($Params)
	{
		global $DB, $CACHE_MANAGER;
		$ID = intVal($Params['id']);
		if (!$ID)
			return false;

		$arAffectedSections = array();
		$Event = $Params['Event'];

		if (!isset($Event) || !is_array($Event))
		{
			CCalendar::SetOffset(false, 0);
			$res = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"ID" => $ID
					),
					'parseRecursion' => false
				)
			);
			$Event = $res[0];
		}

		if ($Event)
		{
			if ($Event['IS_MEETING'])
			{
				$userId = (isset($Params['userId']) && $Params['userId'] > 0) ? $Params['userId'] : CCalendar::GetCurUserId();

				if ($userId && $Event['IS_MEETING'] && $Event['MEETING_HOST'] != $userId)
				{
					CCalendarEvent::SetMeetingStatus(
						$userId,
						$Event['ID'],
						'N'
					);
					return;
				}
			}

			foreach(GetModuleEvents("calendar", "OnBeforeCalendarEventDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $Event));

			CCalendarLiveFeed::OnDeleteCalendarEventEntry($ID, $Event);

			$arAffectedSections[] = $Event['SECT_ID'];
			// Check location: if reserve meeting was reserved - clean reservation
			if ($Event['LOCATION'] != "")
			{
				$loc = CCalendar::ParseLocation($Event['LOCATION']);
				if ($loc['mrid'] !== false && $loc['mrevid'] !== false) // Release MR
					CCalendar::ReleaseLocation($loc);
			}

			if ($Event['CAL_TYPE'] == 'user')
				$CACHE_MANAGER->ClearByTag('calendar_user_'.$Event['OWNER_ID']);

			if ($Event['IS_MEETING'])
			{
				if (CModule::IncludeModule("im"))
					CIMNotify::DeleteBySubTag("CALENDAR|INVITE|".$ID);

				$userId = (isset($Params['userId']) && $Params['userId'] > 0) ? $Params['userId'] : $Event['MEETING_HOST'];
				$CACHE_MANAGER->ClearByTag('calendar_user_'.$userId);

				$curAttendees = self::GetAttendees($ID);
				$curAttendees = $curAttendees[$ID];

				foreach($curAttendees as $user)
				{
					if ($user["USER_ID"] > 0 && $user["STATUS"] != "N")
					{
						$arAffectedSections[] = CCalendar::GetMeetingSection($user["USER_ID"]);
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$user["USER_ID"]);
						CCalendar::SendMessage(array(
							'mode' => 'cancel',
							'name' => $Event['NAME'],
							"from" => $Event["DT_FROM"],
							"to" => $Event["DT_TO"],
							"location" => CCalendar::GetTextLocation($Event["LOCATION"]),
							"guestId" => $user["USER_ID"],
							"eventId" => $ID,
							"userId" => $userId
						));
					}
				}
			}

			if ($Params['bMarkDeleted'])
			{
				$strSql =
					"UPDATE b_calendar_event SET ".
						$DB->PrepareUpdate("b_calendar_event", array("DELETED" => "Y")).
						" WHERE ID=".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			else
			{
				// Real deleting
				$strSql = "DELETE from b_calendar_event WHERE ID=".$ID;
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				// Del link from table
				$strSql = "DELETE FROM b_calendar_event_sect WHERE EVENT_ID=".$ID;
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}

			if (count($arAffectedSections) > 0)
				CCalendarSect::UpdateModificationLabel($arAffectedSections);

			foreach(GetModuleEvents("calendar", "OnAfterCalendarEventDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($ID, $Event));

			CCalendar::ClearCache('event_list');
			return true;
		}
		return false;
	}

	public static function SetMeetingStatus($userId, $eventId, $status = 'Q', $comment = '')
	{
		global $DB;
		$eventId = intVal($eventId);
		$userId = intVal($userId);
		if(!in_array($status, array("Q", "Y", "N")))
			$status = "Q";

		// Select meeting info about event
		CTimeZone::Disable();
		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $eventId,
					"DELETED" => "N"
				),
				'fetchMeetings' => true,
				'parseRecursion' => false,
				'setDefaultLimit' => false
			)
		);

		$Event = $res[0];
		if ($Event && $Event['IS_MEETING'])
		{
			if ($Event['IS_MEETING'])
			{
				$arAffectedSections = array($Event['SECT_ID']);
				// Try to find this user into attendees for this event
				$strSql = "SELECT * FROM b_calendar_attendees WHERE USER_KEY=$userId AND EVENT_ID=$eventId";
				$dbAtt = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$curStatus = "Q";

				if($att = $dbAtt->Fetch()) // We find the user
				{
					$curStatus = $att["STATUS"];
					//Set status
					if ($att["STATUS"] != $status)
					{
						$strSql = "UPDATE b_calendar_attendees SET ".
							$DB->PrepareUpdate("b_calendar_attendees", array("STATUS" => $status, "DESCRIPTION" => $comment)).
							" WHERE EVENT_ID=".$eventId." AND USER_KEY=".$userId;
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
				}
				// We don't find the user but meetin is open and this user want take part
				else if($Event['MEETING'] && $Event['MEETING']['OPEN'] && $status == "Y")
				{
					//Set status
					$strSql = "INSERT INTO b_calendar_attendees(EVENT_ID, USER_KEY, USER_ID, STATUS, DESCRIPTION, ACCESSIBILITY) ".
						"VALUES (".$eventId.", '".$userId."', ".$userId.", '".$status."', '".$DB->ForSql($comment)."','')";
					$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				}

				if (($status == 'Y' || $status = 'N') && CModule::IncludeModule("im"))
					CIMNotify::DeleteByTag("CALENDAR|INVITE|".$eventId."|".$userId);

				if ($Event['MEETING']['NOTIFY'] && $status != 'Q' && $userId != $Event['CREATED_BY'] && $curStatus != $status)
				{
					// Send message to the author
					CCalendar::SendMessage(array(
						'mode' => $status == "Y" ? 'accept' : 'decline',
						'name' => $Event['NAME'],
						"from" => $Event["DT_FROM"],
						"to" => $Event["DT_TO"],
						"location" => CCalendar::GetTextLocation($Event["LOCATION"]),
						"comment" => $comment,
						"guestId" => $userId,
						"eventId" => $eventId,
						"userId" => $Event['CREATED_BY']
					));
				}

				$arAffectedSections[] = CCalendar::GetMeetingSection($userId);

				if (count($arAffectedSections) > 0)
					CCalendarSect::UpdateModificationLabel($arAffectedSections);
			}
		}

		CTimeZone::Enable();
		CCalendar::ClearCache(array('attendees_list', 'event_list'));
	}

	public static function GetMeetingStatus($userId, $eventId)
	{
		global $DB;
		$eventId = intVal($eventId);
		$userId = intVal($userId);

		// Select meeting info about event
		CTimeZone::Disable();
		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $eventId,
					"DELETED" => "N"
				),
				'fetchMeetings' => true,
				'parseRecursion' => false,
				'setDefaultLimit' => false
			)
		);

		$status = false;
		$Event = $res[0];
		if ($Event && $Event['IS_MEETING'])
		{
			// Try to find this user into attendees for this event
			$strSql = "SELECT * FROM b_calendar_attendees WHERE USER_KEY=$userId AND EVENT_ID=$eventId";
			$dbAtt = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if($att = $dbAtt->Fetch()) // We find the user
				$status = $att["STATUS"];
		}

		return $status;
	}

	public static function SetMeetingParams($userId, $eventId, $arFields)
	{
		global $DB;
		$eventId = intVal($eventId);
		$userId = intVal($userId);

		// Check $arFields
		if (!in_array($arFields['ACCESSIBILITY'], array('busy', 'quest', 'free', 'absent')))
			$arFields['ACCESSIBILITY'] = 'busy';

		$arReminders = array();
		if ($arFields['REMIND'] && is_array($arFields['REMIND']))
		{
			foreach ($arFields['REMIND'] as $remind)
				if (in_array($remind['type'], array('min', 'hour', 'day')))
					$arReminders[] = array('type' => $remind['type'],'count' => floatVal($remind['count']));
		}
		$arFields['REMIND'] = count($arReminders) > 0 ? serialize($arReminders) : '';

		// Reminding options
		$Event = CCalendarEvent::GetById($eventId);
		if (!$Event)
			return false;

		$path = CCalendar::GetPath($arFields['CAL_TYPE']);
		$path = CHTTP::urlDeleteParams($path, array("action", "sessid", "bx_event_calendar_request", "EVENT_ID"));
		$viewPath = CHTTP::urlAddParams($path, array('EVENT_ID' => $eventId));

		$remAgentParams = array(
			'eventId' => $eventId,
			'userId' => $userId,
			'viewPath' => $viewPath,
			'calendarType' => $Event["CAL_TYPE"],
			'ownerId' => $Event["OWNER_ID"]
		);

		// 1. clean reminders
		CCalendar::RemoveAgent($remAgentParams);

		// 2. Set new reminders
		foreach($arReminders as $reminder)
		{
			$delta = intVal($reminder['count']) * 60; //Minute
			if ($reminder['type'] == 'hour')
				$delta = $delta * 60; //Hour
			elseif ($reminder['type'] == 'day')
				$delta =  $delta * 60 * 24; //Day

			if (($Event['DT_FROM_TS'] - $delta) >= (time() - 60 * 5)) // Inaccuracy - 5 min
				CCalendar::AddAgent(CCalendar::Date($Event['DT_FROM_TS'] - $delta), $remAgentParams);
		}

		// Select meeting info about event
		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $eventId,
					"DELETED" => "N"
				),
				'parseRecursion' => false
			)
		);

		if ($Event = $res[0])
		{
			if ($Event['IS_MEETING'])
			{
				// Try to find this user into attendees for this event
				$strSql = "SELECT * FROM b_calendar_attendees WHERE USER_KEY=$userId AND EVENT_ID=$eventId";
				$dbAtt = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				if($att = $dbAtt->Fetch()) // We find the user
				{
					//Set params
					$strSql = "UPDATE b_calendar_attendees SET ".
						$DB->PrepareUpdate("b_calendar_attendees", $arFields).
						" WHERE EVENT_ID=".$eventId." AND USER_KEY=".$userId;

					$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				}
			}
		}

		CCalendar::ClearCache('attendees_list');
		return true;
	}

	public static function GetAccessibilityForUsers($Params = array())
	{
		global $DB;

		CTimeZone::Disable();
		$curEventId = intVal($Params['curEventId']);
		if (!is_array($Params['users']) || count($Params['users']) == 0)
			return array();

		$users = array();
		$Accessibility = array();
		foreach($Params['users'] as $userId)
		{
			$userId = intVal($userId);
			if ($userId)
			{
				$users[] = $userId;
				$Accessibility[$userId] = array();
			}
		}

		if (count($users) == 0)
			return array();

		$strUsers = join(',', $users);

		// We fetch all events for user where it was attented
		$strSql = "SELECT EVENT_ID,USER_ID,STATUS,ACCESSIBILITY FROM b_calendar_attendees WHERE USER_KEY in (".$strUsers.") AND STATUS<>'N'";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arInvIds = array();
		$Attendees = array();
		while($ev = $res->Fetch())
		{
			$ev["STATUS"] = trim($ev["STATUS"]);
			$ev["ACCESSIBILITY"] = trim($ev["ACCESSIBILITY"]);

			if (!is_array($Attendees[$ev['EVENT_ID']]))
				$Attendees[$ev['EVENT_ID']] = array();

			$Attendees[$ev['EVENT_ID']][] = $ev;
			if (!in_array(intVal($ev['EVENT_ID']), $arInvIds))
				$arInvIds[] = intVal($ev['EVENT_ID']);
		}
		$strInvIds = join(',', $arInvIds);

		$from_ts = false;
		$to_ts = false;
		$arSqlSearch = array();
		if ($Params['from'])
		{
			$val = $Params['from'];
			$from_ts = CCalendar::Timestamp($val);

			if (strtoupper($DB->type) == "MYSQL")
				$arSqlSearch[] = "CE.DT_TO>=FROM_UNIXTIME('".CCalendar::Timestamp($val, false)."')";
			elseif(strtoupper($DB->type) == "MSSQL")
				$arSqlSearch[] = "CE.DT_TO>=".$DB->CharToDateFunction($val, "SHORT");
			elseif(strtoupper($DB->type) == "ORACLE")
				$arSqlSearch[] = "CE.DT_TO>=TO_DATE('".$DB->FormatDate($val, CSite::GetDateFormat("SHORT", SITE_ID), "D.M.Y")." 00:00:00','dd.mm.yyyy hh24:mi:ss')";
		}
		if ($Params['to'])
		{
			$val = $Params['to'];
			$to_ts = CCalendar::Timestamp($val);
			if (date('H:i', $to_ts) == '00:00')
				$to_ts += CCalendar::DAY_LENGTH;

			if (strtoupper($DB->type) == "MYSQL")
				$arSqlSearch[] = "CE.DT_FROM<=FROM_UNIXTIME('".(CCalendar::Timestamp($val, false) + 86399)."')";
			elseif(strtoupper($DB->type) == "MSSQL")
				$arSqlSearch[] = "CE.DT_FROM<dateadd(day, 1, ".$DB->CharToDateFunction($val, "SHORT").")";
			elseif(strtoupper($DB->type) == "ORACLE")
				$arSqlSearch[] = "CE.DT_FROM<=TO_DATE('".$DB->FormatDate($val, CSite::GetDateFormat("SHORT", SITE_ID), "D.M.Y")." 23:59:59','dd.mm.yyyy hh24:mi:ss')";
		}
		$arSqlSearch[] = GetFilterQuery("DELETED", "N");

		$q = "CE.CAL_TYPE='user' AND CE.OWNER_ID in (".$strUsers.")";
		if (count($arInvIds) > 0)
			$q = '('.$q.') OR CE.ID in ('.$strInvIds.')';
		$arSqlSearch[] = $q;

		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);
		$strSql = "
			SELECT CE.ID, CE.CAL_TYPE,CE.OWNER_ID, CE.NAME, ".$DB->DateToCharFunction("CE.DT_FROM")." as DT_FROM, ".$DB->DateToCharFunction("CE.DT_TO")." as DT_TO, CE.DT_LENGTH, CE.PRIVATE_EVENT, CE.ACCESSIBILITY, CE.IMPORTANCE,CE.IS_MEETING, CE.MEETING_HOST, CE.MEETING, CE.LOCATION, CE.RRULE, CE.EXRULE, CE.RDATE, CE.EXDATE, CES.SECT_ID, CES.REL
			FROM
				b_calendar_event CE
			INNER JOIN b_calendar_event_sect CES ON (CE.ID=CES.EVENT_ID)
			WHERE CE.DELETED='N' AND $strSqlSearch";

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$arResult = Array();
		while($event = $res->Fetch())
		{
			$event = self::PreHandleEvent($event);

			if ($curEventId && $curEventId == $event['ID'])
				continue;

			if (self::CheckRecurcion($event))
				self::ParseRecursion($arResult, $event, array(
					'fromLimit' => $Params["from"],
					'toLimit' => $Params["to"]
				));
			else
				self::HandleEvent($arResult, $event);
		}

		foreach($arResult as $event)
		{
			$ev_to_ts = $event['DT_TO_TS'];
			if ($event['DT_SKIP_TIME'] == 'Y')
				$ev_to_ts += CCalendar::DAY_LENGTH;

			if (($from_ts && $ev_to_ts < $from_ts) || ($to_ts && $event['DT_FROM_TS'] > $to_ts))
				continue;

			if (!in_array($event["ACCESSIBILITY"], array('busy', 'quest', 'free', 'absent')))
				$event["ACCESSIBILITY"] = 'busy';
			if (!in_array($event['IMPORTANCE'], array('high', 'normal', 'low')))
				$event['IMPORTANCE'] = 'normal';

			$val = array(
				"ID" => $event["ID"],
				//"NAME" => $event["NAME"],
				"DT_FROM" => CCalendar::CutZeroTime($event["DT_FROM"]),
				"DT_TO" => CCalendar::CutZeroTime($event["DT_TO"]),
				"ACCESSIBILITY" => $event["ACCESSIBILITY"],
				"IMPORTANCE" => $event["IMPORTANCE"],
				"FROM" => $event['DT_FROM_TS'],
				"TO" => $event['DT_TO_TS']
			);

			if ($event['IS_MEETING'])
			{
				if (is_array($Attendees[$event['ID']]))
				{
					foreach($Attendees[$event['ID']] as $attendee)
					{
						if (is_array($Accessibility[$attendee['USER_ID']]))
						{
							$val['ACCESSIBILITY'] = $attendee['ACCESSIBILITY'];
							$Accessibility[$attendee['USER_ID']][] = $val;
						}
					}
				}
			}
			elseif ($event['CAL_TYPE'] == 'user' && is_array($Accessibility[$event['OWNER_ID']]))
			{
				$Accessibility[$event['OWNER_ID']][] = $val;
			}
		}
		CTimeZone::Enable();

		return $Accessibility;
	}

	public static function ApplyAccessRestrictions($event, $userId = false)
	{
		$sectId = $event['SECT_ID'];
		if (!$event['ACCESSIBILITY'])
			$event['ACCESSIBILITY'] = 'busy';

		$private = $event['PRIVATE_EVENT'] && $event['CAL_TYPE'] == 'user';
		$bManager = false;
		$bAttendee = false;

		if (isset($event['~ATTENDEES']))
		{
			foreach($event['~ATTENDEES'] as $user)
			{
				if ($user['USER_ID'] == $userId)
					$bAttendee = true;
			}
		}

		if(!$userId)
			$userId = CCalendar::GetUserId();

		$settings = CCalendar::GetSettings(array('request' => false));
		if (CModule::IncludeModule('intranet') && $event['CAL_TYPE'] == 'user' && $settings['dep_manager_sub'])
			$bManager = in_array($userId, CCalendar::GetUserManagers($event['OWNER_ID'], true));

		if ($event['CAL_TYPE'] == 'user' && $event['IS_MEETING'] && $event['OWNER_ID'] != $userId)
		{
			if ($bAttendee)
			{
				$sectId = CCalendar::GetMeetingSection($userId);
			}
			elseif (isset($event['USER_MEETING']['ATTENDEE_ID']) && $event['USER_MEETING']['ATTENDEE_ID'] !== $userId)
			{
				$sectId = CCalendar::GetMeetingSection($event['USER_MEETING']['ATTENDEE_ID']);
				$event['SECT_ID'] = $sectId;
				$event['OWNER_ID'] = $event['USER_MEETING']['ATTENDEE_ID'];
			}
		}

		if ($private || (!CCalendarSect::CanDo('calendar_view_full', $sectId, $userId) && !$bManager && !$bAttendee))
		{
			if ($private)
			{
				$event['NAME'] = '['.GetMessage('EC_ACCESSIBILITY_'.strtoupper($event['ACCESSIBILITY'])).']';
				if (!$bManager && !CCalendarSect::CanDo('calendar_view_time', $sectId, $userId))
					return false;
			}
			else
			{
				if (!CCalendarSect::CanDo('calendar_view_title', $sectId, $userId))
				{
					if (CCalendarSect::CanDo('calendar_view_time', $sectId, $userId))
						$event['NAME'] = '['.GetMessage('EC_ACCESSIBILITY_'.strtoupper($event['ACCESSIBILITY'])).']';
					else
						return false;
				}
				else
				{
					$event['NAME'] = $event['NAME'].' ['.GetMessage('EC_ACCESSIBILITY_'.strtoupper($event['ACCESSIBILITY'])).']';
				}
			}
			$event['~IS_MEETING'] = $event['IS_MEETING'];

			// Clear information about
			unset($event['DESCRIPTION'], $event['IS_MEETING'],$event['MEETING_HOST'],$event['MEETING'],$event['LOCATION'],$event['REMIND'],$event['USER_MEETING'],$event['~ATTENDEES'],$event['ATTENDEES_CODES']);
		}

		return $event;
	}

	public static function GetAbsent($users = false, $Params = array())
	{
		global $DB;
		// Can be called from agent... So we have to create $USER if it is not exists
		$tempUser = CCalendar::TempUser(false, true);

		$curUserId = isset($Params['userId']) ? intVal($Params['userId']) : CCalendar::GetCurUserId();
		$arUsers = array();
		if ($users !== false && is_array($users))
		{
			foreach($users as $id)
			{
				if ($id > 0)
					$arUsers[] = intVal($id);
			}
		}
		if (!count($arUsers))
			$users = false;

		// Part 1: select ordinary events
		$arFilter = array(
			'CAL_TYPE' => 'user',
			'DELETED' => 'N',
			'ACCESSIBILITY' => 'absent'
		);

		if (isset($Params['fromLimit']))
			$arFilter['FROM_LIMIT'] = CCalendar::Date(CCalendar::Timestamp($Params['fromLimit'], false), true, false);
		if (isset($Params['toLimit']))
			$arFilter['TO_LIMIT'] = CCalendar::Date(CCalendar::Timestamp($Params['toLimit'], false), true, false);

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => $arFilter,
				'getUserfields' => false,
				'parseRecursion' => true,
				'fetchAttendees' => false,
				'fetchMeetings' => true,
				'userId' => $curUserId,
				'checkPermissions' => false,
				'preciseLimits' => true
			)
		);

		// Part 2: select attendees
		CTimeZone::Disable();
		if (count($arUsers) > 0)
			$userQ = ' AND CA.USER_ID in ('.implode(',',$arUsers).')';
		else
			$userQ = '';

		$strSql = "
			SELECT
				CA.EVENT_ID as ID, CA.USER_ID, CA.STATUS, CA.ACCESSIBILITY,
				CE.CAL_TYPE,CE.OWNER_ID,CE.NAME,".$DB->DateToCharFunction("CE.DT_FROM")." as DT_FROM,".$DB->DateToCharFunction("CE.DT_TO")." as DT_TO, CE.DT_LENGTH, CE.PRIVATE_EVENT, CE.ACCESSIBILITY, CE.IMPORTANCE, CE.IS_MEETING, CE.MEETING_HOST, CE.MEETING, CE.LOCATION, CE.RRULE, CE.EXRULE, CE.RDATE, CE.EXDATE,
				CES.SECT_ID
			FROM b_calendar_attendees CA
			LEFT JOIN
				b_calendar_event CE ON(CA.EVENT_ID=CE.ID)
			LEFT JOIN
				b_calendar_event_sect CES ON (CA.EVENT_ID=CES.EVENT_ID)
			WHERE
					CE.ID IS NOT NULL
				AND
					CE.DELETED='N'
				AND
					STATUS='Y'
				AND
					CA.ACCESSIBILITY='absent'
				$userQ
			";

		if(isset($arFilter['FROM_LIMIT']))
		{
			$strSql .= "AND ";
			if (strtoupper($DB->type) == "MYSQL")
				$strSql .= "CE.DT_TO>=FROM_UNIXTIME('".CCalendar::Timestamp($arFilter['FROM_LIMIT'], false)."')";
			elseif(strtoupper($DB->type) == "MSSQL")
				$strSql .= "CE.DT_TO>=".$DB->CharToDateFunction($arFilter['FROM_LIMIT'], "SHORT");
			elseif(strtoupper($DB->type) == "ORACLE")
				$strSql .= "CE.DT_TO>=TO_DATE('".$DB->FormatDate($arFilter['FROM_LIMIT'], CSite::GetDateFormat("SHORT", SITE_ID), "D.M.Y")." 00:00:00','dd.mm.yyyy hh24:mi:ss')";
		}

		if($arFilter['TO_LIMIT'])
		{
			$strSql .= "AND ";
			if (strtoupper($DB->type) == "MYSQL")
				$strSql .= "CE.DT_FROM<=FROM_UNIXTIME('".(CCalendar::Timestamp($arFilter['TO_LIMIT'], false) + 86399)."')";
			elseif(strtoupper($DB->type) == "MSSQL")
				$strSql .= "CE.DT_FROM<=dateadd(day, 1, ".$DB->CharToDateFunction($arFilter['TO_LIMIT'], "SHORT").")";
			elseif(strtoupper($DB->type) == "ORACLE")
				$strSql .= "CE.DT_FROM<=TO_DATE('".$DB->FormatDate($arFilter['TO_LIMIT'], CSite::GetDateFormat("SHORT", SITE_ID), "D.M.Y")." 23:59:59','dd.mm.yyyy hh24:mi:ss')";
		}

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$arEvents2 = array();
		while($event = $res->Fetch())
		{
			$event = self::PreHandleEvent($event);

			if ($event['CAL_TYPE'] == 'user' && $event['IS_MEETING'] && $event['OWNER_ID'] == $event['USER_ID'])
				continue;

			if (self::CheckRecurcion($event))
				self::ParseRecursion($arEvents2, $event, array(
					'fromLimit' => $arFilter["FROM_LIMIT"],
					'toLimit' => $arFilter["TO_LIMIT"],
				));
			else
				self::HandleEvent($arEvents2, $event);
		}
		CTimeZone::Enable();

		$arEvents = array_merge($arEvents, $arEvents2);
		$bSocNet = CModule::IncludeModule("socialnetwork");

		$result = array();

		$settings = CCalendar::GetSettings(array('request' => false));
		foreach($arEvents as $event)
		{
			$userId = isset($event['USER_ID']) ? $event['USER_ID'] : $event['OWNER_ID'];
			if ($users !== false && !in_array($userId, $arUsers))
				continue;

			if ($bSocNet && !CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $userId, "calendar"))
				continue;

			if ((!$event['CAL_TYPE'] != 'user' || $curUserId != $event['OWNER_ID']) && $curUserId != $event['CREATED_BY'] && !isset($arUserMeeting[$event['ID']]))
			{
				$sectId = $event['SECT_ID'];
				if (!$event['ACCESSIBILITY'])
					$event['ACCESSIBILITY'] = 'busy';

				$private = $event['PRIVATE_EVENT'] && $event['CAL_TYPE'] == 'user';
				$bManager = false;
				if (!$private && CCalendar::IsIntranetEnabled() && CModule::IncludeModule('intranet') && $event['CAL_TYPE'] == 'user' && $settings['dep_manager_sub'])
					$bManager = in_array($curUserId, CCalendar::GetUserManagers($event['OWNER_ID'], true));

				if ($private || (!CCalendarSect::CanDo('calendar_view_full', $sectId) && !$bManager))
				{
					if ($private)
					{
						$event['NAME'] = '['.GetMessage('EC_ACCESSIBILITY_'.strtoupper($event['ACCESSIBILITY'])).']';
					}
					else
					{
						if (!CCalendarSect::CanDo('calendar_view_title', $sectId))
							$event['NAME'] = '['.GetMessage('EC_ACCESSIBILITY_'.strtoupper($event['ACCESSIBILITY'])).']';
						else
							$event['NAME'] = $event['NAME'].' ['.GetMessage('EC_ACCESSIBILITY_'.strtoupper($event['ACCESSIBILITY'])).']';
					}
				}
			}

			$result[] = array(
				'ID' => $event['ID'],
				'NAME' => $event['NAME'],
				'DATE_FROM' => $event['DT_FROM'],
				'DATE_TO' => $event['DT_TO'],
				'DT_FROM_TS' => $event['DT_FROM_TS'],
				'DT_TO_TS' => $event['DT_TO_TS'],
				'CREATED_BY' => $userId,
				'DETAIL_TEXT' => '',
				'USER_ID' => $userId
			);
		}

		// Sort by DT_FROM_TS
		usort($result, array('CCalendar', '_NearestSort'));

		CCalendar::TempUser($tempUser, false);
		return $result;
	}

	public static function DeleteEmpty()
	{
		global $DB;

		$strSql = 'SELECT CE.ID, CE.LOCATION
			FROM b_calendar_event CE
			LEFT JOIN b_calendar_event_sect CES ON (CE.ID=CES.EVENT_ID)
			WHERE CES.SECT_ID is null';
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$strItems = "0";
		while($arRes = $res->Fetch())
		{
			$loc = $arRes['LOCATION'];
			if ($loc && strlen($loc) > 5 && substr($loc, 0, 5) == 'ECMR_')
			{
				$loc = CCalendar::ParseLocation($loc);
				if ($loc['mrid'] !== false && $loc['mrevid'] !== false) // Release MR
					CCalendar::ReleaseLocation($loc);
			}
			$strItems .= ",".IntVal($arRes['ID']);
		}

		// Clean from 'b_calendar_event'
		if ($strItems != "0")
			$DB->Query("DELETE FROM b_calendar_event WHERE ID in (".$strItems.")", false,
				"FILE: ".__FILE__."<br> LINE: ".__LINE__);

		CCalendar::ClearCache(array('section_list', 'event_list'));
	}

	public static function CheckEndUpdateAttendeesCodes($event)
	{
		if ($event['ID'] > 0 && $event['IS_MEETING'] && empty($event['ATTENDEES_CODES']) && is_array($event['~ATTENDEES']))
		{
			$event['ATTENDEES_CODES'] = array();
			foreach($event['~ATTENDEES'] as $attendee)
			{
				if (intval($attendee['USER_ID']) > 0)
				{
					$event['ATTENDEES_CODES'][] = 'U'.IntVal($attendee['USER_ID']);
				}
			}
			$event['ATTENDEES_CODES'] = array_unique($event['ATTENDEES_CODES']);

			global $DB;
			$strSql =
				"UPDATE b_calendar_event SET ".
				"ATTENDEES_CODES='".implode(',', $event['ATTENDEES_CODES'])."'".
				" WHERE ID=".IntVal($event['ID']);
			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			CCalendar::ClearCache(array('event_list'));
		}
		return $event['ATTENDEES_CODES'];
	}

	public static function CanView($eventId, $userId)
	{
		CModule::IncludeModule("calendar");
		$Event = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $eventId,
				),
				'parseRecursion' => false,
				'fetchAttendees' => true,
				'checkPermissions' => true,
				'userId' => $userId,
			)
		);
		if ($Event && is_array($Event[0]))
		{
			// Event is not partly accessible - so it was not cleaned before by ApplyAccessRestrictions
			if (isset($Event[0]['DESCRIPTION']) || isset($Event[0]['IS_MEETING']) || isset($Event[0]['LOCATION']))
				return true;
		}
		return false;
	}
}
?>