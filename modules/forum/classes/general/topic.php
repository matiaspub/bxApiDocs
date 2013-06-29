<?
IncludeModuleLangFile(__FILE__);
/**********************************************************************/
/************** FORUM TOPIC *******************************************/
/**********************************************************************/

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
class CAllForumTopic
{
	public static function CanUserViewTopic($TID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$TID = intVal($TID);
		$arTopic = CForumTopic::GetByID($TID);
		if ($arTopic)
		{
			if ($ExternalPermission === false && (in_array(1, $arUserGroups) || $GLOBALS["APPLICATION"]->GetGroupRight("forum", $arUserGroups) >= "W")):
				return true;
			endif;
			$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups) : $ExternalPermission);
			if ($strPerms >= "Y")
				return true;
			if ($strPerms < "E" || ($strPerms < "Q" && $arTopic["APPROVED"] != "Y"))
				return false;
			$arForum = CForumNew::GetByID($arTopic["FORUM_ID"]);
			return ($arForum["ACTIVE"] == "Y" ? true : false);
		}
		return false;
	}

	
	/**
	 * <p>Всесторонне проверяет, может ли пользователь с кодом <i>iUserID</i>, входящий в группы <i>arUserGroups</i>, добавить новую тему в форум <i>FID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param int $FID  Код форума, в который пользователь хочет добавить тему.
	 *
	 *
	 *
	 * @param array $arUserGroups  Массив групп, в которые входит пользователь. Для текущего
	 * пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	 *
	 *
	 *
	 * @param int $iUserID  Код пользователя. Для текущего пользователя он возвращается
	 * методом $USER-&gt;GetID()
	 *
	 *
	 *
	 * @return bool <p>Возвращает True, если пользователь имеет все права на добавление
	 * темы. В противном случае возвращается значение false.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CForumTopic::CanUserAddTopic($FID, 
	 *                                  $USER-&gt;GetUserGroupArray(), 
	 *                                  $USER-&gt;GetID()))
	 * {
	 * 	echo "You can add topic!";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuserupdatetopic.php">CForumTopic::CanUserUpdateTopic</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuserdeletetopic.php">CForumTopic::CanUserDeleteTopic</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/canuseraddtopic.php
	 * @author Bitrix
	 */
	public static function CanUserAddTopic($FID, $arUserGroups, $iUserID = 0, $arForum = false, $ExternalPermission = false)
	{
		if (!$arForum || (!is_array($arForum)) || (intVal($arForum["ID"]) != intVal($FID)))
			$arForum = CForumNew::GetByID($FID);
		if (is_array($arForum) && $arForum["ID"] = $FID)
		{
			if ($ExternalPermission === false && (in_array(1, $arUserGroups) || $GLOBALS["APPLICATION"]->GetGroupRight("forum", $arUserGroups) >= "W")):
				return true;
			endif;
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arForum["ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arForum["ID"]);
			endif;
			if ($strPerms >= "Y")
				return true;
			if ($strPerms < "M")
				return false;
			return ($arForum["ACTIVE"] == "Y" ? true : false);
		}
		return false;
	}

	
	/**
	 * <p>Всесторонне проверяет, может ли пользователь с кодом <i>iUserID</i>, входящий в группы <i>arUserGroups</i>, изменить тему с кодом <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код темы, которую пользователь хочет изменить.
	 *
	 *
	 *
	 * @param array $arUserGroups  Массив групп, в которые входит пользователь. Для текущего
	 * пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	 *
	 *
	 *
	 * @param int $iUserID  Код пользователя. Для текущего пользователя он возвращается
	 * методом $USER-&gt;GetID()
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CForumTopic::CanUserUpdateTopic($ID, $USER-&gt;GetUserGroupArray(), $USER-&gt;GetID()))
	 * {
	 * 	echo "You can modify this topic!";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuseraddtopic.php">CForumTopic::CanUserAddTopic</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuserdeletetopic.php">CForumTopic::CanUserDeleteTopic</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/canuserupdatetopic.php
	 * @author Bitrix
	 */
	public static function CanUserUpdateTopic($TID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$TID = intVal($TID);
		$iUserID = intVal($iUserID);
		$arTopic = CForumTopic::GetByID($TID);
		if ($arTopic)
		{
			if ($ExternalPermission === false && (in_array(1, $arUserGroups) || $GLOBALS["APPLICATION"]->GetGroupRight("forum", $arUserGroups) >= "W")):
				return true;
			endif;
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arTopic["FORUM_ID"]);
			endif;
			if ($strPerms >= "Y")
				return true;
			elseif ($strPerms < "M" || ($strPerms < "Q" && ($arTopic["APPROVED"] != "Y" || $arTopic["STATE"] != "Y")))
				return false;
			$arForum = CForumNew::GetByID($arTopic["FORUM_ID"]);
			if ($arForum["ACTIVE"] != "Y")
				return false;
			elseif ($strPerms >= "U")
				return true;
			$db_res = CForumMessage::GetList(array("ID"=>"ASC"), array("TOPIC_ID"=>$TID, "FORUM_ID"=>$arTopic["FORUM_ID"]), False, 2);
			$iCnt = 0; $iOwner = 0;
			if (!($db_res && $res = $db_res->Fetch()))
				return false;
			else
			{
				$iCnt++; $iOwner = intVal($res["AUTHOR_ID"]);
				if ($res = $db_res->Fetch())
					return false;
			}
			if ($iOwner <= 0 || $iUserID <= 0 || $iOwner != $iUserID)
				return false;
			return true;
		}
		return false;
	}

	
	/**
	 * <p>Всесторонне проверяет, может ли пользователь с кодом <i>iUserID</i>, входящий в группы <i>arUserGroups</i>, удалить тему с кодом <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код темы, которую пользователь хочет удалить.
	 *
	 *
	 *
	 * @param array $arUserGroups  Массив групп, в которые входит пользователь. Для текущего
	 * пользователя он возвращается методом $USER-&gt;GetUserGroupArray()
	 *
	 *
	 *
	 * @param int $iUserID  Код пользователя. Для текущего пользователя он возвращается
	 * методом $USER-&gt;GetID()
	 *
	 *
	 *
	 * @return bool <p>Возвращает True, если пользователь имеет все права на удаление
	 * темы. В противном случае возвращается значение false.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CForumTopic::CanUserDeleteTopic($ID, $USER-&gt;GetUserGroupArray(), $USER-&gt;GetID()))
	 * {
	 * 	CForumTopic::Delete($ID);
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuseraddtopic.php">CForumTopic::CanUserAddTopic</a>
	 * </li> <li> <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuserupdatetopic.php">CForumTopic::CanUserUpdateTopic</a>
	 * </li> </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/canuserdeletetopic.php
	 * @author Bitrix
	 */
	public static function CanUserDeleteTopic($TID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$TID = intVal($TID);
		$arTopic = CForumTopic::GetByID($TID);
		if ($arTopic)
		{
			if ($ExternalPermission === false && (in_array(1, $arUserGroups) || $GLOBALS["APPLICATION"]->GetGroupRight("forum", $arUserGroups) >= "W")):
				return true;
			endif;
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arTopic["FORUM_ID"]);
			endif;
			if ($strPerms >= "Y")
				return true;
			elseif ($strPerms < "U")
				return false;
			$arForum = CForumNew::GetByID($arTopic["FORUM_ID"]);
			return ($arForum["ACTIVE"] == "Y" ? true : false);
		}
		return false;
	}

	public static function CanUserDeleteTopicMessage($TID, $arUserGroups, $iUserID = 0, $ExternalPermission = false)
	{
		$TID = intVal($TID);
		$arTopic = CForumTopic::GetByID($TID);
		if ($arTopic)
		{
			if ($ExternalPermission === false && (in_array(1, $arUserGroups) || $GLOBALS["APPLICATION"]->GetGroupRight("forum", $arUserGroups) >= "W")):
				return true;
			endif;
			if (!CForumUser::IsLocked($iUserID)):
				$strPerms = ($ExternalPermission == false ? CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups) : $ExternalPermission);
			else:
				$strPerms = CForumNew::GetPermissionUserDefault($arTopic["FORUM_ID"]);
			endif;
			if ($strPerms >= "Y")
				return true;
			elseif ($strPerms < "U")
				return false;
			$arForum = CForumNew::GetByID($arTopic["FORUM_ID"]);
			return ($arForum["ACTIVE"] == "Y" ? true : false);
		}
		return false;
	}

	public static function CheckFields($ACTION, &$arFields)
	{
		// Fatal Errors
		if (is_set($arFields, "TITLE") || $ACTION=="ADD")
		{
			$arFields["TITLE"] = trim($arFields["TITLE"]);
			if (strLen($arFields["TITLE"]) <= 0)
				return false;
		}

		if (is_set($arFields, "USER_START_NAME") || $ACTION=="ADD")
		{
			$arFields["USER_START_NAME"] = trim($arFields["USER_START_NAME"]);
			if (strLen($arFields["USER_START_NAME"]) <= 0)
				return false;
		}

		if (is_set($arFields, "FORUM_ID") || $ACTION=="ADD")
		{
			$arFields["FORUM_ID"] = intVal($arFields["FORUM_ID"]);
			if ($arFields["FORUM_ID"] <= 0)
				return false;
		}
		if (is_set($arFields, "LAST_POSTER_NAME") || $ACTION=="ADD")
		{
			$arFields["LAST_POSTER_NAME"] = trim($arFields["LAST_POSTER_NAME"]);
			if (strLen($arFields["LAST_POSTER_NAME"]) <= 0 && $arFields["APPROVED"] !== "N" && $arFields["STATE"] !== "L")
				return false;
		}
		if (is_set($arFields, "ABS_LAST_POSTER_NAME") || $ACTION=="ADD")
		{
			$arFields["ABS_LAST_POSTER_NAME"] = trim($arFields["ABS_LAST_POSTER_NAME"]);
			if (strLen($arFields["ABS_LAST_POSTER_NAME"]) <= 0 && $ACTION == "ADD" && !empty($arFields["LAST_POSTER_NAME"]))
				$arFields["ABS_LAST_POSTER_NAME"] = $arFields["LAST_POSTER_NAME"];
			elseif (strLen($arFields["ABS_LAST_POSTER_NAME"]) <= 0 && $arFields["APPROVED"] !== "N" && $arFields["STATE"] !== "L")
				return false;
		}

		// Check Data
		if (is_set($arFields, "USER_START_ID") || $ACTION=="ADD")
			$arFields["USER_START_ID"] = (intVal($arFields["USER_START_ID"]) > 0 ? intVal($arFields["USER_START_ID"]) : false);
		if (is_set($arFields, "LAST_POSTER_ID") || $ACTION=="ADD")
			$arFields["LAST_POSTER_ID"] = (intVal($arFields["LAST_POSTER_ID"]) > 0 ? intVal($arFields["LAST_POSTER_ID"]) : false);
		if (is_set($arFields, "LAST_MESSAGE_ID") || $ACTION=="ADD")
			$arFields["LAST_MESSAGE_ID"] = (intVal($arFields["LAST_MESSAGE_ID"]) > 0 ? intVal($arFields["LAST_MESSAGE_ID"]) : false);
		if (is_set($arFields, "ICON_ID") || $ACTION=="ADD")
			$arFields["ICON_ID"] = (intVal($arFields["ICON_ID"]) > 0 ? intVal($arFields["ICON_ID"]) : false);
		if (is_set($arFields, "STATE") || $ACTION=="ADD")
			$arFields["STATE"] = (in_array($arFields["STATE"], array("Y", "N", "L")) ?  $arFields["STATE"] : "Y");
		if (is_set($arFields, "APPROVED") || $ACTION=="ADD")
			$arFields["APPROVED"] = ($arFields["APPROVED"] == "N" ? "N" : "Y");
		if (is_set($arFields, "SORT") || $ACTION=="ADD")
			$arFields["SORT"] = (intVal($arFields["SORT"]) > 0 ? intVal($arFields["SORT"]) : 150);
		if (is_set($arFields, "VIEWS") || $ACTION=="ADD")
			$arFields["VIEWS"] = (intVal($arFields["VIEWS"]) > 0 ? intVal($arFields["VIEWS"]) : 0);
		if (is_set($arFields, "POSTS") || $ACTION=="ADD")
			$arFields["POSTS"] = (intVal($arFields["POSTS"]) > 0 ? intVal($arFields["POSTS"]) : 0);
		if (is_set($arFields, "TOPIC_ID"))
			$arFields["TOPIC_ID"]=intVal($arFields["TOPIC_ID"]);
		if (is_set($arFields, "SOCNET_GROUP_ID") || $ACTION=="ADD")
			$arFields["SOCNET_GROUP_ID"] = (intVal($arFields["SOCNET_GROUP_ID"]) > 0 ? intVal($arFields["SOCNET_GROUP_ID"]) : false);
		if (is_set($arFields, "OWNER_ID") || $ACTION=="ADD")
			$arFields["OWNER_ID"] = (intVal($arFields["OWNER_ID"]) > 0 ? intVal($arFields["OWNER_ID"]) : false);
		return True;
	}

	
	/**
	 * <p>Создает новую тему с параметрами, указанными в массиве <i>arFields</i>. Возвращает код созданной темы.</p>
	 *
	 *
	 *
	 *
	 * @param array $arFields  Массив вида Array(<i>field1</i>=&gt;<i>value1</i>[, <i>field2</i>=&gt;<i>value2</i> [, ..]]), где
	 * <br><br><i>field</i> - название поля;<br><i>value</i> - значение поля.<br><br> Поля
	 * перечислены в <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">списке полей
	 * темы</a>. Обязательные поля должны быть заполнены.
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">Поля темы</a> </li>
	 * <li>Перед добавлением темы следует проверить возможность
	 * добавления методом <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuseraddtopic.php">CForumTopic::CanUserAddTopic</a>
	 * </li> <li>Для добавления и изменения сообщения и темы рекомендуется
	 * пользоваться высокоуровневой функцией <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/functions/forumaddmessage.php">ForumAddMessage</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/add.php
	 * @author Bitrix
	 */
	public static function Add($arFields)
	{
		global $DB;

		$arFields["VIEWS"] = 0;
		$arFields["POSTS"] = 0;
		$arFields["STATE"] = (in_array($arFields["STATE"], array("Y", "N", "L")) ? $arFields["STATE"] : "Y");

		if (!CForumTopic::CheckFields("ADD", $arFields)):
			return false;
		endif;
/***************** Event onBeforeTopicAdd **************************/
		$events = GetModuleEvents("forum", "onBeforeTopicAdd");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;

		foreach (array("START_DATE", "LAST_POST_DATE") as $key)
		{
			if (!is_set($arFields, $key) || empty($arFields[$key]))
			{
				$arFields[$key] = $DB->GetNowFunction();
			}
			elseif (($DB->type == "MSSQL" && strPos($arFields[$key], "convert (datetime") === false) ||
					($DB->type == "ORACLE" && strPos($arFields[$key], "TO_DATE") === false) ||
					$DB->type == "MYSQL")
			{

				$arFields[$key] = $DB->CharToDateFunction(str_replace(array("'", '"'), "", $arFields[$key]));
			}
		}

		if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
		{
			$arr = array(
				"TITLE"=>CFilterUnquotableWords::Filter($arFields["TITLE"]),
				"DESCRIPTION" => CFilterUnquotableWords::Filter($arFields["DESCRIPTION"]),
				"LAST_POSTER_NAME" => CFilterUnquotableWords::Filter($arFields["LAST_POSTER_NAME"]),
				"USER_START_NAME" => CFilterUnquotableWords::Filter($arFields["USER_START_NAME"]),
				"TAGS" => CFilterUnquotableWords::Filter($arFields["TAGS"]));

			foreach ($arr as $key => $val):
				if (empty($val) && !empty($arFields[$key])):
					$arr[$key] = "*";
				endif;
			endforeach;
			$arr["ABS_LAST_POSTER_NAME"] = $arr["LAST_POSTER_NAME"];
			$arFields["HTML"] = serialize($arr);
		}

		$Fields = array(
			"TITLE" => "'".$DB->ForSQL($arFields["TITLE"], 255)."'",
			"USER_START_NAME" => "'".$DB->ForSQL($arFields["USER_START_NAME"], 255)."'",
			"FORUM_ID" => intVal($arFields["FORUM_ID"]),
			"LAST_POSTER_NAME" => "'".$DB->ForSQL($arFields["LAST_POSTER_NAME"], 255)."'",
			"ABS_LAST_POSTER_NAME" => "'".$DB->ForSQL($arFields["LAST_POSTER_NAME"], 255)."'",
			"TAGS" => "'".$DB->ForSQL($arFields["TAGS"], 255)."'",
			"HTML" => "'".$DB->ForSQL($arFields["HTML"])."'",

			"STATE" => "'".$arFields["STATE"]."'",
			"APPROVED" => "'".$arFields["APPROVED"]."'",

			"START_DATE" => $arFields["START_DATE"],
			"LAST_POST_DATE" => $arFields["LAST_POST_DATE"],
			"ABS_LAST_POST_DATE" => $arFields["LAST_POST_DATE"],

			"SORT" => intVal($arFields["SORT"]),
			"POSTS" => intVal($arFields["POSTS"]),
			"VIEWS" => intVal($arFields["VIEWS"]),
			"TOPIC_ID" => intVal($arFields["TOPIC_ID"]));
		if (strLen(trim($arFields["DESCRIPTION"])) > 0)
			$Fields["DESCRIPTION"] = "'".$DB->ForSQL($arFields["DESCRIPTION"], 255)."'";
		if (strLen(trim($arFields["XML_ID"])) > 0)
			$Fields["XML_ID"] = "'".$DB->ForSQL($arFields["XML_ID"], 255)."'";
		if (intVal($arFields["USER_START_ID"]) > 0)
			$Fields["USER_START_ID"] = intVal($arFields["USER_START_ID"]);
		if (intVal($arFields["ICON_ID"]) > 0)
			$Fields["ICON_ID"] = intVal($arFields["ICON_ID"]);
		if (intVal($arFields["LAST_MESSAGE_ID"]) > 0)
			$Fields["LAST_MESSAGE_ID"] = intVal($arFields["LAST_MESSAGE_ID"]);
		if ($arFields["LAST_POSTER_ID"])
			$Fields["LAST_POSTER_ID"] = intVal($arFields["LAST_POSTER_ID"]);
		if ($arFields["SOCNET_GROUP_ID"])
			$Fields["SOCNET_GROUP_ID"] = intVal($arFields["SOCNET_GROUP_ID"]);
		if ($arFields["OWNER_ID"])
			$Fields["OWNER_ID"] = intVal($arFields["OWNER_ID"]);

		$ID = $DB->Insert("b_forum_topic", $Fields, "File: ".__FILE__."<br>Line: ".__LINE__);
/***************** Event onAfterTopicAdd ***************************/
		$events = GetModuleEvents("forum", "onAfterTopicAdd");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arFields));
