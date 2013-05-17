<?php
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/main.php");
IncludeModuleLangFile(__FILE__);
	
class CSupportSearch
{
	static $searchModule = null;
	const TICKET_SEARCH = "b_ticket_search";

	static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . $module_id . "/install/version.php");
		return "<br>Module: " . $module_id . " (" . $arModuleVersion["VERSION"] . ")<br>Class: CSupportSearch<br>File: " . __FILE__;
	}
	
	public static function GetFilterQuery($q, $idName, $titleName, $messageName, &$error)
	{
		if(!self::CheckModule()) return "";
		$res = self::ParseQ(HTMLToTxt($q));
		$res = self::PrepareQuery($res, $idName, $titleName, $messageName, $error);
		return $res;
	}
	
	public static function PrepareQuery($q, $idName, $titleName, $messageName, &$error)
	{
		global $DB;
		$state = 0;
		$sRes = "";
		$n = 0;
		$error = "";
		$errorno = 0;
		$i = 0;
		$quote = "";
		$inQuoteS = "";
		$arrQ = explode(" ", $q);
		foreach($arrQ as $k => $t)
		{
			$t = trim($t);
			if(strlen($t) <= 0)
			{
				continue;
			}
			switch ($state)
			{
				case 0:
					if(($t == "||") || ($t == "&&") || ($t == ")"))
					{
						$error = GetMessage("FILTER_ERROR2") . " " . $t;
						$errorno = 2;
						break 2;
					}
					elseif($t == "!")
					{
						$state = 0;
						$sRes .= " NOT";
					}
					elseif($t == "(")
					{
						$n++;
						$state = 0;
						$sRes .= " (";
					}
					elseif(($t == '"') || ($t == "'"))
					{
						$quote = $t;
						$state = 2;
					}
					else
					{
						$state = 1;
						$sRes .= self::GetSQLfilter($t, $idName, $titleName, $messageName);
					}
					break;

				case 1:
					if(($t == "||") || ($t == "&&"))
					{
						$state = 0;
						if($t == "||") $sRes .= " OR";
						else $sRes .= " AND";
					}
					elseif($t == ")")
					{
						$n--;
						$state = 1;
						$sRes .= ")";
					}
					else
					{
						$error = GetMessage("FILTER_ERROR2") . " " . $t;
						$errorno = 2;
						break 2;
					}
					break;
				case 2:
					if($t == $quote)
					{
						$state = 1;
						$inQuoteS = "%" . str_replace("^", " ", $inQuoteS) . "%";
						$sRes .= "\n ($titleName LIKE '" . $DB->ForSql($inQuoteS) . "' OR $messageName LIKE '" . $DB->ForSql($inQuoteS) . "')";
						$inQuoteS = "";
						$quote = "";
					}
					elseif(($t != "'") && ($t != '"'))
					{
						$inQuoteS .= $t;
					}
					break;
				}
		}
		
		if(($errorno == 0) && ($n != 0))
		{
			$error = GetMessage("FILTER_ERROR1");
			$errorno = 1;
		}
		if(($errorno == 0) && ($quote != ""))
		{
			$error = GetMessage("FILTER_ERROR4");
			$errorno = 3;
		}
		if($errorno > 0) 
		{
		return null;
		}
		return $sRes;
	}
	
	public static function ParseQ($q)
	{
		$q = trim($q);
		if(strlen($q) <= 0)
		{
			return '';
		}
		$q=self::ParseStr($q, "^");

		$q = str_replace(
			array("&"   , "|"   , "~"  , "("  , ")", '"', "'"),
			array(" && ", " || ", " ! ", " (", ") ", ' " ', " ' "),
			$q
		);
		$q="($q)";
		$q = preg_replace("/\\s+/".BX_UTF_PCRE_MODIFIER, " ", $q);

		return $q;
	}
	
	public static function ParseStr($qwe, $default_op = "&")
	{
		$qwe=trim($qwe);

		$qwe=preg_replace("/\\s{0,}\\+ {0,}/", "&", $qwe);

		$qwe=preg_replace("/\\s{0,}([()|~]) {0,}/", "\\1", $qwe);

		$qwe=preg_replace("/(\\s{1,}|\\&\\|{1,}|\\|\\&{1,})/", $default_op, $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\\|+/", "|", $qwe);
		$qwe=preg_replace("/\\&+/", "&", $qwe);
		$qwe=preg_replace("/\\~+/", "~", $qwe);
		$qwe=preg_replace("/\\|\\&\\|/", "&", $qwe);
		$qwe=preg_replace("/[|&~]+$/", "", $qwe);
		$qwe=preg_replace("/^[|&]+/", "", $qwe);

		// transform "w1 ~w2" -> "w1 default_op ~ w2"
		// ") ~w" -> ") default_op ~w"
		// "w ~ (" -> "w default_op ~("
		// ") w" -> ") default_op w"
		// "w (" -> "w default_op ("
		// ")(" -> ") default_op ("

		$qwe=preg_replace("/([^&~|()]+)~([^&~|()]+)/", "\\1".$default_op."~\\2", $qwe);
		$qwe=preg_replace("/\\)~{1,}/", ")".$default_op."~", $qwe);
		$qwe=preg_replace("/~{1,}\\(/", ($default_op=="|"? "~|(": "&~("), $qwe);
		$qwe=preg_replace("/\\)([^&~|()]+)/", ")".$default_op."\\1", $qwe);
		$qwe=preg_replace("/([^&~|()]+)\\(/", "\\1".$default_op."(", $qwe);
		$qwe=preg_replace("/\\) *\\(/", ")".$default_op."(", $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\\|+/", "|", $qwe);
		$qwe=preg_replace("/\\&+/", "&", $qwe);

		// remove errornous format of query - ie: '(&', '&)', '(|', '|)', '~&', '~|', '~)'
		$qwe=preg_replace("/\\(\\&{1,}/", "(", $qwe);
		$qwe=preg_replace("/\\&{1,}\\)/", ")", $qwe);
		$qwe=preg_replace("/\\~{1,}\\)/", ")", $qwe);
		$qwe=preg_replace("/\\(\\|{1,}/", "(", $qwe);
		$qwe=preg_replace("/\\|{1,}\\)/", ")", $qwe);
		$qwe=preg_replace("/\\~{1,}\\&{1,}/", "&", $qwe);
		$qwe=preg_replace("/\\~{1,}\\|{1,}/", "|", $qwe);

		$qwe=preg_replace("/\\(\\)/", "", $qwe);
		$qwe=preg_replace("/^[|&]{1,}/", "", $qwe);
		$qwe=preg_replace("/[|&~]{1,}\$/", "", $qwe);
		$qwe=preg_replace("/\\|\\&/", "&", $qwe);
		$qwe=preg_replace("/\\&\\|/", "|", $qwe);

		// remove unnesessary boolean operators
		$qwe=preg_replace("/\\|+/", "|", $qwe);
		$qwe=preg_replace("/\\&+/", "&", $qwe);

		return($qwe);
	}
	
	static function CheckModule()
	{
		if(self::$searchModule == null)
		{
			self::$searchModule = CModule::IncludeModule("search");
		}
		return self::$searchModule;
	}
	
	static function GetSQLfilter($s, $idName, $titleName, $messageName)
	{
		global $DB;
		$res = "";
		$and = "";
		$arrQ = explode("^", $s);
		foreach($arrQ as $k => $v)
		{
			if(substr_count($v, "%") > 0) $res .= self::StrInEXISTS($and, $idName, "LIKE", $v);
			else
			{
				$resArr = stemming($v, LANGUAGE_ID);
				if(count($resArr) > 0)
				{
					foreach($resArr as $k2 => $v2) $res .= self::StrInEXISTS($and, $idName, "=", $k2);
				}
				else
				{
					$res .= "\n" . $and . " ($titleName = '" . $DB->ForSql($v) . "' OR $messageName = '" . $DB->ForSql($v) . "')";
				}
			}
			$and = " AND";
		}
		if($res != "") $res = "\n(" . $res . "\n)\n";
		return $res;
	}
	
	static function StrInEXISTS($and, $idName, $sign, $key)
	{
		global $DB;
		$ticketSearch = self::TICKET_SEARCH;
		return "\n" . $and . " EXISTS(SELECT 1 FROM $ticketSearch WHERE MESSAGE_ID = $idName AND SEARCH_WORD $sign '" . $DB->ForSql($key) . "')";
	}
	
	static function WriteWordsInTable($M_ID, $SITE_ID, $s)
	{
		global $DB;
		if(!self::CheckModule()) return;
		$err_mess = (self::err_mess()) . "<br>Function: writeWordsInTable<br>Line: ";
		$M_ID = intval($M_ID);
		$ticketSearch = self::TICKET_SEARCH;
		$rsSite = CSite::GetByID($SITE_ID);
		$arrSite = $rsSite->Fetch();
		$langID = $arrSite["LANGUAGE_ID"];
		
		$DB->Query("DELETE FROM $ticketSearch WHERE MESSAGE_ID = $M_ID", false, $err_mess . __LINE__);
		$res = stemming(HTMLToTxt($s), $langID);
		foreach($res as $key => $val)
		{
			$strSql = "INSERT INTO " . $ticketSearch . "(MESSAGE_ID, SEARCH_WORD) VALUES ($M_ID, '" . $DB->ForSql($key) . "')";
			$res = $DB->Query($strSql, false, $err_mess . __LINE__);
			//$DB->Insert($ticketSearch, array("MESSAGE_ID" => $M_ID, "SEARCH_WORD" => "'" . $DB->ForSql($key) . "'"), $err_mess . __LINE__);
		}
	}
	
	static function ReindexMessages($firstID, $periodS = 8)
	{		
		global $DB;
		$firstID = intval( $firstID);
		
		$err_mess = (self::err_mess()) . "<br>Function: reindexMessages<br>Line: ";
		$endTime = time() + $periodS;
		$strSql = "
			SELECT
				T.SITE_ID,
				TM.ID,
				TM.MESSAGE
			FROM
				b_ticket T
				INNER JOIN b_ticket_message TM
					ON T.ID = TM.TICKET_ID
						AND TM.ID > $firstID
						AND TM.IS_LOG = 'N'
			ORDER BY TM.ID";
		$res = $DB->Query($strSql, false, $err_mess.__LINE__);
		$lastID = 0;
		while($cs = $res->Fetch())
		{
			if(time() > $endTime) return $lastID;
			self::WriteWordsInTable($cs["ID"], $cs["SITE_ID"], $cs["MESSAGE"]);
			$lastID = intval($cs["ID"]);
		}
		return -1;
	}
	
}