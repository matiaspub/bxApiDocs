<?php

namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Services\Base;
use \Bitrix\Sale\Services\PaySystem\Restrictions;

Loc::loadMessages(__FILE__);

class PersonType extends Restrictions\PersonType
{
	/**
	 * @param $mode
	 * @return mixed
	 */
	public static function getSeverity($mode)
	{
		$result = Base\RestrictionManager::SEVERITY_STRICT;

		if($mode == Base\RestrictionManager::MODE_MANAGER)
			return Base\RestrictionManager::SEVERITY_SOFT;

		return $result;
	}
}