<?php

IncludeModuleLangFile(__FILE__);

class CMailRegru
{

	static public function __construct()
	{
	}

	// https://api.reg.ru/api/regru2/domain/check
	public static function checkDomain($user, $password, $domain, &$error)
	{
		$result = self::post('https://api.reg.ru/api/regru2/domain/check', array(
			'username'    => $user,
			'password'    => $password,
			'domain_name' => $domain
		));

		if (isset($result['result']) && $result['result'] == 'success')
			return $result['answer'];

		self::setError($result, $error);
		return false;
	}

	// https://api.reg.ru/api/regru2/domain/get_suggest
	public static function suggestDomain($user, $password, $word1, $word2, $tlds, &$error)
	{
		$result = self::post('https://api.reg.ru/api/regru2/domain/get_suggest', array(
			'username'     => $user,
			'password'     => $password,
			'input_format' => 'json',
			'input_data'   => json_encode(array(
				'word'            => $word1,
				'additional_word' => $word2,
				'tlds'            => $tlds
			))
		));

		if (isset($result['result']) && $result['result'] == 'success')
			return $result['answer'];

		self::setError($result, $error);
		return false;
	}

	// https://api.reg.ru/api/regru2/domain/create
	public static function createDomain($user, $password, $domain, $params, &$error)
	{
		$result = self::post('https://api.reg.ru/api/regru2/domain/create', array(
			'username'     => $user,
			'password'     => $password,
			'domain_name'  => $domain,
			'input_format' => 'json',
			'input_data'   => json_encode($params)
		));

		if (isset($result['result']) && $result['result'] == 'success')
			return $result['answer'];

		self::setError($result, $error);
		return false;
	}

	public static function updateDns($user, $password, $domain, $params, &$error)
	{
		$result = self::post('https://api.reg.ru/api/regru2/zone/update_records', array(
			'username'     => $user,
			'password'     => $password,
			'domain_name' => $domain,
			'input_format' => 'json',
			'input_data'   => json_encode(array(
				'action_list' => $params
			))
		));

		if (isset($result['result']) && $result['result'] == 'success')
			return $result['answer']['domains'][0];

		self::setError($result, $error);
		return false;
	}

	private static function post($url, $data)
	{
		$http = new \Bitrix\Main\Web\HttpClient();

		$response = $http->post($url, $data);
		$result   = json_decode($response, true);

		return $result;
	}

	private static function setError($result, &$error)
	{
		$error = empty($result['error_code'])
			? 'unknown'
			: $result['error_text'];
	}

}


/*

PASSWORD_AUTH_FAILED : Username/password Incorrect
INVALID_DOMAIN_NAME_FORMAT : domain_name is invalid or unsupported zone

*/
