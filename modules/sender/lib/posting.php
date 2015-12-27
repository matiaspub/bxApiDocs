<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */
namespace Bitrix\Sender;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;

Loc::loadMessages(__FILE__);

class PostingTable extends Entity\DataManager
{
	const STATUS_NEW = 'N';
	const STATUS_PART = 'P';
	const STATUS_SENT = 'S';
	const STATUS_SENT_WITH_ERRORS = 'E';

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_posting';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'MAILING_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true,
			),
			'MAILING_CHAIN_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'required' => true,
			),
			'DATE_CREATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new Type\DateTime(),
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
				'required' => true,
				'default_value' => new Type\DateTime(),
			),
			'STATUS' => array(
				'data_type' => 'string',
				'required' => true,
				'default_value' => static::STATUS_NEW,
			),
			'DATE_SENT' => array(
				'data_type' => 'datetime',
			),
			'MAILING' => array(
				'data_type' => 'Bitrix\Sender\MailingTable',
				'reference' => array('=this.MAILING_ID' => 'ref.ID'),
			),
			'MAILING_CHAIN' => array(
				'data_type' => 'Bitrix\Sender\MailingChainTable',
				'reference' => array('=this.MAILING_CHAIN_ID' => 'ref.ID'),
			),
			'POSTING_RECIPIENT' => array(
				'data_type' => 'Bitrix\Sender\PostingRecipientTable',
				'reference' => array('=this.ID' => 'ref.POSTING_ID'),
			),
			'POSTING_READ' => array(
				'data_type' => 'Bitrix\Sender\PostingReadTable',
				'reference' => array('=this.ID' => 'ref.POSTING_ID'),
			),
			'POSTING_CLICK' => array(
				'data_type' => 'Bitrix\Sender\PostingClickTable',
				'reference' => array('=this.ID' => 'ref.POSTING_ID'),
			),
			'POSTING_UNSUB' => array(
				'data_type' => 'Bitrix\Sender\PostingUnsubTable',
				'reference' => array('=this.ID' => 'ref.POSTING_ID'),
			),
		);
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function onDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();


		$listId = array();
		if(array_key_exists('ID', $data['primary']))
		{
			$listId[] = $data['primary']['ID'];
		}
		else
		{
			$filter = array();
			foreach($data['primary'] as $primKey => $primVal)
				$filter[$primKey] = $primVal;

			$tableDataList = static::getList(array(
				'select' => array('ID'),
				'filter' => $filter
			));
			while($tableData = $tableDataList->fetch())
			{
				$listId[] = $tableData['ID'];
			}

		}

		foreach($listId as $primaryId)
		{
			$primary = array('POSTING_ID' => $primaryId);
			PostingReadTable::delete($primary);
			PostingClickTable::delete($primary);
			PostingUnsubTable::delete($primary);
			PostingRecipientTable::delete($primary);
		}


		return $result;
	}

	/**
	 * @param $ar
	 * @param bool $checkDuplicate
	 */
	public static function addRecipient($ar, $checkDuplicate = false)
	{
		$ar['EMAIL'] = trim(strtolower($ar['EMAIL']));

		if(!$checkDuplicate)
		{
			$needAdd = true;
		}
		else
		{

			if(!PostingRecipientTable::getRowById(array('EMAIL' => $ar['EMAIL'], 'POSTING_ID' => $ar['POSTING_ID'])))
				$needAdd = true;
			else
				$needAdd = false;
		}

		if($needAdd)
			PostingRecipientTable::add($ar);
	}


	/**
	 * @param $postingId
	 * @param bool $checkDuplicate
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function initGroupRecipients($postingId, $checkDuplicate = true)
	{
		$posting = \Bitrix\Sender\PostingTable::getRowById(array('ID' => $postingId));
		if(!$posting)
			return false;

		$checkRecipientDuplicate = $checkDuplicate;
		if(!$checkDuplicate)
		{
			if($posting['STATUS'] == \Bitrix\Sender\PostingTable::STATUS_NEW)
			{
				$primary = array('POSTING_ID' => $postingId);
				\Bitrix\Sender\PostingRecipientTable::delete($primary);
				$checkRecipientDuplicate = false;
			}
		}

		// fetch all unsubscribed emails of current mailing for excluding from recipients
		$emailNotSendList = array();
		$recipientUnsubDb = \Bitrix\Sender\PostingUnsubTable::getList(array(
			'select' => array('EMAIL' => 'POSTING_RECIPIENT.EMAIL'),
			'filter' => array('POSTING.MAILING_ID' => $posting['MAILING_ID'])
		));
		while($recipientUnsub = $recipientUnsubDb->fetch())
			$emailNotSendList[] = $recipientUnsub['EMAIL'];

		$groupConnectorsDataCount = array();

		$connection = \Bitrix\Main\Application::getConnection();
		$conHelper = $connection->getSqlHelper();
		$statusRecipientNone = \Bitrix\Sender\PostingRecipientTable::SEND_RESULT_NONE;

		// fetch all connectors for getting emails
		$groupConnectorList = array();
		$groupConnectorDb = \Bitrix\Sender\MailingGroupTable::getList(array(
			'select' => array(
				'INCLUDE',
				'CONNECTOR_ENDPOINT' => 'GROUP.GROUP_CONNECTOR.ENDPOINT',
				'GROUP_ID'
			),
			'filter' => array(
				'MAILING_ID' => $posting['MAILING_ID'],
			),
			'order' => array('INCLUDE' => 'DESC', 'GROUP_ID' => 'ASC')
		));
		while($group = $groupConnectorDb->fetch())
		{
			$groupConnectorList[] = $group;
		}

		$groupConnectorList[] = array(
			'INCLUDE' => true,
			'CONNECTOR_ENDPOINT' => array(
				'FIELDS' => array('MAILING_ID' => $posting['MAILING_ID'])
			),
			'GROUP_ID' => null,
			'CONNECTOR' => new \Bitrix\Sender\SenderConnectorSubscriber
		);

		foreach($groupConnectorList as $group)
		{
			$connector = null;
			if(isset($group['CONNECTOR']) && is_object($group['CONNECTOR']))
				$connector = $group['CONNECTOR'];
			elseif(is_array($group['CONNECTOR_ENDPOINT']))
				$connector = \Bitrix\Sender\ConnectorManager::getConnector($group['CONNECTOR_ENDPOINT']);

			if(!$connector)
				continue;

			$connectorDataCount = 0;
			$connector->setFieldValues($group['CONNECTOR_ENDPOINT']['FIELDS']);
			$connectorDataDb = $connector->getData();
			while(true)
			{
				$emailList = array();
				$connectorDataList = array();

				$maxPart = 200;
				while ($connectorData = $connectorDataDb->Fetch())
				{
					// collect connectors counter of addresses
					$connectorDataCount++;

					// exclude unsubscribed addresses
					$connectorData['EMAIL'] = trim(strtolower($connectorData['EMAIL']));
					if (strlen($connectorData['EMAIL']) <= 0 || in_array($connectorData['EMAIL'], $emailNotSendList))
					{
						continue;
					}

					$emailList[] = $connectorData['EMAIL'];
					$connectorDataList[$connectorData['EMAIL']] = $connectorData;

					$maxPart--;
					if($maxPart == 0) break;
				}

				if (empty($emailList)) break;

				foreach($emailList as &$email) $email = $conHelper->forSql($email);
				$emailListString = "'" . implode("', '", $emailList) . "'";

				if ($group['INCLUDE'])
				{
					// add address if not exists
					if($checkRecipientDuplicate)
					{
						$recipientEmailDb = $connection->query("select EMAIL from b_sender_posting_recipient where EMAIL in (".$emailListString.") and POSTING_ID=".intval($postingId));
						while ($recipientEmail = $recipientEmailDb->fetch())
						{
							unset($connectorDataList[$recipientEmail['EMAIL']]);
						}
					}

					if(!empty($connectorDataList))
					{
						foreach($connectorDataList as $email => $connectorData)
						{
							$recipientInsert = array(
								'NAME' => "'" . $conHelper->forSql($connectorData['NAME']) . "'",
								'EMAIL' => "'" . $conHelper->forSql($connectorData['EMAIL']) . "'",
								'STATUS' => "'" . $statusRecipientNone . "'",
								'POSTING_ID' => intval($postingId)
							);

							if (array_key_exists('USER_ID', $connectorData) && intval($connectorData['USER_ID']) > 0)
							{
								$recipientInsert['USER_ID'] = intval($connectorData['USER_ID']);
							}

							$insertColumnNamesString = implode(", ", array_keys($recipientInsert));
							$insertColumnValuesString = implode(", ", array_values($recipientInsert));
							$connection->query("insert into b_sender_posting_recipient(" . $insertColumnNamesString . ") values(" . $insertColumnValuesString . ")");
						}
					}
				}
				else
				{
					// delete address from posting
					$connection->query("delete from b_sender_posting_recipient where EMAIL in (".$emailListString.") and POSTING_ID=".intval($postingId));
				}
			}

			//\Bitrix\Sender\GroupConnectorTable::update(array('ID' => $group['GROUP_CONNECTOR_ID']), array('ADDRESS_COUNT' => $connectorDataCount));
			// collect groups counter of addresses
			if(!empty($group['GROUP_ID']))
			{
				if (array_key_exists($group['GROUP_ID'], $groupConnectorsDataCount))
					$groupConnectorsDataCount[$group['GROUP_ID']] += $connectorDataCount;
				else
					$groupConnectorsDataCount[$group['GROUP_ID']] = $connectorDataCount;
			}

			unset($connector);
		}


		// update group counter of addresses
		foreach($groupConnectorsDataCount as $groupId => $groupDataCount)
		{
			\Bitrix\Sender\GroupTable::update($groupId, array('ADDRESS_COUNT' => $groupDataCount));
		}


		return true;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getRecipientCountByStatus($id)
	{
		$statusList = array();

		$select = array('CNT', 'STATUS');
		$filter = array('POSTING_ID' => $id);
		$postingContactDb = PostingRecipientTable::getList(array(
			'select' => $select,
			'filter' => $filter,
			'runtime' => array(new Entity\ExpressionField('CNT', 'COUNT(*)')),
		));
		while($postingContact = $postingContactDb->fetch())
			$statusList[$postingContact['STATUS']] = intval($postingContact['CNT']);

		return $statusList;
	}

	/**
	 * @param $id
	 * @param string $status
	 * @return int
	 */
	public static function getRecipientCount($id, $status = '')
	{
		$count = 0;

		$ar = static::getRecipientCountByStatus($id);
		if ($status != '')
			$count = (array_key_exists($status, $ar) ? $ar[$status] : 0);
		else
			foreach ($ar as $k => $v) $count += $v;

		return $count;
	}
}



class PostingReadTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_posting_read';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'POSTING_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'RECIPIENT_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
		);
	}
}


class PostingClickTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_posting_click';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'POSTING_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'RECIPIENT_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'URL' => array(
				'data_type' => 'string',
			),
			'POSTING' => array(
				'data_type' => 'Bitrix\Sender\PostingTable',
				'reference' => array('=this.POSTING_ID' => 'ref.ID'),
			),
		);
	}
}

class PostingUnsubTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_posting_unsub';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'POSTING_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'RECIPIENT_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'POSTING' => array(
				'data_type' => 'Bitrix\Sender\PostingTable',
				'reference' => array('=this.POSTING_ID' => 'ref.ID'),
			),
			'POSTING_RECIPIENT' => array(
				'data_type' => 'Bitrix\Sender\PostingRecipientTable',
				'reference' => array('=this.RECIPIENT_ID' => 'ref.ID'),
			),
		);
	}
}

class PostingRecipientTable extends Entity\DataManager
{
	const SEND_RESULT_NONE = 'Y';
	const SEND_RESULT_SUCCESS = 'N';
	const SEND_RESULT_ERROR = 'E';
	const SEND_RESULT_WAIT = 'W';
	const SEND_RESULT_DENY = 'D';

	protected static $personalizeList = null;
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_posting_recipient';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),
			'POSTING_ID' => array(
				'primary' => true,
				'data_type' => 'integer',
			),
			'STATUS' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true,
				'default_value' => static::SEND_RESULT_NONE,
			),
			'DATE_SENT' => array(
				'data_type' => 'datetime',
			),

			'DATE_DENY' => array(
				'data_type' => 'datetime',
			),
			'NAME' => array(
				'data_type' => 'string',
			),
			'EMAIL' => array(
				'data_type' => 'string',
				'primary' => true,
				'required' => true,
			),
			'PHONE' => array(
				'data_type' => 'string',
				//'required' => true,
			),
			'USER_ID' => array(
				'data_type' => 'integer',
			),
			'FIELDS' => array(
				'data_type' => 'text',
				'serialized' => true,
			),
			'ROOT_ID' => array(
				'data_type' => 'integer',
			),
			'POSTING' => array(
				'data_type' => 'Bitrix\Sender\PostingTable',
				'reference' => array('=this.POSTING_ID' => 'ref.ID'),
			),
			'POSTING_READ' => array(
				'data_type' => 'Bitrix\Sender\PostingReadTable',
				'reference' => array('=this.ID' => 'ref.RECIPIENT_ID'),
			),
			'POSTING_CLICK' => array(
				'data_type' => 'Bitrix\Sender\PostingClickTable',
				'reference' => array('=this.ID' => 'ref.RECIPIENT_ID'),
			),
			'POSTING_UNSUB' => array(
				'data_type' => 'Bitrix\Sender\PostingUnsubTable',
				'reference' => array('=this.ID' => 'ref.RECIPIENT_ID'),
			),
		);
	}

	public static function setPersonalizeList(array $personalizeList = null)
	{
		static::$personalizeList = $personalizeList;
	}

	/**
	 * @return array
	 */
	public static function getPersonalizeList()
	{
		return array_merge(
			array(
				array(
					'CODE' => 'NAME',
					'NAME' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_NAME"),
					'DESC' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_NAME_DESC"),
				),
				array(
					'CODE' => 'USER_ID',
					'NAME' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_USER_ID"),
					'DESC' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_USER_ID_DESC"),
				),
				array(
					'CODE' => 'SITE_NAME',
					'NAME' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_SITE_NAME"),
					'DESC' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_SITE_NAME_DESC"),
				),
				array(
					'CODE' => 'EMAIL_TO',
					'NAME' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_EMAIL"),
					'DESC' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_EMAIL_DESC"),
				),
				array(
					'CODE' => 'SENDER_CHAIN_CODE',
					'NAME' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_SENDER_CHAIN_ID"),
					'DESC' => Loc::getMessage("SENDER_POSTING_PERSONALIZE_FIELD_SENDER_CHAIN_ID_DESC"),
				),
			),
			(static::$personalizeList ? static::$personalizeList : array())
		);
	}

	/**
	 * @return array
	 */
	public static function getStatusList()
	{
		return array(
			self::SEND_RESULT_NONE => Loc::getMessage('SENDER_POSTING_RECIPIENT_STATUS_N'),
			self::SEND_RESULT_SUCCESS => Loc::getMessage('SENDER_POSTING_RECIPIENT_STATUS_S'),
			self::SEND_RESULT_ERROR => Loc::getMessage('SENDER_POSTING_RECIPIENT_STATUS_E')
		);
	}
}