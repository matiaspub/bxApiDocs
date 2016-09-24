<?
namespace Sale\Handlers\Delivery\Spsr;

use Bitrix\Main\Error;
use Bitrix\Sale\Result;
use Bitrix\Sale\Shipment;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Localization\Loc;

class Calculator
{
	protected static $url = "http://www.cpcr.ru/cgi-bin/postxml.pl";

	protected static function getHttpClient()
	{
		return new \Bitrix\Main\Web\HttpClient(array(
			"version" => "1.1",
			"socketTimeout" => 20,
			"streamTimeout" => 20,
			"redirect" => true,
			"redirectMax" => 5,
		));
	}

	protected static function getLocationCode(Shipment $shipment)
	{
		$order = $shipment->getCollection()->getOrder();

		if(!$props = $order->getPropertyCollection())
			return '';

		if(!$locationProp = $props->getDeliveryLocation())
			return '';

		if(!$locationCode = $locationProp->getValue())
			return '';

		return $locationCode;
	}

	protected static function buildRequest(Shipment $shipment, array $additional)
	{
		$result = new Result();
		$request = "TARIFFCOMPUTE_2";
		$fromBLocationCode = \CSaleHelper::getShopLocationId($shipment->getCollection()->getOrder()->getSiteId());

		if(strlen($fromBLocationCode) <= 0)
		{
			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_CALC_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'CALCULATOR',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_LOC_FROM_B'),
			));

