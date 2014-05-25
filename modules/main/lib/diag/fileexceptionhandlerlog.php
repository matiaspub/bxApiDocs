<?php
namespace Bitrix\Main\Diag;

use Bitrix\Main;

class FileExceptionHandlerLog
	extends ExceptionHandlerLog
{
	const MAX_LOG_SIZE = 1000000;
	const DEFAULT_LOG_FILE = "bitrix/modules/error.log";

	private $logFile;
	private $logFileHistory;

	private $maxLogSize;
	private $level;

	public function initialize(array $options)
	{
		$this->logFile = static::DEFAULT_LOG_FILE;
		if (isset($options["file"]) && !empty($options["file"]))
			$this->logFile = $options["file"];

		$this->logFile = preg_replace("'[\\\\/]+'", "/", $this->logFile);
		if ((substr($this->logFile, 0, 1) !== "/") && !preg_match("#^[a-z]:/#", $this->logFile))
			$this->logFile = Main\Application::getDocumentRoot()."/".$this->logFile;

		$this->logFileHistory = $this->logFile.".old";

		$this->maxLogSize = static::MAX_LOG_SIZE;
		if (isset($options["log_size"]) && ($options["log_size"] > 0))
			$this->maxLogSize = intval($options["log_size"]);

		if (isset($options["level"]) && ($options["level"] > 0))
			$this->level = intval($options["level"]);
	}

	public function write(\Exception $exception, $logType)
	{
		$text = ExceptionHandlerFormatter::format($exception, false, $this->level);
		$this->writeToLog(date("Y-m-d H:i:s")." - Host: ".$_SERVER["HTTP_HOST"]." - ".static::logTypeToString($logType)." - ".$text."\n");
	}

	protected function writeToLog($text)
	{
		if (empty($text))
			return;

		$logFile = $this->logFile;
		$logFileHistory = $this->logFileHistory;

		$oldAbortStatus = ignore_user_abort(true);

		if ($fp = @fopen($logFile, "ab"))
		{
			if (@flock($fp, LOCK_EX))
			{
				$logSize = @filesize($logFile);
				$logSize = intval($logSize);

				if ($logSize > $this->maxLogSize)
				{
					@copy($logFile, $logFileHistory);
					ftruncate($fp, 0);
				}

				@fwrite($fp, $text);
				@fflush($fp);
				@flock($fp, LOCK_UN);
				@fclose($fp);
			}
		}

		ignore_user_abort($oldAbortStatus);
	}
}
