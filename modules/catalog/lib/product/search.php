<?php
namespace Bitrix\Catalog\Product;

use Bitrix\Catalog,
	Bitrix\Iblock;

/**
 * Class Search
 * Provides various useful methods for product search.
 *
 * @package Bitrix\Catalog\Product
 */
class Search
{
	protected static $catalogList = array();
	
	/**
	 * Fill parameters before create search index.
	 *
	 * @param array $fields		Item fields.
	 * @return array
	 */
	public static function onBeforeIndex($fields)
	{
		if (!isset($fields['MODULE_ID']) || $fields['MODULE_ID'] != 'iblock')
			return $fields;
		if (empty($fields['PARAM2']))
			return $fields;

		if (!isset(self::$catalogList[$fields['PARAM2']]))
		{
			self::$catalogList[$fields['PARAM2']] = false;
			$catalog = Catalog\CatalogIblockTable::getList(array(
				'select' => array('IBLOCK_ID'),
				'filter' => array('=IBLOCK_ID' => $fields['PARAM2'])
			))->fetch();
			if (!empty($catalog))
				self::$catalogList[$fields['PARAM2']] = $catalog['IBLOCK_ID'];
			unset($catalog);
		}

		if (!empty(self::$catalogList[$fields['PARAM2']]) && isset($fields['ITEM_ID']))
		{
			$fields['PARAMS']['iblock_section'] = array();
			if (strpos($fields['ITEM_ID'], 'S') === false)
			{
				$sections = Iblock\SectionElementTable::getList(array(
					'select' => array('IBLOCK_SECTION_ID'),
					'filter' => array('=IBLOCK_ELEMENT_ID' => $fields['ITEM_ID'], '=ADDITIONAL_PROPERTY_ID' => null)
				));
				while ($section = $sections->fetch())
				{
					/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
					$nav = \CIBlockSection::getNavChain($fields['PARAM2'], $section['IBLOCK_SECTION_ID'], array('ID'));
					while ($chain = $nav->fetch())
						$fields['PARAMS']['iblock_section'][$chain['ID']] = $chain['ID'];
					unset($chain, $nav);
				}
				unset($section, $sections);
			}
			else
			{
				/** @noinspection PhpMethodOrClassCallIsNotCaseSensitiveInspection */
				$nav = \CIBlockSection::getNavChain($fields['PARAM2'], preg_replace('#[^0-9]+#', '', $fields["ITEM_ID"]), array('ID'));
				while ($chain = $nav->fetch())
					$fields['PARAMS']['iblock_section'][$chain['ID']] = $chain['ID'];
				unset($chain, $nav);
			}
			if (!empty($fields['PARAMS']['iblock_section']))
				$fields['PARAMS']['iblock_section'] = array_values($fields['PARAMS']['iblock_section']);
		}
		return $fields;
	}
}