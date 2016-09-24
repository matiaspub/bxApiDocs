<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Main\Mail\Internal;

use Bitrix\Main\Entity;

class EventMessageSiteTable extends Entity\DataManager
{

	/**
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_event_message_site';
	}

	/**
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'EVENT_MESSAGE_ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
			),

			'SITE_ID' => array(
				'data_type' => 'string',
				'required' => true,
			),
		);
	}

}
