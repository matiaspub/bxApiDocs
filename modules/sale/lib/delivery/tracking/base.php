<?php

namespace Bitrix\Sale\Delivery\Tracking;

use \Bitrix\Sale\Delivery\Services;

/**
 * Class Base
 * @package Bitrix\Sale\Delivery\Tracking
 *
 * Base class for shipment tracking services handlers
 */
abstract class Base
{
	/** @var array */
	protected $params;
	/** @var  Services\Base */
	protected $deliveryService;

	/**
	 * @param array $params
	 * @param Services\Base $deliveryService
	 */
	public function __construct(array $params, Services\Base $deliveryService)
	{
		$this->params = $params;
		$this->deliveryService = $deliveryService;
	}

	/**
	 * Returns class name for administration interface
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает название класса для административного интерфейса. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/delivery/tracking/base/getclasstitle.php
	* @author Bitrix
	*/
	abstract public function getClassTitle();

	/**
	 * Returns class description for administration interface
	 * @return string
	 */
	
	/**
	* <p>Метод возвращает описание класса для административного интерфейса. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return string 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/delivery/tracking/base/getclassdescription.php
	* @author Bitrix
	*/
	abstract public function getClassDescription();

	/**
	 * @param $trackingNumber
	 * @return \Bitrix\Sale\Delivery\Tracking\StatusResult.
	 */
	abstract public function getStatus($trackingNumber);

	/**
	 * @param string[] $trackingNumbers
	 * @return \Bitrix\Sale\Result.
	 */
	abstract public function getStatuses(array $trackingNumbers);

	/**
	 * Returns params structure
	 * @return array
	 */
	
	/**
	* <p>Метод возвращает массив параметров. Нестатический метод.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sale/delivery/tracking/base/getparamsstructure.php
	* @author Bitrix
	*/
	abstract public function getParamsStructure();

	/**
	 * @param string $paramKey
	 * @param string $inputName
	 * @return string Html
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEditHtml($paramKey, $inputName)
	{
		$paramsStructure = $this->getParamsStructure();

		return \Bitrix\Sale\Internals\Input\Manager::getEditHtml(
			$inputName,
			$paramsStructure[$paramKey],
			$this->params[$paramKey]
		);
	}

	/**
	 * @param string $trackingNumber
	 * @return string Url were we can see tracking information
	 */
	static public function getTrackingUrl($trackingNumber = '')
	{
		return '';
	}
}