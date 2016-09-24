<?php
namespace Bitrix\Main;

abstract class Response
{
	/**
	 * @var Context
	 */
	protected $context;

	const SPREAD_SITES = 2;
	const SPREAD_DOMAIN = 4;

	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	public function clear()
	{

	}

	static public function redirect($url)
	{

	}

	public function flush($text = '')
	{
		$this->writeHeaders();
		$this->writeBody($text);
	}

	protected abstract function writeHeaders();

	protected function writeBody($text)
	{
		echo $text;
	}

}
