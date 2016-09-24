<?php
namespace Bitrix\Sale\Services\Company\Restrictions;

use Bitrix\Main;
use Bitrix\Sale\Internals;
use Bitrix\Sale\Services\Base\RestrictionManager;

class Manager extends RestrictionManager
{
	protected static $classNames = null;

	/**
	 * @param array $parameters
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList(array $parameters)
	{
		return Internals\CompanyTable::getList($parameters);
	}

	/**
	 * @return array
	 */
	public static function getBuildInRestrictions()
	{
		return array(
			'\Bitrix\Sale\Services\Company\Restrictions\Currency' => 'lib/services/company/restrictions/currency.php',
			'\Bitrix\Sale\Services\Company\Restrictions\Site' => 'lib/services/company/restrictions/site.php',
			'\Bitrix\Sale\Services\Company\Restrictions\EntityType' => 'lib/services/company/restrictions/entitytype.php',
			'\Bitrix\Sale\Services\Company\Restrictions\Location' => 'lib/services/company/restrictions/location.php',
			'\Bitrix\Sale\Services\Company\Restrictions\PaySystem' => 'lib/services/company/restrictions/paysystem.php',
			'\Bitrix\Sale\Services\Company\Restrictions\Delivery' => 'lib/services/company/restrictions/delivery.php',
			'\Bitrix\Sale\Services\Company\Restrictions\PersonType' => 'lib/services/company/restrictions/persontype.php',
			'\Bitrix\Sale\Services\Company\Restrictions\Price' => 'lib/services/company/restrictions/price.php',
		);
	}

	/**
	 * @return string
	 */
	public static function getEventName()
	{
		return 'onSaleCompanyRulesClassNamesBuildList';
	}

	/**
	 * @return int
	 */
	protected static function getServiceType()
	{
		return self::SERVICE_TYPE_COMPANY;
	}
}