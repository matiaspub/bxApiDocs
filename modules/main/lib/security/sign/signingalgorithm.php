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
	 * Return message signature
	 *
	 * @param string $value Message.
	 * @param string $key Secret password.
	 * @return string
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	static public function getSignature($value, $key)
	{
		throw new NotImplementedException('Method getSignature must be overridden');
	}

	/**
	 * Verify message signature
	 *
	 * @param string $value Message.
	 * @param string $key Secret password used while signing.
	 * @param string $sig Message signature.
	 * @return bool
	 */
	public function verify($value, $key, $sig)
	{
		return $sig === $this->getSignature($value, $key);
	}
}