/***************** /Event ******************************************/
		return $ID;
	}

	
	/**
	 * <p>Изменяет параметры существующей темы с кодом <i>ID</i> на параметры, указанные в массиве <i>arFields</i>. Возвращает код изменяемой темы.</p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код темы, параметры которой необходимо изменить.
	 *
	 *
	 *
	 * @param array $arFields  Массив вида Array(<i>field1</i>=&gt;<i>value1</i>[, <i>field2</i>=&gt;<i>value2</i> [, ..]]), где
	 * <br><br><i>field</i> - название поля;<br><i>value</i> - значение поля.<br><br> Поля
	 * перечислены в <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">списке полей
	 * темы</a>.
	 *
	 *
	 *
	 * @param bool $skip_counts  Если этот параметр установлен в значение true, то при изменении
	 * темы не будут автоматически обсчитаны статистические данные. Это
	 * ускоряет работу функции, но создает логические ошибки в данных.
	 * Необязательный. По умолчанию равен False.
	 *
	 *
	 *
	 * @return int 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">Поля темы</a> </li>
	 * <li>Перед изменением темы следует проверить возможность изменения
	 * методом <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuserupdatetopic.php">CanUserUpdateTopic</a> </li>
	 * <li>Для добавления и изменения сообщения и темы можно
	 * воспользоваться высокоуровневой функцией <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/functions/forumaddmessage.php">ForumAddMessage</a> </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields, $skip_counts = False)
	{
		global $DB;
		$ID = intVal($ID);
		$arFields1 = array();
		$arFieldsForFilter = array();
		$bNeedFilter = false;

		if ($ID <= 0 || !CForumTopic::CheckFields("UPDATE", $arFields))
			return false;
/***************** Event onBeforeTopicUpdate **************************/
		$events = GetModuleEvents("forum", "onBeforeTopicUpdate");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, &$arFields)) === false)
				return false;
		}
