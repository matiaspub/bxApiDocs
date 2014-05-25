<?php
namespace Bitrix\Main\Security\Sign;

use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Config\Option;

/**
 * Class Signer
 * @since 14.0.7
 * @package Bitrix\Main\Security\Sign
 */
class Signer
{
	/** @var \Bitrix\Main\Security\Sign\SigningAlgorithm */
	protected $algorithm = null;
	protected $separator = '.';
	/** @var string */
	protected $key = null;

	/**
	 * @param SigningAlgorithm $algorithm
	 */
	public function __construct(SigningAlgorithm $algorithm = null)
	{
		if ($algorithm !== null)
			$this->algorithm = $algorithm;
		else
			$this->algorithm = new HmacAlgorithm();
	}

	/**
	 * @param string $value
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function setKey($value)
	{
		if (!is_string($value))
			throw new ArgumentTypeException('value', 'string');

		$this->key = $value;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->separator;
	}

	/**
	 * @param string $value
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function setSeparator($value)
	{
		if (!is_string($value))
			throw new ArgumentTypeException('value', 'string');

		$this->separator = $value;
		return $this;
	}

	/**
	 * @param string $value
	 * @param string|null $salt
	 * @return string
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function getSignature($value, $salt = null)
	{
		if (!is_string($value))
			throw new ArgumentTypeException('value', 'string');

		$key = $this->getKey($salt);
		$signature = $this->algorithm->getSignature($value, $key);
		return base64_encode($signature);
	}

	/**
	 * Simple example:
	 * <code>
	 * //test.salted_sign_hash
	 * echo (new Signer)->sign('test', 'my_salt');
	 * //test.sign_hash
	 * echo (new Signer)->sign('test');
	 * </code>
	 *
	 * @param string $value
	 * @param string|null $salt
	 * @return string
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	public function sign($value, $salt = null)
	{
		if (!is_string($value))
			throw new ArgumentTypeException('value', 'string');

		return $value.$this->separator.$this->getSignature($value, $salt);
	}

	/**
	 * Simple example:
	 * <code>
	 * $signer = new Signer;
	 * $signedValue = $signer->sign('test');
	 * echo $signer->unsign($signedValue);
	 * //output 'test'
	 * echo $signer->unsign('test');
	 * //throw BadSignatureException with message 'Separator not found in value'
	 * echo $signer->unsign('test.invalid_sign');
	 * //throw BadSignatureException with message 'Signature does not match'
	 * echo $signer->unsign($signedValue, 'invalid_salt');
	 * </code>
	 *
	 * @param string $signedValue
	 * @param string|null $salt
	 * @return string
	 * @throws BadSignatureException
	 */
	public function unsign($signedValue, $salt = null)
	{
		if (strpos($signedValue, $this->separator) === false)
			throw new BadSignatureException('Separator not found in value');

		$pos = strrpos($signedValue, $this->separator);
		$value = substr($signedValue, 0, $pos);
		$sig = substr($signedValue, $pos + 1);
		if (!$this->verifySignature($value, $sig, $salt))
			throw new BadSignatureException('Signature does not match');

		return $value;
	}

	/**
	 * @param string $signedValue
	 * @param string|null $salt
	 * @return bool
	 */
	public function validate($signedValue, $salt = null)
	{
		try
		{
			$this->unsign($signedValue, $salt);
			return true;
		}
		catch(BadSignatureException $e)
		{
			return false;
		}
	}

	/**
	 * @param string $value
	 * @param string $sig
	 * @param string|null $salt
	 * @return bool
	 */
	protected function verifySignature($value, $sig, $salt = null)
	{
		$key = $this->getKey($salt);
		$signature = base64_decode($sig);
		return $this->algorithm->verify($value, $key, $signature);
	}

	/**
	 * @param string|null $salt
	 * @return string
	 */
	protected function getKey($salt = null)
	{
		if ($this->key !== null)
			$key = $this->key;
		else
			$key = $this->getDefaultKey();

		return strval($salt).$key;
	}

	/**
	 * @return string
	 */
	protected function getDefaultKey()
	{
		static $defaultKey = null;
		if ($defaultKey === null)
		{
			$defaultKey = Option::get('main', 'signer_default_key', false);
			if (!$defaultKey)
			{
				$defaultKey = hash('sha512', uniqid(rand(), true));
				Option::set('main', 'signer_default_key', $defaultKey, '');
			}
		}

		return $defaultKey;
	}
}