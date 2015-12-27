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
use Bitrix\Sale\Location;

Loc::loadMessages(__FILE__);

final class LocationHelper extends NameHelper
{
	const LIST_PAGE_URL = 'sale_location_node_list.php';
	const EDIT_PAGE_URL = 'sale_location_node_edit.php';

	const MENU_MAX_ITEMS_IN = 100;
	const MENU_LOCATION_PARENT_TAG = 'menu_sale_location_tree';

	const MENU_ITEMS_QUERY_STRING_TAG = 'menu_sale_location_tree';
	const MENU_ITEMS_QUERY_STRING_DELIMITER = ':';

	const URL_PARAM_PARENT_ID = 'find_PARENT_ID';
	const URL_PARAM_ID = 'id';

	#####################################
	#### Entity settings
	#####################################

	/**
	* Function returns instructions from where and which columns we take to show in UI
	* @return string Entity class name
	*/
	static public function getEntityRoadMap()
	{
		return array(
			'main' => array(
				'name' => 'Bitrix\Sale\Location\Location',
				'pages' => array(
					'list' => array(
						//'excludedColumns' => array('PARENT_ID')
					),
					'detail' => array(
						'excludedColumns' => array()
					),
				)
			),
			'name' => array(
				'name' => 'Bitrix\Sale\Location\Name\Location',
				'pages' => array(
					'list' => array(
						'includedColumns' => array('NAME', 'SHORT_NAME')
					),
					'detail' => array(
						'includedColumns' => array('NAME', 'SHORT_NAME')
					)
				)
			),
			'external' => array(
				'name' => 'Bitrix\Sale\Location\External',
				'pages' => array(
					'detail' => array(
						'includedColumns' => array('ID', 'XML_ID', 'SERVICE_ID')
					)
				)
			),
		);
	}

	##############################################
	##############################################
	##############################################

	public static function getFilterColumns()
	{
		$columns = static::getColumns('list');
		foreach($columns as $code => &$col)
		{
			$col['DEFAULT'] = in_array($code, array('ID', 'TYPE_ID', 'NAME_'.ToUpper(LANGUAGE_ID), 'SORT'));
		}

		return $columns;
	}

	public static function getListGridColumns()
	{
		$columns = static::getFilterColumns();
		unset($columns['PARENT_ID']);

		return $columns;
	}

	// generalized filter to orm filter proxy
	public static function getParametersForList($proxed)
	{
		$parameters = parent::getParametersForList($proxed);

		if(isset($parameters['order']['TYPE_ID'])) // sorting by TYPE_ID, being set, should work in different way
		{
			$parameters['order']['TYPE.SORT'] = $parameters['order']['TYPE_ID'];
			unset($parameters['order']['TYPE_ID']);
		}

		return $parameters;
	}

	public static function getFormData($id)
	{
		$formData = parent::getFormData($id);

		// external data as a separate independent table
		$formData = array_merge($formData, static::getExternalData($id));

		return $formData;
	}

	##############################################
	##############################################
	##############################################

	// high-level validators used when accepting data typed in form. There can be some misspelling, etc, so additional buisness-logic required
	public static function validateUpdateRequest(&$data)
	{
		$errors = parent::validateUpdateRequest($data);

		// if type is set in data and not empty, it must exist
		$typeError = false;
		if(intval($data['TYPE_ID']))
		{
			$type = Location\TypeTable::getList(array('select' => array('ID'), 'filter' => array('=ID' => intval($data['TYPE_ID']))))->fetch();

			if(!$type)
				$typeError = true;
		}
		else
			$typeError = true;

		if($typeError)
			$errors[] = Loc::getMessage('SALE_LOCATION_ADMIN_LOCATION_HELPER_ENTITY_TYPE_ID_UNKNOWN_ERROR');

		// formally check service ids in EXTERNAL parameter
		if(is_array($data['EXTERNAL']) && !empty($data['EXTERNAL']))
		{
			$services = self::getExternalServicesList();

			foreach($data['EXTERNAL'] as $external)
			{
				if(!isset($services[$external['SERVICE_ID']]))
				{
					$errors[] = Loc::getMessage('SALE_LOCATION_ADMIN_LOCATION_HELPER_ENTITY_UNKNOWN_EXTERNAL_SERVICE_ID_ERROR');
					break;
				}
			}
		}

		return $errors;
	}

