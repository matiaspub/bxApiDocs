<?php
namespace Bitrix\Scale;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ActionsData
 * @package Bitrix\Scale
 */
class ActionsData
{
	protected static $logLevel = Logger::LOG_LEVEL_INFO;

	/**
	 * @param $actionId
	 * @return array Action's parameters
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getAction($actionId)
	{
		if(strlen($actionId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("actionId");

		$actionsDefinitions = static::getList();

		$result = array();

		if(isset($actionsDefinitions[$actionId]))
			$result = $actionsDefinitions[$actionId];

		return $result;
	}

	/**
	 * @param string $actionId - action idetifyer
	 * @param string $serverHostname - server hostname
	 * @param array $userParams - params filled by user
	 * @param array $freeParams - params filled somewere in code
	 * @param array $actionParams - acrion parameters
	 * @return Action|ActionsChain|bool
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Exception
	 */
	public static function getActionObject($actionId, $serverHostname = "", array $userParams = array(), array $freeParams = array(), array $actionParams = array())
	{
		if(strlen($actionId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("actionId");

		if(!is_array($userParams))
			throw new \Bitrix\Main\ArgumentTypeException("userParams", "array");

		if(!is_array($userParams))
			throw new \Bitrix\Main\ArgumentTypeException("freeParams", "array");

		if(!is_array($actionParams))
			throw new \Bitrix\Main\ArgumentTypeException("actionParams", "array");

		$action = false;

		if(!isset($actionParams["TYPE"]) || $actionParams["TYPE"] != "MODIFYED")
			$actionParams = static::getAction($actionId);

		if(empty($actionParams))
			throw new \Exception("Can't find params of action ".$actionId);

		if(isset($actionParams["TYPE"]) && $actionParams["TYPE"] == "CHAIN")
			$action =  new ActionsChain($actionId, $actionParams, $serverHostname, $userParams, $freeParams);
		else if(!empty($actionParams))
			$action =  new Action($actionId, $actionParams, $serverHostname, $userParams, $freeParams);

		return $action;
	}

	/**
	 * Returns action state
	 * @param string $bid -     action bitrix idetifyer
	 * @return array
	 */
	public static function getActionState($bid)
	{
		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-process -a status -t ".$bid." -o json");
		$data = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($data, true);

			if(isset($arData["params"][$bid]))
				$result = $arData["params"][$bid];

			if($result["status"] == "finished")
				Logger::addRecord(Logger::LOG_LEVEL_INFO, "SCALE_ACTION_CHECK_STATE", $bid, Loc::getMessage("SCALE_ACTIONSDATA_ACTION_FINISHED"));
			elseif($result["status"] == "error")
				Logger::addRecord(Logger::LOG_LEVEL_ERROR, "SCALE_ACTION_CHECK_STATE", $bid, Loc::getMessage("SCALE_ACTIONSDATA_ACTION_ERROR"));

			if(self::$logLevel >= Logger::LOG_LEVEL_DEBUG)
				Logger::addRecord(Logger::LOG_LEVEL_DEBUG, "SCALE_ACTION_CHECK_STATE", $bid, $data);
		}

		return $result;
	}

	/**
	 * Returns actions list
	 * @param bool $checkConditions - if we need to check conditions
	 * @return array of all actions defenitions
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getList($checkConditions = false)
	{
		static $def = null;

		if($def == null)
		{
			$filename = \Bitrix\Main\Application::getDocumentRoot()."/bitrix/modules/scale/include/actionsdefinitions.php";
			$file = new \Bitrix\Main\IO\File($filename);

			if($file->isExists())
				require_once($filename);
			else
				throw new \Bitrix\Main\IO\FileNotFoundException($filename);

			if(isset($actionsDefinitions))
			{
				$def = $actionsDefinitions;

				if(is_array($def) && $checkConditions)
				{
					foreach($def as $actionId => $action)
					{
						if(isset($action["CONDITION"]) && !self::isConditionSatisfied($action["CONDITION"]))
						{
							unset($def[$actionId]);
						}
					}
				}
			}
			else
			{
				$def = array();
			}
		}

		return $def;
	}

	/**
	 * @param array $condition
	 * @return bool
	 */
	protected static function isConditionSatisfied($condition)
	{
		$result = true;

		if(!isset($condition["COMMAND"]) || !isset($condition["PARAMS"]) || !is_array($condition["PARAMS"]))
		{
			return true;
		}

		if(!isset($condition["PARAMS"][0]) || !isset($condition["PARAMS"][1])|| !isset($condition["PARAMS"][2]))
		{
			return true;
		}

		try
		{
			$action =  new Action("condition", array(
				"START_COMMAND_TEMPLATE" => $condition["COMMAND"],
				"LOG_LEVEL" => Logger::LOG_LEVEL_DISABLE
			), "", array());

			if(!$action->start())
			{
				return true;
			}
		}
		catch(\Exception $e)
		{
			return true;
		}

		$actRes = $action->getResult();
		if(isset($actRes["condition"]["OUTPUT"]["DATA"]["params"]))
		{
			$arParam = explode(":", $condition["PARAMS"][0]);
			$buildParam = $actRes["condition"]["OUTPUT"]["DATA"]["params"];
			foreach($arParam as $param)
			{
				if(isset($buildParam[$param]))
				{
					$buildParam = $buildParam[$param];
				}
			}

			$fBody = 'return ($param '.$condition["PARAMS"][1].' '.$condition["PARAMS"][2].');';
			$newfunc = create_function('$param', $fBody);
			$result = $newfunc($buildParam);
		}

		return $result;
	}

	/**
	 * @param int $logLevel
	 */
	public static function setLogLevel($logLevel)
	{
		self::$logLevel = $logLevel;
	}

	/**
	 * Checks if some action is running
	 * after page refresh, or then smb. else come to page
	 * during the action running.
	 * @return array - Action params
	 */
	public static function checkRunningAction()
	{
		$result = array();
		$shellAdapter = new ShellAdapter();
		$execRes = $shellAdapter->syncExec("sudo -u root /opt/webdir/bin/bx-process -a list -o json");
		$data = $shellAdapter->getLastOutput();

		if($execRes)
		{
			$arData = json_decode($data, true);
			$result = array();

			if(isset($arData["params"]) && is_array($arData["params"]))
			{
				foreach($arData["params"] as $bid => $actionParams)
				{
					if(strpos($bid, 'common_') === 0) // || strpos($bid, 'monitor_') === 0)
						continue;

					if($actionParams["status"] == "running")
					{
						$result = array($bid => $actionParams);
						break;
					}
				}
			}
		}

		return $result;
	}
}