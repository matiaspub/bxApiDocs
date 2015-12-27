<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

abstract class NameHelper extends Helper
{
	#####################################
	#### Entity settings
	#####################################

	public static function getEntityRoadCode()
	{
		return 'name';
	}

	public static function getColumns($page)
	{
		return array_merge(parent::getColumns($page), self::getMap($page));
	}

	// get part of the whole field map for responsibility zone of the current entity
	// call this only with self::
	public static function getMap($page)
	{
		static $flds;

		if($flds == null)
		{
			$preFlds = static::readMap(self::getEntityRoadCode(), $page);

			// actually, NAME is not required when adding through LocationTable::add(), unless SHORT_NAME is set
			unset($preFlds['NAME']['required']);

			$flds = array();
			$languages = self::getLanguageList();

			foreach($languages as $lang)
			{
				foreach($preFlds as $code => $column)
				{
					$tmpCol = $column;

					$tmpCol['title'] = $tmpCol['title'].'&nbsp;('.$lang.')';
					$flds[$code.'_'.ToUpper($lang)] = $tmpCol;
				}
			}
		}

		return $flds;
	}

	#####################################
	#### CRUD wrappers
	#####################################

	// generalized filter to orm filter proxy
	public static function getParametersForList($proxed)
	{
		$parameters = parent::getParametersForList($proxed);

		$fldSubMap = static::readMap(self::getEntityRoadCode(), 'list');
		$roadMap = static::getEntityRoadMap();
		$road = $roadMap[self::getEntityRoadCode()]['name'];
		$class = $road.'Table';
		$languages = self::getLanguageList();

		// select all names
		foreach($languages as $lang)
		{
			$lang = ToUpper($lang);

			$parameters['runtime']['NAME__'.$lang] = array(
				'data_type' => $road,
				'reference' => array(
					'=this.ID' => 'ref.'.$class::getReferenceFieldName(),
					'=ref.'. $class::getLanguageFieldName() => array('?', ToLower($lang)) // oracle is case-sensitive
				),
				'join_type' => 'left'
			);

			if(!isset($parameters['select']))
				$parameters['select'] = array();
			foreach($fldSubMap as $code => $fld)
				$parameters['select'][$code.'_'.$lang] = 'NAME__'.$lang.'.'.$code;
		}

		// filter
		if(is_array($proxed['FILTER']) && !empty($proxed['FILTER']))
		{
			foreach($languages as $lang)
			{
				$lang = ToUpper($lang);

				foreach($fldSubMap as $code => $fld)
				{
					$key = $code.'_'.$lang;

					if(isset($proxed['FILTER'][$key]))
					{
						$parameters['filter'][static::getFilterModifier($fld['data_type']).'NAME__'.$lang.'.'.$code] = $proxed['FILTER'][$key];
					}
				}
			}
		}

		return $parameters;
	}

	##############################################
	##############################################
	##############################################

	public static function validateUpdateRequest(&$data)
	{
		$errors = parent::validateUpdateRequest($data);

		// formally check language ids in NAME parameter
		if(is_array($data['NAME']) && !empty($data['NAME']))
		{
			$languages = self::getLanguageList();

			foreach($data['NAME'] as $lid => $name)
			{
				if(!isset($languages[$lid]))
				{
					$errors[] = Loc::getMessage('SALE_LOCATION_ADMIN_NAME_HELPER_ENTITY_UNKNOWN_LANGUAGE_ID_ERROR');
					break;
				}
			}
		}

		return $errors;
	}

	public static function proxyUpdateRequest($data)
	{
		$names = static::extractNames($data);
		$data = parent::proxyUpdateRequest($data);

		if(!empty($names))
			$data['NAME'] = $names;

		return $data;
	}

	// an adapter from CAdminList to ORM getList() logic
	public static function proxyListRequest($page)
	{
		$parameters = parent::proxyListRequest($page);

		$fldSubMap = static::readMap(self::getEntityRoadCode(), 'list');
		$roadMap = static::getEntityRoadMap();
		$road = $roadMap[self::getEntityRoadCode()]['name'];
		$class = $road.'Table';
		$languages = self::getLanguageList();

		// select

		foreach($languages as $lang)
		{
			$lang = ToUpper($lang);

			$parameters['runtime']['NAME__'.$lang] = array(
				'data_type' => $road,
				'reference' => array(
					'=this.ID' => 'ref.'.$class::getReferenceFieldName(),
					'=ref.'. $class::getLanguageFieldName() => array('?', ToLower($lang)) // oracle is case-sensitive
				),
				'join_type' => 'left'
			);

			if(!isset($parameters['select']))
				$parameters['select'] = array();
			foreach($fldSubMap as $code => $fld)
				$parameters['select'][$code.'_'.$lang] = 'NAME__'.$lang.'.'.$code;
		}

		// filter
		if(self::checkUseFilter())
		{
			foreach($languages as $lang)
			{
				$lang = ToUpper($lang);

				foreach($fldSubMap as $code => $fld)
				{
					$key = 'find_'.$code.'_'.$lang;

					if(strlen($GLOBALS[$key]))
						$parameters['filter'][static::getFilterModifier($fld['data_type']).'NAME__'.$lang.'.'.$code] = $GLOBALS[$key];
				}
			}
		}

		return $parameters;
	}

