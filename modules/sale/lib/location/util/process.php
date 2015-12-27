<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Util;

use Bitrix\Main;

abstract class Process
{
	const JUST_SHOW_STAGES = 	false;
	const MIN_TIME_LIMIT = 		5;
	const DEBUG_MODE = 			false;
	const DEBUG_FOLDER = 		'%BX_ROOT%/tmp/';
	const DEBUG_FILE = 			'%SESSION_KEY%_process.txt';
	const LOCK_FILE = 			'%SESSION_KEY%_lock';

	const CALLBACK_TYPE_MANUAL =  	'manual';
	const CALLBACK_TYPE_QUOTA = 	'quota';

	protected $stages = 		array();
	protected $stagesByCode = 	array();
	protected $stage = 			0;
	protected $step = 			0;
	protected $data = 			array();
	protected $time = 			0;
	protected $timeLimit = 		20; // in seconds
	protected $sessionKey = 	'long_process';
	protected $useLock = 		false;

	protected $options = 		array();

	public function __construct($options = array())
	{
		if(isset($options['INITIAL_TIME']))
			$this->time = intval($options['INITIAL_TIME']);
		else
			$this->time = time();

		$this->useLock = !!$options['USE_LOCK'];
		$this->options = $options;

		$this->restore();

		if(isset($options['STEP']) && $options['STEP'] == 0)
			$this->reset();

		$this->logMessage('#############################', false);
		$this->logMessage('HIT STARTED '.$this->getTimeStampString(), false);

		if(intval($options['TIME_LIMIT']))
			$this->setTimeLimit(intval($options['TIME_LIMIT']));

		$this->saveStartTime();
		$this->saveMemoryPeak();
	}

	public function addStage($params)
	{
		if(empty($params['CODE']) || empty($params['CALLBACK']))
			throw new Main\SystemException('Not enought params to add stage');

		$ss = intval($params['STEP_SIZE']);

		$this->stages[] = array(
			'STEP_SIZE' => 				$ss ? $ss : 1,
			'PERCENT' => 				intval($params['PERCENT']),
			'CODE' => 					$params['CODE'],
			'ORDER' => 					count($this->stages),
			'TYPE' => 					strlen($params['TYPE']) ? $params['TYPE'] : static::CALLBACK_TYPE_MANUAL,

			'CALLBACK' => 				$params['CALLBACK'],
			'SUBPERCENT_CALLBACK' => 	$params['SUBPERCENT_CALLBACK'],
			'ON_BEFORE_CALLBACK' => 	strlen($params['ON_BEFORE_CALLBACK']) ? $params['ON_BEFORE_CALLBACK'] : false,
			'ON_AFTER_CALLBACK' => 		strlen($params['ON_AFTER_CALLBACK']) ? $params['ON_AFTER_CALLBACK'] : false
		);
		$this->stagesByCode[$params['CODE']] =& $this->stages[count($this->stages) - 1];
	}

	public function restore()
	{
		if(!isset($_SESSION[$this->sessionKey]['STAGE']))
			$_SESSION[$this->sessionKey]['STAGE'] = 0;

		if(!isset($_SESSION[$this->sessionKey]['STEP']))
			$_SESSION[$this->sessionKey]['STEP'] = 0;

		if(!isset($_SESSION[$this->sessionKey]['DATA']))
			$_SESSION[$this->sessionKey]['DATA'] = array();

		$this->stage =& $_SESSION[$this->sessionKey]['STAGE'];
		$this->step =& $_SESSION[$this->sessionKey]['STEP'];
		$this->data =& $_SESSION[$this->sessionKey]['DATA'];
	}

	// reset current condition
	public function reset()
	{
		$this->stage = 0;
		$this->step = 0;
		$this->data = array();

		$this->clearLogFile();
	}

	public function performStage()
	{
		return $this->performIteration();
	}

	public function performIteration()
	{
		if($this->stage == 0 && $this->step == 0)
		{
			$this->lockProcess();

			if(static::DEBUG_MODE)
			{
				$logDir = $this->getLogFileDir();
				if(!file_exists($logDir))
					mkdir($logDir, 755, true);

				$this->logMessage('PROCESS STARTED, STAGE '.$this->stages[0]['CODE']);
			}
		}

		$this->onBeforePerformIteration();

		if(!isset($this->stages[$this->stage]))
			throw new Main\SystemException('No more stages to perform');

		if(self::JUST_SHOW_STAGES)
			$this->nextStage();
		else
		{
			$stage = $this->stage;

			if($this->stages[$stage]['ON_BEFORE_CALLBACK'] != false)
				call_user_func(array($this, $this->stages[$stage]['ON_BEFORE_CALLBACK']));

			if($this->stages[$this->stage]['TYPE'] == static::CALLBACK_TYPE_MANUAL)
				call_user_func(array($this, $this->stages[$this->stage]['CALLBACK']));
			elseif($this->stages[$this->stage]['TYPE'] == static::CALLBACK_TYPE_QUOTA)
			{
				while($this->checkQuota())
				{
					$result = call_user_func(array($this, $this->stages[$this->stage]['CALLBACK']));
					$this->nextStep();

					if($result)
						break;
				}

				if($result)
					$this->nextStage();
			}

			if($this->stages[$stage]['ON_AFTER_CALLBACK'] != false)
				call_user_func(array($this, $this->stages[$stage]['ON_AFTER_CALLBACK']));
		}

		$this->onAfterPerformIteration();
		$percent = $this->getPercent();

		$this->saveMemoryPeak();

		$this->logMessage('HIT ENDED '.$this->getTimeStampString(), false);

		if($percent == 100)
			$this->unLockProcess();

		return $percent;
	}

