<?php
namespace Bitrix\Main\Type;

class Collection
{
	/**
	 * Sorting array by column.
	 * You can use short mode: Collection::sortByColumn($arr, 'value'); This is equal Collection::sortByColumn($arr, array('value' => SORT_ASC))
	 *
	 * More example:
	 * Collection::sortByColumn($arr, array('value' => array(SORT_NUMERIC, SORT_ASC), 'attr' => SORT_DESC), array('attr' => 'strlen'), 'www');
	 *
	 * @param array        $array
	 * @param string|array $columns
	 * @param string|array $callbacks
	 * @param null         $defaultValueIfNotSetValue If value not set - use $defaultValueIfNotSetValue (any cols)
	 * @param bool         $preserveKeys If false numeric keys will be re-indexed. If true - preserve.
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function sortByColumn(array &$array, $columns, $callbacks = '', $defaultValueIfNotSetValue = null, $preserveKeys = false)
	{
		//by default: sort by ASC
		if (!is_array($columns))
		{
			$columns = array($columns => SORT_ASC);
		}
		$params = $preserveDataKeys = array();
		$alreadyFillPreserveDataKeys = false;
		foreach ($columns as $column => &$order)
		{
			$callback = null;
			//this is an array of callbacks (callable string)
			if(is_array($callbacks) && !is_callable($callbacks))
			{
				//if callback set for column
				if(!empty($callbacks[$column]))
				{
					$callback = is_callable($callbacks[$column])? $callbacks[$column] : false;
				}
			}
			//common callback
			elseif(!empty($callbacks))
			{
				$callback = is_callable($callbacks)? $callbacks : false;
			}

			if($callback === false)
			{
				throw new \Bitrix\Main\ArgumentOutOfRangeException('callbacks');
			}

			//this is similar to the index|slice
			$valueColumn[$column] = array();
			foreach ($array as $index => $row)
			{
				$value = isset($row[$column]) ? $row[$column] : $defaultValueIfNotSetValue;
				if ($callback)
				{
					$value = call_user_func_array($callback, array($value));
				}
				$valueColumn[$column][$index] = $value;
				if($preserveKeys && !$alreadyFillPreserveDataKeys)
				{
					$preserveDataKeys[$index] = $index;
				}
			}
			unset($row, $index);
			$alreadyFillPreserveDataKeys = $preserveKeys && !empty($preserveDataKeys);
			//bug in 5.3 call_user_func_array
			$params[] = &$valueColumn[$column];
			$order    = (array)$order;
			foreach ($order as $i => $ord)
			{
				$params[] = &$columns[$column][$i];
			}
		}
		unset($order, $column);
		$params[] = &$array;
		if($preserveKeys)
		{
			$params[] = &$preserveDataKeys;
		}

		call_user_func_array('array_multisort', $params);

		if($preserveKeys)
		{
			$array = array_combine(array_values($preserveDataKeys), array_values($array));
		}
	}

	/**
	 * Takes all arguments by pairs..
	 * Odd arguments are arrays.
	 * Even arguments ere keys to lookup in these arrays.
	 * Keys may be arrays. In this case function will try to dig deeper.
	 * Returns first not empty element of a[k] pair.
	 *
	 * @param array $a array to analyze
	 * @param string|int $k key to lookup
	 * @param mixed $a,... unlimited array/key pairs to go through
	 * @return mixed|string
	 */
	public static function firstNotEmpty()
	{
		$argCount = func_num_args();
		for ($i = 0; $i < $argCount; $i += 2)
		{
			$anArray = func_get_arg($i);
			$key = func_get_arg($i+1);
			if (is_array($key))
			{
				$current = &$anArray;
				$found = true;
				foreach ($key as $k)
				{
					if (!is_array($current) || !array_key_exists($k, $current))
					{
						$found = false;
						break;
					}
					$current = &$current[$k];
				}
				if ($found)
				{
					if (is_array($current) || is_object($current) || $current != "")
						return $current;
				}
			}
			elseif (is_array($anArray) && array_key_exists($key, $anArray))
			{
				if (is_array($anArray[$key]) || is_object($anArray[$key]) || $anArray[$key] != "")
					return $anArray[$key];
			}
		}
		return "";
	}

	/**
	 * convert array values to int, return unique values > 0. optionally sorted array
	 *
	 * @param array $map - array for normalize
	 * @param bool $sorted - if sorted true, result array will be sorted
	 * @return null
	 */
	public static function normalizeArrayValuesByInt(&$map, $sorted = true)
	{
		if (empty($map) || !is_array($map))
			return;

		$result = array();
		foreach ($map as $value)
		{
			$value = (int)$value;
			if (0 < $value)
				$result[$value] = true;
		}
		$map = array();
		if (!empty($result))
		{
			$map = array_keys($result);
			if ($sorted)
				sort($map);
		}
	}
}