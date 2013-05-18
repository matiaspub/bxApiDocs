<?
IncludeModuleLangFile(__FILE__);

class CAllIMContactList
{
	private $user_id = 0;

	function __construct($user_id = false)
	{
		global $USER;
		$user_id = intval($user_id);
		if ($user_id == 0)
			$user_id = intval($USER->GetID());

		$this->user_id = $user_id;
	}

	public static function GetList($arParams = Array())
	{
		global $USER, $CACHE_MANAGER;

		$bLoadUsers = isset($arParams['LOAD_USERS']) && $arParams['LOAD_USERS'] == 'N'? false: true;

		$arGroups = array();
		if(defined("BX_COMP_MANAGED_CACHE"))
			$ttl = 2592000;
		else
			$ttl = 600;

		$bIntranetEnable = false;
		if(CModule::IncludeModule('intranet') && CModule::IncludeModule('iblock'))
		{
			$bIntranetEnable = true;
			if (!(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()))
			{
				$arGroupStatus = CUserOptions::GetOption('IM', 'groupStatus');
				if(($iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0)) > 0)
				{
					$cache_id = 'im_structure_'.$iblock_id;
					$obIMCache = new CPHPCache;
					$cache_dir = '/bx/im/structure';

					if($obIMCache->InitCache($ttl, $cache_id, $cache_dir))
					{
						$tmpVal = $obIMCache->GetVars();
						$arStructureName = $tmpVal['STRUCTURE_NAME'];
						unset($tmpVal);
					}
					else
					{
						if(defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->StartTagCache($cache_dir);

						$arResult["Structure"] = array();
						$sec = CIBlockSection::GetList(
							Array("left_margin"=>"asc","SORT"=>"ASC"),
							Array("ACTIVE"=>"Y","IBLOCK_ID"=>$iblock_id),
							false,
							Array('ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID')
						);
						$arStructureName = Array();
						while($ar = $sec->GetNext(true, false))
						{
							if ($ar['DEPTH_LEVEL'] > 1)
								$ar['NAME'] .= ' / '.$arStructureName[$ar['IBLOCK_SECTION_ID']];
							$arStructureName[$ar['ID']] = $ar['NAME'];
						}

						if(defined("BX_COMP_MANAGED_CACHE"))
						{
							$CACHE_MANAGER->RegisterTag('iblock_id_'.$iblock_id);
							$CACHE_MANAGER->EndTagCache();
						}

						if($obIMCache->StartDataCache())
						{
							$obIMCache->EndDataCache(array(
								'STRUCTURE_NAME' => $arStructureName
							));
						}
					}

					unset($obIMCache);

					foreach ($arStructureName as $key => $value)
					{
						$arGroups[$key] = Array('id' => $key, 'status' => (isset($arGroupStatus[$key]) && $arGroupStatus[$key] == 'open'? 'open': 'close'), 'name' => $value);
					}
				}
				$arGroups['other'] = Array('id' => 'other', 'status' => (isset($arGroupStatus['other']) && $arGroupStatus['other'] == 'open'? 'open': 'close'), 'name' => GetMessage('IM_CL_GROUP_OTHER'));
			}
		}
		else
		{
			$arGroups['other'] = Array('id' => 'other', 'status' => (isset($arGroupStatus['other']) && $arGroupStatus['other'] == 'open'? 'open': 'close'), 'name' => GetMessage('IM_CL_GROUP_OTHER_2'));
		}

		$arWoGroups = array(
			'all' => array(
				'id' => 'all',
				'status' => (isset($arGroupStatus['all']) && $arGroupStatus['all'] == 'close'? 'close': 'open'),
				'name' => GetMessage('IM_CL_GROUP_ALL')
			),
			'other' => array(
				'id' => 'other',
				'status' => (isset($arGroupStatus['other']) && $arGroupStatus['other'] == 'open'? 'open': 'close'),
				'name' => $bIntranetEnable? GetMessage('IM_CL_GROUP_OTHER'): GetMessage('IM_CL_GROUP_OTHER_2')
			)
		);

		$arUserSG = array();
		$arUsers = array();
		$arUserInGroup = array();
		$arWoUserInGroup = array();
		$arExtranetUsers = array();

		if (CModule::IncludeModule('extranet') && CModule::IncludeModule("socialnetwork"))
		{
			$cache_id = 'im_user_sg_'.$USER->GetID();
			$obSGCache = new CPHPCache;
			$cache_dir = '/bx/im/sonet';

			if($obSGCache->InitCache($ttl, $cache_id, $cache_dir))
			{
				$tmpVal = $obSGCache->GetVars();
				$arUserSG = $tmpVal['USER_SG'];
				$arExtranetUsers = $tmpVal['EXTRANET_USERS'];
				$arUserInGroup = $tmpVal['USER_IN_GROUP'];
				$arWoUserInGroup = $tmpVal['WO_USER_IN_GROUP'];
				unset($tmpVal);
			}
			else
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
					$CACHE_MANAGER->StartTagCache($cache_dir);

				$dbUsersInGroup = CSocNetUserToGroup::GetList(
					array(),
					array(
						"USER_ID" => $USER->GetID(),
						"<=ROLE" => SONET_ROLES_USER,
						"GROUP_SITE_ID" => CExtranet::GetExtranetSiteID(),
						"GROUP_ACTIVE" => "Y",
						"GROUP_CLOSED" => "N"
					),
					false,
					false,
					array("ID", "GROUP_ID", "GROUP_NAME")
				);

				$arUserSocNetGroups = Array();
				while ($ar = $dbUsersInGroup->GetNext(true, false))
				{
					$arUserSocNetGroups[] = $ar["GROUP_ID"];
					$arUserSG['SG'.$ar['GROUP_ID']] = array(
						'id' => 'SG'.$ar['GROUP_ID'],
						'status' => (isset($arGroupStatus['SG'.$ar['GROUP_ID']]) && $arGroupStatus['SG'.$ar['GROUP_ID']] == 'open'? 'open': 'close'),
						'name' => GetMessage('IM_CL_GROUP_SG').$ar['GROUP_NAME']
					);
					if(defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->RegisterTag('sonet_group_'.$ar['GROUP_ID']);
						$CACHE_MANAGER->RegisterTag('sonet_user2group_G'.$ar['GROUP_ID']);
					}
				}

				if (count($arUserSocNetGroups) > 0)
				{
					$dbUsersInGroup = CSocNetUserToGroup::GetList(
						array(),
						array(
							"GROUP_ID" => $arUserSocNetGroups,
							"<=ROLE" => SONET_ROLES_USER,
							"USER_ACTIVE" => "Y"
						),
						false,
						false,
						array("ID", "USER_ID", "GROUP_ID")
					);

					while ($ar = $dbUsersInGroup->GetNext(true, false))
					{
						if($USER->GetID() != $ar["USER_ID"])
						{
							$arExtranetUsers[$ar["USER_ID"]] = $ar["USER_ID"];

							if (isset($arUserInGroup["SG".$ar["GROUP_ID"]]))
								$arUserInGroup["SG".$ar["GROUP_ID"]]['users'][] = $ar["USER_ID"];
							else
								$arUserInGroup["SG".$ar["GROUP_ID"]] = Array('id' => "SG".$ar["GROUP_ID"], 'users' => Array($ar["USER_ID"]));

							if (isset($arWoUserInGroup["extranet"]))
								$arWoUserInGroup["extranet"]['users'][] = $ar["USER_ID"];
							else
								$arWoUserInGroup["extranet"] = Array('id' => "extranet", 'users' => Array($ar["USER_ID"]));
						}
					}
					if (isset($arWoUserInGroup['extranet']) && isset($arWoUserInGroup['extranet']['users']))
						$arWoUserInGroup['extranet']['users'] = array_values(array_unique($arWoUserInGroup['extranet']['users']));
				}
				if(defined("BX_COMP_MANAGED_CACHE"))
					$CACHE_MANAGER->EndTagCache();
				if($obSGCache->StartDataCache())
				{
					$obSGCache->EndDataCache(array(
							'USER_SG' => $arUserSG,
							'EXTRANET_USERS' => $arExtranetUsers,
							'USER_IN_GROUP' => $arUserInGroup,
							'WO_USER_IN_GROUP' => $arWoUserInGroup
						)
					);
				}
			}
			unset($obSGCache);
			if(is_array($arUserSG))
				$arGroups = $arGroups+$arUserSG;
		}

