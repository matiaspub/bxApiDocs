<?
class CCalendarType
{
	private static
		$Permissions = array(),
		$arOp = array(),
		$Fields = array();

	public static function GetList($Params = array())
	{
		global $DB;
		$access = new CAccess();
		$access->UpdateCodes();
		$arFilter = $Params['arFilter'];
		$arOrder = isset($Params['arOrder']) ? $Params['arOrder'] : Array('XML_ID' => 'asc');
		$checkPermissions = $Params['checkPermissions'] !== false;

		$bCache = CCalendar::CacheTime() > 0;
		if ($bCache)
		{
			$cache = new CPHPCache;
			$cacheId = serialize(array('type_list', $arFilter, $arOrder));
			$cachePath = CCalendar::CachePath().'type_list';

			if ($cache->InitCache(CCalendar::CacheTime(), $cacheId, $cachePath))
			{
				$res = $cache->GetVars();
				$arResult = $res["arResult"];
				$arTypeXmlIds = $res["arTypeXmlIds"];
			}
		}

		if (!$bCache || !isset($arTypeXmlIds))
		{
			static $arFields = array(
				"XML_ID" => Array("FIELD_NAME" => "CT.XML_ID", "FIELD_TYPE" => "string"),
				"NAME" => Array("FIELD_NAME" => "CT.NAME", "FIELD_TYPE" => "string"),
				"ACTIVE" => Array("FIELD_NAME" => "CT.ACTIVE", "FIELD_TYPE" => "string"),
				"DESCRIPTION" => Array("FIELD_NAME" => "CT.DESCRIPTION", "FIELD_TYPE" => "string"),
				"EXTERNAL_ID" => Array("FIELD_NAME" => "CT.EXTERNAL_ID", "FIELD_TYPE" => "string")
			);

			$err_mess = "Function: CCalendarType::GetList<br>Line: ";
			$arSqlSearch = array();
			$strSqlSearch = "";
			if(is_array($arFilter))
			{
				$filter_keys = array_keys($arFilter);
				for($i=0, $l = count($filter_keys); $i<$l; $i++)
				{
					$n = strtoupper($filter_keys[$i]);
					$val = $arFilter[$filter_keys[$i]];
					if(is_string($val) && strlen($val) <= 0)
						continue;
					if ($n == 'XML_ID')
					{
						if (is_array($val))
						{
							$strXml = "";
							foreach($val as $xmlId)
								$strXml .= ",'".CDatabase::ForSql($xmlId)."'";
							$arSqlSearch[] = "CT.XML_ID in (".trim($strXml, ", ").")";
						}
						else
						{
							$arSqlSearch[] = GetFilterQuery("CT.XML_ID", $val, 'N');
						}
					}
					if ($n == 'EXTERNAL_ID')
					{
						$arSqlSearch[] = GetFilterQuery("CT.EXTERNAL_ID", $val, 'N');
					}
					elseif(isset($arFields[$n]))
					{
						$arSqlSearch[] = GetFilterQuery($arFields[$n]["FIELD_NAME"], $val);
					}
				}
			}

			$strOrderBy = '';
			foreach($arOrder as $by=>$order)
				if(isset($arFields[strtoupper($by)]))
					$strOrderBy .= $arFields[strtoupper($by)]["FIELD_NAME"].' '.(strtolower($order)=='desc'?'desc'.(strtoupper($DB->type)=="ORACLE"?" NULLS LAST":""):'asc'.(strtoupper($DB->type)=="ORACLE"?" NULLS FIRST":"")).',';

			if(strlen($strOrderBy)>0)
				$strOrderBy = "ORDER BY ".rtrim($strOrderBy, ",");

			$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

			$strSql = "
				SELECT
					CT.*
				FROM
					b_calendar_type CT
				WHERE
					$strSqlSearch
				$strOrderBy";

			$res = $DB->Query($strSql, false, $err_mess.__LINE__);
			$arResult = Array();
			$arTypeXmlIds = Array();
			while($arRes = $res->Fetch())
			{
				$arResult[] = $arRes;
				$arTypeXmlIds[] = $arRes['XML_ID'];
			}

			if ($bCache)
			{
				$cache->StartDataCache(CCalendar::CacheTime(), $cacheId, $cachePath);
				$cache->EndDataCache(array(
					"arResult" => $arResult,
					"arTypeXmlIds" => $arTypeXmlIds
				));
			}
		}

		if ($checkPermissions && count($arTypeXmlIds) > 0)
		{
			$arPerm = self::GetArrayPermissions($arTypeXmlIds);
			$res = array();
			$arAccessCodes = array();
			foreach($arResult as $type)
			{
				$typeXmlId = $type['XML_ID'];
				if (self::CanDo('calendar_type_view', $typeXmlId))
				{
					$type['PERM'] = array(
						'view' => self::CanDo('calendar_type_view', $typeXmlId),
						'add' => self::CanDo('calendar_type_add', $typeXmlId),
						'edit' => self::CanDo('calendar_type_edit', $typeXmlId),
						'edit_section' => self::CanDo('calendar_type_edit_section', $typeXmlId),
						'access' => self::CanDo('calendar_type_edit_access', $typeXmlId)
					);

					if (self::CanDo('calendar_type_edit_access', $typeXmlId))
					{
						$type['ACCESS'] = array();
						if (count($arPerm[$typeXmlId]) > 0)
						{
							// Add codes to get they full names for interface
							$arAccessCodes = array_merge($arAccessCodes, array_keys($arPerm[$typeXmlId]));
							$type['ACCESS'] = $arPerm[$typeXmlId];
						}
					}
					$res[] = $type;
				}
			}

			CCalendar::PushAccessNames($arAccessCodes);
			$arResult = $res;
		}

		return $arResult;
	}

