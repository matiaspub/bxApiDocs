<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage security
 * @copyright 2001-2013 Bitrix
 */

/**
 * Class CSecurityCloudMonitorTest
 * @since 12.5.0
 */
class CSecurityCloudMonitorTest
	extends CSecurityBaseTest
{
	const DEFAULT_RECEIVE_RESULTS_TIME = 15;
	const MAX_CHECKING_REQUEST_REPEATE_COUNT = 5;
	const MAX_RESULTS_REQUEST_REPEATE_COUNT = 50;

	protected $internalName = 'CloudMonitor';
	/** @var CSecurityTemporaryStorage */
	protected $sessionData = null;
	protected $checkingResults = array();
	protected $protocolVersion = 2;

	static public function __construct()
	{
		IncludeModuleLangFile(__FILE__);
	}

	static public function checkRequirements($params = array())
	{
		if(!function_exists('json_decode'))
			throw new CSecurityRequirementsException(GetMessage('SECURITY_SITE_CHECKER_CLOUD_JSON_UNAVAILABLE'));
		return true;
	}

	/**
	 * Run test and return results
	 * @param array $params
	 * @return array
	 */
	public function check($params)
	{
		$this->initializeParams($params);
		$testID = $this->getParam('TEST_ID', $this->internalName);
		$this->sessionData = new CSecurityTemporaryStorage($testID);

		if($this->isCheckRequestNotSended())
		{
			$this->doCheckRequest();
		}
		else
		{
			$this->receiveResults();
		}

		return $this->getResult();
	}

	/**
	 * Return checking results with default values (if it not present before)
	 * @return array
	 */
	protected function getResult()
	{
		if(!is_array($this->checkingResults))
			$this->checkingResults = array();
		if(!isset($this->checkingResults['name']))
			$this->checkingResults['name'] = $this->getName();
		if(!isset($this->checkingResults['timeout']))
			$this->checkingResults['timeout'] = $this->getTimeout();
		if(!isset($this->checkingResults['status']))
			$this->checkingResults['in_progress'] = true;
		return $this->checkingResults;
	}

	/**
	 * Try to receive checking results from Bitrix
	 */
	protected function receiveResults()
	{
		if($this->sessionData->getInt('results_repeat_count') > self::MAX_RESULTS_REQUEST_REPEATE_COUNT)
			$this->stopChecking(GetMessage('SECURITY_SITE_CHECKER_CLOUD_UNAVAILABLE'));

		$response = new CSecurityCloudMonitorRequest('get_results', $this->protocolVersion, $this->getCheckingToken());
		if($response->isOk())
		{
			$this->sessionData->flushData();
			$results = $response->getValue('results');
			if(is_array($results) && count($results) > 0)
			{
				$isSomethingFound = true;
				$problemCount = count($results);
				$errors = self::formatResults($results);
			}
			else
			{
				$isSomethingFound = false;
				$problemCount = 0;
				$errors = array();
			}
			$this->setCheckingResult(array(
				'problem_count' => $problemCount,
				'errors' => $errors,
				'status' => !$isSomethingFound
			));

		}
		elseif($response->isFatalError())
		{
			$this->stopChecking($response->getValue('error_text'));
		}
		else
		{
			$this->sessionData->increment('results_repeat_count');
		}
	}

	/**
	 * @return bool
	 */
	protected function isCheckRequestNotSended()
	{
		return ($this->getParam('STEP', 0) === 0 || $this->sessionData->getBool('repeat_request'));
	}

	/**
	 * Try to start checking (send special request to Bitrix)
	 */
	protected function doCheckRequest()
	{
		$response = new CSecurityCloudMonitorRequest('check', $this->protocolVersion);
		if($response->isOk())
		{
			$this->sessionData->flushData();
			$this->setTimeOut($response->getValue('processing_time'));
			$this->setCheckingToken($response->getValue('testing_token'));
		}
		elseif($response->isFatalError())
		{
			$this->stopChecking($response->getValue('error_text'));
		}
		else
		{
			if($this->sessionData->getBool('repeat_request'))
			{
				if($this->sessionData->getInt('check_repeat_count') > self::MAX_CHECKING_REQUEST_REPEATE_COUNT)
				{
					$this->stopChecking(GetMessage('SECURITY_SITE_CHECKER_CLOUD_UNAVAILABLE'));
				}
				else
				{
					$this->sessionData->increment('check_repeat_count');
				}
			}
			else
			{
				$this->sessionData->flushData();
				$this->sessionData->setData('repeat_request', true);
			}
		}
	}

	/**
	 * @param string $token
	 */
	protected function setCheckingToken($token)
	{
		if(is_string($token) && $token != '')
		{
			$this->sessionData->setData('testing_token', $token);
		}
	}

	/**
	 * @return string
	 */
	protected function getCheckingToken()
	{
		return $this->sessionData->getString('testing_token');
	}

	/**
	 * @param int $timeOut
	 */
	protected function setTimeOut($timeOut)
	{
		if(intval($timeOut) > 0 )
		{
			$this->sessionData->setData('timeout', $timeOut);
		}
	}

	/**
	 * @param array $result
	 */
	protected function setCheckingResult(array $result)
	{
		$this->checkingResults = $result;
	}

	/**
	 * @param string $message
	 */
	protected function stopChecking($message = '')
	{
		$this->checkingResults['status'] = true;
		$this->checkingResults['fatal_error_text'] = $message;
	}

	/**
	 * Format test results for checking output
	 * @param array $results
	 * @return array
	 */
	protected static function formatResults(array $results)
	{
		$formattedResult = array();
		$count = 0;
		foreach($results as $result)
		{
			if(isset($result['name']))
			{
				$formattedResult[$count]['title'] = $result['name'];
				$formattedResult[$count]['critical'] = isset($result['critical'])? $result['critical']: CSecurityCriticalLevel::LOW;
			}
			if(isset($result['detail']))
			{
				$formattedResult[$count]['detail'] = $result['detail'];
			}
			if(isset($result['recommendation']))
			{
				$formattedResult[$count]['recommendation'] = $result['recommendation'];
			}
			if(isset($result['additional_info']))
			{
				$formattedResult[$count]['additional_info'] = $result['additional_info'];
			}
			$count++;
		}
		return $formattedResult;
	}

	/**
	 * @return int
	 */
	protected function getTimeout()
	{
		if($this->sessionData->getString('timeout') > 0)
		{
			return intval($this->sessionData->getString('timeout'));
		}
		else
		{
			return self::DEFAULT_RECEIVE_RESULTS_TIME;
		}
	}
}
