<?php
namespace Bitrix\Main\Diag;

class SqlTrackerQuery
{
	protected $sql;
	protected $arBinds;
	protected $startTime;
	protected $finishTime;
	protected $time;
	protected $trace;

	/**
	 * @var SqlTracker
	 */
	protected $tracker;

	static public function __construct(SqlTracker $tracker)
	{
		$this->tracker = $tracker;
	}

	static public function startQuery($sql, array $arBinds = array())
	{
		$this->sql = $sql;
		$this->arBinds = $arBinds;
		$this->startTime = Helper::getCurrentMicrotime();
	}

	static public function finishQuery()
	{
		$this->finishTime = Helper::getCurrentMicrotime();
		$this->time = $this->finishTime - $this->startTime;
		$this->trace = (function_exists("debug_backtrace") ? debug_backtrace() : null);

		$this->tracker->addTime($this->time);
	}

	static public function restartQuery()
	{
		$this->startTime = Helper::getCurrentMicrotime();
	}

	static public function refinishQuery()
	{
		$this->finishTime = Helper::getCurrentMicrotime();
		$this->time += $this->finishTime - $this->startTime;

		$this->tracker->addTime($this->time);
	}
}
