<?php
IncludeModuleLangFile(__FILE__);

class CPerfCluster
{
	public static function Add($arFields)
	{
		global $DB;
		$ID = $DB->Add("b_perf_cluster", $arFields);
		return $ID;
	}

	public static function Truncate()
	{
		global $DB;
		$res = $DB->Query("DELETE FROM b_perf_cluster");
		return $res;
	}

	/**
	 * @param boolean|array[] $arOrder
	 * @param boolean|array[] $arFilter
	 * @param boolean|array[] $arSelect
	 *
	 * @return boolean|CDBResult
	 */
	public static function GetList($arOrder = false, $arFilter = false, $arSelect = false)
	{
		global $DB;

		if (!is_array($arSelect))
			$arSelect = array();
		if (count($arSelect) < 1)
			$arSelect = array(
				"ID",
				"TIMESTAMP_X",
				"THREADS",
				"HITS",
				"ERRORS",
				"PAGES_PER_SECOND",
				"PAGE_EXEC_TIME",
				"PAGE_RESP_TIME",
			);

		if (!is_array($arOrder))
			$arOrder = array();

		$arQueryOrder = array();
		foreach ($arOrder as $strColumn => $strDirection)
		{
			$strColumn = strtoupper($strColumn);
			$strDirection = strtoupper($strDirection) == "ASC"? "ASC": "DESC";
			switch ($strColumn)
			{
			case "ID":
				$arSelect[] = $strColumn;
				$arQueryOrder[$strColumn] = $strColumn." ".$strDirection;
				break;
			}
		}

		$arQuerySelect = array();
		foreach ($arSelect as $strColumn)
		{
			$strColumn = strtoupper($strColumn);
			switch ($strColumn)
			{
			case "ID":
			case "TIMESTAMP_X":
			case "THREADS":
			case "HITS":
			case "ERRORS":
			case "PAGES_PER_SECOND":
			case "PAGE_EXEC_TIME":
			case "PAGE_RESP_TIME":
				$arQuerySelect[$strColumn] = "p.".$strColumn;
				break;
			}
		}
		if (count($arQuerySelect) < 1)
			$arQuerySelect = array("ID" => "p.ID");

		$obQueryWhere = new CSQLWhere;
		$arFields = array(
			"ID" => array(
				"TABLE_ALIAS" => "p",
				"FIELD_NAME" => "p.ID",
				"FIELD_TYPE" => "int",
				"JOIN" => false,
			),
		);
		$obQueryWhere->SetFields($arFields);

		if (!is_array($arFilter))
			$arFilter = array();
		$strQueryWhere = $obQueryWhere->GetQuery($arFilter);

		$bDistinct = $obQueryWhere->bDistinctReqired;

		$strSql = "
			SELECT ".($bDistinct? "DISTINCT": "")."
			".implode(", ", $arQuerySelect)."
			FROM
				b_perf_cluster p
			".$obQueryWhere->GetJoins()."
		";

		if ($strQueryWhere)
		{
			$strSql .= "
				WHERE
				".$strQueryWhere."
			";
		}

		if (count($arQueryOrder) > 0)
		{
			$strSql .= "
				ORDER BY
				".implode(", ", $arQueryOrder)."
			";
		}

		return $DB->Query($strSql, false, '', array('fixed_connection' => true));
	}

