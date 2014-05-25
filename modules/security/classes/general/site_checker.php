<?
IncludeModuleLangFile(__FILE__);

class CSecuritySiteChecker
{
	const ADMIN_PAGE_URL = "/bitrix/admin/security_scanner.php";
	const CHECKING_REPEAT_TIME = 2592000; //60*60*24*30, one month
	const CACHE_DIR = "/security/site_checker";
	const CACHE_BASE_ID = "sec_site_check";
	const SESSION_DATA_KEY = "SECURITY_SITE_CHECKER";

	protected $allTests = array();

	protected $neededTests = "";
	protected $neededTestName = "";

	protected static $tableName = "b_security_sitecheck";
	protected static $dbFields = array("ID", "TEST_DATE", "RESULTS");
	/** @var CSecurityTemporaryStorage */
	protected $sessionData = null;
	protected $isCheckRequirementsNeeded = true;

	/**
	 * @param array $pTests
	 * @param bool $pIsFirstStart
	 * @param bool $pIsCheckRequirementsNeeded
	 * @throws Exception
	 */
	public function __construct($pTests = array(), $pIsFirstStart = false, $pIsCheckRequirementsNeeded = true)
	{
		$this->isCheckRequirementsNeeded = $pIsCheckRequirementsNeeded;
		$this->sessionData = new CSecurityTemporaryStorage(self::SESSION_DATA_KEY, $pIsFirstStart);
		$isOk = $this->initializeAvailableTests();
		if($isOk)
		{
			$isOk = $this->initializeNeededTests($pTests);
		}
		if(!$isOk)
		{
			throw new Exception('Error while initializing');
		}
	}

	/**
	 * Start testing and return results
	 * @param array $pParams
	 * @return array|bool
	 */
	public function startTesting($pParams = array())
	{
		$testName = $this->getCurrentTestName();
		if(!$testName)
			return false;
		if(!is_callable($testName, "check"))
			return false;

		$test = new $testName;
		if(!($test instanceof CSecurityBaseTest))
			return false;

		try
		{
			$testParams = $this->makeParamsForTest($testName, $pParams);
			if($this->isCheckRequirementsNeeded)
				$test->checkRequirements($testParams);

			$result = $test->check($testParams);
		}
		catch(CSecurityRequirementsException $exception)
		{
			$result = array(
				"name" => $test->getName(),
				"status" => true,
				"fatal_error_text" => $exception->getMessage()
			);
		}

		if($result)
		{
			if(!isset($result["in_progress"]) || !$result["in_progress"])
			{
				$this->finalizeLastTest();
				if(isset($result["timeout"]))
					$result["timeout"] = 0;
			}
		}
		return $result;
	}

	/**
	 * Run several tests
	 * @param string|array $pType
	 * @param bool $pIsFirstStart
	 * @param bool $pIsCheckRequirementsNeeded
	 * @return array|bool
	 */
	public static function runTestPackage($pType = "", $pIsFirstStart = false, $pIsCheckRequirementsNeeded = true)
	{
		try
		{
			$tests = CSecurityTestsPackage::getTestsPackage($pType);
			$dispatcher = new CSecuritySiteChecker($tests, $pIsFirstStart, $pIsCheckRequirementsNeeded);
			$result = $dispatcher->startTesting();
			$result["percent"] = $dispatcher->getPercent();
			if($dispatcher->isAllTestCompleted())
			{
				$result["all_done"] = "Y";
				self::clearCache();
			}
			else
			{
				$result["all_done"] = "N";
			}
		}
		catch(Exception $e)
		{
			$result = array("all_done" => "Y");
		}
		return $result;
	}

	/**
	 * Return current percent of complete testing
	 * @return int
	 */
	public function getPercent()
	{
		$totalTestsCount = count($this->neededTests);
		$remainingTestsCount = count($this->sessionData->getArray("NEEDED_TESTS"));
		return intval((($totalTestsCount - $remainingTestsCount) / $totalTestsCount) * 100);
	}

