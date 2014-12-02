<?php
namespace Bitrix\Main\Security\Sign;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentTypeException;

/**
 * Class TimeSigner
 * @sine 14.0.7
 * @package Bitrix\Main\Security\Sign
 */
class TimeSigner
	extends Signer
{

	/**
	 * Sign message with expired time, return string in format:
	 *  {message}{separator}{expired timestamp}{separator}{signature}
	 *
	 * Simple example:
	 * <code>
	 *  // If salt needed
	 *  $foo = (new TimeSigner)->sign('test', '+1 hour', 'my_salt');
	 *
	 *  // Otherwise
	 *  $bar = (new TimeSigner)->sign('test', '+1 day');
	 * </code>
	 *
	 * @param string $value Message for signing.
	 * @param string $time Timestamp or datetime description (presented in format accepted by strtotime).
	 * @param string|null $salt Salt, if needed.
	 * @return string
	 */
	public function sign($value, $time, $salt = null)
	{
		$timestamp = $this->getTimeStamp($time);

		$value = $this->pack(array($value, $timestamp));
		return parent::sign($value, $salt);
	}


	/**
	 * Check message signature and it lifetime. If everything is OK - return original message.
	 *
	 * Simple example:
	 * <code>
	 *  $signer = new TimeSigner;
	 *
	 *  // Sing message for 1 second
	 *  $signedValue = $signer->sign('test', '+1 second');
	 *
	 *  // Or sign with expiring on some magic timestamp (e.g. 01.01.2030)
	 *  $signedValue = $signer->sign('test', 1893445200);
	 *
	 *  // Get original message with checking
	 *  echo $signer->unsign($signedValue);
	 *  // Output: 'test'
	 *
	 *  // Try to unsigning not signed value
	 *  echo $signer->unsign('test');
	 *  //throw BadSignatureException with message 'Separator not found in value'
	 *
	 *  // Or with invalid sign
	 *  echo $signer->unsign('test.invalid_sign');
	 *
	 *  // Or invalid salt
	 *  echo $signer->unsign($signedValue, 'invalid_salt');
	 *  //throw BadSignatureException with message 'Signature does not match'
	 *
	 *  // Or expired lifetime
	 *  echo $signer->unsign($signedValue);
	 *  //throw BadSignatureException with message 'Signature timestamp expired (1403039921 < 1403040024)'
	 *
	 * </code>
	 *
	 * @param string $signedValue  Signed value, must be in format: {message}{separator}{expired timestamp}{separator}{signature}.
	 * @param string|null $salt Salt, if used while signing.
	 * @return string
	 * @throws BadSignatureException
	 */
	public function unsign($signedValue, $salt = null)
	{
		$timedValue = parent::unsign($signedValue, $salt);

		if (strpos($signedValue, $timedValue) === false)
			throw new BadSignatureException('Timestamp missing');

		list($value, $time) = $this->unpack($timedValue);
		$time = (int) $time;

		if ($time <= 0)
			throw new BadSignatureException(sprintf('Malformed timestamp %d', $time));

		if ($time < time())
			throw new BadSignatureException(sprintf('Signature timestamp expired (%d < %d)', $time, time()));

		return $value;
	}

	/**
	 * Return timestamp parsed from English textual datetime description
	 *
	 * @param string|int $time Timestamp or datetime description (presented in format accepted by strtotime).
	 * @return int
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ArgumentException
	 */
	protected function getTimeStamp($time)
	{
		if (!is_string($time) && !is_int($time))
			throw new ArgumentTypeException('time');

		if (is_string($time))
		{
			$timestamp = strtotime($time);
			if (!$timestamp)
				throw new ArgumentException(sprintf('Invalid time "%s" format. See "Date and Time Formats"', $time));
		}
		else
		{
			$timestamp = (int) $time;
		}

		if ($timestamp < time())
			throw new ArgumentException(sprintf('Timestamp %d must be greater than now()', $timestamp));

		return $timestamp;
	}
}