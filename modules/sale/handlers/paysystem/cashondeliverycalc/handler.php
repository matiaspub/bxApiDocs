<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;

Loc::loadMessages(__FILE__);

/**
 * Class CashOnDeliveryCalcHandler
 */
class CashOnDeliveryCalcHandler extends PaySystem\BaseServiceHandler implements PaySystem\IPayable
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	static public function initiatePay(Payment $payment, Request $request = null)
	{
		return new PaySystem\ServiceResult();
	}

	/**
	 * @return array
	 */
	static public function getCurrencyList()
	{
		return array('RUB');
	}

	/**
	 * @param Payment $payment
	 * @return float|int
	 */
	public function getPrice(Payment $payment)
	{
		$result = 0;

		/** @var \Bitrix\Sale\PaymentCollection $collection */
		$collection = $payment->getCollection();

		/** @var \Bitrix\Sale\Order $order */
		$order = $collection->getOrder();

		/** @var \Bitrix\Sale\PropertyValueCollection $shipmentCollection */
		$propertyCollection = $order->getPropertyCollection();

		/** @var \Bitrix\Sale\PropertyValue $delivery */
		$delivery = $propertyCollection->getDeliveryLocation();

		if (!$delivery)
			return $result;

		$location = \CSaleLocation::GetByID($delivery->getValue());

		$regId = $location["REGION_ID"];

		$params = $this->service->getField('TARIF');

		$tarifs = self::extractFromField($params);
		$tarifs = isset($tarifs[$regId]) ? $tarifs[$regId] : $tarifs[0];

		$fullPrice = $payment->getSum();

		if ($fullPrice <= 1000)
			$tariffNum = "0";
		elseif ($fullPrice <= 5000)
			$tariffNum = "1";
		elseif ($fullPrice <= 20000)
			$tariffNum = "2";
		elseif ($fullPrice <= 500000)
			$tariffNum = "3";

		if (isset($tariffNum))
		{
			$percent = 0;
			if ($tarifs["TARIFS"][$tariffNum]["UPPER_SUMM"] < $payment->getSum())
				$percent = floatval($tarifs["TARIFS"][$tariffNum]["PERCENT"]) * floatval($payment->getSum()) / 100;
			$result = floatval($tarifs["TARIFS"][$tariffNum]["FIX"]) + $percent;
		}

		return roundEx($result, SALE_VALUE_PRECISION);
	}

	/**
	 * @param $params
	 * @return array
	 */
	private static function extractFromField($params)
	{
		$result = array();
		$tarifs = unserialize($params);

		if (!is_array($tarifs))
			$tarifs = array();

		$arRegIds = array_keys($tarifs);
		$regNames = @\CSaleLocation::GetRegionsNamesByIds($arRegIds);

		$result[] = array(
			'TARIFS' => self::getTariffArrayCSV($tarifs[0]),
			'REG_NAME' => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_TARIF_DEFAULT')
		);

		foreach ($regNames as $regId => $regName)
		{
			if (in_array($regId, $arRegIds))
			{
				$result[$regId]["TARIFS"] = self::getTariffArrayCSV($tarifs[$regId]);
				$result[$regId]["REG_NAME"] = $regName;
			}
		}

		return $result;
	}

	/**
	 * @param $tarifs
	 * @return array
	 */
	private static function getTariffArrayCSV($tarifs)
	{
		$result = array();

		if(is_array($tarifs) && count($tarifs) == 12)
		{
			for($i = 0; $i < 4; $i++)
			{
				$result[] = array(
					"FIX" => $tarifs[$i*3],
					"PERCENT" => $tarifs[$i*3+1],
					"UPPER_SUMM" => $tarifs[$i*3+2]
				);
			}
		}

		return $result;
	}

	/**
	 * @param int $paySystemId
	 * @return array|mixed
	 */
	public static function getValues($paySystemId = 0)
	{
		$result = array();

		if($paySystemId > 0)
		{
			$data = PaySystem\Manager::getById($paySystemId);
			$result = self::extractFromField($data['TARIF']);
		}

		if (!$result)
		{
			$result = \CSaleHelper::getOptionOrImportValues(
				'ps_payment_forward_calc_tarifs',
				array(get_called_class(), 'getAllCMTarifsFromCsv')
			);
		}

		return $result;
	}

	/**
	 * @param $tariff
	 * @return string
	 */
	static public function prepareToField($tariff)
	{
		$arResult = array();

		if(is_array($tariff))
		{
			foreach ($tariff as $id => $value)
			{
				if($id == "REG_NEW")
				{
					if ((int)$value > 0)
					{
						for ($i = 0; $i < 12; ++$i)
							$arResult[$value][] = 0;
					}
					continue;
				}
				$tariffIds = explode('_', $id);

				if(isset($tariffIds[2]))
					$regionId = $tariffIds[2];

				$arResult[$regionId][] = $value;
			}
		}
		return serialize($arResult);
	}

	/**
	 * @param int $paySystemId
	 * @return array
	 */
	public static function getStructure($paySystemId = 0)
	{
		$arResult = array();

		$arCmTarifs = self::getValues($paySystemId);

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

			self::setTariffConfig('TARIF_1_'.$regionId, Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_LESS_1K'), $arRegInfo["TARIFS"][0], $arResult);
			self::setTariffConfig('TARIF_2_'.$regionId, Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_LESS_5K'), $arRegInfo["TARIFS"][1], $arResult);
			self::setTariffConfig('TARIF_3_'.$regionId, Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_LESS_20K'), $arRegInfo["TARIFS"][2], $arResult);
			self::setTariffConfig('TARIF_4_'.$regionId, Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_LESS_500K'), $arRegInfo["TARIFS"][3], $arResult);
		}

		$arResult['REG_NEW'] = array(
			'TYPE' => 'DROPDOWN',
			'TITLE' => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_ADD_REGION'),
			'VALUES' => self::getRegionsList(),
			'ONCHANGE' => "document.forms['pay_sys_form'].elements['apply'].click();"
		);

		return $arResult;
	}

	/**
	 * @param $tariffId
	 * @param $tariffTitle
	 * @param $arTarifs
	 * @param $arConfig
	 */
	private static function setTariffConfig($tariffId, $tariffTitle, $arTarifs, &$arConfig)
	{
		$arConfig[$tariffId.'_NAME'] = array('TYPE' => 'MULTI_CONTROL_STRING', 'MCS_ID' => $tariffId, 'TITLE' => $tariffTitle);
		$arConfig[$tariffId.'_FIX'] = array('TYPE' => 'STRING', 'MCS_ID' => $tariffId, 'POST_TEXT' => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_RUB'), 'SIZE' => 4, 'VALUE' => $arTarifs['FIX']);
		$arConfig[$tariffId.'_PERCENT'] = array('TYPE' => 'STRING', 'MCS_ID' => $tariffId, 'PRE_TEXT' => ' + ', 'POST_TEXT' => ' % ', 'SIZE' => 3, 'VALUE' => $arTarifs['PERCENT']);
		$arConfig[$tariffId.'_UPPER_SUMM'] = array('TYPE' => 'STRING', 'MCS_ID' => $tariffId, 'PRE_TEXT' => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_SUMM_MORE'), 'POST_TEXT' => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_RUB'), 'SIZE' => 7, 'VALUE' => $arTarifs['UPPER_SUMM']);
	}

	private static function getRegionsList()
	{
		$result = array(0 => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_CHOOSE_REGION'));

		/** @var \CDBResult $dbRes */
		$dbRes = \CSaleLocation::GetRegionList();
		while ($region = $dbRes->Fetch())
			$result[$region["ID"]] = $region["NAME"];

		return $result;
	}

	/**
	 * @param $regionNameLang
	 * @return array|bool
	 */
	static public function getCMTarifsByRegionFromCsv($regionNameLang)
	{
		if(strlen(trim($regionNameLang)) <= 0)
			return false;

		$csvFile = \CSaleHelper::getCsvObject(__DIR__.'/lang/ru/cm_tarif.csv');

		$COL_REG_NAME = 0;
		$arTarifs = array();

		while ($arRes = $csvFile->Fetch())
		{
			if(strtoupper(trim($regionNameLang)) === $arRes[$COL_REG_NAME])
			{
				$arTarifs = $arRes;
				break;
			}
		}

		$arTarifsResult = self::getTariffArrayCSV($arTarifs);

		return $arTarifsResult;
	}

	/**
	 * @return array
	 */
	public static function getAllCMTarifsFromCsv()
	{
		$csvFile = \CSaleHelper::getCsvObject(__DIR__.'/lang/ru/cm_tarif.csv');
		$tarifs = array();
		$regNames = array();
		$result = array();

		while ($arRes = $csvFile->Fetch())
		{
				$arRegName = array_shift($arRes);
				$tarifs[$arRegName] = $arRes;

				if($arRegName != 'default')
					$regNames[] = $arRegName;
		}

		if(isset($tarifs['default']))
		{
			$result[0] = array(
				"TARIFS" => self::getTariffArrayCSV($tarifs['default']),
				"REG_NAME" => Loc::getMessage('SALE_HPS_CASH_ON_DELIVERY_TARIF_DEFAULT')
			);
		}

		$regInfo = \CSaleLocation::GetRegionsIdsByNames($regNames);

		foreach ($regInfo as $regName => $regId)
		{
			$result[$regInfo[$regName]]["TARIFS"] = self::getTariffArrayCSV($tarifs[$regName]);
			$result[$regInfo[$regName]]["REG_NAME"] = $regName;
		}

		return $result;
	}
}