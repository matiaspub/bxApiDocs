<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sale;

use Bitrix\Main;
use Bitrix\Main\Localization;
use Bitrix\Sale\Internals;

Localization\Loc::loadMessages(__FILE__);

abstract class BasketItemBase
	extends Internals\CollectableEntity
{
	/** @var BasketPropertiesCollection $propertyCollection */
	protected $propertyCollection;

	/** @var Internals\Fields */
	protected $calculatedFields;

	/** @var  ProviderBase */
	protected $provider;

	const TYPE_SET = 1;

	/** @var string */
	protected $internalId = null;

	protected static $idBasket = 0;

	/**
	 * @return int
	 */
	public function getBasketCode()
	{
		if ($this->internalId == null)
		{
			if ($this->getId() > 0)
			{
				$this->internalId = $this->getId();
			}
			else
			{
				static::$idBasket++;
				$this->internalId = 'n'.static::$idBasket;
			}

//			$this->internalId = static::$idBasket;
		}
		return $this->internalId;
	}

	/**
	 * @return array
	 */
	public static function getSettableFields()
	{
		$result = array(
			"PRICE", "CURRENCY", "BASE_PRICE",  "WEIGHT", "QUANTITY", "DELAY", "NAME", "CAN_BUY", "NOTES", "DETAIL_PAGE_URL", "DISCOUNT_PRICE", "CATALOG_XML_ID", "PRODUCT_XML_ID", "DISCOUNT_NAME", "DISCOUNT_VALUE", "DISCOUNT_COUPON", "VAT_RATE", "BARCODE_MULTI", "SUBSCRIBE", "CUSTOM_PRICE", "DIMENSIONS",
			"CALLBACK_FUNC", "ORDER_CALLBACK_FUNC", "CANCEL_CALLBACK_FUNC", "PAY_CALLBACK_FUNC", "PRODUCT_PROVIDER_CLASS", "PRODUCT_ID", "PRODUCT_PRICE_ID", "BARCODE_MULTI", "TYPE", "SET_PARENT_ID", "MEASURE_CODE", "MEASURE_NAME",
			'LID', "VAT_INCLUDED", "SORT"
		);

		return array_merge($result, static::getCalculatedFields());
	}

	/**
	 * @return array
	 */
	public static function getCalculatedFields()
	{
		return array(
			'DISCOUNT_PRICE_PERCENT',
			'IGNORE_CALLBACK_FUNC',
			'DEFAULT_PRICE',
			'DISCOUNT_LIST'
		);
	}

	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return static::getSettableFields();
	}

	/**
	 * @return array
	 */
	public static function getMeaningfulFields()
	{
		return array('QUANTITY', 'PRICE', 'CUSTOM_PRICE');
	}


	/**
	 * @param array $fields				Data.
	 */
	protected function __construct(array $fields = array())
	{
		parent::__construct($fields);
		$this->calculatedFields = new Internals\Fields();
	}

	/**
	 * @param Basket $basket			Basket.
	 * @param string $moduleId			Module id.
	 * @param int $productId			Product id.
	 * @return BasketItem
	 */
	public static function create(Basket $basket, $moduleId, $productId)
	{
		$fields = array(
			"MODULE" => $moduleId,
			"PRODUCT_ID" => $productId,
		);

		$basketItem = new static($fields);
		$basketItem->setCollection($basket);

		return $basketItem;
	}

	/**
	 * @return Result
	 * @throws Main\ObjectNotFoundException
	 */
	public function delete()
	{
		$result = new Result();

		/** @var Result $r */
		$r = $this->setField("QUANTITY", 0);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}
		return parent::delete();
	}

	/**
	 * @param string $name						Field name.
	 * @param string|int|float $value			Field value.
	 * @return Result|bool
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws \Exception
	 */
	public function setField($name, $value)
	{
		if ($this->isCalculatedField($name))
		{
			$this->calculatedFields->set($name, $value);
			return true;
		}

		return parent::setField($name, $value);
	}

	/**
	 * @internal
	 *
	 * @param string $name				Field name.
	 * @param string|int|float $value	Field data.
	 * @return void
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
	 * @param string $name			Field name.
	 * @return mixed|null
	 */
	public function getField($name)
	{
		if ($this->isCalculatedField($name))
		{
			if (isset($this->calculatedFields[$name])
					|| (is_array($this->calculatedFields) && array_key_exists($name, $this->calculatedFields)))
			{
				return $this->calculatedFields->get($name);
			}

			return null;
		}

		$value = parent::getField($name);
		if ($name == "BASE_PRICE" && $value === null)
		{
			$value = PriceMaths::roundPrecision($this->getField('PRICE') + $this->getField('DISCOUNT_PRICE'));
		}

		return $value;
	}

	/**
	 * @param array $fields			Fields list.
	 * @return Result
	 */
	public function setFields(array $fields)
	{
		foreach ($fields as $name => $value)
		{
			if ($this->isCalculatedField($name))
			{
				$this->calculatedFields[$name] = $value;
				unset($fields[$name]);
			}
		}

		if (array_key_exists('CUSTOM_PRICE', $fields) && strval($fields['CUSTOM_PRICE']) != '')
		{
			$this->setField('CUSTOM_PRICE', $fields['CUSTOM_PRICE']);
		}

		if (array_key_exists('VAT_RATE', $fields) && strval($fields['VAT_RATE']) != '')
		{
			$this->setField('VAT_RATE', $fields['VAT_RATE']);
		}

		if (array_key_exists('VAT_INCLUDED', $fields) && strval($fields['VAT_INCLUDED']) != '')
		{
			$this->setField('VAT_INCLUDED', $fields['VAT_INCLUDED']);
		}
		
		if (array_key_exists('PRODUCT_PROVIDER_CLASS', $fields) && strval($fields['PRODUCT_PROVIDER_CLASS']) != '')
		{
			$this->setField('PRODUCT_PROVIDER_CLASS', $fields['PRODUCT_PROVIDER_CLASS']);
		}

		if (array_key_exists('SUBSCRIBE', $fields) && strval($fields['SUBSCRIBE']) != '')
		{
			$this->setField('SUBSCRIBE', $fields['SUBSCRIBE']);
		}

		return parent::setFields($fields);
	}

	/**
	 * @param string $name
	 * @param mixed $oldValue
	 * @param mixed $value
	 *
	 * @return Result
	 */
	protected function onFieldModify($name, $oldValue, $value)
	{
		$r = parent::onFieldModify($name, $oldValue, $value);

		if ($r->isSuccess(true))
		{
			if (($name === 'BASE_PRICE') || ($name === 'DISCOUNT_PRICE'))
			{
				if ($this->getField('CUSTOM_PRICE') !== 'Y')
				{
					$basePrice = $this->getField('BASE_PRICE');

					$r1 = $this->setField('PRICE', $basePrice - $this->getField('DISCOUNT_PRICE'));
					if (!$r1->isSuccess())
						$r->addErrors($r1->getErrors());
				}
			}
		}

		return $r;
	}

	/**
	 * @return bool
	 */
	public function isVatInPrice()
	{
		return $this->getField('VAT_INCLUDED') === 'Y';
	}

	/**
	 * @return float|int
	 */
	public function getVat()
	{
		if ($this->getVatRate() == 0)
			return 0;

		if ($this->isVatInPrice())
			$vat = PriceMaths::roundPrecision(($this->getPrice() * $this->getQuantity() * $this->getVatRate() / ($this->getVatRate() + 1)));
		else
			$vat = PriceMaths::roundPrecision(($this->getPrice() * $this->getQuantity() * $this->getVatRate()));

		return $vat;
	}

	/**
	 * @return float|int
	 */
	public function getInitialPrice()
	{
		$price = PriceMaths::roundPrecision($this->getPrice() * $this->getQuantity());

		if ($this->isVatInPrice())
			$price -= $this->getVat();

		return $price;
	}

	/**
	 * @return float|int
	 */
	public function getFinalPrice()
	{
		$price = PriceMaths::roundPrecision($this->getPrice() * $this->getQuantity());

		if (!$this->isVatInPrice())
			$price += $this->getVat();

		return $price;
	}

	/**
	 * @param string $field			Field name.
	 * @return bool
	 */
	protected function isCalculatedField($field)
	{
		return in_array($field, static::getCalculatedFields());
	}

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
	public function getProductId()
	{
		return $this->getField('PRODUCT_ID');
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
	public function getBasePrice()
	{
		return floatval($this->getField('BASE_PRICE'));
	}

	/**
	 * @return float
	 */
	public function getDefaultPrice()
	{
		return floatval($this->getField('DEFAULT_PRICE'));
	}

	/**
	 * @return float
	 */
	public function getDiscountPrice()
	{
		return $this->getField('DISCOUNT_PRICE');
	}

	/**
	 * @return string
	 */
	public function isCustomPrice()
	{
		return ($this->getField('CUSTOM_PRICE') == "Y"? true : false);
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
	public function getQuantity()
	{
		return floatval($this->getField('QUANTITY'));
	}

	/**
	 * @return float
	 */
	public function getWeight()
	{
		return $this->getField('WEIGHT');
	}


	/**
	 * @return int
	 */
	public function getVatRate()
	{
		return $this->getField('VAT_RATE');
	}

	/**
	 * @return int
	 */
	public function getFUserId()
	{
		return $this->getField('FUSER_ID');
	}

	/**
	 * @param int $id			Order id.
	 * @return void
	 */
	public function setOrderId($id)
	{
		$this->setField('ORDER_ID', intval($id));
	}

	/**
	 * @return string
	 */
	public function isBarcodeMulti()
	{
		return ($this->getField('BARCODE_MULTI') == "Y"? true : false);
	}

	/**
	 * @return bool
	 */
	public function canBuy()
	{
		return ($this->getField('CAN_BUY') == "Y"? true : false);
	}

	/**
	 * @return bool
	 */
	public function isDelay()
	{
		return ($this->getField('DELAY') == "Y"? true : false);
	}


	/**
	 * @return collection
	 */
	abstract public function getPropertyCollection();

	/**
	 * @return bool|string
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function getProvider()
	{

		if ($this->provider !== null)
			return $this->provider;

		$module = $this->getField('MODULE');
		$productProviderClass = $this->getField('PRODUCT_PROVIDER_CLASS');
		if (
			!isset($module)
			|| !isset($productProviderClass)
			|| (strval($productProviderClass) == "")
		)
		{
			return false;
		}

		if (Main\Loader::includeModule($module)
			&& class_exists($productProviderClass)
			&& (array_key_exists("IBXSaleProductProvider", class_implements($productProviderClass))
				|| $productProviderClass instanceof ProviderBase)
		)
		{
			$this->provider = $productProviderClass;
			return $productProviderClass;
		}

		return false;
	}


}