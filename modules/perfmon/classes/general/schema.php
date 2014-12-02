<?php

class CPerfomanceSchema
{
	var $data_relations = null;

	public function Init()
	{
		if (!isset($this->data_relations))
		{
			$this->data_relations = array();
			foreach (GetModuleEvents("perfmon", "OnGetTableSchema", true) as $arEvent)
			{
				$arModuleSchema = ExecuteModuleEventEx($arEvent);
				if (is_array($arModuleSchema))
				{
					foreach ($arModuleSchema as $module_id => $arModuleTables)
					{
						if (!array_key_exists($module_id, $this->data_relations))
							$this->data_relations[$module_id] = array();

						foreach ($arModuleTables as $parent_table_name => $arParentColumns)
						{
							if (!array_key_exists($parent_table_name, $this->data_relations[$module_id]))
								$this->data_relations[$module_id][$parent_table_name] = array();

							foreach ($arParentColumns as $parent_column => $arChildren)
							{
								if (!array_key_exists($parent_column, $this->data_relations[$module_id][$parent_table_name]))
									$this->data_relations[$module_id][$parent_table_name][$parent_column] = array();

								foreach ($arChildren as $child_table_name => $child_column)
								{
									$this->data_relations[$module_id][$parent_table_name][$parent_column][$child_table_name] = $child_column;
								}
							}
						}
					}
				}
			}
		}
	}

	public function GetChildren($table_name)
	{
		$this->Init();
		$result = array();
		foreach ($this->data_relations as $module_id => $arModuleTables)
		{
			if (array_key_exists($table_name, $arModuleTables))
				$key = $table_name;
			elseif (array_key_exists(strtolower($table_name), $arModuleTables))
				$key = strtolower($table_name);
			elseif (array_key_exists(strtoupper($table_name), $arModuleTables))
				$key = strtoupper($table_name);
			else
				$key = '';

			if ($key)
			{
				foreach ($arModuleTables[$key] as $parent_column => $arChildren)
				{
					foreach ($arChildren as $child_table_name => $child_column)
						$result[] = array(
							"PARENT_COLUMN" => $parent_column,
							"CHILD_TABLE" => trim($child_table_name, "^"),
							"CHILD_COLUMN" => $child_column,
						);
				}
			}
		}

		uasort($result, array("CPerfomanceSchema", "_sort"));
		return $result;
	}

	public function GetParents($table_name)
	{
		$this->Init();
		$result = array();
		foreach ($this->data_relations as $module_id => $arModuleTables)
		{
			foreach ($arModuleTables as $parent_table_name => $arParentColumns)
			{
				foreach ($arParentColumns as $parent_column => $arChildren)
				{
					foreach ($arChildren as $child_table_name => $child_column)
					{
						$child_table_name = trim($child_table_name, "^");
						if (
							$child_table_name === $table_name
							|| $child_table_name === strtolower($table_name)
							|| $child_table_name === strtoupper($table_name)
						)
							$result[$child_column] = array(
								"PARENT_TABLE" => $parent_table_name,
								"PARENT_COLUMN" => $parent_column,
							);
					}
				}
			}
		}

		uasort($result, array("CPerfomanceSchema", "_sort"));
		return $result;
	}

	private function _sort($a, $b)
	{
		if (isset($a["CHILD_TABLE"]))
			return strcmp($a["CHILD_TABLE"], $b["CHILD_TABLE"]);
		else
			return strcmp($a["PARENT_TABLE"], $b["PARENT_TABLE"]);
	}
}
