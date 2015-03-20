<?php
namespace Bitrix\Crm;
class Discount
{
	const UNDEFINED = 0;
	const MONETARY = 1;
	const PERCENTAGE = 2;

	const MONETARY_NAME = 'MONETARY';
	const PERCENTAGE_NAME = 'PERCENTAGE';

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::MONETARY && $typeID <= self::PERCENTAGE;
	}
	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::MONETARY:
				return self::MONETARY_NAME;
			case self::PERCENTAGE:
				return self::PERCENTAGE_NAME;
			case self::UNDEFINED:
			default:
				return '';
		}
	}
	public static function calculateDiscountRate($originalPrice, $price)
	{
		$originalPrice = round(doubleval($originalPrice), 2);
		$price = round(doubleval($price), 2);

		if($originalPrice === 0.0)
		{
			return 0.0;
		}

		if($price === 0.0)
		{
			return $originalPrice > 0 ? 100.0 : -100.0;
		}

		return round(((100 * ($originalPrice - $price)) / $originalPrice), 2);
	}
	public static function calculateDiscountSum($price, $discountRate)
	{
		return (self::calculateOriginalPrice($price, $discountRate) - doubleval($price));
	}
	public static function calculateOriginalPrice($price, $discountRate)
	{
		$price = doubleval($price);
		$discountRate = doubleval($discountRate);
		return (100 * $price) / (100 - $discountRate);
	}
	public static function calculatePrice($originalPrice, $discountRate)
	{
		$originalPrice = doubleval($originalPrice);
		$discountRate = doubleval($discountRate);

		return $originalPrice - (($originalPrice * $discountRate) / 100);
	}
}