<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Sale;


use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

class Tax
{
	/** @var OrderBase|null */
	protected $order = null;

	/** @var array */
	protected $list = null;

	/** @var array  */
	protected $availableList = null;

	/** @var array */
	protected $changedValues = array();

	/** @var bool  */
	protected $deliveryTax = null;

	/** @var bool  */
	protected $isClone = false;


	protected function __construct()
	{

	}

	/**
	 * @return array
	 */
	public function getTaxList()
	{
		if ($this->list === null)
		{
			$this->list = $this->loadList();
		}

		return $this->list;
	}


	/**
	 * @param array $list
	 */
	public function initTaxList(array $list)
	{
		if (!empty($list))
			$this->list = $list;
	}

	/**
	 * @return OrderBase
	 */
	public function getOrder()
	{
		return $this->order;
	}


	/**
	 * Calculation of taxes
	 *
	 * @return Result
	 * @throws ObjectNotFoundException
	 */
	
	/**
	* <p>Метод расчитывает налоги. Метод нестатический.</p> <p>Без параметров</p>
	*
	*
	* @return \Bitrix\Sale\Result 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/tax/calculate.php
	* @author Bitrix
	*/
	public function calculate()
	{
		/** @var Result $result */
		$result = new Result();

		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new ObjectNotFoundException('Entity "Order" not found');
		}

		/** @var Basket $basket */
		if (!$basket = $order->getBasket())
		{
			throw new ObjectNotFoundException('Entity "Basket" not found');
		}

		$taxResult = array();

		$taxList = $this->getTaxList();
		
		$taxExempt = static::loadExemptList($order->getUserId());

		$fields = array(
			"SITE_ID" => $order->getSiteid(),
			"PERSON_TYPE_ID" => $order->getPersonTypeId(),
			"TAX_LOCATION" => $order->getTaxLocation(),
			"CURRENCY" => $order->getCurrency(),
			"USE_VAT" => $order->isUsedVat(),
			"VAT_RATE" => $order->getVatRate(),
			"VAT_SUM" => $order->getVatSum(),
		);

		if (is_array($taxExempt))
		{
			$fields['TAX_EXEMPT'] = $taxExempt;
		}

		if (is_array($taxList) && !empty($taxList))
		{
			$fields['TAX_LIST'] = $taxList;
		}

		/** @var Basket $basket */
		$basket = $order->getBasket();

		if (empty($basket))
			return $result;

		/** @var BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			if ($basketItem->getQuantity() == 0)
				continue;
			$fields['BASKET_ITEMS'][] = $basketItem->getFieldValues();
		}

		\CSaleTax::calculateTax($fields, array(), $errors = array());

		if (!$order->isUsedVat() && is_array($fields['TAX_LIST']))
		{
			$taxResult['TAX_LIST'] = $fields['TAX_LIST'];
		}

		if (array_key_exists('TAX_PRICE', $fields) && floatval($fields['TAX_PRICE']) >= 0)
		{
			$taxResult['TAX_PRICE'] = $fields['TAX_PRICE'];
		}

		if (array_key_exists('VAT_SUM', $fields) && floatval($fields['VAT_SUM']) > 0)
		{
			$taxResult['VAT_SUM'] = $fields['VAT_SUM'];
		}

		if (array_key_exists('TAX_LIST', $fields))
		{
			$newTaxList = $this->checkModifyTaxList($fields['TAX_LIST']);
			$taxResult['TAX_LIST'] = $newTaxList;

			$this->list = $newTaxList;
		}

		if (!$order->isUsedVat() && empty($this->list) && is_array($this->list))
		{
			$taxResult['TAX_PRICE'] = 0;
		}

		if (!empty($taxResult))
		{
			$result->setData($taxResult);
		}

		return $result;
	}

	/**
	 * @return Result
	 * @throws ObjectNotFoundException
	 */
	public function calculateDelivery()
	{
		/** @var Result $result */
		$result = new Result();

		$taxResult = array();

		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new ObjectNotFoundException('Entity "Order" not found');
		}
		
