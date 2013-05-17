<?php
namespace Bitrix\Main\Diag;

class SqlTracker
{
	/**
	 * @var SqlTrackerQuery[]
	 */
	protected $arQuery = array();

	protected $time;

	static public function reset()
	{
		$this->arQuery = array();
		$this->time = 0;
	}

	static public function getNewTrackerQuery()
	{
		$query = new SqlTrackerQuery($this);
		$this->arQuery[] = $query;
		return $query;
	}

	static public function addTime($time)
	{
		$this->time += $time;
	}

}
