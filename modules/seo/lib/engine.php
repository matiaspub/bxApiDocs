<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage seo
 * @copyright 2001-2013 Bitrix
 */
namespace Bitrix\Seo;

abstract class Engine
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
		$dbEngine = SearchEngineTable::getByCode($this->engineId);
		$this->engine = $dbEngine->fetch();
		if(!is_array($this->engine))
		{
			throw new \Exception();
		}
		else
		{
			if(strlen($this->engine['SETTINGS']) > 0)
			{
				$this->engineSettings = unserialize($this->engine['SETTINGS']);
			}
		}
	}

	abstract public function getInterface();
	abstract public function getAuthSettings();
	abstract public function setAuthSettings($settings);
}
?>