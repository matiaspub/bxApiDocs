<?php
namespace Bitrix\Im\Model;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class BotChatTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> BOT_ID int mandatory
 * <li> CHAT_ID int mandatory
 * </ul>
 *
 * @package Bitrix\Im
 **/

class BotChatTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_im_bot_chat';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('BOT_CHAT_ENTITY_ID_FIELD'),
			),
			'BOT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('BOT_CHAT_ENTITY_BOT_ID_FIELD'),
			),
			'CHAT_ID' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('BOT_CHAT_ENTITY_CHAT_ID_FIELD'),
			),
		);
	}
}

class_alias("Bitrix\\Im\\Model\\BotChatTable", "Bitrix\\Im\\BotChatTable", false);