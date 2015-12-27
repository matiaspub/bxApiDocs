<?
use Bitrix\Main\localization\Loc;

Loc::loadMessages(__FILE__);

class CCatalogStepOperations
{
	const DEFAULT_SESSION_PREFIX = 'CC';
	protected $sessID = '';
	protected $errorCounter = 0;
	protected $errors = array();
	protected $stepErrors = array();
	protected $maxExecutionTime = 0;
	protected $maxOperationCounter = 0;
	protected $startOperationTime = 0;
	protected $lastID = 0;
	protected $allCounter = 0;
	protected $allOperationCounter = 0;
	protected $finishOperation = false;
	protected $defaultProgressTemplate = '#PROGRESS_BAR#';
	protected $progressTemplate = '#PROGRESS_BAR#';
	protected $errorTemplate = '';

	public function __construct($sessID, $maxExecutionTime, $maxOperationCounter)
	{
		$sessID = (string)$sessID;
		if ($sessID == '')
			$sessID = self::DEFAULT_SESSION_PREFIX.time();
		$this->sessID = $sessID;
		$this->errorCounter = 0;
		$this->errors = array();
		$this->stepErrors = array();
		$maxExecutionTime = (int)$maxExecutionTime;
		if ($maxExecutionTime < 0)
			$maxExecutionTime = $this->getDefaultExecutionTime();
		$this->maxExecutionTime = $maxExecutionTime;
		$maxOperationCounter = (int)$maxOperationCounter;
		if ($maxOperationCounter < 0)
			$maxOperationCounter = 10;
		$this->maxOperationCounter = $maxOperationCounter;
		$this->startOperationTime = time();
		$this->finishOperation = false;
		$this->progressTemplate = Loc::getMessage('BX_STEP_OPERATION_PROGRESS_TEMPLATE').$this->defaultProgressTemplate;
	}

	public function __destruct()
	{
		if ($this->sessID != '' && isset($_SESSION[$this->sessID]))
			unset($_SESSION[$this->sessID]);
	}

	public function initStep($allCount, $allOperationCount, $lastID)
	{
		if (isset($_SESSION[$this->sessID]) && is_array($_SESSION[$this->sessID]))
		{
			if (isset($_SESSION[$this->sessID]['ERRORS_COUNTER']) && (int)$_SESSION[$this->sessID]['ERRORS_COUNTER'] > 0)
				$this->errorCounter = (int)$_SESSION[$this->sessID]['ERRORS_COUNT'];
		}
		$this->stepErrors = array();
		$lastID = (int)$lastID;
		if ($lastID < 0)
			$lastID = 0;
		$this->lastID = $lastID;
		$allCount = (int)$allCount;
		if ($allCount < 0)
			$allCount = 0;
		$this->allCounter = $allCount;
		$allOperationCount = (int)$allOperationCount;
		if ($allOperationCount < 0)
			$allOperationCount = 0;
		$this->allOperationCounter = $allOperationCount;
	}

	public function saveStep()
	{
		if (!isset($_SESSION[$this->sessID]) || !is_array($_SESSION[$this->sessID]))
			$_SESSION[$this->sessID] = array();
		if ($this->errorCounter > 0)
		{
			if (!empty($this->stepErrors))
				$this->errors = $this->stepErrors;
			$_SESSION[$this->sessID]['ERRORS_COUNTER'] = $this->errorCounter;
		}
		$messageParams = array(
			'MESSAGE' => '',
			'PROGRESS_TOTAL' => $this->allCounter,
			'PROGRESS_VALUE' => $this->allOperationCounter,
			'TYPE' => 'PROGRESS',
			'DETAILS' => str_replace(array('#ALL#', '#COUNT#'), array($this->allCounter, $this->allOperationCounter), $this->progressTemplate),
			'HTML' => true
		);
		if (!$this->finishOperation)
		{
			if ($this->maxExecutionTime > (2*(time() - $this->startOperationTime)))
				$this->maxOperationCounter = $this->maxOperationCounter*2;
		}

		$message = new CAdminMessage($messageParams);
		return array(
			'sessID' => $this->sessID,
			'maxExecutionTime' => $this->maxExecutionTime,
			'maxOperationCounter' => $this->maxOperationCounter,
			'lastID' => $this->lastID,
			'allCounter' => $this->allCounter,
			'allOperationCounter' => $this->allOperationCounter,
			'errorCounter' => $this->errorCounter,
			'errors' => (!empty($this->stepErrors) ? '<p>'.implode('</p><p>', $this->stepErrors).'</p>' : ''),
			'finishOperation' => $this->finishOperation,
			'message' => $message->Show()
		);
	}

