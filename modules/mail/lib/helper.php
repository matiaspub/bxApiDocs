<?php

namespace Bitrix\Mail;

use Bitrix\Main;

class Helper
{

	public static function syncMailboxAgent($id)
	{
		$result = self::syncMailbox($id, $error);

		if ($result === false)
			return '';

		return sprintf('Bitrix\Mail\Helper::syncMailboxAgent(%u);', $id);
	}

	public static function syncMailbox($id, &$error)
	{
		global $DB;

		$error = null;

		$id = (int) $id;

		$mailbox = MailboxTable::getList(array(
			'filter' => array('ID' => $id, 'ACTIVE' => 'Y'),
			'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET')
		))->fetch();

		if (empty($mailbox))
		{
			$error = 'no mailbox';
			return false;
		}

		if (!in_array($mailbox['SERVER_TYPE'], array('imap', 'controller', 'domain', 'crdomain')))
		{
			$error = 'unsupported mailbox type';
			return false;
		}

		if ($mailbox['USER_ID'])
			\CUserOptions::setOption('global', 'last_mail_sync_'.$mailbox['LID'], time(), false, $mailbox['USER_ID']);

		if ($mailbox['SYNC_LOCK'] > time()-600)
			return;

		if (in_array($mailbox['SERVER_TYPE'], array('controller', 'crdomain')))
		{
			// @TODO: request controller
			$result = \CMailDomain2::getImapData();

			$mailbox['SERVER']  = $result['server'];
			$mailbox['PORT']    = $result['port'];
			$mailbox['USE_TLS'] = $result['secure'];
		}
		elseif ($mailbox['SERVER_TYPE'] == 'domain')
		{
			$result = \CMailDomain2::getImapData();

			$mailbox['SERVER']  = $result['server'];
			$mailbox['PORT']    = $result['port'];
			$mailbox['USE_TLS'] = $result['secure'];
		}

		$DB->query(sprintf('UPDATE b_mail_mailbox SET SYNC_LOCK = %u WHERE ID = %u', time(), $id));

		$result = static::syncImapMailbox($mailbox, $error);

		$DB->query(sprintf('UPDATE b_mail_mailbox SET SYNC_LOCK = 0 WHERE ID = %u', $id));

