<?
if (!CModule::IncludeModule('webservice'))
	return;

class CCalendarWebService extends IWebService
{
	var $arStatusValues = array(
		'free' => 0, 'quest' => 1, 'busy' => 2, 'absent' => 3,
	);

	var $arPriorityValues = array(
		'low' => -1, 'normal' => 0, 'high' => 1,
	);

	public function __getFieldsDefinition()
	{
		$obFields = new CXMLCreator('Fields');

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ID" ColName="tp_ID" RowOrdinal="0" ReadOnly="TRUE" Type="Counter" Name="ID" PrimaryKey="TRUE" DisplayName="ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ID" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="OWSHIDDENVERSION" ColName="tp_Version" RowOrdinal="0" Hidden="TRUE" ReadOnly="TRUE" Type="Integer" SetAs="owshiddenversion" Name="owshiddenversion" DisplayName="owshiddenversion" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="owshiddenversion" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="FSOBJTYPE" Name="FSObjType" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Item Type" List="Docs" FieldRef="ID" ShowField="FSType" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="FSObjType" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UNIQUEID" Name="UniqueId" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Unique Id" List="Docs" FieldRef="ID" ShowField="UniqueId" JoinColName="DoclibRowId" JoinRowOrdinal="0" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UniqueId" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPEID" ColName="tp_ContentTypeId" Sealed="TRUE" Hidden="TRUE" RowOrdinal="0" ReadOnly="TRUE" Type="ContentTypeId" Name="ContentTypeId" DisplaceOnUpgrade="TRUE" DisplayName="Content Type ID" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentTypeId" FromBaseType="TRUE"'));
		//$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="CONTENTTYPE" ColName="tp_ContentType" RowOrdinal="0" ReadOnly="TRUE" Type="Text" Name="ContentType" DisplaceOnUpgrade="TRUE" DisplayName="Content Type" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="ContentType" FromBaseType="TRUE" PITarget="MicrosoftWindowsSharePointServices" PIAttribute="ContentTypeID"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="METAINFO" Name="MetaInfo" DisplaceOnUpgrade="TRUE" Hidden="TRUE" ShowInFileDlg="FALSE" Type="Lookup" DisplayName="Property Bag" List="Docs" FieldRef="ID" ShowField="MetaInfo" JoinColName="DoclibRowId" JoinType="INNER" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="MetaInfo" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="PERMMASK" Name="PermMask" DisplaceOnUpgrade="TRUE" ReadOnly="TRUE" Hidden="TRUE" RenderXMLUsingPattern="TRUE" ShowInFileDlg="FALSE" Type="Computed" DisplayName="Effective Permissions Mask" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="PermMask" FromBaseType="TRUE"'));

		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="ID"'));

		$obField->addChild($obDisplayPattern = new CXMLCreator('DisplayPattern'));
		$obDisplayPattern->addChild(new CXMLCreator('CurrentRights'));

		//
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="{fa564e0f-0c70-4ab9-b863-0177e6ddd247}" Type="Text" Name="Title" DisplayName="Title" Required="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Title" FromBaseType="TRUE" ColName="nvarchar1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="LOCATION" Type="Text" Name="Location" DisplayName="Location" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Location" ColName="nvarchar3"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field Type="Note" ID="DESCRIPTION" Name="Description" RichText="TRUE" DisplayName="Description" Sortable="FALSE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Description" ColName="ntext2"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="MODIFIED" ColName="tp_Modified" RowOrdinal="0" ReadOnly="TRUE" Type="DateTime" Name="Modified" DisplayName="Modified" StorageTZ="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Modified" FromBaseType="TRUE"'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="AUTHOR" ColName="tp_Author" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Author" DisplayName="Created By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Author" FromBaseType="TRUE"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EDITOR" ColName="tp_Editor" RowOrdinal="0" ReadOnly="TRUE" Type="User" List="UserInfo" Name="Editor" DisplayName="Modified By" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Editor" FromBaseType="TRUE" '));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field Type="DateTime" ID="DATE_FROM" Name="EventDate" DisplayName="Start Time" Format="DateTime" Sealed="TRUE" Required="TRUE" FromBaseType="TRUE" Filterable="FALSE" FilterableNoRecurrence="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="EventDate" ColName="datetime1"'));
		$obField->addChild(CXMLCreator::createTagAttributed('Default', '[today]'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="fAllDayEvent" RefType="AllDayEvent"'));
		$obField->addChild(CXMLCreator::createTagAttributed('DefaultFormulaValue', $this->__makeDateTime(strtotime(date('Y-m-d')))));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DATE_TO" Type="DateTime" Name="EndDate" DisplayName="End Time" Format="DateTime" Sealed="TRUE" Required="TRUE" Filterable="FALSE" FilterableNoRecurrence="FALSE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="EndDate" ColName="datetime2"'));
		$obField->addChild(CXMLCreator::createTagAttributed('Default', '[today]'));
		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="DURATION" Type="Integer" Name="Duration" DisplayName="Duration" Hidden="TRUE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="Duration" ColName="int2"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="ALLDAYEVENT" Type="AllDayEvent" Name="fAllDayEvent" DisplaceOnUpgrade="TRUE" DisplayName="All Day Event" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="fAllDayEvent" ColName="bit1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="EVENTTYPE" Type="Integer" Name="EventType" DisplayName="Event Type" Sealed="TRUE" Hidden="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="EventType" ColName="int1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="UID" Type="Guid" Name="UID" DisplayName="UID" Sealed="TRUE" Hidden="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="UID" ColName="uniqueidentifier1"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="RECURENCE_DATA" Type="Note" Name="RecurrenceData" DisplayName="RecurrenceData" Hidden="TRUE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="RecurrenceData" ColName="ntext3"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="TIMEZONE" Type="Integer" Name="TimeZone" DisplayName="TimeZone" Sealed="TRUE" Hidden="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="TimeZone" ColName="int3"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="XMLTZONE" Type="Note" Name="XMLTZone" DisplayName="XMLTZone" Hidden="TRUE" Sealed="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="XMLTZone" ColName="ntext4"'));

		$obFields->addChild($obField = CXMLCreator::createTagAttributed('Field ID="RECURRENCE" Type="Recurrence" Name="fRecurrence" DisplayName="Recurrence"  Title="Recurrence" Sealed="TRUE" NoEditFormBreak="TRUE" SourceID="http://schemas.microsoft.com/sharepoint/v3" StaticName="fRecurrence" ColName="bit2"'));
		$obField->addChild(CXMLCreator::createTagAttributed('Default', 'FALSE'));
		$obField->addChild($obFieldRefs = new CXMLCreator('FieldRefs'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="RecurrenceData" RefType="RecurData"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="EventType" RefType="EventType"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="UID" RefType="UID"'));
		//$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="RecurrenceID" RefType="RecurrenceId"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="EventDate" RefType="StartDate"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="EndDate" RefType="EndDate"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="Duration" RefType="Duration"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="TimeZone" RefType="TimeZone"'));
		$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="XMLTZone" RefType="XMLTZone"'));
		//$obFieldRefs->addChild(CXMLCreator::createTagAttributed('FieldRef Name="MasterSeriesItemID" RefType="MasterSeriesItemID"'));

		return $obFields;
	}

	public static function __makeDateTime($ts = null)
	{
		if (null === $ts)
			$ts = time();

		return date('Y-m-d', $ts).'T'.date('H:i:s', $ts).'Z';
	}

	public static function __makeTS($datetime = null)
	{
		if (null === $datetime)
			return time();

		if (intval(substr($datetime, 0, 4)) >= 2037)
			$datetime = '2037'.substr($datetime, 4);

		return MakeTimeStamp(substr($datetime, 0, 10).' '.substr($datetime, 11, -1), 'YYYY-MM-DD HH:MI:SS');
	}

	public function GetList($listName)
	{
		global $APPLICATION, $USER;
		//todo: check read access for calendar

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault(
				'Data error',
				'Wrong GUID - '.$listName
			);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));
		$arSections = CCalendarSect::GetList(
			array(
				'arFilter' => array('XML_ID' => strtolower($listName_original))
			)
		);

		if (!$arSections || !is_array($arSections[0]))
			return new CSoapFault(
				'List not found',
				'List with '.$listName.' GUID not found'
			);
		$arSection = $arSections[0];

		$data = new CXMLCreator('List');
		$data->setAttribute('ID', $listName);
		$data->setAttribute('Name', $listName);
		$data->setAttribute('Title', $arSection['NAME']);

		//$data->setAttribute('Created', date('Ymd H:i:s', MakeTimeStamp($arSection['DATE_CREATE'])));
		//$data->setAttribute('Modified', date('Ymd H:i:s', MakeTimeStamp($arSection['TIMESTAMP_X'])));
		$data->setAttribute('Direction', 'none'); // RTL, LTR
		$data->setAttribute('ReadSecurity', '2');
		$data->setAttribute('WriteSecurity', '2');

		$data->setAttribute('Author', $arSection['CREATED_BY'].';#'.CCalendar::GetUserName($arSection['CREATED_BY']));

		$data->addChild($this->__getFieldsDefinition());

		$data->addChild($obNode = new CXMLCreator('RegionalSettings'));

		$obNode->addChild(CXMLCreator::createTagAttributed('Language', '1049'));
		//$obNode->addChild(CXMLCreator::createTagAttributed('Locale', '1033'));
		$obNode->addChild(CXMLCreator::createTagAttributed('AdvanceHijri', '0'));
		$obNode->addChild(CXMLCreator::createTagAttributed('CalendarType', '0'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Time24', 'True'));
		//$obNode->addChild(CXMLCreator::createTagAttributed('TimeZone', '59'));
		$obNode->addChild(CXMLCreator::createTagAttributed('Presence', 'True'));

		$data->addChild($obNode = new CXMLCreator('ServerSettings'));

		$obNode->addChild(CXMLCreator::createTagAttributed('ServerVersion', '12.0.0.6219'));
		$obNode->addChild(CXMLCreator::createTagAttributed('RecycleBinEnabled', 'False'));
		$obNode->addChild(CXMLCreator::createTagAttributed('ServerRelativeUrl', '/company/absence.php'));

		return array('GetListResult' => $data);
	}

	public function __getRow($event, $listName, &$last_change)
	{
		$arStatusValues = $this->arStatusValues;
		$arPriorityValues = $this->arPriorityValues;

		$first_week_day = COption::GetOptionString('calendar', 'week_start', 'MO');
		$first_week_day = strtolower($first_week_day);

		$change = MakeTimeStamp($event['TIMESTAMP_X']);
		if ($last_change < $change)
			$last_change = $change;

		$bRecurrent = (isset($event['RRULE']) && $event['RRULE'] != "") ? 1 : 0;
		$rrule = CCalendarEvent::ParseRRULE($event['RRULE']);

		$ts_start = $event['DT_FROM_TS'];
		$ts_finish = $event['DT_TO_TS'];
		$bAllDay = $event['DT_SKIP_TIME'] == 'Y' ? 1 : 0;

		$TZBias = intval(date('Z'));
		$offset = CCalendar::GetOffset();
		$duration = $event['DT_LENGTH'];
		if ($bAllDay)
			$duration -= 20;

		if (!$bAllDay || defined('OLD_OUTLOOK_VERSION'))
		{
			$ts_start = $ts_start - $TZBias - $offset; // We need time in UTC
			$ts_finish = $ts_finish - $TZBias - $offset;
		}

		$obRow = new CXMLCreator('z:row');
		$obRow->setAttribute('ows_ID', $event['ID']);
		$obRow->setAttribute('ows_Title', htmlspecialcharsback($event['NAME'])); // we have data htmlspecialchared yet
		$version = $event['VERSION'] ? $event['VERSION'] : 1;
		$obRow->setAttribute('ows_Attachments', 0);
		$obRow->setAttribute('ows_owshiddenversion', $version);
		$obRow->setAttribute('ows_MetaInfo_vti_versionhistory', md5($event['ID']).':'.$version);

		/*
			ows_MetaInfo_BusyStatus='2' - Editor
			ows_MetaInfo_IntendedBusyStatus='-1' - Creator

			values:
				-1 - Unspecified busy status. Protocol clients can choose to display one of the other values if BusyStatus is -1.
				0 - Free - ACCESSIBILITY => 'free'
				1 - Tentative - ACCESSIBILITY => 'quest'
				2 - Busy - ACCESSIBILITY => 'busy'
				3 - Out of Office - ACCESSIBILITY => 'absent'
		*/

		$status = $arStatusValues[$event['ACCESSIBILITY']];
		$obRow->setAttribute('ows_MetaInfo_BusyStatus', $status === null ? -1 : $status);
		$obRow->setAttribute('ows_MetaInfo_Priority', intval($arPriorityValues[$event['IMPORTANCE']]));
		$obRow->setAttribute('ows_Created', $this->__makeDateTime(MakeTimeStamp($event['DATE_CREATE'])-$TZBias));
		$obRow->setAttribute('ows_Modified', $this->__makeDateTime($change-$TZBias));
		$obRow->setAttribute('ows_EventType', $bRecurrent ? 1 : 0);
		$obRow->setAttribute('ows_Location', CCalendar::GetTextLocation($event['LOCATION']));
		$obRow->setAttribute('ows_Description', $event['~DESCRIPTION']); // Description parsed from BB-codes to HTML
		$obRow->setAttribute('ows_EventDate', $this->__makeDateTime($ts_start));
		$obRow->setAttribute('ows_EndDate', $this->__makeDateTime($ts_start + $event['DT_LENGTH']));

		$obRow->setAttribute('ows_fAllDayEvent', $bAllDay);

		/* Recurrence */
		$obRow->setAttribute('ows_fRecurrence', $bRecurrent);
		if ($bRecurrent)
		{
			$obRow->setAttribute('ows_UID', CIntranetUtils::makeGUID(md5($event['ID'].'_'.$change)));
			$tz_data = '';
			$tz_data .= '<timeZoneRule>';
			$tz_data .= '<standardBias>'.(-intval(($TZBias - (date('I') ? 3600 : 0)) /60)).'</standardBias>';
			$tz_data .= '<additionalDaylightBias>-60</additionalDaylightBias>';

			$bUseTransition = COption::GetOptionString('intranet', 'tz_transition', 'Y') == 'Y';

			if ($bUseTransition)
			{
				$transition_standard = COption::GetOptionString('intranet', 'tz_transition_standard', '');
				$transition_daylight = COption::GetOptionString('intranet', 'tz_transition_daylight', '');
				if (!$transition_standard) $transition_standard = '<transitionRule month="10" day="su" weekdayOfMonth="last" /><transitionTime>3:0:0</transitionTime>';
				if (!$transition_daylight) $transition_daylight = '<transitionRule  month="3" day="su" weekdayOfMonth="last" /><transitionTime>2:0:0</transitionTime>';

				$tz_data .= '<standardDate>'.$transition_standard.'</standardDate><daylightDate>'.$transition_daylight.'</daylightDate>';
			}

			$tz_data .= '</timeZoneRule>';
			$obRow->setAttribute('ows_XMLTZone', $tz_data);

			$recurence_data = '';
			$recurence_data .= '<recurrence>';
			$recurence_data .= '<rule>';
			$recurence_data .= '<firstDayOfWeek>'.$first_week_day.'</firstDayOfWeek>';

			$recurence_data .= '<repeat>';
			switch($rrule['FREQ'])
			{
				case 'DAILY':
					$recurence_data .= '<daily dayFrequency="'.$rrule['INTERVAL'].'" />';
				break;

				case 'WEEKLY':
					$days = '';
					foreach ($rrule['BYDAY'] as $day)
						$days .= strtolower($day).'="TRUE" ';
					$recurence_data .= '<weekly '.$days.'weekFrequency="'.$rrule['INTERVAL'].'" />';
				break;

				case 'MONTHLY':
					$recurence_data .= '<monthly monthFrequency="'.$rrule['INTERVAL'].'" day="'.date('d', $ts_start).'" />';
				break;

				case 'YEARLY':
					$recurence_data .= '<yearly yearFrequency="'.$rrule['INTERVAL'].'" month="'.date('m', $ts_start).'" day="'.date('d', $ts_start).'" />';
				break;
			}
			$recurence_data .= '</repeat>';

			if (date('Y', $ts_finish) == '2038' || date('Y', $ts_finish) == '2037')
			{
				$recurence_data .= '<repeatForever>FALSE</repeatForever>';
			}
			else
			{
				$recurence_data .= '<windowEnd>'.$this->__makeDateTime($ts_finish).'</windowEnd>';
			}

			$recurence_data .= '</rule>';
			$recurence_data .= '</recurrence>';
			$obRow->setAttribute('ows_RecurrenceData', $recurence_data);
			$obRow->setAttribute('ows_Duration', $duration);
		}
		else
		{
			$obRow->setAttribute('ows_Duration', $duration);
		}

		$obRow->setAttribute('ows_UniqueId', $event['ID'].';#'.$listName);
		$obRow->setAttribute('ows_FSObjType', $event['ID'].';#0');
		$obRow->setAttribute('ows_Editor', $event['CREATED_BY'].';#'.CCalendar::GetUserName($event['CREATED_BY']));
		$obRow->setAttribute('ows_PermMask', '0x7fffffffffffffff');
		$obRow->setAttribute('ows_ContentTypeId', '0x01020005CE290982A58C439E00342702139D1A');

		return $obRow;
	}

	public function GetListItemChanges($listName, $viewFields = '', $since = '', $contains = '')
	{
		define ('OLD_OUTLOOK_VERSION', true);

		$res = $this->GetListItemChangesSinceToken($listName, $viewFields, '', 0, $since ? $this->__makeTS($since) : '');

		if (is_object($res))
			return $res;
		else
			return array('GetListItemChangesResult' => $res['GetListItemChangesSinceTokenResult']);
	}

	public function GetListItemChangesSinceToken($listName, $viewFields = '', $query = '', $rowLimit = 0, $changeToken = '')
	{
		global $APPLICATION, $USER;

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
		{
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);
		}

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));

		$arSections = CCalendarSect::GetList(array('arFilter' => array('XML_ID' => $listName_original)));
		if (!$arSections || !is_array($arSections[0]))
			return new CSoapFault(
				'List not found',
				'List with '.$listName.' GUID not found!'
			);
		$arSection = $arSections[0];

		$userId = (is_object($USER) && $USER->GetID()) ? $USER->GetID() : 1;

		if ($changeToken)
		{
			//$arMethodParams['timestampFrom'] = ConvertTimeStamp($changeToken, 'FULL');
			// $arMethodParams['bLoadAll'] = false;
			// $arMethodParams['timestampFrom'] = ConvertTimeStamp($changeToken, 'FULL');
			// if (!$arMethodParams['timestampFrom'])
			// {
				// return new CSoapFault(
					// 'Params error',
					// 'Wrong changeToken: '.$changeToken
				// );
			// }
		}
		// else
		// {
			// $arMethodParams['bLoadAll'] = true;
		// }

		$fetchMeetings = $arSection['CAL_TYPE'] == 'user' && CCalendar::GetMeetingSection($arSection['OWNER_ID']) == $arSection['ID'];
		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'CAL_TYPE' => $arSection['CAL_TYPE'],
					'OWNER_ID' => $arSection['OWNER_ID'],
					'SECTION' => $arSection['ID'],
					'DELETED' => '' // We fetch all deleted and current events
					//'INCLUDE_INVITINGS' => 'N'
				),
				'getUserfields' => false,
				'parseRecursion' => false,
				'fetchAttendees' => false,
				'fetchMeetings' => $fetchMeetings,
				'userId' => $userId
			)
		);

		$last_change = 0;
		$data = new CXMLCreator('listitems');
		$data->setAttribute('MinTimeBetweenSyncs', 0);
		$data->setAttribute('RecommendedTimeBetweenSyncs', 180);
		$data->setAttribute('TimeStamp', $this->__makeDateTime());
		$data->setAttribute('EffectivePermMask', 'FullMask');

		$data->addChild($obChanges = new CXMLCreator('Changes'));

		if (!$changeToken && !defined('OLD_OUTLOOK_VERSION'))
		{
			$obChanges->addChild($this->__getFieldsDefinition());
		}

		$data->addChild($obData = new CXMLCreator('rs:data'));

		$count = 0;
		foreach ($arEvents as  $event)
		{
			if ($event['DELETED'] != 'N' || ($event['IS_MEETING'] && $event['USER_MEETING']['STATUS'] == 'N'))
			{
				$obId = new CXMLCreator('Id');
				$obId->setAttribute('ChangeType', 'Delete');
				$obId->setData($event['ID']);
				$obChanges->addChild($obId);
			}
			elseif (!$changeToken || $changeToken < CCalendar::Timestamp($event['TIMESTAMP_X'], false))
			{
				$obData->addChild($this->__getRow($event, $listName, $last_change));
				$count++;
			}
		}

		$last_change = time();
		$obData->setAttribute('ItemCount', $count);

		$data->setAttribute('xmlns:rs', 'urn:schemas-microsoft-com:rowset');
		$data->setAttribute('xmlns:z', '#RowsetSchema');

		if ($last_change > 0)
			$obChanges->setAttribute('LastChangeToken', $last_change);


		return array('GetListItemChangesSinceTokenResult' => $data);
	}

	public function UpdateListItems($listName, $updates)
	{
		global $USER;

		$arStatusValues = array_flip($this->arStatusValues);
		$arPriorityValues = array_flip($this->arPriorityValues);

		if (!$listName_original = CIntranetUtils::checkGUID($listName))
			return new CSoapFault('Data error', 'Wrong GUID - '.$listName);

		$obResponse = new CXMLCreator('Results');

		$listName = ToUpper(CIntranetUtils::makeGUID($listName_original));
		$arSections = CCalendarSect::GetList(array('arFilter' => array('XML_ID' => $listName_original)));
		if (!$arSections || !is_array($arSections[0]))
			return new CSoapFault(
				'List not found',
				'List with '.$listName.' GUID not found'
			);
		$arSection = $arSections[0];

		$bGroup = $arSection['CAL_TYPE'] == 'group';
		$calType = $arSection['CAL_TYPE'];
		$ownerId = $arSection['OWNER_ID'];

		if ($bGroup)
		{
			CModule::IncludeModule('socialnetwork');
			$arGroupTmp = CSocNetGroup::GetByID($arSection['SOCNET_GROUP_ID']);
			if ($arGroupTmp["CLOSED"] == "Y")
				if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
					return new CSoapFault('Cannot modify archive group calendar', 'Cannot modify archive group calendar');
		}

		$obBatch = $updates->children[0];
		$atrONERROR = $obBatch->getAttribute('OnError');
		$atrDATEINUTC = $obBatch->getAttribute('DateInUtc');
		$atrPROPERTIES = $obBatch->getAttribute('Properties');

		$arChanges = $obBatch->children;

		$arResultIDs = array();
		$dateStart = ConvertTimeStamp(strtotime('-1 hour'), 'FULL');
		$arResponseRows = array();
		$arReplicationIDs = array();
		$userId = (is_object($USER) && $USER->GetID()) ? $USER->GetID() : 1;

		foreach ($arChanges as $obMethod)
		{
			$arData = array('_command' => $obMethod->getAttribute('Cmd'));

			foreach ($obMethod->children as $obField)
			{
				$name = $obField->getAttribute('Name');
				if ($name == 'MetaInfo')
					$name .= '_'.$obField->getAttribute('Property');

				$arData[$name] = $obField->content;
			}

			if ($arData['_command'] == 'Delete')
			{
				$obRes = new CXMLCreator('Result');
				$obRes->setAttribute('ID', $obMethod->getAttribute('ID').','.$arData['_command']);
				$obRes->setAttribute('List', $listName);
				$obRes->addChild($obNode = new CXMLCreator('ErrorCode'));

				$res = CCalendar::DeleteEvent($arData['ID']);
				if ($res === true)
					$obNode->setData('0x00000000');
				else
					$obNode->setData('0x81020014');

				/*
					0x00000000 - ok
					0x81020015 - data conflict
					0x81020014 - generic error such as invalid value for Field
					0x81020016 - item does not exist
				*/

				$obResponse->addChild($obRes);
			}
			elseif ($arData['_command'] == 'New' || $arData['_command'] == 'Update')
			{
				$q = ToLower($arData['Description']);
				if (($pos = strrpos($q, '</body>')) !== false) $arData['Description'] = substr($arData['Description'], 0, $pos);
				if (($pos = strpos($q, '<body>')) !== false) $arData['Description'] = substr($arData['Description'], $pos + 6);

				$arData['Description'] = str_replace('</DIV>', "\r\n</DIV>", $arData['Description']);
				$arData['Description'] = str_replace(array("&#10;", "&#13;"), "", $arData['Description']);
				$arData['Description'] = preg_replace("/<![^>]*>/", '', $arData['Description']);
				//$arData['Description'] = strip_tags($arData['Description']);
				$arData['Description'] = trim($arData['Description']);

				$arData['Location'] = trim($arData['Location']);

				if (isset($arData['EventDate']))
				{
					$arData['EventDate'] = $this->__makeTS($arData['EventDate']);
					$arData['EndDate'] = $this->__makeTS($arData['EndDate']) + ($arData['fAllDayEvent'] ? -86340 : 0);
					$TZBias = intval(date('Z', $arData['EventDate']));
				}

				$arData['EventType'] = intval($arData['EventType']);

				if ($arData['EventType'] == 2)
					$arData['EventType'] = 0;

				if ($arData['EventType'] > 2 /* || ($arData['EventType'] == 1 && !$arData['RecurrenceData'])*/)
					return new CSoapFault(
						'Unsupported event type',
						'Event type unsupported'
					);

				$arData['fRecurrence'] = intval($arData['fRecurrence']);
				$arData['RRULE'] = '';

				$id = $arData['_command'] == 'New' ? 0 : intVal($arData['ID']);
				if ($arData['RecurrenceData'])
				{
					//$xmlstr = $arData['XMLTZone'];
					//$arData['XMLTZone'] = new CDataXML();
					//$arData['XMLTZone']->LoadString($xmlstr);

					$xmlstr = $arData['RecurrenceData'];
					$obRecurData = new CDataXML();
					$obRecurData->LoadString($xmlstr);

/*
<recurrence>
		<rule>
			<firstDayOfWeek>mo</firstDayOfWeek>
			<repeat>
				<weekly mo='TRUE' tu='TRUE' th='TRUE' sa='TRUE' weekFrequency='1' />
			</repeat>
			<repeatForever>FALSE</repeatForever>
		</rule>
</recurrence>
<deleteExceptions>true</deleteExceptions>
*/

					$obRecurRule = $obRecurData->tree->children[0]->children[0];
					$obRecurRepeat = $obRecurRule->children[1];
					$obNode = $obRecurRepeat->children[0];

					$arData['RRULE'] = array();
					switch($obNode->name)
					{
						case 'daily':
							// hack. we have no "work days" daily recurence
							if ($obNode->getAttribute('weekday') == 'TRUE')
							{
								$arData['RRULE']['FREQ'] = 'WEEKLY';
								$arData['RRULE']['BYDAY'] = 'MO,TU,WE,TH,FR';
								$arData['RRULE']['INTERVAL'] = 1;
							}
							else
							{
								$arData['RRULE']['FREQ'] = 'DAILY';
								$arData['RRULE']['INTERVAL'] = $obNode->getAttribute('dayFrequency');
							}

							$time_end = strtotime(
								date(date('Y-m-d', $arData['EventDate']).' H:i:s', $arData['EndDate'])
							);

							$arData['DT_LENGTH'] = $time_end - $arData['EventDate'];
						break;

						case 'weekly':
							$arData['RRULE']['FREQ'] = 'WEEKLY';
							$arData['RRULE']['BYDAY'] = '';

							$arWeekDays = array('mo', 'tu', 'we', 'th', 'fr', 'sa', 'su');
							foreach ($arWeekDays as $day => $value)
							{
								if ($obNode->getAttribute($value))
									$arData['RRULE']['BYDAY'][] = strtoupper($value);
							}

							$arData['RRULE']['BYDAY'] = implode(',', $arData['RRULE']['BYDAY']);
							$arData['RRULE']['INTERVAL'] = $obNode->getAttribute('weekFrequency');

							$time_end = strtotime(date(date('Y-m-d', $arData['EventDate']).' H:i:s', $arData['EndDate']));

							$arData['DT_LENGTH'] = $time_end - $arData['EventDate'];
						break;

						case 'monthly':
							$arData['RRULE']['FREQ'] = 'MONTHLY';
							$arData['RRULE']['INTERVAL'] = $obNode->getAttribute('monthFrequency');
							$time_end = strtotime(date(date('Y-m', $arData['EventDate']).'-d H:i:s', $arData['EndDate']));

							$arData['DT_LENGTH'] = $time_end - $arData['EventDate'];
						break;

						case 'yearly':
							$arData['RRULE']['FREQ'] = 'YEARLY';
							$arData['RRULE']['INTERVAL'] = $obNode->getAttribute('yearFrequency');

							$time_end = strtotime(date(date('Y', $arData['EventDate']).'-m-d H:i:s', $arData['EndDate']));

							$arData['DT_LENGTH'] = $time_end - $arData['EventDate'];
						break;
					}

					if ($arData['DT_LENGTH'] == 0 && isset($arData['RRULE']['FREQ']))
						$arData['DT_LENGTH'] = 86400;

					$obWhile = $obRecurRule->children[2];
					if ($obWhile->name == 'repeatForever')
					{
						$arData['EndDate'] = MakeTimeStamp('');
					}
					elseif ($obWhile->name == 'windowEnd')
					{
						$arData['EndDate'] = $this->__makeTS($obWhile->textContent());
						$arData['RRULE']['UNTIL'] = ConvertTimeStamp($arData['EndDate'], 'FULL');
					}
				}
				elseif($arData['fRecurrence'] == -1 && $id > 0)
				{
					$arData['RRULE'] = -1;
				}

				if (isset($arData['EventDate']))
				{
					$skipTime = $arData['fAllDayEvent'] ? 'Y' : 'N';
					$TZBias = $arData['fAllDayEvent'] ? 0 : $TZBias;
					$arData['EventDate'] += $TZBias;
					$arData['EndDate'] += $TZBias;
				}
				else
				{
					$arData["DT_FROM"] = -1;
					$arData["DT_TO"] = -1;
				}

				// fields
				$arFields = array(
					"ID" => $id,
					'CAL_TYPE' => $calType,
					'OWNER_ID' => $ownerId,
					'CREATED_BY' => $userId,
					'DT_FROM_TS' => $arData['EventDate'],
					'DT_TO_TS' => $arData['EndDate'],
					'DT_SKIP_TIME' => $skipTime,
					'NAME' => $arData['Title'],
					'DESCRIPTION' => CCalendar::ParseHTMLToBB($arData['Description']),
					'SECTIONS' => array($arSection['ID']),
					'ACCESSIBILITY' => $arStatusValues[$arData['MetaInfo_BusyStatus']],
					'IMPORTANCE' => $arPriorityValues[$arData['MetaInfo_Priority']],
					'RRULE' => $arData['RRULE'],
					'LOCATION' => CCalendar::UnParseTextLocation($arData['Location'])
				);

				if (isset($arData['DT_LENGTH']) && $arData['DT_LENGTH'] > 0)
					$arFields['DT_LENGTH'] = $arData['DT_LENGTH'];

				$EventID = CCalendar::SaveEvent(
					array(
						'arFields' => $arFields,
						'fromWebservice' => true
					)
				);

				if ($EventID)
				{
					// dirty hack
					$arReplicationIDs[$EventID] = $arData['MetaInfo_ReplicationID'];

					$arResponseRows[$EventID] = new CXMLCreator('Result');
					$arResponseRows[$EventID]->setAttribute('ID', $obMethod->getAttribute('ID').','.$arData['_command']);
					$arResponseRows[$EventID]->setAttribute('List', $listName);

					$arResponseRows[$EventID]->addChild($obNode = new CXMLCreator('ErrorCode'));
					$obNode->setData('0x00000000');
					//$arResponseRows[$EventID]->setAttribute('Version', 3);
				}
			}
		}

		$userId = (is_object($USER) && $USER->GetID()) ? $USER->GetID() : 1;
		$fetchMeetings = CCalendar::GetMeetingSection($userId) == $arSection['ID'];
		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					'CAL_TYPE' => $calType,
					'OWNER_ID' => $ownerId,
					'SECTION' => $arSection['ID'],
					//'INCLUDE_INVITINGS' => 'N'
				),
				'getUserfields' => false,
				'parseRecursion' => false,
				'fetchAttendees' => false,
				'fetchMeetings' => $fetchMeetings,
				'userId' => $userId
			)
		);

		foreach ($arEvents as $key => $event)
		{
			if ($arResponseRows[$event['ID']])
			{
				$obRow = $this->__getRow($event, $listName, $last_change = 0);
				$obRow->setAttribute('xmlns:z', "#RowsetSchema");
				if ($arReplicationIDs[$event['ID']])
					$obRow->setAttribute('MetaInfo_ReplicationID', $arReplicationIDs[$event['ID']]);

				$arResponseRows[$event['ID']]->addChild($obRow);
			}
			$obResponse->addChild($arResponseRows[$event['ID']]);
		}
		return array('UpdateListItemsResult' => $obResponse);
	}

	public static function GetWebServiceDesc()
	{
		$wsdesc = new CWebServiceDesc();
		$wsdesc->wsname = "bitrix.webservice.calendar";
		$wsdesc->wsclassname = "CCalendarWebService";
		$wsdesc->wsdlauto = true;
		$wsdesc->wsendpoint = CWebService::GetDefaultEndpoint();
		$wsdesc->wstargetns = CWebService::GetDefaultTargetNS();

		$wsdesc->classTypes = array();
		$wsdesc->structTypes = array();

		$wsdesc->classes = array(
			"CCalendarWebService" => array(
				"GetList" => array(
					"type" => "public",
					"name" => "GetList",
					"input" => array(
						"listName" => array("varType" => "string"),
					),
					"output"	=> array(
						"GetListResult" => array("varType" => 'any'),
					),
					'httpauth' => 'Y'
				),

				'GetListItemChanges' => array(
					'type' => 'public',
					'name' => 'GetListItemChanges',
					'input' => array(
						"listName" => array("varType" => "string"),
						"viewFields" => array("varType" => "any", 'strict'=> 'no'),
						'since' => array('varType' => 'string', 'strict' => 'no'),
					),
					'output' => array(
						'GetListItemChangesResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),

				'GetListItemChangesSinceToken' => array(
					'type' => 'public',
					'name' => 'GetListItemChangesSinceToken',
					'input' => array(
						"listName" => array("varType" => "string"),
						"viewFields" => array("varType" => "any", 'strict'=> 'no'),
						'query' => array('varType' => 'any', 'strict' => 'no'),
						'rowLimit' => array('varType' => 'string', 'strict' => 'no'),
						'changeToken' => array('varType' => 'string', 'strict' => 'no'),
					),
					'output' => array(
						'GetListItemChangesSinceTokenResult' => array('varType' => 'any'),
					),
					'httpauth' => 'Y'
				),

				'UpdateListItems' => array(
					'type' => 'public',
					'name' => 'UpdateListItems',
					'input' => array(
						"listName" => array("varType" => "string"),
						'updates' => array('varType' => 'any', 'strict' => 'no'),
					),
					'output' => array(
						'UpdateListItemsResult' => array('varType' => 'any')
					),
					'httpauth' => 'Y'

				),
			),
		);

		return $wsdesc;
	}
}
?>