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
{
	/** @var Signer */
	protected $signer = null;

	/**
	 * Creates new TimeSigner object. If you want use your own signing algorithm - you can this
	 *
	 * @param SigningAlgorithm $algorithm Custom signing algorithm.
	 */
	
	/**
	* <p>Нестатический метод вызывается при создании экземпляра класса и позволяет в нем произвести при создании объекта какие-то действия. Если необходимо использовать новый алгоритм подписывания - сделайте это с помощью нового объекта.</p>
	*
	*
	* @param mixed $Bitrix  Пользовательский алгоритм подписания.
	*
	* @param Bitri $Main  
	*
	* @param Mai $Security  
	*
	* @param Securit $Sign  
	*
	* @param SigningAlgorithm $algorithm = null 
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/__construct.php
	* @author Bitrix
	*/
	public function __construct(SigningAlgorithm $algorithm = null)
	{
		$this->signer = new Signer($algorithm);
	}

	/**
	 * Set key for signing
	 *
	 * @param string $value Key.
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	
	/**
	* <p>Нестатический метод устанавливает ключ для подписи.</p>
	*
	*
	* @param string $value  Ключ.
	*
	* @return \Bitrix\Main\Security\Sign\TimeSigner 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/setkey.php
	* @author Bitrix
	*/
	public function setKey($value)
	{
		$this->signer->setKey($value);
		return $this;
	}

	/**
	 * Return separator, used for packing/unpacking
	 *
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод возвращает сепаратор, используемый для упаковки/распаковки.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/getseparator.php
	* @author Bitrix
	*/
	public function getSeparator()
	{
		return $this->signer->getSeparator();
	}

	/**
	 * Set separator, used for packing/unpacking
	 *
	 * @param string $value Separator.
	 * @return $this
	 * @throws \Bitrix\Main\ArgumentTypeException
	 */
	
	/**
	* <p>Нестатический метод устанавливает сепаратор для упаковки/распаковки.</p>
	*
	*
	* @param string $value  Сепаратор
	*
	* @return \Bitrix\Main\Security\Sign\TimeSigner 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/setseparator.php
	* @author Bitrix
	*/
	public function setSeparator($value)
	{
		$this->signer->setSeparator($value);
		return $this;
	}

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
	
	/**
	* <p>Нестатический метод подписывает сообщение с истекшим временем, возвращает строку в формате: <code> {message}{separator}{expired timestamp}{separator}{signature}</code>.</p>
	*
	*
	* @param string $value  Сообщение для подписи
	*
	* @param string $time  Метка времени или дата описания (представлено в формате
	* аналогичном <a href="http://php.net/manual/en/function.strtotime.php" >strtotime</a>).
	*
	* @param string $string  Соль, если необходимо.
	*
	* @param null $salt = null 
	*
	* @return string 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* // If salt needed
	*  $foo = (new TimeSigner)-&gt;sign('test', '+1 hour', 'my_salt');
	* 
	*  // Otherwise
	*  $bar = (new TimeSigner)-&gt;sign('test', '+1 day');
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/sign.php
	* @author Bitrix
	*/
	public function sign($value, $time, $salt = null)
	{
		$timestamp = $this->getTimeStamp($time);
		$signature = $this->getSignature($value, $timestamp, $salt);
		return $this->pack(array($value, $timestamp, $signature));
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
	
	/**
	* <p>Нестатический метод проверяет подпись и время жизни сообщения. Если оба параметра - OK, то возвращает оригинальное сообщение.</p>
	*
	*
	* @param string $signedValue  Подписанное значение, должно быть в формате: <code>{message}{separator}{expired
	* timestamp}{separator}{signature}</code>.
	*
	* @param string $string  Соль, если необходимо.
	*
	* @param null $salt = null 
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/unsign.php
	* @author Bitrix
	*/
	public function unsign($signedValue, $salt = null)
	{
		$timedValue = $this->signer->unsign($signedValue, $salt);

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
	 * Return message signature
	 *
	 * @param string $value Message.
	 * @param int $timestamp Expire timestamp.
	 * @param null $salt Salt (if needed).
	 * @return string
	 * @throws ArgumentTypeException
	 */
	
	/**
	* <p>Нестатический метод возвращает подпись сообщения.</p>
	*
	*
	* @param string $value  Сообщение.
	*
	* @param integer $timestamp  Истечение метки времени.
	*
	* @param null $salt = null Соль, если необходимо.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/getsignature.php
	* @author Bitrix
	*/
	public function getSignature($value, $timestamp, $salt = null)
	{
		if (!is_string($value))
			throw new ArgumentTypeException('value', 'string');

		$timedValue = $this->pack(array($value, $timestamp));
		return $this->signer->getSignature($timedValue, $salt);
	}

	/**
	 * Simply validation of message signature
	 *
	 * @param string $value Message.
	 * @param int $timestamp Expire timestamp.
	 * @param string $signature Signature.
	 * @param string|null $salt Salt, if used while signing.
	 * @return bool True if OK, otherwise - false.
	 */
	
	/**
	* <p>Нестатический метод выполняет простую валидацию подписи сообщения.</p>
	*
	*
	* @param string $value  Сообщение.
	*
	* @param integer $timestamp  Время истечения.
	*
	* @param string $signature  Подпись.
	*
	* @param string $string  Соль, если используется.
	*
	* @param null $salt = null 
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/validate.php
	* @author Bitrix
	*/
	public function validate($value, $timestamp, $signature, $salt = null)
	{
		try
		{
			$signedValue = $this->pack(array($value, $timestamp, $signature));
			$this->unsign($signedValue, $salt);
			return true;
		}
		catch(BadSignatureException $e)
		{
			return false;
		}
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

		if (is_string($time) && !is_numeric($time))
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

	/**
	 * Pack array values to single string:
	 * pack(['test', 'all', 'values']) -> 'test.all.values'
	 *
	 * @param array $values Values for packing.
	 * @return string
	 */
	
	/**
	* <p>Нестатический метод упаковывает массив значений в простую строку вида: <code>pack(['test', 'all', 'values']) -&gt; 'test.all.values'</code></p>
	*
	*
	* @param array $values  Значения для упаковки
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/pack.php
	* @author Bitrix
	*/
	public function pack(array $values)
	{
		return $this->signer->pack($values);
	}

	/**
	 * Unpack values from string (something like rsplit).
	 * Simple example for separator ".":
	 * <code>
	 *  // Unpack all values:
	 *  unpack('test.all.values', 0) -> ['test', 'all', 'values']
	 *
	 *  // Unpack 2 values (by default). First element containing the rest of string.
	 *  unpack('test.all.values') -> ['test.all', 'values']
	 *
	 *  // Exception if separator is missing
	 *  unpack('test.all values', 3) -> throws BadSignatureException
	 * </code>
	 *
	 * @param string $value String for unpacking.
	 * @param int $limit If $limit === 0 - unpack all values, default - 2.
	 * @return array
	 * @throws BadSignatureException
	 */
	
	/**
	* <p>Нестатический метод распаковывает значения из строки (подобно <b>rsplit</b>).</p>
	*
	*
	* @param string $value  Строка для распаковки.
	*
	* @param integer $limit = 2 Если <code>$limit === 0</code> - распаковывает все значения, по умолчанию - 2.
	*
	* @return array 
	*
	* <h4>Example</h4> 
	* <pre bgcolor="#323232" style="padding:5px;">
	* Простые примеры для разделителя ".":
	* 
	*  // Unpack all values:
	*  unpack('test.all.values', 0) -&gt; ['test', 'all', 'values']
	* 
	*  // Unpack 2 values (by default). First element containing the rest of string.
	*  unpack('test.all.values') -&gt; ['test.all', 'values']
	* 
	*  // Exception if separator is missing
	*  unpack('test.all values', 3) -&gt; throws BadSignatureException
	* </pre>
	*
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/timesigner/unpack.php
	* @author Bitrix
	*/
	public function unpack($value, $limit = 2)
	{
		return $this->signer->unpack($value, $limit);
	}
}