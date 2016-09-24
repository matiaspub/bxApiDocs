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
	const STATUS_ABORT = 'A';

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
			'COUNT_READ' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_CLICK' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_UNSUB' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_SEND_ALL' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_SEND_NONE' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_SEND_ERROR' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_SEND_SUCCESS' => array(
				'data_type' => 'integer',
				'default_value' => 0
			),
			'COUNT_SEND_DENY' => array(
				'data_type' => 'integer',
				'default_value' => 0
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
		$unSubEmailDb = \Bitrix\Sender\MailingSubscriptionTable::getUnSubscriptionList(array(
			'select' => array('EMAIL' => 'CONTACT.EMAIL'),
			'filter' => array('=MAILING_ID' => $posting['MAILING_ID'])
		));
		while($unSubEmail = $unSubEmailDb->fetch())
			$emailNotSendList[] = $unSubEmail['EMAIL'];

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
			$connectorDataDb = $connector->getResult();
			while(true)
			{
				$emailList = array();
				$connectorDataList = array();

				$maxPart = 200;
				while ($connectorData = $connectorDataDb->fetch())
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
						$insertDataList = array();
						$insertColumnNamesString = array();
						foreach($connectorDataList as $email => $connectorData)
						{
							$recipientInsert = array(
								'NAME' => "'" . $conHelper->forSql($connectorData['NAME']) . "'",
								'EMAIL' => "'" . $conHelper->forSql($connectorData['EMAIL']) . "'",
								'STATUS' => "'" . $statusRecipientNone . "'",
								'POSTING_ID' => intval($postingId),
								'USER_ID' => "NULL",
								'FIELDS' => "NULL"
							);

							if (array_key_exists('USER_ID', $connectorData) && intval($connectorData['USER_ID']) > 0)
							{
								$recipientInsert['USER_ID'] = intval($connectorData['USER_ID']);
							}

							if (array_key_exists('FIELDS', $connectorData) && count($connectorData['FIELDS']) > 0)
							{
								$recipientInsert['FIELDS'] =  "'" . $conHelper->forSql(serialize($connectorData['FIELDS'])) . "'";
							}

							$insertColumnNamesString = implode(", ", array_keys($recipientInsert));
							$insertColumnValuesString = implode(", ", array_values($recipientInsert));
							$insertDataList[] = $insertColumnValuesString;
						}

						if($insertDataList && $insertColumnNamesString)
						{
							$insertDataListString =  implode('),(', $insertDataList);
							$connection->query("insert into b_sender_posting_recipient(" . $insertColumnNamesString . ") values(" . $insertDataListString . ")");
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

	/**
	 * Return send status of posting in percents by posting id.
	 *
	 * @param $id
	 * @return int
	 */
	public static function getSendPercent($id)
	{
		$ar = static::getRecipientCountByStatus($id);
		$count = 0;
		foreach ($ar as $k => $v)
		{
			$count += $v;
		}

		$countNew = 0;
		if(isset($ar[PostingRecipientTable::SEND_RESULT_NONE]))
		{
			$countNew = $ar[PostingRecipientTable::SEND_RESULT_NONE];
		}

		if($count > 0 && $countNew > 0)
		{
			return round(($count - $countNew) / $count, 2) * 100;
		}
		else
		{
			return 100;
		}
	}

	/**
	 * @return array
	 */
	public static function getRecipientStatusToPostingFieldMap()
	{
		return array(
			PostingRecipientTable::SEND_RESULT_NONE => 'COUNT_SEND_NONE',
			PostingRecipientTable::SEND_RESULT_ERROR => 'COUNT_SEND_ERROR',
			PostingRecipientTable::SEND_RESULT_SUCCESS => 'COUNT_SEND_SUCCESS',
			PostingRecipientTable::SEND_RESULT_DENY => 'COUNT_SEND_DENY',
		);
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

	/**
	 * Handler of after add event
	 *
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	
	/**
	* <p>Обработчик события <code>\Bitrix\Sender\PostingRead::add</code>.</p>
	*
	*
	* @param mixed $Bitrix  Объект <code>\Bitrix\<a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/index.php">Main</a>\<a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/index.php">Entity</a>\<a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/event/index.php">Event</a></code>
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Event $event  
	*
	* @return \Bitrix\Main\Entity\EventResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sender/postingreadtable/onafteradd.php
	* @author Bitrix
	*/
	public static function onAfterAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();
		$data = $data['fields'];

		// update read flag of recipient
		PostingRecipientTable::update(array('ID' => $data['RECIPIENT_ID']), array('IS_READ' => 'Y'));

		// update read counter of posting
		$resultDb = static::getList(array('filter' => array('RECIPIENT_ID' => $data['RECIPIENT_ID'])));
		if($resultDb->getSelectedRowsCount() == 1)
		{
			PostingTable::update(array('ID' => $data['POSTING_ID']), array(
				'COUNT_READ' => new \Bitrix\Main\DB\SqlExpression('?# + 1', 'COUNT_READ')
			));
		}

		return $result;
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

	/**
	 * Handler of after add event
	 *
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	
	/**
	* <p>Обработчик события <code>\Bitrix\Sender\PostingClick::add</code>.</p>
	*
	*
	* @param mixed $Bitrix  Объект <code>\Bitrix\<a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/index.php">Main</a>\<a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/index.php">Entity</a>\<a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/event/index.php">Event</a></code>
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Event $event  
	*
	* @return \Bitrix\Main\Entity\EventResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sender/postingclicktable/onafteradd.php
	* @author Bitrix
	*/
	public static function onAfterAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();
		$data = $data['fields'];

		// update click flag of recipient
		PostingRecipientTable::update(array('ID' => $data['RECIPIENT_ID']), array('IS_CLICK' => 'Y'));

		// update click counter of posting
		$resultDb = static::getList(array('filter' => array('RECIPIENT_ID' => $data['RECIPIENT_ID'])));
		if($resultDb->getSelectedRowsCount() == 1)
		{
			PostingTable::update(array('ID' => $data['POSTING_ID']), array(
				'COUNT_CLICK' => new \Bitrix\Main\DB\SqlExpression('?# + 1', 'COUNT_CLICK')
			));
		}

		return $result;
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

	/**
	 * Handler of after add event
	 *
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	
	/**
	* <p>Обработчик события <code>\Bitrix\Sender\PostingUnsub::add</code>.</p>
	*
	*
	* @param mixed $Bitrix  Объект <code>\Bitrix\<a href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/index.php">Main</a>\<a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/index.php">Entity</a>\<a
	* href="http://dev.1c-bitrix.ru/api_d7/bitrix/main/entity/event/index.php">Event</a></code>
	*
	* @param Bitri $Main  
	*
	* @param Mai $Entity  
	*
	* @param Event $event  
	*
	* @return \Bitrix\Main\Entity\EventResult 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/sender/postingunsubtable/onafteradd.php
	* @author Bitrix
	*/
	public static function onAfterAdd(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();
		$data = $data['fields'];

		// update unsub flag of recipient
		PostingRecipientTable::update(array('ID' => $data['RECIPIENT_ID']), array('IS_UNSUB' => 'Y'));

		// update unsub counter of posting
		$resultDb = static::getList(array('filter' => array('RECIPIENT_ID' => $data['RECIPIENT_ID'])));
		if($resultDb->getSelectedRowsCount() == 1)
		{
			PostingTable::update(array('ID' => $data['POSTING_ID']), array(
				'COUNT_UNSUB' => new \Bitrix\Main\DB\SqlExpression('?# + 1', 'COUNT_UNSUB')
			));
		}

		return $result;
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
			'IS_READ' => array(
				'data_type' => 'string',
			),
			'IS_CLICK' => array(
				'data_type' => 'string',
			),
			'IS_UNSUB' => array(
				'data_type' => 'string',
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