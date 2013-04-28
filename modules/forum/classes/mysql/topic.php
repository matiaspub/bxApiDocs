<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/topic.php");


/**
 * <b>CForumTopic</b> - класс для работы с темами форумов.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/index.php
 * @author Bitrix
 */
class CForumTopic extends CAllForumTopic
{
	
	/**
	 * <p>Возвращает список тем по фильтру <i>arFilter</i>, отсортированый в соответствии с <i>arOrder</i>.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder  Массив вида Array(<i>by1</i>=&gt;<i>order1</i>[, <i>by2</i>=&gt;<i>order2</i> [, ..]]), где
	 * <br><br><i>by</i> - поле для сортировки, может принимать значения<br>
	 *     <i>ID</i> - ID темы;<br>     <i>TITLE</i> - заголовок темы;<br>     <i>STATE</i> -
	 * состояние темы;<br>     <i>USER_START_ID</i> - ID пользователя, который создал
	 * тему;<br>     <i>USER_START_NAME</i> - имя пользователя, который создал
	 * тему;<br>     <i>START_DATE</i> - дата создания темы;<br>     <i>POSTS</i> -
	 * количество ответов в теме;<br>     <i>VIEWS</i> - количество просмотров
	 * темы;<br>     <i>APPROVED</i> - опубликованность темы;<br>     <i>LAST_POST_DATE</i> -
	 * дата последнего сообщения в теме;<br>     <i>SORT</i> - индекс
	 * сортировки;<br><br><i>order</i> - порядок сортировки, может принимать
	 * значения<br>     <i>ASC</i> - по возрастанию;<br>     <i>DESC</i> - по
	 * убыванию;<br><br> Необязательный. По умолчанию равен Array("SORT"=&gt;"ASC")
	 *
	 *
	 *
	 * @param array $arFilter  массив вида array("фильтруемое поле"=&gt;"значения фильтра" [, ...])<br>
	 * "фильтруемое поле" может принимать значения: <ul> <i>STATE</i> - состояние
	 * темы;<br><i>FORUM_ID</i> - ID форума;<br><i>ID</i> - ID темы;<br><i>APPROVED</i> -
	 * опубликованность;<br><i>LAST_POST_DATE</i> - дата последнего сообщения в
	 * теме; <br><i>USER_START_ID</i> - ID автора темы. </ul> фильтруемое поле может
	 * иметь содержать перед названием тип проверки фильтра<br> "!" - не
	 * равно<br> "&lt;" - меньше<br> "&lt;=" - меньше либо равно<br> "&gt;" - больше<br>
	 * "&gt;=" - больше либо равно<br><br> Необязательное. По умолчанию записи
	 * не фильтруются.
	 *
	 *
	 *
	 * @param bool $bCount  Если параметр равен True, то возвращается только количество тем,
	 * которое соответствует установленному фильтру. Необязательный.
	 * По умолчанию равен False.
	 *
	 *
	 *
	 * @param int $iNum  Ограничение числа возвращаемых записей. Если параметр iNum отличен
	 * от нуля, то возвращается не более iNum записей. Поддерживается не
	 * для всех баз данных. Если параметр не поддерживается, то его
	 * установки игнорируются. Необязательный. По умолчанию равен 0.
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // выберем все опубликованные темы форума с кодом $FID, 
	 * // отсортированные сначала по индексу сортировки, а потом 
	 * // по дате последнего сообщения в теме
	 * $db_res = CForumTopic::GetList(array("SORT"=&gt;"ASC", "LAST_POST_DATE"=&gt;"DESC"), array("FORUM_ID"=&gt;$FID));
	 * while ($ar_res = $db_res-&gt;Fetch())
	 * {
	 *   echo $ar_res["TITLE"]."&lt;br&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">Поля темы</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/getlist.php
	 * @author Bitrix
	 */
	public static function GetList($arOrder = Array("SORT"=>"ASC"), $arFilter = Array(), $bCount = false, $iNum = 0, $arAddParams = array())
	{
		global $DB;
		$arOrder = (is_array($arOrder) ? $arOrder : array());
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));
		$arSqlSearch = array();
		$arSqlSelect = array();
		$arSqlGroup = array();
		$arSqlOrder = array();
		$strSqlSearch = "";
		$strSqlSelect = "";
		$strSqlGroup = "";
		$strSqlOrder = "";
		$UseGroup = false;

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "STATE":
				case "APPROVED":
				case "XML_ID":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL OR ".($DB->type == "MSSQL" ? "LEN" : "LENGTH")."(FT.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "ID":
				case "USER_START_ID":
				case "SOCNET_GROUP_ID":
				case "OWNER_ID":
				case "FORUM_ID":
				case "SORT":
				case "POSTS":
				case "TOPICS":
					if (($strOperation!="IN") && (intVal($val) > 0))
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." ".intVal($val)." )";
					elseif (($strOperation =="IN") && ((is_array($val) && (array_sum($val) > 0)) || (strlen($val) > 0) ))
					{
						if (!is_array($val)) 
							$val = explode(',', $val);
						$val_int = array();
						foreach ($val as $v)
							$val_int[] = intVal($v);
						$val = implode(", ", $val_int);

						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FT.".$key." IN (".$DB->ForSql($val).") )";
					}
					else 
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL OR FT.".$key."<=0)";
					break;
				case "RENEW_TOPIC":
