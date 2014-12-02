<?
class CSaleDelivery2PaySystem
{
	public static $arFirstDS = array();
	public static $arFirstPS = array();

	private static function makeSqlSearch($arFilter)
	{
		$arSqlSearch = array();

		if (is_array($arFilter))
		{
			foreach ($arFilter as $key => $val)
			{
				if (strlen($val) <= 0)
					continue;

				$key = strtoupper($key);

				switch($key)
				{
					case "PAYSYSTEM_ID":
						$arSqlSearch[] = $key."=".intval($val);
						break;
					case "DELIVERY_ID":
					case "DELIVERY_PROFILE_ID":
						$arSqlSearch[] = GetFilterQuery($key, $val, "N");
						break;
				}
			}
		}

		return GetFilterSqlSearch($arSqlSearch);
	}

	public static function GetList($arFilter = array(), $arGroupBy = false, $arSelectFields = array())
	{
		global $DB;

		$strSqlSearch = self::makeSqlSearch($arFilter);

		$arFieldsToSelect = array();

		if (count($arSelectFields) > 0)
		{
			$arAllFields = array("DELIVERY_ID", "DELIVERY_PROFILE_ID", "PAYSYSTEM_ID");

			foreach ($arSelectFields as $value)
				if(in_array($value, $arAllFields))
					$arFieldsToSelect[] = $value;
		}

		if(!empty($arFieldsToSelect))
			$strFieldsToSelect = implode(", ", $arFieldsToSelect);
		else
			$strFieldsToSelect = "*";

		$strSql = "
			SELECT ".
				$strFieldsToSelect.
			" FROM
				b_sale_delivery2paysystem
			WHERE
			".$strSqlSearch;

		if($arGroupBy !== false && is_array($arGroupBy) && !empty($arGroupBy))
		{
			$strGroupBy = implode(", ", $arGroupBy);
			$strSql .=" GROUP BY ".$strGroupBy;
		}

		$res = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);

