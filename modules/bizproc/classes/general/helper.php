<?
IncludeModuleLangFile(__FILE__);

class CBPHelper
{
	const DISTR_B24 = 'b24';
	const DISTR_BOX = 'box';

	static private $serverName;
	static protected $cAccess;
	static protected $groupsCache = array();

	protected static function getAccessProvider()
	{
		if (self::$cAccess === null)
			self::$cAccess = new CAccess;
		return self::$cAccess;
	}

	private static function UsersArrayToStringInternal($arUsers, $arWorkflowTemplate, $arAllowableUserGroups, $appendId = true)
	{
		if (is_array($arUsers))
		{
			$r = array();

			$keys = array_keys($arUsers);
			foreach ($keys as $key)
				$r[$key] = self::UsersArrayToStringInternal($arUsers[$key], $arWorkflowTemplate, $arAllowableUserGroups, $appendId);

			if (count($r) == 2)
			{
				$keys = array_keys($r);
				if ($keys[0] == 0 && $keys[1] == 1 && is_string($r[0]) && is_string($r[1]))
				{
					if (in_array($r[0], array("Document", "Template", "Variable", "User"))
						|| preg_match('#^A\d+_\d+_\d+_\d+$#i', $r[0])
						|| is_array($arWorkflowTemplate) && CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $r[0]) != null
						)
					{
						return "{=".$r[0].":".$r[1]."}";
					}
				}
			}

			return implode(", ", $r);
		}
		else
		{
			if (array_key_exists(strtolower($arUsers), $arAllowableUserGroups))
				return $arAllowableUserGroups[strtolower($arUsers)];

			$userId = 0;
			if (substr($arUsers, 0, strlen("user_")) == "user_")
				$userId = intval(substr($arUsers, strlen("user_")));

			if ($userId > 0)
			{
				$db = CUser::GetList(
					($by = "LAST_NAME"),
					($order = "asc"),
					array("ID_EQUAL_EXACT" => $userId),
					array(
						"NAV_PARAMS" => false,
					)
				);

				if ($ar = $db->Fetch())
				{
					$str = CUser::FormatName(COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID), $ar, true, false);
					if ($appendId)
						$str = $str." [".$ar["ID"]."]";
					return str_replace(",", " ", $str);
				}
			}
			else if (strpos($arUsers, 'group_') === 0)
			{
				$str = htmlspecialcharsex(self::getExtendedGroupName($arUsers, $appendId));
				return str_replace(",", " ", $str);
			}