//					vhodnye parametry tipa array("TID"=>time); 
//					pri TID = 0 peredaetsya FORUM_LAST_VISIT
					$arSqlTemp = array();
					$strSqlTemp = $val[0];
					unset($val[0]);
					if (is_array($val) && !empty($val))
					{
						foreach ($val as $k => $v)
							$arSqlTemp[] = "(FT.ID=".intVal($k).") AND (FT.LAST_POST_DATE > ".$DB->CharToDateFunction($DB->ForSql($v), "FULL").")";

						$val_int = array();
						foreach (array_keys($val) as $k)
							$val_int[] = intVal($k);
						$keys = implode(", ", $val_int);

					$arSqlSearch[] = 
							"(FT.ID IN (".$DB->ForSql($keys).") AND ((".implode(") OR (", $arSqlTemp).")))
							OR
							(FT.ID NOT IN (".$DB->ForSql($keys).") AND (FT.LAST_POST_DATE > ".$DB->CharToDateFunction($DB->ForSql($strSqlTemp), "FULL")."))";
					}
					break;
				case "START_DATE":
				case "LAST_POST_DATE":
					if(strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
					break;
			}
		}
		if (count($arSqlSearch)>0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).")";
		if (count($arSqlSelect) > 0)
			$strSqlSelect = ", ".implode(", ", $arSqlSelect);
		if ($UseGroup)
		{
			foreach ($arSqlSelect as $key => $val)
			{
				if (substr($key, 0, 1) != "!")
					$arSqlGroup[$key] = $val;
			}
			if (!empty($arSqlGroup)):
				$strSqlGroup = ", ".implode(", ", $arSqlGroup);
			endif;
		}
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if (in_array($by, array("ID", "FORUM_ID", "TOPIC_ID", "TITLE", "TAGS", "DESCRIPTION", "ICON_ID", 
					"STATE", "APPROVED", "SORT", "VIEWS", "USER_START_ID", "USER_START_NAME", "START_DATE", 
					"POSTS", "LAST_POSTER_ID", "LAST_POSTER_NAME", "LAST_POST_DATE", "LAST_MESSAGE_ID", 
					"POSTS_UNAPPROVED", "ABS_LAST_POSTER_ID", "ABS_LAST_POSTER_NAME", "ABS_LAST_POST_DATE", "ABS_LAST_MESSAGE_ID", 
					"SOCNET_GROUP_ID", "OWNER_ID", "HTML"))):
				$arSqlOrder[] = "FT.".$by." ".$order;
			else:
				$arSqlOrder[] = "FT.SORT ".$order;
				$by = "SORT";
			endif;
		}
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder); 
		if (count($arSqlOrder) > 0)
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);

		if ($bCount || (is_set($arAddParams, "bDescPageNumbering") && intVal($arAddParams["nTopCount"]) <= 0))
		{
			$strSql = 
				"SELECT COUNT(FT.ID) as CNT 
				FROM b_forum_topic FT
				WHERE 1 = 1 
				".$strSqlSearch;
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = 0;
			if ($ar_res = $db_res->Fetch()):
				$iCnt = intVal($ar_res["CNT"]);
			endif;
			if ($bCount):
				return $iCnt;
			endif;
		}

		$arSQL = array("select" => "", "join" => "");
		if (!empty($arAddParams["sNameTemplate"]))
		{
			$arSQL = array_merge_recursive(
				CForumUser::GetFormattedNameFieldsForSelect(array_merge(
					$arAddParams, array(
					"sUserTablePrefix" => "U_START.",
					"sForumUserTablePrefix" => "FU_START.",
					"sFieldName" => "USER_START_NAME_FRMT",
					"sUserIDFieldName" => "FT.USER_START_ID"))),
				CForumUser::GetFormattedNameFieldsForSelect(array_merge(
					$arAddParams, array(
					"sUserTablePrefix" => "U_LAST.",
					"sForumUserTablePrefix" => "FU_LAST.",
					"sFieldName" => "LAST_POSTER_NAME_FRMT",
					"sUserIDFieldName" => "FT.LAST_POSTER_ID"))),
				CForumUser::GetFormattedNameFieldsForSelect(array_merge(
					$arAddParams, array(
					"sUserTablePrefix" => "U_ABS_LAST.",
					"sForumUserTablePrefix" => "FU_ABS_LAST.",
					"sFieldName" => "ABS_LAST_POSTER_NAME_FRMT",
					"sUserIDFieldName" => "FT.ABS_LAST_POSTER_ID"))));
			$arSQL["select"] = ",\n\t".implode(",\n\t", $arSQL["select"]);
			$arSQL["join"] = "\n".implode("\n", $arSQL["join"]);
		}

		if ($UseGroup)
		{
			$strSql = 
				" SELECT F_T.*, FT.FORUM_ID, FT.TOPIC_ID, FT.TITLE, FT.TAGS, FT.DESCRIPTION, FT.ICON_ID, \n".
				"	FT.STATE, FT.APPROVED, FT.SORT, FT.VIEWS, FT.USER_START_ID, FT.USER_START_NAME, \n".
				"	".$DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE, \n".
				"	FT.POSTS, FT.LAST_POSTER_ID, FT.LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.LAST_POST_DATE", "FULL")." as LAST_POST_DATE, \n".
				"	FT.LAST_POST_DATE AS LAST_POST_DATE_ORIGINAL, FT.LAST_MESSAGE_ID, \n".
				"	FT.POSTS_UNAPPROVED, FT.ABS_LAST_POSTER_ID, FT.ABS_LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.ABS_LAST_POST_DATE", "FULL")." as ABS_LAST_POST_DATE, \n".
				"	FT.ABS_LAST_POST_DATE AS ABS_LAST_POST_DATE_ORIGINAL, FT.ABS_LAST_MESSAGE_ID, \n".
				"	FT.SOCNET_GROUP_ID, FT.OWNER_ID, FT.HTML".$arSQL["select"]." \n".
				" FROM ( \n".
				"		SELECT FT.ID".$strSqlSelect." \n".
				"		FROM b_forum_topic FT \n".
				"		WHERE 1 = 1 ".$strSqlSearch." \n".
				"		GROUP BY FT.ID ".$strSqlGroup." \n".
				" ) F_T \n".
				" INNER JOIN b_forum_topic FT ON (F_T.ID = FT.ID) ".$arSQL["join"]." \n".
				$strSqlOrder;
		}
		else
		{
			$strSql = 
				" SELECT FT.ID, FT.FORUM_ID, FT.TOPIC_ID, FT.TITLE, FT.TAGS, FT.DESCRIPTION, FT.ICON_ID, \n".
				"	FT.STATE, FT.APPROVED, FT.SORT, FT.VIEWS, FT.USER_START_ID, FT.USER_START_NAME, \n".
				"	".$DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE, \n".
				"	FT.POSTS, FT.LAST_POSTER_ID, FT.LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.LAST_POST_DATE", "FULL")." as LAST_POST_DATE, \n".
				"	FT.LAST_POST_DATE AS LAST_POST_DATE_ORIGINAL, FT.LAST_MESSAGE_ID, \n".
				"	FT.POSTS_UNAPPROVED, FT.ABS_LAST_POSTER_ID, FT.ABS_LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.ABS_LAST_POST_DATE", "FULL")." as ABS_LAST_POST_DATE, \n".
				"	FT.ABS_LAST_POST_DATE AS ABS_LAST_POST_DATE_ORIGINAL, FT.ABS_LAST_MESSAGE_ID, \n".
				"	FT.SOCNET_GROUP_ID, FT.OWNER_ID, FT.HTML".$strSqlSelect.$arSQL["select"]." \n".
				" FROM b_forum_topic FT ".$arSQL["join"]. " \n".
				" WHERE 1 = 1 ".$strSqlSearch." \n".
				$strSqlOrder;
		}

		$iNum = intVal($iNum);
		if ($iNum > 0 || intVal($arAddParams["nTopCount"]) > 0)
		{
			$iNum = ($iNum > 0) ? $iNum : intVal($arAddParams["nTopCount"]);
			$strSql .= "\nLIMIT 0,".$iNum;
		}
		
		if (!$iNum && is_set($arAddParams, "bDescPageNumbering") && intVal($arAddParams["nTopCount"]) <= 0)
		{
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
		}
		else 
		{
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		if (is_set($arAddParams, 'NoFilter') && $arAddParams['NoFilter'] == true)
			return $db_res;
		return new _CTopicDBResult($db_res, $arAddParams);
	}

	
	/**
	 * <p>Возвращает список тем (включая связаные данные) по фильтру <i>arFilter</i>, отсортированый в соответствии с <i>arOrder</i>.</p>
	 *
	 *
	 *
	 *
	 * @param array $arOrder  Массив вида Array(<i>by1</i>=&gt;<i>order1</i>[, <i>by2</i>=&gt;<i>order2</i> [, ..]]). Подробное
	 * описание параметра смотрите в описании функции <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/getlist.php">CForumTopic::GetList</a>.
	 *
	 *
	 *
	 * @param  $array  массив вида array("фильтруемое поле"=&gt;"значения фильтра" [, ...]). <br>
	 * Подробное описание параметра смотрите в описании функции <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/getlist.php">CForumTopic::GetList</a>.
	 *
	 *
	 *
	 * @param arFilte $r  Если параметр равен True, то возвращается только количество
	 * сообщений, которое соответствует установленному фильтру.
	 * Необязательный. По умолчанию равен False.
	 *
	 *
	 *
	 * @param  $bool  Ограничение числа возвращаемых записей. Если параметр iNum отличен
	 * от нуля, то возвращается не более iNum записей. Поддерживается не
	 * для всех баз данных. Если параметр не поддерживается, то его
	 * установки игнорируются. Необязательный. По умолчанию равен 0.
	 *
	 *
	 *
	 * @param bCoun $t  Осуществляет настройки постраничной навигации. На какое
	 * количество сообщений разбивать список сообщений и использовать
	 * ли обратную постраничную навигацию. Пример: array("nPageSize"=&gt;20,
	 * "bDescPageNumbering"=&gt;true).
	 *
	 *
	 *
	 * @param  $int  
	 *
	 *
	 *
	 * @param iNu $m  
	 *
	 *
	 *
	 * @return CDBResult <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>// выберем все опубликованные темы форума с кодом $FID, <br>// отсортированные сначала по индексу сортировки, а потом <br>// по дате последнего сообщения в теме<br>$db_res = CForumTopic::GetList(array("SORT"=&gt;"ASC", "LAST_POST_DATE"=&gt;"DESC"), array("FORUM_ID"=&gt;$FID));<br>while ($ar_res = $db_res-&gt;Fetch())<br>{<br>  echo "&lt;pre&gt;";<br>  print_r($ar_res);<br>  echo "&lt;/pre&gt;&lt;br&gt;";<br>}<br>?&gt;<br>
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/main/reference/cdbresult/index.php">CDBResult</a> </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">Поля темы</a> </li> </ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/getlistex.php
	 * @author Bitrix
	 */
	public static function GetListEx($arOrder = Array("SORT"=>"ASC"), $arFilter = Array(), $bCount = false, $iNum = 0, $arAddParams = array())
	{
		global $DB, $USER;
		$arOrder = (is_array($arOrder) ? $arOrder : array());
		$arFilter = (is_array($arFilter) ? $arFilter : array());
		$arSqlSearch = array();
		$arSqlFrom = array();
		$arSqlSelect = array();
		$arSqlGroup = array();
		$arSqlOrder = array();
		$strSqlSearch = "";
		$strSqlFrom = "";
		$strSqlSelect = "";
		$strSqlGroup = "";
		$strSqlOrder = "";
		$UseGroup = false;
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));

		foreach ($arFilter as $key => $val)
		{
			$key_res = CForumNew::GetFilterOperation($key);
			$key = strtoupper($key_res["FIELD"]);
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];

			switch ($key)
			{
				case "STATE":
				case "XML_ID":
				case "APPROVED":
					if (strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL OR ".($DB->type == "MSSQL" ? "LEN" : "LENGTH")."(FT.".$key.")<=0)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." '".$DB->ForSql($val)."' )";
					break;
				case "ID":
				case "FORUM_ID":
				case "SOCNET_GROUP_ID":
				case "OWNER_ID":
				case "USER_START_ID":
				case "SORT":
				case "POSTS":
				case "TOPICS":
					if (($strOperation!="IN")&&(intVal($val)>0))
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." ".intVal($val)." )";
					elseif (($strOperation =="IN") && ((is_array($val) && (array_sum($val) > 0)) || (strlen($val) > 0) ))
					{
						if (!is_array($val))
							$val = explode(',', $val);
						$val_int = array();
						foreach ($val as $v)
							$val_int[] = intVal($v);
						$val = implode(", ", $val_int);
						$arSqlSearch[] = ($strNegative=="Y"?" NOT ":"")."(FT.".$key." IN (".$DB->ForSql($val).") )";
					}
					else 
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL OR FT.".$key."<=0)";
					break;
				case "TITLE_ALL":
					$arSqlSearch[] = GetFilterQuery("FT.TITLE, FT.DESCRIPTION", $val);
					break;
				case "TITLE":
				case "DESCRIPTION":
					$arSqlSearch[] = GetFilterQuery("FT.".$key, $val);
					$arSqlSearch[] = GetFilterQuery("FT.".$key, $val);
					break;
				case "START_DATE":
				case "LAST_POST_DATE":
				case "ABS_LAST_POST_DATE":
					if(strlen($val)<=0)
						$arSqlSearch[] = ($strNegative=="Y"?"NOT":"")."(FT.".$key." IS NULL)";
					else
						$arSqlSearch[] = ($strNegative=="Y"?" FT.".$key." IS NULL OR NOT ":"")."(FT.".$key." ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")";
					break;
				case "USER_ID":
					$arSqlSelect["LAST_VISIT"] = $DB->DateToCharFunction("FUT.LAST_VISIT", "FULL");
					$arSqlFrom["FUT"] = "LEFT JOIN b_forum_user_topic FUT ON (".(
							strlen($val) <= 0 ? 
								($strNegative=="Y"?"NOT":"")."(FUT.USER_ID IS NULL)"
								:
								"FUT.USER_ID=".intVal($val)
						)." AND FUT.FORUM_ID = FT.FORUM_ID AND FUT.TOPIC_ID = FT.ID)";
					break;
				case "RENEW_TOPIC":
						if ((strlen($val)>0) && array_key_exists("FUT", $arSqlFrom))
						{
							$arSqlSearch[] =
								"((FT.LAST_POST_DATE ".$strOperation." ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").") AND
									(
										(LAST_VISIT IS NULL) OR
										(LAST_VISIT < ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")
									)
								)
								OR
								((FT.LAST_POST_DATE > FUT.LAST_VISIT) AND 
									(
										(LAST_VISIT IS NOT NULL) AND
										(LAST_VISIT > ".$DB->CharToDateFunction($DB->ForSql($val), "FULL").")
									)
								)";
						}
				break;
				case "PERMISSION":
					if (!is_array($val))
						$val = explode(',', $val);
					if (empty($val)):
						$val = $GLOBALS["USER"]->GetGroups();
					elseif (is_array($val)):
						$val_int = array();
						foreach ($val as $v)
							$val_int[] = intVal($v);
						$val = implode(", ", $val_int);
					endif;
					$arSqlFrom["FPP"] =
						" INNER JOIN ( \n".
						"	SELECT FPP.FORUM_ID, MAX(FPP.PERMISSION) AS PERMISSION \n".
						"	FROM b_forum_perms FPP \n".
						"	WHERE FPP.GROUP_ID IN (".$DB->ForSql($val).") AND FPP.PERMISSION > 'A' \n".
						"	GROUP BY FPP.FORUM_ID) FPP ON (FPP.FORUM_ID = FT.FORUM_ID) ";
					$arSqlSelect[] = "FPP.PERMISSION AS PERMISSION";
				break;
				case "RENEW":
					$val = (is_array($val) ? $val : array("USER_ID" => $val));
					$val["USER_ID"] = intVal($val["USER_ID"]);
					if ($val["USER_ID"] <= 0):
						continue;
					endif;
					$perms = "NOT_CHECK";
					$arUserGroups = $GLOBALS["USER"]->GetGroups();
					if (is_set($arFilter, "PERMISSION")):
						$perms = "NORMAL";
					elseif (is_set($arFilter, "APPROVED") && $arFilter["APPROVED"] == "Y"):
						$perms = "ONLY_APPROVED";
					endif;

					$arSqlFrom["FUT"] = "LEFT JOIN b_forum_user_topic FUT ON (FUT.USER_ID=".intVal($val["USER_ID"])." AND FUT.FORUM_ID = FT.FORUM_ID AND FUT.TOPIC_ID = FT.ID)";
					$arSqlFrom["FUF"] = "LEFT JOIN b_forum_user_forum FUF ON (FUF.USER_ID=".$val["USER_ID"]." AND FUF.FORUM_ID = FT.FORUM_ID)";
					$arSqlFrom["FUF_ALL"] = "LEFT JOIN b_forum_user_forum FUF_ALL ON (FUF_ALL.USER_ID=".$val["USER_ID"]." AND FUF_ALL.FORUM_ID = 0)";

					$arSqlSearch[] = "FT.STATE != 'L'";
					$arSqlSearch[] = "
					(
						FUT.LAST_VISIT IS NULL 
						AND 
						(
							(FUF_ALL.LAST_VISIT IS NULL AND FUF.LAST_VISIT IS NULL)
							OR 
							(
								FUF.LAST_VISIT IS NOT NULL
								AND 
								(
					".
						( $perms == "NORMAL" ? "
									(FPP.PERMISSION >= 'Q' AND FUF.LAST_VISIT < FT.ABS_LAST_POST_DATE)
									OR 
									(FT.APPROVED = 'Y' AND FUF.LAST_VISIT < FT.LAST_POST_DATE)
									" : 
							( $perms == "NOT_CHECK" ? "
									(FUF.LAST_VISIT < FT.ABS_LAST_POST_DATE OR FUF.LAST_VISIT < FT.LAST_POST_DATE)
									" : 
									"
									(FT.APPROVED = 'Y' AND FUF.LAST_VISIT < FT.LAST_POST_DATE)
									"
							)
						)
					."
								)
							)
							OR 
							(
								FUF.LAST_VISIT IS NULL AND FUF_ALL.LAST_VISIT IS NOT NULL 
								AND 
								(
					".
						( $perms == "NORMAL" ? "
									(FPP.PERMISSION >= 'Q' AND FUF_ALL.LAST_VISIT < FT.ABS_LAST_POST_DATE)
									OR 
									(FT.APPROVED = 'Y' AND FUF_ALL.LAST_VISIT < FT.LAST_POST_DATE)
									" : 
							( $perms == "NOT_CHECK" ? "
									(FUF_ALL.LAST_VISIT < FT.ABS_LAST_POST_DATE OR FUF_ALL.LAST_VISIT < FT.LAST_POST_DATE)
									" : 
									"
									(FT.APPROVED = 'Y' AND FUF_ALL.LAST_VISIT < FT.LAST_POST_DATE)
									"
							)
						)
					."
								)
							)
						)
					)
					OR
					(
						FUT.LAST_VISIT IS NOT NULL 
						AND 
						(
					".
						( $perms == "NORMAL" ? "
									(FPP.PERMISSION >= 'Q' AND FUT.LAST_VISIT < FT.ABS_LAST_POST_DATE)
									OR 
									(FT.APPROVED = 'Y' AND FUT.LAST_VISIT < FT.LAST_POST_DATE)
									" : 
							( $perms == "NOT_CHECK" ? "
									(FUT.LAST_VISIT < FT.ABS_LAST_POST_DATE OR FUT.LAST_VISIT < FT.LAST_POST_DATE)
									" : 
									"
									(FT.APPROVED = 'Y' AND FUT.LAST_VISIT < FT.LAST_POST_DATE)
									"
							)
						)
					."	)
					)";
				break;
				case "PERMISSION_STRONG":
					$arSqlFrom["FP"] = "LEFT JOIN b_forum_perms FP ON (FP.FORUM_ID=FT.FORUM_ID)";
					$arSqlSearch[] = "FP.GROUP_ID IN (".$DB->ForSql($USER->GetGroups()).") AND (FP.PERMISSION IN ('Q','U','Y'))"; 
					$UseGroup = true;
					break;
			}
		}
		if (count($arSqlSearch)>0)
			$strSqlSearch = " AND (".implode(") AND (", $arSqlSearch).")";
		if (count($arSqlSelect) > 0)
		{
			$res = array();
			foreach ($arSqlSelect as $key => $val)
			{
				if (substr($key, 0, 1) == "!")
					$key = substr($key, 1);
				if ($key != $val)
					$res[] = $val." AS ".$key;
				else 
					$res[] = $val;
			}
			$strSqlSelect = ", ".implode(", ", $res);
		}
		if (count($arSqlFrom) > 0)
			$strSqlFrom = implode("\n", $arSqlFrom);
		if ($UseGroup)
		{
			foreach ($arSqlSelect as $key => $val)
			{
				if (substr($key, 0, 1) != "!")
					$arSqlGroup[$key] = $val;
			}
			if (!empty($arSqlGroup)):
				$strSqlGroup = ", ".implode(", ", $arSqlGroup);
			endif;
		}

		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by); $order = strtoupper($order);
			if ($order!="ASC") $order = "DESC";
			if (in_array($by, array("ID", "FORUM_ID", "TOPIC_ID", "TITLE", "TAGS", "DESCRIPTION", "ICON_ID", 
					"STATE", "APPROVED", "SORT", "VIEWS", "USER_START_ID", "USER_START_NAME", "START_DATE", 
					"POSTS", "LAST_POSTER_ID", "LAST_POSTER_NAME", "LAST_POST_DATE", "LAST_MESSAGE_ID", 
					"POSTS_UNAPPROVED", "ABS_LAST_POSTER_ID", "ABS_LAST_POSTER_NAME", "ABS_LAST_POST_DATE", "ABS_LAST_MESSAGE_ID", 
					"SOCNET_GROUP_ID", "OWNER_ID", "HTML"))):
				$arSqlOrder[] = "FT.".$by." ".$order;
			elseif ($by == "FORUM_NAME"):
				$arSqlOrder[] = "F.NAME ".$order;
			else:
				$arSqlOrder[] = "FT.SORT ".$order;
				$by = "SORT";
			endif;
		}
		$arSqlOrder = array_unique($arSqlOrder);
		DelDuplicateSort($arSqlOrder);
		if (count($arSqlOrder) > 0):
			$strSqlOrder = " ORDER BY ".implode(", ", $arSqlOrder);
		endif;

		if ($bCount || (is_set($arAddParams, "bDescPageNumbering") && intVal($arAddParams["nTopCount"])<=0))
		{
			$strSql = "SELECT COUNT(FT.ID) as CNT FROM b_forum_topic FT ";

			$arCountSqlFrom = $arSqlFrom;
			if (isset($arSqlFrom['FUT']) && (strpos($strSqlSearch, "FUT.") === false))
				unset($arCountSqlFrom['FUT']);
			$strSqlCountFrom = implode("\n", $arCountSqlFrom);

			$strSql .= $strSqlCountFrom . " WHERE 1 = 1 ".$strSqlSearch;

			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$iCnt = 0;
			if ($ar_res = $db_res->Fetch())
			{
				$iCnt = intVal($ar_res["CNT"]);
			}
			if ($bCount)
				return $iCnt;
		}
		$arSQL = array("select" => "", "join" => "");
		if (!empty($arAddParams["sNameTemplate"]))
		{
			$arSQL = array_merge_recursive(
				CForumUser::GetFormattedNameFieldsForSelect(array_merge(
					$arAddParams, array(
					"sUserTablePrefix" => "U_START.",
					"sForumUserTablePrefix" => "FU_START.",
					"sFieldName" => "USER_START_NAME_FRMT",
					"sUserIDFieldName" => "FT.USER_START_ID"))),
				CForumUser::GetFormattedNameFieldsForSelect(array_merge(
					$arAddParams, array(
					"sUserTablePrefix" => "U_LAST.",
					"sForumUserTablePrefix" => "FU_LAST.",
					"sFieldName" => "LAST_POSTER_NAME_FRMT",
					"sUserIDFieldName" => "FT.LAST_POSTER_ID"))),
				CForumUser::GetFormattedNameFieldsForSelect(array_merge(
					$arAddParams, array(
					"sUserTablePrefix" => "U_ABS_LAST.",
					"sForumUserTablePrefix" => "FU_ABS_LAST.",
					"sFieldName" => "ABS_LAST_POSTER_NAME_FRMT",
					"sUserIDFieldName" => "FT.ABS_LAST_POSTER_ID"))));
			$arSQL["select"] = ",\n\t".implode(",\n\t", $arSQL["select"]);
			$arSQL["join"] = "\n".implode("\n", $arSQL["join"]);
		}

		if ($UseGroup)
		{
			$strSql =
				" SELECT F_T.*, FT.FORUM_ID, FT.TOPIC_ID, FT.TITLE, FT.TAGS, FT.DESCRIPTION, FT.ICON_ID, \n".
				"	FT.STATE, FT.APPROVED, FT.SORT, FT.VIEWS, FT.USER_START_ID, FT.USER_START_NAME, \n".
				"	".$DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE, \n".
				"	FT.POSTS, FT.LAST_POSTER_ID, FT.LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.LAST_POST_DATE", "FULL")." as LAST_POST_DATE, \n".
				"	FT.LAST_POST_DATE AS LAST_POST_DATE_ORIGINAL, FT.LAST_MESSAGE_ID, \n".
				"	FT.POSTS_UNAPPROVED, FT.ABS_LAST_POSTER_ID, FT.ABS_LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.ABS_LAST_POST_DATE", "FULL")." as ABS_LAST_POST_DATE, \n".
				"	FT.ABS_LAST_POST_DATE AS ABS_LAST_POST_DATE_ORIGINAL, FT.ABS_LAST_MESSAGE_ID, \n".
				"	FT.SOCNET_GROUP_ID, FT.OWNER_ID, FT.HTML, \n".
				"	F.NAME as FORUM_NAME, \n".
				"	FS.IMAGE, '' as IMAGE_DESCR ".$arSQL["select"]." \n".
				" FROM \n".
				"	( \n".
				"		SELECT FT.ID".$strSqlSelect." \n".
				"		FROM b_forum_topic FT \n".
				"			LEFT JOIN b_forum F ON (FT.FORUM_ID = F.ID) \n".
				"			".$strSqlFrom." \n".
				"		WHERE 1 = 1 ".$strSqlSearch." \n".
				"		GROUP BY FT.ID".$strSqlGroup." \n".
				"	) F_T \n".
				" INNER JOIN b_forum_topic FT ON (F_T.ID = FT.ID) \n".
				" LEFT JOIN b_forum F ON (FT.FORUM_ID = F.ID) \n".
				" LEFT JOIN b_forum_smile FS ON (FT.ICON_ID = FS.ID) ".$arSQL["join"]." \n".
				$strSqlOrder;
		}
		else
		{
			$strSql = 
				" SELECT FT.ID, FT.FORUM_ID, FT.TOPIC_ID, FT.TITLE, FT.TAGS, FT.DESCRIPTION, FT.ICON_ID, \n".
				"	FT.STATE, FT.APPROVED, FT.SORT, FT.VIEWS, FT.USER_START_ID, FT.USER_START_NAME, \n".
				"	".$DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE, \n".
				"	FT.POSTS, FT.LAST_POSTER_ID, FT.LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.LAST_POST_DATE", "FULL")." as LAST_POST_DATE, \n".
				"	FT.LAST_POST_DATE AS LAST_POST_DATE_ORIGINAL, FT.LAST_MESSAGE_ID, \n".
				"	FT.POSTS_UNAPPROVED, FT.ABS_LAST_POSTER_ID, FT.ABS_LAST_POSTER_NAME, \n".
				"	".$DB->DateToCharFunction("FT.ABS_LAST_POST_DATE", "FULL")." as ABS_LAST_POST_DATE, \n".
				"	FT.ABS_LAST_POST_DATE AS ABS_LAST_POST_DATE_ORIGINAL, FT.ABS_LAST_MESSAGE_ID, \n".
				"	FT.SOCNET_GROUP_ID, FT.OWNER_ID, FT.HTML, \n".
				"	F.NAME as FORUM_NAME, \n".
				"	FS.IMAGE, '' as IMAGE_DESCR".$strSqlSelect.$arSQL["select"]." \n".
				" FROM b_forum_topic FT \n".
				"	LEFT JOIN b_forum F ON (FT.FORUM_ID = F.ID) \n".
				"	LEFT JOIN b_forum_smile FS ON (FT.ICON_ID = FS.ID) \n".
				"	".$strSqlFrom.$arSQL["join"]." \n".
				" WHERE 1 = 1 ".$strSqlSearch." \n".
				$strSqlOrder;
		}

		$iNum = intVal($iNum);
		if ($iNum > 0 || intVal($arAddParams["nTopCount"]) > 0)
		{
			$iNum = ($iNum > 0) ? $iNum : intVal($arAddParams["nTopCount"]);
			$strSql .= "\nLIMIT 0,".$iNum;
		}
		if (!$iNum && is_set($arAddParams, "bDescPageNumbering") && intVal($arAddParams["nTopCount"]) <= 0)
		{
			$db_res =  new CDBResult();
			$db_res->NavQuery($strSql, $iCnt, $arAddParams);
		}
		else 
		{
			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		if (is_set($arAddParams, 'NoFilter') && $arAddParams['NoFilter'] == true)
			return $db_res;
		return new _CTopicDBResult($db_res, $arAddParams);
	}
}
?>