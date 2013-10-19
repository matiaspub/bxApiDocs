<?
IncludeModuleLangFile(__FILE__);
require_once('pushservices/services_descriptions.php');

class CPullPush
{
	protected static function PrepareSql(&$arFields, $arOrder, &$arFilter, $arSelectFields)
	{
		global $DB;

		$strSqlSelect = "";
		$strSqlFrom = "";
		$strSqlWhere = "";
		$strSqlGroupBy = "";
		$strSqlOrderBy = "";
		$strSqlLimit = "";
		$arGroupByFunct = Array();
		// SELECT -->
		$arFieldsKeys = array_keys($arFields);
		$arAlreadyJoined = Array();
		if (isset($arSelectFields) && !is_array($arSelectFields) && is_string($arSelectFields) && strlen($arSelectFields)>0 && array_key_exists($arSelectFields, $arFields))
				$arSelectFields = array($arSelectFields);

		if (!isset($arSelectFields)
				|| !is_array($arSelectFields)
				|| count($arSelectFields)<=0
				|| in_array("*", $arSelectFields))
		{
				$countFieldsKeys = count($arFieldsKeys);
				for ($i = 0; $i < $countFieldsKeys; $i++)
				{
					if (isset($arFields[$arFieldsKeys[$i]]["WHERE_ONLY"])
							&& $arFields[$arFieldsKeys[$i]]["WHERE_ONLY"] == "Y")
					{
							continue;
					}

					if (strlen($strSqlSelect) > 0)
							$strSqlSelect .= ", ";

					if ($arFields[$arFieldsKeys[$i]]["FIELD_TYPE"] == "datetime")
					{
							if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
								$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD_NAME"]." as ".$arFieldsKeys[$i]."_X1, ";

							$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD_NAME"], "FULL")." as ".$arFieldsKeys[$i];
					}
					elseif ($arFields[$arFieldsKeys[$i]]["FIELD_TYPE"] == "date")
					{
							if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($arFieldsKeys[$i], $arOrder)))
								$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD_NAME"]." as ".$arFieldsKeys[$i]."_X1, ";

							$strSqlSelect .= $DB->DateToCharFunction($arFields[$arFieldsKeys[$i]]["FIELD_NAME"], "SHORT")." as ".$arFieldsKeys[$i];
					}
					else
							$strSqlSelect .= $arFields[$arFieldsKeys[$i]]["FIELD_NAME"]." as ".$arFieldsKeys[$i];

					if (isset($arFields[$arFieldsKeys[$i]]["FROM"])
							&& strlen($arFields[$arFieldsKeys[$i]]["FROM"]) > 0
							&& !in_array($arFields[$arFieldsKeys[$i]]["FROM"], $arAlreadyJoined))
					{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$arFieldsKeys[$i]]["FROM"];
							$arAlreadyJoined[] = $arFields[$arFieldsKeys[$i]]["FROM"];
					}
				}
		}
		else
		{
				foreach ($arSelectFields as $key => $val)
				{
					$val = strtoupper($val);
					$key = strtoupper($key);
					if (array_key_exists($val, $arFields))
					{
							if (strlen($strSqlSelect) > 0)
								$strSqlSelect .= ", ";

							if (in_array($key, $arGroupByFunct))
							{
								$strSqlSelect .= $key."(".$arFields[$val]["FIELD_NAME"].") as ".$val;
							}
							else
							{
								if ($arFields[$val]["FIELD_TYPE"] == "datetime")
								{
										if ((strtoupper($DB->type)=="ORACLE" || strtoupper($DB->type)=="MSSQL") && (array_key_exists($val, $arOrder)))
											$strSqlSelect .= $arFields[$val]["FIELD_NAME"]." as ".$val."_X1, ";

										$strSqlSelect .= $DB->DateToCharFunction($arFields[$val]["FIELD"], "FULL")." as ".$val;
								}
								else
										$strSqlSelect .= $arFields[$val]["FIELD_NAME"]." as ".$val;
							}

							if (isset($arFields[$val]["FROM"])
								&& strlen($arFields[$val]["FROM"]) > 0
								&& !in_array($arFields[$val]["FROM"], $arAlreadyJoined))
							{
								if (strlen($strSqlFrom) > 0)
										$strSqlFrom .= " ";
								$strSqlFrom .= $arFields[$val]["FROM"];
								$arAlreadyJoined[] = $arFields[$val]["FROM"];
							}
					}
				}
		}

		// <-- SELECT

		// WHERE -->
		$obWhere = new CSQLWhere;
		$obWhere->SetFields($arFields);
		$strSqlWhere = $obWhere->GetQuery($arFilter);

		// ORDER BY -->
		$arSqlOrder = Array();
		foreach ($arOrder as $by => $order)
		{
				$by = strtoupper($by);
				$order = strtoupper($order);

				if ($order != "ASC")
					$order = "DESC";
				else
					$order = "ASC";

				if (array_key_exists($by, $arFields))
				{
					$arSqlOrder[] = " ".$arFields[$by]["FIELD_NAME"]." ".$order." ";

					if (isset($arFields[$by]["FROM"])
							&& strlen($arFields[$by]["FROM"]) > 0
							&& !in_array($arFields[$by]["FROM"], $arAlreadyJoined))
					{
							if (strlen($strSqlFrom) > 0)
								$strSqlFrom .= " ";
							$strSqlFrom .= $arFields[$by]["FROM"];
							$arAlreadyJoined[] = $arFields[$by]["FROM"];
					}
				}
		}

		$strSqlOrderBy = "";
		DelDuplicateSort($arSqlOrder);
		$countSqlOrder = count($arSqlOrder);
		for ($i=0; $i<$countSqlOrder; $i++)
		{
				if (strlen($strSqlOrderBy) > 0)
					$strSqlOrderBy .= ", ";

				if(strtoupper($DB->type)=="ORACLE")
				{
					if(substr($arSqlOrder[$i], -3)=="ASC")
							$strSqlOrderBy .= $arSqlOrder[$i]." NULLS FIRST";
					else
							$strSqlOrderBy .= $arSqlOrder[$i]." NULLS LAST";
				}
				else
					$strSqlOrderBy .= $arSqlOrder[$i];
		}
		// <-- ORDER BY

		return array(
				"SELECT" => $strSqlSelect,
				"FROM" => $strSqlFrom,
				"WHERE" => $strSqlWhere,
				"GROUPBY" => $strSqlGroupBy,
				"ORDERBY" => $strSqlOrderBy,
		);
	}

	public static function GetList($arOrder = array(), $arFilter = array(),$arSelect = array(), $arNavStartParams = Array())
	{
		global $DB;

		$arFields = array(
			"ID" => array("FIELD_NAME" => "R.ID", "FIELD_TYPE" => "int"),
			"USER_ID" => array("FIELD_NAME" => "R.USER_ID", "FIELD_TYPE" => "int"),
			"DEVICE_TYPE" => array("FIELD_NAME" => "R.DEVICE_TYPE", "FIELD_TYPE" => "string"),
			"DEVICE_ID" => array("FIELD_NAME" => "R.DEVICE_ID", "FIELD_TYPE" => "string"),
			"DEVICE_NAME" => array("FIELD_NAME" => "R.DEVICE_NAME", "FIELD_TYPE" => "string"),
			"DEVICE_TOKEN" => array("FIELD_NAME" => "R.DEVICE_TOKEN", "FIELD_TYPE" => "string"),
			"DATE_CREATE" => array("FIELD_NAME" => "R.DATE_CREATE", "TYPE" => "datetime"),
			"DATE_AUTH" => array("FIELD_NAME" => "R.DATE_AUTH", "TYPE" => "datetime"),
		);
		$arSqls = self::PrepareSql($arFields, $arOrder, $arFilter,$arSelect);
		$strSql = "SELECT ".$arSqls["SELECT"]."
		FROM b_pull_push R ".
		(strlen($arSqls["WHERE"])<=0 ? "" : "WHERE ".$arSqls["WHERE"]).
		(strlen($arSqls["ORDERBY"])<=0 ? "" : " ORDER BY ".$arSqls["ORDERBY"]);
		if(is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])>0)
			$strSql = $DB->TopSql($strSql, $arNavStartParams["nTopCount"]);
		//echo "<pre>".$strSql."</pre>";
		$res = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $res;
	}

	public static function Add($arFields = Array())
	{
		global $DB;

		if (!self::CheckFields("ADD", $arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_pull_push", $arFields);
		$strSql ="INSERT INTO b_pull_push(".$arInsert[0].", DATE_CREATE, DATE_AUTH) ".
				"VALUES(".$arInsert[1].", ".$DB->CurrentTimeFunction().", ".$DB->CurrentTimeFunction().")";
		// echo $strSql ;
		$ID = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);
		return $ID;
	}

	public static function Update($ID, $arFields = Array())
	{
		global $DB;
		$ID = intval($ID);

		if (!self::CheckFields("UPDATE", $arFields) || $ID<=0)
			return false;

		$strUpdate = $DB->PrepareUpdate("b_pull_push", $arFields);
		$strSql = "UPDATE b_pull_push SET ".$strUpdate." WHERE ID=".$ID;

		$ID = $DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return $ID;
	}

	public static function CheckFields($type = "ADD", &$arFields = Array())
	{
		$pm = new CPushManager();
		$arDeviceTypes = array_keys($pm->GetServices());
		$arFields["USER_ID"] = intval($arFields["USER_ID"]);
		if (!is_array($arFields) || empty($arFields))
			return false;
		if (!$arFields["DEVICE_TOKEN"]||!$arFields["DEVICE_ID"]||intval($arFields["USER_ID"])<=0)
			return false;
		if (!$arFields["DEVICE_TYPE"] || !in_array($arFields["DEVICE_TYPE"],$arDeviceTypes))
			return false;
		if(!preg_match('~^[a-f0-9]{64}$~i', $arFields["DEVICE_TOKEN"]) && $arFields["DEVICE_TYPE"] == "APPLE")
			return false;

		if($type == "ADD")
		{
			if(!$arFields["DEVICE_NAME"] )
				$arFields["DEVICE_NAME"] = $arFields["DEVICE_ID"];
		}

		return true;
	}

	public static function Delete($ID = false)
	{
		global $DB;
		$ID = intval($ID);
		if ($ID<=0)
			return false;

		$strSql = "DELETE from b_pull_push WHERE ID=".$ID;
		$DB->Query($strSql, false, "FILE: ".__FILE__."<br> LINE: ".__LINE__);

		return true;

	}
}


