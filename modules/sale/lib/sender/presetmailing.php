<?

namespace Bitrix\Sale\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PresetMailing
{
	protected static function getMailTemplate(array $params = null)
	{
		if(!isset($params['TITLE']))
		{
			$params['TITLE'] = '%TITLE%';
		}

		if(!isset($params['TEXT']))
		{
			$params['TEXT'] = '%TEXT%';
		}

		return '
<style>
		body
		{
			font-family: \'Helvetica Neue\', Helvetica, Arial, sans-serif;
			font-size: 14px;
			color: #000;
		}
</style>
<table cellpadding="0" cellspacing="0" width="850" style="background-color: #d1d1d1; border-radius: 2px; border:1px solid #d1d1d1; margin: 0 auto;" border="1" bordercolor="#d1d1d1">
<tbody>
<tr>
	<td height="83" width="850" bgcolor="#eaf3f5" style="border: none; padding-top: 23px; padding-right: 17px; padding-bottom: 24px; padding-left: 17px;">
		<table cellpadding="0" cellspacing="0" width="100%">
		<tbody>
		<tr>
			<td bgcolor="#ffffff" height="75" style="font-weight: bold; text-align: center; font-size: 26px; color: #0b3961;">
				#SITE_NAME#: ' . $params['TITLE'] . '
			</td>
		</tr>
		<tr>
			<td bgcolor="#bad3df" height="11">
			</td>
		</tr>
		</tbody>
		</table>
	</td>
</tr>
<tr>
	<td width="850" bgcolor="#f7f7f7" valign="top" style="border: none; padding-top: 0; padding-right: 44px; padding-bottom: 16px; padding-left: 44px;">
		<br>
		<p style="margin-top: 0px; margin-bottom: 20px;">
			' . $params['TEXT'] . '
		</p>
	</td>
</tr>
<tr>
	<td height="40px" width="850" bgcolor="#f7f7f7" valign="top" style="border: none; padding-top: 0; padding-right: 44px; padding-bottom: 30px; padding-left: 44px;">
		<p style="border-top: 1px solid #d1d1d1; margin-bottom: 5px; margin-top: 0; padding-top: 20px; line-height:21px;">
			' . Loc::getMessage('PRESET_MAIL_TEMPLATE_REGARDS', array('%LINK_START%' => '<a href="http://#SERVER_NAME#" style="color:#2e6eb6;">', '%LINK_END%' => '</a>')) . '
			<br><br>
			' . Loc::getMessage('PRESET_MAIL_TEMPLATE_UNSUB') . '
		</p>
	</td>
</tr>
</tbody>
</table>';
	}