/***************** /Event ******************************************/
		if (empty($arFields))
			return false;
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1)=="=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}
		if (!$skip_counts && is_set($arFields, "FORUM_ID") ||
			COption::GetOptionString("forum", "FILTER", "Y") == "Y" ||
			(is_set($arFields, "TITLE") || is_set($arFields, "TAGS")) && IsModuleInstalled("search"))
		{
			$arTopic_prev = CForumTopic::GetByID($ID, array("NoFilter" => true));
		}
		// Fields "HTML".
		if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
		{
			$arFieldsForFilter = array(
				"TITLE" => (is_set($arFields, "TITLE") ? $arFields["TITLE"] : $arTopic_prev["TITLE"]),
				"TAGS" => (is_set($arFields, "TAGS") ? $arFields["TAGS"] : $arTopic_prev["TAGS"]),
				"DESCRIPTION" => (is_set($arFields, "DESCRIPTION") ? $arFields["DESCRIPTION"] : $arTopic_prev["DESCRIPTION"]),
				"LAST_POSTER_NAME" => (is_set($arFields, "LAST_POSTER_NAME") ? $arFields["LAST_POSTER_NAME"] : $arTopic_prev["LAST_POSTER_NAME"]),
				"ABS_LAST_POSTER_NAME" => (is_set($arFields, "ABS_LAST_POSTER_NAME") ? $arFields["ABS_LAST_POSTER_NAME"] : $arTopic_prev["ABS_LAST_POSTER_NAME"]),
				"USER_START_NAME" => (is_set($arFields, "USER_START_NAME") ? $arFields["USER_START_NAME"] : $arTopic_prev["USER_START_NAME"]));

			$bNeedFilter = false;
			foreach ($arFieldsForFilter as $key => $val):
				if (is_set($arFields, $key)):
					$bNeedFilter = true;
					break;
				endif;
			endforeach;
			if ($bNeedFilter)
			{
				foreach ($arFieldsForFilter as $key => $val)
				{
					$res = CFilterUnquotableWords::Filter($val);
					if (empty($res) && !empty($val))
						$res = "*";
					$arFieldsForFilter[$key] = $res;
				}
				$arFields["HTML"] = serialize($arFieldsForFilter);
			}
		}

		$strUpdate = $DB->PrepareUpdate("b_forum_topic", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strLen($strUpdate)>0) $strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}
		if (!empty($strUpdate))
		{
			$strSql = "UPDATE b_forum_topic SET ".$strUpdate." WHERE ID = ".$ID;
			$DB->QueryBind($strSql, array("HTML"=>$arFields["HTML"]), false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		}

		$res = array_merge($arFields1, $arFields);
		if (count($res) == 1 && !empty($res["VIEWS"]))
		{
			if (intVal($res["VIEWS"]) <= 0)
			{
				$GLOBALS["FORUM_CACHE"]["TOPIC"][$ID]["VIEWS"]++;
				$GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID]["VIEWS"]++;
			}
			else
			{
				$GLOBALS["FORUM_CACHE"]["TOPIC"][$ID]["VIEWS"] = intVal($res["VIEWS"]);
				$GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID]["VIEWS"] = intVal($res["VIEWS"]);
			}
		}
		else
		{
			unset($GLOBALS["FORUM_CACHE"]["FORUM"][$arTopic_prev["FORUM_ID"]]);
			unset($GLOBALS["FORUM_CACHE"]["TOPIC"][$ID]);
			unset($GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID]);
			if (intVal($arFields1["FORUM_ID"]) > 0)
				unset($GLOBALS["FORUM_CACHE"]["FORUM"][intVal($arFields1["FORUM_ID"])]);
			if (intVal($arFields["FORUM_ID"]) > 0)
				unset($GLOBALS["FORUM_CACHE"]["FORUM"][intVal($arFields["FORUM_ID"])]);
		}
		if (count($res) == 1 && !empty($res["VIEWS"]))
			return $ID;
		if (is_set($arFields, "FORUM_ID") && intVal($arFields["FORUM_ID"]) != intVal($arTopic_prev["FORUM_ID"])):
			$arFiles = array();
			$db_res = CForumFiles::GetList(array(), array("TOPIC_ID" => $ID));
			if ($db_res && $res = $db_res->Fetch()):
				do
				{
					$arFiles[] = $res["ID"];
				} while ($res = $db_res->Fetch());
			endif;
			CForumFiles::UpdateByID($arFiles, array("FORUM_ID" => $arFields["FORUM_ID"]));
		endif;
