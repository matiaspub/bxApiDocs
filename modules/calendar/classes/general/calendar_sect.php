<?
class CCalendarSect
{
	private static
		$sections,
		$Permissions = array(),
		$arOp = array(),
		$bClearOperationCache = false,
		$authHashiCal = null, // for login by hash
		$Fields = array();

	private static function GetFields()
	{
		global $DB;
		if (!count(self::$Fields))
			self::$Fields = array(
			"ID" => Array("FIELD_NAME" => "CS.ID", "FIELD_TYPE" => "int"),
			"NAME" => Array("FIELD_NAME" => "CS.NAME", "FIELD_TYPE" => "string"),
			"XML_ID" => Array("FIELD_NAME" => "CS.XML_ID", "FIELD_TYPE" => "string"),
			"EXTERNAL_ID" => Array("FIELD_NAME" => "CS.EXTERNAL_ID", "FIELD_TYPE" => "string"),
			"ACTIVE" => Array("FIELD_NAME" => "CS.ACTIVE", "FIELD_TYPE" => "string"),
			"DESCRIPTION" => Array("FIELD_NAME" => "CS.DESCRIPTION", "FIELD_TYPE" => "string"),
			"COLOR" => Array("FIELD_NAME" => "CS.COLOR", "FIELD_TYPE" => "string"),
			"TEXT_COLOR" => Array("FIELD_NAME" => "CS.TEXT_COLOR", "FIELD_TYPE" => "string"),
			"EXPORT" => Array("FIELD_NAME" => "CS.EXPORT", "FIELD_TYPE" => "string"),
			"SORT" => Array("FIELD_NAME" => "CS.SORT", "FIELD_TYPE" => "int"),
			"CAL_TYPE" => Array("FIELD_NAME" => "CS.CAL_TYPE", "FIELD_TYPE" => "string", "PROCENT" => "N"),
			"OWNER_ID" => Array("FIELD_NAME" => "CS.OWNER_ID", "FIELD_TYPE" => "int"),
			"CREATED_BY" => Array("FIELD_NAME" => "CS.CREATED_BY", "FIELD_TYPE" => "int"),
			"PARENT_ID" => Array("FIELD_NAME" => "CS.PARENT_ID", "FIELD_TYPE" => "int"),
			"TIMESTAMP_X" => Array("~FIELD_NAME" => "CS.TIMESTAMP_X", "FIELD_NAME" => $DB->DateToCharFunction("CS.TIMESTAMP_X").' as TIMESTAMP_X', "FIELD_TYPE" => "date"),
			"DATE_CREATE" => Array("~FIELD_NAME" => "CS.DATE_CREATE", "FIELD_NAME" => $DB->DateToCharFunction("CS.DATE_CREATE").' as DATE_CREATE', "FIELD_TYPE" => "date"),
			"DAV_EXCH_CAL" => Array("FIELD_NAME" => "CS.DAV_EXCH_CAL", "FIELD_TYPE" => "string"), // Exchange calendar
			"DAV_EXCH_MOD" => Array("FIELD_NAME" => "CS.DAV_EXCH_MOD", "FIELD_TYPE" => "string"), // Exchange calendar modification label
			"CAL_DAV_CON" => Array("FIELD_NAME" => "CS.CAL_DAV_CON", "FIELD_TYPE" => "string"), // CalDAV connection
			"CAL_DAV_CAL" => Array("FIELD_NAME" => "CS.CAL_DAV_CAL", "FIELD_TYPE" => "string"), // CalDAV calendar
			"CAL_DAV_MOD" => Array("FIELD_NAME" => "CS.CAL_DAV_MOD", "FIELD_TYPE" => "string"), // CalDAV calendar modification label
			"IS_EXCHANGE" => Array("FIELD_NAME" => "CS.IS_EXCHANGE", "FIELD_TYPE" => "string")
		);
		return self::$Fields;
	}

