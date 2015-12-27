<?

namespace Bitrix\Sale\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class TriggerOrderNew extends \Bitrix\Sender\TriggerConnector
{

	static public function getName()
	{
		return Loc::getMessage('sender_trigger_order_new_name');
	}

	static public function getCode()
	{
		return "order_new";
	}

	static public function getEventModuleId()
	{
		return 'sale';
	}

	static public function getEventType()
	{
		return "OnOrderAdd";
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
				'NAME' => Loc::getMessage('sender_trigger_order_new_pers_order_id_name'),
				'DESC' => Loc::getMessage('sender_trigger_order_new_pers_order_id_desc')
			)
		);
	}

	static public function getForm()
	{
		return '';
	}

}