<?php
namespace Bitrix\Scale;

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class Monitoring
 * @package Bitrix\Scale
 */
class Monitoring
{
	protected static $rrdPath = "/var/lib/munin";
	protected static $monitoringCategories = array();

	/**
	 * Checks if database files are created
	 * @return bool
	 */
	public static function isDatabaseCreated($hostname)
	{
		$dir = new \Bitrix\Main\IO\Directory(static::$rrdPath."/".$hostname);
		return $dir->isExists();
	}

	/**
	 * Checks if monitoring is enabled
	 * @return bool
	 */
	public static function isEnabled()
	{
		$result = false;
		$command = "sudo -u root /opt/webdir/bin/bx-monitor -o json";

		try
		{
			$action =  new Action("is_monitoring_enabled", array(
				"START_COMMAND_TEMPLATE" => $command,
				"LOG_LEVEL" => Logger::LOG_LEVEL_DISABLE
				), "", array());

			if(!$action->start())
			{
				return false;
			}
		}
		catch(\Exception $e)
		{
			return false;
		}

		$actRes = $action->getResult();
		if(isset($actRes["is_monitoring_enabled"]["OUTPUT"]["DATA"]["params"]["monitor"]["monitoring_status"]))
		{
			$result = ($actRes["is_monitoring_enabled"]["OUTPUT"]["DATA"]["params"]["monitor"]["monitoring_status"] == "enable");
		}
		else
		{
			$result = false;
		}

		return $result;
	}

