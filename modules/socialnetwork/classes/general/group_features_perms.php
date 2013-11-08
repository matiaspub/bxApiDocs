<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["arSonetFeaturesPermsCache"] = array();


/**
 * <b>CSocNetFeaturesPerms</b> - класс для управления правами на доступ к дополнительному функционалу групп и пользователей.
 *
 *
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/index.php
 * @author Bitrix
 */
class CAllSocNetFeaturesPerms
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $arSocNetFeaturesSettings, $arSocNetAllowedRolesForFeaturesPerms, $arSocNetAllowedEntityTypes, $arSocNetAllowedRelationsType;

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "FEATURE_ID") || $ACTION=="ADD") && IntVal($arFields["FEATURE_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GFP_EMPTY_GROUP_FEATURE_ID"), "EMPTY_FEATURE_ID");
			return false;
		}
		elseif (is_set($arFields, "FEATURE_ID"))
		{
			$arResult = CSocNetFeatures::GetByID($arFields["FEATURE_ID"]);
			if ($arResult == false)
			{
				$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["FEATURE_ID"], GetMessage("SONET_GFP_ERROR_NO_GROUP_FEATURE_ID")), "ERROR_NO_FEATURE_ID");
				return false;
			}
		}

		$groupFeature = "";
		$groupFeatureType = "";

		if ((is_set($arFields, "OPERATION_ID") || $ACTION=="ADD") && StrLen($arFields["OPERATION_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GFP_EMPTY_OPERATION_ID"), "EMPTY_OPERATION_ID");
			return false;
		}
		elseif (is_set($arFields, "OPERATION_ID"))
		{
			$arFields["OPERATION_ID"] = strtolower($arFields["OPERATION_ID"]);

			if (is_set($arFields, "FEATURE_ID"))
			{
				$arGroupFeature = CSocNetFeatures::GetByID($arFields["FEATURE_ID"]);
				if ($arGroupFeature != false)
				{
					$groupFeature = $arGroupFeature["FEATURE"];
					$groupFeatureType = $arGroupFeature["ENTITY_TYPE"];
				}
			}
			elseif ($ACTION != "ADD" && IntVal($ID) > 0)
			{
				$dbGroupFeature = CSocNetFeaturesPerms::GetList(
					array(),
					array("ID" => $ID),
					false,
					false,
					array("FEATURE_FEATURE", "FEATURE_ENTITY_TYPE")
				);
				if ($arGroupFeature = $dbGroupFeature->Fetch())
				{
					$groupFeature = $arGroupFeature["FEATURE_FEATURE"];
					$groupFeatureType = $arGroupFeature["FEATURE_ENTITY_TYPE"];
				}
			}
			if (StrLen($groupFeature) <= 0 || !array_key_exists($groupFeature, $arSocNetFeaturesSettings))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GFP_BAD_OPERATION_ID"), "BAD_OPERATION_ID");
				return false;
			}

			if (!array_key_exists($arFields["OPERATION_ID"], $arSocNetFeaturesSettings[$groupFeature]["operations"]))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GFP_NO_OPERATION_ID"), "NO_OPERATION_ID");
				return false;
			}
		}

		if ((is_set($arFields, "ROLE") || $ACTION=="ADD") && strlen($arFields["ROLE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GFP_EMPTY_ROLE"), "EMPTY_ROLE");
			return false;
		}
		elseif (is_set($arFields, "ROLE"))
		{
			if (StrLen($groupFeatureType) <= 0)
			{
				if (is_set($arFields, "FEATURE_ID"))
				{
					$arGroupFeature = CSocNetFeatures::GetByID($arFields["FEATURE_ID"]);
					if ($arGroupFeature != false)
					{
						$groupFeature = $arGroupFeature["FEATURE"];
						$groupFeatureType = $arGroupFeature["ENTITY_TYPE"];
					}
				}
				elseif ($ACTION != "ADD" && IntVal($ID) > 0)
				{
					$dbGroupFeature = CSocNetFeaturesPerms::GetList(
						array(),
						array("ID" => $ID),
						false,
						false,
						array("FEATURE_FEATURE", "FEATURE_ENTITY_TYPE")
					);
					if ($arGroupFeature = $dbGroupFeature->Fetch())
					{
						$groupFeature = $arGroupFeature["FEATURE_FEATURE"];
						$groupFeatureType = $arGroupFeature["FEATURE_ENTITY_TYPE"];
					}
				}
			}
			if (StrLen($groupFeatureType) <= 0 || !in_array($groupFeatureType, $arSocNetAllowedEntityTypes))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_TYPE"), "BAD_TYPE");
				return false;
			}
			if ($groupFeatureType == SONET_ENTITY_GROUP)
			{
				if (!in_array($arFields["ROLE"], $arSocNetAllowedRolesForFeaturesPerms))
				{
					$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ROLE"], GetMessage("SONET_GFP_ERROR_NO_ROLE")), "ERROR_NO_SITE");
					return false;
				}
			}
			else
			{
				if (!in_array($arFields["ROLE"], $arSocNetAllowedRelationsType))
				{
					$GLOBALS["APPLICATION"]->ThrowException(str_replace("#ID#", $arFields["ROLE"], GetMessage("SONET_GFP_ERROR_NO_ROLE")), "ERROR_NO_SITE");
					return false;
				}
			}
		}

		return True;
	}

	
	/**
	 * <p>Удаляет право.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Идентификатор записи.
	 *
	 *
	 *
	 * @return bool <p>True в случае успешного удаления и false - в противном случае.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Delete.php
	 * @author Bitrix
	 */
	public static function Delete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetFeaturesPermsDelete");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$events = GetModuleEvents("socialnetwork", "OnSocNetFeaturesPermsDelete");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		if ($bSuccess)
		{
			$bSuccess = $DB->Query("DELETE FROM b_sonet_features2perms WHERE ID = ".$ID."", true);
			if ($bSuccess && defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_features2perms_".$ID);
		}

		return $bSuccess;
	}

	
	/**
	 * <p>Изменяет параметры права.</p> <p><b>Примечание</b>: для установки параметров права может так же использоваться метод <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/SetPerm.php">CSocNetFeaturesPerms::SetPerm</a>.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Идентификатор записи
	 *
	 *
	 *
	 * @param array $arFields  Массив новых значений параметров. Допустимые ключи:<br> FEATURE_ID - код
	 * дополнительного функционала,<br> OPERATION_ID - код операции,<br> ROLE - роль.
	 *
	 *
	 *
	 * @return int <p>Код измененной записи.</p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/SetPerm.php">CSocNetFeaturesPerms::SetPerm</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Add.php">CSocNetFeaturesPerms::Add</a></li>
	 * </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Update.php
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

		if (!CSocNetFeaturesPerms::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetFeaturesPermsUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, $arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_features2perms", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_features2perms SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			$events = GetModuleEvents("socialnetwork", "OnSocNetFeaturesPermsUpdate");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			if (defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_features2perms_".$ID);
		}
		else
			$ID = False;

		return $ID;
	}

	
	/**
	 * <p>Метод устанавливает права для дополнительного функционала. Если запись существует в базе данных, то она изменяется. Если запись не существует, то она добавляется.</p> <p><b>Примечание</b>: для добавления записи используется метод <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Add.php">CSocNetFeaturesPerms::Add</a>, обновляется методом <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Update.php">CSocNetFeaturesPerms::Update</a>.</p>
	 *
	 *
	 *
	 *
	 * @param int $featureID  Идентификатор дополнительного функционала.
	 *
	 *
	 *
	 * @param string $operation  Название операции.
	 *
	 *
	 *
	 * @param string $perm  Право на операцию.
	 *
	 *
	 *
	 * @return int <p>Возвращается идентификатор записи.</p>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * $idTmp = CSocNetFeatures::SetFeature(
	 * 	SONET_ENTITY_GROUP,
	 * 	$ID,
	 * 	"forum",
	 * 	true,
	 * 	"Обсуждения"
	 * );
	 * if ($idTmp)
	 * {
	 * 	$id1Tmp = CSocNetFeaturesPerms::SetPerm(
	 * 		$idTmp,
	 * 		"forum_answer",
	 * 		SONET_ROLES_MODERATOR
	 * 	);
	 * 	if (!$id1Tmp)
	 * 	{
	 * 		if ($e = $GLOBALS["APPLICATION"]-&gt;GetException())
	 * 			$errorMessage .= $e-&gt;GetString();
	 * 	}
	 * }
	 * else
	 * {
	 * 	if ($e = $GLOBALS["APPLICATION"]-&gt;GetException())
	 * 		$errorMessage .= $e-&gt;GetString();
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Add.php">CSocNetFeaturesPerms::Add</a></li>
	 * <li><a
	 * href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/Update.php">CSocNetFeaturesPerms::Update</a></li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/SetPerm.php
	 * @author Bitrix
	 */
	public static function SetPerm($featureID, $operation, $perm)
	{
		$featureID = IntVal($featureID);
		$operation = Trim($operation);
		$perm = Trim($perm);

		$dbResult = CSocNetFeaturesPerms::GetList(
			array(),
			array(
				"FEATURE_ID" => $featureID,
				"OPERATION_ID" => $operation,
			),
			false,
			false,
			array("ID", "FEATURE_ENTITY_TYPE", "FEATURE_ENTITY_ID", "FEATURE_FEATURE", "OPERATION_ID", "ROLE")
		);

		if ($arResult = $dbResult->Fetch())
			$r = CSocNetFeaturesPerms::Update($arResult["ID"], array("ROLE" => $perm));
		else
			$r = CSocNetFeaturesPerms::Add(array("FEATURE_ID" => $featureID, "OPERATION_ID" => $operation, "ROLE" => $perm));

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
		else
		{

			if (!$arResult)
			{
				$arFeature = CSocNetFeatures::GetByID($featureID);
				$entity_type = $arFeature["ENTITY_TYPE"];
				$entity_id = $arFeature["ENTITY_ID"];
				$feature = $arFeature["FEATURE"];
			}
			else
			{
				$entity_type = $arResult["FEATURE_ENTITY_TYPE"];
				$entity_id = $arResult["FEATURE_ENTITY_ID"];
				$feature = $arResult["FEATURE_FEATURE"];
			}

			if(empty($arResult) || $arResult["ROLE"] != $perm)
			{
				if($arResult && ($arResult["ROLE"] != $perm))
					CSocNetSearch::SetFeaturePermissions($entity_type, $entity_id, $feature, $arResult["OPERATION_ID"], $perm);
				else
					CSocNetSearch::SetFeaturePermissions($entity_type, $entity_id, $feature, $operation, $perm);
			}

			if (
				!in_array($feature, array("tasks", "files", "blog"))
				&& is_array($GLOBALS["arSocNetFeaturesSettings"][$feature]["subscribe_events"]))
			{
				$arEventsTmp = array_keys($GLOBALS["arSocNetFeaturesSettings"][$feature]["subscribe_events"]);
				$rsLog = CSocNetLog::GetList(
					array(), 
					array(
						"ENTITY_TYPE" => $entity_type,
						"ENTITY_ID" => $entity_id,
						"EVENT_ID" => $arEventsTmp
					), 
					false, 
					false, 
					array("ID", "EVENT_ID")
				);
				while($arLog = $rsLog->Fetch())
				{
					CSocNetLogRights::DeleteByLogID($arLog["ID"]);
					CSocNetLogRights::SetForSonet(
						$arLog["ID"], 
						$entity_type, 
						$entity_id, 
						$feature, 
						$GLOBALS["arSocNetFeaturesSettings"][$feature]["subscribe_events"][$arLog["EVENT_ID"]]["OPERATION"]
					);
				}
			}
		}

		return $r;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	 * <p>Возвращает параметры права.</p>
	 *
	 *
	 *
	 *
	 * @param int $id  Идентификатор записи
	 *
	 *
	 *
	 * @return array <p>Возвращается массив с ключами:<br> ID - код записи,<br> FEATURE_ID - код
	 * дополнительного функционала,<br> OPERATION_ID - код операции,<br> ROLE -
	 * роль.</p>
	 *
	 *
	 * <h4>See Also</h4> 
	 * <ul> <li> <a
	 * href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/GetList.php">CSocNetFeaturesPerms::GetList</a>
	 * </li> </ul>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/GetByID.php
	 * @author Bitrix
	 */
	public static function GetByID($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$dbResult = CSocNetFeaturesPerms::GetList(Array(), Array("ID" => $ID));
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
	 * <p>Проверяет, имеет ли текущий пользователь право на совершение операции.</p>
	 *
	 *
	 *
	 *
	 * @param char $type  Тип объекта: SONET_ENTITY_GROUP - группа, SONET_ENTITY_USER - пользователь.
	 *
	 *
	 *
	 * @param int $id  Код объекта (пользователя или группы).
	 *
	 *
	 *
	 * @param string $feature  Название дополнительного функционала.
	 *
	 *
	 *
	 * @param string $operation  Название операции.
	 *
	 *
	 *
	 * @return bool <p>True, если текущий пользователь имеет право на совершение
	 * операции. Иначе - false.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/CurrentUserCanPerformOperation.php
	 * @author Bitrix
	 */
	public static function CurrentUserCanPerformOperation($type, $id, $feature, $operation, $site_id = SITE_ID)
	{
		$userID = 0;
		if (is_object($GLOBALS["USER"]) && $GLOBALS["USER"]->IsAuthorized())
			$userID = IntVal($GLOBALS["USER"]->GetID());

		$bCurrentUserIsAdmin = CSocNetUser::IsCurrentUserModuleAdmin($site_id);

		return CSocNetFeaturesPerms::CanPerformOperation($userID, $type, $id, $feature, $operation, $bCurrentUserIsAdmin);
	}

	
	/**
	 * <p>Метод проверяет, может ли указанный пользователь совершать указанное действие над указанным дополнительным функционалом. Например, метод может проверить, может ли указанный пользователь добавлять записи в отчеты указанной рабочей группы.</p>
	 *
	 *
	 *
	 *
	 * @param int $userID  Код пользователя, права которого проверяются.
	 *
	 *
	 *
	 * @param char $type  Тип объекта: SONET_ENTITY_GROUP - группа, SONET_ENTITY_USER - пользователь.
	 *
	 *
	 *
	 * @param mixed $id  Код объекта (пользователя или группы), либо (с версии 8.6.4) массив
	 * кодов объектов.
	 *
	 *
	 *
	 * @param string $feature  Название дополнительного функционала.
	 *
	 *
	 *
	 * @param string $operation  Название операции.
	 *
	 *
	 *
	 * @param bool $bUserIsAdmin = false Является ли пользователь администратором сайта или модуля
	 * социальной сети.
	 *
	 *
	 *
	 * @return mixed <p>Если в параметре id передано скалярное значение, то метод
	 * возвращает true если пользователь имеет права на указанную
	 * операцию и false - в обратном случае. Если (с версии 8.6.4) в параметре id
	 * передан массив кодов объектов, то возвращается ассоциативный
	 * массив, ключами для которого являются коды объектов, а значениями
	 * - true/false по вышеописанной логике.</p><h4>Стандартный дополнительный
	 * функционал и его операции</h4><p> </p><ul> <li>forum - форум <ul> <li>full - полный
	 * доступ</li> <li>newtopic - создание новой темы</li> <li>answer - ответ в
	 * существующей теме</li> <li>view - просмотр</li> </ul> </li> <li>photo - фотогалерея
	 * <ul> <li>write - полный доступ</li> <li>view - просмотр</li> </ul> </li> <li>calendar -
	 * календарь <ul> <li>write - полный доступ</li> <li>view - просмотр</li> </ul> </li>
	 * <li>tasks - задачи <ul> <li>view_all - просмотр всех задач</li> <li>create_tasks -
	 * создание новых задач</li> <li>delete_tasks - удаление новых задач</li>
	 * <li>modify_folders - изменение папок задач</li> </ul> </li> <li>files - файлы <ul> <li>write -
	 * полный доступ</li> <li>write_limited - запись с ограничениями</li> <li>view -
	 * просмотр</li> </ul> </li> <li>blog - блоги <ul> <li>view_post - просмотр сообщений</li>
	 * <li>write_post - создание сообщений</li> <li>full_post - полный доступ</li>
	 * <li>view_comment - просмотр комментариев</li> <li>write_comment - создание
	 * комментариев</li> <li>full_comment - полный доступ к комментариям</li> </ul> </li>
	 * </ul><a name="examples"></a>
	 *
	 *
	 * <h4>Example</h4> 
	 * <pre>
	 * &lt;?
	 * if (CSocNetFeaturesPerms::CanPerformOperation($GLOBALS["USER"]-&gt;GetID(), SONET_ENTITY_GROUP, $ID, "blog", "write_post"))
	 * {
	 *    // Текущий пользователь может писать сообщения в блог группы $ID
	 * }
	 * ?&gt;
	 * </pre>
	 *
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/CanPerformOperation.php
	 * @author Bitrix
	 */
	public static function CanPerformOperation($userID, $type, $id, $feature, $operation, $bCurrentUserIsAdmin = false)
	{
		global $arSocNetFeaturesSettings, $arSocNetAllowedEntityTypes;

		$userID = IntVal($userID);

		if ((is_array($id) && count($id) <= 0) || (!is_array($id) && $id <= 0))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		$type = Trim($type);
		if ((StrLen($type) <= 0) || !in_array($type, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_EMPTY_TYPE");
			return false;
		}

		$featureOperationPerms = CSocNetFeaturesPerms::GetOperationPerm($type, $id, $feature, $operation);

		if ($type == SONET_ENTITY_GROUP)
		{
			if (is_array($id))
			{
				$arGroupToGet = array();
				foreach($id as $group_id)
				{
					if ($featureOperationPerms[$group_id] == false)
						$arReturn[$group_id] = false;
					else
						$arGroupToGet[] = $group_id;
				}

				$userRoleInGroup = CSocNetUserToGroup::GetUserRole($userID, $arGroupToGet);

				$arGroupToGet = array();
				if (is_array($userRoleInGroup))
				{
					foreach($userRoleInGroup as $group_id => $role)
					{
						if ($userRoleInGroup[$group_id] == SONET_ROLES_BAN)
							$arReturn[$group_id] = false;
						else
							$arGroupToGet[] = $group_id;
					}
				}

				if (
					(is_array($arGroupToGet) && count($arGroupToGet) <= 0)
					|| (!is_array($arGroupToGet) && intval($arGroupToGet) <= 0)
				)
				{
					$arReturn = array();
					foreach($id as $group_id)
						$arReturn[$group_id] = false;
					return $arReturn;
				}

				$resGroupTmp = CSocNetGroup::GetList(array("ID"=>"ASC"), array("ID"=>$arGroupToGet));
				while ($arGroupTmp = $resGroupTmp->Fetch())
				{
					if ($arGroupTmp["CLOSED"] == "Y" && !in_array($operation, $arSocNetFeaturesSettings[$feature]["minoperation"])):
						if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
						{
							$arReturn[$arGroupTmp["ID"]] = false;
							continue;
						}
						else
							$featureOperationPerms[$arGroupTmp["ID"]] = SONET_ROLES_OWNER;
					endif;

					if ($bCurrentUserIsAdmin)
					{
						$arReturn[$arGroupTmp["ID"]] = true;
						continue;
					}

					if ($featureOperationPerms[$arGroupTmp["ID"]] == SONET_ROLES_ALL)
					{
						if ($arGroupTmp["VISIBLE"] == "N")
							$featureOperationPerms[$arGroupTmp["ID"]] = SONET_ROLES_USER;
						else
						{
							$arReturn[$arGroupTmp["ID"]] = true;
							continue;
						}
					}

					if ($featureOperationPerms[$arGroupTmp["ID"]] == SONET_ROLES_AUTHORIZED)
					{
						if ($userID > 0)
						{
							$arReturn[$arGroupTmp["ID"]] = true;
							continue;
						}
						else
						{
							$arReturn[$arGroupTmp["ID"]] = false;
							continue;
						}
					}

					if ($userRoleInGroup[$arGroupTmp["ID"]] == false)
					{
						$arReturn[$arGroupTmp["ID"]] = false;
						continue;
					}

					if ($featureOperationPerms[$arGroupTmp["ID"]] == SONET_ROLES_MODERATOR)
					{
						if ($userRoleInGroup[$arGroupTmp["ID"]] == SONET_ROLES_MODERATOR || $userRoleInGroup[$arGroupTmp["ID"]] == SONET_ROLES_OWNER)
						{
							$arReturn[$arGroupTmp["ID"]] = true;
							continue;
						}
						else
						{
							$arReturn[$arGroupTmp["ID"]] = false;
							continue;
						}
					}
					elseif ($featureOperationPerms[$arGroupTmp["ID"]] == SONET_ROLES_USER)
					{
						if ($userRoleInGroup[$arGroupTmp["ID"]] == SONET_ROLES_MODERATOR || $userRoleInGroup[$arGroupTmp["ID"]] == SONET_ROLES_OWNER || $userRoleInGroup[$arGroupTmp["ID"]] == SONET_ROLES_USER)
						{
							$arReturn[$arGroupTmp["ID"]] = true;
							continue;
						}
						else
						{
							$arReturn[$arGroupTmp["ID"]] = false;
							continue;
						}
					}
					elseif ($featureOperationPerms[$arGroupTmp["ID"]] == SONET_ROLES_OWNER)
					{
						if ($userRoleInGroup[$arGroupTmp["ID"]] == SONET_ROLES_OWNER)
						{
							$arReturn[$arGroupTmp["ID"]] = true;
							continue;
						}
						else
						{
							$arReturn[$arGroupTmp["ID"]] = false;
							continue;
						}
					}
				}

				return $arReturn;

			}
			else // not array of groups
			{
				$group_id = IntVal($id);

				if ($featureOperationPerms == false)
					return false;

				$userRoleInGroup = CSocNetUserToGroup::GetUserRole($userID, $id);
				if ($userRoleInGroup == SONET_ROLES_BAN)
					return false;

				$arGroupTmp = CSocNetGroup::GetByID($id);

				if ($arGroupTmp["CLOSED"] == "Y" && !in_array($operation, $arSocNetFeaturesSettings[$feature]["minoperation"])):
					if (COption::GetOptionString("socialnetwork", "work_with_closed_groups", "N") != "Y")
						return false;
					else
						$featureOperationPerms = SONET_ROLES_OWNER;
				endif;


				if ($bCurrentUserIsAdmin)
					return true;

				if ($featureOperationPerms == SONET_ROLES_ALL)
				{
					if ($arGroupTmp["VISIBLE"] == "N")
						$featureOperationPerms = SONET_ROLES_USER;
					else
						return true;
				}

				if ($featureOperationPerms == SONET_ROLES_AUTHORIZED)
				{
					if ($userID > 0)
						return true;
					else
						return false;
				}

				if ($userRoleInGroup == false)
					return false;

				if ($featureOperationPerms == SONET_ROLES_MODERATOR)
				{
					if ($userRoleInGroup == SONET_ROLES_MODERATOR || $userRoleInGroup == SONET_ROLES_OWNER)
						return true;
					else
						return false;
				}
				elseif ($featureOperationPerms == SONET_ROLES_USER)
				{
					if ($userRoleInGroup == SONET_ROLES_MODERATOR || $userRoleInGroup == SONET_ROLES_OWNER || $userRoleInGroup == SONET_ROLES_USER)
						return true;
					else
						return false;
				}
				elseif ($featureOperationPerms == SONET_ROLES_OWNER)
				{
					if ($userRoleInGroup == SONET_ROLES_OWNER)
						return true;
					else
						return false;
				}
			}
		}
		else // user
		{
			if (is_array($id))
			{

				foreach($id as $entity_id)
				{

					if ($featureOperationPerms[$entity_id] == false)
					{
						$arReturn[$entity_id] = false;
						continue;
					}

					$usersRelation = CSocNetUserRelations::GetRelation($userID, $entity_id);

					if ($type == SONET_ENTITY_USER && $userID == $entity_id)
					{
						$arReturn[$entity_id] = true;
						continue;
					}

					if ($bCurrentUserIsAdmin)
					{
						$arReturn[$entity_id] = true;
						continue;
					}

					if ($userID == $entity_id)
					{
						$arReturn[$entity_id] = true;
						continue;
					}

					if ($usersRelation == SONET_RELATIONS_BAN)
					{
						if (!IsModuleInstalled("im"))
						{
							$arReturn[$entity_id] = false;
							continue;
						}
					}

					if ($featureOperationPerms[$entity_id] == SONET_RELATIONS_TYPE_NONE)
					{
						$arReturn[$entity_id] = false;
						continue;
					}

					if ($featureOperationPerms[$entity_id] == SONET_RELATIONS_TYPE_ALL)
					{
						$arReturn[$entity_id] = true;
						continue;
					}

					if ($featureOperationPerms[$entity_id] == SONET_RELATIONS_TYPE_AUTHORIZED)
					{
						if ($userID > 0)
							$arReturn[$entity_id] = true;
						else
							$arReturn[$entity_id] = false;
						continue;
					}

					if ($featureOperationPerms[$entity_id] == SONET_RELATIONS_TYPE_FRIENDS)
					{
						if (CSocNetUserRelations::IsFriends($userID, $entity_id))
						{
							$arReturn[$entity_id] = true;
							continue;
						}
						else
						{
							$arReturn[$entity_id] = false;
							continue;
						}
					}

					if ($featureOperationPerms[$entity_id] == SONET_RELATIONS_TYPE_FRIENDS2)
					{
						if (CSocNetUserRelations::IsFriends($userID, $entity_id))
						{
							$arReturn[$entity_id] = true;
							continue;
						}
						elseif (CSocNetUserRelations::IsFriends2($userID, $entity_id))
						{
							$arReturn[$entity_id] = true;
							continue;
						}
						else
						{
							$arReturn[$entity_id] = false;
							continue;
						}
					}
				}

				return $arReturn;
			}
			else // not array
			{

				if ($featureOperationPerms == false)
					return false;

				if ($type == SONET_ENTITY_USER && $userID == $id)
					return true;

				if ($bCurrentUserIsAdmin)
					return true;

				if ($userID == $id)
					return true;

				$usersRelation = CSocNetUserRelations::GetRelation($userID, $id);
				if ($usersRelation == SONET_RELATIONS_BAN && !IsModuleInstalled("im"))
					return false;

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_NONE)
					return false;

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_ALL)
					return true;

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_AUTHORIZED)
				{
					if ($userID > 0)
						return true;
					else
						return false;
				}

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_FRIENDS)
				{
					if (CSocNetUserRelations::IsFriends($userID, $id))
						return true;
					else
						return false;
				}

				if ($featureOperationPerms == SONET_RELATIONS_TYPE_FRIENDS2)
				{
					if (CSocNetUserRelations::IsFriends($userID, $id))
						return true;
					elseif (CSocNetUserRelations::IsFriends2($userID, $id))
						return true;
					else
						return false;
				}
			}

		}

		return false;
	}

	
	/**
	 * <p>Возвращает права на операцию.</p>
	 *
	 *
	 *
	 *
	 * @param char $type  Тип объекта: SONET_ENTITY_GROUP - группа, SONET_ENTITY_USER - пользователь.
	 *
	 *
	 *
	 * @param mixed $id  Код объекта (пользователя или группы), либо (с версии 8.6.4) массив
	 * кодов объектов.
	 *
	 *
	 *
	 * @param string $feature  Название дополнительного функционала.
	 *
	 *
	 *
	 * @param string $operation  Название операции.
	 *
	 *
	 *
	 * @return mixed <p>Строка, содержащая право на операцию. Если (с версии 8.6.4) в
	 * параметре id передан массив кодов объектов, то возвращается
	 * ассоциативный массив, ключами для которого являются коды
	 * объектов, а значениями - права на операцию по вышеописанной
	 * логике.</p>
	 *
	 * @static
	 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/CSocNetFeaturesPerms/GetOperationPerm.php
	 * @author Bitrix
	 */
	public static function GetOperationPerm($type, $id, $feature, $operation)
	{
		global $arSocNetFeaturesSettings, $arSocNetAllowedEntityTypes;

		$type = Trim($type);
		if ((StrLen($type) <= 0) || !in_array($type, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_EMPTY_TYPE");
			if (is_array($id))
			{
				$arReturn = array();
				foreach($id as $TmpGroupID)
					$arReturn[$TmpGroupID] = false;
				return $arReturn;
			}
			else
				return false;
		}

		$feature = StrToLower(Trim($feature));
		if (StrLen($feature) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_FEATURE_ID"), "ERROR_EMPTY_FEATURE_ID");
			if (is_array($id))
			{
				$arReturn = array();
				foreach($id as $TmpGroupID)
					$arReturn[$TmpGroupID] = false;
				return $arReturn;
			}
			else
				return false;
		}

		if (
			!array_key_exists($feature, $arSocNetFeaturesSettings) 
			|| !array_key_exists("allowed", $arSocNetFeaturesSettings[$feature])
			|| !in_array($type, $arSocNetFeaturesSettings[$feature]["allowed"]))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE_ID");
			if (is_array($id))
			{
				$arReturn = array();
				foreach($id as $TmpGroupID)
					$arReturn[$TmpGroupID] = false;
				return $arReturn;
			}
			else
				return false;
		}

		$operation = StrToLower(Trim($operation));
		if (
			!array_key_exists("operations", $arSocNetFeaturesSettings[$feature])
			|| !array_key_exists($operation, $arSocNetFeaturesSettings[$feature]["operations"])
		)
		{
			if (is_array($id))
			{
				$arReturn = array();
				foreach($id as $TmpGroupID)
					$arReturn[$TmpGroupID] = false;
				return $arReturn;
			}
			else
				return false;
		}

		global $arSonetFeaturesPermsCache;
		if (!isset($arSonetFeaturesPermsCache) || !is_array($arSonetFeaturesPermsCache))
			$arSonetFeaturesPermsCache = array();

		if (is_array($id))
		{
			$arFeaturesPerms = array();
			$arGroupToGet = array();
			foreach($id as $TmpGroupID)
			{
				$arFeaturesPerms[$TmpGroupID] = array();

				if (!array_key_exists($type."_".$TmpGroupID, $arSonetFeaturesPermsCache))
					$arGroupToGet[] = $TmpGroupID;
				else
					$arFeaturesPerms[$TmpGroupID] = $arSonetFeaturesPermsCache[$type."_".$TmpGroupID];
			}

			if (!empty($arGroupToGet))
			{
				$dbResult = CSocNetFeaturesPerms::GetList(
					Array(),
					Array("FEATURE_ENTITY_ID" => $arGroupToGet, "FEATURE_ENTITY_TYPE" => $type, "GROUP_FEATURE_ACTIVE" => "Y"),
					false,
					false,
					array("OPERATION_ID", "FEATURE_ENTITY_ID", "FEATURE_FEATURE", "ROLE")
				);
				while ($arResult = $dbResult->Fetch())
				{
					if (!array_key_exists($arResult["FEATURE_ENTITY_ID"], $arFeaturesPerms) || !array_key_exists($arResult["FEATURE_FEATURE"], $arFeaturesPerms[$arResult["FEATURE_ENTITY_ID"]]))
						$arFeaturesPerms[$arResult["FEATURE_ENTITY_ID"]][$arResult["FEATURE_FEATURE"]] = array();
					$arFeaturesPerms[$arResult["FEATURE_ENTITY_ID"]][$arResult["FEATURE_FEATURE"]][$arResult["OPERATION_ID"]] = $arResult["ROLE"];
				}
			}

			$arReturn = array();

			foreach($id as $TmpEntityID)
			{
				$arSonetFeaturesPermsCache[$type."_".$TmpGroupID] = $arFeaturesPerms[$TmpEntityID];

				if ($type == SONET_ENTITY_GROUP)
				{
					$featureOperationPerms = SONET_ROLES_OWNER;

					if (!array_key_exists($feature, $arFeaturesPerms[$TmpEntityID]))
						$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_GROUP];
					elseif (!array_key_exists($operation, $arFeaturesPerms[$TmpEntityID][$feature]))
						$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_GROUP];
					else
						$featureOperationPerms = $arFeaturesPerms[$TmpEntityID][$feature][$operation];
				}
				else
				{
					$featureOperationPerms = SONET_RELATIONS_TYPE_NONE;

					if (!array_key_exists($feature, $arFeaturesPerms[$TmpEntityID]))
						$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_USER];
					elseif (!array_key_exists($operation, $arFeaturesPerms[$TmpEntityID][$feature]))
						$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_USER];
					else
						$featureOperationPerms = $arFeaturesPerms[$TmpEntityID][$feature][$operation];
				}

				$arReturn[$TmpEntityID] = $featureOperationPerms;

			}

			return $arReturn;
		}
		else // not array
		{
			$id = IntVal($id);
			if ($id <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
				return false;
			}

			$arFeaturesPerms = array();
			if (array_key_exists($type."_".$id, $arSonetFeaturesPermsCache))
				$arFeaturesPerms = $arSonetFeaturesPermsCache[$type."_".$id];
			else
			{
				$cache = new CPHPCache;
				$cache_time = 31536000;
				$cache_id = "entity_"."_".$type."_".$id;
				$cache_path = "/sonet/features_perms/".$type."_".$id."/";

				$arTmp = array();

				if ($cache->InitCache($cache_time, $cache_id, $cache_path))
				{
					$arCacheVars = $cache->GetVars();
					$arTmp = $arCacheVars["RESULT"];
				}
				else
				{
					$cache->StartDataCache($cache_time, $cache_id, $cache_path);
					if (defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);

					$dbResult = CSocNetFeaturesPerms::GetList(
						Array(),
						Array("FEATURE_ENTITY_ID" => $id, "FEATURE_ENTITY_TYPE" => $type, "GROUP_FEATURE_ACTIVE" => "Y"),
						false,
						false,
						array("ID", "OPERATION_ID", "FEATURE_ID", "FEATURE_FEATURE", "ROLE")
					);
					while ($arResult = $dbResult->Fetch())
					{
						if (defined("BX_COMP_MANAGED_CACHE"))
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_features2perms_".$arResult["ID"]);
						$arTmp[] = $arResult;
					}

					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$dbResult = CSocNetFeatures::GetList(
							Array(),
							Array("ENTITY_ID" => $id, "ENTITY_TYPE" => $type),
							false,
							false,
							array("ID")
						);
						while ($arResult = $dbResult->Fetch())
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_feature_".$arResult["ID"]);
					}

					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						if ($type == SONET_ENTITY_GROUP)
						{
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group_".$id);
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_group");
						}
						elseif ($type == SONET_ENTITY_USER)
							$GLOBALS["CACHE_MANAGER"]->RegisterTag("USER_CARD_".intval($id / TAGGED_user_card_size));

						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_features_".$type."_".$id);
					}

					$arCacheData = Array(
						"RESULT" => $arTmp
					);

					if(defined("BX_COMP_MANAGED_CACHE"))
						$GLOBALS["CACHE_MANAGER"]->EndTagCache();

					$cache->EndDataCache($arCacheData);
				}

				foreach($arTmp as $arResult)
				{
					if (!array_key_exists($arResult["FEATURE_FEATURE"], $arFeaturesPerms))
						$arFeaturesPerms[$arResult["FEATURE_FEATURE"]] = array();
					$arFeaturesPerms[$arResult["FEATURE_FEATURE"]][$arResult["OPERATION_ID"]] = $arResult["ROLE"];
				}
				$arSonetFeaturesPermsCache[$type."_".$id] = $arFeaturesPerms;
			}

			if ($type == SONET_ENTITY_GROUP)
			{
				$featureOperationPerms = SONET_ROLES_OWNER;

				if (!array_key_exists($feature, $arFeaturesPerms))
					$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_GROUP];
				elseif (!array_key_exists($operation, $arFeaturesPerms[$feature]))
					$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_GROUP];
				else
					$featureOperationPerms = $arFeaturesPerms[$feature][$operation];
			}
			else
			{
				$featureOperationPerms = SONET_RELATIONS_TYPE_NONE;

				if (!array_key_exists($feature, $arFeaturesPerms))
					$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_USER];
				elseif (!array_key_exists($operation, $arFeaturesPerms[$feature]))
					$featureOperationPerms = $arSocNetFeaturesSettings[$feature]["operations"][$operation][SONET_ENTITY_USER];
				else
					$featureOperationPerms = $arFeaturesPerms[$feature][$operation];
			}

			return $featureOperationPerms;
		}

	}
}
?>