<?php
namespace Bitrix\Main\Diag;

class SqlTracker implements \Iterator
{
	/** @var array[]SqlTrackerQuery */
	protected $queries = array();
	/** @var float */
	protected $time = 0.0;
	/** @var int */
	protected $depthBackTrace = 8;
	/** @var integer */
	protected $counter = 0;
	/** @var string */
	protected $logFilePath = "";

	/**
	 * Clears all queries collected and resets execution time.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод очищает все собранные запросы и сбрасывает время выполнения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/reset.php
	* @author Bitrix
	*/
	public function reset()
	{
		$this->queries = array();
		$this->time = 0.0;
		$this->counter = 0;
	}

	/**
	 * Creates new instance of SqlTrackerQuery object.
	 *
	 * @return \Bitrix\Main\Diag\SqlTrackerQuery
	 */
	
	/**
	* <p>Нестатический метод создаёт новый экземпляр объекта <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/index.php">SqlTrackerQuery</a>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return \Bitrix\Main\Diag\SqlTrackerQuery 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/getnewtrackerquery.php
	* @author Bitrix
	*/
	public function getNewTrackerQuery()
	{
		$query = new SqlTrackerQuery($this);
		$this->queries[] = $query;
		$this->counter++;
		return $query;
	}

	/**
	 * Used by SqlTrackerQuery to track overall execution time.
	 *
	 * @param float $time Time in seconds.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод используется <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/index.php">SqlTrackerQuery</a> для отслеживания общего времени выполнения.</p>
	*
	*
	* @param float $time  Время в секундах
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/addtime.php
	* @author Bitrix
	*/
	public function addTime($time)
	{
		$this->time += $time;
	}

	/**
	 * Returns number of queries executed.
	 *
	 * @return integer
	 */
	
	/**
	* <p>Нестатический метод возвращает количество выполненных запросов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/getcounter.php
	* @author Bitrix
	*/
	public function getCounter()
	{
		return $this->counter;
	}

	/**
	 * Returns overall queries time execution.
	 *
	 * @return float
	 */
	
	/**
	* <p>Нестатический метод возвращает общее время выполнения запросов.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return float 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/gettime.php
	* @author Bitrix
	*/
	public function getTime()
	{
		return $this->time;
	}

	/**
	 * Returns array of SqlTrackerQuery objects so far collected.
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает массив до сих пор собираемых объектов <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltrackerquery/index.php">SqlTrackerQuery</a>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/getqueries.php
	* @author Bitrix
	*/
	public function getQueries()
	{
		return $this->queries;
	}

	/**
	 * Returns backtrace depth for writing into log.
	 *
	 * @return int
	 */
	
	/**
	* <p>Нестатический метод возвращает глубину трассировки для записи в лог файл.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return integer 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/getdepthbacktrace.php
	* @author Bitrix
	*/
	public function getDepthBackTrace()
	{
		return $this->depthBackTrace;
	}

	/**
	 * Sets backtrace depth for writing into log.
	 *
	 * @param int $depthBackTrace Desired backtrace depth.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод устанавливает глубину трассировки для записи в лог файл.</p>
	*
	*
	* @param integer $depthBackTrace  Необходимая глубина трассировки.
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/setdepthbacktrace.php
	* @author Bitrix
	*/
	public function setDepthBackTrace($depthBackTrace)
	{
		$this->depthBackTrace = (int)$depthBackTrace;
	}

	/**
	 * Starts writing queries into log file.
	 *
	 * @param string $filePath Absolute file path.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\SqlTracker->stopFileLog
	 * @see \Bitrix\Main\Diag\SqlTracker->writeFileLog
	 */
	
	/**
	* <p>Нестатический метод запускает запрись запросов в лог файл.</p>
	*
	*
	* @param string $filePath  Абсолютный путь к лог файлу.
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/stopfilelog.php">\Bitrix\Main\Diag\SqlTracker::stopFileLog</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/writefilelog.php">\Bitrix\Main\Diag\SqlTracker::writeFileLog</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/startfilelog.php
	* @author Bitrix
	*/
	public function startFileLog($filePath)
	{
		$this->logFilePath = (string)$filePath;
	}

	/**
	 * Writes query text and part of backtrace into log file.
	 *
	 * @param string $sql Query to be dumped.
	 * @param float $executionTime Query time.
	 * @param string $additional Additional info string to be added to header.
	 * @param integer $traceSkip How many backtrace frames to skip in output.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\SqlTracker->startFileLog
	 * @see \Bitrix\Main\Diag\SqlTracker->stopFileLog
	 */
	
