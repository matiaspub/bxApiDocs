<?php
namespace Bitrix\Main\Security\Sign;

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;

/**
 * Class HmacAlgorithm
 * @since 14.0.7
 * @package Bitrix\Main\Security\Sign
 */
class HmacAlgorithm
	extends SigningAlgorithm
{
	// ToDo: need option here?
	protected $hashAlgorithm = 'sha256';

	/**
	 * @param string $hashAlgorithm
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function setHashAlgorithm($hashAlgorithm)
	{
		if (!in_array($hashAlgorithm, hash_algos()))
			throw new ArgumentOutOfRangeException('hashAlgorithm', hash_algos());

		$this->hashAlgorithm = $hashAlgorithm;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHashAlgorithm()
	{
		return $this->hashAlgorithm;
	}

	/**
	 * @param string $value
	 * @param string $key
	 * @return string
	 */
	public function getSignature($value, $key)
	{
		return hash_hmac($this->hashAlgorithm, $value, $key, true);
	}

	/**
	 * @param string $value
	 * @param string $key
	 * @param string $sig
	 * @return bool
	 */
	public function verify($value, $key, $sig)
	{
		return $this->compareStrings(
			$this->getSignature($value, $key),
			$sig
		);
	}

	/**
	 * A timing safe comparison method.
	 *
	 * C function memcmp() internally used by PHP, exits as soon as a difference
	 * is found in the two buffers. That makes possible of leaking
	 * timing information useful to an attacker attempting to iteratively guess
	 * the unknown string (e.g. password).
	 *
	 * @param string $expected
	 * @param string $actual
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @return bool
	 */
	protected function compareStrings($expected, $actual)
	{
		if (!is_string($expected))
		{
			throw new ArgumentTypeException('expected', 'string');
		}

		if (!is_string($actual))
		{
			throw new ArgumentTypeException('actual', 'string');
		}

		if (function_exists('mb_orig_strlen'))
		{
			$lenExpected = mb_orig_strlen($expected);
			$lenActual = mb_orig_strlen($actual);
		}
		else
		{
			$lenExpected = strlen($expected);
			$lenActual = strlen($actual);
		}

		$status = $lenExpected ^ $lenActual;
		$len = min($lenExpected, $lenActual);
		for ($i = 0; $i < $len; $i++)
		{
			$status |= ord($expected[$i]) ^ ord($actual[$i]);
		}

		return $status === 0;
	}

}