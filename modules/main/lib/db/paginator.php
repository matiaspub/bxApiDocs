<?php
namespace Bitrix\Main\DB;

class Paginator
{
	public static function query($sql, Connection $connection, $numberOfRecords, $pageNumber, $numberOfRecordsPerPage, $backward = false)
	{
		list($offset, $limit) = self::calculateQueryLimits(
			$numberOfRecords, $pageNumber, $numberOfRecordsPerPage, $backward
		);

		return $connection->query($sql, $offset, $limit);
	}

	public static function calculateQueryLimits($numberOfRecords, $pageNumber, $numberOfRecordsPerPage, $backward)
	{
		$pageNumber = intval($pageNumber);
		$numberOfRecords = intval($numberOfRecords);

		$numberOfRecordsPerPage = intval($numberOfRecordsPerPage);
		if ($numberOfRecordsPerPage <= 0)
			$numberOfRecordsPerPage = 10;

		$pageCount = floor($numberOfRecords / $numberOfRecordsPerPage);
		if ($backward)
		{
			$makeweight = ($numberOfRecords % $numberOfRecordsPerPage);
			if ($pageCount == 0 && $makeweight > 0)
				$pageCount = 1;

			if ($pageNumber < 1)
				$pageNumber = 1;
			if ($pageNumber > $pageCount)
				$pageNumber = $pageCount;

			$firstRecordToShow = 0;
			if ($pageNumber != $pageCount)
				$firstRecordToShow += $makeweight;

			$firstRecordToShow += ($pageCount - $pageNumber) * $numberOfRecordsPerPage;
			$lastRecordToShow = $makeweight + ($pageCount - $pageNumber + 1) * $numberOfRecordsPerPage;
		}
		else
		{
			if ($numberOfRecordsPerPage && ($numberOfRecords % $numberOfRecordsPerPage > 0))
				$pageCount++;

			if ($pageNumber < 1)
				$pageNumber = 1;
			if ($pageNumber > $pageCount)
				$pageNumber = $pageCount;

			$firstRecordToShow = $numberOfRecordsPerPage * ($pageNumber - 1);
			$lastRecordToShow = $numberOfRecordsPerPage * $pageNumber;
		}

		return array($firstRecordToShow, $lastRecordToShow - $firstRecordToShow);
	}
}
