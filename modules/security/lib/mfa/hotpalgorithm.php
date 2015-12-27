<?php
namespace Bitrix\Security\Mfa;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class HotpAlgorithm
	extends OtpAlgorithm
{
	const SYNC_WINDOW = 15000;
	protected static $type = 'hotp';

	protected $window = 10;

	public function __construct()
	{
		$window = (int) Option::get('security', 'hotp_user_window', 10);
		if ($window && $window > 0)
			$this->window = $window;
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

		// Backward compatibility. This is the old logic and i can't change it right now:-(
		if (\CUtil::binStrlen($this->secret) > 25)
			$this->digest = 'sha256';

		return $this;
	}

	/**
	 * Verify provided input
	 *
	 * @param string $input Input received from user.
	 * @param int|string $params Synchronized user params, saved for this algorithm (see getSyncParameters).
	 * @throws ArgumentOutOfRangeException
	 * @return array [
	 *  bool isSuccess (Valid input or not),
	 *  string newParams (Updated user params for this OtpAlgorithm)
	 * ]
	 */
	public function verify($input, $params = 0)
	{
		$input = (string) $input;

		if (!preg_match('#^\d+$#D', $input))
			throw new ArgumentOutOfRangeException('input', 'string with numbers');

		$counter = (int) $params;
		$result = false;
		$window = $this->window;
		while ($window--)
		{
			if ($this->isStringsEqual($input, $this->generateOTP($counter)))
			{
				$result = true;
				break;
			}
			$counter++;
		}

		if ($result === true)
			return array($result, $counter + 1);

		return array($result, null);
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
	static public function generateUri($label, array $opts = array())
	{
		$opts += array('counter' => 1);
		return parent::generateUri($label, $opts);
	}

	/**
	 * Return synchronized user params for provided inputs
	 *
	 * @param string $inputA First code.
	 * @param string $inputB Second code.
	 * @throws OtpException
	 * @throws ArgumentOutOfRangeException
	 * @return string
	 */
	public function getSyncParameters($inputA, $inputB)
	{
		$counter = 0;
		$this->window = 1;
		for($i = 0; $i < self::SYNC_WINDOW; $i++)
		{
			list($verifyA,) = $this->verify($inputA, $counter);
			list($verifyB,) = $this->verify($inputB, $counter + 1);
			if ($verifyA && $verifyB)
			{
				$counter++;
				break;
			}
			$counter++;
		}

		if ($i === self::SYNC_WINDOW)
			throw new OtpException('Cannot synchronize this secret key with the provided password values.');

		return $counter;
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
			'title' => Loc::getMessage('SECURITY_HOTP_TITLE'),
			'required_two_code' => true
		);
	}
}