	public static function Edit($Params)
	{
		global $DB;
		$arFields = $Params['arFields'];
		$XML_ID = preg_replace("/[^a-zA-Z0-9_]/i", "", $arFields['XML_ID']);
		$arFields['XML_ID'] = $XML_ID;
		if (!isset($arFields['XML_ID']) || $XML_ID == "")
			return false;

		//return $APPLICATION->ThrowException(GetMessage("EC_ACCESS_DENIED"));

		$access = $arFields['ACCESS'];
		unset($arFields['ACCESS']);

		if (count($arFields) > 1) // We have not only XML_ID
		{
			if ($Params['NEW']) // Add
			{
				$strSql = "SELECT * FROM b_calendar_type WHERE XML_ID='".$DB->ForSql($XML_ID)."'";
				$res = $DB->Query($strSql, false, __LINE__);
				if (!($arRes = $res->Fetch()))
					CDatabase::Add("b_calendar_type", $arFields, array('DESCRIPTION'));
				else
					false;
			}
			else // Update
			{
				unset($arFields['XML_ID']);
				if (count($arFields) > 0)
				{
					$strUpdate = $DB->PrepareUpdate("b_calendar_type", $arFields);
					$strSql =
						"UPDATE b_calendar_type SET ".
							$strUpdate.
						" WHERE XML_ID='".$DB->ForSql($XML_ID)."'";
					$DB->QueryBind($strSql, array('DESCRIPTION' => $arFields['DESCRIPTION']));
				}
			}
		}

		//SaveAccess
		if (self::CanDo('calendar_type_edit_access', $XML_ID) && is_array($access))
			self::SavePermissions($XML_ID, $access);

		CCalendar::ClearCache('type_list');
		return $XML_ID;
	}

	public static function Delete($XML_ID)
	{
		global $DB;
		// Del types
		$strSql = "DELETE FROM b_calendar_type WHERE XML_ID='".$DB->ForSql($XML_ID)."'";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del access for types
		$strSql = "DELETE FROM b_calendar_access WHERE SECT_ID='".$DB->ForSql($XML_ID)."'";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del sections
		$strSql = "DELETE FROM b_calendar_section WHERE CAL_TYPE='".$DB->ForSql($XML_ID)."'";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		// Del events
		$strSql = "DELETE FROM b_calendar_event WHERE CAL_TYPE='".$DB->ForSql($XML_ID)."'";
		$z = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		CCalendar::ClearCache(array('type_list', 'section_list', 'event_list'));
		return true;
	}

	public static function SavePermissions($type, $arTaskPerm)
	{
		global $DB;
		$DB->Query("DELETE FROM b_calendar_access WHERE SECT_ID='".$DB->ForSql($type)."'", false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		foreach($arTaskPerm as $accessCode => $taskId)
		{
			$arInsert = $DB->PrepareInsert("b_calendar_access", array("ACCESS_CODE" => $accessCode, "TASK_ID" => intVal($taskId), "SECT_ID" => $type));
			$strSql = "INSERT INTO b_calendar_access(".$arInsert[0].") VALUES(".$arInsert[1].")";
			$DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
	}

	public static function GetArrayPermissions($arTypes = array())
	{
		global $DB;
		$s = "'0'";
		foreach($arTypes as $xmlid)
			$s .= ",'".$DB->ForSql($xmlid)."'";

		$strSql = 'SELECT *
			FROM b_calendar_access CAP
			WHERE CAP.SECT_ID in ('.$s.')';
		$res = $DB->Query($strSql , false, "File: ".__FILE__."<br>Line: ".__LINE__);

		while($arRes = $res->Fetch())
		{
			$xmlId = $arRes['SECT_ID'];
			if (!is_array(self::$Permissions[$xmlId]))
				self::$Permissions[$xmlId] = array();
			self::$Permissions[$xmlId][$arRes['ACCESS_CODE']] = $arRes['TASK_ID'];
		}
		foreach($arTypes as $xmlid)
			if (!isset(self::$Permissions[$xmlid]))
				self::$Permissions[$xmlid] = array();

		return self::$Permissions;
	}

	public static function CanDo($operation, $xmlId = 0, $userId = false)
	{
		global $USER;
		if ((!$USER || !is_object($USER)) || $USER->CanDoOperation('edit_php'))
			return true;

		if (($xmlId == 'group' || $xmlId == 'user' || CCalendar::IsBitrix24()) && CCalendar::IsSocNet() && CCalendar::IsSocnetAdmin())
			return true;

		return in_array($operation, self::GetOperations($xmlId, $userId));
	}

	public static function GetOperations($xmlId, $userId = false)
	{
		if ($userId === false)
			$userId = CCalendar::GetCurUserId();

		$arCodes = array();
		$rCodes = CAccess::GetUserCodes($userId);
		while($code = $rCodes->Fetch())
			$arCodes[] = $code['ACCESS_CODE'];

		if (!in_array('G2', $arCodes))
			$arCodes[] = 'G2';

		$key = $xmlId.'|'.implode(',', $arCodes);
		if (!is_array(self::$arOp[$key]))
		{
			if (!isset(self::$Permissions[$xmlId]))
				self::GetArrayPermissions(array($xmlId));
			$perms = self::$Permissions[$xmlId];

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

	public static function CheckType($xmlId, $userId = false)
	{
		return true;
	}
}
?>
