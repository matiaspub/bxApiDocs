<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender\Internals;

use \Bitrix\Main\Config\Option;

class CounterCalculation
{
	private static $optionName = '~update_counters_16';
	public static $maxExecutionTime = 10;
	private static $startTime = null;
	private static $stages = array(
		'RECIPIENT_READ' => '1',
		'RECIPIENT_CLICK' => '2',
		'RECIPIENT_UNSUB' => '3',
		'POSTING_STATUS' => '4',
		'POSTING_READ' => '5',
		'POSTING_CLICK' => '6',
		'POSTING_UNSUB' => '7',
		'MAILING_SUBSCRIPTION' => '8',
		'FINISH' => '',
	);

	public static function wasCompleted($stageCode = 'FINISH')
	{
		$currentValue = Option::get('sender', self::$optionName, '');
		$stageValue = self::$stages[$stageCode];

		if($currentValue === '')
		{
			$currentValue = '100';
		}
		if($stageValue === '')
		{
			$stageValue = '100';
		}

		if(intval($currentValue) >= intval($stageValue))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function setCompleted($stageCode = 'FINISH')
	{
		Option::set('sender', self::$optionName, self::$stages[$stageCode]);
	}

	public static function getCompletedPercent()
	{
		$currentValue = Option::get('sender', self::$optionName, '');
		if($currentValue === '')
		{
			$currentValue = count(self::$stages);
		}
		$currentValue = intval($currentValue);

		return array('CURRENT' => $currentValue, 'ALL' => count(self::$stages));
	}

	private static function isTimeUp()
	{
		if(self::$startTime === null)
		{
			self::$startTime = getmicrotime();
		}

		if(self::$maxExecutionTime > 0 && (getmicrotime() - self::$startTime) > self::$maxExecutionTime)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public static function getExecutionTime()
	{
		if(!self::$startTime)
		{
			return 0;
		}
		else
		{
			return getmicrotime() - self::$startTime;
		}
	}

	public static function update()
	{
		if(self::wasCompleted())
		{
			return false;
		}

		$haveData = false;

		// update reading counters of recipients
		foreach(array('READ', 'CLICK', 'UNSUB') as $type)
		{
			if($haveData || self::wasCompleted('RECIPIENT_' . $type))
			{
				continue;
			}

			$haveData = self::updateRecipientReadCounters($type);
			if(!$haveData)
			{
				self::setCompleted('RECIPIENT_' . $type);
			}
		}

		// update status counters of posting
		if(!$haveData && !self::wasCompleted('POSTING_STATUS'))
		{
			$haveData = self::updatePostingStatusCounters();
			if(!$haveData)
			{
				self::setCompleted('POSTING_STATUS');
			}
		}

		// update reading counters of posting
		foreach(array('READ', 'CLICK', 'UNSUB') as $type)
		{
			if($haveData || self::wasCompleted('POSTING_' . $type))
			{
				continue;
			}

			$haveData = self::updatePostingReadCounters($type);
			if(!$haveData)
			{
				self::setCompleted('POSTING_' . $type);
			}
		}

		// update status counters of posting
		if(!$haveData && !self::wasCompleted('MAILING_SUBSCRIPTION'))
		{
			$haveData = self::updateMailingSubscription();
			if(!$haveData)
			{
				self::setCompleted('MAILING_SUBSCRIPTION');
			}
		}

		// if all processed set done flag
		if(!$haveData)
		{
			self::setCompleted();
		}

		return $haveData;
	}

	public static function updateRecipientReadCounters($type)
	{
		$params = array(
			'select' => array('RECIPIENT_ID'),
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'UPDATE_RECIPIENT',
					'Bitrix\Sender\PostingRecipientTable',
					array('=this.RECIPIENT_ID' => 'ref.ID')
				)
			),
			'filter' => array(
				'!UPDATE_RECIPIENT.ID' => null,
				'=UPDATE_RECIPIENT.IS_' . $type => 'N',
			),
			'group' => array('RECIPIENT_ID')
		);
		$dataDb = null;
		switch($type)
		{
			case 'READ':
				$dataDb = \Bitrix\Sender\PostingReadTable::getList($params);
				break;
			case 'CLICK':
				$dataDb = \Bitrix\Sender\PostingClickTable::getList($params);
				break;
			case 'UNSUB':
				$dataDb = \Bitrix\Sender\PostingUnsubTable::getList($params);
				break;
		}

		if(!$dataDb)
		{
			return false;
		}

		while($item = $dataDb->fetch())
		{
			if(self::isTimeUp())
			{
				return true;
			}

			\Bitrix\Sender\PostingRecipientTable::update(array('ID' => $item['RECIPIENT_ID']), array('IS_' . $type => 'Y'));
		}

		return false;
	}

