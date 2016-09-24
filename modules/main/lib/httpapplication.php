<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\IO;
use Bitrix\Main\Security;

/**
 * Http application extends application. Contains http specific methods.
 */
class HttpApplication extends Application
{
	/**
	 * Creates new instance of http application.
	 */
	protected function __construct()
	{
		parent::__construct();
	}

	/**
	 * Initializes context of the current request.
	 *
	 * @param array $params Request parameters
	 */
	protected function initializeContext(array $params)
	{
		$context = new HttpContext($this);

		$server = new Server($params["server"]);

		$request = new HttpRequest(
			$server,
			$params["get"],
			$params["post"],
			$params["files"],
			$params["cookie"]
		);

		$response = new HttpResponse($context);

		$context->initialize($request, $response, $server, array('env' => $params["env"]));

		$this->setContext($context);
	}

	static public function createExceptionHandlerOutput()
	{
		return new Diag\HttpExceptionHandlerOutput();
	}

	/**
	 * Starts request execution. Should be called after initialize.
	 */
	
	/**
	* <p>Нестатический метод запускает выполнение запроса. Вызывается после инициализации.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httpapplication/start.php
	* @author Bitrix
	*/
	static public function start()
	{
		//register_shutdown_function(array($this, "finish"));
	}

	/**
	 * Finishes request execution.
	 * It is registered in start() and called automatically on script shutdown.
	 */
	
	/**
	* <p>Нестатический метод завершает выполнение запроса.</p> <p>Метод регистрируется в <a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/httpapplication/start.php">start</a> и вызывается автоматически при выполнении скрипта.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/httpapplication/finish.php
	* @author Bitrix
	*/
	public function finish()
	{
		//$this->managedCache->finalize();
	}
}