class CPushManager
{
	public static $arPushServices = false;
	private static $remoteProviderUrl = "https://cloud-messaging.bitrix24.com/send/";

	static public function __construct()
	{
		if(!is_array(self::$arPushServices))
		{
			self::$arPushServices = array();

			foreach(GetModuleEvents("pull", "OnPushServicesBuildList", true) as $arEvent)
			{
				$res = ExecuteModuleEventEx($arEvent);
				if(is_array($res))
				{
					if(!is_array($res[0]))
						$res = array($res);
					foreach($res as $serv)
						self::$arPushServices[$serv["ID"]] = $serv;
				}
			}

		}
	}

	static public function AddQueue($arParams)
	{
		global $DB;

		if (isset($arParams['USER_ID']) && intval($arParams['USER_ID']) > 0)
			$arFields['USER_ID'] = intval($arParams['USER_ID']);
		else
			return false;

		if (isset($arParams['MESSAGE']) && strlen(trim($arParams['MESSAGE'])) > 0)
		{
			$arFields['MESSAGE'] = str_replace(Array("\r\n", "\n\r", "\n", "\r"), " ", trim($arParams['MESSAGE']));
			if (strlen($arFields['MESSAGE'])>110)
				$arFields['MESSAGE'] = substr($arFields['MESSAGE'], 0, 105).' ...';
		}

		$arFields['TAG'] = '';
		if (isset($arParams['TAG']) && strlen(trim($arParams['TAG'])) > 0 && strlen(trim($arParams['TAG'])) <= 255)
			$arFields['TAG'] = trim($arParams['TAG']);

		$arFields['SUB_TAG'] = '';
		if (isset($arParams['SUB_TAG']) && strlen(trim($arParams['SUB_TAG'])) > 0 && strlen(trim($arParams['SUB_TAG'])) <= 255)
			$arFields['SUB_TAG'] = trim($arParams['SUB_TAG']);

		$arFields['BADGE'] = -1;
		if (isset($arParams['BADGE']) && $arParams['BADGE'] != '' && intval($arParams['BADGE']) >= 0)
			$arFields['BADGE'] = intval($arParams['BADGE']);

		$arFields['PARAMS'] = '';
		if (isset($arParams['PARAMS']) && strlen(trim($arParams['PARAMS'])) > 0)
			$arFields['PARAMS'] = $arParams['PARAMS'];

		if (isset($arParams['SEND_IMMEDIATELY']) && $arParams['SEND_IMMEDIATELY'] == 'Y' || !CUser::IsOnLine($arFields['USER_ID'], 180))
		{
			$arAdd = Array(
				'USER_ID' => $arFields['USER_ID'],
			);
			if (strlen($arFields['MESSAGE']) > 0)
				$arAdd['MESSAGE'] = $arFields['MESSAGE'];
			if (strlen($arFields['PARAMS']) > 0)
				$arAdd['PARAMS'] = $arFields['PARAMS'];
			if ($arFields['BADGE'] >= 0)
				$arAdd['BADGE'] = $arFields['BADGE'];

			$CPushManager = new CPushManager();
			$CPushManager->SendMessage(Array($arAdd), defined('PULL_PUSH_SANDBOX')? true: false);
		}
		else
		{
			$arAdd = Array(
				'USER_ID' => $arFields['USER_ID'],
				'TAG' => $arFields['TAG'],
				'SUB_TAG' => $arFields['SUB_TAG'],
				'~DATE_CREATE' => $DB->CurrentTimeFunction()
			);
			if (strlen($arFields['MESSAGE']) > 0)
				$arAdd['MESSAGE'] = $arFields['MESSAGE'];
			if (strlen($arFields['PARAMS']) > 0)
				$arAdd['PARAMS'] = $arFields['PARAMS'];
			if ($arFields['BADGE'] >= 0)
				$arAdd['BADGE'] = $arFields['BADGE'];

			$DB->Add("b_pull_push_queue", $arAdd, Array("PARAMS"));

			CAgent::AddAgent("CPushManager::SendAgent();", "pull", "N", 30, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+30, "FULL"));
		}

