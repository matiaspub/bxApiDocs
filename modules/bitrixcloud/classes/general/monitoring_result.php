<?php

interface CBitrixCloudMonitoring_Access extends Iterator, ArrayAccess
{
	// new stuff
}

class CBitrixCloudMonitoringTest
{
	private $name = "";
	private $status = "";
	private $time = 0;
	private $uptime = "";
	private $result = "";

	/**
	 *
	 * @param string $name
	 * @param string $status
	 * @param int $time UTC timestamp
	 * @param string $result
	 * @return void
	 *
	 */
	public function __construct($name, $status, $time, $uptime, $result)
	{
		$this->name = $name;
		$this->status = $status;
		$this->time = $time;
		$this->uptime = $uptime;
		$this->result = $result;
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getUptime()
	{
		return $this->uptime;
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getTime()
	{
		return $this->time;
	}

	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudMonitoringTest
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		return new CBitrixCloudMonitoringTest(
			$node->getAttribute("id"),
			$node->getAttribute("status") == 2? CBitrixCloudMonitoringResult::RED_LAMP: CBitrixCloudMonitoringResult::GREEN_LAMP,
			strtotime($node->getAttribute("time")),
			$node->getAttribute("uptime"),
			$node->textContent()
		);
	}
}

class CBitrixCloudMonitoringDomainResult implements CBitrixCloudMonitoring_Access
{
	/** @var string $name */
	private $name = "";
	/** @var array[int]CBitrixCloudMonitoringTest $tests */
	private $tests = /*.(array[int]CBitrixCloudMonitoringTest.*/
		array();

	/**
	 *
	 * @return string
	 *
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getStatus()
	{
		foreach ($this->tests as $testName => $testResult)
		{
			if ($testResult->getStatus() === CBitrixCloudMonitoringResult::RED_LAMP)
				return CBitrixCloudMonitoringResult::RED_LAMP;
		}
		return CBitrixCloudMonitoringResult::GREEN_LAMP;
	}

	/**
	 *
	 * @param string $name
	 * @param array [int]CBitrixCloudMonitoringTest $tests
	 * @return void
	 *
	 */
	public function __construct($name, array $tests)
	{
		$this->name = $name;
		$this->setTests($tests);
	}

	/**
	 *
	 * @param string $testName
	 * @return CBitrixCloudMonitoringTest
	 *
	 */
	public function getTestByName($testName)
	{
		return $this->tests[$testName];
	}

	/**
	 *
	 * @return array[int]CBitrixCloudMonitoringTest
	 *
	 */
	public function getTests()
	{
		return $this->tests;
	}

	/**
	 *
	 * @param array [int]CBitrixCloudMonitoringTest $tests
	 * @return CBitrixCloudMonitoringDomainResult
	 *
	 */
	public function setTests(array $tests)
	{
		foreach ($tests as $test)
		{
			if (
				is_object($test)
				&& $test instanceof CBitrixCloudMonitoringTest
			)
			{
				$this->tests[$test->getName()] = $test;
			}
		}
		return $this;
	}

	public function saveToOptions(CBitrixCloudOption $option)
	{
		$tests = array();
		foreach ($this->tests as $testName => $testResult)
		{
			$tests[$testName] = serialize(array(
				"status" => $testResult->getStatus(),
				"time" => $testResult->getTime(),
				"uptime" => $testResult->getUptime(),
				"result" => $testResult->getResult(),
			));
		}
		$option->setArrayValue($tests);
	}

	public static function loadFromOptions($name, CBitrixCloudOption $option)
	{
		$tests = array();
		foreach ($option->getArrayValue() as $testName => $testResult)
		{
			$testResult = unserialize($testResult);
			if (is_array($testResult))
			{
				$test = new CBitrixCloudMonitoringTest(
					$testName,
					$testResult["status"],
					$testResult["time"],
					$testResult["uptime"],
					$testResult["result"]
				);
				$tests[$test->getName()] = $test;
			}
		}
		return new CBitrixCloudMonitoringDomainResult($name, $tests);
	}

	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudMonitoringDomainResult
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		$name = $node->getAttribute("name");
		$tests = array();
		foreach ($node->children() as $nodeTest)
		{
			$tests[] = CBitrixCloudMonitoringTest::fromXMLNode($nodeTest);
		}
		return new CBitrixCloudMonitoringDomainResult($name, $tests);
	}

