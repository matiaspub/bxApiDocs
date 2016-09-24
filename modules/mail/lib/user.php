<?php

namespace Bitrix\Mail;

use Bitrix\Main;

Main\Localization\Loc::loadMessages(__FILE__);

class User
{

	/**
	 * Creates mail user
	 *
	 * @param array $fields User fields.
	 * @return int|false
	 */
	
	/**
	* <p>Метод создает пользователя почты. Метод статический.</p>
	*
	*
	* @param array $fields  Массив полей пользователя.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/create.php
	* @author Bitrix
	*/
	public static function create($fields)
	{
		$user = new \CUser;

		$userFields = array(
			'LOGIN'            => $fields["EMAIL"],
			'EMAIL'            => $fields["EMAIL"],
			'NAME'             => (!empty($fields["NAME"]) ? $fields["NAME"] : ''),
			'LAST_NAME'        => (!empty($fields["LAST_NAME"]) ? $fields["LAST_NAME"] : ''),
			'PERSONAL_PHOTO'   => (!empty($fields["PERSONAL_PHOTO_ID"]) ? \CFile::makeFileArray($fields['PERSONAL_PHOTO_ID']) : false),
			'EXTERNAL_AUTH_ID' => 'email',
		);
		if (
			isset($fields['UF'])
			&& is_array($fields['UF'])
		)
		{
			foreach($fields['UF'] as $key => $value)
			{
				if (!empty($value))
				{
					$userFields[$key] = $value;
				}
			}
		}

		$mailGroup = self::getMailUserGroup();
		if (!empty($mailGroup))
		{
			$userFields["GROUP_ID"] = $mailGroup;
		}
		$result = $user->add($userFields);

		return $result;
	}

	/**
	 * Runs user login
	 *
	 * @return void
	 */
	
	/**
	* <p>Метод запускает логин пользователя. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/login.php
	* @author Bitrix
	*/
	public static function login()
	{
		$eventManager = Main\EventManager::getInstance();
		$handler = $eventManager->addEventHandlerCompatible('main', 'OnUserLoginExternal', array('\Bitrix\Mail\User', 'onLoginExternal'));

		global $USER;
		$USER->login(null, null, 'Y');

		$eventManager->removeEventHandler('main', 'OnUserLoginExternal', $handler);
	}

	/**
	 * Returns mail user ID
	 *
	 * @param array &$params Auth params.
	 * @return int|false
	 */
	
	/**
	* <p>Метод возвращает идентификатор пользователя почты. Метод статический.</p>
	*
	*
	* @param array $array  Параметры аутентификации.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/onloginexternal.php
	* @author Bitrix
	*/
	public static function onLoginExternal(&$params)
	{
		$context = Main\Application::getInstance()->getContext();
		$request = $context->getRequest();

		if ($token = $request->get('token') ?: $request->getCookie('MAIL_AUTH_TOKEN'))
		{
			$userRelation = UserRelationsTable::getList(array(
				'select' => array('USER_ID'),
				'filter' => array(
					'=TOKEN'                 => $token,
					'=USER.EXTERNAL_AUTH_ID' => 'email',
					'USER.ACTIVE'            => 'Y'
				)
			))->fetch();

			if ($userRelation)
			{
				$context->getResponse()->addCookie(new Main\Web\Cookie('MAIL_AUTH_TOKEN', $token));

				return $userRelation['USER_ID'];
			}
		}

		return false;
	}

	/**
	 * Returns User-Entity unique email and entry point URL
	 *
	 * @param string $siteId Site ID.
	 * @param int $userId User ID.
	 * @param string $entityType Entity type ID.
	 * @param int $entityId Entity ID.
	 * @param string $entityLink Entity URL.
	 * @param string $backurl Back URL.
	 * @return array|false
	 */
	
