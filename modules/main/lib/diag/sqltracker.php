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
	public function addTime($time)
	{
		$this->time += $time;
	}

	/**
	 * Returns number of queries executed.
	 *
	 * @return integer
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
	public function getTime()
	{
		return $this->time;
	}

	/**
	 * Returns array of SqlTrackerQuery objects so far collected.
	 *
	 * @return array
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
	public function rewind()
	{
		reset($this->queries);
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return mixed
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
	public function key()
	{
		return key($this->queries);
	}

	/**
	 * Part of Iterator implementation made for backward compatibility.
	 *
	 * @return void
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
	public function valid()
	{
		return key($this->queries) !== null;
	}
}
