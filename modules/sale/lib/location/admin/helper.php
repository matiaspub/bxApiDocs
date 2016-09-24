<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

use Bitrix\Main;
use Bitrix\Main\Config;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Location;

abstract class Helper
{
	const DEBUG_MODE_OPT = 'location2_debug_mode';

	const IMPORT_PAGE_URL = 'sale_location_import.php';
	const REINDEX_PAGE_URL = 'sale_location_reindex.php';
	const MIGRATION_PAGE_URL = 'sale_location_migration.php';

	const LOCATION_LINK_DATA_CACHE_TAG = 'sale-location-data';

	#####################################
	#### Entity settings
	#####################################

	abstract public function getEntityRoadMap();

	public static function getEntityRoadCode()
	{
		return 'main';
	}

	// this should be overlapped for each ancestor
	public static function getColumns($page)
	{
		// in the middle of extension, should be like this:
		//return array_merge(parent::getColumns(), self::getMap());

		return self::getMap($page);
	}

	// get part of the whole field map for responsibility zone of the current entity
	// call this only with self::
	public static function getMap($page)
	{
		static $flds;

		if($flds == null)
			$flds = static::readMap(self::getEntityRoadCode(), $page);

		return $flds;
	}

	#####################################
	#### CRUD wrappers
	#####################################

	// columns shown in all grids
	public static function getListGridColumns()
	{
		$columns = static::getColumns('list');
		foreach($columns as &$col)
			$col['DEFAULT'] = true;

		return $columns;
	}

	// columns shown in all filters
	public static function getFilterColumns()
	{
		$columns = static::getColumns('list');
		foreach($columns as &$col)
			$col['DEFAULT'] = true;

		return $columns;
	}

	// columns shown in all forms
	public static function getDetailPageRows()
	{
		return static::getColumns('list');
	}

	// generalized filter to orm filter proxy
	public static function getParametersForList($proxed)
	{
		$columns = self::getMap('list'); // columns only for 'main' class

		$parameters = array();

		// filter
		$filter = array();
		if(is_array($proxed['FILTER']) && !empty($proxed['FILTER']))
		{
			foreach($columns as $code => $fld)
			{
				if($fld['data_type'] == 'integer' || $fld['data_type'] == 'float')
				{
					// range or list expected

					if(is_array($proxed['FILTER'][$code]))
					{
						if(strlen($proxed['FILTER'][$code]['FROM']) && strlen($proxed['FILTER'][$code]['TO'])) // range
						{
							$filter['><'.$code] = array($proxed['FILTER'][$code]['FROM'], $proxed['FILTER'][$code]['TO']);
						}
						elseif(strlen($proxed['FILTER'][$code]['FROM'])) // greather than
						{
							$filter['>='.$code] = $proxed['FILTER'][$code]['FROM'];
						}
						elseif(strlen($proxed['FILTER'][$code]['TO'])) // less than
						{
							$filter['<='.$code] = $proxed['FILTER'][$code]['TO'];
						}
					}
					elseif(strlen($proxed['FILTER'][$code]))
						$filter['='.$code] = (string) $proxed['FILTER'][$code];
				}
				else
				{
					if(strlen($proxed['FILTER'][$code]))
						$filter[static::getFilterModifier($fld['data_type']).$code] = $proxed['FILTER'][$code];
				}
			}
		}

		if(!empty($filter))
			$parameters['filter'] = $filter;

		// select
		foreach($columns as $code => $col)
			$parameters['select'][] = $code;

		// order
		if(is_array($proxed['ORDER']) && !empty($proxed['ORDER']))
			$parameters['order'] = $proxed['ORDER'];
		
		// nav (unused)
		if(($page = intval($proxed['NAV']['PAGE_NUM'])) && ($lop = intval($proxed['NAV']['LOP'])))
		{
			$roadMap = static::getEntityRoadMap();
			$road = $roadMap[self::getEntityRoadCode()]['name'];
			$class = $road.'Table';

			$count = $class::getList(array(
				'filter' => is_array($parameters['filter']) ? $parameters['filter'] : array(),
				'select' => array('CNT'),
				'runtime' => array(
					'CNT' => array(
						'data_type' => 'integer',
						'expression' => array(
							'count(%u)',
							'ID'
						)
					)
				)
			))->fetch();

			$bounds = Main\DB\Paginator::calculateQueryLimits($count['CNT'], $page, $lop);
			$parameters['offset'] = $bounds[0];
			$parameters['limit'] = $bounds[1];
		}

		return $parameters;
	}

