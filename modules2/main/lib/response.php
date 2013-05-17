<?php
namespace Bitrix\Main;

abstract class Response
{
	private $buffer;

	/**
	 * @var Context
	 */
	protected $context;

	const SPREAD_SITES = 2;
	const SPREAD_DOMAIN = 4;

	static public function __construct(Context $context)
	{
		$this->context = $context;
	}

	static public function clear()
	{

	}

	public function redirect($url)
	{

	}

	static public function flush($text)
	{
		$this->writeHeaders();
		$this->writeBody($text);
	}

	protected abstract function writeHeaders();

	private function writeBody($text)
	{
		echo $text;
	}

}
