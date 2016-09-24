<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\Mail;

Loc::loadMessages(__FILE__);

class PostingManager
{
	const SEND_RESULT_ERROR = false;
	const SEND_RESULT_SENT = true;
	const SEND_RESULT_CONTINUE = 'CONTINUE';

	protected static $checkStatusStep = 20;
	protected static $emailSentPerIteration = 0;
	protected static $currentMailingChainFields = null;

	/**
	 * Handler of read event
	 *
	 * @param array $data
	 * @return array
	 */
	public static function onMailEventMailRead(array $data)
	{
		$id = intval($data['RECIPIENT_ID']);
		if ($id > 0)
			static::read($id);

		return $data;
	}

	/**
	 * Handler of click event
	 *
	 * @param array $data
	 * @return array
	 */
	public static function onMailEventMailClick(array $data)
	{
		$id = intval($data['RECIPIENT_ID']);
		$url = $data['URL'];
		if ($id > 0 && strlen($url) > 0)
			static::click($id, $url);

		return $data;
	}

	/**
	 * Do read actions
	 *
	 * @param $recipientId
	 * @return void
	 */
	public static function read($recipientId)
	{
		$postingContactPrimary = array('ID' => $recipientId);
		$recipient = PostingRecipientTable::getRowById($postingContactPrimary);
		if ($recipient && $recipient['ID'])
		{
			PostingReadTable::add(array(
				'POSTING_ID' => $recipient['POSTING_ID'],
				'RECIPIENT_ID' => $recipient['ID'],
			));
		}
	}

	/**
	 * Do click actions
	 *
	 * @param $recipientId
	 * @param $url
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function click($recipientId, $url)
	{

		$postingContactPrimary = array('ID' => $recipientId);
		$recipient = PostingRecipientTable::getRowById($postingContactPrimary);
		if ($recipient && $recipient['ID'])
		{
			$read = PostingReadTable::getRowById(array(
				'POSTING_ID' => $recipient['POSTING_ID'],
				'RECIPIENT_ID' => $recipient['ID']
			));
			if ($read === null)
			{
				static::read($recipientId);
			}

			$postingDb = PostingTable::getList(array(
				'select' => array('ID'),
				'filter' => array('ID' => $recipient['POSTING_ID']),
			));
			if ($postingDb->fetch())
			{
				$uri = new \Bitrix\Main\Web\Uri($url);
				$fixedUrl = $uri->deleteParams(array('bx_sender_conversion_id'))->getLocator();
				$addClickDb = PostingClickTable::add(array(
					'POSTING_ID' => $recipient['POSTING_ID'],
					'RECIPIENT_ID' => $recipient['ID'],
					'URL' => $fixedUrl
				));
				if($addClickDb->isSuccess())
				{
					// send event
					$eventData = array(
						'URL' => $url,
						'URL_FIXED' => $fixedUrl,
						'CLICK_ID' => $addClickDb->getId(),
						'RECIPIENT' => $recipient
					);
					$event = new Event('sender', 'OnAfterRecipientClick', array($eventData));
					$event->send();
				}
			}
		}
	}


	/**
	 * Get chain list for resending
	 *
	 * @param $mailingId
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getChainReSend($mailingId)
	{
		$result = array();
		$mailChainDb = MailingChainTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'MAILING.ID' => $mailingId,
				'MAILING.ACTIVE' => 'Y',
				'REITERATE' => 'N',
				'MAILING_CHAIN.STATUS' => MailingChainTable::STATUS_END,
			)
		));
		while($mailChain = $mailChainDb->fetch())
		{
			$result[] = $mailChain['ID'];
		}

		return (empty($result) ? null : $result);
	}

	/**
	 *
	 * @param $mailingChainId
	 * @param array $params
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\Exception
	 */
	protected static function sendInternal($mailingChainId, array $params)
	{
		// event before sending
		$eventSendParams = $params;
		$eventSendParams['MAILING_CHAIN_ID'] = $mailingChainId;
		$event = new Event('sender', 'OnBeforePostingSendRecipient', array($eventSendParams));
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			if($eventResult->getType() == \Bitrix\Main\EventResult::ERROR)
			{
				return PostingRecipientTable::SEND_RESULT_ERROR;
			}

			if(is_array($eventResult->getParameters()))
			{
				$eventSendParams = array_merge($eventSendParams, $eventResult->getParameters());
			}
		}
		unset($eventSendParams['MAILING_CHAIN_ID']);
		$params = $eventSendParams;