/***************** Event onAfterTopicUpdate ************************/
		$events = GetModuleEvents("forum", "onAfterTopicUpdate");
		if ($events->nSelectedCount > 0)
			$arTopicFields = CForumTopic::GetByID($ID, array("NoFilter" => true));
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID, $arTopicFields));
/***************** /Event ******************************************/
		// recalc statistic if topic removed from another forum
		if (!$skip_counts && is_set($arFields, "FORUM_ID") && intVal($arFields["FORUM_ID"]) != intVal($arTopic_prev["FORUM_ID"]))
		{
			$DB->StartTransaction();
				$db_res = CForumMessage::GetList(array(), array("TOPIC_ID" => $ID));
				while ($ar_res = $db_res->Fetch())
				{
					CForumMessage::Update($ar_res["ID"], array("FORUM_ID" => $arFields["FORUM_ID"]), true);
				}
				$db_res = CForumSubscribe::GetList(array(), array("TOPIC_ID" => $ID));
				while ($ar_res = $db_res->Fetch())
				{
					CForumSubscribe::Update($ar_res["ID"], array("FORUM_ID" => $arFields["FORUM_ID"]));
				}
			$DB->Commit();
			CForumNew::SetStat($arFields["FORUM_ID"]);
			CForumNew::SetStat($arTopic_prev["FORUM_ID"]);
		}
		if (IsModuleInstalled("search"))
		{
			$bNeedDeleteIndex = false;
			if (is_set($arFields, "FORUM_ID") && intVal($arFields["FORUM_ID"]) != intVal($arTopic_prev["FORUM_ID"]))
			{
				$res = CForumNew::GetByID($arFields["FORUM_ID"]);
				$bNeedDeleteIndex = ($res["INDEXATION"] != "Y" ? true : false);
			}
			if ($bNeedDeleteIndex)
			{
				CModule::IncludeModule("search");
				CSearch::DeleteIndex("forum", false, $arTopic_prev["FORUM_ID"], $ID);
			}
			elseif (is_set($arFields, "TITLE") || is_set($arFields, "TAGS") || is_set($arFields, "DESCRIPTION"))
			{
				$arReindex = array();
				$arFields["FORUM_ID"] = (is_set($arFields, "FORUM_ID") ? $arFields["FORUM_ID"] : $arTopic_prev["FORUM_ID"]);

				if (is_set($arFields, "TITLE") && trim($arTopic_prev["TITLE"]) != trim($arFields["TITLE"])):
					$arReindex["TITLE"] = ($bNeedFilter ? $arFieldsForFilter["TITLE"] : $arFields["TITLE"]);
				endif;
				if (is_set($arFields, "DESCRIPTION") && trim($arTopic_prev["DESCRIPTION"]) != trim($arFields["DESCRIPTION"])):
					$title = (is_set($arReindex, "TITLE") ? $arReindex["TITLE"] : ($bNeedFilter ? $arFieldsForFilter["TITLE"] : $arTopic_prev["TITLE"]));
					$description = ($bNeedFilter ? $arFieldsForFilter["DESCRIPTION"] : $arFields["DESCRIPTION"]);
					$arReindex["TITLE_FOR_FIRST_POST"] = $title.(!empty($description) ? ", ".$description : "");
				endif;
				if (is_set($arFields, "TAGS") && trim($arTopic_prev["TAGS"]) != trim($arFields["TAGS"])):
					$arReindex["TAGS"] = ($bNeedFilter ? $arFieldsForFilter["TAGS"] : $arFields["TAGS"]);
				endif;

				if (!empty($arReindex))
				{
					CModule::IncludeModule("search");
					if (is_set($arReindex, "TITLE"))
					{
						$db_res = CForumMessage::GetList(array("ID" => "ASC"), array("FORUM_ID" => $arFields["FORUM_ID"], "TOPIC_ID" => $ID, "NEW_TOPIC" => "Y"));
						if ($db_res)
						{
							while ($arMessage = $db_res->Fetch())
							{
								CForumMessage::Reindex($arMessage['ID'], array_merge($arMessage, $arReindex));
							}
						}
					}
					if (is_set($arReindex, "TITLE_FOR_FIRST_POST") || is_set($arReindex, "TAGS"))
					{
						unset($arReindex["TITLE"]);
						if (is_set($arReindex, "TITLE_FOR_FIRST_POST"))
						{
							$arReindex["TITLE"] = $arReindex["TITLE_FOR_FIRST_POST"];
							unset($arReindex["TITLE_FOR_FIRST_POST"]);
						}
						$db_res = CForumMessage::GetList(array("ID" => "ASC"), array("TOPIC_ID" => $ID, "NEW_TOPIC" => "Y"));
						if ($db_res && $arMessage = $db_res->Fetch())
							CForumMessage::Reindex($arMessage['ID'], array_merge($arMessage, $arReindex));
					}
				}
			}
		}
		return $ID;
	}

	
	/**
	 * <p>Функция переносит тему с кодом <b>TID</b> в форум с кодом <b>FID</b>. </p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	 *
	 *
	 *
	 *
	 * @param int $TID  Код темы, которую необходимо перенести.
	 *
	 *
	 *
	 * @param int $FID  Код форума, в который необходимо перенести тему.
	 *
	 *
	 *
	 * @return bool <p>Функция возвращает True в случае успешного переноса и False - в
	 * противном случае </p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/cforumtopic.movetopic2forum.php
	 * @author Bitrix
	 */
	public static function MoveTopic2Forum($TID, $FID, $leaveLink = "N")
	{
		global $DB;
		$FID = intVal($FID);
		$arForum = CForumNew::GetByID($FID);
		$arTopics = (is_array($TID) ? $TID : (intVal($TID) > 0 ? array($TID) : array()));
		$leaveLink = (strToUpper($leaveLink) == "Y" ? "Y" : "N");
		$arMsg = array();
		$arForums = array();

		if (empty($arForum))
		{
			$arMsg[] = array(
				"id" => "FORUM_NOT_EXIST",
				"text" =>  GetMessage("F_ERR_FORUM_NOT_EXIST", array("#FORUM_ID#" => $FID)));
		}
		if (empty($arTopics))
		{
			$arMsg[] = array(
				"id" => "TOPIC_EMPTY",
				"text" =>  GetMessage("F_ERR_EMPTY_TO_MOVE"));
		}

		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
			return false;
		}

		$arTopicsCopy = $arTopics;
		$arTopics = array();
		foreach ($arTopicsCopy as $res)
		{
			$arTopics[intVal($res)] = array("ID" => intVal($res));
		}

		$db_res = CForumTopic::GetList(array(), array("@ID" => implode(", ", array_keys($arTopics))));
		if ($db_res && ($res = $db_res->Fetch()))
		{
			do
			{
				if (intVal($res["FORUM_ID"]) == $FID)
				{
					$arMsg[] = array(
						"id" => "FORUM_ID_IDENTICAL",
						"text" => GetMessage("F_ERR_THIS_TOPIC_IS_NOT_MOVE",
							array("#TITLE#" => $res["TITLE"], "#ID#" => $res["ID"])));
					continue;
				}

//				$DB->StartTransaction();

				if ($leaveLink != "N")
				{
					CForumTopic::Add(
						array(
							"TITLE" => $res["TITLE"],
							"DESCRIPTION" => $res["DESCRIPTION"],
							"STATE" => "L",
							"USER_START_NAME" => $res["USER_START_NAME"],
							"START_DATE" => $res["START_DATE"],
							"ICON_ID" => $res["ICON_ID"],
							"POSTS" => "0",
							"VIEWS" => "0",
							"FORUM_ID" => $res["FORUM_ID"],
							"TOPIC_ID" => $res["ID"],
							"APPROVED" => $res["APPROVED"],
							"SORT" => $res["SORT"],
							"LAST_POSTER_NAME" => $res["LAST_POSTER_NAME"],
							"LAST_POST_DATE" => $res["LAST_POST_DATE"],
							"HTML" => $res["HTML"],
							"USER_START_ID" => $res["USER_START_ID"],
							"SOCNET_GROUP_ID" => $res["SOCNET_GROUP_ID"],
							"OWNER_ID" => $res["OWNER_ID"]));
				}

				CForumTopic::Update($res["ID"], array("FORUM_ID" => $FID), true);
				// move message
				$strSql = "UPDATE b_forum_message SET FORUM_ID=".$FID.", POST_MESSAGE_HTML='' WHERE TOPIC_ID=".$res["ID"];
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
				// move subscribe
				$strSql = "UPDATE b_forum_subscribe SET FORUM_ID=".intVal($FID)." WHERE TOPIC_ID=".$res["ID"];
				$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

				$arForums[$res["FORUM_ID"]] = $res["FORUM_ID"];
				unset($GLOBALS["FORUM_CACHE"]["TOPIC"][$res["ID"]]);
				unset($GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$res["ID"]]);
				$arTopics[intVal($res["ID"])] = $res;
//				$DB->Commit();

				CForumCacheManager::ClearTag("F", $res["ID"]);

				$res_log["DESCRIPTION"] = str_replace(array("#TOPIC_TITLE#", "#TOPIC_ID#", "#FORUM_TITLE#", "#FORUM_ID#"),
					array($res["TITLE"], $res["ID"], $arForum["NAME"], $arForum["ID"]),
					($leaveLink != "N" ? GetMessage("F_LOGS_MOVE_TOPIC_WITH_LINK") : GetMessage("F_LOGS_MOVE_TOPIC")));
				$res_log["FORUM_ID"] = $arForum["ID"];
				$res_log["TOPIC_ID"] = $res["ID"];
				$res_log["TITLE"] = $res["TITLE"];
				$res_log["FORUM_TITLE"] = $arForum["NAME"];
				CForumEventLog::Log("topic", "move", $res["ID"], serialize($res_log));
			} while ($res = $db_res->Fetch());
		}
