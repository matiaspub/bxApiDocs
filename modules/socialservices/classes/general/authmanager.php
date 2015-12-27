<?php
use Bitrix\Main\Context;

IncludeModuleLangFile(__FILE__);

require_once(dirname(__FILE__)."/descriptions.php");

//manager to operate with services
class CSocServAuthManager
{
	/** @var array  */
	protected static $arAuthServices = false;

	static public function __construct()
	{
		if(!is_array(self::$arAuthServices))
		{
			self::$arAuthServices = array();

			$db_events = GetModuleEvents("socialservices", "OnAuthServicesBuildList");
			while($arEvent = $db_events->Fetch())
			{
				$res = ExecuteModuleEventEx($arEvent);
				if(is_array($res))
				{
					if(!is_array($res[0]))
						$res = array($res);
					foreach($res as $serv)
						self::$arAuthServices[$serv["ID"]] = $serv;
				}
			}
			//services depend on current site
			$suffix = CSocServAuth::OptionsSuffix();
			self::$arAuthServices = self::AppyUserSettings($suffix);
		}
	}

	protected static function AppyUserSettings($suffix)
	{
		$arAuthServices = self::$arAuthServices;

		//user settings: sorting, active
		$arServices = unserialize(COption::GetOptionString("socialservices", "auth_services".$suffix, ""));
		if(is_array($arServices))
		{
			$i = 0;
			foreach($arServices as $serv=>$active)
			{
				if(isset($arAuthServices[$serv]))
				{
					$arAuthServices[$serv]["__sort"] = $i++;
					$arAuthServices[$serv]["__active"] = ($active == "Y");
				}
			}
			\Bitrix\Main\Type\Collection::sortByColumn($arAuthServices, "__sort");
		}
		return $arAuthServices;
	}

	static public function GetAuthServices($suffix)
	{
		//$suffix indicates site specific or common options
		return self::AppyUserSettings($suffix);
	}

	static public function GetActiveAuthServices($arParams)
	{
		$aServ = array();
		self::SetUniqueKey();

		foreach(self::$arAuthServices as $key=>$service)
		{
			if($service["__active"] === true && $service["DISABLED"] !== true)
			{
				$cl = new $service["CLASS"];
				if(is_callable(array($cl, "CheckSettings")))
					if(!call_user_func_array(array($cl, "CheckSettings"), array()))
						continue;

				if(is_callable(array($cl, "GetFormHtml")))
					$service["FORM_HTML"] = call_user_func_array(array($cl, "GetFormHtml"), array($arParams));

				if(is_callable(array($cl, "GetOnClickJs")))
					$service["ONCLICK"] = call_user_func_array(array($cl, "GetOnClickJs"), array($arParams));

				$aServ[$key] = $service;
			}
		}
		return $aServ;
	}

	static public function GetProfileUrl($service, $uid, $arService = false)
	{
		global $USER;

		if(isset(self::$arAuthServices[$service]))
		{
			if(!is_array($arService))
			{
				$dbSocservUser = \CSocServAuthDB::getList(
					array(),
					array(
						'USER_ID' => $USER->GetID(),
						'EXTERNAL_AUTH_ID' => $service,
					)
				);
				$arService = $dbSocservUser->fetch();
			}

			if(
				is_array($arService)
				&& self::$arAuthServices[$service]["__active"] === true
				&& self::$arAuthServices[$service]["DISABLED"] !== true
			)
			{
				/** @var \CSocServFacebook $cl */
				$cl = new self::$arAuthServices[$service]["CLASS"];
				if(is_callable(array($cl, "getProfileUrl")))
				{
					return $cl->getProfileUrl($uid);
				}
			}
		}

		return false;
	}

	static public function GetFriendsList($service, $limit, &$next)
	{
		global $USER;

		if(isset(self::$arAuthServices[$service]))
		{
			$dbSocservUser = \CSocServAuthDB::getList(
				array(),
				array(
					'USER_ID' => $USER->GetID(),
					'EXTERNAL_AUTH_ID' => $service,
				)
			);
			$arService = $dbSocservUser->fetch();

			if(
				is_array($arService)
				&& self::$arAuthServices[$service]["__active"] === true
				&& self::$arAuthServices[$service]["DISABLED"] !== true
			)
			{
				/** @var \CSocServFacebook $cl */
				$cl = new self::$arAuthServices[$service]["CLASS"];
				if(is_callable(array($cl, "getFriendsList")))
				{
					return $cl->getFriendsList($limit, $next);
				}
			}
		}

		return false;
	}

	static public function GetSettings()
	{
		$arOptions = array();
		foreach(self::$arAuthServices as $service)
		{
			if(is_callable(array($service["CLASS"], "GetSettings")))
			{
				$arOptions[] = htmlspecialcharsbx($service["NAME"]);
				$options = call_user_func_array(array($service["CLASS"], "GetSettings"), array());
				if(is_array($options))
					foreach($options as $opt)
						$arOptions[] = $opt;
			}
		}

		return $arOptions;
	}

	static public function Authorize($service_id, $arParams = array())
	{
		if($service_id === 'Bitrix24OAuth')
		{
			CSocServBitrixOAuth::gadgetAuthorize();
		}

		if(isset(self::$arAuthServices[$service_id]))
		{
			$service = self::$arAuthServices[$service_id];
			if($service["__active"] === true && $service["DISABLED"] !== true)
			{
				$cl = new $service["CLASS"];
				if(is_callable(array($cl, "Authorize")))
				{
					return call_user_func_array(array($cl, "Authorize"), array
						($arParams));
				}
			}
		}

		return false;
	}

	static public function GetError($service_id, $error_code)
	{
		if(isset(self::$arAuthServices[$service_id]))
		{
			$service = self::$arAuthServices[$service_id];
			if(is_callable(array($service["CLASS"], "GetError")))
				return call_user_func_array(array($service["CLASS"], "GetError"), array($error_code));
			$error = ($error_code == 2) ? "socserv_error_new_user" : "socserv_controller_error";
			return GetMessage($error, array("#SERVICE_NAME#"=>$service["NAME"]));
		}
		return '';
	}

	public static function GetUniqueKey()
	{
		if(!isset($_SESSION["UNIQUE_KEY"]))
		{
			self::SetUniqueKey();
		}

		return $_SESSION["UNIQUE_KEY"];
	}