		// prepare common params
		if(static::$currentMailingChainFields !== null)
		{
			if(static::$currentMailingChainFields['ID'] != $mailingChainId)
				static::$currentMailingChainFields = null;
		}

		if(static::$currentMailingChainFields === null)
		{
			$mailingChainDb = MailingChainTable::getList(array(
				'select' => array('*', 'SITE_ID' => 'MAILING.SITE_ID'),
				'filter' => array('=ID' => $mailingChainId)
			));
			if(!($mailingChain = $mailingChainDb->fetch()))
			{
				return PostingRecipientTable::SEND_RESULT_ERROR;
			}


			$charset = false;
			$siteDb = \Bitrix\Main\SiteTable::getList(array(
				'select'=>array('SERVER_NAME', 'NAME', 'CULTURE_CHARSET'=>'CULTURE.CHARSET'),
				'filter' => array('=LID' => $mailingChain['SITE_ID'])
			));
			if($site = $siteDb->fetch())
			{
				$charset = $site['CULTURE_CHARSET'];
				$serverName = $site['SERVER_NAME'];
			}
			else
			{
				throw new \Bitrix\Main\DB\Exception(Loc::getMessage('SENDER_POSTING_MANAGER_ERR_SITE', array('#SITE_ID#' => $mailingChain['SITE_ID'])));
			}

			if(!$charset)
			{
				throw new \Bitrix\Main\DB\Exception(Loc::getMessage('SENDER_POSTING_MANAGER_ERR_CHARSET', array('#SITE_ID#' => "[".$mailingChain['SITE_ID']."]".$site['NAME'])));
			}

			$attachmentList = array();
			$attachmentDb = \Bitrix\Sender\MailingAttachmentTable::getList(array(
				'select' => array('FILE_ID'),
				'filter' => array('CHAIN_ID' => $mailingChainId)
			));
			while($attachment = $attachmentDb->fetch())
			{
				$attachmentList[] = $attachment['FILE_ID'];
			}


			static::$currentMailingChainFields = array();

			static::$currentMailingChainFields['EVENT'] = array(
				'FILE' => $attachmentList
			);
			static::$currentMailingChainFields['ID'] = $mailingChain['ID'];

			// create final mail-text due to filling template by blocks
			static::$currentMailingChainFields['IS_MESSAGE_WITH_TEMPLATE'] = false;
			if($mailingChain['TEMPLATE_TYPE'] && $mailingChain['TEMPLATE_ID'])
			{
				$chainTemplate = \Bitrix\Sender\Preset\Template::getById($mailingChain['TEMPLATE_TYPE'], $mailingChain['TEMPLATE_ID']);
				if($chainTemplate && $chainTemplate['HTML'])
				{
					$document = new \Bitrix\Main\Web\DOM\Document;
					$document->loadHTML($chainTemplate['HTML']);
					\Bitrix\Main\Loader::includeModule('fileman');
					if(\Bitrix\Fileman\Block\Editor::fillDocumentBySliceContent($document, $mailingChain['MESSAGE']))
					{
						\Bitrix\Main\Web\DOM\StyleInliner::inlineDocument($document);
						$mailingChain['MESSAGE'] = $document->saveHTML();

						static::$currentMailingChainFields['IS_MESSAGE_WITH_TEMPLATE'] = true;
					}
					else
					{
						unset($document);
					}
				}
			}


			static::$currentMailingChainFields['MESSAGE'] = array(
				'BODY_TYPE' => 'html',
				'EMAIL_FROM' => $mailingChain['EMAIL_FROM'],
				'EMAIL_TO' => '#EMAIL_TO#',
				'PRIORITY' => $mailingChain['PRIORITY'],
				'SUBJECT' => $mailingChain['SUBJECT'],
				'MESSAGE' => $mailingChain['MESSAGE'],
				'MESSAGE_PHP' => \Bitrix\Main\Mail\Internal\EventMessageTable::replaceTemplateToPhp($mailingChain['MESSAGE']),
			);
			static::$currentMailingChainFields['SITE'] = array($mailingChain['SITE_ID']);
			static::$currentMailingChainFields['CHARSET'] = $charset;
			static::$currentMailingChainFields['SERVER_NAME'] = $serverName;
			static::$currentMailingChainFields['LINK_PROTOCOL'] = \Bitrix\Main\Config\Option::get("sender", "link_protocol", 'http');

			static::$currentMailingChainFields['LINK_PARAMS'] = $mailingChain['LINK_PARAMS'];
		}


