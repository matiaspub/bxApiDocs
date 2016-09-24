<?php

namespace Sale\Handlers\Delivery;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\IO\File;
use Bitrix\Sale\Shipment;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\Delivery\Services\Base;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Delivery\ExtraServices\Table;
use Sale\Handlers\Delivery\Additional\Location;
use Sale\Handlers\Delivery\Additional\RestClient;
use Bitrix\Sale\Internals\ServiceRestrictionTable;

Loc::loadMessages(__FILE__);

Loader::registerAutoLoadClasses(
	'sale',
	array(
		'Sale\Handlers\Delivery\AdditionalProfile' => 'handlers/delivery/additional/profile.php',
		'Sale\Handlers\Delivery\Additional\Location' => 'handlers/delivery/additional/location.php',
		'Sale\Handlers\Delivery\Additional\CacheManager' => 'handlers/delivery/additional/cache.php',
		'Sale\Handlers\Delivery\Additional\RestClient' => 'handlers/delivery/additional/restclient.php',
	)
);

/**
 * Class AdditionalHandler
 * Allows to use additional delivery services
 * @package Sale\Handlers\Delivery
 */
class AdditionalHandler extends Base
{
	/** @var string The real type of the handler */
	protected $serviceType = "";
	protected static $canHasProfiles = true;
	protected static $whetherAdminExtraServicesShow = true;

	const LOGO_FILE_ID_OPTION = 'handlers_dlv_add_lgotip';

	/**
	 * AdditionalHandler constructor.
	 * @param array $initParams
	 * @throws ArgumentNullException
	 */

	public function __construct(array $initParams)
	{
		parent::__construct($initParams);

		if(isset($initParams['SERVICE_TYPE']) && strlen($initParams['SERVICE_TYPE']) > 0)
			$this->serviceType = $initParams['SERVICE_TYPE'];
		elseif(isset($this->config["MAIN"]["SERVICE_TYPE"]))
			$this->serviceType = $this->config["MAIN"]["SERVICE_TYPE"];

		if(strlen($this->serviceType) <= 0)
			throw new ArgumentNullException('initParams[SERVICE_TYPE]');

		if(intval($this->id) <= 0)
		{
			$srvParams = $this->getServiceParams();

			if(!empty($srvParams['NAME']))
				$this->name = $srvParams['NAME'];

			if(!empty($srvParams['DESCRIPTION']))
				$this->description = $srvParams['DESCRIPTION'];

			if(!empty($srvParams['LOGOTIP']))
				$this->logotip = $srvParams['LOGOTIP'];
		}
	}

	/**
	 * @return string Class title.
	 */
	public static function getClassTitle()
	{
		return Loc::getMessage("SALE_DLVRS_ADD_NAME");
	}

	/**
	 * @return string Class, service description.
	 */
	public static function getClassDescription()
	{
		return Loc::getMessage("SALE_DLVRS_ADD_DESCRIPTION");
	}

	/**
	 * @param Shipment $shipment
	 * @throws SystemException
	 * @return \Bitrix\Sale\Delivery\CalculationResult
	 */
	protected function calculateConcrete(Shipment $shipment)
	{
		throw new SystemException("Only Additional Profiles can calculate concrete");
	}

