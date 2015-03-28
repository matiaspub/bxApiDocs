<?
IncludeModuleLangFile(__FILE__);

class CCrmExternalSale
{
	public static function GetProxySettings()
	{
		return array(
			"PROXY_SCHEME" => COption::GetOptionString("crm", "proxy_scheme", ""),
			"PROXY_HOST" => COption::GetOptionString("crm", "proxy_host", ""),
			"PROXY_PORT" => COption::GetOptionString("crm", "proxy_port", ""),
			"PROXY_USERNAME" => COption::GetOptionString("crm", "proxy_username", ""),
			"PROXY_PASSWORD" => COption::GetOptionString("crm", "proxy_password", "")
		);
	}

	public static function GetLimitationSettings()
	{
		return array(
			"MAX_SHOPS" => intval(COption::GetOptionString("crm", "~limit_max_shops", "0")),
			"MAX_DAYS" => intval(COption::GetOptionString("crm", "~limit_max_days", "0")),
		);
	}

	public static function GetDefaultSettings($id)
	{
		$result = null;

		if (!is_array($id))
		{
			$id = intval($id);
			$dbResult = self::GetList(array(), array("ID" => $id, "ACTIVE" => "Y"));
			$id = $dbResult->Fetch();
			if (!$id)
				return null;
		}

		$result["NAME"] = $id["NAME"];
		if (empty($result["NAME"]) && isset($id["SERVER"]))
			$result["NAME"] = $id["SERVER"];
		if (empty($result["NAME"]))
			$result["NAME"] = "-";

		$result["PREFIX"] = $id["IMPORT_PREFIX"];
		if (empty($result["PREFIX"]))
			$result["PREFIX"] = $result["NAME"];

		$result["PROBABILITY"] = isset($id["IMPORT_PROBABILITY"]) ? intval($id["IMPORT_PROBABILITY"]) : 0;
		if($result["PROBABILITY"] <= 0)
			$result["PROBABILITY"] = intval(COption::GetOptionString("crm", "sale_deal_probability", "100"));

		$result["RESPONSIBLE"] = isset($id["IMPORT_RESPONSIBLE"]) ? intval($id["IMPORT_RESPONSIBLE"]) : 0;
		if ($result["RESPONSIBLE"] <= 0)
			$result["RESPONSIBLE"] = intval(COption::GetOptionString("crm", "sale_deal_assigned_by_id", "0"));

		$result["GROUP_ID"] = $id["IMPORT_GROUP_ID"];

		$result["PUBLIC"] = $id["IMPORT_PUBLIC"];
		if (empty($result["PUBLIC"]))
			$result["PUBLIC"] = COption::GetOptionString("crm", "sale_deal_opened", "Y");

		$result["LABEL"] = intval($id["MODIFICATION_LABEL"]);
		$result["SIZE"] = intval($id["IMPORT_SIZE"]);
		if ($result["SIZE"] <= 0)
			$result["SIZE"] = 10;
		$result["PERIOD"] = intval($id["IMPORT_PERIOD"]);
		if ($result["PERIOD"] <= 0)
			$result["PERIOD"] = 7;
		$result["ERRORS"] = intval($id["IMPORT_ERRORS"]);

		return $result;
	}

	public static function Add($arFields)
	{
		global $DB;

		if (isset($arFields['ID']))
			unset($arFields['ID']);
		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);

		$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		$arFields['~DATE_UPDATE'] = $DB->CurrentTimeFunction();

		if (!self::CheckFields($arFields))
			return false;

		$result = $DB->Add('b_crm_external_sale', $arFields, array("COOKIE", "LAST_STATUS"), "crm", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		$result = intval($result);

		return ($result > 0) ? $result : false;
	}

