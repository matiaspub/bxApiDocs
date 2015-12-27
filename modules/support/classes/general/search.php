<?php
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/main.php");
IncludeModuleLangFile(__FILE__);
	
class CSupportSearch
{
	static $searchModule;
	const TABLE_NAME = "b_ticket_search";

	static function err_mess()
	{
		$module_id = "support";
		@include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/" . $module_id . "/install/version.php");
		return "<br>Module: " . $module_id . " <br>Class: CSupportSearch<br>File: " . __FILE__;
	}

	public static function getSql($query)
	{
		if (!static::isIndexExists() || !static::CheckModule())
		{
			return false;
		}

		global $DB;

		$whereIn = '';
		$having = '';

		$words = array_keys(stemming($query, LANGUAGE_ID));

		if (count($words))
		{
			$whereInAll = array();

			foreach ($words as $word)
			{
				$whereInAll[] = "'".$DB->ForSql($word)."'";
			}

			$whereIn = 'TS.SEARCH_WORD IN ('.join(', ', $whereInAll).')';

			if (count($words) > 1)
			{
				$having = 'SUM(CASE WHEN '.$whereIn.' THEN 1 ELSE 0 END) = '.count($words);
			}
		}

		return array('WHERE' => $whereIn, 'HAVING' => $having);
	}

	/**
	 * @deprecated
	 */
	public static function GetFilterQuery($q, $idName, $titleName, $messageName, &$error)
	{
		if(!self::CheckModule()) return "";
		$res = self::ParseQ(HTMLToTxt($q));
		$res = self::PrepareQuery($res, $idName, $titleName, $messageName, $error);
		return $res;
	}

	/**
	 * @deprecated
	 */
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

	/**
	 * @deprecated
	 */
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

	/**
	 * @deprecated
	 */
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
		if(self::$searchModule === null)
		{
			self::$searchModule = CModule::IncludeModule("search");
		}

