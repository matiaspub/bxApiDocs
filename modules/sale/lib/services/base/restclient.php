<?php

namespace Bitrix\Sale\Services\Base;

use Bitrix\Main\Error;
use Bitrix\Sale\Result;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location\Exception;
use Bitrix\Sale\ResultSerializable;

Loc::loadMessages(__FILE__);

class RestClient
{
	const REST_URI = '/rest/';
	const REGISTER_URI = '/oauth/register/';
	const SCOPE = 'sale';
	const SERVICE_ACCESS_OPTION = 'saleservices_access';

	const ERROR_WRONG_INPUT = 1;
	const ERROR_WRONG_LICENSE = 2;
	const ERROR_SERVICE_UNAVAILABLE = 3;
	const ERROR_NOTHING_FOUND = 4;

	const UNSUCCESSFUL_CALL_OPTION = 'sale_hda_last_unsuccessful_call';
	const UNSUCCESSFUL_CALL_TRYINGS = 3; //times
	const UNSUCCESSFUL_CALL_WAIT_INTERVAL = 300; //sec

	protected $httpTimeout = 10;
	protected $accessSettings = null;
	protected $serviceHost = 'https://saleservices.bitrix.info';
	/**
	 * Performs call to the REST method and returns decoded results of the call.
	 * define SALE_SRVS_RESTCLIENT_DISABLE_SRV_ALIVE_CHECK to disable server alive checking.
	 * @param string $methodName Name of the REST method.
	 * @param array $additionalParams Parameters, that should be passed to the method.
	 * @param bool $licenseCheck Should client send license key as a parameter of the http request.
	 * @param bool $clearAccessSettings Should client clear authorization before performing http request.
	 * @return Result $result
	 */
	protected function call($methodName, $additionalParams = null, $licenseCheck = false, $clearAccessSettings = false)
	{
		$result = new ResultSerializable();

		if(!self::isServerAlive() && !defined('SALE_SRVS_RESTCLIENT_DISABLE_SRV_ALIVE_CHECK'))
		{
			$result->addError(new Error('Can\'t receive information'));
			return $result;
		}

		if ($clearAccessSettings)
			$this->clearAccessSettings();

		if (is_null($this->accessSettings))
			$this->accessSettings = $this->getAccessSettings();

		if (!$this->accessSettings)
		{
			$result->addError(new Error('Error access settings'));
			return $result;
		}

		if (!is_array($additionalParams))
			$additionalParams = array();
		else
			$additionalParams = Encoding::convertEncodingArray($additionalParams, LANG_CHARSET, "utf-8");

		$additionalParams['client_id'] = $this->accessSettings['client_id'];
		$additionalParams['client_secret'] = $this->accessSettings['client_secret'];

		if ($licenseCheck)
			$additionalParams['key'] = static::getLicenseHash();

		$host = $this->getServiceHost();
		$http = new HttpClient(array('socketTimeout' => $this->httpTimeout));
		$postResult = @$http->post(
			$host.static::REST_URI.$methodName,
			$additionalParams
		);

		try
		{
			$answer = $this->prepareAnswer($postResult);
		}
		catch(\Exception $e)
		{
			$answer = false;
		}

		if (!is_array($answer) || count($answer) == 0)
		{
			$result->addError(new Error('Malformed answer from service. Status: "'.$http->getStatus().'". Result: "'.$postResult.'"', static::ERROR_SERVICE_UNAVAILABLE));
			$this->setLastUnSuccessCallInfo();
			return $result;
		}

		if(self::getLastUnSuccessCount() > 0)
			$this->setLastUnSuccessCallInfo(true);

		if (array_key_exists('error', $answer))
		{
			if ($answer['error'] === 'verification_needed')
			{
				if($licenseCheck)
				{
					$result->addError(new Error($answer['error'].". ".$answer['error_description'], self::ERROR_WRONG_LICENSE));
					return $result;
				}
				else
				{
					return $this->call($methodName, $additionalParams, true);
				}
			}
			else if (($answer['error'] === 'ACCESS_DENIED' || $answer['error'] === 'Invalid client' || $answer['error'] == 'NO_AUTH_FOUND')
				&& !$clearAccessSettings)
			{
				return $this->call($methodName, $additionalParams, true, true);
			}

			$result->addError(new Error($answer['error'].". ".$answer['error_description']));
			return $result;
		}

		if ($answer['result'] == false)
			$result->addError(new Error('Nothing found', static::ERROR_NOTHING_FOUND));

		if (is_array($answer['result']))
			$result->addData($answer['result']);

		return $result;
	}

