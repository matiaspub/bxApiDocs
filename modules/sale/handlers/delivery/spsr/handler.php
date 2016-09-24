<?
namespace Sale\Handlers\Delivery;

use Bitrix\Main\Error;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\ExtraServices\Table;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Result;
use \Bitrix\Sale\Shipment;
use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\BusinessValue;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Sale\Handlers\Delivery\Spsr\Cache;
use Sale\Handlers\Delivery\Spsr\Location;
use Sale\Handlers\Delivery\Spsr\Request;
use Sale\Handlers\Delivery\Spsr\Calculator;
use Bitrix\Sale\Delivery\CalculationResult;

Loc::loadMessages(__FILE__);

Loader::registerAutoLoadClasses(
	'sale',
	array(
		'Sale\Handlers\Delivery\Spsr\Cache' => 'handlers/delivery/spsr/cache.php',
		'Sale\Handlers\Delivery\SpsrProfile' => 'handlers/delivery/spsr/profile.php',
		'Sale\Handlers\Delivery\Spsr\Request' => 'handlers/delivery/spsr/request.php',
		'Sale\Handlers\Delivery\Spsr\Location' => 'handlers/delivery/spsr/location.php',
		'Sale\Handlers\Delivery\SpsrTracking' => 'handlers/delivery/spsr/tracking.php',
		'Sale\Handlers\Delivery\Spsr\Calculator' => 'handlers/delivery/spsr/calculator.php',
	)
);

class SpsrHandler extends \Bitrix\Sale\Delivery\Services\Base
{
	protected static $url_test_request = "http://spsr.ru/testxml";

	/** @var bool $canHasProfiles This handler can has profiles */
	protected static $canHasProfiles = true;
	/** @var bool $whetherAdminExtraServicesShow This handler uses extra services */
	protected static $whetherAdminExtraServicesShow = true;
	/** @var string Tracking class */
	protected $trackingClass = '\Sale\Handlers\Delivery\SpsrTracking';
	/**
	 * @return string
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLV_SRV_SPSR_TITLE");
	}

	/**
	 * @return string
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage(
			"SALE_DLV_SRV_SPSR_DESCRIPTION",
			array(
				'#A1#' => '<a href="http://www.spsr.ru/" target="_blank">',
				'#A2#' => '</a>',
			)
		);
	}

	/**
	 * Handler can't count concrete
	 * @param Shipment $shipment
	 * @throws SystemException
	 * @return void
	 */
	protected function calculateConcrete(Shipment $shipment)
	{
		throw new SystemException("Only SPSR Profiles can calculate concrete");
	}

	/**
	 * Returns string for http request.
	 * @param Shipment $shipment
	 * @return Result
	 */
	protected function getTarifsReq(Shipment $shipment)
	{
		$result = new Result();
		$res = $this->getSidResult();

		if(!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());
			return $result;
		}

		$data = $res->getData();
		$sid = $data[0];
		$additional = array();

		if(!empty($this->config['MAIN']['NATURE']))
			$additional['NATURE'] = $this->config['MAIN']['NATURE'];

		if(isset($this->config['MAIN']['AMOUNT_CHECK']) && intval($this->config['MAIN']['AMOUNT_CHECK']) >= 0)
			$additional['AMOUNT_CHECK'] = $this->config['MAIN']['AMOUNT_CHECK'];

		if(!empty($this->config['MAIN']['ICN']))
			$additional['ICN'] = $this->config['MAIN']['ICN'];

		$additional['DEFAULT_WEIGHT'] = $this->config['MAIN']['DEFAULT_WEIGHT'];

		if(strlen($sid) > 0)
			$additional['SID'] = $sid;

		foreach($shipment->getExtraServices() as $srvId => $value)
		{
			$srvItem = $this->extraServices->getItem($srvId);

			if($srvItem && strlen($srvItem->getCode()) > 0)
				$additional['EXTRA_SERVICES'][$srvItem->getCode()] = $value;
		}

