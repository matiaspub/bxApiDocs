<?php
namespace Bitrix\Main\Security\Sign;

use Bitrix\Main\NotImplementedException;

/**
 * Class SigningAlgorithm
 * @since 14.0.7
 * @package Bitrix\Main\Security\Sign
 */
abstract class SigningAlgorithm
{
	/**
	 * Return message signature
	 *
	 * @param string $value Message.
	 * @param string $key Secret password.
	 * @return string
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	
	/**
	* <p>Нестатический метод возвращает подпись сообщения.</p>
	*
	*
	* @param string $value  Сообщение.
	*
	* @param string $key  Секретный пароль.
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/signingalgorithm/getsignature.php
	* @author Bitrix
	*/
	static public function getSignature($value, $key)
	{
		throw new NotImplementedException('Method getSignature must be overridden');
	}

	/**
	 * Verify message signature
	 *
	 * @param string $value Message.
	 * @param string $key Secret password used while signing.
	 * @param string $sig Message signature.
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет подпись сообщения.</p>
	*
	*
	* @param string $value  Сообщение
	*
	* @param string $key  Секретный пароль, используемый для подписи.
	*
	* @param string $sig  Подпись сообщения.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/security/sign/signingalgorithm/verify.php
	* @author Bitrix
	*/
	public function verify($value, $key, $sig)
	{
		return $sig === $this->getSignature($value, $key);
	}
}