	public function rewind()
	{
		reset($this->tests);
	}

	public function current()
	{
		return current($this->tests);
	}

	public function key()
	{
		return key($this->tests);
	}

	public function next()
	{
		next($this->tests);
	}

	public function valid()
	{
		return key($this->tests) !== null;
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
		{
			$this->tests[] = $value;
		}
		else
		{
			$this->tests[$offset] = $value;
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->tests[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->tests[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->tests[$offset])? $this->tests[$offset]: null;
	}
}

class CBitrixCloudMonitoringResult implements CBitrixCloudMonitoring_Access
{
	const GREEN_LAMP = 'green';
	const RED_LAMP = 'red';

	private $domains = /*.(array[string]CBitrixCloudMonitoringDomainResult).*/
		array();

	/**
	 *
	 * @param CBitrixCloudMonitoringDomainResult $domainResult
	 * @return CBitrixCloudMonitoringResult
	 *
	 */
	public function addDomainResult(CBitrixCloudMonitoringDomainResult $domainResult)
	{
		$this->domains[$domainResult->getName()] = $domainResult;
		return $this;
	}

	/**
	 *
	 * @param string $domainName
	 * @return CBitrixCloudMonitoringDomainResult
	 *
	 */
	public function getResultByDomainName($domainName)
	{
		return $this->domains[$domainName];
	}

	/**
	 *
	 * @return string
	 *
	 */
	public function getStatus()
	{
		foreach ($this->domains as $domainName => $domainResult)
		{
			if ($domainResult->getStatus() === CBitrixCloudMonitoringResult::RED_LAMP)
				return CBitrixCloudMonitoringResult::RED_LAMP;
		}
		return CBitrixCloudMonitoringResult::GREEN_LAMP;
	}

	public static function isExpired()
	{
		$time = CBitrixCloudOption::getOption("monitoring_expire_time")->getIntegerValue();
		return ($time < time());
	}

	public static function getExpirationTime()
	{
		return CBitrixCloudOption::getOption("monitoring_expire_time")->getIntegerValue();
	}

	public static function setExpirationTime($time)
	{
		$time = intval($time);
		CBitrixCloudOption::getOption("monitoring_expire_time")->setStringValue($time);
		return $time;
	}

	public static function loadFromOptions()
	{
		$domains = new CBitrixCloudMonitoringResult;
		foreach (CBitrixCloudOption::getOption("monitoring_result")->getArrayValue() as $i => $domainName)
		{
			$domains->addDomainResult(CBitrixCloudMonitoringDomainResult::loadFromOptions(
				$domainName,
				CBitrixCloudOption::getOption("monitoring_result_$i")
			));
		}
		return $domains;
	}

	public function saveToOptions()
	{
		$domainNames = array_keys($this->domains);
		CBitrixCloudOption::getOption("monitoring_result")->setArrayValue($domainNames);
		foreach ($domainNames as $i => $domainName)
		{
			$this->domains[$domainName]->saveToOptions(
				CBitrixCloudOption::getOption("monitoring_result_$i")
			);
		}
	}

	/**
	 *
	 * @param CDataXMLNode $node
	 * @return CBitrixCloudMonitoringResult
	 *
	 */
	public static function fromXMLNode(CDataXMLNode $node)
	{
		$domains = new CBitrixCloudMonitoringResult;
		if (is_array($node->children()))
		{
			foreach ($node->children() as $sub_node)
			{
				$domains->addDomainResult(CBitrixCloudMonitoringDomainResult::fromXMLNode($sub_node));
			}
		}
		return $domains;
	}

	public function rewind()
	{
		reset($this->domains);
	}

	public function current()
	{
		return current($this->domains);
	}

	public function key()
	{
		return key($this->domains);
	}

	public function next()
	{
		next($this->domains);
	}

	public function valid()
	{
		return key($this->domains) !== null;
	}

	public function offsetSet($offset, $value)
	{
		if (is_null($offset))
		{
			$this->domains[] = $value;
		}
		else
		{
			$this->domains[$offset] = $value;
		}
	}

	public function offsetExists($offset)
	{
		return isset($this->domains[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->domains[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->domains[$offset])? $this->domains[$offset]: null;
	}
}
