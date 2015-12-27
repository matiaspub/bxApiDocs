<?php

namespace Bitrix\Sale\TradingPlatform\Ebay;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Sale\TradingPlatform\MapTable;
use Bitrix\Sale\TradingPlatform\TabHandler;
use Bitrix\Sale\TradingPlatform\Ebay\MapHelper;

Loc::loadMessages(__FILE__);

/**
 * Class CatalogSectionTabHandler
 * Work with iblock section / catalog category edit page.
 * @package Bitrix\Sale\TradingPlatform\Ebay
 */
class CatalogSectionTabHandler extends TabHandler
{
	public $name = "Ebay";
	public $description = "Ebay section mappings";

	protected static $ebayCategoriesVariations = array();
	protected static $ebayRequiredVariations = array();

	static public function Action($arArgs)
	{
		$propsRes = true;

		if(!isset($_POST["SALE"]["EBAY"]))
			return false;

		$ebayCategoryId = isset($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_ID"]) ? $_POST["SALE"]["EBAY"]["EBAY_CATEGORY_ID"] : "";
		$params = array();

		if(!empty($_POST["SALE"]["EBAY"]["POLICY"]) && is_array($_POST["SALE"]["EBAY"]["POLICY"]))
		{
			$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
			$settings = $ebay->getSettings();
			$siteSettings = $settings[$arArgs["IBLOCK"]["LID"]];

			if($_POST["SALE"]["EBAY"]["POLICY"]["RETURN"] == $siteSettings["POLICY"]["RETURN"]["DEFAULT"])
				unset($_POST["SALE"]["EBAY"]["POLICY"]["RETURN"]);

			if($_POST["SALE"]["EBAY"]["POLICY"]["PAYMENT"] == $siteSettings["POLICY"]["PAYMENT"]["DEFAULT"])
				unset($_POST["SALE"]["EBAY"]["POLICY"]["PAYMENT"]);

			if($_POST["SALE"]["EBAY"]["POLICY"]["SHIPPING"] == $siteSettings["POLICY"]["SHIPPING"]["DEFAULT"])
				unset($_POST["SALE"]["EBAY"]["POLICY"]["SHIPPING"]);

			if(!empty($_POST["SALE"]["EBAY"]["POLICY"]))
				$params = array("POLICY" => $_POST["SALE"]["EBAY"]["POLICY"]);
		}

		$catRes = self::saveCategoryMap($arArgs["ID"], $arArgs["IBLOCK"]["ID"], array($ebayCategoryId), $params);

		if(
			isset($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"])
			&& is_array($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"])
			&& isset($_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"])
			&& is_array($_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"])
		)
		{
			$propsRes = self::saveCategoryPropsMap($arArgs["IBLOCK"]["ID"], $ebayCategoryId, $_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"], $_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"]);
		}

		return $catRes && $propsRes;
	}

	protected function saveCategoryPropsMap($iblockId, $ebayCategoryId, array $ebayCatVar, array $bitrixCatProps)
	{
		$result = true;
		$mapEntityId = MapHelper::getCategoryVariationEntityId($iblockId, $ebayCategoryId);
		MapTable::deleteByMapEntityId($mapEntityId);

		foreach($ebayCatVar as $key => $ebayCategoryVariation)
		{
			if(!isset($bitrixCatProps[$key]) || strlen($ebayCategoryVariation) <=0 )
				continue;

			$fields = array(
				"ENTITY_ID" => $mapEntityId,
				"VALUE_EXTERNAL" => $ebayCategoryVariation,
				"VALUE_INTERNAL" => $bitrixCatProps[$key]
			);

			$addRes = MapTable::add($fields);
			$result = $result && $addRes->isSuccess();
		}

		return $result;
	}

	protected function saveCategoryMap($bitrixCategoryId, $iblockId, array $ebayCategoriesIds, $params = array())
	{
		$result = true;
		$catMapEntId = MapHelper::getCategoryEntityId($iblockId);


		foreach($ebayCategoriesIds as $ebayCategoryId)
		{
			if(strlen(trim($ebayCategoryId)) <= 0)
				continue;

			$fields = array(
				"ENTITY_ID" => $catMapEntId,
				"VALUE_INTERNAL" => $bitrixCategoryId
			);

			$dbRes = MapTable::getList(array(
				'filter' => $fields
			));

			$fields["VALUE_EXTERNAL"] = $ebayCategoryId;
			if(!empty($params))
				$fields["PARAMS"] = $params;

			if($map = $dbRes->fetch())
				$res = MapTable::update($map['ID'], $fields);
			else
				$res = MapTable::add($fields);

			$result = $result && $res->isSuccess();
		}

		return $result;
	}

	static public function Check($arArgs)
	{
		if(!isset($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"]) || !is_array($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"]))
			return true;

		$result = true;

		$categoriesVarResult = CategoryVariationTable::getList( array(
			'select' => array('ID', 'NAME', 'REQUIRED'),
			'filter' => array("=CATEGORY_ID" => $_POST["SALE"]["EBAY"]["EBAY_CATEGORY_ID"]),
		));

		while($var = $categoriesVarResult->fetch())
		{
			if($var['REQUIRED'] == 'Y')
			{
				if(!in_array($var["ID"], $_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"]))
				{
					$result = false;
					break;
				}

				$variationIdx = array_search($var["ID"], $_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"]);

				if($variationIdx === false
				||!isset($_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"][$variationIdx])
				|| strlen($_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"][$variationIdx]) <=0 )
				{
					$result = false;
					break;
				}
			}
		}

		if(!$result)
			throw new SystemException("All required variations of category must be filled!");

		return $result;
	}

	static public function ShowTabSection($divName, $arArgs, $bVarsFromForm)
	{
		$ebay = \Bitrix\Sale\TradingPlatform\Ebay\Ebay::getInstance();
		$settings = $ebay->getSettings();

		if(empty($settings[$arArgs["IBLOCK"]["LID"]]))
			return '';

		$siteSettings = $settings[$arArgs["IBLOCK"]["LID"]];

		global $APPLICATION;

		//categories
		$resultHtml =
			'<tr>'.
				'<td width="20%" valign="top">'.Loc::getMessage("SALE_EBAY_CSTH_CATEGORY").':</td>'.
				'<td width="80%">';

		$catMapRes = \Bitrix\Sale\TradingPlatform\MapTable::getList(array(
			"filter" => array(
				"ENTITY_ID" => \Bitrix\Sale\TradingPlatform\Ebay\MapHelper::getCategoryEntityId($arArgs["IBLOCK"]["ID"]),
				"VALUE_INTERNAL" => $arArgs["ID"]
			)
		));

		$arMapRes = $catMapRes->fetch();

		$params = array(
			"TOP_CATEGORY_SELECT_NAME" => "SALE[EBAY][TOP_CATEGORY]",
			"CATEGORY_INPUT_NAME" => "SALE[EBAY][EBAY_CATEGORY_ID]",
			"EBAY_CATEGORY_VARIATIONS_SN" => "SALE[EBAY][EBAY_CATEGORY_VARIATIONS]",
			"BITRIX_CATEGORY_PROPS_SN" => "SALE[EBAY][BITRIX_CATEGORY_PROPS]",
			"BITRIX_CATEGORY_ID"=> $arArgs["ID"],
			"IBLOCK_ID" => $arArgs["IBLOCK"]["ID"]
		);


		if(isset($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_ID"]))
			$params["EBAY_CATEGORY_ID"] = $_POST["SALE"]["EBAY"]["EBAY_CATEGORY_ID"];
		elseif(isset($arMapRes["VALUE_EXTERNAL"]))
			$params["EBAY_CATEGORY_ID"] = $arMapRes["VALUE_EXTERNAL"];

		if(isset($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"]) && isset($_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"]))
		{
			$params["VARIATIONS_VALUES"] = array();

			for($i=0, $l=count($_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"]); $i<$l; $i++)
				$params["VARIATIONS_VALUES"][$_POST["SALE"]["EBAY"]["EBAY_CATEGORY_VARIATIONS"][$i]] = $_POST["SALE"]["EBAY"]["BITRIX_CATEGORY_PROPS"][$i];
		}

		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:sale.ebay.categories",
			".default",
			$params
		);

		$resultHtml .= ob_get_contents();
		ob_end_clean();

		$resultHtml .=
				'</td>'.
			'</tr>';

		//policies
		$errorMsg = "";
		$policy = null;
		$policyReturn = "";
		$policyPayment = "";
		$policyShipping = "";

		if(isset($_POST["SALE"]["EBAY"]["POLICY"]))
		{
			if(!empty($_POST["SALE"]["EBAY"]["POLICY"]["RETURN"]))
				$policyReturn = $_POST["SALE"]["EBAY"]["POLICY"]["RETURN"];

			if(!empty($_POST["SALE"]["EBAY"]["POLICY"]["SHIPPING"]))
				$policyShipping = $_POST["SALE"]["EBAY"]["POLICY"]["SHIPPING"];

			if(!empty($_POST["SALE"]["EBAY"]["POLICY"]["PAYMENT"]))
				$policyPayment = $_POST["SALE"]["EBAY"]["POLICY"]["PAYMENT"];
		}
		elseif(!empty($arMapRes["PARAMS"]["POLICY"]))
		{
			if(!empty($arMapRes["PARAMS"]["POLICY"]["RETURN"]))
				$policyReturn = $arMapRes["PARAMS"]["POLICY"]["RETURN"];

			if(!empty($arMapRes["PARAMS"]["POLICY"]["SHIPPING"]))
				$policyShipping = $arMapRes["PARAMS"]["POLICY"]["SHIPPING"];

			if(!empty($arMapRes["PARAMS"]["POLICY"]["PAYMENT"]))
				$policyPayment = $arMapRes["PARAMS"]["POLICY"]["PAYMENT"];
		}

		if(strlen($policyReturn) <= 0 && !empty($siteSettings["POLICY"]["RETURN"]["DEFAULT"]))
			$policyReturn = $siteSettings["POLICY"]["RETURN"]["DEFAULT"];

		if(strlen($policyShipping) <= 0 && !empty($siteSettings["POLICY"]["SHIPPING"]["DEFAULT"]))
			$policyShipping = $siteSettings["POLICY"]["SHIPPING"]["DEFAULT"];

		if(strlen($policyPayment) <= 0 && !empty($siteSettings["POLICY"]["PAYMENT"]["DEFAULT"]))
			$policyPayment = $siteSettings["POLICY"]["PAYMENT"]["DEFAULT"];

		if(isset($siteSettings["API"]["AUTH_TOKEN"]) && strlen($siteSettings["API"]["AUTH_TOKEN"]) > 0)
			$policy = new \Bitrix\Sale\TradingPlatform\Ebay\Policy($siteSettings["API"]["AUTH_TOKEN"], $arArgs["IBLOCK"]["LID"]);
		else
			$errorMsg .= "You must set API token first!\n";

		$resultHtml .= '
			<tr></tr><td colspan="2" style="border-top: 2px solid #e0e8ea;">&nbsp;</td></tr>
			<tr>
				<td>'.Loc::getMessage("SALE_EBAY_CSTH_POLICY_RETURN").':</span></td>
				<td>
					<select name="SALE[EBAY][POLICY][RETURN]">';

		if($policy)
		{
			foreach($policy->getPoliciesNames(\Bitrix\Sale\TradingPlatform\Ebay\Policy::TYPE_RETURN) as $policyId => $policyName)
				$resultHtml .= '<option value="'.htmlspecialcharsbx($policyId).'"'.($policyReturn == $policyId ? " selected" : "").'>'.$policyName.'</option>';
		}

		$resultHtml .= '
					</select>
				</td>
			</tr>
			<tr>
				<td>'.Loc::getMessage("SALE_EBAY_CSTH_POLICY_SHIPMENT").':</td>
				<td>
					<select name="SALE[EBAY][POLICY][SHIPPING]">';
		if($policy)
		{
			foreach($policy->getPoliciesNames(\Bitrix\Sale\TradingPlatform\Ebay\Policy::TYPE_SHIPPING) as $policyId => $policyName)
				$resultHtml .= '<option value="'.htmlspecialcharsbx($policyId).'"'.($policyShipping == $policyId ? " selected" : "").'>'.$policyName.'</option>';
		}

		$resultHtml .='
					</select>
				</td>
			</tr>
			<tr>
				<td>'.Loc::getMessage("SALE_EBAY_CSTH_POLICY_PAYMENT").':</td>
				<td>
					<select name="SALE[EBAY][POLICY][PAYMENT]">';

		if($policy)
		{
			foreach($policy->getPoliciesNames(\Bitrix\Sale\TradingPlatform\Ebay\Policy::TYPE_PAYMENT) as $policyId => $policyName)
				$resultHtml .= '<option value="'.htmlspecialcharsbx($policyId).'"'.($policyPayment == $policyId ? " selected" : "").'>'.$policyName.'</option>';
		}

		$resultHtml .='	</select>
				</td>
			</tr>
			<tr>
				<td>&nbsp</td><td><a href="http://www.bizpolicy.ebay.ru/businesspolicy/manage?totalPages=1">'.Loc::getMessage('SALE_EBAY_CSTH_EDIT_POLICIES').'</a></td>
			</tr>';

		return $resultHtml;
	}
}