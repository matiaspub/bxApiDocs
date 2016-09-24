<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */

namespace Bitrix\Main\Authentication;

use Bitrix\Main;

class Application
{
	protected $validUrls = array();

	static public function __construct()
	{
	}

	/**
	 * Checks the valid scope for the applicaton.
	 *
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод проверяет валидность значений для приложения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/authentication/application/checkscope.php
	* @author Bitrix
	*/
	public function checkScope()
	{
		/** @var Main\HttpRequest $request */
		$request = Main\Context::getCurrent()->getRequest();
		$realPath = $request->getScriptFile();

		foreach($this->validUrls as $url)
		{
			if(strpos($realPath, $url) === 0)
			{
				return true;
			}
		}

		return false;
	}
}
