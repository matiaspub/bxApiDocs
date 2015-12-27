<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Subscribe;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class SenderEventHandler
{
	/**
	 * Handler of sender:OnConnectorList event.
	 *
	 * @param array $data Empty array.
	 * @return array
	 */
	public static function onConnectorListSubscriber($data)
	{
		$data['CONNECTOR'] = 'Bitrix\Subscribe\SenderConnectorSubscriber';
		return $data;
	}
}

class SenderConnectorSubscriber extends \Bitrix\Sender\Connector
{
	/**
	 * Returns localized name of subscribers source.
	 *
	 * @return string
	 */
	static public function getName()
	{
		return Loc::getMessage('sender_connector_subscriber_name');
	}

	/**
	 * Returns internal code of subscribers source.
	 *
	 * @return string
	 */
	static public function getCode()
	{
		return "subscriber";
	}

	/**
	 * Returns subscribers depended on side filter fields.
	 *
	 * @return \CDBResult
	 */
	public function getData()
	{
		$filter = array();

		$rubric = $this->getFieldValue('RUBRIC', null);
		if ($rubric)
			$filter['RUBRIC'] = $rubric;

		$active = $this->getFieldValue('ACTIVE', null);
		if ($active)
			$filter['ACTIVE'] = $active;

		$confirmed = $this->getFieldValue('CONFIRMED', null);
		if ($confirmed)
			$filter['CONFIRMED'] = $confirmed;

		$dateInsertFrom = $this->getFieldValue('DATE_INSERT_FROM', null);
		if ($dateInsertFrom)
			$filter['INSERT_1'] = $dateInsertFrom;

		$dateInsertTo = $this->getFieldValue('DATE_INSERT_TO', null);
		if ($dateInsertTo)
			$filter['INSERT_2'] = $dateInsertTo;

		$subscriberList = \CSubscription::GetList(array('ID' => 'ASC'), $filter);

		return $subscriberList;
	}

	/**
	 * Returns Html form to display filter criteria.
	 *
	 * @return string
	 */
	public function getForm()
	{
		$dropdownValues = array(
			'' => Loc::getMessage('sender_connector_subscriber_all'),
			'Y' => Loc::getMessage('sender_connector_subscriber_y'),
			'N' => Loc::getMessage('sender_connector_subscriber_n'),
		);

		$rubricInput = '<select name="'.$this->getFieldName('RUBRIC').'">';
		$rubricList = \CRubric::GetList(array("SORT" => "ASC", "NAME" => "ASC"), array());
		while ($rubric = $rubricList->Fetch())
		{
			$inputSelected = ($rubric['ID'] == $this->getFieldValue('RUBRIC')? 'selected': '');
			$rubricInput .= '<option value="'.$rubric['ID'].'" '.$inputSelected.'>';
			$rubricInput .= htmlspecialcharsEx($rubric['NAME']);
			$rubricInput .= '</option>';
		}
		$rubricInput .= '</select>';

		$activeInput = '<select name="'.$this->getFieldName('ACTIVE').'">';
		foreach ($dropdownValues as $k => $v)
		{
			$inputSelected = ($k == $this->getFieldValue('ACTIVE')? 'selected': '');
			$activeInput .= '<option value="'.$k.'" '.$inputSelected.'>';
			$activeInput .= htmlspecialcharsEx($v);
			$activeInput .= '</option>';
		}
		$activeInput .= '</select>';

		$confirmedInput = '<select name="'.$this->getFieldName('CONFIRMED').'">';
		foreach ($dropdownValues as $k => $v)
		{
			$inputSelected = ($k == $this->getFieldValue('CONFIRMED')? 'selected': '');
			$confirmedInput .= '<option value="'.$k.'" '.$inputSelected.'>';
			$confirmedInput .= htmlspecialcharsEx($v);
			$confirmedInput .= '</option>';
		}
		$confirmedInput .= '</select>';

		$dateInsertInput = CalendarPeriod(
			$this->getFieldName('DATE_INSERT_FROM'),
			$this->getFieldValue('DATE_INSERT_FROM'),
			$this->getFieldName('DATE_INSERT_TO'),
			$this->getFieldValue('DATE_INSERT_TO'),
			$this->getFieldFormName()
		);

		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_subscriber_rubric').'</td>
					<td>'.$rubricInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_subscriber_active').'</td>
					<td>'.$activeInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_subscriber_confirmed').'</td>
					<td>'.$confirmedInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_subscriber_dateinsert').'</td>
					<td>'.$dateInsertInput.'</td>
				</tr>
			</table>
		';
	}
}
