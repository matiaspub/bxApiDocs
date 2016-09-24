<?php
namespace Bitrix\Main\Replica;

class UrlMetadataHandler extends \Bitrix\Replica\Client\BaseHandler
{
	protected $tableName = "b_urlpreview_metadata";
	protected $moduleId = "main";
	protected $className = "\\Bitrix\\Main\\UrlPreview\\UrlMetadataTable";
	protected $primary = array(
		"ID" => "auto_increment",
	);
	protected $predicates = array();
	protected $translation = array(
		"IMAGE_ID" => "b_file.ID",
	);
	protected $fields = array(
		"DATE_INSERT" => "datetime",
		"DATE_EXPIRE" => "datetime",
		"TITLE" => "text",
		"DESCRIPTION" => "text",
		"SITE_NAME" => "text",
	);

	/**
	 * Called before log write. You may return false and not log write will take place.
	 *
	 * @param array $record Database record.
	 * @return boolean
	 */
	
	/**
	* <p>Нестатический метод вызывается перед записью в лог. Если вернётся <i>false</i> то записи в лог не произошло.</p>
	*
	*
	* @param array $record  Запись в БД.
	*
	* @return boolean 
	*
	* @static
	* @link http://dev.1c-bitrix.ru/api_d7/bitrix/main/replica/urlmetadatahandler/beforeloginsert.php
	* @author Bitrix
	*/
	static public function beforeLogInsert(array $record)
	{
		if ($record["TYPE"] === \Bitrix\Main\UrlPreview\UrlMetadataTable::TYPE_DYNAMIC)
		{
			return false;
		}
		else
		{
			return true;
		}
	}
}
