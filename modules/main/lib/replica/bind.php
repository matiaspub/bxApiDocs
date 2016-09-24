<?php
namespace Bitrix\Main\Replica;

class Bind
{
	/**
	 * Initializes replication process on main side.
	 *
	 * @return void
	 */
	
	/**
	* <p>Нестатический метод инициализирует процесс репликации на стороне главного модуля.</p> <p>Без параметров</p> <a name="example"></a>
	*
	*
	* @return void 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/replica/bind/start.php
	* @author Bitrix
	*/
	static public function start()
	{
		\Bitrix\Replica\Client\HandlersManager::register(new UrlMetadataHandler());
	}
}
