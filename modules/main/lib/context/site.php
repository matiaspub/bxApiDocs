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

	public function __construct($fields = null)
	{
		$this->fields = $fields;
	}

	public function getCulture()
	{
		return $this->culture;
	}

	/**
	 * @param Culture $culture
	 */
	public function setCulture(Culture $culture)
	{
		$this->culture = $culture;
	}

	public function getLanguage()
	{
		if(isset($this->fields["LANGUAGE_ID"]))
			return $this->fields["LANGUAGE_ID"];
		throw new Main\ObjectPropertyException("language");
	}

	public function getDocRoot()
	{
		if(isset($this->fields["DOC_ROOT"]))
			return $this->fields["DOC_ROOT"];
		throw new Main\ObjectPropertyException("docRoot");
	}

	public function getId()
	{
		if(isset($this->fields["ID"]))
			return $this->fields["ID"];
		throw new Main\ObjectPropertyException("id");
	}
}
