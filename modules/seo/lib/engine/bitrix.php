<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Seo\Engine;

use Bitrix\Main\Loader;
use Bitrix\Seo\Engine;
use Bitrix\Seo\IEngine;

if(!defined("BITRIX_CLOUD_ADV_URL"))
{
	// define("BITRIX_CLOUD_ADV_URL", 'http://cloud-adv.bitrix.info');
}

if(!defined("SEO_BITRIX_API_URL"))
{
	// define("SEO_BITRIX_API_URL", BITRIX_CLOUD_ADV_URL."/rest/");
}

class Bitrix extends Engine implements IEngine
{
	const ENGINE_ID = 'bitrix';

	protected $engineId = 'bitrix';
	protected $engineRegistered = false;

	CONST API_URL = SEO_BITRIX_API_URL;

	public function __construct()
	{
		$this->engine = static::getEngine($this->engineId);
		if($this->engine)
		{
			$this->engineRegistered = true;
			parent::__construct();
		}
	}

	/**
	 * Checks if domain is registered.
	 *
	 * @return bool
	 */
	public function isRegistered()
	{
		return $this->engineRegistered;
	}

	public function getInterface()
	{
		if($this->authInterface === null)
		{
			if(Loader::includeModule('socialservices'))
			{
				$this->authInterface = new \CBitrixSeoOAuthInterface($this->engine['CLIENT_ID'], $this->engine['CLIENT_SECRET']);

				if($this->engineSettings['AUTH'])
				{
					$this->authInterface->setToken($this->engineSettings['AUTH']['access_token']);
					$this->authInterface->setRefreshToken($this->engineSettings['AUTH']['refresh_token']);
					$this->authInterface->setAccessTokenExpires($this->engineSettings['AUTH']['expires_in']);
				}
			}
		}

		return $this->authInterface;
	}

	public function setAuthSettings($settings = null)
	{
		if(is_array($settings) && array_key_exists("expires_in" ,$settings))
		{
			$settings["expires_in"] += time();
		}

		$this->engineSettings['AUTH'] = $settings;
		$this->saveSettings();
	}
}