<?php

// define('NO_KEEP_STATISTIC', 'Y');
// define('NO_AGENT_STATISTIC','Y');
// define('NO_AGENT_CHECK', true);
// define('DisableEventsCheck', true);

// define('NOT_CHECK_PERMISSIONS', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!is_object($USER) || !$USER->IsAuthorized()) return;

$userId = $USER->GetID();

session_write_close();

CModule::IncludeModule('mail');

$siteId = isset($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : SITE_ID;

$error = false;

$dbAcc = CMailbox::GetList(
	array(
		'TIMESTAMP_X' => 'DESC'
	),
	array(
		'LID'         => SITE_ID,
		'ACTIVE'      => 'Y',
		'USER_ID'     => $USER->GetID()
	)
);
while (($acc = $dbAcc->fetch()) !== false && !in_array($acc['SERVER_TYPE'], array('imap', 'controller', 'domain', 'crdomain')));

if (!empty($acc))
{
	switch ($acc['SERVER_TYPE'])
	{
		case 'imap':
			$unseen = CMailUtil::CheckImapMailbox(
				$acc['SERVER'], $acc['PORT'], $acc['USE_TLS'] == 'Y',
				$acc['LOGIN'], $acc['PASSWORD'],
				$error, 30
			);
			break;
		case 'controller':
			list($acc['login'], $acc['domain']) = explode('@', $acc['LOGIN'], 2);
			$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMailbox', array(
				'DOMAIN' => $acc['domain'],
				'NAME'   => $acc['login']
			));
			if (isset($crCheckMailbox['result']))
			{
				$unseen = intval($crCheckMailbox['result']);
			}
			else
			{
				$unseen = -1;
				$error  = empty($crCheckMailbox['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crCheckMailbox['error']);
			}
			break;
		case 'crdomain':
			list($acc['login'], $acc['domain']) = explode('@', $acc['LOGIN'], 2);
			$crCheckMailbox = CControllerClient::ExecuteEvent('OnMailControllerCheckMemberMailbox', array(
				'DOMAIN' => $acc['domain'],
				'NAME'   => $acc['login']
			));
			if (isset($crCheckMailbox['result']))
			{
				$unseen = intval($crCheckMailbox['result']);
			}
			else
			{
				$unseen = -1;
				$error  = empty($crCheckMailbox['error'])
					? GetMessage('INTR_MAIL_CONTROLLER_INVALID')
					: CMail::getErrorMessage($crCheckMailbox['error']);
			}
			break;
		case 'domain':
			$service = \Bitrix\Mail\MailServicesTable::getRowById($acc['SERVICE_ID']);
			list($acc['login'], $acc['domain']) = explode('@', $acc['LOGIN'], 2);
			$result = CMailDomain2::getUnreadMessagesCount(
				$service['TOKEN'],
				$acc['domain'], $acc['login'],
				$error
			);

			if (is_null($result))
			{
				$unseen = -1;
				$error = CMail::getErrorMessage($error);
			}
			else
			{
				$unseen = intval($result);
			}
			break;
	}

	CUserCounter::Set($userId, 'mail_unseen', $unseen, $siteId);

	CUserOptions::SetOption('global', 'last_mail_check_'.$siteId, time(), false, $userId);
	CUserOptions::SetOption('global', 'last_mail_check_success_'.$siteId, $unseen >= 0, false, $userId);
}
else
{
	$unseen = 0;

	CUserOptions::SetOption('global', 'last_mail_check_'.$siteId, -1, false, $userId);
}

header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo json_encode(array(
	'result'     => $error === false ? 'ok' : 'error',
	'unseen'     => $unseen,
	'last_check' => CUserOptions::GetOption('global', 'last_mail_check_'.$siteId, false, $userId),
	'error'      => $error
));
