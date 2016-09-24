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

class MailingTriggerTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_mailing_trigger';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(

			'MAILING_CHAIN_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true,
			),
			'NAME' => array(
				'data_type' => 'string',
			),
			'EVENT' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true,
			),
			'IS_TYPE_START' => array(
				'data_type' => 'boolean',
				'values' => array(false, true),
				'primary' => true,
				'required' => true,
			),
			'ENDPOINT' => array(
				'data_type' => 'text',
				'serialized' => true,
				'required' => true,
			),
			'MAILING_CHAIN' => array(
				'data_type' => 'Bitrix\Sender\MailingChainTable',
				'reference' => array('=this.MAILING_CHAIN_ID' => 'ref.ID'),
			),
		);
	}


	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		if(is_string($data['fields']['ENDPOINT']))
		{
			$data['fields']['ENDPOINT'] = unserialize($data['fields']['ENDPOINT']);
		}
		if(!is_array($data['fields']['ENDPOINT']))
		{
			$data['fields']['ENDPOINT'] = null;
		}
		static::actualizeHandlers($data['primary']['MAILING_CHAIN_ID'], $data['fields']['ENDPOINT'], null);

		return $result;
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		$itemDb = static::getByPrimary($data['primary']);
		$item = $itemDb->fetch();

		if(!is_array($data['fields']['ENDPOINT']))
		{
			$data['fields']['ENDPOINT'] = null;
		}
		if(!$item || !is_array($item['ENDPOINT']))
		{
			$item['ENDPOINT'] = null;
		}
		static::actualizeHandlers($data['primary']['MAILING_CHAIN_ID'], $data['fields']['ENDPOINT'], $item['ENDPOINT']);

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

		$itemDb = static::getByPrimary($data['primary']);
		while($item = $itemDb->fetch())
		{
			if(!is_array($item['ENDPOINT']))
			{
				$item['ENDPOINT'] = null;
			}

			static::actualizeHandlers($item['MAILING_CHAIN_ID'], null, $item['ENDPOINT']);
		}

		return $result;
	}

	public static function actualizeHandlers($chainId, array $fieldsNew = null, array $fieldsOld = null)
	{
		$settingsNew = null;
		$settingsOld = null;

		if($fieldsNew)
			$settingsNew = new TriggerSettings($fieldsNew);
		if($fieldsOld)
			$settingsOld = new TriggerSettings($fieldsOld);


		// if old item was closed trigger
		if($settingsOld && $settingsOld->isClosedTrigger())
		{
			// delete agent
			$agentName = TriggerManager::getClosedEventAgentName(
				$settingsOld->getEventModuleId(),
				$settingsOld->getEventType(),
				$chainId
			);

			$agent = new \CAgent;
			$agentListDb = $agent->GetList(array(), array('MODULE_ID' => 'sender', 'NAME' => $agentName));
			while($agentItem = $agentListDb->Fetch())
				$agent->Delete($agentItem['ID']);
		}


		// if new item is closed trigger
		if($settingsNew && $settingsNew->isClosedTrigger())
		{
			// check active state of mailing
			$chainDb = MailingChainTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=ID' => $chainId, '=MAILING.ACTIVE' => 'Y')
			));
			if(!$chainDb->fetch())
				return;

			// add new agent
			$agentName = TriggerManager::getClosedEventAgentName(
				$settingsNew->getEventModuleId(),
				$settingsNew->getEventType(),
				$chainId
			);

			// set date of next exec
			$agentTime = $settingsNew->getClosedTriggerTime();
			$agentInterval = $settingsNew->getClosedTriggerInterval();
			if($agentInterval <= 0) $agentInterval = 1440;

			$agentTimeArray = explode(":", $agentTime);
			$agentDate = new \Bitrix\Main\Type\DateTime;
			$agentDate->setTime((int)$agentTimeArray[0], (int)$agentTimeArray[1]);

			// set next exec on next day if exec was today
			if($agentDate->getTimestamp() < time())
				$agentDate->add("1 days");

			// add agent
			$agent = new \CAgent;
			$agent->AddAgent($agentName, 'sender', 'N', $agentInterval*60, '', 'Y', $agentDate->toString());

			return;
		}

		// actualize deleted/changed event
		if($settingsOld && !$settingsOld->isClosedTrigger() && $settingsOld->getFullEventType())
		{
			// if delete operation(no the NEW)
			// or change operation(the NEW is not equal to the OLD)
			if(!$settingsNew || $settingsOld->getFullEventType() != $settingsNew->getFullEventType())
			{
				TriggerManager::actualizeHandler(array(
					'MODULE_ID' => $settingsOld->getEventModuleId(),
					'EVENT_TYPE' => $settingsOld->getEventType(),
					'CALLED_BEFORE_CHANGE' => true
				));
			}
		}

		// actualize new event
		if($settingsNew && $settingsNew->getFullEventType())
		{
			$calledBeforeChange = ($fieldsOld ?  false : true);
			TriggerManager::actualizeHandler(array(
				'MODULE_ID' => $settingsNew->getEventModuleId(),
				'EVENT_TYPE' => $settingsNew->getEventType(),
				'CALLED_BEFORE_CHANGE' => $calledBeforeChange
			));
		}
	}
}