	/*
	* $parameters: array of keys: FILTER (generalized), ID, OPERATION
	*/
	public static function performGridOperations($parameters)
	{
		$result = array(
			'sucess' => true,
			'errors' => array()
		);

		@set_time_limit(0);

		if(is_array($parameters['ID']) && !empty($parameters['ID']))
		{
			$parameters['ID'] = array_unique($parameters['ID']);
			foreach($parameters['ID'] as $id)
			{
				$res = static::delete($id);
				if(!$res['success'])
				{
					$result['success'] = false;
					$result['errors'] = array_merge($result['errors'], $res['errors']);
				}
			}
		}
		else if(is_array($parameters['FILTER'])) // filter can be empty
		{
			$entityClass = static::getEntityClass();
			$parameters = Helper::getParametersForList($parameters); // from generalized to orm
			$resItems = $entityClass::getList(array('filter' => $parameters['filter'], 'select' => array('ID')));

			while ($item = $resItems->fetch())
			{
				$res = static::delete($item['ID']);
				if(!$res['success'])
				{
					$result['success'] = false;
					$result['errors'] = array_merge($result['errors'], $res['errors']);
				}
			}
		}

		return $result;
	}

	// get data to display in a form
	public static function getFormData($id)
	{
		$parameters = static::proxyListRequest('detail');
		$parameters['filter']['='.static::getPrimaryFieldName()] = $id;

		$formData = static::getList($parameters)->fetch();

		if(!is_array($formData) || empty($formData))
			throw new Main\SystemException(Loc::getMessage('SALE_LOCATION_E_ITEM_NOT_FOUND'));

		return $formData;
	}

	public static function makeSafeDisplay(&$value, $code)
	{
		$columns = static::getColumns('');

		if(!empty($columns[$code]))
		{
			if(!strlen($value) && strlen($columns[$code]['default']))
				$value = $columns[$code]['default'];

			switch($columns[$code]['data_type'])
			{
				case 'integer':
					$value = intval($value);
					break;
				case 'float':
					$value = floatval($value);
					break;
				default:
					$value = htmlspecialcharsbx($value);
			}
		}
		else
			$value = htmlspecialcharsbx($value);

		return $value;
	}

	##############################################
	##############################################
	##############################################

	public static function validateUpdateRequest($data)
	{
		return array();
	}

	// this function could be much more complicated in the derivative classes
	public static function proxyUpdateRequest($data)
	{
		unset($data['ID']); // drop id if presents

		$proxed = array();
		$columns = static::getColumns('list');

		foreach($columns as $code => $void)
		{
			if(isset($data[$code]))
				$proxed[$code] = $data[$code];
		}

		return $proxed;
	}