	/**
	 * Returns value for server role loadbar (thermometr)
	 * @param $hostname
	 * @param $roleId
	 * @return bool|float
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Exception
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public static function getLoadBarValue($hostname, $roleId)
	{
		if (!extension_loaded('rrd'))
			throw new \Exception("Extension rrd not loaded!");

		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		if(strlen($roleId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("roleId");

		$role = RolesData::getRole($roleId);

		if(empty($role))
			throw new \Exception("Role with id = ".$roleId." was not defined.");

		if(!isset($role["LOADBAR_INFO"]) || strlen($role["LOADBAR_INFO"]) <= 0)
			throw new \Exception("Role ".$roleId." has no correctly defined LOADBAR_INFO param .");

		$rrdFile = str_replace('##HOSTNAME##', $hostname, $role["LOADBAR_INFO"]);
		$rrdPath = "/var/lib/munin/".$hostname."/".$rrdFile;
		$file = new \Bitrix\Main\IO\File($rrdPath);

		if(!$file->isExists())
			throw new \Bitrix\Main\IO\FileNotFoundException($rrdPath);

		$data = \rrd_lastupdate($rrdPath);

		$result = static::extractRrdValue($data);

		return $result;
	}

	public static function getInfoTableCategory($hostname, $categoryId)
	{
		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		if(strlen($categoryId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("paramId");

		$categories = self::getInfoTableCategoriesList($hostname);
		$result = array();

		if(isset($categories[$categoryId]))
			$result = $categories[$categoryId];

		return $result;
	}

	public static function getInfoTableCategoriesList($hostname)
	{
		$result = array();

		$result["HDD"] = array(
			"NAME" => Loc::getMessage("SCALE_MONITORING_HDD"),
			"PARAMS" => static::getHddsParams($hostname)
		);

		$result["NET"] = array(
			"NAME" => Loc::getMessage("SCALE_MONITORING_NET"),
			"PARAMS" => static::getNetParams($hostname)
		);

		$result["HDDACT"] = array(
			"NAME" => Helper::nbsp(Loc::getMessage("SCALE_MONITORING_HDDACT")),
			"PARAMS" => static::getHddsUtilization($hostname)
		);

		$result["MEMORY"] = array(
			"NAME" => Loc::getMessage("SCALE_MONITORING_MEMORY"),
			"PARAMS" => array(
				array(
					"NAME" => Loc::getMessage("SCALE_MONITORING_MEMORY_PARAMS"),
					"TYPE" => "ARRAY",
					"ITEMS" => array(
						array(
							"VALUE_FUNC" => '\Bitrix\Scale\Monitoring::getMemoryUsage',
							"FUNC_PARAMS" => array($hostname)
						),
						array(
							"VALUE_FUNC" => '\Bitrix\Scale\Monitoring::getMemoryUsageValue',
							"FUNC_PARAMS" => array($hostname),
							"TYPE"=>"LOADBAR"
						)
					)
				)
			)
		);

		$result["AVG_LOAD"] = array(
				"NAME" => Helper::nbsp(Loc::getMessage("SCALE_ITS_AVG_LOAD_NAME")),
				"PARAMS" => array(
					"CURR" => array(
						"NAME" => Loc::getMessage("SCALE_ITS_AVG_LOAD_CURR"),
						"RRD" => "##HOSTNAME##-load-load-g.rrd",
						"CF" => "LAST",
						"FORMAT" => "%2.2lf"
					),
					"MIN" => array(
						"NAME" => Loc::getMessage("SCALE_ITS_AVG_LOAD_MIN"),
						"RRD" => "##HOSTNAME##-load-load-g.rrd",
						"CF" => "MIN",
						"FORMAT" => "%2.2lf"
					),
					"MAX" => array(
						"NAME" => Loc::getMessage("SCALE_ITS_AVG_LOAD_MAX"),
						"RRD" => "##HOSTNAME##-load-load-g.rrd",
						"CF" => "MAX",
						"FORMAT" => "%2.2lf"
					)
				)
			);

		return $result;
	}

	public static function getValue($hostname, $categoryId, $param)
	{
		if(strlen($hostname) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("hostname");

		if(strlen($categoryId) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("categoryId");

		if(strlen($param) <= 0)
			throw new \Bitrix\Main\ArgumentNullException("param");

		$arCat = static::getInfoTableCategory($hostname, $categoryId);

		if(!$arCat)
			throw new \Exception("Monitoring category ".$categoryId." not exist.");

		if(!$arCat["PARAMS"][$param])
			throw new \Exception("Monitoring param ".$param." in category ".$categoryId." not exist.");

		$monParam = $arCat["PARAMS"][$param];

		if(isset($monParam["TYPE"]) && $monParam["TYPE"] == "ARRAY")
		{
			if(!isset($monParam["ITEMS"]) || !is_array($monParam["ITEMS"]))
				throw new \Exception("Monitoring param ".$param." in category ".$categoryId." hasn't field ITEMS.");

			$result = array();
			foreach($monParam["ITEMS"] as $item)
			{
				$result[] = static::getItemValue($hostname, $categoryId, $item, $param);
			}
		}
		else
		{
			$result = static::getItemValue($hostname, $categoryId, $monParam, $param);
		}

		return $result;
	}

	protected  static function getItemValue($hostname, $categoryId, $item, $param)
	{
		if(isset($item["VALUE"]))
			return $item["VALUE"];

		if(isset($item["VALUE_FUNC"]))
		{
			return call_user_func_array($item["VALUE_FUNC"], (isset($item["FUNC_PARAMS"]) ? $item["FUNC_PARAMS"] : array()));
		}

		if((!$item["RRD"] || !$item["CF"]) && !$item["OPTIONS"])
			throw new \Exception("Monitoring param item in category ".$categoryId." has no RRD or CF fields.");

		if(isset($item["RRD"]))
		{
			$rrdFile = str_replace('##HOSTNAME##', $hostname, $item["RRD"]);
			$rrdPath = static::$rrdPath."/".$hostname."/".$rrdFile;
			$file = new \Bitrix\Main\IO\File($rrdPath);

			if(!$file->isExists())
				throw new \Bitrix\Main\IO\FileNotFoundException($rrdPath);

			$first = \rrd_first($rrdPath);
			$last = \rrd_last($rrdPath);
		}

		if(isset($item["OPTIONS"]))
		{
			$arOpts = $item["OPTIONS"];
			$arOpts = str_replace('##HOSTNAME##', $hostname, $arOpts);
		}
		else
		{
			if($item["CF"] == "MIN")
			{
				$agr = "MINIMUM";
			}
			elseif($item["CF"] == "MAX")
			{
				$agr = "MAXIMUM";
			}
			else
			{
				$agr = $item["CF"];
			}

			if($item["CF"] == "LAST")
				$item["CF"] = "AVERAGE";

			$format = isset($item["FORMAT"]) ? $item["FORMAT"] : "%6.2lf";

			$arOpts = array(
				"DEF:val=".$rrdPath.":42:".$item["CF"],
				"VDEF:vval=val,".$agr,
				"PRINT:vval:".$format);
		}

		if(isset($item["RRD"]))
		{
			$arOpts[] = "--start";
			$arOpts[] = $first;
			$arOpts[] =  "--end";
			$arOpts[] = $last;
		}

		$data = \rrd_graph( "/dev/null", $arOpts);

		if(isset($item["DATA_FUNC"]))
		{
			$func = create_function('$data', $item["DATA_FUNC"]);

			if(is_callable($func))
			{
				$result = $func($data);
			}
		}
		else
		{
			if(isset($data["calcpr"]))
			{
				$data["data"] = $data["calcpr"];
			}

			$result = static::extractRrdValue($data);
		}

		return $result;
	}

	protected static function extractRrdValue($data)
	{
		$result = false;

		if(isset($data["data"]) && is_array($data["data"]))
		{
			reset($data["data"]);
			$result = current($data["data"]);
		}

		return trim($result);
	}

	protected static function getAnsibleSetup($hostname)
	{
		static $info = array();

		if(!isset($info[$hostname]))
		{
			$shellAdapter = new ShellAdapter();
			$execRes = $shellAdapter->syncExec("sudo -u root /usr/bin/ansible ".$hostname." -m setup");
			$serversData = $shellAdapter->getLastOutput();
			$pos1 = strpos($serversData, ">>");
			$serversData = substr($serversData, $pos1+3-strlen($serversData));

			if($execRes)
			{
				$info[$hostname] = json_decode($serversData, true);
			}
			else
			{
				$info[$hostname] = array();
			}
		}

		return $info[$hostname];
	}

	protected static function getHddsParams($hostname)
	{
		$result = array();
		$arData = static::getAnsibleSetup($hostname);

		if(isset($arData["ansible_facts"]["ansible_mounts"]))
		{
			foreach($arData["ansible_facts"]["ansible_mounts"] as $mountId => $mount)
			{
				$result[$mountId] = array(
					"NAME" => $mount["device"]." ".Loc::getMessage("SCALE_MONITORING_HDD_PARAMS"),
					"TYPE" => "ARRAY",
					"ITEMS" => array(
						array(
							"VALUE_FUNC" => '\Bitrix\Scale\Monitoring::getHddsValues',
							"FUNC_PARAMS" => array(
								$hostname,
								$mountId
							)
						),
						array(
							"TYPE" => "LOADBAR",
							"VALUE_FUNC" => '\Bitrix\Scale\Monitoring::getHddsUsed',
							"FUNC_PARAMS" => array(
								$hostname,
								$mountId
							)

						)
					)
				);
			}
		}

		return $result;
	}

	protected static function getHddsUsed($hostname, $param)
	{
		$arData = static::getAnsibleSetup($hostname);

		if(isset($arData["ansible_facts"]["ansible_mounts"][$param]["size_total"]) && isset($arData["ansible_facts"]["ansible_mounts"][$param]["size_available"]))
		{
			$mount = $arData["ansible_facts"]["ansible_mounts"][$param];
			$result = ($mount["size_total"]-$mount["size_available"])/$mount["size_total"]*100;
		}
		else
		{
			$result = "0";
		}

		return $result;
	}

	protected static function getHddsValues($hostname, $param)
	{
		$arData = static::getAnsibleSetup($hostname);

		if(isset($arData["ansible_facts"]["ansible_mounts"][$param]["size_total"]) && isset($arData["ansible_facts"]["ansible_mounts"][$param]["size_available"]))
		{
			$mount = $arData["ansible_facts"]["ansible_mounts"][$param];

			$result =  static::formatSize($mount["size_total"], 2)."&nbsp;/&nbsp;".
				static::formatSize(($mount["size_total"]-$mount["size_available"]), 2)."&nbsp;/&nbsp;".
				static::formatSize($mount["size_available"], 2);
		}
		else
		{
			$result = "0";
		}

		return $result;
	}

	protected static function getNetParams($hostname)
	{
		$dir = new \Bitrix\Main\IO\Directory(static::$rrdPath."/".$hostname);

		if(!$dir->isExists())
			return array();

		$arChildren = $dir->getChildren();
		$result = array();
		
		foreach ($arChildren as $child)
		{
			if(!$child->isFile())
				continue;

			$name = $child->getName();
			$pos1 = strpos($name, "-if_");
			$pos2 = strpos($name, "-up-");

			if($pos1 !== false && $pos2 !== false)
			{
				$pos1 += 4;
				$dev = substr($name, $pos1, $pos2-$pos1);

				$result[$dev] = array(
					"NAME" => $dev." ".Loc::getMessage("SCALE_MONITORING_NET_PARAMS"),
					"RRD" => $hostname."-if_".$dev."-up-d.rrd",
					"OPTIONS" => array(
						"DEF:in=".static::$rrdPath."/".$hostname."/".$hostname."-if_".$dev."-up-d.rrd:42:AVERAGE:start=now-600;end=now",
						"DEF:out=".static::$rrdPath."/".$hostname."/".$hostname."-if_".$dev."-down-d.rrd:42:AVERAGE:start=now-600;end=now",
						"VDEF:vin=in,TOTAL",
						"VDEF:vout=out,TOTAL",
						"PRINT:vin:%1.2lf",
						"PRINT:vout:%1.2lf"
					),
					"DATA_FUNC" => '
					$result = false;
					if(isset($data["calcpr"][0]) && isset($data["calcpr"][1]))
					{
						$result = \Bitrix\Scale\Monitoring::formatSize($data["calcpr"][0]/600).
							"&nbsp;/&nbsp;".
							\Bitrix\Scale\Monitoring::formatSize($data["calcpr"][1]/600)."&nbsp;'.Helper::nbsp(Loc::getMessage("SCALE_MONITORING_NET_SEC")).'";
					}
					return $result;'
				);
			}
		}

		return $result;
	}

	protected static function getHddsUtilization($hostname)
	{
		$dir = new \Bitrix\Main\IO\Directory(static::$rrdPath."/".$hostname);

		if(!$dir->isExists())
			return array();

		$arChildren = $dir->getChildren();
		$result = array();

		foreach ($arChildren as $child)
		{
			if(!$child->isFile())
				continue;

			$name = $child->getName();
			$pos1 = strpos($name, "-diskstats_utilization-");
			$pos2 = strpos($name, "-util-");
			if($pos1 !== false && $pos2 !== false)
			{
				$pos1 += 23; //strlen("-diskstats_utilization-")
				$dev = substr($name, $pos1, $pos2-$pos1);

				$result[$dev] = array(
					"NAME" => $dev." ".Loc::getMessage("SCALE_MONITORING_HDDACT_PARAMS"),
					"TYPE" => "ARRAY",
					"ITEMS" => array(
						array(
							"OPTIONS" => array(
								"DEF:r=".static::$rrdPath."/".$hostname."/".$hostname."-diskstats_throughput-".$dev."-rdbytes-g.rrd:42:AVERAGE:start=now-600;end=now",
								"DEF:w=".static::$rrdPath."/".$hostname."/".$hostname."-diskstats_throughput-".$dev."-wrbytes-g.rrd:42:AVERAGE:start=now-600;end=now",
								"VDEF:vr=r,TOTAL",
								"VDEF:vw=w,TOTAL",
								"PRINT:vr:%1.2lf",
								"PRINT:vw:%1.2lf"
							),
							"DATA_FUNC" => '
								$result = false;
								if(isset($data["calcpr"][0]) && isset($data["calcpr"][1]))
								{
									$result = \Bitrix\Scale\Monitoring::formatSize($data["calcpr"][0]/600).
										"&nbsp;/&nbsp;".
										\Bitrix\Scale\Monitoring::formatSize($data["calcpr"][1]/600)."&nbsp;'.Loc::getMessage("SCALE_MONITORING_NET_SEC").'";
								}
								return $result;'
						),
						array(
							"RRD" => $hostname."-diskstats_utilization-".$dev."-util-g.rrd",
							"CF" => "LAST",
							"FORMAT" => "%2.2lf",
							"TYPE" => "LOADBAR"
						),
					)
				);
			}
		}

		return $result;
	}

	/**
	 * @param float $size
	 * @param int $precision
	 * @return string
	 */
	public static function formatSize($size, $precision = 2)
	{
		static $a = array("b", "Kb", "Mb", "Gb", "Tb");
		$pos = 0;
		while($size >= 1024 && $pos < 4)
		{
			$size /= 1024;
			$pos++;
		}
		return round($size, $precision)."&nbsp;".$a[$pos];
	}

	protected static function getMemoryUsage($hostname)
	{
		$result = "0";
		$arData = static::getAnsibleSetup($hostname);

		if(isset($arData["ansible_facts"]["ansible_memtotal_mb"]) && isset($arData["ansible_facts"]["ansible_memfree_mb"]))
			$result = $arData["ansible_facts"]["ansible_memtotal_mb"]." / ".(intval($arData["ansible_facts"]["ansible_memtotal_mb"])-intval($arData["ansible_facts"]["ansible_memfree_mb"]))." / ".$arData["ansible_facts"]["ansible_memfree_mb"]." Mb";

		return $result;
	}

	protected static function getMemoryUsageValue($hostname)
	{
		$result = "0";
		$arData = static::getAnsibleSetup($hostname);

		if(isset($arData["ansible_facts"]["ansible_memtotal_mb"]) && isset($arData["ansible_facts"]["ansible_memfree_mb"]) && intval($arData["ansible_facts"]["ansible_memtotal_mb"]) != 0)
			$result = (intval($arData["ansible_facts"]["ansible_memtotal_mb"]) - intval($arData["ansible_facts"]["ansible_memfree_mb"]))/intval($arData["ansible_facts"]["ansible_memtotal_mb"])*100;

		return $result;
	}
}