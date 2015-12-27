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

class ContactTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_contact';
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
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
				'required' => true,
			),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
				'default_value' => new Type\DateTime(),
			),
			'USER_ID' => array(
				'data_type' => 'integer',
			),
			'NAME' => array(
				'data_type' => 'string',
			),
			'EMAIL' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, "validateEmail")
			),
			'PHONE' => array(
				'data_type' => 'string',
			),
			'CONTACT_LIST' => array(
				'data_type' => 'Bitrix\Sender\ContactListTable',
				'reference' => array('=this.ID' => 'ref.CONTACT_ID'),
			),
			'MAILING_SUBSCRIPTION' => array(
				'data_type' => 'Bitrix\Sender\MailingSubscriptionTable',
				'reference' => array('=this.ID' => 'ref.CONTACT_ID'),
			),
		);
	}


	/**
	 * Returns validators for EMAIL_FROM field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return array(
			new Entity\Validator\Length(1, 255),
			array(__CLASS__, 'checkEmail'),
			new Entity\Validator\Unique
		);
	}

	/**
	 * @return mixed
	 */
	public static function checkEmail($value)
	{
		if(empty($value) || check_email($value))
			return true;
		else
			return Loc::getMessage('SENDER_ENTITY_CONTACT_VALID_EMAIL');
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		$primary = array('CONTACT_ID' => $data['primary']['ID']);
		ContactListTable::delete($primary);
		MailingSubscriptionTable::delete($primary);

		return $result;
	}


	/**
	 * @param $ar
	 * @return bool|int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function addIfNotExist($ar)
	{
		$id = false;
		$listId = false;

		if(array_key_exists('LIST_CODE', $ar) && array_key_exists('LIST_NAME', $ar))
		{
			$listId = ListTable::addIfNotExist($ar['LIST_CODE'], $ar['LIST_NAME']);
			unset($ar['LIST_CODE'], $ar['LIST_NAME']);
		}

		$contactDb = ContactTable::getList(array(
			'select' => array('ID'),
			'filter' => array('EMAIL' => $ar['EMAIL'])
		));
		if($arContact = $contactDb->fetch())
		{
			$id = $arContact['ID'];
		}
		else
		{
			$resultAdd = static::add($ar);
			if($resultAdd->isSuccess())
				$id = $resultAdd->getId();
		}

		if($listId && $id)
		{
			ContactListTable::addIfNotExist($id, $listId);
		}

		return $id;
	}

	/**
	 *
	 */
	public static function checkConnectors()
	{
		$connectorList = \Bitrix\Sender\ConnectorManager::getConnectorList();
		/** @var \Bitrix\Sender\Connector $connector */
		foreach($connectorList as $connector)
		{
			if($connector->requireConfigure()) continue;
			static::addFromConnector($connector);
		}
	}

	/**
	 * @param Connector $connector
	 * @param null $pageNumber
	 * @param int $timeout
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function addFromConnector(Connector $connector, $pageNumber = null, $timeout = 0)
	{
		$startTime = getmicrotime();
		$withoutNav = empty($pageNumber);
		$result = false;
		$onlyOneLoop = false;
		$rowsInPage = 5;

		$countAll = 0;
		$countProcessed = 0;
		$countUpdated = 0;
		$countAdded = 0;
		$countError = 0;

		$dataDb = $connector->getData();
		if(!is_subclass_of($dataDb, 'CDBResultMysql'))
		{
			$rowsInPage = 50;
			$onlyOneLoop = true;
		}

		while($timeout==0 || getmicrotime()-$startTime < $timeout)
		{
			if(!$withoutNav)
			{
				$dataDb->NavStart($rowsInPage, false, $pageNumber);
				$countAll = $dataDb->SelectedRowsCount();
			}

			$listId = null;
			while ($arData = $dataDb->Fetch())
			{
				if($withoutNav)
				{
					$countAll++;
				}

				$countProcessed++;

				if(!$listId)
				{
					$listId = ListTable::addIfNotExist(
						$connector->getModuleId() . '_' . $connector->getCode(),
						Loc::getMessage('CONTACT_PULL_LIST_PREFIX').$connector->getName()
					);
				}

				$id = null;
				$contactDb = ContactTable::getList(array(
					'select' => array('ID'),
					'filter' => array('EMAIL' => $arData['EMAIL'])
				));
				if($arContact = $contactDb->fetch())
				{
					$id = $arContact['ID'];
					$countUpdated++;
				}
				else
				{
					$resultAdd = static::add(array(
						'NAME' => $arData['NAME'],
						'EMAIL' => $arData['EMAIL'],
						'USER_ID' => $arData['USER_ID']
					));
					if ($resultAdd->isSuccess())
					{
						$id = $resultAdd->getId();
						$countAdded++;
					} else
					{
						$countError++;
					}
				}

				if($id)
					ContactListTable::addIfNotExist($id, $listId);

			}


			if($withoutNav)
			{
				$result = false;
				break;
			}

			if ($dataDb->NavPageCount <= $pageNumber)
			{
				$result = false;
				break;
			}
			else
			{
				$pageNumber++;
				$result = $pageNumber;
			}

			if($onlyOneLoop)
			{
				break;
			}
		}

		if($withoutNav)
		{
			$countProgress = $countAll;
		}
		else
		{
			$countProgress = ($pageNumber-1) * $dataDb->NavPageSize;
			if (!$result || $countProgress > $countAll) $countProgress = $countAll;
		}

		return array(
			'STATUS' => $result,
			'COUNT_ALL' => $countAll,
			'COUNT_PROGRESS' => $countProgress,
			'COUNT_PROCESSED' => $countProcessed,
			'COUNT_NEW' => $countAdded,
			'COUNT_ERROR' => $countError,
		);
	}
}

class ListTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_list';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'autocomplete' => true,
				'primary' => true,
			),
			'CODE' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateCode'),
				'title' => Loc::getMessage('SENDER_ENTITY_LIST_FIELD_TITLE_CODE'),
			),
			'NAME' => array(
				'data_type' => 'string',
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_LIST_FIELD_TITLE_NAME'),
			),
			'SORT' => array(
				'data_type' => 'integer',
				'default_value' => 100,
				'required' => true,
				'title' => Loc::getMessage('SENDER_ENTITY_LIST_FIELD_TITLE_SORT'),
			),
			'CONTACT_LIST' => array(
				'data_type' => 'Bitrix\Sender\ContactListTable',
				'reference' => array('=this.ID' => 'ref.LIST_ID'),
			),
		);
	}

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Entity\Validator\Length(null, 60),
		);
	}

	/**
	 * @param Entity\Event $event
	 * @return Entity\EventResult
	 */
	public static function onAfterDelete(Entity\Event $event)
	{
		$result = new Entity\EventResult;
		$data = $event->getParameters();

		$primary = array('LIST_ID' => $data['primary']['ID']);
		ContactListTable::delete($primary);

		return $result;
	}

	/**
	 * @param $code
	 * @param $name
	 * @return bool|int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function addIfNotExist($code, $name)
	{
		$id = false;
		if( !($arList = static::getList(array('filter' => array('CODE' => $code)))->fetch() ))
		{
			$resultAdd = static::add(array('CODE' => $code, 'NAME' => $name));
			if ($resultAdd->isSuccess())
			{
				$id = $resultAdd->getId();
			}
		}
		else
		{
			$id = $arList['ID'];
		}

		return $id;
	}
}

class ContactListTable extends Entity\DataManager
{
	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_sender_contact_list';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'CONTACT_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'LIST_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
			),
			'LIST' => array(
				'data_type' => 'Bitrix\Sender\ListTable',
				'reference' => array('=this.LIST_ID' => 'ref.ID'),
			),
			'CONTACT' => array(
				'data_type' => 'Bitrix\Sender\ContactTable',
				'reference' => array('=this.CONTACT_ID' => 'ref.ID'),
			),
		);
	}

	/**
	 * @param $contactId
	 * @param $listId
	 * @return bool
	 */
	public static function addIfNotExist($contactId, $listId)
	{
		$result = false;
		$arPrimary = array('CONTACT_ID' => $contactId, 'LIST_ID' => $listId);
		if( !($arList = static::getRowById($arPrimary) ))
		{
			$resultAdd = static::add($arPrimary);
			if ($resultAdd->isSuccess())
			{
				$result = true;
			}
		}
		else
		{
			$result = true;
		}

		return $result;
	}
}