	/////////////////////////////////////////////////
	/// Staging
	/////////////////////////////////////////////////

	public function setStepSize($code, $stepSize)
	{
		if(!isset($this->stagesByCode[$code]))
			throw new Main\SystemException('Unknown stage code passed');

		if(($stepSize = intval($stepSize)) <= 0)
			throw new Main\SystemException('Bad step size passed');

		$this->stagesByCode[$code]['STEP_SIZE'] = $stepSize;
	}

	// move to next stage
	public function nextStage()
	{
		$this->stage++;
		$this->step = 0;

		$this->logMessage('### NEXT STAGE >>> '.$this->stages[$this->stage]['CODE'].' in '.$this->getElapsedTimeString().', mem peak = '.$this->getMemoryPeakString().' mb');
	}

	// move to next step
	public function nextStep()
	{
		$this->step++;
	}

	public function isStage($code)
	{
		return $this->stages[$this->stage]['CODE'] == $code;
	}

	protected function stageCompare($code, $way)
	{
		$currIndex = $this->stages[$this->stage]['ORDER'];
		$stageIndex = $this->stagesByCode[$code]['ORDER'];

		if($currIndex == $stageIndex) return true;

		if($way) // gt
			return $currIndex > $stageIndex;
		else // lt
			return $currIndex < $stageIndex;
	}

	// $this->stage <= $code
	public function stageLT($code)
	{
		return $this->stageCompare($code, false);
	}

	// $code <= $this->stage
	public function stageGT($code)
	{
		return $this->stageCompare($code, true);
	}

	public function setStage($stage)
	{
		foreach($this->stages as $sId => $info)
		{
			if($info['CODE'] == $stage)
			{
				$this->stage = $sId;
				$this->step = 0;
				break;
			}
		}
	}

	static public function onBeforePerformIteration()
	{
	}

	static public function onAfterPerformIteration()
	{
	}

	public function getStageCode()
	{
		return $this->stages[$this->stage]['CODE'];
	}
	public function getCurrStageIndex()
	{
		return $this->stage;
	}

	public function getStep()
	{
		return $this->step;
	}

	public function getStage($code)
	{
		return $this->stagesByCode[$code];
	}

	public function getCurrStageStepSize()
	{
		return $this->stages[$this->stage]['STEP_SIZE'];
	}

	/////////////////////////////////////////////////
	/// Percentage
	/////////////////////////////////////////////////

	public function getStagePercent($sNum = false)
	{

		if($sNum === false)
			$stage = $this->stages[$this->stage]['PERCENT'];
		else
			$stage = is_numeric($sNum) ? $this->stages[$sNum]['PERCENT'] : $this->stagesByCode[$sNum]['PERCENT'];

		return $stage ? $stage : 0;
	}

	public function getPercentBetween($codeFrom, $codeTo)
	{
		return $this->getStagePercent($codeTo) - $this->getStagePercent($codeFrom);
	}

	public function getPercentFromToCurrent($codeFrom){
		return $this->getStagePercent($this->stage - 1) - $this->getStagePercent($codeFrom);
	}

	public function getCurrentPercentRange()
	{
		return $this->getStagePercent($this->stage) - $this->getStagePercent($this->stage - 1);
	}

	public function getPercent()
	{
		$percent = $this->stage > 0 ? $this->stages[$this->stage - 1]['PERCENT'] : 0;
	
		$addit = 0;
		$cb = $this->stages[$this->stage]['SUBPERCENT_CALLBACK'];
		if(strlen($cb) && method_exists($this, $cb))
			$addit = $this->$cb();

		return $percent + $addit;
	}

	public function calcSubPercent($range)
	{
		if(!$range) return 0;

		return round(($this->step / $range)*($this->getStagePercent($this->stage) - $this->getStagePercent($this->stage - 1)));
	}

	public function getSubPercentByTotalAndDone($total, $done = 0)
	{
		if(!$done || !$total)
			return 0;

		$pRange = $this->getCurrentPercentRange();
		$part = round($pRange * ($done / $total));

		return $part >= $pRange ? $pRange : $part;
	}

	/////////////////////////////////////////////////
	/// Quotas info
	/////////////////////////////////////////////////

	public function checkQuota()
	{
		return (time() - $this->time) < $this->timeLimit;
	}

