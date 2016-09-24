<?php

namespace Sale\Handlers\Delivery;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\Delivery\Tracking\Statuses;
use Bitrix\Sale\Result;
use Sale\Handlers\Delivery\Spsr\Request;
use Bitrix\Sale\Delivery\Tracking\StatusResult;

Loc::loadMessages(__FILE__);

Loader::registerAutoLoadClasses(
	'sale',
	array(
		'Sale\Handlers\Delivery\SpsrHandler' => 'handlers/delivery/spsr/handler.php',
		'Sale\Handlers\Delivery\Spsr\Request' => 'handlers/delivery/spsr/request.php',
	)
);
/**
 * Class RusPost
 * @package \Sale\Handlers\Delivery;
 */
class SpsrTracking extends \Bitrix\Sale\Delivery\Tracking\Base
{
	/** @var  \Sale\Handlers\Delivery\SpsrHandler */
	protected $deliveryService;

	/**
	 * @return string
	 */
	static public function getClassTitle()
	{
		return Loc::getMessage("SALE_DLV_SRV_SPSR_T_TITLE");
	}

	/**
	 * @return string
	 */
	static public function getClassDescription()
	{
		return Loc::getMessage(
			"SALE_DLV_SRV_SPSR_T_DESCR",
			array(
				'#A1#' => '<a href="http://www.spsr.ru/">',
				'#A2#' => '</a>'
			)
		);
	}

	/**
	 * @param $trackingNumber
	 * @return StatusResult.
	 */
	public function getStatus($trackingNumber)
	{
		$results = $this->getStatuses(array($trackingNumber));
		$result = new StatusResult();

		if($results->isSuccess())
		{
			foreach($results->getData() as $statusResult)
				if($statusResult->trackingNumber == $trackingNumber)
					return $statusResult;
		}
		else
		{
			$result->addErrors($results->getErrors());
		}

		return $result;
	}

	/**
	 * @param array $trackingNumbers
	 * @return Result
	 */
	public function getStatuses(array $trackingNumbers)
	{
		$result = new Result();
		$resultData = array();
		$request = new Request();
		/** @var SpsrHandler $parentService */
		$parentService = $this->deliveryService->getParentService();

		if(!$parentService)
			return $result;

		/** @var \Bitrix\Sale\Result $res */
		$res = $parentService->getSidResult();

		if($res->isSuccess())
		{
			$data = $res->getData();
			$sid = $data[0];
		}
		else
		{
			$sid = "";
		}

		$reqRes = $request->getInvoicesInfo(
			$sid,
			$parentService->getICN(),
			LANGUAGE_ID,
			$trackingNumbers
		);

		/** @var \Bitrix\Sale\Result $reqRes */
		if($reqRes->isSuccess())
		{
			$invoicesInfo = $reqRes->getData();

			if(!empty($invoicesInfo['root']['#']['Invoices'][0]['#']['Invoice']) && is_array($invoicesInfo['root']['#']['Invoices'][0]['#']['Invoice']))
			{
				foreach($invoicesInfo['root']['#']['Invoices'][0]['#']['Invoice'] as $invoice)
				{
					if(!in_array($invoice['@']['InvoiceNumber'], $trackingNumbers))
						continue;

					$r = new StatusResult();

					if(!empty($invoice['#']['events'][0]['#']['event']) && is_array($invoice['#']['events'][0]['#']['event']))
					{
						$lastEvent = end($invoice['#']['events'][0]['#']['event']);
						$r->status = $this->translateStatus($lastEvent['@']['EventNumCode']);
						$r->description = $lastEvent['@']['EventName'];
						$r->lastChangeTimestamp = $this->translateDate($lastEvent['@']['Date']);
						$r->trackingNumber = $invoice['@']['InvoiceNumber'];
					}
					else
					{
						$r->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_T_ERROR_DATA')));
					}

					$resultData[] = $r;
				}
			}
			elseif(!empty($invoicesInfo['root']['#']['NotFound'][0]['#']['Invoice']) && is_array($invoicesInfo['root']['#']['NotFound'][0]['#']['Invoice']))
			{
				foreach($invoicesInfo['root']['#']['NotFound'][0]['#']['Invoice'] as $invoice)
				{
					$r = new StatusResult();
					$r->trackingNumber = $invoice['@']['InvoiceNumber'];
					$r->addError(
						new Error(
							self::utfDecode(
								$invoice['@']['ErrorMessage']
							)
						)
					);
					$resultData[] = $r;
				}
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_T_ERROR_DATA')));
			}
		}
		else
		{
			$result->addErrors($reqRes->getErrors());

		}

		if(!empty($resultData))
			$result->setData($resultData);

		return $result;
	}

	protected static function translateStatus($externalStatus)
	{
		if(strlen($externalStatus) <= 0)
			return Statuses::UNKNOWN;

		$statusMaps = array(
			Statuses::WAITING_SHIPMENT => array(),
			Statuses::ON_THE_WAY => array(2, 4, 6, 12, 13, 14, 17, 29, 30, 33, 34, 35, 39, 40, 41, 42, 43, 44, 45, 46,
				47, 48, 49, 50, 51, 53, 54, 105, 106, 107, 108, 109, 110, 111, 115, 119, 120, 122, 100, 32, 63, 64, 66,
				67, 74, 75, 76, 78, 79, 81, 84, 85, 86, 96),
			Statuses::ARRIVED => array(1, 8, 26, 31, ),
			Statuses::HANDED => array(15, 16, 27, 37, 55, 56, 57, 58, 59, 60, 61, 62, 92, 93, 112, 114, 116	),
			Statuses::PROBLEM => array(5, 7, 9, 10, 11, 18, 19, 20, 21, 22, 23, 24, 25, 28, 36, 38, 52, 113, 117, 123,
				124, 125, 126, 127, 128,129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 65, 68, 69,
				70, 71, 72, 73, 77, 80, 82, 83, 87, 88, 89, 90, 91, 94, 95, 97, 98, 99, 101, 102, 103, 104, 143, 144,
				145, 146, 147, 148, 150, 175),
		);

		foreach($statusMaps as $internalStatus => $map)
			if(in_array($externalStatus, $map))
				return $internalStatus;

		return Statuses::UNKNOWN;
	}

	protected static function translateDate($externalDate)
	{
		$date = new \DateTime($externalDate);
		return $date->getTimestamp();
	}

	/**
	 * @return array
	 */
	static public function getParamsStructure()
	{
		return array();
	}

	protected static function utfDecode($str)
	{
		if(strtolower(SITE_CHARSET) != 'utf-8')
			$str = Encoding::convertEncoding($str, 'UTF-8', SITE_CHARSET);

		return $str;
	}

	/**
	 * @param string $trackingNumber
	 * @return string Url were we can see tracking information
	 */
	static public function getTrackingUrl($trackingNumber = '')
	{
		return 'http://www.spsr.ru/ru/service/monitoring';
	}
}