	/**
	* <p>Метод возвращает уникальный email связки Пользователь-Сущность и URL точки входа. Метод статический.</p>
	*
	*
	* @param string $siteId  Идентификатор сайта.
	*
	* @param integer $userId  Идентификатор пользователя.
	*
	* @param string $entityType  Идентификатор типа сущности.
	*
	* @param integer $entityId  Идентификатор сущности.
	*
	* @param string $entityLink = null URL сущности.
	*
	* @param string $backurl = null Предыдущий URL.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/getreplyto.php
	* @author Bitrix
	*/
	public static function getReplyTo($siteId, $userId, $entityType, $entityId, $entityLink = null, $backurl = null)
	{
		$filter = array(
			'=SITE_ID'     => $siteId,
			'=USER_ID'     => $userId,
			'=ENTITY_TYPE' => $entityType,
			'=ENTITY_ID'   => $entityId
		);
		$userRelation = UserRelationsTable::getList(array('filter' => $filter))->fetch();

		if (empty($userRelation))
		{
			$filter['=SITE_ID'] = null;
			$userRelation = UserRelationsTable::getList(array('filter' => $filter))->fetch();
		}

		if (empty($userRelation))
		{
			if (empty($entityLink))
				return false;

			$userRelation = array(
				'SITE_ID'     => $siteId,
				'TOKEN'       => base_convert(md5(time().Main\Security\Random::getBytes(6)), 16, 36),
				'USER_ID'     => $userId,
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID'   => $entityId,
				'ENTITY_LINK' => $entityLink,
				'BACKURL'     => $backurl
			);

			if (!UserRelationsTable::add($userRelation)->isSuccess())
				return false;
		}

		$site    = Main\SiteTable::getByPrimary($siteId)->fetch();
		$context = Main\Application::getInstance()->getContext();

		$scheme = $context->getRequest()->isHttps() ? 'https' : 'http';
		$domain = $site['SERVER_NAME'] ?: \COption::getOptionString('main', 'server_name', '');

		if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
		{
			$domain = $matches['domain'];
			$port   = $matches['port'];
		}
		else
		{
			$port = $context->getServer()->getServerPort();
		}

		$port = in_array($port, array(80, 443)) ? '' : ':'.$port;
		$path = ltrim(trim($site['DIR'], '/') . '/pub/entry.php', '/');

		$replyTo = sprintf('rpl%s@%s', $userRelation['TOKEN'], $domain);
		$backUrl = sprintf('%s://%s%s/%s#%s', $scheme, $domain, $port, $path, $userRelation['TOKEN']);

		return array($replyTo, $backUrl);
	}

	/**
	 * Returns Site-User-Entity unique email
	 *
	 * @param string $siteId Site ID.
	 * @param int $userId User ID.
	 * @param string $entityType Entity type ID.
	 * @return array|false
	 */
	
	/**
	* <p>Метод возвращает уникальный email для связки Сайт-Пользователь-Сущность. Метод статический.</p>
	*
	*
	* @param string $siteId  Идентификатор сайта.
	*
	* @param integer $userId  Идентификатор пользователя.
	*
	* @param string $entityType  Идентификатор типа сущности.
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/getforwardto.php
	* @author Bitrix
	*/
	public static function getForwardTo($siteId, $userId, $entityType)
	{
		$cache = new \CPHPCache();

		if ($cache->initCache(365*24*3600, sprintf('forward_%s_%s_%s', $siteId, $userId, $entityType), '/mail'))
		{
			$forwardTo = $cache->getVars();
		}
		else
		{
			$userRelation = UserRelationsTable::getList(array(
				'filter' => array(
					'=SITE_ID'     => $siteId,
					'=USER_ID'     => $userId,
					'=ENTITY_TYPE' => $entityType,
					'=ENTITY_ID'   => null
				)
			))->fetch();

			if (empty($userRelation))
			{
				$userRelation = array(
					'SITE_ID'     => $siteId,
					'TOKEN'       => base_convert(md5(time().Main\Security\Random::getBytes(6)), 16, 36),
					'USER_ID'     => $userId,
					'ENTITY_TYPE' => $entityType
				);

				if (!UserRelationsTable::add($userRelation)->isSuccess())
					return false;

				// for dav addressbook modification label
				$user = new \CUser;
				$user->update($userId, array());
			}

			$site   = Main\SiteTable::getByPrimary($siteId)->fetch();
			$domain = $site['SERVER_NAME'] ?: \COption::getOptionString('main', 'server_name', '');

			if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domain, $matches))
				$domain = $matches['domain'];

			$forwardTo = sprintf('fwd%s@%s', $userRelation['TOKEN'], $domain);