	public function setTimeLimit($timeLimit)
	{
		if($timeLimit == intval($timeLimit))
		{
			if($timeLimit < static::MIN_TIME_LIMIT)
				$timeLimit = static::MIN_TIME_LIMIT;

			$this->timeLimit = $timeLimit;
		}
	}

	public function getMemoryPeak()
	{
		return $this->data['memory_peak'];
	}

	protected function saveStartTime()
	{
		if(!isset($this->data['process_time']))
			$this->data['process_time'] = time();
	}

	protected function saveMemoryPeak()
	{
		$mp = memory_get_peak_usage(false);

		if(!isset($this->data['memory_peak']))
			$this->data['memory_peak'] = $mp;
		else
		{
			if($this->data['memory_peak'] < $mp)
				$this->data['memory_peak'] = $mp;
		}
	}

	/////////////////////////////////////////////////
	/// Logging
	/////////////////////////////////////////////////

	public function clearLogFile()
	{
		$logDir = $this->getLogFileDir();

		if(!Main\IO\Directory::isDirectoryExists($logDir))
			Main\IO\Directory::createDirectory($logDir);

		$logFile = $this->getLogFilePath();

		Main\IO\File::putFileContents($logFile, '');
	}

	static public function getLogFileDir()
	{
		return $_SERVER['DOCUMENT_ROOT'].'/'.str_replace('%BX_ROOT%', BX_ROOT, self::DEBUG_FOLDER);
	}

	public function getLogFilePath()
	{
		return $this->getLogFileDir().str_replace('%SESSION_KEY%', $this->sessionKey, self::DEBUG_FILE);
	}

	public function logMessage($message = '', $addTimeStamp = true)
	{
		if(!static::DEBUG_MODE || !strlen($message))
			return;

		file_put_contents(
			$this->getLogFilePath(),
			($addTimeStamp ? $this->getTimeStampString().' ' : '').$message.PHP_EOL,
			FILE_APPEND
		);
	}

	public function logMemoryUsage()
	{
		$this->logMessage('MEMORY USAGE: '.(memory_get_usage(false) / (1024 * 1024)).' MB', false);
	}

	public function logFinalResult()
	{
		$this->logMessage('ALL DONE!');

		$this->logMessage('TOTAL PROCESS TIME: '.$this->getElapsedTimeString(), false);
		$this->logMessage('MEMORY PEAK (mb): '.$this->getMemoryPeakString(), false);
	}

	/////////////////////////////////////////////////
	/// Lock
	/////////////////////////////////////////////////

	public function getLockFilePath()
	{
		return $this->getLogFileDir().str_replace('%SESSION_KEY%', $this->sessionKey, self::LOCK_FILE);
	}

	public function lockProcess()
	{
		if(!$this->useLock)
			return;

		file_put_contents($this->getLockFilePath(), '1');
	}

	public function unLockProcess()
	{
		if(!$this->useLock)
			return;

		$file = $this->getLockFilePath();
		if(file_exists($file))
			unlink($file);
	}

	public function checkProcessLocked()
	{
		return $this->useLock && file_exists($this->getLockFilePath());
	}

	/////////////////////////////////////////////////
	/// Diagnostics tools
	/////////////////////////////////////////////////

	protected function getHitTime()
	{
		return time() - $this->time;
	}

	protected function getProcessTime()
	{
		return time() - $this->data['process_time'];
	}

	protected function getProcessTimeString()
	{
		return $this->getTimeString($this->getProcessTime());
	}

	protected function getHitTimeString()
	{
		return $this->getTimeString($this->getHitTime());
	}

	protected function getElapsedTimeString()
	{
		$time = time() - $this->data['process_time'];
		return $this->getProcessTimeString();
	}

	protected function getTimeString($time = 0)
	{
		$h = floor($time / 3600);
		$m = floor(($time - $h * 3600) / 60);
		$s = $time - $h * 3600 - $m * 60;

		if(strlen($m) == 1)
			$m = '0'.$m;

		if(strlen($s) == 1)
			$s = '0'.$s;

		return $h.':'.$m.':'.$s;
	}

	protected function getTimeStampString()
	{
		return '['.date('H:i:s').']';
	}

	protected function getMemoryPeakString()
	{
		return $this->getMemoryPeak() / 1048576;
	}

	/////////////////////////////////////////////////
	/// Util
	/////////////////////////////////////////////////

	public function getData()
	{
		return $this->data;
	}

	// special case for array
	protected function getBlock($from)
	{
		$step = $this->step;

		for($i = 0; $i < $step; $i++)
			next($from);

		$block = array();
		$hadSmth = false;

		for($i = $step; $i <= $step + $this->getCurrStageStepSize(); $i++)
		{
			list($code, $elem) = each($from);
			if(!isset($code)) break;

			$hadSmth = true;
			$block[$code] = $elem;

			$this->nextStep();
		}

		if(!$hadSmth)
		{
			$this->nextStage();
			return false;
		}

		return $block;
	}	
}