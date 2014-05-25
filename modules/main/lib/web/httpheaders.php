<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main\Web;

class HttpHeaders
{
	protected $headers = array();

	static public function __construct()
	{
	}

	public function add($name, $value)
	{
		$name = str_replace(array("\r", "\n"), "", $name);
		$value = str_replace(array("\r", "\n"), "", $value);
		$nameLower = strtolower($name);

		if(!isset($this->headers[$nameLower]))
		{
			$this->headers[$nameLower] = array(
				"name" => $name,
				"values" => array(),
			);
		}
		$this->headers[$nameLower]["values"][] = $value;
	}

	public function set($name, $value)
	{
		$name = str_replace(array("\r", "\n"), "", $name);
		$value = str_replace(array("\r", "\n"), "", $value);
		$nameLower = strtolower($name);

		$this->headers[$nameLower] = array(
			"name" => $name,
			"values" => array($value),
		);
	}

	public function get($name, $returnArray = false)
	{
		$nameLower = strtolower($name);

		if(isset($this->headers[$nameLower]))
		{
			if($returnArray)
			{
				return $this->headers[$nameLower]["values"];
			}
			return $this->headers[$nameLower]["values"][0];
		}
		return null;
	}

	public function clear()
	{
		$this->headers = array();
	}

	public function toString()
	{
		$str = "";
		foreach($this->headers as $header)
		{
			foreach($header["values"] as $value)
			{
				$str .= $header["name"].": ".$value."\r\n";
			}
		}
		return $str;
	}

	public function toArray()
	{
		return $this->headers;
	}
}
