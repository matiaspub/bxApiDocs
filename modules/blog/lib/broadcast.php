<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage blog
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Blog;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Socialnetwork\LogFollowTable;


Loc::loadMessages(__FILE__);

class Broadcast
{
	const ON_CNT = 5;
	const OFF_CNT = 5;

	const ON_PERIOD = 'P7D'; // 7 days
	const OFF_PERIOD = 'P7D'; // 7 days

	private function getValue()
	{
		return Option::get('blog', 'log_notify_all', "N");
	}

	private function setValue($value = false)
	{
		$value = ($value === true);
		Option::set('blog', 'log_notify_all', ($value ? "Y" : "N"));
	}

	private function getOffModeRequested()
	{
		return (Option::get('blog', 'log_notify_all_off_requested', false) == "Y");
	}

	private function getOnModeRequested()
	{
		return (Option::get('blog', 'log_notify_all_on_requested', false) == "Y");
	}

	private function setOffModeRequested()
	{
		Option::set('blog', 'log_notify_all_off_requested', 'Y');
	}

	private function setOnModeRequested()
	{
		Option::set('blog', 'log_notify_all_on_requested', 'Y');
	}

	private function getCount($period)
	{
		$counter = 0;

		$now = new \DateTime();

		$res = PostTable::getList(array(
			'order' => array(),
			'filter' => array(
				"=PostSocnetRights:POST.ENTITY" => 'G2',
				"=PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
				'>DATE_PUBLISH' => DateTime::createFromUserTime(DateTime::createFromUserTime($now->sub(new \DateInterval($period))->format(DateTime::getFormat()))),
			),
			'group' => array(),
			'select' => array('CNT'),
			'runtime' => array(
				new ExpressionField('CNT', 'COUNT(*)')
			),
			'data_doubling' => false
		));
		while($ar = $res->fetch())
		{
			$counter = intval($ar['CNT']);
		}

		return $counter;
	}

	private function onModeNeeded()
	{
		$counter = self::getCount(self::ON_PERIOD);

		return ($counter < self::ON_CNT);
	}

	private function offModeNeeded()
	{
		$counter = self::getCount(self::OFF_PERIOD);

		return ($counter > self::OFF_CNT);
	}

	public static function getData()
	{
		$result = array(
			"cnt" => 0,
			"rate" => 0
		);
		$value = Option::get('blog', 'log_notify_all_data', false);
		if ($value)
		{
			$value = unserialize($value);
			if (
				is_array($value)
				&& isset($value['cnt'])
				&& isset($value['rate'])
			)
			{
				$result = array(
					"cnt" => intval($value['cnt']),
					"rate" => intval($value['rate'])
				);
			}
		}

		return $result;
	}

	public static function setRequestedMode($value)
	{
		$value = ($value === true);

		if ($value)
		{
			self::setOnModeRequested();
		}
		else
		{
			self::setOffModeRequested();
		}
	}

	public static function checkMode()
	{
		if (ModuleManager::isModuleInstalled('intranet'))
		{
			$onModeRequested = self::getOnModeRequested();
			$offModeRequested = self::getOffModeRequested();
			$mode = self::getValue();

			if (
				$onModeRequested
				&& $offModeRequested
			)
			{
				return false;
			}

			if (
				$mode == "N"
				&& !$onModeRequested
			)
			{
				if (self::onModeNeeded())
				{
					self::sendRequest(true);
				}

			}
			elseif (
				$mode == "Y"
				&& !$offModeRequested
			)
			{
				if (self::offModeNeeded())
				{
					self::sendRequest(false);
				}
			}
		}

		return true;
	}

	private function sendRequest($value, $siteId = SITE_ID)
	{
		$value = ($value === true);

		if (Loader::includeModule('im'))
		{
			$str = ($value ? 'ON' : 'OFF');
			$tag = "BLOG|BROADCAST_REQUEST|".($value ? "ON" : "OFF");

			$fields = array(
				"MESSAGE_TYPE" => IM_MESSAGE_SYSTEM,
				"NOTIFY_TYPE" => IM_NOTIFY_CONFIRM,
				"NOTIFY_MODULE" => "blog",
				"NOTIFY_EVENT" => "log_notify_all_request",
				"NOTIFY_SUB_TAG" => $tag,
				"NOTIFY_MESSAGE" => Loc::getMessage("BLOG_BROADCAST_REQUEST_IM_MESSAGE_".$str),
				"NOTIFY_MESSAGE_OUT" => IM_MAIL_SKIP,
				"NOTIFY_BUTTONS" => Array(
					array("TITLE" => GetMessage("BLOG_BROADCAST_REQUEST_IM_BUTTON_".$str."_Y"), "VALUE" => "Y", "TYPE" => "accept"),
					array("TITLE" => GetMessage("BLOG_BROADCAST_REQUEST_IM_BUTTON_".$str."_N"), "VALUE" => "N", "TYPE" => "cancel"),
				)
			);

			$moduleAdminList = array_keys(\Bitrix\Socialnetwork\User::getModuleAdminList(array($siteId, false)));
			foreach($moduleAdminList as $userId)
			{
				$fields["TO_USER_ID"] = $userId;
				$fields["NOTIFY_TAG"] = $tag."|".$userId;

				\CIMNotify::add($fields);
			}
		}

		self::setRequestedMode($value);
	}

