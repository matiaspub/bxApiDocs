<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Type\Date;
use Bitrix\Sale\Delivery\CalculationResult;
use Bitrix\Sale\Helpers\Admin\OrderEdit;
use Bitrix\Sale\Delivery\Services;
use Bitrix\Sale\Delivery\Restrictions;
use Bitrix\Sale\DeliveryStatus;
use Bitrix\Sale\Order;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Result;
use Bitrix\Main\Entity\EntityError;
use Bitrix\Main;
use Bitrix\Sale\Services\Company\Manager;
use Bitrix\Sale\Shipment;

Loc::loadMessages(__FILE__);

class OrderShipment
{
	public static $shipmentObjJs = null;
	/** @var \Bitrix\Sale\Shipment */
	protected static $shipment = null;
	protected static $defaultFields = null;
	protected static $backUrl = '';

	public static function getEditTemplate($data, $index, $formType, $post)
	{
		global $USER;

		$index++;

		static $items = null;
		if (is_null($items))
			$items = self::getDeliveryServiceList();

		if (!isset($items[$data['DELIVERY_ID']]))
		{
			$delivery = self::getDeliveryServiceInfoById($data['DELIVERY_ID']);
			if ($delivery)
				$items[$delivery['ID']] = $delivery;
		}

		static $deliveries = null;
		if (is_null($deliveries))
			$deliveries = self::makeDeliveryServiceTree($items);

		$deliveryId = 0;
		$profileId = 0;

		if (isset($post['DELIVERY_ID']))
		{
			if (isset($post['PROFILE']))
				$data['DELIVERY_ID'] = $post['PROFILE'];
			else
				$data['DELIVERY_ID'] = $post['DELIVERY_ID'];
		}

		$profiles = array();
		if ($data['DELIVERY_ID'])
		{
			$deliveryId = $data['DELIVERY_ID'];
			$service = Services\Manager::getObjectById($deliveryId);
			if ($service && $service->getParentService())
			{
				$profileId = $deliveryId;
				$deliveryId = $service->getParentService()->getId();

				$profiles = self::getDeliveryServiceProfiles($deliveryId);
				if (!$profiles)
					unset($deliveries[$deliveryId]);
			}
			else if ($service && $service->canHasProfiles())
			{
				unset($deliveries[$deliveryId]);
			}
		}

		if (isset($post['ALLOW_DELIVERY']))
			$data['ALLOW_DELIVERY'] = $post['ALLOW_DELIVERY'];

		$allowedStatusesDelivery = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('delivery'));
		$isAllowDelivery = in_array($data["STATUS_ID"], $allowedStatusesDelivery);

		$class = ($data['ALLOW_DELIVERY'] == 'Y') ? '' : 'notdelivery';
		$class .= ($isAllowDelivery) ? '' : ' not_active';
		$status = ($data['ALLOW_DELIVERY'] == 'Y') ? 'YES' : 'NO';
		$triangle = ($class === '') ? '<span class="triangle"> &#9662;</span>' : '';

		$allowDelivery = '<span><span id="BUTTON_ALLOW_DELIVERY_'.$index.'" class="'.$class.'">'.Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_'.$status).'</span>'.$triangle.'</span>';