		$res = Calculator::calculate($shipment, $additional);

		if(!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());
			return $result;
		}

		$result->setData($res->getData());
		return $result;
	}

	/**
	 * Calculates prices for concrete service
	 * @param Shipment $shipment
	 * @param $tariff
	 * @return CalculationResult
	 */
	public function calculateTariff(Shipment $shipment, $tariff)
	{
		$result = new CalculationResult();
		$res = $this->getTarifsReq($shipment);

		if(!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());
			return $result;
		}

		foreach($res->getData() as $tarffParams)
		{
			if(strpos(ToUpper($tarffParams['TariffType']), ToUpper($tariff)) !== false)
			{
				$result->setData($res->getData());
				$result->setDeliveryPrice(
					floatval($tarffParams['Total_Dost'])+
					floatval($tarffParams['Insurance'])+
					floatval($tarffParams['worth'])
				);
				$result->setExtraServicesPrice(floatval($tarffParams['Total_DopUsl']));

				if(strlen($tarffParams['DP']) > 0)
					$result->setPeriodDescription($tarffParams['DP'].' ('.Loc::getMessage('SALE_DLV_SRV_SPSR_DAYS').')');

				return $result;
			}
		}

		$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_TARIF_CALCULATE')));
		return $result;
	}

	/**
	 * @return array Configuration structure
	 */
	protected function getConfigStructure()
	{
		$natures = array_intersect_key(
			self::getNaturesList(),
			array_flip(
					self::getProfileNatures()
			)
		);

		$result = array(
			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLV_SRV_SPSR_MAIN_TITLE"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLV_SRV_SPSR_MAIN_DSCR"),
				"ITEMS" => array(
					"CALCULATE_IMMEDIATELY" => array(
						'TYPE' => 'Y/N',
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_CALCULATE_IMMEDIATELY"),
						"DEFAULT" => "Y"
					),
					"DEFAULT_WEIGHT" => array(
						'TYPE' => 'NUMBER',
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_DEFAULT_WEIGHT"),
						"DEFAULT" => "1000"
					),
					"AMOUNT_CHECK" => array(
						"TYPE" => "ENUM",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_AMOUNT_CHECK"),
						"DEFAULT" => "1",
						"OPTIONS" => array(
								-1 => Loc::getMessage("SALE_DLV_SRV_SPSR_AMOUNT_CHECK__1"),
								0 => Loc::getMessage("SALE_DLV_SRV_SPSR_AMOUNT_CHECK_0"),
								1 => Loc::getMessage("SALE_DLV_SRV_SPSR_AMOUNT_CHECK_1")
						)
					),
					"NATURE" => array(
						"TYPE" => "ENUM",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_NATURE"),
						"DEFAULT" => "1",
						"REQUIRED" => true,
						"OPTIONS" => $natures
					),
					"LOGIN" => array(
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_LOGIN"),
						"DEFAULT" => ""
					),
					"PASS" => array(
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_PASS"),
						"DEFAULT" => ""
					),
					"ICN" => array(
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("SALE_DLV_SRV_SPSR_ICN"),
						"DEFAULT" => ""
					)
				)
			)
		);

		return $result;
	}

	/**
	 * @return int[] Natures of the cargo code => names
	 */
	protected static function getNaturesList()
	{
		$result = array();
		$codes = array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24);

		foreach($codes as $code)
			$result[$code] = Loc::getMessage('SALE_DLV_SRV_SPSR_NATURE_'.$code);

		return $result;
	}

	/**
	 * @param int $profileId
	 * @return int[]
	 */
	protected static function getProfileNatures($profileId = 0)
	{
		if($profileId <= 0)
			return array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24);

		$natures = array(
				20 => array(1, 2, 17), 								//colibri
				21 => array(1, 2, 17), 								//gepard-express 13
				22 => array(1, 2, 17), 								//gepard-express 18
				23 => array(1, 2, 17), 								//gepard-express
				24 => array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24), 	//pelican-standart
				25 => array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24),	//pelican-econom
				26 => array(1, 2, 17, 18, 19, 20, 21, 22 ), 		//bizon-cargo
				27 => array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24), 	//fraxt
				28 => array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24), 	//pelican-online
				35 => array(1, 2, 17 ), 							//gepard-online
				36 => array(1, 2, 17, 18, 19, 20, 21, 22, 23, 24) 	//zebra-online
		);

		return isset($natures[$profileId]) ? $natures[$profileId] : array();
	}

	public function isCalculatePriceImmediately()
	{
		return $this->config['MAIN']['CALCULATE_IMMEDIATELY'] == 'Y';
	}

	/**
	 * @return array Business values.
	 */
	public static function onGetBusinessValueConsumers()
	{
		static $consumers;
		if(!$consumers)
		{
			static $personMaxIndex;

			$providerKeys = array('', 'VALUE', 'USER', 'ORDER', 'PROPERTY', 'COMPANY', 'SHIPMENT');

			$codes = array(
				'NAME'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_NAME'         ), 'SORT' =>  100, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'AGENT_NAME'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_AGENT_NAME'   ), 'SORT' =>  500, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'CONTACT_NAME' => array('NAME' => Loc::getMessage('BIZVAL_CODE_CONTACT_NAME' ), 'SORT' =>  600, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'INN'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_INN'          ), 'SORT' =>  900, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'KPP'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_KPP'          ), 'SORT' => 1000, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'PHONE'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_PHONE'        ), 'SORT' => 2200, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'EMAIL'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_EMAIL'        ), 'SORT' => 2300, 'GROUP' => 'CODE_COMPANY', 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'EGRPO'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_EGRPO'), 'SORT' => 2400, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'OKVED'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKVED'), 'SORT' => 2500, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'OKDP'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKDP' ), 'SORT' => 2600, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'OKOPF'        => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKOPF'), 'SORT' => 2700, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'OKFC'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKFC' ), 'SORT' => 2800, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'OKPO'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_COMPANY_OKPO' ), 'SORT' => 2900, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'BANK_ACCOUNT' => array('NAME' => Loc::getMessage('BIZVAL_CODE_BANK_ACCOUNT' ), 'SORT' => 3000, 'GROUP' => 'CODE_COMPANY'  , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::ENTITY_DOMAIN)),
				'LAST_NAME'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_LAST_NAME'    ), 'SORT' =>  200, 'GROUP' => 'CODE_CLIENT'   , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'FIRST_NAME'   => array('NAME' => Loc::getMessage('BIZVAL_CODE_FIRST_NAME'   ), 'SORT' =>  300, 'GROUP' => 'CODE_CLIENT'   , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'SECOND_NAME'  => array('NAME' => Loc::getMessage('BIZVAL_CODE_SECOND_NAME'  ), 'SORT' =>  400, 'GROUP' => 'CODE_CLIENT'   , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'BIRTHDAY'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_BIRTHDAY'     ), 'SORT' =>  700, 'GROUP' => 'CODE_CLIENT'   , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'GENDER'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_GENDER'       ), 'SORT' =>  800, 'GROUP' => 'CODE_CLIENT'   , 'PROVIDERS' => $providerKeys, 'DOMAINS' => array(BusinessValue::INDIVIDUAL_DOMAIN)),
				'ADDRESS'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_ADDRESS'      ), 'SORT' =>  100, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'ZIP'          => array('NAME' => Loc::getMessage('BIZVAL_CODE_ZIP'          ), 'SORT' =>  200, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'COUNTRY'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_COUNTRY'      ), 'SORT' =>  300, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'REGION'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_REGION'       ), 'SORT' =>  400, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'DISTRICT'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_DISTRICT'     ), 'SORT' =>  500, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'LOCALITY'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_LOCALITY'     ), 'SORT' =>  600, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'CITY'         => array('NAME' => Loc::getMessage('BIZVAL_CODE_CITY'         ), 'SORT' =>  700, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'STREET'       => array('NAME' => Loc::getMessage('BIZVAL_CODE_STREET'       ), 'SORT' =>  800, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'HOUSING'      => array('NAME' => Loc::getMessage('BIZVAL_CODE_HOUSING'      ), 'SORT' =>  900, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'BUILDING'     => array('NAME' => Loc::getMessage('BIZVAL_CODE_BUILDING'     ), 'SORT' => 1000, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
				'APARTMENT'    => array('NAME' => Loc::getMessage('BIZVAL_CODE_APARTMENT'    ), 'SORT' => 1100, 'GROUP' => 'CODE_ADDRESS'  , 'PROVIDERS' => $providerKeys),
			);

			$consumers = array(
				'DELIVERY_SERIVICE_SPSR' => array(
					'NAME'  => Loc::getMessage('BIZVAL_CONSUMER_1C'),
					'SORT'  => 400,
					'CODES' =>$codes
				),
			);
		}

		return $consumers;
	}

	/**
	 * Decodes income data if it needs
	 * @param $str
	 * @return array|bool|string
	 */
	protected static function utfDecode($str)
	{
		if(strtolower(SITE_CHARSET) != 'utf-8')
			$str = Encoding::convertEncodingArray($str, 'UTF-8', SITE_CHARSET);

		return $str;
	}

	/**
	 * @return array Class names for profiles.
	 */
	public static function getChildrenClassNames()
	{
		return array(
			'\Sale\Handlers\Delivery\SpsrProfile'
		);
	}

	/**
	 * @return string Company name.
	 */
	public static function getCompanyName()
	{
		/*
		 * todo:
 		 * $companyName = BusinessValue::getValueFromProvider($shipment, 'COMPANY_NAME', 'SHIPMENT');
 		 */
		return '';
	}

	/**
	 * Returns SID required for requests.
	 * @return Result
	 */
	public function getSidResult()
	{
		$result = new Result();
		$sid = Cache::getSidResult();

		if($sid === false)
		{
			if(!empty($this->config['MAIN']['LOGIN']) && !empty($this->config['MAIN']['PASS']))
			{
				$request = new Request();
				$res = $request->getSidResult($this->config['MAIN']['LOGIN'], $this->config['MAIN']['PASS'], self::getCompanyName());

				if(!$res->isSuccess())
				{
					$result->addErrors($res->getErrors());
					return $result;
				}

				$data = $res->getData();
				$sid = $data[0];
			}
			else
			{
				$sid = "";
			}

			Cache::setSid($sid);
		}

		$result->setData(array($sid));
		return $result;
	}

	public function getICN()
	{
		return $this->config['MAIN']['ICN'];
	}

	/**
	 * Returns services list with parameters.
	 * @return Result
	 */
	public function getServiceTypes()
	{
		$result = new Result();
		$types = Cache::getServiceTypes();

		if($types === false)
		{
			$res = self::getSidResult();

			if($res->isSuccess())
			{
				$data = $res->getData();
				$sessId = $data[0];
			}
			else
			{
				$result->addErrors($res->getErrors());
				$sessId = '';
			}

			$request = new Request();
			$res = $request->getServiceTypes($sessId, $this->getKnownServices());

			if(!$res->isSuccess())
			{
				$result->addErrors($res->getErrors());
				return $result;
			}

			$types = $res->getData();
			$types = $types + self::getOnLineSrvs();
			Cache::setServiceTypes($types);
		}

		if(!is_array($types))
			$types = array();

		$result->setData($types);
		return $result;
	}

	protected static function getOnLineSrvs()
	{
		return array(
			"28" => array(
				"ID" => "28",
				"Name" => Loc::getMessage('SALE_DLV_SRV_SPSR_PELICAN_ONLINE'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_SRV_SPSR_PELICAN_ONLINE_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_SRV_SPSR_PELICAN_ONLINE_DESCR'),
			),
			"35" => array(
				"ID" => "35",
				"Name" => Loc::getMessage('SALE_DLV_SRV_SPSR_GEPARD_ONLINE'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_SRV_SPSR_GEPARD_ONLINE_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_SRV_SPSR_GEPARD_ONLINE_DESCR'),
			),
			"36" => array(
				"ID" => "36",
				"Name" => Loc::getMessage('SALE_DLV_SRV_SPSR_ZEBRA_ONLINE'),
				"ShortDescription" => Loc::getMessage('SALE_DLV_SRV_SPSR_ZEBRA_ONLINE_SDESCR'),
				"Description" => Loc::getMessage('SALE_DLV_SRV_SPSR_ZEBRA_ONLINE_DESCR'),
			)
		);
	}

	/**
	 * @return int[] Services ids we can process.
	 */
	protected function getKnownServices()
	{
		return array(20, 21, 22, 23, 24, 25, 26, 27, 28, 35, 36);
	}

	/**
	 * @return array Extra services list we can use
	 */
	static public function getEmbeddedExtraServicesList()
	{
		return self::getAlltExtraServices();
	}

	public static function getAlltExtraServices()
	{
		return array(
			"SMS" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_SMS'),
				"SORT" => 100,
				"RIGHTS" => "NYN",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_SMS_DESCR'),
				"INIT_VALUE" => "N",
				"PARAMS" => array("PRICE" => 0)
			),
			"SMS_RECV" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_SMS_RECV'),
				"SORT" => 100,
				"RIGHTS" => "NYY",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_SMS_RECV_DESCR'),
				"INIT_VALUE" => "Y",
				"PARAMS" => array("PRICE" => 0)
			),
			"BEFORE_SIGNAL" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_BEFORE_SIGNAL'),
				"SORT" => 100,
				"RIGHTS" => "NYY",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_BEFORE_SIGNAL_DESCR'),
				"INIT_VALUE" => "N",
				"PARAMS" => array("PRICE" => 0)
			),
			"BY_HAND" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_BY_HAND'),
				"SORT" => 100,
				"RIGHTS" => "NYY",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_BY_HAND_DESCR'),
				"INIT_VALUE" => "N",
				"PARAMS" => array("PRICE" => 0)
			),
			"ICD" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_ICD'),
				"SORT" => 100,
				"RIGHTS" => "NYY",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ICD_DESCR'),
				"INIT_VALUE" => "N",
				"PARAMS" => array("PRICE" => 0)
			),
			"TO_BE_CALLED_FOR" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_TO_BE_CALLED_FOR'),
				"SORT" => 100,
				"RIGHTS" => "NYY",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => "",
				"INIT_VALUE" => "N",
				"PARAMS" => array("PRICE" => 0)
			),
			"PLAT_TYPE" => array(
				"NAME" => Loc::getMessage('SALE_DLV_SRV_SPSR_PLAT_TYPE'),
				"SORT" => 100,
				"RIGHTS" => "NYN",
				"ACTIVE" => "Y",
				"CLASS_NAME" => '\Bitrix\Sale\Delivery\ExtraServices\Checkbox',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_PLAT_TYPE_DESCR'),
				"INIT_VALUE" => "N",
				"PARAMS" => array("PRICE" => 0)
			)
		);
	}

	/**
	 * @return array Profiles list code => name
	 */
	public function getProfilesList()
	{
		$result = array();
		$resSrv = $this->getServiceTypes();
		$data = $resSrv->getData();

		if(is_array($data))
			foreach($data as $id => $params)
				$result[$id] = $params['Name'];

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return int[] Services types ids compatible with shipment
	 */
	public function getCompatibleProfiles(Shipment $shipment)
	{
		static $compatibleProfiles = null;

		if($compatibleProfiles !== null)
			return $compatibleProfiles;

		$profilesList = $this->getProfilesList();

		if($this->isCalculatePriceImmediately())
		{
			$res = $this->getTarifsReq($shipment);

			if(!$res->isSuccess())
				return array();

			$compatibleProfiles =  array();

			foreach($res->getData() as $tarffParams)
			{
				foreach($profilesList as $id => $name)
				{
					if(strpos(ToUpper($tarffParams['TariffType']), ToUpper($name)) !== false)
					{
						$compatibleProfiles[] = $id;
						break;
					}
				}
			}
		}
		else
		{
			$compatibleProfiles = array_keys($profilesList);
		}

		return $compatibleProfiles;
	}

	public static function onAfterUpdate($serviceId, array $fields = array())
	{
		Cache::cleanAll();
	}

	/**
	 * @param int $serviceId
	 * @param array $fields
	 * @return bool
	 */
	public static function onAfterAdd($serviceId, array $fields = array())
	{
		if($serviceId <= 0)
			return false;

		$result = true;

		//Add profiles
		$fields["ID"] = $serviceId;
		$srv = new self($fields);
		$profiles = $srv->getProfilesDefaultParams();

		if(is_array($profiles))
		{
			foreach($profiles as $profile)
			{
				$res = Manager::add($profile);
				$result = $result && $res->isSuccess();
			}
		}

		//Add extra services
		foreach(self::getAlltExtraServices() as $code => $esFields)
		{
			$esFields['DELIVERY_ID'] = $serviceId;
			$esFields['CODE'] = $code;
			$res = Table::add($esFields);
			$result = $result && $res->isSuccess();
		}

		return $result;
	}

	public static function install()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandler(
			'sale',
			'onSaleDeliveryTrackingClassNamesBuildList',
			'sale',
			'\Sale\Handlers\Delivery\SpsrHandler',
			'onSaleDeliveryTrackingClassNamesBuildList'
		);

		Location::install();
	}

	public static function unInstall()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler(
			'sale',
			'onSaleDeliveryTrackingClassNamesBuildList',
			'sale',
			'\Sale\Handlers\Delivery\SpsrHandler',
			'onSaleDeliveryTrackingClassNamesBuildList'
		);
		Location::unInstall();
	}

	public static function onSaleDeliveryTrackingClassNamesBuildList()
	{
		return new \Bitrix\Main\EventResult(
			\Bitrix\Main\EventResult::SUCCESS,
			array(
				'\Sale\Handlers\Delivery\SpsrTracking' => '/bitrix/modules/sale/handlers/delivery/spsr/tracking.php'
			),
			'sale'
		);
	}

	public static function isInstalled()
	{
		return Location::isInstalled();
	}

	public function getProfilesDefaultParams()
	{
		$result = array();

		$resSrv = $this->getServiceTypes();
		$srvTypes = $resSrv->getData();

		if(is_array($srvTypes))
		{
			foreach($srvTypes as $profId => $params)
			{
				$result[] = array(
					"CODE" => "",
					"PARENT_ID" => $this->id,
					"NAME" => $params["Name"],
					"ACTIVE" => $this->active ? "Y" : "N",
					"SORT" => $this->sort,
					"DESCRIPTION" => $params["ShortDescription"],
					"CLASS_NAME" => '\Sale\Handlers\Delivery\SpsrProfile',
					"CURRENCY" => $this->currency,
					"CONFIG" => array(
						"MAIN" => array(
							"SERVICE_TYPE" => $profId,
							"SERVICE_TYPE_NAME" => $params["Name"],
							"DESCRIPTION_INNER" => $params["Description"]
						)
					)
				);
			}
		}

		return $result;
	}

	public static function canHasProfiles()
	{
		return self::$canHasProfiles;
	}

	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}
}