/***************** Cleaning cache **********************************/
		unset($GLOBALS["FORUM_CACHE"]["FORUM"][$FID]);
		if(CACHED_b_forum !== false)
			$GLOBALS["CACHE_MANAGER"]->CleanDir("b_forum");
/***************** Cleaning cache/**********************************/
		CForumNew::SetStat($FID);
		foreach ($arForums as $key)
			CForumNew::SetStat($key);
		if (!empty($arMsg))
		{
			$e = new CAdminException($arMsg);
			$GLOBALS["APPLICATION"]->ThrowException($e);
		}
		else
		{

			CForumCacheManager::ClearTag("F", $FID);
			if ($leaveLink != "Y")
			{
				foreach($arTopics as $key => $res)
					CForumCacheManager::ClearTag("F", $res["FORUM_ID"]);
			}
		}
		return true;
	}

	
	/**
	 * <p>Удаляет тему с кодом <i>ID</i>.</p> <p><b>Примечание</b>. Метод использует внутреннюю транзакцию. Если у вас используется <b>MySQL</b> и <b>InnoDB</b>, и ранее была открыта транзакция, то ее необходимо закрыть до подключения метода.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код темы, которую необходимо удалить.
	 *
	 *
	 *
	 * @return bool 
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li>Перед удалением темы следует проверить возможность удаления
	 * методом <a
	 * href="http://dev.1c-bitrix.ruapi_help/forum/developer/cforumtopic/canuserdeletetopic.php">CForumTopic::CanUserDeleteTopic</a>
	 * </li></ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;
		$ID = intVal($ID);
		$arTopic = CForumTopic::GetByID($ID);
		if (empty($arTopic)):
			return false;
		endif;
/***************** Event onBeforeTopicDelete ***********************/
		$events = GetModuleEvents("forum", "onBeforeTopicDelete");
		while ($arEvent = $events->Fetch())
		{
			if (ExecuteModuleEventEx($arEvent, array(&$ID, $arTopic)) === false)
				return false;
		}
/***************** /Event ******************************************/
		$arAuthor = array(); $arVotes = array();
		$db_res = CForumMessage::GetList(array("ID" => "ASC"), array("TOPIC_ID" => $ID));
		while ($res = $db_res->Fetch())
		{
			if (intVal($res["AUTHOR_ID"]) > 0)
				$arAuthor[intVal($res["AUTHOR_ID"])] = $res["AUTHOR_ID"];
			if ($res["PARAM1"] == "VT" && intVal($res["PARAM2"]) > 0)
				$arVotes[] = intVal($res["PARAM2"]);
		}
		if (!empty($arVotes) && IsModuleInstalled("vote") && CModule::IncludeModule("vote")):
			foreach ($arVotes as $res) { CVote::Delete($res); }
		endif;

//		$DB->StartTransaction();
			CForumFiles::Delete(array("TOPIC_ID" => $ID), array("DELETE_TOPIC_FILE" => "Y"));
			$DB->Query("DELETE FROM b_forum_subscribe WHERE TOPIC_ID = ".$ID."");
			$DB->Query("DELETE FROM b_forum_message WHERE TOPIC_ID = ".$ID."");
			$DB->Query("DELETE FROM b_forum_user_topic WHERE TOPIC_ID = ".$ID."");
			$DB->Query("DELETE FROM b_forum_topic WHERE ID = ".$ID."");
			$DB->Query("DELETE FROM b_forum_topic WHERE TOPIC_ID = ".$ID."");
			$DB->Query("DELETE FROM b_forum_stat WHERE TOPIC_ID = ".$ID."");
//		$DB->Commit();

		unset($GLOBALS["FORUM_CACHE"]["TOPIC"][$ID]);
		unset($GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID]);
		foreach ($arAuthor as $key)
			CForumUser::SetStat($key);
		CForumNew::SetStat($arTopic["FORUM_ID"]);

		if (IsModuleInstalled("search") && CModule::IncludeModule("search"))
		{
			CSearch::DeleteIndex("forum", false, $arTopic["FORUM_ID"], $ID);
		}
