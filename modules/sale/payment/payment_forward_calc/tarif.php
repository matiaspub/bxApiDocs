<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
//http://www.russianpost.ru/rp/servise/ru/home/finuslug/cybermoney_russia

include(GetLangFileName(dirname(__FILE__)."/", "/tarif.php"));

class CSalePaySystemTarifPFC extends CSalePaySystemTarif
{
	/* required inhereted methods */
	public static function getPrice(&$arPaySystem, $orderPrice, $deliveryPrice, $buyerLocationId)
	{
		if(!isset($arPaySystem["PSA_TARIF"]) || strlen($arPaySystem["PSA_TARIF"]) <= 0)
			return 0;

		$arTarif = array();
		$arLoc = CSaleLocation::GetByID($buyerLocationId);
		$regId = $arLoc["REGION_ID"];
		$arTarifs = self::extractFromField($arPaySystem["PSA_TARIF"]);
		$arTarif = isset($arTarifs[$regId]) ? $arTarifs[$regId] : $arTarifs[0];
		$fullPrice = $orderPrice + $deliveryPrice;

		if($fullPrice <= 1000)
			$tarifNum = "0";
		elseif($fullPrice <= 5000)
			$tarifNum = "1";
		elseif($fullPrice <= 20000)
			$tarifNum = "2";
		elseif($fullPrice <= 500000)
			$tarifNum = "3";

		if(floatval($arTarif["TARIFS"][$tarifNum]["UPPER_SUMM"]) > 0)
			$percent = floatval($arTarif["TARIFS"][$tarifNum]["UPPER_SUMM"])*floatval($arTarif["TARIFS"][$tarifNum]["PERCENT"]);
		else
			$percent = $fullPrice*floatval($arTarif["TARIFS"][$tarifNum]["PERCENT"]);

		$result = floatval($arTarif["TARIFS"][$tarifNum]["FIX"]) + $percent/100;

		return round($result, 0);
	}

	public static function getValues($psId = false, $persId = false)
	{
		$arResult = array();

		//get saved
		if(intval($psId) > 0 && intval($persId) > 0)
		{
			$dbPSAction = CSalePaySystemAction::GetList(
					array(),
					array("PAY_SYSTEM_ID" => $psId, "PERSON_TYPE_ID" => $persId)
				);
			if ($arPSAction = $dbPSAction->Fetch())
			{
				$arResult = self::extractFromField($arPSAction["TARIF"]);
			}
		}

		//or get default
		if(empty($arResult))
		{
			$arResult = CSaleHelper::getOptionOrImportValues(
										'ps_payment_forward_calc_tarifs',
										array('CSalePaySystemTarifPFC', 'getAllCMTarifsFromCsv')
							);

		}

		return $arResult;
	}

	public static function extractFromField($strFieldContent)
	{
		$arResult=array();
		$arTarifs = parent::extractFromField($strFieldContent);
		$arRegIds = array_keys($arTarifs);
		$regNames = CSaleLocation::GetRegionsNamesByIds($arRegIds);

		$arResult[0]["TARIFS"] = self::getTarifArrayCSV($arTarifs[0]);
		$arResult[0]["REG_NAME"] = GetMessage('SPFPCT_TARIF_DEFAULT');

		foreach ($regNames as $regId => $regName)
		{
			$assocTarif = self::getTarifArrayCSV($arTarifs[$regId]);
			$arResult[$regId]["TARIFS"] = $assocTarif;
			$arResult[$regId]["REG_NAME"] = $regName;
		}

		return $arResult;
	}

	public static function prepareToField($arTarif)
	{
		$arResult = array();

		if(is_array($arTarif))
		{
			foreach ($arTarif as $tarifId => $value)
			{
				if($tarifId == "REG_NEW" && $value > 0)
				{
					for ($i=0; $i<=11; $i++)
						$arResult[$value][] = 0;

					continue;
				}

				$arTarifId = explode('_', $tarifId);

				if(isset($arTarifId[2]))
					$regionId = $arTarifId[2];

				$arResult[$regionId][] = $value;
			}
		}
		return parent::prepareToField($arResult);
	}

	public static function getStructure($psId, $persId)
	{
		$arResult = array();

		$arShopLocation = CSaleHelper::getShopLocation();

		$arCmTarifs = self::getValues($psId, $persId);

		foreach ($arCmTarifs as $regionId => $arRegInfo)
		{

			$arResult[$regionId.'REG_ID'] = array(
						'TYPE' => 'TEXT_CENTERED',
						'TITLE' => $arRegInfo["REG_NAME"],
						'BLOCK_HIDEABLE' => 'Y',
						'BLOCK_LENGTH' => 4,
			);

			if($regionId != 0)
				$arResult[$regionId.'REG_ID']['BLOCK_DELETABLE'] = 'Y';

			self::setTarifConfig(
					'TARIF_1_'.$regionId,
					GetMessage('SPFPCT_LESS_1K'),
					$arRegInfo["TARIFS"][0],
					$arResult);

			self::setTarifConfig(
					'TARIF_2_'.$regionId,
					GetMessage('SPFPCT_LESS_5K'),
					$arRegInfo["TARIFS"][1],
					$arResult);

			self::setTarifConfig(
					'TARIF_3_'.$regionId,
					GetMessage('SPFPCT_LESS_20K'),
					$arRegInfo["TARIFS"][2],
					$arResult);

			self::setTarifConfig(
					'TARIF_4_'.$regionId,
					GetMessage('SPFPCT_LESS_500K'),
					$arRegInfo["TARIFS"][3],
					$arResult);
		}

			$arRegions = self::getRegionsList();
			$arResult['REG_NEW'] = array(
						'TYPE' => 'DROPDOWN',
						'TITLE' => GetMessage('SPFPCT_ADD_REGION'),
						'VALUES' => $arRegions,
						'ONCHANGE' => "document.forms['pay_sys_form'].elements['apply'].click();"
			);

		return $arResult;
	}