	public static function SetUniqueKey()
	{
		if(!isset($_SESSION["UNIQUE_KEY"]))
			$_SESSION["UNIQUE_KEY"] = md5(bitrix_sessid_get().uniqid(rand(), true));
	}

	public static function CheckUniqueKey($bUnset = true)
	{
		$arState = array();

		if(isset($_REQUEST["state"]))
		{
			parse_str($_REQUEST["state"], $arState);

			if(isset($arState['backurl']))
			{
				InitURLParam($arState['backurl']);
			}
		}

		if(!isset($_REQUEST['check_key']) && isset($_REQUEST['backurl']))
		{
			InitURLParam($_REQUEST['backurl']);
		}

		$checkKey = '';
		if(isset($_REQUEST['check_key']))
		{
			$checkKey = $_REQUEST['check_key'];
		}
		elseif(isset($arState['check_key']))
		{
			$checkKey = $arState['check_key'];
		}

		if($_SESSION["UNIQUE_KEY"] != '' && $checkKey != '' && ($checkKey === $_SESSION["UNIQUE_KEY"]))
		{
			if($bUnset)
			{
				unset($_SESSION["UNIQUE_KEY"]);
			}

			return true;
		}
		return false;
	}

	public static function CleanParam()
	{
		global $APPLICATION;

		$redirect_url = $APPLICATION->GetCurPageParam('', array("auth_service_id", "check_key"), false);
		LocalRedirect($redirect_url);
	}

	public static function GetUserArrayForSendMessages($userId)
	{
		$arUserOauth = array();
		$userId = intval($userId);
		if($userId > 0)
		{
			$dbSocservUser = CSocServAuthDB::GetList(array(), array('USER_ID' => $userId), false, false, array("ID", "EXTERNAL_AUTH_ID", "OATOKEN"));
			while($arOauth = $dbSocservUser->Fetch())
			{
				if($arOauth["OATOKEN"] <> '' && ($arOauth["EXTERNAL_AUTH_ID"] == "Twitter" || $arOauth["EXTERNAL_AUTH_ID"] == "Facebook"))
					$arUserOauth[$arOauth["ID"]] = $arOauth["EXTERNAL_AUTH_ID"];
			}
		}
		if(!empty($arUserOauth))
			return $arUserOauth;
		return false;
	}

	public static function SendUserMessage($socServUserId, $providerName, $message, $messageId)
	{
		$result = false;
		$socServUserId = intval($socServUserId);
		if($providerName != '' && $socServUserId > 0)
		{
			switch($providerName)
			{
				case 'Twitter':
					$className = "CSocServTwitter";
					break;
				case 'Facebook':
					$className = "CSocServFacebook";
					break;
				case 'Odnoklassniki':
					$className = "CSocServOdnoklassniki";
					break;
				default:
					$className = "";
			}
			if($className != "")
				$result = call_user_func($className.'::SendUserFeed', $socServUserId, $message, $messageId);
		}
		return $result;
	}

