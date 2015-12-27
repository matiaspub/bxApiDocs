<?
IncludeModuleLangFile(__FILE__);


/**
 * <b>CSocNetUser</b> - класс, содержащий вспомогательные методы для работы с пользователями социальной сети. 
 *
 *
 * @return mixed 
 *
 * @static
 * @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/index.php
 * @author Bitrix
 */
class CAllSocNetUser
{
	public static function OnUserDelete($ID)
	{
		global $DB;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		if (!CSocNetGroup::DeleteNoDemand($ID))
		{
			if($ex = $GLOBALS["APPLICATION"]->GetException())
				$err = $ex->GetString();
			$GLOBALS["APPLICATION"]->ThrowException($err);				
			$bSuccess = false;
		}

		if ($bSuccess)
		{
			CSocNetUserRelations::DeleteNoDemand($ID);
			CSocNetUserPerms::DeleteNoDemand($ID);
			CSocNetUserEvents::DeleteNoDemand($ID);
			CSocNetMessages::DeleteNoDemand($ID);
			CSocNetUserToGroup::DeleteNoDemand($ID);
			CSocNetLogEvents::DeleteNoDemand($ID);
			CSocNetLog::DeleteNoDemand($ID);
			CSocNetLogComments::DeleteNoDemand($ID);
			CSocNetFeatures::DeleteNoDemand($ID);
			CSocNetSubscription::DeleteEx($ID);

			CUserOptions::DeleteOption("socialnetwork", "~menu_".SONET_ENTITY_USER."_".$ID, false, 0);
		}

		return $bSuccess;
	}

	public static function OnBeforeUserUpdate(&$arFields)
	{
		$rsUser = CUser::GetByID($arFields["ID"]);
		if ($arUser = $rsUser->Fetch())
			// define("GLOBAL_ACTIVE_VALUE", $arUser["ACTIVE"]);
	}

	public static function OnAfterUserAdd(&$arFields)
	{
		return;
	}

	public static function OnAfterUserLogout(&$arParams)
	{
		if (array_key_exists("SONET_ADMIN", $_SESSION))
			unset($_SESSION["SONET_ADMIN"]);
	}

	public static function OnAfterUserUpdate(&$arFields)
	{
		if (array_key_exists("ACTIVE", $arFields) && defined("GLOBAL_ACTIVE_VALUE") && GLOBAL_ACTIVE_VALUE != $arFields["ACTIVE"]):

			$arGroups = array();
			$dbResult = CSocNetUserToGroup::GetList(array(), array("USER_ID" => $arFields["ID"]), false, false, array("GROUP_ID"));
			while ($arResult = $dbResult->Fetch())
				$arGroups[] = $arResult["GROUP_ID"];

			$cnt = count($arGroups);
			for ($i = 0; $i < $cnt; $i++)
				CSocNetGroup::SetStat($arGroups[$i]);

		endif;
	}
	
	public static function OnBeforeProlog()
	{
		if (!$GLOBALS["USER"]->IsAuthorized())
			return;

		CUser::SetLastActivityDate($GLOBALS["USER"]->GetID());
	}

