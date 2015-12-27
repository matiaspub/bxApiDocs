<?

namespace Bitrix\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderTriggerUserDontAuth extends \Bitrix\Sender\TriggerConnectorClosed
{
	static public function getName()
	{
		return Loc::getMessage('sender_trigger_user_dontauth_name');
	}

	static public function getCode()
	{
		return "user_dontauth";
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
		$daysDontAuth = $this->getFieldValue('DAYS_DONT_AUTH');
		if(!is_numeric($daysDontAuth))
			$daysDontAuth = 90;

		$dateFrom = new \Bitrix\Main\Type\DateTime;
		$dateTo = new \Bitrix\Main\Type\DateTime;

		$dateFrom->setTime(0, 0, 0)->add('-' . $daysDontAuth . ' days');
		$dateTo->setTime(0, 0, 0)->add('1 days')->add('-' . $daysDontAuth . ' days');

		if($this->isRunForOldData())
		{
			$filter = array(
				'!LAST_LOGIN' => null,
				'<LAST_LOGIN' => $dateTo,
			);
		}
		else
		{
			$filter = array(
				'>LAST_LOGIN' => $dateFrom,
				'<LAST_LOGIN' => $dateTo,
			);
		}

		$userListDb = \Bitrix\Main\UserTable::getList(array(
			'select' => array('EMAIL', 'ID', 'NAME'),
			'filter' => $filter,
			'order' => array('ID' => 'ASC')
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

	public function getRecipient()
	{
		return $this->recipient;
	}

	public function getForm()
	{
		$daysDontAuth = ' <input size=3 type="text" name="'.$this->getFieldName('DAYS_DONT_AUTH').'" value="'.htmlspecialcharsbx($this->getFieldValue('DAYS_DONT_AUTH', 90)).'"> ';

		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_trigger_user_dontauth_days').'</td>
					<td>'.$daysDontAuth.'</td>
				</tr>
			</table>
		';
	}

	static public function getFetchDataModifier($fields)
	{
		if(isset($fields['ID']))
		{
			$fields['USER_ID'] = $fields['ID'];
			unset($fields['ID']);
		}

		return $fields;
	}
}