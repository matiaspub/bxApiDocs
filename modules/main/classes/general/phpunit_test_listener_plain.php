<?php

class PHPUnit_Util_Log_BX_Plain extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener
{
	/**
	 * @var	string
	 */
	protected $currentTestSuiteName = '';

	/**
	 * @var	string
	 */
	protected $currentTestName = '';

	/**
	 * @var	 boolean
	 * @access  private
	 */
	protected $currentTestPass = TRUE;

	protected $messages;

	/**
	 * An error occurred.
	 *
	 * @param  PHPUnit_Framework_Test $test
	 * @param  Exception			  $e
	 * @param  float				  $time
	 */
	public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
	{
		$this->writeCase(
			'error',
			$time,
			PHPUnit_Util_Filter::getFilteredStacktrace($e, FALSE),
			$e->getMessage()." @ ".$e->getFile().":".$e->getLine(),
			$test
		);

		$this->currentTestPass = FALSE;
	}

	/**
	 * A failure occurred.
	 *
	 * @param  PHPUnit_Framework_Test				 $test
	 * @param  PHPUnit_Framework_AssertionFailedError $e
	 * @param  float								  $time
	 */
	public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
	{
		$this->write('fail: '.$e->getMessage());

		$trace = current(PHPUnit_Util_Filter::getFilteredStacktrace($e, FALSE));
		$this->write('trace: '.print_r($trace, 1));

		$this->currentTestPass = FALSE;
	}

	/**
	 * Incomplete test.
	 *
	 * @param  PHPUnit_Framework_Test $test
	 * @param  Exception			  $e
	 * @param  float				  $time
	 */
	public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
	{
		$this->writeCase('error', $time, array(), 'Incomplete Test', $test);

		$this->currentTestPass = FALSE;
	}

	/**
	 * Skipped test.
	 *
	 * @param  PHPUnit_Framework_Test $test
	 * @param  Exception			  $e
	 * @param  float				  $time
	 */
	public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
	{
		$this->writeCase('error', $time, array(), 'Skipped Test', $test);

		$this->currentTestPass = FALSE;
	}

	/**
	 * A testsuite started.
	 *
	 * @param  PHPUnit_Framework_TestSuite $suite
	 */
	public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
	{
		$this->currentTestSuiteName = $suite->getName();
		$this->currentTestName	  = '';

		$this->write(sprintf('suite started: %s (%d test%s)',
			$this->currentTestSuiteName,
			count($suite),
			count($suite) == 1 ? '' : 's'
		));
	}

	/**
	 * A testsuite ended.
	 *
	 * @param  PHPUnit_Framework_TestSuite $suite
	 */
	public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
	{
		$this->currentTestSuiteName = '';
		$this->currentTestName	  = '';
	}

	/**
	 * A test started.
	 *
	 * @param  PHPUnit_Framework_Test $test
	 */
	public function startTest(PHPUnit_Framework_Test $test)
	{
		$this->currentTestName = PHPUnit_Util_Test::describe($test);
		$this->currentTestPass = TRUE;

		$this->write("\n");

		$this->write(sprintf('test started: %s',
			$this->currentTestName
		));
	}

	/**
	 * A test ended.
	 *
	 * @param  PHPUnit_Framework_Test $test
	 * @param  float				  $time
	 */
	public function endTest(PHPUnit_Framework_Test $test, $time)
	{
		if ($this->currentTestPass)
		{
			$this->write(sprintf('test passed in %.3f sec.',
				$time
			));
		}

		echo $test->getActualOutput();
	}

	/**
	 * @param string $status
	 * @param float  $time
	 * @param array  $trace
	 * @param string $message
	 */
	protected function writeCase($status, $time, array $trace = array(), $message = '', $test = NULL)
	{
		$output = '';

		if ($test !== NULL && $test->hasOutput())
		{
			$output = $test->getActualOutput();
		}

		$this->write(
			array(
				'event'   => 'test',
				'suite'   => $this->currentTestSuiteName,
				'test'	=> $this->currentTestName,
				'status'  => $status,
				'time'	=> $time,
				'trace'   => $trace,
				'message' => PHPUnit_Util_String::convertToUtf8($message),
				'output'  => $output,
			)
		);
	}

	/**
	 * @param string $buffer
	 */
	public function write($buffer)
	{
		$this->messages[] = $buffer;
	}

	public function getMessages()
	{
		return $this->messages;
	}
}