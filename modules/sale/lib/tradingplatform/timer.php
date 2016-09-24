<?php

namespace Bitrix\Sale\TradingPlatform;

/**
 * Class Timer
 * @package Bitrix\Sale\TradingPlatform
 */
class Timer
{
	protected $finishTime;
	protected $timeLimit;

	/**
	 * Constructor.
	 * @param int $newTimeLimit Timelimit seconds.
	 */
	
	/**
	* <p>Создает объект данного типа. Метод нестатический.</p>
	*
	*
	* @param integer $newTimeLimit  Временной лимит (в секундах).
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/tradingplatform/timer/__construct.php
	* @author Bitrix
	*/
	public function __construct($newTimeLimit = 0)
	{
		$startTime = (int)time();
		$currentTimeLimit = ini_get('max_execution_time');

		if($newTimeLimit > $currentTimeLimit || $newTimeLimit == 0)
			$timeLimit = $newTimeLimit;
		else
			$timeLimit = $currentTimeLimit;

		$this->timeLimit = $timeLimit;
		$this->finishTime =  $startTime + (int)($timeLimit);
		@set_time_limit($timeLimit);
	}

	/**
	 * Checks if time is over.
	 * @param int $reserveTime Insurance time.
	 * @return bool
	 */
	
	/**
	* <p>Метод проверяет, истекло ли время. Метод нестатический.</p>
	*
	*
	* @param integer $reserveTime  Время проверки.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/tradingplatform/timer/check.php
	* @author Bitrix
	*/
	public function check($reserveTime = 0)
	{
		if($this->timeLimit == 0)
			return true;

		if(time() < $this->finishTime - $reserveTime)
			return true;

		return false;
	}
} 