		return $result;
	}

	protected static function syncImapMailbox($mailbox, &$error)
	{
		$error = null;

		if (empty($mailbox['OPTIONS']['imap']) || !is_array($mailbox['OPTIONS']['imap']))
			return false;

		$imapOptions = $mailbox['OPTIONS']['imap'];
		if (empty($imapOptions['income']) || !is_array($imapOptions['income']))
			return false;

		$client = new Imap(
			$mailbox['SERVER'], $mailbox['PORT'],
			$mailbox['USE_TLS'] == 'Y' || $mailbox['USE_TLS'] == 'S',
			$mailbox['USE_TLS'] == 'Y',
			$mailbox['LOGIN'], $mailbox['PASSWORD'],
			$mailbox['LANG_CHARSET']
		);

		if (!$client->singin($error))
			return $client->getState() ? false : null;

		$localList = array();
		$localSeen = array();
		$res = MailMessageUidTable::getList(array(
			'filter' => array('MAILBOX_ID' => $mailbox['ID']),
			'select' => array('ID', 'HASH' => 'HEADER_MD5', 'IS_SEEN')
		));
		while ($item = $res->fetch())
		{
			$localList[$item['ID']]   = $item['HASH'];
			$localSeen[$item['HASH']] = $item['IS_SEEN'];
		}

		$obsoleteList = $localList;
		$modifiedList = array();

		// @TODO: blacklist entity
		$blacklist = array(
			'domain' => array(),
			'email'  => array(),
		);
		foreach ((array) $mailbox['OPTIONS']['blacklist'] as $item)
		{
			if (strpos($item, '@') === 0)
				$blacklist['domain'][] = $item;
			else
				$blacklist['email'][] = $item;
		}

		$domains = array();
		$defaultDomain = \COption::getOptionString('main', 'server_name', '');
		$res = Main\SiteTable::getList(array('select' => array('LID', 'SERVER_NAME')));
		while ($site = $res->fetch())
		{
			$domains[$site['LID']] = $site['SERVER_NAME'] ?: $defaultDomain;

			if (preg_match('/^(?<domain>.+):(?<port>\d+)$/', $domains[$site['LID']], $matches))
				$domains[$site['LID']] = $matches['domain'];
		}

		$res = UserRelationsTable::getList(array('filter' => array('=USER_ID' => $mailbox['USER_ID'], '=ENTITY_ID' => null)));
		while ($relation = $res->fetch())
			$blacklist['email'][] = sprintf('fwd%s@%s', $relation['TOKEN'], $domains[$relation['SITE_ID']]);

		$blacklist['domain'] = array_map('strtolower', $blacklist['domain']);
		$blacklist['email']  = array_map('strtolower', $blacklist['email']);

		$session = md5(uniqid(''));

		foreach (array_merge($imapOptions['income'], $imapOptions['outcome']) as $name)
		{
			$list = $client->listMessages($name, $uidtoken, $error);

			if ($list === false) // an error occurred
			{
				$obsoleteList = array();
				continue;
			}

			if (empty($list))
				continue;

			foreach ($list as $item)
			{
				$skip = false;

				$item['seen'] = (bool) preg_match('/ ( ^ | \x20 ) \x5c ( Seen ) ( \x20 | $ ) /ix', $item['flags']);

				if (!is_null($item['uid']))
				{
					$item['uid'] = md5(sprintf('%s:%u:%u', $name, $uidtoken, $item['uid']));

					unset($obsoleteList[$item['uid']]);
					if (array_key_exists($item['uid'], $localList))
					{
						$item['hash'] = $localList[$item['uid']];

						$skip = true;
					}
				}

				if ($skip === false)
				{
					$header = $client->getMessage($name, $item['id'], 'header', $error);

					if ($header === false) // an error occurred
					{
						$obsoleteList = array();
						$skip = true;
					}
					else
					{
						$item['hash'] = md5(sprintf('%s:%s:%u', trim($header), $item['date'], $item['size']));

						if (is_null($item['uid']))
							$item['uid'] = $item['hash'];

						if ($uid = array_search($item['hash'], $localList))
						{
							unset($obsoleteList[$uid]);
							if ($uid != $item['hash'])
							{
								MailMessageUidTable::update(
									array('ID' => $uid, 'MAILBOX_ID' => $mailbox['ID']),
									array('ID' => $item['uid'])
								);
							}

							$skip = true;
						}
					}
				}

				if ($skip === true)
				{
					if ($item['seen'] != in_array($localSeen[$item['hash']], array('Y', 'S')))
					{
						if (in_array($localSeen[$item['hash']], array('S', 'U')))
						{
							$item['seen'] = $localSeen[$item['hash']] == 'S';

							$result = $client->updateMessageFlags($name, $item['id'], array(
								'\Seen' => $item['seen'],
							), $err);

							if ($result !== false)
							{
								MailMessageUidTable::update(
									array('ID' => $item['uid'], 'MAILBOX_ID' => $mailbox['ID']),
									array('IS_SEEN' => $item['seen'] ? 'Y' : 'N')
								);
							}
						}
						else
						{
							$modifiedList[$item['uid']] = array(
								'hash' => $item['hash'],
								'seen' => $item['seen'],
							);
						}
					}

					continue;
				}

				MailMessageUidTable::add(array(
					'ID'          => $item['uid'],
					'MAILBOX_ID'  => $mailbox['ID'],
					'HEADER_MD5'  => $item['hash'],
					'IS_SEEN'     => $item['seen'] ? 'Y' : 'N',
					'SESSION_ID'  => $session,
					'DATE_INSERT' => new Main\Type\DateTime(),
					'MESSAGE_ID'  => 0,
				));
				$localList[$item['uid']] = $item['hash'];

				$item['outcome'] = in_array($name, $imapOptions['outcome']);

				$parsedHeader = \CMailMessage::parseHeader($header, $mailbox['LANG_CHARSET']);

				$parsedFrom = array_unique(array_map('strtolower', array_filter(array_merge(
					\CMailUtil::extractAllMailAddresses($parsedHeader->getHeader('FROM')),
					\CMailUtil::extractAllMailAddresses($parsedHeader->getHeader('REPLY-TO'))
				), 'trim')));
				$parsedTo = array_unique(array_map('strtolower', array_filter(array_merge(
					\CMailUtil::extractAllMailAddresses($parsedHeader->getHeader('TO')),
					\CMailUtil::extractAllMailAddresses($parsedHeader->getHeader('CC')),
					\CMailUtil::extractAllMailAddresses($parsedHeader->getHeader('BCC')),
					\CMailUtil::extractAllMailAddresses($parsedHeader->getHeader('X-Original-Rcpt-to'))
				), 'trim')));

				if (!empty($blacklist['email']))
				{
					if (!$item['outcome'] && array_intersect($parsedFrom, $blacklist['email']))
						continue;

					if ($item['outcome'] && !array_diff($parsedTo, $blacklist['email']))
						continue;
				}

				if (!empty($blacklist['domain']))
				{
					$skip = false;

					$haystack = $item['outcome'] ? $parsedTo : $parsedFrom;
					foreach ($haystack as $email)
					{
						$domain = substr($email, strrpos($email, '@'));
						if ($domain != $email)
						{
							if (in_array($domain, $blacklist['domain']))
							{
								$skip = true;
								if (!$item['outcome'])
									break;
							}
							else
							{
								$skip = false;
								if ($item['outcome'])
									break;
							}
						}
					}

					if ($skip)
						continue;
				}

				if (!empty($mailbox['OPTIONS']['sync_from']))
				{
					$syncFrom = (int) $mailbox['OPTIONS']['sync_from'];
					if (strtotime($item['date']) < $syncFrom)
						continue;
				}

				$body = $client->getMessage($name, $item['id'], null, $error);

				if ($body === false) // an error occurred
					continue;

				if (!preg_match('/\r\n$/', $body))
					$body .= "\r\n";

				$messageId = \CMailMessage::addMessage(
					$mailbox['ID'], $body,
					$mailbox['CHARSET'] ?: $mailbox['LANG_CHARSET'],
					array(
						'outcome' => $item['outcome'],
						'seen'    => $item['seen'],
						'hash'    => $item['hash'],
					)
				);
				if ($messageId > 0)
				{
					MailMessageUidTable::update(
						array('ID' => $item['uid'], 'MAILBOX_ID' => $mailbox['ID']),
						array('MESSAGE_ID' => $messageId)
					);
				}
			}
		}

		if (!empty($obsoleteList))
		{
			foreach ($obsoleteList as $msgUid => $dummy)
			{
				MailMessageUidTable::delete(array(
					'ID' => $msgUid, 'MAILBOX_ID' => $mailbox['ID']
				));
			}

			foreach ($obsoleteList as $msgHash)
			{
				$event = new Main\Event(
					'mail', 'OnMessageObsolete',
					array(
						'user' => $mailbox['USER_ID'],
						'hash' => $msgHash,
					)
				);
				$event->send();
			}
		}

		if (!empty($modifiedList))
		{
			foreach ($modifiedList as $msgUid => $msgData)
			{
				MailMessageUidTable::update(
					array('ID' => $msgUid, 'MAILBOX_ID' => $mailbox['ID']),
					array('IS_SEEN' => $msgData['seen'] ? 'Y' : 'N')
				);
			}

			foreach ($modifiedList as $msgData)
			{
				$event = new Main\Event(
					'mail', 'OnMessageModified',
					array(
						'user' => $mailbox['USER_ID'],
						'hash' => $msgData['hash'],
						'seen' => $msgData['seen'],
					)
				);
				$event->send();
			}
		}

		return true;
	}

	public static function listImapDirs($mailbox, &$error)
	{
		$error = null;

		$client = new Imap(
			$mailbox['SERVER'], $mailbox['PORT'],
			$mailbox['USE_TLS'] == 'Y' || $mailbox['USE_TLS'] == 'S',
			$mailbox['USE_TLS'] == 'Y',
			$mailbox['LOGIN'], $mailbox['PASSWORD'],
			$mailbox['LANG_CHARSET'] ?: $mailbox['CHARSET']
		);

		$list = $client->listMailboxes('*', $error);

		if ($list === false)
			return false;

		$flat = function($list, $prefix = '', $level = 0) use (&$flat)
		{
			$k = count($list);
			for ($i = 0; $i < $k; $i++)
			{
				$item = $list[$i];

				$list[$i] = array(
					'level' => $level,
					'name'  => preg_replace(sprintf('/^%s/', preg_quote($prefix, '/')), '', $item['name']),
					'path'  => $item['name']
				);

				if (preg_match('/ ( ^ | \x20 ) \x5c Noselect ( \x20 | $ ) /ix', $item['flags']))
				{
					$list[$i]['disabled'] = true;
				}
				else
				{
					if (strtolower($item['name']) == 'inbox')
						$list[$i]['income'] = true;

					if (preg_match('/ ( ^ | \x20 ) \x5c Sent ( \x20 | $ ) /ix', $item['flags']))
						$list[$i]['outcome'] = true;
				}

				if (!empty($item['children']))
				{
					$children = $flat($item['children'], $item['name'].$item['delim'], $level+1);

					array_splice($list, $i+1, 0, $children);

					$i += count($children);
					$k += count($children);
				}
			}

			return $list;
		};

		return $flat($list);
	}

	public static function getImapUnseen($mailbox, $dir = 'inbox', &$error)
	{
		$error = null;

		$client = new Imap(
			$mailbox['SERVER'], $mailbox['PORT'],
			$mailbox['USE_TLS'] == 'Y' || $mailbox['USE_TLS'] == 'S',
			$mailbox['USE_TLS'] == 'Y',
			$mailbox['LOGIN'], $mailbox['PASSWORD'],
			$mailbox['LANG_CHARSET'] ?: $mailbox['CHARSET']
		);

		return $client->getUnseen($dir, $error);
	}

	public static function addImapMessage($id, $data, &$error)
	{
		$error = null;

		$id = (int) (is_array($id) ? $id['ID'] : $id);

		$mailbox = MailboxTable::getList(array(
			'filter' => array('ID' => $id, 'ACTIVE' => 'Y'),
			'select' => array('*', 'LANG_CHARSET' => 'SITE.CULTURE.CHARSET')
		))->fetch();

		if (empty($mailbox))
			return;

		if (!in_array($mailbox['SERVER_TYPE'], array('imap', 'controller', 'domain', 'crdomain')))
			return;

		if (in_array($mailbox['SERVER_TYPE'], array('controller', 'crdomain')))
		{
			// @TODO: request controller
			$result = \CMailDomain2::getImapData();

			$mailbox['SERVER']  = $result['server'];
			$mailbox['PORT']    = $result['port'];
			$mailbox['USE_TLS'] = $result['secure'];
		}
		elseif ($mailbox['SERVER_TYPE'] == 'domain')
		{
			$result = \CMailDomain2::getImapData();

			$mailbox['SERVER']  = $result['server'];
			$mailbox['PORT']    = $result['port'];
			$mailbox['USE_TLS'] = $result['secure'];
		}

		$client = new Imap(
			$mailbox['SERVER'], $mailbox['PORT'],
			$mailbox['USE_TLS'] == 'Y' || $mailbox['USE_TLS'] == 'S',
			$mailbox['USE_TLS'] == 'Y',
			$mailbox['LOGIN'], $mailbox['PASSWORD'],
			$mailbox['LANG_CHARSET'] ?: $mailbox['CHARSET']
		);

		$imapOptions = $mailbox['OPTIONS']['imap'];
		if (empty($imapOptions['outcome']) || !is_array($imapOptions['outcome']))
			return;

		return $client->addMessage(reset($imapOptions['outcome']), $data, $error);
	}

	public static function updateImapMessage($userId, $hash, $data, &$error)
	{
		$error = null;

		$msgUid = MailMessageUidTable::getList(array(
			'select' => array('ID', 'MAILBOX_ID', 'IS_SEEN'),
			'filter' => array(
				'HEADER_MD5'      => $hash,
				'MAILBOX.USER_ID' => $userId
			),
		))->fetch();

		if ($msgUid && in_array($msgUid['IS_SEEN'], array('Y', 'S')) != $data['seen'])
		{
			MailMessageUidTable::update(
				array('ID' => $msgUid['ID'], 'MAILBOX_ID' => $msgUid['MAILBOX_ID']),
				array('IS_SEEN' => $data['seen'] ? 'S' : 'U')
			);
		}
	}

}


class DummyMail extends Main\Mail\Mail
{

	public function initSettings()
	{
		parent::initSettings();

		$this->settingServerMsSmtp = false;
		$this->settingMailFillToEmail = false;
		$this->settingMailConvertMailHeader = true;
	}

	public static function getMailEol()
	{
		return "\r\n";
	}

	public function __toString()
	{
		return sprintf("%s\r\n\r\n%s", $this->getHeaders(), $this->getBody());
	}

}
