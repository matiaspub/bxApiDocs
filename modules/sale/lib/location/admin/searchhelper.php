<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

//use Bitrix\Sale\Location;
use Bitrix\Sale\Location\Search\Finder;
use Bitrix\Sale\Location\Import\ImportProcess;
use Bitrix\Sale\Location\DB;

Loc::loadMessages(__FILE__);

final class SearchHelper
{
	const INDEX_RECHECK_COUNTER_OPT = 	'sale.location.index_recheck_counter';
	const INDEX_VALID_OPT = 			'sale.location.db_index_valid';
	const HITS_BETWEEN_RECHECKS = 		20;

	protected static function setCounter($value)
	{
		Option::set('sale', self::INDEX_RECHECK_COUNTER_OPT, $value, '');
	}

	protected static function getCounter()
	{
		return intval(Option::get('sale', self::INDEX_RECHECK_COUNTER_OPT, '', ''));
	}

	protected static function showSearchNotification()
	{
		\CAdminMessage::ShowMessage(array('MESSAGE' => Loc::getMessage(
			'SALE_LOCATION_ADMIN_SEARCH_HELPER_ENTITY_INVALID_SINDEX',
			array('#ANCHOR_INDEX_RESTORE#' => '<a href="'.Helper::getReindexUrl().'" target="_blank">', "#ANCHOR_END#" => '</a>')
		), 'type' => 'ERROR', 'HTML' => true));
	}

	public static function checkIndexValid()
	{
		return Option::get('sale', self::INDEX_VALID_OPT, '', '') != 'N';
	}

	public static function setIndexValid()
	{
		Option::set('sale', self::INDEX_VALID_OPT, 'Y', '');
	}

	public static function setIndexInvalid()
	{
		Option::set('sale', self::INDEX_VALID_OPT, 'N', '');
	}

	protected static function showDBIndexNotification()
	{
		\CAdminMessage::ShowMessage(array('MESSAGE' => Loc::getMessage(
			'SALE_LOCATION_ADMIN_SEARCH_HELPER_ENTITY_INVALID_DBINDEX',
			array('#ANCHOR_INDEX_RESTORE#' => '<a href="'.Helper::getImportUrl().'" target="_blank">', "#ANCHOR_END#" => '</a>')
		), 'type' => 'ERROR', 'HTML' => true));
	}

	public static function checkIndexesValid()
	{
		if(!Finder::checkIndexValid())
			static::showSearchNotification();

		$cnt = static::getCounter();

		if($cnt > static::HITS_BETWEEN_RECHECKS || !static::checkIndexValid())
		{
			$allOk = true;
			$map = ImportProcess::getIndexMap();
			if(is_array($map))
			{
				foreach($map as $ixName => $ixInfo)
				{
					if(!$ixInfo['DROP_ONLY'] && !DB\Helper::checkIndexNameExists($ixName, $ixInfo['TABLE']))
					{
						$allOk = false;
						break;
					}
				}
			}
			else
				$allOk = false;

			if($allOk)
				static::setIndexValid();
			else
				static::setIndexInvalid();

			static::setCounter(0);
		}
		else
			static::setCounter($cnt + 1);

		if(!static::checkIndexValid())
			static::showDBIndexNotification();
	}
}