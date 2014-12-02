<?
IncludeModuleLangFile(__FILE__);

class CClusterMemcache
{
	public static $systemConfigurationUpdate = null;
	private static $arList = false;

	public static function LoadConfig()
	{
		if(self::$arList === false)
		{
			$arList = false;
			if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php"))
				include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php");

			if(defined("BX_MEMCACHE_CLUSTER") && is_array($arList))
				self::$arList = $arList;
			else
				self::$arList = array();

		}
		return self::$arList;
	}

	public static function SaveConfig($arServerList)
	{
		self::$arList = false;
		$isOnline = false;

		$content = '<'.'?
// define("BX_MEMCACHE_CLUSTER", "'.EscapePHPString(CMain::GetServerUniqID()).'");
$arList = array(
';
		$defGroup = 1;
		$arGroups = array();
		$rsGroups = CClusterGroup::GetList(array("ID" => "DESC"));
		while($arGroup = $rsGroups->Fetch())
			$defGroup = $arGroups[$arGroup["ID"]] = intval($arGroup["ID"]);

		foreach($arServerList as $i => $arServer)
		{
			$isOnline |= ($arServer["STATUS"] == "ONLINE");

			$GROUP_ID = intval($arServer["GROUP_ID"]);
			if(!array_key_exists($arServer["GROUP_ID"], $arGroups))
				$GROUP_ID = $defGroup;

			$content .= "\t".intval($i)." => array(\n";
			$content .= "\t\t'ID' => \"".EscapePHPString($arServer["ID"])."\",\n";
			$content .= "\t\t'GROUP_ID' => ".$GROUP_ID.",\n";
			$content .= "\t\t'HOST' => \"".EscapePHPString($arServer["HOST"])."\",\n";
			$content .= "\t\t'PORT' => ".intval($arServer["PORT"]).",\n";
			$content .= "\t\t'WEIGHT' => ".intval($arServer["WEIGHT"]).",\n";
			if($arServer["STATUS"] == "ONLINE")
				$content .= "\t\t'STATUS' => \"ONLINE\",\n";
			elseif($arServer["STATUS"] == "OFFLINE")
				$content .= "\t\t'STATUS' => \"OFFLINE\",\n";
			else
				$content .= "\t\t'STATUS' => \"READY\",\n";
			$content .= "\t),\n";
		}

		$content .= ');
?'.'>';
		file_put_contents(
			$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/cluster/memcache.php"
			,$content
		);
		bx_accelerator_reset();

		self::$systemConfigurationUpdate = null;
		$cache = \Bitrix\Main\Config\Configuration::getValue('cache');
		if ($isOnline)
		{
			if (
				!is_array($cache)
				|| !isset($cache['type'])
				|| !is_array($cache['type'])
				|| !isset($cache['type']['class_name'])
				|| !$cache['type']['class_name'] === 'CPHPCacheMemcacheCluster'
			)
			{
				\Bitrix\Main\Config\Configuration::setValue('cache', array(
					'type' => array(
						'class_name' => 'CPHPCacheMemcacheCluster',
						'extension' => 'memcache',
						'required_file' => 'modules/cluster/classes/general/memcache_cache.php',
					),
				));
				self::$systemConfigurationUpdate = true;
			}
		}
		else
		{
			if (
				is_array($cache)
				&& isset($cache['type'])
				&& is_array($cache['type'])
				&& isset($cache['type']['class_name'])
				&& $cache['type']['class_name'] === 'CPHPCacheMemcacheCluster'
			)
			{
				\Bitrix\Main\Config\Configuration::setValue('cache', null);
				self::$systemConfigurationUpdate = false;
			}
		}
	}

	public static function GetList()
	{
		$res = new CDBResult;
		$res->InitFromArray(CClusterMemcache::LoadConfig());
		return $res;
	}

	static public function getServerList()
	{
		global $DB;
		$result = array();
		foreach (CClusterMemcache::LoadConfig() as $arData)
		{
			$result[] = array(
				"ID" => $arData["ID"],
				"GROUP_ID" => $arData["GROUP_ID"],
				"SERVER_TYPE" => "memcache",
				"ROLE_ID" => "",
				"HOST" => $arData["HOST"],
				"DEDICATED" => "Y",
				"EDIT_URL" => "/bitrix/admin/cluster_memcache_edit.php?lang=".LANGUAGE_ID."&group_id=".$arData["GROUP_ID"]."&ID=".$arData["ID"],
			);
		}
		return $result;
	}

	public static function GetByID($id)
	{
		$ar = CClusterMemcache::LoadConfig();
		return $ar[$id];
	}

	public function Add($arFields)
	{
		global $DB, $CACHE_MANAGER;

		if(!$this->CheckFields($arFields, false))
			return false;

		$arServerList = CClusterMemcache::LoadConfig();

		$ID = 1;
		foreach($arServerList as $arServer)
			if($arServer["ID"] >= $ID)
				$ID = $arServer["ID"]+1;

		$arServerList[$ID] = array(
			"ID" => $ID,
			"GROUP_ID" => intval($arFields["GROUP_ID"]),
			"STATUS" => "READY",
			"WEIGHT" => $arFields["WEIGHT"],
			"HOST" => $arFields["HOST"],
			"PORT" => $arFields["PORT"],
		);
		CClusterMemcache::SaveConfig($arServerList);

		return $ID;
	}

	public static function Delete($ID)
	{
		$arServerList = CClusterMemcache::LoadConfig();
		if(array_key_exists($ID, $arServerList))
		{
			unset($arServerList[$ID]);
			CClusterMemcache::SaveConfig($arServerList);
		}
		return true;
	}

	public function Update($ID, $arFields)
	{
		$ID = intval($ID);
		$arServerList = CClusterMemcache::LoadConfig();

		if(!array_key_exists($ID, $arServerList))
			return false;

		if(!$this->CheckFields($arFields, $ID))
			return false;

		$arServerList[$ID] = array(
			"ID" => $ID,
			"GROUP_ID" => $arServerList[$ID]["GROUP_ID"],
			"STATUS" => isset($arFields["STATUS"])? $arFields["STATUS"]: $arServerList[$ID]["STATUS"],
			"WEIGHT" => isset($arFields["WEIGHT"])? $arFields["WEIGHT"]: $arServerList[$ID]["WEIGHT"],
			"HOST" => isset($arFields["HOST"])? $arFields["HOST"]: $arServerList[$ID]["HOST"],
			"PORT" => isset($arFields["PORT"])? $arFields["PORT"]: $arServerList[$ID]["PORT"],
		);
		CClusterMemcache::SaveConfig($arServerList);

		return $ID;
	}

	public static function CheckFields(&$arFields, $ID)
	{
		global $APPLICATION;
		$aMsg = array();

		if(isset($arFields["PORT"]))
			$arFields["PORT"] = intval($arFields["PORT"]);

		if(isset($arFields["WEIGHT"]) || $ID === false)
		{
			$weight = intval($arFields["WEIGHT"]);
			if($weight < 0)
				$weight = 0;
			elseif($weight > 100)
				$weight = 100;
			$arFields["WEIGHT"] = $weight;
		}

		if(isset($arFields["HOST"]) && isset($arFields["PORT"]))
		{
			$ob = new Memcache;
			if(!@$ob->connect($arFields["HOST"], $arFields["PORT"]))
				$aMsg[] = array("id" => "HOST", "text" => GetMessage("CLU_MEMCACHE_CANNOT_CONNECT"));
		}

		if(!empty($aMsg))
		{
			$e = new CAdminException($aMsg);
			$APPLICATION->ThrowException($e);
			return false;
		}
		return true;
	}

	public static function Pause($ID)
	{
		$arServer = CClusterMemcache::GetByID($ID);
		if(is_array($arServer) && $arServer["STATUS"] != "READY")
		{
			$ob = new CClusterMemcache;
			$ob->Update($ID, array("STATUS" => "READY"));
		}
	}

	public static function Resume($ID)
	{
		$arServer = CClusterMemcache::GetByID($ID);
		if(is_array($arServer) && $arServer["STATUS"] == "READY")
		{
			$ob = new CClusterMemcache;
			$ob->Update($ID, array("STATUS" => "ONLINE"));
		}
	}

	public static function GetStatus($id)
	{
		$arStats = array();

		$arServer = CClusterMemcache::GetByID($id);
		if(is_array($arServer))
		{
			$ob = new Memcache;
			if(@$ob->connect($arServer["HOST"], $arServer["PORT"]))
			{
				$arStats = array(
					'uptime' => null,
					'version' => null,
					'cmd_get' => null,
					'cmd_set' => null,
					'get_misses' => null,
					'get_hits' => null,
					'limit_maxbytes' => null,
					'bytes' => null,
					'curr_items' => null,
					'listen_disabled_num' => null,
				);
				$ar = $ob->getStats();
				foreach($arStats as $key => $value)
					$arStats[$key] = $ar[$key];
			}
		}

		return $arStats;
	}

}
?>