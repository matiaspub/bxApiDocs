<?php

namespace Bitrix\Sale\Bigdata;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TargetSaleMailConnector extends \Bitrix\Sender\Connector
{
	static public function getName()
	{
		return Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_NAME');
	}

	static public function getCode()
	{
		return 'target_sale';
	}

	public function getData()
	{
		$productIds = array();

		if (is_array($this->getFieldValue('PRODUCTS')) && count($this->getFieldValue('PRODUCTS')))
		{
			$productIds = array_values($this->getFieldValue('PRODUCTS'));
		}

		$userProducts = array();

		foreach ($productIds as $productId)
		{
			$response = \Bitrix\Sale\Bigdata\Cloud::getPotentialConsumers($productId);

			if (!empty($response['users']))
			{
				foreach ($response['users'] as $userId)
				{
					$userProducts[(string) $userId][] = $productId;
				}
			}
		}

		$rows = array();

		if (!empty($userProducts))
		{
			$result = \Bitrix\Main\UserTable::getList(array(
				'select' => array('USER_ID' => 'ID', 'NAME', 'EMAIL'),
				'filter' => array(
					'=ID' => array_keys($userProducts)
				)
			));

			while ($row = $result->fetch())
			{
				$row['PRODUCTS'] = $userProducts[$row['USER_ID']];

				$rows[] = $row;
			}
		}

		return $rows;
	}

	public static function getPersonalizeList()
	{
		return array(
			array(
				'CODE' => 'PRODUCTS',
				'NAME' => Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_PRODUCTS_TITLE'),
				'DESC' => Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_PRODUCTS_DESC')
			)
		);
	}

	public function getForm()
	{
		// items selector
		$html = '<div id="send_bigdata_pcons_list_%CONNECTOR_NUM%" style="margin-bottom: 20px">';

		// dummy for events
		$html .= '<input type="hidden" id="send_bigdata_changeform_dummy_%CONNECTOR_NUM%" name="'.$this->getFieldName('RND').'" value="">';

		$html .= '</div>';

		$html .= "
		<script>

			function deleteProduct_%CONNECTOR_NUM%(id)
			{
				var li = BX('send_bigdata_pcons_list_%CONNECTOR_NUM%_e' + id);
				BX.remove(li);

				BX('send_bigdata_changeform_dummy_%CONNECTOR_NUM%').value = Math.random();
				BX.fireEvent(BX('send_bigdata_changeform_dummy_%CONNECTOR_NUM%'), 'change');
			}


			function catchProduct_%CONNECTOR_NUM%(e)
			{
				// skip duplicates
				if (BX('send_bigdata_pcons_list_%CONNECTOR_NUM%_e'+e.id))
				{
					return false;
				}

				// check if there is already limit
				var limit = 25;

				var currentCount = BX.findChildren(BX('send_bigdata_pcons_list_%CONNECTOR_NUM%'), {tag: 'li'}).length;

				if (currentCount >= limit)
				{
					// show notice
					var obPopupWin = BX.PopupWindowManager.create('sender_select_products_limit_%CONNECTOR_NUM%', null, {
						autoHide: false,
						offsetLeft: 0,
						offsetTop: 0,
						overlay : true,
						closeByEsc: true,
						titleBar: true,
						closeIcon: {top: '10px', right: '10px'}
					});

					obPopupWin.setTitleBar({
						content: BX.create(
							'span', {html: '<b>' + '".\CUtil::JSEscape(Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_SELECT_LIMIT_TITLE'))."' + '</b>', 'props': {'className': 'access-title-bar'}}
						)
					});

					var msg = '".\CUtil::JSEscape(Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_SELECT_LIMIT_MSG'))."';
					msg = msg.replace('#LIMIT#', limit);

					obPopupWin.setContent(msg);
					obPopupWin.setButtons([new BX.PopupWindowButton({
						text: '".\CUtil::JSEscape(Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_SELECT_LIMIT_CLOSE'))."',
						events: {click: function(){
							this.popupWindow.close();
						}}
				   })]);

					obPopupWin.show();

					return false;
				}

				// item
				var fieldName = \"{$this->getFieldName('PRODUCTS[]')}\".replace(\"\[\]\", \"[\"+e.id+\"]\");
				var itemElement = document.createElement('li');
				var title = e.name + ' (' + e.id + ') ';

				itemElement.id = 'send_bigdata_pcons_list_%CONNECTOR_NUM%_e'+e.id;
				itemElement.innerHTML = title + ' [ <a href=\"#\" onclick=\"deleteProduct_%CONNECTOR_NUM%('+e.id+'); return false;\">'+'".\CUtil::JSEscape(Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_SELECT_DEL'))."'+'</a> ]';
				itemElement.innerHTML += '<input type=\"hidden\" name=\"'+fieldName+'\" value=\"'+e.id+'\"> ';

				BX('send_bigdata_pcons_list_%CONNECTOR_NUM%').appendChild(itemElement);
			}

			function AddProductSearch_%CONNECTOR_NUM%()
			{
				var productPopup = new BX.CDialog({
					content_url: '/bitrix/admin/cat_product_search_dialog.php?lang=".LANG."&caller=sender_target_sale&func_name=catchProduct_%CONNECTOR_NUM%',
					height: Math.max(500, window.innerHeight-400),
					width: Math.max(800, window.innerWidth-400),
					draggable: true,
					resizable: true,
					min_height: 500,
					min_width: 800
				});

				BX.addCustomEvent(productPopup, 'onBeforeWindowClose', function(){
					BX.fireEvent(BX('send_bigdata_changeform_dummy_%CONNECTOR_NUM%'), 'change');
				});

				productPopup.Show();
			}
        </script>

        <button onclick='AddProductSearch_%CONNECTOR_NUM%(); return false;'>".htmlspecialcharsbx(Loc::getMessage('SALE_BIGDATA_TARGET_CONNECTOR_SELECT_TITLE'))."</button>
        ";

		if ($this->getFieldValue('PRODUCTS'))
		{
			// select titles
			$titles = array();

			$result = \Bitrix\Iblock\ElementTable::getList(array(
				'select' => array('ID', 'NAME'),
				'filter' => array('=ID' => $this->getFieldValue('PRODUCTS'))
			));

			while ($row = $result->fetch())
			{
				$titles[(int) $row['ID']] = $row['NAME'];
			}

			// preset
			$html .= "<script>".PHP_EOL;

			foreach ($this->getFieldValue('PRODUCTS') as $productId)
			{
				$html .= 'catchProduct_%CONNECTOR_NUM%('.\CUtil::PhpToJSObject(array(
					'id' => $productId,
					'name' => $titles[(int) $productId]
				)).');'.PHP_EOL;
			}


			$html .= "</script>";
		}

		return $html;
	}

	public static function onConnectorList()
	{
		$arData['CONNECTOR'] = __CLASS__;

		return $arData;
	}

}