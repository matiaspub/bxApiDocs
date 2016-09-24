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
				"log.blogpost.get" => array("CSocNetLogRestService", "getBlogPost"),
				"log.blogpost.add" => array("CSocNetLogRestService", "addBlogPost"),
				"log.blogpost.getusers.important" => array("CSocNetLogRestService", "getBlogPostUsersImprtnt"),
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

	public static function getBlogPost($arFields, $n, $server)
	{
		global $USER, $USER_FIELD_MANAGER;

		$result = array();
		if (!CModule::IncludeModule("blog"))
		{
			return $result;
		}

		$tzOffset = CTimeZone::GetOffset();
		$arOrder = array("LOG_UPDATE" => "DESC");

		$arAccessCodes = $USER->GetAccessCodes();
		foreach ($arAccessCodes as $i => $code)
		{
			if (!preg_match("/^(U|D|DR)/", $code)) //Users and Departments
			{
				unset($arAccessCodes[$i]);
			}
		}

		$arEventId = array("blog_post", "blog_post_important");
		$arEventIdFullset = array();
		foreach($arEventId as $eventId)
		{
			$arEventIdFullset = array_merge($arEventIdFullset, CSocNetLogTools::FindFullSetByEventID($eventId));
		}

		$arFilter = array(
			"LOG_RIGHTS" => $arAccessCodes,
			"EVENT_ID" => array_unique($arEventIdFullset),
			"SITE_ID" => array('s1', false),
			"<=LOG_DATE" => "NOW"
		);

		$arListParams = array(
			"CHECK_RIGHTS" => "Y",
			"USE_FOLLOW" => "N",
			"USE_SUBSCRIBE" => "N"
		);

		$dbLog = CSocNetLog::GetList(
			$arOrder,
			$arFilter,
			false,
			self::getNavData($n),
			array("ID", "SOURCE_ID"),
			$arListParams
		);

		$arPostId = $arPostIdToGet = array();

		while($arLog = $dbLog->Fetch())
		{
			$arPostId[] = $arLog["SOURCE_ID"];
		}

		$cacheTtl = 2592000;

		foreach ($arPostId as $key => $postId)
		{
			$cacheId = 'blog_post_socnet_rest_'.$postId.'_ru'.($tzOffset <> 0 ? '_'.$tzOffset : '');
			$cacheDir = '/blog/socnet_post/gen/'.intval($postId / 100).'/'.$postId;
			$obCache = new CPHPCache;
			if ($obCache->InitCache($cacheTtl, $cacheId, $cacheDir))
			{
				$result[$key] = $obCache->GetVars();
			}
			else
			{
				$arPostIdToGet[$key] = $postId;
			}
			$obCache->EndDataCache();
		}

		if (!empty($arPostIdToGet))
		{
			foreach ($arPostIdToGet as $key => $postId)
			{
				$cacheId = 'blog_post_socnet_rest_'.$postId.'_ru'.($tzOffset <> 0 ? '_'.$tzOffset : '');
				$cacheDir = '/blog/socnet_post/gen/'.intval($postId / 100).'/'.$postId;
				$obCache = new CPHPCache;
				$obCache->InitCache($cacheTtl, $cacheId, $cacheDir);

				$obCache->StartDataCache();

				$dbPost = CBlogPost::GetList(
					array(),
					array("ID" => $postId),
					false,
					false,
					array(
						"ID",
						"BLOG_ID",
						"PUBLISH_STATUS",
						"TITLE",
						"AUTHOR_ID",
						"ENABLE_COMMENTS",
						"NUM_COMMENTS",
						"VIEWS",
						"CODE",
						"MICRO",
						"DETAIL_TEXT",
						"DATE_PUBLISH",
						"CATEGORY_ID",
						"HAS_SOCNET_ALL",
						"HAS_TAGS",
						"HAS_IMAGES",
						"HAS_PROPS",
						"HAS_COMMENT_IMAGES"
					)
				);

				if ($arPost = $dbPost->Fetch())
				{
					if ($arPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH)
					{
						unset($arPost);
					}
					else
					{
						if($arPost["HAS_PROPS"] != "N")
						{
							$arPostFields = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST", $arPost["ID"], LANGUAGE_ID);
							$arPost = array_merge($arPost, $arPostFields);
						}

						$result[$key] = $arPost;
					}
				}

				$obCache->EndDataCache($arPost);
			}
		}

		ksort($result);

		return self::setNavData($result, $dbLog);
	}

	public static function addBlogPost($arFields)
	{
		global $USER, $APPLICATION;

		if (!is_array($_POST))
		{
			$_POST = array();
		}

		$_POST = array_merge($_POST, array("apply" => "Y", "decode" => "N"), $arFields);
		if (isset($arFields["UF_BLOG_POST_IMPRTNT"]))
		{
			$GLOBALS["UF_BLOG_POST_IMPRTNT"] = $arFields["UF_BLOG_POST_IMPRTNT"];
		}

		$strPathToPost = COption::GetOptionString("socialnetwork", "userblogpost_page", false, SITE_ID);
		$strPathToSmile = COption::GetOptionString("socialnetwork", "smile_page", false, SITE_ID);
		$BlogGroupID = COption::GetOptionString("socialnetwork", "userbloggroup_id", false, SITE_ID);

		$arBlogComponentParams = Array(
			"IS_REST" => "Y",
			"ID" => "new",
			"PATH_TO_POST" => $strPathToPost,
			"PATH_TO_SMILE" => $strPathToSmile,
			"GROUP_ID" => $BlogGroupID,
			"USER_ID" => $USER->GetID(),
			"USE_SOCNET" => "Y",
			"MICROBLOG" => "Y"
		);

		ob_start();
		$result = $APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.blog.post.edit",
			"",
			$arBlogComponentParams,
			false,
			array("HIDE_ICONS" => "Y")
		);
		ob_end_clean();

		if (!$result)
		{
			throw new Exception('Error');
		}
		else
		{

			if (
				isset($arFields["FILES"])
				&& \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
				&& CModule::includeModule('disk')
				&& ($storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($USER->GetID()))
				&& ($folder = $storage->getFolderForUploadedFiles())
			)
			{
				// upload to storage
				$arResultFile = array();

				foreach($arFields["FILES"] as $tmp)
				{
					$arFile = CRestUtil::saveFile($tmp);

					if(is_array($arFile))
					{
						$file = $folder->uploadFile(
							$arFile, // file array
							array(
								'NAME' => $arFile["name"],
								'CREATED_BY' => $USER->GetID()
							),
							array(),
							true
						);

						if ($file)
						{
							$arResultFile[] = \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$file->getId();
						}
					}
				}

				if (!empty($arResultFile)) // update post
				{
					CBlogPost::Update($result, array("HAS_PROPS" => "Y", "UF_BLOG_POST_FILE" => $arResultFile));
				}
			}

			return $result;
		}
	}

	public static function getBlogPostUsersImprtnt($arFields)
	{
		global $CACHE_MANAGER, $USER;

		if (!is_array($arFields))
		{
			throw new Exception('Incorrect input data');
		}

		$arParams["postId"] = intval($arFields['POST_ID']);

		if($arParams["postId"] <= 0)
		{
			throw new Exception('Wrong post ID');
		}

		$arParams["nTopCount"] = 500;
		$arParams["paramName"] = 'BLOG_POST_IMPRTNT';
		$arParams["paramValue"] = 'Y';

		$arResult = array();

		$cache = new CPHPCache();
		$cache_id = "blog_post_param_".serialize(array(
			$arParams["postId"],
			$arParams["nTopCount"],
			$arParams["paramName"],
			$arParams["paramValue"]
		));
		$cache_path = $CACHE_MANAGER->GetCompCachePath(CComponentEngine::MakeComponentPath("socialnetwork.blog.blog"))."/".$arParams["postId"];
		$cache_time = (defined("BX_COMP_MANAGED_CACHE") ? 3600*24*365 : 600);

		if ($cache->InitCache($cache_time, $cache_id, $cache_path))
		{
			$arResult = $cache->GetVars();
		}
		else
		{
			$cache->StartDataCache($cache_time, $cache_id, $cache_path);

			if (CModule::IncludeModule("blog"))
			{
				if (defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->StartTagCache($cache_path);
					$CACHE_MANAGER->RegisterTag($arParams["paramName"].$arParams["postId"]);
				}

				if ($arBlogPost = CBlogPost::GetByID($arParams["postId"]))
				{
					$postPerms = CBlogPost::GetSocNetPostPerms($arParams["postId"], true, $USER->GetID(), $arBlogPost["AUTHOR_ID"]);
					if ($postPerms >= BLOG_PERMS_READ)
					{
						$db_res = CBlogUserOptions::GetList(
							array(
							),
							array(
								'POST_ID' => $arParams["postId"],
								'NAME' => $arParams["paramName"],
								'VALUE' => $arParams["paramValue"],
								'USER_ACTIVE' => 'Y'
							),
							array(
								"nTopCount" => $arParams["nTopCount"],
								"SELECT" => array("USER_ID")
							)
						);
						if ($db_res)
						{
							while ($res = $db_res->Fetch())
							{
								$arResult[] = $res["USER_ID"];
							}
						}
					}
				}

				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					$CACHE_MANAGER->EndTagCache();
				}

				$cache->EndDataCache($arResult);
			}
		}

		return $arResult;
	}

	public static function createGroup($arFields)
	{
		global $USER;

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

		$groupID = CSocNetGroup::CreateGroup($USER->GetID(), $arFields, false);

		if($groupID <= 0)
		{
			throw new Exception('Cannot create group');
		}
		else
		{
			CSocNetFeatures::SetFeature(
				SONET_ENTITY_GROUP,
				$groupID,
				'files',
				true,
				false
			);
		}

		return $groupID;
	}

	public static function updateGroup($arFields)
	{
		global $USER;

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
			$arFilter['CHECK_PERMISSIONS'] = $USER->GetID();
		}

		$dbRes = CSocNetGroup::GetList(array(), $arFilter);
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			if (
				$arGroup["OWNER_ID"] == $USER->GetID()
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
	}

	public static function deleteGroup($arFields)
	{
		global $USER;

		$groupID = $arFields['GROUP_ID'];

		if(intval($groupID) <= 0)
			throw new Exception('Wrong group ID');

		$arFilter = array(
			"ID" => $groupID
		);

		if (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
		{
			$arFilter['CHECK_PERMISSIONS'] = $USER->GetID();
		}

		$dbRes = CSocNetGroup::GetList(array(), $arFilter);
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			if (
				$arGroup["OWNER_ID"] == $USER->GetID()
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
		global $USER;

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
			$arFilter['CHECK_PERMISSIONS'] = $USER->GetID();
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
		global $USER;

		$GROUP_ID = intval($arFields['ID']);

		if($GROUP_ID > 0)
		{
			$arFilter = array(
				"ID" => $GROUP_ID
			);

			if (!CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false))
			{
				$arFilter['CHECK_PERMISSIONS'] = $USER->GetID();
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
		global $USER;

		$groupID = $arFields['GROUP_ID'];
		$message = $arFields['MESSAGE'];

		if(intval($groupID) <= 0)
			throw new Exception('Wrong group ID');

		$dbRes = CSocNetGroup::GetList(array(), array(
			"ID" => $groupID,
			"CHECK_PERMISSIONS" => $USER->GetID()
		));
		$arGroup = $dbRes->Fetch();
		if(is_array($arGroup))
		{
			$url = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"].CComponentEngine::MakePathFromTemplate("/workgroups/group/#group_id#/requests/", array("group_id" => $arGroup["ID"]));

			if (!CSocNetUserToGroup::SendRequestToBeMember($USER->GetID(), $arGroup["ID"], $message, $url, false))
			{
				throw new Exception('Cannot request to join group');
			}

			return true;
		}
		else
		{
			throw new Exception('Socialnetwork group not found');
		}
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