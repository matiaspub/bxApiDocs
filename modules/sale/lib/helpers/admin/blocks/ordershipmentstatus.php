<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\StatusTable;
use Bitrix\Sale\Order;

Loc::loadMessages(__FILE__);

class OrderShipmentStatus
{
	public static function getEdit($shipment, $index = 0)
	{
		$data = self::prepareData($shipment);

		return self::getEditTemplate($data, ++$index);
	}

	public static function getEditTemplate($data, $index)
	{
		return '
			<div class="adm-bus-pay">
				<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
					<tbody>
						<tr>
							<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_SHIPMENT_STATUS').':</td>
							<td class="adm-detail-content-cell-r">'.
							\Bitrix\Sale\Helpers\Admin\OrderEdit::makeSelectHtml(
								"SHIPMENT[".$index."][STATUS_ID]",
								self::getShipmentStatusList(),
								$data['STATUS_ID'],
								false,
								array(
									"class" => "adm-bus-select",
									"id" => "SHIPMENT_STATUS_ID"
								)
							)
							.'</td>
						</tr>
					</tbody>
				</table>
			</div>';
	}

	public static function getShipmentStatusList()
	{
		static $shipmentStatuses = array();

		if (empty($shipmentStatuses))
		{
			$context = Application::getInstance()->getContext();
			$lang = $context->getLanguage();
			$params = array(
				'select' => array('ID', 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'),
				'filter' => array(
					'=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => $lang,
					'=TYPE' => 'D'
				),
			);
			$dbRes = StatusTable::getList($params);

			while ($shipmentStatus = $dbRes->fetch())
				$shipmentStatuses[$shipmentStatus["ID"]] = $shipmentStatus["SALE_INTERNALS_STATUS_SALE_INTERNALS_STATUS_LANG_STATUS_NAME"] . " [" . $shipmentStatus["ID"] . "]";
		}
		return $shipmentStatuses;
	}

	public static function getView(Order $order)
	{
		return __METHOD__;
	}

	/**
	 * @param \Bitrix\Sale\ShipmentItem $shipment
	 * @return mixed
	 */
	protected static function prepareData($shipment)
	{
		$data['STATUS_ID'] = $shipment->getField('STATUS_ID');
		return $data;
	}
}