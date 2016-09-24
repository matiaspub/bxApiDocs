<?
namespace Sale\Handlers\Delivery;

use Bitrix\Main\SystemException;
use Bitrix\Sale\Shipment;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\CalculationResult;
use Sale\Handlers\Delivery\Additional\RestClient;

Loc::loadMessages(__FILE__);

class AdditionalProfile extends \Bitrix\Sale\Delivery\Services\Base
{
	/** @var AdditionalHandler Parent service. */
	protected $additionalHandler = null;
	/** @var string Service type */
	protected $profileType = "";

	protected static $whetherAdminExtraServicesShow = true;
	/** @var bool This handler is profile */
	protected static $isProfile = true;

	/**
	 * @param array $initParams
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function __construct(array $initParams)
	{
		if(empty($initParams["PARENT_ID"]))
			throw new ArgumentNullException('initParams[PARENT_ID]');

		parent::__construct($initParams);
		$this->additionalHandler = Manager::getObjectById($this->parentId);

		if(!($this->additionalHandler instanceof AdditionalHandler))
			throw new ArgumentNullException('this->additionalHandler is not instance of AdditionalHandler');

		if(isset($initParams['PROFILE_ID']) && strlen($initParams['PROFILE_ID']) > 0)
			$this->profileType = $initParams['PROFILE_ID'];
		elseif(isset($this->config['MAIN']['PROFILE_TYPE']) && strlen($this->config['MAIN']['PROFILE_TYPE']) > 0)
			$this->profileType = $this->config['MAIN']['PROFILE_TYPE'];

		if(strlen($this->profileType) > 0)
		{
			$profileParams = $this->getProfileParams();

			if(!empty($profileParams))
			{
				$this->name = $profileParams['NAME'];
				$this->description = $profileParams['DESCRIPTION'];

				if(!empty($profileParams['LOGOTIP']))
					$this->logotip = $profileParams['LOGOTIP'];
			}
		}

		$this->inheritParams();
	}

	/**
	 * @return array
	 */
	protected function getProfileParams()
	{
		$result = array();
		$list = $this->additionalHandler->getProfilesListFull();

		if(!empty($list[$this->profileType]))
			$result = $list[$this->profileType];

		return $result;
	}

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVRS_ADDP_NAME");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVRS_ADDP_DESCRIPTION");
	}

	/**
	 * Defines inheritance behavior.
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function 	inheritParams()
	{
		if(strlen($this->name) <= 0) $this->name = $this->additionalHandler->getName();
		if(intval($this->logotip) <= 0) $this->logotip = $this->additionalHandler->getLogotip();
		if(strlen($this->description) <= 0) $this->description = $this->additionalHandler->getDescription();
		if(empty($this->trackingParams)) $this->trackingParams = $this->additionalHandler->getTrackingParams();
		if(strlen($this->trackingClass) <= 0) $this->trackingClass = $this->additionalHandler->getTrackingClass();

		$parentES = \Bitrix\Sale\Delivery\ExtraServices\Manager::getExtraServicesList($this->parentId);

		if(!empty($parentES))
		{
			foreach($parentES as $esFields)
			{
				if(
					(strlen($esFields['CODE']) > 0 && !$this->extraServices->getItemByCode($esFields['CODE']))
					|| strlen($esFields['CODE']) <= 0
				)
				{
					$this->extraServices->addItem($esFields, $this->currency);
				}
			}
		}
	}

	/**
	 * Calculates price
	 * @param Shipment $shipment
	 * @return CalculationResult
	 * todo: Send default values if some params added to config, but not saved yet.
	 */
	protected function calculateConcrete(Shipment $shipment)
	{
		$client = new \Sale\Handlers\Delivery\Additional\RestClient();

		return $client->getDeliveryPrice(
			$this->additionalHandler->getServiceType(),
			$this->profileType,
			self::extractConfigValues($this->additionalHandler->getConfig()),
			self::extractConfigValues($this->getConfig()),
			$shipment
		);
	}

	/**
	 * @param array $config
	 * @return array
	 */
	protected static function extractConfigValues(array $config)
	{
		if(!is_array($config) || empty($config))
			return array();

		$result = array();

		foreach($config as $sectionKey => $sectionConfig)
		{
			if(isset($sectionConfig["ITEMS"]) && is_array($sectionConfig["ITEMS"]) && !empty($sectionConfig["ITEMS"]))
			{
				$result[$sectionKey] = array();

				foreach($sectionConfig["ITEMS"] as $name => $params)
				{
					$value = "";

					if(isset($params['VALUE']))
						$value = $params['VALUE'];
					elseif(isset($params['DEFAULT']))
						$value = $params['DEFAULT'];

					$result[$sectionKey][$name] = $value;
				}
			}
		}

		return $result;
	}


	/**
	 * @return bool
	 */
	public function isCalculatePriceImmediately()
	{
		return $this->additionalHandler->isCalculatePriceImmediately();
	}

	/**
	 * @return array Handler's configuration
	 */
	protected function getConfigStructure()
	{
		$client = new \Sale\Handlers\Delivery\Additional\RestClient();

		$res = $client->getProfileConfig(
			$this->additionalHandler->getServiceType(),
			$this->profileType
		);

		if(!$res->isSuccess())
			throw new SystemException(Loc::getMessage('SALE_DLVRS_ADD_PCONFIG_RECEIVE_ERROR'));

		return $res->getData();
	}

	/**
	 * @return \Bitrix\Sale\Delivery\Services\Base|\Sale\Handlers\Delivery\SpsrHandler Parent sevice.
	 */
	public function getParentService()
	{
		return $this->additionalHandler;
	}

	/**
	 * @param Shipment $shipment
	 * @return bool
	 */
	public function isCompatible(Shipment $shipment)
	{
		return array_key_exists(
			$this->profileType,
			$this->additionalHandler->getCompatibleProfiles($shipment)
		);
	}

	/**
	 * Install handler
	 */
	public static function install()
	{
		AdditionalHandler::install();
	}

	/**
	 * Uninstall
	 */
	public static function unInstall()
	{
		AdditionalHandler::unInstall();
	}

	/**
	 * Is handler installed
	 */
	public static function isInstalled()
	{
		AdditionalHandler::isInstalled();
	}

	/**
	 * @return bool
	 */
	public static function isProfile()
	{
		return self::$isProfile;
	}

	/**
	 * @return bool
	 */
	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}

	/**
	 * @return array
	 */
	public function getEmbeddedExtraServicesList()
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$client = new RestClient();
			$res = $client->getProfileExtraServices($this->additionalHandler->getServiceType(), $this->profileType);

			if($res->isSuccess())
				$result = $res->getData();
		}

		return $result;
	}
}