		return $res;
	}

	public static function isPaySystemApplicable($paySystemId, $deliveryId)
	{
		if(strlen($deliveryId) <= 0)
			return true;

		$result = false;
		$arDelivery = CSaleDeliveryHelper::getDeliverySIDAndProfile($deliveryId);

		$psInList = $dInList = $together = false;

		$dbPSRec = self::GetList();

		while($arPSRec = $dbPSRec->Fetch())
		{
			$psInRecord = $dInRecord = false;

			if($arPSRec["PAYSYSTEM_ID"] == $paySystemId)
				$psInList = $psInRecord = true;

			if($arPSRec["DELIVERY_ID"] == $arDelivery["SID"]
				&&
				(
					is_null($arPSRec["DELIVERY_PROFILE_ID"])
					||
					$arPSRec["DELIVERY_PROFILE_ID"] == $arDelivery["PROFILE"]
				)
			)
			{
				$dInList = $dInRecord = true;
			}

			if($dInRecord && $psInRecord)
			{
				$together = true;
				break;
			}
		}

		if($together)
			$result = true;
		elseif (!$psInList || !$dInList)
			$result = true;

		return $result;
	}


	public static function UpdateDelivery($ID, $arFields)
	{
		if(!is_array($arFields) || strlen($ID) <= 0)
			return false;

		$arAddedRecords = array();

		$arFilterFields["DELIVERY_ID"] = $ID;

		if(isset($arFields["DELIVERY_PROFILE_ID"]))
			$arFilterFields["DELIVERY_PROFILE_ID"] = $arFields["DELIVERY_PROFILE_ID"];

		self::Delete($arFilterFields);

		if(!is_array($arFields["PAYSYSTEM_ID"]))
			$arFields["PAYSYSTEM_ID"] = array("PAYSYSTEM_ID" => $arFields["PAYSYSTEM_ID"]);

		foreach ($arFields["PAYSYSTEM_ID"] as $psId)
		{
			$arFilterFields["PAYSYSTEM_ID"] = $psId;
			self::Add($arFilterFields);

			$arAddedRecords[] = $arFilterFields;
		}

		return true;
	}

	/**
	 * UpdatePaySystem
	 *
	 * @param int $ID Pay system id.
	 * @param array $arFields delivery idenificators.
	 *
	 * @return bool true if success or false otherwise.
	 */
	public static function UpdatePaySystem($ID, $arFields)
	{
		$ID = trim($ID);
		$arUpdateFields = array("PAYSYSTEM_ID" => $ID);

		if (strlen($ID) <= 0 || !is_array($arFields) || empty($arFields))
			return false;

		if ($arFields[0] == "")
			unset($arFields[0]);

		self::Delete($arUpdateFields);

		$arRecords = array();

		foreach ($arFields as $deliveryId)
		{

			$delivery = CSaleDeliveryHelper::getDeliverySIDAndProfile($deliveryId);

			if(!isset($delivery["SID"]))
				continue;

			$arUpdateFields["DELIVERY_ID"] = $delivery["SID"];

			if(isset($delivery["PROFILE"]))
				$arUpdateFields["DELIVERY_PROFILE_ID"] = $delivery["PROFILE"];
			else
				$arUpdateFields["DELIVERY_PROFILE_ID"] = null;

			self::Add($arUpdateFields);
			$arRecords[] = $arUpdateFields;
		}

		return true;
	}

	public static function Delete($arFilter)
	{
		global $DB;

		$strSqlSearch = self::makeSqlSearch($arFilter);

		return $DB->Query("DELETE FROM b_sale_delivery2paysystem WHERE ".$strSqlSearch);
	}

	public static function Add($arFields)
	{
		global $DB;

		if(!isset($arFields["DELIVERY_ID"])
			||
			strlen(trim($arFields["DELIVERY_ID"])) <=0
			||
			!isset($arFields["PAYSYSTEM_ID"])
			||
			intval($arFields["PAYSYSTEM_ID"]) <=0
		)
		{
			return false;
		}

		$arFieldsFiltered = array();

		$arFieldsFiltered["DELIVERY_ID"] = $DB->ForSql($arFields["DELIVERY_ID"]);
		$arFieldsFiltered["PAYSYSTEM_ID"] = $DB->ForSql($arFields["PAYSYSTEM_ID"]);

		if(isset($arFields["DELIVERY_PROFILE_ID"]))
			$arFieldsFiltered["DELIVERY_PROFILE_ID"] = $DB->ForSql($arFields["DELIVERY_PROFILE_ID"]);

		$arInsert = $DB->PrepareInsert("b_sale_delivery2paysystem", $arFieldsFiltered);

		$strSql = "INSERT INTO b_sale_delivery2paysystem (".$arInsert[0].") VALUES(".$arInsert[1].")";

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}

	public static function getSyncData()
	{
		$arD2PS = array();
		$dbD2PS = self::GetList();
		while($arTmpD2PS = $dbD2PS->Fetch())
			$arD2PS[] = $arTmpD2PS;

		static $arPS = null;
		if(is_null($arPS))
		{
			$arPS = array();
			$dbPS = CSalePaySystem::GetList();
			while($arTmpPS = $dbPS->Fetch())
				$arPS[] = $arTmpPS;
		}

		static $arDS = null;
		if(is_null($arDS))
		{
			$arDS = array();
			$dbDS = CSaleDeliveryHandler::GetList();
			while($arTmpDS = $dbDS->Fetch())
				$arDS[] = $arTmpDS;

			$dbDS = CSaleDelivery::GetList();
			while($arTmpDS = $dbDS->Fetch())
			{
				if(!isset($arTmpDS["SID"]))
					$arTmpDS["SID"] = $arTmpDS["ID"];

				$arDS[] = $arTmpDS;
			}
		}

		return array(
			"D2PS" => $arD2PS,
			"DS" => $arDS,
			"PS" => $arPS
			);
	}

	public static function convertEmptyAll()
	{
		$data = self::getSyncData();

		$psList = array();
		$dsList = array();
		$d2ps = array(
			"PS" => array(),
			"DS" => array()
		);

		$psEmptyAll = array();
		$dsEmptyAll = array();

		foreach ($data["D2PS"] as $d2psRec)
		{
			if(!in_array($d2psRec["PAYSYSTEM_ID"], $d2ps["PS"]))
				$d2ps["PS"][] = $d2psRec["PAYSYSTEM_ID"];

			$dId = $d2psRec["DELIVERY_ID"].(!is_null($d2psRec["DELIVERY_PROFILE_ID"]) ? ":".$d2psRec["DELIVERY_PROFILE_ID"] : "");

			if(!in_array($dId, $d2ps["DS"]))
				$d2ps["DS"][] = $dId;
		}

		foreach ($data["PS"] as $ps)
		{
			$psList[] = $ps["ID"];

			if(!in_array($ps["ID"], $d2ps["PS"]))
				$psEmptyAll[] = $ps["ID"];
		}

		foreach ($data["DS"] as $ds)
		{
			if(!isset($ds["ID"]) && isset($ds["SID"]))
			{
				if(isset($ds["PROFILES"]) && is_array($ds["PROFILES"]))
				{
					foreach ($ds["PROFILES"] as $pId => $tmpProf)
					{
						$dsId = $ds["SID"].":".$pId;

						if(!in_array($dsId, $d2ps["DS"]))
							$dsEmptyAll[] = array(
								"SID" => $ds["SID"],
								"PROFILE_ID" => $pId
								);
					}
				}
				else
				{
					$dsId = $ds["SID"];

					if(!in_array($dsId, $d2ps["DS"]))
						$dsEmptyAll[] = array("SID" => $ds["SID"]);

				}
			}
			else
			{
				$dsId = $ds["ID"];

				if(!in_array($dsId, $d2ps["DS"]))
					$dsEmptyAll[] = array("SID" => $ds["ID"]);
			}

			$dsList[] = $dsId;
		}

		foreach ($psEmptyAll as $psId)
			self::UpdatePaySystem($psId, $dsList);

		foreach ($dsEmptyAll as $ds)
			self::UpdateDelivery(
				$ds["SID"],
				array(
					"DELIVERY_PROFILE_ID" => $ds["PROFILE_ID"],
					"PAYSYSTEM_ID" => $psList
					)
			);

		return true;
	}

	public static function convertEmptyAllAgent()
	{
		self::convertEmptyAll();
		return "";
	}
}
?>