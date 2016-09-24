<?php
namespace Bitrix\Catalog\Helpers\Admin;

use Bitrix\Main,
	Bitrix\Iblock,
	Bitrix\Catalog;

class IblockPriceChanger
{
	private $iblockId = 0;
	private $userDialogParams = array();

	/**
	 * IblockChangePrice constructor.
	 * 
	 * @param array $userDialogParams
	 * @param int $iblockId
	 */

	public function __construct(array $userDialogParams, $iblockId)
	{
		$this->setUserDialogParams( $userDialogParams );
		$this->iblockId = (int)$iblockId;
	}

	/**
	 * Set of parameters which was set in CAdminDialog
	 *
	 * @param array $userDialogParams		Dialog's parameter.
	 * @return array|bool
	 */
	public function setUserDialogParams(array $userDialogParams)
	{
		if (!isset($userDialogParams['VALUE_CHANGING'])
			||((float)($userDialogParams['VALUE_CHANGING'] == 0)
			||!isset($userDialogParams['PRICE_TYPE'])
			||!(int)($userDialogParams['PRICE_TYPE'])))
		{
			return false;
		}
		else
		{
			$userDialogParams['PRICE_TYPE'] = (int)($userDialogParams['PRICE_TYPE']);
		}

		if (!isset($userDialogParams['DIFFERENCE_VALUE'])||!(float)$userDialogParams['DIFFERENCE_VALUE'])
		{
			$userDialogParams['DIFFERENCE_VALUE'] = 0;
		}

		if (!isset($userDialogParams['RESULT_MASK'])||!(float)$userDialogParams['RESULT_MASK'])
		{
			$userDialogParams['RESULT_MASK'] = 1;
		}

		if (!isset($userDialogParams['UNITS']))
		{
			$userDialogParams['UNITS'] = null;
		}
		
		return $this->userDialogParams = $userDialogParams;
	}

	/**
	 * Get list of all chosen elements
	 *
	 * @param @return array $productsIdList
	 */
	private function collectAllSectionsElements(&$productsIdList)
	{
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		$resultAllElementsList = \CIBlockElement::getList(
			array(),
			array(
				"SECTION_ID"=>$productsIdList['SECTIONS'],
				"IBLOCK_ID" => $this->iblockId,
				"WF_PARENT_ELEMENT_ID" => NULL,
				"INCLUDE_SUBSECTIONS"=>"Y",
				"CHECK_PERMISSIONS" => "Y", 
				"MIN_PERMISSION" => "W"
			),
			false,
			false,
			array('ID'));
		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		while ($subSectionsResult = $resultAllElementsList->fetch())
		{
			$productsIdList['ELEMENTS'][] = $subSectionsResult['ID'];
		}
		unset($subSectionsResult, $resultAllElementsList);
		unset( $productsIdList['SECTIONS'] );
	}

	/**
	 * Get list of id's price elements
	 *
	 * @param array $productsIdList
	 * @return array priceElementsIdList
	 */
	private function collectPriceSkuElementsId($productsIdList)
	{
		$sectionElementsIdList = array();

		$skuIdList = \CCatalogSku::getOffersList($productsIdList['ELEMENTS'], $this->iblockId);
		if(is_array($skuIdList))
		{
			foreach ($skuIdList as $skuId => $skuListElements)
			{
				$sectionElementsIdList[] = $skuId;
				foreach ($skuListElements as $skuElement)
				{
					$priceElementsIdList['SKU_ELEMENTS'][] = $skuElement["ID"];
				}
			}
		}

		if (empty($priceElementsIdList))
		{
			$priceElementsIdList['SIMPLE_ELEMENTS'] = $productsIdList['ELEMENTS'];
		}
		elseif ($elementsWithoutSkuIdList = array_diff($productsIdList['ELEMENTS'], $sectionElementsIdList))
		{
			$priceElementsIdList['SIMPLE_ELEMENTS'] = $elementsWithoutSkuIdList;
			unset ($elementsWithoutSkuIdList);
		}

		return $priceElementsIdList;
	}

