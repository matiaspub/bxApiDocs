<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

class ExternalServiceHelper extends Helper
{
	const LIST_PAGE_URL = 'sale_location_external_service_list.php';
	const EDIT_PAGE_URL = 'sale_location_external_service_edit.php';

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
				'name' => 'Bitrix\Sale\Location\ExternalService'
			)
		);
	}
}