		$trackClick = isset($params['TRACK_CLICK']) ? $params['TRACK_CLICK'] : null;
		if($trackClick && static::$currentMailingChainFields['LINK_PARAMS'])
		{
			if(!is_array($trackClick['URL_PARAMS']))
			{
				$trackClick['URL_PARAMS'] = array();
			}

			parse_str(static::$currentMailingChainFields['LINK_PARAMS'], $trackClickTmp);
			if(is_array($trackClickTmp))
			{
				$trackClick['URL_PARAMS'] = array_merge($trackClick['URL_PARAMS'], $trackClickTmp);
			}
		}


		// prepare params for send email
		$messageParams = array(
			'EVENT' => static::$currentMailingChainFields['EVENT'],
			'FIELDS' => $params['FIELDS'],
			'MESSAGE' => static::$currentMailingChainFields['MESSAGE'],
			'SITE' => static::$currentMailingChainFields['SITE'],
			'CHARSET' => static::$currentMailingChainFields['CHARSET'],
		);

		if(!empty($params['FIELDS']['UNSUBSCRIBE_LINK']))
		{
			if(substr($params['FIELDS']['UNSUBSCRIBE_LINK'], 0, 4) !== 'http')
			{
				if(!empty(static::$currentMailingChainFields['SERVER_NAME']))
				{
					$serverName = static::$currentMailingChainFields['SERVER_NAME'];
				}
				else
				{
					$serverName = \Bitrix\Main\Config\Option::get("main", "server_name", $GLOBALS["SERVER_NAME"]);
				}

				$linkProtocol = static::$currentMailingChainFields['LINK_PROTOCOL'];
				$params['FIELDS']['UNSUBSCRIBE_LINK'] = $linkProtocol . '://' . $serverName . $params['FIELDS']['UNSUBSCRIBE_LINK'];
			}
		}

		// event on sending
		$eventMessageParams = $messageParams;
		$eventMessageParams['MAILING_CHAIN_ID'] = $mailingChainId;
		$eventMessageParams['IS_MESSAGE_WITH_TEMPLATE'] = static::$currentMailingChainFields['IS_MESSAGE_WITH_TEMPLATE'];
		$event = new Event('sender', 'OnPostingSendRecipient', array($eventMessageParams));
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			if($eventResult->getType() == \Bitrix\Main\EventResult::ERROR)
			{
				return PostingRecipientTable::SEND_RESULT_ERROR;
			}

