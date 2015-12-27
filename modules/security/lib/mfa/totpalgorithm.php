<?php
namespace Bitrix\Security\Mfa;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TotpAlgorithm
	extends OtpAlgorithm
{
	const SYNC_WINDOW = 180;
	protected static $type = 'totp';

	protected $interval = 30;
	// ToDo: option here! May be just merge with HOTP window?
	protected $window = 2;
	protected $requireTwoCode = false;

	public function __construct()
	{
		$interval = (int) Option::get('security', 'totp_interval');
		if ($interval && $interval > 0)
			$this->interval = $interval;
	}

	/**
	 * Verify provided input.
	 *
	 * @param string $input Input received from user.
	 * @param string $params Synchronized user params, saved for this algorithm (see getSyncParameters).
	 * @param int|null $time Override system time, may be used in time machine.
	 * @throws ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @return array [
	 *  bool isSuccess (Valid input or not),
	 *  string newParams (Updated user params for this OtpAlgorithm)
	 * ]
	 */
	public function verify($input, $params = '0:0', $time = null)
	{
		$input = (string) $input;

		if (!preg_match('#^\d+$#D', $input))
			throw new ArgumentOutOfRangeException('input', 'string with numbers');

		list($userOffset, $lastTimeCode) = explode(':', $params);
		$userOffset = (int) $userOffset;
		$lastTimeCode = (int) $lastTimeCode;

		if ($time === null)
			$timeCode = $this->timecode(time());
		else
			$timeCode = $this->timecode((int) $time);

		$checkOffsets = array();
		// First of all we must check input for provided offset
		$checkOffsets[] = $userOffset;
		if ($userOffset)
		{
			// If we failed on previous step and have user offset - try current time, may be user syncing time on device
			$checkOffsets[] = 0;
		}

		if ($this->window)
		{
			// Otherwise, try deal with clock drifting
			$checkOffsets = array_merge(
				$checkOffsets,
				range($userOffset - $this->window, $userOffset + $this->window)
			);
		}

		$isSuccess = false;
		$resultOffset = 0;
		$resultTimeCode = 0;

		foreach($checkOffsets as $offset)
		{
			$code = $timeCode + $offset;
			// Disallow authorization in the past. Must prevent replay attacks.
			if ($lastTimeCode && $code <= $lastTimeCode)
				continue;

			if ($this->isStringsEqual($input, $this->generateOTP($code)))
			{
				$isSuccess = true;
				$resultOffset = $offset;
				$resultTimeCode = $code;
				break;
			}
		}

		if ($isSuccess === true)
			return array($isSuccess, sprintf('%d:%d', $resultOffset, $resultTimeCode));

		return array($isSuccess, null);
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
		$opts += array('period' => $this->getInterval());
		return parent::generateUri($label, $opts);
	}

	/**
	 * Make OTP counter from provided timestamp
	 *
	 * @param int $timestamp Timestamp.
	 * @return int
	 */
	protected function timecode($timestamp)
	{
		return (int) ( (((int) $timestamp * 1000) / ($this->getInterval() * 1000)));
	}

	/**
	 * Return used interval in counter generation
	 *
	 * @return int
	 */
	protected function getInterval()
	{
		return $this->interval;
	}

	/**
	 * Return synchronized user params for provided inputs
	 *
	 * @param string $inputA First code.
	 * @param null $inputB Second code not used for TOTP syncing.
	 * @throws OtpException
	 * @throws ArgumentOutOfRangeException
	 * @return string
	 */
	public function getSyncParameters($inputA, $inputB)
	{
		$offset = 0;
		$this->window = 0;

		$isSuccess = false;

		if (!$isSuccess)
		{
			// Before detect clock drift we must check current time :-)
			list($isSuccess,) = $this->verify($inputA, $offset);
		}

		if (!$isSuccess)
		{
			// Otherwise try to calculate resynchronization
			$offset = -self::SYNC_WINDOW;
			for($i = $offset; $i < self::SYNC_WINDOW; $i++)
			{
				list($isSuccess,) = $this->verify($inputA, $offset);
				if ($isSuccess)
				{
					break;
				}
				$offset++;
			}
		}

		if ($offset === self::SYNC_WINDOW)
			throw new OtpException('Cannot synchronize this secret key with the provided password values.');

		return sprintf('%d:%d', $offset, 0);
	}

	/**
	 * Returns algorithm description:
	 *  string type
	 *  string title
	 *  bool required_two_code
	 *
	 * @return array
	 */
	public static function getDescription()
	{
		return array(
			'type' => static::$type,
			'title' => Loc::getMessage('SECURITY_TOTP_TITLE'),
			'required_two_code' => false
		);
	}
}
