<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage bitrix24
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Im\Bot;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Department
{
	const XML_ID = "im_bot";

	public static function getId()
	{
		if (!\Bitrix\Main\Loader::includeModule('intranet') || !\Bitrix\Main\Loader::includeModule('iblock'))
			return 0;

		$departments = \CIntranetUtils::getDeparmentsTree(0, false);
		if (!is_array($departments) || !isset($departments[0][0]))
			return 0;

		$departmentRootId = \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', 0);
		if($departmentRootId <= 0)
			return 0;

		$section = \CIBlockSection::GetList(Array(), Array('ACTIVE' => 'Y', 'IBLOCK_ID' => $departmentRootId, 'XML_ID' => self::XML_ID));
		if ($row = $section->Fetch())
		{
			$sectionId = $row['ID'];
		}
		else
		{
			$section = new \CIBlockSection();
			$sectionId = $section->Add(array(
				'IBLOCK_ID' => $departmentRootId,
				'NAME' => Loc::getMessage('BOT_DEPARTMENT_NAME'),
				'SORT' => 20000,
				'IBLOCK_SECTION_ID' => intval($departments[0][0]),
				'XML_ID' => self::XML_ID
			));
		}
		if (!$sectionId)
		{
			$sectionId = intval($departments[0][0]);
		}

		return $sectionId;
	}
}