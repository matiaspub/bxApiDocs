<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Sale\Helpers\Admin\OrderEdit;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;

Loc::loadMessages(__FILE__);

class OrderBuyer
{
	public static function getEdit(Order $order, $showProfiles = false)
	{
		$data = self::prepareData($order);

		$result =  '
			<div class="adm-bus-table-container">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">
					<tbody>
						<tr'.(intval($data["USER_ID"]) > 0 && $data["USER_ID"] != \CSaleUser::GetAnonymousUserID() ? ' style="display: none"': '' ).' id="sale-order-buyer-find-button-wrap">
							<td class="adm-detail-content-cell-l fwb" width="40%">
								&nbsp;
							</td>
							<td class="adm-detail-content-cell-r">
								<input type="button" name="FIND_BUYER" value="'.Loc::getMessage("SALE_ORDER_BUYER_FIND").'" onclick="BX.Sale.Admin.OrderBuyer.showChooseBuyerWindow(\''.LANGUAGE_ID.'\')"><br>
								<i>'.Loc::getMessage("SALE_ORDER_BUYER_START_TO_CREATE").':</i>
							</td>
						</tr>
						<tr'.(intval($data["USER_ID"]) <= 0 || $data["USER_ID"] == \CSaleUser::GetAnonymousUserID() ? ' style="display: none"': '' ).' id="sale-order-buyer-name-wrap">
							<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage("SALE_ORDER_BUYER").':</td>
							<td class="adm-detail-content-cell-r">
								<div class="adm-s-order-person-choose">
									<a id="BUYER_USER_NAME" href="'.$data["BUYER_URL"].'">'.
										htmlspecialcharsbx($data["BUYER_USER_NAME"]).
									'</a>&nbsp;
									<a class="adm-s-bus-morelinkqhsw" onclick="BX.Sale.Admin.OrderBuyer.showChooseBuyerWindow(\''.LANGUAGE_ID.'\')" href="javascript:void(0);">
										'.Loc::getMessage("SALE_ORDER_BUYER_CHANGE").'
									</a>&nbsp;
									<a class="adm-s-bus-morelinkqhsw" onclick="BX.Sale.Admin.OrderBuyer.clearBuyer();" href="javascript:void(0);">
										'.Loc::getMessage("SALE_ORDER_BUYER_CLEAR").'
									</a>
									<input type="hidden" name="USER_ID" id="USER_ID" value="'.$data["USER_ID"].'" onchange="BX.Sale.Admin.OrderBuyer.onBuyerIdChange(this);">
								</div>
							</td>
						</tr>
						<tr>
							<td class="adm-detail-content-cell-l fwb">'.Loc::getMessage("SALE_ORDER_BUYER_PAYER_TYPE").':</td>
							<td class="adm-detail-content-cell-r">'.
									\Bitrix\Sale\Helpers\Admin\OrderEdit::makeSelectHtml(
									"PERSON_TYPE_ID",
									self::getBuyerTypesList($order->getSiteId()),
									isset($data["PERSON_TYPE_ID"]) ? $data["PERSON_TYPE_ID"] : "",
									false,
									array(
										"class" => "adm-bus-select",
										"id" => "PERSON_TYPE_ID",
										"onchange" => "BX.Sale.Admin.OrderBuyer.onBuyerTypeChange(this.value);"
									)
								).
							'</td>
						</tr>
						<tr id="sale-order-buyer-profiles-list-row"'.($showProfiles ? '' : ' style="display:none;"').'>
							<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BUYER_CHOOSE_PROFILE").':</td>
							<td class="adm-detail-content-cell-r"><div id="BUYER_PROFILE_ID_CONTAINER">';
		if($showProfiles)
		{
			$result .=	\Bitrix\Sale\Helpers\Admin\OrderEdit::makeSelectHtml(
							"BUYER_PROFILE_ID",
							self::getBuyerProfilesList($data["USER_ID"], $data["PERSON_TYPE_ID"]),
							isset($data["BUYER_PROFILE_ID"]) ? $data["BUYER_PROFILE_ID"] : "",
							false,
							array(
								"class" => "adm-bus-select",
								"id" => "BUYER_PROFILE_ID",
								"onchange" => "BX.Sale.Admin.OrderBuyer.onBuyerProfileChange();"
							)
						);
		}

		$result .=			'</div></td>
						</tr>
					</tbody>
				</table>
			</div>'.
			'<div id="order_properties_container"></div>'.
			'<div>'.self::getOrderPropertiesByJS($order).'</div>'.
			'<div class="adm-bus-table-container caption border sale-order-props-group">'.
				'<div class="adm-bus-table-caption-title">'.Loc::getMessage("SALE_ORDER_BUYER_COMMENT").'</div>
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
					<tbody>
						<tr>
							<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage("SALE_ORDER_BUYER_ORDERCOMMENT").':</td>
							<td class="adm-detail-content-cell-r">
								<textarea style="width:400px;min-height:100px;" name="USER_DESCRIPTION" id="USER_DESCRIPTION">'.
									$data["USER_DESCRIPTION"].
								'</textarea>
							</td>
						</tr>
					</tbody>
				</table>
			</div>';

		return $result;
	}
	public static  function getPropsEdit(Order $order)
	{
		$result = '
				<div>
					<div id="order_properties_container_add"></div>'.
					'<div>'.self::getOrderRelPropertiesByJS($order).'</div>'.
				'</div>';

		return $result;
	}

