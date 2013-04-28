<?

// define("IP_DEFAULT_SOURCE_FILENAME", "/bitrix/modules/statistic/ip2country/ip-to-country.csv");
// define("IP_DEFAULT_SOURCE_TYPE", "ip-to-country.com");
// define("IP_DB_FILENAME", "/bitrix/modules/statistic/ip2country/ip-to-country.db");
// define("IP_IDX_FILENAME", "/bitrix/modules/statistic/ip2country/ip-to-country.idx");

function i2c_create_db(
	&$total_reindex,
	&$reindex_success,
	&$step_reindex,
	&$int_prev,
	$step=0,
	$file_name	= false,
	$file_type	= false,
	$file_db	= false,
	$file_idx	= false
	)
{
	$step = intval($step);

	if ($file_name===false)		$file_name	= IP_DEFAULT_SOURCE_FILENAME;
	if ($file_type===false)		$file_type	= IP_DEFAULT_SOURCE_TYPE;
	if ($file_db===false)		$file_db	= IP_DB_FILENAME;
	if ($file_idx===false)		$file_idx	= IP_IDX_FILENAME;

	$start = getmicrotime();

	$bExtTotal = is_array($total_reindex);
	if($bExtTotal)
	{
		$my_total_reindex = $total_reindex[0];
		$fseek = $total_reindex[1];
	}
	else
	{
		$fseek = $total_reindex;
		$my_total_reindex = $total_reindex;
	}

	if ($fp=fopen($_SERVER["DOCUMENT_ROOT"].$file_name,"rb"))
	{
		if ($fseek<=0) $mode = "wb"; else $mode = "ab";
		$f_db = fopen($_SERVER["DOCUMENT_ROOT"].$file_db,$mode);
		$f_idx = fopen($_SERVER["DOCUMENT_ROOT"].$file_idx,$mode);

		$d = 1000000;
		if ($fseek<=0)
		{
			fwrite($f_idx, $d."\n0,0\n");
		}

		$src_db_lines = 0;
		$step_reindex = 0;

		if($bExtTotal)
			fseek($fp, $fseek);

		while ($fp>0 && !feof($fp))
		{
			$arr=fgetcsv($fp,1000,",");
			if (is_array($arr) && $file_type=="maxmind.com" && !isset($beginIpNum))
			{
				while(list($key,$value)=each($arr))
				{
					$value = trim($value);
					if ($value=="beginIpNum" || $value=="endIpNum" || $value=="countryCode") ${$value} = $key;
				}
			}
			$src_db_lines++;
			if(!$bExtTotal)
			{
				if($fseek > 0 && $src_db_lines <= $fseek)
					continue;
			}

			if ($file_type=="maxmind.com")
			{
				$ix_beginIpNum = (!isset($beginIpNum)) ? 2 : intval($beginIpNum);
				$ip_from = TrimEx($arr[$ix_beginIpNum],"\"");

				$ix_endIpNum = (!isset($endIpNum)) ? 3 : intval($endIpNum);
				$ip_to = TrimEx($arr[$ix_endIpNum],"\"");

				$ip_to = (float) $ip_to;
				if ($ip_to<=0) continue;

				$ix_countryCode = (!isset($countryCode)) ? 4 : intval($countryCode);
				$country_id = TrimEx($arr[$ix_countryCode],"\"");
			}
			else
			{
				$ip_from = TrimEx($arr[0],"\"");
				$ip_to = TrimEx($arr[1],"\"");
				$country_id = TrimEx($arr[2],"\"");
			}
			if (strlen($country_id)<=0 && strlen($country_id)!=2) continue;

			$ip_from_p = str_pad($ip_from, 10, "0", STR_PAD_LEFT);
			$ip_to_p = str_pad($ip_to, 10, "0", STR_PAD_LEFT);
			fwrite($f_db, $ip_from_p.$ip_to_p.$country_id."\n");
			$step_reindex++;

			$int = floor($ip_from/$d);
			if ($int != $int_prev)
				fwrite($f_idx, $int.",".($my_total_reindex + $step_reindex)."\n");
			$int_prev = $int;

			if($step > 0 && (getmicrotime() - $start) > $step)
			{
				$reindex_success = "N";
				break;
			}
		}

		if ($reindex_success!="N")
			$reindex_success = "Y";

		if($bExtTotal)
		{
			$total_reindex[0] += $step_reindex;
			$total_reindex[1] = ftell($fp);
		}
		else
		{
			$total_reindex += $step_reindex;
		}

		fclose($fp);
		fclose($f_db);
		fclose($f_idx);
	}
}