/***************** Event onAfterTopicDelete ************************/
		$events = GetModuleEvents("forum", "onAfterTopicDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array(&$ID, $arTopic));
/***************** /Event ******************************************/
		return true;
	}

	
	/**
	 * <p>Возвращает массив параметров темы по ее коду <i>ID</i>. Результаты вызова функции кешируются, поэтому повторные вызовы метода для одной и той же темы не требуют дополнительного обращения к базе данных (при условии, что кеш не сбросился в результате изменения параметров темы).</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код темы.
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $arTopic = CForumTopic::GetByID($TID);
	 * if ($arTopic)
	 * {
	 *   $FID = IntVal($arTopic["FORUM_ID"]);
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">Поля темы</a> </li></ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/getbyid.php
	 * @author Bitrix
	 */
	public static function GetByID($ID, $arAddParams = array())
	{
		global $DB;

		if (strlen($ID) < 1) return False;

		$NoFilter = ($arAddParams["NoFilter"] == true || COption::GetOptionString("forum", "FILTER", "Y") != "Y" ? true : false);

		if ($NoFilter && isset($GLOBALS["FORUM_CACHE"]["TOPIC"][$ID]) && is_array($GLOBALS["FORUM_CACHE"]["TOPIC"][$ID]) && is_set($GLOBALS["FORUM_CACHE"]["TOPIC"][$ID], "ID"))
		{
			return $GLOBALS["FORUM_CACHE"]["TOPIC"][$ID];
		}
		elseif (!$NoFilter && isset($GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID]) && is_array($GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID]) && is_set($GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID], "ID"))
		{
			return $GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID];
		}
		else
		{
			$strSql =
				"SELECT FT.*,
					".$DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE,
					".$DB->DateToCharFunction("FT.LAST_POST_DATE", "FULL")." as LAST_POST_DATE
				FROM b_forum_topic FT ";

			if (intval($ID) > 0 || $ID === 0)
				$strSql .= "WHERE FT.ID = ".intval($ID);
			else
				$strSql .= "WHERE FT.XML_ID = '".$DB->ForSql($ID)."'";

			$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($db_res && $res = $db_res->Fetch())
			{
				$GLOBALS["FORUM_CACHE"]["TOPIC"][$ID] = $res;
				if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
				{
					$db_res_filter = new CDBResult;
					$db_res_filter->InitFromArray(array($res));
					$db_res_filter = new _CTopicDBResult($db_res_filter);
					if ($res_filter = $db_res_filter->Fetch())
						$GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][$ID] = $res_filter;
				}
				if (!$NoFilter)
					$res = $res_filter;
				return $res;
			}
		}
		return False;
	}

	
	/**
	 * <p>Возвращает массив параметров темы, а так же сопутствующие параметры, по ее коду <i>ID</i>.</p>
	 *
	 *
	 *
	 *
	 * @param int $ID  Код темы.
	 *
	 *
	 *
	 * @return array 
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Распечатаем на экран все возвращаемые параметры темы
	 * $arTopic = CForumTopic::GetByIDEx($TID);
	 * if ($arTopic)
	 * {
	 *   echo "&lt;pre&gt;";
	 *   print_r($arTopic);
	 *   echo "&lt;/pre&gt;";
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul><li> <a href="http://dev.1c-bitrix.ruapi_help/forum/fields.php#cforumtopic">Поля темы</a> </li></ul><a
	 * name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/getbyidex.php
	 * @author Bitrix
	 */
	public static function GetByIDEx($ID, $arAddParams = array())
	{
		global $DB;
		$ID = intVal($ID);
		if ($ID <= 0):
			return false;
		endif;

		$arAddParams = (is_array($arAddParams) ? $arAddParams : array($arAddParams));
		$arAddParams["GET_FORUM_INFO"] = ($arAddParams["GET_FORUM_INFO"] == "Y" ? "Y" : "N");
		$arSQL = array("select" => array(), "join" => array());
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
		}
		if ($arAddParams["GET_FORUM_INFO"] == "Y")
		{
			$arSQL["select"][] = CForumNew::GetSelectFields(array("sPrefix" => "F_", "sReturnResult" => "string"));
			$arSQL["join"][] =  "INNER JOIN b_forum F ON (FT.FORUM_ID = F.ID)";
		}
		$arSQL["select"] = (!empty($arSQL["select"]) ? ",\n\t".implode(",\n\t", $arSQL["select"]) : "");
		$arSQL["join"] = (!empty($arSQL["join"]) ? "\n\t".implode("\n", $arSQL["join"]) : "");

		$strSql =
			"SELECT FT.*,\n".
			"	".$DB->DateToCharFunction("FT.START_DATE", "FULL")." as START_DATE, \n".
			"	".$DB->DateToCharFunction("FT.LAST_POST_DATE", "FULL")." as LAST_POST_DATE, \n".
			"	FS.IMAGE, '' as IMAGE_DESCR".$arSQL["select"]."\n".
			"FROM b_forum_topic FT \n".
			"	LEFT JOIN b_forum_smile FS ON (FT.ICON_ID = FS.ID)".$arSQL["join"]."\n".
			"WHERE FT.ID = ".$ID;
		$db_res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
			$db_res = new _CTopicDBResult($db_res);
		if ($res = $db_res->Fetch())
		{
			if (is_array($res))
			{
				// Cache topic data for hits
				if ($arAddParams["GET_FORUM_INFO"] == "Y")
				{
					$res["TOPIC_INFO"] = array();
					$res["FORUM_INFO"] = array();
					foreach ($res as $key => $val)
					{
						if (substr($key, 0, 2) == "F_")
							$res["FORUM_INFO"][substr($key, 2)] = $val;
						else
							$res["TOPIC_INFO"][$key] = $val;
					}
					if (!empty($res["TOPIC_INFO"]))
					{
						$GLOBALS["FORUM_CACHE"]["TOPIC"][intVal($res["TOPIC_INFO"]["ID"])] = $res["TOPIC_INFO"];
						if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
						{
							$db_res_filter = new CDBResult;
							$db_res_filter->InitFromArray(array($res["TOPIC_INFO"]));
							$db_res_filter = new _CTopicDBResult($db_res_filter);
							if ($res_filter = $db_res_filter->Fetch())
								$GLOBALS["FORUM_CACHE"]["TOPIC_FILTER"][intVal($res["TOPIC_INFO"]["ID"])] = $res_filter;
						}
					}
					if (!empty($res["FORUM_INFO"]))
					{
						$GLOBALS["FORUM_CACHE"]["FORUM"][intVal($res["FORUM_INFO"]["ID"])] = $res["FORUM_INFO"];
					}
				}
			}
			return $res;
		}
		return false;
	}

	
	/**
	 * <p>Функция для темы с кодом TID возвращает коды предыдущей и следующей тем (соседних тем) форума в сортировке по дате последнего сообщения (стандартная сортировка) с учётом прав на доступ групп пользователей arUserGroups.</p>
	 *
	 *
	 *
	 *
	 * @param int $TID  Код темы, соседей которой необходимо получить.
	 *
	 *
	 *
	 * @param array $arUserGroups  Массив кодов групп пользователей для учета прав на доступ. Обычно
	 * годы групп, которым принадлежит текущий пользователь.
	 *
	 *
	 *
	 * @return array <p>Функция возвращает массив из двух элементов, первым из которых
	 * является код предадущей темы, а вторым - следующей. Если
	 * какого-либо из соседей для данной темы не существует, то вместо
	 * кода соответствующего соседа возвращается значение 0. </p><a
	 * name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * // Выведем ссылки на соседние темы для темы TID с учётом прав 
	 * // текущего пользователя
	 * 
	 * list($iPREV_TOPIC, $iNEXT_TOPIC) = 
	 *     CForumTopic::GetNeighboringTopics($TID, 
	 *                                       $USER-&gt;GetUserGroupArray());
	 * 
	 * if ($iPREV_TOPIC &gt; 0)
	 *    echo "&lt;a href=\"read.php?FID=".$FID."&amp;TID=".$iPREV_TOPIC."\"&gt;";
	 * echo "Предыдущая тема";
	 * 
	 * if ($iPREV_TOPIC &gt; 0)
	 *    echo "&lt;/a&gt;";
	 * echo " | ";
	 * 
	 * if ($iNEXT_TOPIC &gt; 0)
	 *    echo "&lt;a href=\"read.php?FID=".$FID."&amp;TID=".$iNEXT_TOPIC."\"&gt;";
	 * echo "Следующая тема";
	 * 
	 * if ($iNEXT_TOPIC &gt; 0)
	 *    echo "&lt;/a&gt;";
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/forum/developer/cforumtopic/cforumtopic.getneighboringtopics.php
	 * @author Bitrix
	 */
	public static function GetNeighboringTopics($TID, $arUserGroups) // out-of-date function
	{
		$TID = intVal($TID);
		$arTopic = CForumTopic::GetByID($TID);
		if (!$arTopic) return False;

		//-- PREV_TOPIC
		$arFilter = array(
			"FORUM_ID" => $arTopic["FORUM_ID"],
			"<LAST_POST_DATE" => $arTopic["LAST_POST_DATE"]
			);
		if (CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups)<"Q")
			$arFilter["APPROVED"] = "Y";

		$db_res = CForumTopic::GetList(array("LAST_POST_DATE"=>"DESC"), $arFilter, false, 1);
		$PREV_TOPIC = 0;
		if ($ar_res = $db_res->Fetch()) $PREV_TOPIC = $ar_res["ID"];

		//-- NEXT_TOPIC
		$arFilter = array(
			"FORUM_ID" => $arTopic["FORUM_ID"],
			">LAST_POST_DATE" => $arTopic["LAST_POST_DATE"]
			);
		if (CForumNew::GetUserPermission($arTopic["FORUM_ID"], $arUserGroups)<"Q")
			$arFilter["APPROVED"] = "Y";

		$db_res = CForumTopic::GetList(array("LAST_POST_DATE"=>"ASC"), $arFilter, false, 1);
		$NEXT_TOPIC = 0;
		if ($ar_res = $db_res->Fetch()) $NEXT_TOPIC = $ar_res["ID"];

		return array($PREV_TOPIC, $NEXT_TOPIC);
	}

	public static function GetSelectFields($arAddParams = array())
	{
		global $DB;
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array());
		$arAddParams["sPrefix"] = $DB->ForSql(empty($arAddParams["sPrefix"]) ? "FT." : $arAddParams["sPrefix"]);
		$arAddParams["sTablePrefix"] = $DB->ForSql(empty($arAddParams["sTablePrefix"]) ? "FT." : $arAddParams["sTablePrefix"]);
		$arAddParams["sReturnResult"] = ($arAddParams["sReturnResult"] == "string" ? "string" : "array");

		$res = array(
			$arAddParams["sPrefix"]."ID" => $arAddParams["sTablePrefix"]."ID",
			$arAddParams["sPrefix"]."TITLE" => $arAddParams["sTablePrefix"]."TITLE",
			$arAddParams["sPrefix"]."TAGS" => $arAddParams["sTablePrefix"]."TAGS",
			$arAddParams["sPrefix"]."DESCRIPTION" => $arAddParams["sTablePrefix"]."DESCRIPTION",
			$arAddParams["sPrefix"]."VIEWS" => $arAddParams["sTablePrefix"]."VIEWS",
			$arAddParams["sPrefix"]."LAST_POSTER_ID" => $arAddParams["sTablePrefix"]."LAST_POSTER_ID",
			($arAddParams["sPrefix"] == $arAddParams["sTablePrefix"] ? "" : $arAddParams["sPrefix"]).
				"START_DATE" => $DB->DateToCharFunction($arAddParams["sTablePrefix"]."START_DATE", "FULL"),
			$arAddParams["sPrefix"]."USER_START_NAME" => $arAddParams["sTablePrefix"]."USER_START_NAME",
			$arAddParams["sPrefix"]."USER_START_ID" => $arAddParams["sTablePrefix"]."USER_START_ID",
			$arAddParams["sPrefix"]."POSTS" => $arAddParams["sTablePrefix"]."POSTS",
			$arAddParams["sPrefix"]."LAST_POSTER_NAME" => $arAddParams["sTablePrefix"]."LAST_POSTER_NAME",
			($arAddParams["sPrefix"] == $arAddParams["sTablePrefix"] ? "" : $arAddParams["sPrefix"]).
				"LAST_POST_DATE" => $DB->DateToCharFunction($arAddParams["sTablePrefix"]."LAST_POST_DATE", "FULL"),
			$arAddParams["sPrefix"]."LAST_MESSAGE_ID" => $arAddParams["sTablePrefix"]."LAST_MESSAGE_ID",
			$arAddParams["sPrefix"]."APPROVED" => $arAddParams["sTablePrefix"]."APPROVED",
			$arAddParams["sPrefix"]."STATE" => $arAddParams["sTablePrefix"]."STATE",
			$arAddParams["sPrefix"]."FORUM_ID" => $arAddParams["sTablePrefix"]."FORUM_ID",
			$arAddParams["sPrefix"]."TOPIC_ID" => $arAddParams["sTablePrefix"]."TOPIC_ID",
			$arAddParams["sPrefix"]."ICON_ID" => $arAddParams["sTablePrefix"]."ICON_ID",
			$arAddParams["sPrefix"]."SORT" => $arAddParams["sTablePrefix"]."SORT",
			$arAddParams["sPrefix"]."SOCNET_GROUP_ID" => $arAddParams["sTablePrefix"]."SOCNET_GROUP_ID",
			$arAddParams["sPrefix"]."OWNER_ID" => $arAddParams["sTablePrefix"]."OWNER_ID");


		if ($arAddParams["sReturnResult"] == "string")
		{
			$arRes = array();
			foreach ($res as $key => $val)
			{
				$arRes[] = $val.($key != $val ? " AS ".$key : "");
			}
			$res = implode(", ", $arRes);
		}
		return $res;
	}

	public static function SetReadLabels($ID, $arUserGroups) // out-of-date function
	{
		$ID = intVal($ID);
		$arTopic = CForumTopic::GetByID($ID);
		if ($arTopic)
		{
			$FID = intVal($arTopic["FORUM_ID"]);
			if (is_null($_SESSION["read_forum_".$FID]) || strLen($_SESSION["read_forum_".$FID])<=0)
			{
				$_SESSION["read_forum_".$FID] = "0";
			}

			$_SESSION["first_read_forum_".$FID] = intVal($_SESSION["first_read_forum_".$FID]);

			$arFilter = array(
				"FORUM_ID" => $FID,
				"TOPIC_ID" => $ID
				);
			if (intVal($_SESSION["first_read_forum_".$FID])>0)
				$arFilter[">ID"] = intVal($_SESSION["first_read_forum_".$FID]);
			if ($_SESSION["read_forum_".$FID]!="0")
				$arFilter["!@ID"] = $_SESSION["read_forum_".$FID];
			if (CForumNew::GetUserPermission($FID, $arUserGroups)<"Q")
				$arFilter["APPROVED"] = "Y";
			$db_res = CForumMessage::GetList(array(), $arFilter);
			if ($db_res)
			{
				while ($ar_res = $db_res->Fetch())
				{
					$_SESSION["read_forum_".$FID] .= ",".intVal($ar_res["ID"]);
				}
			}
			CForumTopic::Update($ID, array("=VIEWS"=>"VIEWS+1"));
		}
	}

	public static function SetReadLabelsNew($ID, $update = false, $LastVisit = false, $arAddParams = array())
	{
		global $DB, $USER;

		$ID = intVal($ID);
		$result = false;
		$arAddParams = (is_array($arAddParams) ? $arAddParams : array());
		$arAddParams["UPDATE_TOPIC_VIEWS"] = ($arAddParams["UPDATE_TOPIC_VIEWS"] == "N" ? "N" : "Y");

		if (!$update)
		{
			$arTopic = CForumTopic::GetByID($ID, array("NoFilter" => true));
			if ($arTopic)
			{
				if ($arAddParams["UPDATE_TOPIC_VIEWS"] == "Y")
					CForumTopic::Update($ID, array("=VIEWS"=>"VIEWS+1"));

				if (!$USER->IsAuthorized())
					return false;

				$USER_ID = intVal($USER->GetID());

				$Fields = array("USER_ID" => $USER_ID, "LAST_VISIT" => $DB->GetNowFunction(),
					"FORUM_ID" => $arTopic["FORUM_ID"], "TOPIC_ID" => $ID);

				if (intVal($LastVisit) > 0):
					$Fields["LAST_VISIT"] = $DB->CharToDateFunction($DB->ForSql(Date(
						CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL")), $LastVisit)), "FULL");
				endif;

				$rows = $DB->Update("b_forum_user_topic", $Fields, "WHERE (TOPIC_ID=".$ID." AND USER_ID=".$USER_ID.")", "File: ".__FILE__."<br>Line: ".__LINE__);
				if (intVal($rows)<=0)
					return $DB->Insert("b_forum_user_topic", $Fields, "File: ".__FILE__."<br>Line: ".__LINE__);
				else
					return true;
			}
		}
		elseif ($USER->IsAuthorized())
		{
			$Fields = array("LAST_VISIT" => $DB->GetNowFunction());
			return $DB->Update("b_forum_user_topic", $Fields, "WHERE (FORUM_ID=".$ID." AND USER_ID=".intVal($USER->GetID()).")", "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		return false;
	}

	public static function CleanUp($period = 168)
	{
		global $DB;
		$period = intVal($period)*3600;
		$date = $DB->CharToDateFunction($DB->ForSql(Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat("FULL", LANG)), time()-$period)), "FULL") ;
		$strSQL = "DELETE FROM b_forum_user_topic
					WHERE (LAST_VISIT
					< ".$date.")";
		$DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		return "CForumTopic::CleanUp();";
	}


	//---------------> Topic utils
	public static function SetStat($ID = 0, $arParams = array())
	{
		global $DB;
		$ID = intVal($ID);
		if ($ID <= 0):
			return false;
		endif;
		$arParams = (is_array($arParams) ? $arParams : array());
		$arMessage = (is_array($arParams["MESSAGE"]) ? $arParams["MESSAGE"] : array());
		if ($arMessage["TOPIC_ID"] != $ID)
			$arMessage = array();
		$arFields = array();

		if (!empty($arMessage))
		{
			$arFields = array(
				"ABS_LAST_POSTER_ID" => ((intVal($arMessage["AUTHOR_ID"])>0) ? $arMessage["AUTHOR_ID"] : false),
				"ABS_LAST_POSTER_NAME" => $arMessage["AUTHOR_NAME"],
				"ABS_LAST_POST_DATE" => $arMessage["POST_DATE"],
				"ABS_LAST_MESSAGE_ID" => $arMessage["ID"]);
			if ($arMessage["APPROVED"] == "Y"):
				$arFields["APPROVED"] = "Y";
				$arFields["LAST_POSTER_ID"] = $arFields["ABS_LAST_POSTER_ID"];
				$arFields["LAST_POSTER_NAME"] = $arFields["ABS_LAST_POSTER_NAME"];
				$arFields["LAST_POST_DATE"] = $arFields["ABS_LAST_POST_DATE"];
				$arFields["LAST_MESSAGE_ID"] = $arFields["ABS_LAST_MESSAGE_ID"];
				if ($arMessage["NEW_TOPIC"] != "Y"):
					$arFields["=POSTS"] = "POSTS+1";
				endif;
			else:
				$arFields["=POSTS_UNAPPROVED"] = "POSTS_UNAPPROVED+1";
			endif;
		}
		else
		{
			$res = CForumMessage::GetList(array(), array("TOPIC_ID" => $ID), "cnt_not_approved");
			$res["CNT"] = (intVal($res["CNT"]) - intVal($res["CNT_NOT_APPROVED"]));
			$res["CNT"] = ($res["CNT"] > 0 ? $res["CNT"] : 0);
			if (intval($res["ABS_FIRST_MESSAGE_ID"]) > 0 && intval($res["ABS_FIRST_MESSAGE_ID"]) != intval($res["FIRST_MESSAGE_ID"]))
			{
				$strSQL = "UPDATE b_forum_message SET NEW_TOPIC = (CASE WHEN ID=".intval($res["ABS_FIRST_MESSAGE_ID"])." THEN 'Y' ELSE 'N' END) WHERE TOPIC_ID=".$ID;
				$GLOBALS["DB"]->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}

			$arFields = array(
				"APPROVED" => ($res["CNT"] > 0 ? "Y" : "N"),
				"POSTS" => ($res["CNT"] > 0 ? ($res["CNT"] - 1) : 0),
				"LAST_POSTER_ID" => false,
				"LAST_POSTER_NAME" => false,
				"LAST_POST_DATE" => false,
				"LAST_MESSAGE_ID" => intVal($res["LAST_MESSAGE_ID"]),
				"POSTS_UNAPPROVED" => intVal($res["CNT_NOT_APPROVED"]),
				"ABS_LAST_POSTER_ID" => false,
				"ABS_LAST_POSTER_NAME" => false,
				"ABS_LAST_POST_DATE" => false,
				"ABS_LAST_MESSAGE_ID" => intVal($res["ABS_LAST_MESSAGE_ID"]));

			if ($arFields["ABS_LAST_MESSAGE_ID"] > 0):
				$res = CForumMessage::GetByID($arFields["ABS_LAST_MESSAGE_ID"], array("FILTER" => "N"));
				$arFields["ABS_LAST_POSTER_ID"] = (intVal($res["AUTHOR_ID"]) > 0 ? $res["AUTHOR_ID"] : false);
				$arFields["ABS_LAST_POSTER_NAME"] = $res["AUTHOR_NAME"];
				$arFields["ABS_LAST_POST_DATE"] = $res["POST_DATE"];
				if (intVal($arFields["LAST_MESSAGE_ID"]) > 0):
					if ($arFields["LAST_MESSAGE_ID"] < $arFields["ABS_LAST_MESSAGE_ID"]):
						$res = CForumMessage::GetByID($arFields["LAST_MESSAGE_ID"], array("FILTER" => "N"));
					endif;
					$arFields["LAST_POSTER_ID"] = (intVal($res["AUTHOR_ID"]) > 0 ? $res["AUTHOR_ID"] : false);
					$arFields["LAST_POSTER_NAME"] = $res["AUTHOR_NAME"];
					$arFields["LAST_POST_DATE"] = $res["POST_DATE"];
				endif;
			endif;

			foreach (array(
				"LAST_POST_DATE" => "START_DATE",
				"ABS_LAST_POST_DATE" => "START_DATE",
				"LAST_POSTER_NAME" => "USER_START_NAME",
				"ABS_LAST_POSTER_NAME" => "USER_START_NAME") as $key => $val)
			{
				if ($arFields[$key] == false)
				{
					$arFields["=".$key] = $val;
					unset($arFields[$key]);
				}
			}
		}
		return CForumTopic::Update($ID, $arFields);
	}

	public static function OnBeforeIBlockElementDelete($ELEMENT_ID)
	{
		$ELEMENT_ID = intVal($ELEMENT_ID);
		if ($ELEMENT_ID > 0 && CModule::IncludeModule("iblock"))
		{
			$rsElement = CIBlockElement::GetList(
				array("ID" => "ASC"),
				array(
					"ID" => $ELEMENT_ID,
					"SHOW_HISTORY" => "Y",
					"CHECK_PERMISSIONS" => "N",
				),
				false,
				false,
				array("ID", "WF_PARENT_ELEMENT_ID", "IBLOCK_ID")
			);
			$arElement = $rsElement->Fetch();
			if(is_array($arElement) && $arElement["WF_PARENT_ELEMENT_ID"] == 0)
			{
				$rsProperty = CIBlockElement::GetProperty($arElement["IBLOCK_ID"], $arElement["ID"], array(), array("CODE" => "FORUM_TOPIC_ID"));
				if ($rsProperty && $arProperty = $rsProperty->Fetch())
				{
					if(is_array($arProperty) && $arProperty["VALUE"] > 0)
					{
						CForumTopic::Delete($arProperty["VALUE"]);
					}
				}
			}
		}
		return true;
	}

	public static function GetMessageCount($forumID, $topicID, $approved = null)
	{
		global $CACHE_MANAGER;
		static $arCacheCount = array();
		static $obCache = null;
		static $cacheLabel = 'forum_msg_count';
		static $notCached = 0;
		static $TTL = 3600000;

		if ($approved === true) $approved = "Y";
		if ($approved === false) $approved = "N";
		if ($approved === null) $approved = "A";

		if ($approved !== "Y" && $approved !== "N" && $approved !== "A")
			return false;

		if (isset($arCacheCount[$forumID][$topicID][$approved]))
		{
			return $arCacheCount[$forumID][$topicID][$approved];
		}

		if ($obCache === null)
			$obCache = new CPHPCache;

		$cacheID = md5($cacheLabel.$forumID);
		$cachePath = str_replace(array(":", "//"), "/", "/".SITE_ID."/".$cacheLabel."/");
		if ($obCache->InitCache($TTL, $cacheID, $cachePath))
		{
			$resCache = $obCache->GetVars();
			if (is_array($resCache['messages']))
				$arCacheCount[$forumID] = $resCache['messages'];
		}

		if (isset($arCacheCount[$forumID][$topicID][$approved]))
		{
			return $arCacheCount[$forumID][$topicID][$approved];
		}
		else
		{
			$bCount = true;
			if ($approved === "N" || $approved === "Y")
				$bCount = "cnt_not_approved";

			if (intval($topicID) > 0 || $topicID === 0)
				$arFilter = array("TOPIC_ID" => $topicID);
			else
			{
				$arRes = CForumTopic::GetByID($topicID);
				if ($arRes)
					$arFilter = array("TOPIC_ID" => $arRes['ID']);
				else
					return false;
			}
			$count = CForumMessage::GetList(null, $arFilter, $bCount);

			$result = 0;
			if ($approved === "N")
			{
				$result = intval($count['CNT_NOT_APPROVED']);
			} elseif ($approved === "Y") {
				$result = $count['CNT'] - $count['CNT_NOT_APPROVED'];
			} else {
				$result = intval($count);
			}
			$notCached++;
		}

		$arCacheCount[$forumID][$topicID][$approved] = $result;

		if ($notCached > 2)
		{
			$obCache->StartDataCache($TTL, $cacheID, $cachePath);
			CForumCacheManager::SetTag($cachePath, $cacheLabel.$forumID);
			$obCache->EndDataCache(array("messages" => $arCacheCount[$forumID]));
			$notCached = 0;
		}
		return $result;
	}
}

class _CTopicDBResult extends CDBResult
{
	var $sNameTemplate = '';
	public function _CTopicDBResult($res, $params = array())
	{
		$this->sNameTemplate = (!empty($params["sNameTemplate"]) ? $params["sNameTemplate"] : '');
		parent::CDBResult($res);
	}
	public function Fetch()
	{
		global $DB;
		if($res = parent::Fetch())
		{
			if (COption::GetOptionString("forum", "FILTER", "Y") == "Y")
			{
				if (!empty($res["HTML"]))
				{
					$arr = unserialize($res["HTML"]);
					if (is_array($arr) && is_set($arr, "TITLE"))
					{
						foreach ($arr as $key => $val)
						{
							if (strLen($val)>0)
								$res[$key] = $val;
						}
					}
				}
				if (!empty($res["F_HTML"]))
				{
					$arr = unserialize($res["F_HTML"]);
					if (is_array($arr))
					{
						foreach ($arr as $key => $val)
						{
							$res["F_".$key] = $val;
						}
					}
					if (!empty($res["TITLE"]))
						$res["F_TITLE"] = $res["TITLE"];
				}
			}

			/* For CForumUser::UserAddInfo only */
			if (is_set($res, "FIRST_POST") || is_set($res, "LAST_POST"))
			{
				$arSqlSearch = array();
				if (is_set($res, "FIRST_POST"))
					$arSqlSearch["FIRST_POST"] = "FM.ID=".intVal($res["FIRST_POST"]);
				if (is_set($res, "LAST_POST"))
					$arSqlSearch["LAST_POST"] = "FM.ID=".intVal($res["LAST_POST"]);
				if (!empty($arSqlSearch)):
					$strSql = "SELECT FM.ID, ".$DB->DateToCharFunction("FM.POST_DATE", "FULL")." AS POST_DATE ".
						"FROM b_forum_message FM WHERE ".implode(" OR ", $arSqlSearch);
					$db_res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
					if($db_res && $val = $db_res->Fetch()):
						do
						{
							if (is_set($res, "FIRST_POST") && $res["FIRST_POST"] == $val["ID"])
								$res["FIRST_POST_DATE"] = $val["POST_DATE"];
							if (is_set($res, "LAST_POST") && $res["LAST_POST"] == $val["ID"])
								$res["LAST_POST_DATE"] = $val["POST_DATE"];
						}while ($val = $db_res->Fetch());
					endif;
				endif;
			}

			if (!empty($this->sNameTemplate))
			{
				$arTmp = array();
				foreach (array(
					"USER_START_ID" => "USER_START_NAME",
					"LAST_POSTER_ID" => "LAST_POSTER_NAME",
					"ABS_LAST_POSTER_ID" => "ABS_LAST_POSTER_NAME") as $id => $name)
				{
					$tmp = "";
					if (!empty($res[$id]))
					{
						if (in_array($res[$id], $arTmp))
						{
							$tmp = $arTmp[$res[$id]];
						}
						else
						{
							$arTmp[$res[$id]] = $tmp = (!empty($res[$name."_FRMT"]) ? $res[$name."_FRMT"] :
								CForumUser::GetFormattedNameByUserID($res[$id], $this->sNameTemplate));
						}
					}

					$res[$name] = (!empty($tmp) ? $tmp : $res[$name]);
				}
			}
		}
		return $res;
	}
}
?>