	public static function getView(Order $order)
	{
		$data = self::prepareData($order);
		$buyersList = self::getBuyerTypesList($order->getSiteId());

		return '
			<div class="adm-bus-table-container">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">
					<tbody>
					<tr>
						<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage("SALE_ORDER_BUYER").':</td>
						<td class="adm-detail-content-cell-r">
							<div>
									<a href="'.$data["BUYER_URL"].'">'.
									htmlspecialcharsbx($data["BUYER_USER_NAME"]).
								'</a>
							</div>
						</td>
					</tr>
					<tr>
						<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_BUYER_PAYER_TYPE").':</td>
						<td class="adm-detail-content-cell-r">'.$buyersList[$data["PERSON_TYPE_ID"]].
						'</td>
					</tr>
					</tbody>
				</table>
			</div>'.
		self::getOrderPropertiesHtml($order, true).
		'<div class="adm-bus-table-container caption border sale-order-props-group">'.
		'<div class="adm-bus-table-caption-title">'.Loc::getMessage("SALE_ORDER_BUYER_COMMENT").'</div>
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
					<tbody>
						<tr>
							<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage("SALE_ORDER_BUYER_ORDERCOMMENT").':</td>
							<td class="adm-detail-content-cell-r">'.(strlen($data["USER_DESCRIPTION"]) > 0 ? htmlspecialcharsbx($data["USER_DESCRIPTION"]) : Loc::getMessage("SALE_ORDER_BUYER_NO")).'</td>
						</tr>
					</tbody>
				</table>
			</div>';

	}

	public static function getScripts()
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_buyer.js");
		\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/sale/input.js');
		\Bitrix\Sale\PropertyValueCollection::initJs();
		$langPhrases = array("SALE_ORDER_BUYER_CREATE_NEW");

		$result = '<script type="text/javascript">'.
			'BX.Sale.Admin.OrderBuyer.isFeatureSaleAccountsEnabled = '.(\CBXFeatures::IsFeatureEnabled('SaleAccounts') ? 'true' : 'false').';';

		foreach($langPhrases as $phrase)
			$result .= ' BX.message({'.$phrase.': "'.\CUtil::jsEscape(Loc::getMessage($phrase)).'"});';

		$result .= '
				BX.ready(function(){
					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( BX.Sale.Admin.OrderBuyer.getFieldsUpdaters() );
				});
			</script>
		';

