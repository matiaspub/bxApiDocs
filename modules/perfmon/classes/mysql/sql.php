<?php

/*
if(!defined('LOG_FILENAME')) // define('LOG_FILENAME', $_SERVER["DOCUMENT_ROOT"]."/debug.trc");
if(CModule::IncludeModule('perfmon')) AddMessage2Log(CPerfomanceSQL::_console_explain($strSql));
*/

class CPerfomanceSQL extends CAllPerfomanceSQL
{
	public static function _console_explain($strSQL)
	{
		global $DB;
		$rs = $DB->Query("explain ".$strSQL);
		$arResult = array();
		while ($ar = $rs->Fetch())
			$arResult[] = $ar;

		$arColumnW = array();
		$arColumns = array('id', 'select_type', 'table', 'type', 'possible_keys', 'key_len', 'ref', 'rows', 'Extra');
		foreach ($arColumns as $name)
			$arColumnW[$name] = strlen($name);

		foreach ($arResult as $i => $ar)
		{
			foreach ($arColumns as $name)
			{
				if ($name == 'possible_keys')
				{
					$l = 0;
					$arResult[$i][$name] = explode(',', $ar[$name]);
					foreach ($arResult[$i][$name] as $j => $key)
						if ($arResult[$i]['key'] == $key && $key != '')
							$arResult[$i][$name][$j] = '*'.$arResult[$i][$name][$j];
						else
							$arResult[$i][$name][$j] = ' '.$arResult[$i][$name][$j];

					foreach ($arResult[$i][$name] as $key)
						if (strlen($key) > $l)
							$l = strlen($key);

					if ($arColumnW[$name] < $l)
						$arColumnW[$name] = $l;
				}
				elseif ($name == 'Extra')
				{
					$l = 0;
					$arResult[$i][$name] = array_map('trim', explode(';', $ar[$name]));
					foreach ($arResult[$i][$name] as $key)
						if (strlen($key) > $l)
							$l = strlen($key);

					if ($arColumnW[$name] < $l)
						$arColumnW[$name] = $l;
				}
				elseif ($arColumnW[$name] < strlen($ar[$name]))
					$arColumnW[$name] = strlen($ar[$name]);
			}
		}

		$arTable = array();

		$arTable['headers'] = array();
		foreach ($arColumns as $name)
			$arTable['headers'][] = str_pad($name, $arColumnW[$name], ' ', STR_PAD_RIGHT);

		$arTable['delim'] = array();
		foreach ($arColumns as $name)
			$arTable['delim'][] = str_repeat('-', $arColumnW[$name]);

		$i = 0;
		$j = 0;
		while ($i < count($arResult))
		{
			$arTable[$j] = array();
			$bNext = true;

			foreach ($arColumns as $name)
			{
				if ($name == 'key_len' || $name == 'rows')
					$pad = STR_PAD_LEFT;
				else
					$pad = STR_PAD_RIGHT;

				if (is_array($arResult[$i][$name]))
				{
					if (count($arResult[$i][$name]) > 1)
					{
						$arTable[$j][] = str_pad(array_shift($arResult[$i][$name]), $arColumnW[$name], ' ', $pad);
						$bNext = false;
					}
					else
					{
						$arTable[$j][] = str_pad(array_shift($arResult[$i][$name]), $arColumnW[$name], ' ', $pad);
						$arResult[$i][$name] = '';
					}
				}
				else
				{
					$arTable[$j][] = str_pad($arResult[$i][$name], $arColumnW[$name], ' ', $pad);
					$arResult[$i][$name] = '';
				}
			}

			if ($bNext)
				$i++;
			$j++;
		}

		$result = $strSQL."\n";
		foreach ($arTable as $row)
			$result .= implode('|', $row)."\n";
		$result .= implode('-', $arTable['delim'])."\n\n";

		return $result;
	}

	public static function Clear()
	{
		global $DB;
		$res = $DB->Query("TRUNCATE TABLE b_perf_sql_backtrace");
		if ($res)
			$res = $DB->Query("TRUNCATE TABLE b_perf_index_suggest_sql");
		if ($res)
			$res = $DB->Query("TRUNCATE TABLE b_perf_sql");
		return $res;
	}
}