		return true;
	}

	public static function DeleteFromQueueByTag($userId, $tag)
	{
		global $DB;
		if (strlen($tag) <= 0 || intval($userId) <= 0)
			return false;

		$strSql = "DELETE FROM b_pull_push_queue WHERE USER_ID = ".intval($userId)." AND TAG = '".$DB->ForSQL($tag)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function DeleteFromQueueBySubTag($userId, $tag)
	{
		global $DB;
		if (strlen($tag) <= 0 || intval($userId) <= 0)
			return false;

		$strSql = "DELETE FROM b_pull_push_queue WHERE USER_ID = ".intval($userId)." AND SUB_TAG = '".$DB->ForSQL($tag)."'";
		$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return true;
	}

	public static function SendAgent()
	{
		global $DB;

		if (!CPullOptions::GetPushStatus())
			return false;

		$count = 0;
		$maxId = 0;
		$pushLimit = 70;
		$arPush = Array();

		$sqlDate = "";
		$dbType = strtolower($DB->type);
		if ($dbType== "mysql")
			$sqlDate = " WHERE DATE_CREATE < DATE_SUB(NOW(), INTERVAL 15 SECOND) ";
		else if ($dbType == "mssql")
			$sqlDate = " WHERE DATE_CREATE < dateadd(SECOND, -15, getdate()) ";
		else if ($dbType == "oracle")
			$sqlDate = " WHERE DATE_CREATE < SYSDATE-(1/24/60/60*15) ";

		$strSql = $DB->TopSql("SELECT ID, USER_ID, MESSAGE, PARAMS, BADGE FROM b_pull_push_queue".$sqlDate, 280);
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		while ($arRes = $dbRes->Fetch())
		{
			if ($arRes['BADGE'] == '')
				unset($arRes['BADGE']);

			$arPush[$count][] = $arRes;
			if ($pushLimit <= count($arPush[$count]))
				$count++;

			$maxId = $maxId < $arRes['ID']? $arRes['ID']: $maxId;
		}

		$CPushManager = new CPushManager();
		foreach ($arPush as $arStack)
		{
			$CPushManager->SendMessage($arStack, defined('PULL_PUSH_SANDBOX')? true: false);
		}

		if ($maxId > 0)
		{
			$strSql = "DELETE FROM b_pull_push_queue WHERE ID <= ".$maxId;
			$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		$strSql = "SELECT COUNT(ID) CNT FROM b_pull_push_queue";
		$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			if ($arRes['CNT'] > 280)
			{
				CAgent::RemoveAgent("CPushManager::SendAgent();", "pull");
				CAgent::AddAgent("CPushManager::SendAgent();", "pull", "N", 15, "", "Y", ConvertTimeStamp(time()+CTimeZone::GetOffset()+15, "FULL"));
			}
			else if ($arRes['CNT'] > 0)
			{
				return "CPushManager::SendAgent();";
			}
		}

		return false;
	}

	public function SendMessage($arMessages = Array(), $sandbox = false)
	{
		if(empty($arMessages))
			return false;
		$arUsers = Array();
		$arTmpMessages = Array();
		foreach ($arMessages as $message)
		{
			if(!$message["USER_ID"])
				continue;
			$arUsers[] = $message["USER_ID"];
			if(!array_key_exists("USER_".$message["USER_ID"], $arTmpMessages))
				$arTmpMessages["USER_".$message["USER_ID"]] = Array();
			$arTmpMessages["USER_".$message["USER_ID"]][] = htmlspecialcharsback($message);
		}

		$arUsers = array_unique($arUsers);
		$dbDevices= CPullPush::GetList(Array("DEVICE_TYPE"=>"ASC"),Array("USER_ID"=>$arUsers));


		$arServicesIDs = array_keys(self::$arPushServices);
		$arPushMessages = Array();
		while($arDevice = $dbDevices->Fetch())
		{
			if(in_array($arDevice["DEVICE_TYPE"], $arServicesIDs))
					$arPushMessages[$arDevice["DEVICE_TYPE"]][$arDevice["DEVICE_TOKEN"]] = $arTmpMessages["USER_".$arDevice["USER_ID"]];
		}

		if(empty($arPushMessages))
			return false;

		$batch = "";
		foreach($arServicesIDs as $serviceID)
		{
			if($arPushMessages[$serviceID])
			{
				if (class_exists(self::$arPushServices[$serviceID]["CLASS"]))
					$obPush = new self::$arPushServices[$serviceID]["CLASS"];
				if(method_exists($obPush, "setConnectTimeout"))
					$obPush->setConnectTimeout(10);
				if(method_exists($obPush, "GetBatch"))
				{
					$batch.= $obPush->GetBatch($arPushMessages[$serviceID]);
				}
			}
		}
		$this->SendBatch($batch);

		return true;
	}

	static public function SendBatch($batch)
	{
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
		$key = CUpdateClient::GetLicenseKey();
		if(strlen($key)>0 && strlen($batch)>0)
		{
			$request = new CHTTP();
			$arPostData = Array(
				"Action"=>"SendMessage",
				"MessageBody" =>$batch
			);

			$postdata = CHTTP::PrepareData($arPostData);
			$arUrl = $request->ParseURL(self::$remoteProviderUrl."?key=".md5($key), false);

			$request->Query('POST', $arUrl['host'], $arUrl['port'], $arUrl['path_query'], $postdata, $arUrl['proto'], 'N', true);

			return true;

		}

		return false;
	}

	static public function GetServices()
	{
		return self::$arPushServices;
	}

	static function _MakeJson($arData, $bWS, $bSkipTilda)
	{
		static $aSearch = array("\r", "\n");

		if(is_array($arData))
		{

			if($arData == array_values($arData))
			{

				foreach($arData as $key => $value)
				{
					if(is_array($value))
					{
						$arData[$key] = self::_MakeJson($value, $bWS, $bSkipTilda);
					}
					elseif(is_bool($value))
					{
						if($value === true)
							$arData[$key] = "true";
						else
							$arData[$key] = "false";
					}
					elseif(is_integer($value))
					{
						$res .= $value;
					}
					else
					{
						if(preg_match("#['\"\\n\\r<\\\\]#", $value))
							$arData[$key] = "\"".CUtil::JSEscape($value)."\"";
						else
							$arData[$key] = "\"".$value."\"";
					}
				}
				return '['.implode(',', $arData).']';
			}

			$sWS = ','.($bWS ? "\n" : '');
			$res = ($bWS ? "\n" : '').'{';
			$first = true;

			foreach($arData as $key => $value)
			{
				if ($bSkipTilda && substr($key, 0, 1) == '~')
					continue;

				if($first)
					$first = false;
				else
					$res .= $sWS;

				if(preg_match("#['\"\\n\\r<\\\\]#", $key))
					$res .= "\"".str_replace($aSearch, '', CUtil::addslashes($key))."\":";
				else
					$res .= "\"".$key."\":";

				if(is_array($value))
				{
					$res .= self::_MakeJson($value, $bWS, $bSkipTilda);
				}
				elseif(is_bool($value))
				{
					if($value === true)
						$res .= "true";
					else
						$res .= "false";
				}
				elseif(is_integer($value))
				{
					$res .= $value;
				}
				else
				{
					if(preg_match("#['\"\\n\\r<\\\\]#", $value))
						$res .= "\"".CUtil::JSEscape($value)."\"";
					else
						$res .= "\"".$value."\"";
				}
			}
			$res .= ($bWS ? "\n" : '').'}';

			return $res;
		}
		elseif(is_bool($arData))
		{
			if($arData === true)
				return 'true';
			else
				return 'false';
		}
		elseif(is_integer($value))
		{
			return $value;
		}
		else
		{
			if(preg_match("#['\"\\n\\r<\\\\]#", $arData))
				return "\"".CUtil::JSEscape($arData)."'";
			else
				return "\"".$arData."\"";
		}
	}
}
?>