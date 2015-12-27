<?php
namespace Bitrix\Seo\Adv;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Seo\Engine;

/**
 * Class LogTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> ENGINE_ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> REQUEST_URI string(100) mandatory
 * <li> REQUEST_DATA string optional
 * <li> RESPONSE_TIME double mandatory
 * <li> RESPONSE_STATUS int optional
 * <li> RESPONSE_DATA string optional
 * </ul>
 *
 * @package Bitrix\Seo
 **/

class LogTable extends Entity\DataManager
{
	const TTL = 604800; // one week

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_seo_adv_log';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'ENGINE_ID' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'TIMESTAMP_X' => array(
				'data_type' => 'datetime',
				'required' => true,
			),
			'REQUEST_URI' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'REQUEST_DATA' => array(
				'data_type' => 'text',
				'required' => true,
			),
			'RESPONSE_TIME' => array(
				'data_type' => 'float',
				'required' => true,
			),
			'RESPONSE_STATUS' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'RESPONSE_DATA' => array(
				'data_type' => 'text',
				'required' => true,
			),
		);
	}

	public static function onBeforeAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult();
		$result->modifyFields(array("TIMESTAMP_X" => new DateTime()));
		return $result;
	}

	public static function clean()
	{
		$connection = Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();

		$tableName = static::getTableName();
		$deleteBorder = $sqlHelper->addSecondsToDateTime(
			-static::TTL,
			$sqlHelper->getCurrentDateTimeFunction()
		);

		$connection->queryExecute("DELETE FROM {$tableName} WHERE TIMESTAMP_X < {$deleteBorder}");

		return __CLASS__."::clean();";
	}
}
