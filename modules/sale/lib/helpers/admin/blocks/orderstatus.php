<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Sale\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\TradingPlatform\OrderTable;

Loc::loadMessages(__FILE__);

class OrderStatus
{
	public static function getEdit(Order $order, \CUser $user, $showCancel, $showSaveButton)
	{
		$data = self::prepareData($order);

		if($showCancel)
			$bUserCanCancelOrder = \CSaleOrder::CanUserCancelOrder($order->getId(), $user->GetUserGroupArray(), $user->GetID());
		else
			$bUserCanCancelOrder = false;

		$result = '
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage("SALE_ORDER_STATUS_CREATED").':</td>
						<td class="adm-detail-content-cell-r">
							<div>'.
								 $data["DATE_INSERT"].
								'&nbsp;<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='. $data["CREATOR_USER_ID"].'">'.
									 htmlspecialcharsbx($data["CREATOR_USER_NAME"]).
								'</a>
							</div>
						</td>
					</tr>
					<tr>
						<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_STATUS_LAST_MODIF").':</td>
						<td class="adm-detail-content-cell-r"><div>'. $data["DATE_UPDATE"].'</div></td>
					</tr>
					<tr>
						<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_STATUS_SITE").':</td>
						<td class="adm-detail-content-cell-r"><div>'.
								htmlspecialcharsbx(
									\Bitrix\Sale\Helpers\Admin\OrderEdit::getSiteName($order->getSiteId())
								).
							'</div></td>
					</tr>';

		if(strlen($data['SOURCE_NAME']) > 0)
		{
			$result .=	'<tr>'.
							'<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_STATUS_SOURCE").':</td>'.
							'<td class="adm-detail-content-cell-r">'.$data['SOURCE_NAME'].'</td>'.
						'</tr>';
		}

		$result .= '<tr>
						<td class="adm-detail-content-cell-l">'.Loc::getMessage("SALE_ORDER_STATUS").':</td>
						<td class="adm-detail-content-cell-r">'.
								\Bitrix\Sale\Helpers\Admin\OrderEdit::makeSelectHtml(
								"STATUS_ID",
								self::getStatusesList($user->GetID(), $data["STATUS_ID"]),
								 $data["STATUS_ID"],
								false,
								array(
									"class" => "adm-bus-select",
									"id" => "STATUS_ID"
								)
							);

		if($showSaveButton)
		{
			$result .= '
									&nbsp;
									<span id="save_status_button" class="adm-btn" onclick="BX.Sale.Admin.OrderEditPage.onSaveStatusButton(\''.$order->getId().'\',\'STATUS_ID\');">
										'.Loc::getMessage("SALE_ORDER_STATUS_SAVE").'
									</span>';
		}

		$result .= '</td>
			</tr>';

		if($showCancel && $bUserCanCancelOrder)
			$result .= self::getCancelBlockHtml($order, $data);

		$result .= '</tbody>
			</table>
			';

		return $result;
	}

	protected static function getCancelBlockHtml(Order $order, array $data)
	{
		$isCanceled = ($order->getField('CANCELED') == "Y" ? true : false);

		if($isCanceled)
		{
			$text = '
				<div class="adm-s-select-popup-element-selected" id="sale-adm-status-cancel-blocktext">
					<div class="adm-s-select-popup-element-selected-bad">
						<span>'.Loc::getMessage("SALE_ORDER_STATUS_CANCELED").'</span>
						'.$order->getField('DATE_CANCELED').'
						<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='. $order->getField("EMP_CANCELED_ID").'">'.
							htmlspecialcharsbx($data["EMP_CANCELED_NAME"]).
						'</a>
					</div>
				</div>';
		}
		else
		{
			$text = '
				<div class="adm-s-select-popup-element-selected" style="text-align:center;" id="sale-adm-status-cancel-blocktext">
					<a href="javascript:void(0);" onclick="BX.Sale.Admin.OrderEditPage.toggleCancelDialog();">
						'.Loc::getMessage("SALE_ORDER_STATUS_CANCELING").'
					</a>
				</div>';
		}

		$reasonCanceled = htmlspecialcharsbx(trim($order->getField("REASON_CANCELED")));
		if(!\CSaleYMHandler::isOrderFromYandex($order->getId()))
		{
			$reasonHtml = '
				<div class="adm-s-select-popup-modal-title">'.Loc::getMessage("SALE_ORDER_STATUS_COMMENT").'</div>
				<textarea style="width:400px;min-height:100px;" name="FORM_REASON_CANCELED" id="FORM_REASON_CANCELED"'.($isCanceled ? ' disabled' : '' ).'>'.(strlen($reasonCanceled) > 0 ? $reasonCanceled : '').'</textarea>
			';
		}
		else
		{
			$reasonHtml = '
				<div class="adm-s-select-popup-modal-title">'.Loc::getMessage("SALE_ORDER_STATUS_CANCELING_REASON").'</div>
				<select name="FORM_REASON_CANCELED" id="FORM_REASON_CANCELED" class="adm-bus-select"'.($isCanceled ? ' disabled' : '' ).'>';

			foreach (\CSaleYMHandler::getOrderSubstatuses() as $statusId => $statusName)
				$reasonHtml .= '<option value="'.$statusId.'"'.($statusId == $reasonCanceled ? " selected" : "").'>'.$statusName.'</option>';

			$reasonHtml .= '</select>';
		}

		return '
			<tr id="sale-adm-status-cancel-row">
				<td class="adm-detail-content-cell-l">&nbsp;</td>
				<td class="adm-detail-content-cell-r">
					<div class="adm-s-select-popup-box">
						<div class="adm-s-select-popup-container">
							<div class="adm-s-select-popup-element-selected-control" onclick="BX.Sale.Admin.OrderEditPage.toggleCancelDialog();"></div>
							'.$text.'
						</div>
						<div class="adm-s-select-popup-modal /*active*/" id="sale-adm-status-cancel-dialog">
							<div class="adm-s-select-popup-modal-content">
								'.$reasonHtml.'
								<div class="adm-s-select-popup-modal-desc">'.Loc::getMessage("SALE_ORDER_STATUS_USER_CAN_VIEW").'</div>
								<span class="adm-btn" id="sale-adm-status-cancel-dialog-btn" onclick="BX.Sale.Admin.OrderEditPage.onCancelStatusButton(\''.$order->getId().'\',\''.$data["CANCELED"].'\');">
									'.($data["CANCELED"] == "N" ? Loc::getMessage("SALE_ORDER_STATUS_CANCEL") : Loc::getMessage("SALE_ORDER_STATUS_CANCEL_CANCEL")).'
								</span>
								<span class="adm-s-select-popup-modal-close" onclick="BX.Sale.Admin.OrderEditPage.toggleCancelDialog();">'.Loc::getMessage("SALE_ORDER_STATUS_TOGGLE").'</span>
							</div>
						</div>
					</div>
				</td>
			</tr>';
	}

