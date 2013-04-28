<?
##############################################
# Bitrix Site Manager Forum                  #
# Copyright (c) 2002-2009 Bitrix             #
# http://www.bitrixsoft.com                  #
# mailto:admin@bitrixsoft.com                #
##############################################
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/forum/classes/general/forum_new.php");

/**********************************************************************/
/************** FORUM *************************************************/
/**********************************************************************/

/**
 * <b>CForumUserPoints</b> - класс для работы с голосованиями пользователей форума за других пользователей форума.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumuserpoints/index.php
 * @author Bitrix
 */
class CForumNew extends CAllForumNew
{
	
	/**
	 * <p>Создает новый форум с параметрами, указанными в массиве <i>arFields</i>. Возвращает код созданного форума.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив вида Array(<i>field1</i>=&gt;<i>value1</i>[, <i>field2</i>=&gt;<i>value2</i> [, ..]]), где
	 * <br><br><i>field</i> - название поля; <br><i>value</i> - значение поля. <br><br> Поля
	 * перечислены в <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumnew">списке полей
	 * форума</a>. Обязательные поля должны быть заполнены.
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?<br>CModule::IncludeModule("forum");
	 * $arFields = Array(
	 *    "NAME" =&gt; "Название форума", <br>   "DESCRIPTION" =&gt; "Описание форума (м.б. пустым, м.б. html-код)",<br>   "FORUM_GROUP_ID" =&gt; 0,<br>   "GROUP_ID" =&gt; array(1 =&gt; "Y", 2 =&gt; "I"), <br>   "SITES" =&gt; array(), // заполняется ниже<br>   "ACTIVE" =&gt; "Y", <br>   "MODERATION" =&gt; "N",<br>   "INDEXATION" =&gt; "Y",<br>   "SORT" =&gt; 150,<br>   "ASK_GUEST_EMAIL" =&gt; "N",<br>   "USE_CAPTCHA" =&gt; "N",<br>   "ALLOW_HTML" =&gt; "N",<br>   "ALLOW_ANCHOR" =&gt; "Y",<br>   "ALLOW_BIU" =&gt; "Y",<br>   "ALLOW_IMG" =&gt; "Y",<br>   "ALLOW_VIDEO" =&gt; "Y",<br>   "ALLOW_LIST" =&gt; "Y",<br>   "ALLOW_QUOTE" =&gt; "Y",<br>   "ALLOW_CODE" =&gt; "Y",<br>   "ALLOW_FONT" =&gt; "Y",<br>   "ALLOW_SMILES" =&gt; "Y",<br>   "ALLOW_UPLOAD" =&gt; "Y", <br>   "ALLOW_UPLOAD_EXT" =&gt; "",<br>   "ALLOW_TOPIC_TITLED" =&gt; "N", <br>   "EVENT1" =&gt; "forum");<br><br>$db_res = CSite::GetList($lby="sort", $lorder="asc");<br>while ($res = $db_res-&gt;Fetch()):<br>   $arFields["SITES"][$res["LID"]] = "/".$res["LID"]."/forum/#FORUM_ID#/#TOPIC_ID#/";<br>endwhile;<br><br>$res = CForumNew::Add($arFields);<br>if (intVal($res) &gt; 0):<br>   echo "New Forum ID: ".$res;<br>else:<br>   $e = $GLOBALS['APPLICATION']-&gt;GetException();<br>   if ($e &amp;&amp; $str = $e-&gt;GetString()):<br>       echo "Error: ".$str;<br>   else:<br>       echo "Unknown Error";<br>   endif;<br>endif;<br>?&gt;<br><br>Короткий пример добавления форума c обязательными полями:<br> 
	 * &lt;?<br>CModule::IncludeModule("forum");
	 * $arFields = Array(
	 *    "NAME" =&gt; "Имя форума",<br>   "GROUP_ID" =&gt; array(1 =&gt; "Y", 2 =&gt; "I"), <br>   "SITES" =&gt; array(<br>       "ru" =&gt; "/url/"));<br>$res = CForumNew::Add($arFields);<br>?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumnew">Поля форума</a> </li>
	 * <li>Перед добавлением форума следует проверить возможность
	 * добавления методом <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumnew/canuseraddforum.php">CForumNew::CanUserAddForum</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumnew/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumNew::CheckFields("ADD", $arFields))
			return false;
/***************** Event onBeforeForumAdd **************************/
		$events = GetModuleEvents("forum", "onBeforeForumAdd");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$arInsert = $DB->PrepareInsert("b_forum", $arFields);
		$strSql = "INSERT INTO b_forum(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());

