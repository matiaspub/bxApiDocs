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
	// define("BITRIX_CLOUD_ADV_URL", 'https://cloud-adv.bitrix.info');
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
	
	/**
	* <p>Метод проверяет, зарегистрирован ли домен. Метод нестатический.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/seo/engine/bitrix/isregistered.php
	* @author Bitrix
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