	public static function proxyUpdateRequest($data)
	{
		$externals = $data['EXTERNAL'];
		unset($data['EXTERNAL']);

		if(is_array($externals) && !empty($externals))
		{
			foreach($externals as $eId => $external)
			{
				if(!strlen($external['XML_ID']))
					unset($externals[$eId]);
			}
		}

		$data = parent::proxyUpdateRequest($data);

		if(!empty($externals))
			$data['EXTERNAL'] = $externals;

		return $data;
	}

	public static function proxyListRequest($page = 'list')
	{
		$parameters = parent::proxyListRequest($page);

		if($page == 'list') // filter by parent_id in list
		{
			if(!isset($parameters['filter']['=PARENT_ID'])) // value has not came from filter
			{
				if(isset($_REQUEST['find_PARENT_ID']))
					$parameters['filter']['=PARENT_ID'] = intval($_REQUEST['find_PARENT_ID']);
				//else
				//	$parameters['filter']['=PARENT_ID'] = 0;
			}
		}

		if(isset($parameters['order']['TYPE_ID'])) // sorting by TYPE_ID, being set, should work in different way
		{
			$parameters['order']['TYPE.SORT'] = $parameters['order']['TYPE_ID'];
			unset($parameters['order']['TYPE_ID']);
		}

		return $parameters;
	}

