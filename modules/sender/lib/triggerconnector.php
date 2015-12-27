<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;


abstract class TriggerConnector extends Trigger
{
	/** @var \Bitrix\Sender\Connector $connector */
	var $connector;

	/** @return string */
	final protected function getConnectorForm()
	{
		$endpoint = $this->getConnector();
		if(is_array($endpoint) && !empty($endpoint['CODE']))
		{
			$this->connector = ConnectorManager::getConnector($endpoint);
		}
		elseif(is_object($endpoint) && $endpoint instanceof Connector)
		{
			$this->connector = $endpoint;
		}

		if($this->connector)
		{
			$this->connector->setFieldPrefixExtended($this->getFieldPrefix());
			$this->connector->setFieldFormName($this->getFieldFormName());
			$this->connector->setFieldValues($this->getFields());

			return $this->connector->getForm();
		}
		else
			return '';
	}

	/** @return bool */
	final protected function filterConnectorData()
	{
		$result = true;

		$endpoint = $this->getConnector();
		if(is_array($endpoint) && !empty($endpoint['CODE']))
		{
			// do not send mails if connector will not found
			$result = false;

			// try to find connector
			$connector = ConnectorManager::getConnector($endpoint);
			$this->connector = $connector;
		}
		elseif(is_object($endpoint) && $endpoint instanceof Connector)
		{
			$this->connector = $endpoint;
		}


		if($this->connector)
		{
			// merge connector filter and proxy fields. proxy fields have priority
			$connectorFields = $this->getProxyFieldsFromEventToConnector();
			if(!empty($connectorFields))
			{
				$connectorFields = $connectorFields + $this->getFields();
			}
			// set fields to connector
			$this->connector->setFieldValues($connectorFields);


			$result = false;
			// add recipient from data if connector get it
			$recipientDb = $this->connector->getData();
			if($recipient = $recipientDb->Fetch())
			{
				$this->recipient = $recipient;
				$result = true;
			}
		}

		return $result;
	}


	/** @return string */
	public function getForm()
	{
		$connectorForm = $this->getConnectorForm();
		if($connectorForm)
			return $connectorForm;
		else
			return '';
	}

	/** @return bool */
	public function filter()
	{
		return $this->filterConnectorData();
	}

	public function getRecipient()
	{
		return $this->recipient;
	}

	/** @return array */
	public function getProxyFieldsFromEventToConnector()
	{
		$eventData = $this->getParam('EVENT');
		return array('ID' => $eventData['ID']);
	}

	/**
	 * return filled array('MODULE_ID' => '', 'CODE' => '') that describes connector
	 * or return object \Bitrix\Sender\Connector
	 *
	 * @return array|\Bitrix\Sender\Connector
	 */
	static public function getConnector()
	{
		return array(
			'MODULE_ID' => '',
			'CODE' => ''
		);
	}
}