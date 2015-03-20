<?php
namespace Bitrix\Crm;
class MessageHelper
{
	public static function getNumberDeclension($number, $nominative, $genitiveSingular, $genitivePlural)
	{
		$number = intval($number);
		if($number === 0)
		{
			return $genitivePlural;
		}

		if($number < 0)
		{
			$number = -$number;
		}

		$lastDigit = $number % 10;
		$penultimateDigit = (($number % 100) - $lastDigit) / 10;

		if ($lastDigit === 1 && $penultimateDigit !== 1)
		{
			return $nominative;
		}

		return ($penultimateDigit !== 1 && $lastDigit >= 2 && $lastDigit <= 4)
			? $genitiveSingular : $genitivePlural;
	}
}