	/**
	 * @return array
	 * @throws SystemException
	 * todo: business values for default values
	 */
	protected function getConfigStructure()
	{
		$fields = $this->getServiceParams();

		if(empty($fields))
			throw new SystemException(Loc::getMessage('SALE_DLVRS_ADD_CONFIG_RECEIVE_ERROR'));

		$result = array(
			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLVRS_ADD_MAIN_TITLE"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLVRS_ADD_MAIN_DESCRIPTION"),
				"ITEMS" => array(
					"SERVICE_TYPE_NAME" => array(
						"TYPE" => "STRING",
						"NAME" => Loc::getMessage("SALE_DLVRS_ADD_SERVICE_TYPE"),
						"READONLY" => true,
						"DEFAULT" => $fields['NAME']
					),
					"SERVICE_TYPE" => array(
						"TYPE" => "STRING",
						"NAME" =>"SERVICE_TYPE",
						"HIDDEN" => true,
						"DEFAULT" => $this->serviceType
					)
				)
			)
		);

		if(!empty($fields['CONFIG']) && is_array($fields['CONFIG']))
			foreach($fields['CONFIG'] as $key => $params)
				$result['MAIN']['ITEMS'][$key] = $params;

		$result['MAIN']['ITEMS']["DEFAULT_VALUES"] = array(
			"TYPE" => "DELIVERY_SECTION",
			"NAME" =>Loc::getMessage('SALE_DLVRS_ADD_MAIN_DEFAULT_VALUES'),
		);
		$result['MAIN']['ITEMS']["LENGTH_DEFAULT"] = array(
			"TYPE" => "STRING",
			"NAME" =>Loc::getMessage('SALE_DLVRS_ADD_MAIN_LENGTH_DEFAULT'),
			"DEFAULT" => 200
		);
		$result['MAIN']['ITEMS']["WIDTH_DEFAULT"] = array(
			"TYPE" => "STRING",
			"NAME" =>Loc::getMessage('SALE_DLVRS_ADD_MAIN_WIDTH_DEFAULT'),
			"DEFAULT" => 300
		);
		$result['MAIN']['ITEMS']["HEIGHT_DEFAULT"] = array(
			"TYPE" => "STRING",
			"NAME" =>Loc::getMessage('SALE_DLVRS_ADD_MAIN_HEIGHT_DEFAULT'),
			"DEFAULT" => 200
		);
		$result['MAIN']['ITEMS']["WEIGHT_DEFAULT"] = array(
			"TYPE" => "STRING",
			"NAME" =>Loc::getMessage('SALE_DLVRS_ADD_MAIN_WEIGHT_DEFAULT'),
			"DEFAULT" => 500
		);

		return $result;
	}

	/**
	 * @return array Supported types of services.
	 */
	public static function getSupportedServicesList()
	{
		static $result = null;

		if($result === null)
		{
			$client = new RestClient();
			$res = $client->getDeliveryList();

			if($res->isSuccess())
				$result = $res->getData();
			else
				$result = array(array("ERROR" => "Can't receive supported services list data"));
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getServiceParams()
	{
		$result = array();
		$client = new RestClient();
		$res = $client->getDeliveryFields($this->serviceType);

		if($res->isSuccess())
		{
			$logoId = intval($this->getLogoFileId());
			$result = $res->getData();

			if($logoId <= 0 && !empty($result['LOGOTIP']['CONTENT']) && !empty($result['LOGOTIP']['NAME']))
			{
				$tmpDir = \CTempFile::GetDirectoryName();
				CheckDirPath($tmpDir);
				$filePath = $tmpDir."/".$result['LOGOTIP']['NAME'];

				$res = File::putFileContents(
					$filePath,
					base64_decode($result['LOGOTIP']['CONTENT'])
				);

				if($res)
				{
					$file = \CFile::MakeFileArray($tmpDir."/".$result['LOGOTIP']['NAME']);
					$file['MODULE_ID'] = "sale";
					$logoId = intval(\CFile::SaveFile($file, $filePath));
					$this->setLogoFileId($logoId);
				}
			}

			$result['LOGOTIP'] = $logoId > 0 ? $logoId : 0;
		}

		return $result;
	}

	protected function getLogoFileId()
	{
		return intval(Option::get('sale', self::LOGO_FILE_ID_OPTION.'_'.$this->serviceType, ''));
	}

	protected function setLogoFileId($logoId)
	{
		if(intval($logoId) > 0)
			Option::set('sale', self::LOGO_FILE_ID_OPTION.'_'.$this->serviceType, $logoId);
	}

	/**
	 * @return array
	 */
	public static function getChildrenClassNames()
	{
		return array(
			'\Sale\Handlers\Delivery\AdditionalProfile'
		);
	}

	/**
	 * @return array profiles ids and names
	 */
	public function getProfilesList()
	{
		$result =array();

		$profiles = $this->getProfilesListFull();

		foreach($profiles as $profileType => $profile)
			$result[$profileType] = $profile['NAME'];

		return $result;
	}

	/**
	 * @return array All profile fields.
	 */
	public function getProfilesListFull()
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$client = new RestClient();
			$res = $client->getDeliveryProfilesList($this->serviceType);

			if($res->isSuccess())
				$result = $res->getData();
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public static function whetherAdminExtraServicesShow()
	{
		return self::$whetherAdminExtraServicesShow;
	}

	/**
	 * @return string
	 */
	public function getServiceType()
	{
		return $this->serviceType;
	}

	/**
	 * @param $shipment
	 * @return array
	 */
	public function getCompatibleProfiles($shipment)
	{
		return $this->getProfilesList();
	}

	/**
	 * @return bool
	 */
	public static function canHasProfiles()
	{
		return self::$canHasProfiles;
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
		$profiles = $srv->getProfilesListFull();

		if(is_array($profiles) && !empty($profiles))
		{
			foreach($profiles as $profileType => $pFields)
			{
				$profile = $srv->getProfileDefaultParams($profileType, $pFields);
				$res = Manager::add($profile);

				if($res->isSuccess() && !empty($pFields["RESTRICTIONS"]) && is_array($pFields["RESTRICTIONS"]))
				{
					$profileId = $res->getId();

					foreach($pFields["RESTRICTIONS"] as $restrictionType => $params)
					{
						$srv->addRestriction($restrictionType, $profileId, $params);
					}
				}

				$result = $result && $res->isSuccess();
			}
		}

		$extraservices = $srv->getEmbeddedExtraServicesList();

		if(!empty($extraservices))
		{
			//Add extra services
			foreach($extraservices as $code => $esFields)
			{
				$esFields['DELIVERY_ID'] = $serviceId;
				$esFields['CODE'] = $code;
				$res = Table::add($esFields);
				$result = $result && $res->isSuccess();
			}
		}

		return $result;
	}

	/**
	 * @param string $type
	 * @param string $profileId
	 * @param array $params
	 * @throws \Exception
	 */
	protected function addRestriction($type, $profileId, array $params)
	{
		$fields  = array();

		switch($type)
		{
			case "WEIGHT":
				$p = array();
				if(isset($params['MIN']))	$p['MIN_WEIGHT'] = $params['MIN'];
				if(isset($params['MAX']))	$p['MAX_WEIGHT'] = $params['MAX'];

				if(!empty($p))
				{
					$fields = array(
						"SERVICE_ID" => $profileId,
						"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByWeight',
						"SERVICE_TYPE" => \Bitrix\Sale\Services\Base\RestrictionManager::SERVICE_TYPE_SHIPMENT,
						"PARAMS" => $p
					);
				}

				break;

			case "DIMENSIONS":
				$p = array();
				if(isset($params['LENGTH']))	$p['LENGTH'] = $params['LENGTH'];
				if(isset($params['WIDTH']))	$p['WIDTH'] = $params['WIDTH'];
				if(isset($params['HEIGHT']))	$p['HEIGHT'] = $params['HEIGHT'];

				if(!empty($p))
				{
					$fields = array(
						"SERVICE_ID" => $profileId,
						"CLASS_NAME" => '\Bitrix\Sale\Delivery\Restrictions\ByDimensions',
						"SERVICE_TYPE" => \Bitrix\Sale\Services\Base\RestrictionManager::SERVICE_TYPE_SHIPMENT,
						"PARAMS" => $p
					);
				}

				break;
		}

		if(!empty($fields))
			ServiceRestrictionTable::add($fields);
	}

	/**
	 * @param string $type
	 * @param array $fields
	 * @return array
	 */
	protected function getProfileDefaultParams($type, array $fields)
	{
		return array(
			"CODE" => "",
			"PARENT_ID" => $this->id,
			"NAME" => $fields["NAME"],
			"ACTIVE" => $this->active ? "Y" : "N",
			"SORT" => $this->sort,
			"DESCRIPTION" => $fields["DESCRIPTION"],
			"CLASS_NAME" => '\Sale\Handlers\Delivery\AdditionalProfile',
			"CURRENCY" => $this->currency,
			"CONFIG" => array(
				"MAIN" => array(
					"PROFILE_TYPE" => $type,
					"NAME" => $fields["NAME"],
					"DESCRIPTION" => $fields["DESCRIPTION"],
					"MODE" => $fields["MODE"]
				)
			)
		);
	}

	public function getAdminMessage()
	{
		$result = array();
		$message = '';

		if($this->isLicenseWrong())
			$message = Loc::getMessage('SALE_DLVRS_ADD_LICENSE_WRONG');
		elseif(!Location::isInstalled() && !empty($_REQUEST['ID']))
			$message = Loc::getMessage('SALE_DLVRS_ADD_LOC_INSTALL');

		if(strlen($message) > 0)
		{
			$result = array(
				'MESSAGE' => $message,
				"TYPE" => "ERROR",
				"HTML" => true
			);
		}

		return $result;
	}

	protected function isLicenseWrong()
	{
		return Option::get('sale', RestClient::WRONG_LICENSE_OPTION, 'N') == 'Y';
	}

	static public function execAdminAction()
	{
		$result = new \Bitrix\Sale\Result();
		Asset::getInstance()->addJs("/bitrix/js/sale/additional_delivery.js", true);
		Asset::getInstance()->addString('<link rel="stylesheet" type="text/css" href="/bitrix/css/sale/additional_delivery.css">');
		return $result;
	}

	static public function getAdminAdditionalTabs()
	{
		self::install();

		ob_start();
		require_once(__DIR__.'/location/admin_tab.php');
		$content = ob_get_contents();
		ob_end_clean();

		return array(
			array(
				"TAB" => Loc::getMessage('SALE_DLVRS_ADD_LOC_TAB'),
				"TITLE" => Loc::getMessage('SALE_DLVRS_ADD_LOC_TAB_TITLE'),
				"CONTENT" => $content
			)
		);
	}

	public static function install()
	{
		global $DB;

		if(!file_exists($_SERVER["DOCUMENT_ROOT"].'/bitrix/css/sale/additional_delivery.css'))
		{
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/css",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/css/sale", true, true
			);
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js/sale", true, true
			);
			CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/sale", true, true
			);
		}

		$con = \Bitrix\Main\Application::getConnection();

		if(!$con->isTableExists('b_sale_hdale'))
		{
			$DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/db/".$con->getType()."/install.sql");
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('sale', 'onSaleDeliveryExtraServicesClassNamesBuildList' , 'sale', '\Sale\Handlers\Delivery\AdditionalHandler', 'onSaleDeliveryExtraServicesClassNamesBuildList');

		return parent::install();
	}

	public static function unInstall()
	{
		global $DB;

		if(file_exists($_SERVER["DOCUMENT_ROOT"].'/bitrix/css/sale/additional_delivery.css'))
		{
			DeleteDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/css",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/css/sale"
			);
			DeleteDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/js",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/js/sale"
			);
			DeleteDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/tools",
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/tools/sale"
			);
		}

		$con = \Bitrix\Main\Application::getConnection();

		if(!$con->isTableExists('b_sale_hdale'))
		{
			$DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/handlers/delivery/additional/install/db/".$con->getType()."/uninstall.sql");
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('sale', 'onSaleDeliveryExtraServicesClassNamesBuildList' , 'sale', '\Sale\Handlers\Delivery\AdditionalHandler', 'onSaleDeliveryExtraServicesClassNamesBuildList');

		return parent::install();
	}

	public function getEmbeddedExtraServicesList()
	{
		static $result = null;

		if($result === null)
		{
			$result = array();
			$client = new RestClient();
			$res = $client->getDeliveryExtraServices($this->serviceType);

			if($res->isSuccess())
				$result = $res->getData();
		}

		return $result;
	}

	public static function onSaleDeliveryExtraServicesClassNamesBuildList()
	{
		return new \Bitrix\Main\EventResult(
			\Bitrix\Main\EventResult::SUCCESS,
			array(
				'\Sale\Handlers\Delivery\Additional\ExtraServices\Insurance' => '/bitrix/modules/sale/handlers/delivery/additional/extra_services/insurance.php',
				'\Sale\Handlers\Delivery\Additional\ExtraServices\Lift' => '/bitrix/modules/sale/handlers/delivery/additional/extra_services/lift.php'
			),
			'sale'
		);
	}

	static public function isCompatible(Shipment $shipment)
	{
		$client = new RestClient();
		return $client->isServerAlive();
	}
}