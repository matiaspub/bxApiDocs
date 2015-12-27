<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;

Loc::loadMessages(__FILE__);

class DefaultSiteHelper extends Helper
{
	const LIST_PAGE_URL = 'sale_location_default_list.php';
	const EDIT_PAGE_URL = 'sale_location_default_edit.php';

	#####################################
	#### Entity settings
	#####################################

	/**
	* Function returns class name for an attached entity
	* @return string Entity class name
	*/
	static public function getEntityRoadMap()
	{
		return array(
			'main' => array(
				'name' => 'Bitrix\Main\Site',
				'pages' => array(
					'list' => array(
						'includedColumns' => array('SITE_NAME', 'COUNT')
					),
					'detail' => array(
						'includedColumns' => array()
					)
				),
				'additional' => array(
					'SITE_NAME' => array(
						'data_type' => 'string',
						'title' => Loc::getMessage('SALE_LOCATION_ADMIN_DEFAULT_SITE_HELPER_ENTITY_SITE_NAME_FIELD')
					),
					'COUNT' => array(
						'data_type' => 'integer',
						'title' => Loc::getMessage('SALE_LOCATION_ADMIN_DEFAULT_SITE_HELPER_ENTITY_COUNT_FIELD')
					),
				),
				'primaryFieldName' => 'LID'
			),
			'location' => array(
				'name' => 'Bitrix\Sale\Location\DefaultSite'
			)
		);
	}

	#####################################
	#### CRUD wrappers
	#####################################

	public static function proxyUpdateRequest($data)
	{
		// strip away empty and removed items
		foreach($data['LOCATION'] as $id => $item)
		{
			if($item['REMOVE'] == 1 || !strlen($item['LOCATION_CODE']))
				unset($data['LOCATION'][$id]);
		}

		return $data['LOCATION'];
	}

	public static function proxyListRequest($page)
	{
		$request = array();

		if($page == 'list')
		{
			$request['runtime']['DEFAULT_LOCATION'] = array(
				'data_type' => '\Bitrix\Sale\Location\DefaultSite',
				'reference' => array(
					'=this.LID' => 'ref.SITE_ID'
				),
				'join_type' => 'left'
			);

			$request['runtime']['COUNT'] = array(
				'data_type' => 'integer',
				'expression' => array(
					'count(%s)',
					'DEFAULT_LOCATION.LOCATION_CODE'
				)
			);

			$request['select'] = array(
				'COUNT',
				'NAME',
				'SITE_ID' => 'LID'
			);
		}
		elseif($page == 'detail')
		{
			$id = strlen($_REQUEST['id']) ? self::tryParseSiteId($_REQUEST['id']) : false;

			if($id)
				$request['filter']['=LID'] = $id;
		}

		return $request;
	}

	// block add handle, nothing to add
	public static function add()
	{
		throw new Main\NotSupportedException(Loc::getMessage('SALE_LOCATION_ADMIN_DEFAULT_SITE_HELPER_ADD_OP_UNSUPPORTED'));
	}

	public static function update($siteId, $data)
	{
		$success = true;
		$entityClass = static::getEntityClass('location');

		$data = static::proxyUpdateRequest($data);
		$errors = static::validateUpdateRequest($data);

		unset($data['ID']);

		if(empty($errors))
			$entityClass::updateMultipleForOwner($siteId, $data); // result is always successfull, unless an exception thrown

		return array(
			'success' => $success,
			'errors' => $errors
		);
	}

	// block delete handle, nothing to delete
	public static function delete()
	{
		throw new Main\NotSupportedException(Loc::getMessage('SALE_LOCATION_ADMIN_DEFAULT_SITE_HELPER_DELETE_OP_UNSUPPORTED'));
	}

	// avoid paging here, kz its based on ID which is absent for this table
	public static function getList($parameters = array(), $tableId = false)
	{
		$entityClass = static::getEntityClass();

		// only active sites to show
		if(is_array($parameters))
		{
			$parameters['filter']['=ACTIVE'] = 'Y';
		}

		return new \CAdminResult($entityClass::getList($parameters), $tableId);
	}

	public static function getFormData($id)
	{
		$formData = parent::getFormData($id);
		$formData = array_merge($formData, static::getDefaultLocationList($id));

		return $formData;
	}

	public static function getNameToDisplay($siteId)
	{
		$entityClass = static::getEntityClass('main');

		$site = $entityClass::getById($siteId)->fetch();
		return $site['SITE_NAME'];
	}

	#####################################
	#### Entity-specific
	#####################################

	public static function tryParseSiteId($sid)
	{
		return htmlspecialcharsbx(substr($sid, 0, 2));
	}

	public static function getDefaultLocationList($siteId)
	{
		$entityClass = static::getEntityClass('location');

		$res = $entityClass::getList(array(
			'filter' => array(
				'=SITE_ID' => $siteId
			),
			//'order' => array('SORT' => 'asc')
		));
		$index = array();
		while($item = $res->fetch())
			$index['LOCATION'][] = $item;
		
		return $index;
	}
}