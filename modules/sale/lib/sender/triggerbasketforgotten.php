<?

namespace Bitrix\Sale\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TriggerBasketForgotten extends \Bitrix\Sender\TriggerConnectorClosed
{

	static public function getName()
	{
		return Loc::getMessage('sender_trigger_basket_forgotten_name');
	}

	static public function getCode()
	{
		return "basket_forgotten";
	}

	/** @return bool */
	public static function canBeTarget()
	{
		return false;
	}

	/** @return bool */
	public static function canRunForOldData()
	{
		return true;
	}

	public function filter()
	{
		\Bitrix\Main\Loader::includeModule('sale');

		$daysBasketForgotten = $this->getFieldValue('DAYS_BASKET_FORGOTTEN');
			if(!is_numeric($daysBasketForgotten))
				$daysBasketForgotten = 90;

		$dateFrom = new \Bitrix\Main\Type\DateTime;
		$dateTo = new \Bitrix\Main\Type\DateTime;

		$dateFrom->setTime(0, 0, 0)->add('-' . $daysBasketForgotten . ' days');
		$dateTo->setTime(0, 0, 0)->add('1 days')->add('-' . $daysBasketForgotten . ' days');

		if($this->isRunForOldData())
		{
			$filter = array(
				'<MIN_DATE_INSERT' => $dateTo->format(\Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT),
			);
		}
		else
		{
			$filter = array(
				'>MIN_DATE_INSERT' => $dateFrom->format(\Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT),
				'<MIN_DATE_INSERT' => $dateTo->format(\Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT),
			);
		}
		$filter = $filter + array(
			'!FUSER.USER_ID' => null,
			'=ORDER_ID' => null,
			'=LID' => $this->getSiteId(),
		);

		$userListDb = \Bitrix\Sale\Internals\BasketTable::getList(array(
			'select' => array('USER_ID' => 'FUSER.USER_ID', 'EMAIL' => 'FUSER.USER.EMAIL', 'FUSER_USER_NAME' => 'FUSER.USER.NAME'),
			'filter' => $filter,
			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('MIN_DATE_INSERT', 'MIN(%s)', 'DATE_INSERT'),
			),
			'order' => array('USER_ID' => 'ASC')
		));

		if($userListDb->getSelectedRowsCount() > 0)
		{
			$userListDb->addFetchDataModifier(array($this, 'getFetchDataModifier'));
			$this->recipient = $userListDb;
			return true;
		}
		else
			return false;
	}

	public function getForm()
	{
		$daysBasketForgottenInput = ' <input size=3 type="text" name="'.$this->getFieldName('DAYS_BASKET_FORGOTTEN').'" value="'.htmlspecialcharsbx($this->getFieldValue('DAYS_BASKET_FORGOTTEN', 1)).'"> ';

		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_trigger_basket_forgotten_days').'</td>
					<td>'.$daysBasketForgottenInput.'</td>
				</tr>
			</table>
		';
	}

	public function getRecipient()
	{
		return $this->recipient;
	}

	static public function getFetchDataModifier($fields)
	{
		if(isset($fields['FUSER_USER_NAME']))
		{
			$fields['NAME'] = $fields['FUSER_USER_NAME'];
			unset($fields['FUSER_USER_NAME']);
		}

		return $fields;
	}
}