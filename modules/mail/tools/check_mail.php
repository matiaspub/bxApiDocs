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
$acc = CMailbox::GetList(
	array(
		'TIMESTAMP_X' => 'DESC'
	),
	array(
		'LID'         => $siteId,
		'ACTIVE'      => 'Y',
		'SERVER_TYPE' => 'imap',
		'USER_ID'     => $userId
	)
)->Fetch();
if (!empty($acc))
{
	$unseen = CMailUtil::CheckImapMailbox(
		$acc['SERVER'], $acc['PORT'], $acc['USE_TLS'] == 'Y',
		$acc['LOGIN'], $acc['PASSWORD'],
		$error, 30
	);

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
