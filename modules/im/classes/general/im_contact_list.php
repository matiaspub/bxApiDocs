<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Im as IM;

class CAllIMContactList
{
	private $user_id = 0;

	const NETWORK_AUTH_ID = 'replica';

	public function __construct($user_id = false)
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
		$bLoadChats = isset($arParams['LOAD_CHATS']) && $arParams['LOAD_CHATS'] == 'N'? false: true;

		$arGroups = array();
		if(defined("BX_COMP_MANAGED_CACHE"))
			$ttl = 2592000;
		else
			$ttl = 600;

		$bVoximplantEnable = IsModuleInstalled('voximplant');

		$bBusShowAll = !IsModuleInstalled('intranet') && COption::GetOptionInt('im', 'contact_list_show_all_bus');

		$bIntranetEnable = false;
		$arGroupStatus = CUserOptions::GetOption('IM', 'groupStatus');
		if(CModule::IncludeModule('intranet') && CModule::IncludeModule('iblock'))
		{
			$bIntranetEnable = true;
			if (!(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser()))
			{
				if(($iblock_id = COption::GetOptionInt('intranet', 'iblock_structure', 0)) > 0)
				{
					$cache_id = 'im_structure_'.$iblock_id;
					$obIMCache = new CPHPCache;
					$cache_dir = '/bx/imc/structure';

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
						if (strlen($value) > 0)
						{
							$arGroups[$key] = Array('id' => $key, 'status' => (isset($arGroupStatus[$key]) && $arGroupStatus[$key] == 'open'? 'open': 'close'), 'name' => $value);
						}
					}
				}
			}
		}
		else if ($bBusShowAll)
		{
			$arGroups['all'] = array(
				'id' => 'all',
				'status' => (isset($arGroupStatus['all']) && $arGroupStatus['all'] == 'close'? 'close': 'open'),
				'name' => GetMessage('IM_CL_GROUP_ALL')
			);
		}
		$arGroups['chat'] = Array(
			'id' => 'chat',
			'status' => (isset($arGroupStatus['chat']) && $arGroupStatus['chat'] == 'open'? 'open': 'close'),
			'name' => GetMessage('IM_CL_GROUP_CHATS')
		);
		$arGroups['other'] = Array(
			'id' => 'other',
			'status' => (isset($arGroupStatus['other']) && $arGroupStatus['other'] == 'open'? 'open': 'close'),
			'name' => GetMessage('IM_CL_GROUP_OTHER_2')
		);
		$arGroups['search'] = Array(
			'id' => 'search',
			'status' => (isset($arGroupStatus['search']) && $arGroupStatus['search'] == 'open'? 'open': 'close'),
			'name' => GetMessage('IM_CL_GROUP_SEARCH')
		);

		$arWoGroups = array(
			'all' => array(
				'id' => 'all',
				'status' => (isset($arGroupStatus['all']) && $arGroupStatus['all'] == 'close'? 'close': 'open'),
				'name' => GetMessage('IM_CL_GROUP_ALL')
			),
			'chat' => array(
				'id' => 'chat',
				'status' => (isset($arGroupStatus['chat']) && $arGroupStatus['chat'] == 'open'? 'open': 'close'),
				'name' => GetMessage('IM_CL_GROUP_CHATS')
			),
			'other' => array(
				'id' => 'other',
				'status' => (isset($arGroupStatus['other']) && $arGroupStatus['other'] == 'open'? 'open': 'close'),
				'name' => $bIntranetEnable? GetMessage('IM_CL_GROUP_OTHER'): GetMessage('IM_CL_GROUP_OTHER_2')
			),
			'search' => array(
				'id' => 'search',
				'status' => (isset($arGroupStatus['search']) && $arGroupStatus['search'] == 'open'? 'open': 'close'),
				'name' => GetMessage('IM_CL_GROUP_SEARCH')
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
			$cache_dir = '/bx/imc/sonet';

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
							"USER_ACTIVE" => "Y",
							"USER_CONFIRM_CODE" => false
						),
						false,
						false,
						array("ID", "USER_ID", "GROUP_ID")
					);

					while ($ar = $dbUsersInGroup->GetNext(true, false))
					{
						if($ar["USER_ID"] == $USER->GetID())
							continue;

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

		$arFilter = array(
			'=ACTIVE' => 'Y',
			'=CONFIRM_CODE' => false
		);
		if (CModule::IncludeModule('extranet'))
		{
			if(!CExtranet::IsIntranetUser())
			{

				$arFilter['=ID'] = array_merge(Array($USER->GetId()), $arExtranetUsers);
			}

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
				if (!$bIntranetEnable && !$bBusShowAll)
				{
					$arFilter['=ID'][] = $USER->GetId();
					if (!empty($arFriendUsers))
					{
						$arFilter['=ID'] =  array_merge($arFilter['=ID'], $arFriendUsers);
					}
					if (!empty($arExtranetUsers))
					{
						$arFilter['=ID'] =  array_merge($arFilter['=ID'], $arExtranetUsers);
					}
				}
			}

			$bCLCacheEnable = false;
			if ($bIntranetEnable && (!$bFriendEnable || $bBusShowAll))
				$bCLCacheEnable = true;

			if ($bCLCacheEnable && CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
				$bCLCacheEnable = false;

			$bVoximplantEnable = IsModuleInstalled('voximplant');
			$bColorEnabled = IM\Color::isEnabled();

			$nameTemplate = self::GetUserNameTemplate(SITE_ID);
			$nameTemplateSite = CSite::GetNameFormat(false);
			$cache_id = 'im_contact_list_v11_'.$nameTemplate.'_'.$nameTemplateSite.(!empty($arExtranetUsers)? '_'.$USER->GetID(): '').$bVoximplantEnable.$bColorEnabled;
			$obCLCache = new CPHPCache;
			$cache_dir = '/bx/imc/contact';

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

				$arOnline = CIMStatus::GetList();
				foreach ($arUsers as $userId => $value)
				{
					$arUsers[$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arUsers[$userId]['birthday']): false;
					$arUsers[$userId]['status'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['status']: 'offline';
					$arUsers[$userId]['idle'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['idle']: 0;
					$arUsers[$userId]['mobileLastDate'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['mobileLastDate']: 0;
					if ($arOnline['users'][$userId]['color'])
					{
						$arUsers[$userId]['color'] = $arOnline['users'][$userId]['color'];
					}
				}
			}
			else
			{
				$arSelect = array("ID", "LAST_NAME", "NAME", "LOGIN", "PERSONAL_PHOTO", "SECOND_NAME", "PERSONAL_BIRTHDAY", "WORK_POSITION", "PERSONAL_GENDER", "EXTERNAL_AUTH_ID");
				if($bIntranetEnable)
				{
					$arSelect[] = 'UF_DEPARTMENT';
				}
				if ($bVoximplantEnable)
				{
					$arSelect[] = 'UF_VI_PHONE';
				}

				$query = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());

				$query->registerRuntimeField('', new \Bitrix\Main\Entity\ReferenceField('ref', 'Bitrix\Im\StatusTable', array('=this.ID' => 'ref.USER_ID')));
				$query->addSelect('ref.COLOR', 'COLOR')
					->addSelect('ref.STATUS', 'STATUS')
					->addSelect('ref.IDLE', 'IDLE')
					->addSelect('ref.MOBILE_LAST_DATE', 'MOBILE_LAST_DATE');

				$sago = Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('-180');
				$query->registerRuntimeField('', new \Bitrix\Main\Entity\ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN LAST_ACTIVITY_DATE > '.$sago.' THEN \'Y\' ELSE \'N\' END'));
				$query->addSelect('IS_ONLINE_CUSTOM');

				foreach ($arSelect as $value)
				{
					$query->addSelect($value);
				}
				foreach ($arFilter as $key => $value)
				{
					$query->addFilter($key, $value);
				}
				$resultQuery = $query->exec();

				$arExtraUser = Array();
				while ($arUser = $resultQuery->fetch())
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
					else if ($bBusShowAll)
					{
						$skipUser = false;
						if (isset($arWoUserInGroup['all']))
							$arWoUserInGroup['all']['users'][] = $arUser["ID"];
						else
							$arWoUserInGroup['all'] = Array('id' => 'all', 'users' => Array($arUser["ID"]));

						if (isset($arUserInGroup['all']))
							$arUserInGroup['all']['users'][] = $arUser["ID"];
						else
							$arUserInGroup['all'] = Array('id' => 'all', 'users' => Array($arUser["ID"]));
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
						foreach ($arUser as $key => $value)
						{
							$arUser[$key] = !is_array($value) && !is_object($value)? htmlspecialcharsEx($value): $value;
						}
						$arExtraUser[$arUser["ID"]] = $arUser;

						$arFileTmp = CFile::ResizeImageGet(
							$arUser["PERSONAL_PHOTO"],
							array('width' => 58, 'height' => 58),
							BX_RESIZE_IMAGE_EXACT,
							false,
							false,
							true
						);

						$color = self::GetUserColor($arUser["ID"], $arUser['PERSONAL_GENDER'] == 'M'? 'M': 'F');
						if (isset($arUser['COLOR']) && strlen($arUser['COLOR']) > 0)
						{
							$color = IM\Color::getColor($arUser['COLOR']);
						}
						if (!$color)
						{
							$color = self::GetUserColor($arUser["ID"], $arUser['PERSONAL_GENDER'] == 'M'? 'M': 'F');
						}

						$arUsersToGroup[$arUser['ID']] = $arUser["UF_DEPARTMENT"];
						$arUsers[$arUser["ID"]] = Array(
							'id' => $arUser["ID"],
							'name' => CUser::FormatName($nameTemplateSite, $arUser, true, false),
							'nameList' => CUser::FormatName($nameTemplate, $arUser, true, false),
							'workPosition' => $arUser['WORK_POSITION'],
							'color' => $color,
							'avatar' => empty($arFileTmp['src'])? '/bitrix/js/im/images/blank.gif': $arFileTmp['src'],
							'status' => 'offline',
							'birthday' => $arUser['PERSONAL_BIRTHDAY'],
							'gender' => $arUser['PERSONAL_GENDER'] == 'F'? 'F': 'M',
							'phoneDevice' => $bVoximplantEnable && $arUser['UF_VI_PHONE'] == 'Y',
							'extranet' => self::IsExtranet($arUser),
							'network' => $arUser['EXTERNAL_AUTH_ID'] == self::NETWORK_AUTH_ID,
							'profile' => CIMContactList::GetUserPath($arUser["ID"])
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
						$CACHE_MANAGER->RegisterTag("IM_CONTACT_LIST");
						$CACHE_MANAGER->RegisterTag($bVoximplantEnable? "USER_CARD": "USER_NAME");
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
				foreach ($arUsers as $userId => $arUser)
				{
					$arUsers[$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arUsers[$userId]['birthday']): false;
					$arUsers[$userId]['status'] = $arExtraUser[$userId]['IS_ONLINE_CUSTOM'] == 'Y'? $arExtraUser[$userId]['STATUS']: 'offline';
					$arUsers[$userId]['idle'] = $arExtraUser[$userId]['IS_ONLINE_CUSTOM'] == 'Y' && is_object($arExtraUser[$userId]['IDLE'])? $arExtraUser[$userId]['IDLE']->getTimestamp(): 0;
					$arUsers[$userId]['mobileLastDate'] = $arExtraUser[$userId]['IS_ONLINE_CUSTOM'] == 'Y' && is_object($arExtraUser[$userId]['MOBILE_LAST_DATE'])? $arExtraUser[$userId]['MOBILE_LAST_DATE']->getTimestamp(): 0;
				}
			}

			//uasort($ar, create_function('$a, $b', 'if($a["stamp"] < $b["stamp"]) return 1; elseif($a["stamp"] > $b["stamp"]) return -1; else return 0;'));
			if (is_array($arUsersToGroup[$USER->GetID()]))
			{
				foreach($arUsersToGroup[$USER->GetID()] as $dep_id)
				{
					if (isset($arGroups[$dep_id]))
					{
						$arGroups[$dep_id]['status'] = (isset($arGroupStatus[$dep_id]) && $arGroupStatus[$dep_id] == 'close'? 'close': 'open');
					}
				}
			}
			foreach ($arUserInGroupStructure as $key => $val)
			{
				$arUserInGroup[$key] = $val;
			}
			unset($arUsersToGroup, $arUserInGroupStructure);
		}

		$arChats = Array();
		if ($bLoadChats)
		{
			$bColorEnabled = IM\Color::isEnabled();
			$cache_id = 'im_chats_v4_'.$USER->GetID().'_'.$bColorEnabled;
			$obCLCache = new CPHPCache;
			$cache_dir = '/bx/imc/chats';

			$arUsersToGroup = array();
			$arUserInGroupStructure = array();

			if($obCLCache->InitCache($ttl, $cache_id, $cache_dir))
			{
				$tmpVal = $obCLCache->GetVars();
				$arChats = $tmpVal['CHATS'];
				unset($tmpVal);
			}
			else
			{
				$chats = CIMChat::GetChatData(Array(
					'SKIP_PRIVATE' => 'Y',
					'GET_LIST' => 'Y',
					'USER_ID' => $USER->GetID()
				));

				$arChats = $chats['chat'];
				if($obCLCache->StartDataCache())
				{
					$obCLCache->EndDataCache(array(
						'CHATS' => $arChats,
					));
				}
			}
		}

		$arContactList = Array('users' => $arUsers, 'groups' => $arGroups, 'chats' => $arChats, 'woGroups' => $arWoGroups, 'userInGroup' => $arUserInGroup, 'woUserInGroup' => $arWoUserInGroup );

		foreach(GetModuleEvents("im", "OnAfterContactListGetList", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arContactList));

		return $arContactList;
	}

	static function CleanChatCache($userId)
	{
		$bColorEnabled = IM\Color::isEnabled();
		$cache_id = 'im_chats_v4_'.$userId.'_'.$bColorEnabled;
		$obCLCache = new CPHPCache;
		$cache_dir = '/bx/imc/chats';

		$obCLCache->Clean($cache_id, $cache_dir);
	}

	public static function SearchUsers($searchText)
	{
		$searchText = trim($searchText);
		if (strlen($searchText) < 3)
		{
			$GLOBALS["APPLICATION"]->ThrowException(GetMessage("IM_CL_SEARCH_EMPTY"), "ERROR_SEARCH_EMPTY");
			return false;
		}

		$nameTemplate = self::GetUserNameTemplate(SITE_ID);
		$nameTemplateSite = CSite::GetNameFormat(false);

		$arFilter = array(
			"ACTIVE" => "Y",
			"CONFIRM_CODE" => false,
			"NAME" => $searchText,
		);

		$bIntranetEnable = IsModuleInstalled('intranet');
		$bVoximplantEnable = IsModuleInstalled('voximplant');

		if (!$bIntranetEnable)
		{
			$arSettings = CIMSettings::GetDefaultSettings(CIMSettings::SETTINGS);
			if ($arSettings[CIMSettings::PRIVACY_SEARCH] == CIMSettings::PRIVACY_RESULT_ALL)
				$arFilter['!=UF_IM_SEARCH'] = CIMSettings::PRIVACY_RESULT_CONTACT;
			else
				$arFilter['UF_IM_SEARCH'] = CIMSettings::PRIVACY_RESULT_ALL;
		}

		$arExtParams = Array('FIELDS' => Array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "PERSONAL_BIRTHDAY", "WORK_POSITION", "PERSONAL_GENDER", "EXTERNAL_AUTH_ID"), 'SELECT' => Array('UF_IM_SEARCH'));
		if($bIntranetEnable)
			$arExtParams['SELECT'][] = 'UF_DEPARTMENT';
		if ($bVoximplantEnable)
			$arExtParams['SELECT'][] = 'UF_VI_PHONE';

		$arUsers = Array();
		$dbUsers = CUser::GetList(($sort_by = Array('last_name'=>'asc')), ($dummy=''), $arFilter, $arExtParams);
		while ($arUser = $dbUsers->GetNext(true, false))
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array('width' => 58, 'height' => 58),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);

			$arUsers[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'name' => CUser::FormatName($nameTemplateSite, $arUser, true, false),
				'nameList' => CUser::FormatName($nameTemplate, $arUser, true, false),
				'workPosition' => $arUser['WORK_POSITION'],
				'color' => self::GetUserColor($arUser["ID"], $arUser['PERSONAL_GENDER'] == 'M'? 'M': 'F'),
				'avatar' => empty($arFileTmp['src'])? '/bitrix/js/im/images/blank.gif': $arFileTmp['src'],
				'status' => 'offline',
				'birthday' => $bIntranetEnable? CIntranetUtils::IsToday($arUser['PERSONAL_BIRTHDAY']): false,
				'gender' => $arUser['PERSONAL_GENDER'] == 'F'? 'F': 'M',
				'phoneDevice' => $bVoximplantEnable && $arUser['UF_VI_PHONE'] == 'Y',
				'extranet' => self::IsExtranet($arUser),
				'network' => $arUser['EXTERNAL_AUTH_ID'] == self::NETWORK_AUTH_ID,
				'profile' => CIMContactList::GetUserPath($arUser["ID"]),
				'searchMark' => $searchText,
			);
		}

		if (!empty($arUsers))
		{
			$arOnline = CIMStatus::GetList(Array('ID' => array_keys($arUsers), 'GET_OFFLINE' => 'Y'));
			foreach ($arUsers as $userId => $value)
			{
				$arUsers[$userId]['status'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['status']: 'offline';
				$arUsers[$userId]['idle'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['idle']: 0;
				$arUsers[$userId]['mobileLastDate'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['mobileLastDate']: 0;
				if ($arOnline['users'][$userId]['color'])
				{
					$arUsers[$userId]['color'] = $arOnline['users'][$userId]['color'];
				}
			}
		}

		if (CModule::IncludeModule('socialservices'))
		{
			$network = new \Bitrix\Socialservices\Network();
			if ($network->isEnabled())
			{
				$result = $network->searchUser($searchText);
				if ($result)
				{
					$arUserIds = array_keys($arUsers);
					$arIntersectUserIds = Array();
					foreach ($result as $arUser)
					{
						$id = 'network'.$arUser["NETWORK_ID"];
						$arUsers[$id] = Array(
							'id' => $id,
							'name' => CUser::FormatName($nameTemplateSite, $arUser, true, false),
							'nameList' => CUser::FormatName($nameTemplate, $arUser, true, false),
							'workPosition' => $arUser['CLIENT_DOMAIN'],
							'color' => IM\Color::getColor('GRAY'),
							'avatar' => empty($arUser['PERSONAL_PHOTO'])? '/bitrix/js/im/images/blank.gif': $arUser['PERSONAL_PHOTO'],
							'status' => 'guest',
							'birthday' => false,
							'gender' => $arUser['PERSONAL_GENDER'] == 'F'? 'F': 'M',
							'phoneDevice' => false,
							'extranet' => true,
							'network' => true,
							'profile' => CIMContactList::GetUserPath($arUser["ID"]),
							'select' => 'Y',
							'networkId' => $arUser['NETWORK_ID'],
							'searchMark' => $searchText,
						);
						$arIntersectUserIds[$arUser['XML_ID']] = $id;
					}
					if (!empty($arUserIds))
					{
						$result = \Bitrix\Main\UserTable::getList(Array(
							'select' => Array('XML_ID'),
							'filter' => Array(
								'=XML_ID' => array_keys($arIntersectUserIds),
								'=EXTERNAL_AUTH_ID' => \Bitrix\Socialservices\Network::EXTERNAL_AUTH_ID
							),
						));
						while($user = $result->fetch())
						{
							unset($arUsers[$arIntersectUserIds[$user['XML_ID']]]);
						}
					}
				}
			}
		}

		return Array('users' => $arUsers);
	}

	static function AllowToSend($arParams)
	{
		$bResult = false;
		if (isset($arParams['TO_USER_ID']))
		{
			global $USER;
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
					$cache_dir = '/bx/imc/sonet';

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
									"USER_ACTIVE" => "Y",
									"USER_CONFIRM_CODE" => false
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
			else if (!IsModuleInstalled('intranet'))
			{
				if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($USER->GetID(), $arParams['TO_USER_ID']))
				{
					$bResult = false;
				}
				else if (CIMSettings::GetPrivacy(CIMSettings::PRIVACY_MESSAGE, $arParams['TO_USER_ID']) == CIMSettings::PRIVACY_RESULT_CONTACT && CModule::IncludeModule('socialnetwork') && CSocNetUser::IsFriendsAllowed() && !CSocNetUserRelations::IsFriends($USER->GetID(), $arParams['TO_USER_ID']))
				{
					$bResult = false;
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
					AND R.MESSAGE_TYPE IN ('".IM_MESSAGE_CHAT."', '".IM_MESSAGE_OPEN."')
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
		$getHrPhoto = $arParams['HR_PHOTO'] == 'Y' ? true : false;
		$getPhones = $arParams['PHONES'] == 'Y' ? true : false;
		$useCache = !$getPhones && $arParams['USE_CACHE'] == 'Y' ? true : false;
		$showOnline = $arParams['SHOW_ONLINE'] == 'N' ? false : true;

		$arFilter = Array();
		if (isset($arParams['ID']) && is_array($arParams['ID']) && !empty($arParams['ID']))
		{
			foreach ($arParams['ID'] as $key => $value)
			{
				if (intval($value) > 0)
				{
					$arParams['ID'][$key] = intval($value);
				}
			}
			$arFilter['=ID'] = $arParams['ID'];
		}
		else if (isset($arParams['ID']) && intval($arParams['ID']) > 0)
		{
			$arFilter['=ID'] = Array(intval($arParams['ID']));
		}

		if (empty($arFilter))
			return false;

		$nameTemplate = self::GetUserNameTemplate(SITE_ID);
		$nameTemplateSite = CSite::GetNameFormat(false);

		$bIntranetEnable = false;
		if(IsModuleInstalled('intranet') && CModule::IncludeModule('intranet'))
			$bIntranetEnable = true;

		$bVoximplantEnable = IsModuleInstalled('voximplant');
		$bColorEnabled = IM\Color::isEnabled();

		if($useCache)
		{
			global $USER;
			$obCache = new CPHPCache;
			$cache_ttl = intval($arParams['CACHE_TTL']);
			if ($cache_ttl <= 0)
				$cache_ttl = defined("BX_COMP_MANAGED_CACHE") ? 18144000 : 1800;
			$cache_id = 'user_data_v8_'.(is_object($USER)? $USER->GetID(): 'AGENT').'_'.implode('|', $arFilter['=ID']).'_'.$nameTemplate.'_'.$nameTemplateSite.'_'.$getPhones.'_'.$getDepartment.'_'.$bIntranetEnable.'_'.$bVoximplantEnable.'_'.LANGUAGE_ID.'_'.$bColorEnabled;
			$cache_dir = '/bx/imc/userdata';

			if($obCache->InitCache($cache_ttl, $cache_id, $cache_dir))
			{
				$arCacheResult = $obCache->GetVars();
				if ($showOnline)
					$arOnline = CIMStatus::GetList(Array('ID' => array_keys($arCacheResult['users']), 'GET_OFFLINE' => 'Y'));

				foreach ($arCacheResult['users'] as $userId => $value)
				{
					$arCacheResult['users'][$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arCacheResult['users'][$userId]['birthday']): false;

					if ($showOnline)
					{
						$arCacheResult['users'][$userId]['status'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['status']: 'offline';
						$arCacheResult['users'][$userId]['idle'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['idle']: 0;
						$arCacheResult['users'][$userId]['mobileLastDate'] = isset($arOnline['users'][$userId])? $arOnline['users'][$userId]['mobileLastDate']: 0;
						if ($arOnline['users'][$userId])
						{
							$arCacheResult['users'][$userId]['color'] = $arOnline['users'][$userId]['color'];
						}
					}

					if ($getHrPhoto && !isset($arCacheResult['hrphoto']))
					{
						$arPhotoHrTmp = CFile::ResizeImageGet(
							$arCacheResult['source'][$userId]["PERSONAL_PHOTO"],
							array('width' => 200, 'height' => 200),
							BX_RESIZE_IMAGE_EXACT,
							false,
							false,
							true
						);
						$arCacheResult['hrphoto'][$userId] = empty($arPhotoHrTmp['src'])? '/bitrix/js/im/images/hidef-avatar-v3.png': $arPhotoHrTmp['src']; // TODO REMOVE DEFAULT
					}
				}
				return $arCacheResult;
			}
		}

		$arSelect = array("ID", "LAST_NAME", "NAME", "LOGIN", "PERSONAL_PHOTO", "SECOND_NAME", "PERSONAL_BIRTHDAY", "WORK_POSITION", "PERSONAL_GENDER", "EXTERNAL_AUTH_ID");
		if ($getPhones)
		{
			$arSelect[] = 'WORK_PHONE';
			$arSelect[] = 'PERSONAL_PHONE';
			$arSelect[] = 'PERSONAL_MOBILE';
		}
		if($bIntranetEnable)
		{
			$arSelect[] = 'UF_PHONE_INNER';
			$arSelect[] = 'UF_DEPARTMENT';
		}
		if ($bVoximplantEnable)
		{
			$arSelect[] = 'UF_VI_PHONE';
			$arSelect[] = 'UF_PHONE_INNER';
		}

		$arUsers = array();
		$arUserInGroup = array();
		$arPhones = array();
		$arWoUserInGroup = array();
		$arHrPhoto = array();
		$arSource = array();

		$query = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());

		$query->registerRuntimeField('', new \Bitrix\Main\Entity\ReferenceField('ref', 'Bitrix\Im\StatusTable', array('=this.ID' => 'ref.USER_ID')));
		$query->addSelect('ref.COLOR', 'COLOR')
			->addSelect('ref.STATUS', 'STATUS')
			->addSelect('ref.IDLE', 'IDLE')
			->addSelect('ref.MOBILE_LAST_DATE', 'MOBILE_LAST_DATE');

		$sago = Bitrix\Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('-180');
		$query->registerRuntimeField('', new \Bitrix\Main\Entity\ExpressionField('IS_ONLINE_CUSTOM', 'CASE WHEN LAST_ACTIVITY_DATE > '.$sago.' THEN \'Y\' ELSE \'N\' END'));
		$query->addSelect('IS_ONLINE_CUSTOM');

		foreach ($arSelect as $value)
		{
			$query->addSelect($value);
		}
		foreach ($arFilter as $key => $value)
		{
			$query->addFilter($key, $value);
		}
		$resultQuery = $query->exec();

		global $USER;

		$arExtraUser = Array();
		while ($arUser = $resultQuery->fetch())
		{
			foreach ($arUser as $key => $value)
			{
				$arUser[$key] = !is_array($value) && !is_object($value)? htmlspecialcharsEx($value): $value;
			}

			$arExtraUser[$arUser["ID"]] = $arUser;

			$arSource[$arUser["ID"]]["PERSONAL_PHOTO"] = $arUser["PERSONAL_PHOTO"];

			$arPhotoTmp = CFile::ResizeImageGet(
				$arUser["PERSONAL_PHOTO"],
				array('width' => 58, 'height' => 58),
				BX_RESIZE_IMAGE_EXACT,
				false,
				false,
				true
			);

			$color = self::GetUserColor($arUser["ID"], $arUser['PERSONAL_GENDER'] == 'M'? 'M': 'F');
			if (isset($arUser['COLOR']) && strlen($arUser['COLOR']) > 0)
			{
				$color = IM\Color::getColor($arUser['COLOR']);
			}
			if (!$color)
			{
				$color = self::GetUserColor($arUser["ID"], $arUser['PERSONAL_GENDER'] == 'M'? 'M': 'F');
			}

			$arUsers[$arUser["ID"]] = Array(
				'id' => $arUser["ID"],
				'name' => CUser::FormatName($nameTemplateSite, $arUser, true, false),
				'nameList' => CUser::FormatName($nameTemplate, $arUser, true, false),
				'workPosition' => $arUser['WORK_POSITION'],
				'color' => $color,
				'avatar' => empty($arPhotoTmp['src'])? '/bitrix/js/im/images/blank.gif': $arPhotoTmp['src'],
				'status' => 'offline',
				'birthday' => $arUser['PERSONAL_BIRTHDAY'],
				'gender' => $arUser['PERSONAL_GENDER'] == 'F'? 'F': 'M',
				'phoneDevice' => $bVoximplantEnable && $arUser['UF_VI_PHONE'] == 'Y',
				'extranet' => self::IsExtranet($arUser),
				'network' => $arUser['EXTERNAL_AUTH_ID'] == self::NETWORK_AUTH_ID,
				'profile' => CIMContactList::GetUserPath($arUser["ID"])
			);

			if($getDepartment && is_array($arUser["UF_DEPARTMENT"]) && !empty($arUser["UF_DEPARTMENT"]))
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

			if ($getHrPhoto)
			{
				$arPhotoHrTmp = CFile::ResizeImageGet(
					$arUser["PERSONAL_PHOTO"],
					array('width' => 200, 'height' => 200),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);
				$arHrPhoto[$arUser["ID"]] = empty($arPhotoHrTmp['src'])? '/bitrix/js/im/images/hidef-avatar-v3.png': $arPhotoHrTmp['src']; // TODO REMOVE DEFAULT
			}

			if ($getPhones)
			{
				if (CModule::IncludeModule('voximplant'))
				{
					$result = CVoxImplantPhone::Normalize($arUser["WORK_PHONE"]);
					if ($result)
					{
						$arPhones[$arUser["ID"]]['WORK_PHONE'] = $arUser['WORK_PHONE'];
					}
					$result = CVoxImplantPhone::Normalize($arUser["PERSONAL_MOBILE"]);
					if ($result)
					{
						$arPhones[$arUser["ID"]]['PERSONAL_MOBILE'] = $arUser['PERSONAL_MOBILE'];
					}
					$result = CVoxImplantPhone::Normalize($arUser["PERSONAL_PHONE"]);
					if ($result)
					{
						$arPhones[$arUser["ID"]]['PERSONAL_PHONE'] = $arUser['PERSONAL_PHONE'];
					}
					$result = preg_replace("/[^0-9\#\*]/i", "", $arUser["UF_PHONE_INNER"]);
					if ($result)
					{
						$arPhones[$arUser["ID"]]['INNER_PHONE'] = $result;
					}
				}
				else
				{
					$arPhones[$arUser["ID"]]['WORK_PHONE'] = $arUser['WORK_PHONE'];
					$arPhones[$arUser["ID"]]['PERSONAL_MOBILE'] = $arUser['PERSONAL_MOBILE'];
					$arPhones[$arUser["ID"]]['PERSONAL_PHONE'] = $arUser['PERSONAL_PHONE'];
				}
			}
		}

		foreach ($arUsers as $userId => $arUser)
		{
			$arUsers[$userId]['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($arUsers[$userId]['birthday']): false;
			$arUsers[$userId]['status'] = $arExtraUser[$userId]['IS_ONLINE_CUSTOM'] == 'Y'? $arExtraUser[$userId]['STATUS']: 'offline';
			$arUsers[$userId]['idle'] = $arExtraUser[$userId]['IS_ONLINE_CUSTOM'] == 'Y' && is_object($arExtraUser[$userId]['IDLE'])? $arExtraUser[$userId]['IDLE']->getTimestamp(): 0;
			$arUsers[$userId]['mobileLastDate'] = $arExtraUser[$userId]['IS_ONLINE_CUSTOM'] == 'Y' && is_object($arExtraUser[$userId]['MOBILE_LAST_DATE'])? $arExtraUser[$userId]['MOBILE_LAST_DATE']->getTimestamp(): 0;
		}

		$result = array('users' => $arUsers, 'hrphoto' => $arHrPhoto, 'userInGroup' => $arUserInGroup, 'woUserInGroup' => $arWoUserInGroup, 'phones' => $arPhones, 'source' => $arSource);

		if($useCache)
		{
			$cacheTag = array();
			if($obCache->StartDataCache())
			{
				if(defined("BX_COMP_MANAGED_CACHE"))
				{
					global $CACHE_MANAGER;
					$CACHE_MANAGER->StartTagCache($cache_dir);
					$CACHE_MANAGER->RegisterTag("IM_CONTACT_LIST");
					if(is_array($arParams['ID']))
					{
						foreach ($arParams['ID'] as $id)
						{
							$tag = 'USER_NAME_'.intval($id);
							if(!in_array($tag, $cacheTag))
							{
								$cacheTag[] = $tag;
								$CACHE_MANAGER->RegisterTag($tag);
							}
						}
					}
					elseif (isset($arParams['ID']) && intval($arParams['ID']) > 0)
					{
						$tag = 'USER_NAME_'.intval($arParams['ID']);
						$CACHE_MANAGER->RegisterTag($tag);
					}
					$CACHE_MANAGER->EndTagCache();
				}
				$obCache->EndDataCache($result);
				unset($cacheTag);
			}
		}
		unset($result['source']);

		return $result;
	}

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
			if (isset($_SESSION['USER_LAST_ONLINE_'.$userId]) && intval($_SESSION['USER_LAST_ONLINE_'.$userId])+60 > time())
				return false;

			$_SESSION['USER_LAST_ONLINE_'.$userId] = time();
		}

		CUser::SetLastActivityDate($userId);

		return true;
	}

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
		{
			unset($_SESSION['IM_LAST_ONLINE']);
			unset($_SESSION['USER_LAST_ONLINE_'.$userId]);
		}

		$USER->Logout();

		return true;
	}

	public static function SetCurrentTab($userId)
	{
		return CIMMessenger::SetCurrentTab($userId);
	}

	public static function DeleteRecent($entityId, $isChat = false, $userId = false)
	{
		if (is_array($entityId))
		{
			foreach ($entityId as $key => $value)
				$entityId[$key] = intval($value);

			$entityId = array_slice($entityId, 0, 1000);

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

		if ($isChat)
		{
			$itemType = "ITEM_TYPE != '".IM_MESSAGE_PRIVATE."'";
		}
		else
		{
			$itemType = "ITEM_TYPE = '".IM_MESSAGE_PRIVATE."'";
		}

		$strSQL = "DELETE FROM b_im_recent WHERE USER_ID = ".$userId." AND ".$itemType." AND ".$sqlEntityId;
		$DB->Query($strSQL, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		$obCache = new CPHPCache();
		$obCache->CleanDir('/bx/imc/recent'.CIMMessenger::GetCachePath($userId));

		return true;
	}

	public static function GetRecentList($arParams = Array())
	{
		global $DB, $USER;

		$bLoadUnreadMessage = isset($arParams['LOAD_UNREAD_MESSAGE']) && $arParams['LOAD_UNREAD_MESSAGE'] == 'Y'? true: false;
		$bTimeZone = isset($arParams['USE_TIME_ZONE']) && $arParams['USE_TIME_ZONE'] == 'N'? false: true;
		$bSmiles = isset($arParams['USE_SMILES']) && $arParams['USE_SMILES'] == 'N'? false: true;
		$userId = isset($arParams['USER_ID'])? $arParams['USER_ID']: $USER->GetId();

		$nameTemplate = self::GetUserNameTemplate(SITE_ID);
		$nameTemplateSite = CSite::GetNameFormat(false);
		$bIntranetEnable = IsModuleInstalled('intranet') && CModule::IncludeModule('intranet')? true: false;

		$arRecent = Array();
		$arUsers = Array();

		$bColorEnabled = IM\Color::isEnabled();

		$cache_ttl = 2592000;
		$cache_id = 'im_recent_v8_'.$userId.'_'.$bColorEnabled;
		$cache_dir = '/bx/imc/recent'.CIMMessenger::GetCachePath($userId);
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
					R.ITEM_MID M_ID, M.AUTHOR_ID M_AUTHOR_ID, M.ID M_ID, M.CHAT_ID M_CHAT_ID, M.MESSAGE M_MESSAGE, ".$DB->DatetimeToTimestampFunction('M.DATE_CREATE')." M_DATE_CREATE,
					C.TITLE C_TITLE, C.AUTHOR_ID C_OWNER_ID, C.ENTITY_TYPE C_ENTITY_TYPE, C.AVATAR C_AVATAR, C.CALL_NUMBER C_CALL_NUMBER, C.EXTRANET CHAT_EXTRANET, C.COLOR CHAT_COLOR, C.TYPE CHAT_TYPE,
					U.LOGIN, U.NAME, U.LAST_NAME, U.PERSONAL_PHOTO, U.SECOND_NAME, U.PERSONAL_BIRTHDAY, U.PERSONAL_GENDER, U.EXTERNAL_AUTH_ID, U.WORK_POSITION,
					C1.USER_ID RID
				FROM
				b_im_recent R
				LEFT JOIN b_user U ON R.ITEM_TYPE = '".IM_MESSAGE_PRIVATE."' AND R.ITEM_ID = U.ID
				LEFT JOIN b_im_chat C ON R.ITEM_TYPE != '".IM_MESSAGE_PRIVATE."' AND R.ITEM_ID = C.ID
				LEFT JOIN b_im_message M ON R.ITEM_MID = M.ID
				LEFT JOIN b_im_relation C1 ON C1.CHAT_ID = C.ID AND C1.USER_ID = ".$userId."
				WHERE R.USER_ID = ".$userId;
			if (!$bTimeZone)
				CTimeZone::Enable();

			$toDelete = Array();
			$arMessageId = Array();
			$CCTP = new CTextParser();
			$CCTP->MaxStringLen = 255;
			$CCTP->allow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "LIST" => "N", "SMILES" => ($bSmiles? "Y": "N"), "NL2BR" => "Y", "VIDEO" => "N", "TABLE" => "N", "CUT_ANCHOR" => "N", "ALIGN" => "N");
			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->GetNext(true, false))
			{
				$arRes['ITEM_TYPE'] = trim($arRes['ITEM_TYPE']);
				if ($arRes['ITEM_TYPE'] == IM_MESSAGE_OPEN)
				{
					if (intval($arRes['RID']) <= 0 && IM\User::getInstance($userId)->isExtranet())
					{
						continue;
					}
				}
				else if ($arRes['ITEM_TYPE'] == IM_MESSAGE_CHAT)
				{
					if (intval($arRes['RID']) <= 0)
					{
						continue;
					}
				}

				$arMessageId[] = $arRes['M_ID'];

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
						'chatId' => $arRes['M_CHAT_ID'],
						'senderId' => $arRes['M_AUTHOR_ID'],
						'date' => $arRes['M_DATE_CREATE'],
						'text' => $CCTP->convertText(preg_replace("/\[s\].*?\[\/s\]/i", "", $arRes['M_MESSAGE']))
					)
				);
				$item['MESSAGE']['text'] = preg_replace('#\-{54}.+?\-{54}#s', " [".GetMessage('IM_QUOTE')."] ", strip_tags(str_replace(array("<br>","<br/>","<br />", "#BR#"), Array(" "," ", " ", " "), $item['MESSAGE']['text']), "<img>"));

				if ($arRes['ITEM_TYPE'] == IM_MESSAGE_PRIVATE)
				{
					$arUsers[] = $arRes['ITEM_ID'];

					$arFileTmp = CFile::ResizeImageGet(
						$arRes["PERSONAL_PHOTO"],
						array('width' => 58, 'height' => 58),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);

					$item['USER'] = Array(
						'id' => $arRes['ITEM_ID'],
						'name' => CUser::FormatName($nameTemplateSite, $arRes, true, false),
						'nameList' => CUser::FormatName($nameTemplate, $arRes, true, false),
						'workPosition' => $arRes['WORK_POSITION'],
						'color' => self::GetUserColor($arRes["ID"], $arRes['PERSONAL_GENDER'] == 'M'? 'M': 'F'),
						'avatar' => empty($arFileTmp['src'])? '/bitrix/js/im/images/blank.gif': $arFileTmp['src'],
						'status' => 'offline',
						'birthday' => $arRes['PERSONAL_BIRTHDAY'],
						'gender' => $arRes['PERSONAL_GENDER'] == 'F'? 'F': 'M',
						'extranet' => false,
						'network' => $arRes['EXTERNAL_AUTH_ID'] == self::NETWORK_AUTH_ID,
						'phoneDevice' => false,
						'profile' => CIMContactList::GetUserPath($arRes["ITEM_ID"])
					);
				}
				else
				{

					$chatType = $arRes["ITEM_TYPE"] == IM_MESSAGE_OPEN? 'open': 'chat';
					if ($arRes["C_ENTITY_TYPE"] == 'CALL')
						$chatType = 'call';

					$itemId = 'chat'.$itemId;
					$item['CHAT'] = Array(
						'id' => $arRes['ITEM_ID'],
						'name' => $arRes["C_TITLE"],
						'color' => $arRes["CHAT_COLOR"] == ""? IM\Color::getColorByNumber($arRes['ITEM_ID']): IM\Color::getColor($arRes['CHAT_COLOR']),
						'avatar' => CIMChat::GetAvatarImage($arRes["C_AVATAR"]),
						'extranet' => $arRes["CHAT_EXTRANET"] == ""? "": ($arRes["CHAT_EXTRANET"] == "Y"? true: false),
						'owner' => $arRes["C_OWNER_ID"],
						'type' => $chatType,
						'messageType' => $arRes['CHAT_TYPE'],
						'call_number' => $arRes["C_CALL_NUMBER"]
					);
				}
				$arRecent[$itemId] = $item;
			}
			$params = CIMMessageParam::Get($arMessageId);
			foreach ($arRecent as $key => $value)
			{
				if (isset($params[$value['MESSAGE']['id']]))
				{
					if (count($params[$value['MESSAGE']['id']]['FILE_ID']) > 0 && strlen(trim($arRecent[$key]['MESSAGE']['text'])) <= 0)
					{
						$arRecent[$key]['MESSAGE']['text'] = "[".GetMessage('IM_FILE')."]";
					}
					$arRecent[$key]['MESSAGE']['params'] = $params[$value['MESSAGE']['id']];
				}
			}

			if (!empty($toDelete))
			{
				if (isset($toDelete[IM_MESSAGE_PRIVATE]))
					self::DeleteRecent($toDelete[IM_MESSAGE_PRIVATE]);
				if (isset($toDelete[IM_MESSAGE_CHAT]))
					self::DeleteRecent($toDelete[IM_MESSAGE_CHAT], true);
				if (isset($toDelete[IM_MESSAGE_OPEN]))
					self::DeleteRecent($toDelete[IM_MESSAGE_OPEN], true);
			}

			$bExtranetEnable = IsModuleInstalled('extranet');
			$bVoximplantEnable = IsModuleInstalled('voximplant');
			if ($bExtranetEnable || $bVoximplantEnable)
			{
				$arUserPhone = Array();
				$arUserDepartment = Array();

				$arSelectParams = Array();
				if ($bExtranetEnable)
					$arSelectParams[] = 'UF_DEPARTMENT';
				if ($bVoximplantEnable)
					$arSelectParams[] = 'UF_VI_PHONE';

				$dbUsers = CUser::GetList(($sort_by = Array('last_name'=>'asc')), ($dummy=''), Array('ID' => $userId."|".implode('|', $arUsers)), Array('FIELDS' => Array("ID"), 'SELECT' => $arSelectParams));
				while ($arUser = $dbUsers->GetNext(true, false))
				{
					$arUserPhone[$arUser['ID']] = $arUser['UF_VI_PHONE'] == 'Y';
					$arUserDepartment[$arUser['ID']] = self::IsExtranet($arUser);
				}

				foreach ($arRecent as $key => $value)
				{
					if (isset($value['USER']))
					{
						$arRecent[$key]['USER']['extranet'] = $arUserDepartment[$value['USER']['id']];
						$arRecent[$key]['USER']['phoneDevice'] = $arUserPhone[$value['USER']['id']];
					}
				}
			}

			if($obCache->StartDataCache())
				$obCache->EndDataCache(Array('recent' => $arRecent, 'users' => $arUsers));
		}

		$arOnline = CIMStatus::GetList(Array('ID' => array_values($arUsers), 'GET_OFFLINE' => 'Y'));
		foreach ($arRecent as $key => $value)
		{
			if ($value['TYPE'] != IM_MESSAGE_PRIVATE)
				continue;

			$arRecent[$key]['USER']['birthday'] = $bIntranetEnable? CIntranetUtils::IsToday($value['USER']['birthday']): false;
			$arRecent[$key]['USER']['status'] = isset($arOnline['users'][$value['USER']['id']])? $arOnline['users'][$value['USER']['id']]['status']: 'offline';
			$arRecent[$key]['USER']['idle'] = isset($arOnline['users'][$value['USER']['id']])? $arOnline['users'][$value['USER']['id']]['idle']: 0;
			$arRecent[$key]['USER']['mobileLastDate'] = isset($arOnline['users'][$value['USER']['id']])? $arOnline['users'][$value['USER']['id']]['mobileLastDate']: 0;
			if ($arOnline['users'][$value['USER']['id']]['color'])
			{
				$arRecent[$key]['USER']['color'] = $arOnline['users'][$value['USER']['id']]['color'];
			}
		}

		if ($bLoadUnreadMessage)
		{
			$CIMMessage = new CIMMessage(false, Array(
				'HIDE_LINK' => 'Y'
			));

			$ar = $CIMMessage->GetUnreadMessage(Array(
				'LOAD_DEPARTMENT' => 'N',
				'ORDER' => 'ASC',
				'GROUP_BY_CHAT' => 'Y',
				'USE_TIME_ZONE' => $bTimeZone? 'Y': 'N',
				'USE_SMILES' => $bSmiles? 'Y': 'N'
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
					'text' => preg_replace('#\-{54}.+?\-{54}#s', " [".GetMessage('IM_QUOTE')."] ", strip_tags(str_replace(array("<br>","<br/>","<br />", "#BR#"), Array(" ", " ", " ", " "), $data['text']), "<img>"))
				);

				$arRecent[$data['senderId']]['COUNTER'] = $data['counter'];
			}

			$CIMChat = new CIMChat(false, Array(
				'HIDE_LINK' => 'Y'
			));

			$ar = $CIMChat->GetUnreadMessage(Array(
				'ORDER' => 'ASC',
				'GROUP_BY_CHAT' => 'Y',
				'USER_LOAD' => 'N',
				'FILE_LOAD' => 'N',
				'USE_SMILES' => $bSmiles? 'Y': 'N',
				'USE_TIME_ZONE' => $bTimeZone? 'Y': 'N'
			));
			foreach ($ar['message'] as $data)
			{
				if (!isset($arRecent['chat'.$data['recipientId']]))
				{
					$arRecent['chat'.$data['recipientId']] = Array(
						'TYPE' => $ar['messageType']? $ar['messageType']: IM_MESSAGE_CHAT,
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
					'COUNTER' => array(__CLASS__, 'GetRecentListSortCounter'),
					'MESSAGE' => array(__CLASS__, 'GetRecentListSortMessage'),
				),
				null, true
			);
		}

		return $arRecent;
	}

	public static function GetRecentListSortCounter($counter)
	{
		return !is_null($counter);
	}

	public static function GetRecentListSortMessage($recent)
	{
		return $recent['date'];
	}

	public static function IsExtranet($arUser)
	{
		$result = false;
		if (IsModuleInstalled('extranet'))
		{
			if (array_key_exists('UF_DEPARTMENT', $arUser))
			{
				if ($arUser['UF_DEPARTMENT'] == "")
				{
					$result = true;
				}
				else if (is_array($arUser['UF_DEPARTMENT']) && empty($arUser['UF_DEPARTMENT']))
				{
					$result = true;
				}
				else if (is_array($arUser['UF_DEPARTMENT']) && count($arUser['UF_DEPARTMENT']) == 1 && $arUser['UF_DEPARTMENT'][0] == 0)
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	public static function GetUserPath($userId = false)
	{
		static $extranetSiteID = false;

		$userId = intval($userId);

		if (
			$extranetSiteID === false
			&& CModule::IncludeModule("extranet")
		)
		{
			$extranetSiteID = CExtranet::GetExtranetSiteID();
		}

		if (IsModuleInstalled('intranet'))
		{
			$strPathTemplate = COption::GetOptionString(
				"socialnetwork",
				"user_page",
				SITE_DIR.'company/personal/',
				(CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser() ? $extranetSiteID : SITE_ID)
			)."user/#user_id#/";
		}
		else
		{
			$strPathTemplate = COption::GetOptionString(
				"im",
				"path_to_user_profile",
				"/club/user/#user_id#/",
				SITE_ID
			);
		}

		if ($userId <= 0)
		{
			return $strPathTemplate;
		}
		else
		{
			return CComponentEngine::MakePathFromTemplate(
				$strPathTemplate,
				array("user_id" => $userId)
			);
		}
	}

	public static function GetUserNameTemplate($siteId = false, $langId = false, $getDefault = false)
	{
		if (!$langId && defined('LANGUAGE_ID'))
		{
			$langId = LANGUAGE_ID;
		}


		if (in_array($langId, Array('ru', 'kz', 'by', 'ua')))
		{
			$template = "#LAST_NAME# #NAME#";
		}
		else
		{
			$template = "#LAST_NAME#, #NAME#";
		}

		return $getDefault? $template: COption::GetOptionString("im", "user_name_template", $template, $siteId);
	}

	public static function GetUserColor($id, $gender)
	{
		$code = IM\Color::getCodeByNumber($id);
		if ($gender == 'M')
		{
			$replaceColor = IM\Color::getReplaceColors();
			if (isset($replaceColor[$code]))
			{
				$code = $replaceColor[$code];
			}
		}

		return IM\Color::getColor($code);
	}

	public static function PrepareUserId($id, $searchMark = '')
	{
		$result = self::PrepareUserIds(Array($id), $searchMark);

		return $result[$id];
	}

	public static function PrepareUserIds($userIds, $searchMark = '')
	{
		$portalId = Array();
		$networkId = Array();
		foreach ($userIds as $userId)
		{
			if (substr($userId, 0, 7) == 'network')
			{
				$networkId[$userId] = substr($userId, 7);
			}
			else
			{
				$userId = intval($userId);
				if ($userId > 0)
				{
					$portalId[$userId] = $userId;
				}
			}
		}
		if (!empty($networkId) && CModule::IncludeModule('socialservices'))
		{
			$network = new \Bitrix\Socialservices\Network();
			$networkEnabled = $network->isEnabled();
			if ($networkEnabled)
			{
				$users = $network->addUsersById($networkId, $searchMark);
				if ($users)
				{
					foreach ($users as $networkId => $userId)
					{
						$portalId['network'.$networkId] = $userId;
					}
				}
			}
		}

		return $portalId;
	}
}
?>