	protected static function getCoupon($perc = 5)
	{
		if(!is_numeric($perc))
			$perc = 5;

		return '<?EventMessageThemeCompiler::includeComponent(
			"bitrix:sale.discount.coupon.mail",
			"",
			Array(
				"COMPONENT_TEMPLATE" => ".default",
				"DISCOUNT_XML_ID" => "{#SENDER_CHAIN_CODE#}",
				"DISCOUNT_VALUE" => "' . $perc . '",
				"DISCOUNT_UNIT" => "Perc",
				"COUPON_TYPE" => "Order",
				"COUPON_DESCRIPTION" => "{#EMAIL_TO#}"
			)
		);?>';
	}

	protected static function getBasketCart()
	{
		return '<?EventMessageThemeCompiler::includeComponent(
			"bitrix:sale.basket.basket.small.mail",
			"",
			Array(
				"USER_ID" => "{#USER_ID#}",
				"PATH_TO_BASKET" => "/",
				"PATH_TO_ORDER" => "/",
			)
		);?>';
	}

	protected static function getMessagePlaceHolders()
	{
		return array(
			'%BASKET_CART%' => self::getBasketCart(),
			'%COUPON%' => self::getCoupon(5),
			'%COUPON_3%' => self::getCoupon(3),
			'%COUPON_5%' => self::getCoupon(5),
			'%COUPON_7%' => self::getCoupon(7),
			'%COUPON_10%' => self::getCoupon(10),
			'%COUPON_11%' => self::getCoupon(11),
			'%COUPON_15%' => self::getCoupon(15),
			'%COUPON_20%' => self::getCoupon(20),
		);
	}

	public static function getForgottenCart($days)
	{
		return array(
			'TYPE' => Loc::getMessage('PRESET_TYPE_BASKET'),
			'CODE' => 'sale_basket',
			'NAME' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_NAME'),
			'DESC_USER' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_DESC_USER'),
			'DESC' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_DESC'),
			'TRIGGER' => array(
				'START' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'basket_forgotten',
						'FIELDS' => array('DAYS_BASKET_FORGOTTEN' => $days)
					)
				),
				'END' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'order_paid',
						'FIELDS' => array()
					)
				),
			),
			'CHAIN' => array(
				array(
					'TIME_SHIFT' => 0,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_1_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_1_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_1_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_2_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_2_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_2_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_3_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_3_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_FORGOTTEN_BASKET_LETTER_3_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
			)
		);
	}

	public static function getCanceledOrder()
	{
		return array(
			'TYPE' => Loc::getMessage('PRESET_TYPE_ORDER'),
			'CODE' => 'sale_order_cancel',
			'NAME' => Loc::getMessage('PRESET_CANCELED_ORDER_NAME'),
			'DESC_USER' => Loc::getMessage('PRESET_CANCELED_ORDER_DESC_USER'),
			'DESC' => Loc::getMessage('PRESET_CANCELED_ORDER_DESC'),
			'TRIGGER' => array(
				'START' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'order_cancel',
						'FIELDS' => array()
					)
				),
				'END' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'order_paid',
						'FIELDS' => array()
					)
				),
			),
			'CHAIN' => array(
				array(
					'TIME_SHIFT' => 0,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_1_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_1_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_1_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_2_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_2_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_2_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_3_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_3_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_CANCELED_ORDER_LETTER_3_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
			)
		);
	}

	public static function getPaidOrder()
	{
		return array(
			'TYPE' => Loc::getMessage('PRESET_TYPE_ORDER'),
			'CODE' => 'sale_order_pay',
			'NAME' => Loc::getMessage('PRESET_PAID_ORDER_NAME'),
			'DESC_USER' => Loc::getMessage('PRESET_PAID_ORDER_DESC_USER'),
			'DESC' => Loc::getMessage('PRESET_PAID_ORDER_DESC'),
			'TRIGGER' => array(
				'START' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'order_paid',
						'FIELDS' => array()
					)
				),
				'END' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'order_paid',
						'FIELDS' => array()
					)
				),
			),
			'CHAIN' => array(
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_PAID_ORDER_LETTER_1_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_1_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_1_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_PAID_ORDER_LETTER_2_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_2_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_2_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_PAID_ORDER_LETTER_3_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_3_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_3_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_PAID_ORDER_LETTER_4_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_4_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_4_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_PAID_ORDER_LETTER_5_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_5_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_PAID_ORDER_LETTER_5_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
			)
		);
	}

	public static function getDontBuy($days)
	{
		return array(
			'TYPE' => Loc::getMessage('PRESET_TYPE_ORDER'),
			'CODE' => 'sale_order_not_create'.$days,
			'NAME' => Loc::getMessage('PRESET_DONT_BUY_NAME', array('%DAYS%' => $days)),
			'DESC_USER' => Loc::getMessage('PRESET_DONT_BUY_DESC_USER', array('%DAYS%' => $days)),
			'DESC' => Loc::getMessage('PRESET_DONT_BUY_DESC_' . $days),
			'TRIGGER' => array(
				'START' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'dont_buy',
						'FIELDS' => array('DAYS_DONT_BUY' => $days),
						'RUN_FOR_OLD_DATA' => ($days > 300 ? 'Y' : 'N')
					)
				),
				'END' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sale',
						'CODE' => 'order_paid',
						'FIELDS' => array()
					)
				),
			),
			'CHAIN' => array(
				array(
					'TIME_SHIFT' => 0,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_BUY_LETTER_1_SUBJECT_' . $days),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_BUY_LETTER_1_SUBJECT_' . $days),
						'TEXT' => Loc::getMessage('PRESET_DONT_BUY_LETTER_1_MESSAGE_' . $days, static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_BUY_LETTER_2_SUBJECT_' . $days),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_BUY_LETTER_2_SUBJECT_' . $days),
						'TEXT' => Loc::getMessage('PRESET_DONT_BUY_LETTER_2_MESSAGE_' . $days, static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_BUY_LETTER_3_SUBJECT_' . $days),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_BUY_LETTER_3_SUBJECT_' . $days),
						'TEXT' => Loc::getMessage('PRESET_DONT_BUY_LETTER_3_MESSAGE_' . $days, static::getMessagePlaceHolders()),
					)),
				),
			)
		);

	}

	public static function getDontAuth($days)
	{
		return array(
			'TYPE' => Loc::getMessage('PRESET_TYPE_ORDER'),
			'CODE' => 'sale_user_dontauth',
			'NAME' => Loc::getMessage('PRESET_DONT_AUTH_NAME'),
			'DESC_USER' => Loc::getMessage('PRESET_DONT_AUTH_DESC_USER'),
			'DESC' => Loc::getMessage('PRESET_DONT_AUTH_DESC', array('%DAYS%' => $days)),
			'TRIGGER' => array(
				'START' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sender',
						'CODE' => 'user_dontauth',
						'FIELDS' => array('DAYS_DONT_AUTH' => $days)
					)
				),
				'END' => array(
					'ENDPOINT' => array(
						'MODULE_ID' => 'sender',
						'CODE' => 'user_auth',
						'FIELDS' => array()
					)
				),
			),
			'CHAIN' => array(
				array(
					'TIME_SHIFT' => 0,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_AUTH_LETTER_1_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_1_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_1_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_AUTH_LETTER_2_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_2_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_2_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_AUTH_LETTER_3_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_3_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_3_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
				array(
					'TIME_SHIFT' => 1440,
					'SUBJECT' => '#SITE_NAME#: ' . Loc::getMessage('PRESET_DONT_AUTH_LETTER_4_SUBJECT'),
					'MESSAGE' => self::getMailTemplate(array(
						'TITLE' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_4_SUBJECT'),
						'TEXT' => Loc::getMessage('PRESET_DONT_AUTH_LETTER_4_MESSAGE', static::getMessagePlaceHolders()),
					)),
				),
			)
		);

	}
}