	/**
	 * Publishes messages from Twitter in Buzz corporate portal.
	 * @static
	 * @param $arUserTwit
	 * @param $lastTwitId
	 * @param $arSiteId
	 * @return int|null
	 */
	public static function PostIntoBuzz($arUserTwit, $lastTwitId, $arSiteId=array())
	{
		if(isset($arUserTwit['statuses']) && !empty($arUserTwit['statuses']))
		{
			foreach($arUserTwit['statuses'] as $userTwit)
			{
				if(isset($userTwit["id_str"]))
					$lastTwitId = ($userTwit["id_str"].'/' > $lastTwitId.'/') ? $userTwit["id_str"] : $lastTwitId;
				if(IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME'))
				{
					$userId = $userTwit['kp_user_id'];
					$rsUser = CUser::GetByID($userId);
					$arUser = $rsUser->Fetch();
					foreach(GetModuleEvents("socialservices", "OnPublishSocServMessage", true) as $arEvent)
						ExecuteModuleEventEx($arEvent, array($arUser, $userTwit, $arSiteId));
				}
				else
					self::PostIntoBuzzAsBlog($userTwit, $lastTwitId, $arSiteId);
			}
			return $lastTwitId;
		}
		return null;
	}

	public static function PostIntoBuzzAsBlog($userTwit, $arSiteId=array(), $userLogin = '')
	{
		global $DB;
		if(!CModule::IncludeModule("blog") || !CModule::IncludeModule("socialnetwork"))
			return;
		$arParams = array();
		if((IsModuleInstalled('bitrix24') && defined('BX24_HOST_NAME')) && $userLogin != '')
		{
			if($arUserTwit = unserialize(base64_decode($userTwit)))
				$userTwit = $arUserTwit;
			if($arSiteIdCheck = unserialize(base64_decode($arSiteId)))
				$arSiteId = $arSiteIdCheck;
			$dbUser = CUser::GetByLogin($userLogin);
			if($arUser = $dbUser->Fetch())
				$arParams["USER_ID"] = $arUser["ID"];
		}
		else
			$arParams["USER_ID"] = $userTwit['kp_user_id'];
		$siteId = null;
		if(isset($arSiteId[$userTwit['kp_user_id']]))
			$siteId = $arSiteId[$userTwit['kp_user_id']];
		if(strlen($siteId) <= 0)
			$siteId = SITE_ID;
		if(isset($userTwit['text']))
		{
			$arParams["GROUP_ID"] = COption::GetOptionString("socialnetwork", "userbloggroup_id", false, $siteId);
			$arParams["PATH_TO_BLOG"] = COption::GetOptionString("socialnetwork", "userblogpost_page", false, $siteId);
			$arParams["PATH_TO_SMILE"] = COption::GetOptionString("socialnetwork", "smile_page", false, $siteId);
			$arParams["NAME_TEMPLATE"] = COption::GetOptionString("main", "TOOLTIP_NAME_TEMPLATE", false, $siteId);
			$arParams["SHOW_LOGIN"] = 'Y';
			$arParams["PATH_TO_POST"] = $arParams["PATH_TO_BLOG"];

			$arFilterblg = Array(
				"ACTIVE" => "Y",
				"USE_SOCNET" => "Y",
				"GROUP_ID" => $arParams["GROUP_ID"],
				"GROUP_SITE_ID" => $siteId,
				"OWNER_ID" => $arParams["USER_ID"],
			);
			$groupId = (is_array($arParams["GROUP_ID"]) ? IntVal($arParams["GROUP_ID"][0]) : IntVal($arParams["GROUP_ID"]));
			if (isset($GLOBALS["BLOG_POST"]["BLOG_P_".$groupId."_".$arParams["USER_ID"]]) && !empty($GLOBALS["BLOG_POST"]["BLOG_P_".$groupId."_".$arParams["USER_ID"]]))
			{
				$arBlog = $GLOBALS["BLOG_POST"]["BLOG_P_".$groupId."_".$arParams["USER_ID"]];
			}
			else
			{
				$dbBl = CBlog::GetList(Array(), $arFilterblg);
				$arBlog = $dbBl ->Fetch();
				if (!$arBlog && IsModuleInstalled("intranet"))
					$arBlog = CBlog::GetByOwnerID($arParams["USER_ID"]);

				$GLOBALS["BLOG_POST"]["BLOG_P_".$groupId."_".$arParams["USER_ID"]] = $arBlog;
			}

			$arResult["Blog"] = $arBlog;

			if(empty($arBlog))
			{
				if(!empty($arParams["GROUP_ID"]))
				{
					$arFields = array(
						"=DATE_UPDATE" => $DB->CurrentTimeFunction(),
						"GROUP_ID" => (is_array($arParams["GROUP_ID"])) ? IntVal($arParams["GROUP_ID"][0]) : IntVal($arParams["GROUP_ID"]),
						"ACTIVE" => "Y",
						"ENABLE_COMMENTS" => "Y",
						"ENABLE_IMG_VERIF" => "Y",
						"EMAIL_NOTIFY" => "Y",
						"ENABLE_RSS" => "Y",
						"ALLOW_HTML" => "N",
						"ENABLE_TRACKBACK" => "N",
						"SEARCH_INDEX" => "Y",
						"USE_SOCNET" => "Y",
						"=DATE_CREATE" => $DB->CurrentTimeFunction(),
						"PERMS_POST" => Array(
							1 => "I",
							2 => "I" ),
						"PERMS_COMMENT" => Array(
							1 => "P",
							2 => "P" ),
					);

					$bRights = false;
					$rsUser = CUser::GetByID($arParams["USER_ID"]);
					$arUser = $rsUser->Fetch();
					if(strlen($arUser["NAME"]."".$arUser["LAST_NAME"]) <= 0)
						$arFields["NAME"] = GetMessage("BLG_NAME")." ".$arUser["LOGIN"];
					else
						$arFields["NAME"] = GetMessage("BLG_NAME")." ".$arUser["NAME"]." ".$arUser["LAST_NAME"];

					$arFields["URL"] = str_replace(" ", "_", $arUser["LOGIN"])."-blog-".SITE_ID;
					$arFields["OWNER_ID"] = $arParams["USER_ID"];

					$urlCheck = preg_replace("/[^a-zA-Z0-9_-]/is", "", $arFields["URL"]);
					if ($urlCheck != $arFields["URL"])
					{
						$arFields["URL"] = "u".$arParams["USER_ID"]."-blog-".SITE_ID;
					}
					if(CBlog::GetByUrl($arFields["URL"]))
					{
						$uind = 0;
						do
						{
							$uind++;
							$arFields["URL"] = $arFields["URL"].$uind;
						}
						while (CBlog::GetByUrl($arFields["URL"]));
					}

					$featureOperationPerms = CSocNetFeaturesPerms::GetOperationPerm(SONET_ENTITY_USER, $arFields["OWNER_ID"], "blog", "view_post");
					if ($featureOperationPerms == SONET_RELATIONS_TYPE_ALL)
						$bRights = true;

					$arFields["PATH"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arFields["URL"], "user_id" => $arFields["OWNER_ID"], "group_id" => $arFields["SOCNET_GROUP_ID"]));

					$blogID = CBlog::Add($arFields);
					if($bRights)
						CBlog::AddSocnetRead($blogID);
					$arBlog = CBlog::GetByID($blogID, $arParams["GROUP_ID"]);
				}
			}

			//	$DATE_PUBLISH = "";
			//	if(strlen($_POST["DATE_PUBLISH_DEF"]) > 0)
			//		$DATE_PUBLISH = $_POST["DATE_PUBLISH_DEF"];
			//	elseif (strlen($_POST["DATE_PUBLISH"])<=0)

			$DATE_PUBLISH = ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL");

			//	else
			//		$DATE_PUBLISH = $_POST["DATE_PUBLISH"];

			$arFields=array(
				"DETAIL_TEXT"       => $userTwit['text'],
				"DETAIL_TEXT_TYPE"	=> "text",
				"DATE_PUBLISH"		=> $DATE_PUBLISH,
				"PUBLISH_STATUS"	=> BLOG_PUBLISH_STATUS_PUBLISH,
				"PATH" 				=> CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => "#post_id#", "user_id" => $arBlog["OWNER_ID"])),
				"URL" 				=> $arBlog["URL"],
				"SOURCE_TYPE"       => "twitter",
			);

			$arFields["PERMS_POST"] = array();
			$arFields["PERMS_COMMENT"] = array();
			$arFields["MICRO"] = "N";
			if(strlen($arFields["TITLE"]) <= 0)
			{
				$arFields["MICRO"] = "Y";
				$arFields["TITLE"] = trim(blogTextParser::killAllTags($arFields["DETAIL_TEXT"]));
				if(strlen($arFields["TITLE"]) <= 0)
					$arFields["TITLE"] = GetMessage("BLOG_EMPTY_TITLE_PLACEHOLDER");
			}

