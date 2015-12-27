<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals;
use Bitrix\Currency;

Loc::loadMessages(__FILE__);

abstract class ProviderBase
{
	/** @var Internals\Pool[] */
	protected static $reservationPool = array();

	/** @var array  */
	protected static $hitCache = array();

	/** @var array  */
	protected static $trustData = array();

	/** @var bool */
	protected static $useReadTrustData = false;

	public static function dump($i)
	{
		$s = '';
		/** @var Internals\Pool $v */
		foreach (static::$reservationPool as $k => $v)
		{
			$s .= str_repeat(' ', $i).$k.": ".$v->dump()."\n";
		}
		return $s;
	}

	/**
	 * @param $key
	 * @return Internals\Pool
	 */
	protected static function getReservationPool($key)
	{
		if (!isset(static::$reservationPool[$key]))
			static::$reservationPool[$key] = new Internals\Pool();

		return static::$reservationPool[$key];
	}

	/**
	 * @param $key
	 * @param BasketItem $item
	 * @return float|null
	 */
	public static function getReservationPoolItem($key, BasketItem $item)
	{
		$pool = static::getReservationPool($key);
		return $pool->get($item);
	}

	/**
	 * @param $key
	 * @param BasketItem $item
	 * @param $value
	 */
	protected static function setReservationPoolItem($key, BasketItem $item, $value)
	{
		$pool = static::getReservationPool($key);
		$pool->set($item, $value);
	}

	/**
	 * @param $key
	 * @param BasketItem $item
	 * @param $value
	 */
	protected static function addReservationPoolItem($key, BasketItem $item, $value)
	{
		$pool = static::getReservationPool($key);
		$pool->set($item, $pool->get($item) + $value);
	}

	/**
	 * @param Order $order
	 * @return Result
	 * @throws NotImplementedException
	 * @throws SystemException
	 */
	public static function onOrderSave(Order $order)
	{
		$result = new Result();

		static::resetTrustData($order->getSiteId());

		/** @var Result $r */
		$r = static::applyPoolReservation($order);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		/** @var Result $r */
		$r = static::applyShipment($order);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param Order $order
	 * @return Result
	 * @throws NotImplementedException
	 * @throws NotSupportedException
	 * @throws SystemException
	 */
	private static function applyPoolReservation(Order $order)
	{
		if (!isset(static::$reservationPool[$order->getInternalId()]))
			return new Result();

		$result = new Result();

		$pool = static::getReservationPool($order->getInternalId());

		$poolQuantities = $pool->getQuantities();
		$poolItems = $pool->getItems();

		$shipmentCollection = $order->getShipmentCollection();

		foreach ($poolQuantities as $basketCode => $quantity)
		{
			$quantity = round($quantity, 4);
			if ($quantity == 0)
				continue;

			if (!isset($poolItems[$basketCode]))
				throw new SystemException();

			/** @var BasketItem $basketItem */
			$basketItem = $poolItems[$basketCode];
			$canReserve = false;

			/** @var Result $r */
			$r = Provider::reserveBasketItem($basketItem, $quantity);
			if ($r->isSuccess())
			{
				$reserveBasketItemResult = $r->getData();
				$reservedQuantity = null;

				if (!empty($reserveBasketItemResult) && is_array($reserveBasketItemResult))
				{
					if (array_key_exists('QUANTITY', $reserveBasketItemResult))
					{
						$reservedQuantity = round($reserveBasketItemResult['QUANTITY'], 4);
					}

					if (array_key_exists('HAS_PROVIDER', $reserveBasketItemResult))
					{
						$canReserve = $reserveBasketItemResult['HAS_PROVIDER'];
					}
				}

				if ($reservedQuantity === null)
				{
					$result->addError( new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_WRONG_QUANTITY'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_WRONG_QUANTITY') );
				}
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

			if (!$result->isSuccess())
			{
				return $result;
			}


			$pool->set($basketItem, 0);

			if ($quantity > 0 && $reservedQuantity > $quantity
				|| $quantity < 0 && $reservedQuantity < $quantity)
			{
				$result->addError( new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_QUANTITY_NOT_ENOUGH'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_QUANTITY_NOT_ENOUGH') );

				return $result;
			}

			// not implemented yet
			if ($quantity < 0 && $reservedQuantity != $quantity)
				throw new NotImplementedException();

			if ($canReserve && Configuration::isEnabledReservation() && $quantity != $reservedQuantity)
			{
				$systemShipment = $shipmentCollection->getSystemShipment();

				/** @var ShipmentItemCollection $systemShipmentItemCollection */
				$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();

				if ($shipmentItem = $systemShipmentItemCollection->getItemByBasketCode($basketCode))
				{
					if ($shipmentItem->getReservedQuantity() > 0)
					{
						$needQuantity = $quantity - $reservedQuantity;

						if ($shipmentItem->getReservedQuantity() >= $needQuantity)
						{
							$setQuantity = $shipmentItem->getReservedQuantity() - $needQuantity;
						}
						else
						{
							$setQuantity = 0;
							$needQuantity = $shipmentItem->getReservedQuantity();
						}

						$reservedQuantity += $needQuantity;
						$shipmentItem->setField('RESERVED_QUANTITY', $setQuantity);
					}
				}

				if ($quantity != $reservedQuantity)
				{
					$diffQuantity = $quantity - $reservedQuantity;

					/** @var Shipment $shipment */
					foreach ($shipmentCollection as $shipment)
					{
						if ($shipment->isSystem())
							continue;

						/** @var ShipmentItemCollection $shipmentItemCollection */
						$shipmentItemCollection = $shipment->getShipmentItemCollection();
						if ($shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketCode))
						{
							if ($shipmentItem->getReservedQuantity() >= $diffQuantity)
							{
								$shipmentItem->setField('RESERVED_QUANTITY', $shipmentItem->getReservedQuantity() - $diffQuantity);
								$diffQuantity = 0;
								break;
							}
							else
							{
								$diffQuantity -= $shipmentItem->getReservedQuantity();
								$shipmentItem->setField('RESERVED_QUANTITY', 0);
							}

						}
					}

					if ($diffQuantity > 0 && $reservedQuantity > 0)
					{
						$result->addError( new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_QUANTITY_WRONG_RESIDUE'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_QUANTITY_WRONG_RESIDUE') );

						return $result;
//						throw new SystemException("diffQuantity");
					}
				}
			}

