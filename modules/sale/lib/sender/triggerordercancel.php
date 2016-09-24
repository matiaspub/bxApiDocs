<?

namespace Bitrix\Sale\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TriggerOrderCancel extends \Bitrix\Sender\TriggerConnector
{
	static public function getName()
	{
		return Loc::getMessage('sender_trigger_order_cancel_name');
	}

	static public function getCode()
	{
		return "order_cancel";
	}

	static public function getEventModuleId()
	{
		return 'sale';
	}

	static public function getEventType()
	{
		return "OnSaleCancelOrder";
	}

	/** @return bool */
	public static function canBeTarget()
	{
		return false;
	}

	public function filter()
	{
		$eventData = $this->getParam('EVENT');
		if($eventData[1] != 'Y')
			return false;
		else
			return $this->filterConnectorData();
	}

	static public function getConnector()
	{
		$connector = new \Bitrix\Sale\Sender\ConnectorOrder;
		$connector->setModuleId('sale');

		return $connector;
	}

	/** @return array */
	public function getProxyFieldsFromEventToConnector()
	{
		$eventData = $this->getParam('EVENT');
		return array('ID' => $eventData[0], 'LID' => $this->getSiteId());
	}

	/** @return array */
	public function getMailEventToPrevent()
	{
		$eventData = $this->getParam('EVENT');
		return array(
			'EVENT_NAME' => 'SALE_ORDER_CANCEL',
			'FILTER' => array('ORDER_ID' => $eventData[0])
		);
	}

	/**
	 * @return array
	 */
	public function getPersonalizeFields()
	{
		$eventData = $this->getParam('EVENT');
		return array(
			'ORDER_ID' => $eventData[0]
		);
	}

	/**
	 * @return array
	 */
	public static function getPersonalizeList()
	{
		return array(
			array(
				'CODE' => 'ORDER_ID',
				'NAME' => Loc::getMessage('sender_trigger_order_cancel_pers_order_id_name'),
				'DESC' => Loc::getMessage('sender_trigger_order_cancel_pers_order_id_desc')
			),
		);
	}
}