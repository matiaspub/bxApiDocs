<?php

namespace Bitrix\Sale\Delivery\Pecom;

class Request
{
	/**
	 * Base default URL
	 * @const string
	 */
	const API_BASE_URL = 'https://kabinet.pecom.ru/api/v1/';

	/**
	 * User login
	 * @var string
	 */
	protected $apiLogin = '';

	/**
	 * API access key
	 * @var string
	 */
	protected $apiKey = '';

	/**
	 * Base URL
	 * @var string
	 */
	protected $apiUrl = '';

	public function __construct($apiLogin, $apiKey, $apiUrl = '')
	{
		$this->apiLogin = $apiLogin;
		$this->apiKey = $apiKey;
		$this->apiUrl = ($apiUrl === '') ? self::API_BASE_URL : $apiUrl;
	}

	/**
	 * Calls API
	 * @param string $controller Group name
	 * @param string $action Method name
	 * @param mixed $data Input data
	 * @param bool $assoc Result format. true - array, false - object
	 * @return mixed Result
	 * @throws \Exception Case error during requesting
	 */
	public function send($controller, $action, $data, $assoc = true)
	{
		global $APPLICATION;
		$http = new \Bitrix\Main\Web\HttpClient(array(
			"version" => "1.1",
			"socketTimeout" => 30,
			"streamTimeout" => 30,
			"redirect" => true,
			"redirectMax" => 5,
		));

		$http->setHeader("Content-Type", "application/json; charset=utf-8");
		$http->setHeader("Authorization", "Basic ".base64_encode($this->apiLogin.":".$this->apiKey));

		if(strtolower(SITE_CHARSET) != 'utf-8')
			$data = $APPLICATION->ConvertCharsetArray($data, SITE_CHARSET, 'utf-8');

		$jsonData = json_encode($data);
		$result = $http->post($this->constructApiUrl($controller, $action), $jsonData);
		$errors = $http->getError();

		if (!$result && !empty($errors))
		{
			$strError = "";

			foreach($errors as $errorCode => $errMes)
				$strError .= $errorCode.": ".$errMes;

			throw new \Exception($strError);
		}
		else
		{
			$status = $http->getStatus();

			if ($status != 200)
			{
				throw new \Exception(sprintf('HTTP error code: %d', $status));
			}

			$resData = $http->getResult();

			$decodedResult = json_decode($resData, $assoc);

			if(strtolower(SITE_CHARSET) != 'utf-8')
				$decodedResult = $APPLICATION->ConvertCharsetArray($decodedResult, 'utf-8', SITE_CHARSET);
		}

		return $decodedResult;
	}

	/**
	 * Returns full URL for API request
	 * @param string $controller Group name
	 * @param string $action Method name
	 * @return string Full URL
	 */
	protected function constructApiUrl($controller, $action)
	{
		return sprintf('%s%s/%s/', $this->apiUrl, $controller, $action);
	}
}