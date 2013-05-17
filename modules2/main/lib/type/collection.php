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
					$value = $callback($value);
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
}