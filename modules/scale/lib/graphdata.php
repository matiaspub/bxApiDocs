<?php
namespace Bitrix\Scale;

/**
 * Class GraphData
 * @package Bitrix\Scale
 */
class GraphData
{
	/**
	 * Returns graphics definition
	 * @param string $graphCategory
	 * @return array
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getGraphs($graphCategory)
	{
		if(strlen($graphCategory) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("graphCategory");

		$graphics = self::getList();
		$result = array();

		if(isset($graphics[$graphCategory]))
			$result = $graphics[$graphCategory];

		return $result;
	}

	/**
	 * @return array All graphics
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getList()
	{
		static $def = null;

		if($def == null)
		{
			$filename = \Bitrix\Main\Application::getDocumentRoot()."/bitrix/modules/scale/include/graphdefinitions.php";
			$file = new \Bitrix\Main\IO\File($filename);

			if($file->isExists())
				require_once($filename);
			else
				throw new \Bitrix\Main\IO\FileNotFoundException($filename);

			if(isset($graphics))
				$def = $graphics;
			else
				$def = array();
		}

		return $def;
	}
}