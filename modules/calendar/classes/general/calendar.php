<?
/** var CMain $APPLICATION */
IncludeModuleLangFile(__FILE__);

class CCalendar
{
	const
		CALENDAR_MAX_TIMESTAMP = 2145938400,
		DAY_LENGTH = 86400; // 60 * 60 * 24

	private static
		$CALENDAR_MAX_DATE,
		$type,
		$arTypes,
		$ownerId = 0,
		$settings,
		$siteId,
		$userSettings = array(),
		$pathToUser,
		$bOwner,
		$userId,
		$curUserId,
		$userMeetingSection,
		$meetingSections = array(),
		$offset,
		$arTimezoneOffsets = array(),
		$bCurUser,
		$perm = array(),
		$isArchivedGroup = false,
		$addExternalAttendees = true,
		$userNameTemplate = "#NAME# #LAST_NAME#",
		$bSuperpose,
		$bCanAddToSuperpose,
		$bExtranet,
		$bIntranet,
		$bWebservice,
		$arSPTypes = array(),
		$bTasks = true,
		$actionUrl,
		$path = '',
		$outerUrl,
		$accessNames = array(),
		$bSocNet,
		$bAnonym,
		$allowReserveMeeting = true,
		$SectionsControlsDOMId = 'sidebar',
		$allowVideoMeeting = true,
		$arAccessTask = array(),
		$ownerNames = array(),
		$meetingRoomList,
		$cachePath = "calendar/",
		$cacheTime = 2592000, // 30 days by default
		$bCache = true,
		$bReadOnly,
		$showLogin = true,
		$pathesForSite = false,
		$pathes = array(), // links for several sites
		$userManagers = array(),
		$arUserDepartment = array(),
		$bAMPM = false,
		$bWideDate = false,
		$arExchEnabledCache = array(),
		$silentErrorMode = false,
		$weekStart,
		$bCurUserSocNetAdmin,
		$serverPath,
		$pathesList = array('path_to_user','path_to_user_calendar','path_to_group','path_to_group_calendar','path_to_vr','path_to_rm'),
		$pathesListEx = null,
		$timezones = array();

	public static function Init($Params)
	{
		global $USER;
		$access = new CAccess();
		$access->UpdateCodes();
		if (!$USER || !is_object($USER))
			$USER = new CUser;
		// Owner params
		self::$siteId = isset($Params['siteId']) ? $Params['siteId'] : SITE_ID;
		self::$type = $Params['type'];
		self::$arTypes = CCalendarType::GetList();
		self::$bIntranet = CCalendar::IsIntranetEnabled();
		self::$bSocNet = self::IsSocNet();
		self::$userId = isset($Params['userId']) ? intVal($Params['userId']) : CCalendar::GetCurUserId();
		self::$bOwner = self::$type == 'user' || self::$type == 'group';
		self::$settings = self::GetSettings();
		self::$userSettings = self::GetUserSettings();
		self::$pathesForSite = self::GetPathes(self::$siteId);
		self::$pathToUser = self::$pathesForSite['path_to_user'];
		self::$bSuperpose = $Params['allowSuperpose'] != false && self::$bSocNet;
		self::$bAnonym = !$USER || !$USER->IsAuthorized();
		self::$userNameTemplate = self::$settings['user_name_template'];
		self::$bAMPM = IsAmPmMode();
		self::$bWideDate = strpos(FORMAT_DATETIME, 'MMMM') !== false;


		if (isset($Params['SectionControlsDOMId']))
			self::$SectionsControlsDOMId = $Params['SectionControlsDOMId'];

		if (self::$bOwner && isset($Params['ownerId']) && $Params['ownerId'] > 0)
			self::$ownerId = intVal($Params['ownerId']);

		self::$bTasks = self::$type == 'user' && $Params['showTasks'] !== false && CModule::IncludeModule('tasks');
		if (self::$bTasks && self::$ownerId != self::$userId)
			self::$bTasks = false;

		self::GetPermissions(array(
			'type' => self::$type,
			'bOwner' => self::$bOwner,
			'userId' => self::$userId,
			'ownerId' => self::$ownerId,
		));

		// Cache params
		if (isset($Params['cachePath']))
			self::$cachePath = $Params['cachePath'];
		if (isset($Params['cacheTime']))
			self::$cacheTime = $Params['cacheTime'];
		self::$bCache = self::$cacheTime > 0;

		// Urls
		$page = preg_replace(
			array(
				"/EVENT_ID=.*?\&/i",
				"/CHOOSE_MR=.*?\&/i",
				"/action=.*?\&/i",
				"/bx_event_calendar_request=.*?\&/i",
				"/clear_cache=.*?\&/i",
				"/bitrix_include_areas=.*?\&/i",
				"/bitrix_show_mode=.*?\&/i",
				"/back_url_admin=.*?\&/i"
			),
			"", $Params['pageUrl'].'&'
		);
		$page = preg_replace(array("/^(.*?)\&$/i","/^(.*?)\?$/i"), "\$1", $page);
		self::$actionUrl = $page;

		if (self::$bOwner && !empty(self::$ownerId))
			self::$path = self::GetPath(self::$type, self::$ownerId, true);
		else
			self::$path = CCalendar::GetServerPath().$page;

		self::$outerUrl = $GLOBALS['APPLICATION']->GetCurPageParam('', array("action", "bx_event_calendar_request", "clear_cache", "bitrix_include_areas", "bitrix_show_mode", "back_url_admin", "SEF_APPLICATION_CUR_PAGE_URL", "EVENT_ID", "CHOOSE_MR"), false);

		// Superposing
		self::$bCanAddToSuperpose = false;
		if (self::$bSuperpose)
		{
			if (self::$type == 'user' || self::$type == 'group')
				self::$bCanAddToSuperpose = true;

			foreach(self::$arTypes as $t)
			{
				if (is_array(self::$settings['denied_superpose_types']) && !in_array($t['XML_ID'], self::$settings['denied_superpose_types']))
					self::$arSPTypes[] = $t['XML_ID'];
			}
			self::$bCanAddToSuperpose = (is_array(self::$arSPTypes) && in_array(self::$type, self::$arSPTypes));
		}

		// **** Reserve meeting and reserve video meeting
		// *** Meeting room params ***
		$RMiblockId = self::$settings['rm_iblock_id'];
		self::$allowReserveMeeting = $Params["allowResMeeting"] && $RMiblockId > 0;

		if(self::$allowReserveMeeting && !$USER->IsAdmin() && (CIBlock::GetPermission($RMiblockId) < "R"))
			self::$allowReserveMeeting = false;

		// *** Video meeting room params ***
		$VMiblockId = self::$settings['vr_iblock_id'];
		self::$allowVideoMeeting = $Params["allowVideoMeeting"] && $VMiblockId > 0;
		if((self::$allowVideoMeeting && !$USER->IsAdmin() && (CIBlock::GetPermission($VMiblockId) < "R")) || !CModule::IncludeModule("video"))
			self::$allowVideoMeeting = false;
	}

