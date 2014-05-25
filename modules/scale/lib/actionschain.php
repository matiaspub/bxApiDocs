<?php
namespace Bitrix\Scale;

/**
 * Class ActionsChain
 * @package Bitrix\Scale
 */
class ActionsChain
{
	protected $id = "";
	protected $userParams = array();
	protected $freeParams = array();
	protected $actionParams = array();
	protected $resultData = array();

	public $results = "";

	/**
	 * @param string $actionId
	 * @param array $actionParams
	 * @param string $serverHostname
	 * @param array $userParams
	 * @param array $freeParams
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __construct($actionId, $actionParams, $serverHostname = "", $userParams = array(), $freeParams = array())
	{
		if(strlen($actionId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("actionId");

		if(!is_array($actionParams) || empty($actionParams))
			throw new \Exception("Params of action ".$actionId." are not defined correctly!");

		if(!isset($actionParams["ACTIONS"]) || !is_array($actionParams["ACTIONS"]))
			throw new \Exception("Required param ACTIONS of action ".$actionId." are not defined!");

		if(!is_array($userParams))
			throw new \Bitrix\Main\ArgumentTypeException("userParams", "array");

		if(!is_array($freeParams))
			throw new \Bitrix\Main\ArgumentTypeException("freeParams", "array");

		$this->id = $actionId;
		$this->userParams = $userParams;
		$this->freeParams = $freeParams;
		$this->actionParams = $actionParams;
		$this->serverHostname = $serverHostname;
	}

	public function getResult()
	{
		return $this->results;
	}

	public function getActionObj($actionId)
	{
		return ActionsData::getActionObject($actionId, $this->serverHostname, $this->userParams, $this->freeParams);
	}

	public function start($inputParams = array())
	{
		if(!is_array($inputParams))
			throw new \Bitrix\Main\ArgumentTypeException("inputParams", "array");

		$result = true;

		foreach($this->actionParams["ACTIONS"] as $actionId)
		{
			$action = $this->getActionObj($actionId);

			if(!$action->start($inputParams))
				$result = false;

			$arRes = $action->getResult();

			foreach($arRes as $actId => $res)
				$this->results[$actId] = $res;

			if(!$result)
				break;

			if(isset($arRes[$actionId]["OUTPUT"]["DATA"]["params"]) && is_array($arRes[$actionId]["OUTPUT"]["DATA"]["params"]))
				foreach($arRes[$actionId]["OUTPUT"]["DATA"]["params"] as $paramId => $paramValue)
					if(!isset($inputParams[$paramId]))
						$inputParams[$paramId] = $paramValue;
		}

		return $result;
	}
}