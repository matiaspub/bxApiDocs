<?php
namespace Bitrix\Iblock\Helpers\Admin;

use Bitrix\Main\Localization\Loc,
	Bitrix\Iblock;

Loc::loadMessages(__FILE__);

class Property
{
	/**
	 * Returns base type list.
	 *
	 * @param bool $descr			Return with description.
	 * @return array
	 */
	public static function getBaseTypeList($descr = false)
	{
		$descr = ($descr === true);
		if ($descr)
		{
			return array(
				Iblock\PropertyTable::TYPE_STRING => Loc::getMessage('PROPERTY_TYPE_STRING'),
				Iblock\PropertyTable::TYPE_NUMBER => Loc::getMessage('PROPERTY_TYPE_NUMBER'),
				Iblock\PropertyTable::TYPE_LIST => Loc::getMessage('PROPERTY_TYPE_LIST'),
				Iblock\PropertyTable::TYPE_FILE => Loc::getMessage('PROPERTY_TYPE_FILE'),
				Iblock\PropertyTable::TYPE_ELEMENT => Loc::getMessage('PROPERTY_TYPE_ELEMENT'),
				Iblock\PropertyTable::TYPE_SECTION => Loc::getMessage('PROPERTY_TYPE_SECTION')
			);
		}
		return array(
			Iblock\PropertyTable::TYPE_STRING,
			Iblock\PropertyTable::TYPE_NUMBER,
			Iblock\PropertyTable::TYPE_LIST,
			Iblock\PropertyTable::TYPE_FILE,
			Iblock\PropertyTable::TYPE_ELEMENT,
			Iblock\PropertyTable::TYPE_SECTION
		);
	}
}