			return $result;
		}

		$fromCity = Location::getExternal($fromBLocationCode);

		if(strlen($fromCity) <= 0)
		{
			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_CALC_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'CALCULATOR',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_LOC_FROM_S'),
			));

			return $result;
		}

		$toBLocationCode = self::getLocationCode($shipment);

		if(strlen($toBLocationCode) <= 0)
		{
			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_CALC_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'CALCULATOR',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_LOC_TO_B'),
			));

			return $result;
		}

		$toCity = Location::getExternal($toBLocationCode);

		if(strlen($toCity) <= 0)
		{
			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_CALC_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'CALCULATOR',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_LOC_TO_S'),
			));

			return $result;
		}

		if(floatval($shipment->getWeight()) > 0)
			$weight = $shipment->getWeight()/1000;
		elseif($additional['DEFAULT_WEIGHT'])
			$weight = intval($additional['DEFAULT_WEIGHT'])/1000;
		else
			$weight = 0;



		if(floatval($weight) <= 0)
		{
			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_CALC_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'CALCULATOR',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_WEIGHT'),
			));

			return $result;
		}

		$request .= '&ToCity='.$toCity.'&FromCity='.$fromCity;

		if(!empty($additional['NATURE']))
			$request .= '&Nature='.$additional['NATURE'];

		if(!empty($additional['SID']) )
			$request .= '&SID='.$additional['SID'];

		if(!empty($additional['ICN']) )
			$request .= '&ICN='.$additional['ICN'];

		if(!empty($additional['EXTRA_SERVICES']))
		{
			if(!empty($additional['EXTRA_SERVICES']['SMS']) && $additional['EXTRA_SERVICES']['SMS'] == 'Y')
				$request .= '&SMS=1';

			if(!empty($additional['EXTRA_SERVICES']['SMS_RECV']) && $additional['EXTRA_SERVICES']['SMS_RECV'] == 'Y')
				$request .= '&SMS_Recv=1';

			if(!empty($additional['EXTRA_SERVICES']['BEFORE_SIGNAL']) && $additional['EXTRA_SERVICES']['BEFORE_SIGNAL'] == 'Y')
				$request .= '&BeforeSignal=1';

			if(!empty($additional['EXTRA_SERVICES']['BY_HAND']) && $additional['EXTRA_SERVICES']['BY_HAND'] == 'Y')
				$request .= '&ByHand=1';

			if(!empty($additional['EXTRA_SERVICES']['ICD']) && $additional['EXTRA_SERVICES']['ICD'] == 'Y')
				$request .= '&icd=1';

			if(!empty($additional['EXTRA_SERVICES']['PLAT_TYPE']) && $additional['EXTRA_SERVICES']['PLAT_TYPE'] == 'Y')
				$request .= '&PlatType=1';

			if(!empty($additional['EXTRA_SERVICES']['TO_BE_CALLED_FOR']) && $additional['EXTRA_SERVICES']['TO_BE_CALLED_FOR'] == 'Y')
				$request .= '&ToBeCalledFor=1';
		}

		$maxWeight = 0;
		$gabarit180 = false;
		$price = 0;
		$volume = 0;
		$volumeWeight = 0;

		/** @var \Bitrix\Sale\ShipmentItem $item */
		foreach($shipment->getShipmentItemCollection() as $item)
		{
			$basketItem = $item->getBasketItem();
			$itemWeight = floatval($basketItem->getWeight());

			if($maxWeight < $itemWeight)
				$maxWeight = $itemWeight;

			$dimensions = $basketItem->getField('DIMENSIONS');

			if(!is_array($dimensions) && strlen($dimensions) > 0)
				$dimensions = unserialize($dimensions);

			if(!empty($dimensions['WIDTH']) && !empty($dimensions['HEIGHT']) && !empty($dimensions['LENGTH']))
			{
				$width = floatval($dimensions['WIDTH']);
				$height = floatval($dimensions['HEIGHT']);
				$length = floatval($dimensions['LENGTH']);
				$quantityItem = floatval($basketItem->getField('QUANTITY'));
				$volume += $quantityItem*$width*$height*$length/1000 ; //cm

				if(!$gabarit180 && $width+$height+$length > 1800) //mm
				{
					$request .= '&GabarythB=1';
					$gabarit180 = true;
				}
			}

			$price += $basketItem->getPrice();
		}

		if($volume > 0)
			$volumeWeight = $volume / 5000;

		$request .= '&Weight='.($volumeWeight > $weight ? $volumeWeight : $weight);

		if($maxWeight > 200000) // gr
			$request .= '&Weight200=1';
		elseif($maxWeight > 80000)
			$request .= '&Weight80=1';
		elseif($maxWeight > 35000)
			$request .= '&Weight35=1';

		if(isset($additional['AMOUNT_CHECK']))
			$request .= '&Amount='.$price.'&AmountCheck='.$additional['AMOUNT_CHECK'];

		//todo: DuesOrder ClickAndCollectPostomat
		$result->setData(array($request));
		return $result;
	}

	public static function calculate(Shipment $shipment, $additional)
	{
		$result = new \Bitrix\Sale\Result();
		$res = self::buildRequest($shipment, $additional);

		if(!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());
			return $result;
		}

		$data = $res->getData();
		$requestParams = $data[0];
		$calcRes = Cache::getCalcRes($requestParams);

		if($calcRes === false)
		{
			$res = self::sendRequest($requestParams);

			if(!$res->isSuccess())
			{
				$result ->addErrors($res->getErrors());
				return $result;
			}

			$data = $res->getData();
			$xmlAnswer = new \SimpleXMLElement($data[0]);
					
			if((bool)$xmlAnswer->Error && !empty($xmlAnswer->Error))
			{
				$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

				$eventLog = new \CEventLog;
				$eventLog->Add(array(
					"SEVERITY" => $eventLog::SEVERITY_ERROR,
					"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_ERROR",
					"MODULE_ID" => "sale",
					"ITEM_ID" => "CALCULATOR",
					"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_CALCULATE').": ".self::utfDecode($xmlAnswer->Error),
				));

				return $result;
			}

			$calcRes = array();

			if((bool)$xmlAnswer->Tariff)
			{
				foreach($xmlAnswer->Tariff as $tarif)
				{
					$calcRes[] = array(
						'TariffType' => self::utfDecode((string)$tarif->TariffType),
						'Total_Dost' => (string)$tarif->Total_Dost,
						'Total_DopUsl' => (string)$tarif->Total_DopUsl,
						'Insurance' => (string)$tarif->Insurance,
						'worth' =>(string)$tarif->worth,
						'DP' =>(string)$tarif->DP
					);
				}
			}

			Cache::setCalcRes($calcRes, $requestParams);
		}

		$result->addData($calcRes);
		return $result;

	}

	protected static function sendRequest($request)
	{
		$result = new \Bitrix\Sale\Result();
		$httpClient = self::getHttpClient();
		$httpRes = $httpClient->get(self::$url.'?'.$request);
		$errors = $httpClient->getError();

		if (!$httpRes && !empty($errors))
		{
			$strError = "";

			foreach($errors as $errorCode => $errMes)
				$strError .= $errorCode.": ".$errMes;

			$result->addError(new \Bitrix\Main\Error(\Bitrix\Main\Localization\Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_HTTP_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'CALCULATOR',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP').":".$strError,
			));
		}
		else
		{
			$status = $httpClient->getStatus();

			if ($status != 200)
			{
				$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

				$eventLog = new \CEventLog;
				$eventLog->Add(array(
					"SEVERITY" => $eventLog::SEVERITY_ERROR,
					"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_HTTP_STATUS_ERROR",
					"MODULE_ID" => "sale",
					"ITEM_ID" => 'CALCULATOR',
					"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_STATUS').": ".$status,
				));
			}

			$result->addData(array($httpRes));
		}

		return $result;
	}

	protected static function utfDecode($str)
	{
		if(strtolower(SITE_CHARSET) != 'utf-8')
			$str = Encoding::convertEncoding($str, 'UTF-8', SITE_CHARSET);

		return $str;
	}
}