	// an adapter from CAdminList + CAdminFilter to ORM getList() logic
	// deprecated: too strong relation with admin grid, replaced with getParametersForList
	public static function proxyListRequest($page)
	{
		global $by;
		global $order;

		$columns = self::getMap($page); // columns only for 'main' class

		$parameters = array('filter' => array());

		foreach($columns as $code => $col)
			$parameters['select'][] = $code;

		$filter = array();
		if(self::checkUseFilter())
		{
			foreach($columns as $code => $fld)
			{
				$from = 'find_'.$code.'_1';
				$to = 'find_'.$code.'_2';

				if($fld['data_type'] == 'integer' && (isset($GLOBALS[$from]) || isset($GLOBALS[$to])))
				{
					// range expected

					if(strlen($GLOBALS[$from]) && strlen($GLOBALS[$to])) // range
					{
						$filter['><'.$code] = array($GLOBALS[$from], $GLOBALS[$to]);
					}
					elseif(strlen($GLOBALS[$from])) // greather than
					{
						$filter['>='.$code] = $GLOBALS[$from];
					}
					elseif(strlen($GLOBALS[$to])) // less than
					{
						$filter['<='.$code] = $GLOBALS[$to];
					}
				}
				else
				{
					if(strlen($GLOBALS['find_'.$code]))
						$filter[static::getFilterModifier($fld['data_type']).$code] = $GLOBALS['find_'.$code];
				}
			}
		}

		if(!empty($filter))
			$parameters['filter'] = $filter;
		if(strlen($by))
		{
			$columns = static::getColumns($page); // check if that column really exists, for the whole extension hierarchy

			if(isset($columns[$by]))
				$parameters['order'] = array($by => isset($order) ? $order : 'asc');
		}

		return $parameters;
	}

	// crud over entity: add
	public static function add($data)
	{
		$success = true;
		$id = false;
		$entityClass = static::getEntityClass();

		$data = static::convertToArray($data);

		$data = static::proxyUpdateRequest($data);
		$errors = static::validateUpdateRequest($data);

		if(empty($errors))
		{
			$res = $entityClass::add($data);
			if(!$res->isSuccess())
			{
				$success = false;
				$errors = $res->getErrorMessages();
			}
			else
				$id = $res->getId();
		}
		else
			$success = false;

		return array(
			'success' => $success,
			'errors' => $errors,
			'id' => $id
		);
	}

	// crud over entity: update
	public static function update($primary, $data)
	{
		$success = true;
		$entityClass = static::getEntityClass();

		$data = static::convertToArray($data);
		$data = static::proxyUpdateRequest($data);
		$errors = static::validateUpdateRequest($data);

		if(empty($errors))
		{
			$res = $entityClass::update($primary, $data);
			if(!$res->isSuccess())
			{
				$success = false;
				$errors = $res->getErrorMessages();
			}
		}
		else
			$success = false;

		return array(
			'success' => $success,
			'errors' => $errors
		);
	}

	// crud over entity: delete
	public static function delete($primary)
	{
		$success = true;
		$errors = array();
		$entityClass = static::getEntityClass();

		$res = $entityClass::delete($primary);
		if(!$res->isSuccess())
		{
			$success = false;
			$errors = $res->getErrorMessages();
		}

		return array(
			'success' => $success,
			'errors' => $errors
		);
	}

	// function calculates limit and offset for sql select query, based on current request and session
	// variables, then forms fake old-style database result
	public static function getList($parameters = array(), $tableId = false, $navigation = 20)
	{
		$entityClass = static::getEntityClass();

		$navNum = $GLOBALS['NavNum'] + 1;
		$unique = md5($GLOBALS['APPLICATION']->GetCurPage());
		$showAll = $_SESSION[$unique.'SESS_ALL_'.$navNum] || $_GET['SHOWALL_'.$navNum];

		if(ADMIN_SECTION === true && strlen($tableId))
		{
			$result = new \CSaleProxyAdminResult($parameters, $entityClass, $tableId); // being in admin and knowing table, do admin result api call
		}
		else
		{
			$result = new \CSaleProxyResult($parameters, $entityClass); // otherwise - public api call
		}

		if(!$showAll && $navigation !== false)
		{
			if($navigation === true)
			{
				$result->NavStart();
			}
			else
			{
				$result->NavStart($navigation);
			}
		}
		else
		{
			$result->NavStart();
		}

		// temporal fix
		$result->bShowAll = false;

		return $result;
	}

