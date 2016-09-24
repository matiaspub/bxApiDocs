<?php
namespace Bitrix\Sale\TradingPlatform;

use \Bitrix\Main\SystemException;

/**
 * Class TimeIsOverException
 * Throws, then timelimit is over.
 * For multistep actions.
 * @package Bitrix\Sale\TradingPlatform
 */
class TimeIsOverException extends SystemException
{
	protected $endPosition;

	/**
	 * @param string $message Message to show.
	 * @param string  $endPosition Position from witch must be start ed next step.
	 * @param \Exception $previous.
	 */
	public function __construct($message = "", $endPosition = "", \Exception $previous = null)
	{
		parent::__construct($message, 0, '', 0, $previous);
		$this->endPosition = $endPosition;
	}

	/**
	 * Returns position from witch next step must be started.
	 * @return string
	 */
	
	/**
	* <p>Возвращает позицию, с которой должен начинаться следующий шаг. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/tradingplatform/timeisoverexception/getendposition.php
	* @author Bitrix
	*/
	public function getEndPosition()
	{
		return $this->endPosition;
	}
}
