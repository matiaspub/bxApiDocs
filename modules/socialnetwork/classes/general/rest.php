<?
if(!CModule::IncludeModule('rest'))
	return;

class CSocNetLogRestService extends IRestService
{
	private static $arAllowedOperations = array('', '!', '<', '<=', '>', '>=', '><', '!><', '?', '=', '!=', '%', '!%', '');

	public static function OnRestServiceBuildDescription()
	{
		return array(
			"log" => array(
				"log.blogpost.add" => array("CSocNetLogRestService", "AddBlogPost"),
			),
			"sonet_group" => array(
				"sonet_group.get" => array("CSocNetLogRestService", "getGroup"),
				"sonet_group.create" => array("CSocNetLogRestService", "createGroup"),
				"sonet_group.update" => array("CSocNetLogRestService", "updateGroup"),
				"sonet_group.delete" => array("CSocNetLogRestService", "deleteGroup"),
				"sonet_group.user.get" => array("CSocNetLogRestService", "getGroupUsers"),
				"sonet_group.user.invite" => array("CSocNetLogRestService", "inviteGroupUsers"),
				"sonet_group.user.request" => array("CSocNetLogRestService", "requestGroupUser"),
				"sonet_group.user.groups" => array("CSocNetLogRestService", "getUserGroups"),
				"sonet_group.feature.access" => array("CSocNetLogRestService", "getGroupFeatureAccess"),
			),
		);
	}

	public static function AddBlogPost($arFields)
	{
		if (!is_array($_POST))
			$_POST = array();

		$_POST = array_merge($_POST, array("apply" => "Y", "decode" => "Y"), $arFields);

		$strPathToPost = COption::GetOptionString("socialnetwork", "userblogpost_page", false, SITE_ID);
		$strPathToSmile = COption::GetOptionString("socialnetwork", "smile_page", false, SITE_ID);
		$BlogGroupID = COption::GetOptionString("socialnetwork", "userbloggroup_id", false, SITE_ID);

		$arBlogComponentParams = Array(
			"IS_REST" => "Y",
			"ID" => "new",
			"PATH_TO_POST" => $strPathToPost,
			"PATH_TO_SMILE" => $strPathToSmile,
			"GROUP_ID" => $BlogGroupID,
			"USER_ID" => $GLOBALS["USER"]->GetID(),
			"USE_SOCNET" => "Y",
			"MICROBLOG" => "Y"
		);

		ob_start();
		$result = $GLOBALS["APPLICATION"]->IncludeComponent(
			"bitrix:socialnetwork.blog.post.edit",
			"",
			$arBlogComponentParams,
			false,
			array("HIDE_ICONS" => "Y")
		);
		ob_end_clean();

		if (!$result)
			throw new Exception('Error');
		else
			return true;
	}

	public static function createGroup($arFields)
	{
		if (!is_array($arFields))
		{
			throw new Exception('Incorrect input data');
		}

		foreach($arFields as $key => $value)
		{
			if (
				substr($key, 0, 1) == "~"
				|| substr($key, 0, 1) == "="
			)
			{
				unset($arFields[$key]);
			}
		}

		if (isset($arFields["IMAGE_ID"]))
		{
			unset($arFields["IMAGE_ID"]);
		}

		if (
			!is_set($arFields, "SITE_ID") 
			|| strlen($arFields["SITE_ID"]) <= 0
		)
		{
			$arFields["SITE_ID"] = array(SITE_ID);
		}

		if (
			!is_set($arFields, "SUBJECT_ID") 
			|| intval($arFields["SUBJECT_ID"]) <= 0
		)
		{
			$rsSubject = CSocNetGroupSubject::GetList(
				array("SORT" => "ASC"),
				array("SITE_ID" => $arFields["SITE_ID"]),
				false,
				false,
				array("ID")
			);
			if ($arSubject = $rsSubject->Fetch())
			{
				$arFields["SUBJECT_ID"] = $arSubject["ID"];
			}
		}

		$groupID = CSocNetGroup::CreateGroup($GLOBALS["USER"]->GetID(), $arFields, false);

		if($groupID <= 0)
		{
			throw new Exception('Cannot create group');
		}

		return $groupID;
	}