function i2c_load_countries(
	$file_name	= false,
	$file_type	= false
	)
{
	$DB = CDatabase::GetModuleConnection('statistic');
	$err_mess = "FILE: ".__FILE__."<br>LINE: ";

	if ($file_name===false)		$file_name	= IP_DEFAULT_SOURCE_FILENAME;
	if ($file_type===false)		$file_type	= IP_DEFAULT_SOURCE_TYPE;

	if ($fp=fopen($_SERVER["DOCUMENT_ROOT"].$file_name,"rb"))
	{
		set_time_limit(0);
		ignore_user_abort(true);
		$arFields = Array(
			"NAME"			=> "'NA'",
			"SHORT_NAME"	=> "'N00'"
		);
		$rows = $DB->Update("b_stat_country",$arFields,"WHERE ID='N0'", $err_mess.__LINE__);
		if (intval($rows)<=0)
		{
			$strSql = "INSERT INTO b_stat_country (ID, SHORT_NAME, NAME) VALUES ('N0','N00','NA')";
			$DB->Query($strSql, false, $err_mess.__LINE__);
		}
		$arrUpdated = array();

		$bHeader = true;
		$bMaxMind = $file_type == "maxmind.com";
		if($bMaxMind)
		{
			$ix_countryCode = 5;
			$ix_countryName = 6;
		}
		else
		{
			$ix_countryCode = 2;
			$ix_countryName = 4;
		}

		while (!feof($fp))
		{
			$arr = fgetcsv($fp, 4096);
			if(is_array($arr) && (count($arr) > 0))
			{
				if($bMaxMind && $bHeader)
				{
					foreach($arr as $key => $value)
					{
						$value = trim($value);
						if ($value == "countryCode")
						{
							$bHeader = false;
							$ix_countryCode = intval($key);
						}
						elseif($value == "countryName")
						{
							$bHeader = false;
							$ix_countryName = intval($key);
						}
					}
				}

				//Check if was hanled with "dirty" $country_id
				if(array_key_exists($arr[$ix_countryCode], $arrUpdated))
					continue;

				$country_id = trim($arr[$ix_countryCode], "\"");

				if(!array_key_exists($country_id, $arrUpdated))
				{

					$country_name = trim($arr[$ix_countryName], "\"");
					if($bMaxMind)
					{
						if(strlen($country_id) != 2)
							continue;
						$country_short_name = "";
					}
					else
					{
						$country_short_name = trim($arr[3], "\"");
					}

					$arFields["NAME"] = "'".$DB->ForSql($country_name, 50)."'";
					$arFields["SHORT_NAME"] = "'".$DB->ForSql($country_short_name, 3)."'";

					$rows = $DB->Update("b_stat_country", $arFields, "WHERE ID='".$DB->ForSql(strtoupper($country_id), 2)."'", $err_mess.__LINE__);
					if(intval($rows)<=0 && strlen($country_id)>0 && strlen($country_name)>0)
					{
						$strSql = "
							INSERT INTO b_stat_country (ID, SHORT_NAME, NAME) VALUES (
								'".$DB->ForSql($country_id, 2)."',
								".$arFields["SHORT_NAME"].",
								".$arFields["NAME"]."
							)";
						$DB->Query($strSql, false, $err_mess.__LINE__);
					}
					$arrUpdated[$country_id] = true;
				}
			}
		}
		fclose($fp);
	}
}

function ip2number($dotted)
{
	return sprintf("%u", ip2long($dotted));
}

function ip2address($ip_number)
{
	$ip_number = (float) $ip_number;
	return long2ip($ip_number);
}

function i2c_get_country($ip=false)
{
	if($ip === false)
		$ip = $_SERVER['REMOTE_ADDR'];

	$ipn = (float) sprintf("%u", ip2long($ip));
	$idx = i2c_search_in_index($ipn);

	if($idx !== false)
		$country = i2c_search_in_db($ipn, $idx);
	else
		$country = 'N0';

	return $country;
}

function i2c_search_in_index($ip, $idx_name = IP_IDX_FILENAME)
{
	if(file_exists($_SERVER['DOCUMENT_ROOT'].$idx_name))
	{
		$dbidx = fopen($_SERVER['DOCUMENT_ROOT'].$idx_name,"rb");
		if($dbidx)
		{
			$granularity = intval(fgets($dbidx, 64));
			$ip_chunk = intval($ip / $granularity);
			$idxpart = 0;
			$recnum = 0;
			$prev_recnum = 0;

			while(!feof($dbidx))
			{
				$data = fgetcsv($dbidx, 100);
				if(is_array($data) && count($data))
				{
					if($ip_chunk >= $idxpart && $ip_chunk < intval($data[0]))
					{
						return array(
							$prev_recnum > 0? $prev_recnum: $recnum,
							intval($data[1])
						);
					}

					$prev_recnum = $recnum;
					$idxpart = intval($data[0]);
					$recnum  = intval($data[1]);
				}
			}

			return array(
				$prev_recnum > 0? $prev_recnum : $recnum,
				-1
			);
		}
	}
	return false;
}

function i2c_search_in_db($ip, $idx, $db_name=IP_DB_FILENAME)
{
	$range_start = 0;
	$range_end = 0;
	$country = "N0";
	$ipdb = fopen($_SERVER['DOCUMENT_ROOT'].$db_name,"rb");

	if (!$ipdb)
		return $country;

	$i1_size = 10;
	$i2_size = 10;
	$cn_size = 2;
	$record_size = $i1_size + $i2_size + $cn_size + 1;
	$seek = ($idx[0]*$record_size)-$record_size;
	fseek($ipdb, $seek);
	$ip = (float) $ip;
	while (!feof($ipdb) && !($range_start <= $ip && $range_end >= $ip))
	{
		if ($idx[1] != -1 && $idx[0] > $idx[1])
		{
			$country = "N0";
			break;
		}
		$record = fread($ipdb,$record_size);
		if (strlen($record)!=$record_size)
		{
			$country = "N0";
			break;
		}
		$range_start = (float) substr($record, 0, $i1_size);
		$range_end   = (float) substr($record, $i1_size, $i2_size);
		$country     = substr($record, $i1_size + $i2_size, $cn_size);
		$idx[0] += 1;
	}
	fclose($ipdb);
	return $country;
}

function get_realip()
{
	$ip = FALSE;
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
		for ($i = 0; $i < count($ips); $i++)
		{
			if (!preg_match("/^(10|172\\.16|192\\.168)\\./", $ips[$i]))
			{
				$ip = $ips[$i];
				break;
			}
		}
	}
	return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
?>