	public static function getNameToDisplay($id)
	{
		if(!($id = intval($id)))
			return '';

		$class = static::getEntityClass('main');
		$nameClass = static::getEntityClass(self::getEntityRoadCode());
		$item = $class::getList(array(
			'filter' => array('=ID' => $id, 'NAME.'.$nameClass::getLanguageFieldName() => LANGUAGE_ID),
			'select' => array('LNAME' => 'NAME.NAME')
		))->fetch();

		return $item['LNAME'];
	}

	#####################################
	#### Entity-specific
	#####################################

	public static function getLanguageList()
	{
		static $languages;

		if($languages == null)
		{
			$by = 'sort';
			$order = 'asc';

			$lang = new \CLanguage();
			$res = $lang->GetList($by, $order, array());
			$languages = array();
			while($item = $res->Fetch())
				$languages[$item['LANGUAGE_ID']] = $item['LANGUAGE_ID'];
		}

		return $languages;
	}

	public static function getTranslatedName($names, $languageId)
	{
		if(!is_array($names) || empty($names) || (string) $languageId == '')
			return '';

		$languageIdMapped = static::mapLanguage($languageId);

		if(is_array($names[$languageId]) && (string) $names[$languageId]['NAME'] != '')
			return $names[$languageId];

		if(is_array($names[$languageIdMapped]) && (string) $names[$languageIdMapped]['NAME'] != '')
			return $names[$languageIdMapped];

		$languageId = 		ToUpper($languageId);
		$languageIdMapped = ToUpper($languageIdMapped);

		if(is_array($names[$languageId]) && (string) $names[$languageId]['NAME'] != '')
			return $names[$languageId];

		if(is_array($names[$languageIdMapped]) && (string) $names[$languageIdMapped]['NAME'] != '')
			return $names[$languageIdMapped];

		if((string) $names['EN'] != '')
			return $names['EN'];

		return '';
	}

	// extracts NAME data from known data, rather than do a separate query for it
	public static function extractNames(&$data)
	{
		$fldSubMap = static::readMap(self::getEntityRoadCode());
		$languages = self::getLanguageList();

		$names = array();
		foreach($languages as $lang)
		{
			foreach($fldSubMap as $code => $fld)
			{
				$langU = ToUpper($lang);

				$key = $code.'_'.$langU;
				if(isset($data[$key]))
					$names[$lang][$code] = $data[$key];

				unset($data[$key]);
			}
		}

		return $names;
	}

	public static function checkIsNameField($code)
	{
		$map = self::getMap('detail');
		return isset($map[$code]);
	}

	public static function getNameMap()
	{
		$map = static::readMap('name', 'detail');

		// actually, NAME is not required when adding through LocationTable::add(), unless SHORT_NAME is set
		unset($map['NAME']['required']);

		return $map;
	}

	#####################################
	#### Utilitary functions
	#####################################

	public static function translitFromUTF8($string)
	{
		$match = array( 
			"\xD0\x90" => "A", "\xD0\x91" => "B", "\xD0\x92" => "V", "\xD0\x93" => "G", "\xD0\x94" => "D", 
			"\xD0\x95" => "E", "\xD0\x01" => "YO", "\xD0\x96" => "ZH", "\xD0\x97" => "Z", "\xD0\x98" => "I", 
			"\xD0\x99" => "J", "\xD0\x9A" => "K", "\xD0\x9B" => "L", "\xD0\x9C" => "M", "\xD0\x9D" => "N", 
			"\xD0\x9E" => "O", "\xD0\x9F" => "P", "\xD0\xA0" => "R", "\xD0\xA1" => "S", "\xD0\xA2" => "T", 
			"\xD0\xA3" => "U", "\xD0\xA4" => "F", "\xD0\xA5" => "H", "\xD0\xA6" => "C", "\xD0\xA7" => "CH", 
			"\xD0\xA8" => "SH", "\xD0\xA9" => "SCH", "\xD0\xAC" => "", "\xD0\xAB" => "Y", "\xD0\xAA" => "", 
			"\xD0\xAD" => "E", "\xD0\xAE" => "YU", "\xD0\xAF" => "YA", 

			"\xD0\xB0" => "a", "\xD0\xB1" => "b", "\xD0\xB2" => "v", "\xD0\xB3" => "g", "\xD0\xB4" => "d", 
			"\xD0\xB5" => "e", "\xD1\x91" => "yo", "\xD0\xB6" => "zh", "\xD0\xB7" => "z", "\xD0\xB8" => "i", 
			"\xD0\xB9" => "j", "\xD0\xBA" => "k", "\xD0\xBB" => "l", "\xD0\xBC" => "m", "\xD0\xBD" => "n",
			"\xD0\xBE" => "o", "\xD0\xBF" => "p", "\xD1\x80" => "r", "\xD1\x81" => "s", "\xD1\x82" => "t", 
			"\xD1\x83" => "u", "\xD1\x84" => "f", "\xD1\x85" => "h", "\xD1\x86" => "c", "\xD1\x87" => "ch", 
			"\xD1\x88" => "sh", "\xD1\x89" => "sch", "\xD1\x8C" => "", "\xD1\x8B" => "y", "\xD1\x8A" => "", 
			"\xD1\x8d" => "e", "\xD1\x8E" => "yu", "\xD1\x8F" => "ya", 
		); 

		return str_replace(array_keys($match), array_values($match), $string);
	}

	public static function mapLanguage($lid)
	{
		if($lid == 'ua' || $lid == 'kz')
			return 'ru';

		return 'en';
	}
}
