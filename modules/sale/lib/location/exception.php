<?
namespace Bitrix\Sale\Location;

use Bitrix\Main\SystemException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Exception extends SystemException
{
	protected $info = array();

	public function __construct($message = '', array $parameters = array())
	{
		if(isset($parameters['INFO']))
		{
			$this->info = $parameters['INFO'];
		}

		if($message === false)
		{
			$message = $this->getDefaultMessage();
		}

		if(!isset($parameters['FILE']))
		{
			$parameters['FILE'] = '';
		}
		$parameters['LINE'] = intval($parameters['LINE']);
		$parameters['CODE'] = intval($parameters['CODE']);
		if(!isset($parameters['PREVIOUS_EXCEPTION']))
		{
			$parameters['PREVIOUS_EXCEPTION'] = null;
		}

		parent::__construct($message, $parameters['CODE'], $parameters['FILE'], $parameters['LINE'], $parameters['PREVIOUS_EXCEPTION']);
	}

	public function getAdditionalInfo()
	{
		return $this->info;
	}

	static public function getDefaultMessage()
	{
		return '';
	}
}