		$bFriendEnable = false;
		if ((!CModule::IncludeModule('extranet') || !CExtranet::IsExtranetSite()) && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
		{
			$bFriendEnable = true;
			$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $USER->GetID(), "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID", "DATE_CREATE", "DATE_UPDATE", "INITIATED_BY"));
			if ($dbFriends)
			{
				while ($arFriends = $dbFriends->GetNext(true, false))
				{
					$friendId = $pref = (IntVal($USER->GetID()) == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"];
					$arFriendUsers[$friendId] = $friendId;

					if (isset($arUserInGroup["friends"]))
						$arUserInGroup["friends"]['users'][] = $friendId;
					else
						$arUserInGroup["friends"] = Array('id' => "friends", 'users' => Array($friendId));

					if (isset($arWoUserInGroup["all"]))
						$arWoUserInGroup["all"]['users'][] = $friendId;
					else
						$arWoUserInGroup["all"] = Array('id' => "all", 'users' => Array($friendId));
				}
			}
			$arGroups['friends'] = array(
				'id' => 'friends',
				'status' => (isset($arGroupStatus['friends']) && $arGroupStatus['friends'] == 'close'? 'close': 'open'),
				'name' => GetMessage('IM_CL_GROUP_FRIENDS')
			);
		}

		$arFilter = array('ACTIVE' => 'Y');
		if (CModule::IncludeModule('extranet'))
		{
			if(!CExtranet::IsIntranetUser())
				$arFilter['ID'] = $USER->GetID()."|".implode('|', $arExtranetUsers);

			$arWoGroups['extranet'] = array(
				'id' => 'extranet',
				'status' => (isset($arGroupStatus['extranet']) && $arGroupStatus['extranet'] == 'open'? 'open': 'close'),
				'name' => GetMessage('IM_CL_GROUP_EXTRANET')
			);
		}
		if ($bLoadUsers)
		{
			if ($bFriendEnable)
			{
				if (!$bIntranetEnable)
				{
					$arFilter['ID'] = $USER->GetID();
					if (!empty($arFriendUsers))
						$arFilter['ID'] .= "|".implode('|', $arFriendUsers);
					if (!empty($arExtranetUsers))
						$arFilter['ID'] .= "|".implode('|', $arExtranetUsers);
				}
			}

			$bCLCacheEnable = false;
			if ($bIntranetEnable && !$bFriendEnable)
				$bCLCacheEnable = true;

			if ($bCLCacheEnable && CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
				$bCLCacheEnable = false;

			$nameTemplate = COption::GetOptionString("im", "user_name_template", "#LAST_NAME# #NAME#", SITE_ID);
			$nameTemplateSite = CSite::GetNameFormat(false);
			$cache_id = 'im_contact_list_'.$nameTemplate.'_'.$nameTemplateSite.(!empty($arExtranetUsers)? '_'.$USER->GetID(): '');
			$obCLCache = new CPHPCache;
			$cache_dir = '/bx/im/contact';

			$arUsersToGroup = array();
			$arUserInGroupStructure = array();

			if($bCLCacheEnable && $obCLCache->InitCache($ttl, $cache_id, $cache_dir))
			{
				$tmpVal = $obCLCache->GetVars();
				$arUsers = $tmpVal['USERS'];
				$arWoUserInGroup['all'] = $tmpVal['WO_USER_IN_GROUP_ALL'];
				$arUsersToGroup = $tmpVal['USER_TO_GROUP'];
				$arUserInGroupStructure = $tmpVal['USER_IN_GROUP'];
				unset($tmpVal);
				$arOnline = self::GetStatus();
				foreach ($arUsers as $userId => $value)
				{
					$arUsers[$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arUsers[$userId]['birthday']): false;
					$arUsers[$userId]['status'] = isset($arOnline['users'][$userId])? 'online': 'offline';
				}
			}
			else
			{
				$arExtParams = Array('FIELDS' => Array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "PERSONAL_BIRTHDAY", "IS_ONLINE"));
				if ($bIntranetEnable)
					$arExtParams['SELECT'] = array('UF_DEPARTMENT');

				$dbUsers = CUser::GetList(($sort_by = Array('last_name'=>'asc')), ($dummy=''), $arFilter, $arExtParams);
				while ($arUser = $dbUsers->GetNext(true, false))
				{
					$skipUser = false;
					if(is_array($arUser["UF_DEPARTMENT"]) && !empty($arUser["UF_DEPARTMENT"]))
					{
						foreach($arUser["UF_DEPARTMENT"] as $dep_id)
						{
							if (isset($arUserInGroupStructure[$dep_id]))
								$arUserInGroupStructure[$dep_id]['users'][] = $arUser["ID"];
							else
								$arUserInGroupStructure[$dep_id] = Array('id' => $dep_id, 'users' => Array($arUser["ID"]));
						}
						if (isset($arWoUserInGroup['all']))
							$arWoUserInGroup['all']['users'][] = $arUser["ID"];
						else
							$arWoUserInGroup['all'] = Array('id' => 'all', 'users' => Array($arUser["ID"]));
					}
					else
					{
						$skipUser = true;
						if (isset($arExtranetUsers[$arUser["ID"]]))
							$skipUser = false;
						elseif (isset($arFriendUsers[$arUser["ID"]]))
							$skipUser = false;
						elseif ($arUser["ID"] == $USER->GetID())
							$skipUser = false;
					}

					if (!$skipUser)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$arUser["PERSONAL_PHOTO"],
							array('width' => 58, 'height' => 58),
							BX_RESIZE_IMAGE_EXACT,
							false
						);

						$arUsersToGroup[$arUser['ID']] = $arUser["UF_DEPARTMENT"];
						$arUsers[$arUser["ID"]] = Array(
							'id' => $arUser["ID"],
							'name' => CUser::FormatName($nameTemplateSite, $arUser, true, false),
							'nameList' => CUser::FormatName($nameTemplate, $arUser, true, false),
							'avatar' => empty($arFileTmp['src'])? '/bitrix/js/im/images/blank.gif': $arFileTmp['src'],
							'status' => $arUser['IS_ONLINE'] == 'Y'? 'online': 'offline',
							'birthday' => $arUser['PERSONAL_BIRTHDAY'],
							'profile' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('im', 'path_to_user_profile', "", CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()? "ex": false), array("user_id" => $arUser["ID"]))
						);
					}
				}
				if (isset($arWoUserInGroup['all']) && isset($arWoUserInGroup['all']['users']))
					$arWoUserInGroup['all']['users'] = array_values(array_unique($arWoUserInGroup['all']['users']));

				if ($bCLCacheEnable)
				{
					if(defined("BX_COMP_MANAGED_CACHE"))
					{
						$CACHE_MANAGER->StartTagCache($cache_dir);
						$CACHE_MANAGER->RegisterTag("USER_CARD");
						$CACHE_MANAGER->EndTagCache();
					}
					if($obCLCache->StartDataCache())
					{
						$obCLCache->EndDataCache(array(
								'USERS' => $arUsers,
								'WO_USER_IN_GROUP_ALL' => $arWoUserInGroup['all'],
								'USER_TO_GROUP' => $arUsersToGroup,
								'USER_IN_GROUP' => $arUserInGroupStructure
							)
						);
					}
				}
				foreach ($arUsers as $userId => $value)
					$arUsers[$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arUsers[$userId]['birthday']): false;
			}

			//uasort($ar, create_function('$a, $b', 'if($a["stamp"] < $b["stamp"]) return 1; elseif($a["stamp"] > $b["stamp"]) return -1; else return 0;'));
			if (isset($arUsersToGroup[$USER->GetID()]))
			{
				foreach($arUsersToGroup[$USER->GetID()] as $dep_id)
				{
					$arGroups[$dep_id]['status'] = (isset($arGroupStatus[$dep_id]) && $arGroupStatus[$dep_id] == 'close'? 'close': 'open');
				}
			}
			foreach ($arUserInGroupStructure as $key => $val)
			{
				$arUserInGroup[$key] = $val;
			}
			unset($arUsersToGroup, $arUserInGroupStructure);
		}

		$arContactList = Array('users' => $arUsers, 'groups' => $arGroups, 'woGroups' => $arWoGroups, 'userInGroup' => $arUserInGroup, 'woUserInGroup' => $arWoUserInGroup );

		foreach(GetModuleEvents("im", "OnAfterContactListGetList", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arContactList));

		return $arContactList;
	}

	public static function AllowToSend($arParams)
	{
		$bResult = false;
		if (isset($arParams['TO_USER_ID']))
		{
			$toUserId = intval($arParams['TO_USER_ID']);

			$bResult = true;
			if(IsModuleInstalled('intranet') && CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
			{
				$bResult = false;
				if (CModule::IncludeModule("socialnetwork"))
				{
					global $USER, $CACHE_MANAGER;

					if(defined("BX_COMP_MANAGED_CACHE"))
						$ttl = 2592000;
					else
						$ttl = 600;

					$cache_id = 'im_user_sg_'.$USER->GetID();
					$obSGCache = new CPHPCache;
					$cache_dir = '/bx/im/sonet';

					if($obSGCache->InitCache($ttl, $cache_id, $cache_dir))
					{
						$tmpVal = $obSGCache->GetVars();
						$bResult = in_array($toUserId, $tmpVal['EXTRANET_USERS']);
					}
					else
					{
						if(defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->StartTagCache($cache_dir);

						$dbUsersInGroup = CSocNetUserToGroup::GetList(
							array(),
							array(
								"USER_ID" => $USER->GetID(),
								"<=ROLE" => SONET_ROLES_USER,
								"GROUP_SITE_ID" => CExtranet::GetExtranetSiteID(),
								"GROUP_ACTIVE" => "Y",
								"GROUP_CLOSED" => "N"
							),
							false,
							false,
							array("ID", "GROUP_ID", "GROUP_NAME")
						);

						$arUserSocNetGroups = Array();
						$arUserSG = Array();
						while ($ar = $dbUsersInGroup->GetNext(true, false))
						{
							$arUserSocNetGroups[] = $ar["GROUP_ID"];
							$arUserSG['SG'.$ar['GROUP_ID']] = array(
								'id' => 'SG'.$ar['GROUP_ID'],
								'status' => 'close',
								'name' => GetMessage('IM_CL_GROUP_SG').$ar['GROUP_NAME']
							);
							if(defined("BX_COMP_MANAGED_CACHE"))
							{
								$CACHE_MANAGER->RegisterTag('sonet_group_'.$ar['GROUP_ID']);
								$CACHE_MANAGER->RegisterTag('sonet_user2group_G'.$ar['GROUP_ID']);
							}
						}

						$arExtranetUsers = Array();
						$arUserInGroup = Array();
						$arWoUserInGroup = Array();
						if (count($arUserSocNetGroups) > 0)
						{
							$dbUsersInGroup = CSocNetUserToGroup::GetList(
								array(),
								array(
									"GROUP_ID" => $arUserSocNetGroups,
									"<=ROLE" => SONET_ROLES_USER,
									"USER_ACTIVE" => "Y"
								),
								false,
								false,
								array("ID", "USER_ID", "GROUP_ID")
							);

							while ($ar = $dbUsersInGroup->GetNext(true, false))
							{
								if($USER->GetID() != $ar["USER_ID"])
								{
									$arExtranetUsers[$ar["USER_ID"]] = $ar["USER_ID"];

									if (isset($arUserInGroup["SG".$ar["GROUP_ID"]]))
										$arUserInGroup["SG".$ar["GROUP_ID"]]['users'][] = $ar["USER_ID"];
									else
										$arUserInGroup["SG".$ar["GROUP_ID"]] = Array('id' => "SG".$ar["GROUP_ID"], 'users' => Array($ar["USER_ID"]));

									if (isset($arWoUserInGroup["extranet"]))
										$arWoUserInGroup["extranet"]['users'][] = $ar["USER_ID"];
									else
										$arWoUserInGroup["extranet"] = Array('id' => "extranet", 'users' => Array($ar["USER_ID"]));
								}
							}
							if (isset($arWoUserInGroup['extranet']) && isset($arWoUserInGroup['extranet']['users']))
								$arWoUserInGroup['extranet']['users'] = array_values(array_unique($arWoUserInGroup['extranet']['users']));
						}
						if(defined("BX_COMP_MANAGED_CACHE"))
							$CACHE_MANAGER->EndTagCache();
						if($obSGCache->StartDataCache())
						{
							$obSGCache->EndDataCache(array(
									'USER_SG' => $arUserSG,
									'EXTRANET_USERS' => $arExtranetUsers,
									'USER_IN_GROUP' => $arUserInGroup,
									'WO_USER_IN_GROUP' => $arWoUserInGroup
								)
							);
						}
						$bResult = in_array($toUserId, $arExtranetUsers);
					}
					unset($obSGCache);
				}
			}
		}
		else if (isset($arParams['TO_CHAT_ID']))
		{
			global $DB, $USER;
			$toChatId = intval($arParams['TO_CHAT_ID']);
			$fromUserId = intval($USER->GetID());

			$strSql = "
				SELECT R.CHAT_ID
				FROM b_im_relation R
				WHERE R.USER_ID = ".$fromUserId."
					AND R.MESSAGE_TYPE = '".IM_MESSAGE_GROUP."'
					AND R.CHAT_ID = ".$toChatId."";
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				$bResult = true;
			else
				$bResult = false;
		}
		return $bResult;
	}

	static function GetUserData($arParams = Array())
	{
		$getDepartment = $arParams['DEPARTMENT'] == 'N' ? false : true;
		$useCache = $arParams['USE_CACHE'] == 'Y' ? true : false;
		$showOnline = $arParams['SHOW_ONLINE'] == 'N' ? false : true;

		$arFilter = Array();
		if (isset($arParams['ID']) && is_array($arParams['ID']) && !empty($arParams['ID']))
		{
			foreach ($arParams['ID'] as $key => $value)
				$arParams['ID'][$key] = intval($value);

			$arFilter['ID'] = implode('|', $arParams['ID']);

		}
		else if (isset($arParams['ID']) && intval($arParams['ID']) > 0)
		{
			$arFilter['ID'] = intval($arParams['ID']);
		}

		if (empty($arFilter))
			return false;

		$nameTemplate = COption::GetOptionString("im", "user_name_template", "#LAST_NAME# #NAME#", SITE_ID);
		$nameTemplateSite = CSite::GetNameFormat(false);

		$bIntranetEnable = false;
		if(CModule::IncludeModule('intranet'))
			$bIntranetEnable = true;

		if($useCache)
		{
			global $USER;
			$obCache = new CPHPCache;
			$cache_ttl = intval($arParams['CACHE_TTL']);
			if ($cache_ttl <= 0)
				$cache_ttl = defined("BX_COMP_MANAGED_CACHE") ? 18144000 : 1800;
			$cache_id = 'bx_mob_recent_list2_'.(is_object($USER)? $USER->GetID(): 'AGENT').'_'.$arFilter['ID'].'_'.$nameTemplate.'_'.$nameTemplateSite.'_'.$getDepartment.'_'.LANGUAGE_ID;
			$cache_dir = '/bx/im/rec';

			if($obCache->InitCache($cache_ttl, $cache_id, $cache_dir))
			{
				$arCacheResult = $obCache->GetVars();
				if ($showOnline)
					$arOnline = self::GetStatus(Array('ID' => array_keys($arCacheResult['users'])));
				foreach ($arCacheResult['users'] as $userId => $value)
				{
					$arCacheResult['users'][$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arCacheResult['users'][$userId]['birthday']): false;
					if ($showOnline)
						$arCacheResult['users'][$userId]['status'] = isset($arOnline['users'][$userId])? 'online': 'offline';
				}
				return $arCacheResult;
			}
		}

		$arSelect = array('FIELDS' => array("ID", "LAST_NAME", "NAME", "LOGIN", "PERSONAL_PHOTO", "SECOND_NAME", "IS_ONLINE", "PERSONAL_BIRTHDAY", "PERSONAL_GENDER"));
		if($bIntranetEnable && $getDepartment)
			$arSelect['SELECT'] = array('UF_DEPARTMENT');

		$arUsers = array();
		$arUserInGroup = array();
		$arWoUserInGroup = array();
		$dbUsers = CUser::GetList(($sort_by = Array('is_online'=>'desc', 'last_name'=>'asc')), ($dummy=''), $arFilter, $arSelect);
		while ($arUser = $dbUsers->GetNext(true, false))
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array('width' => 58, 'height' => 58),
				BX_RESIZE_IMAGE_EXACT,
				false
			);

			$arUsers[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'name' => CUser::FormatName($nameTemplateSite, $arUser, true, false),
				'nameList' => CUser::FormatName($nameTemplate, $arUser, true, false),
				'avatar' => empty($arFileTmp['src'])? '/bitrix/js/im/images/blank.gif': $arFileTmp['src'],
				'status' => ($arUser['IS_ONLINE'] == 'Y'? 'online': 'offline'),
				'birthday' => $arUser['PERSONAL_BIRTHDAY'],
				'gender' => $arUser['PERSONAL_GENDER'] == 'F'? 'F': 'M',
				'profile' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('im', 'path_to_user_profile', "", CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()? "ex": false), array("user_id" => $arUser["ID"]))
			);

			if(is_array($arUser["UF_DEPARTMENT"]) && !empty($arUser["UF_DEPARTMENT"]))
			{
				foreach($arUser["UF_DEPARTMENT"] as $dep_id)
				{
					if (isset($arUserInGroup[$dep_id]))
						$arUserInGroup[$dep_id]['users'][] = $arUser["ID"];
					else
						$arUserInGroup[$dep_id] = Array('id' => $dep_id, 'users' => Array($arUser["ID"]));
				}
				if (isset($arWoUserInGroup['all']))
					$arWoUserInGroup['all']['users'][] = $arUser["ID"];
				else
					$arWoUserInGroup['all'] = Array('id' => 'all', 'users' => Array($arUser["ID"]));
			}
		}
		foreach ($arUsers as $userId => $value)
			$arUsers[$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arUsers[$userId]['birthday']): false;

		$result = array('users' => $arUsers, 'userInGroup' => $arUserInGroup, 'woUserInGroup' => $arWoUserInGroup );

		if($useCache)
		{
			$cacheTag = array();
			if($obCache->StartDataCache())
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->StartTagCache($cache_dir);
					if(is_array($arParams['ID']))
					{
						foreach ($arParams['ID'] as $id)
						{
							$tag = 'USER_CARD_'.intval($id/100);
							if(!in_array($tag, $cacheTag))
							{
								$cacheTag[] = $tag;
								$CACHE_MANAGER->RegisterTag($tag);
							}
						}
					}
					elseif (isset($arParams['ID']) && intval($arParams['ID']) > 0)
					{
						$tag = 'USER_CARD_'.intval($arParams['ID']/100);
						$CACHE_MANAGER->RegisterTag($tag);
					}
					$CACHE_MANAGER->EndTagCache();
				}
				$obCache->EndDataCache($result);
				unset($cacheTag);
			}
		}
		return $result;
	}

	public static function GetStatus($arParams = Array())
	{
		if (!is_array($arParams))
			$arParams = Array();

		$arID = Array();
		if (isset($arParams['ID']) && is_array($arParams['ID']) && !empty($arParams['ID']))
		{
			foreach ($arParams['ID'] as $key => $value)
				$arID[] = intval($value);
		}
		else if (isset($arParams['ID']) && intval($arParams['ID']) > 0)
		{
			$arID[] = intval($arParams['ID']);
		}

		global $USER;
		if(!isset($arFilter['ID']) && !IsModuleInstalled('intranet') && is_object($USER))
		{
			$arID[] = $USER->GetID();
			if (CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed())
			{
				$dbFriends = CSocNetUserRelations::GetList(array(),array("USER_ID" => $USER->GetID(), "RELATION" => SONET_RELATIONS_FRIEND), false, false, array("ID", "FIRST_USER_ID", "SECOND_USER_ID"));
				if ($dbFriends)
				{
					while ($arFriends = $dbFriends->GetNext(true, false))
					{
						$friendId = $pref = (IntVal($USER->GetID()) == $arFriends["FIRST_USER_ID"]) ? $arFriends["SECOND_USER_ID"] : $arFriends["FIRST_USER_ID"];
						$arID[] = $friendId;
					}
				}
			}
		}

		$arUsers = Array();
		$dbUsers = CUser::GetList(($sort_by = 'ID'), ($sort_dir = 'asc'), array('LAST_ACTIVITY' => '120'), array('FIELDS' => array("ID")));
		while ($arUser = $dbUsers->GetNext(false, false))
		{
			if (!empty($arID) && !in_array($arUser["ID"], $arID))
				continue;

			$arUsers[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'status' => 'online',
			);
		}

		return Array('users' => $arUsers);
	}

	/* TODO: fix in D7 revision */
	public static function SetOnline($userId = null, $cache = false)
	{
		global $USER;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		if ($cache && $userId == $USER->GetId())
		{
			if (isset($_SESSION['USER_LAST_ONLINE']) && intval($_SESSION['USER_LAST_ONLINE'])+60 > time())
				return false;

			$_SESSION['USER_LAST_ONLINE'] = time();
		}

		CUser::SetLastActivityDate($userId);

		return true;
	}

	/* TODO: fix in D7 revision */
	public static function SetOffline($userId = null)
	{
		global $USER, $DB;

		if (is_null($userId))
			$userId = $USER->GetId();

		$userId = intval($userId);
		if ($userId <= 0)
			return false;

		$sqlDateFunction = 'NULL';
		$dbType = strtolower($DB->type);
		if ($dbType== "mysql")
			$sqlDateFunction = "DATE_SUB(NOW(), INTERVAL 120 SECOND)";
		else if ($dbType == "mssql")
			$sqlDateFunction = "dateadd(SECOND, -120, getdate())";
		else if ($dbType == "oracle")
			$sqlDateFunction = "SYSDATE-(1/24/60/60*120)";

		$DB->Query("UPDATE b_user SET LAST_ACTIVITY_DATE = ".$sqlDateFunction." WHERE ID = ".$userId);

		if ($userId == $USER->GetId())
			unset($_SESSION['IM_LAST_ONLINE']);

		return true;
	}

	public static function SetCurrentTab($userId)
	{
		$userId = intval($userId);
		if (intval($userId)<=0)
			$userId = null;
		CUserOptions::SetOption('IM', 'currentTab', $userId);

		return true;
	}

	public static function UpdateRecent($entityId, $messageId, $isChat = false, $userId = false)
	{
		$entityId = intval($entityId);
		$messageId = intval($messageId);
		if ($entityId <= 0 || $messageId <= 0)
			return false;

		$sqlUserId = "";
		if (!$isChat)
		{
			if ($userId == $entityId)
				return false;

			$userId = intval($userId);
			$sqlUserId = 'USER_ID = '.($userId<=0? $GLOBALS['USER']->GetID(): $userId).' AND';
		}

		global $DB;

		$strSQL = "
			UPDATE b_im_recent
			SET ITEM_MID = ".$messageId."
			WHERE ".$sqlUserId." ITEM_TYPE = '".($isChat? IM_MESSAGE_GROUP: IM_MESSAGE_PRIVATE)."' AND ITEM_ID = ".$entityId;
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		if (!$isChat)
		{
			$obCache = new CPHPCache();
			$obCache->CleanDir('/bx/im/rec'.CIMMessenger::GetCachePath($userId));
			CIMMessenger::SpeedFileDelete($userId, IM_SPEED_MESSAGE);
		}
		else
		{
			$obCache = new CPHPCache();
			$arRel = CIMChat::GetRelationById($entityId);
			foreach ($arRel as $rel)
			{
				$obCache->CleanDir('/bx/im/rec'.CIMMessenger::GetCachePath($rel['USER_ID']));
				CIMMessenger::SpeedFileDelete($rel['USER_ID'], IM_SPEED_GROUP);
			}
		}

		return true;
	}

	public static function DeleteRecent($entityId, $isChat = false, $userId = false)
	{
		if (is_array($entityId))
		{
			foreach ($entityId as $key => $value)
				$entityId[$key] = intval($value);

			$sqlEntityId = 'ITEM_ID IN ('.implode(',', $entityId).')';
		}
		else if (intval($entityId) > 0)
		{
			$sqlEntityId = 'ITEM_ID = '.intval($entityId);
		}
		else
			return false;

		if (intval($userId) <= 0)
			$userId = $GLOBALS['USER']->GetID();

		global $DB;

		$strSQL = "DELETE FROM b_im_recent WHERE USER_ID = ".$userId." AND ITEM_TYPE = '".($isChat? IM_MESSAGE_GROUP: IM_MESSAGE_PRIVATE)."' AND ".$sqlEntityId;
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$obCache = new CPHPCache();
		$obCache->CleanDir('/bx/im/rec'.CIMMessenger::GetCachePath($userId));

		return true;
	}

	public static function GetRecentList($arParams = Array())
	{
		global $DB, $USER;

		$bLoadUnreadMessage = isset($arParams['LOAD_UNREAD_MESSAGE']) && $arParams['LOAD_UNREAD_MESSAGE'] == 'Y'? true: false;
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;

		// Convert old version, if needed
		CIMConvert::RecentList();

		$nameTemplate = COption::GetOptionString("im", "user_name_template", "#LAST_NAME# #NAME#", SITE_ID);
		$nameTemplateSite = CSite::GetNameFormat(false);
		$nameOfSite = CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()? "ex": false;
		$bIntranetEnable = CModule::IncludeModule('intranet')? true: false;

		$arRecent = Array();
		$arUsers = Array();

		$cache_ttl = 2592000;
		$cache_id = $GLOBALS['USER']->GetID();
		$cache_dir = '/bx/im/rec'.CIMMessenger::GetCachePath($cache_id);
		$obCache = new CPHPCache();
		if($obCache->InitCache($cache_ttl, $cache_id, $cache_dir))
		{
			$ar = $obCache->GetVars();
			$arRecent = $ar['recent'];
			$arUsers = $ar['users'];
		}
		else
		{
			if (!$bTimeZone)
				CTimeZone::Disable();
			$strSql = "
				SELECT
					R.ITEM_TYPE, R.ITEM_ID,
					R.ITEM_MID M_ID, M.AUTHOR_ID M_AUTHOR_ID, M.MESSAGE M_MESSAGE, ".$DB->DateToCharFunction('M.DATE_CREATE')." M_DATE_CREATE,
					C.TITLE C_TITLE, C.AUTHOR_ID C_OWNER_ID,
					U.LOGIN, U.NAME, U.LAST_NAME, U.PERSONAL_PHOTO, U.SECOND_NAME, U.PERSONAL_BIRTHDAY, U.PERSONAL_GENDER
				FROM
				b_im_recent R
				LEFT JOIN b_user U ON R.ITEM_TYPE = '".IM_MESSAGE_PRIVATE."' AND R.ITEM_ID = U.ID
				LEFT JOIN b_im_chat C ON R.ITEM_TYPE = '".IM_MESSAGE_GROUP."' AND R.ITEM_ID = C.ID
				LEFT JOIN b_im_message M ON R.ITEM_MID = M.ID
				WHERE R.USER_ID = ".$USER->GetId();
			if (!$bTimeZone)
				CTimeZone::Enable();

			$toDelete = Array();
			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 255;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->GetNext(true, false))
			{
				$arRes['ITEM_TYPE'] = trim($arRes['ITEM_TYPE']);
				$arRes['M_DATE_CREATE'] = MakeTimeStamp($arRes['M_DATE_CREATE']);
				if ($arRes['M_DATE_CREATE']+2592000 < time())
				{
					$toDelete[$arRes['ITEM_TYPE']][] = $arRes['ITEM_ID'];
					continue;
				}

				$itemId = $arRes['ITEM_ID'];
				$item = Array(
					'TYPE' => $arRes['ITEM_TYPE'],
					'MESSAGE' => Array(
						'id' => $arRes['M_ID'],
						'senderId' => $arRes['M_AUTHOR_ID'],
						'date' => $arRes['M_DATE_CREATE'],
						'text' => $CCTP->convertText($arRes['M_MESSAGE'])
					)
				);
				$item['MESSAGE']['text'] = preg_replace("/------------------------------------------------------(.*)------------------------------------------------------/mi", " [".GetMessage('IM_QUOTE')."] ", strip_tags(str_replace(array("<br>","<br/>","<br />", "#BR#"), Array(" ", " ", " ", " "), $item['MESSAGE']['text'])));
				if ($arRes['ITEM_TYPE'] == IM_MESSAGE_PRIVATE)
				{
					$arUsers[] = $arRes['ITEM_ID'];
					$arFileTmp = CFile::ResizeImageGet(
						$arRes["PERSONAL_PHOTO"],
						array('width' => 58, 'height' => 58),
						BX_RESIZE_IMAGE_EXACT,
						false
					);

					$item['USER'] = Array(
						'id' => $arRes['ITEM_ID'],
						'name' => CUser::FormatName($nameTemplateSite, $arRes, true, false),
						'nameList' => CUser::FormatName($nameTemplate, $arRes, true, false),
						'avatar' => empty($arFileTmp['src'])? '/bitrix/js/im/images/blank.gif': $arFileTmp['src'],
						'gender' => $arRes['PERSONAL_GENDER'] == 'F'? 'F': 'M',
						'status' => 'offline',
						'birthday' => $arRes['PERSONAL_BIRTHDAY'],
						'profile' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('im', 'path_to_user_profile', "", $nameOfSite), array("user_id" => $arRes["ITEM_ID"]))
					);
				}
				else
				{
					$itemId = 'chat'.$itemId;
					$item['CHAT'] = Array(
						'id' => $arRes['ITEM_ID'],
						'name' => $arRes["C_TITLE"],
						'owner' => $arRes["C_OWNER_ID"],
					);
				}
				$arRecent[$itemId] = $item;
			}
			// remove old entity
			if (!empty($toDelete))
			{
				if (isset($toDelete[IM_MESSAGE_PRIVATE]))
					self::DeleteRecent($toDelete[IM_MESSAGE_PRIVATE]);
				if (isset($toDelete[IM_MESSAGE_GROUP]))
					self::DeleteRecent($toDelete[IM_MESSAGE_GROUP], true);
			}
			if($obCache->StartDataCache())
				$obCache->EndDataCache(Array('recent' => $arRecent, 'users' => $arUsers));
		}

		$arOnline = self::GetStatus(Array('ID' => array_values($arUsers)));
		foreach ($arRecent as $key => $value)
		{
			if ($value['TYPE'] != IM_MESSAGE_PRIVATE)
				continue;

			$arRecent[$key]['USER']['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($value['USER']['birthday']): false;
			$arRecent[$key]['USER']['status'] = isset($arOnline['users'][$value['USER']['id']])? 'online': 'offline';
		}

		if ($bLoadUnreadMessage)
		{
			$CIMMessage = new CIMMessage(false, Array(
				'hide_link' => true
			));

			$ar = $CIMMessage->GetUnreadMessage(Array(
				'LOAD_DEPARTMENT' => 'N',
				'ORDER' => 'ASC',
				'GROUP_BY_CHAT' => 'Y',
				'USE_TIME_ZONE' => $bTimeZone? 'Y': 'N'
			));
			foreach ($ar['message'] as $data)
			{
				if (!isset($arRecent[$data['senderId']]))
				{
					$arRecent[$data['senderId']] = Array(
						'TYPE' => IM_MESSAGE_PRIVATE,
						'USER' => $ar['users'][$data['senderId']]
					);
				}
				$arRecent[$data['senderId']]['MESSAGE'] = Array(
					'id' => $data['id'],
					'senderId' => $data['senderId'],
					'date' => $data['date'],
					'text' => $data['text']
				);
				$arRecent[$data['senderId']]['COUNTER'] = $data['counter'];
			}

			$CIMChat = new CIMChat(false, Array(
				'hide_link' => true
			));

			$ar = $CIMChat->GetUnreadMessage(Array(
				'ORDER' => 'ASC',
				'GROUP_BY_CHAT' => 'Y',
				'USER_LOAD' => 'N',
				'USE_TIME_ZONE' => $bTimeZone? 'Y': 'N'
			));
			foreach ($ar['message'] as $data)
			{
				if (!isset($arRecent['chat'.$data['recipientId']]))
				{
					$arRecent['chat'.$data['recipientId']] = Array(
						'TYPE' => IM_MESSAGE_GROUP,
						'CHAT' => $ar['chat']
					);
				}
				$arRecent['chat'.$data['recipientId']]['MESSAGE'] = Array(
					'id' => $data['id'],
					'senderId' => $data['senderId'],
					'date' => $data['date'],
					'text' => $data['text']
				);
				$arRecent['chat'.$data['recipientId']]['COUNTER'] = $data['counter'];
			}
		}

		if (!empty($arRecent))
		{
			sortByColumn(
				$arRecent,
				array(
					'COUNTER' => array(SORT_NUMERIC, SORT_DESC),
					'MESSAGE' => array(SORT_NUMERIC, SORT_DESC)
				),
				array(
					'COUNTER' => function($counter){ return !is_null($counter); },
					'MESSAGE' => function($recent){ return $recent['date']; }
				),
				null, true
			);
		}

		return $arRecent;
	}
}
?>