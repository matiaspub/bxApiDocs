<?php
namespace Bitrix\Sale\Delivery;

use Bitrix\Main\Entity;

class CalculationResult extends Entity\Result
{
	/** @var string */
	protected $description = "";
	/** @var string */
	protected $periodDescription = "";
	/** @var bool */
	protected $nextStep = false;
	/** @var int */
	protected $packsCount = 0;
	/** @var float */
	protected $extraServicesPrice = 0;
	/** @var float */
	protected $deliveryPrice = 0;
	/** @var string $tmpData */
	protected $tmpData = "";


	static public function __construct() { parent::__construct(); }

	/**	@return float */
	public function getDeliveryPrice() { return $this->deliveryPrice; }

	/** @param float $price */
	public function setDeliveryPrice($price) { $this->deliveryPrice = $price; }

	/** @return float  */
	public function getExtraServicesPrice() { return $this->extraServicesPrice; }

	/** @param float $price */
	public function setExtraServicesPrice($price) { $this->extraServicesPrice = $price; }

	/**	@return float */
	public function getPrice() { return $this->deliveryPrice + $this->extraServicesPrice;	}

	/** @param string $description */
	public function setDescription($description) { $this->description = $description; }

	/** @return string */
	public function getDescription() { return $this->description; }

	/** @param string $description */
	public function setPeriodDescription($description) { $this->periodDescription = $description; }

	/** @return string */
	public function getPeriodDescription() { return $this->periodDescription; }

	public function setAsNextStep() { $this->nextStep = true; }

	/** @return string */
	public function isNextStep() { return $this->nextStep; }

	/**	@return int */
	public function getPacksCount() { return $this->packsCount; }

	/** @param int $count */
	public function setPacksCount($count) { $this->packsCount = $count; }

	/**	@return int */
	public function getTmpData() { return $this->tmpData; }

	/** @param string $data */
	public function setTmpData($data) { $this->tmpData = $data; }
}