	/**
	 * @return string Host.
	 * Define const SALE_SRVS_RESTCLIENT_SRV_HOST to change server host.
	 */
	public function getServiceHost()
	{
		if(!defined('SALE_SRVS_RESTCLIENT_SRV_HOST'))
			$result = $this->serviceHost;
		else
			$result = SALE_SRVS_RESTCLIENT_SRV_HOST;

		return $result;
	}

	/**
	 * Decodes answer of the method.
	 * @param string $result Json-encoded answer.
	 * @return array|bool|mixed|string Decoded answer.
	 */
	protected function prepareAnswer($result)
	{
		return Json::decode($result);
	}

	/**
	 * Registers client on the properties service.
	 * @return array|false Access credentials if registration was successful or false otherwise.
	 */
	protected function register()
	{
		$result = new Result();
		$httpClient = new HttpClient();

		$queryParams = array(
			"key" => static::getLicenseHash(),
			"scope" => static::SCOPE,
			"redirect_uri" => static::getRedirectUri(),
		);

		$host = $this->getServiceHost();
		$postResult = $httpClient->post($host.static::REGISTER_URI, $queryParams);

		if ($postResult === false)
		{
			$result->addError(new Error(implode("\n", $httpClient->getError()), static::ERROR_SERVICE_UNAVAILABLE));
			return $result;
		}

		try
		{
			$jsonResult = Json::decode($postResult);
		}
		catch(Exception $e)
		{
			$result->addError(new Error($e->getMessage()));
			return $result;
		}

		if($jsonResult["error"])
			$result->addError(new Error($jsonResult["error"], static::ERROR_WRONG_LICENSE));
		else
			$result->addData($jsonResult);

		return $result;
	}

	/**
	 * Stores access credentials.
	 * @param array $params Access credentials.
	 * @return void
	 */
	protected static function setAccessSettings(array $params)
	{
		Option::set('sale', static::SERVICE_ACCESS_OPTION, serialize($params));
	}

	/**
	 * Reads and returns access credentials.
	 * @return array|false Access credentials or false in case of errors.
	 */
	protected function getAccessSettings()
	{
		$accessSettings = Option::get('sale', static::SERVICE_ACCESS_OPTION);

		if ($accessSettings != '')
		{
			return unserialize($accessSettings);
		}
		else
		{
			/** @var Result $result */
			$result = $this->register();
			if ($result->isSuccess())
			{
				$accessSettings = $result->getData();
				$this->setAccessSettings($accessSettings);
				return $accessSettings;
			}
			else
			{
				return array();
			}
		}
	}

	/**
	 * Drops current stored access credentials.
	 * @return void
	 */
	static public function clearAccessSettings()
	{
		Option::set('sale', static::SERVICE_ACCESS_OPTION, null);
	}

	/**
	 * Internal method for usage in registration process.
	 * @return string URL of the host.
	 */
	protected static function getRedirectUri()
	{
		$request = Context::getCurrent()->getRequest();

		$host = $request->getHttpHost();
		$isHttps = $request->isHttps();

		return ($isHttps ? 'https' : 'http').'://'.$host."/";
	}

	/**
	 * Returns md5 hash of the license key.
	 * @return string md5 hash of the license key.
	 */
	protected static function getLicenseHash()
	{
		return md5(LICENSE_KEY);
	}

	protected static function getLastUnSuccessCallInfo()
	{
		$result = Option::get('sale', static::UNSUCCESSFUL_CALL_OPTION, "");

		if(strlen($result) > 0)
			$result = unserialize($result);

		return is_array($result) ? $result : array();
	}

	/**
	 * @param bool|false $reset
	 */
	protected static function setLastUnSuccessCallInfo($reset = false)
	{
		static $alreadySetted = false;

		if($alreadySetted && !$reset)
			return;

		$data = "";

		if(!$reset)
		{
			$alreadySetted = true;
			$last = static::getLastUnSuccessCallInfo();

			$data = serialize(array(
					'COUNT' => intval($last['COUNT']) > 0 ? intval($last['COUNT'])+1 : 1,
					'TIMESTAMP' => time()
			));
		}

		Option::set('sale', static::UNSUCCESSFUL_CALL_OPTION, $data);
	}


	/**
	 * Check if server is alive.
	 * @return bool
	 */
	public static function isServerAlive()
	{
		$last = static::getLastUnSuccessCallInfo();

		if(empty($last))
			return true;

		if(time() - intval($last['TIMESTAMP']) >= self::UNSUCCESSFUL_CALL_WAIT_INTERVAL)
			return true;

		if(intval($last['COUNT']) <= self::UNSUCCESSFUL_CALL_TRYINGS)
			return true;

		return false;
	}

	/**
	 * @return int Counts
	 */
	protected function getLastUnSuccessCount()
	{
		$last = static::getLastUnSuccessCallInfo();
		return intval($last['COUNT']) > 0 ? intval($last['COUNT']) : 0;
	}
}