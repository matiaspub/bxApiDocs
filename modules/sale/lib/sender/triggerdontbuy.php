<?

namespace Bitrix\Sale\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TriggerDontBuy extends \Bitrix\Sender\TriggerConnectorClosed
{

	static public function getName()
	{
		return Loc::getMessage('sender_trigger_dont_buy_name');
	}

	static public function getCode()
	{
		return "dont_buy";
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

		$daysDontBuy = $this->getFieldValue('DAYS_DONT_BUY');
		if(!is_numeric($daysDontBuy))
			$daysDontBuy = 90;

		$dateFrom = new \Bitrix\Main\Type\DateTime;
		$dateTo = new \Bitrix\Main\Type\DateTime;

		$dateFrom->setTime(0, 0, 0)->add('-' . $daysDontBuy . ' days');
		$dateTo->setTime(0, 0, 0)->add('1 days')->add('-' . $daysDontBuy . ' days');

		if($this->isRunForOldData())
		{
			$filter = array(
				'<MAX_DATE_INSERT' => $dateTo->format(\Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT),
			);
		}
		else
		{
			$filter = array(
				'>MAX_DATE_INSERT' => $dateFrom->format(\Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT),
				'<MAX_DATE_INSERT' => $dateTo->format(\Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT),
			);
		}
		$filter = $filter + array(
			'=LID' => $this->getSiteId()
		);

		$userListDb = \Bitrix\Sale\Internals\OrderTable::getList(array(
			'select' => array('BUYER_USER_ID' => 'USER.ID', 'EMAIL' => 'USER.EMAIL', 'BUYER_USER_NAME' => 'USER.NAME'),
			'filter' => $filter,
			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('MAX_DATE_INSERT', 'MAX(%s)', 'DATE_INSERT'),
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
		$daysDontBuyInput = ' <input size=3 type="text" name="'.$this->getFieldName('DAYS_DONT_BUY').'" value="'.htmlspecialcharsbx($this->getFieldValue('DAYS_DONT_BUY', 90)).'"> ';

		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_trigger_dont_buy_days').'</td>
					<td>'.$daysDontBuyInput.'</td>
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
		if(isset($fields['BUYER_USER_NAME']))
		{
			$fields['NAME'] = $fields['BUYER_USER_NAME'];
			unset($fields['BUYER_USER_NAME']);
		}
		if(isset($fields['BUYER_USER_ID']))
		{
			$fields['USER_ID'] = $fields['BUYER_USER_ID'];
			unset($fields['BUYER_USER_ID']);
		}

		return $fields;
	}
}