	public static function convertToArray($data)
	{
		if(!is_array($data))
		{
			$converted = array();
			foreach($data as $key => $value)
				$converted[$key] = $value;

			$data = $converted;
		}

		foreach($data as &$value)
		{
			if(is_string($value))
				$value = trim($value);
		}

		return $data;
	}

	// deprecated: not optimal
	public static function getIdsByFilter($listFilter)
	{
		$ids = array();
		$entityClass = static::getEntityClass();

		$res = $entityClass::getList(array(
			'select' => array('ID'),
			'filter' => is_array($listFilter) ? $listFilter : array()
		));
		while($item = $res->fetch())
		{
			$ids[] = intval($item['ID']);
		}

		return $ids;
	}

	public static function getPrimaryFieldName()
	{
		$map = static::getEntityRoadMap();

		return strlen($map['main']['primaryFieldName']) ? $map['main']['primaryFieldName'] : 'ID';
	}

	// returns element name by it`s primary
	public static function getNameToDisplay($id)
	{
		if(!($id = intval($id)))
			return '';

		$entityClass = static::getEntityClass('main');

		$item = $entityClass::getById($id)->fetch();
		return $item['CODE'];
	}

	public static function getListUrl($parameters = array())
	{
		return self::getUrl(static::LIST_PAGE_URL, $parameters);
	}

	public static function getEditUrl($parameters = array())
	{
		return self::getUrl(static::EDIT_PAGE_URL, $parameters);
	}

	public static function getImportUrl()
	{
		return self::getUrl(static::IMPORT_PAGE_URL, array());
	}

	public static function getReindexUrl()
	{
		return self::getUrl(static::REINDEX_PAGE_URL, array());
	}

	public static function getMigrationUrl()
	{
		return self::getUrl(static::MIGRATION_PAGE_URL, array());
	}

	public static function getUrl($page, $parameters = array())
	{
		if(!is_array($parameters))
			$parameters = array();

		$parameters['lang'] = LANGUAGE_ID;

		$packed = self::packUrlParameters($parameters);
		return '/bitrix/admin/'.$page.(strlen($packed) ? '?'.$packed : '');
	}

	#####################################
	#### Utilily methods for CRUD
	#####################################

	// deprecated: too strong relation with admin grid
	public static function checkUseFilter()
	{
		return $GLOBALS['filter'] == 'Y' && !$GLOBALS['del_filter'];
	}

	public static function readMap($entityRoadCode, $page = 'list')
	{
		$roads = static::getEntityRoadMap();
		$road = $roads[$entityRoadCode];

		if(!$road['name'])
			throw new Main\SystemException('Undefined entity name in entity map');

		if(!strlen($page))
			$page = 'list';

		$flds = array();
		$class = $road['name'].'Table';
		$excluded = $road['pages'][$page]['excludedColumns'];
		$included = $road['pages'][$page]['includedColumns'];

		$map = $class::getMap();
		if(is_array($road['additional']) && !empty($road['additional']))
			$map = array_merge($map, $road['additional']);

		foreach($map as $fldCode => $fldDesc)
		{
			if((strlen($fldDesc['title']) || $fldDesc['required'] || $fldDesc['primary'] || $fldCode == 'ID'))
			{
				if(is_array($excluded) && in_array($fldCode, $excluded))
					continue;

				if(is_array($included) && !in_array($fldCode, $included))
					continue;

				$fldDesc['title'] = strlen($fldDesc['title']) ? htmlspecialcharsbx($fldDesc['title']) : $fldCode;
				$fldDesc['ownerEntity'] = $road['name']; // map can be cumulative, from several entites, so we need to know who is an owner
				$flds[$fldCode] = $fldDesc;
			}
		}

		return $flds;
	}

	protected static function getFilterModifier($type)
	{
		return $type == 'string' ? '?' : '=';
	}

