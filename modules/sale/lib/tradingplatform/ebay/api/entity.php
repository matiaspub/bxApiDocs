<?php
/**
 * Created by PhpStorm.
 * User: wasil
 * Date: 11.09.14
 * Time: 14:47
 */

namespace Bitrix\Sale\TradingPlatform\Ebay\Api;

use Bitrix\Main\SystemException;
use Bitrix\Sale\TradingPlatform\Ebay\Ebay;
use Bitrix\Main\ArgumentNullException;

abstract class Entity
{
	protected $siteId;
	protected $apiCaller;
	protected $authToken;
	protected $warningLevel = "High";

	public function __construct($siteId)
	{
		if(!isset($siteId))
			throw new ArgumentNullException("siteId");

		$this->siteId = $siteId;
		$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
		$settings = $ebay->getSettings();

		if(empty($settings[$siteId]["API"]["SITE_ID"]))
			throw new SystemException("EBAY API SITE_ID is not defined!");

		if(empty($settings[$siteId]["API"]["SITE_ID"]))
			throw new ArgumentNullException("EBAY AUTH_TOKEN is not defined!");

		$this->ebaySiteId = $settings[$siteId]["API"]["SITE_ID"];
		$this->authToken = $settings[$siteId]["API"]["AUTH_TOKEN"];
		$this->apiCaller = new Caller( array(
			"EBAY_SITE_ID" => $settings[$siteId]["API"]["SITE_ID"],
			"URL" => $ebay->getApiUrl(),
		));
	}

	protected function array2Tags(array $params)
	{
		$result = "";

		foreach($params as $tag => $value)
		{
			if(is_array($value))
			{
				reset($value);

				if(key($value) !== 0)
				{
					$result .= $this->array2Tags($value);
				}
				else
				{
					foreach($value as $val)
					{
						$result .= '<'.$tag.'>'.$val.'</'.$tag.'>'."\n";
					}
				}
			}
			elseif(strlen($value) > 0)
			{
				$result .= '<'.$tag.'>'.$value.'</'.$tag.'>'."\n";
			}
		}

		return $result;
	}
} 