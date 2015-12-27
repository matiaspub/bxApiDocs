<?php

namespace Bitrix\Sale\TradingPlatform;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Main\Entity\Result;
use Bitrix\Sale\TradingPlatformTable;
use Bitrix\Main\EventManager;
/**
 * Class Platform
 * Base class for trading platforms.
 * @package Bitrix\Sale\TradingPlatform
 */
abstract class Platform
{
	protected $logger;
	protected $logLevel = Logger::LOG_LEVEL_ERROR;

	protected $code;
	protected $isActive = false;
	protected $settings = array();

	protected $isInstalled = false;
	protected $isNeedCatalogSectionsTab = false;

	protected $id;

	protected static $instances = array();

	const TRADING_PLATFORM_CODE = "";

	/**
	 * Constructor
	 * @param $code
	 */
	protected function __construct($code)
	{
		$this->code = $code;

		$resPltf = TradingPlatformTable::getList(array(
			'filter'=>array(
				'=CODE' => $this->code
			),
		));

		if($platform = $resPltf->fetch())
		{
			$this->isActive = $platform["ACTIVE"] == "Y" ? true : false;
			$this->isNeedCatalogSectionsTab = strlen($platform["CATALOG_SECTION_TAB_CLASS_NAME"]) > 0 ? true : false;

			if(is_array($platform["SETTINGS"]))
				$this->settings = $platform["SETTINGS"];

			$this->isInstalled = true;
			$this->id = $platform["ID"];
		}

		$this->logger = new Logger($this->logLevel);
	}

	protected function __clone(){}

	/**
	 * @param $code
	 * @return \Bitrix\Sale\TradingPlatform\Platform
	 * @throws ArgumentNullException
	 */
	public static function getInstance($code)
	{
		if(strlen($code) <=0)
			throw new ArgumentNullException("code");

		if (!isset(self::$instances[$code]))
			self::$instances[$code] = new static($code);

		return self::$instances[$code];
	}
	/**
	 * @return mixed Id of the current trading platform.
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $level The level of event.
	 * @param string $type Type of event.
	 * @param string $itemId Item idenifyer.
	 * @param string $description Description of event.
	 * @return bool Success or not.
	 */
	public function addLogRecord($level, $type, $itemId, $description)
	{
		return $this->logger->addRecord($level, $type, $itemId, $description);
	}

	/**
	 * @return bool Is the platfor active?.
	 */
	public function isActive()
	{
		return $this->isActive;
	}

	/**
	 * Sets the platform active.
	 * @return bool
	 */
	public function setActive()
	{
		if($this->isActive())
			return true;

		$this->isActive = true;

		if($this->isNeedCatalogSectionsTab && !$this->isSomebodyUseCatalogSectionsTab())
			$this->setCatalogSectionsTabEvent();

		// if we are the first, let's switch on the event to notify about the track numbers changings
		if(!$this->isActiveItemsExist())
			$this->setShipmentTableOnAfterUpdateEvent();

		$res = TradingPlatformTable::update($this->id, array("ACTIVE" => "Y"));

		return $res->isSuccess();
	}

	/**
	 * Sets  the platform inactive.
	 * @return bool
	 */
	public function unsetActive()
	{
		$this->isActive = false;

		if($this->isNeedCatalogSectionsTab && !$this->isSomebodyUseCatalogSectionsTab())
				$this->unSetCatalogSectionsTabEvent();

		$res = TradingPlatformTable::update($this->id, array("ACTIVE" => "N"));

		//If we are last let's switch off unused event about track numbers changing
		if(!$this->isActiveItemsExist())
			$this->unSetShipmentTableOnAfterUpdateEvent();

		return $res->isSuccess();
	}

	protected static function isActiveItemsExist()
	{
		$dbRes = TradingPlatformTable::getList(array(
			'filter' => array(
				'ACTIVE' => 'Y'
			),
			'select' => array('ID')
		));

		if($platform = $dbRes->fetch())
			$result = true;
		else
			$result = false;

		return $result;
	}

	public static function setShipmentTableOnAfterUpdateEvent()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandler(
			'sale',
			'ShipmentOnAfterUpdate',
			'sale',
			'\Bitrix\Sale\TradingPlatform\Helper',
			'onAfterUpdateShipment'
		);
	}

	protected static function unSetShipmentTableOnAfterUpdateEvent()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler(
			'sale',
			'ShipmentOnAfterUpdate',
			'sale',
			'\Bitrix\Sale\TradingPlatform\Helper',
			'onAfterUpdateShipment'
		);
	}

	/**
	 * Shows is another platforms using the iblock section edit page, "trading platforms" tab.
	 * @return bool
	 */
	protected function isSomebodyUseCatalogSectionsTab()
	{
		$result = false;

		$res = TradingPlatformTable::getList(array(
			'select' => array("ID", "CATALOG_SECTION_TAB_CLASS_NAME"),
			'filter' => array(
				'!=CODE' => $this->code,
				'=ACTIVE' => 'Y'
			),
		));

		while($arRes = $res->fetch())
		{
			if(strlen($arRes["CATALOG_SECTIONS_TAB_CLASS_NAME"]) > 0)
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	protected function setCatalogSectionsTabEvent()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandlerCompatible("main", "OnAdminIBlockSectionEdit", "sale", "\\Bitrix\\Sale\\TradingPlatform\\CatalogSectionTab", "OnInit");
	}

	protected function unSetCatalogSectionsTabEvent()
	{
		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler("main", "OnAdminIBlockSectionEdit", "sale", "\\Bitrix\\Sale\\TradingPlatform\\CatalogSectionTab", "OnInit");
	}

	/**
	 * @return array Platform settings.
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * @param array $settings Platform settings.
	 * @return bool Is success?.
	 */
	public function saveSettings(array $settings)
	{
		$this->settings = $settings;
		$result = TradingPlatformTable::update($this->id, array("SETTINGS" => $settings));
		return $result->isSuccess() && $result->getAffectedRowsCount();
	}

	/**
	 * @return bool Is platfom installed?.
	 */
	public function isInstalled()
	{
		return $this->isInstalled;
	}

	/**
	 * Installs platform
	 * @return int Platform Id.
	 */
	public function install()
	{
		$res = TradingPlatformTable::add(array(
			"CODE" => self::TRADING_PLATFORM_CODE,
			"ACTIVE" => "N"
		));

		self::$instances[$this->getCode()] = new static($this->getCode());

		return $res->getId();
	}

	/**
	 * @return bool Is deletion successful?.
	 */
	public function uninstall()
	{
		if($this->isInstalled())
		{
			$this->unsetActive();
			$res = TradingPlatformTable::delete($this->getId());
		}
		else
		{
			$res = new Result();
		}

		unset(self::$instances[$this->getCode()]);
		$this->isInstalled = false;
		return $res->isSuccess();
	}

	/**
	 * @return string Platform code.
	 */
	public function getCode()
	{
		return $this->code;
	}

	public static function onAfterUpdateShipment(\Bitrix\Main\Event $event, array $additional)
	{
		return new EventResult();
	}
}