			$cache->startDataCache();
			$cache->endDataCache($forwardTo);
		}

		return array($forwardTo);
	}

	/**
	 * Sends email related events
	 *
	 * @param string $to Recipient email.
	 * @param array $message Message.
	 * @param string &$error Error.
	 * @return bool
	 */
	
	/**
	* <p>Метод посылает события, связанные с электронной почтой. Метод статический.</p>
	*
	*
	* @param string $to  Email получателя.
	*
	* @param array $message  Сообщение.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/onemailreceived.php
	* @author Bitrix
	*/
	public static function onEmailReceived($to, $message, &$error)
	{
		if (!preg_match('/^(?<type>rpl|fwd)(?<token>[a-z0-9]+)@(?<domain>.+)/i', $to, $matches))
		{
			$error = sprintf('Invalid recipient (%s)', $to);
			return false;
		}

		$type  = $matches['type'];
		$token = $matches['token'];

		$userRelation = UserRelationsTable::getByPrimary($token)->fetch();

		if (!$userRelation)
		{
			$error = sprintf('Unknown recipient (%s)', $to);
			return false;
		}

		$message['secret'] = $token;

		switch ($type)
		{
			case 'rpl':
				$eventId = sprintf('onReplyReceived%s', $userRelation['ENTITY_TYPE']);
				$content = Message::parseReply($message);
				break;
			case 'fwd':
				$eventId = sprintf('onForwardReceived%s', $userRelation['ENTITY_TYPE']);
				$content = Message::parseForward($message);
				break;
		}

		if (empty($content) && empty($message['files']))
		{
			$error = sprintf('Empty message (rcpt: %s)', $to);
			return false;
		}

		$event = new Main\Event(
			'mail', $eventId,
			array(
				'site_id'     => $userRelation['SITE_ID'],
				'entity_id'   => $userRelation['ENTITY_ID'],
				'from'        => $userRelation['USER_ID'],
				'subject'     => $message['subject'],
				'content'     => $content,
				'attachments' => $message['files']
			)
		);
		$event->send();

		return $event->getResults();
	}

	/**
	 * Returns email users group
	 *
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает группу пользователей электронной почты. Метод статический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/mail/user/getmailusergroup.php
	* @author Bitrix
	*/
	public static function getMailUserGroup()
	{
		$res = array();
		$mailInvitedGroup = Main\Config\Option::get("mail", "mail_invited_group", false);
		if ($mailInvitedGroup)
		{
			$res[] = intval($mailInvitedGroup);
		}
		return $res;
	}

	public static function getDefaultEmailFrom($serverName = false)
	{
		if (defined("BX24_HOST_NAME"))
		{
			if(preg_match("/\\.bitrix24\\.([a-z]+|com\\.br)$/i", BX24_HOST_NAME))
			{
				$domain = BX24_HOST_NAME;
			}
			else
			{
				$domain = str_replace(".", "-", BX24_HOST_NAME).".bitrix24.com";
			}
		}
		else
		{
			$domain = Main\Config\Option::get('main', 'server_name', $GLOBALS["SERVER_NAME"]);
			$domain = ($serverName ?: $domain);
		}

		$defaultEmailFrom = "info@".$domain;

		return $defaultEmailFrom;
	}

	public static function getUserData($userList, $nameTemplate)
	{
		$result = array();

		if (
			!is_array($userList)
			|| empty($userList)
		)
		{
			return $result;
		}

		$filter = array(
			"ID" => $userList,
			"ACTIVE" => "Y",
			"=EXTERNAL_AUTH_ID" => 'email'
		);

		if (
			\IsModuleInstalled('intranet')
			|| Main\Config\Option::get("main", "new_user_registration_email_confirmation", "N") == "Y"
		)
		{
			$filter["CONFIRM_CODE"] = false;
		}

		$res = \Bitrix\Main\UserTable::getList(array(
			'order' => array(),
			'filter' => $filter,
			'select' => array("ID", "EMAIL", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN")
		));

		while ($user = $res->fetch())
		{
			$result[$user["ID"]] = array(
				"NAME_FORMATTED" => (
					!empty($user["NAME"])
					|| !empty($user["LAST_NAME"])
						? \CUser::formatName($nameTemplate, $user)
						: ''
				),
				"EMAIL" => $user["EMAIL"]
			);
		}

		return $result;
	}
}