	private static function getRegionsList()
	{
		$arResult = array(0 => GetMessage('SPFPCT_CHOOSE_REGION'));

		$dbReg = CSaleLocation::GetRegionList();
		while ($arReg = $dbReg->Fetch())
			$arResult[$arReg["ID"]] = $arReg["NAME"];

		return $arResult;
	}

	public static function checkCompability(&$arOrder, $orderPrice, $deliveryPrice, $buyerLocationId)
	{
		$maxSumm = 500000;
		$fullPrice = $orderPrice+$deliveryPrice;

		return ($fullPrice <= $maxSumm);
	}

	private static function setTarifConfig($tarifId, $tarifTitle, $arTarifs, &$arConfig)
	{
		$arConfig[$tarifId.'_NAME'] = array(
					'TYPE' => 'MULTI_CONTROL_STRING',
					'MCS_ID' => $tarifId,
					'TITLE' => $tarifTitle,
		);

		$arConfig[$tarifId.'_FIX'] = array(
					'TYPE' => 'STRING',
					'MCS_ID' => $tarifId,
					'POST_TEXT' => ' '.GetMessage('SPFPCT_RUB').' ',
					'SIZE' => 4,
					'VALUE' => $arTarifs['FIX']
		);

		$arConfig[$tarifId.'_PERCENT'] = array(
					'TYPE' => 'STRING',
					'MCS_ID' => $tarifId,
					'PRE_TEXT' => ' + ',
					'POST_TEXT' => ' % ',
					'SIZE' => 3,
					'VALUE' => $arTarifs['PERCENT']
		);

		$arConfig[$tarifId.'_UPPER_SUMM'] = array(
					'TYPE' => 'STRING',
					'MCS_ID' => $tarifId,
					'PRE_TEXT' => ' '.GetMessage('SPFPCT_SUMM_MORE').' ',
					'POST_TEXT' => ' '.GetMessage('SPFPCT_RUB').' ',
					'SIZE' => 7,
					'VALUE' => $arTarifs['UPPER_SUMM']
		);
	}

	private static function getTarifArrayCSV($arTarifs)
	{
		$arResult = array();

		if(is_array($arTarifs) && count($arTarifs) == 12)
		{
			for($i = 0; $i < 4; $i++)
			{
				$arResult[] = array(
								"FIX" => $arTarifs[$i*3],
								"PERCENT" => $arTarifs[$i*3+1],
								"UPPER_SUMM" => $arTarifs[$i*3+2]
							);
			}
		}

		return $arResult;
	}

	public static function getCMTarifsByRegionFromCsv($regionNameLang)
	{
		if(strlen(trim($regionNameLang)) <= 0)
			return false;

		$csvFile = CSaleHelper::getCsvObject(__DIR__.'/ru/cm_tarif.csv');

		$COL_REG_NAME = 0;
		$arTarifs = array();
		$arTarifs = $csvFile->Fetch();

		while ($arRes = $csvFile->Fetch())
		{
			if(strtoupper(trim($regionNameLang)) === $arRes[$COL_REG_NAME])
			{
				$arTarifs = $arRes;
				break;
			}
		}

		$arTarifsResult = self::getTarifArrayCSV($arTarifs);

		return $arTarifsResult;
	}

	public static function getAllCMTarifsFromCsv()
	{
		$csvFile = CSaleHelper::getCsvObject(__DIR__.'/ru/cm_tarif.csv');
		$arTarifs = array();
		$regNames = array();
		$arResult = array();

		while ($arRes = $csvFile->Fetch())
		{
				$arRegName = array_shift($arRes);
				$arTarifs[$arRegName] = $arRes;

				if($arRegName != 'default')
					$regNames[] = $arRegName;
		}

		if(isset($arTarifs['default']))
		{
			$arResult[0] = array(
							"TARIFS" => self::getTarifArrayCSV($arTarifs['default']),
							"REG_NAME" => GetMessage('SPFPCT_TARIF_DEFAULT')
						);
		}

		$regInfo = CSaleLocation::GetRegionsIdsByNames($regNames);

		foreach ($regInfo as $regName => $regId)
		{
			$assocTarif = self::getTarifArrayCSV($arTarifs[$regName]);

			$arResult[$regInfo[$regName]]["TARIFS"] = $assocTarif;
			$arResult[$regInfo[$regName]]["REG_NAME"] = $regName;
		}

		return $arResult;
	}
}

$PSTarifClassName = 'CSalePaySystemTarifPFC';
?>
