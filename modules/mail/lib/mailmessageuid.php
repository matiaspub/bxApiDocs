<?php

namespace Bitrix\Mail;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization;

Localization\Loc::loadMessages(__FILE__);

class MailMessageUidTable extends Entity\DataManager
{

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_mail_message_uid';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'string',
				'primary'   => true,
			),
			'MAILBOX_ID' => array(
				'data_type' => 'integer',
				'primary'   => true,
			),
			'HEADER_MD5' => array(
				'data_type' => 'string',
			),
			'IS_SEEN' => array(
				'data_type' => 'enum',
				'values'    => array('Y', 'N', 'S', 'U'),
			),
			'SESSION_ID' => array(
				'data_type' => 'string',
				'required'  => true,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'required'  => true,
			),
			'MESSAGE_ID' => array(
				'data_type' => 'integer',
				'required'  => true,
			),
			'MAILBOX' => array(
				'data_type' => 'Bitrix\Mail\Mailbox',
				'reference' => array('=this.MAILBOX_ID' => 'ref.ID'),
			),
			'MESSAGE' => array(
				'data_type' => 'Bitrix\Mail\MailMessage',
				'reference' => array('=this.MESSAGE_ID' => 'ref.ID'),
			),
		);
	}

}
