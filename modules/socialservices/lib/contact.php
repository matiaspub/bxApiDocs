<?php
namespace Bitrix\Socialservices;

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class ContactTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional default 'CURRENT_TIMESTAMP'
 * <li> USER_ID int mandatory
 * <li> CONTACT_USER_ID int optional
 * <li> CONTACT_XML_ID int optional
 * <li> CONTACT_NAME string(255) optional
 * <li> CONTACT_LAST_NAME string(255) optional
 * <li> CONTACT_PHOTO string(255) optional
 * <li> NOTIFY bool optional default 'N'
 * </ul>
 *
 * @package Bitrix\Socialservices
 **/

class ContactTable extends Main\Entity\DataManager
{
	const NOTIFY = 'Y';
	const DONT_NOTIFY = 'N';

	const NOTIFY_CONTACT_COUNT = 3;
	const NOTIFY_POSSIBLE_COUNT = 3;

	const POSSIBLE_LAST_AUTHORIZE_LIMIT = '-1 weeks';
	const POSSIBLE_RESET_TIME = 2592000; // 86400 * 30
	const POSSIBLE_RESET_TIME_KEY = "_ts";

	protected static $notifyStack = array();

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_socialservices_contact';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
			),
			'USER_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'CONTACT_USER_ID' => array(
				'data_type' => 'integer',
			),
			'CONTACT_XML_ID' => array(
				'data_type' => 'integer',
			),
			'CONTACT_NAME' => array(
				'data_type' => 'string',
			),
			'CONTACT_LAST_NAME' => array(
				'data_type' => 'string',
			),
			'CONTACT_PHOTO' => array(
				'data_type' => 'string',
			),
			'LAST_AUTHORIZE' => array(
				'data_type' => 'datetime',
			),
			'NOTIFY' => array(
				'data_type' => 'boolean',
				'values' => array(static::DONT_NOTIFY, static::NOTIFY),
			),
			'USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.USER_ID' => 'ref.ID'),
			),
			'CONTACT_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.CONTACT_USER_ID' => 'ref.ID'),
			),
		);
	}

	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		$data = $event->getParameter("fields");

		if(!isset($data['TIMESTAMP_X']))
		{
			$data['TIMESTAMP_X'] = new DateTime();
			$result->modifyFields($data);
		}
	}

	public static function onUserLoginSocserv($params)
	{
		global $USER;

		if(
			$params['EXTERNAL_AUTH_ID'] === \CSocServBitrix24Net::ID
			&& \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24')
		)
		{
			$notificationOptions = \CUserOptions::getOption("socialservices", "notifications", array());

			$lastDate = 0;
			if(isset($notificationOptions["CONTACTS_NOTIFY_DATE"]))
			{
				$lastDate = $notificationOptions["CONTACTS_NOTIFY_DATE"];
			}

			if($lastDate < time() - 86400)
			{
				static::notifyPossible($USER->getId());

				$notificationOptions["CONTACTS_NOTIFY_DATE"] = time();
				\CUserOptions::setOption("socialservices", "notifications", $notificationOptions);
			}
		}
	}

	public static function onNetworkBroadcast($data)
	{
		$contactsList = array();
		$possibleContactsList = array();

		if(isset($data["contact"]) && is_array($data["contact"]))
		{
			foreach($data["contact"] as $contact)
			{
				if(!isset($contactsList[$contact['CONTACT_OWNER']]))
				{
					$contactsList[$contact['CONTACT_OWNER']] = array();
				}

				$contactsList[$contact['CONTACT_OWNER']][] = $contact;
			}
		}

		if(isset($data["contact_possible"]) && is_array($data["contact_possible"]))
		{
			foreach($data["contact_possible"] as $contact)
			{
				if(!isset($possibleContactsList[$contact['CONTACT_OWNER']]))
				{
					$possibleContactsList[$contact['CONTACT_OWNER']] = array();
				}

				$possibleContactsList[$contact['CONTACT_OWNER']][] = $contact;
			}
		}

		$dbRes = UserTable::getList(array(
			'filter' => array(
				'=EXTERNAL_AUTH_ID' => \CSocServBitrix24Net::ID,
				'=XML_ID' => array_unique(
					array_merge(
						array_keys($contactsList),
						array_keys($possibleContactsList)
					)
				),
			),
			'select' => array('ID', 'USER_ID', 'XML_ID')
		));

		while($owner = $dbRes->fetch())
		{
			if(
				count($contactsList) > 0
				&& count($contactsList[$owner["XML_ID"]]) > 0
			)
			{
				static::processContacts($owner, $contactsList[$owner["XML_ID"]]);
			}

			if(
				count($possibleContactsList) > 0
				&& count($possibleContactsList[$owner["XML_ID"]]) > 0
			)
			{
				static::processPossibleContacts($owner, $possibleContactsList[$owner["XML_ID"]]);
			}
		}
	}

	protected static function processContacts($owner, array $contactsList)
	{
		if(!Main\Loader::includeModule('rest'))
		{
			return;
		}

		$existedContacts = array();
		$dbRes = ContactTable::getList(array(
			'filter' => array(
				'=USER_ID' => $owner["USER_ID"],
			),
			'select' => array('ID', 'CONTACT_XML_ID')
		));
		while($existedContact = $dbRes->fetch())
		{
			$existedContacts[$existedContact['CONTACT_XML_ID']] = $existedContact['ID'];
		}

		foreach($contactsList as $contact)
		{
			$contactFields = array(
				"USER_ID" => $owner["USER_ID"],
				"CONTACT_XML_ID" => $contact["CONTACT_ID"],
				"CONTACT_NAME" => $contact["NAME"],
				"CONTACT_LAST_NAME" => $contact["LAST_NAME"],
				"CONTACT_PHOTO" => $contact["PHOTO"],
				"NOTIFY" => $contact["NOTIFY"],
				"LAST_AUTHORIZE" => DateTime::createFromUserTime(\CRestUtil::unConvertDateTime($contact['LAST_AUTHORIZE'])),
			);

			$contactId = false;
			if(isset($existedContacts[$contactFields["CONTACT_XML_ID"]]))
			{
				$contactId = $existedContacts[$contactFields["CONTACT_XML_ID"]];
				$result = static::update($contactId, $contactFields);
				if(!$result->isSuccess())
				{
					AddMessage2Log($result->getErrorMessages());
				}
			}
			else
			{
				$result = static::add($contactFields);
				if($result->isSuccess())
				{
					$contactId = $result->getId();
				}
				else
				{
					AddMessage2Log($result->getErrorMessages());
				}
			}

			if(
				$contactId > 0
				&& isset($contact["profile"])
				&& count($contact["profile"]) > 0
			)
			{
				if(isset($existedContacts[$contactFields["CONTACT_XML_ID"]]))
				{
					ContactConnectTable::deleteByContact($contactId);
				}

				foreach($contact["profile"] as $profile)
				{
					$connectFields = array(
						'CONTACT_ID' => $contactId,
						'CONTACT_PROFILE_ID' => $profile['PROFILE_ID'],
						'CONTACT_PORTAL' => $profile['PORTAL'],
						'CONNECT_TYPE' => $profile['TYPE'],
						'LAST_AUTHORIZE' => DateTime::createFromUserTime(\CRestUtil::unConvertDateTime($profile['LAST_AUTHORIZE'])),
					);

					$r = ContactConnectTable::add($connectFields);
					if($r->isSuccess())
					{
						if(!isset($contactFields["CONNECT"]))
						{
							$contactFields["CONNECT"] = array($connectFields);
						}
						else
						{
							$contactFields["CONNECT"][] = $connectFields;
						}
					}
				}

				if(!isset($existedContacts[$contactFields["CONTACT_XML_ID"]]))
				{
					static::notifyJoin($contactId, $contactFields);
				}
			}
		}

		static::notifyJoinFinish($owner["USER_ID"]);
	}

	protected static function processPossibleContacts($owner, array $contactsList)
	{
		if(!Main\Loader::includeModule('rest'))
		{
			return;
		}

		$existedContacts = array();
		$dbRes = UserLinkTable::getList(array(
			'filter' => array(
				'=SOCSERV_USER_ID' => $owner["ID"],
				'=SOCSERV_USER.EXTERNAL_AUTH_ID' => \CSocServBitrix24Net::ID,
			),
			'select' => array('ID', 'LINK_UID')
		));
		while($existedContact = $dbRes->fetch())
		{
			$existedContacts[$existedContact['LINK_UID']] = $existedContact['ID'];
		}

		foreach($contactsList as $contact)
		{
			$contactFields = array(
				"USER_ID" => $owner["USER_ID"],
				"SOCSERV_USER_ID" => $owner["ID"],
				"LINK_UID" => $contact["CONTACT_ID"],
				"LINK_NAME" => $contact["NAME"],
				"LINK_LAST_NAME" => $contact["LAST_NAME"],
				"LINK_PICTURE" => $contact["PHOTO"],
			);

			$linkId = false;
			if(isset($existedContacts[$contactFields["LINK_UID"]]))
			{
				$linkId = $existedContacts[$contactFields["LINK_UID"]];
				UserLinkTable::update($linkId, $contactFields);
			}
			else
			{
				$result = UserLinkTable::add($contactFields);
				if($result->isSuccess())
				{
					$linkId = $result->getId();
				}
			}

			if(
				$linkId !== false
				&& isset($contact["profile"])
				&& count($contact["profile"]) > 0
			)
			{
				if(isset($existedContacts[$contactFields["LINK_UID"]]))
				{
					ContactConnectTable::deleteByLink($linkId);
				}

				foreach($contact["profile"] as $profile)
				{
					$result = ContactConnectTable::add(array(
						'LINK_ID' => $linkId,
						'CONTACT_PROFILE_ID' => $profile['PROFILE_ID'],
						'CONTACT_PORTAL' => $profile['PORTAL'],
						'CONNECT_TYPE' => $profile['TYPE'],
						'LAST_AUTHORIZE' => DateTime::createFromUserTime(\CRestUtil::unConvertDateTime($profile['LAST_AUTHORIZE'])),
					));
				}
			}
		}
	}

	public static function getConnectId($connect)
	{
		return $connect["CONNECT_TYPE"].$connect["CONTACT_PROFILE_ID"];
	}

	protected static function notifyJoin($contactId, array $contactInfo = null)
	{
		if(Main\Loader::includeModule('im'))
		{
			if($contactInfo === null)
			{
				$dbRes = static::getByPrimary($contactId);
				$contactInfo = $dbRes->fetch();
			}

			if(!$contactInfo)
			{
				return false;
			}

			if(!isset($contactInfo["CONNECT"]))
			{
				$contactInfo["CONNECT"] = array();
				$dbRes = ContactConnectTable::getList(array(
					"order" => array("LAST_AUTHORIZE" => "ASC"),
					"filter" => array(
						"=CONTACT_ID" => $contactInfo["ID"],
					),
					"limit" => 1,
					"select" => array(
						"CONTACT_PROFILE_ID", "CONNECT_TYPE"
					)
				));
				while($connect = $dbRes->fetch())
				{
					$contactInfo["CONNECT"][] = $connect;
				}
			}

			if(count($contactInfo["CONNECT"]) > 0)
			{
				if(isset($contactInfo["CONTACT_PHOTO_RESIZED"]))
				{
					$contactInfo["CONTACT_PHOTO"] = $contactInfo["CONTACT_PHOTO_RESIZED"];
				}

				if($contactInfo["NOTIFY"] == ContactTable::NOTIFY)
				{
					$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);

					$attachParams = array(
						"NAME" => \CUser::FormatName(\CSite::GetNameFormat(), array(
							"NAME" => $contactInfo["CONTACT_NAME"],
							"LAST_NAME" => $contactInfo["CONTACT_LAST_NAME"]
						), false, false),
					);
					if($contactInfo["CONTACT_PHOTO"])
					{
						$attachParams["AVATAR"] = $contactInfo["CONTACT_PHOTO"];
					}
					$attachParams["NETWORK_ID"] = static::getConnectId($contactInfo["CONNECT"][0]);

					$attach->AddUser($attachParams);

					$messageFields = array(
						"TO_USER_ID" => $contactInfo["USER_ID"],
						"FROM_USER_ID" => 0,
						"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
						"NOTIFY_MODULE" => "socialservices",
						"NOTIFY_EVENT" => "contacts",
						"NOTIFY_MESSAGE" => Loc::getMessage("SS_JOIN_NOTIFY"),
						"NOTIFY_MESSAGE_OUT" => IM_MAIL_SKIP,
						"ATTACH" => array($attach),
					);

					return \CIMNotify::Add($messageFields);
				}
				else
				{
					static::$notifyStack[] = $contactInfo;
				}
			}
		}

		return false;
	}

	protected static function notifyJoinFinish($userId)
	{
		if(
			count(static::$notifyStack) > 0
			&& Main\Loader::includeModule('im')
		)
		{
			$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);

			$count = 0;
			foreach(static::$notifyStack as $contactInfo)
			{
				if(++$count > static::NOTIFY_CONTACT_COUNT)
				{
					$attach->AddHtml('<a href="'.str_replace("#USER_ID#", $userId, Option::get("intranet", "path_user", "/company/persona/user/#USER_ID#/")).'">'.Loc::getMessage("SS_JOIN_NOTIFY_MORE", array("#NUM#" => count(static::$notifyStack)-$count+1)).'</a>');
					break;
				}
				else
				{
					$attachParams = array(
						"NAME" => \CUser::FormatName(\CSite::GetNameFormat(), array(
							"NAME" => $contactInfo["CONTACT_NAME"],
							"LAST_NAME" => $contactInfo["CONTACT_LAST_NAME"]
						), false, false),
					);
					if($contactInfo["CONTACT_PHOTO"])
					{
						$attachParams["AVATAR"] = $contactInfo["CONTACT_PHOTO"];
					}
					$attachParams["NETWORK_ID"] = static::getConnectId($contactInfo["CONNECT"][0]);

					$attach->AddUser($attachParams);
				}
			}

			$messageFields = array(
				"TO_USER_ID" => $userId,
				"FROM_USER_ID" => 0,
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
				"NOTIFY_MODULE" => "socialservices",
				"NOTIFY_EVENT" => "multiple_contacts",
				"NOTIFY_MESSAGE" => Loc::getMessage("SS_JOIN_NOTIFY_MULTIPLE"),
				"NOTIFY_MESSAGE_OUT" => IM_MAIL_SKIP,
				"ATTACH" => array($attach),
			);

			\CIMNotify::Add($messageFields);

			static::$notifyStack = array();
		}
	}

	protected static function notifyPossible($userId)
	{
		if(Main\Loader::includeModule('im'))
		{
			$ts = time();
			$alreadyShown = \CUserOptions::GetOption("socialservices", "possible_contacts", null, $userId);

			if(
				!is_array($alreadyShown)
				|| $alreadyShown[static::POSSIBLE_RESET_TIME_KEY] < $ts - static::POSSIBLE_RESET_TIME
			)
			{
				$alreadyShown = array();
			}
			else
			{
				$ts = $alreadyShown[static::POSSIBLE_RESET_TIME_KEY];
				unset($alreadyShown[static::POSSIBLE_RESET_TIME_KEY]);
			}

			$dateLimit = new DateTime();
			$dateLimit->add(static::POSSIBLE_LAST_AUTHORIZE_LIMIT);

			$contactList = ContactConnectTable::getList(array(
				'order' => array('LAST_AUTHORIZE' => 'DESC'),
				'filter' => array(
					'!=LINK_ID' => '',
					'=CONNECT_TYPE' => ContactConnectTable::TYPE_PORTAL,
					'>=LAST_AUTHORIZE' => $dateLimit,
					'=LINK.USER_ID' => $userId,
					'!=LINK.ID' => $alreadyShown,
				),
				'count_total' => true,
				'group' => array('LINK_ID'),
				'limit' => static::NOTIFY_POSSIBLE_COUNT,
				'select' => array(
					'LINK_ID', 'LINK_NAME' => 'LINK.LINK_NAME', 'LINK_LAST_NAME' => 'LINK.LINK_LAST_NAME', 'LINK_PICTURE' => 'LINK.LINK_PICTURE',
				),
			));

			/*
			$contactList = UserLinkTable::getList(array(
				'order' => array("RND" => "ASC"),
				'filter' => array(
					'=USER_ID' => $userId,
					'!=ID' => $alreadyShown,
				),
				'limit' => static::NOTIFY_POSSIBLE_COUNT,
				'count_total' => true,
				'group' => array("CONNECT.CONTACT_ID"), // Mistake? CONNECT.LINK_ID should be here
				'runtime' => array(
					new Entity\ExpressionField('RND', 'RAND()'),
					new Entity\ReferenceField(
						"CONNECT",
						ContactConnectTable::getEntity(),
						array(
							"=ref.LINK_ID" => "this.ID",
							"=ref.CONNECT_TYPE" => new Main\DB\SqlExpression(
								'?', ContactConnectTable::TYPE_PORTAL
							)
						),
						array("join_type"=>"inner")
					),
				)
			));
			*/

			$count = $contactList->getCount();
			if($count > 0)
			{
				$attach = new \CIMMessageParamAttach(null, \CIMMessageParamAttach::NORMAL);

				while($contactInfo = $contactList->fetch())
				{
					$alreadyShown[] = $contactInfo["LINK_ID"];

					// get all link portals, authorized during last week
					$contactInfo["CONNECT"] = array();
					$dbRes = ContactConnectTable::getList(array(
						"order" => array("LAST_AUTHORIZE" => "DESC"),
						"filter" => array(
							"=LINK_ID" => $contactInfo["LINK_ID"],
							">=LAST_AUTHORIZE" => $dateLimit,
						),
						"limit" => 1,
						"select" => array(
							"CONTACT_PROFILE_ID", "CONNECT_TYPE"
						)
					));
					while($connect = $dbRes->fetch())
					{
						$contactInfo["CONNECT"][] = $connect;
					}

					if(count($contactInfo["CONNECT"]) > 0)
					{
						$attachParams = array(
							"NAME" => \CUser::FormatName(\CSite::GetNameFormat(), array(
								"NAME" => $contactInfo["LINK_NAME"],
								"LAST_NAME" => $contactInfo["LINK_LAST_NAME"]
							), false, false),
						);
						if($contactInfo["LINK_PICTURE"])
						{
							$attachParams["AVATAR"] = $contactInfo["LINK_PICTURE"];
						}
						$attachParams["NETWORK_ID"] = static::getConnectId($contactInfo["CONNECT"][0]);

						$attach->AddUser($attachParams);
					}
				}
/*
				if($count > static::NOTIFY_POSSIBLE_COUNT)
				{
					$attach->AddHtml('<a href="'.str_replace("#USER_ID#", $userId, Option::get("intranet", "path_user", "/company/persona/user/#USER_ID#/")).'">'.Loc::getMessage("SS_JOIN_NOTIFY_MORE", array("#NUM#" => $count - static::NOTIFY_POSSIBLE_COUNT)).'</a>');
				}
*/
				$messageFields = array(
					"TO_USER_ID" => $userId,
					"FROM_USER_ID" => 0,
					"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
					"NOTIFY_MODULE" => "socialservices",
					"NOTIFY_EVENT" => "possible_contacts",
					"NOTIFY_MESSAGE" => Loc::getMessage("SS_JOIN_NOTIFY_POSSIBLE"),
					"NOTIFY_MESSAGE_OUT" => IM_MAIL_SKIP,
					"ATTACH" => $attach,
				);

				\CIMNotify::Add($messageFields);

			}

			$alreadyShown[static::POSSIBLE_RESET_TIME_KEY] = $ts;
			\CUserOptions::SetOption("socialservices", "possible_contacts", $alreadyShown, false, $userId);
		}
	}
}