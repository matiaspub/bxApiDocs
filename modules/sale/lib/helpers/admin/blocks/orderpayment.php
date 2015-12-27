<?php

namespace Bitrix\Sale\Helpers\Admin\Blocks;

use Bitrix\Main\Entity\EntityError;
use Bitrix\Sale\Helpers\Admin\OrderEdit;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\Internals\PaySystemInner;
use \Bitrix\Sale\PaySystemService;
use Bitrix\Sale\Internals\CompanyTable;
use Bitrix\Sale\Order;
use Bitrix\Main\Page\Asset;
use Bitrix\Sale\Internals\PaySystemServiceTable;
use Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Type\Date;
use \Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Result;
use Bitrix\Main;
use Bitrix\Sale\UserMessageException;

Loc::loadMessages(__FILE__);

class OrderPayment
{
	/** @var $order \Bitrix\Sale\Order */
	private static $order = null;
	private static $defaultFields = null;

	private static function prepareData($item, $error = false)
	{
		/** @var $item \Bitrix\Sale\Payment */

		global $USER;
		static $users = array();

		if (is_null(self::$order))
			self::$order = $item->getCollection()->getOrder();

		$fields = ($error) ? self::$defaultFields : $item->getFieldValues();

		$fields['EMP_PAID_ID_NAME'] = '';
		$fields['EMP_PAID_ID_LAST_NAME'] = '';

		$empPaidId = $fields['EMP_PAID_ID'];
		if ($empPaidId > 0)
		{
			if (!array_key_exists($item->getField('EMP_PAID_ID'), $users))
				$users[$empPaidId] = $USER->GetByID($empPaidId)->Fetch();
			$fields['EMP_PAID_ID_NAME'] = $users[$empPaidId]['NAME'];
			$fields['EMP_PAID_ID_LAST_NAME'] = $users[$empPaidId]['LAST_NAME'];
		}

		$fields['ORDER_ID'] = self::$order->getId();
		$fields['SUM'] = $item->getSum();
		$fields['ORDER_PRICE'] = self::$order->getPrice();
		$fields['ORDER_PAYMENT_SUM'] = $item->getCollection()->getSum();
		$fields['CURRENCY'] = self::$order->getCurrency();
		$fields['PERSON_TYPE_ID'] = self::$order->getPersonTypeId();
		$fields['SITE_ID'] = self::$order->getSiteId();

		return $fields;
	}

	/**
	 * @param $paySystemId
	 * @param $personTypeId
	 * @return mixed
	 * @throws Main\ArgumentException
	 */
	public static function getPaySystemParams($paySystemId, $personTypeId)
	{
		static $result = array();

		if (!isset($result[$paySystemId]))
		{
			$data = array();
			if ($paySystemId > 0)
			{
				$params = array(
					'select' => array('NAME', 'LOGOTIP', 'HAVE_RESULT', 'RESULT_FILE', 'ACTION_FILE'),
					'filter' => array(
						"LOGIC" => "AND",
						array(
							'PAY_SYSTEM_ID' => $paySystemId
						),
						array(
							"LOGIC" => "OR",
							array(
								'PERSON_TYPE_ID' => $personTypeId
							),
							array(
								'PERSON_TYPE_ID' => 0
							)
						)
					)
				);
				$res = PaySystemActionTable::getList($params);
				$data = $res->fetch();
			}

			$path = "";
			$result[$paySystemId] =  $data;

			if(intval($data["LOGOTIP"]) > 0)
				$path = \CFile::GetPath(intval($data['LOGOTIP']));

			$result[$paySystemId]["LOGOTIP_PATH"] = !empty($path) ? $path : '/bitrix/images/sale/nopaysystem.gif';
		}

		return $result[$paySystemId];
	}

	/**
	 * @param $payment
	 * @param $data
	 * @return mixed
	 */
	public static function modifyData($payment, $data)
	{
		/** @var \Bitrix\sale\Order $order */

		if (is_null(self::$order))
			self::$order = $payment->getCollection()->getOrder();

		if (!$data['ERROR'])
			$data['ERROR'] = 'Y';
		$data['ID'] = $data['PAYMENT_ID'];
		$data['CURRENCY'] = self::$order->getCurrency();
		$data['PERSON_TYPE_ID'] = self::$order->getPersonTypeId();
		$data['SITE_ID'] = self::$order->getSiteId();

		return $data;
	}

	/**
	 * @param \Bitrix\Sale\Payment $payment
	 * @param int $index
	 * @param $dataForRecovery
	 * @return string
	 */

	public static function getEdit($payment, $index = 1, $dataForRecovery = array())
	{
		$data = self::prepareData($payment, !empty($dataForRecovery));
		$result = self::getEditTemplate($data, $index, $dataForRecovery);

		return $result;
	}

	/**
	 * @param $payment
	 * @param int $index
	 * @param string $form
	 * @return string
	 */

	public static function getView($payment, $index = 1, $form='')
	{
		$data = self::prepareData($payment);
		return self::getViewTemplate($data, $index, $form);
	}

	/**
	 * @return string
	 */

