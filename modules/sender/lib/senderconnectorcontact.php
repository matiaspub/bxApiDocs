<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderConnectorContact extends \Bitrix\Sender\Connector
{
	/**
	 * @return string
	 */
	static public function getName()
	{
		return Loc::getMessage('sender_connector_contact_name');
	}

	/**
	 * @return string
	 */
	static public function getCode()
	{
		return "contact_list";
	}
	/** @return \CDBResult */
	public function getData()
	{
		$listId = $this->getFieldValue('LIST_ID', null);

		$contactDb = ContactTable::getList(array(
			'select' => array('NAME', 'EMAIL'),
			'filter' => array(
				'CONTACT_LIST.LIST_ID' => $listId
			)
		));

		return new \CDBResult($contactDb);
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getForm()
	{
		$listInput = '<select name="'.$this->getFieldName('LIST_ID').'">';
		$listDb = ListTable::getList(array(
			'select' => array('ID','NAME',),
			'order' => array('NAME' => 'ASC', 'ID' => 'DESC')
		));
		while($list = $listDb->fetch())
		{
			$inputSelected = ($list['ID'] == $this->getFieldValue('LIST_ID') ? 'selected' : '');
			$listInput .= '<option value="'.$list['ID'].'" '.$inputSelected.'>';
			$listInput .= htmlspecialcharsbx($list['NAME']);
			$listInput .= '</option>';
		}
		$listInput .= '</select>';


		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_contact_list').'</td>
					<td>'.$listInput.'</td>
				</tr>
			</table>
		';
	}
}