	static public function startOperation()
	{

	}

	public function finalOperation()
	{

	}

	static public function runOperation()
	{

	}

	public function run()
	{
		$this->startOperation();
		$this->runOperation();
		$this->finalOperation();
	}

	public function setProgressTemplates($template)
	{
		$template = (string)$template;
		if ($template !== '')
			$this->progressTemplate = $template.$this->defaultProgressTemplate;
	}

	public static function getAllCounter()
	{
		return 0;
	}

	public static function getDefaultExecutionTime()
	{
		$executionTime = (int)ini_get('max_execution_time');
		if ($executionTime <= 0)
			$executionTime = 60;
		return (int)(2*$executionTime/3);
	}
}

class CCatalogProductSetAvailable extends CCatalogStepOperations
{
	const SESSION_PREFIX = 'PS';

	static public function __construct($sessID, $maxExecutionTime, $maxOperationCounter)
	{
		$sessID = (string)$sessID;
		if ($sessID == '')
			$sessID = self::SESSION_PREFIX.time();
		parent::__construct($sessID, $maxExecutionTime, $maxOperationCounter);
	}

	public function runOperation()
	{
		global $DB;

		switch (ToUpper($DB->type))
		{
			case 'MYSQL':
				$tableName = 'b_catalog_discount';
				break;
			case 'MSSQL':
				$tableName = 'B_CATALOG_DISCOUNT';
				break;
			case 'ORACLE':
				$tableName = 'B_CATALOG_DISCOUNT';
				break;
		}

		$emptyList = true;
		CTimeZone::Disable();
		$filter = array('TYPE' => CCatalogProductSet::TYPE_SET, 'SET_ID' => 0);
		if ($this->lastID > 0)
			$filter['>ID'] = $this->lastID;
		$topCount = ($this->maxOperationCounter > 0 ? array('nTopCount' => $this->maxOperationCounter) : false);
		$productSetsIterator = CCatalogProductSet::getList(
			array('ID' => 'ASC'),
			array('TYPE' => CCatalogProductSet::TYPE_SET, 'SET_ID' => 0, '>ID' => $this->lastID),
			false,
			$topCount,
			array('ID', 'OWNER_ID', 'ITEM_ID', 'MODIFIED_BY', 'TIMESTAMP_X')
		);
		while ($productSet = $productSetsIterator->Fetch())
		{
			$emptyList = false;
			$productSet['MODIFIED_BY'] = (int)$productSet['MODIFIED_BY'];
			if ($productSet['MODIFIED_BY'] == 0)
				$productSet['MODIFIED_BY'] = false;
			CCatalogProductSet::recalculateSet($productSet['ID'], $productSet['ITEM_ID']);
			$arTimeFields = array(
				'~TIMESTAMP_X' => $DB->CharToDateFunction($productSet['TIMESTAMP_X'], "FULL"),
				'~MODIFIED_BY' => $productSet['MODIFIED_BY']
			);
			$strUpdate = $DB->PrepareUpdate($tableName, $arTimeFields);
			if (!empty($strUpdate))
			{
				$strQuery = "update ".$tableName." set ".$strUpdate." where ID = ".$productSet['ID'];
				$DB->Query($strQuery, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			}
			$this->allOperationCounter++;
			$this->lastID = $productSet['ID'];
			if ($this->maxExecutionTime > 0 && (time() - $this->startOperationTime > $this->maxExecutionTime))
				break;
		}
		CTimeZone::Enable();
		if ($emptyList)
			$this->finishOperation = true;
	}

	public static function getAllCounter()
	{
		return (int)CCatalogProductSet::getList(
			array(),
			array('TYPE' => CCatalogProductSet::TYPE_SET, 'SET_ID' => 0),
			array()
		);
	}
}