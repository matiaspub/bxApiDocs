<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main\Mail;

use Bitrix\Main\Mail\Internal\EventAttachmentTable;
use Bitrix\Main\Mail\Internal\EventTable;
use Bitrix\Main\Config as Config;
use Bitrix\Main\Type as Type;

class EventManager
{

	/**
	 * @return string|void
	 */
	public static function checkEvents()
	{
		if((defined("DisableEventsCheck") && DisableEventsCheck===true) || (defined("BX_CRONTAB_SUPPORT") && BX_CRONTAB_SUPPORT===true && BX_CRONTAB!==true))
			return;

		$manage_cache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		if(CACHED_b_event !== false && $manage_cache->read(CACHED_b_event, "events"))
			return "";

		return static::executeEvents();
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public static function executeEvents()
	{
		$manage_cache = \Bitrix\Main\Application::getInstance()->getManagedCache();

		if(defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION"))
		{
			if(\CMain::ForkActions(array("CEvent", "ExecuteEvents")))
				return "";
		}

		$bulk = intval(Config\Option::get("main", "mail_event_bulk", 5));
		if($bulk <= 0)
			$bulk = 5;


		$connection = \Bitrix\Main\Application::getConnection();
		if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$uniq = Config\Option::get("main", "server_uniq_id", "");
			if(strlen($uniq)<=0)
			{
				$uniq = md5(uniqid(rand(), true));
				Config\Option::set("main", "server_uniq_id", $uniq);
			}

			$strSql= "SELECT 'x' FROM b_event WHERE SUCCESS_EXEC='N' LIMIT 1";
			$resultEventDb = $connection->query($strSql);
			if($resultEventDb->fetch())
			{
				$lockDb = $connection->query("SELECT GET_LOCK('".$uniq."_event', 0) as L");
				$arLock = $lockDb->fetch();
				if($arLock["L"]=="0")
					return "";
			}
			else
			{
				if(CACHED_b_event!==false)
					$manage_cache->set("events", true);

				return "";
			}

			$strSql = "
				SELECT ID, C_FIELDS, EVENT_NAME, MESSAGE_ID, LID, DATE_FORMAT(DATE_INSERT, '%d.%m.%Y %H:%i:%s') as DATE_INSERT, DUPLICATE
				FROM b_event
				WHERE SUCCESS_EXEC='N'
				ORDER BY ID
				LIMIT ".$bulk;

			$rsMails = $connection->query($strSql);
		}
		elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			$connection->startTransaction();
			$connection->query("SET LOCK_TIMEOUT 0");

			\CTimeZone::Disable();
			$strSql = "
				SELECT TOP ".$bulk."
					ID,
					C_FIELDS,
					EVENT_NAME,
					MESSAGE_ID,
					LID,
					".$connection->getSqlHelper()->getDateToCharFunction("DATE_INSERT")." as DATE_INSERT,
					DUPLICATE
				FROM b_event
				WITH (TABLOCKX)
				WHERE SUCCESS_EXEC = 'N'
				ORDER BY ID
				";
			$rsMails = $connection->query($strSql);
			\CTimeZone::Enable();
		}
		elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			$connection->startTransaction();

			$strSql = "
				SELECT /*+RULE*/ E.ID, E.C_FIELDS, E.EVENT_NAME, E.MESSAGE_ID, E.LID,
					TO_CHAR(E.DATE_INSERT, 'DD.MM.YYYY HH24:MI:SS') as DATE_INSERT, DUPLICATE
				FROM b_event E
				WHERE E.SUCCESS_EXEC='N'
				ORDER BY E.ID
				FOR UPDATE NOWAIT
				";

			$rsMails = $connection->query($strSql);
		}



		if($rsMails)
		{
			$arCallableModificator = array();
			$cnt = 0;
			foreach(EventTable::getFetchModificatorsForFieldsField() as $callableModificator)
				if(is_callable($callableModificator)) $arCallableModificator[] = $callableModificator;
			while($arMail = $rsMails->fetch())
			{
				foreach($arCallableModificator as $callableModificator)
					$arMail['C_FIELDS'] = call_user_func_array($callableModificator, array($arMail['C_FIELDS']));

				$arFiles = array();
				$fileListDb = EventAttachmentTable::getList(array('select' => array('FILE_ID'), 'filter' => array('EVENT_ID' => $arMail["ID"])));
				while($file = $fileListDb->fetch())
					$arFiles[] = $file['FILE_ID'];
				$arMail['FILE'] = $arFiles;

				if(!is_array($arMail['C_FIELDS'])) $arMail['C_FIELDS'] = array();
				$flag = Event::handleEvent($arMail);
				EventTable::update($arMail["ID"], array('SUCCESS_EXEC' => $flag, 'DATE_EXEC' => new Type\DateTime));

				$cnt++;
				if($cnt >= $bulk)
					break;
			}
		}



		if($connection instanceof \Bitrix\Main\DB\MysqlCommonConnection)
		{
			$connection->query("SELECT RELEASE_LOCK('".$uniq."_event')");
		}
		elseif($connection instanceof \Bitrix\Main\DB\MssqlConnection)
		{
			$connection->query("SET LOCK_TIMEOUT -1");
			$connection->commitTransaction();
		}
		elseif($connection instanceof \Bitrix\Main\DB\OracleConnection)
		{
			$connection->commitTransaction();
		}

		if($cnt===0 && CACHED_b_event!==false)
			$manage_cache->set("events", true);
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function cleanUpAgent()
	{
		$period = abs(intval(Config\Option::get("main", "mail_event_period", 14)));
		$periodInSeconds = $period * 24 * 3600;

		$connection = \Bitrix\Main\Application::getConnection();
		$datetime = $connection->getSqlHelper()->addSecondsToDateTime('-' . $periodInSeconds);

		$strSql = "DELETE FROM b_event WHERE DATE_EXEC <= " . $datetime . "";
		$connection->query($strSql);

		return "CEvent::CleanUpAgent();";
	}
}
