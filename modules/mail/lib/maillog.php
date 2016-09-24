<?php

namespace Bitrix\Mail;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization;

Localization\Loc::loadMessages(__FILE__);

class MailLogTable extends Entity\DataManager
{

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_mail_log';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type'    => 'integer',
				'primary'      => true,
				'autocomplete' => true,
			),
			'MAILBOX_ID' => array(
				'data_type' => 'integer',
			),
			'FILTER_ID' => array(
				'data_type' => 'integer',
			),
			'MESSAGE_ID' => array(
				'data_type' => 'integer',
			),
			'LOG_TYPE' => array(
				'data_type' => 'string',
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'required'  => true,
			),
			'STATUS_GOOD' => array(
				'data_type' => 'boolean',
				'values'    => array('N', 'Y'),
			),
			'MESSAGE' => array(
				'data_type' => 'string',
			),
			'MAILBOX' => array(
				'data_type' => 'Bitrix\Mail\Mailbox',
				'reference' => array('=this.MAILBOX_ID' => 'ref.ID'),
			),
			'FILTER' => array(
				'data_type' => 'Bitrix\Mail\MailFilter',
				'reference' => array('=this.FILTER_ID' => 'ref.ID'),
			),
			'MAIL_MESSAGE' => array(
				'data_type' => 'Bitrix\Mail\MailMessage',
				'reference' => array('=this.MESSAGE_ID' => 'ref.ID'),
			),
		);
	}

}
