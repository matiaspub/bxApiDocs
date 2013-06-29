<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Context;

use Bitrix\Main;
use Bitrix\Main\Localization\CultureTable;

class Culture
{
	protected $fields;

	public function __construct($fields = null)
	{
		if($fields !== null)
		{
			$this->fields = $fields;
		}
		else
		{
			$this->fields = array(
				"FORMAT_DATE" => "MM/DD/YYYY",
				"FORMAT_DATETIME" => "MM/DD/YYYY HH:MI:SS",
				"FORMAT_NAME" => "#NAME# #LAST_NAME#",
				"CHARSET" => "UTF-8",
				"DIRECTION" => 'Y',
				"WEEK_START" => 0
			);
		}
	}

	/**
	 * @param int $cultureId
	 * @return Culture
	 */
	public static function wakeUp($cultureId)
	{
		$cultureDb = CultureTable::getById($cultureId);
		if(($arCulture = $cultureDb->fetch()))
		{
			return new static($arCulture);
		}
		return null;
	}

	public function getDateTimeFormat()
	{
		if(isset($this->fields["FORMAT_DATETIME"]))
			return $this->fields["FORMAT_DATETIME"];
		throw new Main\ObjectPropertyException("dateTimeFormat");
	}

	public function getDateFormat()
	{
		if(isset($this->fields["FORMAT_DATE"]))
			return $this->fields["FORMAT_DATE"];
		throw new Main\ObjectPropertyException("dateFormat");
	}

	public function getCharset()
	{
		if(isset($this->fields["CHARSET"]))
			return $this->fields["CHARSET"];
		throw new Main\ObjectPropertyException("charset");
	}
}
