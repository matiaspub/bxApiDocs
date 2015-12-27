<?
IncludeModuleLangFile(__FILE__);

$GLOBALS["SONET_FEATURES_CACHE"] = array();


/**
 * <b>CSocNetFeatures</b> - класс для работы с дополнительным функционалом групп и пользователей социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/index.php
 * @author Bitrix
 */
class CAllSocNetFeatures
{
	/***************************************/
	/********  DATA MODIFICATION  **********/
	/***************************************/
	public static function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $DB, $arSocNetAllowedEntityTypes;

		if ($ACTION != "ADD" && IntVal($ID) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException("System error 870164", "ERROR");
			return false;
		}

		if ((is_set($arFields, "ENTITY_TYPE") || $ACTION=="ADD") && StrLen($arFields["ENTITY_TYPE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_TYPE"), "EMPTY_ENTITY_TYPE");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_TYPE"))
		{
			if (!in_array($arFields["ENTITY_TYPE"], $arSocNetAllowedEntityTypes))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_NO_ENTITY_TYPE");
				return false;
			}
		}

		if ((is_set($arFields, "ENTITY_ID") || $ACTION=="ADD") && IntVal($arFields["ENTITY_ID"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_ID"), "EMPTY_ENTITY_ID");
			return false;
		}
		elseif (is_set($arFields, "ENTITY_ID"))
		{
			$type = "";
			if (is_set($arFields, "ENTITY_TYPE"))
			{
				$type = $arFields["ENTITY_TYPE"];
			}
			elseif ($ACTION != "ADD")
			{
				$arRe = CSocNetFeatures::GetByID($ID);
				if ($arRe)
					$type = $arRe["ENTITY_TYPE"];
			}
			if (StrLen($type) <= 0)
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_CALC_ENTITY_TYPE"), "ERROR_CALC_ENTITY_TYPE");
				return false;
			}

			if ($type == SONET_ENTITY_GROUP)
			{
				$arResult = CSocNetGroup::GetByID($arFields["ENTITY_ID"]);
				if ($arResult == false)
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
			elseif ($type == SONET_ENTITY_USER)
			{
				$dbResult = CUser::GetByID($arFields["ENTITY_ID"]);
				if (!$dbResult->Fetch())
				{
					$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_ID"), "ERROR_NO_ENTITY_ID");
					return false;
				}
			}
			else
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_CALC_ENTITY_TYPE"), "ERROR_CALC_ENTITY_TYPE");
				return false;
			}
		}

		if ((is_set($arFields, "FEATURE") || $ACTION=="ADD") && StrLen($arFields["FEATURE"]) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_FEATURE_ID"), "EMPTY_FEATURE");
			return false;
		}
		elseif (is_set($arFields, "FEATURE"))
		{
			$arFields["FEATURE"] = strtolower($arFields["FEATURE"]);
			$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

			if (!array_key_exists($arFields["FEATURE"], $arSocNetFeaturesSettings))
			{
				$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE");
				return false;
			}
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

		if ((is_set($arFields, "ACTIVE") || $ACTION=="ADD") && $arFields["ACTIVE"] != "Y" && $arFields["ACTIVE"] != "N")
			$arFields["ACTIVE"] = "Y";

		return True;
	}

	
	/**
	* <p>Метод удаляет запись дополнительного функционала из базы.</p>
	*
	*
	* @param int $ID  Код записи
	*
	* @return bool <p>True в случае успешного удаления и false - в противном случае.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/Delete.php
	* @author Bitrix
	*/
	public static function Delete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetFeatures");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
				return false;

		$events = GetModuleEvents("socialnetwork", "OnSocNetFeatures");
		while ($arEvent = $events->Fetch())
			ExecuteModuleEventEx($arEvent, array($ID));

		$DB->StartTransaction();

		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_features2perms WHERE FEATURE_ID = ".$ID."", true);
		if ($bSuccess)
			$bSuccess = $DB->Query("DELETE FROM b_sonet_features WHERE ID = ".$ID."", true);

		if ($bSuccess)
		{
			$DB->Commit();
			if (defined("BX_COMP_MANAGED_CACHE"))
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_feature_".$ID);
		}
		else
			$DB->Rollback();

		return $bSuccess;
	}

