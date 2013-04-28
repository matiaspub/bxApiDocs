<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CSocNetUserPerms</b> - класс для работы с правами на доступ к профилю пользователя.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/index.php
 * @author Bitrix
 */
class CAllSocNetUserPerms
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $arSocNetUserOperations, $arSocNetAllowedRelationsType;

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "USER_ID") || $ACTION=="ADD") && IntVal($arFields["USER_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_EMPTY_USER_ID"), "EMPTY_USER_ID");
			return false;
		}
		elseif (is_set($arFields, "USER_ID"))
		{
			$dbResult = CUser::GetByID($arFields["USER_ID"]);
			if (!$dbResult->Fetch())
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GB_ERROR_NO_USER_ID"), "ERROR_NO_USER_ID");
				return false;
			}
		}

		if ((is_set($arFields, "OPERATION_ID") || $ACTION=="ADD") && strlen($arFields["OPERATION_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GG_EMPTY_OPERATION_ID"), "EMPTY_OPERATION_ID");
			return false;
		}
		elseif (is_set($arFields, "OPERATION_ID") && !array_key_exists($arFields["OPERATION_ID"], $arSocNetUserOperations))
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["OPERATION_ID"], GetMessage("SONET_GG_ERROR_NO_OPERATION_ID")), "ERROR_NO_OPERATION_ID");
			return false;
		}

		if ((is_set($arFields, "RELATION_TYPE") || $ACTION=="ADD") && strlen($arFields["RELATION_TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GG_EMPTY_RELATION_TYPE"), "EMPTY_RELATION_TYPE");
			return false;
		}
		elseif (is_set($arFields, "RELATION_TYPE") && !in_array($arFields["RELATION_TYPE"], $arSocNetAllowedRelationsType))
		{
			$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["RELATION_TYPE"], GetMessage("SONET_GG_ERROR_NO_RELATION_TYPE")), "ERROR_NO_RELATION_TYPE");
			return false;
		}

		return True;
	}

	
	/**
	 * <p>Метод удаляет запись из базы данных.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Код записи.
	 *
	 *
	 *
	 * @return bool <p>True в случае успешного удаления и false - в противном случае.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/Delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_user_perms WHERE ID = ".$ID."", true);

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($userID))
			return false;

		$userID = IntVal($userID);
		$bSuccess = True;

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_user_perms WHERE USER_ID = ".$userID."", true);

		return $bSuccess;
	}

	
	/**
	 * <p>Метод изменяет параметры записи.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Код записи.
	 *
	 *
	 *
	 * @param array $arFields  Массив измененных параметров записи с ключами:<br> USER_ID - код
	 * пользователя,<br> OPERATION_ID - операция,<br> RELATION_TYPE - тип отношений между
	 * пользователями.
	 *
	 *
	 *
	 * @return int <p>Код записи в случае успешного выполнения и false - в случае
	 * ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/Update.php
	 * @author Bitrix
	 */
	public static function Update($ID, $arFields)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$arFields1 = array();
		foreach ($arFields as $key => $value)
		{
			if (substr($key, 0, 1) == "=")
			{
				$arFields1[substr($key, 1)] = $value;
				unset($arFields[$key]);
			}
		}

		if (!CSocNetUserPerms::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_user_perms", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_user_perms SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);
		}
		else
		{
			$ID = False;
		}

		return $ID;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	 * <p>Метод возвращает массив параметров записи.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Код записи.
	 *
	 *
	 *
	 * @return array <p>Массив с ключами:<br> ID - код записи,<br> USER_ID - код пользователя,<br>
	 * OPERATION_ID - операция,<br> RELATION_TYPE - тип отношений между
	 * пользователями.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/GetByID.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$dbResult = CSocNetUserPerms::GetList(Array(), Array("ID" => $ID));
		if ($arResult = $dbResult->GetNext())
		{
			return $arResult;
		}

		return False;
	}
	
	/***************************************/
	/**********  COMMON METHODS  ***********/
	/***************************************/
	
	/**
	 * <p>Метод возвращает необходимые права на выполнение заданной операции над профайлом заданного пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param int $userID  Код пользователя, к профайлу которого осуществляется доступ.
	 *
	 *
	 *
	 * @param string $operation  Операция.
	 *
	 *
	 *
	 * @return char <p>Права на доступ.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/GetOperationPerms.php
	 * @author Bitrix
	 */
	public static function GetOperationPerms($userID, $operation)
	{
		global $arSocNetUserOperations;
		static $arCachedUserPerms;

		if (
			is_array($userID) 
			&& !$arCachedUserPerms
		)
			$arCachedUserPerms = array();

		if (!is_array($userID))
		{
			$userID = IntVal($userID);
			if ($userID <= 0)
				return false;
		}

		$operation = StrToLower(Trim($operation));
		if (!array_key_exists($operation, $arSocNetUserOperations))
			return false;

		$arUserPerms = array();
		if (
			!is_array($userID)
			&& isset($GLOBALS["SONET_USER_PERMS_".$userID]) 
			&& is_array($GLOBALS["SONET_USER_PERMS_".$userID])
			&& !array_key_exists("SONET_USER_PERMS_".$userID, $_REQUEST)
		)
			$arUserPerms = $GLOBALS["SONET_USER_PERMS_".$userID];
		elseif (
			!is_array($userID)
			&& is_array($arCachedUserPerms)
			&& array_key_exists($userID, $arCachedUserPerms)
			&& is_array($arCachedUserPerms[$userID])
			&& !array_key_exists("SONET_USER_PERMS_".$userID, $_REQUEST)
		)
			$arUserPerms = $arCachedUserPerms[$userID];			
		else
		{
			$dbResult = CSocNetUserPerms::GetList(Array(), Array("USER_ID" => $userID));
			while ($arResult = $dbResult->Fetch())
			{
				if (!is_array($userID))
					$arUserPerms[$arResult["OPERATION_ID"]] = $arResult["RELATION_TYPE"];
				else
					$arCachedUserPerms[$arResult["USER_ID"]][$arResult["OPERATION_ID"]] = $arResult["RELATION_TYPE"];
			}
			if (!is_array($userID))
				$GLOBALS["SONET_USER_PERMS_".$userID] = $arUserPerms;
		}

		if (!is_array($userID))
		{
			if (!array_key_exists($operation, $arUserPerms))
				$toUserOperationPerms = $arSocNetUserOperations[$operation];
			else
				$toUserOperationPerms = $arUserPerms[$operation];

			return $toUserOperationPerms;
		}
		else
		{
			foreach ($userID as $user_id_tmp)
				if (!array_key_exists($user_id_tmp, $arCachedUserPerms))
					$arCachedUserPerms[$user_id_tmp] = array();

			return true;
		}
	}

	
	/**
	 * <p>Метод проверяет, может ли пользователь совершать указанную операцию над профайлом заданного пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param int $fromUserID  Код пользователя, права которого проверяются.
	 *
	 *
	 *
	 * @param int $toUserID  Код пользователя, к профайлу которого осуществляется доступ.
	 *
	 *
	 *
	 * @param string $operation  Операция.
	 *
	 *
	 *
	 * @param bool $bCurrentUserIsAdmin = false Является ли администратором пользователь, права которого
	 * проверяются.
	 *
	 *
	 *
	 * @return bool <p>True, если права на выполнение операции есть. Иначе - false.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/CanPerformOperation.php
	 * @author Bitrix
	 */
	public static function CanPerformOperation($fromUserID, $toUserID, $operation, $bCurrentUserIsAdmin = false)
	{
		global $arSocNetUserOperations;

		$fromUserID = IntVal($fromUserID);
		$toUserID = IntVal($toUserID);
		if ($toUserID <= 0)
			return false;
		$operation = StrToLower(Trim($operation));
		if (!array_key_exists($operation, $arSocNetUserOperations))
			return false;

// use no profile private permission restrictions at the extranet site
		if (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite())
			return true;

		if ($bCurrentUserIsAdmin)
			return true;
		if ($fromUserID == $toUserID)
			return true;

		$usersRelation = CSocNetUserRelations::GetRelation($fromUserID, $toUserID);

		if ($usersRelation == SONET_RELATIONS_BAN && !IsModuleInstalled("im"))
			return false;

		$toUserOperationPerms = CSocNetUserPerms::GetOperationPerms($toUserID, $operation);

		if ($toUserOperationPerms == SONET_RELATIONS_TYPE_NONE)
			return false;
		if ($toUserOperationPerms == SONET_RELATIONS_TYPE_ALL)
			return true;

		if ($toUserOperationPerms == SONET_RELATIONS_TYPE_AUTHORIZED)
		{
			if ($fromUserID > 0)
				return true;
			else
				return false;
		}

		if ($toUserOperationPerms == SONET_RELATIONS_TYPE_FRIENDS)
		{
			if (CSocNetUserRelations::IsFriends($fromUserID, $toUserID))
				return true;
			else
				return false;
		}

		if ($toUserOperationPerms == SONET_RELATIONS_TYPE_FRIENDS2)
		{
			if (CSocNetUserRelations::IsFriends2($fromUserID, $toUserID))
				return true;
			elseif (CSocNetUserRelations::IsFriends($fromUserID, $toUserID))
				return true;
			else
				return false;
		}

		return false;
	}

	
	/**
	 * <p>Метод инициализирует массив прав пользователя на операции над профайлом заданного пользователя.</p>
	 *
	 *
	 *
	 *
	 * @param int $currentUserID  Код пользователя, права которого проверяются.
	 *
	 *
	 *
	 * @param int $userID  Код пользователя, к профайлу которого осуществляется доступ.
	 *
	 *
	 *
	 * @param bool $bCurrentUserIsAdmin  Флаг, является ли администратором пользователь, права которого
	 * проверяются.
	 *
	 *
	 *
	 * @return array <p>Массив с ключами:<br> IsCurrentUser - флаг, осуществляется ли доступ к
	 * собственному профайлу, Relation - отношения между пользователями,
	 * Operations - массив операций: modifyuser - право на изменение профайла,
	 * viewcontacts - право на просмотр контактной информации, invitegroup -
	 * приглашение в группу, message - отправка персонального сообщения,
	 * viewfriends - просмотр друзей, viewgroups - просмотр групп, viewprofile - просмотр
	 * профиля. .</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/InitUserPerms.php
	 * @author Bitrix
	 */
	public static function InitUserPerms($currentUserID, $userID, $bCurrentUserIsAdmin)
	{
		global $arSocNetUserOperations, $USER;

		$arReturn = array();

		$currentUserID = IntVal($currentUserID);
		$userID = IntVal($userID);

		if ($userID <= 0)
			return false;

		$arReturn["Operations"] = array();
		if ($currentUserID <= 0)
		{
			$arReturn["IsCurrentUser"] = false;
			$arReturn["Relation"] = false;
			$arReturn["Operations"]["modifyuser"] = false;
			$arReturn["Operations"]["viewcontacts"] = false;
			foreach ($arSocNetUserOperations as $operation => $defPerm)
				$arReturn["Operations"][$operation] = CSocNetUserPerms::CanPerformOperation($currentUserID, $userID, $operation, false);
		}
		else
		{
			$arReturn["IsCurrentUser"] = ($currentUserID == $userID);
			if ($arReturn["IsCurrentUser"])
				$arReturn["Relation"] = false;
			else
				$arReturn["Relation"] = CSocNetUserRelations::GetRelation($currentUserID, $userID);

			if ($bCurrentUserIsAdmin || $arReturn["IsCurrentUser"])
			{
				$arReturn["Operations"]["modifyuser"] = true;
				$arReturn["Operations"]["viewcontacts"] = true;
				foreach ($arSocNetUserOperations as $operation => $defPerm)
					$arReturn["Operations"][$operation] = true;
			}
			else
			{
				$arReturn["Operations"]["modifyuser"] = false;
				if (CSocNetUser::IsFriendsAllowed())
					$arReturn["Operations"]["viewcontacts"] = ($arReturn["Relation"] == SONET_RELATIONS_FRIEND);
				else
					$arReturn["Operations"]["viewcontacts"] = true;
				foreach ($arSocNetUserOperations as $operation => $defPerm)
					$arReturn["Operations"][$operation] = CSocNetUserPerms::CanPerformOperation($currentUserID, $userID, $operation, false);
			}

			$arReturn["Operations"]["modifyuser_main"] = false;
			if ($arReturn["IsCurrentUser"])
			{
				if ($USER->CanDoOperation('edit_own_profile'))
					$arReturn["Operations"]["modifyuser_main"] = true;
			}
			elseif($USER->CanDoOperation('edit_all_users'))
				$arReturn["Operations"]["modifyuser_main"] = true;				
			elseif($USER->CanDoOperation('edit_subordinate_users'))
			{
				$arUserGroups = CUser::GetUserGroup($userID);

				if (array_key_exists("SONET_SUBORD_GROUPS_BY_USER_ID", $GLOBALS) && !array_key_exists("SONET_ALLOW_FRIENDS_CACHE", $_REQUEST))
					$arUserSubordinateGroups = $GLOBALS["SONET_SUBORD_GROUPS_BY_USER_ID"][$currentUserID];
				else
				{
					$arUserSubordinateGroups = Array(2);
					$arUserGroups_u = CUser::GetUserGroup($currentUserID);
					for ($j = 0,$len = count($arUserGroups_u); $j < $len; $j++)
					{
						$arSubordinateGroups = CGroup::GetSubordinateGroups($arUserGroups_u[$j]);
						$arUserSubordinateGroups = array_merge ($arUserSubordinateGroups, $arSubordinateGroups);
					}
					$arUserSubordinateGroups = array_unique($arUserSubordinateGroups);

					if (!array_key_exists("SONET_SUBORD_GROUPS_BY_USER_ID", $GLOBALS))
						$GLOBALS["SONET_SUBORD_GROUPS_BY_USER_ID"] = array();

					$GLOBALS["SONET_SUBORD_GROUPS_BY_USER_ID"][$currentUserID] = $arUserSubordinateGroups;
				}

				if (count(array_diff($arUserGroups, $arUserSubordinateGroups)) <= 0)
					$arReturn["Operations"]["modifyuser_main"] = true;						
			}
		}
		
		return $arReturn;
	}

	
	/**
	 * <p>Изменяет право на операцию, если таковое есть. Иначе добавляет новую запись.</p>
	 *
	 *
	 *
	 *
	 * @param int $userID  Код пользователя.
	 *
	 *
	 *
	 * @param string $feature  Название функционала.
	 *
	 *
	 *
	 * @param string $perm  Право.
	 *
	 *
	 *
	 * @return int <p>Код записи при успешном сохранении и false - в случае ошибки.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetUserPerms/SetPerm.php
	 * @author Bitrix
	 */
	public static function SetPerm($userID, $feature, $perm)
	{
		$userID = IntVal($userID);
		$feature = Trim($feature);
		$perm = Trim($perm);

		$dbResult = CSocNetUserPerms::GetList(
			array(),
			array(
				"USER_ID" => $userID,
				"OPERATION_ID" => $feature,
			),
			false,
			false,
			array("ID")
		);

		if ($arResult = $dbResult->Fetch())
			$r = CSocNetUserPerms::Update($arResult["ID"], array("RELATION_TYPE" => $perm));
		else
			$r = CSocNetUserPerms::Add(array("USER_ID" => $userID, "OPERATION_ID" => $feature, "RELATION_TYPE" => $perm));

		if (!$r)
		{
			$errorMessage = "";
			if ($e = $GLOBALS["APPLICATION"]->GetException())
				$errorMessage = $e->GetString();
			if (StrLen($errorMessage) <= 0)
				$errorMessage = GetMessage("SONET_GF_ERROR_SET").".";

			$GLOBALS["APPLICATION"]->ThrowException($errorMessage, "ERROR_SET_RECORD");
			return false;
		}
		elseif ($feature == "viewprofile")
			unset($GLOBALS["SONET_USER_PERMS_".$userID]);

		return $r;
	}
}
?>