	protected static function packUrlParameters($parameters = array())
	{
		$params = array();
		foreach($parameters as $param => $value)
		{
			if(strlen($value))
			{
				if(strpos($param, '=') === 0)
				{
					// value goes as-is, unsafe
					$param = substr($param, 1);
				}
				else
					$value = urlencode($value);

				$params[] = urlencode($param).'='.$value;
			}
		}

		return implode('&', $params);
	}

	protected static function getEntityClass($code = '')
	{
		$entityRoad = static::getEntityRoadMap();
		$entityName = $entityRoad[strlen($code) ? $code : self::getEntityRoadCode()]['name'];

		if(!$entityName)
			throw new Main\SystemException('Undefined entity name in helper');

		return $entityName.'Table';
	}

	public static function getWidgetAppearance()
	{
		$appearance = Config\Option::get("sale", "sale_location_selector_appearance");

		if(!strlen($appearance) || !in_array($appearance, array('search', 'steps')))
			return 'steps';

		return $appearance;
	}

	protected static function normalizeList($list, $expectNumeric = true)
	{
		$list = array_unique(array_values($list));
		foreach($list as $i => $id)
		{
			if($expectNumeric)
			{
				if(intval($id) != $id)
					unset($list[$i]);

				$list[$i] = intval($id);
				if(!$list[$i])
					unset($list[$i]);
			}
			else
			{
				if(!strlen($list[$i]))
					unset($list[$i]);
			}
		}

		return $list;
	}

	// proxy between $_REQUEST and resulting array to save links between entites and locations
	public static function prepareLinksForSaving($connectorClass, $links)
	{
		$useIds = !$connectorClass::getUseCodes();
		$useGroups = $connectorClass::getUseGroups();
		$l = $connectorClass::DB_LOCATION_FLAG;
		$g = $connectorClass::DB_GROUP_FLAG;

		if(isset($links[$l]))
		{
			if(is_string($links[$l]))
				$links[$l] = explode(':', $links[$l]);
		}
		else
			$links[$l] = array();

		$links[$l] = self::normalizeList($links[$l], $useIds);

		if(!$useGroups)
			unset($links[$g]);
		else
		{
			if(isset($links[$g]))
			{
				if(is_string($links[$g]))
					$links[$g] = explode(':', $links[$g]);
			}
			else
				$links[$g] = array();

			$links[$g] = self::normalizeList($links[$g], $useIds);
		}

		return $links;
	}

	public static function resetLocationsForEntity($entityId, $locations, $entityName, $expectCodes = false)
	{
		$locList = array();

		if(is_array($locations) && !empty($locations))
		{
			foreach($locations as $loc)
			{
				if($loc['LOCATION_TYPE'] == 'L')
					$locList[Location\Connector::DB_LOCATION_FLAG][] = $loc['LOCATION_ID'];
				elseif($loc['LOCATION_TYPE'] == 'G')
					$locList[Location\Connector::DB_GROUP_FLAG][] = $loc['LOCATION_ID'];
			}
		}

		$entityClass = $entityName.'Table';

		try
		{
			if(!empty($locList) && !$expectCodes)
			{
				$locList[Location\Connector::DB_LOCATION_FLAG] = $entityClass::normalizeLocationList($locList[Location\Connector::DB_LOCATION_FLAG]);

				$gf = Location\Connector::DB_GROUP_FLAG;
				if(!empty($locList[$gf]))
				{
					$groupCodes = array();
					$locList[$gf] = array_flip($locList[$gf]);
					// here we must get codes by ids for groups. There will be no thousands of groups, so we can do the following:
					$res = Location\GroupTable::getList(array('select' => array('ID', 'CODE')));
					while($item = $res->fetch())
					{
						if(isset($locList[$gf][$item['ID']]))
							$groupCodes[$item['CODE']] = 1;
					}

					$locList[$gf] = array_keys($groupCodes);
				}
			}

			$entityClass::resetMultipleForOwner($entityId, $locList);
		}
		catch(Exception $e)
		{
		}
	}
}