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
	// Default hashing algorithm used by HMAC
	protected $hashAlgorithm = 'sha256';


	/**
	 * Creates signing algorithm based on HMAC.
	 *
	 * @since 16.0.0
	 * @param string|null $hashAlgorithm Hashing algorithm (optional). See registered algorithms in hash_algos().
	 */
	
	/**
	* <p>Нестатический метод создаёт алгоритм подписи на основе <a href="https://ru.wikipedia.org/wiki/HMAC" >HMAC</a>.</p>
	*
	*
	* @param mixed $string  Алгоритм хэширования. Сморти зарегистрированные алгоритмы в <a
	* href="http://php.net/manual/pl/function.hash-algos.php" >hash_algos()</a>.
	*
	* @param null $hashAlgorithm = null 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/hmacalgorithm/__construct.php
	* @author Bitrix
	*/
	public function __construct($hashAlgorithm = null)
	{
		if ($hashAlgorithm)
			$this->setHashAlgorithm($hashAlgorithm);
	}

	/**
	 * Set hashing algorithm for using in HMAC
	 *
	 * @param string $hashAlgorithm Hashing algorithm. See registered algorithms in hash_algos().
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	
	/**
	* <p>Нестатический метод устанавливает алгоритм хеширования для использования в HMAC</p>
	*
	*
	* @param string $hashAlgorithm  Алгоритм хеширования. Смотри зарегистрированные алгоритмы в <a
	* href="http://php.net/manual/pl/function.hash-algos.php" >hash_algos()</a>.
	*
	* @return \Bitrix\Main\Security\Sign\HmacAlgorithm 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/hmacalgorithm/sethashalgorithm.php
	* @author Bitrix
	*/
	public function setHashAlgorithm($hashAlgorithm)
	{
		if (!in_array($hashAlgorithm, hash_algos()))
			throw new ArgumentOutOfRangeException('hashAlgorithm', hash_algos());

		$this->hashAlgorithm = $hashAlgorithm;
		return $this;
	}

	/**
	 * Return currently used hashing algorithm
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает текущий используемый алгоритм хеширования.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/hmacalgorithm/gethashalgorithm.php
	* @author Bitrix
	*/
	public function getHashAlgorithm()
	{
		return $this->hashAlgorithm;
	}

	/**
	 * Return message signature
	 *
	 * @param string $value Message.
	 * @param string $key Secret password for HMAC.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает подпись сообщения.</p>
	*
	*
	* @param string $value  Сообщение
	*
	* @param string $key  Секретный пароль для HMAC.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/hmacalgorithm/getsignature.php
	* @author Bitrix
	*/
	public function getSignature($value, $key)
	{
		return hash_hmac($this->hashAlgorithm, $value, $key, true);
	}

	/**
	 * Verify message signature
	 *
	 * @param string $value Message.
	 * @param string $key Secret password used while signing.
	 * @param string $sig Message signature password for HMAC.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод верифицирует подпись сообщения.</p>
	*
	*
	* @param string $value  Сообщение
	*
	* @param string $key  Секретный пароль используемый для подписи.
	*
	* @param string $sig  Пароль подписи сообщения для HMAC.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/hmacalgorithm/verify.php
	* @author Bitrix
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
	 * @param string $expected Expected string (e.g. generated signature).
	 * @param string $actual Actual string (e.g. signature received from user).
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