	// crud over entity: update
	public static function update($primary, $data, $batch = false)
	{
		$success = true;
		$entityClass = static::getEntityClass();

		$data = static::convertToArray($data);
		$data = static::proxyUpdateRequest($data);
		$errors = static::validateUpdateRequest($data);

		if(empty($errors))
		{
			$res = $entityClass::update($primary, $data, array('RESET_LEGACY' => !$batch));
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
	public static function delete($primary, $batch = false)
	{
		$success = true;
		$errors = array();
		$entityClass = static::getEntityClass();

		$res = $entityClass::delete($primary, array('RESET_LEGACY' => !$batch));
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

	#####################################
	#### Entity-specific
	#####################################

	public static function checkFirstImportDone()
	{
		return \Bitrix\Main\Config\Option::get('sale', 'sale_locationpro_import_performed', '') == 'Y';
	}

	public static function getParentId($id)
	{
		if(!($id = intval($id)))
			return 0;

		$class = static::getEntityClass('main');
		$item = $class::getList(array(
			'filter' => array('=ID' => $id),
			'select' => array('PARENT_ID')
		))->fetch();

		return $item['PARENT_ID'];
	}

	public static function getExternalData($id)
	{
		$res = Location\LocationTable::getExternalData($id);
		$result = array();
		while($item = $res->Fetch())
			$result['EXTERNAL'][$item['ID']] = $item;

		return $result;
	}

	public static function getExternalServicesList()
	{
		static $services;

		if($services == null)
		{
			$res = Location\ExternalServiceTable::getList();
			$services = array();
			while($item = $res->Fetch())
				$services[$item['ID']] = $item;
		}

		return $services;
	}

	/**
	 * @deprecated
	 * 
	 * Use TypeHelper::getTypes() instead
	 */
	public static function getTypeList()
	{
		static $types;

		if($types == null)
		{
			$types = array();
			$typesWithNames = Location\Admin\TypeHelper::getTypes();
			foreach($typesWithNames as $type)
			{
				$types[intval($type['ID'])] = $type['NAME_CURRENT'];
			}
		}

		return $types;
	}

	public static function getExternalMap()
	{
		return static::readMap('external', 'detail');
	}

	public static function checkRequestIsMenuRequest()
	{
		return strpos($_REQUEST['admin_mnu_menu_id'], self::MENU_ITEMS_QUERY_STRING_TAG) !== false;
	}

	public static function getLocationSubMenu()
	{
		// how it works: every time when we call for the next sub-level, we must 
		// obtain not sublevel itself, but a whole parent-tree of it

		$queryParams = self::unPackItemsQueryString();

		$requiredToShow = false;
		// three situations:
		// 1) node id comes in $_REQUEST['admin_mnu_menu_id'] parameter when user walks along left menu tree
		$id = false;
		if(self::checkRequestIsMenuRequest())
		{
			$requiredToShow = true;
			$id = $queryParams['ID'];
		}
		if(!$id)
		{
			// 2) node id comes in $_REQUEST['id'] or in $_REQUEST['parent_id'] when user enters self::LIST_PAGE_URL or self::EDIT_PAGE_URL page
			$page = $GLOBALS['APPLICATION']->GetCurPage();
			if($page == '/bitrix/admin/'.self::LIST_PAGE_URL || $page == '/bitrix/admin/'.self::EDIT_PAGE_URL)
			{
				$requiredToShow = true;

				/*
				if(intval($_REQUEST['id']))
					$id = intval($_REQUEST['id']);
				*/
				if(intval($_REQUEST['find_PARENT_ID']))
					$id = intval($_REQUEST['find_PARENT_ID']);
				elseif(intval($_REQUEST['parent_id']))
					$id = intval($_REQUEST['parent_id']);
			}
		}
		// 3) there is no node id at all

		$tree = array();
		if($requiredToShow)
		{
			$parameters = array(
				'select' => array(
					'ID', 
					'LOCATION_NAME' => 'NAME.NAME',
					'DEPTH_LEVEL',
					'PARENT_ID',
					'CHILD_CNT'
				),
				'filter' => array(
					'=NAME.LANGUAGE_ID' => LANGUAGE_ID
				),
				'order' => array(
					'LOCATION_NAME' => 'asc' // result always come sorted by LEFT_MARGIN, we must resort it by NAME
				)
			);

			if($id)
			{
				$res = Location\LocationTable::getParentTree($id, $parameters, array('SHOW_CHILDREN' => true));
			}
			else
			{
				$res = Location\LocationTable::getChildren(false, $parameters);
			}

			$index = array();
			while($item = $res->Fetch())
			{
				$index[$item['PARENT_ID']][$item['ID']] = array(
					'NAME' => htmlspecialcharsbx($item['LOCATION_NAME']),
					'PARENT_ID' => intval($item['PARENT_ID']),
					'CHILD_CNT' => intval($item['CHILD_CNT'])
				);
			}

			unset($res);
			unset($item);

			self::appendMenuChildren($tree, 0, $index, $queryParams);
		}

		return $tree;
	}

	// transcendental thing that makes location menu work
	public static function packItemsQueryString($parameters = array())
	{
		$inRequest = self::unPackItemsQueryString();

		$query = array(self::MENU_ITEMS_QUERY_STRING_TAG, intval($parameters['ID']));
		if(isset($parameters['LIMIT'])) // limit taken from the argument
			$query[] = intval($parameters['LIMIT']);
		else
		{
			if(self::checkRequestIsMenuRequest() && isset($inRequest['LIMIT'])) // limit taken from request
				$query[] = intval($inRequest['LIMIT']);
			else // limit taken by default
				$query[] = self::MENU_MAX_ITEMS_IN;
		}

		if(isset($parameters['SHOW_CHECKBOX']))
			$query[] = $parameters['SHOW_CHECKBOX'] ? '1' : '0';
		else
		{
			if(self::checkRequestIsMenuRequest() && isset($inRequest['SHOW_CHECKBOX']))
				$query[] = $inRequest['SHOW_CHECKBOX'] ? '1' : '0';
			else
				$query[] = '0';
		}

		return implode(self::MENU_ITEMS_QUERY_STRING_DELIMITER, $query);
	}

	public static function unPackItemsQueryString()
	{
		$path = explode(self::MENU_ITEMS_QUERY_STRING_DELIMITER, $_REQUEST['admin_mnu_menu_id']);

		return array(
			'ID' => intval($path[1]) ? intval($path[1]) : false,
			'LIMIT' => intval($path[2]),
			'SHOW_CHECKBOX' => !!$path[3]
		);
	}

	public static function getListUrl($parent = false, $parameters = array())
	{
		if(!is_array($parameters))
			$parameters = array();

		if($parent !== false)
		{
			//$parameters['filter'] = 'Y';
			//$parameters['set_filter'] = 'Y';
			$parameters[static::URL_PARAM_PARENT_ID] = intval($parent);
			//$parameters['adm_filter_applied'] = '0';
		}

		return parent::getListUrl($parameters);
	}

	public static function getEditUrl($node = false, $parameters = array())
	{
		if(!is_array($parameters))
			$parameters = array();

		if($node != false)
			$parameters[static::URL_PARAM_ID] = $node;

		return self::getUrl(static::EDIT_PAGE_URL, $parameters);
	}

	public static function appendMenuChildren(&$attachTo, $attachWhat, $index, $queryParams)
	{
		$i = 0;
		$overflow = false;
		$inChain = false;
		$limit = self::MENU_MAX_ITEMS_IN; // set always limited to self::MENU_MAX_ITEMS_IN (originally was intval($queryParams['LIMIT']));

		if(empty($index))
			return;

		if(is_array($index[$attachWhat]))
		{
			foreach($index[$attachWhat] as $id => $item)
			{
				if($limit && (!$overflow && $i >= $limit) || ($overflow && $inChain/* the previous item was significant */))
				{
					$overflow = true;

					$attachTo[] =  array(
						"text" => Loc::getMessage("SALE_MENU_LOCATION_THE_REST_OF"),
						"url" => static::getListUrl(intval($item['PARENT_ID'])),
						"module_id" => "sale",
						"parent_menu" => self::packItemsQueryString(array('ID' => $item['PARENT_ID'], 'LIMIT' => $limit, 'SHOW_CHECKBOX' => $queryParams['SHOW_CHECKBOX'])),
					);
				}

				$inChain = isset($index[$id]); // this is important node - it belongs to the chain being displayed

				if(!$overflow || $inChain)
				{
					$node = array(
						"text" => ($queryParams['SHOW_CHECKBOX'] ? '<input type="checkbox" value="'.intval($id).'" />&nbsp;' : '').$item['NAME'],
						"fav_id" => intval($id), // allows javascript to know what item it is
						"url" => static::getListUrl(intval($id)),
						"module_id" => "sale",
						"items_id" => self::packItemsQueryString(array('ID' => $id, 'LIMIT' => $limit, 'SHOW_CHECKBOX' => $queryParams['SHOW_CHECKBOX'])),
						//"skip_chain" => true, // uncomment, if you dont want this menu item figure in breadcrumbs
						"parent_menu" => self::packItemsQueryString(array('ID' => $item['PARENT_ID'], 'LIMIT' => $limit, 'SHOW_CHECKBOX' => $queryParams['SHOW_CHECKBOX'])),
						"more_url" => array( // additional route, which will be treated as an alias when calculating selected menu path
							self::getEditUrl(intval($id)), // when editing existed node
							self::getEditUrl(false, array('parent_id' => intval($id))) // when adding a new node
						)
					);

					if($item['CHILD_CNT'])
					{
						$node['dynamic'] = true;
						$node['items'] = array();

						if($inChain)
							self::appendMenuChildren($node['items'], $id, $index, $queryParams);
					}

					$attachTo[] = $node;

				}
				$i++;
			}
		}
	}

	public static function getLocationStringById($primary, $behaviour = array('INVERSE' => false, 'DELIMITER' => ', ', 'LANGUAGE_ID' => LANGUAGE_ID))
	{
		return static::getLocationStringByCondition(array('=ID' => $primary), $behaviour);
	}

	public static function getLocationStringByCode($primary, $behaviour = array('INVERSE' => false, 'DELIMITER' => ', ', 'LANGUAGE_ID' => LANGUAGE_ID))
	{
		return static::getLocationStringByCondition(array('=CODE' => $primary), $behaviour);
	}

	protected static function getLocationStringByCondition($condition, $behaviour = array('INVERSE' => false, 'DELIMITER' => ', ', 'LANGUAGE_ID' => LANGUAGE_ID))
	{
		if(isset($behaviour) && !is_array($behaviour))
			$behaviour = array();

		if(!isset($behaviour['DELIMITER']))
			$behaviour['DELIMITER'] = ', ';

		if(!isset($behaviour['LANGUAGE_ID']))
			$behaviour['LANGUAGE_ID'] = LANGUAGE_ID;

		try
		{
			$res = Location\LocationTable::getPathToNodeByCondition($condition, array('select' => array('LNAME' => 'NAME.NAME'), 'filter' => array('=NAME.LANGUAGE_ID' => $behaviour['LANGUAGE_ID'])));
			$path = array();
			while($item = $res->fetch())
			{
				$path[] = $item['LNAME'];
			}

			if($behaviour['INVERSE'])
				$path = array_reverse($path);

			return implode($behaviour['DELIMITER'], $path);
		}
		catch(\Bitrix\Main\SystemException $e)
		{
			return '';
		}
	}

	public static function getLocationsByZip($zip, $parameters = array())
	{
		$zip = trim($zip);

		if(!strlen($zip) || !preg_match('#^\d+$#', $zip))
			throw new Main\SystemException('Empty or incorrect zip code passed');

		if(!is_array($parameters))
			$parameters = array();

		$parameters['filter']['=SERVICE.CODE'] = 'ZIP';
		$parameters['filter']['=XML_ID'] = $zip;

		return \Bitrix\Sale\Location\ExternalTable::getList($parameters);
	}

	public static function checkLocationMigrated()
	{
		return Main\Config\Option::get('sale', 'sale_locationpro_migrated', '') == 'Y';
	}

	// checks if new location enabled or not
	public static function checkLocationEnabled()
	{
		return static::checkLocationMigrated();
	}

	// informers
	public static function informAdminLocationDatabaseFailure()
	{
		static::deleteInformer('SALE_LOCATIONPRO_DATABASE_FAILURE');

		$fields = array(
			"MESSAGE" => Loc::getMessage('SALE_LOCATION_ADMIN_LOCATION_HELPER_DATABASE_FAILURE', array(
				'#ANCHOR_IMPORT_URL#' => '<a target="_blank" href="'.static::getImportUrl().'">',
				'#ANCHOR_END#' => '</a>'
			)),
			"TAG" => 'SALE_LOCATIONPRO_DATABASE_FAILURE',
			"MODULE_ID" => "sale",
			"ENABLE_CLOSE" => "Y",
			"PUBLIC_SECTION" => "N"
		);
		\CAdminNotify::Add($fields);
	}
	public static function deleteInformer($informerTag)
	{
		if((string) $informerTag == '')
			return;

		$rsAdminNotify = \CAdminNotify::GetList(array(), array('MODULE_ID'=>'sale', 'TAG' => $informerTag));
		if ($arAdminNotify = $rsAdminNotify->Fetch())
		{
			\CAdminNotify::DeleteByTag($informerTag);
		}
	}

	/**
	 * @deprecated
	 */
	public static function getLocationPathDisplay($primary)
	{
		if(!strlen($primary))
			return '';

		if((string) $primary === (string) intval($primary))
			return static::getLocationStringById($primary);
		else
			return static::getLocationStringByCode($primary);
	}
}