	/**
	 * Return array of parameters for CPrice::GetList
	 *
	 * @return array $filterList
	 */
	private function initFilterParams()
	{
		$filterList = array();
		if (is_null($this->userDialogParams['INITIAL_PRICE_TYPE']))
		{
			$filterList = array("=CATALOG_GROUP_ID"=>intval($this->userDialogParams['PRICE_TYPE']));
		}

		if ($this->userDialogParams['UNITS'] !== "percent"
			&& $this->userDialogParams['UNITS'] !== "multiple")
		{
			$filterList["=CURRENCY"] = $this->userDialogParams['UNITS'];
		}

		return $filterList;
	}

	/**
	 * Calculate price element before update by user's params
	 *
	 * @param float $price
	 * @return float $price
	 */
	private function calculateResultPrice($price)
	{
		$userDialogParams = $this->userDialogParams;
		$valueChangingPrice = $this->userDialogParams['VALUE_CHANGING'];

		if ($userDialogParams['UNITS'] === "percent")
		{
			$price = ($price * (100 + $valueChangingPrice) / 100);
		}
		elseif ($userDialogParams['UNITS'] === "multiple")
		{
			if ($valueChangingPrice > 0)
			{
				$price = $price * $valueChangingPrice;
			}
			else
			{
				$price = $price / $valueChangingPrice * (-1);
			}
		}
		else
		{
			$price = $price + $valueChangingPrice;
		}

		switch ($userDialogParams['FORMAT_RESULTS'])
		{
			case "floor":
				$price = floor($price * $userDialogParams['RESULT_MASK']) / $userDialogParams['RESULT_MASK'] - $userDialogParams['DIFFERENCE_VALUE'] ;
				break;
			case "ceil":
				$price = ceil($price * $userDialogParams['RESULT_MASK']) / $userDialogParams['RESULT_MASK'] - $userDialogParams['DIFFERENCE_VALUE'] ;
				break;
			default:
				$price = round($price * $userDialogParams['RESULT_MASK']) / $userDialogParams['RESULT_MASK'] - $userDialogParams['DIFFERENCE_VALUE'] ;
				break;
		}

		return $price;
	}

