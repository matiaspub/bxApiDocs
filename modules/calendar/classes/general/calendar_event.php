<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/calendar/classes/general/calendar.php");
class CCalendarEvent
{
	private static $Fields = array(), $lastAttendeesList = array();
	public static $eventUFDescriptions;
	public static $TextParser;

	private static function GetFields()
	{
		global $DB;
		if (!count(self::$Fields))
		{
			CTimeZone::Disable();
			self::$Fields = array(
				"ID" => Array("FIELD_NAME" => "CE.ID", "FIELD_TYPE" => "int"),
				"PARENT_ID" => Array("FIELD_NAME" => "CE.PARENT_ID", "FIELD_TYPE" => "int"),
				"ACTIVE" => Array("FIELD_NAME" => "CE.ACTIVE", "FIELD_TYPE" => "string"),
				"DELETED" => Array("FIELD_NAME" => "CE.DELETED", "FIELD_TYPE" => "string"),
				"CAL_TYPE" => Array("FIELD_NAME" => "CE.CAL_TYPE", "FIELD_TYPE" => "string"),
				"OWNER_ID" => Array("FIELD_NAME" => "CE.OWNER_ID", "FIELD_TYPE" => "int"),
				"EVENT_TYPE" => Array("FIELD_NAME" => "CE.EVENT_TYPE", "FIELD_TYPE" => "string"),
				"CREATED_BY" => Array("FIELD_NAME" => "CE.CREATED_BY", "FIELD_TYPE" => "int"),
				"NAME" => Array("FIELD_NAME" => "CE.NAME", "FIELD_TYPE" => "string"),
				"DATE_FROM" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DATE_FROM").' as DATE_FROM', "FIELD_TYPE" => "date"),
				"DATE_TO" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DATE_TO").' as DATE_TO', "FIELD_TYPE" => "date"),
				"TZ_FROM" => Array("FIELD_NAME" => "CE.TZ_FROM", "FIELD_TYPE" => "string"),
				"TZ_TO" => Array("FIELD_NAME" => "CE.TZ_TO", "FIELD_TYPE" => "string"),
				"TZ_OFFSET_FROM" => Array("FIELD_NAME" => "CE.TZ_OFFSET_FROM", "FIELD_TYPE" => "int"),
				"TZ_OFFSET_TO" => Array("FIELD_NAME" => "CE.TZ_OFFSET_TO", "FIELD_TYPE" => "int"),
				"DATE_FROM_TS_UTC" => Array("FIELD_NAME" => "CE.DATE_FROM_TS_UTC", "FIELD_TYPE" => "int"),
				"DATE_TO_TS_UTC" => Array("FIELD_NAME" => "CE.DATE_TO_TS_UTC", "FIELD_TYPE" => "int"),

				"TIMESTAMP_X" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.TIMESTAMP_X").' as TIMESTAMP_X', "FIELD_TYPE" => "date"),
				"DT_FROM" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DT_FROM").' as DT_FROM', "FIELD_TYPE" => "date"),
				"DT_TO" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DT_TO").' as DT_TO', "FIELD_TYPE" => "date"),
				"DATE_CREATE" => Array("FIELD_NAME" => $DB->DateToCharFunction("CE.DATE_CREATE").' as DATE_CREATE', "FIELD_TYPE" => "date"),
				"DESCRIPTION" => Array("FIELD_NAME" => "CE.DESCRIPTION", "FIELD_TYPE" => "string"),
				"DT_SKIP_TIME" => Array("FIELD_NAME" => "CE.DT_SKIP_TIME", "FIELD_TYPE" => "string"),
				"DT_LENGTH" => Array("FIELD_NAME" => "CE.DT_LENGTH", "FIELD_TYPE" => "int"),
				"PRIVATE_EVENT" => Array("FIELD_NAME" => "CE.PRIVATE_EVENT", "FIELD_TYPE" => "string"),
				"ACCESSIBILITY" => Array("FIELD_NAME" => "CE.ACCESSIBILITY", "FIELD_TYPE" => "string"),
				"IMPORTANCE" => Array("FIELD_NAME" => "CE.IMPORTANCE", "FIELD_TYPE" => "string"),
				"IS_MEETING" => Array("FIELD_NAME" => "CE.IS_MEETING", "FIELD_TYPE" => "string"),
				"MEETING_HOST" => Array("FIELD_NAME" => "CE.MEETING_HOST", "FIELD_TYPE" => "int"),
				"MEETING_STATUS" => Array("FIELD_NAME" => "CE.MEETING_STATUS", "FIELD_TYPE" => "string"),
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
		}
		return self::$Fields;
	}

	public static function GetList($Params = array())
	{
		global $DB, $USER_FIELD_MANAGER;
		$getUF = $Params['getUserfields'] !== false;
		$checkPermissions = $Params['checkPermissions'] !== false;
		$bCache = CCalendar::CacheTime() > 0;
		$Params['setDefaultLimit'] = $Params['setDefaultLimit'] === true;
		$userId = isset($Params['userId']) ? intVal($Params['userId']) : CCalendar::GetCurUserId();

		CTimeZone::Disable();
		if($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = 'event_list_'.md5(serialize($Params));
			if ($checkPermissions)
				$cacheId .= 'chper'.CCalendar::GetCurUserId().'|';
			if (CCalendar::IsSocNet() && CCalendar::IsSocnetAdmin())
				$cacheId .= 'socnetAdmin|';
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

			$Params['fetchAttendees'] = $Params['fetchAttendees'] !== false;

			if ($Params['setDefaultLimit'] !== false) // Deprecated
			{
				if (!isset($arFilter["FROM_LIMIT"])) // default 3 month back
					$arFilter["FROM_LIMIT"] = CCalendar::Date(time() - 31 * 3 * 24 * 3600, false);

				if (!isset($arFilter["TO_LIMIT"])) // default one year into the future
					$arFilter["TO_LIMIT"] = CCalendar::Date(time() + 365 * 24 * 3600, false);
			}

			// Array('ID' => 'asc')
			$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array();
			$arFields = self::GetFields();

			if ($arFilter["DELETED"] === false)
				unset($arFilter["DELETED"]);
			elseif (!isset($arFilter["DELETED"]))
				$arFilter["DELETED"] = "N";

			$join = '';
			$arSqlSearch = array();
			if(is_array($arFilter))
			{
				$filter_keys = array_keys($arFilter);
				for($i = 0, $l = count($filter_keys); $i<$l; $i++)
				{
					$n = strtoupper($filter_keys[$i]);
					$val = $arFilter[$filter_keys[$i]];
					if(is_string($val) && strlen($val) <=0 || strval($val) == "NOT_REF")
						continue;

					if($n == 'FROM_LIMIT')
					{
						$ts = CCalendar::Timestamp($val, false);
						if ($ts > 0)
							$arSqlSearch[] = "CE.DATE_TO_TS_UTC>=".$ts;
					}
					elseif($n == 'TO_LIMIT')
					{
						$ts = CCalendar::Timestamp($val, false);
						if ($ts > 0)
							$arSqlSearch[] = "CE.DATE_FROM_TS_UTC<=".($ts + 86399);
					}
					elseif($n == 'OWNER_ID')
					{
						if(is_array($val))
						{
							$val = array_map(intVal, $val);
							$arSqlSearch[] = 'CE.OWNER_ID IN (\''.implode('\',\'', $val).'\')';
						}
						else if (intVal($val) > 0)
						{
							$arSqlSearch[] = "CE.OWNER_ID=".intVal($val);
						}
					}
					elseif($n == 'NAME')
					{
						$arSqlSearch[] = "CE.NAME='".CDatabase::ForSql($val)."'";
					}
					elseif($n == 'CREATED_BY')
					{
						if(is_array($val))
						{
							$val = array_map(intVal, $val);
							$arSqlSearch[] = 'CE.CREATED_BY IN (\''.implode('\',\'', $val).'\')';
						}
						else if (intVal($val) > 0)
						{
							$arSqlSearch[] = "CE.CREATED_BY=".intVal($val);
						}
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

						if ($q != "")
							$arSqlSearch[] = $q;
					}
					elseif($n == 'ACTIVE_SECTION' && $val == "Y")
					{
						$arSqlSearch[] = "CS.ACTIVE='Y'";
						$join .= 'LEFT JOIN b_calendar_section CS ON (CES.SECT_ID=CS.ID)';
					}
					elseif($n == 'DAV_XML_ID' && is_array($val))
					{
						$val = array_map(array($DB, 'ForSQL'), $val);
						$arSqlSearch[] = 'CE.DAV_XML_ID IN (\''.implode('\',\'', $val).'\')';
					}
					elseif($n == 'DAV_XML_ID' && is_string($val))
					{
						$arSqlSearch[] = "CE.DAV_XML_ID='".CDatabase::ForSql($val)."'";
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
			{
				if(isset($arFields[strtoupper($by)]))
				{
					$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type) == "ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';
				}
			}

			if(strlen($strOrderBy) > 0)
			{
				$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");
			}

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
				LEFT JOIN b_calendar_event_sect CES ON (CE.ID=CES.EVENT_ID)
				".$join."
				".($getUF ? $obUserFieldsSql->GetJoin("CE.ID") : '')."
				WHERE
					$strSqlSearch
				$strOrderBy";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($getUF)
			{
				$res->SetUserFields($USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT"));
			}

			$arResult = Array();
			$arMeetingIds = array();
			$arEvents = array();
			$bIntranet = CCalendar::IsIntranetEnabled();

			$defaultMeetingSection = false;
			while($event = $res->Fetch())
			{
				$event['IS_MEETING'] = intVal($event['IS_MEETING']) > 0;

				if ($event['IS_MEETING'] && $event['CAL_TYPE'] == 'user' && $event['OWNER_ID'] == $userId && !$event['SECT_ID'])
				{
					if (!$defaultMeetingSection)
					{
						$defaultMeetingSection = CCalendar::GetMeetingSection($userId);
						if (!$defaultMeetingSection || !CCalendarSect::GetById($defaultMeetingSection, false))
						{
							$sectRes = CCalendarSect::GetSectionForOwner($event['CAL_TYPE'], $userId);
							$defaultMeetingSection = $sectRes['sectionId'];
						}
					}
					self::ConnectEventToSection($event['ID'], $defaultMeetingSection);
					$event['SECT_ID'] = $defaultMeetingSection;
				}

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
				if ($bIntranet && isset($event['MEETING']) && $event['MEETING'] != "")
				{
					$event['MEETING'] = unserialize($event['MEETING']);
					if (!is_array($event['MEETING']))
						$event['MEETING'] = array();
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

	// TODO: cache it!
	public static function GetAttendees($arEventIds = array())
	{
		global $DB;

		$arAttendees = array();

		if (CCalendar::IsSocNet())
		{
			if(is_array($arEventIds))
			{
				$arEventIds = array_unique($arEventIds);
			}
			else
			{
				$arEventIds = array($arEventIds);
			}

			$strMeetIds = "";
			foreach($arEventIds as $id)
				if(intVal($id) > 0)
					$strMeetIds .= ','.intVal($id);
			$strMeetIds = trim($strMeetIds, ', ');

			if($strMeetIds != '')
			{
				$strSql = "
				SELECT
					CE.OWNER_ID AS USER_ID,
					CE.ID, CE.PARENT_ID, CE.MEETING_STATUS, CE.COLOR, CE.TEXT_COLOR, CE.ACCESSIBILITY, CE.REMIND,
					U.LOGIN, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.EMAIL, U.PERSONAL_PHOTO, U.WORK_POSITION,
					BUF.UF_DEPARTMENT
				FROM
					b_calendar_event CE
					LEFT JOIN b_user U ON (U.ID=CE.OWNER_ID)
					LEFT JOIN b_uts_user BUF ON (BUF.VALUE_ID = CE.OWNER_ID)
				WHERE
					U.ACTIVE = 'Y' AND
					CE.ACTIVE = 'Y' AND
					CE.CAL_TYPE = 'user' AND
					CE.DELETED = 'N' AND
					CE.PARENT_ID in (".$strMeetIds.")";

				$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				while($entry = $res->Fetch())
				{
					$parentId = $entry['PARENT_ID'];
					$attendeeId = $entry['USER_ID'];
					if(!isset($arAttendees[$parentId]))
						$arAttendees[$parentId] = array();

					$entry["STATUS"] = trim($entry["MEETING_STATUS"]);
					$entry["COLOR"] = trim($entry["COLOR"]);
					$entry["TEXT_COLOR"] = trim($entry["TEXT_COLOR"]);
					$entry["ACCESSIBILITY"] = trim($entry["ACCESSIBILITY"]);

					if(empty($entry["ACCESSIBILITY"]))
						$entry["ACCESSIBILITY"] = 'busy';

					CCalendar::SetUserDepartment($attendeeId, (empty($entry['UF_DEPARTMENT']) ? array() : unserialize($entry['UF_DEPARTMENT'])));
					$entry['DISPLAY_NAME'] = CCalendar::GetUserName($entry);
					$entry['URL'] = CCalendar::GetUserUrl($attendeeId);
					$entry['AVATAR'] = CCalendar::GetUserAvatarSrc($entry);
					$entry['EVENT_ID'] = $entry['ID'];
					unset($entry['ID'], $entry['PARENT_ID'], $entry['MEETING_STATUS']);

					$arAttendees[$parentId][] = $entry;
				}
			}
		}

		return $arAttendees;
	}

	public static function GetAttendeesOld($arEventIds = array())
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

		$tsFrom = CCalendar::Timestamp($event['DATE_FROM']);
		$tsTo = CCalendar::Timestamp($event['DATE_TO']);

		if (($tsTo - $tsFrom) > $event['DT_LENGTH'] + CCalendar::DAY_LENGTH)
		{
			$toTS = $tsFrom + $event['DT_LENGTH'];
			if ($event['DT_SKIP_TIME'] == 'Y')
			{
				$toTS -= CCalendar::GetDayLen();
			}
			$event['DATE_TO'] = CCalendar::Date($toTS);
		}

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

		$evFromTS = CCalendar::Timestamp($event['DATE_FROM']);
		$evToTS = CCalendar::Timestamp($event['DATE_TO']);

		$limitFromTS += $event['TZ_OFFSET_FROM'];
		$limitToTS += $event['TZ_OFFSET_TO'];
		$limitToTS += CCalendar::GetDayLen();
		$limitFromTSReal = $limitFromTS;

		if ($limitFromTS < $event['DATE_FROM_TS_UTC'])
			$limitFromTS = $event['DATE_FROM_TS_UTC'];
		if ($limitToTS > $event['DATE_TO_TS_UTC'])
			$limitToTS = $event['DATE_TO_TS_UTC'];

		$skipTime = $event['DT_SKIP_TIME'] === 'Y';
		$fromTS = $evFromTS;

		$event['~DATE_FROM'] = $event['DATE_FROM'];
		$event['~DATE_TO'] = $event['DATE_TO'];

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
					if (($preciseLimits && $toTS > $limitFromTSReal) || (!$preciseLimits && $toTS > $limitFromTS - $h24))
					{
						if ($event['DT_SKIP_TIME'] == 'Y')
						{
							$toTS -= CCalendar::GetDayLen();
						}
						$event['DATE_FROM'] = CCalendar::Date($fromTS, !$skipTime, false);
						$event['DATE_TO'] = CCalendar::Date($toTS, !$skipTime, false);
						$event['RRULE'] = $rrule;
						$event['RINDEX'] = $count;
						self::HandleEvent($res, $event);
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
				if (($preciseLimits && $toTS > $limitFromTSReal) || (!$preciseLimits && $toTS > $limitFromTS - $h24))
				{
					if ($event['DT_SKIP_TIME'] == 'Y')
					{
						$toTS -= CCalendar::GetDayLen();
					}
					$event['DATE_FROM'] = CCalendar::Date($fromTS, !$skipTime, false);
					$event['DATE_TO'] = CCalendar::Date($toTS, !$skipTime, false);
					$event['RRULE'] = $rrule;
					$event['RINDEX'] = $count;
					self::HandleEvent($res, $event);

					$dispCount++;
				}
				$realCount++;
				switch ($rrule['FREQ'])
				{
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
		$item['LOCATION'] = trim($item['LOCATION']);

		if ($item['IS_MEETING'] && $item['MEETING'] != "" && !is_array($item['MEETING']))
		{
			$item['MEETING'] = unserialize($item['MEETING']);
			if (!is_array($item['MEETING']))
				$item['MEETING'] = array();
		}

		if (self::CheckRecurcion($item))
		{
			$tsFrom = CCalendar::Timestamp($item['DATE_FROM']);
			$tsTo = CCalendar::Timestamp($item['DATE_TO']);
			if (($tsTo - $tsFrom) > $item['DT_LENGTH'] + CCalendar::DAY_LENGTH)
			{
				$toTS = $tsFrom + $item['DT_LENGTH'];
				if ($item['DT_SKIP_TIME'] == 'Y')
				{
					$toTS -= CCalendar::GetDayLen();
				}
				$item['DATE_TO'] = CCalendar::Date($toTS);
			}
		}

		if ($item['IS_MEETING'])
		{
			if ($item['ATTENDEES_CODES'] != '')
			{
				$item['ATTENDEES_CODES'] = explode(',', $item['ATTENDEES_CODES']);
			}

			if ($item['ID'] == $item['PARENT_ID'])
				$item['MEETING_STATUS'] = 'H';
		}

		if (!isset($item['~IS_MEETING']))
			$item['~IS_MEETING'] = $item['IS_MEETING'];

		$item['DT_SKIP_TIME'] = $item['DT_SKIP_TIME'] === 'Y' ? 'Y' : 'N';

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

	private static function HandleEvent(&$res, $item = array())
	{
		$userId = CCalendar::GetCurUserId();

		$item['~USER_OFFSET_FROM'] = $item['~USER_OFFSET_TO'] = CCalendar::GetTimezoneOffset($item['TZ_FROM']) - CCalendar::GetCurrentOffsetUTC($userId);
		if ($item['TZ_FROM'] !== $item['TZ_TO'])
			$item['~USER_OFFSET_TO'] = CCalendar::GetTimezoneOffset($item['TZ_TO']) - CCalendar::GetCurrentOffsetUTC($userId);

		$res[] = $item;
	}

	public static function ParseRRULE($rule = '')
	{
		$res = array();
		if (!$rule || $rule === '')
			return $res;
		if (is_array($rule))
			return isset($rule['FREQ']) ? $rule : $res;

		$arRule = explode(";", $rule);
		if (!is_array($arRule))
			return $res;
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
						$res['UNTIL'] = CCalendar::Timestamp($arPar[1]) ? $arPar[1] : CCalendar::Date(intVal($arPar[1]), false, false);
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

		$res['~UNTIL'] = $res['UNTIL'];
		if ($res['UNTIL'] == CCalendar::GetMaxDate())
		{
			$res['~UNTIL'] = '';
		}
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

	public static function CheckFields(&$arFields, $currentEvent = array(), $userId = false)
	{
		if (!isset($arFields['TIMESTAMP_X']))
			$arFields['TIMESTAMP_X'] = CCalendar::Date(mktime(), true, false);

		if (!$userId)
			$userId = CCalendar::GetCurUserId();

		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;
		if (!isset($arFields['DATE_CREATE']) && $bNew)
		{
			$arFields['DATE_CREATE'] = $arFields['TIMESTAMP_X'];
		}

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

		unset($arFields['DT_FROM']);
		unset($arFields['DT_TO']);

		$arFields['DT_SKIP_TIME'] = $arFields['DT_SKIP_TIME'] !== 'Y' ? 'N' : 'Y';
		$fromTs = CCalendar::Timestamp($arFields['DATE_FROM'], false, $arFields['DT_SKIP_TIME'] !== 'Y');
		$toTs = CCalendar::Timestamp($arFields['DATE_TO'], false, $arFields['DT_SKIP_TIME'] !== 'Y');

		if ($fromTs > $toTs)
		{
			$toTs = $fromTs;
		}

		$arFields['DATE_FROM'] = CCalendar::Date($fromTs);
		$arFields['DATE_TO'] = CCalendar::Date($toTs);

		if (!$fromTs)
		{
			$arFields['DATE_FROM'] = FormatDate("SHORT", time());
			$fromTs = CCalendar::Timestamp($arFields['DATE_FROM'], false, false);
			if (!$toTs)
			{
				$arFields['DATE_TO'] = $arFields['DATE_FROM'];
				$toTs = $fromTs;
				$arFields['DT_SKIP_TIME'] = 'Y';
			}
		}
		elseif (!$toTs)
		{
			$arFields['DATE_TO'] = $arFields['DATE_FROM'];
			$toTs = $fromTs;
		}

		if ($arFields['DT_SKIP_TIME'] !== 'Y')
		{
			$arFields['DT_SKIP_TIME'] = 'N';
			if (!isset($arFields['TZ_FROM']) && isset($currentEvent['TZ_FROM']))
			{
				$arFields['TZ_FROM'] = $currentEvent['TZ_FROM'];
			}
			if (!isset($arFields['TZ_TO']) && isset($currentEvent['TZ_TO']))
			{
				$arFields['TZ_TO'] = $currentEvent['TZ_TO'];
			}

			if (!isset($arFields['TZ_FROM']) && !isset($arFields['TZ_TO']))
			{
				$userTimezoneOffsetUTC = CCalendar::GetCurrentOffsetUTC($userId);
				$userTimezoneName = CCalendar::GetUserTimezoneName($userId);
				if (!$userTimezoneName)
					$userTimezoneName = CCalendar::GetGoodTimezoneForOffset($userTimezoneOffsetUTC);

				$arFields['TZ_FROM'] = $userTimezoneName;
				$arFields['TZ_TO'] = $userTimezoneName;
			}

			if (!isset($arFields['TZ_OFFSET_FROM']))
			{
				$arFields['TZ_OFFSET_FROM'] = CCalendar::GetTimezoneOffset($arFields['TZ_FROM'], $fromTs);
			}
			if (!isset($arFields['TZ_OFFSET_TO']))
			{
				$arFields['TZ_OFFSET_TO'] = CCalendar::GetTimezoneOffset($arFields['TZ_TO'], $toTs);
			}
		}

		if (!isset($arFields['TZ_OFFSET_FROM']))
		{
			$arFields['TZ_OFFSET_FROM'] = 0;
		}
		if (!isset($arFields['TZ_OFFSET_TO']))
		{
			$arFields['TZ_OFFSET_TO'] = 0;
		}

		if (!isset($arFields['DATE_FROM_TS_UTC']))
		{
			$arFields['DATE_FROM_TS_UTC'] = $fromTs - $arFields['TZ_OFFSET_FROM'];
		}
		if (!isset($arFields['DATE_TO_TS_UTC']))
		{
			$arFields['DATE_TO_TS_UTC'] = $toTs - $arFields['TZ_OFFSET_TO'];
		}

		$h24 = 60 * 60 * 24; // 24 hours
		if ($arFields['DT_SKIP_TIME'] == 'Y')
		{
			unset($arFields['TZ_FROM']);
			unset($arFields['TZ_TO']);
			unset($arFields['TZ_OFFSET_FROM']);
			unset($arFields['TZ_OFFSET_TO']);
		}

		// Event length in seconds
		if (!isset($arFields['DT_LENGTH']) || $arFields['DT_LENGTH'] == 0)
		{
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
			$periodTs = CCalendar::Timestamp($arFields['RRULE']['UNTIL'], false, false);
			if (!$periodTs)
			{
				$arFields['RRULE']['UNTIL'] = CCalendar::GetMaxDate();
				$periodTs = CCalendar::Timestamp($arFields['RRULE']['UNTIL'], false, false);
			}
			$arFields['DATE_TO_TS_UTC'] = $periodTs + CCalendar::GetDayLen();
			$arFields['RRULE']['UNTIL'] = CCalendar::Date($periodTs, false);

			if (isset($arFields['RRULE']['BYDAY']))
			{
				if (is_array($arFields['RRULE']['BYDAY']))
				{
					$BYDAY = $arFields['RRULE']['BYDAY'];
				}
				else
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
				}
				$arFields['RRULE']['BYDAY'] = implode(',',$BYDAY);
			}
			unset($arFields['RRULE']['~UNTIL']);
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
		$arAffectedSections = array();
		$significantFieldList = array(
			'DATE_FROM',
			'DATE_TO',
			'RRULE',
			'RDATE',
			'EXDATE',
			'NAME',
			'DESCRIPTION',
			'LOCATION'
		);
		$significantChanges = isset($Params['significantChanges']) ? $Params['significantChanges'] : false;

		$result = false;
		// Get current user id
		$userId = (isset($Params['userId']) && intVal($Params['userId']) > 0) ? intVal($Params['userId']) : CCalendar::GetCurUserId();
		if (!$userId && isset($arFields['CREATED_BY']))
			$userId = intVal($arFields['CREATED_BY']);
		$path = !empty($Params['path']) ? $Params['path'] : CCalendar::GetPath($arFields['CAL_TYPE'], $arFields['OWNER_ID'], true);

		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;
		$arFields['TIMESTAMP_X'] = CCalendar::Date(mktime(), true, false);
		if ($bNew)
		{
			if (!isset($arFields['CREATED_BY']))
			{
				$arFields['CREATED_BY'] = ($arFields['IS_MEETING'] && $arFields['CAL_TYPE'] == 'user' && $arFields['OWNER_ID']) ? $arFields['OWNER_ID'] : $userId;
			}

			if (!isset($arFields['DATE_CREATE']))
				$arFields['DATE_CREATE'] = $arFields['TIMESTAMP_X'];
		}

		if (!isset($arFields['OWNER_ID']) || !$arFields['OWNER_ID'])
			$arFields['OWNER_ID'] = 0;

		// Current event
		$currentEvent = array();
		if (!$bNew)
		{
			if (isset($Params['currentEvent']))
				$currentEvent = $Params['currentEvent'];
			else
				$currentEvent = CCalendarEvent::GetById($arFields['ID']);

			if (is_array($arFields['LOCATION']) && !isset($arFields['LOCATION']['OLD']) && $currentEvent)
			{
				$arFields['LOCATION']['OLD'] = $currentEvent['LOCATION'];
			}

			if ($currentEvent['IS_MEETING'] && !isset($arFields['ATTENDEES']) && $currentEvent['PARENT_ID'] == $currentEvent['ID'] && $arFields['IS_MEETING'])
			{
				$arFields['ATTENDEES'] = array();
				$attendees = self::GetAttendees($currentEvent['PARENT_ID']);
				if ($attendees[$currentEvent['PARENT_ID']])
				{
					for($i = 0, $l = count($attendees[$currentEvent['PARENT_ID']]); $i < $l; $i++)
					{
						$arFields['ATTENDEES'][] = $attendees[$currentEvent['PARENT_ID']][$i]['USER_ID'];
					}
				}
			}

			if (($currentEvent['IS_MEETING'] || $arFields['IS_MEETING']) && $currentEvent['PARENT_ID'])
				$arFields['PARENT_ID'] = $currentEvent['PARENT_ID'];
		}
		if ($userId > 0 && self::CheckFields($arFields, $currentEvent, $userId))
		{
			if (!$bNew && !isset($Params['significantChanges']))
			{
				foreach ($significantFieldList as $fieldKey)
				{
					if ($arFields[$fieldKey] !== $currentEvent[$fieldKey] && $fieldKey != 'LOCATION')
					{
						$significantChanges = true;
						break;
					}
					else if ($fieldKey == 'LOCATION' && $arFields['LOCATION']['NEW'] != $currentEvent[$fieldKey])
					{
						$significantChanges = true;
						break;
					}
				}
			}

			if ($arFields['CAL_TYPE'] == 'user')
				$CACHE_MANAGER->ClearByTag('calendar_user_'.$arFields['OWNER_ID']);
			$attendees = is_array($arFields['ATTENDEES']) ? $arFields['ATTENDEES'] : array();

			if (!$arFields['PARENT_ID'] || $arFields['PARENT_ID'] == $arFields['ID'])
			{
				$fromTs = $arFields['DATE_FROM_TS_UTC'];
				$toTs = $arFields['DATE_TO_TS_UTC'];
				if ($arFields['DT_SKIP_TIME'] == "Y")
				{
					//$toTs += CCalendar::GetDayLen();
				}
				else
				{
					$fromTs += date('Z', $arFields['DATE_FROM_TS_UTC']);
					$toTs += date('Z', $arFields['DATE_TO_TS_UTC']);
				}

				$arFields['LOCATION'] = CCalendar::SetLocation(
					$arFields['LOCATION']['OLD'],
					$arFields['LOCATION']['NEW'],
					array(
						// UTC timestamp + date('Z', $timestamp) /*offset of the server*/
						'dateFrom' => CCalendar::Date($fromTs, $arFields['DT_SKIP_TIME'] !== "Y"),
						'dateTo' => CCalendar::Date($toTs, $arFields['DT_SKIP_TIME'] !== "Y"),
						'name' => $arFields['NAME'],
						'persons' => count($attendees),
						'attendees' => $attendees,
						'bRecreateReserveMeetings' => $arFields['LOCATION']['RE_RESERVE'] !== 'N'
					)
				);
			}
			else
			{
				$arFields['LOCATION'] = CCalendar::GetTextLocation($arFields['LOCATION']['NEW']);
			}

			$bSendInvitations = $Params['bSendInvitations'] !== false;
			if (!isset($arFields['IS_MEETING']) &&
				isset($arFields['ATTENDEES']) && is_array($arFields['ATTENDEES']) && empty($arFields['ATTENDEES']))
			{
				$arFields['IS_MEETING'] = false;
			}

			$attendeesCodes = array();

			if (is_array($arFields['MEETING']))
			{
				$arFields['~MEETING'] = $arFields['MEETING'];
				$arFields['MEETING'] = serialize($arFields['~MEETING']);
			}

			if ($arFields['IS_MEETING'] && is_array($arFields['MEETING']))
			{
				if (!empty($arFields['ATTENDEES_CODES']))
				{
					$attendeesCodes = $arFields['ATTENDEES_CODES'];
					$arFields['ATTENDEES_CODES'] = implode(',', $arFields['ATTENDEES_CODES']);
				}

				if (!isset($arFields['MEETING_STATUS']))
					$arFields['MEETING_STATUS'] = 'H';
			}

			$arReminders = array();
			if (is_array($arFields['REMIND']))
			{
				foreach ($arFields['REMIND'] as $remind)
					if (in_array($remind['type'], array('min', 'hour', 'day')))
						$arReminders[] = array('type' => $remind['type'],'count' => floatVal($remind['count']));
			}
			elseif($currentEvent['REMIND'])
			{
				$arReminders = $currentEvent['REMIND'];
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
				$eventId = CDatabase::Add("b_calendar_event", $dbFields, array('DESCRIPTION', 'MEETING', 'RDATE', 'EXDATE'));
			}
			else // Update
			{
				$eventId = $arFields['ID'];
				$strUpdate = $DB->PrepareUpdate("b_calendar_event", $dbFields);
				$strSql =
					"UPDATE b_calendar_event SET ".
						$strUpdate.
						" WHERE ID=".IntVal($eventId);

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
						$DB->PrepareUpdate("b_calendar_event", array('DAV_XML_ID' => $eventId)).
						" WHERE ID=".IntVal($eventId);
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			// *** Check and update section links ***
			$sectionId = (is_array($arFields['SECTIONS']) && $arFields['SECTIONS'][0]) ? intVal($arFields['SECTIONS'][0]) : false;

			if ($sectionId && CCalendarSect::GetById($sectionId, false))
			{
				if (!$bNew)
				{
					$arAffectedSections[] = $currentEvent['SECT_ID'];
				}
				self::ConnectEventToSection($eventId, $sectionId);
			}
			else
			{
				// It's new event we have to find section where to put it automatically
				if ($bNew)
				{
					if ($arFields['IS_MEETING'] && $arFields['PARENT_ID'] && $arFields['CAL_TYPE'] == 'user')
					{
						$sectionId = CCalendar::GetMeetingSection($arFields['OWNER_ID']);
					}
					else
					{
						$sectionId = CCalendarSect::GetLastUsedSection($arFields['CAL_TYPE'], $arFields['OWNER_ID'], $userId);
					}

					if ($sectionId)
					{
						$res = CCalendarSect::GetList(array('arFilter' => array('CAL_TYPE' => $arFields['CAL_TYPE'],'OWNER_ID' => $arFields['OWNER_ID'], 'ID'=> $sectionId)));
						if (!$res || !$res[0])
							$sectionId = false;
					}
					else
					{
						$sectionId = false;
					}

					if (!$sectionId)
					{
						$sectRes = CCalendarSect::GetSectionForOwner($arFields['CAL_TYPE'], $arFields['OWNER_ID'], true);
						$sectionId = $sectRes['sectionId'];
					}
					self::ConnectEventToSection($eventId, $sectionId);
				}
				else
				{
					// It's existing event, we take it's section to update modification lables (no db changes in b_calendar_event_sect)
					$sectionId = $currentEvent['SECT_ID'];
				}
			}
			$arAffectedSections[] = $sectionId;

			if (count($arAffectedSections) > 0)
				CCalendarSect::UpdateModificationLabel($arAffectedSections);

			$bPull = CModule::IncludeModule("pull");

			if ($arFields['IS_MEETING'] || (!$bNew && $currentEvent['IS_MEETING']))
			{
				if (!$arFields['PARENT_ID'])
				{
					$DB->Query("UPDATE b_calendar_event SET ".$DB->PrepareUpdate("b_calendar_event", array("PARENT_ID" => $eventId))." WHERE ID=".intVal($eventId), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
				}

				if (!$arFields['PARENT_ID'] || $arFields['PARENT_ID'] == $eventId)
				{
					self::CreateChildEvents($eventId, $arFields, $Params, $userId);
				}

				if (!$arFields['PARENT_ID'])
				{
					$arFields['PARENT_ID'] = intVal($eventId);
				}
			}
			else
			{
				if (($bNew && !$arFields['PARENT_ID']) || (!$bNew && !$currentEvent['PARENT_ID']))
				{
					$DB->Query("UPDATE b_calendar_event SET ".$DB->PrepareUpdate("b_calendar_event", array("PARENT_ID" => $eventId))." WHERE ID=".intVal($eventId), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					if (!$arFields['PARENT_ID'])
						$arFields['PARENT_ID'] = intVal($eventId);
				}

				if ($bPull)
				{
					$curUserId = $userId;
					if ($arFields['PARENT_ID'] && $arFields['PARENT_ID'] !== $arFields['ID'])
						$curUserId = $arFields['OWNER_ID'];

					CPullStack::AddByUser($curUserId, Array(
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
			if ($arFields["CAL_TYPE"] != 'user' || $arFields['OWNER_ID'] != $userId || $eventId == $arFields['PARENT_ID'])
			{
				self::UpdateReminders(
						array(
								'id' => $eventId,
								'reminders' => $arReminders,
								'arFields' => $arFields,
								'userId' => $userId,
								'path' => $path,
								'bNew' => $bNew
						)
				);
			}

			// Send invitations and notivications
			if ($arFields['IS_MEETING'])
			{
				if ($bSendInvitations)
				{
					if ($arFields['PARENT_ID'] != $eventId)
					{
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$arFields['OWNER_ID']);
						$fromTo = CCalendarEvent::GetEventFromToForUser($arFields, $arFields['OWNER_ID']);
						CCalendar::SendMessage(array(
							'mode' => 'invite',
							'name' => $arFields['NAME'],
							"from" => $fromTo['DATE_FROM'],
							"to" => $fromTo['DATE_TO'],
							"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
							"meetingText" => $arFields["~MEETING"]["TEXT"],
							"guestId" => $arFields['OWNER_ID'],
							"eventId" => $arFields['PARENT_ID'],
							"userId" => $userId
						));
					}
				}
				else
				{
					if ($arFields['PARENT_ID'] != $eventId && $arFields['MEETING_STATUS'] == "Y" && $significantChanges)
					{
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$arFields['OWNER_ID']);
						$fromTo = CCalendarEvent::GetEventFromToForUser($arFields, $arFields['OWNER_ID']);
						CCalendar::SendMessage(array(
							'mode' => 'change_notify',
							'name' => $arFields['NAME'],
							"from" => $fromTo['DATE_FROM'],
							"to" => $fromTo['DATE_TO'],
							"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
							"meetingText" => $arFields["~MEETING"]["TEXT"],
							"guestId" => $arFields['OWNER_ID'],
							"eventId" => $arFields['PARENT_ID'],
							"userId" => $userId
						));
					}
				}
			}

			if ($arFields['CAL_TYPE'] == 'user' && $arFields['IS_MEETING'] && !empty($attendeesCodes) && $arFields['PARENT_ID'] == $eventId)
			{
				CCalendarLiveFeed::OnEditCalendarEventEntry($eventId, $arFields, $attendeesCodes);
			}

			CCalendar::ClearCache('event_list');

			$result = $eventId;
		}

		return $result;
	}

	public static function GetEventFromToForUser($params, $userId)
	{
		$skipTime = $params['DT_SKIP_TIME'] !== 'N';

		$fromTs = CCalendar::Timestamp($params['DATE_FROM'], false, !$skipTime);
		$toTs = CCalendar::Timestamp($params['DATE_TO'], false, !$skipTime);
		if (!$skipTime)
		{
			$fromTs = $fromTs - (CCalendar::GetTimezoneOffset($params['TZ_FROM']) - CCalendar::GetCurrentOffsetUTC($userId));
			$toTs = $toTs - (CCalendar::GetTimezoneOffset($params['TZ_TO']) - CCalendar::GetCurrentOffsetUTC($userId));
		}
		$dateFrom = CCalendar::Date($fromTs, !$skipTime);
		$dateTo = CCalendar::Date($toTs, !$skipTime);

		return array(
			"DATE_FROM" => $dateFrom,
			"DATE_TO" => $dateTo,
			"TS_FROM" => $fromTs,
			"TS_TO" => $toTs
		);
	}

	public static function GetCurrentSectionIds($eventId)
	{
		global $DB;
		$strSql = "SELECT SECT_ID FROM b_calendar_event_sect WHERE EVENT_ID=".intVal($eventId);
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

		if (isset($arFields['~MEETING']))
			$arFields['MEETING'] = $arFields['~MEETING'];


		if ($arFields['REMIND'] !== '' && !is_array($arFields['REMIND']))
		{
			$arFields['REMIND'] = unserialize($arFields['REMIND']);
			if (!is_array($arFields['REMIND']))
				$arFields['REMIND'] = array();
		}

		if ($arFields['RRULE'] != '')
			$arFields['RRULE'] = self::ParseRRULE($arFields['RRULE']);

		return $arFields;
	}

	public static function UpdateUserFields($eventId, $arFields = array())
	{
		$eventId = intVal($eventId);
		if (!is_array($arFields) || count($arFields) == 0 || $eventId <= 0)
			return false;

		global $USER_FIELD_MANAGER;
		if ($USER_FIELD_MANAGER->CheckFields("CALENDAR_EVENT", $eventId, $arFields))
			$USER_FIELD_MANAGER->Update("CALENDAR_EVENT", $eventId, $arFields);

		foreach(GetModuleEvents("calendar", "OnAfterCalendarEventUserFieldsUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array('ID' => $eventId,'arFields' => $arFields));

		return true;
	}

	public static function CreateChildEvents($parentId, $arFields, $Params, $userId = 0)
	{
		global $DB, $CACHE_MANAGER;
		$parentId = intVal($parentId);
		$attendees = $arFields['ATTENDEES'];
		$bCalDav = CCalendar::IsCalDAVEnabled();
		$involvedAttendees = array();

		if ($parentId)
		{

			// It's new event
			$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;

			$curAttendeesIndex = array();
			$deletedAttendees = array();
			//$arAffectedSections = array();
			if (!$bNew)
			{
				$curAttendees = self::GetAttendees($parentId);
				$curAttendees = $curAttendees[$parentId];

				if (is_array($curAttendees))
				{
					foreach($curAttendees as $user)
					{
						$curAttendeesIndex[$user['USER_ID']] = $user;
						if ($user['USER_ID'] !== $arFields['MEETING_HOST'] && ($user['USER_ID'] !== $arFields['OWNER_ID'] || $arFields['CAL_TYPE'] !== 'user'))
						{
							$deletedAttendees[$user['USER_ID']] = $user['USER_ID'];
							$involvedAttendees[] = $user['USER_ID'];
						}
						//$arAffectedSections[] = CCalendar::GetMeetingSection($user['USER_KEY']);
					}
				}
			}

			if (is_array($attendees))
			{
				foreach($attendees as $userKey)
				{
					$attendeeId = intVal($userKey);
					$CACHE_MANAGER->ClearByTag('calendar_user_'.$attendeeId);

					// Skip creation child event if it's event inside his own user calendar
					if ($attendeeId && (intVal($arFields['CREATED_BY']) !== $attendeeId || $arFields['CAL_TYPE'] != 'user'))
					{
						$childParams = $Params;
						$childParams['arFields']['CAL_TYPE'] = 'user';
						$childParams['arFields']['PARENT_ID'] = $parentId;
						$childParams['arFields']['OWNER_ID'] = $attendeeId;
						$childParams['arFields']['CREATED_BY'] = $attendeeId;
						if (intVal($arFields['CREATED_BY']) == $attendeeId)
							$childParams['arFields']['MEETING_STATUS'] = 'Y';
						else
							$childParams['arFields']['MEETING_STATUS'] = 'Q';

						unset($childParams['arFields']['SECTIONS']);
						unset($childParams['currentEvent']);
						unset($childParams['arFields']['ID']);
						unset($childParams['arFields']['DAV_XML_ID']);

						$bExchange = CCalendar::IsExchangeEnabled($attendeeId);

						if ($bNew || !$curAttendeesIndex[$attendeeId])
						{
							$childSectId = CCalendar::GetMeetingSection($attendeeId, true);
							if ($childSectId)
							{
								$childParams['arFields']['SECTIONS'] = array($childSectId);
							}

							// CalDav & Exchange
							if ($bExchange || $bCalDav)
							{
								CCalendar::DoSaveToDav(array(
										'bCalDav' => $bCalDav,
										'bExchange' => $bExchange,
										'sectionId' => $childSectId
								), $childParams['arFields']);
							}
						}

						$childParams['bSendInvitations'] = $Params['bSendInvitations'];
						if (!$bNew && $curAttendeesIndex[$attendeeId])
						{
							$childParams['arFields']['ID'] = $curAttendeesIndex[$attendeeId]['EVENT_ID'];

							if (!$arFields['~MEETING']['REINVITE'])
							{
								$childParams['arFields']['MEETING_STATUS'] = $curAttendeesIndex[$attendeeId]['STATUS'];

								$childParams['bSendInvitations'] = $childParams['bSendInvitations'] &&  $curAttendeesIndex[$attendeeId]['STATUS'] != 'Q';
							}

							if ($bExchange || $bCalDav)
							{
								$childParams['currentEvent'] = CCalendarEvent::GetById($childParams['arFields']['ID'], false);
								CCalendar::DoSaveToDav(array(
										'bCalDav' => $bCalDav,
										'bExchange' => $bExchange,
										'sectionId' => $childParams['currentEvent']['SECT_ID']
								), $childParams['arFields'], $childParams['currentEvent']);
							}
						}

						self::Edit($childParams);
						$involvedAttendees[] = $attendeeId;
						unset($deletedAttendees[$attendeeId]);
					}
				}
			}

			// Delete
			$delIdStr = '';
			if (!$bNew && count($deletedAttendees) > 0)
			{
				foreach($deletedAttendees as $attendeeId)
				{
					$att = $curAttendeesIndex[$attendeeId];
					if ($Params['bSendInvitations'] !== false && $att['STATUS'] == 'Y')
					{
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$att["USER_ID"]);
						$fromTo = CCalendarEvent::GetEventFromToForUser($arFields, $att["USER_ID"]);
						CCalendar::SendMessage(array(
							'mode' => 'cancel',
							'name' => $arFields['NAME'],
							"from" => $fromTo['DATE_FROM'],
							"to" => $fromTo['DATE_TO'],
							"location" => CCalendar::GetTextLocation($arFields["LOCATION"]),
							"guestId" => $att["USER_ID"],
							"eventId" => $parentId,
							"userId" => $arFields['MEETING_HOST']
						));
					}
					$delIdStr .= ','.intVal($att['EVENT_ID']);

					$bExchange = CCalendar::IsExchangeEnabled($attendeeId);
					if ($bExchange || $bCalDav)
					{
						$currentEvent = CCalendarEvent::GetList(
							array(
								'arFilter' => array(
									"PARENT_ID" => $parentId,
									"OWNER_ID" => $attendeeId,
									"IS_MEETING" => 1,
									"DELETED" => "N"
								),
								'parseRecursion' => false,
								'fetchAttendees' => true,
								'fetchMeetings' => true,
								'checkPermissions' => false,
								'setDefaultLimit' => false
							)
						);
						$currentEvent = $currentEvent[0];

						if ($currentEvent)
						{
							CCalendar::DoDeleteToDav(array(
									'bCalDav' => $bCalDav,
									'bExchangeEnabled' => $bExchange,
									'sectionId' => $currentEvent['SECT_ID']
							), $currentEvent);
						}
					}
				}
			}

			$delIdStr = trim($delIdStr, ', ');
			if ($delIdStr != '')
			{
				$strSql =
					"UPDATE b_calendar_event SET ".
					$DB->PrepareUpdate("b_calendar_event", array("DELETED" => "Y")).
					" WHERE PARENT_ID=".intval($parentId)." AND ID IN(".$delIdStr.")";
				$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
			}

			if (count($involvedAttendees) > 0)
			{
				$involvedAttendees = array_unique($involvedAttendees);
				CCalendar::UpdateCounter($involvedAttendees);
			}
		}
	}

	public static function GetChildEvents($parentId)
	{
		global $DB;

		$arFields = self::GetFields();
		$childEvents = array();
		$selectList = "";
		foreach($arFields as $field)
			$selectList .= $field['FIELD_NAME'].", ";
		$selectList = trim($selectList, ' ,').' ';

		if ($parentId > 0)
		{

			$strSql = "
				SELECT ".
				$selectList.
				"FROM b_calendar_event CE WHERE CE.PARENT_ID=".intval($parentId);

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			while($event = $res->Fetch())
			{
				$childEvents[] = $event;
			}
		}
		return false;
	}

	public static function ConnectEventToSection($eventId, $sectionId)
	{
		global $DB;
		$DB->Query(
			"DELETE FROM b_calendar_event_sect WHERE EVENT_ID=".intVal($eventId),
			false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$DB->Query(
			"INSERT INTO b_calendar_event_sect(EVENT_ID, SECT_ID) ".
			"SELECT ".intVal($eventId).", ID ".
			"FROM b_calendar_section ".
			"WHERE ID=".intVal($sectionId),
			false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
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
			'userId' => $arFields["CREATED_BY"],
			'viewPath' => $viewPath,
			'calendarType' => $arFields["CAL_TYPE"],
			'ownerId' => $arFields["OWNER_ID"]
		);

		// 1. clean reminders
		if (!$bNew) // if we edit event here can be "old" reminders
			CCalendar::RemoveAgent($remAgentParams);

		// 2. Set new reminders
		$startTs = $arFields['DATE_FROM_TS_UTC']; // Start of the event in UTC
		$agentTime = 0;

		foreach($reminders as $reminder)
		{
			$delta = intVal($reminder['count']) * 60; //Minute
			if ($reminder['type'] == 'hour')
				$delta = $delta * 60; //Hour
			elseif ($reminder['type'] == 'day')
				$delta =  $delta * 60 * 24; //Day

			// $startTs - UTC timestamp;  date('Z', $startTs) - offset of the server
			$agentTime = $startTs + date('Z', $startTs);
			if (($agentTime - $delta) >= (time() - 60 * 5)) // Inaccuracy - 5 min
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
							"TO_LIMIT" => CCalendar::GetMaxDate()
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
					$startTs = CCalendar::Timestamp($nextEvent['DATE_FROM'], false, $arEvents[0]["DT_SKIP_TIME"] !== 'Y');
					if ($nextEvent["DT_SKIP_TIME"] == 'N' && $nextEvent["TZ_FROM"])
					{
						$startTs = $startTs - CCalendar::GetTimezoneOffset($nextEvent["TZ_FROM"], $startTs); // UTC timestamp
					}

					if (($startTs + date("Z", $startTs)) < (time() - 60 * 5) && $arEvents[1]) // Inaccuracy - 5 min)
					{
						$nextEvent = $arEvents[1];
					}

					$startTs = CCalendar::Timestamp($nextEvent['DATE_FROM'], false, $arEvents[0]["DT_SKIP_TIME"] !== 'Y');
					if ($nextEvent["DT_SKIP_TIME"] == 'N' && $nextEvent["TZ_FROM"])
					{
						$startTs = $startTs - CCalendar::GetTimezoneOffset($nextEvent["TZ_FROM"], $startTs); // UTC timestamp
					}
					$reminder = $nextEvent['REMIND'][0];
					if ($reminder)
					{
						$delta = intVal($reminder['count']) * 60; //Minute
						if ($reminder['type'] == 'hour')
							$delta = $delta * 60; //Hour
						elseif ($reminder['type'] == 'day')
							$delta =  $delta * 60 * 24; //Day

						// $startTs - UTC timestamp;  date("Z", $startTs) - offset of the server
						$agentTime = $startTs + date("Z", $startTs);
						if (($agentTime - $delta) >= (time() - 60 * 5)) // Inaccuracy - 5 min
							CCalendar::AddAgent(CCalendar::Date($agentTime - $delta), $remAgentParams);
					}
				}
			}
		}
	}

	public static function Delete($params)
	{
		global $DB, $CACHE_MANAGER;
		$bCalDav = CCalendar::IsCalDAVEnabled();
		$id = intVal($params['id']);
		if ($id)
		{
			$userId = (isset($params['userId']) && $params['userId'] > 0) ? $params['userId'] : CCalendar::GetCurUserId();
			$arAffectedSections = array();
			$event = $params['Event'];

			if (!isset($event) || !is_array($event))
			{
				CCalendar::SetOffset(false, 0);
				$res = CCalendarEvent::GetList(
					array(
						'arFilter' => array(
							"ID" => $id
						),
						'parseRecursion' => false
					)
				);
				$event = $res[0];
			}

			if ($event)
			{
				if ($event['IS_MEETING'] && $event['PARENT_ID'] !== $event['ID'])
				{
					CCalendarEvent::SetMeetingStatus(
						$userId,
						$event['ID'],
						'N'
					);
				}
				else
				{
					foreach(GetModuleEvents("calendar", "OnBeforeCalendarEventDelete", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array($id, $event));

					if ($event['PARENT_ID'])
						CCalendarLiveFeed::OnDeleteCalendarEventEntry($event['PARENT_ID'], $event);
					else
						CCalendarLiveFeed::OnDeleteCalendarEventEntry($event['ID'], $event);

					$arAffectedSections[] = $event['SECT_ID'];
					// Check location: if reserve meeting was reserved - clean reservation
					if ($event['LOCATION'] != "")
					{
						$loc = CCalendar::ParseLocation($event['LOCATION']);
						if ($loc['mrid'] !== false && $loc['mrevid'] !== false) // Release MR
							CCalendar::ReleaseLocation($loc);
					}

					if ($event['CAL_TYPE'] == 'user')
						$CACHE_MANAGER->ClearByTag('calendar_user_'.$event['OWNER_ID']);

					if ($event['IS_MEETING'])
					{
						if (CModule::IncludeModule("im"))
						{
							CIMNotify::DeleteBySubTag("CALENDAR|INVITE|".$event['PARENT_ID']);
							CIMNotify::DeleteBySubTag("CALENDAR|STATUS|".$event['PARENT_ID']);
						}

						$involvedAttendees = array();

						$CACHE_MANAGER->ClearByTag('calendar_user_'.$userId);
						$childEvents = CCalendarEvent::GetList(
							array(
								'arFilter' => array(
									"PARENT_ID" => $id
								),
								'parseRecursion' => false,
								'checkPermissions' => false,
								'setDefaultLimit' => false
							)
						);

						$chEventIds = array();
						foreach($childEvents as $chEvent)
						{
							$CACHE_MANAGER->ClearByTag('calendar_user_'.$chEvent["OWNER_ID"]);
							if ($chEvent["MEETING_STATUS"] != "N")
							{
								if ($chEvent['DATE_TO_TS_UTC'] + date("Z", $chEvent['DATE_TO_TS_UTC']) > (time() - 60 * 5))
								{
									$fromTo = CCalendarEvent::GetEventFromToForUser($event, $chEvent["OWNER_ID"]);
									CCalendar::SendMessage(array(
										'mode' => 'cancel',
										'name' => $chEvent['NAME'],
										"from" => $fromTo["DATE_FROM"],
										"to" => $fromTo["DATE_TO"],
										"location" => CCalendar::GetTextLocation($chEvent["LOCATION"]),
										"guestId" => $chEvent["OWNER_ID"],
										"eventId" => $id,
										"userId" => $userId
									));
								}
							}
							$chEventIds[] = $chEvent["ID"];

							if ($chEvent["MEETING_STATUS"] == "Q")
								$involvedAttendees[] = $chEvent["OWNER_ID"];

							$bExchange = CCalendar::IsExchangeEnabled($chEvent["OWNER_ID"]);
							if ($bExchange || $bCalDav)
							{
								CCalendar::DoDeleteToDav(array(
										'bCalDav' => $bCalDav,
										'bExchangeEnabled' => $bExchange,
										'sectionId' => $chEvent['SECT_ID']
								), $chEvent);
							}
						}

						// Set flag
						if ($params['bMarkDeleted'])
						{
							$strSql =
								"UPDATE b_calendar_event SET ".
								$DB->PrepareUpdate("b_calendar_event", array("DELETED" => "Y")).
								" WHERE PARENT_ID=".$id;
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
						}
						else // Actual deleting
						{
							$strSql = "DELETE from b_calendar_event WHERE PARENT_ID=".$id;
							$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

							$strChEvent = join(',', $chEventIds);
							if (count($chEventIds) > 0)
							{
								// Del link from table
								$strSql = "DELETE FROM b_calendar_event_sect WHERE EVENT_ID in (".$strChEvent.")";
								$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
							}
						}

						if (count($involvedAttendees) > 0)
						{
							CCalendar::UpdateCounter($involvedAttendees);
						}
					}

					if ($params['bMarkDeleted'])
					{
						$strSql =
							"UPDATE b_calendar_event SET ".
							$DB->PrepareUpdate("b_calendar_event", array("DELETED" => "Y")).
							" WHERE ID=".$id;
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
					}
					else
					{
						// Real deleting
						$strSql = "DELETE from b_calendar_event WHERE ID=".$id;
						$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

						// Del link from table
						$strSql = "DELETE FROM b_calendar_event_sect WHERE EVENT_ID=".$id;
						$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					}

					if (count($arAffectedSections) > 0)
						CCalendarSect::UpdateModificationLabel($arAffectedSections);

					foreach(GetModuleEvents("calendar", "OnAfterCalendarEventDelete", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array($id, $event));

					CCalendar::ClearCache('event_list');
				}
				return true;
			}
		}
		return false;
	}

	public static function SetMeetingStatus($userId, $eventId, $status = 'Q', $comment = '')
	{
		CTimeZone::Disable();
		global $DB, $CACHE_MANAGER;
		$eventId = intVal($eventId);
		$userId = intVal($userId);
		if(!in_array($status, array("Q", "Y", "N", "H", "M")))
			$status = "Q";

		$event = CCalendarEvent::GetById($eventId, false);
		if ($event && $event['IS_MEETING'] && intVal($event['PARENT_ID']) > 0)
		{
			$strSql = "UPDATE b_calendar_event SET ".
				$DB->PrepareUpdate("b_calendar_event", array("MEETING_STATUS" => $status)).
				" WHERE PARENT_ID=".intVal($event['PARENT_ID'])." AND OWNER_ID=".$userId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			CCalendarSect::UpdateModificationLabel($event['SECT_ID']);

			// Clear invitation in messager
			if (CModule::IncludeModule("im"))
			{
				CIMNotify::DeleteByTag("CALENDAR|INVITE|".$event['PARENT_ID']."|".$userId);
				CIMNotify::DeleteByTag("CALENDAR|STATUS|".$event['PARENT_ID']."|".$userId);
			}

			// Add new notification in messenger
			$fromTo = CCalendarEvent::GetEventFromToForUser($event, $userId);
			CCalendar::SendMessage(array(
				'mode' => $status == "Y" ? 'status_accept' : 'status_decline',
				'name' => $event['NAME'],
				"from" => $fromTo["DATE_FROM"],
				"guestId" => $userId,
				"eventId" => $event['PARENT_ID'],
				"userId" => $userId,
				"markRead" => true
			));

			// If it's open meeting and our attendee is not on the list
			if ($event['MEETING'] && $event['MEETING']['OPEN'] && ($status == 'Y' || $status == 'M'))
			{
				$arAttendees = self::GetAttendees(array($event['PARENT_ID']));
				$arAttendees = $arAttendees[$event['PARENT_ID']];
				$attendeeExist = false;
				foreach($arAttendees as $attendee)
				{
					if ($attendee['USER_ID'] == $userId)
					{
						$attendeeExist = true;
						break;
					}
				}

				if (!$attendeeExist)
				{
					// 1. Create another childEvent for new attendee
					$AllFields = self::GetFields();
					$dbFields = array();
					foreach($event as $field => $val)
					{
						if(isset($AllFields[$field]) && $field != "ID" && $field != "ATTENDEES_CODES")
						{
							$dbFields[$field] = $event[$field];
						}
					}
					$dbFields['MEETING_STATUS'] = $status;
					$dbFields['CAL_TYPE'] = 'user';
					$dbFields['OWNER_ID'] = $userId;
					$dbFields['PARENT_ID'] = $event['PARENT_ID'];
					$dbFields['MEETING'] = serialize($event['MEETING']);
					$dbFields['REMIND'] = serialize($event['REMIND']);
					$eventId = CDatabase::Add("b_calendar_event", $dbFields, array('DESCRIPTION', 'MEETING', 'RDATE', 'EXDATE'));
					$DB->Query("UPDATE b_calendar_event SET ".
						$DB->PrepareUpdate("b_calendar_event", array('DAV_XML_ID' => $eventId)).
						" WHERE ID=".IntVal($eventId), false, "File: ".__FILE__."<br>Line: ".__LINE__);

					$sectionId = CCalendarSect::GetLastUsedSection('user', $userId, $userId);
					if (!$sectionId || !CCalendarSect::GetById($sectionId, false))
					{
						$sectRes = CCalendarSect::GetSectionForOwner('user', $userId);
						$sectionId = $sectRes['sectionId'];
					}
					if ($eventId && $sectionId)
					{
						self::ConnectEventToSection($eventId, $sectionId);
					}

					// 2. Update ATTENDEES_CODES
					$attendeesCodes = $event['ATTENDEES_CODES'];
					$attendeesCodes[] = 'U'.intVal($userId);
					$attendeesCodes = array_unique($attendeesCodes);
					$DB->Query("UPDATE b_calendar_event SET ".
						"ATTENDEES_CODES='".implode(',', $attendeesCodes)."'".
						" WHERE PARENT_ID=".intVal($event['PARENT_ID']), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

					CCalendarSect::UpdateModificationLabel(array($sectionId));
				}
			}

			// Notify author of event
			if ($event['MEETING']['NOTIFY'] && $userId != $event['MEETING_HOST'])
			{
				// Send message to the author
				$fromTo = CCalendarEvent::GetEventFromToForUser($event, $event['MEETING_HOST']);
				CCalendar::SendMessage(array(
					'mode' => $status == "Y" ? 'accept' : 'decline',
					'name' => $event['NAME'],
					"from" => $fromTo["DATE_FROM"],
					"to" => $fromTo["DATE_TO"],
					"location" => CCalendar::GetTextLocation($event["LOCATION"]),
					"comment" => $comment,
					"guestId" => $userId,
					"eventId" => $event['PARENT_ID'],
					"userId" => $event['MEETING_HOST']
				));
			}
			CCalendarSect::UpdateModificationLabel(array($event['SECTIONS'][0]));

			CCalendar::UpdateCounter($userId);

			$CACHE_MANAGER->ClearByTag('calendar_user_'.$userId);
			$CACHE_MANAGER->ClearByTag('calendar_user_'.$event['CREATED_BY']);
		}

		CTimeZone::Enable();
		CCalendar::ClearCache(array('attendees_list', 'event_list'));
	}

	public static function GetMeetingStatus($userId, $eventId)
	{
		global $DB;
		$eventId = intVal($eventId);
		$userId = intVal($userId);
		$status = false;
		$event = CCalendarEvent::GetById($eventId, false);
		if ($event && $event['IS_MEETING'] && intVal($event['PARENT_ID']) > 0)
		{
			if ($event['CREATED_BY'] == $userId)
			{
				$status = $event['MEETING_STATUS'];
			}
			else
			{
				$res = $DB->Query("SELECT MEETING_STATUS from b_calendar_event WHERE PARENT_ID=".intVal($event['PARENT_ID'])." AND CREATED_BY=".$userId, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				$event = $res->Fetch();
				$status = $event['MEETING_STATUS'];
			}
		}
		return $status;
	}

	public static function SetMeetingParams($userId, $eventId, $arFields)
	{
		$eventId = intVal($eventId);
		$userId = intVal($userId);

		// Check $arFields
		if (!in_array($arFields['ACCESSIBILITY'], array('busy', 'quest', 'free', 'absent')))
			$arFields['ACCESSIBILITY'] = 'busy';

		$event = CCalendarEvent::GetById($eventId);
		if (!$event)
			return false;

		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"PARENT_ID" => $eventId,
					"CREATED_BY" => $userId,
					"IS_MEETING" => 1,
					"DELETED" => "N"
				),
				'parseRecursion' => false,
				'fetchAttendees' => true,
				'fetchMeetings' => true,
				'checkPermissions' => true,
				'setDefaultLimit' => false
			)
		);

		if (!$res || !$res[0])
		{
			$res = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"ID" => $eventId,
						"CREATED_BY" => $userId,
						"IS_MEETING" => 1,
						"DELETED" => "N"
					),
					'parseRecursion' => false,
					'fetchAttendees' => true,
					'fetchMeetings' => true,
					'checkPermissions' => true,
					'setDefaultLimit' => false
				)
			);
		}

		if ($res[0])
		{
			$event = $res[0];
			$arReminders = array();
			if (isset($arFields['REMIND']))
			{
				if ($arFields['REMIND'] && is_array($arFields['REMIND']))
				{
					foreach ($arFields['REMIND'] as $remind)
						if (in_array($remind['type'], array('min', 'hour', 'day')))
							$arReminders[] = array('type' => $remind['type'],'count' => floatVal($remind['count']));
				}
			}

			$arFields = array(
				"ID" => $event['ID'],
				"REMIND" => $arReminders,
				"ACCESSIBILITY" => $arFields['ACCESSIBILITY']
			);
			//SaveEvent
			CCalendar::SaveEvent(array('arFields' => $arFields));
		}
		return true;
	}

	/*
	 * $params['dateFrom']
	 * $params['dateTo']
	 *
	 * */
	public static function GetAccessibilityForUsers($params = array())
	{
		$curEventId = intVal($params['curEventId']);
		if (!is_array($params['users']) || count($params['users']) == 0)
			return array();

		if (!isset($params['checkPermissions']))
			$params['checkPermissions'] = true;

		$users = array();
		$accessibility = array();
		foreach($params['users'] as $userId)
		{
			$userId = intVal($userId);
			if ($userId)
			{
				$users[] = $userId;
				$accessibility[$userId] = array();
			}
		}

		if (count($users) == 0)
			return array();

		$events = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"FROM_LIMIT" => $params['from'],
					"TO_LIMIT" => $params['to'],
					"CAL_TYPE" => 'user',
					"OWNER_ID" => $users,
					"ACTIVE_SECTION" => "Y"
				),
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'setDefaultLimit' => false,
				'checkPermissions' => $params['checkPermissions']
			)
		);

		foreach($events as $event)
		{
			if ($curEventId && ($event["ID"] == $curEventId || $event["PARENT_ID"] == $curEventId))
				continue;
			if ($event["IS_MEETING"] && ($event["MEETING_STATUS"] == "N" || $event["MEETING_STATUS"] == "Q"))
				continue;

			$accessibility[$event['OWNER_ID']][] = array(
				"ID" => $event["ID"],
				"NAME" => $event["NAME"],
				"DATE_FROM" => $event["DATE_FROM"],
				"DATE_TO" => $event["DATE_TO"],
				"~USER_OFFSET_FROM" => $event["~USER_OFFSET_FROM"],
				"~USER_OFFSET_TO" => $event["~USER_OFFSET_TO"],
				"DT_SKIP_TIME" => $event["DT_SKIP_TIME"],
				"TZ_FROM" => $event["TZ_FROM"],
				"TZ_TO" => $event["TZ_TO"],
				"ACCESSIBILITY" => $event["ACCESSIBILITY"],
				"IMPORTANCE" => $event["IMPORTANCE"],
				"EVENT_TYPE" => $event["EVENT_TYPE"]
			);
		}

		return $accessibility;
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

			foreach($event as $k => $value)
			{
				if (substr($k, 0, 3) == 'UF_')
					unset($event[$k]);
			}
		}

		return $event;
	}

	public static function GetAbsent($users = false, $Params = array())
	{
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

		$arFilter = array(
			'DELETED' => 'N',
			'ACCESSIBILITY' => 'absent',
		);

		if ($users)
			$arFilter['CREATED_BY'] = $users;

		if (isset($Params['fromLimit']))
			$arFilter['FROM_LIMIT'] = CCalendar::Date(CCalendar::Timestamp($Params['fromLimit'], false), true, false);
		if (isset($Params['toLimit']))
			$arFilter['TO_LIMIT'] = CCalendar::Date(CCalendar::Timestamp($Params['toLimit'], false), true, false);

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => $arFilter,
				'parseRecursion' => true,
				'getUserfields' => false,
				'userId' => $curUserId,
				'preciseLimits' => true,
				'checkPermissions' => false,
				'skipDeclined' => true
			)
		);

		$bSocNet = CModule::IncludeModule("socialnetwork");
		$result = array();
		$settings = CCalendar::GetSettings(array('request' => false));

		foreach($arEvents as $event)
		{
			$userId = isset($event['USER_ID']) ? $event['USER_ID'] : $event['CREATED_BY'];
			if ($users !== false && !in_array($userId, $arUsers))
				continue;

			if ($bSocNet && !CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $userId, "calendar"))
				continue;

			if ($event['IS_MEETING'] && $event["MEETING_STATUS"] == 'N')
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
					$event = self::ApplyAccessRestrictions($event, $userId);
				}
			}

			$skipTime = $event['DT_SKIP_TIME'] === 'Y';
			$fromTs = CCalendar::Timestamp($event['DATE_FROM'], false, !$skipTime);
			$toTs = CCalendar::Timestamp($event['DATE_TO'], false, !$skipTime);
			if ($event['DT_SKIP_TIME'] !== 'Y')
			{
				$fromTs -= $event['~USER_OFFSET_FROM'];
				$toTs -= $event['~USER_OFFSET_TO'];
			}
			$result[] = array(
				'ID' => $event['ID'],
				'NAME' => $event['NAME'],
				'DATE_FROM' => CCalendar::Date($fromTs, !$skipTime, false),
				'DATE_TO' => CCalendar::Date($toTs, !$skipTime, false),
				'DT_FROM_TS' => $fromTs,
				'DT_TO_TS' => $toTs,
				'CREATED_BY' => $userId,
				'DETAIL_TEXT' => '',
				'USER_ID' => $userId
			);
		}

		// Sort by DATE_FROM_TS_UTC
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

	public static function UpdateAttendeesCodes($parentEventId, $newAttendee)
	{

//		$arFields['ATTENDEES_CODES'] = $arAccessCodes;
//		$arFields['ATTENDEES'] = CCalendar::GetDestinationUsers($arAccessCodes);

//		if ($event['ID'] > 0 && $event['IS_MEETING'] && empty($event['ATTENDEES_CODES']) && is_array($event['~ATTENDEES']))
//		{
//			$event['ATTENDEES_CODES'] = array();
//			foreach($event['~ATTENDEES'] as $attendee)
//			{
//				if (intval($attendee['USER_ID']) > 0)
//				{
//					$event['ATTENDEES_CODES'][] = 'U'.IntVal($attendee['USER_ID']);
//				}
//			}
//			$event['ATTENDEES_CODES'] = array_unique($event['ATTENDEES_CODES']);
//
//			global $DB;
//			$strSql =
//				"UPDATE b_calendar_event SET ".
//				"ATTENDEES_CODES='".implode(',', $event['ATTENDEES_CODES'])."'".
//				" WHERE ID=".IntVal($event['ID']);
//			$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
//			CCalendar::ClearCache(array('event_list'));
//		}
//		return $event['ATTENDEES_CODES'];
	}

	public static function CanView($eventId, $userId)
	{
		CModule::IncludeModule("calendar");
		$event = CCalendarEvent::GetList(
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

		if (!$event || !is_array($event[0]))
		{
			$event = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"PARENT_ID" => $eventId,
						"CREATED_BY" => $userId
					),
					'parseRecursion' => false,
					'fetchAttendees' => true,
					'checkPermissions' => true,
					'userId' => $userId,
				)
			);
		}

		if ($event && is_array($event[0]))
		{
			// Event is not partly accessible - so it was not cleaned before by ApplyAccessRestrictions
			if (isset($event[0]['DESCRIPTION']) || isset($event[0]['IS_MEETING']) || isset($event[0]['LOCATION']))
				return true;
		}

		return false;
	}

	public static function GetEventUserFields($event)
	{
		global $USER_FIELD_MANAGER;
		if ($event['PARENT_ID'])
		{
			$UF = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $event['PARENT_ID'], LANGUAGE_ID);
		}
		else
		{
			$UF = $USER_FIELD_MANAGER->GetUserFields("CALENDAR_EVENT", $event['ID'], LANGUAGE_ID);
		}
		return $UF;
	}
}
?>