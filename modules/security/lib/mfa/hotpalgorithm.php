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
	
	/**
	* <p>Нестатический метод подтверждает введенную информацию.</p>
	*
	*
	* @param string $input  Введенная пользователем информация.
	*
	* @param string $integer  Синхронизированные пользовательские параметры, сохраненные для
	* алгоритма (см. <a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/security/mfa/hotpalgorithm/getsyncparameters.php">getSyncParameters</a> -
	* <code>\Bitrix\Security\Mfa\HotpAlgorithm::getSyncParameters</code>).
	*
	* @param string $params  
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/mfa/hotpalgorithm/verify.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод генерирует ссылку для подключения мобильного аппарата с OTP в соответствии с <i>KeyUriFormat</i>.</p>
	*
	*
	* @param string $label  Пользовательская метка.
	*
	* @param array $opts = array() Дополнительные параметры URI.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/mfa/hotpalgorithm/generateuri.php
	* @author Bitrix
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
	
	/**
	* <p>Нестатический метод возвращает синхронизированные пользовательские параметры предоставленных данных.</p>
	*
	*
	* @param string $inputA  Первый код.
	*
	* @param string $inputB  Второй код.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/mfa/hotpalgorithm/getsyncparameters.php
	* @author Bitrix
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
	
	/**
	* <p>Статический метод возвращает описание алгоритма: <code>string type</code>, <code>string title</code>, <code>bool required_two_code</code>.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/security/mfa/hotpalgorithm/getdescription.php
	* @author Bitrix
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