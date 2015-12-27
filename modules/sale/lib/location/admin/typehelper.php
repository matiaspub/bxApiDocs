<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 *
 * @access private
 */

namespace Bitrix\Sale\Location\Admin;

class TypeHelper extends NameHelper
{
	const LIST_PAGE_URL = 'sale_location_type_list.php';
	const EDIT_PAGE_URL = 'sale_location_type_edit.php';

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
				'name' => 'Bitrix\Sale\Location\Type',
			),
			'name' => array(
				'name' => 'Bitrix\Sale\Location\Name\Type',
				'pages' => array(
					'list' => array(
						'includedColumns' => array('NAME')
					),
					'detail' => array(
						'includedColumns' => array('NAME')
					)
				)
			),
		);
	}

	public static function getTypeCodeIdMapCached()
	{
		static $types;

		if($types == null)
		{
			$res = \Bitrix\Sale\Location\TypeTable::getList(array(
				'select' => array(
					'ID', 'CODE'
				)
			));

			$types = array();
			while($item = $res->Fetch())
			{
				$types['ID2CODE'][intval($item['ID'])] = $item['CODE'];
				$types['CODE2ID'][$item['CODE']] = intval($item['ID']);
			}
		}

		return $types;
	}

	public static function getTypes($params = array('LANGUAGE_ID' => LANGUAGE_ID))
	{
		if(!is_array($params))
			$params = array();

		if(!isset($params['LANGUAGE_ID']))
			$params['LANGUAGE_ID'] = LANGUAGE_ID;

		$result = array();

		$lang = ToLower($params['LANGUAGE_ID']);
		$langMapped = static::mapLanguage($lang);

		$res = \Bitrix\Sale\Location\TypeTable::getList(array(
			'select' => array(
				'*',
				'TNAME' => 'NAME.NAME',
				'TLANGUAGE_ID' => 'NAME.LANGUAGE_ID'
			),
			'order' => array(
				'SORT' => 'asc',
				'NAME.NAME' => 'asc'
			)
		));
		while($item = $res->fetch())
		{
			if(!isset($result[$item['CODE']]))
			{
				$result[$item['CODE']] = array(
					'CODE' => $item['CODE'],
					'ID' => $item['ID'],
					'NAME' => array()
				);
			}

			$result[$item['CODE']]['NAME'][$item['TLANGUAGE_ID']] = $item['TNAME'];
		}

		foreach($result as $code => &$data)
		{
			if((string) $data['NAME'][$lang] != '')
			{
				$name = $data['NAME'][$lang];
			}
			else
			{
				if((string) $data['NAME'][$langMapped] != '')
					$name = $data['NAME'][$langMapped];
				else
					$name = $data['NAME']['en'];
			}

			$data['NAME_CURRENT'] = $name;
		}

		return $result;
	}
}