		return $result;
	}

	public static function getDefaultPersonType($siteId)
	{
		$personTypes = self::getBuyerTypesList($siteId);
		reset($personTypes);
		return key($personTypes);
	}

	public static function 	prepareData(Order $order)
	{
		if (\CBXFeatures::IsFeatureEnabled('SaleAccounts'))
		{
			$strBuyerProfileUrl = '/bitrix/admin/sale_buyers_profile.php?USER_ID='.$order->getUserId().'&lang='.LANGUAGE_ID;
		}
		else
		{
			$strBuyerProfileUrl = '/bitrix/admin/user_edit.php?ID='.$order->getUserId().'&lang='.LANGUAGE_ID;
		}

		$result = array(
			"USER_ID" => $order->getUserId(),
			"PERSON_TYPE_ID" => $order->getPersonTypeId(),
			"BUYER_USER_NAME" => OrderEdit::getUserName($order->getUserId()),
			"USER_DESCRIPTION" => $order->getField("USER_DESCRIPTION"),
			"BUYER_URL" => $strBuyerProfileUrl
		);

		return $result;
	}

	public static function getBuyerTypesList($siteId)
	{
		static $result = array();

		if(!isset($result[$siteId]))
		{
			$result[$siteId] = array();
			$dbPersonType = \CSalePersonType::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array("ACTIVE" => "Y", "LID"=> $siteId));

			while ($personType = $dbPersonType->GetNext())
				$result[$siteId][$personType["ID"]] = $personType["NAME"]." [".$personType["ID"]."]";
		}

		return $result[$siteId];
	}

	public static function getProfileParams($userId, $profileId)
	{
		$profiles = \CSaleOrderUserProps::DoLoadProfiles($userId);

		if(is_array($profiles))
			foreach($profiles as $types)
				foreach($types as $key => $value)
				{
					if(isset($value["VALUES_ORIG"]) && !empty($value["VALUES_ORIG"]))
					{
						$value["VALUES"] = $value["VALUES_ORIG"];
						unset($value["VALUES_ORIG"]);
					}

					if($key == $profileId && isset($value["VALUES"]))
						return $value["VALUES"];
				}

		return array();
	}

	public static function getUserProfiles($userId)
	{
		if(intval($userId) <=0)
			return array();

		$result = array();
		$profiles = \CSaleOrderUserProps::DoLoadProfiles($userId);

		if(is_array($profiles))
		{
			foreach($profiles as $typeId => $types)
			{
				if(!isset($result[$typeId]))
					$result[$typeId] = array();

				foreach($types as $key => $value)
				{
					if(!isset($result[$typeId][$key]))
						$result[$typeId][$key] = array();

					if(isset($value["VALUES_ORIG"]) && !empty($value["VALUES_ORIG"]))
					{
						$value["VALUES"] = $value["VALUES_ORIG"];
						unset($value["VALUES_ORIG"]);
					}

					if(isset($value["VALUES"]))
						$result[$typeId][$key] = htmlspecialcharsback($value["VALUES"]);
				}
			}
		}

		return $result;
	}

	public static function getBuyerProfilesList($userId, $personTypeId = null)
	{
		$result = array(0 => Loc::getMessage("SALE_ORDER_BUYER_CREATE_NEW"));

		if(intval($userId) > 0)
		{
			$profiles = \CSaleOrderUserProps::DoLoadProfiles($userId);

			if(is_array($profiles))
				foreach($profiles as $types)
					foreach($types as $key => $value)
						$result[$key] = htmlspecialcharsback($value["NAME"]);
		}

		return $result;
	}

	public static function getOrderPropertiesHtml(Order $order, $readonly = false)
	{
		$propertyCollection = $order->getPropertyCollection();
		$result = "";

		foreach ($propertyCollection->getGroups() as $group)
		{
			$result .= '<div class="adm-bus-table-container caption border sale-order-props-group">
				<div class="adm-bus-table-caption-title">'.htmlspecialcharsbx($group['NAME']).'</div>
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
					<tbody>';
			/** @var \Bitrix\Sale\PropertyValue $property */
			foreach ($propertyCollection->getGroupProperties($group['ID']) as $property)
			{
				$propertyValue = $property->getValue();
				if ($readonly && empty($propertyValue))
					continue;

				$result .= '
					<tr>
						<td class="adm-detail-content-cell-l" width="40%" valign="top">'.htmlspecialcharsbx($property->getName()).':</td>
						<td class="adm-detail-content-cell-r"><div>'.(($readonly) ? $property->getViewHtml() : $property->getEditHtml()).'</div></td>
					</tr>';
			}

		$result .= '
					</tbody>
				</table>
			</div>';
		}

		return $result;
	}

	public static function getRelPropData(Order $order)
	{
		$result = array();
		$groups = array();

		$items = $order->getPropertyCollection()->getArray();
		foreach ($items as $key => $item)
		{
			if ($key == 'properties')
			{
				$result[$key] = array();
				foreach ($item as $property)
				{
					if (isset($property['RELATION']))
					{
						foreach ($property['RELATION'] as $relation)
						{
							if (
								in_array($relation['ENTITY_ID'], $order->getPaymentSystemId())
								||
								in_array($relation['ENTITY_ID'], $order->getDeliverySystemId())
							)
							{
								$result[$key][] = $property;
								$groups[$property['PROPS_GROUP_ID']] = true;
								break;
							}
						}
					}
				}
			}
			else
			{
				$result[$key] = $item;
			}
		}

		foreach ($result['groups'] as $i => $group)
		{
			if (!isset($groups[$group['ID']]))
				unset($result['groups'][$i]);
		}

		return $result;
	}

	public static function getNotRelPropData(Order $order)
	{
		$result = array();
		$groups = array();

		$items = $order->getPropertyCollection()->getArray();
		foreach ($items as $key => $item)
		{
			if ($key == 'properties')
			{
				$result[$key] = array();
				foreach ($item as $property)
				{
					if (!isset($property['RELATION']))
					{
						$result[$key][] = $property;
						$groups[$property['PROPS_GROUP_ID']] = true;
					}
				}
			}
			else
			{
				$result[$key] = $item;
			}
		}

		foreach ($result['groups'] as $i => $group)
		{
			if (!isset($groups[$group['ID']]))
				unset($result['groups'][$i]);
		}

		return $result;
	}

	public static function getOrderPropertiesByJS(Order $order)
	{
		if ($order->getId() > 0)
			$result = $order->getPropertyCollection()->getArray();
		else
			$result = self::getNotRelPropData($order);

		return '
			<script type="text/javascript">
				BX.ready(function(){
					BX.Sale.Admin.OrderBuyer.setOrderPropsArray('.\CUtil::PhpToJSObject($result).');
				});
			</script>
		';
	}

	public static function getOrderRelPropertiesByJS(Order $order)
	{
		$result = self::getRelPropData($order);

		return '
			<script type="text/javascript">
				BX.ready(function(){
					BX.Sale.Admin.OrderBuyer.setOrderRelPropsArray('.\CUtil::PhpToJSObject($result).');

					var updater = [];
					updater["RELATED_PROPS"] = {
						callback: BX.Sale.Admin.OrderBuyer.setOrderRelPropsArray,
						context: this
					};

					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters(updater);
				});
			</script>
		';
	}
}