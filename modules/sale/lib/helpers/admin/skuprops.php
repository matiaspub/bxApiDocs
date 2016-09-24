<?
namespace Bitrix\Sale\Helpers\Admin;

use Bitrix\Main\Loader;

/**
 * Class SkuPropsV1
 * @package Bitrix\Sale\Helpers\Admin
 * Helper class to find offer id by sku properties
 */
class SkuProps
{
	/**
	 * @param array $skuProps
	 * @param array $offersIds
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function filterByProps(array $skuProps, array $offersIds = array())
	{
		if(empty($offersIds))
			return array();

		if(!Loader::includeModule('iblock'))
			return array();

		$result = array();
		$filter = array('ID' => $offersIds);

		foreach($skuProps as $id => $value)
		{
			$propField = 'PROPERTY_'.$id;

			if($value == '-')
				$filter[$propField] = false;
			else
				$filter[$propField] = $value;
		}

		$res = \CIBlockElement::GetList(array("SORT"=>"ASC"), $filter, false, false, array('ID'));

		while($el = $res->Fetch())
			$result[] = $el['ID'];

		return $result;
	}

	/**
	 * @param array $skuProps
	 * @param array $skuOrder
	 * @param $changedSkuId
	 * @return array
	 */
	protected static function extractRequiredProps(array $skuProps, array $skuOrder, $changedSkuId)
	{
		$result = array();

		foreach($skuOrder as $skuId)
		{
			if(!empty($skuProps[$skuId]))
				$result[$skuId] = $skuProps[$skuId];

			if($skuId == $changedSkuId)
				break;
		}

		return $result;
	}

	/**
	 * @param array $skuProps
	 * @param array $skuOrder
	 * @param $changedSkuId
	 * @return array
	 */
	protected static function extractOptionalProps(array $skuProps, array $skuOrder, $changedSkuId)
	{
		$result = array();
		$changedAchieved = false;

		foreach($skuOrder as $skuId)
		{
			if($skuId == $changedSkuId)
			{
				$changedAchieved = true;
				continue;
			}

			if($skuId != $changedSkuId && !$changedAchieved)
				continue;

			if(!empty($skuProps[$skuId]))
				$result[$skuId] = $skuProps[$skuId];
		}

		return $result;
	}

	/**
	 * @param array $skuProps
	 * @param $productId
	 * @param $iblock
	 * @param array $skuOrder
	 * @param $changedSkuId
	 * @return int|mixed
	 * @internal
	 */
	public static function getProductId(array $skuProps, $productId, array $skuOrder, $changedSkuId)
	{
		if(
			empty($skuProps)
			|| intval($productId) <= 0
			|| empty($skuOrder)
			|| intval($changedSkuId) <= 0
			)
		{
			return 0;
		}

		$offersIds = self::getOffersIds($productId);
		$requiredProps = self::extractRequiredProps($skuProps, $skuOrder, $changedSkuId);
		$offersIds = self::filterByProps($requiredProps, $offersIds[$productId]);

		if(count($offersIds) == 0)
			return 0;

		if(count($offersIds) == 1)
			return current($offersIds);

		$optionalProps = self::extractOptionalProps($skuProps, $skuOrder, $changedSkuId);

		if(empty($optionalProps))
			return current($offersIds);

		foreach($optionalProps as $id => $val)
		{
			$prevProducts = $offersIds;
			$offersIds = self::filterByProps(array($id => $val), $offersIds);

			if(count($offersIds) == 0)
				return current($prevProducts);

			if(count($offersIds) == 1)
				return current($offersIds);
		}

		return current($offersIds);
	}

	/**
	 * @param $productId
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function getOffersIds($productIds)
	{
		if(!Loader::includeModule('catalog'))
			return array();

		$result = array();
		$offers = \CCatalogSKU::getOffersList($productIds, 0, array(), array('ID'));

		if(is_array($offers))
		{
			foreach($offers as $productId => $items)
			{
				if(!is_array($result[$productId]))
					$result[$productId] = array();

				foreach($items as $item)
					$result[$productId][] = $item['ID'];
			}
		}

		return $result;
	}

	/**
	 * @param $propId
	 * @param $currentValue
	 * @param array $offersIds
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected static function getSkuPropValues($propId, $currentValue, array &$offersIds)
	{
		if(!Loader::includeModule('iblock'))
			return array();

		$result = array();
		$foundElements = array();

		$res = \CIBlockElement::GetList(
			array("SORT"=>"ASC"),
			array(
				'ID' => $offersIds,
				'!=PROPERTY_'.$propId => false
			),
			false,
			false,
			array('ID', 'PROPERTY_'.$propId)
		);

		while($el = $res->Fetch())
		{
			if(isset($el['PROPERTY_'.$propId.'_ENUM_ID']))
				$value = $el['PROPERTY_'.$propId.'_ENUM_ID'];
			else
				$value = $el['PROPERTY_'.$propId.'_VALUE'];

			if(!in_array($value, $result))
				$result[] = $value;

			$foundElements[] = $el['ID'];

			if($value != $currentValue)
			{
				$key = array_search($el['ID'], $offersIds);

				if($key !== false)
					unset($offersIds[$key]);
			}
		}

		$foundElements = array_unique($foundElements, SORT_NUMERIC);
		$valueLess = array_diff($offersIds, $foundElements);

		if(!empty($valueLess))
		{
			if(!empty($result))
				$result[] = '-';

			if($currentValue != '-')
				$offersIds = array_diff($offersIds, $valueLess);
		}

		return $result;
	}

	/**
	 * @param array $params
	 * @return array
	 * @internal
	 */
	public static function getPossibleSkuPropsValues(array $params)
	{
		$result = array();
		$productIds = array();

		foreach($params as $param)
			$productIds[] = $param['PRODUCT_ID'];

		$productOffersIds = self::getOffersIds($productIds);

		foreach($params as $param)
		{
			if(intval($param['PRODUCT_ID']) <= 0)
				continue;

			if(empty($productOffersIds[$param['PRODUCT_ID']]))
				continue;

			$offerId = intval($param['OFFER_ID']);

			if(!is_array($result[$offerId]))
				$result[$offerId] = array();

			if(empty($param['SKU_PROPS']) || empty($param['SKU_ORDER']))
				continue;

			$offersIds = $productOffersIds[$param['PRODUCT_ID']];

			foreach($param['SKU_ORDER'] as $propId)
			{
				if(empty($param['SKU_PROPS'][$propId]))
					continue;

				if(count($offersIds) > 0)
					$res = self::getSkuPropValues($propId, $param['SKU_PROPS'][$propId], $offersIds);

				if(count($offersIds) > 0)
				{
					if(!empty($res))
						$result[$offerId][$propId] = $res;
				}
				else
				{
					$result[$offerId][$propId] = $param['SKU_PROPS'][$propId];
				}
			}
		}

		return $result;
	}
}