			if(is_array($eventResult->getParameters()))
			{
				$eventMessageParams = array_merge($eventMessageParams, $eventResult->getParameters());
			}
		}
		static::$currentMailingChainFields['IS_MESSAGE_WITH_TEMPLATE'] = $eventMessageParams['IS_MESSAGE_WITH_TEMPLATE'];
		unset($eventMessageParams['IS_MESSAGE_WITH_TEMPLATE']);
		unset($eventMessageParams['MAILING_CHAIN_ID']);
		$messageParams = $eventMessageParams;

		$message = Mail\EventMessageCompiler::createInstance($messageParams);
		$message->compile();

		// add unsubscribe info to header
		$mailHeaders = $message->getMailHeaders();
		if(!empty($params['FIELDS']['UNSUBSCRIBE_LINK']))
		{
			$unsubUrl = $params['FIELDS']['UNSUBSCRIBE_LINK'];
			$mailHeaders['List-Unsubscribe'] = '<'.$unsubUrl.'>';
		}

		$mailBody = null;
		if(static::$currentMailingChainFields['IS_MESSAGE_WITH_TEMPLATE'] && Option::get('sender', 'use_inliner_for_each_template_mail', 'N') == 'Y')
		{
			// inline styles
			$mailBody = \Bitrix\Main\Web\DOM\StyleInliner::inlineHtml($message->getMailBody());
		}

		if(!$mailBody)
		{
			$mailBody = $message->getMailBody();
		}


		// set email params
		$mailParams = array(
			'TO' => $message->getMailTo(),
			'SUBJECT' => $message->getMailSubject(),
			'BODY' => $mailBody,
			'HEADER' => $mailHeaders,
			'CHARSET' => $message->getMailCharset(),
			'CONTENT_TYPE' => $message->getMailContentType(),
			'MESSAGE_ID' => '',
			'ATTACHMENT' => $message->getMailAttachment(),
			'LINK_PROTOCOL' => static::$currentMailingChainFields['LINK_PROTOCOL'],
			'LINK_DOMAIN' => static::$currentMailingChainFields['SERVER_NAME'],
			'TRACK_READ' => (isset($params['TRACK_READ']) ? $params['TRACK_READ'] : null),
			'TRACK_CLICK' => $trackClick
		);

		// event on sending email
		$eventMailParams = $mailParams;
		$eventMailParams['MAILING_CHAIN_ID'] = $mailingChainId;
		$event = new Event('sender', 'OnPostingSendRecipientEmail', array($eventMailParams));
		$event->send();
		foreach ($event->getResults() as $eventResult)
		{
			if($eventResult->getType() == \Bitrix\Main\EventResult::ERROR)
			{
				return PostingRecipientTable::SEND_RESULT_ERROR;
			}

			if(is_array($eventResult->getParameters()))
			{
				$eventMailParams = array_merge($eventMailParams, $eventResult->getParameters());
			}
		}
		unset($eventMailParams['MAILING_CHAIN_ID']);
		$mailParams = $eventMailParams;

		// send mail
		$result = Mail\Mail::send($mailParams);
		if($result)
			return PostingRecipientTable::SEND_RESULT_SUCCESS;
		else
			return PostingRecipientTable::SEND_RESULT_ERROR;
	}

	/**
	 * Send letter by message from posting to address.
	 *
	 * @param $mailingChainId
	 * @param $address
	 * @return bool
	 * @throws \Bitrix\Main\DB\Exception
	 */
	public static function sendToAddress($mailingChainId, $address)
	{
		$recipientEmail = $address;
		$emailParts = explode('@', $recipientEmail);
		$recipientName = $emailParts[0];

		global $USER;

		$mailingChain = MailingChainTable::getRowById(array('ID' => $mailingChainId));
		$sendParams = array(
			'FIELDS' => array(
				'NAME' => $recipientName,
				'EMAIL_TO' => $address,
				'USER_ID' => $USER->GetID(),
				'SENDER_CHAIN_ID' => $mailingChain["ID"],
				'SENDER_CHAIN_CODE' => 'sender_chain_item_' . $mailingChain["ID"],
				'UNSUBSCRIBE_LINK' => Subscription::getLinkUnsub(array(
					'MAILING_ID' => !empty($mailingChain) ? $mailingChain['MAILING_ID'] : 0,
					'EMAIL' => $address,
					'TEST' => 'Y'
				))
			),
			'TRACK_READ' => array(
				'MODULE_ID' => "sender",
				'FIELDS' => array('RECIPIENT_ID' => 0),
			),
			'TRACK_CLICK' => array(
				'MODULE_ID' => "sender",
				'FIELDS' => array('RECIPIENT_ID' => 0),
				'URL_PARAMS' => array('bx_sender_conversion_id' => 0)
			)
		);

		$mailSendResult = static::sendInternal($mailingChainId, $sendParams);


		switch($mailSendResult)
		{
			case PostingRecipientTable::SEND_RESULT_SUCCESS:
				$mailResult = static::SEND_RESULT_SENT;
				break;

			case PostingRecipientTable::SEND_RESULT_ERROR:
			default:
				$mailResult = static::SEND_RESULT_ERROR;
		}

		return $mailResult;
	}

	/**
	 * Send posting.
	 *
	 * @param $id
	 * @param int $timeout
	 * @param int $maxMailCount
	 * @return bool|string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\DB\Exception
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Exception
	 */
	public static function send($id, $timeout=0, $maxMailCount=0)
	{
		$start_time = getmicrotime();
		@set_time_limit(0);

		static::$emailSentPerIteration = 0;

		$postingDb = PostingTable::getList(array(
			'select' => array(
				'ID',
				'STATUS',
				'MAILING_ID',
				'MAILING_CHAIN_ID',
				'MAILING_CHAIN_REITERATE' => 'MAILING_CHAIN.REITERATE',
				'MAILING_CHAIN_IS_TRIGGER' => 'MAILING_CHAIN.IS_TRIGGER',
			),
			'filter' => array(
				'=ID' => $id,
				'=MAILING.ACTIVE' => 'Y',
				'=MAILING_CHAIN.STATUS' => MailingChainTable::STATUS_SEND,
			)
		));
		$postingData = $postingDb->fetch();

		// posting not found
		if(!$postingData)
		{
			return static::SEND_RESULT_ERROR;
		}

		// if posting in new status, then import recipients from groups and set right status for sending
		$isInitGroupRecipients = false;
		$isChangeStatusToPart = false;
		if($postingData["STATUS"] == PostingTable::STATUS_NEW)
		{
			$isInitGroupRecipients = true;
			$isChangeStatusToPart = true;
		}
		if($postingData["STATUS"] != PostingTable::STATUS_PART && $postingData["MAILING_CHAIN_IS_TRIGGER"] == 'Y')
		{
			$isInitGroupRecipients = false;
			$isChangeStatusToPart = true;
		}

		if($isInitGroupRecipients)
			PostingTable::initGroupRecipients($postingData['ID']);
		if($isChangeStatusToPart)
		{
			PostingTable::update(array('ID' => $postingData['ID']), array('STATUS' => PostingTable::STATUS_PART));
			$postingData["STATUS"] = PostingTable::STATUS_PART;
		}


		// posting not in right status
		if($postingData["STATUS"] != PostingTable::STATUS_PART)
		{
			return static::SEND_RESULT_ERROR;
		}

		// lock posting for exclude double parallel sending
		if(static::lockPosting($id) === false)
		{
			throw new \Bitrix\Main\DB\Exception(Loc::getMessage('SENDER_POSTING_MANAGER_ERR_LOCK'));
		}


		$isStopped = false;
		$checkStatusCounter = 0;
		static::$checkStatusStep = intval(Option::get('sender', 'send_check_status_step', static::$checkStatusStep));

		// select all recipients of posting, only not processed
		$recipientDataDb = PostingRecipientTable::getList(array(
			'filter' => array(
				'=POSTING_ID' => $postingData['ID'],
				'=STATUS' => PostingRecipientTable::SEND_RESULT_NONE
			),
			'limit' => $maxMailCount
		));

		while($recipientData = $recipientDataDb->fetch())
		{
			// check pause or stop status
			if(++$checkStatusCounter >= static::$checkStatusStep)
			{
				$checkStatusDb = MailingChainTable::getList(array(
					'select' => array('ID'),
					'filter' => array(
						'=ID' => $postingData["MAILING_CHAIN_ID"],
						'=STATUS' => MailingChainTable::STATUS_SEND
					)
				));
				if(!$checkStatusDb->fetch())
				{
					break;
				}

				$checkStatusCounter = 0;
			}

			// create name from email
			$recipientEmail = $recipientData["EMAIL"];
			if(empty($recipientData["NAME"]))
			{
				$recipientEmailParts = explode('@', $recipientEmail);
				$recipientName = $recipientEmailParts[0];
			}
			else
			{
				$recipientName = $recipientData["NAME"];
			}


			// prepare params for send
			$sendParams = array(
				'FIELDS' => array(
					'EMAIL_TO' => $recipientEmail,
					'NAME' => $recipientName,
					'USER_ID' => $recipientData["USER_ID"],
					'SENDER_CHAIN_ID' => $postingData["MAILING_CHAIN_ID"],
					'SENDER_CHAIN_CODE' => 'sender_chain_item_' . $postingData["MAILING_CHAIN_ID"],
					'UNSUBSCRIBE_LINK' => Subscription::getLinkUnsub(array(
						'MAILING_ID' => $postingData['MAILING_ID'],
						'EMAIL' => $recipientEmail,
						'RECIPIENT_ID' => $recipientData["ID"]
					)),
				),
				'TRACK_READ' => array(
					'MODULE_ID' => "sender",
					'FIELDS' => array('RECIPIENT_ID' => $recipientData["ID"]),
				),
				'TRACK_CLICK' => array(
					'MODULE_ID' => "sender",
					'FIELDS' => array('RECIPIENT_ID' => $recipientData["ID"]),
					'URL_PARAMS' => array('bx_sender_conversion_id' => $recipientData["ID"])
				)
			);

			if(is_array($recipientData['FIELDS']) && count($recipientData) > 0)
				$sendParams['FIELDS'] = $sendParams['FIELDS'] + $recipientData['FIELDS'];

			// set sending result to recipient
			try
			{
				$mailSendResult = static::sendInternal($postingData['MAILING_CHAIN_ID'], $sendParams);
			}
			catch(\Bitrix\Main\Mail\StopException $e)
			{
				$isStopped = true;
				break;
			}

			PostingRecipientTable::update(array('ID' => $recipientData["ID"]), array('STATUS' => $mailSendResult, 'DATE_SENT' => new Type\DateTime()));

			// send event
			$eventData = array(
				'SEND_RESULT' => $mailSendResult == PostingRecipientTable::SEND_RESULT_SUCCESS,
				'RECIPIENT' => $recipientData,
				'POSTING' => $postingData
			);
			$event = new Event('sender', 'OnAfterPostingSendRecipient', array($eventData));
			$event->send();

			// limit executing script by time
			if($timeout > 0 && getmicrotime()-$start_time >= $timeout)
				break;

			// increment sending statistic
			static::$emailSentPerIteration++;
		}


		//set status and delivered and error emails
		$statusList = PostingTable::getRecipientCountByStatus($id);
		if($isStopped)
		{
			$STATUS = PostingTable::STATUS_ABORT;
			$DATE = new Type\DateTime();
		}
		elseif(!array_key_exists(PostingRecipientTable::SEND_RESULT_NONE, $statusList))
		{
			if(array_key_exists(PostingRecipientTable::SEND_RESULT_ERROR, $statusList))
				$STATUS = PostingTable::STATUS_SENT_WITH_ERRORS;
			else
				$STATUS = PostingTable::STATUS_SENT;

			$DATE = new Type\DateTime();
		}
		else
		{
			$STATUS = PostingTable::STATUS_PART;
			$DATE = null;
		}


		// unlock posting for exclude double parallel sending
		static::unlockPosting($id);


		// update status of posting
		$postingUpdateFields = array(
			'STATUS' => $STATUS,
			'DATE_SENT' => $DATE,
			'COUNT_SEND_ALL' => 0
		);
		$recipientStatusToPostingFieldMap = PostingTable::getRecipientStatusToPostingFieldMap();
		foreach($recipientStatusToPostingFieldMap as $recipientStatus => $postingFieldName)
		{
			if(!array_key_exists($recipientStatus, $statusList))
			{
				$postingCountFieldValue = 0;
			}
			else
			{
				$postingCountFieldValue = $statusList[$recipientStatus];
			}

			$postingUpdateFields['COUNT_SEND_ALL'] += $postingCountFieldValue;
			$postingUpdateFields[$postingFieldName] = $postingCountFieldValue;
		}
		PostingTable::update(array('ID' => $id), $postingUpdateFields);

		// return status to continue or end of sending
		if($STATUS == PostingTable::STATUS_PART)
			return static::SEND_RESULT_CONTINUE;
		else
			return static::SEND_RESULT_SENT;
	}

	/**
	 * Lock posting for preventing double sending
	 *
	 * @param $id
	 * @return bool
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 * @throws \Exception
	 */
	public static function lockPosting($id)
	{
		$id = intval($id);

		$uniq = \COption::GetOptionString("main", "server_uniq_id", "");
		if($uniq == '')
		{
			$uniq = md5(uniqid(rand(), true));
			\COption::SetOptionString("main", "server_uniq_id", $uniq);
		}

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$lockDb = $connection->query("SELECT GET_LOCK('".$uniq."_sendpost_".$id."', 0) as L", false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$lock = $lockDb->fetch();
			if($lock["L"]=="1")
				return true;
			else
				return false;
		}
		elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			//Clean up locks
			$i=\COption::GetOptionInt("sender", "posting_interval");
			//For at least 5 seconds
			if($i<5) $i=5;
			$connection->query("DELETE FROM B_SENDER_POSTING_LOCK WHERE DATEDIFF(SECOND, TIMESTAMP_X, GETDATE())>".$i);
			$connection->query("SET LOCK_TIMEOUT 1");
			$lockDb = $connection->query("INSERT INTO B_SENDER_POSTING_LOCK (ID, TIMESTAMP_X) VALUES (".$id.", GETDATE())");
			$connection->query("SET LOCK_TIMEOUT -1");
			return $lockDb->getResource()!==false;
		}
		elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			try
			{
				$lockDb = $connection->query("
					declare
						my_lock_id number;
						my_result number;
						lock_failed exception;
						pragma exception_init(lock_failed, -54);
					begin
						my_lock_id:=dbms_utility.get_hash_value(to_char('" . $uniq . "_sendpost_" . $id . "'), 0, 1024);
						my_result:=dbms_lock.request(my_lock_id, dbms_lock.x_mode, 0, true);
						--  Return value:
						--    0 - success
						--    1 - timeout
						--    2 - deadlock
						--    3 - parameter error
						--    4 - already own lock specified by 'id' or 'lockhandle'
						--    5 - illegal lockhandle
						if(my_result<>0 and my_result<>4)then
							raise lock_failed;
						end if;
					end;
				");
			}
			catch(\Bitrix\Main\Db\SqlQueryException $exception)
			{
				if(strpos($exception->getDatabaseMessage(), "ORA-00054") === false)
					throw $exception;
			}

			return $lockDb->getResource()!==false;
		}

		return false;
	}

	/**
	 * UnLock posting that was locking for preventing double sending
	 *
	 * @param $id
	 * @return bool
	 */
	public static function unlockPosting($id)
	{
		$id = intval($id);

		$connection = \Bitrix\Main\Application::getInstance()->getConnection();
		if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$uniq = \COption::GetOptionString("main", "server_uniq_id", "");
			if(strlen($uniq)>0)
			{
				$lockDb = $connection->query("SELECT RELEASE_LOCK('".$uniq."_sendpost_".$id."') as L");
				$lock = $lockDb->fetch();
				if($lock["L"] == "0")
					return false;
				else
					return true;
			}
		}
		elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			$connection->query("DELETE FROM B_SENDER_POSTING_LOCK WHERE ID=".$id);
			return true;
		}
		elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			//lock released on commit
			return true;
		}

		return false;
	}
}