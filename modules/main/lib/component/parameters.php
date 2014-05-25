<?php
namespace Bitrix\Main\Component;

use Bitrix\Main\Entity\DataManager;

class ParametersTable
	extends DataManager
{
	const SEF_MODE = 'Y';
	const NOT_SEF_MODE = 'N';

	public static function getFilePath()
	{
		return __FILE__;
	}

	public static function getTableName()
	{
		return 'b_component_params';
	}

	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'COMPONENT_NAME' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'TEMPLATE_NAME' => array(
				'data_type' => 'string',
			),
			'REAL_PATH' => array(
				'data_type' => 'string',
				'required' => true,
			),
			'SEF_MODE' => array(
				'data_type' => 'boolean',
				'values' => array(self::SEF_MODE, self::NOT_SEF_MODE),
			),
			'SEF_FOLDER' => array(
				'data_type' => 'string',
			),
			'START_CHAR' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'END_CHAR' => array(
				'data_type' => 'integer',
				'required' => true,
			),
			'PARAMETERS' => array(
				'data_type' => 'text',
			),
		);
	}

	public static function deleteBySiteId($siteId)
	{
		if (empty($siteId))
			throw new \Bitrix\Main\ArgumentNullException("siteId");

		$result = new \Bitrix\Main\Entity\DeleteResult();

		// event PRE

		// delete
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		$tableName = static::getEntity()->getDBTableName();

		$sql = "DELETE FROM ".$tableName." WHERE SITE_ID = '".$helper->forSql($siteId)."'";
		$connection->queryExecute($sql);

		// event POST
		return $result;
	}

	public static function deleteByFilter($filter)
	{
		if (empty($filter))
			throw new \Bitrix\Main\ArgumentNullException("filter");

		$result = new \Bitrix\Main\Entity\DeleteResult();

		$dbResult = static::getList(
			array(
				"select" => array("ID"),
				"filter" => $filter,
			)
		);
		while ($ar = $dbResult->fetch())
			static::delete($ar["ID"]);

		return $result;
	}
}