	public static function OnUserInitialize($user_id, $arFields = array())
	{
		if (intval($user_id) <= 0)
		{
			return false;
		}

		if (CModule::IncludeModule("im"))
		{
			$bIM = true;
		}

		$dbRelation = CSocNetUserToGroup::GetList(
			array(), 
			array(
				"USER_ID" => $user_id, 
				"ROLE" => SONET_ROLES_REQUEST, 
				"INITIATED_BY_TYPE" => SONET_INITIATED_BY_GROUP
			), 
			false, 
			false, 
			array("ID", "GROUP_ID")
		);
		while ($arRelation = $dbRelation->Fetch())
		{
			if (
				CSocNetUserToGroup::UserConfirmRequestToBeMember($user_id, $arRelation["ID"], false) 
				&& defined("BX_COMP_MANAGED_CACHE")
			)
			{
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_G".$arRelation["GROUP_ID"]);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group_U".$user_id);
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("sonet_user2group");
				if ($bIM)
				{
					CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".$user_id."|".intval($arRelation["ID"]));
				}
			}
		}
	}

	
	/**
	* <p>Метод проверяет, находится ли сейчас пользователь на сайте. Пользователь находится на сайте, если он совершал на сайте какие-либо действия за последние 2 минуты.</p>
	*
	*
	* @param int $userID  Код пользователя. </h
	*
	* @return bool <p>True, если пользователь сейчас на сайте. Иначе - false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/IsOnLine.php
	* @author Bitrix
	*/
	public static function IsOnLine($userID)
	{
		$userID = IntVal($userID);
		if ($userID <= 0)
			return false;

		return CUser::IsOnLine($userID, 120);
	}

	
	/**
	* <p>Проверяет, разрешен ли функционал друзей.</p>
	*
	*
	* @return bool <p>True, если функционал друзей включен на сайте. Иначе - false.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/isfriendsallowed.php
	* @author Bitrix
	*/
	public static function IsFriendsAllowed()
	{
		return (COption::GetOptionString("socialnetwork", "allow_frields", "Y") == "Y");
	}

	public static function IsFriendsFriendsAllowed()
	{
		return (COption::GetOptionString("socialnetwork", "allow_frields_friends", "Y") == "Y");
	}

	
	/**
	* <p>Метод проверяет, есть ли у текущего пользователя административные права на доступ к модулю социальной сети.</p>
	*
	*
	* @param ) $;  Идентификатор сайта, необязательный параметр. По умолчанию
	* подставляется текущий сайт.
	*
	* @param string $site_id = SITE_ID Параметр, указывающий использовать текущую сессию авторизации
	* пользователя. Необязательный параметр. По умолчанию равен true.
	*
	* @param bool $bUseSession = true 
	*
	* @return bool <p>Если пользователь является администратором или имеет права
	* записи на модуль социальной сети, то метод возвращает true, иначе -
	* false.</p> <a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre>
	* &lt;?
	* if (CSocNetUser::IsCurrentUserModuleAdmin())
	* {
	*    // Текущему пользователю можно все
	* }
	* ?&gt;
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/iscurrentusermoduleadmin.php
	* @author Bitrix
	*/
	public static function IsCurrentUserModuleAdmin($site_id = SITE_ID, $bUseSession = true)
	{
		if (!is_object($GLOBALS["USER"]) || !$GLOBALS["USER"]->IsAuthorized())
			return false;
			
		if ($bUseSession && !isset($_SESSION["SONET_ADMIN"]))
			return false;

		if ($GLOBALS["USER"]->IsAdmin())
			return true;

		if (is_array($site_id))
		{
			foreach ($site_id as $site_id_tmp)
			{
				$modulePerms = $GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork", false, "Y", "Y", array($site_id_tmp, false));
				if ($modulePerms >= "W")
					return true;
			}
			return false;
		}
		else
		{
			$modulePerms = $GLOBALS["APPLICATION"]->GetGroupRight("socialnetwork", false, "Y", "Y", ($site_id ? array($site_id, false) : false));
			return ($modulePerms >= "W");
		}
	}

	public static function IsUserModuleAdmin($userID, $site_id = SITE_ID)
	{
		global $DB, $CACHE_MANAGER;
		static $arSocnetModuleAdminsCache = array();

		if ($userID <= 0)
		{
			return false;
		}

		if ($site_id && !is_array($site_id))
		{
			$site_id = array($site_id, false);
		}
		elseif ($site_id && is_array($site_id))
		{
			$site_id = array_merge($site_id, array(false));
		}

		$cache_key = serialize($site_id);
		if (!array_key_exists($cache_key, $arSocnetModuleAdminsCache))
		{
			$cache = new CPHPCache;
			$cache_time = 31536000;
			$cache_id = 'site'.($site_id ? '_'.implode('|', $site_id) : '');
			$cache_path = "/sonet/user_admin/";

			$arModuleAdmins = array();

			if ($cache->InitCache($cache_time, $cache_id, $cache_path))
			{
				$arCacheVars = $cache->GetVars();
				$arModuleAdmins = $arCacheVars["RESULT"];
			}
			else
			{
				$cache->StartDataCache($cache_time, $cache_id, $cache_path);

				if(!$site_id)
				{
					$strSqlSite = "AND MG.SITE_ID IS NULL";
				}
				else
				{
					$strSqlSite = " AND (";
					foreach($site_id as $i => $site_id_tmp)
					{
						if($i > 0)
						{
							$strSqlSite .= " OR ";
						}

						$strSqlSite .= "MG.SITE_ID " . ($site_id_tmp ? "= '" . $DB->ForSQL($site_id_tmp) . "'" : "IS NULL");
					}
					$strSqlSite .= ")";
				}

				$strSql = "SELECT " .
					"UG.USER_ID U_ID, " .
					"G.ID G_ID, " .
					"MAX(".CDatabase::DatetimeToTimestampFunction("UG.DATE_ACTIVE_FROM").") UG_DATE_FROM_TS, " .
					"MAX(".CDatabase::DatetimeToTimestampFunction("UG.DATE_ACTIVE_TO").") UG_DATE_TO_TS, " .
					"MAX(MG.G_ACCESS) G_ACCESS " .
					"FROM b_user_group UG, b_module_group MG, b_group G  " .
					"WHERE " .
					"	(G.ID = MG.GROUP_ID or G.ID = 1) " .
					"	AND MG.MODULE_ID = 'socialnetwork' " .
					"	AND G.ID = UG.GROUP_ID " .
					"	AND G.ACTIVE = 'Y' " .
					"	AND G_ACCESS >= 'W' " .
					"	AND (G.ANONYMOUS<>'Y' OR G.ANONYMOUS IS NULL) " .
						$strSqlSite .
					" " .
					"GROUP BY " .
					"	UG.USER_ID, G.ID";

				$result = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br> LINE: " . __LINE__);
				while($ar = $result->Fetch())
				{
					if(!array_key_exists($ar["U_ID"], $arModuleAdmins))
					{
						$arModuleAdmins[$ar["U_ID"]] = array(
							"USER_ID" => $ar["U_ID"],
							"DATE_FROM_TS" => $ar["UG_DATE_FROM_TS"],
							"DATE_TO_TS" => $ar["UG_DATE_TO_TS"]
						);
					}
				}
			}

			$arCacheData = Array(
				"RESULT" => $arModuleAdmins
			);

			$cache->EndDataCache($arCacheData);

			foreach ($arModuleAdmins as $key => $arUserData)
			{
				if (
					(
						!empty($arUserData["DATE_FROM_TS"])
						&& $arUserData["DATE_FROM_TS"] > time()
					)
					|| (
						!empty($arUserData["DATE_TO_TS"])
						&& $arUserData["DATE_TO_TS"] < time()
					)
				)
				{
					unset($arModuleAdmins[$key]);
				}
			}

			$arSocnetModuleAdminsCache[$cache_key] = $arModuleAdmins;
		}

		return (array_key_exists($userID, $arSocnetModuleAdminsCache[$cache_key]));
	}

	public static function DeleteUserAdminCache()
	{
		BXClearCache(true, "/sonet/user_admin/");
	}

	
	/**
	* <p>Метод подготавливает имя пользователя для вывода.</p>
	*
	*
	* @param string $name  Имя пользователя.
	*
	* @param string $lastName  Фамилия пользователя. </htm
	*
	* @param string $login  Логин пользователя. </h
	*
	* @return string <p>Возвращается строка, содержащая отформатированное имя
	* пользователя.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/formatname.php
	* @author Bitrix
	*/
	public static function FormatName($name, $lastName, $login)
	{
		$name = Trim($name);
		$lastName = Trim($lastName);
		$login = Trim($login);

		$formatName = $name;
		if (StrLen($formatName) > 0 && StrLen($lastName) > 0)
			$formatName .= " ";
		$formatName .= $lastName;
		if (StrLen($formatName) <= 0)
			$formatName = $login;

		return $formatName;
	}

	
	/**
	* <p>Метод подготавливает имя пользователя для вывода в расширенном виде.</p>
	*
	*
	* @param string $name  Имя пользователя.
	*
	* @param string $secondName  Отчество пользователя. </htm
	*
	* @param string $lastName  Фамилия пользователя. </htm
	*
	* @param string $login  Логин пользователя </h
	*
	* @param string $email  E-Mail пользователя.
	*
	* @param string $id  Код пользователя. </h
	*
	* @return string <p>Возвращается строка, содержащая отформатированное имя
	* пользователя.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/formatnameex.php
	* @author Bitrix
	*/
	public static function FormatNameEx($name, $secondName, $lastName, $login, $email, $id)
	{
		$name = Trim($name);
		$lastName = Trim($lastName);
		$secondName = Trim($secondName);
		$login = Trim($login);
		$email = Trim($email);
		$id = IntVal($id);

		$formatName = $name;
		if (StrLen($formatName) > 0 && StrLen($secondName) > 0)
			$formatName .= " ";
		$formatName .= $secondName;
		if (StrLen($formatName) > 0 && StrLen($lastName) > 0)
			$formatName .= " ";
		$formatName .= $lastName;
		if (StrLen($formatName) <= 0)
			$formatName = $login;

		if (StrLen($email) > 0)
			$formatName .= " &lt;".$email."&gt;";
		$formatName .= " [".$id."]";

		return $formatName;
	}

	
	/**
	* <p>Метод ищет пользователя по его имени или коду.</p>
	*
	*
	* @param string $user  Имя или код пользователя. Если параметр является числом или
	* строкой, в которой содержится число в квадратных скобках, то это
	* число рассматривается как код пользователя. В противном случае
	* параметр рассматривается как строка, содержащая ФИО
	* пользователя.
	*
	* @param bool $bIntranet = false Флаг, определяющий, осуществляется ли работа в рамках решения
	* интранет. Необязательный параметр. По умолчанию равен false.
	*
	* @return array <p>Массив пользователей, удовлетворяющих условию поиска.</p> <br><br>
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_help/socialnetwork/classes/csocnetuser/searchuser.php
	* @author Bitrix
	*/
	public static function SearchUser($user, $bIntranet = false)
	{
		$user = Trim($user);
		if (StrLen($user) <= 0)
			return false;

		$userID = 0;
		if ($user."|" == IntVal($user)."|")
			$userID = IntVal($user);

		if ($userID <= 0)
		{
			$arMatches = array();
			if (preg_match("#\[(\d+)\]#i", $user, $arMatches))
				$userID = IntVal($arMatches[1]);
		}


		$dbUsers =  false;
		if ($userID > 0)
		{
			$arFilter = array("ID_EQUAL_EXACT" => $userID);

			$dbUsers = CUser::GetList(
				($by = "LAST_NAME"),
				($order = "asc"),
				$arFilter,
				array(
					"NAV_PARAMS" => false,
				)
			);
		}
		else
		{
			$email = "";
			$arMatches = array();
			if (preg_match("#<(.+?)>#i", $user, $arMatches))
			{

				if (check_email($arMatches[1]))
				{
					$email = $arMatches[1];
					$user = Trim(Str_Replace("<".$email.">", "", $user));
				}
			}

			$arUser = array();
			$arUserTmp = Explode(" ", $user);
			foreach ($arUserTmp as $s)
			{
				$s = Trim($s);
				if (StrLen($s) > 0)
					$arUser[] = $s;
			}

			if (
				count($arUser) <= 0
				&& strlen($email) > 0
			)
			{
				$arFilter = array(
					"ACTIVE" => "Y",
					"EMAIL" => $email,
				);
				$dbUsers = CUser::GetList(($by="id"), ($order="asc"), $arFilter);
			}
			else
			{
				$dbUsers = CUser::SearchUserByName($arUser, $email);
			}
		}

		if ($dbUsers)
		{
			$arResult = array();
			while ($arUsers = $dbUsers->GetNext())
			{
				$arResult[$arUsers["ID"]] = CSocNetUser::FormatNameEx(
					$arUsers["NAME"],
					$arUsers["SECOND_NAME"],
					$arUsers["LAST_NAME"],
					$arUsers["LOGIN"],
					($bIntranet ? $arUsers["EMAIL"] : ""),
					$arUsers["ID"]
				);
			}

			return $arResult;
		}

		return false;
	}

	public static function GetByID($ID)
	{
		$ID = IntVal($ID);

		$dbUser = CUser::GetByID($ID);
		if ($arUser = $dbUser->GetNext())
		{
			$arUser["NAME_FORMATTED"] = CUser::FormatName(CSite::GetNameFormat(false), $arUser);
			$arUser["~NAME_FORMATTED"] = htmlspecialcharsback($arUser["NAME_FORMATTED"]);
			return $arUser;
		}
		else
			return false;
	}

	public static function GetFields($bAdditional = false)
	{
		$arRes = array(
			"ID" => GetMessage("SONET_UP1_ID"),
			"LOGIN" => GetMessage("SONET_UP1_LOGIN"),
			"NAME" => GetMessage("SONET_UP1_NAME"),
			"SECOND_NAME" => GetMessage("SONET_UP1_SECOND_NAME"),
			"LAST_NAME" => GetMessage("SONET_UP1_LAST_NAME"),
			"EMAIL" => GetMessage("SONET_UP1_EMAIL"),
			"TIME_ZONE" => GetMessage("SONET_UP1_TIME_ZONE"),
			"LAST_LOGIN" => GetMessage("SONET_UP1_LAST_LOGIN"),
			"DATE_REGISTER" => GetMessage("SONET_UP1_DATE_REGISTER"),
			"LID" => GetMessage("SONET_UP1_LID"),
			"PASSWORD" => GetMessage("SONET_UP1_PASSWORD"),
			"PERSONAL_BIRTHDAY" => GetMessage("SONET_UP1_PERSONAL_BIRTHDAY"),
			"PERSONAL_BIRTHDAY_YEAR" => GetMessage("SONET_UP1_PERSONAL_BIRTHDAY_YEAR"),
			"PERSONAL_BIRTHDAY_DAY" => GetMessage("SONET_UP1_PERSONAL_BIRTHDAY_DAY"),

			"PERSONAL_PROFESSION" => GetMessage("SONET_UP1_PERSONAL_PROFESSION"),
			"PERSONAL_WWW" => GetMessage("SONET_UP1_PERSONAL_WWW"),
			"PERSONAL_ICQ" => GetMessage("SONET_UP1_PERSONAL_ICQ"),
			"PERSONAL_GENDER" => GetMessage("SONET_UP1_PERSONAL_GENDER"),
			"PERSONAL_PHOTO" => GetMessage("SONET_UP1_PERSONAL_PHOTO"),
			"PERSONAL_NOTES" => GetMessage("SONET_UP1_PERSONAL_NOTES"),

			"PERSONAL_PHONE" => GetMessage("SONET_UP1_PERSONAL_PHONE"),
			"PERSONAL_FAX" => GetMessage("SONET_UP1_PERSONAL_FAX"),
			"PERSONAL_MOBILE" => GetMessage("SONET_UP1_PERSONAL_MOBILE"),
			"PERSONAL_PAGER" => GetMessage("SONET_UP1_PERSONAL_PAGER"),

			"PERSONAL_COUNTRY" => GetMessage("SONET_UP1_PERSONAL_COUNTRY"),
			"PERSONAL_STATE" => GetMessage("SONET_UP1_PERSONAL_STATE"),
			"PERSONAL_CITY" => GetMessage("SONET_UP1_PERSONAL_CITY"),
			"PERSONAL_ZIP" => GetMessage("SONET_UP1_PERSONAL_ZIP"),
			"PERSONAL_STREET" => GetMessage("SONET_UP1_PERSONAL_STREET"),
			"PERSONAL_MAILBOX" => GetMessage("SONET_UP1_PERSONAL_MAILBOX"),

			"WORK_COMPANY" => GetMessage("SONET_UP1_WORK_COMPANY"),
			"WORK_DEPARTMENT" => GetMessage("SONET_UP1_WORK_DEPARTMENT"),
			"WORK_POSITION" => GetMessage("SONET_UP1_WORK_POSITION"),
			"WORK_WWW" => GetMessage("SONET_UP1_WORK_WWW"),
			"WORK_PROFILE" => GetMessage("SONET_UP1_WORK_PROFILE"),
			"WORK_LOGO" => GetMessage("SONET_UP1_WORK_LOGO"),
			"WORK_NOTES" => GetMessage("SONET_UP1_WORK_NOTES"),

			"WORK_PHONE" => GetMessage("SONET_UP1_WORK_PHONE"),
			"WORK_FAX" => GetMessage("SONET_UP1_WORK_FAX"),
			"WORK_PAGER" => GetMessage("SONET_UP1_WORK_PAGER"),

			"WORK_COUNTRY" => GetMessage("SONET_UP1_WORK_COUNTRY"),
			"WORK_STATE" => GetMessage("SONET_UP1_WORK_STATE"),
			"WORK_CITY" => GetMessage("SONET_UP1_WORK_CITY"),
			"WORK_ZIP" => GetMessage("SONET_UP1_WORK_ZIP"),
			"WORK_STREET" => GetMessage("SONET_UP1_WORK_STREET"),
			"WORK_MAILBOX" => GetMessage("SONET_UP1_WORK_MAILBOX"),
		);

		if (IsModuleInstalled("forum"))
		{
			$arRes["FORUM_SHOW_NAME"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_SHOW_NAME");
			$arRes["FORUM_DESCRIPTION"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_DESCRIPTION");
			$arRes["FORUM_INTERESTS"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_INTERESTS");
			$arRes["FORUM_SIGNATURE"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_SIGNATURE");
			$arRes["FORUM_AVATAR"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_AVATAR");
			$arRes["FORUM_HIDE_FROM_ONLINE"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_HIDE_FROM_ONLINE");
			$arRes["FORUM_SUBSC_GET_MY_MESSAGE"] = GetMessage("SONET_UP1_FORUM_PREFIX").GetMessage("SONET_UP1_FORUM_SUBSC_GET_MY_MESSAGE");
		}

		if (IsModuleInstalled("blog"))
		{
			$arRes["BLOG_ALIAS"] = GetMessage("SONET_UP1_BLOG_PREFIX").GetMessage("SONET_UP1_BLOG_ALIAS");
			$arRes["BLOG_DESCRIPTION"] = GetMessage("SONET_UP1_BLOG_PREFIX").GetMessage("SONET_UP1_BLOG_DESCRIPTION");
			$arRes["BLOG_INTERESTS"] = GetMessage("SONET_UP1_BLOG_PREFIX").GetMessage("SONET_UP1_BLOG_INTERESTS");
			$arRes["BLOG_AVATAR"] = GetMessage("SONET_UP1_BLOG_PREFIX").GetMessage("SONET_UP1_BLOG_AVATAR");
		}

		return $arRes;
	}

	public static function GetFieldsMap($bAdditional = false)
	{
		$arUserFields = CSocNetUser::GetFields($bAdditional);
		return array_keys($arUserFields);
	}

}
?>