		return self::$searchModule;
	}

	static function isIndexExists()
	{
		return (COption::GetOptionString('support', 'SEARCH_VERSION', '0') === '12.0.3');
	}

	/**
	 * @deprecated
	 */
	static function GetSQLfilter($s, $idName, $titleName, $messageName)
	{
		global $DB;
		$res = "";
		$and = "";
		$arrQ = explode("^", $s);
		foreach($arrQ as $k => $v)
		{
			if(substr_count($v, "%") > 0)
			{
				$res .= self::StrInEXISTS($and, $idName, "LIKE", $v);
			}
			else
			{
				$resArr = stemming($v, LANGUAGE_ID);
				if(count($resArr) > 0)
				{
					foreach($resArr as $k2 => $v2)
					{
						$res .= self::StrInEXISTS($and, $idName, "=", $k2);
						$and = " AND";
					}
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

	/**
	 * @deprecated
	 */
	static function StrInEXISTS($and, $idName, $sign, $key)
	{
		global $DB;
		$ticketSearch = self::TABLE_NAME;
		return "\n" . $and . " EXISTS(SELECT 1 FROM $ticketSearch WHERE MESSAGE_ID = $idName AND SEARCH_WORD $sign '" . $DB->ForSql($key) . "')";
	}

	/**
	 * @param integer|array $ticket ticket ID or ticket fetched row with ID, SITE_ID, TITLE
	 * @param null|array $messages Message rows with MESSAGE; if null - will be fetched automatically
	 *
	 * @return boolean
	 */
	public static function indexTicket($ticket, $messages = null)
	{
		if(!self::CheckModule())
		{
			return false;
		}

		global $DB;

		// select ticket row
		if (!is_array($ticket))
		{
			$result = $DB->Query("SELECT ID, SITE_ID, TITLE FROM b_ticket WHERE ID = ".intval($ticket));
			$ticket = $result->Fetch();

			if (!$ticket)
			{
				return false;
			}
		}

		// select message rows
		if ($messages === null)
		{
			$messages = array();
			$result = $DB->Query("SELECT MESSAGE FROM b_ticket_message WHERE IS_LOG='N' AND IS_HIDDEN='N' AND TICKET_ID = ".intval($ticket['ID']));
			while ($row = $result->Fetch())
			{
				$messages[] = $row;
			}
		}

		// select language for stemming
		if ($ticket['SITE_ID'] === SITE_ID) // чему он равен в админке?
		{
			$langId = LANGUAGE_ID;
		}
		else
		{
			$result = CSite::GetByID($ticket['SITE_ID']);
			$site = $result->Fetch();

			if (!$site)
			{
				return false;
			}

			$langId = $site['LANGUAGE_ID'];
		}

		// set index text
		$indexText = $ticket['TITLE'];

		foreach ($messages as $message)
		{
			$indexText .= ' '.$message['MESSAGE'].' ';
		}

		$index = stemming(HTMLToTxt($indexText), $langId);

		// insert index into db
		// better to make DB->multiInsert
		foreach (array_keys($index) as $phrase)
		{
			$insertQuery = "INSERT INTO " . self::TABLE_NAME . "(TICKET_ID, SEARCH_WORD) VALUES ".
				"(".intval($ticket['ID']).", '" . $DB->ForSql($phrase) . "')";

			$DB->Query($insertQuery, false, (self::err_mess()) . "<br>Method: indexTicket<br>Line: " . __LINE__);
		}
	}

public static 	public static function reindexTicket($ticket, $messages = null)
	{
		if(!self::CheckModule())
		{
			return false;
		}

		global $DB;

		if (is_array($ticket))
		{
			$ticketId = intval($ticket['ID']);
		}
		else
		{
			$ticketId = intval($ticket);
		}

		$err_mess = (self::err_mess()) . "<br>Function: reindexTicket<br>Line: ";
		$DB->Query("DELETE FROM ".self::TABLE_NAME." WHERE TICKET_ID = ".$ticketId, false, $err_mess . __LINE__);

		return static::indexTicket($ticket, $messages);
	}

public static 	public static function indexAllTickets($startFromId = 0, $timeLimit = 10)
	{
		return static::performAllTicketsIndexing($startFromId, $timeLimit, false);
	}

public static 	public static function reindexAllTickets($startFromId = 0, $timeLimit = 10)
	{
		return static::performAllTicketsIndexing($startFromId, $timeLimit, true);
	}

public static 	protected static function performAllTicketsIndexing($startFromId = 0, $timeLimit = 10, $removeOldIndex = false)
	{
		if (!static::CheckModule())
		{
			return false;
		}

		$endTime = time() + $timeLimit;

		global $DB;

		$lastId = intval($startFromId);

		while (time() < $endTime)
		{
			$tickets = array();
			$messages = array();

			$result = $DB->Query($DB->TopSql("
				SELECT
					T.ID, T.SITE_ID, T.TITLE, TM.MESSAGE
				FROM
					b_ticket T,
					b_ticket_message TM
				WHERE
					TM.TICKET_ID = T.ID AND T.ID > " . $lastId . " AND TM.IS_LOG='N' AND IS_HIDDEN='N'
				ORDER BY
					T.ID ASC"
			, 100));

			while ($row = $result->Fetch())
			{
				$tickets[$row['ID']] = $row;
				$messages[$row['ID']][] = array('MESSAGE' => $row['MESSAGE']);
				$endTicketId = $row['ID'];
			}

			// empty result
			if (empty($tickets))
			{
				// set option allows to use new index
				COption::SetOptionString('support', 'SEARCH_VERSION', '12.0.3');

				// delete updater notification
				CAdminNotify::DeleteByTag('SUPORT_SEARCH_CONVERT_12_0_3');

				return -1;
			}

			// reselect last ticket's messages to complete them because of previous limit in query
			unset($messages[$endTicketId]);
			$result = $DB->Query("SELECT MESSAGE FROM b_ticket_message WHERE TICKET_ID = ".$endTicketId." AND IS_LOG='N' AND IS_HIDDEN='N'");
			while ($row = $result->Fetch())
			{
				$messages[$endTicketId][] = $row;
			}

			// remove old index
			if ($removeOldIndex)
			{
				$ticketIds 	  = array_keys($tickets);
				$removeFromId = min($ticketIds);
				$removeToId   = max($ticketIds);

				$DB->Query("DELETE FROM ".static::TABLE_NAME." WHERE TICKET_ID >= ".$removeFromId." AND TICKET_ID <= ".$removeToId);
			}

			// add new index
			foreach ($tickets as $ticket)
			{
				static::indexTicket($ticket, $messages[$ticket['ID']]);
				$lastId = $ticket['ID'];
			}
		}

		return $lastId;
	}

	/**
	 * @deprecated
	 */
public static 	static function WriteWordsInTable($M_ID, $SITE_ID, $s)
	{
		global $DB;
		if(!self::CheckModule()) return;
		$err_mess = (self::err_mess()) . "<br>Function: writeWordsInTable<br>Line: ";
		$M_ID = intval($M_ID);
		$ticketSearch = self::TABLE_NAME;
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

	/**
	 * @deprecated
	 */
public static 	static function ReindexMessages($firstID, $periodS = 8)
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