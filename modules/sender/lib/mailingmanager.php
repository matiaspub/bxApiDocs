<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Exception;
use Bitrix\Sender;
use \Bitrix\Main\Type;

class MailingManager
{
	/* @var Exception $error */
	protected static $error = null;

	/**
	 * @return Exception
	 */
	public static function getErrors()
	{
		return static::$error;
	}

	/**
	 * @return string
	 */
	public static function getAgentNamePeriod()
	{
		return '\Bitrix\Sender\MailingManager::checkPeriod();';
	}

	/**
	 * @param $mailingChainId
	 * @return string
	 */
	public static function getAgentName($mailingChainId)
	{
		return '\Bitrix\Sender\MailingManager::chainSend('.intval($mailingChainId).');';
	}

	/**
	 * @param null $mailingId
	 * @param null $mailingChainId
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function actualizeAgent($mailingId = null, $mailingChainId = null)
	{
		$agent = new \CAgent();

		$isSendByTimeMethodCron = \Bitrix\Main\Config\Option::get("sender", "auto_method") === 'cron';

		$arFilter = array();
		if ($mailingId)
			$arFilter['=MAILING_ID'] = $mailingId;
		if ($mailingChainId)
			$arFilter['=ID'] = $mailingChainId;

		$mailingChainDb = MailingChainTable::getList(array(
			'select' => array('ID', 'STATUS', 'AUTO_SEND_TIME', 'MAILING_ACTIVE' => 'MAILING.ACTIVE'),
			'filter' => $arFilter
		));
		while ($mailingChain = $mailingChainDb->fetch())
		{
			$agentName = static::getAgentName($mailingChain['ID']);
			$rsAgents = $agent->GetList(array("ID" => "DESC"), array(
				"MODULE_ID" => "sender",
				"NAME" => $agentName,
			));
			while ($arAgent = $rsAgents->Fetch())
				$agent->Delete($arAgent["ID"]);

			if($isSendByTimeMethodCron || empty($mailingChain['AUTO_SEND_TIME']))
				continue;

			if ($mailingChain['MAILING_ACTIVE'] == 'Y' && $mailingChain['STATUS'] == MailingChainTable::STATUS_SEND)
			{
				if(!empty($mailingChain['AUTO_SEND_TIME']))
					$dateExecute = $mailingChain['AUTO_SEND_TIME'];
				else
					$dateExecute = "";

				$interval = \Bitrix\Main\Config\Option::get('sender', 'auto_agent_interval', "0");
				$agent->AddAgent($agentName, "sender", "N", intval($interval), null, "Y", $dateExecute);
			}
		}
	}

	/**
	 * @param $mailingChainId
	 * @return string
	 */
	public static function chainSend($mailingChainId)
	{
		static::$error = null;

		$mailingChainPrimary = array('ID' => $mailingChainId);
		$mailingChainDb = MailingChainTable::getById($mailingChainPrimary);
		$mailingChain = $mailingChainDb->fetch();
		if($mailingChain && $mailingChain['STATUS'] == MailingChainTable::STATUS_SEND)
		{
			if(\COption::GetOptionString("sender", "auto_method") === 'cron')
			{
				$maxMailCount = 0;
				$timeout = 0;
			}
			else
			{
				$maxMailCount = \COption::GetOptionInt("sender", "max_emails_per_hit");
				$timeout = \COption::GetOptionInt("sender", "interval");
			}

			$postingSendStatus = '';
			if(!empty($mailingChain['POSTING_ID']))
			{
				try
				{
					$postingSendStatus = PostingManager::send($mailingChain['POSTING_ID'], $timeout, $maxMailCount);
				} catch (Exception $e)
				{
					static::$error = $e;
					$postingSendStatus = PostingManager::SEND_RESULT_ERROR;
				}
			}

			if(empty(static::$error) && $postingSendStatus !== PostingManager::SEND_RESULT_CONTINUE)
			{
				if ($mailingChain['REITERATE'] == 'Y')
				{
					$mailingChainFields = array(
						'STATUS' => MailingChainTable::STATUS_WAIT,
						'AUTO_SEND_TIME' => null,
						'POSTING_ID' => null
					);

					if($mailingChain['IS_TRIGGER'] == 'Y')
					{
						$postingDb = PostingTable::getList(array(
							'select' => array('ID', 'DATE_CREATE'),
							'filter' => array(
								'STATUS' => PostingTable::STATUS_NEW,
								'MAILING_CHAIN_ID' => $mailingChain['ID']
							),
							'order' => array('DATE_CREATE' => 'ASC'),
							'limit' => 1
						));
						if($posting = $postingDb->fetch())
						{
							$mailingChainFields['AUTO_SEND_TIME'] = $posting['DATE_CREATE']->add($mailingChain['TIME_SHIFT'].' minutes');
							$mailingChainFields['STATUS'] = MailingChainTable::STATUS_SEND;
							$mailingChainFields['POSTING_ID'] = $posting['ID'];
						}
					}

					MailingChainTable::update($mailingChainPrimary, $mailingChainFields);

					$eventData = array(
						'MAILING_CHAIN' => $mailingChain
					);
					$event = new \Bitrix\Main\Event('sender', 'OnAfterMailingChainSend', array($eventData));
					$event->send();
				}
				else
				{
					MailingChainTable::update($mailingChainPrimary, array('STATUS' => MailingChainTable::STATUS_END));
				}
			}
			else
			{
				return static::getAgentName($mailingChainId);
			}
		}

		return "";
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function checkSend()
	{
		if(\COption::GetOptionString("sender", "auto_method") !== 'cron')
			return;

		$mailingChainDb = MailingChainTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'STATUS' => MailingChainTable::STATUS_SEND,
				'MAILING.ACTIVE' => 'Y',
				'<=AUTO_SEND_TIME' => new Type\DateTime(),
			)
		));
		while ($mailingChain = $mailingChainDb->fetch())
		{
			static::chainSend($mailingChain['ID']);
		}
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function checkPeriod($isAgentExec = true)
	{
		$isAgentExecInSetting = \COption::GetOptionString("sender", "reiterate_method") !== 'cron';
		if(($isAgentExec && !$isAgentExecInSetting) || (!$isAgentExec && $isAgentExecInSetting))
		{
				return "";
		}

		$dateTodayPhp = new \DateTime();
		$datetimeToday = Type\DateTime::createFromPhp(clone $dateTodayPhp);
		$dateToday = clone $dateTodayPhp;
		$dateToday = Type\Date::createFromPhp($dateToday->setTime(0,0,0));
		$dateTomorrow = clone $dateTodayPhp;
		$dateTomorrow = Type\Date::createFromPhp($dateTomorrow->setTime(0,0,0))->add('1 DAY');
		$arDateFilter = array($dateToday, $dateTomorrow);

		$chainDb = MailingChainTable::getList(array(
			'select' => array(
				'ID', 'LAST_EXECUTED', 'POSTING_ID',
				'DAYS_OF_MONTH', 'DAYS_OF_WEEK', 'TIMES_OF_DAY'
			),
			'filter' => array(
				'=REITERATE' => 'Y',
				'=MAILING.ACTIVE' => 'Y',
				'=IS_TRIGGER' => 'N',
				'=STATUS' => MailingChainTable::STATUS_WAIT,
				//'!><LAST_EXECUTED' => $arDateFilter,
			)
		));
		while($arMailingChain = $chainDb->fetch())
		{
			$lastExecuted = $arMailingChain['LAST_EXECUTED'];
			/* @var \Bitrix\Main\Type\DateTime $lastExecuted*/
			if($lastExecuted && $lastExecuted->getTimestamp() >= $dateToday->getTimestamp())
			{
				continue;
			}


			$timeOfExecute = static::getDateExecute(
				$dateTodayPhp,
				$arMailingChain["DAYS_OF_MONTH"],
				$arMailingChain["DAYS_OF_WEEK"],
				$arMailingChain["TIMES_OF_DAY"]
			);

			if($timeOfExecute)
			{
				$arUpdateMailChain = array('LAST_EXECUTED' => $datetimeToday);

				$postingDb = PostingTable::getList(array(
					'select' => array('ID'),
					'filter' => array(
						'=MAILING_CHAIN_ID' => $arMailingChain['ID'],
						'><DATE_CREATE' => $arDateFilter
					)
				));
				$arPosting = $postingDb->fetch();
				if(!$arPosting)
				{
					$postingId = MailingChainTable::initPosting($arMailingChain['ID']);
				}
				else
				{
					$postingId = $arPosting['ID'];
					$arUpdateMailChain['POSTING_ID'] = $postingId;
					PostingTable::initGroupRecipients($postingId);
				}

				if ($postingId)
				{
					$arUpdateMailChain['STATUS'] = MailingChainTable::STATUS_SEND;
					$arUpdateMailChain['AUTO_SEND_TIME'] = Type\DateTime::createFromPhp($timeOfExecute);
				}


				MailingChainTable::update(array('ID' => $arMailingChain['ID']), $arUpdateMailChain);
			}
		}


		return static::getAgentNamePeriod();
	}

	/**
	 * @param \DateTime $date
	 * @param $daysOfMonth
	 * @param $dayOfWeek
	 * @param $timesOfDay
	 * @return \DateTime|null
	 */
	protected static function getDateExecute(\DateTime $date, $daysOfMonth, $dayOfWeek, $timesOfDay)
	{
		$timeOfExecute = null;

		$arDay = static::parseDaysOfMonth($daysOfMonth);
		$arWeek = static::parseDaysOfWeek($dayOfWeek);
		$arTime = static::parseTimesOfDay($timesOfDay);
		if(!$arTime)
			$arTime = array(0,0);

		$day = $date->format('j');
		$week = $date->format('N');

		if( (!$arDay || in_array($day, $arDay)) && (!$arWeek || in_array($week, $arWeek)) )
			$timeOfExecute = $date->setTime($arTime[0], $arTime[1]);

		return $timeOfExecute;
	}

	/**
	 * @param $strDaysOfMonth
	 * @return array|null
	 */
	protected static function parseDaysOfMonth($strDaysOfMonth)
	{
		$arResult = array();
		if (strlen($strDaysOfMonth) > 0)
		{
			$arDoM = explode(",", $strDaysOfMonth);
			$arFound = array();
			foreach ($arDoM as $strDoM)
			{
				if (preg_match("/^(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if (intval($arFound[1]) < 1 || intval($arFound[1]) > 31)
						return null;
					else
						$arResult[] = intval($arFound[1]);
				}
				elseif (preg_match("/^(\d{1,2})-(\d{1,2})$/", trim($strDoM), $arFound))
				{
					if (intval($arFound[1]) < 1 || intval($arFound[1]) > 31 || intval($arFound[2]) < 1 || intval($arFound[2]) > 31 || intval($arFound[1]) >= intval($arFound[2]))
						return null;
					else
						for ($i = intval($arFound[1]); $i <= intval($arFound[2]); $i++)
							$arResult[] = intval($i);
				} else
					return null;
			}
		}
		else
			return null;


		return $arResult;
	}

	/**
	 * @param $strDaysOfWeek
	 * @return array|null
	 */
	protected static function parseDaysOfWeek($strDaysOfWeek)
	{
		if(strlen($strDaysOfWeek) <= 0)
			return null;

		$arResult = array();

		$arDoW = explode(",", $strDaysOfWeek);
		foreach($arDoW as $strDoW)
		{
			$arFound = array();
			if(
				preg_match("/^(\d)$/", trim($strDoW), $arFound)
				&& $arFound[1] >= 1
				&& $arFound[1] <= 7
			)
			{
				$arResult[]=intval($arFound[1]);
			}
			else
			{
				return null;
			}
		}


		return $arResult;
	}

	/**
	 * @param $strTimesOfDay
	 * @return array|null
	 */
	protected static function parseTimesOfDay($strTimesOfDay)
	{
		if(strlen($strTimesOfDay) <= 0)
			return null;

		$result = null;

		$arToD = explode(",", $strTimesOfDay);
		foreach($arToD as $strToD)
		{
			$arFound = array();
			if(
				preg_match("/^(\d{1,2}):(\d{1,2})$/", trim($strToD), $arFound)
				&& $arFound[1] <= 23
				&& $arFound[2] <= 59
			)
			{
				$result = array($arFound[1], $arFound[2]);
			}
			else
			{
				return null;
			}
		}

		return $result;
	}
}
