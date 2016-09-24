<?
namespace Bitrix\Sale\Delivery\Services;

use Bitrix\Sale\Delivery\Restrictions;

/**
 * Class NewToAutomatic
 * Proxy for using new delivery services with old API for back compatibility.
 * Desn't support updating, saving etc. via admin pages.
 * Just show details & calculate for public components.
 * @package Bitrix\Sale\Delivery\Services
 */
class NewToAutomatic
{
	/** @var Base $service Real service, we want to wrap.*/
	protected $service = null;
	const HANDLER = __FILE__;

	/**
	 * @param Base $service
	 */
	public function __construct(Base $service)
	{
		$this->service = $service;
	}

	/**
	 * @return array
	 */
	public function Init()
	{
		return array(
			"SID" => 'new'.$this->service->getId(),
			"NAME" => $this->service->getName(),
			"DESCRIPTION" => $this->service->getDescription(),
			"DESCRIPTION_INNER" => "DESCRIPTION_INNER",
			"BASE_CURRENCY" => $this->service->getCurrency(),
			"HANDLER" => self::HANDLER,

			// Handler methods
			"COMPABILITY" => array($this, "compatibility"),
			"CALCULATOR" => array($this, "calculate"),

			// Fake profile
			"PROFILES" => array(
				"profile" => array(
					"TITLE" => ".",
					"DESCRIPTION" => ""
				)
			)
		);
	}

	/**
	 * Check if this service is compatible.
	 * @param array $arOrder Order details.
	 * @param array $arConfig Useless.
	 * @return array with profile.
	 */
	public function compatibility($arOrder, $arConfig)
	{
		$result = array();
		$shipment = \CSaleDelivery::convertOrderOldToNew($arOrder);

		if($this->service->isCompatible($shipment))
			$result = array('profile');

		return $result;
	}

	/**
	 * @param string $profile Useless.
	 * @param array $arConfig Useless.
	 * @param array $arOrder Order details.
	 * @param int $STEP Useless.
	 * @param bool|false $TEMP Useless.
	 * @return array Delivery price.
	 */
	public function calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
	{
		$res = $this->service->calculate(
			\CSaleDelivery::convertOrderOldToNew($arOrder)
		);

		return array(
			"VALUE" => $res->getPrice(),
			"TRANSIT" => $res->getPeriodDescription(),
			"RESULT" => $res->isSuccess() ? "OK" : "ERROR",
		);
	}

	/**
	 * Converts new service fields to old.
	 * @param array $service Delivery service fields from new table.
	 * @return array Service fields as it was in old API.
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function convertNewServiceToOld($service)
	{
		if(intval($service["ID"]) <= 0)
			return array();

		$service["SID"] = 'new'.$service["ID"];
		$service["TAX_RATE"] = 0;
		$service["INSTALLED"] = 'Y';
		$service["BASE_CURRENCY"] = $service["CURRENCY"];
		$service["SETTINGS"] = array();
		$service["HANDLER"] = self::HANDLER;

		if (intval($service["LOGOTIP"]) > 0)
			$service["LOGOTIP"] = \CFile::getFileArray($service["LOGOTIP"]);

		$service["CONFIG"] = array(
			"CONFIG_GROUPS" => array(),
			"CONFIG" => array(),
		);

		$service["PROFILES"] = array();

		$profileParams = array(
			"TITLE" => "",
			"DESCRIPTION" => $service["DESCRIPTION"],
			"TAX_RATE" => 0,
			"ACTIVE" =>  $service["ACTIVE"]
		);

		$restrictions = Restrictions\Manager::getRestrictionsList($service["ID"]);

		foreach($restrictions as $restriction)
		{
			switch($restriction["CLASS_NAME"])
			{
				case '\Bitrix\Sale\Delivery\Restrictions\ByWeight':
					$profileParams["RESTRICTIONS_WEIGHT"] = array($restriction["PARAMS"]["MIN_WEIGHT"], $restriction["PARAMS"]["MAX_WEIGHT"]);
					break;

				case '\Bitrix\Sale\Delivery\Restrictions\ByPrice':
					$profileParams["RESTRICTIONS_SUM"] = array($restriction["PARAMS"]["MIN_PRICE"], $restriction["PARAMS"]["MAX_PRICE"]);
					break;

				case '\Bitrix\Sale\Delivery\Restrictions\ByDimensions':
					$profileParams["RESTRICTIONS_DIMENSIONS"] = array(
						$restriction["PARAMS"]["LENGTH"],
						$restriction["PARAMS"]["WIDTH"],
						$restriction["PARAMS"]["HEIGHT"]
					);

					$profileParams["RESTRICTIONS_MAX_SIZE"] = $restriction["PARAMS"]["MAX_DIMENSION"];
					$profileParams["RESTRICTIONS_DIMENSIONS_SUM"] = $restriction["PARAMS"]["MAX_DIMENSIONS_SUM"];
					break;

				default:
					break;
			}
		}

		$service["PROFILES"]['profile'] = $profileParams;

		$newService = Manager::getObjectById($service["ID"]);
		$newToAutomatic = new self($newService);

		$service = array_merge($newToAutomatic->init(), $service);
		return $service;
	}
}