			$arFields["SOCNET_RIGHTS"] = Array();
			if(!empty($userTwit['user_perms']))
			{
				$bOne = true;
				foreach($userTwit['user_perms'] as $v => $k)
				{
					if(strlen($v) > 0 && is_array($k) && !empty($k))
					{
						foreach($k as $vv)
						{
							if(strlen($vv) > 0)
							{
								$arFields["SOCNET_RIGHTS"][] = $vv;
								if($v != "SG")
									$bOne = false;

							}
						}
					}
				}

				if($bOne && !empty($userTwit['user_perms']["SG"]))
				{
					$bOnesg = false;
					$bFirst = true;
					$oGrId = 0;
					foreach($userTwit['user_perms']["SG"] as $v)
					{
						if(strlen($v) > 0)
						{
							if($bFirst)
							{
								$bOnesg = true;
								$bFirst = false;
								$v = str_replace("SG", "", $v);
								$oGrId = IntVal($v);
							}
							else
							{
								$bOnesg = false;
							}
						}
					}
					if($bOnesg)
					{
						if (!CSocNetFeaturesPerms::CanPerformOperation($arParams["USER_ID"], SONET_ENTITY_GROUP, $oGrId, "blog", "write_post") && !CSocNetFeaturesPerms::CanPerformOperation($arParams["USER_ID"], SONET_ENTITY_GROUP, $oGrId, "blog", "moderate_post") && !CSocNetFeaturesPerms::CanPerformOperation($arParams["USER_ID"], SONET_ENTITY_GROUP, $oGrId, "blog", "full_post"))
							$arFields["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;
					}
				}
			}
			$bError = false;
			/*	if (CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser())
				{
					if(empty($arFields["SOCNET_RIGHTS"]) || in_array("UA", $arFields["SOCNET_RIGHTS"]))
					{
						$bError = true;
						$arResult["ERROR_MESSAGE"] = GetMessage("BLOG_BPE_EXTRANET_ERROR");
					}
				}*/

			$newID = null;
			$socnetRightsOld = Array("U" => Array());
			if(!$bError)
			{
				preg_match_all("/\\[user\\s*=\\s*([^\\]]*)\\](.+?)\\[\\/user\\]/ies".BX_UTF_PCRE_MODIFIER, $userTwit['text'], $arMention);

				$arFields["=DATE_CREATE"] = $DB->GetNowFunction();
				$arFields["AUTHOR_ID"] = $arParams["USER_ID"];
				$arFields["BLOG_ID"] = $arBlog["ID"];

				$newID = CBlogPost::Add($arFields);

				if($newID)
				{
					$arFields["ID"] = $newID;
					$arParamsNotify = Array(
						"bSoNet" => true,
						"UserID" => $arParams["USER_ID"],
						"allowVideo" => $arResult["allowVideo"],
						//"bGroupMode" => $arResult["bGroupMode"],
						"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
						"PATH_TO_POST" => $arParams["PATH_TO_POST"],
						"SOCNET_GROUP_ID" => $arParams["GROUP_ID"],
						"user_id" => $arParams["USER_ID"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					);
					CBlogPost::Notify($arFields, $arBlog, $arParamsNotify);
				}
			}
			if ($newID > 0 && strlen($arResult["ERROR_MESSAGE"]) <= 0 && $arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH) // Record saved successfully
			{
				BXClearCache(true, "/".SITE_ID."/blog/last_messages_list/");

				$arFieldsIM = Array(
					"TYPE" => "POST",
					"TITLE" => $arFields["TITLE"],
					"URL" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => $newID, "user_id" => $arBlog["OWNER_ID"])),
					"ID" => $newID,
					"FROM_USER_ID" => $arParams["USER_ID"],
					"TO_USER_ID" => array(),
					"TO_SOCNET_RIGHTS" => $arFields["SOCNET_RIGHTS"],
					"TO_SOCNET_RIGHTS_OLD" => $socnetRightsOld["U"],
				);
				if(!empty($arMentionOld))
					$arFieldsIM["MENTION_ID_OLD"] = $arMentionOld[1];
				if(!empty($arMention))
					$arFieldsIM["MENTION_ID"] = $arMention[1];

				CBlogPost::NotifyIm($arFieldsIM);

				$arParams["ID"] = $newID;
				if(!empty($_POST["SPERM"]["SG"]))
				{
					foreach($_POST["SPERM"]["SG"] as $v)
					{
						$group_id_tmp = substr($v, 2);
						if(IntVal($group_id_tmp) > 0)
							CSocNetGroup::SetLastActivity(IntVal($group_id_tmp));
					}
				}
			}
		}
	}

	public static function GetTwitMessages($lastTwitId = "1", $counter = 1)
	{
		$oAuthManager = new CSocServAuthManager();
		$arActiveSocServ = $oAuthManager->GetActiveAuthServices(array());
		if(!(isset($arActiveSocServ["Twitter"]) && isset($arActiveSocServ["Twitter"]["__active"])) || !function_exists("hash_hmac"))
			return false;
		if(!CModule::IncludeModule("socialnetwork"))
			return "CSocServAuthManager::GetTwitMessages(\"$lastTwitId\", $counter);";
		global $USER;
		$bTmpUserCreated = false;
		if(!isset($USER) || !(($USER instanceof CUser) && ('CUser' == get_class($USER))))
		{
			$bTmpUserCreated = true;
			if(isset($USER))
			{
				$USER_TMP = $USER;
				unset($USER);
			}

			$USER = new CUser();
		}
		if(intval($lastTwitId) <= 1 || $counter == 1)
			$lastTwitId = COption::GetOptionString('socialservices', 'last_twit_id', '1');
		$socServUserArray = self::GetUserArray('Twitter');
		$arSiteId = array();
		if(isset($socServUserArray[3]) && is_array($socServUserArray[3]))
			$arSiteId = $socServUserArray[3];
		$twitManager = new CSocServTwitter();
		$arUserTwit = $twitManager->GetUserMessage($socServUserArray, $lastTwitId);
		if(is_array($arUserTwit))
		{
			if(isset($arUserTwit["statuses"]) && !empty($arUserTwit["statuses"]))
				$lastTwitId = self::PostIntoBuzz($arUserTwit, $lastTwitId, $arSiteId);
			elseif((is_array($arUserTwit["search_metadata"]) && isset($arUserTwit["search_metadata"]["max_id_str"])) &&	(strlen($arUserTwit["search_metadata"]["max_id_str"]) > 0))
				$lastTwitId = $arUserTwit["search_metadata"]["max_id_str"];
		}
		$counter++;
		if($counter >= 20)
		{
			// $oldLastId = COption::GetOptionString('socialservices', 'last_twit_id', '1');
			// if((strlen($lastTwitId) > strlen($oldLastId)) && $oldLastId[0] != 9)
			// 	$lastTwitId = substr($lastTwitId, 1);
			COption::SetOptionString('socialservices', 'last_twit_id', $lastTwitId);
			$counter = 1;
		}
		$lastTwitId = preg_replace("|\D|", '', $lastTwitId);
		if($bTmpUserCreated)
		{
			unset($USER);
			if(isset($USER_TMP))
			{
				$USER = $USER_TMP;
				unset($USER_TMP);
			}
		}
		return "CSocServAuthManager::GetTwitMessages(\"$lastTwitId\", $counter);";
	}

	public static function SendSocialservicesMessages()
	{
		$oAuthManager = new CSocServAuthManager();
		$arActiveSocServ = $oAuthManager->GetActiveAuthServices(array());
		if(!(isset($arActiveSocServ["Twitter"]) && isset($arActiveSocServ["Twitter"]["__active"])) || !function_exists("hash_hmac"))
			return false;

		$ttl = 86400;
		$cache_id = 'socserv_mes_user';
		$obCache = new CPHPCache;
		$cache_dir = '/bx/socserv_mes_user';

		$arSocServMessage = array();
		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
			$arSocServMessage = $obCache->GetVars();
		else
		{
			$dbSocServMessage = CSocServMessage::GetList(array(), array('SUCCES_SENT' => 'N'), false, array("nTopCount" => 5), array("ID", "SOCSERV_USER_ID", "PROVIDER", "MESSAGE"));

			while($arSocMessage = $dbSocServMessage->Fetch())
				$arSocServMessage[] = $arSocMessage;
			if(empty($arSocServMessage))
				if($obCache->StartDataCache())
					$obCache->EndDataCache($arSocServMessage);
		}
		if(is_array($arSocServMessage) && !empty($arSocServMessage))
			foreach($arSocServMessage as $arSocMessage)
			{
				$arResult = CSocServAuthManager::SendUserMessage($arSocMessage['SOCSERV_USER_ID'], $arSocMessage['PROVIDER'], $arSocMessage['MESSAGE'], $arSocMessage['ID']);
				if($arResult !== false && is_array($arResult) && !preg_match("/error/i", join(",", array_keys($arResult))))
					self::MarkMessageAsSent($arSocMessage['ID']);
			}
		return "CSocServAuthManager::SendSocialservicesMessages();";
	}

	private static function MarkMessageAsSent($id)
	{
		CSocServMessage::Update($id, array("SUCCES_SENT" => 'Y'));
	}

	static public function GetUserArray($authId)
	{
		$ttl = 10000;
		$cache_id = 'socserv_ar_user';
		$obCache = new CPHPCache;
		$cache_dir = '/bx/socserv_ar_user';

		if($obCache->InitCache($ttl, $cache_id, $cache_dir))
		{
			$arResult = $obCache->GetVars();
		}
		else
		{
			$arUserXmlId = $arOaToken = $arOaSecret = $arSiteId = array();
			$dbSocUser = CSocServAuthDB::GetList(array(), array('EXTERNAL_AUTH_ID' => $authId, "ACTIVE" => 'Y'), false, false, array("XML_ID", "USER_ID", "OATOKEN", "OASECRET", "SITE_ID"));
			while($arSocUser = $dbSocUser->Fetch())
			{
				$arUserXmlId[$arSocUser["USER_ID"]] = $arSocUser["XML_ID"];
				$arOaToken[$arSocUser["USER_ID"]] = $arSocUser["OATOKEN"];
				$arOaSecret[$arSocUser["USER_ID"]] = $arSocUser["OASECRET"];
				$arSiteId[$arSocUser["USER_ID"]] = $arSocUser["SITE_ID"];
			}
			$arResult = array($arUserXmlId, $arOaToken, $arOaSecret, $arSiteId);
			if($obCache->StartDataCache())
				$obCache->EndDataCache($arResult);
		}
		return $arResult;
	}

	public static function GetCachedUserOption($option)
	{
		global $USER;
		$result = '';
		if(is_object($USER))
		{
			$userId = $USER->GetID();
			$ttl = 10000;
			$cache_id = 'socserv_user_option_'.$userId;
			$obCache = new CPHPCache;
			$cache_dir = '/bx/socserv_user_option';

			if($obCache->InitCache($ttl, $cache_id, $cache_dir))
				$result = $obCache->GetVars();
			else
			{
				$result = CUtil::JSEscape(CUserOptions::GetOption("socialservices", $option, "N", $USER->GetID()));
				if($obCache->StartDataCache())
					$obCache->EndDataCache($result);
			}

		}

		return $result;
	}
}