	/**
	* <p>Нестатический метод записывает в лог файл текст запроса и часть трассировки.</p>
	*
	*
	* @param string $sql  Запрос для записи.
	*
	* @param float $executionTime = 0.0 Время выполнения
	*
	* @param string $additional = "" ДОполнительная информационная строка, которая будет добавлена в
	* заголовок.
	*
	* @param integer $traceSkip = 2 Сколько фрагментов трассировки пропустить при выходе.
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/startfilelog.php">\Bitrix\Main\Diag\SqlTracker::startFileLog</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/stopfilelog.php">\Bitrix\Main\Diag\SqlTracker::stopFileLog</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/writefilelog.php
	* @author Bitrix
	*/
	public function writeFileLog($sql, $executionTime = 0.0, $additional = "", $traceSkip = 2)
	{
		if ($this->logFilePath)
		{
			$header = "TIME: ".round($executionTime, 6)." SESSION: ".session_id()." ".$additional."\n";
			$headerLength = strlen($header);
			$body = $this->formatSql($sql);
			$trace = $this->formatTrace(\Bitrix\Main\Diag\Helper::getBackTrace($this->depthBackTrace, null, $traceSkip));
			$footer = str_repeat("-", $headerLength);
			$message =
				"\n".$header.
				"\n".$body.
				"\n\n".$trace.
				"\n".$footer.
				"\n";
			\Bitrix\Main\IO\File::putFileContents($this->logFilePath, $message, \Bitrix\Main\IO\File::APPEND);
		}
	}

	/**
	 * Stops writing queries into log file.
	 *
	 * @return void
	 * @see \Bitrix\Main\Diag\SqlTracker->startFileLog
	 * @see \Bitrix\Main\Diag\SqlTracker->writeFileLog
	 */
	
	/**
	* <p>Нестатический метод останавливает запись запросов в лог файл.</p> <p>Без параметров</p>
	*
	*
	* @return void 
	*
	* <h4>See Also</h4> 
	* <ul> <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/startfilelog.php">\Bitrix\Main\Diag\SqlTracker::startFileLog</a></li>
	* <li><a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/writefilelog.php">\Bitrix\Main\Diag\SqlTracker::writeFileLog</a></li>
	* </ul><a name="example"></a>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/stopfilelog.php
	* @author Bitrix
	*/
	public function stopFileLog()
	{
		$this->logFilePath = "";
	}

	/**
	 * Skips leading whitespace lines.
	 * And cuts leftmost repeated tabs.
	 *
	 * @param string $sql Sql text.
	 *
	 * @return string
	 */
	protected function formatSql($sql)
	{
		$sqlLines = explode("\n", $sql);
		$skip = true;
		$tabs = 0;
		foreach ($sqlLines as $i => $line)
		{
			if ($skip)
			{
				if (trim($line, "\n\r\t ") == "")
				{
					unset($sqlLines[$i]);
				}
				else
				{
					$skip = false;
					$tabs = strlen($line) - strlen(ltrim($line, "\t"));
				}
			}
			if ($tabs)
			{
				$line = preg_replace("/^[\\t]{1,$tabs}/", "", $line);
				if ($line !== "")
					$sqlLines[$i] = $line;
				else
					unset($sqlLines[$i]);
			}
		}
		return implode("\n", $sqlLines);
	}

	/**
	 * Returns formatted backtrace for log writing.
	 * Format is multi line. Line separator is "\n".
	 *
	 * @param array $trace Backtrace.
	 *
	 * @return string
	 */
	protected function formatTrace(array $trace = null)
	{
		if ($trace)
		{
			$traceLines = array();
			foreach ($trace as $traceNum => $traceInfo)
			{
				$traceLine = '';

				if (array_key_exists('class', $traceInfo))
					$traceLine .= $traceInfo['class'].$traceInfo['type'];

				if (array_key_exists('function', $traceInfo))
					$traceLine .= $traceInfo['function'].'()';

				if (array_key_exists('file', $traceInfo))
				{
					$traceLine .= ' '.$traceInfo['file'];
					if (array_key_exists('line', $traceInfo))
					$traceLine .= ':'.$traceInfo['line'];
				}

				if ($traceLine)
					$traceLines[] = ' from '.$traceLine;
			}

			return implode("\n", $traceLines);
		}
		else
		{
			return "";
		}
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод. Часть реализации <code>\Iterator</code>, сделана для сохранения обратной совместимости.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/rewind.php
	* @author Bitrix
	*/
	public function rewind()
	{
		reset($this->queries);
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод. Часть реализации <code>\Iterator</code>, сделана для сохранения обратной совместимости.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/current.php
	* @author Bitrix
	*/
	public function current()
	{
		return current($this->queries);
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return mixed
	 */
	
	/**
	* <p>Нестатический метод. Часть реализации <code>\Iterator</code>, сделана для сохранения обратной совместимости.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return mixed 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/key.php
	* @author Bitrix
	*/
	public function key()
	{
		return key($this->queries);
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод. Часть реализации <code>\Iterator</code>, сделана для сохранения обратной совместимости.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/next.php
	* @author Bitrix
	*/
	public function next()
	{
		next($this->queries);
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return boolean
	 */
	
	/**
	* <p>Нестатический метод. Часть реализации <code>\Iterator</code>, сделана для сохранения обратной совместимости.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/diag/sqltracker/valid.php
	* @author Bitrix
	*/
	public function valid()
	{
		return key($this->queries) !== null;
	}
}
