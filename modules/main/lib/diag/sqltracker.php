<?php
namespace Bitrix\Main\Diag;

class SqlTracker
{
	/**
	 * @var SqlTrackerQuery[]
	 */
	protected $arQuery = array();

	protected $time;

	public function reset()
	{
		$this->arQuery = array();
		$this->time = 0;
	}

	public function getNewTrackerQuery()
	{
		$query = new SqlTrackerQuery($this);
		$this->arQuery[] = $query;
		return $query;
	}

	public function addTime($time)
	{
		$this->time += $time;
	}

}