//base class for auth services
class CSocServAuth
{
	protected static $settingsSuffix = false;

	static public function GetSettings()
	{
		return false;
	}

	protected function CheckFields($action, &$arFields)
	{
		global $USER;

		if($action === 'ADD')
		{
			if(isset($arFields["EXTERNAL_AUTH_ID"]) && strlen($arFields["EXTERNAL_AUTH_ID"])<=0)
			{
				return false;
			}

			if(isset($arFields["SITE_ID"]) && strlen($arFields["SITE_ID"])<=0)
			{
				$arFields["SITE_ID"] = SITE_ID;
			}

			if(!isset($arFields["USER_ID"]))
			{
				$arFields["USER_ID"] = $USER->GetID();
			}

			$dbCheck = CSocServAuthDB::GetList(array(), array("USER_ID" => $arFields["USER_ID"], "EXTERNAL_AUTH_ID" => $arFields["EXTERNAL_AUTH_ID"]), false, false, array("ID"));
			if($dbCheck->Fetch())
			{
				return false;
			}
		}

		if(is_set($arFields, "PERSONAL_PHOTO"))
		{
			$res = CFile::CheckImageFile($arFields["PERSONAL_PHOTO"]);
			if(strlen($res)>0)
			{
				unset($arFields["PERSONAL_PHOTO"]);
			}
			else
			{
				$arFields["PERSONAL_PHOTO"]["MODULE_ID"] = "socialservices";
				CFile::SaveForDB($arFields, "PERSONAL_PHOTO", "socialservices");
			}
		}

		return true;
	}

	static function Update($id, $arFields)
	{
		global $DB;
		$id = intval($id);

		if($id <= 0)
		{
			return false;
		}

		foreach(GetModuleEvents("socialservices", "OnBeforeSocServUserUpdate", true) as $arEvent)
		{
			if(ExecuteModuleEventEx($arEvent, array($id, &$arFields)) === false)
			{
				return false;
			}
		}

		if(!self::CheckFields('UPDATE', $arFields))
		{
			return false;
		}

		$strUpdate = $DB->PrepareUpdate("b_socialservices_user", $arFields);

		$strSql = "UPDATE b_socialservices_user SET ".$strUpdate." WHERE ID = ".$id." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		$cache_id = 'socserv_ar_user';
		$obCache = new CPHPCache;
		$cache_dir = '/bx/socserv_ar_user';
		$obCache->Clean($cache_id, $cache_dir);

		$arFields['ID'] = $id;
		foreach(GetModuleEvents("socialservices", "OnAfterSocServUserUpdate", true) as $arEvent)
			ExecuteModuleEventEx($arEvent, array(&$arFields));

		return $id;
	}

