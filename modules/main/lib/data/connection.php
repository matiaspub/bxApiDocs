<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2014 Bitrix
 */

namespace Bitrix\Main\Data;

/**
 * Class Connection
 *
 * Abstarct base class for data connections.
 */
abstract class Connection
{
	/** @var resource */
	protected $resource;
	protected $isConnected = false;
	protected $configuration;

	public function __construct(array $configuration)
	{
		$this->configuration = $configuration;
	}

	/**
	 * Connects to data source.
	 */
	
	/**
	* <p>Нестатический метод производит соединение с источником данных.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connection/connect.php
	* @author Bitrix
	*/
	public function connect()
	{
		$this->isConnected = false;

		$this->connectInternal();
	}

	/**
	 * Disconects from data source.
	 */
	
	/**
	* <p>Нестатический метод производит отключение от источника данных.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return public 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connection/disconnect.php
	* @author Bitrix
	*/
	public function disconnect()
	{
		$this->disconnectInternal();
	}

	/**
	 * Returns the resource of the connection.
	 *
	 * @return resource
	 */
	
	/**
	* <p>Нестатический метод возвращает ресурсы соединения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return resource 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connection/getresource.php
	* @author Bitrix
	*/
	public function getResource()
	{
		$this->connectInternal();
		return $this->resource;
	}

	/**
	 * Returns the state of the connection.
	 *
	 * @return bool
	 */
	
	/**
	* <p>Нестатический метод возвращает состояние соединения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connection/isconnected.php
	* @author Bitrix
	*/
	public function isConnected()
	{
		return $this->isConnected;
	}

	abstract protected function connectInternal();
	abstract protected function disconnectInternal();

	/**
	 * Returns the array with the connection parameters.
	 *
	 * @return array
	 */
	
	/**
	* <p>Нестатический метод возвращает массив с параметрами соединения.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return array 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/data/connection/getconfiguration.php
	* @author Bitrix
	*/
	public function getConfiguration()
	{
		return $this->configuration;
	}
}
