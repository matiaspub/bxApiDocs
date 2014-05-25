<?php
namespace Bitrix\Scale;

/**
 * Class ShellAdapter
 * Executes shell commands
 * @package Bitrix\Scale
 */
class ShellAdapter
{
	const SUCCESS_RESULT = 0;

	protected $resOutput = "";
	protected $resError = "";

	/**
	 * Checks and escapes command
	 * @param string $command
	 * @return string escapedSring
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function prepareExecution($command)
	{
		if(strlen($command) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("command");

		$this->resOutput = "";
		$this->resError = "";

		return  escapeshellcmd($command);
	}

	/**
	 * Starts execution fo shell command
	 * Results can be obtained by another special commands
	 * @param $command
	 * @return true;
	 */
	public function asyncExec($command)
	{
		$outputPath = "/dev/null";
		$command = $this->prepareExecution($command);
		exec($command. " > ".$outputPath." 2>&1 &");
		return true;
	}

	/**
	 * @return string Last command output
	 */
	public function getLastOutput()
	{
		return $this->resOutput;
	}

	/**
	 * @return string last error output
	 */
	public function getLastError()
	{
		return $this->resError;
	}

	/**
	 * Executes shell command & return shell command execution result
	 * @param string $command
	 * @return bool
	 */
	public function syncExec($command)
	{
		$command = $this->prepareExecution($command);
		$retVal = 1;

		$descriptorspec = array(
			0 => array("pipe", "r"),  // stdin
			1 => array("pipe", "w"),  // stdout
			2 => array("pipe", "w") // stderr
		);

		$pipes = array();
		$process = proc_open('/bin/bash', $descriptorspec, $pipes);

		if (is_resource($process))
		{
			fwrite($pipes[0], $command);
			fclose($pipes[0]);

			$this->resOutput = stream_get_contents($pipes[1]);
			fclose($pipes[1]);

			$this->resError = stream_get_contents($pipes[2]);
			fclose($pipes[2]);

			$retVal = proc_close($process);
		}

		return $retVal == static::SUCCESS_RESULT;
	}
}