	public static function Delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			$rsUser = $DB->Query("SELECT ID FROM b_socialservices_user WHERE ID=".$id);
			$arUser = $rsUser->Fetch();
			if(!$arUser)
				return false;

			foreach(GetModuleEvents("socialservices", "OnBeforeSocServUserDelete", true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array($id));

			$DB->Query("DELETE FROM b_socialservices_user WHERE ID = ".$id." ", true);
			$cache_id = 'socserv_ar_user';
			$obCache = new CPHPCache;
			$cache_dir = '/bx/socserv_ar_user';
			$obCache->Clean($cache_id, $cache_dir);
			return true;
		}
		return false;
	}

	public static function OnUserDelete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			$DB->Query("DELETE FROM b_socialservices_user WHERE USER_ID = ".$id." ", true);
			return true;
		}
		return false;
	}

	public static function OnAfterTMReportDailyAdd()
	{
		if(COption::GetOptionString("socialservices", "allow_send_user_activity", "Y") != 'Y')
			return;
		global $USER;
		$arIntranetData = $arResult = $arData = array();
		$eventCounter = $taskCounter = 0;
		if(CModule::IncludeModule('intranet'))
		{
			$arIntranetData = CIntranetPlanner::getData(SITE_ID, true);
		}
		if(isset($arIntranetData['DATA']))
		{
			$arData = $arIntranetData['DATA'];
		}
		if(isset($arData['EVENTS']) && is_array($arData['EVENTS']))
		{
			$eventCounter = count($arData['EVENTS']);
		}
		if(isset($arData['TASKS']) && is_array($arData['TASKS']))
		{
			$taskCounter = count($arData['TASKS']);
		}

		$arResult['USER_ID'] = intval($USER->GetID());
		if($arResult['USER_ID'] > 0)
		{
			$enabledSendMessage = CUserOptions::GetOption("socialservices", "user_socserv_enable", "N", $arResult['USER_ID']);
			if($enabledSendMessage == 'Y')
			{
				$enabledEndDaySend = CUserOptions::GetOption("socialservices", "user_socserv_end_day", "N", $arResult['USER_ID']);
				if($enabledEndDaySend == 'Y')
				{
					$arResult['MESSAGE'] = str_replace('#event#', $eventCounter, str_replace('#task#', $taskCounter, CUserOptions::GetOption("socialservices", "user_socserv_end_text", GetMessage("JS_CORE_SS_WORKDAY_START"), $arResult['USER_ID'])));

					$socServArray = CUserOptions::GetOption("socialservices", "user_socserv_array", "a:0:{}", $arResult['USER_ID']);
					if(!CheckSerializedData($socServArray))
					{
						$socServArray = "a:0:{}";
					}

					$arSocServUser['SOCSERVARRAY'] = unserialize($socServArray);

					if(is_array($arSocServUser['SOCSERVARRAY']) && count($arSocServUser['SOCSERVARRAY']) > 0)
					{
						foreach($arSocServUser['SOCSERVARRAY'] as $id => $providerName)
						{
							$arResult['SOCSERV_USER_ID'] = $id;
							$arResult['PROVIDER'] = $providerName;
							CSocServMessage::Add($arResult);
						}
					}
				}
			}
		}
	}

	public static function OnAfterTMDayStart()
	{
		if(COption::GetOptionString("socialservices", "allow_send_user_activity", "Y") != 'Y')
			return;
		global $USER;
		$arResult = array();
		$arResult['USER_ID'] = intval($USER->GetID());
		if($arResult['USER_ID'] > 0)
		{
			$enabledSendMessage = CUserOptions::GetOption("socialservices", "user_socserv_enable", "N", $arResult['USER_ID']);
			if($enabledSendMessage == 'Y')
			{
				$enabledEndDaySend = CUserOptions::GetOption("socialservices", "user_socserv_start_day", "N", $arResult['USER_ID']);
				if($enabledEndDaySend == 'Y')
				{
					$arResult['MESSAGE'] = CUserOptions::GetOption("socialservices", "user_socserv_start_text", GetMessage("JS_CORE_SS_WORKDAY_START"), $arResult['USER_ID']);

					$socServArray = CUserOptions::GetOption("socialservices", "user_socserv_array", "a:0:{}", $arResult['USER_ID']);
					if(!CheckSerializedData($socServArray))
					{
						$socServArray = "a:0:{}";
					}

					$arSocServUser['SOCSERVARRAY'] = unserialize($socServArray);

					if(is_array($arSocServUser['SOCSERVARRAY']) && count($arSocServUser['SOCSERVARRAY']) > 0)
					{
						foreach($arSocServUser['SOCSERVARRAY'] as $id => $providerName)
						{
							$arResult['SOCSERV_USER_ID'] = $id;
							$arResult['PROVIDER'] = $providerName;
							CSocServMessage::Add($arResult);
						}
					}
				}
			}
		}
	}

	public function CheckSettings()
	{
		$arSettings = $this->GetSettings();
		if(is_array($arSettings))
		{
			foreach($arSettings as $sett)
				if(is_array($sett) && !array_key_exists("note", $sett))
					if(self::GetOption($sett[0]) == '')
						return false;
		}
		return true;
	}

	static public function CheckPhotoURI($photoURI)
	{
		if(preg_match("|^http[s]?://|i", $photoURI))
			return true;
		return false;
	}

	public static function OptionsSuffix()
	{
		//settings depend on current site
		$arUseOnSites = unserialize(COption::GetOptionString("socialservices", "use_on_sites", ""));
		return ($arUseOnSites[SITE_ID] == "Y"? '_bx_site_'.SITE_ID : '');
	}

	public static function GetOption($opt)
	{
		if(self::$settingsSuffix === false)
			self::$settingsSuffix = self::OptionsSuffix();

		return COption::GetOptionString("socialservices", $opt.self::$settingsSuffix);
	}

	public static function getGroupsDenyAuth()
	{
		return explode(',', (\COption::GetOptionString("socialservices", "group_deny_auth", "")));
	}

	public static function getGroupsDenySplit()
	{
		return explode(',', (\COption::GetOptionString("socialservices", "group_deny_split", "")));
	}

	public static function setGroupsDenyAuth($value)
	{
		\COption::SetOptionString('socialservices', 'group_deny_auth', is_array($value) ? implode(',', $value) : '');
	}

	public static function setGroupsDenySplit($value)
	{
		\COption::SetOptionString('socialservices', 'group_deny_split', is_array($value) ? implode(',', $value) : '');
	}

	public static function isSplitDenied($arGroups = null)
	{
		global $USER;

		if($arGroups === null)
		{
			return $USER->IsAuthorized()
				&& count(array_intersect(self::getGroupsDenySplit(), $USER->GetUserGroupArray())) > 0;
		}
		else
		{
			return count(array_intersect(self::getGroupsDenySplit(), $arGroups)) > 0;
		}
	}

	public static function isAuthDenied($arGroups)
	{
		return count(array_intersect(self::getGroupsDenyAuth(), $arGroups)) > 0;
	}

	static public function AuthorizeUser($arFields)
	{
		global $USER, $APPLICATION;

		if(!isset($arFields['XML_ID']) || $arFields['XML_ID'] == '')
			return false;
		if(!isset($arFields['EXTERNAL_AUTH_ID']) || $arFields['EXTERNAL_AUTH_ID'] == '')
			return false;

		$arOAuthKeys = array();
		if(isset($arFields["OATOKEN"]))
			$arOAuthKeys["OATOKEN"] = $arFields["OATOKEN"];
		if(isset($arFields["REFRESH_TOKEN"]) && $arFields["REFRESH_TOKEN"] !== '')
			$arOAuthKeys["REFRESH_TOKEN"] = $arFields["REFRESH_TOKEN"];
		if(isset($arFields["OATOKEN_EXPIRES"]))
			$arOAuthKeys["OATOKEN_EXPIRES"] = $arFields["OATOKEN_EXPIRES"];

		$errorCode = SOCSERV_AUTHORISATION_ERROR;

		$dbSocUser = CSocServAuthDB::GetList(
			array(),
			array(
				'XML_ID'=>$arFields['XML_ID'],
				'EXTERNAL_AUTH_ID'=>$arFields['EXTERNAL_AUTH_ID']
			), false, false, array("ID", "USER_ID", "ACTIVE")
		);
		$arUser = $dbSocUser->Fetch();

		if($USER->IsAuthorized())
		{
			if(!self::isSplitDenied())
			{
				if(!$arUser)
				{
					$id = CSocServAuthDB::Add($arFields);
				}
				else
				{
					$id = $arUser['ID'];

					// socservice link split
					if($arUser['USER_ID'] != $USER->GetID())
					{
						$dbRes = CSocServAuthDB::GetList(
							array(),
							array(
								'USER_ID'=>$USER->GetID(),
								'EXTERNAL_AUTH_ID'=>$arFields['EXTERNAL_AUTH_ID']
							), false, false, array("ID")
						);
						if($dbRes->Fetch())
						{
							return SOCSERV_AUTHORISATION_ERROR;
						}
						else
						{
							$arOAuthKeys['USER_ID'] = $USER->GetID();
							$arOAuthKeys['CAN_DELETE'] = 'Y';
						}
					}
				}

				if($_SESSION["OAUTH_DATA"] && is_array($_SESSION["OAUTH_DATA"]))
				{
					$arOAuthKeys = array_merge($arOAuthKeys, $_SESSION['OAUTH_DATA']);
					unset($_SESSION["OAUTH_DATA"]);
				}

				CSocServAuthDB::Update($id, $arOAuthKeys);
			}
			else
			{
				return SOCSERV_REGISTRATION_DENY;
			}
		}
		else
		{
			$entryId = 0;
			$USER_ID = 0;

			if($arUser)
			{
				$entryId = $arUser['ID'];
				if($arUser["ACTIVE"] === 'Y')
				{
					$USER_ID = $arUser["USER_ID"];
				}
			}
			else
			{
				// check for user with old socialservices linking system (socservice ID in user's EXTERNAL_AUTH_ID)
				$dbUsersOld = CUser::GetList($by='ID', $ord='ASC', array('XML_ID'=>$arFields['XML_ID'], 'EXTERNAL_AUTH_ID'=>$arFields['EXTERNAL_AUTH_ID'], 'ACTIVE'=>'Y'), array('NAV_PARAMS'=>array("nTopCount"=>"1")));
				$arUser = $dbUsersOld->Fetch();
				if($arUser)
				{
					$USER_ID = $arUser["ID"];
				}
				else
				{
					// theoretically possible situation with abandoned external user w/o b_socialservices_user entry
					$dbUsersNew = CUser::GetList($by='ID', $ord='ASC', array('XML_ID'=>$arFields['XML_ID'], 'EXTERNAL_AUTH_ID'=>'socservices', 'ACTIVE'=>'Y'),  array('NAV_PARAMS'=>array("nTopCount"=>"1")));
					$arUser = $dbUsersNew->Fetch();

					if($arUser)
					{
						$USER_ID = $arUser["ID"];
					}
					elseif
					(
						COption::GetOptionString("main", "new_user_registration", "N") == "Y"
						&& COption::GetOptionString("socialservices", "allow_registration", "Y") == "Y"
					)
					{
						$arFields['PASSWORD'] = randString(30); //not necessary but...
						$arFields['LID'] = SITE_ID;

						$def_group = COption::GetOptionString('main', 'new_user_registration_def_group', '');
						if($def_group <> '')
						{
							$arFields['GROUP_ID'] = explode(',', $def_group);
						}


						if(!empty($arFields['GROUP_ID']) && self::isAuthDenied($arFields['GROUP_ID']))
						{
							$errorCode = SOCSERV_REGISTRATION_DENY;
						}
						else
						{
							$arFieldsUser = $arFields;
							$arFieldsUser["EXTERNAL_AUTH_ID"] = "socservices";

							if(isset($arFieldsUser['PERSONAL_PHOTO']) && is_array($arFieldsUser['PERSONAL_PHOTO']))
							{
								$res = CFile::CheckImageFile($arFieldsUser["PERSONAL_PHOTO"]);
								if($res <> '')
								{
									unset($arFieldsUser['PERSONAL_PHOTO']);
								}
							}

							$USER_ID = $USER->Add($arFieldsUser);
							if($USER_ID <= 0)
							{
								$errorCode = SOCSERV_AUTHORISATION_ERROR;
							}
						}
					}
					elseif(COption::GetOptionString("main", "new_user_registration", "N") == "N")
					{
						$errorCode = SOCSERV_REGISTRATION_DENY;
					}

					$arFields['CAN_DELETE'] = 'N';

				}
			}

			if(isset($_SESSION["OAUTH_DATA"]) && is_array($_SESSION["OAUTH_DATA"]))
			{
				foreach ($_SESSION['OAUTH_DATA'] as $key => $value)
				{
					$arFields[$key] = $value;
				}
				unset($_SESSION["OAUTH_DATA"]);
			}

			if($USER_ID > 0)
			{
				$arGroups = $USER->GetUserGroup($USER_ID);
				if(self::isAuthDenied($arGroups))
				{
					return SOCSERV_AUTHORISATION_ERROR;
				}

				if($entryId > 0)
				{
					CSocServAuthDB::Update($entryId, $arFields);
				}
				else
				{
					$arFields['USER_ID'] = $USER_ID;
					CSocServAuthDB::Add($arFields);
				}

				$USER->AuthorizeWithOtp($USER_ID);
			}
			else
			{
				return $errorCode;
			}

			// possible redirect after authorization, so no spreading. Store cookies in the session for next hit
			$APPLICATION->StoreCookies();
		}

		return true;
	}

	public static function OnFindExternalUser($login)
	{
		global $DB;

		$res = $DB->Query("
SELECT bsu.USER_ID
FROM b_socialservices_user bsu
LEFT JOIN b_user bu ON bsu.USER_ID=bu.ID
WHERE bsu.LOGIN='".$DB->ForSql($login)."' AND bu.ACTIVE='Y'
");
		if(($user = $res->Fetch()))
		{
			return $user["USER_ID"];
		}
		return 0;
	}
}

