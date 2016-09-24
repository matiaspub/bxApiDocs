<?php
/**
 * Bitrix Framework
 * @package Bitrix\Sale\Location
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Sale\Location\Search;

use Bitrix\Main;
use Bitrix\Main\DB;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

use Bitrix\Sale\Location;
use Bitrix\Sale\Location\Util\Assert;

Loc::loadMessages(__FILE__);

class Finder
{
	const SALE_LOCATION_INDEXED_TYPES_OPT = 		'sale.location.indexed_types';
	const SALE_LOCATION_INDEXED_LANGUAGES_OPT = 	'sale.location.indexed_langs';
	const SALE_LOCATION_INDEX_VALID_OPT = 			'sale.location.index_valid';

	protected static $allowedOperations = array(
		'=' => true
	);

	public static function checkIndexValid()
	{
		return Option::get('sale', self::SALE_LOCATION_INDEX_VALID_OPT, '', '') == 'Y';
	}

	public static function setIndexValid()
	{
		Option::set('sale', self::SALE_LOCATION_INDEX_VALID_OPT, 'Y', '');
	}

	public static function setIndexInvalid()
	{
		Option::set('sale', self::SALE_LOCATION_INDEX_VALID_OPT, 'N', '');
	}

	public static function getIndexedTypes()
	{
		$types = Option::get('sale', self::SALE_LOCATION_INDEXED_TYPES_OPT, '', '');
		$typesFromDb = static::getTypesFromDb();

		if(!strlen($types)) // means "all"
			return array_keys($typesFromDb);

		$types = explode(':', $types);
		$result = array();

		if(is_array($types))
		{
			foreach($types as $type)
			{
				$type = intval($type);
				if(isset($typesFromDb[$type]))
					$result[] = $type;
			}
		}

		return array_unique($result);
	}

	public static function setIndexedTypes($types = array())
	{
		$result = array();
		if(is_array($types) && !empty($types))
		{
			$typesFromDb = static::getTypesFromDb();

			foreach($types as $type)
			{
				$type = intval($type);
				if(isset($typesFromDb[$type]))
					$result[] = $type;
			}

			$result = array_unique($result);
		}

		Option::set('sale', self::SALE_LOCATION_INDEXED_TYPES_OPT, implode(':', $result), '');
	}

	public static function getIndexedLanguages()
	{
		$langs = Option::get('sale', self::SALE_LOCATION_INDEXED_LANGUAGES_OPT, '', '');
		$langsFromDb = static::getLangsFromDb();

		if(!strlen($langs))
			return array_keys($langsFromDb);

		$result = array();
		$langs = explode(':', $langs);

		if(is_array($langs))
		{
			foreach($langs as $lang)
			{
				if(isset($langsFromDb[$lang]))
					$result[] = $lang;
			}
		}

		return array_unique($result);
	}

	public static function setIndexedLanguages($langs = array())
	{
		if(is_array($langs) && !empty($langs))
			$langs = array_unique($langs);
		else
			$langs = array();

		$result = array();
		if(is_array($langs) && !empty($langs))
		{
			$langsFromDb = static::getLangsFromDb();

			foreach($langs as $lang)
			{
				if(isset($langsFromDb[$lang]))
					$result[] = $lang;
			}

			$result = array_unique($result);
		}

		Option::set('sale', self::SALE_LOCATION_INDEXED_LANGUAGES_OPT, implode(':', $result), '');
	}

	protected static function getLangsFromDb()
	{
		$langsFromDb = array();
		$res = \Bitrix\Main\Localization\LanguageTable::getList(array('select' => array('ID')));
		while($item = $res->fetch())
			$langsFromDb[$item['ID']] = true;

		return $langsFromDb;
	}

	protected static function getTypesFromDb()
	{
		$typesFromDb = array();
		$res = Location\TypeTable::getList(array('select' => array('ID')));
		while($item = $res->fetch())
			$typesFromDb[intval($item['ID'])] = true;

		return $typesFromDb;
	}

	/**
	 * 
	 * $parameters is an ORM`s getList compatible array of parameters
	 * 
	 * 
	 */
	public static function find($parameters, $behaviour = array('FALLBACK_TO_NOINDEX_ON_NOTFOUND' => true, 'USE_INDEX' => true, 'USE_ORM' => true))
	{
		/////////////////////////////////
		// parameter check and process

		Assert::expectArray($parameters, '$parameters');

		if(!is_array($behaviour))
			$behaviour = array();
		if(!isset($behaviour['FALLBACK_TO_NOINDEX_ON_NOTFOUND']))
			$behaviour['FALLBACK_TO_NOINDEX_ON_NOTFOUND'] = true;
		if(!isset($behaviour['USE_INDEX']))
			$behaviour['USE_INDEX'] = true;
		if(!isset($behaviour['USE_ORM']))
			$behaviour['USE_ORM'] = true;

		if(!isset($parameters['select']))
			$parameters['select'] = array('ID');

		Assert::expectArray($parameters['select'], '$parameters[select]');

		if(isset($parameters['filter']))
		{
			Assert::expectArray($parameters['filter'], '$parameters[filter]');

			// spikes, refactor later

			if(isset($parameters['filter']['PHRASE']) || isset($parameters['filter']['=PHRASE']))
			{
				$key = isset($parameters['filter']['PHRASE']) ? 'PHRASE' : '=PHRASE';

				$parameters['filter'][$key] = Assert::expectStringNotNull($parameters['filter'][$key], '$parameters[filter]['.$key.']');
				$parameters['filter'][$key] = str_replace('%', '', $parameters['filter'][$key]); // cannot pass '%' to like
			}

			if(isset($parameters['filter']['SITE_ID']) || isset($parameters['filter']['=SITE_ID']))
			{
				$key = isset($parameters['filter']['SITE_ID']) ? 'SITE_ID' : '=SITE_ID';
				$parameters['filter'][$key] = Assert::expectStringNotNull($parameters['filter'][$key], '$parameters[filter]['.$key.']'); // stronger here

				if(!Location\SiteLocationTable::checkLinkUsageAny($parameters['filter'][$key]))
					unset($parameters['filter'][$key]);
			}
		}

		if(isset($parameters['limit']))
			$parameters['limit'] = Assert::expectIntegerNonNegative($parameters['limit'], '$parameters[limit]');
		if(isset($parameters['offset']))
			$parameters['offset'] = Assert::expectIntegerNonNegative($parameters['offset'], '$parameters[offset]');

		/////////////////////////////////

		if(
			(isset($parameters['filter']['PHRASE']) || isset($parameters['filter']['SITE_ID']) || isset($parameters['filter']['=PHRASE']) || isset($parameters['filter']['=SITE_ID']))
			||
			$behaviour['USE_ORM'] === false
		)
		{
			if(static::checkIndexValid() && $behaviour['USE_INDEX'])
			{
				$result = static::findUsingIndex($parameters);
				if(!$behaviour['FALLBACK_TO_NOINDEX_ON_NOTFOUND'])
				{
					return $result;
				}
				else
				{
					$temporalBuffer = array();
					while($item = $result->fetch())
					{
						$temporalBuffer[] = $item;
					}

					if(empty($temporalBuffer))
					{
						return static::findNoIndex($parameters);
					}
					else
					{
						return new DB\ArrayResult($temporalBuffer);
					}
				}
			}
			else
			{
				return static::findNoIndex($parameters);
			}
		}
		else
		{
			return Location\LocationTable::getList($parameters);
		}
	}

	protected static function parseFilter($filter)
	{
		$parsed = array();

		if(is_array($filter))
		{
			foreach($filter as $field => $value)
			{
				$found = array();
				preg_match("#^(=?)(.+)#", $field, $found);

				if(strlen($found[1]))
					$op = $found[1];
				else
					$op = '=';

				if(!isset(static::$allowedOperations[$op]))
					throw new Main\ArgumentException('Unknown modifier in the filter');

				$fieldParsed = $found[2];

				$parsed[$fieldParsed] = array(
					'OP' => strlen($op) ? $op : '=',
					'VALUE' => $value
				);
			}
		}

		return $parsed;
	}

	protected static function findUsingIndex($parameters)
	{
		$query = array();

		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = Main\HttpApplication::getConnection()->getSqlHelper();

		$filter = static::parseFilter($parameters['filter']);

		$filterByPhrase = isset($filter['PHRASE']) && strlen($filter['PHRASE']['VALUE']);

		if($filterByPhrase) // filter by phrase
		{
			$bounds = WordTable::getBoundsForPhrase($filter['PHRASE']['VALUE']);

			$firstBound = array_shift($bounds);
			$k = 0;
			foreach($bounds as $bound)
			{
				$query['JOIN'][] = " inner join ".ChainTable::getTableName()." A".$k." on A.LOCATION_ID = A".$k.".LOCATION_ID and (

					".($bound['INF'] == $bound['SUP']
						? " A".$k.".POSITION = '".$bound['INF']."'"
						: " A".$k.".POSITION >= '".$bound['INF']."' and A".$k.".POSITION <= '".$bound['SUP']."'"
					)."
				)";
				$k++;
			}

			$query['WHERE'][] = (
				$firstBound['INF'] == $firstBound['SUP']
				? " A.POSITION = '".$firstBound['INF']."'"
				: " A.POSITION >= '".$firstBound['INF']."' and A.POSITION <= '".$firstBound['SUP']."'"
			);

			$mainTableJoinCondition = 'A.LOCATION_ID';
		}
		else
		{
			$mainTableJoinCondition = 'L.ID';
		}

		// site link search
		if(strlen($filter['SITE_ID']['VALUE']) && SiteLinkTable::checkTableExists())
		{
			$query['JOIN'][] = "inner join ".SiteLinkTable::getTableName()." SL on SL.LOCATION_ID = ".$mainTableJoinCondition." and SL.SITE_ID = '".$dbHelper->forSql($filter['SITE_ID']['VALUE'])."'";
		}

		// process filter and select statements
		// at least, we support here basic field selection and filtration + NAME.NAME and NAME.LANGUAGE_ID

		$map = Location\LocationTable::getMap();
		$nameRequired = false;
		$locationRequred = false;

		if(is_array($parameters['select']))
		{
			foreach($parameters['select'] as $alias => $field)
			{
				if($field == 'NAME.NAME' || $field == 'NAME.LANGUAGE_ID')
				{
					$nameRequired = true;
					continue;
				}

				if(
					!isset($map[$field]) || 
					!in_array($map[$field]['data_type'], array('integer', 'string', 'float', 'boolean')) ||
					isset($map[$field]['expression'])
				)
				{
					unset($parameters['select'][$alias]);
				}

				$locationRequred = true;
			}
		}

		foreach($filter as $field => $params)
		{
			if($field == 'NAME.NAME' || $field == 'NAME.LANGUAGE_ID')
			{
				$nameRequired = true;
				continue;
			}

			if(
				!isset($map[$field]) || 
				!in_array($map[$field]['data_type'], array('integer', 'string', 'float', 'boolean')) ||
				isset($map[$field]['expression'])
			)
			{
				unset($filter[$field]);
			}

			$locationRequred = true;
		}

		// data join, only if extended select specified

		if($locationRequred && $filterByPhrase)
			$query['JOIN'][] = "inner join ".Location\LocationTable::getTableName()." L on A.LOCATION_ID = L.ID";

		if($nameRequired)
			$query['JOIN'][] = "inner join ".Location\Name\LocationTable::getTableName()." NAME on NAME.LOCATION_ID = ".$mainTableJoinCondition; //  and N.LANGUAGE_ID = 'ru'

		// making select
		if(is_array($parameters['select']))
		{
			$select = array();
			foreach($parameters['select'] as $alias => $field)
			{
				if($field != 'NAME.NAME' && $field != 'NAME.LANGUAGE_ID')
					$field = 'L.'.$dbHelper->forSql($field);

				if((string) $alias === (string) intval($alias))
					$select[] = $field;
				else
					$select[] = $field.' as '.$dbHelper->forSql($alias);
			}

			$sqlSelect = implode(', ', $select);
		}
		else
			$sqlSelect = $mainTableJoinCondition.' as ID';

		// making filter
		foreach($filter as $field => $params)
		{
			if($field != 'NAME.NAME' && $field != 'NAME.LANGUAGE_ID')
				$field = 'L.'.$dbHelper->forSql($field);

			$query['WHERE'][] = $field.' '.$params['OP']." '".$dbHelper->forSql($params['VALUE'])."'";
		}

		if($filterByPhrase)
		{
			$sql = "
				select ".($dbConnection->getType() != 'mysql' ? '' : 'distinct')/*fix this in more clever way later*/." 
					".$sqlSelect.(\Bitrix\Sale\Location\DB\Helper::needSelectFieldsInOrderByWhenDistinct() ? ', A.RELEVANCY' : '')."

				from ".ChainTable::getTableName()." A

					".implode(' ', $query['JOIN'])."

				".(count($query['WHERE']) ? 'where ' : '').implode(' and ', $query['WHERE'])."

				order by A.RELEVANCY asc
			";
		}
		else
		{
			$sql = "

				select 
					".$sqlSelect."

				from ".Location\LocationTable::getTableName()." L

					".implode(' ', $query['JOIN'])."

				".(count($query['WHERE']) ? 'where ' : '').implode(' and ', $query['WHERE'])."
			";
		}

		$offset = intval($parameters['offset']);
		$limit = intval($parameters['limit']);

		if($limit)
			$sql = $dbHelper->getTopSql($sql, $limit, $offset);

		$res = $dbConnection->query($sql);

		return $res;
	}

	/**
	*
	*
	* @param
	*
	* @return
	*/
	protected static function findNoIndex($parameters)
	{
		$dbConnection = Main\HttpApplication::getConnection();
		$dbHelper = $dbConnection->getSqlHelper();

		// tables
		$locationTable = Location\LocationTable::getTableName();
		$locationNameTable = Location\Name\LocationTable::getTableName();
		$locationGroupTable = Location\GroupLocationTable::getTableName();
		$locationSiteTable = Location\SiteLocationTable::getTableName();
		$locationTypeTable = Location\TypeTable::getTableName();

		//////////////////////////////////
		// sql parameters prepare
		//////////////////////////////////

		$filter = static::parseFilter($parameters['filter']);

		if(strlen($filter['SITE_ID']['VALUE']))
		{
			$filterSite = $dbHelper->forSql(substr($filter['SITE_ID']['VALUE'], 0, 2));

			$hasLocLinks = Location\SiteLocationTable::checkLinkUsage($filterSite, Location\SiteLocationTable::DB_LOCATION_FLAG);
			$hasGrpLinks = Location\SiteLocationTable::checkLinkUsage($filterSite, Location\SiteLocationTable::DB_GROUP_FLAG);
			$doFilterBySite = true;
		}
		if(strlen($filter['PHRASE']['VALUE']))
		{
			$doFilterByName = true;

			$filterName = ToUpper($dbHelper->forSql($filter['PHRASE']['VALUE']));
		}

		if(intval($filter['ID']['VALUE']))
		{
			$doFilterById = true;
			$filterId = intval($filter['ID']['VALUE']);
		}
		if(intval($filter['CODE']['VALUE']))
		{
			$doFilterByCode = true;
			$filterCode = $dbHelper->forSql($filter['CODE']['VALUE']);
		}

		$doFilterByLang = true;
		if(strlen($filter['NAME.LANGUAGE_ID']['VALUE']))
		{
			$filterLang = $dbHelper->forSql(substr($filter['NAME.LANGUAGE_ID']['VALUE'], 0, 2));
		}
		else
			$filterLang = LANGUAGE_ID;

		if(isset($filter['PARENT_ID']) && intval($filter['PARENT_ID']['VALUE']) >= 0)
		{
			$doFilterByParent = true;
			$filterParentId = intval($filter['PARENT_ID']['VALUE']);
		}
		if(intval($filter['TYPE_ID']['VALUE']))
		{
			$doFilterByType = true;
			$filterTypeId = intval($filter['TYPE_ID']['VALUE']);
		}

		// filter select fields
		if(!is_array($parameters['select']))
			$parameters['select'] = array();

		$map = Location\LocationTable::getMap();
		$nameAlias = false;
		foreach($parameters['select'] as $alias => $field)
		{
			if($field == 'CHILD_CNT')
				$doCountChildren = true;

			if($field == 'NAME.NAME')
				$nameAlias = $alias;

			if(/*in_array($field, array('ID', 'CODE', 'SORT', 'LEFT_MARGIN', 'RIGHT_MARGIN')) || */
				!isset($map[$field]) || 
				!in_array($map[$field]['data_type'], array('integer', 'string', 'float', 'boolean')) ||
				isset($map[$field]['expression'])
			)
			{
				unset($parameters['select'][$alias]);
			}
		}

		//////////////////////////////////
		// sql query build
		//////////////////////////////////

		// mandatory fields to be selected anyway
		// alias => field
		$fields = array(
			'L.ID' => 'L.ID',
			'L.CODE' => 'L.CODE',
			'L.SORT' => 'L.SORT',
			'LT_SORT' => 'LT.DISPLAY_SORT'
		);

		if($nameAlias === false || !preg_match('#^[a-zA-Z0-9]+$#', $nameAlias))
		{
			$fields['NAME'] = 'LN.NAME';
		}
		else
		{
			$fields[$nameAlias] = 'LN.NAME';
		}

		$fields = array_merge($fields, array(
			'L.LEFT_MARGIN' => 'L.LEFT_MARGIN',
			'L.RIGHT_MARGIN' => 'L.RIGHT_MARGIN'
		));

		$groupFields = $fields;

		// additional fields to select
		foreach($parameters['select'] as $alias => $fld)
		{
			$lFld = 'L.'.$fld;
			// check if field is already selected
			if((string) $alias === (string) intval($alias))
			{
				// already selected
				if(in_array($lFld, $fields))
					continue;

				$fields[$lFld] = $lFld;
				//$groupFields[$lFld] = $lFld;
			}
			else // alias is not a number
			{
				if(isset($fields[$alias]))
					continue;

				$fields[$alias] = $lFld;
				//$groupFields[$alias] = $lFld;
			}

			$groupFields[$lFld] = $lFld;
		}

		if($doCountChildren)
			$fields['CHILD_CNT'] = 'COUNT(LC.ID)';

		// make select sql
		$selectSql = array();
		foreach($fields as $alias => $fld)
		{
			if($fld == $alias)
				$selectSql[] = $fld;
			else
				$selectSql[] = $fld.' as '.$alias;
		}

		$selectSql = implode(', ', $selectSql);
		//$groupSql = implode(', ', array_keys($groupFields));
		$groupSql = implode(', ', $groupFields);

		$mainSql = "select {$selectSql}
						from {$locationTable} L 
							inner join {$locationNameTable} LN on L.ID = LN.LOCATION_ID
							inner join {$locationTypeTable} LT on L.TYPE_ID = LT.ID ".

							($doCountChildren ? "
								left join {$locationTable} LC on L.ID = LC.PARENT_ID
							" : "")." 

						%SITE_FILTER_CONDITION%

						where 

							%MAIN_FILTER_CONDITION%

							%GROUP_BY%
							";

		$where = array();

		if($doFilterByLang)
			$where[] = "LN.LANGUAGE_ID = '".$filterLang."'";

		if($doFilterByParent)
			$where[] = "L.PARENT_ID = '".$filterParentId."'";

		if($doFilterById)
			$where[] = "L.ID = '".$filterId."'";

		if($doFilterByCode)
			$where[] = "L.CODE = '".$filterCode."'";

		if($doFilterByType)
			$where[] = "L.TYPE_ID = '".$filterTypeId."'";

		if($doFilterByName)
			$where[] = "LN.NAME_UPPER like '".$filterName."%'";

		$mainSql = 			str_replace('%MAIN_FILTER_CONDITION%', implode(' and ', $where), $mainSql);
		$needDistinct = 	false;
		$unionized = 		false;
		$artificialNav = 	false;

		if(!$doFilterBySite)
		{
			$sql = str_replace('%SITE_FILTER_CONDITION%', '', $mainSql);
		}
		else
		{
			$sql = array();
			if($hasLocLinks)
			{
				$sql[] = str_replace('%SITE_FILTER_CONDITION%', "

					inner join {$locationTable} L2 on L2.LEFT_MARGIN <= L.LEFT_MARGIN and L2.RIGHT_MARGIN >= L.RIGHT_MARGIN
					inner join {$locationSiteTable} LS2 on L2.ID = LS2.LOCATION_ID and LS2.LOCATION_TYPE = 'L' and LS2.SITE_ID = '{$filterSite}'

				", $mainSql);
			}
			if($hasGrpLinks)
			{
				$sql[] = str_replace('%SITE_FILTER_CONDITION%', "

					inner join {$locationTable} L2 on L2.LEFT_MARGIN <= L.LEFT_MARGIN and L2.RIGHT_MARGIN >= L.RIGHT_MARGIN
					inner join {$locationGroupTable} LG on LG.LOCATION_ID = L2.ID
					inner join {$locationSiteTable} LS2 on LG.LOCATION_GROUP_ID = LS2.LOCATION_ID and LS2.LOCATION_TYPE = 'G' and LS2.SITE_ID = '{$filterSite}'

				", $mainSql);

				$useDistinct = true;
			}

			$cnt = count($sql);

			if($cnt == 1)
			{
				$needDistinct = true;
			}
			else
			{
				// UNION removes duplicates, so distinct is required only when no union here
				$unionized = true;
			}

			$sql = ($cnt > 1 ? '(' : '').implode(') union (', $sql).($cnt > 1 ? ')' : '');
		}

		// set groupping if needed
		$sql = str_replace('%GROUP_BY%', $needDistinct || $doCountChildren ? "group by {$groupSql}" : '', $sql);

		if(!is_array($parameters['order']))
		{
			$sql .= " order by 3, 4 asc, 5";
		}
		else
		{
			// currenly spike
			if(isset($parameters['order']['NAME.NAME']))
				$sql .= " order by 5 ".($parameters['order']['NAME.NAME'] == 'asc' ? 'asc' : 'desc');
		}

		$offset = intval($parameters['offset']);
		$limit = intval($parameters['limit']);

		if($limit)
		{
			if($dbConnection->getType() == 'mssql')
			{
				// due to huge amount of limitations of windowed functions in transact, using artificial nav here
				// (does not support UNION and integer indices in ORDER BY)
				$artificialNav = true;
			}
			else
			{
				$sql = $dbHelper->getTopSql($sql, $limit, $offset);
			}
		}

		$res = $dbConnection->query($sql);

		if($artificialNav)
		{
			$result = array();
			$i = -1;
			while($item = $res->fetch())
			{
				$i++;

				if($i < $offset)
					continue;

				if($i >= $offset + $limit)
					break;

				$result[] = $item;
			}

			return new DB\ArrayResult($result);
		}
		else
		{
			return $res;
		}
	}
}