		if ($ID > 0)
		{
			foreach ($arFields["SITES"] as $key => $value)
			{
				$DB->Query("INSERT INTO b_forum2site (FORUM_ID, SITE_ID, PATH2FORUM_MESSAGE) VALUES(".$ID.", '".$DB->ForSql($key, 2)."', '".$DB->ForSql($value, 250)."')",
					false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			if (is_set($arFields, "GROUP_ID") && is_array($arFields["GROUP_ID"]))
			{
				CForumNew::SetAccessPermissions($ID, $arFields["GROUP_ID"]);
			}
		}
/***************** Event onAfterForumAdd ***************************/
		$events = GetModuleEvents("forum", "onAfterForumAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	public static function OnReindex($NS = array(), $oCallback = NULL, $callback_method = "")
	{
		global $DB;

		$arResultAll = array();
		$arParams = array(
			"PERMISSION" => array(),
			"SITE" => array(),
			"DEFAULT_URL" => array());
		$search_message_count = intVal(COption::GetOptionInt("forum", "search_message_count", 0));

		$strNSJoin = "";
		$strFilter = "";

		if ($NS["MODULE"] == "forum" && intVal($NS["ID"]) > 0 && intVal($NS["CNT"]) > 0)
			$strFilter = " AND (FM.ID>".intVal($NS["ID"]).") ";
		elseif ($NS["MODULE"] == "forum" && intVal($NS["ID"]) > 0) // out of date
			$strFilter = " AND (FM.ID>=".intVal($NS["ID"]).") ";
		if ($NS["SITE_ID"] != "")
		{
			$strNSJoin .= " INNER JOIN b_forum2site FS ON (FS.FORUM_ID=F.ID) ";
			$strFilter .= " AND FS.SITE_ID='".$DB->ForSQL($NS["SITE_ID"])."' ";
		}

		$strSql =
			"SELECT STRAIGHT_JOIN FT.ID as TID, FM.ID as MID, FM.ID AS ID, FT.FORUM_ID, FT.TITLE, FT.DESCRIPTION, FT.TAGS, FT.HTML as FT_HTML,
				FM.PARAM1, FM.PARAM2, FM.POST_MESSAGE, FM.POST_MESSAGE_FILTER, FM.POST_MESSAGE_HTML, FM.AUTHOR_NAME, FM.AUTHOR_ID, FM.NEW_TOPIC,
				".$DB->DateToCharFunction("FM.POST_DATE")." as POST_DATE, ".$DB->DateToCharFunction("FM.EDIT_DATE")." as EDIT_DATE, FT.SOCNET_GROUP_ID, FT.OWNER_ID
			FROM b_forum_message FM use index (PRIMARY), b_forum_topic FT, b_forum F
			".$strNSJoin."
			WHERE (FM.TOPIC_ID = FT.ID) AND (F.ID = FT.FORUM_ID) AND (F.INDEXATION = 'Y') AND (FM.APPROVED = 'Y')
			".$strFilter."
			ORDER BY FM.ID";
		if ($search_message_count > 0)
			$strSql .= " LIMIT 0, ".$search_message_count;

		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($db_res && COption::GetOptionString("forum", "FILTER", "Y") == "Y")
			$db_res = new _CMessageDBResult($db_res);
		$rownum = 0;
		while ($res = $db_res->Fetch())
		{
			$rownum++;
			if (empty($arParams["PERMISSION"][$res["FORUM_ID"]]))
			{
				$arGroups = CForumNew::GetAccessPermissions($res["FORUM_ID"]);
				$arParams["PERMISSION"][$res["FORUM_ID"]] = array();
				for ($i = 0; $i < count($arGroups); $i++)
				{
					if ($arGroups[$i][1] >= "E")
					{
						$arParams["PERMISSION"][$res["FORUM_ID"]][] = $arGroups[$i][0];
						if ($arGroups[$i][0]==2)
							break;
					}
				}
			}

			if (empty($arParams["SITE"][$res["FORUM_ID"]]))
			{
				$arParams["SITE"][$res["FORUM_ID"]] =  CForumNew::GetSites($res["FORUM_ID"]);
			}

			$arResult = array(
				"ID" => $res["MID"],
				"LID" => array(),
				"DATE_CHANGE" => ((!empty($res["EDIT_DATE"])) ? $res["EDIT_DATE"] : $res["POST_DATE"]),
				"PARAM1" => $res["FORUM_ID"],
				"PARAM2" => $res["TID"],
				"USER_ID" => $res["AUTHOR_ID"],
				"ENTITY_TYPE_ID"  => ($res["NEW_TOPIC"] == "Y" ? "FORUM_TOPIC" : "FORUM_POST"),
				"ENTITY_ID" => ($res["NEW_TOPIC"] == "Y" ? $res["TID"] : $res["MID"]),
				"PERMISSIONS" => $arParams["PERMISSION"][$res["FORUM_ID"]],
				"TITLE" => $res["TITLE"].($res["NEW_TOPIC"] == "Y" && !empty($res["DESCRIPTION"]) ?
						", ".$res["DESCRIPTION"] : ""),
				"TAGS" => ($res["NEW_TOPIC"] == "Y" ? $res["TAGS"] : ""),
				"BODY" => GetMessage("AVTOR_PREF")." ".$res["AUTHOR_NAME"].". ".
					(textParser::killAllTags(
						COption::GetOptionString("forum", "FILTER", "Y") != "Y" ? $res["POST_MESSAGE"] : $res["POST_MESSAGE_FILTER"])),
				"URL" => "",
				"INDEX_TITLE" => $res["NEW_TOPIC"] == "Y",
			);

			foreach ($arParams["SITE"][$res["FORUM_ID"]] as $key => $val)
			{
				$arResult["LID"][$key] = CForumNew::PreparePath2Message($val,
					array("FORUM_ID"=>$res["FORUM_ID"], "TOPIC_ID"=>$res["TID"], "MESSAGE_ID"=>$res["MID"],
						"SOCNET_GROUP_ID" => $res["SOCNET_GROUP_ID"], "OWNER_ID" => $res["OWNER_ID"],
						"PARAM1" => $res["PARAM1"], "PARAM2" => $res["PARAM2"]));
				if (empty($arResult["URL"]) && !empty($arResult["LID"][$key]))
					$arResult["URL"] = $arResult["LID"][$key];
			}

			if (empty($arResult["URL"]))
			{
				if (empty($arParams["DEFAULT_URL"][$res["FORUM_ID"]]))
				{
					$arParams["DEFAULT_URL"][$res["FORUM_ID"]] = "/";
					foreach ($arParams["SITE"][$res["FORUM_ID"]] as $key => $val):
						$db_lang = CLang::GetByID($key);
						if ($db_lang && $ar_lang = $db_lang->Fetch()):
							$arParams["DEFAULT_URL"][$res["FORUM_ID"]] = $ar_lang["DIR"];
							break;
						endif;
					endforeach;
					$arParams["DEFAULT_URL"][$res["FORUM_ID"]] .= COption::GetOptionString("forum", "REL_FPATH", "").
						"forum/read.php?FID=#FID#&TID=#TID#&MID=#MID##message#MID#";
				}
				$arResult["URL"] = CForumNew::PreparePath2Message($arParams["DEFAULT_URL"][$res["FORUM_ID"]],
					array("FORUM_ID"=>$res["FORUM_ID"], "TOPIC_ID"=>$res["TID"], "MESSAGE_ID"=>$res["MID"],
						"SOCNET_GROUP_ID" => $res["SOCNET_GROUP_ID"], "OWNER_ID" => $res["OWNER_ID"],
						"PARAM1" => $res["PARAM1"], "PARAM2" => $res["PARAM2"]));
			}

			if($oCallback)
			{
				$resCall = call_user_func(array($oCallback, $callback_method), $arResult);
				if(!$resCall)
					return $arResult["ID"];
			}
			else
			{
				$arResultAll[] = $arResult;
			}
		}

		if ($oCallback && ($search_message_count > 0) && ($rownum >= ($search_message_count - 1)))
			return $arResult["ID"];
		if ($oCallback)
			return false;

		return $arResultAll;
	}

	public static function GetNowTime($ResultType = "timestamp")
	{
		global $DB;
		static $result = array();
		$ResultType = (in_array($ResultType, array("timestamp", "time")) ? $ResultType : "timestamp");
		if (empty($result)):
			$db_res = $DB->Query("SELECT ".$DB->DateToCharFunction($DB->GetNowFunction(), "FULL")." FORUM_DATE", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$res = $db_res->Fetch();
			$result["time"] = $res["FORUM_DATE"];
			$result["timestamp"] = MakeTimeStamp($res["FORUM_DATE"]);
		endif;
		return $result[$ResultType];
	}
}

/**********************************************************************/
/************** FORUM GROUP *******************************************/
/**********************************************************************/

/**
 * <b>CForumGroup</b> - класс для работы с группами форумов.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/index.php
 * @author Bitrix
 */
class CForumGroup extends CAllForumGroup
{
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumGroup::CheckFields("ADD", $arFields))
			return false;
		if(CACHED_b_forum_group !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_forum_group");
/***************** Event onBeforeGroupForumsAdd ********************/
		$events = GetModuleEvents("forum", "onBeforeGroupForumsAdd");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$arInsert = $DB->PrepareInsert("b_forum_group", $arFields);
		$strSql = "INSERT INTO b_forum_group(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());

		for ($i = 0; $i < count($arFields["LANG"]); $i++)
		{
			$arInsert = $DB->PrepareInsert("b_forum_group_lang", $arFields["LANG"][$i]);
			$strSql = "INSERT INTO b_forum_group_lang(FORUM_GROUP_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		CForumGroup::Resort();
/***************** Event onAfterGroupForumsAdd *********************/
		$events = GetModuleEvents("forum", "onAfterGroupForumsAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	
	/**
	 * <p>Изменяет параметры существующей группы с кодом <i>ID</i> на параметры, указанные в массиве <i>arFields</i>. Возвращает код изменяемой группы.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код группы, параметры которой необходимо изменить.
	 *
	 *
	 *
	 * @param array $arFields  Массив вида Array(<i>field1</i>=&gt;<i>value1</i>[, <i>field2</i>=&gt;<i>value2</i> [, ..]]), где
	 * <br><br><i>field</i> - название поля;<br><i>value</i> - значение поля.<br><br> Поля
	 * перечислены в <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumgroup">списке полей
	 * групп</a>. В специальное поле "LANG" заносится массив массивов полей
	 * языковых параметров групп, которые имеют аналогичную структуру.
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arFields = array("SORT" =&gt; $SORT);
	 * $arSysLangs = array("ru", "en");
	 * for ($i = 0; $i&lt;count($arSysLangs); $i++)
	 * {
	 *   $arFields["LANG"][] = array(
	 *     "LID" =&gt; $arSysLangs[$i],
	 *     "NAME" =&gt; ${"NAME_".$arSysLangs[$i]},
	 *     "DESCRIPTION" =&gt; ${"DESCRIPTION_".$arSysLangs[$i]}
	 *     );
	 * }
	 * $ID1 = CForumGroup::Update($ID, $arFields);
	 * if (IntVal($ID1)&lt;=0)
	 *   echo "Error!";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumgroup">Поля группы</a> </li></ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumgroup/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;
		$ID = intVal($ID);
		if ($ID <= 0):
			return false;
		endif;

		if (!CForumGroup::CheckFields("UPDATE", $arFields, $ID))
			return false;
		if(CACHED_b_forum_group !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_forum_group");
/***************** Event onBeforeGroupForumsUpdate *****************/
		$events = GetModuleEvents("forum", "onBeforeGroupForumsUpdate");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_forum_group", $arFields);
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_forum_group SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		if (is_set($arFields, "LANG"))
		{
			$DB->Query("DELETE FROM b_forum_group_lang WHERE FORUM_GROUP_ID = ".$ID, false, "File: ".__FILE__."<br>Line: ".__LINE__);

			for ($i = 0; $i<count($arFields["LANG"]); $i++)
			{
				$arInsert = $DB->PrepareInsert("b_forum_group_lang", $arFields["LANG"][$i]);
				$strSql = "INSERT INTO b_forum_group_lang(FORUM_GROUP_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
		}
		CForumGroup::Resort();
/***************** Event onAfterGroupForumsUpdate *****************/
		$events = GetModuleEvents("forum", "onAfterGroupForumsUpdate");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}
}

/**********************************************************************/
/************** FORUM SMILE *******************************************/
/**********************************************************************/

/**
 * <b>CForumSmile</b> - класс для работы со смайлами.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumsmile/index.php
 * @author Bitrix
 */
class CForumSmile extends CAllForumSmile
{
	
	/**
	 * <p>Создает новый смайл с параметрами, указанными в массиве <i>arFields</i>. Возвращает код созданного смайла.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив вида Array(<i>field1</i>=&gt;<i>value1</i>[, <i>field2</i>=&gt;<i>value2</i> [, ..]]), где
	 * <br><br><i>field</i> - название поля;<br><i>value</i> - значение поля.<br><br> Поля
	 * перечислены в <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumsmile">списке полей
	 * смайла</a>. В специальное поле "LANG" заносится массив массивов полей
	 * языковых параметров смайла, которые имеют аналогичную структуру.
	 * Обязательные поля должны быть заполнены.
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumsmile">Поля смайла</a> </li></ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumsmile/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		if (!CForumSmile::CheckFields("ADD", $arFields))
			return false;

		if(CACHED_b_forum_smile !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_forum_smile");

		$arInsert = $DB->PrepareInsert("b_forum_smile", $arFields);
		$strSql = "INSERT INTO b_forum_smile(".$arInsert[0].") VALUES(".$arInsert[1].")";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ID = intVal($DB->LastID());

		foreach ($arFields["LANG"] as $i => $val)
		{
			$arInsert = $DB->PrepareInsert("b_forum_smile_lang", $arFields["LANG"][$i]);
			$strSql = "INSERT INTO b_forum_smile_lang(SMILE_ID, ".$arInsert[0].") VALUES(".$ID.", ".$arInsert[1].")";
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return $ID;
	}
}
?>