	public function Measure($host, $port, $url, $threads, $iterations = 3, $arOptions = array())
	{
		$strRequest = "GET ".$url." HTTP/1.0\r\n";
		$strRequest .= "User-Agent: BitrixSMCluster (thread #thread#)\r\n";
		$strRequest .= "Accept: */*\r\n";
		$strRequest .= "Host: ".$host."\r\n";
		$strRequest .= "Accept-Language: en\r\n";

		$socket_timeout = intval($arOptions["socket_timeout"]);
		if ($socket_timeout <= 0)
			$socket_timeout = 20;

		$rw_timeout = intval($arOptions["rw_timeout"]);
		if ($rw_timeout <= 0)
			$rw_timeout = 20;

		$iteration_timeout = intval($arOptions["iteration_timeout"]);
		if ($iteration_timeout <= 0)
			$iteration_timeout = 30;

		if ($port == 443)
			$proto = "ssl://";
		else
			$proto = "";

		$start = getmicrotime();
		$end = $start + $iterations;
		$end_after_end = $start + $iteration_timeout;

		$errors = 0;
		$arConnections = array();
		$arCookie = array();
		$arStartTimes = array();
		$Pages = 0;
		$arPageExecTime = array();
		$arResponseTime = array();
		while (getmicrotime() < $end)
		{
			//Open new connection if needed
			if (count($arConnections) < $threads)
			{
				//Find first free slot
				for ($j = 0; $j < $threads; $j++)
				{
					if (!isset($arConnections[$j]))
					{
						$arStartTimes[$j] = getmicrotime();
						$socket = fsockopen($proto.$host, $port, $errno, $errstr, $socket_timeout);
						if ($socket)
						{
							$request = str_replace("#thread#", $j, $strRequest);
							if (isset($arCookie[$j]))
								$request .= "Cookie: ".implode(';', $arCookie[$j])."\n";
							$request .= "\r\n";

							stream_set_blocking($socket, true);
							stream_set_timeout($socket, $rw_timeout);
							fputs($socket, $request);
							stream_set_blocking($socket, false);
							$arConnections[$j] = $socket;
							$Pages++;
						}
						else
						{
							$arConnections[$j] = false;
							$errors++;
						}
						break;
					}
				}
			}

			//Try to read connections
			foreach ($arConnections as $j => $socket)
			{
				if ($socket)
				{
					if (feof($socket))
					{
						$arResponseTime[] = getmicrotime() - $arStartTimes[$j];
						fclose($socket);
						unset($arConnections[$j]);
					}
					else
					{
						$line = fgets($socket);
						if ($line !== false)
						{
							if (preg_match("/^Set-Cookie: (.*?)=(.*?);/", $line, $match))
							{
								$arCookie[$j][$match[1]] = $match[1].'='.$match[2];
							}
							elseif (preg_match("/<span id=\"bx_main_exec_time\">(\\d+\\.\\d+)<\\/span>/", $line, $match))
							{
								$arPageExecTime[] = $match[1];
							}
							elseif (preg_match("/^HTTP\\/\\d+\\.\\d+\\s+(\\d+)\\s/", $line, $match))
							{
								if ($match[1] !== '200')
									$errors++;
							}
							elseif (preg_match("/^Status:\\s+(\\d+)\\s/", $line, $match))
							{
								if ($match[1] !== '200')
									$errors++;
							}
						}
					}
				}
			}
		}

		//Finish all connections
		while (count($arConnections) > 0)
		{
			//Try to read connections
			foreach ($arConnections as $j => $socket)
			{
				if ($socket)
				{
					if (feof($socket))
					{
						$arResponseTime[] = getmicrotime() - $arStartTimes[$j];
						fclose($socket);
						unset($arConnections[$j]);
					}
					else
					{
						$line = fgets($socket);
						if ($line !== false)
						{
							if (preg_match("/<span id=\"bx_main_exec_time\">(\\d+\\.\\d+)<\\/span>/", $line, $match))
							{
								$arPageExecTime[] = $match[1];
							}
							elseif (preg_match("/^HTTP\\/\\d+\\.\\d+\\s+(\\d+)\\s/", $line, $match))
							{
								if ($match[1] !== '200')
									$errors++;
							}
							elseif (preg_match("/^Status:\\s+(\\d+)\\s/", $line, $match))
							{
								if ($match[1] !== '200')
									$errors++;
							}
						}
					}
				}
				else
				{
					unset($arConnections[$j]);
				}
				if (getmicrotime() > $end_after_end)
					break;
			}
			if (getmicrotime() > $end_after_end)
				break;
		}

		$this->Add(array(
			"THREADS" => $threads,
			"HITS" => $Pages,
			"ERRORS" => $errors,
			"PAGES_PER_SECOND" => $Pages / $iterations,
			"PAGE_EXEC_TIME" => count($arPageExecTime)? array_sum($arPageExecTime) / count($arPageExecTime): 0,
			"PAGE_RESP_TIME" => count($arResponseTime)? array_sum($arResponseTime) / count($arResponseTime): 0,
		));
	}
}