//some repetitive functionality
class CSocServUtil
{
	const OAUTH_PACK_PARAM = "oauth_proxy_params";
	private static $oAuthParams = array("redirect_uri", "client_id", "scope", "response_type", "state");

	public static function GetCurUrl($addParam="", $removeParam=false, $checkOAuthProxy=true)
	{
		global $APPLICATION;

		$arRemove = array("logout", "auth_service_error", "auth_service_id", "MUL_MODE", "SEF_APPLICATION_CUR_PAGE_URL");

		if($removeParam !== false)
		{
			$arRemove = array_merge($arRemove, $removeParam);
		}

		if($checkOAuthProxy !== false)
		{
			$proxyString = "";
			foreach(self::$oAuthParams as $param)
			{
				if(isset($_GET[$param]))
				{
					$arRemove[] = $param;
					$proxyString .= ($proxyString == "" ? "" : "&").urlencode($param)."=".urlencode($_GET[$param]);
				}
			}

			if($proxyString != "")
			{
				$addParam .= ($addParam == "" ? "" : "&").self::packOAuthProxyString($proxyString);
			}
		}
		return self::ServerName().$APPLICATION->GetCurPageParam($addParam, $arRemove);
	}

	public static function ServerName($forceHttps = false)
	{
		$request = Context::getCurrent()->getRequest();

		$protocol = ($forceHttps || $request->isHttps()) ? "https" : "http";
		$serverName = $request->getHttpHost();

		// :-(
		if($protocol == "https")
		{
			$serverName = str_replace(":443", "", $serverName);
		}

		return $protocol.'://'.$serverName;
	}

