<?
namespace Sale\Handlers\Delivery;

use Bitrix\Main\Error;
use Bitrix\Sale\Shipment;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\CalculationResult;

Loc::loadMessages(__FILE__);

class SpsrProfile extends \Bitrix\Sale\Delivery\Services\Base
{
	/** @var SpsrHandler Parent service. */
	protected $spsrHandler = null;
	/** @var int Service type */
	protected $serviceType = 0;

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
		$this->spsrHandler = Manager::getObjectById($this->parentId);

		if(!($this->spsrHandler instanceof SpsrHandler))
			throw new ArgumentNullException('this->spsrHandler is not instance of SpsrHandler');

		if(isset($initParams['PROFILE_ID']) && intval($initParams['PROFILE_ID']) > 0)
			$this->serviceType = intval($initParams['PROFILE_ID']);
		elseif(isset($this->config['MAIN']['SERVICE_TYPE']) && intval($this->config['MAIN']['SERVICE_TYPE']) > 0)
			$this->serviceType = $this->config['MAIN']['SERVICE_TYPE'];

		if($this->serviceType > 0)
		{
			$srvRes = $this->spsrHandler->getServiceTypes();
			$srvTypes = $srvRes->getData();

			if(!empty($srvTypes[$this->serviceType]))
			{
				$this->name = $srvTypes[$this->serviceType]['Name'];
				$this->description = $srvTypes[$this->serviceType]['ShortDescription'];
			}
		}

		$this->inheritParams();
	}

	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLV_SRV_SPSR_PROFILE_TITLE");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLV_SRV_SPSR_PROFILE_DESCRIPTION");
	}

	/**
	 * Defines inheritance behavior.
	 * @throws ArgumentNullException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function 	inheritParams()
	{
		if(strlen($this->name) <= 0) $this->name = $this->spsrHandler->getName();
		if(intval($this->logotip) <= 0) $this->logotip = $this->spsrHandler->getLogotip();
		if(strlen($this->description) <= 0) $this->description = $this->spsrHandler->getDescription();
		if(empty($this->trackingParams)) $this->trackingParams = $this->spsrHandler->getTrackingParams();
		if(strlen($this->trackingClass) <= 0) $this->trackingClass = $this->spsrHandler->getTrackingClass();

		$parentES = \Bitrix\Sale\Delivery\ExtraServices\Manager::getExtraServicesList($this->parentId);
		$allowEsCodes = self::getProfileES($this->serviceType);

		if(!empty($parentES))
		{
			foreach($parentES as $esFields)
			{
				if(
					strlen($esFields['CODE']) > 0
					&& !$this->extraServices->getItemByCode($esFields['CODE'])
					&& in_array($esFields['CODE'], $allowEsCodes)
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
	 */
	protected function calculateConcrete(Shipment $shipment)
	{
		$srvRes = $this->spsrHandler->getServiceTypes();
		$srvList = $srvRes->getData();

		if(empty($srvList[$this->serviceType]['Name']))
		{
			$result = new CalculationResult();
			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_PROFILE_CONF_TYPE_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => $this->getId(),
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_CONFIG_SRV_TYPE'),
			));

			return $result;
		}

		return $this->spsrHandler->calculateTariff($shipment, $srvList[$this->serviceType]['Name']);
	}

	public function isCalculatePriceImmediately()
	{
		return $this->spsrHandler->isCalculatePriceImmediately();
	}

	/**
	 * @return array Handler's configuration
	 */
	protected function getConfigStructure()
	{
		$srvRes = $this->spsrHandler->getServiceTypes();
		$srvList = $srvRes->getData();

		$result = array(
			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLV_SRV_SPSR_PROFILE_MAIN_TITLE"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLV_SRV_SPSR_PROFILE_MAIN_DSCR"),
				"ITEMS" => array(
					"SERVICE_TYPE_NAME" => array(
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_PROFILE_ST"),
						"READONLY" => true,
						"DEFAULT" => $srvList[$this->serviceType]['Name']
					),
					"SERVICE_TYPE" => array(
						"TYPE" => "STRING",
						"NAME" =>"SERVICE_TYPE",
						"HIDDEN" => true,
						"DEFAULT" => $this->serviceType
					),
					"DESCRIPTION_INNER" => array(
						"TYPE" => "DELIVERY_READ_ONLY",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_INNER_DESCR"),
						"ID" => "adm-sale-delivery-spsr-description_inner",
						"DEFAULT" => $srvList[$this->serviceType]['Description']
					)
				)
			)
		);

		if($this->serviceType != 20) //colibri
		{
		}

		return $result;
	}

	/**
	 * @return \Bitrix\Sale\Delivery\Services\Base|\Sale\Handlers\Delivery\SpsrHandler Parent sevice.
	 */
	public function getParentService()
	{
		return $this->spsrHandler;
	}

	/**
	 * @return array
	 */
	public function getEmbeddedExtraServicesList()
	{
		$result = array();
		$allowEsCodes = self::getProfileES($this->serviceType);

		foreach($this->spsrHandler->getEmbeddedExtraServicesList() as $code => $params)
			if(in_array($code, $allowEsCodes))
				$result[$code] = $params;

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return bool
	 */
	public function isCompatible(Shipment $shipment)
	{
		return in_array(
			$this->serviceType,
			$this->spsrHandler->getCompatibleProfiles($shipment)
		);
	}

	/**
	 * @param $profileId
	 * @return array
	 */
	public static function getProfileES($profileId)
	{
		$extraServices = array(
			20 => array('SMS_RECV', 'SMS', 'TO_BE_CALLED_FOR'), 										//colibri
			21 => array('BY_HAND', 'SMS', 'SMS_RECV'), 													//gepard-express 13
			22 => array('BY_HAND', 'SMS', 'SMS_RECV'), 													//gepard-express 18
			23 => array('BY_HAND', 'SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR'), 								//gepard-express
			24 => array('BY_HAND', 'SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR', 'PLAT_TYPE', 'ICD'), 			//pelican-standart
			25 => array('BY_HAND', 'SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR', 'PLAT_TYPE', 'ICD'),			//pelican-econom
			26 => array('SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR', 'PLAT_TYPE', 'ICD'), 					//bizon-cargo
			27 => array('TO_BE_CALLED_FOR'), 															//fraxt
			28 => array('BY_HAND', 'SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR', 'ICD', 'TO_BE_CALLED_FOR'),	//pelican-online
			35 => array('BY_HAND', 'SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR', 'ICD', 'TO_BE_CALLED_FOR'),	//gepard-online
			36 => array('BY_HAND', 'SMS', 'SMS_RECV', 'TO_BE_CALLED_FOR', 'ICD', 'TO_BE_CALLED_FOR'), 	//zebra-online
		);

		return isset($extraServices[$profileId]) ? $extraServices[$profileId] : array();
	}

	public static function install()
	{
		SpsrHandler::install();
	}

	public static function unInstall()
	{
		SpsrHandler::unInstall();
	}

	public static function isInstalled()
	{
		SpsrHandler::isInstalled();
	}

	public static function isProfile()
	{
		return self::$isProfile;
	}

	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}
}