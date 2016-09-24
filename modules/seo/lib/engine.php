<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Seo;

use Bitrix\Main\SystemException;
use Bitrix\Seo\SearchEngineTable;

class Engine
{
	const HTTP_STATUS_OK = 200;
	const HTTP_STATUS_CREATED = 201;
	const HTTP_STATUS_NO_CONTENT = 204;
	const HTTP_STATUS_AUTHORIZATION = 401;

	protected $engineId = 'unknown engine';

	protected $engine = null;
	protected $engineSettings = array();

	protected $authInterface = null;

	public function __construct()
	{
		if(!$this->engine)
		{
			$this->engine = static::getEngine($this->engineId);
		}

		if(!is_array($this->engine))
		{
			throw new SystemException("Unknown search engine");
		}
		else
		{
			if(strlen($this->engine['SETTINGS']) > 0)
			{
				$this->engineSettings = unserialize($this->engine['SETTINGS']);
			}
		}
	}

	public function getId()
	{
		return $this->engine['ID'];
	}

	public function getCode()
	{
		return $this->engine['CODE'];
	}

	public function getSettings()
	{
		return $this->engineSettings;
	}

	public function getClientId()
	{
		return $this->engine['CLIENT_ID'];
	}

	public function getClientSecret()
	{
		return $this->engine['CLIENT_SECRET'];
	}

	public function getAuthSettings()
	{
		return $this->engineSettings['AUTH'];
	}

	public function clearAuthSettings()
	{
		unset($this->engineSettings['AUTH']);
		$this->saveSettings();
	}

	protected function saveSettings()
	{
		SearchEngineTable::update($this->engine['ID'], array(
			'SETTINGS' => serialize($this->engineSettings)
		));
	}

	protected static function getEngine($engineId)
	{
		$dbEngine = SearchEngineTable::getByCode($engineId);
		return $dbEngine->fetch();
	}
}