	private static function CheckFields(&$arFields, $id = 0)
	{
		$id = intval($id);

		if ((is_set($arFields, "ACTIVE") || ($id <= 0)) && ($arFields["ACTIVE"] != "Y" && $arFields["ACTIVE"] != "N"))
			$arFields["ACTIVE"] = "Y";

		if ((is_set($arFields, "LOGIN") || ($id <= 0)) && strlen($arFields["LOGIN"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CES_LOGIN_EMPTY"), "EMPTY_LOGIN");
			return false;
		}
		if ((is_set($arFields, "PASSWORD") || ($id <= 0)) && strlen($arFields["PASSWORD"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CES_PASSWORD_EMPTY"), "EMPTY_PASSWORD");
			return false;
		}

		if ((is_set($arFields, "SCHEME") || ($id <= 0)) && ($arFields["SCHEME"] != "http" && $arFields["SCHEME"] != "https"))
			$arFields["SCHEME"] = "http";
		if ((is_set($arFields, "PORT") || ($id <= 0)) && intval($arFields["PORT"]) <= 0)
			$arFields["PORT"] = 80;
		if ((is_set($arFields, "SERVER") || ($id <= 0)) && strlen($arFields["SERVER"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("CES_SERVER_EMPTY"), "EMPTY_SERVER");
			return false;
		}

		return true;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;

		$ID = intval($ID);

		if (isset($arFields['ID']))
			unset($arFields['ID']);
		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		if (isset($arFields['DATE_UPDATE']))
			unset($arFields['DATE_UPDATE']);

		$arFields['~DATE_UPDATE'] = $DB->CurrentTimeFunction();

		if (!self::CheckFields($arFields, $ID))
			return false;

		$strSql = $DB->PrepareUpdate('b_crm_external_sale', $arFields, "crm");

		$arBinds = array();
		if (is_set($arFields, "COOKIE"))
			$arBinds["COOKIE"] = $arFields["COOKIE"];
		if (is_set($arFields, "LAST_STATUS"))
			$arBinds["LAST_STATUS"] = $arFields["LAST_STATUS"];

		$DB->QueryBind(
			"UPDATE b_crm_external_sale SET ".$strSql." WHERE ID = ".$ID,
			$arBinds,
			false
		);

		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;

		$ID = intval($ID);
		return $DB->Query("DELETE FROM b_crm_external_sale WHERE ID = ".$ID, false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
	}

	public static function Count()
	{
		global $DB;

		$db = $DB->Query("SELECT COUNT(ID) as CNT FROM b_crm_external_sale", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if ($ar = $db->Fetch())
			return intval($ar["CNT"]);

		return 0;
	}

	public static function GetList($arOrder = array(), $arFilter = array())
	{
		global $DB;

		$arSelectFields = array('ID', 'ACTIVE', 'DATE_CREATE', 'DATE_UPDATE', 'NAME', 'SCHEME', 'SERVER', 'PORT', 'LOGIN', 'PASSWORD', 'MODIFICATION_LABEL', 'IMPORT_SIZE', 'COOKIE', 'LAST_STATUS', 'IMPORT_PERIOD', "IMPORT_PROBABILITY", "IMPORT_RESPONSIBLE", "IMPORT_PUBLIC", "IMPORT_PREFIX", "IMPORT_ERRORS", 'IMPORT_GROUP_ID', 'LAST_STATUS_DATE');

		static $arFields = array(
			"ID" => array("FIELD" => "E.ID", "TYPE" => "int"),
			"ACTIVE" => array("FIELD" => "E.ACTIVE", "TYPE" => "string"),
			"DATE_CREATE" => array("FIELD" => "E.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_UPDATE" => array("FIELD" => "E.DATE_UPDATE", "TYPE" => "datetime"),
			"NAME" => array("FIELD" => "E.NAME", "TYPE" => "string"),
			"SCHEME" => array("FIELD" => "E.SCHEME", "TYPE" => "string"),
			"SERVER" => array("FIELD" => "E.SERVER", "TYPE" => "string"),
			"PORT" => array("FIELD" => "E.PORT", "TYPE" => "int"),
			"LOGIN" => array("FIELD" => "E.LOGIN", "TYPE" => "string"),
			"PASSWORD" => array("FIELD" => "E.PASSWORD", "TYPE" => "string"),
			"MODIFICATION_LABEL" => array("FIELD" => "E.MODIFICATION_LABEL", "TYPE" => "int"),
			"IMPORT_SIZE" => array("FIELD" => "E.IMPORT_SIZE", "TYPE" => "int"),
			"COOKIE" => array("FIELD" => "E.COOKIE", "TYPE" => "string"),
			"LAST_STATUS" => array("FIELD" => "E.LAST_STATUS", "TYPE" => "string"),
			"IMPORT_PERIOD" => array("FIELD" => "E.IMPORT_PERIOD", "TYPE" => "int"),
			"IMPORT_PROBABILITY" => array("FIELD" => "E.IMPORT_PROBABILITY", "TYPE" => "int"),
			"IMPORT_RESPONSIBLE" => array("FIELD" => "E.IMPORT_RESPONSIBLE", "TYPE" => "int"),
			"IMPORT_PUBLIC" => array("FIELD" => "E.IMPORT_PUBLIC", "TYPE" => "string"),
			"IMPORT_PREFIX" => array("FIELD" => "E.IMPORT_PREFIX", "TYPE" => "string"),
			"IMPORT_ERRORS" => array("FIELD" => "E.IMPORT_ERRORS", "TYPE" => "int"),
			"IMPORT_GROUP_ID" => array("FIELD" => "E.IMPORT_GROUP_ID", "TYPE" => "int"),
			"LAST_STATUS_DATE" => array("FIELD" => "E.LAST_STATUS_DATE", "TYPE" => "datetime"),
		);

		$arSqls = self::PrepareSql($arFields, $arOrder, $arFilter, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_crm_external_sale E ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $dbRes;
	}

	private static function GetFilterOperation($key)
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
			$strOperation = "IN";
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

	private static function PrepareSql(&$arFields, $arOrder, $arFilter, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlOrderBy = "";

		$arAlreadyJoined = array();

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
			$arSelectFields = array($arSelectFields);

		if (!isset($arSelectFields)
			|| !is_array($arSelectFields)
			|| count($arSelectFields)<=0
			|| in_array("*", $arSelectFields))
		{
			for ($i = 0; $i < count($arFieldsKeys); $i++)
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

				if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
					&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
					&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
					$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
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

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}

		$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		// <-- SELECT

		// WHERE -->
		$arSqlSearch = Array();

		if (!is_array($arFilter))
			$filter_keys = Array();
		else
			$filter_keys = array_keys($arFilter);

		for ($i = 0; $i < count($filter_keys); $i++)
		{
			$vals = $arFilter[$filter_keys[$i]];
			if (!is_array($vals))
				$vals = array($vals);

			$key = $filter_keys[$i];
			$key_res = self::GetFilterOperation($key);
			$key = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$strOrNull = $key_res["OR_NULL"];

			if (array_key_exists($key, $arFields))
			{
				$arSqlSearch_tmp = array();
				for ($j = 0; $j < count($vals); $j++)
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

				if (isset($arFields[$key]["FROM"])
					&& strlen($arFields[$key]["FROM"]) > 0
					&& !in_array($arFields[$key]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$key]["FROM"];
					$arAlreadyJoined[] = $arFields[$key]["FROM"];
				}

				$strSqlSearch_tmp = "";
				for ($j = 0; $j < count($arSqlSearch_tmp); $j++)
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

		for ($i = 0; $i < count($arSqlSearch); $i++)
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

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		for ($i=0; $i<count($arSqlOrder); $i++)
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
			"ORDERBY" => $strSqlOrderBy
		);
	}
}