	/**
	 * Return saved testing results
	 * @param array $pFilter
	 * @param int $pMaxCount
	 * @return bool|CDBResult
	 */
	protected static function getList($pFilter = array(), $pMaxCount = 1)
	{
		/** @global CDataBase $DB */
		global $DB;

		$sqlQuery = "SELECT RESULTS, ".$DB->DateToCharFunction("TEST_DATE", "SHORT")." TEST_DATE FROM ".self::$tableName;
		if (is_array($pFilter) && !empty($pFilter))
		{
			$sqlWhereQuery = array();
			foreach($pFilter as $key => $value)
			{
				if (is_string($value) && $value != "" && in_array($key, self::$dbFields))
				{
					$sqlWhereQuery[] = $key."='".$DB->ForSql($value)."'";
				}
			}
			if(!empty($sqlWhereQuery))
			{
				$sqlQuery .= " WHERE ".GetFilterSqlSearch($sqlWhereQuery);
			}
		}

		$sqlQuery .= " ORDER BY ID desc";
		$sqlQuery = $DB->TopSql($sqlQuery, $pMaxCount);
		$arResult = $DB->Query($sqlQuery, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $arResult;
	}

	/**
	 * Add new testing results ro DB
	 * @param array $pResults
	 * @return bool
	 */
	public static function addResults($pResults)
	{
		if (!isset($pResults) || !is_array($pResults))
			return false;

		/** @global CDataBase $DB */
		global $DB;
		$fields = array("RESULTS" => serialize($pResults));
		$insertValues = $DB->PrepareInsert(self::$tableName, $fields);
		$testingDate = self::getFormatedDate(time());
		$sqlQuery = "INSERT INTO ".self::$tableName."(".$insertValues[0].", TEST_DATE) ".
			"VALUES(".$insertValues[1].", ".$testingDate.")";
		/** @var CDBResult $queryResult */
		$queryResult = $DB->QueryBind($sqlQuery, $fields);
		if($queryResult && $queryResult->result)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Return last saved testing results
	 * @return array
	 */
	public static function getLastTestingInfo()
	{
		/** @global CCacheManager $CACHE_MANAGER*/
		global $CACHE_MANAGER;
		$cacheId = self::CACHE_BASE_ID."_last_results";

		if($CACHE_MANAGER->read(self::CHECKING_REPEAT_TIME, $cacheId, self::CACHE_DIR))
		{
			$lastResult = $CACHE_MANAGER->get($cacheId);
		}
		else
		{
			$lastResult = array();
			$dbResults = self::getList();
			if($dbResults)
			{
				$result = $dbResults->fetch();
				if($result && isset($result["RESULTS"]))
				{
					if(checkSerializedData($result["RESULTS"]))
					{
						$lastResult["results"] = unserialize($result["RESULTS"]);
					}
				}
				if($result && isset($result["TEST_DATE"]))
				{
					$lastResult["test_date"] = $result["TEST_DATE"];
				}
			}

			$CACHE_MANAGER->set($cacheId, $lastResult);
		}
		return (is_array($lastResult) ? $lastResult: array()) ;
	}

	/**
	 *
	 */
	public static function clearTemporaryData()
	{
		CSecurityTemporaryStorage::ClearAll();
	}

	/**
	 * Return true if administrator must run testing
	 * @return bool
	 */
	public static function isNewTestNeeded()
	{
		/**
		 * @global CCacheManager $CACHE_MANAGER
		 * @global CDataBase $DB
		 */
		global $DB, $CACHE_MANAGER;
		$cacheId = self::CACHE_BASE_ID."_last_check";

		if($CACHE_MANAGER->read(self::CHECKING_REPEAT_TIME, $cacheId, self::CACHE_DIR))
		{
			$result = $CACHE_MANAGER->get($cacheId);
		}
		else
		{
			$minimalDate = self::getFormatedDate(time() - self::CHECKING_REPEAT_TIME);
			$sqlQuery = "
				SELECT COUNT(ID) AS COUNT
				FROM
					".self::$tableName."
				WHERE
					TEST_DATE >= ".$minimalDate."
			";

			$res = $DB->query($sqlQuery, false, $err_mess = "FILE: ".__FILE__."<br>LINE: ".__LINE__);
			$result = true;
			if($count = $res->fetch())
			{
				if($count["COUNT"] > 0)
				{
					$result = false;
				}
			}

			$CACHE_MANAGER->set($cacheId, $result);
		}

		return $result;
	}

	/**
	 * @param $timestamp
	 * @return string
	 */
	protected static function getFormatedDate($timestamp)
	{
		/** @global CDataBase $DB */
		global $DB;
		return $DB->CharToDateFunction(ConvertTimeStamp($timestamp + CTimeZone::GetOffset(), "FULL"));
	}
	/**
	 *
	 */
	protected static function clearCache()
	{
		/** @global CCacheManager $CACHE_MANAGER*/
		global $CACHE_MANAGER;
		$CACHE_MANAGER->CleanDir(self::CACHE_DIR);
	}

	/**
	 *
	 */
	static public function isAnyCheckingExists()
	{

	}
	/**
	 *
	 */
	protected function finalizeLastTest()
	{
		$this->sessionData->clearKey("STEP");
		$nextTestName = $this->sessionData->getArrayPop("NEEDED_TESTS");
		if(is_string($nextTestName) && $nextTestName != "")
		{
			$this->sessionData->setData("CUR_TEST", $nextTestName);
		}
		else
		{
			$this->sessionData->setData("ALL_DONE", true);
		}
	}

	/**
	 * @return bool
	 */
	public function isAllTestCompleted()
	{
		return $this->sessionData->getBool("ALL_DONE");
	}


	/**
	 * @param array $pResults
	 * @return int
	 */
	public static function calculateCriticalResults($pResults = array())
	{
		if(empty($pResults))
		{
			$testingInfo = CSecuritySiteChecker::getLastTestingInfo();
			if(isset($testingInfo["results"]))
			{
				$results = $testingInfo["results"];
			}
			else
			{
				$results = array();
			}
		}
		else
		{
			$results = $pResults;
		}

		$criticalResultsCount = 0;
		foreach($results as $result)
		{
			if(isset($result["critical"]) && $result["critical"] === CSecurityCriticalLevel::HIGHT)
				$criticalResultsCount++;
		}

		return $criticalResultsCount;
	}

	/**
	 * @return bool
	 */
	public static function OnAdminInformerInsertItems()
	{
		/** @global CUser $USER */
		global $USER;
		if(!$USER->isAdmin())
			return false;

		if(!self::isNewTestNeeded())
			return false;

		try
		{
			$adminUrl = self::ADMIN_PAGE_URL."?lang=".LANGUAGE_ID;
			$htmlText = '
<div class="adm-informer-item-section">
	<span class="adm-informer-item-l">
		<span>'.GetMessage("SEC_CHECKER_INFORMER_DESCRIPTION").'</span>
	</span>
</div>
';
			$WAFAIParams = array(
				"TITLE" => GetMessage("SEC_CHECKER_INFORMER_TITLE"),
				"COLOR" => "blue",
				"ALERT" =>  true,
				"HTML" => $htmlText,
				"FOOTER" => '<a href="'.$adminUrl.'">'.GetMessage("SEC_CHECKER_INFORMER_LINK").'</a>'
			);

		}
		catch (Exception $e)
		{
			$WAFAIParams = array(
				"TITLE" => GetMessage("SEC_CHECKER_INFORMER_TITLE")." - ".GetMessage("top_panel_ai_title_err"),
				"ALERT" =>  true,
				"HTML" => $e->getMessage()
			);
		}

		CAdminInformer::AddItem($WAFAIParams);
		return true;
	}

	/**
	 * @return string
	 */
	protected function getCurrentTestName()
	{
		if($this->sessionData->isEmpty("CUR_TEST"))
		{
			$this->finalizeLastTest();
		}
		return $this->sessionData->getString("CUR_TEST");
	}

	/**
	 * @param string $pTestName
	 * @param array $pParams
	 * @return array
	 */
	protected function makeParamsForTest($pTestName, $pParams)
	{
		if(is_array($pParams) && !empty($pParams))
		{
			return $pParams;
		}
		else
		{
			return array(
				"TEST_ID" => md5($pTestName),
				"STEP" => $this->getNextStep()
			);
		}
	}

	/**
	 * @return int
	 */
	protected function getNextStep()
	{
		if($this->sessionData->isExists("STEP"))
		{
			$this->sessionData->increment("STEP");
		}
		else
		{
			$this->sessionData->setData("STEP", 0);
		}
		return $this->sessionData->getInt("STEP");
	}


	/**
	 * @return bool
	 */
	protected function initializeAvailableTests()
	{
		$this->allTests = CSecurityTestsPackage::getAllTests();
		return true;
	}

	/**
	 * @param string|array $pTests
	 * @return bool
	 */
	protected function initializeNeededTests($pTests)
	{
		if(is_string($pTests) && $pTests != "")
		{
			$this->neededTests = array($pTests);
		}
		elseif(is_array($pTests) && !empty($pTests))
		{
			$this->neededTests = $pTests;
		}
		else
		{
			$this->neededTests = $this->allTests;
		}

		if(!$this->sessionData->isExists("NEEDED_TESTS"))
		{
			$this->sessionData->setData("NEEDED_TESTS", $this->neededTests);
		}
		return true;
	}
}
