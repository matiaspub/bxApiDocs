<?php
abstract class CAllBitrixCloudOption
{
	private $name = "";
	private $value = /*.(array[string]string).*/ null;
	/**
	 *
	 * @param string $name
	 * @return void
	 *
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}
	/**
	 *
	 * @return array[string]string
	 *
	 */
	private function _read_db()
	{
		global $DB;
		$result = /*.(array[string]string).*/ array();
		$rs = $DB->Query("
			select PARAM_KEY, PARAM_VALUE
			from b_bitrixcloud_option
			where NAME = '".$DB->ForSQL($this->name)."'
			order by SORT
		");
		while (is_array($ar = $rs->Fetch()))
		{
			$key = $ar["PARAM_KEY"];
			$result[$key] = $ar["PARAM_VALUE"];
		}
		return $result;
	}
	/**
	 *
	 * @return array[string][string]string
	 *
	 */
	private function _read_all_db()
	{
		global $DB;
		$result = /*.(array[string][string]string).*/ array();
		$rs = $DB->Query("
			select NAME, PARAM_KEY, PARAM_VALUE
			from b_bitrixcloud_option
			order by NAME, SORT
		");
		while (is_array($ar = $rs->Fetch()))
		{
			$name = $ar["NAME"];
			$key = $ar["PARAM_KEY"];
			$result[$name][$key] = $ar["PARAM_VALUE"];
		}
		return $result;
	}
	/**
	 *
	 * @return void
	 *
	 */
	private function _delete_db()
	{
		global $DB;
		$DB->Query("
			delete
			from b_bitrixcloud_option
			where NAME = '".$DB->ForSQL($this->name)."'
		");
	}
	/**
	 *
	 * @param array[string]string $value
	 * @return void
	 *
	 */
	private function _write_db($value)
	{
		global $DB;
		if (is_array($value))
		{
			$sort = 0;
			foreach ($value as $key => $val)
			{
				$DB->Add("b_bitrixcloud_option", array(
					"NAME" => $this->name,
					"SORT" => (string)$sort,
					"PARAM_KEY" => $key,
					"PARAM_VALUE" => $val,
				));
				$sort++;
			}
		}
	}
	/**
	 *
	 * @param array[string]string $value
	 * @return void
	 *
	 */
	private function _update_db($value)
	{
		global $DB;
		if (!is_array($value))
			$value = array();

		reset($value);
		$rs = $DB->Query("
			select ID, SORT, PARAM_KEY, PARAM_VALUE
			from b_bitrixcloud_option
			where NAME = '".$DB->ForSQL($this->name)."'
			order by ID
		");

		$sort = 0;
		while (list($key, $val) = each($value))
		{
			if ($db_row = $rs->fetch())
			{
				if (
					"".$db_row["PARAM_VALUE"]."" !== "".$val.""
					|| "".$db_row["PARAM_KEY"]."" !== "".$key.""
					|| "".$db_row["SORT"]."" !== "".$sort.""
				)
				{
					$DB->Query("
						UPDATE b_bitrixcloud_option SET
							PARAM_KEY = '".$DB->ForSql($key, 50)."'
							,PARAM_VALUE = '".$DB->ForSql($val, 200)."'
							,SORT = ".$sort."
						WHERE ID = ".$db_row["ID"]."
					");
				}
			}
			else
			{
				$DB->Add("b_bitrixcloud_option", array(
					"NAME" => $this->name,
					"SORT" => (string)$sort,
					"PARAM_KEY" => $key,
					"PARAM_VALUE" => $val,
				));
			}
			$sort++;
		}

		if ($db_row = $rs->fetch())
		{
			$DB->Query("
				DELETE FROM b_bitrixcloud_option
				WHERE NAME = '".$DB->ForSql($this->name, 50)."'
				AND ID >= ".$db_row["ID"]."
			");
		}
	}
	/**
	 *
	 * @return array[string]string
	 *
	 */
	public function getArrayValue()
	{
		global $CACHE_MANAGER;
		if (strlen($this->name) <= 0)
			return /*.(array[string]string).*/ array();

		if (!isset($this->value))
		{
			if (CACHED_b_bitrixcloud_option <= 0)
			{
				$this->value = $this->_read_db();
			}
			else
			{
				if (!$CACHE_MANAGER->Read(CACHED_b_bitrixcloud_option, "b_bitrixcloud_option"))
				{
					$arOptions = $this->_read_all_db();
					$CACHE_MANAGER->Set("b_bitrixcloud_option", $arOptions);
				}
				else
				{
					$arOptions = $CACHE_MANAGER->Get("b_bitrixcloud_option");
				}
				if (array_key_exists($this->name, $arOptions))
					$this->value = $arOptions[$this->name];
				else
					$this->value = /*.(array[string]string).*/ array();
			}
		}
		return $this->value;
	}
	/**
	 *
	 * @return string
	 *
	 */
	public function getStringValue()
	{
		$value = $this->getArrayValue();
		return (string)current($value);
	}
	/**
	 *
	 * @return int
	 *
	 */
	public function getIntegerValue()
	{
		$value = $this->getArrayValue();
		return (integer)current($value);
	}
	/**
	 * @param array[string]string $value
	 * @return void
	 *
	 */
	public function setArrayValue($value)
	{
		global $CACHE_MANAGER;
		if (strlen($this->name) > 0)
		{
			$stored = $this->getArrayValue();
			if ($stored !== $value)
			{
				$this->value = null;
				$this->_update_db($value);
				if (CACHED_b_bitrixcloud_option !== false)
					$CACHE_MANAGER->Clean("b_bitrixcloud_option");
			}
		}
	}
	/**
	 * @param string $value
	 * @return void
	 *
	 */
	public function setStringValue($value)
	{
		$this->setArrayValue(array(
			"0" => $value,
		));
	}
	/**
	 * @return void
	 *
	 */
	public function delete()
	{
		$this->setArrayValue(/*.(array[string]string).*/ array());
	}
}
