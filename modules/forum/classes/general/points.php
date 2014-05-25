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

/**
 * <b>CForumPoints</b> - класс для работы со званиями (статусами пользователей) форума.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/index.php
 * @author Bitrix
 */
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

	
	/**
	* <p>Изменяет параметры звания с кодом ID на значения, указанные в массиве arFields.</p>
	*
	*
	*
	*
	* @param int $ID  Код звания.
	*
	*
	*
	* @param array $arFields  Массив новых значений параметров звания.
	*
	*
	*
	* @return int <p>Функция возвращает код изменяемого звания или false в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/update.php
	* @author Bitrix
	*/
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

	
	/**
	* <p>Удаляет звание (рейтинг) с кодом ID из системы званий форума. </p>
	*
	*
	*
	*
	* @param int $ID  Код звания для удаления.
	*
	*
	*
	* @return bool <p>Возвращает True.</p><h4> </h4
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$DB->Query("DELETE FROM b_forum_points_lang WHERE POINTS_ID = ".$ID, True);
		$DB->Query("DELETE FROM b_forum_points WHERE ID = ".$ID, True);

		return true;
	}

	
	/**
	* <p>Возвращает звания форума, которые удовлетворяют фильтру arFilter, упорядоченные в соответствии с порядком arOrder </p>
	*
	*
	*
	*
	* @param array $arrayarOrder = array("MIN_POINTS"=>"ASC") <p>Порядок сортировки записей; представляет собой ассоциативный
	* массив, в котором ключами являются названия параметров звания, а
	* значениями - направления сортировки.</p> <p>Допустимые параметры
	* звания для сортировки:<br><b>ID</b> - код звания<br><b>CODE</b> - мнемонический
	* код<br><b> VOTES</b> - количество голосов при голосовании<br><b>MIN_POINTS</b> -
	* количество баллов, которые нужны для достижения этого звания</p>
	*
	*
	*
	* @param array $arrayarFilter = array() <p>Фильтр на возвращаемые звания; представляет собой
	* ассоциативный массив, в котором ключами являются названия
	* параметров звания, а значениями - условия на эти параметры.</p>
	* <p>Допустимые параметры звания для фильтрации:<br><b>ID</b> - код
	* звания<br><b>CODE</b> - мнемонический код<br><b>MIN_POINTS</b> - количество
	* баллов, которые нужны для достижения этого звания.</p>
	*
	*
	*
	* @return CDBResult <p>Возвращяется объект класса CDBResult, каждая запись которого
	* представляет собой массив с ключами</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код звания.</td> </tr> <tr>
	* <td>MIN_POINTS</td> <td>Количество баллов, необходимое для получения этого
	* звания.</td> </tr> <tr> <td>CODE</td> <td>Мнемонический код.</td> </tr> <tr> <td>VOTES</td>
	* <td>Количество голосов, которое имеет пользователь с этим
	* званием.</td> </tr> </table> <p> </p<a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* // Сортировка по количеству голосов по убыванию
	* // при равном количестве голосов сортировка по ID по возрастанию
	* $arOrder = array("VOTES"=&gt;"DESC", "ID"=&gt;"ASC");
	* // Фильтр указывает, что нужно выбирать только те звания
	* // для получения которых нужно как минимум 50 баллов
	* $arFilter = array("&gt;=MIN_POINTS"=&gt;50);
	* $db_res = CForumPoints::GetList($arOrder, $arFilter);
	* while ($ar_res = $db_res-&gt;Fetch())
	* {
	*    echo $ar_res["ID"]."-".$ar_res["VOTES"]."&lt;br&gt;";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/getlist.php
	* @author Bitrix
	*/
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

	
	/**
	* <p>Возвращает звания форума, которые удовлетворяют фильтру arFilter, упорядоченные в соответствии с порядком arOrder</p>
	*
	*
	*
	*
	* @param array $arrayarOrder = array("MIN_POINTS"=>"ASC") <p>Порядок сортировки записей; представляет собой ассоциативный
	* массив, в котором ключами являются названия параметров звания, а
	* значениями - направления сортировки.</p> <p>Допустимые параметры
	* звания для сортировки:<br><b>ID</b> - код звания<br><b>CODE</b> - мнемонический
	* код<br><b>VOTES</b> - количество голосов при голосовании<br><b>MIN_POINTS</b> -
	* количество баллов, которые нужны для достижения этого звания</p>
	*
	*
	*
	* @param array $arrayarFilter = array() <p>Фильтр на возвращаемые звания; представляет собой
	* ассоциативный массив, в котором ключами являются названия
	* параметров звания, а значениями - условия на эти параметры.</p>
	* <p>Допустимые параметры звания для фильтрации:<br><b>LID</b> - код языка
	* (должен присутствовать в фильтре) <br><b>ID</b> - код звания<br><b>CODE</b> -
	* мнемонический код<br><b>MIN_POINTS</b> - количество баллов, которые нужны
	* для достижения этого звания.</p>
	*
	*
	*
	* @return CDBResult <p>Возвращается объект класса CDBResult, каждая запись которого
	* представляет собой массив с ключами</p> <table class="tnormal" width="100%"> <tr> <th
	* width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код звания.</td> </tr> <tr>
	* <td>MIN_POINTS</td> <td>Количество баллов, необходимое для получения этого
	* звания.</td> </tr> <tr> <td>CODE</td> <td>Мнемонический код.</td> </tr> <tr> <td>VOTES</td>
	* <td>Количество голосов, которое имеет пользователь с этим
	* званием.</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr> <td>NAME</td> <td>Название
	* звания на языке LID </td> </tr> </table> <a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* // Сортировка по количеству голосов по убыванию
	* // при равном количестве голосов сортировка по ID по возрастанию
	* $arOrder = array("VOTES"=&gt;"DESC", "ID"=&gt;"ASC");
	* 
	* // Фильтр указывает, что нужно выбирать только те звания
	* // для получения которых нужно как минимум 50 баллов
	* $arFilter = array("LID"=&gt;"ru", "&gt;=MIN_POINTS"=&gt;50);
	* $db_res = CForumPoints::GetList($arOrder, $arFilter);
	* while ($ar_res = $db_res-&gt;Fetch())
	* {
	*     echo $ar_res["NAME"]."-".$ar_res["VOTES"]."&lt;br&gt;";
	* }
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/getlistex.php
	* @author Bitrix
	*/
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
	*
	*
	* @param int $ID  Код звания.
	*
	*
	*
	* @return array <p>Возвращает ассоциативный массив с ключами</p> <table class="tnormal"
	* width="100%"> <tr> <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код
	* звания.</td> </tr> <tr> <td>MIN_POINTS</td> <td>Количество баллов, необходимое для
	* получения этого звания.</td> </tr> <tr> <td>CODE</td> <td>Мнемонический код.</td>
	* </tr> <tr> <td>VOTES</td> <td>Количество голосов, которое имеет пользователь с
	* этим званием.</td> </tr> </table> <p>  </p<a name="examples"></a>
	*
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

	
	/**
	* <p>Возвращает массив параметров звания (рейтинга) форума по его коду включая языкозависимые параметры.</p>
	*
	*
	*
	*
	* @param int $ID  Код звания.
	*
	*
	*
	* @param string $strLang  Код языка.
	*
	*
	*
	* @return array <p>Возвращает ассоциативный массив с ключами</p> <table class="tnormal"
	* width="100%"><tbody> <tr> <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код
	* звания.</td> </tr> <tr> <td>MIN_POINTS</td> <td>Количество баллов, необходимое для
	* получения этого звания.</td> </tr> <tr> <td>CODE</td> <td>Мнемонический код.</td>
	* </tr> <tr> <td>VOTES</td> <td>Количество голосов, которое имеет пользователь с
	* этим званием.</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr> <td>NAME</td>
	* <td>Название звания на языке LID.</td> </tr> </tbody></table> <p></p><a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $ar_res = CForumPoints::GetByIDEx(3, "ru");
	* echo "Пользователи со званием ".$ar_res["NAME"]." могут отдать ".$ar_res["VOTES"]." голосов";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/getbyidex.php
	* @author Bitrix
	*/
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

	
	/**
	* <p>Возвращает массив языкозависимых параметров звания (рейтинга) форума по его коду.</p>
	*
	*
	*
	*
	* @param int $POINTS_ID  Код звания.
	*
	*
	*
	* @param string $strLang  Код языка.
	*
	*
	*
	* @return array <p>Возвращает ассоциативный массив с ключами.</p> <table class="tnormal"
	* width="100%"><tbody> <tr> <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>POINTS_ID</td>
	* <td>Код звания.</td> </tr> <tr> <td>LID</td> <td>Код языка.</td> </tr> <tr> <td>NAME</td>
	* <td>Название звания на языке LID. </td> </tr> </tbody></table> <p></p><a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $ar_res = CForumPoints::GetLangByID(3, "ru");
	* echo "Звание с кодом ".$ar_res["POINTS_ID"]." на русском языке называется ".$ar_res["NAME"]."";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints/getlangbyid.php
	* @author Bitrix
	*/
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
	
	/**
	* <p>Изменяет параметры записи с кодом ID в таблице соответствий между количеством сообщений пользователя на форуме и количеством баллов за одно сообщение на значения, указанные в массиве arFields.</p>
	*
	*
	*
	*
	* @param int $ID  Код изменяемой записи. </htm
	*
	*
	*
	* @param array $arFields  Массив новых значений параметров записи.
	*
	*
	*
	* @return int <p>Функция возвращает код записи или false в случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints2post/update.php
	* @author Bitrix
	*/
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
	
	/**
	* <p>Удаляет запись с кодом ID из таблицы соответствий между количеством сообщений пользователя на форуме и количеством баллов за одно сообщение.</p>
	*
	*
	*
	*
	* @param int $ID  Код записи.
	*
	*
	*
	* @return bool <p>Функция возвращает значение True.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints2post/delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;
		$ID = IntVal($ID);

		$DB->Query("DELETE FROM b_forum_points2post WHERE ID = ".$ID, True);

		return true;
	}

	
	/**
	* <p>Возвращает записи в таблице соответствий между количеством сообщений пользователя на форуме и количеством балов за одно сообщение, которые удовлетворяют фильтру arFilter, упорядоченные в соответствии с порядком arOrder.</p>
	*
	*
	*
	*
	* @param array $arrayarOrder = array("MIN_NUM_POSTS"=>"ASC") <p>Порядок сортировки записей; представляет собой ассоциативный
	* массив, в котором ключами являются названия параметров записи, а
	* значениями - направления сортировки.</p> <p>Допустимые параметры
	* записи для сортировки: <br><b>ID</b> - код записи <br><b>MIN_NUM_POSTS</b> -
	* количество сообщений, которое пользователь должен написать на
	* форуме для получения этого количества балов за одно сообщение
	* <br><b>POINTS_PER_POST</b> - Количество балов за одно сообщение <br></p>
	*
	*
	*
	* @param array $arrayarFilter = array() <p>Фильтр на возвращаемые записи; представляет собой
	* ассоциативный массив, в котором ключами являются названия
	* параметров записи, а значениями - условия на эти параметры.</p>
	* <p>Допустимые параметры записи для фильтрации: <br><b>ID</b> - код записи
	* <br><b>MIN_NUM_POSTS</b> - количество сообщений, которое пользователь должен
	* написать на форуме для получения этого количества балов за одно
	* сообщение.</p>
	*
	*
	*
	* @return CDBResult <p>Возвращяется объект класса CDBResult, каждая запись которого
	* представляет собой массив с ключами.</p> <table class="tnormal" width="100%"><tbody> <tr>
	* <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>ID</td> <td>Код записи.</td> </tr> <tr>
	* <td>MIN_NUM_POSTS</td> <td>количество сообщений, которое пользователь должен
	* написать на форуме для получения этого количества балов за одно
	* сообщение.</td> </tr> <tr> <td>POINTS_PER_POST</td> <td>Количество балов за одно
	* сообщение. Может иметь как целое значение, так и дробное: <br> 2 - два
	* бала за каждое сообщение <br> 0.1 - одна десятая бала за каждое
	* сообщение.</td> </tr> </tbody></table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumpoints2post/getlist.php
	* @author Bitrix
	*/
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
	*
	*
	* @param int $ID  Код записи.
	*
	*
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

	
	/**
	* <p>Изменяет параметры голосования пользователя с кодом FROM_USER_ID за пользователя с кодом TO_USER_ID на значения, указанные в массиве arFields.</p>
	*
	*
	*
	*
	* @param int $FROM_USER_ID  Пользователь, который голосовал.
	*
	*
	*
	* @param int $TO_USER_ID  Пользователь, за которого голосовали.
	*
	*
	*
	* @param array $arFields  Массив новых значений параметров голосования.
	*
	*
	*
	* @return bool <p>Возвращает True в случае успешного изменения параметров
	* голосования и False - в противном случае.</p> <a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Изменим количество голосов, отданных текущим пользователем
	* // за пользователя с кодом $UID на 53
	* $arFields = array("POINTS" =&gt; 53);
	* if (CForumUserPoints::Update($USER-&gt;GetID(), $UID, $arFields))
	*    echo "Голосования успешно изменено";
	* else
	*    echo "Ошибка изменения голосования";
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuserpoints/update.php
	* @author Bitrix
	*/
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

	
	/**
	* <p>Функция удаляет из голосования голоса, отданные пользователем с кодом FROM_USER_ID пользователю с кодом TO_USER_ID.</p>
	*
	*
	*
	*
	* @param int $FROM_USER_ID  Код пользователя, отдавшего голос.
	*
	*
	*
	* @param int $TO_USER_ID  Код пользователя, которому был отдан голос.
	*
	*
	*
	* @return bool <p>Функция возвращает True в случае успешного удаления и False - в
	* случае ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuserpoints/delete.php
	* @author Bitrix
	*/
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

	
	/**
	* <p>Возвращает голосования за посетителей форума, которые удовлетворяют фильтру arFilter, упорядоченные в соответствии с порядком arOrder.</p>
	*
	*
	*
	*
	* @param array $arrayarOrder = array("TO_USER_ID"=>"ASC") <p>Порядок сортировки записей; представляет собой ассоциативный
	* массив, в котором ключами являются названия параметров
	* голосования, а значениями - направления сортировки.</p>
	* <p>Допустимые параметры голосования для сортировки: <br><b>FROM_USER_ID</b> -
	* код голосовавшего посетителя <br><b>TO_USER_ID</b> - код посетителя, за
	* которого голосовали <br><b>POINTS</b> - количество отданных голосов
	* <br><b>DATE_UPDATE</b> - дата последнего изменения записи </p>
	*
	*
	*
	* @param array $arrayarFilter = array() <p>Фильтр на возвращаемые голосования; представляет собой
	* ассоциативный массив, в котором ключами являются названия
	* параметров голосования, а значениями - условия на эти
	* параметры.</p> <p>Допустимые параметры голосования для фильтрации:
	* <br><b>FROM_USER_ID</b> - код голосовавшего посетителя <br><b>TO_USER_ID</b> - код
	* посетителя, за которого голосовали</p>
	*
	*
	*
	* @return CDBResult <p>Возвращяется объект класса CDBResult, каждая запись которого
	* представляет собой массив с ключами.</p> <table class="tnormal" width="100%"><tbody> <tr>
	* <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>FROM_USER_ID</td> <td>Код
	* пользователя, который отдал голос.</td> </tr> <tr> <td>TO_USER_ID</td> <td>Код
	* пользователя, за которого отдали голос.</td> </tr> <tr> <td>POINTS</td>
	* <td>Количество отданных голосов.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата
	* последнего изменения записи.</td> </tr> </tbody></table> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuserpoints/getlist.php
	* @author Bitrix
	*/
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

	
	/**
	* <p>Функция возвращает массив параметров голосования пользователя с кодом FROM_USER_ID за пользователя с кодом TO_USER_ID.</p>
	*
	*
	*
	*
	* @param int $FROM_USER_ID  Код пользователя, который отдал голос.
	*
	*
	*
	* @param int $TO_USER_ID  Код пользователя, за которого отдали голос.
	*
	*
	*
	* @return array <p>Возвращает ассоциативный массив с ключами.</p> <table class="tnormal"
	* width="100%"><tbody> <tr> <th width="15%">Ключ</th> <th>Значение</th> </tr> <tr> <td>FROM_USER_ID</td>
	* <td>Код пользователя, который отдал голос.</td> </tr> <tr> <td>TO_USER_ID</td>
	* <td>Код пользователя, за которого отдали голос.</td> </tr> <tr> <td>POINTS</td>
	* <td>Количество отданных голосов.</td> </tr> <tr> <td>DATE_UPDATE</td> <td>Дата
	* последнего изменения записи.</td> </tr> </tbody></table> <p></p><a name="examples"></a>
	*
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выведем, сколько голосов отдал текущий пользователь 
	* // за пользователя с кодом $AID
	* $arUserPoints = CForumUserPoints::GetByID($USER-&gt;GetID(), $AID);
	* if ($arUserPoints)
	* {
	*    echo "Вы отдали за этого пользователя ".$arUserPoints["POINTS"]. " голосов";
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuserpoints/getbyid.php
	* @author Bitrix
	*/
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
