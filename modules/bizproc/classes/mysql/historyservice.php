<?
include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/bizproc/classes/general/historyservice.php");

class CBPHistoryService
	extends CBPAllHistoryService
{
	static public function __construct()
	{
		parent::__construct();
	}

	static public function AddHistory($arFields)
	{
		global $DB;

		self::ParseFields($arFields, 0);

		$arInsert = $DB->PrepareInsert("b_bp_history", $arFields);

		$strSql =
			"INSERT INTO b_bp_history (".$arInsert[0].", MODIFIED) ".
			"VALUES(".$arInsert[1].", ".$DB->CurrentTimeFunction().")";
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		$ID = intval($DB->LastID());

		$arEventParams = array(
			"ID" => $ID,
			"DOCUMENT_ID" => array($arFields['MODULE_ID'], $arFields['ENTITY'], $arFields['DOCUMENT_ID']),
		);
		$rsEvents = GetModuleEvents('bizproc', 'OnAddToHistory');
		while ($arEvent = $rsEvents->Fetch())
			$result = ExecuteModuleEventEx($arEvent, array($arEventParams));

		return $ID;
	}

	static public function UpdateHistory($id, $arFields)
	{
		global $DB;

		$id = intval($id);
		if ($id <= 0)
			throw new CBPArgumentNullException("id");

		self::ParseFields($arFields, $id);

		$strUpdate = $DB->PrepareUpdate("b_bp_history", $arFields);

		$strSql =
			"UPDATE b_bp_history SET ".
			"	".$strUpdate.", ".
			"	MODIFIED = ".$DB->CurrentTimeFunction()." ".
			"WHERE ID = ".intval($id)." ";
		$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $id;
	}

	public function GetHistoryList($arOrder = array("ID" => "DESC"), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
 		global $DB;

		if (count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "MODULE_ID", "ENTITY", "DOCUMENT_ID", "NAME", "DOCUMENT", "MODIFIED", "USER_ID");

		if (count(array_intersect($arSelectFields, array("MODULE_ID", "ENTITY", "DOCUMENT_ID"))) > 0)
		{
			if (!in_array("MODULE_ID", $arSelectFields))
				$arSelectFields[] = "MODULE_ID";
			if (!in_array("ENTITY", $arSelectFields))
				$arSelectFields[] = "ENTITY";
			if (!in_array("DOCUMENT_ID", $arSelectFields))
				$arSelectFields[] = "DOCUMENT_ID";
		}

		if (array_key_exists("DOCUMENT_ID", $arFilter))
		{
			$d = CBPHelper::ParseDocumentId($arFilter["DOCUMENT_ID"]);
			$arFilter["MODULE_ID"] = $d[0];
			$arFilter["ENTITY"] = $d[1];
			$arFilter["DOCUMENT_ID"] = $d[2];
		}

		static $arFields = array(
			"ID" => Array("FIELD" => "H.ID", "TYPE" => "int"),
			"MODULE_ID" => Array("FIELD" => "H.MODULE_ID", "TYPE" => "string"),
			"ENTITY" => Array("FIELD" => "H.ENTITY", "TYPE" => "string"),
			"DOCUMENT_ID" => Array("FIELD" => "H.DOCUMENT_ID", "TYPE" => "string"),
			"NAME" => Array("FIELD" => "H.NAME", "TYPE" => "string"),
			"DOCUMENT" => Array("FIELD" => "H.DOCUMENT", "TYPE" => "string"),
			"MODIFIED" => Array("FIELD" => "H.MODIFIED", "TYPE" => "datetime"),
 			"USER_ID" => Array("FIELD" => "H.USER_ID", "TYPE" => "int"),

			"USER_NAME" => Array("FIELD" => "U.NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (H.USER_ID = U.ID)"),
			"USER_LAST_NAME" => Array("FIELD" => "U.LAST_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (H.USER_ID = U.ID)"),
			"USER_SECOND_NAME" => Array("FIELD" => "U.SECOND_NAME", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (H.USER_ID = U.ID)"),
			"USER_LOGIN" => Array("FIELD" => "U.LOGIN", "TYPE" => "string", "FROM" => "INNER JOIN b_user U ON (H.USER_ID = U.ID)"),
		);

		$arSqls = CBPHelper::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_bp_history H ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!1!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return False;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_bp_history H ".
			"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";

		if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) <= 0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
				"FROM b_bp_history H ".
				"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			//echo "!2.1!=".htmlspecialcharsbx($strSql_tmp)."<br>";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				// ТОЛЬКО ДЛЯ MYSQL!!! ДЛЯ ORACLE ДРУГОЙ КОД
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			//echo "!2.3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && IntVal($arNavStartParams["nTopCount"]) > 0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			//echo "!3!=".htmlspecialcharsbx($strSql)."<br>";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$dbRes = new CBPHistoryResult($dbRes, $this->useGZipCompression);
		return $dbRes;
	}

}
?>