	public static function updateGroup($arFields)
	{
		foreach($arFields as $key => $value)
		{
			if (
				substr($key, 0, 1) == "~"
				|| substr($key, 0, 1) == "="
			)
			{
				unset($arFields[$key]);
			}
		}

		if (isset($arFields["IMAGE_ID"]))
		{
			unset($arFields["IMAGE_ID"]);
		}

		$groupID = $arFields['GROUP_ID'];
		unset($arFields['GROUP_ID']);

		if(intval($groupID) <= 0)
		{
			throw new Exception('Wrong group ID');
		}

		$arFilter = array(
			"ID" => $groupID
		);

		if (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
		{
			$arFilter['CHECK_PERMISSIONS'] = $GLOBALS["USER"]->GetID();
		}

		$dbRes = CSocNetGroup::GetList(array(), $arFilter);
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			if (
				$arGroup["OWNER_ID"] == $GLOBALS["USER"]->GetID()
				|| CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
			{
				$res = CSocNetGroup::Update($arGroup["ID"], $arFields, false);
				if(intval($res) <= 0)
				{
					throw new Exception('Cannot update group');
				}
			}
			else
			{
				throw new Exception('User has no permissions to update group');
			}

			return $res;
		}
		else
		{
			throw new Exception('Socialnetwork group not found');
		}

		return false;
	}

	public static function deleteGroup($arFields)
	{
		$groupID = $arFields['GROUP_ID'];

		if(intval($groupID) <= 0)
			throw new Exception('Wrong group ID');

		$arFilter = array(
			"ID" => $groupID
		);

		if (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
		{
			$arFilter['CHECK_PERMISSIONS'] = $GLOBALS["USER"]->GetID();
		}

		$dbRes = CSocNetGroup::GetList(array(), $arFilter);
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			if (
				$arGroup["OWNER_ID"] == $GLOBALS["USER"]->GetID()
				|| CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
			)
			{
				if (!CSocNetGroup::Delete($arGroup["ID"]))
					throw new Exception('Cannot delete group');
			}
			else
				throw new Exception('User has no permissions to delete group');
		}
		else
			throw new Exception('Socialnetwork group not found');

		return true;
	}

	public static function getGroup($arFields, $n, $server)
	{
		$arOrder = $arFields['ORDER'];
		if(!is_array($arOrder))
		{
			$arOrder = array("ID" => "DESC");
		}
		
		if ($arFields['IS_ADMIN'] == 'Y')
		{
			if (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
			{
				unset($arFields['IS_ADMIN']);
			}
		}

		$arFilter = self::checkGroupFilter($arFields['FILTER']);
		if ($arFields['IS_ADMIN'] != 'Y')
		{
			$arFilter['CHECK_PERMISSIONS'] = $GLOBALS["USER"]->GetID();
		}

		$result = array();
		$dbRes = CSocNetGroup::GetList($arOrder, $arFilter, false, self::getNavData($n));
		while($arRes = $dbRes->Fetch())
		{
			$arRes['DATE_CREATE'] = CRestUtil::ConvertDateTime($arRes['DATE_CREATE']);
			$arRes['DATE_UPDATE'] = CRestUtil::ConvertDateTime($arRes['DATE_UPDATE']);
			$arRes['DATE_ACTIVITY'] = CRestUtil::ConvertDateTime($arRes['DATE_ACTIVITY']);

			if($arRes['IMAGE_ID'] > 0)
			{
				$arRes['IMAGE'] = self::getFile($arRes['IMAGE_ID']);
			}

			if (
				CModule::IncludeModule("extranet")
				&& ($extranet_site_id = CExtranet::GetExtranetSiteID())
			)
			{
				$arRes["IS_EXTRANET"] = "N";
				$rsGroupSite = CSocNetGroup::GetSite($arRes["ID"]);
				while ($arGroupSite = $rsGroupSite->Fetch())
				{
					if ($arGroupSite["LID"] == $extranet_site_id)
					{
						$arRes["IS_EXTRANET"] = "Y";
						break;
					}
				}
			}

			unset($arRes['INITIATE_PERMS']);
			unset($arRes['SPAM_PERMS']);
			unset($arRes['IMAGE_ID']);
			
			$result[] = $arRes;
		}

		return self::setNavData($result, $dbRes);
	}

	public static function getGroupUsers($arFields, $n, $server)
	{
		$GROUP_ID = intval($arFields['ID']);

		if($GROUP_ID > 0)
		{
			$arFilter = array(
				"ID" => $GROUP_ID
			);

			if (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
			{
				$arFilter['CHECK_PERMISSIONS'] = $GLOBALS["USER"]->GetID();
			}

			$dbRes = CSocNetGroup::GetList(array(), $arFilter);
			$arGroup = $dbRes->Fetch();
			if(is_array($arGroup))
			{
				$dbRes = CSocNetUserToGroup::GetList(
					array('ID' => 'ASC'),
					array(
						'GROUP_ID' => $arGroup['ID'],
						'<=ROLE' => SONET_ROLES_USER
					), false, false, array('USER_ID', 'ROLE')
				);

				$res = array();
				while ($arRes = $dbRes->Fetch())
				{
					$res[] = $arRes;
				}

				return $res;
			}
			else
			{
				throw new Exception('Socialnetwork group not found');
			}
		}
		else
		{
			throw new Exception('Wrong socialnetwork group ID');
		}
	}

	public static function inviteGroupUsers($arFields)
	{
		global $USER;

		$groupID = $arFields['GROUP_ID'];
		$arUserID = $arFields['USER_ID'];
		$message = $arFields['MESSAGE'];

		if(intval($groupID) <= 0)
			throw new Exception('Wrong group ID');

		if (
			(is_array($arUserID) && count($arUserID) <= 0)
			|| (!is_array($arUserID) && intval($arUserID) <= 0)
		)
			throw new Exception('Wrong user IDs');

		if (!is_array($arUserID))
			$arUserID = array($arUserID);

		$arSuccessID = array();

		$dbRes = CSocNetGroup::GetList(array(), array(
			"ID" => $groupID,
			"CHECK_PERMISSIONS" => $USER->GetID(),
		));
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			foreach($arUserID as $user_id)
			{
				$isCurrentUserTmp = ($USER->GetID() == $user_id);
				$canInviteGroup = CSocNetUserPerms::CanPerformOperation($USER->GetID(), $user_id, "invitegroup", CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false));
				$user2groupRelation = CSocNetUserToGroup::GetUserRole($user_id, $arGroup["ID"]);

				if (
					!$isCurrentUserTmp && $canInviteGroup && !$user2groupRelation
					&& CSocNetUserToGroup::SendRequestToJoinGroup($USER->GetID(), $user_id, $arGroup["ID"], $message, true)
				)
					$arSuccessID[] = $user_id;
			}
		}
		else
			throw new Exception('Socialnetwork group not found');

		return $arSuccessID;
	}

	public static function requestGroupUser($arFields)
	{
		$groupID = $arFields['GROUP_ID'];
		$message = $arFields['MESSAGE'];

		if(intval($groupID) <= 0)
			throw new Exception('Wrong group ID');

		$dbRes = CSocNetGroup::GetList(array(), array(
			"ID" => $groupID,
			"CHECK_PERMISSIONS" => $GLOBALS["USER"]->GetID()
		));
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			$url = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"].CComponentEngine::MakePathFromTemplate("/workgroups/group/#group_id#/requests/", array("group_id" => $arGroup["ID"]));

			if (!CSocNetUserToGroup::SendRequestToBeMember($GLOBALS["USER"]->GetID(), $arGroup["ID"], $message, $url, false))
				throw new Exception('Cannot request to join group');

			return true;
		}
		else
			throw new Exception('Socialnetwork group not found');
	}


