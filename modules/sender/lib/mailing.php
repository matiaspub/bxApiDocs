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

class MailingTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_mailing';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_FIELD_TITLE_NAME')
			),
			'DESCRIPTION' => array(
				'data_type' => 'string',
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_FIELD_TITLE_DESCRIPTION'),
				'validation' => array(__CLASS__, 'validateDescription'),
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new Type\DateTime(),
			),
			'ACTIVE' => array(
				'data_type' => 'string',
				'default_value' => 'Y'
			),
			'TRACK_CLICK' => array(
				'data_type' => 'string',
				'default_value' => 'N',
			),
			'IS_PUBLIC' => array(
				'data_type' => 'string',
				'default_value' => 'Y',
			),
			'IS_TRIGGER' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => 'N',
			),
			'SORT' => array(
				'data_type' => 'integer',
				'required' => true,
				'default_value' => 100,
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_FIELD_TITLE_SORT')
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'TRIGGER_FIELDS' => array(
				'data_type' => 'text',
				'serialized' => true
			),
			'EMAIL_FROM' => array(
				'data_type' => 'string',
				'required' => false,
				'title' => Loc::getMessage('SENDER_ENTITY_MAILING_FIELD_TITLE_EMAIL_FROM'),
				'validation' => array('Bitrix\Sender\MailingChainTable', 'validateEmailForm'),
			),
			'CHAIN' => array(
				'data_type' => 'Bitrix\Sender\MailingChainTable',
				'reference' => array('=this.ID' => 'ref.MAILING_ID'),
			),
			'POSTING' => array(
				'data_type' => 'Bitrix\Sender\PostingTable',
				'reference' => array('=this.ID' => 'ref.MAILING_ID'),
			),
			'MAILING_GROUP' => array(
				'data_type' => 'Bitrix\Sender\MailingGroupTable',
				'reference' => array('=this.ID' => 'ref.MAILING_ID'),
			),
			'MAILING_SUBSCRIPTION' => array(
				'data_type' => 'Bitrix\Sender\MailingSubscriptionTable',
				'reference' => array('=this.ID' => 'ref.MAILING_ID'),
			),
		);
	}

	/**
	 * Returns validators for DESCRIPTION field.
	 *
	 * @return array
	 */
	public static function validateDescription()
	{
		return array(
			new Entity\Validator\Length(null, 2000),
		);
	}


	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		if(array_key_exists('ACTIVE', $data['fields']))
		{
			MailingManager::actualizeAgent($data['primary']['ID']);
		}

		if (array_key_exists('ACTIVE', $data['fields']) || array_key_exists('TRIGGER_FIELDS', $data['fields']))
		{
			static::updateChainTrigger($data['primary']['ID']);
		}

		if(!empty($data['fields']['EMAIL_FROM']))
		{
			$chainListDb = MailingChainTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=MAILING_ID' => $data['primary']['ID'], '=IS_TRIGGER' => 'Y', '=MAILING.IS_TRIGGER' => 'Y',),
			));
			while($chain = $chainListDb->fetch())
			{
				MailingChainTable::update(array('ID' => $chain['ID']), array('EMAIL_FROM' => $data['fields']['EMAIL_FROM']));
			}
		}

		return $result;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		$primary = array('MAILING_ID' => $data['primary']['ID']);
		MailingGroupTable::delete($primary);
		MailingChainTable::delete($primary);
		MailingSubscriptionTable::delete($primary);
		PostingTable::delete($primary);

		return $result;
	}

	/*
	 *
	 * @return \Bitrix\Main\DB\Result
	 * */
	public static function getPresetMailingList(array $params = null)
	{
		$resultList = array();
		$event = new \Bitrix\Main\Event('sender', 'OnPresetMailingList');
		$event->send();

		foreach ($event->getResults() as $eventResult)
		{
			if ($eventResult->getType() == \Bitrix\Main\EventResult::ERROR)
			{
				continue;
			}

			$eventResultParameters = $eventResult->getParameters();

			if (!empty($eventResultParameters))
			{
				if(!empty($params['CODE']))
				{
					$eventResultParametersTmp = array();
					foreach($eventResultParameters as $preset)
					{
						if($params['CODE'] == $preset['CODE'])
						{
							$eventResultParametersTmp[] = $preset;
							break;
						}
					}

					$eventResultParameters = $eventResultParametersTmp;
				}

				$resultList = array_merge($resultList, $eventResultParameters);
			}
		}

		$resultListTmp = array();
		foreach($resultList as $result)
		{
			if(empty($result['TRIGGER']['START']['ENDPOINT']['CODE']))
				continue;

			$trigger = TriggerManager::getOnce($result['TRIGGER']['START']['ENDPOINT']);
			if(!$trigger)
				continue;

			$result['TRIGGER']['START']['ENDPOINT']['NAME'] = $trigger->getName();
			if(!empty($result['TRIGGER']['START']['ENDPOINT']['CODE']))
			{
				$trigger = TriggerManager::getOnce($result['TRIGGER']['END']['ENDPOINT']);
				if(!$trigger)
					$result['TRIGGER']['END']['ENDPOINT']['NAME'] = $trigger->getName();
			}


			$resultListTmp[] = $result;
		}

		return $resultListTmp;
	}

	public static function checkFieldsChain(\Bitrix\Main\Entity\Result $result, $primary = null, array $fields)
	{
		$id = $primary;
		$errorList = array();
		$errorCurrentNumber = 0;

		foreach($fields as $item)
		{
			$errorCurrentNumber++;

			$chainFields = array(
				'MAILING_ID' => ($id ? $id : 1),
				'ID' => $item['ID'],
				'REITERATE' => 'Y',
				'IS_TRIGGER' => 'Y',
				'EMAIL_FROM' => $item['EMAIL_FROM'],
				'SUBJECT' => $item['SUBJECT'],
				'MESSAGE' => $item['MESSAGE'],
				'TIME_SHIFT' => intval($item['TIME_SHIFT']),
			);

			$chainId = 0;
			if(!empty($item['ID']))
				$chainId = $item['ID'];

			if($chainId > 0)
			{
				$chain = \Bitrix\Sender\MailingChainTable::getRowById(array('ID' => $chainId));
				if($chain && $chain['STATUS'] != \Bitrix\Sender\MailingChainTable::STATUS_WAIT)
				{
					$chainFields['STATUS'] = $chain['STATUS'];
				}
			}

			if(empty($chainFields['STATUS']))
				$chainFields['STATUS'] = \Bitrix\Sender\MailingChainTable::STATUS_WAIT;

			$chainFields['ID'] = $chainId;

			$resultItem = new \Bitrix\Main\Entity\Result;
			\Bitrix\Sender\MailingChainTable::checkFields($resultItem, null, $chainFields);
			if($resultItem->isSuccess())
			{

			}
			else
			{
				$errorList[$errorCurrentNumber] = $resultItem->getErrors();
			}
		}

		$delimiter = '';
		foreach($errorList as $number => $errors)
		{
			/* @var \Bitrix\Main\Entity\FieldError[] $errors*/
			foreach($errors as $error)
			{
				$result->addError(new Entity\FieldError(
						$error->getField(),
						$delimiter . Loc::getMessage('SENDER_ENTITY_MAILING_CHAIN_ITEM_NUMBER') . $number . ': ' . $error->getMessage(),
						$error->getCode()
					)
				);

				$delimiter = '';
			}

			$delimiter = "\n";
		}


		return $result;
	}

	public static function updateChain($id, array $fields)
	{
		$result = new \Bitrix\Main\Entity\Result;

		static::checkFieldsChain($result, $id, $fields);
		if(!$result->isSuccess(true))
			return $result;

		$parentChainId = null;
		$existChildIdList = array();
		foreach($fields as $chainFields)
		{
			$chainId = $chainFields['ID'];
			unset($chainFields['ID']);

			$chainFields['MAILING_ID'] = $id;
			$chainFields['IS_TRIGGER'] = 'Y';
			$chainFields['REITERATE'] = 'Y';
			$chainFields['PARENT_ID'] = $parentChainId;

			// default status
			if($chainId > 0)
			{
				$chain = \Bitrix\Sender\MailingChainTable::getRowById(array('ID' => $chainId));
				if($chain && $chain['STATUS'] != \Bitrix\Sender\MailingChainTable::STATUS_WAIT)
				{
					$chainFields['STATUS'] = $chain['STATUS'];
					unset($chainFields['CREATED_BY']);
				}
			}
			if(empty($chainFields['STATUS']))
				$chainFields['STATUS'] = \Bitrix\Sender\MailingChainTable::STATUS_WAIT;


			// add or update
			if($chainId > 0)
			{
				$existChildIdList[] = $chainId;

				$chainUpdateDb = MailingChainTable::update(array('ID' => $chainId), $chainFields);
				if($chainUpdateDb->isSuccess())
				{

				}
				else
				{
					$result->addErrors($chainUpdateDb->getErrors());
				}
			}
			else
			{
				$chainAddDb = MailingChainTable::add($chainFields);
				if($chainAddDb->isSuccess())
				{
					$chainId = $chainAddDb->getId();
					$existChildIdList[] = $chainId;
				}
				else
				{
					$result->addErrors($chainAddDb->getErrors());
				}
			}

			if(!empty($errorList)) break;

			$parentChainId = null;
			if($chainId !== null)
				$parentChainId = $chainId;
		}

		$deleteChainDb = MailingChainTable::getList(array(
			'select' => array('ID'),
			'filter' => array('MAILING_ID' => $id, '!ID' => $existChildIdList),
		));
		while($deleteChain = $deleteChainDb->fetch())
		{
			MailingChainTable::delete(array('ID' => $deleteChain['ID']));
		}

		static::updateChainTrigger($id);

		return $result;
	}

	public static function getChain($id)
	{
		$result = array();
		$parentId = null;

		do
		{
			$chainDb = MailingChainTable::getList(array(
				'select' => array(
					'ID', 'SUBJECT', 'EMAIL_FROM', 'MESSAGE', 'TIME_SHIFT', 'PARENT_ID',
					'DATE_INSERT', 'CREATED_BY', 'CREATED_BY_NAME' => 'CREATED_BY_USER.NAME', 'CREATED_BY_LAST_NAME' => 'CREATED_BY_USER.LAST_NAME'
				),
				'filter' => array('=MAILING_ID' => $id, '=PARENT_ID' => $parentId),
			));

			$parentId = null;
			while($chain = $chainDb->fetch())
			{
				//unset($chain['MESSAGE']);
				$result[] = $chain;
				$parentId = $chain['ID'];
			}


		}while($parentId !== null);


		return $result;
	}

	public static function updateChainTrigger($id)
	{
		// get first item of chain
		$chainDb = MailingChainTable::getList(array(
			'select' => array('ID', 'TRIGGER_FIELDS' => 'MAILING.TRIGGER_FIELDS'),
			'filter' => array('=MAILING_ID' => $id, '=IS_TRIGGER' => 'Y', '=PARENT_ID' => null),
		));

		$chain = $chainDb->fetch();
		if(!$chain) return;
		$chainId = $chain['ID'];

		// get trigger settings from mailing
		$triggerFields = $chain['TRIGGER_FIELDS'];
		if(!is_array($triggerFields))
			$triggerFields = array();

		// init TriggerSettings objects
		$settingsList = array();
		foreach($triggerFields as $key => $point)
		{
			if(empty($point['CODE'])) continue;

			$point['IS_EVENT_OCCUR'] = true;
			$point['IS_PREVENT_EMAIL'] = false;
			$point['SEND_INTERVAL_UNIT'] = 'M';
			$point['IS_CLOSED_TRIGGER'] = ($point['IS_CLOSED_TRIGGER'] == 'Y' ? true : false);

			switch($key)
			{
				case 'END':
					$point['IS_TYPE_START'] = false;
					break;

				case 'START':
				default:
					$point['IS_TYPE_START'] = true;
			}

			$settingsList[] = new \Bitrix\Sender\TriggerSettings($point);
		}


		// prepare fields for save
		$mailingTriggerList = array();
		foreach($settingsList as $settings)
		{
			/* @var \Bitrix\Sender\TriggerSettings $settings */
			$trigger = \Bitrix\Sender\TriggerManager::getOnce($settings->getEndpoint());
			if($trigger)
			{
				$triggerFindId = $trigger->getFullEventType() . "/" .((int) $settings->isTypeStart());
				$mailingTriggerList[$triggerFindId] = array(
					'IS_TYPE_START' => $settings->isTypeStart(),
					'NAME' => $trigger->getName(),
					'EVENT' => $trigger->getFullEventType(),
					'ENDPOINT' => $settings->getArray(),
				);
			}
		}


		// add new, update exists, delete old rows
		$triggerDb = MailingTriggerTable::getList(array(
			'select' => array('EVENT', 'MAILING_CHAIN_ID', 'IS_TYPE_START'),
			'filter' => array('=MAILING_CHAIN_ID' => $chainId)
		));
		while($trigger = $triggerDb->fetch())
		{
			$triggerFindId = $trigger['EVENT'] . "/" . ((int) $trigger['IS_TYPE_START']);
			if(!isset($mailingTriggerList[$triggerFindId]))
			{
				MailingTriggerTable::delete($trigger);
			}
			else
			{
				MailingTriggerTable::update($trigger, $mailingTriggerList[$triggerFindId]);
				unset($mailingTriggerList[$triggerFindId]);
			}
		}

		foreach($mailingTriggerList as $triggerFindId => $settings)
		{
			$settings['MAILING_CHAIN_ID'] = $chainId;
			MailingTriggerTable::add($settings);
		}

		TriggerManager::actualizeHandlerForChild();
	}

	public static function setWasRunForOldData($id, $state)
	{
		$state = (bool) $state == true ? 'Y' : 'N';
		$mailing = static::getRowById($id);
		if(!$mailing)
		{
			return;
		}

		$triggerFields = $mailing['TRIGGER_FIELDS'];
		if(!is_array($triggerFields))
		{
			return;
		}

		if(!isset($triggerFields['START']))
		{
			return;
		}

		$triggerFields['START']['WAS_RUN_FOR_OLD_DATA'] = $state;
		$updateDb = static::update($id, array('TRIGGER_FIELDS' => $triggerFields));
		if($updateDb->isSuccess())
		{
			static::updateChainTrigger($id);
		}
	}

	public static function getChainPersonalizeList($id)
	{
		$result = array();

		$mailingDb = \Bitrix\Sender\MailingTable::getList(array(
			'select' => array('ID', 'TRIGGER_FIELDS'),
			'filter' => array(
				//'=ACTIVE' => 'Y',
				'=IS_TRIGGER' => 'Y',
				'=ID' => $id
			),
		));
		if(!$mailing = $mailingDb->fetch())
			return $result;

		$triggerFields = $mailing['TRIGGER_FIELDS'];
		if(!is_array($triggerFields))
			$triggerFields = array();

		$settingsList = array();
		foreach($triggerFields as $key => $point)
		{
			if(empty($point['CODE'])) continue;

			$point['IS_EVENT_OCCUR'] = true;
			$point['IS_PREVENT_EMAIL'] = false;
			$point['SEND_INTERVAL_UNIT'] = 'M';

			switch($key)
			{
				case 'END':
					$point['IS_TYPE_START'] = false;
					break;

				case 'START':
				default:
					$point['IS_TYPE_START'] = true;
			}

			$settingsList[] = new \Bitrix\Sender\TriggerSettings($point);
		}

		foreach($settingsList as $settings)
		{
			/* @var \Bitrix\Sender\TriggerSettings $settings */
			if(!$settings->isTypeStart())
				continue;

			$trigger = \Bitrix\Sender\TriggerManager::getOnce($settings->getEndpoint());
			if($trigger)
			{
				$result = array_merge($result, $trigger->getPersonalizeList());
			}
		}

		return $result;
	}
}


class MailingGroupTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_mailing_group';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'MAILING_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'GROUP_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'INCLUDE' => array(
				'data_type' => 'boolean',
				'values' => array(false, true),
				'required' => true,
			),
			'MAILING' => array(
				'data_type' => 'Bitrix\Sender\MailingTable',
				'reference' => array('=this.MAILING_ID' => 'ref.ID'),
			),
			'GROUP' => array(
				'data_type' => 'Bitrix\Sender\GroupTable',
				'reference' => array('=this.GROUP_ID' => 'ref.ID'),
			),
		);
	}
}

class MailingSubscriptionTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_mailing_subscription';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'MAILING_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'CONTACT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'MAILING' => array(
				'data_type' => 'Bitrix\Sender\MailingTable',
				'reference' => array('=this.MAILING_ID' => 'ref.ID'),
			),
			'CONTACT' => array(
				'data_type' => 'Bitrix\Sender\ContactTable',
				'reference' => array('=this.CONTACT_ID' => 'ref.ID'),
			),
		);
	}
}