	public static function getScripts()
	{
		Asset::getInstance()->addJs("/bitrix/js/sale/admin/order_payment.js");
		$imgPathList = self::getImgPathList();
		$message = array(
			'PAYMENT_PAID_NO' => Loc::getMessage('SALE_ORDER_PAYMENT_STATUS_NO'),
			'PAYMENT_PAID_YES' => Loc::getMessage('SALE_ORDER_PAYMENT_STATUS_YES'),
			'PAYMENT_PAID_RETURN' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN'),
			'PAYMENT_PAID_CANCEL' => Loc::getMessage('SALE_ORDER_PAYMENT_CANCEL'),
			'PAYMENT_RETURN_COMMENT' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_COMMENT'),
			'PAYMENT_RETURN_NUM' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_NUM'),
			'PAYMENT_RETURN_DATE' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_DATE'),
			'PAYMENT_OPERATION_TITLE' => Loc::getMessage('SALE_ORDER_PAYMENT_OPERATION_TITLE'),
			'PAYMENT_OPERATION_RETURN' => Loc::getMessage('SALE_ORDER_PAYMENT_OPERATION_RETURN'),
			'PAYMENT_RETURN_DATE_ALT' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_DATE_ALT'),
			'PAYMENT_WINDOW_RETURN_TITLE' => Loc::getMessage('SALE_ORDER_PAYMENT_WINDOW_RETURN_TITLE'),
			'PAYMENT_WINDOW_CANCEL_TITLE' => Loc::getMessage('SALE_ORDER_PAYMENT_WINDOW_CANCEL_TITLE'),
			'PAYMENT_WINDOW_RETURN_BUTTON_SAVE' => Loc::getMessage('SALE_ORDER_PAYMENT_WINDOW_RETURN_BUTTON_SAVE'),
			'PAYMENT_RETURN_NUM_DOC' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_NUM_DOC'),
			'PAYMENT_RETURN_SUM' => Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_SUM'),
			'PAYMENT_OPERATION_CANCEL' => Loc::getMessage('SALE_ORDER_PAYMENT_OPERATION_CANCEL'),
			'PAYMENT_TOGGLE_DOWN' => Loc::getMessage('SALE_ORDER_PAYMENT_TOGGLE_DOWN'),
			'PAYMENT_TOGGLE_UP' => Loc::getMessage('SALE_ORDER_PAYMENT_TOGGLE_UP'),
			'PAYMENT_PAY_VOUCHER_NUM' => Loc::getMessage('SALE_ORDER_PAYMENT_PAY_VOUCHER_NUM'),
			'PAYMENT_PAY_VOUCHER_DATE' => Loc::getMessage('SALE_ORDER_PAYMENT_PAY_VOUCHER_DATE'),
			'PAYMENT_WINDOW_VOUCHER_TITLE' => Loc::getMessage('SALE_ORDER_PAYMENT_WINDOW_VOUCHER_TITLE'),
			'PAYMENT_USE_INNER_BUDGET' => Loc::getMessage('SALE_ORDER_PAYMENT_USE_INNER_BUDGET'),
			'PAYMENT_ORDER_STATUS' => Loc::getMessage('SALE_ORDER_PAYMENT_ORDER_STATUS'),
			'PAYMENT_CONFIRM_DELETE' => Loc::getMessage('SALE_ORDER_PAYMENT_CONFIRM_DELETE')
		);
		return '<script type="text/javascript">
			BX.message('.\CUtil::PhpToJSObject($message).');
			logoList = '.\CUtil::PhpToJSObject($imgPathList).';

			BX.ready(function(){
				if(BX.Sale.Admin.OrderEditPage && BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters)
					BX.Sale.Admin.OrderEditPage.registerFieldsUpdaters( BX.Sale.Admin.OrderPayment.prototype.getCreateOrderFieldsUpdaters() );
			});

		</script>';
	}