	public static function getUserGroups($arFields, $n, $server)
	{
		global $USER;

		$dbRes = CSocNetUserToGroup::GetList(
			array('ID' => 'ASC'),
			array(
				'USER_ID' => $USER->GetID(),
				'<=ROLE' => SONET_ROLES_USER
			), false, false, array('GROUP_ID', 'GROUP_NAME', 'ROLE')
		);

		$res = array();
		while ($arRes = $dbRes->Fetch())
		{
			$res[] = $arRes;
		}

		return $res;
	}

	public static function getGroupFeatureAccess($arFields)
	{
		$arSocNetFeaturesSettings = CSocNetAllowed::GetAllowedFeatures();

		$groupID = intval($arFields["GROUP_ID"]);
		$feature = trim($arFields["FEATURE"]);
		$operation = trim($arFields["OPERATION"]);

		if ($groupID <= 0)
		{
			throw new Exception("Wrong socialnetwork group ID");
		}

		if (
			strlen($feature) <= 0
			|| !array_key_exists($feature, $arSocNetFeaturesSettings)
			|| !array_key_exists("allowed", $arSocNetFeaturesSettings[$feature])
			|| !in_array(SONET_ENTITY_GROUP, $arSocNetFeaturesSettings[$feature]["allowed"])
		)
		{
			throw new Exception("Wrong feature");
		}

		if (
			strlen($operation) <= 0
			|| !array_key_exists("operations", $arSocNetFeaturesSettings[$feature])
			|| !array_key_exists($operation, $arSocNetFeaturesSettings[$feature]["operations"])
		)
		{
			throw new Exception("Wrong operation");
		}

		return CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, $groupID, $feature, $operation);
	}

	private static function checkGroupFilter($arFilter)
	{

		if(!is_array($arFilter))
		{
			$arFilter = array();
		}
		else
		{
			foreach ($arFilter as $key => $value)
			{
				if(preg_match('/^([^a-zA-Z]*)(.*)/', $key, $matches))
				{
					$operation = $matches[1];
					$field = $matches[2];

					if(!in_array($operation, self::$arAllowedOperations))
					{
						unset($arFilter[$key]);
					}
					else
					{
						switch($field)
						{
							case 'DATE_CREATE':
							case 'DATE_ACTIVITY':
							case 'DATE_UPDATE':
								$arFilter[$key] = CRestUtil::unConvertDateTime($value);
							break;

							case 'CHECK_PERMISSIONS':
								unset($arFilter[$key]);
							break;

							default:
							break;
						}
					}
				}
			}
		}

		return $arFilter;
	}

	private static function getFile($fileId)
	{
		$arFile = CFile::GetFileArray($fileId);
		if(is_array($arFile))
		{
			return $arFile['SRC'];
		}
		else
		{
			return '';
		}
	}
}
?>