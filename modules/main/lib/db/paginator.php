<?php
namespace Bitrix\Main\DB;
/**
 * Class Paginator
 *
 * @deprecated To be removed soon. Don't use it.
 * @package Bitrix\Main\DB
 */
class Paginator
{
	/**
	 * Makes offset and limit calculations and executes limited query against $connection.
	 *
	 * @param string $sql Sql query.
	 * @param Connection $connection Database connection for query execution.
	 * @param integer $numberOfRecords Total number of records returned by Sql query.
	 * @param integer $pageNumber Page to be displayed.
	 * @param integer $numberOfRecordsPerPage Page size.
	 * @param boolean $backward Use backward paging.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\Db\SqlQueryException
	 */
	
	/**
	* <p>Статический метод задаёт смещение и ограничение вычислений и выполняет ограниченный запрос к БД.</p>
	*
	*
	* @param string $sql  Sql запрос.
	*
	* @param string $Bitrix  Соединение с БД для запроса.
	*
	* @param Bitri $Main  Общий номер записи, возвращаемой запросом.
	*
	* @param Mai $MaiDB  СТраница для отображения.
	*
	* @param Connection $connection  Размер страницы.
	*
	* @param integer $numberOfRecords  Использование обратной пагинации.
	*
	* @param integer $pageNumber  
	*
	* @param integer $numberOfRecordsPerPage  
	*
	* @param boolean $backward = false 
	*
	* @return \Bitrix\Main\DB\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/paginator/query.php
	* @author Bitrix
	*/
	public static function query($sql, Connection $connection, $numberOfRecords, $pageNumber, $numberOfRecordsPerPage, $backward = false)
	{
		list($offset, $limit) = self::calculateQueryLimits(
			$numberOfRecords, $pageNumber, $numberOfRecordsPerPage, $backward
		);

		return $connection->query($sql, $offset, $limit);
	}

	/**
	 * Returns two element array with offset and limit based on paging parameters.
	 *
	 * @param integer $numberOfRecords Total number of records returned by Sql query.
	 * @param integer $pageNumber Page to be displayed.
	 * @param integer $numberOfRecordsPerPage Page size.
	 * @param boolean $backward Use backward paging.
	 *
	 * @return array
	 */
	
	/**
	* <p>Статический метод возвращает массив из двух элементов со смещением и ограничением, основанном на параметрах пагинации.</p>
	*
	*
	* @param integer $numberOfRecords  Общий номер записи возвращённый Sql запросом.
	*
	* @param integer $pageNumber  страница для отображения.
	*
	* @param integer $numberOfRecordsPerPage  Размер страницы.
	*
	* @param boolean $backward  Использовать обратную пагинацию.
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/db/paginator/calculatequerylimits.php
	* @author Bitrix
	*/
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
