<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo\Engine;

use Bitrix\Main\Text;
use Bitrix\Main\Web;
use Bitrix\Seo\Engine;
use Bitrix\Seo\Service;

class BitrixEngine extends Engine
{
	protected $engineId = 'bitrix_generic';

	static public function __construct()
	{
		parent::__construct();
	}

	static public function getProxy()
	{
		return Service::getEngine();
	}

	public function getAuthSettings()
	{
		$proxy = $this->getProxy();
		if($proxy && $proxy->getAuthSettings())
		{
			return parent::getAuthSettings();
		}

		return null;
	}
}