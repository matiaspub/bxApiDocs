<?php
namespace Bitrix\Catalog\Product;

use Bitrix\Main,
	Bitrix\Iblock,
	Bitrix\Catalog;

/**
 * Class Viewed
 * Provides various useful methods for viewed products.
 *
 * @package Bitrix\Catalog\Product
 */
class Viewed
{
	/**
	 * Return parent section id for need section depth.
	 * 
	 * @param int $sectionId			Section id.
	 * @param int $depth				Parent section depth.
	 * @return int|null
	 * @throws Main\ArgumentException
	 */
	public static function getParentSection($sectionId, $depth)
	{
		$sectionId = (int)$sectionId;
		$depth = (int)$depth;
		if ($sectionId <= 0 || $depth <= 0)
			return null;
		$section = Iblock\SectionTable::getList(array(
			'select' => array('ID', 'IBLOCK_ID', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'),
			'filter' => array('=ID' => $sectionId)
		))->fetch();
		if (empty($section))
			return null;
		$section['DEPTH_LEVEL'] = (int)$section['DEPTH_LEVEL'];
		if ($section['DEPTH_LEVEL'] < $depth)
			return null;
		if ($section['DEPTH_LEVEL'] == $depth)
			return (int)$section['ID'];

		$parentSection = Iblock\SectionTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=IBLOCK_ID' => $section['IBLOCK_ID'],
				'<=LEFT_MARGIN' => $section['LEFT_MARGIN'],
				'>=RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
				'=DEPTH_LEVEL' => $depth
			)
		))->fetch();
		if (!empty($parentSection))
			return (int)$parentSection['ID'];

		return null;
	}
}