	public static function send($params)
	{
		if (
			!Loader::includeModule('intranet')
			|| !Loader::includeModule('pull')
		)
		{
			return false;
		}

		if (
			empty($params["ENTITY_TYPE"])
			|| !in_array($params["ENTITY_TYPE"], array("POST"))
			|| empty($params["ENTITY_ID"])
			|| empty($params["AUTHOR_ID"])
			|| empty($params["URL"])
			|| empty($params["SOCNET_RIGHTS"])
			|| !is_array($params["SOCNET_RIGHTS"])
		)
		{
			return false;
		}

		if (empty($params["SITE_ID"]))
		{
			$params["SITE_ID"] = SITE_ID;
		}

		$res = Main\UserTable::getList(array(
			'filter' => array(
				'=ID' => intval($params["AUTHOR_ID"])
			),
			'select' => array('ID', 'PERSONAL_GENDER', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN')
		));

		if($author = $res->fetch())
		{
			$author['NAME_FORMATTED'] = \CUser::formatName(\CSite::getNameFormat(null, $params["SITE_ID"]), $author, true);
			switch($author['PERSONAL_GENDER'])
			{
				case 'M':
					$authorSuffix = '_M';
					break;
				case 'F':
					$authorSuffix = '_F';
					break;
				default:
					$authorSuffix = '';
			}
		}
		else
		{
			return false;
		}

		if (
			!empty($params["SOCNET_RIGHTS_OLD"])
			&& is_array($params["SOCNET_RIGHTS_OLD"])
		)
		{
			$rightsOld = array();
			foreach($params["SOCNET_RIGHTS_OLD"] as $key => $entities)
			{
				foreach($entities as $rightsList)
				{
					foreach($rightsList as $right)
					{
						$rightsOld[] = ($right == 'G2' ? 'UA' : $right);
					}
				}
			}
			$params["SOCNET_RIGHTS"] = array_diff($params["SOCNET_RIGHTS"], $rightsOld);
		}

		$found = false;

		$userListParams = array(
			"SKIP" => intval($params["AUTHOR_ID"]),
			"DEPARTMENTS" => array()
		);

		foreach($params["SOCNET_RIGHTS"] as $right)
		{
			if ($right == 'UA')
			{
				$userListParams["SITE_ID"] = $params["SITE_ID"];
				$found = true;
			}
			elseif (preg_match('/^DR(\d+)$/', $right, $matches))
			{
				$userListParams["DEPARTMENTS"][] = $matches[1];
				$found = true;
			}
		}

		if ($found)
		{
			$userList = \Bitrix\Intranet\Util::getEmployeesList($userListParams);
		}

		if (empty($userList))
		{
			return false;
		}

		if (
			$params["ENTITY_TYPE"] == "POST"
			&& ($post = \CBlogPost::getByID($params["ENTITY_ID"]))
		)
		{
			$titleTmp = str_replace(array("\r\n", "\n"), " ", $post["TITLE"]);
			$title = truncateText($titleTmp, 100);
			$titleEmail = ($post['MICRO'] != 'Y' ? truncateText($titleTmp, 255) : '');

			$titleEmpty = (strlen(trim($title, " \t\n\r\0\x0B\xA0" )) <= 0);

			$message = Loc::getMessage(
				'BLOG_BROADCAST_PUSH_POST'.($titleEmpty ? 'A' : '').$authorSuffix,
				array(
					'#author#' => $author['NAME_FORMATTED'],
					'#title#' => $title
				)
			);

			$userIdList = array_keys($userList);
			if (
				!empty($params["EXCLUDE_USERS"])
				&& is_array($params["EXCLUDE_USERS"])
			)
			{
				$userIdList = array_diff($userIdList, $params["EXCLUDE_USERS"]);
			}

			if (!empty($userIdList))
			{
				$userIdListPush = $userIdList;
				foreach ($userIdListPush as $key=> $userId)
				{
					if (!\CIMSettings::getNotifyAccess($userId, 'blog', 'broadcast_post', \CIMSettings::CLIENT_PUSH))
					{
						unset($userIdListPush[$key]);
					}
				}

				$CPushManager = new \CPushManager();

				$CPushManager->addQueue(Array(
					'USER_ID' => $userIdListPush,
					'MESSAGE' => str_replace("\n", " ", $message),
					'PARAMS' => array(
						'ACTION' => 'post',
						'TAG' => 'BLOG|POST|'.$params["ENTITY_ID"]
					),
					'TAG' => 'BLOG|POST|'.$params["ENTITY_ID"],
					'SEND_IMMEDIATELY' => 'Y',
				));

				$CPushManager->addQueue(Array(
					'USER_ID' => $userIdListPush,
					'SEND_DEFERRED' => 'Y',
				));

				$offlineUserIdList = array();

				$deviceInfo = \CPushManager::getDeviceInfo($userIdList);
				foreach($deviceInfo as $userId => $info)
				{
					if (
						in_array($info['mode'], array(\CPushManager::SEND_DEFERRED, \CPushManager::RECORD_NOT_FOUND))
						&& \CIMSettings::getNotifyAccess($userId, 'blog', 'broadcast_post', \CIMSettings::CLIENT_MAIL)
					)
					{
						$offlineUserIdList[] = $userId;
					}
				}

				if (!empty($offlineUserIdList))
				{
					$res = Main\UserTable::getList(array(
						'filter' => array(
							'=SEND_EMAIL' => 'Y',
							'@ID' => $offlineUserIdList
						),
						'runtime' => array(
							new Main\Entity\ExpressionField('SEND_EMAIL', "CASE WHEN LAST_ACTIVITY_DATE IS NOT NULL AND LAST_ACTIVITY_DATE > ".Main\Application::getConnection()->getSqlHelper()->addSecondsToDateTime('-'.(60*60*24*90))." THEN 'Y' ELSE 'N' END")
						),
						'select' => array('ID')
					));

					$offlineUserIdList = array();
					while($ar = $res->fetch())
					{
						$offlineUserIdList[] = $ar['ID'];
					}
				}

				if (!empty($offlineUserIdList))
				{
					$serverName = '';

					$res = \CSite::getByID($params["SITE_ID"]);
					if ($site = $res->fetch())
					{
						$serverName = $site['SERVER_NAME'];
					}
					if (empty($serverName))
					{
						$serverName = (
							defined("SITE_SERVER_NAME")
							&& strlen(SITE_SERVER_NAME) > 0
								? SITE_SERVER_NAME
								: Option::get("main", "server_name", $_SERVER["SERVER_NAME"])
						);
					}

					$serverName = (\CMain::isHTTPS() ? "https" : "http")."://".$serverName;

					$textEmail = $post["DETAIL_TEXT"];
					if ($post["DETAIL_TEXT_TYPE"] == "html")
					{
						$textEmail = HTMLToTxt($textEmail);
					}

					$imageList = array();
					$res = \CBlogImage::getList(
						array("ID"=>"ASC"),
						array(
							"POST_ID" => $post["ID"],
							"BLOG_ID" => $post["BLOG_ID"],
							"IS_COMMENT" => "N"
						)
					);
					while ($image = $res->fetch())
					{
						$imageList[$image['ID']] = $image['FILE_ID'];
					}
					$parserBlog = new \blogTextParser();
					$textEmail = $parserBlog->convert4mail($textEmail, $imageList);

					foreach($offlineUserIdList as $userId)
					{
						if (!empty($userList[$userId]["EMAIL"]))
						{
							\CEvent::send(
								"BLOG_POST_BROADCAST",
								$params["SITE_ID"],
								array(
									"EMAIL_TO" => (!empty($userList[$userId]["NAME_FORMATTED"]) ? ''.$userList[$userId]["NAME_FORMATTED"].' <'.$userList[$userId]["EMAIL"].'>' : $userList[$userId]["EMAIL"]),
									"AUTHOR" => $author['NAME_FORMATTED'],
									"MESSAGE_TITLE" => $titleEmail,
									"MESSAGE_TEXT" => $textEmail,
									"MESSAGE_PATH" => $serverName.$params["URL"]
								)
							);
						}
					}
				}
			}
		}

		return false;
	}

	public static function onBeforeConfirmNotify($module, $tag, $value, $params)
	{
		if ($module == "blog")
		{
			$tagList = explode("|", $tag);
			if (
				count($tagList) == 4
				&& $tagList[1] == 'BROADCAST_REQUEST'
			)
			{
				$mode = $tagList[2];
				if (
					$value == 'Y'
					&& in_array($mode, array('ON', 'OFF'))
				)
				{
					self::setValue($mode == 'ON');
					\CIMNotify::deleteBySubTag("BLOG|BROADCAST_REQUEST|".$mode);
				}

				return true;
			}
		}

		return false;
	}
}
