<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;

Loc::loadMessages(__FILE__);

class MailingChainTable extends Entity\DataManager
{

	const STATUS_NEW = 'N';
	const STATUS_SEND = 'S';
	const STATUS_PAUSE = 'P';
	const STATUS_WAIT = 'W';
	const STATUS_END = 'Y';

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_mailing_chain';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'autocomplete' => true,
				'primary' => true,
			),
			'MAILING_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true,
			),
			'POSTING_ID' => array(
				'data_type' => 'integer',
			),
			'PARENT_ID' => array(
				'data_type' => 'integer',
			),
			'CREATED_BY' => array(
				'data_type' => 'integer',
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => static::STATUS_NEW,
			),
			'REITERATE' => array(
				'data_type' => 'string',
				'default_value' => 'N',
			),
			'LAST_EXECUTED' => array(
				'data_type' => 'datetime',
			),

			'EMAIL_FROM' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_CHAIN_FIELD_TITLE_EMAIL_FROM'),
				'validation' => array(__CLASS__, 'validateEmailForm'),
			),
			'SUBJECT' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_CHAIN_FIELD_TITLE_SUBJECT')
			),
			'MESSAGE' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_CHAIN_FIELD_TITLE_MESSAGE')
			),

			'IS_TRIGGER' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => 'N',
			),

			'TIME_SHIFT' => array(
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 0,
			),

			'AUTO_SEND_TIME' => array(
				'data_type' => 'datetime',
			),

			'DAYS_OF_MONTH' => array(
				'data_type' => 'string',
			),
			'DAYS_OF_WEEK' => array(
				'data_type' => 'string',
			),
			'TIMES_OF_DAY' => array(
				'data_type' => 'string',
			),

			'MAILING' => array(
				'data_type' => 'Bitrix\Sender\MailingTable',
				'reference' => array('=this.MAILING_ID' => 'ref.ID'),
			),
			'CURRENT_POSTING' => array(
				'data_type' => 'Bitrix\Sender\PostingTable',
				'reference' => array('=this.POSTING_ID' => 'ref.ID'),
			),
			'POSTING' => array(
				'data_type' => 'Bitrix\Sender\PostingTable',
				'reference' => array('=this.ID' => 'ref.MAILING_CHAIN_ID'),
			),
			'ATTACHMENT' => array(
				'data_type' => 'Bitrix\Sender\MailingAttachmentTable',
				'reference' => array('=this.ID' => 'ref.CHAIN_ID'),
			),
			'CREATED_BY_USER' => array(
				'data_type' => 'Bitrix\Main\UserTable',
				'reference' => array('=this.CREATED_BY' => 'ref.ID'),
			),
		);
	}

	/**
	 * Returns validators for EMAIL_FROM field.
	 *
	 * @return array
	 */
	public static function validateEmailForm()
	{
		return array(
			new Entity\Validator\Length(null, 50),
			array(__CLASS__, 'checkEmail')
		);
	}

	/**
	 * @return mixed
	 */
	public static function checkEmail($value)
	{
		if(empty($value) || check_email($value))
			return true;
		else
			return Loc::getMessage('SENDER_ENTITY_MAILING_CHAIN_VALID_EMAIL_FROM');
	}

	/**
	 * @param $mailingChainId
	 * @return int|null
	 */
	public static function initPosting($mailingChainId)
	{
		$postingId = null;
		$chainPrimary = array('ID' => $mailingChainId);
		$arMailingChain = static::getRowById($chainPrimary);
		if($arMailingChain)
		{
			$needAddPosting = true;

			if(!empty($arMailingChain['POSTING_ID']))
			{
				$arPosting = PostingTable::getRowById(array('ID' => $arMailingChain['POSTING_ID']));
				if($arPosting)
				{
					if($arPosting['STATUS'] == PostingTable::STATUS_NEW)
					{
						$postingId = $arMailingChain['POSTING_ID'];
						$needAddPosting = false;
					}
					/*
					elseif($arMailingChain['IS_TRIGGER'] == 'Y')
					{
						$postingId = $arMailingChain['POSTING_ID'];
						$needAddPosting = false;
					}
					*/
				}
			}

			if($needAddPosting)
			{
				$postingAddDb = PostingTable::add(array(
					'MAILING_ID' => $arMailingChain['MAILING_ID'],
					'MAILING_CHAIN_ID' => $arMailingChain['ID'],
				));
				if ($postingAddDb->isSuccess())
				{
					$postingId = $postingAddDb->getId();
					static::update($chainPrimary, array('POSTING_ID' => $postingId));
				}
			}

			if($postingId && $arMailingChain['IS_TRIGGER'] != 'Y')
				PostingTable::initGroupRecipients($postingId);
		}

		return $postingId;
	}


	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		if(!isset($data['fields']['IS_TRIGGER']) || $data['fields']['IS_TRIGGER'] != 'Y')
		{
			static::initPosting($data['primary']['ID']);
		}

		if(array_key_exists('STATUS', $data['fields']) || array_key_exists('AUTO_SEND_TIME', $data['fields']))
		{
			MailingManager::actualizeAgent(null, $data['primary']['ID']);
		}

		if(isset($data['fields']['PARENT_ID']))
		{
			TriggerManager::actualizeHandlerForChild();
		}

		return $result;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();
		if(array_key_exists('STATUS', $data['fields']) || array_key_exists('AUTO_SEND_TIME', $data['fields']))
		{
			if(array_key_exists('STATUS', $data['fields']) && $data['fields']['STATUS'] == static::STATUS_NEW)
				static::initPosting($data['primary']['ID']);

			MailingManager::actualizeAgent(null, $data['primary']['ID']);
		}

		if(isset($data['fields']['PARENT_ID']))
		{
			TriggerManager::actualizeHandlerForChild();
		}

		return $result;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		$deleteIdList = array();
		if(!empty($data['primary']))
		{
			$itemDb = static::getList(array(
				'select' => array('ID'),
				'filter' => $data['primary']
			));
			while($item = $itemDb->fetch())
			{
				$deleteIdList[] = $item['ID'];
			}
		}

		foreach($deleteIdList as $chainId)
		{
			MailingAttachmentTable::delete(array('CHAIN_ID' => $chainId));
			MailingTriggerTable::delete(array('MAILING_CHAIN_ID' => $chainId));
			PostingTable::delete(array('MAILING_CHAIN_ID' => $chainId));
		}

		return $result;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		TriggerManager::actualizeHandlerForChild();
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function isReadyToSend($id)
	{
		$mailingChainDb = static::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'ID' => $id,
				'MAILING.ACTIVE' => 'Y',
				'STATUS' => array(static::STATUS_NEW, static::STATUS_PAUSE),
			),
		));
		$mailingChain = $mailingChainDb->fetch();

		return !empty($mailingChain);
	}

	/**
	 * @param $id
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function isManualSentPartly($id)
	{
		$mailingChainDb = static::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'ID' => $id,
				'MAILING.ACTIVE' => 'Y',
				'AUTO_SEND_TIME' => null,
				'!REITERATE' => 'Y',
				'STATUS' => array(static::STATUS_SEND),
			),
		));
		$mailingChain = $mailingChainDb->fetch();

		return !empty($mailingChain);
	}

	/**
	 * @param $mailingId
	 */
	public static function setStatusNew($mailingId)
	{
		static::update(array('MAILING_ID' => $mailingId), array('STATUS' => static::STATUS_NEW));
	}

	/**
	 * @return array
	 */
	public static function getStatusList()
	{
		return array(
			self::STATUS_NEW => Loc::getMessage('SENDER_CHAIN_STATUS_N'),
			self::STATUS_SEND => Loc::getMessage('SENDER_CHAIN_STATUS_S'),
			self::STATUS_WAIT => Loc::getMessage('SENDER_CHAIN_STATUS_W'),
			self::STATUS_END => Loc::getMessage('SENDER_CHAIN_STATUS_Y'),
		);
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getDefaultEmailFromList()
	{
		$arAddressFrom = array();
		$siteEmailDb = \Bitrix\Main\SiteTable::getList(array('select'=>array('EMAIL')));
		while($siteEmail = $siteEmailDb->fetch())
		{
			$arAddressFrom[] = $siteEmail['EMAIL'];
		}

		try
		{
			$mainEmail = \COption::GetOptionString('main', 'email_from');
			if (!empty($mainEmail))
				$arAddressFrom[] = $mainEmail;

			$saleEmail = \COption::GetOptionString('sale', 'order_email');
			if(!empty($saleEmail))
				$arAddressFrom[] = $saleEmail;

			$arAddressFrom = array_unique($arAddressFrom);
			trimArr($arAddressFrom, true);

		}
		catch(\Exception $e)
		{

		}

		return $arAddressFrom;
	}

	/**
	 * @return array
	 */
	public static function getEmailFromList()
	{
		$arAddressFrom = static::getDefaultEmailFromList();
		$email = \COption::GetOptionString('sender', 'address_from');
		if(!empty($email))
		{
			$arEmail = explode(',', $email);
			$arAddressFrom = array_merge($arEmail, $arAddressFrom);
			$arAddressFrom = array_unique($arAddressFrom);
			trimArr($arAddressFrom, true);
		}

		return $arAddressFrom;
	}

	/**
	 * @param $email
	 */
	public static function setEmailFromToList($email)
	{
		$emailList = \COption::GetOptionString('sender', 'address_from');
		if(!empty($email))
		{
			$arAddressFrom = explode(',', $emailList);
			$arAddressFrom = array_merge(array($email), $arAddressFrom);
			$arAddressFrom = array_unique($arAddressFrom);
			trimArr($arAddressFrom, true);
			\COption::SetOptionString('sender', 'address_from', implode(',', $arAddressFrom));
		}
	}

	/**
	 * @return array
	 */
	public static function getEmailToMeList()
	{
		$arAddressTo = array();
		$email = \COption::GetOptionString('sender', 'address_send_to_me');
		if(!empty($email))
		{
			$arAddressTo = explode(',', $email);
			$arAddressTo = array_unique($arAddressTo);
			trimArr($arAddressTo, true);
		}

		return $arAddressTo;
	}

	/**
	 * @param $email
	 */
	public static function setEmailToMeList($email)
	{
		$emailList = \COption::GetOptionString('sender', 'address_send_to_me');
		if(!empty($email))
		{
			$arAddressTo = explode(',', $emailList);
			$arAddressTo = array_merge(array($email), $arAddressTo);
			$arAddressTo = array_unique($arAddressTo);
			trimArr($arAddressTo, true);
			\COption::SetOptionString('sender', 'address_send_to_me', implode(',', $arAddressTo));
		}
	}
}

class MailingAttachmentTable extends Entity\DataManager
{

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_mailing_attachment';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'CHAIN_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'FILE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
		);
	}

}