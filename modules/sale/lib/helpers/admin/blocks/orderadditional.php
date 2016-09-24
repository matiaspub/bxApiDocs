<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OrderAdditional
{
	public static function getEdit($collection, $formName, $formPrefix)
	{
		$data = self::prepareData($collection);

		if(get_class($collection) == 'Bitrix\Sale\Order')
			$orderLocked = \Bitrix\Sale\Order::isLocked($collection->getId());
		else
			$orderLocked = false;

		$blockEmpResponsible = '';
		if (isset($data['EMP_RESPONSIBLE']) && !empty($data['EMP_RESPONSIBLE']))
		{
			$blockEmpResponsible = '
				<tr>
					<td class="adm-detail-content-cell-l fwb vat" width="40%"></td>
					<td class="adm-detail-content-cell-r">
						<div>'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_CHANGE_BY').': <span style="color: #66878F" id="order_additional_info_date_responsible">'.$data['DATE_RESPONSIBLE'].'</span>  <a href="" id="order_additional_info_emp_responsible">'.htmlspecialcharsbx($data['EMP_RESPONSIBLE']).'</a></div>
					</td>
				</tr>
			';
		}

		$additionalInfo = '';

		if (isset($data['ADDITIONAL_INFO']) && !empty($data['ADDITIONAL_INFO']))
		{
			$additionalInfo = '
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_ADDITIONAL_INFO').':</td>
						<td class="adm-detail-content-cell-r">'.$data['ADDITIONAL_INFO'].'</td>
					</tr>
				</tbody>
			</table>';
		}

		return '
		<input type="hidden" name="'.$formPrefix.'[RESPONSIBLE_ID]" id="RESPONSIBLE_ID" value="'.$data['RESPONSIBLE_ID'].'" onChange="BX.Sale.Admin.OrderAdditionalInfo.changePerson();">
		<div class="adm-bus-moreInfo_part1">
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_RESPONSIBLE').':</td>
						<td class="adm-detail-content-cell-r">
							<div class="adm-s-order-person-choose">
								<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='. $data["RESPONSIBLE_ID"].'" id="order_additional_info_responsible">'.
									htmlspecialcharsbx($data['RESPONSIBLE']).'
								</a>&nbsp;
								<a class="adm-s-bus-morelinkqhsw" onclick="BX.Sale.Admin.OrderAdditionalInfo.choosePerson(\''.$formName.'\', \''.LANGUAGE_ID.'\');" href="javascript:void(0);">
									'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_CHANGE').'
								</a>
							</div>
						</td>
					</tr>
					'.$blockEmpResponsible.'
				</tbody>
			</table>
		</div>
		<div class="adm-bus-moreInfo_part1-5">
		'.$additionalInfo.'
		</div>
		<div class="adm-s-gray-title">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_COMMENT').'</div>

		<div class="adm-bus-moreInfo_part2">
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_MANAGER_COMMENT').':</td>
						<td class="adm-detail-content-cell-r">
							<div>
								<textarea style="width:400px;min-height:100px;" name="'.$formPrefix.'[COMMENTS]" id="COMMENTS"'.($orderLocked ? ' disabled' : '').'>'
									.htmlspecialcharsbx($data['COMMENTS']).
								'</textarea>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>';
	}

	public static function getView($collection, $formName)
	{
		$data = self::prepareData($collection);
		$blockEmpResponsible = '';

		if(get_class($collection) == 'Bitrix\Sale\Order')
			$orderLocked = \Bitrix\Sale\Order::isLocked($collection->getId());
		else
			$orderLocked = false;

		if (isset($data['EMP_RESPONSIBLE']) && !empty($data['EMP_RESPONSIBLE']))
		{
			$blockEmpResponsible = '
				<tr>
					<td class="adm-detail-content-cell-l vat" width="40%"></td>
					<td class="adm-detail-content-cell-r">
						<div>'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_CHANGE_BY').': <span style="color: #66878F" id="order_additional_info_date_responsible">'.$data['DATE_RESPONSIBLE'].'</span>  <a href="" id="order_additional_info_emp_responsible">'.htmlspecialcharsbx($data['EMP_RESPONSIBLE']).'</a></div>
					</td>
				</tr>
			';
		}

		$additionalInfo = '';

		if (isset($data['ADDITIONAL_INFO']) && !empty($data['ADDITIONAL_INFO']))
		{
			$additionalInfo = '
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_ADDITIONAL_INFO').':</td>
						<td class="adm-detail-content-cell-r">'.$data['ADDITIONAL_INFO'].'</td>
					</tr>
				</tbody>
			</table>';
		}

		return '
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_RESPONSIBLE').':</td>
						<td class="adm-detail-content-cell-r">
							<div>
								<a href="/bitrix/admin/user_edit.php?lang='.LANGUAGE_ID.'&ID='. $data["RESPONSIBLE_ID"].'" id="order_additional_info_responsible">'.
									htmlspecialcharsbx($data['RESPONSIBLE']).'
								</a>
							</div>
						</td>
					</tr>
					'.$blockEmpResponsible.'
				</tbody>
			</table>
			'.$additionalInfo.'
			<table class="adm-detail-content-table edit-table" border="0" width="100%" cellpadding="0" cellspacing="0">
				<tbody>
					<tr>
						<td class="adm-detail-content-cell-l'.($orderLocked ? '' : ' vat').'" width="40%">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_MANAGER_COMMENT').':</td>
						<td class="adm-detail-content-cell-r">'.($orderLocked ? '' : '<a href="javascript:void(0);" style="text-decoration: none; border-bottom: 1px dashed" onClick="BX.Sale.Admin.OrderAdditionalInfo.showCommentsDialog(\''.$collection->getField('ID').'\', BX(\'sale-adm-comments-view\'))">'.Loc::getMessage('SALE_ORDER_ADDITIONAL_INFO_COMMENT_TITLE').'</a>').
							'<pre id="sale-adm-comments-view" style="color:gray; max-width:800px; overflow:auto;">'.(strlen($data['COMMENTS']) ? htmlspecialcharsbx($data['COMMENTS']) : '').'</pre>
						</td>
					</tr>
				</tbody>
			</table>';
	}

	public static function getScripts()
	{
		\Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_additional_info.js");
		return '<script type="text/javascript">'.
			'BX.message({
				SALE_ORDER_ADDITIONAL_INFO_COMMENT_EDIT: "'.\CUtil::jsEscape(Loc::getMessage("SALE_ORDER_ADDITIONAL_INFO_COMMENT_EDIT")).'",
				SALE_ORDER_ADDITIONAL_INFO_COMMENT_SAVE: "'.\CUtil::jsEscape(Loc::getMessage("SALE_ORDER_ADDITIONAL_INFO_COMMENT_SAVE")).'",
				SALE_ORDER_ADDITIONAL_INFO_NO_COMMENT: "'.\CUtil::jsEscape(Loc::getMessage("SALE_ORDER_ADDITIONAL_INFO_NO_COMMENT")).'"
			})'.
			'</script>';
	}

	protected static function prepareData($collection)
	{
		global $USER;
		$data = array();

		if (is_null($collection))
		{
			$data['COMMENTS'] = '';
		}
		else
		{
			if (intval($collection->getField('EMP_RESPONSIBLE_ID')) > 0)
				$data['EMP_RESPONSIBLE'] = \Bitrix\Sale\Helpers\Admin\OrderEdit::getUserName($collection->getField('EMP_RESPONSIBLE_ID'));

			$dateResponsibleId = $collection->getField('DATE_RESPONSIBLE_ID');
			if (!is_null($dateResponsibleId))
				$data['DATE_RESPONSIBLE'] = $dateResponsibleId->toString();

			$data['COMMENTS'] = $collection->getField('COMMENTS');
		}

		if (intval($collection->getField('RESPONSIBLE_ID')) > 0)
		{
			$data['RESPONSIBLE'] = \Bitrix\Sale\Helpers\Admin\OrderEdit::getUserName($collection->getField('RESPONSIBLE_ID'));
			$data['RESPONSIBLE_ID'] = intval($collection->getField('RESPONSIBLE_ID'));
		}
		else
		{
			$data['RESPONSIBLE_ID'] = 0;
		}


		if(in_array("ADDITIONAL_INFO", $collection->getAvailableFields()))
			if(strlen($collection->getField("ADDITIONAL_INFO")) > 0)
				$data["ADDITIONAL_INFO"] = $collection->getField("ADDITIONAL_INFO");
		
		return $data;
	}
}