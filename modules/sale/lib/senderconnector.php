<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sale;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderEventHandler
{
	/**
	 * @param $data
	 * @return mixed
	 */
	public static function onConnectorListBuyer($data)
	{
		$data['CONNECTOR'] = 'Bitrix\Sale\SenderConnectorBuyer';

		return $data;
	}
}


class SenderConnectorBuyer extends \Bitrix\Sender\Connector
{
	/**
	 * @return string
	 */
	static public function getName()
	{
		return Loc::getMessage('sender_connector_buyer_name');
	}

	/**
	 * @return string
	 */
	static public function getCode()
	{
		return "buyer";
	}

	/** @return \CDBResult */
	public function getData()
	{
		$lid = $this->getFieldValue('LID', null);
		$orderCountFrom = $this->getFieldValue('ORDER_COUNT_FROM', null);
		$orderCountTo = $this->getFieldValue('ORDER_COUNT_TO', null);
		$orderSumFrom = $this->getFieldValue('ORDER_SUM_FROM', null);
		$orderSumTo = $this->getFieldValue('ORDER_SUM_TO', null);
		$orderLastDateFrom = $this->getFieldValue('LAST_ORDER_DATE_FROM', null);
		$orderLastDateTo = $this->getFieldValue('LAST_ORDER_DATE_TO', null);

		$filter = array();
		if($lid)
			$filter['LID'] = $lid;
		if($orderCountFrom)
			$filter['>=ORDER_COUNT'] = $orderCountFrom;
		if($orderCountTo)
			$filter['<ORDER_COUNT'] = $orderCountTo;
		if($orderSumFrom)
			$filter['>=ORDER_SUM'] = $orderSumFrom;
		if($orderSumTo)
			$filter['<ORDER_SUM'] = $orderSumTo;
		if($orderLastDateFrom)
			$filter['>=LAST_ORDER_DATE'] = $orderLastDateFrom;
		if($orderLastDateTo)
			$filter['<LAST_ORDER_DATE'] = $orderLastDateTo;


		$dbBuyerList = \CSaleUser::GetBuyersList(
			array('ID' => 'ASC'),
			$filter,
			false,
			false,
			array("EMAIL", "NAME", "USER_ID", "ID")
		);

		return $dbBuyerList;
	}

	/**
	 * @return string
	 */
	public function getForm()
	{
		$siteInput = '<select name="'.$this->getFieldName('LID').'">';
		$siteDb = \CSite::GetList($by="sort", $order="asc", array("ACTIVE" => "Y"));
		while ($site = $siteDb->Fetch())
		{
			$inputSelected = ($site['LID'] == $this->getFieldValue('LID') ? 'selected' : '');
			$siteInput .= '<option value="'.$site['LID'].'" '.$inputSelected.'>';
			$siteInput .= htmlspecialcharsbx($site['NAME']);
			$siteInput .= '</option>';
		}
		$siteInput .= '</select>';


		$orderCountInput = Loc::getMessage('sender_connector_buyer_from');
		$orderCountInput .= ' <input size=3 type="text" name="'.$this->getFieldName('ORDER_COUNT_FROM').'" value="'.htmlspecialcharsbx($this->getFieldValue('ORDER_COUNT_FROM')).'"> ';
		$orderCountInput .= Loc::getMessage('sender_connector_buyer_to');
		$orderCountInput .= ' <input size=3 type="text" name="'.$this->getFieldName('ORDER_COUNT_TO').'" value="'.htmlspecialcharsbx($this->getFieldValue('ORDER_COUNT_TO')).'">';

		$orderSumInput = Loc::getMessage('sender_connector_buyer_from');
		$orderSumInput .= ' <input size=3 type="text" name="'.$this->getFieldName('ORDER_SUM_FROM').'" value="'.htmlspecialcharsbx($this->getFieldValue('ORDER_SUM_FROM')).'"> ';
		$orderSumInput .= Loc::getMessage('sender_connector_buyer_to');
		$orderSumInput .= ' <input size=3 type="text" name="'.$this->getFieldName('ORDER_SUM_TO').'" value="'.htmlspecialcharsbx($this->getFieldValue('ORDER_SUM_TO')).'">';

		$lastOrderDateInput = CalendarPeriod(
			$this->getFieldName('LAST_ORDER_DATE_FROM'),
			$this->getFieldValue('LAST_ORDER_DATE_FROM'),
			$this->getFieldName('LAST_ORDER_DATE_TO'),
			$this->getFieldValue('LAST_ORDER_DATE_TO'),
			$this->getFieldFormName()
		);

		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_buyer_site').'</td>
					<td>'.$siteInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_buyer_ordercnt').'</td>
					<td>'.$orderCountInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_buyer_ordersum').'</td>
					<td>'.$orderSumInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_buyer_lastorderdate').'</td>
					<td>'.$lastOrderDateInput.'</td>
				</tr>
			</table>
		';
	}
}
