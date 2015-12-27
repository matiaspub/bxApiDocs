<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["SONET_GROUP_CACHE"] = array();


/**
 * <b>CSocNetGroup</b> - класс для работы с рабочими группами социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/index.php
 * @author Bitrix
 */
class CAllSocNetGroup
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $arSocNetAllowedInitiatePerms, $arSocNetAllowedSpamPerms;

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if(
			($ID === 0 && !is_set($arFields, "SITE_ID")) 
			||
			(
				is_set($arFields, "SITE_ID")
				&& (
					(is_array($arFields["SITE_ID"]) && count($arFields["SITE_ID"]) <= 0)
					||
					(!is_array($arFields["SITE_ID"]) && strlen($arFields["SITE_ID"]) <= 0)
				)
			)
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GG_EMPTY_SITE_ID"), "EMPTY_SITE_ID");
			return false;
		}
		elseif(is_set($arFields, "SITE_ID"))
		{
			if(!is_array($arFields["SITE_ID"]))
				$arFields["SITE_ID"] = array($arFields["SITE_ID"]);

			foreach($arFields["SITE_ID"] as $v)
			{
				$r = CSite::GetByID($v);
				if(!$r->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $v, GetMessage("SONET_GG_ERROR_NO_SITE")), "ERROR_NO_SITE");				
					return false;
				}
			}
		}

		if ((is_set($arFields, "NAME") || $ACTION=="ADD") && strlen($arFields["NAME"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_NAME"), "EMPTY_NAME");
			return false;
		}

		if (is_set($arFields, "DATE_CREATE") && (!$DB->IsDate($arFields["DATE_CREATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_DATE_CREATE"), "EMPTY_DATE_CREATE");
			return false;
		}

		if (is_set($arFields, "DATE_UPDATE") && (!$DB->IsDate($arFields["DATE_UPDATE"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_DATE_UPDATE"), "EMPTY_DATE_UPDATE");
			return false;
		}

		if (is_set($arFields, "DATE_ACTIVITY") && (!$DB->IsDate($arFields["DATE_ACTIVITY"], false, LANG, "FULL")))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_DATE_ACTIVITY"), "EMPTY_DATE_ACTIVITY");
			return false;
		}

		if ((is_set($arFields, "OWNER_ID") || $ACTION=="ADD") && IntVal($arFields["OWNER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_OWNER_ID"), "EMPTY_OWNER_ID");
			return false;
		}
		elseif (is_set($arFields, "OWNER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["OWNER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_ERROR_NO_OWNER_ID"), "ERROR_NO_OWNER_ID");
				return false;
			}
		}

		if ((is_set($arFields, "SUBJECT_ID") || $ACTION=="ADD") && IntVal($arFields["SUBJECT_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_SUBJECT_ID"), "EMPTY_SUBJECT_ID");
			return false;
		}
		elseif (is_set($arFields, "SUBJECT_ID"))
		{
			$arResult = CSocNetGroupSubject::GetByID($arFields["SUBJECT_ID"]);
			if ($arResult == false)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_ERROR_NO_SUBJECT_ID"), "ERROR_NO_SUBJECT_ID");
				return false;
			}
		}

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"] != "Y" && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";

		if ((is_set($arFields, "VISIBLE") || $ACTION=="ADD") && $arFields["VISIBLE"] != "Y" && $arFields["VISIBLE"] != "N")
			$arFields["VISIBLE"] = "Y";

		if ((is_set($arFields, "OPENED") || $ACTION=="ADD") && $arFields["OPENED"] != "Y" && $arFields["OPENED"] != "N")
			$arFields["OPENED"] = "N";

		if ((is_set($arFields, "CLOSED") || $ACTION=="ADD") && $arFields["CLOSED"] != "Y" && $arFields["CLOSED"] != "N")
			$arFields["CLOSED"] = "N";

		if ((is_set($arFields, "INITIATE_PERMS") || $ACTION=="ADD") && strlen($arFields["INITIATE_PERMS"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UG_EMPTY_INITIATE_PERMS"), "EMPTY_INITIATE_PERMS");
			return false;
		}
		elseif (is_set($arFields, "INITIATE_PERMS") && !in_array($arFields["INITIATE_PERMS"], $arSocNetAllowedInitiatePerms))
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["INITIATE_PERMS"], GetMessage("SONET_UG_ERROR_NO_INITIATE_PERMS")), "ERROR_NO_INITIATE_PERMS");
			return false;
		}

		if ((is_set($arFields, "SPAM_PERMS") || $ACTION=="ADD") && strlen($arFields["SPAM_PERMS"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UG_EMPTY_SPAM_PERMS"), "EMPTY_SPAM_PERMS");
			return false;
		}
		elseif (is_set($arFields, "SPAM_PERMS") && !in_array($arFields["SPAM_PERMS"], $arSocNetAllowedSpamPerms))
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["SPAM_PERMS"], GetMessage("SONET_UG_ERROR_NO_SPAM_PERMS")), "ERROR_NO_SPAM_PERMS");
			return false;
		}

		if (is_set($arFields, "IMAGE_ID") && strlen($arFields["IMAGE_ID"]["name"])<=0 && (strlen($arFields["IMAGE_ID"]["del"])<=0 || $arFields["IMAGE_ID"]["del"] != "Y"))
			unset($arFields["IMAGE_ID"]);

		if (is_set($arFields, "IMAGE_ID"))
		{
			$arResult = CFile::CheckImageFile($arFields["IMAGE_ID"], 0, 0, 0);
			if (strlen($arResult) > 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GP_ERROR_IMAGE_ID").": ".$arResult, "ERROR_IMAGE_ID");
				return false;
			}
		}

		if (!$GLOBALS["USER_FIELD_MANAGER"]->CheckFields("SONET_GROUP", $ID, $arFields))
			return false;

		return True;
	}

	public static function Delete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetGroupDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$arGroup = CSocNetGroup::GetByID($ID);
		if (!$arGroup)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_NO_GROUP"), "ERROR_NO_GROUP");
			return false;
		}

		$DB->StartTransaction();

		$events = GetModuleEvents("socialnetwork", "OnSocNetGroupDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_user2group WHERE GROUP_ID = ".$ID."", true);

		if ($bSuccess)
		{
			$bSuccessTmp = true;
			$dbResult = CSocNetFeatures::GetList(
				array(),
				array("ENTITY_ID" => $ID, "ENTITY_TYPE" => SONET_ENTITY_GROUP)
			);
			while ($arResult = $dbResult->Fetch())
			{
				$bSuccessTmp = $DB->Query("DELETE FROM b_sonet_features2perms WHERE FEATURE_ID = ".$arResult["ID"]."", true);
				if (!$bSuccessTmp)
					break;
			}
			if (!$bSuccessTmp)
				$bSuccess = false;
		}
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_features WHERE ENTITY_ID = ".$ID." AND ENTITY_TYPE = '".$DB->ForSql(SONET_ENTITY_GROUP, 1)."'", true);
		if ($bSuccess)
		{
			$dbResult = CSocNetLog::GetList(
				array(),
				array("ENTITY_ID" => $ID, "ENTITY_TYPE" => SONET_ENTITY_GROUP),
				false,
				false,
				array("ID")
			);
			while ($arResult = $dbResult->Fetch())
			{
				$bSuccessTmp = $DB->Query("DELETE FROM b_sonet_log_site WHERE LOG_ID = ".$arResult["ID"]."", true);
				if (!$bSuccessTmp)
					break;

				$bSuccessTmp = $DB->Query("DELETE FROM b_sonet_log_right WHERE LOG_ID = ".$arResult["ID"]."", true);
				if (!$bSuccessTmp)
					break;
			}
			if (!$bSuccessTmp)
				$bSuccess = false;
		}
		if ($bSuccess)		
			$bSuccess = $DB->Query("DELETE FROM b_sonet_log WHERE ENTITY_TYPE = '".SONET_ENTITY_GROUP."' AND ENTITY_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = CSocNetLog::DeleteSystemEventsByGroupID($ID);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_log_events WHERE ENTITY_TYPE = 'G' AND ENTITY_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_group_site WHERE GROUP_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_log_right WHERE GROUP_CODE LIKE 'SG".$ID."\_%' OR GROUP_CODE = 'SG".$ID."'", true);
		if ($bSuccess)
			$bSuccess = CSocNetSubscription::DeleteEx(false, "SG".$ID);

		if ($bSuccess)
		{
			CFile::Delete($arGroup["IMAGE_ID"]);
			$bSuccess = $DB->Query("DELETE FROM b_sonet_group WHERE ID = ".$ID."", true);
		}

		if ($bSuccess)
		{
			CUserOptions::DeleteOption("socialnetwork", "~menu_".SONET_ENTITY_GROUP."_".$ID, false, 0);
			unset($GLOBALS["SONET_GROUP_CACHE"][$ID]);
		}
		
		if ($bSuccess)
			$DB->Commit();
		else
			$DB->Rollback();

		if ($bSuccess)
		{
			unset($GLOBALS["SONET_GROUP_CACHE"][$ID]);
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_G".$ID);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group");
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group_".$ID);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_group");
			}
		}

		if ($bSuccess && CModule::IncludeModule("search"))
			CSearch::DeleteIndex("socialnetwork", "G".$ID);

		if ($bSuccess)
			$DB->Query("DELETE FROM b_sonet_event_user_view WHERE ENTITY_TYPE = '".SONET_ENTITY_GROUP."' AND ENTITY_ID = ".$ID, true);

		if ($bSuccess)
			$GLOBALS["USER_FIELD_MANAGER"]->Delete("SONET_GROUP", $ID);

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		if (!CSocNetGroup::__ValidateID($userID))
			return false;

		$userID = IntVal($userID);

		$err = "";
		$dbResult = CSocNetGroup::GetList(array(), array("OWNER_ID" => $userID), false, false, array("ID", "NAME"));
		while ($arResult = $dbResult->GetNext())
			$err .= $arResult["NAME"]."<br>";
		
		if (strlen($err) <= 0)
			return true;
		else
		{
			$err = GetMessage("SONET_GG_ERROR_CANNOT_DELETE_USER_1").$err;
			$err .= GetMessage("SONET_GG_ERROR_CANNOT_DELETE_USER_2");
			$GLOBALS["APPLICATION"]->ThrowException($err);
			return false;
		}
	}

	public static function SetStat($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;
		
		$ID = IntVal($ID);

		$num = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" => $ID,
				"USER_ACTIVE" => "Y",
				"<=ROLE" => SONET_ROLES_USER
			),
			array()
		);

		$num_mods = CSocNetUserToGroup::GetList(
			array(),
			array(
				"GROUP_ID" => $ID,
				"USER_ACTIVE" => "Y",
				"<=ROLE" => SONET_ROLES_MODERATOR
			),
			array()
		);

		CSocNetGroup::Update(
			$ID, 
			array(
				"NUMBER_OF_MEMBERS" => $num,
				"NUMBER_OF_MODERATORS" => $num_mods
			), 
			true,
			false
		);
	}

	public static function SetLastActivity($ID, $date = false)
	{
		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		if ($date == false)
			CSocNetGroup::Update($ID, array("=DATE_ACTIVITY" => $GLOBALS["DB"]->CurrentTimeFunction()));
		else
			CSocNetGroup::Update($ID, array("DATE_ACTIVITY" => $date));
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Метод возвращает параметры рабочей группы с заданным идентификатором.</p> <p><b>Примечание</b>: при многократном вызове метода для одного и того же идентификатора рабочей группы в рамках одного хита запрос к базе направляется только один раз. В дальнейшем результат возвращается без запроса к базе.</p>
	*
	*
	* @param int $ID  Идентификатор рабочей группы
	*
	* @param bool $bCheckPermissions = false Флаг проверки прав доступа. Необязательный параметр. По
	* умолчанию равен false.
	*
	* @return array <p>Возвращается массив с ключами:<br><b>ID</b> - идентификатор рабочей
	* группы,<br><b>SITE_ID</b> - код сайта,<br><b>NAME</b> - название
	* группы,<br><b>DESCRIPTION</b> - описание группы,<br><b>DATE_CREATE</b> - дата
	* создания,<br><b>DATE_UPDATE</b> - дата последнего изменения параметров
	* группы,<br><b>ACTIVE</b> - активность,<br><b>VISIBLE</b> - видима ли группа в
	* списках,<br><b>OPENED</b> - открыта ли группа для свободного
	* вступления,<br><b>CLOSED</b> - является ли группа архивной,<br><b>SUBJECT_ID</b> -
	* код темы группы,<br><b>OWNER_ID</b> - код пользователя-владельца
	* группы,<br><b>KEYWORDS</b> - ключевые слова,<br><b>IMAGE_ID</b> - код
	* иконки,<br><b>NUMBER_OF_MEMBERS</b> - количество членов группы,<br><b>INITIATE_PERMS</b> -
	* кто имеет право на прием в группу новых членов,<br><b>SPAM_PERMS</b> - кто
	* имеет право на написание сообщений членам группы,<br><b>DATE_ACTIVITY</b> -
	* дата последней активности в группе,<br><b>SUBJECT_NAME</b> - название темы
	* группы.</p>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arGroup = CSocNetGroup::GetByID(5);
	* print_r($arGroup);
	* ?&gt;
	* </pre>
	*
	*
	* <h4>See Also</h4> 
	* <ul> <li> <a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/GetList.php">CSocNetGroup::GetList</a> </li>
	* </ul><a name="examples"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/getbyid.php
	* @author Bitrix
	*/
	public static function GetByID($ID, $bCheckPermissions = false)
	{
		global $DB, $USER;

		if (!CSocNetGroup::__ValidateID($ID))
		{
			return false;
		}

		$ID = IntVal($ID);
		$cacheArrayKey = ($bCheckPermissions ? "Y" : "N");

		if (
			is_array($GLOBALS["SONET_GROUP_CACHE"])
			&& is_array($GLOBALS["SONET_GROUP_CACHE"][$ID])
			&& is_array($GLOBALS["SONET_GROUP_CACHE"][$cacheArrayKey])
		)
		{
			return $GLOBALS["SONET_GROUP_CACHE"][$ID][$cacheArrayKey];
		}
		else
		{
			if (!$bCheckPermissions)
			{
				$cache = new CPHPCache;
				$cache_time = 31536000;
				$cache_id = "group_".$ID."_".LANGUAGE_ID."_".CTimeZone::GetOffset();
				$cache_path = "/sonet/group/".$ID."/";
			}

			if (
				is_object($cache)
				&& $cache->InitCache($cache_time, $cache_id, $cache_path)
			)
			{
				$arCacheVars = $cache->GetVars();
				$arResult = $arCacheVars["FIELDS"];
			}
			else
			{
				if (is_object($cache))
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
				}

				$arFilter = array("ID" => $ID);
				if (
					$bCheckPermissions 
					&& ($USER->GetID() > 0)
				)
				{
					$arFilter["CHECK_PERMISSIONS"] = $USER->GetID();
				}

				$dbResult = CSocNetGroup::GetList(
					Array(), 
					$arFilter, 
					false, 
					false, 
					array("ID", "SITE_ID", "NAME", "DESCRIPTION", "DATE_CREATE", "DATE_UPDATE", "ACTIVE", "VISIBLE", "OPENED", "CLOSED", "SUBJECT_ID", "OWNER_ID", "KEYWORDS", "IMAGE_ID", "NUMBER_OF_MEMBERS", "NUMBER_OF_MODERATORS", "INITIATE_PERMS", "SPAM_PERMS", "DATE_ACTIVITY", "SUBJECT_NAME", "UF_*")
				);
				if ($arResult = $dbResult->GetNext())
				{
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$ID);
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");
					}

					$arResult["NAME_FORMATTED"] = $arResult["NAME"];
				}
				else
				{
					$arResult = false;
				}

				if (is_object($cache))
				{
					$arCacheData = Array(
						"FIELDS" => $arResult
					);
					$cache->EndDataCache($arCacheData);
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();
					}
				}
			}

			if (
				!array_key_exists("SONET_GROUP_CACHE", $GLOBALS)
				|| !is_array($GLOBALS["SONET_GROUP_CACHE"])
			)
			{
				$GLOBALS["SONET_GROUP_CACHE"] = array();
			}

			if (
				!array_key_exists($ID, $GLOBALS["SONET_GROUP_CACHE"])
				|| !is_array($GLOBALS["SONET_GROUP_CACHE"][$cacheArrayKey])
			)
			{
				$GLOBALS["SONET_GROUP_CACHE"][$ID] = array();
			}

			$GLOBALS["SONET_GROUP_CACHE"][$ID][$cacheArrayKey] = $arResult;

			return $arResult;
		}
	}

	/***************************************/
	/**********  COMMON METHODS  ***********/
	/***************************************/
	
	/**
	* <p>Проверяет, может ли пользователь принимать в группу новых членов.</p>
	*
	*
	* @param int $userID  Код пользователя </h
	*
	* @param int $groupID  Код группы
	*
	* @return bool <p>True, если пользователь может принимать в группу новых членов.
	* Иначе - false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/CanUserInitiate.php
	* @author Bitrix
	*/
	public static function CanUserInitiate($userID, $groupID)
	{
		$userID = IntVal($userID);
		$groupID = IntVal($groupID);
		if ($groupID <= 0)
			return false;

		$userRoleInGroup = CSocNetUserToGroup::GetUserRole($userID, $groupID);
		if ($userRoleInGroup == false)
			return false;

		$arGroup = CSocNetGroup::GetById($groupID);
		if ($arGroup == false)
			return false;

		if ($arGroup["INITIATE_PERMS"] == SONET_ROLES_MODERATOR)
		{
			if ($userRoleInGroup == SONET_ROLES_MODERATOR || $userRoleInGroup == SONET_ROLES_OWNER)
				return true;
			else
				return false;
		}
		elseif ($arGroup["INITIATE_PERMS"] == SONET_ROLES_USER)
		{
			if ($userRoleInGroup == SONET_ROLES_MODERATOR || $userRoleInGroup == SONET_ROLES_OWNER || $userRoleInGroup == SONET_ROLES_USER)
				return true;
			else
				return false;
		}
		elseif ($arGroup["INITIATE_PERMS"] == SONET_ROLES_OWNER)
		{
			if ($userRoleInGroup == SONET_ROLES_OWNER)
				return true;
			else
				return false;
		}

		return false;
	}

	
	/**
	* <p>Проверяет, может ли пользователь видеть группу в списке групп. Пользователь может видеть группу с списке групп, если группа видимая, либо пользователь является ее членом.</p>
	*
	*
	* @param int $userID  Код пользователя </h
	*
	* @param int $groupID  Код группы
	*
	* @return bool <p>True, если пользователь имеет право видеть группу. Иначе - false.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/CanUserViewGroup.php
	* @author Bitrix
	*/
	public static function CanUserViewGroup($userID, $groupID)
	{
		$userID = IntVal($userID);
		$groupID = IntVal($groupID);
		if ($groupID <= 0)
			return false;

		$arGroup = CSocNetGroup::GetById($groupID);
		if ($arGroup == false)
			return false;

		if ($arGroup["VISIBLE"] == "Y")
			return true;

		$userRoleInGroup = CSocNetUserToGroup::GetUserRole($userID, $groupID);
		if ($userRoleInGroup == false)
			return false;

		if ($userRoleInGroup == SONET_ROLES_MODERATOR || $userRoleInGroup == SONET_ROLES_OWNER || $userRoleInGroup == SONET_ROLES_USER)
			return true;
		else
			return false;

		return false;
	}

	
	/**
	* <p>Проверяет, может ли пользователь просматривать групу. Пользователь может просматривать группу, если она открытая, либо если этот пользователь является членом группы.</p>
	*
	*
	* @param int $userID  Код пользователя </h
	*
	* @param int $groupID  Код группы
	*
	* @return bool <p>True, если пользователь может просматривать группу. Иначе - false.</p>
	* <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/CanUserReadGroup.php
	* @author Bitrix
	*/
	public static function CanUserReadGroup($userID, $groupID)
	{
		$userID = IntVal($userID);
		$groupID = IntVal($groupID);
		if ($groupID <= 0)
			return false;

		$arGroup = CSocNetGroup::GetById($groupID);
		if ($arGroup == false)
			return false;

		if ($arGroup["OPENED"] == "Y")
			return true;

		$userRoleInGroup = CSocNetUserToGroup::GetUserRole($userID, $groupID);
		if ($userRoleInGroup == false)
			return false;

		if ($userRoleInGroup == SONET_ROLES_MODERATOR || $userRoleInGroup == SONET_ROLES_OWNER || $userRoleInGroup == SONET_ROLES_USER)
			return true;
		else
			return false;

		return false;
	}

	/***************************************/
	/************  ACTIONS  ****************/
	/***************************************/
	
	/**
	* <p>Метод создает новую рабочую группу. Для создания группы необходимо задать права пользователей (Параметры <b> INITIATE_PERMS</b> и <b>SPAM_PERMS</b>). Лучше использовать константы (см. ключи массива), но можно использовать и символы:</p> <ul> <li> <b>A</b> (Только владелец группы),</li> <li> <b>E</b> (Владелец группы и модераторы группы),</li> <li> <b>K</b> (Все члены группы ).</li> </ul> <p><b>Примечание</b>: при работе метода вызываются события <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnBeforeSocNetGroupAdd.php">OnBeforeSocNetGroupAdd</a> и <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/events/OnSocNetGroupAdd.php">OnSocNetGroupAdd</a>.</p>
	*
	*
	* @param int $ownerID  Код пользователя - владельца новой рабочей группы.
	*
	* @param array $arFields  Массив параметров новой группы. Допустимые ключи
	* массива:<br><b>SITE_ID</b> - код сайта (обязательное поле),<br><b>NAME</b> -
	* название группы (обязательное поле),<br><b>DESCRIPTION</b> - описание
	* группы,<br><b>VISIBLE</b> - флаг Y/N - видна ли группа в списке
	* групп,<br><b>OPENED</b> - флаг Y/N - открыта ли группа для свободного
	* вступления,<br><b>SUBJECT_ID</b> - код темы (обязательное поле),<br><b>KEYWORDS</b> -
	* ключевые слова,<br><b>IMAGE_ID</b> - иконка группы,<br><b>INITIATE_PERMS</b> - кто
	* имеет право на приглашение пользователей в группу (обязательное
	* поле): SONET_ROLES_OWNER - только владелец группы, SONET_ROLES_MODERATOR - владелец
	* группы и модераторы группы , SONET_ROLES_USER - все члены группы,<br><b>CLOSED</b>
	* - флаг Y/N - является ли группа архивной,<br><b>SPAM_PERMS</b> - кто имеет
	* право на отправку сообщений в группу (обязательное поле):
	* SONET_ROLES_OWNER - только владелец группы, SONET_ROLES_MODERATOR - владелец группы и
	* модераторы группы, SONET_ROLES_USER - все члены группы, SONET_ROLES_ALL - все
	* пользователи.
	*
	* @param bool $bAutoSubscribe = true Атоподписывание на созданную тему. Необязательный параметр. По
	* умолчанию имеет значение True.
	*
	* @return int <p>Метод возвращает код вновь созданной группы или false в случае
	* ошибки.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* $arImageID = $GLOBALS["HTTP_POST_FILES"]["GROUP_IMAGE_ID"];
	* if (StrLen($arImageID["tmp_name"]) &gt; 0)
	* 	CFile::ResizeImage($arImageID, array("width" =&gt; 300, "height" =&gt; 300), BX_RESIZE_IMAGE_PROPORTIONAL);
	* 
	* $arFields = array(
	* 	"SITE_ID" =&gt; SITE_ID,
	* 	"NAME" =&gt; $_POST["GROUP_NAME"],
	* 	"DESCRIPTION" =&gt; $_POST["GROUP_DESCRIPTION"],
	* 	"VISIBLE" =&gt; ($_POST["GROUP_VISIBLE"] == "Y" ? "Y" : "N"),
	* 	"OPENED" =&gt; ($_POST["GROUP_OPENED"] == "Y" ? "Y" : "N"),
	* 	"CLOSED" =&gt; ($_POST["GROUP_CLOSED"] == "Y" ? "Y" : "N"),
	* 	"SUBJECT_ID" =&gt; $_POST["GROUP_SUBJECT_ID"],
	* 	"KEYWORDS" =&gt; $_POST["GROUP_KEYWORDS"],
	* 	"IMAGE_ID" =&gt; $arImageID,
	* 	"INITIATE_PERMS" =&gt; $_POST["GROUP_INITIATE_PERMS"],
	* 	"SPAM_PERMS" =&gt; $_POST["GROUP_SPAM_PERMS"],
	* );
	* 
	* $groupId = CSocNetGroup::CreateGroup($GLOBALS["USER"]-&gt;GetID(), $arFields);
	* if (!$groupId)
	* {
	* 	if ($e = $GLOBALS["APPLICATION"]-&gt;GetException())
	* 		$errorMessage .= $e-&gt;GetString();
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetgroup/creategroup.php
	* @author Bitrix
	*/
	public static function CreateGroup($ownerID, $arFields, $bAutoSubscribe = true)
	{
		global $APPLICATION, $DB;

		$ownerID = IntVal($ownerID);
		if ($ownerID <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UR_EMPTY_OWNERID").". ", "ERROR_OWNERID");
			return false;
		}

		if (!isset($arFields) || !is_array($arFields))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_UR_EMPTY_FIELDS").". ", "ERROR_FIELDS");
			return false;
		}

		$DB->StartTransaction();

		$arFields["=DATE_CREATE"] = $GLOBALS["DB"]->CurrentTimeFunction();
		$arFields["=DATE_UPDATE"] = $GLOBALS["DB"]->CurrentTimeFunction();
		$arFields["=DATE_ACTIVITY"] = $GLOBALS["DB"]->CurrentTimeFunction();
		$arFields["ACTIVE"] = "Y";
		$arFields["OWNER_ID"] = $ownerID;

		if (!is_set($arFields, "SPAM_PERMS") || strlen($arFields["SPAM_PERMS"]) <= 0)
			$arFields["SPAM_PERMS"] = SONET_ROLES_OWNER;

		$groupID = CSocNetGroup::Add($arFields);

		if (!$groupID || IntVal($groupID) <= 0)
		{
			$errorMessage = "";
			if ($e = $APPLICATION->GetException())
			{
				$errorMessage = $e->GetString();
				$errorID = $e->GetID();

				if (StrLen($errorID) <= 0 && isset($e->messages) && is_array($e->messages) && is_array($e->messages[0]) && array_key_exists("id", $e->messages[0]))
					$errorID = $e->messages[0]["id"];
			}

			if (StrLen($errorMessage) <= 0)
				$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_GROUP").". ";

			if (StrLen($errorID) <= 0)
				$errorID = "ERROR_CREATE_GROUP";

			$GLOBALS["APPLICATION"]->ThrowException($errorMessage, $errorID);
			$DB->Rollback();
			return false;
		}

		$arFields1 = array(
			"USER_ID" => $ownerID,
			"GROUP_ID" => $groupID,
			"ROLE" => SONET_ROLES_OWNER,
			"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
			"INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
			"INITIATED_BY_USER_ID" => $ownerID,
			"MESSAGE" => false
		);

		if (!CSocNetUserToGroup::Add($arFields1))
		{
			$errorMessage = "";
			if ($e = $APPLICATION->GetException())
				$errorMessage = $e->GetString();
			if (StrLen($errorMessage) <= 0)
				$errorMessage = GetMessage("SONET_UR_ERROR_CREATE_U_GROUP").". ";

			$GLOBALS["APPLICATION"]->ThrowException($errorMessage, "ERROR_CREATE_GROUP");
			$DB->Rollback();
			return false;
		}

		if ($bAutoSubscribe)
			CSocNetLogEvents::AutoSubscribe($ownerID, SONET_ENTITY_GROUP, $groupID);

		CSocNetSubscription::Set($ownerID, "SG".$groupID, "Y");

		$DB->Commit();

		return $groupID;
	}

	/***************************************/
	/*************  UTILITIES  *************/
	/***************************************/
	public static function __ValidateID($ID)
	{
		if (IntVal($ID)."|" == $ID."|")
			return true;

		$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_WRONG_PARAMETER_ID"), "ERROR_NO_ID");
		return false;
	}

	public static function GetFilterOperation($key)
	{
		$strNegative = "N";
		if (substr($key, 0, 1)=="!")
		{
			$key = substr($key, 1);
			$strNegative = "Y";
		}

		$strOrNull = "N";
		if (substr($key, 0, 1)=="+")
		{
			$key = substr($key, 1);
			$strOrNull = "Y";
		}

		if (substr($key, 0, 2)==">=")
		{
			$key = substr($key, 2);
			$strOperation = ">=";
		}
		elseif (substr($key, 0, 1)==">")
		{
			$key = substr($key, 1);
			$strOperation = ">";
		}
		elseif (substr($key, 0, 2)=="<=")
		{
			$key = substr($key, 2);
			$strOperation = "<=";
		}
		elseif (substr($key, 0, 1)=="<")
		{
			$key = substr($key, 1);
			$strOperation = "<";
		}
		elseif (substr($key, 0, 1)=="@")
		{
			$key = substr($key, 1);
			$strOperation = "IN";
		}
		elseif (substr($key, 0, 1)=="~")
		{
			$key = substr($key, 1);
			$strOperation = "LIKE";
		}
		elseif (substr($key, 0, 1)=="%")
		{
			$key = substr($key, 1);
			$strOperation = "QUERY";
		}
		else
		{
			$strOperation = "=";
		}

		return array("FIELD" => $key, "NEGATIVE" => $strNegative, "OPERATION" => $strOperation, "OR_NULL" => $strOrNull);
	}

	public static function PrepareSql(&$arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields, $arUF = array())
	{
		global $DB;

		$obUserFieldsSql = false;
		
		if (is_array($arUF) && array_key_exists("ENTITY_ID", $arUF))
		{
			$obUserFieldsSql = new CUserTypeSQL;
			$obUserFieldsSql->SetEntity($arUF["ENTITY_ID"], $arFields["ID"]["FIELD"]);
			$obUserFieldsSql->SetSelect($arSelectFields);
			$obUserFieldsSql->SetFilter($arFilter);
			$obUserFieldsSql->SetOrder($arOrder);
		}

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";

		$arGroupByFunct = array("COUNT", "AVG", "MIN", "MAX", "SUM");

		$arAlreadyJoined = array();

		// GROUP BY -->
		if (is_array($arGroupBy) && count($arGroupBy)>0)
		{
			$arSelectFields = $arGroupBy;
			foreach ($arGroupBy as $key => $val)
			{
				$val = strtoupper($val);
				$key = strtoupper($key);
				if (array_key_exists($val, $arFields) && !in_array($key, $arGroupByFunct))
				{
					if (strlen($strSqlGroupBy) > 0)
						$strSqlGroupBy .= ", ";
					$strSqlGroupBy .= $arFields[$val]["FIELD"];

					if (isset($arFields[$val]["FROM"])
						&& strlen($arFields[$val]["FROM"]) > 0
						&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$val]["FROM"];
						$arAlreadyJoined[] = $arFields[$val]["FROM"];
					}
				}
			}
		}
		// <-- GROUP BY

		// WHERE -->
		$arAlreadyJoinedOld = $arAlreadyJoined;
		$strSqlWhere .= CSqlUtil::PrepareWhere($arFields, $arFilter, $arAlreadyJoined);
		$arAlreadyJoinedDiff = array_diff($arAlreadyJoined, $arAlreadyJoinedOld);
		
		foreach($arAlreadyJoinedDiff as $from_tmp)
		{
			if (strlen($strSqlFrom) > 0)
				$strSqlFrom .= " ";
			$strSqlFrom .= $from_tmp;
		}

		if ($obUserFieldsSql)
		{
			$r = $obUserFieldsSql->GetFilter();
			if(strlen($r) > 0)
				$strSqlWhere .= (strlen($strSqlWhere) > 0 ? " AND" : "")." (".$r.") ";
		}
		// <-- WHERE

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
			$by = strtoupper($by);
			$order = strtoupper($order);

			if ($order != "ASC")
				$order = "DESC";
			else
				$order = "ASC";

			if (array_key_exists($by, $arFields))
			{
				if ($arFields[$by]["TYPE"] == "datetime" || $arFields[$by]["TYPE"] == "date")
				{
					$arSqlOrder[] = " ".$by."_X1 ".$order." ";
					if (!is_array($arSelectFields) || !in_array($by, $arSelectFields))
						$arSelectFields[] = $by;
				}
				else
					$arSqlOrder[] = " ".$arFields[$by]["FIELD"]." ".$order." ";

				if (isset($arFields[$by]["FROM"])
					&& strlen($arFields[$by]["FROM"]) > 0
					&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
				{
					if (strlen($strSqlFrom) > 0)
						$strSqlFrom .= " ";
					$strSqlFrom .= $arFields[$by]["FROM"];
					$arAlreadyJoined[] = $arFields[$by]["FROM"];
				}
			}
			elseif($obUserFieldsSql && $s = $obUserFieldsSql->GetOrder($by))
				$arSqlOrder[$by] = " ".$s." ".$order." ";
		}
		
		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder); 
		$tmp_count = count($arSqlOrder);
		for ($i=0; $i < $tmp_count; $i++)
		{
			if (strlen($strSqlOrderBy) > 0)
				$strSqlOrderBy .= ", ";

			if(strtoupper($DB->type)=="ORACLE")
			{
				if(substr($arSqlOrder[$i], -3)=="ASC")
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
				else
					$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
			}
			else
				$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		// SELECT -->
		$arFieldsKeys = array_keys($arFields);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
			$strSqlSelect = "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT ";
		else
		{
			if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

			if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields) <= 0
				|| in_array("*", $arSelectFields))
			{
				$tmp_count = count($arFieldsKeys);
				for ($i = 0; $i < $tmp_count; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
						&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
						continue;

					if (strlen($strSqlSelect) > 0)
						$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["TYPE"] == "datetime")
					{
						if (array_key_exists($arFieldsKeys[$i], $arOrder))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["TYPE"] == "date")
					{
						if (array_key_exists($arFieldsKeys[$i], $arOrder))
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i]."_X1, ";

						$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
						$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
						&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
						&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
						if (strlen($strSqlFrom) > 0)
							$strSqlFrom .= " ";
						$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
						$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
			}
			else
			{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
						if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

						if (in_array($key, $arGroupByFunct))
							$strSqlSelect .= $key."(".$arFields[$val]["FIELD"].") as ".$val;
						else
						{
							if ($arFields[$val]["TYPE"] == "datetime")
							{
								if (array_key_exists($val, $arOrder))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
							}
							elseif ($arFields[$val]["TYPE"] == "date")
							{
								if (array_key_exists($val, $arOrder))
									$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val."_X1, ";

								$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "SHORT")." as ".$val;
							}
							else
								$strSqlSelect .= $arFields[$val]["FIELD"]." as ".$val;
						}

						if (isset($arFields[$val]["FROM"])
							&& strlen($arFields[$val]["FROM"]) > 0
							&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
						{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$val]["FROM"];
							$arAlreadyJoined[] = $arFields[$val]["FROM"];
						}
					}
				}
			}

			if ($obUserFieldsSql)
				$strSqlSelect .= (strlen($strSqlSelect) <= 0 ? $arFields["ID"]["FIELD"] : "").$obUserFieldsSql->GetSelect();
			
			if (strlen($strSqlGroupBy) > 0)
			{
				if (strlen($strSqlSelect) > 0)
					$strSqlSelect .= ", ";
				$strSqlSelect .= "COUNT(%%_DISTINCT_%% ".$arFields[$arFieldsKeys[0]]["FIELD"].") as CNT";
			}
			else
				$strSqlSelect = "%%_DISTINCT_%% ".$strSqlSelect;
		}
		// <-- SELECT

		if ($obUserFieldsSql)
			$strSqlFrom .= " ".$obUserFieldsSql->GetJoin($arFields["ID"]["FIELD"]);

		return array(
			"SELECT" => $strSqlSelect,
			"FROM" => $strSqlFrom,
			"WHERE" => $strSqlWhere,
			"GROUPBY" => $strSqlGroupBy,
			"ORDERBY" => $strSqlOrderBy
		);
	}

	/***************************************/
	/*************    *************/
	/***************************************/

	public static function GetSite($group_id)
	{
		global $DB;
		$strSql = "SELECT L.*, SGS.* FROM b_sonet_group_site SGS, b_lang L WHERE L.LID=SGS.SITE_ID AND SGS.GROUP_ID=".IntVal($group_id);
		return $DB->Query($strSql);
	}

	public static function GetDefaultSiteId($groupId, $siteId = false)
	{
		$groupSiteId = ($siteId ? $siteId : SITE_ID);

		if (CModule::IncludeModule("extranet"))
		{
			$extranetSiteId = CExtranet::GetExtranetSiteID();

			$rsGroupSite = CSocNetGroup::GetSite($groupId);
			while ($arGroupSite = $rsGroupSite->Fetch())
			{
				if (
					!$extranetSiteId 
					|| $arGroupSite["LID"] != $extranetSiteId
				)
				{
					$groupSiteId = $arGroupSite["LID"];
					break;
				}
			}
		}

		return $groupSiteId;
	}

	public static function OnBeforeLangDelete($lang)
	{
		global $APPLICATION, $DB;
		$r = $DB->Query("
			SELECT GROUP_ID
			FROM b_sonet_group_site
			WHERE SITE_ID='".$DB->ForSQL($lang, 2)."'
			ORDER BY GROUP_ID
		");
		$arSocNetGroups = array();
		while($a = $r->Fetch())
			$arSocNetGroups[] = $a["GROUP_ID"];
		if(count($arSocNetGroups) > 0)
		{
			$APPLICATION->ThrowException(GetMessage("SONET_GROUP_SITE_LINKS_EXISTS", array("#ID_LIST#" => implode(", ", $arSocNetGroups))));
			return false;
		}
		else
			return true;
	}
}
?>