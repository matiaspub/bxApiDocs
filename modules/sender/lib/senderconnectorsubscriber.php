<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender;


class SenderConnectorSubscriber extends \Bitrix\Sender\Connector
{
	/**
	 * @return string
	 */
	static public function getName()
	{
		return 'Email-marketing - Subscriber';
	}

	/**
	 * @return string
	 */
	static public function getCode()
	{
		return "sender_subscriber";
	}
	/** @return \CDBResult */
	public function getData()
	{
		$mailingId = $this->getFieldValue('MAILING_ID', 0);

		$mailingDb = MailingSubscriptionTable::getList(array(
			'select' => array('NAME' => 'CONTACT.NAME', 'EMAIL' => 'CONTACT.EMAIL', 'USER_ID' => 'CONTACT.USER_ID'),
			'filter' => array(
				'MAILING_ID' => $mailingId,
			)
		));

		return new \CDBResult($mailingDb);
	}

	/**
	 * @return string
	 */
	static public function getForm()
	{
		return '';
	}
}