	/**
	 * Function updates prices of chosen elements by GroupOperations
	 *
	 * @param array $productsIdList		 List of elements's IDs.
	 * @return Main\Result
	 */
	public function updatePrices($productsIdList)
	{
		$result = new Main\Result();

		if ($this->userDialogParams == false)
		{
			$result->addError( 
				new Main\Error("IBLIST_CHPRICE_ERROR_WRONG_INPUT_VALUE", null)
			);
			return  $result;
		}

		if($this->userDialogParams['UNITS'] === null)
		{
			$result->addError(
				new Main\Error("IBLIST_CHPRICE_ERROR_WRONG_CURRENCY")
			);
			return  $result;
		}

		if (!empty( $productsIdList['SECTIONS']) )
		{
			$this->collectAllSectionsElements($productsIdList);
		}

		/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
		if (\CCatalogSku::getInfoByProductIBlock($this->iblockId))
		{
			$priceElementsListSplitedByType = $this->collectPriceSkuElementsId($productsIdList);
		}
		else
		{
			$priceElementsListSplitedByType['SIMPLE_ELEMENTS'] = $productsIdList['ELEMENTS'];
		}
		$parameters = array(
			"select" => array('*', 'ELEMENT_NAME' => 'ELEMENT.NAME', 'ELEMENT_IBLOCK_ID' => 'ELEMENT.IBLOCK_ID'),
			"filter" => $this->initFilterParams()
		);
		$elementsCPriceList = array();
		$boolRecalc = false;
		$basePriceId = null;

		$groupResult = Catalog\GroupTable::getList(array(
			'select' => array('ID'),
			'filter' => array('=BASE'=>'Y')
		));
		while($group = $groupResult->fetch())
		{
			$basePriceId = $group['ID'];
		}

		foreach ($priceElementsListSplitedByType as $typeElements => $priceElementsIdList)
		{
			$priceElementsIdList = array_chunk($priceElementsIdList, 500);
			foreach ($priceElementsIdList as $productIdList)
			{
				$parameters['filter']['@PRODUCT_ID'] = $productIdList;

				$cpriceResult = Catalog\PriceTable::getList($parameters);

				while ($elementCPrice = $cpriceResult->fetch())
				{
					if ($this->userDialogParams['PRICE_TYPE'] == $elementCPrice['CATALOG_GROUP_ID'])
					{
						$elementsCPriceList[(int)$elementCPrice['PRODUCT_ID']]['TARGET'] = $elementCPrice;
					}
					elseif ($this->userDialogParams['INITIAL_PRICE_TYPE'] == $elementCPrice['CATALOG_GROUP_ID'])
					{
						$elementsCPriceList[(int)$elementCPrice['PRODUCT_ID']]['INITIAL'] = $elementCPrice;
					}
				}

				foreach ($elementsCPriceList as $elementCPrice)
				{
					if (empty($elementCPrice['TARGET']))
					{
						if (!empty($elementCPrice['INITIAL']))
						{
							$newPriceElement = $elementCPrice['INITIAL'];
							$newPriceElement['PRICE'] = $this->calculateResultPrice($newPriceElement['PRICE']);

							if ($newPriceElement['PRICE'] > 0)
							{
								$newPriceElement['CATALOG_GROUP_ID'] = $this->userDialogParams['PRICE_TYPE'];
								unset($newPriceElement['ID']);
								/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
								\CPrice::add($newPriceElement);
							}
						}
					}
					else
					{
						if ($elementCPrice['INITIAL']['PRICE'] > 0)
						{
							$elementCPrice['TARGET']['PRICE'] = $elementCPrice['INITIAL']['PRICE'];
						}

						$elementCPrice['TARGET']['PRICE'] = $this->calculateResultPrice($elementCPrice['TARGET']['PRICE']);

						if ($elementCPrice['TARGET']['PRICE'] <= 0)
						{
							$result->addError(
								new Main\Error("IBLIST_CHPRICE_ERROR_WRONG_VALUE_".$typeElements,
									array(
										"#ID#" => $elementCPrice['TARGET']['PRODUCT_ID'],
										"#NAME#" => $elementCPrice['TARGET']['ELEMENT_NAME'],
									)
								)
							);
							continue;
						}

						if (!is_null($elementCPrice['TARGET']['EXTRA_ID']))
						{
							$result->addError(
								new Main\Error("IBLIST_CHPRICE_ERROR_PRICE_WITH_EXTRA_".$typeElements,
									array(
										"#ID#" => $elementCPrice['TARGET']['PRODUCT_ID'],
										"#NAME#" => $elementCPrice['TARGET']['ELEMENT_NAME'],
									)
								)
							);
							continue;
						}

						if ($elementCPrice['TARGET']['CATALOG_GROUP_ID'] === $basePriceId && !is_null($elementCPrice['INITIAL']['EXTRA_ID']))
						{
							$result->addError(
								new Main\Error("IBLIST_CHPRICE_ERROR_BASE_FROM_EXTRA_".$typeElements,
									array(
										"#ID#" => $elementCPrice['TARGET']['PRODUCT_ID'],
										"#NAME#" => $elementCPrice['TARGET']['ELEMENT_NAME'],
									)
								)
							);
							continue;
						}

						if ($elementCPrice['TARGET']['CATALOG_GROUP_ID'] === $basePriceId)
						{
							$boolRecalc = true;
						}
						/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
						\CPrice::update($elementCPrice['TARGET']['ID'], $elementCPrice['TARGET'], $boolRecalc);
					}
					Iblock\PropertyIndex\Manager::updateElementIndex($elementCPrice['TARGET']['ELEMENT_IBLOCK_ID'], $elementCPrice['TARGET']['PRODUCT_ID']);
				}
				unset($elementsCPriceList, $elementCPrice);
			}
			/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
			\CCatalogSku::clearCache();
		}
		return $result;
	}
}