		$allowedStatusesDeduction = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('deduction'));
		$isAllowDeduction = in_array($data["STATUS_ID"], $allowedStatusesDeduction);

		if (isset($post['DEDUCTED']) && $isAllowDeduction)
			$data['DEDUCTED'] = $post['DEDUCTED'];

		$class = ($data['DEDUCTED'] == 'Y') ? '' : 'notdeducted';
		$class .= ($isAllowDeduction) ? '' : ' not_active';
		$status = ($data['DEDUCTED'] == 'Y') ? 'YES' : 'NO';
		$triangle = ($class === '') ? '<span class="triangle"> &#9662;</span>' : '';

		$deducted = '<span><span id="BUTTON_DEDUCTED_'.$index.'" class="'.$class.'">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED_'.$status).'</span>'.$triangle.'</span>';

		$lang = Main\Application::getInstance()->getContext()->getLanguage();
		$map = '';
		$extraServiceHTML = '';
		$extraServiceManager = new \Bitrix\Sale\Delivery\ExtraServices\Manager($data['DELIVERY_ID']);
		$extraServiceManager->setOperationCurrency($data['CURRENCY']);
		if (isset($post['EXTRA_SERVICES']))
			$data['EXTRA_SERVICES'] = $post['EXTRA_SERVICES'];
		if (isset($post['EXTRA_SERVICES']))
			$data['DELIVERY_STORE_ID'] = $post['DELIVERY_STORE_ID'];

		if ($data['EXTRA_SERVICES'])
			$extraServiceManager->setValues($data['EXTRA_SERVICES']);
		$extraService = $extraServiceManager->getItems();
		if ($extraService)
			$extraServiceHTML = self::getExtraServiceEditControl($extraService, $index, false, self::$shipment);

		if ($data['DELIVERY_ID'] > 0)
			$map = self::getMap($data['DELIVERY_ID'], $index, $data['DELIVERY_STORE_ID']);

		if ($data['ID'] > 0)
		{
			$dateInsert = new Date($data['DATE_INSERT']);
			$title = Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_EDIT_SHIPMENT_TITLE', array("#ID#" => $data['ID'], '#DATE_INSERT#' => $dateInsert));
		}
		else
		{
			$title = Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_NEW_SHIPMENT_TITLE');
		}

		$curFormat = \CCurrencyLang::getCurrencyFormat($data['CURRENCY']);
		$currencyLang = preg_replace("/(^|[^&])#/", '$1', $curFormat["FORMAT_STRING"]);

		$customPriceDelivery = isset($post['CUSTOM_PRICE_DELIVERY']) ? $post['CUSTOM_PRICE_DELIVERY'] : $data['CUSTOM_PRICE_DELIVERY'];

		$basePriceDelivery = round((isset($post['BASE_PRICE_DELIVERY']) ? $post['BASE_PRICE_DELIVERY'] : $data['BASE_PRICE_DELIVERY']), 2);
		$priceDelivery = round((isset($post['PRICE_DELIVERY']) ? $post['PRICE_DELIVERY'] : $data['PRICE_DELIVERY']), 2);

		$blockProfiles = '';
		if ($profileId > 0 && $profiles)
		{
			$profiles = self::checkProfilesRestriction($profiles, self::$shipment);

			$profilesTemplate = self::getProfileEditControl($profiles, $index, $profileId);
			$blockProfiles = '
				<tr id="BLOCK_PROFILES_'.$index.'">
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SERVICE_PROFILE').':</td>
					<td class="adm-detail-content-cell-r" id="PROFILE_SELECT_'.$index.'">'.$profilesTemplate.'</td>
				</tr>';
		}
		$id = (isset($post['ID'])) ? $post['SHIPMENT_ID'] : $data['ID'];

		$companies =  '';

		if (!empty($data['COMPANIES']))
		{
			$companies = OrderEdit::makeSelectHtmlWithRestricted(
				'SHIPMENT['.$index.'][COMPANY_ID]',
				$data['COMPANIES'],
				isset($post["COMPANY_ID"]) ? $post["COMPANY_ID"] : $data["COMPANY_ID"],
				true,
				array(
					"class" => "adm-bus-select",
					"id" => "SHIPMENT_COMPANY_ID_".$index
				)
			);
		}
		else
		{
			global $APPLICATION;
			$saleModulePermissions = $APPLICATION->GetGroupRight("sale");
			if ($saleModulePermissions >= "W")
				$companies = str_replace("#URL#", "/bitrix/admin/sale_company_edit.php?lang=".$lang, Loc::getMessage('SALE_ORDER_SHIPMENT_ADD_COMPANY'));
		}

		if (isset($items[$data['DELIVERY_ID']]['LOGOTIP']['MAIN']))
			$logo = $items[$data['DELIVERY_ID']]['LOGOTIP']['MAIN'];
		else
			$logo = '/bitrix/images/sale/logo-default-d.gif';

		$trackingNumber = htmlspecialcharsbx(isset($post['TRACKING_NUMBER']) ? $post['TRACKING_NUMBER'] : $data['TRACKING_NUMBER']);

		$result = '
		<div class="adm-bus-pay" id="shipment_container_'.$index.'">
			<input type="hidden" name="SHIPMENT['.$index.'][SHIPMENT_ID]" id="SHIPMENT_ID_'.$index.'" value="'.$id.'">
			<input type="hidden" name="SHIPMENT['.$index.'][CUSTOM_PRICE_DELIVERY]" id="CUSTOM_PRICE_DELIVERY_'.$index.'" value="'.$customPriceDelivery.'">
			<input type="hidden" name="SHIPMENT['.$index.'][BASE_PRICE_DELIVERY]" id="BASE_PRICE_DELIVERY_'.$index.'" value="'.$data['BASE_PRICE_DELIVERY'].'">
			<input type="hidden" name="SHIPMENT['.$index.'][CALCULATED_PRICE]" id="CALCULATED_PRICE_'.$index.'" value="'.(isset($post['CALCULATED_PRICE']) ? $post['CALCULATED_PRICE'] : $data['CALCULATED_PRICE']).'">
			<input type="hidden" name="SHIPMENT['.$index.'][DEDUCTED]" id="STATUS_DEDUCTED_'.$index.'" value="'.($data['DEDUCTED'] == "" ? "N" : $data['DEDUCTED']).'">
			<input type="hidden" name="SHIPMENT['.$index.'][ALLOW_DELIVERY]" id="STATUS_ALLOW_DELIVERY_'.$index.'" value="'.($data['ALLOW_DELIVERY'] == "" ? "N" : $data['ALLOW_DELIVERY']).'">
			<div class="adm-bus-component-content-container">
				<div class="adm-bus-pay-section">
					<div class="adm-bus-pay-section-title-container">
						<div class="adm-bus-pay-section-title">'.$title.'</div>
					</div>
					<div class="adm-bus-pay-section-content">
						<div class="adm-bus-pay-section-sidebar">
							<div style="background: url(\''.$logo.'\')" id="delivery_service_logo_'.$index.'" class="adm-shipment-block-logo"></div>
							<div id="section_map_'.$index.'">'.$map.'</div>
						</div>
						<div class="adm-bus-pay-section-right">
							<div class="adm-bus-table-container caption border">
								<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SERVICE').'</div>
								<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
									<tbody>
										<tr id="BLOCK_DELIVERY_SERVICE_'.$index.'">
											<td class="adm-detail-content-cell-l fwb" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SERVICE').':</td>
											<td class="adm-detail-content-cell-r">
												'.self::getDeliverySelectHtml($deliveries, $deliveryId, $index).'
											</td>
										</tr>
										'.$blockProfiles.'
									</tbody>
								</table>
							</div>
							<div class="adm-bus-table-container caption border">
								<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_BLOCK_PRICE').'</div>
								<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
									<tbody>
										<tr style="display: none;">
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SUM_PRICE').':</td>
											<td class="adm-detail-content-cell-r tal"><span id="BASE_PRICE_DELIVERY_T_'.$index.'">'.$basePriceDelivery.'</span> '.$currencyLang.'<br></td>
										</tr>
										<tr id="sale-order-shipment-discounts-row-'.$index.'" style="display: none;">
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DISCOUNT').':</td>
											<td class="adm-detail-content-cell-r tal" id="sale-order-shipment-discounts-container-'.$index.'"></td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SUM_DISCOUNT_PRICE').':</td>
											<td class="adm-detail-content-cell-r tal"><input type="text" class="adm-bus-input-price" name="SHIPMENT['.$index.'][PRICE_DELIVERY]" id="PRICE_DELIVERY_'.$index.'" value="'.$priceDelivery.'"> '.$currencyLang.'</td>
										</tr>
									</tbody>
								</table>
							</div>';
	if ($companies)
	{
		$result .= '<div class="adm-bus-table-container caption border">
			<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT').'</div>
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_OFFICE').':</td>
						<td class="adm-detail-content-cell-r">'.$companies.'</td>
					</tr>
				</tbody>
			</table>
		</div>';
	}

	$result .= '<div class="adm-bus-table-container caption border">
		<div class="adm-bus-moreInfo_part1">
			<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_STATUS').'</div>
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY').':</td>
						<td class="adm-detail-content-cell-r delivery-status">'.$allowDelivery.'</td>
					</tr>
					'.((!empty($data['EMP_ALLOW_DELIVERY_ID'])) ? '
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%"></td>
						<td class="adm-detail-content-cell-r">
							<div>'.Loc::getMessage('SALE_ORDER_SHIPMENT_MODIFY_BY').': <span style="color: #66878F" id="order_additional_info_date_responsible">'.htmlspecialcharsbx($data['DATE_ALLOW_DELIVERY']).'</span>  <a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_ALLOW_DELIVERY_ID'].'" id="order_additional_info_emp_responsible">'.htmlspecialcharsbx($data['EMP_ALLOW_DELIVERY_ID_LAST_NAME']).' '.htmlspecialcharsbx($data['EMP_ALLOW_DELIVERY_ID_NAME']).'</a></div>
						</td>
					</tr>
					' : '').'
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED').':</td>
						<td class="adm-detail-content-cell-r deducted-status">'.$deducted.'</td>
					</tr>
					'.((!empty($data['EMP_DEDUCTED_ID'])) ? '
					<tr>
						<td class="adm-detail-content-cell-l fwb vat" width="40%"></td>
						<td class="adm-detail-content-cell-r">
							<div>'.Loc::getMessage('SALE_ORDER_SHIPMENT_MODIFY_BY').': <span style="color: #66878F" id="order_additional_info_date_responsible">'.htmlspecialcharsbx($data['DATE_DEDUCTED']).'</span>  <a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_DEDUCTED_ID'].'" id="order_additional_info_emp_responsible">'.htmlspecialcharsbx($data['EMP_DEDUCTED_ID_LAST_NAME']).' '.htmlspecialcharsbx($data['EMP_DEDUCTED_ID_NAME']).'</a></div>
						</td>
					</tr>
					' : '').'
				</tbody>
			</table>
		</div>
	</div>
	<div class="adm-bus-table-container caption border">
		<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_DELIVERY_INFO').'</div>
		<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
			<tbody>
				<tr>
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_NUMBER').':</td>
					<td class="adm-detail-content-cell-r tal"><input type="text" class="adm-bus-input" name="SHIPMENT['.$index.'][TRACKING_NUMBER]" value="'.$trackingNumber.'"><br></td>
				</tr>'.(
				$data['HAS_TRACKING'] && strlen($trackingNumber) > 0 && intval($data['ID'] > 0)
				?
				'<tr>
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_STATUS').':</td>
					<td class="adm-detail-content-cell-r tal">'.
						'<span id="sale-order-shipment-tracking-status-'.$index.'">'.(intval($data['TRACKING_STATUS']) >= 0  ? \Bitrix\Sale\Delivery\Tracking\Manager::getStatusName($data['TRACKING_STATUS']) : '-').'</span>'.
						'&nbsp;&nbsp;&nbsp;[<span onclick="BX.Sale.Admin.GeneralShipment.refreshTrackingStatus(\''.$index.'\', \''.$data['ID'].'\', true);" style="border-bottom: 1px dashed #2675d7; cursor: pointer; color: #2675d7;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_STATUS_REFRESH').'</span>]<br></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_DESCRIPTION').':</td>
					<td class="adm-detail-content-cell-r tal" id="sale-order-shipment-tracking-description-'.$index.'">'.(strlen($data['TRACKING_DESCRIPTION']) > 0 ? $data['TRACKING_DESCRIPTION'] : '-').'<br></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_LAST_CHANGE').':</td>
					<td class="adm-detail-content-cell-r tal" id="sale-order-shipment-tracking-last-change-'.$index.'">'.(strlen($data['TRACKING_LAST_CHANGE']) > 0 ? $data['TRACKING_LAST_CHANGE'] : '-').'<br></td>
				</tr>'
				:
				''
				).'<tr>
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_DOC_NUM').':</td>
					<td class="adm-detail-content-cell-r tal"><input type="text" class="adm-bus-input" name="SHIPMENT['.$index.'][DELIVERY_DOC_NUM]" value="'.htmlspecialcharsbx(isset($post['DELIVERY_DOC_NUM']) ? $post['DELIVERY_DOC_NUM'] : $data['DELIVERY_DOC_NUM']).'"><br></td>
				</tr>
				<tr>
					<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_DOC_DATE').':</td>
					<td class="adm-detail-content-cell-r tal">
						<div class="adm-input-wrap adm-calendar-second" style="display: inline-block;">
							<input type="text" class="adm-input adm-calendar-to" id="DELIVERY_DOC_DATE" name="SHIPMENT['.$index.'][DELIVERY_DOC_DATE]" size="15" value="'.htmlspecialcharsbx(isset($post['DELIVERY_DOC_DATE']) ? $post['DELIVERY_DOC_DATE'] : $data['DELIVERY_DOC_DATE']).'">
							<span class="adm-calendar-icon" title="'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_CHOOSE_DATE').'" onclick="BX.calendar({node:this, field:\'DELIVERY_DOC_DATE\', form: \'\', bTime: false, bHideTime: false});"></span>
						</div>
					</td>
				</tr>
			</tbody>
		</table>
		<div id="DELIVERY_INFO_'.$index.'">'.$extraServiceHTML.'
		</div>
	</div>';

		if(is_array($data['DELIVERY_ADDITIONAL_INFO_EDIT']) && !empty($data['DELIVERY_ADDITIONAL_INFO_EDIT']))
		{
			$result .= '<div class="adm-bus-table-container caption border">
							<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_DELIVERY_ADDITIONAL').'</div>
							<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
								<tbody><tr>';

			foreach($data['DELIVERY_ADDITIONAL_INFO_EDIT'] as $name => $params)
			{
				$result .='
					<td class="adm-detail-content-cell-l" width="40%">'.$params['LABEL'].':</td>
					<td class="adm-detail-content-cell-r" width="60%">
						'.\Bitrix\Sale\Internals\Input\Manager::getEditHtml('SHIPMENT['.$index.'][ADDITIONAL]['.$name.']', $params).'
					</td>';
			}

			$result .=	'		</tr></tbody>
							</table>
						</div>';
		}

		$result .=			'</div>
						<div class="clb"></div>
					</div>
				</div>
			</div>
		</div>';

		$srcList = self::getImgDeliveryServiceList($items);

		$params = array(
			'index' => $index,
			'id' => (int)$data['ID'],
			'isAjax' => false,
			'canAllow' => $isAllowDelivery,
			'canDeduct' => $isAllowDeduction,
			'canChangeStatus' => false,
			'src_list' => $srcList,
			'active' => true,
			'discounts' => $data["DISCOUNTS"],
			'discountsMode' =>  ($formType == "edit" ? "view" : "edit"),
			'templateType' => 'edit'
		);

		if ($customPriceDelivery == 'Y')
			$params['calculated_price'] = $data['CALCULATED_PRICE'];

		$result .= self::initJsShipment($params);
		return $result;
	}

	static public function getImgDeliveryServiceList($items)
	{
		$srcList = array();
		foreach ($items as $item)
			$srcList[$item['ID']] = $item['LOGOTIP'];
		return $srcList;
	}

	public static function getDeliveryServiceProfiles($parentId)
	{
		return Services\Manager::getByParentId($parentId);
	}

	public static function initJsShipment($params)
	{
		self::$shipmentObjJs = 'obShipment_'.$params['index'];

		return "<script>
					BX.ready(function() {
						var ".self::$shipmentObjJs." = new BX.Sale.Admin.OrderShipment(".\CUtil::PhpToJSObject($params).");
						if (BX.Sale.Admin.ShipmentBasketObj)
							BX.Sale.Admin.ShipmentBasketObj.shipment = ".self::$shipmentObjJs.";
					});
				</script>";
	}

	private static function getDeliveryServiceInfoById($id)
	{
		$service = null;

		if ($id > 0)
		{
			$resService = \Bitrix\Sale\Delivery\Services\Table::getList(array(
				'filter' => array('ID' => $id),
				'order' => array('SORT' => 'ASC', 'NAME' => 'ASC'),
				'select' => array("ID", "NAME", "DESCRIPTION", "LOGOTIP", "CLASS_NAME", "PARENT_ID", "CONFIG")
			));
			$service = $resService->fetch();
		}

		return $service;
	}

	public static function getDeliveryServiceList($shipment = null)
	{
		static $result = null;
		$logoPath ='/bitrix/images/sale/logo-default-d.gif';

		if($result === null)
		{
			if ($shipment != null)
				self::$shipment = $shipment;

			$result = array(
				array(
					'ID' => 0,
					'PARENT_ID' => 0,
					'NAME' => Loc::getMessage('SALE_ORDER_PAYMENT_NO_DELIVERY_SERVICE'),
					'LOGOTIP' => array(
						'MAIN' => $logoPath,
						'SHORT' =>  $logoPath
					)
				)
			);

			$deliveryList = Services\Manager::getRestrictedList(
				self::$shipment,
				Restrictions\Manager::MODE_MANAGER,
				array(
					Services\Manager::SKIP_CHILDREN_PARENT_CHECK,
					Services\Manager::SKIP_PROFILE_PARENT_CHECK
				)
			);

			foreach ($deliveryList as $delivery)
			{
				$service = Services\Manager::getObjectById($delivery['ID']);

				if(!$service)
					continue;

				if($shipment && !$service->isCompatible($shipment))
					continue;

				if ($service->canHasProfiles())
				{
					$profiles = $service->getProfilesList();
					if (empty($profiles))
						continue;
				}

				if (!empty($delivery['LOGOTIP']))
				{
					$mainLogo = self::getMainImgPath($delivery['LOGOTIP']);
					$shortLogo = self::getShortImgPath($delivery['LOGOTIP']);
					$delivery['LOGOTIP'] = array(
						'MAIN' => $mainLogo['src'],
						'SHORT' =>  $shortLogo['src']
					);
				}
				else
				{
					$delivery['LOGOTIP'] = array(
						'MAIN' => $logoPath,
						'SHORT' =>  $logoPath
					);
				}
				$result[$delivery['ID']] = $delivery;
			}
		}

		return $result;
	}

	private static function getMainImgPath($logotip)
	{
		return \CFile::ResizeImageGet(
			$logotip,
			array('width'=>100, 'height'=>60)
		);
	}

	private static function getShortImgPath($logotip)
	{
		return \CFile::ResizeImageGet(
			$logotip,
			array('width'=>80, 'height'=>50)
		);
	}

	public static function getExtraServiceEditControl($extraService, $index, $view = false, Shipment $shipment = null)
	{
		ob_start();
		echo '<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table" id="BLOCK_EXTRA_SERVICE_'.$index.'">';
		echo '<tbody>';

		/**
		 * @var  $itemId
		 * @var \Bitrix\Sale\Delivery\ExtraServices\Base $item
		 */
		foreach ($extraService as $itemId => $item)
		{
			if (!$item->canManagerEditValue())
				continue;

			echo '<tr><td class="adm-detail-content-cell-l" width="40%">'.htmlspecialcharsbx($item->getName()).':</td>';
			echo '<td class="adm-detail-content-cell-r tal">';

			if ($view)
				echo $item->getViewControl();
			else
				echo $item->getEditControl('SHIPMENT['.$index.'][EXTRA_SERVICES]['.$itemId.']');

			$order = self::$shipment->getCollection()->getOrder();
			$currency = $order->getCurrency();
			$price = $item->getPriceShipment($shipment);

			if($price)
				echo ' ('.SaleFormatCurrency(floatval($price), $currency).')';

			echo '</td></tr>';
		}
		echo '</tbody></table>';

		$result = ob_get_contents();
		ob_end_clean();

		return $result;
	}
	/**
	 * @param $shipment
	 * @param int $index
	 * @param string $formType
	 * @param array $dataForRecovery
	 * @return string
	 */
	public static function getEdit($shipment, $index = 0, $formType = '', $dataForRecovery = array())
	{
		self::$shipment = $shipment;
		$data = self::prepareData(!empty($dataForRecovery));
		$data['COMPANIES'] = Manager::getListWithRestrictions($shipment, \Bitrix\Sale\Services\Company\Restrictions\Manager::MODE_MANAGER);
		$result = self::getEditTemplate($data, $index, $formType, $dataForRecovery);

		return $result;
	}

	public static function modifyData($data)
	{
		$order = self::$shipment->getCollection()->getOrder();

		foreach ($data as &$item)
		{
			$item['ID'] = $item['SHIPMENT_ID'];
			if ($item['PROFILE'] && $item['PROFILE'] > 0)
			{
				$item['DELIVERY_ID'] = $item['PROFILE'];
				unset($item['PROFILE']);
			}
			$item['CURRENCY'] = $order->getCurrency();
		}
		unset($item);

		return array('SHIPMENT' => $data);
	}

	protected static function getStoresList($deliveryId)
	{
		$result = array();

		if(!\Bitrix\Main\Loader::includeModule('catalog'))
			return $result;

		$storesIds = \Bitrix\Sale\Delivery\ExtraServices\Manager::getStoresList($deliveryId);

		if(!empty($storesIds))
		{
			$dbList = \CCatalogStore::GetList(
				array("SORT" => "DESC", "ID" => "DESC"),
				array("ACTIVE" => "Y", "ISSUING_CENTER" => "Y", "ID" => $storesIds),
				false,
				false,
				array("ID", "SITE_ID", "TITLE", "ADDRESS", "DESCRIPTION", "IMAGE_ID", "PHONE", "SCHEDULE", "LOCATION_ID", "GPS_N", "GPS_S")
			);

			while ($store = $dbList->Fetch())
				$result[$store["ID"]] = $store;
		}

		return $result;
	}

	public static function getMap($deliveryId, $index, $storeId = 0)
	{
		global $APPLICATION;
		$map = '';

		if ($deliveryId <= 0)
			return $map;

		$stores = self::getStoresList($deliveryId);
		if ($stores)
		{
			$params = array(
				"INPUT_NAME" => 'SHIPMENT['.$index.'][DELIVERY_STORE_ID]',
				"INPUT_ID" => 'DELIVERY_ST_'.$index,
				"INDEX" => $index,
				"DELIVERY_ID" => $deliveryId,
				"STORES_LIST" => self::getStoresList($deliveryId),
				"MAP" => array(
					'OPTIONS' => array('ENABLE_DRAGGING'),
					'CONTROLS' => array('SMALLZOOM')
				)
			);
			if (intval($storeId) > 0)
				$params["SELECTED_STORE"] = $storeId;
			ob_start();
			$APPLICATION->IncludeComponent(
				"bitrix:sale.store.choose",
				".default",
				$params
			);
			$map .= '<div class="adm-s-order-delivery-store-title">'.Loc::getMessage('SALE_ORDER_SHIPMENT_STORE_SELF_DELIVERY').'</div>';
			$map .= ob_get_contents();
			ob_end_clean();

			$map .= '<link rel="stylesheet" type="text/css" href="/bitrix/components/bitrix/sale.store.choose/templates/.default/style.css">';
		}

		return $map;
	}

	private static function getDeliverySelectHtml($deliveryServices, $selected='', $index)
	{
		$result = '<select class="adm-bus-select" name="SHIPMENT['.$index.'][DELIVERY_ID]" id="DELIVERY_'.$index.'">';
		$result .= self::getTemplate($deliveryServices, $selected);
		$result .= '</select>';

		return $result;
	}

	public static function getScripts()
	{
		Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_shipment.js");
		$message = array(
			'SALE_ORDER_SHIPMENT_DEDUCTED_YES' => Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED_YES'),
			'SALE_ORDER_SHIPMENT_DEDUCTED_NO' => Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED_NO'),
			'SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_YES' => Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_YES'),
			'SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_NO' => Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_NO'),
			'SALE_ORDER_SHIPMENT_NEW_PRICE_DELIVERY' => Loc::getMessage('SALE_ORDER_SHIPMENT_NEW_PRICE_DELIVERY'),
			'SALE_ORDER_SHIPMENT_APPLY' => Loc::getMessage('SALE_ORDER_SHIPMENT_APPLY'),
			'SALE_ORDER_SHIPMENT_CONFIRM_SET_NEW_PRICE' => Loc::getMessage('SALE_ORDER_SHIPMENT_CONFIRM_SET_NEW_PRICE'),
			'SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_TOGGLE_UP' => Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_TOGGLE_UP'),
			'SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_TOGGLE' => Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_TOGGLE'),
			'SALE_ORDER_SHIPMENT_CONFIRM_DELETE_SHIPMENT' => Loc::getMessage('SALE_ORDER_SHIPMENT_CONFIRM_DELETE_SHIPMENT'),
			'SALE_ORDER_SHIPMENT_PROFILE' => Loc::getMessage('SALE_ORDER_SHIPMENT_PROFILE'),
			'SALE_ORDER_SHIPMENT_TRACKING_S_EMPTY' => Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_S_EMPTY'),
		);

		return "<script>
			BX.message(".\CUtil::PhpToJSObject($message).");
		</script>";
	}

	public static function registerShipmentFieldsUpdaters()
	{
		return "<script>
			BX.ready(function(){
				BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( BX.Sale.Admin.GeneralShipment.getFieldsUpdaters() );
			});
		</script>";

	}

	/**
	 * @param $profiles
	 * @param \Bitrix\Sale\Shipment $shipment
	 */
	public static function checkProfilesRestriction($profiles, $shipment)
	{
		foreach ($profiles as &$profile)
			$profile['RESTRICTED'] = Restrictions\Manager::checkService($profile['ID'], $shipment, Restrictions\Manager::MODE_MANAGER);
		unset($profile);

		return $profiles;
	}

	/**
	 * @param $profiles
	 * @param $index
	 * @param int $selectedProfileId
	 * @return string
	 */
	public static function getProfileEditControl($profiles, $index = 1, $selectedProfileId = 0)
	{

		$result = '<select class="adm-bus-select" name="SHIPMENT['.$index.'][PROFILE]" id="PROFILE_'.$index.'">';
		$availableProfile = '';
		$unAvailableProfile = '';

		foreach ($profiles as $profile)
		{
			if ($profile['ACTIVE'] == 'N')
				continue;

			$selected = ($profile['ID'] == $selectedProfileId ? 'selected' : '');

			if ($profile['RESTRICTED'] == Restrictions\Manager::SEVERITY_SOFT)
				$unAvailableProfile .= '<option value="'.$profile['ID'].'" '.$selected.' class="bx-admin-service-restricted">'.htmlspecialcharsbx($profile['NAME']).'</option>';
			else
				$availableProfile .= '<option value="'.$profile['ID'].'" '.$selected.'>'.htmlspecialcharsbx($profile['NAME']).'</option>';
		}

		$result .= $availableProfile.$unAvailableProfile.'</select>';
		return $result;
	}

	/**
	 * @param $items
	 * @return array
	 */
	public static function makeDeliveryServiceTree($items)
	{
		$deliveries = array();
		$rootId = array();

		foreach ($items as $item)
			$deliveries[$item['ID']] = $item;

		foreach ($deliveries as $id => $delivery)
		{
			if (is_callable($deliveries[$delivery['PARENT_ID']]['CLASS_NAME'].'::canHasProfiles') && $deliveries[$delivery['PARENT_ID']]['CLASS_NAME']::canHasProfiles())
				continue;
			if ($delivery['PARENT_ID'])
				$deliveries[$delivery['PARENT_ID']]['SUBMENU'][$id] = & $deliveries[$id];
			else
				$rootId[] = $id;
		}

		$result = array();
		foreach ($rootId as $id)
		{
			if (is_callable($deliveries[$id]['CLASS_NAME'].'::canHasChildren') && $deliveries[$id]['CLASS_NAME']::canHasChildren() && !isset($deliveries[$id]['SUBMENU']))
				continue;

			$result[$id] = $deliveries[$id];
		}

		return $result;
	}

	/**
	 * @param $deliveries
	 * @param string $selected
	 * @return string
	 */
	public static function getTemplate($deliveries, $selected = '')
	{
		$result = '';
		$restricted = '';
		foreach ($deliveries as $service)
		{
			$serviceCode = '';
			if ($service['ID'] > 0)
				$serviceCode = '['.$service['ID'].'] ';

			if (isset($service['SUBMENU']) && count($service['SUBMENU']) > 0)
			{
				$result .= '<optgroup label="'.htmlspecialcharsbx($service['NAME']).'" id="parent_'.$service['ID'].'">';
				$subRestricted = '';
				foreach ($service['SUBMENU'] as $subService)
				{
					$subServiceCode = '';
					if ($subService['ID'] > 0)
						$subServiceCode = '['.$service['ID'].'] ';

					if (isset($subService['RESTRICTED']) && $subService['RESTRICTED'])
					{
						if ($subService['ID'] == $selected)
							$subRestricted .= '<option value="'.$subService['ID'].'" class="bx-admin-service-restricted" data-parent-id="'.$subService['PARENT_ID'].'" selected>'.$subServiceCode.htmlspecialcharsbx(TruncateText($subService['NAME'], 40)).'</option>';
						else
							$subRestricted .= '<option value="'.$subService['ID'].'" class="bx-admin-service-restricted" data-parent-id="'.$subService['PARENT_ID'].'">'.$subServiceCode.htmlspecialcharsbx(TruncateText($subService['NAME'], 40)).'</option>';
					}
					else
					{
						if ($subService['ID'] == $selected)
							$result .= '<option value="'.$subService['ID'].'" data-parent-id="'.$subService['PARENT_ID'].'" selected>'.$subServiceCode.htmlspecialcharsbx(TruncateText($subService['NAME'], 40)).'</option>';
						else
							$result .= '<option value="'.$subService['ID'].'" data-parent-id="'.$subService['PARENT_ID'].'">'.$subServiceCode.htmlspecialcharsbx(TruncateText($subService['NAME'], 40)).'</option>';
					}
				}
				$result .= $subRestricted.'</optgroup>';
			}
			else
			{
				if (isset($service['RESTRICTED']) && $service['RESTRICTED'])
				{
					if ($service['ID'] == $selected)
						$restricted .= '<option value="'.$service['ID'].'" class="bx-admin-service-restricted" selected>'.$serviceCode.htmlspecialcharsbx(TruncateText($service['NAME'], 40)).'</option>';
					else
						$restricted .= '<option value="'.$service['ID'].'" class="bx-admin-service-restricted">'.$serviceCode.htmlspecialcharsbx(TruncateText($service['NAME'], 40)).'</option>';
				}
				else
				{
					if ($service['ID'] == $selected)
						$result .= '<option value="'.$service['ID'].'" selected>'.$serviceCode.htmlspecialcharsbx(TruncateText($service['NAME'], 40)).'</option>';
					else
						$result .= '<option value="'.$service['ID'].'">'.$serviceCode.htmlspecialcharsbx(TruncateText($service['NAME'], 40)).'</option>';
				}
			}

		}

		return $result.$restricted;
	}

	/**
	 * @param \Bitrix\Sale\Shipment $shipment
	 * @param int $index
	 * @param string $formType
	 * @return string
	 */

	public static function getView($shipment, $index = 0, $formType = '')
	{
		self::$shipment = $shipment;
		$data = self::prepareData(false, false);

		$result = self::getViewTemplate($data, $index, $formType);

		return $result;
	}

	/**
	 * @param $data
	 * @param $index
	 * @param $formType
	 * @return string
	 * @throws Main\ArgumentNullException
	 */
	public static function getViewTemplate($data, $index, $formType)
	{
		global $USER;
		$index++;

		if (self::$backUrl !== '')
			$backUrl = self::$backUrl;
		else
			$backUrl = $_SERVER['REQUEST_URI'];

		$allowDeliveryString = ($data['ALLOW_DELIVERY'] == 'Y') ? 'YES' : 'NO';
		$deductedString = ($data['DEDUCTED'] == 'Y') ? 'YES' : 'NO';

		$allowedStatusesDelivery = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('delivery'));
		$isAllowDelivery = in_array($data["STATUS_ID"], $allowedStatusesDelivery);

		$isActive = ($formType != 'edit') && !Order::isLocked($data['ORDER_ID']);
		$triangle = ($isActive && $isAllowDelivery) ? '<span class="triangle"> &#9662;</span>' : '';

		if ($data['ALLOW_DELIVERY'] == 'Y')
			$class = ($isActive && $isAllowDelivery) ? '' : 'class="not_active"';
		else
			$class = ($isActive && $isAllowDelivery) ? 'class="notdelivery"' : 'class="notdelivery not_active"';

		$allowDelivery = '<span><span id="BUTTON_ALLOW_DELIVERY_'.$index.'" '.$class.'>'.Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_'.$allowDeliveryString).'</span>'.$triangle.'</span>';

		$allowedStatusesDeduction = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('deduction'));
		$isAllowDeduction = in_array($data["STATUS_ID"], $allowedStatusesDeduction);

		$triangle = ($isActive && $isAllowDeduction) ? '<span class="triangle"> &#9662;</span>' : '';

		if ($data['DEDUCTED'] == 'Y')
			$class = ($isActive && $isAllowDeduction) ? '' : 'class="not_active"';
		else
			$class = ($isActive && $isAllowDeduction) ? 'class="notdeducted"' : 'class="notdeducted not_active"';
		$deducted = '<span><span id="BUTTON_DEDUCTED_'.$index.'" '.$class.'>'.Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED_'.$deductedString).'</span>'.$triangle.'</span>';

		$map = ($data['DELIVERY_ID'] > 0) ? self::getMap($data['DELIVERY_ID'], $index, $data['DELIVERY_STORE_ID']) : '';

		$lang = Main\Application::getInstance()->getContext()->getLanguage();
		$service = null;
		$extraServiceHTML = '';
		$mainLogoPath =  '/bitrix/images/sale/logo-default-d.gif';
		$shortLogoPath =  '/bitrix/images/sale/logo-default-d.gif';

		if ($data['DELIVERY_ID'] > 0)
		{
			$service = Services\Manager::getObjectById($data['DELIVERY_ID']);
			$extraServiceManager = new \Bitrix\Sale\Delivery\ExtraServices\Manager($data['DELIVERY_ID']);
			$extraServiceManager->setOperationCurrency($data['CURRENCY']);
			if ($data['EXTRA_SERVICES'])
				$extraServiceManager->setValues($data['EXTRA_SERVICES']);

			if ($service && $service->getLogotip() > 0)
			{
				$mainLogo = self::getMainImgPath($service->getLogotip());
				$shortLogo = self::getShortImgPath($service->getLogotip());
				$mainLogoPath = $mainLogo['src'];
				$shortLogoPath = $shortLogo['src'];
			}

			$extraService = $extraServiceManager->getItems();
			if ($extraService)
				$extraServiceHTML = self::getExtraServiceEditControl($extraService, $index, true, self::$shipment);
		}

		$companyList = OrderEdit::getCompanyList();
		$shipmentStatusList = OrderShipmentStatus::getShipmentStatusList($data['STATUS_ID']);
		$jsShipmentStatus = array();
		foreach ($shipmentStatusList as $id => $name)
		{
			$jsShipmentStatus[] = array(
				'ID' => $id,
				'NAME' => htmlspecialcharsbx($name)
			);
		}

		$allowedStatusesFrom = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('from'));
		$canChangeStatus = in_array($data["STATUS_ID"], $allowedStatusesFrom);
		$triangle = ($isActive && $canChangeStatus) ? '<span class="triangle"> &#9662;</span>' : '';

		$class = ($isActive && $canChangeStatus) ? '' : 'class="not_active"';
		$shipmentStatus = '<span><span id="BUTTON_SHIPMENT_' . $index . '" '.$class.'>' . htmlspecialcharsbx($shipmentStatusList[$data['STATUS_ID']]) . '</span>'.$triangle.'</span>';

		$shippingBlockId = '';
		if($isActive || strlen($data['TRACKING_NUMBER']) > 0)
		{
			$shippingBlockId = '<tr>
									<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_NUMBER').':</td>
									<td class="adm-detail-content-cell-r tal">
										<input type="text" id="TRACKING_NUMBER_'.$index.'_EDIT" name="SHIPMENT['.$index.'][TRACKING_NUMBER]" style="display: none;" value="'.htmlspecialcharsbx($data['TRACKING_NUMBER']).'">
										<span id="TRACKING_NUMBER_'.$index.'_VIEW">'.htmlspecialcharsbx($data['TRACKING_NUMBER']).'</span>';
			if ($isActive)
				$shippingBlockId .= '<div class="bx-adm-edit-pencil" id="TRACKING_NUMBER_PENCIL_'.$index.'"></div>';

			if($data['HAS_TRACKING'] && strlen($data['TRACKING_NUMBER']) > 0)
			{
				$shippingBlockId .= '</td></tr>
				<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_STATUS').':</td>
												<td class="adm-detail-content-cell-r tal">'.
				'<span id="sale-order-shipment-tracking-status-'.$index.'">'.(intval($data['TRACKING_STATUS']) >= 0  ? \Bitrix\Sale\Delivery\Tracking\Manager::getStatusName($data['TRACKING_STATUS']) : '-').'</span>'.
				'&nbsp;&nbsp;&nbsp;[<span onclick="BX.Sale.Admin.GeneralShipment.refreshTrackingStatus(\''.$index.'\', \''.$data['ID'].'\');" style="border-bottom: 1px dashed #2675d7; cursor: pointer; color: #2675d7;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_STATUS_REFRESH').'</span>]<br></td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_DESCRIPTION').':</td>
												<td class="adm-detail-content-cell-r tal" id="sale-order-shipment-tracking-description-'.$index.'">'.(strlen($data['TRACKING_DESCRIPTION']) > 0 ? $data['TRACKING_DESCRIPTION'] : '-').'<br></td>
											<tr>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_TRACKING_LAST_CHANGE').':</td>
												<td class="adm-detail-content-cell-r tal" id="sale-order-shipment-tracking-last-change-'.$index.'">'.(strlen($data['TRACKING_LAST_CHANGE']) > 0 ? $data['TRACKING_LAST_CHANGE'] : '-').'<br></td>
											<tr>';
			}
		}

		$shippingBlockDocNum = '';
		if (strlen($data['DELIVERY_DOC_NUM']) > 0)
		{
			$shippingBlockDocNum = '<tr>
								<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_DOC_NUM').':</td>
								<td class="adm-detail-content-cell-r tal">
									' . htmlspecialcharsbx($data['DELIVERY_DOC_NUM']) . '
								</td>
							</tr>';
		}

		$shippingBlockDocDate = '';
		if (strlen($data['DELIVERY_DOC_DATE']) > 0)
		{
			$shippingBlockDocDate = '<tr>
								<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_DOC_DATE').':</td>
								<td class="adm-detail-content-cell-r tal">
									' . htmlspecialcharsbx($data['DELIVERY_DOC_DATE']) . '
								</td>
							</tr>';
		}

		$dateInsert = new Date($data['DATE_INSERT']);

		$sectionDelete = '';
		$allowedDeliveryStatusesDelete = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('delete'));
		if (in_array($data["STATUS_ID"], $allowedDeliveryStatusesDelete) && !$data['ORDER_LOCKED'])
			$sectionDelete = '<div class="adm-bus-pay-section-action" id="SHIPMENT_SECTION_'.$index.'_DELETE">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_DELETE').'</div>';

		$sectionEdit = '';
		$allowedOrderStatusesUpdate = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('update'));
		if (in_array($data["STATUS_ID"], $allowedOrderStatusesUpdate) && !$data['ORDER_LOCKED'])
			$sectionEdit = '<div class="adm-bus-pay-section-action" id="SHIPMENT_SECTION_'.$index.'_EDIT"><a href="/bitrix/admin/sale_order_shipment_edit.php?order_id='.$data['ORDER_ID'].'&shipment_id='.$data['ID'].'&backurl='.urlencode($backUrl).'">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_EDIT').'</a></div>';

		$result = '
			<input type="hidden" name="SHIPMENT['.$index.'][DEDUCTED]" id="STATUS_DEDUCTED_'.$index.'" value="'.($data['DEDUCTED'] == "" ? "N" : $data['DEDUCTED']).'">
			<input type="hidden" name="SHIPMENT['.$index.'][ALLOW_DELIVERY]" id="STATUS_ALLOW_DELIVERY_'.$index.'" value="'.($data['ALLOW_DELIVERY'] == "" ? "N" : $data['ALLOW_DELIVERY']).'">
			<input type="hidden" name="SHIPMENT['.$index.'][STATUS_ID]" id="STATUS_SHIPMENT_'.$index.'" value="'.$data['STATUS_ID'].'">
		<div class="adm-bus-pay" id="shipment_container_'.$index.'">
			<input type="hidden" name="SHIPMENT['.$index.'][SHIPMENT_ID]" id="SHIPMENT_ID_'.$index.'" value="'.$data['ID'].'">
			<div class="adm-bus-component-content-container">
				<div class="adm-bus-pay-section">
					<div class="adm-bus-pay-section-title-container">
						<div class="adm-bus-pay-section-title" id="shipment_'.$data['ID'].'">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_EDIT_SHIPMENT_TITLE', array("#ID#" => $data['ID'], '#DATE_INSERT#' => $dateInsert)).'</div>
						<div class="adm-bus-pay-section-action-block">'.$sectionDelete.$sectionEdit.'						
							<div class="adm-bus-pay-section-action" id="SHIPMENT_SECTION_'.$index.'_TOGGLE">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT_TOGGLE_UP').'</div>
						</div>
					</div>
					<div class="adm-bus-pay-section-content" id="SHIPMENT_SECTION_'.$index.'" style="display:none;">
						<div class="adm-bus-pay-section-sidebar">
							<div style="background: url(\''.$mainLogoPath.'\')" id="delivery_service_logo_'.$index.'" class="adm-shipment-block-logo"></div>
							'.$map.'
						</div>
						<div class="adm-bus-pay-section-right">
							<div class="adm-bus-table-container caption border">
								<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SERVICE').'</div>
								<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
									<tbody>
										<tr>
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SERVICE').':</td>
											<td class="adm-detail-content-cell-r">
												'.htmlspecialcharsbx($data['DELIVERY_NAME']).' ['.$data['DELIVERY_ID'].'] 
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="adm-bus-table-container caption border">
								<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_BLOCK_PRICE').'</div>
								<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">
									<tbody>
										<tr style="display: none;">
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SUM_PRICE').':</td>
											<td class="adm-detail-content-cell-r tal">
												'.SaleFormatCurrency(floatval($data['BASE_PRICE_DELIVERY']), $data['CURRENCY']).'
											</td>
										</tr>
										<tr id="sale-order-shipment-discounts-row-'.$index.'" style="display: none;">
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DISCOUNT').':</td>
											<td class="adm-detail-content-cell-r tal" id="sale-order-shipment-discounts-container-'.$index.'"></td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SUM_DISCOUNT_PRICE').':</td>
											<td class="adm-detail-content-cell-r tal" id="PRICE_DELIVERY_'.$index.'">'.SaleFormatCurrency(floatval($data['PRICE_DELIVERY']), $data['CURRENCY']).'<br></td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="adm-bus-table-container caption border">
								<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_SHIPMENT').'</div>
								<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
									<tbody>
										<tr>
											<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_OFFICE').':</td>
											<td class="adm-detail-content-cell-r">
												'.(isset($companyList[$data['COMPANY_ID']]) ? htmlspecialcharsbx($companyList[$data['COMPANY_ID']]) : Loc::getMessage('SALE_ORDER_SHIPMENT_NO_COMPANY')).'
											</td>
										</tr>
									</tbody>
								</table>
							</div>
							<div class="adm-bus-table-container caption border">
								<div class="adm-bus-moreInfo_part1">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_STATUS').'</div>
									<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY').':</td>
												<td class="adm-detail-content-cell-r delivery-status">'.$allowDelivery.'</td>
											</tr>
											'.((!empty($data['EMP_ALLOW_DELIVERY_ID'])) ? '
											<tr>
												<td class="adm-detail-content-cell-l vat" width="40%"></td>
												<td class="adm-detail-content-cell-r">
													<div>'.Loc::getMessage('SALE_ORDER_SHIPMENT_MODIFY_BY').': <span style="color: #66878F" id="order_additional_info_date_responsible">'.htmlspecialcharsbx($data['DATE_ALLOW_DELIVERY']).'</span>  <a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_ALLOW_DELIVERY_ID'].'" id="order_additional_info_emp_responsible">'.htmlspecialcharsbx($data['EMP_ALLOW_DELIVERY_ID_LAST_NAME']).' '.htmlspecialcharsbx($data['EMP_ALLOW_DELIVERY_ID_NAME']).'</a></div>
												</td>
											</tr>
											' : '').'
											<tr>
												<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED').':</td>
												<td class="adm-detail-content-cell-r"><div class="delivery-status">'.$deducted.'</div></td>
											</tr>
											'.((!empty($data['EMP_DEDUCTED_ID'])) ? '
											<tr>
												<td class="adm-detail-content-cell-l vat" width="40%"></td>
												<td class="adm-detail-content-cell-r">
													<div>'.Loc::getMessage('SALE_ORDER_SHIPMENT_MODIFY_BY').': <span style="color: #66878F" id="order_additional_info_date_responsible">'.htmlspecialcharsbx($data['DATE_DEDUCTED']).'</span>  <a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_DEDUCTED_ID'].'" id="order_additional_info_emp_responsible">'.htmlspecialcharsbx($data['EMP_DEDUCTED_ID_LAST_NAME']).' '.htmlspecialcharsbx($data['EMP_DEDUCTED_ID_NAME']).'</a></div>
												</td>
											</tr>
											' : '').'
											<tr>
												<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_DOC_STATUS').':</td>
												<td class="adm-detail-content-cell-r">
													<div class="shipment-status">
														'.$shipmentStatus.'
													</div>
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>';

		if (!empty($shippingBlockId) || !empty($shippingBlockDocNum) || !empty($shippingBlockDocDate) || !empty($extraServiceHTML))
		{
			$result .= '<div class="adm-bus-table-container caption border">
				<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_DELIVERY_INFO').'</div>
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
					<tbody>
						' . $shippingBlockId . $shippingBlockDocNum . $shippingBlockDocDate . '
					</tbody>
				</table>
				<div id="DELIVERY_INFO_' . $index . '">
				' . $extraServiceHTML . '
				</div>
			</div>';
		}

		if(is_array($data['DELIVERY_ADDITIONAL_INFO_VIEW']) && !empty($data['DELIVERY_ADDITIONAL_INFO_VIEW']))
		{
			$result .= '<div class="adm-bus-table-container caption border">
							<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BLOCK_DELIVERY_ADDITIONAL').'</div>
							<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
								<tbody><tr>';

			foreach($data['DELIVERY_ADDITIONAL_INFO_VIEW'] as $name => $params)
			{
				$result .='
					<td class="adm-detail-content-cell-l" width="40%">'.$params['LABEL'].':</td>
					<td class="adm-detail-content-cell-r" width="60%">
						'.\Bitrix\Sale\Internals\Input\Manager::getViewHtml($params).'
					</td>';
			}

			$result .=	'		</tr></tbody>
							</table>
						</div>';
		}

		$result .= '</div>
		<div class="clb"></div>
		<div class="adm-s-order-shipment-basket-structure">'.Loc::getMessage('SALE_ORDER_SHIPMENT_BASKET').'</div>';

		$shipmentBasket = new OrderBasketShipment(self::$shipment, "BX.Sale.Admin.ShipmentBasketObj_".$index, "shipment_basket_".$index);
		$result .= $shipmentBasket->getView($index);

		$result .='</div>';

		$result .= self::getShortViewTemplate($data, $index, $shortLogoPath, $formType);
		$result .= '</div>
			</div>
		</div>';

		$params = array(
			'index' => $index,
			'canAllow' => $isAllowDelivery,
			'canDeduct' => $isAllowDeduction,
			'canChangeStatus' => $canChangeStatus,
			'id' => (int)$data['ID'],
			'extra_service' => array(),
			'shipment_statuses' => $jsShipmentStatus,
			'isAjax' => true,
			'active' => $isActive,
			'discounts' => $data["DISCOUNTS"],
			'discountsMode' => ($formType == "edit" ? "edit" : "view"),
			'templateType' => 'view'
		);

		$result .= self::initJsShipment($params);

		return $result;
	}

	private static function getShortViewTemplate($data, $index, $logo, $formType)
	{
		global $USER;
		$allowDeliveryString = ($data['ALLOW_DELIVERY'] == 'Y') ? 'YES' : 'NO';
		$deductedString = ($data['DEDUCTED'] == 'Y') ? 'YES' : 'NO';

		$allowedStatusesDelivery = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('delivery'));
		$isAllowDelivery = in_array($data["STATUS_ID"], $allowedStatusesDelivery);

		$isActive = ($formType != 'edit') && !Order::isLocked($data['ORDER_ID']);
		$triangle = ($isActive && $isAllowDelivery) ? '<span class="triangle"> &#9662;</span>' : '';

		if ($data['ALLOW_DELIVERY'] == 'Y')
			$class = ($isActive && $isAllowDelivery) ? '' : 'class="not_active"';
		else
			$class = ($isActive && $isAllowDelivery) ? 'class="notdelivery"' : 'class="notdelivery not_active"';

		$allowDelivery = '<span><span id="BUTTON_ALLOW_DELIVERY_SHORT_'.$index.'" '.$class.'>'.Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY_'.$allowDeliveryString).'</span>'.$triangle.'</span>';

		$allowedStatusesDeduction = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('deduction'));
		$isAllowDeduction = in_array($data["STATUS_ID"], $allowedStatusesDeduction);

		$triangle = ($isActive && $isAllowDeduction) ? '<span class="triangle"> &#9662;</span>' : '';

		if ($data['DEDUCTED'] == 'Y')
			$class = ($isActive && $isAllowDeduction) ? '' : 'class="not_active"';
		else
			$class = ($isActive && $isAllowDeduction) ? 'class="notdeducted"' : 'class="notdeducted not_active"';
		$deducted = '<span><span id="BUTTON_DEDUCTED_SHORT_'.$index.'" '.$class.'>'.Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED_'.$deductedString).'</span>'.$triangle.'</span>';

		$shipmentStatusList = OrderShipmentStatus::getShipmentStatusList($data['STATUS_ID']);

		$allowedStatusesFrom = DeliveryStatus::getStatusesUserCanDoOperations($USER->GetID(), array('from'));
		$canChangeStatus = in_array($data["STATUS_ID"], $allowedStatusesFrom);
		$triangle = ($isActive && $canChangeStatus) ? '<span class="triangle"> &#9662;</span>' : '';

		$class = ($isActive && $canChangeStatus) ? '' : 'class="not_active"';
		$shipmentStatus = '<span><span id="BUTTON_SHIPMENT_SHORT_' . $index . '" '.$class.'>' . htmlspecialcharsbx($shipmentStatusList[$data['STATUS_ID']]) . '</span>'.$triangle.'</span>';

		$result = '<div class="adm-bus-pay-section-content" id="SHIPMENT_SECTION_SHORT_'.$index.'">
						<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
							<tbody>
								<tr class="adm-shipment-block-short-info">
									<td class="adm-detail-content-cell-l vat">
										<div style="background: url(\''.$logo.'\')" id="delivery_service_short_logo_'.$index.'" class="adm-shipment-block-short-logo"></div>
									</td>
									<td class="adm-detail-content-cell-l vat">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_SERVICE').': '.htmlspecialcharsbx($data['DELIVERY_NAME']).'</td>
									<td class="adm-detail-content-cell-l vat"><div class="delivery-status">'.Loc::getMessage('SALE_ORDER_SHIPMENT_ALLOW_DELIVERY').': '.$allowDelivery.'</div></td>
									<td class="adm-detail-content-cell-l vat"><div class="deducted-status">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DEDUCTED').': '.$deducted.'</div></td>
									<td class="adm-detail-content-cell-l vat"><div class="shipment-status">'.Loc::getMessage('SALE_ORDER_SHIPMENT_DELIVERY_STATUS').': '.$shipmentStatus.'</div></td>
								</tr>
							</tbody>
						</table>
						<div class="clb"></div>
					</div>';


		return $result;
	}

	public static function createNewShipmentButton()
	{
		return '<input type="button" onclick="BX.Sale.Admin.GeneralShipment.createNewShipment()" value = "'.Loc::getMessage('SALE_ORDER_SHIPMENT_ADD_SHIPMENT').'">';
	}

	/**
	 * @param bool $error
	 * @param bool $needRecalculate
	 * @return mixed
	 */
	protected static function prepareData($error = false, $needRecalculate = true)
	{
		global $USER;
		static $users = array();
		$result = array();
		if ($error)
		{
			$fields = self::$defaultFields;
		}
		else
		{
			$fields = self::$shipment->getFieldValues();
			$fields['DELIVERY_STORE_ID'] = self::$shipment->getStoreId();
			$fields["EXTRA_SERVICES"] = self::$shipment->getExtraServices();
			$fields["STORE"] = self::$shipment->getStoreId();
		}

		if ($fields['DELIVERY_DOC_DATE'])
		{
			$date = new Date($fields['DELIVERY_DOC_DATE']);
			$fields['DELIVERY_DOC_DATE'] = $date->toString();
		}

		$empDeductedId = $fields['EMP_DEDUCTED_ID'];
		if ($empDeductedId > 0)
		{
			if (!array_key_exists($empDeductedId, $users))
				$users[$empDeductedId] = $USER->GetByID($empDeductedId)->Fetch();
			$fields['EMP_DEDUCTED_ID_NAME'] = $users[$empDeductedId]['NAME'];
			$fields['EMP_DEDUCTED_ID_LAST_NAME'] = $users[$empDeductedId]['LAST_NAME'];
		}

		$empAllowDeliveryId = $fields['EMP_ALLOW_DELIVERY_ID'];
		if ($empAllowDeliveryId > 0)
		{
			if (!array_key_exists($empAllowDeliveryId, $users))
				$users[$empAllowDeliveryId] = $USER->GetByID($empAllowDeliveryId)->Fetch();
			$fields['EMP_ALLOW_DELIVERY_ID_NAME'] = $users[$empAllowDeliveryId]['NAME'];
			$fields['EMP_ALLOW_DELIVERY_ID_LAST_NAME'] = $users[$empAllowDeliveryId]['LAST_NAME'];
		}

		$empCanceledId = $fields['EMP_CANCELED_ID'];
		if ($empCanceledId > 0)
		{
			if (!array_key_exists($empCanceledId, $users))
				$users[$empCanceledId] = $USER->GetByID($empCanceledId)->Fetch();
			$fields['EMP_CANCELLED_ID_NAME'] = $users[$empCanceledId]['NAME'];
			$fields['EMP_CANCELLED_ID_LAST_NAME'] = $users[$empCanceledId]['LAST_NAME'];
		}

		$empMarkedId = $fields['EMP_MARKED_ID'];
		if ($empMarkedId > 0)
		{
			if (!array_key_exists($empMarkedId, $users))
				$users[$empMarkedId] = $USER->GetByID($empMarkedId)->Fetch();
			$fields['EMP_MARKED_ID_NAME'] = $users[$empMarkedId]['NAME'];
			$fields['EMP_MARKED_ID_LAST_NAME'] = $users[$empMarkedId]['LAST_NAME'];
		}
		/** @var \Bitrix\Sale\Order $order */
		$order = self::$shipment->getCollection()->getOrder();
		$fields['CURRENCY'] = $order->getCurrency();

		$calcResult = self::calculateDeliveryPrice(self::$shipment);
		if ($calcResult->isSuccess())
			$fields['CALCULATED_PRICE'] = $calcResult->getPrice();

		if ($fields['CUSTOM_PRICE_DELIVERY'] == 'Y' && $fields['ID'] <= 0)
			$fields['BASE_PRICE_DELIVERY'] = self::$shipment->getField('BASE_PRICE_DELIVERY');

		$discounts = OrderEdit::getDiscountsApplyResult($order, $needRecalculate);
		$shipmentIds = $order->getDiscount()->getShipmentsIds();

		foreach ($shipmentIds as $shipmentId)
		{
			if ($shipmentId == self::$shipment->getId())
				$fields['DISCOUNTS'] = $discounts;
		}

		/** @var \Bitrix\Sale\Delivery\Services\Base $delivery */
		$delivery = self::$shipment->getDelivery();

		if(!is_null($delivery))
		{
			$fields['HAS_TRACKING'] = strlen($delivery->getTrackingClass()) > 0 ? true : false;
			$fields['DELIVERY_ADDITIONAL_INFO_EDIT'] = $delivery->getAdditionalInfoShipmentEdit(self::$shipment);
			$fields['DELIVERY_ADDITIONAL_INFO_VIEW'] = $delivery->getAdditionalInfoShipmentView(self::$shipment);
		}

		$fields['ORDER_LOCKED'] = Order::isLocked($fields['ORDER_ID']);
		return $fields;
	}

	/**
	 * @param Order $order
	 * @param array $shipments
	 * @return Result
	 * @throws SystemException
	 */
	public static function updateData(Order &$order, array $shipments)
	{
		global $USER;
		
		$result = new Result();
		$data = array();
		$basketResult = null;

		if (!$order)
		{
			$result->addError(
				new EntityError(
					Loc::getMessage('SALE_ORDER_SHIPMENT_ERROR_ORDER_NOT_FOUND')
				)
			);
			return $result;
		}

		$shipmentCollection = $order->getShipmentCollection();

		$isStartField = $shipmentCollection->isStartField();

		foreach ($shipments as $item)
		{
			$shipmentId = intval($item['SHIPMENT_ID']);
			$isNew = ($shipmentId <= 0);
			$deliveryService = null;

			if ($isNew)
			{
				self::$shipment = $shipmentCollection->createItem();
			}
			else
			{
				self::$shipment = $shipmentCollection->getItemById($shipmentId);
				if (!self::$shipment)
				{
					$result->addError(
						new EntityError(
							Loc::getMessage('SALE_ORDER_SHIPMENT_ERROR_SHIPMENT_NOT_FOUND')
						)
					);
					continue;
				}
			}

			self::$defaultFields = self::$shipment->getFieldValues();

			/** @var \Bitrix\Sale\BasketItem $product */

			$countItems = count(self::$shipment->getShipmentItemCollection());
			$systemShipment = $shipmentCollection->getSystemShipment();
			$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();

			$products = array();
			if (
				!isset($item['PRODUCT'])
				&& self::$shipment->getId() <= 0
			)
			{
				$basket = $order->getBasket();
				if ($basket)
				{
					$basketItems = $basket->getBasketItems();
					foreach ($basketItems as $product)
					{
						$systemShipmentItem = $systemShipmentItemCollection->getItemByBasketCode($product->getBasketCode());
						if ($product->isBundleChild() || !$systemShipmentItem || $systemShipmentItem->getQuantity() <= 0)
							continue;

						$products[] = array(
							'AMOUNT' => $product->getQuantity(),
							'BASKET_CODE' => $product->getBasketCode()
						);
					}
				}
			}
			else
			{
				$products = $item['PRODUCT'];
			}

			if ($item['DEDUCTED'] == 'Y')
			{
				$basketResult = OrderBasketShipment::updateData($order, self::$shipment, $products);
				if (!$basketResult->isSuccess())
					$result->addErrors($basketResult->getErrors());
			}

			$extraServices = ($item['EXTRA_SERVICES']) ? $item['EXTRA_SERVICES'] : array();

			$shipmentFields = array(
				'COMPANY_ID' => (isset($item['COMPANY_ID']) && $item['COMPANY_ID'] > 0) ? $item['COMPANY_ID'] : 0,
				'DEDUCTED' => $item['DEDUCTED'],
				'DELIVERY_DOC_NUM' => $item['DELIVERY_DOC_NUM'],
				'TRACKING_NUMBER' => $item['TRACKING_NUMBER'],
				'CURRENCY' => $order->getCurrency(),
				'COMMENTS' => $item['COMMENTS'],
				'STATUS_ID' =>($isNew) ? DeliveryStatus::getInitialStatus() : $item['STATUS_ID']
			);

			if ($item['DELIVERY_DOC_DATE'])
			{
				try
				{
					$shipmentFields['DELIVERY_DOC_DATE'] = new Date($item['DELIVERY_DOC_DATE']);
				}
				catch (Main\ObjectException $exception)
				{
					$result->addError(
						new EntityError(
							Loc::getMessage('SALE_ORDER_SHIPMENT_ERROR_UNCORRECT_FORM_DATE')
						)
					);
				}
			}

			$shipmentFields['DELIVERY_ID'] = ($item['PROFILE'] > 0) ? $item['PROFILE'] : $item['DELIVERY_ID'];

			try
			{
				if($deliveryService = Services\Manager::getObjectById($shipmentFields['DELIVERY_ID']))
				{
					if ($deliveryService->isProfile())
						$shipmentFields['DELIVERY_NAME'] = $deliveryService->getNameWithParent();
					else
						$shipmentFields['DELIVERY_NAME'] = $deliveryService->getName();
				}
			}
			catch (Main\ArgumentNullException $e)
			{
				$result->addError(
					new EntityError(
						Loc::getMessage('SALE_ORDER_SHIPMENT_ERROR_NO_DELIVERY_SERVICE')
					)
				);
			}

			$responsibleId = self::$shipment->getField('RESPONSIBLE_ID');
			if ($item['RESPONSIBLE_ID'] != $responsibleId || empty($responsibleId))
			{
				if (isset($item['RESPONSIBLE_ID']))
					$shipmentFields['RESPONSIBLE_ID'] = $item['RESPONSIBLE_ID'];
				else
					$shipmentFields['RESPONSIBLE_ID'] = $order->getField('RESPONSIBLE_ID');

				if (!empty($shipmentFields['RESPONSIBLE_ID']))
				{
					$shipmentFields['EMP_RESPONSIBLE_ID'] = $USER->getID();
					$shipmentFields['DATE_RESPONSIBLE_ID'] = new DateTime();
				}
			}

			if ($extraServices)
				self::$shipment->setExtraServices($extraServices);
			$setFieldsResult = self::$shipment->setFields($shipmentFields);
			if (!$setFieldsResult->isSuccess())
				$result->addErrors($setFieldsResult->getErrors());
			self::$shipment->setStoreId($item['DELIVERY_STORE_ID']);

			if ($item['DEDUCTED'] == 'N')
			{
				$basketResult = OrderBasketShipment::updateData($order, self::$shipment, $products);
				if (!$basketResult->isSuccess())
					$result->addErrors($basketResult->getErrors());
			}

			$fields = array(
				'CUSTOM_PRICE_DELIVERY' => $item['CUSTOM_PRICE_DELIVERY'],
				'ALLOW_DELIVERY' => $item['ALLOW_DELIVERY']
			);

			$deliveryPrice = (float)str_replace(',', '.', $item['PRICE_DELIVERY']);

			if ($item['CUSTOM_PRICE_DELIVERY'] == 'Y')
				$fields['BASE_PRICE_DELIVERY'] = $deliveryPrice;
			else
				$fields['BASE_PRICE_DELIVERY'] = (float)str_replace(',', '.', $item['BASE_PRICE_DELIVERY']);

			$fields['PRICE_DELIVERY'] = $deliveryPrice;

			self::$shipment->setFields($fields);

			if($deliveryService && !empty($item['ADDITIONAL']))
			{
				$modifiedShipment = $deliveryService->processAdditionalInfoShipmentEdit(self::$shipment, $item['ADDITIONAL']);

				if($modifiedShipment && get_class($modifiedShipment) == 'Bitrix\Sale\Shipment')
					self::$shipment = $modifiedShipment;
			}

			$data['SHIPMENT'][] = self::$shipment;
		}

		if ($isStartField)
		{
			$hasMeaningfulFields = $shipmentCollection->hasMeaningfulField();

			/** @var Result $r */
			$r = $shipmentCollection->doFinalAction($hasMeaningfulFields);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		$result->setData($data);

		return $result;
	}

	/**
	 * @param Shipment $shipment
	 * @return CalculationResult
	 * @throws Main\ArgumentNullException
	 */
	public static function calculateDeliveryPrice(\Bitrix\Sale\Shipment $shipment)
	{
		$result = new CalculationResult();

		if ($shipment->getDeliveryId())
		{
			$service = Services\Manager::getObjectById($shipment->getDeliveryId());

			if ($service && !$service->canHasProfiles())
			{
				$extraServices = $shipment->getExtraServices();
				$extraServicesManager = $service->getExtraServices();
				$extraServicesManager->setValues($extraServices);
				return $service->calculate($shipment);
			}
		}

		return $result;
	}

	/**
	 * @param \Bitrix\Sale\Shipment $shipment
	 * @throws Main\NotSupportedException
	 */
	public static function setShipmentByDefaultValues(&$shipment)
	{
		/** @var \Bitrix\Sale\ShipmentCollection $shipmentCollection */
		$shipmentCollection = $shipment->getCollection();

		$systemShipment = $shipmentCollection->getSystemShipment();
		$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();
		/** @var \Bitrix\Sale\ShipmentItemCollection $shipmentItemCollection */
		$shipmentItemCollection = $shipment->getShipmentItemCollection();

		/** @var \Bitrix\Sale\ShipmentItem $systemShipmentItem */
		foreach ($systemShipmentItemCollection as $systemShipmentItem)
		{
			if ($systemShipmentItem->getQuantity() <= 0)
				continue;

			$basketItem = $systemShipmentItem->getBasketItem();
			$shipmentItem = $shipmentItemCollection->createItem($basketItem);
			$shipmentItem->setField('QUANTITY', $systemShipmentItem->getQuantity());
		}

		$shipment->setField('CUSTOM_PRICE_DELIVERY', 'N');
		$shipment->setField('DELIVERY_ID', $systemShipment->getField('DELIVERY_ID'));
		$shipment->setField('COMPANY_ID', $systemShipment->getField('COMPANY_ID'));
		$shipment->setField('DELIVERY_NAME', $systemShipment->getField('DELIVERY_NAME'));
		$shipment->setExtraServices($systemShipment->getExtraServices());
		$shipment->setStoreId($systemShipment->getStoreId());

		$price = 0;
		$calcResult = self::calculateDeliveryPrice($shipment);
		if ($calcResult->isSuccess())
			$price = $calcResult->getPrice();
		$shipment->setField('BASE_PRICE_DELIVERY', $price);
	}

	public static function setBackUrl($backUrl)
	{
		self::$backUrl = $backUrl;
	}
}