	public static function packOAuthProxyString($proxyString)
	{
		return self::OAUTH_PACK_PARAM."=".urlencode(base64_encode($proxyString));
	}

	public static function checkOAuthProxyParams()
	{
		if(isset($_REQUEST[self::OAUTH_PACK_PARAM]) && strlen($_REQUEST[self::OAUTH_PACK_PARAM]) > 0)
		{
			$proxyString = base64_decode($_REQUEST[self::OAUTH_PACK_PARAM]);
			if(strlen($proxyString) > 0)
			{
				$arVars = array();
				parse_str($proxyString, $arVars);
				foreach(self::$oAuthParams as $param)
				{
					if(isset($arVars[$param]))
					{
						$_GET[$param] = $_REQUEST[$param] = $arVars[$param];
					}
				}
			}

			unset($_REQUEST[self::OAUTH_PACK_PARAM]);
			unset($_GET[self::OAUTH_PACK_PARAM]);
		}
	}
}

class CSocServAllMessage
{
	protected function CheckFields($action, &$arFields)
	{
		if(($action == "ADD" && !isset($arFields["SOCSERV_USER_ID"])) || (isset($arFields["SOCSERV_USER_ID"]) && intval($arFields["SOCSERV_USER_ID"])<=0))
		{
			return false;
		}
		if(($action == "ADD" && !isset($arFields["PROVIDER"])) || (isset($arFields["PROVIDER"]) && strlen($arFields["PROVIDER"])<=0))
		{
			return false;
		}
		if($action == "ADD")
			$arFields["INSERT_DATE"] = ConvertTimeStamp(time(), "FULL");
		return true;
	}

	static function Update($id, $arFields)
	{
		global $DB;
		$id = intval($id);
		if($id<=0 || !self::CheckFields('UPDATE', $arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_socialservices_message", $arFields);
		$strSql = "UPDATE b_socialservices_message SET ".$strUpdate." WHERE ID = ".$id." ";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$cache_id = 'socserv_mes_user';
		$obCache = new CPHPCache;
		$cache_dir = '/bx/socserv_mes_user';
		$obCache->Clean($cache_id, $cache_dir);

		return $id;
	}

	static function Delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			$rsUser = $DB->Query("SELECT ID FROM b_socialservices_message WHERE ID=".$id);
			$arUser = $rsUser->Fetch();
			if(!$arUser)
				return false;

			$DB->Query("DELETE FROM b_socialservices_message WHERE ID = ".$id." ", true);
			$cache_id = 'socserv_mes_user';
			$obCache = new CPHPCache;
			$cache_dir = '/bx/socserv_mes_user';
			$obCache->Clean($cache_id, $cache_dir);
			return true;
		}
		return false;
	}

}