	public static function updatePostingStatusCounters()
	{
		$lastPostingId = null;
		$statusList = array();

		$resultDb = \Bitrix\Sender\PostingRecipientTable::getList(array(
			'select' => array('POSTING_ID', 'STATUS', 'CALC_COUNT'),
			'filter' => array(
				'!UPDATE_POSTING.ID' => null,
				'!STATUS' => null,
				'=UPDATE_POSTING.COUNT_SEND_ALL' => 0, // run only for postings with empty count field
				'>CALC_COUNT' => 0 // run only if posting have recipients
			),
			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('CALC_COUNT', 'COUNT(%s)', 'ID'),
				new \Bitrix\Main\Entity\ReferenceField(
					'UPDATE_POSTING',
					'Bitrix\Sender\PostingTable',
					array('=this.POSTING_ID' => 'ref.ID'),
					array('join_type' => 'INNER')
				),
			),
			'order' => array('CALC_COUNT' => 'DESC', 'POSTING_ID' => 'ASC'),
		));
		$stopRun = false;
		while(!$stopRun)
		{
			if(self::isTimeUp())
			{
				return true;
			}

			$data = $resultDb->fetch();

			// do update if last record or starts records for another posting
			if(!$data || $lastPostingId != $data['POSTING_ID'])
			{
				// do update if it have fields for update
				$updateFields = self::getPostingStatusUpdateFields($lastPostingId, $statusList);
				if($updateFields)
				{
					\Bitrix\Sender\PostingTable::update(array('ID' => $lastPostingId), $updateFields);
				}


				$statusList = array();
			}

			if($data)
			{
				$lastPostingId = $data['POSTING_ID'];
				$statusList[$data['STATUS']] = $data['CALC_COUNT'];
			}

			if(!$data)
			{
				$stopRun = true;
			}


		}


		return false;
	}

	public static function updatePostingReadCounters($type)
	{
		$dataDb = \Bitrix\Sender\PostingRecipientTable::getList(array(
			'select' => array(
				'POSTING_ID',
				'CNT'
			),
			'filter' => array(
				'=UPDATE_POSTING.COUNT_' . $type => 0,
				'>CNT' => 0,
				'=IS_' . $type => 'Y'
			),
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'UPDATE_POSTING',
					'Bitrix\Sender\PostingTable',
					array('=this.POSTING_ID' => 'ref.ID'),
					array('join_type' => 'INNER')
				),
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(%s)', 'ID')
			),
			'order' => array('CNT' => 'DESC', 'POSTING_ID' => 'ASC'),
		));

		while($item = $dataDb->fetch())
		{
			if(self::isTimeUp())
			{
				return true;
			}

			\Bitrix\Sender\PostingTable::update(array('ID' => $item['POSTING_ID']), array('COUNT_' . $type => $item['CNT']));
		}

		return false;
	}

	public static function updateMailingSubscription()
	{
		// get list of all subscribers and unsubscribers
		$existedSubList = array();
		$existedSubDb = \Bitrix\Sender\MailingSubscriptionTable::getList(array(
			'select' => array('EMAIL' => 'CONTACT.EMAIL', 'MAILING_ID')
		));
		while($existedSub = $existedSubDb->fetch())
		{
			$existedSubList[$existedSub['EMAIL']][] = $existedSub['MAILING_ID'];
		}

		// get list of all unsubscribers
		$dataDb = \Bitrix\Sender\PostingUnsubTable::getList(array(
			'select' => array(
				'EMAIL' => 'UPDATE_RECIPIENT.EMAIL',
				'MAILING_ID' => 'UPDATE_POSTING.MAILING_ID',
			),
			'filter' => array(),
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'UPDATE_RECIPIENT',
					'Bitrix\Sender\PostingRecipientTable',
					array('=this.RECIPIENT_ID' => 'ref.ID'),
					array('join_type' => 'INNER')
				),
				new \Bitrix\Main\Entity\ReferenceField(
					'UPDATE_POSTING',
					'Bitrix\Sender\PostingTable',
					array('=this.POSTING_ID' => 'ref.ID'),
					array('join_type' => 'INNER')
				),
			),
			'order' => array('ID' => 'ASC'),
		));
		while($data = $dataDb->fetch())
		{
			if(self::isTimeUp())
			{
				return true;
			}

			if(isset($existedSubList[$data['EMAIL']]) && in_array($data['MAILING_ID'], $existedSubList[$data['EMAIL']]) )
			{
				continue;
			}

			$contactId = \Bitrix\Sender\ContactTable::addIfNotExist(array('EMAIL' => $data['EMAIL']));
			$primary = array('MAILING_ID' => $data['MAILING_ID'], 'CONTACT_ID' => $contactId);
			$fields = array('IS_UNSUB' => 'Y');
			$row = \Bitrix\Sender\MailingSubscriptionTable::getRowById($primary);
			if(!$row)
			{
				$result = \Bitrix\Sender\MailingSubscriptionTable::add($fields + $primary);
				$result->isSuccess();
			}
		}
		
		return false;
	}

	private static function getPostingStatusUpdateFields($postingId, $statusList)
	{
		if(!$postingId || count($statusList) == 0)
		{
			return null;
		}

		$postingUpdateFields = array('COUNT_SEND_ALL' => 0);

		$map = \Bitrix\Sender\PostingTable::getRecipientStatusToPostingFieldMap();
		foreach($map as $recipientStatus => $postingFieldName)
		{
			if(!array_key_exists($recipientStatus, $statusList))
			{
				continue;
			}
			else
			{
				$postingCountFieldValue = $statusList[$recipientStatus];
			}

			$postingUpdateFields['COUNT_SEND_ALL'] += $postingCountFieldValue;
			$postingUpdateFields[$postingFieldName] = $postingCountFieldValue;
		}

		if($postingUpdateFields['COUNT_SEND_ALL'] == 0)
		{
			return null;
		}

		return $postingUpdateFields;
	}
}