			return str_replace(",", " ", $arUsers);
		}
	}

	public static function UsersArrayToString($arUsers, $arWorkflowTemplate, $documentType, $appendId = true)
	{
		if (!is_array($arUsers) && strlen($arUsers) <= 0 || is_array($arUsers) && count($arUsers) <= 0)
			return "";

		$arAllowableUserGroups = array();
		$arAllowableUserGroupsTmp = CBPDocument::GetAllowableUserGroups($documentType);
		foreach ($arAllowableUserGroupsTmp as $k1 => $v1)
			$arAllowableUserGroups[strtolower($k1)] = str_replace(",", " ", $v1);

		return self::UsersArrayToStringInternal($arUsers, $arWorkflowTemplate, $arAllowableUserGroups, $appendId);
	}

	public static function UsersStringToArray($strUsers, $documentType, &$arErrors, $callbackFunction = null)
	{
		$arErrors = array();

		$strUsers = trim($strUsers);
		if (strlen($strUsers) <= 0)
			return ($callbackFunction != null) ? array(array(), array()) : array();

		$arUsers = array();
		$strUsers = str_replace(";", ",", $strUsers);
		$arUsersTmp = explode(",", $strUsers);
		foreach ($arUsersTmp as $user)
		{
			$user = trim($user);
			if (strlen($user) > 0)
				$arUsers[] = $user;
		}

		$arAllowableUserGroups = null;

		$arResult = array();
		$arResultAlt = array();
		foreach ($arUsers as $user)
		{
			$bCorrectUser = false;
			$bNotFoundUser = true;
			if (preg_match(CBPActivity::ValuePattern, $user, $arMatches))
			{
				$bCorrectUser = true;
				$arResult[] = $arMatches[0];
			}
			else
			{
				if ($arAllowableUserGroups == null)
				{
					$arAllowableUserGroups = array();
					$arAllowableUserGroupsTmp = CBPDocument::GetAllowableUserGroups($documentType);
					foreach ($arAllowableUserGroupsTmp as $k1 => $v1)
						$arAllowableUserGroups[strtolower($k1)] = strtolower($v1);
				}

				if (array_key_exists(strtolower($user), $arAllowableUserGroups))
				{
					$bCorrectUser = true;
					$arResult[] = $user;
				}
				elseif (($k1 = array_search(strtolower($user), $arAllowableUserGroups)) !== false)
				{
					$bCorrectUser = true;
					$arResult[] = $k1;
				}
				elseif (preg_match('#\[([A-Z]{1,}[0-9A-Z_]+)\]#i', $user, $arMatches))
				{
					$bCorrectUser = true;
					$arResult[] = "group_".strtolower($arMatches[1]);
				}
				else
				{
					$ar = self::SearchUserByName($user);
					$cnt = count($ar);
					if ($cnt == 1)
					{
						$bCorrectUser = true;
						$arResult[] = "user_".$ar[0];
					}
					elseif ($cnt > 1)
					{
						$bNotFoundUser = false;
						$arErrors[] = array(
							"code" => "Ambiguous",
							"message" => str_replace("#USER#", htmlspecialcharsbx($user), GetMessage("BPCGHLP_AMBIGUOUS_USER")),
						);
					}
					elseif ($callbackFunction != null)
					{
						$s = call_user_func_array($callbackFunction, array($user));
						if ($s != null)
						{
							$arResultAlt[] = $s;
							$bCorrectUser = true;
						}
					}
				}
			}

			if (!$bCorrectUser)
			{
				if ($bNotFoundUser)
				{
					$arErrors[] = array(
						"code" => "NotFound",
						"message" => str_replace("#USER#", htmlspecialcharsbx($user), GetMessage("BPCGHLP_INVALID_USER")),
					);
				}
			}
		}

		return ($callbackFunction != null) ? array($arResult, $arResultAlt) : $arResult;
	}

	private function SearchUserByName($user)
	{
		$user = trim($user);
		if (strlen($user) <= 0)
			return array();

		$userId = 0;
		if ($user."|" == intval($user)."|")
			$userId = intval($user);

		if ($userId <= 0)
		{
			$arMatches = array();
			if (preg_match('#\[(\d+)\]#i', $user, $arMatches))
				$userId = intval($arMatches[1]);
		}

		$arResult = array();

		$dbUsers = false;
		if ($userId > 0)
		{
			$arFilter = array("ID_EQUAL_EXACT" => $userId);

			$dbUsers = CUser::GetList(
				($by = "LAST_NAME"),
				($order = "asc"),
				$arFilter,
				array(
					"NAV_PARAMS" => false,
				)
			);
		}
		else
		{
			$userLogin = "";
			$arMatches = array();
			if (preg_match('#\((.+?)\)#i', $user, $arMatches))
			{
				$userLogin = $arMatches[1];
				$user = trim(str_replace("(".$userLogin.")", "", $user));
			}

			$userEmail = "";
			$arMatches = array();
			if (preg_match("#<(.+?)>#i", $user, $arMatches))
			{
				if (check_email($arMatches[1]))
				{
					$userEmail = $arMatches[1];
					$user = Trim(Str_Replace("<".$userEmail.">", "", $user));
				}
			}

			$arUser = array();
			$arUserTmp = Explode(" ", $user);
			foreach ($arUserTmp as $s)
			{
				$s = Trim($s);
				if (StrLen($s) > 0)
					$arUser[] = $s;
			}
			if (strlen($userLogin) > 0)
				$arUser[] = $userLogin;

			$dbUsers = CUser::SearchUserByName($arUser, $userEmail, true);
		}

		if ($dbUsers)
		{
			while ($arUsers = $dbUsers->GetNext())
				$arResult[] = $arUsers["ID"];
		}

		return $arResult;
	}

	public static function FormatTimePeriod($period)
	{
		$period = intval($period);

		$days = intval($period / 86400);
		$period = $period - $days * 86400;

		$hours = intval($period / 3600);
		$period = $period - $hours * 3600;

		$minutes = intval($period / 60);
		$period = $period - $minutes * 60;

		$seconds = intval($period);

		$s = "";
		if ($days > 0)
			$s .= str_replace(
				array("#VAL#", "#UNIT#"),
				array($days, self::MakeWord($days, array(GetMessage("BPCGHLP_DAY1"), GetMessage("BPCGHLP_DAY2"), GetMessage("BPCGHLP_DAY3")))),
				"#VAL# #UNIT# "
			);
		if ($hours > 0)
			$s .= str_replace(
				array("#VAL#", "#UNIT#"),
				array($hours, self::MakeWord($hours, array(GetMessage("BPCGHLP_HOUR1"), GetMessage("BPCGHLP_HOUR2"), GetMessage("BPCGHLP_HOUR3")))),
				"#VAL# #UNIT# "
			);
		if ($minutes > 0)
			$s .= str_replace(
				array("#VAL#", "#UNIT#"),
				array($minutes, self::MakeWord($minutes, array(GetMessage("BPCGHLP_MIN1"), GetMessage("BPCGHLP_MIN2"), GetMessage("BPCGHLP_MIN3")))),
				"#VAL# #UNIT# "
			);
		if ($seconds > 0)
			$s .= str_replace(
				array("#VAL#", "#UNIT#"),
				array($seconds, self::MakeWord($seconds, array(GetMessage("BPCGHLP_SEC1"), GetMessage("BPCGHLP_SEC2"), GetMessage("BPCGHLP_SEC3")))),
				"#VAL# #UNIT# "
			);

		return $s;
	}

	private static function MakeWord($val, $arWords)
	{
		if ($val > 20)
			$val = ($val % 10);

		if ($val == 1)
			return $arWords[0];
		elseif ($val > 1 && $val < 5)
			return $arWords[1];
		else
			return $arWords[2];
	}

	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "=";
			$strNegative = 'N';
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	public static function PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arOrder = array_change_key_case($arOrder, CASE_UPPER);

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (!empty($arFields[$val]["FROM"]))
					{
						$toJoin = (array)$arFields[$val]["FROM"];
						foreach ($toJoin as $join)
						{
							if (in_array($join, $arAlreadyJoined))
								continue;
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $join;
							$arAlreadyJoined[] = $join;
						}
					}
				}
			}
		}
		// <-- GROUP BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		}
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
			{
				for ($i = 0, $cnt = count($arFieldsKeys); $i < $cnt; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
						continue;
					}

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (array_key_exists($arFieldsKeys[$i], $arOrder))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (array_key_exists($arFieldsKeys[$i], $arOrder))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (!empty($arFields[$arFieldsKeys[$i]]["FROM"]))
					{
						$toJoin = (array)$arFields[$arFieldsKeys[$i]]["FROM"];
						foreach ($toJoin as $join)
						{
							if (in_array($join, $arAlreadyJoined))
								continue;
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $join;
							$arAlreadyJoined[] = $join;
						}
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
						{
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						}
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (array_key_exists($val, $arOrder))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (array_key_exists($val, $arOrder))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}
						if (!empty($arFields[$val]["FROM"]))
						{
							$toJoin = (array)$arFields[$val]["FROM"];
							foreach ($toJoin as $join)
							{
								if (in_array($join, $arAlreadyJoined))
									continue;
								if (strlen($strSqlFrom) > 0)
									$strSqlFrom .= " ";
								$strSqlFrom .= $join;
								$arAlreadyJoined[] = $join;
							}
						}
					}
				}
			}

			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for ($i = 0, $cnt = count($filter_keys); $i < $cnt; $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);

			$key = $filter_keys[$i];
			$key_res = CBPHelper::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				for ($j = 0, $cntj = count($vals); $j < $cntj; $j++)
				{
					$val = $vals[$j];

					if (isset($arFields[$key]["WHERE"]))
					{
						$arSqlSearch_tmp1 = call_user_func_array(
								$arFields[$key]["WHERE"],
								array($val, $key, $strOperation, $strNegative, $arFields[$key]["FIELD"], $arFields, $arFilter)
							);
						if ($arSqlSearch_tmp1 !== false)
							$arSqlSearch_tmp[] = $arSqlSearch_tmp1;
					}
					else
					{
						if ($arFields[$key]["TYPE"] == "int")
						{
							if ((IntVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".IntVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "double")
						{
							$val = str_replace(",", ".", $val);

							if ((DoubleVal($val) == 0) && (strpos($strOperation, "=") !== False))
								$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND" : "OR")." ".(($strNegative == "Y") ? "NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." 0)";
							else
								$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".DoubleVal($val)." )";
						}
						elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						{
							if ($strOperation == "QUERY")
							{
								$arSqlSearch_tmp[] = GetFilterQuery($arFields[$key]["FIELD"], $val, "Y");
							}
							else
							{
								if ((strlen($val) == 0) && (strpos($strOperation, "=") !== False))
									$arSqlSearch_tmp[] = "(".$arFields[$key]["FIELD"]." IS ".(($strNegative == "Y") ? "NOT " : "")."NULL) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$DB->Length($arFields[$key]["FIELD"])." <= 0) ".(($strNegative == "Y") ? "AND NOT" : "OR")." (".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
								else
									$arSqlSearch_tmp[] = (($strNegative == "Y") ? " ".$arFields[$key]["FIELD"]." IS NULL OR NOT " : "")."(".$arFields[$key]["FIELD"]." ".$strOperation." '".$DB->ForSql($val)."' )";
							}
						}
						elseif ($arFields[$key]["TYPE"] == "datetime")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
						}
						elseif ($arFields[$key]["TYPE"] == "date")
						{
							if (strlen($val) <= 0)
								$arSqlSearch_tmp[] = ($strNegative=="Y"?"NOT":"")."(".$arFields[$key]["FIELD"]." IS NULL)";
							else
								$arSqlSearch_tmp[] = ($strNegative=="Y"?" ".$arFields[$key]["FIELD"]." IS NULL OR NOT ":"")."(".$arFields[$key]["FIELD"]." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "SHORT").")";
						}
					}
				}

				if (!empty($arFields[$key]["FROM"]))
				{
					$toJoin = (array)$arFields[$key]["FROM"];
					foreach ($toJoin as $join)
					{
						if (in_array($join, $arAlreadyJoined))
							continue;
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $join;
						$arAlreadyJoined[] = $join;
					}
				}

				$strSqlSearch_tmp = "";
				for ($j = 0, $cntj = count($arSqlSearch_tmp); $j < $cntj; $j++)
				{
					if ($j > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arSqlSearch_tmp[$j].")";
				}
				if ($strOrNull == "Y")
				{
					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." IS ".($strNegative=="Y" ? "NOT " : "")."NULL)";

					if (strlen($strSqlSearch_tmp) > 0)
						$strSqlSearch_tmp .= ($strNegative=="Y" ? " AND " : " OR ");
					if ($arFields[$key]["TYPE"] == "int" || $arFields[$key]["TYPE"] == "double")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." 0)";
					elseif ($arFields[$key]["TYPE"] == "string" || $arFields[$key]["TYPE"] == "char")
						$strSqlSearch_tmp .= "(".$arFields[$key]["FIELD"]." ".($strNegative=="Y" ? "<>" : "=")." '')";
				}

				if ($strSqlSearch_tmp != "")
					$arSqlSearch[] = "(".$strSqlSearch_tmp.")";
			}
		}

		for ($i = 0, $cnt = count($arSqlSearch); $i < $cnt; $i++)
		{
			if (strlen($strSqlWhere) > 0)
				$strSqlWhere .= " AND ";
			$strSqlWhere .= "(".$arSqlSearch[$i].")";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				if ($arFields[$by]["TYPE"] == "datetime" || $arFields[$by]["TYPE"] == "date")
					$arSqlOrder[] = " ".$by."_X1 ".$order." ";
				else
					$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (!empty($arFields[$by]["FROM"]))
				{
					$toJoin = (array)$arFields[$by]["FROM"];
					foreach ($toJoin as $join)
					{
						if (in_array($join, $arAlreadyJoined))
							continue;
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $join;
						$arAlreadyJoined[] = $join;
					}
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		for ($i = 0, $cnt = count($arSqlOrder); $i < $cnt; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}

	public static function ParseDocumentId($parameterDocumentId)
	{
		if (!is_array($parameterDocumentId))
			$parameterDocumentId = array($parameterDocumentId);

		$moduleId = "";
		$entity = "";
		$documentId = "";

		$cnt = count($parameterDocumentId);
		if ($cnt > 2)
		{
			$documentId = $parameterDocumentId[2];
			$entity = $parameterDocumentId[1];
			$moduleId = $parameterDocumentId[0];
		}
		elseif ($cnt == 2)
		{
			$documentId = $parameterDocumentId[1];
			$entity = $parameterDocumentId[0];
		}

		$moduleId = trim($moduleId);

		$documentId = trim($documentId);
		if (strlen($documentId) <= 0)
			throw new CBPArgumentNullException("documentId");

		$entity = trim($entity);
		if (strlen($entity) <= 0)
			throw new CBPArgumentNullException("entity");

		return array($moduleId, $entity, $documentId);
	}

	public static function ParseDocumentIdArray($parameterDocumentId)
	{
		if (!is_array($parameterDocumentId))
			$parameterDocumentId = array($parameterDocumentId);

		$moduleId = "";
		$entity = "";
		$documentId = "";

		$cnt = count($parameterDocumentId);
		if ($cnt > 2)
		{
			$documentId = $parameterDocumentId[2];
			$entity = $parameterDocumentId[1];
			$moduleId = $parameterDocumentId[0];
		}
		elseif ($cnt == 2)
		{
			$documentId = $parameterDocumentId[1];
			$entity = $parameterDocumentId[0];
		}

		$moduleId = trim($moduleId);

		$entity = trim($entity);
		if (strlen($entity) <= 0)
			throw new Exception("entity");

		if (is_array($documentId))
		{
			$a = array();
			foreach ($documentId as $v)
			{
				$v = trim($v);
				if (strlen($v) > 0)
					$a[] = $v;
			}
			$documentId = $a;
			if (count($documentId) <= 0)
				throw new CBPArgumentNullException("documentId");
		}
		else
		{
			$documentId = trim($documentId);
			if (strlen($documentId) <= 0)
				throw new CBPArgumentNullException("documentId");
			$documentId = array($documentId);
		}

		return array($moduleId, $entity, $documentId);
	}

	static public function GetFieldValuePrintable($fieldName, $fieldType, $result)
	{
		$newResult = null;

		switch ($fieldType)
		{
			case "user":
				if (is_array($result))
				{
					$newResult = array();
					foreach ($result as $r)
						$newResult[] = CBPHelper::ConvertUserToPrintableForm($r);
				}
				else
				{
					$newResult = CBPHelper::ConvertUserToPrintableForm($result);
				}
				break;

			case "file":
				if (is_array($result))
				{
					$newResult = array();
					foreach ($result as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$newResult[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$r."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$result = intval($result);
					$dbImg = CFile::GetByID($result);
					if ($arImg = $dbImg->Fetch())
						$newResult = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$result."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;

			default:
				$newResult = $result;
		}

		return $newResult;
	}

	static public function ConvertUserToPrintableForm($userId, $nameTemplate = "")
	{
		if (substr($userId, 0, strlen("user_")) == "user_")
			$userId = substr($userId, strlen("user_"));

		if (empty($nameTemplate))
			$nameTemplate = COption::GetOptionString("bizproc", "name_template", CSite::GetNameFormat(false), SITE_ID);

		$userId = intval($userId);

		$db = CUser::GetList(
			($by = "LAST_NAME"),
			($order = "asc"),
			array("ID_EQUAL_EXACT" => $userId),
			array(
				"NAV_PARAMS" => false,
			)
		);

		$str = "";
		if ($ar = $db->Fetch())
		{
			$str = CUser::FormatName($nameTemplate, $ar, true);
			$str = $str." [".$ar["ID"]."]";
			$str = str_replace(",", " ", $str);
		}

		return $str;
	}

	static public function GetJSFunctionsForFields($objectName, $arDocumentFields, $arDocumentFieldTypes)
	{
		ob_start();

		echo CAdminCalendar::ShowScript();
		?>
		<script type="text/javascript">
		<?= $objectName ?>.GetGUIFieldEdit = function(field, value, showAddButton, inputName)
		{
			alert("Deprecated method GetGUIFieldEdit used");

			if (!this.arDocumentFields[field])
				return "";

			if (typeof showAddButton == "undefined")
				showAddButton = false;

			if (typeof inputName == "undefined")
				inputName = field;

			var type = this.arDocumentFields[field]["Type"];

			var bAddSelection = false;
			var bAddButton = true;

			s = "";
			if (type == "int" || type == "double")
			{
				s += '<input type="text" size="10" id="id_' + field + '" name="' + inputName + '" value="' + this.HtmlSpecialChars(value) + '">';
			}
			else if (type == "select")
			{
				s += '<select name="' + inputName + '_1">';
				s += '<option value=""></option>';
				for (k in this.arDocumentFields[field]["Options"])
				{
					s += '<option value="' + k + '"' + (value == this.arDocumentFields[field]["Options"][k] ? " selected" : "") + '>' + this.arDocumentFields[field]["Options"][k] + '</option>';
					if (value == this.arDocumentFields[field]["Options"][k])
						value = "";
				}
				s += '</select>';
				bAddSelection = true;
			}
			else if (type == "file")
			{
				s += '<input type="file" id="id_' + field + '_1" name="' + inputName + '">';
				bAddSelection = true;
				bAddButton = true;
			}
			else if (type == "bool")
			{
				s += '<select name="' + inputName + '_1">';
				s += '<option value=""></option>';
				s += '<option value="Y"' + (value == "Y" ? " selected" : "") + '><?= GetMessage("BPCGHLP_YES") ?></option>';
				s += '<option value="N"' + (value == "N" ? " selected" : "") + '><?= GetMessage("BPCGHLP_NO") ?></option>';
				s += '</select>';
				bAddSelection = true;
				if (value == "Y" || value == "N")
					value = "";
			}
			else if (type == "datetime" || type == "date")
			{
				s += '<span style="white-space:nowrap;">';
				s += '<input type="text" name="' + inputName + '" id="id_' + field + '" size="10" value="' + this.HtmlSpecialChars(value) + '">';
				s += '<a href="javascript:void(0);" title="<?= GetMessage("BPCGHLP_CALENDAR") ?>">';
				s += '<img src="<?= ADMIN_THEMES_PATH ?>/<?= ADMIN_THEME_ID ?>/images/calendar/icon.gif" alt="<?= GetMessage("BPCGHLP_CALENDAR") ?>" class="calendar-icon" onclick="jsAdminCalendar.Show(this, \'' + inputName + '\', \'\', \'\', ' + ((type == "datetime") ? 'true' : 'false') + ', <?= time() + date("Z") + CTimeZone::GetOffset() ?>);" onmouseover="this.className+=\' calendar-icon-hover\';" onmouseout="this.className = this.className.replace(/\s*calendar-icon-hover/ig, \'\');">';
				s += '</a></span>';
			}
			else // type == "S"
			{
				s += '<input type="text" size="40" id="id_' + field + '" name="' + inputName + '" value="' + this.HtmlSpecialChars(value) + '">';
			}

			if (bAddSelection)
				s += '<br /><input type="text" id="id_' + field + '" name="' + inputName + '" value="' + this.HtmlSpecialChars(value) + '">';

			if (bAddButton && showAddButton)
				s += '<input type="button" value="..." onclick="BPAShowSelector(\'id_' + field + '\', \'' + type + '\');">';

			return s;
		}

		<?= $objectName ?>.SetGUIFieldEdit = function(field)
		{
			alert("Deprecated method SetGUIFieldEdit used");
		}

		<?= $objectName ?>.GetGUIFieldEditSimple = function(type, value, name)
		{
			alert("Deprecated method GetGUIFieldEditSimple used");

			if (typeof name == "undefined" || name.length <= 0)
				name = "BPVDDefaultValue";

			if (typeof value == "undefined")
			{
				value = "";

				var obj = document.getElementById('id_' + name);
				if (obj)
				{
					if (obj.type.substr(0, "select".length) == "select")
						value = obj.options[obj.selectedIndex].value;
					else
						value = obj.value;
				}
			}

			s = "";
			if (type == "file")
			{
				s += '';
			}
			else if (type == "bool")
			{
				s += '<select name="' + name + '" id="id_' + name + '">';
				s += '<option value=""></option>';
				s += '<option value="Y"' + (value == "Y" ? " selected" : "") + '><?= GetMessage("BPCGHLP_YES") ?></option>';
				s += '<option value="N"' + (value == "N" ? " selected" : "") + '><?= GetMessage("BPCGHLP_NO") ?></option>';
				s += '</select>';
			}
			else if (type == "user")
			{
				s += '<input type="text" size="10" id="id_' + name + '" name="' + name + '" value="' + this.HtmlSpecialChars(value) + '">';
				s += '<input type="button" value="..." onclick="BPAShowSelector(\'id_' + name + '\', \'user\')">';
			}
			else
			{
				s += '<input type="text" size="10" id="id_' + name + '" name="' + name + '" value="' + this.HtmlSpecialChars(value) + '">';
			}

			return s;
		}

		<?= $objectName ?>.SetGUIFieldEditSimple = function(type, name)
		{
			alert("Deprecated method SetGUIFieldEditSimple used");

			if (typeof name == "undefined" || name.length <= 0)
				name = "BPVDDefaultValue";

			s = "";
			if (type != "file")
			{
				var obj = document.getElementById('id_' + name);
				if (obj)
				{
					if (obj.type.substr(0, "select".length) == "select")
						s = obj.options[obj.selectedIndex].value;
					else
						s = obj.value;
				}
			}

			return s;
		}
		</script>
		<?
		$str = ob_get_contents();
		ob_end_clean();

		return $str;
	}

	static public function GetDocumentFieldTypes()
	{
		$arResult = array(
			"string" => array("Name" => GetMessage("BPCGHLP_PROP_STRING"), "BaseType" => "string"),
			"text" => array("Name" => GetMessage("BPCGHLP_PROP_TEXT"), "BaseType" => "text"),
			"int" => array("Name" => GetMessage("BPCGHLP_PROP_INT"), "BaseType" => "int"),
			"double" => array("Name" => GetMessage("BPCGHLP_PROP_DOUBLE"), "BaseType" => "double"),
			"select" => array("Name" => GetMessage("BPCGHLP_PROP_SELECT"), "BaseType" => "select"),
			"bool" => array("Name" => GetMessage("BPCGHLP_PROP_BOOL"), "BaseType" => "bool"),
			"date" => array("Name" => GetMessage("BPCGHLP_PROP_DATA"), "BaseType" => "date"),
			"datetime" => array("Name" => GetMessage("BPCGHLP_PROP_DATETIME"), "BaseType" => "datetime"),
			"user" => array("Name" => GetMessage("BPCGHLP_PROP_USER"), "BaseType" => "user"),
			"file" => array("Name" => GetMessage("BPCGHLP_PROP_FILE"), "BaseType" => "file"),
		);

		return $arResult;
	}

	/**
	 * @deprecated
	 */
	static public function GetGUIFieldEdit($documentType, $formName, $fieldName, $fieldValue, $arDocumentField, $bAllowSelection)
	{
		return self::GetFieldInputControl(
			$documentType,
			$arDocumentField,
			array("Form" => $formName, "Field" => $fieldName),
			$fieldValue,
			$bAllowSelection
		);
	}

	static public function GetFieldInputControl($documentType, $arFieldType, $arFieldName, $fieldValue, $bAllowSelection = false)
	{
		if (!is_array($fieldValue) || is_array($fieldValue) && CBPHelper::IsAssociativeArray($fieldValue))
			$fieldValue = array($fieldValue);

		ob_start();

		if ($arFieldType["Type"] == "select")
		{
			$fieldValueTmp = $fieldValue;
			?>
			<select id="id_<?= $arFieldName["Field"] ?>" name="<?= $arFieldName["Field"].($arFieldType["Multiple"] ? "[]" : "") ?>"<?= ($arFieldType["Multiple"] ? ' size="5" multiple' : '') ?>>
				<?
				if (!$arFieldType["Required"])
					echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
				foreach ($arFieldType["Options"] as $k => $v)
				{
					$ind = array_search($k, $fieldValueTmp);
					echo '<option value="'.htmlspecialcharsbx($k).'"'.($ind !== false ? ' selected' : '').'>'.htmlspecialcharsbx($v).'</option>';
					if ($ind !== false)
						unset($fieldValueTmp[$ind]);
				}
				?>
			</select>
			<?
			if ($bAllowSelection)
			{
				?>
				<br /><input type="text" id="id_<?= $arFieldName["Field"] ?>_text" name="<?= $arFieldName["Field"] ?>_text" value="<?
				if (count($fieldValueTmp) > 0)
				{
					$a = array_values($fieldValueTmp);
					echo htmlspecialcharsbx($a[0]);
				}
				?>">
				<input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>_text', 'select');">
				<?
			}
		}
		elseif ($arFieldType["Type"] == "user")
		{
			$fieldValue = CBPHelper::UsersArrayToString($fieldValue, null, $documentType);
			?><input type="text" size="40" id="id_<?= $arFieldName["Field"] ?>" name="<?= $arFieldName["Field"] ?>" value="<?= htmlspecialcharsbx($fieldValue) ?>"><input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>', 'user');"><?
		}
		else
		{
			if (!array_key_exists("CBPVirtualDocumentCloneRowPrinted", $GLOBALS) && $arFieldType["Multiple"])
			{
				$GLOBALS["CBPVirtualDocumentCloneRowPrinted"] = 1;
				?>
				<script language="JavaScript">
				<!--
				function CBPVirtualDocumentCloneRow(tableID)
				{
					var tbl = document.getElementById(tableID);
					var cnt = tbl.rows.length;
					var oRow = tbl.insertRow(cnt);
					var oCell = oRow.insertCell(0);
					var sHTML = tbl.rows[cnt - 1].cells[0].innerHTML;
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('[n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf(']', s);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 2, e - s));
						sHTML = sHTML.substr(0, s) + '[n' + (++n) + ']' + sHTML.substr(e + 1);
						p = s + 1;
					}
					var p = 0;
					while (true)
					{
						var s = sHTML.indexOf('__n', p);
						if (s < 0)
							break;
						var e = sHTML.indexOf('_', s + 2);
						if (e < 0)
							break;
						var n = parseInt(sHTML.substr(s + 3, e - s));
						sHTML = sHTML.substr(0, s) + '__n' + (++n) + '_' + sHTML.substr(e + 1);
						p = e + 1;
					}
					oCell.innerHTML = sHTML;
					var patt = new RegExp('<' + 'script' + '>[^\000]*?<' + '\/' + 'script' + '>', 'ig');
					var code = sHTML.match(patt);
					if (code)
					{
						for (var i = 0; i < code.length; i++)
						{
							if (code[i] != '')
							{
								var s = code[i].substring(8, code[i].length - 9);
								jsUtils.EvalGlobal(s);
							}
						}
					}
				}
				//-->
				</script>
				<?
			}

			if ($arFieldType["Multiple"])
				echo '<table width="100%" border="0" cellpadding="2" cellspacing="2" id="CBPVirtualDocument_'.$arFieldName["Field"].'_Table">';

			if ($bAllowSelection)
			{
				$arFieldType["BaseType"] = "string";

				static $arDocumentTypes = null;
				if (is_null($arDocumentTypes))
					$arDocumentTypes = self::GetDocumentFieldTypes($documentType);

				if (array_key_exists($arFieldType["Type"], $arDocumentTypes))
					$arFieldType["BaseType"] = $arDocumentTypes[$arFieldType["Type"]]["BaseType"];
			}

			$fieldValueTmp = $fieldValue;

			$ind = -1;
			foreach ($fieldValue as $key => $value)
			{
				$ind++;
				$fieldNameId = 'id_'.$arFieldName["Field"].'__n'.$ind.'_';
				$fieldNameName = $arFieldName["Field"].($arFieldType["Multiple"] ? "[n".$ind."]" : "");

				if ($arFieldType["Multiple"])
					echo '<tr><td>';

				switch ($arFieldType["Type"])
				{
					case "int":
					case "double":
						unset($fieldValueTmp[$key]);
						?><input type="text" size="10" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
						break;
					case "file":
						unset($fieldValueTmp[$key]);
						?><input type="file" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?
						break;
					case "bool":
						if (in_array($value, array("Y", "N")))
							unset($fieldValueTmp[$key]);
						?>
						<select id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>">
							<?
							if (!$arFieldType["Required"])
								echo '<option value="">['.GetMessage("BPCGHLP_NOT_SET").']</option>';
							?>
							<option value="Y"<?= (in_array("Y", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_YES") ?></option>
							<option value="N"<?= (in_array("N", $fieldValue) ? ' selected' : '') ?>><?= GetMessage("BPCGHLP_NO") ?></option>
						</select>
						<?
						break;
					case "text":
						unset($fieldValueTmp[$key]);
						?><textarea rows="5" cols="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>"><?= htmlspecialcharsbx($value) ?></textarea><?
						break;
					case "date":
					case "datetime":
						$v = "";
						if (!CBPActivity::isExpression($value))
						{
							$v = $value;
							unset($fieldValueTmp[$key]);
						}
						require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/interface/init_admin.php");
						echo CAdminCalendar::CalendarDate($fieldNameName, $v, 19, ($arFieldType["Type"] == "date"));
						break;
					default:
						unset($fieldValueTmp[$key]);
						?><input type="text" size="40" id="<?= $fieldNameId ?>" name="<?= $fieldNameName ?>" value="<?= htmlspecialcharsbx($value) ?>"><?
				}

				if ($bAllowSelection)
				{
					if (!in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")))
					{
						?><input type="button" value="..." onclick="BPAShowSelector('<?= $fieldNameId ?>', '<?= $arFieldType["BaseType"] ?>');"><?
					}
				}

				if ($arFieldType["Multiple"])
					echo '</td></tr>';
			}

			if ($arFieldType["Multiple"])
				echo "</table>";

			if ($arFieldType["Multiple"])
				echo '<input type="button" value="'.GetMessage("BPCGHLP_ADD").'" onclick="CBPVirtualDocumentCloneRow(\'CBPVirtualDocument_'.$arFieldName["Field"].'_Table\')"/><br />';

			if ($bAllowSelection)
			{
				if (in_array($arFieldType["Type"], array("file", "bool", "date", "datetime")))
				{
					?>
					<input type="text" id="id_<?= $arFieldName["Field"] ?>_text" name="<?= $arFieldName["Field"] ?>_text" value="<?
					if (count($fieldValueTmp) > 0)
					{
						$a = array_values($fieldValueTmp);
						echo htmlspecialcharsbx($a[0]);
					}
					?>">
					<input type="button" value="..." onclick="BPAShowSelector('id_<?= $arFieldName["Field"] ?>_text', '<?= $arFieldType["BaseType"] ?>');">
					<?
				}
			}
		}

		$s = ob_get_contents();
		ob_end_clean();

		return $s;
	}

	public static function GetFieldInputValue($documentType, $arFieldType, $arFieldName, $arRequest, &$arErrors)
	{
		$result = array();

		if ($arFieldType["Type"] == "user")
		{
			$value = $arRequest[$arFieldName["Field"]];
			if (strlen($value) > 0)
			{
				$result = CBPHelper::UsersStringToArray($value, $documentType, $arErrors);
				if (count($arErrors) > 0)
				{
					foreach ($arErrors as $e)
						$arErrors[] = $e;
				}
			}
		}
		elseif (array_key_exists($arFieldName["Field"], $arRequest) || array_key_exists($arFieldName["Field"]."_text", $arRequest))
		{
			$arValue = array();
			if (array_key_exists($arFieldName["Field"], $arRequest))
			{
				$arValue = $arRequest[$arFieldName["Field"]];
				if (!is_array($arValue) || is_array($arValue) && CBPHelper::IsAssociativeArray($arValue))
					$arValue = array($arValue);
			}
			if (array_key_exists($arFieldName["Field"]."_text", $arRequest))
				$arValue[] = $arRequest[$arFieldName["Field"]."_text"];

			foreach ($arValue as $value)
			{
				if (!CBPActivity::isExpression($value))
				{
					if ($arFieldType["Type"] == "int")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", $value);
							if ($value."|" == intval($value)."|")
							{
								$value = intval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID1"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "double")
					{
						if (strlen($value) > 0)
						{
							$value = str_replace(" ", "", str_replace(",", ".", $value));
							if ($value."|" == doubleval($value)."|")
							{
								$value = doubleval($value);
							}
							else
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID11"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					elseif ($arFieldType["Type"] == "select")
					{
						if (!is_array($arFieldType["Options"]) || count($arFieldType["Options"]) <= 0 || strlen($value) <= 0)
						{
							$value = null;
						}
						elseif (!array_key_exists($value, $arFieldType["Options"]))
						{
							$value = null;
							$arErrors[] = array(
								"code" => "ErrorValue",
								"message" => GetMessage("BPCGWTL_INVALID35"),
								"parameter" => $arFieldName["Field"],
							);
						}
					}
					elseif ($arFieldType["Type"] == "bool")
					{
						if ($value !== "Y" && $value !== "N")
						{
							if ($value === true)
							{
								$value = "Y";
							}
							elseif ($value === false)
							{
								$value = "N";
							}
							elseif (strlen($value) > 0)
							{
								$value = strtolower($value);
								if (in_array($value, array("y", "yes", "true", "1")))
								{
									$value = "Y";
								}
								elseif (in_array($value, array("n", "no", "false", "0")))
								{
									$value = "N";
								}
								else
								{
									$value = null;
									$arErrors[] = array(
										"code" => "ErrorValue",
										"message" => GetMessage("BPCGWTL_INVALID45"),
										"parameter" => $arFieldName["Field"],
									);
								}
							}
							else
							{
								$value = null;
							}
						}
					}
					elseif ($arFieldType["Type"] == "file")
					{
						if (array_key_exists("name", $value) && strlen($value["name"]) > 0)
						{
							if (!array_key_exists("MODULE_ID", $value) || strlen($value["MODULE_ID"]) <= 0)
								$value["MODULE_ID"] = "bizproc";

							$value = CFile::SaveFile($value, "bizproc_wf", true, true);
							if (!$value)
							{
								$value = null;
								$arErrors[] = array(
									"code" => "ErrorValue",
									"message" => GetMessage("BPCGWTL_INVALID915"),
									"parameter" => $arFieldName["Field"],
								);
							}
						}
						else
						{
							$value = null;
						}
					}
					else
					{
						if (!is_array($value) && strlen($value) <= 0)
							$value = null;
					}
				}

				if ($value != null)
					$result[] = $value;
			}
		}

		if (!$arFieldType["Multiple"])
		{
			if (count($result) > 0)
				$result = $result[0];
			else
				$result = null;
		}

		return $result;
	}

	public static function GetFieldInputValuePrintable($documentType, $arFieldType, $fieldValue)
	{
		$result = $fieldValue;

		switch ($arFieldType['Type'])
		{
			case "user":
				$result = CBPHelper::UsersArrayToString($fieldValue, null, $documentType);
				break;

			case "bool":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
						$result[] = ((strtoupper($r) == "Y") ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				else
				{
					$result = ((strtoupper($fieldValue) == "Y") ? GetMessage("BPVDX_YES") : GetMessage("BPVDX_NO"));
				}
				break;

			case "file":
				if (is_array($fieldValue))
				{
					$result = array();
					foreach ($fieldValue as $r)
					{
						$r = intval($r);
						$dbImg = CFile::GetByID($r);
						if ($arImg = $dbImg->Fetch())
							$result[] = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$r."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
					}
				}
				else
				{
					$fieldValue = intval($fieldValue);
					$dbImg = CFile::GetByID($fieldValue);
					if ($arImg = $dbImg->Fetch())
						$result = "[url=/bitrix/tools/bizproc_show_file.php?f=".urlencode($arImg["FILE_NAME"])."&i=".$fieldValue."]".htmlspecialcharsbx($arImg["ORIGINAL_NAME"])."[/url]";
				}
				break;
			case "select":
				if (isset($arFieldType["Options"][$fieldValue]))
					$result = $arFieldType["Options"][$fieldValue];

				break;
		}

		return $result;
	}

	static function SetGUIFieldEdit($documentType, $fieldName, $arRequest, &$arErrors, $arDocumentField = null)
	{
		return self::GetFieldInputValue($documentType, $arDocumentField, array("Field" => $fieldName), $arRequest, $arErrors);
	}

	public static function ConvertTextForMail($text, $siteId = false)
	{
		if (is_array($text))
			$text = implode(', ', $text);

		$text = trim($text);
		if (strlen($text) <= 0)
			return "";

		if (!$siteId)
			$siteId = SITE_ID;

		$arPattern = array();
		$arReplace = array();

		$arPattern[] = "/\[(code|quote)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>================== \\1 ===================\n";

		$arPattern[] = "/\[\/(code|quote)(.*?)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n>===========================================\n";

		$arPattern[] = "/\<WBR[\s\/]?\>/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		$arPattern[] = "/^(\r|\n)+?(.*)$/";
		$arReplace[] = "\\2";

		$arPattern[] = "/\[b\](.+?)\[\/b\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\[i\](.+?)\[\/i\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\\1";

		$arPattern[] = "/\[u\](.+?)\[\/u\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\[s\](.+?)\[\/s\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "_\\1_";

		$arPattern[] = "/\[(\/?)(color|font|size)([^\]]*)\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "";

		//$arPattern[] = "/\[url\](\S+?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		//$arReplace[] = "(URL: \\1)";

		//$arPattern[] = "/\[url\s*=\s*(\S+?)\s*\](.*?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER;
		//$arReplace[] = "\\2 (URL: \\1)";

		$arPattern[] = "/\[img\](.+?)\[\/img\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(IMAGE: \\1)";

		$arPattern[] = "/\[video([^\]]*)\](.+?)\[\/video[\s]*\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "(VIDEO: \\2)";

		$arPattern[] = "/\[(\/?)list\]/is".BX_UTF_PCRE_MODIFIER;
		$arReplace[] = "\n";

		$text = preg_replace($arPattern, $arReplace, $text);


		$dbSite = CSite::GetByID($siteId);
		$arSite = $dbSite->Fetch();
		static::$serverName = $arSite["SERVER_NAME"];
		if (strLen(static::$serverName) <= 0)
		{
			if (defined("SITE_SERVER_NAME") && strlen(SITE_SERVER_NAME) > 0)
				static::$serverName = SITE_SERVER_NAME;
			else
				static::$serverName = COption::GetOptionString("main", "server_name", "");
		}

		$text = preg_replace_callback(
			"/\[url\]([^\]]+?)\[\/url\]/i".BX_UTF_PCRE_MODIFIER,
			array("CBPHelper", "__ConvertAnchorTag"),
			$text
		);
		$text = preg_replace_callback(
			"/\[url\s*=\s*([^\]]+?)\s*\](.*?)\[\/url\]/is".BX_UTF_PCRE_MODIFIER,
			array("CBPHelper", "__ConvertAnchorTag"),
			$text
		);

		return $text;
	}

	public static function __ConvertAnchorTag($url, $text = '', $serverName = '')
	{
		if (is_array($url))
		{
			$text = isset($url[2]) ? $url[2] : $url[1];
			$url = $url[1];
			$serverName = static::$serverName;
		}

		if (substr($url, 0, 1) != "/" && !preg_match("/^(http|news|https|ftp|aim|mailto)\:\/\//i".BX_UTF_PCRE_MODIFIER, $url))
			$url = 'http://'.$url;
		if (!preg_match("/^(http|https|news|ftp|aim):\/\/[-_:.a-z0-9@]+/i".BX_UTF_PCRE_MODIFIER, $url))
			$url = $serverName.$url;
		if (!preg_match("/^(http|news|https|ftp|aim|mailto)\:\/\//i".BX_UTF_PCRE_MODIFIER, $url))
			$url = 'http://'.$url;

		$url = str_replace(' ', '%20', $url);

		if (strlen($text) > 0)
			return $text." ( ".$url." )";

		return $url;
	}

	public static function IsAssociativeArray($ar)
	{
		$fl = false;

		$arKeys = array_keys($ar);
		$ind = -1;
		$indn = -1;
		foreach ($arKeys as $key)
		{
			$ind++;
			if ($key."!" !== $ind."!")
			{
				if (substr($key, 0, 1) === 'n')
				{
					$indn++;
					if (($indn === 0) && ("".$key === "n1"))
						$indn++;

					if ("".$key !== "n".$indn)
					{
						$fl = true;
						break;
					}
				}
				else
				{
					$fl = true;
					break;
				}
			}
		}

		return $fl;
	}

	public static function ExtractUsersFromUserGroups($value, $activity)
	{
		$result = array();

		if (!is_array($value))
			$value = array($value);

		$l = strlen("user_");
		$runtime = CBPRuntime::GetRuntime();
		$documentService = $runtime->GetService("DocumentService");

		foreach ($value as $v)
		{
			if (substr($v, 0, $l) == "user_")
			{
				$result[] = $v;
			}
			else
			{
				$arDSUsers = self::extractUsersFromExtendedGroup($v);
				if ($arDSUsers === false)
					$arDSUsers = $documentService->GetUsersFromUserGroup($v, $activity->GetDocumentId());
				foreach ($arDSUsers as $v1)
					$result[] = "user_".$v1;
			}
		}

		return $result;
	}

	/**
	 * Method return array of user ids, extracting from special codes. Supported: user (U), group (G),
	 * intranet (IU, D, DR, Dextranet, UA), socnet (SU, SG1_A, SG1_E, SG1_K)
	 *
	 * @param string $code - group code, ex. group_D1
	 * @return bool|array
	 */
	public static function extractUsersFromExtendedGroup($code)
	{
		if (strpos($code, 'group_') !== 0)
			return false;
		$code = strtoupper(substr($code, strlen('group_')));

		if (strpos($code, 'G') === 0)
		{
			$group = (int)substr($code, 1);
			if ($group <= 0)
				return array();
			$result = array();

			$iterator = CUser::GetList(($b = "ID"), ($o = "ASC"), array("GROUPS_ID" => $group, "ACTIVE" => "Y"));
			while ($user = $iterator->fetch())
				$result[] = $user['ID'];

			return $result;
		}

		if (preg_match('/^(U|IU|SU)([0-9]+)$/i', $code, $match))
		{
			return array($match[2]);
		}

		if ($code == 'UA' && CModule::IncludeModule('intranet'))
		{
			$result = array();
			$iterator = CUser::GetList(($by="id"), ($order="asc"),
				array('ACTIVE' => 'Y', '>UF_DEPARTMENT' => 0),
				array('FIELDS' => array('ID'))
			);
			while($user = $iterator->fetch())
			{
				$result[] = $user['ID'];
			}
			return $result;
		}

		if (preg_match('/^(D|DR)([0-9]+)$/', $code, $match) && CModule::IncludeModule('intranet'))
		{
			$recursive = $match[1] == 'DR';
			$id = $match[2];
			$iblockId = COption::GetOptionInt('intranet', 'iblock_structure');
			$departmentIds = array($id);

			if ($recursive)
			{
				$iterator = CIBlockSection::GetList(
					array('ID' => 'ASC'),
					array('=IBLOCK_ID' => $iblockId, 'ID'=> $id),
					false,
					array('ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL')
				);
				$section = $iterator->fetch();
				$filter = array (
					'=IBLOCK_ID' => $iblockId,
					">LEFT_MARGIN" => $section["LEFT_MARGIN"],
					"<RIGHT_MARGIN" => $section["RIGHT_MARGIN"],
					">DEPTH_LEVEL" => $section['DEPTH_LEVEL'],
				);
				$iterator = CIBlockSection::GetList(array("left_margin"=>"asc"), $filter, false, array('ID'));
				while($section = $iterator->fetch())
				{
					$departmentIds[] =  $section['ID'];
				}
				unset($iterator, $section, $filter);
			}
			$result = array();
			$iterator = CUser::GetList(($by="id"), ($order="asc"),
				array('ACTIVE' => 'Y', 'UF_DEPARTMENT' => $departmentIds),
				array('FIELDS' => array('ID'))
			);
			while($user = $iterator->fetch())
			{
				$result[] = $user['ID'];
			}
			return $result;
		}
		if ($code == 'Dextranet' && CModule::IncludeModule('extranet'))
		{
			$result = array();
			$iterator = CUser::GetList(($by="id"), ($order="asc"),
				array(COption::GetOptionString("extranet", "extranet_public_uf_code", "UF_PUBLIC") => "1",
					"!UF_DEPARTMENT" => false,
					"GROUPS_ID" => array(CExtranet::GetExtranetUserGroupID())
				),
				array('FIELDS' => array('ID'))
			);
			while($user = $iterator->fetch())
			{
				$result[] = $user['ID'];
			}
			return $result;
		}
		if (preg_match('/^SG([0-9]+)_?([AEK])?$/', $code, $match) && CModule::IncludeModule('socialnetwork'))
		{
			$groupId = (int)$match[1];
			$role = isset($match[2])? $match[2] : 'K';

			$iterator = CSocNetUserToGroup::GetList(
				array("USER_ID" => "ASC"),
				array(
					"=GROUP_ID" => $groupId,
					"<=ROLE" => $role,
					"USER_ACTIVE" => "Y"
				),
				false,
				false,
				array("USER_ID")
			);
			$result = array();
			while($user = $iterator->fetch())
			{
				$result[] = $user['USER_ID'];
			}
			return $result;
		}

		return false;
	}

	public static function ExtractUsers($arUsersDraft, $documentId, $bFirst = false)
	{
		$result = array();

		if (!is_array($arUsersDraft))
			$arUsersDraft = array($arUsersDraft);

		$l = strlen("user_");

		$runtime = CBPRuntime::GetRuntime();
		$documentService = $runtime->GetService("DocumentService");

		foreach ($arUsersDraft as $user)
		{
			if (substr($user, 0, $l) === "user_")
			{
				$user = intval(substr($user, $l));
				if (($user > 0) && !in_array($user, $result))
				{
					if ($bFirst)
						return $user;
					$result[] = $user;
				}
			}
			else
			{
				$users = self::extractUsersFromExtendedGroup($user);
				if ($users === false)
					$users = $documentService->GetUsersFromUserGroup($user, $documentId);
				foreach ($users as $u)
				{
					$u = (int)$u;
					if (($u > 0) && !in_array($u, $result))
					{
						if ($bFirst)
							return $u;
						$result[] = $u;
					}
				}
			}
		}

		if (!$bFirst)
			return $result;

		if (count($result) > 0)
			return $result[0];

		return null;
	}

	public static function MakeArrayFlat($ar)
	{
		if (!is_array($ar))
			return array($ar);

		$result = array();

		if (!CBPHelper::IsAssociativeArray($ar) && (count($ar) == 2) && in_array($ar[0], array("Variable", "Document", "Template", "Workflow", "User", "System")) && is_string($ar[1]))
		{
			$result[] = $ar;
			return $result;
		}

		foreach ($ar as $val)
		{
			if (!is_array($val))
			{
				if (trim($val) !== "")
					$result[] = $val;
			}
			else
			{
				foreach (self::MakeArrayFlat($val) as $val1)
					$result[] = $val1;
			}
		}

		return $result;
	}

	public static function getBool($value)
	{
		return (empty($value) || is_int($value) && ($value == 0) || (strtoupper($value) == 'N')) ? false : true;
	}

	public static function isEmptyValue($value)
	{
		return $value === null || $value === '' || is_array($value) && sizeof($value) <= 0;
	}

	public static function ConvertParameterValues($val)
	{
		$result = $val;

		if (is_string($val) && preg_match(CBPActivity::ValuePattern, $val, $arMatches))
		{
			$result = null;
			if ($arMatches['object'] == "User")
			{
				if ($GLOBALS["USER"]->IsAuthorized())
					$result = "user_".$GLOBALS["USER"]->GetID();
			}
			elseif ($arMatches['object'] == "System")
			{
				if ($arMatches['field'] == "Now")
					$result = date($GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("FULL")));
				elseif ($arMatches['field'] == "Date")
					$result = date($GLOBALS["DB"]->DateFormatToPHP(CSite::GetDateFormat("SHORT")));
			}
		}

		return $result;
	}

	public static function StripUserPrefix($value)
	{
		if (is_array($value) && !CBPHelper::IsAssociativeArray($value))
		{
			foreach ($value as &$v)
			{
				if (substr($v, 0, 5) == "user_")
					$v = substr($v, 5);
			}
		}
		else
		{
			if (substr($value, 0, 5) == "user_")
				$value = substr($value, 5);
		}

		return $value;
	}

	/**
	 * @param $userId
	 * @return array
	 */
	public static function getUserExtendedGroups($userId)
	{
		if (!isset(self::$groupsCache[$userId]))
		{
			self::$groupsCache[$userId] = array();
			$access = self::getAccessProvider();
			$userCodes = $access->GetUserCodesArray($userId);
			foreach ($userCodes AS $code)
			{
				self::$groupsCache[$userId][] = 'group_'.strtolower($code);
			}
		}
		return self::$groupsCache[$userId];
	}

	/**
	 * @param string $group - Extended group code (ex. group_g1)
	 * @param bool $appendId - Append id to group name
	 * @return string
	 */
	public static function getExtendedGroupName($group, $appendId = true)
	{
		if (strpos($group, 'group_') === 0)
			$group = substr($group, strlen('group_'));
		$group = strtoupper($group);
		$access = self::getAccessProvider();
		$arNames = $access->GetNames(array($group));
		return $arNames[$group]['provider'].' '.$arNames[$group]['name'].($appendId? ' ['.$group.']' : '');
	}

	/**
	 * @param $users
	 * @return array
	 */

	public static function convertToExtendedGroups($users)
	{
		$users = (array)$users;
		foreach ($users as &$user)
		{
			if (strpos($user, 'user_') === 0)
			{
				$user = 'group_u'.substr($user, strlen('user_'));
			}
			elseif (preg_match('#^[0-9]+$#', $user))
			{
				$user = 'group_g'.$user;
			}
			else
				$user = strtolower($user);
		}
		return $users;
	}

	/**
	 * @param $users
	 * @param bool $extractUsers
	 * @return array
	 */

	public static function convertToSimpleGroups($users, $extractUsers = false)
	{
		$users = (array)$users;
		$converted = array();

		foreach ($users as $user)
		{
			$user = strtolower($user);
			if (strpos($user, 'group_u') === 0)
			{
				$converted[] = 'user_'.substr($user, strlen('group_u'));
			}
			elseif (strpos($user, 'group_g') === 0)
			{
				$converted[] = substr($user, strlen('group_g'));
			}
			elseif (strpos($user, 'group_') === 0)
			{
				if ($extractUsers)
				{
					$extracted = self::extractUsersFromExtendedGroup($user);
					if ($extracted !== false)
					{
						foreach ($extracted as $exUser)
						{
							$converted[] = 'user_'.$exUser;
						}
					}
				}
			}
			else
				$converted[] = $user;
		}
		return $converted;
	}

	public static function getForumId()
	{
		$forumId = COption::GetOptionString('bizproc', 'forum_id', 0);
		if (!$forumId && CModule::includeModule('forum'))
		{
			$defaultSiteId = CSite::GetDefSite();
			$forumId = CForumNew::Add(array(
				'NAME' => 'Bizproc Workflow',
				'XML_ID' => 'bizproc_workflow',
				'SITES' => array($defaultSiteId => '/'),
				'ACTIVE' => 'Y',
				'DEDUPLICATION' => 'N'
			));
			COption::SetOptionString("bizproc", "forum_id", $forumId);
		}

		return $forumId;
	}

	public static function getDistrName()
	{
		if (CModule::IncludeModule('bitrix24'))
			return static::DISTR_B24;
		return static::DISTR_BOX;
	}

	/**
	 * @param int $headUserId
	 * @param int $subUserId
	 * @return bool
	 */
	public static function checkUserSubordination($headUserId, $subUserId)
	{
		if (CModule::IncludeModule('intranet'))
		{
			$headUserId = (int)$headUserId;
			$subUserId = (int)$subUserId;

			if ($headUserId && $subUserId)
			{
				$headDepts = (array) CIntranetUtils::GetSubordinateDepartments($headUserId, true);
				if (!empty($headDepts))
				{
					$subDepts = (array) CIntranetUtils::GetUserDepartments($subUserId);
					return (sizeof(array_intersect($headDepts, $subDepts)) > 0);
				}
			}
		}
		return false;
	}
}

if (!function_exists("bpdump"))
{
	function bpdump($var, $name = "", $tofile = true)
	{
		$result = "";
		if (is_array($var) || is_object($var))
		{
			$result .= ($tofile ? "\n" : "<br />");
			if (strlen($name) > 0)
				$result .= $name.($tofile ? "\n" : "<br /><pre>");
			else
				$result .= ($tofile ? "" : "<pre>");
			$result .= print_r($var, true);
			$result .= ($tofile ? "\n" : "</pre><br />");
		}
		else
		{
			$result .= ($tofile ? "\n" : "<br />");
			if (strlen($name) > 0)
				$result .= $name."=";
			$result .= $var.";";
			$result .= ($tofile ? "\n" : "<br />");
		}

		if ($tofile)
		{
			$tempFile = fopen($_SERVER["DOCUMENT_ROOT"]."/++++++++++bp.log", "a");
			fwrite($tempFile, $result);
			fclose($tempFile);
		}
		else
		{
			echo '<div style="background-color:#000; color:#0a0; font-size:14px; padding:10px;">';
			echo $result;
			echo '</div>';
		}
	}
}
?>