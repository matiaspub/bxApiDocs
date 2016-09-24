<?php
namespace Bitrix\Catalog\Helpers;

/**
 * Class Tools
 * Provides various useful methods for data.
 *
 * @package Bitrix\Catalog\Helpers
 */
class Tools
{
	/**
	 * Check existing keys in array.
	 *
	 * @param array $fields			Source array.
	 * @param array $keyList		Key list for search.
	 * @param bool $checkNull		Checked null values.
	 * @return bool
	 */
	public static function checkExistKeys(array $fields, array $keyList, $checkNull = false)
	{
		$result = false;
		if (empty($fields) || empty($keyList))
			return $result;

		$checkNull = ($checkNull === true);
		if (!$checkNull)
			$fields = array_filter($fields, '\Bitrix\Catalog\Helpers\Tools::clearNullFields');

		foreach ($keyList as &$key)
		{
			if (array_key_exists($key, $fields))
			{
				$result = true;
				break;
			}
		}
		unset($key);

		return $result;
	}

	/**
	 * Return missing key list.
	 *
	 * @param array $fields			Source array.
	 * @param array $keyList		Key list for search.
	 * @param bool $checkNull		Checked null values.
	 * @return array
	 */
	public static function getMissingKeys(array $fields, array $keyList, $checkNull = false)
	{
		$result = array();
		if (empty($keyList))
			return $result;
		if (empty($fields))
			return $keyList;

		$checkNull = ($checkNull === true);
		if (!$checkNull)
			$fields = array_filter($fields, '\Bitrix\Catalog\Helpers\Tools::clearNullFields');

		foreach ($keyList as &$key)
		{
			if (!array_key_exists($key, $fields))
				$result[] = $key;
		}
		unset($key);
		return $result;
	}

	/**
	 * Return keys status in fields.
	 *
	 * @param array $fields			Source array.
	 * @param array $keyList		Key list for search.
	 * @param bool $checkNull		Checked null values.
	 * @return array|bool
	 */
	public static function prepareKeys(array $fields, array $keyList, $checkNull = false)
	{
		$result = array(
			'EXIST' => array(),
			'MISSING' => array()
		);
		if (empty($keyList))
			return false;

		if (empty($fields))
		{
			$result['MISSING'] = $keyList;
			return $result;
		}

		$checkNull = ($checkNull === true);
		if (!$checkNull)
			$fields = array_filter($fields, '\Bitrix\Catalog\Helpers\Tools::clearNullFields');

		foreach ($keyList as &$key)
		{
			if (!array_key_exists($key, $fields))
				$result['MISSING'][] = $key;
			else
				$result['EXIST'][] = $key;
		}
		unset($key);
		return $result;
	}

	/**
	 * Callback for array_filter - clear null fields.
	 *
	 * @param mixed $value		Clear value.
	 * @return bool
	 */
	public static function clearNullFields($value)
	{
		return ($value !== null);
	}
}