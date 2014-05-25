<?php
namespace Bitrix\Scale;

/**
 * Class RolesData
 * @package Bitrix\Scale
 */
class RolesData
{
	/**
	 * Returns role defenition
	 * @param string $roleId
	 * @return array role params
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getRole($roleId)
	{
		if(strlen($roleId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("roleId");

		$rolesDefinitions = self::getList();
		$result = array();

		if(isset($rolesDefinitions[$roleId]))
			$result = $rolesDefinitions[$roleId];

		return $result;
	}

	/**
	 * @return array All roles defenitions
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getList()
	{
		static $def = null;

		if($def == null)
		{
			$filename = \Bitrix\Main\Application::getDocumentRoot()."/bitrix/modules/scale/include/rolesdefinitions.php";
			$file = new \Bitrix\Main\IO\File($filename);

			if($file->isExists())
				require_once($filename);
			else
				throw new \Bitrix\Main\IO\FileNotFoundException($filename);

			if(isset($rolesDefinitions))
				$def = $rolesDefinitions;
			else
				$def = array();
		}

		return $def;
	}

	/**
	 * @param string $roleId
	 * @return array graphs
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getGraphsCategories($roleId)
	{
		if(strlen($roleId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("roleId");

		$result = array();
		$role = static::getRole($roleId);

		if(isset($role["GRAPH_CATEGORIES"]))
			$result = $role["GRAPH_CATEGORIES"];

		return $result;
	}
}