	public static function DeleteNoDemand($userID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($userID))
			return false;

		$userID = IntVal($userID);

		$dbResult = CSocNetFeatures::GetList(array(), array("ENTITY_TYPE" => "U", "ENTITY_ID" => $userID), false, false, array("ID"));
		while ($arResult = $dbResult->Fetch())
		{
			$DB->Query("DELETE FROM b_sonet_features2perms WHERE FEATURE_ID = ".$arResult["ID"]."", true);
			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_feature_".$arResult["ID"]);
			}
		}

		$DB->Query("DELETE FROM b_sonet_features WHERE ENTITY_TYPE = 'U' AND ENTITY_ID = ".$userID."", true);

		return true;
	}

	
	/**
	* <p>Изменяет параметры сохраненного дополнительного функционала.</p>
	*
	*
	* @param int $ID  Код записи
	*
	* @param array $arFields  Массив новых параметров </htm
	*
	* @return int <p>Код записи в случае успешного изменения и false - в случае
	* ошибки.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/Update.php
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

		if (!CSocNetFeatures::CheckFields("UPDATE", $arFields, $ID))
			return false;

		$db_events = GetModuleEvents("socialnetwork", "OnBeforeSocNetFeaturesUpdate");
		while ($arEvent = $db_events->Fetch())
			if (ExecuteModuleEventEx($arEvent, array($ID, $arFields))===false)
				return false;

		$strUpdate = $DB->PrepareUpdate("b_sonet_features", $arFields);

		foreach ($arFields1 as $key => $value)
		{
			if (strlen($strUpdate) > 0)
				$strUpdate .= ", ";
			$strUpdate .= $key."=".$value." ";
		}

		if (strlen($strUpdate) > 0)
		{
			$strSql =
				"UPDATE b_sonet_features SET ".
				"	".$strUpdate." ".
				"WHERE ID = ".$ID." ";
			$DB->Query($strSql, False, "File: ".__FILE__."<br>Line: ".__LINE__);

			if (array_key_exists("ENTITY_TYPE", $arFields) && array_key_exists("ENTITY_ID", $arFields))
			{
				unset($GLOBALS["SONET_FEATURES_CACHE"][$arFields["ENTITY_TYPE"]][$arFields["ENTITY_ID"]]);
			}

			$events = GetModuleEvents("socialnetwork", "OnSocNetFeaturesUpdate");
			while ($arEvent = $events->Fetch())
				ExecuteModuleEventEx($arEvent, array($ID, $arFields));

			if (defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_feature_".$ID);
			}
		}
		else
			$ID = False;

		return $ID;
	}

	
	/**
	* <p>Метод устанавливает настройки дополнительного функционала для группы или пользователя. Если запись для функционала существует, то она изменяется. Иначе создается новая запись.</p> <p><b>Примечание</b>: новая запись создается методом <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/csocnetfeatures.add.php">CSocNetFeatures::Add</a>, обновляется методом <a href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/Update.php">CSocNetFeatures::Update</a>.</p>
	*
	*
	* @param char $type  Тип объекта: <br><b>SONET_ENTITY_GROUP</b> - группа,<br><b>SONET_ENTITY_USER</b> -
	* пользователь.
	*
	* @param int $id  Идентификатор объекта (пользователя или группы).
	*
	* @param string $feature  Внутреннее название дополнительного функционала.
	*
	* @param char $active  Флаг активности (Y/N). </h
	*
	* @param string $featureName = false Название дополнительного функционала.
	*
	* @return int <p>Возвращается идентификатор записи дополнительного
	* функционала.</p>
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/csocnetfeatures.add.php">CSocNetFeatures::Add</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/Update.php">CSocNetFeatures::Update</a></li>
	* </ul><br><br>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/SetFeature.php
	* @author Bitrix
	*/
	public static function SetFeature($type, $id, $feature, $active, $featureName = false)
	{
		global $arSocNetAllowedEntityTypes, $APPLICATION;

		$type = Trim($type);
		if ((StrLen($type) <= 0) || !in_array($type, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_EMPTY_TYPE");
			return false;
		}

		$id = IntVal($id);
		if ($id <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		$feature = StrToLower(Trim($feature));
		if (StrLen($feature) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_FEATURE_ID"), "ERROR_EMPTY_FEATURE_ID");
			return false;
		}

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		if (
			!array_key_exists($feature, $arSocNetFeaturesSettings) 
			|| !in_array($type, $arSocNetFeaturesSettings[$feature]["allowed"])
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE_ID");
			return false;
		}

		$active = ($active ? "Y" : "N");

		$dbResult = CSocNetFeatures::GetList(
			array(),
			array(
				"ENTITY_TYPE" => $type,
				"ENTITY_ID" => $id,
				"FEATURE" => $feature
			),
			false,
			false,
			array("ID", "ACTIVE")
		);

		if ($arResult = $dbResult->Fetch())
		{
			$r = CSocNetFeatures::Update($arResult["ID"], array("FEATURE_NAME" => $featureName, "ACTIVE" => $active, "=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction()));
		}
		else
		{
			$r = CSocNetFeatures::Add(array("ENTITY_TYPE" => $type, "ENTITY_ID" => $id, "FEATURE" => $feature, "FEATURE_NAME" => $featureName, "ACTIVE" => $active, "=DATE_UPDATE" => $GLOBALS["DB"]->CurrentTimeFunction(), "=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction()));
		}

		if (!$r)
		{
			$errorMessage = "";
			if ($e = $APPLICATION->GetException())
				$errorMessage = $e->GetString();
			if (StrLen($errorMessage) <= 0)
				$errorMessage = GetMessage("SONET_GF_ERROR_SET").".";

			$GLOBALS["APPLICATION"]->ThrowException($errorMessage, "ERROR_SET_RECORD");
			return false;
		}

		return $r;
	}

	/***************************************/
	/**********  DATA SELECTION  ***********/
	/***************************************/
	
	/**
	* <p>Метод возвращает массив параметров дополнительного функционала.</p>
	*
	*
	* @param int $ID  Код записи
	*
	* @return array <p>Массив с ключами:<br><b>ID</b> - код записи,<br><b>ENTITY_TYPE</b> - тип объекта:
	* SONET_ENTITY_GROUP - группа, SONET_ENTITY_USER - пользователь,<br><b>ENTITY_ID</b> - код
	* объекта (группы или пользователя),<br><b>FEATURE</b> - внутреннее название
	* дополнительного функционала,<br><b>FEATURE_NAME</b> - название
	* дополнительного функционала,<br><b>ACTIVE</b> - флаг активности
	* (Y/N),<br><b>DATE_CREATE</b> - дата создания записи,<br><b>DATE_UPDATE</b> - дата
	* изменения записи.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/GetByID.php
	* @author Bitrix
	*/
	public static function GetByID($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);

		$dbResult = CSocNetFeatures::GetList(Array(), Array("ID" => $ID));
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
	* <p>Метод проверяет, активен ли функционал группы или пользователя.</p>
	*
	*
	* @param char $type  Тип объекта: <br><b>SONET_ENTITY_GROUP</b> - группа, <br><b>SONET_ENTITY_USER</b> -
	* пользователь.
	*
	* @param mixed $id  Идентификатор объекта (пользователя или группы), либо (с версии
	* 8.6.4) массив идентификаторов объектов.
	*
	* @param string $feature  Название дополнительного функционала.
	*
	* @return mixed <p>Если в параметре id передано скалярное значение, true, если
	* дополнительный функционал активен. Иначе - false. Если (с версии 8.6.4)
	* в параметре id передан массив идентификаторов объектов, то
	* возвращается ассоциативный массив, ключами для которого
	* являются идентификаторы, а значениями - true/false по вышеописанной
	* логике.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/IsActiveFeature.php
	* @author Bitrix
	*/
	public static function IsActiveFeature($type, $id, $feature)
	{
		global $arSocNetAllowedEntityTypes;

		$type = Trim($type);
		if ((StrLen($type) <= 0) || !in_array($type, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_EMPTY_TYPE");
			return false;
		}

		$feature = StrToLower(Trim($feature));
		if (StrLen($feature) <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_FEATURE_ID"), "ERROR_EMPTY_FEATURE_ID");
			return false;
		}

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		if (
			!array_key_exists($feature, $arSocNetFeaturesSettings) 
			|| !in_array($type, $arSocNetFeaturesSettings[$feature]["allowed"])
		)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_FEATURE_ID"), "ERROR_NO_FEATURE_ID");
			return false;
		}

		$arFeatures = array();

		if (is_array($id))
		{
			$arGroupToGet = array();
			foreach($id as $group_id)
			{
				if ($group_id <= 0)
					$arReturn[$group_id] = false;
				else
				{
					if (array_key_exists("SONET_FEATURES_CACHE", $GLOBALS)
						&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type])
						&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type][$group_id])
						&& is_array($GLOBALS["SONET_FEATURES_CACHE"][$type][$group_id]))
					{
						$arFeatures[$group_id] = $GLOBALS["SONET_FEATURES_CACHE"][$type][$group_id];
						
						if (!array_key_exists($feature, $arFeatures[$group_id]))
						{
							$arReturn[$group_id] = true;
							continue;
						}
						
						$arReturn[$group_id] = ($arFeatures[$group_id][$feature]["ACTIVE"] == "Y");
					}
					else
					{
						$arGroupToGet[] = $group_id;
					}
				}
			}
			
			if(!empty($arGroupToGet))
			{
				$dbResult = CSocNetFeatures::GetList(Array(), Array("ENTITY_ID" => $arGroupToGet, "ENTITY_TYPE" => $type));
				while ($arResult = $dbResult->GetNext())
					$arFeatures[$arResult["ENTITY_ID"]][$arResult["FEATURE"]] = array("ACTIVE" => $arResult["ACTIVE"], "FEATURE_NAME" => $arResult["FEATURE_NAME"]);

				foreach($arGroupToGet as $group_id)	
				{
					
					if (!array_key_exists("SONET_FEATURES_CACHE", $GLOBALS) || !is_array($GLOBALS["SONET_FEATURES_CACHE"]))
						$GLOBALS["SONET_FEATURES_CACHE"] = array();
					if (!array_key_exists($type, $GLOBALS["SONET_FEATURES_CACHE"]) || !is_array($GLOBALS["SONET_FEATURES_CACHE"][$type]))
						$GLOBALS["SONET_FEATURES_CACHE"][$type] = array();

					$GLOBALS["SONET_FEATURES_CACHE"][$type][$group_id] = $arFeatures[$group_id];

					if(!isset($arFeatures[$group_id]))
						$arFeatures[$group_id] = Array();
					if (!array_key_exists($feature, $arFeatures[$group_id]))
					{
						$arReturn[$group_id] = true;
						continue;
					}
					
					$arReturn[$group_id] = ($arFeatures[$group_id][$feature]["ACTIVE"] == "Y");
				}
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
			
			if (array_key_exists("SONET_FEATURES_CACHE", $GLOBALS)
				&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type])
				&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type][$id])
				&& is_array($GLOBALS["SONET_FEATURES_CACHE"][$type][$id]))
			{
				$arFeatures = $GLOBALS["SONET_FEATURES_CACHE"][$type][$id];
			}
			else
			{
				$dbResult = CSocNetFeatures::GetList(Array(), Array("ENTITY_ID" => $id, "ENTITY_TYPE" => $type));
				while ($arResult = $dbResult->GetNext())
					$arFeatures[$arResult["FEATURE"]] = array("ACTIVE" => $arResult["ACTIVE"], "FEATURE_NAME" => $arResult["FEATURE_NAME"]);

				if (!array_key_exists("SONET_FEATURES_CACHE", $GLOBALS) || !is_array($GLOBALS["SONET_FEATURES_CACHE"]))
					$GLOBALS["SONET_FEATURES_CACHE"] = array();
				if (!array_key_exists($type, $GLOBALS["SONET_FEATURES_CACHE"]) || !is_array($GLOBALS["SONET_FEATURES_CACHE"][$type]))
					$GLOBALS["SONET_FEATURES_CACHE"][$type] = array();

				$GLOBALS["SONET_FEATURES_CACHE"][$type][$id] = $arFeatures;
			}
			
			if (!array_key_exists($feature, $arFeatures))
				return true;
				
			return ($arFeatures[$feature]["ACTIVE"] == "Y");
		}
	}

	
	/**
	* <p>Метод возвращает список активных дополнительных функционалов группы или пользователя.</p>
	*
	*
	* @param char $type  Тип объекта: <br><b>SONET_ENTITY_GROUP</b> - группа,<br><b>SONET_ENTITY_USER</b> -
	* пользователь.
	*
	* @param int $id  Идентификатор объекта (пользователя или группы).
	*
	* @return array <p>Массив названий активных дополнительных функционалов.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/GetActiveFeatures.php
	* @author Bitrix
	*/
	public static function GetActiveFeatures($type, $id)
	{
		global $arSocNetAllowedEntityTypes;

		$type = Trim($type);
		if ((StrLen($type) <= 0) || !in_array($type, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_EMPTY_TYPE");
			return false;
		}

		$id = IntVal($id);
		if ($id <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		$arReturn = array();

		$arFeatures = array();

		if (array_key_exists("SONET_FEATURES_CACHE", $GLOBALS)
			&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type])
			&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type][$id])
			&& is_array($GLOBALS["SONET_FEATURES_CACHE"][$type][$id]))
		{
			$arFeatures = $GLOBALS["SONET_FEATURES_CACHE"][$type][$id];
		}
		else
		{
			$dbResult = CSocNetFeatures::GetList(Array(), Array("ENTITY_ID" => $id, "ENTITY_TYPE" => $type));
			while ($arResult = $dbResult->GetNext())
				$arFeatures[$arResult["FEATURE"]] = array("ACTIVE" => $arResult["ACTIVE"], "FEATURE_NAME" => $arResult["FEATURE_NAME"]);

			if (!array_key_exists("SONET_FEATURES_CACHE", $GLOBALS) || !is_array($GLOBALS["SONET_FEATURES_CACHE"]))
				$GLOBALS["SONET_FEATURES_CACHE"] = array();
			if (!array_key_exists($type, $GLOBALS["SONET_FEATURES_CACHE"]) || !is_array($GLOBALS["SONET_FEATURES_CACHE"][$type]))
				$GLOBALS["SONET_FEATURES_CACHE"][$type] = array();

			$GLOBALS["SONET_FEATURES_CACHE"][$type][$id] = $arFeatures;
		}

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		foreach ($arSocNetFeaturesSettings as $feature => $arr)
		{
			if (
				!array_key_exists("allowed", $arSocNetFeaturesSettings[$feature])
				|| !is_array($arSocNetFeaturesSettings[$feature]["allowed"])
				|| !in_array($type, $arSocNetFeaturesSettings[$feature]["allowed"])
			)
			{
				continue;
			}

			if (array_key_exists($feature, $arFeatures) && ($arFeatures[$feature]["ACTIVE"] == "N"))
			{
				continue;
			}

			$arReturn[] = $feature;
		}

		return $arReturn;
	}

	
	/**
	* <p>Пользователь в своем профайле и владелец группы могут задавать пользовательские названия для дополнительного функционала (названия вкладок). Метод служит для получения пользовательских названий дополнительного функционала, если они заданы. </p>
	*
	*
	* @param char $type  Тип объекта:<br><b>SONET_ENTITY_USER</b> - профиль
	* пользователя,<br><b>SONET_ENTITY_GROUP</b> - группа.
	*
	* @param int $id  Код объекта (пользователя или группы).
	*
	* @return array <p>Метод вернет массив пользовательских названий для
	* дополнительного функционала, которые были заданы. Если
	* пользовательские названия не были заданы, то они не будут
	* возвращены.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* // Выберем пользовательские названия для группы $ID
	* $arRealTabsName = CSocNetFeatures::GetActiveFeaturesNames(SONET_ENTITY_GROUP, $ID);
	* 
	* // Результат будет типа
	* // array 
	* // ( 
	* //    [forum] =&gt; Мой форум 
	* //    [photo] =&gt; Моя фотогалерея 
	* //    [blog] =&gt; Мои записки 
	* // )
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetfeatures/GetActiveFeaturesNames.php
	* @author Bitrix
	*/
	public static function GetActiveFeaturesNames($type, $id)
	{
		global $arSocNetAllowedEntityTypes;

		$type = Trim($type);
		if ((StrLen($type) <= 0) || !in_array($type, $arSocNetAllowedEntityTypes))
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_ERROR_NO_ENTITY_TYPE"), "ERROR_EMPTY_TYPE");
			return false;
		}

		$id = IntVal($id);
		if ($id <= 0)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("SONET_GF_EMPTY_ENTITY_ID"), "ERROR_EMPTY_ENTITY_ID");
			return false;
		}

		$arReturn = array();
		$arFeatures = array();

		if (array_key_exists("SONET_FEATURES_CACHE", $GLOBALS)
			&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type])
			&& isset($GLOBALS["SONET_FEATURES_CACHE"][$type][$id])
			&& is_array($GLOBALS["SONET_FEATURES_CACHE"][$type][$id]))
		{
			$arFeatures = $GLOBALS["SONET_FEATURES_CACHE"][$type][$id];
		}
		else
		{
			$cache = new CPHPCache;
			$cache_time = 31536000;
			$cache_id = $type."_".$id;
			$cache_path = "/sonet/features/";

			if ($cache->InitCache($cache_time, $cache_id, $cache_path))
			{
				$arCacheVars = $cache->GetVars();
				$arFeatures = $arCacheVars["FEATURES"];
			}
			else
			{
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->StartTagCache($cache_path);
					$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_features_".$type."_".$id);
				}

				$dbResult = CSocNetFeatures::GetList(Array(), Array("ENTITY_ID" => $id, "ENTITY_TYPE" => $type));
				while ($arResult = $dbResult->GetNext())
				{
					$arFeatures[$arResult["FEATURE"]] = array("ACTIVE" => $arResult["ACTIVE"], "FEATURE_NAME" => $arResult["FEATURE_NAME"]);
					if (defined("BX_COMP_MANAGED_CACHE"))
					{
						$GLOBALS["CACHE_MANAGER"]->RegisterTag("sonet_feature_".$arResult["ID"]);
					}
				}

				$arCacheData = Array(
					"FEATURES" => $arFeatures
				);
				$cache->EndDataCache($arCacheData);
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$GLOBALS["CACHE_MANAGER"]->EndTagCache();				
				}
			}

			if (!array_key_exists("SONET_FEATURES_CACHE", $GLOBALS) || !is_array($GLOBALS["SONET_FEATURES_CACHE"]))
			{
				$GLOBALS["SONET_FEATURES_CACHE"] = array();
			}

			if (!array_key_exists($type, $GLOBALS["SONET_FEATURES_CACHE"]) || !is_array($GLOBALS["SONET_FEATURES_CACHE"][$type]))
			{
				$GLOBALS["SONET_FEATURES_CACHE"][$type] = array();
			}

			$GLOBALS["SONET_FEATURES_CACHE"][$type][$id] = $arFeatures;
		}

		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();
		foreach ($arSocNetFeaturesSettings as $feature => $arr)
		{
			if (
				!array_key_exists("allowed", $arSocNetFeaturesSettings[$feature]) 
				|| !in_array($type, $arSocNetFeaturesSettings[$feature]["allowed"])
			)
			{
				continue;
			}

			if (array_key_exists($feature, $arFeatures) && ($arFeatures[$feature]["ACTIVE"] == "N"))
				continue;

			$arReturn[$feature] = $arFeatures[$feature]["FEATURE_NAME"];
		}

		return $arReturn;
	}
}
?>