	public static function GetList($Params = array())
	{
		global $DB;
		$arFilter = $Params['arFilter'];
		$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array('SORT' => 'asc');
		$Params['joinTypeInfo'] = !!$Params['joinTypeInfo'];
		$checkPermissions = $Params['checkPermissions'] !== false;

		$bCache = CCalendar::CacheTime() > 0;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = serialize(array('section_list', $arFilter, $arOrder, $Params['joinTypeInfo'], CCalendar::IsIntranetEnabled()));
			$cachePath = CCalendar::CachePath().'section_list';

			if ($cache->InitCache(CCalendar::CacheTime(), $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arResult = $res["arResult"];
				$arSectionIds = $res["arSectionIds"];
			}
		}

		if (!$bCache || !isset($arSectionIds))
		{
			$arFields = self::GetFields();
			$arSqlSearch = array();
			if(is_array($arFilter))
			{
				$filter_keys = array_keys($arFilter);
				for($i = 0, $l = count($filter_keys); $i<$l; $i++)
				{
					$n = strtoupper($filter_keys[$i]);
					$val = $arFilter[$filter_keys[$i]];
					if(is_string($val)  && strlen($val) <=0 || strval($val)=="NOT_REF")
						continue;
					if ($n == 'ID' || $n == 'XML_ID' || $n == 'OWNER_ID')
					{
						$arSqlSearch[] = GetFilterQuery("CS.".$n, $val, 'N');
					}
					elseif ($n == 'CAL_TYPE' && is_array($val))
					{
						$Params['joinTypeInfo'] = true;
						$strType = "";
						foreach($val as $type)
							$strType .= ",'".CDatabase::ForSql($type)."'";
						$arSqlSearch[] = "CS.CAL_TYPE in (".trim($strType, ", ").")";
						$arSqlSearch[] = "CT.ACTIVE='Y'";
					}
					elseif(isset($arFields[$n]))
					{
						$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val, (isset($arFields[$n]["PROCENT"]) &&
						$arFields[$n]["PROCENT"] == "N") ? "N" : "Y");
					}
				}
			}

			$strOrderBy = '';
			foreach($arOrder as $by => $order)
				if(isset($arFields[strtoupper($by)]))
				{
					$byName = isset($arFields[strtoupper($by)]["~FIELD_NAME"]) ? $arFields[strtoupper($by)]["~FIELD_NAME"] : $arFields[strtoupper($by)]["FIELD_NAME"];
					$strOrderBy .= $byName.' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type) == "ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';
				}

			if(strlen($strOrderBy)>0)
				$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

			$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

			if (isset($arFilter['ADDITIONAL_IDS']) && is_array($arFilter['ADDITIONAL_IDS']) && count($arFilter['ADDITIONAL_IDS']) > 0)
			{
				$strTypes = "";
				foreach($arFilter['ADDITIONAL_IDS'] as $adid)
					$strTypes .= ",".IntVal($adid);
				$strSqlSearch = '('.$strSqlSearch.') OR ID in('.trim($strTypes, ', ').')';
			}

			$select = 'CS.*';
			$from = 'b_calendar_section CS';

			// Fetch types info into selection
			if ($Params['joinTypeInfo'])
			{
				$select .= ", CT.NAME AS TYPE_NAME, CT.DESCRIPTION AS TYPE_DESC";
				$from .= "\n INNER JOIN b_calendar_type CT ON (CS.CAL_TYPE=CT.XML_ID)";
			}

			$strSql = "
				SELECT
					$select
				FROM
					$from
				WHERE
					$strSqlSearch
				$strOrderBy";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arResult = Array();
			$arSectionIds = Array();

			$isExchangeEnabled = CCalendar::IsExchangeEnabled();
			$isCalDAVEnabled = CCalendar::IsCalDAVEnabled();

			while($arRes = $res->Fetch())
			{
				$arRes['COLOR'] = CCalendar::Color($arRes['COLOR'], true);
				$arSectionIds[] = $arRes['ID'];
				if (isset($arRes['EXPORT']) && $arRes['EXPORT'] != "" && CheckSerializedData($arRes['EXPORT']))
				{
					$arRes['EXPORT'] = unserialize($arRes['EXPORT']);
					if (is_array($arRes['EXPORT']) && $arRes['EXPORT']['ALLOW'])
						$arRes['EXPORT']['LINK'] = self::GetExportLink($arRes['ID'], $arRes['CAL_TYPE'], $arRes['OWNER_ID']);
				}
				if (!is_array($arRes['EXPORT']))
				{
					$arRes['EXPORT'] = array('ALLOW' => false, 'SET' => false, 'LINK' => false);
				}

				// Outlook js
				if (CCalendar::IsIntranetEnabled())
				{
					$arRes['OUTLOOK_JS'] = CCalendarSect::GetOutlookLink(array(
						'ID' => intVal($arRes['ID']),
						'XML_ID' => $arRes['XML_ID'],
						'TYPE' => $arRes['CAL_TYPE'],
						'NAME' => $arRes['NAME'],
						'PREFIX' => CCalendar::GetOwnerName($arRes['CAL_TYPE'], $arRes['OWNER_ID']),
						'LINK_URL' => CCalendar::GetOuterUrl()
					));
				}

				if ($arRes['CAL_TYPE'] == 'user')
				{
					$arRes['IS_EXCHANGE'] = strlen($arRes["DAV_EXCH_CAL"]) > 0 && $isExchangeEnabled;
					if ($arRes["CAL_DAV_CON"] && $isCalDAVEnabled)
					{
						$arRes["CAL_DAV_CON"] = intVal($arRes["CAL_DAV_CON"]);
						$resCon = CDavConnection::GetList(array("ID" => "ASC"), array("ID" => $arRes["CAL_DAV_CON"]));

						if ($con = $resCon->Fetch())
							$arRes['CAL_DAV_CON'] = $arRes["CAL_DAV_CON"];
						else
							$arRes['CAL_DAV_CON'] = false;
					}
				}
				else
				{
					$arRes['IS_EXCHANGE'] = false;
					$arRes['CAL_DAV_CON'] = false;
				}

				$arResult[] = $arRes;
			}


			if ($bCache)
			{
				$cache->StartDataCache(CCalendar::CacheTime(), $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arResult" => $arResult,
					"arSectionIds" => $arSectionIds
				));
			}
		}

		if ($checkPermissions && count($arSectionIds) > 0)
		{
			$userId = $Params['userId'] ? intVal($Params['userId']) : CCalendar::GetCurUserId();
			$arPerm = CCalendarSect::GetArrayPermissions($arSectionIds);

			$res = array();
			$arAccessCodes = array();

			$settings = CCalendar::GetSettings(array('request' => false));
			foreach($arResult as $sect)
			{
				$sectId = $sect['ID'];
				$bOwner = $sect['CAL_TYPE'] == 'user' && $sect['OWNER_ID'] == $userId;

				$bManager = false;
				if (CModule::IncludeModule('intranet') && $sect['CAL_TYPE'] == 'user' && $settings['dep_manager_sub'])
				{
					if(!$userId)
						$userId = CCalendar::GetUserId();
					$bManager = in_array($userId, CCalendar::GetUserManagers($sect['OWNER_ID'], true));
				}

				if ($bOwner || $bManager || self::CanDo('calendar_view_time', $sectId))
				{
					$sect['PERM'] = array(
						'view_time' => $bManager|| $bOwner || self::CanDo('calendar_view_time', $sectId, $userId),
						'view_title' => $bManager || $bOwner || self::CanDo('calendar_view_title', $sectId, $userId),
						'view_full' => $bManager || $bOwner || self::CanDo('calendar_view_full', $sectId, $userId),
						'add' => $bOwner || self::CanDo('calendar_add', $sectId, $userId),
						'edit' => $bOwner || self::CanDo('calendar_edit', $sectId, $userId),
						'edit_section' => $bOwner || self::CanDo('calendar_edit_section', $sectId, $userId),
						'access' => $bOwner || self::CanDo('calendar_edit_access', $sectId, $userId)
					);

					if ($bOwner || self::CanDo('calendar_edit_access', $sectId, $userId))
					{
						$sect['ACCESS'] = array();
						if (count($arPerm[$sectId]) > 0)
						{
							// Add codes to get they full names for interface
							$arAccessCodes = array_merge($arAccessCodes, array_keys($arPerm[$sectId]));
							$sect['ACCESS'] = $arPerm[$sectId];
						}
					}
					$res[] = $sect;
				}
			}

			CCalendar::PushAccessNames($arAccessCodes);
			$arResult = $res;
		}

		return $arResult;
	}

	public static function GetById($ID = 0, $checkPermissions = true, $bRerequest = false)
	{
		$ID = intVal($ID);
		if ($ID > 0)
		{
			if (!isset(self::$sections[$ID]) || $bRerequest)
			{
				$Sect = CCalendarSect::GetList(array('arFilter' => array('ID' => $ID), 'checkPermissions' => $checkPermissions));
				if($Sect && is_array($Sect) && is_array($Sect[0]))
				{
					self::$sections[$ID] = $Sect[0];
					return $Sect[0];
				}
			}
			else
			{
				return self::$sections[$ID];
			}
		}
		return false;
	}

	//
	public static function GetSuperposedList($Params = array())
	{
		global $DB;
		$checkPermissions = $Params['checkPermissions'] !== false;
		$checkSocnetPermissions = $Params['checkSocnetPermissions'] !== false;
		$userId = isset($Params['userId']) ? intVal($Params['userId']) : self::$userId;

		$arResult = Array();
		$arSectionIds = Array();
		$sqlSearch = "";

		// Common types
		$strTypes = "";
		if (isset($Params['TYPES']) && is_array($Params['TYPES']))
		{
			foreach($Params['TYPES'] as $type)
				$strTypes .= ",'".CDatabase::ForSql($type)."'";

			$strTypes = trim($strTypes, ", ");
			if ($strTypes != "")
				$sqlSearch .= "(CS.CAL_TYPE in (".$strTypes."))";
		}

		// Group's calendars
		$strGroups = "0";
		if (is_array($Params['GROUPS']) && count($Params['GROUPS']) > 0)
		{
			foreach($Params['GROUPS'] as $ownerId)
				if (IntVal($ownerId) > 0)
					$strGroups .= ",".IntVal($ownerId);

			if ($strGroups != "0")
			{
				if ($sqlSearch != "")
					$sqlSearch .= " OR ";
				$sqlSearch .= "(CS.OWNER_ID in (".$strGroups.") AND CS.CAL_TYPE='group')";
			}
		}

		if ($sqlSearch != "")
		{
			$strSql = "
				SELECT
					CS.*,
					CT.NAME AS TYPE_NAME, CT.DESCRIPTION AS TYPE_DESC
				FROM
					b_calendar_section CS
					LEFT JOIN b_calendar_type CT ON (CS.CAL_TYPE=CT.XML_ID)
				WHERE
					(
						CT.ACTIVE='Y'
					AND
						CS.ACTIVE='Y'
					AND
					(
						$sqlSearch
					))";

			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			while($arRes = $res->Fetch())
			{
				$arSectionIds[] = $arRes['ID'];
				$arResult[] = $arRes;
			}
		}

		// User's calendars
		$strUsers = "0";
		if (is_array($Params['USERS']) && count($Params['USERS']) > 0)
		{
			foreach($Params['USERS'] as $ownerId)
				if (IntVal($ownerId) > 0)
					$strUsers .= ",".IntVal($ownerId);

			if ($strUsers != "0")
			{
				$strSql = "
				SELECT
					CS.*,
					U.LOGIN AS USER_LOGIN, U.NAME AS USER_NAME, U.LAST_NAME AS USER_LAST_NAME, U.SECOND_NAME AS USER_SECOND_NAME
				FROM
					b_calendar_section CS
					LEFT JOIN b_user U ON (CS.OWNER_ID=U.ID)
				WHERE
					(
						CS.ACTIVE='Y'
					AND
						CS.OWNER_ID in (".$strUsers.")
					AND
						CS.CAL_TYPE='user'
					)";

				$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			while($arRes = $res->Fetch())
			{
				$arSectionIds[] = $arRes['ID'];
				$arResult[] = $arRes;
			}
		}

		if ($checkPermissions && count($arSectionIds) > 0)
		{
			if ($checkSocnetPermissions)
			{
				if (isset($Params['USERS']) && count($Params['USERS']) > 0)  // Fetch all socnet permissions for users
				{
					$arFeaturesU = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_USER, $Params['USERS'], "calendar");
					$arViewU = CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_USER, $Params['USERS'], "calendar", 'view');
					$arWriteU = CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $Params['USERS'], "calendar", 'write');
				}

				if (isset($Params['GROUPS']) && count($Params['GROUPS']) > 0) // Fetch all socnet permissions for groups
				{
					$arFeaturesG = CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $Params['GROUPS'], "calendar");
					$arViewG = CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $Params['GROUPS'], "calendar", 'view');
					$arWriteG = CSocNetFeaturesPerms::CanPerformOperation($userId, SONET_ENTITY_GROUP, $Params['GROUPS'], "calendar", 'write');
				}
			}

			CCalendarSect::GetArrayPermissions($arSectionIds);
			$res = array();
			$sectIds = array();
			foreach($arResult as $sect)
			{
				$sectId = $sect['ID'];
				$ownerId = $sect['OWNER_ID'];

				if (self::CanDo('calendar_view_time', $sectId) && !in_array($sectId, $sectIds))
				{
					if ($checkSocnetPermissions)
					{
						// Disabled in socialnetwork
						if (($sect['CAL_TYPE'] == 'group' && (!$arFeaturesG[$ownerId] || !$arViewG[$ownerId])) || ($sect['CAL_TYPE'] == 'user' && (!$arFeaturesU[$ownerId] || !$arViewU[$ownerId])))
							continue;
					}

					$sect['PERM'] = array(
						'view_time' => self::CanDo('calendar_view_time', $sectId),
						'view_title' => self::CanDo('calendar_view_title', $sectId),
						'view_full' => self::CanDo('calendar_view_full', $sectId),
						'add' => self::CanDo('calendar_add', $sectId),
						'edit' => self::CanDo('calendar_edit', $sectId),
						'edit_section' => self::CanDo('calendar_edit_section', $sectId),
						'access' => self::CanDo('calendar_edit_access', $sectId)
					);

					if ($checkSocnetPermissions) // Forse denied access for all "write" operations
					{
						if (($sect['CAL_TYPE'] == 'group' && !$arWriteG[$ownerId]) || ($sect['CAL_TYPE'] == 'user' && !$arWriteU[$ownerId]))
						{
							$sect['PERM']['add'] = false;
							$sect['PERM']['edit'] = false;
							$sect['PERM']['edit_section'] = false;
							$sect['PERM']['access'] = false;
						}
					}

					if ($sect['CAL_TYPE'] == 'user')
					{
						if (isset($sect['USER_NAME'], $sect['USER_LAST_NAME']))
						{
							$sect['OWNER_NAME'] = CCalendar::GetUserName(array("NAME" => $sect['USER_NAME'], "LAST_NAME" => $sect['USER_LAST_NAME'], "LOGIN" => $sect['USER_LOGIN'], "ID" => $ownerId, "SECOND_NAME" => $sect['USER_SECOND_NAME']));
							unset($sect['USER_LOGIN']);
							unset($sect['USER_LAST_NAME']);
							unset($sect['USER_SECOND_NAME']);
							unset($sect['USER_NAME']);
						}
						else
						{
							$sect['OWNER_NAME'] = CCalendar::GetUserName($ownerId);
						}
					}
					elseif ($sect['CAL_TYPE'] == 'group' && isset($Params['arGroups']))
					{
						$sect['OWNER_NAME'] = $Params['arGroups'][$ownerId]['NAME'];
					}

					$res[] = $sect;
					$sectIds[] = $sectId;
				}
			}
			$arResult = $res;
		}

		return $arResult;
	}

	public static function CheckFields($arFields)
	{
		return true;
	}

	public static function Edit($Params)
	{
		global $DB;
		$arFields = $Params['arFields'];
		if(!self::CheckFields($arFields))
			return false;

		$userId = intVal(isset($Params['userId']) ? $Params['userId'] : CCalendar::GetCurUserId());
		//if (!CCalendarSect::CanDo('calendar_edit_section', $ID))
		//	return CCalendar::ThrowError('EC_ACCESS_DENIED');

		$bNew = !isset($arFields['ID']) || $arFields['ID'] <= 0;
		if (isset($arFields['COLOR']) || $bNew)
			$arFields['COLOR'] = CCalendar::Color($arFields['COLOR']);

		$arFields['TIMESTAMP_X'] = CCalendar::Date(mktime());

		if (is_array($arFields['EXPORT']))
		{
			$arFields['EXPORT'] = array(
				'ALLOW' => !!$arFields['EXPORT']['ALLOW'],
				'SET' => (in_array($arFields['EXPORT']['set'], array('all', '3_9', '6_12'))) ? $arFields['EXPORT']['set'] : 'all'
			);
			//if (!is_array($arFields['EXPORT']))
			//	$arFields['EXPORT'] = array('ALLOW' => false,'SET' => 'all');

			$arFields['EXPORT'] = serialize($arFields['EXPORT']);
		}

		if ($bNew) // Add
		{
			if (!isset($arFields['DATE_CREATE']))
				$arFields['DATE_CREATE'] = CCalendar::Date(mktime());

			if ((!isset($arFields['CREATED_BY']) || !$arFields['CREATED_BY']))
				$arFields['CREATED_BY'] = CCalendar::GetCurUserId();

			unset($arFields['ID']);
			$ID = CDatabase::Add("b_calendar_section", $arFields, array('DESCRIPTION'));
		}
		else // Update
		{
			$ID = $arFields['ID'];
			unset($arFields['ID']);
			$strUpdate = $DB->PrepareUpdate("b_calendar_section", $arFields);
			$strSql =
				"UPDATE b_calendar_section SET ".
					$strUpdate.
				" WHERE ID=".IntVal($ID);

			$DB->QueryBind($strSql, array('DESCRIPTION' => $arFields['DESCRIPTION']));
		}

		//SaveAccess
		if ($ID > 0 && is_array($arFields['ACCESS']))
		{
			if (($arFields['CAL_TYPE'] == 'user' && $arFields['OWNER_ID'] == $userId) || self::CanDo('calendar_edit_access', $ID))
			{
				self::SavePermissions($ID, $arFields['ACCESS']);
			}
			elseif($bNew)
			{
				self::SavePermissions($ID, CCalendarSect::GetDefaultAccess($arFields['CAL_TYPE'], $arFields['OWNER_ID']));
			}
		}

		if ($bNew && $ID > 0 && !isset($arFields['ACCESS']))
		{
			self::SavePermissions($ID, CCalendarSect::GetDefaultAccess($arFields['CAL_TYPE'], $arFields['OWNER_ID']));
		}

		CCalendar::ClearCache('section_list');
		if ($ID > 0 && isset(self::$Permissions[$ID]))
		{
			unset(self::$Permissions[$ID]);
			self::$arOp = array();
		}

		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		if (!CCalendarSect::CanDo('calendar_edit_section', $ID))
			return CCalendar::ThrowError('EC_ACCESS_DENIED');

		// Del link from table
		$strSql = "DELETE FROM b_calendar_event_sect WHERE SECT_ID=".IntVal($ID);
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del from
		$strSql = "DELETE FROM b_calendar_section WHERE ID=".IntVal($ID);
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		CCalendarEvent::DeleteEmpty();

		CCalendar::ClearCache(array('section_list', 'event_list'));
		return true;
	}

	public static function CreateDefault($Params = array())
	{
		if ($Params['type'] == 'user' || $Params['type'] == 'group')
			$name = CCalendar::GetOwnerName($Params['type'], $Params['ownerId']);
		else
			$name = GetMessage('EC_DEF_SECT_GROUP_CAL');
		// if ($Params['type'] == 'user')
			// $name = GetMessage('EC_DEF_SECT_USER_CAL');
		// else
			// $name = GetMessage('EC_DEF_SECT_GROUP_CAL');

		$arFields = Array(
			'CAL_TYPE' => $Params['type'],
			'NAME' => $name,
			'DESCRIPTION' => GetMessage('EC_DEF_SECT_DESC'),
			'COLOR' => CCalendar::Color(),
			'OWNER_ID' => $Params['ownerId'],
			//'EXPORT' => 'Y',
			//'EXPORT_SET' => 'all',
			'IS_EXCHANGE' => 0,
			'ACCESS' => CCalendarSect::GetDefaultAccess($Params['type'], $Params['ownerId']),
			'PERM' => array(
				'view_time' => true,
				'view_title' => true,
				'view_full' => true,
				'add' => true,
				'edit' => true,
				'edit_section' => true,
				'access' => true
			)
		);
		$arFields['ID'] = self::Edit(array('arFields' => $arFields));
		if ($arFields['ID'] > 0)
			return $arFields;
		return false;
	}

	public static function SavePermissions($sectId, $arTaskPerm)
	{
		global $DB;
		$DB->Query("DELETE FROM b_calendar_access WHERE SECT_ID='".intVal($sectId)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (is_array($arTaskPerm))
		{
			foreach($arTaskPerm as $accessCode => $taskId)
			{
				$arInsert = $DB->PrepareInsert("b_calendar_access", array("ACCESS_CODE" => $accessCode, "TASK_ID" => intVal($taskId), "SECT_ID" => intVal($sectId)));
				$strSql = "INSERT INTO b_calendar_access(".$arInsert[0].") VALUES(".$arInsert[1].")";
				$DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
	}

	public static function GetArrayPermissions($arSections = array())
	{
		global $DB;
		$s = "'0'";
		foreach($arSections as $id)
			if ($id > 0)
				$s .= ",'".intVal($id)."'";

		if (strtoupper($DB->type) == "MYSQL")
		{
			$strSql = 'SELECT SC.ID, CAP.ACCESS_CODE, CAP.TASK_ID, SC.CAL_TYPE, SC.OWNER_ID, SC.CREATED_BY
				FROM b_calendar_section SC
				LEFT JOIN b_calendar_access CAP ON (SC.ID=CAP.SECT_ID)
				WHERE SC.ID in ('.$s.')';
		}
		elseif(strtoupper($DB->type) == "MSSQL")
		{
			$strSql = 'SELECT SC.ID, CAP.ACCESS_CODE, CAP.TASK_ID, SC.CAL_TYPE, SC.OWNER_ID, SC.CREATED_BY
				FROM b_calendar_section SC
				LEFT JOIN b_calendar_access CAP ON (convert(varchar,SC.ID)=CAP.SECT_ID)
				WHERE SC.ID in ('.$s.')';
		}
		elseif(strtoupper($DB->type) == "ORACLE")
		{
			$strSql = 'SELECT SC.ID, CAP.ACCESS_CODE, CAP.TASK_ID, SC.CAL_TYPE, SC.OWNER_ID, SC.CREATED_BY
				FROM b_calendar_section SC
				LEFT JOIN b_calendar_access CAP ON (TO_CHAR(SC.ID)=CAP.SECT_ID)
				WHERE SC.ID in ('.$s.')';
		}


		$res = $DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while($arRes = $res->Fetch())
		{
			$sectId = $arRes['ID'];
			if ($sectId <= 0)
				continue;
			if (!is_array(self::$Permissions[$sectId]))
				self::$Permissions[$sectId] = array();

			if ($arRes['ACCESS_CODE'] != '' && $arRes['ACCESS_CODE'] != '0' && $arRes['TASK_ID'] > 0)
				self::$Permissions[$sectId][$arRes['ACCESS_CODE']] = $arRes['TASK_ID'];

			if ($arRes['CAL_TYPE'] != 'group' && $arRes['OWNER_ID'] > 0) // Owner for user or other calendar types
				self::$Permissions[$sectId]['U'.$arRes['OWNER_ID']] = CCalendar::GetAccessTasksByName('calendar_section', 'calendar_access');

			if ($arRes['CAL_TYPE'] == 'group' && $arRes['OWNER_ID'] > 0) // Owner for group
				self::$Permissions[$sectId]['SG'.$arRes['OWNER_ID'].'_A'] = CCalendar::GetAccessTasksByName('calendar_section', 'calendar_access');
		}

		return self::$Permissions;
	}

	public static function SetClearOperationCache($val = true)
	{
		self::$bClearOperationCache = $val;
	}

	public static function CanDo($operation, $sectId = 0, $userId = false)
	{
		global $USER;

		if (!$userId)
			$userId = CCalendar::GetCurUserId();

		if (!isset($USER) || !is_object($USER) || !$sectId)
			return false;

		if ($userId == CCalendar::GetCurUserId() && $USER->CanDoOperation('edit_php'))
			return true;

		if ((CCalendar::GetType() == 'group' || CCalendar::GetType() == 'user') && CCalendar::IsSocNet() && CCalendar::IsSocnetAdmin())
			return true;

		$res = in_array($operation, self::GetOperations($sectId, $userId));
		self::$bClearOperationCache = false;
		return $res;
	}

	public static function GetOperations($sectId, $userId = false)
	{
		if (!$userId)
			$userId = CCalendar::GetCurUserId();

		$arCodes = array();
		$rCodes = CAccess::GetUserCodes($userId);
		while($code = $rCodes->Fetch())
			$arCodes[] = $code['ACCESS_CODE'];

		if (!in_array('G2', $arCodes))
			$arCodes[] = 'G2';

		$key = $sectId.'|'.implode(',', $arCodes);
		if (self::$bClearOperationCache || !is_array(self::$arOp[$key]))
		{
			if (!isset(self::$Permissions[$sectId]))
				self::GetArrayPermissions(array($sectId));
			$perms = self::$Permissions[$sectId];

			self::$arOp[$key] = array();
			if (is_array($perms))
			{
				foreach ($perms as $code => $taskId)
				{
					if (in_array($code, $arCodes))
						self::$arOp[$key] = array_merge(self::$arOp[$key], CTask::GetOperations($taskId, true));
				}
			}
		}
		return self::$arOp[$key];
	}

	public static function GetCalDAVConnectionId($section = 0)
	{
		global $DB;

		$arIds = is_array($section) ? $section : array($section);
		$arIds = array_unique($arIds);
		$strIds = array();
		$result = array();
		foreach($arIds as $id)
			if (intVal($id) > 0)
			{
				$strIds[] = intVal($id);
				$result[intVal($id)] = 0;
			}
		$strIds = implode(',', $strIds);

		$strSql = "SELECT ID, CAL_DAV_CON FROM b_calendar_section WHERE ID in (".$strIds.")";
		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		while ($arRes = $res->Fetch())
			$result[$arRes['ID']] = ($arRes['CAL_DAV_CON'] > 0) ? intVal($arRes['CAL_DAV_CON']) : 0;

		if (!is_array($section))
			return $result[$section];

		return $result;
	}

	public static function GetExportLink($sectionId, $type = '', $ownerId = false)
	{
		$userId = CCalendar::GetCurUserId();
		$params = '';
		if ($type !== false)
			$params .=  '&type='.strtolower($type);
		if ($ownerId !== false)
			$params .=  '&owner='.intVal($ownerId);
		return $params.'&user='.intVal($userId).'&'.'sec_id='.intVal($sectionId).'&sign='.self::GetSign($userId, $sectionId).'&bx_hit_hash='.self::GetAuthHash();
	}

	public static function GetSPExportLink()
	{
		$userId = CCalendar::GetCurUserId();
		return '&user_id='.$userId.'&sign='.CCalendarSect::GetSign($userId, 'superposed_calendars');
	}

	public static function GetOutlookLink($Params)
	{
		if (CModule::IncludeModule('intranet'))
			return CIntranetUtils::GetStsSyncURL($Params);
	}

	private static function GetUniqCalendarId()
	{
		$uniq = COption::GetOptionString("calendar", "~export_uniq_id", "");
		if(strlen($uniq) <= 0)
		{
			$uniq = md5(uniqid(rand(), true));
			COption::SetOptionString("calendar", "~export_uniq_id", $uniq);
		}
		return $uniq;
	}

	public static function GetSign($userId, $sectId)
	{
		return md5($userId."||".$sectId."||".CCalendarSect::GetUniqCalendarId());
	}

	public static function CheckSign($sign, $userId, $sectId)
	{
		return (md5($userId."||".$sectId."||".CCalendarSect::GetUniqCalendarId()) == $sign);
	}

	public static function Hidden($userId, $ar = false)
	{
		$res = array();
		if (class_exists('CUserOptions'))
		{
			if ($ar === false) // Get
			{
				$str = CUserOptions::GetOption("calendar", "hidden_sections", false, $userId);
				if ($str !== false && CheckSerializedData($str))
					$res = unserialize($str);
			}
			elseif(is_array($ar)) // Set
			{
				$res = CUserOptions::SetOption("calendar", "hidden_sections", serialize($ar));
			}
		}
		return $res;
	}

	// * * * * EXPORT TO ICAL  * * * *
	public static function ReturnICal($Params)
	{
		$sectId = $Params['sectId'];
		$userId = intVal($Params['userId']);
		$sign = $Params['sign'];
		$type = strtolower($Params['type']);
		$ownerId = intVal($Params['ownerId']);
		$bCache = false;

		$GLOBALS['APPLICATION']->RestartBuffer();

		if (!self::CheckSign($sign, $userId, $sectId))
			return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));

		$arSections = CCalendarSect::GetList(
			array(
				'arFilter' => array('ID' => $sectId),
				'checkPermissions' => false
			));

		if ($arSections && $arSections[0] && $arSections[0]['EXPORT'] && $arSections[0]['EXPORT']['ALLOW'])
		{
			$arSection = $arSections[0];
			$arEvents = CCalendarEvent::GetList(
				array(
					'arFilter' => array(
						'SECTION' => $arSection['ID']
					),
					'getUserfields' => false,
					'parseRecursion' => false,
					'fetchAttendees' => false,
					'fetchMeetings' => true,
					'userId' => $userId
				)
			);
			$iCalEvents = self::FormatICal($arSection, $arEvents);
		}
		else
		{
			return CCalendar::ThrowError(GetMessage('EC_ACCESS_DENIED'));
		}

		self::ShowICalHeaders();
		echo $iCalEvents;
		exit();
	}

	public static function ExtendExportEventsArray($arEvents, $arCalEx)
	{
		for($i = 0, $l = count($arEvents); $i < $l; $i++)
		{
			$calId = $arEvents[$i]['IBLOCK_SECTION_ID'];
			if (!isset($arCalEx[$calId]))
				continue;
			$arEvents[$i]['NAME'] = $arEvents[$i]['NAME'].' ['.$arCalEx[$calId]['SP_PARAMS']['NAME'].' :: '.$arCalEx[$calId]['NAME'].']';
		}
		return $arEvents;
	}

	private static function ShowICalHeaders()
	{
		header("Content-Type: text/calendar; charset=UTF-8");
		header("Accept-Ranges: bytes");
		header("Connection: Keep-Alive");
		header("Keep-Alive: timeout=15, max=100");
	}

	private static function FormatICal($Section, $Events)
	{
		$res = 'BEGIN:VCALENDAR
PRODID:-//Bitrix//Bitrix Calendar//EN
VERSION:2.0
CALSCALE:GREGORIAN
METHOD:PUBLISH
X-WR-CALNAME:'.self::_ICalPaste($Section['NAME']).'
X-WR-CALDESC:'.self::_ICalPaste($Section['DESCRIPTION'])."\n";


	$localTime = new DateTime();
	$localOffset = $localTime->getOffset();
	$h24 = CCalendar::GetDayLen();

	foreach ($Events as $event)
	{
		//$fts = CCalendar::Timestamp($event['DT_FROM']);
		//$tts = CCalendar::Timestamp($event['DT_TO']);
		$fts = $event['DT_FROM_TS'];
		$tts = $event['DT_TO_TS'];


		if ($event['DT_SKIP_TIME'] == 'Y') // All days events
		{
			$dtStart = date("Ymd", $event['DT_FROM_TS']);
			if ($event['DT_LENGTH'] == $h24)
			{
				$dtEnd = $dtStart;
			}
			else
			{
				$dtEnd = date("Ymd", $event['DT_TO_TS'] - $h24);
			}
		}
		else // Events with time
		{
			$dtStart = date("Ymd\THis\Z", $event['DT_FROM_TS'] - $localOffset);
			$dtEnd = date("Ymd\THis\Z", $event['DT_TO_TS'] - $localOffset);
		}

		$dtStamp = str_replace('T000000Z', '', date("Ymd\THisZ", CCalendar::Timestamp($event['TIMESTAMP_X']) - $localOffset));
		$uid = md5(uniqid(rand(), true).$event['ID']).'@bitrix';
		$period = '';

		$rrule = CCalendarEvent::ParseRRULE($event['RRULE']);

		if($rrule && isset($rrule['FREQ']) && $rrule['FREQ'] != 'NONE')
		{
			$period = 'RRULE:FREQ='.$rrule['FREQ'].';';
			$period .= 'INTERVAL='.$rrule['INTERVAL'].';';
			if ($rrule['FREQ'] == 'WEEKLY')
				$period .= 'BYDAY='.implode(',', $rrule['BYDAY']).';';

			if ($event['DT_SKIP_TIME'] == 'Y') // All days events
			{
				if ($event['DT_LENGTH'] == $h24)
					$dtEnd_ = $dtStart;
				else
					$dtEnd_ = date("Ymd", $event['DT_FROM_TS'] + $event['DT_LENGTH'] - $h24 - $localOffset);
			}
			else // Events with time
			{
				$dtEnd_ = date("Ymd\THisZ", $event['DT_FROM_TS'] + $event['DT_LENGTH'] - $localOffset);
			}

			if (date("Ymd", $tts) != '20380101')
				$period .= 'UNTIL='.$dtEnd.';';
			$period .= 'WKST=MO';
			$dtEnd = $dtEnd_;
			$period .= "\n";
		}
		$res .= 'BEGIN:VEVENT
DTSTART;VALUE=DATE:'.$dtStart.'
DTEND;VALUE=DATE:'.$dtEnd.'
DTSTAMP:'.$dtStamp.'
UID:'.$uid.'
SUMMARY:'.self::_ICalPaste($event['NAME']).'
DESCRIPTION:'.self::_ICalPaste($event['DESCRIPTION'])."\n".$period.'
CLASS:PRIVATE
LOCATION:'.self::_ICalPaste(CCalendar::GetTextLocation($event['LOCATION'])).'
SEQUENCE:0
STATUS:CONFIRMED
TRANSP:TRANSPARENT
END:VEVENT'."\n";
		}
		$res .= 'END:VCALENDAR';
		if (!defined('BX_UTF') || BX_UTF !== true)
			$res = $GLOBALS["APPLICATION"]->ConvertCharset($res, LANG_CHARSET, 'UTF-8');

		return $res;
	}

	private static function _ICalPaste($str)
	{
		$str = preg_replace ("/\r/i", '', $str);
		$str = preg_replace ("/\n/i", '\\n', $str);
		//$str = htmlspecialcharsback($str);
		return $str;
	}

	public static function GetModificationLabel($calendarId) // GetCalendarModificationLabel
	{
		global $DB;
		$sectionId = intVal($calendarId[0]);

		if ($sectionId > 0)
		{
			$strSql = "
				SELECT ".$DB->DateToCharFunction("CS.TIMESTAMP_X")." as TIMESTAMP_X
				FROM b_calendar_section CS
				WHERE ID=".$sectionId;
			$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			if($sect = $res->Fetch())
				return $sect['TIMESTAMP_X'];
		}
		return "";
	}

	public static function UpdateModificationLabel($arId = array())
	{
		global $DB;
		if (!is_array($arId) && $arId)
			$arId = array($arId);

		$arId = array_unique($arId);
		$strIds = array();
		foreach($arId as $id)
			if (intVal($id) > 0)
				$strIds[] = intVal($id);
		$strIds = implode(',', $strIds);

		if ($strIds)
		{
			$strSql =
			"UPDATE b_calendar_section SET ".
				$DB->PrepareUpdate("b_calendar_section", array('TIMESTAMP_X' => FormatDate(CCalendar::DFormat(true), mktime()))).
			" WHERE ID in (".$strIds.")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function GetDefaultAccess($type, $ownerId)
	{
		if (CCalendar::IsIntranetEnabled())
			$access = array('G2' => CCalendar::GetAccessTasksByName('calendar_section', 'calendar_view_time'));
		else
			$access = array('G2' => CCalendar::GetAccessTasksByName('calendar_section', 'calendar_view'));

		if ($type == 'user')
		{
		}
		elseif ($type == 'group' && $ownerId > 0)
		{
			$access['SG'.$ownerId.'_A'] = CCalendar::GetAccessTasksByName('calendar_section', 'calendar_access');
			$access['SG'.$ownerId.'_E'] = CCalendar::GetAccessTasksByName('calendar_section', 'calendar_edit');
			$access['SG'.$ownerId.'_K'] = CCalendar::GetAccessTasksByName('calendar_section', 'calendar_edit');
		}
		else
		{
		}

		// Creator of the section
		$access['U'.CCalendar::GetUserId()] = CCalendar::GetAccessTasksByName('calendar_section', 'calendar_access');

		$arAccessCodes = array();
		foreach($access as $code => $o)
			$arAccessCodes[] = $code;

		CCalendar::PushAccessNames($arAccessCodes);
		return $access;
	}

	public static function GetAuthHash()
	{
		if (!isset(self::$authHashiCal) || empty(self::$authHashiCal))
		{
			global $USER, $APPLICATION;
			self::$authHashiCal = $USER->AddHitAuthHash($APPLICATION->GetCurPage());
		}
		return self::$authHashiCal;
	}

	public static function CheckAuthHash()
	{
		global $USER;
		if (strlen($_REQUEST['bx_hit_hash']) > 0) // $_REQUEST['bx_hit_hash']
			return $USER->LoginHitByHash();

		return false;
	}
}
?>