	static public function Show($Params = array())
	{
		global $APPLICATION, $USER;
		$arType = false;

		foreach(self::$arTypes as $t)
			if (self::$type == $t['XML_ID'])
				$arType = $t;

		if (!$arType)
			return $APPLICATION->ThrowException('[EC_WRONG_TYPE]'.GetMessage('EC_WRONG_TYPE'));

		if (!CCalendarType::CanDo('calendar_type_view', self::$type))
			return $APPLICATION->ThrowException(GetMessage("EC_ACCESS_DENIED"));

		$arStartupEvent = false;
		$showNewEventDialog = false;
		//Show new event dialog
		if (isset($_GET['EVENT_ID']))
		{
			if ($_GET['EVENT_ID'] == 'NEW')
			{
				$showNewEventDialog = true;
			}
			elseif(substr($_GET['EVENT_ID'], 0, 4) == 'EDIT')
			{
				$arStartupEvent = self::GetStartUpEvent(intval(substr($_GET['EVENT_ID'], 4)));
				if ($arStartupEvent)
					$arStartupEvent['EDIT'] = true;
				if ($arStartupEvent['DT_FROM'])
				{
					$ts = self::Timestamp($arStartupEvent['DT_FROM']);
					$init_month = date('m', $ts);
					$init_year = date('Y', $ts);
				}
			}
			// Show popup event at start
			elseif ($arStartupEvent = self::GetStartUpEvent($_GET['EVENT_ID']))
			{
				if ($arStartupEvent['DT_FROM'])
				{
					$ts = self::Timestamp($arStartupEvent['DT_FROM']);
					$init_month = date('m', $ts);
					$init_year = date('Y', $ts);
				}
			}
		}

		if (!$init_month && !$init_year && strlen($Params["initDate"]) > 0 && strpos($Params["initDate"], '.') !== false)
		{
			$ts = self::Timestamp($Params["initDate"]);
			$init_month = date('m', $ts);
			$init_year = date('Y', $ts);
		}

		if (!isset($init_month))
			$init_month = date("m");
		if (!isset($init_year))
			$init_year = date("Y");

		$id = 'EC'.rand();

		$weekHolidays = array();
		if (isset(self::$settings['week_holidays']))
		{
			$days = array('MO' => 0, 'TU' => 1, 'WE' => 2,'TH' => 3,'FR' => 4,'SA' => 5,'SU' => 6);
			foreach(self::$settings['week_holidays'] as $day)
				$weekHolidays[] = $days[$day];
		}
		else
			$weekHolidays = array(5, 6);

		$yearHolidays = array();
		if (isset(self::$settings['year_holidays']))
		{
			foreach(explode(',', self::$settings['year_holidays']) as $date)
			{
				$date = trim($date);
				$ardate = explode('.', $date);
				if (count($ardate) == 2 && $ardate[0] && $ardate[1])
					$yearHolidays[] = intVal($ardate[0]).'.'.(intVal($ardate[1]) - 1);
			}
		}

		$yearWorkdays = array();
		if (isset(self::$settings['year_workdays']))
		{
			foreach(explode(',', self::$settings['year_workdays']) as $date)
			{
				$date = trim($date);
				$ardate = explode('.', $date);
				if (count($ardate) == 2 && $ardate[0] && $ardate[1])
					$yearWorkdays[] = intVal($ardate[0]).'.'.(intVal($ardate[1]) - 1);
			}
		}

		$bSyncPannel = self::IsPersonal();
		$bExchange = CCalendar::IsExchangeEnabled() && self::$type == 'user';
		$bExchangeConnected = $bExchange && CDavExchangeCalendar::IsExchangeEnabledForUser(self::$ownerId);
		$bCalDAV = CCalendar::IsCalDAVEnabled() && self::$type == "user";
		$bWebservice = CCalendar::IsWebserviceEnabled();
		$bExtranet = CCalendar::IsExtranetEnabled();

		self::GetMeetingRoomList(array(
			'RMiblockId' => self::$settings['rm_iblock_id'],
			'pathToMR' => self::$pathesForSite['path_to_rm'],
			'VMiblockId' => self::$settings['vr_iblock_id'],
			'pathToVR' => self::$pathesForSite['path_to_vr']
		));

		$userTimezoneOffsetUTC = self::GetCurrentOffsetUTC(self::$userId);
		$userTimezoneName = self::GetUserTimezoneName(self::$userId);
		$userTimezoneDefault = '';

		// We don't have default timezone for this offset for this user
		// We will ask him but we should suggest some suitable for his offset
		if (!$userTimezoneName)
		{
			$userTimezoneDefault = self::GetGoodTimezoneForOffset($userTimezoneOffsetUTC);
		}

		$JSConfig = Array(
			'id' => $id,
			'type' => self::$type,
			'userId' => self::$userId,
			'userName' => self::GetUserName(self::$userId),
			'ownerId' => self::$ownerId,
			'perm' => $arType['PERM'], // Permissions from type
			'permEx' => self::$perm,
			'bTasks' => self::$bTasks,
			'sectionControlsDOMId' => self::$SectionsControlsDOMId,
			'week_holidays' => $weekHolidays,
			'year_holidays' => $yearHolidays,
			'year_workdays' => $yearWorkdays,
			'week_start' => self::GetWeekStart(),
			'week_days' => CCalendarSceleton::GetWeekDaysEx(self::GetWeekStart()),
			'init_month' => $init_month,
			'init_year' => $init_year,
			'pathToUser' => self::$pathToUser,
			'path' => self::$path,
			'page' => self::$actionUrl,
			'settings' => self::$settings,
			'userSettings' => self::$userSettings,
			'bAnonym' => self::$bAnonym,
			'bIntranet' => self::$bIntranet,
			'bWebservice' => $bWebservice,
			'bExtranet' => $bExtranet,
			'bSocNet' => self::$bSocNet,
			'bExchange' => $bExchangeConnected,
			'startupEvent' => $arStartupEvent,
			'canAddToSuperpose' => self::$bCanAddToSuperpose,
			'workTime' => array(self::$settings['work_time_start'], self::$settings['work_time_end']),
			'meetingRooms' => self::GetMeetingRoomList(array(
				'RMiblockId' => self::$settings['rm_iblock_id'],
				'pathToMR' => self::$pathesForSite['path_to_rm'],
				'VMiblockId' => self::$settings['vr_iblock_id'],
				'pathToVR' => self::$pathesForSite['path_to_vr']
			)),
			'allowResMeeting' => self::$allowReserveMeeting,
			'allowVideoMeeting' => self::$allowVideoMeeting,
			'bAMPM' => self::$bAMPM,
			'bWideDate' => self::$bWideDate,
			'WDControllerCID' => 'UFWD'.$id,
			'userTimezoneOffsetUTC' => $userTimezoneOffsetUTC,
			'userTimezoneName' => $userTimezoneName,
			'userTimezoneDefault' => $userTimezoneDefault
		);

		$JSConfig['lastSection'] = CCalendarSect::GetLastUsedSection(self::$type, self::$ownerId, self::$userId);

		// Access permissons for type
		if (CCalendarType::CanDo('calendar_type_edit_access', self::$type))
			$JSConfig['TYPE_ACCESS'] = $arType['ACCESS'];

		if ($bCalDAV)
			self::InitCalDavParams($JSConfig);

		if ($bSyncPannel)
		{
			$macSyncInfo = self::GetSyncInfo(self::$userId, 'mac');
			$iphoneSyncInfo = self::GetSyncInfo(self::$userId, 'iphone');
			$androidSyncInfo = self::GetSyncInfo(self::$userId, 'android');
			$outlookSyncInfo = self::GetSyncInfo(self::$userId, 'outlook');
			$exchangeSyncInfo = self::GetSyncInfo(self::$userId, 'exchange');

			$syncInfo = array(
				'google' => array(
					'active' => $bCalDAV && ($JSConfig['googleCalDavStatus']['connection_id'] > 0 || $JSConfig['googleCalDavStatus']['authLink']),
					'connected' => $JSConfig['googleCalDavStatus']['connection_id'] > 0,
					'syncDate' => $JSConfig['googleCalDavStatus']['sync_date']
				),
				'macosx' => array(
					'active' => true,
					'connected' => $macSyncInfo['connected'],
					'syncDate' => $macSyncInfo['date'],
				),
				'iphone' => array(
					'active' => true,
					'connected' => $iphoneSyncInfo['connected'],
					'syncDate' => $iphoneSyncInfo['date'],
				),
				'android' => array(
					'active' => true,
					'connected' => $androidSyncInfo['connected'],
					'syncDate' => $androidSyncInfo['date'],
				),
				'outlook' => array(
					'active' => true,
					'connected' => $outlookSyncInfo['connected'],
					'syncDate' => $outlookSyncInfo['date'],
				),
				'office365' => array(
					'active' => false,
					'connected' => false,
					'syncDate' => false
				),
				'exchange' => array(
					'active' => $bExchange,
					'connected' => $bExchangeConnected,
					'syncDate' => $exchangeSyncInfo['date']
				)
			);
		}
		$JSConfig['syncInfo'] = $bSyncPannel ? $syncInfo : false;

		// If enabled superposed sections - fetch it
		$arAddSections = array();
		if (self::$bSuperpose)
			$arAddSections = self::GetDisplayedSuperposed(self::$userId);

		if (!is_array($arAddSections))
			$arAddSections = array();

		$arSectionIds = array();
		$hiddenSections = CCalendarSect::Hidden(self::$userId);

		$arDisplayedSPSections = array();
		$arDisplayedNowSPSections = array();
		foreach($arAddSections as $sect)
		{
			$arDisplayedSPSections[] = $sect;
			if (!in_array($sect, $hiddenSections))
				$arDisplayedNowSPSections[] = $sect;
		}

		self::$userMeetingSection = CCalendar::GetCurUserMeetingSection();

		//  **** GET SECTIONS ****
		$arSections = self::GetSectionList(array(
			'ADDITIONAL_IDS' => $arDisplayedSPSections
		));

		$bReadOnly = !self::$perm['edit'] && !self::$perm['section_edit'];

		if (self::$type == 'user' && self::$ownerId != self::$userId)
			$bReadOnly = true;

		if (self::$bAnonym)
			$bReadOnly = true;

		$bCreateDefault = !self::$bAnonym;

		if (self::$type == 'user')
			$bCreateDefault = self::$ownerId == self::$userId;

		$additonalMeetingsId = array();
		$groupOrUser = self::$type == 'user' || self::$type == 'group';
		if ($groupOrUser)
		{
			$noEditAccessedCalendars = true;
		}

		foreach ($arSections as $i => $section)
		{
			$arSections[$i]['~IS_MEETING_FOR_OWNER'] = $section['CAL_TYPE'] == 'user' && $section['OWNER_ID'] != self::$userId && CCalendar::GetMeetingSection($section['OWNER_ID']) == $section['ID'];

			if (!in_array($section['ID'], $hiddenSections) && $section['ACTIVE'] !== 'N')
			{
				$arSectionIds[] = $section['ID'];
				// It's superposed calendar of the other user and it's need to show user's meetings
				if ($arSections[$i]['~IS_MEETING_FOR_OWNER'])
					$additonalMeetingsId[] = array('ID' => $section['OWNER_ID'], 'SECTION_ID' => $section['ID']);
			}

			// We check access only for main sections because we can't edit superposed section
			if ($groupOrUser && $arSections[$i]['CAL_TYPE'] == self::$type &&
				$arSections[$i]['OWNER_ID'] == self::$ownerId)
			{
				if ($noEditAccessedCalendars && $section['PERM']['edit'])
					$noEditAccessedCalendars = false;

				if ($bReadOnly && ($section['PERM']['edit'] || $section['PERM']['edit_section']) && !self::$isArchivedGroup)
					$bReadOnly = false;
			}

			if (self::$bSuperpose && in_array($section['ID'], $arAddSections))
				$arSections[$i]['SUPERPOSED'] = true;

			if ($bCreateDefault && $section['CAL_TYPE'] == self::$type && $section['OWNER_ID'] == self::$ownerId)
				$bCreateDefault = false;

			if ($arSections[$i]['SUPERPOSED'])
			{
				$type = $arSections[$i]['CAL_TYPE'];
				if ($type == 'user')
				{
					$path = self::$pathesForSite['path_to_user_calendar'];
					$path = CComponentEngine::MakePathFromTemplate($path, array("user_id" => $arSections[$i]['OWNER_ID']));
				}
				elseif($type == 'group')
				{
					$path = self::$pathesForSite['path_to_group_calendar'];
					$path = CComponentEngine::MakePathFromTemplate($path, array("group_id" => $arSections[$i]['OWNER_ID']));
				}
				else
				{
					$path = self::$pathesForSite['path_to_type_'.$type];
				}
				$arSections[$i]['LINK'] = $path;
			}
		}

		if ($groupOrUser && $noEditAccessedCalendars && !$bCreateDefault)
			$bReadOnly = true;

		if (self::$bSuperpose && $bReadOnly && count($arAddSections) <= 0)
			self::$bSuperpose = false;


		self::$bReadOnly = $bReadOnly;
		if (!$bReadOnly && $showNewEventDialog)
		{
			$JSConfig['showNewEventDialog'] = true;
			$JSConfig['bChooseMR'] = isset($_GET['CHOOSE_MR']) && $_GET['CHOOSE_MR'] == "Y";
		}

		if (!in_array($JSConfig['lastSection'], $arSectionIds))
		{
			$JSConfig['lastSection'] = $arSectionIds[0];
		}

		//  **** GET EVENTS ****
		// NOTICE: Attendees for meetings selected inside this method and returns as array by link '$arAttendees'
		$arAttendees = array(); // List of attendees for each event Array([ID] => Array(), ..,);

		$arEvents = self::GetEventList(array(
			'type' => self::$type,
			'section' => $arSectionIds,
			'fromLimit' => self::Date(mktime(0, 0, 0, $init_month - 1, 20, $init_year), false),
			'toLimit' => self::Date(mktime(0, 0, 0, $init_month + 1, 10, $init_year), false),
			'additonalMeetingsId' => $additonalMeetingsId
		), $arAttendees);

		if ($arStartupEvent && is_array($arStartupEvent))
			$arEvents[] = $arStartupEvent;

		if (count($arDisplayedNowSPSections) > 0)
		{
			$arSuperposedEvents = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"FROM_LIMIT" => self::Date(mktime(0, 0, 0, $init_month - 1, 20, $init_year), false),
						"TO_LIMIT" => self::Date(mktime(0, 0, 0, $init_month + 1, 10, $init_year), false),
						"SECTION" => $arDisplayedNowSPSections
					),
					'parseRecursion' => true,
					'fetchAttendees' => true,
					'userId' => self::$userId
				)
			);

			$arEvents = array_merge($arEvents, $arSuperposedEvents);
		}

		$arTaskIds = array();
		//  **** GET TASKS ****
		if (self::$bTasks && !in_array('tasks', $hiddenSections))
		{
			$arTasks = self::GetTaskList(array(
				'fromLimit' => self::Date(mktime(0, 0, 0, $init_month - 1, 20, $init_year), false),
				'toLimit' => self::Date(mktime(0, 0, 0, $init_month + 1, 10, $init_year), false)
			), $arTaskIds);

			if (count($arTasks) > 0)
				$arEvents = array_merge($arEvents, $arTasks);
		}

		// We don't have any section
		if ($bCreateDefault)
		{
			$fullSectionsList = $groupOrUser ? self::GetSectionList(array('checkPermissions' => false)) : array();
			// Section exists but it closed to this user (Ref. mantis:#64037)
			if (count($fullSectionsList) > 0)
			{
				$bReadOnly = true;
			}
			else
			{
				$defCalendar = CCalendarSect::CreateDefault(array(
					'type' => CCalendar::GetType(),
					'ownerId' => CCalendar::GetOwnerId()
				));
				$arSectionIds[] = $defCalendar['ID'];
				$arSections[] = $defCalendar;
				self::$userMeetingSection = $defCalendar['ID'];
			}
		}

		if (CCalendarType::CanDo('calendar_type_edit', self::$type))
			$JSConfig['new_section_access'] = CCalendarSect::GetDefaultAccess(self::$type, self::$ownerId);

		if ($bReadOnly && (!count($arSections) || count($arSections) == 1 && !self::$bIntranet))
			$bShowSections = false;
		else
			$bShowSections = true;

		$colors = array(
			'#DAA187','#78D4F1','#C8CDD3','#43DAD2','#EECE8F','#AEE5EC','#B6A5F6','#F0B1A1','#82DC98','#EE9B9A',
			'#B47153','#2FC7F7','#A7ABB0','#04B4AB','#FFA801','#5CD1DF','#6E54D1','#F73200','#29AD49','#FE5957'
		);

		// Build calendar base html and dialogs
		CCalendarSceleton::Build(array(
			'id' => $id,
			'type' => self::$type,
			'ownerId' => self::$ownerId,
			'bShowSections' => $bShowSections,
			'bShowSuperpose' => self::$bSuperpose,
			'syncPannel' => $bSyncPannel,
			'bOutlook' => self::$bIntranet && self::$bWebservice,
			'bExtranet' => $bExtranet,
			'bReadOnly' => $bReadOnly,
			'bShowTasks' => self::$bTasks,
			'arTaskIds' => $arTaskIds,
			'bSocNet' => self::$bSocNet,
			'bIntranet' => self::$bIntranet,
			'bCalDAV' => $bCalDAV,
			'bExchange' => $bExchange,
			'bExchangeConnected' => $bExchangeConnected,
			'inPersonalCalendar' => self::IsPersonal(),
			'colors' => $colors,
			'bAMPM' => self::$bAMPM,
			'AVATAR_SIZE' => 21,
			'event' => array(),
			'googleCalDavStatus' => $JSConfig['googleCalDavStatus']
		));

		$JSConfig['arCalColors'] = $colors;
		$JSConfig['events'] = $arEvents;
		$JSConfig['sections'] = $arSections;
		$JSConfig['sectionsIds'] = $arSectionIds;
		$JSConfig['hiddenSections'] = $hiddenSections;
		$JSConfig['readOnly'] = $bReadOnly;
		$JSConfig['accessNames'] = self::GetAccessNames();
		$JSConfig['bSuperpose'] = self::$bSuperpose;
		$JSConfig['additonalMeetingsId'] = $additonalMeetingsId;

		// Append Javascript files and CSS files, and some base configs
		CCalendarSceleton::InitJS($JSConfig);
	}

	private static function InitCalDavParams(&$JSConfig)
	{
		global $USER;

		$googleCalDavStatus = array();
		$JSConfig['bCalDAV'] = true;
		$JSConfig['caldav_link_all'] = CCalendar::GetServerPath();
		$tzEnabled = CTimeZone::Enabled();
		if ($tzEnabled)
			CTimeZone::Disable();

		$login = '';
		if (self::$type == 'user')
		{
			if (self::IsPersonal())
			{
				$login = $USER->GetLogin();
			}
			else
			{
				$rsUser = CUser::GetByID(self::$ownerId);
				if($arUser = $rsUser->Fetch())
					$login = $arUser['LOGIN'];
			}

			$JSConfig['caldav_link_one'] = CCalendar::GetServerPath()."/bitrix/groupdav.php/".SITE_ID."/".$login."/calendar/#CALENDAR_ID#/";

			$arConnections = array();
			$res = CDavConnection::GetList(array("ID" => "DESC"), array("ENTITY_TYPE" => "user","ENTITY_ID" => self::$ownerId), false, false);

			if (self::IsPersonal())
			{
				$googleCalDavStatus = CCalendar::GetGoogleCalendarConnection();
			}

			while($arCon = $res->Fetch())
			{
				if ($arCon['ACCOUNT_TYPE'] == 'caldav_google_oauth' || $arCon['ACCOUNT_TYPE'] == 'caldav')
				{
					$arConnections[] = array(
						'id' => $arCon['ID'],
						'server_host' => $arCon['SERVER_HOST'],
						'account_type' => $arCon['ACCOUNT_TYPE'],
						'name' => $arCon['NAME'],
						'link' => $arCon['SERVER'],
						'user_name' => $arCon['SERVER_USERNAME'],
						'last_result' => $arCon['LAST_RESULT'],
						'sync_date' => $arCon['SYNCHRONIZED']
					);
				}
			}

			if (self::IsPersonal())
			{
				if($googleCalDavStatus && $googleCalDavStatus['googleCalendarPrimaryId'])
				{
					$serverPath = 'https://apidata.googleusercontent.com/caldav/v2/'.$googleCalDavStatus['googleCalendarPrimaryId'].'/user';
					$addConnection = true;

					foreach($arConnections as $arCon)
					{
						if ($arCon['link'] == $serverPath)
						{
							$googleCalDavStatus['last_result'] = $arCon['last_result'];
							$googleCalDavStatus['sync_date'] = CCalendar::Date(self::Timestamp($arCon['sync_date']) + CCalendar::GetOffset(self::$ownerId), true, true, true);
							$googleCalDavStatus['connection_id'] = $arCon['id'];

							$addConnection = false;
							break;
						}
					}

					if ($addConnection)
					{
						$conId = CDavConnection::Add(array(
							"ENTITY_TYPE" => 'user',
							"ENTITY_ID" => self::$ownerId,
							"ACCOUNT_TYPE" => 'caldav_google_oauth',
							"NAME" => 'Google Calendar ('.$googleCalDavStatus['googleCalendarPrimaryId'].')',
							"SERVER" => 'https://apidata.googleusercontent.com/caldav/v2/'.$googleCalDavStatus['googleCalendarPrimaryId'].'/user'
						));

						if ($conId)
						{
							CDavGroupdavClientCalendar::DataSync("user", self::$ownerId);
							$res = CDavConnection::GetList(array("ID" => "DESC"), array("ID" => $conId), false, false);
							if($arCon = $res->Fetch())
							{
								$arConnections[] = array(
									'id' => $arCon['ID'],
									'server_host' => $arCon['SERVER_HOST'],
									'account_type' => $arCon['ACCOUNT_TYPE'],
									'name' => $arCon['NAME'],
									'link' => $arCon['SERVER'],
									'user_name' => $arCon['SERVER_USERNAME'],
									'last_result' => $arCon['LAST_RESULT'],
									'sync_date' => $arCon['SYNCHRONIZED']
								);
								$googleCalDavStatus['connection_id'] = $arCon['ID'];
								$googleCalDavStatus['last_result'] = $arCon['LAST_RESULT'];
								$googleCalDavStatus['sync_date'] = CCalendar::Date(self::Timestamp($arCon['SYNCHRONIZED']) + CCalendar::GetOffset(self::$ownerId), true, true, true);
							}
						}
					}
				}
			}

			$JSConfig['googleCalDavStatus'] = $googleCalDavStatus;
			$JSConfig['connections'] = $arConnections;
		}
		else if (self::$type == 'group')
		{
			$JSConfig['caldav_link_one'] = CCalendar::GetServerPath()."/bitrix/groupdav.php/".SITE_ID."/group-".self::$ownerId."/calendar/#CALENDAR_ID#/";
		}

		if ($tzEnabled)
			CTimeZone::Enable();
	}

	public static function GetPermissions($Params = array())
	{
		global $USER;
		$type = isset($Params['type']) ? $Params['type'] : self::$type;
		$ownerId = isset($Params['ownerId']) ? $Params['ownerId'] : self::$ownerId;
		$userId = isset($Params['userId']) ? $Params['userId'] : self::$userId;

		$bView = true;
		$bEdit = true;
		$bEditSection = true;

		if ($type == 'user' && $ownerId != $userId)
		{
			$bEdit = false;
			$bEditSection = false;
		}

		if ($type == 'group')
		{
			if (!$USER->CanDoOperation('edit_php'))
			{
				$keyOwner = 'SG'.$ownerId.'_A';
				$keyMod = 'SG'.$ownerId.'_E';
				$keyMember = 'SG'.$ownerId.'_K';

				$arCodes = array();
				$rCodes = CAccess::GetUserCodes($userId);
				while($code = $rCodes->Fetch())
					$arCodes[] = $code['ACCESS_CODE'];

				if (CModule::IncludeModule("socialnetwork"))
				{
					$group = CSocNetGroup::getByID($ownerId);
					if(!empty($group['CLOSED']) && $group['CLOSED'] === 'Y' &&
						\Bitrix\Main\Config\Option::get('socialnetwork', 'work_with_closed_groups', 'N') === 'N')
					{
						self::$isArchivedGroup = true;
					}
				}

				if (in_array($keyOwner, $arCodes))// Is owner
				{
					$bEdit = true;
					$bEditSection = true;
				}
				elseif(in_array($keyMod, $arCodes) && !self::$isArchivedGroup)// Is moderator
				{
					$bEdit = true;
					$bEditSection = true;
				}
				elseif(in_array($keyMember, $arCodes) && !self::$isArchivedGroup)// Is member
				{
					$bEdit = true;
					$bEditSection = false;
				}
				else
				{
					$bEdit = false;
					$bEditSection = false;
				}
			}
		}

		if ($type != 'user' && $type != 'group')
		{
			$bView = CCalendarType::CanDo('calendar_type_view', $type);
			$bEdit = CCalendarType::CanDo('calendar_type_edit', $type);
			$bEditSection = CCalendarType::CanDo('calendar_type_edit_section', $type);
		}

		if ($Params['setProperties'] !== false)
		{
			self::$perm['view'] = $bView;
			self::$perm['edit'] = $bEdit;
			self::$perm['section_edit'] = $bEditSection;
		}

		return array(
			'view' => $bView,
			'edit' => $bEdit,
			'section_edit' => $bEditSection
		);
	}

	public static function GetSectionList($params = array())
	{
		$type = isset($params['CAL_TYPE']) ? $params['CAL_TYPE'] : self::$type;
		$arFilter = array(
			'CAL_TYPE' => $type,
		);

		if (isset($params['OWNER_ID']))
			$arFilter['OWNER_ID'] = $params['OWNER_ID'];
		elseif ($type == 'user' || $type == 'group')
			$arFilter['OWNER_ID'] = self::GetOwnerId();
		if (isset($params['ACTIVE']))
			$arFilter['ACTIVE'] = $params['ACTIVE'];

		if (isset($params['ADDITIONAL_IDS']) && count($params['ADDITIONAL_IDS']) > 0)
			$arFilter['ADDITIONAL_IDS'] = $params['ADDITIONAL_IDS'];

		$res = CCalendarSect::GetList(
			array(
				'arFilter' => $arFilter,
				'checkPermissions' => $params['checkPermissions']
			)
		);
		return $res;
	}

	public static function GetStartUpEvent($eventId = false)
	{
		if (!$eventId)
			return false;

		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"PARENT_ID" => $eventId,
					"OWNER_ID" => self::$userId,
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

		if (!$res || !is_array($res[0]))
		{
			$res = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"ID" => $eventId,
						"DELETED" => "N"
					),
					'parseRecursion' => false,
					'userId' => self::$userId,
					'fetchAttendees' => false,
					'fetchMeetings' => true
				)
			);
		}

		if ($res && isset($res[0]) && ($event = $res[0]))
		{
			if ($event['MEETING_STATUS'] == 'Y' || $event['MEETING_STATUS'] == 'N' || $event['MEETING_STATUS'] == 'Q')
			{
				if ($event['IS_MEETING'] && self::$userId == self::$ownerId && self::$type == 'user' && ($_GET['CONFIRM'] == 'Y' || $_GET['CONFIRM'] == 'N'))
				{
					CCalendarEvent::SetMeetingStatus(
						self::$userId,
						$event['ID'],
						$_GET['CONFIRM'] == 'Y' ? 'Y' : 'N'
					);
				}
			}

			return $event;
		}
		return false;
	}

	// Used to handle any request from the calendar
	public static function Request($action = '')
	{
		global $APPLICATION;
		if ($_REQUEST['skip_unescape'] !== 'Y')
			CUtil::JSPostUnEscape();

		// Export calendar
		if ($action == 'export')
		{
			// We don't need to check access  couse we will check security SIGN from the URL
			$sectId = intVal($_GET['sec_id']);
			if ($_GET['check'] == 'Y') // Just for access check from calendar interface
			{
				$APPLICATION->RestartBuffer();
				if (CCalendarSect::CheckSign($_GET['sign'], intVal($_GET['user']), $sectId > 0 ? $sectId : 'superposed_calendars'))
					echo 'BEGIN:VCALENDAR';
				CMain::FinalActions();
				die();
			}

			if (CCalendarSect::CheckAuthHash() && $sectId > 0)
			{
				// We don't need any warning in .ics file
				error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);
				CCalendarSect::ReturnICal(array(
					'sectId' => $sectId,
					'userId' => intVal($_GET['user']),
					'sign' => $_GET['sign'],
					'type' => $_GET['type'],
					'ownerId' => intVal($_GET['owner'])
				));
			}
		}
		else
		{
			// // First of all - CHECK ACCESS
			if (!CCalendarType::CanDo('calendar_type_view', self::$type) || !check_bitrix_sessid())
				return $APPLICATION->ThrowException(GetMessage("EC_ACCESS_DENIED"));

			$APPLICATION->ShowAjaxHead();
			$APPLICATION->RestartBuffer();
			$reqId = intVal($_REQUEST['reqId']);

			switch ($action)
			{
				// * * * * * Add and Edit event * * * * *
				case 'edit_event':
					if (self::$bReadOnly || !CCalendarType::CanDo('calendar_type_view', self::$type))
						return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$id = intVal($_POST['id']);
					if (isset($_POST['section']))
					{
						$sectId = intVal($_POST['section']);
						$_POST['sections'] = array($sectId);
					}
					else
					{
						$sectId = intVal($_POST['sections'][0]);
					}

					if (self::$type != 'user' || self::$ownerId != self::$userId) // Personal user's calendar
					{
						if (!$id && !CCalendarSect::CanDo('calendar_add', $sectId, self::$userId))
							return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

						if ($id && !CCalendarSect::CanDo('calendar_edit', $sectId, self::$userId))
							return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
					}

					// Default name for events
					$_POST['name'] = trim($_POST['name']);
					if ($_POST['name'] == '')
						$_POST['name'] = GetMessage('EC_DEFAULT_EVENT_NAME');

					$remind = array();
					if (isset($_POST['remind']['checked']) && $_POST['remind']['checked'] == 'Y')
						$remind[] = array('type' => $_POST['remind']['type'], 'count' => intval($_POST['remind']['count']));

					$rrule = isset($_POST['rrule_enabled']) ? $_POST['rrule'] : false;

					// Date & Time
					$dateFrom = $_POST['date_from'];
					$dateTo = $_POST['date_to'];
					$skipTime = isset($_POST['skip_time']) && $_POST['skip_time'] == 'Y';

					if (!$skipTime)
					{
						$dateFrom .= ' '.$_POST['time_from'];
						$dateTo .= ' '.$_POST['time_to'];
					}

					// Timezone
					$tzFrom = $_POST['tz_from'];
					$tzTo = $_POST['tz_to'];
					if (!$tzFrom && isset($_POST['default_tz']))
					{
						$tzFrom = $_POST['default_tz'];
					}
					if (!$tzTo && isset($_POST['default_tz']))
					{
						$tzTo = $_POST['default_tz'];
					}

					if (isset($_POST['default_tz']) && $_POST['default_tz'] != '')
					{
						self::SaveUserTimezoneName(self::$userId, $_POST['default_tz']);
					}

					$arFields = array(
						"ID" => $id,
						"DATE_FROM" => $dateFrom,
						"DATE_TO" => $dateTo,
						'TZ_FROM' => $tzFrom,
						'TZ_TO' => $tzTo,
						'NAME' => $_POST['name'],
						'DESCRIPTION' => trim($_POST['desc']),
						'SECTIONS' => $_POST['sections'],
						'COLOR' => $_POST['color'],
						'TEXT_COLOR' => $_POST['text_color'],
						'ACCESSIBILITY' => $_POST['accessibility'],
						'IMPORTANCE' => $_POST['importance'],
						'PRIVATE_EVENT' => $_POST['private_event'] == 'Y',
						'RRULE' => $rrule,
						'LOCATION' => is_array($_POST['location']) ? $_POST['location'] : array(),
						"REMIND" => $remind,
						"IS_MEETING" => !!$_POST['is_meeting'],
						"SKIP_TIME" => $skipTime
					);

					$arAccessCodes = array();
					if (isset($_POST['EVENT_DESTINATION']))
					{
						foreach($_POST["EVENT_DESTINATION"] as $v => $k)
						{
							if(strlen($v) > 0 && is_array($k) && !empty($k))
							{
								foreach($k as $vv)
								{
									if(strlen($vv) > 0)
									{
										$arAccessCodes[] = $vv;
									}
								}
							}
						}
						if (!$arFields["ID"])
							$arAccessCodes[] = 'U'.self::$userId;
						$arAccessCodes = array_unique($arAccessCodes);
					}

					$arFields['IS_MEETING'] = !empty($arAccessCodes) && $arAccessCodes != array('U'.self::$userId);
					if ($arFields['IS_MEETING'])
					{
						$arFields['ATTENDEES_CODES'] = $arAccessCodes;
						$arFields['ATTENDEES'] = CCalendar::GetDestinationUsers($arAccessCodes);
						$arFields['MEETING_HOST'] = self::$userId;
						$arFields['MEETING'] = array(
							'HOST_NAME' => self::GetUserName($arFields['MEETING_HOST']),
							'TEXT' => isset($_POST['meeting_text']) ? $_POST['meeting_text'] : '',
							'OPEN' => $_POST['open_meeting'] === 'Y',
							'NOTIFY' => $_POST['meeting_notify'] === 'Y',
							'REINVITE' => $_POST['meeting_reinvite'] === 'Y'
						);
					}

					// Userfields for event
					$arUFFields = array();
					foreach ($_POST as $field => $value)
					{
						if (substr($field, 0, 3) == "UF_")
						{
							$arUFFields[$field] = $value;
						}
					}

					$newId = self::SaveEvent(array('arFields' => $arFields, 'UF' => $arUFFields));
					if ($newId)
					{
						$arFilter = array("ID" => $newId);
						$month = intVal($_REQUEST['month']);
						$year = intVal($_REQUEST['year']);
						$arFilter["FROM_LIMIT"] = self::Date(mktime(0, 0, 0, $month - 1, 20, $year), false);
						$arFilter["TO_LIMIT"] = self::Date(mktime(0, 0, 0, $month + 1, 10, $year), false);

						$arAttendees = array(); // List of attendees for event
						$arEvents = CCalendarEvent::GetList(
							array(
								'arFilter' => $arFilter,
								'parseRecursion' => true,
								'fetchAttendees' => true,
								'userId' => self::$userId
							)
						);

						if ($arFields['IS_MEETING'])
						{
							\Bitrix\Main\FinderDestTable::merge(array(
								"CONTEXT" => "CALENDAR",
								"CODE" => \Bitrix\Main\FinderDestTable::convertRights($arAccessCodes, array('U'.self::$userId))
							));
						}
					}

					if ($arEvents && $arFields['IS_MEETING'])
						$arAttendees = CCalendarEvent::GetLastAttendees();

					CCalendar::OutputJSRes($reqId, array(
						'id' => $newId,
						'events' => $arEvents,
						'attendees' => $arAttendees,
						'deletedEventId' => ($id && $newId != $id) ? $id : 0
					));
					break;

				case 'move_event_to_date':
					if (self::$bReadOnly || !CCalendarType::CanDo('calendar_type_view', self::$type))
						return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$id = intVal($_POST['id']);
					$sectId = intVal($_POST['section']);

					if (self::$type != 'user' || self::$ownerId != self::$userId) // Personal user's calendar
					{
						if (!$id && !CCalendarSect::CanDo('calendar_add', $sectId, self::$userId))
							return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));


						if ($id && !CCalendarSect::CanDo('calendar_edit', $sectId, self::$userId))
							return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
					}

					$skipTime = isset($_POST['skip_time']) && $_POST['skip_time'] == 'Y';
					$arFields = array(
						"ID" => $id,
						"DATE_FROM" => CCalendar::Date(CCalendar::Timestamp($_POST['date_from']), !$skipTime),
						"SKIP_TIME" => $skipTime
					);

					if (isset($_POST['date_to']))
						$arFields["DATE_TO"] = CCalendar::Date(CCalendar::Timestamp($_POST['date_to']), !$skipTime);

					if (!$skipTime && isset($_POST['timezone']) && $_POST['timezone'])
					{
						$arFields["TZ_FROM"] = $_POST['timezone'];
						$arFields["TZ_TO"] = $_POST['timezone'];
					}

					//SaveEvent
					$id = self::SaveEvent(array('arFields' => $arFields));

					CCalendar::OutputJSRes($reqId, array(
						'id' => $id
					));

					break;
				// * * * * * Delete event * * * * *
				case 'delete':
					if (self::$bReadOnly || !CCalendarType::CanDo('calendar_type_view', self::$type))
						return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$res = self::DeleteEvent(intVal($_POST['id']));

					if ($res !== true)
						return CCalendar::ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_EVENT_DEL_ERROR'));

					CCalendar::OutputJSRes($reqId, true);
					break;

				// * * * * * Load events for some time limits * * * * *
				case 'load_events':
					$arSect = array();
					$arHiddenSect = array();
					$month = intVal($_REQUEST['month']);
					$year = intVal($_REQUEST['year']);
					$fromLimit = self::Date(mktime(0, 0, 0, $month - 1, 20, $year), false);
					$toLimit = self::Date(mktime(0, 0, 0, $month + 1, 10, $year), false);
					$connections = false;

					if ($_REQUEST['cal_dav_data_sync'] == 'Y' && CCalendar::IsCalDAVEnabled())
					{
						CDavGroupdavClientCalendar::DataSync("user", self::$ownerId);

						$JSConfig = array();
						self::InitCalDavParams($JSConfig);
						if ($JSConfig['connections'])
							$connections = $JSConfig['connections'];
					}

					$bGetTask = false;
					if (is_array($_REQUEST['active_sect']))
					{
						foreach($_REQUEST['active_sect'] as $sectId)
						{
							if ($sectId == 'tasks')
								$bGetTask = true;
							elseif (intval($sectId) > 0)
								$arSect[] = intval($sectId);
						}
					}

					if (is_array($_REQUEST['hidden_sect']))
					{
						foreach($_REQUEST['hidden_sect'] as $sectId)
						{
							if ($sectId == 'tasks')
								$arHiddenSect[] = 'tasks';
							elseif(intval($sectId) > 0)
								$arHiddenSect[] = intval($sectId);
						}
					}

					$arAttendees = array(); // List of attendees for each event Array([ID] => Array(), ..,);
					$arEvents = array();

					if (count($arSect) > 0)
					{
						// NOTICE: Attendees for meetings selected inside this method and returns as array by link '$arAttendees'
						$arEvents = self::GetEventList(array(
							'type' => self::$type,
							'section' => $arSect,
							'fromLimit' => $fromLimit,
							'toLimit' => $toLimit
						), $arAttendees);
					}

					if (is_array($_REQUEST['sup_sect']))
					{
						$arDisplayedSPSections = array();
						foreach($_REQUEST['sup_sect'] as $sectId)
						{
							$arDisplayedSPSections[] = intval($sectId);
						}

						if (count($arDisplayedSPSections) > 0)
						{
							$arSuperposedEvents = CCalendarEvent::GetList(
								array(
									'arFilter' => array(
										"FROM_LIMIT" => $fromLimit,
										"TO_LIMIT" => $toLimit,
										"SECTION" => $arDisplayedSPSections
									),
									'parseRecursion' => true,
									'fetchAttendees' => true,
									'userId' => self::$userId
								)
							);

							$arEvents = array_merge($arEvents, $arSuperposedEvents);
						}
					}

					//  **** GET TASKS ****
					$arTaskIds = array();
					if (self::$bTasks && $bGetTask)
					{
						$arTasks = self::GetTaskList(array(
							'fromLimit' => $fromLimit,
							'toLimit' => $toLimit
						), $arTaskIds);

						if (count($arTasks) > 0)
							$arEvents = array_merge($arEvents, $arTasks);
					}

					// Save hidden calendars
					CCalendarSect::Hidden(self::$userId, $arHiddenSect);

					CCalendar::OutputJSRes($reqId, array(
						'events' => $arEvents,
						'attendees' => $arAttendees,
						'connections' => $connections
					));
					break;

				// * * * * * Edit calendar * * * * *
				case 'section_edit':
					$id = intVal($_POST['id']);
					$bNew = (!isset($id) || $id == 0);

					if ($bNew) // For new sections
					{
						if (self::$type == 'group')
						{
							// It's for groups
							if (!self::$perm['section_edit'])
								return CCalendar::ThrowError('[se01]'.GetMessage('EC_ACCESS_DENIED'));
						}
						else if (self::$type == 'user')
						{
							if (!self::IsPersonal()) // If it's not owner of the group.
								return CCalendar::ThrowError('[se02]'.GetMessage('EC_ACCESS_DENIED'));
						}
						else // other types
						{
							if (!CCalendarType::CanDo('calendar_type_edit_section', self::$type))
								return CCalendar::ThrowError('[se03]'.GetMessage('EC_ACCESS_DENIED'));
						}
					}
					// For existent sections
					elseif (!self::IsPersonal() && !$bNew && !CCalendarSect::CanDo('calendar_edit_section', $id, self::$userId))
					{
						return CCalendar::ThrowError(GetMessage('[se02]EC_ACCESS_DENIED'));
					}

					$arFields = Array(
						'CAL_TYPE' => self::$type,
						'ID' => $id,
						'NAME' => trim($_POST['name']),
						'DESCRIPTION' => trim($_POST['desc']),
						'COLOR' => $_POST['color'],
						'TEXT_COLOR' => $_POST['text_color'],
						'OWNER_ID' => self::$bOwner ? self::GetOwnerId() : '',
						'EXPORT' => array(
							'ALLOW' => isset($_POST['export']) && $_POST['export'] == 'Y',
							'SET' => $_POST['exp_set']
						),
						'ACCESS' => is_array($_POST['access']) ? $_POST['access'] : array()
					);

					if ($bNew)
						$arFields['IS_EXCHANGE'] = $_POST['is_exchange'] == 'Y';

					$id = intVal(self::SaveSection(
						array(
							'arFields' => $arFields
						)
					));

					if ($id > 0)
					{
						CCalendarSect::SetClearOperationCache(true);
						$oSect = CCalendarSect::GetById($id, true, true);
						if (!$oSect)
							return CCalendar::ThrowError(GetMessage('EC_CALENDAR_SAVE_ERROR'));

						if (self::$type == 'user' && isset($_POST['is_def_meet_calendar']) && $_POST['is_def_meet_calendar'] == 'Y')
						{
							$set = CCalendar::GetUserSettings(self::$ownerId);
							$set['meetSection'] = $id;
							CCalendar::SetUserSettings($set, self::$ownerId);
						}

						CCalendar::OutputJSRes($reqId, array('calendar' => $oSect, 'accessNames' => CCalendar::GetAccessNames()));
					}

					if ($id <= 0)
						return CCalendar::ThrowError(GetMessage('EC_CALENDAR_SAVE_ERROR'));
					break;

				// * * * * * Delete calendar * * * * *
				case 'section_delete':
					$sectId = intVal($_REQUEST['id']);

					if (!self::IsPersonal() && !CCalendarSect::CanDo('calendar_edit_section', $sectId, self::$userId))
						return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

					$res = self::DeleteSection($sectId);
					// if ($res !== true)
					// return CCalendar::ThrowError(strlen($res) > 0 ? $res : GetMessage('EC_CALENDAR_DEL_ERROR'));

					CCalendar::OutputJSRes($reqId, array('result' => true));
					break;

				// * * * * * Delete calendar * * * * *
				case 'section_caldav_hide':
					$sectId = intVal($_REQUEST['id']);

					if (!self::IsPersonal() && !CCalendarSect::CanDo('calendar_edit_section', $sectId, self::$userId))
					{
						return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
					}

					CCalendarSect::Edit(array(
						'arFields' => array(
							"ID" => $sectId,
							"ACTIVE" => "N"
						)
					));

					CCalendar::OutputJSRes($reqId, array('result' => true));
					break;

				// * * * * * Save superposed sections * * * * *
				case 'set_superposed':

					$trackedUser = intVal($_REQUEST['trackedUser']);
					if ($trackedUser > 0)
					{
						$arUserIds = self::TrackingUsers(self::$userId);
						if (!in_array($trackedUser, $arUserIds))
						{
							$arUserIds[] = $trackedUser;
							self::TrackingUsers(self::$userId, $arUserIds);
						}
					}

					if (CCalendar::SetDisplayedSuperposed(self::$userId, $_REQUEST['sect']))
						CCalendar::OutputJSRes($reqId, array('result' => true));
					else
						CCalendar::ThrowError('Error! Cant save displayed superposed calendars');
					break;

				// * * * * * Fetch all available sections for superposing * * * * *
				case 'get_superposed':
					CCalendar::OutputJSRes($reqId, array('sections' => CCalendar::GetSuperposed()));
					break;

				// * * * * * Return info about user, and user calendars * * * * *
				case 'spcal_user_cals':
					CCalendar::OutputJSRes($reqId, array('sections' => CCalendar::GetSuperposedForUsers($_REQUEST['users'])));
					break;

				// * * * * * Delete tracking user * * * * *
				case 'spcal_del_user':
					CCalendar::OutputJSRes($reqId, array('result' => CCalendar::DeleteTrackingUser(intVal($_REQUEST['userId']))));
					break;

				// * * * * * Save user settings * * * * *
				case 'save_settings':
					if (isset($_POST['clear_all']) && $_POST['clear_all'] == true)
					{
						// Clear personal settings
						CCalendar::SetUserSettings(false);
					}
					else
					{
						// Personal
						CCalendar::SetUserSettings($_REQUEST['user_settings']);

						// Save access for type
						if (CCalendarType::CanDo('calendar_type_edit_access', self::$type))
						{
							// General
							$_REQUEST['settings']['week_holidays'] = implode('|',$_REQUEST['settings']['week_holidays']);
							CCalendar::SetSettings($_REQUEST['settings']);
							CCalendarType::Edit(array(
								'arFields' => array(
									'XML_ID' => self::$type,
									'ACCESS' => $_REQUEST['type_access']
								)
							));
						}
					}
					if (isset($_POST['user_timezone_name']))
					{
						self::SaveUserTimezoneName(self::$userId, $_POST['user_timezone_name']);
					}

					CCalendar::OutputJSRes($reqId, array('result' => true));
					break;

				// * * * * * Confirm user part in event * * * * *
				case 'set_meeting_status':
					CCalendarEvent::SetMeetingStatus(
						self::$userId,
						intVal($_REQUEST['event_id']),
						in_array($_REQUEST['status'], array('Q', 'Y', 'N')) ? $_REQUEST['status'] : 'Q',
						$_REQUEST['status_comment']
					);
					CCalendar::OutputJSRes($reqId, true);
					break;
				case 'set_meeting_params':
					CCalendarEvent::SetMeetingParams(
						self::$userId,
						intVal($_REQUEST['event_id']),
						array(
							'ACCESSIBILITY' => $_REQUEST['accessibility'],
							'REMIND' =>  $_REQUEST['remind']
						)
					);
					CCalendar::OutputJSRes($reqId, true);
					break;

				// * * * * * Get list of group members * * * * *
				case 'get_group_members':
					if (self::$type == 'group')
						CCalendar::OutputJSRes($reqId, array('users' => self::GetGroupMembers(self::$ownerId)));

					break;
				// * * * * * Get Guests Accessibility * * * * *
				case 'get_accessibility':
					$res = CCalendar::GetAccessibilityForUsers(array(
						'users' => $_POST['users'],
						'from' => self::Date(self::Timestamp($_POST['from'])),
						'to' => self::Date(self::Timestamp($_POST['to'])),
						'curEventId' => intVal($_POST['cur_event_id']),
						'getFromHR' => true
					));
					CCalendar::OutputJSRes($reqId, array('data' => $res));
					break;

				// * * * * * Get meeting room accessibility * * * * *
				case 'get_mr_accessibility':
					$res = CCalendar::GetAccessibilityForMeetingRoom(array(
						'id' => intVal($_POST['id']),
						'from' => self::Date(self::Timestamp($_POST['from'])),
						'to' => self::Date(self::Timestamp($_POST['to'])),
						'curEventId' => intVal($_POST['cur_event_id'])
					));

					CCalendar::OutputJSRes($reqId, array('data' => $res));
					break;

				// * * * * * Get meeting room accessibility * * * * *
				case 'check_meeting_room':
					$check = false;
					if (self::$allowReserveMeeting || self::$allowVideoMeeting)
					{
						$from = self::Date(self::Timestamp($_POST['from']));
						$to = self::Date(self::Timestamp($_POST['to']));
						$loc_old = $_POST['location_old'] ? CCalendar::ParseLocation(trim($_POST['location_old'])) : false;
						$loc_new = CCalendar::ParseLocation(trim($_POST['location_new']));

						$Params = array(
							'dateFrom' => $from,
							'dateTo' => $to,
							'regularity' => 'NONE',
							'members' => isset($_POST['guest']) ? $_POST['guest'] : false,
						);

//						$tst = MakeTimeStamp($Params['dateTo']);
//						if (date("H:i", $tst) == '00:00')
//							$Params['dateTo'] = CIBlockFormatProperties::DateFormat(self::DFormat(true), $tst + (23 * 60 + 59) * 60);
						if (intVal($_POST['id']) > 0)
							$Params['ID'] = intVal($_POST['id']);

						if (self::$allowVideoMeeting && $loc_new['mrid'] == self::$settings['vr_iblock_id'])
						{
							$Params['VMiblockId'] = self::$settings['vr_iblock_id'];
							if ($loc_old['mrevid'] > 0)
								$Params['ID'] = $loc_old['mrevid'];
							$check = CCalendar::CheckVideoRoom($Params);
						}
						elseif(self::$allowReserveMeeting)
						{
							$Params['RMiblockId'] = self::$settings['rm_iblock_id'];
							$Params['mrid'] = $loc_new['mrid'];
							$Params['mrevid_old'] = $loc_old ? $loc_old['mrevid'] : 0;
							$check = CCalendar::CheckMeetingRoom($Params);
						}
					}

					CCalendar::OutputJSRes($reqId, array('check' => $check));
					break;

				case 'connections_edit':
					if (self::$type == 'user' && CCalendar::IsCalDAVEnabled())
					{
						$res = CCalendar::ManageConnections($_POST['connections']);
						if ($res !== true)
							CCalendar::ThrowError($res == '' ? 'Edit connections error' : $res);
						else
							CCalendar::OutputJSRes($reqId, array('result' => true));
					}
					break;
				case 'disconnect_google':
					if (self::$type == 'user' && CCalendar::IsCalDAVEnabled())
					{
						CCalendar::RemoveConnection(array('id' => intval($_POST['connectionId']), 'del_calendars' => 'Y'));
						CCalendar::OutputJSRes($reqId, array('result' => true));
					}
					break;
				case 'clear_sync_info':
					CCalendar::ClearSyncInfo(self::$userId, $_POST['sync_type']);
					CCalendar::OutputJSRes($reqId, array('result' => true));
					break;
				case 'exchange_sync':
					if (self::$type == 'user' && CCalendar::IsExchangeEnabled(self::$ownerId))
					{
						$error = "";
						$res = CDavExchangeCalendar::DoDataSync(self::$ownerId, $error);
						if ($res === true || $res === false)
							CCalendar::OutputJSRes($reqId, array('result' => true));
						else
							CCalendar::ThrowError($error);
					}
					break;
				case 'get_view_event_dialog':
					$APPLICATION->ShowAjaxHead();

					$jsId = $color = preg_replace('/[^\d|\w]/', '', $_REQUEST['js_id']);
					$eventId = intval($_REQUEST['event_id']);
					$fromTs = CCalendar::Timestamp($_REQUEST['date_from']) - $_REQUEST['date_from_offset'];
					$Event = CCalendarEvent::GetList(
						array(
							'arFilter' => array(
								"ID" => $eventId,
								"DELETED" => "N",
								"FROM_LIMIT" => CCalendar::Date($fromTs),
								"TO_LIMIT" => CCalendar::Date($fromTs)
							),
							'parseRecursion' => true,
							'maxInstanceCount' => 1,
							'preciseLimits' => true,
							'fetchAttendees' => true,
							'checkPermissions' => true,
							'setDefaultLimit' => false
						)
					);

					if (!$Event || !is_array($Event[0]))
					{
						$Event = CCalendarEvent::GetList(
							array(
								'arFilter' => array(
									"ID" => $eventId,
									"DELETED" => "N"
								),
								'parseRecursion' => true,
								'maxInstanceCount' => 1,
								'fetchAttendees' => true,
								'checkPermissions' => true,
								'setDefaultLimit' => false
							)
						);
					}

					if ($Event && is_array($Event[0]))
					{
						CCalendarSceleton::DialogViewEvent(array(
							'id' => $jsId,
							'event' => $Event[0],
							'sectionName' => $_REQUEST['section_name'],
							'bIntranet' => self::IsIntranetEnabled(),
							'bSocNet' => self::IsSocNet(),
							'AVATAR_SIZE' => 21
						));
					}

					require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
					break;

				case 'get_edit_event_dialog':
					$APPLICATION->ShowAjaxHead();

					$jsId = $color = preg_replace('/[^\d|\w]/', '', $_REQUEST['js_id']);
					$event_id = intval($_REQUEST['event_id']);

					if ($event_id > 0)
					{
						$Event = CCalendarEvent::GetList(
							array(
								'arFilter' => array(
									"ID" => $event_id
								),
								'parseRecursion' => false,
								'fetchAttendees' => true,
								'checkPermissions' => true,
								'setDefaultLimit' => false
							)
						);

						$Event = $Event && is_array($Event[0]) ? $Event[0] : false;
					}
					else
					{
						$Event = array();
					}

					if (!$event_id || !empty($Event))
					{
						CCalendarSceleton::DialogEditEvent(array(
							'id' => $jsId,
							'event' => $Event,
							'type' => self::$type,
							'bIntranet' => self::IsIntranetEnabled(),
							'bSocNet' => self::IsSocNet(),
							'AVATAR_SIZE' => 21
						));
					}

					require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
					break;

				case 'update_planner':
					$curEventId = intVal($_REQUEST['cur_event_id']);

					$result = array(
							'users' => array(),
							'entries' => array(),
							'accessibility' => array()
					);
					$userIds = array();
					$curUserId = CCalendar::GetCurUserId();

					if (isset($_REQUEST['codes']) && is_array($_REQUEST['codes']))
					{
						$codes = array();
						foreach($_REQUEST['codes'] as $permCode)
						{
							if($permCode)
								$codes[] = $permCode;
						}

						if(count($codes) > 0)
							$codes[] = 'U'.$curUserId;

						$users = CCalendar::GetDestinationUsers($codes, true);

						foreach($users as $user)
						{
							$userIds[] = $user['USER_ID'];
							$status = '';
							if ($curUserId == $user['USER_ID'])
								$status = 'h';

							$result['entries'][] = array(
									'type' => 'user',
									'id' => $user['USER_ID'],
									'name' => CCalendar::GetUserName($user),
									'status' => $status,
									'url' => CCalendar::GetUserUrl($user['USER_ID'], self::$pathToUser),
									'avatar' => CCalendar::GetUserAvatarSrc($user)
							);
						}

					}
					elseif(isset($_REQUEST['entries']) && is_array($_REQUEST['entries']))
					{
						foreach($_REQUEST['entries'] as $userId)
						{
							$userIds[] = intval($userId);
						}
					}

					$from = self::Date(self::Timestamp($_REQUEST['date_from']), false);
					$to = self::Date(self::Timestamp($_REQUEST['date_to']), false);

					$accessibility = CCalendar::GetAccessibilityForUsers(array(
						'users' => $userIds,
						'from' => $from, // date or datetime in UTC
						'to' => $to, // date or datetime in UTC
						'curEventId' => $curEventId,
						'getFromHR' => true,
						'checkPermissions' => false
					));

					$result['accessibility'] = array();
					$deltaOffset = isset($_REQUEST['timezone']) ? (CCalendar::GetTimezoneOffset($_REQUEST['timezone']) - CCalendar::GetCurrentOffsetUTC($curUserId)) : 0;

					foreach($accessibility as $userId => $entries)
					{
						$result['accessibility'][$userId] = array();

						foreach($entries as $entry)
						{
							if (isset($entry['DT_FROM']) && !isset($entry['DATE_FROM']))
							{
								$result['accessibility'][$userId][] = array(
									'id' => $entry['ID'],
									'dateFrom' => $entry['DT_FROM'],
									'dateTo' => $entry['DT_TO'],
									'type' => $entry['FROM_HR'] ? 'hr' : 'event'
								);
							}
							else
							{
								$fromTs = CCalendar::Timestamp($entry['DATE_FROM']);
								$toTs = CCalendar::Timestamp($entry['DATE_TO']);
								if ($entry['DT_SKIP_TIME'] !== "Y")
								{
									$fromTs -= $entry['~USER_OFFSET_FROM'];
									$toTs -= $entry['~USER_OFFSET_TO'];
									$fromTs += $deltaOffset;
									$toTs += $deltaOffset;
								}
								$result['accessibility'][$userId][] = array(
									'id' => $entry['ID'],
									'dateFrom' => CCalendar::Date($fromTs, $entry['DT_SKIP_TIME'] != 'Y'),
									'dateTo' => CCalendar::Date($toTs, $entry['DT_SKIP_TIME'] != 'Y'),
									'type' => $entry['FROM_HR'] ? 'hr' : 'event'
								);
							}
						}
					}

					$location = CCalendar::ParseLocation(trim($_REQUEST['location']));

					if(self::$allowReserveMeeting && $location['mrid'])
					{
						$mrid = 'MR_'.$location['mrid'];
						$roomEventId = intval($_REQUEST['roomEventId']);
						$entry = array(
							'type' => 'room',
							'id' => $mrid,
							'name' => 'meeting room'
						);

						$roomList = CCalendar::GetMeetingRoomList();
						foreach($roomList as $room)
						{
							if ($room['ID'] == $location['mrid'])
							{
								$entry['name'] = $room['NAME'];
								$entry['url'] = $room['URL'];
								break;
							}
						}

						$result['entries'][] = $entry;
						$result['accessibility'][$mrid] = array();

						$meetingRoomRes = CCalendar::GetAccessibilityForMeetingRoom(array(
							'allowReserveMeeting' => true,
							'id' => $location['mrid'],
							'from' => $from,
							'to' => $to,
							'curEventId' => $roomEventId
						));

						foreach($meetingRoomRes as $entry)
						{
							$result['accessibility'][$mrid][] = array(
								'id' => $entry['ID'],
								'dateFrom' => $entry['DT_FROM'],
								'dateTo' => $entry['DT_TO']
							);
						}
					}

					CCalendar::OutputJSRes($reqId, $result);
					break;
			}
		}

		if($ex = $APPLICATION->GetException())
			ShowError($ex->GetString());

		CMain::FinalActions();
		die();
	}

	public static function GetEventList($Params = array(), &$arAttendees)
	{
		$type = isset($Params['type']) ? $Params['type'] : self::$type;
		$ownerId = isset($Params['ownerId']) ? $Params['ownerId'] : self::$ownerId;
		$userId = isset($Params['userId']) ? $Params['userId'] : self::$userId;

		if ($type != 'user' && !isset($Params['section']) || count($Params['section']) <= 0)
			return array();

		$arFilter = array();

		CCalendarEvent::SetLastAttendees(false);

		if (isset($Params['fromLimit']))
			$arFilter["FROM_LIMIT"] = $Params['fromLimit'];
		if (isset($Params['toLimit']))
			$arFilter["TO_LIMIT"] = $Params['toLimit'];

		$arFilter["OWNER_ID"] = $ownerId;

		if ($type == 'user')
			$fetchMeetings = in_array(self::GetMeetingSection($ownerId), $Params['section']);
		else
			$fetchMeetings = in_array(self::GetCurUserMeetingSection(), $Params['section']);

		$res = CCalendarEvent::GetList(
			array(
				'arFilter' => $arFilter,
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'userId' => $userId,
				'fetchMeetings' => $fetchMeetings
			)
		);

		if (count($Params['section']) > 0)
		{
			$NewRes = array();
			foreach($res as $event)
			{
				if (in_array($event['SECT_ID'], $Params['section']))
					$NewRes[] = $event;
			}
			$res = $NewRes;
		}

		$arAttendees = CCalendarEvent::GetLastAttendees();

		return $res;
	}

	public static function GetTaskList($Params = array(), &$arTaskIds)
	{
		$arFilter = array(
			"DOER" => isset($Params['userId']) ? $Params['userId'] : self::$userId
		);

		// TODO: add filter with OR logic here
		//if (isset($Params['fromLimit']))
		//	$arFilter[">=START_DATE_PLAN"] = $Params['fromLimit'];
		//if (isset($Params['toLimit']))
		//	$arFilter["<=END_DATE_PLAN"] = $Params['toLimit'];

		$tzEnabled = CTimeZone::Enabled();
		if ($tzEnabled)
			CTimeZone::Disable();

		$rsTasks = CTasks::GetList(
			array("START_DATE_PLAN" => "ASC"),
			$arFilter,
			array("ID", "TITLE", "DESCRIPTION", "CREATED_DATE", "DEADLINE", "START_DATE_PLAN", "END_DATE_PLAN", "CLOSED_DATE", "STATUS_CHANGED_DATE", "STATUS", "REAL_STATUS"),
			array()
		);

		$offset = CCalendar::GetOffset();
		$res = array();
		while($task = $rsTasks->Fetch())
		{
			$dtFrom = NULL;
			$dtTo = NULL;
			$arTaskIds[] = $task['ID'];

			$skipFromOffset = false;
			$skipToOffset = false;

			if (isset($task["START_DATE_PLAN"]) && $task["START_DATE_PLAN"])
				$dtFrom = CCalendar::CutZeroTime($task["START_DATE_PLAN"]);

			if (isset($task["END_DATE_PLAN"]) && $task["END_DATE_PLAN"])
				$dtTo = CCalendar::CutZeroTime($task["END_DATE_PLAN"]);

			if (!isset($dtTo) && isset($task["CLOSED_DATE"]))
				$dtTo = CCalendar::CutZeroTime($task["CLOSED_DATE"]);

			//Task statuses: 1 - New, 2 - Pending, 3 - In Progress, 4 - Supposedly completed, 5 - Completed, 6 - Deferred, 7 - Declined
			if (!isset($dtTo) && isset($task["STATUS_CHANGED_DATE"]) && in_array($task["REAL_STATUS"], array('4', '5', '6', '7')))
				$dtTo = CCalendar::CutZeroTime($task["STATUS_CHANGED_DATE"]);

			if (isset($dtTo))
			{
				$ts = CCalendar::Timestamp($dtTo); // Correction display logic for harmony with Tasks interfaces
				if (date("H:i", $ts) == '00:00')
					$dtTo = CCalendar::Date($ts - 24 * 60 *60);
			}
			elseif(isset($task["DEADLINE"]))
			{
				$dtTo = CCalendar::CutZeroTime($task["DEADLINE"]);
				$ts = CCalendar::Timestamp($dtTo); // Correction display logic for harmony with Tasks interfaces
				if (date("H:i", $ts) == '00:00')
					$dtTo = CCalendar::Date($ts - 24 * 60 *60);

				if(!isset($dtFrom))
				{
					$skipFromOffset = true;
					$dtFrom = CCalendar::Date(time(), false);
				}
			}

			if (!isset($dtTo))
				$dtTo = CCalendar::Date(time(), false);

			if (!isset($dtFrom))
				$dtFrom = $dtTo;

			$dtFromTS = CCalendar::Timestamp($dtFrom);
			$dtToTS = CCalendar::Timestamp($dtTo);

			if ($dtToTS < $dtFromTS)
			{
				$dtToTS = $dtFromTS;
				$dtTo = CCalendar::Date($dtToTS, true);
			}

			$skipTime = date("H:i", $dtFromTS) == '00:00' && date("H:i", $dtToTS) == '00:00';
			if (!$skipTime && $offset != 0)
			{
				if (!$skipFromOffset)
				{
					$dtFromTS += $offset;
					$dtFrom = CCalendar::Date($dtFromTS, true);
				}

				if (!$skipToOffset)
				{
					$dtToTS += $offset;
					$dtTo = CCalendar::Date($dtToTS, true);
				}
			}

			$res[] = array(
				"ID" => $task["ID"],
				"~TYPE" => "tasks",
				"NAME" => $task["TITLE"],
				"DATE_FROM" => $dtFrom,
				"DATE_TO" => $dtTo,
				"DT_SKIP_TIME" => $skipTime ? 'Y' : 'N',
				"CAN_EDIT" => CTasks::CanCurrentUserEdit($task)
			);
		}

		if ($tzEnabled)
			CTimeZone::Enable();

		return $res;
	}

	// SUPERPOSE
	public static function GetSuperposed($Params = array())
	{
		$userId = self::$userId;
		$sections = array();
		$arGroupIds = array();
		$arUserIds = array();
		$arGroups = array();

		// *** For social network ***
		if (class_exists('CSocNetUserToGroup'))
		{
			//User's groups
			$arGroups = self::GetUserGroups($userId); // Fetch groups info
			foreach($arGroups as $group)
				$arGroupIds[] = $group['ID'];

			//User's calendars
			$arUserIds = self::TrackingUsers($userId);

			// Add current user
			if (!in_array(self::$userId, $arUserIds))
				$arUserIds[] = $userId;
		}

		// All Available superposed sections
		if (count($arUserIds) > 0 || count($arGroupIds) > 0|| count(self::$arSPTypes) > 0)
		{
			$sections = CCalendarSect::GetSuperposedList(array(
				'USERS' => $arUserIds,
				'GROUPS' => $arGroupIds,
				'TYPES' => self::$arSPTypes,
				'userId' => $userId,
				'checkPermissions' => true,
				'checkSocnetPermissions' => self::$bSocNet,
				'arGroups' => $arGroups // Info about groups
			));
		}

		return $sections;
	}

	public static function GetSuperposedForUsers($arUsers, $userId = false)
	{
		if ($userId === false)
			$userId = self::$userId;

		$arUserIds = self::TrackingUsers($userId);
		$arNewUsers = array();
		if (!is_array($arUsers))
			return false;

		foreach($arUsers as $id)
		{
			$id = intVal($id);
			if ($id <= 0 || in_array($id, $arUserIds) || $id == $userId)
				continue;

			$arNewUsers[] = $id;
			$arUserIds[] = $id;
		}

		// If we add some users for tracking
		if (count($arNewUsers) > 0)
		{
			$sections = CCalendarSect::GetSuperposedList(array(
				'USERS' => $arNewUsers,
				'userId' => self::$userId,
				'checkPermissions' => true,
				'checkSocnetPermissions' => true
			));
			// Save new tracking users
			self::TrackingUsers($userId, $arUserIds);

			if (count($sections))
				return $sections;
		}
		return false;
	}

	public static function GetDisplayedSuperposed($userId = false)
	{
		if (!class_exists('CUserOptions') || !$userId)
			return false;

		$res = array();

		$def = CUserOptions::GetOption("calendar", "superpose_displayed_default", false, $userId);
		$saveOption = false;
		if (intval($def) > 0)
		{
			$saveOption = true;
			$res[] = intVal($def);
		}

		$str = CUserOptions::GetOption("calendar", "superpose_displayed", false, $userId);
		if (CheckSerializedData($str))
		{
			$arIds = unserialize($str);
			if (is_array($arIds) && count($arIds) > 0)
			{
				foreach($arIds as $id)
				{
					if (intVal($id) > 0)
					{
						$res[] = intVal($id);
					}
				}
			}
		}

		if ($saveOption)
		{
			CUserOptions::SetOption("calendar", "superpose_displayed", serialize($res));
			CUserOptions::SetOption("calendar", "superpose_displayed_default", false);
		}

		return $res;
	}

	public static function SetDisplayedSuperposed($userId = false, $arIds = array())
	{
		if (!class_exists('CUserOptions') || !$userId)
			return false;
		$res = array();

		if (is_array($arIds))
		{
			foreach($arIds as $id)
				if (intVal($id) > 0)
					$res[] = intVal($id);
		}
		CUserOptions::SetOption("calendar", "superpose_displayed", serialize($res));

		return true;
	}

	public static function GetUserGroups($userId = 0)
	{
		if (!$userId || !class_exists('CSocNetUserToGroup') || !class_exists('CSocNetFeatures'))
			return;

		$dbGroups = CSocNetUserToGroup::GetList(
			array("GROUP_NAME" => "ASC"),
			array(
				"USER_ID" => $userId,
				"<=ROLE" => SONET_ROLES_USER,
				"GROUP_SITE_ID" => SITE_ID,
				"GROUP_ACTIVE" => "Y"
			),
			false,
			false,
			array("GROUP_ID", "GROUP_NAME")
		);

		$arRes = array();
		if ($dbGroups)
		{
			$arGroupIds = array();
			$arGroups = array();
			while ($g = $dbGroups->GetNext())
			{
				$arGroups[] = $g;
				$arGroupIds[] = $g['GROUP_ID'];
			}
			if (count($arGroupIds) > 0)
			{
				$arFeaturesActive = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arGroupIds, "calendar");
				$arView = CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $arGroupIds, "calendar", 'view');
				$arWrite = CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $arGroupIds, "calendar", 'write');

				foreach($arGroups as $group)
				{
					$groupId = intVal($group['~GROUP_ID']);
					// Calendar is disabled as feature or user can't even view it
					if (!$arFeaturesActive[$groupId] || !$arView[$groupId])
						continue;

					$arRes[$groupId] = array(
						'ID' => $groupId,
						'NAME' => $group['~GROUP_NAME'],
						'READONLY' => !$arWrite[$groupId] // Can't write to group's calendars
					);
				}
			}
		}

		return $arRes;
	}

	public static function TrackingUsers($userId, $arUserIds = false)
	{
		if (!class_exists('CUserOptions') || !$userId)
			return false;

		if ($arUserIds === false) // Get tracking users
		{
			$res = array();
			$str = CUserOptions::GetOption("calendar", "superpose_tracking_users", false, $userId);

			if ($str !== false && CheckSerializedData($str))
			{
				$arIds = unserialize($str);
				if (is_array($arIds) && count($arIds) > 0)
					foreach($arIds as $id)
						if (intVal($id) > 0)
							$res[] = intVal($id);
			}
			return $res;
		}
		else // Set tracking users
		{
			$res = array();
			foreach($arUserIds as $id)
				if (intVal($id) > 0)
					$res[] = intVal($id);
			CUserOptions::SetOption("calendar", "superpose_tracking_users", serialize($res));
			return $res;
		}
	}

	public static function DeleteTrackingUser($userId = false)
	{
		if ($userId === false)
		{
			self::TrackingUsers(self::$userId, array());
			return true;
		}

		$arUserIds = self::TrackingUsers(self::$userId);
		$key = array_search($userId, $arUserIds);
		if ($key === false)
			return false;
		array_splice($arUserIds, $key, 1);
		self::TrackingUsers(self::$userId, $arUserIds);
		return true;
	}

	public static function SaveSection($Params)
	{
		$type = isset($Params['arFields']['CAL_TYPE']) ? $Params['arFields']['CAL_TYPE'] : self::$type;

		// Exchange
		if ($Params['bAffectToDav'] !== false && CCalendar::IsExchangeEnabled(self::$ownerId) && $type == 'user')
		{
			$exchRes = true;
			$ownerId = isset($Params['arFields']['OWNER_ID']) ? $Params['arFields']['OWNER_ID'] : self::$ownerId;

			if(isset($Params['arFields']['ID']) && $Params['arFields']['ID'] > 0)
			{
				// Fetch section
				//$oSect = CCalendarSect::GetById($Params['arFields']['ID']);
				// For exchange we change only calendar name
				//if ($oSect && $oSect['IS_EXCHANGE'] && $oSect['DAV_EXCH_CAL'] && $oSect["NAME"] != $Params['arFields']['NAME'])
				//	$exchRes = CDavExchangeCalendar::DoUpdateCalendar($ownerId, $oSect['DAV_EXCH_CAL'], $oSect['DAV_EXCH_MOD'], $Params['arFields']);
			}
			elseif($Params['arFields']['IS_EXCHANGE'])
			{
				$exchRes = CDavExchangeCalendar::DoAddCalendar($ownerId, $Params['arFields']);
			}

			if ($exchRes !== true)
			{
				if (!is_array($exchRes) || !array_key_exists("XML_ID", $exchRes))
					return CCalendar::ThrowError(CCalendar::CollectExchangeErrors($exchRes));

				// // It's ok, we successfuly save event to exchange calendar - and save it to DB
				$Params['arFields']['DAV_EXCH_CAL'] = $exchRes['XML_ID'];
				$Params['arFields']['DAV_EXCH_MOD'] = $exchRes['MODIFICATION_LABEL'];
			}
		}

		// Save here
		$ID = CCalendarSect::Edit($Params);
		CCalendar::ClearCache(array('section_list', 'event_list'));
		return $ID;
	}

	public static function DeleteSection($ID)
	{
		if (CCalendar::IsExchangeEnabled(self::GetCurUserId()) && self::$type == 'user')
		{
			$oSect = CCalendarSect::GetById($ID);
			// For exchange we change only calendar name
			if ($oSect && $oSect['IS_EXCHANGE'] && $oSect['DAV_EXCH_CAL'])
			{
				$exchRes = CDavExchangeCalendar::DoDeleteCalendar($oSect['OWNER_ID'], $oSect['DAV_EXCH_CAL']);
				if ($exchRes !== true)
					return CCalendar::CollectExchangeErrors($exchRes);
			}
		}

		return CCalendarSect::Delete($ID);
	}

	public static function SaveEvent($Params)
	{
		$arFields = $Params['arFields'];
		if (self::$type && !isset($arFields['CAL_TYPE']))
			$arFields['CAL_TYPE'] = self::$type;
		if (self::$bOwner && !isset($arFields['OWNER_ID']))
			$arFields['OWNER_ID'] = self::$ownerId;

		if (!isset($arFields['SKIP_TIME']) && isset($arFields['DT_SKIP_TIME']))
			$arFields['SKIP_TIME'] = $arFields['DT_SKIP_TIME'] == 'Y';

		$userId = isset($Params['userId']) ? $Params['userId'] : self::GetCurUserId();
		$sectionId = (is_array($arFields['SECTIONS']) && count($arFields['SECTIONS']) > 0) ? $arFields['SECTIONS'][0] : 0;
		$bPersonal = self::IsPersonal($arFields['CAL_TYPE'], $arFields['OWNER_ID'], $userId);

		if (!isset($arFields['DATE_FROM']) &&
			!isset($arFields['DATE_TO']) &&
			isset($arFields['DT_FROM']) &&
			isset($arFields['DT_TO']))
		{
			$arFields['DATE_FROM'] = $arFields['DT_FROM'];
			$arFields['DATE_TO'] = $arFields['DT_TO'];
		}

		// Fetch current event
		$oCurEvent = false;
		$bNew = !isset($arFields['ID']) || !$arFields['ID'];
		if (!$bNew)
		{
			$oCurEvent = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"ID" => intVal($arFields['ID']),
						"DELETED" => "N"
					),
					'parseRecursion' => false,
					'fetchAttendees' => $Params['bSilentAccessMeeting'] === true,
					'fetchMeetings' => false,
					'userId' => $userId
				)
			);
			if ($oCurEvent)
				$oCurEvent = $oCurEvent[0];

			$bPersonal = $bPersonal && self::IsPersonal($oCurEvent['CAL_TYPE'], $oCurEvent['OWNER_ID'], $userId);

			$arFields['CAL_TYPE'] = $oCurEvent['CAL_TYPE'];
			$arFields['OWNER_ID'] = $oCurEvent['OWNER_ID'];
			$arFields['CREATED_BY'] = $oCurEvent['CREATED_BY'];
			$arFields['ACTIVE'] = $oCurEvent['ACTIVE'];

			$bChangeMeeting = $arFields['CAL_TYPE'] != 'user' && CCalendarSect::CanDo('calendar_edit', $oCurEvent['SECT_ID'], self::$userId);

			if (!isset($arFields['NAME']))
				$arFields['NAME'] = $oCurEvent['NAME'];
			if (!isset($arFields['DESCRIPTION']))
				$arFields['DESCRIPTION'] = $oCurEvent['DESCRIPTION'];
			if (!isset($arFields['COLOR']) && $oCurEvent['COLOR'])
				$arFields['COLOR'] = $oCurEvent['COLOR'];
			if (!isset($arFields['TEXT_COLOR']) && $oCurEvent['TEXT_COLOR'])
				$arFields['TEXT_COLOR'] = $oCurEvent['TEXT_COLOR'];
			if (!isset($arFields['SECTIONS']))
			{
				$arFields['SECTIONS'] = array($oCurEvent['SECT_ID']);
				$sectionId = (is_array($arFields['SECTIONS']) && count($arFields['SECTIONS']) > 0) ? $arFields['SECTIONS'][0] : 0;
			}
			if (!isset($arFields['IS_MEETING']))
				$arFields['IS_MEETING'] = $oCurEvent['IS_MEETING'];
			if (!isset($arFields['ACTIVE']))
				$arFields['ACTIVE'] = $oCurEvent['ACTIVE'];
			if (!isset($arFields['PRIVATE_EVENT']))
				$arFields['PRIVATE_EVENT'] = $oCurEvent['PRIVATE_EVENT'];
			if (!isset($arFields['ACCESSIBILITY']))
				$arFields['ACCESSIBILITY'] = $oCurEvent['ACCESSIBILITY'];
			if (!isset($arFields['IMPORTANCE']))
				$arFields['IMPORTANCE'] = $oCurEvent['IMPORTANCE'];

			if (!isset($arFields['LOCATION']) && $oCurEvent['LOCATION'] != "")
			{
				$arFields['LOCATION'] = Array(
					"OLD" => $oCurEvent['LOCATION'],
					"NEW" => $oCurEvent['LOCATION']
				);
			}

			if (!$bPersonal && !$bChangeMeeting)
			{
				$arFields['IS_MEETING'] = $oCurEvent['IS_MEETING'];
				if ($arFields['IS_MEETING'])
					$arFields['SECTIONS'] = array($oCurEvent['SECT_ID']);
			}

			if ($oCurEvent['IS_MEETING'])
			{
				$arFields['MEETING_HOST'] = $oCurEvent['MEETING_HOST'];
			}

			// If it's attendee and but modifying called from CalDav methods
			if ($Params['bSilentAccessMeeting'] && $oCurEvent['IS_MEETING'] && $oCurEvent['PARENT_ID'] != $oCurEvent['ID'])
			{
				return true; // CalDav will return 204
			}

			if (!$bPersonal && !CCalendarSect::CanDo('calendar_edit', $oCurEvent['SECT_ID'], self::$userId))
			{
				return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
			}

			if (!isset($arFields["RRULE"]) && $oCurEvent["RRULE"] != '' && $Params['fromWebservice'] !== true)
				$arFields["RRULE"] = CCalendarEvent::ParseRRULE($oCurEvent["RRULE"]);

			if ($Params['fromWebservice'] === true)
			{
				if ($arFields["RRULE"] == -1 && CCalendarEvent::CheckRecurcion($oCurEvent))
					$arFields["RRULE"] = CCalendarEvent::ParseRRULE($oCurEvent['RRULE']);
			}

			if ($oCurEvent)
				$Params['currentEvent'] = $oCurEvent;

			if (!$bPersonal && !CCalendarSect::CanDo('calendar_edit', $oCurEvent['SECT_ID'], self::$userId))
				return GetMessage('EC_ACCESS_DENIED');
		}
		elseif ($sectionId > 0 && !$bPersonal && !CCalendarSect::CanDo('calendar_add', $sectionId, self::$userId))
		{
			return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
		}

		if ($Params['autoDetectSection'] && $sectionId <= 0)
		{
			$sectionId = false;
			if ($arFields['CAL_TYPE'] == 'user')
			{
				$sectionId = CCalendarSect::GetLastUsedSection('user', $arFields['OWNER_ID'], $userId);
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

				if ($sectionId)
					$arFields['SECTIONS'] = array($sectionId);
			}

			if (!$sectionId)
			{
				$sectRes = self::GetSectionForOwner($arFields['CAL_TYPE'], $arFields['OWNER_ID'], $Params['autoCreateSection']);
				if ($sectRes['sectionId'] > 0)
				{
					$arFields['SECTIONS'] = array($sectRes['sectionId']);
					if ($sectRes['autoCreated'])
						$Params['bAffectToDav'] = false;
				}
				else
				{
					return false;
				}
			}
		}

		if (isset($arFields["RRULE"]))
			$arFields["RRULE"] = CCalendarEvent::CheckRRULE($arFields["RRULE"]);

		// Set version
		if (!isset($arFields['VERSION']) || $arFields['VERSION'] <= $oCurEvent['VERSION'])
			$arFields['VERSION'] = $oCurEvent['VERSION'] ? $oCurEvent['VERSION'] + 1 : 1;

		if ($Params['autoDetectSection'] && $sectionId <= 0 && $arFields['OWNER_ID'] > 0)
		{
			$res = CCalendarSect::GetList(array('arFilter' => array('CAL_TYPE' => $arFields['CAL_TYPE'],'OWNER_ID' => $arFields['OWNER_ID']), 'checkPermissions' => false));
			if ($res && is_array($res) && isset($res[0]))
			{
				$sectionId = $res[0]['ID'];
			}
			elseif ($Params['autoCreateSection'])
			{
				$defCalendar = CCalendarSect::CreateDefault(array(
					'type' => $arFields['CAL_TYPE'],
					'ownerId' => $arFields['OWNER_ID']
				));
				$sectionId = $defCalendar['ID'];

				$Params['bAffectToDav'] = false;
			}
			if ($sectionId > 0)
				$arFields['SECTIONS'] = array($sectionId);
			else
				return false;
		}

		$bExchange = CCalendar::IsExchangeEnabled() && $arFields['CAL_TYPE'] == 'user';
		$bCalDav = CCalendar::IsCalDAVEnabled() && $arFields['CAL_TYPE'] == 'user';

		if ($Params['bAffectToDav'] !== false && ($bExchange || $bCalDav) && $sectionId > 0)
		{
			$res = CCalendar::DoSaveToDav(array(
				'bCalDav' => $bCalDav,
				'bExchange' => $bExchange,
				'sectionId' => $sectionId
			), $arFields, $oCurEvent);
			if ($res !== true)
				return CCalendar::ThrowError($res);
		}

		$Params['arFields'] = $arFields;
		$Params['userId'] = $userId;

		if (self::$ownerId != $arFields['OWNER_ID'] && self::$type != $arFields['CAL_TYPE'])
			$Params['path'] = self::GetPath($arFields['CAL_TYPE'], $arFields['OWNER_ID'], 1);
		else
			$Params['path'] = self::$path;

		$id = CCalendarEvent::Edit($Params);

		$UFs = $Params['UF'];
		if (isset($UFs) && count($UFs) > 0)
		{
			CCalendarEvent::UpdateUserFields($id, $UFs);

			if ($arFields['IS_MEETING'])
			{
				if (!empty($UFs['UF_WEBDAV_CAL_EVENT']))
				{
					$UF = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("CALENDAR_EVENT", $id, LANGUAGE_ID);
					CCalendar::UpdateUFRights($UFs['UF_WEBDAV_CAL_EVENT'], $arFields['ATTENDEES_CODES'], $UF['UF_WEBDAV_CAL_EVENT']);
				}
			}
		}

		$arFields['ID'] = $id;
		foreach(GetModuleEvents("calendar", "OnAfterCalendarEventEdit", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array('arFields' => $arFields,'bNew' => $bNew,'userId' => $userId));

		return $id;
	}

	public static function DeleteEvent($ID, $bSyncDAV = true)
	{
		$ID = intVal($ID);
		if (!$ID)
			return false;

		if (!isset(self::$userId))
			self::$userId = CCalendar::GetCurUserId();

		CCalendar::SetOffset(false, 0);
		$res = CCalendarEvent::GetList(
			array('arFilter' => array("ID" => $ID),'parseRecursion' => false, 'setDefaultLimit' => false)
		);

		if ($Event = $res[0])
		{
			if (!isset(self::$type))
				self::$type = $Event['CAL_TYPE'];

			if (!isset(self::$ownerId))
				self::$ownerId = $Event['OWNER_ID'];

			if (!self::IsPersonal($Event['CAL_TYPE'], $Event['OWNER_ID'], self::$userId) && !CCalendarSect::CanDo('calendar_edit', $Event['SECT_ID'], self::$userId))
				return GetMessage('EC_ACCESS_DENIED');

			if ($bSyncDAV !== false && $Event['SECT_ID'])
			{
				$bCalDav = CCalendar::IsCalDAVEnabled() && $Event['CAL_TYPE'] == 'user' && strlen($Event['CAL_DAV_LABEL']) > 0;
				$bExchangeEnabled = CCalendar::IsExchangeEnabled() && $Event['CAL_TYPE'] == 'user';

				if ($bExchangeEnabled || $bCalDav)
				{
					$res = CCalendar::DoDeleteToDav(array(
						'bCalDav' => $bCalDav,
						'bExchangeEnabled' => $bExchangeEnabled,
						'sectionId' => $Event['SECT_ID']
					), $Event);

					if ($res !== true)
						return $res;
				}
			}

			$res = CCalendarEvent::Delete(array(
				'id' => $ID,
				'Event' => $Event,
				'bMarkDeleted' => true,
				'userId' => self::$userId
			));

			return $res;
		}

		return false;
	}

	public static function TrimTime($strTime)
	{
		$strTime = trim($strTime);
		$strTime = preg_replace("/:00$/", "", $strTime);
		$strTime = preg_replace("/:00$/", "", $strTime);
		$strTime = preg_replace("/\\s00$/", "", $strTime);
		return rtrim($strTime);
	}

	public static function SendMessage($Params)
	{
		if (!CModule::IncludeModule("im"))
			return false;

		$mode = $Params['mode']; // invite|change|cancel|accept|decline
		$Params["meetingText"] = (isset($Params["meetingText"]) && is_string($Params["meetingText"])) ? trim($Params["meetingText"]) : '';
		$fromUser = intVal($Params["userId"]);
		$toUser = intVal($Params["guestId"]);
		if (!$fromUser || !$toUser || ($toUser == $fromUser && $mode !== 'status_accept' && $mode !== 'status_decline'))
			return false;

		$arNotifyFields = array(
			'EMAIL_TEMPLATE' => "CALENDAR_INVITATION",
			'NOTIFY_MODULE' => "calendar",
		);

		if ($mode == 'accept' || $mode == 'decline')
		{
			$arNotifyFields['FROM_USER_ID'] = $toUser;
			$arNotifyFields['TO_USER_ID'] = $fromUser;
		}
		else
		{
			$arNotifyFields['FROM_USER_ID'] = $fromUser;
			$arNotifyFields['TO_USER_ID'] = $toUser;
		}

		$rs = CUser::GetList(($by="id"), ($order="asc"), array("ID_EQUAL_EXACT"=>$toUser, "ACTIVE" => "Y"));
		if (!$rs->Fetch())
			return false;

		$eventId = intVal($Params["eventId"]);
		$calendarUrl = self::GetPathForCalendarEx($arNotifyFields['TO_USER_ID']);
		$calendarUrlEV = $calendarUrl.((strpos($calendarUrl, "?") === false) ? '?' : '&').'EVENT_ID='.$eventId;

		$curPath = CCalendar::GetPath();
		if ($curPath && $eventId)
		{
			$curPath = CHTTP::urlDeleteParams($curPath, array("action", "sessid", "bx_event_calendar_request", "EVENT_ID"));
			$curPath = CHTTP::urlAddParams($curPath, array('EVENT_ID' => $eventId));
		}

		$arNotifyFields = array(
			'FROM_USER_ID' => $fromUser,
			'TO_USER_ID' => $toUser,
			'EMAIL_TEMPLATE' => "CALENDAR_INVITATION",
			'NOTIFY_MODULE' => "calendar",
		);

		switch($mode)
		{
			case 'invite':
				$arNotifyFields['NOTIFY_EVENT'] = "invite";
				$arNotifyFields['NOTIFY_TYPE'] = IM_NOTIFY_CONFIRM;
				$arNotifyFields['NOTIFY_TAG'] = "CALENDAR|INVITE|".$eventId."|".$toUser;
				$arNotifyFields['NOTIFY_SUB_TAG'] = "CALENDAR|INVITE|".$eventId;
				$arNotifyFields['MESSAGE'] = GetMessage('EC_MESS_INVITE_SITE',
					array(
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"])
				);

				$arNotifyFields['MESSAGE_OUT'] = GetMessage('EC_MESS_INVITE',
					array(
						'#OWNER_NAME#' => CCalendar::GetUserName($Params['userId']),
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"])
				);

				if ($Params['location'] != "")
				{
					$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_LOCATION').': '.$Params['location'];
					$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_LOCATION').': '.$Params['location'];
				}

				if ($Params["meetingText"] != "")
				{
					$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array(
							'#MEETING_TEXT#' => $Params["meetingText"]
						));
					$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array(
							'#MEETING_TEXT#' => $Params["meetingText"]
						));
				}

				$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS_SITE', array('#LINK#' => $calendarUrlEV));

				$arNotifyFields['NOTIFY_BUTTONS'] = Array(
					Array('TITLE' => GetMessage('EC_MESS_INVITE_CONF_Y_SITE'), 'VALUE' => 'Y', 'TYPE' => 'accept'),
					Array('TITLE' => GetMessage('EC_MESS_INVITE_CONF_N_SITE'), 'VALUE' => 'N', 'TYPE' => 'cancel')
				);

				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_CONF_Y', array('#LINK#' => $calendarUrlEV.'&CONFIRM=Y'));
				$arNotifyFields['MESSAGE_OUT'] .= "\n".GetMessage('EC_MESS_INVITE_CONF_N', array('#LINK#' => $calendarUrlEV.'&CONFIRM=N'));
				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS', array('#LINK#' => $calendarUrlEV));

				$arNotifyFields['TITLE'] = GetMessage('EC_MESS_INVITE_TITLE',
					array(
						'#OWNER_NAME#' => CCalendar::GetUserName($Params['userId']),
						'#TITLE#' => $Params["name"]
					)
				);
				break;
			case 'change':
				$arNotifyFields['NOTIFY_EVENT'] = "change";
				$arNotifyFields['NOTIFY_TYPE'] = IM_NOTIFY_CONFIRM;
				$arNotifyFields['NOTIFY_TAG'] = "CALENDAR|INVITE|".$eventId."|".$toUser;
				$arNotifyFields['NOTIFY_SUB_TAG'] = "CALENDAR|INVITE|".$eventId;

				$arNotifyFields['MESSAGE'] = GetMessage('EC_MESS_INVITE_CHANGED_SITE',
					array(
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				$arNotifyFields['MESSAGE_OUT'] = GetMessage('EC_MESS_INVITE_CHANGED',
					array(
						'#OWNER_NAME#' => CCalendar::GetUserName($Params['userId']),
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				if ($Params["meetingText"] != "")
				{
					$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array(
							'#MEETING_TEXT#' => $Params["meetingText"]
						));
					$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array(
							'#MEETING_TEXT#' => $Params["meetingText"]
						));
				}

				$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS_SITE', array('#LINK#' => $calendarUrlEV));
				$arNotifyFields['NOTIFY_BUTTONS'] = Array(
					Array('TITLE' => GetMessage('EC_MESS_INVITE_CONF_Y_SITE'), 'VALUE' => 'Y', 'TYPE' => 'accept'),
					Array('TITLE' => GetMessage('EC_MESS_INVITE_CONF_N_SITE'), 'VALUE' => 'N', 'TYPE' => 'cancel')
				);

				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_CONF_Y', array('#LINK#' => $calendarUrlEV.'&CONFIRM=Y'));
				$arNotifyFields['MESSAGE_OUT'] .= "\n".GetMessage('EC_MESS_INVITE_CONF_N', array('#LINK#' => $calendarUrlEV.'&CONFIRM=N'));
				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS', array('#LINK#' => $calendarUrlEV));

				$arNotifyFields['TITLE'] = GetMessage('EC_MESS_INVITE_CHANGED_TITLE',array('#TITLE#' => $Params["name"]));

				break;
			case 'change_notify':
				$arNotifyFields['NOTIFY_EVENT'] = "change";
				$arNotifyFields['NOTIFY_TAG'] = "CALENDAR|INVITE|".$eventId."|".$toUser;
				$arNotifyFields['NOTIFY_SUB_TAG'] = "CALENDAR|INVITE|".$eventId;

				$arNotifyFields['MESSAGE'] = GetMessage('EC_MESS_INVITE_CHANGED_SITE',
					array(
						'#TITLE#' => "[url=".$calendarUrlEV."]".$Params["name"]."[/url]",
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				$arNotifyFields['MESSAGE_OUT'] = GetMessage('EC_MESS_INVITE_CHANGED',
					array(
						'#OWNER_NAME#' => CCalendar::GetUserName($Params['userId']),
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				if ($Params["meetingText"] != "")
				{
					$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array(
							'#MEETING_TEXT#' => $Params["meetingText"]
						));
					$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_MEETING_TEXT', array(
							'#MEETING_TEXT#' => $Params["meetingText"]
						));
				}

				$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS_SITE', array('#LINK#' => $calendarUrlEV));
				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS', array('#LINK#' => $calendarUrlEV));

				$arNotifyFields['TITLE'] = GetMessage('EC_MESS_INVITE_CHANGED_TITLE',array('#TITLE#' => $Params["name"]));

				break;
			case 'cancel':
				$arNotifyFields['NOTIFY_EVENT'] = "change";
				$arNotifyFields['NOTIFY_TAG'] = "CALENDAR|INVITE|".$eventId."|".$toUser."|cancel";
				$arNotifyFields['NOTIFY_SUB_TAG'] = "CALENDAR|INVITE|".$eventId;

				$arNotifyFields['MESSAGE'] = GetMessage('EC_MESS_INVITE_CANCEL_SITE', array(
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);
				$arNotifyFields['MESSAGE_OUT'] = GetMessage('EC_MESS_INVITE_CANCEL', array(
						'#OWNER_NAME#' => CCalendar::GetUserName($Params['userId']),
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);
				$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_VIEW_OWN_CALENDAR', array('#LINK#' => $calendarUrl));
				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_VIEW_OWN_CALENDAR_OUT', array('#LINK#' => $calendarUrl));
				$arNotifyFields['TITLE'] = GetMessage('EC_MESS_INVITE_CANCEL_TITLE', array('#TITLE#' => $Params["name"]));
				break;
			case 'accept':
			case 'decline':
				$arNotifyFields['NOTIFY_EVENT'] = "info";
				$arNotifyFields['FROM_USER_ID'] = intVal($Params["guestId"]);
				$arNotifyFields['TO_USER_ID'] = intVal($Params["userId"]);
				$arNotifyFields['NOTIFY_TAG'] = "CALENDAR|INVITE|".$eventId."|".$mode;
				$arNotifyFields['NOTIFY_SUB_TAG'] = "CALENDAR|INVITE|".$eventId;

				$arNotifyFields['MESSAGE'] = GetMessage($mode=='accept' ? 'EC_MESS_INVITE_ACCEPTED_SITE' : 'EC_MESS_INVITE_DECLINED_SITE',
					array(
						'#TITLE#' => "[url=".$calendarUrlEV."]".$Params["name"]."[/url]",
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				$arNotifyFields['MESSAGE_OUT'] = GetMessage($mode=='accept' ? 'EC_MESS_INVITE_ACCEPTED' : 'EC_MESS_INVITE_DECLINED',
					array(
						'#GUEST_NAME#' => CCalendar::GetUserName($Params['guestId']),
						'#TITLE#' => $Params["name"],
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				if ($Params["comment"] != "")
				{
					$arNotifyFields['MESSAGE'] .= "\n\n".GetMessage('EC_MESS_INVITE_ACC_COMMENT', array(
							'#COMMENT#' => $Params["comment"]
						));
					$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_ACC_COMMENT', array(
							'#COMMENT#' => $Params["comment"]
						));
				}
				$arNotifyFields['MESSAGE_OUT'] .= "\n\n".GetMessage('EC_MESS_INVITE_DETAILS', array('#LINK#' => $calendarUrlEV));

				break;

			case 'status_accept':
			case 'status_decline':
				$arNotifyFields['NOTIFY_EVENT'] = "info";
				$arNotifyFields['FROM_USER_ID'] = intVal($Params["guestId"]);
				$arNotifyFields['TO_USER_ID'] = intVal($Params["userId"]);
				$arNotifyFields['NOTIFY_TAG'] = "CALENDAR|STATUS|".$eventId."|".intVal($Params["userId"]);
				$arNotifyFields['NOTIFY_SUB_TAG'] = "CALENDAR|STATUS|".$eventId;

				$arNotifyFields['MESSAGE'] = GetMessage($mode =='status_accept' ? 'EC_MESS_STATUS_NOTIFY_Y_SITE' : 'EC_MESS_STATUS_NOTIFY_N_SITE',
					array(
						'#TITLE#' => "[url=".$calendarUrlEV."]".$Params["name"]."[/url]",
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);

				$arNotifyFields['MESSAGE_OUT'] = GetMessage($mode == 'status_accept' ? 'EC_MESS_STATUS_NOTIFY_Y' : 'EC_MESS_STATUS_NOTIFY_N',
					array(
						'#TITLE#' => "[url=".$calendarUrlEV."]".$Params["name"]."[/url]",
						'#ACTIVE_FROM#' => $Params["from"]
					)
				);
				break;
		}

		$messageId = CIMNotify::Add($arNotifyFields);
		if ($Params['markRead'] && $messageId > 0)
		{
			$CIMNotify = new CIMNotify(intVal($Params["userId"]));
			$CIMNotify->MarkNotifyRead($messageId);
		}

		foreach(GetModuleEvents("calendar", "OnSendInvitationMessage", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($Params));
	}

	public static function HandleImCallback($module, $tag, $value, $arNotify)
	{
		$userId = CCalendar::GetCurUserId();
		if ($module == "calendar" && $userId)
		{
			$arTag = explode("|", $tag);
			$eventId = intVal($arTag[2]);
			if ($arTag[0] == "CALENDAR" && $arTag[1] == "INVITE" && $eventId > 0 && $userId)
			{
				CCalendarEvent::SetMeetingStatus(
					$userId,
					$eventId,
					$value == 'Y' ? 'Y' : 'N'
				);

				return $value == 'Y' ? GetMessage('EC_PROP_CONFIRMED_TEXT_Y') : GetMessage('EC_PROP_CONFIRMED_TEXT_N');
			}
		}
	}

	public static function GetSettings($Params = array())
	{
		if (!is_array($Params))
			$Params = array();
		if (isset(self::$settings) && count(self::$settings) > 0 && $Params['request'] === false)
			return self::$settings;

		$pathes_for_sites = COption::GetOptionString('calendar', 'pathes_for_sites', true);
		if ($Params['forseGetSitePathes'] || !$pathes_for_sites)
			$pathes = self::GetPathes(isset($Params['site']) ? $Params['site'] : false);
		else
			$pathes = array();

		if (!isset($Params['getDefaultForEmpty']) || $Params['getDefaultForEmpty'] !== false)
			$Params['getDefaultForEmpty'] = true;

		self::$settings = array(
			'work_time_start' => COption::GetOptionString('calendar', 'work_time_start', 9),
			'work_time_end' => COption::GetOptionString('calendar', 'work_time_end', 19),
			'year_holidays' => COption::GetOptionString('calendar', 'year_holidays', '1.01,2.01,7.01,23.02,8.03,1.05,9.05,12.06,4.11,12.12'),
			'year_workdays' => COption::GetOptionString('calendar', 'year_workdays', ''),
			'week_holidays' => explode('|', COption::GetOptionString('calendar', 'week_holidays', 'SA|SU')),
			'week_start' => COption::GetOptionString('calendar', 'week_start', 'MO'),
			'user_name_template' => self::GetUserNameTemplate($Params['getDefaultForEmpty']),
			'user_show_login' => COption::GetOptionString('calendar', 'user_show_login', true),
			'path_to_user' => COption::GetOptionString('calendar', 'path_to_user', "/company/personal/user/#user_id#/"),
			'path_to_user_calendar' => COption::GetOptionString('calendar', 'path_to_user_calendar', "/company/personal/user/#user_id#/calendar/"),
			'path_to_group' => COption::GetOptionString('calendar', 'path_to_group', "/workgroups/group/#group_id#/"),
			'path_to_group_calendar' => COption::GetOptionString('calendar', 'path_to_group_calendar', "/workgroups/group/#group_id#/calendar/"),
			'path_to_vr' => COption::GetOptionString('calendar', 'path_to_vr', ""),
			'path_to_rm' => COption::GetOptionString('calendar', 'path_to_rm', ""),
			'rm_iblock_type' => COption::GetOptionString('calendar', 'rm_iblock_type', ""),
			'rm_iblock_id' => COption::GetOptionString('calendar', 'rm_iblock_id', ""),
			'vr_iblock_id' => COption::GetOptionString('calendar', 'vr_iblock_id', ""),
			'dep_manager_sub' => COption::GetOptionString('calendar', 'dep_manager_sub', true),
			'denied_superpose_types' => unserialize(COption::GetOptionString('calendar', 'denied_superpose_types', serialize(array()))),
			'pathes_for_sites' => $pathes_for_sites,
			'pathes' => $pathes,
			'forum_id' => COption::GetOptionString('calendar', 'forum_id', "")
		);

		$arPathes = self::GetPathesList();
		foreach($arPathes as $pathName)
		{
			if (!isset(self::$settings[$pathName]))
				self::$settings[$pathName] = COption::GetOptionString('calendar', $pathName, "");
		}

		if(self::$settings['work_time_start'] > 23)
			self::$settings['work_time_start'] = 23;
		if (self::$settings['work_time_end'] <= self::$settings['work_time_start'])
			self::$settings['work_time_end'] = self::$settings['work_time_start'] + 1;
		if (self::$settings['work_time_end'] > 23.30)
			self::$settings['work_time_end'] = 23.30;

		if (self::$settings['forum_id'] == "")
		{
			self::$settings['forum_id'] = COption::GetOptionString("tasks", "task_forum_id", "");
			if (self::$settings['forum_id'] == "" && CModule::IncludeModule("forum"))
			{
				$db = CForumNew::GetListEx();
				if ($ar = $db->GetNext())
					self::$settings['forum_id'] = $ar["ID"];
			}
			COption::SetOptionString("calendar", "forum_id", self::$settings['forum_id']);
		}

		return self::$settings;
	}

	public static function GetUserNameTemplate($fromSite = true)
	{
		$user_name_template = COption::GetOptionString('calendar', 'user_name_template', '');
		if ($fromSite && empty($user_name_template))
			$user_name_template = CSite::GetNameFormat(false);
		return $user_name_template;
	}

	public static function SetSettings($arSettings = array(), $bClear = false)
	{
		$arPathes = self::GetPathesList();
		$arOpt = array('work_time_start', 'work_time_end', 'year_holidays', 'year_workdays', 'week_holidays', 'week_start', 'user_name_template', 'user_show_login', 'rm_iblock_type', 'rm_iblock_id', 'vr_iblock_id', 'denied_superpose_types', 'pathes_for_sites', 'pathes', 'dep_manager_sub', 'forum_id');
		$arOpt = array_merge($arOpt, $arPathes);

		foreach($arOpt as $opt)
		{
			if ($bClear)
			{
				COption::RemoveOption("calendar", $opt);
			}
			elseif (isset($arSettings[$opt]))
			{
				if ($opt == 'pathes' && is_array($arSettings[$opt]))
				{
					$sitesPathes = $arSettings[$opt];

					$ar = array();
					$arAffectedSites = array();
					foreach($sitesPathes as $s => $pathes)
					{
						$affect = false;
						foreach($arPathes as $path)
						{
							if ($pathes[$path] != $arSettings[$path])
							{
								$ar[$path] = $pathes[$path];
								$affect = true;
							}
						}

						if ($affect && !in_array($s, $arAffectedSites))
						{
							$arAffectedSites[] = $s;
							COption::SetOptionString("calendar", 'pathes_'.$s, serialize($ar));
						}
						else
						{
							COption::RemoveOption("calendar", 'pathes_'.$s);
						}
					}
					COption::SetOptionString("calendar", 'pathes_sites', serialize($arAffectedSites));
					continue;
				}
				elseif ($opt == 'denied_superpose_types' && is_array($arSettings[$opt]))
				{
					$arSettings[$opt] = serialize($arSettings[$opt]);
				}
				COption::SetOptionString("calendar", $opt, $arSettings[$opt]);
			}
		}
	}

	public static function ClearSettings()
	{
		self::SetSettings(array(), true);
	}

	public static function GetPathesList()
	{
		if (!self::$pathesListEx)
		{
			self::$pathesListEx = self::$pathesList;
			$arTypes = CCalendarType::GetList(array('checkPermissions' => false));
			for ($i = 0, $l = count($arTypes); $i < $l; $i++)
			{
				if ($arTypes[$i]['XML_ID'] !== 'user' && $arTypes[$i]['XML_ID'] !== 'group')
				{
					self::$pathesList[] = 'path_to_type_'.$arTypes[$i]['XML_ID'];
				}
			}
		}
		return self::$pathesList;
	}

	public static function GetPathes($forSite = false)
	{
		$pathes = array();
		$pathes_for_sites = COption::GetOptionString('calendar', 'pathes_for_sites', true);
		if ($forSite === false)
		{
			$arAffectedSites = COption::GetOptionString('calendar', 'pathes_sites', false);

			if ($arAffectedSites != false && CheckSerializedData($arAffectedSites))
				$arAffectedSites = unserialize($arAffectedSites);
		}
		else
		{
			if (is_array($forSite))
				$arAffectedSites = $forSite;
			else
				$arAffectedSites = array($forSite);
		}

		if(is_array($arAffectedSites) && count($arAffectedSites) > 0)
		{
			foreach($arAffectedSites as $s)
			{
				$ar = COption::GetOptionString("calendar", 'pathes_'.$s, false);
				if ($ar != false && CheckSerializedData($ar))
				{
					$ar = unserialize($ar);
					if(is_array($ar))
						$pathes[$s] = $ar;
				}
			}
		}

		if ($forSite !== false)
		{
			$result = array();
			if (isset($pathes[$forSite]) && is_array($pathes[$forSite]))
				$result = $pathes[$forSite];

			$arPathes = self::GetPathesList();
			foreach($arPathes as $pathName)
			{
				$val = $result[$pathName];
				if (!isset($val) || empty($val) || $pathes_for_sites)
				{
					if (!isset($SET))
						$SET = self::GetSettings();
					$val = $SET[$pathName];
					$result[$pathName] = $val;
				}
			}
			return $result;
		}
		return $pathes;
	}

	public static function GetUserSettings($userId = false)
	{
		if (!$userId)
			$userId = self::$userId;

		$DefSettings = array(
			'tabId' => 'month',
			'CalendarSelCont' => false,
			'SPCalendarSelCont' => false,
			'meetSection' => false,
			'blink' => true,
			'showDeclined' => false,
			'showMuted' => true
		);

		if ($userId)
		{
			$Settings = CUserOptions::GetOption("calendar", "user_settings", false, $userId);
			if (is_array($Settings))
			{
				if (isset($Settings['tabId']) && in_array($Settings['tabId'], array('month','week','day')))
					$DefSettings['tabId'] = $Settings['tabId'];

				if (isset($Settings['blink']))
					$DefSettings['blink'] = !!$Settings['blink'];
				if (isset($Settings['showDeclined']))
					$DefSettings['showDeclined'] = !!$Settings['showDeclined'];
				if (isset($Settings['showMuted']))
					$DefSettings['showMuted'] = !!$Settings['showMuted'];

				if (isset($Settings['meetSection']) && $Settings['meetSection'] > 0)
					$DefSettings['meetSection'] = intVal($Settings['meetSection']);
			}
		}
		return $DefSettings;
	}

	public static function SetUserSettings($Settings = array(), $userId = false)
	{
		if (!$userId)
			$userId = self::$userId;
		if (!$userId)
			return;

		if ($Settings === false)
		{
			CUserOptions::SetOption("calendar", "user_settings", false, false, $userId);
		}
		elseif(is_array($Settings))
		{
			$arOpt = array('tabId','CalendarSelCont','SPCalendarSelCont','meetSection','blink','showDeclined','showMuted');
			$curSet = self::GetUserSettings($userId);
			foreach($Settings as $key => $val)
			{
				if (in_array($key, $arOpt))
					$curSet[$key] = $val;
			}
			CUserOptions::SetOption("calendar", "user_settings", $curSet, false, $userId);
		}
	}

	public static function IsSocNet()
	{
		if (!isset(self::$bSocNet))
		{
			CModule::IncludeModule("socialnetwork");
			self::$bSocNet = class_exists('CSocNetUserToGroup') && CBXFeatures::IsFeatureEnabled("Calendar") && self::IsIntranetEnabled();
		}

		return self::$bSocNet;
	}

	public static function IsBitrix24()
	{
		return IsModuleInstalled('bitrix24');
	}

	public static function SearchAttendees($name = '', $Params = array())
	{
		if (!isset($Params['arFoundUsers']))
			$Params['arFoundUsers'] = CSocNetUser::SearchUser($name);

		$arUsers = array();
		if (!is_array($Params['arFoundUsers']) || count($Params['arFoundUsers']) <= 0)
		{
			if ($Params['addExternal'] !== false)
			{
				if (check_email($name, true))
				{
					$arUsers[] = array(
						'type' => 'ext',
						'email' => htmlspecialcharsex($name)
					);
				}
				else
				{
					$arUsers[] = array(
						'type' => 'ext',
						'name' => htmlspecialcharsex($name)
					);
				}
			}
		}
		else
		{
			foreach ($Params['arFoundUsers'] as $userId => $userName)
			{
				$userId = intVal($userId);

				$by = "id";
				$order = "asc";
				$r = CUser::GetList($by, $order, array("ID_EQUAL_EXACT" => $userId, "ACTIVE" => "Y"));

				if (!$User = $r->Fetch())
					continue;
				$name = trim($User['NAME'].' '.$User['LAST_NAME']);
				if ($name == '')
					$name = trim($User['LOGIN']);

				$arUsers[] = array(
					'type' => 'int',
					'id' => $userId,
					'name' => $name,
					'status' => 'Q',
					'busy' => 'free'
				);
			}
		}
		return $arUsers;
	}

	public static function GetGroupMembers($groupId)
	{
		$dbMembers = CSocNetUserToGroup::GetList(
			array("RAND" => "ASC"),
			array(
				"GROUP_ID" => $groupId,
				"<=ROLE" => SONET_ROLES_USER,
				"USER_ACTIVE" => "Y"
			),
			false,
			false,
			array("USER_ID", "USER_NAME", "USER_LAST_NAME", "USER_SECOND_NAME", "USER_LOGIN")
		);

		$arMembers = array();
		if ($dbMembers)
		{
			while ($Member = $dbMembers->GetNext())
			{
				$name = trim($Member['USER_NAME'].' '.$Member['USER_LAST_NAME']);
				if ($name == '')
					$name = trim($Member['USER_LOGIN']);
				$arMembers[] = array('id' => $Member["USER_ID"],'name' => $name);
			}
		}
		return $arMembers;
	}

	//
//	public static function CheckUsersAccessibility($Params = array())
//	{
//		if (!isset($Params['from']) || !is_array($Params['users']))
//			return false;
//
//		// + 5 min
//		// - 5 min
//		$from = $Params['from'];
//		$to = $Params['to'];
//
//		$result = array();
//		$userIds = array();
//		foreach($Params['users'] as $userId)
//		{
//			if (intVal($userId) > 0)
//			{
//				$userIds[] = intVal($userId);
//				$result[intVal($userId)] = false;
//			}
//		}
//
//		if (count($userIds) <= 0)
//			return false;
//
//		// Fetch absence from intranet
//		if (CCalendar::IsIntranetEnabled())
//		{
//			$resHR = CIntranetUtils::GetAbsenceData(
//				array(
//					'DATE_START' => $from,
//					'DATE_FINISH' => $to,
//					'USERS' => $userIds,
//					'PER_USER' => true,
//					'SELECT' => array('ID', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO')
//				),
//				BX_INTRANET_ABSENCE_HR
//			);
//
//			foreach($resHR as $userId => $forUser)
//				$result[$userId] = 'absent';
//
//			$userIdsNew = array();
//			foreach($userIds as $userId)
//				if (!$result[$userId])
//					$userIdsNew[] = $userId;
//			$userIds = $userIdsNew;
//		}
//
//		if (count($userIds) > 0)
//		{
//			$resCal =  CCalendarEvent::GetAccessibilityForUsers(array(
//				'users' => $userIds,
//				'from' => $from,
//				'to' => $to,
//				'curEventId' => $Params['eventId']
//			));
//
//			foreach($resCal as $userId => $forUser)
//			{
//				if (count($forUser) > 0)
//				{
//					foreach($forUser as $event)
//					{
//						if ($event['ACCESSIBILITY'] == 'absent')
//						{
//							$result[$userId] = 'absent';
//							break;
//						}
//						elseif($event['ACCESSIBILITY'] == 'busy')
//						{
//							$result[$userId] = 'busy';
//							break;
//						}
//					}
//				}
//			}
//		}
//		return $result;
//	}

	public static function AddAgent($remindTime, $arParams)
	{
		global $DB;
		CCalendar::RemoveAgent($arParams);
		if (strlen($remindTime) > 0 && $DB->IsDate($remindTime, false, LANG, "FULL"))
		{
			$tzEnabled = CTimeZone::Enabled();
			if ($tzEnabled)
				CTimeZone::Disable();
			CAgent::AddAgent(
				"CCalendar::ReminderAgent(".intVal($arParams['eventId']).", ".intVal($arParams['userId']).", '".addslashes($arParams['viewPath'])."', '".addslashes($arParams['calendarType'])."', ".intVal($arParams['ownerId']).");",
				"calendar",
				"N",
				86400,
				"",
				"Y",
				$remindTime
			);
			if ($tzEnabled)
				CTimeZone::Enable();
		}
	}

	public static function RemoveAgent($arParams)
	{
		CAgent::RemoveAgent("CCalendar::ReminderAgent(".$arParams['eventId'].", ".$arParams['userId'].", '".$arParams['viewPath']."', '".$arParams['calendarType']."', ".$arParams['ownerId'].");", "calendar");
	}

	public static function ReminderAgent($eventId = 0, $userId = 0, $viewPath = '', $calendarType = '', $ownerId = 0)
	{
		if ($eventId > 0 && $userId > 0 && $calendarType != '')
		{
			if (!CModule::IncludeModule("im"))
				return false;

			$event = false;
			$skipReminding = false;
			global $USER;
			// Create tmp user
			if ($bTmpUser = (!$USER || !is_object($USER)))
				$USER = new CUser;

			// We have to use this to set timezone offset to local user's timezone
			self::SetOffset(false, self::GetOffset($userId));

			$arEvents = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						"ID" => $eventId,
						"DELETED" => "N",
						"FROM_LIMIT" => CCalendar::Date(time() - 3600, false),
						"TO_LIMIT" => CCalendar::Date(CCalendar::GetMaxTimestamp(), false)
					),
					'parseRecursion' => true,
					'maxInstanceCount' => 3,
					'preciseLimits' => true,
					'fetchAttendees' => true,
					'checkPermissions' => false,
					'setDefaultLimit' => false
				)
			);

			if ($arEvents && is_array($arEvents[0]))
				$event = $arEvents[0];

			if ($event && $event['IS_MEETING'])
			{
				$attendees = CCalendarEvent::GetAttendees($event['PARENT_ID']);
				$attendees = $attendees[$event['PARENT_ID']];
				foreach($attendees as $attendee)
				{
					// If current user is an attendee but his status is 'N' we don't take care about reminding
					if ($attendee['USER_ID'] == $userId && $attendee['STATUS'] == 'N')
					{
						$skipReminding = true;
						break;
					}
				}
			}

			if ($event && $event['DELETED'] != 'Y' && !$skipReminding)
			{
				// Get Calendar Info
				$Section = CCalendarSect::GetById($event['SECT_ID'], false);
				if ($Section)
				{
					$arNotifyFields = array(
						'FROM_USER_ID' => $userId,
						'TO_USER_ID' => $userId,
						'NOTIFY_TYPE' => IM_NOTIFY_SYSTEM,
						'NOTIFY_MODULE' => "calendar",
						'NOTIFY_EVENT' => "reminder",
						'NOTIFY_TAG' => "CALENDAR|INVITE|".$eventId."|".$userId."|REMINDER",
						'NOTIFY_SUB_TAG' => "CALENDAR|INVITE|".$eventId
					);

					$fromTs = CCalendar::Timestamp($event['DATE_FROM'], false, $event['DT_SKIP_TIME'] !== 'Y');
					if ($event['DT_SKIP_TIME'] !== 'Y')
					{
						$fromTs -= $event['~USER_OFFSET_FROM'];
					}
					$arNotifyFields['MESSAGE'] = GetMessage('EC_EVENT_REMINDER', Array(
						'#EVENT_NAME#' => $event["NAME"],
						'#DATE_FROM#' => CCalendar::Date($fromTs, $event['DT_SKIP_TIME'] !== 'Y')
					));

					$sectionName = $Section['NAME'];
					$ownerName = CCalendar::GetOwnerName($calendarType, $ownerId);
					if ($calendarType == 'user' && $ownerId == $userId)
						$arNotifyFields['MESSAGE'] .= ' '.GetMessage('EC_EVENT_REMINDER_IN_PERSONAL', Array('#CALENDAR_NAME#' => $sectionName));
					else if($calendarType == 'user')
						$arNotifyFields['MESSAGE'] .= ' '.GetMessage('EC_EVENT_REMINDER_IN_USER', Array('#CALENDAR_NAME#' => $sectionName, '#USER_NAME#' => $ownerName));
					else if($calendarType == 'group')
						$arNotifyFields['MESSAGE'] .= ' '.GetMessage('EC_EVENT_REMINDER_IN_GROUP', Array('#CALENDAR_NAME#' => $sectionName, '#GROUP_NAME#' => $ownerName));
					else
						$arNotifyFields['MESSAGE'] .= ' '.GetMessage('EC_EVENT_REMINDER_IN_COMMON', Array('#CALENDAR_NAME#' => $sectionName, '#IBLOCK_NAME#' => $ownerName));

					if ($viewPath != '')
						$arNotifyFields['MESSAGE'] .= "\n".GetMessage('EC_EVENT_REMINDER_DETAIL', Array('#URL_VIEW#' => $viewPath));

					if (CModule::IncludeModule("im"))
					{
						CIMNotify::Add($arNotifyFields);
					}

					foreach(GetModuleEvents("calendar", "OnRemindEvent", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array(
							array(
								'eventId' => $eventId,
								'userId' => $userId,
								'viewPath' => $viewPath,
								'calType' => $calendarType,
								'ownerId' => $ownerId
							)
						));

					if (CCalendarEvent::CheckRecurcion($event) && ($nextEvent = $arEvents[1]))
					{
						$remAgentParams = array(
							'eventId' => $eventId,
							'userId' => $userId,
							'viewPath' => $viewPath,
							'calendarType' => $calendarType,
							'ownerId' => $ownerId
						);

						// 1. clean reminders
						CCalendar::RemoveAgent($remAgentParams);

						$startTs = CCalendar::Timestamp($nextEvent['DATE_FROM'], false, $event["DT_SKIP_TIME"] !== 'Y');
						if ($nextEvent["DT_SKIP_TIME"] == 'N' && $nextEvent["TZ_FROM"])
						{
							$startTs = $startTs - CCalendar::GetTimezoneOffset($nextEvent["TZ_FROM"], $startTs); // UTC timestamp
						}

						// 2. Set new reminders
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

			self::$offset = null;
			if (isset($bTmpUser) && $bTmpUser)
				unset($USER);
		}
	}

	public static function TempUser($TmpUser = false, $create = true, $ID = false)
	{
		global $USER;
		if ($create && $TmpUser === false && (!$USER || !is_object($USER)))
		{
			$USER = new CUser;
			if ($ID && intVal($ID) > 0)
				$USER->Authorize(intVal($ID));
			return $USER;
		}
		elseif (!$create && $USER && is_object($USER))
		{
			unset($USER);
			return false;
		}
		return false;
	}

	public static function GetAbsentEvents($Params)
	{
		if (!isset($Params['arUserIds']))
			return false;

		return CCalendarEvent::GetAbsent($Params['arUserIds'], $Params);
	}

	public static function GetOwnerName($type = '', $ownerId = '')
	{
		$type = strtolower($type);
		$key = $type.'_'.$ownerId;

		if (isset(self::$ownerNames[$key]))
			return self::$ownerNames[$key];

		$ownerName = '';
		if($type == 'user')
		{
			$ownerName = CCalendar::GetUserName($ownerId);
		}
		elseif($type == 'group')
		{
			// Get group name
			if (!CModule::IncludeModule("socialnetwork"))
				return $ownerName;

			if ($arGroup = CSocNetGroup::GetByID($ownerId))
				$ownerName = $arGroup["~NAME"];
		}
		else
		{
			// Get type name
			$arTypes = CCalendarType::GetList(array("arFilter" => array("XML_ID" => $type)));
			$ownerName = $arTypes[0]['NAME'];
		}
		self::$ownerNames[$key] = $ownerName;
		$ownerName = trim($ownerName);

		return $ownerName;
	}

	/*
	 * $params['from'], $params['from'] - datetime in UTC
	 * */
	public static function GetAccessibilityForUsers($params)
	{
		if (!isset($params['checkPermissions']))
			$params['checkPermissions'] = true;

		$res = CCalendarEvent::GetAccessibilityForUsers(array(
			'users' => $params['users'],
			'from' => $params['from'],
			'to' => $params['to'],
			'curEventId' => $params['curEventId'],
			'checkPermissions' => $params['checkPermissions']
		));

		// Fetch absence from intranet
		if ($params['getFromHR'] && CCalendar::IsIntranetEnabled())
		{
			$resHR = CIntranetUtils::GetAbsenceData(
				array(
					'DATE_START' => $params['from'],
					'DATE_FINISH' => $params['to'],
					'USERS' => $params['users'],
					'PER_USER' => true,
					'SELECT' => array('ID', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO')
				),
				BX_INTRANET_ABSENCE_HR
			);

			foreach($resHR as $userId => $forUser)
			{
				if (!isset($res[$userId]) || !is_array($res[$userId]))
					$res[$userId] = array();

				foreach($forUser as $event)
				{
					$res[$userId][] = array(
						'FROM_HR' => true,
						'ID' => $event['ID'],
						'DT_FROM' => $event['DATE_ACTIVE_FROM'],
						'DT_TO' => $event['DATE_ACTIVE_TO'],
						'ACCESSIBILITY' => 'absent',
						'IMPORTANCE' => 'normal',
						"FROM" => CCalendar::Timestamp($event['DATE_ACTIVE_FROM']),
						"TO" => CCalendar::Timestamp($event['DATE_ACTIVE_TO'])
					);
				}
			}
		}

		return $res;
	}

	public static function GetNearestEventsList($params = array())
	{
		$type = $params['bCurUserList'] ? 'user' : $params['type'];

		// Get current user id
		if (!isset($params['userId']) || $params['userId'] <= 0)
			$curUserId = CCalendar::GetCurUserId();
		else
			$curUserId = intval($params['userId']);

		if (!CCalendarType::CanDo('calendar_type_view', $type, $curUserId))
			return 'access_denied';

		if ($params['bCurUserList'] && ($curUserId <= 0 || (class_exists('CSocNetFeatures') && !CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $curUserId, "calendar"))))
			return 'inactive_feature';

		$arFilter = array(
			'CAL_TYPE' => $type,
			'FROM_LIMIT' => $params['fromLimit'],
			'TO_LIMIT' => $params['toLimit'],
			'DELETED' => 'N',
			'ACTIVE_SECTION' => 'Y'
		);

		if ($params['bCurUserList'])
			$arFilter['OWNER_ID'] = $curUserId;

		if (isset($params['sectionId']) && $params['sectionId'])
			$arFilter["SECTION"] = $params['sectionId'];

		if ($type == 'user')
			unset($arFilter['CAL_TYPE']);

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => $arFilter,
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'userId' => $curUserId,
				'fetchMeetings' => $type == 'user',
				'preciseLimits' => true,
				'skipDeclined' => true
			)
		);

		if (CCalendar::Date(time(), false) == $params['fromLimit'])
			$currentTime = time();
		else
			$currentTime = CCalendar::Timestamp($params['fromLimit']);

		$arResult = array();
		$serverOffset = intVal(date("Z"));

		foreach($arEvents as $event)
		{
			if ($event['IS_MEETING'] && $event["MEETING_STATUS"] == 'N')
				continue;

			if ($type == 'user' && !$event['IS_MEETING'] && $event['CAL_TYPE'] != 'user')
				continue;

			$toTs = $event['DATE_TO_TS_UTC'] + $serverOffset;
			if ($event['DT_SKIP_TIME'] == 'Y')
				$toTs += self::DAY_LENGTH;

			if ($toTs < $currentTime)
				continue;

			$fromTs = CCalendar::Timestamp($event['DATE_FROM']);
			$toTs = CCalendar::Timestamp($event['DATE_TO']);
			if ($event['DT_SKIP_TIME'] !== "Y")
			{
				$fromTs -= $event['~USER_OFFSET_FROM'];
				$toTs -= $event['~USER_OFFSET_TO'];
			}
			$event['DATE_FROM'] = CCalendar::Date($fromTs, $event['DT_SKIP_TIME'] != 'Y');
			$event['DATE_TO'] = CCalendar::Date($toTs, $event['DT_SKIP_TIME'] != 'Y');

			unset($event['TZ_FROM'], $event['TZ_TO'], $event['TZ_OFFSET_FROM'], $event['TZ_OFFSET_TO']);

			$event['DT_FROM_TS'] = $fromTs;
			$event['DT_TO_TS'] = $toTs;

			$arResult[] = $event;
		}

		// Sort by DATE_FROM_TS
		usort($arResult, array('CCalendar', '_NearestSort'));
		return $arResult;
	}

	public static function _NearestSort($a, $b)
	{
		if ($a['DT_FROM_TS'] == $b['DT_FROM_TS'])
			return 0;
		if ($a['DT_FROM_TS'] < $b['DT_FROM_TS'])
			return -1;
		return 1;
	}

	/* * * * RESERVE MEETING ROOMS  * * * */
	public static function GetMeetingRoomList($Params = array())
	{
		if (isset(self::$meetingRoomList))
			return self::$meetingRoomList;

		if (!isset($Params['RMiblockId']) && !isset($Params['VMiblockId']))
		{
			if (!isset(self::$settings))
				self::$settings = self::GetSettings();

			if (!self::$pathesForSite)
				self::$pathesForSite = self::GetSettings(array('forseGetSitePathes' => true,'site' =>self::GetSiteId()));
			$RMiblockId = self::$settings['rm_iblock_id'];
			$VMiblockId = self::$settings['vr_iblock_id'];
			$pathToMR = self::$pathesForSite['path_to_rm'];
			$pathToVR = self::$pathesForSite['path_to_vr'];
		}
		else
		{
			$RMiblockId = $Params['RMiblockId'];
			$VMiblockId = $Params['VMiblockId'];
			$pathToMR = $Params['pathToMR'];
			$pathToVR = $Params['pathToVR'];
		}

		$MRList = Array();
		if (IntVal($RMiblockId) > 0 && CIBlock::GetPermission($RMiblockId) >= "R" && self::$allowReserveMeeting)
		{
			$arOrderBy = array("NAME" => "ASC", "ID" => "DESC");
			$arFilter = array("IBLOCK_ID" => $RMiblockId, "ACTIVE" => "Y");
			$arSelectFields = array("IBLOCK_ID","ID","NAME","DESCRIPTION","UF_FLOOR","UF_PLACE","UF_PHONE");
			$res = CIBlockSection::GetList($arOrderBy, $arFilter, false, $arSelectFields );
			while ($arMeeting = $res->GetNext())
			{
				$MRList[] = array(
					'ID' => $arMeeting['ID'],
					'NAME' => $arMeeting['~NAME'],
					'DESCRIPTION' => $arMeeting['~DESCRIPTION'],
					'UF_PLACE' => $arMeeting['UF_PLACE'],
					'UF_PHONE' => $arMeeting['UF_PHONE'],
					'URL' => str_replace(array("#id#", "#ID#"), $arMeeting['ID'], $pathToMR)
				);
			}
		}

		if(IntVal($VMiblockId) > 0 && CIBlock::GetPermission($VMiblockId) >= "R" && self::$allowVideoMeeting)
		{
			$arFilter = array("IBLOCK_ID" => $VMiblockId, "ACTIVE" => "Y");
			$arSelectFields = array("ID", "NAME", "DESCRIPTION", "IBLOCK_ID");
			$res = CIBlockSection::GetList(Array(), $arFilter, false, $arSelectFields);
			if($arMeeting = $res->GetNext())
			{
				$MRList[] = array(
					'ID' => $VMiblockId,
					'NAME' => $arMeeting["~NAME"],
					'DESCRIPTION' => $arMeeting['~DESCRIPTION'],
					'URL' => str_replace(array("#id#", "#ID#"), $arMeeting['ID'], $pathToVR),
				);
			}
		}
		self::$meetingRoomList = $MRList;

		return $MRList;
	}

	public static function GetAccessibilityForMeetingRoom($Params)
	{
		$allowReserveMeeting = isset($Params['allowReserveMeeting']) ? $Params['allowReserveMeeting'] : self::$allowReserveMeeting;
		$allowVideoMeeting = isset($Params['allowVideoMeeting']) ? $Params['allowVideoMeeting'] : self::$allowVideoMeeting;
		$RMiblockId = isset($Params['RMiblockId']) ? $Params['RMiblockId'] : self::$settings['rm_iblock_id'];
		$VMiblockId = isset($Params['VMiblockId']) ? $Params['VMiblockId'] : self::$settings['vr_iblock_id'];
		$curEventId = $Params['curEventId'] > 0 ? $Params['curEventId'] : false;
		$arResult = array();
		$offset = CCalendar::GetOffset();

		if ($allowReserveMeeting)
		{
			$arSelect = array("ID", "NAME", "IBLOCK_SECTION_ID", "IBLOCK_ID", "ACTIVE_FROM", "ACTIVE_TO");
			$arFilter = array(
				"IBLOCK_ID" => $RMiblockId,
				"SECTION_ID" => $Params['id'],
				"INCLUDE_SUBSECTIONS" => "Y",
				"ACTIVE" => "Y",
				"CHECK_PERMISSIONS" => 'N',
				">=DATE_ACTIVE_TO" => $Params['from'],
				"<=DATE_ACTIVE_FROM" => $Params['to']
			);
			if(IntVal($curEventId) > 0)
				$arFilter["!ID"] = IntVal($curEventId);

			$rsElement = CIBlockElement::GetList(Array('ACTIVE_FROM' => 'ASC'), $arFilter, false, false, $arSelect);
			while($obElement = $rsElement->GetNextElement())
			{
				$arItem = $obElement->GetFields();
				$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(self::DFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
				$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(self::DFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));

				$arResult[] = array(
					"ID" => intVal($arItem['ID']),
					"NAME" => $arItem['~NAME'],
					"DT_FROM" => CCalendar::CutZeroTime($arItem['DISPLAY_ACTIVE_FROM']),
					"DT_TO" => CCalendar::CutZeroTime($arItem['DISPLAY_ACTIVE_TO']),
					"DT_FROM_TS" => (CCalendar::Timestamp($arItem['DISPLAY_ACTIVE_FROM']) - $offset) * 1000,
					"DT_TO_TS" => (CCalendar::Timestamp($arItem['DISPLAY_ACTIVE_TO']) - $offset) * 1000
				);
			}
		}

		if ($allowVideoMeeting && $Params['id'] == $VMiblockId)
		{
			$arSelect = array("ID", "NAME", "IBLOCK_ID", "ACTIVE_FROM", "ACTIVE_TO", "PROPERTY_*");
			$arFilter = array(
				"IBLOCK_ID" => $VMiblockId,
				"ACTIVE" => "Y",
				"CHECK_PERMISSIONS" => 'N',
				">=DATE_ACTIVE_TO" => $Params['from'],
				"<=DATE_ACTIVE_FROM" => $Params['to']
			);
			if(IntVal($curEventId) > 0)
				$arFilter["!ID"] = IntVal($curEventId);

			$arSort = Array('ACTIVE_FROM' => 'ASC');

			$rsElement = CIBlockElement::GetList($arSort, $arFilter, false, false, $arSelect);
			while($obElement = $rsElement->GetNextElement())
			{
				$arItem = $obElement->GetFields();
				$arItem["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat(self::DFormat(true), MakeTimeStamp($arItem["ACTIVE_FROM"]));
				$arItem["DISPLAY_ACTIVE_TO"] = CIBlockFormatProperties::DateFormat(self::DFormat(true), MakeTimeStamp($arItem["ACTIVE_TO"]));

				$check = CCalendar::CheckVideoRoom(Array(
					"dateFrom" => $arItem["ACTIVE_FROM"],
					"dateTo" => $arItem["ACTIVE_TO"],
					"VMiblockId" => $VMiblockId,
					"regularity" => "NONE",
				));

				if ($check !== true && $check == "reserved")
				{
					//todo make only factical reserved, not any time
					$arResult[] = array(
						"ID" => intVal($arItem['ID']),
						"NAME" => $arItem['~NAME'],
						"DT_FROM" => CCalendar::CutZeroTime($arItem['DISPLAY_ACTIVE_FROM']),
						"DT_TO" => CCalendar::CutZeroTime($arItem['DISPLAY_ACTIVE_TO']),
						"DT_FROM_TS" => (CCalendar::Timestamp($arItem['DISPLAY_ACTIVE_FROM']) - $offset) * 1000,
						"DT_TO_TS" => (CCalendar::Timestamp($arItem['DISPLAY_ACTIVE_TO']) - $offset) * 1000
					);
				}
			}
		}

		return $arResult;
	}

	public static function GetMeetingRoomById($Params)
	{
		if (IntVal($Params['RMiblockId']) > 0 && CIBlock::GetPermission($Params['RMiblockId']) >= "R")
		{
			$arFilter = array("IBLOCK_ID" => $Params['RMiblockId'], "ACTIVE" => "Y", "ID" => $Params['id']);
			$arSelectFields = array("NAME");
			$res = CIBlockSection::GetList(array(), $arFilter, false, array("NAME"));
			if ($arMeeting = $res->GetNext())
				return $arMeeting;
		}

		if(IntVal($Params['VMiblockId']) > 0 && CIBlock::GetPermission($Params['VMiblockId']) >= "R")
		{
			$arFilter = array("IBLOCK_ID" => $Params['VMiblockId'], "ACTIVE" => "Y");
			$arSelectFields = array("ID", "NAME", "DESCRIPTION", "IBLOCK_ID");
			$res = CIBlockSection::GetList(Array(), $arFilter, false, $arSelectFields);
			if($arMeeting = $res->GetNext())
			{
				return array(
					'ID' => $Params['VMiblockId'],
					'NAME' => $arMeeting["NAME"],
					'DESCRIPTION' => $arMeeting['DESCRIPTION'],
				);
			}
		}
		return false;
	}

	public static function ReserveMeetingRoom($Params)
	{
		$tst = MakeTimeStamp($Params['dateTo']);
		if (date("H:i", $tst) == '00:00')
			$Params['dateTo'] = CIBlockFormatProperties::DateFormat(self::DFormat(true), $tst + (23 * 60 + 59) * 60);

		$Params['RMiblockId'] = isset($Params['RMiblockId']) ? $Params['RMiblockId'] : self::$settings['rm_iblock_id'];
		$check = CCalendar::CheckMeetingRoom($Params);
		if ($check !== true)
			return $check;

		$arFields = array(
			"IBLOCK_ID" => $Params['RMiblockId'],
			"IBLOCK_SECTION_ID" => $Params['mrid'],
			"NAME" => $Params['name'],
			"DATE_ACTIVE_FROM" => $Params['dateFrom'],
			"DATE_ACTIVE_TO" => $Params['dateTo'],
			"CREATED_BY" => CCalendar::GetCurUserId(),
			"DETAIL_TEXT" => $Params['description'],
			"PROPERTY_VALUES" => array(
				"UF_PERSONS" => $Params['persons'],
				"PERIOD_TYPE" => 'NONE'
			),
			"ACTIVE" => "Y"
		);

		$bs = new CIBlockElement;
		$id = $bs->Add($arFields);

		// Hack: reserve meeting calendar based on old calendar's cache
		$cache = new CPHPCache;
		$cache->CleanDir('event_calendar/');
		$cache->CleanDir('event_calendar/events/');
		$cache->CleanDir('event_calendar/events/'.$Params['RMiblockId']);

		return $id;
	}

	public static function ReleaseMeetingRoom($Params)
	{
		$Params['RMiblockId'] = isset($Params['RMiblockId']) ? $Params['RMiblockId'] : self::$settings['rm_iblock_id'];
		$arFilter = array(
			"ID" => $Params['mrevid'],
			"IBLOCK_ID" => $Params['RMiblockId'],
			"IBLOCK_SECTION_ID" => $Params['mrid'],
			"SECTION_ID" => array($Params['mrid'])
		);

		$res = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID"));
		if($arElement = $res->Fetch())
		{
			$obElement = new CIBlockElement;
			$obElement->Delete($Params['mrevid']);
		}

		// Hack: reserve meeting calendar based on old calendar's cache
		$cache = new CPHPCache;
		$cache->CleanDir('event_calendar/');
		$cache->CleanDir('event_calendar/events/');
		$cache->CleanDir('event_calendar/events/'.$Params['RMiblockId']);
	}

	public static function CheckMeetingRoom($Params)
	{
		$fromDateTime = MakeTimeStamp($Params['dateFrom']);
		$toDateTime = MakeTimeStamp($Params['dateTo']);
		$arFilter = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $Params['RMiblockId'],
			"SECTION_ID" => $Params['mrid'],
			"<DATE_ACTIVE_FROM" => $Params['dateTo'],
			">DATE_ACTIVE_TO" => $Params['dateFrom'],
			"PROPERTY_PERIOD_TYPE" => "NONE",
		);

		if ($Params['mrevid_old'] > 0)
			$arFilter["!=ID"] = $Params['mrevid_old'];

		$dbElements = CIBlockElement::GetList(array("DATE_ACTIVE_FROM" => "ASC"), $arFilter, false, false, array('ID'));
		if ($arElements = $dbElements->GetNext())
			return 'reserved';

		include_once($_SERVER['DOCUMENT_ROOT']."/bitrix/components/bitrix/intranet.reserve_meeting/init.php");
		$arPeriodicElements = __IRM_SearchPeriodic($fromDateTime, $toDateTime, $Params['RMiblockId'], $Params['mrid']);

		for ($i = 0, $l = count($arPeriodicElements); $i < $l; $i++)
			if (!$Params['mrevid_old'] || $arPeriodicElements[$i]['ID'] != $Params['mrevid_old'])
				return 'reserved';

		return true;
	}

	public static function ReserveVideoRoom($Params)
	{
		$tst = MakeTimeStamp($Params['dateTo']);
		if (date("H:i", $tst) == '00:00')
			$Params['dateTo'] = CIBlockFormatProperties::DateFormat(self::DFormat(true), $tst + (23 * 60 + 59) * 60);
		$Params['VMiblockId'] = isset($Params['VMiblockId']) ? $Params['VMiblockId'] : self::$settings['vr_iblock_id'];
		$check = CCalendar::CheckVideoRoom($Params);
		if ($check !== true)
			return $check;

		$sectionID = 0;
		$dbItem = CIBlockSection::GetList(Array(), Array("IBLOCK_ID" => $Params['VMiblockId'], "ACTIVE" => "Y"));
		if($arItem = $dbItem->Fetch())
			$sectionID = $arItem["ID"];

		$arFields = array(
			"IBLOCK_ID" => $Params['VMiblockId'],
			"IBLOCK_SECTION_ID" => $sectionID,
			"NAME" => $Params['name'],
			"DATE_ACTIVE_FROM" => $Params['dateFrom'],
			"DATE_ACTIVE_TO" => $Params['dateTo'],
			"CREATED_BY" => CCalendar::GetCurUserId(),
			"DETAIL_TEXT" => $Params['description'],
			"PROPERTY_VALUES" => array(
				"PERIOD_TYPE" => 'NONE',
				"UF_PERSONS" => $Params['persons'],
				"MEMBERS" => $Params['members'],
			),
			"ACTIVE" => "Y"
		);

		$bs = new CIBlockElement;
		$id = $bs->Add($arFields);

		return $id;
	}

	public static function ReleaseVideoRoom($Params)
	{
		$arFilter = array(
			"ID" => $Params['mrevid'],
			"IBLOCK_ID" => $Params['VMiblockId']
		);

		$res = CIBlockElement::GetList(array(), $arFilter, false, false, array("ID"));
		if($arElement = $res->Fetch())
		{
			$obElement = new CIBlockElement;
			$obElement->Delete($Params['mrevid']);
		}
	}

	public static function CheckVideoRoom($Params)
	{
		if (CModule::IncludeModule("video"))
		{
			return CVideo::CheckRooms(Array(
				"regularity" => $Params["regularity"],
				"dateFrom" => $Params["dateFrom"],
				"dateTo" => $Params["dateTo"],
				"iblockId" => $Params["VMiblockId"],
				"ID" => $Params["ID"],
			));
		}
		return false;
	}

	public static function GetTextLocation($loc = '')
	{
		$oLoc = self::ParseLocation($loc);
		$result = $loc;
		if ($oLoc['mrid'] === false)
		{
			$result = $oLoc['str'];
		}
		else
		{
			$MRList = CCalendar::GetMeetingRoomList();
			foreach($MRList as $MR)
			{
				if ($MR['ID'] == $oLoc['mrid'])
				{
					$result = $MR['NAME'];
					break;
				}
			}
		}

		return $result;
	}

	public static function UnParseTextLocation($loc = '')
	{
		$result = array('NEW' => $loc);
		if ($loc != "")
		{
			$oLoc = self::ParseLocation($loc);
			if ($oLoc['mrid'] === false)
			{
				$MRList = CCalendar::GetMeetingRoomList();
				$loc_ = trim(strtolower($loc));
				foreach($MRList as $MR)
				{
					if (trim(strtolower($MR['NAME'])) == $loc_)
					{
						$result['NEW'] = 'ECMR_'.$MR['ID'];
						break;
					}
				}
			}
		}
		return $result;
	}

	public static function ParseLocation($str = '')
	{
		$res = array('mrid' => false, 'mrevid' => false, 'str' => $str);
		if (strlen($str) > 5 && substr($str, 0, 5) == 'ECMR_')
		{
			$ar_ = explode('_', $str);
			if (count($ar_) >= 2)
			{
				if (intVal($ar_[1]) > 0)
					$res['mrid'] = intVal($ar_[1]);
				if (intVal($ar_[2]) > 0)
					$res['mrevid'] = intVal($ar_[2]);
			}
		}
		return $res;
	}

	public static function ReleaseLocation($loc)
	{
		$set = CCalendar::GetSettings(array('request' => false));
		if($loc['mrid'] == $set['vr_iblock_id']) // video meeting
		{
			CCalendar::ReleaseVideoRoom(array(
				'mrevid' => $loc['mrevid'],
				'mrid' => $loc['mrid'],
				'VMiblockId' => $set['vr_iblock_id']
			));
		}
		elseif($set['rm_iblock_id'])
		{
			CCalendar::ReleaseMeetingRoom(array(
				'mrevid' => $loc['mrevid'],
				'mrid' => $loc['mrid'],
				'RMiblockId' => $set['rm_iblock_id']
			));
		}
	}

	public static function ThrowError($str)
	{
		if (self::$silentErrorMode)
			return false;

		global $APPLICATION;
		echo '<!-- BX_EVENT_CALENDAR_ACTION_ERROR:'.$str.'-->';
		return $APPLICATION->ThrowException($str);
	}

	public static function IsWebserviceEnabled()
	{
		if (!isset(self::$bWebservice))
			self::$bWebservice = IsModuleInstalled('webservice');
		return self::$bWebservice;
	}

	public static function IsIntranetEnabled()
	{
		if (!isset(self::$bIntranet))
			self::$bIntranet = IsModuleInstalled('intranet');
		return self::$bIntranet;
	}

	public static function IsExtranetEnabled()
	{
		if (!isset(self::$bExtranet))
			self::$bExtranet = CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite();
		return self::$bExtranet;
	}

	// * * * * * * * * * * * * CalDAV + Exchange * * * * * * * * * * * * * * * *
	public static function IsCalDAVEnabled()
	{
		if (!IsModuleInstalled('dav') || COption::GetOptionString("dav", "agent_calendar_caldav") != "Y")
			return false;
		return CModule::IncludeModule('dav') && CDavGroupdavClientCalendar::IsCalDAVEnabled();
	}

	public static function IsExchangeEnabled($userId = false)
	{
		if (isset(self::$arExchEnabledCache[$userId]))
			return self::$arExchEnabledCache[$userId];

		if (!IsModuleInstalled('dav') || COption::GetOptionString("dav", "agent_calendar") != "Y")
			$res = false;
		elseif (!CModule::IncludeModule('dav'))
			$res = false;
		elseif ($userId === false)
			$res = CDavExchangeCalendar::IsExchangeEnabled();
		else
			$res = CDavExchangeCalendar::IsExchangeEnabled() && CDavExchangeCalendar::IsExchangeEnabledForUser($userId);

		self::$arExchEnabledCache[$userId] = $res;
		return $res;
	}

	// Called from CalDav sync functions and from  CCalendar::SyncCalendarItems
	public static function DeleteCalendarEvent($calendarId, $eventId, $userId, $oEvent = false)
	{
		self::$silentErrorMode = true;
		list($sectionId, $entityType, $entityId) = $calendarId;

		$res = CCalendarEvent::Delete(array(
			'id' => $eventId,
			'userId' => $userId,
			'bMarkDeleted' => true,
			'Event' => $oEvent
		));
		self::$silentErrorMode = false;
		return $res;
	}

	public static function GetCalendarList($calendarId, $params = array())
	{
		self::$silentErrorMode = true;
		list($sectionId, $entityType, $entityId) = $calendarId;
		$arFilter = array(
			'CAL_TYPE' => $entityType,
			'OWNER_ID' => $entityId
		);

		if (!is_array($params))
			$params = array();

		if ($sectionId > 0)
			$arFilter['ID'] = $sectionId;

		$res = CCalendarSect::GetList(array('arFilter' => $arFilter));

		$arCalendars = array();
		foreach($res as $calendar)
		{
			if ($params['skipExchange'] == true && strlen($calendar['DAV_EXCH_CAL']) > 0)
				continue;

			$arCalendars[] = array(
				'ID' => $calendar['ID'],
				'~NAME' => $calendar['NAME'],
				'NAME' => htmlspecialcharsbx($calendar['NAME']),
				'DESCRIPTION' => htmlspecialcharsbx($calendar['DESCRIPTION']),
				'COLOR' => htmlspecialcharsbx($calendar['COLOR'])
				//"DATE_CREATE" => date("d.m.Y H:i", self::Timestamp($arSection['DATE_CREATE']))
			);
		}

		self::$silentErrorMode = false;
		return $arCalendars;
	}

	public static function GetDavCalendarEventsList($calendarId, $arFilter = array())
	{
		list($sectionId, $entityType, $entityId) = $calendarId;

		CCalendar::SetOffset(false, 0);
		$arFilter1 = array(
			'OWNER_ID' => $entityId,
			'DELETED' => 'N'
		);

		if (isset($arFilter['DAV_XML_ID']))
		{
			unset($arFilter['DATE_START'], $arFilter['FROM_LIMIT'], $arFilter['DATE_END'], $arFilter['TO_LIMIT']);
		}
		else
		{
			if (isset($arFilter['DATE_START']))
			{
				$arFilter['FROM_LIMIT'] = $arFilter['DATE_START'];
				unset($arFilter['DATE_START']);
			}
			if (isset($arFilter['DATE_END']))
			{
				$arFilter['TO_LIMIT'] = $arFilter['DATE_END'];
				unset($arFilter['DATE_END']);
			}
		}

		$fetchMeetings = true;
		if ($sectionId > 0)
		{
			$arFilter['SECTION'] = $sectionId;
			$fetchMeetings = false;
			if ($entityType == 'user')
				$fetchMeetings = self::GetMeetingSection($entityId) == $sectionId;
		}
		$arFilter = array_merge($arFilter1, $arFilter);

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => $arFilter,
				'getUserfields' => false,
				'parseRecursion' => false,
				'fetchAttendees' => false,
				'fetchMeetings' => $fetchMeetings,
				'userId' => CCalendar::GetCurUserId()
			)
		);

		$result = array();
		foreach ($arEvents as $event)
		{
			if ($event['IS_MEETING'] && $event["MEETING_STATUS"] == 'N')
				continue;

			// Skip events from where owner is host of the meeting and it's meeting from other section
			if ($entityType == 'user' && $event['IS_MEETING']  && $event['MEETING_HOST'] == $entityId && $event['SECT_ID'] != $sectionId)
				continue;

			$event['XML_ID'] = $event['DAV_XML_ID'];
			if ($event['LOCATION'] !== '')
				$event['LOCATION'] = CCalendar::GetTextLocation($event['LOCATION']);
			$event['RRULE'] = CCalendarEvent::ParseRRULE($event['RRULE']);
			$result[] = $event;
		}

		return $result;
	}

	private static $instance;
	private static function GetInstance()
	{
		if (!isset(self::$instance))
		{
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}

	// return array('bAccess' => true/false, 'bReadOnly' => true/false, 'privateStatus' => 'time'/'title');
	public static function GetUserPermissionsForCalendar($calendarId, $userId)
	{
		list($sectionId, $entityType, $entityId) = $calendarId;
		$entityType = strtolower($entityType);

		if ($sectionId == 0)
		{
			$res = array(
				'bAccess' => CCalendarType::CanDo('calendar_type_view', $entityType, $userId),
				'bReadOnly' => !CCalendarType::CanDo('calendar_type_edit', $entityType, $userId)
			);
		}

		$bOwner = $entityType == 'user' && $entityId == $userId;
		$res = array(
			'bAccess' => $bOwner || CCalendarSect::CanDo('calendar_view_time', $sectionId, $userId),
			'bReadOnly' => !$bOwner && !CCalendarSect::CanDo('calendar_edit', $sectionId, $userId)
		);

		if ($res['bReadOnly'] && !$bOwner)
		{
			if (CCalendarSect::CanDo('calendar_view_time', $sectionId, $userId))
				$res['privateStatus'] = 'time';
			if (CCalendarSect::CanDo('calendar_view_title', $sectionId, $userId))
				$res['privateStatus'] = 'title';
		}

		return $res;
	}

	// Called from CalDav, Exchange methods
	public static function ModifyEvent($calendarId, $arFields)
	{
		list($sectionId, $entityType, $entityId) = $calendarId;
		$userId = $entityType == 'user' ? $entityId : 0;
		$eventId = false;

		self::$silentErrorMode = true;

		if ($sectionId && CCalendarSect::GetById($sectionId, false))
		{
			CCalendar::SetOffset(false, CCalendar::GetOffset($userId));
			$entityType = strtolower($entityType);
			$eventId = ((isset($arFields["ID"]) && (intval($arFields["ID"]) > 0)) ? intval($arFields["ID"]) : 0);
			$arNewFields = array(
				"DAV_XML_ID" => $arFields['XML_ID'],
				"CAL_DAV_LABEL" => (isset($arFields['PROPERTY_BXDAVCD_LABEL']) && strlen($arFields['PROPERTY_BXDAVCD_LABEL']) > 0) ? $arFields['PROPERTY_BXDAVCD_LABEL'] : '',
				"DAV_EXCH_LABEL" => (isset($arFields['PROPERTY_BXDAVEX_LABEL']) && strlen($arFields['PROPERTY_BXDAVEX_LABEL']) > 0) ? $arFields['PROPERTY_BXDAVEX_LABEL'] : '',
				"ID" => $eventId,
				'NAME' => $arFields["NAME"] ? $arFields["NAME"] : GetMessage('EC_NONAME_EVENT'),
				'CAL_TYPE' => $entityType,
				'OWNER_ID' => $entityId,
				'DESCRIPTION' => isset($arFields['DESCRIPTION']) ? $arFields['DESCRIPTION'] : '',
				'SECTIONS' => $sectionId,
				'ACCESSIBILITY' => isset($arFields['ACCESSIBILITY']) ? $arFields['ACCESSIBILITY'] : 'busy',
				'IMPORTANCE' => isset($arFields['IMPORTANCE']) ? $arFields['IMPORTANCE'] : 'normal',
				"REMIND" => is_array($arFields['REMIND']) ? $arFields['REMIND'] : array(),
				"RRULE" => is_array($arFields['RRULE']) ? is_array($arFields['RRULE']) : array(),
				"VERSION" => isset($arFields['VERSION']) ? intVal($arFields['VERSION']) : 1,
				"PRIVATE_EVENT" => !!$arFields['PRIVATE_EVENT']
			);

			$arNewFields["DATE_FROM"] = $arFields['DATE_FROM'];
			$arNewFields["DATE_TO"] = $arFields['DATE_TO'];
			$arNewFields["TZ_FROM"] = $arFields['TZ_FROM'];
			$arNewFields["TZ_TO"] = $arFields['TZ_TO'];
			$arNewFields["SKIP_TIME"] = $arFields['SKIP_TIME'];

			if ($arNewFields["SKIP_TIME"])
			{
				$arNewFields["DATE_TO"] = CCalendar::Date(CCalendar::Timestamp($arNewFields['DATE_TO']) - CCalendar::GetDayLen(), false);
			}

			if (!empty($arFields['PROPERTY_REMIND_SETTINGS']))
			{
				$ar = explode("_", $arFields["PROPERTY_REMIND_SETTINGS"]);
				if(count($ar) == 2)
					$arNewFields["REMIND"][] = array('type' => $ar[1],'count' => floatVal($ar[0]));
			}

			if (!empty($arFields['PROPERTY_ACCESSIBILITY']))
				$arNewFields["ACCESSIBILITY"] = $arFields['PROPERTY_ACCESSIBILITY'];
			if (!empty($arFields['PROPERTY_IMPORTANCE']))
				$arNewFields["IMPORTANCE"] = $arFields['PROPERTY_IMPORTANCE'];
			if (!empty($arFields['PROPERTY_LOCATION']))
				$arNewFields["LOCATION"] = CCalendar::UnParseTextLocation($arFields['PROPERTY_LOCATION']);
			if (!empty($arFields['DETAIL_TEXT']))
				$arNewFields["DESCRIPTION"] = $arFields['DETAIL_TEXT'];

			$arNewFields["DESCRIPTION"] = CCalendar::ClearExchangeHtml($arNewFields["DESCRIPTION"]);
			if (isset($arFields["PROPERTY_PERIOD_TYPE"]) && in_array($arFields["PROPERTY_PERIOD_TYPE"], array("DAILY", "WEEKLY", "MONTHLY", "YEARLY")))
			{
				$arNewFields['RRULE']['FREQ'] = $arFields["PROPERTY_PERIOD_TYPE"];
				$arNewFields['RRULE']['INTERVAL'] = $arFields["PROPERTY_PERIOD_COUNT"];

				if (!isset($arNewFields['DT_LENGTH']) && !empty($arFields['PROPERTY_EVENT_LENGTH']))
				{
					$arNewFields['DT_LENGTH'] = intval($arFields['PROPERTY_EVENT_LENGTH']);
				}
				else
				{
					$arNewFields['DT_LENGTH'] = $arFields['DT_TO_TS'] - $arFields['DT_FROM_TS'];
				}

				if ($arNewFields['RRULE']['FREQ'] == "WEEKLY" && !empty($arFields['PROPERTY_PERIOD_ADDITIONAL']))
				{
					$arNewFields['RRULE']['BYDAY'] = array();
					$bydays = explode(',',$arFields['PROPERTY_PERIOD_ADDITIONAL']);
					foreach($bydays as $day)
					{
						$day = CCalendar::WeekDayByInd($day, false);
						if ($day !== false)
							$arNewFields['RRULE']['BYDAY'][] = $day;
					}
					$arNewFields['RRULE']['BYDAY'] = implode(',',$arNewFields['RRULE']['BYDAY']);
				}

				if (isset($arFields['PROPERTY_PERIOD_UNTIL']))
					$arNewFields['RRULE']['UNTIL'] = $arFields['PROPERTY_PERIOD_UNTIL'];
				else
					$arNewFields['RRULE']['UNTIL'] = $arFields['DT_TO_TS'];

				if (isset($arFields['EXDATE']))
					$arNewFields['EXDATE'] = $arFields["EXDATE"];
			}

			if (isset($arFields['ORGANIZER']) && !empty($arFields['ORGANIZER']))
			{
				$arNewFields['MEETING']['ORGANIZER'] = $arFields['ORGANIZER'];
			}

			$eventId = CCalendar::SaveEvent(
				array(
					'arFields' => $arNewFields,
					'userId' => $userId,
					'bAffectToDav' => false, // Used to prevent syncro with calDav again
					'bSilentAccessMeeting' => true,
					'autoDetectSection' => false
				)
			);
		}

		self::$silentErrorMode = false;

		return $eventId;
	}

	// Called from SaveEvent: try to save event in Exchange or to Dav Server and if it's Ok, return true
	public static function DoSaveToDav($Params = array(), &$arFields, $oCurEvent = false)
	{
		$sectionId = $Params['sectionId'];
		$bExchange = $Params['bExchange'];
		$bCalDav = $Params['bCalDav'];

		if (isset($oCurEvent['DAV_XML_ID']))
			$arFields['DAV_XML_ID'] = $oCurEvent['DAV_XML_ID'];
		if (isset($oCurEvent['DAV_EXCH_LABEL']))
			$arFields['DAV_EXCH_LABEL'] = $oCurEvent['DAV_EXCH_LABEL'];
		if (isset($oCurEvent['CAL_DAV_LABEL']))
			$arFields['CAL_DAV_LABEL'] = $oCurEvent['CAL_DAV_LABEL'];

		$oSect = CCalendarSect::GetById($sectionId, false);

		if ($oCurEvent)
		{
			if ($oCurEvent['SECT_ID'] != $sectionId)
			{
				$bCalDavCur = CCalendar::IsCalDAVEnabled() && $oCurEvent['CAL_TYPE'] == 'user' && strlen($oCurEvent['CAL_DAV_LABEL']) > 0;
				$bExchangeEnabledCur = CCalendar::IsExchangeEnabled() && $oCurEvent['CAL_TYPE'] == 'user';

				if ($bExchangeEnabledCur || $bCalDavCur)
				{
					$res = CCalendar::DoDeleteToDav(array(
						'bCalDav' => $bCalDavCur,
						'bExchangeEnabled' => $bExchangeEnabledCur,
						'sectionId' => $oCurEvent['SECT_ID']
					), $oCurEvent);

					if ($oCurEvent['DAV_EXCH_LABEL'])
						$oCurEvent['DAV_EXCH_LABEL'] = '';

					if ($res !== true)
						return CCalendar::ThrowError($res);
				}
			}
		}

		$arDavFields = $arFields;
		CCalendarEvent::CheckFields($arDavFields);
		if ($arDavFields['RRULE'] != '')
			$arDavFields['RRULE'] = $arFields['RRULE'];

		$arDavFields['PROPERTY_LOCATION'] = $arDavFields['LOCATION']['NEW'];
		if ($arDavFields['PROPERTY_LOCATION'] !== '')
			$arDavFields['PROPERTY_LOCATION'] = CCalendar::GetTextLocation($arDavFields['PROPERTY_LOCATION']);
		$arDavFields['PROPERTY_IMPORTANCE'] = $arDavFields['IMPORTANCE'];

		$arDavFields['REMIND_SETTINGS'] = '';
		if ($arFields['REMIND'] && is_array($arFields['REMIND']) && is_array($arFields['REMIND'][0]))
			$arDavFields['REMIND_SETTINGS'] = floatVal($arFields['REMIND'][0]['count']).'_'.$arFields['REMIND'][0]['type'];

		// **** Synchronize with CalDav ****
		if ($bCalDav && $oSect['CAL_DAV_CON'] > 0)
		{
			// New event or move existent event to DAV calendar
			if ($arFields['ID'] <= 0 || ($oCurEvent && !$oCurEvent['CAL_DAV_LABEL']))
				$DAVRes = CDavGroupdavClientCalendar::DoAddItem($oSect['CAL_DAV_CON'], $oSect['CAL_DAV_CAL'], $arDavFields);
			else // Edit existent event
				$DAVRes = CDavGroupdavClientCalendar::DoUpdateItem($oSect['CAL_DAV_CON'], $oSect['CAL_DAV_CAL'], $oCurEvent['DAV_XML_ID'], $oCurEvent['CAL_DAV_LABEL'], $arDavFields);

			if (!is_array($DAVRes) || !array_key_exists("XML_ID", $DAVRes))
				return CCalendar::CollectCalDAVErros($DAVRes);

			// // It's ok, we successfuly save event to caldav calendar - and save it to DB
			$arFields['DAV_XML_ID'] = $DAVRes['XML_ID'];
			$arFields['CAL_DAV_LABEL'] = $DAVRes['MODIFICATION_LABEL'];
		}
		// **** Synchronize with Exchange ****
		elseif ($bExchange && $oSect['IS_EXCHANGE'] && strlen($oSect['DAV_EXCH_CAL']) > 0 && $oSect['DAV_EXCH_CAL'] !== 0)
		{
			$ownerId = $arFields['OWNER_ID'];

			$fromTo = CCalendarEvent::GetEventFromToForUser($arDavFields, $ownerId);
			$arDavFields["DATE_FROM"] = $fromTo['DATE_FROM'];
			$arDavFields["DATE_TO"] = $fromTo['DATE_TO'];

			// Convert BBcode to HTML for exchange
			$arDavFields["DESCRIPTION"] = CCalendarEvent::ParseText($arDavFields['DESCRIPTION']);

			// New event  or move existent event to Exchange calendar
			if ($arFields['ID'] <= 0 || ($oCurEvent && !$oCurEvent['DAV_EXCH_LABEL']))
				$exchRes = CDavExchangeCalendar::DoAddItem($ownerId, $oSect['DAV_EXCH_CAL'], $arDavFields);
			else
				$exchRes = CDavExchangeCalendar::DoUpdateItem($ownerId, $oCurEvent['DAV_XML_ID'], $oCurEvent['DAV_EXCH_LABEL'], $arDavFields);

			if (!is_array($exchRes) || !array_key_exists("XML_ID", $exchRes))
				return CCalendar::CollectExchangeErrors($exchRes);

			// It's ok, we successfuly save event to exchange calendar - and save it to DB
			$arFields['DAV_XML_ID'] = $exchRes['XML_ID'];
			$arFields['DAV_EXCH_LABEL'] = $exchRes['MODIFICATION_LABEL'];
		}

		return true;
	}

	public static function DoDeleteToDav($Params, $oCurEvent)
	{
		$sectionId = $Params['sectionId'];
		$bExchangeEnabled = $Params['bExchangeEnabled'];
		$bCalDav = $Params['bCalDav'];
		$oSect = CCalendarSect::GetById($sectionId, false);

		// Google and other caldav
		if ($bCalDav && $oSect['CAL_DAV_CON'] > 0)
		{
			$DAVRes = CDavGroupdavClientCalendar::DoDeleteItem($oSect['CAL_DAV_CON'], $oSect['CAL_DAV_CAL'], $oCurEvent['DAV_XML_ID']);

			if ($DAVRes !== true)
				return CCalendar::CollectCalDAVErros($DAVRes);
		}
		// Exchange
		if ($bExchangeEnabled && $oSect['IS_EXCHANGE'])
		{
			$exchRes = CDavExchangeCalendar::DoDeleteItem($oCurEvent['OWNER_ID'], $oCurEvent['DAV_XML_ID']);
			if ($exchRes !== true)
				return CCalendar::CollectExchangeErrors($exchRes);
		}

		return true;
	}

	// Called from CalDav sync methods
	public static function SyncCalendars($connectionType, $arCalendars, $entityType, $entityId, $connectionId = null)
	{
		self::$silentErrorMode = true;
		//Array(
		//	[0] => Array(
		//		[XML_ID] => calendar
		//		[NAME] => calendar
		//	)
		//	[1] => Array(
		//		[XML_ID] => AQATAGFud...
		//		[NAME] => geewgvwe 1
		//		[DESCRIPTION] => gewgvewgvw
		//		[COLOR] => #FF0000
		//		[MODIFICATION_LABEL] => af720e7c7b6a
		//	)
		//)

		$entityType = strtolower($entityType);
		$entityId = intVal($entityId);

		$tempUser = self::TempUser(false, true);

		$arCalendarNames = array();
		foreach ($arCalendars as $value)
			$arCalendarNames[$value["XML_ID"]] = $value;

		if ($connectionType == 'exchange')
		{
			$xmlIdField = "DAV_EXCH_CAL";
			$xmlIdModLabel = "DAV_EXCH_MOD";
		}
		elseif ($connectionType == 'caldav')
		{
			$xmlIdField = "CAL_DAV_CAL";
			$xmlIdModLabel = "CAL_DAV_MOD";
		}
		else
			return array();

		$arFilter = array(
			'CAL_TYPE' => $entityType,
			'OWNER_ID' => $entityId,
			'!'.$xmlIdField => false
		);

		if ($connectionType == 'caldav')
			$arFilter["CAL_DAV_CON"] = $connectionId;
		if ($connectionType == 'exchange')
			$arFilter["IS_EXCHANGE"] = 1;

		$arResult = array();
		$res = CCalendarSect::GetList(array('arFilter' => $arFilter, 'checkPermissions' => false, 'getPermissions' => false));

		foreach($res as $section)
		{
			$xmlId = $section[$xmlIdField];
			$modificationLabel = $section[$xmlIdModLabel];

			if ($connectionType == 'caldav' && $section['DAV_EXCH_CAL'])
				continue;

			if (empty($xmlId))
				continue;

			if (!array_key_exists($xmlId, $arCalendarNames))
			{
				CCalendarSect::Delete($section["ID"]);
			}
			else
			{
				if ($modificationLabel != $arCalendarNames[$xmlId]["MODIFICATION_LABEL"])
				{
					CCalendarSect::Edit(array(
						'arFields' => array(
							"ID" => $section["ID"],
							"NAME" => $arCalendarNames[$xmlId]["NAME"],
							"OWNER_ID" => $entityType == 'user' ? $entityId : 0,
							"CREATED_BY" => $entityType == 'user' ? $entityId : 0,
							"DESCRIPTION" => $arCalendarNames[$xmlId]["DESCRIPTION"],
							"COLOR" => $arCalendarNames[$xmlId]["COLOR"],
							$xmlIdModLabel => $arCalendarNames[$xmlId]["MODIFICATION_LABEL"],
						)
					));
				}

				if (empty($modificationLabel) || ($modificationLabel != $arCalendarNames[$xmlId]["MODIFICATION_LABEL"]))
				{
					$arResult[] = array(
						"XML_ID" => $xmlId,
						"CALENDAR_ID" => array($section["ID"], $entityType, $entityId)
					);
				}

				unset($arCalendarNames[$xmlId]);
			}
		}

		foreach($arCalendarNames as $key => $value)
		{
			$arFields = Array(
				'CAL_TYPE' => $entityType,
				'OWNER_ID' => $entityId,
				'NAME' => $value["NAME"],
				'DESCRIPTION' => $value["DESCRIPTION"],
				'COLOR' => $value["COLOR"],
				'EXPORT' => array('ALLOW' => false),
				"CREATED_BY" => $entityType == 'user' ? $entityId : 0,
				'ACCESS' => array(),
				$xmlIdField => $key,
				$xmlIdModLabel => $value["MODIFICATION_LABEL"]
			);

			if ($connectionType == 'caldav')
				$arFields["CAL_DAV_CON"] = $connectionId;
			if ($entityType == 'user')
				$arFields["CREATED_BY"] = $entityId;
			if ($connectionType == 'exchange')
				$arFields["IS_EXCHANGE"] = 1;

			$id = intVal(CCalendar::SaveSection(array('arFields' => $arFields, 'bAffectToDav' => false)));
			if ($id)
				$arResult[] = array("XML_ID" => $key, "CALENDAR_ID" => array($id, $entityType, $entityId));
		}

		self::TempUser($tempUser, false);
		self::$silentErrorMode = false;

		return $arResult;
	}

	public static function SyncCalendarItems($connectionType, $calendarId, $arCalendarItems)
	{
		self::$silentErrorMode = true;
		// $arCalendarItems:
		//Array(
		//	[0] => Array(
		//		[XML_ID] => AAATAGFudGlfYn...
		//		[MODIFICATION_LABEL] => DwAAABYAAA...
		//	)
		//	[1] => Array(
		//		[XML_ID] => AAATAGFudGlfYnVn...
		//		[MODIFICATION_LABEL] => DwAAABYAAAAQ...
		//	)
		//)

		list($sectionId, $entityType, $entityId) = $calendarId;
		$entityType = strtolower($entityType);

		if ($connectionType == 'exchange')
			$xmlIdField = "DAV_EXCH_LABEL";
		elseif ($connectionType == 'caldav')
			$xmlIdField = "CAL_DAV_LABEL";
		else
			return array();

		$arCalendarItemsMap = array();
		foreach ($arCalendarItems as $value)
			$arCalendarItemsMap[$value["XML_ID"]] = $value["MODIFICATION_LABEL"];

		$arModified = array();
		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'CAL_TYPE' => $entityType,
					'OWNER_ID' => $entityId,
					'SECTION' => $sectionId
				),
				'getUserfields' => false,
				'parseRecursion' => false,
				'fetchAttendees' => false,
				'fetchMeetings' => false,
				'userId' => $entityType == 'user' ? $entityId : '0'
			)
		);

		foreach ($arEvents as $event)
		{
			if (isset($arCalendarItemsMap[$event["DAV_XML_ID"]]))
			{
				if ($event[$xmlIdField] != $arCalendarItemsMap[$event["DAV_XML_ID"]])
					$arModified[$event["DAV_XML_ID"]] = $event["ID"];

				unset($arCalendarItemsMap[$event["DAV_XML_ID"]]);
			}
			else
			{
				self::DeleteCalendarEvent($calendarId, $event["ID"], self::$userId, $event);
			}
		}

		$arResult = array();
		foreach ($arCalendarItems as $value)
		{
			if (array_key_exists($value["XML_ID"], $arModified))
			{
				$arResult[] = array(
					"XML_ID" => $value["XML_ID"],
					"ID" => $arModified[$value["XML_ID"]]
				);
			}
		}

		foreach ($arCalendarItemsMap as $key => $value)
		{
			$arResult[] = array(
				"XML_ID" => $key,
				"ID" => 0
			);
		}

		self::$silentErrorMode = false;
		return $arResult;
	}

	public static function CollectExchangeErrors($arErrors = array())
	{
		if (count($arErrors) == 0 || !is_array($arErrors))
			return '[EC_NO_EXCH] '.GetMessage('EC_NO_EXCHANGE_SERVER');

		$str = "";
		$errorCount = count($arErrors);
		for($i = 0; $i < $errorCount; $i++)
			$str .= "[".$arErrors[$i][0]."] ".$arErrors[$i][1]."\n";

		return $str;
	}

	public static function CollectCalDAVErros($arErrors = array())
	{
		if (count($arErrors) == 0 || !is_array($arErrors))
			return '[EC_NO_EXCH] '.GetMessage('EC_NO_CAL_DAV');

		$str = "";
		$errorCount = count($arErrors);
		for($i = 0; $i < $errorCount; $i++)
			$str .= "[".$arErrors[$i][0]."] ".$arErrors[$i][1]."\n";

		return $str;
	}

	public static function SyncClearCache()
	{
	}

	public static function CheckCalDavUrl($url, $username, $password)
	{
		$arServer = parse_url($url);

		// Mantis #71074
		if (strpos(strtolower($_SERVER['SERVER_NAME']), strtolower($arServer['host'])) !== false || strpos(strtolower($_SERVER['HTTP_HOST']), strtolower($arServer['host'])) !== false)
			return false;

		return CDavGroupdavClientCalendar::DoCheckCalDAVServer($arServer["scheme"], $arServer["host"], $arServer["port"], $username, $password, $arServer["path"]);
	}

	public static function Color($color = '', $defaultColor = true)
	{
		if ($color != '')
		{
			$color = ltrim(trim(preg_replace('/[^\d|\w]/', '', $color)), "#");
			if (strlen($color) > 6)
				$color = substr($color, 0, 6);
			elseif(strlen($color) < 6)
				$color = '';
		}
		$color = '#'.$color;

		// Default color
		$DEFAULT_COLOR = '#CEE669';
		if ($color == '#')
		{
			if ($defaultColor === true)
				$color = $DEFAULT_COLOR;
			elseif($defaultColor)
				$color = $defaultColor;
			else
				$color = '';
		}

		return $color;
	}

	public static function WeekDayByInd($i, $binv = true)
	{
		if ($binv)
			$arDays = array('SU','MO','TU','WE','TH','FR','SA');
		else
			$arDays = array('MO','TU','WE','TH','FR','SA','SU');
		return isset($arDays[$i]) ? $arDays[$i] : false;
	}

	public static function ConvertDayInd($i)
	{
		return $i == 0 ? 6 : $i - 1;
	}

	public static function Date($timestamp, $bTime = true, $bRound = true, $bCutSeconds = false)
	{
		if ($bRound)
			$timestamp = self::RoundTimestamp($timestamp);

		$format = self::DFormat($bTime);
		if ($bTime && $bCutSeconds)
			$format = str_replace(':s', '', $format);
		return FormatDate($format, $timestamp);
	}

	public static function Timestamp($date, $bRound = true, $bTime = true)
	{
		$timestamp = MakeTimeStamp($date, self::TSFormat($bTime ? "FULL" : "SHORT"));
		if ($bRound)
			$timestamp = self::RoundTimestamp($timestamp);
		return $timestamp;
	}

	public static function _fixTimestamp($timestamp)
	{
		if (date("Z") !== date("Z", $timestamp))
		{
			$timestamp += (date("Z") - date("Z", $timestamp));
		}
		return $timestamp;
	}

	public static function RoundTimestamp($ts)
	{
		return round($ts / 60) * 60; // We don't need for seconds here
	}

	public static function TSFormat($format = "FULL")
	{
		return CSite::GetDateFormat($format);
	}

	public static function DFormat($bTime = true)
	{
		return CDatabase::DateFormatToPHP(CSite::GetDateFormat($bTime ? "FULL" : "SHORT", SITE_ID));
	}

	public static function CutZeroTime($date)
	{
		if (preg_match('/.*\s\d\d:\d\d:\d\d/i', $date))
		{
			$date = trim($date);
			if (substr($date, -9) == ' 00:00:00')
				return substr($date, 0, -9);
			if (substr($date, -3) == ':00')
				return substr($date, 0, -3);
		}
		return $date;
	}

	public static function FormatTime($h = 0, $m = 0)
	{
		$m = intVal($m);

		if ($m > 59)
			$m = 59;
		elseif ($m < 0)
			$m = 0;

		if ($m < 10)
			$m = '0'.$m;

		$h = intVal($h);
		if ($h > 24)
			$h = 24;
		if ($h < 0)
			$h = 0;

		if (IsAmPmMode())
		{
			$ampm = 'am';

			if ($h == 0)
			{
				$h = 12;
			}
			else if ($h == 12)
			{
				$ampm = 'pm';
			}
			else if ($h > 12)
			{
				$ampm = 'pm';
				$h -= 12;
			}

			$res = $h.':'.$m.' '.$ampm;
		}
		else
		{
			$res = (($h < 10) ? '0' : '').$h.':'.$m;
		}
		return $res;
	}

	public static function GetType()
	{
		return self::$type;
	}

	public static function GetOwnerId()
	{
		return self::$ownerId;
	}

	public static function GetUserId()
	{
		if (!self::$userId)
			self::$userId = self::GetCurUserId();
		return self::$userId;
	}

	public static function GetUser($userId, $bPhoto = false)
	{
		global $USER;
		if (is_object($USER) && intVal($userId) == $USER->GetId() && !$bPhoto)
		{
			$user = array(
				'ID' => $USER->GetId(),
				'NAME' => $USER->GetFirstName(),
				'LAST_NAME' => $USER->GetLastName(),
				'SECOND_NAME' => $USER->GetParam('SECOND_NAME'),
				'LOGIN' => $USER->GetLogin()
			);
		}
		else
		{
			$rsUser = CUser::GetByID(intVal($userId));
			$user = $rsUser->Fetch();
		}
		return $user;
	}

	public static function GetUserName($user)
	{
		if (!is_array($user) && intVal($user) > 0)
			$user = self::GetUser($user);

		if(!$user || !is_array($user))
			return '';

		return CUser::FormatName(self::$userNameTemplate, $user, self::$showLogin, false);
	}

	public static function GetUserAvatar($arUser = array(), $arParams = array())
	{
		if (!empty($arUser["PERSONAL_PHOTO"]))
		{
			if (empty($arParams['AVATAR_SIZE']))
			{
				$arParams['AVATAR_SIZE'] = 42;
			}
			$arFileTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array('width' => $arParams['AVATAR_SIZE'], 'height' => $arParams['AVATAR_SIZE']),
				BX_RESIZE_IMAGE_EXACT,
				false
			);
			$avatar_src = $arFileTmp['src'];
		}
		else
		{
			$avatar_src = false;
		}
		return $avatar_src;
	}

	public static function GetUserAvatarSrc($arUser = array(), $arParams = array())
	{
		$avatar_src = self::GetUserAvatar($arUser, $arParams);
		if ($avatar_src === false)
			$avatar_src = '/bitrix/images/1.gif';
		return $avatar_src;
	}

	public static function GetUserUrl($userId = 0, $pathToUser = "")
	{
		if ($pathToUser == '')
		{
			if (self::$pathToUser == '')
			{
				if (empty(self::$pathesForSite))
					self::$pathesForSite = self::GetPathes(SITE_ID);
				self::$pathToUser = self::$pathesForSite['path_to_user'];
			}
			$pathToUser = self::$pathToUser;
		}

		return CUtil::JSEscape(CComponentEngine::MakePathFromTemplate($pathToUser, array("user_id" => $userId, "USER_ID" => $userId)));
	}

	public static function OutputJSRes($reqId = false, $Res = false)
	{
		if ($Res === false)
			return;
		if ($reqId === false)
			$reqId = intVal($_REQUEST['reqId']);
		if (!$reqId)
			return;
		?>
		<script>
			top.BXCRES['<?= $reqId?>'] = <?= CUtil::PhpToJSObject($Res)?>
		</script>
		<?
	}

	public static function GetAccessTasks($binging = 'calendar_section')
	{
		\Bitrix\Main\Localization\Loc::loadLanguageFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/calendar/admin/task_description.php");

		if (is_array(self::$arAccessTask[$binging]))
			return self::$arAccessTask[$binging];

		$bIntranet = self::IsIntranetEnabled();
		$arTasks = Array();
		$res = CTask::GetList(Array('ID' => 'asc'), Array('MODULE_ID' => 'calendar', 'BINDING' => $binging));
		while($arRes = $res->Fetch())
		{
			if (!$bIntranet && (strtolower($arRes['NAME']) == 'calendar_view_time' || strtolower($arRes['NAME']) == 'calendar_view_title'))
				continue;

			$name = '';
			if ($arRes['SYS'])
				$name = GetMessage('TASK_NAME_'.strtoupper($arRes['NAME']));
			if (strlen($name) == 0)
				$name = $arRes['NAME'];

			$arTasks[$arRes['ID']] = array(
				'name' => $arRes['NAME'],
				'title' => $name
			);
		}

		self::$arAccessTask[$binging] = $arTasks;

		return $arTasks;
	}

	public static function GetAccessTasksByName($binging = 'calendar_section', $name = 'calendar_denied')
	{
		$arTasks = CCalendar::GetAccessTasks($binging);

		foreach($arTasks as $id => $task)
			if ($task['name'] == $name)
				return $id;

		return false;
	}

	public static function PushAccessNames($arCodes = array())
	{
		foreach($arCodes as $code)
		{
			if (!array_key_exists($code, self::$accessNames))
			{
				self::$accessNames[$code] = null;
			}
		}
	}

	public static function GetAccessNames()
	{
		$arCodes = array();
		foreach (self::$accessNames as $code => $name)
		{
			if ($name === null)
				$arCodes[] = $code;
		}

		if ($arCodes)
		{
			$access = new CAccess();
			$arNames = $access->GetNames($arCodes);
			foreach($arNames as $code => $name)
			{
				self::$accessNames[$code] = trim(htmlspecialcharsbx($name['provider'].' '.$name['name']));
			}
		}

		return self::$accessNames;
	}

	public static function GetPath($type = '', $ownerId = '', $hard = false)
	{
		if (self::$path == '' || $hard)
		{
			$path = '';
			if (empty($type))
				$type = self::$type;
			if (!empty($type))
			{
				if ($type == 'user')
					$path = COption::GetOptionString('calendar', 'path_to_user_calendar', "/company/personal/user/#user_id#/calendar/");
				elseif($type == 'group')
					$path = COption::GetOptionString('calendar', 'path_to_group_calendar', "/workgroups/group/#group_id#/calendar/");

				if (!COption::GetOptionString('calendar', 'pathes_for_sites', true))
				{
					$siteId = self::GetSiteId();
					$pathes = self::GetPathes();
					if (isset($pathes[$siteId]))
					{
						if ($type == 'user' && isset($pathes[$siteId]['path_to_user_calendar']))
							$path = $pathes[$siteId]['path_to_user_calendar'];
						elseif($type == 'group' && isset($pathes[$siteId]['path_to_group_calendar']))
							$path = $pathes[$siteId]['path_to_group_calendar'];
					}
				}

				if (empty($ownerId))
					$ownerId = self::$ownerId;

				if (!empty($path) && !empty($ownerId))
				{
					if ($type == 'user')
						$path = str_replace(array('#user_id#', '#USER_ID#'), $ownerId, $path);
					elseif($type == 'group')
						$path = str_replace(array('#group_id#', '#GROUP_ID#'), $ownerId, $path);
				}

				$path = CCalendar::GetServerPath().$path;
			}
		}
		else
		{
			$path = self::$path;
		}

		return $path;
	}

	public static function SetLocation($old = '', $new = '', $Params = array())
	{
		$tzEnabled = CTimeZone::Enabled();
		if ($tzEnabled)
			CTimeZone::Disable();

		$res = '';
		// *** ADD MEETING ROOM ***
		$locOld = CCalendar::ParseLocation($old);
		$locNew = CCalendar::ParseLocation($new);

		$allowReserveMeeting = isset($Params['allowReserveMeeting']) ? $Params['allowReserveMeeting'] : self::$allowReserveMeeting;
		$allowVideoMeeting = isset($Params['allowVideoMeeting']) ? $Params['allowVideoMeeting'] : self::$allowVideoMeeting;

		$RMiblockId = isset($Params['RMiblockId']) ? $Params['RMiblockId'] : self::$settings['rm_iblock_id'];
		$VMiblockId = isset($Params['VMiblockId']) ? $Params['VMiblockId'] : self::$settings['vr_iblock_id'];

		// If not allowed
		if (!$allowReserveMeeting && !$allowVideoMeeting)
		{
			$res = $locNew['mrid'] ? $locNew['str'] : $new;
		}
		else
		{
			if ($locOld['mrid'] !== false && $locOld['mrevid'] !== false) // Release MR
			{
				if($allowVideoMeeting && $locOld['mrid'] == $VMiblockId) // video meeting
				{
					CCalendar::ReleaseVideoRoom(array(
						'mrevid' => $locOld['mrevid'],
						'mrid' => $locOld['mrid'],
						'VMiblockId' => $VMiblockId
					));
				}
				elseif($allowReserveMeeting)
				{
					CCalendar::ReleaseMeetingRoom(array(
						'mrevid' => $locOld['mrevid'],
						'mrid' => $locOld['mrid'],
						'RMiblockId' => $RMiblockId
					));
				}
			}

			if ($locNew['mrid'] !== false) // Reserve MR
			{
				if ($Params['bRecreateReserveMeetings'])
				{
					// video meeting
					if($allowVideoMeeting && $locNew['mrid'] == $VMiblockId)
					{
						$mrevid = CCalendar::ReserveVideoRoom(array(
							'mrid' => $locNew['mrid'],
							'dateFrom' => $Params['dateFrom'],
							'dateTo' => $Params['dateTo'],
							'name' => $Params['name'],
							'description' => GetMessage('EC_RESERVE_FOR_EVENT').': '.$Params['name'],
							'persons' => $Params['persons'],
							'members' => $Params['attendees'],
							'VMiblockId' => $VMiblockId
						));
					}
					elseif ($allowReserveMeeting)
					{
						$mrevid = CCalendar::ReserveMeetingRoom(array(
							'RMiblockId' => $RMiblockId,
							'mrid' => $locNew['mrid'],
							'dateFrom' => $Params['dateFrom'],
							'dateTo' => $Params['dateTo'],
							'name' => $Params['name'],
							'description' => GetMessage('EC_RESERVE_FOR_EVENT').': '.$Params['name'],
							'persons' => $Params['persons'],
							'members' => $Params['attendees']
						));
					}
				}
				elseif(is_array($locNew) && $locNew['mrevid'] !== false)
				{
					$mrevid = $locNew['mrevid'];
				}

				if ($mrevid && $mrevid != 'reserved' && $mrevid != 'expire' && $mrevid > 0)
					$locNew = 'ECMR_'.$locNew['mrid'].'_'.$mrevid;
				else
					$locNew = '';
			}
			else
			{
				$locNew = $locNew['str'];
			}

			$res = $locNew;
		}

		if ($tzEnabled)
			CTimeZone::Enable();

		return $res;
	}

	public static function GetOuterUrl()
	{
		return self::$outerUrl;
	}

	public static function ManageConnections($arConnections = array())
	{
		global $APPLICATION;
		$bSync = false;
		$l = count($arConnections);

		for ($i = 0; $i < $l; $i++)
		{
			$con = $arConnections[$i];
			$conId = intVal($con['id']);
			if ($conId <= 0) // It's new connection
			{
				if ($con['del'] != 'Y')
				{
					if(!CCalendar::CheckCalDavUrl($con['link'], $con['user_name'], $con['pass']))
						return GetMessage("EC_CALDAV_URL_ERROR");

					CDavConnection::Add(array("ENTITY_TYPE" => 'user', "ENTITY_ID" => self::$ownerId, "ACCOUNT_TYPE" => 'caldav', "NAME" => $con['name'], "SERVER" => $con['link'], "SERVER_USERNAME" => $con['user_name'], "SERVER_PASSWORD" => $con['pass']));
					$bSync = true;
				}
			}
			elseif ($con['del'] != 'Y') // Edit connection
			{
				$arFields = array(
					"NAME" => $con['name'],
					"SERVER" => $con['link'],
					"SERVER_USERNAME" => $con['user_name']
				);
				if ($con['pass'] !== 'bxec_not_modify_pass')
					$arFields["SERVER_PASSWORD"] = $con['pass'];

				$resCon = CDavConnection::GetList(array("ID" => "ASC"), array("ID" => $conId));
				if ($arCon = $resCon->Fetch())
				{
					if($arCon['ACCOUNT_TYPE'] !== 'caldav_google_oauth')
					{
						CDavConnection::Update($conId, $arFields);
					}
				}

				if (is_array($con['sections']))
				{
					foreach ($con['sections'] as $sectId => $active)
					{
						$sectId = intVal($sectId);

						if(CCalendar::IsPersonal() || CCalendarSect::CanDo('calendar_edit_section', $sectId, self::$userId))
						{
							CCalendarSect::Edit(array('arFields' => array("ID" => $sectId, "ACTIVE" => $active == "Y" ? "Y" : "N")));
						}
					}
				}

				$bSync = true;
			}
			else
			{
				CCalendar::RemoveConnection(array('id' => $conId, 'del_calendars' => $con['del_calendars']));
			}
		}

		if($err = $APPLICATION->GetException())
		{
			return $err->GetString();
		}

		if ($bSync)
			CDavGroupdavClientCalendar::DataSync("user", self::$ownerId);

		$res = CDavConnection::GetList(
			array("ID" => "DESC"),
			array(
				"ENTITY_TYPE" => "user",
				"ENTITY_ID" => self::$ownerId
			),
			false,
			false
		);

		while($arCon = $res->Fetch())
		{
			if ($arCon['ACCOUNT_TYPE'] == 'caldav_google_oauth' || $arCon['ACCOUNT_TYPE'] == 'caldav')
			{
				if(strpos($arCon['LAST_RESULT'], "[200]") === false)
					return GetMessage('EC_CALDAV_CONNECTION_ERROR', array('#CONNECTION_NAME#' => $arCon['NAME'], '#ERROR_STR#' => $arCon['LAST_RESULT']));
			}
		}

		return true;
	}

	public static function RemoveConnection($connection = array())
	{
		// Clean sections
		$res = CCalendarSect::GetList(array('arFilter' => array('CAL_TYPE' => 'user', 'OWNER_ID' => self::$ownerId, 'CAL_DAV_CON' => $connection['id'])));

		foreach ($res as $sect)
		{
			if ($connection['del_calendars'] == 'Y') // Delete all callendars from this connection
				CCalendarSect::Delete($sect['ID'], false);
			else
				CCalendarSect::Edit(array('arFields' => array("ID" => $sect['ID'], "CAL_DAV_CON" => '', 'CAL_DAV_CAL' => '', 'CAL_DAV_MOD' => '')));
		}

		// Delete Google oauth token if it's google oauth caldav connection
		$resCon = CDavConnection::GetList(array("ID" => "ASC"), array("ID" => $connection['id']));
		if ($arCon = $resCon->Fetch())
		{
			$googleCalDavStatus = CCalendar::GetGoogleCalendarConnection();
			if($googleCalDavStatus['googleCalendarPrimaryId'] && $arCon['ACCOUNT_TYPE'] == 'caldav_google_oauth')
			{
				$serverPath = 'https://apidata.googleusercontent.com/caldav/v2/'.$googleCalDavStatus['googleCalendarPrimaryId'].'/user';
				if ($arCon['SERVER'] == $serverPath)
				{
					if (CModule::IncludeModule('socialservices'))
					{
						$client = new CSocServGoogleOAuth(CCalendar::GetCurUserId());
						$client->getEntityOAuth()->addScope(array('https://www.googleapis.com/auth/calendar', 'https://www.googleapis.com/auth/calendar.readonly'));

						// Delete stored tokens
						$client->getEntityOAuth()->deleteStorageTokens();
					}
				}
			}
		}

		// Delete dav connections
		CDavConnection::Delete($connection['id']);
	}

	public static function GetTypeByExternalId($externalId = false)
	{
		if ($externalId)
		{
			$res = CCalendarType::GetList(array('arFilter' => array('EXTERNAL_ID' => $externalId)));
			if ($res && $res[0])
				return $res[0]['XML_ID'];
		}
		return false;
	}

	// TODO: cache it!!!!!!
	public static function GetMeetingSection($userId, $autoCreate = false)
	{
		if (isset(self::$meetingSections[$userId]))
			return self::$meetingSections[$userId];

		$result = false;
		if ($userId > 0)
		{
			$set = CCalendar::GetUserSettings($userId);

			$result = $set['meetSection'];

			if($result && !CCalendarSect::GetById($result, false, false))
				$result = false;

			if (!$result)
			{
				$res = CCalendarSect::GetList(array(
					'arFilter' => array(
						'CAL_TYPE' => 'user',
						'OWNER_ID' => $userId
					),
					'checkPermissions' => false
				));
				if ($res && count($res) > 0 && $res[0]['ID'])
					$result = $res[0]['ID'];

				if (!$result && $autoCreate)
				{
					$defCalendar = CCalendarSect::CreateDefault(array(
						'type' => 'user',
						'ownerId' => $userId
					));
					if ($defCalendar && $defCalendar['ID'] > 0)
						$result = $defCalendar['ID'];
				}

				if($result)
				{
					$set['meetSection'] = $result;
					CCalendar::SetUserSettings($set, $userId);
				}
			}
		}

		self::$meetingSections[$userId] = $result;
		return $result;
	}

	public static function GetCurUserMeetingSection($bCreate = false)
	{
		if (!isset(self::$userMeetingSection) || !self::$userMeetingSection)
			self::$userMeetingSection = CCalendar::GetMeetingSection(self::$userId, $bCreate);
		return self::$userMeetingSection;
	}

	public static function SetCurUserMeetingSection($userMeetingSection)
	{
		self::$userMeetingSection = $userMeetingSection;
	}

	public static function CachePath()
	{
		return self::$cachePath;
	}

	public static function CacheTime($time = false)
	{
		if ($time !== false)
			self::$cacheTime = $time;
		return self::$cacheTime;
	}

	public static function ClearCache($arPath = false)
	{
		global $CACHE_MANAGER;

		$CACHE_MANAGER->ClearByTag("CALENDAR_EVENT_LIST");

		if ($arPath === false)
			$arPath = array(
				'access_tasks',
				'type_list',
				'section_list',
				'attendees_list',
				'event_list'
			);
		elseif (!is_array($arPath))
			$arPath = array($arPath);

		if (is_array($arPath) && count($arPath) > 0)
		{
			$cache = new CPHPCache;
			foreach($arPath as $path)
				if ($path != '')
					$cache->CleanDir(CCalendar::CachePath().$path);
		}
	}

	public static function ClearExchangeHtml($html = "")
	{
		// Echange in chrome puts chr(13) instead of \n
		$html = str_replace(chr(13), "\n", trim($html, chr(13)));
		$html = preg_replace("/(\s|\S)*<a\s*name=\"bm_begin\"><\/a>/is".BX_UTF_PCRE_MODIFIER,"", $html);
		$html = preg_replace("/<br>(\n|\r)+/is".BX_UTF_PCRE_MODIFIER,"<br>", $html);
		return self::ParseHTMLToBB($html);
	}

	public static function ParseHTMLToBB($html = "")
	{
		$id = AddEventHandler("main", "TextParserBeforeTags", Array("CCalendar", "_ParseHack"));

		$TextParser = new CTextParser();
		$TextParser->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "N", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "Y", "VIDEO" => "Y", "TABLE" => "Y", "CUT_ANCHOR" => "Y", "ALIGN" => "Y");

		$html = $TextParser->convertText($html);

		$html = htmlspecialcharsback($html);
		// Replace BR
		$html = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"\n", $html);
		// Kill &nbsp;
		$html = preg_replace("/&nbsp;/is".BX_UTF_PCRE_MODIFIER,"", $html);
		// Kill tags
		$html = preg_replace("/\<([^>]*?)>/is".BX_UTF_PCRE_MODIFIER,"", $html);
		$html = htmlspecialcharsbx($html);

		RemoveEventHandler("main", "TextParserBeforeTags", $id);

		return $html;
	}

	public static function _ParseHack(&$text, &$TextParser)
	{
		$text = preg_replace(array("/\&lt;/is".BX_UTF_PCRE_MODIFIER, "/\&gt;/is".BX_UTF_PCRE_MODIFIER),array('<', '>'),$text);

		$text = preg_replace("/\<br\s*\/*\>/is".BX_UTF_PCRE_MODIFIER,"", $text);
		$text = preg_replace("/\<(\w+)[^>]*\>(.+?)\<\/\\1[^>]*\>/is".BX_UTF_PCRE_MODIFIER,"\\2",$text);
		$text = preg_replace("/\<*\/li\>/is".BX_UTF_PCRE_MODIFIER,"", $text);

		$text = str_replace(array("<", ">"),array("&lt;", "&gt;"),$text);

		$TextParser->allow = array();
		return true;
	}

	public static function GetUserManagers($userId, $bReturnIds = false)
	{
		if (!isset(self::$userManagers[$userId]))
		{
			$rsUser = CUser::GetByID($userId);
			if($arUser = $rsUser->Fetch())
			{
				self::SetUserDepartment($userId, $arUser["UF_DEPARTMENT"]);
				self::$userManagers[$userId] = CIntranetUtils::GetDepartmentManager($arUser["UF_DEPARTMENT"], $arUser["ID"], true);
			}
			else
			{
				self::$userManagers[$userId] = false;
			}
		}

		if (!$bReturnIds)
			return self::$userManagers[$userId];

		$res = array();
		if (is_array(self::$userManagers[$userId]))
		{
			foreach(self::$userManagers[$userId] as $user)
				$res[] = $user['ID'];
		}
		return $res;
	}

	public static function GetOffset($userId = false)
	{
		if ($userId > 0)
		{
			if (!isset(self::$arTimezoneOffsets[$userId]))
			{
				if (!CTimeZone::Enabled())
				{
					CTimeZone::Enable();
					$offset = CTimeZone::GetOffset($userId);
					CTimeZone::Disable();
				}
				else
				{
					$offset = CTimeZone::GetOffset($userId);
				}
				self::$arTimezoneOffsets[$userId] = $offset;
			}
			else
			{
				$offset = self::$arTimezoneOffsets[$userId];
			}
		}
		else
		{
			if (!isset(self::$offset))
			{
				if (!CTimeZone::Enabled())
				{
					CTimeZone::Enable();
					$offset = CTimeZone::GetOffset();
					CTimeZone::Disable();
				}
				else
				{
					$offset = CTimeZone::GetOffset();
				}
				self::$offset = $offset;
			}
			else
			{
				$offset = self::$offset;
			}
		}
		return $offset;
	}

	public static function SetOffset($userId = false, $value = 0)
	{
		if ($userId === false)
			self::$offset = $value;
		else
			self::$arTimezoneOffsets[$userId] = $value;
	}

	public static function GetWeekStart()
	{
		if (!isset(self::$weekStart))
		{
			$days = array('1' => 'MO', '2' => 'TU', '3' => 'WE', '4' => 'TH', '5' => 'FR', '6' => 'SA', '0' => 'SU');
			self::$weekStart = $days[CSite::GetWeekStart()];

			if (!in_array(self::$weekStart, $days))
				self::$weekStart = 'MO';
		}

		return self::$weekStart;
	}

	public static function IsPersonal($type = false, $ownerId = false, $userId = false)
	{
		if (!$type)
			$type = self::$type;
		if(!$ownerId)
			$ownerId = self::$ownerId;
		if(!$userId)
			$userId = self::$userId;

		return $type == 'user' && $ownerId == $userId;
	}

	public static function GetPathForCalendarEx($userId = 0)
	{
		$bExtranet = CModule::IncludeModule('extranet');
		// It's extranet user
		if ($bExtranet && self::IsExtranetUser($userId))
		{
			$siteId = CExtranet::GetExtranetSiteID();
		}
		else
		{
			if ($bExtranet && !self::IsExtranetUser($userId))
				$siteId = CSite::GetDefSite();
			else
				$siteId = self::GetSiteId();

			if (self::$siteId == $siteId && isset(self::$pathesForSite) && is_array(self::$pathesForSite))
				self::$pathes[$siteId] = self::$pathesForSite;
		}

		if (!isset(self::$pathes[$siteId]) || !is_array(self::$pathes[$siteId]))
			self::$pathes[$siteId] = self::GetPathes($siteId);

		$calendarUrl = self::$pathes[$siteId]['path_to_user_calendar'];
		$calendarUrl = str_replace(array('#user_id#', '#USER_ID#'), $userId, $calendarUrl);
		$calendarUrl = CCalendar::GetServerPath().$calendarUrl;

		return $calendarUrl;
	}

	public static function SetUserDepartment($userId = 0, $dep = array())
	{
		if (!is_array($dep))
			$dep = array();
		self::$arUserDepartment[$userId] = $dep;
	}

	public static function GetUserDepartment($userId = 0)
	{
		if (!isset(self::$arUserDepartment[$userId]))
		{
			$rsUser = CUser::GetByID($userId);
			if($arUser = $rsUser->Fetch())
				self::SetUserDepartment($userId, $arUser["UF_DEPARTMENT"]);
		}

		return self::$arUserDepartment[$userId];
	}

	public static function IsExtranetUser($userId = 0)
	{
		return !count(self::GetUserDepartment($userId));
	}

	public static function IsSocnetAdmin()
	{
		if (!isset(self::$bCurUserSocNetAdmin))
			self::$bCurUserSocNetAdmin = self::IsSocNet() && CSocNetUser::IsCurrentUserModuleAdmin();

		return self::$bCurUserSocNetAdmin;
	}

	public static function GetMaxTimestamp()
	{
		return self::CALENDAR_MAX_TIMESTAMP;
	}

	public static function GetMaxDate()
	{
		if (!self::$CALENDAR_MAX_DATE)
		{
			$date = new DateTime();
			$date->setDate(2038, 1, 1);
			self::$CALENDAR_MAX_DATE = self::Date($date->getTimestamp(), false);
		}
		return self::$CALENDAR_MAX_DATE;
	}

	public static function GetSiteId()
	{
		if (!self::$siteId)
			self::$siteId = SITE_ID;
		return self::$siteId;
	}

	public static function GetDayLen()
	{
		return self::DAY_LENGTH;
	}

	public static function GetCurUserId()
	{
		global $USER;

		if (!isset(self::$curUserId))
		{
			if (is_object($USER) && $USER->IsAuthorized())
				self::$curUserId = $USER->GetId();
			else
				self::$curUserId = 0;
		}

		return self::$curUserId;
	}

	public static function GetDestinationUsers($arCodes, $bFetchUsers = false)
	{
		global $DB;
		$arUsers = array();

		$arCodes2 = array();
		if (!$bFetchUsers)
		{
			foreach($arCodes as $code)
			{
				if (substr($code, 0, 1) === 'U' && $code !== 'UA')
				{
					$id = intVal(substr($code, 1));
					if($id > 0)
					{
						$arUsers[] = $id;
						continue;
					}
				}

				if (substr($code, 0, 2) === 'SG')
					$arCodes2[] = $code.'_K';
				$arCodes2[] = $code;
			}
			$bUnique = count($arCodes2) > 0 && count($arUsers) > 0;
		}
		else
		{
			foreach($arCodes as $code)
			{
				if (substr($code, 0, 2) === 'SG')
					$arCodes2[] = $code.'_K';
				$arCodes2[] = $code;
			}
			$bUnique = false;
		}

		$obUserFieldsSql = new CUserTypeSQL();
		$obUserFieldsSql->SetEntity("USER", "USER_ID");
		$obUserFieldsSql->SetFilter(array(
			"!UF_DEPARTMENT" => false
		));
		$where = $obUserFieldsSql->GetFilter();
		$join = $obUserFieldsSql->GetJoin("UA.USER_ID");

		if ($where == '')
		{
			$where = '1=1';
		}

		$strCodes = in_array('UA', $arCodes2) ? "'G2'" : "'".join("','", $arCodes2)."'";

		if ($bFetchUsers)
		{
			$strSql = "SELECT DISTINCT UA.USER_ID, U.LOGIN, U.NAME, U.LAST_NAME, U.SECOND_NAME, U.EMAIL, U.PERSONAL_PHOTO, U.WORK_POSITION ".
				"FROM b_user_access UA
				INNER JOIN b_user U ON (U.ID=UA.USER_ID)".
				$join.
				" WHERE ACCESS_CODE in (".$strCodes.") AND U.ACTIVE='Y' AND ".$where;
		}
		else
		{
			$strSql = "SELECT DISTINCT USER_ID ".
				"FROM b_user_access UA
				INNER JOIN b_user U ON (U.ID=UA.USER_ID)".
				$join.
				" WHERE ACCESS_CODE in (".$strCodes.") AND U.ACTIVE='Y' AND ".$where;
		}

		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if ($bFetchUsers)
		{
			while($ar = $res->Fetch())
			{
				if ($ar > 0)
					$arUsers[] = $ar;
			}
		}
		else
		{
			while($ar = $res->Fetch())
			{
				if($ar['USER_ID'] > 0)
					$arUsers[] = $ar['USER_ID'];
			}
		}

		if ($bUnique)
			$arUsers = array_unique($arUsers);

		return $arUsers;
	}

	public static function GetAttendeesMessage($cnt = 0)
	{
		if (
			($cnt % 100) > 10
			&& ($cnt % 100) < 20
		)
			$suffix = 5;
		else
			$suffix = $cnt % 10;

		return GetMessage("EC_ATTENDEE_".$suffix, Array("#NUM#" => $cnt));
	}

	public static function GetMoreAttendeesMessage($cnt = 0)
	{
		if (
			($cnt % 100) > 10
			&& ($cnt % 100) < 20
		)
			$suffix = 5;
		else
			$suffix = $cnt % 10;

		return GetMessage("EC_ATTENDEE_MORE_".$suffix, Array("#NUM#" => $cnt));
	}

	public static function GetFormatedDestination($codes = array())
	{
		$ac = CSocNetLogTools::FormatDestinationFromRights($codes, array(
			"CHECK_PERMISSIONS_DEST" => "Y",
			"DESTINATION_LIMIT" => 100000,
			"NAME_TEMPLATE" => "#NAME# #LAST_NAME#",
			"PATH_TO_USER" => "/company/personal/user/#user_id#/",
		));

		return $ac;
	}

	public static function GetFromToHtml($fromTs = false, $toTs = false, $skipTime = false, $dtLength = 0)
	{
		if (intVal($fromTs) != $fromTs)
			$fromTs = self::Timestamp($fromTs);
		if (intVal($toTs) != $toTs)
			$toTs = self::Timestamp($toTs);

		// Formats
		$formatShort = CCalendar::DFormat(false);
		$formatFull = CCalendar::DFormat(true);
		$formatTime = str_replace($formatShort, '', $formatFull);
		if ($formatTime == $formatFull)
			$formatTime = "H:i";
		else
			$formatTime = str_replace(':s', '', $formatTime);

		if ($skipTime)
		{
			if ($dtLength == self::DAY_LENGTH) // One full day event
			{
				$html = FormatDate(array(
					"tommorow" => "tommorow",
					"today" => "today",
					"yesterday" => "yesterday",
					"-" => $formatShort,
					"" => $formatShort,
				), $fromTs, time() + CTimeZone::GetOffset());
				$html .= ', '.GetMessage('EC_VIEW_FULL_DAY');
			}
			else // Event for several days
			{
				$from = FormatDate(array(
					"tommorow" => "tommorow",
					"today" => "today",
					"yesterday" => "yesterday",
					"-" => $formatShort,
					"" => $formatShort,
				), $fromTs, time() + CTimeZone::GetOffset());

				$to = FormatDate(array(
					"tommorow" => "tommorow",
					"today" => "today",
					"yesterday" => "yesterday",
					"-" => $formatShort,
					"" => $formatShort,
				), $toTs - self::DAY_LENGTH, time() + CTimeZone::GetOffset());

				$html = GetMessage('EC_VIEW_DATE_FROM_TO', array('#DATE_FROM#' => $from, '#DATE_TO#' => $to));
			}
		}
		else
		{
			// Event during one day
			if(date('dmY', $fromTs) == date('dmY', $toTs))
			{
				$html = FormatDate(array(
					"tommorow" => "tommorow",
					"today" => "today",
					"yesterday" => "yesterday",
					"-" => $formatShort,
					"" => $formatShort,
				), $fromTs, time() + CTimeZone::GetOffset());

				$html .= ', '.GetMessage('EC_VIEW_TIME_FROM_TO_TIME', array('#TIME_FROM#' => FormatDate($formatTime, $fromTs), '#TIME_TO#' => FormatDate($formatTime, $toTs)));
			}
			else
			{
				$html = GetMessage('EC_VIEW_DATE_FROM_TO', array('#DATE_FROM#' => FormatDate($formatFull, $fromTs, time() + CTimeZone::GetOffset()), '#DATE_TO#' => FormatDate($formatFull, $toTs, time() + CTimeZone::GetOffset())));
			}
		}

		return $html;
	}

	public static function GetSocNetDestination($user_id = false, $selected = array())
	{
		global $CACHE_MANAGER;

		if (!is_array($selected))
			$selected = array();

		if (method_exists('CSocNetLogDestination','GetDestinationSort'))
		{
			$DESTINATION = array(
				'LAST' => array(),
				'DEST_SORT' => CSocNetLogDestination::GetDestinationSort(array("DEST_CONTEXT" => "CALENDAR"))
			);

			CSocNetLogDestination::fillLastDestination($DESTINATION['DEST_SORT'], $DESTINATION['LAST']);
		}
		else
		{
			$DESTINATION = array(
				'LAST' => array(
					'SONETGROUPS' => CSocNetLogDestination::GetLastSocnetGroup(),
					'DEPARTMENT' => CSocNetLogDestination::GetLastDepartment(),
					'USERS' => CSocNetLogDestination::GetLastUser()
				)
			);
		}

		if (!$user_id)
			$user_id = CCalendar::GetCurUserId();

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = 'blog_post_form_dest_'.$user_id;
		$cacheDir = '/blog/form/dest/'.SITE_ID.'/'.$user_id;

		$obCache = new CPHPCache;
		if($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
		{
			$DESTINATION['SONETGROUPS'] = $obCache->GetVars();
		}
		else
		{
			$obCache->StartDataCache();
			$DESTINATION['SONETGROUPS'] = CSocNetLogDestination::GetSocnetGroup(Array('features' => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post"))));
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$CACHE_MANAGER->StartTagCache($cacheDir);
				foreach($DESTINATION['SONETGROUPS'] as $val)
				{
					$CACHE_MANAGER->RegisterTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->RegisterTag("sonet_group_".$val["entityId"]);
				}
				$CACHE_MANAGER->RegisterTag("sonet_user2group_U".$user_id);
				$CACHE_MANAGER->EndTagCache();
			}
			$obCache->EndDataCache($DESTINATION['SONETGROUPS']);
		}

		$arDestUser = Array();
		$DESTINATION['SELECTED'] = Array();
		foreach ($selected as $ind => $code)
		{
			if (substr($code, 0 , 2) == 'DR')
			{
				$DESTINATION['SELECTED'][$code] = "department";
			}
			elseif (substr($code, 0 , 2) == 'UA')
			{
				$DESTINATION['SELECTED'][$code] = "groups";
			}
			elseif (substr($code, 0 , 2) == 'SG')
			{
				$DESTINATION['SELECTED'][$code] = "sonetgroups";
			}
			elseif (substr($code, 0 , 1) == 'U')
			{
				$DESTINATION['SELECTED'][$code] = "users";
				$arDestUser[] = str_replace('U', '', $code);
			}
		}

		// intranet structure
		$arStructure = CSocNetLogDestination::GetStucture();
		//$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
		$DESTINATION['DEPARTMENT'] = $arStructure['department'];
		$DESTINATION['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
		$DESTINATION['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

		if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
		{
			$DESTINATION['EXTRANET_USER'] = 'Y';
			$DESTINATION['USERS'] = CSocNetLogDestination::GetExtranetUser();
		}
		else
		{
			if (is_array($DESTINATION['LAST']['USERS']))
			{
				foreach ($DESTINATION['LAST']['USERS'] as $value)
				{
					$arDestUser[] = str_replace('U', '', $value);
				}
			}

			$DESTINATION['EXTRANET_USER'] = 'N';
			$DESTINATION['USERS'] = CSocNetLogDestination::GetUsers(Array('id' => $arDestUser));
		}

		$users = array();
		foreach ($DESTINATION['USERS'] as $key => $entry)
		{
			if ($entry['isExtranet'] == 'N')
				$users[$key] = $entry;
		}
		$DESTINATION['USERS'] = $users;

		return $DESTINATION;
	}


	public static function UpdateUFRights($files, $rights, $ufEntity = array())
	{
		static $arTasks = null;

		if (!is_array($rights) || sizeof($rights) <= 0)
			return false;
		if ($files===null || $files===false)
			return false;
		if (!is_array($files))
			$files = array($files);
		if (sizeof($files) <= 0)
			return false;
		if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('webdav'))
			return false;

		$arFiles = array();
		foreach($files as $id)
		{
			$id = intval($id);
			if (intval($id) > 0)
				$arFiles[] = $id;
		}

		if (sizeof($arFiles) <= 0)
			return false;

		if ($arTasks == null)
			$arTasks = CWebDavIblock::GetTasks();

		$arCodes = array();
		foreach($rights as $value)
		{
			if (substr($value, 0, 2) === 'SG')
				$arCodes[] = $value.'_K';
			$arCodes[] = $value;
		}
		$arCodes = array_unique($arCodes);

		$i=0;
		$arViewRights = $arEditRights = array();
		$curUserID = 'U'.$GLOBALS['USER']->GetID();
		foreach($arCodes as $right)
		{
			if ($curUserID == $right) // do not override owner's rights
				continue;
			$key = 'n' . $i++;
			$arViewRights[$key] = array(
				'GROUP_CODE' => $right,
				'TASK_ID' => $arTasks['R'],
			);
		}

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(array(), array('ID' => $arFiles, 'SHOW_NEW' => 'Y'), false, false, array('ID', 'NAME', 'SECTION_ID', 'IBLOCK_ID', 'WF_NEW'));
		$iblockIds = array();
		if ($dbWDFile)
		{
			while ($arWDFile = $dbWDFile->Fetch())
			{
				$id = $arWDFile['ID'];

				if ($arWDFile['WF_NEW'] == 'Y')
					$ibe->Update($id, array('BP_PUBLISHED' => 'Y'));

				if (CIBlock::GetArrayByID($arWDFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
				{
					$ibRights = CWebDavIblock::_get_ib_rights_object('ELEMENT', $id, $arWDFile['IBLOCK_ID']);
					$ibRights->SetRights(CWebDavTools::appendRights($ibRights, $arViewRights, $arTasks));
					if(empty($iblockIds[$arWDFile['IBLOCK_ID']]))
						$iblockIds[$arWDFile['IBLOCK_ID']] = $arWDFile['IBLOCK_ID'];
				}
			}

			global $CACHE_MANAGER;

			foreach ($iblockIds as $iblockId)
				$CACHE_MANAGER->ClearByTag('iblock_id_' . $iblockId);

			unset($iblockId);
		}
	}

	public static function GetServerName()
	{
		if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
			$server_name = SITE_SERVER_NAME;
		if (!$server_name)
			$server_name = COption::GetOptionString("main", "server_name", "");
		if (!$server_name)
			$server_name = $_SERVER['HTTP_HOST'];
		$server_name = rtrim($server_name, '/');
		if (!preg_match('/^[a-z0-9\.\-]+$/i', $server_name)) // cyrillic domain hack
		{
			$converter = new CBXPunycode(defined('BX_UTF') && BX_UTF === true ? 'UTF-8' : 'windows-1251');
			$host = $converter->Encode($server_name);
			if (!preg_match('#--p1ai$#', $host)) // trying to guess
				$host = $converter->Encode(CharsetConverter::ConvertCharset($server_name, 'utf-8', 'windows-1251'));
			$server_name = $host;
		}

		return $server_name;
	}

	public static function GetServerPath()
	{
		if (!isset(self::$serverPath))
		{
			self::$serverPath = (CMain::IsHTTPS() ? "https://" : "http://").self::GetServerName();
		}

		return self::$serverPath;
	}

	public static function GetSectionForOwner($calType, $ownerId, $autoCreate = true)
	{
		return CCalendarSect::GetSectionForOwner($calType, $ownerId, $autoCreate);
	}

	private static function __tzsort($a, $b)
	{
		if($a['offset'] == $b['offset'])
			return strcmp($a['timezone_id'], $b['timezone_id']);
		return ($a['offset'] < $b['offset']? -1 : 1);
	}

	public static function GetTimezoneList()
	{
		if (empty(self::$timezones))
		{
			self::$timezones = array();
			static $aExcept = array("Etc/", "GMT", "UTC", "UCT", "HST", "PST", "MST", "CST", "EST", "CET", "MET", "WET", "EET", "PRC", "ROC", "ROK", "W-SU");
			foreach(DateTimeZone::listIdentifiers() as $tz)
			{
				foreach($aExcept as $ex)
					if(strpos($tz, $ex) === 0)
						continue 2;
				try
				{
					$oTz = new DateTimeZone($tz);
					self::$timezones[$tz] = array('timezone_id' => $tz, 'offset' => $oTz->getOffset(new DateTime("now", $oTz)));
				}
				catch(Exception $e){}
			}
			uasort(self::$timezones, array('CCalendar', '__tzsort'));

			foreach(self::$timezones as $k => $z)
			{
				self::$timezones[$k]['title'] = '(UTC'.($z['offset'] <> 0? ' '.($z['offset'] < 0? '-':'+').sprintf("%02d", ($h = floor(abs($z['offset'])/3600))).':'.sprintf("%02d", abs($z['offset'])/60 - $h*60) : '').') '.$z['timezone_id'];
			}
		}
		return self::$timezones;
	}

	public static function GetUserTimezoneName($user)
	{
		if (!is_array($user) && intVal($user) > 0)
			$user = self::GetUser($user, true);

		$tzName = CUserOptions::GetOption("calendar", "timezone".self::GetCurrentOffsetUTC($user['ID']), false, $user['ID']);

		if (!$tzName && $user['AUTO_TIME_ZONE'] !== 'Y' && $user['TIME_ZONE'])
		{
			$tzName = $user['TIME_ZONE'];
		}

		return $tzName;
	}

	public static function SaveUserTimezoneName($user, $tzName = '')
	{
		if (!is_array($user) && intVal($user) > 0)
			$user = self::GetUser($user, true);

		CUserOptions::SetOption("calendar", "timezone".self::GetCurrentOffsetUTC($user['ID']), $tzName, false, $user['ID']);
	}

	// TODO: check offset for timezone for certain date
	public static function CheckOffsetForTimezone($timezone, $offset, $date = false)
	{
		return true;
	}

	public static function GetGoodTimezoneForOffset($offset)
	{
		$timezones = self::GetTimezoneList();
		$goodTz = array();
		$result = false;

		foreach($timezones as $tz)
		{
			if ($tz['offset'] == $offset)
			{
				$goodTz[] = $tz;
				if (LANGUAGE_ID == 'ru')
				{
					if (preg_match('/(kaliningrad|moscow|samara|yekaterinburg|novosibirsk|krasnoyarsk|irkutsk|yakutsk|vladivostok)/i', $tz['timezone_id']))
					{

						$result = $tz['timezone_id'];
						break;
					}
				}
				elseif (strpos($tz['timezone_id'], 'Europe') !== false)
				{
					$result = $tz['timezone_id'];
					break;
				}
			}
		}

		if (!$result && count($goodTz) > 0)
		{
			$result = $goodTz[0]['timezone_id'];
		}

		return $result;
	}

	public static function GetTimezoneOffset($timezoneId, $dateTimestamp = false)
	{
		$offset = 0;
		if ($timezoneId)
		{
			try
			{
				$oTz = new DateTimeZone($timezoneId);
				if ($oTz)
				{
					$offset = $oTz->getOffset(new DateTime($dateTimestamp ? "@$dateTimestamp" : "now", $oTz));
				}
			}
			catch(Exception $e){}
		}
		return $offset;
	}

	public static function GetCurrentOffsetUTC($userId = false)
	{
		if (!$userId && self::$userId)
			$userId = self::$userId;
		return intVal(date("Z") + self::GetOffset($userId));
	}

	public static function GetOffsetUTC($userId = false, $dateTimestamp)
	{
		if (!$userId && self::$userId)
			$userId = self::$userId;

		$tzName = self::GetUserTimezoneName($userId);
		if ($tzName)
		{
			$offset = self::GetTimezoneOffset($tzName, $dateTimestamp);
		}
		else
		{
			$offset = date("Z", $dateTimestamp) + CCalendar::GetOffset($userId);
		}
		return intVal($offset);
	}

	public static function OnSocNetGroupDelete($groupId)
	{
		$groupId = intVal($groupId);
		if ($groupId > 0)
		{
			$res = CCalendarSect::GetList(
				array(
					'arFilter' => array(
						'CAL_TYPE' => 'group',
						'OWNER_ID' => $groupId
					),
					'checkPermissions' => false
				)
			);

			foreach($res as $sect)
			{
				CCalendarSect::Delete($sect['ID'], false);
			}
		}
		return true;
	}


	public static function GetGoogleCalendarConnection()
	{
		$userId = self::GetCurUserId();
		$result = array();
		if (CModule::IncludeModule('socialservices'))
		{
			$client = new CSocServGoogleOAuth($userId);
			$client->getEntityOAuth()->addScope(array('https://www.googleapis.com/auth/calendar', 'https://www.googleapis.com/auth/calendar.readonly'));

			$id = false;
			if($client->getEntityOAuth()->GetAccessToken())
			{
				$url = "https://www.googleapis.com/calendar/v3/users/me/calendarList";
				$h = new \Bitrix\Main\Web\HttpClient();
				$h->setHeader('Authorization', 'Bearer '.$client->getEntityOAuth()->getToken());
				$response = \Bitrix\Main\Web\Json::decode($h->get($url));
				$id = self::GetGoogleOauthPrimaryId($response);
				$result['googleCalendarPrimaryId'] = $id;
			}

			if(!$id)
			{
				$curPath = CCalendar::GetPath();
				if($curPath)
					$curPath = CHTTP::urlDeleteParams($curPath, array("action", "sessid", "bx_event_calendar_request", "EVENT_ID"));
				$result['authLink'] = $client->getUrl('opener', null, array('BACKURL' => $curPath));
			}
		}

		return $result;
	}

	private static function GetGoogleOauthPrimaryId($data = array())
	{
		$id = false;
		if (is_array($data['items']) && count($data['items']) > 0)
		{
			foreach($data['items'] as $item)
			{
				if (is_array($item) && $item['primary'] && $item['accessRole'] == 'owner')
				{
					$id = $item['id'];
					break;
				}
			}
		}
		return $id;
	}

	/**
	 * Handles last caldav activity from mobile devices
	 *
	 * @param \Bitrix\Main\Event $event Event.
	 * @return null
	 */
	public static function OnDavCalendarSync(\Bitrix\Main\Event $event)
	{
		$calendarId = $event->getParameter('id');
		$userAgent = strtolower($event->getParameter('agent'));
		$agent = false;
		list($sectionId, $entityType, $entityId) = $calendarId;

		static $arAgentsMap = array(
				'android' => 'android', // Android/iOS CardDavBitrix24
				'iphone' => 'iphone', // Apple iPhone iCal
				'davkit' => 'mac', // Apple iCal
				'mac os' => 'mac', // Apple iCal (Mac Os X > 10.8)
				'mac_os_x' => 'mac', // Apple iCal (Mac Os X > 10.8)
				'mac+os+x' => 'mac', // Apple iCal (Mac Os X > 10.10)
				'dataaccess' => 'iphone', // Apple addressbook iPhone
				//'sunbird' => 'sunbird', // Mozilla Sunbird
				'ios' => 'iphone'
		);

		foreach ($arAgentsMap as $pattern => $name)
		{
			if (strpos($userAgent, $pattern) !== false)
			{
				$agent = $name;
				break;
			}
		}

		if ($entityType == 'user' && $agent)
		{
			self::SaveSyncDate($entityId, $agent);
		}
	}

	public static function OnExchangeCalendarSync(\Bitrix\Main\Event $event)
	{
		self::SaveSyncDate($event->getParameter('userId'), 'exchange');
	}

	/**
	 * Saves date of last successful sync
	 *
	 * @param int $userId User Id
	 * @param string $syncType Type of synchronization.
	 * @param string $date Date of synchronization.
	 * @return null
	 */
	public static function SaveSyncDate($userId, $syncType, $date = false)
	{
		if ($date === false)
			$date = self::Date(time());
		$syncTypes = array('iphone', 'android', 'mac', 'exchange', 'outlook', 'office365');
		if (in_array($syncType, $syncTypes))
		{
			CUserOptions::SetOption("calendar", "last_sync_".$syncType, $date, false, $userId);
		}
	}

	public static function GetSyncInfo($userId, $syncType)
	{
		$activeSyncPeriod = 604800; // 3600 * 24 * 7 - one week
		$syncTypes = array('iphone', 'android', 'mac', 'exchange', 'outlook', 'office365');
		$result = array('connected' => false);
		if (in_array($syncType, $syncTypes))
		{
			$result['date'] = CUserOptions::GetOption("calendar", "last_sync_".$syncType, false, $userId);
		}

		if ($result['date'])
		{
			$period = time() - self::Timestamp($result['date']);
			if ($period >= 0 && $period <= $activeSyncPeriod)
			{
				$result['date'] = CCalendar::Date(self::Timestamp($result['date']) + CCalendar::GetOffset($userId), true, true, true);
				$result['connected'] = true;
			}
		}

		return $result;
	}

	public static function ClearSyncInfo($userId, $syncType)
	{
		$syncTypes = array('iphone', 'android', 'mac', 'exchange', 'outlook', 'office365');
		if (in_array($syncType, $syncTypes))
		{
			CUserOptions::DeleteOption("calendar", "last_sync_".$syncType, false, $userId);
		}
	}

	/**
	 * Updates counter in left menu in b24, sets amount of requests for meeting for current user or
	 * set of users
	 *
	 * @param int|array $users array of user's ids or user id as an int
	 * @return null
	 */
	public static function UpdateCounter($users = false)
	{
		if ($users == false)
			$users = array(self::GetCurUserId());
		elseif(!is_array($users))
			$users = array($users);

		$ids = array();
		foreach($users as $user)
		{
			if (intVal($user) > 0)
				$ids[] = intVal($user);
		}
		$users = $ids;

		if (count($users) > 0)
		{
			$events = CCalendarEvent::GetList(array('arFilter' => array('CAL_TYPE' => 'user', 'OWNER_ID' => $users, 'FROM_LIMIT' => self::Date(time(), false), 'TO_LIMIT' => self::Date(time() + self::DAY_LENGTH * 90, false), 'IS_MEETING' => 1, 'MEETING_STATUS' => 'Q', 'DELETED' => 'N'), 'parseRecursion' => false, 'checkPermissions' => false));

			$counters = array();
			foreach($events as $event)
			{
				if(!isset($counters[$event['OWNER_ID']]))
					$counters[$event['OWNER_ID']] = 0;

				$counters[$event['OWNER_ID']]++;
			}

			foreach($users as $user)
			{
				if($user > 0)
				{
					if(isset($counters[$user]) && $counters[$user] > 0)
						CUserCounter::Set($user, 'calendar', $counters[$user], '**', false);
					else
						CUserCounter::Set($user, 'calendar', 0, '**', false);
				}
			}
		}
	}
}
?>