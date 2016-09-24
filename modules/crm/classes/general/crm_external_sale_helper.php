<?php
class CCrmExternalSaleHelper
{
	public static function PrepareListItems()
	{
		$ary = array();

		$rsSaleSettings = CCrmExternalSale::GetList(array('NAME' => 'ASC', 'SERVER' => 'ASC'), array('ACTIVE' => 'Y'));
		while($arSaleSetting = $rsSaleSettings->Fetch())
		{
			$saleSettingsID = $arSaleSetting['ID'];
			$saleSettingName = isset($arSaleSetting['NAME']) ? strval($arSaleSetting['NAME']) : '';
			if(!isset($saleSettingName[0]) && isset($arSaleSetting['SERVER']))
			{
				$saleSettingName = $arSaleSetting['SERVER'];
			}

			if(!isset($saleSettingName[0]))
			{
				$saleSettingName = $saleSettingsID;
			}
			$ary[$saleSettingsID] = $saleSettingName;
		}

		return $ary;
	}
}