			if ($canReserve)
				$order->setFieldNoDemand('RESERVED', $shipmentCollection->isReserved() ? "Y" : "N");

		}

		return $result;
	}

	/**
	 * @param Order $order
	 * @return Result
	 * @throws NotSupportedException
	 */
	private static function applyShipment(Order $order)
	{
		$result = new Result();

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $order->getShipmentCollection();
		/** @var Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			/** @var Result $r */
			$r = static::shipShipment($shipment);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}


	/**
	 * @param Shipment $shipment
	 * @return array
	 * @throws NotSupportedException
	 * @throws SystemException
	 */
	public static function shipShipment(Shipment $shipment)
	{
		global $APPLICATION;

		$result = new Result();

		$needShip = $shipment->needShip();
		if ($needShip === null || ($needShip === false && $shipment->getId() <= 0))
			return $result;

		$reverse = false;

		$resultList = array();
		$storeData = array();


		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$basketList = static::getBasketFromShipmentItemCollection($shipmentItemCollection);

		$basketCountList = static::getBasketCountFromShipmentItemCollection($shipmentItemCollection);

		$basketProviderMap = static::createProviderBasketMap($basketList, array('QUANTITY', 'RESERVED'));
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (Configuration::useStoreControl())
		{
			/** @var Result $r */
			$r = static::getStoreDataFromShipmentItemCollection($shipmentItemCollection);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			else
			{
				$storeData = $r->getData();
			}
		}

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				if ($provider instanceof Provider)
				{
					throw new NotSupportedException('provider not supported');
				}
				elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
				{

					foreach ($providerBasketItemList as $providerBasketItem)
					{

						if ($providerBasketItem['BASKET_ITEM']->isBundleParent())
						{
							continue;
						}

						if (!$providerBasketItem['BASKET_ITEM']->isEmptyItem())
						{

							$data = array(
								"BASKET_ITEM" => $providerBasketItem['BASKET_ITEM'],
								"PRODUCT_ID" => $providerBasketItem['PRODUCT_ID'],
								"QUANTITY"   => $providerBasketItem['QUANTITY'],
								"PRODUCT_RESERVED"   => $providerBasketItem['RESERVED'],
								'UNDO_DEDUCTION' => $needShip? 'N' : 'Y',
								'EMULATE' => 'N',
							);

							$resultProductData = array();

							if (Configuration::useStoreControl())
							{
								$quantity = $basketCountList[$providerBasketItem['BASKET_CODE']];

								if (!empty($storeData) && is_array($storeData) && isset($storeData[$providerBasketItem['BASKET_CODE']]))
								{
									$data['STORE_DATA'] = $storeData[$providerBasketItem['BASKET_CODE']];
								}

								if (!empty($data['STORE_DATA']))
								{
									$allBarcodeQuantity = 0;
									foreach($data['STORE_DATA'] as $basketShipmentItemStore)
									{
										$allBarcodeQuantity += $basketShipmentItemStore['QUANTITY'];
									}

									if ($quantity > $allBarcodeQuantity)
									{
										$result->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_SHIPMENT_SHIPPED_LESS_QUANTITY', array(
											'#PRODUCT_NAME#' => $providerBasketItem['BASKET_ITEM']->getField('NAME')
										)), 'SALE_PROVIDER_SHIPMENT_SHIPPED_LESS_QUANTITY'));

										$resultProductData['RESULT'] = false;
									}
									elseif ($quantity < $allBarcodeQuantity)
									{
										$result->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_SHIPMENT_SHIPPED_MORE_QUANTITY', array(
											'#PRODUCT_NAME#' => $providerBasketItem['BASKET_ITEM']->getField('NAME')
										)), 'SALE_PROVIDER_SHIPMENT_SHIPPED_MORE_QUANTITY'));

										$resultProductData['RESULT'] = false;
									}
								}

							}

							if (!isset($resultProductData['RESULT'])
								|| $resultProductData['RESULT'] !== false)
							{
								$APPLICATION->ResetException();
								$resultProductData = $provider::DeductProduct($data);
								if ($ex = $APPLICATION->GetException())
								{
									$result->addError( new ResultError($ex->GetString(), $ex->GetID()) );
								}
							}
						}
						else
						{
							$resultProductData['RESULT'] = true;
						}

						$resultList[$providerBasketItem['BASKET_CODE']] = $resultProductData;

						if (isset($resultProductData['RESULT'])
							&& $resultProductData['RESULT'] === false && $needShip)
						{
							$reverse = true;
							break;
						}

					}

				}
			}
		}

		if ($reverse === true)
		{
			static::reverseShipment($shipment, $resultList);
		}
		else
		{
			static::setShipmentItemReserved($shipment);
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @param array $shippedList
	 * @throws NotSupportedException
	 * @throws SystemException
	 */
	private function reverseShipment(Shipment $shipment, array $shippedList)
	{
		$needShip = $shipment->needShip();

		$correct = null;

		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$basketList = static::getBasketFromShipmentItemCollection($shipmentItemCollection);

		$bundleIndexList = static::getBundleIndexFromShipmentItemCollection($shipmentItemCollection);

		$basketProviderMap = static::createProviderBasketMap($basketList, array('QUANTITY', 'RESERVED'));
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (Configuration::useStoreControl())
		{
			/** @var Result $r */
			$r = static::getStoreDataFromShipmentItemCollection($shipmentItemCollection);
		}

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				if ($provider instanceof Provider)
				{
					throw new NotSupportedException('provider not supported');
				}
				elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
				{

					foreach ($providerBasketItemList as $providerBasketItem)
					{
						if ($providerBasketItem['BASKET_ITEM']->isBundleParent())
						{
							continue;
						}

						$basketCode = $providerBasketItem['BASKET_CODE'];
						if (!isset($shippedList[$basketCode]))
						{
							continue;
						}

						if (!$providerBasketItem['BASKET_ITEM']->isEmptyItem())
						{
							$data = array(
								"BASKET_ITEM" => $providerBasketItem['BASKET_ITEM'],
								"PRODUCT_ID" => $providerBasketItem['PRODUCT_ID'],
								"QUANTITY"   => $providerBasketItem['QUANTITY'],
								"PRODUCT_RESERVED"   => $providerBasketItem['RESERVED'],
								'UNDO_DEDUCTION' => $needShip? 'Y' : 'N',
								'EMULATE' => 'N',
							);

							if (Configuration::useStoreControl() && !empty($storeData) && is_array($storeData) && isset($storeData[$providerBasketItem['BASKET_CODE']]))
							{
								$data['STORE_DATA'] = $storeData[$providerBasketItem['BASKET_CODE']];

								$barcodeReverseList = array();

								if (!empty($shippedList[$basketCode]['BARCODE']) && is_array($shippedList[$basketCode]['BARCODE']))
								{
									foreach ($shippedList[$basketCode]['BARCODE'] as $barcodeValue => $barcodeShipped)
									{
										if ($barcodeShipped === true)
										{
											$barcodeReverseList[] = $barcodeValue;
										}
									}

									foreach ($data['STORE_DATA'] as $storeId => $barcodeData)
									{
										if (!empty($barcodeData['BARCODE']) && is_array($barcodeData['BARCODE']))
										{
											if (empty($barcodeReverseList))
											{
												$data['STORE_DATA'][$storeId]['BARCODE'] = array();
											}
											else
											{
												foreach ($barcodeData['BARCODE'] as $barcodeId => $barcodeValue)
												{
													if (!in_array($barcodeValue, $barcodeReverseList))
													{
														unset($data['STORE_DATA'][$storeId]['BARCODE'][$barcodeId]);
														$data['STORE_DATA'][$storeId]['QUANTITY'] -= 1;
													}
												}
											}

										}
									}
								}
							}

							$resultProductData = $provider::DeductProduct($data);
						}
						else
						{
							$resultProductData['RESULT'] = true;
						}


						$result[$providerBasketItem['BASKET_CODE']] = $resultProductData;

						if (isset($resultProductData['RESULT'])
							&& $resultProductData['RESULT'] === true)
						{
							$correct = true;
						}

					}

				}
			}
		}

		if ($correct === true)
		{
			$shipment->setField('DEDUCTED', $needShip? 'N' : 'Y');
		}

		if (!empty($result)
			&& !empty($bundleIndexList) && is_array($bundleIndexList))
		{

			foreach ($bundleIndexList as $bundleParentBasketCode => $bundleChildList)
			{
				$tryShipmentBundle = false;
				foreach($bundleChildList as $bundleChildBasketCode)
				{
					if (isset($result[$bundleChildBasketCode])
						&& $result[$bundleChildBasketCode]['RESULT'] === true)
					{
						$tryShipmentBundle = true;
					}
					else
					{
						$tryShipmentBundle = false;
						break;
					}
				}

				$result[$bundleParentBasketCode] = array(
					'RESULT' => $tryShipmentBundle
				);
			}

		}
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function setShipmentItemReserved(Shipment $shipment)
	{
		$needShip = $shipment->needShip();

		if ($needShip === null || $shipment->isSystem()
			|| ($needShip === false && !$shipment->isReserved()))
		{
			return new Result();
		}

		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{

			$setReservedQuantity = 0;
			if ($needShip === false)
			{
				/** @var BasketItem $basketItem */
				$basketItem = $shipmentItem->getBasketItem();

				if ($basketItem->isBundleParent() || !Configuration::isEnabledReservation())
				{
					continue;
				}

				$setReservedQuantity = $shipmentItem->getQuantity();
			}

			$shipmentItem->setFieldNoDemand('RESERVED_QUANTITY', $setReservedQuantity);

			if ($needShip === false)
				$shipment->updateReservedFlag();
		}

		return new Result();
	}

	/**
	 * @param Basket $basketCollection
	 * @param BasketItem $refreshItem
	 * @return array
	 * @throws NotSupportedException
	 */
	public static function getProductAvailableQuantity(Basket $basketCollection, BasketItem $refreshItem = null)
	{

		static $proxyProductAvailableQuantity = array();
		$result = array();
		$userId = null;

		if (($order = $basketCollection->getOrder()) !== null)
		{
			$userId = $order->getUserId();
		}

		if ($userId === null)
		{
			$userId = \CSaleUser::GetUserID($basketCollection->getFUserId());
		}

		$basketList = static::makeArrayFromBasketCollection($basketCollection, $refreshItem);

		$basketProviderMap = static::createProviderBasketMap($basketList);
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				if ($provider instanceof Provider)
				{
					throw new NotSupportedException('provider not supported');
				}
				elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
				{

					foreach ($providerBasketItemList as $providerBasketItem)
					{

						$proxyProductKey = $providerBasketItem['PRODUCT_ID']."|".$userId;
						if (!empty($proxyProductAvailableQuantity[$proxyProductKey]) && is_array($proxyProductAvailableQuantity[$proxyProductKey]))
						{
							$resultProductData = $proxyProductAvailableQuantity[$proxyProductKey];
						}
						else
						{
							$resultProductData = $resultProductData = $provider::getProductAvailableQuantity($providerBasketItem['PRODUCT_ID'], $userId);
							$proxyProductAvailableQuantity[$proxyProductKey] = $resultProductData;
						}


						$basketCode = $providerBasketItem['BASKET_ITEM']->getBasketCode();
						$result[$basketCode] = $resultProductData;
					}
				}
				else
				{
					foreach ($providerBasketItemList as $providerBasketItem)
					{
						$resultProductData = \CSaleBasket::ExecuteCallbackFunction(
							$providerBasketItem['CALLBACK_FUNC'],
							$providerBasketItem['MODULE'],
							$providerBasketItem['PRODUCT_ID']
						);

						$basketCode = $providerBasketItem['BASKET_ITEM']->getBasketCode();
						$result[$basketCode] = $resultProductData;
					}
				}


			}
		}

		return $result;
	}

	/**
	 * @param Basket $basketCollection
	 * @param array $select
	 * @param BasketItem $refreshItem
	 * @return array
	 * @throws NotSupportedException
	 */
	public static function getProductData(Basket $basketCollection, array $select = array(), BasketItem $refreshItem = null)
	{
		$result = array();

		$orderId = null;
		$userId = null;
		$siteId = null;
		$currency = null;

		if (($order = $basketCollection->getOrder()) !== null)
		{
			$userId = $order->getUserId();
			$siteId = $order->getSiteId();
			$currency = $order->getCurrency();
		}

		if ($userId === null)
		{
			static $usersIds = array();

			if(empty($usersIds[$basketCollection->getFUserId(true)]))
				$usersIds[$basketCollection->getFUserId(true)] = \CSaleUser::getUserID($basketCollection->getFUserId(true));

			$userId = $usersIds[$basketCollection->getFUserId(true)];
		}

		if  ($siteId === null)
			$siteId = $basketCollection->getSiteId();

		if ($siteId === null)
			return array();

		if ($currency === null)
		{
			$currency = Internals\SiteCurrencyTable::getSiteCurrency($siteId);
			if (!$currency)
				$currency = Currency\CurrencyManager::getBaseCurrency();
		}

		$basketList = static::makeArrayFromBasketCollection($basketCollection, $refreshItem);

		$basketProviderMap = static::createProviderBasketMap($basketList, array('QUANTITY', 'RENEWAL'));
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (!empty($basketProviderList))
		{
			$needPrice = in_array('PRICE', $select);
			$needBasePrice = in_array('BASE_PRICE', $select);
			$needCoupons = in_array('COUPONS', $select);
			$data = array(
				'USER_ID' => (int)$userId,
				'SITE_ID' => $siteId,
				'CURRENCY' => $currency,
				'CHECK_QUANTITY' => (in_array('QUANTITY', $select) ? 'Y' : 'N'),
				'CHECK_PRICE' => ($needPrice ? 'Y' : 'N'),
				'CHECK_COUPONS' => ($needCoupons ? 'Y' : 'N'),
				'RENEWAL' => (in_array('RENEWAL', $select) ? 'Y' : 'N')
			);

			if ($needBasePrice)
				$data['CHECK_DISCOUNT'] = 'N';

			$useOrderProduct = false;
			if ($needPrice)
				$useOrderProduct = true;
			if ($needCoupons)
				$useOrderProduct = false;
			unset($needCoupons, $needPrice);

			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				if ($provider instanceof Provider)
				{
					throw new NotSupportedException('provider not supported');
				}
				elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
				{
					foreach ($providerBasketItemList as $providerBasketItem)
					{
						$currentUseOrderProduct = $useOrderProduct;
						if (!isset($providerBasketItem['BASKET_ID']) || (int)$providerBasketItem['BASKET_ID'] <= 0)
							$currentUseOrderProduct = false;

						$providerFields = $data;

						if ($providerBasketItem['BASKET_ITEM']->isBundleChild())
						{
							$providerFields['CHECK_DISCOUNT'] = 'N';
						}
						$providerFields['PRODUCT_ID'] = $providerBasketItem['PRODUCT_ID'];
						$providerFields['QUANTITY'] = $providerBasketItem['QUANTITY'];

						$hasTrustData = false;

						$trustData = static::getTrustData($siteId, $providerBasketItem['MODULE'], $providerBasketItem['PRODUCT_ID']);

						if (static::isReadTrustData() === true
							&& !empty($trustData) && is_array($trustData))
						{
							$hasTrustData = true;
							$resultProductData = $trustData;

							foreach (static::getProductDataRequiredFields() as $requiredField)
							{
								if (!array_key_exists($requiredField, $resultProductData))
								{
									$hasTrustData = false;
									break;
								}
							}


							if ($hasTrustData && in_array('PRICE', $select))
							{
								foreach (static::getProductDataRequiredPriceFields() as $requiredField)
								{
									if (!array_key_exists($requiredField, $resultProductData))
									{
										$hasTrustData = false;
										break;
									}
								}
							}
						}


						if(!$hasTrustData)
						{
							$resultProductData = ($currentUseOrderProduct ? $provider::OrderProduct($providerFields) : $provider::GetProductData($providerFields));
						}

						$basketCode = $providerBasketItem['BASKET_ITEM']->getBasketCode();
						$result[$basketCode] = $resultProductData;
					}
				}
				else
				{
					foreach ($providerBasketItemList as $providerBasketItem)
					{
						$resultProductData = \CSaleBasket::executeCallbackFunction(
							$providerBasketItem['CALLBACK_FUNC'],
							$providerBasketItem['MODULE'],
							$providerBasketItem['PRODUCT_ID'],
							$providerBasketItem['QUANTITY']
						);

						$basketCode = $providerBasketItem['BASKET_ITEM']->getBasketCode();
						$result[$basketCode] = $resultProductData;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return array
	 * @throws NotSupportedException
	 */
	public static function tryShipment(Shipment $shipment)
	{
		$result = new Result();
		$needShip = $shipment->needShip();
		if ($needShip === null)
			return $result;

		$resultList = array();
		$storeData = array();

		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		$basketList = static::getBasketFromShipmentItemCollection($shipmentItemCollection);

		$bundleIndexList = static::getBundleIndexFromShipmentItemCollection($shipmentItemCollection);

		$basketCountList = static::getBasketCountFromShipmentItemCollection($shipmentItemCollection);

		$basketProviderMap = static::createProviderBasketMap($basketList, array('RESERVED', 'SITE_ID'));
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (Configuration::useStoreControl())
		{
			/** @var Result $r */
			$r = static::getStoreDataFromShipmentItemCollection($shipmentItemCollection);
			if ($r->isSuccess())
			{
				$storeData = $r->getData();
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

		}

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				if ($provider instanceof Provider)
				{
					throw new NotSupportedException('provider not supported');
				}
				elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
				{
					foreach ($providerBasketItemList as $providerBasketItem)
					{
						if ($providerBasketItem['BASKET_ITEM']->isBundleParent())
						{
							continue;
						}

						$resultProduct = new Result();


						$quantity = 0;
						$basketStoreData = array();

						if (Configuration::useStoreControl())
						{
							$quantity = $basketCountList[$providerBasketItem['BASKET_CODE']];

							if (!empty($storeData) && is_array($storeData)
							&& isset($storeData[$providerBasketItem['BASKET_CODE']]))
							{
								$basketStoreData = $storeData[$providerBasketItem['BASKET_CODE']];
							}

							if (!empty($basketStoreData))
							{
								$allBarcodeQuantity = 0;
								foreach($basketStoreData as $basketShipmentItemStore)
								{
									$allBarcodeQuantity += $basketShipmentItemStore['QUANTITY'];
								}

								if ($quantity > $allBarcodeQuantity)
								{
									$resultProduct->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_SHIPMENT_SHIPPED_LESS_QUANTITY', array(
										'#PRODUCT_NAME#' => $providerBasketItem['BASKET_ITEM']->getField('NAME')
									)), 'SALE_PROVIDER_SHIPMENT_SHIPPED_LESS_QUANTITY'));
								}
								elseif ($quantity < $allBarcodeQuantity)
								{
									$resultProduct->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_SHIPMENT_SHIPPED_MORE_QUANTITY', array(
										'#PRODUCT_NAME#' => $providerBasketItem['BASKET_ITEM']->getField('NAME')
									)), 'SALE_PROVIDER_SHIPMENT_SHIPPED_MORE_QUANTITY'));
								}
							}

						}

						if ($resultProduct->isSuccess())
						{

							if ($needShip === true)
							{
								if (method_exists($provider, 'tryShipmentProduct'))
								{
									/** @var Result $resultProductData */
									$resultProduct = $provider::tryShipmentProduct($providerBasketItem['BASKET_ITEM'], $providerBasketItem['RESERVED'], $basketStoreData, $quantity);
								}
							}
							else
							{
								if (method_exists($provider, 'tryUnshipmentProduct'))
								{
									/** @var Result $resultProductData */
									$resultProduct = $provider::tryUnshipmentProduct($providerBasketItem['PRODUCT_ID']);
								}
							}
						}

						$resultList[$providerBasketItem['BASKET_CODE']] = $resultProduct;

					}
				}
			}
		}

		if (!empty($resultList)
			&& !empty($bundleIndexList) && is_array($bundleIndexList))
		{

			foreach ($bundleIndexList as $bundleParentBasketCode => $bundleChildList)
			{
//				$tryShipmentBundle = false;
				foreach($bundleChildList as $bundleChildBasketCode)
				{
					if (!isset($resultList[$bundleChildBasketCode]))
					{
						if (!isset($resultList[$bundleParentBasketCode]))
						{
							$resultList[$bundleParentBasketCode] = new Result();
						}

						$resultList[$bundleParentBasketCode]->addError(new ResultError('Bundle child item not found'));
					}

				}
			}

		}

		if (!empty($resultList))
		{
			$result->setData($resultList);
		}

		return $result;
	}

	/**
	 * @param ShipmentItemCollection $shipmentItemCollection
	 * @return array
	 */
	protected static function getBundleIndexFromShipmentItemCollection(ShipmentItemCollection $shipmentItemCollection)
	{
		$bundleIndexList = array();
		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			/** @var BasketItem $basketItem */
			$basketItem = $shipmentItem->getBasketItem();

			if ($basketItem->isBundleChild())
			{
				/** @var BasketItem $parentBasketItem */
				$parentBasketItem = $basketItem->getParentBasketItem();
				$parentBasketCode = $parentBasketItem->getBasketCode();

				if (!array_key_exists($parentBasketCode, $bundleIndexList))
				{
					$bundleIndexList[$parentBasketCode] = array();
				}

				$bundleIndexList[$parentBasketCode][] = $basketItem->getBasketCode();
			}
		}

		return $bundleIndexList;
	}
	/**
	 * @param \Bitrix\Sale\ShipmentItemCollection $shipmentItemCollection
	 * @return array
	 */
	protected static function getBasketFromShipmentItemCollection(ShipmentItemCollection $shipmentItemCollection)
	{
		/** @var Shipment $shipment */
		$shipment = $shipmentItemCollection->getShipment();

		$basketList = array();
		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			/** @var BasketItem $basketItem */
			$basketItem = $shipmentItem->getBasketItem();

			if ($basketItem->isBundleParent()
				|| (!$basketItem->isBundleParent() && !$basketItem->isBundleChild()))
			{
				$basketList[$basketItem->getBasketCode()] = array(
					'BASKET_ITEM' => $basketItem,
					'RESERVED' => ( ($shipmentItem->getQuantity() - $shipmentItem->getReservedQuantity()) == 0 || $shipment->getField('RESERVED') == "Y" ? "Y" : "N")
				);
			}

			if($basketItem->isBundleParent())
			{
				/** @var ShipmentItem $bundleShipmentItem */
				foreach ($shipmentItemCollection as $bundleShipmentItem)
				{
					/** @var BasketItem $bundleBasketItem */
					$bundleBasketItem = $bundleShipmentItem->getBasketItem();

					if($bundleBasketItem->isBundleChild())
					{
						$bundleParentBasketItem = $bundleBasketItem->getParentBasketItem();
						if ($bundleParentBasketItem->getBasketCode() == $basketItem->getBasketCode())
						{
							$basketList[$bundleBasketItem->getBasketCode()] = array(
								'BASKET_ITEM' => $bundleBasketItem,
								'RESERVED' => ( ($shipmentItem->getQuantity() - $shipmentItem->getReservedQuantity()) == 0 || $shipment->getField('RESERVED') == "Y" ? "Y" : "N")
							);
						}
					}
				}
			}


		}

		return $basketList;
	}

	/**
	 * @param \Bitrix\Sale\ShipmentItemCollection $shipmentItemCollection
	 * @return array
	 */
	protected static function getBasketCountFromShipmentItemCollection(ShipmentItemCollection $shipmentItemCollection)
	{
		$basketCountList = array();
		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			/** @var BasketItem $basketItem */
			$basketItem = $shipmentItem->getBasketItem();

			if ($basketItem->isBundleParent()
				|| (!$basketItem->isBundleParent() && !$basketItem->isBundleChild()))
			{
				$basketCountList[$basketItem->getBasketCode()] = $shipmentItem->getQuantity();
			}


			if($basketItem->isBundleParent())
			{
				/** @var ShipmentItem $bundleShipmentItem */
				foreach ($shipmentItemCollection as $bundleShipmentItem)
				{
					/** @var BasketItem $bundleBasketItem */
					$bundleBasketItem = $bundleShipmentItem->getBasketItem();

					if($bundleBasketItem->isBundleChild())
					{
						$bundleParentBasketItem = $bundleBasketItem->getParentBasketItem();
						if ($bundleParentBasketItem->getBasketCode() == $basketItem->getBasketCode())
						{
							$basketCountList[$bundleBasketItem->getBasketCode()] = $bundleShipmentItem->getQuantity();
						}
					}
				}
			}

		}

		return $basketCountList;
	}

	/**
	 * @param ShipmentItemCollection $shipmentItemCollection
	 * @return array
	 * @throws SystemException
	 */
	protected static function getStoreDataFromShipmentItemCollection(ShipmentItemCollection $shipmentItemCollection)
	{
		$result = new Result();
		$fields = array();

		$storeCountList = array();
		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			/** @var BasketItem $basketItem */
			$basketItem = $shipmentItem->getBasketItem();
			if ($basketItem->isBundleParent())
			{
				continue;
			}

			/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
			if (($shipmentItemStoreCollection  = $shipmentItem->getShipmentItemStoreCollection()) && count($shipmentItemStoreCollection) > 0)
			{
				/** @var ShipmentItemStore $shipmentItemStore */
				foreach ($shipmentItemStoreCollection as $shipmentItemStore)
				{
					$basketItem = $shipmentItemStore->getBasketItem();
					$basketCode = $basketItem->getBasketCode();

					$storeId = $shipmentItemStore->getStoreId();

					// store

					if (!isset($fields[$basketCode]) || !isset($fields[$basketCode][$storeId]))
					{
						$fields[$basketCode][$storeId] = array(
							'QUANTITY' => 0,
							'STORE_ID' => $storeId,
							'BARCODE' => array()
						);
					}

					$fields[$basketCode][$storeId]['QUANTITY'] += $basketItem->isBarcodeMulti()? 1 : $shipmentItemStore->getQuantity();

					if (!isset($fields[$basketCode][$storeId]['BARCODE']))
					{
						$fields[$basketCode][$storeId]['BARCODE'] = array();
					}

					$fields[$basketCode][$storeId]['BARCODE'][$shipmentItemStore->getId()] = $shipmentItemStore->getBarCode();

				}
			}

		}

		if (!empty($fields))
		{
			$result->setData($fields);
		}

		return $result;
	}

	/**
	 * @param Basket $basketCollection
	 * @param BasketItem $refreshItem
	 * @return array
	 */
	protected static function makeArrayFromBasketCollection(Basket $basketCollection, BasketItem $refreshItem = null)
	{
		$basketList = array();
		/** @var BasketItem $basketItem */
		foreach ($basketCollection as $basketItem)
		{
			if ($refreshItem !== null)
			{

				if ($basketItem->getBasketCode() != $refreshItem->getBasketCode() && $basketItem->isBundleParent())
				{
					if ($bundleCollection = $basketItem->getBundleCollection())
					{
						$foundItem = false;
						/** @var BasketItem $bundleBasketItem */
						foreach ($bundleCollection as $bundleBasketItem)
						{
							if ($bundleBasketItem->getBasketCode() == $refreshItem->getBasketCode())
							{
								$foundItem = true;
								break;
							}
						}

						if (!$foundItem)
							continue;

						$basketList[] = $bundleBasketItem;
						continue;
					}
				}
				elseif ($basketItem->getBasketCode() != $refreshItem->getBasketCode())
				{
					continue;
				}

				$basketList[] = $basketItem;

				continue;
			}

			$basketList[] = $basketItem;

			if ($basketItem->isBundleParent())
			{
				if ($bundleCollection = $basketItem->getBundleCollection())
				{
					foreach ($bundleCollection as $bundleBasketItem)
					{
						$basketList[] = $bundleBasketItem;
					}
				}
			}
		}

		return $basketList;
	}

	public static function tryReserveShipment(Shipment $shipment)
	{
		$result = new Result();

		/** @var ShipmentItemCollection $shipmentCollection */
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			try
			{
				/** @var Result $r */
				$r = static::tryReserveShipmentItem($shipmentItem);
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}
			}
			catch(\Exception $e)
			{
				/** @var Shipment $shipment */
				if (!$shipment = $shipmentItemCollection->getShipment())
				{
					throw new ObjectNotFoundException('Entity "Shipment" not found');
				}
				else
				{
					throw new $e;
				}

			}

		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 */
	public static function tryUnreserveShipment(Shipment $shipment)
	{
		$result = new Result();
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentItem)
		{
			/** @var Result $r */
			$r = static::tryUnreserveShipmentItem($shipmentItem);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @param ShipmentItem $shipmentItem
	 * @return Result
	 * @throws NotSupportedException
	 */
	public static function tryReserveShipmentItem(ShipmentItem $shipmentItem)
	{
		$result = new Result();

		if (floatval($shipmentItem->getQuantity()) == floatval($shipmentItem->getReservedQuantity()))
			return $result;

		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = $shipmentItem->getCollection();
		$shipment = $shipmentItemCollection->getShipment();
		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();
		$order = $shipmentCollection->getOrder();

		/** @var BasketItem $basketItem */
		$basketItem = $shipmentItem->getBasketItem();

		if ($basketItem->isBundleParent())
		{
			return $result;
		}

		$needQuantity = ($shipmentItem->getQuantity() - $shipmentItem->getReservedQuantity());
		$canReserve = false;

		/** @var Result $r */
		$r = static::tryReserveBasketItem($shipmentItem->getBasketItem(), $needQuantity);
		if ($r->isSuccess())
		{
			$availableQuantityData = $r->getData();
			if (array_key_exists('AVAILABLE_QUANTITY', $availableQuantityData))
			{
				$availableQuantity = $availableQuantityData['AVAILABLE_QUANTITY'];
			}
			else
			{
				$result->addError( new ResultError(Loc::getMessage('PROVIDER_RESERVE_SHIPMENT_ITEM_WRONG_AVAILABLE_QUANTITY'), 'PROVIDER_RESERVE_SHIPMENT_ITEM_WRONG_AVAILABLE_QUANTITY') );
				return $result;
			}

			if (array_key_exists('HAS_PROVIDER', $availableQuantityData))
			{
				$canReserve = $availableQuantityData['HAS_PROVIDER'];
			}

			if ($canReserve && array_key_exists('QUANTITY_TRACE', $availableQuantityData))
			{
				$canReserve = $availableQuantityData['QUANTITY_TRACE'];
			}
		}
		else
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		if ($canReserve)
		{
			if (($needQuantity > 0) && ($needQuantity > $availableQuantity)
				/*|| ($needReserved < 0) && ($availableQuantity < $needReserved) */)
			{
	//			throw new SystemException("availableQuantity");
				$result->addError(new ResultError(Loc::getMessage("SALE_PROVIDER_RESERVE_SHIPMENT_ITEM_QUANTITY_NOT_ENOUGH"), "SALE_PROVIDER_RESERVE_SHIPMENT_ITEM_QUANTITY_NOT_ENOUGH"));
				return $result;
			}

			// is not completely correct, but will be processed in real reservations while saving
			if (($availableQuantity < 0) && ($shipmentItem->getReservedQuantity() + $availableQuantity < 0))
			{
				$availableQuantity = -1 * $shipmentItem->getReservedQuantity();
			}

			if (Configuration::getProductReservationCondition() != Configuration::RESERVE_ON_SHIP)
			{
				static::addReservationPoolItem($order->getInternalId(), $shipmentItem->getBasketItem(), ($availableQuantity >= $needQuantity ? $needQuantity : $availableQuantity));

				if (Configuration::isEnabledReservation())
				{
					$shipmentItem->setField('RESERVED_QUANTITY', $shipmentItem->getReservedQuantity() + ($availableQuantity >= $needQuantity ? $needQuantity : $availableQuantity));
				}
			}
		}

		$result->addData(array(
							'CAN_RESERVE' => $canReserve
						 ));

		return $result;
	}

	/**
	 * @param ShipmentItem $shipmentItem
	 * @return float|int|null
	 * @throws NotSupportedException
	 * @throws SystemException
	 */
	public static function tryUnreserveShipmentItem(ShipmentItem $shipmentItem)
	{
		if (Configuration::isEnabledReservation() && $shipmentItem->getReservedQuantity() == 0)
			return new Result();

		$result = new Result();

		/** @var BasketItem $basketItem */
		$basketItem = $shipmentItem->getBasketItem();

		if ($basketItem->isBundleParent())
		{
			return $result;
		}

		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = $shipmentItem->getCollection();
		$shipment = $shipmentItemCollection->getShipment();

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();
		$order = $shipmentCollection->getOrder();


		if (Configuration::isEnabledReservation())
		{
			$quantity = $shipmentItem->getReservedQuantity();
		}
		else
		{
			$quantity = $shipmentItem->getQuantity();
		}

		$canReserve = false;

		/** @var Result $r */
		$r = static::tryReserveBasketItem($shipmentItem->getBasketItem(), -1 * $quantity);
		if ($r->isSuccess())
		{
			$availableQuantityData = $r->getData();
			if (array_key_exists('AVAILABLE_QUANTITY', $availableQuantityData))
			{
				$availableQuantity = $availableQuantityData['AVAILABLE_QUANTITY'];
			}
			else
			{
				$result->addError( new ResultError(Loc::getMessage('PROVIDER_UNRESERVE_SHIPMENT_ITEM_WRONG_AVAILABLE_QUANTITY'), 'PROVIDER_UNRESERVE_SHIPMENT_ITEM_WRONG_AVAILABLE_QUANTITY') );
				return $result;
			}

			if (array_key_exists('HAS_PROVIDER', $availableQuantityData))
			{
				$canReserve = $availableQuantityData['HAS_PROVIDER'];
			}

			if ($canReserve && array_key_exists('QUANTITY_TRACE', $availableQuantityData))
			{
				$canReserve = $availableQuantityData['QUANTITY_TRACE'];
			}
		}
		else
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		if ($canReserve)
		{
			if ($availableQuantity > 0)
			{
				//			throw new SystemException("availableQuantity");
				$result->addError(new ResultError(Loc::getMessage("SALE_PROVIDER_RESERVE_SHIPMENT_ITEM_QUANTITY_NOT_ENOUGH"), "SALE_PROVIDER_RESERVE_SHIPMENT_ITEM_QUANTITY_NOT_ENOUGH"));
				return $result;
			}

			static::addReservationPoolItem($order->getInternalId(), $shipmentItem->getBasketItem(), $availableQuantity);

			if (Configuration::isEnabledReservation())
				$shipmentItem->setField('RESERVED_QUANTITY', $shipmentItem->getReservedQuantity() + $availableQuantity);
		}

		$result->addData(array(
							 'CAN_RESERVE' => $canReserve
						 ));

		return $result;
	}

	/**
	 * @param BasketItem $basketItem
	 * @param $quantity
	 * @return float|int|null
	 * @throws NotSupportedException
	 */
	protected static function tryReserveBasketItem(BasketItem $basketItem, $quantity)
	{
		$result = new Result();

		$provider = $basketItem->getProvider();

		if (!$basketItem->isBundleChild())
		{
			/** @var Basket $basket */
			$basket = $basketItem->getCollection();
		}
		else
		{
			/** @var BasketItem $parentBasketItem */
			$parentBasketItem = $basketItem->getParentBasketItem();

			/** @var Basket $basket */
			$basket = $parentBasketItem->getCollection();
		}

		$order = $basket->getOrder();
		$hasProvider = false;
		$quantityTrace = null;

		$poolQuantity = static::getReservationPoolItem($order->getInternalId(), $basketItem);
		$tryQuantity = $quantity + $poolQuantity;

		if ($provider instanceof Provider)
		{
			throw new NotSupportedException('provider not supported');
		}
		elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
		{
			$hasProvider = true;
			$r = static::checkAvailableProductQuantity($basketItem, $tryQuantity);
			if ($r->isSuccess())
			{
				$availableQuantityData = $r->getData();
				if (array_key_exists('AVAILABLE_QUANTITY', $availableQuantityData))
				{
					$availableQuantity = $availableQuantityData['AVAILABLE_QUANTITY'];
				}
				else
				{
					$result->addError( new ResultError(Loc::getMessage('PROVIDER_RESERVE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY'), 'PROVIDER_RESERVE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY') );
					return $result;
				}

				if (array_key_exists('QUANTITY_TRACE', $availableQuantityData))
				{
					$quantityTrace = $availableQuantityData['QUANTITY_TRACE'];
				}
				//
			}
			else
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

			$availableQuantity -= $poolQuantity;
		}
		else
		{
			$availableQuantity = $quantity;
		}

		$fields = array(
			'AVAILABLE_QUANTITY' => $availableQuantity,
			'HAS_PROVIDER' => $hasProvider,
		);

		if ($quantityTrace !== null)
		{
			$fields['QUANTITY_TRACE'] = $quantityTrace;
		}

		$result->setData($fields);
		return $result;
	}


	/**
	 * @param BasketItem $basketItem
	 * @param $quantity
	 * @return Result
	 * @throws NotSupportedException
	 * @throws ObjectNotFoundException
	 */
	private static function reserveBasketItem(BasketItem $basketItem, $quantity)
	{
		global $APPLICATION;

		$result = new Result();
		$fields = array();

		$provider = $basketItem->getProvider();
		$hasProvider = false;

		if ($provider instanceof Provider)
		{
			throw new NotSupportedException('provider not supported');
		}
		elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
		{
			$hasProvider = true;
			$data = array("PRODUCT_ID" => $basketItem->getProductId());

			if ($quantity > 0)
			{
				$data["UNDO_RESERVATION"] = "N";
				$data["QUANTITY_ADD"] = $quantity;
			}
			else
			{
				$data["UNDO_RESERVATION"] = "Y";
				$data["QUANTITY_ADD"] = abs($quantity);
			}

			$APPLICATION->ResetException();
			if (($resultReserveData = $provider::ReserveProduct($data)))
			{

				if ($resultReserveData['RESULT'])
				{
					$fields['QUANTITY'] = $resultReserveData['QUANTITY_RESERVED'];

					if ($quantity < 0)
					{
						$fields['QUANTITY'] = $quantity;
					}

					$fields['HAS_PROVIDER'] = $hasProvider;
					$result->setData($fields);

					if ($ex = $APPLICATION->GetException())
					{
						/** @var Basket $basket */
						if (!$basket = $basketItem->getCollection())
						{
							throw new ObjectNotFoundException('Entity "Basket" not found');
						}

						if ($order = $basket->getOrder())
						{
							/** @var ShipmentCollection $shipmentCollection */
							if (!$shipmentCollection = $order->getShipmentCollection())
							{
								throw new ObjectNotFoundException('Entity "ShipmentCollection" not found');
							}

							/** @var Shipment $shipment */
							foreach ($shipmentCollection as $shipment)
							{
								/** @var ShipmentItemCollection $shipmentItemCollection */
								if (!$shipmentItemCollection = $shipment->getShipmentItemCollection())
								{
									throw new ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
								}

								if($shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode()))
								{
									$shipment->setField('MARKED', 'Y');
									$oldErrorText = $shipment->getField('REASON_MARKED');
									$shipment->setField('REASON_MARKED', $oldErrorText.(strval($oldErrorText) != '' ? "\n" : ""). $ex->GetString());

								}
							}
						}
					}
					return $result;
				}
				else
				{
					if ($ex = $APPLICATION->GetException())
					{
						$result->addError(new ResultError($ex->GetString())) ;
					}
					else
					{
						$result->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR')) ;
					}
				}

			}
			else
			{
				$result->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR')) ;
			}

		}
		else
		{
			$fields['QUANTITY'] = $quantity;
			$result->setData($fields);
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return array
	 * @throws ArgumentException
	 * @throws NotSupportedException
	 */
	private static function reserveShipment(Shipment $shipment)
	{
		if ($shipment->isReserved())
			new SystemException("shipment already reserved");

		$result = array();

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();

		/** @var OrderBase $order */
		$order = $shipmentCollection->getOrder();

		/** @var ShipmentItemCollection $shipmentCollection */
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentIndex => $shipmentItem)
		{
			$poolQuantity = static::getReservationPoolItem($order->getInternalId(), $shipmentItem->getBasketItem());
			if ($poolQuantity === null
				|| floatval($shipmentItem->getQuantity()) == floatval($shipmentItem->getReservedQuantity()))
			{
				continue;
			}

			$needReserved = floatval($shipmentItem->getQuantity()) - floatval($shipmentItem->getReservedQuantity());

			$resultQuantity = static::reserveShipmentItem($shipmentItem, ($poolQuantity - $needReserved) > 0? $needReserved : $poolQuantity );

			$poolQuantity -= $resultQuantity;
			static::setReservationPoolItem($order->getInternalId(), $shipmentItem->getBasketItem(), $poolQuantity);

			$result[$shipmentItem->getBasketCode()] = $resultQuantity;
		}

		return $result;
	}

	/**
	 * @param ShipmentItem $shipmentItem
	 * @param $quantity
	 * @return array|bool
	 * @throws NotSupportedException
	 */
	private static function reserveShipmentItem(ShipmentItem $shipmentItem, $quantity)
	{
		global $APPLICATION;
		$result = new Result();
		$fields = array();

		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = $shipmentItem->getCollection();

		/** @var Shipment $shipment */
		$shipment = $shipmentItemCollection->getShipment();

		/** @var BasketItem $basketItem */
		$basketItem = $shipmentItem->getBasketItem();
		$provider = $basketItem->getProvider();


		if ($provider instanceof Provider)
		{
			throw new NotSupportedException('provider not supported');
		}
		elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
		{

			$data = array(
				"PRODUCT_ID" => $basketItem->getProductId(),
				"UNDO_RESERVATION" => "N",
				"QUANTITY_ADD"   => $quantity,
				"ORDER_DEDUCTED" => $shipment->isShipped()? "Y" : "N",
			);

			$APPLICATION->ResetException();
			if (($resultReserveData = $provider::ReserveProduct($data)))
			{
				if ($resultReserveData['RESULT'])
				{
					$fields['QUANTITY'] = $resultReserveData['QUANTITY_RESERVED'];

					if (isset($resultReserveData['QUANTITY_NOT_RESERVED']) && floatval($resultReserveData['QUANTITY_NOT_RESERVED']) > 0)
					{
						$fields['QUANTITY'] = $shipmentItem->getReservedQuantity() + ($shipmentItem->getQuantity() - $shipmentItem->getReservedQuantity()) -  $resultReserveData['QUANTITY_NOT_RESERVED'];
					}

					$result->setData($fields);
					return $result;
				}
				else
				{
					if ($ex = $APPLICATION->GetException())
					{
						if ($ex->GetID() != "ALREADY_FLAG")
							$result->addError(new ResultError($ex->GetString())) ;
					}
					else
					{
						$result->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR')) ;
					}
				}

			}

		}

		if (!empty($fields))
		{
			$result->setData($fields);
		}
		return $result;
	}

	/**
	 * @param ShipmentItem $shipmentItem
	 * @param $quantity
	 * @return array|bool
	 * @throws NotSupportedException
	 */
	private static function unreserveShipmentItem(ShipmentItem $shipmentItem, $quantity)
	{
		global $APPLICATION;

		$result = new Result();
		$fields = array();

		/** @var ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = $shipmentItem->getCollection();

		/** @var Shipment $shipment */
		$shipment = $shipmentItemCollection->getShipment();

		/** @var BasketItem $basketItem */
		$basketItem = $shipmentItem->getBasketItem();
		$provider = $basketItem->getProvider();

		if ($provider instanceof Provider)
		{
			throw new NotSupportedException('provider not supported');
		}
		elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
		{

			$data = array(
				"PRODUCT_ID" => $basketItem->getProductId(),
				"UNDO_RESERVATION" => "Y",
				"QUANTITY_ADD"   => $quantity,
				"ORDER_DEDUCTED" => $shipment->isShipped()? "Y" : "N",
			);
			$APPLICATION->ResetException();
			if (($resultReserveData = $provider::ReserveProduct($data)))
			{
				if ($resultReserveData['RESULT'])
				{
					$fields['QUANTITY'] = $resultReserveData['QUANTITY_RESERVED'];

					if (isset($resultReserveData['QUANTITY_NOT_RESERVED']) && floatval($resultReserveData['QUANTITY_NOT_RESERVED']) > 0)
					{
						$fields['QUANTITY'] = $shipmentItem->getReservedQuantity() + ($shipmentItem->getQuantity() - $shipmentItem->getReservedQuantity()) -  $resultReserveData['QUANTITY_NOT_RESERVED'];
					}

					return $result;
				}
				else
				{
					if ($ex = $APPLICATION->GetException())
					{
						if ($ex->GetID() != "ALREADY_FLAG")
							$result->addError(new ResultError($ex->GetString())) ;
					}
					else
					{
						$result->addError(new ResultError(Loc::getMessage('SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR'), 'SALE_PROVIDER_RESERVE_BASKET_ITEM_ERROR')) ;
					}
				}

			}

		}

		if (!empty($fields))
		{
			$result->setData($fields);
		}
		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return array
	 * @throws NotSupportedException
	 * @throws ObjectNotFoundException
	 */
	public static function syncReservedQuantity(Shipment $shipment)
	{
		$result = array();

		/** @var ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();

		$systemShipment = $shipmentCollection->getSystemShipment();

		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		/** @var ShipmentItem $shipmentItem */
		foreach ($shipmentItemCollection as $shipmentIndex => $shipmentItem)
		{
			$basketCode = $shipmentItem->getBasketCode();

			if (!array_key_exists($basketCode, static::$poolProductQuantity))
			{
				continue;
			}

			$reserveQuantity = static::$poolProductQuantity[$basketCode];


			if ($reserveQuantity >= 0)
			{

				$basketCode = $shipmentItem->getBasketCode();

				if (floatval($shipmentItem->getQuantity()) == floatval($shipmentItem->getReservedQuantity()))
				{
					continue;
				}

				$needReserved = floatval($shipmentItem->getQuantity()) - floatval($shipmentItem->getReservedQuantity());

				$resultQuantity = static::reserveShipmentItem($shipmentItem, (($reserveQuantity - $needReserved) > 0 || $reserveQuantity == 0)? $needReserved : $reserveQuantity );

				if (!$shipment->isSystem())
				{
					$shipmentReservedQuantity = floatval($shipmentItem->getReservedQuantity()) + $resultQuantity;
					if (floatval($shipmentReservedQuantity) != floatval($shipmentItem->getQuantity()))
					{
						/** @var ShipmentItemCollection $systemShipmentItemCollection */
						$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();

						/** @var ShipmentItem $systemShipmentItem */
						if ($systemShipmentItem = $systemShipmentItemCollection->getItemByBasketCode($shipmentItem->getBasketCode()))
						{
							$needMoreReserved = $shipmentItem->getQuantity() - $shipmentReservedQuantity;
							$quantityNeedReserve = $systemShipmentItem->getReservedQuantity() - $needMoreReserved;
							$quantityNeedReserve = ($quantityNeedReserve <= 0) ? $systemShipmentItem->getReservedQuantity() : $needMoreReserved ;

							if (static::unreserveShipmentItem($systemShipmentItem, $quantityNeedReserve))
							{
								$resultNeedQuantity = static::reserveShipmentItem($shipmentItem, $quantityNeedReserve);

								$resultQuantity = $resultQuantity + $resultNeedQuantity;
							}
						}
					}
				}
			}
			else
			{

				$resultQuantity = static::unreserveShipmentItem($shipmentItem, $shipmentItem->getReservedQuantity());

			}

			static::$poolProductQuantity[$basketCode] -= $resultQuantity;
			$result[$shipmentItem->getBasketCode()] = $resultQuantity;

			if (static::$poolProductQuantity[$basketCode] == 0)
			{
				unset(static::$poolProductQuantity[$basketCode]);
			}

		}

		return $result;
	}
	/**
	 * reduce in the quantity of product if the reservation is disabled
	 * @param ShipmentCollection $shipmentCollection
	 * @param array $shipmentReserveList
	 */
	public static function reduceProductQuantity(ShipmentCollection $shipmentCollection, array $shipmentReserveList = array())
	{
		$order = $shipmentCollection->getOrder();

		$options = array(
			'ORDER_DEDUCTED' => $order->isShipped()
		);

		$shipmentReserveListKeys = array_keys($shipmentReserveList);

		foreach ($shipmentCollection as $shipmentKey => $shipment)
		{
			if (!in_array($shipment->getId(), $shipmentReserveListKeys))
			{
				unset($shipmentCollection[$shipmentKey]);
			}
		}


		foreach ($shipmentCollection as $shipment)
		{
			$basketProviderList = static::getProviderBasketFromShipment($shipment);

			$productList = static::getProductListFromBasketProviderList($basketProviderList);

			if (!empty($basketProviderList))
			{
				foreach ($basketProviderList as $provider => $providerBasketItemList)
				{
					$shipmentReserveListData = array();
					if (!empty($shipmentReserveList)
						&& !empty($shipmentReserveList[$shipment->getId()]) && is_array($shipmentReserveList[$shipment->getId()]))
					{
						$shipmentReserveListData = $shipmentReserveList[$shipment->getId()];
					}

					$result = $provider::reduceProductQuantity($providerBasketItemList, $productList, $shipmentReserveListData, $options);
				}
			}

		}
	}

	/**
	 * increase in the quantity of product if the reservation is disabled
	 * @param ShipmentCollection $shipmentCollection
	 * @param array $shipmentReserveList
	 */
	public static function increaseProductQuantity(ShipmentCollection $shipmentCollection, array $shipmentReserveList = array())
	{
		$order = $shipmentCollection->getOrder();

		$options = array(
			'ORDER_DEDUCTED' => $order->isShipped()
		);

		$shipmentReserveListKeys = array_keys($shipmentReserveList);

		foreach ($shipmentCollection as $shipmentKey => $shipment)
		{
			if (!in_array($shipment->getId(), $shipmentReserveListKeys))
			{
				unset($shipmentCollection[$shipmentKey]);
			}
		}


		foreach ($shipmentCollection as $shipment)
		{
			$basketProviderList = static::getProviderBasketFromShipment($shipment);

			$productList = static::getProductListFromBasketProviderList($basketProviderList);

			if (!empty($basketProviderList))
			{
				foreach ($basketProviderList as $provider => $providerBasketItemList)
				{
					$shipmentReserveListData = array();
					if (!empty($shipmentReserveList)
						&& !empty($shipmentReserveList[$shipment->getId()]) && is_array($shipmentReserveList[$shipment->getId()]))
					{
						$shipmentReserveListData = $shipmentReserveList[$shipment->getId()];
					}

					$result = $provider::increaseProductQuantity($providerBasketItemList, $productList, $shipmentReserveListData, $options);
				}
			}

		}
	}


	/**
	 * @param ShipmentItemStore $shipmentItemStore
	 */
	public static function getStoresCount(ShipmentItemStore $shipmentItemStore)
	{
		$basketItem = $shipmentItemStore->getBasketItem();
		$siteId = $basketItem->getField('LID');

//		$result = $provider::getStoresIdBySiteId($siteId);
	}

	/**
	 * @param \Bitrix\Sale\Basket $basketCollection
	 * @param array $productList
	 * @param array $options
	 */
	public static function getProductStores(Basket $basketCollection, array $productList = array(), array $options = array())
	{
		$siteId = null;

		if (!$basketCollection->getOrderId())
		{
			if (($order = $basketCollection->getOrder()) !== null)
			{
				$siteId = $order->getSiteId();
			}
		}

		if ($siteId === null)
		{
			$siteId = $basketCollection->getSiteId();
		}

		$basketList = static::makeArrayFromBasketCollection($basketCollection);
		$basketProviderMap = static::createProviderBasketMap($basketList, array('STORE'));
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				$result = $provider::getProductStores($siteId,  $providerBasketItemList, $productList, $options);
			}
		}
	}

	/**
	 * @param BasketItem $basketItem
	 * @param array $params
	 * @return null
	 * @throws NotSupportedException
	 */

	public static function checkProductBarcode(BasketItem $basketItem, array $params = array())
	{
		$provider = $basketItem->getProvider();
		$params = array(
			'BARCODE' => $params['BARCODE'],
			'STORE_ID' => $params['STORE_ID'],
			'PRODUCT_ID' => $basketItem->getProductId()
		);
		$result = false;

		if ($provider instanceof Provider)
		{
			throw new NotSupportedException('provider not supported');
		}
		elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
		{
			$result = $provider::checkProductBarcode($params);
		}
		return $result;
	}

	/**
	 * @param \Bitrix\Sale\Basket $basketCollection
	 * @param array $productList
	 */
	public static function viewProduct(Basket $basketCollection, array $productList = array())
	{
		$orderId = null;
		$siteId = null;

		if (!($orderId = $basketCollection->getOrderId()))
		{
			if (($order = $basketCollection->getOrder()) !== null)
			{
				$userId = $order->getUserId();
				$siteId = $order->getSiteId();
			}
		}

		if ($userId === null)
		{
			$userId = \CSaleUser::GetUserID($basketCollection->getFUserId());
			$siteId = $basketCollection->getSiteId();
		}

		$basketList = static::makeArrayFromBasketCollection($basketCollection);
		$basketProviderMap = static::createProviderBasketMap($basketCollection);
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				$result = $provider::viewProduct($siteId, $userId, $providerBasketItemList, $productList);
			}
		}
	}

	/**
	 * @param Basket $basketCollection
	 * @param array $productList
	 */
	public static function recurringOrderProduct(Basket $basketCollection, array $productList = array())
	{
		$userId = null;

		if (!($orderId = $basketCollection->getOrderId()))
		{
			if (($order = $basketCollection->getOrder()) !== null)
			{
				$userId = $order->getUserId();
			}
		}

		if ($userId === null)
		{
			$userId = \CSaleUser::GetUserID($basketCollection->getFUserId());
		}

		$basketList = static::makeArrayFromBasketCollection($basketCollection);
		$basketProviderMap = static::createProviderBasketMap($basketList);
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				$result = $provider::recurringOrderProduct($userId, $providerBasketItemList, $productList);
			}
		}
	}

	/**
	 * @param BasketItem $basketItem
	 * @return array
	 * @throws NotSupportedException
	 */
	public static function getSetItems(BasketItem $basketItem)
	{
		$bundleChildList = array();
		if ($provider = $basketItem->getProvider())
		{
			if ($provider instanceof Provider)
			{
				throw new NotSupportedException('provider not supported');
			}
			elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
			{
				$bundleChildList = $provider::GetSetItems($basketItem->getProductId(), $basketItem::TYPE_SET, array('BASKET_ID' => $basketItem->getId()));

			}

			return $bundleChildList;
		}

		return false;
	}


	/**
	 * @param $basketProviderList
	 * @param array $productList
	 * @return array|bool
	 */
	protected static function getProductListFromBasketProviderList($basketProviderList, array $productList = array())
	{
		$select = array(
			'ID',
			'CAN_BUY_ZERO',
			'NEGATIVE_AMOUNT_TRACE',
			'QUANTITY_TRACE',
			'QUANTITY',
			'QUANTITY_RESERVED'
		);

		$providerProductList = array();

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				$providerProductList = $provider::getProductList($providerBasketItemList, $productList, $select) + $providerProductList;
			}
		}

		return (!empty($providerProductList) && is_array($providerProductList) ? $providerProductList : false);
	}


	public static function checkAvailableProductQuantity(BasketItem $basketItem, $deltaQuantity)
	{
		global $APPLICATION;

		$result = new Result();

		$resultProductData = array();

		$orderId = null;
		$userId = null;
		$siteId = null;

		/** @var Basket $basket */
		$basket = $basketItem->getCollection();

		if (($order = $basket->getOrder()) !== null)
		{
			$userId = $order->getUserId();
			$siteId = $order->getSiteId();
		}

		if ($userId === null || $siteId === null)
		{
			static $usersIds = array();

			if(empty($usersIds[$basket->getFUserId()]))
				$usersIds[$basket->getFUserId()] =  \CSaleUser::GetUserID($basket->getFUserId());

			$userId = $usersIds[$basket->getFUserId()];
			$siteId = $basket->getSiteId();
		}

		if ($provider = $basketItem->getProvider())
		{
			if ($provider instanceof Provider)
			{
				throw new NotSupportedException('provider not supported');
//					$result = $provider::getProductData($siteId, $userId, $currency, $providerBasketItemList, $select);
			}
			elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
			{
				$needQuantity = $basketItem->getQuantity();
				if ($order && $order->getId() > 0)
				{
					$needQuantity = $deltaQuantity;
				}

				$data = array(
					"PRODUCT_ID" => $basketItem->getProductId(),
					"QUANTITY"   => $needQuantity,
					"USER_ID"   => $userId,
					"SITE_ID"   => $siteId,
					"BASKET_ID" => $basketItem->getId(),
					"CHECK_QUANTITY" => "Y",
					'CHECK_PRICE' => 'N',
					'CHECK_COUPONS' => 'N',
					"SELECT_QUANTITY_TRACE" => "Y",
				);

				// TODO: !
				if ($deltaQuantity <= 0)
				{
					$result->setData(array('AVAILABLE_QUANTITY' => $deltaQuantity));
					return $result;
				}

				$APPLICATION->ResetException();
				$resultProductData = $provider::GetProductData($data);
				if ($ex = $APPLICATION->GetException())
				{
					$result->addError( new ResultError($ex->GetString(), $ex->GetID()) );
				}

			}
			else
			{
				$APPLICATION->ResetException();
				$resultProductData = \CSaleBasket::ExecuteCallbackFunction(
					$basketItem->getField('CALLBACK_FUNC'),
					$basketItem->getField('MODULE'),
					$basketItem->getProductId(),
					$basketItem->getQuantity()
				);

				if ($ex = $APPLICATION->GetException())
				{
					$result->addError( new ResultError($ex->GetString(), $ex->GetID()) );
				}
			}
		}
		else
		{
			$availableQuantity = $basketItem->getQuantity();
			if ($deltaQuantity <= 0)
			{
				$availableQuantity = $deltaQuantity;
			}
			$result->setData(array(
								 'AVAILABLE_QUANTITY' => $availableQuantity
							 ));
			return $result;
		}

		$fields = array();

		if (array_key_exists('QUANTITY', $resultProductData))
		{
			$fields['AVAILABLE_QUANTITY'] = $resultProductData['QUANTITY'];
		}

		if (array_key_exists('QUANTITY_TRACE', $resultProductData))
		{
			$fields['QUANTITY_TRACE'] = ($resultProductData['QUANTITY_TRACE'] == "Y");
		}

		if (!empty($fields))
		{
			$result->setData($fields);
		}

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return Result
	 * @throws NotSupportedException
	 */
	public static function deliverShipment(Shipment $shipment)
	{
		global $APPLICATION;

		$result = new Result();

		$needDeliver = $shipment->needDeliver();
		if ($needDeliver === null || ($needDeliver === false && $shipment->getId() <= 0))
			return $result;

		$resultList = array();

		$shipmentItemCollection = $shipment->getShipmentItemCollection();
		$basketList = static::getBasketFromShipmentItemCollection($shipmentItemCollection);

		$basketProviderMap = static::createProviderBasketMap($basketList, array('ORDER_ID', 'USER_ID', 'QUANTITY', 'PAID'));
		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		if (!empty($basketProviderList))
		{
			foreach ($basketProviderList as $provider => $providerBasketItemList)
			{
				if ($provider instanceof Provider)
				{
					throw new NotSupportedException('provider not supported');
				}
				elseif ($provider && array_key_exists("IBXSaleProductProvider", class_implements($provider)))
				{

					foreach ($providerBasketItemList as $providerBasketItem)
					{

						if ($providerBasketItem['BASKET_ITEM']->isBundleParent())
						{
							continue;
						}

						if (!$providerBasketItem['BASKET_ITEM']->isEmptyItem())
						{
							$data = array(
								"PRODUCT_ID" => $providerBasketItem["PRODUCT_ID"],
								"USER_ID"    => $providerBasketItem["USER_ID"],
								"PAID"       => $providerBasketItem["PAID"],
								"ORDER_ID"   => $providerBasketItem["ORDER_ID"],
								"BASKET_ID"  => $providerBasketItem['BASKET_ID']
							);

							$APPLICATION->ResetException();
							$resultProductData = $provider::DeliverProduct($data);
							if ($ex = $APPLICATION->GetException())
							{
								$result->addError( new ResultError($ex->GetString(), $ex->GetID()) );
							}


							if (!empty($resultProductData) && is_array($resultProductData))
							{
								$resultProductData['ORDER_ID'] = $providerBasketItem['ORDER_ID'];
							}
						}
						else
						{
							$resultProductData = true;
						}

						$resultList[$providerBasketItem['BASKET_CODE']] = $resultProductData;

					}

				}
				else
				{
					foreach ($providerBasketItemList as $providerBasketItem)
					{
						$resultProductData = \CSaleBasket::ExecuteCallbackFunction(
							$providerBasketItem['CALLBACK_FUNC'],
							$providerBasketItem['MODULE'],
							$providerBasketItem['PRODUCT_ID'],
							$providerBasketItem['USER_ID'],
							$providerBasketItem["PAID"],
							$providerBasketItem['ORDER_ID'],
							$providerBasketItem["QUANTITY"]
						);

						$basketCode = $providerBasketItem['BASKET_ITEM']->getBasketCode();

						if (!empty($resultProductData) && is_array($resultProductData))
						{
							$resultProductData['ORDER_ID'] = $providerBasketItem['ORDER_ID'];
						}

						$resultList[$basketCode] = $resultProductData;
					}
				}
			}

			if (!empty($resultList) && is_array($resultList))
			{
				foreach ($resultList as $basketCode => $resultData)
				{
					if ($resultData && !empty($resultData) && is_array($resultData))
					{
						$resultData["REMAINING_ATTEMPTS"] = (defined("SALE_PROC_REC_ATTEMPTS") ? SALE_PROC_REC_ATTEMPTS : 3);
						$resultData["SUCCESS_PAYMENT"] = "Y";

						// TODO: recurringID
						/*
						if ($recurringID > 0)
							\CSaleRecurring::Update($recurringID, $resultData);
						else
							\CSaleRecurring::Add($resultData);
						*/
					}
					/*
					elseif ($recurringID > 0)
					{
						\CSaleRecurring::Delete($recurringID);
					}
					*/
				}
			}
		}

		if (!empty($resultList))
		{
			$result->setData($resultList);
		}

		return $result;
	}

	/**
	 * @param array $basketList
	 * @param array $select
	 * @return array
	 * @throws ObjectNotFoundException
	 */
	protected static function createProviderBasketMap(array $basketList, array $select = array())
	{
		$basketProviderMap = array();

		/**
		 * @var string $basketKey
		 * @var BasketItem $basketItem
		 */
		foreach($basketList as $basketIndex => $basketItemDat)
		{
			if (is_array($basketItemDat) && isset($basketItemDat['BASKET_ITEM']))
			{
				$basketItem = $basketItemDat['BASKET_ITEM'];
			}
			else
			{
				$basketItem = $basketItemDat;
			}

			$basketProviderData = array(
				'BASKET_ITEM' => $basketItem,
				'BASKET_ID' => $basketItem->getId(),
				'BASKET_CODE' => $basketItem->getBasketCode(),
				'PRODUCT_ID' => $basketItem->getProductId(),
				'MODULE' => $basketItem->getField('MODULE'),
			);

			if ($provider = $basketItem->getProvider())
			{
				$basketProviderData['PROVIDER'] = $provider;
			}
			elseif (strval($basketItem->getField('CALLBACK_FUNC')) != '' || strval($basketItem->getField('PAY_CALLBACK_FUNC')) != '')
			{
				$basketProviderData['CALLBACK_FUNC'] = strval($basketItem->getField('CALLBACK_FUNC')) != '' ? $basketItem->getField('CALLBACK_FUNC') : $basketItem->getField('PAY_CALLBACK_FUNC');
			}
			else
			{
				continue;
			}


			if (in_array('QUANTITY', $select))
			{
				$basketProviderData['QUANTITY'] = $basketItem->getQuantity(); // ????
			}

			if (in_array('RENEWAL', $select))
			{
				$basketProviderData['RENEWAL'] = $basketItem->getField('RENEWAL')!== null && $basketItem->getField('RENEWAL') != 'N'? 'Y' : 'N';
			}

			if (in_array('RESERVED', $select))
			{
				$basketProviderData['RESERVED'] = $basketItemDat['RESERVED'];
			}

			if (in_array('SITE_ID', $select))
			{
				$basketProviderData['SITE_ID'] = $basketItem->getField('LID');
			}

			if (in_array('ORDER_ID', $select))
			{
				/** @var Basket $basket */
				if (!$basket = $basketItem->getCollection())
				{
					throw new ObjectNotFoundException('Entity "Basket" not found');
				}

				if ($basket->getOrder() && $basket->getOrderId() > 0)
				{
					$basketProviderData['ORDER_ID'] = $basket->getOrderId();
				}

			}

			if (in_array('USER_ID', $select))
			{
				/** @var Basket $basket */
				if (!$basket = $basketItem->getCollection())
				{
					throw new ObjectNotFoundException('Entity "Basket" not found');
				}

				if ($order = $basket->getOrder())
				{
					$userId = $order->getUserId();

					if ($userId === null)
					{
						$userId = \CSaleUser::GetUserID($basket->getFUserId());
					}

					if ($userId > 0)
					{
						$basketProviderData['USER_ID'] = $userId;
					}
				}

			}

			if (in_array('PAID', $select))
			{
				/** @var Basket $basket */
				if (!$basket = $basketItem->getCollection())
				{
					throw new ObjectNotFoundException('Entity "Basket" not found');
				}

				if ($basket->getOrder() && $basket->getOrderId() > 0)
				{
					$order = $basket->getOrder();
					$basketProviderData['PAID'] = $order->isPaid();
				}

			}


			$basketProviderMap[$basketIndex] = $basketProviderData;

		}

		return $basketProviderMap;
	}

	/**
	 * @param Shipment $shipment
	 * @return array
	 */
	private static function getProviderBasketFromShipment(Shipment $shipment)
	{
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		$basketList = static::getBasketFromShipmentItemCollection($shipmentItemCollection);

		$basketProviderMap = static::createProviderBasketMap($basketList, array('QUANTITY', 'PRODUCT_ID'));

		$basketProviderList = static::redistributeToProviders($basketProviderMap);

		return $basketProviderList;
	}

	/**
	 * @param array $basketProviderMap
	 * @return array
	 */
	protected static function redistributeToProviders(array $basketProviderMap)
	{

		$basketProviderList = array();
		foreach($basketProviderMap as $basketProviderItem)
		{
			$basketProviderList[$basketProviderItem['PROVIDER']][] = $basketProviderItem;
		}

		return $basketProviderList;
	}

	/**
	 * @internal
	 * @param bool $value
	 */
	public static function setUsingTrustData($value)
	{
		static::$useReadTrustData = (bool)$value;
	}

	/**
	 * @internal
	 * @return bool
	 */
	public static function isReadTrustData()
	{
		return (bool)static::$useReadTrustData;
	}


	/**
	 * @internal
	 * @param $siteId
	 * @param $module
	 * @param $productId
	 *
	 * @return bool
	 */
	public static function isExistsTrustData($siteId, $module, $productId)
	{
		return (!empty(static::$trustData[$siteId][$module][$productId]) && is_array(static::$trustData[$siteId][$module][$productId]));
	}


	/**
	 * @internal
	 * @param string $siteId
	 * @param string $module
	 * @param int $productId
	 * @param array $fields
	 */
	public static function setTrustData($siteId, $module, $productId, array $fields)
	{
		static::$trustData[$siteId][$module][$productId] = $fields;
	}


	/**
	 * @internal
	 * @param $siteId
	 * @param $module
	 * @param $productId
	 *
	 * @return null
	 */
	public static function getTrustData($siteId, $module, $productId)
	{
		if (static::isExistsTrustData($siteId, $module, $productId))
			return static::$trustData[$siteId][$module][$productId];

		return null;
	}

	/**
	 * @internal
	 * @param null|string $siteId
	 * @param null|string $module
	 * @param null|int $productId
	 */
	public static function resetTrustData($siteId = null, $module = null, $productId = null)
	{
		if (strval($siteId) != '')
		{
			if (!empty(static::$trustData[$siteId]))
			{
				if (intval($productId) > 0 )
				{
					if (strval($module) == '')
					{
						foreach (static::$trustData[$siteId] as $moduleName => $data)
						{
							if (isset(static::$trustData[$siteId][$moduleName][$productId]))
								unset(static::$trustData[$siteId][$moduleName][$productId]);
						}
					}
					else
					{
						if (isset(static::$trustData[$siteId][$module][$productId]))
							unset(static::$trustData[$siteId][$module][$productId]);
					}
				}
				elseif (strval($module) != '')
				{
					if (isset(static::$trustData[$siteId][$module]))
						unset(static::$trustData[$siteId][$module]);
				}
				else
				{
					if (isset(static::$trustData[$siteId]))
						unset(static::$trustData[$siteId]);
				}
			}
		}
		else
		{
			static::$trustData = array();
		}

	}

	/**
	 * @internal
	 * @return array
	 */
	protected static function getProductDataRequiredFields()
	{
		return array(
			'NAME',
			'CAN_BUY',
			'BARCODE_MULTI',
			'WEIGHT',
			'TYPE',
			'QUANTITY',
		);
	}

	/**
	 * @internal
	 * @return array
	 */
	protected static function getProductDataRequiredPriceFields()
	{
		return array(
			'PRODUCT_PRICE_ID',
			'NOTES',
			'VAT_RATE',
			'BASE_PRICE',
			'PRICE',
			'CURRENCY',
			'DISCOUNT_PRICE',
		);
	}
}
