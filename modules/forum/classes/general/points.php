<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2007 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
IncludeModuleLangFile(__FILE__);
/**********************************************************************/
/************** POINTS ************************************************/
/**********************************************************************/
class CAllForumPoints
{
	//---------------> Points insert, update, delete
	public static function CanUserAddPoints($arUserGroups)
	{
		if (in_array(1, $arUserGroups)) return True;
		return False;
	}

	public static function CanUserUpdatePoints($ID, $arUserGroups)
	{
		if (in_array(1, $arUserGroups)) return True;
		return False;
	}

	public static function CanUserDeletePoints($ID, $arUserGroups)
	{
		if (in_array(1, $arUserGroups)) return True;
		return False;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		$aMsg = array();

		if (is_set($arFields, "LANG") || $ACTION=="ADD")
		{
			if (!is_array($arFields["LANG"]))
				$arFields["LANG"] = array();

			$db_lang = CLangAdmin::GetList(($b="sort"), ($o="asc"));
			while ($arLang = $db_lang->Fetch())
			{
				$bFound = False;
				foreach ($arFields["LANG"] as $key => $res)
				{
					if (is_array($res) && $res["LID"] == $arLang["LID"])
					{
						$arFields["LANG"][$key]["NAME"] = trim($res["NAME"]);
						if (strLen($arFields["LANG"][$key]["NAME"]) > 0)
						{
							$bFound = True;
							break;
						}
					}
				}
				if (!$bFound)
				{
					$aMsg[] = array(
						"id"=>'POINTS[NAME][LID]['.$arLang["LID"].']',
						"text" => str_replace("#LANG#", $arLang["NAME"]." [".$arLang["LID"]."]", GetMessage("FORUM_PE_ERROR_NONAME")));
				}
			}
		}

		if (is_set($arFields, "MIN_POINTS") || $ACTION=="ADD")
		{
			$arFields["MIN_POINTS"] = trim($arFields["MIN_POINTS"]);
			if (strLen($arFields["MIN_POINTS"]) <= 0)
			{
				$aMsg[] = array(
					"id"=>'POINTS[MIN_POINTS]',
					"text" => GetMessage("FORUM_PE_ERROR_MIN_POINTS_EMPTY"));
			}
			elseif (preg_match("/[^0-9]/", $arFields["MIN_POINTS"]))
			{
				$aMsg[] = array(
					"id"=>'POINTS[MIN_POINTS]',
					"text" => GetMessage("FORUM_PE_ERROR_MIN_POINTS_BAD"));
			}
			else
			{
				$arFields["MIN_POINTS"] = intVal($arFields["MIN_POINTS"]);
				$db_res = CForumPoints::GetList(array(), array("MIN_POINTS" => $arFields["MIN_POINTS"]));
				if ($db_res && $res = $db_res->GetNext())
				{
					if ($ACTION=="ADD" || $ID == 0 || $ID != $res["ID"])
					{
						$aMsg[] = array(
							"id"=>'POINTS[MIN_POINTS]',
							"text" => GetMessage("FORUM_PE_ERROR_MIN_POINTS_EXIST"));
					}
				}
			}
		}
		$arFields["VOTES"] = intval($arFields["VOTES"]);

		if(!empty($aMsg))
		{
			$e = new CAdminException(array_reverse($aMsg));
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;
	}

	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = intVal($ID);
		if ($ID <= 0)
			return False;

		if (!CForumPoints::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_forum_points", $arFields);
		$strSql = "UPDATE b_forum_points SET ".$strUpdate." WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if (is_set($arFields, "LANG"))
		{
			$DB->Query("DELETE FROM b_forum_points_lang WHERE POINTS_ID = ".$ID."");

			for ($i = 0; $i<count($arFields["LANG"]); $i++)
			{
				$arInsert = $DB->PrepareInsert("b_forum_points_lang", $arFields["LANG"][$i]);
				$strSql = "INSERT INTO b_forum_points_lang(POINTS_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		return $ID;
	}

	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$DB->Query("DELETE FROM b_forum_points_lang WHERE POINTS_ID = ".$ID, True);
		$DB->Query("DELETE FROM b_forum_points WHERE ID = ".$ID, True);

		return true;
	}

	public static function GetList($arOrder = array("MIN_POINTS"=>"ASC"), $arFilter = array())
	{
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$arSqlOrder = Array();
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "ID":
				case "MIN_POINTS":
					if (IntVal($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FR.".$key." IS NULL OR FR.".$key."<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FR.".$key." IS NULL OR NOT ":"")."(FR.".$key." ".$strOperation." ".IntVal($val)." )";
					break;
				case "CODE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FR.CODE IS NULL)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FR.CODE IS NULL OR NOT ":"")."(FR.CODE ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = "WHERE (".implode(") AND (", $arSqlSearch).")";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "ID") $arSqlOrder[] = " FR.ID ".$order." ";
			elseif ($by == "CODE") $arSqlOrder[] = " FR.CODE ".$order." ";
			elseif ($by == "VOTES") $arSqlOrder[] = " FR.VOTES ".$order." ";
			else
			{
				$arSqlOrder[] = " FR.MIN_POINTS ".$order." ";
				$by = "MIN_POINTS";
			}
		}
		DelDuplicateSort($arSqlOrder);
		if (!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql =
			"SELECT FR.ID, FR.MIN_POINTS, FR.CODE, FR.VOTES ".
			"FROM b_forum_points FR ".
			$strSqlSearch.
			$strSqlOrder;

		//echo htmlspecialcharsbx($strSql);
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function GetListEx($arOrder = array("MIN_POINTS"=>"ASC"), $arFilter = array())
	{
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$arSqlOrder = Array();
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "ID":
				case "MIN_POINTS":
					if (IntVal($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FR.".$key." IS NULL OR FR.".$key."<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FR.".$key." IS NULL OR NOT ":"")."(FR.".$key." ".$strOperation." ".IntVal($val)." )";
					break;
				case "CODE":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FR.CODE IS NULL)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FR.CODE IS NULL OR NOT ":"")."(FR.CODE ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "LID":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FRL.LID IS NULL OR ".($DB->type == "MSSQL" ? "LEN" : "LENGTH")."(FRL.LID)<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FRL.LID IS NULL OR NOT ":"")."(FRL.LID ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = " WHERE (".implode(") AND (", $arSqlSearch).") ";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "ID") $arSqlOrder[] = " FR.ID ".$order." ";
			elseif ($by == "LID") $arSqlOrder[] = " FRL.LID ".$order." ";
			elseif ($by == "NAME") $arSqlOrder[] = " FRL.NAME ".$order." ";
			elseif ($by == "CODE") $arSqlOrder[] = " FR.CODE ".$order." ";
			elseif ($by == "VOTES") $arSqlOrder[] = " FR.VOTES ".$order." ";
			else
			{
				$arSqlOrder[] = " FR.MIN_POINTS ".$order." ";
				$by = "MIN_POINTS";
			}
		}
		DelDuplicateSort($arSqlOrder);
		if (!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql =
			"SELECT FR.ID, FR.MIN_POINTS, FR.CODE, FR.VOTES, FRL.LID, FRL.NAME ".
			"FROM b_forum_points FR ".
			"	LEFT JOIN b_forum_points_lang FRL ON FR.ID = FRL.POINTS_ID ".
			$strSqlSearch." ".
			$strSqlOrder;

		//echo htmlspecialcharsbx($strSql);
		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	
	/**
	* <p>Возвращает массив параметров звания (рейтинга) форума по его коду </p>
	*
	*
	* @param int $ID  Код звания.
	*
	* @return array <p>Возвращает ассоциативный массив с ключами</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код
	* звания.</td> </tr> <tr> <td>MIN_POINTS</td> <td>Количество баллов, необходимое для
	* получения этого звания.</td> </tr> <tr> <td>CODE</td> <td>Символьный код.</td> </tr>
	* <tr> <td>VOTES</td> <td>Количество голосов, которое имеет пользователь с
	* этим званием.</td> </tr> </table> <p>  </p<a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* 
	* $ar_res = CForumPoints::GetByID(3);
	* echo "Пользователь с этим званием может отдать ".$ar_res["VOTES"]." голос(ов)";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT FR.ID, FR.MIN_POINTS, FR.CODE, FR.VOTES ".
			"FROM b_forum_points FR ".
			"WHERE FR.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function GetByIDEx($ID, $strLang)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT FR.ID, FR.MIN_POINTS, FR.CODE, FR.VOTES, FRL.LID, FRL.NAME ".
			"FROM b_forum_points FR ".
			"	LEFT JOIN b_forum_points_lang FRL ON (FR.ID = FRL.POINTS_ID AND FRL.LID = '".$DB->ForSql($strLang)."') ".
			"WHERE FR.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}

	public static function GetLangByID($POINTS_ID, $strLang)
	{
		global $DB;

		$POINTS_ID = IntVal($POINTS_ID);
		$strSql =
			"SELECT FRL.POINTS_ID, FRL.LID, FRL.NAME ".
			"FROM b_forum_points_lang FRL ".
			"WHERE FRL.POINTS_ID = ".$POINTS_ID." ".
			"	AND FRL.LID = '".$DB->ForSql($strLang)."' ";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}
}


/**********************************************************************/
/************** POINTS2POST *******************************************/
/**********************************************************************/
class CAllForumPoints2Post
{
	//---------------> Insert, update, delete
	public static function CanUserAddPoints2Post($arUserGroups)
	{
		if (in_array(1, $arUserGroups)) return True;
		return False;
	}

	public static function CanUserUpdatePoints2Post($ID, $arUserGroups)
	{
		if (in_array(1, $arUserGroups)) return True;
		return False;
	}

	public static function CanUserDeletePoints2Post($ID, $arUserGroups)
	{
		if (in_array(1, $arUserGroups)) return True;
		return False;
	}

	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		$aMsg = array();
		if (is_set($arFields, "MIN_NUM_POSTS") || $ACTION=="ADD")
		{
			$arFields["MIN_NUM_POSTS"] = trim($arFields["MIN_NUM_POSTS"]);
			if (empty($arFields["MIN_NUM_POSTS"]))
			{
				$aMsg[] = array(
					"id"=>'POINTS2POST[MIN_NUM_POSTS]',
					"text" => GetMessage("FORUM_PE_ERROR_MIN_NUM_POSTS_EMPTY"));
			}
			elseif (strlen($arFields["MIN_NUM_POSTS"]) > 18 || preg_match("/[^0-9]/", $arFields["MIN_NUM_POSTS"]))
			{
				$aMsg[] = array(
					"id"=>'POINTS2POST[MIN_NUM_POSTS]',
					"text" => GetMessage("FORUM_PE_ERROR_MIN_NUM_POSTS_BAD"));
			}
			else
			{
				$arFields["MIN_NUM_POSTS"] = intVal($arFields["MIN_NUM_POSTS"]);
				$db_res = CForumPoints2Post::GetList(array(), array("MIN_NUM_POSTS" => $arFields["MIN_NUM_POSTS"]));
				if ($db_res && $res = $db_res->GetNext())
				{
					if ($ACTION=="ADD" || $ID == 0 || $ID != $res["ID"])
					{
						$aMsg[] = array(
							"id"=>'POINTS2POST[MIN_NUM_POSTS]',
							"text" => GetMessage("FORUM_PE_ERROR_MIN_NUM_POSTS_EXIST"));
					}
				}
			}
		}
		if ((is_set($arFields, "POINTS_PER_POST") || $ACTION=="ADD") && DoubleVal($arFields["POINTS_PER_POST"])<=0)
			$arFields["POINTS_PER_POST"] = 0;
		else {
			$arFields["POINTS_PER_POST"] = round(doubleval($arFields["POINTS_PER_POST"]), 4);
			if (strlen(round($arFields["POINTS_PER_POST"], 0)) > 14 || strlen(strstr($arFields["POINTS_PER_POST"], ".")) > 5 ||
				preg_match("/[^0-9.]/", $arFields["POINTS_PER_POST"]))
				$aMsg[] = array(
					"id" => 'POINTS2POST[POINTS_PER_POST]',
					"text" => GetMessage("FORUM_PE_ERROR_MIN_POINTS_BAD"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException(array_reverse($aMsg));
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		return true;

		return True;
	}

	// User points is not recount.
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = IntVal($ID);
		if ($ID<=0) return False;

		if (!CForumPoints2Post::CheckFields("UPDATE", $arFields, $ID))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_forum_points2post", $arFields);
		$strSql = "UPDATE b_forum_points2post SET ".$strUpdate." WHERE ID = ".$ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $ID;
	}

	// User points is not recount.
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$DB->Query("DELETE FROM b_forum_points2post WHERE ID = ".$ID, True);

		return true;
	}

	public static function GetList($arOrder = array("MIN_NUM_POSTS"=>"ASC"), $arFilter = array())
	{
		global $DB;

		$arSqlSearch = array();
		$arSqlOrder = Array();
		$strSqlSearch = "";
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "ID":
				case "MIN_NUM_POSTS":
					if (IntVal($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FR.".$key." IS NULL OR FR.".$key."<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FR.".$key." IS NULL OR NOT ":"")."(FR.".$key." ".$strOperation." ".IntVal($val)." )";
					break;
			}
		}
		if (count($arSqlSearch) > 0)
			$strSqlSearch = "WHERE (".implode(") AND (", $arSqlSearch).") ";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if ($by == "ID") $arSqlOrder[] = " FR.ID ".$order." ";
			elseif ($by == "MIN_NUM_POSTS") $arSqlOrder[] = " FR.MIN_NUM_POSTS ".$order." ";
			else
			{
				$arSqlOrder[] = " FR.POINTS_PER_POST ".$order." ";
				$by = "POINTS_PER_POST";
			}
		}

		DelDuplicateSort($arSqlOrder);
		if (count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql =
			"SELECT FR.ID, FR.MIN_NUM_POSTS, FR.POINTS_PER_POST
			FROM b_forum_points2post FR
			".$strSqlSearch."
			".$strSqlOrder;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	
	/**
	* <p>Возвращает массив параметров записи в таблицу соответствий между количеством сообщений пользователя на форуме и количеством балов за одно сообщение по ее коду.</p>
	*
	*
	* @param int $ID  Код записи.
	*
	* @return array <p>Возвращает ассоциативный массив с ключами.</p> <table class="tnormal"
	* width="100%"><tbody> <tr> <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код
	* записи.</td> </tr> <tr> <td>MIN_NUM_POSTS</td> <td>Количество сообщений, которое
	* пользователь должен написать на форуме для получения этого
	* количества балов за одно сообщение.</td> </tr> <tr> <td>POINTS_PER_POST</td>
	* <td>Количество балов за одно сообщение. Может иметь как целое
	* значение, так и дробное: <br> 2 - два бала за каждое сообщение <br> 0.1 -
	* одна десятая бала за каждое сообщение.</td> </tr> </tbody></table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints2post/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		$ID = IntVal($ID);
		$strSql =
			"SELECT FR.ID, FR.MIN_NUM_POSTS, FR.POINTS_PER_POST ".
			"FROM b_forum_points2post FR ".
			"WHERE FR.ID = ".$ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return $res;
		}
		return False;
	}
}


/**********************************************************************/
/************** FORUM USER POINTS *************************************/
/**********************************************************************/
class CAllForumUserPoints
{
	//---------------> Insert, update, delete
	public static function CanUserAddUserPoints($iUserID)
	{
		if (CForumUser::IsLocked($iUserID)) return False;
		return True;
	}

	public static function CanUserUpdateUserPoints($iUserID)
	{
		if (CForumUser::IsLocked($iUserID)) return False;
		return True;
	}

	public static function CanUserDeleteUserPoints($iUserID)
	{
		if (CForumUser::IsLocked($iUserID)) return False;
		return True;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		if ((is_set($arFields, "FROM_USER_ID") || $ACTION=="ADD") && IntVal($arFields["FROM_USER_ID"])<=0) return false;
		if ((is_set($arFields, "TO_USER_ID") || $ACTION=="ADD") && IntVal($arFields["TO_USER_ID"])<=0) return false;
		if ((is_set($arFields, "POINTS") || $ACTION=="ADD") && IntVal($arFields["POINTS"])<=0) return false;

		return True;
	}

	public static function Update($FROM_USER_ID, $TO_USER_ID, $arFields)
	{
		global $DB;

		$FROM_USER_ID = IntVal($FROM_USER_ID);
		if ($FROM_USER_ID<=0) return False;

		$TO_USER_ID = IntVal($TO_USER_ID);
		if ($TO_USER_ID<=0) return False;

		if (!CForumUserPoints::CheckFields("UPDATE", $arFields))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_forum_user_points", $arFields);

		$strDatePostValue = "";
		if (!is_set($arFields, "DATE_UPDATE"))
		{
			$strDatePostValue .= ", DATE_UPDATE = ".$DB->GetNowFunction()." ";
		}

		$strSql = "UPDATE b_forum_user_points SET ".$strUpdate.$strDatePostValue." WHERE FROM_USER_ID = ".$FROM_USER_ID." AND TO_USER_ID = ".$TO_USER_ID;
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		// Recount user points.
		$arUserFields = array();
		$arUserFields["POINTS"] = CForumUser::CountUserPoints($TO_USER_ID);

		$arUser = CForumUser::GetByUSER_ID($TO_USER_ID);
		if ($arUser)
		{
			CForumUser::Update($arUser["ID"], $arUserFields);
		}
		else
		{
			$arUserFields["USER_ID"] = $TO_USER_ID;
			$ID_tmp = CForumUser::Add($arUserFields);
		}

		return true;
	}

	public static function Delete($FROM_USER_ID, $TO_USER_ID)
	{
		global $DB;

		$FROM_USER_ID = IntVal($FROM_USER_ID);
		if ($FROM_USER_ID<=0) return False;

		$TO_USER_ID = IntVal($TO_USER_ID);
		if ($TO_USER_ID<=0) return False;

		$DB->Query("DELETE FROM b_forum_user_points WHERE FROM_USER_ID = ".$FROM_USER_ID." AND TO_USER_ID = ".$TO_USER_ID);

		// Recount user points.
		$arUserFields = array();
		$arUserFields["POINTS"] = CForumUser::CountUserPoints($TO_USER_ID);

		$arUser = CForumUser::GetByUSER_ID($TO_USER_ID);
		if ($arUser)
		{
			CForumUser::Update($arUser["ID"], $arUserFields);
		}
		else
		{
			$arUserFields["USER_ID"] = $TO_USER_ID;
			$ID_tmp = CForumUser::Add($arUserFields);
		}

		return true;
	}

	public static function GetList($arOrder = array("TO_USER_ID"=>"ASC"), $arFilter = array())
	{
		global $DB;
		$arSqlSearch = Array();
		$strSqlSearch = "";
		$arSqlOrder = Array();
		$strSqlOrder = "";
		$arFilter = (is_array($arFilter) ? $arFilter : array());

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "FROM_USER_ID":
				case "TO_USER_ID":
					if (IntVal($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FR.".$key." IS NULL OR FR.".$key."<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FR.".$key." IS NULL OR NOT ":"")."(FR.".$key." ".$strOperation." ".IntVal($val)." )";
					break;
			}
		}
		if (!empty($arSqlSearch))
			$strSqlSearch = " WHERE (".implode(") AND (", $arSqlSearch).") ";

		foreach ($arOrder as $by=>$order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";

			if ($by == "FROM_USER_ID") $arSqlOrder[] = " FR.FROM_USER_ID ".$order." ";
			elseif ($by == "POINTS") $arSqlOrder[] = " FR.POINTS ".$order." ";
			elseif ($by == "DATE_UPDATE") $arSqlOrder[] = " FR.DATE_UPDATE ".$order." ";
			else
			{
				$arSqlOrder[] = " FR.TO_USER_ID ".$order." ";
				$by = "TO_USER_ID";
			}
		}
		DelDuplicateSort($arSqlOrder);
		if (!empty($arSqlOrder))
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		$strSql =
			"SELECT FR.FROM_USER_ID, FR.TO_USER_ID, FR.POINTS, FR.DATE_UPDATE ".
			"FROM b_forum_user_points FR ".
			$strSqlSearch." ".
			$strSqlOrder;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return $db_res;
	}

	public static function GetByID($FROM_USER_ID, $TO_USER_ID)
	{
		global $DB;

		$FROM_USER_ID = IntVal($FROM_USER_ID);
		if ($FROM_USER_ID<=0)
			return False;

		$TO_USER_ID = IntVal($TO_USER_ID);
		if ($TO_USER_ID<=0)
			return False;

		$strSql =
			"SELECT FR.FROM_USER_ID, FR.TO_USER_ID, FR.POINTS, FR.DATE_UPDATE
			FROM b_forum_user_points FR
			WHERE FR.FROM_USER_ID = ".$FROM_USER_ID."
				AND FR.TO_USER_ID = ".$TO_USER_ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($res = $db_res->Fetch())
			return $res;
		return False;
	}

	public static function CountSumPoints($TO_USER_ID)
	{
		global $DB;

		$TO_USER_ID = IntVal($TO_USER_ID);
		if ($TO_USER_ID<=0) return 0;

		$strSql =
			"SELECT SUM(FR.POINTS) as SM ".
			"FROM b_forum_user_points FR ".
			"WHERE FR.TO_USER_ID = ".$TO_USER_ID."";
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		if ($res = $db_res->Fetch())
		{
			return IntVal($res["SM"]);
		}
		return 0;
	}
}
?>
