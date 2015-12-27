<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

abstract class OrderBase
	extends Internals\Entity
{
	/** @var Internals\Fields */
	protected $calculatedFields = null;

	/** @var Basket */
	protected $basketCollection;

	/** @var ShipmentCollection */
	protected $shipmentCollection;

	/** @var PaymentCollection */
	protected $paymentCollection;

	/** @var PropertyValueCollection */
	protected $propertyCollection;

	/** @var Tax $tax */
	protected $tax = null;

	/** @var int */
	protected $internalId = 0;


	/** @var null|string $calculateType */
	protected $calculateType = null;

	const SALE_ORDER_CALC_TYPE_NEW = 'N';
	const SALE_ORDER_CALC_TYPE_CHANGE = 'C';
	const SALE_ORDER_CALC_TYPE_REFRESH = 'R';

	public function getInternalId()
	{
		static $idPool = 0;
		if ($this->internalId == 0)
		{
			$idPool++;
			$this->internalId = $idPool;
		}
		return $this->internalId;
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		$result = array(
			"LID", "PERSON_TYPE_ID", "CANCELED", "DATE_CANCELED",
			"EMP_CANCELED_ID", "REASON_CANCELED", "STATUS_ID", "DATE_STATUS", "EMP_STATUS_ID",  "DEDUCTED",
			"MARKED", "DATE_MARKED", "EMP_MARKED_ID", "REASON_MARKED",
			"PRICE", "CURRENCY", "DISCOUNT_VALUE", "USER_ID",
			"DATE_INSERT", "DATE_UPDATE", "USER_DESCRIPTION", "ADDITIONAL_INFO", "COMMENTS", "TAX_VALUE",
			"STAT_GID", "RECURRING_ID", "LOCKED_BY",
			"DATE_LOCK", "RECOUNT_FLAG", "AFFILIATE_ID", "DELIVERY_DOC_NUM", "DELIVERY_DOC_DATE", "UPDATED_1C",
			"STORE_ID", "ORDER_TOPIC", "RESPONSIBLE_ID", "DATE_BILL", "DATE_PAY_BEFORE", "ACCOUNT_NUMBER",
			"XML_ID", "ID_1C", "VERSION_1C", "VERSION", "EXTERNAL_ORDER"
		);

		return array_merge($result, static::getCalculatedFields());
	}


	/**
	 * @return array
	 */
	public static function getCalculatedFields()
	{
		return array(
			'PRICE_WITHOUT_DISCOUNT',
			'ORDER_WEIGHT',
			'DISCOUNT_PRICE',
			'BASE_PRICE_DELIVERY',

			'DELIVERY_LOCATION',
			'DELIVERY_LOCATION_ZIP',
			'TAX_LOCATION',
			'TAX_PRICE',

			'VAT_RATE',
			'VAT_VALUE',
			'VAT_SUM',
			'USE_VAT',
		);
	}

	/**
	 * @return array
	 */
	public static function getMeaningfulFields()
	{
		return array('PERSON_TYPE_ID', 'PRICE');
	}

	/**
	 * @return array
	 */
	public static function getAllFields()
	{
		static $fields = null;
		if ($fields == null)
			$fields = array_keys(Internals\OrderTable::getMap());
		return $fields;
	}

	protected function __construct(array $fields = array())
	{
		parent::__construct($fields);
	}

	/**
	 * @param string $siteId
	 * @param int $userId
	 * @param string $currency
	 * @return static
	 */
	public static function create($siteId, $userId = null, $currency = null)
	{
		$order = new static();

		$order->setFieldNoDemand('LID', $siteId);
		if (intval($userId) > 0)
			$order->setFieldNoDemand('USER_ID', $userId);

		if ($currency == null)
			$currency = Internals\SiteCurrencyTable::getSiteCurrency($siteId);

		$order->setFieldNoDemand('CURRENCY', $currency);

		$order->calculateType = static::SALE_ORDER_CALC_TYPE_NEW;

		return $order;
	}

	/**
	 * @param $id
	 * @return null|static
	 * @throws Main\ArgumentNullException
	 * @throws Main\NotImplementedException
	 */
	public static function load($id)
	{
		if (intval($id) <= 0)
			throw new Main\ArgumentNullException("id");

		if ($orderDat = static::loadFromDb($id))
		{
			$order = new static($orderDat);

			$order->calculateType = static::SALE_ORDER_CALC_TYPE_CHANGE;

			return $order;
		}

		return null;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws Main\NotImplementedException
	 */
	static protected function loadFromDb($id)
	{
		throw new Main\NotImplementedException();
	}

	/**
	 * @param $id
	 * @return mixed
	 */
	//abstract protected function loadFromDb($id);

	/**
	 * @param Basket $basket
	 * @return Result
	 * @throws Main\NotSupportedException
	 */
	public function setBasket(Basket $basket)
	{
		if ($this->getId())
			throw new Main\NotSupportedException();

		$result = new Result();

		$basket->setOrder($this);

		$this->basketCollection = $basket;
//		$basket->refreshData(array('PRICE', 'QUANTITY'));

		if (!$this->isMathActionOnly())
		{
			/** @var Result $r */
			$r = $basket->refreshData(array('PRICE', 'QUANTITY', 'COUPONS'));
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
		}

//		/** @var Result $r */
//		$r = $this->setField("PRICE", $basket->getPrice());
//		if (!$r->isSuccess(true))
//		{
//			$result->addErrors($r->getErrors());
//			return $result;
//		}
		//$this->setField("CURRENCY", $basket->get)

		return $result;
	}

	/**
	 * Return order basket.
	 *
	 * @return Basket
	 */
	public function getBasket()
	{
		if (!isset($this->basketCollection) || empty($this->basketCollection))
			$this->basketCollection = $this->loadBasket();
		return $this->basketCollection;
	}

	/**
	 * Return basket exists.
	 *
	 * @return bool
	 */
	public function isNotEmptyBasket()
	{
		if (!isset($this->basketCollection) || empty($this->basketCollection))
			$this->basketCollection = $this->loadBasket();
		return !empty($this->basketCollection);
	}

	/**
	 *
	 */
	abstract protected function loadBasket();


	/**
	 * @param $name
	 * @param $value
	 * @return bool|void
	 * @throws Main\ArgumentException
	 */
	public function setField($name, $value)
	{
		if ($this->isCalculatedField($name))
		{
			$this->calculatedFields->set($name, $value);
			return new Result();
		}

		return parent::setField($name, $value);
	}

	/**
	 * @internal
	 *
	 * @param $name
	 * @param $value
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function setFieldNoDemand($name, $value)
	{
		if ($this->isCalculatedField($name))
		{
			$this->calculatedFields->set($name, $value);
			return;
		}

		parent::setFieldNoDemand($name, $value);
	}

	/**
	 * @param $name
	 * @return null|string
	 */
	public function getField($name)
	{
		if ($this->isCalculatedField($name))
		{
			return $this->calculatedFields->get($name);
		}

		return parent::getField($name);
	}

	/**
	 * @internal
	 *
	 * @param $name
	 * @param $value
	 * @return Result|void
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public function initField($name, $value)
	{
		if ($this->isCalculatedField($name))
		{
			$this->calculatedFields->set($name, $value);
			return new Result();
		}

		parent::initField($name, $value);
	}

//	/**
//	 * @internal
//	 *
//	 * @param array $values
//	 * @return Result|void
//	 * @throws Main\ArgumentOutOfRangeException
//	 */
//	public function setFieldsNoDemand(array $values)
//	{
//		foreach($values as $name => $value)
//		{
//			$this->setFieldNoDemand($name, $value);
//		}
//	}

	/**
	 * @return PropertyValueCollection
	 */
	public function getPropertyCollection()
	{
		if(empty($this->propertyCollection))
		{
			$this->propertyCollection = $this->loadPropertyCollection();
		}

		return $this->propertyCollection;
	}

	abstract protected function loadPropertyCollection();


	/**
	 * @return int
	 */
	public function getId()
	{
		return intval($this->getField('ID'));
	}

	/**
	 * @return int
	 */
	public function getPersonTypeId()
	{
		return $this->getField('PERSON_TYPE_ID');
	}

	/**
	 * @param $personTypeId
	 *
	 * @return bool|void
	 */
	public function setPersonTypeId($personTypeId)
	{
		return $this->setField('PERSON_TYPE_ID', intval($personTypeId));
	}

	/**
	 * @return float
	 */
	public function getPrice()
	{
		return floatval($this->getField('PRICE'));
	}

	/**
	 * @return float
	 */
	public function getSumPaid()
	{
		return floatval($this->getField('SUM_PAID'));
	}

	/**
	 * @return float
	 */
	public function getDeliveryPrice()
	{
		return floatval($this->getField('PRICE_DELIVERY'));
	}


	/**
	 * @return float
	 */
	public function getDeliveryLocation()
	{
		return $this->getField('DELIVERY_LOCATION');
	}


	/**
	 * @return float
	 */
	public function getTaxPrice()
	{
		return floatval($this->getField('TAX_PRICE'));
	}

	/**
	 * @return float
	 */
	public function getTaxValue()
	{
		return $this->getField('TAX_VALUE');
	}

	/**
	 * @return float
	 */
	public function getDiscountPrice()
	{
		return floatval($this->getField('DISCOUNT_PRICE'));
	}

	/**
	 * @return string
	 */
	public function getCurrency()
	{
		return $this->getField('CURRENCY');
	}


	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->getField('USER_ID');
	}


	/**
	 * @return null|string
	 */
	public function getSiteId()
	{
		return $this->getField('LID');
	}

	/**
	 * @return bool
	 */
	public function isUsedVat()
	{
		$useVat = $this->getField('USE_VAT');
		if ($useVat === null)
		{
			$this->refreshVat();
		}

		return $this->getField('USE_VAT') == "Y"? true : false;
	}

	/**
	 * @return mixed|null
	 */
	public function getVatRate()
	{
		$vatRate = $this->getField('VAT_RATE');
		if ($vatRate === null && $this->getId() > 0)
		{
			$this->refreshVat();
			return $this->getField('VAT_RATE');
		}
		return $vatRate;
	}

	/**
	 * @return float
	 */
	public function getVatSum()
	{
		$vatSum = $this->getField('VAT_SUM');
		if ($vatSum === null && $this->getId() > 0)
		{
			$this->refreshVat();
			return $this->getField('VAT_SUM');
		}
		return $vatSum;
	}

	/**
	 * @throws Main\ArgumentOutOfRangeException
	 */
	protected function resetVat()
	{
		$this->setFieldNoDemand('USE_VAT', 'N');
		$this->setFieldNoDemand('VAT_RATE', 0);

		$this->setFieldNoDemand('VAT_SUM', 0);
		$this->setFieldNoDemand('VAT_DELIVERY', 0);
	}

	/**
	 * @internal
	 */
	public function refreshVat()
	{
		$result = new Result();

		if (($basket = $this->getBasket()) && count($basket) > 0)
		{
			$this->resetVat();

			$basketVatRate = $basket->getVatRate();
			if ($basketVatRate > 0)
			{
				/** @var Result $r */
				$r = $this->setField('USE_VAT', 'Y');
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}

				/** @var Result $r */
				$r = $this->setField('VAT_RATE', $basketVatRate);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}

				/** @var Result $r */
				$r = $this->setField('VAT_SUM', $basket->getVatSum());
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function isShipped()
	{
		return $this->getField('DEDUCTED');
	}

	/**
	 * @return bool
	 */
	public function isExternal()
	{
		return ($this->getField('EXTERNAL_ORDER') == "Y");
	}



	/**
	 * @param $field
	 * @return bool
	 */
	protected function isCalculatedField($field)
	{
		if ($this->calculatedFields == null )
		{
			$this->calculatedFields = new Internals\Fields();
		}

		return (in_array($field, $this->getCalculatedFields()));
	}

	/**
	 * @return Entity\AddResult|Entity\UpdateResult|mixed
	 */
	abstract public function save();



	private static function setStatus($value)
	{

	}




	/**
	 * @param $price
	 */
	public function setVatSum($price)
	{
		$this->setField('VAT_SUM', $price);
	}

	/**
	 * @param $price
	 */
	public function setVatDelivery($price)
	{
		$this->setField('VAT_DELIVERY', $price);
	}


	/**
	 * @return Main\Type\DateTime
	 */
	public function getDateInsert()
	{
		return $this->getField('DATE_INSERT');
	}

	/**
	 * @return null|string
	 */
	public function getCalculateType()
	{
		return $this->calculateType;
	}

	/**
	 * @param string $event
	 * @return bool
	 */
	public static function getEventListUsed($event)
	{
		return array();
	}
}