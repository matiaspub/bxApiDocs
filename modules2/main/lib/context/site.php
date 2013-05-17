<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Context;

use Bitrix\Main;

class Site
{
	/** @var Culture */
	protected $culture;

	protected $fields;

	static public function __construct($fields = null)
	{
		$this->fields = $fields;
	}

	static public function getCulture()
	{
		return $this->culture;
	}

	/**
	 * @param Culture $culture
	 */
	static public function setCulture(Culture $culture)
	{
		$this->culture = $culture;
	}

	static public function getLanguage()
	{
		if(isset($this->fields["LANGUAGE_ID"]))
			return $this->fields["LANGUAGE_ID"];
		throw new Main\ObjectPropertyException("language");
	}

	static public function getDocRoot()
	{
		if(isset($this->fields["DOC_ROOT"]))
			return $this->fields["DOC_ROOT"];
		throw new Main\ObjectPropertyException("docRoot");
	}

	static public function getId()
	{
		if(isset($this->fields["ID"]))
			return $this->fields["ID"];
		throw new Main\ObjectPropertyException("id");
	}
}