		if ($order->getId() > 0 || (!empty($this->list) && is_array($this->list)))
		{
			$taxList = $this->getTaxList();
		}
		else
		{
			$taxList = $this->getAvailableList();
		}

		$taxExempt = static::loadExemptList($order->getUserId());

		/** @var Basket $basket */
		if (!$basket = $order->getBasket())
		{
			throw new ObjectNotFoundException('Entity "Basket" not found');
		}

		$fields = array(
			"TAX_LOCATION" => $order->getTaxLocation(),
			"DELIVERY_PRICE" => $order->getDeliveryPrice(),
			"USE_VAT" => $order->isUsedVat(),

			"VAT_RATE" => $order->getVatRate(),
			"VAT_SUM" => $basket->getVatSum(),

			"CURRENCY" => $order->getCurrency(),
		);


		if (!empty($taxExempt))
		{
			$fields['TAX_EXEMPT'] = $taxExempt;
		}

		if (!empty($taxList))
		{
			$fields['TAX_LIST'] = $taxList;
		}

		$options = array();

		if (($isDeliveryCalculate = $this->isDeliveryCalculate()))
		{
			$options['COUNT_DELIVERY_TAX'] = ($isDeliveryCalculate === true ? "Y" : "N");
		}


		\CSaleTax::calculateDeliveryTax($fields, $options, $errors = array());

		if (array_key_exists('TAX_PRICE', $fields) && floatval($fields['TAX_PRICE']) > 0)
		{
			$taxResult['TAX_PRICE'] = $fields['TAX_PRICE'];
		}

		if (array_key_exists('VAT_SUM', $fields) && floatval($fields['VAT_SUM']) > 0)
		{
			$taxResult['VAT_SUM'] = $fields['VAT_SUM'];
		}

		if (array_key_exists('VAT_DELIVERY', $fields) && floatval($fields['VAT_DELIVERY']) > 0)
		{
			$taxResult['VAT_DELIVERY'] = $fields['VAT_DELIVERY'];
		}


		if ($isDeliveryCalculate && array_key_exists('TAX_LIST', $fields) && !empty($fields['TAX_LIST']) && is_array($fields['TAX_LIST']))
		{
			$newTaxList = $this->checkModifyTaxList($fields['TAX_LIST']);
			$this->list = $newTaxList;
		}

		if (!empty($taxResult))
		{
			$result->setData($taxResult);
		}


