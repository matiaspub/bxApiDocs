<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/filter_dictionary.php");

/**
 * <b>CFilterDictionary</b> - класс для работы cо словарями нецензурных слов.</body> </html>
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cfilterdictionary/index.php
 * @author Bitrix
 */
class CFilterDictionary extends CAllFilterDictionary
{
	public static function GetList($arOrder = array("ID"=>"ASC"), $arFilter = array(), $bCount = false)
	{
		global $DB;
		$arSqlSearch = array();
		$strSqlSearch = "";
		$arSqlOrder = array();
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		
		foreach ($arFilter as $key => $val)
		{
			$key_res = CFilterDictionary::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			
			switch ($key)
			{
				case "TYPE":
				case "TITLE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FD.".$key." IS NULL OR LENGTH(FD.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FD.".$key." IS NULL OR NOT ":"")."(FD.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "ID":
					if ($strOperation!="IN")
					{
						if (intVal($val)<=0)
							$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FD.ID IS NULL OR FD.ID<=0)";
						else
							$arSqlSearch[] = ($strNegative=="Y"?" FD.ID IS NULL OR NOT ":"")."(FD.ID ".$strOperation." ".intVal($val)." )";
					}
					else
					{
						if (!is_array($val))
							$val = explode(',', $val);
						$val_int=array();
						foreach($val as $v)
							$val_int[] = intval($v);
						$val = implode(', ', $val_int);
						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FD.ID IN (".$DB->ForSql($val).") )";
					}
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = "WHERE (".implode(") AND (", $arSqlSearch).")";

		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if ($by == "ID") $arSqlOrder[] = " FD.ID ".$order." ";
			elseif ($by == "TITLE") $arSqlOrder[] = " FD.TITLE ".$order." ";
			else
			{
				$arSqlOrder[] = " FD.ID ".$order." ";
				$by = "ID";
			}
		}
		DelDuplicateSort($arSqlOrder); 
		if (!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		if ($bCount)
		{
			$strSql = "SELECT COUNT(FD.ID) as CNT FROM b_forum_dictionary FD ".$strSqlSearch;				
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = 0;
			if ($ar_res = $db_res->Fetch())
				$iCnt = intVal($ar_res["CNT"]);
			return $iCnt;
		}
		$strSql = "SELECT FD.ID, FD.TITLE, FD.TYPE FROM b_forum_dictionary FD ".$strSqlSearch.$strSqlOrder;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}
}
class CFilterLetter extends CAllFilterLetter
{
	public static function GetList($arOrder = array("ID"=>"ASC"), $arFilter = array(), $bCount = false)
	{
		global $DB;
		$arSqlSearch = array();
		$strSqlSearch = "";
		$arSqlOrder = array();
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		
		foreach ($arFilter as $key => $val)
		{
			$key_res = CFilterDictionary::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "LETTER":
				case "REPLACEMENT":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FL.".$key." IS NULL OR LENGTH(FL.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FL.".$key." IS NULL OR NOT ":"")."(FL.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "DICTIONARY_ID":
				case "ID":
					if ($strOperation!="IN")
					{
						if (intVal($val)<=0)
							$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FL.".$key." IS NULL OR FL.".$key."<=0)";
						else
							$arSqlSearch[] = ($strNegative=="Y"?" FL.".$key." IS NULL OR NOT ":"")."(FL.".$key." ".$strOperation." ".intVal($val)." )";
					}
					else
					{
						if (!is_array($val))
							$val = explode(',', $val);
						$val_int=array();
						foreach($val as $v)
							$val_int[] = intval($v);
						$val = implode(', ', $val_int);
						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FL.".$key." IN (".$DB->ForSql($val).") )";
					}
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).") ";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if ($by == "ID") $arSqlOrder[] = " FL.ID ".$order." ";
			elseif ($by == "TITLE") $arSqlOrder[] = " FD.TITLE ".$order." ";
			elseif ($by == "LETTER") $arSqlOrder[] = " FL.LETTER ".$order." ";
			elseif ($by == "REPLACEMENT") $arSqlOrder[] = " FL.REPLACEMENT ".$order." ";
			else
			{
				$arSqlOrder[] = " FL.ID ".$order." ";
				$by = "ID";
			}
		}
		DelDuplicateSort($arSqlOrder); 
		if (!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(") AND (", $arSqlOrder);

		if ($bCount)
		{
			$strSql = "SELECT COUNT(FD.ID) as CNT ".
				"FROM b_forum_letter FL, b_forum_dictionary FD ".
				"WHERE (FL.DICTIONARY_ID = FD.ID) ".
				$strSqlSearch;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = 0;
			if ($ar_res = $db_res->Fetch())
				$iCnt = intVal($ar_res["CNT"]);
			return $iCnt;
		}
		$strSql = 
			"SELECT FL.ID, FL.LETTER, FL.REPLACEMENT, FL.DICTIONARY_ID, FD.TITLE ".
			"FROM b_forum_letter FL, b_forum_dictionary FD ".
			"WHERE (FL.DICTIONARY_ID = FD.ID) ".
			$strSqlSearch.
			$strSqlOrder;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}
}
class CFilterUnquotableWords extends CAllFilterUnquotableWords
{
	public static function GetList($arOrder = array("ID"=>"ASC"), $arFilter = array(), $bCount = false)
	{
		global $DB;
		$arSqlSearch = array();
		$strSqlSearch = "";
		$arSqlOrder = array();
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		
		foreach ($arFilter as $key => $val)
		{
			$key_res = CFilterUnquotableWords::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			switch ($key)
			{
				case "WORDS":
				case "USE_IT":
				case "PATTERN":
				case "REPLACEMENT":
				case "DESCRIPTION":
				case "PATTERN_CREATE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR LENGTH(FM.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "ID":
				case "DICTIONARY_ID":
					if (intVal($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FM.".$key." IS NULL OR FM.".$key."<=0)";
					else 
					{
						if ($strOperation!="IN")
						{
								$arSqlSearch[] = ($strNegative=="Y"?" FM.".$key." IS NULL OR NOT ":"")."(FM.".$key." ".$strOperation." ".intVal($val)." )";
						}
						else
						{
							if (!is_array($val))
								$val = explode(',', $val);
							$val_int=array();
							foreach($val as $v)
								$val_int[] = intval($v);
							$val = implode(', ', $val_int);
							$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FM.".$key." IN (".$DB->ForSql($val).") )";
						}
					}
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = " WHERE (".implode(") AND (", $arSqlSearch).")";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if ($by == "ID") $arSqlOrder[] = " FM.ID ".$order." ";
			elseif ($by == "WORDS") $arSqlOrder[] = " FM.WORDS ".$order." ";
			elseif ($by == "PATTERN") $arSqlOrder[] = " FM.PATTERN ".$order." ";
			elseif ($by == "REPLACEMENT") $arSqlOrder[] = " FM.REPLACEMENT ".$order." ";
			elseif ($by == "DESCRIPTION") $arSqlOrder[] = " FM.DESCRIPTION ".$order." ";
			elseif ($by == "USE_IT") $arSqlOrder[] = " FM.USE_IT ".$order." ";
			else
			{
				$arSqlOrder[] = " FM.ID ".$order." ";
				$by = "ID";
			}
		}
		DelDuplicateSort($arSqlOrder); 
		if (!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		if ($bCount)
		{
			$strSql = 
				"SELECT COUNT(FM.ID) as CNT ".
				"FROM b_forum_filter FM ".
				$strSqlSearch;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = 0;
			if ($ar_res = $db_res->Fetch())
				$iCnt = intVal($ar_res["CNT"]);
			return $iCnt;
		}
		$strSql = "SELECT FM.ID, FM.DICTIONARY_ID, FM.WORDS, FM.PATTERN, FM.REPLACEMENT, FM.DESCRIPTION,  FM.USE_IT, FM.PATTERN_CREATE ".
			"FROM b_forum_filter FM ".
			$strSqlSearch.
			$strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}
}
?>