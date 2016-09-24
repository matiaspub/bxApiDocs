<?php

namespace Bitrix\Sale\PaySystem\ExtraService;

use Bitrix\Sale\Internals\Input;

class Checkbox extends BaseExtraService
{
	/**
	 * @param array $initParams
	 * @param array $additionalParams
	 */
	static public function __construct(array $initParams, array $additionalParams = array())
	{
		$initParams['PARAMS']["TYPE"] = "Y/N";
		parent::__construct($initParams, $additionalParams);
	}

	public function getDefaultValue()
	{
		return $this->fields['DEFAULT_VALUE'];
	}

	public function getValue()
	{
		return $this->fields['PAYMENT.VALUE'];
	}


}