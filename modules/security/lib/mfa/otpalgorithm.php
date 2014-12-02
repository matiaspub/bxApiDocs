<?php
namespace Bitrix\Security\Mfa;

use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Security\Codec\Base32;

abstract class OtpAlgorithm
{
	protected static $type = 'undefined';
	protected $digest = 'sha1';
	protected $digits = 6;
	protected $secret = null;
	protected $appScheme = 'otpauth';
	protected $requireTwoCode = true;

	/**
	 * Verify provided input
	 *
	 * @param string $input Input received from user.
	 * @param string $params Synchronized user params, saved for this algorithm (see getSyncParameters).
	 * @return array [
	 *  bool isSuccess (Valid input or not),
	 *  string newParams (Updated user params for this OtpAlgorithm)
	 * ]
	 */
	abstract public function verify($input, $params = null);

	/**
	 * Return synchronized user params for provided inputs
	 *
	 * @param string $inputA First code.
	 * @param string|null $inputB Second code. Must be provided if current OtpAlgorithm required it (see isTwoCodeRequired).
	 * @throws OtpException
	 * @return string
	 */
	abstract public function getSyncParameters($inputA, $inputB);

	/**
	 * Returns algorithm description
	 * Each algorithm must provide:
	 *  string type
	 *  string title
	 *
	 * @return array
	 * @throws NotImplementedException
	 */
	public static function getDescription()
	{
		throw new NotImplementedException('Method getDescription must be overridden');
	}

	/**
	 * Require or not _two_ code for synchronize parameters
	 *
	 * @return bool
	 */
	public function isTwoCodeRequired()
	{
		return $this->requireTwoCode;
	}

	/**
	 * Set new secret
	 *
	 * @param string $secret Secret (binary).
	 * @return $this
	 */
	public function setSecret($secret)
	{
		$this->secret = $secret;
		return $this;
	}

	/**
	 * Return used secret (binary)
	 *
	 * @return string
	 */
	public function getSecret()
	{
		return $this->secret;
	}

	/**
	 * Generate provision URI according to KeyUriFormat
	 *
	 * @link https://code.google.com/p/google-authenticator/wiki/KeyUriFormat
	 * @param string $label User label.
	 * @param array $opts Additional URI parameters, e.g. ['image' => 'http://example.com/my_logo.png'] .
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @return string
	 */
	public function generateUri($label, array $opts = array())
	{
		$positionalOpts = array(
			// Don't change order!
			'secret' => Base32::encode($this->getSecret())
		);

		$opts['algorithm'] = $this->getDigest();
		// Digest must be in upper case for some OTP apps (e.g Google Authenticator for iOS)
		$opts['algorithm'] = strtoupper($opts['algorithm']);
		$opts['digits'] = $this->getDigits();

		ksort($opts);

		// Some devices require a specific order for some parameters (e.g. Microsoft Authenticator require "secret" at first place %) )
		$opts = array_merge(
			$positionalOpts,
			$opts
		);

		$params = http_build_query($opts, '', '&');

		// Ugly hack for old PHP versions. Use PHP_QUERY_RFC3986 when Bitrix reached PHP 5.4.0
		$params = str_replace(
			array('+', '%7E'),
			array('%20', '~'),
			$params
		);

		return sprintf(
			'%s://%s/%s?%s',
			$this->getAppScheme(),
			$this->getType(),
			rawurlencode($label),
			$params
		);
	}

	/**
	 * Main method, generate OTP value for provided counter
	 *
	 * @param string|int $counter Counter.
	 * @return string
	 */
	public function generateOTP($counter)
	{
		$hash = hash_hmac($this->getDigest(), static::toByte($counter), $this->getSecret());
		$hmac = array();
		foreach (str_split($hash, 2) as $hex)
		{
			$hmac[] = hexdec($hex);
		}
		$offset = $hmac[19] & 0xf;
		$code = ($hmac[$offset + 0] & 0x7F) << 24;
		$code |= ($hmac[$offset + 1] & 0xFF) << 16;
		$code |= ($hmac[$offset + 2] & 0xFF) << 8;
		$code |= ($hmac[$offset + 3] & 0xFF);

		$otp = $code % pow(10, $this->getDigits());
		return str_pad($otp, $this->getDigits(), '0', STR_PAD_LEFT);
	}

	/**
	 * Convert value to byte string with padding
	 *
	 * @param string|int $value Value for convert. Must be unsigned integer, e.g. 123, '123', '0x7b', etc.
	 * @return string
	 */
	protected static function toByte($value)
	{
		$result = array();
		while ($value > 0)
		{
			$result[] = chr($value & 0xFF);
			$value >>= 8;
		}

		return str_pad(implode(array_reverse($result)), 8, "\000", STR_PAD_LEFT);
	}

	/**
	 * A timing safe comparison method
	 *
	 * @param string $expected Expected string (e.g. input from user).
	 * @param string $actual Actual string (e.g. generated password).
	 * @throws ArgumentTypeException
	 * @return bool
	 */
	protected function isStringsEqual($expected, $actual)
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

	/**
	 * Return used digest
	 * Mostly used for generate provision URI
	 *
	 * @return string
	 */
	public function getDigest()
	{
		return $this->digest;
	}

	/**
	 * Return digits (password length)
	 *
	 * @return int
	 */
	public function getDigits()
	{
		return $this->digits;
	}

	/**
	 * Return OtpAlgorithm type
	 *
	 * @return string
	 */
	public function getType()
	{
		return static::$type;
	}

	/**
	 * Return algorithm scheme
	 * Mostly used for generate provision URI
	 *
	 * @return string
	 */
	public function getAppScheme()
	{
		return $this->appScheme;
	}
}