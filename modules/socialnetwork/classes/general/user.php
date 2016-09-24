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
		global $APPLICATION;

		if (!CSocNetGroup::__ValidateID($ID))
			return false;

		$ID = IntVal($ID);
		$bSuccess = True;

		if (!CSocNetGroup::DeleteNoDemand($ID))
		{
			if($ex = $APPLICATION->GetException())
			{
				$APPLICATION->ThrowException($ex->GetString());
			}
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
		global $USER;

		if (!$USER->IsAuthorized())
			return;

		CUser::SetLastActivityDate($USER->GetID());
	}

	public static function OnUserInitialize($user_id, $arFields = array())
	{
		global $CACHE_MANAGER;

		if (intval($user_id) <= 0)
		{
			return false;
		}

		$bIM = (CModule::IncludeModule("im"));

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
				$CACHE_MANAGER->ClearByTag("sonet_user2group_G".$arRelation["GROUP_ID"]);
				$CACHE_MANAGER->ClearByTag("sonet_user2group_U".$user_id);
				$CACHE_MANAGER->ClearByTag("sonet_user2group");
				if ($bIM)
				{
					CIMNotify::DeleteByTag("SOCNET|INVITE_GROUP|".$user_id."|".intval($arRelation["ID"]));
				}
			}
		}
	}

	
	/**
	* <p>Метод проверяет, находится ли сейчас пользователь на сайте. Пользователь находится на сайте, если он совершал на сайте какие-либо действия за последние 2 минуты. Метод статический.</p>
	*
	*
	* @param int $userID  Код пользователя.
	*
	* @return bool <p>True, если пользователь сейчас на сайте. Иначе - false.</p><br><br>
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
	* <p>Проверяет, разрешен ли функционал друзей. Метод статический.</p>
	*
	*
	* @return bool <p>True, если функционал друзей включен на сайте. Иначе - false.</p><br><br>
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
	* <p>Метод проверяет, есть ли у текущего пользователя административные права на доступ к модулю социальной сети. Метод статический.</p>
	*
	*
	* @param mixed $mixed);  Идентификатор сайта, необязательный параметр. По умолчанию
	* подставляется текущий сайт.
	*
	* @param string $site_id = SITE_ID Параметр, указывающий использовать текущую сессию авторизации
	* пользователя. Необязательный параметр. По умолчанию равен true.
	*
	* @param bool $bUseSession = true 
	*
	* @return bool <p>Если пользователь является администратором или имеет права
	* записи на модуль социальной сети, то метод возвращает true, иначе -
	* false.</p><a name="examples"></a>
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
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
		global $APPLICATION, $USER;

		if (!is_object($USER) || !$USER->IsAuthorized())
			return false;

		if ($bUseSession && !isset($_SESSION["SONET_ADMIN"]))
			return false;

		if ($USER->IsAdmin())
			return true;

		if (is_array($site_id))
		{
			foreach ($site_id as $site_id_tmp)
			{
				$modulePerms = $APPLICATION->GetGroupRight("socialnetwork", false, "Y", "Y", array($site_id_tmp, false));
				if ($modulePerms >= "W")
					return true;
			}
			return false;
		}
		else
		{
			$modulePerms = $APPLICATION->GetGroupRight("socialnetwork", false, "Y", "Y", ($site_id ? array($site_id, false) : false));
			return ($modulePerms >= "W");
		}
	}

	public static function IsUserModuleAdmin($userID, $site_id = SITE_ID)
	{
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

		$arModuleAdmin = \Bitrix\Socialnetwork\User::getModuleAdminList($site_id);

		return (array_key_exists($userID, $arModuleAdmin));
	}

	public static function DeleteUserAdminCache()
	{
		BXClearCache(true, "/sonet/user_admin/");
	}

	
	/**
	* <p>Метод подготавливает имя пользователя для вывода. Метод статический.</p>
	*
	*
	* @param string $name  Имя пользователя.
	*
	* @param string $lastName  Фамилия пользователя.
	*
	* @param string $login  Логин пользователя.
	*
	* @return string <p>Возвращается строка, содержащая отформатированное имя
	* пользователя.</p><br><br>
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
	* <p>Метод подготавливает имя пользователя для вывода в расширенном виде. Метод статический.</p>
	*
	*
	* @param string $name  Имя пользователя.
	*
	* @param string $secondName  Отчество пользователя.
	*
	* @param string $lastName  Фамилия пользователя.
	*
	* @param string $login  Логин пользователя
	*
	* @param string $email  E-Mail пользователя.
	*
	* @param string $stringid  Код пользователя.
	*
	* @return string <p>Возвращается строка, содержащая отформатированное имя
	* пользователя.</p><br><br>
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
	* <p>Метод ищет пользователя по его имени или коду. Метод статический.</p>
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
	* @return array <p>Массив пользователей, удовлетворяющих условию поиска.</p><br><br>
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

	public static function CanProfileView($currentUserId, $arUser, $siteId = SITE_ID, $arContext = array())
	{
		global $USER;

		if (
			!is_array($arUser)
			&& intval($arUser) > 0
		)
		{
			$dbUser = CUser::GetByID(intval($arUser));
			$arUser = $dbUser->Fetch();
		}

		if (
			!is_array($arUser)
			|| !isset($arUser["ID"])
			|| intval($arUser["ID"]) <= 0
		)
		{
			return false;
		}

		if (
			$currentUserId == $USER->GetId()
			&& self::IsCurrentUserModuleAdmin()
		)
		{
			return true;
		}

		if (self::OnGetProfileView($currentUserId, $arUser, $siteId, $arContext)) // only for email users
		{
			return true;
		}

		$bFound = false;
		foreach(GetModuleEvents("socialnetwork", "OnGetProfileView", true) as $arEvent)
		{
			if (IsModuleInstalled($arEvent["TO_MODULE_ID"]))
			{
				$bFound = true;
				if (ExecuteModuleEventEx($arEvent, Array($currentUserId, $arUser, $siteId, $arContext, false)) === true)
				{
					return true;
				}
			}
		}

		return (!$bFound);
	}

	public static function OnGetProfileView($currentUserId, $arUser, $siteId, $arContext)
	{
		if (!IsModuleInstalled('mail'))
		{
			return false;
		}

		if (
			intval($currentUserId) <= 0
			|| !isset($arContext)
			|| !isset($arContext["ENTITY_TYPE"])
			|| !in_array($arContext["ENTITY_TYPE"], array("LOG_ENTRY"))
			|| !isset($arContext["ENTITY_ID"])
			|| intval($arContext["ENTITY_ID"]) <= 0
			|| !is_array($arUser)
			|| !isset($arUser["ID"])
			|| intval($arUser["ID"]) <= 0
		)
		{
			return false;
		}

		if (
			(
				isset($arUser["EXTERNAL_AUTH_ID"])
				&& $arUser["EXTERNAL_AUTH_ID"] == 'email'
			) // -> email user
			||
			(
				($rsCurrentUser = CUser::GetByID(intval($currentUserId)))
				&& ($arCurrentUser = $rsCurrentUser->Fetch())
				&& ($arCurrentUser["EXTERNAL_AUTH_ID"] == 'email')
			) // email user ->
		)
		{
			return self::CheckContext($currentUserId, $arUser["ID"], $arContext);
		}

		return false;
	}

	public static function CheckContext($currentUserId = false, $userId = false, $arContext = array())
	{
		if (
			intval($currentUserId) <= 0
			|| intval($userId) <= 0
			|| !is_array($arContext)
			|| empty($arContext["ENTITY_TYPE"])
			|| empty($arContext["ENTITY_ID"])
		)
		{
			return false;
		}

		if ($arContext["ENTITY_TYPE"] == "LOG_ENTRY")
		{
			$dbRes = CSocNetLogRights::GetList(
				array(),
				array(
					"LOG_ID" => intval($arContext["ENTITY_ID"])
				)
			);

			$arLogEntryUserId = $arSonetGroupId = $arDepartmentId = array();
			$bIntranetInstalled = IsModuleInstalled('intranet');

			while ($arRes = $dbRes->Fetch())
			{
				if (preg_match('/^U(\d+)$/', $arRes["GROUP_CODE"], $matches))
				{
					$arLogEntryUserId[] = $matches[1];
				}
				elseif (
					preg_match('/^SG(\d+)$/', $arRes["GROUP_CODE"], $matches)
					|| preg_match('/^SG(\d+)_'.SONET_ROLES_USER.'$/', $arRes["GROUP_CODE"], $matches)
					&& !in_array($matches[1], $arSonetGroupId)
				)
				{
					$arSonetGroupId[] = $matches[1];
				}
				elseif (
					$bIntranetInstalled
					&& preg_match('/^DR(\d+)$/', $arRes["GROUP_CODE"], $matches)
					&& !in_array($matches[1], $arDepartmentId)
				)
				{
					$arDepartmentId[] = $matches[1];
				}
				elseif ($arRes["GROUP_CODE"] == 'G2')
				{
					if (!empty($arContext['SITE_ID']))
					{
						$arLogSite = array();
						$rsSite = CSocNetLog::GetSite(intval($arContext["ENTITY_ID"]));
						while ($arSite = $rsSite->Fetch())
						{
							$arLogSite[] = $arSite["SITE_ID"];
						}

						return in_array($arContext['SITE_ID'], $arLogSite);
					}
				}
			}

			if (
				in_array($currentUserId, $arLogEntryUserId)
				&& in_array($userId, $arLogEntryUserId)
			)
			{
				return true;
			}
			else // check by log USER_ID field / author
			{
				if (in_array($userId, $arLogEntryUserId))
				{
					if (!empty($arSonetGroupId))
					{
						foreach($arSonetGroupId as $groupId)
						{
							if (CSocNetUserToGroup::GetUserRole($currentUserId, $groupId) <= SONET_ROLES_USER)
							{
								return true;
							}
						}
					}

					if (
						!empty($arDepartmentId)
						&& CModule::IncludeModule('intranet')
					)
					{
						$arDepartmentUserId = array();

						$rsDepartmentUserId = \Bitrix\Intranet\Util::getDepartmentEmployees(array(
							'DEPARTMENTS' => $arDepartmentId,
							'RECURSIVE' => 'Y',
							'ACTIVE' => 'Y',
							'CONFIRMED' => 'Y',
							'SELECT' => array('ID')
						));

						while ($arUser = $rsDepartmentUserId->Fetch())
						{
							$arDepartmentUserId[] = $arUser["ID"];
						}

						if (in_array($currentUserId, $arDepartmentUserId))
						{
							return true;
						}
					}
				}

				$rsLog = CSocNetLog::GetList(
					array(),
					array(
						"ID" => intval($arContext["ENTITY_ID"])
					),
					false,
					false,
					array(
						"USER_ID"
					)
				);
				if ($arLog = $rsLog->Fetch())
				{
					return (
						(
							in_array($currentUserId, $arLogEntryUserId)
							&& ($userId == $arLog["USER_ID"])
						)
						|| (
							in_array($userId, $arLogEntryUserId)
							&& ($currentUserId == $arLog["USER_ID"])
						)
					);
				}
			}
		}

		return false;
	}
}
