<?php

class CMailDomainRegistrar
{

	static public function __construct()
	{
	}

	public static function isDomainExists($user, $password, $domain, &$error)
	{
		$domain = CharsetConverter::ConvertCharset($domain, SITE_CHARSET, 'UTF-8');

		$result = CMailRegru::checkDomain($user, $password, $domain, $error);

		if ($result !== false)
		{
			if (isset($result['domains'][0]['dname']) && strtolower($result['domains'][0]['dname']) == strtolower($domain))
			{
				$result = $result['domains'][0];
				if ($result['result'] == 'Available')
					return false;
				else if ($result['error_code'] == 'DOMAIN_ALREADY_EXISTS')
					return true;

				$error = $result['error_code'];
			}
			else
			{
				$error = 'unknown';
			}
		}

		$error = self::getErrorCode($error);
		return null;
	}

	public static function suggestDomain($user, $password, $word1, $word2, $tlds, &$error)
	{
		$word1 = CharsetConverter::ConvertCharset($word1, SITE_CHARSET, 'UTF-8');
		$word2 = CharsetConverter::ConvertCharset($word2, SITE_CHARSET, 'UTF-8');
		foreach ($tlds as &$v)
			$v = CharsetConverter::ConvertCharset($v, SITE_CHARSET, 'UTF-8');

		$result = CMailRegru::suggestDomain($user, $password, $word1, $word2, $tlds, $error);

		if ($result !== false)
		{
			$suggestions = array();
			if (!empty($result['suggestions']) && is_array($result['suggestions']))
			{
				foreach ($result['suggestions'] as $entry)
				{
					foreach ($entry['avail_in'] as $tlds)
					{
						$suggestions[] = CharsetConverter::ConvertCharset(
							sprintf('%s.%s', $entry['name'], $tlds),
							'UTF-8', SITE_CHARSET
						);
					}
				}
			}

			return $suggestions;
		}

		$error = self::getErrorCode($error);
		return null;
	}

	public static function createDomain($user, $password, $domain, $params, &$error)
	{
		$domain = CharsetConverter::ConvertCharset($domain, SITE_CHARSET, 'UTF-8');

		$result = CMailRegru::createDomain($user, $password, $domain, array(
			'period'       => 1,
			'enduser_ip'   => $params['ip'],
			'profile_type' => $params['profile_type'],
			'profile_name' => $params['profile_name'],
			'nss'          => array(
				'ns0' => 'ns1.reg.ru.',
				'ns1' => 'ns2.reg.ru.'
			),
		), $error);

		if ($result !== false)
		{
			if (isset($result['dname']) && strtolower($result['dname']) == strtolower($domain))
				return true;
			else
				$error = 'unknown';
		}

		$error = self::getErrorCode($result['error_code']);
		return null;
	}

	public static function updateDns($user, $password, $domain, $params, &$error)
	{
		foreach ($params as &$record)
		{
			switch ($record['type'])
			{
				case 'cname':
					$record = array(
						'action'         => 'add_cname',
						'subdomain'      => $record['name'],
						'canonical_name' => $record['value']
					);
					break;
				case 'mx':
					$record = array(
						'action'      => 'add_mx',
						'subdomain'   => $record['name'],
						'mail_server' => $record['value'],
						'priority'    => $record['priority']
					);
					break;
			}
		}

		$result = CMailRegru::updateDns($user, $password, $domain, $params, $error);

		if ($result !== false)
		{
			if (isset($result['dname']) && strtolower($result['dname']) == strtolower($domain))
			{
				if (isset($result['result']) && $result['result'] == 'success')
					return true;
				else
					return false;
			}
			else
			{
				$error = 'unknown';
			}
		}

		$error = self::getErrorCode($result['error_code']);
		return null;
	}

	private static function getErrorCode($error)
	{
		$errorsList = array(
			'unknown'                      => CMail::ERR_API_DEFAULT,
			'INVALID_DOMAIN_NAME_PUNYCODE' => CMail::ERR_API_DEFAULT,
			'TLD_DISABLED'                 => CMail::ERR_API_DEFAULT,
			'DOMAIN_BAD_NAME'              => CMail::ERR_API_DEFAULT,
			'INVALID_DOMAIN_NAME_FORMAT'   => CMail::ERR_API_DEFAULT,
			'DOMAIN_INVALID_LENGTH'        => CMail::ERR_API_DEFAULT,
			'HAVE_MIXED_CODETABLES'        => CMail::ERR_API_DEFAULT
		);

		return array_key_exists($error, $errorsList) ? $errorsList[$error] : CMail::ERR_API_DEFAULT;
	}

}
