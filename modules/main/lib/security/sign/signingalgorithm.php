<?php
namespace Bitrix\Main\Security\Sign;

use Bitrix\Main\NotImplementedException;

/**
 * Class SigningAlgorithm
 * @since 14.0.7
 * @package Bitrix\Main\Security\Sign
 */
abstract class SigningAlgorithm
{
	/**
	 * @param string $value
	 * @param string $key
	 * @return string
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	static public function getSignature($value, $key)
	{
		throw new NotImplementedException('Method getSignature must be overridden');
	}

	/**
	 * @param string $value
	 * @param string $key
	 * @param string $sig
	 * @return bool
	 */
	public function verify($value, $key, $sig)
	{
		return $sig === $this->getSignature($value, $key);
	}
}