	public static function getUserInfo($userId)
	{
		static $users = array();

		$userId = intval($userId);
		if ($userId <= 0)
			return array( "ID"=>0, "NAME" => "", "LOGIN"=>"");

		if (isset($users[$userId]))
			return $users[$userId];

		$dbRes = \CUser::GetList(($by="id"), ($byOrder="asc"), array("ID" => $userId), array("FIELDS" => array("ID", "NAME", "LOGIN")));
		$user = $dbRes->Fetch();

		if ($user)
			$users[$userId] = $user;
		else
			$user = array( "ID"=>0, "NAME" => "", "LOGIN"=>"");

		return $user;
	}

	protected static function prepareData(Order $order)
	{
		static $result = null;

		if($result === null)
		{
			$creator = static::getUserInfo($order->getField("CREATED_BY"));
			$creatorName = $creator["LOGIN"];

			if(strlen($creator["NAME"]) > 0)
				$creatorName = $creator["NAME"]." (".$creatorName.")";

			$canceler = static::getUserInfo($order->getField("EMP_CANCELED_ID"));
			$cancelerName = $canceler["LOGIN"];

			if(strlen($canceler["NAME"]) > 0)
				$cancelerName = $canceler["NAME"]." (".$cancelerName.")";

			$sourceName = "";

			if(strlen($order->getField('XML_ID')) > 0)
			{
				$dbRes = OrderTable::getList(array(
					'filter' => array(
						'ORDER_ID' => $order->getId()
					),
					'select' => array('SOURCE_NAME' => 'TRADING_PLATFORM.NAME')
				));

				if($tpOrder = $dbRes->fetch())
					$sourceName = $tpOrder['SOURCE_NAME'];
			}

			$result = array(
				"DATE_INSERT" => $order->getDateInsert()->toString(),
				"DATE_UPDATE" => $order->getField('DATE_UPDATE')->toString(),
				"CREATOR_USER_NAME" => $creatorName,
				"CREATOR_USER_ID" => $creator["ID"],
				"STATUS_ID" => $order->getField('STATUS_ID'),
				"CANCELED" => $order->getField("CANCELED"),
			    "EMP_CANCELED_NAME" => $cancelerName,
			    "SOURCE_NAME" => $sourceName
			);
		}

		return $result;
	}

	public static function getScripts(Order $order, $userId)
	{
		$langPhrases = array("SALE_ORDER_STATUS_SAVE", "SALE_ORDER_STATUS_CANCEL", "SALE_ORDER_STATUS_CHANGE_ERROR",
			"SALE_ORDER_STATUS_CANCEL_ERROR", "SALE_ORDER_STATUS_CANCEL_CANCEL", "SALE_ORDER_STATUS_CHANGED_SUCCESS",
			"SALE_ORDER_STATUS_CANCELED", "SALE_ORDER_STATUS_CANCELING");

		$result = '
			<script type="text/javascript">
				BX.ready(function(){
					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters({
						STATUS_ID: {
							callback: BX.Sale.Admin.OrderEditPage.setStatus,
							context: this
						}
					});
					BX.Sale.Admin.OrderEditPage.statusesNames = '.\CUtil::PhpToJSObject(self::getStatusesList($userId, $order->getField('STATUS_ID'))).';
					BX.Sale.Admin.OrderEditPage.callFieldsUpdaters('.\CUtil::PhpToJSObject(self::prepareData($order)).');';

		foreach($langPhrases as $phrase)
			$result .= ' BX.message({'.$phrase.': "'.\CUtil::JSEscape(Loc::getMessage($phrase)).'"});';

		$result .= '});	</script>';
		return $result;
	}

	public static function getStatusesList($userId, $orderStatus = false)
	{
		if($orderStatus === false)
			$orderStatus = \Bitrix\Sale\OrderStatus::getInitialStatus();

		return \Bitrix\Sale\OrderStatus::getAllowedUserStatuses($userId, $orderStatus);
	}

	public static function getEditSimple($userId, $fieldName, $status)
	{
		return '
			<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l" width="40%">'.
							Loc::getMessage("SALE_ORDER_STATUS").':'.
						'</td>
						<td class="adm-detail-content-cell-r">'.
							\Bitrix\Sale\Helpers\Admin\OrderEdit::makeSelectHtml(
								$fieldName,
								self::getStatusesList($userId, $status),
								$status,
								false,
								array(
									"class" => "adm-bus-select",
									"id" => "STATUS_ID"
								)
							).
						'</td>
					</tr>
				</tbody>
			</table>';
	}
}