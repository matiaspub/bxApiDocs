<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityTaintCheckingTest
 * @since 12.5.0
 */
class CSecurityTaintCheckingTest
	extends CSecurityBaseTest
{
	const REQUEST_TIMEOUT = 3;
	protected $internalName = "TaintCheckingTest";

	static public function __construct()
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/vuln_scanner.php");
		IncludeModuleLangFile(__FILE__);
	}

	/**
	 * Check test requirements (e.g. max_execution_time)
	 *
	 * @param array $params
	 * @throws CSecurityRequirementsException
	 * @return bool
	 */
	static public function checkRequirements($params = array())
	{
		if(extension_loaded('tokenizer') !== true)
			throw new CSecurityRequirementsException(GetMessage("SECURITY_SITE_CHECKER_TAINT_TOKENIZER_NOT_FOUND"));
		$maxExecutionTime = ini_get("max_execution_time");
		if($maxExecutionTime > 0 && $maxExecutionTime <= 20)
			throw new CSecurityRequirementsException(GetMessage("SECURITY_SITE_CHECKER_TAINT_EXECUTION_TIME"));
		$memoryLimit = CUtil::Unformat(ini_get("memory_limit"));
		if($memoryLimit > 0 && $memoryLimit <= 250 * 1024 * 1024)
			throw new CSecurityRequirementsException(GetMessage("SECURITY_SITE_CHECKER_TAINT_MEMORY_LIMIT"));
		return true;
	}

	/**
	 * Run test and return results
	 * @param array $params
	 * @return array
	 */
	public function check($params)
	{
		$dirtyResults = CQAACheckListTests::checkVulnerabilities($params);
		$result = $this->formatResults($dirtyResults);
		return $result;
	}

	/**
	 * @param array $pResults
	 * @return array
	 */
	protected function formatResults($pResults)
	{
		$result = array();
		$result["name"] = $this->getName();
		if(isset($pResults["STATUS"]))
		{
			$result["status"] = $pResults["STATUS"];
		}

		if(isset($pResults["IN_PROGRESS"]))
		{
			$result["in_progress"] = $pResults["IN_PROGRESS"];
			$result["timeout"] = self::REQUEST_TIMEOUT;
		}
		
		elseif(!$result["status"])
		{
			$result["problem_count"] = 1;
		}

		if(isset($pResults["MESSAGE"]))
		{
			$result["errors"] = self::formatErrors($pResults["MESSAGE"]);
		}
		else
		{
			$result["errors"] = array();
		}

		return $result;
	}

	/**
	 * @param array $pErrors
	 * @return array
	 */
	protected static function formatErrors($pErrors)
	{
		$result = array();
		if(isset($pErrors["PROBLEM_COUNT"]) && intval($pErrors["PROBLEM_COUNT"]) > 0)
		{
			$problemCount = $pErrors["PROBLEM_COUNT"];
		}
		else
		{
			$problemCount = 0;
		}
		$result["title"] = GetMessage("SECURITY_SITE_CHECKER_TAINT_TITLE", array("#COUNT#" => $problemCount));
		$result["critical"] = CSecurityCriticalLevel::HIGHT;
		$result["recommendation"] = GetMessage("SECURITY_SITE_CHECKER_TAINT_RECOMMENDATION");
		$result["detail"] = self::formatDetail($pErrors["DETAIL"]);
		return array($result);
	}

	/**
	 * @deprecated
	 * @param string $pDetail
	 * @return string
	 */
	protected static function formatDetail($pDetail)
	{
		$detail = preg_replace("#
				(<span\sclass=\"checklist-vulnscan-filename\">[^<]+</span>\s*)
				(<div\sid=\"[^\"]+\">\s*<div\sclass=\"checklist-vulnscan-vulnblock\">\s*)
				(<div\sclass=\"checklist-vulnscan-vulnscan-blocktitle\">[^<]+</div>)
			#xis", "\\3\\2\\1", $pDetail);
		return $detail;
	}

}
