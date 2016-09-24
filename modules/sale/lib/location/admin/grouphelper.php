<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

use Bitrix\Sale\Location;

class GroupHelper extends NameHelper
{
	const LIST_PAGE_URL = 'sale_location_group_list.php';
	const EDIT_PAGE_URL = 'sale_location_group_edit.php';

	#####################################
	#### Entity settings
	#####################################

	/**
	* Function returns class name for an attached entity
	* 
	* @return string Entity class name
	*/
	static public function getEntityRoadMap()
	{
		return array(
			'main' => array(
				'name' => 'Bitrix\Sale\Location\Group',
			),
			'name' => array(
				'name' => 'Bitrix\Sale\Location\Name\Group',
				'pages' => array(
					'list' => array(
						'includedColumns' => array('NAME')
					),
					'detail' => array(
						'includedColumns' => array('NAME')
					)
				)
			),
			'link' => array(
				'name' => 'Bitrix\Sale\Location\GroupLocation',
			),
		);
	}

	#####################################
	#### CRUD wrappers
	#####################################

	public static function add($data)
	{
		$loc = $data['LOC'];
		unset($data['LOC']);

		$result = parent::add($data);
		if($result['success'])
		{
			$entityClass = static::getEntityClass('link');
			$loc = self::prepareLinksForSaving($entityClass, $loc);

			$entityClass::resetMultipleForOwner($result['id'], $loc);
		}

		return $result;
	}

	public static function update($gId, $data)
	{
		$loc = $data['LOC'];
		unset($data['LOC']);

		$result = parent::update($gId, $data);
		if($result['success'])
		{
			$entityClass = static::getEntityClass('link');
			$loc = self::prepareLinksForSaving($entityClass, $loc);

			$entityClass::resetMultipleForOwner($gId, $loc);
		}

		return $result;
	}

	public static function delete($gId)
	{
		$result = parent::delete($gId);
		if($result['success'])
		{
			// update also locations
			$entityClass = static::getEntityClass('link');
			$entityClass::deleteAllForOwner($gId); // we should also remove links when removing group (for other entities this is not always so)
		}

		return $result;
	}
}