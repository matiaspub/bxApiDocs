<?
namespace Sale\Handlers\Delivery\Spsr;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\Encoding;
use Bitrix\Sale\Result;
use Bitrix\Main\Error;

Loc::loadMessages(__FILE__);

class Request
{
	protected $httpClient;

	protected static $url_http = "http://api.spsr.ru:8020/waExec";
	protected static $url_https = "https://api.spsr.ru/";

	public function __construct()
	{
		$this->httpClient = new \Bitrix\Main\Web\HttpClient(array(
			"version" => "1.1",
			"socketTimeout" => 30,
			"streamTimeout" => 30,
			"redirect" => true,
			"redirectMax" => 5,
		));

		$this->httpClient->setHeader("Content-Type", "application/xml");
	}

	/**
	 * @param $requestData
	 * @return Result
	 */
	public function send($requestData)
	{
		$result = new Result();

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$requestData = Encoding::convertEncodingArray($requestData, SITE_CHARSET, 'UTF-8');

		$httpRes = $this->httpClient->post(self::$url_https, $requestData);
		$errors = $this->httpClient->getError();

		if (!$httpRes && !empty($errors))
		{
			$strError = "";

			foreach($errors as $errorCode => $errMes)
				$strError .= $errorCode.": ".$errMes;

			$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

			$eventLog = new \CEventLog;
			$eventLog->Add(array(
				"SEVERITY" => $eventLog::SEVERITY_ERROR,
				"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_HTTP_ERROR",
				"MODULE_ID" => "sale",
				"ITEM_ID" => 'REQUEST',
				"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP').":".$strError,
			));
		}
		else
		{
			$status = $this->httpClient->getStatus();

			if ($status != 200)
			{
				$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

				$eventLog = new \CEventLog;
				$eventLog->Add(array(
					"SEVERITY" => $eventLog::SEVERITY_ERROR,
					"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_HTTP_STATUS_ERROR",
					"MODULE_ID" => "sale",
					"ITEM_ID" => 'REQUEST',
					"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_STATUS').": ".$status,
				));
			}
			else
			{
				$xmlAnswer = new \SimpleXMLElement($httpRes);

				if(
					(bool)$xmlAnswer->error
					&& !empty($xmlAnswer->error['ErrorMessageRU']))
				{
					$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

					$eventLog = new \CEventLog;
					$eventLog->Add(array(
						"SEVERITY" => $eventLog::SEVERITY_ERROR,
						"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_ERROR",
						"MODULE_ID" => "sale",
						"ITEM_ID" => 'REQUEST',
						"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR').": ".self::utfDecode($xmlAnswer->error['ErrorMessageRU']),
					));
				}
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

	public function getServiceTypes($sid, array $knownServices)
	{
		$result = new Result();

		if(strlen($sid) > 0)
			$sidStr =  ' SID="'.$sid.'"';
		else
			$sidStr = '';

		$requestData = '<root  xmlns="http://spsr.ru/webapi/Info/Info/1.0">
			<p:Params Name="WAGetServices" Ver="1.0" xmlns:p="http://spsr.ru/webapi/WA/1.0" />
			<Login'.$sidStr.'/>
		</root>';

		$res = $this->send($requestData);

		if($res->isSuccess())
		{
			$data = $res->getData();
			$xmlAnswer = new \SimpleXMLElement($data[0]);
			$srvs = array();

			if((bool)$xmlAnswer->MainServices->Service)
			{
				foreach($xmlAnswer->MainServices->Service as $service)
				{
					if(!in_array((int)$service['ID'], $knownServices))
						continue;

					$srvs[(string)$service['ID']] = array(
						'ID' => self::utfDecode((string)$service['ID']),
						'Name' => self::utfDecode((string)$service['Name']),
						'ShortDescription' => self::utfDecode((string)$service['ShortDescription']),
						'Description' => self::utfDecode((string)$service['Description'])
					);
				}
			}

			if(!empty($srvs))
			{
				$result->setData($srvs);
			}
			else
			{
				$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

				$eventLog = new \CEventLog;
				$eventLog->Add(array(
					"SEVERITY" => $eventLog::SEVERITY_ERROR,
					"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_SERVICE_TYPE_ERROR",
					"MODULE_ID" => "sale",
					"ITEM_ID" => 'REQUEST',
					"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_SERVICE_TYPES'),
				));
			}
		}
		else
		{
			$result->addErrors($res->getErrors());
		}

		return $result;
	}

	public function getSidResult($login, $pass, $companyName)
	{
		$result = new Result();

		$requestData = '
			<root xmlns="http://spsr.ru/webapi/usermanagment/login/1.0">
				<p:Params Name="WALogin" Ver="1.0" xmlns:p="http://spsr.ru/webapi/WA/1.0" />
				<Login Login="'.$login.'" Pass="'.$pass.'" UserAgent="'.$companyName.'" />
			</root>';

		$res = $this->send($requestData);

		if($res->isSuccess())
		{
			$data = $res->getData();
			$xmlAnswer = new \SimpleXMLElement($data[0]);
			$sess = array();

			if((bool)$xmlAnswer->Login && !empty($xmlAnswer->Login['SID']))
			{
				$sess = (string)$xmlAnswer->Login['SID'];
				$result->setData(array($sess));
			}

			if(empty($sess))
			{
				$result->addError(new Error(Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_HTTP_PUBLIC')));

				$eventLog = new \CEventLog;
				$eventLog->Add(array(
					"SEVERITY" => $eventLog::SEVERITY_ERROR,
					"AUDIT_TYPE_ID" => "SALE_DELIVERY_HANDLER_SPSR_SESSION_ERROR",
					"MODULE_ID" => "sale",
					"ITEM_ID" => 'REQUEST',
					"DESCRIPTION" => Loc::getMessage('SALE_DLV_SRV_SPSR_ERROR_SESSION')." (".self::utfDecode($data[0]).")",
				));
			}
		}
		else
		{
			$result->addErrors($res->getErrors());
		}

		return $result;
	}

	public function getInvoicesInfo($sid, $icn, $lang, array $invoiceNumbers)
	{
		$result = new Result();

		if(strlen($sid) > 0)
			$sidStr =  ' SID="'.$sid.'"';
		else
			$sidStr = '';

		if(strlen($sid) > 0)
			$icnStr =  ' ICN="'.$icn.'"';
		else
			$icnStr = '';

		$requestData = '
			<root xmlns="http://spsr.ru/webapi/Monitoring/MonInvoiceInfo/1.3">
				<p:Params Name="WAMonitorInvoiceInfo" Ver="1.3" xmlns:p="http://spsr.ru/webapi/WA/1.0" />
				<Login'.$sidStr.$icnStr.'/>
				<Monitoring Language="'.$lang.'" >';

		foreach($invoiceNumbers as $number)
			$requestData .= '<Invoice InvoiceNumber="'.$number.'"/>';

		$requestData .= '
				</Monitoring>
			</root>';

		$res = $this->send($requestData);

		if($res->isSuccess())
		{
			$data = $res->getData();
			$objXML = new \CDataXML();
			$objXML->LoadString($data[0]);
			$invoiceInfo = $objXML->GetArray();

			if($invoiceInfo['root']['#']['Result'][0]['@']['RC'] == 0)
			{
				$result->setData($invoiceInfo);
			}
			else
			{
				$errorMsg = Loc::getMessage('SALE_DLV_SRV_SPSR_T_ERROR_DATA');

				if(!empty($invoiceInfo['root']['#']['error'][0]['@']['ErrorMessageRU']))
					$errorMsg = $invoiceInfo['root']['#']['error'][0]['@']['ErrorMessageRU'];
				elseif(!empty($invoiceInfo['root']['#']['error'][0]['@']['ErrorMessageEn']))
					$errorMsg = $invoiceInfo['root']['#']['error'][0]['@']['ErrorMessageEn'];
				elseif(!empty($invoiceInfo['root']['#']['error'][0]['@']['ErrorMessage']))
					$errorMsg = $invoiceInfo['root']['#']['error'][0]['@']['ErrorMessage'];

				$result->addError(new Error($errorMsg));
			}
		}
		else
		{
			$result->addErrors($res->getErrors());
		}

		return $result;
	}
}