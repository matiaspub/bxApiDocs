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
	 * @param string $value
	 * @param string $time
	 * @param string|null $salt
	 * @return string
	 */
	public function sign($value, $time, $salt = null)
	{
		$timestamp = $this->getTimeStamp($time);

		$value = $value.$this->separator.$timestamp;
		return parent::sign($value, $salt);
	}

	/**
	 * @param string $signedValue
	 * @param string|null $salt
	 * @return string
	 * @throws BadSignatureException
	 */
	public function unsign($signedValue, $salt = null)
	{
		$timedValue = parent::unsign($signedValue, $salt);

		if (strpos($signedValue, $timedValue) === false)
			throw new BadSignatureException('Timestamp missing');

		$pos = strrpos($timedValue, $this->separator);
		$value = substr($timedValue, 0, $pos);
		$time = (int) substr($timedValue, $pos + 1);

		if ($time <= 0)
			throw new BadSignatureException(sprintf('Malformed timestamp %d', $time));

		if ($time < time())
			throw new BadSignatureException(sprintf('Signature timestamp expired (%d < %d)', $time, time()));

		return $value;
	}

	/**
	 * @param string|int $time
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