		return $result;
	}


	/**
	 * @param array $taxList
	 * @return array
	 */
	protected function checkModifyTaxList(array $taxList)
	{
		$oldTaxList = $this->loadList();

		$taxIndexList = array();

		if (!empty($oldTaxList) && is_array($oldTaxList))
		{
			$oldTaxIndexList = array();
			foreach ($oldTaxList as $taxOldKey => $taxOldValue)
			{
				$oldTaxIndexList[$taxOldValue['NAME']."|".$taxOldValue['CODE']] = $taxOldKey;
			}

			foreach ($taxList as $taxValue)
			{
				if (array_key_exists($taxValue['NAME']."|".$taxValue['CODE'], $oldTaxIndexList))
				{
					$taxIndexList[$taxValue['NAME']."|".$taxValue['CODE']] = $oldTaxIndexList[$taxValue['NAME']."|".$taxValue['CODE']];
				}
			}
		}

		if (!empty($taxList) && is_array($taxList))
		{
			foreach ($taxList as $taxKey => $taxValue)
			{
				$taxCode = $taxValue['NAME']."|".$taxValue['CODE'];
				if (isset($taxIndexList[$taxCode]))
				{
					$oldTaxKey = $taxIndexList[$taxCode];

					$oldTaxValueDat = $oldTaxList[$oldTaxKey];
					foreach($taxValue as $key => $value)
					{
						if (array_key_exists($key, $oldTaxValueDat))
						{
							if ($oldTaxValueDat[$key] != $value)
							{
								$oldTaxList[$oldTaxKey][$key] = $value;

								if (!in_array($taxCode, $this->changedValues))
								{
									$this->changedValues[$taxCode] = true;
								}
							}
						}
						else
						{
							$oldTaxList[$oldTaxKey][$key] = $value;
							if (!in_array($taxCode, $this->changedValues))
							{
								$this->changedValues[$taxCode] = true;
							}
						}
					}
				}
				else
				{
					$oldTaxList[] = array(
						'NAME' => $taxValue['NAME'],
						'IS_PERCENT' => $taxValue['IS_PERCENT'],
						'VALUE' => $taxValue['VALUE'],
						'VALUE_MONEY' => $taxValue['VALUE_MONEY'],
						'APPLY_ORDER' => $taxValue['APPLY_ORDER'],
						'IS_IN_PRICE' => $taxValue['IS_IN_PRICE'],
						'TAX_VAL' => $taxValue['TAX_VAL'],
						'CODE' => $taxValue['CODE'],
					);

	//				if (!in_array($taxCode, $this->changedValues))
	//				{
	//					$this->changedValues[$taxCode] = true;
	//				}
				}
			}

			$vat1cFound = false;
			$taxListModify = array();
			foreach($oldTaxList as $taxOrder)
			{
				if($taxOrder['CODE']=='VAT1C')
					$vat1cFound = true;
			}

			if($vat1cFound)
			{
				foreach($oldTaxList as $taxOrder)
				{
					if($taxOrder['CODE']!='VAT')
					{
						$taxListModify[] = $taxOrder;
					}
				}

				if(count($taxListModify)>0)
					$oldTaxList = $taxListModify;
			}
		}
		else
		{
			$oldTaxList = array();
		}

		return $oldTaxList;
	}


	/**
	 * @return Result
	 * @throws ObjectNotFoundException
	 */
	public function save()
	{

		$result = new Result();
		/** @var Order $order */
		if (!$order = $this->getOrder())
		{
			throw new ObjectNotFoundException('Entity "Order" not found');
		}

		//DoSaveOrderTax
		\CSaleTax::DoSaveOrderTax($order->getId(), $this->getTaxList(), $errors = array());

		if (!empty($errors) && is_array($errors))
		{
			foreach ($errors as $error)
			{
				$result->addError(new EntityError($error));
			}
		}


		if ($order->getId() > 0)
		{
			OrderHistory::collectEntityFields('TAX', $order->getId());
		}

		return $result;
	}

	/**
	 * @param OrderBase $order
	 * @return array|null
	 */
	public static function load(OrderBase $order)
	{
		$tax = new static();
		$tax->order = $order;

		if ($order->getId() > 0)
		{
			$tax->list = $tax->loadList($order);
		}
		else
		{
			$tax->list = $tax->getAvailableList();
		}

		return $tax;
	}

	/**
	 * @return array
	 */
	protected function loadList()
	{
		$resultList = array();
		$order = $this->getOrder();

		if ($order->getId() <= 0)
			return null;

			$dbTaxList = \CSaleOrderTax::GetList(
				array("APPLY_ORDER" => "ASC"),
				array("ORDER_ID" => $order->getId())
			);
			while ($taxList = $dbTaxList->Fetch())
			{
				$taxList['NAME'] = $taxList['TAX_NAME'];
				$resultList[] = $taxList;
			}

		return (!empty($resultList) ? $resultList : null);
	}


	public function resetTaxList()
	{
		$this->list = array();
	}
	/**
	 *
	 */
	public function refreshData()
	{
		$result = new Result();
		$this->resetTaxList();

		/** @var Result $r */
		$r = $this->calculate();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		return $this->calculateDelivery();
	}

	/**
	 * @param $userId
	 * @return array
	 */
	public static function loadExemptList($userId)
	{
		$exemptList = array();

		static $proxyUserGroups = array();
		static $proxyTaxExemptList = array();

		if (!empty($proxyUserGroups[$userId]))
		{
			$userGroups = $proxyUserGroups[$userId];
		}
		else
		{
			$userGroups = \CUser::GetUserGroup($userId);
			$proxyUserGroups[$userId] = $userGroups;
		}


		$proxyTaxExemptKey = md5(join('|', $userGroups));

		if (array_key_exists($proxyTaxExemptKey, $proxyTaxExemptList))
		{
			$exemptList = $proxyTaxExemptList[$proxyTaxExemptKey];
		}
		else
		{

			$dbTaxExemptList = \CSaleTax::GetExemptList(array("GROUP_ID" => $userGroups));
			while ($taxExemptList = $dbTaxExemptList->Fetch())
			{
				if (!in_array(intval($taxExemptList["TAX_ID"]), $exemptList))
					$exemptList[] = intval($taxExemptList["TAX_ID"]);
			}

			$proxyTaxExemptList[$proxyTaxExemptKey] = $exemptList;
		}



		return $exemptList;
	}

	/**
	 * @return array
	 */
	public function getAvailableList()
	{
		if ($this->availableList === null)
		{
			$this->availableList = $this->loadAvailableList();
		}

		return $this->availableList;

	}

	/**
	 * @return array
	 */
	protected function loadAvailableList()
	{
		$order = $this->getOrder();
		$basket = $order->getBasket();
		if (!$basket)
			return null;

		$availableList = array();

		if (!$order->isUsedVat())
		{
			$taxExemptList = static::loadExemptList($order->getUserId());

			$taxRateRes = \CSaleTaxRate::GetList(
				array("APPLY_ORDER" => "ASC"),
				array(
					"LID" => $order->getSiteId(),
					"PERSON_TYPE_ID" => $order->getPersonTypeId(),
					"ACTIVE" => "Y",
					"LOCATION_CODE" => $order->getTaxLocation(),
				)
			);
			while ($taxRate = $taxRateRes->GetNext())
			{
				if (!in_array(intval($taxRate["TAX_ID"]), $taxExemptList))
				{
					if ($taxRate["IS_PERCENT"] != "Y")
					{
						$taxRate["VALUE"] = PriceMaths::roundPrecision(\CCurrencyRates::convertCurrency($taxRate["VALUE"], $taxRate["CURRENCY"], $order->getCurrency()));
						$taxRate["CURRENCY"] = $order->getCurrency();
					}
					$availableList[] = $taxRate;
				}
			}
		}
		else
		{
			$availableList[] = array(
				"NAME" => Loc::getMessage("SOA_VAT"),
				"IS_PERCENT" => "Y",
				"VALUE" => $order->getVatRate() * 100,
				"VALUE_FORMATED" => "(".($order->getVatRate() * 100)."%, ".GetMessage("SOA_VAT_INCLUDED").")",
				"VALUE_MONEY" => $order->getVatSum(),
				"VALUE_MONEY_FORMATED" => SaleFormatCurrency($order->getVatSum(), $order->getCurrency()),
				"APPLY_ORDER" => 100,
				"IS_IN_PRICE" => "Y",
				"CODE" => "VAT"
			);
		}

		return $availableList;
	}

	/**
	 * @param $value
	 */
	public function setDeliveryCalculate($value)
	{
		$this->deliveryTax = ($value === true? true : false);
	}

	/**
	 * @return bool
	 */
	public function isDeliveryCalculate()
	{
		return $this->deliveryTax;
	}

	/**
	 * @internal
	 * @param \SplObjectStorage $cloneEntity
	 *
	 * @return Tax
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		$taxClone = clone $this;
		$taxClone->isClone = true;

		if (!$cloneEntity->contains($this))
		{
			$cloneEntity[$this] = $taxClone;
		}

		if ($this->order)
		{
			if ($cloneEntity->contains($this->order))
			{
				$taxClone->order = $cloneEntity[$this->order];
			}
		}

		return $taxClone;
	}

	/**
	 * @return bool
	 */
	public function isClone()
	{
		return $this->isClone;
	}

}