<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/short_uri.php");

class CBXShortUri
	extends CBXAllShortUri
{
	public static function Add($arFields)
	{
		global $DB;

		self::ClearErrors();

		if (!self::ParseFields($arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_short_uri", $arFields);

		$strSql =
			"INSERT INTO b_short_uri (".$arInsert[0].", MODIFIED) ".
			"VALUES(".$arInsert[1].", ".$DB->CurrentTimeFunction().")";
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		$taskId = intval($DB->LastID());

		$arFields["ID"] = $taskId;

		foreach (GetModuleEvents("main", "OnAfterShortUriAdd", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array($arFields));

		return $taskId;
	}

	public static function GetList($arOrder = array("ID" => "DESC"), $arFilter = array(), $arNavStartParams = false)
	{
		global $DB;

		self::ClearErrors();

		$arWherePart = array();
		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				$key = strtoupper($key);
				switch($key)
				{
					case "ID":
						$arWherePart[] = "U.ID=".intval($val);
						break;
					case "URI":
						$q = GetFilterQuery("U.URI", $val);
						if (!empty($q) && ($q != "0"))
							$arWherePart[] = $q;
						break;
					case "URI_EXACT":
						$arWherePart[] = "U.URI='".$DB->ForSQL($val)."'";
						break;
					case "URI_CRC":
						$arWherePart[] = "U.URI_CRC=".intval($val);
						break;
					case "SHORT_URI":
						$arWherePart[] = "U.SHORT_URI='".$DB->ForSQL($val)."'";
						break;
					case "SHORT_URI_CRC":
						$arWherePart[] = "U.SHORT_URI_CRC=".intval($val);
						break;
					case "STATUS":
						$arWherePart[] = "U.STATUS=".intval($val);
						break;
					case "MODIFIED_1":
						$arWherePart[] = "U.MODIFIED >= FROM_UNIXTIME('".MkDateTime(FmtDate($val, "D.M.Y"), "d.m.Y")."')";
						break;
					case "MODIFIED_2":
						$arWherePart[] = "U.MODIFIED <= FROM_UNIXTIME('".MkDateTime(FmtDate($val, "D.M.Y")." 23:59:59", "d.m.Y")."')";
						break;
					case "LAST_USED_1":
						$arWherePart[] = "U.LAST_USED >= FROM_UNIXTIME('".MkDateTime(FmtDate($val, "D.M.Y"), "d.m.Y")."')";
						break;
					case "LAST_USED_2":
						$arWherePart[] = "U.LAST_USED <= FROM_UNIXTIME('".MkDateTime(FmtDate($val, "D.M.Y")." 23:59:59", "d.m.Y")."')";
						break;
					case "NUMBER_USED":
						$arWherePart[] = "U.NUMBER_USED=".intval($val);
						break;
				}
			}
		}

		$strWherePart = "";
		if (count($arWherePart) > 0)
		{
			foreach ($arWherePart as $val)
			{
				if ($strWherePart !== "")
					$strWherePart .= " AND ";
				$strWherePart .= "(".$val.")";
			}
		}
		if ($strWherePart !== "")
			$strWherePart = "WHERE ".$strWherePart;

		$arOrderByPart = array();
		if (is_array($arOrder))
		{
			foreach ($arOrder as $key => $val)
			{
				$key = strtoupper($key);
				if (!in_array($key, array("ID", "URI", "URI_CRC", "SHORT_URI", "SHORT_URI_CRC", "STATUS", "MODIFIED", "LAST_USED", "NUMBER_USED")))
					continue;
				$val = strtoupper($val);
				if (!in_array($val, array("ASC", "DESC")))
					$val = "ASC";
				if ($key == "MODIFIED")
					$key = "MODIFIED1";
				if ($key == "LAST_USED")
					$key = "LAST_USED1";
				$arOrderByPart[] = $key." ".$val;
			}
		}

		$strOrderByPart = "";
		if (count($arOrderByPart) > 0)
		{
			foreach ($arOrderByPart as $val)
			{
				if ($strOrderByPart !== "")
					$strOrderByPart .= ", ";
				$strOrderByPart .= $val;
			}
		}
		if ($strOrderByPart !== "")
			$strOrderByPart = "ORDER BY ".$strOrderByPart;

		$strSql = "FROM b_short_uri U ".$strWherePart;

		if ($arNavStartParams)
		{
			$dbResultCount = $DB->Query("SELECT COUNT(U.ID) as C ".$strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$arResultCount = $dbResultCount->Fetch();
			$strSql = "SELECT ID, URI, URI_CRC, SHORT_URI, SHORT_URI_CRC, STATUS, ".$DB->DateToCharFunction("MODIFIED")." MODIFIED, MODIFIED MODIFIED1, ".$DB->DateToCharFunction("LAST_USED")." LAST_USED, LAST_USED LAST_USED1, NUMBER_USED ".$strSql.$strOrderByPart;
			$dbResult = new CDBResult();
			$dbResult->NavQuery($strSql, $arResultCount["C"], $arNavStartParams);
		}
		else
		{
			$strSql = "SELECT ID, URI, URI_CRC, SHORT_URI, SHORT_URI_CRC, STATUS, ".$DB->DateToCharFunction("MODIFIED")." MODIFIED, MODIFIED MODIFIED1, ".$DB->DateToCharFunction("LAST_USED")." LAST_USED, LAST_USED LAST_USED1, NUMBER_USED ".$strSql.$strOrderByPart;
			$dbResult = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbResult;
	}
}
?>