	private static function getEditTemplate($data, $index, $post = array())
	{
		$paid = ($post) ? $post['PAID'] : $data['PAID'];
		$id = ($post) ? $post['PAYMENT_ID'] : $data['ID'];
		$paidString = ($paid == 'Y') ? 'YES' : 'NO';
		if (!$post)
		{
			if ($data['SUM'] > 0)
				$sum = $data['SUM'];
			else
				$sum = ($data['ORDER_PRICE'] - $data['ORDER_PAYMENT_SUM'] <= 0) ? 0 : $data['ORDER_PRICE'] - $data['ORDER_PAYMENT_SUM'];
		}
		else
		{
			$sum = $post['SUM'];
		}

		$psData = self::getPaySystemParams(
			(isset($post['PAY_SYSTEM_ID'])) ? $post['PAY_SYSTEM_ID'] : $data['PAY_SYSTEM_ID'],
			$data['PERSON_TYPE_ID']
		);

		if (isset($psData["LOGOTIP_PATH"]))
			$data['PAY_SYSTEM_LOGOTIP'] = $psData["LOGOTIP_PATH"];

		$paymentStatus = '<span><span id="BUTTON_PAID_'.$index.'" '.(($paid != 'Y') ? 'class="notpay"' : '').'>'.Loc::getMessage('SALE_ORDER_PAYMENT_STATUS_'.$paidString).'</span><span class="triangle"> &#9662;</span></span>';

		$note = BeginNote();
		$note .= Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_ALERT');
		$note .= EndNote();

		$hiddenPaySystemInnerId = '';
		if ($index == 1)
			$hiddenPaySystemInnerId = '<input type="hidden" value="'.PaySystemInner::getId().'" id="PAYMENT_INNER_BUDGET_ID">';

		$notPaidBlock = ($paid == 'N' && !empty($data['EMP_PAID_ID'])) ? '' : 'style="display:none;"';

		$return = ($post['IS_RETURN'] == 'Y') ? '' : 'style="display:none;"';
		$returnInformation = '
		<tr '.$return.' class="return">
			<td class="adm-detail-content-cell-l fwb">'.Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_TO').':</td>
			<td class="adm-detail-content-cell-r">
				<select name="PAYMENT['.$index.'][OPERATION_ID]" id="OPERATION_ID_'.$index.'" class="adm-bus-select">
					<option value="RETURN">'.Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_ACCOUNT').'</option>
				</select>
			</td>
		</tr>
		<tr '.$return.' class="return">
			<td colspan="2" style="text-align: center">'.$note.'</td>
		</tr>
		<tr '.$notPaidBlock.' class="not_paid">
			<td class="adm-detail-content-cell-l" width="40%"><br>'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_RETURN_NUM').':</td>
			<td class="adm-detail-content-cell-r tal">
				<br>
				<input type="text" class="adm-bus-input" name="PAYMENT['.$index.'][PAY_RETURN_NUM]" id="PAYMENT_RETURN_NUM_'.$index.'" value="'.htmlspecialcharsbx(($post['PAY_RETURN_NUM']) ? $post['PAY_RETURN_NUM'] : $data['PAY_RETURN_NUM']).'" maxlength="20">
			</td>
		</tr>
		<tr '.$notPaidBlock.' class="not_paid">
			<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_RETURN_DATE').':</td>
			<td class="adm-detail-content-cell-r tal">
				<div class="adm-input-wrap adm-calendar-second" style="display: inline-block;">
					<input type="text" class="adm-input adm-calendar-to" name="PAYMENT['.$index.'][PAY_RETURN_DATE]" id="PAYMENT_RETURN_DATE_'.$index.'" size="15" value="'.htmlspecialcharsbx(($post['PAY_RETURN_DATE']) ? $post['PAY_RETURN_DATE'] : $data['PAY_RETURN_DATE']).'">
					<span class="adm-calendar-icon" title="'.Loc::getMessage('SALE_ORDER_PAYMENT_CHOOSE_DATE').'" onclick="BX.calendar({node:this, field:\'PAYMENT_RETURN_DATE_'.$index.'\', form: \'\', bTime: false, bHideTime: false});"></span>
				</div>
			</td>
		</tr>
		<tr '.$notPaidBlock.' class="not_paid">
			<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_RETURN_COMMENT').':</td>
			<td class="adm-detail-content-cell-r tal">
				<div class="adm-input-wrap adm-calendar-second" style="display: inline-block;">
					<textarea name="PAYMENT['.$index.'][PAY_RETURN_COMMENT]" id="PAYMENT_RETURN_COMMENTS_'.$index.'">'.htmlspecialcharsbx(isset($post['PAY_RETURN_COMMENT']) ? $post['PAY_RETURN_COMMENT'] : $data['PAY_RETURN_COMMENT']).'</textarea>
				</div>
			</td>
		</tr>';

		$lang = Main\Application::getInstance()->getContext()->getLanguage();
		$title = ($id > 0) ? Loc::getMessage('SALE_ORDER_PAYMENT_BLOCK_EDIT_PAYMENT_TITLE').'#'.$id : Loc::getMessage('SALE_ORDER_PAYMENT_BLOCK_NEW_PAYMENT_TITLE').'#'.$index;
		$curFormat = \CCurrencyLang::getCurrencyFormat($data['CURRENCY']);
		$currencyLang = trim(str_replace("#", '', $curFormat["FORMAT_STRING"]));
		$disabled = ($data['PAID'] == 'Y') ? 'readonly' : '';

		$companyList = OrderEdit::getCompanyList();
		if (!empty($companyList))
		{
			$companies = \Bitrix\Sale\Helpers\Admin\OrderEdit::makeSelectHtml(
					'PAYMENT['.$index.'][COMPANY_ID]',
					$companyList,
					isset($post["COMPANY_ID"]) ? $post["COMPANY_ID"] : $data["COMPANY_ID"],
					true,
					array(
					"class" => "adm-bus-select",
					"id" => "COMPANY_ID"
				)
			);
		}
		else
		{
			$companies = str_replace("#URL#", "/bitrix/admin/sale_company_edit.php?lang=".$lang, Loc::getMessage('SALE_ORDER_PAYMENT_ADD_COMPANY'));
		}

		$result = '<div>
			<div class="adm-bus-pay" id="payment_container_'.$index.'">
				<input type="hidden" name="PAYMENT['.$index.'][PAYMENT_ID]" id="payment_id_'.$index.'" value="'.$id.'">
				<input type="hidden" name="PAYMENT['.$index.'][INDEX]" value="'.$index.'" class="index">
				<input type="hidden" name="PAYMENT['.$index.'][PAID]" id="PAYMENT_PAID_'.$index.'" value="'.(empty($paid) ? 'N' : $paid).'">
				<input type="hidden" name="PAYMENT['.$index.'][IS_RETURN]" id="PAYMENT_IS_RETURN_'.$index.'" value="'.($post['IS_RETURN'] ? $post['IS_RETURN'] : 'N').'">
				'.$hiddenPaySystemInnerId.'
				<div class="adm-bus-component-content-container">
					<div class="adm-bus-pay-section">
						<div class="adm-bus-pay-section-title-container">
							<div class="adm-bus-pay-section-title">'.$title.'</div>
							<div class="adm-bus-pay-section-action-block">'.
								((!isset($data['ID']) || $data['ID'] <= 0) ? '<div class="adm-bus-pay-section-action" id="SECTION_'.$index.'_DELETE">'.Loc::getMessage('SALE_ORDER_PAYMENT_DELETE').'</div>' : '')
							.'</div>
						</div>
						<div class="adm-bus-pay-section-content" id="SECTION_'.$index.'">
							<div class="adm-bus-pay-section-sidebar">
								<div class="adm-bus-pay-section-sidebar-service-logo">
									<img id="LOGOTIP_'.$index.'" src="'.$data['PAY_SYSTEM_LOGOTIP'].'" alt="">
								</div>
							</div>
							<div class="adm-bus-pay-section-right">
								<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_METHOD').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l fwb" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_SYSTEM').':</td>
												<td class="adm-detail-content-cell-r">'.
												self::makePaymentSelectHtml(
													'PAYMENT['.$index.'][PAY_SYSTEM_ID]',
													self::getPaySystemList(),
													(isset($post['PAY_SYSTEM_ID'])) ? $post['PAY_SYSTEM_ID'] : $data['PAY_SYSTEM_ID'],
													array(
														"class" => "adm-bus-select",
														"id" => "PAY_SYSTEM_ID_".$index
													)
												)
												.'</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_SUM').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAYABLE_SUM').':</td>
												<td class="adm-detail-content-cell-r tal"><input type="text" class="adm-bus-input-price" name="PAYMENT['.$index.'][SUM]" id="PAYMENT_SUM_'.$index.'" value="'.round($sum, 2).'" '.$disabled.'> '.$currencyLang.'<br></td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="adm-bus-table-container caption border" style="padding-top:10px;">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_STATUS').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table alternation edit-table" id="PAYMENT_BLOCK_STATUS_'.$index.'">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l vat payment-status" width="40%">
													'.$paymentStatus.'
												</td>
												<td class="adm-detail-content-cell-r tal" id="PAYMENT_CHANGE_USER_INFO_'.$index.'">
													'.$data['DATE_PAID'].'
													<a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_PAID_ID'].'">'.htmlspecialcharsbx($data['EMP_PAID_ID_NAME']).' '.htmlspecialcharsbx($data['EMP_PAID_ID_LAST_NAME']).'</a>
												</td>
											</tr>
										</tbody>
									</table>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table" id="PAYMENT_BLOCK_STATUS_INFO_'.$index.'">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%"><br>'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_VOUCHER_NUM').':</td>
												<td class="adm-detail-content-cell-r tal">
													<br>
													<input type="text" class="adm-bus-input" id="PAYMENT_NUM" name="PAYMENT['.$index.'][PAY_VOUCHER_NUM]" value="'.htmlspecialcharsbx(isset($post['PAY_VOUCHER_NUM']) ? $post['PAY_VOUCHER_NUM'] : $data['PAY_VOUCHER_NUM']).'" maxlength="20">
												</td>
											</tr>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_VOUCHER_DATE').':</td>
												<td class="adm-detail-content-cell-r tal">
													<div class="adm-input-wrap adm-calendar-second" style="display: inline-block;">
														<input type="text" class="adm-input adm-calendar-to" id="PAYMENT_DATE_'.$index.'" name="PAYMENT['.$index.'][PAY_VOUCHER_DATE]" size="15" value="'.htmlspecialcharsbx(($post['PAY_VOUCHER_DATE']) ? $post['PAY_VOUCHER_DATE'] : $data['PAY_VOUCHER_DATE']).'">
														<span class="adm-calendar-icon" title="'.Loc::getMessage('SALE_ORDER_PAYMENT_CHOOSE_DATE').'" onclick="BX.calendar({node:this, field:\'PAYMENT_DATE_'.$index.'\', form: \'\', bTime: false, bHideTime: false});"></span>
													</div>
												</td>
											</tr>
											'.$returnInformation.'
										</tbody>
									</table>
								</div>';
		if (isset($data['PS_STATUS']) && !empty($data['PS_STATUS']))
		{
			$result .= '<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS_TITLE').'</div>
									<a href="javascript:void(0);" id="PS_INFO_'.$index.'">'.Loc::getMessage('SALE_ORDER_PAYMENT_TOGGLE_DOWN').'</a>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table" style="display: none">
										<tbody>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS').':</td>
											<td class="adm-detail-content-cell-r tal">'.$data['PS_STATUS'].'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS_CODE').':</td>
											<td class="adm-detail-content-cell-r tal">'.$data['PS_STATUS_CODE'].'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS_DESCRIPTION').':</td>
											<td class="adm-detail-content-cell-r tal">'.$data['PS_STATUS_DESCRIPTION'].'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_CURRENCY').':</td>
											<td class="adm-detail-content-cell-r tal">'.$data['PS_CURRENCY'].'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_SUM').':</td>
											<td class="adm-detail-content-cell-r tal">'.$data['PS_SUM'].'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_DATE').':</td>
											<td class="adm-detail-content-cell-r tal">'.$data['PS_RESPONSE_DATE'].'</td>
										</tr>
										</tbody>
									</table>
								</div>';
		}
		$result .=	'
								<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_BLOCK_COMPANY').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_COMPANY_BY').':</td>
												<td class="adm-detail-content-cell-r">'.$companies.'</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
						<div class="clb"></div>
					</div>
				</div>
			</div>
		</div>
		</div>';

		$params = array(
			'index' => $index,
			'functionOnSave' => 'saveInHiddenFields',
			'isPaid' => ($data['PAID'] == 'Y'),
			'viewForm' => false,
			'isAvailableChangeStatus' => true
		);
		$result .= self::initJsPayment($params);
		return $result;
	}

	private static function getViewTemplate($data, $index, $form)
	{
		$psData = self::getPaySystemParams(
			$data['PAY_SYSTEM_ID'],
			$data['PERSON_TYPE_ID']
		);

		if (isset($psData["LOGOTIP_PATH"]))
			$data['PAY_SYSTEM_LOGOTIP'] = $psData["LOGOTIP_PATH"];

		$psResult = '';
		if ($psData['HAVE_RESULT'] == 'Y' && $form != 'edit')
		{
			$psResult .= '&nbsp;&nbsp;&nbsp;<span style="border-bottom: 1px dashed #658d0f; color: #658d0f; cursor: pointer" id="ps_update">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_SYSTEM_CHECK').'</span>';
		}

		$lang = Main\Application::getInstance()->getContext()->getLanguage();
		$paidString = ($data['PAID'] == 'Y') ? 'YES' : 'NO';
		$isActive = ($form != 'edit');
		$triangle = ($isActive) ? '<span class="triangle"> &#9662;</span>' : '';

		if ($data['PAID'] == 'Y')
			$class = (!$isActive) ? 'class="not_active"' : '';
		else
			$class = (!$isActive) ? 'class="notpay not_active"' : 'class="notpay"';
		$paymentStatus = '<span><span id="BUTTON_PAID_'.$index.'" '.$class.'>'.Loc::getMessage('SALE_ORDER_PAYMENT_STATUS_'.$paidString).'</span>'.$triangle.'</span>';

		$res = CompanyTable::getList(array(
			'select' => array('NAME'),
			'filter' => array('ID' => $data['COMPANY_ID'])
		));
		$company = $res->fetch();

		$paymentStatusBlockVoucherNum = '';
		if (strlen($data['PAY_VOUCHER_NUM']) > 0)
		{
			$paymentStatusBlockVoucherNum = '<tr>
										<td class="adm-detail-content-cell-l" width="40%"><br>'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_VOUCHER_NUM').':</td>
										<td class="adm-detail-content-cell-r tal">
											<br>
											'.htmlspecialcharsbx($data['PAY_VOUCHER_NUM']).'
										</td>
									</tr>';
		}

		$paymentStatusBlockVoucherDate = '';
		if (strlen($data['PAY_VOUCHER_DATE']) > 0)
		{
			$paymentStatusBlockVoucherDate = '<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_VOUCHER_DATE').':</td>
												<td class="adm-detail-content-cell-r tal">
													<div class="adm-input-wrap adm-calendar-second" style="display: inline-block;">'.htmlspecialcharsbx($data['PAY_VOUCHER_DATE']).'</div>
												</td>
											</tr>';
		}

		$paymentStatusBlockReturnNum = '';
		if (strlen($data['PAY_RETURN_NUM']) > 0)
		{
			$paymentStatusBlockReturnNum = '<tr>
			<td class="adm-detail-content-cell-l" width="40%"><br>'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_RETURN_NUM').':</td>
			<td class="adm-detail-content-cell-r tal">
				<br>'.htmlspecialcharsbx($data['PAY_RETURN_NUM']).'</td>
			</tr>';
		}

		$paymentStatusBlockReturnDate = '';
		if (strlen($data['PAY_RETURN_DATE']) > 0)
		{
			$paymentStatusBlockReturnDate = '<tr>
				<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_RETURN_DATE').':</td>
				<td class="adm-detail-content-cell-r tal">
					<div class="adm-input-wrap adm-calendar-second" style="display: inline-block;">'.htmlspecialcharsbx($data['PAY_RETURN_DATE']).'</div>
				</td>
			</tr>';
		}

		$result = '
		<div>
			<div class="adm-bus-pay" id="payment_container_'.$index.'">
				<input type="hidden" name="PAYMENT['.$index.'][PAYMENT_ID]" id="PAYMENT_ID_'.$index.'" value="'.$data['ID'].'">
				<div class="adm-bus-component-content-container">
					<div class="adm-bus-pay-section">
						<div class="adm-bus-pay-section-title-container">
							<div class="adm-bus-pay-section-title">'.Loc::getMessage('SALE_ORDER_PAYMENT_BLOCK_EDIT_PAYMENT_TITLE').' <span id="payment_'.$data['ID'].'">#'.$data['ID'].'</span>'.(($data['PAY_VOUCHER_DATE']) ? ' '.Loc::getMessage('SALE_ORDER_PAYMENT_FROM').' '.$data['PAY_VOUCHER_DATE'] : '').'</div>
							<div class="adm-bus-pay-section-action-block">
								<div class="adm-bus-pay-section-action" id="SECTION_'.$index.'_DELETE">'.Loc::getMessage('SALE_ORDER_PAYMENT_DELETE').'</div>
								<div class="adm-bus-pay-section-action" id="SECTION_'.$index.'_EDIT"><a href="/bitrix/admin/sale_order_payment_edit.php?order_id='.$data['ORDER_ID'].'&payment_id='.$data['ID'].'&backurl='.urlencode($_SERVER['REQUEST_URI']).'">'.Loc::getMessage('SALE_ORDER_PAYMENT_EDIT').'</a></div>
								<div class="adm-bus-pay-section-action" id="SECTION_'.$index.'_TOGGLE">'.Loc::getMessage('SALE_ORDER_PAYMENT_TOGGLE_UP').'</div>
							</div>
						</div>
						<div class="adm-bus-pay-section-content" id="SECTION_'.$index.'">
							<div class="adm-bus-pay-section-sidebar">
								<div class="adm-bus-pay-section-sidebar-service-logo">
									<img id="LOGOTIP_'.$index.'" src="'.$data['PAY_SYSTEM_LOGOTIP'].'" alt="">
								</div>
							</div>
							<div class="adm-bus-pay-section-right">
								<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_METHOD').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAY_SYSTEM').':</td>
												<td class="adm-detail-content-cell-r">'.htmlspecialcharsbx($data['PAY_SYSTEM_NAME']).' '.$psResult.'</td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_SUM').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PAYABLE_SUM').':</td>
												<td class="adm-detail-content-cell-r tal">'.SaleFormatCurrency($data['SUM'], $data['CURRENCY']).'<br></td>
											</tr>
										</tbody>
									</table>
								</div>
								<div class="adm-bus-table-container caption border" style="padding-top:10px;">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_STATUS').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table alternation edit-table" id="PAYMENT_BLOCK_STATUS_'.$index.'">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l vat payment-status" width="40%">
													'.$paymentStatus.'
												</td>
												<td class="adm-detail-content-cell-r tal" id="PAYMENT_CHANGE_USER_INFO_'.$index.'">
													'.$data['DATE_PAID'].'
													<a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_PAID_ID'].'">'.htmlspecialcharsbx($data['EMP_PAID_ID_NAME']).' '.htmlspecialcharsbx($data['EMP_PAID_ID_LAST_NAME']).'</a>
												</td>
											</tr>
										</tbody>
									</table>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table">
										<tbody>
										'.$paymentStatusBlockVoucherNum.$paymentStatusBlockVoucherDate.$paymentStatusBlockReturnNum.$paymentStatusBlockReturnDate.'
										</tbody>
									</table>
								</div>';
		if (isset($data['PS_STATUS']) && !empty($data['PS_STATUS']))
		{
			$result .= '<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS_TITLE').'</div>
									<a href="javascript:void(0);" id="PS_INFO_'.$index.'">'.Loc::getMessage('SALE_ORDER_PAYMENT_TOGGLE_DOWN').'</a>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table" style="display: none">
										<tbody>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS').':</td>
											<td class="adm-detail-content-cell-r tal">'.htmlspecialcharsbx($data['PS_STATUS']).'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS_CODE').':</td>
											<td class="adm-detail-content-cell-r tal">'.htmlspecialcharsbx($data['PS_STATUS_CODE']).'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_STATUS_DESCRIPTION').':</td>
											<td class="adm-detail-content-cell-r tal">'.htmlspecialcharsbx($data['PS_STATUS_DESCRIPTION']).'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_CURRENCY').':</td>
											<td class="adm-detail-content-cell-r tal">'.htmlspecialcharsbx($data['PS_CURRENCY']).'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_SUM').':</td>
											<td class="adm-detail-content-cell-r tal">'.htmlspecialcharsbx($data['PS_SUM']).'</td>
										</tr>
										<tr>
											<td class="adm-detail-content-cell-l vat" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_PS_DATE').':</td>
											<td class="adm-detail-content-cell-r tal">'.htmlspecialcharsbx($data['PS_RESPONSE_DATE']).'</td>
										</tr>
										</tbody>
									</table>
								</div>';
		}
		$result .= '<div class="adm-bus-table-container caption border">
									<div class="adm-bus-table-caption-title" style="background: #eef5f5;">'.Loc::getMessage('SALE_ORDER_PAYMENT_BLOCK_COMPANY').'</div>
									<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table edit-table ">
										<tbody>
											<tr>
												<td class="adm-detail-content-cell-l" width="40%">'.Loc::getMessage('SALE_ORDER_PAYMENT_COMPANY_BY').':</td>
												<td class="adm-detail-content-cell-r">'.(isset($company['NAME']) && !empty($company['NAME']) ? htmlspecialcharsbx($company['NAME']) : Loc::getMessage('SALE_ORDER_PAYMENT_NO_COMPANY')).'</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
							<div class="clb"></div>
						</div>
						'.self::getShortViewTemplate($data, $index, $form).'
					</div>
				</div>
			</div>
		</div>';

		$params = array(
			'index' => $index,
			'functionOnSave' => 'sendAjax',
			'viewForm' => true,
			'isPaid' => ($data['PAID'] == 'Y'),
			'isAvailableChangeStatus' => $isActive
		);
		$result .= self::initJsPayment($params);
		return $result;
	}

	private static function getShortViewTemplate($data, $index, $form)
	{
		$lang = Main\Application::getInstance()->getContext()->getLanguage();
		$paidString = ($data['PAID'] == 'Y') ? 'YES' : 'NO';
		$isActive = ($form != 'edit');
		$triangle = ($isActive) ? '<span class="triangle"> &#9662;</span>' : '';

		if ($data['PAID'] == 'Y')
			$class = (!$isActive) ? 'class="not_active"' : '';
		else
			$class = (!$isActive) ? 'class="notpay not_active"' : 'class="notpay"';
		$paymentStatus = '<span><span id="BUTTON_PAID_'.$index.'_SHORT" '.$class.'>'.Loc::getMessage('SALE_ORDER_PAYMENT_STATUS_'.$paidString).'</span>'.$triangle.'</span>';

		$result = '<div class="adm-bus-pay-section-content" id="SECTION_SHORT_'.$index.'" style="display:none">
						<div class="adm-bus-pay-section-sidebar">
							<div class="adm-bus-pay-section-sidebar-service-logo">
								<img id="LOGOTIP_SHORT_'.$index.'" src="'.$data['PAY_SYSTEM_LOGOTIP'].'" alt="">
							</div>
						</div>
						<div class="adm-bus-pay-section-right">
							<table border="0" cellspacing="0" cellpadding="0" width="100%" class="adm-detail-content-table alternation edit-tab" id="PAYMENT_BLOCK_STATUS_'.$index.'">
								<tbody>
									<tr>
										<td class="adm-detail-content-cell-l vat payment-status" width="40%">
											'.$paymentStatus.'
										</td>
										<td class="adm-detail-content-cell-r tal" id="PAYMENT_CHANGE_USER_INFO_'.$index.'">
											'.$data['DATE_PAID'].'
											<a href="/bitrix/admin/user_edit.php?lang='.$lang.'&ID='.$data['EMP_PAID_ID'].'">'.htmlspecialcharsbx($data['EMP_PAID_ID_NAME']).' '.htmlspecialcharsbx($data['EMP_PAID_ID_LAST_NAME']).'</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div class="clb"></div>
					</div>';
		return $result;
	}

	private static function initJsPayment($params)
	{
		return "<script>
				BX.ready( function(){
					var obPayment_".$params['index']." = new BX.Sale.Admin.OrderPayment(".\CUtil::PhpToJSObject($params).");
				});
				</script>";
	}

	/**
	 * @param null $order
	 * @return array
	 */
	public static function getPaySystemList($order = null)
	{
		$result = array();

		$result[] = array(
			'ID' => '0',
			'NAME' => Loc::getMessage('SALE_ORDER_PAYMENT_NO_PAYSYSTEM')
		);

		if ($order)
			self::$order = $order;
		else if (!self::$order)
			return $result;

		$res = \CSaleUserAccount::getList(
			array(),
			array(
				'USER_ID' => self::$order->getUserId(),
				'CURRENCY' => self::$order->getCurrency(),
				'LOCKED' => 'N'
			),
			false,
			false,
			array(
				'CURRENT_BUDGET'
			)
		);
		$userAccount = $res->Fetch();

		$dbRes = PaySystemServiceTable::getListWithInner(
			array(
				'select' => array('ID', 'NAME'),
				'filter' => array(
					'=ACTION.PERSON_TYPE_ID' => self::$order->getPersonTypeId(),
					'=ACTION.HAVE_PAYMENT' => 'Y',
					'=ACTIVE' => 'Y'
				),
				'order' => array('SORT' => 'ASC')
			)
		);

		while ($paySystem = $dbRes->fetch())
		{
			if (($userAccount['CURRENT_BUDGET'] <=0) && ($paySystem["ID"] == PaySystemInner::getId()))
				continue;

			$result[] = array(
				'ID' => $paySystem['ID'],
				'NAME' => $paySystem["NAME"]." [".$paySystem["ID"]."]"
			);
		}

		return $result;
	}

	/**
	 * @param string $name
	 * @param array $data
	 * @param string $selected
	 * @param array $attributes
	 * @return string "<select>....</select>"
	 * @throws Main\ArgumentTypeException
	 */
	public static function makePaymentSelectHtml($name, array $data, $selected = "", $attributes = array())
	{
		if(!is_array($data))
			throw new Main\ArgumentTypeException("data", "array");

		if(!is_array($attributes))
			throw new Main\ArgumentTypeException("attributies", "array");

		$result = '<select name="'.htmlspecialcharsbx($name).'"';

		foreach($attributes as $attrName => $attrValue )
			$result .= " ".$attrName."=\"".htmlspecialcharsbx($attrValue)."\"";

		$result .= '>';

		$result .= self::makePaymentSelectHtmlBody($data, $selected);
		$result .= '</select>';

		return $result;
	}

	public static function makePaymentSelectHtmlBody($data, $selected = '')
	{
		$result = '';
		foreach($data as $item)
			$result .= '<option value="'.htmlspecialcharsbx($item['ID']).'"'.($selected == $item['ID'] ? " selected" : "").'>'.htmlspecialcharsbx(TruncateText($item['NAME'], 40)).'</option>';

		return $result;
	}

	private static function getImgPathList()
	{
		$dbRes = PaySystemServiceTable::getList(
			array(
				'select' => array(
					'ID',
					'PAYSYS_LOGOTIP' => 'ACTION.LOGOTIP'
				)
			)
		);
		$paySystems = $dbRes->fetchAll();
		$logotypes = array('/bitrix/images/sale/nopaysystem.gif');
		foreach ($paySystems as $paySystem)
		{
			if (empty($paySystem['PAYSYS_LOGOTIP']))
				$logotypes[$paySystem['ID']] = $logotypes[0];
			else
				$logotypes[$paySystem['ID']] = \CFile::GetPath($paySystem['PAYSYS_LOGOTIP']);
		}

		return $logotypes;
	}

	/**
	 * @param $formType
	 * @return string
	 */
	public static function createButtonAddPayment($formType)
	{
		return '
			<input type="button" value="'.Loc::getMessage('SALE_ORDER_PAYMENT_BUTTON_ADD').'" onclick="BX.Sale.Admin.GeneralPayment.addNewPayment(this, \''.$formType.'\')">
		';
	}

	/**
	 * @param Order $order
	 * @param $payments
	 * @param bool $canSetPaid
	 * @return Result
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ObjectNotFoundException
	 * @throws UserMessageException
	 */
	public static function updateData(Order &$order, $payments, $canSetPaid = false)
	{
		global $USER;
		$result = new Result();
		$data['PAYMENT'] = array();

		if (!$order)
			throw new UserMessageException('Order does not exist');

		foreach ($payments as $payment)
		{
			$paymentId = intval($payment['PAYMENT_ID']);
			$isNew = ($paymentId <= 0);
			$paymentCollection = $order->getPaymentCollection();

			/** @var \Bitrix\Sale\Payment $paymentItem */
			if ($isNew)
			{
				$paymentItem = $paymentCollection->createItem();
			}
			else
			{
				$paymentItem = $paymentCollection->getItemById($paymentId);

				if (!$paymentItem)
					throw new UserMessageException('Payment does not exist');
			}

			self::$defaultFields = $paymentItem->getFieldValues();

			$isReturn = (isset($payment['IS_RETURN']) && $payment['IS_RETURN'] == 'Y');

			/** @var \Bitrix\Sale\PaySystemService $paymentService */
			$paymentService = PaySystemService::load($payment['PAY_SYSTEM_ID']);
			if (!$paymentService)
			{
				$result->addError(
					new EntityError(
						Loc::getMessage('SALE_ORDER_PAYMENT_ERROR_PAYSYSTEM')
					)
				);
			}

			$paymentFields = array(
				'PAY_SYSTEM_ID' => $payment['PAY_SYSTEM_ID'],
				'COMPANY_ID' => (isset($payment['COMPANY_ID']) ? $payment['COMPANY_ID'] : 0),
				'SUM' => (float)str_replace(',', '.', $payment['SUM']),
				'PAY_VOUCHER_NUM' => $payment['PAY_VOUCHER_NUM'],
				'PAY_RETURN_NUM' => $payment['PAY_RETURN_NUM'],
				'PAY_RETURN_COMMENT' => $payment['PAY_RETURN_COMMENT'],
				'COMMENTS' => $payment['COMMENTS'],
				'PAY_SYSTEM_NAME' => ($paymentService) ? $paymentService->getName() : ''
			);

			if ($isNew)
				$paymentFields['DATE_BILL'] = new DateTime();

			if (!empty($payment['PAY_RETURN_DATE']))
			{
				try
				{
					$paymentFields['PAY_RETURN_DATE'] = new Date($payment['PAY_RETURN_DATE']);
				}
				catch (Main\ObjectException $exception)
				{
					$result->addError(
						new EntityError(Loc::getMessage('SALE_ORDER_PAYMENT_ERROR_RETURN_DATE_FORMAT'))
					);
				}
			}

			if (!empty($payment['PAY_VOUCHER_DATE']))
			{
				try
				{
					$paymentFields['PAY_VOUCHER_DATE'] = new Date($payment['PAY_VOUCHER_DATE']);
				}
				catch (Main\ObjectException $exception)
				{
					$result->addError(
						new EntityError(Loc::getMessage('SALE_ORDER_PAYMENT_ERROR_VOUCHER_DATE_FORMAT'))
					);
				}
			}

			if (isset($payment['RESPONSIBLE_ID']))
			{
				$paymentFields['RESPONSIBLE_ID'] = !empty($payment['RESPONSIBLE_ID']) ? $payment['RESPONSIBLE_ID'] : $USER->GetID();
				if ($payment['RESPONSIBLE_ID'] != $paymentItem->getField('RESPONSIBLE_ID'))
				{
					$paymentFields['DATE_RESPONSIBLE_ID'] = new DateTime();
					if (!$isNew)
						$paymentFields['EMP_RESPONSIBLE_ID'] = $USER->GetID();
				}
			}

			if ($result->isSuccess())
			{
				if ($paymentItem->getField('PAID') != $payment['PAID'] && $paymentItem->getField('IS_RETURN') == 'Y')
				{
					$setResult = $paymentItem->setReturn('N');
					if (!$setResult->isSuccess())
						$result->addErrors($setResult->getErrors());
				}

				if ($isReturn && $payment['OPERATION_ID'] == 'RETURN')
				{
					$setResult = $paymentItem->setReturn('Y');
					if (!$setResult->isSuccess())
						$result->addErrors($setResult->getErrors());
				}

				$setResult = $paymentItem->setFields($paymentFields);
				if (!$setResult->isSuccess())
					$result->addErrors($setResult->getErrors());

				if (!$canSetPaid)
				{
					$setResult = $paymentItem->setPaid($payment['PAID']);
					if (!$setResult->isSuccess())
						$result->addErrors($setResult->getErrors());
				}

				if ($payment['ORDER_STATUS_ID'])
					$order->setField('STATUS_ID', $payment['ORDER_STATUS_ID']);
			}

			$data['PAYMENT'][] = $paymentItem;
		}

		$result->setData($data);

		return $result;
	}
}